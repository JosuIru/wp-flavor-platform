<?php
/**
 * Solicitudes de Empresas - Admin
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-modulo-page">
    <?php $this->render_page_header(__('Solicitudes de registro', 'flavor-platform')); ?>

    <?php if (empty($solicitudes)): ?>
    <div style="background:#fff;padding:40px;border-radius:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <span class="dashicons dashicons-yes-alt" style="font-size:48px;width:48px;height:48px;color:#10b981;"></span>
        <h3 style="margin:16px 0 8px;"><?php esc_html_e('No hay solicitudes pendientes', 'flavor-platform'); ?></h3>
        <p style="color:#666;"><?php esc_html_e('Todas las solicitudes han sido procesadas.', 'flavor-platform'); ?></p>
    </div>
    <?php else: ?>

    <div style="display:grid;gap:16px;">
        <?php foreach ($solicitudes as $sol): ?>
        <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border-left:4px solid #f59e0b;">
            <div style="display:flex;justify-content:space-between;align-items:start;">
                <div>
                    <h3 style="margin:0 0 8px;font-size:18px;"><?php echo esc_html($sol->nombre); ?></h3>
                    <?php if ($sol->razon_social && $sol->razon_social !== $sol->nombre): ?>
                    <p style="margin:0 0 8px;color:#666;"><?php echo esc_html($sol->razon_social); ?></p>
                    <?php endif; ?>

                    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;margin-top:12px;">
                        <?php if ($sol->cif_nif): ?>
                        <span><strong><?php esc_html_e('CIF/NIF:', 'flavor-platform'); ?></strong> <?php echo esc_html($sol->cif_nif); ?></span>
                        <?php endif; ?>
                        <span><strong><?php esc_html_e('Tipo:', 'flavor-platform'); ?></strong> <?php echo esc_html(strtoupper($sol->tipo)); ?></span>
                        <?php if ($sol->sector): ?>
                        <span><strong><?php esc_html_e('Sector:', 'flavor-platform'); ?></strong> <?php echo esc_html(ucfirst($sol->sector)); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($sol->email || $sol->telefono): ?>
                    <div style="margin-top:12px;font-size:13px;color:#666;">
                        <?php if ($sol->email): ?>
                        <span class="dashicons dashicons-email" style="font-size:14px;"></span> <?php echo esc_html($sol->email); ?>
                        <?php endif; ?>
                        <?php if ($sol->telefono): ?>
                        <span style="margin-left:16px;"><span class="dashicons dashicons-phone" style="font-size:14px;"></span> <?php echo esc_html($sol->telefono); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($sol->descripcion): ?>
                    <p style="margin:12px 0 0;color:#444;font-size:13px;max-width:600px;">
                        <?php echo esc_html(wp_trim_words($sol->descripcion, 30)); ?>
                    </p>
                    <?php endif; ?>

                    <p style="margin:12px 0 0;font-size:12px;color:#999;">
                        <?php printf(esc_html__('Solicitado el %s', 'flavor-platform'), date_i18n('d/m/Y H:i', strtotime($sol->created_at))); ?>
                        <?php
                        $creador = get_userdata($sol->creador_id);
                        if ($creador):
                        ?>
                        <?php printf(esc_html__('por %s', 'flavor-platform'), esc_html($creador->display_name)); ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <form method="post" style="display:flex;flex-direction:column;gap:8px;">
                        <?php wp_nonce_field('gestionar_solicitud'); ?>
                        <input type="hidden" name="empresa_id" value="<?php echo esc_attr($sol->id); ?>" />

                        <button type="submit" name="accion_solicitud" value="aprobar" class="button button-primary">
                            <span class="dashicons dashicons-yes" style="vertical-align:middle;"></span>
                            <?php esc_html_e('Aprobar', 'flavor-platform'); ?>
                        </button>

                        <button type="button" class="button" onclick="this.nextElementSibling.style.display='block';this.style.display='none';">
                            <span class="dashicons dashicons-no" style="vertical-align:middle;"></span>
                            <?php esc_html_e('Rechazar', 'flavor-platform'); ?>
                        </button>

                        <div style="display:none;">
                            <textarea name="motivo" placeholder="<?php esc_attr_e('Motivo del rechazo (opcional)...', 'flavor-platform'); ?>" style="width:200px;height:60px;margin-bottom:8px;"></textarea>
                            <button type="submit" name="accion_solicitud" value="rechazar" class="button" style="width:100%;">
                                <?php esc_html_e('Confirmar rechazo', 'flavor-platform'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
