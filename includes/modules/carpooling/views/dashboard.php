<?php
/**
 * Vista del Dashboard de Carpooling
 *
 * @package FlavorChatIA
 * @subpackage Carpooling
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

// Obtener estadísticas
global $wpdb;
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
$tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
$tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';

$fecha_inicio_mes = date('Y-m-01 00:00:00');
$fecha_actual = current_time('mysql');

// Viajes activos (usando fecha_salida que es la columna correcta)
$viajes_activos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_viajes} WHERE estado = 'activo' AND fecha_salida >= NOW()");
$viajes_completados_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_viajes} WHERE estado = 'finalizado' AND fecha_salida >= %s AND fecha_salida <= %s",
    $fecha_inicio_mes,
    $fecha_actual
));

// Reservas
$reservas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'");
$reservas_confirmadas_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'confirmada' AND fecha_reserva >= %s",
    $fecha_inicio_mes
));

// Conductores activos (contamos usuarios únicos con viajes)
$conductores_activos = $wpdb->get_var("SELECT COUNT(DISTINCT conductor_id) FROM {$tabla_viajes} WHERE estado = 'activo'");
$conductores_pendientes_verificacion = 0; // Sin tabla de conductores separada

// Total de usuarios participantes este mes
$usuarios_participantes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT r.pasajero_id)
    FROM {$tabla_reservas} r
    WHERE r.fecha_reserva >= %s",
    $fecha_inicio_mes
));

// Calcular CO2 ahorrado (estimación: 120g CO2/km por persona)
// Nota: No hay columna distancia_km, usamos estimación de plazas ocupadas
$plazas_compartidas = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(r.numero_plazas)
    FROM {$tabla_viajes} v
    INNER JOIN {$tabla_reservas} r ON v.id = r.viaje_id
    WHERE v.estado = 'finalizado' AND r.estado = 'completada' AND v.fecha_salida >= %s AND v.fecha_salida <= %s",
    $fecha_inicio_mes,
    $fecha_actual
)) ?? 0;

// Estimación: 20km promedio por viaje, 120g CO2/km
$co2_ahorrado_kg = ($plazas_compartidas * 20 * 0.12);

// Rutas más populares
$rutas_populares = $wpdb->get_results(
    "SELECT
        origen,
        destino,
        COUNT(*) as total_viajes,
        SUM(plazas_disponibles) as total_plazas
    FROM {$tabla_viajes}
    WHERE fecha_salida >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY origen, destino
    ORDER BY total_viajes DESC
    LIMIT 5"
);

// Top conductores (basado en viajes completados)
$top_conductores = $wpdb->get_results(
    "SELECT
        v.conductor_id as id,
        u.display_name,
        COUNT(v.id) as total_viajes,
        SUM(CASE WHEN v.estado = 'finalizado' THEN 1 ELSE 0 END) as viajes_completados
    FROM {$tabla_viajes} v
    INNER JOIN {$wpdb->users} u ON v.conductor_id = u.ID
    GROUP BY v.conductor_id
    ORDER BY viajes_completados DESC
    LIMIT 5"
);

// Datos para gráfica de viajes (últimos 30 días)
$datos_grafica_viajes = $wpdb->get_results(
    "SELECT
        DATE(fecha_salida) as fecha,
        COUNT(*) as total_viajes,
        SUM(plazas_disponibles) as plazas_ofertadas,
        SUM(plazas_disponibles - plazas_ocupadas) as plazas_disponibles
    FROM {$tabla_viajes}
    WHERE fecha_salida >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_salida)
    ORDER BY fecha ASC"
);

$fechas_grafica = [];
$viajes_grafica = [];
$plazas_grafica = [];

foreach ($datos_grafica_viajes as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $viajes_grafica[] = (int) $dato->total_viajes;
    $plazas_grafica[] = (int) $dato->plazas_disponibles;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Dashboard de Carpooling', 'flavor-chat-ia'); ?></h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Viajes Activos -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Viajes Activos', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($viajes_activos, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Disponibles ahora', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Viajes Completados -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Viajes Completados', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($viajes_completados_mes, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Reservas Pendientes -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Reservas Pendientes', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($reservas_pendientes, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Requieren atención', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Conductores Activos -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Conductores', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold;">
                <span style="color: #00a32a;"><?php echo esc_html($conductores_activos); ?></span>
                <span style="color: #666; font-size: 18px;">/</span>
                <span style="color: #d63638;"><?php echo esc_html($conductores_pendientes_verificacion); ?></span>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Activos / Pendientes', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- CO2 Ahorrado -->
        <div class="card" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #00a32a 0%, #008a24 100%); color: white;">
            <h3 style="margin: 0 0 10px 0; color: rgba(255,255,255,0.9); font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('CO₂ Ahorrado', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: white;">
                <?php echo esc_html(number_format($co2_ahorrado_kg, 1, ',', '.')); ?> kg
            </p>
            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9);">
                <?php esc_html_e('Este mes', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Usuarios Participantes -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($usuarios_participantes, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', 'flavor-chat-ia'); ?>
            </p>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Gráfica de viajes -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Actividad de Viajes - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
            <canvas id="flavor-carpooling-chart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Rutas Populares -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Rutas Más Populares', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ruta', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Plazas', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rutas_populares)) : ?>
                        <?php foreach ($rutas_populares as $ruta) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($ruta->origen); ?></strong>
                                    <span style="color: #666;"> → </span>
                                    <strong><?php echo esc_html($ruta->destino); ?></strong>
                                </td>
                                <td><?php echo esc_html(number_format($ruta->total_viajes, 0, ',', '.')); ?></td>
                                <td><?php echo esc_html(number_format($ruta->total_plazas, 0, ',', '.')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No hay datos disponibles.', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Top Conductores -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Top 5 Conductores', 'flavor-chat-ia'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Conductor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Viajes', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Valoración', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_conductores)) : ?>
                    <?php foreach ($top_conductores as $conductor) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($conductor->display_name); ?></strong></td>
                            <td><?php echo esc_html(number_format($conductor->total_viajes, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($conductor->viajes_completados, 0, ',', '.')); ?></td>
                            <td>
                                <?php
                                $estrellas = round($conductor->valoracion_promedio);
                                echo str_repeat('⭐', $estrellas) . ' ' . number_format($conductor->valoracion_promedio, 1);
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&conductor_id=' . $conductor->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver Perfil', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay conductores disponibles.', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Acciones rápidas -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Acciones Rápidas', 'flavor-chat-ia'); ?></h2>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes')); ?>" class="button button-primary button-large">
                <?php esc_html_e('Gestionar Viajes', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas')); ?>" class="button button-large">
                <?php esc_html_e('Ver Reservas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores')); ?>" class="button button-large">
                <?php esc_html_e('Gestionar Conductores', 'flavor-chat-ia'); ?>
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Datos para la gráfica
    const fechas = <?php echo json_encode($fechas_grafica); ?>;
    const viajes = <?php echo json_encode($viajes_grafica); ?>;
    const plazas = <?php echo json_encode($plazas_grafica); ?>;

    // Crear gráfica
    const ctx = document.getElementById('flavor-carpooling-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php esc_html_e('Viajes Publicados', 'flavor-chat-ia'); ?>',
                        data: viajes,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: '<?php esc_html_e('Plazas Disponibles', 'flavor-chat-ia'); ?>',
                        data: plazas,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>

<style>
@media (max-width: 782px) {
    .flavor-stats-grid {
        grid-template-columns: 1fr !important;
    }
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
