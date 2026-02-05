<?php
/**
 * Gestión de pedidos en el admin
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Order_Admin {

    /**
     * Instancia única
     */
    private static $instance = null;

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
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_get_restaurant_orders', [$this, 'get_orders_ajax']);
        add_action('wp_ajax_update_order_status', [$this, 'update_order_status_ajax']);
        add_action('wp_ajax_get_order_details', [$this, 'get_order_details_ajax']);
        add_action('wp_ajax_get_tables_list', [$this, 'get_tables_ajax']);
        add_action('wp_ajax_create_table', [$this, 'create_table_ajax']);
        add_action('wp_ajax_update_table', [$this, 'update_table_ajax']);
        add_action('wp_ajax_delete_table', [$this, 'delete_table_ajax']);
    }

    /**
     * Agregar páginas al menú
     */
    public function add_menu_pages() {
        // Página principal de pedidos
        add_submenu_page(
            'flavor-platform',
            'Pedidos del Restaurante',
            'Pedidos',
            'manage_options',
            'flavor-restaurant-orders',
            [$this, 'render_orders_page']
        );

        // Página de mesas
        add_submenu_page(
            'flavor-platform',
            'Mesas del Restaurante',
            'Mesas',
            'manage_options',
            'flavor-restaurant-tables',
            [$this, 'render_tables_page']
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        $pages = [
            'flavor-platform_page_flavor-restaurant-orders',
            'flavor-platform_page_flavor-restaurant-tables',
        ];

        if (!in_array($hook, $pages)) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-restaurant-admin',
            FLAVOR_RESTAURANT_URL . "assets/css/restaurant-admin{$sufijo_asset}.css",
            [],
            FLAVOR_RESTAURANT_VERSION
        );

        wp_enqueue_script(
            'flavor-restaurant-admin',
            FLAVOR_RESTAURANT_URL . "assets/js/restaurant-admin{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_RESTAURANT_VERSION,
            true
        );

        wp_localize_script('flavor-restaurant-admin', 'flavorRestaurantAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_restaurant_admin'),
            'current_page' => $hook,
        ]);
    }

    /**
     * Renderizar página de pedidos
     */
    public function render_orders_page() {
        $restaurant_manager = Flavor_Restaurant_Manager::get_instance();
        $statuses = $restaurant_manager->get_order_statuses();

        ?>
        <div class="wrap flavor-restaurant-orders">
            <h1>
                <span class="dashicons dashicons-clipboard"></span>
                Gestión de Pedidos
            </h1>

            <div class="flavor-restaurant-toolbar">
                <div class="filter-group">
                    <label>Estado:</label>
                    <select id="filter-status" class="status-filter">
                        <option value="">Todos</option>
                        <?php foreach ($statuses as $status => $label): ?>
                        <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Mesa:</label>
                    <select id="filter-table" class="table-filter">
                        <option value="">Todas las mesas</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Fecha:</label>
                    <select id="filter-date" class="date-filter">
                        <option value="today">Hoy</option>
                        <option value="yesterday">Ayer</option>
                        <option value="week">Última semana</option>
                        <option value="month">Último mes</option>
                        <option value="">Todos</option>
                    </select>
                </div>

                <button type="button" id="refresh-orders" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Actualizar
                </button>

                <button type="button" id="show-statistics" class="button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    Estadísticas
                </button>
            </div>

            <!-- Tarjetas de estadísticas rápidas -->
            <div class="orders-statistics-cards">
                <div class="stat-card pending">
                    <span class="stat-icon dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-value" id="stat-pending">-</div>
                    </div>
                </div>

                <div class="stat-card preparing">
                    <span class="stat-icon dashicons dashicons-carrot"></span>
                    <div class="stat-content">
                        <div class="stat-label">En preparación</div>
                        <div class="stat-value" id="stat-preparing">-</div>
                    </div>
                </div>

                <div class="stat-card ready">
                    <span class="stat-icon dashicons dashicons-yes"></span>
                    <div class="stat-content">
                        <div class="stat-label">Listos</div>
                        <div class="stat-value" id="stat-ready">-</div>
                    </div>
                </div>

                <div class="stat-card revenue">
                    <span class="stat-icon dashicons dashicons-money-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label">Ingresos hoy</div>
                        <div class="stat-value" id="stat-revenue">-</div>
                    </div>
                </div>
            </div>

            <!-- Lista de pedidos -->
            <div class="orders-container">
                <div id="orders-list" class="orders-grid">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando pedidos...
                    </div>
                </div>

                <div id="no-orders" class="no-results" style="display: none;">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p>No hay pedidos que coincidan con los filtros seleccionados.</p>
                </div>
            </div>
        </div>

        <!-- Modal de detalles del pedido -->
        <div id="order-details-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2 id="modal-order-title">Pedido #</h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="order-details-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando detalles...
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Modal de estadísticas -->
        <div id="statistics-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2>Estadísticas del Restaurante</h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="statistics-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando estadísticas...
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close">Cerrar</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página de mesas
     */
    public function render_tables_page() {
        ?>
        <div class="wrap flavor-restaurant-tables">
            <h1>
                <span class="dashicons dashicons-layout"></span>
                Gestión de Mesas
            </h1>

            <div class="flavor-restaurant-toolbar">
                <button type="button" id="add-table" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Agregar Mesa
                </button>

                <button type="button" id="refresh-tables" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Actualizar
                </button>
            </div>

            <!-- Estadísticas de mesas -->
            <div class="tables-statistics-cards">
                <div class="stat-card available">
                    <span class="stat-icon dashicons dashicons-yes-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label">Disponibles</div>
                        <div class="stat-value" id="stat-tables-available">-</div>
                    </div>
                </div>

                <div class="stat-card occupied">
                    <span class="stat-icon dashicons dashicons-admin-users"></span>
                    <div class="stat-content">
                        <div class="stat-label">Ocupadas</div>
                        <div class="stat-value" id="stat-tables-occupied">-</div>
                    </div>
                </div>

                <div class="stat-card reserved">
                    <span class="stat-icon dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label">Reservadas</div>
                        <div class="stat-value" id="stat-tables-reserved">-</div>
                    </div>
                </div>

                <div class="stat-card total">
                    <span class="stat-icon dashicons dashicons-layout"></span>
                    <div class="stat-content">
                        <div class="stat-label">Total</div>
                        <div class="stat-value" id="stat-tables-total">-</div>
                    </div>
                </div>
            </div>

            <!-- Lista de mesas -->
            <div class="tables-container">
                <div id="tables-list" class="tables-grid">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        Cargando mesas...
                    </div>
                </div>

                <div id="no-tables" class="no-results" style="display: none;">
                    <span class="dashicons dashicons-layout"></span>
                    <p>No hay mesas creadas. ¡Crea tu primera mesa!</p>
                    <button type="button" class="button button-primary" id="add-first-table">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Crear Primera Mesa
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de crear/editar mesa -->
        <div id="table-form-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2 id="modal-table-title">Nueva Mesa</h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body">
                    <form id="table-form">
                        <input type="hidden" id="table-id" name="table_id">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="table-number">Número de Mesa *</label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="table-number"
                                           name="table_number"
                                           class="regular-text"
                                           required>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-name">Nombre</label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="table-name"
                                           name="table_name"
                                           class="regular-text">
                                    <p class="description">Opcional: un nombre descriptivo</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-capacity">Capacidad</label>
                                </th>
                                <td>
                                    <input type="number"
                                           id="table-capacity"
                                           name="capacity"
                                           min="1"
                                           max="20"
                                           value="4"
                                           class="small-text">
                                    <p class="description">Número de comensales</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-location">Ubicación</label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="table-location"
                                           name="location"
                                           class="regular-text"
                                           placeholder="Ej: Terraza, Salón Principal">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-notes">Notas</label>
                                </th>
                                <td>
                                    <textarea id="table-notes"
                                              name="notes"
                                              rows="3"
                                              class="large-text"></textarea>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close">Cancelar</button>
                    <button type="button" id="save-table" class="button button-primary">Guardar Mesa</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Obtener pedidos
     */
    public function get_orders_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $args = [
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'table_id' => absint($_POST['table_id'] ?? 0),
            'limit' => absint($_POST['per_page'] ?? 50),
            'include_items' => true,
        ];

        // Filtro de fecha
        if (!empty($_POST['date'])) {
            switch ($_POST['date']) {
                case 'today':
                    $args['date_from'] = date('Y-m-d 00:00:00');
                    break;
                case 'yesterday':
                    $args['date_from'] = date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $args['date_to'] = date('Y-m-d 23:59:59', strtotime('-1 day'));
                    break;
                case 'week':
                    $args['date_from'] = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'month':
                    $args['date_from'] = date('Y-m-d 00:00:00', strtotime('-30 days'));
                    break;
            }
        }

        // Limpiar args vacíos
        $args = array_filter($args);

        $order_manager = Flavor_Order_Manager::get_instance();
        $orders = $order_manager->get_orders($args);

        wp_send_json_success([
            'orders' => $orders,
            'total' => count($orders),
        ]);
    }

    /**
     * AJAX: Actualizar estado de pedido
     */
    public function update_order_status_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $order_id = absint($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $order_manager = Flavor_Order_Manager::get_instance();
        $result = $order_manager->update_status($order_id, $new_status, $notes);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Estado actualizado correctamente',
            'order' => $result,
        ]);
    }

    /**
     * AJAX: Obtener detalles del pedido
     */
    public function get_order_details_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $order_id = absint($_POST['order_id']);

        $order_manager = Flavor_Order_Manager::get_instance();
        $order = $order_manager->get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Pedido no encontrado']);
        }

        // Obtener historial
        $history = $order_manager->get_status_history($order_id);

        wp_send_json_success([
            'order' => $order,
            'history' => $history,
        ]);
    }

    /**
     * AJAX: Obtener mesas
     */
    public function get_tables_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $table_manager = Flavor_Table_Manager::get_instance();
        $tables = $table_manager->get_tables();
        $stats = $table_manager->get_statistics();

        wp_send_json_success([
            'tables' => $tables,
            'statistics' => $stats,
        ]);
    }

    /**
     * AJAX: Crear mesa
     */
    public function create_table_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $data = [
            'table_number' => sanitize_text_field($_POST['table_number']),
            'table_name' => sanitize_text_field($_POST['table_name'] ?? ''),
            'capacity' => absint($_POST['capacity'] ?? 4),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->create_table($data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Mesa creada exitosamente',
            'table' => $result,
        ]);
    }

    /**
     * AJAX: Actualizar mesa
     */
    public function update_table_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $table_id = absint($_POST['table_id']);
        $data = [
            'table_number' => sanitize_text_field($_POST['table_number']),
            'table_name' => sanitize_text_field($_POST['table_name'] ?? ''),
            'capacity' => absint($_POST['capacity'] ?? 4),
            'status' => sanitize_text_field($_POST['status'] ?? 'available'),
            'location' => sanitize_text_field($_POST['location'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ];

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->update_table($table_id, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Mesa actualizada exitosamente',
            'table' => $result,
        ]);
    }

    /**
     * AJAX: Eliminar mesa
     */
    public function delete_table_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $table_id = absint($_POST['table_id']);

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->delete_table($table_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Mesa eliminada exitosamente']);
    }
}
