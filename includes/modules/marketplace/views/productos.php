<?php
/**
 * Vista Productos - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = 20;
$search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
$filtro_estado = sanitize_key($_GET['estado'] ?? 'publish');
$filtro_tipo = sanitize_key($_GET['tipo'] ?? '');
$filtro_categoria = sanitize_key($_GET['categoria'] ?? '');
$current_page = sanitize_key($_GET['page'] ?? 'marketplace-productos');

$allowed_status = ['publish', 'pending', 'draft', 'private'];
if (!in_array($filtro_estado, $allowed_status, true)) {
    $filtro_estado = 'publish';
}

$query_args = [
    'post_type' => 'marketplace_item',
    'post_status' => $filtro_estado,
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
    's' => $search,
];

$tax_query = [];
if ($filtro_tipo !== '') {
    $tax_query[] = [
        'taxonomy' => 'marketplace_tipo',
        'field' => 'slug',
        'terms' => $filtro_tipo,
    ];
}
if ($filtro_categoria !== '') {
    $tax_query[] = [
        'taxonomy' => 'marketplace_categoria',
        'field' => 'slug',
        'terms' => $filtro_categoria,
    ];
}
if (!empty($tax_query)) {
    $query_args['tax_query'] = $tax_query;
}

$anuncios_query = new WP_Query($query_args);

$counts = wp_count_posts('marketplace_item');
$total_publicados = isset($counts->publish) ? (int) $counts->publish : 0;
$total_pendientes = isset($counts->pending) ? (int) $counts->pending : 0;
$total_borradores = isset($counts->draft) ? (int) $counts->draft : 0;

global $wpdb;
$precio_promedio = (float) $wpdb->get_var(
    "SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
     FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_key = '_marketplace_precio'
       AND p.post_type = 'marketplace_item'
       AND p.post_status = 'publish'
       AND pm.meta_value != ''"
);

$tipos = get_terms([
    'taxonomy' => 'marketplace_tipo',
    'hide_empty' => false,
]);
$categorias = get_terms([
    'taxonomy' => 'marketplace_categoria',
    'hide_empty' => false,
]);

$base_url = admin_url('admin.php?page=' . $current_page);
?>

<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Anuncios del Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Gestión centralizada de publicaciones, estado y metadatos de catálogo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=marketplace_item')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=marketplace_item')); ?>" class="dm-btn dm-btn--secondary">
                <?php esc_html_e('Editor nativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid" style="margin-bottom:16px;">
        <div class="dm-stat-card">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_borradores); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Borradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($precio_promedio, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Precio medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <form method="get" class="dm-card" style="padding:16px; margin-bottom:16px; display:grid; gap:12px; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; align-items:end;">
        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <div>
            <label for="marketplace-search"><strong><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
            <input id="marketplace-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e('Título o contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" />
        </div>
        <div>
            <label for="marketplace-estado"><strong><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
            <select id="marketplace-estado" name="estado" style="width:100%;">
                <option value="publish" <?php selected($filtro_estado, 'publish'); ?>><?php esc_html_e('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="pending" <?php selected($filtro_estado, 'pending'); ?>><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="draft" <?php selected($filtro_estado, 'draft'); ?>><?php esc_html_e('Borradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="private" <?php selected($filtro_estado, 'private'); ?>><?php esc_html_e('Privados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
        <div>
            <label for="marketplace-tipo"><strong><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
            <select id="marketplace-tipo" name="tipo" style="width:100%;">
                <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php if (!is_wp_error($tipos) && !empty($tipos)) : ?>
                    <?php foreach ($tipos as $tipo) : ?>
                        <option value="<?php echo esc_attr($tipo->slug); ?>" <?php selected($filtro_tipo, $tipo->slug); ?>><?php echo esc_html($tipo->name); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label for="marketplace-categoria"><strong><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label>
            <select id="marketplace-categoria" name="categoria" style="width:100%;">
                <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php if (!is_wp_error($categorias) && !empty($categorias)) : ?>
                    <?php foreach ($categorias as $categoria) : ?>
                        <option value="<?php echo esc_attr($categoria->slug); ?>" <?php selected($filtro_categoria, $categoria->slug); ?>><?php echo esc_html($categoria->name); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </form>

    <div class="dm-card" style="padding:0; overflow:auto;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado item', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($anuncios_query->have_posts()) : ?>
                    <?php while ($anuncios_query->have_posts()) : $anuncios_query->the_post(); ?>
                        <?php
                        $anuncio_id = get_the_ID();
                        $tipos_post = wp_get_post_terms($anuncio_id, 'marketplace_tipo', ['fields' => 'names']);
                        $cats_post = wp_get_post_terms($anuncio_id, 'marketplace_categoria', ['fields' => 'names']);
                        $precio = get_post_meta($anuncio_id, '_marketplace_precio', true);
                        $estado_item = get_post_meta($anuncio_id, '_marketplace_estado', true);
                        $ubicacion = get_post_meta($anuncio_id, '_marketplace_ubicacion', true);
                        $autor = get_the_author_meta('display_name', (int) get_post_field('post_author', $anuncio_id));
                        ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($anuncio_id)); ?>"><?php echo esc_html(get_the_title()); ?></a></strong>
                                <div class="description"><?php echo esc_html(get_the_date(get_option('date_format'))); ?></div>
                            </td>
                            <td><?php echo !empty($tipos_post) && !is_wp_error($tipos_post) ? esc_html(implode(', ', $tipos_post)) : '—'; ?></td>
                            <td><?php echo !empty($cats_post) && !is_wp_error($cats_post) ? esc_html(implode(', ', $cats_post)) : '—'; ?></td>
                            <td><?php echo $precio !== '' ? esc_html(number_format_i18n((float) $precio, 2)) . ' €' : '—'; ?></td>
                            <td><?php echo $estado_item ? esc_html($estado_item) : '—'; ?></td>
                            <td><?php echo $ubicacion ? esc_html($ubicacion) : '—'; ?></td>
                            <td><?php echo esc_html($autor ?: '—'); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($anuncio_id)); ?>"><?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(get_permalink($anuncio_id)); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No hay anuncios para los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
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
        'total' => max(1, (int) $anuncios_query->max_num_pages),
        'add_args' => [
            'page' => $current_page,
            's' => $search,
            'estado' => $filtro_estado,
            'tipo' => $filtro_tipo,
            'categoria' => $filtro_categoria,
        ],
        'type' => 'plain',
    ]));
    echo '</div></div>';
    ?>
</div>
