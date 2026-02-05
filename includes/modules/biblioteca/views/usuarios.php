<?php
/**
 * Vista Usuarios Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

$usuarios = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email,
           COUNT(DISTINCT l.id) as libros_compartidos,
           COUNT(DISTINCT p1.id) as libros_prestados,
           COUNT(DISTINCT p2.id) as libros_tomados
    FROM {$wpdb->users} u
    LEFT JOIN $tabla_libros l ON u.ID = l.propietario_id
    LEFT JOIN $tabla_prestamos p1 ON u.ID = p1.prestamista_id
    LEFT JOIN $tabla_prestamos p2 ON u.ID = p2.prestatario_id
    WHERE l.id IS NOT NULL OR p1.id IS NOT NULL OR p2.id IS NOT NULL
    GROUP BY u.ID
    ORDER BY libros_compartidos DESC
");

?>

<div class="wrap">
    <h1>Usuarios de la Biblioteca</h1>
    <hr class="wp-header-end">

    <div class="flavor-card">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Libros Compartidos</th>
                    <th>Libros Prestados</th>
                    <th>Libros Tomados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                        <td><?php echo esc_html($u->user_email); ?></td>
                        <td class="flavor-text-center"><?php echo number_format($u->libros_compartidos); ?></td>
                        <td class="flavor-text-center"><?php echo number_format($u->libros_prestados); ?></td>
                        <td class="flavor-text-center"><?php echo number_format($u->libros_tomados); ?></td>
                        <td><button class="button button-small">Ver Perfil</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
