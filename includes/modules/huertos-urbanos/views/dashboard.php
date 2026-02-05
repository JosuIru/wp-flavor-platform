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
    <h1><?php echo esc_html__('Dashboard - Huertos Urbanos', 'flavor-chat-ia'); ?></h1>
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
