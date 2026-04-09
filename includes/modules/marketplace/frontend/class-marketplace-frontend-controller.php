<?php
/**
 * Controller Frontend para Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend de Marketplace
 */
class Flavor_Marketplace_Frontend_Controller {

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

        // Registrar shortcodes avanzados
        if (!shortcode_exists('marketplace_catalogo')) {
            add_shortcode('marketplace_catalogo', [$this, 'shortcode_catalogo']);
        }
        if (!shortcode_exists('marketplace_listado')) {
            add_shortcode('marketplace_listado', [$this, 'shortcode_catalogo']); // Alias para páginas dinámicas
        }
        if (!shortcode_exists('marketplace_destacados')) {
            add_shortcode('marketplace_destacados', [$this, 'shortcode_destacados']); // Alias legacy para widgets dinámicos
        }
        if (!shortcode_exists('marketplace_mis_anuncios')) {
            add_shortcode('marketplace_mis_anuncios', [$this, 'shortcode_mis_anuncios']);
        }
        if (!shortcode_exists('marketplace_formulario')) {
            add_shortcode('marketplace_formulario', [$this, 'shortcode_formulario']);
        }
        if (!shortcode_exists('marketplace_detalle')) {
            add_shortcode('marketplace_detalle', [$this, 'shortcode_detalle']);
        }
        if (!shortcode_exists('marketplace_favoritos')) {
            add_shortcode('marketplace_favoritos', [$this, 'shortcode_favoritos']);
        }
        if (!shortcode_exists('marketplace_busqueda')) {
            add_shortcode('marketplace_busqueda', [$this, 'shortcode_busqueda']);
        }

        // AJAX handlers
        add_action('wp_ajax_marketplace_agregar_favorito', [$this, 'ajax_agregar_favorito']);
        add_action('wp_ajax_marketplace_quitar_favorito', [$this, 'ajax_quitar_favorito']);
        add_action('wp_ajax_marketplace_contactar_vendedor', [$this, 'ajax_contactar_vendedor']);
        add_action('wp_ajax_marketplace_marcar_vendido', [$this, 'ajax_marcar_vendido']);
        add_action('wp_ajax_marketplace_filtrar_anuncios', [$this, 'ajax_filtrar_anuncios']);

        // Template overrides
        add_filter('template_include', [$this, 'cargar_templates']);

