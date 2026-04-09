<?php
/**
 * Template: Economía Circular - Intercambio de materiales
 * @var array $materiales_disponibles Materiales de otros usuarios
 * @var array $mis_ofertas Mis materiales publicados
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;

$tipos_material = [
    'electronico' => ['icono' => 'dashicons-laptop', 'color' => '#2196f3'],
    'ropa' => ['icono' => 'dashicons-universal-access', 'color' => '#9c27b0'],
    'mueble' => ['icono' => 'dashicons-admin-home', 'color' => '#795548'],
    'plastico' => ['icono' => 'dashicons-archive', 'color' => '#ff9800'],
    'papel' => ['icono' => 'dashicons-media-text', 'color' => '#607d8b'],
    'otro' => ['icono' => 'dashicons-marker', 'color' => '#9e9e9e'],
];
?>
<div class="rec-circular" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="rec-circular__header">
        <span class="rec-circular__icono">
            <span class="dashicons dashicons-update-alt"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Economía Circular', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('Da una segunda vida a objetos que ya no usas', 'flavor-platform'); ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="rec-circular__tabs">
        <button class="rec-circular__tab rec-circular__tab--active" data-tab="disponibles">
            <?php esc_html_e('Disponibles', 'flavor-platform'); ?>
            <span class="rec-circular__tab-count"><?php echo count($materiales_disponibles); ?></span>
        </button>
        <button class="rec-circular__tab" data-tab="mis-ofertas">
            <?php esc_html_e('Mis ofertas', 'flavor-platform'); ?>
            <span class="rec-circular__tab-count"><?php echo count($mis_ofertas); ?></span>
        </button>
        <button class="rec-circular__tab" data-tab="publicar">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Publicar', 'flavor-platform'); ?>
        </button>
    </div>

    <!-- Tab: Disponibles -->
    <div class="rec-circular__panel rec-circular__panel--active" id="tab-disponibles">
        <?php if (empty($materiales_disponibles)): ?>
            <div class="rec-circular__vacio">
                <span class="dashicons dashicons-search"></span>
                <p><?php esc_html_e('No hay materiales disponibles en este momento.', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <div class="rec-circular__grid">
                <?php foreach ($materiales_disponibles as $mat):
                    $tipo_info = $tipos_material[$mat->material_tipo] ?? $tipos_material['otro'];
                ?>
                    <div class="rec-circular__item" data-id="<?php echo esc_attr($mat->id); ?>">
                        <div class="rec-circular__item-header">
                            <span class="rec-circular__tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                                <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                            </span>
                            <div class="rec-circular__item-info">
                                <strong><?php echo esc_html(ucfirst($mat->material_tipo)); ?></strong>
                                <span class="rec-circular__oferente">
                                    <?php echo esc_html($mat->display_name); ?>
                                </span>
                            </div>
                            <?php if ($mat->co2_ahorrado > 0): ?>
                                <span class="rec-circular__co2-badge">
                                    <span class="dashicons dashicons-cloud"></span>
                                    -<?php echo esc_html(number_format($mat->co2_ahorrado, 1)); ?> kg
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="rec-circular__descripcion"><?php echo esc_html($mat->descripcion); ?></p>
                        <?php if ($mat->ubicacion): ?>
                            <span class="rec-circular__ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($mat->ubicacion); ?>
                            </span>
                        <?php endif; ?>
                        <div class="rec-circular__item-footer">
                            <span class="rec-circular__fecha">
                                <?php echo esc_html(human_time_diff(strtotime($mat->fecha_creacion), current_time('timestamp'))); ?>
                            </span>
                            <button type="button" class="rec-btn rec-btn--primary rec-solicitar-material" data-id="<?php echo esc_attr($mat->id); ?>">
                                <?php esc_html_e('Me interesa', 'flavor-platform'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Mis ofertas -->
    <div class="rec-circular__panel" id="tab-mis-ofertas">
        <?php if (empty($mis_ofertas)): ?>
            <div class="rec-circular__vacio">
                <span class="dashicons dashicons-archive"></span>
                <p><?php esc_html_e('No tienes materiales publicados.', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <div class="rec-circular__lista">
                <?php foreach ($mis_ofertas as $oferta):
                    $tipo_info = $tipos_material[$oferta->material_tipo] ?? $tipos_material['otro'];
                ?>
                    <div class="rec-circular__mi-oferta">
                        <span class="rec-circular__tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </span>
                        <div class="rec-circular__oferta-info">
                            <strong><?php echo esc_html($oferta->descripcion ?: ucfirst($oferta->material_tipo)); ?></strong>
                            <span class="rec-circular__estado rec-circular__estado--<?php echo esc_attr($oferta->estado); ?>">
                                <?php
                                $estados = [
                                    'disponible' => __('Disponible', 'flavor-platform'),
                                    'reservado' => __('Reservado', 'flavor-platform'),
                                ];
                                echo esc_html($estados[$oferta->estado] ?? $oferta->estado);
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Publicar -->
    <div class="rec-circular__panel" id="tab-publicar">
        <form class="rec-circular__form" id="form-publicar-material">
            <div class="rec-circular__field">
                <label><?php esc_html_e('Tipo de material', 'flavor-platform'); ?></label>
                <select name="material_tipo" required>
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-platform'); ?></option>
                    <option value="electronico"><?php esc_html_e('Electrónico', 'flavor-platform'); ?></option>
                    <option value="ropa"><?php esc_html_e('Ropa/Textil', 'flavor-platform'); ?></option>
                    <option value="mueble"><?php esc_html_e('Mueble', 'flavor-platform'); ?></option>
                    <option value="plastico"><?php esc_html_e('Plástico', 'flavor-platform'); ?></option>
                    <option value="papel"><?php esc_html_e('Papel/Cartón', 'flavor-platform'); ?></option>
                    <option value="otro"><?php esc_html_e('Otro', 'flavor-platform'); ?></option>
                </select>
            </div>
            <div class="rec-circular__field">
                <label><?php esc_html_e('Descripción', 'flavor-platform'); ?></label>
                <textarea name="descripcion" rows="3" required placeholder="<?php esc_attr_e('Describe el objeto...', 'flavor-platform'); ?>"></textarea>
            </div>
            <div class="rec-circular__field">
                <label><?php esc_html_e('Ubicación aproximada', 'flavor-platform'); ?></label>
                <input type="text" name="ubicacion" placeholder="<?php esc_attr_e('Ej: Centro, Barrio Norte...', 'flavor-platform'); ?>">
            </div>
            <button type="submit" class="rec-btn rec-btn--primary rec-btn--full">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Publicar', 'flavor-platform'); ?>
            </button>
        </form>
    </div>

    <!-- Impacto -->
    <div class="rec-circular__impacto">
        <h4><?php esc_html_e('Impacto de la economía circular', 'flavor-platform'); ?></h4>
        <div class="rec-circular__impacto-grid">
            <div class="rec-circular__impacto-item">
                <span class="dashicons dashicons-cloud"></span>
                <span><?php esc_html_e('Reduce emisiones CO₂', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-circular__impacto-item">
                <span class="dashicons dashicons-trash"></span>
                <span><?php esc_html_e('Menos residuos', 'flavor-platform'); ?></span>
            </div>
            <div class="rec-circular__impacto-item">
                <span class="dashicons dashicons-groups"></span>
                <span><?php esc_html_e('Fortalece comunidad', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>
</div>
<style>
.rec-circular{--rec-primary:#4caf50;--rec-primary-light:#e8f5e9;--rec-warning:#ff9800;--rec-text:#333;--rec-text-light:#666;--rec-border:#e0e0e0;background:#fff;border:1px solid var(--rec-border);border-radius:12px;padding:1.5rem}
.rec-circular__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.rec-circular__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--rec-primary);border-radius:50%}
.rec-circular__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.rec-circular__header h3{margin:0;font-size:1.1rem}
.rec-circular__header p{margin:0;font-size:.85rem;color:var(--rec-text-light)}
.rec-circular__tabs{display:flex;gap:.5rem;margin-bottom:1rem;border-bottom:2px solid var(--rec-border);padding-bottom:.5rem}
.rec-circular__tab{display:flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;background:none;font-size:.9rem;cursor:pointer;color:var(--rec-text-light);border-radius:8px 8px 0 0}
.rec-circular__tab--active{background:var(--rec-primary-light);color:var(--rec-primary);font-weight:500}
.rec-circular__tab-count{padding:.1rem .4rem;background:#e0e0e0;border-radius:10px;font-size:.75rem}
.rec-circular__tab--active .rec-circular__tab-count{background:var(--rec-primary);color:#fff}
.rec-circular__panel{display:none}
.rec-circular__panel--active{display:block}
.rec-circular__vacio{text-align:center;padding:2rem}
.rec-circular__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:#ccc}
.rec-circular__vacio p{color:var(--rec-text-light);margin:.5rem 0 0}
.rec-circular__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
.rec-circular__item{padding:1rem;border:1px solid var(--rec-border);border-radius:10px}
.rec-circular__item:hover{border-color:var(--rec-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.rec-circular__item-header{display:flex;align-items:flex-start;gap:.75rem;margin-bottom:.75rem}
.rec-circular__tipo-badge{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px}
.rec-circular__tipo-badge .dashicons{color:#fff;font-size:1.25rem;width:1.25rem;height:1.25rem}
.rec-circular__item-info{flex:1}
.rec-circular__item-info strong{display:block;font-size:.95rem}
.rec-circular__oferente{font-size:.8rem;color:var(--rec-text-light)}
.rec-circular__co2-badge{display:flex;align-items:center;gap:.25rem;padding:.2rem .5rem;background:var(--rec-primary-light);color:var(--rec-primary);border-radius:10px;font-size:.7rem;font-weight:500}
.rec-circular__descripcion{margin:0 0 .5rem;font-size:.9rem;color:var(--rec-text)}
.rec-circular__ubicacion{display:flex;align-items:center;gap:.25rem;font-size:.8rem;color:var(--rec-text-light);margin-bottom:.75rem}
.rec-circular__ubicacion .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.rec-circular__item-footer{display:flex;align-items:center;justify-content:space-between}
.rec-circular__fecha{font-size:.75rem;color:var(--rec-text-light)}
.rec-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem 1rem;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer}
.rec-btn--primary{background:var(--rec-primary);color:#fff}
.rec-btn--primary:hover{background:#388e3c}
.rec-btn--full{width:100%;justify-content:center}
.rec-circular__lista{display:grid;gap:.75rem}
.rec-circular__mi-oferta{display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid var(--rec-border);border-radius:8px}
.rec-circular__oferta-info{flex:1}
.rec-circular__oferta-info strong{display:block;font-size:.9rem}
.rec-circular__estado{padding:.15rem .4rem;border-radius:8px;font-size:.7rem;font-weight:500}
.rec-circular__estado--disponible{background:var(--rec-primary-light);color:var(--rec-primary)}
.rec-circular__estado--reservado{background:#fff3e0;color:#e65100}
.rec-circular__form{display:grid;gap:1rem}
.rec-circular__field label{display:block;margin-bottom:.35rem;font-size:.9rem;font-weight:500}
.rec-circular__field select,.rec-circular__field input,.rec-circular__field textarea{width:100%;padding:.6rem;border:1px solid var(--rec-border);border-radius:8px;font-size:.9rem}
.rec-circular__impacto{margin-top:1.5rem;padding:1rem;background:#f5f5f5;border-radius:10px}
.rec-circular__impacto h4{margin:0 0 .75rem;font-size:.9rem}
.rec-circular__impacto-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem}
.rec-circular__impacto-item{display:flex;flex-direction:column;align-items:center;text-align:center;font-size:.8rem;color:var(--rec-text-light)}
.rec-circular__impacto-item .dashicons{color:var(--rec-primary);font-size:1.25rem;width:1.25rem;height:1.25rem;margin-bottom:.25rem}
@media(max-width:600px){.rec-circular__grid{grid-template-columns:1fr}.rec-circular__impacto-grid{grid-template-columns:1fr}}
</style>
