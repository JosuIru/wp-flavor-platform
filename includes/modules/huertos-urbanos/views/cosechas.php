<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
$cultivos = $wpdb->get_results("SELECT * FROM $tabla_cultivos ORDER BY fecha_plantacion DESC LIMIT 50");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Registro de Cosechas', 'flavor-chat-ia'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Cultivo', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha Plantación', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha Cosecha', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cultivos as $cultivo) : ?>
                <tr>
                    <td><?php echo esc_html($cultivo->tipo_cultivo); ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($cultivo->fecha_plantacion)); ?></td>
                    <td><?php echo $cultivo->fecha_cosecha ? date_i18n(get_option('date_format'), strtotime($cultivo->fecha_cosecha)) : '-'; ?></td>
                    <td><?php echo $cultivo->cantidad_kg ? number_format($cultivo->cantidad_kg, 2) . ' kg' : '-'; ?></td>
                    <td><?php echo esc_html($cultivo->estado); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
