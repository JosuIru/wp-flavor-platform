<?php
/**
 * Template: Voluntariado para Eventos
 * @var array $tareas Lista de tareas de voluntariado disponibles
 * @var array $mis_inscripciones Inscripciones del usuario actual
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;
$usuario_logueado = is_user_logged_in();
?>
<div class="ev-voluntariado" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="ev-voluntariado__header">
        <span class="ev-voluntariado__icono">
            <span class="dashicons dashicons-heart"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Voluntariado en Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Colabora en la organización de eventos comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <?php if (!empty($mis_inscripciones)): ?>
        <!-- Mis compromisos -->
        <div class="ev-voluntariado__mis-compromisos">
            <h4>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Mis compromisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>
            <div class="ev-voluntariado__compromisos-lista">
                <?php foreach ($mis_inscripciones as $inscripcion): ?>
                    <div class="ev-voluntariado__compromiso">
                        <div class="ev-voluntariado__compromiso-info">
                            <strong><?php echo esc_html($inscripcion->tarea_nombre); ?></strong>
                            <span class="ev-voluntariado__compromiso-evento">
                                <?php echo esc_html($inscripcion->evento_titulo); ?>
                            </span>
                            <span class="ev-voluntariado__compromiso-fecha">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($inscripcion->fecha_inicio))); ?>
                            </span>
                        </div>
                        <span class="ev-voluntariado__compromiso-estado ev-voluntariado__compromiso-estado--<?php echo esc_attr($inscripcion->estado); ?>">
                            <?php
                            $estados = [
                                'confirmado' => __('Confirmado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'completado' => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            ];
                            echo esc_html($estados[$inscripcion->estado] ?? $inscripcion->estado);
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tareas disponibles -->
    <?php if (empty($tareas)): ?>
        <div class="ev-voluntariado__vacio">
            <span class="dashicons dashicons-smiley"></span>
            <p><?php esc_html_e('No hay tareas de voluntariado disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else: ?>
        <div class="ev-voluntariado__tareas">
            <h4><?php esc_html_e('Tareas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <?php foreach ($tareas as $tarea): ?>
                <?php
                $plazas_disponibles = $tarea->plazas_totales - $tarea->plazas_ocupadas;
                $porcentaje_ocupacion = $tarea->plazas_totales > 0 ? ($tarea->plazas_ocupadas / $tarea->plazas_totales) * 100 : 0;
                ?>
                <div class="ev-voluntariado__tarea" data-tarea="<?php echo esc_attr($tarea->id); ?>">
                    <div class="ev-voluntariado__tarea-header">
                        <div class="ev-voluntariado__tarea-icono">
                            <?php
                            $iconos_tipo = [
                                'montaje' => 'dashicons-hammer',
                                'atencion' => 'dashicons-groups',
                                'limpieza' => 'dashicons-trash',
                                'seguridad' => 'dashicons-shield',
                                'logistica' => 'dashicons-car',
                                'comunicacion' => 'dashicons-megaphone',
                            ];
                            $icono = $iconos_tipo[$tarea->tipo] ?? 'dashicons-star-filled';
                            ?>
                            <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                        </div>
                        <div class="ev-voluntariado__tarea-info">
                            <h5><?php echo esc_html($tarea->nombre); ?></h5>
                            <span class="ev-voluntariado__tarea-evento">
                                <?php echo esc_html($tarea->evento_titulo); ?>
                            </span>
                        </div>
                        <?php if ($tarea->puntos_recompensa > 0): ?>
                            <span class="ev-voluntariado__tarea-puntos">
                                +<?php echo esc_html($tarea->puntos_recompensa); ?> pts
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="ev-voluntariado__tarea-desc">
                        <?php echo esc_html($tarea->descripcion); ?>
                    </p>

                    <div class="ev-voluntariado__tarea-detalles">
                        <span class="ev-voluntariado__detalle">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n('j M Y', strtotime($tarea->fecha_inicio))); ?>
                        </span>
                        <span class="ev-voluntariado__detalle">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html(date_i18n('H:i', strtotime($tarea->fecha_inicio))); ?> -
                            <?php echo esc_html(date_i18n('H:i', strtotime($tarea->fecha_fin))); ?>
                        </span>
                        <span class="ev-voluntariado__detalle">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($tarea->ubicacion ?: __('Por determinar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </span>
                    </div>

                    <?php if (!empty($tarea->requisitos)): ?>
                        <div class="ev-voluntariado__requisitos">
                            <strong><?php esc_html_e('Requisitos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <?php echo esc_html($tarea->requisitos); ?>
                        </div>
                    <?php endif; ?>

                    <div class="ev-voluntariado__tarea-footer">
                        <div class="ev-voluntariado__plazas">
                            <div class="ev-voluntariado__plazas-bar">
                                <div class="ev-voluntariado__plazas-fill" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%"></div>
                            </div>
                            <span class="ev-voluntariado__plazas-texto">
                                <?php printf(
                                    esc_html__('%d/%d plazas ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    $tarea->plazas_ocupadas,
                                    $tarea->plazas_totales
                                ); ?>
                            </span>
                        </div>
                        <?php if ($usuario_logueado && $plazas_disponibles > 0): ?>
                            <button type="button" class="ev-btn ev-btn--primary ev-inscribir-voluntariado" data-tarea="<?php echo esc_attr($tarea->id); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e('Apuntarme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php elseif ($plazas_disponibles <= 0): ?>
                            <span class="ev-voluntariado__completo">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Beneficios del voluntariado -->
    <div class="ev-voluntariado__beneficios">
        <h4><?php esc_html_e('¿Por qué ser voluntario?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="ev-voluntariado__beneficios-grid">
            <div class="ev-voluntariado__beneficio">
                <span class="dashicons dashicons-star-filled"></span>
                <span><?php esc_html_e('Gana puntos de participación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="ev-voluntariado__beneficio">
                <span class="dashicons dashicons-groups"></span>
                <span><?php esc_html_e('Conoce a tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="ev-voluntariado__beneficio">
                <span class="dashicons dashicons-awards"></span>
                <span><?php esc_html_e('Desbloquea logros especiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="ev-voluntariado__beneficio">
                <span class="dashicons dashicons-heart"></span>
                <span><?php esc_html_e('Contribuye al bien común', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>
<style>
.ev-voluntariado{--ev-primary:#e91e63;--ev-primary-light:#fce4ec;--ev-success:#4caf50;--ev-warning:#ff9800;--ev-text:#333;--ev-text-light:#666;--ev-border:#e0e0e0;background:#fff;border:1px solid var(--ev-border);border-radius:12px;padding:1.5rem}
.ev-voluntariado__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.ev-voluntariado__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--ev-primary);border-radius:50%}
.ev-voluntariado__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.ev-voluntariado__header h3{margin:0;font-size:1.1rem}
.ev-voluntariado__header p{margin:0;font-size:.85rem;color:var(--ev-text-light)}
.ev-voluntariado__mis-compromisos{padding:1rem;background:var(--ev-primary-light);border-radius:10px;margin-bottom:1.5rem}
.ev-voluntariado__mis-compromisos h4{display:flex;align-items:center;gap:.5rem;margin:0 0 1rem;font-size:.95rem;color:var(--ev-primary)}
.ev-voluntariado__compromisos-lista{display:grid;gap:.75rem}
.ev-voluntariado__compromiso{display:flex;align-items:center;justify-content:space-between;padding:.75rem;background:#fff;border-radius:8px}
.ev-voluntariado__compromiso-info strong{display:block;font-size:.9rem}
.ev-voluntariado__compromiso-evento{display:block;font-size:.8rem;color:var(--ev-text-light)}
.ev-voluntariado__compromiso-fecha{display:flex;align-items:center;gap:.25rem;font-size:.75rem;color:var(--ev-text-light);margin-top:.25rem}
.ev-voluntariado__compromiso-fecha .dashicons{font-size:.85rem;width:.85rem;height:.85rem}
.ev-voluntariado__compromiso-estado{padding:.25rem .5rem;border-radius:12px;font-size:.75rem;font-weight:600}
.ev-voluntariado__compromiso-estado--confirmado{background:#e8f5e9;color:#2e7d32}
.ev-voluntariado__compromiso-estado--pendiente{background:#fff3e0;color:#e65100}
.ev-voluntariado__compromiso-estado--completado{background:#e3f2fd;color:#1565c0}
.ev-voluntariado__vacio{text-align:center;padding:2rem}
.ev-voluntariado__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.ev-voluntariado__vacio p{color:var(--ev-text-light);margin:.5rem 0 0}
.ev-voluntariado__tareas h4{margin:0 0 1rem;font-size:.95rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-voluntariado__tarea{padding:1rem;border:1px solid var(--ev-border);border-radius:10px;margin-bottom:1rem}
.ev-voluntariado__tarea:hover{border-color:var(--ev-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.ev-voluntariado__tarea-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.ev-voluntariado__tarea-icono{display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:var(--ev-primary-light);border-radius:10px}
.ev-voluntariado__tarea-icono .dashicons{color:var(--ev-primary);font-size:1.25rem;width:1.25rem;height:1.25rem}
.ev-voluntariado__tarea-info{flex:1}
.ev-voluntariado__tarea-info h5{margin:0;font-size:1rem}
.ev-voluntariado__tarea-evento{font-size:.8rem;color:var(--ev-text-light)}
.ev-voluntariado__tarea-puntos{padding:.25rem .6rem;background:linear-gradient(135deg,#ffc107,#ffb300);color:#333;border-radius:15px;font-size:.75rem;font-weight:600}
.ev-voluntariado__tarea-desc{margin:0 0 .75rem;font-size:.9rem;color:var(--ev-text-light)}
.ev-voluntariado__tarea-detalles{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem}
.ev-voluntariado__detalle{display:flex;align-items:center;gap:.25rem;font-size:.8rem;color:var(--ev-text-light)}
.ev-voluntariado__detalle .dashicons{font-size:.9rem;width:.9rem;height:.9rem;color:var(--ev-primary)}
.ev-voluntariado__requisitos{padding:.5rem;background:#f5f5f5;border-radius:6px;font-size:.8rem;color:var(--ev-text-light);margin-bottom:.75rem}
.ev-voluntariado__tarea-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem}
.ev-voluntariado__plazas{flex:1}
.ev-voluntariado__plazas-bar{height:6px;background:#e0e0e0;border-radius:3px;overflow:hidden;margin-bottom:.25rem}
.ev-voluntariado__plazas-fill{height:100%;background:var(--ev-primary);border-radius:3px}
.ev-voluntariado__plazas-texto{font-size:.75rem;color:var(--ev-text-light)}
.ev-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer;transition:all .2s}
.ev-btn--primary{background:var(--ev-primary);color:#fff}
.ev-btn--primary:hover{background:#c2185b}
.ev-btn .dashicons{font-size:1rem;width:1rem;height:1rem}
.ev-voluntariado__completo{display:flex;align-items:center;gap:.25rem;color:var(--ev-success);font-size:.85rem;font-weight:500}
.ev-voluntariado__beneficios{margin-top:1.5rem;padding:1rem;background:#f5f5f5;border-radius:10px}
.ev-voluntariado__beneficios h4{margin:0 0 1rem;font-size:.9rem}
.ev-voluntariado__beneficios-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem}
.ev-voluntariado__beneficio{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--ev-text-light)}
.ev-voluntariado__beneficio .dashicons{color:var(--ev-primary);font-size:1rem;width:1rem;height:1rem}
@media(max-width:600px){.ev-voluntariado__tarea-footer{flex-direction:column;align-items:stretch}.ev-voluntariado__beneficios-grid{grid-template-columns:1fr}}
</style>
