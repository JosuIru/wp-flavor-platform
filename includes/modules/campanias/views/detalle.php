<?php
/**
 * Vista de detalle de campania
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$tipos_campania = [
    'protesta' => 'Protesta',
    'recogida_firmas' => 'Recogida de Firmas',
    'concentracion' => 'Concentracion',
    'boicot' => 'Boicot',
    'denuncia_publica' => 'Denuncia Publica',
    'sensibilizacion' => 'Sensibilizacion',
    'accion_legal' => 'Accion Legal',
    'otra' => 'Otra',
];

$porcentaje_firmas = $campania->objetivo_firmas > 0
    ? ($campania->firmas_actuales / $campania->objetivo_firmas) * 100
    : 0;

$user_id = get_current_user_id();
$es_participante = false;
$rol_usuario = '';

if ($user_id && !empty($campania->participantes)) {
    foreach ($campania->participantes as $participante) {
        if ($participante->user_id == $user_id) {
            $es_participante = true;
            $rol_usuario = $participante->rol;
            break;
        }
    }
}
?>

<div class="flavor-campania-detalle">
    <header class="flavor-campania-header">
        <span class="flavor-campania-tipo">
            <?php echo esc_html($tipos_campania[$campania->tipo] ?? $campania->tipo); ?>
        </span>

        <span class="flavor-estado flavor-estado-<?php echo esc_attr($campania->estado); ?>">
            <?php echo esc_html(ucfirst($campania->estado)); ?>
        </span>

        <h1 class="flavor-campania-titulo"><?php echo esc_html($campania->titulo); ?></h1>

        <div class="flavor-campania-meta">
            <?php if ($campania->creador): ?>
                <span>
                    <span class="dashicons dashicons-admin-users"></span>
                    Organiza: <?php echo esc_html($campania->creador->display_name); ?>
                </span>
            <?php endif; ?>

            <?php if ($campania->fecha_inicio): ?>
                <span>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo date_i18n('j F Y', strtotime($campania->fecha_inicio)); ?>
                    <?php if ($campania->fecha_fin && $campania->fecha_fin !== $campania->fecha_inicio): ?>
                        - <?php echo date_i18n('j F Y', strtotime($campania->fecha_fin)); ?>
                    <?php endif; ?>
                </span>
            <?php endif; ?>

            <?php if ($campania->ubicacion): ?>
                <span>
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($campania->ubicacion); ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="flavor-campania-body">
        <?php if ($campania->imagen): ?>
            <img src="<?php echo esc_url($campania->imagen); ?>" alt="" style="width: 100%; border-radius: 8px; margin-bottom: 1.5rem;">
        <?php endif; ?>

        <div class="flavor-campania-descripcion-full">
            <?php echo wp_kses_post(nl2br($campania->descripcion)); ?>
        </div>

        <?php if ($campania->objetivo_descripcion): ?>
            <div style="margin-top: 2rem; padding: 1.5rem; background: #fef2f2; border-radius: 8px;">
                <h3 style="margin: 0 0 0.5rem; color: #991b1b;">Objetivo</h3>
                <p style="margin: 0;"><?php echo esc_html($campania->objetivo_descripcion); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($campania->tipo === 'recogida_firmas'): ?>
            <div style="margin-top: 2rem;">
                <h3>Firmas recogidas</h3>
                <div class="flavor-firmas-progress">
                    <div class="flavor-firmas-bar" style="height: 12px;">
                        <div class="flavor-firmas-fill" data-porcentaje="<?php echo esc_attr($porcentaje_firmas); ?>"></div>
                    </div>
                    <div class="flavor-firmas-count" style="font-size: 1rem;">
                        <span class="flavor-firmas-actual" style="font-size: 1.5rem;">
                            <?php echo number_format($campania->firmas_actuales); ?> firmas
                        </span>
                        <?php if ($campania->objetivo_firmas > 0): ?>
                            <span class="flavor-firmas-objetivo" data-objetivo="<?php echo esc_attr($campania->objetivo_firmas); ?>">
                                Objetivo: <?php echo number_format($campania->objetivo_firmas); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario de firma -->
                <div class="flavor-firma-form" style="margin-top: 2rem;">
                    <h3>Firma esta campania</h3>
                    <form method="post">
                        <input type="hidden" name="campania_id" value="<?php echo esc_attr($campania->id); ?>">

                        <div class="flavor-form-group">
                            <label for="firma_nombre">Nombre completo *</label>
                            <input type="text" id="firma_nombre" name="nombre" required
                                   value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->display_name) : ''; ?>">
                        </div>

                        <div class="flavor-form-group">
                            <label for="firma_email">Email *</label>
                            <input type="email" id="firma_email" name="email" required
                                   value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>">
                        </div>

                        <div class="flavor-form-group">
                            <label for="firma_localidad">Localidad</label>
                            <input type="text" id="firma_localidad" name="localidad">
                        </div>

                        <div class="flavor-form-group">
                            <label for="firma_comentario">Comentario (opcional)</label>
                            <textarea id="firma_comentario" name="comentario" rows="3"></textarea>
                        </div>

                        <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-block">
                            <span class="dashicons dashicons-edit"></span>
                            Firmar campania
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Acciones programadas -->
        <?php if (!empty($campania->acciones)): ?>
            <div class="flavor-campania-acciones-lista">
                <h3>Proximas acciones</h3>
                <?php foreach ($campania->acciones as $accion): ?>
                    <div class="flavor-accion-item">
                        <div class="flavor-accion-fecha">
                            <span class="dia"><?php echo date('j', strtotime($accion->fecha)); ?></span>
                            <span class="mes"><?php echo date_i18n('M', strtotime($accion->fecha)); ?></span>
                        </div>
                        <div class="flavor-accion-info">
                            <h4><?php echo esc_html($accion->titulo); ?></h4>
                            <p>
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo date_i18n('H:i', strtotime($accion->fecha)); ?>
                                <?php if ($accion->ubicacion): ?>
                                    &nbsp;|&nbsp;
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($accion->ubicacion); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($accion->descripcion): ?>
                                <p><?php echo esc_html($accion->descripcion); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Participantes -->
        <?php if (!empty($campania->participantes)): ?>
            <div style="margin-top: 2rem;">
                <h3><?php echo count($campania->participantes); ?> participantes</h3>
                <div class="flavor-participantes-grid">
                    <?php foreach ($campania->participantes as $participante):
                        $iniciales = mb_substr($participante->display_name, 0, 2);
                    ?>
                        <div class="flavor-participante-avatar" title="<?php echo esc_attr($participante->display_name . ' (' . $participante->rol . ')'); ?>">
                            <?php echo esc_html(strtoupper($iniciales)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botones de accion -->
        <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <?php if (is_user_logged_in()): ?>
                <?php if ($es_participante): ?>
                    <span class="flavor-estado flavor-estado-activa">Participas como <?php echo esc_html($rol_usuario); ?></span>
                    <?php if ($rol_usuario !== 'organizador'): ?>
                        <button class="flavor-btn flavor-btn-secondary flavor-btn-abandonar" data-campania-id="<?php echo esc_attr($campania->id); ?>">
                            Abandonar campania
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="flavor-btn flavor-btn-primary flavor-btn-participar" data-campania-id="<?php echo esc_attr($campania->id); ?>">
                        <span class="dashicons dashicons-groups"></span>
                        Unirme a la campania
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <p>
                    <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>">Inicia sesion</a> para participar en esta campania.
                </p>
            <?php endif; ?>

            <?php if ($campania->hashtags): ?>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <?php foreach (explode(',', $campania->hashtags) as $hashtag): ?>
                        <span style="background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 100px; font-size: 0.85rem;">
                            #<?php echo esc_html(trim($hashtag)); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actualizaciones -->
        <?php if (!empty($campania->actualizaciones)): ?>
            <div style="margin-top: 2rem;">
                <h3>Actualizaciones</h3>
                <?php foreach ($campania->actualizaciones as $actualizacion): ?>
                    <div style="padding: 1rem; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong><?php echo esc_html($actualizacion->titulo); ?></strong>
                            <span style="color: #6b7280; font-size: 0.85rem;">
                                <?php echo human_time_diff(strtotime($actualizacion->created_at), current_time('timestamp')); ?>
                            </span>
                        </div>
                        <p style="margin: 0;"><?php echo wp_kses_post($actualizacion->contenido); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
