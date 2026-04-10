<?php
/**
 * Template: Registrar Avistamiento
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Encolar estilos del módulo
wp_enqueue_style(
    'flavor-biodiversidad-local',
    FLAVOR_PLATFORM_URL . 'includes/modules/biodiversidad-local/assets/css/biodiversidad-local.css',
    [],
    FLAVOR_PLATFORM_VERSION
);

$biodiversidad_local_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Biodiversidad_Local_Module')
    : 'Flavor_Chat_Biodiversidad_Local_Module';
$categorias = $biodiversidad_local_module_class::CATEGORIAS_ESPECIES;
$estados = $biodiversidad_local_module_class::ESTADOS_CONSERVACION;
$habitats = $biodiversidad_local_module_class::TIPOS_HABITAT;

$especies = get_posts([
    'post_type' => 'bl_especie',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);
?>

<div class="bl-container">
    <header class="bl-header">
        <h2><?php esc_html_e('Registrar Avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Contribuye al catálogo de biodiversidad local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Tabs -->
    <div class="bl-tabs">
        <button class="bl-tab activo" data-tab="tab-avistamiento">
            <span class="dashicons dashicons-camera"></span>
            <?php esc_html_e('Registrar Avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <button class="bl-tab" data-tab="tab-especie">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Proponer Nueva Especie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>

    <!-- Tab: Registrar Avistamiento -->
    <div id="tab-avistamiento" class="bl-tab-contenido">
        <form class="bl-form bl-form-avistamiento">
            <div class="bl-form-grupo">
                <label for="bl-especie"><?php esc_html_e('Especie observada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <select name="especie_id" id="bl-especie" required>
                    <option value=""><?php esc_html_e('Selecciona una especie...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                    <optgroup label="<?php echo esc_attr($cat_data['nombre']); ?>">
                        <?php foreach ($especies as $especie) :
                            $terms = wp_get_post_terms($especie->ID, 'bl_categoria');
                            if (!empty($terms) && $terms[0]->slug === $cat_id) :
                        ?>
                        <option value="<?php echo esc_attr($especie->ID); ?>">
                            <?php echo esc_html($especie->post_title); ?>
                        </option>
                        <?php endif; endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                    <option value="desconocida"><?php esc_html_e('No identificada / Desconocida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-fecha"><?php esc_html_e('Fecha del avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="date" name="fecha" id="bl-fecha" required value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-cantidad"><?php esc_html_e('Cantidad de ejemplares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" name="cantidad" id="bl-cantidad" min="1" value="1">
                </div>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-habitat"><?php esc_html_e('Hábitat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="habitat" id="bl-habitat">
                    <option value=""><?php esc_html_e('Selecciona un hábitat...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($habitats as $hab_id => $hab_data) : ?>
                    <option value="<?php echo esc_attr($hab_id); ?>"><?php echo esc_html($hab_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bl-form-grupo">
                <label><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                    <input type="text" name="latitud" id="bl-latitud" placeholder="<?php esc_attr_e('Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="flex: 1;">
                    <input type="text" name="longitud" id="bl-longitud" placeholder="<?php esc_attr_e('Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="flex: 1;">
                    <button type="button" class="bl-btn bl-btn--secondary bl-btn-ubicacion">
                        <span class="dashicons dashicons-location-alt"></span>
                        <?php esc_html_e('Mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
                <div id="bl-mapa" class="bl-mapa bl-mapa-seleccionar" style="height: 300px; border-radius: 8px;"></div>
                <small style="color: var(--bl-text-light);"><?php esc_html_e('Haz clic en el mapa para marcar la ubicación exacta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-descripcion"><?php esc_html_e('Descripción / Notas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea name="descripcion" id="bl-descripcion" rows="4"
                          placeholder="<?php esc_attr_e('Describe lo que observaste: comportamiento, condiciones, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <span class="dashicons dashicons-camera"></span>
                    <?php esc_html_e('Registrar Avistamiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Tab: Proponer Nueva Especie -->
    <div id="tab-especie" class="bl-tab-contenido" style="display: none;">
        <form class="bl-form bl-form-especie">
            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-nombre-comun"><?php esc_html_e('Nombre común', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" name="nombre_comun" id="bl-nombre-comun" required
                           placeholder="<?php esc_attr_e('Ej: Jilguero europeo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-nombre-cientifico"><?php esc_html_e('Nombre científico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="nombre_cientifico" id="bl-nombre-cientifico"
                           placeholder="<?php esc_attr_e('Ej: Carduelis carduelis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-categoria"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <select name="categoria" id="bl-categoria" required>
                        <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                        <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-estado"><?php esc_html_e('Estado de conservación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado_conservacion" id="bl-estado">
                        <?php foreach ($estados as $est_id => $est_data) : ?>
                        <option value="<?php echo esc_attr($est_id); ?>"><?php echo esc_html($est_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="bl-form-grupo">
                <label><?php esc_html_e('Hábitats donde se encuentra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="bl-habitats-checkboxes">
                    <?php foreach ($habitats as $hab_id => $hab_data) : ?>
                    <label class="bl-habitat-checkbox">
                        <input type="checkbox" name="habitats[]" value="<?php echo esc_attr($hab_id); ?>">
                        <span class="dashicons <?php echo esc_attr($hab_data['icono']); ?>"></span>
                        <?php echo esc_html($hab_data['nombre']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-descripcion-especie"><?php esc_html_e('Descripción de la especie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <textarea name="descripcion" id="bl-descripcion-especie" rows="6" required
                          placeholder="<?php esc_attr_e('Describe la especie: características físicas, comportamiento, alimentación, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Proponer Especie', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>
