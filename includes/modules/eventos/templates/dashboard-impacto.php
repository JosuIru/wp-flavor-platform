<?php
/**
 * Template: Dashboard de Impacto Social de Eventos
 * @var array $metricas Métricas globales de impacto
 * @var array $eventos_destacados Eventos con mayor impacto
 * @var string $periodo Período actual (Y-m)
 */
if (!defined('ABSPATH')) exit;

$periodo_texto = date_i18n('F Y', strtotime($periodo . '-01'));
?>
<div class="ev-impacto-dash">
    <div class="ev-impacto-dash__header">
        <span class="ev-impacto-dash__icono">
            <span class="dashicons dashicons-chart-area"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Impacto Social de Eventos', 'flavor-chat-ia'); ?></h3>
            <span class="ev-impacto-dash__periodo"><?php echo esc_html($periodo_texto); ?></span>
        </div>
    </div>

    <!-- Métricas principales -->
    <div class="ev-impacto-dash__metricas">
        <div class="ev-impacto-dash__metrica">
            <span class="ev-impacto-dash__metrica-icono" style="background: #2196f3;">
                <span class="dashicons dashicons-calendar-alt"></span>
            </span>
            <span class="ev-impacto-dash__metrica-valor"><?php echo esc_html($metricas['total_eventos'] ?? 0); ?></span>
            <span class="ev-impacto-dash__metrica-label"><?php esc_html_e('Eventos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="ev-impacto-dash__metrica">
            <span class="ev-impacto-dash__metrica-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-groups"></span>
            </span>
            <span class="ev-impacto-dash__metrica-valor"><?php echo esc_html($metricas['total_asistentes'] ?? 0); ?></span>
            <span class="ev-impacto-dash__metrica-label"><?php esc_html_e('Asistentes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="ev-impacto-dash__metrica">
            <span class="ev-impacto-dash__metrica-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <span class="ev-impacto-dash__metrica-valor"><?php echo esc_html($metricas['horas_voluntariado'] ?? 0); ?>h</span>
            <span class="ev-impacto-dash__metrica-label"><?php esc_html_e('Voluntariado', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="ev-impacto-dash__metrica">
            <span class="ev-impacto-dash__metrica-icono" style="background: #ff9800;">
                <span class="dashicons dashicons-universal-access-alt"></span>
            </span>
            <span class="ev-impacto-dash__metrica-valor"><?php echo esc_html($metricas['plazas_solidarias'] ?? 0); ?></span>
            <span class="ev-impacto-dash__metrica-label"><?php esc_html_e('P. Solidarias', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Indicadores de inclusividad -->
    <div class="ev-impacto-dash__inclusividad">
        <h4>
            <span class="dashicons dashicons-universal-access"></span>
            <?php esc_html_e('Inclusividad', 'flavor-chat-ia'); ?>
        </h4>
        <div class="ev-impacto-dash__inclu-grid">
            <?php
            $indicadores = [
                'accesibilidad' => [
                    'valor' => $metricas['eventos_accesibles'] ?? 0,
                    'total' => $metricas['total_eventos'] ?? 1,
                    'icono' => 'dashicons-universal-access',
                    'label' => __('Accesibles', 'flavor-chat-ia'),
                    'color' => '#9c27b0',
                ],
                'lse' => [
                    'valor' => $metricas['eventos_lse'] ?? 0,
                    'total' => $metricas['total_eventos'] ?? 1,
                    'icono' => 'dashicons-format-status',
                    'label' => __('Con LSE', 'flavor-chat-ia'),
                    'color' => '#3f51b5',
                ],
                'cuidado_infantil' => [
                    'valor' => $metricas['eventos_cuidado'] ?? 0,
                    'total' => $metricas['total_eventos'] ?? 1,
                    'icono' => 'dashicons-smiley',
                    'label' => __('Con guardería', 'flavor-chat-ia'),
                    'color' => '#00bcd4',
                ],
            ];
            foreach ($indicadores as $key => $ind):
                $porcentaje = $ind['total'] > 0 ? round(($ind['valor'] / $ind['total']) * 100) : 0;
            ?>
                <div class="ev-impacto-dash__inclu-item">
                    <div class="ev-impacto-dash__inclu-ring" style="--porcentaje: <?php echo esc_attr($porcentaje); ?>; --color: <?php echo esc_attr($ind['color']); ?>">
                        <span class="ev-impacto-dash__inclu-valor"><?php echo esc_html($porcentaje); ?>%</span>
                    </div>
                    <span class="ev-impacto-dash__inclu-label"><?php echo esc_html($ind['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Huella de carbono -->
    <div class="ev-impacto-dash__co2">
        <div class="ev-impacto-dash__co2-header">
            <span class="dashicons dashicons-cloud"></span>
            <span><?php esc_html_e('Huella de Carbono Total', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="ev-impacto-dash__co2-body">
            <div class="ev-impacto-dash__co2-emitido">
                <span class="ev-impacto-dash__co2-numero"><?php echo esc_html(number_format($metricas['co2_emitido'] ?? 0, 0)); ?></span>
                <span class="ev-impacto-dash__co2-unidad">kg CO<sub>2</sub> emitidos</span>
            </div>
            <div class="ev-impacto-dash__co2-compensado">
                <span class="ev-impacto-dash__co2-numero">-<?php echo esc_html(number_format($metricas['co2_compensado'] ?? 0, 0)); ?></span>
                <span class="ev-impacto-dash__co2-unidad">kg compensados</span>
            </div>
        </div>
        <?php
        $co2_neto = ($metricas['co2_emitido'] ?? 0) - ($metricas['co2_compensado'] ?? 0);
        $porcentaje_compensado = ($metricas['co2_emitido'] ?? 0) > 0
            ? min(100, (($metricas['co2_compensado'] ?? 0) / $metricas['co2_emitido']) * 100)
            : 0;
        ?>
        <div class="ev-impacto-dash__co2-balance">
            <div class="ev-impacto-dash__co2-bar">
                <div class="ev-impacto-dash__co2-fill" style="width: <?php echo esc_attr($porcentaje_compensado); ?>%"></div>
            </div>
            <span class="ev-impacto-dash__co2-text">
                <?php printf(esc_html__('%d%% compensado', 'flavor-chat-ia'), round($porcentaje_compensado)); ?>
            </span>
        </div>
    </div>

    <!-- Eventos destacados -->
    <?php if (!empty($eventos_destacados)): ?>
        <div class="ev-impacto-dash__destacados">
            <h4><?php esc_html_e('Eventos con mayor impacto', 'flavor-chat-ia'); ?></h4>
            <div class="ev-impacto-dash__destacados-lista">
                <?php foreach ($eventos_destacados as $evento): ?>
                    <div class="ev-impacto-dash__destacado">
                        <div class="ev-impacto-dash__destacado-info">
                            <strong><?php echo esc_html($evento->titulo); ?></strong>
                            <span class="ev-impacto-dash__destacado-fecha">
                                <?php echo esc_html(date_i18n('j M Y', strtotime($evento->fecha_inicio))); ?>
                            </span>
                        </div>
                        <div class="ev-impacto-dash__destacado-stats">
                            <span title="<?php esc_attr_e('Asistentes', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo esc_html($evento->asistentes); ?>
                            </span>
                            <span title="<?php esc_attr_e('Voluntarios', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php echo esc_html($evento->voluntarios); ?>
                            </span>
                            <?php if ($evento->plazas_solidarias > 0): ?>
                                <span title="<?php esc_attr_e('Plazas solidarias', 'flavor-chat-ia'); ?>" class="ev-impacto-dash__destacado-solidario">
                                    <span class="dashicons dashicons-awards"></span>
                                    <?php echo esc_html($evento->plazas_solidarias); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Colaboraciones inter-comunitarias -->
    <?php if (!empty($metricas['colaboraciones'])): ?>
        <div class="ev-impacto-dash__colaboraciones">
            <h4>
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('Colaboraciones entre comunidades', 'flavor-chat-ia'); ?>
            </h4>
            <div class="ev-impacto-dash__colab-stats">
                <div class="ev-impacto-dash__colab-stat">
                    <span class="ev-impacto-dash__colab-valor"><?php echo esc_html($metricas['colaboraciones']['total'] ?? 0); ?></span>
                    <span class="ev-impacto-dash__colab-label"><?php esc_html_e('Eventos colaborativos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="ev-impacto-dash__colab-stat">
                    <span class="ev-impacto-dash__colab-valor"><?php echo esc_html($metricas['colaboraciones']['comunidades'] ?? 0); ?></span>
                    <span class="ev-impacto-dash__colab-label"><?php esc_html_e('Comunidades participantes', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Llamada a la acción -->
    <div class="ev-impacto-dash__cta">
        <h4><?php esc_html_e('¿Cómo aumentar el impacto?', 'flavor-chat-ia'); ?></h4>
        <ul>
            <li>
                <span class="dashicons dashicons-universal-access"></span>
                <?php esc_html_e('Organiza eventos accesibles para todos', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Reserva plazas solidarias', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-palmtree"></span>
                <?php esc_html_e('Compensa la huella de carbono', 'flavor-chat-ia'); ?>
            </li>
            <li>
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('Colabora con otras comunidades', 'flavor-chat-ia'); ?>
            </li>
        </ul>
    </div>
</div>
<style>
.ev-impacto-dash{--ev-primary:#673ab7;--ev-primary-light:#ede7f6;--ev-success:#4caf50;--ev-text:#333;--ev-text-light:#666;--ev-border:#e0e0e0;background:#fff;border:1px solid var(--ev-border);border-radius:12px;padding:1.5rem}
.ev-impacto-dash__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.ev-impacto-dash__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--ev-primary);border-radius:50%}
.ev-impacto-dash__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.ev-impacto-dash__header h3{margin:0;font-size:1.1rem}
.ev-impacto-dash__periodo{font-size:.85rem;color:var(--ev-text-light)}
.ev-impacto-dash__metricas{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.5rem}
.ev-impacto-dash__metrica{text-align:center;padding:1rem .5rem;background:#f5f5f5;border-radius:10px}
.ev-impacto-dash__metrica-icono{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;margin-bottom:.5rem}
.ev-impacto-dash__metrica-icono .dashicons{color:#fff;font-size:1.1rem;width:1.1rem;height:1.1rem}
.ev-impacto-dash__metrica-valor{display:block;font-size:1.5rem;font-weight:700;line-height:1.2}
.ev-impacto-dash__metrica-label{font-size:.7rem;color:var(--ev-text-light)}
.ev-impacto-dash__inclusividad{margin-bottom:1.5rem;padding:1rem;background:var(--ev-primary-light);border-radius:10px}
.ev-impacto-dash__inclusividad h4{display:flex;align-items:center;gap:.5rem;margin:0 0 1rem;font-size:.9rem;color:var(--ev-primary)}
.ev-impacto-dash__inclu-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
.ev-impacto-dash__inclu-item{text-align:center}
.ev-impacto-dash__inclu-ring{position:relative;width:70px;height:70px;margin:0 auto .5rem;border-radius:50%;background:conic-gradient(var(--color) calc(var(--porcentaje) * 1%),#e0e0e0 0);display:flex;align-items:center;justify-content:center}
.ev-impacto-dash__inclu-ring::before{content:'';position:absolute;width:54px;height:54px;background:#fff;border-radius:50%}
.ev-impacto-dash__inclu-valor{position:relative;z-index:1;font-size:1rem;font-weight:700}
.ev-impacto-dash__inclu-label{font-size:.75rem;color:var(--ev-text-light)}
.ev-impacto-dash__co2{padding:1rem;background:linear-gradient(135deg,#e8f5e9,#c8e6c9);border-radius:10px;margin-bottom:1.5rem}
.ev-impacto-dash__co2-header{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;font-weight:500}
.ev-impacto-dash__co2-header .dashicons{color:#2e7d32}
.ev-impacto-dash__co2-body{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:.75rem}
.ev-impacto-dash__co2-emitido,.ev-impacto-dash__co2-compensado{text-align:center;padding:.75rem;border-radius:8px}
.ev-impacto-dash__co2-emitido{background:rgba(244,67,54,.1)}
.ev-impacto-dash__co2-compensado{background:rgba(76,175,80,.1)}
.ev-impacto-dash__co2-numero{display:block;font-size:1.5rem;font-weight:700}
.ev-impacto-dash__co2-emitido .ev-impacto-dash__co2-numero{color:#c62828}
.ev-impacto-dash__co2-compensado .ev-impacto-dash__co2-numero{color:#2e7d32}
.ev-impacto-dash__co2-unidad{font-size:.75rem;color:var(--ev-text-light)}
.ev-impacto-dash__co2-balance{display:flex;align-items:center;gap:.75rem}
.ev-impacto-dash__co2-bar{flex:1;height:10px;background:#e0e0e0;border-radius:5px;overflow:hidden}
.ev-impacto-dash__co2-fill{height:100%;background:var(--ev-success);border-radius:5px}
.ev-impacto-dash__co2-text{font-size:.8rem;font-weight:500;color:var(--ev-success)}
.ev-impacto-dash__destacados{margin-bottom:1.5rem}
.ev-impacto-dash__destacados h4{margin:0 0 1rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-impacto-dash__destacados-lista{display:grid;gap:.75rem}
.ev-impacto-dash__destacado{display:flex;align-items:center;justify-content:space-between;padding:.75rem;border:1px solid var(--ev-border);border-radius:8px}
.ev-impacto-dash__destacado:hover{border-color:var(--ev-primary)}
.ev-impacto-dash__destacado-info strong{display:block;font-size:.9rem}
.ev-impacto-dash__destacado-fecha{font-size:.75rem;color:var(--ev-text-light)}
.ev-impacto-dash__destacado-stats{display:flex;gap:.75rem}
.ev-impacto-dash__destacado-stats span{display:flex;align-items:center;gap:.25rem;font-size:.85rem;color:var(--ev-text-light)}
.ev-impacto-dash__destacado-stats .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.ev-impacto-dash__destacado-solidario{color:#e91e63!important}
.ev-impacto-dash__colaboraciones{padding:1rem;background:#e3f2fd;border-radius:10px;margin-bottom:1.5rem}
.ev-impacto-dash__colaboraciones h4{display:flex;align-items:center;gap:.5rem;margin:0 0 1rem;font-size:.9rem;color:#1565c0}
.ev-impacto-dash__colab-stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.ev-impacto-dash__colab-stat{text-align:center;padding:.75rem;background:#fff;border-radius:8px}
.ev-impacto-dash__colab-valor{display:block;font-size:1.75rem;font-weight:700;color:#1565c0}
.ev-impacto-dash__colab-label{font-size:.75rem;color:var(--ev-text-light)}
.ev-impacto-dash__cta{padding:1rem;background:#f5f5f5;border-radius:10px}
.ev-impacto-dash__cta h4{margin:0 0 .75rem;font-size:.9rem}
.ev-impacto-dash__cta ul{margin:0;padding:0;list-style:none}
.ev-impacto-dash__cta li{display:flex;align-items:center;gap:.5rem;padding:.4rem 0;font-size:.85rem;color:var(--ev-text-light)}
.ev-impacto-dash__cta li .dashicons{color:var(--ev-primary);font-size:1rem;width:1rem;height:1rem}
@media(max-width:600px){.ev-impacto-dash__metricas{grid-template-columns:repeat(2,1fr)}.ev-impacto-dash__inclu-grid{grid-template-columns:1fr}.ev-impacto-dash__co2-body{grid-template-columns:1fr}}
</style>
