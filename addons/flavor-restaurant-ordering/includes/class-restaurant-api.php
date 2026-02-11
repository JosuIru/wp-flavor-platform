<?php
/**
 * API REST para el sistema de restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Restaurant_API {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    private $namespace = 'restaurant/v1';

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Obtener menú completo
        register_rest_route($this->namespace, '/menu', [
            'methods' => 'GET',
            'callback' => [$this, 'get_menu'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Obtener items por categoría
        register_rest_route($this->namespace, '/menu/(?P<category>[a-z_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_menu_category'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'category' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, ['dishes', 'drinks', 'desserts']);
                    }
                ]
            ]
        ]);

        // Gestión de mesas
        register_rest_route($this->namespace, '/tables', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_tables'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_table'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ]
        ]);

        register_rest_route($this->namespace, '/tables/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_table'],
                'permission_callback' => [$this, 'public_permission_check'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_table'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_table'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ]
        ]);

        // Estadísticas de mesas
        register_rest_route($this->namespace, '/tables/statistics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tables_statistics'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Gestión de pedidos
        register_rest_route($this->namespace, '/orders', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_orders'],
                'permission_callback' => [$this, 'check_view_orders_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_order'],
                'permission_callback' => [$this, 'public_permission_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order'],
            'permission_callback' => [$this, 'check_view_order_permission'],
        ]);

        register_rest_route($this->namespace, '/orders/number/(?P<number>[A-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_by_number'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Actualizar estado del pedido
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)/status', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_order_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Historial de estados
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)/history', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_history'],
            'permission_callback' => [$this, 'check_view_order_permission'],
        ]);

        // Estadísticas
        register_rest_route($this->namespace, '/statistics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_statistics'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Estados disponibles
        register_rest_route($this->namespace, '/order-statuses', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_statuses'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Gestión de reservas
        register_rest_route($this->namespace, '/reservations', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_reservations'],
                'permission_callback' => [$this, 'check_view_reservations_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_reservation'],
                'permission_callback' => [$this, 'public_permission_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/reservations/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_reservation'],
                'permission_callback' => [$this, 'check_view_reservation_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_reservation'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'cancel_reservation'],
                'permission_callback' => [$this, 'check_cancel_reservation_permission'],
            ]
        ]);

        register_rest_route($this->namespace, '/reservations/code/(?P<code>[A-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_reservation_by_code'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($this->namespace, '/reservations/(?P<id>\d+)/confirm', [
            'methods' => 'POST',
            'callback' => [$this, 'confirm_reservation'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route($this->namespace, '/reservations/(?P<id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_reservation_endpoint'],
            'permission_callback' => [$this, 'check_cancel_reservation_permission'],
        ]);

        register_rest_route($this->namespace, '/reservations/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'check_availability'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($this->namespace, '/reservations/statistics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_reservations_statistics'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Obtener menú completo
     */
    public function get_menu($request) {
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $menu = $restaurant_manager->get_full_menu();

        return rest_ensure_response([
            'success' => true,
            'menu' => $menu,
        ]);
    }

    /**
     * Obtener items de una categoría del menú
     */
    public function get_menu_category($request) {
        $category = $request->get_param('category');
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();

        $menu_cpts = $restaurant_manager->get_menu_cpts();

        if (!isset($menu_cpts[$category])) {
            return new WP_Error('category_not_found', __('Categoría no encontrada', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        $items = [];
        foreach ($menu_cpts[$category] as $cpt_info) {
            $cpt_items = $restaurant_manager->get_menu_items($cpt_info['slug']);
            $items = array_merge($items, $cpt_items);
        }

        return rest_ensure_response([
            'success' => true,
            'category' => $category,
            'label' => $restaurant_manager->get_category_label($category),
            'items' => $items,
            'total' => count($items),
        ]);
    }

    /**
     * Obtener mesas
     */
    public function get_tables($request) {
        $status = $request->get_param('status');
        $location = $request->get_param('location');

        $table_manager = Flavor_Table_Manager::get_instance();
        $tables = $table_manager->get_tables([
            'status' => $status,
            'location' => $location,
        ]);

        return rest_ensure_response([
            'success' => true,
            'tables' => $tables,
            'total' => count($tables),
        ]);
    }

    /**
     * Obtener mesa específica
     */
    public function get_table($request) {
        $table_id = $request->get_param('id');
        $table_manager = Flavor_Table_Manager::get_instance();
        $table = $table_manager->get_table($table_id);

        if (!$table) {
            return new WP_Error('table_not_found', __('Mesa no encontrada', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'table' => $table,
        ]);
    }

    /**
     * Crear mesa
     */
    public function create_table($request) {
        $data = [
            'table_number' => $request->get_param('table_number'),
            'table_name' => $request->get_param('table_name'),
            'capacity' => $request->get_param('capacity'),
            'location' => $request->get_param('location'),
            'notes' => $request->get_param('notes'),
        ];

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->create_table($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Mesa creada exitosamente', 'flavor-restaurant-ordering'),
            'table' => $result,
        ]);
    }

    /**
     * Actualizar mesa
     */
    public function update_table($request) {
        $table_id = $request->get_param('id');

        $data = [];
        $allowed_fields = ['table_number', 'table_name', 'capacity', 'status', 'location', 'notes'];

        foreach ($allowed_fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->update_table($table_id, $data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Mesa actualizada exitosamente', 'flavor-restaurant-ordering'),
            'table' => $result,
        ]);
    }

    /**
     * Eliminar mesa
     */
    public function delete_table($request) {
        $table_id = $request->get_param('id');
        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->delete_table($table_id);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Mesa eliminada exitosamente', 'flavor-restaurant-ordering'),
        ]);
    }

    /**
     * Obtener estadísticas de mesas
     */
    public function get_tables_statistics($request) {
        $table_manager = Flavor_Table_Manager::get_instance();
        $stats = $table_manager->get_statistics();

        return rest_ensure_response([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Obtener pedidos
     */
    public function get_orders($request) {
        $args = [
            'status' => $request->get_param('status'),
            'table_id' => $request->get_param('table_id'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'limit' => $request->get_param('per_page') ?: 20,
            'offset' => ($request->get_param('page') - 1) * ($request->get_param('per_page') ?: 20),
            'include_items' => $request->get_param('include_items') === 'true',
        ];

        $order_manager = Flavor_Order_Manager::get_instance();
        $orders = $order_manager->get_orders($args);

        return rest_ensure_response([
            'success' => true,
            'orders' => $orders,
            'total' => count($orders),
        ]);
    }

    /**
     * Obtener pedido específico
     */
    public function get_order($request) {
        $order_id = $request->get_param('id');
        $order_manager = Flavor_Order_Manager::get_instance();
        $order = $order_manager->get_order($order_id);

        if (!$order) {
            return new WP_Error('order_not_found', __('Pedido no encontrado', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Obtener pedido por número
     */
    public function get_order_by_number($request) {
        $rate_limit = Flavor_API_Rate_Limiter::check_rate_limit('get');
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        $order_number = $request->get_param('number');
        $order_manager = Flavor_Order_Manager::get_instance();
        $order = $order_manager->get_order_by_number($order_number);

        if (!$order) {
            return new WP_Error('order_not_found', __('Pedido no encontrado', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        $order = $this->filter_public_order($order);

        return rest_ensure_response([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Crear pedido
     */
    public function create_order($request) {
        $data = [
            'table_id' => $request->get_param('table_id'),
            'customer_name' => $request->get_param('customer_name'),
            'customer_phone' => $request->get_param('customer_phone'),
            'customer_email' => $request->get_param('customer_email'),
            'items' => $request->get_param('items'),
            'notes' => $request->get_param('notes'),
        ];

        $order_manager = Flavor_Order_Manager::get_instance();
        $result = $order_manager->create_order($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Pedido creado exitosamente', 'flavor-restaurant-ordering'),
            'order' => $result,
        ]);
    }

    /**
     * Actualizar estado del pedido
     */
    public function update_order_status($request) {
        $order_id = $request->get_param('id');
        $new_status = $request->get_param('status');
        $notes = $request->get_param('notes') ?: '';

        $order_manager = Flavor_Order_Manager::get_instance();
        $result = $order_manager->update_status($order_id, $new_status, $notes);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Estado actualizado exitosamente', 'flavor-restaurant-ordering'),
            'order' => $result,
        ]);
    }

    /**
     * Obtener historial de estados
     */
    public function get_order_history($request) {
        $order_id = $request->get_param('id');
        $order_manager = Flavor_Order_Manager::get_instance();

        // Verificar que el pedido existe
        $order = $order_manager->get_order($order_id);
        if (!$order) {
            return new WP_Error('order_not_found', __('Pedido no encontrado', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        $history = $order_manager->get_status_history($order_id);

        return rest_ensure_response([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Obtener estadísticas
     */
    public function get_statistics($request) {
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');

        $order_manager = Flavor_Order_Manager::get_instance();
        $stats = $order_manager->get_statistics($date_from, $date_to);

        return rest_ensure_response([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Obtener estados disponibles
     */
    public function get_order_statuses($request) {
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $statuses = $restaurant_manager->get_order_statuses();

        return rest_ensure_response([
            'success' => true,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Verificar permisos de admin
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_options');
    }

    /**
     * Verificar permisos para ver pedidos
     */
    public function check_view_orders_permission($request) {
        // Los administradores pueden ver todos los pedidos
        if (current_user_can('manage_options')) {
            return true;
        }

        // Los usuarios pueden ver sus propios pedidos
        return is_user_logged_in();
    }

    /**
     * Verificar permisos para ver un pedido específico
     */
    public function check_view_order_permission($request) {
        // Los administradores pueden ver cualquier pedido
        if (current_user_can('manage_options')) {
            return true;
        }

        // Los usuarios solo pueden ver sus propios pedidos
        if (!is_user_logged_in()) {
            return false;
        }

        $order_id = $request->get_param('id');
        $order_manager = Flavor_Order_Manager::get_instance();
        $order = $order_manager->get_order($order_id);

        if (!$order) {
            return false;
        }

        return (int) $order['user_id'] === get_current_user_id();
    }

    /**
     * Obtener reservas
     */
    public function get_reservations($request) {
        $args = [
            'status' => $request->get_param('status'),
            'table_id' => $request->get_param('table_id'),
            'date' => $request->get_param('date'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
            'upcoming' => $request->get_param('upcoming') === 'true',
            'limit' => $request->get_param('per_page') ?: 50,
            'offset' => ($request->get_param('page') - 1) * ($request->get_param('per_page') ?: 50),
        ];

        // Si es admin, puede ver todas. Si no, solo las suyas
        if (!current_user_can('manage_options') && is_user_logged_in()) {
            $args['user_id'] = get_current_user_id();
        }

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservations = $reservation_manager->get_reservations($args);

        return rest_ensure_response([
            'success' => true,
            'reservations' => $reservations,
            'total' => count($reservations),
        ]);
    }

    /**
     * Obtener reserva específica
     */
    public function get_reservation($request) {
        $reservation_id = $request->get_param('id');
        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservation = $reservation_manager->get_reservation($reservation_id);

        if (!$reservation) {
            return new WP_Error('reservation_not_found', __('Reserva no encontrada', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'reservation' => $reservation,
        ]);
    }

    /**
     * Obtener reserva por código
     */
    public function get_reservation_by_code($request) {
        $rate_limit = Flavor_API_Rate_Limiter::check_rate_limit('get');
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        $code = $request->get_param('code');
        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservation = $reservation_manager->get_reservation_by_code($code);

        if (!$reservation) {
            return new WP_Error('reservation_not_found', __('Reserva no encontrada', 'flavor-restaurant-ordering'), ['status' => 404]);
        }

        $reservation = $this->filter_public_reservation($reservation);

        return rest_ensure_response([
            'success' => true,
            'reservation' => $reservation,
        ]);
    }

    /**
     * Filtra campos sensibles en pedidos públicos
     *
     * @param array|object $order
     * @return array
     */
    private function filter_public_order($order) {
        $allowed = [
            'id',
            'order_id',
            'order_number',
            'status',
            'items',
            'total',
            'created_at',
            'table_id',
        ];

        if (is_array($order)) {
            return array_intersect_key($order, array_flip($allowed));
        }

        $filtered = [];
        foreach ($allowed as $key) {
            if (is_object($order) && isset($order->$key)) {
                $filtered[$key] = $order->$key;
            }
        }
        return $filtered;
    }

    /**
     * Filtra campos sensibles en reservas públicas
     *
     * @param array|object $reservation
     * @return array
     */
    private function filter_public_reservation($reservation) {
        $allowed = [
            'id',
            'reservation_id',
            'code',
            'reservation_code',
            'status',
            'reservation_date',
            'reservation_time',
            'guests_count',
            'table_id',
        ];

        if (is_array($reservation)) {
            return array_intersect_key($reservation, array_flip($allowed));
        }

        $filtered = [];
        foreach ($allowed as $key) {
            if (is_object($reservation) && isset($reservation->$key)) {
                $filtered[$key] = $reservation->$key;
            }
        }
        return $filtered;
    }

    /**
     * Crear reserva
     */
    public function create_reservation($request) {
        $data = [
            'table_id' => $request->get_param('table_id'),
            'customer_name' => $request->get_param('customer_name'),
            'customer_phone' => $request->get_param('customer_phone'),
            'customer_email' => $request->get_param('customer_email'),
            'guests_count' => $request->get_param('guests_count'),
            'reservation_date' => $request->get_param('reservation_date'),
            'reservation_time' => $request->get_param('reservation_time'),
            'duration' => $request->get_param('duration'),
            'special_requests' => $request->get_param('special_requests'),
        ];

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->create_reservation($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Reserva creada exitosamente', 'flavor-restaurant-ordering'),
            'reservation' => $result,
        ]);
    }

    /**
     * Actualizar reserva
     */
    public function update_reservation($request) {
        $reservation_id = $request->get_param('id');

        $data = [];
        $allowed_fields = [
            'table_id', 'customer_name', 'customer_phone', 'customer_email',
            'guests_count', 'reservation_date', 'reservation_time',
            'duration', 'special_requests', 'notes'
        ];

        foreach ($allowed_fields as $field) {
            $value = $request->get_param($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->update_reservation($reservation_id, $data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Reserva actualizada exitosamente', 'flavor-restaurant-ordering'),
            'reservation' => $result,
        ]);
    }

    /**
     * Confirmar reserva
     */
    public function confirm_reservation($request) {
        $reservation_id = $request->get_param('id');
        $notes = $request->get_param('notes') ?: '';

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->update_status($reservation_id, 'confirmed', $notes);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Reserva confirmada exitosamente', 'flavor-restaurant-ordering'),
            'reservation' => $result,
        ]);
    }

    /**
     * Cancelar reserva (endpoint)
     */
    public function cancel_reservation_endpoint($request) {
        $reservation_id = $request->get_param('id');
        $reason = $request->get_param('reason') ?: '';

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $result = $reservation_manager->cancel_reservation($reservation_id, $reason);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Reserva cancelada exitosamente', 'flavor-restaurant-ordering'),
            'reservation' => $result,
        ]);
    }

    /**
     * Verificar disponibilidad
     */
    public function check_availability($request) {
        $date = $request->get_param('date');
        $time = $request->get_param('time');
        $guests = absint($request->get_param('guests') ?: 2);
        $duration = absint($request->get_param('duration') ?: 120);

        if (!$date || !$time) {
            return new WP_Error('missing_params', __('Fecha y hora son requeridos', 'flavor-restaurant-ordering'), ['status' => 400]);
        }

        $table_manager = Flavor_Table_Manager::get_instance();
        $reservation_manager = Flavor_Reservation_Manager::get_instance();

        // Obtener todas las mesas con capacidad suficiente
        $tables = $table_manager->get_tables();
        $available_tables = [];

        foreach ($tables as $table) {
            if ($table['capacity'] >= $guests) {
                // Verificar si está disponible en ese horario
                $reflection = new ReflectionClass($reservation_manager);
                $method = $reflection->getMethod('is_table_available');
                $method->setAccessible(true);

                if ($method->invoke($reservation_manager, $table['id'], $date, $time, $duration)) {
                    $available_tables[] = $table;
                }
            }
        }

        return rest_ensure_response([
            'success' => true,
            'available' => count($available_tables) > 0,
            'tables_count' => count($available_tables),
            'tables' => $available_tables,
        ]);
    }

    /**
     * Obtener estadísticas de reservas
     */
    public function get_reservations_statistics($request) {
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');

        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $stats = $reservation_manager->get_statistics($date_from, $date_to);

        return rest_ensure_response([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Verificar permisos para ver reservas
     */
    public function check_view_reservations_permission($request) {
        // Los administradores pueden ver todas las reservas
        if (current_user_can('manage_options')) {
            return true;
        }

        // Los usuarios pueden ver sus propias reservas
        return is_user_logged_in();
    }

    /**
     * Verificar permisos para ver una reserva específica
     */
    public function check_view_reservation_permission($request) {
        // Los administradores pueden ver cualquier reserva
        if (current_user_can('manage_options')) {
            return true;
        }

        // Los usuarios solo pueden ver sus propias reservas
        if (!is_user_logged_in()) {
            return false;
        }

        $reservation_id = $request->get_param('id');
        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservation = $reservation_manager->get_reservation($reservation_id);

        if (!$reservation) {
            return false;
        }

        return (int) $reservation['user_id'] === get_current_user_id();
    }

    /**
     * Verificar permisos para cancelar reserva
     */
    public function check_cancel_reservation_permission($request) {
        // Los administradores pueden cancelar cualquier reserva
        if (current_user_can('manage_options')) {
            return true;
        }

        // Los usuarios pueden cancelar sus propias reservas
        if (!is_user_logged_in()) {
            return false;
        }

        $reservation_id = $request->get_param('id');
        $reservation_manager = Flavor_Reservation_Manager::get_instance();
        $reservation = $reservation_manager->get_reservation($reservation_id);

        if (!$reservation) {
            return false;
        }

        // Solo puede cancelar si es suya y está pendiente o confirmada
        return (int) $reservation['user_id'] === get_current_user_id() &&
               in_array($reservation['status'], ['pending', 'confirmed']);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
