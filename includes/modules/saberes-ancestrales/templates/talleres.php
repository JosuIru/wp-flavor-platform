<?php
/**
 * Template: Talleres de Saberes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

global $wpdb;
$talleres = $wpdb->get_results(
    "SELECT p.*, pm_fecha.meta_value as fecha, pm_lugar.meta_value as lugar,
            pm_plazas.meta_value as plazas, pm_inscritos.meta_value as inscritos
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm_fecha ON p.ID = pm_fecha.post_id AND pm_fecha.meta_key = '_sa_fecha'
     LEFT JOIN {$wpdb->postmeta} pm_lugar ON p.ID = pm_lugar.post_id AND pm_lugar.meta_key = '_sa_lugar'
     LEFT JOIN {$wpdb->postmeta} pm_plazas ON p.ID = pm_plazas.post_id AND pm_plazas.meta_key = '_sa_plazas'
     LEFT JOIN {$wpdb->postmeta} pm_inscritos ON p.ID = pm_inscritos.post_id AND pm_inscritos.meta_key = '_sa_inscritos'
     WHERE p.post_type = 'sa_taller'
       AND p.post_status = 'publish'
       AND (pm_fecha.meta_value IS NULL OR pm_fecha.meta_value >= NOW())
     ORDER BY pm_fecha.meta_value ASC"
);
?>

<div class="sa-container">
    <header class="sa-header">
        <h2><?php esc_html_e('Talleres de Saberes', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Aprende directamente de quienes saben', 'flavor-platform'); ?></p>
    </header>

    <?php if ($talleres) : ?>
    <div class="sa-talleres-grid">
        <?php foreach ($talleres as $taller) :
            $inscritos_lista = maybe_unserialize($taller->inscritos) ?: [];
            $num_inscritos = is_array($inscritos_lista) ? count($inscritos_lista) : 0;
            $plazas_total = intval($taller->plazas) ?: 20;
            $plazas_restantes = $plazas_total - $num_inscritos;
            $ya_inscrito = is_array($inscritos_lista) && in_array($user_id, $inscritos_lista);
        ?>
        <article class="sa-taller-card">
            <div class="sa-taller-card__header">
                <?php if ($taller->fecha) : ?>
                <div class="sa-taller-card__fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n('l, j F Y', strtotime($taller->fecha))); ?>
                </div>
                <?php endif; ?>
                <h3 class="sa-taller-card__titulo"><?php echo esc_html($taller->post_title); ?></h3>
            </div>

            <div class="sa-taller-card__body">
                <p class="sa-taller-card__descripcion">
                    <?php echo esc_html(wp_trim_words($taller->post_content, 30)); ?>
                </p>

                <div class="sa-taller-card__meta">
                    <?php if ($taller->lugar) : ?>
                    <span>
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($taller->lugar); ?>
                    </span>
                    <?php endif; ?>
                    <span>
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo esc_html($num_inscritos); ?>/<?php echo esc_html($plazas_total); ?>
                    </span>
                </div>
            </div>

            <div class="sa-taller-card__footer">
                <span class="sa-plazas <?php echo $plazas_restantes < 5 ? 'sa-plazas--pocas' : ''; ?>">
                    <?php printf(esc_html__('%d plazas', 'flavor-platform'), $plazas_restantes); ?>
                </span>

                <?php if (is_user_logged_in()) : ?>
                    <?php if ($ya_inscrito) : ?>
                    <span class="sa-btn sa-btn--secondary sa-btn--small"><?php esc_html_e('Inscrito/a', 'flavor-platform'); ?></span>
                    <?php elseif ($plazas_restantes > 0) : ?>
                    <button class="sa-btn sa-btn--primary sa-btn--small sa-btn-inscribirse" data-taller="<?php echo esc_attr($taller->ID); ?>">
                        <?php esc_html_e('Inscribirme', 'flavor-platform'); ?>
                    </button>
                    <?php else : ?>
                    <span class="sa-btn sa-btn--secondary sa-btn--small"><?php esc_html_e('Completo', 'flavor-platform'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="sa-empty-state">
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e('No hay talleres programados.', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>
</div>
