<?php
/**
 * Vista de Gestión de Pedidos de WooCommerce
 *
 * @package FlavorPlatform
 * @subpackage WooCommerce
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// Verificar que WooCommerce está activo
if (!class_exists('WooCommerce')) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Gestión de Pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce no está instalado o activado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Parámetros de filtrado
$estado_filtro = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 20;

// Construir query de pedidos
$args_pedidos = [
    'limit' => $por_pagina,
    'offset' => ($pagina_actual - 1) * $por_pagina,
    'orderby' => 'date',
    'order' => 'DESC',
    'paginate' => true,
];

if (!empty($estado_filtro)) {
    $args_pedidos['status'] = $estado_filtro;
}

if (!empty($busqueda)) {
    // Buscar por número de pedido, email o nombre
    $args_pedidos['s'] = $busqueda;
}

$resultado_pedidos = wc_get_orders($args_pedidos);
$pedidos = $resultado_pedidos->orders;
$total_pedidos = $resultado_pedidos->total;
$total_paginas = $resultado_pedidos->max_num_pages;

// Contar pedidos por estado
$estados_wc = wc_get_order_statuses();
$conteo_estados = [];
foreach (array_keys($estados_wc) as $estado) {
    $estado_sin_prefijo = str_replace('wc-', '', $estado);
    $conteo = wc_orders_count($estado_sin_prefijo);
    if ($conteo > 0) {
        $conteo_estados[$estado] = $conteo;
    }
}

// URL base para paginación
$url_base = admin_url('admin.php?page=flavor-woocommerce-pedidos');

?>
<div class="wrap flavor-admin-page flavor-woocommerce-pedidos">
    <h1>
        <span class="dashicons dashicons-clipboard"></span>
        <?php esc_html_e('Gestión de Pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <!-- Filtros por estado -->
    <div class="flavor-status-filters">
        <a href="<?php echo esc_url($url_base); ?>"
           class="status-filter <?php echo empty($estado_filtro) ? 'active' : ''; ?>">
            <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="count">(<?php echo esc_html($total_pedidos); ?>)</span>
        </a>
        <?php foreach ($conteo_estados as $estado => $conteo): ?>
            <?php $estado_sin_prefijo = str_replace('wc-', '', $estado); ?>
            <a href="<?php echo esc_url(add_query_arg('status', $estado_sin_prefijo, $url_base)); ?>"
               class="status-filter <?php echo $estado_filtro === $estado_sin_prefijo ? 'active' : ''; ?>">
                <?php echo esc_html($estados_wc[$estado]); ?>
                <span class="count">(<?php echo esc_html($conteo); ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Barra de búsqueda -->
    <div class="flavor-search-bar">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-woocommerce-pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php if (!empty($estado_filtro)): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($estado_filtro); ?>">
            <?php endif; ?>
            <input type="search"
                   name="s"
                   value="<?php echo esc_attr($busqueda); ?>"
                   placeholder="<?php esc_attr_e('Buscar por # pedido, email o cliente...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <button type="submit" class="button">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php if (!empty($busqueda)): ?>
                <a href="<?php echo esc_url($url_base); ?>" class="button">
                    <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de pedidos -->
    <div class="flavor-card">
        <div class="card-content">
            <?php if (!empty($pedidos)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-order"><?php esc_html_e('Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-date"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-customer"><?php esc_html_e('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-status"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-items"><?php esc_html_e('Items', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-total"><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th class="column-actions"><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $order): ?>
                            <?php
                            $order_id = $order->get_id();
                            $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                            $customer_email = $order->get_billing_email();
                            $order_status = $order->get_status();
                            $order_status_name = wc_get_order_status_name($order_status);
                            $items_count = $order->get_item_count();
                            ?>
                            <tr>
                                <td class="column-order">
                                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" class="order-number">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html($order->get_date_created()->date_i18n('d/m/Y')); ?>
                                    <br>
                                    <small><?php echo esc_html($order->get_date_created()->date_i18n('H:i')); ?></small>
                                </td>
                                <td class="column-customer">
                                    <strong><?php echo esc_html($customer_name); ?></strong>
                                    <br>
                                    <a href="mailto:<?php echo esc_attr($customer_email); ?>">
                                        <?php echo esc_html($customer_email); ?>
                                    </a>
                                </td>
                                <td class="column-status">
                                    <span class="order-status status-<?php echo esc_attr($order_status); ?>">
                                        <?php echo esc_html($order_status_name); ?>
                                    </span>
                                </td>
                                <td class="column-items">
                                    <?php printf(esc_html(_n('%d item', '%d items', $items_count, FLAVOR_PLATFORM_TEXT_DOMAIN)), $items_count); ?>
                                </td>
                                <td class="column-total">
                                    <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                    <br>
                                    <small><?php echo esc_html($order->get_payment_method_title()); ?></small>
                                </td>
                                <td class="column-actions">
                                    <div class="action-buttons">
                                        <a href="<?php echo esc_url($order->get_edit_order_url()); ?>"
                                           class="button button-small"
                                           title="<?php esc_attr_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        <?php if ($order_status === 'processing'): ?>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $order_id), 'woocommerce-mark-order-status')); ?>"
                                               class="button button-small button-primary"
                                               title="<?php esc_attr_e('Marcar como completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons dashicons-yes"></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-pagination">
                        <?php
                        $args_paginacion = [];
                        if (!empty($estado_filtro)) {
                            $args_paginacion['status'] = $estado_filtro;
                        }
                        if (!empty($busqueda)) {
                            $args_paginacion['s'] = $busqueda;
                        }
                        ?>

                        <span class="pagination-info">
                            <?php printf(
                                esc_html__('Mostrando %1$d-%2$d de %3$d pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                (($pagina_actual - 1) * $por_pagina) + 1,
                                min($pagina_actual * $por_pagina, $total_pedidos),
                                $total_pedidos
                            ); ?>
                        </span>

                        <div class="pagination-links">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&laquo;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $pagina_actual - 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&lsaquo;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            <?php endif; ?>

                            <span class="current-page">
                                <?php printf(esc_html__('Página %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN), $pagina_actual, $total_paginas); ?>
                            </span>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $pagina_actual + 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&rsaquo;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $total_paginas]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&raquo;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-data">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p>
                        <?php if (!empty($busqueda)): ?>
                            <?php printf(esc_html__('No se encontraron pedidos para "%s".', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($busqueda)); ?>
                        <?php elseif (!empty($estado_filtro)): ?>
                            <?php esc_html_e('No hay pedidos con este estado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php else: ?>
                            <?php esc_html_e('No hay pedidos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flavor-woocommerce-pedidos h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-status-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.status-filter {
    padding: 8px 15px;
    text-decoration: none;
    color: #646970;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.status-filter:hover {
    background: #f0f0f0;
    color: #1d2327;
}

.status-filter.active {
    background: #2271b1;
    color: #fff;
}

.status-filter .count {
    opacity: 0.8;
}

.flavor-search-bar {
    margin: 20px 0;
}

.flavor-search-bar form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.flavor-search-bar input[type="search"] {
    min-width: 300px;
    padding: 8px 12px;
}

.flavor-search-bar .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.flavor-card .card-content {
    padding: 0;
}

.flavor-card table {
    margin: 0;
    border: none;
}

.flavor-card table th,
.flavor-card table td {
    padding: 12px 15px;
}

.column-order { width: 100px; }
.column-date { width: 100px; }
.column-customer { width: auto; }
.column-status { width: 120px; }
.column-items { width: 80px; text-align: center; }
.column-total { width: 120px; }
.column-actions { width: 100px; text-align: center; }

.order-number {
    font-weight: 600;
    color: #2271b1;
    text-decoration: none;
}

.order-number:hover {
    text-decoration: underline;
}

.order-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.order-status.status-pending { background: #f0f0f0; color: #996800; }
.order-status.status-processing { background: #e5f4ff; color: #0073aa; }
.order-status.status-on-hold { background: #fff3e3; color: #94660c; }
.order-status.status-completed { background: #e6f4ea; color: #2e7d32; }
.order-status.status-cancelled { background: #fce8e8; color: #d63638; }
.order-status.status-refunded { background: #f0f0f0; color: #646970; }
.order-status.status-failed { background: #fce8e8; color: #d63638; }

.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-buttons .button {
    padding: 4px 8px;
    min-height: auto;
}

.action-buttons .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
}

.pagination-info {
    color: #646970;
}

.pagination-links {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination-links .current-page {
    padding: 0 10px;
    color: #1d2327;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #646970;
}

.no-data .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

@media (max-width: 782px) {
    .flavor-search-bar form {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-search-bar input[type="search"] {
        min-width: auto;
        width: 100%;
    }

    .column-items,
    .column-date {
        display: none;
    }
}
</style>
