<?php
/**
 * AJAX Handlers para Grupos de Consumo
 *
 * Maneja todas las peticiones AJAX del frontend:
 * - Agregar/quitar/actualizar productos en el carrito
 * - Confirmar pedidos
 * - Sincronizar carrito
 * - Cargar mas productos (paginacion)
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar peticiones AJAX de Grupos de Consumo
 */
class Flavor_GC_Ajax_Handlers {

    /**
     * Instancia singleton
     */
    private static $instancia = null;

    /**
     * Nombre de la tabla de lista de compra
     */
    private $tabla_lista;

    /**
     * Nombre de la tabla de pedidos
     */
    private $tabla_pedidos;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';
        $this->tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $this->registrar_handlers();
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Registrar todos los handlers AJAX
     */
    private function registrar_handlers() {
        // Handlers para usuarios autenticados
        $acciones_autenticadas = [
            'gc_agregar_producto',
            'gc_actualizar_cantidad',
            'gc_quitar_producto',
            'gc_obtener_carrito',
            'gc_confirmar_pedido',
            'gc_vaciar_carrito',
            'gc_cargar_mas_productos',
            'gc_sincronizar_carrito',
        ];

        foreach ($acciones_autenticadas as $accion) {
            add_action('wp_ajax_' . $accion, [$this, 'handle_' . str_replace('gc_', '', $accion)]);
        }

        // Handlers para usuarios no autenticados (solo lectura)
        add_action('wp_ajax_nopriv_gc_cargar_mas_productos', [$this, 'handle_cargar_mas_productos']);
    }

