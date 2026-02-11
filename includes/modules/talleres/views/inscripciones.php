<?php
/**
 * Vista Gestión de Inscripciones
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';

$inscripciones = $wpdb->get_results(
    "SELECT i.*,
            t.titulo as taller_titulo,
            u.display_name as participante_nombre,
            u.user_email as participante_email
     FROM $tabla_inscripciones i
     INNER JOIN $tabla_talleres t ON i.taller_id = t.id
     INNER JOIN {$wpdb->users} u ON i.participante_id = u.ID
     ORDER BY i.fecha_inscripcion DESC
     LIMIT 100"
);

?>

<div class="wrap">
    <h1><?php echo esc_html__('Gestión de Inscripciones', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Participante', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Fecha Inscripción', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado Pago', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inscripciones)): ?>
                    <?php foreach ($inscripciones as $i): ?>
                        <tr>
                            <td><?php echo $i->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($i->taller_titulo); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($i->participante_nombre); ?>
                                <br><small class="flavor-text-muted"><?php echo esc_html($i->participante_email); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($i->fecha_inscripcion)); ?></td>
                            <td><?php echo $i->precio_pagado > 0 ? number_format($i->precio_pagado, 2) . '€' : 'Gratis'; ?></td>
                            <td>
                                <span class="flavor-badge flavor-badge-<?php
                                    echo $i->estado_pago === 'pagado' ? 'success' : 'warning';
                                ?>">
                                    <?php echo ucfirst($i->estado_pago); ?>
                                </span>
                            </td>
                            <td>
                                <span class="flavor-badge flavor-badge-<?php
                                    echo $i->estado === 'confirmada' ? 'success' : 'info';
                                ?>">
                                    <?php echo ucfirst($i->estado); ?>
                                </span>
                            </td>
                            <td><button class="button button-small"><?php echo esc_html__('Ver', 'flavor-chat-ia'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="flavor-no-data"><?php echo esc_html__('No hay inscripciones', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
