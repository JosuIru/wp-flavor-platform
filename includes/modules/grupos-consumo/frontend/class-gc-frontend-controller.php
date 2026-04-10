<?php
/**
 * Controller Frontend para Grupos de Consumo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del frontend
 */
class Flavor_GC_Frontend_Controller {

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

        // Registrar shortcodes avanzados sin reemplazar implementaciones existentes.
        $shortcodes = [
            'gc_catalogo' => 'shortcode_catalogo',
            'gc_carrito' => 'shortcode_carrito',
            'gc_calendario' => 'shortcode_calendario',
            'gc_historial' => 'shortcode_historial',
            'gc_suscripciones' => 'shortcode_suscripciones',
            'gc_mi_cesta' => 'shortcode_mi_cesta',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers (legacy, nuevos en class-gc-ajax-handlers.php)
        add_action('wp_ajax_gc_agregar_lista', [$this, 'ajax_agregar_lista']);
        add_action('wp_ajax_gc_quitar_lista', [$this, 'ajax_quitar_lista']);
        add_action('wp_ajax_gc_actualizar_cantidad', [$this, 'ajax_actualizar_cantidad']);
        add_action('wp_ajax_gc_convertir_pedido', [$this, 'ajax_convertir_pedido']);
        add_action('wp_ajax_gc_filtrar_productos', [$this, 'ajax_filtrar_productos']);

        // Cargar handlers AJAX mejorados
        $this->cargar_ajax_handlers();

        // Template overrides
        add_filter('template_include', [$this, 'cargar_templates']);

        // Inyectar carrito flotante en el footer
        add_action('wp_footer', [$this, 'renderizar_carrito_flotante']);
    }

    /**
     * Cargar handlers AJAX mejorados
     */
    private function cargar_ajax_handlers() {
        $archivo_handlers = dirname(__FILE__) . '/class-gc-ajax-handlers.php';
        if (file_exists($archivo_handlers)) {
            require_once $archivo_handlers;
        }
    }

    /**
     * Registrar assets del frontend
     */
    public function registrar_assets() {
        $plugin_url = plugins_url('/', dirname(__FILE__));
        $version = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';
        $gc_frontend_version = file_exists(dirname(dirname(__FILE__)) . '/assets/gc-frontend.js')
            ? (string) filemtime(dirname(dirname(__FILE__)) . '/assets/gc-frontend.js')
            : $version;
        $gc_catalogo_version = file_exists(dirname(dirname(__FILE__)) . '/assets/gc-catalogo.js')
            ? (string) filemtime(dirname(dirname(__FILE__)) . '/assets/gc-catalogo.js')
            : $version;

        // CSS base
        wp_register_style(
            'gc-frontend',
            $plugin_url . 'assets/gc-frontend.css',
            [],
            $version
        );

        // CSS del catalogo y carrito mejorado
        wp_register_style(
            'gc-catalogo',
            $plugin_url . 'assets/gc-catalogo.css',
            ['gc-frontend'],
            $version
        );

        // JavaScript base
        wp_register_script(
            'gc-frontend',
            $plugin_url . 'assets/gc-frontend.js',
            ['jquery'],
            $gc_frontend_version,
            true
        );

        // JavaScript del catalogo y carrito mejorado
        wp_register_script(
            'gc-catalogo',
            $plugin_url . 'assets/gc-catalogo.js',
            ['jquery', 'gc-frontend'],
            $gc_catalogo_version,
            true
        );

        // Configuracion global para JavaScript
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(FLAVOR_PLATFORM_REST_NAMESPACE . '/gc/'),
            'nonce' => wp_create_nonce('gc_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl' => wp_login_url(home_url('/mi-portal/grupos-consumo/productos/')),
            'carritoUrl' => Flavor_Platform_Helpers::get_action_url('grupos-consumo', 'mi-pedido'),
            'i18n' => [
                'agregado' => __('Producto agregado al pedido', 'flavor-platform'),
                'eliminado' => __('Producto eliminado del pedido', 'flavor-platform'),
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'confirmarEliminar' => __('Eliminar este producto del pedido?', 'flavor-platform'),
                'confirmarVaciar' => __('Seguro que deseas vaciar el pedido?', 'flavor-platform'),
                'cargando' => __('Cargando...', 'flavor-platform'),
                'sinProductos' => __('No hay productos disponibles', 'flavor-platform'),
                'pedidoCreado' => __('Pedido confirmado correctamente', 'flavor-platform'),
                'pedidoVacio' => __('Tu pedido esta vacio', 'flavor-platform'),
                'stockInsuficiente' => __('Stock insuficiente', 'flavor-platform'),
            ],
        ];

        wp_localize_script('gc-frontend', 'gcFrontend', $configuracion_js);
        wp_localize_script('gc-catalogo', 'gcFrontend', $configuracion_js);
    }

    /**
     * Encolar assets cuando se necesitan
     *
     * @param bool $incluir_catalogo Si incluir assets del catalogo mejorado
     */
    private function encolar_assets($incluir_catalogo = false) {
        wp_enqueue_style('gc-frontend');
        wp_enqueue_script('gc-frontend');

        if ($incluir_catalogo) {
            wp_enqueue_style('gc-catalogo');
            wp_enqueue_script('gc-catalogo');
        }
    }

