<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_mantenimiento = $wpdb->prefix . 'flavor_compostaje_mantenimiento';
$tareas = $wpdb->get_results("SELECT * FROM $tabla_mantenimiento ORDER BY fecha_programada DESC");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Mantenimiento - Compostaje', 'flavor-chat-ia'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tareas as $tarea) : ?>
                <tr>
                    <td><?php echo esc_html($tarea->tipo_mantenimiento); ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($tarea->fecha_programada)); ?></td>
                    <td><?php echo esc_html($tarea->estado); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
