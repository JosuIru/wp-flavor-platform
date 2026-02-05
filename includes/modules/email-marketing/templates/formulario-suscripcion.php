<?php
/**
 * Template: Formulario de suscripción
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'em-form-' . uniqid();
?>

<div class="em-suscripcion-wrapper em-estilo-<?php echo esc_attr($atts['estilo']); ?>">
    <?php if ($atts['estilo'] === 'card'): ?>
        <div class="em-suscripcion-card">
    <?php endif; ?>

    <?php if (!empty($atts['titulo'])): ?>
        <h3 class="em-suscripcion-titulo"><?php echo esc_html($atts['titulo']); ?></h3>
    <?php endif; ?>

    <?php if (!empty($atts['descripcion'])): ?>
        <p class="em-suscripcion-descripcion"><?php echo esc_html($atts['descripcion']); ?></p>
    <?php endif; ?>

    <form id="<?php echo esc_attr($form_id); ?>" class="em-suscripcion-form" data-lista="<?php echo esc_attr($atts['lista']); ?>">
        <?php if ($atts['mostrar_nombre'] === 'true'): ?>
            <div class="em-form-campo">
                <input type="text" name="nombre" placeholder="<?php esc_attr_e('Tu nombre', 'flavor-chat-ia'); ?>" class="em-input">
            </div>
        <?php endif; ?>

        <div class="em-form-campo em-campo-email">
            <input type="email" name="email" placeholder="<?php esc_attr_e('Tu email', 'flavor-chat-ia'); ?>" required class="em-input">
        </div>

        <button type="submit" class="em-btn em-btn-suscribir">
            <span class="em-btn-texto"><?php echo esc_html($atts['boton']); ?></span>
            <span class="em-btn-loading" style="display:none;">
                <span class="em-spinner"></span>
            </span>
        </button>

        <div class="em-form-mensaje" style="display:none;"></div>

        <p class="em-privacidad">
            <small>
                <?php
                printf(
                    __('Al suscribirte, aceptas nuestra %spolítica de privacidad%s.', 'flavor-chat-ia'),
                    '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                    '</a>'
                );
                ?>
            </small>
        </p>
    </form>

    <?php if ($atts['estilo'] === 'card'): ?>
        </div>
    <?php endif; ?>
</div>
