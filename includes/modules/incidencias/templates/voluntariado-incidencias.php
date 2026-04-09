<?php
/**
 * Template: Voluntariado para resolver incidencias
 * @var array $incidencias Incidencias que pueden resolver voluntarios
 * @var array $mis_voluntariados Mis voluntariados activos
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;
?>
<div class="inc-voluntariado" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="inc-voluntariado__header">
        <span class="inc-voluntariado__icono">
            <span class="dashicons dashicons-heart"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Voluntariado Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Ayuda a resolver incidencias menores en tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <?php if (!empty($mis_voluntariados)): ?>
        <div class="inc-voluntariado__mis-compromisos">
            <h4>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Mis compromisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>
            <div class="inc-voluntariado__compromisos-lista">
                <?php foreach ($mis_voluntariados as $vol): ?>
                    <div class="inc-voluntariado__compromiso" data-id="<?php echo esc_attr($vol->id); ?>">
                        <div class="inc-voluntariado__compromiso-info">
                            <strong><?php echo esc_html($vol->titulo); ?></strong>
                            <span class="inc-voluntariado__tipo-ayuda">
                                <?php
                                $tipos = [
                                    'reparar' => __('Reparación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'limpiar' => __('Limpieza', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'vigilar' => __('Vigilancia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'coordinar' => __('Coordinación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                echo esc_html($tipos[$vol->tipo_ayuda] ?? $vol->tipo_ayuda);
                                ?>
                            </span>
                            <span class="inc-voluntariado__direccion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($vol->direccion); ?>
                            </span>
                        </div>
                        <div class="inc-voluntariado__compromiso-acciones">
                            <span class="inc-voluntariado__estado inc-voluntariado__estado--<?php echo esc_attr($vol->estado); ?>">
                                <?php
                                $estados = [
                                    'ofrecida' => __('Ofrecida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'aceptada' => __('Aceptada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                echo esc_html($estados[$vol->estado] ?? $vol->estado);
                                ?>
                            </span>
                            <?php if ($vol->estado === 'en_proceso'): ?>
                                <button type="button" class="inc-btn inc-btn--sm inc-btn--success inc-marcar-completado" data-id="<?php echo esc_attr($vol->id); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($incidencias)): ?>
        <div class="inc-voluntariado__vacio">
            <span class="dashicons dashicons-smiley"></span>
            <p><?php esc_html_e('No hay incidencias disponibles para voluntariado en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else: ?>
        <div class="inc-voluntariado__disponibles">
            <h4><?php esc_html_e('Incidencias donde puedes ayudar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="inc-voluntariado__lista">
                <?php foreach ($incidencias as $inc): ?>
                    <?php
                    $iconos_cat = [
                        'limpieza' => 'dashicons-trash',
                        'parques' => 'dashicons-palmtree',
                        'mobiliario' => 'dashicons-admin-tools',
                        'senalizacion' => 'dashicons-warning',
                    ];
                    $icono = $iconos_cat[$inc->categoria] ?? 'dashicons-marker';
                    ?>
                    <div class="inc-voluntariado__incidencia" data-id="<?php echo esc_attr($inc->id); ?>">
                        <div class="inc-voluntariado__inc-header">
                            <span class="inc-voluntariado__cat-icono">
                                <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                            </span>
                            <div class="inc-voluntariado__inc-info">
                                <strong><?php echo esc_html($inc->titulo); ?></strong>
                                <span class="inc-voluntariado__categoria"><?php echo esc_html(ucfirst(str_replace('_', ' ', $inc->categoria))); ?></span>
                            </div>
                            <?php if ($inc->votos > 0): ?>
                                <span class="inc-voluntariado__votos">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo esc_html($inc->votos); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="inc-voluntariado__descripcion">
                            <?php echo esc_html(wp_trim_words($inc->descripcion, 20)); ?>
                        </p>

                        <div class="inc-voluntariado__meta">
                            <span>
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($inc->direccion ?: __('Sin ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </span>
                            <span>
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html(human_time_diff(strtotime($inc->fecha_creacion), current_time('timestamp'))); ?>
                            </span>
                            <?php if ($inc->voluntarios_activos > 0): ?>
                                <span class="inc-voluntariado__ya-ayudando">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(esc_html__('%d ayudando', FLAVOR_PLATFORM_TEXT_DOMAIN), $inc->voluntarios_activos); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="inc-voluntariado__acciones">
                            <select class="inc-voluntariado__tipo-select">
                                <option value="reparar"><?php esc_html_e('Puedo reparar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="limpiar"><?php esc_html_e('Puedo limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="vigilar"><?php esc_html_e('Puedo vigilar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="coordinar"><?php esc_html_e('Puedo coordinar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="otro"><?php esc_html_e('Otra ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                            <button type="button" class="inc-btn inc-btn--primary inc-ofrecer-ayuda" data-incidencia="<?php echo esc_attr($inc->id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Ofrecer ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="inc-voluntariado__beneficios">
        <h4><?php esc_html_e('Beneficios del voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="inc-voluntariado__beneficios-grid">
            <div class="inc-voluntariado__beneficio">
                <span class="dashicons dashicons-star-filled"></span>
                <span><?php esc_html_e('15 puntos por hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="inc-voluntariado__beneficio">
                <span class="dashicons dashicons-awards"></span>
                <span><?php esc_html_e('Logros especiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="inc-voluntariado__beneficio">
                <span class="dashicons dashicons-groups"></span>
                <span><?php esc_html_e('Mejora tu barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="inc-voluntariado__beneficio">
                <span class="dashicons dashicons-heart"></span>
                <span><?php esc_html_e('Reconocimiento vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>
<style>
.inc-voluntariado{--inc-primary:#e91e63;--inc-primary-light:#fce4ec;--inc-success:#4caf50;--inc-warning:#ff9800;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem}
.inc-voluntariado__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-voluntariado__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--inc-primary);border-radius:50%}
.inc-voluntariado__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.inc-voluntariado__header h3{margin:0;font-size:1.1rem}
.inc-voluntariado__header p{margin:0;font-size:.85rem;color:var(--inc-text-light)}
.inc-voluntariado__mis-compromisos{padding:1rem;background:var(--inc-primary-light);border-radius:10px;margin-bottom:1.5rem}
.inc-voluntariado__mis-compromisos h4{display:flex;align-items:center;gap:.5rem;margin:0 0 1rem;font-size:.95rem;color:var(--inc-primary)}
.inc-voluntariado__compromisos-lista{display:grid;gap:.75rem}
.inc-voluntariado__compromiso{display:flex;align-items:center;justify-content:space-between;padding:.75rem;background:#fff;border-radius:8px}
.inc-voluntariado__compromiso-info strong{display:block;font-size:.9rem}
.inc-voluntariado__tipo-ayuda{display:inline-block;padding:.15rem .4rem;background:var(--inc-primary);color:#fff;border-radius:8px;font-size:.7rem;margin-top:.25rem}
.inc-voluntariado__direccion{display:flex;align-items:center;gap:.25rem;font-size:.75rem;color:var(--inc-text-light);margin-top:.25rem}
.inc-voluntariado__direccion .dashicons{font-size:.8rem;width:.8rem;height:.8rem}
.inc-voluntariado__compromiso-acciones{display:flex;align-items:center;gap:.5rem}
.inc-voluntariado__estado{padding:.2rem .5rem;border-radius:10px;font-size:.7rem;font-weight:500}
.inc-voluntariado__estado--ofrecida{background:#fff3e0;color:#e65100}
.inc-voluntariado__estado--aceptada{background:#e3f2fd;color:#1565c0}
.inc-voluntariado__estado--en_proceso{background:#e8f5e9;color:#2e7d32}
.inc-voluntariado__vacio{text-align:center;padding:2rem}
.inc-voluntariado__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.inc-voluntariado__vacio p{color:var(--inc-text-light);margin:.5rem 0 0}
.inc-voluntariado__disponibles h4{margin:0 0 1rem;font-size:.95rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-voluntariado__lista{display:grid;gap:1rem;margin-bottom:1.5rem}
.inc-voluntariado__incidencia{padding:1rem;border:1px solid var(--inc-border);border-radius:10px}
.inc-voluntariado__incidencia:hover{border-color:var(--inc-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.inc-voluntariado__inc-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem}
.inc-voluntariado__cat-icono{display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:var(--inc-primary-light);border-radius:10px}
.inc-voluntariado__cat-icono .dashicons{color:var(--inc-primary);font-size:1.25rem;width:1.25rem;height:1.25rem}
.inc-voluntariado__inc-info{flex:1}
.inc-voluntariado__inc-info strong{display:block;font-size:.95rem}
.inc-voluntariado__categoria{font-size:.75rem;color:var(--inc-text-light)}
.inc-voluntariado__votos{display:flex;align-items:center;gap:.25rem;padding:.25rem .5rem;background:#e8f5e9;color:#2e7d32;border-radius:12px;font-size:.8rem}
.inc-voluntariado__votos .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-voluntariado__descripcion{margin:0 0 .75rem;font-size:.9rem;color:var(--inc-text-light)}
.inc-voluntariado__meta{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem;font-size:.8rem;color:var(--inc-text-light)}
.inc-voluntariado__meta span{display:flex;align-items:center;gap:.25rem}
.inc-voluntariado__meta .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-voluntariado__ya-ayudando{color:var(--inc-primary)!important}
.inc-voluntariado__acciones{display:flex;gap:.75rem}
.inc-voluntariado__tipo-select{flex:1;padding:.5rem;border:1px solid var(--inc-border);border-radius:8px;font-size:.85rem}
.inc-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer}
.inc-btn--primary{background:var(--inc-primary);color:#fff}
.inc-btn--primary:hover{background:#c2185b}
.inc-btn--success{background:var(--inc-success);color:#fff}
.inc-btn--sm{padding:.3rem .6rem;font-size:.8rem}
.inc-btn .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-voluntariado__beneficios{padding:1rem;background:#f5f5f5;border-radius:10px}
.inc-voluntariado__beneficios h4{margin:0 0 .75rem;font-size:.9rem}
.inc-voluntariado__beneficios-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem}
.inc-voluntariado__beneficio{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--inc-text-light)}
.inc-voluntariado__beneficio .dashicons{color:var(--inc-primary);font-size:1rem;width:1rem;height:1rem}
@media(max-width:600px){.inc-voluntariado__acciones{flex-direction:column}.inc-voluntariado__beneficios-grid{grid-template-columns:1fr}}
</style>
