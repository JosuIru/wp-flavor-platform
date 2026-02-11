<?php
/**
 * Vista Gestión de Préstamos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'activo';

$prestamos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*,
            l.titulo as libro_titulo,
            u1.display_name as prestamista,
            u2.display_name as prestatario
     FROM $tabla_prestamos p
     INNER JOIN $tabla_libros l ON p.libro_id = l.id
     INNER JOIN {$wpdb->users} u1 ON p.prestamista_id = u1.ID
     INNER JOIN {$wpdb->users} u2 ON p.prestatario_id = u2.ID
     WHERE p.estado = %s
     ORDER BY p.fecha_prestamo DESC
     LIMIT 50",
    $filtro_estado
));

?>

<div class="wrap">
    <h1><?php echo esc_html__('Gestión de Préstamos', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <div class="flavor-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-biblioteca', 'flavor-chat-ia'); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('prestamos', 'flavor-chat-ia'); ?>">
            <select name="estado">
                <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'activo'); ?>><?php echo esc_html__('Activos', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('devuelto', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'devuelto'); ?>><?php echo esc_html__('Devueltos', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('retrasado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'retrasado'); ?>><?php echo esc_html__('Retrasados', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('perdido', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'perdido'); ?>><?php echo esc_html__('Perdidos', 'flavor-chat-ia'); ?></option>
            </select>
            <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>
        </form>
    </div>

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Libro', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Prestamista', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Prestatario', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Fecha Préstamo', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Devolución Prevista', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td><?php echo $p->id; ?></td>
                        <td><strong><?php echo esc_html($p->libro_titulo); ?></strong></td>
                        <td><?php echo esc_html($p->prestamista); ?></td>
                        <td><?php echo esc_html($p->prestatario); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($p->fecha_prestamo)); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($p->fecha_devolucion_prevista)); ?></td>
                        <td><span class="flavor-badge"><?php echo ucfirst($p->estado); ?></span></td>
                        <td><button class="button button-small"><?php echo esc_html__('Ver', 'flavor-chat-ia'); ?></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
