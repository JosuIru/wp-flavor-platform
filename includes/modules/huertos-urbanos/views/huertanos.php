<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$huertanos = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email, COUNT(p.id) as parcelas
    FROM {$wpdb->users} u
    INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
    WHERE p.estado = 'ocupada'
    GROUP BY u.ID
    ORDER BY u.display_name
");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Huertanos', 'flavor-chat-ia'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Parcelas', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($huertanos as $huertano) : ?>
                <tr>
                    <td><?php echo esc_html($huertano->display_name); ?></td>
                    <td><?php echo esc_html($huertano->user_email); ?></td>
                    <td><?php echo $huertano->parcelas; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
