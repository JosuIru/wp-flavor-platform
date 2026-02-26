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
        add_shortcode('marketplace_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('marketplace_listado', [$this, 'shortcode_catalogo']); // Alias para páginas dinámicas
        add_shortcode('marketplace_mis_anuncios', [$this, 'shortcode_mis_anuncios']);
        add_shortcode('marketplace_formulario', [$this, 'shortcode_formulario']);
        add_shortcode('marketplace_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('marketplace_favoritos', [$this, 'shortcode_favoritos']);
        add_shortcode('marketplace_busqueda', [$this, 'shortcode_busqueda']);

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
            'loginUrl' => wp_login_url(get_permalink()),
            'i18n' => [
                'agregadoFavoritos' => __('Añadido a favoritos', 'flavor-chat-ia'),
                'quitadoFavoritos' => __('Quitado de favoritos', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'confirmarEliminar' => __('¿Eliminar este anuncio?', 'flavor-chat-ia'),
                'confirmarVendido' => __('¿Marcar como vendido?', 'flavor-chat-ia'),
                'mensajeEnviado' => __('Mensaje enviado correctamente', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sinResultados' => __('No hay anuncios disponibles', 'flavor-chat-ia'),
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
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs_dashboard($tabs) {
        $tabs['marketplace-mis-anuncios'] = [
            'label' => __('Mis Anuncios', 'flavor-chat-ia'),
            'icon' => 'store',
            'callback' => [$this, 'render_tab_mis_anuncios'],
            'orden' => 35,
            'badge' => $this->contar_mis_anuncios(),
        ];

        $tabs['marketplace-favoritos'] = [
            'label' => __('Favoritos', 'flavor-chat-ia'),
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
            'posts_per_page' => -1,
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

        $atributos = shortcode_atts([
            'tipo' => '', // regalo, venta, cambio, alquiler
            'categoria' => '',
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'si',
        ], $atts);

        ob_start();
        $this->render_catalogo($atributos);
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis Anuncios
     */
    public function shortcode_mis_anuncios($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">'
                 . '<p class="text-yellow-800">' . __('Debes iniciar sesión para ver tus anuncios.', 'flavor-chat-ia') . '</p>'
                 . '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . __('Iniciar Sesión', 'flavor-chat-ia') . '</a>'
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

        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">'
                 . '<p class="text-yellow-800">' . __('Debes iniciar sesión para publicar un anuncio.', 'flavor-chat-ia') . '</p>'
                 . '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="inline-block mt-4 bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600">' . __('Iniciar Sesión', 'flavor-chat-ia') . '</a>'
                 . '</div>';
        }

        // Cargar template
        $template_path = FLAVOR_CHAT_IA_PATH . 'templates/frontend/marketplace/formulario.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback básico
        return '<div class="marketplace-formulario-fallback">'
             . '<p>' . __('El formulario de publicación no está disponible.', 'flavor-chat-ia') . '</p>'
             . '</div>';
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
            'orderby' => 'date',
            'order' => 'DESC',
        ];

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
            'regalo' => __('Regalo', 'flavor-chat-ia'),
            'venta' => __('Venta', 'flavor-chat-ia'),
            'cambio' => __('Cambio', 'flavor-chat-ia'),
            'alquiler' => __('Alquiler', 'flavor-chat-ia'),
        ];
        ?>
        <div class="marketplace-catalogo" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="marketplace-filtros">
                    <div class="filtro-buscar">
                        <input type="text" id="marketplace-buscar" placeholder="<?php _e('Buscar anuncios...', 'flavor-chat-ia'); ?>">
                        <span class="filtro-icon dashicons dashicons-search"></span>
                    </div>
                    <div class="filtro-tipo">
                        <select id="marketplace-filtrar-tipo">
                            <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($tipos_anuncio as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-categoria">
                        <select id="marketplace-filtrar-categoria">
                            <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
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
                    <p class="marketplace-sin-anuncios"><?php _e('No hay anuncios disponibles en este momento.', 'flavor-chat-ia'); ?></p>
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
            'regalo' => __('Regalo', 'flavor-chat-ia'),
            'venta' => __('Venta', 'flavor-chat-ia'),
            'cambio' => __('Cambio', 'flavor-chat-ia'),
            'alquiler' => __('Alquiler', 'flavor-chat-ia'),
        ];
        ?>
        <div class="marketplace-anuncio-card <?php echo $es_favorito ? 'es-favorito' : ''; ?> estado-<?php echo esc_attr($estado); ?>"
             data-anuncio-id="<?php echo esc_attr($anuncio->ID); ?>"
             data-tipo="<?php echo esc_attr($tipo); ?>">

            <div class="anuncio-imagen">
                <?php if ($imagen): ?>
                    <a href="<?php echo get_permalink($anuncio->ID); ?>">
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
                    <span class="anuncio-vendido-badge"><?php _e('Vendido', 'flavor-chat-ia'); ?></span>
                <?php endif; ?>
                <?php if (is_user_logged_in()): ?>
                    <button type="button" class="btn-favorito <?php echo $es_favorito ? 'activo' : ''; ?>"
                            title="<?php echo $es_favorito ? __('Quitar de favoritos', 'flavor-chat-ia') : __('Añadir a favoritos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons <?php echo $es_favorito ? 'dashicons-heart' : 'dashicons-heart'; ?>"></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="anuncio-info">
                <h3 class="anuncio-titulo">
                    <a href="<?php echo get_permalink($anuncio->ID); ?>"><?php echo esc_html($anuncio->post_title); ?></a>
                </h3>
                <?php if ($tipo === 'venta' || $tipo === 'alquiler'): ?>
                    <p class="anuncio-precio">
                        <?php if ($precio): ?>
                            <span class="precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                            <?php if ($tipo === 'alquiler'): ?>
                                <span class="precio-periodo">/<?php _e('día', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="precio-negociable"><?php _e('Precio a negociar', 'flavor-chat-ia'); ?></span>
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
                    <?php echo esc_html($autor ? $autor->display_name : __('Usuario', 'flavor-chat-ia')); ?>
                </p>
                <p class="anuncio-fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo human_time_diff(get_the_time('U', $anuncio), current_time('timestamp')); ?>
                </p>
            </div>

            <div class="anuncio-acciones">
                <a href="<?php echo get_permalink($anuncio->ID); ?>" class="btn-ver-detalle">
                    <?php _e('Ver detalle', 'flavor-chat-ia'); ?>
                </a>
                <?php if (is_user_logged_in() && (int) $anuncio->post_author !== get_current_user_id() && $estado !== 'vendido'): ?>
                    <button type="button" class="btn-contactar" data-anuncio-id="<?php echo esc_attr($anuncio->ID); ?>">
                        <?php _e('Contactar', 'flavor-chat-ia'); ?>
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
            return '<p class="marketplace-login-requerido">' . __('Inicia sesión para ver tus favoritos.', 'flavor-chat-ia') . '</p>';
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
                        <label for="busqueda-texto"><?php _e('Buscar', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="busqueda-texto" name="texto"
                               placeholder="<?php _e('¿Qué estás buscando?', 'flavor-chat-ia'); ?>"
                               value="<?php echo esc_attr($termino_busqueda); ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                        <select id="busqueda-tipo" name="tipo">
                            <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="regalo" <?php selected($tipo_filtro, 'regalo'); ?>><?php _e('Regalo', 'flavor-chat-ia'); ?></option>
                            <option value="venta" <?php selected($tipo_filtro, 'venta'); ?>><?php _e('Venta', 'flavor-chat-ia'); ?></option>
                            <option value="cambio" <?php selected($tipo_filtro, 'cambio'); ?>><?php _e('Cambio', 'flavor-chat-ia'); ?></option>
                            <option value="alquiler" <?php selected($tipo_filtro, 'alquiler'); ?>><?php _e('Alquiler', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-precio-min"><?php _e('Precio mín.', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="busqueda-precio-min" name="precio_min" min="0" step="0.01"
                               value="<?php echo $precio_minimo > 0 ? esc_attr($precio_minimo) : ''; ?>">
                    </div>
                    <div class="campo-grupo">
                        <label for="busqueda-precio-max"><?php _e('Precio máx.', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="busqueda-precio-max" name="precio_max" min="0" step="0.01"
                               value="<?php echo $precio_maximo > 0 ? esc_attr($precio_maximo) : ''; ?>">
                    </div>
                </div>
                <button type="submit" class="btn-buscar">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <?php if ($hay_busqueda): ?>
            <div class="busqueda-resultados" id="marketplace-resultados">
                <p class="resultados-count">
                    <?php printf(
                        _n('%d anuncio encontrado', '%d anuncios encontrados', count($anuncios_encontrados), 'flavor-chat-ia'),
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
                            <a href="<?php echo get_permalink($anuncio->ID); ?>">
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
                                <a href="<?php echo get_permalink($anuncio->ID); ?>"><?php echo esc_html($anuncio->post_title); ?></a>
                            </h4>
                            <div class="anuncio-precio">
                                <?php if ($tipo_anuncio === 'regalo'): ?>
                                    <span class="precio-gratis"><?php _e('Gratis', 'flavor-chat-ia'); ?></span>
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
                    <p><?php _e('No se encontraron anuncios con esos criterios.', 'flavor-chat-ia'); ?></p>
                    <p class="sugerencia"><?php _e('Prueba con otros términos o ajusta los filtros.', 'flavor-chat-ia'); ?></p>
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
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        ?>
        <div class="marketplace-dashboard-tab marketplace-mis-anuncios">
            <div class="tab-header">
                <h2><?php _e('Mis Anuncios', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>" class="btn btn-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Nuevo Anuncio', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-store"></span>
                    <p><?php _e('No tienes anuncios publicados.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>" class="btn btn-primary">
                        <?php _e('Publicar mi primer anuncio', 'flavor-chat-ia'); ?>
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
                                        'publish' => __('Publicado', 'flavor-chat-ia'),
                                        'pending' => __('Pendiente', 'flavor-chat-ia'),
                                        'draft' => __('Borrador', 'flavor-chat-ia'),
                                    ];
                                    echo esc_html($estados_label[$anuncio->post_status] ?? $anuncio->post_status);
                                    ?>
                                </span>
                                <?php if ($estado === 'vendido'): ?>
                                    <span class="vendido-badge"><?php _e('Vendido', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="anuncio-acciones">
                                <a href="<?php echo get_edit_post_link($anuncio->ID); ?>" class="btn-editar" title="<?php _e('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo get_permalink($anuncio->ID); ?>" class="btn-ver" title="<?php _e('Ver', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                <?php if ($estado !== 'vendido'): ?>
                                    <button type="button" class="btn-marcar-vendido" title="<?php _e('Marcar vendido', 'flavor-chat-ia'); ?>">
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
                'posts_per_page' => -1,
                'orderby' => 'post__in',
            ]);
        }
        ?>
        <div class="marketplace-dashboard-tab marketplace-favoritos">
            <div class="tab-header">
                <h2><?php _e('Mis Favoritos', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-heart"></span>
                    <p><?php _e('No tienes anuncios guardados en favoritos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/')); ?>" class="btn btn-primary">
                        <?php _e('Explorar anuncios', 'flavor-chat-ia'); ?>
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
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        if (!$anuncio_id) {
            wp_send_json_error(['message' => __('Anuncio no válido', 'flavor-chat-ia')]);
        }

        $usuario_id = get_current_user_id();
        $favoritos = get_user_meta($usuario_id, '_marketplace_favoritos', true) ?: [];

        if (!in_array($anuncio_id, $favoritos)) {
            $favoritos[] = $anuncio_id;
            update_user_meta($usuario_id, '_marketplace_favoritos', $favoritos);
        }

        wp_send_json_success(['message' => __('Añadido a favoritos', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Quitar de favoritos
     */
    public function ajax_quitar_favorito() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);

        $usuario_id = get_current_user_id();
        $favoritos = get_user_meta($usuario_id, '_marketplace_favoritos', true) ?: [];

        $favoritos = array_diff($favoritos, [$anuncio_id]);
        update_user_meta($usuario_id, '_marketplace_favoritos', array_values($favoritos));

        wp_send_json_success(['message' => __('Quitado de favoritos', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Contactar vendedor
     */
    public function ajax_contactar_vendedor() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (!$anuncio_id || empty($mensaje)) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $anuncio = get_post($anuncio_id);
        if (!$anuncio || $anuncio->post_type !== 'marketplace_item') {
            wp_send_json_error(['message' => __('Anuncio no encontrado', 'flavor-chat-ia')]);
        }

        // Enviar notificación al vendedor
        $vendedor = get_userdata($anuncio->post_author);
        $comprador = wp_get_current_user();

        if ($vendedor && $vendedor->user_email) {
            $asunto = sprintf(__('[Marketplace] Nuevo mensaje sobre: %s', 'flavor-chat-ia'), $anuncio->post_title);
            $cuerpo = sprintf(
                __("Hola %s,\n\n%s te ha enviado un mensaje sobre tu anuncio \"%s\":\n\n%s\n\nPuedes responder directamente a este email.", 'flavor-chat-ia'),
                $vendedor->display_name,
                $comprador->display_name,
                $anuncio->post_title,
                $mensaje
            );

            wp_mail($vendedor->user_email, $asunto, $cuerpo, [
                'Reply-To: ' . $comprador->user_email,
            ]);
        }

        wp_send_json_success(['message' => __('Mensaje enviado correctamente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Marcar como vendido
     */
    public function ajax_marcar_vendido() {
        check_ajax_referer('marketplace_frontend_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $anuncio_id = absint($_POST['anuncio_id'] ?? 0);
        $anuncio = get_post($anuncio_id);

        if (!$anuncio || (int) $anuncio->post_author !== get_current_user_id()) {
            wp_send_json_error(['message' => __('No tienes permisos para esta acción', 'flavor-chat-ia')]);
        }

        update_post_meta($anuncio_id, '_marketplace_estado', 'vendido');

        wp_send_json_success(['message' => __('Anuncio marcado como vendido', 'flavor-chat-ia')]);
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
            echo '<p class="marketplace-sin-anuncios">' . __('No se encontraron anuncios.', 'flavor-chat-ia') . '</p>';
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
