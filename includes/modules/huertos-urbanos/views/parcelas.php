<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$parcelas = $wpdb->get_results("SELECT p.*, h.nombre as huerto_nombre FROM $tabla_parcelas p LEFT JOIN {$wpdb->prefix}flavor_huertos h ON p.huerto_id = h.id ORDER BY h.nombre, p.numero_parcela");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Gestión de Parcelas', 'flavor-chat-ia'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Huerto', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Parcela', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Tamaño', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Responsable', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parcelas as $parcela) : ?>
                <tr>
                    <td><?php echo esc_html($parcela->huerto_nombre); ?></td>
                    <td>#<?php echo $parcela->numero_parcela; ?></td>
                    <td><?php echo $parcela->tamano_m2; ?> m²</td>
                    <td><?php echo esc_html($parcela->estado); ?></td>
                    <td><?php echo $parcela->usuario_id ? esc_html(get_userdata($parcela->usuario_id)->display_name) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
