<?php
/**
 * Template: Mapa de impacto ambiental
 * @var array $incidencias Incidencias con impacto ambiental
 * @var string $nonce Nonce de seguridad
 */
if (!defined('ABSPATH')) exit;

$tipos_impacto = [
    'ruido' => ['label' => __('Ruido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ff5722', 'icono' => 'dashicons-megaphone'],
    'contaminacion_aire' => ['label' => __('Aire', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#9c27b0', 'icono' => 'dashicons-cloud'],
    'contaminacion_agua' => ['label' => __('Agua', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#2196f3', 'icono' => 'dashicons-admin-site-alt3'],
    'residuos' => ['label' => __('Residuos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#795548', 'icono' => 'dashicons-trash'],
    'visual' => ['label' => __('Visual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#607d8b', 'icono' => 'dashicons-visibility'],
    'otro' => ['label' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#9e9e9e', 'icono' => 'dashicons-warning'],
];
?>
<div class="inc-mapa-ambiental" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="inc-mapa-ambiental__header">
        <span class="inc-mapa-ambiental__icono">
            <span class="dashicons dashicons-location-alt"></span>
        </span>
        <div>
            <h3><?php esc_html_e('Mapa de Impacto Ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Incidencias que afectan al medio ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="inc-mapa-ambiental__filtros">
        <?php foreach ($tipos_impacto as $key => $tipo): ?>
            <label class="inc-mapa-ambiental__filtro">
                <input type="checkbox" name="tipo_impacto[]" value="<?php echo esc_attr($key); ?>" checked>
                <span class="inc-mapa-ambiental__filtro-badge" style="background: <?php echo esc_attr($tipo['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($tipo['icono']); ?>"></span>
                    <?php echo esc_html($tipo['label']); ?>
                </span>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Área del mapa -->
    <div class="inc-mapa-ambiental__mapa" id="mapa-impacto-container">
        <div class="inc-mapa-ambiental__mapa-placeholder">
            <span class="dashicons dashicons-location-alt"></span>
            <p><?php esc_html_e('Cargando mapa...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <!-- Resumen -->
    <div class="inc-mapa-ambiental__resumen">
        <h4><?php esc_html_e('Resumen de impactos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="inc-mapa-ambiental__resumen-grid">
            <?php
            $conteo_tipos = [];
            foreach ($incidencias as $inc) {
                $tipo = $inc->tipo_impacto ?? 'otro';
                if (!isset($conteo_tipos[$tipo])) $conteo_tipos[$tipo] = 0;
                $conteo_tipos[$tipo]++;
            }
            arsort($conteo_tipos);

            foreach ($conteo_tipos as $tipo => $cantidad):
                $info = $tipos_impacto[$tipo] ?? $tipos_impacto['otro'];
            ?>
                <div class="inc-mapa-ambiental__resumen-item">
                    <span class="inc-mapa-ambiental__resumen-icono" style="background: <?php echo esc_attr($info['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($info['icono']); ?>"></span>
                    </span>
                    <span class="inc-mapa-ambiental__resumen-cantidad"><?php echo esc_html($cantidad); ?></span>
                    <span class="inc-mapa-ambiental__resumen-label"><?php echo esc_html($info['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lista de incidencias -->
    <?php if (!empty($incidencias)): ?>
        <div class="inc-mapa-ambiental__lista">
            <h4><?php esc_html_e('Incidencias con impacto ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <?php foreach (array_slice($incidencias, 0, 10) as $inc):
                $tipo_info = $tipos_impacto[$inc->tipo_impacto] ?? $tipos_impacto['otro'];
            ?>
                <div class="inc-mapa-ambiental__incidencia" data-lat="<?php echo esc_attr($inc->latitud ?? ''); ?>" data-lng="<?php echo esc_attr($inc->longitud ?? ''); ?>">
                    <div class="inc-mapa-ambiental__inc-header">
                        <span class="inc-mapa-ambiental__tipo-badge" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                            <span class="dashicons <?php echo esc_attr($tipo_info['icono']); ?>"></span>
                        </span>
                        <div class="inc-mapa-ambiental__inc-info">
                            <strong><?php echo esc_html($inc->titulo); ?></strong>
                            <span class="inc-mapa-ambiental__direccion">
                                <?php echo esc_html($inc->direccion ?: __('Sin ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </span>
                        </div>
                        <div class="inc-mapa-ambiental__severidad">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="dashicons dashicons-warning <?php echo $i <= $inc->severidad ? 'activo' : ''; ?>"></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if ($inc->afecta_salud): ?>
                        <div class="inc-mapa-ambiental__alerta-salud">
                            <span class="dashicons dashicons-heart"></span>
                            <?php esc_html_e('Puede afectar a la salud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="inc-mapa-ambiental__vacio">
            <span class="dashicons dashicons-smiley"></span>
            <p><?php esc_html_e('No hay incidencias con impacto ambiental registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <!-- Reportar impacto -->
    <div class="inc-mapa-ambiental__reportar">
        <h4><?php esc_html_e('¿Ves un problema ambiental?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <p><?php esc_html_e('Reporta incidencias que afecten al medio ambiente o la salud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <a href="<?php echo esc_url(home_url('/incidencias/reportar/')); ?>" class="inc-btn inc-btn--primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Reportar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>
<style>
.inc-mapa-ambiental{--inc-primary:#4caf50;--inc-primary-light:#e8f5e9;--inc-danger:#f44336;--inc-text:#333;--inc-text-light:#666;--inc-border:#e0e0e0;background:#fff;border:1px solid var(--inc-border);border-radius:12px;padding:1.5rem}
.inc-mapa-ambiental__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.inc-mapa-ambiental__icono{display:flex;align-items:center;justify-content:center;width:50px;height:50px;background:var(--inc-primary);border-radius:50%}
.inc-mapa-ambiental__icono .dashicons{color:#fff;font-size:1.5rem;width:1.5rem;height:1.5rem}
.inc-mapa-ambiental__header h3{margin:0;font-size:1.1rem}
.inc-mapa-ambiental__header p{margin:0;font-size:.85rem;color:var(--inc-text-light)}
.inc-mapa-ambiental__filtros{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
.inc-mapa-ambiental__filtro{cursor:pointer}
.inc-mapa-ambiental__filtro input{display:none}
.inc-mapa-ambiental__filtro-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.3rem .6rem;border-radius:15px;font-size:.75rem;color:#fff;opacity:.5;transition:opacity .2s}
.inc-mapa-ambiental__filtro input:checked+.inc-mapa-ambiental__filtro-badge{opacity:1}
.inc-mapa-ambiental__filtro-badge .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-mapa-ambiental__mapa{height:300px;background:#f5f5f5;border-radius:10px;margin-bottom:1.5rem;overflow:hidden}
.inc-mapa-ambiental__mapa-placeholder{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--inc-text-light)}
.inc-mapa-ambiental__mapa-placeholder .dashicons{font-size:3rem;width:3rem;height:3rem;margin-bottom:.5rem}
.inc-mapa-ambiental__resumen{margin-bottom:1.5rem}
.inc-mapa-ambiental__resumen h4{margin:0 0 1rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-mapa-ambiental__resumen-grid{display:flex;flex-wrap:wrap;gap:1rem}
.inc-mapa-ambiental__resumen-item{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;background:#f5f5f5;border-radius:10px}
.inc-mapa-ambiental__resumen-icono{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%}
.inc-mapa-ambiental__resumen-icono .dashicons{color:#fff;font-size:.9rem;width:.9rem;height:.9rem}
.inc-mapa-ambiental__resumen-cantidad{font-size:1.25rem;font-weight:700}
.inc-mapa-ambiental__resumen-label{font-size:.8rem;color:var(--inc-text-light)}
.inc-mapa-ambiental__lista{margin-bottom:1.5rem}
.inc-mapa-ambiental__lista h4{margin:0 0 1rem;font-size:.9rem;color:var(--inc-text-light);text-transform:uppercase}
.inc-mapa-ambiental__incidencia{padding:.75rem;border:1px solid var(--inc-border);border-radius:10px;margin-bottom:.75rem;cursor:pointer}
.inc-mapa-ambiental__incidencia:hover{border-color:var(--inc-primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.inc-mapa-ambiental__inc-header{display:flex;align-items:center;gap:.75rem}
.inc-mapa-ambiental__tipo-badge{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%}
.inc-mapa-ambiental__tipo-badge .dashicons{color:#fff;font-size:1.1rem;width:1.1rem;height:1.1rem}
.inc-mapa-ambiental__inc-info{flex:1}
.inc-mapa-ambiental__inc-info strong{display:block;font-size:.9rem}
.inc-mapa-ambiental__direccion{font-size:.75rem;color:var(--inc-text-light)}
.inc-mapa-ambiental__severidad{display:flex;gap:2px}
.inc-mapa-ambiental__severidad .dashicons{font-size:.8rem;width:.8rem;height:.8rem;color:#e0e0e0}
.inc-mapa-ambiental__severidad .dashicons.activo{color:#ff9800}
.inc-mapa-ambiental__alerta-salud{display:flex;align-items:center;gap:.5rem;margin-top:.5rem;padding:.4rem .6rem;background:#ffebee;border-radius:8px;font-size:.75rem;color:var(--inc-danger)}
.inc-mapa-ambiental__alerta-salud .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.inc-mapa-ambiental__vacio{text-align:center;padding:2rem}
.inc-mapa-ambiental__vacio .dashicons{font-size:3rem;width:3rem;height:3rem;color:var(--inc-primary)}
.inc-mapa-ambiental__vacio p{color:var(--inc-text-light);margin:.5rem 0 0}
.inc-mapa-ambiental__reportar{padding:1rem;background:var(--inc-primary-light);border-radius:10px;text-align:center}
.inc-mapa-ambiental__reportar h4{margin:0 0 .5rem;font-size:1rem}
.inc-mapa-ambiental__reportar p{margin:0 0 1rem;font-size:.9rem;color:var(--inc-text-light)}
.inc-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.6rem 1.2rem;border:none;border-radius:8px;font-size:.9rem;font-weight:500;cursor:pointer;text-decoration:none}
.inc-btn--primary{background:var(--inc-primary);color:#fff}
.inc-btn--primary:hover{background:#388e3c}
.inc-btn .dashicons{font-size:1rem;width:1rem;height:1rem}
</style>
