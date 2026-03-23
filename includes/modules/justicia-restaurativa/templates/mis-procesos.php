<?php
/**
 * Template: Mis Procesos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$tipos = Flavor_Chat_Justicia_Restaurativa_Module::TIPOS_PROCESO;
$estados = Flavor_Chat_Justicia_Restaurativa_Module::ESTADOS_PROCESO;

// Procesos donde soy solicitante
global $wpdb;
$mis_procesos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.* FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'jr_proceso'
       AND p.post_status = 'private'
       AND (
           (pm.meta_key = '_jr_solicitante_id' AND pm.meta_value = %d)
           OR (pm.meta_key = '_jr_otra_parte_id' AND pm.meta_value = %d)
       )
     ORDER BY p.post_date DESC",
    $user_id, $user_id
));

// Procesos pendientes de mi respuesta
$procesos_pendientes = $wpdb->get_results($wpdb->prepare(
    "SELECT p.* FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
     WHERE p.post_type = 'jr_proceso'
       AND pm.meta_key = '_jr_otra_parte_id'
       AND pm.meta_value = %d
       AND pm2.meta_key = '_jr_estado'
       AND pm2.meta_value = 'solicitado'",
    $user_id
));
?>

<div class="jr-mis-procesos">
    <h2><?php esc_html_e('Mis Procesos Restaurativos', 'flavor-chat-ia'); ?></h2>

    <?php if ($procesos_pendientes) : ?>
    <section class="jr-seccion jr-seccion--pendientes">
        <h3><?php esc_html_e('Invitaciones pendientes', 'flavor-chat-ia'); ?></h3>

        <?php foreach ($procesos_pendientes as $proceso) :
            $tipo = get_post_meta($proceso->ID, '_jr_tipo', true);
            $tipo_data = $tipos[$tipo] ?? $tipos['mediacion'];
            $solicitante_id = get_post_meta($proceso->ID, '_jr_solicitante_id', true);
            $solicitante = get_userdata($solicitante_id);
        ?>
        <article class="jr-proceso-card" style="border-left-color: var(--jr-warning);">
            <header class="jr-proceso-card__header">
                <div>
                    <span class="jr-proceso-card__tipo"><?php echo esc_html($tipo_data['nombre']); ?></span>
                    <h4 class="jr-proceso-card__titulo">
                        <?php printf(
                            esc_html__('Invitación de %s', 'flavor-chat-ia'),
                            esc_html($solicitante->display_name)
                        ); ?>
                    </h4>
                </div>
                <span class="jr-estado-badge jr-estado-badge--solicitado">
                    <?php esc_html_e('Pendiente de respuesta', 'flavor-chat-ia'); ?>
                </span>
            </header>

            <p style="margin: 1rem 0; color: var(--jr-text-light);">
                <?php esc_html_e('Te han invitado a participar en un proceso de mediación. Tu participación es voluntaria.', 'flavor-chat-ia'); ?>
            </p>

            <div class="jr-proceso-card__actions">
                <button class="jr-btn jr-btn--primary jr-btn-aceptar" data-proceso="<?php echo esc_attr($proceso->ID); ?>">
                    <?php esc_html_e('Aceptar participar', 'flavor-chat-ia'); ?>
                </button>
                <button class="jr-btn jr-btn--secondary jr-btn-rechazar" data-proceso="<?php echo esc_attr($proceso->ID); ?>">
                    <?php esc_html_e('No participar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="jr-seccion">
        <h3><?php esc_html_e('Historial de procesos', 'flavor-chat-ia'); ?></h3>

        <?php if ($mis_procesos) : ?>
        <div class="jr-procesos-lista">
            <?php foreach ($mis_procesos as $proceso) :
                $tipo = get_post_meta($proceso->ID, '_jr_tipo', true);
                $tipo_data = $tipos[$tipo] ?? $tipos['mediacion'];
                $estado = get_post_meta($proceso->ID, '_jr_estado', true) ?: 'solicitado';
                $estado_data = $estados[$estado];
                $mediador_id = get_post_meta($proceso->ID, '_jr_mediador_id', true);
                $mediador = $mediador_id ? get_userdata($mediador_id) : null;
                $fecha_inicio = get_post_meta($proceso->ID, '_jr_fecha_inicio', true);

                $soy_solicitante = get_post_meta($proceso->ID, '_jr_solicitante_id', true) == $user_id;
            ?>
            <article class="jr-proceso-card" style="--estado-color: <?php echo esc_attr($estado_data['color']); ?>">
                <header class="jr-proceso-card__header">
                    <div>
                        <span class="jr-proceso-card__tipo"><?php echo esc_html($tipo_data['nombre']); ?></span>
                        <h4 class="jr-proceso-card__titulo">
                            <?php echo esc_html($proceso->post_title); ?>
                        </h4>
                    </div>
                    <span class="jr-estado-badge jr-estado-badge--<?php echo esc_attr($estado); ?>">
                        <?php echo esc_html($estado_data['nombre']); ?>
                    </span>
                </header>

                <div class="jr-proceso-card__meta">
                    <span>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo $fecha_inicio
                            ? date_i18n('d M Y', strtotime($fecha_inicio))
                            : date_i18n('d M Y', strtotime($proceso->post_date)); ?>
                    </span>
                    <?php if ($mediador) : ?>
                    <span>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php printf(esc_html__('Mediador: %s', 'flavor-chat-ia'), esc_html($mediador->display_name)); ?>
                    </span>
                    <?php endif; ?>
                    <span>
                        <span class="dashicons dashicons-<?php echo $soy_solicitante ? 'arrow-right-alt' : 'arrow-left-alt'; ?>"></span>
                        <?php echo $soy_solicitante
                            ? esc_html__('Iniciado por ti', 'flavor-chat-ia')
                            : esc_html__('Te invitaron', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="jr-empty-state">
            <span class="dashicons dashicons-shield"></span>
            <p><?php esc_html_e('No tienes procesos restaurativos activos.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </section>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('justicia_restaurativa', 'solicitar')); ?>" class="jr-btn jr-btn--primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Iniciar nuevo proceso', 'flavor-chat-ia'); ?>
        </a>
    </div>
</div>

<style>
.jr-seccion {
    margin-bottom: 2rem;
}

.jr-seccion h3 {
    font-size: 1.25rem;
    margin: 0 0 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--jr-border);
}

.jr-seccion--pendientes {
    background: color-mix(in srgb, var(--jr-warning) 10%, #fff);
    border: 1px solid var(--jr-warning);
    border-radius: var(--jr-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.jr-seccion--pendientes h3 {
    border-bottom-color: var(--jr-warning);
}
</style>
