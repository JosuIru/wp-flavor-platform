<?php
/**
 * Template: Darse de baja
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="em-baja-wrapper">
    <div class="em-baja-content">
        <div class="em-baja-icono">
            <span class="dashicons dashicons-email"></span>
        </div>

        <h2><?php _e('Darse de baja', 'flavor-chat-ia'); ?></h2>

        <p class="em-baja-email">
            <?php printf(__('Email: %s', 'flavor-chat-ia'), '<strong>' . esc_html($suscriptor->email) . '</strong>'); ?>
        </p>

        <p class="em-baja-descripcion">
            <?php _e('Lamentamos verte partir. Si te das de baja, dejarás de recibir nuestros emails.', 'flavor-chat-ia'); ?>
        </p>

        <form id="em-form-baja" class="em-baja-form" data-token="<?php echo esc_attr($token); ?>">
            <div class="em-form-campo">
                <label><?php _e('¿Por qué te das de baja? (opcional)', 'flavor-chat-ia'); ?></label>
                <select name="motivo" class="em-select">
                    <option value=""><?php _e('Seleccionar motivo', 'flavor-chat-ia'); ?></option>
                    <option value="muchos_emails"><?php _e('Recibo demasiados emails', 'flavor-chat-ia'); ?></option>
                    <option value="no_relevante"><?php _e('El contenido no es relevante para mí', 'flavor-chat-ia'); ?></option>
                    <option value="no_suscribi"><?php _e('No recuerdo haberme suscrito', 'flavor-chat-ia'); ?></option>
                    <option value="otro"><?php _e('Otro motivo', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="em-form-campo em-campo-otro" style="display:none;">
                <textarea name="motivo_otro" rows="3" placeholder="<?php esc_attr_e('Cuéntanos más...', 'flavor-chat-ia'); ?>" class="em-textarea"></textarea>
            </div>

            <div class="em-baja-acciones">
                <a href="<?php echo esc_url(add_query_arg('token', $token, home_url('/preferencias-email/'))); ?>" class="em-btn em-btn-secondary">
                    <?php _e('Mejor gestionar preferencias', 'flavor-chat-ia'); ?>
                </a>

                <button type="submit" class="em-btn em-btn-danger">
                    <?php _e('Confirmar baja', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div class="em-form-mensaje" style="display:none;"></div>
        </form>
    </div>
</div>
