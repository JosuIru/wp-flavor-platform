<?php
/**
 * Vista de Gestión de Productos de WooCommerce
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
        <h1><?php esc_html_e('Gestión de Productos', 'flavor-chat-ia'); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce no está instalado o activado.', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Parámetros de filtrado
$categoria_filtro = isset($_GET['category']) ? intval($_GET['category']) : 0;
$stock_filtro = isset($_GET['stock']) ? sanitize_text_field($_GET['stock']) : '';
$busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 20;

// Construir query de productos
$args_productos = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => $por_pagina,
    'paged' => $pagina_actual,
    'orderby' => 'date',
    'order' => 'DESC',
];

if (!empty($categoria_filtro)) {
    $args_productos['tax_query'] = [
        [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $categoria_filtro,
        ],
    ];
}

if (!empty($stock_filtro)) {
    $args_productos['meta_query'] = [
        [
            'key' => '_stock_status',
            'value' => $stock_filtro,
        ],
    ];
}

if (!empty($busqueda)) {
    $args_productos['s'] = $busqueda;
}

$query_productos = new WP_Query($args_productos);
$productos = $query_productos->posts;
$total_productos = $query_productos->found_posts;
$total_paginas = $query_productos->max_num_pages;

// Obtener categorías para el filtro
$categorias = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
]);

// Estadísticas rápidas
$total_publicados = wp_count_posts('product')->publish;
$productos_en_stock = wc_get_products(['stock_status' => 'instock', 'return' => 'ids', 'limit' => -1]);
$total_en_stock = count($productos_en_stock);
$productos_sin_stock = wc_get_products(['stock_status' => 'outofstock', 'return' => 'ids', 'limit' => -1]);
$total_sin_stock = count($productos_sin_stock);
$productos_backorder = wc_get_products(['stock_status' => 'onbackorder', 'return' => 'ids', 'limit' => -1]);
$total_backorder = count($productos_backorder);

// URL base para paginación
$url_base = admin_url('admin.php?page=flavor-woocommerce-productos');

?>
<div class="wrap flavor-admin-page flavor-woocommerce-productos">
    <h1>
        <span class="dashicons dashicons-archive"></span>
        <?php esc_html_e('Gestión de Productos', 'flavor-chat-ia'); ?>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="page-title-action">
            <?php esc_html_e('Añadir producto', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Estadísticas rápidas -->
    <div class="flavor-quick-stats">
        <a href="<?php echo esc_url($url_base); ?>" class="quick-stat <?php echo empty($stock_filtro) ? 'active' : ''; ?>">
            <span class="stat-value"><?php echo esc_html($total_publicados); ?></span>
            <span class="stat-label"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('stock', 'instock', $url_base)); ?>"
           class="quick-stat stat-success <?php echo $stock_filtro === 'instock' ? 'active' : ''; ?>">
            <span class="stat-value"><?php echo esc_html($total_en_stock); ?></span>
            <span class="stat-label"><?php esc_html_e('En stock', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('stock', 'outofstock', $url_base)); ?>"
           class="quick-stat stat-danger <?php echo $stock_filtro === 'outofstock' ? 'active' : ''; ?>">
            <span class="stat-value"><?php echo esc_html($total_sin_stock); ?></span>
            <span class="stat-label"><?php esc_html_e('Sin stock', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('stock', 'onbackorder', $url_base)); ?>"
           class="quick-stat stat-warning <?php echo $stock_filtro === 'onbackorder' ? 'active' : ''; ?>">
            <span class="stat-value"><?php echo esc_html($total_backorder); ?></span>
            <span class="stat-label"><?php esc_html_e('Bajo pedido', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="flavor-filters-bar">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-woocommerce-productos', 'flavor-chat-ia'); ?>">

            <select name="category" class="filter-select">
                <option value=""><?php esc_html_e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo esc_attr($categoria->term_id); ?>"
                            <?php selected($categoria_filtro, $categoria->term_id); ?>>
                        <?php echo esc_html($categoria->name); ?> (<?php echo esc_html($categoria->count); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="stock" class="filter-select">
                <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('instock', 'flavor-chat-ia'); ?>" <?php selected($stock_filtro, 'instock'); ?>>
                    <?php esc_html_e('En stock', 'flavor-chat-ia'); ?>
                </option>
                <option value="<?php echo esc_attr__('outofstock', 'flavor-chat-ia'); ?>" <?php selected($stock_filtro, 'outofstock'); ?>>
                    <?php esc_html_e('Sin stock', 'flavor-chat-ia'); ?>
                </option>
                <option value="<?php echo esc_attr__('onbackorder', 'flavor-chat-ia'); ?>" <?php selected($stock_filtro, 'onbackorder'); ?>>
                    <?php esc_html_e('Bajo pedido', 'flavor-chat-ia'); ?>
                </option>
            </select>

            <input type="search"
                   name="s"
                   value="<?php echo esc_attr($busqueda); ?>"
                   placeholder="<?php esc_attr_e('Buscar productos...', 'flavor-chat-ia'); ?>">

            <button type="submit" class="button">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
            </button>

            <?php if (!empty($categoria_filtro) || !empty($stock_filtro) || !empty($busqueda)): ?>
                <a href="<?php echo esc_url($url_base); ?>" class="button">
                    <?php esc_html_e('Limpiar filtros', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Grid de productos -->
    <div class="flavor-card">
        <div class="card-content">
            <?php if (!empty($productos)): ?>
                <div class="products-grid">
                    <?php foreach ($productos as $post_producto): ?>
                        <?php
                        $producto = wc_get_product($post_producto->ID);
                        if (!$producto) continue;

                        $product_id = $producto->get_id();
                        $imagen_id = $producto->get_image_id();
                        $imagen_url = $imagen_id ? wp_get_attachment_image_url($imagen_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');
                        $stock_status = $producto->get_stock_status();
                        $stock_quantity = $producto->get_stock_quantity();
                        $categorias_producto = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
                        ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo esc_url($imagen_url); ?>"
                                     alt="<?php echo esc_attr($producto->get_name()); ?>">
                                <span class="stock-badge stock-<?php echo esc_attr($stock_status); ?>">
                                    <?php
                                    if ($stock_status === 'instock') {
                                        if ($stock_quantity !== null) {
                                            printf(esc_html__('Stock: %d', 'flavor-chat-ia'), $stock_quantity);
                                        } else {
                                            esc_html_e('En stock', 'flavor-chat-ia');
                                        }
                                    } elseif ($stock_status === 'outofstock') {
                                        esc_html_e('Sin stock', 'flavor-chat-ia');
                                    } else {
                                        esc_html_e('Bajo pedido', 'flavor-chat-ia');
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title">
                                    <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>">
                                        <?php echo esc_html($producto->get_name()); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($categorias_producto)): ?>
                                    <div class="product-categories">
                                        <?php echo esc_html(implode(', ', $categorias_producto)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="product-price">
                                    <?php echo wp_kses_post($producto->get_price_html()); ?>
                                </div>
                                <?php if ($producto->get_sku()): ?>
                                    <div class="product-sku">
                                        SKU: <?php echo esc_html($producto->get_sku()); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="product-meta">
                                    <span class="product-type">
                                        <?php echo esc_html(wc_get_product_types()[$producto->get_type()] ?? $producto->get_type()); ?>
                                    </span>
                                    <span class="product-sales">
                                        <?php printf(esc_html__('%d ventas', 'flavor-chat-ia'), $producto->get_total_sales()); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="product-actions">
                                <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>"
                                   class="button button-small"
                                   title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo esc_url(get_permalink($product_id)); ?>"
                                   class="button button-small"
                                   target="_blank"
                                   title="<?php esc_attr_e('Ver en tienda', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit#inventory_product_data')); ?>"
                                   class="button button-small"
                                   title="<?php esc_attr_e('Gestionar stock', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-archive"></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flavor-pagination">
                        <?php
                        $args_paginacion = [];
                        if (!empty($categoria_filtro)) {
                            $args_paginacion['category'] = $categoria_filtro;
                        }
                        if (!empty($stock_filtro)) {
                            $args_paginacion['stock'] = $stock_filtro;
                        }
                        if (!empty($busqueda)) {
                            $args_paginacion['s'] = $busqueda;
                        }
                        ?>

                        <span class="pagination-info">
                            <?php printf(
                                esc_html__('Mostrando %1$d-%2$d de %3$d productos', 'flavor-chat-ia'),
                                (($pagina_actual - 1) * $por_pagina) + 1,
                                min($pagina_actual * $por_pagina, $total_productos),
                                $total_productos
                            ); ?>
                        </span>

                        <div class="pagination-links">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&laquo;', 'flavor-chat-ia'); ?></a>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $pagina_actual - 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&lsaquo;', 'flavor-chat-ia'); ?></a>
                            <?php endif; ?>

                            <span class="current-page">
                                <?php printf(esc_html__('Página %1$d de %2$d', 'flavor-chat-ia'), $pagina_actual, $total_paginas); ?>
                            </span>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $pagina_actual + 1]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></a>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($args_paginacion, ['paged' => $total_paginas]), $url_base)); ?>"
                                   class="button"><?php echo esc_html__('&raquo;', 'flavor-chat-ia'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-data">
                    <span class="dashicons dashicons-archive"></span>
                    <p>
                        <?php if (!empty($busqueda)): ?>
                            <?php printf(esc_html__('No se encontraron productos para "%s".', 'flavor-chat-ia'), esc_html($busqueda)); ?>
                        <?php elseif (!empty($categoria_filtro) || !empty($stock_filtro)): ?>
                            <?php esc_html_e('No hay productos que coincidan con los filtros seleccionados.', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <?php esc_html_e('No hay productos registrados.', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </p>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="button button-primary">
                        <?php esc_html_e('Crear primer producto', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flavor-woocommerce-productos h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-quick-stats {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}

.quick-stat {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px 25px;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease;
    min-width: 120px;
}

.quick-stat:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.quick-stat.active {
    border-color: #2271b1;
    background: #f0f6fc;
}

.quick-stat .stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1d2327;
}

.quick-stat .stat-label {
    color: #646970;
    font-size: 13px;
}

.quick-stat.stat-success .stat-value { color: #2e7d32; }
.quick-stat.stat-danger .stat-value { color: #d63638; }
.quick-stat.stat-warning .stat-value { color: #996800; }

.flavor-filters-bar {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.flavor-filters-bar form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.filter-select {
    min-width: 180px;
}

.flavor-filters-bar input[type="search"] {
    min-width: 200px;
    padding: 6px 12px;
}

.flavor-filters-bar .button {
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
    padding: 20px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.product-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-image {
    position: relative;
    height: 180px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.stock-badge.stock-instock {
    background: #e6f4ea;
    color: #2e7d32;
}

.stock-badge.stock-outofstock {
    background: #fce8e8;
    color: #d63638;
}

.stock-badge.stock-onbackorder {
    background: #fff3e3;
    color: #996800;
}

.product-info {
    padding: 15px;
}

.product-title {
    margin: 0 0 8px 0;
    font-size: 15px;
    line-height: 1.4;
}

.product-title a {
    color: #1d2327;
    text-decoration: none;
}

.product-title a:hover {
    color: #2271b1;
}

.product-categories {
    color: #646970;
    font-size: 12px;
    margin-bottom: 8px;
}

.product-price {
    font-size: 16px;
    font-weight: 600;
    color: #2e7d32;
    margin-bottom: 8px;
}

.product-price del {
    color: #999;
    font-weight: normal;
}

.product-price ins {
    text-decoration: none;
}

.product-sku {
    color: #646970;
    font-size: 12px;
    margin-bottom: 8px;
}

.product-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #646970;
}

.product-actions {
    display: flex;
    gap: 5px;
    padding: 10px 15px;
    background: #f9f9f9;
    border-top: 1px solid #e0e0e0;
}

.product-actions .button {
    flex: 1;
    text-align: center;
    padding: 6px;
}

.product-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    margin-top: 20px;
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
    .flavor-quick-stats {
        flex-wrap: wrap;
    }

    .quick-stat {
        flex: 1 1 calc(50% - 10px);
        min-width: 100px;
    }

    .flavor-filters-bar form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-select,
    .flavor-filters-bar input[type="search"] {
        min-width: auto;
        width: 100%;
    }

    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>
