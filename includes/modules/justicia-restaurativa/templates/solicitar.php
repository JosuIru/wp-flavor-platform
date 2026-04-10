<?php
/**
 * Template: Solicitar Mediación
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$justicia_restaurativa_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Justicia_Restaurativa_Module')
    : 'Flavor_Chat_Justicia_Restaurativa_Module';
$tipos = $justicia_restaurativa_module_class::TIPOS_PROCESO;
?>

<div class="jr-solicitar">
    <form class="jr-solicitar__form jr-form-solicitar">
        <header class="jr-solicitar__header">
            <h2><?php esc_html_e('Solicitar mediación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php esc_html_e('Inicia un proceso de diálogo facilitado para resolver un conflicto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </header>

        <div class="jr-aviso-confidencial">
            <span class="dashicons dashicons-lock"></span>
            <p><?php esc_html_e('Toda la información que proporciones es estrictamente confidencial. Solo será visible para el mediador asignado y, con tu consentimiento, para la otra parte.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-tipo"><?php esc_html_e('Tipo de proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="tipo" id="jr-tipo" required>
                <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
                <option value="<?php echo esc_attr($tipo_id); ?>">
                    <?php echo esc_html($tipo_data['nombre']); ?> - <?php echo esc_html($tipo_data['descripcion']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-otra-parte"><?php esc_html_e('Email de la otra persona (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="email" name="otra_parte_email" id="jr-otra-parte"
                   placeholder="<?php esc_attr_e('correo@ejemplo.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <p class="description">
                <?php esc_html_e('Si conoces el email de la otra persona, le enviaremos una invitación a participar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-descripcion"><?php esc_html_e('Describe la situación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
            <textarea name="descripcion" id="jr-descripcion" rows="6" required
                      placeholder="<?php esc_attr_e('Describe brevemente qué ha ocurrido y qué te gustaría resolver...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            <p class="description">
                <?php esc_html_e('Esta información es confidencial y ayudará al mediador a entender la situación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <div class="jr-solicitar__submit">
            <button type="submit" class="jr-btn jr-btn--primary">
                <span class="dashicons dashicons-shield"></span>
                <?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>
