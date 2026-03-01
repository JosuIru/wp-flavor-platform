<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_huertos = $wpdb->prefix . 'flavor_huertos';
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';

$total_huertos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_huertos WHERE estado = 'activo'");
$total_parcelas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas");
$parcelas_ocupadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'ocupada'");
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Dashboard - Huertos Urbanos', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rapidos -->
    <div class="huertos-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=huertos-parcelas'); ?>" class="huertos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-site-alt" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Parcelas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=huertos-huertanos'); ?>" class="huertos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Huertanos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=huertos-cosechas'); ?>" class="huertos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-carrot" style="font-size: 24px; color: #d63638;"></span>
            <span><?php echo esc_html__('Cosechas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=huertos-recursos'); ?>" class="huertos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-tools" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Recursos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=huertos-config'); ?>" class="huertos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <div class="flavor-stats-cards">
        <div class="flavor-stat-card">
            <h3><?php echo $total_huertos; ?></h3>
            <p><?php echo esc_html__('Huertos Activos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="flavor-stat-card">
            <h3><?php echo $total_parcelas; ?></h3>
            <p><?php echo esc_html__('Parcelas Total', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="flavor-stat-card">
            <h3><?php echo $parcelas_ocupadas; ?></h3>
            <p><?php echo esc_html__('Parcelas Ocupadas', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="flavor-stat-card">
            <h3><?php echo $total_parcelas - $parcelas_ocupadas; ?></h3>
            <p><?php echo esc_html__('Parcelas Disponibles', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
</div>
<style>
.flavor-stats-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
.flavor-stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
.flavor-stat-card h3 { font-size: 36px; margin: 0 0 10px; color: #28a745; }
.flavor-stat-card p { margin: 0; color: #666; }
</style>
