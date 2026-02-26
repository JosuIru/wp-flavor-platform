<?php
/**
 * Formulario de consentimientos
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-consent-form">
    <?php foreach ($consentimientos as $tipo => $info): ?>
        <div class="flavor-consent-item <?php echo $info['obligatorio'] ? 'obligatorio' : ''; ?>">
            <label class="flavor-checkbox-label">
                <input type="checkbox"
                       name="consent_<?php echo esc_attr($tipo); ?>"
                       class="flavor-consent-checkbox"
                       data-tipo="<?php echo esc_attr($tipo); ?>"
                       <?php checked($info['consentido']); ?>
                       <?php echo $info['obligatorio'] ? 'required' : ''; ?>>
                <span class="flavor-checkbox-custom"></span>
                <span class="flavor-checkbox-text">
                    <?php echo esc_html($info['descripcion']); ?>
                    <?php if ($info['obligatorio']): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </span>
            </label>
        </div>
    <?php endforeach; ?>

    <p class="flavor-consent-note">
        <small>Los campos marcados con * son obligatorios para utilizar el servicio.</small>
    </p>
</div>
