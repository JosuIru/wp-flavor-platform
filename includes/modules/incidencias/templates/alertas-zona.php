<?php
/**
 * Template: Alertas de mi zona
 * @var array $alertas Lista de alertas activas
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;
?>
<div class="inc-alertas" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="inc-alertas__header">
        <span class="inc-alertas__icono">
            <span class="dashicons dashicons-bell"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Alertas de tu Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Incidencias que afectan a tu entorno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <?php if (empty($alertas)): ?>
        <div class="inc-alertas__vacio">
            <span class="dashicons dashicons-smiley"></span>
            <p><?php esc_html_e('No hay alertas activas en tu zona. ¡Todo en orden!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else: ?>
        <div class="inc-alertas__lista">
            <?php foreach ($alertas as $alerta): ?>
                <div class="inc-alertas__alerta inc-alertas__alerta--<?php echo esc_attr($alerta->tipo_alerta); ?>">
                    <div class="inc-alertas__alerta-header">
                        <?php
                        $iconos = [
                            'urgente' => 'dashicons-warning',
                            'moderada' => 'dashicons-info',
                            'informativa' => 'dashicons-megaphone',
                        ];
                        $icono = $iconos[$alerta->tipo_alerta] ?? 'dashicons-info';
                        ?>
                        <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                        <div class="inc-alertas__alerta-titulo">
                            <strong><?php echo esc_html($alerta->titulo); ?></strong>
                            <span class="inc-alertas__categoria"><?php echo esc_html(ucfirst($alerta->categoria)); ?></span>
                        </div>
                        <span class="inc-alertas__tipo-badge">
                            <?php
                            $tipos = [
                                'urgente' => __('Urgente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'moderada' => __('Moderada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'informativa' => __('Info', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            ];
                            echo esc_html($tipos[$alerta->tipo_alerta] ?? $alerta->tipo_alerta);
                            ?>
                        </span>
                    </div>
                    <p class="inc-alertas__mensaje"><?php echo esc_html($alerta->mensaje); ?></p>
                    <div class="inc-alertas__meta">
                        <span>
                            <span class="dashicons dashicons-location"></span>
                            <?php printf(esc_html__('Radio: %d metros', FLAVOR_PLATFORM_TEXT_DOMAIN), $alerta->radio_metros); ?>
                        </span>
                        <span>
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html(human_time_diff(strtotime($alerta->fecha_creacion), current_time('timestamp'))); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="inc-alertas__config">
        <h4><?php esc_html_e('Configurar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="inc-alertas__opciones">
            <label>
                <input type="checkbox" id="inc-notif-urgentes" checked>
                <?php esc_html_e('Alertas urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <label>
                <input type="checkbox" id="inc-notif-moderadas" checked>
                <?php esc_html_e('Alertas moderadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <label>
                <input type="checkbox" id="inc-notif-informativas">
                <?php esc_html_e('Alertas informativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
        </div>
    </div>
</div>
<style>
.inc-alertas{--inc-primary:#f44336;--inc-warning:#ff9800;--inc-info:#2196f3;--inc-success:#4caf50;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem}
.inc-alertas__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-alertas__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--inc-primary);border-radius:50%}
.inc-alertas__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.inc-alertas__header h3{margin:0;font-size:1.1rem}
.inc-alertas__header p{margin:0;font-size:.85rem;color:var(--inc-text-light)}
.inc-alertas__vacio{text-align:center;padding:2rem}
.inc-alertas__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:var(--inc-success)}
.inc-alertas__vacio p{color:var(--inc-text-light);margin:.5rem 0 0}
.inc-alertas__lista{display:grid;gap:1rem;margin-bottom:1.5rem}
.inc-alertas__alerta{padding:1rem;border-radius:10px;border-left:4px solid}
.inc-alertas__alerta--urgente{background:#ffebee;border-left-color:var(--inc-primary)}
.inc-alertas__alerta--moderada{background:#fff3e0;border-left-color:var(--inc-warning)}
.inc-alertas__alerta--informativa{background:#e3f2fd;border-left-color:var(--inc-info)}
.inc-alertas__alerta-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.5rem}
.inc-alertas__alerta--urgente .inc-alertas__alerta-header .dashicons{color:var(--inc-primary)}
.inc-alertas__alerta--moderada .inc-alertas__alerta-header .dashicons{color:var(--inc-warning)}
.inc-alertas__alerta--informativa .inc-alertas__alerta-header .dashicons{color:var(--inc-info)}
.inc-alertas__alerta-titulo{flex:1}
.inc-alertas__alerta-titulo strong{display:block;font-size:.95rem}
.inc-alertas__categoria{font-size:.75rem;color:var(--inc-text-light)}
.inc-alertas__tipo-badge{padding:.2rem .5rem;border-radius:10px;font-size:.7rem;font-weight:600;text-transform:uppercase}
.inc-alertas__alerta--urgente .inc-alertas__tipo-badge{background:var(--inc-primary);color:#fff}
.inc-alertas__alerta--moderada .inc-alertas__tipo-badge{background:var(--inc-warning);color:#fff}
.inc-alertas__alerta--informativa .inc-alertas__tipo-badge{background:var(--inc-info);color:#fff}
.inc-alertas__mensaje{margin:0 0 .75rem;font-size:.9rem;color:var(--inc-text)}
.inc-alertas__meta{display:flex;gap:1rem;font-size:.8rem;color:var(--inc-text-light)}
.inc-alertas__meta span{display:flex;align-items:center;gap:.25rem}
.inc-alertas__meta .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-alertas__config{padding:1rem;background:#f5f5f5;border-radius:10px}
.inc-alertas__config h4{margin:0 0 .75rem;font-size:.9rem}
.inc-alertas__opciones{display:flex;flex-wrap:wrap;gap:1rem}
.inc-alertas__opciones label{display:flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer}
</style>
