<?php
/**
 * Template: Muro de Gratitud
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$gratitudes = new WP_Query([
    'post_type' => 'ed_gratitud',
    'posts_per_page' => $atts['limite'],
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="ed-muro-gratitud">
    <header class="ed-muro-gratitud__header">
        <h2><?php esc_html_e('Muro de Gratitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Agradecimientos de quienes han recibido dones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <?php if ($gratitudes->have_posts()) : ?>
    <div class="ed-gratitudes-lista">
        <?php while ($gratitudes->have_posts()) : $gratitudes->the_post();
            $don_id = get_post_meta(get_the_ID(), '_ed_don_id', true);
            $don_titulo = $don_id ? get_the_title($don_id) : '';
        ?>
        <article class="ed-gratitud-card">
            <div class="ed-gratitud-card__mensaje">
                <?php the_content(); ?>
            </div>

            <footer class="ed-gratitud-card__footer">
                <span class="ed-gratitud-card__autor">
                    — <?php the_author(); ?>, <?php echo get_the_date(); ?>
                </span>
                <?php if ($don_titulo) : ?>
                <span class="ed-gratitud-card__don">
                    <span class="dashicons dashicons-heart"></span>
                    <?php echo esc_html($don_titulo); ?>
                </span>
                <?php endif; ?>
            </footer>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php else : ?>
    <div class="ed-empty-state">
        <span class="dashicons dashicons-smiley"></span>
        <p><?php esc_html_e('El muro de gratitud está esperando sus primeros agradecimientos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>