    /**
     * Shortcode: Catálogo de productos mejorado
     *
     * Atributos:
     * - grupo_id: ID del grupo de consumo (opcional)
     * - mostrar_filtros: 'si' o 'no' (por defecto 'si')
     * - columnas: 2, 3 o 4 (por defecto 3)
     * - productor: ID del productor para filtrar
     * - categoria: slug de la categoria para filtrar
     * - limite: numero maximo de productos (-1 para todos)
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del catalogo
     */
    public function shortcode_catalogo($atts) {
        $this->encolar_assets(true); // Incluir assets del catalogo

        $atributos = shortcode_atts([
            'grupo_id' => '',
            'mostrar_filtros' => 'si',
            'columnas' => 3,
            'productor' => '',
            'categoria' => '',
            'limite' => -1,
            // Parámetros visuales (VBP)
            'esquema_color' => 'default',
            'estilo_tarjeta' => 'elevated',
            'radio_bordes' => 'lg',
            'animacion_entrada' => 'fade',
            'orderby' => 'title',
            'order' => 'ASC',
        ], $atts);

        // Preparar datos para el template
        $datos_template = $this->preparar_datos_catalogo($atributos);

        ob_start();

        // Cargar template mejorado
        $ruta_template = dirname(dirname(__FILE__)) . '/templates/catalogo.php';
        if (file_exists($ruta_template)) {
            $args = $datos_template;
            include $ruta_template;
        } else {
            // Fallback al render antiguo
            $this->render_catalogo($atributos);
        }

        return ob_get_clean();
    }

