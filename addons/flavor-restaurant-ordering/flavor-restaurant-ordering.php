<?php
/**
 * Addon Name: Flavor Restaurant Ordering
 * Description: Sistema completo de gestión de pedidos y reservas para restaurantes
 * Version: 1.0.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Chat IA 3.0.0+
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del addon
if (!defined('FLAVOR_RESTAURANT_VERSION')) {
    define('FLAVOR_RESTAURANT_VERSION', '1.0.0');
}
if (!defined('FLAVOR_RESTAURANT_PATH')) {
    define('FLAVOR_RESTAURANT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_RESTAURANT_URL')) {
    define('FLAVOR_RESTAURANT_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_restaurant_ordering_register');

function flavor_restaurant_ordering_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('restaurant-ordering', [
        'name' => __('Restaurant Ordering', 'flavor-restaurant-ordering'),
        'version' => FLAVOR_RESTAURANT_VERSION,
        'description' => __('Sistema completo de gestión de pedidos y reservas para restaurantes.', 'flavor-restaurant-ordering'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-food',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.4',
                'wordpress' => '5.8',
            ],
        ],
        'init_callback' => 'flavor_restaurant_ordering_init',
        'settings_page' => 'admin.php?page=flavor-restaurant-settings',
        'documentation_url' => 'https://gailu.net/docs/restaurant-ordering',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 */
function flavor_restaurant_ordering_init() {
    // Cargar managers del sistema
    require_once FLAVOR_RESTAURANT_PATH . 'includes/class-restaurant-manager.php';
    require_once FLAVOR_RESTAURANT_PATH . 'includes/class-table-manager.php';
    require_once FLAVOR_RESTAURANT_PATH . 'includes/class-order-manager.php';
    require_once FLAVOR_RESTAURANT_PATH . 'includes/class-reservation-manager.php';
    require_once FLAVOR_RESTAURANT_PATH . 'includes/class-restaurant-api.php';

    // Cargar admin solo en backend
    if (is_admin()) {
        require_once FLAVOR_RESTAURANT_PATH . 'admin/class-restaurant-settings.php';
        require_once FLAVOR_RESTAURANT_PATH . 'admin/class-order-admin.php';
        require_once FLAVOR_RESTAURANT_PATH . 'admin/class-reservation-admin.php';
    }

    // Inicializar managers
    if (class_exists('Flavor_Restaurant_Manager')) {
        Flavor_Restaurant_Manager::get_instance();
    }

    if (class_exists('Flavor_Table_Manager')) {
        Flavor_Table_Manager::get_instance();
    }

    if (class_exists('Flavor_Order_Manager')) {
        Flavor_Order_Manager::get_instance();
    }

    if (class_exists('Flavor_Reservation_Manager')) {
        Flavor_Reservation_Manager::get_instance();
    }

    if (class_exists('Flavor_Restaurant_API')) {
        Flavor_Restaurant_API::get_instance();
    }

    // Admin
    if (is_admin()) {
        if (class_exists('Flavor_Restaurant_Settings')) {
            Flavor_Restaurant_Settings::get_instance();
        }

        if (class_exists('Flavor_Order_Admin')) {
            Flavor_Order_Admin::get_instance();
        }

        if (class_exists('Flavor_Reservation_Admin')) {
            Flavor_Reservation_Admin::get_instance();
        }
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_CHAT_IA_DEBUG') && FLAVOR_CHAT_IA_DEBUG) {
        flavor_log_debug( 'Addon inicializado correctamente v' . FLAVOR_RESTAURANT_VERSION, 'RestaurantOrdering' );
    }
}

/**
 * Callback de instalación del addon (llamado al activar desde el panel de addons)
 */
function flavor_restaurant_install() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de mesas
    $tabla_mesas = $wpdb->prefix . 'restaurant_tables';
    $sql_mesas = "CREATE TABLE IF NOT EXISTS $tabla_mesas (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        table_number varchar(50) NOT NULL,
        table_name varchar(255) DEFAULT NULL,
        capacity int(11) DEFAULT 4,
        status varchar(20) DEFAULT 'available',
        qr_code text DEFAULT NULL,
        location varchar(255) DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY table_number (table_number),
        KEY status (status)
    ) $charset_collate;";

    // Tabla de pedidos
    $tabla_pedidos = $wpdb->prefix . 'restaurant_orders';
    $sql_pedidos = "CREATE TABLE IF NOT EXISTS $tabla_pedidos (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_number varchar(50) NOT NULL,
        table_id bigint(20) UNSIGNED DEFAULT NULL,
        customer_name varchar(255) DEFAULT NULL,
        customer_phone varchar(50) DEFAULT NULL,
        customer_email varchar(100) DEFAULT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        status varchar(20) DEFAULT 'pending',
        subtotal decimal(10,2) DEFAULT 0,
        tax decimal(10,2) DEFAULT 0,
        total decimal(10,2) DEFAULT 0,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY order_number (order_number),
        KEY table_id (table_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de items de pedido
    $tabla_items = $wpdb->prefix . 'restaurant_order_items';
    $sql_items = "CREATE TABLE IF NOT EXISTS $tabla_items (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id bigint(20) UNSIGNED NOT NULL,
        post_id bigint(20) UNSIGNED NOT NULL,
        post_type varchar(50) NOT NULL,
        item_name varchar(255) NOT NULL,
        item_category varchar(50) DEFAULT NULL,
        quantity int(11) DEFAULT 1,
        unit_price decimal(10,2) DEFAULT 0,
        subtotal decimal(10,2) DEFAULT 0,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY post_id (post_id)
    ) $charset_collate;";

    // Tabla de historial de estados
    $tabla_historial = $wpdb->prefix . 'restaurant_order_status_history';
    $sql_historial = "CREATE TABLE IF NOT EXISTS $tabla_historial (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id bigint(20) UNSIGNED NOT NULL,
        status varchar(20) NOT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_mesas);
    dbDelta($sql_pedidos);
    dbDelta($sql_items);
    dbDelta($sql_historial);

    // Crear tabla de reservas
    if (class_exists('Flavor_Reservation_Manager')) {
        Flavor_Reservation_Manager::create_table();
    }

    // Configuración inicial
    $configuracion_inicial = [
        'menu_cpts' => [
            'dishes' => [],
            'drinks' => [],
            'desserts' => [],
        ],
        'table_prefix' => 'MESA',
        'order_statuses' => [
            'pending' => 'Pendiente',
            'preparing' => 'Preparando',
            'ready' => 'Listo',
            'served' => 'Servido',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado'
        ],
        'enable_table_qr' => true,
        'enable_notifications' => true,
        'enable_reservations' => true,
        'reservation_duration_default' => 120,
        'reservation_min_advance_hours' => 2,
        'reservation_max_advance_days' => 30,
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'tax_rate' => 10
    ];

    add_option('flavor_restaurant_settings', $configuracion_inicial);
    update_option('flavor_restaurant_db_version', '1.0.0');
    flush_rewrite_rules();
}

/**
 * Callback de desinstalación del addon
 */
function flavor_restaurant_uninstall() {
    delete_option('flavor_restaurant_settings');
    delete_option('flavor_restaurant_db_version');
    flush_rewrite_rules();
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-restaurant-ordering',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
