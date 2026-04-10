<?php
/**
 * Template: Publicar Oferta
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fallback: asegurar assets frontend del módulo cuando se renderiza fuera de su flujo principal.
if (!wp_style_is('flavor-trabajo-digno', 'registered')) {
    wp_register_style(
        'flavor-trabajo-digno',
        FLAVOR_PLATFORM_URL . 'includes/modules/trabajo-digno/assets/css/trabajo-digno.css',
        [],
        FLAVOR_PLATFORM_VERSION
    );
}
if (!wp_style_is('flavor-trabajo-digno', 'enqueued')) {
    wp_enqueue_style('flavor-trabajo-digno');
}
if (!wp_script_is('flavor-trabajo-digno', 'registered')) {
    wp_register_script(
        'flavor-trabajo-digno',
        FLAVOR_PLATFORM_URL . 'includes/modules/trabajo-digno/assets/js/trabajo-digno.js',
        ['jquery'],
        FLAVOR_PLATFORM_VERSION,
        true
    );
}
if (!wp_script_is('flavor-trabajo-digno', 'enqueued')) {
    wp_enqueue_script('flavor-trabajo-digno');
}
wp_localize_script('flavor-trabajo-digno', 'flavorTrabajoDigno', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('trabajo_digno_nonce'),
    'i18n' => [
        'error' => __('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'confirm_postular' => __('¿Confirmas tu postulación?', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
]);

if (!is_user_logged_in()) {
    echo '<div class="td-empty-state"><p>' . esc_html__('Debes iniciar sesión para publicar ofertas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

$trabajo_digno_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Trabajo_Digno_Module')
    : 'Flavor_Chat_Trabajo_Digno_Module';
$tipos = $trabajo_digno_module_class::TIPOS_OFERTA;
$sectores = $trabajo_digno_module_class::SECTORES;
$jornadas = $trabajo_digno_module_class::JORNADAS;
$criterios = $trabajo_digno_module_class::CRITERIOS_DIGNIDAD;
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Publicar Oferta de Trabajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Comparte oportunidades laborales con criterios de trabajo digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <form class="td-form td-form-oferta" method="post">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('trabajo_digno_nonce')); ?>">
        <div class="td-form-grupo">
            <label for="td-titulo"><?php esc_html_e('Título del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
            <input type="text" name="titulo" id="td-titulo" required
                   placeholder="<?php esc_attr_e('Ej: Técnico/a en energías renovables', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>

        <div class="td-form-row">
            <div class="td-form-grupo">
                <label for="td-tipo"><?php esc_html_e('Tipo de oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <select name="tipo" id="td-tipo" required>
                    <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
                    <option value="<?php echo esc_attr($tipo_id); ?>"><?php echo esc_html($tipo_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="td-form-grupo">
                <label for="td-sector"><?php esc_html_e('Sector', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="sector" id="td-sector">
                    <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($sectores as $sector_id => $sector_data) : ?>
                    <option value="<?php echo esc_attr($sector_id); ?>"><?php echo esc_html($sector_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="td-form-row">
            <div class="td-form-grupo">
                <label for="td-jornada"><?php esc_html_e('Jornada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="jornada" id="td-jornada">
                    <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($jornadas as $jornada_id => $jornada_nombre) : ?>
                    <option value="<?php echo esc_attr($jornada_id); ?>"><?php echo esc_html($jornada_nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="td-form-grupo">
                <label for="td-ubicacion"><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="ubicacion" id="td-ubicacion"
                       placeholder="<?php esc_attr_e('Ej: Madrid, remoto, híbrido...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
        </div>

        <div class="td-form-grupo">
            <label for="td-salario"><?php esc_html_e('Salario / Retribución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" name="salario" id="td-salario"
                   placeholder="<?php esc_attr_e('Ej: 25.000-30.000€/año, A convenir, Por horas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>

        <div class="td-form-grupo">
            <label for="td-descripcion"><?php esc_html_e('Descripción del puesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
            <textarea name="descripcion" id="td-descripcion" rows="8" required
                      placeholder="<?php esc_attr_e('Describe las funciones, requisitos, condiciones y beneficios...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
        </div>

        <div class="td-form-grupo">
            <label><?php esc_html_e('Criterios de Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <p style="color: var(--td-text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                <?php esc_html_e('Selecciona los criterios que cumple esta oferta. Esto ayuda a los candidatos a valorar la calidad del empleo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <div class="td-criterios-checkboxes">
                <?php foreach ($criterios as $criterio_id => $criterio_data) : ?>
                <label class="td-criterio-checkbox">
                    <input type="checkbox" name="criterios[]" value="<?php echo esc_attr($criterio_id); ?>">
                    <div class="td-criterio-checkbox__info">
                        <span class="td-criterio-checkbox__nombre">
                            <span class="dashicons <?php echo esc_attr($criterio_data['icono']); ?>"></span>
                            <?php echo esc_html($criterio_data['nombre']); ?>
                        </span>
                        <span class="td-criterio-checkbox__desc"><?php echo esc_html($criterio_data['descripcion']); ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <button type="submit" class="td-btn td-btn--primary">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Publicar Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>
