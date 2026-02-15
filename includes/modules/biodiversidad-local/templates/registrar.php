<?php
/**
 * Template: Registrar Avistamiento
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = Flavor_Chat_Biodiversidad_Local_Module::CATEGORIAS_ESPECIES;
$estados = Flavor_Chat_Biodiversidad_Local_Module::ESTADOS_CONSERVACION;
$habitats = Flavor_Chat_Biodiversidad_Local_Module::TIPOS_HABITAT;

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
        <h2><?php esc_html_e('Registrar Avistamiento', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Contribuye al catálogo de biodiversidad local', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Tabs -->
    <div class="bl-tabs">
        <button class="bl-tab activo" data-tab="tab-avistamiento">
            <span class="dashicons dashicons-camera"></span>
            <?php esc_html_e('Registrar Avistamiento', 'flavor-chat-ia'); ?>
        </button>
        <button class="bl-tab" data-tab="tab-especie">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Proponer Nueva Especie', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Tab: Registrar Avistamiento -->
    <div id="tab-avistamiento" class="bl-tab-contenido">
        <form class="bl-form bl-form-avistamiento">
            <div class="bl-form-grupo">
                <label for="bl-especie"><?php esc_html_e('Especie observada', 'flavor-chat-ia'); ?> *</label>
                <select name="especie_id" id="bl-especie" required>
                    <option value=""><?php esc_html_e('Selecciona una especie...', 'flavor-chat-ia'); ?></option>
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
                    <option value="desconocida"><?php esc_html_e('No identificada / Desconocida', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-fecha"><?php esc_html_e('Fecha del avistamiento', 'flavor-chat-ia'); ?> *</label>
                    <input type="date" name="fecha" id="bl-fecha" required value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-cantidad"><?php esc_html_e('Cantidad de ejemplares', 'flavor-chat-ia'); ?></label>
                    <input type="number" name="cantidad" id="bl-cantidad" min="1" value="1">
                </div>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-habitat"><?php esc_html_e('Hábitat', 'flavor-chat-ia'); ?></label>
                <select name="habitat" id="bl-habitat">
                    <option value=""><?php esc_html_e('Selecciona un hábitat...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($habitats as $hab_id => $hab_data) : ?>
                    <option value="<?php echo esc_attr($hab_id); ?>"><?php echo esc_html($hab_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bl-form-grupo">
                <label><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></label>
                <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                    <input type="text" name="latitud" id="bl-latitud" placeholder="<?php esc_attr_e('Latitud', 'flavor-chat-ia'); ?>" style="flex: 1;">
                    <input type="text" name="longitud" id="bl-longitud" placeholder="<?php esc_attr_e('Longitud', 'flavor-chat-ia'); ?>" style="flex: 1;">
                    <button type="button" class="bl-btn bl-btn--secondary bl-btn-ubicacion">
                        <span class="dashicons dashicons-location-alt"></span>
                        <?php esc_html_e('Mi ubicación', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <div id="bl-mapa" class="bl-mapa bl-mapa-seleccionar" style="height: 300px; border-radius: 8px;"></div>
                <small style="color: var(--bl-text-light);"><?php esc_html_e('Haz clic en el mapa para marcar la ubicación exacta', 'flavor-chat-ia'); ?></small>
            </div>

            <div class="bl-form-grupo">
                <label for="bl-descripcion"><?php esc_html_e('Descripción / Notas', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" id="bl-descripcion" rows="4"
                          placeholder="<?php esc_attr_e('Describe lo que observaste: comportamiento, condiciones, etc.', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <span class="dashicons dashicons-camera"></span>
                    <?php esc_html_e('Registrar Avistamiento', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Tab: Proponer Nueva Especie -->
    <div id="tab-especie" class="bl-tab-contenido" style="display: none;">
        <form class="bl-form bl-form-especie">
            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-nombre-comun"><?php esc_html_e('Nombre común', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="nombre_comun" id="bl-nombre-comun" required
                           placeholder="<?php esc_attr_e('Ej: Jilguero europeo', 'flavor-chat-ia'); ?>">
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-nombre-cientifico"><?php esc_html_e('Nombre científico', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="nombre_cientifico" id="bl-nombre-cientifico"
                           placeholder="<?php esc_attr_e('Ej: Carduelis carduelis', 'flavor-chat-ia'); ?>">
                </div>
            </div>

            <div class="bl-form-row">
                <div class="bl-form-grupo">
                    <label for="bl-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?> *</label>
                    <select name="categoria" id="bl-categoria" required>
                        <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                        <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bl-form-grupo">
                    <label for="bl-estado"><?php esc_html_e('Estado de conservación', 'flavor-chat-ia'); ?></label>
                    <select name="estado_conservacion" id="bl-estado">
                        <?php foreach ($estados as $est_id => $est_data) : ?>
                        <option value="<?php echo esc_attr($est_id); ?>"><?php echo esc_html($est_data['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="bl-form-grupo">
                <label><?php esc_html_e('Hábitats donde se encuentra', 'flavor-chat-ia'); ?></label>
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
                <label for="bl-descripcion-especie"><?php esc_html_e('Descripción de la especie', 'flavor-chat-ia'); ?> *</label>
                <textarea name="descripcion" id="bl-descripcion-especie" rows="6" required
                          placeholder="<?php esc_attr_e('Describe la especie: características físicas, comportamiento, alimentación, etc.', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Proponer Especie', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
