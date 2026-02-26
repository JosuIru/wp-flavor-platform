<?php
/**
 * Template: Reserva Activa
 *
 * Muestra la reserva activa actual del usuario (si existe).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    return;
}

global $wpdb;

$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_parkings = $wpdb->prefix . 'flavor_parkings';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
    return;
}

$usuario_actual_id = get_current_user_id();

// Obtener reserva activa del usuario
$reserva_activa = $wpdb->get_row($wpdb->prepare("
    SELECT
        r.id,
        r.fecha_inicio,
        r.fecha_fin,
        r.estado,
        r.matricula,
        r.codigo_acceso,
        pl.numero AS plaza_numero,
        pl.tipo AS plaza_tipo,
        pk.nombre AS parking_nombre,
        pk.direccion AS parking_direccion,
        pk.telefono AS parking_telefono
    FROM $tabla_reservas r
    LEFT JOIN $tabla_plazas pl ON r.plaza_id = pl.id
    LEFT JOIN $tabla_parkings pk ON pl.parking_id = pk.id
    WHERE r.user_id = %d
      AND r.estado IN ('activa', 'confirmada')
      AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin
    ORDER BY r.fecha_inicio ASC
    LIMIT 1
", $usuario_actual_id));

// Si no hay reserva activa, mostrar mensaje o nada según contexto
if (!$reserva_activa) {
    // Buscar próxima reserva
    $proxima_reserva = $wpdb->get_row($wpdb->prepare("
        SELECT
            r.fecha_inicio,
            pk.nombre AS parking_nombre
        FROM $tabla_reservas r
        LEFT JOIN $tabla_plazas pl ON r.plaza_id = pl.id
        LEFT JOIN $tabla_parkings pk ON pl.parking_id = pk.id
        WHERE r.user_id = %d
          AND r.estado IN ('pendiente', 'confirmada')
          AND r.fecha_inicio > NOW()
        ORDER BY r.fecha_inicio ASC
        LIMIT 1
    ", $usuario_actual_id));

    if ($proxima_reserva) {
        ?>
        <div class="reserva-activa-widget reserva-activa-widget--proxima">
            <div class="widget-icono">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="widget-contenido">
                <span class="widget-label"><?php esc_html_e('Próxima reserva', 'flavor-chat-ia'); ?></span>
                <span class="widget-titulo"><?php echo esc_html($proxima_reserva->parking_nombre); ?></span>
                <span class="widget-fecha"><?php echo date_i18n('d M, H:i', strtotime($proxima_reserva->fecha_inicio)); ?></span>
            </div>
            <a href="<?php echo esc_url(home_url('/mi-portal/parkings/mis-reservas/')); ?>" class="widget-btn">
                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }
    return;
}

// Calcular tiempo restante
$fecha_fin = strtotime($reserva_activa->fecha_fin);
$ahora = time();
$diferencia = $fecha_fin - $ahora;

$horas_restantes = floor($diferencia / 3600);
$minutos_restantes = floor(($diferencia % 3600) / 60);

// Determinar estado de urgencia del tiempo
$urgencia_tiempo = 'normal';
if ($diferencia < 1800) { // Menos de 30 minutos
    $urgencia_tiempo = 'urgente';
} elseif ($diferencia < 3600) { // Menos de 1 hora
    $urgencia_tiempo = 'advertencia';
}

// Iconos de tipo de plaza
$iconos_tipo = [
    'normal' => '',
    'discapacitado' => '♿',
    'moto' => '🏍️',
    'electrico' => '⚡',
    'grande' => '🚐',
];

$parkings_url = home_url('/mi-portal/parkings/');
?>

<div class="reserva-activa-card reserva-activa-card--<?php echo esc_attr($urgencia_tiempo); ?>">
    <header class="reserva-activa__header">
        <span class="estado-indicador">
            <span class="indicador-punto"></span>
            <?php esc_html_e('Reserva activa', 'flavor-chat-ia'); ?>
        </span>
        <span class="tiempo-restante">
            <?php if ($horas_restantes > 0): ?>
                <?php printf(esc_html__('%dh %dm', 'flavor-chat-ia'), $horas_restantes, $minutos_restantes); ?>
            <?php else: ?>
                <?php printf(esc_html__('%d min', 'flavor-chat-ia'), $minutos_restantes); ?>
            <?php endif; ?>
            <small><?php esc_html_e('restantes', 'flavor-chat-ia'); ?></small>
        </span>
    </header>

    <div class="reserva-activa__body">
        <div class="parking-info">
            <h3 class="parking-nombre"><?php echo esc_html($reserva_activa->parking_nombre); ?></h3>
            <p class="parking-direccion">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($reserva_activa->parking_direccion); ?>
            </p>
        </div>

        <div class="plaza-info">
            <div class="plaza-numero-grande">
                <?php if (isset($iconos_tipo[$reserva_activa->plaza_tipo]) && $iconos_tipo[$reserva_activa->plaza_tipo]): ?>
                    <span class="plaza-tipo-icono"><?php echo $iconos_tipo[$reserva_activa->plaza_tipo]; ?></span>
                <?php endif; ?>
                <span class="numero"><?php echo esc_html($reserva_activa->plaza_numero); ?></span>
                <span class="label"><?php esc_html_e('Plaza', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="reserva-detalles">
            <div class="detalle-grupo">
                <span class="detalle-label"><?php esc_html_e('Entrada', 'flavor-chat-ia'); ?></span>
                <span class="detalle-valor"><?php echo date_i18n('d M, H:i', strtotime($reserva_activa->fecha_inicio)); ?></span>
            </div>
            <div class="detalle-grupo">
                <span class="detalle-label"><?php esc_html_e('Salida', 'flavor-chat-ia'); ?></span>
                <span class="detalle-valor"><?php echo date_i18n('d M, H:i', $fecha_fin); ?></span>
            </div>
            <?php if ($reserva_activa->matricula): ?>
                <div class="detalle-grupo">
                    <span class="detalle-label"><?php esc_html_e('Matrícula', 'flavor-chat-ia'); ?></span>
                    <span class="detalle-valor detalle-valor--matricula"><?php echo esc_html($reserva_activa->matricula); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($reserva_activa->codigo_acceso): ?>
            <div class="codigo-acceso">
                <span class="codigo-label"><?php esc_html_e('Código de acceso', 'flavor-chat-ia'); ?></span>
                <span class="codigo-valor"><?php echo esc_html($reserva_activa->codigo_acceso); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <footer class="reserva-activa__footer">
        <?php if ($reserva_activa->parking_telefono): ?>
            <a href="tel:<?php echo esc_attr($reserva_activa->parking_telefono); ?>" class="btn btn-outline btn-sm">
                <span class="dashicons dashicons-phone"></span>
                <?php esc_html_e('Llamar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
        <a href="<?php echo esc_url(add_query_arg('reserva', $reserva_activa->id, $parkings_url . 'detalle/')); ?>" class="btn btn-primary btn-sm">
            <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
        </a>
    </footer>
</div>

<style>
.reserva-activa-card { background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); overflow: hidden; border-left: 4px solid #10b981; }
.reserva-activa-card--advertencia { border-left-color: #f59e0b; }
.reserva-activa-card--urgente { border-left-color: #ef4444; animation: pulse-urgente 2s infinite; }

@keyframes pulse-urgente {
    0%, 100% { box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15); }
    50% { box-shadow: 0 4px 24px rgba(239, 68, 68, 0.25); }
}

.reserva-activa__header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); }
.reserva-activa-card--advertencia .reserva-activa__header { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }
.reserva-activa-card--urgente .reserva-activa__header { background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }

.estado-indicador { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; font-weight: 600; color: #059669; }
.reserva-activa-card--advertencia .estado-indicador { color: #d97706; }
.reserva-activa-card--urgente .estado-indicador { color: #dc2626; }

.indicador-punto { width: 8px; height: 8px; background: currentColor; border-radius: 50%; animation: pulse-punto 1.5s infinite; }
@keyframes pulse-punto { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.tiempo-restante { text-align: right; font-size: 1.25rem; font-weight: 700; color: #1f2937; line-height: 1.2; }
.tiempo-restante small { display: block; font-size: 0.7rem; font-weight: 400; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.reserva-activa__body { padding: 1.25rem; }

.parking-info { margin-bottom: 1rem; }
.parking-nombre { margin: 0 0 0.25rem; font-size: 1.25rem; color: #1f2937; }
.parking-direccion { margin: 0; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; }

.plaza-info { display: flex; justify-content: center; margin: 1.25rem 0; }
.plaza-numero-grande { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px; text-align: center; min-width: 100px; }
.plaza-tipo-icono { display: block; font-size: 1rem; margin-bottom: 0.25rem; }
.plaza-numero-grande .numero { display: block; font-size: 2rem; font-weight: 700; line-height: 1; }
.plaza-numero-grande .label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.9; }

.reserva-detalles { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; margin-bottom: 1rem; }
.detalle-grupo { text-align: center; }
.detalle-label { display: block; font-size: 0.7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.125rem; }
.detalle-valor { font-size: 0.9rem; font-weight: 500; color: #1f2937; }
.detalle-valor--matricula { font-family: monospace; background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 4px; }

.codigo-acceso { background: #fef3c7; border: 1px dashed #f59e0b; border-radius: 8px; padding: 0.75rem; text-align: center; margin-top: 1rem; }
.codigo-label { display: block; font-size: 0.7rem; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.codigo-valor { font-size: 1.5rem; font-weight: 700; font-family: monospace; color: #92400e; letter-spacing: 0.1em; }

.reserva-activa__footer { display: flex; justify-content: center; gap: 0.75rem; padding: 1rem 1.25rem; border-top: 1px solid #f3f4f6; }

/* Widget compacto para próxima reserva */
.reserva-activa-widget { display: flex; align-items: center; gap: 0.75rem; background: white; border-radius: 10px; padding: 0.75rem 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.reserva-activa-widget--proxima { border-left: 3px solid #f59e0b; }
.widget-icono { color: #f59e0b; }
.widget-contenido { flex: 1; }
.widget-label { display: block; font-size: 0.7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; }
.widget-titulo { display: block; font-weight: 600; color: #1f2937; font-size: 0.9rem; }
.widget-fecha { font-size: 0.8rem; color: #6b7280; }
.widget-btn { padding: 0.375rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 6px; font-size: 0.8rem; font-weight: 500; text-decoration: none; }
.widget-btn:hover { background: #e5e7eb; }

.btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
</style>