    /**
     * Preparar datos para el template del catalogo
     *
     * @param array $atributos Atributos del shortcode
     * @return array Datos para el template
     */
    private function preparar_datos_catalogo($atributos) {
        global $wpdb;

        // Obtener productos
        $args_query = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => intval($atributos['limite']),
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if (!empty($atributos['productor'])) {
            $args_query['meta_query'][] = [
                'key' => '_gc_productor_id',
                'value' => absint($atributos['productor']),
            ];
        }

        if (!empty($atributos['categoria'])) {
            $args_query['tax_query'] = [[
                'taxonomy' => 'gc_categoria',
                'field' => 'slug',
                'terms' => sanitize_text_field($atributos['categoria']),
            ]];
        }

        $productos = get_posts($args_query);

        // Obtener productores para filtros
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Obtener categorias
        $categorias = get_terms([
            'taxonomy' => 'gc_categoria',
            'hide_empty' => true,
        ]);

        // Lista de compra del usuario
        $lista_compra = [];
        if (is_user_logged_in()) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT producto_id, cantidad FROM {$wpdb->prefix}flavor_gc_lista_compra WHERE usuario_id = %d",
                get_current_user_id()
            ));
            foreach ($items as $item) {
                $lista_compra[$item->producto_id] = floatval($item->cantidad);
            }
        }

        // Obtener ciclo activo
        $ciclo_activo = $this->obtener_ciclo_activo();

        // Obtener configuracion del modulo
        $opciones = get_option('flavor_chat_modules', []);
        $porcentaje_gestion = floatval($opciones['grupos_consumo']['settings']['porcentaje_gestion'] ?? 5);

        return [
            'productos' => $productos,
            'productores' => $productores,
            'categorias' => is_array($categorias) ? $categorias : [],
            'lista_compra' => $lista_compra,
            'ciclo_activo' => $ciclo_activo,
            'porcentaje_gestion' => $porcentaje_gestion,
            'atts' => $atributos,
        ];
    }

    /**
     * Obtener ciclo activo
     *
     * @return array|null Datos del ciclo o null
     */
    private function obtener_ciclo_activo() {
        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_gc_estado',
                    'value' => 'abierto',
                ],
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_gc_fecha_cierre',
            'order' => 'ASC',
        ]);

        if (empty($ciclos)) {
            return null;
        }

        $ciclo = $ciclos[0];
        return [
            'id' => $ciclo->ID,
            'titulo' => $ciclo->post_title,
            'fecha_cierre' => get_post_meta($ciclo->ID, '_gc_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($ciclo->ID, '_gc_fecha_entrega', true),
            'hora_entrega' => get_post_meta($ciclo->ID, '_gc_hora_entrega', true),
            'lugar_entrega' => get_post_meta($ciclo->ID, '_gc_lugar_entrega', true),
            'notas' => get_post_meta($ciclo->ID, '_gc_notas', true),
            'estado' => 'abierto',
        ];
    }

    /**
     * Renderizar catálogo
     */
    private function render_catalogo($atts) {
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
        $visual_class_string = implode(' ', $visual_classes);

        // Mapeo de orderby para productos GC
        $orderby_map = [
            'title' => 'title',
            'date' => 'date',
            'precio' => ['meta_key' => '_gc_precio', 'orderby' => 'meta_value_num'],
            'productor' => ['meta_key' => '_gc_productor_id', 'orderby' => 'meta_value'],
        ];
        $orderby_config = $orderby_map[$atts['orderby']] ?? ['orderby' => 'title'];
        $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

        // Obtener productos
        $args = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => $atts['limite'],
            'order' => $order,
        ];

        // Aplicar orderby config
        if (is_array($orderby_config)) {
            if (isset($orderby_config['meta_key'])) {
                $args['meta_key'] = $orderby_config['meta_key'];
            }
            $args['orderby'] = $orderby_config['orderby'] ?? 'title';
        } else {
            $args['orderby'] = $orderby_config;
        }

        if ($atts['productor']) {
            $args['meta_query'][] = [
                'key' => '_gc_productor_id',
                'value' => absint($atts['productor']),
            ];
        }

        $productos = get_posts($args);

        // Obtener productores para filtro
        $productores = get_posts([
            'post_type' => 'gc_productor',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Lista de compra del usuario actual
        $lista_compra = [];
        if (is_user_logged_in()) {
            global $wpdb;
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT producto_id, cantidad FROM {$wpdb->prefix}flavor_gc_lista_compra WHERE usuario_id = %d",
                get_current_user_id()
            ));
            foreach ($items as $item) {
                $lista_compra[$item->producto_id] = $item->cantidad;
            }
        }
        ?>
        <div class="gc-catalogo <?php echo esc_attr($visual_class_string); ?>" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <?php if ($atts['mostrar_filtros'] === 'si'): ?>
                <div class="gc-catalogo-filtros">
                    <div class="gc-filtro-buscar">
                        <input type="text" id="gc-buscar-producto" placeholder="<?php _e('Buscar producto...', 'flavor-platform'); ?>">
                        <span class="gc-filtro-icon dashicons dashicons-search"></span>
                    </div>
                    <div class="gc-filtro-productor">
                        <select id="gc-filtrar-productor">
                            <option value=""><?php _e('Todos los productores', 'flavor-platform'); ?></option>
                            <?php foreach ($productores as $productor): ?>
                                <option value="<?php echo $productor->ID; ?>">
                                    <?php echo esc_html($productor->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="gc-filtro-orden">
                        <select id="gc-ordenar-productos">
                            <option value="<?php echo esc_attr__('nombre', 'flavor-platform'); ?>"><?php _e('Nombre A-Z', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('nombre-desc', 'flavor-platform'); ?>"><?php _e('Nombre Z-A', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('precio', 'flavor-platform'); ?>"><?php _e('Precio menor', 'flavor-platform'); ?></option>
                            <option value="<?php echo esc_attr__('precio-desc', 'flavor-platform'); ?>"><?php _e('Precio mayor', 'flavor-platform'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="gc-productos-grid" id="gc-productos-lista">
                <?php if (empty($productos)): ?>
                    <p class="gc-sin-productos"><?php _e('No hay productos disponibles en este momento.', 'flavor-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                        <?php $this->render_producto_card($producto, $lista_compra); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tarjeta de producto
     */
    private function render_producto_card($producto, $lista_compra = []) {
        $precio = get_post_meta($producto->ID, '_gc_precio', true);
        $unidad = get_post_meta($producto->ID, '_gc_unidad', true) ?: 'ud';
        $productor_id = get_post_meta($producto->ID, '_gc_productor_id', true);
        $productor = $productor_id ? get_post($productor_id) : null;
        $imagen = get_the_post_thumbnail_url($producto->ID, 'medium');
        $en_lista = isset($lista_compra[$producto->ID]);
        $cantidad = $en_lista ? $lista_compra[$producto->ID] : 1;
        ?>
        <div class="gc-producto-card <?php echo $en_lista ? 'en-lista' : ''; ?>"
             data-producto-id="<?php echo $producto->ID; ?>"
             data-precio="<?php echo esc_attr($precio); ?>"
             data-nombre="<?php echo esc_attr($producto->post_title); ?>">

            <div class="gc-producto-imagen">
                <?php if ($imagen): ?>
                    <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($producto->post_title); ?>">
                <?php else: ?>
                    <div class="gc-producto-sin-imagen">
                        <span class="dashicons dashicons-carrot"></span>
                    </div>
                <?php endif; ?>
                <?php if ($en_lista): ?>
                    <span class="gc-badge-en-lista"><?php _e('En lista', 'flavor-platform'); ?></span>
                <?php endif; ?>
            </div>

            <div class="gc-producto-info">
                <h3 class="gc-producto-nombre"><?php echo esc_html($producto->post_title); ?></h3>
                <?php if ($productor): ?>
                    <p class="gc-producto-productor">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo esc_html($productor->post_title); ?>
                    </p>
                <?php endif; ?>
                <p class="gc-producto-precio">
                    <span class="gc-precio-valor"><?php echo number_format($precio, 2, ',', '.'); ?>€</span>
                    <span class="gc-precio-unidad">/ <?php echo esc_html($unidad); ?></span>
                </p>
            </div>

            <div class="gc-producto-acciones">
                <?php if (is_user_logged_in()): ?>
                    <div class="gc-cantidad-control">
                        <button type="button" class="gc-btn-cantidad gc-btn-menos" data-action="menos">-</button>
                        <input type="number" class="gc-cantidad-input" value="<?php echo esc_attr($cantidad); ?>" min="1" max="99" step="1">
                        <button type="button" class="gc-btn-cantidad gc-btn-mas" data-action="mas">+</button>
                    </div>
                    <button type="button" class="gc-btn-agregar-lista <?php echo $en_lista ? 'en-lista' : ''; ?>">
                        <span class="dashicons <?php echo $en_lista ? 'dashicons-yes' : 'dashicons-cart'; ?>"></span>
                        <span class="gc-btn-texto"><?php echo $en_lista ? __('En lista', 'flavor-platform') : __('Agregar', 'flavor-platform'); ?></span>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/productos/'))); ?>" class="gc-btn-login">
                        <?php _e('Iniciar sesión para agregar', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Carrito / Lista de compra mejorado
     *
     * Atributos:
     * - estilo: 'completo' (pagina), 'mini' (widget), 'flotante' (esquina)
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del carrito
     */
    public function shortcode_carrito($atts) {
        $this->encolar_assets(true);

        if (!is_user_logged_in()) {
            return '<p class="gc-login-requerido">' . __('Inicia sesión para ver tu lista de compra.', 'flavor-platform') . '</p>';
        }

        $atributos = shortcode_atts([
            'estilo' => 'completo', // completo, mini, flotante
        ], $atts);

        // Preparar datos para el template
        $datos_template = $this->preparar_datos_carrito();

        ob_start();

        // Seleccionar template segun estilo
        $estilo = $atributos['estilo'];
        if ($estilo === 'completo') {
            $ruta_template = dirname(dirname(__FILE__)) . '/templates/carrito-completo.php';
        } else {
            $ruta_template = dirname(dirname(__FILE__)) . '/templates/carrito.php';
        }

        if (file_exists($ruta_template)) {
            $args = $datos_template;
            include $ruta_template;
        } else {
            // Fallback al render antiguo
            $this->render_carrito($estilo);
        }

        return ob_get_clean();
    }

    /**
     * Preparar datos para el template del carrito
     *
     * @return array Datos para el template
     */
    private function preparar_datos_carrito() {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Obtener items de la lista
        $items_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$wpdb->prefix}flavor_gc_lista_compra l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d
             ORDER BY l.fecha_agregado DESC",
            $usuario_id
        ));

        $items = [];
        $total = 0;

        foreach ($items_raw as $item) {
            $precio = floatval(get_post_meta($item->producto_id, '_gc_precio', true));
            $unidad = get_post_meta($item->producto_id, '_gc_unidad', true) ?: 'ud';
            $cantidad_minima = floatval(get_post_meta($item->producto_id, '_gc_cantidad_minima', true) ?: 1);
            $stock = get_post_meta($item->producto_id, '_gc_stock', true);
            $productor_id = get_post_meta($item->producto_id, '_gc_productor_id', true);
            $productor = $productor_id ? get_post($productor_id) : null;
            $es_ecologico = $productor_id ? get_post_meta($productor_id, '_gc_certificacion_eco', true) : false;

            $subtotal = $precio * $item->cantidad;
            $total += $subtotal;

            $items[] = [
                'id' => $item->id,
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre,
                'cantidad' => floatval($item->cantidad),
                'precio' => $precio,
                'unidad' => $unidad,
                'cantidad_minima' => $cantidad_minima,
                'stock' => $stock,
                'subtotal' => $subtotal,
                'imagen' => get_the_post_thumbnail_url($item->producto_id, 'thumbnail'),
                'productor' => $productor ? $productor->post_title : '',
                'es_ecologico' => (bool) $es_ecologico,
            ];
        }

        // Obtener ciclo activo
        $ciclo = $this->obtener_ciclo_activo();

        // Configuracion del modulo
        $opciones = get_option('flavor_chat_modules', []);
        $porcentaje_gestion = floatval($opciones['grupos_consumo']['settings']['porcentaje_gestion'] ?? 5);

        return [
            'items' => $items,
            'total' => $total,
            'ciclo' => $ciclo,
            'porcentaje_gestion' => $porcentaje_gestion,
            'notas_ciclo' => $ciclo ? $ciclo['notas'] : '',
            'url_catalogo' => home_url('/mi-portal/grupos-consumo/productos/'),
        ];
    }

    /**
     * Renderizar carrito/lista
     */
    private function render_carrito($estilo) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$wpdb->prefix}flavor_gc_lista_compra l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d
             ORDER BY l.fecha_agregado DESC",
            $usuario_id
        ));

        $total = 0;
        foreach ($items as $item) {
            $precio = get_post_meta($item->producto_id, '_gc_precio', true);
            $total += $precio * $item->cantidad;
        }
        ?>
        <div class="gc-carrito gc-carrito-<?php echo esc_attr($estilo); ?>" id="gc-carrito">
            <div class="gc-carrito-header">
                <h3>
                    <span class="dashicons dashicons-cart"></span>
                    <?php _e('Mi Lista de Compra', 'flavor-platform'); ?>
                    <span class="gc-carrito-count"><?php echo count($items); ?></span>
                </h3>
            </div>

            <div class="gc-carrito-items">
                <?php if (empty($items)): ?>
                    <p class="gc-carrito-vacio"><?php _e('Tu lista está vacía', 'flavor-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($items as $item):
                        $precio = get_post_meta($item->producto_id, '_gc_precio', true);
                        $unidad = get_post_meta($item->producto_id, '_gc_unidad', true) ?: 'ud';
                        $subtotal = $precio * $item->cantidad;
                    ?>
                        <div class="gc-carrito-item" data-item-id="<?php echo $item->id; ?>" data-producto-id="<?php echo $item->producto_id; ?>">
                            <div class="gc-item-info">
                                <span class="gc-item-nombre"><?php echo esc_html($item->nombre); ?></span>
                                <span class="gc-item-precio"><?php echo number_format($precio, 2, ',', '.'); ?>€/<?php echo esc_html($unidad); ?></span>
                            </div>
                            <div class="gc-item-cantidad">
                                <button type="button" class="gc-btn-item-menos">-</button>
                                <span class="gc-item-qty"><?php echo esc_html($item->cantidad); ?></span>
                                <button type="button" class="gc-btn-item-mas">+</button>
                            </div>
                            <div class="gc-item-subtotal">
                                <?php echo number_format($subtotal, 2, ',', '.'); ?>€
                            </div>
                            <button type="button" class="gc-btn-item-eliminar" title="<?php _e('Eliminar', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($items)): ?>
                <div class="gc-carrito-footer">
                    <div class="gc-carrito-total">
                        <span><?php _e('Total:', 'flavor-platform'); ?></span>
                        <strong id="gc-total-carrito"><?php echo number_format($total, 2, ',', '.'); ?>€</strong>
                    </div>
                    <button type="button" class="gc-btn-convertir-pedido" id="gc-convertir-pedido">
                        <?php _e('Convertir en Pedido', 'flavor-platform'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Shortcode: Calendario de ciclos
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'meses' => 3,
            'mostrar_pasados' => 'no',
        ], $atts);

        ob_start();
        $this->render_calendario($atts);
        return ob_get_clean();
    }

    /**
     * Renderizar calendario
     */
    private function render_calendario($atts) {
        // Obtener ciclos
        $fecha_inicio = $atts['mostrar_pasados'] === 'si'
            ? date('Y-m-d', strtotime('-1 month'))
            : current_time('Y-m-d');

        $fecha_fin = date('Y-m-d', strtotime('+' . $atts['meses'] . ' months'));

        global $wpdb;
        $ciclos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title,
                    pm_cierre.meta_value as fecha_cierre,
                    pm_entrega.meta_value as fecha_entrega,
                    pm_estado.meta_value as estado
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm_cierre ON p.ID = pm_cierre.post_id AND pm_cierre.meta_key = '_gc_fecha_cierre'
             LEFT JOIN {$wpdb->postmeta} pm_entrega ON p.ID = pm_entrega.post_id AND pm_entrega.meta_key = '_gc_fecha_entrega'
             LEFT JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '_gc_estado'
             WHERE p.post_type = 'gc_ciclo'
             AND p.post_status = 'publish'
             AND (pm_cierre.meta_value >= %s OR pm_entrega.meta_value >= %s)
             AND pm_cierre.meta_value <= %s
             ORDER BY pm_cierre.meta_value ASC",
            $fecha_inicio,
            $fecha_inicio,
            $fecha_fin
        ));
        ?>
        <div class="gc-calendario">
            <div class="gc-calendario-header">
                <h3><?php _e('Calendario de Ciclos', 'flavor-platform'); ?></h3>
                <div class="gc-calendario-leyenda">
                    <span class="gc-leyenda-item gc-leyenda-abierto"><?php _e('Abierto', 'flavor-platform'); ?></span>
                    <span class="gc-leyenda-item gc-leyenda-cerrado"><?php _e('Cerrado', 'flavor-platform'); ?></span>
                    <span class="gc-leyenda-item gc-leyenda-entrega"><?php _e('Entrega', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="gc-calendario-timeline">
                <?php if (empty($ciclos)): ?>
                    <p class="gc-sin-ciclos"><?php _e('No hay ciclos programados próximamente.', 'flavor-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($ciclos as $ciclo):
                        $es_pasado = strtotime($ciclo->fecha_entrega) < current_time('timestamp');
                        $es_abierto = $ciclo->estado === 'abierto';
                    ?>
                        <div class="gc-ciclo-timeline <?php echo $es_pasado ? 'pasado' : ''; ?> <?php echo $es_abierto ? 'abierto' : ''; ?>">
                            <div class="gc-ciclo-fechas">
                                <div class="gc-fecha-cierre">
                                    <span class="gc-fecha-label"><?php _e('Cierre', 'flavor-platform'); ?></span>
                                    <span class="gc-fecha-valor"><?php echo date_i18n('j M', strtotime($ciclo->fecha_cierre)); ?></span>
                                </div>
                                <div class="gc-ciclo-linea"></div>
                                <div class="gc-fecha-entrega">
                                    <span class="gc-fecha-label"><?php _e('Entrega', 'flavor-platform'); ?></span>
                                    <span class="gc-fecha-valor"><?php echo date_i18n('j M', strtotime($ciclo->fecha_entrega)); ?></span>
                                </div>
                            </div>
                            <div class="gc-ciclo-info">
                                <h4><?php echo esc_html($ciclo->post_title); ?></h4>
                                <span class="gc-ciclo-estado gc-estado-<?php echo esc_attr($ciclo->estado); ?>">
                                    <?php echo esc_html(ucfirst($ciclo->estado ?: 'pendiente')); ?>
                                </span>
                                <?php if ($es_abierto): ?>
                                    <a href="<?php echo esc_url(add_query_arg('ciclo', intval($ciclo->ID), home_url('/mi-portal/grupos-consumo/ciclo/'))); ?>" class="gc-btn-ver-ciclo">
                                        <?php _e('Ver productos', 'flavor-platform'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Historial de pedidos
     */
    public function shortcode_historial($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="gc-login-requerido">' . __('Inicia sesión para ver tu historial.', 'flavor-platform') . '</p>';
        }

        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->render_historial($atts);
        return ob_get_clean();
    }

    /**
     * Renderizar historial
     */
    private function render_historial($atts) {
        global $wpdb;

        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.post_title as ciclo_nombre
             FROM {$wpdb->prefix}flavor_gc_pedidos p
             LEFT JOIN {$wpdb->posts} c ON p.ciclo_id = c.ID
             WHERE p.usuario_id = %d
             ORDER BY p.fecha_pedido DESC
             LIMIT %d",
            get_current_user_id(),
            $atts['limite']
        ));
        ?>
        <div class="gc-historial">
            <h3><?php _e('Historial de Pedidos', 'flavor-platform'); ?></h3>

            <?php if (empty($pedidos)): ?>
                <p class="gc-sin-pedidos"><?php _e('No tienes pedidos anteriores.', 'flavor-platform'); ?></p>
            <?php else: ?>
                <div class="gc-pedidos-lista">
                    <?php foreach ($pedidos as $pedido):
                        $detalles = json_decode($pedido->detalles, true);
                    ?>
                        <div class="gc-pedido-card" data-pedido="<?php echo $pedido->id; ?>">
                            <div class="gc-pedido-header">
                                <span class="gc-pedido-numero">#<?php echo $pedido->id; ?></span>
                                <span class="gc-pedido-fecha"><?php echo date_i18n('j M Y', strtotime($pedido->fecha_pedido)); ?></span>
                                <span class="gc-pedido-estado gc-estado-<?php echo esc_attr($pedido->estado); ?>">
                                    <?php echo esc_html(ucfirst($pedido->estado)); ?>
                                </span>
                            </div>
                            <div class="gc-pedido-body">
                                <p class="gc-pedido-ciclo">
                                    <strong><?php _e('Ciclo:', 'flavor-platform'); ?></strong>
                                    <?php echo esc_html($pedido->ciclo_nombre ?: 'N/A'); ?>
                                </p>
                                <?php if (!empty($detalles)): ?>
                                    <ul class="gc-pedido-items">
                                        <?php foreach (array_slice($detalles, 0, 3) as $item): ?>
                                            <li><?php echo esc_html($item['nombre']); ?> x<?php echo esc_html($item['cantidad']); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (count($detalles) > 3): ?>
                                            <li class="gc-mas-items">+<?php echo count($detalles) - 3; ?> <?php _e('más', 'flavor-platform'); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="gc-pedido-footer">
                                <span class="gc-pedido-total"><?php echo number_format($pedido->total, 2, ',', '.'); ?>€</span>
                                <button type="button" class="gc-btn-ver-detalle" data-pedido="<?php echo $pedido->id; ?>">
                                    <?php _e('Ver detalle', 'flavor-platform'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Shortcode: Cestas disponibles para suscripción
     */
    public function shortcode_suscripciones($atts) {
        $this->encolar_assets();

        ob_start();
        $this->render_suscripciones_disponibles();
        return ob_get_clean();
    }

    /**
     * Renderizar cestas disponibles
     */
    private function render_suscripciones_disponibles() {
        global $wpdb;

        $cestas = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_gc_cestas_tipo WHERE activa = 1 ORDER BY orden ASC"
        );
        ?>
        <div class="gc-cestas-suscripcion">
            <h3><?php _e('Cestas Disponibles', 'flavor-platform'); ?></h3>
            <p class="gc-cestas-intro"><?php _e('Suscríbete a una cesta y recibe productos frescos de forma regular.', 'flavor-platform'); ?></p>

            <div class="gc-cestas-grid">
                <?php foreach ($cestas as $cesta):
                    $productos = json_decode($cesta->productos_incluidos, true) ?: [];
                ?>
                    <div class="gc-cesta-card">
                        <div class="gc-cesta-imagen">
                            <?php if ($cesta->imagen_id):
                                echo wp_get_attachment_image($cesta->imagen_id, 'medium');
                            else: ?>
                                <div class="gc-cesta-sin-imagen">
                                    <span class="dashicons dashicons-products"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="gc-cesta-info">
                            <h4><?php echo esc_html($cesta->nombre); ?></h4>
                            <p class="gc-cesta-descripcion"><?php echo esc_html($cesta->descripcion); ?></p>
                            <p class="gc-cesta-precio">
                                <span class="gc-precio-desde"><?php _e('Desde', 'flavor-platform'); ?></span>
                                <span class="gc-precio-valor"><?php echo number_format($cesta->precio_base, 2, ',', '.'); ?>€</span>
                            </p>
                            <?php if (!empty($productos)): ?>
                                <p class="gc-cesta-productos">
                                    <?php echo count($productos); ?> <?php _e('productos incluidos', 'flavor-platform'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="gc-cesta-acciones">
                            <?php if (is_user_logged_in()): ?>
                                <button type="button" class="gc-btn-suscribirse" data-cesta="<?php echo esc_attr($cesta->id); ?>">
                                    <?php _e('Suscribirse', 'flavor-platform'); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/suscripciones/'))); ?>" class="gc-btn-login">
                                    <?php _e('Inicia sesión', 'flavor-platform'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Mi suscripción activa
     */
    public function shortcode_mi_cesta($atts) {
        $this->encolar_assets();

        if (!is_user_logged_in()) {
            return '<p class="gc-login-requerido">' . __('Inicia sesión para ver tu suscripción.', 'flavor-platform') . '</p>';
        }

        ob_start();
        $this->render_mi_suscripcion();
        return ob_get_clean();
    }

    /**
     * Renderizar mi suscripción
     */
    private function render_mi_suscripcion() {
        global $wpdb;

        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_gc_consumidores WHERE usuario_id = %d AND estado = 'activo'",
            get_current_user_id()
        ));

        if (!$consumidor) {
            echo '<p class="gc-no-consumidor">' . __('No tienes una cuenta de consumidor activa.', 'flavor-platform') . '</p>';
            return;
        }

        $suscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.nombre as cesta_nombre, c.descripcion as cesta_descripcion
             FROM {$wpdb->prefix}flavor_gc_suscripciones s
             LEFT JOIN {$wpdb->prefix}flavor_gc_cestas_tipo c ON s.tipo_cesta_id = c.id
             WHERE s.consumidor_id = %d AND s.estado IN ('activa', 'pausada')
             ORDER BY s.fecha_inicio DESC
             LIMIT 1",
            $consumidor->id
        ));

        if (!$suscripcion): ?>
            <div class="gc-sin-suscripcion">
                <p><?php _e('No tienes una suscripción activa.', 'flavor-platform'); ?></p>
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/suscripciones/')); ?>" class="gc-btn-ver-cestas">
                    <?php _e('Ver cestas disponibles', 'flavor-platform'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="gc-mi-suscripcion">
                <div class="gc-suscripcion-header">
                    <h3><?php echo esc_html($suscripcion->cesta_nombre); ?></h3>
                    <span class="gc-suscripcion-estado gc-estado-<?php echo esc_attr($suscripcion->estado); ?>">
                        <?php echo esc_html(ucfirst($suscripcion->estado)); ?>
                    </span>
                </div>
                <div class="gc-suscripcion-info">
                    <p class="gc-suscripcion-descripcion"><?php echo esc_html($suscripcion->cesta_descripcion); ?></p>
                    <div class="gc-suscripcion-detalles">
                        <p><strong><?php _e('Frecuencia:', 'flavor-platform'); ?></strong> <?php echo esc_html(ucfirst($suscripcion->frecuencia)); ?></p>
                        <p><strong><?php _e('Importe:', 'flavor-platform'); ?></strong> <?php echo number_format($suscripcion->importe, 2, ',', '.'); ?>€</p>
                        <p><strong><?php _e('Próximo cargo:', 'flavor-platform'); ?></strong>
                            <?php echo $suscripcion->fecha_proximo_cargo
                                ? date_i18n('j M Y', strtotime($suscripcion->fecha_proximo_cargo))
                                : 'N/A'; ?>
                        </p>
                    </div>
                </div>
                <div class="gc-suscripcion-acciones">
                    <?php if ($suscripcion->estado === 'activa'): ?>
                        <button type="button" class="gc-btn-pausar-suscripcion" data-suscripcion="<?php echo $suscripcion->id; ?>">
                            <?php _e('Pausar', 'flavor-platform'); ?>
                        </button>
                    <?php elseif ($suscripcion->estado === 'pausada'): ?>
                        <button type="button" class="gc-btn-reanudar-suscripcion" data-suscripcion="<?php echo $suscripcion->id; ?>">
                            <?php _e('Reanudar', 'flavor-platform'); ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="gc-btn-cancelar-suscripcion" data-suscripcion="<?php echo $suscripcion->id; ?>">
                        <?php _e('Cancelar', 'flavor-platform'); ?>
                    </button>
                </div>
            </div>
        <?php endif;
    }

    /**
     * AJAX: Agregar producto a lista
     */
    public function ajax_agregar_lista() {
        check_ajax_referer('gc_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $producto_id = absint($_POST['producto_id']);
        $cantidad = absint($_POST['cantidad'] ?? 1);

        if (!$producto_id) {
            wp_send_json_error(['message' => __('Producto no válido', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_lista_compra';
        $usuario_id = get_current_user_id();

        // Asegurar que la tabla existe
        $this->crear_tabla_lista_compra();

        // Verificar si ya existe
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE usuario_id = %d AND producto_id = %d",
            $usuario_id,
            $producto_id
        ));

        if ($existente) {
            $resultado = $wpdb->update($tabla, ['cantidad' => $cantidad], ['id' => $existente]);
        } else {
            $resultado = $wpdb->insert($tabla, [
                'usuario_id' => $usuario_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'fecha_agregado' => current_time('mysql'),
            ]);
        }

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al agregar el producto', 'flavor-platform')]);
        }

        wp_send_json_success(['message' => __('Producto agregado a la lista', 'flavor-platform')]);
    }

    /**
     * Crea la tabla de lista de compra si no existe
     */
    private function crear_tabla_lista_compra() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_gc_lista_compra';

        // Verificar si la tabla ya existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla));
        if ($tabla_existe === $tabla) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            producto_id bigint(20) unsigned NOT NULL,
            cantidad decimal(10,2) DEFAULT 1.00,
            fecha_agregado datetime DEFAULT NULL,
            notas varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY producto_id (producto_id)
        ) $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta($sql);
    }

    /**
     * AJAX: Quitar producto de lista
     */
    public function ajax_quitar_lista() {
        check_ajax_referer('gc_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $producto_id = absint($_POST['producto_id']);

        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'flavor_gc_lista_compra',
            [
                'usuario_id' => get_current_user_id(),
                'producto_id' => $producto_id,
            ]
        );

        wp_send_json_success(['message' => __('Producto eliminado de la lista', 'flavor-platform')]);
    }

    /**
     * AJAX: Actualizar cantidad
     */
    public function ajax_actualizar_cantidad() {
        check_ajax_referer('gc_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $item_id = absint($_POST['item_id']);
        $cantidad = absint($_POST['cantidad']);

        if ($cantidad < 1) {
            $cantidad = 1;
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'flavor_gc_lista_compra',
            ['cantidad' => $cantidad],
            ['id' => $item_id, 'usuario_id' => get_current_user_id()]
        );

        wp_send_json_success(['cantidad' => $cantidad]);
    }

    /**
     * AJAX: Convertir lista en pedido
     */
    public function ajax_convertir_pedido() {
        check_ajax_referer('gc_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $usuario_id = get_current_user_id();

        // Obtener items de la lista
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$wpdb->prefix}flavor_gc_lista_compra l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d",
            $usuario_id
        ));

        if (empty($items)) {
            wp_send_json_error(['message' => __('Tu lista está vacía', 'flavor-platform')]);
        }

        // Buscar ciclo activo
        $ciclo = get_posts([
            'post_type' => 'gc_ciclo',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => '_gc_estado', 'value' => 'abierto'],
            ],
        ]);

        if (empty($ciclo)) {
            wp_send_json_error(['message' => __('No hay ciclos abiertos para hacer pedidos', 'flavor-platform')]);
        }

        $ciclo_id = $ciclo[0]->ID;

        // Calcular total y preparar detalles
        $total = 0;
        $detalles = [];

        foreach ($items as $item) {
            $precio = get_post_meta($item->producto_id, '_gc_precio', true);
            $subtotal = $precio * $item->cantidad;
            $total += $subtotal;

            $detalles[] = [
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre,
                'cantidad' => $item->cantidad,
                'precio' => $precio,
                'subtotal' => $subtotal,
            ];
        }

        // Crear pedido
        $wpdb->insert($wpdb->prefix . 'flavor_gc_pedidos', [
            'ciclo_id' => $ciclo_id,
            'usuario_id' => $usuario_id,
            'detalles' => wp_json_encode($detalles),
            'total' => $total,
            'estado' => 'pendiente',
            'fecha_pedido' => current_time('mysql'),
        ]);

        $pedido_id = $wpdb->insert_id;

        // Vaciar lista de compra
        $wpdb->delete($wpdb->prefix . 'flavor_gc_lista_compra', ['usuario_id' => $usuario_id]);

        // Disparar acción
        do_action('gc_pedido_creado', $pedido_id, $usuario_id);

        wp_send_json_success([
            'message' => __('Pedido creado correctamente', 'flavor-platform'),
            'pedido_id' => $pedido_id,
        ]);
    }

    /**
     * Cargar templates personalizados
     */
    public function cargar_templates($template) {
        $plugin_templates_path = FLAVOR_PLATFORM_PATH . 'templates/frontend/grupos-consumo/';

        // Template para single gc_grupo
        if (is_singular('gc_grupo')) {
            // Primero buscar en el tema
            $custom_theme = locate_template('grupos-consumo/single-gc_grupo.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            // Si no existe en el tema, usar el del plugin
            $plugin_template = $plugin_templates_path . 'single-gc_grupo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para single gc_ciclo
        if (is_singular('gc_ciclo')) {
            $custom_theme = locate_template('grupos-consumo/single-ciclo.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single-gc_ciclo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para single gc_productor
        if (is_singular('gc_productor')) {
            $custom_theme = locate_template('grupos-consumo/single-gc_productor.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single-gc_productor.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Template para single gc_producto
        if (is_singular('gc_producto')) {
            $custom_theme = locate_template('grupos-consumo/single-gc_producto.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'single-gc_producto.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        // Templates para archive
        if (is_post_type_archive('gc_grupo')) {
            $custom_theme = locate_template('grupos-consumo/archive-gc_grupo.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'archive-gc_grupo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_post_type_archive('gc_ciclo')) {
            $custom_theme = locate_template('grupos-consumo/archive-ciclo.php');
            if ($custom_theme) {
                return $custom_theme;
            }

            $plugin_template = $plugin_templates_path . 'archive-gc_ciclo.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Renderizar carrito flotante en el footer
     *
     * Se inyecta automaticamente en todas las paginas donde el usuario esta logueado
     * y hay productos de grupos de consumo disponibles.
     */
    public function renderizar_carrito_flotante() {
        // Solo mostrar si el usuario esta logueado
        if (!is_user_logged_in()) {
            return;
        }

        // No mostrar en admin
        if (is_admin()) {
            return;
        }

        // Verificar si estamos en una pagina relacionada con grupos de consumo
        // o si hay productos de gc_producto disponibles
        $mostrar_carrito = false;

        if (is_singular(['gc_producto', 'gc_productor', 'gc_ciclo', 'gc_grupo'])) {
            $mostrar_carrito = true;
        }

        if (is_post_type_archive(['gc_producto', 'gc_productor', 'gc_ciclo', 'gc_grupo'])) {
            $mostrar_carrito = true;
        }

        // Verificar si hay shortcode de GC en la pagina
        global $post;
        if ($post && (
            has_shortcode($post->post_content, 'gc_catalogo') ||
            has_shortcode($post->post_content, 'gc_carrito') ||
            has_shortcode($post->post_content, 'gc_productos') ||
            has_shortcode($post->post_content, 'gc_mi_pedido')
        )) {
            $mostrar_carrito = true;
        }

        if (!$mostrar_carrito) {
            return;
        }

        // Encolar assets del catalogo
        $this->encolar_assets(true);

        // Preparar datos
        global $wpdb;
        $usuario_id = get_current_user_id();

        // Obtener items del carrito
        $items_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$wpdb->prefix}flavor_gc_lista_compra l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d
             ORDER BY l.fecha_agregado DESC
             LIMIT 10",
            $usuario_id
        ));

        $items_carrito = [];
        $total_carrito = 0;

        foreach ($items_raw as $item) {
            $precio = floatval(get_post_meta($item->producto_id, '_gc_precio', true));
            $subtotal = $precio * $item->cantidad;
            $total_carrito += $subtotal;

            $items_carrito[] = [
                'id' => $item->id,
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre,
                'cantidad' => floatval($item->cantidad),
                'precio' => $precio,
                'imagen' => get_the_post_thumbnail_url($item->producto_id, 'thumbnail'),
            ];
        }

        // Contar total de items
        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_gc_lista_compra WHERE usuario_id = %d",
            $usuario_id
        ));

        // Obtener ciclo activo
        $ciclo_activo = $this->obtener_ciclo_activo();

        // Configuracion del modulo
        $opciones = get_option('flavor_chat_modules', []);
        $porcentaje_gestion = floatval($opciones['grupos_consumo']['settings']['porcentaje_gestion'] ?? 5);

        // Cargar template
        $ruta_template = dirname(dirname(__FILE__)) . '/templates/carrito-flotante.php';
        if (file_exists($ruta_template)) {
            $args = [
                'items_carrito' => $items_carrito,
                'total_carrito' => $total_carrito,
                'total_items' => intval($total_items),
                'ciclo_activo' => $ciclo_activo,
                'porcentaje_gestion' => $porcentaje_gestion,
                'url_carrito' => Flavor_Platform_Helpers::get_action_url('grupos-consumo', 'mi-pedido'),
            ];
            include $ruta_template;
        }
    }
}
