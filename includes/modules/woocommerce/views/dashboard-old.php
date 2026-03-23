<?php
/**
 * Vista del Dashboard de WooCommerce
 *
 * @package FlavorChatIA
 * @subpackage WooCommerce
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

// Verificar que WooCommerce está activo
if (!class_exists('WooCommerce')) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Dashboard de WooCommerce', 'flavor-chat-ia'); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce no está instalado o activado.', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Obtener estadísticas
$fecha_inicio_mes = date('Y-m-01');
$fecha_inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fecha_hoy = date('Y-m-d');

// Pedidos de hoy
$pedidos_hoy = wc_get_orders([
    'date_created' => '>=' . strtotime('today midnight'),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_hoy = count($pedidos_hoy);

// Ventas de hoy
$ventas_hoy = 0;
foreach ($pedidos_hoy as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_hoy += floatval($order->get_total());
    }
}

// Pedidos de la semana
$pedidos_semana = wc_get_orders([
    'date_created' => '>=' . strtotime($fecha_inicio_semana),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_semana = count($pedidos_semana);

// Ventas de la semana
$ventas_semana = 0;
foreach ($pedidos_semana as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_semana += floatval($order->get_total());
    }
}

// Pedidos del mes
$pedidos_mes = wc_get_orders([
    'date_created' => '>=' . strtotime($fecha_inicio_mes),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_mes = count($pedidos_mes);

// Ventas del mes
$ventas_mes = 0;
foreach ($pedidos_mes as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_mes += floatval($order->get_total());
    }
}

// Pedidos por estado
$pedidos_pendientes = wc_get_orders([
    'status' => 'pending',
    'return' => 'ids',
    'limit' => -1,
]);
$total_pendientes = count($pedidos_pendientes);

$pedidos_procesando = wc_get_orders([
    'status' => 'processing',
    'return' => 'ids',
    'limit' => -1,
]);
$total_procesando = count($pedidos_procesando);

$pedidos_en_espera = wc_get_orders([
    'status' => 'on-hold',
    'return' => 'ids',
    'limit' => -1,
]);
$total_en_espera = count($pedidos_en_espera);

// Productos
$productos_totales = wp_count_posts('product');
$total_productos = $productos_totales->publish ?? 0;

// Productos sin stock
$productos_sin_stock_query = new WP_Query([
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
]);
$productos_sin_stock = $productos_sin_stock_query->found_posts;

// Productos con stock bajo
$umbral_stock_bajo = get_option('woocommerce_notify_low_stock_amount', 2);
$productos_stock_bajo_query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
        [
            'key' => '_stock',
            'value' => $umbral_stock_bajo,
            'compare' => '<=',
            'type' => 'NUMERIC',
        ],
        [
            'key' => '_stock_status',
            'value' => 'instock',
        ],
    ],
]);
$productos_stock_bajo = $productos_stock_bajo_query->found_posts;

// Pedidos recientes
$pedidos_recientes = wc_get_orders([
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Productos más vendidos
$productos_mas_vendidos = wc_get_products([
    'limit' => 5,
    'orderby' => 'meta_value_num',
    'meta_key' => 'total_sales',
    'order' => 'DESC',
]);

// Formatear precios
$currency_symbol = get_woocommerce_currency_symbol();

?>
<div class="wrap flavor-admin-page flavor-woocommerce-dashboard">
    <h1>
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e('Dashboard de WooCommerce', 'flavor-chat-ia'); ?>
    </h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid">
        <div class="stat-card stat-ventas-hoy">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_hoy); ?></span>
                <span class="stat-label"><?php esc_html_e('Ventas hoy', 'flavor-chat-ia'); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', 'flavor-chat-ia'), $total_pedidos_hoy); ?></span>
            </div>
        </div>

        <div class="stat-card stat-ventas-semana">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_semana); ?></span>
                <span class="stat-label"><?php esc_html_e('Esta semana', 'flavor-chat-ia'); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', 'flavor-chat-ia'), $total_pedidos_semana); ?></span>
            </div>
        </div>

        <div class="stat-card stat-ventas-mes">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_mes); ?></span>
                <span class="stat-label"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', 'flavor-chat-ia'), $total_pedidos_mes); ?></span>
            </div>
        </div>

        <div class="stat-card stat-productos">
            <div class="stat-icon">
                <span class="dashicons dashicons-archive"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($total_productos); ?></span>
                <span class="stat-label"><?php esc_html_e('Productos', 'flavor-chat-ia'); ?></span>
                <span class="stat-secondary">
                    <?php if ($productos_sin_stock > 0): ?>
                        <span class="text-danger"><?php printf(esc_html__('%d sin stock', 'flavor-chat-ia'), $productos_sin_stock); ?></span>
                    <?php else: ?>
                        <?php esc_html_e('Todos en stock', 'flavor-chat-ia'); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Estado de pedidos -->
    <div class="flavor-section">
        <h2><?php esc_html_e('Estado de Pedidos', 'flavor-chat-ia'); ?></h2>
        <div class="flavor-order-status-grid">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-pending')); ?>" class="order-status-card status-pending">
                <span class="status-count"><?php echo esc_html($total_pendientes); ?></span>
                <span class="status-label"><?php esc_html_e('Pendientes de pago', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-processing')); ?>" class="order-status-card status-processing">
                <span class="status-count"><?php echo esc_html($total_procesando); ?></span>
                <span class="status-label"><?php esc_html_e('Procesando', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-on-hold')); ?>" class="order-status-card status-on-hold">
                <span class="status-count"><?php echo esc_html($total_en_espera); ?></span>
                <span class="status-label"><?php esc_html_e('En espera', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <div class="flavor-columns">
        <!-- Pedidos recientes -->
        <div class="flavor-column">
            <div class="flavor-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Pedidos Recientes', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="button button-small">
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <div class="card-content">
                    <?php if (!empty($pedidos_recientes)): ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Pedido', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Cliente', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Total', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos_recientes as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                                #<?php echo esc_html($order->get_order_number()); ?>
                                            </a>
                                            <br>
                                            <small><?php echo esc_html($order->get_date_created()->date_i18n('d/m/Y H:i')); ?></small>
                                        </td>
                                        <td>
                                            <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
                                        </td>
                                        <td>
                                            <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data"><?php esc_html_e('No hay pedidos recientes.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Productos más vendidos -->
        <div class="flavor-column">
            <div class="flavor-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Productos Más Vendidos', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="button button-small">
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <div class="card-content">
                    <?php if (!empty($productos_mas_vendidos)): ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Producto', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Ventas', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_mas_vendidos as $producto): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($producto->get_id())); ?>">
                                                <?php echo esc_html($producto->get_name()); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo wp_kses_post($producto->get_price_html()); ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html($producto->get_total_sales()); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data"><?php esc_html_e('No hay datos de ventas disponibles.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alertas de stock -->
            <?php if ($productos_sin_stock > 0 || $productos_stock_bajo > 0): ?>
                <div class="flavor-card flavor-card-warning">
                    <div class="card-header">
                        <h3><?php esc_html_e('Alertas de Stock', 'flavor-chat-ia'); ?></h3>
                    </div>
                    <div class="card-content">
                        <ul class="stock-alerts">
                            <?php if ($productos_sin_stock > 0): ?>
                                <li class="alert-danger">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php printf(
                                        esc_html__('%d productos sin stock', 'flavor-chat-ia'),
                                        $productos_sin_stock
                                    ); ?>
                                </li>
                            <?php endif; ?>
                            <?php if ($productos_stock_bajo > 0): ?>
                                <li class="alert-warning">
                                    <span class="dashicons dashicons-info"></span>
                                    <?php printf(
                                        esc_html__('%d productos con stock bajo', 'flavor-chat-ia'),
                                        $productos_stock_bajo
                                    ); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flavor-woocommerce-dashboard h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-card .stat-icon {
    background: #f0f6fc;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card .stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.stat-card .stat-content {
    display: flex;
    flex-direction: column;
}

.stat-card .stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.stat-card .stat-label {
    color: #646970;
    font-size: 14px;
}

.stat-card .stat-secondary {
    color: #787c82;
    font-size: 12px;
    margin-top: 4px;
}

.stat-card .text-danger {
    color: #d63638;
}

.flavor-section {
    margin: 30px 0;
}

.flavor-section h2 {
    margin-bottom: 15px;
}

.flavor-order-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.order-status-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.order-status-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-status-card .status-count {
    display: block;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.order-status-card .status-label {
    color: #646970;
    font-size: 14px;
}

.order-status-card.status-pending .status-count { color: #996800; }
.order-status-card.status-processing .status-count { color: #0073aa; }
.order-status-card.status-on-hold .status-count { color: #94660c; }

.flavor-columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
}

.flavor-card .card-header h3 {
    margin: 0;
    font-size: 16px;
}

.flavor-card .card-content {
    padding: 15px 20px;
}

.flavor-card-warning {
    border-color: #dba617;
}

.stock-alerts {
    margin: 0;
    padding: 0;
    list-style: none;
}

.stock-alerts li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
}

.stock-alerts .alert-danger { color: #d63638; }
.stock-alerts .alert-warning { color: #996800; }

.order-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.order-status.status-pending { background: #f0f0f0; color: #996800; }
.order-status.status-processing { background: #e5f4ff; color: #0073aa; }
.order-status.status-on-hold { background: #fff3e3; color: #94660c; }
.order-status.status-completed { background: #e6f4ea; color: #2e7d32; }
.order-status.status-cancelled { background: #fce8e8; color: #d63638; }
.order-status.status-refunded { background: #f0f0f0; color: #646970; }
.order-status.status-failed { background: #fce8e8; color: #d63638; }

.no-data {
    color: #646970;
    text-align: center;
    padding: 20px;
}

@media (max-width: 782px) {
    .flavor-columns {
        grid-template-columns: 1fr;
    }
}
</style>
