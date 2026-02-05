<?php
/**
 * Vista Dashboard - Banco de Tiempo
 *
 * Panel principal con estadísticas y resúmenes del banco de tiempo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

// Obtener estadísticas generales
$total_servicios_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios WHERE estado = 'activo'");
$total_servicios_ofrecidos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios");
$total_intercambios_completados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'completado'");
$total_intercambios_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado IN ('pendiente', 'aceptado')");

// Total de horas intercambiadas
$total_horas_intercambiadas = $wpdb->get_var("SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones WHERE estado = 'completado'");

// Servicios por categoría
$servicios_por_categoria = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM $tabla_servicios
     WHERE estado = 'activo'
     GROUP BY categoria
     ORDER BY total DESC"
);

// Top usuarios por créditos ganados
$top_usuarios_ganados = $wpdb->get_results(
    "SELECT usuario_receptor_id, SUM(horas) as total_horas
     FROM $tabla_transacciones
     WHERE estado = 'completado'
     GROUP BY usuario_receptor_id
     ORDER BY total_horas DESC
     LIMIT 10"
);

// Top usuarios por créditos gastados
$top_usuarios_gastados = $wpdb->get_results(
    "SELECT usuario_solicitante_id, SUM(horas) as total_horas
     FROM $tabla_transacciones
     WHERE estado = 'completado'
     GROUP BY usuario_solicitante_id
     ORDER BY total_horas DESC
     LIMIT 10"
);

// Intercambios recientes
$intercambios_recientes = $wpdb->get_results(
    "SELECT t.*, s.titulo as servicio_titulo
     FROM $tabla_transacciones t
     LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
     ORDER BY t.fecha_creacion DESC
     LIMIT 10"
);

// Actividad por mes (últimos 6 meses)
$actividad_mensual = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
            COUNT(*) as total_intercambios,
            SUM(horas) as total_horas
     FROM $tabla_transacciones
     WHERE estado = 'completado'
     AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes
     ORDER BY mes ASC"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clock"></span>
        Dashboard - Banco de Tiempo
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas Principales -->
    <div class="banco-tiempo-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="banco-tiempo-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #2271b1; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_servicios_activos); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                Servicios Activos
            </div>
        </div>

        <div class="banco-tiempo-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #00a32a; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_intercambios_completados); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                Intercambios Completados
            </div>
        </div>

        <div class="banco-tiempo-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #dba617; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_intercambios_pendientes); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                Intercambios Pendientes
            </div>
        </div>

        <div class="banco-tiempo-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="stat-icon" style="color: #8c52ff; font-size: 32px; margin-bottom: 10px;">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-value" style="font-size: 32px; font-weight: bold; color: #1d2327;">
                <?php echo number_format($total_horas_intercambiadas, 1); ?>
            </div>
            <div class="stat-label" style="color: #646970; font-size: 14px;">
                Horas Totales Intercambiadas
            </div>
        </div>
    </div>

    <!-- Gráficos y Estadísticas Detalladas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Servicios por Categoría -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-pie"></span> Servicios por Categoría</h2>
            <div class="inside">
                <canvas id="grafico-categorias" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <!-- Actividad Mensual -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-chart-line"></span> Actividad Últimos 6 Meses</h2>
            <div class="inside">
                <canvas id="grafico-actividad" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas de Rankings y Actividad -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Top Usuarios - Horas Ganadas -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-star-filled"></span> Top Usuarios - Horas Ganadas</h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Usuario</th>
                            <th style="text-align: right;">Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $posicion = 1;
                        foreach ($top_usuarios_ganados as $usuario):
                            $user_data = get_userdata($usuario->usuario_receptor_id);
                            if (!$user_data) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo $posicion++; ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $usuario->usuario_receptor_id); ?>">
                                    <?php echo esc_html($user_data->display_name); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($usuario->total_horas, 1); ?> h</strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_usuarios_ganados)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px; color: #646970;">
                                No hay datos disponibles
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Usuarios - Horas Gastadas -->
        <div class="postbox">
            <h2 class="hndle"><span class="dashicons dashicons-money-alt"></span> Top Usuarios - Horas Gastadas</h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Usuario</th>
                            <th style="text-align: right;">Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $posicion = 1;
                        foreach ($top_usuarios_gastados as $usuario):
                            $user_data = get_userdata($usuario->usuario_solicitante_id);
                            if (!$user_data) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo $posicion++; ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $usuario->usuario_solicitante_id); ?>">
                                    <?php echo esc_html($user_data->display_name); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($usuario->total_horas, 1); ?> h</strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_usuarios_gastados)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px; color: #646970;">
                                No hay datos disponibles
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Intercambios Recientes -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle"><span class="dashicons dashicons-update"></span> Intercambios Recientes</h2>
        <div class="inside">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Servicio</th>
                        <th>Solicitante</th>
                        <th>Proveedor</th>
                        <th>Horas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intercambios_recientes as $intercambio):
                        $solicitante = get_userdata($intercambio->usuario_solicitante_id);
                        $receptor = get_userdata($intercambio->usuario_receptor_id);

                        $estado_class = '';
                        $estado_texto = '';
                        switch ($intercambio->estado) {
                            case 'completado':
                                $estado_class = 'success';
                                $estado_texto = 'Completado';
                                break;
                            case 'aceptado':
                                $estado_class = 'info';
                                $estado_texto = 'Aceptado';
                                break;
                            case 'pendiente':
                                $estado_class = 'warning';
                                $estado_texto = 'Pendiente';
                                break;
                            case 'cancelado':
                                $estado_class = 'error';
                                $estado_texto = 'Cancelado';
                                break;
                        }
                    ?>
                    <tr>
                        <td><strong>#<?php echo $intercambio->id; ?></strong></td>
                        <td><?php echo esc_html($intercambio->servicio_titulo ?: 'N/A'); ?></td>
                        <td><?php echo $solicitante ? esc_html($solicitante->display_name) : 'Desconocido'; ?></td>
                        <td><?php echo $receptor ? esc_html($receptor->display_name) : 'Desconocido'; ?></td>
                        <td><strong><?php echo number_format($intercambio->horas, 1); ?> h</strong></td>
                        <td>
                            <span class="banco-tiempo-badge badge-<?php echo $estado_class; ?>"
                                  style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                <?php echo $estado_texto; ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($intercambio->fecha_creacion)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($intercambios_recientes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #646970;">
                            No hay intercambios registrados
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Estilos CSS -->
<style>
.banco-tiempo-badge {
    display: inline-block;
    text-transform: uppercase;
}
.badge-success {
    background-color: #00a32a;
    color: #fff;
}
.badge-info {
    background-color: #2271b1;
    color: #fff;
}
.badge-warning {
    background-color: #dba617;
    color: #fff;
}
.badge-error {
    background-color: #d63638;
    color: #fff;
}
.postbox h2 {
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(document).ready(function($) {

    // Gráfico de Servicios por Categoría
    const ctxCategorias = document.getElementById('grafico-categorias').getContext('2d');
    new Chart(ctxCategorias, {
        type: 'doughnut',
        data: {
            labels: [
                <?php
                foreach ($servicios_por_categoria as $cat) {
                    echo "'" . esc_js(ucfirst($cat->categoria)) . "',";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php
                    foreach ($servicios_por_categoria as $cat) {
                        echo $cat->total . ',';
                    }
                    ?>
                ],
                backgroundColor: [
                    '#2271b1', '#00a32a', '#dba617', '#d63638', '#8c52ff', '#00a0d2', '#b4a000'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Gráfico de Actividad Mensual
    const ctxActividad = document.getElementById('grafico-actividad').getContext('2d');
    new Chart(ctxActividad, {
        type: 'line',
        data: {
            labels: [
                <?php
                foreach ($actividad_mensual as $mes) {
                    $fecha = DateTime::createFromFormat('Y-m', $mes->mes);
                    echo "'" . $fecha->format('M Y') . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Intercambios',
                data: [
                    <?php
                    foreach ($actividad_mensual as $mes) {
                        echo $mes->total_intercambios . ',';
                    }
                    ?>
                ],
                borderColor: '#2271b1',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Horas',
                data: [
                    <?php
                    foreach ($actividad_mensual as $mes) {
                        echo $mes->total_horas . ',';
                    }
                    ?>
                ],
                borderColor: '#00a32a',
                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top'
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
</script>
