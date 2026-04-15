<?php
/**
 * Vista Estadísticas - Módulo Reciclaje
 * Análisis detallado del impacto del reciclaje
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

// Filtros
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'mes';
$material_filtro = isset($_GET['material']) ? sanitize_text_field($_GET['material']) : '';

// Condiciones de fecha según el período
$condiciones_fecha = match($periodo) {
    'semana' => 'fecha_deposito >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
    'mes' => 'MONTH(fecha_deposito) = MONTH(CURRENT_DATE()) AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())',
    'trimestre' => 'fecha_deposito >= DATE_SUB(NOW(), INTERVAL 3 MONTH)',
    'anio' => 'YEAR(fecha_deposito) = YEAR(CURRENT_DATE())',
    default => '1=1'
};

$where_material = $material_filtro ? $wpdb->prepare('AND tipo_material = %s', $material_filtro) : '';

// Estadísticas generales del período
$stats_generales = $wpdb->get_row("
    SELECT
        COUNT(*) as total_depositos,
        SUM(cantidad_kg) as total_kg,
        AVG(cantidad_kg) as promedio_kg,
        COUNT(DISTINCT usuario_id) as usuarios_activos,
        SUM(puntos_ganados) as puntos_totales
    FROM $tabla_depositos
    WHERE $condiciones_fecha $where_material
");

// Evolución temporal
$evolucion_query = match($periodo) {
    'semana' => "SELECT DATE(fecha_deposito) as periodo, SUM(cantidad_kg) as kg FROM $tabla_depositos WHERE $condiciones_fecha $where_material GROUP BY periodo",
    'mes' => "SELECT DATE(fecha_deposito) as periodo, SUM(cantidad_kg) as kg FROM $tabla_depositos WHERE $condiciones_fecha $where_material GROUP BY periodo",
    'trimestre' => "SELECT DATE_FORMAT(fecha_deposito, '%Y-%m-%d') as periodo, SUM(cantidad_kg) as kg FROM $tabla_depositos WHERE $condiciones_fecha $where_material GROUP BY periodo",
    'anio' => "SELECT DATE_FORMAT(fecha_deposito, '%Y-%m') as periodo, SUM(cantidad_kg) as kg FROM $tabla_depositos WHERE $condiciones_fecha $where_material GROUP BY periodo",
    default => "SELECT DATE(fecha_deposito) as periodo, SUM(cantidad_kg) as kg FROM $tabla_depositos WHERE $condiciones_fecha $where_material GROUP BY periodo"
};
$evolucion_datos = $wpdb->get_results($evolucion_query);

// Distribución por material
$distribucion_materiales = $wpdb->get_results("
    SELECT tipo_material,
           SUM(cantidad_kg) as total_kg,
           COUNT(*) as total_depositos
    FROM $tabla_depositos
    WHERE $condiciones_fecha
    GROUP BY tipo_material
    ORDER BY total_kg DESC
");

// Top usuarios
$top_usuarios = $wpdb->get_results("
    SELECT u.ID, u.display_name,
           COUNT(d.id) as depositos,
           SUM(d.cantidad_kg) as kg_total,
           SUM(d.puntos_ganados) as puntos
    FROM {$wpdb->users} u
    INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
    WHERE $condiciones_fecha $where_material
    GROUP BY u.ID
    ORDER BY kg_total DESC
    LIMIT 20
");

// Estadísticas por día de la semana
$stats_dias_semana = $wpdb->get_results("
    SELECT DAYOFWEEK(fecha_deposito) as dia_semana,
           COUNT(*) as total_depositos,
           SUM(cantidad_kg) as total_kg
    FROM $tabla_depositos
    WHERE $condiciones_fecha $where_material
    GROUP BY dia_semana
    ORDER BY dia_semana
");

// Comparativa con período anterior
$periodo_anterior = match($periodo) {
    'semana' => 'fecha_deposito >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND fecha_deposito < DATE_SUB(NOW(), INTERVAL 7 DAY)',
    'mes' => 'MONTH(fecha_deposito) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_deposito) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))',
    'trimestre' => 'fecha_deposito >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND fecha_deposito < DATE_SUB(NOW(), INTERVAL 3 MONTH)',
    'anio' => 'YEAR(fecha_deposito) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR))',
    default => '1=1'
};

$stats_anterior = $wpdb->get_row("
    SELECT SUM(cantidad_kg) as total_kg
    FROM $tabla_depositos
    WHERE $periodo_anterior $where_material
");

$variacion_kg = $stats_anterior && $stats_anterior->total_kg > 0
    ? (($stats_generales->total_kg - $stats_anterior->total_kg) / $stats_anterior->total_kg) * 100
    : 0;

// Cálculos de impacto ambiental
$co2_evitado = $stats_generales->total_kg * 0.75;
$arboles_equivalentes = $stats_generales->total_kg / 17;
$agua_ahorrada = $stats_generales->total_kg * 5;
$energia_ahorrada = $stats_generales->total_kg * 0.5;
?>

<div class="wrap flavor-reciclaje-estadisticas">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php echo esc_html__('Estadísticas e Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filtros-container">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-reciclaje-estadisticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

            <label for="periodo"><?php echo esc_html__('Período:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="periodo" id="periodo" onchange="this.form.submit()">
                <option value="<?php echo esc_attr__('semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($periodo, 'semana'); ?>><?php echo esc_html__('Última semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($periodo, 'mes'); ?>><?php echo esc_html__('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('trimestre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($periodo, 'trimestre'); ?>><?php echo esc_html__('Último trimestre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('anio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($periodo, 'anio'); ?>><?php echo esc_html__('Este año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <label for="material"><?php echo esc_html__('Material:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="material" id="material" onchange="this.form.submit()">
                <option value=""><?php echo esc_html__('Todos los materiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('papel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'papel'); ?>><?php echo esc_html__('Papel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('plastico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'plastico'); ?>><?php echo esc_html__('Plástico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('vidrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'vidrio'); ?>><?php echo esc_html__('Vidrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('organico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'organico'); ?>><?php echo esc_html__('Orgánico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('electronico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'electronico'); ?>><?php echo esc_html__('Electrónico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('ropa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'ropa'); ?>><?php echo esc_html__('Ropa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('aceite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'aceite'); ?>><?php echo esc_html__('Aceite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('pilas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($material_filtro, 'pilas'); ?>><?php echo esc_html__('Pilas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <button type="submit" class="button"><?php echo esc_html__('Aplicar Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-estadisticas'); ?>" class="button">
                <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </form>
    </div>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-principales">
        <div class="flavor-stat-card flavor-stat-primary">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($stats_generales->total_kg, 2); ?> kg</h3>
                <p><?php echo esc_html__('Total Reciclado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php if ($variacion_kg != 0) : ?>
                    <span class="flavor-stat-trend <?php echo $variacion_kg > 0 ? 'positive' : 'negative'; ?>">
                        <span class="dashicons dashicons-arrow-<?php echo $variacion_kg > 0 ? 'up' : 'down'; ?>-alt"></span>
                        <?php echo abs(round($variacion_kg, 1)); ?>% vs período anterior
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-success">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($stats_generales->total_depositos); ?></h3>
                <p><?php echo esc_html__('Depósitos Realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-info">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($stats_generales->usuarios_activos); ?></h3>
                <p><?php echo esc_html__('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-warning">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($stats_generales->puntos_totales); ?></h3>
                <p><?php echo esc_html__('Puntos Generados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <div class="flavor-dashboard-grid">
        <!-- Gráfica de evolución -->
        <div class="flavor-dashboard-widget flavor-widget-large">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Evolución Temporal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-evolucion" height="80"></canvas>
            </div>
        </div>

        <!-- Distribución por material -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Distribución por Material', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-materiales" height="200"></canvas>
            </div>
        </div>

        <!-- Top usuarios -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Top Recicladores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <div class="flavor-ranking-list">
                    <?php foreach ($top_usuarios as $index => $usuario) : ?>
                        <div class="flavor-ranking-item">
                            <span class="flavor-ranking-position"><?php echo $index + 1; ?></span>
                            <div class="flavor-ranking-user">
                                <?php echo get_avatar($usuario->ID, 32); ?>
                                <div class="flavor-ranking-info">
                                    <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                    <span><?php echo sprintf(__('%s kg • %s depósitos', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($usuario->kg_total, 2), number_format($usuario->depositos)); ?></span>
                                </div>
                            </div>
                            <span class="flavor-ranking-points"><?php echo number_format($usuario->puntos); ?> pts</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Estadísticas por día de la semana -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Actividad por Día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-dias-semana" height="200"></canvas>
            </div>
        </div>

        <!-- Impacto ambiental -->
        <div class="flavor-dashboard-widget flavor-widget-large flavor-widget-impacto">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Impacto Ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-widget-subtitle"><?php echo esc_html__('Equivalencias estimadas del reciclaje realizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="flavor-widget-body">
                <div class="flavor-impacto-grid">
                    <div class="flavor-impacto-item">
                        <div class="flavor-impacto-icon">🌍</div>
                        <div class="flavor-impacto-content">
                            <strong><?php echo number_format($co2_evitado, 2); ?> kg</strong>
                            <span><?php echo esc_html__('CO₂ evitado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>

                    <div class="flavor-impacto-item">
                        <div class="flavor-impacto-icon">🌳</div>
                        <div class="flavor-impacto-content">
                            <strong><?php echo number_format($arboles_equivalentes, 0); ?></strong>
                            <span><?php echo esc_html__('Árboles equivalentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>

                    <div class="flavor-impacto-item">
                        <div class="flavor-impacto-icon">💧</div>
                        <div class="flavor-impacto-content">
                            <strong><?php echo number_format($agua_ahorrada, 0); ?> L</strong>
                            <span><?php echo esc_html__('Agua ahorrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>

                    <div class="flavor-impacto-item">
                        <div class="flavor-impacto-icon">⚡</div>
                        <div class="flavor-impacto-content">
                            <strong><?php echo number_format($energia_ahorrada, 2); ?> kWh</strong>
                            <span><?php echo esc_html__('Energía ahorrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de exportación -->
    <div class="flavor-export-section">
        <h3><?php echo esc_html__('Exportar Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <button class="button button-primary" onclick="exportarCSV()">
            <span class="dashicons dashicons-download"></span>
            <?php echo esc_html__('Exportar a CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <button class="button" onclick="window.print()">
            <span class="dashicons dashicons-media-document"></span>
            <?php echo esc_html__('Imprimir Informe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const datosEvolucion = <?php echo json_encode($evolucion_datos); ?>;
    const datosMateriales = <?php echo json_encode($distribucion_materiales); ?>;
    const datosDiasSemana = <?php echo json_encode($stats_dias_semana); ?>;

    const diasSemanaLabels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    // Gráfica de evolución
    new Chart(document.getElementById('grafica-evolucion'), {
        type: 'line',
        data: {
            labels: datosEvolucion.map(d => d.periodo),
            datasets: [{
                label: '<?php echo esc_js(__('Kg reciclados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                data: datosEvolucion.map(d => parseFloat(d.kg)),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfica de materiales
    const coloresMateriales = {
        'papel': '#6c757d',
        'plastico': '#ffc107',
        'vidrio': '#17a2b8',
        'organico': '#28a745',
        'electronico': '#dc3545',
        'ropa': '#6f42c1',
        'aceite': '#fd7e14',
        'pilas': '#20c997'
    };

    new Chart(document.getElementById('grafica-materiales'), {
        type: 'doughnut',
        data: {
            labels: datosMateriales.map(m => m.tipo_material.charAt(0).toUpperCase() + m.tipo_material.slice(1)),
            datasets: [{
                data: datosMateriales.map(m => parseFloat(m.total_kg)),
                backgroundColor: datosMateriales.map(m => coloresMateriales[m.tipo_material] || '#6c757d')
            }]
        }
    });

    // Gráfica de días de la semana
    new Chart(document.getElementById('grafica-dias-semana'), {
        type: 'bar',
        data: {
            labels: datosDiasSemana.map(d => diasSemanaLabels[d.dia_semana - 1]),
            datasets: [{
                label: '<?php echo esc_js(__('Kg reciclados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                data: datosDiasSemana.map(d => parseFloat(d.total_kg)),
                backgroundColor: '#0073aa'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

function exportarCSV() {
    window.location.href = '<?php echo admin_url('admin-ajax.php?action=flavor_reciclaje_export_csv&periodo=' . $periodo . '&material=' . $material_filtro); ?>';
}
</script>

<style>
.flavor-filtros-container {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-filtros-container form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.flavor-filtros-container label {
    font-weight: 600;
}

.flavor-stats-principales {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    margin-top: 5px;
}

.flavor-stat-trend.positive {
    color: #28a745;
}

.flavor-stat-trend.negative {
    color: #dc3545;
}

.flavor-widget-impacto {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
}

.flavor-widget-impacto .flavor-widget-header {
    border-bottom-color: rgba(255,255,255,0.2);
}

.flavor-widget-impacto h2,
.flavor-widget-impacto .flavor-widget-subtitle {
    color: #fff;
}

.flavor-impacto-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.flavor-impacto-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
}

.flavor-impacto-icon {
    font-size: 48px;
}

.flavor-impacto-content {
    display: flex;
    flex-direction: column;
}

.flavor-impacto-content strong {
    font-size: 24px;
    margin-bottom: 5px;
}

.flavor-impacto-content span {
    font-size: 13px;
    opacity: 0.9;
}

.flavor-export-section {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-export-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.flavor-export-section .button {
    margin-right: 10px;
}

.flavor-ranking-points {
    font-weight: 700;
    color: #0073aa;
}

@media print {
    .flavor-filtros-container,
    .flavor-export-section {
        display: none;
    }
}
</style>
