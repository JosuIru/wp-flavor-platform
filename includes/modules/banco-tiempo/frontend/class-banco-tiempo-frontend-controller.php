<?php
/**
 * Frontend Controller para Banco de Tiempo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Banco de Tiempo
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Banco_Tiempo_Frontend_Controller {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks y filtros
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_banco_tiempo_solicitar', [$this, 'ajax_solicitar_servicio']);
        add_action('wp_ajax_banco_tiempo_ofrecer', [$this, 'ajax_ofrecer_servicio']);
        add_action('wp_ajax_banco_tiempo_aceptar', [$this, 'ajax_aceptar_solicitud']);
        add_action('wp_ajax_banco_tiempo_rechazar', [$this, 'ajax_rechazar_solicitud']);
        add_action('wp_ajax_banco_tiempo_completar', [$this, 'ajax_completar_intercambio']);
        add_action('wp_ajax_banco_tiempo_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_banco_tiempo_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_banco_tiempo_filtrar', [$this, 'ajax_filtrar']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_template']);
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-banco-tiempo',
            $base_url . '/assets/css/banco-tiempo.css',
            [],
            $version
        );

        // JS
        wp_register_script(
            'flavor-banco-tiempo',
            $base_url . '/assets/js/banco-tiempo.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-banco-tiempo', 'flavorBancoTiempo', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('banco_tiempo_nonce'),
            'i18n' => [
                'solicitud_enviada' => __('Solicitud enviada correctamente', 'flavor-chat-ia'),
                'servicio_publicado' => __('Servicio publicado correctamente', 'flavor-chat-ia'),
                'intercambio_completado' => __('Intercambio completado', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmacion' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'gracias_valoracion' => __('Gracias por tu valoración', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-banco-tiempo');
        wp_enqueue_script('flavor-banco-tiempo');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        add_shortcode('banco_tiempo_servicios', [$this, 'shortcode_servicios']);
        add_shortcode('banco_tiempo_mi_saldo', [$this, 'shortcode_mi_saldo']);
        add_shortcode('banco_tiempo_mis_servicios', [$this, 'shortcode_mis_servicios']);
        add_shortcode('banco_tiempo_mis_intercambios', [$this, 'shortcode_mis_intercambios']);
        add_shortcode('banco_tiempo_ofrecer', [$this, 'shortcode_ofrecer']);
        add_shortcode('banco_tiempo_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('banco_tiempo_ranking', [$this, 'shortcode_ranking']);
        add_shortcode('banco_tiempo_ultimos_intercambios', [$this, 'shortcode_ultimos_intercambios']);
    }

    /**
     * Shortcode: Últimos intercambios de la comunidad
     * Uso: [banco_tiempo_ultimos_intercambios limite="4"]
     */
    public function shortcode_ultimos_intercambios($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 4,
        ], $atts);

        global $wpdb;
        $tabla_intercambios = $wpdb->prefix . 'flavor_banco_tiempo_intercambios';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $intercambios = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, s.titulo, s.categoria,
                    u_of.display_name as oferente_nombre,
                    u_so.display_name as solicitante_nombre
             FROM {$tabla_intercambios} i
             LEFT JOIN {$tabla_servicios} s ON i.servicio_id = s.id
             LEFT JOIN {$wpdb->users} u_of ON i.oferente_id = u_of.ID
             LEFT JOIN {$wpdb->users} u_so ON i.solicitante_id = u_so.ID
             WHERE i.estado = 'completado'
             ORDER BY i.fecha_completado DESC
             LIMIT %d",
            absint($atts['limite'])
        ));

        if (empty($intercambios)) {
            return '<p class="flavor-empty">' . __('No hay intercambios recientes.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-bt-ultimos-intercambios">
            <?php foreach ($intercambios as $intercambio): ?>
                <div class="bt-intercambio-item">
                    <div class="bt-intercambio-icono">
                        <span class="dashicons dashicons-randomize"></span>
                    </div>
                    <div class="bt-intercambio-info">
                        <span class="bt-intercambio-titulo"><?php echo esc_html($intercambio->titulo); ?></span>
                        <span class="bt-intercambio-participantes">
                            <?php echo esc_html($intercambio->oferente_nombre); ?> ↔ <?php echo esc_html($intercambio->solicitante_nombre); ?>
                        </span>
                        <span class="bt-intercambio-horas"><?php echo esc_html($intercambio->horas); ?>h</span>
                    </div>
                    <span class="bt-intercambio-fecha">
                        <?php echo esc_html(human_time_diff(strtotime($intercambio->fecha_completado), current_time('timestamp'))); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de servicios
     */
    public function shortcode_servicios($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
            'columnas' => 3,
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        $this->render_servicios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi saldo
     */
    public function shortcode_mi_saldo($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tu saldo.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_mi_saldo();
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis servicios ofrecidos
     */
    public function shortcode_mis_servicios($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus servicios.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_servicios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis intercambios
     */
    public function shortcode_mis_intercambios($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus intercambios.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_intercambios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario para ofrecer servicio
     */
    public function shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ofrecer un servicio.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();

        ob_start();
        $this->render_formulario_ofrecer();
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de servicio
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $servicio_id = $atts['id'] ?: (isset($_GET['servicio_id']) ? absint($_GET['servicio_id']) : 0);

        if (!$servicio_id) {
            return '<p class="flavor-error">' . __('Servicio no especificado.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        $this->render_detalle_servicio($servicio_id);
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking de la comunidad
     */
    public function shortcode_ranking($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->render_ranking($atts);
        return ob_get_clean();
    }

    /**
     * Renderizar listado de servicios
     */
    private function render_servicios($atts) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_servicios)) {
            echo '<p class="flavor-error">' . __('El módulo de banco de tiempo no está configurado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        $sql = "SELECT * FROM $tabla_servicios WHERE " . implode(' AND ', $where) . " ORDER BY fecha_publicacion DESC LIMIT %d";
        $params[] = intval($atts['limite']);

        $servicios = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $template = dirname(__FILE__) . '/../templates/servicios.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback
            echo '<div class="flavor-bt-servicios grid-' . esc_attr($atts['columnas']) . '">';
            foreach ($servicios as $servicio) {
                $this->render_servicio_card($servicio);
            }
            echo '</div>';
        }
    }

    /**
     * Renderizar mi saldo
     */
    private function render_mi_saldo() {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $usuario_id = get_current_user_id();

        // Calcular saldo
        $horas_dadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
             WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $horas_recibidas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
             WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $saldo = $horas_dadas - $horas_recibidas;

        $template = dirname(__FILE__) . '/../templates/mi-saldo.php';
        if (file_exists($template)) {
            include $template;
        } else {
            ?>
            <div class="flavor-bt-saldo">
                <h3><?php _e('Mi Saldo de Tiempo', 'flavor-chat-ia'); ?></h3>
                <div class="saldo-grid">
                    <div class="saldo-item saldo-dado">
                        <span class="valor"><?php echo esc_html(number_format($horas_dadas, 1)); ?>h</span>
                        <span class="label"><?php _e('Horas Dadas', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="saldo-item saldo-recibido">
                        <span class="valor"><?php echo esc_html(number_format($horas_recibidas, 1)); ?>h</span>
                        <span class="label"><?php _e('Horas Recibidas', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="saldo-item saldo-total <?php echo $saldo >= 0 ? 'positivo' : 'negativo'; ?>">
                        <span class="valor"><?php echo esc_html(($saldo >= 0 ? '+' : '') . number_format($saldo, 1)); ?>h</span>
                        <span class="label"><?php _e('Saldo Actual', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Renderizar mis servicios
     */
    private function render_mis_servicios($atts) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $usuario_id = get_current_user_id();

        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_servicios WHERE usuario_id = %d ORDER BY fecha_publicacion DESC LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        $template = dirname(__FILE__) . '/../templates/mis-servicios.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar mis intercambios
     */
    private function render_mis_intercambios($atts) {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $usuario_id = get_current_user_id();

        $intercambios = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, s.titulo as servicio_titulo
             FROM $tabla_transacciones t
             LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
             WHERE t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d
             ORDER BY t.fecha_solicitud DESC
             LIMIT %d",
            $usuario_id,
            $usuario_id,
            intval($atts['limite'])
        ));

        $template = dirname(__FILE__) . '/../templates/intercambios.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar formulario para ofrecer servicio
     */
    private function render_formulario_ofrecer() {
        $template = dirname(__FILE__) . '/../templates/ofrecer-servicio.php';
        if (file_exists($template)) {
            include $template;
        } else {
            ?>
            <div class="flavor-bt-ofrecer">
                <h3><?php _e('Ofrecer un Servicio', 'flavor-chat-ia'); ?></h3>
                <form id="form-ofrecer-servicio" class="flavor-form">
                    <?php wp_nonce_field('banco_tiempo_nonce', 'bt_nonce_field'); ?>
                    <p>
                        <label><?php _e('Título del servicio', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="titulo" required>
                    </p>
                    <p>
                        <label><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea name="descripcion" rows="4" required></textarea>
                    </p>
                    <p>
                        <label><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select name="categoria" required>
                            <option value="cuidados"><?php _e('Cuidados', 'flavor-chat-ia'); ?></option>
                            <option value="educacion"><?php _e('Educación', 'flavor-chat-ia'); ?></option>
                            <option value="bricolaje"><?php _e('Bricolaje', 'flavor-chat-ia'); ?></option>
                            <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
                            <option value="transporte"><?php _e('Transporte', 'flavor-chat-ia'); ?></option>
                            <option value="otros"><?php _e('Otros', 'flavor-chat-ia'); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><?php _e('Horas estimadas', 'flavor-chat-ia'); ?></label>
                        <input type="number" name="horas_estimadas" min="0.5" max="8" step="0.5" value="1" required>
                    </p>
                    <p>
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php _e('Publicar Servicio', 'flavor-chat-ia'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php
        }
    }

    /**
     * Renderizar detalle de servicio
     */
    private function render_detalle_servicio($servicio_id) {
        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_servicios WHERE id = %d",
            $servicio_id
        ));

        if (!$servicio) {
            echo '<p class="flavor-error">' . __('Servicio no encontrado.', 'flavor-chat-ia') . '</p>';
            return;
        }

        $usuario = get_userdata($servicio->usuario_id);

        $template = dirname(__FILE__) . '/single.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Renderizar ranking
     */
    private function render_ranking($atts) {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_receptor_id as usuario_id, SUM(horas) as total_horas, COUNT(*) as total_servicios
             FROM $tabla_transacciones
             WHERE estado = 'completado'
             GROUP BY usuario_receptor_id
             ORDER BY total_horas DESC
             LIMIT %d",
            intval($atts['limite'])
        ));

        $template = dirname(__FILE__) . '/../templates/ranking-comunidad.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['banco-tiempo-saldo'] = [
            'titulo' => __('Mi Saldo', 'flavor-chat-ia'),
            'icono' => 'dashicons-clock',
            'callback' => [$this, 'render_tab_saldo'],
            'orden' => 30,
            'modulo' => 'banco_tiempo',
        ];

        $tabs['banco-tiempo-intercambios'] = [
            'titulo' => __('Mis Intercambios', 'flavor-chat-ia'),
            'icono' => 'dashicons-randomize',
            'callback' => [$this, 'render_tab_intercambios'],
            'orden' => 31,
            'modulo' => 'banco_tiempo',
        ];

        $tabs['banco-tiempo-servicios'] = [
            'titulo' => __('Mis Servicios', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-tools',
            'callback' => [$this, 'render_tab_servicios'],
            'orden' => 32,
            'modulo' => 'banco_tiempo',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de saldo
     */
    public function render_tab_saldo() {
        $this->encolar_assets();
        $this->render_mi_saldo();
    }

    /**
     * Renderizar tab de intercambios
     */
    public function render_tab_intercambios() {
        $this->encolar_assets();
        $this->render_mis_intercambios(['estado' => '', 'limite' => 20]);
    }

    /**
     * Renderizar tab de servicios
     */
    public function render_tab_servicios() {
        $this->encolar_assets();
        $this->render_mis_servicios(['estado' => '', 'limite' => 20]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        return $template;
    }

    /**
     * AJAX: Solicitar servicio
     */
    public function ajax_solicitar_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;
        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
        $horas = isset($_POST['horas']) ? floatval($_POST['horas']) : 1;
        $usuario_id = get_current_user_id();

        if (!$servicio_id) {
            wp_send_json_error(__('Servicio no válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Obtener servicio
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_servicios WHERE id = %d AND estado = 'activo'",
            $servicio_id
        ));

        if (!$servicio) {
            wp_send_json_error(__('Servicio no disponible', 'flavor-chat-ia'));
        }

        if ($servicio->usuario_id == $usuario_id) {
            wp_send_json_error(__('No puedes solicitar tu propio servicio', 'flavor-chat-ia'));
        }

        // Crear transacción
        $resultado = $wpdb->insert($tabla_transacciones, [
            'servicio_id' => $servicio_id,
            'usuario_solicitante_id' => $usuario_id,
            'usuario_receptor_id' => $servicio->usuario_id,
            'horas' => $horas ?: $servicio->horas_estimadas,
            'mensaje' => $mensaje,
            'estado' => 'pendiente',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'mensaje' => __('Solicitud enviada correctamente', 'flavor-chat-ia'),
                'transaccion_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al enviar solicitud', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Ofrecer servicio
     */
    public function ajax_ofrecer_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'otros';
        $horas_estimadas = isset($_POST['horas_estimadas']) ? floatval($_POST['horas_estimadas']) : 1;
        $usuario_id = get_current_user_id();

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(__('Título y descripción son obligatorios', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $resultado = $wpdb->insert($tabla_servicios, [
            'usuario_id' => $usuario_id,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'horas_estimadas' => $horas_estimadas,
            'estado' => 'activo',
            'fecha_publicacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success([
                'mensaje' => __('Servicio publicado correctamente', 'flavor-chat-ia'),
                'servicio_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al publicar servicio', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Aceptar solicitud
     */
    public function ajax_aceptar_solicitud() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $transaccion_id = isset($_POST['transaccion_id']) ? absint($_POST['transaccion_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $resultado = $wpdb->update(
            $tabla_transacciones,
            [
                'estado' => 'aceptado',
                'fecha_aceptacion' => current_time('mysql'),
            ],
            [
                'id' => $transaccion_id,
                'usuario_receptor_id' => $usuario_id,
                'estado' => 'pendiente',
            ]
        );

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Solicitud aceptada', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al aceptar solicitud', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Rechazar solicitud
     */
    public function ajax_rechazar_solicitud() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $transaccion_id = isset($_POST['transaccion_id']) ? absint($_POST['transaccion_id']) : 0;
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $resultado = $wpdb->update(
            $tabla_transacciones,
            [
                'estado' => 'rechazado',
                'motivo_cancelacion' => $motivo,
                'fecha_cancelacion' => current_time('mysql'),
            ],
            [
                'id' => $transaccion_id,
                'usuario_receptor_id' => $usuario_id,
            ]
        );

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Solicitud rechazada', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al rechazar solicitud', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Completar intercambio
     */
    public function ajax_completar_intercambio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $transaccion_id = isset($_POST['transaccion_id']) ? absint($_POST['transaccion_id']) : 0;
        $horas_reales = isset($_POST['horas_reales']) ? floatval($_POST['horas_reales']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Verificar que el usuario participa en la transacción
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d AND (usuario_solicitante_id = %d OR usuario_receptor_id = %d)",
            $transaccion_id,
            $usuario_id,
            $usuario_id
        ));

        if (!$transaccion) {
            wp_send_json_error(__('Transacción no encontrada', 'flavor-chat-ia'));
        }

        $update_data = [
            'estado' => 'completado',
            'fecha_completado' => current_time('mysql'),
        ];

        if ($horas_reales > 0) {
            $update_data['horas'] = $horas_reales;
        }

        $resultado = $wpdb->update($tabla_transacciones, $update_data, ['id' => $transaccion_id]);

        if ($resultado !== false) {
            // Disparar acción para actualizar saldos
            do_action('flavor_banco_tiempo_servicio_completado', $transaccion_id, $transaccion->horas);

            wp_send_json_success(['mensaje' => __('Intercambio completado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al completar intercambio', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Valorar
     */
    public function ajax_valorar() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $transaccion_id = isset($_POST['transaccion_id']) ? absint($_POST['transaccion_id']) : 0;
        $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
        $usuario_id = get_current_user_id();

        if (!$transaccion_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(__('Datos no válidos', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_valoraciones = $wpdb->prefix . 'flavor_banco_tiempo_valoraciones';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        // Determinar rol del valorador
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d",
            $transaccion_id
        ));

        if (!$transaccion) {
            wp_send_json_error(__('Transacción no encontrada', 'flavor-chat-ia'));
        }

        $rol = $transaccion->usuario_solicitante_id == $usuario_id ? 'solicitante' : 'receptor';
        $valorado_id = $rol == 'solicitante' ? $transaccion->usuario_receptor_id : $transaccion->usuario_solicitante_id;

        $resultado = $wpdb->insert($tabla_valoraciones, [
            'transaccion_id' => $transaccion_id,
            'valorador_id' => $usuario_id,
            'valorado_id' => $valorado_id,
            'rol_valorador' => $rol,
            'rating_general' => $valoracion,
            'comentario' => $comentario,
            'fecha_valoracion' => current_time('mysql'),
        ]);

        if ($resultado) {
            wp_send_json_success(['mensaje' => __('Gracias por tu valoración', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al guardar valoración', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Filtrar servicios
     */
    public function ajax_filtrar() {
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($categoria)) {
            $where[] = "categoria = %s";
            $params[] = $categoria;
        }

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM $tabla_servicios WHERE " . implode(' AND ', $where) . " ORDER BY fecha_publicacion DESC LIMIT 50";

        if (!empty($params)) {
            $servicios = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $servicios = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($servicios)) {
            foreach ($servicios as $servicio) {
                $this->render_servicio_card($servicio);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron servicios', 'flavor-chat-ia') . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($servicios)]);
    }

    /**
     * Renderizar tarjeta de servicio
     */
    private function render_servicio_card($servicio) {
        $usuario = get_userdata($servicio->usuario_id);
        ?>
        <div class="flavor-bt-servicio-card" data-id="<?php echo esc_attr($servicio->id); ?>">
            <div class="servicio-header">
                <span class="categoria"><?php echo esc_html(ucfirst($servicio->categoria)); ?></span>
                <span class="horas"><?php echo esc_html(number_format($servicio->horas_estimadas, 1)); ?>h</span>
            </div>
            <h3><?php echo esc_html($servicio->titulo); ?></h3>
            <p class="servicio-descripcion"><?php echo esc_html(wp_trim_words($servicio->descripcion, 20)); ?></p>
            <div class="servicio-footer">
                <span class="usuario">
                    <?php echo get_avatar($servicio->usuario_id, 24); ?>
                    <?php echo esc_html($usuario ? $usuario->display_name : __('Usuario', 'flavor-chat-ia')); ?>
                </span>
                <a href="<?php echo esc_url(home_url('/banco-tiempo/?servicio_id=' . $servicio->id)); ?>" class="flavor-btn flavor-btn-sm">
                    <?php _e('Ver', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
