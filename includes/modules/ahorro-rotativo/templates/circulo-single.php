<?php
/**
 * Plantilla frontend: detalle de un círculo (turnos, miembros, activar/invitar).
 *
 * Variables disponibles: $detalle = ['circulo' => obj, 'miembros' => array].
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$circulo = $detalle['circulo'];
$miembros = $detalle['miembros'];
$bote = (float) $circulo->importe_aportacion * (int) $circulo->num_plazas;
$es_creador = is_user_logged_in() && (int) $circulo->creado_por === get_current_user_id();
$es_borrador = $circulo->estado === 'borrador';
?>
<div class="flavor-ar flavor-ar--detalle">
    <h2 class="flavor-ar__titulo"><?php echo esc_html($circulo->nombre); ?></h2>
    <p class="flavor-ar__resumen">
        <?php esc_html_e('Bote', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>: <strong><?php echo esc_html(number_format_i18n($bote, 2)); ?> <?php echo esc_html($circulo->moneda); ?></strong>
        · <?php esc_html_e('aportación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format_i18n($circulo->importe_aportacion, 2)); ?>
        · <?php echo (int) $circulo->num_plazas; ?> <?php esc_html_e('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        · <span class="flavor-ar__estado flavor-ar__estado--<?php echo esc_attr($circulo->estado); ?>"><?php echo esc_html($circulo->estado); ?></span>
    </p>

    <h3><?php esc_html_e('Orden de turnos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
    <ol class="flavor-ar__turnos">
        <?php foreach ($miembros as $miembro) : ?>
            <li>
                <span class="flavor-ar__pos"><?php echo $miembro->posicion_turno ? (int) $miembro->posicion_turno : '–'; ?></span>
                <span class="flavor-ar__nombre"><?php echo esc_html($miembro->nombre_visible); ?></span>
                <span class="flavor-ar__estado-inv"><?php echo esc_html($miembro->estado_invitacion); ?></span>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if ($es_creador && $es_borrador) : ?>
        <div class="flavor-ar__acciones-creador">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="flavor-ar__form-inline">
                <input type="hidden" name="action" value="flavor_ar_invitar_miembro">
                <input type="hidden" name="circulo_id" value="<?php echo (int) $circulo->id; ?>">
                <?php wp_nonce_field('flavor_ar_invitar_miembro'); ?>
                <input type="text" name="nombre_visible" placeholder="<?php esc_attr_e('Nombre del miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" required>
                <button type="submit" class="button"><?php esc_html_e('Invitar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="flavor_ar_activar_circulo">
                <input type="hidden" name="circulo_id" value="<?php echo (int) $circulo->id; ?>">
                <?php wp_nonce_field('flavor_ar_activar_circulo'); ?>
                <button type="submit" class="button button-primary"><?php esc_html_e('Activar círculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>
