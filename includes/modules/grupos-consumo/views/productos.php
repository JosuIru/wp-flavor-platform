<?php
/**
 * Vista Productos - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = 20;
$search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
$filtro_productor = absint($_GET['productor'] ?? 0);
$filtro_estado = sanitize_key($_GET['estado'] ?? 'publish');
$current_page = sanitize_key($_GET['page'] ?? 'gc-productos');

$allowed_status = ['publish', 'draft', 'pending', 'private'];
if (!in_array($filtro_estado, $allowed_status, true)) {
    $filtro_estado = 'publish';
}

$query_args = [
    'post_type' => 'gc_producto',
    'post_status' => $filtro_estado,
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
    's' => $search,
];

if ($filtro_productor > 0) {
    $query_args['meta_query'] = [
        [
            'key' => '_gc_productor_id',
            'value' => $filtro_productor,
            'compare' => '=',
            'type' => 'NUMERIC',
        ],
    ];
}

$productos_query = new WP_Query($query_args);

$counts = wp_count_posts('gc_producto');
$total_publicados = isset($counts->publish) ? (int) $counts->publish : 0;
$total_borrador = isset($counts->draft) ? (int) $counts->draft : 0;
$total_pendiente = isset($counts->pending) ? (int) $counts->pending : 0;

$avg_precio = (float) $wpdb->get_var(
    "SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
     FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_key = '_gc_precio'
       AND p.post_type = 'gc_producto'
       AND p.post_status = 'publish'
       AND pm.meta_value != ''"
);

$total_sin_stock = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm_stock ON pm_stock.post_id = p.ID AND pm_stock.meta_key = '_gc_stock'
     WHERE p.post_type = 'gc_producto'
       AND p.post_status = 'publish'
       AND pm_stock.meta_value != ''
       AND CAST(pm_stock.meta_value AS DECIMAL(10,2)) <= 0"
);

$productores = get_posts([
    'post_type' => 'gc_productor',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);

$base_url = admin_url('admin.php?page=' . $current_page);

?>

<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-carrot"></span>
                <?php esc_html_e('Productos del Grupo de Consumo', 'flavor-platform'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Catálogo interno de productos, precios y disponibilidad por productor.', 'flavor-platform'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=gc_producto')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo producto', 'flavor-platform'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_producto')); ?>" class="dm-btn dm-btn--secondary">
                <?php esc_html_e('Editor nativo', 'flavor-platform'); ?>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid" style="margin-bottom:16px;">
        <div class="dm-stat-card">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Publicados', 'flavor-platform'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_pendiente); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_borrador); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Borradores', 'flavor-platform'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_sin_stock); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Sin stock', 'flavor-platform'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($avg_precio, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Precio medio', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <form method="get" class="dm-card" style="padding:16px; margin-bottom:16px; display:grid; gap:12px; grid-template-columns: 1.4fr 1fr 1fr auto; align-items:end;">
        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <div>
            <label for="gc-productos-search"><strong><?php esc_html_e('Buscar', 'flavor-platform'); ?></strong></label>
            <input id="gc-productos-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e('Título o descripción', 'flavor-platform'); ?>" />
        </div>
        <div>
            <label for="gc-productos-productor"><strong><?php esc_html_e('Productor', 'flavor-platform'); ?></strong></label>
            <select id="gc-productos-productor" name="productor" style="width:100%;">
                <option value="0"><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                <?php foreach ($productores as $productor) : ?>
                    <option value="<?php echo esc_attr($productor->ID); ?>" <?php selected($filtro_productor, (int) $productor->ID); ?>>
                        <?php echo esc_html($productor->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="gc-productos-estado"><strong><?php esc_html_e('Estado', 'flavor-platform'); ?></strong></label>
            <select id="gc-productos-estado" name="estado" style="width:100%;">
                <option value="publish" <?php selected($filtro_estado, 'publish'); ?>><?php esc_html_e('Publicados', 'flavor-platform'); ?></option>
                <option value="pending" <?php selected($filtro_estado, 'pending'); ?>><?php esc_html_e('Pendientes', 'flavor-platform'); ?></option>
                <option value="draft" <?php selected($filtro_estado, 'draft'); ?>><?php esc_html_e('Borradores', 'flavor-platform'); ?></option>
                <option value="private" <?php selected($filtro_estado, 'private'); ?>><?php esc_html_e('Privados', 'flavor-platform'); ?></option>
            </select>
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>
        </div>
    </form>

    <div class="dm-card" style="padding:0; overflow:auto;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Producto', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Productor', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Precio', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Unidad', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Stock', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Actualizado', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($productos_query->have_posts()) : ?>
                    <?php while ($productos_query->have_posts()) : $productos_query->the_post(); ?>
                        <?php
                        $producto_id = get_the_ID();
                        $productor_id = (int) get_post_meta($producto_id, '_gc_productor_id', true);
                        $precio = get_post_meta($producto_id, '_gc_precio', true);
                        $unidad = get_post_meta($producto_id, '_gc_unidad', true) ?: 'ud';
                        $stock = get_post_meta($producto_id, '_gc_stock', true);
                        $productor_nombre = $productor_id ? get_the_title($productor_id) : __('Sin asignar', 'flavor-platform');
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($producto_id)); ?>"><?php echo esc_html(get_the_title()); ?></a></strong>
                            </td>
                            <td><?php echo esc_html($productor_nombre); ?></td>
                            <td><?php echo $precio !== '' ? esc_html(number_format_i18n((float) $precio, 2)) . ' €' : '—'; ?></td>
                            <td><?php echo esc_html($unidad); ?></td>
                            <td>
                                <?php if ($stock === '' || $stock === null) : ?>
                                    <span class="dm-badge dm-badge--info"><?php esc_html_e('Ilimitado', 'flavor-platform'); ?></span>
                                <?php else : ?>
                                    <?php $stock_num = (float) $stock; ?>
                                    <span class="dm-badge <?php echo $stock_num <= 0 ? 'dm-badge--error' : ($stock_num <= 5 ? 'dm-badge--warning' : 'dm-badge--success'); ?>">
                                        <?php echo esc_html(number_format_i18n($stock_num, 1)); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(get_the_modified_date(get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($producto_id)); ?>"><?php esc_html_e('Editar', 'flavor-platform'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(get_permalink($producto_id)); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver', 'flavor-platform'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No hay productos para los filtros seleccionados.', 'flavor-platform'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    echo '<div class="tablenav" style="margin-top:12px;">';
    echo '<div class="tablenav-pages">';
    echo wp_kses_post(paginate_links([
        'base' => add_query_arg('paged', '%#%', remove_query_arg('paged', $base_url)),
        'format' => '',
        'current' => $paged,
        'total' => max(1, (int) $productos_query->max_num_pages),
        'add_args' => [
            'page' => $current_page,
            's' => $search,
            'productor' => $filtro_productor,
            'estado' => $filtro_estado,
        ],
        'type' => 'plain',
    ]));
    echo '</div></div>';
    ?>
</div>
