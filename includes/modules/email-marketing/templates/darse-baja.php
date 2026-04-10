<?php
/**
 * Template: Darse de baja
 *
 * @package FlavorPlatform
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

        <h2><?php _e('Darse de baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <p class="em-baja-email">
            <?php printf(__('Email: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), '<strong>' . esc_html($suscriptor->email) . '</strong>'); ?>
        </p>

        <p class="em-baja-descripcion">
            <?php _e('Lamentamos verte partir. Si te das de baja, dejarás de recibir nuestros emails.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <form id="em-form-baja" class="em-baja-form" data-token="<?php echo esc_attr($token); ?>">
            <div class="em-form-campo">
                <label><?php _e('¿Por qué te das de baja? (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="motivo" class="em-select">
                    <option value=""><?php _e('Seleccionar motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="muchos_emails"><?php _e('Recibo demasiados emails', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="no_relevante"><?php _e('El contenido no es relevante para mí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="no_suscribi"><?php _e('No recuerdo haberme suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="otro"><?php _e('Otro motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="em-form-campo em-campo-otro" style="display:none;">
                <textarea name="motivo_otro" rows="3" placeholder="<?php esc_attr_e('Cuéntanos más...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="em-textarea"></textarea>
            </div>

            <div class="em-baja-acciones">
                <a href="<?php echo esc_url(add_query_arg('token', $token, home_url('/preferencias-email/'))); ?>" class="em-btn em-btn-secondary">
                    <?php _e('Mejor gestionar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>

                <button type="submit" class="em-btn em-btn-danger">
                    <?php _e('Confirmar baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <div class="em-form-mensaje" style="display:none;"></div>
        </form>
    </div>
</div>