        // Registrar tabs en Mi Portal
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs_dashboard']);
    }

    /**
     * Registrar assets del frontend
     */
    public function registrar_assets() {
        // Ruta al directorio del modulo marketplace (no frontend)
        $module_url = plugins_url('/', dirname(__FILE__));
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        // CSS base
        wp_register_style(
            'marketplace-frontend',
            $module_url . 'assets/marketplace-frontend.css',
            [],
            $version
        );

        // JavaScript base
        wp_register_script(
            'marketplace-frontend',
            $module_url . 'assets/marketplace-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Configuración global para JavaScript
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/marketplace/'),
            'nonce' => wp_create_nonce('marketplace_frontend_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url($this->get_current_request_url()),
            'i18n' => [
                'agregadoFavoritos' => __('Añadido a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'quitadoFavoritos' => __('Quitado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarEliminar' => __('¿Eliminar este anuncio?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarVendido' => __('¿Marcar como vendido?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'mensajeEnviado' => __('Mensaje enviado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinResultados' => __('No hay anuncios disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];

        wp_localize_script('marketplace-frontend', 'marketplaceFrontend', $configuracion_js);
    }

    /**
     * Encolar assets cuando se necesitan
     */
    private function encolar_assets() {
        wp_enqueue_style('marketplace-frontend');
        wp_enqueue_script('marketplace-frontend');
    }

    /**
     * Obtiene la URL actual para redirects de login en páginas dinámicas.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/mi-portal/marketplace/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Obtiene la URL canónica de detalle dentro del portal.
     */
    private function get_anuncio_url(int $anuncio_id): string {
        return home_url('/mi-portal/marketplace/detalle/?anuncio_id=' . absint($anuncio_id));
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs_dashboard($tabs) {
        $tabs['marketplace-mis-anuncios'] = [
            'label' => __('Mis Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'store',
            'callback' => [$this, 'render_tab_mis_anuncios'],
            'orden' => 35,
            'badge' => $this->contar_mis_anuncios(),
        ];

        $tabs['marketplace-favoritos'] = [
            'label' => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'heart',
            'callback' => [$this, 'render_tab_favoritos'],
            'orden' => 36,
            'badge' => $this->contar_favoritos(),
        ];

        return $tabs;
    }

    /**
     * Contar mis anuncios activos
     */
    private function contar_mis_anuncios() {
        if (!is_user_logged_in()) {
            return 0;
        }

        return count(get_posts([
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'author' => get_current_user_id(),
            'posts_per_page' => function_exists('flavor_safe_posts_limit') ? flavor_safe_posts_limit(-1) : 200,
            'fields' => 'ids',
        ]));
    }

    /**
     * Contar favoritos
     */
    private function contar_favoritos() {
        if (!is_user_logged_in()) {
            return 0;
        }

        $favoritos = get_user_meta(get_current_user_id(), '_marketplace_favoritos', true);
        return is_array($favoritos) ? count($favoritos) : 0;
    }

    /**
     * Shortcode: Catálogo de anuncios
     */
    public function shortcode_catalogo($atts) {
        $this->encolar_assets();

        $request_tipo = isset($_GET['tipo']) ? sanitize_text_field(wp_unslash((string) $_GET['tipo'])) : '';
        $request_categoria = isset($_GET['categoria']) ? sanitize_text_field(wp_unslash((string) $_GET['categoria'])) : '';
        $request_comunidad = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : '';

        $atributos = shortcode_atts([
            'tipo' => $request_tipo, // regalo, venta, cambio, alquiler
            'categoria' => $request_categoria,
            'columnas' => 3,
            'limite' => 12,
            'limit' => null, // Compatibilidad con shortcodes legacy
            'mostrar_filtros' => 'si',
            'comunidad' => $request_comunidad, // ID de comunidad para filtrar anuncios
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'date',
            'order' => 'DESC',
        ], $atts);

        if (!empty($atributos['limit']) && empty($atts['limite'])) {
            $atributos['limite'] = intval($atributos['limit']);
        }

        ob_start();
        $this->render_catalogo($atributos);
        return ob_get_clean();
    }

    /**
     * Shortcode: Destacados (compatibilidad legacy)
     *
     * La lógica nueva no distingue destacados a nivel de shortcode frontend,
     * así que este alias muestra los anuncios más recientes sin filtros.
     */
    public function shortcode_destacados($atts) {
        $atributos = shortcode_atts([
            'limit' => 4,
            'limite' => '',
            'columnas' => 2,
            'tipo' => '',
            'categoria' => '',
        ], $atts);

        if (empty($atributos['limite'])) {
            $atributos['limite'] = intval($atributos['limit']);
        }

        $atributos['mostrar_filtros'] = 'no';

        return $this->shortcode_catalogo($atributos);
    }

    /**
     * Shortcode: Mis Anuncios
     */
    public function shortcode_mis_anuncios($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">'
                 . '<p class="text-yellow-800">' . __('Debes iniciar sesión para ver tus anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
                 . '<a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . __('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>'
                 . '</div>';
        }

        // Cargar template
        $template_path = FLAVOR_CHAT_IA_PATH . 'templates/frontend/marketplace/mis-anuncios.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback al método existente
        ob_start();
        $this->render_tab_mis_anuncios();
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de publicar anuncio
     */
    public function shortcode_formulario($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'comunidad' => '', // ID de comunidad si se publica desde una comunidad
        ], $atts, 'marketplace_formulario');

        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">'
                 . '<p class="text-yellow-800">' . __('Debes iniciar sesión para publicar un anuncio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
                 . '<a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . __('Iniciar Sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>'
                 . '</div>';
        }

        // Variable disponible para el template
        $marketplace_comunidad_id = absint($atributos['comunidad']);

        // Cargar template
        $template_path = FLAVOR_CHAT_IA_PATH . 'templates/frontend/marketplace/formulario.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback básico
        return '<div class="marketplace-formulario-fallback">'
             . '<p>' . __('El formulario de publicación no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
             . '</div>';
    }

    /**
     * Shortcode: Detalle de anuncio
     */
    public function shortcode_detalle($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'id' => 0,
            'anuncio_id' => 0,
        ], $atts, 'marketplace_detalle');

        $anuncio_id = absint(
            $atts['id']
            ?: $atts['anuncio_id']
            ?: ($_GET['anuncio_id'] ?? 0)
            ?: ($_GET['id'] ?? 0)
        );

        if (!$anuncio_id) {
            return '<div class="marketplace-detalle-vacio">'
                 . '<p>' . esc_html__('No se ha indicado ningún anuncio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
                 . '<a href="' . esc_url(home_url('/mi-portal/marketplace/')) . '" class="btn btn-primary">' . esc_html__('Volver al marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>'
                 . '</div>';
        }

        $anuncio = get_post($anuncio_id);
        if (!$anuncio || 'marketplace_item' !== $anuncio->post_type || 'publish' !== $anuncio->post_status) {
            return '<div class="marketplace-detalle-vacio">'
                 . '<p>' . esc_html__('El anuncio no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>'
                 . '<a href="' . esc_url(home_url('/mi-portal/marketplace/')) . '" class="btn btn-primary">' . esc_html__('Ver otros anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>'
                 . '</div>';
        }

        $precio = get_post_meta($anuncio_id, '_marketplace_precio', true);
        $estado = get_post_meta($anuncio_id, '_marketplace_estado', true) ?: 'disponible';
        $ubicacion = get_post_meta($anuncio_id, '_marketplace_ubicacion', true);
        $condicion = get_post_meta($anuncio_id, '_marketplace_condicion', true);
        $comunidad_id = get_post_meta($anuncio_id, '_marketplace_comunidad_id', true);
        $imagen = get_the_post_thumbnail_url($anuncio_id, 'large');
        $autor = get_userdata((int) $anuncio->post_author);
        $tipos = wp_get_post_terms($anuncio_id, 'marketplace_tipo');
        $categorias = wp_get_post_terms($anuncio_id, 'marketplace_categoria');
        $tipo_slug = (!empty($tipos) && !is_wp_error($tipos)) ? $tipos[0]->slug : '';
        $tipo_label = (!empty($tipos) && !is_wp_error($tipos)) ? $tipos[0]->name : __('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $categoria_label = (!empty($categorias) && !is_wp_error($categorias)) ? $categorias[0]->name : '';

        // Obtener información de la comunidad si existe
        $comunidad_info = null;
        if ($comunidad_id) {
            global $wpdb;
            $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
            $comunidad_info = $wpdb->get_row($wpdb->prepare(
                "SELECT id, nombre FROM {$tabla_comunidades} WHERE id = %d AND estado = 'activa'",
                absint($comunidad_id)
            ));
        }

        // Etiquetas de condición
        $condiciones_label = [
            'nuevo' => __('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'como_nuevo' => __('Como nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'buen_estado' => __('Buen estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'usado' => __('Usado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'para_piezas' => __('Para piezas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        ob_start();
        ?>
        <article class="marketplace-detalle">
            <div class="marketplace-detalle-header">
                <?php if ($comunidad_info): ?>
                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad_info->id . '/')); ?>" class="btn btn-secondary">
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo esc_html($comunidad_info->nombre); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/?comunidad=' . $comunidad_info->id)); ?>" class="btn btn-outline">
                        <?php _e('Ver marketplace de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/')); ?>" class="btn btn-secondary">
                        <?php _e('Volver al marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="marketplace-detalle-grid">
                <div class="marketplace-detalle-media">
                    <?php if ($imagen): ?>
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($anuncio->post_title); ?>">
                    <?php else: ?>
                        <div class="marketplace-detalle-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="marketplace-detalle-content">
                    <div class="marketplace-detalle-badges">
                        <span class="anuncio-tipo-badge tipo-<?php echo esc_attr($tipo_slug ?: 'general'); ?>">
                            <?php echo esc_html($tipo_label); ?>
                        </span>
                        <span class="anuncio-estado-badge estado-<?php echo esc_attr($estado); ?>">
                            <?php echo esc_html(ucfirst($estado)); ?>
                        </span>
                    </div>

                    <h1><?php echo esc_html($anuncio->post_title); ?></h1>

                    <?php if (!empty($precio) && in_array($tipo_slug, ['venta', 'alquiler'], true)): ?>
                        <p class="marketplace-detalle-precio"><?php echo esc_html(number_format_i18n((float) $precio, 2)); ?>€</p>
                    <?php elseif ('regalo' === $tipo_slug): ?>
                        <p class="marketplace-detalle-precio"><?php _e('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>

                    <div class="marketplace-detalle-meta">
                        <?php if ($condicion && isset($condiciones_label[$condicion])): ?>
                            <p><span class="dashicons dashicons-tag"></span> <?php echo esc_html($condiciones_label[$condicion]); ?></p>
                        <?php endif; ?>
                        <?php if ($ubicacion): ?>
                            <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($ubicacion); ?></p>
                        <?php endif; ?>
                        <?php if ($categoria_label): ?>
                            <p><span class="dashicons dashicons-category"></span> <?php echo esc_html($categoria_label); ?></p>
                        <?php endif; ?>
                        <?php if ($comunidad_info): ?>
                            <p>
                                <span class="dashicons dashicons-groups"></span>
                                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad_info->id . '/')); ?>">
                                    <?php echo esc_html($comunidad_info->nombre); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <p><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(get_the_date('', $anuncio)); ?></p>
                        <p><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>
                    </div>

                    <div class="marketplace-detalle-descripcion">
                        <?php echo wpautop(wp_kses_post($anuncio->post_content)); ?>
                    </div>

                    <div class="marketplace-detalle-acciones">
                        <?php if (is_user_logged_in() && (int) $anuncio->post_author !== get_current_user_id() && 'vendido' !== $estado): ?>
                            <button type="button" class="btn-contactar" data-anuncio-id="<?php echo esc_attr($anuncio_id); ?>">
                                <?php _e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php elseif (!is_user_logged_in()): ?>
                            <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="btn btn-primary">
                                <?php _e('Iniciar sesión para contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php

        return ob_get_clean();
    }

    /**
     * Renderizar catálogo
     */
    private function render_catalogo($atts) {
        // Obtener anuncios
        $args = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limite']),
            'orderby' => sanitize_key($atts['orderby'] ?? 'date'),
            'order' => in_array(strtoupper($atts['order'] ?? 'DESC'), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'DESC',
        ];

        // Clases CSS para estilos visuales
        $visual_classes = [];
        if (!empty($atts['esquema_color']) && $atts['esquema_color'] !== 'default') {
            $visual_classes[] = 'flavor-scheme-' . sanitize_html_class($atts['esquema_color']);
        }
        if (!empty($atts['estilo_tarjeta'])) {
            $visual_classes[] = 'flavor-card-' . sanitize_html_class($atts['estilo_tarjeta']);
        }
        if (!empty($atts['radio_bordes'])) {
            $visual_classes[] = 'flavor-radius-' . sanitize_html_class($atts['radio_bordes']);
        }
        if (!empty($atts['animacion_entrada']) && $atts['animacion_entrada'] !== 'none') {
            $visual_classes[] = 'flavor-animate-' . sanitize_html_class($atts['animacion_entrada']);
        }
        $visual_class_str = implode(' ', $visual_classes);

        // Filtrar por tipo usando tax_query (marketplace_tipo es una taxonomía)
        if (!empty($atts['tipo'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'marketplace_tipo',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['tipo']),
            ];
        }

        if (!empty($atts['categoria'])) {
            $args['tax_query'] = [[
                'taxonomy' => 'marketplace_categoria',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['categoria']),
            ]];
        }

        // Filtrar por comunidad si se especifica
        if (!empty($atts['comunidad'])) {
            $args['meta_query'] = $args['meta_query'] ?? [];
            $args['meta_query'][] = [
                'key' => '_marketplace_comunidad_id',
                'value' => absint($atts['comunidad']),
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
        }

        $anuncios = get_posts($args);

        // Obtener categorías para filtros
        $categorias = get_terms([
            'taxonomy' => 'marketplace_categoria',
            'hide_empty' => true,
        ]);

        // Favoritos del usuario
        $favoritos = [];
        if (is_user_logged_in()) {
            $favoritos = get_user_meta(get_current_user_id(), '_marketplace_favoritos', true) ?: [];
        }

        $tipos_anuncio = [
            'regalo' => __('Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'venta' => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cambio' => __('Cambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'alquiler' => __('Alquiler', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        // Obtener información de la comunidad si está filtrando
        $comunidad_info = null;
        if (!empty($atts['comunidad'])) {
            global $wpdb;
            $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
            $comunidad_info = $wpdb->get_row($wpdb->prepare(
                "SELECT id, nombre FROM {$tabla_comunidades} WHERE id = %d AND estado = 'activa'",
                absint($atts['comunidad'])
            ));
        }
        ?>
        <div class="marketplace-catalogo <?php echo esc_attr($visual_class_str); ?>" data-columnas="<?php echo esc_attr($atts['columnas']); ?>" <?php if ($comunidad_info): ?>data-comunidad="<?php echo esc_attr($comunidad_info->id); ?>"<?php endif; ?>>
            <?php if ($comunidad_info): ?>
                <div class="marketplace-comunidad-banner">
                    <div class="banner-info">
                        <span class="dashicons dashicons-groups"></span>
                        <span>
                            <?php printf(
                                esc_html__('Mostrando anuncios de la comunidad: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                '<strong>' . esc_html($comunidad_info->nombre) . '</strong>'
                            ); ?>
                        </span>
                    </div>
                    <div class="banner-actions">
                        <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad_info->id . '/')); ?>" class="btn-volver-comunidad">
                            <?php esc_html_e('Volver a la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/')); ?>" class="btn-ver-todos">
                            <?php esc_html_e('Ver todo el marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="marketplace-filtros">
                    <div class="filtro-buscar">
                        <input type="text" id="marketplace-buscar" placeholder="<?php _e('Buscar anuncios...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="filtro-icon dashicons dashicons-search"></span>
                    </div>
                    <div class="filtro-tipo">
                        <select id="marketplace-filtrar-tipo">
                            <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($tipos_anuncio as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-categoria">
                        <select id="marketplace-filtrar-categoria">
                            <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php if (!is_wp_error($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo esc_attr($categoria->slug); ?>">
                                        <?php echo esc_html($categoria->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="marketplace-grid" id="marketplace-lista">
                <?php if (empty($anuncios)): ?>
                    <p class="marketplace-sin-anuncios"><?php _e('No hay anuncios disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <?php foreach ($anuncios as $anuncio): ?>
                        <?php $this->render_anuncio_card($anuncio, $favoritos); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tarjeta de anuncio
     */
    private function render_anuncio_card($anuncio, $favoritos = []) {
        // Obtener tipo desde taxonomía (no desde meta)
        $tipos_terminos = wp_get_post_terms($anuncio->ID, 'marketplace_tipo', ['fields' => 'slugs']);
        $tipo = (!empty($tipos_terminos) && !is_wp_error($tipos_terminos)) ? $tipos_terminos[0] : 'venta';
        $precio = get_post_meta($anuncio->ID, '_marketplace_precio', true);
        $estado = get_post_meta($anuncio->ID, '_marketplace_estado', true) ?: 'disponible';
        $ubicacion = get_post_meta($anuncio->ID, '_marketplace_ubicacion', true);
        $imagen = get_the_post_thumbnail_url($anuncio->ID, 'medium');
        $es_favorito = in_array($anuncio->ID, $favoritos);
        $autor = get_userdata($anuncio->post_author);

        $tipos_label = [
            'regalo' => __('Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'venta' => __('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cambio' => __('Cambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'alquiler' => __('Alquiler', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        ?>
        <div class="marketplace-anuncio-card <?php echo $es_favorito ? 'es-favorito' : ''; ?> estado-<?php echo esc_attr($estado); ?>"
             data-anuncio-id="<?php echo esc_attr($anuncio->ID); ?>"
             data-tipo="<?php echo esc_attr($tipo); ?>">

            <div class="anuncio-imagen">
                <?php if ($imagen): ?>
                    <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>">
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($anuncio->post_title); ?>">
                    </a>
                <?php else: ?>
                    <div class="anuncio-sin-imagen">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
                <span class="anuncio-tipo-badge tipo-<?php echo esc_attr($tipo); ?>">
                    <?php echo esc_html($tipos_label[$tipo] ?? $tipo); ?>
                </span>
                <?php if ($estado === 'vendido'): ?>
                    <span class="anuncio-vendido-badge"><?php _e('Vendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
                <?php if (is_user_logged_in()): ?>
                    <button type="button" class="btn-favorito <?php echo $es_favorito ? 'activo' : ''; ?>"
                            title="<?php echo $es_favorito ? __('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons <?php echo $es_favorito ? 'dashicons-heart' : 'dashicons-heart'; ?>"></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="anuncio-info">
                <h3 class="anuncio-titulo">
                    <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>"><?php echo esc_html($anuncio->post_title); ?></a>
                </h3>
                <?php if ($tipo === 'venta' || $tipo === 'alquiler'): ?>
                    <p class="anuncio-precio">
                        <?php if ($precio): ?>
                            <span class="precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                            <?php if ($tipo === 'alquiler'): ?>
                                <span class="precio-periodo">/<?php _e('día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="precio-negociable"><?php _e('Precio a negociar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ($ubicacion): ?>
                    <p class="anuncio-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($ubicacion); ?>
                    </p>
                <?php endif; ?>
                <p class="anuncio-autor">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php echo esc_html($autor ? $autor->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                </p>
                <p class="anuncio-fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo human_time_diff(get_the_time('U', $anuncio), current_time('timestamp')); ?>
                </p>
            </div>

            <div class="anuncio-acciones">
                <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>" class="btn-ver-detalle">
                    <?php _e('Ver detalle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <?php if (is_user_logged_in() && (int) $anuncio->post_author !== get_current_user_id() && $estado !== 'vendido'): ?>
                    <button type="button" class="btn-contactar" data-anuncio-id="<?php echo esc_attr($anuncio->ID); ?>">
                        <?php _e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Favoritos
     */
    public function shortcode_favoritos($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="marketplace-login-requerido">' . __('Inicia sesión para ver tus favoritos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        $this->render_tab_favoritos();
        return ob_get_clean();
    }

    /**
     * Shortcode: Búsqueda avanzada
     */
    public function shortcode_busqueda($atts) {
        $this->encolar_assets();

        $atributos = shortcode_atts([
            'mostrar_mapa' => 'no',
            'limite' => 12,
        ], $atts);

        $termino_busqueda = sanitize_text_field($_GET['texto'] ?? '');
        $tipo_filtro = sanitize_text_field($_GET['tipo'] ?? '');
        $precio_minimo = floatval($_GET['precio_min'] ?? 0);
        $precio_maximo = floatval($_GET['precio_max'] ?? 0);

        $anuncios_encontrados = [];
        $hay_busqueda = !empty($termino_busqueda) || !empty($tipo_filtro) || $precio_minimo > 0 || $precio_maximo > 0;

        if ($hay_busqueda) {
            $anuncios_encontrados = $this->buscar_anuncios($termino_busqueda, $tipo_filtro, $precio_minimo, $precio_maximo, $atributos['limite']);
        }

        ob_start();
        ?>
        <div class="marketplace-busqueda-avanzada">
            <form class="busqueda-form" id="marketplace-busqueda-form" method="get">
                <div class="busqueda-campos">
                    <div class="campo-grupo">
                        <label for="busqueda-texto"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="busqueda-texto" name="texto"
                               placeholder="<?php _e('¿Qué estás buscando?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                               value="<?php echo esc_attr($termino_busqueda); ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-tipo"><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="busqueda-tipo" name="tipo">
                            <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="regalo" <?php selected($tipo_filtro, 'regalo'); ?>><?php _e('Regalo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="venta" <?php selected($tipo_filtro, 'venta'); ?>><?php _e('Venta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="cambio" <?php selected($tipo_filtro, 'cambio'); ?>><?php _e('Cambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="alquiler" <?php selected($tipo_filtro, 'alquiler'); ?>><?php _e('Alquiler', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-precio-min"><?php _e('Precio mín.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="busqueda-precio-min" name="precio_min" min="0" step="0.01"
                               value="<?php echo $precio_minimo > 0 ? esc_attr($precio_minimo) : ''; ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-precio-max"><?php _e('Precio máx.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="busqueda-precio-max" name="precio_max" min="0" step="0.01"
                               value="<?php echo $precio_maximo > 0 ? esc_attr($precio_maximo) : ''; ?>">
                    </div>
                </div>
                <button type="submit" class="btn-buscar">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>

            <?php if ($hay_busqueda): ?>
            <div class="busqueda-resultados" id="marketplace-resultados">
                <p class="resultados-count">
                    <?php printf(
                        _n('%d anuncio encontrado', '%d anuncios encontrados', count($anuncios_encontrados), FLAVOR_PLATFORM_TEXT_DOMAIN),
                        count($anuncios_encontrados)
                    ); ?>
                </p>

                <?php if (!empty($anuncios_encontrados)): ?>
                <div class="marketplace-grid">
                    <?php foreach ($anuncios_encontrados as $anuncio): ?>
                    <?php
                    $precio_anuncio = get_post_meta($anuncio->ID, '_precio', true);
                    $tipo_anuncio = get_post_meta($anuncio->ID, '_tipo_anuncio', true);
                    $ubicacion_anuncio = get_post_meta($anuncio->ID, '_ubicacion', true);
                    ?>
                    <div class="marketplace-card tipo-<?php echo esc_attr($tipo_anuncio); ?>">
                        <?php if (has_post_thumbnail($anuncio->ID)): ?>
                        <div class="marketplace-card-imagen">
                            <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>">
                                <?php echo get_the_post_thumbnail($anuncio->ID, 'medium'); ?>
                            </a>
                            <?php if ($tipo_anuncio): ?>
                            <span class="tipo-badge tipo-<?php echo esc_attr($tipo_anuncio); ?>">
                                <?php echo esc_html(ucfirst($tipo_anuncio)); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="marketplace-card-content">
                            <h4 class="anuncio-titulo">
                                <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>"><?php echo esc_html($anuncio->post_title); ?></a>
                            </h4>
                            <div class="anuncio-precio">
                                <?php if ($tipo_anuncio === 'regalo'): ?>
                                    <span class="precio-gratis"><?php _e('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php elseif ($precio_anuncio): ?>
                                    <span class="precio"><?php echo esc_html(number_format_i18n($precio_anuncio, 2)); ?>€</span>
                                <?php endif; ?>
                            </div>
                            <div class="anuncio-meta">
                                <?php if ($ubicacion_anuncio): ?>
                                <span class="anuncio-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($ubicacion_anuncio); ?>
                                </span>
                                <?php endif; ?>
                                <span class="anuncio-fecha">
                                    <?php echo human_time_diff(get_the_time('U', $anuncio->ID), current_time('timestamp')); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="sin-resultados">
                    <span class="dashicons dashicons-search"></span>
                    <p><?php _e('No se encontraron anuncios con esos criterios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="sugerencia"><?php _e('Prueba con otros términos o ajusta los filtros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Busca anuncios en el marketplace
     */
    private function buscar_anuncios($termino = '', $tipo = '', $precio_min = 0, $precio_max = 0, $limite = 12) {
        $argumentos_query = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => ['relation' => 'AND'],
        ];

        if (!empty($termino)) {
            $argumentos_query['s'] = $termino;
        }

        if (!empty($tipo)) {
            $argumentos_query['meta_query'][] = [
                'key' => '_tipo_anuncio',
                'value' => $tipo,
                'compare' => '=',
            ];
        }

        if ($precio_min > 0) {
            $argumentos_query['meta_query'][] = [
                'key' => '_precio',
                'value' => $precio_min,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        if ($precio_max > 0) {
            $argumentos_query['meta_query'][] = [
                'key' => '_precio',
                'value' => $precio_max,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        $consulta_anuncios = new WP_Query($argumentos_query);
        return $consulta_anuncios->posts;
    }

    /**
     * Render tab de Mis Anuncios en dashboard
     */
    public function render_tab_mis_anuncios() {
        $usuario_id = get_current_user_id();

        $anuncios = get_posts([
            'post_type' => 'marketplace_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'author' => $usuario_id,
            'posts_per_page' => function_exists('flavor_safe_posts_limit') ? flavor_safe_posts_limit(-1) : 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        ?>
        <div class="marketplace-dashboard-tab marketplace-mis-anuncios">
            <div class="tab-header">
                <h2><?php _e('Mis Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>" class="btn btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Nuevo Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-store"></span>
                    <p><?php _e('No tienes anuncios publicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>" class="btn btn-primary">
                        <?php _e('Publicar mi primer anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="anuncios-lista">
                    <?php foreach ($anuncios as $anuncio):
                        $tipo = get_post_meta($anuncio->ID, '_marketplace_tipo', true) ?: 'venta';
                        $precio = get_post_meta($anuncio->ID, '_marketplace_precio', true);
                        $estado = get_post_meta($anuncio->ID, '_marketplace_estado', true) ?: 'disponible';
                        $vistas = get_post_meta($anuncio->ID, '_marketplace_vistas', true) ?: 0;
                        $imagen = get_the_post_thumbnail_url($anuncio->ID, 'thumbnail');
                    ?>
                        <div class="anuncio-item estado-<?php echo esc_attr($estado); ?>" data-anuncio-id="<?php echo esc_attr($anuncio->ID); ?>">
                            <div class="anuncio-imagen">
                                <?php if ($imagen): ?>
                                    <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($anuncio->post_title); ?>">
                                <?php else: ?>
                                    <span class="sin-imagen dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </div>
                            <div class="anuncio-info">
                                <h4><?php echo esc_html($anuncio->post_title); ?></h4>
                                <p class="anuncio-meta">
                                    <span class="tipo-badge tipo-<?php echo esc_attr($tipo); ?>"><?php echo esc_html(ucfirst($tipo)); ?></span>
                                    <?php if ($precio): ?>
                                        <span class="precio"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                                    <?php endif; ?>
                                </p>
                                <p class="anuncio-stats">
                                    <span class="stat"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html($vistas); ?></span>
                                    <span class="stat"><span class="dashicons dashicons-calendar"></span> <?php echo get_the_date('', $anuncio); ?></span>
                                </p>
                            </div>
                            <div class="anuncio-estado">
                                <span class="estado-badge estado-<?php echo esc_attr($anuncio->post_status); ?>">
                                    <?php
                                    $estados_label = [
                                        'publish' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        'pending' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        'draft' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    ];
                                    echo esc_html($estados_label[$anuncio->post_status] ?? $anuncio->post_status);
                                    ?>
                                </span>
                                <?php if ($estado === 'vendido'): ?>
                                    <span class="vendido-badge"><?php _e('Vendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="anuncio-acciones">
                                <a href="<?php echo get_edit_post_link($anuncio->ID); ?>" class="btn-editar" title="<?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo esc_url($this->get_anuncio_url((int) $anuncio->ID)); ?>" class="btn-ver" title="<?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                <?php if ($estado !== 'vendido'): ?>
                                    <button type="button" class="btn-marcar-vendido" title="<?php _e('Marcar vendido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-yes-alt"></span>
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
     * Render tab de Favoritos en dashboard
     */
    public function render_tab_favoritos() {
        $usuario_id = get_current_user_id();
        $favoritos_ids = get_user_meta($usuario_id, '_marketplace_favoritos', true) ?: [];

        $anuncios = [];
        if (!empty($favoritos_ids)) {
            $anuncios = get_posts([
                'post_type' => 'marketplace_item',
                'post_status' => 'publish',
                'post__in' => $favoritos_ids,
                'posts_per_page' => function_exists('flavor_safe_posts_limit') ? flavor_safe_posts_limit(-1) : 200,
                'orderby' => 'post__in',
            ]);
        }
        ?>
        <div class="marketplace-dashboard-tab marketplace-favoritos">
            <div class="tab-header">
                <h2><?php _e('Mis Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-heart"></span>
                    <p><?php _e('No tienes anuncios guardados en favoritos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="marketplace-grid">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <?php $this->render_anuncio_card($anuncio, $favoritos_ids); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Agregar a favoritos
     */
    public function ajax_agregar_favorito() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        if (!$anuncio_id) {
            wp_send_json_error(['message' => __('Anuncio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $usuario_id = get_current_user_id();
        $favoritos = get_user_meta($usuario_id, '_marketplace_favoritos', true) ?: [];

        if (!in_array($anuncio_id, $favoritos)) {
            $favoritos[] = $anuncio_id;
            update_user_meta($usuario_id, '_marketplace_favoritos', $favoritos);
        }

        wp_send_json_success(['message' => __('Añadido a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Quitar de favoritos
     */
    public function ajax_quitar_favorito() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);

        $usuario_id = get_current_user_id();
        $favoritos = get_user_meta($usuario_id, '_marketplace_favoritos', true) ?: [];

        $favoritos = array_diff($favoritos, [$anuncio_id]);
        update_user_meta($usuario_id, '_marketplace_favoritos', array_values($favoritos));

        wp_send_json_success(['message' => __('Quitado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Contactar vendedor
     */
    public function ajax_contactar_vendedor() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (!$anuncio_id || empty($mensaje)) {
            wp_send_json_error(['message' => __('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $anuncio = get_post($anuncio_id);
        if (!$anuncio || $anuncio->post_type !== 'marketplace_item') {
            wp_send_json_error(['message' => __('Anuncio no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Enviar notificación al vendedor
        $vendedor = get_userdata($anuncio->post_author);
        $comprador = wp_get_current_user();

        if ($vendedor && $vendedor->user_email) {
            $asunto = sprintf(__('[Marketplace] Nuevo mensaje sobre: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $anuncio->post_title);
            $cuerpo = sprintf(
                __("Hola %s,\n\n%s te ha enviado un mensaje sobre tu anuncio \"%s\":\n\n%s\n\nPuedes responder directamente a este email.", FLAVOR_PLATFORM_TEXT_DOMAIN),
                $vendedor->display_name,
                $comprador->display_name,
                $anuncio->post_title,
                $mensaje
            );

            wp_mail($vendedor->user_email, $asunto, $cuerpo, [
                'Reply-To: ' . $comprador->user_email,
            ]);
        }

        wp_send_json_success(['message' => __('Mensaje enviado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Marcar como vendido
     */
    public function ajax_marcar_vendido() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        $anuncio = get_post($anuncio_id);

        if (!$anuncio || (int) $anuncio->post_author !== get_current_user_id()) {
            wp_send_json_error(['message' => __('No tienes permisos para esta acción', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        update_post_meta($anuncio_id, '_marketplace_estado', 'vendido');
        update_post_meta($anuncio_id, '_marketplace_fecha_venta', current_time('mysql'));

        $precio_venta = (float) get_post_meta($anuncio_id, '_marketplace_precio', true);
        do_action('flavor_marketplace_anuncio_vendido', $anuncio_id, [
            'origen' => 'marketplace_frontend',
            'vendedor_id' => (int) get_current_user_id(),
            'precio' => $precio_venta,
            'titulo' => (string) $anuncio->post_title,
            'fecha_venta' => current_time('mysql'),
        ]);

        wp_send_json_success(['message' => __('Anuncio marcado como vendido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Filtrar anuncios
     */
    public function ajax_filtrar_anuncios() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $precio_min = floatval($_POST['precio_min'] ?? 0);
        $precio_max = floatval($_POST['precio_max'] ?? 0);
        $comunidad_id = absint($_POST['comunidad_id'] ?? 0);

        $args = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ];

        if (!empty($busqueda)) {
            $args['s'] = $busqueda;
        }

        // Filtrar por tipo usando tax_query (marketplace_tipo es una taxonomía)
        if (!empty($tipo)) {
            $args['tax_query'][] = [
                'taxonomy' => 'marketplace_tipo',
                'field' => 'slug',
                'terms' => $tipo,
            ];
        }

        if (!empty($categoria)) {
            $args['tax_query'] = [[
                'taxonomy' => 'marketplace_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        // Filtrar por comunidad
        if ($comunidad_id > 0) {
            $args['meta_query'] = $args['meta_query'] ?? [];
            $args['meta_query'][] = [
                'key' => '_marketplace_comunidad_id',
                'value' => $comunidad_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
        }

        if ($precio_min > 0 || $precio_max > 0) {
            $precio_query = [
                'key' => '_marketplace_precio',
                'type' => 'NUMERIC',
            ];

            if ($precio_min > 0 && $precio_max > 0) {
                $precio_query['value'] = [$precio_min, $precio_max];
                $precio_query['compare'] = 'BETWEEN';
            } elseif ($precio_min > 0) {
                $precio_query['value'] = $precio_min;
                $precio_query['compare'] = '>=';
            } else {
                $precio_query['value'] = $precio_max;
                $precio_query['compare'] = '<=';
            }

            $args['meta_query'][] = $precio_query;
        }

        $anuncios = get_posts($args);
        $favoritos = [];
        if (is_user_logged_in()) {
            $favoritos = get_user_meta(get_current_user_id(), '_marketplace_favoritos', true) ?: [];
        }

        ob_start();
        if (empty($anuncios)) {
            echo '<p class="marketplace-sin-anuncios">' . __('No se encontraron anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        } else {
            foreach ($anuncios as $anuncio) {
                $this->render_anuncio_card($anuncio, $favoritos);
            }
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => count($anuncios)]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_templates($template) {
        $plugin_templates_path = dirname(dirname(__FILE__)) . '/frontend/';

        // Template para single marketplace_item
        if (is_singular('marketplace_item')) {
            $custom_theme = locate_template('marketplace/single-marketplace_item.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para archive marketplace_item
        if (is_post_type_archive('marketplace_item')) {
            $custom_theme = locate_template('marketplace/archive-marketplace_item.php');
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
