<?php
/**
 * Template: Resumen Compacto del Presupuesto
 *
 * Widget compacto para mostrar un resumen del presupuesto actual.
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

// Verificar que la tabla existe
if (!Flavor_Chat_Helpers::tabla_existe($tabla_presupuestos)) {
    return;
}

// Obtener ejercicio
$ejercicio = isset($atts['ejercicio']) ? intval($atts['ejercicio']) : date('Y');

// Obtener totales
$totales = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(CASE WHEN tipo = 'ingresos' THEN credito_definitivo ELSE 0 END) as presupuesto_ingresos,
        SUM(CASE WHEN tipo = 'ingresos' THEN obligaciones_reconocidas ELSE 0 END) as ingresos_recaudados,
        SUM(CASE WHEN tipo = 'gastos' THEN credito_definitivo ELSE 0 END) as presupuesto_gastos,
        SUM(CASE WHEN tipo = 'gastos' THEN obligaciones_reconocidas ELSE 0 END) as gastos_obligados,
        SUM(CASE WHEN tipo = 'gastos' THEN pagos_realizados ELSE 0 END) as gastos_pagados
     FROM $tabla_presupuestos
     WHERE ejercicio = %d",
    $ejercicio
));

if (!$totales || ($totales->presupuesto_ingresos == 0 && $totales->presupuesto_gastos == 0)) {
    return;
}

// Calcular porcentajes
$porcentaje_ingresos = $totales->presupuesto_ingresos > 0
    ? round(($totales->ingresos_recaudados / $totales->presupuesto_ingresos) * 100, 1)
    : 0;

$porcentaje_gastos = $totales->presupuesto_gastos > 0
    ? round(($totales->gastos_obligados / $totales->presupuesto_gastos) * 100, 1)
    : 0;

// Superavit/deficit previsto
$resultado_previsto = $totales->presupuesto_ingresos - $totales->presupuesto_gastos;
$resultado_actual = $totales->ingresos_recaudados - $totales->gastos_obligados;

// Estilo compacto o expandido
$estilo = isset($atts['estilo']) ? sanitize_text_field($atts['estilo']) : 'compacto';
$mostrar_enlace = isset($atts['enlace']) ? filter_var($atts['enlace'], FILTER_VALIDATE_BOOLEAN) : true;
?>

<div class="transparencia-presupuesto-resumen transparencia-presupuesto-resumen--<?php echo esc_attr($estilo); ?>">
    <header class="transparencia-presupuesto-resumen__header">
        <h3>
            <span class="dashicons dashicons-chart-pie"></span>
            <?php printf(esc_html__('Presupuesto %d', 'flavor-chat-ia'), $ejercicio); ?>
        </h3>
    </header>

    <div class="transparencia-presupuesto-resumen__contenido">
        <!-- Ingresos -->
        <div class="transparencia-presupuesto-resumen__item transparencia-presupuesto-resumen__item--ingresos">
            <div class="transparencia-presupuesto-resumen__item-header">
                <span class="transparencia-presupuesto-resumen__label"><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></span>
                <span class="transparencia-presupuesto-resumen__porcentaje"><?php echo esc_html($porcentaje_ingresos); ?>%</span>
            </div>
            <div class="transparencia-presupuesto-resumen__barra">
                <div class="transparencia-presupuesto-resumen__barra-fill transparencia-presupuesto-resumen__barra-fill--ingresos" style="width: <?php echo esc_attr(min(100, $porcentaje_ingresos)); ?>%"></div>
            </div>
            <div class="transparencia-presupuesto-resumen__valores">
                <span class="transparencia-presupuesto-resumen__recaudado">
                    <?php echo esc_html(number_format($totales->ingresos_recaudados, 0, ',', '.')); ?> &euro;
                </span>
                <span class="transparencia-presupuesto-resumen__total">
                    / <?php echo esc_html(number_format($totales->presupuesto_ingresos, 0, ',', '.')); ?> &euro;
                </span>
            </div>
        </div>

        <!-- Gastos -->
        <div class="transparencia-presupuesto-resumen__item transparencia-presupuesto-resumen__item--gastos">
            <div class="transparencia-presupuesto-resumen__item-header">
                <span class="transparencia-presupuesto-resumen__label"><?php esc_html_e('Gastos', 'flavor-chat-ia'); ?></span>
                <span class="transparencia-presupuesto-resumen__porcentaje"><?php echo esc_html($porcentaje_gastos); ?>%</span>
            </div>
            <div class="transparencia-presupuesto-resumen__barra">
                <div class="transparencia-presupuesto-resumen__barra-fill transparencia-presupuesto-resumen__barra-fill--gastos" style="width: <?php echo esc_attr(min(100, $porcentaje_gastos)); ?>%"></div>
            </div>
            <div class="transparencia-presupuesto-resumen__valores">
                <span class="transparencia-presupuesto-resumen__obligado">
                    <?php echo esc_html(number_format($totales->gastos_obligados, 0, ',', '.')); ?> &euro;
                </span>
                <span class="transparencia-presupuesto-resumen__total">
                    / <?php echo esc_html(number_format($totales->presupuesto_gastos, 0, ',', '.')); ?> &euro;
                </span>
            </div>
        </div>

        <!-- Resultado -->
        <?php if ($estilo !== 'mini') : ?>
        <div class="transparencia-presupuesto-resumen__resultado <?php echo $resultado_actual >= 0 ? 'positivo' : 'negativo'; ?>">
            <span class="transparencia-presupuesto-resumen__resultado-label">
                <?php echo $resultado_actual >= 0 ? esc_html__('Superavit actual', 'flavor-chat-ia') : esc_html__('Deficit actual', 'flavor-chat-ia'); ?>
            </span>
            <span class="transparencia-presupuesto-resumen__resultado-valor">
                <?php echo $resultado_actual >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($resultado_actual, 0, ',', '.')); ?> &euro;
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($mostrar_enlace) : ?>
    <footer class="transparencia-presupuesto-resumen__footer">
        <a href="<?php echo esc_url(home_url('/transparencia/presupuestos/')); ?>" class="transparencia-presupuesto-resumen__enlace">
            <?php esc_html_e('Ver presupuesto completo', 'flavor-chat-ia'); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </footer>
    <?php endif; ?>
</div>

<style>
.transparencia-presupuesto-resumen {
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.transparencia-presupuesto-resumen__header {
    margin-bottom: 1rem;
}

.transparencia-presupuesto-resumen__header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuesto-resumen__header .dashicons {
    font-size: 1.25rem;
    width: 1.25rem;
    height: 1.25rem;
    color: var(--flavor-primary, #3b82f6);
}

/* Items de presupuesto */
.transparencia-presupuesto-resumen__contenido {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.transparencia-presupuesto-resumen__item {
    padding: 0.75rem;
    background: var(--flavor-bg-light, #f9fafb);
    border-radius: 8px;
}

.transparencia-presupuesto-resumen__item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.transparencia-presupuesto-resumen__label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--flavor-text, #374151);
}

.transparencia-presupuesto-resumen__porcentaje {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

/* Barra de progreso */
.transparencia-presupuesto-resumen__barra {
    height: 8px;
    background: var(--flavor-border, #e5e7eb);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.transparencia-presupuesto-resumen__barra-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.transparencia-presupuesto-resumen__barra-fill--ingresos {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.transparencia-presupuesto-resumen__barra-fill--gastos {
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
}

/* Valores */
.transparencia-presupuesto-resumen__valores {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
    font-size: 0.8125rem;
}

.transparencia-presupuesto-resumen__recaudado,
.transparencia-presupuesto-resumen__obligado {
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

.transparencia-presupuesto-resumen__total {
    color: var(--flavor-text-light, #6b7280);
}

/* Resultado */
.transparencia-presupuesto-resumen__resultado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.transparencia-presupuesto-resumen__resultado.positivo {
    background: #d1fae5;
}

.transparencia-presupuesto-resumen__resultado.negativo {
    background: #fee2e2;
}

.transparencia-presupuesto-resumen__resultado-label {
    font-size: 0.8125rem;
    color: var(--flavor-text, #374151);
}

.transparencia-presupuesto-resumen__resultado-valor {
    font-weight: 700;
    font-size: 1rem;
}

.transparencia-presupuesto-resumen__resultado.positivo .transparencia-presupuesto-resumen__resultado-valor {
    color: #047857;
}

.transparencia-presupuesto-resumen__resultado.negativo .transparencia-presupuesto-resumen__resultado-valor {
    color: #dc2626;
}

/* Footer con enlace */
.transparencia-presupuesto-resumen__footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

.transparencia-presupuesto-resumen__enlace {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    color: var(--flavor-primary, #3b82f6);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s;
}

.transparencia-presupuesto-resumen__enlace:hover {
    color: var(--flavor-primary-dark, #2563eb);
}

.transparencia-presupuesto-resumen__enlace .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

/* Variante mini */
.transparencia-presupuesto-resumen--mini {
    padding: 1rem;
}

.transparencia-presupuesto-resumen--mini .transparencia-presupuesto-resumen__header h3 {
    font-size: 0.875rem;
}

.transparencia-presupuesto-resumen--mini .transparencia-presupuesto-resumen__contenido {
    gap: 0.75rem;
}

.transparencia-presupuesto-resumen--mini .transparencia-presupuesto-resumen__item {
    padding: 0.5rem;
}

.transparencia-presupuesto-resumen--mini .transparencia-presupuesto-resumen__barra {
    height: 6px;
}

/* Variante expandido */
.transparencia-presupuesto-resumen--expandido {
    padding: 1.5rem;
}

.transparencia-presupuesto-resumen--expandido .transparencia-presupuesto-resumen__header h3 {
    font-size: 1.125rem;
}

.transparencia-presupuesto-resumen--expandido .transparencia-presupuesto-resumen__item {
    padding: 1rem;
}

.transparencia-presupuesto-resumen--expandido .transparencia-presupuesto-resumen__barra {
    height: 10px;
}

.transparencia-presupuesto-resumen--expandido .transparencia-presupuesto-resumen__valores {
    font-size: 0.9375rem;
}
</style>
