<?php
/**
 * Template: Formulario de verificación de certificado
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="verificar-certificado-wrapper" style="max-width: 500px; margin: 3rem auto; padding: 2rem;">
    <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 2rem; text-align: center;">
        <span class="dashicons dashicons-awards" style="font-size: 48px; width: 48px; height: 48px; color: #6366f1; margin-bottom: 1rem;"></span>

        <h2 style="margin: 0 0 0.5rem 0; color: #1f2937;"><?php _e('Verificar Certificado', 'flavor-chat-ia'); ?></h2>
        <p style="color: #6b7280; margin-bottom: 1.5rem;">
            <?php _e('Introduce el código de verificación para comprobar la autenticidad del certificado.', 'flavor-chat-ia'); ?>
        </p>

        <form action="" method="get" style="display: flex; gap: 0.5rem;">
            <input
                type="text"
                name="codigo"
                placeholder="<?php esc_attr_e('Ej: CERT-ABC12345', 'flavor-chat-ia'); ?>"
                required
                style="flex: 1; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem;"
            >
            <button
                type="submit"
                style="padding: 0.75rem 1.5rem; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-weight: 500; cursor: pointer;"
            >
                <?php _e('Verificar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
