<?php
/**
 * Template: Mis Reservas de Espacios
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="espacios-empty"><span class="dashicons dashicons-lock"></span><h3>' . __('Inicia sesión para ver tus reservas', 'flavor-chat-ia') . '</h3></div>';
    return;
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

$usuario_id = get_current_user_id();

// Reservas activas (solicitadas, confirmadas, en_curso)
$reservas_activas = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion, e.fotos, e.precio_hora as precio_hora_espacio
     FROM $tabla_reservas r
     INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
     WHERE r.usuario_id = %d AND r.estado IN ('solicitada', 'confirmada', 'en_curso')
     ORDER BY r.fecha_inicio ASC",
    $usuario_id
));

// Historial de reservas
$historial = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion, e.fotos
     FROM $tabla_reservas r
     INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
     WHERE r.usuario_id = %d AND r.estado IN ('finalizada', 'cancelada', 'rechazada')
     ORDER BY r.fecha_inicio DESC
     LIMIT 20",
    $usuario_id
));

// Funcion helper para obtener la primera imagen de fotos JSON
if (!function_exists('espacios_get_primera_imagen')) {
    function espacios_get_primera_imagen($fotos_json) {
        if (empty($fotos_json)) return '';
        $fotos = json_decode($fotos_json, true);
        if (is_array($fotos) && !empty($fotos)) {
            return $fotos[0];
        }
        return '';
    }
}

$estados_labels = [
    'solicitada' => __('Pendiente de aprobación', 'flavor-chat-ia'),
    'confirmada' => __('Confirmada', 'flavor-chat-ia'),
    'en_curso' => __('En uso', 'flavor-chat-ia'),
    'finalizada' => __('Completada', 'flavor-chat-ia'),
    'cancelada' => __('Cancelada', 'flavor-chat-ia'),
    'rechazada' => __('Rechazada', 'flavor-chat-ia'),
];
?>

<div class="espacios-wrapper">
    <div class="espacios-header">
        <h2 class="espacios-titulo"><?php _e('Mis Reservas', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php _e('Ver espacios', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <div class="mis-reservas-tabs">
        <button class="mis-reservas-tab active" data-tab="activas">
            <?php _e('Activas', 'flavor-chat-ia'); ?>
            <?php if ($reservas_activas): ?>
                <span style="background: #6366f1; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 0.75rem; margin-left: 0.5rem;">
                    <?php echo count($reservas_activas); ?>
                </span>
            <?php endif; ?>
        </button>
        <button class="mis-reservas-tab" data-tab="historial">
            <?php _e('Historial', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Panel: Activas -->
    <div id="activas" class="mis-reservas-panel">
        <?php if ($reservas_activas): ?>
            <?php foreach ($reservas_activas as $reserva): ?>
                <?php
                $fecha_inicio_dt = new DateTime($reserva->fecha_inicio);
                $fecha_fin_dt = new DateTime($reserva->fecha_fin);
                $hoy = new DateTime('today');
                $es_hoy = $fecha_inicio_dt->format('Y-m-d') === $hoy->format('Y-m-d');
                ?>
                <div class="reserva-card">
                    <div class="reserva-card-header">
                        <div class="reserva-card-imagen">
                            <?php $imagen_espacio = espacios_get_primera_imagen($reserva->fotos ?? ''); ?>
                            <?php if ($imagen_espacio): ?>
                                <img src="<?php echo esc_url($imagen_espacio); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="reserva-card-info">
                            <h4><?php echo esc_html($reserva->espacio_nombre); ?></h4>
                            <p>
                                <span class="dashicons dashicons-location" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                <?php echo esc_html($reserva->ubicacion); ?>
                            </p>
                            <span class="reserva-card-estado <?php echo esc_attr($reserva->estado); ?>">
                                <?php echo $estados_labels[$reserva->estado] ?? $reserva->estado; ?>
                            </span>
                        </div>
                    </div>

                    <div class="reserva-card-detalles">
                        <div class="reserva-card-detalle">
                            <label><?php _e('Fecha', 'flavor-chat-ia'); ?></label>
                            <span style="<?php echo $es_hoy ? 'color: #6366f1; font-weight: 600;' : ''; ?>">
                                <?php
                                if ($es_hoy) {
                                    _e('Hoy', 'flavor-chat-ia');
                                } else {
                                    echo date_i18n(get_option('date_format'), strtotime($reserva->fecha_inicio));
                                }
                                ?>
                            </span>
                        </div>
                        <div class="reserva-card-detalle">
                            <label><?php _e('Horario', 'flavor-chat-ia'); ?></label>
                            <span><?php echo esc_html($fecha_inicio_dt->format('H:i')); ?> - <?php echo esc_html($fecha_fin_dt->format('H:i')); ?></span>
                        </div>
                        <?php if (!empty($reserva->num_asistentes)): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Personas', 'flavor-chat-ia'); ?></label>
                                <span><?php echo (int) $reserva->num_asistentes; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($reserva->precio_total) && $reserva->precio_total > 0): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Precio', 'flavor-chat-ia'); ?></label>
                                <span style="font-weight: 600;"><?php echo number_format((float) $reserva->precio_total, 2); ?>€</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($reserva->fianza) && $reserva->fianza > 0): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Fianza', 'flavor-chat-ia'); ?></label>
                                <span style="font-family: monospace; font-weight: 600; color: #6366f1;"><?php echo number_format((float) $reserva->fianza, 2); ?>€</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($reserva->motivo)): ?>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem;">
                            <label style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; display: block; margin-bottom: 0.25rem;">
                                <?php _e('Motivo', 'flavor-chat-ia'); ?>
                            </label>
                            <span style="font-size: 0.875rem;"><?php echo esc_html($reserva->motivo); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($reserva->motivo_rechazo)): ?>
                        <div style="padding: 0.75rem; background: #fef2f2; border-radius: 8px; margin-bottom: 1rem;">
                            <label style="font-size: 0.75rem; color: #dc2626; text-transform: uppercase; display: block; margin-bottom: 0.25rem;">
                                <?php _e('Motivo de rechazo', 'flavor-chat-ia'); ?>
                            </label>
                            <span style="font-size: 0.875rem;"><?php echo esc_html($reserva->motivo_rechazo); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="reserva-card-acciones">
                        <?php if ($reserva->estado === 'solicitada' || $reserva->estado === 'confirmada'): ?>
                            <?php
                            // Calcular si se puede cancelar (ej: 24h antes)
                            $ahora = new DateTime();
                            $diff = $ahora->diff($fecha_inicio_dt);
                            $horas_hasta_reserva = ($diff->days * 24) + $diff->h;
                            $puede_cancelar = $diff->invert === 0 && $horas_hasta_reserva >= 24;
                            ?>
                            <?php if ($puede_cancelar): ?>
                                <button class="btn btn-danger btn-sm btn-cancelar-reserva" data-reserva-id="<?php echo (int) $reserva->id; ?>">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: #6b7280;">
                                    <?php _e('No se puede cancelar con menos de 24h de antelación', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="<?php echo add_query_arg('espacio_id', $reserva->espacio_id, remove_query_arg('vista')); ?>" class="btn btn-outline btn-sm">
                            <?php _e('Ver espacio', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="espacios-empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <h3><?php _e('No tienes reservas activas', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Explora los espacios disponibles y haz tu primera reserva.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-primary">
                    <?php _e('Ver espacios', 'flavor-chat-ia'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel: Historial -->
    <div id="historial" class="mis-reservas-panel" style="display: none;">
        <?php if ($historial): ?>
            <?php foreach ($historial as $reserva): ?>
                <?php
                $fecha_inicio_hist = new DateTime($reserva->fecha_inicio);
                $fecha_fin_hist = new DateTime($reserva->fecha_fin);
                ?>
                <div class="reserva-card" style="opacity: 0.85;">
                    <div class="reserva-card-header">
                        <div class="reserva-card-imagen">
                            <?php $imagen_espacio = espacios_get_primera_imagen($reserva->fotos ?? ''); ?>
                            <?php if ($imagen_espacio): ?>
                                <img src="<?php echo esc_url($imagen_espacio); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="reserva-card-info">
                            <h4><?php echo esc_html($reserva->espacio_nombre); ?></h4>
                            <p><?php echo esc_html($reserva->ubicacion); ?></p>
                            <span class="reserva-card-estado <?php echo esc_attr($reserva->estado); ?>">
                                <?php echo $estados_labels[$reserva->estado] ?? $reserva->estado; ?>
                            </span>
                        </div>
                    </div>

                    <div class="reserva-card-detalles">
                        <div class="reserva-card-detalle">
                            <label><?php _e('Fecha', 'flavor-chat-ia'); ?></label>
                            <span><?php echo date_i18n(get_option('date_format'), strtotime($reserva->fecha_inicio)); ?></span>
                        </div>
                        <div class="reserva-card-detalle">
                            <label><?php _e('Horario', 'flavor-chat-ia'); ?></label>
                            <span><?php echo esc_html($fecha_inicio_hist->format('H:i')); ?> - <?php echo esc_html($fecha_fin_hist->format('H:i')); ?></span>
                        </div>
                        <?php if (!empty($reserva->valoracion)): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Tu valoración', 'flavor-chat-ia'); ?></label>
                                <span>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="dashicons dashicons-star-<?php echo $i <= $reserva->valoracion ? 'filled' : 'empty'; ?>" style="color: #fbbf24; font-size: 14px; width: 14px; height: 14px;"></span>
                                    <?php endfor; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($reserva->estado === 'finalizada' && empty($reserva->valoracion)): ?>
                        <div class="reserva-card-acciones">
                            <a href="<?php echo add_query_arg('espacio_id', $reserva->espacio_id, remove_query_arg('vista')); ?>" class="btn btn-primary btn-sm">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php _e('Valorar', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="espacios-empty">
                <span class="dashicons dashicons-backup"></span>
                <h3><?php _e('Sin historial', 'flavor-chat-ia'); ?></h3>
                <p><?php _e('Aquí aparecerán tus reservas pasadas.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
