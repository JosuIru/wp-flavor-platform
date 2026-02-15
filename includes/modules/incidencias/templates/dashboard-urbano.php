<?php
/**
 * Template: Dashboard Urbano de Incidencias
 * @var object $metricas Métricas del período actual
 * @var array $por_categoria Resumen por categoría
 * @var array $tendencia Tendencia mensual
 */
if (!defined('ABSPATH')) exit;

$periodo_texto = date_i18n('F Y', strtotime($metricas->periodo . '-01'));
$tasa_resolucion = $metricas->total_incidencias > 0
    ? round(($metricas->resueltas / $metricas->total_incidencias) * 100)
    : 0;

// Tiempo medio en formato legible
$horas = $metricas->tiempo_medio_resolucion ?? 0;
if ($horas >= 24) {
    $tiempo_texto = sprintf(__('%d días', 'flavor-chat-ia'), round($horas / 24));
} else {
    $tiempo_texto = sprintf(__('%d horas', 'flavor-chat-ia'), round($horas));
}
?>
<div class="inc-dashboard">
    <div class="inc-dashboard__header">
        <span class="inc-dashboard__icono">
            <span class="dashicons dashicons-chart-area"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Dashboard Urbano', 'flavor-chat-ia'); ?></h3>
            <span class="inc-dashboard__periodo"><?php echo esc_html($periodo_texto); ?></span>
        </div>
    </div>

    <!-- Métricas principales -->
    <div class="inc-dashboard__metricas">
        <div class="inc-dashboard__metrica">
            <span class="inc-dashboard__metrica-icono" style="background: #f44336;">
                <span class="dashicons dashicons-flag"></span>
            </span>
            <span class="inc-dashboard__metrica-valor"><?php echo esc_html($metricas->total_incidencias); ?></span>
            <span class="inc-dashboard__metrica-label"><?php esc_html_e('Incidencias', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="inc-dashboard__metrica">
            <span class="inc-dashboard__metrica-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-yes-alt"></span>
            </span>
            <span class="inc-dashboard__metrica-valor"><?php echo esc_html($metricas->resueltas); ?></span>
            <span class="inc-dashboard__metrica-label"><?php esc_html_e('Resueltas', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="inc-dashboard__metrica">
            <span class="inc-dashboard__metrica-icono" style="background: #2196f3;">
                <span class="dashicons dashicons-groups"></span>
            </span>
            <span class="inc-dashboard__metrica-valor"><?php echo esc_html($metricas->participacion_ciudadana); ?></span>
            <span class="inc-dashboard__metrica-label"><?php esc_html_e('Ciudadanos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="inc-dashboard__metrica">
            <span class="inc-dashboard__metrica-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <span class="inc-dashboard__metrica-valor"><?php echo esc_html($metricas->voluntariados); ?></span>
            <span class="inc-dashboard__metrica-label"><?php esc_html_e('Voluntariados', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Indicadores clave -->
    <div class="inc-dashboard__indicadores">
        <div class="inc-dashboard__indicador">
            <div class="inc-dashboard__indicador-header">
                <span class="dashicons dashicons-chart-pie"></span>
                <span><?php esc_html_e('Tasa de resolución', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="inc-dashboard__gauge">
                <?php $clase_tasa = $tasa_resolucion >= 70 ? 'bueno' : ($tasa_resolucion >= 40 ? 'medio' : 'bajo'); ?>
                <div class="inc-dashboard__gauge-bar">
                    <div class="inc-dashboard__gauge-fill inc-dashboard__gauge-fill--<?php echo esc_attr($clase_tasa); ?>" style="width: <?php echo esc_attr($tasa_resolucion); ?>%"></div>
                </div>
                <span class="inc-dashboard__gauge-valor"><?php echo esc_html($tasa_resolucion); ?>%</span>
            </div>
        </div>

        <div class="inc-dashboard__indicador">
            <div class="inc-dashboard__indicador-header">
                <span class="dashicons dashicons-clock"></span>
                <span><?php esc_html_e('Tiempo medio resolución', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="inc-dashboard__tiempo">
                <span class="inc-dashboard__tiempo-valor"><?php echo esc_html($tiempo_texto); ?></span>
            </div>
        </div>

        <?php if ($metricas->indice_satisfaccion): ?>
            <div class="inc-dashboard__indicador">
                <div class="inc-dashboard__indicador-header">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span><?php esc_html_e('Satisfacción', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="inc-dashboard__satisfaccion">
                    <?php
                    $satisfaccion = round($metricas->indice_satisfaccion, 1);
                    for ($i = 1; $i <= 5; $i++):
                        $llena = $i <= floor($satisfaccion);
                        $media = !$llena && $i == ceil($satisfaccion) && $satisfaccion != floor($satisfaccion);
                    ?>
                        <span class="dashicons <?php echo $llena ? 'dashicons-star-filled' : ($media ? 'dashicons-star-half' : 'dashicons-star-empty'); ?>"></span>
                    <?php endfor; ?>
                    <span class="inc-dashboard__satisfaccion-valor"><?php echo esc_html($satisfaccion); ?>/5</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Por categoría -->
    <?php if (!empty($por_categoria)): ?>
        <div class="inc-dashboard__categorias">
            <h4><?php esc_html_e('Por categoría', 'flavor-chat-ia'); ?></h4>
            <?php foreach ($por_categoria as $cat): ?>
                <?php
                $tasa_cat = $cat->total > 0 ? round(($cat->resueltas / $cat->total) * 100) : 0;
                $iconos = [
                    'alumbrado' => 'dashicons-lightbulb',
                    'limpieza' => 'dashicons-trash',
                    'via_publica' => 'dashicons-admin-site',
                    'mobiliario' => 'dashicons-admin-tools',
                    'parques' => 'dashicons-palmtree',
                ];
                $icono = $iconos[$cat->categoria] ?? 'dashicons-warning';
                ?>
                <div class="inc-dashboard__categoria">
                    <div class="inc-dashboard__cat-header">
                        <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                        <span class="inc-dashboard__cat-nombre"><?php echo esc_html(ucfirst(str_replace('_', ' ', $cat->categoria))); ?></span>
                        <span class="inc-dashboard__cat-total"><?php echo esc_html($cat->total); ?></span>
                    </div>
                    <div class="inc-dashboard__cat-bar">
                        <div class="inc-dashboard__cat-fill" style="width: <?php echo esc_attr($tasa_cat); ?>%"></div>
                    </div>
                    <span class="inc-dashboard__cat-tasa"><?php echo esc_html($tasa_cat); ?>% resueltas</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tendencia -->
    <?php if (!empty($tendencia)): ?>
        <div class="inc-dashboard__tendencia">
            <h4><?php esc_html_e('Tendencia mensual', 'flavor-chat-ia'); ?></h4>
            <div class="inc-dashboard__tendencia-chart">
                <?php
                $max_valor = max(array_column($tendencia, 'total'));
                foreach ($tendencia as $mes):
                    $altura = $max_valor > 0 ? ($mes->total / $max_valor) * 100 : 0;
                    $resueltas_altura = $max_valor > 0 ? ($mes->resueltas / $max_valor) * 100 : 0;
                ?>
                    <div class="inc-dashboard__tendencia-col">
                        <div class="inc-dashboard__tendencia-bars">
                            <div class="inc-dashboard__tendencia-bar inc-dashboard__tendencia-bar--total" style="height: <?php echo esc_attr($altura); ?>%"></div>
                            <div class="inc-dashboard__tendencia-bar inc-dashboard__tendencia-bar--resueltas" style="height: <?php echo esc_attr($resueltas_altura); ?>%"></div>
                        </div>
                        <span class="inc-dashboard__tendencia-mes">
                            <?php echo esc_html(date_i18n('M', strtotime($mes->mes . '-01'))); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="inc-dashboard__tendencia-leyenda">
                <span><span class="inc-dashboard__leyenda-color inc-dashboard__leyenda-color--total"></span> <?php esc_html_e('Reportadas', 'flavor-chat-ia'); ?></span>
                <span><span class="inc-dashboard__leyenda-color inc-dashboard__leyenda-color--resueltas"></span> <?php esc_html_e('Resueltas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Impacto ambiental -->
    <?php if ($metricas->incidencias_ambientales > 0): ?>
        <div class="inc-dashboard__ambiental">
            <h4>
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php esc_html_e('Impacto Ambiental', 'flavor-chat-ia'); ?>
            </h4>
            <div class="inc-dashboard__ambiental-valor">
                <?php echo esc_html($metricas->incidencias_ambientales); ?>
                <span><?php esc_html_e('incidencias con impacto ambiental', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>
<style>
.inc-dashboard{--inc-primary:#f44336;--inc-success:#4caf50;--inc-warning:#ff9800;--inc-info:#2196f3;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem}
.inc-dashboard__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-dashboard__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--inc-primary);border-radius:50%}
.inc-dashboard__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.inc-dashboard__header h3{margin:0;font-size:1.1rem}
.inc-dashboard__periodo{font-size:.85rem;color:var(--inc-text-light)}
.inc-dashboard__metricas{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem}
.inc-dashboard__metrica{text-align:center;padding:1rem .5rem;background:#f5f5f5;border-radius:10px}
.inc-dashboard__metrica-icono{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;margin-bottom:.5rem}
.inc-dashboard__metrica-icono .dashicons{color:#fff;font-size:1.1rem;width:1.1rem;height:1.1rem}
.inc-dashboard__metrica-valor{display:block;font-size:1.5rem;font-weight:700;line-height:1.2}
.inc-dashboard__metrica-label{font-size:.7rem;color:var(--inc-text-light)}
.inc-dashboard__indicadores{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.inc-dashboard__indicador{padding:1rem;background:#f9f9f9;border-radius:10px}
.inc-dashboard__indicador-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;font-size:.9rem;font-weight:500}
.inc-dashboard__indicador-header .dashicons{color:var(--inc-primary)}
.inc-dashboard__gauge{display:flex;align-items:center;gap:.75rem}
.inc-dashboard__gauge-bar{flex:1;height:12px;background:#e0e0e0;border-radius:6px;overflow:hidden}
.inc-dashboard__gauge-fill{height:100%;border-radius:6px}
.inc-dashboard__gauge-fill--bueno{background:var(--inc-success)}
.inc-dashboard__gauge-fill--medio{background:var(--inc-warning)}
.inc-dashboard__gauge-fill--bajo{background:var(--inc-primary)}
.inc-dashboard__gauge-valor{font-size:1.1rem;font-weight:700;min-width:50px;text-align:right}
.inc-dashboard__tiempo{text-align:center;padding:.5rem}
.inc-dashboard__tiempo-valor{font-size:1.5rem;font-weight:700;color:var(--inc-info)}
.inc-dashboard__satisfaccion{display:flex;align-items:center;gap:.25rem}
.inc-dashboard__satisfaccion .dashicons{color:#ffc107;font-size:1.1rem;width:1.1rem;height:1.1rem}
.inc-dashboard__satisfaccion-valor{margin-left:.5rem;font-weight:600}
.inc-dashboard__categorias{margin-bottom:1.5rem}
.inc-dashboard__categorias h4{margin:0 0 1rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-dashboard__categoria{margin-bottom:.75rem}
.inc-dashboard__cat-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem}
.inc-dashboard__cat-header .dashicons{color:var(--inc-primary);font-size:1rem;width:1rem;height:1rem}
.inc-dashboard__cat-nombre{flex:1;font-size:.9rem}
.inc-dashboard__cat-total{font-weight:600}
.inc-dashboard__cat-bar{height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden;margin-bottom:.25rem}
.inc-dashboard__cat-fill{height:100%;background:var(--inc-success);border-radius:4px}
.inc-dashboard__cat-tasa{font-size:.75rem;color:var(--inc-text-light)}
.inc-dashboard__tendencia{margin-bottom:1.5rem}
.inc-dashboard__tendencia h4{margin:0 0 1rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-dashboard__tendencia-chart{display:flex;align-items:flex-end;gap:.5rem;height:100px;padding-bottom:.5rem}
.inc-dashboard__tendencia-col{flex:1;display:flex;flex-direction:column;align-items:center}
.inc-dashboard__tendencia-bars{position:relative;width:100%;height:80px;display:flex;align-items:flex-end;justify-content:center;gap:2px}
.inc-dashboard__tendencia-bar{width:40%;border-radius:3px 3px 0 0;transition:height .3s}
.inc-dashboard__tendencia-bar--total{background:#ffcdd2}
.inc-dashboard__tendencia-bar--resueltas{background:var(--inc-success)}
.inc-dashboard__tendencia-mes{font-size:.7rem;color:var(--inc-text-light);margin-top:.25rem}
.inc-dashboard__tendencia-leyenda{display:flex;justify-content:center;gap:1.5rem;margin-top:.75rem;font-size:.75rem;color:var(--inc-text-light)}
.inc-dashboard__leyenda-color{display:inline-block;width:12px;height:12px;border-radius:2px;margin-right:.35rem;vertical-align:middle}
.inc-dashboard__leyenda-color--total{background:#ffcdd2}
.inc-dashboard__leyenda-color--resueltas{background:var(--inc-success)}
.inc-dashboard__ambiental{padding:1rem;background:#e8f5e9;border-radius:10px}
.inc-dashboard__ambiental h4{display:flex;align-items:center;gap:.5rem;margin:0 0 .75rem;font-size:.9rem;color:#2e7d32}
.inc-dashboard__ambiental-valor{font-size:1.75rem;font-weight:700;color:#2e7d32}
.inc-dashboard__ambiental-valor span{display:block;font-size:.85rem;font-weight:400;color:var(--inc-text-light)}
@media(max-width:600px){.inc-dashboard__metricas{grid-template-columns:repeat(2,1fr)}.inc-dashboard__indicadores{grid-template-columns:1fr}}
</style>
