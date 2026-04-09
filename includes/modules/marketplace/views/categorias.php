<?php
/**
 * Vista Categorías - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = get_terms([
    'taxonomy' => 'marketplace_categoria',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$tipos = get_terms([
    'taxonomy' => 'marketplace_tipo',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

global $wpdb;
$total_categorias = is_wp_error($categorias) ? 0 : count($categorias);
$total_tipos = is_wp_error($tipos) ? 0 : count($tipos);
$total_publicados = (int) wp_count_posts('marketplace_item')->publish;
$total_sin_categoria = (int) $wpdb->get_var(
    "SELECT COUNT(*)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
     LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'marketplace_categoria'
     WHERE p.post_type = 'marketplace_item'
       AND p.post_status = 'publish'
       AND tt.term_id IS NULL"
);
?>

<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-category"></span>
                <?php esc_html_e('Categorías y Tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Estructura taxonómica del marketplace para navegar, filtrar y organizar anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item')); ?>" class="dm-btn dm-btn--primary">
                <?php esc_html_e('Gestionar categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=marketplace_tipo&post_type=marketplace_item')); ?>" class="dm-btn dm-btn--secondary">
                <?php esc_html_e('Gestionar tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid" style="margin-bottom:16px;">
        <div class="dm-stat-card">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_categorias); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_tipos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Anuncios publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_sin_categoria); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">
        <div class="dm-card" style="padding:0; overflow:auto;">
            <div style="padding:16px; border-bottom:1px solid #e5e7eb;">
                <h2 style="margin:0;"><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Slug', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!is_wp_error($categorias) && !empty($categorias)) : ?>
                    <?php foreach ($categorias as $categoria) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($categoria->name); ?></strong></td>
                            <td><code><?php echo esc_html($categoria->slug); ?></code></td>
                            <td><?php echo number_format_i18n((int) $categoria->count); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=marketplace_categoria&post_type=marketplace_item')); ?>"><?php esc_html_e('Editar taxonomía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=marketplace-productos&categoria=' . rawurlencode($categoria->slug))); ?>"><?php esc_html_e('Filtrar en anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4"><?php esc_html_e('No hay categorías registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="dm-card" style="padding:0; overflow:auto;">
            <div style="padding:16px; border-bottom:1px solid #e5e7eb;">
                <h2 style="margin:0;"><?php esc_html_e('Tipos de anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Slug', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!is_wp_error($tipos) && !empty($tipos)) : ?>
                    <?php foreach ($tipos as $tipo) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($tipo->name); ?></strong></td>
                            <td><code><?php echo esc_html($tipo->slug); ?></code></td>
                            <td><?php echo number_format_i18n((int) $tipo->count); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=marketplace_tipo&post_type=marketplace_item')); ?>"><?php esc_html_e('Editar taxonomía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=marketplace-productos&tipo=' . rawurlencode($tipo->slug))); ?>"><?php esc_html_e('Filtrar en anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4"><?php esc_html_e('No hay tipos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
