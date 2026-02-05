<?php
/**
 * Vista del Dashboard de Parkings
 *
 * @package FlavorChatIA
 * @subpackage Parkings
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
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_propietarios = $wpdb->prefix . 'flavor_parkings_propietarios';

$fecha_inicio_mes = date('Y-m-01 00:00:00');
$fecha_actual = current_time('mysql');

// Plazas
$total_plazas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas}");
$plazas_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'disponible'");
$plazas_ocupadas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'ocupada'");
$plazas_mantenimiento = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_plazas} WHERE estado = 'mantenimiento'");

// Reservas
$reservas_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'activa' AND fecha_fin >= NOW()");
$reservas_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_inicio >= %s",
    $fecha_inicio_mes
));

// Ingresos
$ingresos_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(precio_total) FROM {$tabla_reservas} WHERE estado IN ('activa', 'completada') AND fecha_inicio >= %s",
    $fecha_inicio_mes
)) ?? 0;

// Propietarios
$propietarios_activos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_propietarios} WHERE estado = 'activo'");

// Tasa de ocupación
$tasa_ocupacion = $total_plazas > 0 ? ($plazas_ocupadas / $total_plazas) * 100 : 0;

// Plazas por zona
$plazas_por_zona = $wpdb->get_results(
    "SELECT
        zona,
        COUNT(*) as total_plazas,
        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
        SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas
    FROM {$tabla_plazas}
    GROUP BY zona
    ORDER BY total_plazas DESC
    LIMIT 10"
);

// Top propietarios
$top_propietarios = $wpdb->get_results(
    "SELECT
        p.id,
        u.display_name,
        COUNT(pl.id) as total_plazas,
        SUM(CASE WHEN pl.estado = 'ocupada' THEN 1 ELSE 0 END) as plazas_ocupadas,
        COUNT(r.id) as total_reservas,
        COALESCE(SUM(r.precio_total), 0) as ingresos_totales
    FROM {$tabla_propietarios} p
    INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
    LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
    LEFT JOIN {$tabla_reservas} r ON pl.id = r.plaza_id AND r.fecha_inicio >= '{$fecha_inicio_mes}'
    WHERE p.estado = 'activo'
    GROUP BY p.id
    ORDER BY ingresos_totales DESC
    LIMIT 5"
);

// Datos para gráfica (últimos 30 días)
$datos_grafica = $wpdb->get_results(
    "SELECT
        DATE(fecha_inicio) as fecha,
        COUNT(*) as reservas,
        SUM(precio_total) as ingresos
    FROM {$tabla_reservas}
    WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_inicio)
    ORDER BY fecha ASC"
);

$fechas_grafica = [];
$reservas_grafica = [];
$ingresos_grafica = [];

foreach ($datos_grafica as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $reservas_grafica[] = (int) $dato->reservas;
    $ingresos_grafica[] = (float) $dato->ingresos;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Dashboard de Parkings', 'flavor-chat-ia'); ?></h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Total Plazas -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Total Plazas', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($total_plazas, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('En el sistema', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Plazas Disponibles -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($plazas_disponibles, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php echo esc_html(number_format(($total_plazas > 0 ? ($plazas_disponibles / $total_plazas) * 100 : 0), 1)); ?>% del total
            </p>
        </div>

        <!-- Tasa de Ocupación -->
        <div class="card" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white;">
            <h3 style="margin: 0 0 10px 0; color: rgba(255,255,255,0.9); font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Tasa de Ocupación', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: white;">
                <?php echo esc_html(number_format($tasa_ocupacion, 1)); ?>%
            </p>
            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9);">
                <?php echo esc_html($plazas_ocupadas); ?> / <?php echo esc_html($total_plazas); ?> ocupadas
            </p>
        </div>

        <!-- Reservas Activas -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Reservas Activas', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($reservas_activas, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('En curso ahora', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Ingresos del Mes -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Ingresos del Mes', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                €<?php echo esc_html(number_format($ingresos_mes, 2, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php echo esc_html($reservas_mes); ?> <?php esc_html_e('reservas', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Propietarios Activos -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Propietarios', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($propietarios_activos, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Activos', 'flavor-chat-ia'); ?>
            </p>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Gráfica de reservas e ingresos -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Actividad - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
            <canvas id="flavor-parkings-chart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Estado de las plazas -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Estado de Plazas', 'flavor-chat-ia'); ?></h2>
            <canvas id="flavor-parkings-pie-chart" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Plazas por Zona -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Distribución por Zona', 'flavor-chat-ia'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Zona', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Plazas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('% Ocupación', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($plazas_por_zona)) : ?>
                    <?php foreach ($plazas_por_zona as $zona) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($zona->zona); ?></strong></td>
                            <td><?php echo esc_html(number_format($zona->total_plazas, 0, ',', '.')); ?></td>
                            <td style="color: #00a32a;">
                                <strong><?php echo esc_html(number_format($zona->disponibles, 0, ',', '.')); ?></strong>
                            </td>
                            <td style="color: #d63638;">
                                <strong><?php echo esc_html(number_format($zona->ocupadas, 0, ',', '.')); ?></strong>
                            </td>
                            <td>
                                <?php
                                $ocupacion_zona = $zona->total_plazas > 0 ? ($zona->ocupadas / $zona->total_plazas) * 100 : 0;
                                ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e0e0e0; border-radius: 10px; height: 20px; overflow: hidden;">
                                        <div style="background: <?php echo $ocupacion_zona > 80 ? '#d63638' : ($ocupacion_zona > 50 ? '#dba617' : '#00a32a'); ?>; height: 100%; width: <?php echo esc_attr($ocupacion_zona); ?>%;"></div>
                                    </div>
                                    <span style="min-width: 50px;"><?php echo number_format($ocupacion_zona, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay datos disponibles.', 'flavor-chat-ia'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Propietarios -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Top 5 Propietarios por Ingresos', 'flavor-chat-ia'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Propietario', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Plazas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_propietarios)) : ?>
                    <?php foreach ($top_propietarios as $propietario) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($propietario->display_name); ?></strong></td>
                            <td><?php echo esc_html(number_format($propietario->total_plazas, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($propietario->plazas_ocupadas, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($propietario->total_reservas, 0, ',', '.')); ?></td>
                            <td><strong>€<?php echo esc_html(number_format($propietario->ingresos_totales, 2, ',', '.')); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios&propietario_id=' . $propietario->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver Perfil', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay datos disponibles.', 'flavor-chat-ia'); ?>
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas')); ?>" class="button button-primary button-large">
                <?php esc_html_e('Gestionar Plazas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-reservas')); ?>" class="button button-large">
                <?php esc_html_e('Ver Reservas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios')); ?>" class="button button-large">
                <?php esc_html_e('Gestionar Propietarios', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-calendario')); ?>" class="button button-large">
                <?php esc_html_e('Ver Calendario', 'flavor-chat-ia'); ?>
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Gráfica de líneas
    const fechas = <?php echo json_encode($fechas_grafica); ?>;
    const reservas = <?php echo json_encode($reservas_grafica); ?>;
    const ingresos = <?php echo json_encode($ingresos_grafica); ?>;

    const ctx1 = document.getElementById('flavor-parkings-chart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php esc_html_e('Reservas', 'flavor-chat-ia'); ?>',
                        data: reservas,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: '<?php esc_html_e('Ingresos (€)', 'flavor-chat-ia'); ?>',
                        data: ingresos,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    }

    // Gráfica de pastel
    const ctx2 = document.getElementById('flavor-parkings-pie-chart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [
                    '<?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>',
                    '<?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?>',
                    '<?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?>'
                ],
                datasets: [{
                    data: [
                        <?php echo esc_js($plazas_disponibles); ?>,
                        <?php echo esc_js($plazas_ocupadas); ?>,
                        <?php echo esc_js($plazas_mantenimiento); ?>
                    ],
                    backgroundColor: ['#00a32a', '#d63638', '#dba617']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
});
</script>

<style>
@media (max-width: 782px) {
    .flavor-stats-grid,
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