    /**
     * Verificar nonce y permisos
     *
     * @param string $accion Accion del nonce
     * @return bool
     */
    private function verificar_seguridad($accion = 'gc_frontend_nonce') {
        if (!check_ajax_referer($accion, 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Error de seguridad. Recarga la pagina e intentalo de nuevo.', 'flavor-chat-ia'),
                'code' => 'invalid_nonce'
            ], 403);
            return false;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesion para realizar esta accion.', 'flavor-chat-ia'),
                'code' => 'not_logged_in',
                'redirect' => wp_login_url(wp_get_referer())
            ], 401);
            return false;
        }

        return true;
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
     * Handler: Agregar producto al carrito
     */
    public function handle_agregar_producto() {
        $this->verificar_seguridad();

        $producto_id = absint($_POST['producto_id'] ?? 0);
        $cantidad = floatval($_POST['cantidad'] ?? 1);
        $usuario_id = get_current_user_id();

        if (!$producto_id) {
            wp_send_json_error([
                'message' => __('Producto no valido.', 'flavor-chat-ia'),
                'code' => 'invalid_product'
            ]);
        }

        // Verificar que el producto existe
        $producto = get_post($producto_id);
        if (!$producto || $producto->post_type !== 'gc_producto') {
            wp_send_json_error([
                'message' => __('El producto no existe.', 'flavor-chat-ia'),
                'code' => 'product_not_found'
            ]);
        }

        // Verificar stock disponible
        $stock = get_post_meta($producto_id, '_gc_stock', true);
        if (!empty($stock) && floatval($stock) < $cantidad) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Stock insuficiente. Solo quedan %s unidades disponibles.', 'flavor-chat-ia'),
                    number_format($stock, 0, ',', '.')
                ),
                'code' => 'insufficient_stock',
                'stock_disponible' => floatval($stock)
            ]);
        }

        // Verificar cantidad minima
        $cantidad_minima = floatval(get_post_meta($producto_id, '_gc_cantidad_minima', true) ?: 1);
        if ($cantidad < $cantidad_minima) {
            $cantidad = $cantidad_minima;
        }

        global $wpdb;

        // Verificar si ya existe en la lista
        $item_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_lista} WHERE usuario_id = %d AND producto_id = %d",
            $usuario_id,
            $producto_id
        ));

        if ($item_existente) {
            // Actualizar cantidad
            $resultado = $wpdb->update(
                $this->tabla_lista,
                [
                    'cantidad' => $cantidad,
                    'fecha_modificado' => current_time('mysql'),
                ],
                [
                    'id' => $item_existente,
                ]
            );
        } else {
            // Insertar nuevo
            $resultado = $wpdb->insert(
                $this->tabla_lista,
                [
                    'usuario_id' => $usuario_id,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'fecha_agregado' => current_time('mysql'),
                    'fecha_modificado' => current_time('mysql'),
                ]
            );
        }

        if ($resultado === false) {
            wp_send_json_error([
                'message' => __('Error al agregar el producto. Intentalo de nuevo.', 'flavor-chat-ia'),
                'code' => 'db_error'
            ]);
        }

        // Obtener datos actualizados del carrito
        $datos_carrito = $this->obtener_datos_carrito($usuario_id);

        wp_send_json_success([
            'message' => __('Producto agregado al pedido.', 'flavor-chat-ia'),
            'producto_id' => $producto_id,
            'cantidad' => $cantidad,
            'carrito' => $datos_carrito,
        ]);
    }

    /**
     * Handler: Actualizar cantidad de un producto
     */
    public function handle_actualizar_cantidad() {
        $this->verificar_seguridad();

        $item_id = absint($_POST['item_id'] ?? 0);
        $producto_id = absint($_POST['producto_id'] ?? 0);
        $cantidad = floatval($_POST['cantidad'] ?? 1);
        $usuario_id = get_current_user_id();

        // Validar que tenemos al menos un identificador
        if (!$item_id && !$producto_id) {
            wp_send_json_error([
                'message' => __('Datos incompletos.', 'flavor-chat-ia'),
                'code' => 'missing_data'
            ]);
        }

        global $wpdb;

        // Obtener el item
        $where_clause = $item_id
            ? $wpdb->prepare("id = %d AND usuario_id = %d", $item_id, $usuario_id)
            : $wpdb->prepare("producto_id = %d AND usuario_id = %d", $producto_id, $usuario_id);

        $item = $wpdb->get_row("SELECT * FROM {$this->tabla_lista} WHERE {$where_clause}");

        if (!$item) {
            wp_send_json_error([
                'message' => __('Producto no encontrado en tu pedido.', 'flavor-chat-ia'),
                'code' => 'item_not_found'
            ]);
        }

        // Verificar cantidad minima
        $cantidad_minima = floatval(get_post_meta($item->producto_id, '_gc_cantidad_minima', true) ?: 1);
        if ($cantidad < $cantidad_minima) {
            $cantidad = $cantidad_minima;
        }

        // Verificar stock
        $stock = get_post_meta($item->producto_id, '_gc_stock', true);
        if (!empty($stock) && floatval($stock) < $cantidad) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Stock insuficiente. Maximo disponible: %s', 'flavor-chat-ia'),
                    number_format($stock, 0, ',', '.')
                ),
                'code' => 'insufficient_stock',
                'stock_disponible' => floatval($stock),
                'cantidad_ajustada' => floatval($stock)
            ]);
        }

        // Actualizar
        $resultado = $wpdb->update(
            $this->tabla_lista,
            [
                'cantidad' => $cantidad,
                'fecha_modificado' => current_time('mysql'),
            ],
            ['id' => $item->id]
        );

        if ($resultado === false) {
            wp_send_json_error([
                'message' => __('Error al actualizar la cantidad.', 'flavor-chat-ia'),
                'code' => 'db_error'
            ]);
        }

        // Calcular nuevo subtotal
        $precio = floatval(get_post_meta($item->producto_id, '_gc_precio', true));
        $subtotal = $precio * $cantidad;

        // Obtener totales actualizados
        $datos_carrito = $this->obtener_datos_carrito($usuario_id);

        wp_send_json_success([
            'message' => __('Cantidad actualizada.', 'flavor-chat-ia'),
            'item_id' => $item->id,
            'producto_id' => $item->producto_id,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'subtotal_formateado' => number_format($subtotal, 2, ',', '.') . ' EUR',
            'carrito' => $datos_carrito,
        ]);
    }

    /**
     * Handler: Quitar producto del carrito
     */
    public function handle_quitar_producto() {
        $this->verificar_seguridad();

        $item_id = absint($_POST['item_id'] ?? 0);
        $producto_id = absint($_POST['producto_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$item_id && !$producto_id) {
            wp_send_json_error([
                'message' => __('Datos incompletos.', 'flavor-chat-ia'),
                'code' => 'missing_data'
            ]);
        }

        global $wpdb;

        // Eliminar
        if ($item_id) {
            $resultado = $wpdb->delete(
                $this->tabla_lista,
                [
                    'id' => $item_id,
                    'usuario_id' => $usuario_id,
                ]
            );
        } else {
            $resultado = $wpdb->delete(
                $this->tabla_lista,
                [
                    'producto_id' => $producto_id,
                    'usuario_id' => $usuario_id,
                ]
            );
        }

        if ($resultado === false) {
            wp_send_json_error([
                'message' => __('Error al eliminar el producto.', 'flavor-chat-ia'),
                'code' => 'db_error'
            ]);
        }

        // Obtener datos actualizados
        $datos_carrito = $this->obtener_datos_carrito($usuario_id);

        wp_send_json_success([
            'message' => __('Producto eliminado del pedido.', 'flavor-chat-ia'),
            'producto_id' => $producto_id,
            'carrito' => $datos_carrito,
        ]);
    }

    /**
     * Handler: Obtener datos del carrito
     */
    public function handle_obtener_carrito() {
        $this->verificar_seguridad();

        $usuario_id = get_current_user_id();
        $datos_carrito = $this->obtener_datos_carrito($usuario_id, true);

        wp_send_json_success([
            'carrito' => $datos_carrito,
        ]);
    }

    /**
     * Handler: Vaciar carrito
     */
    public function handle_vaciar_carrito() {
        $this->verificar_seguridad();

        $usuario_id = get_current_user_id();

        global $wpdb;
        $resultado = $wpdb->delete(
            $this->tabla_lista,
            ['usuario_id' => $usuario_id]
        );

        wp_send_json_success([
            'message' => __('Tu pedido ha sido vaciado.', 'flavor-chat-ia'),
            'carrito' => [
                'items' => [],
                'total_items' => 0,
                'subtotal' => 0,
                'subtotal_formateado' => '0,00 EUR',
            ],
        ]);
    }

    /**
     * Handler: Confirmar pedido
     */
    public function handle_confirmar_pedido() {
        $this->verificar_seguridad();

        $usuario_id = get_current_user_id();

        // Verificar ciclo activo
        $ciclo = $this->obtener_ciclo_activo();
        if (!$ciclo) {
            wp_send_json_error([
                'message' => __('No hay ningun ciclo de pedidos abierto actualmente.', 'flavor-chat-ia'),
                'code' => 'no_active_cycle'
            ]);
        }

        // Verificar que no haya pasado la fecha de cierre
        if (strtotime($ciclo['fecha_cierre']) < current_time('timestamp')) {
            wp_send_json_error([
                'message' => __('El ciclo de pedidos ha cerrado. Ya no es posible realizar pedidos.', 'flavor-chat-ia'),
                'code' => 'cycle_closed'
            ]);
        }

        global $wpdb;

        // Obtener items de la lista
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$this->tabla_lista} l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d",
            $usuario_id
        ));

        if (empty($items)) {
            wp_send_json_error([
                'message' => __('Tu pedido esta vacio. Agrega productos antes de confirmar.', 'flavor-chat-ia'),
                'code' => 'empty_cart'
            ]);
        }

        // Verificar stock y calcular totales
        $total = 0;
        $detalles = [];
        $errores_stock = [];

        foreach ($items as $item) {
            $precio = floatval(get_post_meta($item->producto_id, '_gc_precio', true));
            $stock = get_post_meta($item->producto_id, '_gc_stock', true);
            $unidad = get_post_meta($item->producto_id, '_gc_unidad', true) ?: 'ud';

            // Verificar stock
            if (!empty($stock) && floatval($stock) < $item->cantidad) {
                $errores_stock[] = sprintf(
                    __('%s: solo quedan %s disponibles', 'flavor-chat-ia'),
                    $item->nombre,
                    number_format($stock, 0)
                );
                continue;
            }

            $subtotal = $precio * $item->cantidad;
            $total += $subtotal;

            $detalles[] = [
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre,
                'cantidad' => $item->cantidad,
                'precio' => $precio,
                'unidad' => $unidad,
                'subtotal' => $subtotal,
            ];
        }

        if (!empty($errores_stock)) {
            wp_send_json_error([
                'message' => __('Algunos productos no tienen stock suficiente:', 'flavor-chat-ia'),
                'errores' => $errores_stock,
                'code' => 'stock_issues'
            ]);
        }

        // Calcular gastos de gestion
        $porcentaje_gestion = $this->obtener_porcentaje_gestion();
        $gastos_gestion = $total * ($porcentaje_gestion / 100);
        $total_final = $total + $gastos_gestion;

        // Crear pedido
        $resultado_pedido = $wpdb->insert(
            $this->tabla_pedidos,
            [
                'ciclo_id' => $ciclo['id'],
                'usuario_id' => $usuario_id,
                'detalles' => wp_json_encode($detalles),
                'subtotal' => $total,
                'gastos_gestion' => $gastos_gestion,
                'total' => $total_final,
                'estado' => 'confirmado',
                'fecha_pedido' => current_time('mysql'),
            ]
        );

        if ($resultado_pedido === false) {
            wp_send_json_error([
                'message' => __('Error al crear el pedido. Por favor, intentalo de nuevo.', 'flavor-chat-ia'),
                'code' => 'db_error'
            ]);
        }

        $pedido_id = $wpdb->insert_id;

        // Actualizar stock de productos
        foreach ($detalles as $detalle) {
            $stock_actual = get_post_meta($detalle['producto_id'], '_gc_stock', true);
            if (!empty($stock_actual)) {
                $nuevo_stock = max(0, floatval($stock_actual) - $detalle['cantidad']);
                update_post_meta($detalle['producto_id'], '_gc_stock', $nuevo_stock);
            }
        }

        // Vaciar lista de compra
        $wpdb->delete($this->tabla_lista, ['usuario_id' => $usuario_id]);

        // Disparar accion para notificaciones
        do_action('gc_pedido_confirmado', $pedido_id, $usuario_id, $detalles);

        wp_send_json_success([
            'message' => __('Tu pedido ha sido confirmado correctamente.', 'flavor-chat-ia'),
            'pedido_id' => $pedido_id,
            'total' => $total_final,
            'total_formateado' => number_format($total_final, 2, ',', '.') . ' EUR',
            'redirect_url' => home_url('/mi-cuenta/?tab=gc-mis-pedidos'),
        ]);
    }

    /**
     * Handler: Cargar mas productos (paginacion AJAX)
     */
    public function handle_cargar_mas_productos() {
        check_ajax_referer('gc_frontend_nonce', 'nonce', false);

        $pagina = absint($_POST['pagina'] ?? 1);
        $por_pagina = absint($_POST['por_pagina'] ?? 12);
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $productor_id = absint($_POST['productor_id'] ?? 0);
        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $orden = sanitize_text_field($_POST['orden'] ?? 'nombre-asc');

        $args_query = [
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => $por_pagina,
            'paged' => $pagina + 1, // Siguiente pagina
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        // Aplicar orden
        switch ($orden) {
            case 'nombre-desc':
                $args_query['order'] = 'DESC';
                break;
            case 'precio-asc':
                $args_query['meta_key'] = '_gc_precio';
                $args_query['orderby'] = 'meta_value_num';
                $args_query['order'] = 'ASC';
                break;
            case 'precio-desc':
                $args_query['meta_key'] = '_gc_precio';
                $args_query['orderby'] = 'meta_value_num';
                $args_query['order'] = 'DESC';
                break;
        }

        // Filtrar por categoria
        if ($categoria) {
            $args_query['tax_query'] = [[
                'taxonomy' => 'gc_categoria',
                'field' => 'slug',
                'terms' => $categoria,
            ]];
        }

        // Filtrar por productor
        if ($productor_id) {
            $args_query['meta_query'][] = [
                'key' => '_gc_productor_id',
                'value' => $productor_id,
            ];
        }

        // Busqueda
        if ($busqueda) {
            $args_query['s'] = $busqueda;
        }

        $query = new WP_Query($args_query);
        $productos = [];

        // Lista de compra del usuario
        $lista_compra = [];
        if (is_user_logged_in()) {
            global $wpdb;
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT producto_id, cantidad FROM {$this->tabla_lista} WHERE usuario_id = %d",
                get_current_user_id()
            ));
            foreach ($items as $item) {
                $lista_compra[$item->producto_id] = $item->cantidad;
            }
        }

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $producto_id = get_the_ID();
                $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
                $productor = $productor_id ? get_post($productor_id) : null;
                $es_ecologico = $productor_id ? get_post_meta($productor_id, '_gc_certificacion_eco', true) : false;
                $stock = get_post_meta($producto_id, '_gc_stock', true);

                $productos[] = [
                    'id' => $producto_id,
                    'nombre' => get_the_title(),
                    'precio' => floatval(get_post_meta($producto_id, '_gc_precio', true)),
                    'unidad' => get_post_meta($producto_id, '_gc_unidad', true) ?: 'ud',
                    'stock' => $stock,
                    'tiene_stock' => empty($stock) || floatval($stock) > 0,
                    'stock_bajo' => !empty($stock) && floatval($stock) <= 5,
                    'productor_id' => $productor_id,
                    'productor_nombre' => $productor ? $productor->post_title : '',
                    'es_ecologico' => (bool) $es_ecologico,
                    'imagen' => get_the_post_thumbnail_url($producto_id, 'medium') ?: '',
                    'en_lista' => isset($lista_compra[$producto_id]),
                    'cantidad_en_lista' => $lista_compra[$producto_id] ?? 1,
                    'enlace' => get_permalink($producto_id),
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'productos' => $productos,
            'pagina_actual' => $pagina + 1,
            'total_paginas' => $query->max_num_pages,
            'total_productos' => $query->found_posts,
            'hay_mas' => ($pagina + 1) < $query->max_num_pages,
        ]);
    }

    /**
     * Handler: Sincronizar carrito con localStorage
     */
    public function handle_sincronizar_carrito() {
        $this->verificar_seguridad();

        $usuario_id = get_current_user_id();
        $items_local = json_decode(stripslashes($_POST['items_local'] ?? '[]'), true);

        if (!is_array($items_local)) {
            $items_local = [];
        }

        global $wpdb;

        // Obtener items actuales del servidor
        $items_servidor = $wpdb->get_results($wpdb->prepare(
            "SELECT producto_id, cantidad FROM {$this->tabla_lista} WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        $productos_servidor = [];
        foreach ($items_servidor as $item) {
            $productos_servidor[$item['producto_id']] = floatval($item['cantidad']);
        }

        // Sincronizar: combinar items del local con servidor
        foreach ($items_local as $item) {
            $producto_id = absint($item['producto_id'] ?? 0);
            $cantidad = floatval($item['cantidad'] ?? 1);

            if (!$producto_id || $cantidad < 1) continue;

            // Verificar que el producto existe
            $producto = get_post($producto_id);
            if (!$producto || $producto->post_type !== 'gc_producto') continue;

            // Si ya existe en servidor, usar la cantidad mayor
            if (isset($productos_servidor[$producto_id])) {
                if ($cantidad > $productos_servidor[$producto_id]) {
                    $wpdb->update(
                        $this->tabla_lista,
                        ['cantidad' => $cantidad, 'fecha_modificado' => current_time('mysql')],
                        ['usuario_id' => $usuario_id, 'producto_id' => $producto_id]
                    );
                }
            } else {
                // Nuevo item
                $wpdb->insert(
                    $this->tabla_lista,
                    [
                        'usuario_id' => $usuario_id,
                        'producto_id' => $producto_id,
                        'cantidad' => $cantidad,
                        'fecha_agregado' => current_time('mysql'),
                        'fecha_modificado' => current_time('mysql'),
                    ]
                );
            }
        }

        // Obtener datos actualizados
        $datos_carrito = $this->obtener_datos_carrito($usuario_id, true);

        wp_send_json_success([
            'message' => __('Carrito sincronizado.', 'flavor-chat-ia'),
            'carrito' => $datos_carrito,
        ]);
    }

    /**
     * Obtener datos completos del carrito
     *
     * @param int  $usuario_id   ID del usuario
     * @param bool $incluir_items Si incluir lista de items
     * @return array
     */
    private function obtener_datos_carrito($usuario_id, $incluir_items = false) {
        global $wpdb;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as nombre
             FROM {$this->tabla_lista} l
             LEFT JOIN {$wpdb->posts} p ON l.producto_id = p.ID
             WHERE l.usuario_id = %d
             ORDER BY l.fecha_agregado DESC",
            $usuario_id
        ));

        $total = 0;
        $items_formateados = [];

        foreach ($items as $item) {
            $precio = floatval(get_post_meta($item->producto_id, '_gc_precio', true));
            $unidad = get_post_meta($item->producto_id, '_gc_unidad', true) ?: 'ud';
            $subtotal = $precio * $item->cantidad;
            $total += $subtotal;

            if ($incluir_items) {
                $productor_id = get_post_meta($item->producto_id, '_gc_productor_id', true);
                $productor = $productor_id ? get_post($productor_id) : null;

                $items_formateados[] = [
                    'id' => $item->id,
                    'producto_id' => $item->producto_id,
                    'nombre' => $item->nombre,
                    'cantidad' => floatval($item->cantidad),
                    'precio' => $precio,
                    'unidad' => $unidad,
                    'subtotal' => $subtotal,
                    'subtotal_formateado' => number_format($subtotal, 2, ',', '.') . ' EUR',
                    'imagen' => get_the_post_thumbnail_url($item->producto_id, 'thumbnail') ?: '',
                    'productor' => $productor ? $productor->post_title : '',
                    'es_ecologico' => $productor_id ? (bool) get_post_meta($productor_id, '_gc_certificacion_eco', true) : false,
                ];
            }
        }

        $porcentaje_gestion = $this->obtener_porcentaje_gestion();
        $gastos_gestion = $total * ($porcentaje_gestion / 100);
        $total_final = $total + $gastos_gestion;

        $datos = [
            'total_items' => count($items),
            'subtotal' => $total,
            'subtotal_formateado' => number_format($total, 2, ',', '.') . ' EUR',
            'porcentaje_gestion' => $porcentaje_gestion,
            'gastos_gestion' => $gastos_gestion,
            'gastos_gestion_formateado' => number_format($gastos_gestion, 2, ',', '.') . ' EUR',
            'total' => $total_final,
            'total_formateado' => number_format($total_final, 2, ',', '.') . ' EUR',
        ];

        if ($incluir_items) {
            $datos['items'] = $items_formateados;
        }

        return $datos;
    }

    /**
     * Obtener porcentaje de gestion del modulo
     *
     * @return float
     */
    private function obtener_porcentaje_gestion() {
        $opciones = get_option('flavor_chat_modules', []);
        return floatval($opciones['grupos_consumo']['settings']['porcentaje_gestion'] ?? 5);
    }
}

// Inicializar handlers
add_action('init', function() {
    Flavor_GC_Ajax_Handlers::get_instance();
});
