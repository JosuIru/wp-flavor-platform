<?php
/**
 * Vista del Dashboard de Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
$tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

$fecha_inicio_mes = date('Y-m-01 00:00:00');

// Estadísticas
$total_bicicletas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas}");
$bicicletas_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'disponible'");
$bicicletas_en_uso = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'en_uso'");
$bicicletas_mantenimiento = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estado = 'mantenimiento'");

$total_estaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estaciones}");
$estaciones_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_estaciones} WHERE estado = 'activa'");

$usos_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_prestamos} WHERE fecha_inicio >= %s",
    $fecha_inicio_mes
));

$usos_activos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_prestamos} WHERE estado = 'activo'");

// Calcular distancia total y CO2 ahorrado
$stats_distancia = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(kilometros_recorridos) as distancia_total,
        AVG(duracion_minutos) as duracion_promedio
    FROM {$tabla_prestamos}
    WHERE estado = 'completado' AND fecha_inicio >= %s",
    $fecha_inicio_mes
));

$distancia_total_mes = $stats_distancia->distancia_total ?? 0;
$duracion_promedio = $stats_distancia->duracion_promedio ?? 0;
$co2_ahorrado_kg = $distancia_total_mes * 0.12; // 120g CO2 por km

// Top estaciones por uso
$top_estaciones = $wpdb->get_results(
    "SELECT
        e.nombre,
        e.direccion,
        COUNT(p.id) as total_usos,
        (SELECT COUNT(*) FROM {$tabla_bicicletas} WHERE estacion_actual_id = e.id) as bicicletas_actuales
    FROM {$tabla_estaciones} e
    LEFT JOIN {$tabla_prestamos} p ON (e.id = p.estacion_salida_id OR e.id = p.estacion_llegada_id)
    WHERE e.estado = 'activa'
    GROUP BY e.id
    ORDER BY total_usos DESC
    LIMIT 5"
);

// Datos para gráfica
$datos_grafica = $wpdb->get_results(
    "SELECT
        DATE(fecha_inicio) as fecha,
        COUNT(*) as usos,
        SUM(kilometros_recorridos) as distancia
    FROM {$tabla_prestamos}
    WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_inicio)
    ORDER BY fecha ASC"
);

$fechas_grafica = [];
$usos_grafica = [];
$distancia_grafica = [];

foreach ($datos_grafica as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $usos_grafica[] = (int) $dato->usos;
    $distancia_grafica[] = (float) $dato->distancia;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Dashboard de Bicicletas Compartidas', 'flavor-chat-ia'); ?></h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Total Bicicletas', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($total_bicicletas, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('En la flota', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($bicicletas_disponibles, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php echo esc_html(number_format(($total_bicicletas > 0 ? ($bicicletas_disponibles / $total_bicicletas) * 100 : 0), 1)); ?>%
            </p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('En Uso', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($bicicletas_en_uso, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Ahora mismo', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Estaciones', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold;">
                <span style="color: #00a32a;"><?php echo esc_html($estaciones_activas); ?></span>
                <span style="color: #666; font-size: 18px;">/</span>
                <span style="color: #666;"><?php echo esc_html($total_estaciones); ?></span>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Activas / Total', 'flavor-chat-ia'); ?>
            </p>
        </div>

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

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Usos del Mes', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($usos_mes, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php echo esc_html(number_format($duracion_promedio, 0)); ?> min promedio
            </p>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Gráfica de actividad -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Actividad - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
            <canvas id="flavor-bicicletas-chart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Estado de la flota -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Estado de la Flota', 'flavor-chat-ia'); ?></h2>
            <canvas id="flavor-bicicletas-pie-chart" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Top Estaciones -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Top 5 Estaciones por Uso', 'flavor-chat-ia'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Estación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Usos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Bicicletas Actuales', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_estaciones)) : ?>
                    <?php foreach ($top_estaciones as $estacion) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($estacion->nombre); ?></strong></td>
                            <td><?php echo esc_html($estacion->direccion); ?></td>
                            <td><?php echo esc_html(number_format($estacion->total_usos, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html($estacion->bicicletas_actuales); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=ver&estacion_id=' . $estacion->nombre)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
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

    <!-- Acciones rápidas -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Acciones Rápidas', 'flavor-chat-ia'); ?></h2>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="button button-primary button-large">
                <?php esc_html_e('Gestionar Estaciones', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas')); ?>" class="button button-large">
                <?php esc_html_e('Gestionar Bicicletas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-uso')); ?>" class="button button-large">
                <?php esc_html_e('Ver Estadísticas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento')); ?>" class="button button-large">
                <?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?>
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Gráfica de líneas
    const fechas = <?php echo json_encode($fechas_grafica); ?>;
    const usos = <?php echo json_encode($usos_grafica); ?>;
    const distancia = <?php echo json_encode($distancia_grafica); ?>;

    const ctx1 = document.getElementById('flavor-bicicletas-chart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php esc_html_e('Usos', 'flavor-chat-ia'); ?>',
                        data: usos,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: '<?php esc_html_e('Distancia (km)', 'flavor-chat-ia'); ?>',
                        data: distancia,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', beginAtZero: true },
                    y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // Gráfica de pastel
    const ctx2 = document.getElementById('flavor-bicicletas-pie-chart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['<?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>', '<?php esc_html_e('En Uso', 'flavor-chat-ia'); ?>', '<?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?>'],
                datasets: [{
                    data: [<?php echo esc_js($bicicletas_disponibles); ?>, <?php echo esc_js($bicicletas_en_uso); ?>, <?php echo esc_js($bicicletas_mantenimiento); ?>],
                    backgroundColor: ['#00a32a', '#2271b1', '#dba617']
                }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
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
