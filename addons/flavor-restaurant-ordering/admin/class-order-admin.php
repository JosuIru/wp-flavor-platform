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
            __('Pedidos del Restaurante', 'flavor-restaurant-ordering'),
            __('Pedidos', 'flavor-restaurant-ordering'),
            'manage_options',
            'flavor-restaurant-orders',
            [$this, 'render_orders_page']
        );

        // Página de mesas
        add_submenu_page(
            'flavor-platform',
            __('Mesas del Restaurante', 'flavor-restaurant-ordering'),
            __('Mesas', 'flavor-restaurant-ordering'),
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
            'strings' => [
                'loading_orders' => __('Cargando pedidos...', 'flavor-restaurant-ordering'),
                'error_load_orders' => __('Error al cargar pedidos', 'flavor-restaurant-ordering'),
                'loading_details' => __('Cargando detalles...', 'flavor-restaurant-ordering'),
                'error_load_details' => __('Error al cargar detalles del pedido', 'flavor-restaurant-ordering'),
                'status_label' => __('Estado', 'flavor-restaurant-ordering'),
                'change_status' => __('Cambiar Estado', 'flavor-restaurant-ordering'),
                'customer_info' => __('Información del Cliente', 'flavor-restaurant-ordering'),
                'name_label' => __('Nombre', 'flavor-restaurant-ordering'),
                'phone_label' => __('Teléfono', 'flavor-restaurant-ordering'),
                'email_label' => __('Email', 'flavor-restaurant-ordering'),
                'table_label' => __('Mesa', 'flavor-restaurant-ordering'),
                'no_table' => __('Sin mesa', 'flavor-restaurant-ordering'),
                'items_label' => __('Items del Pedido', 'flavor-restaurant-ordering'),
                'item_label' => __('Item', 'flavor-restaurant-ordering'),
                'item_singular' => __('item', 'flavor-restaurant-ordering'),
                'item_plural' => __('items', 'flavor-restaurant-ordering'),
                'quantity_label' => __('Cantidad', 'flavor-restaurant-ordering'),
                'price_label' => __('Precio', 'flavor-restaurant-ordering'),
                'subtotal_label' => __('Subtotal', 'flavor-restaurant-ordering'),
                'tax_label' => __('IVA', 'flavor-restaurant-ordering'),
                'total_label' => __('Total', 'flavor-restaurant-ordering'),
                'notes_label' => __('Notas', 'flavor-restaurant-ordering'),
                'new_status_prompt' => __('Nuevo estado (pending, preparing, ready, served, completed, cancelled):', 'flavor-restaurant-ordering'),
                'notes_optional_prompt' => __('Notas opcionales:', 'flavor-restaurant-ordering'),
                'status_updated' => __('Estado actualizado correctamente', 'flavor-restaurant-ordering'),
                'error_update_status' => __('Error al actualizar el estado', 'flavor-restaurant-ordering'),
                'error_prefix' => __('Error', 'flavor-restaurant-ordering'),
                'loading_tables' => __('Cargando mesas...', 'flavor-restaurant-ordering'),
                'error_load_tables' => __('Error al cargar mesas', 'flavor-restaurant-ordering'),
                'number_label' => __('Número', 'flavor-restaurant-ordering'),
                'capacity_label' => __('Capacidad', 'flavor-restaurant-ordering'),
                'people_label' => __('personas', 'flavor-restaurant-ordering'),
                'location_label' => __('Ubicación', 'flavor-restaurant-ordering'),
                'edit_table' => __('Editar', 'flavor-restaurant-ordering'),
                'delete_table' => __('Eliminar', 'flavor-restaurant-ordering'),
                'edit_table_title' => __('Editar Mesa', 'flavor-restaurant-ordering'),
                'new_table_title' => __('Nueva Mesa', 'flavor-restaurant-ordering'),
                'error_save_table' => __('Error al guardar la mesa', 'flavor-restaurant-ordering'),
                'error_delete_table' => __('Error al eliminar la mesa', 'flavor-restaurant-ordering'),
                'confirm_delete_table' => __('¿Estás seguro de eliminar esta mesa?', 'flavor-restaurant-ordering'),
                'now' => __('Ahora mismo', 'flavor-restaurant-ordering'),
                'minute_ago' => __('Hace 1 minuto', 'flavor-restaurant-ordering'),
                'minutes_ago' => __('Hace %d minutos', 'flavor-restaurant-ordering'),
                'hour_ago' => __('Hace 1 hora', 'flavor-restaurant-ordering'),
                'hours_ago' => __('Hace %d horas', 'flavor-restaurant-ordering'),
                'yesterday' => __('Ayer', 'flavor-restaurant-ordering'),
                'days_ago' => __('Hace %d días', 'flavor-restaurant-ordering'),
            ],
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
                <?php esc_html_e('Gestión de Pedidos', 'flavor-restaurant-ordering'); ?>
            </h1>

            <div class="flavor-restaurant-toolbar">
                <div class="filter-group">
                    <label><?php esc_html_e('Estado:', 'flavor-restaurant-ordering'); ?></label>
                    <select id="filter-status" class="status-filter">
                        <option value=""><?php esc_html_e('Todos', 'flavor-restaurant-ordering'); ?></option>
                        <?php foreach ($statuses as $status => $label): ?>
                        <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php esc_html_e('Mesa:', 'flavor-restaurant-ordering'); ?></label>
                    <select id="filter-table" class="table-filter">
                        <option value=""><?php esc_html_e('Todas las mesas', 'flavor-restaurant-ordering'); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><?php esc_html_e('Fecha:', 'flavor-restaurant-ordering'); ?></label>
                    <select id="filter-date" class="date-filter">
                        <option value="today"><?php esc_html_e('Hoy', 'flavor-restaurant-ordering'); ?></option>
                        <option value="yesterday"><?php esc_html_e('Ayer', 'flavor-restaurant-ordering'); ?></option>
                        <option value="week"><?php esc_html_e('Última semana', 'flavor-restaurant-ordering'); ?></option>
                        <option value="month"><?php esc_html_e('Último mes', 'flavor-restaurant-ordering'); ?></option>
                        <option value=""><?php esc_html_e('Todos', 'flavor-restaurant-ordering'); ?></option>
                    </select>
                </div>

                <button type="button" id="refresh-orders" class="button">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Actualizar', 'flavor-restaurant-ordering'); ?>
                </button>

                <button type="button" id="show-statistics" class="button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Estadísticas', 'flavor-restaurant-ordering'); ?>
                </button>
            </div>

            <!-- Tarjetas de estadísticas rápidas -->
            <div class="orders-statistics-cards">
                <div class="stat-card pending">
                    <span class="stat-icon dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Pendientes', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-pending">-</div>
                    </div>
                </div>

                <div class="stat-card preparing">
                    <span class="stat-icon dashicons dashicons-carrot"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('En preparación', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-preparing">-</div>
                    </div>
                </div>

                <div class="stat-card ready">
                    <span class="stat-icon dashicons dashicons-yes"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Listos', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-ready">-</div>
                    </div>
                </div>

                <div class="stat-card revenue">
                    <span class="stat-icon dashicons dashicons-money-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Ingresos hoy', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-revenue">-</div>
                    </div>
                </div>
            </div>

            <!-- Lista de pedidos -->
            <div class="orders-container">
                <div id="orders-list" class="orders-grid">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e('Cargando pedidos...', 'flavor-restaurant-ordering'); ?>
                    </div>
                </div>

                <div id="no-orders" class="no-results" style="display: none;">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No hay pedidos que coincidan con los filtros seleccionados.', 'flavor-restaurant-ordering'); ?></p>
                </div>
            </div>
        </div>

        <!-- Modal de detalles del pedido -->
        <div id="order-details-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2 id="modal-order-title"><?php esc_html_e('Pedido #', 'flavor-restaurant-ordering'); ?></h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="order-details-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e('Cargando detalles...', 'flavor-restaurant-ordering'); ?>
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close"><?php esc_html_e('Cerrar', 'flavor-restaurant-ordering'); ?></button>
                </div>
            </div>
        </div>

        <!-- Modal de estadísticas -->
        <div id="statistics-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2><?php esc_html_e('Estadísticas del Restaurante', 'flavor-restaurant-ordering'); ?></h2>
                    <button type="button" class="flavor-modal-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="flavor-modal-body" id="statistics-content">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e('Cargando estadísticas...', 'flavor-restaurant-ordering'); ?>
                    </div>
                </div>

                <div class="flavor-modal-footer">
                    <button type="button" class="button button-secondary flavor-modal-close"><?php esc_html_e('Cerrar', 'flavor-restaurant-ordering'); ?></button>
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
                <?php esc_html_e('Gestión de Mesas', 'flavor-restaurant-ordering'); ?>
            </h1>

            <div class="flavor-restaurant-toolbar">
                <button type="button" id="add-table" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Agregar Mesa', 'flavor-restaurant-ordering'); ?>
                </button>

                <button type="button" id="refresh-tables" class="button">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Actualizar', 'flavor-restaurant-ordering'); ?>
                </button>
            </div>

            <!-- Estadísticas de mesas -->
            <div class="tables-statistics-cards">
                <div class="stat-card available">
                    <span class="stat-icon dashicons dashicons-yes-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Disponibles', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-tables-available">-</div>
                    </div>
                </div>

                <div class="stat-card occupied">
                    <span class="stat-icon dashicons dashicons-admin-users"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Ocupadas', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-tables-occupied">-</div>
                    </div>
                </div>

                <div class="stat-card reserved">
                    <span class="stat-icon dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Reservadas', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-tables-reserved">-</div>
                    </div>
                </div>

                <div class="stat-card total">
                    <span class="stat-icon dashicons dashicons-layout"></span>
                    <div class="stat-content">
                        <div class="stat-label"><?php esc_html_e('Total', 'flavor-restaurant-ordering'); ?></div>
                        <div class="stat-value" id="stat-tables-total">-</div>
                    </div>
                </div>
            </div>

            <!-- Lista de mesas -->
            <div class="tables-container">
                <div id="tables-list" class="tables-grid">
                    <div class="loading-indicator">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e('Cargando mesas...', 'flavor-restaurant-ordering'); ?>
                    </div>
                </div>

                <div id="no-tables" class="no-results" style="display: none;">
                    <span class="dashicons dashicons-layout"></span>
                    <p><?php esc_html_e('No hay mesas creadas. ¡Crea tu primera mesa!', 'flavor-restaurant-ordering'); ?></p>
                    <button type="button" class="button button-primary" id="add-first-table">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Crear Primera Mesa', 'flavor-restaurant-ordering'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de crear/editar mesa -->
        <div id="table-form-modal" class="flavor-modal" style="display: none;">
            <div class="flavor-modal-content">
                <div class="flavor-modal-header">
                    <h2 id="modal-table-title"><?php esc_html_e('Nueva Mesa', 'flavor-restaurant-ordering'); ?></h2>
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
                                    <label for="table-number"><?php esc_html_e('Número de Mesa *', 'flavor-restaurant-ordering'); ?></label>
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
                                    <label for="table-name"><?php esc_html_e('Nombre', 'flavor-restaurant-ordering'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="table-name"
                                           name="table_name"
                                           class="regular-text">
                                    <p class="description"><?php esc_html_e('Opcional: un nombre descriptivo', 'flavor-restaurant-ordering'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-capacity"><?php esc_html_e('Capacidad', 'flavor-restaurant-ordering'); ?></label>
                                </th>
                                <td>
                                    <input type="number"
                                           id="table-capacity"
                                           name="capacity"
                                           min="1"
                                           max="20"
                                           value="4"
                                           class="small-text">
                                    <p class="description"><?php esc_html_e('Número de comensales', 'flavor-restaurant-ordering'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-location"><?php esc_html_e('Ubicación', 'flavor-restaurant-ordering'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="table-location"
                                           name="location"
                                           class="regular-text"
                                           placeholder="<?php esc_attr_e('Ej: Terraza, Salón Principal', 'flavor-restaurant-ordering'); ?>">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="table-notes"><?php esc_html_e('Notas', 'flavor-restaurant-ordering'); ?></label>
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
                    <button type="button" class="button button-secondary flavor-modal-close"><?php esc_html_e('Cancelar', 'flavor-restaurant-ordering'); ?></button>
                    <button type="button" id="save-table" class="button button-primary"><?php esc_html_e('Guardar Mesa', 'flavor-restaurant-ordering'); ?></button>
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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
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
            'message' => __('Estado actualizado correctamente', 'flavor-restaurant-ordering'),
            'order' => $result,
        ]);
    }

    /**
     * AJAX: Obtener detalles del pedido
     */
    public function get_order_details_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
        }

        $order_id = absint($_POST['order_id']);

        $order_manager = Flavor_Order_Manager::get_instance();
        $order = $order_manager->get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => __('Pedido no encontrado', 'flavor-restaurant-ordering')]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
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
            'message' => __('Mesa creada exitosamente', 'flavor-restaurant-ordering'),
            'table' => $result,
        ]);
    }

    /**
     * AJAX: Actualizar mesa
     */
    public function update_table_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
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
            'message' => __('Mesa actualizada exitosamente', 'flavor-restaurant-ordering'),
            'table' => $result,
        ]);
    }

    /**
     * AJAX: Eliminar mesa
     */
    public function delete_table_ajax() {
        check_ajax_referer('flavor_restaurant_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-restaurant-ordering')]);
        }

        $table_id = absint($_POST['table_id']);

        $table_manager = Flavor_Table_Manager::get_instance();
        $result = $table_manager->delete_table($table_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Mesa eliminada exitosamente', 'flavor-restaurant-ordering')]);
    }
}
