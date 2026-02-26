<?php
/**
 * Widget de dashboard para Campanias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;
?>

<div class="flavor-dashboard-widget flavor-campanias-widget">
    <div class="flavor-widget-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem;">
        <div class="flavor-stat-card" style="text-align: center; padding: 1rem; background: #fef2f2; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #dc2626;">
                <?php echo number_format($estadisticas['activas']); ?>
            </div>
            <div style="font-size: 0.85rem; color: #991b1b;">Campanias activas</div>
        </div>

        <div class="flavor-stat-card" style="text-align: center; padding: 1rem; background: #f0fdf4; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #16a34a;">
                <?php echo number_format($estadisticas['total_firmas'] ?? 0); ?>
            </div>
            <div style="font-size: 0.85rem; color: #166534;">Firmas recogidas</div>
        </div>

        <div class="flavor-stat-card" style="text-align: center; padding: 1rem; background: #eff6ff; border-radius: 8px;">
            <div style="font-size: 1.75rem; font-weight: 700; color: #2563eb;">
                <?php echo number_format($estadisticas['proximas_acciones']); ?>
            </div>
            <div style="font-size: 0.85rem; color: #1e40af;">Acciones programadas</div>
        </div>
    </div>

    <div class="flavor-widget-actions" style="display: flex; gap: 0.5rem;">
        <a href="<?php echo esc_url(home_url('/campanias/')); ?>" class="flavor-btn flavor-btn-secondary" style="flex: 1; text-align: center;">
            Ver campanias
        </a>
        <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="flavor-btn flavor-btn-primary" style="flex: 1; text-align: center;">
            Nueva campania
        </a>
    </div>
</div>
