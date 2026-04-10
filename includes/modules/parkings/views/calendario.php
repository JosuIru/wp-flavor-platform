<?php
/**
 * Vista de Calendario de Disponibilidad - Parkings
 *
 * @package FlavorPlatform
 * @subpackage Parkings
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';

// Obtener reservas del próximo mes
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t', strtotime('+1 month'));

$reservas_calendario = $wpdb->get_results($wpdb->prepare(
    "SELECT
        r.*,
        p.numero_plaza,
        p.zona,
        u.display_name as nombre_usuario
    FROM {$tabla_reservas} r
    INNER JOIN {$tabla_plazas} p ON r.plaza_id = p.id
    INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
    WHERE r.fecha_inicio >= %s AND r.fecha_inicio <= %s
    ORDER BY r.fecha_inicio ASC",
    $fecha_inicio,
    $fecha_fin
));

// Convertir a formato FullCalendar
$eventos_calendario = [];
foreach ($reservas_calendario as $reserva) {
    $color = ['activa' => '#2271b1', 'completada' => '#00a32a', 'cancelada' => '#d63638'];
    $eventos_calendario[] = [
        'id' => $reserva->id,
        'title' => 'Plaza ' . $reserva->numero_plaza . ' - ' . $reserva->nombre_usuario,
        'start' => $reserva->fecha_inicio,
        'end' => $reserva->fecha_fin,
        'backgroundColor' => $color[$reserva->estado] ?? '#666',
        'extendedProps' => [
            'plaza' => $reserva->numero_plaza,
            'zona' => $reserva->zona,
            'usuario' => $reserva->nombre_usuario,
            'precio' => $reserva->precio_total,
            'estado' => $reserva->estado
        ]
    ];
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Calendario de Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <div class="card" style="padding: 20px; margin: 20px 0;">
        <div id="calendario-parkings"></div>
    </div>

    <!-- Leyenda -->
    <div class="card" style="padding: 20px; margin: 20px 0;">
        <h3><?php esc_html_e('Leyenda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 20px; height: 20px; background: #2271b1; border-radius: 3px;"></div>
                <span><?php esc_html_e('Reserva Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 20px; height: 20px; background: #00a32a; border-radius: 3px;"></div>
                <span><?php esc_html_e('Reserva Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 20px; height: 20px; background: #d63638; border-radius: 3px;"></div>
                <span><?php esc_html_e('Reserva Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendario-parkings');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            events: <?php echo json_encode($eventos_calendario); ?>,
            eventClick: function(info) {
                const props = info.event.extendedProps;
                alert(
                    'Plaza: ' + props.plaza + '\n' +
                    'Zona: ' + props.zona + '\n' +
                    'Usuario: ' + props.usuario + '\n' +
                    'Estado: ' + props.estado + '\n' +
                    'Precio: €' + props.precio
                );
            },
            height: 'auto'
        });
        calendar.render();
    }
});
</script>
