<?php
/**
 * Template: Verificar Certificado
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="certificado-wrapper" style="max-width: 700px; margin: 2rem auto; padding: 2rem;">
    <?php if (!$certificado): ?>
        <div class="certificado-no-encontrado" style="text-align: center; padding: 3rem; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <span class="dashicons dashicons-dismiss" style="font-size: 48px; width: 48px; height: 48px; color: #ef4444; margin-bottom: 1rem;"></span>
            <h2 style="color: #1f2937; margin: 0 0 0.5rem 0;"><?php _e('Certificado no encontrado', 'flavor-chat-ia'); ?></h2>
            <p style="color: #6b7280;"><?php _e('El código de verificación no corresponde a ningún certificado válido.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <?php
        $alumno = get_userdata($certificado->alumno_id);
        ?>
        <div class="certificado-valido" style="background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
            <div class="certificado-header" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 2rem; text-align: center; color: #fff;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 1rem;"></span>
                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;"><?php _e('Certificado Válido', 'flavor-chat-ia'); ?></h2>
                <p style="margin: 0; opacity: 0.9;"><?php _e('Este certificado es auténtico y verificable', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="certificado-body" style="padding: 2rem;">
                <div class="certificado-campo" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">
                        <?php _e('Otorgado a', 'flavor-chat-ia'); ?>
                    </label>
                    <div style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">
                        <?php echo esc_html($alumno ? $alumno->display_name : __('Usuario', 'flavor-chat-ia')); ?>
                    </div>
                </div>

                <div class="certificado-campo" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">
                        <?php _e('Por completar el curso', 'flavor-chat-ia'); ?>
                    </label>
                    <div style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">
                        <?php echo esc_html($certificado->curso_titulo); ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                    <div class="certificado-campo">
                        <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">
                            <?php _e('Fecha de emisión', 'flavor-chat-ia'); ?>
                        </label>
                        <div style="color: #1f2937;">
                            <?php echo date_i18n(get_option('date_format'), strtotime($certificado->fecha_emision)); ?>
                        </div>
                    </div>

                    <div class="certificado-campo">
                        <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.25rem;">
                            <?php _e('Duración del curso', 'flavor-chat-ia'); ?>
                        </label>
                        <div style="color: #1f2937;">
                            <?php printf(__('%d horas', 'flavor-chat-ia'), $certificado->duracion_horas); ?>
                        </div>
                    </div>
                </div>

                <div class="certificado-codigo" style="margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; text-align: center;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 0.5rem;">
                        <?php _e('Código de verificación', 'flavor-chat-ia'); ?>
                    </label>
                    <div style="font-size: 1.25rem; font-weight: 700; font-family: monospace; color: #6366f1; letter-spacing: 2px;">
                        <?php echo esc_html($certificado->codigo_verificacion); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
