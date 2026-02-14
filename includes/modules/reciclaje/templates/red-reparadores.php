<?php
/**
 * Template: Red de reparadores comunitarios
 * @var array $reparadores Lista de reparadores disponibles
 * @var array $mis_solicitudes Mis solicitudes de reparación
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;

$especialidades = [
    'electronica' => ['icono' => 'dashicons-laptop', 'color' => '#2196f3'],
    'ropa' => ['icono' => 'dashicons-universal-access', 'color' => '#9c27b0'],
    'muebles' => ['icono' => 'dashicons-admin-home', 'color' => '#795548'],
    'bicicletas' => ['icono' => 'dashicons-location-alt', 'color' => '#4caf50'],
    'electrodomesticos' => ['icono' => 'dashicons-desktop', 'color' => '#ff5722'],
    'otros' => ['icono' => 'dashicons-admin-tools', 'color' => '#607d8b'],
];
?>
<div class="rec-reparadores" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="rec-reparadores__header">
        <span class="rec-reparadores__icono">
            <span class="dashicons dashicons-admin-tools"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Red de Reparadores', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Conecta con vecinos que pueden reparar tus objetos', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="rec-reparadores__tabs">
        <button class="rec-reparadores__tab rec-reparadores__tab--active" data-tab="buscar">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
        </button>
        <button class="rec-reparadores__tab" data-tab="solicitudes">
            <?php esc_html_e('Mis solicitudes', 'flavor-chat-ia'); ?>
            <?php if (!empty($mis_solicitudes)): ?>
                <span class="rec-reparadores__tab-count"><?php echo count($mis_solicitudes); ?></span>
            <?php endif; ?>
        </button>
        <button class="rec-reparadores__tab" data-tab="ofrecer">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Ofrecer servicio', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Tab: Buscar reparadores -->
    <div class="rec-reparadores__panel rec-reparadores__panel--active" id="tab-buscar">
        <!-- Filtros -->
        <div class="rec-reparadores__filtros">
            <select class="rec-reparadores__filtro-esp" id="filtro-especialidad">
                <option value=""><?php esc_html_e('Todas las especialidades', 'flavor-chat-ia'); ?></option>
                <?php foreach ($especialidades as $key => $esp): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html(ucfirst($key)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($reparadores)): ?>
            <div class="rec-reparadores__vacio">
                <span class="dashicons dashicons-admin-tools"></span>
                <p><?php esc_html_e('No hay reparadores disponibles.', 'flavor-chat-ia'); ?></p>
                <p class="rec-reparadores__vacio-sub"><?php esc_html_e('¡Sé el primero en ofrecer tus habilidades!', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <div class="rec-reparadores__grid">
                <?php foreach ($reparadores as $rep):
                    $esp_info = $especialidades[$rep->especialidad] ?? $especialidades['otros'];
                    $valoracion = $rep->valoracion_promedio ?? 0;
                ?>
                    <div class="rec-reparadores__card" data-id="<?php echo esc_attr($rep->id); ?>" data-esp="<?php echo esc_attr($rep->especialidad); ?>">
                        <div class="rec-reparadores__card-header">
                            <div class="rec-reparadores__avatar">
                                <?php echo get_avatar($rep->usuario_id, 50); ?>
                            </div>
                            <div class="rec-reparadores__info">
                                <strong><?php echo esc_html($rep->display_name); ?></strong>
                                <span class="rec-reparadores__esp-badge" style="background: <?php echo esc_attr($esp_info['color']); ?>">
                                    <span class="dashicons <?php echo esc_attr($esp_info['icono']); ?>"></span>
                                    <?php echo esc_html(ucfirst($rep->especialidad)); ?>
                                </span>
                            </div>
                            <div class="rec-reparadores__valoracion">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="dashicons dashicons-star-<?php echo $i <= round($valoracion) ? 'filled' : 'empty'; ?>"></span>
                                <?php endfor; ?>
                                <span class="rec-reparadores__val-num"><?php echo esc_html(number_format($valoracion, 1)); ?></span>
                            </div>
                        </div>

                        <p class="rec-reparadores__descripcion"><?php echo esc_html($rep->descripcion); ?></p>

                        <div class="rec-reparadores__stats">
                            <span class="rec-reparadores__stat">
                                <span class="dashicons dashicons-hammer"></span>
                                <?php printf(esc_html__('%d reparaciones', 'flavor-chat-ia'), $rep->total_reparaciones ?? 0); ?>
                            </span>
                            <?php if ($rep->ubicacion): ?>
                                <span class="rec-reparadores__stat">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($rep->ubicacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="rec-reparadores__impacto">
                            <span class="rec-reparadores__co2">
                                <span class="dashicons dashicons-cloud"></span>
                                -<?php echo esc_html(number_format($rep->co2_total_ahorrado ?? 0, 1)); ?> kg CO₂
                            </span>
                        </div>

                        <div class="rec-reparadores__card-footer">
                            <button type="button" class="rec-btn rec-btn--primary rec-solicitar-reparacion" data-id="<?php echo esc_attr($rep->id); ?>">
                                <span class="dashicons dashicons-email"></span>
                                <?php esc_html_e('Solicitar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Mis solicitudes -->
    <div class="rec-reparadores__panel" id="tab-solicitudes">
        <?php if (empty($mis_solicitudes)): ?>
            <div class="rec-reparadores__vacio">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('No tienes solicitudes de reparación.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <div class="rec-reparadores__solicitudes">
                <?php foreach ($mis_solicitudes as $sol):
                    $esp_info = $especialidades[$sol->tipo_objeto] ?? $especialidades['otros'];
                    $estados = [
                        'pendiente' => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => '#ff9800'],
                        'aceptada' => ['label' => __('Aceptada', 'flavor-chat-ia'), 'color' => '#2196f3'],
                        'en_proceso' => ['label' => __('En proceso', 'flavor-chat-ia'), 'color' => '#9c27b0'],
                        'completada' => ['label' => __('Completada', 'flavor-chat-ia'), 'color' => '#4caf50'],
                        'cancelada' => ['label' => __('Cancelada', 'flavor-chat-ia'), 'color' => '#f44336'],
                    ];
                    $estado_info = $estados[$sol->estado] ?? $estados['pendiente'];
                ?>
                    <div class="rec-reparadores__solicitud">
                        <div class="rec-reparadores__solicitud-header">
                            <span class="rec-reparadores__tipo-badge" style="background: <?php echo esc_attr($esp_info['color']); ?>">
                                <span class="dashicons <?php echo esc_attr($esp_info['icono']); ?>"></span>
                            </span>
                            <div class="rec-reparadores__solicitud-info">
                                <strong><?php echo esc_html($sol->descripcion_objeto); ?></strong>
                                <span class="rec-reparadores__reparador-nombre">
                                    <?php printf(esc_html__('Reparador: %s', 'flavor-chat-ia'), $sol->reparador_nombre); ?>
                                </span>
                            </div>
                            <span class="rec-reparadores__estado" style="background: <?php echo esc_attr($estado_info['color']); ?>">
                                <?php echo esc_html($estado_info['label']); ?>
                            </span>
                        </div>
                        <div class="rec-reparadores__solicitud-meta">
                            <span>
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n('j M Y', strtotime($sol->fecha_solicitud))); ?>
                            </span>
                            <?php if ($sol->estado === 'completada' && $sol->co2_ahorrado > 0): ?>
                                <span class="rec-reparadores__co2-badge">
                                    <span class="dashicons dashicons-cloud"></span>
                                    -<?php echo esc_html(number_format($sol->co2_ahorrado, 1)); ?> kg CO₂
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($sol->estado === 'completada' && empty($sol->valoracion)): ?>
                            <button type="button" class="rec-btn rec-btn--outline rec-valorar-reparacion" data-id="<?php echo esc_attr($sol->id); ?>">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php esc_html_e('Valorar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Ofrecer servicio -->
    <div class="rec-reparadores__panel" id="tab-ofrecer">
        <form class="rec-reparadores__form" id="form-ofrecer-reparacion">
            <div class="rec-reparadores__form-intro">
                <span class="dashicons dashicons-heart"></span>
                <p><?php esc_html_e('Comparte tus habilidades con la comunidad y ayuda a reducir residuos.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="rec-reparadores__field">
                <label><?php esc_html_e('Especialidad principal', 'flavor-chat-ia'); ?></label>
                <select name="especialidad" required>
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                    <option value="electronica"><?php esc_html_e('Electrónica', 'flavor-chat-ia'); ?></option>
                    <option value="ropa"><?php esc_html_e('Ropa/Costura', 'flavor-chat-ia'); ?></option>
                    <option value="muebles"><?php esc_html_e('Muebles', 'flavor-chat-ia'); ?></option>
                    <option value="bicicletas"><?php esc_html_e('Bicicletas', 'flavor-chat-ia'); ?></option>
                    <option value="electrodomesticos"><?php esc_html_e('Electrodomésticos', 'flavor-chat-ia'); ?></option>
                    <option value="otros"><?php esc_html_e('Otros', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="rec-reparadores__field">
                <label><?php esc_html_e('Descripción de tus habilidades', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="3" required placeholder="<?php esc_attr_e('Ej: Reparo móviles, tablets y pequeños electrodomésticos...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="rec-reparadores__field">
                <label><?php esc_html_e('Ubicación aproximada', 'flavor-chat-ia'); ?></label>
                <input type="text" name="ubicacion" placeholder="<?php esc_attr_e('Ej: Centro, Barrio Norte...', 'flavor-chat-ia'); ?>">
            </div>

            <div class="rec-reparadores__field">
                <label>
                    <input type="checkbox" name="acepto_condiciones" required>
                    <?php esc_html_e('Acepto ofrecer mis servicios de forma solidaria o a precios justos', 'flavor-chat-ia'); ?>
                </label>
            </div>

            <button type="submit" class="rec-btn rec-btn--primary rec-btn--full">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Unirme a la red', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <!-- Impacto de la red -->
    <div class="rec-reparadores__impacto-global">
        <h4><?php esc_html_e('Impacto de nuestra red', 'flavor-chat-ia'); ?></h4>
        <div class="rec-reparadores__impacto-grid">
            <div class="rec-reparadores__impacto-item">
                <span class="rec-reparadores__impacto-valor"><?php echo esc_html(count($reparadores)); ?></span>
                <span class="rec-reparadores__impacto-label"><?php esc_html_e('Reparadores', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="rec-reparadores__impacto-item">
                <?php
                $total_reparaciones = array_sum(array_column($reparadores, 'total_reparaciones'));
                ?>
                <span class="rec-reparadores__impacto-valor"><?php echo esc_html($total_reparaciones); ?></span>
                <span class="rec-reparadores__impacto-label"><?php esc_html_e('Reparaciones', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="rec-reparadores__impacto-item">
                <?php
                $co2_total = array_sum(array_column($reparadores, 'co2_total_ahorrado'));
                ?>
                <span class="rec-reparadores__impacto-valor"><?php echo esc_html(number_format($co2_total, 0)); ?></span>
                <span class="rec-reparadores__impacto-label"><?php esc_html_e('kg CO₂ ahorrados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>
</div>
<style>
.rec-reparadores{--rec-primary:#4caf50;--rec-primary-light:#e8f5e9;--rec-text:#333;--rec-text-light:#666;--rec-border:#e0e0e0;background:#fff;border:1px solid var(--rec-border);border-radius:12px;padding:1.5rem}
.rec-reparadores__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.rec-reparadores__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--rec-primary);border-radius:50%}
.rec-reparadores__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-reparadores__header h3{margin:0;font-size:1.1rem}
.rec-reparadores__header p{margin:0;font-size:.85rem;color:var(--rec-text-light)}
.rec-reparadores__tabs{display:flex;gap:.5rem;margin-bottom:1rem;border-bottom:2px solid var(--rec-border);padding-bottom:.5rem}
.rec-reparadores__tab{display:flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;background:none;font-size:.9rem;cursor:pointer;color:var(--rec-text-light);border-radius:8px 8px 0 0}
.rec-reparadores__tab--active{background:var(--rec-primary-light);color:var(--rec-primary);font-weight:500}
.rec-reparadores__tab-count{padding:.1rem .4rem;background:var(--rec-primary);color:#fff;border-radius:10px;font-size:.75rem}
.rec-reparadores__panel{display:none}
.rec-reparadores__panel--active{display:block}
.rec-reparadores__filtros{margin-bottom:1rem}
.rec-reparadores__filtro-esp{width:100%;padding:.6rem;border:1px solid var(--rec-border);border-radius:8px;font-size:.9rem}
.rec-reparadores__vacio{text-align:center;padding:2rem}
.rec-reparadores__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.rec-reparadores__vacio p{color:var(--rec-text-light);margin:.5rem 0 0}
.rec-reparadores__vacio-sub{font-size:.85rem}
.rec-reparadores__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem}
.rec-reparadores__card{padding:1rem;border:1px solid var(--rec-border);border-radius:10px}
.rec-reparadores__card:hover{border-color:var(--rec-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.rec-reparadores__card-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem}
.rec-reparadores__avatar img{width:50px;height:50px;border-radius:50%}
.rec-reparadores__info{flex:1}
.rec-reparadores__info strong{display:block;font-size:1rem}
.rec-reparadores__esp-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .5rem;border-radius:10px;color:#fff;font-size:.75rem;margin-top:.25rem}
.rec-reparadores__esp-badge .dashicons{font-size:.85rem;width:.85rem;height:.85rem}
.rec-reparadores__valoracion{display:flex;align-items:center;gap:.1rem}
.rec-reparadores__valoracion .dashicons{font-size:.9rem;width:.9rem;height:.9rem;color:#ffc107}
.rec-reparadores__val-num{font-size:.8rem;color:var(--rec-text-light);margin-left:.25rem}
.rec-reparadores__descripcion{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text)}
.rec-reparadores__stats{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem}
.rec-reparadores__stat{display:flex;align-items:center;gap:.25rem;font-size:.8rem;color:var(--rec-text-light)}
.rec-reparadores__stat .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.rec-reparadores__impacto{margin-bottom:.75rem}
.rec-reparadores__co2{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .5rem;background:var(--rec-primary-light);color:var(--rec-primary);border-radius:10px;font-size:.8rem;font-weight:500}
.rec-reparadores__card-footer{display:flex;justify-content:flex-end}
.rec-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer}
.rec-btn--primary{background:var(--rec-primary);color:#fff}
.rec-btn--primary:hover{background:#388e3c}
.rec-btn--outline{background:#fff;border:1px solid var(--rec-primary);color:var(--rec-primary)}
.rec-btn--full{width:100%;justify-content:center}
.rec-reparadores__solicitudes{display:grid;gap:.75rem}
.rec-reparadores__solicitud{padding:1rem;border:1px solid var(--rec-border);border-radius:10px}
.rec-reparadores__solicitud-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.5rem}
.rec-reparadores__tipo-badge{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px}
.rec-reparadores__tipo-badge .dashicons{color:#fff;font-size:1.25rem;width:1.25rem;height:1.25rem}
.rec-reparadores__solicitud-info{flex:1}
.rec-reparadores__solicitud-info strong{display:block;font-size:.95rem}
.rec-reparadores__reparador-nombre{font-size:.8rem;color:var(--rec-text-light)}
.rec-reparadores__estado{padding:.2rem .6rem;border-radius:10px;color:#fff;font-size:.75rem;font-weight:500}
.rec-reparadores__solicitud-meta{display:flex;align-items:center;gap:1rem;font-size:.8rem;color:var(--rec-text-light)}
.rec-reparadores__solicitud-meta .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.rec-reparadores__co2-badge{display:flex;align-items:center;gap:.25rem;padding:.15rem .4rem;background:var(--rec-primary-light);color:var(--rec-primary);border-radius:8px;font-size:.75rem}
.rec-reparadores__form{display:grid;gap:1rem}
.rec-reparadores__form-intro{display:flex;align-items:center;gap:.75rem;padding:1rem;background:var(--rec-primary-light);border-radius:10px;margin-bottom:.5rem}
.rec-reparadores__form-intro .dashicons{color:var(--rec-primary);font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-reparadores__form-intro p{margin:0;font-size:.9rem;color:var(--rec-text)}
.rec-reparadores__field label{display:block;margin-bottom:.35rem;font-size:.9rem;font-weight:500}
.rec-reparadores__field select,.rec-reparadores__field input[type="text"],.rec-reparadores__field textarea{width:100%;padding:.6rem;border:1px solid var(--rec-border);border-radius:8px;font-size:.9rem}
.rec-reparadores__field input[type="checkbox"]{margin-right:.5rem}
.rec-reparadores__impacto-global{margin-top:1.5rem;padding:1rem;background:linear-gradient(135deg,var(--rec-primary-light),#c8e6c9);border-radius:10px}
.rec-reparadores__impacto-global h4{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-primary)}
.rec-reparadores__impacto-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem}
.rec-reparadores__impacto-item{text-align:center}
.rec-reparadores__impacto-valor{display:block;font-size:1.5rem;font-weight:700;color:var(--rec-primary)}
.rec-reparadores__impacto-label{font-size:.75rem;color:var(--rec-text-light)}
@media(max-width:600px){.rec-reparadores__grid{grid-template-columns:1fr}.rec-reparadores__impacto-grid{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tabs
    document.querySelectorAll('.rec-reparadores__tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetId = 'tab-' + this.dataset.tab;
            document.querySelectorAll('.rec-reparadores__tab').forEach(function(t) { t.classList.remove('rec-reparadores__tab--active'); });
            document.querySelectorAll('.rec-reparadores__panel').forEach(function(p) { p.classList.remove('rec-reparadores__panel--active'); });
            this.classList.add('rec-reparadores__tab--active');
            document.getElementById(targetId).classList.add('rec-reparadores__panel--active');
        });
    });

    // Filtro especialidad
    var filtro = document.getElementById('filtro-especialidad');
    if (filtro) {
        filtro.addEventListener('change', function() {
            var esp = this.value;
            document.querySelectorAll('.rec-reparadores__card').forEach(function(card) {
                if (!esp || card.dataset.esp === esp) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>
