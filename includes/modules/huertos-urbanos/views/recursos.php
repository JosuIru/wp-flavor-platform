<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_herramientas = $wpdb->prefix . 'flavor_huertos_herramientas';
?>
<div class="wrap">
    <h1><?php echo esc_html__('Recursos y Herramientas', 'flavor-chat-ia'); ?></h1>
    <div class="flavor-recursos-grid">
        <div class="flavor-recurso-card">
            <h3><?php echo esc_html__('Herramientas Disponibles', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li>Palas - 5 unidades</li>
                <li>Azadas - 3 unidades</li>
                <li>Regaderas - 8 unidades</li>
                <li>Tijeras de poda - 4 unidades</li>
            </ul>
        </div>
        <div class="flavor-recurso-card">
            <h3><?php echo esc_html__('Recursos Comunes', 'flavor-chat-ia'); ?></h3>
            <ul>
                <li>Sistema de riego automático</li>
                <li>Composteras comunitarias</li>
                <li>Almacén de herramientas</li>
                <li>Zona de descanso</li>
            </ul>
        </div>
    </div>
</div>
<style>
.flavor-recursos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
.flavor-recurso-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.flavor-recurso-card h3 { margin-top: 0; color: #28a745; }
.flavor-recurso-card ul { margin: 0; padding-left: 20px; }
.flavor-recurso-card li { margin-bottom: 10px; }
</style>
