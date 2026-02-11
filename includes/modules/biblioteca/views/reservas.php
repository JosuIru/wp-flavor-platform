<?php
/**
 * Vista Reservas Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

$reservas = $wpdb->get_results(
    "SELECT r.*, l.titulo as libro_titulo, u.display_name as usuario_nombre
     FROM $tabla_reservas r
     INNER JOIN $tabla_libros l ON r.libro_id = l.id
     INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
     WHERE r.estado IN ('pendiente', 'confirmada')
     ORDER BY r.fecha_solicitud DESC
     LIMIT 50"
);

?>

<div class="wrap">
    <h1><?php echo esc_html__('Gestión de Reservas', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Libro', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Fecha Solicitud', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Expira', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservas)): ?>
                    <?php foreach ($reservas as $r): ?>
                        <tr>
                            <td><?php echo $r->id; ?></td>
                            <td><strong><?php echo esc_html($r->libro_titulo); ?></strong></td>
                            <td><?php echo esc_html($r->usuario_nombre); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($r->fecha_solicitud)); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($r->fecha_expiracion)); ?></td>
                            <td>
                                <span class="flavor-badge flavor-badge-<?php
                                    echo $r->estado === 'confirmada' ? 'success' : 'warning';
                                ?>">
                                    <?php echo ucfirst($r->estado); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small"><?php echo esc_html__('Confirmar', 'flavor-chat-ia'); ?></button>
                                <button class="button button-small"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="flavor-no-data"><?php echo esc_html__('No hay reservas pendientes', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
