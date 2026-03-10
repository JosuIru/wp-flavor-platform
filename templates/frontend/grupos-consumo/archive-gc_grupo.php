<?php
/**
 * Template: Archive Grupos de Consumo
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="gc-archive-grupos">
    <header class="gc-archive-header">
        <h1><?php _e('Grupos de Consumo', 'flavor-chat-ia'); ?></h1>
        <p class="gc-archive-intro">
            <?php _e('Descubre los grupos de consumo de tu zona y unete a uno para acceder a productos locales, ecologicos y de temporada.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <?php if (have_posts()): ?>
    <div class="gc-grupos-grid">
        <?php while (have_posts()): the_post();
            $grupo_id = get_the_ID();
            $miembros_count = get_post_meta($grupo_id, '_gc_miembros_count', true) ?: 0;
            $ubicacion = get_post_meta($grupo_id, '_gc_ubicacion', true);
            $estado = get_post_meta($grupo_id, '_gc_estado', true) ?: 'activo';
            $admite_nuevos = get_post_meta($grupo_id, '_gc_admite_nuevos', true) !== 'no';
        ?>
        <article class="gc-grupo-card">
            <?php if (has_post_thumbnail()): ?>
            <div class="gc-grupo-card-imagen">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium'); ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="gc-grupo-card-body">
                <h2 class="gc-grupo-card-titulo">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <div class="gc-grupo-card-meta">
                    <?php if (!empty($ubicacion)): ?>
                    <span class="gc-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($ubicacion); ?>
                    </span>
                    <?php endif; ?>
                    <span class="gc-miembros">
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo esc_html($miembros_count); ?>
                    </span>
                </div>

                <?php if (has_excerpt()): ?>
                <p class="gc-grupo-card-extracto"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>

                <div class="gc-grupo-card-footer">
                    <?php if ($admite_nuevos && $estado === 'activo'): ?>
                    <span class="gc-badge gc-badge-abierto"><?php _e('Admite nuevos', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                    <a href="<?php the_permalink(); ?>" class="gc-btn gc-btn-secondary">
                        <?php _e('Ver grupo', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endwhile; ?>
    </div>

    <nav class="gc-paginacion">
        <?php
        the_posts_pagination([
            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Anterior', 'flavor-chat-ia'),
            'next_text' => __('Siguiente', 'flavor-chat-ia') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
        ]);
        ?>
    </nav>

    <?php else: ?>
    <div class="gc-empty-state">
        <span class="dashicons dashicons-groups"></span>
        <p><?php _e('No hay grupos de consumo disponibles actualmente.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php
get_footer();
