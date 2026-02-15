<?php
/**
 * Template: Huella de Uso del Espacio
 *
 * @package FlavorChatIA
 * @subpackage EspaciosComunes
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $huella Datos de huella del espacio
 */

if (!defined('ABSPATH')) {
    exit;
}

$consumos = $huella['consumos'] ?? [];
$total_co2 = $huella['total_co2'] ?? 0;
$total_coste = $huella['total_coste'] ?? 0;
$variacion = $huella['variacion_porcentaje'] ?? 0;
$tendencia = $huella['tendencia'] ?? 'estable';

$iconos_consumo = [
    'electricidad' => 'dashicons-lightbulb',
    'agua' => 'dashicons-water',
    'gas' => 'dashicons-admin-tools',
    'climatizacion' => 'dashicons-cloud',
    'otro' => 'dashicons-marker',
];

$colores_consumo = [
    'electricidad' => '#ffc107',
    'agua' => '#2196f3',
    'gas' => '#ff5722',
    'climatizacion' => '#9c27b0',
    'otro' => '#607d8b',
];
?>

<div class="ec-huella">
    <div class="ec-huella__header">
        <span class="ec-huella__icono">
            <span class="dashicons dashicons-chart-area"></span>
        </span>
        <div>
            <h3 class="ec-huella__titulo"><?php esc_html_e('Huella de Uso', 'flavor-chat-ia'); ?></h3>
            <span class="ec-huella__periodo"><?php echo esc_html(date_i18n('F Y', strtotime($huella['periodo'] . '-01'))); ?></span>
        </div>
    </div>

    <div class="ec-huella__resumen">
        <div class="ec-huella__co2">
            <span class="ec-huella__co2-valor"><?php echo esc_html(number_format($total_co2, 1)); ?></span>
            <span class="ec-huella__co2-unidad">kg CO<sub>2</sub></span>
            <div class="ec-huella__tendencia ec-huella__tendencia--<?php echo esc_attr($tendencia); ?>">
                <span class="dashicons dashicons-arrow-<?php echo $tendencia === 'subiendo' ? 'up' : ($tendencia === 'bajando' ? 'down' : 'right'); ?>-alt2"></span>
                <?php echo esc_html(abs($variacion)); ?>%
            </div>
        </div>
        <div class="ec-huella__coste">
            <span class="ec-huella__coste-label"><?php esc_html_e('Coste estimado', 'flavor-chat-ia'); ?></span>
            <span class="ec-huella__coste-valor"><?php echo esc_html(number_format($total_coste, 2)); ?>€</span>
        </div>
    </div>

    <?php if (!empty($consumos)): ?>
        <div class="ec-huella__desglose">
            <h4><?php esc_html_e('Desglose por tipo', 'flavor-chat-ia'); ?></h4>
            <?php foreach ($consumos as $consumo): ?>
                <?php
                $tipo = $consumo['tipo_consumo'];
                $icono = $iconos_consumo[$tipo] ?? 'dashicons-marker';
                $color = $colores_consumo[$tipo] ?? '#607d8b';
                $porcentaje = $total_co2 > 0 ? ($consumo['total_co2'] / $total_co2) * 100 : 0;
                ?>
                <div class="ec-huella__item">
                    <div class="ec-huella__item-header">
                        <span class="ec-huella__item-icono" style="background: <?php echo esc_attr($color); ?>">
                            <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                        </span>
                        <span class="ec-huella__item-nombre"><?php echo esc_html(ucfirst($tipo)); ?></span>
                        <span class="ec-huella__item-co2"><?php echo esc_html(number_format($consumo['total_co2'], 1)); ?> kg</span>
                    </div>
                    <div class="ec-huella__item-bar">
                        <div class="ec-huella__item-fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($color); ?>"></div>
                    </div>
                    <div class="ec-huella__item-detalle">
                        <span><?php echo esc_html(number_format($consumo['total_cantidad'], 1)); ?> <?php echo $tipo === 'agua' ? 'm³' : 'kWh'; ?></span>
                        <span><?php echo esc_html(number_format($consumo['total_coste'], 2)); ?>€</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="ec-huella__sin-datos">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('No hay datos de consumo registrados para este período.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <div class="ec-huella__consejos">
        <h4><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('Consejos de ahorro', 'flavor-chat-ia'); ?></h4>
        <ul>
            <?php if ($tendencia === 'subiendo'): ?>
                <li><?php esc_html_e('Apaga luces y equipos al terminar de usar el espacio.', 'flavor-chat-ia'); ?></li>
                <li><?php esc_html_e('Ajusta la climatización a temperaturas moderadas (20-22°C).', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <li><?php esc_html_e('Aprovecha la luz natural siempre que sea posible.', 'flavor-chat-ia'); ?></li>
            <li><?php esc_html_e('Reporta fugas de agua o equipos defectuosos.', 'flavor-chat-ia'); ?></li>
        </ul>
    </div>
</div>

<style>
.ec-huella {
    --ec-primary: #4caf50;
    --ec-primary-light: #e8f5e9;
    --ec-warning: #ff9800;
    --ec-danger: #f44336;
    --ec-text: #333;
    --ec-text-light: #666;
    --ec-border: #e0e0e0;
    --ec-radius: 12px;
    background: #fff;
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 1.5rem;
    max-width: 450px;
}

.ec-huella__header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ec-huella__icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--ec-primary);
    border-radius: 50%;
}

