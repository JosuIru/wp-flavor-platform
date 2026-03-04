<?php
/**
 * Módulo WooCommerce para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de integración con WooCommerce
 */
class Flavor_Chat_WooCommerce_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'woocommerce';
        $this->name = 'WooCommerce'; // Translation loaded on init
        $this->description = 'Integración con WooCommerce: carrito, productos, pedidos y más.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return class_exists('WooCommerce');
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!class_exists('WooCommerce')) {
            return __('WooCommerce no está instalado o activado.', 'flavor-chat-ia');
        }
        return '';
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
    public function init() {
        // Hooks específicos de WooCommerce si es necesario
        add_action('woocommerce_cart_updated', [$this, 'on_cart_updated']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes(): void {
        $namespace = 'flavor/v1';

        // Productos
        register_rest_route($namespace, '/woocommerce/productos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_productos'],
            'permission_callback' => '__return_true',
        ]);

        // Producto individual
        register_rest_route($namespace, '/woocommerce/productos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_producto'],
            'permission_callback' => '__return_true',
        ]);

        // Categorías
        register_rest_route($namespace, '/woocommerce/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_categorias'],
            'permission_callback' => '__return_true',
        ]);

        // Carrito
        register_rest_route($namespace, '/woocommerce/carrito', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_carrito'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        // Mis pedidos
        register_rest_route($namespace, '/woocommerce/mis-pedidos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_mis_pedidos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    /**
     * Verifica si el usuario está logueado
     */
    public function check_user_logged_in(): bool {
        return is_user_logged_in();
    }

    /**
     * API: Obtener productos
     */
    public function api_get_productos(\WP_REST_Request $request): \WP_REST_Response {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
        ];

        if ($categoria = $request->get_param('categoria')) {
            $args['tax_query'] = [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $categoria]];
        }

        $query = new \WP_Query($args);
        $productos = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) continue;

            $productos[] = [
                'id' => $product->get_id(),
                'nombre' => $product->get_name(),
                'precio' => $product->get_price(),
                'precio_html' => $product->get_price_html(),
                'imagen' => wp_get_attachment_url($product->get_image_id()),
                'en_stock' => $product->is_in_stock(),
            ];
        }

        return new \WP_REST_Response(['productos' => $productos, 'total' => $query->found_posts]);
    }

    /**
     * API: Obtener producto
     */
    public function api_get_producto(\WP_REST_Request $request): \WP_REST_Response {
        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product) {
            return new \WP_REST_Response(['error' => 'Producto no encontrado'], 404);
        }

        return new \WP_REST_Response([
            'id' => $product->get_id(),
            'nombre' => $product->get_name(),
            'descripcion' => $product->get_description(),
            'descripcion_corta' => $product->get_short_description(),
            'precio' => $product->get_price(),
            'precio_regular' => $product->get_regular_price(),
            'precio_rebajado' => $product->get_sale_price(),
            'imagen' => wp_get_attachment_url($product->get_image_id()),
            'galeria' => array_map('wp_get_attachment_url', $product->get_gallery_image_ids()),
            'en_stock' => $product->is_in_stock(),
            'stock' => $product->get_stock_quantity(),
        ]);
    }

    /**
     * API: Obtener categorías
     */
    public function api_get_categorias(\WP_REST_Request $request): \WP_REST_Response {
        $categorias = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);

        $lista = [];
        foreach ($categorias as $cat) {
            $lista[] = [
                'id' => $cat->term_id,
                'nombre' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count,
            ];
        }

        return new \WP_REST_Response(['categorias' => $lista]);
    }

    /**
     * API: Obtener carrito
     */
    public function api_get_carrito(\WP_REST_Request $request): \WP_REST_Response {
        if (!function_exists('WC') || !WC()->cart) {
            return new \WP_REST_Response(['error' => 'WooCommerce no disponible'], 500);
        }

        $cart = WC()->cart;
        $items = [];

        foreach ($cart->get_cart() as $key => $item) {
            $product = $item['data'];
            $items[] = [
                'key' => $key,
                'product_id' => $item['product_id'],
                'nombre' => $product->get_name(),
                'cantidad' => $item['quantity'],
                'precio' => $product->get_price(),
                'subtotal' => $cart->get_product_subtotal($product, $item['quantity']),
            ];
        }

        return new \WP_REST_Response([
            'items' => $items,
            'total' => $cart->get_cart_total(),
            'items_count' => $cart->get_cart_contents_count(),
        ]);
    }

    /**
     * API: Obtener mis pedidos
     */
    public function api_get_mis_pedidos(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = get_current_user_id();

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => $request->get_param('per_page') ?: 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $pedidos = [];
        foreach ($orders as $order) {
            $pedidos[] = [
                'id' => $order->get_id(),
                'numero' => $order->get_order_number(),
                'estado' => $order->get_status(),
                'total' => $order->get_total(),
                'fecha' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'items_count' => $order->get_item_count(),
            ];
        }

        return new \WP_REST_Response(['pedidos' => $pedidos]);
    }

    /**
     * Callback cuando el carrito se actualiza
     */
    public function on_cart_updated() {
        // Podemos usar esto para notificaciones en tiempo real
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'ver_carrito' => [
                'description' => 'Ver contenido del carrito',
                'params' => [],
            ],
            'anadir_al_carrito' => [
                'description' => 'Añadir producto al carrito',
                'params' => ['product_id', 'quantity', 'variation_id'],
            ],
            'eliminar_del_carrito' => [
                'description' => 'Eliminar producto del carrito',
                'params' => ['cart_item_key', 'product_id', 'vaciar_todo'],
            ],
            'actualizar_cantidad' => [
                'description' => 'Actualizar cantidad de un producto en el carrito',
                'params' => ['cart_item_key', 'quantity'],
            ],
            'aplicar_cupon' => [
                'description' => 'Aplicar cupón de descuento',
                'params' => ['codigo_cupon'],
            ],
            'buscar_productos' => [
                'description' => 'Buscar productos en la tienda',
                'params' => ['busqueda', 'categoria', 'limite'],
            ],
            'ver_producto' => [
                'description' => 'Ver detalles de un producto',
                'params' => ['product_id', 'slug'],
            ],
            'consultar_pedido' => [
                'description' => 'Consultar estado de un pedido',
                'params' => ['order_id', 'email'],
            ],
            'ver_categorias' => [
                'description' => 'Ver categorías de productos',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'buscar_productos',
            'listado' => 'buscar_productos',
            'explorar' => 'buscar_productos',
            'buscar' => 'buscar_productos',
            'detalle' => 'ver_producto',
            'ver' => 'ver_producto',
            'carrito' => 'ver_carrito',
            'anadir' => 'anadir_al_carrito',
            'agregar' => 'anadir_al_carrito',
            'eliminar' => 'eliminar_del_carrito',
            'actualizar' => 'actualizar_cantidad',
            'cupon' => 'aplicar_cupon',
            'pedido' => 'consultar_pedido',
            'categorias' => 'ver_categorias',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Ver carrito
     */
    private function action_ver_carrito($params) {
        if (!function_exists('WC') || !WC()->cart) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está disponible.', 'flavor-chat-ia'),
            ];
        }

        $cart = WC()->cart;
        $items = [];

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'] ?? 0;

            $item_data = [
                'cart_item_key' => $cart_item_key,
                'product_id' => $product_id,
                'nombre' => $product->get_name(),
                'cantidad' => $cart_item['quantity'],
                'precio_unitario' => floatval($product->get_price()),
                'precio_unitario_formateado' => $this->format_price($product->get_price()),
                'subtotal' => floatval($cart_item['line_total']),
                'subtotal_formateado' => $this->format_price($cart_item['line_total']),
                'imagen' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'url' => get_permalink($product_id),
            ];

            // Información de variación
            if ($variation_id > 0) {
                $item_data['variation_id'] = $variation_id;
                $item_data['atributos'] = $cart_item['variation'] ?? [];
            }

            $items[] = $item_data;
        }

        // Cupones aplicados
        $cupones = [];
        foreach ($cart->get_applied_coupons() as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            $cupones[] = [
                'codigo' => $coupon_code,
                'descuento' => $this->format_price($cart->get_coupon_discount_amount($coupon_code)),
            ];
        }

        return [
            'success' => true,
            'carrito' => [
                'items' => $items,
                'total_items' => $cart->get_cart_contents_count(),
                'subtotal' => floatval($cart->get_subtotal()),
                'subtotal_formateado' => $this->format_price($cart->get_subtotal()),
                'descuentos' => floatval($cart->get_discount_total()),
                'descuentos_formateado' => $this->format_price($cart->get_discount_total()),
                'envio' => floatval($cart->get_shipping_total()),
                'envio_formateado' => $this->format_price($cart->get_shipping_total()),
                'impuestos' => floatval($cart->get_total_tax()),
                'impuestos_formateado' => $this->format_price($cart->get_total_tax()),
                'total' => floatval($cart->get_total('edit')),
                'total_formateado' => $this->format_price($cart->get_total('edit')),
                'cupones' => $cupones,
                'url_carrito' => wc_get_cart_url(),
                'url_checkout' => wc_get_checkout_url(),
            ],
            'vacio' => $cart->is_empty(),
            'cart_updated' => false,
        ];
    }

    /**
     * Acción: Añadir al carrito
     */
    private function action_anadir_al_carrito($params) {
        if (!function_exists('WC') || !WC()->cart) {
            return [
                'success' => false,
                'error' => __('ID de producto no válido.', 'flavor-chat-ia'),
            ];
        }

        $product_id = intval($params['product_id'] ?? 0);
        $quantity = intval($params['quantity'] ?? 1);
        $variation_id = intval($params['variation_id'] ?? 0);
        $variation = $params['variation'] ?? [];

        if ($product_id <= 0) {
            return [
                'success' => false,
                'error' => __('ID de producto inválido.', 'flavor-chat-ia'),
            ];
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return [
                'success' => false,
                'error' => __('Producto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar stock
        if (!$product->is_in_stock()) {
            return [
                'success' => false,
                'error' => __('Este producto no está disponible actualmente.', 'flavor-chat-ia'),
            ];
        }

        if (!$product->has_enough_stock($quantity)) {
            $stock_qty = $product->get_stock_quantity();
            return [
                'success' => false,
                'error' => sprintf('Stock insuficiente. Disponibles: %d', $stock_qty),
            ];
        }

        // Verificar si es variable y necesita variación
        if ($product->is_type('variable') && $variation_id <= 0) {
            $available_variations = $product->get_available_variations();
            $attributes = [];

            foreach ($product->get_variation_attributes() as $attr_name => $options) {
                $attributes[] = [
                    'nombre' => wc_attribute_label($attr_name),
                    'opciones' => $options,
                ];
            }

            return [
                'success' => false,
                'error' => __('Este producto requiere seleccionar una variación.', 'flavor-chat-ia'),
                'requiere_variacion' => true,
                'atributos' => $attributes,
                'instrucciones' => __('Pregunta al usuario qué variación prefiere.', 'flavor-chat-ia'),
            ];
        }

        // Añadir al carrito
        try {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

            if ($cart_item_key) {
                return [
                    'success' => true,
                    'mensaje' => sprintf(
                        /* translators: %s: nombre del producto */
                        __('%s añadido al carrito.', 'flavor-chat-ia'),
                        $product->get_name()
                    ),
                    'cart_item_key' => $cart_item_key,
                    'producto' => [
                        'id' => $product_id,
                        'nombre' => $product->get_name(),
                        'cantidad' => $quantity,
                        'precio' => $this->format_price($product->get_price()),
                    ],
                    'carrito_total' => $this->format_price(WC()->cart->get_total('edit')),
                    'carrito_items' => WC()->cart->get_cart_contents_count(),
                    'url_carrito' => wc_get_cart_url(),
                    'cart_updated' => true,
                ];
            }

            return [
                'success' => false,
                'error' => __('No se pudo añadir el producto al carrito.', 'flavor-chat-ia'),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Acción: Eliminar del carrito
     */
    private function action_eliminar_del_carrito($params) {
        if (!function_exists('WC') || !WC()->cart) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está disponible', 'flavor-chat-ia'),
            ];
        }

        $cart = WC()->cart;

        // Vaciar todo
        if (!empty($params['vaciar_todo'])) {
            $cart->empty_cart();
            return [
                'success' => true,
                'mensaje' => __('Carrito vaciado.', 'flavor-chat-ia'),
                'cart_updated' => true,
            ];
        }

        $cart_item_key = $params['cart_item_key'] ?? '';
        $product_id = intval($params['product_id'] ?? 0);

        // Buscar por cart_item_key
        if (!empty($cart_item_key)) {
            if ($cart->remove_cart_item($cart_item_key)) {
                return [
                    'success' => true,
                    'mensaje' => __('Producto eliminado del carrito.', 'flavor-chat-ia'),
                    'cart_updated' => true,
                ];
            }
        }

        // Buscar por product_id
        if ($product_id > 0) {
            foreach ($cart->get_cart() as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    if ($cart->remove_cart_item($key)) {
                        return [
                            'success' => true,
                            'mensaje' => __('Carrito vaciado.', 'flavor-chat-ia'),
                            'cart_updated' => true,
                        ];
                    }
                }
            }
        }

        return [
            'success' => false,
            'error' => __('No se pudo eliminar el producto del carrito.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Actualizar cantidad
     */
    private function action_actualizar_cantidad($params) {
        if (!function_exists('WC') || !WC()->cart) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está disponible.', 'flavor-chat-ia'),
            ];
        }

        $cart_item_key = $params['cart_item_key'] ?? '';
        $quantity = intval($params['quantity'] ?? 0);

        if (empty($cart_item_key)) {
            return [
                'success' => false,
                'error' => __('Producto no encontrado en el carrito.', 'flavor-chat-ia'),
            ];
        }

        if ($quantity <= 0) {
            return $this->action_eliminar_del_carrito(['cart_item_key' => $cart_item_key]);
        }

        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        if (!$cart_item) {
            return [
                'success' => false,
                'error' => __('Producto no encontrado en el carrito.', 'flavor-chat-ia'),
            ];
        }

        // Verificar stock
        $product = $cart_item['data'];
        if (!$product->has_enough_stock($quantity)) {
            return [
                'success' => false,
                'error' => sprintf('Stock insuficiente. Máximo disponible: %d', $product->get_stock_quantity()),
            ];
        }

        WC()->cart->set_quantity($cart_item_key, $quantity);

        return [
            'success' => true,
            'mensaje' => sprintf('Cantidad actualizada a %d.', $quantity),
            'carrito_total' => $this->format_price(WC()->cart->get_total('edit')),
            'cart_updated' => true,
        ];
    }

    /**
     * Acción: Aplicar cupón
     */
    private function action_aplicar_cupon($params) {
        if (!function_exists('WC') || !WC()->cart) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está disponible.', 'flavor-chat-ia'),
            ];
        }

        $codigo = sanitize_text_field($params['codigo_cupon'] ?? '');

        if (empty($codigo)) {
            return [
                'success' => false,
                'error' => __('Debes proporcionar un código de cupón.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya está aplicado
        if (WC()->cart->has_discount($codigo)) {
            return [
                'success' => false,
                'error' => __('Este cupón ya está aplicado.', 'flavor-chat-ia'),
            ];
        }

        // Aplicar cupón
        $result = WC()->cart->apply_coupon($codigo);

        if ($result) {
            $coupon = new WC_Coupon($codigo);
            $discount = WC()->cart->get_coupon_discount_amount($codigo);

            return [
                'success' => true,
                'mensaje' => sprintf('Cupón "%s" aplicado.', $codigo),
                'descuento' => $this->format_price($discount),
                'nuevo_total' => $this->format_price(WC()->cart->get_total('edit')),
                'cart_updated' => true,
            ];
        }

        // Obtener mensajes de error
        $notices = wc_get_notices('error');
        $error_msg = !empty($notices) ? strip_tags($notices[0]['notice']) : 'Cupón no válido.';
        wc_clear_notices();

        return [
            'success' => false,
            'error' => $error_msg,
        ];
    }

    /**
     * Acción: Buscar productos
     */
    private function action_buscar_productos($params) {
        $busqueda = sanitize_text_field($params['busqueda'] ?? '');
        $categoria = sanitize_text_field($params['categoria'] ?? '');
        $limite = intval($params['limite'] ?? 10);
        $limite = min($limite, 20); // Máximo 20

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'instock',
                ],
            ],
        ];

        if (!empty($busqueda)) {
            $args['s'] = $busqueda;
        }

        if (!empty($categoria)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $categoria,
                ],
            ];
        }

        $query = new WP_Query($args);
        $productos = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);

            if (!$product) {
                continue;
            }

            $producto_data = [
                'id' => $post->ID,
                'nombre' => $product->get_name(),
                'precio' => floatval($product->get_price()),
                'precio_formateado' => $this->format_price($product->get_price()),
                'descripcion_corta' => wp_trim_words($product->get_short_description(), 20),
                'en_stock' => $product->is_in_stock(),
                'stock_cantidad' => $product->get_stock_quantity(),
                'url' => get_permalink($post->ID),
                'imagen' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'tipo' => $product->get_type(),
            ];

            // Precio rebajado
            if ($product->is_on_sale()) {
                $producto_data['en_oferta'] = true;
                $producto_data['precio_regular'] = $this->format_price($product->get_regular_price());
            }

            // Categorías
            $cats = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'names']);
            $producto_data['categorias'] = $cats;

            $productos[] = $producto_data;
        }

        return [
            'success' => true,
            'productos' => $productos,
            'total_encontrados' => $query->found_posts,
            'mostrando' => count($productos),
        ];
    }

    /**
     * Acción: Ver producto
     */
    private function action_ver_producto($params) {
        $product_id = intval($params['product_id'] ?? 0);
        $slug = sanitize_text_field($params['slug'] ?? '');

        if ($product_id <= 0 && !empty($slug)) {
            $product_id = wc_get_product_id_by_sku($slug);
            if (!$product_id) {
                $post = get_page_by_path($slug, OBJECT, 'product');
                if ($post) {
                    $product_id = $post->ID;
                }
            }
        }

        if ($product_id <= 0) {
            return [
                'success' => false,
                'error' => __('ID de producto inválido.', 'flavor-chat-ia'),
            ];
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return [
                'success' => false,
                'error' => __('Producto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $data = [
            'id' => $product_id,
            'nombre' => $product->get_name(),
            'descripcion' => $product->get_description(),
            'descripcion_corta' => $product->get_short_description(),
            'precio' => floatval($product->get_price()),
            'precio_formateado' => $this->format_price($product->get_price()),
            'sku' => $product->get_sku(),
            'en_stock' => $product->is_in_stock(),
            'stock_cantidad' => $product->get_stock_quantity(),
            'url' => get_permalink($product_id),
            'imagen' => wp_get_attachment_image_url($product->get_image_id(), 'large'),
            'tipo' => $product->get_type(),
            'peso' => $product->get_weight(),
            'dimensiones' => $product->get_dimensions(false),
        ];

        // Precio de oferta
        if ($product->is_on_sale()) {
            $data['en_oferta'] = true;
            $data['precio_regular'] = $this->format_price($product->get_regular_price());
            $data['precio_oferta'] = $this->format_price($product->get_sale_price());
        }

        // Categorías
        $cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
        $data['categorias'] = $cats;

        // Etiquetas
        $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'names']);
        $data['etiquetas'] = $tags;

        // Galería de imágenes
        $gallery_ids = $product->get_gallery_image_ids();
        $data['galeria'] = array_map(function($id) {
            return wp_get_attachment_image_url($id, 'medium');
        }, $gallery_ids);

        // Variaciones
        if ($product->is_type('variable')) {
            $data['es_variable'] = true;
            $data['atributos'] = [];

            foreach ($product->get_variation_attributes() as $attr_name => $options) {
                $data['atributos'][] = [
                    'nombre' => wc_attribute_label($attr_name),
                    'opciones' => $options,
                ];
            }

            $data['precio_desde'] = $this->format_price($product->get_variation_price('min'));
            $data['precio_hasta'] = $this->format_price($product->get_variation_price('max'));
        }

        // Productos relacionados
        $related_ids = wc_get_related_products($product_id, 4);
        $data['relacionados'] = array_map(function($id) {
            $p = wc_get_product($id);
            return [
                'id' => $id,
                'nombre' => $p->get_name(),
                'precio' => $this->format_price($p->get_price()),
            ];
        }, $related_ids);

        return [
            'success' => true,
            'producto' => $data,
        ];
    }

    /**
     * Acción: Consultar pedido
     */
    private function action_consultar_pedido($params) {
        $order_id = intval($params['order_id'] ?? 0);
        $email = sanitize_email($params['email'] ?? '');

        if ($order_id <= 0) {
            return [
                'success' => false,
                'error' => __('ID de pedido inválido.', 'flavor-chat-ia'),
            ];
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return [
                'success' => false,
                'error' => __('Pedido no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar email si se proporciona (seguridad)
        if (!empty($email) && $order->get_billing_email() !== $email) {
            return [
                'success' => false,
                'error' => __('No tienes permiso para ver este pedido.', 'flavor-chat-ia'),
            ];
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'nombre' => $item->get_name(),
                'cantidad' => $item->get_quantity(),
                'total' => $this->format_price($item->get_total()),
            ];
        }

        $status_labels = wc_get_order_statuses();
        $status_key = 'wc-' . $order->get_status();
        $status_label = $status_labels[$status_key] ?? $order->get_status();

        return [
            'success' => true,
            'pedido' => [
                'numero' => $order->get_order_number(),
                'fecha' => $order->get_date_created()->date_i18n('d/m/Y H:i'),
                'estado' => $status_label,
                'estado_key' => $order->get_status(),
                'items' => $items,
                'subtotal' => $this->format_price($order->get_subtotal()),
                'envio' => $this->format_price($order->get_shipping_total()),
                'descuento' => $this->format_price($order->get_discount_total()),
                'total' => $this->format_price($order->get_total()),
                'metodo_pago' => $order->get_payment_method_title(),
                'direccion_envio' => $order->get_formatted_shipping_address(),
                'notas' => $order->get_customer_note(),
            ],
        ];
    }

    /**
     * Acción: Ver categorías
     */
    private function action_ver_categorias($params) {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0, // Solo categorías padre
        ]);

        $result = [];

        foreach ($categories as $cat) {
            $cat_data = [
                'id' => $cat->term_id,
                'nombre' => $cat->name,
                'slug' => $cat->slug,
                'descripcion' => $cat->description,
                'productos' => $cat->count,
                'url' => get_term_link($cat),
            ];

            // Imagen de categoría
            $thumbnail_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $cat_data['imagen'] = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }

            // Subcategorías
            $subcats = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'parent' => $cat->term_id,
            ]);

            if (!empty($subcats) && !is_wp_error($subcats)) {
                $cat_data['subcategorias'] = array_map(function($sub) {
                    return [
                        'id' => $sub->term_id,
                        'nombre' => $sub->name,
                        'slug' => $sub->slug,
                        'productos' => $sub->count,
                    ];
                }, $subcats);
            }

            $result[] = $cat_data;
        }

        return [
            'success' => true,
            'categorias' => $result,
            'total' => count($result),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'ver_carrito',
                'description' => 'Ver el contenido actual del carrito de compras. Muestra productos, cantidades, precios y totales.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new stdClass(),
                    'required' => [],
                ],
            ],
            [
                'name' => 'anadir_al_carrito',
                'description' => 'Añadir un producto al carrito de compras.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'product_id' => [
                            'type' => 'integer',
                            'description' => 'ID del producto a añadir',
                        ],
                        'quantity' => [
                            'type' => 'integer',
                            'description' => 'Cantidad a añadir (por defecto 1)',
                        ],
                        'variation_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la variación (para productos variables)',
                        ],
                    ],
                    'required' => ['product_id'],
                ],
            ],
            [
                'name' => 'eliminar_del_carrito',
                'description' => 'Eliminar un producto del carrito o vaciar todo el carrito.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cart_item_key' => [
                            'type' => 'string',
                            'description' => 'Clave del item en el carrito',
                        ],
                        'product_id' => [
                            'type' => 'integer',
                            'description' => 'ID del producto a eliminar',
                        ],
                        'vaciar_todo' => [
                            'type' => 'boolean',
                            'description' => 'Si es true, vacía todo el carrito',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'actualizar_cantidad',
                'description' => 'Actualizar la cantidad de un producto en el carrito.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cart_item_key' => [
                            'type' => 'string',
                            'description' => 'Clave del item en el carrito',
                        ],
                        'quantity' => [
                            'type' => 'integer',
                            'description' => 'Nueva cantidad',
                        ],
                    ],
                    'required' => ['cart_item_key', 'quantity'],
                ],
            ],
            [
                'name' => 'aplicar_cupon',
                'description' => 'Aplicar un código de cupón de descuento al carrito.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'codigo_cupon' => [
                            'type' => 'string',
                            'description' => 'Código del cupón a aplicar',
                        ],
                    ],
                    'required' => ['codigo_cupon'],
                ],
            ],
            [
                'name' => 'buscar_productos',
                'description' => 'Buscar productos en la tienda por nombre, descripción o categoría.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Slug de la categoría para filtrar',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados (máx 20)',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'ver_producto',
                'description' => 'Ver información detallada de un producto específico.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'product_id' => [
                            'type' => 'integer',
                            'description' => 'ID del producto',
                        ],
                        'slug' => [
                            'type' => 'string',
                            'description' => 'Slug o SKU del producto',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'consultar_pedido',
                'description' => 'Consultar el estado de un pedido existente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => [
                            'type' => 'integer',
                            'description' => 'Número del pedido',
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente (para verificación)',
                        ],
                    ],
                    'required' => ['order_id'],
                ],
            ],
            [
                'name' => 'ver_categorias',
                'description' => 'Ver las categorías de productos disponibles en la tienda.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new stdClass(),
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        $shop_name = get_bloginfo('name');
        $currency = get_woocommerce_currency_symbol();

        $knowledge = "Eres el asistente virtual de la tienda online {$shop_name}.\n\n";
        $knowledge .= "CAPACIDADES DE TIENDA:\n";
        $knowledge .= "- Puedes buscar productos, ver detalles y mostrar categorías\n";
        $knowledge .= "- Puedes gestionar el carrito: añadir, eliminar productos y aplicar cupones\n";
        $knowledge .= "- Puedes consultar el estado de pedidos (requiere número de pedido)\n";
        $knowledge .= "- La moneda de la tienda es: {$currency}\n\n";

        $knowledge .= "INSTRUCCIONES IMPORTANTES:\n";
        $knowledge .= "- Siempre muestra precios con el formato de la tienda\n";
        $knowledge .= "- Para productos variables, pregunta qué variación quiere el cliente\n";
        $knowledge .= "- Verifica stock antes de confirmar disponibilidad\n";
        $knowledge .= "- Si el cliente quiere finalizar compra, indícale que vaya al carrito o checkout\n";
        $knowledge .= "- No proceses pagos ni solicites datos de tarjeta - eso se hace en checkout\n";

        return $knowledge;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo puedo hacer un pedido?',
                'respuesta' => 'Puedo ayudarte a buscar productos y añadirlos al carrito. Cuando estés listo, te guiaré al proceso de pago.',
            ],
            [
                'pregunta' => '¿Cuánto cuesta el envío?',
                'respuesta' => 'El coste de envío depende de tu ubicación y el peso del pedido. Lo verás en el carrito antes de pagar.',
            ],
            [
                'pregunta' => '¿Dónde está mi pedido?',
                'respuesta' => 'Dame tu número de pedido y tu email, y te muestro el estado actual.',
            ],
            [
                'pregunta' => '¿Aceptáis devoluciones?',
                'respuesta' => 'Consulta nuestra política de devoluciones en la web. Si tienes un problema con un pedido, puedo derivarte a atención al cliente.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Tienda', 'flavor-chat-ia'),
                'description' => __('Sección hero con productos destacados', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Nuestra Tienda', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Descubre nuestros productos', 'flavor-chat-ia'),
                    ],
                    'mostrar_ofertas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar ofertas destacadas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'woocommerce/hero',
            ],
            'productos_grid' => [
                'label' => __('Grid de Productos', 'flavor-chat-ia'),
                'description' => __('Listado de productos WooCommerce', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Productos', 'flavor-chat-ia'),
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Mostrar', 'flavor-chat-ia'),
                        'options' => ['todos', 'destacados', 'ofertas', 'nuevos'],
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
                'template' => 'woocommerce/productos-grid',
            ],
            'categorias' => [
                'label' => __('Categorías de Productos', 'flavor-chat-ia'),
                'description' => __('Grid de categorías de la tienda', 'flavor-chat-ia'),
                'category' => 'navigation',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Categorías', 'flavor-chat-ia'),
                    ],
                    'mostrar_imagen' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar imagen', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_contador' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar contador', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'woocommerce/categorias',
            ],
            'ofertas_banner' => [
                'label' => __('Banner de Ofertas', 'flavor-chat-ia'),
                'description' => __('Banner promocional con ofertas', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-tag',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¡Ofertas Especiales!', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'text',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Hasta 50% de descuento', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Ver Ofertas', 'flavor-chat-ia'),
                    ],
                    'color_fondo' => [
                        'type' => 'color',
                        'label' => __('Color de fondo', 'flavor-chat-ia'),
                        'default' => '#ef4444',
                    ],
                ],
                'template' => 'woocommerce/ofertas-banner',
            ],
            'carrito_mini' => [
                'label' => __('Mini Carrito', 'flavor-chat-ia'),
                'description' => __('Widget de carrito flotante', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-cart',
                'fields' => [
                    'mostrar_total' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar total', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_items' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar número de items', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'woocommerce/carrito-mini',
            ],
        ];
    }

    /**
     * Configuración para el Panel de Administración Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'woocommerce',
            'label' => __('WooCommerce', 'flavor-chat-ia'),
            'icon' => 'dashicons-cart',
            'capability' => 'manage_woocommerce',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'flavor-woocommerce-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'flavor-woocommerce-pedidos',
                    'titulo' => __('Pedidos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_pedidos'],
                    'badge' => [$this, 'contar_pedidos_pendientes'],
                ],
                [
                    'slug' => 'flavor-woocommerce-productos',
                    'titulo' => __('Productos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_productos'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_tienda'],
        ];
    }

    /**
     * Cuenta los pedidos pendientes de procesar
     *
     * @return int
     */
    public function contar_pedidos_pendientes() {
        if (!function_exists('wc_get_orders')) {
            return 0;
        }

        $pedidos_pendientes = wc_get_orders([
            'status' => ['pending', 'processing', 'on-hold'],
            'return' => 'ids',
            'limit' => -1,
        ]);

        return count($pedidos_pendientes);
    }

    /**
     * Renderiza el dashboard de administración de WooCommerce
     */
    public function render_admin_dashboard() {
        $ruta_vista = plugin_dir_path(__FILE__) . 'views/dashboard.php';

        if (file_exists($ruta_vista)) {
            include $ruta_vista;
        } else {
            $this->render_vista_placeholder(__('Dashboard de WooCommerce', 'flavor-chat-ia'));
        }
    }

    /**
     * Renderizar página dashboard con vista completa
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            $this->render_admin_dashboard();
        }
    }

    /**
     * Renderiza la página de pedidos
     */
    public function render_admin_pedidos() {
        $ruta_vista = plugin_dir_path(__FILE__) . 'views/pedidos.php';

        if (file_exists($ruta_vista)) {
            include $ruta_vista;
        } else {
            $this->render_vista_placeholder(__('Gestión de Pedidos', 'flavor-chat-ia'));
        }
    }

    /**
     * Renderiza la página de productos
     */
    public function render_admin_productos() {
        $ruta_vista = plugin_dir_path(__FILE__) . 'views/productos.php';

        if (file_exists($ruta_vista)) {
            include $ruta_vista;
        } else {
            $this->render_vista_placeholder(__('Gestión de Productos', 'flavor-chat-ia'));
        }
    }

    /**
     * Renderiza un placeholder cuando la vista no existe
     *
     * @param string $titulo_pagina Título de la página
     */
    private function render_vista_placeholder($titulo_pagina) {
        $estadisticas = $this->get_estadisticas_tienda();
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header($titulo_pagina); ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('La plantilla específica no está disponible en esta instalación. Se muestra un resumen operativo para mantener el módulo utilizable.', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flavor-woo-fallback-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0;">
                <div class="card"><h3><?php esc_html_e('Pedidos hoy', 'flavor-chat-ia'); ?></h3><p><?php echo esc_html($estadisticas['pedidos_hoy']); ?></p></div>
                <div class="card"><h3><?php esc_html_e('Ventas hoy', 'flavor-chat-ia'); ?></h3><p><?php echo esc_html($estadisticas['ventas_hoy_formateado']); ?></p></div>
                <div class="card"><h3><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></h3><p><?php echo esc_html($estadisticas['pendientes']); ?></p></div>
            </div>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=flavor-woocommerce-dashboard')); ?>"><?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-woocommerce-pedidos')); ?>"><?php esc_html_e('Pedidos', 'flavor-chat-ia'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-woocommerce-productos')); ?>"><?php esc_html_e('Productos', 'flavor-chat-ia'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Renderiza el widget del dashboard principal
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_tienda();
        ?>
        <div class="flavor-widget-woocommerce">
            <div class="widget-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['pedidos_hoy']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Pedidos hoy', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['ventas_hoy_formateado']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Ventas hoy', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($estadisticas['pendientes']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="widget-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-woocommerce-pedidos')); ?>" class="button">
                    <?php esc_html_e('Ver pedidos', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene las estadísticas de la tienda para el dashboard
     *
     * @return array
     */
    public function get_estadisticas_tienda() {
        $estadisticas = [
            'pedidos_hoy' => 0,
            'ventas_hoy' => 0,
            'ventas_hoy_formateado' => $this->format_price(0),
            'pendientes' => 0,
            'productos_total' => 0,
            'productos_sin_stock' => 0,
        ];

        if (!function_exists('wc_get_orders')) {
            return $estadisticas;
        }

        // Pedidos de hoy
        $inicio_hoy = strtotime('today midnight');
        $pedidos_hoy = wc_get_orders([
            'date_created' => '>=' . $inicio_hoy,
            'status' => ['completed', 'processing', 'on-hold'],
            'return' => 'ids',
            'limit' => -1,
        ]);

        $estadisticas['pedidos_hoy'] = count($pedidos_hoy);

        // Ventas de hoy
        $total_ventas_hoy = 0;
        foreach ($pedidos_hoy as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $total_ventas_hoy += floatval($order->get_total());
            }
        }
        $estadisticas['ventas_hoy'] = $total_ventas_hoy;
        $estadisticas['ventas_hoy_formateado'] = $this->format_price($total_ventas_hoy);

        // Pedidos pendientes
        $estadisticas['pendientes'] = $this->contar_pedidos_pendientes();

        // Total de productos
        $args_productos = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        $productos_query = new WP_Query($args_productos);
        $estadisticas['productos_total'] = $productos_query->found_posts;

        // Productos sin stock
        $args_sin_stock = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'outofstock',
                ],
            ],
        ];
        $sin_stock_query = new WP_Query($args_sin_stock);
        $estadisticas['productos_sin_stock'] = $sin_stock_query->found_posts;

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Tienda', 'flavor-chat-ia'),
                'slug' => 'tienda-woo',
                'content' => '<h1>' . __('Tienda', 'flavor-chat-ia') . '</h1>
<p>' . __('Explora nuestros productos', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="woocommerce" action="productos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Categorías', 'flavor-chat-ia'),
                'slug' => 'categorias-tienda',
                'content' => '<h1>' . __('Categorías', 'flavor-chat-ia') . '</h1>
<p>' . __('Navega por categorías de productos', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="woocommerce" action="categorias"]',
                'parent' => 'tienda-woo',
            ],
            [
                'title' => __('Mis Pedidos', 'flavor-chat-ia'),
                'slug' => 'mis-pedidos-woo',
                'content' => '<h1>' . __('Mis Pedidos', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta el estado de tus pedidos', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="woocommerce" action="mis_pedidos"]',
                'parent' => 'tienda-woo',
            ],
            [
                'title' => __('Ofertas', 'flavor-chat-ia'),
                'slug' => 'ofertas-tienda',
                'content' => '<h1>' . __('Ofertas', 'flavor-chat-ia') . '</h1>
<p>' . __('Descubre las mejores ofertas', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="woocommerce" action="ofertas"]',
                'parent' => 'tienda-woo',
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-woocommerce-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Woocommerce_Dashboard_Tab::get_instance();
        }
    }
}
