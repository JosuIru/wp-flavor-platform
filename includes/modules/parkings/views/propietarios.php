<?php
/**
 * Vista de Gestión de Propietarios - Parkings
 *
 * @package FlavorChatIA
 * @subpackage Parkings
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;
$tabla_propietarios = $wpdb->prefix . 'flavor_parkings_propietarios';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';

$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

$propietarios = $wpdb->get_results(
    "SELECT
        p.*,
        u.display_name,
        u.user_email,
        COUNT(pl.id) as total_plazas,
        SUM(CASE WHEN pl.estado = 'ocupada' THEN 1 ELSE 0 END) as plazas_ocupadas
    FROM {$tabla_propietarios} p
    INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
    LEFT JOIN {$tabla_plazas} pl ON p.id = pl.propietario_id
    GROUP BY p.id
    ORDER BY total_plazas DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_propietarios = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_propietarios}");
$total_paginas = ceil($total_propietarios / $elementos_por_pagina);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Propietarios', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Propietario', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Email', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Total Plazas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($propietarios)) : ?>
                    <?php foreach ($propietarios as $propietario) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($propietario->id); ?></strong></td>
                            <td><strong><?php echo esc_html($propietario->display_name); ?></strong></td>
                            <td><?php echo esc_html($propietario->user_email); ?></td>
                            <td><?php echo esc_html($propietario->telefono ?? '-'); ?></td>
                            <td><?php echo esc_html($propietario->total_plazas); ?></td>
                            <td>
                                <span style="color: <?php echo $propietario->plazas_ocupadas > 0 ? '#00a32a' : '#666'; ?>;">
                                    <?php echo esc_html($propietario->plazas_ocupadas); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $propietario->estado === 'activo' ? '#00a32a' : '#666'; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($propietario->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-propietarios&action=ver&propietario_id=' . $propietario->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&propietario_id=' . $propietario->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Plazas', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p><?php esc_html_e('No hay propietarios registrados.', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'total' => $total_paginas, 'current' => $pagina_actual]); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
