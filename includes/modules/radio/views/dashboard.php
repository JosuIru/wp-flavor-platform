<?php
/**
 * Vista Dashboard del módulo Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$tabla_emisiones = $wpdb->prefix . 'flavor_radio_emisiones';
$tabla_locutores = $wpdb->prefix . 'flavor_radio_locutores';

// Obtener estadísticas generales
$total_programas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_programas WHERE estado = 'activo'");
$total_emisiones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_emisiones");
$total_locutores = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_locutores WHERE estado = 'activo'");
$total_oyentes = $wpdb->get_var("SELECT SUM(oyentes_pico) FROM $tabla_emisiones WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

// Programas más populares
$programas_populares = $wpdb->get_results("
    SELECT p.*, COUNT(e.id) as total_emisiones, SUM(e.oyentes_pico) as total_oyentes
    FROM $tabla_programas p
    LEFT JOIN $tabla_emisiones e ON p.id = e.programa_id
    WHERE p.estado = 'activo'
    GROUP BY p.id
    ORDER BY total_oyentes DESC
    LIMIT 10
");

// Emisiones recientes
$emisiones_recientes = $wpdb->get_results("
    SELECT e.*, p.nombre as programa_nombre
    FROM $tabla_emisiones e
    INNER JOIN $tabla_programas p ON e.programa_id = p.id
    ORDER BY e.fecha_emision DESC
    LIMIT 10
");

// Estadísticas de audiencia por día de la semana
$audiencia_por_dia = $wpdb->get_results("
    SELECT DAYNAME(fecha_emision) as dia, AVG(oyentes_pico) as promedio_oyentes
    FROM $tabla_emisiones
    WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DAYOFWEEK(fecha_emision), DAYNAME(fecha_emision)
    ORDER BY DAYOFWEEK(fecha_emision)
");

// Estado actual de la emisión
$emision_actual = $wpdb->get_row("
    SELECT e.*, p.nombre as programa_nombre, p.descripcion as programa_descripcion
    FROM $tabla_emisiones e
    INNER JOIN $tabla_programas p ON e.programa_id = p.id
    WHERE e.estado = 'en_vivo'
    ORDER BY e.fecha_emision DESC
    LIMIT 1
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-controls-volumeon"></span>
        <?php echo esc_html__('Dashboard de Radio', 'flavor-chat-ia'); ?>
    </h1>

    <!-- Estado de emisión en vivo -->
    <?php if ($emision_actual): ?>
        <div class="notice notice-success" style="display: flex; align-items: center; padding: 20px; margin: 20px 0; border-left: 4px solid #00a32a; background: #fff;">
            <span class="dashicons dashicons-controls-play" style="font-size: 48px; color: #00a32a; margin-right: 20px; animation: pulse 2s infinite;"></span>
            <div style="flex: 1;">
                <h2 style="margin: 0 0 5px 0; color: #00a32a;">
                    <?php echo esc_html__('EN VIVO AHORA', 'flavor-chat-ia'); ?>
                </h2>
                <h3 style="margin: 0 0 5px 0;"><?php echo esc_html($emision_actual->programa_nombre); ?></h3>
                <p style="margin: 0; color: #666;"><?php echo esc_html($emision_actual->programa_descripcion); ?></p>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                    <strong><?php echo number_format($emision_actual->oyentes_actual ?? 0); ?></strong> <?php echo esc_html__('oyentes conectados', 'flavor-chat-ia'); ?>
                </p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-radio&tab=emision&id=' . $emision_actual->id)); ?>" class="button button-primary button-large">
                <span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('Gestionar Emisión', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="notice notice-info" style="display: flex; align-items: center; padding: 20px; margin: 20px 0;">
            <span class="dashicons dashicons-info" style="font-size: 32px; color: #2271b1; margin-right: 15px;"></span>
            <div>
                <strong><?php echo esc_html__('No hay emisiones en vivo en este momento', 'flavor-chat-ia'); ?></strong>
                <p style="margin: 5px 0 0 0;"><?php echo esc_html__('La próxima emisión programada comenzará pronto.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Programas Activos', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_programas); ?></h2>
                </div>
                <span class="dashicons dashicons-microphone" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Emisiones', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($total_emisiones); ?></h2>
                </div>
                <span class="dashicons dashicons-album" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Locutores', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #8c49d8;"><?php echo number_format($total_locutores); ?></h2>
                </div>
                <span class="dashicons dashicons-admin-users" style="font-size: 48px; color: #8c49d8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Oyentes (30d)', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #d63638;"><?php echo number_format($total_oyentes ?? 0); ?></h2>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 48px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- Gráfico de audiencia por día -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php echo esc_html__('Audiencia por Día de la Semana', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="grafico-audiencia-dia" style="max-height: 300px;"></canvas>
        </div>

        <!-- Programas más populares -->
        <div class="flavor-list-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-star-filled"></span>
                <?php echo esc_html__('Top Programas', 'flavor-chat-ia'); ?>
            </h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php if (empty($programas_populares)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;"><?php echo esc_html__('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <?php foreach (array_slice($programas_populares, 0, 5) as $indice => $programa): ?>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div style="width: 30px; height: 30px; background: <?php echo ['#2271b1', '#00a32a', '#8c49d8', '#dba617', '#d63638'][$indice % 5]; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 12px;">
                                <?php echo $indice + 1; ?>
                            </div>
                            <div style="flex: 1;">
                                <strong style="display: block; font-size: 14px;"><?php echo esc_html($programa->nombre); ?></strong>
                                <small style="color: #666;"><?php echo number_format($programa->total_oyentes ?? 0); ?> oyentes</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Emisiones recientes -->
    <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-clock"></span>
            <?php echo esc_html__('Emisiones Recientes', 'flavor-chat-ia'); ?>
        </h3>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Programa', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Duración', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Oyentes Pico', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emisiones_recientes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-album" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;"><?php echo esc_html__('No hay emisiones registradas', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emisiones_recientes as $emision): ?>
                        <tr>
                            <td><strong>#<?php echo $emision->id; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($emision->programa_nombre); ?></strong>
                            </td>
                            <td>
                                <?php
                                if ($emision->duracion_minutos) {
                                    echo floor($emision->duracion_minutos / 60) . 'h ' . ($emision->duracion_minutos % 60) . 'min';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="dashicons dashicons-groups" style="color: #2271b1;"></span>
                                <?php echo number_format($emision->oyentes_pico ?? 0); ?>
                            </td>
                            <td>
                                <?php
                                $estado_colors = [
                                    'en_vivo' => '#00a32a',
                                    'finalizada' => '#2271b1',
                                    'programada' => '#dba617',
                                    'cancelada' => '#d63638'
                                ];
                                ?>
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $estado_colors[$emision->estado] ?? '#666'; ?>;">
                                    <?php echo ucfirst(str_replace('_', ' ', $emision->estado)); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($emision->fecha_emision)); ?></td>
                            <td>
                                <button class="button button-small" onclick="verEmision(<?php echo $emision->id; ?>)">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {

    // Gráfico de audiencia por día
    var ctxAudienciaDia = document.getElementById('grafico-audiencia-dia').getContext('2d');
    new Chart(ctxAudienciaDia, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($item) {
                return $item->dia;
            }, $audiencia_por_dia)); ?>,
            datasets: [{
                label: 'Oyentes Promedio',
                data: <?php echo json_encode(array_map(function($item) {
                    return round($item->promedio_oyentes);
                }, $audiencia_por_dia)); ?>,
                backgroundColor: '#2271b1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
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

});

function verEmision(emisionId) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-radio&tab=emisiones&ver='); ?>' + emisionId;
}
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.flavor-stats-grid,
.flavor-chart-card,
.flavor-table-card,
.flavor-list-card {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