.ec-huella__icono .dashicons {
    color: #fff;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.ec-huella__titulo {
    margin: 0;
    font-size: 1.1rem;
}

.ec-huella__periodo {
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-huella__resumen {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--ec-primary-light);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.ec-huella__co2 {
    text-align: center;
}

.ec-huella__co2-valor {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--ec-primary);
    line-height: 1;
}

.ec-huella__co2-unidad {
    font-size: 0.9rem;
    color: var(--ec-text-light);
}

.ec-huella__tendencia {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.ec-huella__tendencia--subiendo {
    background: #ffebee;
    color: var(--ec-danger);
}

.ec-huella__tendencia--bajando {
    background: #e8f5e9;
    color: var(--ec-primary);
}

.ec-huella__tendencia--estable {
    background: #fff3e0;
    color: var(--ec-warning);
}

.ec-huella__coste {
    text-align: right;
}

.ec-huella__coste-label {
    display: block;
    font-size: 0.8rem;
    color: var(--ec-text-light);
}

.ec-huella__coste-valor {
    font-size: 1.25rem;
    font-weight: 600;
}

.ec-huella__desglose h4 {
    font-size: 0.9rem;
    color: var(--ec-text-light);
    margin: 0 0 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ec-huella__item {
    margin-bottom: 1rem;
}

.ec-huella__item-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.ec-huella__item-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
}

.ec-huella__item-icono .dashicons {
    color: #fff;
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.ec-huella__item-nombre {
    flex: 1;
    font-weight: 500;
}

.ec-huella__item-co2 {
    font-size: 0.9rem;
    font-weight: 600;
}

.ec-huella__item-bar {
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.ec-huella__item-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.ec-huella__item-detalle {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: var(--ec-text-light);
    margin-top: 0.25rem;
}

.ec-huella__sin-datos {
    text-align: center;
    padding: 1.5rem;
    color: var(--ec-text-light);
}

.ec-huella__sin-datos .dashicons {
    font-size: 2rem;
    width: 2rem;
    height: 2rem;
    opacity: 0.5;
}

.ec-huella__consejos {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ec-border);
}

.ec-huella__consejos h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
}

.ec-huella__consejos .dashicons {
    color: #ffc107;
}

.ec-huella__consejos ul {
    margin: 0;
    padding-left: 1.5rem;
}

.ec-huella__consejos li {
    font-size: 0.85rem;
    color: var(--ec-text-light);
    margin-bottom: 0.25rem;
}
</style>
