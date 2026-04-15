<?php
/**
 * Template: Mi Camino de Suficiencia
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$economia_suficiencia_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Economia_Suficiencia_Module')
    : 'Flavor_Chat_Economia_Suficiencia_Module';
$modulo = new $economia_suficiencia_module_class();
$stats = $modulo->get_estadisticas_usuario($user_id);
$nivel = $stats['nivel'];
$categorias = $economia_suficiencia_module_class::CATEGORIAS_NECESIDADES;
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Mi Camino de Suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Tu progreso hacia una vida más plena con menos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Nivel actual -->
    <div class="es-nivel-card">
        <div class="es-nivel-card__icono">
            <?php
            $iconos_nivel = ['explorando' => '🌱', 'consciente' => '🌿', 'practicante' => '🌳', 'mentor' => '🌲', 'sabio' => '🏔️'];
            echo esc_html($iconos_nivel[$nivel['nivel']['id']] ?? '🌱');
            ?>
        </div>
        <h3 class="es-nivel-card__nombre"><?php echo esc_html($nivel['nivel']['nombre']); ?></h3>
        <p class="es-nivel-card__descripcion"><?php echo esc_html($nivel['nivel']['descripcion']); ?></p>

        <div class="es-nivel-card__puntos">
            <div class="es-nivel-card__puntos-item">
                <div class="es-nivel-card__puntos-valor"><?php echo esc_html($nivel['puntos']); ?></div>
                <div class="es-nivel-card__puntos-label"><?php esc_html_e('Puntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <?php if ($nivel['siguiente_nivel']) : ?>
        <div class="es-progreso-nivel">
            <div class="es-progreso-nivel__header">
                <span><?php esc_html_e('Progreso al siguiente nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span><?php echo esc_html($nivel['siguiente_nivel']['nombre']); ?></span>
            </div>
            <div class="es-progreso-nivel__bar">
                <div class="es-progreso-nivel__fill" data-progreso="<?php echo esc_attr($nivel['progreso']); ?>" style="width: <?php echo esc_attr($nivel['progreso']); ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="es-stats-grid">
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-yes-alt"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['compromisos_activos']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Compromisos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-calendar-alt"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['practicas_mes']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Prácticas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-share"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['recursos_compartidos']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Objetos compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-edit"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['reflexiones']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Reflexiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Radar de necesidades -->
        <div class="es-necesidades-radar">
            <h3><?php esc_html_e('Mi mapa de necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <?php if ($stats['evaluacion_necesidades']) : ?>
            <div class="es-radar-visual">
                <?php foreach ($categorias as $cat_id => $cat_data) :
                    $valor = $stats['evaluacion_necesidades'][$cat_id] ?? 0;
                ?>
                <div class="es-radar-item">
                    <div class="es-radar-item__barra">
                        <div class="es-radar-item__fill" data-valor="<?php echo esc_attr($valor); ?>" style="background: <?php echo esc_attr($cat_data['color']); ?>; height: <?php echo esc_attr($valor * 20); ?>%"></div>
                    </div>
                    <div class="es-radar-item__label"><?php echo esc_html($cat_data['nombre']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia_suficiencia', 'evaluacion')); ?>" class="es-btn es-btn--secondary es-btn--small">
                    <?php esc_html_e('Actualizar evaluación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </p>
            <?php else : ?>
            <div class="es-empty-state" style="padding: 1.5rem;">
                <p><?php esc_html_e('Aún no has evaluado tus necesidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia_suficiencia', 'evaluacion')); ?>" class="es-btn es-btn--primary es-btn--small">
                    <?php esc_html_e('Evaluar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones rápidas -->
        <div>
            <h3><?php esc_html_e('Continúa tu camino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia_suficiencia', 'compromisos')); ?>" class="es-card" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;">
                    <span style="font-size: 2rem;">✊</span>
                    <div>
                        <strong><?php esc_html_e('Hacer un compromiso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Elige una práctica de suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>
                </a>

                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia_suficiencia', 'biblioteca')); ?>" class="es-card" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;">
                    <span style="font-size: 2rem;">📦</span>
                    <div>
                        <strong><?php esc_html_e('Compartir un objeto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Añade algo a la biblioteca comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>
                </a>

                <button class="es-card es-btn-abrir-modal" data-modal="modal-reflexion" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit; background: var(--es-bg-card); border: none; cursor: pointer; text-align: left; width: 100%;">
                    <span style="font-size: 2rem;">💭</span>
                    <div>
                        <strong><?php esc_html_e('Escribir una reflexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Registra tus pensamientos sobre suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal reflexión -->
<div id="modal-reflexion" class="es-modal">
    <div class="es-modal__contenido">
        <div class="es-modal__header">
            <h3><?php esc_html_e('Nueva reflexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button class="es-modal__cerrar">&times;</button>
        </div>
        <form class="es-modal__body es-form-reflexion">
            <div class="es-form-grupo">
                <label for="reflexion-categoria"><?php esc_html_e('Sobre qué necesidad reflexionas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="categoria" id="reflexion-categoria">
                    <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                    <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="es-form-grupo">
                <label for="reflexion-respuesta"><?php esc_html_e('Tu reflexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <textarea name="respuesta" id="reflexion-respuesta" rows="5" required
                          placeholder="<?php esc_attr_e('¿Qué has descubierto sobre tus necesidades reales?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="es-modal__footer">
                <button type="button" class="es-btn es-btn--secondary es-modal__cerrar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="es-btn es-btn--primary">
                    <?php esc_html_e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>
