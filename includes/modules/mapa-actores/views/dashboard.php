<?php if (!defined('ABSPATH')) exit; ?>
<div class="flavor-dashboard-widget">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
        <div style="text-align: center; padding: 1rem; background: #eff6ff; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #2563eb;"><?php echo $estadisticas['total']; ?></div>
            <div style="font-size: 0.85rem; color: #1e40af;">Actores</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #dcfce7; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #16a34a;"><?php echo $estadisticas['aliados']; ?></div>
            <div style="font-size: 0.85rem; color: #166534;">Aliados</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #fee2e2; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #dc2626;"><?php echo $estadisticas['opositores']; ?></div>
            <div style="font-size: 0.85rem; color: #991b1b;">Opositores</div>
        </div>
    </div>
    <a href="<?php echo home_url('/mapa-actores/'); ?>" class="flavor-btn flavor-btn-secondary" style="width: 100%; text-align: center;">Ver mapa</a>
</div>
