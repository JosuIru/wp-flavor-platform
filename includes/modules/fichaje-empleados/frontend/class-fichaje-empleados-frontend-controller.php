<?php
/**
 * Frontend Controller para Fichaje de Empleados
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador frontend del módulo Fichaje de Empleados
 */
class Flavor_Fichaje_Empleados_Frontend_Controller {

    /**
     * Instancia del módulo
     *
     * @var Flavor_Chat_Fichaje_Empleados_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Fichaje_Empleados_Module $module Instancia del módulo
     */
    public function __construct($module) {
        $this->module = $module;
        $this->init();
    }

    /**
     * Inicializa el controlador
     */
    public function init() {
        // Registrar shortcodes
        $shortcodes = [
            'fichaje_panel' => 'render_panel_fichaje',
            'fichaje_historial' => 'render_historial',
            'fichaje_resumen' => 'render_resumen',
            'fichaje_boton' => 'render_boton_fichaje',
            'fichaje_estado' => 'render_estado_actual',
            'fichaje_solicitar_cambio' => 'render_formulario_cambio',
            'flavor_fichaje_empleados_acciones' => 'render_acciones',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // Registrar AJAX handlers
        add_action('wp_ajax_fichaje_entrada', [$this, 'ajax_fichar_entrada']);
        add_action('wp_ajax_fichaje_salida', [$this, 'ajax_fichar_salida']);
        add_action('wp_ajax_fichaje_pausa_iniciar', [$this, 'ajax_pausa_iniciar']);
        add_action('wp_ajax_fichaje_pausa_finalizar', [$this, 'ajax_pausa_finalizar']);
        add_action('wp_ajax_fichaje_obtener_estado', [$this, 'ajax_obtener_estado']);
        add_action('wp_ajax_fichaje_obtener_historial', [$this, 'ajax_obtener_historial']);
        add_action('wp_ajax_fichaje_solicitar_cambio', [$this, 'ajax_solicitar_cambio']);

        // Encolar assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Encola CSS y JS del módulo
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $base_url = FLAVOR_CHAT_URL . 'includes/modules/fichaje-empleados/assets/';
        $version = FLAVOR_CHAT_VERSION;

        wp_enqueue_style(
            'flavor-fichaje-empleados',
            $base_url . 'css/fichaje-empleados.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-fichaje-empleados',
            $base_url . 'js/fichaje-empleados.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-fichaje-empleados', 'fichajeEmpleados', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fichaje_empleados_nonce'),
            'strings' => [
                'confirmEntrada' => __('¿Confirmas fichar entrada?', 'flavor-chat-ia'),
                'confirmSalida' => __('¿Confirmas fichar salida?', 'flavor-chat-ia'),
                'confirmPausa' => __('¿Confirmas iniciar pausa?', 'flavor-chat-ia'),
                'confirmReanudar' => __('¿Confirmas reanudar la jornada?', 'flavor-chat-ia'),
                'procesando' => __('Procesando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'exito' => __('Fichaje registrado correctamente', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Determina si cargar assets
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        // Cargar si es página del módulo
        if (strpos($post->post_name, 'fichaje') !== false) {
            return true;
        }

        // Cargar si tiene shortcodes del módulo
        $shortcodes_modulo = [
            'fichaje_panel',
            'fichaje_historial',
            'fichaje_resumen',
            'fichaje_boton',
            'fichaje_estado',
            'fichaje_solicitar_cambio',
            'flavor_fichaje_empleados_acciones',
        ];
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renderiza el panel principal de fichaje
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del panel
     */
    public function render_panel_fichaje($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $atts = shortcode_atts([
            'mostrar_historial' => 'true',
            'mostrar_resumen' => 'true',
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/../views/panel-fichaje.php';
        return ob_get_clean();
    }

    /**
     * Renderiza el historial de fichajes
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del historial
     */
    public function render_historial($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $atts = shortcode_atts([
            'limite' => 30,
            'periodo' => 'mes', // hoy, semana, mes
        ], $atts);

        $fichajes = $this->obtener_fichajes_usuario(get_current_user_id(), $atts);

        ob_start();
        include dirname(__FILE__) . '/../views/historial.php';
        return ob_get_clean();
    }

    /**
     * Renderiza el resumen de horas
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del resumen
     */
    public function render_resumen($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $atts = shortcode_atts([
            'mes' => date('m'),
            'anio' => date('Y'),
        ], $atts);

        $resumen = $this->obtener_resumen_mensual(get_current_user_id(), $atts['mes'], $atts['anio']);

        ob_start();
        include dirname(__FILE__) . '/../views/resumen.php';
        return ob_get_clean();
    }

    /**
     * Renderiza un botón de fichaje rápido
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del botón
     */
    public function render_boton_fichaje($atts = []) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'tipo' => 'auto', // auto, entrada, salida, pausa
            'estilo' => 'grande', // grande, compacto
        ], $atts);

        $estado = $this->obtener_estado_actual(get_current_user_id());

        ob_start();
        ?>
        <div class="fichaje-boton-wrapper fichaje-boton-<?php echo esc_attr($atts['estilo']); ?>"
             data-estado="<?php echo esc_attr($estado['estado']); ?>">

            <?php if ($atts['tipo'] === 'auto'): ?>
                <?php if ($estado['estado'] === 'fuera' || $estado['estado'] === 'sin_fichar'): ?>
                    <button type="button" class="fichaje-btn fichaje-btn-entrada" data-action="entrada">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Fichar Entrada', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($estado['estado'] === 'trabajando'): ?>
                    <div class="fichaje-btns-grupo">
                        <button type="button" class="fichaje-btn fichaje-btn-pausa" data-action="pausa">
                            <span class="dashicons dashicons-coffee"></span>
                            <?php esc_html_e('Pausa', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="fichaje-btn fichaje-btn-salida" data-action="salida">
                            <span class="dashicons dashicons-migrate"></span>
                            <?php esc_html_e('Fichar Salida', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                <?php elseif ($estado['estado'] === 'en_pausa'): ?>
                    <button type="button" class="fichaje-btn fichaje-btn-reanudar" data-action="reanudar">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Reanudar Jornada', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" class="fichaje-btn fichaje-btn-<?php echo esc_attr($atts['tipo']); ?>"
                        data-action="<?php echo esc_attr($atts['tipo']); ?>">
                    <?php echo esc_html($this->get_label_boton($atts['tipo'])); ?>
                </button>
            <?php endif; ?>

            <div class="fichaje-estado-info">
                <span class="fichaje-estado-badge estado-<?php echo esc_attr($estado['estado']); ?>">
                    <?php echo esc_html($this->get_label_estado($estado['estado'])); ?>
                </span>
                <?php if (!empty($estado['ultimo_fichaje'])): ?>
                    <span class="fichaje-ultima-hora">
                        <?php printf(
                            esc_html__('Último: %s a las %s', 'flavor-chat-ia'),
                            esc_html($estado['ultimo_fichaje']['tipo']),
                            esc_html($estado['ultimo_fichaje']['hora'])
                        ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el estado actual
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del estado
     */
    public function render_estado_actual($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $estado = $this->obtener_estado_actual(get_current_user_id());
        $fichajes_hoy = $this->obtener_fichajes_hoy(get_current_user_id());

        ob_start();
        ?>
        <div class="fichaje-estado-container">
            <div class="fichaje-estado-card">
                <div class="fichaje-estado-indicador estado-<?php echo esc_attr($estado['estado']); ?>">
                    <span class="estado-icono"></span>
                    <span class="estado-texto"><?php echo esc_html($this->get_label_estado($estado['estado'])); ?></span>
                </div>

                <?php if (!empty($estado['ultimo_fichaje'])): ?>
                <div class="fichaje-ultimo">
                    <strong><?php esc_html_e('Último fichaje:', 'flavor-chat-ia'); ?></strong>
                    <?php printf(
                        '%s - %s',
                        esc_html(ucfirst($estado['ultimo_fichaje']['tipo'])),
                        esc_html($estado['ultimo_fichaje']['hora'])
                    ); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($fichajes_hoy['horas_trabajadas'])): ?>
                <div class="fichaje-horas-hoy">
                    <strong><?php esc_html_e('Horas hoy:', 'flavor-chat-ia'); ?></strong>
                    <?php printf(
                        esc_html__('%s horas', 'flavor-chat-ia'),
                        number_format($fichajes_hoy['horas_trabajadas'], 2)
                    ); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de solicitud de cambio
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del formulario
     */
    public function render_formulario_cambio($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        ob_start();
        ?>
        <div class="fichaje-form-cambio">
            <h3><?php esc_html_e('Solicitar Corrección de Fichaje', 'flavor-chat-ia'); ?></h3>
            <p class="fichaje-form-desc"><?php esc_html_e('¿Olvidaste fichar? Solicita una corrección aquí.', 'flavor-chat-ia'); ?></p>

            <form id="fichaje-solicitar-cambio-form" class="fichaje-form">
                <div class="fichaje-form-row">
                    <label for="fichaje-fecha"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></label>
                    <input type="date" id="fichaje-fecha" name="fecha" required
                           max="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>

                <div class="fichaje-form-row">
                    <label for="fichaje-tipo"><?php esc_html_e('Tipo de fichaje', 'flavor-chat-ia'); ?></label>
                    <select id="fichaje-tipo" name="tipo" required>
                        <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <option value="entrada"><?php esc_html_e('Entrada', 'flavor-chat-ia'); ?></option>
                        <option value="salida"><?php esc_html_e('Salida', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="fichaje-form-row">
                    <label for="fichaje-hora"><?php esc_html_e('Hora correcta', 'flavor-chat-ia'); ?></label>
                    <input type="time" id="fichaje-hora" name="hora" required>
                </div>

                <div class="fichaje-form-row">
                    <label for="fichaje-motivo"><?php esc_html_e('Motivo', 'flavor-chat-ia'); ?></label>
                    <textarea id="fichaje-motivo" name="motivo" rows="3" required
                              placeholder="<?php esc_attr_e('Explica por qué necesitas esta corrección...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="fichaje-form-actions">
                    <button type="submit" class="fichaje-btn fichaje-btn-submit">
                        <?php esc_html_e('Enviar Solicitud', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <div class="fichaje-form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Fichar entrada
     */
    public function ajax_fichar_entrada() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $notas = sanitize_textarea_field($_POST['notas'] ?? '');
        $resultado = $this->module->execute_action('fichar_entrada', ['notas' => $notas]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al fichar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Fichar salida
     */
    public function ajax_fichar_salida() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $notas = sanitize_textarea_field($_POST['notas'] ?? '');
        $resultado = $this->module->execute_action('fichar_salida', ['notas' => $notas]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al fichar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Iniciar pausa
     */
    public function ajax_pausa_iniciar() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $tipo_pausa = sanitize_text_field($_POST['tipo_pausa'] ?? 'descanso');
        $resultado = $this->module->execute_action('pausar_jornada', ['tipo_pausa' => $tipo_pausa]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al iniciar pausa', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Finalizar pausa
     */
    public function ajax_pausa_finalizar() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $resultado = $this->module->execute_action('reanudar_jornada', []);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al reanudar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener estado actual
     */
    public function ajax_obtener_estado() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $estado = $this->obtener_estado_actual(get_current_user_id());
        wp_send_json_success($estado);
    }

    /**
     * AJAX: Obtener historial
     */
    public function ajax_obtener_historial() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $desde = sanitize_text_field($_POST['desde'] ?? '');
        $hasta = sanitize_text_field($_POST['hasta'] ?? '');
        $limite = absint($_POST['limite'] ?? 30);

        $fichajes = $this->obtener_fichajes_usuario(get_current_user_id(), [
            'desde' => $desde,
            'hasta' => $hasta,
            'limite' => $limite,
        ]);

        wp_send_json_success(['fichajes' => $fichajes]);
    }

    /**
     * AJAX: Solicitar cambio
     */
    public function ajax_solicitar_cambio() {
        check_ajax_referer('fichaje_empleados_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $params = [
            'fecha' => sanitize_text_field($_POST['fecha'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? ''),
            'hora' => sanitize_text_field($_POST['hora'] ?? ''),
            'motivo' => sanitize_textarea_field($_POST['motivo'] ?? ''),
        ];

        $resultado = $this->module->execute_action('solicitar_cambio', $params);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('Error al enviar solicitud', 'flavor-chat-ia')]);
        }
    }

    // =========================================================================
    // Métodos auxiliares
    // =========================================================================

    /**
     * Obtiene el estado actual de fichaje del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array Estado actual
     */
    private function obtener_estado_actual($usuario_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $ultimo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d ORDER BY fecha_hora DESC LIMIT 1",
            $usuario_id
        ));

        if (!$ultimo) {
            return [
                'estado' => 'sin_fichar',
                'ultimo_fichaje' => null,
            ];
        }

        $estados = [
            'entrada' => 'trabajando',
            'salida' => 'fuera',
            'pausa_inicio' => 'en_pausa',
            'pausa_fin' => 'trabajando',
        ];

        return [
            'estado' => $estados[$ultimo->tipo] ?? 'desconocido',
            'ultimo_fichaje' => [
                'tipo' => $ultimo->tipo,
                'hora' => date('H:i', strtotime($ultimo->fecha_hora)),
                'fecha' => date('Y-m-d', strtotime($ultimo->fecha_hora)),
            ],
        ];
    }

    /**
     * Obtiene fichajes del usuario
     *
     * @param int   $usuario_id ID del usuario
     * @param array $args       Argumentos de filtro
     * @return array Fichajes
     */
    private function obtener_fichajes_usuario($usuario_id, $args = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $limite = absint($args['limite'] ?? 30);
        $periodo = $args['periodo'] ?? 'mes';
        $desde = $args['desde'] ?? '';
        $hasta = $args['hasta'] ?? '';

        // Calcular fechas según periodo
        if (empty($desde)) {
            switch ($periodo) {
                case 'hoy':
                    $desde = date('Y-m-d');
                    break;
                case 'semana':
                    $desde = date('Y-m-d', strtotime('-7 days'));
                    break;
                case 'mes':
                default:
                    $desde = date('Y-m-01');
                    break;
            }
        }

        if (empty($hasta)) {
            $hasta = date('Y-m-d');
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE usuario_id = %d
             AND DATE(fecha_hora) >= %s
             AND DATE(fecha_hora) <= %s
             ORDER BY fecha_hora DESC
             LIMIT %d",
            $usuario_id,
            $desde,
            $hasta,
            $limite
        );

        $resultados = $wpdb->get_results($sql);

        return array_map(function($fichaje) {
            return [
                'id' => $fichaje->id,
                'tipo' => $fichaje->tipo,
                'fecha' => date('Y-m-d', strtotime($fichaje->fecha_hora)),
                'hora' => date('H:i', strtotime($fichaje->fecha_hora)),
                'notas' => $fichaje->notas,
                'validado' => (bool) $fichaje->validado,
            ];
        }, $resultados);
    }

    /**
     * Obtiene fichajes del día actual
     *
     * @param int $usuario_id ID del usuario
     * @return array Fichajes de hoy con horas calculadas
     */
    private function obtener_fichajes_hoy($usuario_id) {
        $resultado = $this->module->execute_action('ver_fichajes_hoy', ['usuario_id' => $usuario_id]);
        return $resultado['success'] ? $resultado : ['fichajes' => [], 'horas_trabajadas' => 0];
    }

    /**
     * Obtiene resumen mensual
     *
     * @param int $usuario_id ID del usuario
     * @param int $mes        Mes (1-12)
     * @param int $anio       Año
     * @return array Resumen
     */
    private function obtener_resumen_mensual($usuario_id, $mes, $anio) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $fichajes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE usuario_id = %d
             AND MONTH(fecha_hora) = %d
             AND YEAR(fecha_hora) = %d
             ORDER BY fecha_hora ASC",
            $usuario_id,
            $mes,
            $anio
        ));

        // Agrupar por día y calcular horas
        $fichajes_por_dia = [];
        foreach ($fichajes as $fichaje) {
            $fecha = date('Y-m-d', strtotime($fichaje->fecha_hora));
            if (!isset($fichajes_por_dia[$fecha])) {
                $fichajes_por_dia[$fecha] = [];
            }
            $fichajes_por_dia[$fecha][] = $fichaje;
        }

        $dias_trabajados = 0;
        $total_horas = 0;
        $detalle_dias = [];

        foreach ($fichajes_por_dia as $fecha => $fichajes_dia) {
            $horas_dia = $this->calcular_horas_trabajadas($fichajes_dia);
            if ($horas_dia > 0) {
                $dias_trabajados++;
                $total_horas += $horas_dia;
                $detalle_dias[] = [
                    'fecha' => $fecha,
                    'horas' => round($horas_dia, 2),
                    'fichajes' => count($fichajes_dia),
                ];
            }
        }

        return [
            'mes' => $mes,
            'anio' => $anio,
            'dias_trabajados' => $dias_trabajados,
            'total_horas' => round($total_horas, 2),
            'promedio_horas_diarias' => $dias_trabajados > 0 ? round($total_horas / $dias_trabajados, 2) : 0,
            'detalle_dias' => $detalle_dias,
        ];
    }

    /**
     * Calcula horas trabajadas de un conjunto de fichajes
     *
     * @param array $fichajes Lista de fichajes
     * @return float Horas trabajadas
     */
    private function calcular_horas_trabajadas($fichajes) {
        $horas = 0;
        $ultima_entrada = null;

        foreach ($fichajes as $fichaje) {
            if ($fichaje->tipo === 'entrada' || $fichaje->tipo === 'pausa_fin') {
                $ultima_entrada = strtotime($fichaje->fecha_hora);
            } elseif (($fichaje->tipo === 'salida' || $fichaje->tipo === 'pausa_inicio') && $ultima_entrada) {
                $salida = strtotime($fichaje->fecha_hora);
                $horas += ($salida - $ultima_entrada) / 3600;
                $ultima_entrada = null;
            }
        }

        return round($horas, 2);
    }

    /**
     * Obtiene label para estado
     *
     * @param string $estado Estado
     * @return string Label
     */
    private function get_label_estado($estado) {
        $labels = [
            'sin_fichar' => __('Sin fichar', 'flavor-chat-ia'),
            'trabajando' => __('Trabajando', 'flavor-chat-ia'),
            'en_pausa' => __('En pausa', 'flavor-chat-ia'),
            'fuera' => __('Fuera', 'flavor-chat-ia'),
        ];
        return $labels[$estado] ?? $estado;
    }

    /**
     * Obtiene label para botón
     *
     * @param string $tipo Tipo de botón
     * @return string Label
     */
    private function get_label_boton($tipo) {
        $labels = [
            'entrada' => __('Fichar Entrada', 'flavor-chat-ia'),
            'salida' => __('Fichar Salida', 'flavor-chat-ia'),
            'pausa' => __('Iniciar Pausa', 'flavor-chat-ia'),
            'reanudar' => __('Reanudar Jornada', 'flavor-chat-ia'),
        ];
        return $labels[$tipo] ?? $tipo;
    }

    /**
     * Mensaje para usuarios no logueados
     *
     * @return string HTML
     */
    private function render_login_required() {
        return sprintf(
            '<div class="fichaje-login-required">
                <p>%s</p>
                <a href="%s" class="fichaje-btn">%s</a>
            </div>',
            esc_html__('Debes iniciar sesión para acceder al sistema de fichaje.', 'flavor-chat-ia'),
            esc_url(wp_login_url(flavor_current_request_url())),
            esc_html__('Iniciar sesión', 'flavor-chat-ia')
        );
    }

    /**
     * Renderiza un panel de acciones rápidas de fichaje
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del panel de acciones
     */
    public function render_acciones($atts = []) {
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $atts = shortcode_atts([
            'mostrar_estado' => 'true',
            'mostrar_horas' => 'true',
            'estilo' => 'completo', // completo, compacto, mini
        ], $atts);

        $estado = $this->obtener_estado_actual(get_current_user_id());
        $fichajes_hoy = $this->obtener_fichajes_hoy(get_current_user_id());

        ob_start();
        ?>
        <div class="fichaje-acciones-panel estilo-<?php echo esc_attr($atts['estilo']); ?>">
            <?php if ($atts['mostrar_estado'] === 'true'): ?>
            <div class="fichaje-estado-actual">
                <span class="fichaje-estado-badge estado-<?php echo esc_attr($estado['estado']); ?>">
                    <?php echo esc_html($this->get_label_estado($estado['estado'])); ?>
                </span>
                <?php if (!empty($estado['ultimo_fichaje'])): ?>
                <span class="fichaje-ultimo-registro">
                    <?php printf(
                        esc_html__('Último: %s a las %s', 'flavor-chat-ia'),
                        esc_html($estado['ultimo_fichaje']['tipo']),
                        esc_html($estado['ultimo_fichaje']['hora'])
                    ); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="fichaje-botones-acciones">
                <?php if ($estado['estado'] === 'fuera' || $estado['estado'] === 'sin_fichar'): ?>
                    <button type="button" class="fichaje-btn fichaje-btn-entrada fichaje-btn-primary" data-action="entrada">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Fichar Entrada', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($estado['estado'] === 'trabajando'): ?>
                    <button type="button" class="fichaje-btn fichaje-btn-pausa" data-action="pausa">
                        <span class="dashicons dashicons-coffee"></span>
                        <?php esc_html_e('Pausa', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="fichaje-btn fichaje-btn-salida fichaje-btn-secondary" data-action="salida">
                        <span class="dashicons dashicons-migrate"></span>
                        <?php esc_html_e('Fichar Salida', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($estado['estado'] === 'en_pausa'): ?>
                    <button type="button" class="fichaje-btn fichaje-btn-reanudar fichaje-btn-primary" data-action="reanudar">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Reanudar', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($atts['mostrar_horas'] === 'true' && !empty($fichajes_hoy['horas_trabajadas'])): ?>
            <div class="fichaje-horas-info">
                <span class="fichaje-horas-label"><?php esc_html_e('Horas hoy:', 'flavor-chat-ia'); ?></span>
                <span class="fichaje-horas-valor"><?php echo esc_html(number_format($fichajes_hoy['horas_trabajadas'], 2)); ?>h</span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
