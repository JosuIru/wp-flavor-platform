<?php
/**
 * Template: Historico de Presupuestos
 *
 * Muestra el historico de presupuestos de multiples ejercicios.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_presupuestos = $prefijo_tabla . 'presupuestos';
$tabla_documentos = $prefijo_tabla . 'documentos_publicos';

// Verificar que la tabla existe
if (!Flavor_Chat_Helpers::tabla_existe($tabla_presupuestos)) {
    echo '<div class="transparencia-aviso transparencia-aviso--info">';
    echo '<span class="dashicons dashicons-info"></span>';
    echo '<p>' . esc_html__('El sistema de presupuestos no esta disponible en este momento.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Obtener ejercicios disponibles con sus totales
$ejercicios = $wpdb->get_results(
    "SELECT
        ejercicio,
        SUM(CASE WHEN tipo = 'ingresos' THEN credito_inicial ELSE 0 END) as ingresos_inicial,
        SUM(CASE WHEN tipo = 'ingresos' THEN credito_definitivo ELSE 0 END) as ingresos_definitivo,
        SUM(CASE WHEN tipo = 'ingresos' THEN obligaciones_reconocidas ELSE 0 END) as ingresos_recaudados,
        SUM(CASE WHEN tipo = 'gastos' THEN credito_inicial ELSE 0 END) as gastos_inicial,
        SUM(CASE WHEN tipo = 'gastos' THEN credito_definitivo ELSE 0 END) as gastos_definitivo,
        SUM(CASE WHEN tipo = 'gastos' THEN obligaciones_reconocidas ELSE 0 END) as gastos_obligados,
        SUM(CASE WHEN tipo = 'gastos' THEN pagos_realizados ELSE 0 END) as gastos_pagados
     FROM $tabla_presupuestos
     GROUP BY ejercicio
     ORDER BY ejercicio DESC"
);

// Obtener documentos de presupuestos (para descargas)
$documentos_presupuestos = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
    $docs = $wpdb->get_results(
        "SELECT id, titulo, periodo, archivo_url, fecha_publicacion
         FROM $tabla_documentos
         WHERE categoria = 'presupuestos' AND estado = 'publicado'
         ORDER BY periodo DESC, fecha_publicacion DESC"
    );
    foreach ($docs as $documento) {
        $anio = preg_match('/\d{4}/', $documento->periodo ?? '', $matches) ? $matches[0] : null;
        if ($anio) {
            if (!isset($documentos_presupuestos[$anio])) {
                $documentos_presupuestos[$anio] = [];
            }
            $documentos_presupuestos[$anio][] = $documento;
        }
    }
}

// Preparar datos para grafico de evolucion
$datos_evolucion = [];
foreach (array_reverse($ejercicios) as $ejercicio_data) {
    $datos_evolucion[] = [
        'ejercicio' => $ejercicio_data->ejercicio,
        'ingresos' => floatval($ejercicio_data->ingresos_definitivo),
        'gastos' => floatval($ejercicio_data->gastos_definitivo),
    ];
}

$ejercicio_actual = date('Y');
?>

<div class="transparencia-presupuestos">
    <header class="transparencia-presupuestos__header">
        <div class="transparencia-presupuestos__titulo">
            <span class="dashicons dashicons-chart-area"></span>
            <h2><?php esc_html_e('Historico de Presupuestos', 'flavor-chat-ia'); ?></h2>
        </div>
        <p class="transparencia-presupuestos__descripcion">
            <?php esc_html_e('Consulta los presupuestos de ejercicios anteriores y su evolucion.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <?php if (empty($ejercicios)) : ?>
    <div class="transparencia-empty-state">
        <span class="dashicons dashicons-chart-pie"></span>
        <h3><?php esc_html_e('Sin datos presupuestarios', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('No hay presupuestos registrados en el sistema.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php else : ?>

    <!-- Grafico de evolucion -->
    <?php if (count($ejercicios) > 1) : ?>
    <section class="transparencia-presupuestos__evolucion">
        <h3>
            <span class="dashicons dashicons-chart-line"></span>
            <?php esc_html_e('Evolucion Presupuestaria', 'flavor-chat-ia'); ?>
        </h3>
        <div class="transparencia-grafico-evolucion-container">
            <canvas id="grafico-evolucion-presupuestos" height="300"></canvas>
        </div>
    </section>
    <?php endif; ?>

    <!-- Lista de ejercicios -->
    <section class="transparencia-presupuestos__lista">
        <h3>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('Presupuestos por Ejercicio', 'flavor-chat-ia'); ?>
        </h3>

        <div class="transparencia-ejercicios-grid">
            <?php foreach ($ejercicios as $ejercicio_data) :
                $es_actual = ($ejercicio_data->ejercicio == $ejercicio_actual);
                $porcentaje_ingresos = $ejercicio_data->ingresos_definitivo > 0
                    ? round(($ejercicio_data->ingresos_recaudados / $ejercicio_data->ingresos_definitivo) * 100, 1)
                    : 0;
                $porcentaje_gastos = $ejercicio_data->gastos_definitivo > 0
                    ? round(($ejercicio_data->gastos_obligados / $ejercicio_data->gastos_definitivo) * 100, 1)
                    : 0;
                $resultado = $ejercicio_data->ingresos_recaudados - $ejercicio_data->gastos_obligados;
                $documentos_ejercicio = $documentos_presupuestos[$ejercicio_data->ejercicio] ?? [];
            ?>
            <article class="transparencia-ejercicio-card <?php echo $es_actual ? 'transparencia-ejercicio-card--actual' : ''; ?>">
                <header class="transparencia-ejercicio-card__header">
                    <h4>
                        <?php echo esc_html($ejercicio_data->ejercicio); ?>
                        <?php if ($es_actual) : ?>
                        <span class="transparencia-badge transparencia-badge--actual"><?php esc_html_e('Actual', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </h4>
                </header>

                <div class="transparencia-ejercicio-card__contenido">
                    <!-- Ingresos -->
                    <div class="transparencia-ejercicio-card__seccion">
                        <div class="transparencia-ejercicio-card__seccion-header">
                            <span class="transparencia-ejercicio-card__seccion-titulo"><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></span>
                            <span class="transparencia-ejercicio-card__porcentaje"><?php echo esc_html($porcentaje_ingresos); ?>%</span>
                        </div>
                        <div class="transparencia-barra-progreso transparencia-barra-progreso--sm">
                            <div class="transparencia-barra-progreso__fill transparencia-barra-progreso__fill--ingresos" style="width: <?php echo esc_attr(min(100, $porcentaje_ingresos)); ?>%"></div>
                        </div>
                        <div class="transparencia-ejercicio-card__valores">
                            <span class="transparencia-ejercicio-card__definitivo">
                                <?php echo esc_html(number_format($ejercicio_data->ingresos_definitivo, 0, ',', '.')); ?> &euro;
                            </span>
                            <span class="transparencia-ejercicio-card__ejecutado">
                                <?php printf(esc_html__('Recaudado: %s EUR', 'flavor-chat-ia'), number_format($ejercicio_data->ingresos_recaudados, 0, ',', '.')); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Gastos -->
                    <div class="transparencia-ejercicio-card__seccion">
                        <div class="transparencia-ejercicio-card__seccion-header">
                            <span class="transparencia-ejercicio-card__seccion-titulo"><?php esc_html_e('Gastos', 'flavor-chat-ia'); ?></span>
                            <span class="transparencia-ejercicio-card__porcentaje"><?php echo esc_html($porcentaje_gastos); ?>%</span>
                        </div>
                        <div class="transparencia-barra-progreso transparencia-barra-progreso--sm">
                            <div class="transparencia-barra-progreso__fill transparencia-barra-progreso__fill--gastos" style="width: <?php echo esc_attr(min(100, $porcentaje_gastos)); ?>%"></div>
                        </div>
                        <div class="transparencia-ejercicio-card__valores">
                            <span class="transparencia-ejercicio-card__definitivo">
                                <?php echo esc_html(number_format($ejercicio_data->gastos_definitivo, 0, ',', '.')); ?> &euro;
                            </span>
                            <span class="transparencia-ejercicio-card__ejecutado">
                                <?php printf(esc_html__('Obligado: %s EUR', 'flavor-chat-ia'), number_format($ejercicio_data->gastos_obligados, 0, ',', '.')); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Resultado -->
                    <div class="transparencia-ejercicio-card__resultado <?php echo $resultado >= 0 ? 'positivo' : 'negativo'; ?>">
                        <span class="transparencia-ejercicio-card__resultado-label">
                            <?php echo $resultado >= 0 ? esc_html__('Superavit', 'flavor-chat-ia') : esc_html__('Deficit', 'flavor-chat-ia'); ?>
                        </span>
                        <span class="transparencia-ejercicio-card__resultado-valor">
                            <?php echo $resultado >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($resultado, 0, ',', '.')); ?> &euro;
                        </span>
                    </div>
                </div>

                <footer class="transparencia-ejercicio-card__footer">
                    <a href="<?php echo esc_url(add_query_arg('ejercicio', $ejercicio_data->ejercicio, home_url('/transparencia/presupuesto/'))); ?>" class="transparencia-btn transparencia-btn--outline transparencia-btn--sm">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Ver detalle', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if (!empty($documentos_ejercicio)) : ?>
                    <div class="transparencia-ejercicio-card__documentos">
                        <?php foreach (array_slice($documentos_ejercicio, 0, 2) as $documento) : ?>
                        <a href="<?php echo esc_url($documento->archivo_url); ?>" class="transparencia-btn transparencia-btn--sm transparencia-btn--primary" target="_blank" title="<?php echo esc_attr($documento->titulo); ?>">
                            <span class="dashicons dashicons-download"></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </footer>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Tabla comparativa -->
    <?php if (count($ejercicios) > 1) : ?>
    <section class="transparencia-presupuestos__comparativa">
        <h3>
            <span class="dashicons dashicons-editor-table"></span>
            <?php esc_html_e('Tabla Comparativa', 'flavor-chat-ia'); ?>
        </h3>
        <div class="transparencia-tabla-wrapper">
            <table class="transparencia-tabla">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ejercicio', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php esc_html_e('Presup. Ingresos', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php esc_html_e('Recaudado', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php esc_html_e('Presup. Gastos', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php esc_html_e('Obligado', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php esc_html_e('Resultado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejercicios as $ejercicio_data) :
                        $resultado = $ejercicio_data->ingresos_recaudados - $ejercicio_data->gastos_obligados;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($ejercicio_data->ejercicio); ?></strong>
                            <?php if ($ejercicio_data->ejercicio == $ejercicio_actual) : ?>
                            <span class="transparencia-badge transparencia-badge--sm"><?php esc_html_e('Actual', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo esc_html(number_format($ejercicio_data->ingresos_definitivo, 0, ',', '.')); ?> &euro;</td>
                        <td class="text-right"><?php echo esc_html(number_format($ejercicio_data->ingresos_recaudados, 0, ',', '.')); ?> &euro;</td>
                        <td class="text-right"><?php echo esc_html(number_format($ejercicio_data->gastos_definitivo, 0, ',', '.')); ?> &euro;</td>
                        <td class="text-right"><?php echo esc_html(number_format($ejercicio_data->gastos_obligados, 0, ',', '.')); ?> &euro;</td>
                        <td class="text-right <?php echo $resultado >= 0 ? 'positivo' : 'negativo'; ?>">
                            <?php echo $resultado >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($resultado, 0, ',', '.')); ?> &euro;
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        return;
    }

    var datosEvolucion = <?php echo json_encode($datos_evolucion); ?>;

    if (datosEvolucion.length < 2) {
        return;
    }

    var ctxEvolucion = document.getElementById('grafico-evolucion-presupuestos');
    if (ctxEvolucion) {
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(function(item) { return item.ejercicio; }),
                datasets: [
                    {
                        label: '<?php esc_attr_e('Ingresos', 'flavor-chat-ia'); ?>',
                        data: datosEvolucion.map(function(item) { return item.ingresos; }),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php esc_attr_e('Gastos', 'flavor-chat-ia'); ?>',
                        data: datosEvolucion.map(function(item) { return item.gastos; }),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('es-ES') + ' \u20AC';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.transparencia-presupuestos {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-presupuestos__header {
    margin-bottom: 2rem;
}

.transparencia-presupuestos__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.transparencia-presupuestos__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #3b82f6);
}

.transparencia-presupuestos__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuestos__descripcion {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

/* Grafico de evolucion */
.transparencia-presupuestos__evolucion {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-presupuestos__evolucion h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuestos__evolucion h3 .dashicons {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-grafico-evolucion-container {
    height: 300px;
}

/* Lista de ejercicios */
.transparencia-presupuestos__lista {
    margin-bottom: 2rem;
}

.transparencia-presupuestos__lista h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuestos__lista h3 .dashicons {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-ejercicios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
}

/* Card de ejercicio */
.transparencia-ejercicio-card {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.transparencia-ejercicio-card--actual {
    border: 2px solid var(--flavor-primary, #3b82f6);
}

.transparencia-ejercicio-card__header {
    padding: 1rem 1.25rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-ejercicio-card__header h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-badge--actual {
    background: var(--flavor-primary, #3b82f6);
    color: #fff;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
}

.transparencia-ejercicio-card__contenido {
    padding: 1.25rem;
}

.transparencia-ejercicio-card__seccion {
    margin-bottom: 1rem;
}

.transparencia-ejercicio-card__seccion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.375rem;
}

.transparencia-ejercicio-card__seccion-titulo {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--flavor-text, #374151);
}

.transparencia-ejercicio-card__porcentaje {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-barra-progreso--sm {
    height: 6px;
    background: var(--flavor-border, #e5e7eb);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.375rem;
}

.transparencia-barra-progreso__fill--ingresos {
    background: #10b981;
}

.transparencia-barra-progreso__fill--gastos {
    background: #3b82f6;
}

.transparencia-ejercicio-card__valores {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.transparencia-ejercicio-card__definitivo {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-ejercicio-card__ejecutado {
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
}

/* Resultado */
.transparencia-ejercicio-card__resultado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.transparencia-ejercicio-card__resultado.positivo {
    background: #d1fae5;
}

.transparencia-ejercicio-card__resultado.negativo {
    background: #fee2e2;
}

.transparencia-ejercicio-card__resultado-label {
    font-size: 0.8125rem;
    font-weight: 500;
}

.transparencia-ejercicio-card__resultado.positivo .transparencia-ejercicio-card__resultado-label {
    color: #047857;
}

.transparencia-ejercicio-card__resultado.negativo .transparencia-ejercicio-card__resultado-label {
    color: #dc2626;
}

.transparencia-ejercicio-card__resultado-valor {
    font-weight: 700;
}

.transparencia-ejercicio-card__resultado.positivo .transparencia-ejercicio-card__resultado-valor {
    color: #047857;
}

.transparencia-ejercicio-card__resultado.negativo .transparencia-ejercicio-card__resultado-valor {
    color: #dc2626;
}

/* Footer */
.transparencia-ejercicio-card__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-ejercicio-card__documentos {
    display: flex;
    gap: 0.5rem;
}

/* Buttons */
.transparencia-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.8125rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.transparencia-btn--primary {
    background: var(--flavor-primary, #3b82f6);
    color: #fff;
}

.transparencia-btn--outline {
    background: transparent;
    color: var(--flavor-primary, #3b82f6);
    border: 1px solid var(--flavor-primary, #3b82f6);
}

.transparencia-btn--sm {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
}

.transparencia-btn .dashicons {
    font-size: 0.875rem;
    width: 0.875rem;
    height: 0.875rem;
}

/* Tabla comparativa */
.transparencia-presupuestos__comparativa {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-presupuestos__comparativa h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuestos__comparativa h3 .dashicons {
    color: var(--flavor-text-light, #6b7280);
}

.transparencia-tabla-wrapper {
    overflow-x: auto;
}

.transparencia-tabla {
    width: 100%;
    border-collapse: collapse;
}

.transparencia-tabla th,
.transparencia-tabla td {
    padding: 0.875rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-tabla th {
    background: var(--flavor-bg-light, #f9fafb);
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--flavor-text-light, #6b7280);
    text-transform: uppercase;
}

.transparencia-tabla .text-right {
    text-align: right;
}

.transparencia-tabla td.positivo {
    color: #047857;
    font-weight: 600;
}

.transparencia-tabla td.negativo {
    color: #dc2626;
    font-weight: 600;
}

.transparencia-badge--sm {
    background: var(--flavor-primary-light, #dbeafe);
    color: var(--flavor-primary, #3b82f6);
    padding: 0.125rem 0.375rem;
    border-radius: 10px;
    font-size: 0.625rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

/* Empty state */
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
    .transparencia-ejercicios-grid {
        grid-template-columns: 1fr;
    }

    .transparencia-ejercicio-card__footer {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>
