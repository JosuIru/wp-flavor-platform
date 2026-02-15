<?php
/**
 * Template: Catálogo de Saberes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = Flavor_Chat_Saberes_Ancestrales_Module::CATEGORIAS_SABER;

$saberes = get_posts([
    'post_type' => 'sa_saber',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="sa-container">
    <header class="sa-header">
        <h2><?php esc_html_e('Saberes Ancestrales', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Conocimientos transmitidos de generación en generación', 'flavor-chat-ia'); ?></p>
    </header>

    <div class="sa-cita">
        <p class="sa-cita__texto"><?php esc_html_e('Un pueblo que olvida su pasado está condenado a repetirlo.', 'flavor-chat-ia'); ?></p>
        <span class="sa-cita__autor">— Proverbio tradicional</span>
    </div>

    <!-- Filtros por categoría -->
    <div class="sa-categorias-grid">
        <button class="sa-categoria-btn activo" data-categoria="todos">
            <div class="sa-categoria-btn__icono">📚</div>
            <div class="sa-categoria-btn__nombre"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></div>
        </button>
        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
        <button class="sa-categoria-btn" data-categoria="<?php echo esc_attr($cat_id); ?>">
            <div class="sa-categoria-btn__icono">
                <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>" style="color: <?php echo esc_attr($cat_data['color']); ?>"></span>
            </div>
            <div class="sa-categoria-btn__nombre"><?php echo esc_html($cat_data['nombre']); ?></div>
        </button>
        <?php endforeach; ?>
    </div>

    <?php if ($saberes) : ?>
    <div class="sa-saberes-grid">
        <?php foreach ($saberes as $saber) :
            $terms = wp_get_post_terms($saber->ID, 'sa_categoria');
            $cat_slug = !empty($terms) ? $terms[0]->slug : '';
            $cat_data = $categorias[$cat_slug] ?? ['nombre' => 'Otro', 'color' => '#666'];
            $agradecimientos = intval(get_post_meta($saber->ID, '_sa_agradecimientos', true));
            $origen = get_post_meta($saber->ID, '_sa_origen', true);
        ?>
        <article class="sa-saber-card" data-categoria="<?php echo esc_attr($cat_slug); ?>" style="border-left-color: <?php echo esc_attr($cat_data['color']); ?>">
            <div class="sa-saber-card__imagen">
                <?php if (has_post_thumbnail($saber->ID)) : ?>
                    <?php echo get_the_post_thumbnail($saber->ID, 'medium'); ?>
                <?php else : ?>
                    <span class="dashicons <?php echo esc_attr($cat_data['icono'] ?? 'dashicons-book'); ?>"></span>
                <?php endif; ?>
            </div>

            <div class="sa-saber-card__body">
                <span class="sa-saber-card__categoria" style="color: <?php echo esc_attr($cat_data['color']); ?>">
                    <?php echo esc_html($cat_data['nombre']); ?>
                </span>
                <h3 class="sa-saber-card__titulo"><?php echo esc_html($saber->post_title); ?></h3>
                <p class="sa-saber-card__extracto"><?php echo esc_html(wp_trim_words($saber->post_content, 25)); ?></p>

                <div class="sa-saber-card__meta">
                    <?php if ($origen) : ?>
                    <span><?php echo esc_html($origen); ?></span>
                    <?php endif; ?>
                    <span class="sa-saber-card__agradecimientos" data-saber="<?php echo esc_attr($saber->ID); ?>">
                        🙏 <span class="sa-agradecimientos-count"><?php echo esc_html($agradecimientos); ?></span>
                    </span>
                </div>
            </div>

            <?php if (is_user_logged_in()) : ?>
            <div style="padding: 0 1.25rem 1.25rem;">
                <button class="sa-btn sa-btn--primary sa-btn--small sa-btn-solicitar" data-saber="<?php echo esc_attr($saber->ID); ?>" style="width: 100%;">
                    <?php esc_html_e('Quiero aprender', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="sa-empty-state">
        <span class="dashicons dashicons-book"></span>
        <p><?php esc_html_e('Aún no hay saberes documentados.', 'flavor-chat-ia'); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/mi-portal/saberes-ancestrales/compartir/')); ?>" class="sa-btn sa-btn--primary">
            <?php esc_html_e('Compartir un saber', 'flavor-chat-ia'); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
