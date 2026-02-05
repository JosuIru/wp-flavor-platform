<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_recogidas = $wpdb->prefix . 'flavor_compostaje_recogidas';
$recogidas = $wpdb->get_results("SELECT * FROM $tabla_recogidas ORDER BY fecha_recogida DESC LIMIT 50");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Producción de Compost', 'flavor-chat-ia'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recogidas as $recogida) : ?>
                <tr>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($recogida->fecha_recogida)); ?></td>
                    <td><?php echo number_format($recogida->cantidad_kg, 2); ?> kg</td>
                    <td><?php echo esc_html(get_userdata($recogida->usuario_id)->display_name ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
