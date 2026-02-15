<?php
/**
 * Template: Publicar Oferta
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="td-empty-state"><p>' . esc_html__('Debes iniciar sesión para publicar ofertas.', 'flavor-chat-ia') . '</p></div>';
    return;
}

$tipos = Flavor_Chat_Trabajo_Digno_Module::TIPOS_OFERTA;
$sectores = Flavor_Chat_Trabajo_Digno_Module::SECTORES;
$jornadas = Flavor_Chat_Trabajo_Digno_Module::JORNADAS;
$criterios = Flavor_Chat_Trabajo_Digno_Module::CRITERIOS_DIGNIDAD;
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Publicar Oferta de Trabajo', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Comparte oportunidades laborales con criterios de trabajo digno', 'flavor-chat-ia'); ?></p>
    </header>

    <form class="td-form td-form-oferta">
        <div class="td-form-grupo">
            <label for="td-titulo"><?php esc_html_e('Título del puesto', 'flavor-chat-ia'); ?> *</label>
            <input type="text" name="titulo" id="td-titulo" required
                   placeholder="<?php esc_attr_e('Ej: Técnico/a en energías renovables', 'flavor-chat-ia'); ?>">
        </div>

        <div class="td-form-row">
            <div class="td-form-grupo">
                <label for="td-tipo"><?php esc_html_e('Tipo de oferta', 'flavor-chat-ia'); ?> *</label>
                <select name="tipo" id="td-tipo" required>
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
                    <option value="<?php echo esc_attr($tipo_id); ?>"><?php echo esc_html($tipo_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="td-form-grupo">
                <label for="td-sector"><?php esc_html_e('Sector', 'flavor-chat-ia'); ?></label>
                <select name="sector" id="td-sector">
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($sectores as $sector_id => $sector_data) : ?>
                    <option value="<?php echo esc_attr($sector_id); ?>"><?php echo esc_html($sector_data['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="td-form-row">
            <div class="td-form-grupo">
                <label for="td-jornada"><?php esc_html_e('Jornada', 'flavor-chat-ia'); ?></label>
                <select name="jornada" id="td-jornada">
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($jornadas as $jornada_id => $jornada_nombre) : ?>
                    <option value="<?php echo esc_attr($jornada_id); ?>"><?php echo esc_html($jornada_nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="td-form-grupo">
                <label for="td-ubicacion"><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></label>
                <input type="text" name="ubicacion" id="td-ubicacion"
                       placeholder="<?php esc_attr_e('Ej: Madrid, remoto, híbrido...', 'flavor-chat-ia'); ?>">
            </div>
        </div>

        <div class="td-form-grupo">
            <label for="td-salario"><?php esc_html_e('Salario / Retribución', 'flavor-chat-ia'); ?></label>
            <input type="text" name="salario" id="td-salario"
                   placeholder="<?php esc_attr_e('Ej: 25.000-30.000€/año, A convenir, Por horas...', 'flavor-chat-ia'); ?>">
        </div>

        <div class="td-form-grupo">
            <label for="td-descripcion"><?php esc_html_e('Descripción del puesto', 'flavor-chat-ia'); ?> *</label>
            <textarea name="descripcion" id="td-descripcion" rows="8" required
                      placeholder="<?php esc_attr_e('Describe las funciones, requisitos, condiciones y beneficios...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <div class="td-form-grupo">
            <label><?php esc_html_e('Criterios de Trabajo Digno', 'flavor-chat-ia'); ?></label>
            <p style="color: var(--td-text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                <?php esc_html_e('Selecciona los criterios que cumple esta oferta. Esto ayuda a los candidatos a valorar la calidad del empleo.', 'flavor-chat-ia'); ?>
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
                <?php esc_html_e('Publicar Oferta', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>
