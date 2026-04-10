<?php
/**
 * Template del tab "Notificaciones"
 *
 * Variables disponibles:
 *   $notificaciones - Array de objetos de notificacion
 *   $sin_leer       - Cantidad de notificaciones sin leer
 *   $usuario        - WP_User objeto del usuario actual
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-dashboard-notificaciones">

    <div class="flavor-dashboard-notificaciones-header">
        <h2 class="flavor-dashboard-section-title">
            <?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($sin_leer > 0) : ?>
                <span class="flavor-dashboard-badge flavor-dashboard-badge--inline"><?php echo intval($sin_leer); ?></span>
            <?php endif; ?>
        </h2>

        <?php if ($sin_leer > 0) : ?>
            <button type="button"
                    class="flavor-dashboard-btn flavor-dashboard-btn--text"
                    id="flavor-btn-marcar-todas-leidas">
                <?php esc_html_e('Marcar todas como leidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="flavor-dashboard-notificaciones-lista" id="flavor-lista-notificaciones">
        <?php if (empty($notificaciones)) : ?>
            <div class="flavor-dashboard-empty-state">
                <div class="flavor-dashboard-empty-state-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <p class="flavor-dashboard-empty-state-texto">
                    <?php esc_html_e('No tienes notificaciones por el momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
        <?php else : ?>
            <?php foreach ($notificaciones as $notificacion) :
                $esta_sin_leer         = empty($notificacion->is_read);
                $clases_notificacion   = 'flavor-dashboard-notificacion';
                if ($esta_sin_leer) {
                    $clases_notificacion .= ' flavor-dashboard-notificacion--sin-leer';
                }
                $titulo_notificacion   = $notificacion->title ?? '';
                $mensaje_notificacion  = $notificacion->message ?? $notificacion->content ?? '';
                $fecha_notificacion    = $notificacion->created_at ?? '';
                $tipo_notificacion     = $notificacion->type ?? 'general';
                $id_notificacion       = $notificacion->id ?? 0;
            ?>
                <div class="<?php echo esc_attr($clases_notificacion); ?>"
                     data-notification-id="<?php echo intval($id_notificacion); ?>">
                    <div class="flavor-dashboard-notificacion-indicador"></div>
                    <div class="flavor-dashboard-notificacion-contenido">
                        <?php if (!empty($titulo_notificacion)) : ?>
                            <h4 class="flavor-dashboard-notificacion-titulo"><?php echo esc_html($titulo_notificacion); ?></h4>
                        <?php endif; ?>
                        <p class="flavor-dashboard-notificacion-mensaje"><?php echo wp_kses_post($mensaje_notificacion); ?></p>
                        <div class="flavor-dashboard-notificacion-meta">
                            <span class="flavor-dashboard-notificacion-tipo"><?php echo esc_html($tipo_notificacion); ?></span>
                            <?php if (!empty($fecha_notificacion)) : ?>
                                <time class="flavor-dashboard-notificacion-fecha"
                                      datetime="<?php echo esc_attr($fecha_notificacion); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($fecha_notificacion), current_time('timestamp'))); ?>
                                    <?php esc_html_e('hace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </time>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($esta_sin_leer) : ?>
                        <button type="button"
                                class="flavor-dashboard-btn-notificacion-leida"
                                data-notification-id="<?php echo intval($id_notificacion); ?>"
                                title="<?php esc_attr_e('Marcar como leida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
