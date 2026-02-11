<?php
/**
 * Tab de Usuarios - Panel de Permisos
 *
 * Gestion de permisos por usuario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Paginacion
$paginacion_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$usuarios_por_pagina = 20;
$busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Obtener usuarios
$argumentos_usuarios = [
    'number' => $usuarios_por_pagina,
    'offset' => ($paginacion_actual - 1) * $usuarios_por_pagina,
    'orderby' => 'display_name',
    'order' => 'ASC',
];

if (!empty($busqueda)) {
    $argumentos_usuarios['search'] = '*' . $busqueda . '*';
    $argumentos_usuarios['search_columns'] = ['user_login', 'user_email', 'display_name'];
}

$consulta_usuarios = new WP_User_Query($argumentos_usuarios);
$usuarios = $consulta_usuarios->get_results();
$total_usuarios = $consulta_usuarios->get_total();
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);
?>

<div class="usuarios-tab">
    <h2><?php esc_html_e('Permisos por Usuario', 'flavor-chat-ia'); ?></h2>

    <!-- Buscador -->
    <form method="get" style="margin-bottom: 20px;">
        <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-permissions', 'flavor-chat-ia'); ?>">
        <input type="hidden" name="tab" value="<?php echo esc_attr__('usuarios', 'flavor-chat-ia'); ?>">
        <div class="inline-form">
            <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>"
                   placeholder="<?php esc_attr_e('Buscar usuario...', 'flavor-chat-ia'); ?>"
                   style="min-width: 250px;">
            <button type="submit" class="button">
                <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
            </button>
            <?php if ($busqueda): ?>
                <a href="?page=flavor-permissions&tab=usuarios" class="button">
                    <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Listado de usuarios -->
    <?php if (empty($usuarios)): ?>
        <div class="flavor-card">
            <p><?php esc_html_e('No se encontraron usuarios.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($usuarios as $usuario): ?>
            <?php
            $roles_modulo_usuario = Flavor_Permission_Helper::get_all_module_roles($usuario->ID);
            $resumen_permisos = Flavor_Permission_Helper::get_permissions_summary($usuario->ID);
            ?>
            <div class="user-permissions-card">
                <div class="user-info">
                    <?php echo get_avatar($usuario->ID, 64); ?>
                    <h4 class="user-name" style="margin: 10px 0 5px;">
                        <?php echo esc_html($usuario->display_name); ?>
                    </h4>
                    <p class="user-email" style="margin: 0; font-size: 12px; color: #666;">
                        <?php echo esc_html($usuario->user_email); ?>
                    </p>
                    <p style="margin: 5px 0 0;">
                        <strong><?php esc_html_e('Rol WP:', 'flavor-chat-ia'); ?></strong>
                        <?php
                        $roles_usuario = $usuario->roles;
                        echo esc_html(implode(', ', $roles_usuario));
                        ?>
                    </p>
                    <p style="margin: 5px 0;">
                        <a href="<?php echo esc_url(get_edit_user_link($usuario->ID)); ?>"
                           class="button button-small">
                            <?php esc_html_e('Editar Usuario', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                </div>

                <div class="user-roles-modules">
                    <h4 style="margin-top: 0;">
                        <?php esc_html_e('Roles por Modulo', 'flavor-chat-ia'); ?>
                    </h4>

                    <div class="user-modules">
                        <?php foreach ($modulos as $modulo_slug => $modulo_info): ?>
                            <div style="padding: 8px; border: 1px solid #eee; border-radius: 4px; background: #fafafa;">
                                <strong style="font-size: 12px; display: block; margin-bottom: 5px;">
                                    <?php echo esc_html($modulo_info['label']); ?>
                                </strong>

                                <?php
                                $rol_actual_modulo = $roles_modulo_usuario[$modulo_slug] ?? null;
                                ?>

                                <?php if ($rol_actual_modulo): ?>
                                    <?php
                                    $rol_info = $modulo_info['roles'][$rol_actual_modulo] ?? null;
                                    $rol_label = $rol_info ? $rol_info['label'] : $rol_actual_modulo;
                                    ?>
                                    <span class="module-role-badge has-role">
                                        <?php echo esc_html($rol_label); ?>
                                    </span>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                                        <input type="hidden" name="accion" value="<?php echo esc_attr__('revocar_rol_usuario', 'flavor-chat-ia'); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($usuario->ID); ?>">
                                        <input type="hidden" name="modulo" value="<?php echo esc_attr($modulo_slug); ?>">
                                        <button type="submit" class="button button-small"
                                                style="padding: 0 5px; min-height: 20px; line-height: 18px; font-size: 11px;"
                                                title="<?php esc_attr_e('Revocar rol', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-no" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline-form" style="gap: 5px;">
                                        <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                                        <input type="hidden" name="accion" value="<?php echo esc_attr__('asignar_rol_usuario', 'flavor-chat-ia'); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($usuario->ID); ?>">
                                        <input type="hidden" name="modulo" value="<?php echo esc_attr($modulo_slug); ?>">

                                        <select name="rol" class="module-role-select" style="min-width: 100px; font-size: 11px;">
                                            <option value=""><?php esc_html_e('-- Asignar --', 'flavor-chat-ia'); ?></option>
                                            <?php if (!empty($modulo_info['roles'])): ?>
                                                <?php foreach ($modulo_info['roles'] as $rol_slug => $rol_info): ?>
                                                    <option value="<?php echo esc_attr($rol_slug); ?>">
                                                        <?php echo esc_html($rol_info['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>

                                        <button type="submit" class="button button-small assign-btn" disabled
                                                style="padding: 0 5px; min-height: 24px;">
                                            <span class="dashicons dashicons-yes"
                                                  style="font-size: 16px; width: 16px; height: 16px; line-height: 24px;"></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Resumen de capabilities -->
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; font-weight: 600;">
                            <?php esc_html_e('Ver resumen de capabilities', 'flavor-chat-ia'); ?>
                        </summary>
                        <div style="padding: 10px; background: #f9f9f9; margin-top: 5px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                            <?php foreach ($resumen_permisos as $grupo => $info): ?>
                                <div style="margin-bottom: 10px;">
                                    <strong>
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $grupo))); ?>:
                                    </strong>
                                    <span style="color: #46b450;"><?php echo esc_html($info['granted']); ?></span>
                                    / <?php echo esc_html($info['total']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Paginacion -->
        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            esc_html(_n('%s usuario', '%s usuarios', $total_usuarios, 'flavor-chat-ia')),
                            number_format_i18n($total_usuarios)
                        );
                        ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        $base_url = add_query_arg([
                            'page' => 'flavor-permissions',
                            'tab' => 'usuarios',
                            's' => $busqueda,
                        ], admin_url('admin.php'));

                        if ($paginacion_actual > 1): ?>
                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $paginacion_actual - 1, $base_url)); ?>">
                                <span class="screen-reader-text"><?php esc_html_e('Pagina anterior', 'flavor-chat-ia'); ?></span>
                                <span aria-hidden="true"><?php esc_html_e('&lsaquo;', 'flavor-chat-ia'); ?></span>
                            </a>
                        <?php endif; ?>

                        <span class="paging-input">
                            <span class="tablenav-paging-text">
                                <?php echo esc_html($paginacion_actual); ?>
                                <?php esc_html_e('de', 'flavor-chat-ia'); ?>
                                <span class="total-pages"><?php echo esc_html($total_paginas); ?></span>
                            </span>
                        </span>

                        <?php if ($paginacion_actual < $total_paginas): ?>
                            <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $paginacion_actual + 1, $base_url)); ?>">
                                <span class="screen-reader-text"><?php esc_html_e('Pagina siguiente', 'flavor-chat-ia'); ?></span>
                                <span aria-hidden="true"><?php esc_html_e('&rsaquo;', 'flavor-chat-ia'); ?></span>
                            </a>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
