<?php
/**
 * Template: Eventos Inclusivos
 * @var array $eventos Lista de eventos con opciones de inclusividad
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;
$usuario_logueado = is_user_logged_in();
?>
<div class="ev-inclusivos" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="ev-inclusivos__header">
        <span class="dashicons dashicons-universal-access-alt"></span>
        <h3><?php esc_html_e('Eventos Inclusivos', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Eventos con accesibilidad y plazas solidarias disponibles', 'flavor-chat-ia'); ?></p>
    </div>
    <?php if (empty($eventos)): ?>
        <div class="ev-inclusivos__vacio">
            <p><?php esc_html_e('No hay eventos inclusivos programados próximamente.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <div class="ev-inclusivos__lista">
            <?php foreach ($eventos as $evento): ?>
                <div class="ev-inclusivos__evento">
                    <div class="ev-inclusivos__evento-header">
                        <h4><?php echo esc_html($evento->titulo); ?></h4>
                        <span class="ev-inclusivos__fecha">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($evento->fecha_inicio))); ?>
                        </span>
                    </div>
                    <div class="ev-inclusivos__badges">
                        <?php if ($evento->accesibilidad_fisica): ?>
                            <span class="ev-badge" title="<?php esc_attr_e('Acceso para silla de ruedas', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-universal-access"></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($evento->interprete_lse): ?>
                            <span class="ev-badge" title="<?php esc_attr_e('Intérprete de lengua de signos', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-format-status"></span> LSE
                            </span>
                        <?php endif; ?>
                        <?php if ($evento->cuidado_infantil): ?>
                            <span class="ev-badge" title="<?php esc_attr_e('Servicio de guardería', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-smiley"></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($evento->bucle_magnetico): ?>
                            <span class="ev-badge" title="<?php esc_attr_e('Bucle magnético', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-megaphone"></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($evento->plazas_solidarias > 0): ?>
                        <?php $disponibles = $evento->plazas_solidarias - $evento->plazas_solidarias_usadas; ?>
                        <div class="ev-inclusivos__solidarias">
                            <span class="dashicons dashicons-heart"></span>
                            <span>
                                <?php printf(
                                    esc_html__('%d plazas solidarias disponibles', 'flavor-chat-ia'),
                                    $disponibles
                                ); ?>
                                <?php if ($evento->precio_solidario > 0): ?>
                                    (<?php echo esc_html(number_format($evento->precio_solidario, 2)); ?>€)
                                <?php else: ?>
                                    (<?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>)
                                <?php endif; ?>
                            </span>
                            <?php if ($usuario_logueado && $disponibles > 0): ?>
                                <button type="button" class="ev-btn ev-btn--sm ev-solicitar-solidaria" data-evento="<?php echo esc_attr($evento->id); ?>">
                                    <?php esc_html_e('Solicitar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<style>
.ev-inclusivos{--ev-primary:#673ab7;--ev-primary-light:#ede7f6;--ev-heart:#e91e63;background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:1.5rem}
.ev-inclusivos__header{text-align:center;margin-bottom:1.5rem}
.ev-inclusivos__header .dashicons{color:var(--ev-primary);font-size:2rem;width:2rem;height:2rem}
.ev-inclusivos__header h3{margin:.5rem 0 .25rem}
.ev-inclusivos__header p{margin:0;color:#666;font-size:.9rem}
.ev-inclusivos__lista{display:grid;gap:1rem}
.ev-inclusivos__evento{padding:1rem;border:1px solid #e0e0e0;border-radius:10px}
.ev-inclusivos__evento:hover{border-color:var(--ev-primary);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.ev-inclusivos__evento-header{margin-bottom:.75rem}
.ev-inclusivos__evento-header h4{margin:0 0 .25rem}
.ev-inclusivos__fecha{display:flex;align-items:center;gap:.25rem;font-size:.85rem;color:#666}
.ev-inclusivos__badges{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem}
.ev-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.25rem .5rem;background:var(--ev-primary-light);border-radius:15px;font-size:.75rem;color:var(--ev-primary)}
.ev-badge .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.ev-inclusivos__solidarias{display:flex;align-items:center;gap:.5rem;padding:.5rem;background:#fce4ec;border-radius:8px;font-size:.85rem}
.ev-inclusivos__solidarias .dashicons{color:var(--ev-heart)}
.ev-btn{padding:.4rem .8rem;border:none;border-radius:6px;font-size:.8rem;cursor:pointer}
.ev-btn--sm{padding:.3rem .6rem}
.ev-btn{background:var(--ev-primary);color:#fff}
</style>
