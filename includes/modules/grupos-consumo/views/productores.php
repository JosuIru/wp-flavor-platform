<?php
/**
 * Vista Productores - Grupos de Consumo
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
$current_page = sanitize_key($_GET['page'] ?? 'gc-productores');

$query_args = [
    'post_type' => 'gc_productor',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    's' => $search,
];

$productores_query = new WP_Query($query_args);

$counts = wp_count_posts('gc_productor');
$total_productores = isset($counts->publish) ? (int) $counts->publish : 0;

$total_productos_asignados = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_gc_productor_id'
     WHERE p.post_type = 'gc_producto' AND p.post_status = 'publish' AND pm.meta_value != ''"
);

$promedio_productos = $total_productores > 0 ? $total_productos_asignados / $total_productores : 0;

$productores_con_email = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_gc_contacto_email'
     WHERE p.post_type = 'gc_productor' AND p.post_status = 'publish' AND pm.meta_value != ''"
);

$base_url = admin_url('admin.php?page=' . $current_page);
?>

<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-store"></span>
                <?php esc_html_e('Productores', 'flavor-chat-ia'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Directorio operativo de productores y datos de contacto para logística del ciclo.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=gc_productor')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo productor', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_productor')); ?>" class="dm-btn dm-btn--secondary">
                <?php esc_html_e('Editor nativo', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid" style="margin-bottom:16px;">
        <div class="dm-stat-card">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_productores); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Productores activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_productos_asignados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Productos asignados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($promedio_productos, 1); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Productos por productor', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($productores_con_email); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Con email de contacto', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <form method="get" class="dm-card" style="padding:16px; margin-bottom:16px; display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <div style="flex:1;">
            <label for="gc-productores-search"><strong><?php esc_html_e('Buscar productor', 'flavor-chat-ia'); ?></strong></label>
            <input id="gc-productores-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e('Nombre o descripción', 'flavor-chat-ia'); ?>" />
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
        </div>
    </form>

    <div class="dm-card" style="padding:0; overflow:auto;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Productor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Contacto', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Productos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Actualizado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($productores_query->have_posts()) : ?>
                    <?php while ($productores_query->have_posts()) : $productores_query->the_post(); ?>
                        <?php
                        $productor_id = get_the_ID();
                        $contacto_nombre = get_post_meta($productor_id, '_gc_contacto_nombre', true);
                        $contacto_email = get_post_meta($productor_id, '_gc_contacto_email', true);
                        $contacto_telefono = get_post_meta($productor_id, '_gc_contacto_telefono', true);
                        $ubicacion = get_post_meta($productor_id, '_gc_ubicacion', true);
                        $direccion = get_post_meta($productor_id, '_gc_direccion_completa', true);

                        $productos_asociados = (int) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*)
                             FROM {$wpdb->posts} p
                             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_gc_productor_id'
                             WHERE p.post_type = 'gc_producto' AND p.post_status = 'publish' AND pm.meta_value = %d",
                            $productor_id
                        ));
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($productor_id)); ?>"><?php echo esc_html(get_the_title()); ?></a></strong>
                            </td>
                            <td>
                                <?php if ($contacto_nombre) : ?><div><?php echo esc_html($contacto_nombre); ?></div><?php endif; ?>
                                <?php if ($contacto_email) : ?><div><a href="mailto:<?php echo esc_attr($contacto_email); ?>"><?php echo esc_html($contacto_email); ?></a></div><?php endif; ?>
                                <?php if ($contacto_telefono) : ?><div><?php echo esc_html($contacto_telefono); ?></div><?php endif; ?>
                                <?php if (!$contacto_nombre && !$contacto_email && !$contacto_telefono) : ?>—<?php endif; ?>
                            </td>
                            <td><?php echo esc_html($direccion ?: $ubicacion ?: '—'); ?></td>
                            <td>
                                <span class="dm-badge <?php echo $productos_asociados > 0 ? 'dm-badge--success' : 'dm-badge--warning'; ?>">
                                    <?php echo number_format_i18n($productos_asociados); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(get_the_modified_date(get_option('date_format'))); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($productor_id)); ?>"><?php esc_html_e('Editar', 'flavor-chat-ia'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('edit.php?post_type=gc_producto&meta_key=_gc_productor_id&meta_value=' . $productor_id)); ?>"><?php esc_html_e('Productos', 'flavor-chat-ia'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php esc_html_e('No hay productores para los filtros seleccionados.', 'flavor-chat-ia'); ?></td>
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
        'total' => max(1, (int) $productores_query->max_num_pages),
        'add_args' => [
            'page' => $current_page,
            's' => $search,
        ],
        'type' => 'plain',
    ]));
    echo '</div></div>';
    ?>
</div>
