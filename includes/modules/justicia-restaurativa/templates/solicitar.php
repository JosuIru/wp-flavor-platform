<?php
/**
 * Template: Solicitar Mediación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos = Flavor_Chat_Justicia_Restaurativa_Module::TIPOS_PROCESO;
?>

<div class="jr-solicitar">
    <form class="jr-solicitar__form jr-form-solicitar">
        <header class="jr-solicitar__header">
            <h2><?php esc_html_e('Solicitar mediación', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Inicia un proceso de diálogo facilitado para resolver un conflicto', 'flavor-chat-ia'); ?></p>
        </header>

        <div class="jr-aviso-confidencial">
            <span class="dashicons dashicons-lock"></span>
            <p><?php esc_html_e('Toda la información que proporciones es estrictamente confidencial. Solo será visible para el mediador asignado y, con tu consentimiento, para la otra parte.', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-tipo"><?php esc_html_e('Tipo de proceso', 'flavor-chat-ia'); ?></label>
            <select name="tipo" id="jr-tipo" required>
                <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
                <option value="<?php echo esc_attr($tipo_id); ?>">
                    <?php echo esc_html($tipo_data['nombre']); ?> - <?php echo esc_html($tipo_data['descripcion']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-otra-parte"><?php esc_html_e('Email de la otra persona (opcional)', 'flavor-chat-ia'); ?></label>
            <input type="email" name="otra_parte_email" id="jr-otra-parte"
                   placeholder="<?php esc_attr_e('correo@ejemplo.com', 'flavor-chat-ia'); ?>">
            <p class="description">
                <?php esc_html_e('Si conoces el email de la otra persona, le enviaremos una invitación a participar.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="jr-form-grupo">
            <label for="jr-descripcion"><?php esc_html_e('Describe la situación', 'flavor-chat-ia'); ?> *</label>
            <textarea name="descripcion" id="jr-descripcion" rows="6" required
                      placeholder="<?php esc_attr_e('Describe brevemente qué ha ocurrido y qué te gustaría resolver...', 'flavor-chat-ia'); ?>"></textarea>
            <p class="description">
                <?php esc_html_e('Esta información es confidencial y ayudará al mediador a entender la situación.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="jr-solicitar__submit">
            <button type="submit" class="jr-btn jr-btn--primary">
                <span class="dashicons dashicons-shield"></span>
                <?php esc_html_e('Enviar solicitud', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>
