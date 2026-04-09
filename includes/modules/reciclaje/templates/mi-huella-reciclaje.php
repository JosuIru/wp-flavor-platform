<?php
/**
 * Template: Mi huella de reciclaje
 * @var object|null $huella_mes Datos de huella del mes actual
 * @var array $historico Histórico de últimos 6 meses
 * @var object $totales Totales acumulados
 */
if (!defined('ABSPATH')) exit;

$usuario = wp_get_current_user();
$co2_mes = $huella_mes->co2_total_ahorrado ?? 0;
$kg_mes = $huella_mes->kg_reciclados ?? 0;

// Equivalencias
$arboles_equivalentes = $co2_mes / 21; // 21kg CO2/árbol/año
$km_coche = $co2_mes / 0.12; // 120g CO2/km
?>
<div class="rec-huella">
    <div class="rec-huella__header">
        <div class="rec-huella__avatar">
            <?php echo get_avatar(get_current_user_id(), 64); ?>
        </div>
        <div class="rec-huella__info">
            <h3><?php echo esc_html($usuario->display_name); ?></h3>
            <span class="rec-huella__titulo"><?php esc_html_e('Eco-ciudadano', 'flavor-platform'); ?></span>
        </div>
    </div>

    <!-- Resumen del mes -->
    <div class="rec-huella__mes">
        <h4><?php echo esc_html(date_i18n('F Y')); ?></h4>
        <div class="rec-huella__co2-principal">
            <span class="rec-huella__co2-valor"><?php echo esc_html(number_format($co2_mes, 1)); ?></span>
            <span class="rec-huella__co2-unidad">kg CO<sub>2</sub> ahorrados</span>
        </div>
    </div>

    <!-- Estadísticas del mes -->
    <div class="rec-huella__stats">
        <div class="rec-huella__stat">
            <span class="rec-huella__stat-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-update"></span>
            </span>
            <span class="rec-huella__stat-valor"><?php echo esc_html(number_format($kg_mes, 1)); ?></span>
            <span class="rec-huella__stat-label"><?php esc_html_e('kg reciclados', 'flavor-platform'); ?></span>
        </div>
        <div class="rec-huella__stat">
            <span class="rec-huella__stat-icono" style="background: #2196f3;">
                <span class="dashicons dashicons-share-alt"></span>
            </span>
            <span class="rec-huella__stat-valor"><?php echo esc_html($huella_mes->items_reutilizados ?? 0); ?></span>
            <span class="rec-huella__stat-label"><?php esc_html_e('reutilizados', 'flavor-platform'); ?></span>
        </div>
        <div class="rec-huella__stat">
            <span class="rec-huella__stat-icono" style="background: #ff9800;">
                <span class="dashicons dashicons-admin-tools"></span>
            </span>
            <span class="rec-huella__stat-valor"><?php echo esc_html($huella_mes->items_reparados ?? 0); ?></span>
            <span class="rec-huella__stat-label"><?php esc_html_e('reparados', 'flavor-platform'); ?></span>
        </div>
    </div>

    <!-- Desglose CO2 -->
    <div class="rec-huella__desglose">
        <h4><?php esc_html_e('Desglose de ahorro', 'flavor-platform'); ?></h4>
        <div class="rec-huella__desglose-items">
            <div class="rec-huella__desglose-item">
                <span class="rec-huella__desglose-label"><?php esc_html_e('Reciclaje', 'flavor-platform'); ?></span>
                <div class="rec-huella__desglose-bar">
                    <?php $pct_rec = $co2_mes > 0 ? (($huella_mes->co2_reciclaje ?? 0) / $co2_mes) * 100 : 0; ?>
                    <div class="rec-huella__desglose-fill" style="width: <?php echo esc_attr($pct_rec); ?>%; background: #4caf50;"></div>
                </div>
                <span class="rec-huella__desglose-valor"><?php echo esc_html(number_format($huella_mes->co2_reciclaje ?? 0, 1)); ?> kg</span>
            </div>
            <div class="rec-huella__desglose-item">
                <span class="rec-huella__desglose-label"><?php esc_html_e('Reutilización', 'flavor-platform'); ?></span>
                <div class="rec-huella__desglose-bar">
                    <?php $pct_reut = $co2_mes > 0 ? (($huella_mes->co2_reutilizacion ?? 0) / $co2_mes) * 100 : 0; ?>
                    <div class="rec-huella__desglose-fill" style="width: <?php echo esc_attr($pct_reut); ?>%; background: #2196f3;"></div>
                </div>
                <span class="rec-huella__desglose-valor"><?php echo esc_html(number_format($huella_mes->co2_reutilizacion ?? 0, 1)); ?> kg</span>
            </div>
            <div class="rec-huella__desglose-item">
                <span class="rec-huella__desglose-label"><?php esc_html_e('Reparación', 'flavor-platform'); ?></span>
                <div class="rec-huella__desglose-bar">
                    <?php $pct_rep = $co2_mes > 0 ? (($huella_mes->co2_reparacion ?? 0) / $co2_mes) * 100 : 0; ?>
                    <div class="rec-huella__desglose-fill" style="width: <?php echo esc_attr($pct_rep); ?>%; background: #ff9800;"></div>
                </div>
                <span class="rec-huella__desglose-valor"><?php echo esc_html(number_format($huella_mes->co2_reparacion ?? 0, 1)); ?> kg</span>
            </div>
        </div>
    </div>

    <!-- Equivalencias -->
    <div class="rec-huella__equivalencias">
        <h4><?php esc_html_e('Tu impacto equivale a...', 'flavor-platform'); ?></h4>
        <div class="rec-huella__equiv-grid">
            <div class="rec-huella__equiv-item">
                <span class="dashicons dashicons-palmtree"></span>
                <span class="rec-huella__equiv-valor"><?php echo esc_html(number_format($arboles_equivalentes, 1)); ?></span>
                <span class="rec-huella__equiv-label"><?php esc_html_e('árboles/año', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-huella__equiv-item">
                <span class="dashicons dashicons-car"></span>
                <span class="rec-huella__equiv-valor"><?php echo esc_html(number_format($km_coche, 0)); ?></span>
                <span class="rec-huella__equiv-label"><?php esc_html_e('km no recorridos', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <!-- Totales acumulados -->
    <?php if ($totales): ?>
        <div class="rec-huella__totales">
            <h4><?php esc_html_e('Acumulado histórico', 'flavor-platform'); ?></h4>
            <div class="rec-huella__totales-grid">
                <div class="rec-huella__total-item">
                    <span class="rec-huella__total-valor"><?php echo esc_html(number_format($totales->co2_total ?? 0, 0)); ?></span>
                    <span class="rec-huella__total-label"><?php esc_html_e('kg CO₂ total', 'flavor-platform'); ?></span>
                </div>
                <div class="rec-huella__total-item">
                    <span class="rec-huella__total-valor"><?php echo esc_html(number_format($totales->kg_total ?? 0, 0)); ?></span>
                    <span class="rec-huella__total-label"><?php esc_html_e('kg reciclados', 'flavor-platform'); ?></span>
                </div>
                <div class="rec-huella__total-item">
                    <span class="rec-huella__total-valor"><?php echo esc_html($totales->items_reut ?? 0); ?></span>
                    <span class="rec-huella__total-label"><?php esc_html_e('items reutil.', 'flavor-platform'); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tendencia -->
    <?php if (!empty($historico)): ?>
        <div class="rec-huella__tendencia">
            <h4><?php esc_html_e('Evolución mensual', 'flavor-platform'); ?></h4>
            <div class="rec-huella__chart">
                <?php
                $max_co2 = max(array_column($historico, 'co2_total_ahorrado')) ?: 1;
                foreach (array_reverse($historico) as $h):
                    $altura = ($h->co2_total_ahorrado / $max_co2) * 100;
                ?>
                    <div class="rec-huella__chart-col">
                        <div class="rec-huella__chart-bar" style="height: <?php echo esc_attr($altura); ?>%"></div>
                        <span class="rec-huella__chart-mes"><?php echo esc_html(date_i18n('M', strtotime($h->periodo . '-01'))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<style>
.rec-huella{--rec-primary:#4caf50;--rec-primary-light:#e8f5e9;--rec-text:#333;--rec-text-light:#666;--rec-border:#e0e0e0;background:#fff;border:1px solid var(--rec-border);border-radius:12px;padding:1.5rem;max-width:450px}
.rec-huella__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.rec-huella__avatar img{width:64px;height:64px;border-radius:50%;border:3px solid var(--rec-primary)}
.rec-huella__info h3{margin:0;font-size:1.1rem}
.rec-huella__titulo{display:inline-block;padding:.2rem .5rem;background:var(--rec-primary-light);color:var(--rec-primary);border-radius:10px;font-size:.75rem;font-weight:500;margin-top:.25rem}
.rec-huella__mes{text-align:center;padding:1.5rem;background:linear-gradient(135deg,var(--rec-primary-light),#c8e6c9);border-radius:12px;margin-bottom:1.5rem}
.rec-huella__mes h4{margin:0 0 .5rem;font-size:.9rem;color:var(--rec-text-light)}
.rec-huella__co2-principal{display:flex;flex-direction:column;align-items:center}
.rec-huella__co2-valor{font-size:3rem;font-weight:700;color:var(--rec-primary);line-height:1}
.rec-huella__co2-unidad{font-size:.9rem;color:var(--rec-text-light);margin-top:.25rem}
.rec-huella__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.5rem}
.rec-huella__stat{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem;background:#f5f5f5;border-radius:10px}
.rec-huella__stat-icono{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;margin-bottom:.5rem}
.rec-huella__stat-icono .dashicons{color:#fff;font-size:1.1rem;width:1.1rem;height:1.1rem}
.rec-huella__stat-valor{font-size:1.25rem;font-weight:700}
.rec-huella__stat-label{font-size:.7rem;color:var(--rec-text-light)}
.rec-huella__desglose{margin-bottom:1.5rem}
.rec-huella__desglose h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-huella__desglose-item{display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem}
.rec-huella__desglose-label{width:80px;font-size:.85rem}
.rec-huella__desglose-bar{flex:1;height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden}
.rec-huella__desglose-fill{height:100%;border-radius:4px}
.rec-huella__desglose-valor{width:60px;text-align:right;font-size:.85rem;font-weight:500}
.rec-huella__equivalencias{padding:1rem;background:#e3f2fd;border-radius:10px;margin-bottom:1.5rem}
.rec-huella__equivalencias h4{margin:0 0 .75rem;font-size:.9rem;color:#1565c0}
.rec-huella__equiv-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.rec-huella__equiv-item{display:flex;flex-direction:column;align-items:center;text-align:center}
.rec-huella__equiv-item .dashicons{color:#1565c0;font-size:1.5rem;width:1.5rem;height:1.5rem;margin-bottom:.25rem}
.rec-huella__equiv-valor{font-size:1.5rem;font-weight:700;color:#1565c0}
.rec-huella__equiv-label{font-size:.75rem;color:var(--rec-text-light)}
.rec-huella__totales{margin-bottom:1.5rem}
.rec-huella__totales h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-huella__totales-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}
.rec-huella__total-item{text-align:center;padding:.5rem;background:#f5f5f5;border-radius:8px}
.rec-huella__total-valor{display:block;font-size:1.1rem;font-weight:700;color:var(--rec-primary)}
.rec-huella__total-label{font-size:.65rem;color:var(--rec-text-light)}
.rec-huella__tendencia h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-huella__chart{display:flex;align-items:flex-end;gap:.5rem;height:80px}
.rec-huella__chart-col{flex:1;display:flex;flex-direction:column;align-items:center}
.rec-huella__chart-bar{width:100%;background:var(--rec-primary);border-radius:4px 4px 0 0;min-height:4px}
.rec-huella__chart-mes{font-size:.65rem;color:var(--rec-text-light);margin-top:.25rem}
</style>
