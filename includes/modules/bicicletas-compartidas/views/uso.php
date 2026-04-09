<?php
/**
 * Vista de Estadísticas de Uso - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN));

global $wpdb;
$tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';

$fecha_inicio_mes = date('Y-m-01');

// Estadísticas generales
$stats = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total_usos,
        SUM(kilometros_recorridos) as distancia_total,
        AVG(kilometros_recorridos) as distancia_promedio,
        AVG(duracion_minutos) as duracion_promedio,
        COUNT(DISTINCT usuario_id) as usuarios_unicos
    FROM {$tabla_prestamos}
    WHERE fecha_inicio >= %s",
    $fecha_inicio_mes
));

// Top usuarios
$top_usuarios = $wpdb->get_results($wpdb->prepare(
    "SELECT
        u.display_name,
        u.user_email,
        COUNT(p.id) as total_usos,
        SUM(p.kilometros_recorridos) as distancia_total,
        AVG(p.duracion_minutos) as duracion_promedio
    FROM {$tabla_prestamos} p
    INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
    WHERE p.fecha_inicio >= %s
    GROUP BY p.usuario_id
    ORDER BY total_usos DESC
    LIMIT 10",
    $fecha_inicio_mes
));

// Top bicicletas
$top_bicicletas = $wpdb->get_results($wpdb->prepare(
    "SELECT
        b.codigo,
        b.modelo,
        COUNT(p.id) as total_usos,
        SUM(p.kilometros_recorridos) as distancia_total
    FROM {$tabla_prestamos} p
    INNER JOIN {$tabla_bicicletas} b ON p.bicicleta_id = b.id
    WHERE p.fecha_inicio >= %s
    GROUP BY p.bicicleta_id
    ORDER BY total_usos DESC
    LIMIT 10",
    $fecha_inicio_mes
));

// Datos por hora del día
$usos_por_hora = $wpdb->get_results(
    "SELECT
        HOUR(fecha_inicio) as hora,
        COUNT(*) as total_usos
    FROM {$tabla_prestamos}
    WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(fecha_inicio)
    ORDER BY hora ASC"
);

$horas_grafica = [];
$usos_hora_grafica = [];
for ($i = 0; $i < 24; $i++) {
    $horas_grafica[] = sprintf('%02d:00', $i);
    $usos_hora_grafica[$i] = 0;
}
foreach ($usos_por_hora as $dato) {
    $usos_hora_grafica[(int)$dato->hora] = (int)$dato->total_usos;
}

$co2_ahorrado = ($stats->distancia_total ?? 0) * 0.12;
?>

<div class="wrap">
    <h1><?php esc_html_e('Estadísticas de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Total Usos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats->total_usos ?? 0, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Distancia Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats->distancia_total ?? 0, 1, ',', '.')); ?> km
            </p>
            <p class="description" style="margin: 10px 0 0 0;"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="card" style="padding: 20px; text-align: center; background: linear-gradient(135deg, #00a32a 0%, #008a24 100%); color: white;">
            <h3 style="margin: 0 0 10px 0; color: rgba(255,255,255,0.9); font-size: 14px; text-transform: uppercase;"><?php esc_html_e('CO₂ Ahorrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: white;">
                <?php echo esc_html(number_format($co2_ahorrado, 1, ',', '.')); ?> kg
            </p>
            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9);"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Duración Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats->duracion_promedio ?? 0, 0)); ?> min
            </p>
            <p class="description" style="margin: 10px 0 0 0;"><?php esc_html_e('Por viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;"><?php esc_html_e('Usuarios Únicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats->usuarios_unicos ?? 0, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <!-- Gráfica de uso por hora -->
    <div class="card" style="padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Distribución de Uso por Hora del Día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <canvas id="flavor-uso-hora-chart" style="max-height: 300px;"></canvas>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Top Usuarios -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Top 10 Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Usos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Distancia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_usuarios)) : ?>
                        <?php foreach ($top_usuarios as $usuario) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($usuario->display_name); ?></strong></td>
                                <td><?php echo esc_html($usuario->total_usos); ?></td>
                                <td><?php echo number_format($usuario->distancia_total, 1); ?> km</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3" style="text-align: center; padding: 20px;"><?php esc_html_e('No hay datos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Bicicletas -->
        <div class="card" style="padding: 20px;">
            <h2><?php esc_html_e('Top 10 Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Usos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Distancia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_bicicletas)) : ?>
                        <?php foreach ($top_bicicletas as $bicicleta) : ?>
                            <tr>
                                <td>🚲 <strong><?php echo esc_html($bicicleta->codigo); ?></strong></td>
                                <td><?php echo esc_html($bicicleta->total_usos); ?></td>
                                <td><?php echo number_format($bicicleta->distancia_total, 1); ?> km</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3" style="text-align: center; padding: 20px;"><?php esc_html_e('No hay datos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('flavor-uso-hora-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($horas_grafica); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Usos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                    data: <?php echo json_encode(array_values($usos_hora_grafica)); ?>,
                    backgroundColor: 'rgba(34, 113, 177, 0.8)',
                    borderColor: '#2271b1',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>

<style>
@media (max-width: 782px) {
    .flavor-stats-grid,
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
