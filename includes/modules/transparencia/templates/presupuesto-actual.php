<?php
/**
 * Template: Presupuesto del Ano Actual
 *
 * Muestra el presupuesto del ejercicio actual con graficos de ejecucion.
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_presupuestos = '';
$tablas_presupuestos_candidatas = [
    $prefijo_tabla . 'presupuestos',
    $prefijo_tabla . 'presupuesto',
];
foreach ($tablas_presupuestos_candidatas as $tabla_candidata) {
    if (Flavor_Platform_Helpers::tabla_existe($tabla_candidata)) {
        $tabla_presupuestos = $tabla_candidata;
        break;
    }
}

// Verificar que la tabla existe
if ($tabla_presupuestos === '') {
    echo '<div class="transparencia-aviso transparencia-aviso--info">';
    echo '<span class="dashicons dashicons-info"></span>';
    echo '<p>' . esc_html__('Todavía no hay presupuesto publicado en esta instalación.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '</div>';
    return;
}

// Obtener ejercicio (por defecto el actual)
$ejercicio = isset($atts['ejercicio']) ? intval($atts['ejercicio']) : date('Y');
$ejercicio_solicitado = isset($_GET['ejercicio']) ? intval($_GET['ejercicio']) : $ejercicio;

// Obtener ejercicios disponibles
$ejercicios_disponibles = $wpdb->get_col("SELECT DISTINCT ejercicio FROM $tabla_presupuestos ORDER BY ejercicio DESC");

// Verificar que el ejercicio existe
if (!in_array($ejercicio_solicitado, $ejercicios_disponibles)) {
    $ejercicio_solicitado = !empty($ejercicios_disponibles) ? $ejercicios_disponibles[0] : date('Y');
}

// Obtener totales de ingresos
$ingresos = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(credito_inicial) as inicial,
        SUM(modificaciones) as modificaciones,
        SUM(credito_definitivo) as definitivo,
        SUM(obligaciones_reconocidas) as recaudado
     FROM $tabla_presupuestos
     WHERE ejercicio = %d AND tipo = 'ingresos'",
    $ejercicio_solicitado
));

// Obtener totales de gastos
$gastos = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(credito_inicial) as inicial,
        SUM(modificaciones) as modificaciones,
        SUM(credito_definitivo) as definitivo,
        SUM(obligaciones_reconocidas) as obligaciones,
        SUM(pagos_realizados) as pagos,
        SUM(pendiente_pago) as pendiente
     FROM $tabla_presupuestos
     WHERE ejercicio = %d AND tipo = 'gastos'",
    $ejercicio_solicitado
));

// Calcular porcentajes de ejecucion
$porcentaje_ingresos = ($ingresos && $ingresos->definitivo > 0)
    ? round(($ingresos->recaudado / $ingresos->definitivo) * 100, 1)
    : 0;

$porcentaje_gastos = ($gastos && $gastos->definitivo > 0)
    ? round(($gastos->obligaciones / $gastos->definitivo) * 100, 1)
    : 0;

$porcentaje_pagos = ($gastos && $gastos->obligaciones > 0)
    ? round(($gastos->pagos / $gastos->obligaciones) * 100, 1)
    : 0;

// Obtener desglose por capitulos de ingresos
$capitulos_ingresos = $wpdb->get_results($wpdb->prepare(
    "SELECT
        capitulo,
        MAX(denominacion) as denominacion,
        SUM(credito_definitivo) as definitivo,
        SUM(obligaciones_reconocidas) as recaudado
     FROM $tabla_presupuestos
     WHERE ejercicio = %d AND tipo = 'ingresos'
     GROUP BY capitulo
     ORDER BY capitulo",
    $ejercicio_solicitado
));

// Obtener desglose por capitulos de gastos
$capitulos_gastos = $wpdb->get_results($wpdb->prepare(
    "SELECT
        capitulo,
        MAX(denominacion) as denominacion,
        SUM(credito_definitivo) as definitivo,
        SUM(obligaciones_reconocidas) as obligaciones,
        SUM(pagos_realizados) as pagos
     FROM $tabla_presupuestos
     WHERE ejercicio = %d AND tipo = 'gastos'
     GROUP BY capitulo
     ORDER BY capitulo",
    $ejercicio_solicitado
));

// Nombres de capitulos presupuestarios
$nombres_capitulos_ingresos = [
    '1' => __('Impuestos directos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '2' => __('Impuestos indirectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '3' => __('Tasas y otros ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '4' => __('Transferencias corrientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '5' => __('Ingresos patrimoniales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '6' => __('Enajenacion inversiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '7' => __('Transferencias de capital', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '8' => __('Activos financieros', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '9' => __('Pasivos financieros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$nombres_capitulos_gastos = [
    '1' => __('Gastos de personal', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '2' => __('Gastos en bienes y servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '3' => __('Gastos financieros', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '4' => __('Transferencias corrientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '5' => __('Fondo de contingencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '6' => __('Inversiones reales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '7' => __('Transferencias de capital', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '8' => __('Activos financieros', FLAVOR_PLATFORM_TEXT_DOMAIN),
    '9' => __('Pasivos financieros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Colores para graficos
$colores_ingresos = ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#065f46', '#047857', '#059669', '#0d9488'];
$colores_gastos = ['#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#dbeafe', '#1e40af', '#1d4ed8', '#2563eb', '#3b82f6'];

// Preparar datos para graficos
$datos_grafico_ingresos = [];
$datos_grafico_gastos = [];

foreach ($capitulos_ingresos as $index => $capitulo) {
    $datos_grafico_ingresos[] = [
        'label' => 'Cap. ' . $capitulo->capitulo,
        'value' => floatval($capitulo->definitivo),
        'color' => $colores_ingresos[$index % count($colores_ingresos)],
    ];
}

foreach ($capitulos_gastos as $index => $capitulo) {
    $datos_grafico_gastos[] = [
        'label' => 'Cap. ' . $capitulo->capitulo,
        'value' => floatval($capitulo->definitivo),
        'color' => $colores_gastos[$index % count($colores_gastos)],
    ];
}
?>

<div class="transparencia-presupuesto">
    <header class="transparencia-presupuesto__header">
        <div class="transparencia-presupuesto__titulo">
            <span class="dashicons dashicons-chart-pie"></span>
            <h2><?php printf(esc_html__('Presupuesto %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_solicitado); ?></h2>
        </div>
        <?php if (count($ejercicios_disponibles) > 1) : ?>
        <form class="transparencia-presupuesto__selector" method="get">
            <label for="ejercicio"><?php esc_html_e('Ejercicio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="ejercicio" id="ejercicio" onchange="this.form.submit()">
                <?php foreach ($ejercicios_disponibles as $ej) : ?>
                <option value="<?php echo esc_attr($ej); ?>" <?php selected($ejercicio_solicitado, $ej); ?>>
                    <?php echo esc_html($ej); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </header>

    <?php if (!$ingresos && !$gastos) : ?>
    <div class="transparencia-empty-state">
        <span class="dashicons dashicons-chart-pie"></span>
        <h3><?php esc_html_e('Sin datos presupuestarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php printf(esc_html__('No hay datos de presupuesto para el ejercicio %d.', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_solicitado); ?></p>
    </div>
    <?php else : ?>

    <!-- Resumen general -->
    <div class="transparencia-presupuesto__resumen">
        <!-- Ingresos -->
        <div class="transparencia-presupuesto__bloque transparencia-presupuesto__bloque--ingresos">
            <div class="transparencia-presupuesto__bloque-header">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Presupuesto de Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="transparencia-presupuesto__kpis">
                <div class="transparencia-kpi-mini">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Inicial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor"><?php echo esc_html(number_format($ingresos->inicial ?: 0, 2, ',', '.')); ?> &euro;</span>
                </div>
                <div class="transparencia-kpi-mini">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Modificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor <?php echo ($ingresos->modificaciones ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                        <?php echo ($ingresos->modificaciones ?? 0) >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($ingresos->modificaciones ?: 0, 2, ',', '.')); ?> &euro;
                    </span>
                </div>
                <div class="transparencia-kpi-mini transparencia-kpi-mini--destacado">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Definitivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor"><?php echo esc_html(number_format($ingresos->definitivo ?: 0, 2, ',', '.')); ?> &euro;</span>
                </div>
            </div>
            <div class="transparencia-ejecucion">
                <div class="transparencia-ejecucion__header">
                    <span><?php esc_html_e('Ejecucion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-ejecucion__porcentaje"><?php echo esc_html($porcentaje_ingresos); ?>%</span>
                </div>
                <div class="transparencia-barra-progreso transparencia-barra-progreso--grande">
                    <div class="transparencia-barra-progreso__fill transparencia-barra-progreso__fill--ingresos" style="width: <?php echo esc_attr(min(100, $porcentaje_ingresos)); ?>%"></div>
                </div>
                <div class="transparencia-ejecucion__detalle">
                    <span><?php esc_html_e('Recaudado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format($ingresos->recaudado ?: 0, 2, ',', '.')); ?> &euro;</span>
                </div>
            </div>
        </div>

        <!-- Gastos -->
        <div class="transparencia-presupuesto__bloque transparencia-presupuesto__bloque--gastos">
            <div class="transparencia-presupuesto__bloque-header">
                <h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Presupuesto de Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="transparencia-presupuesto__kpis">
                <div class="transparencia-kpi-mini">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Inicial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor"><?php echo esc_html(number_format($gastos->inicial ?: 0, 2, ',', '.')); ?> &euro;</span>
                </div>
                <div class="transparencia-kpi-mini">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Modificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor <?php echo ($gastos->modificaciones ?? 0) >= 0 ? 'positivo' : 'negativo'; ?>">
                        <?php echo ($gastos->modificaciones ?? 0) >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($gastos->modificaciones ?: 0, 2, ',', '.')); ?> &euro;
                    </span>
                </div>
                <div class="transparencia-kpi-mini transparencia-kpi-mini--destacado">
                    <span class="transparencia-kpi-mini__label"><?php esc_html_e('Definitivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-kpi-mini__valor"><?php echo esc_html(number_format($gastos->definitivo ?: 0, 2, ',', '.')); ?> &euro;</span>
                </div>
            </div>
            <div class="transparencia-ejecucion">
                <div class="transparencia-ejecucion__header">
                    <span><?php esc_html_e('Ejecucion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="transparencia-ejecucion__porcentaje"><?php echo esc_html($porcentaje_gastos); ?>%</span>
                </div>
                <div class="transparencia-barra-progreso transparencia-barra-progreso--grande">
                    <div class="transparencia-barra-progreso__fill transparencia-barra-progreso__fill--gastos" style="width: <?php echo esc_attr(min(100, $porcentaje_gastos)); ?>%"></div>
                </div>
                <div class="transparencia-ejecucion__detalle">
                    <span><?php esc_html_e('Obligaciones:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format($gastos->obligaciones ?: 0, 2, ',', '.')); ?> &euro;</span>
                    <span><?php esc_html_e('Pagado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format($gastos->pagos ?: 0, 2, ',', '.')); ?> &euro; (<?php echo esc_html($porcentaje_pagos); ?>%)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Graficos -->
    <div class="transparencia-presupuesto__graficos">
        <div class="transparencia-grafico-container">
            <h4><?php esc_html_e('Distribucion de Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <canvas id="grafico-ingresos" width="300" height="300"></canvas>
        </div>
        <div class="transparencia-grafico-container">
            <h4><?php esc_html_e('Distribucion de Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <canvas id="grafico-gastos" width="300" height="300"></canvas>
        </div>
    </div>

    <!-- Desglose por capitulos -->
    <div class="transparencia-presupuesto__desglose">
        <!-- Capitulos de ingresos -->
        <section class="transparencia-capitulos">
            <h4>
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Desglose de Ingresos por Capitulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>
            <?php if (!empty($capitulos_ingresos)) : ?>
            <div class="transparencia-capitulos__lista">
                <?php foreach ($capitulos_ingresos as $index => $capitulo) :
                    $nombre_capitulo = $nombres_capitulos_ingresos[$capitulo->capitulo] ?? $capitulo->denominacion;
                    $porcentaje_cap = $capitulo->definitivo > 0 ? round(($capitulo->recaudado / $capitulo->definitivo) * 100, 1) : 0;
                ?>
                <div class="transparencia-capitulo-item">
                    <div class="transparencia-capitulo-item__color" style="background-color: <?php echo esc_attr($colores_ingresos[$index % count($colores_ingresos)]); ?>"></div>
                    <div class="transparencia-capitulo-item__info">
                        <span class="transparencia-capitulo-item__nombre">
                            <strong>Cap. <?php echo esc_html($capitulo->capitulo); ?></strong> - <?php echo esc_html($nombre_capitulo); ?>
                        </span>
                        <div class="transparencia-capitulo-item__barra">
                            <div class="transparencia-barra-progreso transparencia-barra-progreso--sm">
                                <div class="transparencia-barra-progreso__fill" style="width: <?php echo esc_attr(min(100, $porcentaje_cap)); ?>%; background-color: <?php echo esc_attr($colores_ingresos[$index % count($colores_ingresos)]); ?>"></div>
                            </div>
                            <span class="transparencia-capitulo-item__porcentaje"><?php echo esc_html($porcentaje_cap); ?>%</span>
                        </div>
                    </div>
                    <div class="transparencia-capitulo-item__importes">
                        <span class="transparencia-capitulo-item__definitivo"><?php echo esc_html(number_format($capitulo->definitivo, 0, ',', '.')); ?> &euro;</span>
                        <span class="transparencia-capitulo-item__recaudado"><?php echo esc_html(number_format($capitulo->recaudado, 0, ',', '.')); ?> &euro;</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Capitulos de gastos -->
        <section class="transparencia-capitulos">
            <h4>
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Desglose de Gastos por Capitulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>
            <?php if (!empty($capitulos_gastos)) : ?>
            <div class="transparencia-capitulos__lista">
                <?php foreach ($capitulos_gastos as $index => $capitulo) :
                    $nombre_capitulo = $nombres_capitulos_gastos[$capitulo->capitulo] ?? $capitulo->denominacion;
                    $porcentaje_cap = $capitulo->definitivo > 0 ? round(($capitulo->obligaciones / $capitulo->definitivo) * 100, 1) : 0;
                ?>
                <div class="transparencia-capitulo-item">
                    <div class="transparencia-capitulo-item__color" style="background-color: <?php echo esc_attr($colores_gastos[$index % count($colores_gastos)]); ?>"></div>
                    <div class="transparencia-capitulo-item__info">
                        <span class="transparencia-capitulo-item__nombre">
                            <strong>Cap. <?php echo esc_html($capitulo->capitulo); ?></strong> - <?php echo esc_html($nombre_capitulo); ?>
                        </span>
                        <div class="transparencia-capitulo-item__barra">
                            <div class="transparencia-barra-progreso transparencia-barra-progreso--sm">
                                <div class="transparencia-barra-progreso__fill" style="width: <?php echo esc_attr(min(100, $porcentaje_cap)); ?>%; background-color: <?php echo esc_attr($colores_gastos[$index % count($colores_gastos)]); ?>"></div>
                            </div>
                            <span class="transparencia-capitulo-item__porcentaje"><?php echo esc_html($porcentaje_cap); ?>%</span>
                        </div>
                    </div>
                    <div class="transparencia-capitulo-item__importes">
                        <span class="transparencia-capitulo-item__definitivo"><?php echo esc_html(number_format($capitulo->definitivo, 0, ',', '.')); ?> &euro;</span>
                        <span class="transparencia-capitulo-item__obligaciones"><?php echo esc_html(number_format($capitulo->obligaciones, 0, ',', '.')); ?> &euro;</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si Chart.js esta disponible
    if (typeof Chart === 'undefined') {
        console.log('Chart.js no disponible');
        return;
    }

    // Datos para graficos
    var datosIngresos = <?php echo json_encode($datos_grafico_ingresos); ?>;
    var datosGastos = <?php echo json_encode($datos_grafico_gastos); ?>;

    // Grafico de ingresos
    var ctxIngresos = document.getElementById('grafico-ingresos');
    if (ctxIngresos && datosIngresos.length > 0) {
        new Chart(ctxIngresos, {
            type: 'doughnut',
            data: {
                labels: datosIngresos.map(function(item) { return item.label; }),
                datasets: [{
                    data: datosIngresos.map(function(item) { return item.value; }),
                    backgroundColor: datosIngresos.map(function(item) { return item.color; }),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    // Grafico de gastos
    var ctxGastos = document.getElementById('grafico-gastos');
    if (ctxGastos && datosGastos.length > 0) {
        new Chart(ctxGastos, {
            type: 'doughnut',
            data: {
                labels: datosGastos.map(function(item) { return item.label; }),
                datasets: [{
                    data: datosGastos.map(function(item) { return item.value; }),
                    backgroundColor: datosGastos.map(function(item) { return item.color; }),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.transparencia-presupuesto {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-presupuesto__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.transparencia-presupuesto__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.transparencia-presupuesto__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #3b82f6);
}

.transparencia-presupuesto__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuesto__selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transparencia-presupuesto__selector label {
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-presupuesto__selector select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: 8px;
    font-size: 0.875rem;
}

/* Resumen */
.transparencia-presupuesto__resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.transparencia-presupuesto__bloque {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-presupuesto__bloque--ingresos {
    border-top: 4px solid #10b981;
}

.transparencia-presupuesto__bloque--gastos {
    border-top: 4px solid #3b82f6;
}

.transparencia-presupuesto__bloque-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1.25rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuesto__bloque-header .dashicons {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-presupuesto__kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.transparencia-kpi-mini {
    text-align: center;
    padding: 0.75rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-radius: 8px;
}

.transparencia-kpi-mini--destacado {
    background: var(--flavor-primary-light, #eff6ff);
}

.transparencia-kpi-mini__label {
    display: block;
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
    margin-bottom: 0.25rem;
}

.transparencia-kpi-mini__valor {
    display: block;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-kpi-mini__valor.positivo {
    color: #10b981;
}

.transparencia-kpi-mini__valor.negativo {
    color: #ef4444;
}

/* Ejecucion */
.transparencia-ejecucion__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-ejecucion__porcentaje {
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--flavor-text, #1f2937);
}

.transparencia-barra-progreso--grande {
    height: 12px;
    background: var(--flavor-border, #e5e7eb);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.transparencia-barra-progreso__fill--ingresos {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.transparencia-barra-progreso__fill--gastos {
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
}

.transparencia-ejecucion__detalle {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Graficos */
.transparencia-presupuesto__graficos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.transparencia-grafico-container {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}

.transparencia-grafico-container h4 {
    margin: 0 0 1rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-grafico-container canvas {
    max-width: 100%;
    margin: 0 auto;
}

/* Desglose por capitulos */
.transparencia-presupuesto__desglose {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.transparencia-capitulos {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-capitulos h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-capitulos h4 .dashicons {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-capitulos__lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.transparencia-capitulo-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-radius: 8px;
}

.transparencia-capitulo-item__color {
    width: 8px;
    height: 40px;
    border-radius: 4px;
    flex-shrink: 0;
}

.transparencia-capitulo-item__info {
    flex: 1;
    min-width: 0;
}

.transparencia-capitulo-item__nombre {
    display: block;
    font-size: 0.8125rem;
    color: var(--flavor-text, #374151);
    margin-bottom: 0.375rem;
}

.transparencia-capitulo-item__nombre strong {
    color: var(--flavor-text, #1f2937);
}

.transparencia-capitulo-item__barra {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transparencia-barra-progreso--sm {
    flex: 1;
    height: 4px;
    background: var(--flavor-border, #e5e7eb);
    border-radius: 2px;
    overflow: hidden;
}

.transparencia-capitulo-item__porcentaje {
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
    min-width: 40px;
    text-align: right;
}

.transparencia-capitulo-item__importes {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.125rem;
    flex-shrink: 0;
}

.transparencia-capitulo-item__definitivo {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-capitulo-item__recaudado,
.transparencia-capitulo-item__obligaciones {
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Empty y avisos */
.transparencia-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
}

.transparencia-empty-state .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: var(--flavor-text-light, #9ca3af);
    margin-bottom: 1rem;
}

.transparencia-aviso {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 8px;
}

.transparencia-aviso--info {
    background: #eff6ff;
    color: #1e40af;
}

@media (max-width: 768px) {
    .transparencia-presupuesto__resumen,
    .transparencia-presupuesto__desglose {
        grid-template-columns: 1fr;
    }

    .transparencia-presupuesto__kpis {
        grid-template-columns: 1fr;
    }

    .transparencia-capitulo-item {
        flex-wrap: wrap;
    }

    .transparencia-capitulo-item__importes {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        margin-top: 0.5rem;
        padding-left: calc(8px + 0.75rem);
    }
}
</style>
