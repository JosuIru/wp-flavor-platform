<?php
/**
 * Vista Vendedores - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Obtener usuarios con anuncios en marketplace
$vendedores = $wpdb->get_results(
    "SELECT u.ID, u.display_name, u.user_email, COUNT(p.ID) as total_anuncios
     FROM {$wpdb->users} u
     INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
     WHERE p.post_type = 'marketplace_item' AND p.post_status = 'publish'
     GROUP BY u.ID
     ORDER BY total_anuncios DESC"
);

?>

<div class="wrap">
    <h1><span class="dashicons dashicons-groups"></span> Vendedores del Marketplace</h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Vendedor</th>
                <th>Email</th>
                <th style="text-align: center;">Total Anuncios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $posicion = 1;
            foreach ($vendedores as $vendedor):
            ?>
            <tr>
                <td><?php echo $posicion++; ?></td>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $vendedor->ID); ?>">
                            <?php echo esc_html($vendedor->display_name); ?>
                        </a>
                    </strong>
                </td>
                <td><?php echo esc_html($vendedor->user_email); ?></td>
                <td style="text-align: center;">
                    <strong style="color: #2271b1;"><?php echo $vendedor->total_anuncios; ?></strong>
                </td>
                <td>
                    <a href="<?php echo admin_url('edit.php?post_type=marketplace_item&author=' . $vendedor->ID); ?>"
                       class="button button-small">
                        Ver Anuncios
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($vendedores)): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px;">No hay vendedores registrados</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
