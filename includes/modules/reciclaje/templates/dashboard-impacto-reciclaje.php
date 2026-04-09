<?php
/**
 * Template: Dashboard de impacto ambiental del reciclaje
 * @var object $metricas_globales Métricas globales del sistema
 * @var array $top_recicladores Top 10 recicladores
 * @var array $tendencias Tendencias de los últimos 6 meses
 * @var array $por_tipo Desglose por tipo de material
 */
if (!defined('ABSPATH')) exit;

$tipos_material = [
    'papel' => ['icono' => 'dashicons-media-text', 'color' => '#607d8b', 'label' => 'Papel/Cartón'],
    'plastico' => ['icono' => 'dashicons-archive', 'color' => '#ff9800', 'label' => 'Plástico'],
    'vidrio' => ['icono' => 'dashicons-visibility', 'color' => '#4caf50', 'label' => 'Vidrio'],
    'metal' => ['icono' => 'dashicons-admin-generic', 'color' => '#9e9e9e', 'label' => 'Metal'],
    'electronico' => ['icono' => 'dashicons-laptop', 'color' => '#2196f3', 'label' => 'Electrónico'],
    'organico' => ['icono' => 'dashicons-carrot', 'color' => '#795548', 'label' => 'Orgánico'],
    'textil' => ['icono' => 'dashicons-universal-access', 'color' => '#9c27b0', 'label' => 'Textil'],
];
?>
<div class="rec-dashboard">
    <div class="rec-dashboard__header">
        <span class="rec-dashboard__icono">
            <span class="dashicons dashicons-chart-area"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Dashboard de Impacto', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('Impacto ambiental colectivo de nuestra comunidad', 'flavor-platform'); ?></p>
        </div>
    </div>

    <!-- Métricas principales -->
    <div class="rec-dashboard__metricas">
        <div class="rec-dashboard__metrica rec-dashboard__metrica--principal">
            <span class="rec-dashboard__metrica-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-cloud"></span>
            </span>
            <div class="rec-dashboard__metrica-content">
                <span class="rec-dashboard__metrica-valor"><?php echo esc_html(number_format($metricas_globales->co2_total_ahorrado ?? 0, 0)); ?></span>
                <span class="rec-dashboard__metrica-label"><?php esc_html_e('kg CO₂ ahorrados', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="rec-dashboard__metricas-grid">
            <div class="rec-dashboard__metrica">
                <span class="rec-dashboard__metrica-icono rec-dashboard__metrica-icono--sm" style="background: #2196f3;">
                    <span class="dashicons dashicons-update"></span>
                </span>
                <span class="rec-dashboard__metrica-valor"><?php echo esc_html(number_format($metricas_globales->kg_reciclados ?? 0, 0)); ?></span>
                <span class="rec-dashboard__metrica-label"><?php esc_html_e('kg reciclados', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-dashboard__metrica">
                <span class="rec-dashboard__metrica-icono rec-dashboard__metrica-icono--sm" style="background: #ff9800;">
                    <span class="dashicons dashicons-share-alt"></span>
                </span>
                <span class="rec-dashboard__metrica-valor"><?php echo esc_html($metricas_globales->items_reutilizados ?? 0); ?></span>
                <span class="rec-dashboard__metrica-label"><?php esc_html_e('reutilizados', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-dashboard__metrica">
                <span class="rec-dashboard__metrica-icono rec-dashboard__metrica-icono--sm" style="background: #9c27b0;">
                    <span class="dashicons dashicons-admin-tools"></span>
                </span>
                <span class="rec-dashboard__metrica-valor"><?php echo esc_html($metricas_globales->items_reparados ?? 0); ?></span>
                <span class="rec-dashboard__metrica-label"><?php esc_html_e('reparados', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-dashboard__metrica">
                <span class="rec-dashboard__metrica-icono rec-dashboard__metrica-icono--sm" style="background: #607d8b;">
                    <span class="dashicons dashicons-groups"></span>
                </span>
                <span class="rec-dashboard__metrica-valor"><?php echo esc_html($metricas_globales->participantes_activos ?? 0); ?></span>
                <span class="rec-dashboard__metrica-label"><?php esc_html_e('participantes', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <!-- Equivalencias visuales -->
    <div class="rec-dashboard__equivalencias">
        <h4><?php esc_html_e('Nuestro impacto equivale a...', 'flavor-platform'); ?></h4>
        <div class="rec-dashboard__equiv-grid">
            <?php
            $co2_total = $metricas_globales->co2_total_ahorrado ?? 0;
            $arboles = $co2_total / 21;
            $km_coche = $co2_total / 0.12;
            $litros_agua = ($metricas_globales->kg_reciclados ?? 0) * 100;
            ?>
            <div class="rec-dashboard__equiv-item">
                <span class="rec-dashboard__equiv-icono">🌳</span>
                <span class="rec-dashboard__equiv-valor"><?php echo esc_html(number_format($arboles, 0)); ?></span>
                <span class="rec-dashboard__equiv-label"><?php esc_html_e('árboles plantados', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-dashboard__equiv-item">
                <span class="rec-dashboard__equiv-icono">🚗</span>
                <span class="rec-dashboard__equiv-valor"><?php echo esc_html(number_format($km_coche, 0)); ?></span>
                <span class="rec-dashboard__equiv-label"><?php esc_html_e('km no recorridos', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-dashboard__equiv-item">
                <span class="rec-dashboard__equiv-icono">💧</span>
                <span class="rec-dashboard__equiv-valor"><?php echo esc_html(number_format($litros_agua, 0)); ?></span>
                <span class="rec-dashboard__equiv-label"><?php esc_html_e('litros agua ahorrados', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <!-- Desglose por tipo -->
    <?php if (!empty($por_tipo)): ?>
        <div class="rec-dashboard__tipos">
            <h4><?php esc_html_e('Reciclaje por tipo de material', 'flavor-platform'); ?></h4>
            <div class="rec-dashboard__tipos-lista">
                <?php
                $max_kg = max(array_column($por_tipo, 'kg_total')) ?: 1;
                foreach ($por_tipo as $tipo):
                    $tipo_info = $tipos_material[$tipo->tipo_material] ?? ['icono' => 'dashicons-marker', 'color' => '#9e9e9e', 'label' => ucfirst($tipo->tipo_material)];
                    $porcentaje = ($tipo->kg_total / $max_kg) * 100;
                ?>
                    <div class="rec-dashboard__tipo-item">
                        <div class="rec-dashboard__tipo-header">
                            <span class="rec-dashboard__tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                                <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                            </span>
                            <span class="rec-dashboard__tipo-nombre"><?php echo esc_html($tipo_info['label']); ?></span>
                            <span class="rec-dashboard__tipo-valor"><?php echo esc_html(number_format($tipo->kg_total, 0)); ?> kg</span>
                        </div>
                        <div class="rec-dashboard__tipo-bar">
                            <div class="rec-dashboard__tipo-fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($tipo_info['color']); ?>"></div>
                        </div>
                        <span class="rec-dashboard__tipo-co2">
                            <span class="dashicons dashicons-cloud"></span>
                            -<?php echo esc_html(number_format($tipo->co2_total ?? 0, 1)); ?> kg CO₂
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tendencias -->
    <?php if (!empty($tendencias)): ?>
        <div class="rec-dashboard__tendencias">
            <h4><?php esc_html_e('Evolución mensual', 'flavor-platform'); ?></h4>
            <div class="rec-dashboard__chart">
                <?php
                $max_co2 = max(array_column($tendencias, 'co2_ahorrado')) ?: 1;
                foreach (array_reverse($tendencias) as $t):
                    $altura = ($t->co2_ahorrado / $max_co2) * 100;
                ?>
                    <div class="rec-dashboard__chart-col">
                        <div class="rec-dashboard__chart-tooltip">
                            <?php echo esc_html(number_format($t->co2_ahorrado, 0)); ?> kg
                        </div>
                        <div class="rec-dashboard__chart-bar" style="height: <?php echo esc_attr($altura); ?>%"></div>
                        <span class="rec-dashboard__chart-mes"><?php echo esc_html(date_i18n('M', strtotime($t->periodo . '-01'))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Top recicladores -->
    <?php if (!empty($top_recicladores)): ?>
        <div class="rec-dashboard__ranking">
            <h4><?php esc_html_e('Top Eco-ciudadanos', 'flavor-platform'); ?></h4>

            <!-- Podio -->
            <div class="rec-dashboard__podio">
                <?php
                $posiciones = [1 => 'segundo', 0 => 'primero', 2 => 'tercero'];
                $orden_podio = [1, 0, 2];
                foreach ($orden_podio as $pos):
                    if (!isset($top_recicladores[$pos])) continue;
                    $r = $top_recicladores[$pos];
                    $clase_pos = $posiciones[$pos];
                ?>
                    <div class="rec-dashboard__podio-item rec-dashboard__podio-item--<?php echo esc_attr($clase_pos); ?>">
                        <div class="rec-dashboard__podio-avatar">
                            <?php echo get_avatar($r->usuario_id, 60); ?>
                            <span class="rec-dashboard__podio-pos"><?php echo ($pos + 1); ?></span>
                        </div>
                        <strong><?php echo esc_html($r->display_name); ?></strong>
                        <span class="rec-dashboard__podio-co2">
                            <?php echo esc_html(number_format($r->co2_total_ahorrado, 0)); ?> kg CO₂
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Resto del ranking -->
            <?php if (count($top_recicladores) > 3): ?>
                <div class="rec-dashboard__ranking-lista">
                    <?php for ($i = 3; $i < count($top_recicladores); $i++):
                        $r = $top_recicladores[$i];
                    ?>
                        <div class="rec-dashboard__ranking-item">
                            <span class="rec-dashboard__ranking-pos"><?php echo ($i + 1); ?></span>
                            <div class="rec-dashboard__ranking-avatar">
                                <?php echo get_avatar($r->usuario_id, 36); ?>
                            </div>
                            <span class="rec-dashboard__ranking-nombre"><?php echo esc_html($r->display_name); ?></span>
                            <span class="rec-dashboard__ranking-co2">
                                <?php echo esc_html(number_format($r->co2_total_ahorrado, 0)); ?> kg
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Objetivos comunitarios -->
    <div class="rec-dashboard__objetivos">
        <h4><?php esc_html_e('Objetivos comunitarios', 'flavor-platform'); ?></h4>
        <?php
        $objetivos = [
            ['meta' => 10000, 'actual' => $metricas_globales->co2_total_ahorrado ?? 0, 'label' => __('10.000 kg CO₂ ahorrados', 'flavor-platform')],
            ['meta' => 100, 'actual' => $metricas_globales->participantes_activos ?? 0, 'label' => __('100 participantes activos', 'flavor-platform')],
            ['meta' => 500, 'actual' => ($metricas_globales->items_reutilizados ?? 0) + ($metricas_globales->items_reparados ?? 0), 'label' => __('500 objetos salvados', 'flavor-platform')],
        ];
        foreach ($objetivos as $obj):
            $progreso = min(100, ($obj['actual'] / $obj['meta']) * 100);
            $completado = $progreso >= 100;
        ?>
            <div class="rec-dashboard__objetivo <?php echo $completado ? 'rec-dashboard__objetivo--completado' : ''; ?>">
                <div class="rec-dashboard__objetivo-header">
                    <span class="rec-dashboard__objetivo-label"><?php echo esc_html($obj['label']); ?></span>
                    <span class="rec-dashboard__objetivo-progreso">
                        <?php if ($completado): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                        <?php else: ?>
                            <?php echo esc_html(round($progreso)); ?>%
                        <?php endif; ?>
                    </span>
                </div>
                <div class="rec-dashboard__objetivo-bar">
                    <div class="rec-dashboard__objetivo-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Call to action -->
    <div class="rec-dashboard__cta">
        <span class="dashicons dashicons-heart"></span>
        <p><?php esc_html_e('¡Cada pequeña acción cuenta! Únete a nuestra comunidad sostenible.', 'flavor-platform'); ?></p>
    </div>
</div>
<style>
.rec-dashboard{--rec-primary:#4caf50;--rec-primary-light:#e8f5e9;--rec-text:#333;--rec-text-light:#666;--rec-border:#e0e0e0;background:#fff;border:1px solid var(--rec-border);border-radius:12px;padding:1.5rem}
.rec-dashboard__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.rec-dashboard__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--rec-primary);border-radius:50%}
.rec-dashboard__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-dashboard__header h3{margin:0;font-size:1.1rem}
.rec-dashboard__header p{margin:0;font-size:.85rem;color:var(--rec-text-light)}
.rec-dashboard__metricas{margin-bottom:1.5rem}
.rec-dashboard__metrica--principal{display:flex;align-items:center;gap:1rem;padding:1.5rem;background:linear-gradient(135deg,var(--rec-primary-light),#c8e6c9);border-radius:12px;margin-bottom:1rem}
.rec-dashboard__metrica-icono{display:flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:50%}
.rec-dashboard__metrica-icono .dashicons{color:#fff;font-size:1.75rem;width:1.75rem;height:1.75rem}
.rec-dashboard__metrica-icono--sm{width:40px;height:40px}
.rec-dashboard__metrica-icono--sm .dashicons{font-size:1.25rem;width:1.25rem;height:1.25rem}
.rec-dashboard__metrica-content{flex:1}
.rec-dashboard__metrica--principal .rec-dashboard__metrica-valor{font-size:2.5rem;font-weight:700;color:var(--rec-primary);display:block}
.rec-dashboard__metrica--principal .rec-dashboard__metrica-label{font-size:1rem;color:var(--rec-text-light)}
.rec-dashboard__metricas-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem}
.rec-dashboard__metricas-grid .rec-dashboard__metrica{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem;background:#f5f5f5;border-radius:10px}
.rec-dashboard__metricas-grid .rec-dashboard__metrica-valor{font-size:1.25rem;font-weight:700;margin-top:.5rem}
.rec-dashboard__metricas-grid .rec-dashboard__metrica-label{font-size:.7rem;color:var(--rec-text-light)}
.rec-dashboard__equivalencias{padding:1rem;background:#e3f2fd;border-radius:10px;margin-bottom:1.5rem}
.rec-dashboard__equivalencias h4{margin:0 0 .75rem;font-size:.9rem;color:#1565c0}
.rec-dashboard__equiv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
.rec-dashboard__equiv-item{display:flex;flex-direction:column;align-items:center;text-align:center}
.rec-dashboard__equiv-icono{font-size:2rem;margin-bottom:.25rem}
.rec-dashboard__equiv-valor{font-size:1.5rem;font-weight:700;color:#1565c0}
.rec-dashboard__equiv-label{font-size:.75rem;color:var(--rec-text-light)}
.rec-dashboard__tipos{margin-bottom:1.5rem}
.rec-dashboard__tipos h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-dashboard__tipos-lista{display:grid;gap:.75rem}
.rec-dashboard__tipo-item{padding:.75rem;background:#f9f9f9;border-radius:8px}
.rec-dashboard__tipo-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
.rec-dashboard__tipo-badge{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%}
.rec-dashboard__tipo-badge .dashicons{color:#fff;font-size:.9rem;width:.9rem;height:.9rem}
.rec-dashboard__tipo-nombre{flex:1;font-size:.9rem}
.rec-dashboard__tipo-valor{font-weight:600;font-size:.9rem}
.rec-dashboard__tipo-bar{height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden;margin-bottom:.35rem}
.rec-dashboard__tipo-fill{height:100%;border-radius:4px;transition:width .5s}
.rec-dashboard__tipo-co2{display:flex;align-items:center;gap:.25rem;font-size:.75rem;color:var(--rec-primary)}
.rec-dashboard__tipo-co2 .dashicons{font-size:.8rem;width:.8rem;height:.8rem}
.rec-dashboard__tendencias{margin-bottom:1.5rem}
.rec-dashboard__tendencias h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-dashboard__chart{display:flex;align-items:flex-end;gap:.5rem;height:100px}
.rec-dashboard__chart-col{flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.rec-dashboard__chart-bar{width:100%;background:var(--rec-primary);border-radius:4px 4px 0 0;min-height:4px;transition:height .3s}
.rec-dashboard__chart-col:hover .rec-dashboard__chart-tooltip{opacity:1;visibility:visible}
.rec-dashboard__chart-tooltip{position:absolute;bottom:100%;left:50%;transform:translateX(-50%);padding:.25rem .5rem;background:#333;color:#fff;font-size:.7rem;border-radius:4px;white-space:nowrap;opacity:0;visibility:hidden;transition:opacity .2s}
.rec-dashboard__chart-mes{font-size:.65rem;color:var(--rec-text-light);margin-top:.25rem}
.rec-dashboard__ranking{margin-bottom:1.5rem}
.rec-dashboard__ranking h4{margin:0 0 1rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-dashboard__podio{display:flex;align-items:flex-end;justify-content:center;gap:1rem;margin-bottom:1rem}
.rec-dashboard__podio-item{display:flex;flex-direction:column;align-items:center;text-align:center;padding:1rem;background:#f5f5f5;border-radius:10px}
.rec-dashboard__podio-item--primero{order:2;background:linear-gradient(135deg,#ffd700,#ffec8b);padding:1.25rem}
.rec-dashboard__podio-item--segundo{order:1;background:linear-gradient(135deg,#c0c0c0,#e8e8e8)}
.rec-dashboard__podio-item--tercero{order:3;background:linear-gradient(135deg,#cd7f32,#dda15e)}
.rec-dashboard__podio-avatar{position:relative;margin-bottom:.5rem}
.rec-dashboard__podio-avatar img{width:60px;height:60px;border-radius:50%;border:3px solid #fff}
.rec-dashboard__podio-item--primero .rec-dashboard__podio-avatar img{width:70px;height:70px}
.rec-dashboard__podio-pos{position:absolute;bottom:-5px;right:-5px;width:24px;height:24px;background:var(--rec-primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700}
.rec-dashboard__podio-item strong{font-size:.85rem;margin-bottom:.25rem}
.rec-dashboard__podio-co2{font-size:.75rem;color:var(--rec-primary);font-weight:500}
.rec-dashboard__ranking-lista{display:grid;gap:.5rem}
.rec-dashboard__ranking-item{display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;background:#f9f9f9;border-radius:8px}
.rec-dashboard__ranking-pos{width:24px;height:24px;background:#e0e0e0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600}
.rec-dashboard__ranking-avatar img{width:36px;height:36px;border-radius:50%}
.rec-dashboard__ranking-nombre{flex:1;font-size:.9rem}
.rec-dashboard__ranking-co2{font-size:.85rem;color:var(--rec-primary);font-weight:500}
.rec-dashboard__objetivos{margin-bottom:1.5rem}
.rec-dashboard__objetivos h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light);text-transform:uppercase}
.rec-dashboard__objetivo{padding:.75rem;background:#f5f5f5;border-radius:8px;margin-bottom:.5rem}
.rec-dashboard__objetivo--completado{background:var(--rec-primary-light)}
.rec-dashboard__objetivo-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem}
.rec-dashboard__objetivo-label{font-size:.9rem}
.rec-dashboard__objetivo-progreso{font-size:.85rem;font-weight:500;color:var(--rec-primary)}
.rec-dashboard__objetivo-progreso .dashicons{color:var(--rec-primary);font-size:1rem;width:1rem;height:1rem}
.rec-dashboard__objetivo-bar{height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden}
.rec-dashboard__objetivo-fill{height:100%;background:var(--rec-primary);border-radius:4px;transition:width .5s}
.rec-dashboard__cta{display:flex;align-items:center;gap:.75rem;padding:1rem;background:linear-gradient(135deg,#fff3e0,#ffe0b2);border-radius:10px}
.rec-dashboard__cta .dashicons{color:#ff9800;font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-dashboard__cta p{margin:0;font-size:.9rem;color:var(--rec-text)}
@media(max-width:768px){.rec-dashboard__metricas-grid{grid-template-columns:repeat(2,1fr)}.rec-dashboard__equiv-grid{grid-template-columns:1fr}.rec-dashboard__podio{flex-direction:column;align-items:stretch}.rec-dashboard__podio-item{order:unset!important}}
</style>
