<?php
/**
 * Template: Huella de Carbono del Evento
 * @var object $evento Datos del evento
 * @var array $huella Datos de huella de carbono
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;

$co2_total = $huella['co2_total'] ?? 0;
$asistentes = $huella['asistentes_estimados'] ?? 1;
$co2_per_capita = $asistentes > 0 ? $co2_total / $asistentes : 0;

// Categorías de emisiones
$categorias = [
    'transporte' => [
        'valor' => $huella['co2_transporte'] ?? 0,
        'icono' => 'dashicons-car',
        'color' => '#2196f3',
        'label' => __('Transporte', 'flavor-chat-ia'),
    ],
    'energia' => [
        'valor' => $huella['co2_energia'] ?? 0,
        'icono' => 'dashicons-lightbulb',
        'color' => '#ff9800',
        'label' => __('Energía', 'flavor-chat-ia'),
    ],
    'materiales' => [
        'valor' => $huella['co2_materiales'] ?? 0,
        'icono' => 'dashicons-archive',
        'color' => '#9c27b0',
        'label' => __('Materiales', 'flavor-chat-ia'),
    ],
    'catering' => [
        'valor' => $huella['co2_catering'] ?? 0,
        'icono' => 'dashicons-food',
        'color' => '#4caf50',
        'label' => __('Catering', 'flavor-chat-ia'),
    ],
];

// Calcular porcentajes
$max_valor = max(array_column($categorias, 'valor'));
foreach ($categorias as $key => $cat) {
    $categorias[$key]['porcentaje'] = $max_valor > 0 ? ($cat['valor'] / $max_valor) * 100 : 0;
}

// Compensaciones
$compensaciones = $huella['compensaciones'] ?? [];
$co2_compensado = array_sum(array_column($compensaciones, 'kg_compensados'));
$co2_neto = max(0, $co2_total - $co2_compensado);
?>
<div class="ev-huella" data-evento="<?php echo esc_attr($evento->id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="ev-huella__header">
        <span class="ev-huella__icono">
            <span class="dashicons dashicons-cloud"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Huella de Carbono', 'flavor-chat-ia'); ?></h3>
            <span class="ev-huella__evento"><?php echo esc_html($evento->titulo); ?></span>
        </div>
    </div>

    <!-- Resumen principal -->
    <div class="ev-huella__resumen">
        <div class="ev-huella__total">
            <span class="ev-huella__total-valor"><?php echo esc_html(number_format($co2_total, 1)); ?></span>
            <span class="ev-huella__total-unidad">kg CO<sub>2</sub></span>
            <span class="ev-huella__total-label"><?php esc_html_e('Emisiones totales', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="ev-huella__per-capita">
            <span class="ev-huella__pc-valor"><?php echo esc_html(number_format($co2_per_capita, 2)); ?></span>
            <span class="ev-huella__pc-label"><?php esc_html_e('kg CO₂ por asistente', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Desglose por categorías -->
    <div class="ev-huella__categorias">
        <h4><?php esc_html_e('Desglose de emisiones', 'flavor-chat-ia'); ?></h4>
        <?php foreach ($categorias as $key => $cat): ?>
            <div class="ev-huella__categoria">
                <div class="ev-huella__cat-header">
                    <span class="ev-huella__cat-icono" style="background: <?php echo esc_attr($cat['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($cat['icono']); ?>"></span>
                    </span>
                    <span class="ev-huella__cat-label"><?php echo esc_html($cat['label']); ?></span>
                    <span class="ev-huella__cat-valor"><?php echo esc_html(number_format($cat['valor'], 1)); ?> kg</span>
                </div>
                <div class="ev-huella__cat-bar">
                    <div class="ev-huella__cat-fill" style="width: <?php echo esc_attr($cat['porcentaje']); ?>%; background: <?php echo esc_attr($cat['color']); ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Compensaciones -->
    <div class="ev-huella__compensaciones">
        <h4>
            <span class="dashicons dashicons-palmtree"></span>
            <?php esc_html_e('Compensaciones', 'flavor-chat-ia'); ?>
        </h4>
        <?php if (!empty($compensaciones)): ?>
            <div class="ev-huella__comp-lista">
                <?php foreach ($compensaciones as $comp): ?>
                    <div class="ev-huella__comp-item">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span><?php echo esc_html($comp['descripcion']); ?></span>
                        <span class="ev-huella__comp-kg">-<?php echo esc_html(number_format($comp['kg_compensados'], 1)); ?> kg</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="ev-huella__balance">
            <div class="ev-huella__balance-item">
                <span class="ev-huella__balance-label"><?php esc_html_e('Emitido', 'flavor-chat-ia'); ?></span>
                <span class="ev-huella__balance-valor ev-huella__balance-valor--negativo"><?php echo esc_html(number_format($co2_total, 1)); ?> kg</span>
            </div>
            <div class="ev-huella__balance-item">
                <span class="ev-huella__balance-label"><?php esc_html_e('Compensado', 'flavor-chat-ia'); ?></span>
                <span class="ev-huella__balance-valor ev-huella__balance-valor--positivo">-<?php echo esc_html(number_format($co2_compensado, 1)); ?> kg</span>
            </div>
            <div class="ev-huella__balance-item ev-huella__balance-item--total">
                <span class="ev-huella__balance-label"><?php esc_html_e('Huella neta', 'flavor-chat-ia'); ?></span>
                <span class="ev-huella__balance-valor"><?php echo esc_html(number_format($co2_neto, 1)); ?> kg</span>
            </div>
        </div>
    </div>

    <!-- Equivalencias visuales -->
    <div class="ev-huella__equivalencias">
        <h4><?php esc_html_e('¿Qué significa?', 'flavor-chat-ia'); ?></h4>
        <div class="ev-huella__equiv-grid">
            <?php
            $km_coche = $co2_neto / 0.12; // ~120g CO2/km
            $arboles_necesarios = $co2_neto / 21; // 21kg CO2/árbol/año
            $horas_led = $co2_neto / 0.01; // ~10g CO2/hora bombilla LED
            ?>
            <div class="ev-huella__equiv-item">
                <span class="dashicons dashicons-car"></span>
                <span class="ev-huella__equiv-valor"><?php echo esc_html(number_format($km_coche, 0)); ?></span>
                <span class="ev-huella__equiv-label"><?php esc_html_e('km en coche', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="ev-huella__equiv-item">
                <span class="dashicons dashicons-palmtree"></span>
                <span class="ev-huella__equiv-valor"><?php echo esc_html(number_format($arboles_necesarios, 1)); ?></span>
                <span class="ev-huella__equiv-label"><?php esc_html_e('árboles/año', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="ev-huella__equiv-item">
                <span class="dashicons dashicons-lightbulb"></span>
                <span class="ev-huella__equiv-valor"><?php echo esc_html(number_format($horas_led, 0)); ?></span>
                <span class="ev-huella__equiv-label"><?php esc_html_e('h bombilla LED', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Tips para reducir -->
    <div class="ev-huella__tips">
        <h4><?php esc_html_e('Consejos para reducir', 'flavor-chat-ia'); ?></h4>
        <ul>
            <li>
                <span class="dashicons dashicons-car"></span>
                <?php esc_html_e('Fomenta el transporte compartido o público', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-food"></span>
                <?php esc_html_e('Opta por catering local y de temporada', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-archive"></span>
                <?php esc_html_e('Usa materiales reutilizables o reciclados', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-video-alt3"></span>
                <?php esc_html_e('Considera formato híbrido presencial/online', 'flavor-chat-ia'); ?>
            </li>
        </ul>
    </div>
</div>
<style>
.ev-huella{--ev-primary:#4caf50;--ev-primary-light:#e8f5e9;--ev-danger:#f44336;--ev-text:#333;--ev-text-light:#666;--ev-border:#e0e0e0;background:#fff;border:1px solid var(--ev-border);border-radius:12px;padding:1.5rem}
.ev-huella__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.ev-huella__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--ev-primary);border-radius:50%}
.ev-huella__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.ev-huella__header h3{margin:0;font-size:1.1rem}
.ev-huella__evento{font-size:.85rem;color:var(--ev-text-light)}
.ev-huella__resumen{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.ev-huella__total{text-align:center;padding:1.5rem;background:linear-gradient(135deg,#ffebee,#ffcdd2);border-radius:12px}
.ev-huella__total-valor{display:block;font-size:2.5rem;font-weight:700;color:#c62828}
.ev-huella__total-unidad{font-size:1rem;color:#c62828}
.ev-huella__total-label{display:block;font-size:.85rem;color:var(--ev-text-light);margin-top:.5rem}
.ev-huella__per-capita{display:flex;flex-direction:column;justify-content:center;align-items:center;padding:1rem;background:#f5f5f5;border-radius:12px}
.ev-huella__pc-valor{font-size:1.75rem;font-weight:700;color:var(--ev-text)}
.ev-huella__pc-label{font-size:.8rem;color:var(--ev-text-light);text-align:center}
.ev-huella__categorias{margin-bottom:1.5rem}
.ev-huella__categorias h4{margin:0 0 1rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-huella__categoria{margin-bottom:.75rem}
.ev-huella__cat-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem}
.ev-huella__cat-icono{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%}
.ev-huella__cat-icono .dashicons{color:#fff;font-size:.9rem;width:.9rem;height:.9rem}
.ev-huella__cat-label{flex:1;font-size:.9rem}
.ev-huella__cat-valor{font-size:.85rem;font-weight:600}
.ev-huella__cat-bar{height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden}
.ev-huella__cat-fill{height:100%;border-radius:4px;transition:width .5s ease}
.ev-huella__compensaciones{padding:1rem;background:var(--ev-primary-light);border-radius:12px;margin-bottom:1.5rem}
.ev-huella__compensaciones h4{display:flex;align-items:center;gap:.5rem;margin:0 0 1rem;font-size:.95rem;color:var(--ev-primary)}
.ev-huella__comp-lista{margin-bottom:1rem}
.ev-huella__comp-item{display:flex;align-items:center;gap:.5rem;padding:.5rem 0;border-bottom:1px dashed rgba(0,0,0,.1);font-size:.9rem}
.ev-huella__comp-item:last-child{border-bottom:none}
.ev-huella__comp-item .dashicons{color:var(--ev-primary)}
.ev-huella__comp-kg{margin-left:auto;font-weight:600;color:var(--ev-primary)}
.ev-huella__balance{padding-top:1rem;border-top:2px solid rgba(0,0,0,.1)}
.ev-huella__balance-item{display:flex;justify-content:space-between;padding:.25rem 0;font-size:.9rem}
.ev-huella__balance-item--total{padding-top:.5rem;border-top:1px solid rgba(0,0,0,.1);font-weight:600}
.ev-huella__balance-valor--negativo{color:var(--ev-danger)}
.ev-huella__balance-valor--positivo{color:var(--ev-primary)}
.ev-huella__equivalencias{margin-bottom:1.5rem}
.ev-huella__equivalencias h4{margin:0 0 1rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-huella__equiv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem}
.ev-huella__equiv-item{text-align:center;padding:1rem;background:#f5f5f5;border-radius:10px}
.ev-huella__equiv-item .dashicons{display:block;margin:0 auto .5rem;font-size:1.5rem;width:1.5rem;height:1.5rem;color:var(--ev-text-light)}
.ev-huella__equiv-valor{display:block;font-size:1.25rem;font-weight:700}
.ev-huella__equiv-label{font-size:.75rem;color:var(--ev-text-light)}
.ev-huella__tips{padding:1rem;background:#fff8e1;border-radius:10px}
.ev-huella__tips h4{margin:0 0 .75rem;font-size:.9rem}
.ev-huella__tips ul{margin:0;padding:0;list-style:none}
.ev-huella__tips li{display:flex;align-items:center;gap:.5rem;padding:.4rem 0;font-size:.85rem;color:var(--ev-text-light)}
.ev-huella__tips li .dashicons{color:#f57c00;font-size:1rem;width:1rem;height:1rem}
@media(max-width:600px){.ev-huella__resumen{grid-template-columns:1fr}.ev-huella__equiv-grid{grid-template-columns:1fr}}
</style>
