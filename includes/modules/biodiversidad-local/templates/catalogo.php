<?php
/**
 * Template: Catálogo de Especies
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Encolar estilos del módulo
wp_enqueue_style(
    'flavor-biodiversidad-local',
    FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-local.css',
    [],
    FLAVOR_CHAT_IA_VERSION
);

$categorias = Flavor_Chat_Biodiversidad_Local_Module::CATEGORIAS_ESPECIES;
$estados = Flavor_Chat_Biodiversidad_Local_Module::ESTADOS_CONSERVACION;

$especies = get_posts([
    'post_type' => 'bl_especie',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Contar por categoría
$conteo_categorias = [];
foreach ($categorias as $cat_id => $cat_data) {
    $conteo_categorias[$cat_id] = 0;
}
foreach ($especies as $especie) {
    $terms = wp_get_post_terms($especie->ID, 'bl_categoria');
    if (!empty($terms)) {
        $cat_slug = $terms[0]->slug;
        if (isset($conteo_categorias[$cat_slug])) {
            $conteo_categorias[$cat_slug]++;
        }
    }
}
?>

<div class="bl-container">
    <header class="bl-header">
        <h2><?php esc_html_e('Catálogo de Biodiversidad Local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Conoce las especies que habitan nuestro territorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Estadísticas -->
    <div class="bl-stats-bar">
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor"><?php echo esc_html(count($especies)); ?></div>
            <div class="bl-stat-item__label"><?php esc_html_e('Especies', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor" style="color: <?php echo esc_attr($cat_data['color']); ?>">
                <?php echo esc_html($conteo_categorias[$cat_id]); ?>
            </div>
            <div class="bl-stat-item__label"><?php echo esc_html($cat_data['nombre']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="bl-categorias-grid">
        <button class="bl-categoria-btn activo" data-categoria="todos">
            <span class="dashicons dashicons-screenoptions"></span>
            <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
        <button class="bl-categoria-btn" data-categoria="<?php echo esc_attr($cat_id); ?>">
            <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>" style="color: <?php echo esc_attr($cat_data['color']); ?>"></span>
            <?php echo esc_html($cat_data['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <?php if ($especies) : ?>
    <div class="bl-especies-grid">
        <?php foreach ($especies as $especie) :
            $terms = wp_get_post_terms($especie->ID, 'bl_categoria');
            $cat_slug = !empty($terms) ? $terms[0]->slug : '';
            $cat_data = $categorias[$cat_slug] ?? ['nombre' => 'Otro', 'color' => '#6b7280'];

            $nombre_cientifico = get_post_meta($especie->ID, '_bl_nombre_cientifico', true);
            $estado = get_post_meta($especie->ID, '_bl_estado_conservacion', true) ?: 'no_evaluada';
            $estado_data = $estados[$estado] ?? $estados['no_evaluada'];

            $habitats = wp_get_post_terms($especie->ID, 'bl_habitat');
            $avistamientos = new WP_Query([
                'post_type' => 'bl_avistamiento',
                'post_status' => 'publish',
                'meta_query' => [
                    ['key' => '_bl_especie_id', 'value' => $especie->ID]
                ],
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);
        ?>
        <article class="bl-especie-card" data-categoria="<?php echo esc_attr($cat_slug); ?>">
            <div class="bl-especie-card__imagen">
                <?php if (has_post_thumbnail($especie->ID)) : ?>
                    <?php echo get_the_post_thumbnail($especie->ID, 'medium'); ?>
                <?php else : ?>
                    <span class="dashicons <?php echo esc_attr($cat_data['icono'] ?? 'dashicons-admin-site-alt3'); ?>"></span>
                <?php endif; ?>
                <span class="bl-especie-card__estado estado-<?php echo esc_attr($estado_data['icono']); ?>"
                      style="background: <?php echo esc_attr($estado_data['color']); ?>">
                    <?php echo esc_html($estado_data['icono']); ?>
                </span>
            </div>

            <div class="bl-especie-card__body">
                <span class="bl-especie-card__categoria" style="color: <?php echo esc_attr($cat_data['color']); ?>">
                    <?php echo esc_html($cat_data['nombre']); ?>
                </span>
                <h3 class="bl-especie-card__nombre"><?php echo esc_html($especie->post_title); ?></h3>
                <?php if ($nombre_cientifico) : ?>
                <p class="bl-especie-card__cientifico"><?php echo esc_html($nombre_cientifico); ?></p>
                <?php endif; ?>

                <div class="bl-especie-card__meta">
                    <span title="<?php esc_attr_e('Avistamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo esc_html($avistamientos->found_posts); ?>
                    </span>
                    <?php if (!empty($habitats)) : ?>
                    <span title="<?php esc_attr_e('Hábitats', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-admin-site"></span>
                        <?php echo esc_html(count($habitats)); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="bl-empty-state">
        <span class="dashicons dashicons-admin-site-alt3"></span>
        <p><?php esc_html_e('Aún no hay especies catalogadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/biodiversidad/registrar/')); ?>" class="bl-btn bl-btn--primary">
            <?php esc_html_e('Proponer una especie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Enlace a registro -->
    <?php if (is_user_logged_in() && $especies) : ?>
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(home_url('/biodiversidad/registrar/')); ?>" class="bl-btn bl-btn--primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Registrar avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
    <?php endif; ?>
</div>
