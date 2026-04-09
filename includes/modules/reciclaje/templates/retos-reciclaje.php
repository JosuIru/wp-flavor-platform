<?php
/**
 * Template: Retos de reciclaje comunitarios
 * @var array $retos Lista de retos activos
 * @var array $mis_retos IDs de retos donde participa el usuario
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;

$tipos_reto = [
    'reciclaje' => ['icono' => 'dashicons-update', 'color' => '#4caf50'],
    'reutilizacion' => ['icono' => 'dashicons-share-alt', 'color' => '#2196f3'],
    'reparacion' => ['icono' => 'dashicons-admin-tools', 'color' => '#ff9800'],
    'reduccion' => ['icono' => 'dashicons-arrow-down-alt', 'color' => '#9c27b0'],
];
?>
<div class="rec-retos" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="rec-retos__header">
        <span class="rec-retos__icono">
            <span class="dashicons dashicons-flag"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Retos Comunitarios', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('Únete a retos colectivos de reciclaje', 'flavor-platform'); ?></p>
        </div>
    </div>

    <?php if (empty($retos)): ?>
        <div class="rec-retos__vacio">
            <span class="dashicons dashicons-calendar-alt"></span>
            <p><?php esc_html_e('No hay retos activos en este momento.', 'flavor-platform'); ?></p>
        </div>
    <?php else: ?>
        <div class="rec-retos__lista">
            <?php foreach ($retos as $reto):
                $tipo_info = $tipos_reto[$reto->tipo] ?? $tipos_reto['reciclaje'];
                $progreso = $reto->meta_cantidad > 0 ? min(100, ($reto->progreso_actual / $reto->meta_cantidad) * 100) : 0;
                $dias_restantes = max(0, floor((strtotime($reto->fecha_fin) - time()) / 86400));
                $participo = in_array($reto->id, $mis_retos);
            ?>
                <div class="rec-retos__reto <?php echo $participo ? 'rec-retos__reto--participando' : ''; ?>" data-id="<?php echo esc_attr($reto->id); ?>">
                    <div class="rec-retos__reto-header">
                        <span class="rec-retos__tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </span>
                        <div class="rec-retos__reto-info">
                            <strong><?php echo esc_html($reto->titulo); ?></strong>
                            <span class="rec-retos__participantes">
                                <span class="dashicons dashicons-groups"></span>
                                <?php printf(esc_html__('%d participantes', 'flavor-platform'), $reto->total_participantes); ?>
                            </span>
                        </div>
                        <div class="rec-retos__dias">
                            <span class="rec-retos__dias-valor"><?php echo esc_html($dias_restantes); ?></span>
                            <span class="rec-retos__dias-label"><?php esc_html_e('días', 'flavor-platform'); ?></span>
                        </div>
                    </div>

                    <p class="rec-retos__descripcion"><?php echo esc_html($reto->descripcion); ?></p>

                    <div class="rec-retos__progreso">
                        <div class="rec-retos__progreso-header">
                            <span><?php printf(esc_html__('Progreso: %s de %s %s', 'flavor-platform'),
                                number_format($reto->progreso_actual, 0),
                                number_format($reto->meta_cantidad, 0),
                                $reto->unidad
                            ); ?></span>
                            <span><?php echo esc_html(round($progreso)); ?>%</span>
                        </div>
                        <div class="rec-retos__progreso-bar">
                            <div class="rec-retos__progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%; background: <?php echo esc_attr($tipo_info['color']); ?>"></div>
                        </div>
                    </div>

                    <div class="rec-retos__reto-footer">
                        <span class="rec-retos__recompensa">
                            <span class="dashicons dashicons-awards"></span>
                            +<?php echo esc_html($reto->puntos_recompensa); ?> pts
                        </span>
                        <?php if ($participo): ?>
                            <span class="rec-retos__participando-badge">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Participando', 'flavor-platform'); ?>
                            </span>
                        <?php elseif (is_user_logged_in()): ?>
                            <button type="button" class="rec-btn rec-btn--primary rec-unirse-reto" data-id="<?php echo esc_attr($reto->id); ?>">
                                <?php esc_html_e('Unirme', 'flavor-platform'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tipos de retos -->
    <div class="rec-retos__tipos">
        <h4><?php esc_html_e('Tipos de retos', 'flavor-platform'); ?></h4>
        <div class="rec-retos__tipos-grid">
            <?php foreach ($tipos_reto as $key => $tipo): ?>
                <div class="rec-retos__tipo">
                    <span class="rec-retos__tipo-icono" style="background: <?php echo esc_attr($tipo['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($tipo['icono']); ?>"></span>
                    </span>
                    <span><?php echo esc_html(ucfirst($key)); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<style>
.rec-retos{--rec-primary:#4caf50;--rec-primary-light:#e8f5e9;--rec-text:#333;--rec-text-light:#666;--rec-border:#e0e0e0;background:#fff;border:1px solid var(--rec-border);border-radius:12px;padding:1.5rem}
.rec-retos__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.rec-retos__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--rec-primary);border-radius:50%}
.rec-retos__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-retos__header h3{margin:0;font-size:1.1rem}
.rec-retos__header p{margin:0;font-size:.85rem;color:var(--rec-text-light)}
.rec-retos__vacio{text-align:center;padding:2rem}
.rec-retos__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.rec-retos__vacio p{color:var(--rec-text-light);margin:.5rem 0 0}
.rec-retos__lista{display:grid;gap:1rem;margin-bottom:1.5rem}
.rec-retos__reto{padding:1rem;border:1px solid var(--rec-border);border-radius:10px}
.rec-retos__reto:hover{border-color:var(--rec-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.rec-retos__reto--participando{border-color:var(--rec-primary);background:var(--rec-primary-light)}
.rec-retos__reto-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem}
.rec-retos__tipo-badge{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px}
.rec-retos__tipo-badge .dashicons{color:#fff;font-size:1.25rem;width:1.25rem;height:1.25rem}
.rec-retos__reto-info{flex:1}
.rec-retos__reto-info strong{display:block;font-size:1rem}
.rec-retos__participantes{display:flex;align-items:center;gap:.25rem;font-size:.8rem;color:var(--rec-text-light)}
.rec-retos__participantes .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.rec-retos__dias{text-align:center;padding:.5rem;background:#f5f5f5;border-radius:8px;min-width:50px}
.rec-retos__dias-valor{display:block;font-size:1.25rem;font-weight:700;color:var(--rec-primary)}
.rec-retos__dias-label{font-size:.65rem;color:var(--rec-text-light)}
.rec-retos__descripcion{margin:0 0 .75rem;font-size:.9rem;color:var(--rec-text-light)}
.rec-retos__progreso{margin-bottom:.75rem}
.rec-retos__progreso-header{display:flex;justify-content:space-between;font-size:.8rem;color:var(--rec-text-light);margin-bottom:.35rem}
.rec-retos__progreso-bar{height:10px;background:#e0e0e0;border-radius:5px;overflow:hidden}
.rec-retos__progreso-fill{height:100%;border-radius:5px;transition:width .5s}
.rec-retos__reto-footer{display:flex;align-items:center;justify-content:space-between}
.rec-retos__recompensa{display:flex;align-items:center;gap:.35rem;font-size:.85rem;color:#ff9800;font-weight:500}
.rec-retos__recompensa .dashicons{font-size:1rem;width:1rem;height:1rem}
.rec-retos__participando-badge{display:flex;align-items:center;gap:.25rem;padding:.35rem .6rem;background:var(--rec-primary);color:#fff;border-radius:15px;font-size:.8rem}
.rec-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer}
.rec-btn--primary{background:var(--rec-primary);color:#fff}
.rec-btn--primary:hover{background:#388e3c}
.rec-retos__tipos{padding:1rem;background:#f5f5f5;border-radius:10px}
.rec-retos__tipos h4{margin:0 0 .75rem;font-size:.9rem}
.rec-retos__tipos-grid{display:flex;flex-wrap:wrap;gap:.75rem}
.rec-retos__tipo{display:flex;align-items:center;gap:.5rem;font-size:.85rem;color:var(--rec-text-light)}
.rec-retos__tipo-icono{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%}
.rec-retos__tipo-icono .dashicons{color:#fff;font-size:.9rem;width:.9rem;height:.9rem}
</style>
