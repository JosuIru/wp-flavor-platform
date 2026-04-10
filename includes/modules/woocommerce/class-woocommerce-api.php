<?php
/**
 * API REST para WooCommerce (Móvil)
 *
 * Adaptadores simplificados sobre la API nativa de WooCommerce
 * para facilitar integración en apps móviles
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API REST adaptadora para WooCommerce
 */
class Flavor_WooCommerce_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = FLAVOR_PLATFORM_REST_NAMESPACE;

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        // GET /woocommerce/productos - Lista simplificada de productos
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/productos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_productos'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'busqueda' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
                'pagina' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
                'orderby' => [
                    'type' => 'string',
                    'default' => 'date',
                    'enum' => ['date', 'title', 'price', 'popularity', 'rating'],
                ],
                'order' => [
                    'type' => 'string',
                    'default' => 'desc',
                    'enum' => ['asc', 'desc'],
                ],
            ],
        ]);

        // GET /woocommerce/productos/{id} - Detalle de producto
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/productos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_producto'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /woocommerce/carrito - Ver carrito
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/carrito', [
            'methods' => 'GET',
            'callback' => [$this, 'get_carrito'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /woocommerce/carrito/agregar - Agregar al carrito
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/carrito/agregar', [
            'methods' => 'POST',
            'callback' => [$this, 'agregar_al_carrito'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'producto_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'cantidad' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
                'variacion_id' => [
                    'type' => 'integer',
                ],
            ],
        ]);

        // PUT /woocommerce/carrito/actualizar - Actualizar cantidad
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/carrito/actualizar', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_carrito'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'cart_item_key' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'cantidad' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // DELETE /woocommerce/carrito/eliminar - Eliminar del carrito
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/carrito/eliminar', [
            'methods' => 'DELETE',
            'callback' => [$this, 'eliminar_del_carrito'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'cart_item_key' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // DELETE /woocommerce/carrito/vaciar - Vaciar carrito
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/carrito/vaciar', [
            'methods' => 'DELETE',
            'callback' => [$this, 'vaciar_carrito'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /woocommerce/pedidos - Mis pedidos
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/pedidos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pedidos'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // GET /woocommerce/pedidos/{id} - Detalle de pedido
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/pedidos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pedido'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /woocommerce/categorias - Categorías de productos
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /woocommerce/checkout-url - URL de checkout
        flavor_register_rest_route(self::NAMESPACE, '/woocommerce/checkout-url', [
            'methods' => 'GET',
            'callback' => [$this, 'get_checkout_url'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    /**
     * GET /woocommerce/productos
     * Lista productos
     */
    public function get_productos($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $busqueda = $request->get_param('busqueda');
        $categoria = $request->get_param('categoria');
        $limite = $request->get_param('limite');
        $pagina = $request->get_param('pagina');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'paged' => $pagina,
            'orderby' => $orderby,
            'order' => $order,
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

        // Solo productos en stock
        $args['meta_query'] = [
            [
                'key' => '_stock_status',
                'value' => 'instock',
            ],
        ];

        $query = new WP_Query($args);

        $productos = [];
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $productos[] = $this->formatear_producto($product);
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'productos' => $productos,
            'total' => $query->found_posts,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => $query->max_num_pages,
        ], 200);
    }

    /**
     * GET /woocommerce/productos/{id}
     * Detalle de producto
     */
    public function get_producto($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error(
                'producto_no_encontrado',
                'Producto no encontrado',
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'producto' => $this->formatear_producto($product, true),
        ], 200);
    }

    /**
     * GET /woocommerce/carrito
     * Ver carrito actual
     */
    public function get_carrito($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $cart = WC()->cart;

        $items = [];
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $items[] = [
                'cart_item_key' => $cart_item_key,
                'producto_id' => $cart_item['product_id'],
                'variacion_id' => $cart_item['variation_id'],
                'cantidad' => $cart_item['quantity'],
                'nombre' => $product->get_name(),
                'precio_unitario' => (float) $product->get_price(),
                'subtotal' => (float) $cart_item['line_subtotal'],
                'total' => (float) $cart_item['line_total'],
                'imagen' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'items' => $items,
            'subtotal' => (float) $cart->get_subtotal(),
            'total' => (float) $cart->get_total(''),
            'total_items' => $cart->get_cart_contents_count(),
            'impuestos' => (float) $cart->get_total_tax(),
            'envio' => (float) $cart->get_shipping_total(),
        ], 200);
    }

    /**
     * POST /woocommerce/carrito/agregar
     * Agregar producto al carrito
     */
    public function agregar_al_carrito($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $producto_id = $request->get_param('producto_id');
        $cantidad = $request->get_param('cantidad');
        $variacion_id = $request->get_param('variacion_id');

        // Verificar que el producto existe
        $product = wc_get_product($producto_id);
        if (!$product) {
            return new WP_Error(
                'producto_no_encontrado',
                'Producto no encontrado',
                ['status' => 404]
            );
        }

        // Agregar al carrito
        $cart_item_key = WC()->cart->add_to_cart(
            $producto_id,
            $cantidad,
            $variacion_id
        );

        if (!$cart_item_key) {
            return new WP_Error(
                'error_agregar_carrito',
                'No se pudo agregar el producto al carrito',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'cart_item_key' => $cart_item_key,
            'mensaje' => __('Producto agregado al carrito', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'total_items' => WC()->cart->get_cart_contents_count(),
        ], 200);
    }

    /**
     * PUT /woocommerce/carrito/actualizar
     * Actualizar cantidad de un item
     */
    public function actualizar_carrito($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $cart_item_key = $request->get_param('cart_item_key');
        $cantidad = $request->get_param('cantidad');

        if ($cantidad <= 0) {
            return new WP_Error(
                'cantidad_invalida',
                'La cantidad debe ser mayor a 0',
                ['status' => 400]
            );
        }

        $result = WC()->cart->set_quantity($cart_item_key, $cantidad);

        if (!$result) {
            return new WP_Error(
                'error_actualizar',
                'No se pudo actualizar el carrito',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Carrito actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * DELETE /woocommerce/carrito/eliminar
     * Eliminar item del carrito
     */
    public function eliminar_del_carrito($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $cart_item_key = $request->get_param('cart_item_key');

        $result = WC()->cart->remove_cart_item($cart_item_key);

        if (!$result) {
            return new WP_Error(
                'error_eliminar',
                'No se pudo eliminar el producto',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Producto eliminado del carrito', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * DELETE /woocommerce/carrito/vaciar
     * Vaciar todo el carrito
     */
    public function vaciar_carrito($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        WC()->cart->empty_cart();

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => __('Carrito vaciado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ], 200);
    }

    /**
     * GET /woocommerce/pedidos
     * Mis pedidos
     */
    public function get_pedidos($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado');
        $limite = $request->get_param('limite');

        $args = [
            'customer_id' => $usuario_id,
            'limit' => $limite,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (!empty($estado)) {
            $args['status'] = $estado;
        }

        $orders = wc_get_orders($args);

        $pedidos = array_map([$this, 'formatear_pedido'], $orders);

        return new WP_REST_Response([
            'success' => true,
            'pedidos' => $pedidos,
            'total' => count($pedidos),
        ], 200);
    }

    /**
     * GET /woocommerce/pedidos/{id}
     * Detalle de pedido
     */
    public function get_pedido($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $order_id = $request->get_param('id');
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error(
                'pedido_no_encontrado',
                __('Pedido no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Verificar que pertenece al usuario
        if ($order->get_customer_id() != get_current_user_id()) {
            return new WP_Error(
                'sin_permiso',
                'No tienes permiso para ver este pedido',
                ['status' => 403]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'pedido' => $this->formatear_pedido($order, true),
        ], 200);
    }

    /**
     * GET /woocommerce/categorias
     * Categorías de productos
     */
    public function get_categorias($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        $categorias = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);

        $categorias_formateadas = array_map(function($cat) {
            $thumbnail_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
            return [
                'id' => $cat->term_id,
                'slug' => $cat->slug,
                'nombre' => $cat->name,
                'descripcion' => $cat->description,
                'count' => $cat->count,
                'imagen' => $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '',
            ];
        }, $categorias);

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias_formateadas,
        ], 200);
    }

    /**
     * GET /woocommerce/checkout-url
     * URL de checkout para abrir en webview
     */
    public function get_checkout_url($request) {
        if (!class_exists('WooCommerce')) {
            return new WP_Error(
                'woocommerce_no_activo',
                'WooCommerce no está activo',
                ['status' => 503]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'checkout_url' => wc_get_checkout_url(),
            'cart_url' => wc_get_cart_url(),
        ], 200);
    }

    /**
     * Formatea un producto para la respuesta
     */
    private function formatear_producto($product, $detalle_completo = false) {
        if (!$product) {
            return null;
        }

        $data = [
            'id' => $product->get_id(),
            'nombre' => $product->get_name(),
            'descripcion' => $detalle_completo ? $product->get_description() : wp_trim_words($product->get_short_description(), 20),
            'precio' => (float) $product->get_price(),
            'precio_regular' => (float) $product->get_regular_price(),
            'precio_venta' => $product->get_sale_price() ? (float) $product->get_sale_price() : null,
            'en_oferta' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'en_stock' => $product->is_in_stock(),
            'cantidad_disponible' => $product->get_stock_quantity(),
            'imagen' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
            'categorias' => array_map(function($term) {
                return [
                    'id' => $term->term_id,
                    'nombre' => $term->name,
                    'slug' => $term->slug,
                ];
            }, wp_get_post_terms($product->get_id(), 'product_cat')),
        ];

        if ($detalle_completo) {
            // Galería de imágenes
            $gallery_ids = $product->get_gallery_image_ids();
            $data['imagenes'] = array_map(function($id) {
                return wp_get_attachment_image_url($id, 'large');
            }, $gallery_ids);

            // Atributos
            $data['atributos'] = $product->get_attributes();

            // Variaciones si es producto variable
            if ($product->is_type('variable')) {
                $data['variaciones'] = array_map(function($variation_id) {
                    $variation = wc_get_product($variation_id);
                    return [
                        'id' => $variation->get_id(),
                        'precio' => (float) $variation->get_price(),
                        'en_stock' => $variation->is_in_stock(),
                        'atributos' => $variation->get_variation_attributes(),
                    ];
                }, $product->get_children());
            }
        }

        return $data;
    }

    /**
     * Formatea un pedido para la respuesta
     */
    private function formatear_pedido($order, $detalle_completo = false) {
        if (!$order) {
            return null;
        }

        $data = [
            'id' => $order->get_id(),
            'numero_pedido' => $order->get_order_number(),
            'estado' => $order->get_status(),
            'fecha' => $order->get_date_created()->date('c'),
            'total' => (float) $order->get_total(),
            'total_items' => $order->get_item_count(),
        ];

        if ($detalle_completo) {
            $data['items'] = array_map(function($item) {
                $product = $item->get_product();
                return [
                    'producto_id' => $item->get_product_id(),
                    'nombre' => $item->get_name(),
                    'cantidad' => $item->get_quantity(),
                    'precio_unitario' => (float) $order->get_item_subtotal($item),
                    'total' => (float) $item->get_total(),
                    'imagen' => $product ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : '',
                ];
            }, $order->get_items());

            $data['subtotal'] = (float) $order->get_subtotal();
            $data['envio'] = (float) $order->get_shipping_total();
            $data['impuestos'] = (float) $order->get_total_tax();
            $data['metodo_pago'] = $order->get_payment_method_title();
            $data['direccion_envio'] = $order->get_address('shipping');
            $data['notas'] = $order->get_customer_note();
        }

        return $data;
    }

    /**
     * Verifica autenticación
     */
    public function check_authentication($request) {
        return is_user_logged_in();
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
