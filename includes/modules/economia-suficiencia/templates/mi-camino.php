<?php
/**
 * Template: Mi Camino de Suficiencia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$modulo = new Flavor_Chat_Economia_Suficiencia_Module();
$stats = $modulo->get_estadisticas_usuario($user_id);
$nivel = $stats['nivel'];
$categorias = Flavor_Chat_Economia_Suficiencia_Module::CATEGORIAS_NECESIDADES;
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Mi Camino de Suficiencia', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Tu progreso hacia una vida más plena con menos', 'flavor-chat-ia'); ?></p>
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
                <div class="es-nivel-card__puntos-label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <?php if ($nivel['siguiente_nivel']) : ?>
        <div class="es-progreso-nivel">
            <div class="es-progreso-nivel__header">
                <span><?php esc_html_e('Progreso al siguiente nivel', 'flavor-chat-ia'); ?></span>
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
            <div class="es-stat-card__label"><?php esc_html_e('Compromisos activos', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-calendar-alt"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['practicas_mes']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Prácticas este mes', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-share"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['recursos_compartidos']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Objetos compartidos', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="es-stat-card">
            <span class="es-stat-card__icono dashicons dashicons-edit"></span>
            <div class="es-stat-card__valor"><?php echo esc_html($stats['reflexiones']); ?></div>
            <div class="es-stat-card__label"><?php esc_html_e('Reflexiones', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Radar de necesidades -->
        <div class="es-necesidades-radar">
            <h3><?php esc_html_e('Mi mapa de necesidades', 'flavor-chat-ia'); ?></h3>

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
                <a href="<?php echo esc_url(home_url('/mi-portal/economia-suficiencia/evaluacion/')); ?>" class="es-btn es-btn--secondary es-btn--small">
                    <?php esc_html_e('Actualizar evaluación', 'flavor-chat-ia'); ?>
                </a>
            </p>
            <?php else : ?>
            <div class="es-empty-state" style="padding: 1.5rem;">
                <p><?php esc_html_e('Aún no has evaluado tus necesidades.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/mi-portal/economia-suficiencia/evaluacion/')); ?>" class="es-btn es-btn--primary es-btn--small">
                    <?php esc_html_e('Evaluar ahora', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones rápidas -->
        <div>
            <h3><?php esc_html_e('Continúa tu camino', 'flavor-chat-ia'); ?></h3>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="<?php echo esc_url(home_url('/mi-portal/economia-suficiencia/compromisos/')); ?>" class="es-card" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;">
                    <span style="font-size: 2rem;">✊</span>
                    <div>
                        <strong><?php esc_html_e('Hacer un compromiso', 'flavor-chat-ia'); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Elige una práctica de suficiencia', 'flavor-chat-ia'); ?>
                        </p>
                    </div>
                </a>

                <a href="<?php echo esc_url(home_url('/mi-portal/economia-suficiencia/biblioteca/')); ?>" class="es-card" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;">
                    <span style="font-size: 2rem;">📦</span>
                    <div>
                        <strong><?php esc_html_e('Compartir un objeto', 'flavor-chat-ia'); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Añade algo a la biblioteca comunitaria', 'flavor-chat-ia'); ?>
                        </p>
                    </div>
                </a>

                <button class="es-card es-btn-abrir-modal" data-modal="modal-reflexion" style="display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit; background: var(--es-bg-card); border: none; cursor: pointer; text-align: left; width: 100%;">
                    <span style="font-size: 2rem;">💭</span>
                    <div>
                        <strong><?php esc_html_e('Escribir una reflexión', 'flavor-chat-ia'); ?></strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--es-text-light);">
                            <?php esc_html_e('Registra tus pensamientos sobre suficiencia', 'flavor-chat-ia'); ?>
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
            <h3><?php esc_html_e('Nueva reflexión', 'flavor-chat-ia'); ?></h3>
            <button class="es-modal__cerrar">&times;</button>
        </div>
        <form class="es-modal__body es-form-reflexion">
            <div class="es-form-grupo">
                <label for="reflexion-categoria"><?php esc_html_e('Sobre qué necesidad reflexionas', 'flavor-chat-ia'); ?></label>
                <select name="categoria" id="reflexion-categoria">
                    <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                    <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="es-form-grupo">
                <label for="reflexion-respuesta"><?php esc_html_e('Tu reflexión', 'flavor-chat-ia'); ?> *</label>
                <textarea name="respuesta" id="reflexion-respuesta" rows="5" required
                          placeholder="<?php esc_attr_e('¿Qué has descubierto sobre tus necesidades reales?', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="es-modal__footer">
                <button type="button" class="es-btn es-btn--secondary es-modal__cerrar">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" class="es-btn es-btn--primary">
                    <?php esc_html_e('Guardar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
