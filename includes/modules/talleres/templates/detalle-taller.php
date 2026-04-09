<?php
/**
 * Template: Detalle de Taller
 *
 * Variables disponibles:
 * - $taller: objeto con datos del taller
 * - $sesiones: array de sesiones del taller
 * - $valoraciones: array de valoraciones
 * - $inscripcion: datos de inscripción del usuario actual (o null)
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$esta_inscrito = !empty($inscripcion);
$plazas_disponibles = max(0, ($taller->capacidad ?? 20) - ($taller->inscritos ?? 0));
?>

<div class="talleres-detalle">
    <div class="talleres-detalle-header">
        <?php if (!empty($taller->imagen)): ?>
        <div class="talleres-detalle-imagen">
            <img src="<?php echo esc_url($taller->imagen); ?>" alt="<?php echo esc_attr($taller->titulo); ?>">
        </div>
        <?php endif; ?>

        <div class="talleres-detalle-info">
            <h1 class="talleres-detalle-titulo"><?php echo esc_html($taller->titulo); ?></h1>

            <div class="talleres-detalle-meta">
                <?php if (!empty($taller->categoria)): ?>
                <span class="talleres-badge"><?php echo esc_html(ucfirst($taller->categoria)); ?></span>
                <?php endif; ?>

                <?php if (!empty($taller->nivel)): ?>
                <span class="talleres-badge talleres-badge-nivel"><?php echo esc_html(ucfirst($taller->nivel)); ?></span>
                <?php endif; ?>

                <span class="talleres-badge <?php echo $plazas_disponibles > 0 ? 'talleres-badge-disponible' : 'talleres-badge-completo'; ?>">
                    <?php echo $plazas_disponibles > 0 ? sprintf(__('%d plazas', 'flavor-platform'), $plazas_disponibles) : __('Completo', 'flavor-platform'); ?>
                </span>
            </div>

            <?php if (!empty($taller->organizador_nombre)): ?>
            <p class="talleres-detalle-organizador">
                <strong><?php _e('Organiza:', 'flavor-platform'); ?></strong>
                <?php echo esc_html($taller->organizador_nombre); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="talleres-detalle-body">
        <div class="talleres-detalle-contenido">
            <h2><?php _e('Descripcion', 'flavor-platform'); ?></h2>
            <div class="talleres-descripcion">
                <?php echo wp_kses_post($taller->descripcion ?? ''); ?>
            </div>

            <?php if (!empty($taller->requisitos)): ?>
            <h3><?php _e('Requisitos', 'flavor-platform'); ?></h3>
            <div class="talleres-requisitos">
                <?php echo wp_kses_post($taller->requisitos); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($taller->materiales)): ?>
            <h3><?php _e('Materiales necesarios', 'flavor-platform'); ?></h3>
            <div class="talleres-materiales">
                <?php echo wp_kses_post($taller->materiales); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="talleres-detalle-sidebar">
            <div class="talleres-card">
                <h3><?php _e('Sesiones', 'flavor-platform'); ?></h3>
                <?php if (!empty($sesiones)): ?>
                <ul class="talleres-sesiones-lista">
                    <?php foreach ($sesiones as $sesion): ?>
                    <li>
                        <strong><?php echo esc_html(date_i18n('d/m/Y', strtotime($sesion->fecha))); ?></strong>
                        <span><?php echo esc_html($sesion->hora_inicio . ' - ' . $sesion->hora_fin); ?></span>
                        <?php if (!empty($sesion->ubicacion)): ?>
                        <small><?php echo esc_html($sesion->ubicacion); ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p><?php _e('Fechas por confirmar', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <div class="talleres-card talleres-inscripcion-box">
                <?php if ($esta_inscrito): ?>
                <p class="talleres-inscrito-msg">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Ya estas inscrito', 'flavor-platform'); ?>
                </p>
                <form method="post" class="talleres-cancelar-form">
                    <?php wp_nonce_field('talleres_cancelar_' . $taller->id); ?>
                    <input type="hidden" name="taller_id" value="<?php echo esc_attr($taller->id); ?>">
                    <button type="submit" name="talleres_cancelar" class="talleres-btn talleres-btn-secondary">
                        <?php _e('Cancelar inscripcion', 'flavor-platform'); ?>
                    </button>
                </form>
                <?php elseif ($plazas_disponibles > 0): ?>
                <form method="post" class="talleres-inscribir-form">
                    <?php wp_nonce_field('talleres_inscribir_' . $taller->id); ?>
                    <input type="hidden" name="taller_id" value="<?php echo esc_attr($taller->id); ?>">
                    <button type="submit" name="talleres_inscribir" class="talleres-btn talleres-btn-primary">
                        <?php _e('Inscribirme', 'flavor-platform'); ?>
                    </button>
                </form>
                <?php else: ?>
                <p class="talleres-completo-msg"><?php _e('Este taller esta completo', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($valoraciones)): ?>
    <div class="talleres-valoraciones">
        <h2><?php _e('Valoraciones', 'flavor-platform'); ?></h2>
        <div class="talleres-valoraciones-lista">
            <?php foreach ($valoraciones as $val): ?>
            <div class="talleres-valoracion">
                <div class="talleres-valoracion-header">
                    <span class="talleres-valoracion-autor"><?php echo esc_html($val->autor_nombre ?? __('Anonimo', 'flavor-platform')); ?></span>
                    <span class="talleres-valoracion-fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($val->fecha))); ?></span>
                    <span class="talleres-valoracion-estrellas">
                        <?php echo str_repeat('★', intval($val->puntuacion)); ?>
                        <?php echo str_repeat('☆', 5 - intval($val->puntuacion)); ?>
                    </span>
                </div>
                <?php if (!empty($val->comentario)): ?>
                <p class="talleres-valoracion-texto"><?php echo esc_html($val->comentario); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
