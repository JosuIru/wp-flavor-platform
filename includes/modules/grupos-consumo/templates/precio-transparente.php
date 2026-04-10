<?php
/**
 * Template: Precio Justo Visible / Precio Transparente
 *
 * @package FlavorPlatform
 * @subpackage GruposConsumo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $desglose Datos del desglose de precio
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$estilo = $atts['estilo'] ?? 'grafico';
$producto_id = intval($atts['producto_id']);
$producto_nombre = get_the_title($producto_id);

// Calcular porcentajes
$precio_final = floatval($desglose['precio_final'] ?? 0);
$porcentajes = [];

if ($precio_final > 0) {
    $campos = [
        'precio_productor' => ['label' => __('Productor', 'flavor-platform'), 'color' => '#2e7d32'],
        'coste_transporte' => ['label' => __('Transporte', 'flavor-platform'), 'color' => '#1976d2'],
        'coste_gestion'    => ['label' => __('Gestión', 'flavor-platform'), 'color' => '#7b1fa2'],
        'coste_mermas'     => ['label' => __('Mermas', 'flavor-platform'), 'color' => '#f57c00'],
        'aportacion_fondo_social' => ['label' => __('Fondo social', 'flavor-platform'), 'color' => '#c2185b'],
        'iva'              => ['label' => __('IVA', 'flavor-platform'), 'color' => '#616161'],
    ];

    foreach ($campos as $campo => $info) {
        $valor = floatval($desglose[$campo] ?? 0);
        if ($valor > 0) {
            $porcentajes[$campo] = [
                'valor'      => $valor,
                'porcentaje' => round(($valor / $precio_final) * 100, 1),
                'label'      => $info['label'],
                'color'      => $info['color'],
            ];
        }
    }
}

$margen_productor = floatval($desglose['margen_productor_porcentaje'] ?? 0);
$es_estimado = !empty($desglose['es_estimado']);
?>

<?php if ($estilo === 'compacto'): ?>
    <div class="gc-precio-compacto">
        <span class="gc-precio-compacto__final"><?php echo esc_html(number_format($precio_final, 2)); ?>&euro;</span>
        <span class="gc-precio-compacto__productor" title="<?php esc_attr_e('El productor recibe el', 'flavor-platform'); ?> <?php echo esc_attr(number_format($margen_productor, 0)); ?>%">
            <span class="dashicons dashicons-store"></span>
            <?php echo esc_html(number_format($margen_productor, 0)); ?>%
        </span>
    </div>

<?php elseif ($estilo === 'tabla'): ?>
    <div class="gc-precio-tabla">
        <table class="gc-precio-tabla__table">
            <caption>
                <?php printf(esc_html__('Desglose de precio: %s', 'flavor-platform'), esc_html($producto_nombre)); ?>
                <?php if ($es_estimado): ?>
                    <small>(<?php esc_html_e('estimado', 'flavor-platform'); ?>)</small>
                <?php endif; ?>
            </caption>
            <thead>
                <tr>
                    <th><?php esc_html_e('Concepto', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Importe', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('%', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porcentajes as $campo => $datos): ?>
                    <tr>
                        <td>
                            <span class="gc-precio-tabla__color" style="background: <?php echo esc_attr($datos['color']); ?>"></span>
                            <?php echo esc_html($datos['label']); ?>
                        </td>
                        <td><?php echo esc_html(number_format($datos['valor'], 2)); ?>&euro;</td>
                        <td><?php echo esc_html(number_format($datos['porcentaje'], 1)); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th><?php esc_html_e('Total', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html(number_format($precio_final, 2)); ?>&euro;</th>
                    <th>100%</th>
                </tr>
            </tfoot>
        </table>
    </div>

<?php else: ?>
    <div class="gc-precio-grafico">
        <div class="gc-precio-grafico__header">
            <h4 class="gc-precio-grafico__titulo">
                <span class="dashicons dashicons-visibility"></span>
                <?php esc_html_e('Precio Justo Visible', 'flavor-platform'); ?>
            </h4>
            <?php if ($es_estimado): ?>
                <span class="gc-precio-grafico__estimado">
                    <?php esc_html_e('Estimado', 'flavor-platform'); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="gc-precio-grafico__producto">
            <?php echo esc_html($producto_nombre); ?>
        </div>

        <div class="gc-precio-grafico__precio-final">
            <span class="gc-precio-grafico__monto"><?php echo esc_html(number_format($precio_final, 2)); ?>&euro;</span>
        </div>

        <!-- Barra de desglose -->
        <div class="gc-precio-grafico__barra">
            <?php foreach ($porcentajes as $campo => $datos): ?>
                <div
                    class="gc-precio-grafico__segmento"
                    style="width: <?php echo esc_attr($datos['porcentaje']); ?>%; background: <?php echo esc_attr($datos['color']); ?>;"
                    title="<?php echo esc_attr($datos['label'] . ': ' . number_format($datos['porcentaje'], 1) . '%'); ?>"
                ></div>
            <?php endforeach; ?>
        </div>

        <!-- Leyenda -->
        <div class="gc-precio-grafico__leyenda">
            <?php foreach ($porcentajes as $campo => $datos): ?>
                <div class="gc-precio-grafico__leyenda-item">
                    <span class="gc-precio-grafico__leyenda-color" style="background: <?php echo esc_attr($datos['color']); ?>"></span>
                    <span class="gc-precio-grafico__leyenda-label"><?php echo esc_html($datos['label']); ?></span>
                    <span class="gc-precio-grafico__leyenda-valor">
                        <?php echo esc_html(number_format($datos['valor'], 2)); ?>&euro;
                        <small>(<?php echo esc_html(number_format($datos['porcentaje'], 0)); ?>%)</small>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Destacado productor -->
        <div class="gc-precio-grafico__productor">
            <div class="gc-precio-grafico__productor-icon">
                <span class="dashicons dashicons-store"></span>
            </div>
            <div class="gc-precio-grafico__productor-info">
                <strong><?php echo esc_html(number_format($margen_productor, 0)); ?>%</strong>
                <span><?php esc_html_e('llega al productor', 'flavor-platform'); ?></span>
            </div>
            <div class="gc-precio-grafico__productor-barra">
                <div class="gc-precio-grafico__productor-fill" style="width: <?php echo esc_attr(min(100, $margen_productor)); ?>%"></div>
            </div>
        </div>

        <?php if (!empty($desglose['origen_km'])): ?>
            <div class="gc-precio-grafico__origen">
                <span class="dashicons dashicons-location"></span>
                <?php printf(esc_html__('Origen a %d km', 'flavor-platform'), intval($desglose['origen_km'])); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($desglose['certificaciones'])): ?>
            <div class="gc-precio-grafico__certificaciones">
                <span class="dashicons dashicons-awards"></span>
                <?php echo esc_html($desglose['certificaciones']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
/* Compacto */
.gc-precio-compacto {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.gc-precio-compacto__final {
    font-weight: 700;
    font-size: 1.1rem;
}

.gc-precio-compacto__productor {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    cursor: help;
}

.gc-precio-compacto__productor .dashicons {
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

/* Tabla */
.gc-precio-tabla {
    margin: 1rem 0;
}

.gc-precio-tabla__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.gc-precio-tabla__table caption {
    text-align: left;
    font-weight: 600;
    padding-bottom: 0.5rem;
    color: #333;
}

.gc-precio-tabla__table caption small {
    font-weight: normal;
    color: #999;
    font-style: italic;
}

.gc-precio-tabla__table th,
.gc-precio-tabla__table td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.gc-precio-tabla__table thead th {
    background: #f5f5f5;
    font-weight: 600;
}

.gc-precio-tabla__table tfoot th {
    background: #e8f5e9;
}

.gc-precio-tabla__color {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 0.5rem;
    vertical-align: middle;
}

/* Gráfico */
.gc-precio-grafico {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 1rem 0;
}

.gc-precio-grafico__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.gc-precio-grafico__titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1rem;
    color: #333;
}

.gc-precio-grafico__titulo .dashicons {
    color: #2e7d32;
}

.gc-precio-grafico__estimado {
    background: #fff3e0;
    color: #e65100;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
}

.gc-precio-grafico__producto {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.gc-precio-grafico__precio-final {
    text-align: center;
    margin-bottom: 1rem;
}

.gc-precio-grafico__monto {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
}

.gc-precio-grafico__barra {
    display: flex;
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.gc-precio-grafico__segmento {
    height: 100%;
    transition: width 0.5s ease;
}

.gc-precio-grafico__leyenda {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.gc-precio-grafico__leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.gc-precio-grafico__leyenda-color {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    flex-shrink: 0;
}

.gc-precio-grafico__leyenda-label {
    color: #666;
}

.gc-precio-grafico__leyenda-valor {
    margin-left: auto;
    font-weight: 500;
}

.gc-precio-grafico__leyenda-valor small {
    color: #999;
    font-weight: normal;
}

.gc-precio-grafico__productor {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.gc-precio-grafico__productor-icon {
    width: 40px;
    height: 40px;
    background: #2e7d32;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.gc-precio-grafico__productor-icon .dashicons {
    color: #fff;
}

.gc-precio-grafico__productor-info {
    display: flex;
    flex-direction: column;
}

.gc-precio-grafico__productor-info strong {
    font-size: 1.25rem;
    color: #2e7d32;
}

.gc-precio-grafico__productor-info span {
    font-size: 0.85rem;
    color: #555;
}

.gc-precio-grafico__productor-barra {
    flex: 1;
    height: 8px;
    background: #c8e6c9;
    border-radius: 4px;
    overflow: hidden;
}

.gc-precio-grafico__productor-fill {
    height: 100%;
    background: #2e7d32;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.gc-precio-grafico__origen,
.gc-precio-grafico__certificaciones {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.5rem;
}

.gc-precio-grafico__origen .dashicons,
.gc-precio-grafico__certificaciones .dashicons {
    color: #1976d2;
}

@media (max-width: 480px) {
    .gc-precio-grafico__leyenda {
        grid-template-columns: 1fr;
    }

    .gc-precio-grafico__productor {
        flex-wrap: wrap;
    }

    .gc-precio-grafico__productor-barra {
        width: 100%;
        margin-top: 0.5rem;
    }
}
</style>
