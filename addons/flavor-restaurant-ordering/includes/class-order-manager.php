<?php
/**
 * Gestor de pedidos del restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Order_Manager {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Nombres de tablas
     */
    private $orders_table;
    private $order_items_table;
    private $status_history_table;

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
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->orders_table = $wpdb->prefix . 'restaurant_orders';
        $this->order_items_table = $wpdb->prefix . 'restaurant_order_items';
        $this->status_history_table = $wpdb->prefix . 'restaurant_order_status_history';
    }

    /**
     * Crear nuevo pedido
     */
    public function create_order($data) {
        global $wpdb;

        // Validar datos requeridos
        if (empty($data['items']) || !is_array($data['items'])) {
            return new WP_Error('missing_items', __('El pedido debe contener al menos un item', 'flavor-restaurant-ordering'));
        }

        // Datos del pedido
        $order_data = [
            'table_id' => isset($data['table_id']) ? absint($data['table_id']) : null,
            'customer_name' => sanitize_text_field($data['customer_name'] ?? ''),
            'customer_phone' => sanitize_text_field($data['customer_phone'] ?? ''),
            'customer_email' => sanitize_email($data['customer_email'] ?? ''),
            'user_id' => isset($data['user_id']) ? absint($data['user_id']) : get_current_user_id(),
            'notes' => wp_kses_post($data['notes'] ?? ''),
        ];

        // Validar mesa si se proporciona
        if ($order_data['table_id']) {
            $table = Flavor_Table_Manager::get_instance()->get_table($order_data['table_id']);
            if (!$table) {
                return new WP_Error('invalid_table', __('Mesa no válida', 'flavor-restaurant-ordering'));
            }
        }

        // Calcular totales
        $totals = $this->calculate_order_totals($data['items']);
        if (is_wp_error($totals)) {
            return $totals;
        }

        // Generar número de pedido
        $order_number = $this->generate_order_number();

        // Iniciar transacción
        $wpdb->query('START TRANSACTION');

        try {
            // Insertar pedido
            $inserted = $wpdb->insert(
                $this->orders_table,
                array_merge($order_data, [
                    'order_number' => $order_number,
                    'status' => 'pending',
                    'subtotal' => $totals['subtotal'],
                    'tax' => $totals['tax'],
                    'total' => $totals['total'],
                ]),
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%f']
            );

            if ($inserted === false) {
                throw new Exception(__('Error al crear el pedido', 'flavor-restaurant-ordering'));
            }

            $order_id = $wpdb->insert_id;

            // Insertar items del pedido
            foreach ($data['items'] as $item) {
                $item_inserted = $this->add_order_item($order_id, $item);
                if (is_wp_error($item_inserted)) {
                    throw new Exception($item_inserted->get_error_message());
                }
            }

            // Registrar estado inicial
            $this->add_status_history($order_id, 'pending', $order_data['user_id']);

            // Actualizar estado de mesa si aplica
            if ($order_data['table_id']) {
                Flavor_Table_Manager::get_instance()->update_status($order_data['table_id'], 'occupied');
            }

            $wpdb->query('COMMIT');

            do_action('flavor_restaurant_order_created', $order_id, $data);

            // Enviar notificación si está habilitado
            if (Flavor_Restaurant_Manager::get_instance()->get_settings('enable_notifications')) {
                $this->send_order_notification($order_id, 'created');
            }

            return $this->get_order($order_id);

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('order_creation_failed', $e->getMessage());
        }
    }

    /**
     * Agregar item a un pedido
     */
    private function add_order_item($order_id, $item_data) {
        global $wpdb;

        $post_id = absint($item_data['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('invalid_item', sprintf(__('Item no encontrado: %s', 'flavor-restaurant-ordering'), $post_id));
        }

        // Obtener información del item
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $category = $restaurant_manager->get_cpt_category($post->post_type);

        if ($category === false) {
            return new WP_Error('invalid_menu_item', __('El item no forma parte del menú', 'flavor-restaurant-ordering'));
        }

        $unit_price = $restaurant_manager->get_item_price($post_id);
        $quantity = absint($item_data['quantity'] ?? 1);
        $subtotal = $unit_price * $quantity;

        $inserted = $wpdb->insert(
            $this->order_items_table,
            [
                'order_id' => $order_id,
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'item_name' => $post->post_title,
                'item_category' => $category,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'subtotal' => $subtotal,
                'notes' => wp_kses_post($item_data['notes'] ?? ''),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%f', '%f', '%s']
        );

        if ($inserted === false) {
            return new WP_Error('item_insert_failed', __('Error al agregar item al pedido', 'flavor-restaurant-ordering'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Calcular totales del pedido
     */
    private function calculate_order_totals($items) {
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $subtotal = 0;

        foreach ($items as $item) {
            $post_id = absint($item['post_id']);
            $quantity = absint($item['quantity'] ?? 1);

            $price = $restaurant_manager->get_item_price($post_id);
            if ($price === 0) {
                // Permitir items gratuitos pero verificar que el post exista
                $post = get_post($post_id);
                if (!$post) {
                    return new WP_Error('invalid_item', sprintf(__('Item no encontrado: %s', 'flavor-restaurant-ordering'), $post_id));
                }
            }

            $subtotal += $price * $quantity;
        }

        return $restaurant_manager->calculate_total($subtotal);
    }

    /**
     * Obtener pedido por ID
     */
    public function get_order($order_id) {
        global $wpdb;

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->orders_table} WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            return null;
        }

        return $this->format_order($order, true);
    }

    /**
     * Obtener pedido por número
     */
    public function get_order_by_number($order_number) {
        global $wpdb;

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->orders_table} WHERE order_number = %s",
            $order_number
        ));

        if (!$order) {
            return null;
        }

        return $this->format_order($order, true);
    }

    /**
     * Obtener pedidos
     */
    public function get_orders($args = []) {
        global $wpdb;

        $defaults = [
            'status' => null,
            'table_id' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0,
            'include_items' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $where_values = [];

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $where_values = array_merge($where_values, $args['status']);
            } else {
                $where[] = 'status = %s';
                $where_values[] = $args['status'];
            }
        }

        if ($args['table_id']) {
            $where[] = 'table_id = %d';
            $where_values[] = $args['table_id'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $orderby = in_array($args['orderby'], ['created_at', 'total', 'status', 'order_number'])
            ? $args['orderby']
            : 'created_at';

        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit_clause = $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);

        $query = "SELECT * FROM {$this->orders_table} WHERE {$where_clause} ORDER BY {$orderby} {$order}{$limit_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query);

        return array_map(function($order) use ($args) {
            return $this->format_order($order, $args['include_items']);
        }, $results);
    }

    /**
     * Actualizar estado del pedido
     */
    public function update_status($order_id, $new_status, $notes = '') {
        global $wpdb;

        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();

        if (!$restaurant_manager->is_valid_status($new_status)) {
            return new WP_Error('invalid_status', __('Estado de pedido inválido', 'flavor-restaurant-ordering'));
        }

        $order = $this->get_order($order_id);
        if (!$order) {
            return new WP_Error('order_not_found', __('Pedido no encontrado', 'flavor-restaurant-ordering'));
        }

        $old_status = $order['status'];

        // Actualizar estado
        $update_data = ['status' => $new_status];

        // Si se completa, registrar fecha
        if ($new_status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        }

        $updated = $wpdb->update(
            $this->orders_table,
            $update_data,
            ['id' => $order_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('update_failed', __('Error al actualizar el estado', 'flavor-restaurant-ordering'));
        }

        // Registrar en historial
        $this->add_status_history($order_id, $new_status, get_current_user_id(), $notes);

        // Si el pedido se completa o cancela, liberar mesa
        if (in_array($new_status, ['completed', 'cancelled']) && $order['table_id']) {
            // Verificar que no haya otros pedidos activos en la mesa
            $active_orders = $this->get_orders([
                'table_id' => $order['table_id'],
                'status' => ['pending', 'preparing', 'ready', 'served'],
                'limit' => 1
            ]);

            if (empty($active_orders)) {
                Flavor_Table_Manager::get_instance()->update_status($order['table_id'], 'available');
            }
        }

        do_action('flavor_restaurant_order_status_updated', $order_id, $new_status, $old_status);

        // Enviar notificación
        if ($restaurant_manager->get_settings('enable_notifications')) {
            $this->send_order_notification($order_id, 'status_updated');
        }

        return $this->get_order($order_id);
    }

    /**
     * Agregar al historial de estados
     */
    private function add_status_history($order_id, $status, $user_id = null, $notes = '') {
        global $wpdb;

        return $wpdb->insert(
            $this->status_history_table,
            [
                'order_id' => $order_id,
                'status' => $status,
                'user_id' => $user_id ?: get_current_user_id(),
                'notes' => $notes,
            ],
            ['%d', '%s', '%d', '%s']
        );
    }

    /**
     * Obtener historial de estados
     */
    public function get_status_history($order_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->status_history_table}
             WHERE order_id = %d
             ORDER BY created_at ASC",
            $order_id
        ));
    }

    /**
     * Obtener items del pedido
     */
    private function get_order_items($order_id) {
        global $wpdb;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->order_items_table} WHERE order_id = %d",
            $order_id
        ));

        return array_map([$this, 'format_order_item'], $items);
    }

    /**
     * Formatear pedido para respuesta
     */
    private function format_order($order, $include_items = false) {
        if (!$order) {
            return null;
        }

        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $statuses = $restaurant_manager->get_order_statuses();

        $formatted = [
            'id' => (int) $order->id,
            'order_number' => $order->order_number,
            'table_id' => $order->table_id ? (int) $order->table_id : null,
            'table' => null,
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
            'user_id' => $order->user_id ? (int) $order->user_id : null,
            'status' => $order->status,
            'status_label' => $statuses[$order->status] ?? $order->status,
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->tax,
            'total' => (float) $order->total,
            'total_formatted' => $restaurant_manager->format_price($order->total),
            'notes' => $order->notes,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'completed_at' => $order->completed_at,
        ];

        // Agregar información de mesa si existe
        if ($order->table_id) {
            $table = Flavor_Table_Manager::get_instance()->get_table($order->table_id);
            $formatted['table'] = $table;
        }

        // Incluir items si se solicita
        if ($include_items) {
            $formatted['items'] = $this->get_order_items($order->id);
            $formatted['items_count'] = count($formatted['items']);
        }

        return $formatted;
    }

    /**
     * Formatear item del pedido
     */
    private function format_order_item($item) {
        return [
            'id' => (int) $item->id,
            'post_id' => (int) $item->post_id,
            'post_type' => $item->post_type,
            'name' => $item->item_name,
            'category' => $item->item_category,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'subtotal' => (float) $item->subtotal,
            'notes' => $item->notes,
        ];
    }

    /**
     * Generar número de pedido único
     */
    private function generate_order_number() {
        $prefix = Flavor_Restaurant_Manager::get_instance()->get_settings('table_prefix');
        $timestamp = date('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $timestamp . '-' . $random;
    }

    /**
     * Enviar notificación de pedido
     */
    private function send_order_notification($order_id, $event) {
        // Hook para que otras partes del sistema manejen las notificaciones
        do_action('flavor_restaurant_send_notification', $order_id, $event);
    }

    /**
     * Obtener estadísticas de pedidos
     */
    public function get_statistics($date_from = null, $date_to = null) {
        global $wpdb;

        $where = ['1=1'];
        $where_values = [];

        if ($date_from) {
            $where[] = 'created_at >= %s';
            $where_values[] = $date_from;
        }

        if ($date_to) {
            $where[] = 'created_at <= %s';
            $where_values[] = $date_to;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(CASE WHEN status IN ('pending', 'preparing') THEN 1 ELSE 0 END) as active_orders,
            SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue,
            AVG(CASE WHEN status = 'completed' THEN total ELSE NULL END) as average_order_value
            FROM {$this->orders_table}
            WHERE {$where_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $stats = $wpdb->get_row($query, ARRAY_A);

        return [
            'total_orders' => (int) $stats['total_orders'],
            'completed_orders' => (int) $stats['completed_orders'],
            'cancelled_orders' => (int) $stats['cancelled_orders'],
            'active_orders' => (int) $stats['active_orders'],
            'total_revenue' => (float) $stats['total_revenue'],
            'average_order_value' => (float) $stats['average_order_value'],
        ];
    }
}
