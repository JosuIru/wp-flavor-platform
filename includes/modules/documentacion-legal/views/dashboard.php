<?php
/**
 * Widget dashboard Documentacion Legal
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-dashboard-widget">
    <div class="flavor-widget-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
        <div style="text-align: center; padding: 1rem; background: #eff6ff; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #2563eb;"><?php echo number_format($estadisticas['total']); ?></div>
            <div style="font-size: 0.85rem; color: #1e40af;">Documentos</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #d97706;"><?php echo number_format($estadisticas['modelos']); ?></div>
            <div style="font-size: 0.85rem; color: #92400e;">Modelos</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #dcfce7; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #16a34a;"><?php echo number_format($estadisticas['descargas'] ?? 0); ?></div>
            <div style="font-size: 0.85rem; color: #166534;">Descargas</div>
        </div>
    </div>
    <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>" class="flavor-btn flavor-btn-secondary" style="width: 100%; text-align: center;">
        Ver repositorio
    </a>
</div>
