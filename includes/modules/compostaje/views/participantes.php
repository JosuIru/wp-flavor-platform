<?php
/**
 * Vista Participantes - Módulo Compostaje
 * Gestión de participantes del programa de compostaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tabla_depositos = $wpdb->prefix . 'flavor_compostaje_depositos';

// Obtener participantes activos
$participantes = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email,
           COUNT(d.id) as total_depositos,
           SUM(d.cantidad_kg) as total_kg,
           MAX(d.fecha_deposito) as ultimo_deposito
    FROM {$wpdb->users} u
    INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
    GROUP BY u.ID
    ORDER BY total_kg DESC
");
?>

<div class="wrap">
    <h1><?php echo esc_html__('Participantes - Compostaje', 'flavor-chat-ia'); ?></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Total Depositado', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Depósitos', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Último Depósito', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($participantes as $participante) : ?>
                <tr>
                    <td><?php echo esc_html($participante->display_name); ?></td>
                    <td><?php echo esc_html($participante->user_email); ?></td>
                    <td><?php echo number_format($participante->total_kg, 2); ?> kg</td>
                    <td><?php echo number_format($participante->total_depositos); ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($participante->ultimo_deposito)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
