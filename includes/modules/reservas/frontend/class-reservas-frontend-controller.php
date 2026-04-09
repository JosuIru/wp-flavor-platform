<?php
/**
 * Frontend Controller para Reservas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Reservas
 * Gestiona shortcodes, assets y dashboard tabs del frontend
 */
class Flavor_Reservas_Frontend_Controller {

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
        add_action('wp_ajax_reservas_crear', [$this, 'ajax_crear_reserva']);
        add_action('wp_ajax_reservas_cancelar', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_reservas_disponibilidad', [$this, 'ajax_verificar_disponibilidad']);
        add_action('wp_ajax_nopriv_reservas_disponibilidad', [$this, 'ajax_verificar_disponibilidad']);
        add_action('wp_ajax_reservas_calendario', [$this, 'ajax_calendario']);
        add_action('wp_ajax_nopriv_reservas_calendario', [$this, 'ajax_calendario']);
        add_action('wp_ajax_reservas_filtrar', [$this, 'ajax_filtrar']);
        add_action('wp_ajax_nopriv_reservas_filtrar', [$this, 'ajax_filtrar']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_template']);
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(dirname(__FILE__)));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-reservas',
            $base_url . '/assets/css/reservas.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-reservas',
            $base_url . '/assets/js/reservas.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-reservas', 'flavorReservas', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reservas_nonce'),
            'i18n' => [
                'reserva_creada' => __('Reserva creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reserva_cancelada' => __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmacion' => __('¿Estás seguro?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'no_disponible' => __('Horario no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'disponible' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'selecciona_fecha' => __('Selecciona una fecha', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'selecciona_hora' => __('Selecciona una hora', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-reservas');
        wp_enqueue_script('flavor-reservas');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('reservas_recursos')) {
            add_shortcode('reservas_recursos', [$this, 'shortcode_recursos']);
        }
        if (!shortcode_exists('reservas_mis_reservas')) {
            add_shortcode('reservas_mis_reservas', [$this, 'shortcode_mis_reservas']);
        }
        if (!shortcode_exists('reservas_calendario')) {
            add_shortcode('reservas_calendario', [$this, 'shortcode_calendario']);
        }
        if (!shortcode_exists('reservas_formulario')) {
            add_shortcode('reservas_formulario', [$this, 'shortcode_formulario']);
        }
        if (!shortcode_exists('reservas_detalle_recurso')) {
            add_shortcode('reservas_detalle_recurso', [$this, 'shortcode_detalle_recurso']);
        }
    }

    /**
     * Renderiza un estado de login consistente para el portal.
     *
     * @param string $mensaje Mensaje principal.
     * @return string
     */
    private function render_login_required($mensaje) {
        return '<div class="flavor-empty-state">' .
            '<p>' . esc_html($mensaje) . '</p>' .
            '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' .
            esc_html__('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            '</a></div>';
    }

    /**
     * Shortcode: Listado de recursos reservables
     */
    public function shortcode_recursos($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'tipo' => '',
            'categoria' => '',
            'limite' => 12,
            'columnas' => 3,
            'mostrar_filtros' => 'true',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'nombre',
            'order' => 'ASC',
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

        ob_start();
        $this->render_recursos($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas
     */
    public function shortcode_mis_reservas($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_required(__('Inicia sesión para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'estado' => isset($_GET['estado']) ? sanitize_text_field(wp_unslash($_GET['estado'])) : '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->render_mis_reservas($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de disponibilidad
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'recurso_id' => 0,
            'mes' => date('m'),
            'anio' => date('Y'),
        ], $atts);

        ob_start();
        $this->render_calendario($atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de reserva
     */
    public function shortcode_formulario($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_required(__('Inicia sesión para hacer una reserva.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $this->encolar_assets();

        $atts = shortcode_atts([
            'recurso_id' => 0,
        ], $atts);

        $recurso_id = $atts['recurso_id'] ?: (isset($_GET['recurso_id']) ? absint($_GET['recurso_id']) : 0);

        ob_start();
        $this->render_formulario_reserva($recurso_id);
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de recurso
     */
    public function shortcode_detalle_recurso($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $recurso_id = $atts['id'] ?: (isset($_GET['recurso_id']) ? absint($_GET['recurso_id']) : 0);

        if (!$recurso_id) {
            return '<div class="flavor-empty-state">' .
                '<p>' . esc_html__('Recurso no especificado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>' .
                '<a href="' . esc_url(home_url('/mi-portal/reservas/')) . '" class="flavor-btn flavor-btn-primary">' .
                esc_html__('Ver recursos', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                '</a></div>';
        }

        ob_start();
        $this->render_detalle_recurso($recurso_id);
        return ob_get_clean();
    }

    /**
     * Renderizar listado de recursos
     */
    private function render_recursos($atts) {
        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_recursos)) {
            echo '<p class="flavor-error">' . __('El módulo de reservas no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($atts['tipo'])) {
            $where[] = "tipo = %s";
            $params[] = $atts['tipo'];
        }

        if (!empty($atts['categoria'])) {
            $where[] = "categoria = %s";
            $params[] = $atts['categoria'];
        }

        $sql = "SELECT * FROM $tabla_recursos WHERE " . implode(' AND ', $where) . " ORDER BY nombre ASC LIMIT %d";
        $params[] = intval($atts['limite']);

        $recursos = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ?>
        <div class="flavor-reservas-recursos">
            <?php if ($atts['mostrar_filtros'] === 'true') : ?>
                <div class="flavor-filtros">
                    <select id="filtro-tipo" class="filtro-reservas">
                        <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="sala"><?php _e('Sala', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="equipo"><?php _e('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="vehiculo"><?php _e('Vehículo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="espacio"><?php _e('Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                    <input type="text" id="filtro-busqueda" placeholder="<?php _e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="filtro-reservas">
                </div>
            <?php endif; ?>

            <div class="recursos-grid grid-<?php echo esc_attr($atts['columnas']); ?>" id="recursos-lista">
                <?php foreach ($recursos as $recurso) : ?>
                    <?php $this->render_recurso_card($recurso); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar mis reservas
     */
    private function render_mis_reservas($atts) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';
        $usuario_id = get_current_user_id();

        $where = ["r.usuario_id = %d"];
        $params = [$usuario_id];

        if (!empty($atts['estado'])) {
            $where[] = "r.estado = %s";
            $params[] = $atts['estado'];
        }

        $sql = "SELECT r.*, rec.nombre as recurso_nombre, rec.tipo as recurso_tipo, rec.imagen
                FROM $tabla_reservas r
                JOIN $tabla_recursos rec ON r.recurso_id = rec.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY r.fecha_inicio DESC
                LIMIT %d";
        $params[] = intval($atts['limite']);

        $reservas = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        ?>
        <div class="flavor-mis-reservas">
            <?php if (empty($reservas)) : ?>
                <div class="flavor-empty-state">
                    <p><?php _e('No tienes reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'recursos', home_url('/mi-portal/reservas/'))); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Ver recursos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="reservas-lista">
                    <?php foreach ($reservas as $reserva) : ?>
                        <div class="reserva-item estado-<?php echo esc_attr($reserva->estado); ?>">
                            <div class="reserva-recurso">
                                <?php if (!empty($reserva->imagen)) : ?>
                                    <img src="<?php echo esc_url($reserva->imagen); ?>" alt="" class="recurso-thumb">
                                <?php endif; ?>
                                <div class="recurso-info">
                                    <h4><?php echo esc_html($reserva->recurso_nombre); ?></h4>
                                    <span class="tipo"><?php echo esc_html(ucfirst($reserva->recurso_tipo)); ?></span>
                                </div>
                            </div>
                            <div class="reserva-fechas">
                                <div class="fecha-inicio">
                                    <span class="label"><?php _e('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="valor"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_inicio))); ?></span>
                                </div>
                                <div class="fecha-fin">
                                    <span class="label"><?php _e('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="valor"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_fin))); ?></span>
                                </div>
                            </div>
                            <div class="reserva-estado">
                                <span class="estado"><?php echo esc_html(ucfirst($reserva->estado)); ?></span>
                                <?php if ($reserva->estado === 'confirmada' && strtotime($reserva->fecha_inicio) > time()) : ?>
                                    <button class="flavor-btn flavor-btn-sm flavor-btn-danger btn-cancelar-reserva"
                                            data-id="<?php echo esc_attr($reserva->id); ?>">
                                        <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar calendario de disponibilidad
     */
    private function render_calendario($atts) {
        $recurso_id = $atts['recurso_id'] ?: (isset($_GET['recurso_id']) ? absint($_GET['recurso_id']) : 0);

        ?>
        <div class="flavor-reservas-calendario" data-recurso="<?php echo esc_attr($recurso_id); ?>">
            <div class="calendario-header">
                <button class="nav-mes" data-dir="-1">&lt;</button>
                <span class="mes-actual"><?php echo esc_html(date_i18n('F Y', mktime(0, 0, 0, $atts['mes'], 1, $atts['anio']))); ?></span>
                <button class="nav-mes" data-dir="1">&gt;</button>
            </div>
            <div class="calendario-body" id="calendario-grid">
                <!-- Se carga via JS -->
            </div>
            <div class="calendario-leyenda">
                <span class="leyenda-item disponible"><?php _e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="leyenda-item ocupado"><?php _e('Ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="leyenda-item parcial"><?php _e('Parcialmente ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar formulario de reserva
     */
    private function render_formulario_reserva($recurso_id) {
        if (!$recurso_id) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('Selecciona un recurso para reservar.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p><a href="' . esc_url(add_query_arg('tab', 'recursos', home_url('/mi-portal/reservas/'))) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Ver recursos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
            return;
        }

        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        $recurso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_recursos WHERE id = %d AND estado = 'activo'",
            $recurso_id
        ));

        if (!$recurso) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('Recurso no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p><a href="' . esc_url(add_query_arg('tab', 'recursos', home_url('/mi-portal/reservas/'))) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Volver a recursos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
            return;
        }

        ?>
        <div class="flavor-reservas-form">
            <h3><?php printf(__('Reservar: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($recurso->nombre)); ?></h3>

            <form id="form-crear-reserva" class="flavor-form">
                <?php wp_nonce_field('reservas_nonce', 'reservas_nonce_field'); ?>
                <input type="hidden" name="recurso_id" value="<?php echo esc_attr($recurso_id); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('Fecha de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="fecha_inicio" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php _e('Hora de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="time" name="hora_inicio" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('Fecha de fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" name="fecha_fin" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php _e('Hora de fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="time" name="hora_fin" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php _e('Motivo de la reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="motivo" rows="3" placeholder="<?php _e('Describe brevemente para qué necesitas este recurso...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div id="verificacion-disponibilidad" class="verificacion-box" style="display:none;">
                    <span class="icono"></span>
                    <span class="mensaje"></span>
                </div>

                <div class="form-actions">
                    <button type="button" class="flavor-btn flavor-btn-secondary" id="btn-verificar-disponibilidad">
                        <?php _e('Verificar Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="flavor-btn flavor-btn-primary" disabled id="btn-confirmar-reserva">
                        <?php _e('Confirmar Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar detalle de recurso
     */
    private function render_detalle_recurso($recurso_id) {
        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        $recurso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_recursos WHERE id = %d",
            $recurso_id
        ));

        if (!$recurso) {
            echo '<div class="flavor-empty-state"><p>' . esc_html__('Recurso no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p><a href="' . esc_url(add_query_arg('tab', 'recursos', home_url('/mi-portal/reservas/'))) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Volver a recursos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
            return;
        }

        ?>
        <div class="flavor-recurso-detalle">
            <div class="recurso-header">
                <?php if (!empty($recurso->imagen)) : ?>
                    <div class="recurso-imagen">
                        <img src="<?php echo esc_url($recurso->imagen); ?>" alt="<?php echo esc_attr($recurso->nombre); ?>">
                    </div>
                <?php endif; ?>
                <div class="recurso-info">
                    <h2><?php echo esc_html($recurso->nombre); ?></h2>
                    <span class="tipo"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                    <?php if (!empty($recurso->ubicacion)) : ?>
                        <p class="ubicacion"><?php echo esc_html($recurso->ubicacion); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="recurso-descripcion">
                <?php echo wp_kses_post($recurso->descripcion); ?>
            </div>

            <?php if (!empty($recurso->capacidad)) : ?>
                <div class="recurso-meta">
                    <span class="capacidad">
                        <strong><?php _e('Capacidad:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <?php echo esc_html($recurso->capacidad); ?> <?php _e('personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="recurso-acciones">
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'nueva-reserva', 'recurso_id' => $recurso->id], home_url('/mi-portal/reservas/'))); ?>" class="flavor-btn flavor-btn-primary">
                    <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <div class="recurso-calendario">
                <h3><?php _e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php $this->render_calendario(['recurso_id' => $recurso_id, 'mes' => date('m'), 'anio' => date('Y')]); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['reservas-mis-reservas'] = [
            'titulo' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-calendar',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden' => 45,
            'modulo' => 'reservas',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de mis reservas
     */
    public function render_tab_mis_reservas() {
        $this->encolar_assets();
        $this->render_mis_reservas(['estado' => '', 'limite' => 20]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_template($template) {
        return $template;
    }

    /**
     * AJAX: Crear reserva
     */
    public function ajax_crear_reserva() {
        check_ajax_referer('reservas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $recurso_id = isset($_POST['recurso_id']) ? absint($_POST['recurso_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : $fecha;
        $hora_inicio = isset($_POST['hora_inicio']) ? sanitize_text_field($_POST['hora_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : $fecha_inicio;
        $hora_fin = isset($_POST['hora_fin']) ? sanitize_text_field($_POST['hora_fin']) : '';
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';
        if ($motivo === '' && isset($_POST['notas'])) {
            $motivo = sanitize_textarea_field($_POST['notas']);
        }
        $usuario_id = get_current_user_id();

        if (!$recurso_id || empty($fecha_inicio) || empty($hora_inicio) || empty($fecha_fin) || empty($hora_fin)) {
            wp_send_json_error(__('Todos los campos son obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $fecha_inicio_completa = $fecha_inicio . ' ' . $hora_inicio . ':00';
        $fecha_fin_completa = $fecha_fin . ' ' . $hora_fin . ':00';

        // Verificar disponibilidad
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        $conflicto = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE recurso_id = %d
             AND estado IN ('confirmada', 'pendiente')
             AND (
                 (fecha_inicio <= %s AND fecha_fin > %s)
                 OR (fecha_inicio < %s AND fecha_fin >= %s)
                 OR (fecha_inicio >= %s AND fecha_fin <= %s)
             )",
            $recurso_id,
            $fecha_inicio_completa,
            $fecha_inicio_completa,
            $fecha_fin_completa,
            $fecha_fin_completa,
            $fecha_inicio_completa,
            $fecha_fin_completa
        ));

        if ($conflicto > 0) {
            wp_send_json_error(__('El horario seleccionado no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Crear reserva
        $resultado = $wpdb->insert($tabla_reservas, [
            'recurso_id' => $recurso_id,
            'usuario_id' => $usuario_id,
            'fecha_inicio' => $fecha_inicio_completa,
            'fecha_fin' => $fecha_fin_completa,
            'motivo' => $motivo,
            'estado' => 'confirmada',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            do_action('flavor_reserva_creada', (int) $wpdb->insert_id, [
                'origen' => 'reservas_frontend',
                'recurso_id' => (int) $recurso_id,
                'usuario_id' => (int) $usuario_id,
                'fecha_inicio' => $fecha_inicio_completa,
                'fecha_fin' => $fecha_fin_completa,
                'motivo' => $motivo,
                'estado' => 'confirmada',
                'importe' => isset($_POST['importe']) ? (float) $_POST['importe'] : 0.0,
            ]);
            wp_send_json_success([
                'mensaje' => __('Reserva creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reserva_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(__('Error al crear la reserva', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('reservas_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $reserva_id = isset($_POST['reserva_id']) ? absint($_POST['reserva_id']) : 0;
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        $resultado = $wpdb->update(
            $tabla_reservas,
            ['estado' => 'cancelada', 'fecha_cancelacion' => current_time('mysql')],
            ['id' => $reserva_id, 'usuario_id' => $usuario_id]
        );

        if ($resultado !== false) {
            do_action('flavor_reserva_cancelada', (int) $reserva_id, [
                'origen' => 'reservas_frontend',
                'usuario_id' => (int) $usuario_id,
                'devolucion' => isset($_POST['devolucion']) ? (float) $_POST['devolucion'] : 0.0,
            ]);
            wp_send_json_success(['mensaje' => __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(__('Error al cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
    }

    /**
     * AJAX: Verificar disponibilidad
     */
    public function ajax_verificar_disponibilidad() {
        $recurso_id = isset($_POST['recurso_id']) ? absint($_POST['recurso_id']) : 0;
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : '';
        $hora_inicio = isset($_POST['hora_inicio']) ? sanitize_text_field($_POST['hora_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : '';
        $hora_fin = isset($_POST['hora_fin']) ? sanitize_text_field($_POST['hora_fin']) : '';

        if ($recurso_id && $fecha && empty($hora_inicio) && empty($hora_fin) && empty($fecha_inicio) && empty($fecha_fin)) {
            wp_send_json_success([
                'horarios' => $this->obtener_horarios_disponibles($recurso_id, $fecha),
            ]);
        }

        if (!$recurso_id || empty($fecha_inicio) || empty($hora_inicio) || empty($fecha_fin) || empty($hora_fin)) {
            wp_send_json_error(__('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $fecha_inicio_completa = $fecha_inicio . ' ' . $hora_inicio . ':00';
        $fecha_fin_completa = $fecha_fin . ' ' . $hora_fin . ':00';

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        $conflicto = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE recurso_id = %d
             AND estado IN ('confirmada', 'pendiente')
             AND (
                 (fecha_inicio <= %s AND fecha_fin > %s)
                 OR (fecha_inicio < %s AND fecha_fin >= %s)
                 OR (fecha_inicio >= %s AND fecha_fin <= %s)
             )",
            $recurso_id,
            $fecha_inicio_completa,
            $fecha_inicio_completa,
            $fecha_fin_completa,
            $fecha_fin_completa,
            $fecha_inicio_completa,
            $fecha_fin_completa
        ));

        if ($conflicto > 0) {
            wp_send_json_success([
                'disponible' => false,
                'mensaje' => __('Horario no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_success([
                'disponible' => true,
                'mensaje' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * AJAX: Calendario de disponibilidad
     */
    public function ajax_calendario() {
        check_ajax_referer('reservas_nonce', 'nonce');

        $recurso_id = isset($_POST['recurso_id']) ? absint($_POST['recurso_id']) : 0;
        $mes = isset($_POST['mes']) ? absint($_POST['mes']) : (int) gmdate('n');
        $anio = isset($_POST['anio']) ? absint($_POST['anio']) : (int) gmdate('Y');

        if ($mes < 1 || $mes > 12) {
            $mes = (int) gmdate('n');
        }

        if ($anio < 2000 || $anio > 2100) {
            $anio = (int) gmdate('Y');
        }

        wp_send_json_success([
            'html' => $this->render_calendario_ajax($recurso_id, $mes, $anio),
        ]);
    }

    /**
     * AJAX: Filtrar recursos
     */
    public function ajax_filtrar() {
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $busqueda = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        $where = ["estado = 'activo'"];
        $params = [];

        if (!empty($tipo)) {
            $where[] = "tipo = %s";
            $params[] = $tipo;
        }

        if (!empty($busqueda)) {
            $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT * FROM $tabla_recursos WHERE " . implode(' AND ', $where) . " ORDER BY nombre ASC LIMIT 50";

        if (!empty($params)) {
            $recursos = $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            $recursos = $wpdb->get_results($sql);
        }

        ob_start();
        if (!empty($recursos)) {
            foreach ($recursos as $recurso) {
                $this->render_recurso_card($recurso);
            }
        } else {
            echo '<p class="no-resultados">' . __('No se encontraron recursos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'total' => count($recursos)]);
    }

    /**
     * Renderizar tarjeta de recurso
     */
    private function render_recurso_card($recurso) {
        ?>
        <div class="flavor-recurso-card" data-id="<?php echo esc_attr($recurso->id); ?>">
            <?php if (!empty($recurso->imagen)) : ?>
                <div class="recurso-imagen">
                    <img src="<?php echo esc_url($recurso->imagen); ?>" alt="<?php echo esc_attr($recurso->nombre); ?>">
                </div>
            <?php endif; ?>
            <div class="recurso-contenido">
                <span class="tipo"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                <h3><?php echo esc_html($recurso->nombre); ?></h3>
                <?php if (!empty($recurso->ubicacion)) : ?>
                    <p class="ubicacion"><?php echo esc_html($recurso->ubicacion); ?></p>
                <?php endif; ?>
                <div class="recurso-acciones">
                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'recursos', 'recurso_id' => $recurso->id], home_url('/mi-portal/reservas/'))); ?>" class="flavor-btn flavor-btn-sm">
                        <?php _e('Ver Detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg(['tab' => 'nueva-reserva', 'recurso_id' => $recurso->id], home_url('/mi-portal/reservas/'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el HTML del calendario para AJAX.
     */
    private function render_calendario_ajax($recurso_id, $mes, $anio) {
        $primer_dia = mktime(0, 0, 0, $mes, 1, $anio);
        $dias_mes = (int) date('t', $primer_dia);
        $inicio_semana = (int) date('N', $primer_dia);
        $hoy = current_time('Y-m-d');
        $dias = [__('Lun', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mar', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mie', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Jue', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Vie', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Sab', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Dom', FLAVOR_PLATFORM_TEXT_DOMAIN)];

        ob_start();
        echo '<div class="reservas-calendario__grid">';
        foreach ($dias as $dia_nombre) {
            echo '<div class="reservas-calendario__dia-header">' . esc_html($dia_nombre) . '</div>';
        }

        for ($i = 1; $i < $inicio_semana; $i++) {
            echo '<div class="reservas-calendario__dia otro-mes"></div>';
        }

        for ($dia = 1; $dia <= $dias_mes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            $clases = ['reservas-calendario__dia'];

            if ($fecha < $hoy) {
                $clases[] = 'deshabilitado';
            }
            if ($fecha === $hoy) {
                $clases[] = 'hoy';
            }

            $slots = $this->obtener_horarios_disponibles($recurso_id, $fecha);
            $disponibles = array_filter($slots, static function($slot) {
                return !empty($slot['disponible']);
            });
            if (empty($disponibles)) {
                $clases[] = 'deshabilitado';
            }

            echo '<div class="' . esc_attr(implode(' ', $clases)) . '" data-fecha="' . esc_attr($fecha) . '">';
            echo '<span class="reservas-calendario__dia-numero">' . esc_html((string) $dia) . '</span>';
            echo '</div>';
        }

        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Obtiene slots de una hora para un recurso y una fecha.
     */
    private function obtener_horarios_disponibles($recurso_id, $fecha) {
        global $wpdb;

        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $inicio = strtotime($fecha . ' 09:00:00');
        $fin_dia = strtotime($fecha . ' 22:00:00');
        $horarios = [];

        while ($inicio < $fin_dia) {
            $fin_slot = $inicio + HOUR_IN_SECONDS;
            $hora_inicio = gmdate('H:i', $inicio);
            $hora_fin = gmdate('H:i', $fin_slot);
            $inicio_completo = $fecha . ' ' . $hora_inicio . ':00';
            $fin_completo = $fecha . ' ' . $hora_fin . ':00';

            $conflicto = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$tabla_reservas}
                 WHERE recurso_id = %d
                   AND estado IN ('confirmada', 'pendiente')
                   AND (
                       (fecha_inicio <= %s AND fecha_fin > %s)
                       OR (fecha_inicio < %s AND fecha_fin >= %s)
                       OR (fecha_inicio >= %s AND fecha_fin <= %s)
                   )",
                $recurso_id,
                $inicio_completo,
                $inicio_completo,
                $fin_completo,
                $fin_completo,
                $inicio_completo,
                $fin_completo
            ));

            $horarios[] = [
                'inicio' => $hora_inicio,
                'fin' => $hora_fin,
                'disponible' => $conflicto === 0,
            ];

            $inicio = $fin_slot;
        }

        return $horarios;
    }
}
