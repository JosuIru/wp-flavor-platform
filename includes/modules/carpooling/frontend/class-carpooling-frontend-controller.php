<?php
/**
 * Controller Frontend para Carpooling
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Carpooling
 */
class Flavor_Carpooling_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicialización
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes avanzados sin pisar implementaciones previas.
        $shortcodes = [
            'carpooling_viajes' => 'shortcode_viajes',
            'carpooling_mis_viajes' => 'shortcode_mis_viajes',
            'carpooling_mis_reservas' => 'shortcode_mis_reservas',
            'carpooling_publicar' => 'shortcode_publicar',
            'carpooling_buscar' => 'shortcode_buscar',
            'carpooling_proximo_viaje' => 'shortcode_proximo_viaje',
            'carpooling_busqueda_rapida' => 'shortcode_busqueda_rapida',
            'carpooling_buscar_viaje' => 'shortcode_buscar',
            'carpooling_publicar_viaje' => 'shortcode_publicar',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_carpooling_reservar_plaza', [$this, 'ajax_reservar_plaza']);
        add_action('wp_ajax_carpooling_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_carpooling_cancelar_viaje', [$this, 'ajax_cancelar_viaje']);
        add_action('wp_ajax_carpooling_buscar_viajes', [$this, 'ajax_buscar_viajes']);
        add_action('wp_ajax_nopriv_carpooling_buscar_viajes', [$this, 'ajax_buscar_viajes']);
        add_action('wp_ajax_carpooling_contactar_conductor', [$this, 'ajax_contactar_conductor']);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_templates']);

        // Nota: Los tabs del dashboard se registran en class-carpooling-dashboard-tab.php
        // para mejor organizacion y funcionalidad completa
    }

    /**
     * Registrar assets del frontend
     */
    public function registrar_assets() {
        $plugin_url = plugins_url('/', dirname(__FILE__));
        $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

        // CSS base
        wp_register_style(
            'carpooling-frontend',
            $plugin_url . 'assets/carpooling-frontend.css',
            [],
            $version
        );

        // JavaScript base
        wp_register_script(
            'carpooling-frontend',
            $plugin_url . 'assets/carpooling-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Configuración global para JavaScript
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/carpooling/'),
            'nonce' => wp_create_nonce('carpooling_frontend_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url(flavor_current_request_url()),
            'i18n' => [
                'reservaExitosa' => __('Plaza reservada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reservaCancelada' => __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'viajeCancelado' => __('Viaje cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarReserva' => __('¿Confirmar reserva de plaza?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarCancelarReserva' => __('¿Cancelar tu reserva?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarCancelarViaje' => __('¿Cancelar este viaje? Se notificará a los pasajeros.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinResultados' => __('No se encontraron viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'mensajeEnviado' => __('Mensaje enviado al conductor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];

        wp_localize_script('carpooling-frontend', 'carpoolingFrontend', $configuracion_js);
    }

    /**
     * Encolar assets cuando se necesitan
     */
    private function encolar_assets() {
        wp_enqueue_style('carpooling-frontend');
        wp_enqueue_script('carpooling-frontend');
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs_dashboard($tabs) {
        $tabs['carpooling-conductor'] = [
            'label' => __('Mis Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'car',
            'callback' => [$this, 'render_tab_mis_viajes'],
            'orden' => 50,
            'badge' => $this->contar_viajes_activos(),
        ];

        $tabs['carpooling-pasajero'] = [
            'label' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'tickets-alt',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden' => 51,
            'badge' => $this->contar_reservas_activas(),
        ];

        return $tabs;
    }

    /**
     * Contar viajes activos como conductor
     */
    private function contar_viajes_activos() {
        if (!is_user_logged_in()) {
            return 0;
        }

        return count(get_posts([
            'post_type' => 'carpooling_viaje',
            'post_status' => 'publish',
            'author' => get_current_user_id(),
            'posts_per_page' => function_exists('flavor_safe_posts_limit') ? flavor_safe_posts_limit(-1) : 200,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_carpooling_fecha',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ]));
    }

    /**
     * Contar reservas activas como pasajero
     */
    private function contar_reservas_activas() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND estado = 'confirmada'",
            get_current_user_id()
        ));
    }

    /**
     * Verificar si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;
    }

    /**
     * Shortcode: Listado de viajes
     */
    public function shortcode_viajes($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'limite' => 12,
            'mostrar_filtros' => 'si',
            'origen' => '',
            'destino' => '',
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'fecha',
            'order' => 'ASC',
        ], $atts);

        // Generar clases CSS visuales (VBP)
        $visual_classes = [];
        if (!empty($atributos['esquema_color']) && $atributos['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atributos['esquema_color']);
        }
        if (!empty($atributos['estilo_tarjeta']) && $atributos['estilo_tarjeta'] !== 'elevated') {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atributos['estilo_tarjeta']);
        }
        if (!empty($atributos['radio_bordes']) && $atributos['radio_bordes'] !== 'lg') {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atributos['radio_bordes']);
        }
        if (!empty($atributos['animacion_entrada']) && $atributos['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atributos['animacion_entrada']);
        }
        $atributos['visual_class_string'] = implode(' ', $visual_classes);

        ob_start();
        $this->render_viajes($atributos);
        return ob_get_clean();
    }

    /**
     * Renderizar listado de viajes
     */
    private function render_viajes($atts) {
        // Obtener viajes futuros
        $args = [
            'post_type' => 'carpooling_viaje',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'meta_key' => '_carpooling_fecha',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_carpooling_fecha',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
                [
                    'key' => '_carpooling_plazas_disponibles',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ],
            ],
        ];

        if (!empty($atts['origen'])) {
            $args['meta_query'][] = [
                'key' => '_carpooling_origen',
                'value' => $atts['origen'],
                'compare' => 'LIKE',
            ];
        }

        if (!empty($atts['destino'])) {
            $args['meta_query'][] = [
                'key' => '_carpooling_destino',
                'value' => $atts['destino'],
                'compare' => 'LIKE',
            ];
        }

        $viajes = get_posts($args);
        $visual_class_string = isset($atts['visual_class_string']) ? $atts['visual_class_string'] : '';
        ?>
        <div class="carpooling-viajes <?php echo esc_attr($visual_class_string); ?>">
            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="carpooling-filtros">
                    <div class="filtro-origen">
                        <label for="carpooling-origen"><?php _e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="carpooling-origen" placeholder="<?php _e('¿Desde dónde?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="filtro-destino">
                        <label for="carpooling-destino"><?php _e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="carpooling-destino" placeholder="<?php _e('¿A dónde?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="filtro-fecha">
                        <label for="carpooling-fecha"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" id="carpooling-fecha" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="button" class="btn-buscar" id="carpooling-buscar-btn">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php endif; ?>

            <div class="carpooling-lista" id="carpooling-lista">
                <?php if (empty($viajes)): ?>
                    <p class="carpooling-sin-viajes"><?php _e('No hay viajes disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($viajes as $viaje): ?>
                        <?php $this->render_viaje_card($viaje); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tarjeta de viaje
     */
    private function render_viaje_card($viaje) {
        $origen = get_post_meta($viaje->ID, '_carpooling_origen', true);
        $destino = get_post_meta($viaje->ID, '_carpooling_destino', true);
        $fecha = get_post_meta($viaje->ID, '_carpooling_fecha', true);
        $hora = get_post_meta($viaje->ID, '_carpooling_hora', true);
        $precio = get_post_meta($viaje->ID, '_carpooling_precio', true);
        $plazas_total = (int) get_post_meta($viaje->ID, '_carpooling_plazas', true);
        $plazas_disponibles = (int) get_post_meta($viaje->ID, '_carpooling_plazas_disponibles', true);
        $vehiculo = get_post_meta($viaje->ID, '_carpooling_vehiculo', true);
        $conductor = get_userdata($viaje->post_author);
        $es_propio = is_user_logged_in() && $viaje->post_author == get_current_user_id();

        // Valoración del conductor
        $valoracion = get_user_meta($viaje->post_author, '_carpooling_valoracion', true) ?: 0;
        $num_viajes = get_user_meta($viaje->post_author, '_carpooling_viajes_completados', true) ?: 0;
        ?>
        <div class="carpooling-viaje-card <?php echo $es_propio ? 'es-propio' : ''; ?>"
             data-viaje-id="<?php echo esc_attr($viaje->ID); ?>">

            <div class="viaje-ruta">
                <div class="viaje-punto viaje-origen">
                    <span class="punto-marker origen"></span>
                    <div class="punto-info">
                        <span class="punto-hora"><?php echo esc_html($hora); ?></span>
                        <span class="punto-lugar"><?php echo esc_html($origen); ?></span>
                    </div>
                </div>
                <div class="viaje-linea"></div>
                <div class="viaje-punto viaje-destino">
                    <span class="punto-marker destino"></span>
                    <div class="punto-info">
                        <span class="punto-lugar"><?php echo esc_html($destino); ?></span>
                    </div>
                </div>
            </div>

            <div class="viaje-info">
                <p class="viaje-fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo date_i18n('l, j F Y', strtotime($fecha)); ?>
                </p>
                <div class="viaje-detalles">
                    <span class="viaje-plazas <?php echo $plazas_disponibles <= 1 ? 'pocas-plazas' : ''; ?>">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(__('%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), $plazas_disponibles); ?>
                    </span>
                    <?php if ($vehiculo): ?>
                        <span class="viaje-vehiculo">
                            <span class="dashicons dashicons-car"></span>
                            <?php echo esc_html($vehiculo); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="viaje-conductor">
                <div class="conductor-avatar">
                    <?php echo get_avatar($viaje->post_author, 48); ?>
                </div>
                <div class="conductor-info">
                    <span class="conductor-nombre"><?php echo esc_html($conductor ? $conductor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                    <?php if ($valoracion > 0): ?>
                        <span class="conductor-valoracion">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php echo number_format($valoracion, 1); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($num_viajes > 0): ?>
                        <span class="conductor-viajes"><?php printf(__('%d viajes', FLAVOR_PLATFORM_TEXT_DOMAIN), $num_viajes); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="viaje-precio">
                <?php if ($precio && floatval($precio) > 0): ?>
                    <span class="precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                    <span class="precio-label"><?php _e('por plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php else: ?>
                    <span class="precio-gratis"><?php _e('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>

            <div class="viaje-acciones">
                <a href="<?php echo get_permalink($viaje->ID); ?>" class="btn-ver-detalle">
                    <?php _e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php if (!$es_propio && is_user_logged_in() && $plazas_disponibles > 0): ?>
                    <button type="button" class="btn-reservar" data-viaje-id="<?php echo esc_attr($viaje->ID); ?>">
                        <?php _e('Reservar plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php elseif ($es_propio): ?>
                    <span class="badge-propio"><?php _e('Tu viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mis viajes como conductor
     */
    public function shortcode_mis_viajes($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="carpooling-login-requerido">' . __('Inicia sesión para ver tus viajes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        $this->render_tab_mis_viajes();
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis reservas como pasajero
     */
    public function shortcode_mis_reservas($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="carpooling-login-requerido">' . __('Inicia sesión para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        $this->render_tab_mis_reservas();
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario publicar viaje
     */
    public function shortcode_publicar($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="carpooling-login-requerido">' . __('Inicia sesión para publicar un viaje.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <div class="carpooling-publicar">
            <h3><?php _e('Publicar nuevo viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <form id="carpooling-form-publicar" class="carpooling-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="viaje-origen"><?php _e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" id="viaje-origen" name="origen" required placeholder="<?php _e('Ciudad o punto de partida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                    <div class="form-group">
                        <label for="viaje-destino"><?php _e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" id="viaje-destino" name="destino" required placeholder="<?php _e('Ciudad o punto de llegada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="viaje-fecha"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="date" id="viaje-fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="viaje-hora"><?php _e('Hora de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="time" id="viaje-hora" name="hora" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="viaje-plazas"><?php _e('Plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="number" id="viaje-plazas" name="plazas" required min="1" max="8" value="3">
                    </div>
                    <div class="form-group">
                        <label for="viaje-precio"><?php _e('Precio por plaza (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="viaje-precio" name="precio" min="0" step="0.5" value="0" placeholder="<?php _e('0 = Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="viaje-vehiculo"><?php _e('Vehículo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" id="viaje-vehiculo" name="vehiculo" placeholder="<?php _e('Ej: Seat Ibiza rojo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
                <div class="form-group">
                    <label for="viaje-notas"><?php _e('Notas adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="viaje-notas" name="notas" rows="3" placeholder="<?php _e('Paradas intermedias, equipaje permitido, preferencias...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Búsqueda avanzada
     */
    public function shortcode_buscar($atts) {
        $this->encolar_assets();

        $origen_filtro = sanitize_text_field($_GET['origen'] ?? '');
        $destino_filtro = sanitize_text_field($_GET['destino'] ?? '');
        $fecha_filtro = sanitize_text_field($_GET['fecha'] ?? '');
        $plazas_filtro = intval($_GET['plazas'] ?? 0);

        $viajes_encontrados = [];
        $hay_busqueda = !empty($origen_filtro) || !empty($destino_filtro) || !empty($fecha_filtro);

        if ($hay_busqueda) {
            $viajes_encontrados = $this->buscar_viajes($origen_filtro, $destino_filtro, $fecha_filtro, $plazas_filtro);
        }

        ob_start();
        ?>
        <div class="carpooling-busqueda">
            <form class="busqueda-form" id="carpooling-busqueda-form" method="get">
                <div class="busqueda-campos">
                    <div class="campo-grupo">
                        <label for="busqueda-origen"><?php _e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="busqueda-origen" name="origen"
                               placeholder="<?php _e('¿Desde dónde?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               value="<?php echo esc_attr($origen_filtro); ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-destino"><?php _e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="busqueda-destino" name="destino"
                               placeholder="<?php _e('¿A dónde?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               value="<?php echo esc_attr($destino_filtro); ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-fecha"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" id="busqueda-fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo esc_attr($fecha_filtro); ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-plazas"><?php _e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="busqueda-plazas" name="plazas">
                            <option value=""><?php _e('Cualquiera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="1" <?php selected($plazas_filtro, 1); ?>>1+</option>
                            <option value="2" <?php selected($plazas_filtro, 2); ?>>2+</option>
                            <option value="3" <?php selected($plazas_filtro, 3); ?>>3+</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-buscar">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>

            <?php if ($hay_busqueda): ?>
            <div class="busqueda-resultados" id="carpooling-resultados">
                <p class="resultados-count">
                    <?php printf(
                        _n('%d viaje encontrado', '%d viajes encontrados', count($viajes_encontrados), FLAVOR_PLATFORM_TEXT_DOMAIN),
                        count($viajes_encontrados)
                    ); ?>
                </p>

                <?php if (!empty($viajes_encontrados)): ?>
                <div class="carpooling-viajes-grid">
                    <?php foreach ($viajes_encontrados as $viaje): ?>
                    <?php
                    $fecha_viaje = get_post_meta($viaje->ID, '_carpooling_fecha', true);
                    $hora_viaje = get_post_meta($viaje->ID, '_carpooling_hora', true);
                    $origen_viaje = get_post_meta($viaje->ID, '_carpooling_origen', true);
                    $destino_viaje = get_post_meta($viaje->ID, '_carpooling_destino', true);
                    $precio_viaje = get_post_meta($viaje->ID, '_carpooling_precio', true);
                    $plazas_viaje = get_post_meta($viaje->ID, '_carpooling_plazas_disponibles', true);
                    $conductor_viaje = get_userdata($viaje->post_author);
                    ?>
                    <div class="carpooling-viaje-card">
                        <div class="viaje-ruta">
                            <div class="viaje-punto origen">
                                <span class="dashicons dashicons-location"></span>
                                <span class="punto-nombre"><?php echo esc_html($origen_viaje); ?></span>
                            </div>
                            <div class="viaje-linea">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </div>
                            <div class="viaje-punto destino">
                                <span class="dashicons dashicons-location-alt"></span>
                                <span class="punto-nombre"><?php echo esc_html($destino_viaje); ?></span>
                            </div>
                        </div>
                        <div class="viaje-detalles">
                            <div class="viaje-fecha">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo date_i18n('j M Y', strtotime($fecha_viaje)); ?>
                                <?php if ($hora_viaje): ?>
                                    <span class="viaje-hora"><?php echo esc_html($hora_viaje); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="viaje-info-extra">
                                <?php if ($plazas_viaje): ?>
                                <span class="viaje-plazas">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(__('%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), intval($plazas_viaje)); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($precio_viaje): ?>
                                <span class="viaje-precio">
                                    <?php echo esc_html(number_format_i18n($precio_viaje, 2)); ?>€
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="viaje-conductor">
                            <?php echo get_avatar($viaje->post_author, 32); ?>
                            <span><?php echo esc_html($conductor_viaje->display_name ?? __('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                        </div>
                        <div class="viaje-acciones">
                            <a href="<?php echo get_permalink($viaje->ID); ?>" class="btn-ver-viaje">
                                <?php _e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="sin-resultados">
                    <span class="dashicons dashicons-car"></span>
                    <p><?php _e('No se encontraron viajes con esos criterios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="sugerencia"><?php _e('Prueba con otras fechas o destinos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca viajes disponibles
     */
    private function buscar_viajes($origen = '', $destino = '', $fecha = '', $plazas_minimas = 0, $limite = 12) {
        $argumentos_query = [
            'post_type' => 'carpooling_viaje',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'meta_query' => ['relation' => 'AND'],
        ];

        // Solo viajes futuros por defecto
        if (empty($fecha)) {
            $argumentos_query['meta_query'][] = [
                'key' => '_carpooling_fecha',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE',
            ];
        } else {
            $argumentos_query['meta_query'][] = [
                'key' => '_carpooling_fecha',
                'value' => $fecha,
                'compare' => '=',
                'type' => 'DATE',
            ];
        }

        if (!empty($origen)) {
            $argumentos_query['meta_query'][] = [
                'key' => '_carpooling_origen',
                'value' => $origen,
                'compare' => 'LIKE',
            ];
        }

        if (!empty($destino)) {
            $argumentos_query['meta_query'][] = [
                'key' => '_carpooling_destino',
                'value' => $destino,
                'compare' => 'LIKE',
            ];
        }

        if ($plazas_minimas > 0) {
            $argumentos_query['meta_query'][] = [
                'key' => '_carpooling_plazas_disponibles',
                'value' => $plazas_minimas,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        $argumentos_query['meta_key'] = '_carpooling_fecha';
        $argumentos_query['orderby'] = 'meta_value';
        $argumentos_query['order'] = 'ASC';

        $consulta_viajes = new WP_Query($argumentos_query);
        return $consulta_viajes->posts;
    }

    /**
     * Shortcode: Próximo viaje del usuario (como conductor o pasajero)
     * Uso: [carpooling_proximo_viaje]
     */
    public function shortcode_proximo_viaje($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        // Buscar próximo viaje como conductor
        $proximo_conductor = get_posts([
            'post_type'      => 'carpooling_viaje',
            'post_status'    => 'publish',
            'author'         => $usuario_id,
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => '_carpooling_fecha',
                    'value'   => date('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
            'meta_key'       => '_carpooling_fecha',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ]);

        // Buscar próxima reserva como pasajero
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
        $proxima_reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title as titulo, pm.meta_value as fecha
             FROM {$tabla_reservas} r
             JOIN {$wpdb->posts} p ON r.viaje_id = p.ID
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_carpooling_fecha'
             WHERE r.pasajero_id = %d AND r.estado = 'confirmada' AND pm.meta_value >= %s
             ORDER BY pm.meta_value ASC LIMIT 1",
            $usuario_id,
            date('Y-m-d')
        ));

        $viaje = null;
        $es_conductor = false;

        if (!empty($proximo_conductor) && !$proxima_reserva) {
            $viaje = $proximo_conductor[0];
            $es_conductor = true;
        } elseif ($proxima_reserva && empty($proximo_conductor)) {
            $viaje = (object)['ID' => $proxima_reserva->viaje_id, 'post_title' => $proxima_reserva->titulo];
            $es_conductor = false;
        } elseif (!empty($proximo_conductor) && $proxima_reserva) {
            $fecha_conductor = get_post_meta($proximo_conductor[0]->ID, '_carpooling_fecha', true);
            if (strtotime($fecha_conductor) <= strtotime($proxima_reserva->fecha)) {
                $viaje = $proximo_conductor[0];
                $es_conductor = true;
            } else {
                $viaje = (object)['ID' => $proxima_reserva->viaje_id, 'post_title' => $proxima_reserva->titulo];
            }
        }

        if (!$viaje) {
            return '<div class="carpooling-empty-widget"><p>' . __('No tienes viajes próximos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        $fecha = get_post_meta($viaje->ID, '_carpooling_fecha', true);
        $hora = get_post_meta($viaje->ID, '_carpooling_hora', true);
        $origen = get_post_meta($viaje->ID, '_carpooling_origen', true);
        $destino = get_post_meta($viaje->ID, '_carpooling_destino', true);

        ob_start();
        ?>
        <div class="carpooling-proximo-widget">
            <div class="proximo-badge <?php echo $es_conductor ? 'conductor' : 'pasajero'; ?>">
                <?php echo $es_conductor ? __('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
            <div class="proximo-fecha"><?php echo esc_html(date_i18n('D j M', strtotime($fecha)) . ' · ' . $hora); ?></div>
            <div class="proximo-ruta">
                <span class="origen"><?php echo esc_html($origen); ?></span>
                <span class="flecha">→</span>
                <span class="destino"><?php echo esc_html($destino); ?></span>
            </div>
            <a href="<?php echo get_permalink($viaje->ID); ?>" class="proximo-link"><?php _e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Búsqueda rápida compacta
     * Uso: [carpooling_busqueda_rapida]
     */
    public function shortcode_busqueda_rapida($atts) {
        $this->encolar_assets();

        ob_start();
        ?>
        <div class="carpooling-busqueda-rapida">
            <form class="busqueda-rapida-form" action="<?php echo esc_url(home_url('/mi-portal/carpooling/buscar/')); ?>" method="get">
                <input type="text" name="origen" placeholder="<?php _e('Desde...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="campo-origen">
                <span class="separador">→</span>
                <input type="text" name="destino" placeholder="<?php _e('Hasta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="campo-destino">
                <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" class="campo-fecha">
                <button type="submit" class="btn-busqueda-rapida">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tab de Mis Viajes (conductor) en dashboard
     */
    public function render_tab_mis_viajes() {
        $usuario_id = get_current_user_id();

        $viajes = get_posts([
            'post_type' => 'carpooling_viaje',
            'post_status' => ['publish', 'draft'],
            'author' => $usuario_id,
            'posts_per_page' => function_exists('flavor_safe_posts_limit') ? flavor_safe_posts_limit(-1) : 200,
            'meta_key' => '_carpooling_fecha',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ]);
        ?>
        <div class="carpooling-dashboard-tab carpooling-mis-viajes">
            <div class="tab-header">
                <h2><?php _e('Mis Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/carpooling/publicar/')); ?>" class="btn btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Nuevo viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($viajes)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-car"></span>
                    <p><?php _e('No has publicado ningún viaje.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/carpooling/publicar/')); ?>" class="btn btn-primary">
                        <?php _e('Publicar mi primer viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="viajes-lista">
                    <?php foreach ($viajes as $viaje):
                        $origen = get_post_meta($viaje->ID, '_carpooling_origen', true);
                        $destino = get_post_meta($viaje->ID, '_carpooling_destino', true);
                        $fecha = get_post_meta($viaje->ID, '_carpooling_fecha', true);
                        $hora = get_post_meta($viaje->ID, '_carpooling_hora', true);
                        $plazas = (int) get_post_meta($viaje->ID, '_carpooling_plazas', true);
                        $plazas_disponibles = (int) get_post_meta($viaje->ID, '_carpooling_plazas_disponibles', true);
                        $pasado = strtotime($fecha) < strtotime(date('Y-m-d'));
                    ?>
                        <div class="viaje-item <?php echo $pasado ? 'pasado' : ''; ?>" data-viaje-id="<?php echo esc_attr($viaje->ID); ?>">
                            <div class="viaje-ruta-mini">
                                <span class="origen"><?php echo esc_html($origen); ?></span>
                                <span class="flecha">→</span>
                                <span class="destino"><?php echo esc_html($destino); ?></span>
                            </div>
                            <div class="viaje-info">
                                <span class="viaje-fecha">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo date_i18n('j M Y', strtotime($fecha)); ?>
                                </span>
                                <span class="viaje-hora">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($hora); ?>
                                </span>
                                <span class="viaje-plazas">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(__('%d/%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), $plazas - $plazas_disponibles, $plazas); ?>
                                </span>
                            </div>
                            <div class="viaje-estado">
                                <?php if ($pasado): ?>
                                    <span class="estado-badge pasado"><?php _e('Finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php elseif ($viaje->post_status === 'draft'): ?>
                                    <span class="estado-badge borrador"><?php _e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="estado-badge activo"><?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="viaje-acciones">
                                <a href="<?php echo get_permalink($viaje->ID); ?>" class="btn-ver" title="<?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                <?php if (!$pasado): ?>
                                    <a href="<?php echo get_edit_post_link($viaje->ID); ?>" class="btn-editar" title="<?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <button type="button" class="btn-cancelar-viaje" title="<?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
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
     * Render tab de Mis Reservas (pasajero) en dashboard
     */
    public function render_tab_mis_reservas() {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';

        $reservas = [];
        if ($this->tabla_existe($tabla)) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, v.post_title as viaje_titulo
                 FROM {$tabla} r
                 LEFT JOIN {$wpdb->posts} v ON r.viaje_id = v.ID
                 WHERE r.usuario_id = %d
                 ORDER BY r.fecha_reserva DESC",
                $usuario_id
            ));
        }
        ?>
        <div class="carpooling-dashboard-tab carpooling-mis-reservas">
            <div class="tab-header">
                <h2><?php _e('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($reservas)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-tickets-alt"></span>
                    <p><?php _e('No tienes reservas de viaje.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/carpooling/')); ?>" class="btn btn-primary">
                        <?php _e('Buscar viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="reservas-lista">
                    <?php foreach ($reservas as $reserva):
                        $origen = get_post_meta($reserva->viaje_id, '_carpooling_origen', true);
                        $destino = get_post_meta($reserva->viaje_id, '_carpooling_destino', true);
                        $fecha = get_post_meta($reserva->viaje_id, '_carpooling_fecha', true);
                        $hora = get_post_meta($reserva->viaje_id, '_carpooling_hora', true);
                        $viaje = get_post($reserva->viaje_id);
                        $conductor = $viaje ? get_userdata($viaje->post_author) : null;
                        $pasado = $fecha && strtotime($fecha) < strtotime(date('Y-m-d'));
                    ?>
                        <div class="reserva-item estado-<?php echo esc_attr($reserva->estado); ?> <?php echo $pasado ? 'pasado' : ''; ?>">
                            <div class="reserva-ruta">
                                <span class="origen"><?php echo esc_html($origen); ?></span>
                                <span class="flecha">→</span>
                                <span class="destino"><?php echo esc_html($destino); ?></span>
                            </div>
                            <div class="reserva-info">
                                <span class="reserva-fecha">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo $fecha ? date_i18n('j M Y', strtotime($fecha)) : '-'; ?>
                                </span>
                                <span class="reserva-hora">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($hora ?: '-'); ?>
                                </span>
                                <?php if ($conductor): ?>
                                    <span class="reserva-conductor">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php echo esc_html($conductor->display_name); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="reserva-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($reserva->estado); ?>">
                                    <?php
                                    $estados_label = [
                                        'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        'completada' => __('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    ];
                                    echo esc_html($estados_label[$reserva->estado] ?? $reserva->estado);
                                    ?>
                                </span>
                            </div>
                            <?php if ($reserva->estado === 'confirmada' && !$pasado): ?>
                                <div class="reserva-acciones">
                                    <button type="button" class="btn-contactar" data-viaje-id="<?php echo esc_attr($reserva->viaje_id); ?>">
                                        <span class="dashicons dashicons-email"></span>
                                    </button>
                                    <button type="button" class="btn-cancelar" data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Reservar plaza
     */
    public function ajax_reservar_plaza() {
        check_ajax_referer('carpooling_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $plazas = absint($_POST['plazas'] ?? 1);

        if (!$viaje_id) {
            wp_send_json_error(['message' => __('Viaje no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que no sea el propio viaje
        $viaje = get_post($viaje_id);
        if (!$viaje || $viaje->post_author == get_current_user_id()) {
            wp_send_json_error(['message' => __('No puedes reservar en tu propio viaje', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar plazas disponibles
        $plazas_disponibles = (int) get_post_meta($viaje_id, '_carpooling_plazas_disponibles', true);
        if ($plazas > $plazas_disponibles) {
            wp_send_json_error(['message' => __('No hay suficientes plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';

        // Verificar si ya tiene reserva
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE usuario_id = %d AND viaje_id = %d AND estado != 'cancelada'",
            get_current_user_id(),
            $viaje_id
        ));

        if ($existente) {
            wp_send_json_error(['message' => __('Ya tienes una reserva para este viaje', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Crear reserva
        $resultado = $wpdb->insert($tabla, [
            'usuario_id' => get_current_user_id(),
            'viaje_id' => $viaje_id,
            'plazas' => $plazas,
            'fecha_reserva' => current_time('mysql'),
            'estado' => 'confirmada',
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al procesar la reserva', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Actualizar plazas disponibles
        update_post_meta($viaje_id, '_carpooling_plazas_disponibles', $plazas_disponibles - $plazas);

        // Notificar al conductor
        $conductor = get_userdata($viaje->post_author);
        $pasajero = wp_get_current_user();
        if ($conductor && $conductor->user_email) {
            $origen = get_post_meta($viaje_id, '_carpooling_origen', true);
            $destino = get_post_meta($viaje_id, '_carpooling_destino', true);
            $fecha = get_post_meta($viaje_id, '_carpooling_fecha', true);

            $asunto = sprintf(__('[Carpooling] Nueva reserva para tu viaje %s → %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $origen, $destino);
            $cuerpo = sprintf(
                __("Hola %s,\n\n%s ha reservado %d plaza(s) para tu viaje de %s a %s el %s.\n\nPuedes ver los detalles en tu panel.", FLAVOR_PLATFORM_TEXT_DOMAIN),
                $conductor->display_name,
                $pasajero->display_name,
                $plazas,
                $origen,
                $destino,
                date_i18n('j M Y', strtotime($fecha))
            );

            wp_mail($conductor->user_email, $asunto, $cuerpo);
        }

        wp_send_json_success(['message' => __('Plaza reservada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('carpooling_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $reserva_id = absint($_POST['reserva_id'] ?? 0);

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';

        // Obtener reserva
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d AND usuario_id = %d",
            $reserva_id,
            get_current_user_id()
        ));

        if (!$reserva) {
            wp_send_json_error(['message' => __('Reserva no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Cancelar
        $wpdb->update($tabla, ['estado' => 'cancelada'], ['id' => $reserva_id]);

        // Liberar plazas
        $plazas_disponibles = (int) get_post_meta($reserva->viaje_id, '_carpooling_plazas_disponibles', true);
        update_post_meta($reserva->viaje_id, '_carpooling_plazas_disponibles', $plazas_disponibles + $reserva->plazas);

        wp_send_json_success(['message' => __('Reserva cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Cancelar viaje
     */
    public function ajax_cancelar_viaje() {
        check_ajax_referer('carpooling_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $viaje = get_post($viaje_id);

        if (!$viaje || $viaje->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('No tienes permisos para esta acción', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Cambiar a borrador
        wp_update_post([
            'ID' => $viaje_id,
            'post_status' => 'draft',
        ]);

        // Notificar a pasajeros
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_carpooling_reservas';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.user_email, u.display_name
             FROM {$tabla} r
             LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.viaje_id = %d AND r.estado = 'confirmada'",
            $viaje_id
        ));

        $origen = get_post_meta($viaje_id, '_carpooling_origen', true);
        $destino = get_post_meta($viaje_id, '_carpooling_destino', true);
        $fecha = get_post_meta($viaje_id, '_carpooling_fecha', true);

        foreach ($reservas as $reserva) {
            if ($reserva->user_email) {
                $asunto = sprintf(__('[Carpooling] Viaje cancelado: %s → %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $origen, $destino);
                $cuerpo = sprintf(
                    __("Hola %s,\n\nLamentamos informarte que el viaje de %s a %s programado para el %s ha sido cancelado por el conductor.\n\nTe invitamos a buscar otras opciones de viaje.", FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $reserva->display_name,
                    $origen,
                    $destino,
                    date_i18n('j M Y', strtotime($fecha))
                );

                wp_mail($reserva->user_email, $asunto, $cuerpo);
            }

            // Marcar reserva como cancelada
            $wpdb->update($tabla, ['estado' => 'cancelada'], ['id' => $reserva->id]);
        }

        wp_send_json_success(['message' => __('Viaje cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Buscar viajes
     */
    public function ajax_buscar_viajes() {
        check_ajax_referer('carpooling_frontend_nonce', 'nonce');

        $origen = sanitize_text_field($_POST['origen'] ?? '');
        $destino = sanitize_text_field($_POST['destino'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? '');
        $plazas = absint($_POST['plazas'] ?? 0);

        $args = [
            'post_type' => 'carpooling_viaje',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_key' => '_carpooling_fecha',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_carpooling_fecha',
                    'value' => $fecha ?: date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
            ],
        ];

        if (!empty($origen)) {
            $args['meta_query'][] = [
                'key' => '_carpooling_origen',
                'value' => $origen,
                'compare' => 'LIKE',
            ];
        }

        if (!empty($destino)) {
            $args['meta_query'][] = [
                'key' => '_carpooling_destino',
                'value' => $destino,
                'compare' => 'LIKE',
            ];
        }

        if ($plazas > 0) {
            $args['meta_query'][] = [
                'key' => '_carpooling_plazas_disponibles',
                'value' => $plazas,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        $viajes = get_posts($args);

        ob_start();
        if (empty($viajes)) {
            echo '<p class="carpooling-sin-viajes">' . __('No se encontraron viajes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            foreach ($viajes as $viaje) {
                $this->render_viaje_card($viaje);
            }
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => count($viajes)]);
    }

    /**
     * AJAX: Contactar conductor
     */
    public function ajax_contactar_conductor() {
        check_ajax_referer('carpooling_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $viaje_id = absint($_POST['viaje_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (!$viaje_id || empty($mensaje)) {
            wp_send_json_error(['message' => __('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $viaje = get_post($viaje_id);
        if (!$viaje) {
            wp_send_json_error(['message' => __('Viaje no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $conductor = get_userdata($viaje->post_author);
        $pasajero = wp_get_current_user();

        if ($conductor && $conductor->user_email) {
            $origen = get_post_meta($viaje_id, '_carpooling_origen', true);
            $destino = get_post_meta($viaje_id, '_carpooling_destino', true);

            $asunto = sprintf(__('[Carpooling] Mensaje sobre tu viaje %s → %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $origen, $destino);
            $cuerpo = sprintf(
                __("Hola %s,\n\n%s te ha enviado un mensaje sobre tu viaje de %s a %s:\n\n%s\n\nPuedes responder directamente a este email.", FLAVOR_PLATFORM_TEXT_DOMAIN),
                $conductor->display_name,
                $pasajero->display_name,
                $origen,
                $destino,
                $mensaje
            );

            wp_mail($conductor->user_email, $asunto, $cuerpo, [
                'Reply-To: ' . $pasajero->user_email,
            ]);
        }

        wp_send_json_success(['message' => __('Mensaje enviado al conductor', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_templates($template) {
        $plugin_templates_path = dirname(dirname(__FILE__)) . '/frontend/';

        // Template para single carpooling_viaje
        if (is_singular('carpooling_viaje')) {
            $custom_theme = locate_template('carpooling/single-carpooling_viaje.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para archive carpooling_viaje
        if (is_post_type_archive('carpooling_viaje')) {
            $custom_theme = locate_template('carpooling/archive-carpooling_viaje.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'archive.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}
