<?php
/**
 * Template: Proyectos de Conservación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Encolar estilos del módulo
wp_enqueue_style(
    'flavor-biodiversidad-local',
    FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-local.css',
    [],
    FLAVOR_CHAT_IA_VERSION
);

// Fallback: asegurar JS del módulo cuando este template se renderiza fuera del shortcode principal.
if (!wp_script_is('flavor-biodiversidad', 'registered')) {
    wp_register_script(
        'flavor-biodiversidad',
        FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/js/biodiversidad-local.js',
        ['jquery'],
        FLAVOR_CHAT_IA_VERSION,
        true
    );
}
if (!wp_script_is('flavor-biodiversidad', 'enqueued')) {
    wp_enqueue_script('flavor-biodiversidad');
}
wp_localize_script('flavor-biodiversidad', 'flavorBiodiversidad', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('biodiversidad_nonce'),
    'categorias' => Flavor_Chat_Biodiversidad_Local_Module::CATEGORIAS_ESPECIES,
    'estados' => Flavor_Chat_Biodiversidad_Local_Module::ESTADOS_CONSERVACION,
    'habitats' => Flavor_Chat_Biodiversidad_Local_Module::TIPOS_HABITAT,
    'i18n' => [
        'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'success' => __('Operación completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'confirm_avistamiento' => __('¿Registrar este avistamiento?', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
]);

$tipos_proyecto = Flavor_Chat_Biodiversidad_Local_Module::TIPOS_PROYECTO;
$user_id = get_current_user_id();

$proyectos = get_posts([
    'post_type' => 'bl_proyecto',
    'post_status' => 'publish',
    'posts_per_page' => 20,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="bl-container">
    <header class="bl-header">
        <h2><?php esc_html_e('Proyectos de Conservación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Únete a iniciativas para proteger nuestra biodiversidad local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Tabs -->
    <div class="bl-tabs">
        <button class="bl-tab activo" data-tab="tab-proyectos">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e('Proyectos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php if (is_user_logged_in()) : ?>
        <button class="bl-tab" data-tab="tab-crear">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Crear Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- Tab: Proyectos -->
    <div id="tab-proyectos" class="bl-tab-contenido">
        <?php if ($proyectos) : ?>
        <div class="bl-proyectos-grid">
            <?php foreach ($proyectos as $proyecto) :
                $tipo = get_post_meta($proyecto->ID, '_bl_tipo', true);
                $tipo_data = $tipos_proyecto[$tipo] ?? ['nombre' => $tipo, 'icono' => 'dashicons-marker', 'color' => '#6b7280'];
                $fecha_inicio = get_post_meta($proyecto->ID, '_bl_fecha_inicio', true);
                $ubicacion = get_post_meta($proyecto->ID, '_bl_ubicacion', true);
                $participantes = get_post_meta($proyecto->ID, '_bl_participantes', true) ?: [];
                $max_participantes = intval(get_post_meta($proyecto->ID, '_bl_participantes_max', true));
                $ya_participa = in_array($user_id, $participantes);
            ?>
            <article class="bl-proyecto-card" style="border-left-color: <?php echo esc_attr($tipo_data['color']); ?>">
                <div class="bl-proyecto-card__header">
                    <span class="bl-proyecto-card__tipo" style="background: <?php echo esc_attr($tipo_data['color']); ?>15; color: <?php echo esc_attr($tipo_data['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                        <?php echo esc_html($tipo_data['nombre']); ?>
                    </span>
                    <h3 class="bl-proyecto-card__titulo"><?php echo esc_html($proyecto->post_title); ?></h3>
                </div>

                <div class="bl-proyecto-card__body">
                    <p class="bl-proyecto-card__descripcion">
                        <?php echo esc_html(wp_trim_words($proyecto->post_content, 30)); ?>
                    </p>

                    <div class="bl-proyecto-card__info">
                        <?php if ($fecha_inicio) : ?>
                        <span>
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n('j M Y', strtotime($fecha_inicio))); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($ubicacion) : ?>
                        <span>
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($ubicacion); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bl-proyecto-card__footer">
                    <span class="bl-proyecto-card__participantes">
                        <span class="dashicons dashicons-groups"></span>
                        <span class="bl-participantes-count"><?php echo esc_html(count($participantes)); ?></span>
                        <?php if ($max_participantes > 0) : ?>
                        / <?php echo esc_html($max_participantes); ?>
                        <?php endif; ?>
                        <?php esc_html_e('participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>

                    <?php if (is_user_logged_in()) : ?>
                        <?php if ($ya_participa) : ?>
                        <span class="bl-btn bl-btn--secondary bl-btn--small"><?php esc_html_e('Participando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php elseif ($max_participantes === 0 || count($participantes) < $max_participantes) : ?>
                        <button class="bl-btn bl-btn--primary bl-btn--small bl-btn-participar" data-proyecto="<?php echo esc_attr($proyecto->ID); ?>">
                            <?php esc_html_e('Unirme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <?php else : ?>
                        <span class="bl-btn bl-btn--secondary bl-btn--small"><?php esc_html_e('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="bl-empty-state">
            <span class="dashicons dashicons-shield"></span>
            <p><?php esc_html_e('No hay proyectos de conservación activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php if (is_user_logged_in()) : ?>
            <button class="bl-btn bl-btn--primary bl-tab" data-tab="tab-crear">
                <?php esc_html_e('Crear el primero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Crear Proyecto -->
    <?php if (is_user_logged_in()) : ?>
    <div id="tab-crear" class="bl-tab-contenido" style="display: none;">
        <form class="bl-form bl-form-proyecto" method="post">
            <input type="hidden" name="bl_nonce" value="<?php echo esc_attr(wp_create_nonce('biodiversidad_nonce')); ?>">
            <div class="bl-form-grupo">
                <label for="bl-proyecto-titulo"><?php esc_html_e('Nombre del proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" name="titulo" id="bl-proyecto-titulo" required
                       placeholder="<?php esc_attr_e('Ej: Reforestación del parque municipal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="bl-form-grupo">
                <label for="bl-proyecto-tipo"><?php esc_html_e('Tipo de proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <select name="tipo" id="bl-proyecto-tipo" required>
                    <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_proyecto as $tipo_id => $tipo_data) : ?>
                    <option value="<?php echo esc_attr($tipo_id); ?>"><?php echo esc_html($tipo_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-proyecto-fecha"><?php esc_html_e('Fecha de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" name="fecha_inicio" id="bl-proyecto-fecha">
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-proyecto-max"><?php esc_html_e('Máximo participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" name="participantes_max" id="bl-proyecto-max" min="0" value="0"
                           placeholder="<?php esc_attr_e('0 = sin límite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-proyecto-ubicacion"><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="ubicacion" id="bl-proyecto-ubicacion"
                       placeholder="<?php esc_attr_e('Ej: Parque municipal, zona norte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="bl-form-grupo">
                <label for="bl-proyecto-descripcion"><?php esc_html_e('Descripción del proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <textarea name="descripcion" id="bl-proyecto-descripcion" rows="6" required
                          placeholder="<?php esc_attr_e('Describe los objetivos, actividades previstas, materiales necesarios, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php esc_html_e('Crear Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
