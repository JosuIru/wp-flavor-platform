<?php
/**
 * Módulo Grupos de Consumo para Chat IA
 *
 * Gestión de pedidos colectivos, productores locales y repartos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Grupos de Consumo
 * Permite organizar pedidos colectivos de productores locales
 */
class Flavor_Chat_Grupos_Consumo_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'grupos_consumo';
        $this->name = __('Grupos de Consumo', 'flavor-chat-ia');
        $this->description = __('Gestión de pedidos colectivos, productores locales y distribución comunitaria.', 'flavor-chat-ia');

        parent::__construct();

        // Cargar clases auxiliares
        $this->cargar_clases_auxiliares();

        // Cargar API REST para móviles
        $this->cargar_api_rest();
    }

    /**
     * Carga las clases auxiliares del módulo
     */
    private function cargar_clases_auxiliares() {
        $base_dir = dirname(__FILE__);

        // Clases principales
        $clases = [
            'class-gc-consumidor-manager.php',
            'class-gc-subscriptions.php',
            'class-gc-dashboard-tab.php',
            'class-gc-notification-channels.php',
            'class-gc-export.php',
            'class-gc-pwa.php',
        ];

        foreach ($clases as $clase) {
            $ruta_archivo = $base_dir . '/' . $clase;
            if (file_exists($ruta_archivo)) {
                require_once $ruta_archivo;
            }
        }

        // Cargar frontend controller
        $frontend_controller = $base_dir . '/frontend/class-gc-frontend-controller.php';
        if (file_exists($frontend_controller)) {
            require_once $frontend_controller;
        }

        // Inicializar singletons
        if (class_exists('Flavor_GC_Consumidor_Manager')) {
            Flavor_GC_Consumidor_Manager::get_instance();
        }
        if (class_exists('Flavor_GC_Subscriptions')) {
            Flavor_GC_Subscriptions::get_instance();
        }
        if (class_exists('Flavor_GC_Dashboard_Tab')) {
            Flavor_GC_Dashboard_Tab::get_instance();
        }
        if (class_exists('Flavor_GC_Notification_Channels')) {
            Flavor_GC_Notification_Channels::get_instance();
        }
        if (class_exists('Flavor_GC_Export')) {
            Flavor_GC_Export::get_instance();
        }
        if (class_exists('Flavor_GC_PWA')) {
            Flavor_GC_PWA::get_instance();
        }
        if (class_exists('Flavor_GC_Frontend_Controller')) {
            Flavor_GC_Frontend_Controller::get_instance();
        }
    }

    /**
     * Carga la API REST
     */
    private function cargar_api_rest() {
        $api_file = dirname(__FILE__) . '/class-grupos-consumo-api.php';
        if (file_exists($api_file)) {
            require_once $api_file;
            // Inicializar API en el contexto REST apropiado
            add_action('rest_api_init', function() {
                if (class_exists('Flavor_Grupos_Consumo_API')) {
                    Flavor_Grupos_Consumo_API::get_instance();
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'dias_anticipacion_pedido' => 7,
            'hora_cierre_pedidos' => '23:59',
            'permitir_modificar_pedido' => true,
            'horas_limite_modificacion' => 24,
            'porcentaje_gestion' => 5, // % para gastos del grupo
            'requiere_aprobacion_productores' => true,
            'notificar_nuevos_productos' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Crear tablas si no existen
        $this->crear_tablas_si_no_existen();

        // Registrar Custom Post Types
        add_action('init', [$this, 'registrar_custom_post_types']);
        add_action('init', [$this, 'registrar_taxonomias']);

        // Meta Boxes
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_gc_productor', [$this, 'guardar_meta_productor']);
        add_action('save_post_gc_producto', [$this, 'guardar_meta_producto']);
        add_action('save_post_gc_ciclo', [$this, 'guardar_meta_ciclo']);

        // Columnas admin
        add_filter('manage_gc_ciclo_posts_columns', [$this, 'columnas_ciclo']);
        add_action('manage_gc_ciclo_posts_custom_column', [$this, 'contenido_columnas_ciclo'], 10, 2);
        add_filter('manage_gc_producto_posts_columns', [$this, 'columnas_producto']);
        add_action('manage_gc_producto_posts_custom_column', [$this, 'contenido_columnas_producto'], 10, 2);

        // Estados personalizados para ciclos
        add_action('init', [$this, 'registrar_estados_ciclo']);

        // Shortcodes
        add_shortcode('gc_ciclo_actual', [$this, 'shortcode_ciclo_actual']);
        add_shortcode('gc_productos', [$this, 'shortcode_productos']);
        add_shortcode('gc_mi_pedido', [$this, 'shortcode_mi_pedido']);
        add_shortcode('gc_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('gc_carrito', [$this, 'shortcode_carrito']);
        add_shortcode('gc_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('gc_historial', [$this, 'shortcode_historial']);
        add_shortcode('gc_suscripciones', [$this, 'shortcode_suscripciones']);
        add_shortcode('gc_mi_cesta', [$this, 'shortcode_mi_cesta']);

        // Páginas de admin
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // AJAX
        add_action('wp_ajax_gc_hacer_pedido', [$this, 'ajax_hacer_pedido']);
        add_action('wp_ajax_gc_modificar_pedido', [$this, 'ajax_modificar_pedido']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Cron para cerrar ciclos automáticamente
        add_action('gc_cerrar_ciclos_automatico', [$this, 'cerrar_ciclos_automatico']);
        if (!wp_next_scheduled('gc_cerrar_ciclos_automatico')) {
            wp_schedule_event(time(), 'hourly', 'gc_cerrar_ciclos_automatico');
        }
    }

    /**
     * Crea las tablas necesarias si no existen
     */
    private function crear_tablas_si_no_existen() {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $charset_collate = $wpdb->get_charset_collate();

        // Verificar si la tabla ya existe
        if (Flavor_Chat_Helpers::tabla_existe($tabla_pedidos)) {
            return;
        }

        $sql = "CREATE TABLE $tabla_pedidos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            producto_id bigint(20) unsigned NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            precio_unitario decimal(10,2) NOT NULL,
            fecha_pedido datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(20) DEFAULT 'pendiente',
            notas text,
            PRIMARY KEY  (id),
            KEY ciclo_id (ciclo_id),
            KEY usuario_id (usuario_id),
            KEY producto_id (producto_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Método enqueue_assets está al final del archivo

    /**
     * Registra Custom Post Types
     */
    public function registrar_custom_post_types() {
        // CPT: Productores
        register_post_type('gc_productor', [
            'labels' => [
                'name' => __('Productores', 'flavor-chat-ia'),
                'singular_name' => __('Productor', 'flavor-chat-ia'),
                'add_new' => __('Añadir Productor', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nuevo Productor', 'flavor-chat-ia'),
                'edit_item' => __('Editar Productor', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-carrot',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'productores'],
        ]);

        // CPT: Productos
        register_post_type('gc_producto', [
            'labels' => [
                'name' => __('Productos', 'flavor-chat-ia'),
                'singular_name' => __('Producto', 'flavor-chat-ia'),
                'add_new' => __('Añadir Producto', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-carrot',
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'productos-grupo-consumo'],
        ]);

        // CPT: Ciclos de pedido
        register_post_type('gc_ciclo', [
            'labels' => [
                'name' => __('Ciclos de Pedido', 'flavor-chat-ia'),
                'singular_name' => __('Ciclo', 'flavor-chat-ia'),
                'add_new' => __('Crear Ciclo', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'ciclos-pedido'],
        ]);

        // CPT: Grupos de Consumo
        register_post_type('gc_grupo', [
            'labels' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'singular_name' => __('Grupo', 'flavor-chat-ia'),
                'add_new' => __('Crear Grupo', 'flavor-chat-ia'),
                'add_new_item' => __('Crear Nuevo Grupo', 'flavor-chat-ia'),
                'edit_item' => __('Editar Grupo', 'flavor-chat-ia'),
                'view_item' => __('Ver Grupo', 'flavor-chat-ia'),
                'all_items' => __('Todos los Grupos', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => 'edit.php?post_type=gc_productor',
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
            'capability_type' => 'post',
            'rewrite' => ['slug' => 'grupos-consumo'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    public function registrar_taxonomias() {
        // Taxonomía: Categoría de productos
        register_taxonomy('gc_categoria', 'gc_producto', [
            'labels' => [
                'name' => __('Categorías', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'categoria-producto'],
        ]);

        // Términos por defecto
        $categorias_defecto = [
            'frutas' => __('Frutas', 'flavor-chat-ia'),
            'verduras' => __('Verduras', 'flavor-chat-ia'),
            'lacteos' => __('Lácteos', 'flavor-chat-ia'),
            'carne' => __('Carne', 'flavor-chat-ia'),
            'pescado' => __('Pescado', 'flavor-chat-ia'),
            'pan' => __('Pan y Cereales', 'flavor-chat-ia'),
            'conservas' => __('Conservas', 'flavor-chat-ia'),
            'bebidas' => __('Bebidas', 'flavor-chat-ia'),
            'otros' => __('Otros', 'flavor-chat-ia'),
        ];

        foreach ($categorias_defecto as $slug => $nombre) {
            if (!term_exists($slug, 'gc_categoria')) {
                wp_insert_term($nombre, 'gc_categoria', ['slug' => $slug]);
            }
        }
    }

    /**
     * Registra estados personalizados para ciclos
     */
    public function registrar_estados_ciclo() {
        register_post_status('gc_abierto', [
            'label' => __('Abierto', 'flavor-chat-ia'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Abierto <span class="count">(%s)</span>', 'Abiertos <span class="count">(%s)</span>', 'flavor-chat-ia'),
        ]);

        register_post_status('gc_cerrado', [
            'label' => __('Cerrado', 'flavor-chat-ia'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Cerrado <span class="count">(%s)</span>', 'Cerrados <span class="count">(%s)</span>', 'flavor-chat-ia'),
        ]);

        register_post_status('gc_entregado', [
            'label' => __('Entregado', 'flavor-chat-ia'),
            'public' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Entregado <span class="count">(%s)</span>', 'Entregados <span class="count">(%s)</span>', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Registra meta boxes
     */
    public function registrar_meta_boxes() {
        // Meta box para productores
        add_meta_box(
            'gc_productor_info',
            __('Información del Productor', 'flavor-chat-ia'),
            [$this, 'render_meta_productor'],
            'gc_productor',
            'normal',
            'high'
        );

        // Meta box para productos
        add_meta_box(
            'gc_producto_info',
            __('Información del Producto', 'flavor-chat-ia'),
            [$this, 'render_meta_producto'],
            'gc_producto',
            'normal',
            'high'
        );

        // Meta box para ciclos
        add_meta_box(
            'gc_ciclo_info',
            __('Información del Ciclo', 'flavor-chat-ia'),
            [$this, 'render_meta_ciclo'],
            'gc_ciclo',
            'normal',
            'high'
        );

        // Meta box resumen pedidos del ciclo
        add_meta_box(
            'gc_ciclo_pedidos',
            __('Resumen de Pedidos', 'flavor-chat-ia'),
            [$this, 'render_meta_ciclo_pedidos'],
            'gc_ciclo',
            'side',
            'default'
        );
    }

    /**
     * Renderiza meta box del productor
     */
    public function render_meta_productor($post) {
        wp_nonce_field('gc_productor_meta', 'gc_productor_nonce');

        $contacto_nombre = get_post_meta($post->ID, '_gc_contacto_nombre', true);
        $contacto_telefono = get_post_meta($post->ID, '_gc_contacto_telefono', true);
        $contacto_email = get_post_meta($post->ID, '_gc_contacto_email', true);
        $ubicacion_ciudad = get_post_meta($post->ID, '_gc_ubicacion', true);
        $certificacion_ecologica = get_post_meta($post->ID, '_gc_certificacion_eco', true);
        $numero_certificado = get_post_meta($post->ID, '_gc_numero_certificado', true);
        $metodos_produccion = get_post_meta($post->ID, '_gc_metodos_produccion', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_contacto_nombre"><?php _e('Nombre de Contacto', 'flavor-chat-ia'); ?></label></th>
                <td><input type="text" id="gc_contacto_nombre" name="gc_contacto_nombre"
                           value="<?php echo esc_attr($contacto_nombre); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_contacto_telefono"><?php _e('Teléfono', 'flavor-chat-ia'); ?></label></th>
                <td><input type="tel" id="gc_contacto_telefono" name="gc_contacto_telefono"
                           value="<?php echo esc_attr($contacto_telefono); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_contacto_email"><?php _e('Email', 'flavor-chat-ia'); ?></label></th>
                <td><input type="email" id="gc_contacto_email" name="gc_contacto_email"
                           value="<?php echo esc_attr($contacto_email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_ubicacion"><?php _e('Ubicación', 'flavor-chat-ia'); ?></label></th>
                <td><input type="text" id="gc_ubicacion" name="gc_ubicacion"
                           value="<?php echo esc_attr($ubicacion_ciudad); ?>" class="regular-text"
                           placeholder="<?php _e('Ciudad, Provincia', 'flavor-chat-ia'); ?>" /></td>
            </tr>
            <tr>
                <th><label for="gc_certificacion_eco"><?php _e('Certificación Ecológica', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="gc_certificacion_eco" name="gc_certificacion_eco"
                               value="1" <?php checked($certificacion_ecologica, '1'); ?> />
                        <?php _e('Productor certificado ecológico', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="gc_numero_certificado"><?php _e('Nº Certificado', 'flavor-chat-ia'); ?></label></th>
                <td><input type="text" id="gc_numero_certificado" name="gc_numero_certificado"
                           value="<?php echo esc_attr($numero_certificado); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_metodos_produccion"><?php _e('Métodos de Producción', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <textarea id="gc_metodos_produccion" name="gc_metodos_produccion"
                              rows="4" class="large-text"><?php echo esc_textarea($metodos_produccion); ?></textarea>
                    <p class="description"><?php _e('Describe los métodos y prácticas de producción', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box del producto
     */
    public function render_meta_producto($post) {
        wp_nonce_field('gc_producto_meta', 'gc_producto_nonce');

        $productor_id = get_post_meta($post->ID, '_gc_productor_id', true);
        $precio = get_post_meta($post->ID, '_gc_precio', true);
        $unidad = get_post_meta($post->ID, '_gc_unidad', true);
        $cantidad_minima = get_post_meta($post->ID, '_gc_cantidad_minima', true);
        $stock_disponible = get_post_meta($post->ID, '_gc_stock', true);
        $temporada = get_post_meta($post->ID, '_gc_temporada', true);
        $origen = get_post_meta($post->ID, '_gc_origen', true);

        // Obtener productores
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_productor_id"><?php _e('Productor', 'flavor-chat-ia'); ?> *</label></th>
                <td>
                    <select id="gc_productor_id" name="gc_productor_id" class="regular-text" required>
                        <option value=""><?php _e('Selecciona un productor', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($productores as $productor): ?>
                            <option value="<?php echo $productor->ID; ?>"
                                    <?php selected($productor_id, $productor->ID); ?>>
                                <?php echo esc_html($productor->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gc_precio"><?php _e('Precio', 'flavor-chat-ia'); ?> *</label></th>
                <td>
                    <input type="number" step="0.01" id="gc_precio" name="gc_precio"
                           value="<?php echo esc_attr($precio); ?>" required /> €
                </td>
            </tr>
            <tr>
                <th><label for="gc_unidad"><?php _e('Unidad', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select id="gc_unidad" name="gc_unidad">
                        <option value="kg" <?php selected($unidad, 'kg'); ?>>Kg</option>
                        <option value="g" <?php selected($unidad, 'g'); ?>>g</option>
                        <option value="l" <?php selected($unidad, 'l'); ?>>L</option>
                        <option value="unidad" <?php selected($unidad, 'unidad'); ?>><?php _e('Unidad', 'flavor-chat-ia'); ?></option>
                        <option value="caja" <?php selected($unidad, 'caja'); ?>><?php _e('Caja', 'flavor-chat-ia'); ?></option>
                        <option value="ramo" <?php selected($unidad, 'ramo'); ?>><?php _e('Ramo', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gc_cantidad_minima"><?php _e('Cantidad Mínima', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" step="0.1" id="gc_cantidad_minima" name="gc_cantidad_minima"
                           value="<?php echo esc_attr($cantidad_minima ?: 1); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="gc_stock"><?php _e('Stock Disponible', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" step="0.1" id="gc_stock" name="gc_stock"
                           value="<?php echo esc_attr($stock_disponible); ?>" />
                    <p class="description"><?php _e('Dejar vacío si es ilimitado', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="gc_temporada"><?php _e('Temporada', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" id="gc_temporada" name="gc_temporada"
                           value="<?php echo esc_attr($temporada); ?>" class="regular-text"
                           placeholder="<?php _e('Ej: Primavera-Verano', 'flavor-chat-ia'); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="gc_origen"><?php _e('Origen', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="text" id="gc_origen" name="gc_origen"
                           value="<?php echo esc_attr($origen); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza meta box del ciclo
     */
    public function render_meta_ciclo($post) {
        wp_nonce_field('gc_ciclo_meta', 'gc_ciclo_nonce');

        $fecha_inicio = get_post_meta($post->ID, '_gc_fecha_inicio', true);
        $fecha_cierre = get_post_meta($post->ID, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($post->ID, '_gc_fecha_entrega', true);
        $lugar_entrega = get_post_meta($post->ID, '_gc_lugar_entrega', true);
        $hora_entrega = get_post_meta($post->ID, '_gc_hora_entrega', true);
        $notas = get_post_meta($post->ID, '_gc_notas', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="gc_fecha_inicio"><?php _e('Fecha Apertura', 'flavor-chat-ia'); ?> *</label></th>
                <td><input type="datetime-local" id="gc_fecha_inicio" name="gc_fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_fecha_cierre"><?php _e('Fecha Cierre', 'flavor-chat-ia'); ?> *</label></th>
                <td><input type="datetime-local" id="gc_fecha_cierre" name="gc_fecha_cierre"
                           value="<?php echo esc_attr($fecha_cierre); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_fecha_entrega"><?php _e('Fecha Entrega', 'flavor-chat-ia'); ?> *</label></th>
                <td><input type="date" id="gc_fecha_entrega" name="gc_fecha_entrega"
                           value="<?php echo esc_attr($fecha_entrega); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="gc_hora_entrega"><?php _e('Hora Entrega', 'flavor-chat-ia'); ?></label></th>
                <td><input type="time" id="gc_hora_entrega" name="gc_hora_entrega"
                           value="<?php echo esc_attr($hora_entrega); ?>" /></td>
            </tr>
            <tr>
                <th><label for="gc_lugar_entrega"><?php _e('Lugar de Entrega', 'flavor-chat-ia'); ?></label></th>
                <td><input type="text" id="gc_lugar_entrega" name="gc_lugar_entrega"
                           value="<?php echo esc_attr($lugar_entrega); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="gc_notas"><?php _e('Notas', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <textarea id="gc_notas" name="gc_notas" rows="4"
                              class="large-text"><?php echo esc_textarea($notas); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderiza resumen de pedidos del ciclo
     */
    public function render_meta_ciclo_pedidos($post) {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Total pedidos
        $total_pedidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_pedidos WHERE ciclo_id = %d",
            $post->ID
        ));

        // Total importe
        $total_importe = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(precio_unitario * cantidad) FROM $tabla_pedidos WHERE ciclo_id = %d",
            $post->ID
        ));

        // Productos más pedidos
        $productos_top = $wpdb->get_results($wpdb->prepare(
            "SELECT producto_id, SUM(cantidad) as total_cantidad
            FROM $tabla_pedidos
            WHERE ciclo_id = %d
            GROUP BY producto_id
            ORDER BY total_cantidad DESC
            LIMIT 5",
            $post->ID
        ));

        echo '<div class="gc-resumen-pedidos">';
        echo '<p><strong>' . __('Total Pedidos:', 'flavor-chat-ia') . '</strong> ' . intval($total_pedidos) . '</p>';
        echo '<p><strong>' . __('Importe Total:', 'flavor-chat-ia') . '</strong> ' . number_format($total_importe, 2) . ' €</p>';

        if (!empty($productos_top)) {
            echo '<h4>' . __('Productos Más Pedidos:', 'flavor-chat-ia') . '</h4>';
            echo '<ul>';
            foreach ($productos_top as $prod) {
                $producto = get_post($prod->producto_id);
                if ($producto) {
                    echo '<li>' . esc_html($producto->post_title) . ': ' . floatval($prod->total_cantidad) . '</li>';
                }
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    /**
     * Guarda meta del productor
     */
    public function guardar_meta_productor($post_id) {
        if (!isset($_POST['gc_productor_nonce']) ||
            !wp_verify_nonce($_POST['gc_productor_nonce'], 'gc_productor_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_a_guardar = [
            '_gc_contacto_nombre' => 'sanitize_text_field',
            '_gc_contacto_telefono' => 'sanitize_text_field',
            '_gc_contacto_email' => 'sanitize_email',
            '_gc_ubicacion' => 'sanitize_text_field',
            '_gc_certificacion_eco' => 'sanitize_text_field',
            '_gc_numero_certificado' => 'sanitize_text_field',
            '_gc_metodos_produccion' => 'sanitize_textarea_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                update_post_meta($post_id, $campo, $funcion_sanitizar($_POST[$campo_form]));
            }
        }
    }

    /**
     * Guarda meta del producto
     */
    public function guardar_meta_producto($post_id) {
        if (!isset($_POST['gc_producto_nonce']) ||
            !wp_verify_nonce($_POST['gc_producto_nonce'], 'gc_producto_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_a_guardar = [
            '_gc_productor_id' => 'absint',
            '_gc_precio' => 'floatval',
            '_gc_unidad' => 'sanitize_text_field',
            '_gc_cantidad_minima' => 'floatval',
            '_gc_stock' => 'floatval',
            '_gc_temporada' => 'sanitize_text_field',
            '_gc_origen' => 'sanitize_text_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                $valor = $_POST[$campo_form];
                if ($funcion_sanitizar === 'absint') {
                    $valor = absint($valor);
                } elseif ($funcion_sanitizar === 'floatval') {
                    $valor = floatval($valor);
                } else {
                    $valor = $funcion_sanitizar($valor);
                }
                update_post_meta($post_id, $campo, $valor);
            }
        }
    }

    /**
     * Guarda meta del ciclo
     */
    public function guardar_meta_ciclo($post_id) {
        if (!isset($_POST['gc_ciclo_nonce']) ||
            !wp_verify_nonce($_POST['gc_ciclo_nonce'], 'gc_ciclo_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $campos_a_guardar = [
            '_gc_fecha_inicio' => 'sanitize_text_field',
            '_gc_fecha_cierre' => 'sanitize_text_field',
            '_gc_fecha_entrega' => 'sanitize_text_field',
            '_gc_lugar_entrega' => 'sanitize_text_field',
            '_gc_hora_entrega' => 'sanitize_text_field',
            '_gc_notas' => 'sanitize_textarea_field',
        ];

        foreach ($campos_a_guardar as $campo => $funcion_sanitizar) {
            $campo_form = str_replace('_gc_', 'gc_', $campo);
            if (isset($_POST[$campo_form])) {
                update_post_meta($post_id, $campo, $funcion_sanitizar($_POST[$campo_form]));
            }
        }
    }

    /**
     * Columnas personalizadas para ciclos
     */
    public function columnas_ciclo($columnas) {
        $nuevas_columnas = [
            'cb' => $columnas['cb'],
            'title' => $columnas['title'],
            'gc_fechas' => __('Fechas', 'flavor-chat-ia'),
            'gc_estado_ciclo' => __('Estado', 'flavor-chat-ia'),
            'gc_total_pedidos' => __('Pedidos', 'flavor-chat-ia'),
            'date' => $columnas['date'],
        ];
        return $nuevas_columnas;
    }

    /**
     * Contenido columnas ciclo
     */
    public function contenido_columnas_ciclo($columna, $post_id) {
        switch ($columna) {
            case 'gc_fechas':
                $fecha_cierre = get_post_meta($post_id, '_gc_fecha_cierre', true);
                $fecha_entrega = get_post_meta($post_id, '_gc_fecha_entrega', true);
                echo '<strong>' . __('Cierre:', 'flavor-chat-ia') . '</strong> ' .
                     esc_html(date('d/m/Y H:i', strtotime($fecha_cierre))) . '<br>';
                echo '<strong>' . __('Entrega:', 'flavor-chat-ia') . '</strong> ' .
                     esc_html(date('d/m/Y', strtotime($fecha_entrega)));
                break;

            case 'gc_estado_ciclo':
                $estado = get_post_status($post_id);
                $etiquetas = [
                    'gc_abierto' => '<span style="color: green;">⬤ ' . __('Abierto', 'flavor-chat-ia') . '</span>',
                    'gc_cerrado' => '<span style="color: orange;">⬤ ' . __('Cerrado', 'flavor-chat-ia') . '</span>',
                    'gc_entregado' => '<span style="color: blue;">⬤ ' . __('Entregado', 'flavor-chat-ia') . '</span>',
                ];
                echo $etiquetas[$estado] ?? $estado;
                break;

            case 'gc_total_pedidos':
                global $wpdb;
                $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
                $total = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_pedidos WHERE ciclo_id = %d",
                    $post_id
                ));
                echo intval($total);
                break;
        }
    }

    /**
     * Columnas producto
     */
    public function columnas_producto($columnas) {
        $nuevas_columnas = [
            'cb' => $columnas['cb'],
            'title' => $columnas['title'],
            'gc_productor' => __('Productor', 'flavor-chat-ia'),
            'gc_precio_producto' => __('Precio', 'flavor-chat-ia'),
            'gc_stock_producto' => __('Stock', 'flavor-chat-ia'),
            'date' => $columnas['date'],
        ];
        return $nuevas_columnas;
    }

    /**
     * Contenido columnas producto
     */
    public function contenido_columnas_producto($columna, $post_id) {
        switch ($columna) {
            case 'gc_productor':
                $productor_id = get_post_meta($post_id, '_gc_productor_id', true);
                if ($productor_id) {
                    $productor = get_post($productor_id);
                    echo $productor ? esc_html($productor->post_title) : '—';
                }
                break;

            case 'gc_precio_producto':
                $precio = get_post_meta($post_id, '_gc_precio', true);
                $unidad = get_post_meta($post_id, '_gc_unidad', true);
                echo $precio ? number_format($precio, 2) . ' €/' . esc_html($unidad) : '—';
                break;

            case 'gc_stock_producto':
                $stock = get_post_meta($post_id, '_gc_stock', true);
                echo $stock ? esc_html($stock) : __('Ilimitado', 'flavor-chat-ia');
                break;
        }
    }

    /**
     * Cierra ciclos automáticamente cuando llega la fecha
     */
    public function cerrar_ciclos_automatico() {
        $ciclos_abiertos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => -1,
        ]);

        $fecha_actual = current_time('timestamp');

        foreach ($ciclos_abiertos as $ciclo) {
            $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
            if ($fecha_cierre && strtotime($fecha_cierre) <= $fecha_actual) {
                wp_update_post([
                    'ID' => $ciclo->ID,
                    'post_status' => 'gc_cerrado',
                ]);

                // Notificar a coordinadores
                do_action('gc_ciclo_cerrado', $ciclo->ID);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_productos' => [
                'description' => 'Listar productos disponibles',
                'params' => ['categoria', 'productor_id', 'limite'],
            ],
            'ciclo_actual' => [
                'description' => 'Obtener información del ciclo actual',
                'params' => [],
            ],
            'hacer_pedido' => [
                'description' => 'Realizar un pedido en el ciclo actual',
                'params' => ['productos'], // [{producto_id, cantidad}, ...]
            ],
            'ver_mi_pedido' => [
                'description' => 'Ver el pedido del usuario en el ciclo actual',
                'params' => [],
            ],
            'modificar_pedido' => [
                'description' => 'Modificar un pedido existente',
                'params' => ['productos'],
            ],
            'buscar_productor' => [
                'description' => 'Buscar productores',
                'params' => ['busqueda', 'certificacion_eco'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    /**
     * Acción: Listar productos
     */
    private function action_listar_productos($parametros) {
        $args_query = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => absint($parametros['limite'] ?? 20),
        ];

        if (!empty($parametros['categoria'])) {
            $args_query['tax_query'] = [
                [
                    'taxonomy' => 'gc_categoria',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($parametros['categoria']),
                ],
            ];
        }

        if (!empty($parametros['productor_id'])) {
            $args_query['meta_query'] = [
                [
                    'key' => '_gc_productor_id',
                    'value' => absint($parametros['productor_id']),
                ],
            ];
        }

        $query = new WP_Query($args_query);
        $productos_formateados = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $productor_id = get_post_meta($post_id, '_gc_productor_id', true);
                $productor = get_post($productor_id);

                $productos_formateados[] = [
                    'id' => $post_id,
                    'nombre' => get_the_title(),
                    'descripcion' => wp_trim_words(get_the_content(), 20),
                    'precio' => floatval(get_post_meta($post_id, '_gc_precio', true)),
                    'unidad' => get_post_meta($post_id, '_gc_unidad', true),
                    'stock' => get_post_meta($post_id, '_gc_stock', true),
                    'productor' => $productor ? $productor->post_title : '',
                    'imagen' => get_the_post_thumbnail_url($post_id, 'medium'),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'total' => count($productos_formateados),
            'productos' => $productos_formateados,
        ];
    }

    /**
     * Acción: Ciclo actual
     */
    private function action_ciclo_actual($parametros) {
        $ciclo_actual = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($ciclo_actual)) {
            return [
                'success' => false,
                'error' => __('No hay ningún ciclo abierto actualmente.', 'flavor-chat-ia'),
            ];
        }

        $ciclo = $ciclo_actual[0];
        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
        $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
        $lugar_entrega = get_post_meta($ciclo->ID, '_gc_lugar_entrega', true);

        return [
            'success' => true,
            'ciclo' => [
                'id' => $ciclo->ID,
                'nombre' => $ciclo->post_title,
                'fecha_cierre' => $fecha_cierre,
                'fecha_entrega' => $fecha_entrega,
                'lugar_entrega' => $lugar_entrega,
                'tiempo_restante' => human_time_diff(current_time('timestamp'), strtotime($fecha_cierre)),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'gc_listar_productos',
                'description' => 'Lista los productos disponibles del grupo de consumo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría de productos',
                            'enum' => ['frutas', 'verduras', 'lacteos', 'carne', 'pescado', 'pan', 'conservas', 'bebidas', 'otros'],
                        ],
                        'productor_id' => [
                            'type' => 'integer',
                            'description' => 'ID del productor',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de productos',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'gc_ciclo_actual',
                'description' => 'Obtiene información del ciclo de pedidos actual',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Grupos de Consumo**

Un grupo de consumo es una asociación de consumidores que se organizan para comprar directamente a productores locales.

**Funcionamiento:**
1. **Ciclos de Pedido**: Períodos (normalmente semanales o quincenales) donde se abren pedidos
2. **Productos Locales**: De productores cercanos, priorizando ecológicos
3. **Compra Colectiva**: Se juntan pedidos para conseguir mejores precios
4. **Repartos**: En un lugar y fecha acordados

**Ventajas:**
- Productos frescos y de temporada
- Apoyo a productores locales
- Precios justos para productor y consumidor
- Trazabilidad completa
- Reducción de intermediarios
- Menor huella ecológica

**Ciclo de Pedido:**
1. Se abre el ciclo con fechas de cierre y entrega
2. Los miembros hacen pedidos online
3. Se cierra el ciclo automáticamente
4. Se consolidan pedidos por productor
5. Entrega en el punto acordado

**Categorías de productos**: Frutas, Verduras, Lácteos, Carne, Pescado, Pan, Conservas, Bebidas, etc.
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo funciona el grupo de consumo?',
                'respuesta' => 'Se abren ciclos de pedido periódicos donde puedes comprar productos directamente de productores locales. Los pedidos se recogen en un punto acordado.',
            ],
            [
                'pregunta' => '¿Cuándo puedo hacer pedidos?',
                'respuesta' => 'Durante el ciclo abierto. Consulta las fechas de apertura y cierre del ciclo actual.',
            ],
            [
                'pregunta' => '¿Los productos son ecológicos?',
                'respuesta' => 'Priorizamos productos ecológicos certificados, aunque también trabajamos con productores locales de agricultura sostenible.',
            ],
            [
                'pregunta' => '¿Puedo modificar mi pedido?',
                'respuesta' => 'Sí, hasta 24 horas antes del cierre del ciclo puedes modificar tu pedido.',
            ],
        ];
    }

    /**
     * Shortcode: Ciclo actual
     */
    public function shortcode_ciclo_actual($atributos) {
        $resultado = $this->action_ciclo_actual([]);

        if (!$resultado['success']) {
            return '<p class="gc-aviso">' . esc_html($resultado['error']) . '</p>';
        }

        $ciclo = $resultado['ciclo'];
        ob_start();
        ?>
        <div class="gc-ciclo-actual">
            <h3><?php echo esc_html($ciclo['nombre']); ?></h3>
            <p><strong><?php _e('Cierra:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($ciclo['fecha_cierre']); ?></p>
            <p><strong><?php _e('Entrega:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($ciclo['fecha_entrega']); ?></p>
            <?php if ($ciclo['lugar_entrega']): ?>
                <p><strong><?php _e('Lugar:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($ciclo['lugar_entrega']); ?></p>
            <?php endif; ?>
            <p class="gc-tiempo-restante"><?php printf(__('Quedan %s para cerrar el ciclo', 'flavor-chat-ia'), $ciclo['tiempo_restante']); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Productos
     */
    public function shortcode_productos($atributos) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
        ], $atributos);

        $resultado = $this->action_listar_productos($atributos);

        if (!$resultado['success']) {
            return '<p>' . esc_html($resultado['error']) . '</p>';
        }

        ob_start();
        ?>
        <div class="gc-productos-grid">
            <?php foreach ($resultado['productos'] as $producto): ?>
                <div class="gc-producto-card">
                    <?php if ($producto['imagen']): ?>
                        <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['nombre']); ?>" />
                    <?php endif; ?>
                    <h4><?php echo esc_html($producto['nombre']); ?></h4>
                    <p class="gc-productor"><?php echo esc_html($producto['productor']); ?></p>
                    <p class="gc-precio"><?php echo number_format($producto['precio'], 2); ?> € / <?php echo esc_html($producto['unidad']); ?></p>
                    <button class="gc-anadir-pedido" data-producto-id="<?php echo $producto['id']; ?>">
                        <?php _e('Añadir al pedido', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi pedido
     */
    public function shortcode_mi_pedido() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tu pedido.', 'flavor-chat-ia') . '</p>';
        }

        $resultado = $this->action_ver_mi_pedido([]);

        // Implementar visualización del pedido
        return '<div class="gc-mi-pedido"><!-- Pedido del usuario --></div>';
    }

    /**
     * AJAX: Hacer pedido
     */
    public function ajax_hacer_pedido() {
        check_ajax_referer('gc_pedido_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $productos = isset($_POST['productos']) ? json_decode(stripslashes($_POST['productos']), true) : [];

        if (empty($productos)) {
            wp_send_json_error(['mensaje' => __('No hay productos en el pedido.', 'flavor-chat-ia')]);
        }

        $resultado = $this->action_hacer_pedido(['productos' => $productos]);

        if ($resultado['success']) {
            wp_send_json_success(['mensaje' => $resultado['mensaje'] ?? __('Pedido realizado correctamente.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['mensaje' => $resultado['error']]);
        }
    }

    /**
     * AJAX: Modificar pedido
     */
    public function ajax_modificar_pedido() {
        check_ajax_referer('gc_pedido_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $productos = isset($_POST['productos']) ? json_decode(stripslashes($_POST['productos']), true) : [];

        if (empty($productos)) {
            wp_send_json_error(['mensaje' => __('No hay productos para modificar.', 'flavor-chat-ia')]);
        }

        $resultado = $this->action_modificar_pedido(['productos' => $productos]);

        if ($resultado['success']) {
            wp_send_json_success(['mensaje' => $resultado['mensaje'] ?? __('Pedido modificado correctamente.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['mensaje' => $resultado['error']]);
        }
    }

    /**
     * Acción: Ver mi pedido
     */
    private function action_ver_mi_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $items_pedido = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_pedidos WHERE ciclo_id = %d AND usuario_id = %d",
            $ciclo_id,
            $usuario_id
        ));

        if (empty($items_pedido)) {
            return [
                'success' => false,
                'error' => __('No tienes pedidos en el ciclo actual.', 'flavor-chat-ia'),
            ];
        }

        $pedido_formateado = [];
        $total = 0;

        foreach ($items_pedido as $item) {
            $producto = get_post($item->producto_id);
            $subtotal = $item->precio_unitario * $item->cantidad;
            $total += $subtotal;

            $pedido_formateado[] = [
                'producto' => $producto ? $producto->post_title : '',
                'cantidad' => floatval($item->cantidad),
                'precio_unitario' => floatval($item->precio_unitario),
                'subtotal' => $subtotal,
            ];
        }

        return [
            'success' => true,
            'pedido' => $pedido_formateado,
            'total' => $total,
        ];
    }

    /**
     * Acción: Hacer pedido
     */
    private function action_hacer_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();
        $productos = $parametros['productos'] ?? [];

        if (empty($productos)) {
            return [
                'success' => false,
                'error' => __('No hay productos en el pedido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Verificar si ya tiene pedido en este ciclo
        $pedido_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_pedidos WHERE ciclo_id = %d AND usuario_id = %d",
            $ciclo_id,
            $usuario_id
        ));

        if ($pedido_existente > 0) {
            return [
                'success' => false,
                'error' => __('Ya tienes un pedido en este ciclo. Puedes modificarlo.', 'flavor-chat-ia'),
            ];
        }

        // Insertar productos
        $total_importe = 0;
        foreach ($productos as $producto) {
            $producto_id = absint($producto['producto_id']);
            $cantidad = floatval($producto['cantidad']);

            $precio = get_post_meta($producto_id, '_gc_precio', true);

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $usuario_id,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s']
            );

            $total_importe += $precio * $cantidad;
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Pedido realizado correctamente. Total: %.2f €', 'flavor-chat-ia'),
                $total_importe
            ),
            'total' => $total_importe,
        ];
    }

    /**
     * Acción: Modificar pedido
     */
    private function action_modificar_pedido($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión.', 'flavor-chat-ia'),
            ];
        }

        // Obtener ciclo actual
        $resultado_ciclo = $this->action_ciclo_actual([]);
        if (!$resultado_ciclo['success']) {
            return $resultado_ciclo;
        }

        $ciclo_id = $resultado_ciclo['ciclo']['id'];
        $usuario_id = get_current_user_id();
        $productos = $parametros['productos'] ?? [];

        if (empty($productos)) {
            return [
                'success' => false,
                'error' => __('No hay productos para modificar.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si puede modificar (24 horas antes del cierre)
        $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $tiempo_restante = strtotime($fecha_cierre) - current_time('timestamp');
        $horas_restantes = $tiempo_restante / 3600;

        $limite_modificacion = $this->get_setting('horas_limite_modificacion', 24);

        if ($horas_restantes < $limite_modificacion && !$this->get_setting('permitir_modificar_pedido', true)) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('No se puede modificar el pedido. Límite: %d horas antes del cierre.', 'flavor-chat-ia'),
                    $limite_modificacion
                ),
            ];
        }

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Eliminar pedido anterior
        $wpdb->delete(
            $tabla_pedidos,
            [
                'ciclo_id' => $ciclo_id,
                'usuario_id' => $usuario_id,
            ],
            ['%d', '%d']
        );

        // Insertar nuevo pedido
        $total_importe = 0;
        foreach ($productos as $producto) {
            $producto_id = absint($producto['producto_id']);
            $cantidad = floatval($producto['cantidad']);

            $precio = get_post_meta($producto_id, '_gc_precio', true);

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $usuario_id,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s']
            );

            $total_importe += $precio * $cantidad;
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Pedido modificado correctamente. Total: %.2f €', 'flavor-chat-ia'),
                $total_importe
            ),
            'total' => $total_importe,
        ];
    }

    /**
     * Acción: Buscar productor
     */
    private function action_buscar_productor($parametros) {
        $args = [
            'post_type' => 'gc_productor',
            'post_status' => 'publish',
            'posts_per_page' => 20,
        ];

        if (!empty($parametros['busqueda'])) {
            $args['s'] = sanitize_text_field($parametros['busqueda']);
        }

        if (!empty($parametros['certificacion_eco'])) {
            $args['meta_query'] = [
                [
                    'key' => '_gc_certificacion_eco',
                    'value' => '1',
                ],
            ];
        }

        $query = new WP_Query($args);
        $productores = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $productores[] = [
                    'id' => $post_id,
                    'nombre' => get_the_title(),
                    'descripcion' => wp_trim_words(get_the_content(), 30),
                    'ubicacion' => get_post_meta($post_id, '_gc_ubicacion', true),
                    'certificacion_eco' => get_post_meta($post_id, '_gc_certificacion_eco', true) == '1',
                    'contacto' => get_post_meta($post_id, '_gc_contacto_email', true),
                ];
            }
            wp_reset_postdata();
        }

        return [
            'success' => true,
            'total' => count($productores),
            'productores' => $productores,
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Sección hero con buscador de grupos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Grupos de Consumo', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Consume local, apoya a productores cercanos', 'flavor-chat-ia'),
                    ],
                    'mostrar_buscador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar buscador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'grupos-consumo/hero',
            ],
            'grupos_grid' => [
                'label' => __('Grid de Grupos', 'flavor-chat-ia'),
                'description' => __('Listado de grupos de consumo activos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Grupos Activos', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'grupos-consumo/grupos-grid',
            ],
            'productores' => [
                'label' => __('Productores Locales', 'flavor-chat-ia'),
                'description' => __('Listado de productores asociados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-store',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestros Productores', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 8,
                    ],
                ],
                'template' => 'grupos-consumo/productores',
            ],
            'como_funciona' => [
                'label' => __('Cómo Funciona', 'flavor-chat-ia'),
                'description' => __('Pasos para unirse a un grupo', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'grupos-consumo/como-funciona',
            ],
            'proximo_pedido' => [
                'label' => __('Próximo Pedido', 'flavor-chat-ia'),
                'description' => __('Información del próximo pedido colectivo', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Próximo Pedido', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'grupos-consumo/proximo-pedido',
            ],
        ];
    }

    // ========================================
    // Nuevos Shortcodes
    // ========================================

    /**
     * Shortcode: Catálogo de productos con filtros
     */
    public function shortcode_catalogo($atributos) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'productor' => '',
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'true',
        ], $atributos);

        $mostrar_filtros = filter_var($atributos['mostrar_filtros'], FILTER_VALIDATE_BOOLEAN);

        // Obtener categorías para filtros
        $categorias = get_terms([
            'taxonomy' => 'gc_categoria',
            'hide_empty' => true,
        ]);

        // Obtener productores para filtros
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Obtener productos
        $resultado = $this->action_listar_productos([
            'categoria' => $atributos['categoria'],
            'productor_id' => $atributos['productor'],
            'limite' => $atributos['limite'],
        ]);

        ob_start();
        ?>
        <div class="gc-catalogo" data-columnas="<?php echo esc_attr($atributos['columnas']); ?>">
            <?php if ($mostrar_filtros): ?>
            <div class="gc-catalogo-filtros">
                <select class="gc-filtro-categoria" data-filtro="categoria">
                    <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($atributos['categoria'], $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="gc-filtro-productor" data-filtro="productor">
                    <option value=""><?php _e('Todos los productores', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($productores as $prod): ?>
                        <option value="<?php echo esc_attr($prod->ID); ?>" <?php selected($atributos['productor'], $prod->ID); ?>>
                            <?php echo esc_html($prod->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="gc-productos-grid gc-columnas-<?php echo esc_attr($atributos['columnas']); ?>">
                <?php if ($resultado['success'] && !empty($resultado['productos'])): ?>
                    <?php foreach ($resultado['productos'] as $producto): ?>
                        <div class="gc-producto-card" data-producto-id="<?php echo esc_attr($producto['id']); ?>">
                            <div class="gc-producto-imagen">
                                <?php if ($producto['imagen']): ?>
                                    <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['nombre']); ?>">
                                <?php else: ?>
                                    <span class="gc-placeholder dashicons dashicons-carrot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="gc-producto-info">
                                <h4><?php echo esc_html($producto['nombre']); ?></h4>
                                <p class="gc-productor"><?php echo esc_html($producto['productor']); ?></p>
                                <p class="gc-descripcion"><?php echo esc_html($producto['descripcion']); ?></p>
                            </div>
                            <div class="gc-producto-footer">
                                <span class="gc-precio"><?php echo number_format($producto['precio'], 2); ?> € / <?php echo esc_html($producto['unidad']); ?></span>
                                <button type="button" class="gc-btn gc-btn-primary gc-agregar-lista" data-producto-id="<?php echo esc_attr($producto['id']); ?>">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Añadir', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="gc-sin-resultados"><?php _e('No se encontraron productos.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mini carrito/lista de compra
     */
    public function shortcode_carrito($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu lista de compra.', 'flavor-chat-ia') . '</p>';
        }

        $dashboard_tab = Flavor_GC_Dashboard_Tab::get_instance();
        $items = $dashboard_tab->obtener_lista_compra(get_current_user_id());

        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item->precio) * floatval($item->cantidad);
        }

        ob_start();
        ?>
        <div class="gc-mini-carrito">
            <div class="gc-carrito-header">
                <span class="dashicons dashicons-cart"></span>
                <span class="gc-carrito-titulo"><?php _e('Mi Lista', 'flavor-chat-ia'); ?></span>
                <span class="gc-carrito-count"><?php echo count($items); ?></span>
            </div>
            <?php if (!empty($items)): ?>
                <div class="gc-carrito-items">
                    <?php foreach (array_slice($items, 0, 5) as $item): ?>
                        <div class="gc-carrito-item">
                            <span class="gc-item-nombre"><?php echo esc_html($item->producto_nombre); ?></span>
                            <span class="gc-item-cantidad"><?php echo esc_html($item->cantidad); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($items) > 5): ?>
                        <p class="gc-carrito-mas"><?php printf(__('y %d más...', 'flavor-chat-ia'), count($items) - 5); ?></p>
                    <?php endif; ?>
                </div>
                <div class="gc-carrito-footer">
                    <span class="gc-carrito-total"><?php printf(__('Total: %s €', 'flavor-chat-ia'), number_format($total, 2)); ?></span>
                    <a href="#" class="gc-btn gc-btn-primary gc-ver-lista"><?php _e('Ver Lista', 'flavor-chat-ia'); ?></a>
                </div>
            <?php else: ?>
                <p class="gc-carrito-vacio"><?php _e('Tu lista está vacía', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario visual de ciclos
     */
    public function shortcode_calendario($atributos) {
        $atributos = shortcode_atts([
            'meses' => 3,
        ], $atributos);

        // Obtener ciclos de los próximos meses
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-t', strtotime('+' . intval($atributos['meses']) . ' months'));

        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => -1,
            'post_status' => ['gc_abierto', 'gc_cerrado', 'gc_entregado', 'publish'],
            'meta_query' => [
                [
                    'key' => '_gc_fecha_entrega',
                    'value' => [$fecha_inicio, $fecha_fin],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_gc_fecha_entrega',
            'order' => 'ASC',
        ]);

        ob_start();
        ?>
        <div class="gc-calendario">
            <div class="gc-calendario-header">
                <h3><?php _e('Calendario de Entregas', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="gc-calendario-lista">
                <?php if (empty($ciclos)): ?>
                    <p class="gc-sin-ciclos"><?php _e('No hay ciclos programados próximamente.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach ($ciclos as $ciclo):
                        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
                        $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
                        $lugar_entrega = get_post_meta($ciclo->ID, '_gc_lugar_entrega', true);
                        $hora_entrega = get_post_meta($ciclo->ID, '_gc_hora_entrega', true);
                        $estado = get_post_status($ciclo->ID);
                    ?>
                        <div class="gc-calendario-item gc-estado-<?php echo esc_attr($estado); ?>">
                            <div class="gc-fecha-box">
                                <span class="gc-dia"><?php echo esc_html(date('d', strtotime($fecha_entrega))); ?></span>
                                <span class="gc-mes"><?php echo esc_html(date_i18n('M', strtotime($fecha_entrega))); ?></span>
                            </div>
                            <div class="gc-ciclo-info">
                                <h4><?php echo esc_html($ciclo->post_title); ?></h4>
                                <p class="gc-lugar">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($lugar_entrega); ?>
                                    <?php if ($hora_entrega): ?>
                                        - <?php echo esc_html($hora_entrega); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($estado === 'gc_abierto'): ?>
                                    <p class="gc-cierre">
                                        <?php printf(__('Pedidos hasta: %s', 'flavor-chat-ia'), date_i18n(get_option('date_format') . ' H:i', strtotime($fecha_cierre))); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="gc-estado-badge gc-estado-<?php echo esc_attr($estado); ?>">
                                <?php
                                $estados_texto = [
                                    'gc_abierto' => __('Abierto', 'flavor-chat-ia'),
                                    'gc_cerrado' => __('Cerrado', 'flavor-chat-ia'),
                                    'gc_entregado' => __('Entregado', 'flavor-chat-ia'),
                                ];
                                echo esc_html($estados_texto[$estado] ?? $estado);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de pedidos del usuario
     */
    public function shortcode_historial($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu historial.', 'flavor-chat-ia') . '</p>';
        }

        $atributos = shortcode_atts([
            'limite' => 10,
        ], $atributos);

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $usuario_id = get_current_user_id();

        // Obtener pedidos agrupados por ciclo
        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT ciclo_id, SUM(cantidad * precio_unitario) as total, COUNT(*) as num_items, MIN(fecha_pedido) as fecha
            FROM $tabla_pedidos
            WHERE usuario_id = %d
            GROUP BY ciclo_id
            ORDER BY fecha DESC
            LIMIT %d",
            $usuario_id,
            $atributos['limite']
        ));

        ob_start();
        ?>
        <div class="gc-historial">
            <h3><?php _e('Mi Historial de Pedidos', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($pedidos)): ?>
                <p class="gc-sin-historial"><?php _e('No tienes pedidos anteriores.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <div class="gc-historial-lista">
                    <?php foreach ($pedidos as $pedido):
                        $ciclo = get_post($pedido->ciclo_id);
                        $fecha_entrega = get_post_meta($pedido->ciclo_id, '_gc_fecha_entrega', true);
                        $estado = get_post_status($pedido->ciclo_id);
                    ?>
                        <div class="gc-historial-item">
                            <div class="gc-historial-fecha">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($fecha_entrega))); ?>
                            </div>
                            <div class="gc-historial-info">
                                <strong><?php echo $ciclo ? esc_html($ciclo->post_title) : __('Ciclo eliminado', 'flavor-chat-ia'); ?></strong>
                                <span><?php printf(_n('%d producto', '%d productos', $pedido->num_items, 'flavor-chat-ia'), $pedido->num_items); ?></span>
                            </div>
                            <div class="gc-historial-total">
                                <?php echo number_format($pedido->total, 2); ?> €
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Cestas disponibles para suscribirse
     */
    public function shortcode_suscripciones($atributos) {
        $atributos = shortcode_atts([
            'columnas' => 3,
        ], $atributos);

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $cestas = $suscripciones_manager->listar_tipos_cestas();

        ob_start();
        ?>
        <div class="gc-cestas-disponibles">
            <h3><?php _e('Nuestras Cestas', 'flavor-chat-ia'); ?></h3>
            <p class="gc-cestas-intro"><?php _e('Suscríbete a una cesta y recibe productos frescos de forma regular.', 'flavor-chat-ia'); ?></p>
            <div class="gc-cestas-grid gc-columnas-<?php echo esc_attr($atributos['columnas']); ?>">
                <?php foreach ($cestas as $cesta): ?>
                    <div class="gc-cesta-card">
                        <div class="gc-cesta-imagen">
                            <?php if ($cesta->imagen_id): ?>
                                <?php echo wp_get_attachment_image($cesta->imagen_id, 'medium'); ?>
                            <?php else: ?>
                                <span class="gc-placeholder dashicons dashicons-carrot"></span>
                            <?php endif; ?>
                        </div>
                        <div class="gc-cesta-info">
                            <h4><?php echo esc_html($cesta->nombre); ?></h4>
                            <p class="gc-cesta-descripcion"><?php echo esc_html($cesta->descripcion); ?></p>
                            <p class="gc-cesta-precio">
                                <?php if ($cesta->precio_base > 0): ?>
                                    <strong><?php echo number_format($cesta->precio_base, 2); ?> €</strong>
                                    <span><?php _e('/ entrega', 'flavor-chat-ia'); ?></span>
                                <?php else: ?>
                                    <?php _e('Precio según contenido', 'flavor-chat-ia'); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (is_user_logged_in()): ?>
                            <button type="button" class="gc-btn gc-btn-primary gc-suscribirse-cesta" data-cesta-id="<?php echo esc_attr($cesta->id); ?>">
                                <?php _e('Suscribirse', 'flavor-chat-ia'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="gc-btn gc-btn-outline">
                                <?php _e('Inicia sesión para suscribirte', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi suscripción activa
     */
    public function shortcode_mi_cesta($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="gc-aviso">' . __('Inicia sesión para ver tu cesta.', 'flavor-chat-ia') . '</p>';
        }

        $usuario_id = get_current_user_id();

        // Buscar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($grupos)) {
            return '<p class="gc-aviso">' . __('No hay grupos de consumo disponibles.', 'flavor-chat-ia') . '</p>';
        }

        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);

        if (!$consumidor) {
            return '<div class="gc-aviso gc-aviso-info"><p>' . __('No eres miembro de ningún grupo de consumo todavía.', 'flavor-chat-ia') . '</p></div>';
        }

        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $suscripciones = $suscripciones_manager->listar_suscripciones_consumidor($consumidor->id, ['estado' => 'activa']);

        ob_start();
        ?>
        <div class="gc-mi-cesta">
            <?php if (empty($suscripciones)): ?>
                <div class="gc-sin-suscripcion">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php _e('No tienes ninguna suscripción activa.', 'flavor-chat-ia'); ?></p>
                    <a href="#" class="gc-btn gc-btn-primary"><?php _e('Ver Cestas Disponibles', 'flavor-chat-ia'); ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($suscripciones as $suscripcion): ?>
                    <div class="gc-suscripcion-activa">
                        <div class="gc-suscripcion-header">
                            <h4><?php echo esc_html($suscripcion->cesta_nombre); ?></h4>
                            <span class="gc-estado-activa"><?php _e('Activa', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="gc-suscripcion-detalles">
                            <p><strong><?php _e('Frecuencia:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($suscripciones_manager->obtener_etiqueta_frecuencia($suscripcion->frecuencia)); ?></p>
                            <p><strong><?php _e('Importe:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($suscripcion->importe, 2); ?> €</p>
                            <p><strong><?php _e('Próxima entrega:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($suscripcion->fecha_proximo_cargo))); ?></p>
                        </div>
                        <div class="gc-suscripcion-acciones">
                            <button type="button" class="gc-btn gc-btn-outline gc-pausar-suscripcion" data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                <?php _e('Pausar', 'flavor-chat-ia'); ?>
                            </button>
                            <button type="button" class="gc-btn gc-btn-danger gc-cancelar-suscripcion" data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registra las páginas de administración del módulo
     */
    public function registrar_paginas_admin() {
        // Menú principal de Grupos de Consumo
        add_menu_page(
            __('Grupos de Consumo', 'flavor-chat-ia'),
            __('Grupos Consumo', 'flavor-chat-ia'),
            'manage_options',
            'grupos-consumo',
            [$this, 'render_pagina_dashboard'],
            'dashicons-groups',
            56
        );

        // Submenú: Dashboard (mismo que principal)
        add_submenu_page(
            'grupos-consumo',
            __('Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            'manage_options',
            'grupos-consumo',
            [$this, 'render_pagina_dashboard']
        );

        // Submenú: Consumidores
        add_submenu_page(
            'grupos-consumo',
            __('Consumidores', 'flavor-chat-ia'),
            __('Consumidores', 'flavor-chat-ia'),
            'manage_options',
            'gc-consumidores',
            [$this, 'render_pagina_consumidores']
        );

        // Submenú: Suscripciones
        add_submenu_page(
            'grupos-consumo',
            __('Suscripciones', 'flavor-chat-ia'),
            __('Suscripciones', 'flavor-chat-ia'),
            'manage_options',
            'gc-suscripciones',
            [$this, 'render_pagina_suscripciones']
        );

        // Submenú: Consolidado
        add_submenu_page(
            'grupos-consumo',
            __('Consolidado', 'flavor-chat-ia'),
            __('Consolidado', 'flavor-chat-ia'),
            'manage_options',
            'gc-consolidado',
            [$this, 'render_pagina_consolidado']
        );

        // Submenú: Reportes
        add_submenu_page(
            'grupos-consumo',
            __('Reportes', 'flavor-chat-ia'),
            __('Reportes', 'flavor-chat-ia'),
            'manage_options',
            'gc-reportes',
            [$this, 'render_pagina_reportes']
        );

        // Submenú: Configuración
        add_submenu_page(
            'grupos-consumo',
            __('Configuración', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            'manage_options',
            'gc-configuracion',
            [$this, 'render_pagina_configuracion']
        );
    }

    /**
     * Renderiza página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . __('Dashboard Grupos de Consumo', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderiza página de consumidores
     */
    public function render_pagina_consumidores() {
        include dirname(__FILE__) . '/views/consumidores.php';
    }

    /**
     * Renderiza página de suscripciones
     */
    public function render_pagina_suscripciones() {
        include dirname(__FILE__) . '/views/suscripciones.php';
    }

    /**
     * Renderiza página de consolidado
     */
    public function render_pagina_consolidado() {
        include dirname(__FILE__) . '/views/consolidado.php';
    }

    /**
     * Renderiza página de reportes
     */
    public function render_pagina_reportes() {
        include dirname(__FILE__) . '/views/reportes.php';
    }

    /**
     * Renderiza página de configuración
     */
    public function render_pagina_configuracion() {
        include dirname(__FILE__) . '/views/settings.php';
    }

    /**
     * Encola assets del módulo
     */
    public function enqueue_assets() {
        if (!is_admin()) {
            $plugin_url = plugins_url('assets/', __FILE__);
            $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

            // CSS Frontend
            wp_enqueue_style(
                'gc-frontend',
                $plugin_url . 'gc-frontend.css',
                [],
                $version
            );

            // JS Frontend
            wp_enqueue_script(
                'gc-frontend',
                $plugin_url . 'gc-frontend.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('gc-frontend', 'gcFrontend', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor-chat-ia/v1/gc/'),
                'nonce' => wp_create_nonce('gc_frontend_nonce'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'isLoggedIn' => is_user_logged_in(),
                'i18n' => [
                    'agregado' => __('Producto agregado a la lista', 'flavor-chat-ia'),
                    'eliminado' => __('Producto eliminado de la lista', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                    'confirmarEliminar' => __('¿Eliminar este producto de la lista?', 'flavor-chat-ia'),
                    'cargando' => __('Cargando...', 'flavor-chat-ia'),
                    'sinProductos' => __('No hay productos disponibles', 'flavor-chat-ia'),
                    'pedidoCreado' => __('Pedido creado correctamente', 'flavor-chat-ia'),
                ],
            ]);
        }

        // Admin assets
        if (is_admin()) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'gc-') !== false) {
                $plugin_url = plugins_url('assets/', __FILE__);
                $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';

                wp_enqueue_style(
                    'gc-admin',
                    $plugin_url . 'gc-admin.css',
                    [],
                    $version
                );

                wp_enqueue_script(
                    'gc-admin',
                    $plugin_url . 'gc-admin.js',
                    ['jquery'],
                    $version,
                    true
                );

                wp_localize_script('gc-admin', 'gcAdmin', [
                    'nonce' => wp_create_nonce('gc_admin_nonce'),
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                ]);
            }
        }
    }
}
