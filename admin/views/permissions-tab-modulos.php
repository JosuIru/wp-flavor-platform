<?php
/**
 * Tab de Modulos - Panel de Permisos
 *
 * Vista de permisos organizada por modulo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$modulo_seleccionado = isset($_GET['modulo']) ? sanitize_key($_GET['modulo']) : null;
?>

<div class="modulos-tab">
    <h2><?php esc_html_e('Permisos por Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
    <p class="description">
        <?php esc_html_e('Selecciona un modulo para ver sus capabilities y roles especificos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </p>

    <!-- Selector de modulo -->
    <div class="flavor-card" style="margin-bottom: 20px;">
        <div class="inline-form">
            <label for="selector-modulo" style="font-weight: 600;">
                <?php esc_html_e('Modulo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
            <select id="selector-modulo" onchange="location.href='?page=flavor-permissions&tab=modulos&modulo=' + this.value;">
                <option value=""><?php esc_html_e('-- Seleccionar modulo --', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($modulos as $slug => $modulo_info): ?>
                    <option value="<?php echo esc_attr($slug); ?>"
                            <?php selected($modulo_seleccionado, $slug); ?>>
                        <?php echo esc_html($modulo_info['label']); ?>
                        (<?php echo count($modulo_info['capabilities']); ?> <?php esc_html_e('caps)
', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($modulo_seleccionado && isset($modulos[$modulo_seleccionado])): ?>
        <?php
        $modulo_actual = $modulos[$modulo_seleccionado];
        $caps_modulo = $modulo_actual['capabilities'];
        $roles_modulo = $modulo_actual['roles'];
        ?>

        <div class="module-detail">
            <div class="module-section">
                <h3><?php echo esc_html($modulo_actual['label']); ?></h3>

                <div class="module-capabilities">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <!-- Capabilities del modulo -->
                        <div>
                            <h4 style="margin-top: 0;">
                                <?php esc_html_e('Capabilities Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Capability', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($caps_modulo as $cap => $descripcion): ?>
                                        <tr>
                                            <td><code><?php echo esc_html($cap); ?></code></td>
                                            <td><?php echo esc_html($descripcion); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Roles del modulo -->
                        <div>
                            <h4 style="margin-top: 0;">
                                <?php esc_html_e('Roles del Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>

                            <?php if (!empty($roles_modulo)): ?>
                                <?php foreach ($roles_modulo as $rol_slug => $rol_info): ?>
                                    <div class="flavor-card" style="margin-bottom: 10px;">
                                        <h4 style="margin: 0 0 5px;">
                                            <?php echo esc_html($rol_info['label']); ?>
                                            <code style="font-size: 11px; color: #666;">
                                                <?php echo esc_html($rol_slug); ?>
                                            </code>
                                        </h4>
                                        <?php if (!empty($rol_info['description'])): ?>
                                            <p style="margin: 0 0 10px; color: #666; font-size: 13px;">
                                                <?php echo esc_html($rol_info['description']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <div style="background: #f9f9f9; padding: 10px; border-radius: 4px;">
                                            <strong style="font-size: 12px;">
                                                <?php esc_html_e('Capabilities incluidas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </strong>
                                            <div style="margin-top: 5px;">
                                                <?php
                                                $caps_rol = $role_manager->obtener_capabilities_rol_modulo($modulo_seleccionado, $rol_slug);
                                                foreach ($caps_rol as $cap):
                                                ?>
                                                    <span style="display: inline-block; background: #e7e7e7; padding: 2px 6px; margin: 2px; border-radius: 3px; font-size: 11px;">
                                                        <?php echo esc_html($cap); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #666;">
                                    <?php esc_html_e('Este modulo no tiene roles especificos definidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Usuarios con roles en este modulo -->
                    <div style="margin-top: 30px;">
                        <h4><?php esc_html_e('Usuarios con Roles en este Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>

                        <?php
                        $usuarios_con_roles = [];
                        if (!empty($roles_modulo)) {
                            foreach (array_keys($roles_modulo) as $rol_slug) {
                                $ids_usuarios = $role_manager->obtener_usuarios_por_rol_modulo($modulo_seleccionado, $rol_slug);
                                foreach ($ids_usuarios as $user_id) {
                                    if (!isset($usuarios_con_roles[$user_id])) {
                                        $usuarios_con_roles[$user_id] = [];
                                    }
                                    $usuarios_con_roles[$user_id][] = $rol_slug;
                                }
                            }
                        }
                        ?>

                        <?php if (!empty($usuarios_con_roles)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;"></th>
                                        <th><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Rol en Modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios_con_roles as $user_id => $roles_asignados): ?>
                                        <?php $usuario = get_userdata($user_id); ?>
                                        <?php if ($usuario): ?>
                                            <tr>
                                                <td><?php echo get_avatar($user_id, 32); ?></td>
                                                <td>
                                                    <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                                </td>
                                                <td><?php echo esc_html($usuario->user_email); ?></td>
                                                <td>
                                                    <?php foreach ($roles_asignados as $rol_asignado): ?>
                                                        <?php
                                                        $rol_info = $roles_modulo[$rol_asignado] ?? null;
                                                        $label = $rol_info ? $rol_info['label'] : $rol_asignado;
                                                        ?>
                                                        <span class="module-role-badge has-role">
                                                            <?php echo esc_html($label); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td>
                                                    <form method="post" style="display: inline;">
                                                        <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                                                        <input type="hidden" name="accion" value="revocar_rol_usuario">
                                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                                                        <input type="hidden" name="modulo" value="<?php echo esc_attr($modulo_seleccionado); ?>">
                                                        <button type="submit" class="button button-small">
                                                            <?php esc_html_e('Revocar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #666;">
                                <?php esc_html_e('No hay usuarios con roles asignados en este modulo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Formulario para asignar rol -->
                        <div class="flavor-card" style="margin-top: 15px;">
                            <h4 style="margin-top: 0;">
                                <?php esc_html_e('Asignar Rol a Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <form method="post" class="inline-form">
                                <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                                <input type="hidden" name="accion" value="asignar_rol_usuario">
                                <input type="hidden" name="modulo" value="<?php echo esc_attr($modulo_seleccionado); ?>">

                                <label>
                                    <?php esc_html_e('Usuario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <select name="user_id" required style="min-width: 200px;">
                                        <option value=""><?php esc_html_e('-- Seleccionar --', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <?php
                                        $todos_usuarios = get_users(['orderby' => 'display_name', 'number' => 100]);
                                        foreach ($todos_usuarios as $usr):
                                        ?>
                                            <option value="<?php echo esc_attr($usr->ID); ?>">
                                                <?php echo esc_html($usr->display_name); ?>
                                                (<?php echo esc_html($usr->user_email); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label>
                                    <?php esc_html_e('Rol:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <select name="rol" required>
                                        <option value=""><?php esc_html_e('-- Seleccionar --', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <?php if (!empty($roles_modulo)): ?>
                                            <?php foreach ($roles_modulo as $rol_slug => $rol_info): ?>
                                                <option value="<?php echo esc_attr($rol_slug); ?>">
                                                    <?php echo esc_html($rol_info['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </label>

                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e('Asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Vista general de todos los modulos -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
            <?php foreach ($modulos as $slug => $modulo_info): ?>
                <div class="module-section">
                    <h3>
                        <?php echo esc_html($modulo_info['label']); ?>
                        <a href="?page=flavor-permissions&tab=modulos&modulo=<?php echo esc_attr($slug); ?>" class="button button-small" style="float: right; margin-top: -2px;">
                            <?php esc_html_e('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </h3>
                    <div class="module-capabilities">
                        <div style="display: flex; gap: 20px;">
                            <div>
                                <strong><?php esc_html_e('Capabilities:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span style="background: #e7e7e7; padding: 2px 8px; border-radius: 10px;">
                                    <?php echo count($modulo_info['capabilities']); ?>
                                </span>
                            </div>
                            <div>
                                <strong><?php esc_html_e('Roles:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span style="background: #e7e7e7; padding: 2px 8px; border-radius: 10px;">
                                    <?php echo count($modulo_info['roles']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($modulo_info['roles'])): ?>
                            <div style="margin-top: 10px;">
                                <?php foreach ($modulo_info['roles'] as $rol_slug => $rol_info): ?>
                                    <span class="module-role-badge">
                                        <?php echo esc_html($rol_info['label']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
