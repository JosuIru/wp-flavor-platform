<?php
/**
 * Módulo Marketplace para Chat IA
 *
 * Marketplace comunitario: Regalo, Venta, Cambio, Alquiler
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Marketplace - Anuncios de regalo, venta, cambio y alquiler
 */
class Flavor_Chat_Marketplace_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'marketplace';
        $this->name = 'Marketplace'; // Translation loaded on init
        $this->description = 'Plataforma para publicar anuncios de regalo, venta, cambio y alquiler entre usuarios.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true; // No tiene dependencias externas
    }

    /**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'permitir_regalo' => true,
            'permitir_venta' => true,
            'permitir_cambio' => true,
            'permitir_alquiler' => true,
            'requiere_moderacion' => false,
            'dias_expiracion_anuncio' => 30,
            'maximo_imagenes_por_anuncio' => 5,
            'permitir_mensajeria_directa' => true,
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        return [
            [
                'type'      => 'post',
                'post_type' => 'marketplace_item',
                'context'   => 'side',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();

        // Cargar instalador de tablas
        $archivo_install = dirname(__FILE__) . '/install.php';
        if (file_exists($archivo_install)) {
            require_once $archivo_install;
        }

        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en el panel de administración unificado
        $this->registrar_en_panel_unificado();

        // Registrar Custom Post Type
        add_action('init', [$this, 'registrar_custom_post_type']);

        // Registrar Taxonomías
        add_action('init', [$this, 'registrar_taxonomias']);

        // Registrar Custom Fields (Meta Boxes)
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_marketplace_item', [$this, 'guardar_meta_boxes']);

        // Modificar columnas en el listado del admin
        add_filter('manage_marketplace_item_posts_columns', [$this, 'personalizar_columnas_admin']);
        add_action('manage_marketplace_item_posts_custom_column', [$this, 'contenido_columnas_admin'], 10, 2);

        // Shortcodes
        $this->register_shortcodes();

        // AJAX para frontend
        add_action('wp_ajax_marketplace_crear_anuncio', [$this, 'ajax_crear_anuncio']);
        add_action('wp_ajax_nopriv_marketplace_crear_anuncio', [$this, 'ajax_crear_anuncio']);

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();

        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
    }

    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-marketplace-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Marketplace_Dashboard_Tab::get_instance();
        }
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-marketplace-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Marketplace_Frontend_Controller::get_instance();
        }
    }

    /**
     * Garantiza que el frontend controller del Marketplace esté cargado
     * antes de resolver shortcodes frontend desde tabs legacy.
     */
    private static function asegurar_frontend_controller(): void {
        if (!class_exists('Flavor_Marketplace_Frontend_Controller')) {
            $archivo_controller = dirname(__FILE__) . '/frontend/class-marketplace-frontend-controller.php';
            if (file_exists($archivo_controller)) {
                require_once $archivo_controller;
            }
        }

        if (class_exists('Flavor_Marketplace_Frontend_Controller')) {
            Flavor_Marketplace_Frontend_Controller::get_instance();
        }
    }

    /**
     * Obtiene la URL actual para redirects de login en páginas dinámicas.
     */
    private static function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/mi-portal/marketplace/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Registra shortcodes del módulo
     *
     * Nota: Los shortcodes principales (marketplace_catalogo, marketplace_listado,
     * marketplace_mis_anuncios, etc.) se registran en el Frontend Controller.
     * Aquí solo se registra marketplace_formulario con implementación local.
     */
    public function register_shortcodes() {
        // marketplace_listado se registra en el Frontend Controller
        add_shortcode('marketplace_formulario', [$this, 'shortcode_formulario']);
    }

    /**
     * Shortcode para formulario de publicar anuncio
     */
    public function shortcode_formulario($atts) {
        if (!is_user_logged_in()) {
            return '<div class="marketplace-login-required"><p>' .
                   esc_html__('Debes iniciar sesión para publicar un anuncio.', 'flavor-chat-ia') .
                   '</p><a href="' . esc_url(wp_login_url(self::get_current_request_url())) . '" class="btn-login">' .
                   esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a></div>';
        }

        ob_start();
        $this->render_formulario_anuncio();
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de publicar anuncio
     */
    private function render_formulario_anuncio() {
        $categorias = get_terms([
            'taxonomy' => 'marketplace_categoria',
            'hide_empty' => false,
        ]);

        $tipos = [
            'regalo' => __('Regalo', 'flavor-chat-ia'),
            'venta' => __('Venta', 'flavor-chat-ia'),
            'cambio' => __('Cambio', 'flavor-chat-ia'),
            'alquiler' => __('Alquiler', 'flavor-chat-ia'),
        ];
        ?>
        <div class="marketplace-formulario">
            <h2><?php esc_html_e('Publicar Anuncio', 'flavor-chat-ia'); ?></h2>

            <form id="marketplace-form-anuncio" class="marketplace-form">
                <?php wp_nonce_field('marketplace_crear_anuncio', 'marketplace_nonce'); ?>

                <div class="form-group">
                    <label for="anuncio-titulo"><?php esc_html_e('Título', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="anuncio-titulo" name="titulo" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="anuncio-descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?> *</label>
                    <textarea id="anuncio-descripcion" name="descripcion" rows="5" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="anuncio-tipo"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?> *</label>
                        <select id="anuncio-tipo" name="tipo" required>
                            <option value=""><?php esc_html_e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($tipos as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="anuncio-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select id="anuncio-categoria" name="categoria">
                            <option value=""><?php esc_html_e('Sin categoría', 'flavor-chat-ia'); ?></option>
                            <?php if (!is_wp_error($categorias)): ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="grupo-precio">
                    <label for="anuncio-precio"><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="anuncio-precio" name="precio" min="0" step="0.01" placeholder="0.00">
                    <small><?php esc_html_e('Dejar en 0 para regalo o negociable', 'flavor-chat-ia'); ?></small>
                </div>

                <div class="form-group">
                    <label><?php esc_html_e('Imágenes', 'flavor-chat-ia'); ?></label>
                    <div class="marketplace-upload-area" id="upload-area">
                        <input type="file" id="anuncio-imagenes" name="imagenes[]" multiple accept="image/*" style="display:none;">
                        <button type="button" class="btn-upload" onclick="document.getElementById('anuncio-imagenes').click();">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Subir imágenes', 'flavor-chat-ia'); ?>
                        </button>
                        <div id="preview-imagenes" class="preview-grid"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn-publicar">
                        <?php esc_html_e('Publicar Anuncio', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Configuración de páginas de administración para el panel unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'marketplace',
            'label' => __('Marketplace', 'flavor-chat-ia'),
            'icon' => 'dashicons-store',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'marketplace-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                    'badge' => [$this, 'contar_anuncios_pendientes'],
                ],
                [
                    'slug' => 'marketplace-anuncios',
                    'titulo' => __('Anuncios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_productos'],
                ],
                [
                    'slug' => 'marketplace-moderacion',
                    'titulo' => __('Moderación', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_moderacion'],
                    'badge' => [$this, 'contar_anuncios_pendientes'],
                ],
                [
                    'slug' => 'marketplace-categorias',
                    'titulo' => __('Categorías', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_categorias'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_marketplace'],
        ];
    }

    /**
     * Cuenta los anuncios pendientes de moderación
     *
     * @return int
     */
    public function contar_anuncios_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        $contador_pendientes = wp_count_posts('marketplace_item');
        return isset($contador_pendientes->pending) ? $contador_pendientes->pending : 0;
    }

    /**
     * Renderiza el dashboard del módulo en el panel de administración
     */
    public function render_admin_dashboard() {
        $estadisticas = $this->get_estadisticas_marketplace();
        $this->render_page_header(__('Dashboard Marketplace', 'flavor-chat-ia'), [
            [
                'label' => __('Nuevo Anuncio', 'flavor-chat-ia'),
                'url' => admin_url('post-new.php?post_type=marketplace_item'),
                'class' => 'button-primary',
            ],
        ]);
        ?>
        <div class="marketplace-dashboard-stats">
            <div class="stat-card">
                <span class="dashicons dashicons-megaphone"></span>
                <div class="stat-content">
                    <h3><?php echo esc_html($estadisticas['total_anuncios']); ?></h3>
                    <p><?php _e('Anuncios Activos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-clock"></span>
                <div class="stat-content">
                    <h3><?php echo esc_html($estadisticas['pendientes']); ?></h3>
                    <p><?php _e('Pendientes de Moderación', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-category"></span>
                <div class="stat-content">
                    <h3><?php echo esc_html($estadisticas['categorias']); ?></h3>
                    <p><?php _e('Categorías', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-groups"></span>
                <div class="stat-content">
                    <h3><?php echo esc_html($estadisticas['usuarios_activos']); ?></h3>
                    <p><?php _e('Usuarios con Anuncios', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la lista de anuncios en el panel de administración
     */
    public function render_admin_anuncios() {
        $this->render_page_header(__('Gestión de Anuncios', 'flavor-chat-ia'), [
            [
                'label' => __('Nuevo Anuncio', 'flavor-chat-ia'),
                'url' => admin_url('post-new.php?post_type=marketplace_item'),
                'class' => 'button-primary',
            ],
            [
                'label' => __('Ver Todos', 'flavor-chat-ia'),
                'url' => admin_url('edit.php?post_type=marketplace_item'),
                'class' => '',
            ],
        ]);
        ?>
        <div class="marketplace-admin-content">
            <p><?php _e('Administra todos los anuncios del marketplace desde aquí.', 'flavor-chat-ia'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=marketplace_item')); ?>" class="button">
                    <?php _e('Ir al listado de anuncios', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Renderiza la gestión de categorías en el panel de administración
     */
    public function render_admin_categorias() {
        $this->render_page_header(__('Categorías del Marketplace', 'flavor-chat-ia'), [
            [
                'label' => __('Gestionar Categorías', 'flavor-chat-ia'),
                'url' => admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item'),
                'class' => 'button-primary',
            ],
            [
                'label' => __('Tipos de Transacción', 'flavor-chat-ia'),
                'url' => admin_url('edit-tags.php?taxonomy=marketplace_tipo&post_type=marketplace_item'),
                'class' => '',
            ],
        ]);
        ?>
        <div class="marketplace-admin-content">
            <h3><?php _e('Categorías de Productos', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Organiza los anuncios por tipo de producto.', 'flavor-chat-ia'); ?></p>

            <h3><?php _e('Tipos de Transacción', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('Define cómo se realizan las transacciones: Regalo, Venta, Cambio o Alquiler.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
    }

    /**
     * Renderiza el widget del dashboard principal
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_marketplace();
        ?>
        <div class="marketplace-widget">
            <p><strong><?php echo esc_html($estadisticas['total_anuncios']); ?></strong> <?php _e('anuncios activos', 'flavor-chat-ia'); ?></p>
            <?php if ($estadisticas['pendientes'] > 0): ?>
                <p class="warning">
                    <strong><?php echo esc_html($estadisticas['pendientes']); ?></strong>
                    <?php _e('pendientes de moderación', 'flavor-chat-ia'); ?>
                </p>
            <?php endif; ?>
            <a href="<?php echo esc_url($this->admin_page_url('marketplace-dashboard')); ?>" class="button">
                <?php _e('Ver Dashboard', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Obtiene las estadísticas del marketplace
     *
     * @return array
     */
    public function get_estadisticas_marketplace() {
        $contador_posts = wp_count_posts('marketplace_item');
        $total_anuncios = isset($contador_posts->publish) ? $contador_posts->publish : 0;
        $anuncios_pendientes = isset($contador_posts->pending) ? $contador_posts->pending : 0;

        $terminos_categorias = get_terms([
            'taxonomy' => 'marketplace_categoria',
            'hide_empty' => false,
        ]);
        $total_categorias = is_array($terminos_categorias) ? count($terminos_categorias) : 0;

        global $wpdb;
        $total_usuarios_activos = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} WHERE post_type = 'marketplace_item' AND post_status = 'publish'"
        );

        return [
            'total_anuncios' => $total_anuncios,
            'pendientes' => $anuncios_pendientes,
            'categorias' => $total_categorias,
            'usuarios_activos' => $total_usuarios_activos ?: 0,
        ];
    }

    /**
     * Registra el Custom Post Type para anuncios
     */
    public function registrar_custom_post_type() {
        $etiquetas = [
            'name' => __('Anuncios Marketplace', 'flavor-chat-ia'),
            'singular_name' => __('Anuncio', 'flavor-chat-ia'),
            'add_new' => __('Añadir Anuncio', 'flavor-chat-ia'),
            'add_new_item' => __('Añadir Nuevo Anuncio', 'flavor-chat-ia'),
            'edit_item' => __('Editar Anuncio', 'flavor-chat-ia'),
            'new_item' => __('Nuevo Anuncio', 'flavor-chat-ia'),
            'view_item' => __('Ver Anuncio', 'flavor-chat-ia'),
            'search_items' => __('Buscar Anuncios', 'flavor-chat-ia'),
            'not_found' => __('No se encontraron anuncios', 'flavor-chat-ia'),
            'not_found_in_trash' => __('No hay anuncios en la papelera', 'flavor-chat-ia'),
        ];

        $argumentos = [
            'labels' => $etiquetas,
            'public' => true,
            'has_archive' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'thumbnail', 'author', 'comments'],
            'rewrite' => ['slug' => 'marketplace', 'with_front' => false],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type('marketplace_item', $argumentos);
    }

    /**
     * Registra las taxonomías
     */
    public function registrar_taxonomias() {
        // Taxonomía: Tipo de transacción (regalo, venta, cambio, alquiler)
        register_taxonomy('marketplace_tipo', 'marketplace_item', [
            'labels' => [
                'name' => __('Tipo de Transacción', 'flavor-chat-ia'),
                'singular_name' => __('Tipo', 'flavor-chat-ia'),
            ],
            'public' => true,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'marketplace-tipo'],
        ]);

        // Taxonomía: Categoría del producto
        register_taxonomy('marketplace_categoria', 'marketplace_item', [
            'labels' => [
                'name' => __('Categorías', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'marketplace-categoria'],
        ]);

        // Insertar términos por defecto para tipo de transacción
        $tipos_defecto = ['regalo', 'venta', 'cambio', 'alquiler'];
        foreach ($tipos_defecto as $tipo) {
            if (!term_exists($tipo, 'marketplace_tipo')) {
                wp_insert_term(ucfirst($tipo), 'marketplace_tipo', ['slug' => $tipo]);
            }
        }

        // Insertar categorías por defecto
        $categorias_defecto = [
            'Electrónica', 'Muebles', 'Ropa', 'Libros', 'Deportes',
            'Hogar', 'Juguetes', 'Herramientas', 'Otros'
        ];
        foreach ($categorias_defecto as $categoria) {
            if (!term_exists($categoria, 'marketplace_categoria')) {
                wp_insert_term($categoria, 'marketplace_categoria');
            }
        }
    }

    /**
     * Registra los Meta Boxes (Custom Fields)
     */
    public function registrar_meta_boxes() {
        add_meta_box(
            'marketplace_detalles',
            __('Detalles del Anuncio', 'flavor-chat-ia'),
            [$this, 'renderizar_meta_box_detalles'],
            'marketplace_item',
            'normal',
            'high'
        );
    }

    /**
     * Renderiza el contenido del Meta Box
     */
    public function renderizar_meta_box_detalles($post) {
        wp_nonce_field('marketplace_guardar_meta', 'marketplace_meta_nonce');

        $precio = get_post_meta($post->ID, '_marketplace_precio', true);
        $estado_conservacion = get_post_meta($post->ID, '_marketplace_estado', true);
        $ubicacion = get_post_meta($post->ID, '_marketplace_ubicacion', true);
        $contacto_preferido = get_post_meta($post->ID, '_marketplace_contacto', true);
        $fecha_expiracion = get_post_meta($post->ID, '_marketplace_fecha_expiracion', true);
        $intercambio_preferencias = get_post_meta($post->ID, '_marketplace_intercambio_prefs', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="marketplace_precio"><?php _e('Precio', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="marketplace_precio" name="marketplace_precio"
                           value="<?php echo esc_attr($precio); ?>" class="regular-text" />
                    <p class="description"><?php _e('Deja en blanco si es regalo o cambio', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="marketplace_estado"><?php _e('Estado de Conservación', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select id="marketplace_estado" name="marketplace_estado">
                        <option value=""><?php _e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('nuevo', 'flavor-chat-ia'); ?>" <?php selected($estado_conservacion, 'nuevo'); ?>><?php _e('Nuevo', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('como_nuevo', 'flavor-chat-ia'); ?>" <?php selected($estado_conservacion, 'como_nuevo'); ?>><?php _e('Como nuevo', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('buen_estado', 'flavor-chat-ia'); ?>" <?php selected($estado_conservacion, 'buen_estado'); ?>><?php _e('Buen estado', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('usado', 'flavor-chat-ia'); ?>" <?php selected($estado_conservacion, 'usado'); ?>><?php _e('Usado', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('reparar', 'flavor-chat-ia'); ?>" <?php selected($estado_conservacion, 'reparar'); ?>><?php _e('Necesita reparación', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="marketplace_ubicacion"><?php _e('Ubicación', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" id="marketplace_ubicacion" name="marketplace_ubicacion"
                           value="<?php echo esc_attr($ubicacion); ?>" class="regular-text"
                           placeholder="<?php _e('Ciudad o barrio', 'flavor-chat-ia'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="marketplace_contacto"><?php _e('Contacto Preferido', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select id="marketplace_contacto" name="marketplace_contacto">
                        <option value="<?php echo esc_attr__('chat', 'flavor-chat-ia'); ?>" <?php selected($contacto_preferido, 'chat'); ?>><?php _e('Chat interno', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('email', 'flavor-chat-ia'); ?>" <?php selected($contacto_preferido, 'email'); ?>><?php _e('Email', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('whatsapp', 'flavor-chat-ia'); ?>" <?php selected($contacto_preferido, 'whatsapp'); ?>><?php _e('WhatsApp', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('telefono', 'flavor-chat-ia'); ?>" <?php selected($contacto_preferido, 'telefono'); ?>><?php _e('Teléfono', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="marketplace_intercambio_prefs"><?php _e('Preferencias de Intercambio', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <textarea id="marketplace_intercambio_prefs" name="marketplace_intercambio_prefs"
                              rows="3" class="large-text"><?php echo esc_textarea($intercambio_preferencias); ?></textarea>
                    <p class="description"><?php _e('Si es cambio, indica qué te interesaría a cambio', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="marketplace_fecha_expiracion"><?php _e('Fecha de Expiración', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="date" id="marketplace_fecha_expiracion" name="marketplace_fecha_expiracion"
                           value="<?php echo esc_attr($fecha_expiracion); ?>" />
                    <p class="description"><?php _e('Fecha hasta la que estará disponible el anuncio', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guarda los datos del Meta Box
     */
    public function guardar_meta_boxes($post_id) {
        // Verificaciones de seguridad
        if (!isset($_POST['marketplace_meta_nonce']) ||
            !wp_verify_nonce($_POST['marketplace_meta_nonce'], 'marketplace_guardar_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar campos
        $campos_a_guardar = [
            '_marketplace_precio' => 'sanitize_text_field',
            '_marketplace_estado' => 'sanitize_text_field',
            '_marketplace_ubicacion' => 'sanitize_text_field',
            '_marketplace_contacto' => 'sanitize_text_field',
            '_marketplace_fecha_expiracion' => 'sanitize_text_field',
            '_marketplace_intercambio_prefs' => 'sanitize_textarea_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitize) {
            $campo_form = str_replace('_marketplace_', 'marketplace_', $campo);
            if (isset($_POST[$campo_form])) {
                update_post_meta($post_id, $campo, $funcion_sanitize($_POST[$campo_form]));
            }
        }
    }

    /**
     * Personaliza las columnas del admin
     */
    public function personalizar_columnas_admin($columnas) {
        $nuevas_columnas = [
            'cb' => $columnas['cb'],
            'title' => $columnas['title'],
            'marketplace_tipo' => __('Tipo', 'flavor-chat-ia'),
            'marketplace_precio' => __('Precio', 'flavor-chat-ia'),
            'marketplace_estado' => __('Estado', 'flavor-chat-ia'),
            'marketplace_ubicacion' => __('Ubicación', 'flavor-chat-ia'),
            'author' => $columnas['author'],
            'date' => $columnas['date'],
        ];
        return $nuevas_columnas;
    }

    /**
     * Muestra el contenido de las columnas personalizadas
     */
    public function contenido_columnas_admin($columna, $post_id) {
        switch ($columna) {
            case 'marketplace_tipo':
                $tipos = wp_get_post_terms($post_id, 'marketplace_tipo');
                if (!empty($tipos)) {
                    echo esc_html($tipos[0]->name);
                }
                break;

            case 'marketplace_precio':
                $precio = get_post_meta($post_id, '_marketplace_precio', true);
                echo $precio ? esc_html($precio) . ' €' : '—';
                break;

            case 'marketplace_estado':
                $estado = get_post_meta($post_id, '_marketplace_estado', true);
                if ($estado) {
                    $estados_etiquetas = [
                        'nuevo' => __('Nuevo', 'flavor-chat-ia'),
                        'como_nuevo' => __('Como nuevo', 'flavor-chat-ia'),
                        'buen_estado' => __('Buen estado', 'flavor-chat-ia'),
                        'usado' => __('Usado', 'flavor-chat-ia'),
                        'reparar' => __('A reparar', 'flavor-chat-ia'),
                    ];
                    echo esc_html($estados_etiquetas[$estado] ?? $estado);
                }
                break;

            case 'marketplace_ubicacion':
                $ubicacion = get_post_meta($post_id, '_marketplace_ubicacion', true);
                echo $ubicacion ? esc_html($ubicacion) : '—';
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_anuncios' => [
                'description' => 'Buscar anuncios en el marketplace',
                'params' => ['busqueda', 'tipo', 'categoria', 'precio_max', 'ubicacion', 'limite'],
            ],
            'ver_anuncio' => [
                'description' => 'Ver detalles de un anuncio específico',
                'params' => ['anuncio_id'],
            ],
            'publicar_anuncio' => [
                'description' => 'Publicar un nuevo anuncio',
                'params' => ['titulo', 'descripcion', 'tipo', 'categoria', 'precio', 'estado', 'ubicacion'],
            ],
            'mis_anuncios' => [
                'description' => 'Ver mis anuncios publicados',
                'params' => ['estado'],
            ],
            'favoritos' => [
                'description' => 'Ver anuncios guardados en favoritos',
                'params' => [],
            ],
            'categorias' => [
                'description' => 'Explorar categorías del marketplace',
                'params' => [],
            ],
            'contactar_vendedor' => [
                'description' => 'Iniciar contacto con el vendedor de un anuncio',
                'params' => ['anuncio_id', 'mensaje'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'buscar_anuncios',
            'listado' => 'buscar_anuncios',
            'buscar' => 'buscar_anuncios',
            'explorar' => 'buscar_anuncios',
            'detalle' => 'ver_anuncio',
            'ver' => 'ver_anuncio',
            'crear' => 'publicar_anuncio',
            'nuevo' => 'publicar_anuncio',
            'mis_items' => 'mis_anuncios',
            'mis-anuncios' => 'mis_anuncios',
            'foro' => 'foro_anuncio',
            'chat' => 'chat_anuncio',
            'multimedia' => 'multimedia_anuncio',
            'red-social' => 'red_social_anuncio',
            'red_social' => 'red_social_anuncio',
            'favoritos' => 'favoritos',
            'categorias' => 'categorias',
            'contactar' => 'contactar_vendedor',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Ver un anuncio concreto.
     */
    private function action_ver_anuncio($params) {
        $anuncio_id = absint($params['id'] ?? $params['anuncio_id'] ?? 0);

        return [
            'success' => true,
            'html' => do_shortcode('[marketplace_detalle id="' . $anuncio_id . '"]'),
        ];
    }

    /**
     * Acción: Publicar anuncio.
     */
    private function action_publicar_anuncio($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[marketplace_formulario]'),
        ];
    }

    /**
     * Acción: Mis anuncios.
     */
    private function action_mis_anuncios($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[marketplace_mis_anuncios]'),
        ];
    }

    /**
     * Acción: Favoritos.
     */
    private function action_favoritos($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[marketplace_favoritos]'),
        ];
    }

    /**
     * Acción: Categorías.
     */
    private function action_categorias($params) {
        ob_start();
        self::render_tab_categorias();

        return [
            'success' => true,
            'html' => ob_get_clean(),
        ];
    }

    /**
     * Resuelve el anuncio contextual para tabs satélite.
     */
    private function resolve_contextual_anuncio(array $params = []): ?array {
        global $wpdb;

        $anuncio_id = absint(
            $params['anuncio_id']
            ?? $params['id']
            ?? $_GET['anuncio_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$anuncio_id) {
            return null;
        }

        $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_anuncios)) {
            return null;
        }

        $anuncio = $wpdb->get_row($wpdb->prepare(
            "SELECT id, titulo, descripcion FROM {$tabla_anuncios} WHERE id = %d",
            $anuncio_id
        ));

        if (!$anuncio) {
            return null;
        }

        return [
            'id' => (int) $anuncio->id,
            'titulo' => (string) $anuncio->titulo,
            'descripcion' => (string) ($anuncio->descripcion ?? ''),
        ];
    }

    private function action_foro_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio((array) $params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;"><h2>'
            . esc_html__('Foro del anuncio', 'flavor-chat-ia')
            . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . do_shortcode('[flavor_foros_integrado entidad="marketplace_anuncio" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_chat_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio((array) $params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su chat.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este anuncio.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?anuncio_id=' . absint($anuncio['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="marketplace_anuncio" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio((array) $params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver sus archivos.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?anuncio_id=' . absint($anuncio['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="marketplace_anuncio" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio((array) $params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este anuncio.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?anuncio_id=' . absint($anuncio['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="marketplace_anuncio" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    /**
     * Acción: Contactar con vendedor.
     */
    private function action_contactar_vendedor($params) {
        $anuncio_id = absint($params['id'] ?? $params['anuncio_id'] ?? 0);

        return [
            'success' => true,
            'html' => do_shortcode('[marketplace_detalle id="' . $anuncio_id . '"]'),
        ];
    }

    /**
     * Acción: Buscar anuncios
     */
    private function action_buscar_anuncios($params) {
        $argumentos_query = [
            'post_type' => 'marketplace_item',
            'post_status' => 'publish',
            'posts_per_page' => absint($params['limite'] ?? 10),
        ];

        // Búsqueda por texto
        if (!empty($params['busqueda'])) {
            $argumentos_query['s'] = sanitize_text_field($params['busqueda']);
        }

        // Filtrar por taxonomías
        $tax_query = [];
        if (!empty($params['tipo'])) {
            $tax_query[] = [
                'taxonomy' => 'marketplace_tipo',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['tipo']),
            ];
        }
        if (!empty($params['categoria'])) {
            $tax_query[] = [
                'taxonomy' => 'marketplace_categoria',
                'field' => 'slug',
                'terms' => sanitize_text_field($params['categoria']),
            ];
        }
        if (!empty($tax_query)) {
            $argumentos_query['tax_query'] = $tax_query;
        }

        // Filtrar por precio máximo
        if (isset($params['precio_max'])) {
            $argumentos_query['meta_query'] = [
                [
                    'key' => '_marketplace_precio',
                    'value' => floatval($params['precio_max']),
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                ],
            ];
        }

        $query = new WP_Query($argumentos_query);
        $anuncios_formateados = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $tipos = wp_get_post_terms($post_id, 'marketplace_tipo');
                $categorias = wp_get_post_terms($post_id, 'marketplace_categoria');

                $anuncios_formateados[] = [
                    'id' => $post_id,
                    'titulo' => get_the_title(),
                    'descripcion' => wp_trim_words(get_the_content(), 30),
                    'tipo' => !empty($tipos) ? $tipos[0]->name : '',
                    'categoria' => !empty($categorias) ? $categorias[0]->name : '',
                    'precio' => get_post_meta($post_id, '_marketplace_precio', true),
                    'estado' => get_post_meta($post_id, '_marketplace_estado', true),
                    'ubicacion' => get_post_meta($post_id, '_marketplace_ubicacion', true),
                    'imagen' => get_the_post_thumbnail_url($post_id, 'medium'),
                    'url' => get_permalink($post_id),
                    'fecha_publicacion' => get_the_date('Y-m-d'),
                    'autor' => get_the_author(),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'total' => count($anuncios_formateados),
            'anuncios' => $anuncios_formateados,
            'mensaje' => sprintf('Se encontraron %d anuncios.', count($anuncios_formateados)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'marketplace_buscar',
                'description' => 'Busca anuncios en el marketplace (regalo, venta, cambio, alquiler)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de transacción',
                            'enum' => ['regalo', 'venta', 'cambio', 'alquiler'],
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría del producto',
                        ],
                        'precio_max' => [
                            'type' => 'number',
                            'description' => 'Precio máximo',
                        ],
                        'ubicacion' => [
                            'type' => 'string',
                            'description' => 'Ubicación',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Marketplace Comunitario**

Plataforma donde los miembros pueden publicar anuncios de:
- **Regalo**: Dar cosas gratis a otros miembros
- **Venta**: Vender artículos a precio justo
- **Cambio**: Intercambiar objetos con otros miembros
- **Alquiler**: Alquilar temporalmente artículos

Categorías disponibles: Electrónica, Muebles, Ropa, Libros, Deportes, Hogar, Juguetes, Herramientas, etc.

Cada anuncio incluye: título, descripción, fotos, estado de conservación, ubicación y forma de contacto preferida.
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo puedo publicar un anuncio?',
                'respuesta' => 'Puedes publicar un anuncio desde tu panel de usuario. Incluye fotos, descripción detallada y el precio si es venta.',
            ],
            [
                'pregunta' => '¿Es seguro el marketplace?',
                'respuesta' => 'Recomendamos encontrarse en lugares públicos y verificar el estado de los artículos antes de realizar cualquier transacción.',
            ],
        ];
    }

    /**
     * Shortcode para mostrar el listado
     */
    public function shortcode_listado($atributos) {
        $atributos = shortcode_atts([
            'tipo' => '',
            'limite' => 12,
        ], $atributos);

        $resultado = $this->action_buscar_anuncios($atributos);

        if (!$resultado['success']) {
            return '<p>' . esc_html($resultado['error']) . '</p>';
        }

        ob_start();
        ?>
        <div class="marketplace-grid">
            <?php foreach ($resultado['anuncios'] as $anuncio): ?>
                <div class="marketplace-item">
                    <?php if ($anuncio['imagen']): ?>
                        <img src="<?php echo esc_url($anuncio['imagen']); ?>" alt="<?php echo esc_attr($anuncio['titulo']); ?>" />
                    <?php endif; ?>
                    <h3><?php echo esc_html($anuncio['titulo']); ?></h3>
                    <p class="tipo"><?php echo esc_html($anuncio['tipo']); ?></p>
                    <?php if ($anuncio['precio']): ?>
                        <p class="precio"><?php echo esc_html($anuncio['precio']); ?> €</p>
                    <?php endif; ?>
                    <p><?php echo esc_html($anuncio['descripcion']); ?></p>
                    <a href="<?php echo esc_url($anuncio['url']); ?>" class="button"><?php _e('Ver detalles', 'flavor-chat-ia'); ?></a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Marketplace', 'flavor-chat-ia'),
                'description' => __('Sección hero con buscador de anuncios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mercadillo Vecinal', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Compra, vende e intercambia con tus vecinos', 'flavor-chat-ia'),
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'marketplace/hero',
            ],
            'anuncios_grid' => [
                'label' => __('Grid de Anuncios', 'flavor-chat-ia'),
                'description' => __('Listado de anuncios en formato tarjeta', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Anuncios Recientes', 'flavor-chat-ia'),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de anuncio', 'flavor-chat-ia'),
                        'options' => ['todos', 'venta', 'regalo', 'intercambio', 'alquiler'],
                        'default' => 'todos',
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 4,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 12,
                    ],
                ],
                'template' => 'marketplace/anuncios-grid',
            ],
            'categorias' => [
                'label' => __('Categorías Marketplace', 'flavor-chat-ia'),
                'description' => __('Navegación por categorías de productos', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Categorías', 'flavor-chat-ia'),
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar contador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'marketplace/categorias',
            ],
            'cta_publicar' => [
                'label' => __('CTA Publicar Anuncio', 'flavor-chat-ia'),
                'description' => __('Llamada a acción para publicar', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes algo que ofrecer?', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'default' => __('Publica tu anuncio gratis y llega a toda la comunidad', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Publicar Anuncio', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'marketplace/cta-publicar',
            ],
        ];
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('marketplace');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('marketplace');
        if (!$pagina && !get_option('flavor_marketplace_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['marketplace']);
            update_option('flavor_marketplace_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        // Productos publicados
        $total_productos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'marketplace_item' AND post_status = 'publish'"
        );

        $estadisticas['productos'] = [
            'icon' => 'dashicons-cart',
            'valor' => $total_productos,
            'label' => __('Productos', 'flavor-chat-ia'),
            'color' => 'green',
        ];

        $usuario_id = get_current_user_id();
        if ($usuario_id) {
            // Mis anuncios
            $mis_anuncios = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_type = 'marketplace_item'
                 AND post_status = 'publish'
                 AND post_author = %d",
                $usuario_id
            ));

            $estadisticas['mis_anuncios'] = [
                'icon' => 'dashicons-megaphone',
                'valor' => $mis_anuncios,
                'label' => __('Mis anuncios', 'flavor-chat-ia'),
                'color' => $mis_anuncios > 0 ? 'blue' : 'gray',
            ];
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Marketplace', 'flavor-chat-ia'),
                'slug' => 'marketplace',
                'content' => '<h1>' . __('Marketplace Local', 'flavor-chat-ia') . '</h1>
<p>' . __('Compra y vende productos y servicios en tu comunidad', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="marketplace" action="listar_anuncios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Publicar Anuncio', 'flavor-chat-ia'),
                'slug' => 'publicar',
                'content' => '<h1>' . __('Publicar Anuncio', 'flavor-chat-ia') . '</h1>
<p>' . __('Anuncia tu producto o servicio', 'flavor-chat-ia') . '</p>

[flavor_module_form module="marketplace" action="publicar_anuncio"]',
                'parent' => 'marketplace',
            ],
            [
                'title' => __('Mis Anuncios', 'flavor-chat-ia'),
                'slug' => 'mis-anuncios',
                'content' => '<h1>' . __('Mis Anuncios', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="marketplace"]',
                'parent' => 'marketplace',
            ],
            [
                'title' => __('Mis Compras', 'flavor-chat-ia'),
                'slug' => 'mis-compras',
                'content' => '<h1>' . __('Mis Compras', 'flavor-chat-ia') . '</h1>

[flavor_module_listing module="marketplace" action="mis_compras" user_specific="yes"]',
                'parent' => 'marketplace',
            ],
        ];
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Dashboard - página oculta (slug canónico)
        add_submenu_page(
            null,
            __('Dashboard Marketplace', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'marketplace-dashboard',
            [$this, 'render_pagina_dashboard']
        );

        // Alias para compatibilidad con enlaces legacy
        add_submenu_page(
            null,
            __('Dashboard Marketplace', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'marketplace',
            [$this, 'render_pagina_dashboard']
        );

        // Productos - página oculta
        add_submenu_page(
            null,
            __('Productos Marketplace', 'flavor-chat-ia'),
            __('Productos', 'flavor-chat-ia'),
            $capability,
            'marketplace-productos',
            [$this, 'render_pagina_productos']
        );

        // Ventas - página oculta
        add_submenu_page(
            null,
            __('Ventas Marketplace', 'flavor-chat-ia'),
            __('Ventas', 'flavor-chat-ia'),
            $capability,
            'marketplace-ventas',
            [$this, 'render_pagina_ventas']
        );

        // Vendedores - página oculta
        add_submenu_page(
            null,
            __('Vendedores Marketplace', 'flavor-chat-ia'),
            __('Vendedores', 'flavor-chat-ia'),
            $capability,
            'marketplace-vendedores',
            [$this, 'render_pagina_vendedores']
        );

        // Anuncios - página oculta
        add_submenu_page(
            null,
            __('Anuncios Marketplace', 'flavor-chat-ia'),
            __('Anuncios', 'flavor-chat-ia'),
            $capability,
            'marketplace-anuncios',
            [$this, 'render_pagina_productos']
        );

        // Categorías - página oculta
        add_submenu_page(
            null,
            __('Categorías Marketplace', 'flavor-chat-ia'),
            __('Categorías', 'flavor-chat-ia'),
            $capability,
            'marketplace-categorias',
            [$this, 'render_pagina_categorias']
        );

        // Moderación - página oculta
        add_submenu_page(
            null,
            __('Moderación Marketplace', 'flavor-chat-ia'),
            __('Moderación', 'flavor-chat-ia'),
            $capability,
            'marketplace-moderacion',
            [$this, 'render_pagina_moderacion']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Marketplace', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de productos
     */
    public function render_pagina_productos() {
        $views_path = dirname(__FILE__) . '/views/productos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Productos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de ventas
     */
    public function render_pagina_ventas() {
        $views_path = dirname(__FILE__) . '/views/ventas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Ventas', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de vendedores
     */
    public function render_pagina_vendedores() {
        $views_path = dirname(__FILE__) . '/views/vendedores.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Vendedores', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de categorías
     */
    public function render_pagina_categorias() {
        $views_path = dirname(__FILE__) . '/views/categorias.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Categorías', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de moderación
     */
    public function render_pagina_moderacion() {
        $views_path = dirname(__FILE__) . '/views/moderacion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Moderación Marketplace', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * Define toda la configuración necesaria para renderizar
     * las vistas del módulo de forma dinámica.
     *
     * @return array Configuración completa del módulo
     */
    public static function get_renderer_config(): array {
        return [
            // Identidad del módulo
            'module'   => 'marketplace',
            'title'    => __('Marketplace Vecinal', 'flavor-chat-ia'),
            'subtitle' => __('Compra y vende productos entre vecinos', 'flavor-chat-ia'),
            'icon'     => '🛒',
            'color'    => 'success', // Usa variable CSS --flavor-success del tema

            // Base de datos - Tabla principal de anuncios
            'database' => [
                'table'          => 'flavor_marketplace_anuncios',
                'status_field'   => 'estado',
                'exclude_status' => 'eliminado',
                'order_by'       => 'created_at DESC',
                'filter_fields'  => ['estado', 'categoria_id', 'tipo'],
                'user_field'     => 'usuario_id',
            ],

            // Mapeo de campos BD → Vista
            'fields' => [
                'titulo'      => 'titulo',
                'descripcion' => 'descripcion',
                'imagen'      => 'imagen_principal',
                'estado'      => 'estado',
                'tipo'        => 'tipo',
                'categoria'   => 'categoria_id',
                'precio'      => 'precio',
                'user_id'     => 'usuario_id',
                'ubicacion'   => 'ubicacion_texto',
                'condicion'   => 'condicion',
                'fecha'       => 'fecha_publicacion',
            ],

            // Estados con sus propiedades
            'estados' => [
                'publicado'  => ['label' => __('Publicado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'pendiente'  => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '🟡'],
                'vendido'    => ['label' => __('Vendido', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '⚫'],
                'pausado'    => ['label' => __('Pausado', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🔵'],
                'expirado'   => ['label' => __('Expirado', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '🔴'],
                'borrador'   => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '⚪'],
                'rechazado'  => ['label' => __('Rechazado', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
            ],

            // Tipos de anuncio
            'tipos' => [
                'venta'       => ['label' => __('Venta', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🏷️'],
                'compra'      => ['label' => __('Compra', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🛒'],
                'intercambio' => ['label' => __('Intercambio', 'flavor-chat-ia'), 'color' => 'purple', 'icon' => '🔄'],
                'regalo'      => ['label' => __('Regalo', 'flavor-chat-ia'), 'color' => 'pink', 'icon' => '🎁'],
                'alquiler'    => ['label' => __('Alquiler', 'flavor-chat-ia'), 'color' => 'orange', 'icon' => '🏠'],
                'servicio'    => ['label' => __('Servicio', 'flavor-chat-ia'), 'color' => 'cyan', 'icon' => '🔧'],
            ],

            // Estadísticas para stats-grid
            'stats' => [
                ['label' => __('Publicados', 'flavor-chat-ia'), 'icon' => '🟢', 'color' => 'green', 'count_where' => "estado = 'publicado'"],
                ['label' => __('Pendientes', 'flavor-chat-ia'), 'icon' => '🟡', 'color' => 'yellow', 'count_where' => "estado = 'pendiente'"],
                ['label' => __('Vendidos', 'flavor-chat-ia'), 'icon' => '🏆', 'color' => 'gray', 'count_where' => "estado = 'vendido'"],
                ['label' => __('Total', 'flavor-chat-ia'), 'icon' => '📦', 'color' => 'blue', 'count_where' => "estado NOT IN ('eliminado', 'rechazado')"],
            ],

            // Configuración de la card genérica
            'card' => [
                'color'  => 'green',
                'icon'   => '🛒',
                'fields' => [
                    'id'       => 'id',
                    'title'    => 'titulo',
                    'subtitle' => 'descripcion',
                    'image'    => 'imagen_principal',
                    'url'      => 'url',
                ],
                'badge' => [
                    'field'  => 'tipo',
                    'colors' => [
                        'venta'       => 'green',
                        'compra'      => 'blue',
                        'intercambio' => 'purple',
                        'regalo'      => 'pink',
                        'alquiler'    => 'orange',
                        'servicio'    => 'cyan',
                    ],
                ],
                'meta' => [
                    ['icon' => '💰', 'field' => 'precio', 'format' => 'currency'],
                    ['icon' => '📍', 'field' => 'ubicacion_texto'],
                ],
                'data_attrs' => ['estado', 'categoria_id', 'tipo'],
            ],

            // Tabs del módulo con callbacks de renderizado
            'tabs' => [
                'anuncios' => [
                    'label'    => __('Anuncios', 'flavor-chat-ia'),
                    'icon'     => 'dashicons-megaphone',
                    'callback' => [__CLASS__, 'render_tab_anuncios'],
                    'default'  => true,
                ],
                'mis-anuncios' => [
                    'label'    => __('Mis Anuncios', 'flavor-chat-ia'),
                    'icon'     => 'dashicons-welcome-write-blog',
                    'callback' => [__CLASS__, 'render_tab_mis_anuncios'],
                    'requires_login' => true,
                ],
                'publicar' => [
                    'label'    => __('Publicar', 'flavor-chat-ia'),
                    'icon'     => 'dashicons-plus-alt',
                    'callback' => [__CLASS__, 'render_tab_publicar'],
                    'requires_login' => true,
                ],
                'categorias' => [
                    'label'    => __('Categorías', 'flavor-chat-ia'),
                    'icon'     => 'dashicons-category',
                    'callback' => [__CLASS__, 'render_tab_categorias'],
                ],
                'detalle' => [
                    'label'      => __('Detalle', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-visibility',
                    'callback'   => [__CLASS__, 'render_tab_detalle'],
                    'hidden_nav' => true,
                ],
            ],

            // Configuración del archive/listado
            'archive' => [
                'columns'      => 3,
                'per_page'     => 12,
                'filter_field' => 'tipo',
                'filters' => [
                    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
                    ['id' => 'venta', 'label' => __('En venta', 'flavor-chat-ia'), 'icon' => '🏷️'],
                    ['id' => 'regalo', 'label' => __('Regalos', 'flavor-chat-ia'), 'icon' => '🎁'],
                    ['id' => 'intercambio', 'label' => __('Intercambio', 'flavor-chat-ia'), 'icon' => '🔄'],
                    ['id' => 'alquiler', 'label' => __('Alquiler', 'flavor-chat-ia'), 'icon' => '🏠'],
                ],
                'cta_text' => __('Publicar anuncio', 'flavor-chat-ia'),
                'cta_icon' => '📝',
                'cta_url'  => home_url('/mi-portal/marketplace/?tab=publicar'),
                'empty_state' => [
                    'icon'     => '🛒',
                    'title'    => __('No hay anuncios', 'flavor-chat-ia'),
                    'text'     => __('Sé el primero en publicar algo', 'flavor-chat-ia'),
                    'cta_text' => __('Publicar un anuncio', 'flavor-chat-ia'),
                ],
            ],

            // Configuración del single/detalle
            'single' => [
                'meta_fields' => [
                    ['field' => 'precio', 'icon' => '💰', 'format' => 'currency'],
                    ['field' => 'ubicacion_texto', 'icon' => '📍'],
                    ['field' => 'fecha_publicacion', 'icon' => '📅', 'format' => 'date'],
                ],
                'detail_fields' => [
                    ['field' => 'tipo', 'label' => __('Tipo', 'flavor-chat-ia')],
                    ['field' => 'condicion', 'label' => __('Condición', 'flavor-chat-ia')],
                ],
                'actions' => [
                    ['label' => __('Contactar', 'flavor-chat-ia'), 'icon' => '💬', 'action' => 'flavorMarketplace.contactar({id})', 'primary' => true],
                    ['label' => __('Compartir', 'flavor-chat-ia'), 'icon' => '📤', 'action' => 'flavorMarketplace.compartir({id})'],
                    ['label' => __('Favorito', 'flavor-chat-ia'), 'icon' => '❤️', 'action' => 'flavorMarketplace.favorito({id})'],
                ],
                'sidebar' => [
                    ['type' => 'author'],
                    ['type' => 'related', 'title' => __('Productos similares', 'flavor-chat-ia'), 'limit' => 3],
                ],
            ],

            // Configuración del formulario
            'form' => [
                'title' => __('Publicar anuncio', 'flavor-chat-ia'),
                'submit_label' => __('Publicar', 'flavor-chat-ia'),
                'ajax_action' => 'marketplace_crear_anuncio',
                'fields' => [
                    [
                        'name'        => 'titulo',
                        'type'        => 'text',
                        'label'       => __('Título del anuncio', 'flavor-chat-ia'),
                        'placeholder' => __('¿Qué vendes o buscas?', 'flavor-chat-ia'),
                        'required'    => true,
                    ],
                    [
                        'name'        => 'descripcion',
                        'type'        => 'textarea',
                        'label'       => __('Descripción', 'flavor-chat-ia'),
                        'placeholder' => __('Describe el producto o servicio...', 'flavor-chat-ia'),
                        'required'    => true,
                    ],
                    [
                        'name'    => 'tipo',
                        'type'    => 'select',
                        'label'   => __('Tipo de anuncio', 'flavor-chat-ia'),
                        'options' => [
                            'venta'       => __('En venta', 'flavor-chat-ia'),
                            'regalo'      => __('Regalo', 'flavor-chat-ia'),
                            'intercambio' => __('Intercambio', 'flavor-chat-ia'),
                            'alquiler'    => __('Alquiler', 'flavor-chat-ia'),
                            'servicio'    => __('Servicio', 'flavor-chat-ia'),
                            'compra'      => __('Busco comprar', 'flavor-chat-ia'),
                        ],
                        'required' => true,
                    ],
                    [
                        'name'        => 'precio',
                        'type'        => 'number',
                        'label'       => __('Precio (€)', 'flavor-chat-ia'),
                        'placeholder' => '0.00',
                        'min'         => 0,
                        'step'        => '0.01',
                    ],
                    [
                        'name'    => 'condicion',
                        'type'    => 'select',
                        'label'   => __('Condición', 'flavor-chat-ia'),
                        'options' => [
                            'nuevo'       => __('Nuevo', 'flavor-chat-ia'),
                            'como_nuevo'  => __('Como nuevo', 'flavor-chat-ia'),
                            'buen_estado' => __('Buen estado', 'flavor-chat-ia'),
                            'usado'       => __('Usado', 'flavor-chat-ia'),
                            'para_piezas' => __('Para piezas', 'flavor-chat-ia'),
                        ],
                    ],
                    [
                        'name'  => 'imagen',
                        'type'  => 'image',
                        'label' => __('Foto del producto', 'flavor-chat-ia'),
                        'help'  => __('Añade una foto para atraer más compradores', 'flavor-chat-ia'),
                    ],
                    [
                        'name'        => 'ubicacion_texto',
                        'type'        => 'text',
                        'label'       => __('Ubicación', 'flavor-chat-ia'),
                        'placeholder' => __('Barrio o zona', 'flavor-chat-ia'),
                    ],
                ],
            ],

            // Configuración del dashboard
            'dashboard' => [
                'show_header' => true,
                'stats_layout' => 'horizontal',
                'header_actions' => [
                    ['label' => __('Publicar', 'flavor-chat-ia'), 'icon' => '📝', 'url' => home_url('/mi-portal/marketplace/?tab=publicar'), 'primary' => true],
                    ['label' => __('Mis anuncios', 'flavor-chat-ia'), 'icon' => '📋', 'url' => home_url('/mi-portal/marketplace/?tab=mis-anuncios')],
                ],
                'quick_actions' => [
                    ['title' => __('Publicar', 'flavor-chat-ia'), 'icon' => '📝', 'color' => 'green', 'url' => home_url('/mi-portal/marketplace/?tab=publicar')],
                    ['title' => __('Mis anuncios', 'flavor-chat-ia'), 'icon' => '📋', 'color' => 'blue', 'url' => home_url('/mi-portal/marketplace/?tab=mis-anuncios')],
                    ['title' => __('Categorías', 'flavor-chat-ia'), 'icon' => '📁', 'color' => 'purple', 'url' => home_url('/mi-portal/marketplace/?tab=categorias')],
                    ['title' => __('Explorar', 'flavor-chat-ia'), 'icon' => '🔍', 'color' => 'cyan', 'url' => home_url('/mi-portal/marketplace/')],
                ],
                'widgets' => [
                    [
                        'type'  => 'list',
                        'title' => __('Mis últimos anuncios', 'flavor-chat-ia'),
                        'icon'  => '📋',
                        'limit' => 5,
                    ],
                ],
                'show_recent' => true,
                'recent_title' => __('Últimos anuncios del barrio', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Renderiza el tab de listado de anuncios
     *
     * @return void
     */
    public static function render_tab_anuncios(): void {
        self::asegurar_frontend_controller();
        if (class_exists('Flavor_Marketplace_Frontend_Controller')) {
            echo Flavor_Marketplace_Frontend_Controller::get_instance()->shortcode_catalogo([
                'limite' => 12,
                'mostrar_filtros' => 'si',
            ]);
            return;
        }

        echo do_shortcode('[marketplace_listado limite="12" mostrar_filtros="si"]');
    }

    /**
     * Renderiza el tab de mis anuncios
     *
     * @return void
     */
    public static function render_tab_mis_anuncios(): void {
        self::asegurar_frontend_controller();
        if (class_exists('Flavor_Marketplace_Frontend_Controller')) {
            echo Flavor_Marketplace_Frontend_Controller::get_instance()->shortcode_mis_anuncios([]);
            return;
        }

        echo do_shortcode('[marketplace_mis_anuncios]');
    }

    /**
     * Renderiza el tab de publicar anuncio
     *
     * @return void
     */
    public static function render_tab_publicar(): void {
        if (!is_user_logged_in()) {
            echo '<div class="text-center py-12 bg-yellow-50 rounded-2xl">';
            echo '<div class="text-5xl mb-4">🔐</div>';
            echo '<h3 class="text-xl font-semibold text-gray-700 mb-2">' . esc_html__('Inicia sesión', 'flavor-chat-ia') . '</h3>';
            echo '<p class="text-gray-500 mb-4">' . esc_html__('Necesitas iniciar sesión para publicar un anuncio', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(wp_login_url(self::get_current_request_url())) . '" class="inline-block bg-yellow-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-yellow-600">' . esc_html__('Iniciar Sesión', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla_categorias = $wpdb->prefix . 'flavor_marketplace_categorias';
        $categorias = $wpdb->get_results("SELECT id, nombre, icono FROM {$tabla_categorias} WHERE activa = 1 ORDER BY orden ASC");

        // Verificar si estamos editando
        $editando_id = isset($_GET['editar']) ? absint($_GET['editar']) : 0;
        $anuncio_editar = null;
        if ($editando_id > 0) {
            $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';
            $anuncio_editar = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_anuncios} WHERE id = %d AND usuario_id = %d",
                $editando_id,
                get_current_user_id()
            ));
        }

        $titulo_formulario = $anuncio_editar ? __('Editar Anuncio', 'flavor-chat-ia') : __('Publicar Anuncio', 'flavor-chat-ia');
        ?>
        <div class="flavor-marketplace-publicar max-w-2xl mx-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-2xl p-6 mb-6 shadow-lg text-center">
                <h2 class="text-2xl font-bold mb-1">📝 <?php echo esc_html($titulo_formulario); ?></h2>
                <p class="text-green-100"><?php echo esc_html__('Completa los datos de tu producto o servicio', 'flavor-chat-ia'); ?></p>
            </div>

            <!-- Formulario -->
            <form id="marketplace-form-publicar" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                  data-ajax-action="marketplace_guardar_anuncio">
                <?php wp_nonce_field('marketplace_publicar_anuncio', 'marketplace_nonce'); ?>
                <?php if ($anuncio_editar): ?>
                <input type="hidden" name="anuncio_id" value="<?php echo esc_attr($anuncio_editar->id); ?>">
                <?php endif; ?>

                <!-- Título -->
                <div>
                    <label for="anuncio-titulo" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Título del anuncio', 'flavor-chat-ia'); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="anuncio-titulo" name="titulo" required maxlength="255"
                           value="<?php echo esc_attr($anuncio_editar->titulo ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Ej: iPhone 12 Pro en perfecto estado', 'flavor-chat-ia'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Tipo y Categoría -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="anuncio-tipo" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Tipo de anuncio', 'flavor-chat-ia'); ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="anuncio-tipo" name="tipo" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                            <option value="venta" <?php selected($anuncio_editar->tipo ?? '', 'venta'); ?>>🏷️ <?php echo esc_html__('Venta', 'flavor-chat-ia'); ?></option>
                            <option value="regalo" <?php selected($anuncio_editar->tipo ?? '', 'regalo'); ?>>🎁 <?php echo esc_html__('Regalo', 'flavor-chat-ia'); ?></option>
                            <option value="intercambio" <?php selected($anuncio_editar->tipo ?? '', 'intercambio'); ?>>🔄 <?php echo esc_html__('Intercambio', 'flavor-chat-ia'); ?></option>
                            <option value="alquiler" <?php selected($anuncio_editar->tipo ?? '', 'alquiler'); ?>>🏠 <?php echo esc_html__('Alquiler', 'flavor-chat-ia'); ?></option>
                            <option value="servicio" <?php selected($anuncio_editar->tipo ?? '', 'servicio'); ?>>🔧 <?php echo esc_html__('Servicio', 'flavor-chat-ia'); ?></option>
                            <option value="compra" <?php selected($anuncio_editar->tipo ?? '', 'compra'); ?>>🔍 <?php echo esc_html__('Busco comprar', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label for="anuncio-categoria" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?>
                        </label>
                        <select id="anuncio-categoria" name="categoria_id"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value=""><?php echo esc_html__('Sin categoría', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo esc_attr($categoria->id); ?>" <?php selected($anuncio_editar->categoria_id ?? '', $categoria->id); ?>>
                                <?php echo esc_html($categoria->nombre); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Descripción -->
                <div>
                    <label for="anuncio-descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Descripción', 'flavor-chat-ia'); ?> <span class="text-red-500">*</span>
                    </label>
                    <textarea id="anuncio-descripcion" name="descripcion" required rows="4"
                              placeholder="<?php echo esc_attr__('Describe tu producto o servicio con detalle...', 'flavor-chat-ia'); ?>"
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"><?php echo esc_textarea($anuncio_editar->descripcion ?? ''); ?></textarea>
                </div>

                <!-- Precio y Condición -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="anuncio-precio" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Precio (€)', 'flavor-chat-ia'); ?>
                        </label>
                        <input type="number" id="anuncio-precio" name="precio" min="0" step="0.01"
                               value="<?php echo esc_attr($anuncio_editar->precio ?? ''); ?>"
                               placeholder="0.00"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <label class="flex items-center gap-2 mt-2 text-sm text-gray-600">
                            <input type="checkbox" name="precio_negociable" value="1" <?php checked($anuncio_editar->precio_negociable ?? 0, 1); ?>
                                   class="rounded text-green-500 focus:ring-green-500">
                            <?php echo esc_html__('Precio negociable', 'flavor-chat-ia'); ?>
                        </label>
                    </div>

                    <div>
                        <label for="anuncio-condicion" class="block text-sm font-medium text-gray-700 mb-2">
                            <?php echo esc_html__('Condición', 'flavor-chat-ia'); ?>
                        </label>
                        <select id="anuncio-condicion" name="condicion"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="buen_estado" <?php selected($anuncio_editar->condicion ?? 'buen_estado', 'buen_estado'); ?>><?php echo esc_html__('Buen estado', 'flavor-chat-ia'); ?></option>
                            <option value="nuevo" <?php selected($anuncio_editar->condicion ?? '', 'nuevo'); ?>><?php echo esc_html__('Nuevo', 'flavor-chat-ia'); ?></option>
                            <option value="como_nuevo" <?php selected($anuncio_editar->condicion ?? '', 'como_nuevo'); ?>><?php echo esc_html__('Como nuevo', 'flavor-chat-ia'); ?></option>
                            <option value="usado" <?php selected($anuncio_editar->condicion ?? '', 'usado'); ?>><?php echo esc_html__('Usado', 'flavor-chat-ia'); ?></option>
                            <option value="para_piezas" <?php selected($anuncio_editar->condicion ?? '', 'para_piezas'); ?>><?php echo esc_html__('Para piezas', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Ubicación -->
                <div>
                    <label for="anuncio-ubicacion" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?>
                    </label>
                    <input type="text" id="anuncio-ubicacion" name="ubicacion_texto" maxlength="500"
                           value="<?php echo esc_attr($anuncio_editar->ubicacion_texto ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Ej: Centro, Madrid', 'flavor-chat-ia'); ?>"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Imagen -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__('Foto del producto', 'flavor-chat-ia'); ?>
                    </label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-green-400 transition-colors cursor-pointer"
                         id="zona-upload-imagen">
                        <input type="file" id="anuncio-imagen" name="imagen" accept="image/*" class="hidden">
                        <div class="text-4xl mb-2">📷</div>
                        <p class="text-gray-500 text-sm"><?php echo esc_html__('Haz clic o arrastra una imagen', 'flavor-chat-ia'); ?></p>
                        <?php if (!empty($anuncio_editar->imagen_principal)): ?>
                        <img src="<?php echo esc_url($anuncio_editar->imagen_principal); ?>" class="mt-4 max-h-32 mx-auto rounded-lg" id="preview-imagen">
                        <?php else: ?>
                        <img src="" class="mt-4 max-h-32 mx-auto rounded-lg hidden" id="preview-imagen">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Opciones de contacto -->
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="mostrar_email" value="1" <?php checked($anuncio_editar->mostrar_email ?? 1, 1); ?>
                               class="rounded text-green-500 focus:ring-green-500">
                        <?php echo esc_html__('Mostrar email', 'flavor-chat-ia'); ?>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="mostrar_telefono" value="1" <?php checked($anuncio_editar->mostrar_telefono ?? 0, 1); ?>
                               class="rounded text-green-500 focus:ring-green-500">
                        <?php echo esc_html__('Mostrar teléfono', 'flavor-chat-ia'); ?>
                    </label>
                </div>

                <!-- Botones -->
                <div class="flex gap-4 pt-4 border-t border-gray-100">
                    <button type="submit"
                            class="flex-1 bg-green-500 text-white py-3 px-6 rounded-xl font-semibold hover:bg-green-600 transition-colors">
                        <?php echo $anuncio_editar ? esc_html__('Guardar Cambios', 'flavor-chat-ia') : esc_html__('Publicar Anuncio', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(remove_query_arg(['tab', 'editar'])); ?>"
                       class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                        <?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Preview de imagen
            $('#zona-upload-imagen').on('click', function() {
                $('#anuncio-imagen').click();
            });

            $('#anuncio-imagen').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview-imagen').attr('src', e.target.result).removeClass('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza el tab de categorías
     *
     * @return void
     */
    public static function render_tab_categorias(): void {
        $categorias = get_terms([
            'taxonomy' => 'marketplace_categoria',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($categorias)) {
            $categorias = [];
        }

        $base_anuncios_url = home_url('/mi-portal/marketplace/anuncios/');
        $total_anuncios = (int) wp_count_posts('marketplace_item')->publish;
        ?>
        <div class="flavor-marketplace-categorias">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-2xl p-6 mb-6 shadow-lg">
                <h2 class="text-2xl font-bold mb-1">📁 <?php echo esc_html__('Categorías', 'flavor-chat-ia'); ?></h2>
                <p class="text-purple-100"><?php echo esc_html__('Explora anuncios por categoría', 'flavor-chat-ia'); ?></p>
            </div>

            <?php if (empty($categorias)): ?>
            <div class="text-center py-12 bg-gray-50 rounded-2xl">
                <div class="text-5xl mb-4">📁</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('Sin categorías', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500"><?php echo esc_html__('No hay categorías configuradas todavía', 'flavor-chat-ia'); ?></p>
            </div>
            <?php else: ?>
            <!-- Grid de categorías -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Todos -->
                <a href="<?php echo esc_url($base_anuncios_url); ?>"
                   class="bg-gradient-to-br from-gray-700 to-gray-800 text-white rounded-2xl p-6 text-center hover:shadow-lg transition-all group">
                    <div class="text-4xl mb-3">🏪</div>
                    <h3 class="font-semibold mb-1"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></h3>
                    <p class="text-gray-300 text-sm"><?php echo esc_html($total_anuncios); ?> <?php echo esc_html__('anuncios', 'flavor-chat-ia'); ?></p>
                </a>

                <?php foreach ($categorias as $categoria):
                    $term_link = add_query_arg('categoria', $categoria->slug, $base_anuncios_url);
                ?>
                <a href="<?php echo esc_url($term_link); ?>"
                   class="bg-white rounded-2xl p-6 text-center shadow-sm border border-gray-100 hover:shadow-lg hover:border-purple-200 transition-all group">
                    <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">
                        <span class="dashicons dashicons-category" style="font-size: 2.5rem; width: 2.5rem; height: 2.5rem;"></span>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html($categoria->name); ?></h3>
                    <p class="text-gray-500 text-sm"><?php echo esc_html((int) $categoria->count); ?> <?php echo esc_html__('anuncios', 'flavor-chat-ia'); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab oculto de detalle dentro del portal.
     */
    public static function render_tab_detalle(): void {
        self::asegurar_frontend_controller();
        $anuncio_id = absint($_GET['anuncio_id'] ?? $_GET['id'] ?? 0);
        echo do_shortcode('[marketplace_detalle id="' . $anuncio_id . '"]');
    }

    /**
     * Handler AJAX para guardar anuncio
     */
    public function ajax_guardar_anuncio() {
        check_ajax_referer('marketplace_publicar_anuncio', 'marketplace_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';
        $usuario_id = get_current_user_id();
        $usuario = wp_get_current_user();

        // Validar datos requeridos
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'venta');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son obligatorios', 'flavor-chat-ia')]);
        }

        // Preparar datos
        $datos_anuncio = [
            'titulo'           => $titulo,
            'slug'             => sanitize_title($titulo) . '-' . uniqid(),
            'descripcion'      => $descripcion,
            'tipo'             => $tipo,
            'categoria_id'     => !empty($_POST['categoria_id']) ? absint($_POST['categoria_id']) : null,
            'precio'           => !empty($_POST['precio']) ? floatval($_POST['precio']) : null,
            'precio_negociable'=> !empty($_POST['precio_negociable']) ? 1 : 0,
            'es_gratuito'      => $tipo === 'regalo' ? 1 : 0,
            'condicion'        => sanitize_text_field($_POST['condicion'] ?? 'buen_estado'),
            'usuario_id'       => $usuario_id,
            'usuario_nombre'   => $usuario->display_name,
            'usuario_email'    => $usuario->user_email,
            'ubicacion_texto'  => sanitize_text_field($_POST['ubicacion_texto'] ?? ''),
            'mostrar_email'    => !empty($_POST['mostrar_email']) ? 1 : 0,
            'mostrar_telefono' => !empty($_POST['mostrar_telefono']) ? 1 : 0,
            'estado'           => 'pendiente',
            'updated_at'       => current_time('mysql'),
        ];

        // Verificar si es edición o creación
        $anuncio_id = isset($_POST['anuncio_id']) ? absint($_POST['anuncio_id']) : 0;

        if ($anuncio_id > 0) {
            // Verificar que el anuncio pertenece al usuario
            $anuncio_existente = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_anuncios} WHERE id = %d AND usuario_id = %d",
                $anuncio_id,
                $usuario_id
            ));

            if (!$anuncio_existente) {
                wp_send_json_error(['message' => __('No tienes permisos para editar este anuncio', 'flavor-chat-ia')]);
            }

            $wpdb->update($tabla_anuncios, $datos_anuncio, ['id' => $anuncio_id], null, ['%d']);
            $mensaje_exito = __('Anuncio actualizado correctamente', 'flavor-chat-ia');
        } else {
            // Nuevo anuncio
            $datos_anuncio['fecha_publicacion'] = current_time('mysql');
            $datos_anuncio['created_at'] = current_time('mysql');

            $wpdb->insert($tabla_anuncios, $datos_anuncio);
            $anuncio_id = $wpdb->insert_id;
            $mensaje_exito = __('Anuncio publicado correctamente', 'flavor-chat-ia');
        }

        if (!$anuncio_id) {
            wp_send_json_error(['message' => __('Error al guardar el anuncio', 'flavor-chat-ia')]);
        }

        // Procesar imagen si se subió
        if (!empty($_FILES['imagen']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload('imagen', 0);
            if (!is_wp_error($attachment_id)) {
                $imagen_url = wp_get_attachment_url($attachment_id);
                $wpdb->update($tabla_anuncios, ['imagen_principal' => $imagen_url], ['id' => $anuncio_id]);
            }
        }

        wp_send_json_success([
            'message' => $mensaje_exito,
            'redirect' => add_query_arg('tab', 'mis-anuncios', remove_query_arg(['editar', 'tab'])),
        ]);
    }
}
