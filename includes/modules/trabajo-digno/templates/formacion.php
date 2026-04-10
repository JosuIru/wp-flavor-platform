<?php
/**
 * Template: Formación
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$trabajo_digno_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Trabajo_Digno_Module')
    : 'Flavor_Chat_Trabajo_Digno_Module';
$sectores = $trabajo_digno_module_class::SECTORES;
$user_id = get_current_user_id();

$formaciones = get_posts([
    'post_type' => 'td_formacion',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Formación y Capacitación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Cursos, talleres y formaciones para tu desarrollo profesional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <?php if ($formaciones) : ?>
    <div class="td-formacion-grid">
        <?php foreach ($formaciones as $formacion) :
            $terms = wp_get_post_terms($formacion->ID, 'td_sector');
            $sector = !empty($terms) ? $terms[0] : null;
            $sector_data = $sector ? ($sectores[$sector->slug] ?? ['nombre' => $sector->name]) : ['nombre' => ''];

            $fecha = get_post_meta($formacion->ID, '_td_fecha', true);
            $duracion = get_post_meta($formacion->ID, '_td_duracion', true);
            $modalidad = get_post_meta($formacion->ID, '_td_modalidad', true);
            $plazas = intval(get_post_meta($formacion->ID, '_td_plazas', true));
            $inscritos = get_post_meta($formacion->ID, '_td_inscritos', true) ?: [];

            $ya_inscrito = in_array($user_id, $inscritos);
            $plazas_disponibles = $plazas === 0 || count($inscritos) < $plazas;
        ?>
        <article class="td-formacion-card">
            <div class="td-formacion-card__imagen">
                <?php if (has_post_thumbnail($formacion->ID)) : ?>
                    <?php echo get_the_post_thumbnail($formacion->ID, 'medium'); ?>
                <?php else : ?>
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                <?php endif; ?>
            </div>

            <div class="td-formacion-card__body">
                <?php if ($sector) : ?>
                <span class="td-formacion-card__sector"><?php echo esc_html($sector_data['nombre']); ?></span>
                <?php endif; ?>

                <h3 class="td-formacion-card__titulo"><?php echo esc_html($formacion->post_title); ?></h3>

                <div class="td-formacion-card__meta">
                    <?php if ($fecha) : ?>
                    <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date_i18n('j M Y', strtotime($fecha))); ?></span>
                    <?php endif; ?>
                    <?php if ($duracion) : ?>
                    <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($duracion); ?></span>
                    <?php endif; ?>
                    <?php if ($modalidad) : ?>
                    <span><span class="dashicons dashicons-desktop"></span> <?php echo esc_html($modalidad); ?></span>
                    <?php endif; ?>
                </div>

                <p style="color: var(--td-text-light); font-size: 0.9rem; margin-top: 0.75rem;">
                    <?php echo esc_html(wp_trim_words($formacion->post_content, 20)); ?>
                </p>
            </div>

            <div class="td-formacion-card__footer">
                <span style="color: var(--td-text-light); font-size: 0.85rem;">
                    <span class="td-inscritos-count"><?php echo esc_html(count($inscritos)); ?></span>
                    <?php if ($plazas > 0) : ?>
                    / <?php echo esc_html($plazas); ?>
                    <?php endif; ?>
                    <?php esc_html_e('inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>

                <?php if (is_user_logged_in()) : ?>
                    <?php if ($ya_inscrito) : ?>
                    <span class="td-btn td-btn--secondary td-btn--small"><?php esc_html_e('Inscrito/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php elseif ($plazas_disponibles) : ?>
                    <button class="td-btn td-btn--primary td-btn--small td-btn-inscribir" data-formacion="<?php echo esc_attr($formacion->ID); ?>">
                        <?php esc_html_e('Inscribirme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php else : ?>
                    <span class="td-btn td-btn--secondary td-btn--small"><?php esc_html_e('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="td-empty-state">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <p><?php esc_html_e('No hay formaciones programadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>
