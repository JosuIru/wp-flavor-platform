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
    <h1>Gestión de Préstamos</h1>
    <hr class="wp-header-end">

    <div class="flavor-filters">
        <form method="get">
            <input type="hidden" name="page" value="flavor-chat-biblioteca">
            <input type="hidden" name="tab" value="prestamos">
            <select name="estado">
                <option value="activo" <?php selected($filtro_estado, 'activo'); ?>>Activos</option>
                <option value="devuelto" <?php selected($filtro_estado, 'devuelto'); ?>>Devueltos</option>
                <option value="retrasado" <?php selected($filtro_estado, 'retrasado'); ?>>Retrasados</option>
                <option value="perdido" <?php selected($filtro_estado, 'perdido'); ?>>Perdidos</option>
            </select>
            <button type="submit" class="button">Filtrar</button>
        </form>
    </div>

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Libro</th>
                    <th>Prestamista</th>
                    <th>Prestatario</th>
                    <th>Fecha Préstamo</th>
                    <th>Devolución Prevista</th>
                    <th>Estado</th>
                    <th>Acciones</th>
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
                        <td><button class="button button-small">Ver</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
