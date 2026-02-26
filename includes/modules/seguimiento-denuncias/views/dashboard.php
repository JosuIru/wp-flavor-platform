<?php if (!defined('ABSPATH')) exit; ?>
<div class="flavor-dashboard-widget">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
        <div style="text-align: center; padding: 1rem; background: #fef3c7; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #d97706;"><?php echo $estadisticas['activas']; ?></div>
            <div style="font-size: 0.85rem; color: #92400e;">En tramite</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #f3f4f6; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #6b7280;"><?php echo $estadisticas['silencio']; ?></div>
            <div style="font-size: 0.85rem; color: #374151;">Silencio</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #dcfce7; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #16a34a;"><?php echo $estadisticas['resueltas']; ?></div>
            <div style="font-size: 0.85rem; color: #166534;">Resueltas</div>
        </div>
    </div>
    <a href="<?php echo home_url('/denuncias/'); ?>" class="flavor-btn flavor-btn-secondary" style="width: 100%; text-align: center;">Ver seguimiento</a>
</div>
