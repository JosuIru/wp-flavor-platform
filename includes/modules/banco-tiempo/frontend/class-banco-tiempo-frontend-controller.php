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

        // Registrar shortcodes. Si el controlador se instancia despues de `init`,
        // registrarlos inmediatamente para no caer al fallback generico del portal.
        if (did_action('init')) {
            $this->registrar_shortcodes();
        } else {
            add_action('init', [$this, 'registrar_shortcodes']);
        }

        // AJAX handlers
        add_action('wp_ajax_banco_tiempo_solicitar', [$this, 'ajax_solicitar_servicio']);
        add_action('wp_ajax_banco_tiempo_ofrecer', [$this, 'ajax_ofrecer_servicio']);
        add_action('wp_ajax_banco_tiempo_aceptar', [$this, 'ajax_aceptar_solicitud']);
        add_action('wp_ajax_banco_tiempo_rechazar', [$this, 'ajax_rechazar_solicitud']);
        add_action('wp_ajax_banco_tiempo_completar', [$this, 'ajax_completar_intercambio']);
        add_action('wp_ajax_banco_tiempo_aceptar_intercambio', [$this, 'ajax_aceptar_solicitud']);
        add_action('wp_ajax_banco_tiempo_rechazar_intercambio', [$this, 'ajax_rechazar_solicitud']);
        add_action('wp_ajax_banco_tiempo_cancelar_intercambio', [$this, 'ajax_cancelar_intercambio']);
        add_action('wp_ajax_banco_tiempo_valorar', [$this, 'ajax_valorar']);
        add_action('wp_ajax_banco_tiempo_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_banco_tiempo_obtener_historial', [$this, 'ajax_obtener_historial']);
        add_action('wp_ajax_banco_tiempo_obtener_mis_servicios', [$this, 'ajax_obtener_mis_servicios']);
        add_action('wp_ajax_banco_tiempo_obtener_servicio', [$this, 'ajax_obtener_servicio']);
        add_action('wp_ajax_banco_tiempo_actualizar_servicio', [$this, 'ajax_actualizar_servicio']);
        add_action('wp_ajax_banco_tiempo_pausar_servicio', [$this, 'ajax_pausar_servicio']);
        add_action('wp_ajax_banco_tiempo_activar_servicio', [$this, 'ajax_activar_servicio']);
        add_action('wp_ajax_banco_tiempo_eliminar_servicio', [$this, 'ajax_eliminar_servicio']);
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
        // Usar constante global del plugin para ruta confiable
        $base_url = FLAVOR_CHAT_IA_URL . 'includes/modules/banco-tiempo';
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
            'categorias' => [
                'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'i18n' => [
                'solicitud_enviada' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'servicio_publicado' => __('Servicio publicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'intercambio_completado' => __('Intercambio completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmacion' => __('¿Estás seguro?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'gracias_valoracion' => __('Gracias por tu valoración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_conexion' => __('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_servicios' => __('No hay servicios disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados' => __('No se encontraron servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ofrecer_servicio' => __('Ofrecer servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ver_mas' => __('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'solicitar_servicio' => __('Solicitar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'mensaje_proveedor' => __('Mensaje para el proveedor', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha_preferida' => __('Fecha preferida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'explica_necesidad' => __('Explica brevemente por qué necesitas este servicio...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cancelar' => __('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'enviar_solicitud' => __('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'escribe_mensaje' => __('Por favor escribe un mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria' => __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'horas_estimadas' => __('Horas estimadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'publicado' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cerrar' => __('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ofrecer_nuevo_servicio' => __('Ofrecer nuevo servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'publicar_servicio' => __('Publicar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'selecciona_categoria' => __('Selecciona categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_publicar_servicio' => __('Error al publicar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_enviar_solicitud' => __('Error al enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_intercambios' => __('No hay intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'de' => __('De: ', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'para' => __('Para: ', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'intercambio_aceptado' => __('Intercambio aceptado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_aceptar' => __('Error al aceptar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_rechazar_intercambio' => __('¿Estás seguro de rechazar este intercambio?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'intercambio_rechazado' => __('Intercambio rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error_generico' => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'completar_intercambio' => __('Completar intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'horas_reales_servicio' => __('Horas reales del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'valoracion' => __('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'comentario' => __('Comentario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'comparte_experiencia' => __('Comparte tu experiencia...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar' => __('Confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_cancelar_intercambio' => __('¿Estás seguro de cancelar este intercambio?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'intercambio_cancelado' => __('Intercambio cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_servicios_publicados' => __('No tienes servicios publicados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'solicitudes' => __('solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'editar' => __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'pausar' => __('Pausar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'activar' => __('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminar' => __('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        $shortcodes = [
            'banco_tiempo_servicios' => 'shortcode_servicios',
            'banco_tiempo_mi_saldo' => 'shortcode_mi_saldo',
            'banco_tiempo_mis_servicios' => 'shortcode_mis_servicios',
            'banco_tiempo_mis_intercambios' => 'shortcode_mis_intercambios',
            'banco_tiempo_widget_saldo' => 'shortcode_widget_saldo',
            'banco_tiempo_widget_intercambios' => 'shortcode_widget_intercambios',
            'banco_tiempo_ofrecer' => 'shortcode_ofrecer',
            'banco_tiempo_detalle' => 'shortcode_detalle',
            'banco_tiempo_ranking' => 'shortcode_ranking',
            'banco_tiempo_ultimos_intercambios' => 'shortcode_ultimos_intercambios',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }
    }

    /**
     * Normaliza una categoría de servicio legacy a su clave canónica.
     */
    private function normalize_service_category($categoria) {
        $categoria = sanitize_key(remove_accents((string) $categoria));
        $map = [
            'cuidados' => 'cuidados',
            'educacion' => 'educacion',
            'bricolaje' => 'bricolaje',
            'tecnologia' => 'tecnologia',
            'transporte' => 'transporte',
            'otros' => 'otros',
        ];

        return $map[$categoria] ?? 'otros';
    }

    /**
     * Normaliza un estado legacy del servicio.
     */
    private function normalize_service_status($estado) {
        $estado = sanitize_key(remove_accents((string) $estado));
        $map = [
            'activo' => 'activo',
            'active' => 'activo',
            'pausado' => 'pausado',
            'paused' => 'pausado',
            'inactivo' => 'pausado',
            'completado' => 'completado',
            'completed' => 'completado',
            'cancelado' => 'cancelado',
            'cancelled' => 'cancelado',
        ];

        return $map[$estado] ?? 'activo';
    }

    /**
     * Shortcode compacto para widget de saldo
     */
    public function shortcode_widget_saldo($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tu saldo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)) {
            return '<p class="flavor-empty">' . __('El historial de intercambios aún no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $usuario_id = get_current_user_id();

        $horas_dadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM {$tabla_transacciones}
             WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $horas_recibidas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM {$tabla_transacciones}
             WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        $pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_transacciones}
             WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
             AND estado IN ('pendiente', 'aceptado', 'en_curso')",
            $usuario_id,
            $usuario_id
        ));

        $saldo = $horas_dadas - $horas_recibidas;
        $saldo_clase = $saldo >= 0 ? 'positivo' : 'negativo';

        ob_start();
        ?>
        <div class="bt-widget-resumen bt-widget-resumen-saldo">
            <div class="bt-widget-kpi">
                <span class="bt-widget-kpi__label"><?php esc_html_e('Saldo actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <strong class="bt-widget-kpi__value bt-widget-kpi__value--<?php echo esc_attr($saldo_clase); ?>">
                    <?php echo esc_html(($saldo >= 0 ? '+' : '') . number_format($saldo, 1)); ?>h
                </strong>
            </div>
            <div class="bt-widget-meta">
                <span><?php echo esc_html(number_format($horas_dadas, 1)); ?>h <?php esc_html_e('dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span><?php echo esc_html(number_format($horas_recibidas, 1)); ?>h <?php esc_html_e('recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span><?php echo esc_html(number_format_i18n($pendientes)); ?> <?php esc_html_e('pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode compacto para widget de intercambios
     */
    public function shortcode_widget_intercambios($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus intercambios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_transacciones)) {
            return '<p class="flavor-empty">' . __('Aún no hay intercambios disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $atts = shortcode_atts([
            'limite' => 3,
        ], $atts);

        $usuario_id = get_current_user_id();
        $intercambios = $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.horas, t.estado, t.fecha_solicitud, s.titulo AS servicio_titulo
             FROM {$tabla_transacciones} t
             LEFT JOIN {$tabla_servicios} s ON t.servicio_id = s.id
             WHERE t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d
             ORDER BY t.fecha_solicitud DESC
             LIMIT %d",
            $usuario_id,
            $usuario_id,
            intval($atts['limite'])
        ));

        if (empty($intercambios)) {
            return '<p class="flavor-empty">' . __('No tienes intercambios registrados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="bt-widget-resumen bt-widget-resumen-intercambios">
            <?php foreach ($intercambios as $intercambio): ?>
                <div class="bt-widget-item">
                    <div class="bt-widget-item__main">
                        <strong><?php echo esc_html($intercambio->servicio_titulo ?: __('Intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                        <span><?php echo esc_html(number_format((float) $intercambio->horas, 1)); ?>h</span>
                    </div>
                    <div class="bt-widget-item__meta">
                        <span><?php echo esc_html(ucfirst(str_replace('_', ' ', $intercambio->estado))); ?></span>
                        <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($intercambio->fecha_solicitud))); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
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
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $intercambios = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, s.titulo, s.categoria,
                    u_rec.display_name as oferente_nombre,
                    u_sol.display_name as solicitante_nombre
             FROM {$tabla_transacciones} t
             LEFT JOIN {$tabla_servicios} s ON t.servicio_id = s.id
             LEFT JOIN {$wpdb->users} u_rec ON t.usuario_receptor_id = u_rec.ID
             LEFT JOIN {$wpdb->users} u_sol ON t.usuario_solicitante_id = u_sol.ID
             WHERE t.estado = 'completado'
             ORDER BY t.fecha_completado DESC
             LIMIT %d",
            absint($atts['limite'])
        ));

        if (empty($intercambios)) {
            return '<p class="flavor-empty">' . __('No hay intercambios recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
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
            'mostrar_propios' => 'false',
            'comunidad_id' => 0,
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'fecha',
            'order' => 'DESC',
        ], $atts);

        // Generar clases CSS visuales (VBP)
        $visual_classes = [];
        if (!empty($atts['esquema_color']) && $atts['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atts['esquema_color']);
        }
        if (!empty($atts['estilo_tarjeta']) && $atts['estilo_tarjeta'] !== 'elevated') {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atts['estilo_tarjeta']);
        }
        if (!empty($atts['radio_bordes']) && $atts['radio_bordes'] !== 'lg') {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atts['radio_bordes']);
        }
        if (!empty($atts['animacion_entrada']) && $atts['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atts['animacion_entrada']);
        }
        $atts['visual_class_string'] = implode(' ', $visual_classes);

        $atts['modo'] = 'catalogo';

        ob_start();
        $this->render_servicios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi saldo
     */
    public function shortcode_mi_saldo($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tu saldo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus servicios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => '',
            'limite' => 20,
            'comunidad_id' => 0,
        ], $atts);

        $atts['mostrar_propios'] = 'true';
        $atts['modo'] = 'mis-servicios';

        ob_start();
        $this->render_servicios($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis intercambios
     */
    public function shortcode_mis_intercambios($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus intercambios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
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
            return '<p class="flavor-login-required">' . __('Inicia sesión para ofrecer un servicio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'comunidad_id' => 0,
        ], $atts);

        ob_start();
        $this->render_formulario_ofrecer($atts);
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
            return '<p class="flavor-error">' . __('Servicio no especificado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
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
            echo '<p class="flavor-error">' . __('El módulo de banco de tiempo no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $modo = sanitize_key($atts['modo'] ?? 'catalogo');
        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        $comunidad_id = absint($atts['comunidad_id'] ?? ($_GET['comunidad_id'] ?? 0));
        if ($comunidad_id > 0) {
            $where[] = "comunidad_id = %d";
            $params[] = $comunidad_id;
        }

        $atts['mostrar_propios'] = filter_var($atts['mostrar_propios'], FILTER_VALIDATE_BOOLEAN);

        if ($modo === 'mis-servicios') {
            $this->render_mis_servicios($atts);
            return;
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
                <h3><?php _e('Mi Saldo de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="saldo-grid">
                    <div class="saldo-item saldo-dado">
                        <span class="valor"><?php echo esc_html(number_format($horas_dadas, 1)); ?>h</span>
                        <span class="label"><?php _e('Horas Dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="saldo-item saldo-recibido">
                        <span class="valor"><?php echo esc_html(number_format($horas_recibidas, 1)); ?>h</span>
                        <span class="label"><?php _e('Horas Recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="saldo-item saldo-total <?php echo $saldo >= 0 ? 'positivo' : 'negativo'; ?>">
                        <span class="valor"><?php echo esc_html(($saldo >= 0 ? '+' : '') . number_format($saldo, 1)); ?>h</span>
                        <span class="label"><?php _e('Saldo Actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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
        $categorias = [
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_servicios WHERE usuario_id = %d ORDER BY fecha_publicacion DESC LIMIT %d",
            $usuario_id,
            intval($atts['limite'])
        ));

        $template = dirname(__FILE__) . '/../templates/mis-servicios.php';
        if (file_exists($template)) {
            include $template;
            return;
        }

        if (empty($servicios)) {
            echo '<div class="fl-empty-state"><span class="dashicons dashicons-admin-tools"></span><p>' .
                esc_html__('No tienes servicios publicados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                '</p></div>';
            return;
        }

        echo '<div class="fl-services-grid fl-services-mine">';
        foreach ($servicios as $servicio) {
            $categoria = $this->normalize_service_category($servicio->categoria ?? '');
            $estado_key = $this->normalize_service_status($servicio->estado ?? '');
            $estado = $estado_key === 'activo'
                ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Pausado', FLAVOR_PLATFORM_TEXT_DOMAIN);

            echo '<div class="fl-service-card fl-service-mine ' . ($estado_key === 'pausado' ? 'fl-service-paused' : '') . '">';
            echo '<div class="fl-service-header">';
            echo '<span class="fl-service-category">' . esc_html($categorias[$categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</span>';
            echo '<span class="fl-service-status fl-status-' . esc_attr($estado_key) . '">' . esc_html($estado) . '</span>';
            echo '</div>';
            echo '<h4 class="fl-service-title">' . esc_html($servicio->titulo) . '</h4>';
            echo '<p class="fl-service-desc">' . esc_html(wp_trim_words($servicio->descripcion, 18)) . '</p>';
            echo '<div class="fl-service-footer">';
            echo '<span class="fl-service-hours"><span class="dashicons dashicons-clock"></span>' . esc_html(number_format((float) $servicio->horas_estimadas, 1)) . 'h</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
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
    private function render_formulario_ofrecer($atts = []) {
        $comunidad_id = absint($atts['comunidad_id'] ?? ($_GET['comunidad_id'] ?? 0));
        $template = dirname(__FILE__) . '/../templates/ofrecer-servicio.php';
        if (file_exists($template)) {
            include $template;
        } else {
            ?>
            <div class="flavor-bt-ofrecer">
                <h3><?php _e('Ofrecer un Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <form id="form-ofrecer-servicio" class="flavor-form">
                    <?php wp_nonce_field('banco_tiempo_nonce', 'bt_nonce_field'); ?>
                    <?php if ($comunidad_id > 0): ?>
                        <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad_id); ?>">
                    <?php endif; ?>
                    <p>
                        <label><?php _e('Título del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="titulo" required>
                    </p>
                    <p>
                        <label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea name="descripcion" rows="4" required></textarea>
                    </p>
                    <p>
                        <label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="categoria" required>
                            <option value="cuidados"><?php _e('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="educacion"><?php _e('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="bricolaje"><?php _e('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="tecnologia"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="transporte"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="otros"><?php _e('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><?php _e('Horas estimadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" name="horas_estimadas" min="0.5" max="8" step="0.5" value="1" required>
                    </p>
                    <p>
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php _e('Publicar Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
            echo '<p class="flavor-error">' . __('Servicio no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $usuario = get_userdata($servicio->usuario_id);
        $categorias = [
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        $categoria = $this->normalize_service_category($servicio->categoria ?? '');

        $template = dirname(__FILE__) . '/single.php';
        if (file_exists($template)) {
            include $template;
            return;
        }

        echo '<div class="flavor-bt-detalle">';
        echo '<h3>' . esc_html($servicio->titulo) . '</h3>';
        echo '<p>' . esc_html($servicio->descripcion) . '</p>';
        echo '<div class="flavor-bt-detalle__meta">';
        echo '<span><strong>' . esc_html__('Categoría:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . esc_html($categorias[$categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</span>';
        echo '<span><strong>' . esc_html__('Horas estimadas:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . esc_html(number_format((float) $servicio->horas_estimadas, 1)) . 'h</span>';
        if ($usuario) {
            echo '<span><strong>' . esc_html__('Ofrecido por:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong> ' . esc_html($usuario->display_name) . '</span>';
        }
        echo '</div>';
        echo '</div>';
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
            'titulo' => __('Mi Saldo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-clock',
            'callback' => [$this, 'render_tab_saldo'],
            'orden' => 30,
            'modulo' => 'banco_tiempo',
        ];

        $tabs['banco-tiempo-intercambios'] = [
            'titulo' => __('Mis Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-randomize',
            'callback' => [$this, 'render_tab_intercambios'],
            'orden' => 31,
            'modulo' => 'banco_tiempo',
        ];

        $tabs['banco-tiempo-servicios'] = [
            'titulo' => __('Mis Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;
        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
        $horas = isset($_POST['horas']) ? floatval($_POST['horas']) : 1;
        $usuario_id = get_current_user_id();

        if (!$servicio_id) {
            wp_send_json_error(__('Servicio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_error(__('Servicio no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if ($servicio->usuario_id == $usuario_id) {
            wp_send_json_error(__('No puedes solicitar tu propio servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
                'mensaje' => __('Solicitud enviada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'transaccion_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Ofrecer servicio
     */
    public function ajax_ofrecer_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'otros';
        $horas_estimadas = isset($_POST['horas_estimadas']) ? floatval($_POST['horas_estimadas']) : 1;
        $comunidad_id = isset($_POST['comunidad_id']) ? absint($_POST['comunidad_id']) : 0;
        $usuario_id = get_current_user_id();

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(__('Título y descripción son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $resultado = $wpdb->insert($tabla_servicios, [
            'usuario_id' => $usuario_id,
            'comunidad_id' => $comunidad_id ?: null,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'horas_estimadas' => $horas_estimadas,
            'estado' => 'activo',
            'fecha_publicacion' => current_time('mysql'),
        ], [
            '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s'
        ]);

        if ($resultado) {
            wp_send_json_success([
                'mensaje' => __('Servicio publicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'servicio_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al publicar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Aceptar solicitud
     */
    public function ajax_aceptar_solicitud() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_success(['mensaje' => __('Solicitud aceptada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al aceptar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Rechazar solicitud
     */
    public function ajax_rechazar_solicitud() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_success(['mensaje' => __('Solicitud rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al rechazar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Completar intercambio
     */
    public function ajax_completar_intercambio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_error(__('Transacción no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
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

            wp_send_json_success(['mensaje' => __('Intercambio completado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al completar intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Cancelar intercambio
     */
    public function ajax_cancelar_intercambio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $transaccion_id = isset($_POST['intercambio_id']) ? absint($_POST['intercambio_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$transaccion_id) {
            wp_send_json_error(__('Transacción no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        $resultado = $wpdb->update(
            $tabla_transacciones,
            [
                'estado' => 'cancelado',
                'fecha_cancelacion' => current_time('mysql'),
            ],
            [
                'id' => $transaccion_id,
                'usuario_solicitante_id' => $usuario_id,
                'estado' => 'pendiente',
            ]
        );

        if ($resultado === false) {
            wp_send_json_error(__('Error al cancelar intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        wp_send_json_success(['mensaje' => __('Intercambio cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Valorar
     */
    public function ajax_valorar() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $transaccion_id = isset($_POST['transaccion_id']) ? absint($_POST['transaccion_id']) : 0;
        $valoracion = isset($_POST['valoracion']) ? intval($_POST['valoracion']) : 0;
        $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
        $usuario_id = get_current_user_id();

        if (!$transaccion_id || $valoracion < 1 || $valoracion > 5) {
            wp_send_json_error(__('Datos no válidos', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_error(__('Transacción no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            wp_send_json_success(['mensaje' => __('Gracias por tu valoración', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al guardar valoración', FLAVOR_PLATFORM_TEXT_DOMAIN));
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
            echo '<p class="no-resultados">' . __('No se encontraron servicios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($servicios)]);
    }

    /**
     * AJAX: Obtener historial de intercambios del usuario
     */
    public function ajax_obtener_historial() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

        $where = [
            '(t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d)',
        ];
        $params = [$usuario_id, $usuario_id];

        if ($filtro === 'activos') {
            $where[] = "t.estado IN ('pendiente', 'aceptado', 'en_curso')";
        } elseif ($filtro === 'completados') {
            $where[] = "t.estado = 'completado'";
        } elseif ($filtro === 'cancelados') {
            $where[] = "t.estado IN ('cancelado', 'rechazado')";
        }

        $sql = "SELECT t.*, s.titulo as servicio_titulo,
                       CASE
                           WHEN t.usuario_receptor_id = %d THEN 'entrante'
                           ELSE 'saliente'
                       END as direccion,
                       CASE
                           WHEN t.usuario_receptor_id = %d THEN u_sol.display_name
                           ELSE u_rec.display_name
                       END as otro_usuario,
                       COALESCE(t.fecha_completado, t.fecha_cancelacion, t.fecha_aceptacion, t.fecha_solicitud) as fecha
                FROM {$tabla_transacciones} t
                LEFT JOIN {$tabla_servicios} s ON t.servicio_id = s.id
                LEFT JOIN {$wpdb->users} u_sol ON t.usuario_solicitante_id = u_sol.ID
                LEFT JOIN {$wpdb->users} u_rec ON t.usuario_receptor_id = u_rec.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY fecha DESC
                LIMIT 50";

        array_unshift($params, $usuario_id, $usuario_id);
        $intercambios = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        $data = array_map(function($intercambio) {
            $estados = [
                'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'aceptado' => __('Aceptado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'en_curso' => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'completado' => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cancelado' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'rechazado' => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];

            return [
                'id' => (int) $intercambio->id,
                'servicio_titulo' => $intercambio->servicio_titulo ?: __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'otro_usuario' => $intercambio->otro_usuario ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'direccion' => $intercambio->direccion,
                'horas' => (float) $intercambio->horas,
                'estado' => $intercambio->estado,
                'estado_texto' => $estados[$intercambio->estado] ?? __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => $intercambio->fecha,
            ];
        }, $intercambios ?: []);

        wp_send_json_success(['intercambios' => $data]);
    }

    /**
     * AJAX: Obtener servicios del usuario actual
     */
    public function ajax_obtener_mis_servicios() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $usuario_id = get_current_user_id();

        $servicios = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,
                    (
                        SELECT COUNT(*)
                        FROM {$tabla_transacciones} t
                        WHERE t.servicio_id = s.id
                    ) as solicitudes_count
             FROM {$tabla_servicios} s
             WHERE s.usuario_id = %d
             ORDER BY s.fecha_publicacion DESC
             LIMIT 50",
            $usuario_id
        ));

        $categorias = [
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $data = array_map(function($servicio) use ($categorias) {
            return [
                'id' => (int) $servicio->id,
                'titulo' => $servicio->titulo,
                'descripcion' => $servicio->descripcion,
                'categoria' => $servicio->categoria,
                'categoria_nombre' => $categorias[$servicio->categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'horas_estimadas' => (float) $servicio->horas_estimadas,
                'estado' => $servicio->estado,
                'estado_texto' => $servicio->estado === 'activo' ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Pausado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'solicitudes_count' => (int) $servicio->solicitudes_count,
            ];
        }, $servicios ?: []);

        wp_send_json_success(['servicios' => $data]);
    }

    /**
     * AJAX: Obtener un servicio para frontend
     */
    public function ajax_obtener_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;

        if (!$servicio_id) {
            wp_send_json_error(__('Servicio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $servicio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_servicios} WHERE id = %d",
            $servicio_id
        ));

        if (!$servicio) {
            wp_send_json_error(__('Servicio no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $usuario_id = get_current_user_id();
        if ($servicio->estado !== 'activo' && (int) $servicio->usuario_id !== (int) $usuario_id) {
            wp_send_json_error(__('No tienes acceso a este servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $categorias = [
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        wp_send_json_success([
            'id' => (int) $servicio->id,
            'titulo' => $servicio->titulo,
            'descripcion' => $servicio->descripcion,
            'categoria' => $servicio->categoria,
            'categoria_nombre' => $categorias[$servicio->categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'horas_estimadas' => (float) $servicio->horas_estimadas,
            'estado' => $servicio->estado,
            'fecha_publicacion' => $servicio->fecha_publicacion,
        ]);
    }

    /**
     * AJAX: Actualizar servicio propio
     */
    public function ajax_actualizar_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'otros';
        $horas_estimadas = isset($_POST['horas_estimadas']) ? floatval($_POST['horas_estimadas']) : 1;

        if (!$servicio_id || $titulo === '' || $descripcion === '') {
            wp_send_json_error(__('Datos no válidos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $usuario_id = get_current_user_id();

        $resultado = $wpdb->update(
            $tabla_servicios,
            [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'horas_estimadas' => $horas_estimadas,
            ],
            [
                'id' => $servicio_id,
                'usuario_id' => $usuario_id,
            ]
        );

        if ($resultado === false) {
            wp_send_json_error(__('Error al actualizar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        wp_send_json_success(['mensaje' => __('Servicio actualizado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Pausar servicio propio
     */
    public function ajax_pausar_servicio() {
        $this->ajax_cambiar_estado_servicio('inactivo', __('Servicio pausado', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * AJAX: Activar servicio propio
     */
    public function ajax_activar_servicio() {
        $this->ajax_cambiar_estado_servicio('activo', __('Servicio activado', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    /**
     * AJAX: Eliminar servicio propio
     */
    public function ajax_eliminar_servicio() {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;

        if (!$servicio_id) {
            wp_send_json_error(__('Servicio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $usuario_id = get_current_user_id();

        $resultado = $wpdb->delete($tabla_servicios, [
            'id' => $servicio_id,
            'usuario_id' => $usuario_id,
        ]);

        if ($resultado === false) {
            wp_send_json_error(__('Error al eliminar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        wp_send_json_success(['mensaje' => __('Servicio eliminado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * Cambia el estado de un servicio propio
     */
    private function ajax_cambiar_estado_servicio($estado, $mensaje_ok) {
        check_ajax_referer('banco_tiempo_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $servicio_id = isset($_POST['servicio_id']) ? absint($_POST['servicio_id']) : 0;

        if (!$servicio_id) {
            wp_send_json_error(__('Servicio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        global $wpdb;
        $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $usuario_id = get_current_user_id();

        $resultado = $wpdb->update(
            $tabla_servicios,
            ['estado' => $estado],
            [
                'id' => $servicio_id,
                'usuario_id' => $usuario_id,
            ]
        );

        if ($resultado === false) {
            wp_send_json_error(__('Error al actualizar servicio', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        wp_send_json_success(['mensaje' => $mensaje_ok]);
    }

    /**
     * Renderizar tarjeta de servicio
     */
    private function render_servicio_card($servicio) {
        $usuario = get_userdata($servicio->usuario_id);
        $categoria = $this->normalize_service_category($servicio->categoria ?? '');
        ?>
        <div class="flavor-bt-servicio-card" data-id="<?php echo esc_attr($servicio->id); ?>">
            <div class="servicio-header">
                <span class="categoria"><?php echo esc_html([
                    'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'educacion' => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'tecnologia' => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ][$categoria] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                <span class="horas"><?php echo esc_html(number_format($servicio->horas_estimadas, 1)); ?>h</span>
            </div>
            <h3><?php echo esc_html($servicio->titulo); ?></h3>
            <p class="servicio-descripcion"><?php echo esc_html(wp_trim_words($servicio->descripcion, 20)); ?></p>
            <div class="servicio-footer">
                <span class="usuario">
                    <?php echo get_avatar($servicio->usuario_id, 24); ?>
                    <?php echo esc_html($usuario ? $usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                </span>
                <a href="<?php echo esc_url(add_query_arg('servicio_id', $servicio->id, home_url('/mi-portal/banco-tiempo/servicios/'))); ?>" class="flavor-btn flavor-btn-sm">
                    <?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
