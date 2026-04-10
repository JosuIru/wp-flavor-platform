<?php
/**
 * Template: Single Grupo de Consumo
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $grupo_id = get_the_ID();
    $miembros_count = get_post_meta($grupo_id, '_gc_miembros_count', true) ?: 0;
    $ubicacion = get_post_meta($grupo_id, '_gc_ubicacion', true);
    $punto_recogida = get_post_meta($grupo_id, '_gc_punto_recogida', true);
    $estado = get_post_meta($grupo_id, '_gc_estado', true) ?: 'activo';
    $admite_nuevos = get_post_meta($grupo_id, '_gc_admite_nuevos', true) !== 'no';
    $descripcion_corta = get_post_meta($grupo_id, '_gc_descripcion_corta', true);
?>

<div class="gc-single-grupo">
    <article id="grupo-<?php echo esc_attr($grupo_id); ?>" class="gc-grupo-article">
        <header class="gc-grupo-header">
            <?php if (has_post_thumbnail()): ?>
            <div class="gc-grupo-imagen">
                <?php the_post_thumbnail('large'); ?>
            </div>
            <?php endif; ?>

            <div class="gc-grupo-info">
                <h1 class="gc-grupo-titulo"><?php the_title(); ?></h1>

                <?php if (!empty($descripcion_corta)): ?>
                <p class="gc-grupo-extracto"><?php echo esc_html($descripcion_corta); ?></p>
                <?php endif; ?>

                <div class="gc-grupo-meta">
                    <span class="gc-badge gc-estado-<?php echo esc_attr($estado); ?>">
                        <?php echo esc_html(ucfirst($estado)); ?>
                    </span>
                    <span class="gc-miembros">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf(_n('%d miembro', '%d miembros', $miembros_count, FLAVOR_PLATFORM_TEXT_DOMAIN), $miembros_count); ?>
                    </span>
                    <?php if (!empty($ubicacion)): ?>
                    <span class="gc-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($ubicacion); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="gc-grupo-body">
            <div class="gc-grupo-contenido">
                <h2><?php _e('Sobre este grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="gc-grupo-descripcion">
                    <?php the_content(); ?>
                </div>

                <?php if (!empty($punto_recogida)): ?>
                <h3><?php _e('Punto de recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php echo esc_html($punto_recogida); ?></p>
                <?php endif; ?>
            </div>

            <aside class="gc-grupo-sidebar">
                <?php if ($admite_nuevos && $estado === 'activo'): ?>
                <div class="gc-card gc-card-unirse">
                    <h3><?php _e('Unirse al grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <?php if (is_user_logged_in()): ?>
                    <p><?php _e('Forma parte de este grupo de consumo y accede a productos locales de calidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <form method="post" class="gc-unirse-form">
                        <?php wp_nonce_field('gc_unirse_' . $grupo_id); ?>
                        <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo_id); ?>">
                        <button type="submit" name="gc_solicitar_union" class="gc-btn gc-btn-primary gc-btn-block">
                            <?php _e('Solicitar union', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <p><?php _e('Inicia sesion para unirte a este grupo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="gc-btn gc-btn-primary gc-btn-block">
                        <?php _e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="gc-card">
                    <p><?php _e('Este grupo no admite nuevos miembros en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <?php endif; ?>

                <div class="gc-card">
                    <h3><?php _e('Ciclo actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <?php
                    $ciclo_actual = get_posts([
                        'post_type' => 'gc_ciclo',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            ['key' => '_gc_grupo_id', 'value' => $grupo_id],
                            ['key' => '_gc_estado', 'value' => 'abierto'],
                        ],
                    ]);
                    if ($ciclo_actual):
                        $ciclo = $ciclo_actual[0];
                        $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
                    ?>
                    <p><strong><?php echo esc_html($ciclo->post_title); ?></strong></p>
                    <?php if ($fecha_cierre): ?>
                    <p><?php printf(__('Cierra: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), date_i18n('d/m/Y H:i', strtotime($fecha_cierre))); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo get_permalink($ciclo->ID); ?>" class="gc-btn gc-btn-secondary gc-btn-block">
                        <?php _e('Ver ciclo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <?php else: ?>
                    <p><?php _e('No hay ciclo abierto actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <nav class="gc-grupo-nav">
            <a href="<?php echo esc_url(get_post_type_archive_link('gc_grupo')); ?>" class="gc-btn gc-btn-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver a grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </nav>
    </article>
</div>

<?php
endwhile;
get_footer();
