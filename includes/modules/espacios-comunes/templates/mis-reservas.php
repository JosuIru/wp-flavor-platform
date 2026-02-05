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

// Reservas activas (pendientes, confirmadas, activas)
$reservas_activas = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion, e.imagen_url, e.precio_hora
     FROM $tabla_reservas r
     INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
     WHERE r.usuario_id = %d AND r.estado IN ('pendiente', 'confirmada', 'activa')
     ORDER BY r.fecha ASC, r.hora_inicio ASC",
    $usuario_id
));

// Historial de reservas
$historial = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion, e.imagen_url
     FROM $tabla_reservas r
     INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
     WHERE r.usuario_id = %d AND r.estado IN ('completada', 'cancelada', 'rechazada')
     ORDER BY r.fecha DESC
     LIMIT 20",
    $usuario_id
));

$estados_labels = [
    'pendiente' => __('Pendiente de aprobación', 'flavor-chat-ia'),
    'confirmada' => __('Confirmada', 'flavor-chat-ia'),
    'activa' => __('En uso', 'flavor-chat-ia'),
    'completada' => __('Completada', 'flavor-chat-ia'),
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
                $fecha_reserva = new DateTime($reserva->fecha);
                $hoy = new DateTime('today');
                $es_hoy = $fecha_reserva->format('Y-m-d') === $hoy->format('Y-m-d');
                ?>
                <div class="reserva-card">
                    <div class="reserva-card-header">
                        <div class="reserva-card-imagen">
                            <?php if ($reserva->imagen_url): ?>
                                <img src="<?php echo esc_url($reserva->imagen_url); ?>" alt="">
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
                                    echo date_i18n(get_option('date_format'), strtotime($reserva->fecha));
                                }
                                ?>
                            </span>
                        </div>
                        <div class="reserva-card-detalle">
                            <label><?php _e('Horario', 'flavor-chat-ia'); ?></label>
                            <span><?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></span>
                        </div>
                        <?php if ($reserva->num_personas): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Personas', 'flavor-chat-ia'); ?></label>
                                <span><?php echo $reserva->num_personas; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($reserva->precio_total > 0): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Precio', 'flavor-chat-ia'); ?></label>
                                <span style="font-weight: 600;"><?php echo number_format($reserva->precio_total, 2); ?>€</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($reserva->codigo_acceso): ?>
                            <div class="reserva-card-detalle">
                                <label><?php _e('Código acceso', 'flavor-chat-ia'); ?></label>
                                <span style="font-family: monospace; font-weight: 600; color: #6366f1;"><?php echo esc_html($reserva->codigo_acceso); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($reserva->motivo): ?>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem;">
                            <label style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; display: block; margin-bottom: 0.25rem;">
                                <?php _e('Motivo', 'flavor-chat-ia'); ?>
                            </label>
                            <span style="font-size: 0.875rem;"><?php echo esc_html($reserva->motivo); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($reserva->notas_admin): ?>
                        <div style="padding: 0.75rem; background: #eff6ff; border-radius: 8px; margin-bottom: 1rem;">
                            <label style="font-size: 0.75rem; color: #3b82f6; text-transform: uppercase; display: block; margin-bottom: 0.25rem;">
                                <?php _e('Nota del administrador', 'flavor-chat-ia'); ?>
                            </label>
                            <span style="font-size: 0.875rem;"><?php echo esc_html($reserva->notas_admin); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="reserva-card-acciones">
                        <?php if ($reserva->estado === 'pendiente' || $reserva->estado === 'confirmada'): ?>
                            <?php
                            // Calcular si se puede cancelar (ej: 24h antes)
                            $fecha_hora_reserva = new DateTime($reserva->fecha . ' ' . $reserva->hora_inicio);
                            $ahora = new DateTime();
                            $diff = $ahora->diff($fecha_hora_reserva);
                            $horas_hasta_reserva = ($diff->days * 24) + $diff->h;
                            $puede_cancelar = $diff->invert === 0 && $horas_hasta_reserva >= 24;
                            ?>
                            <?php if ($puede_cancelar): ?>
                                <button class="btn btn-danger btn-sm btn-cancelar-reserva" data-reserva-id="<?php echo $reserva->id; ?>">
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
                <div class="reserva-card" style="opacity: 0.85;">
                    <div class="reserva-card-header">
                        <div class="reserva-card-imagen">
                            <?php if ($reserva->imagen_url): ?>
                                <img src="<?php echo esc_url($reserva->imagen_url); ?>" alt="">
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
                            <span><?php echo date_i18n(get_option('date_format'), strtotime($reserva->fecha)); ?></span>
                        </div>
                        <div class="reserva-card-detalle">
                            <label><?php _e('Horario', 'flavor-chat-ia'); ?></label>
                            <span><?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></span>
                        </div>
                        <?php if ($reserva->valoracion): ?>
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

                    <?php if ($reserva->estado === 'completada' && !$reserva->valoracion): ?>
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
