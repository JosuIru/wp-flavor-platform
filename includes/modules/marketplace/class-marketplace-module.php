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
                   '</p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn-login">' .
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
                    'callback' => [$this, 'render_admin_dashboard'],
                    'badge' => [$this, 'contar_anuncios_pendientes'],
                ],
                [
                    'slug' => 'marketplace-anuncios',
                    'titulo' => __('Anuncios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_anuncios'],
                ],
                [
                    'slug' => 'marketplace-categorias',
                    'titulo' => __('Categorías', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_categorias'],
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

        // Dashboard - página oculta
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

        // Categorías - página oculta
        add_submenu_page(
            null,
            __('Categorías Marketplace', 'flavor-chat-ia'),
            __('Categorías', 'flavor-chat-ia'),
            $capability,
            'marketplace-categorias',
            [$this, 'render_pagina_categorias']
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
}
