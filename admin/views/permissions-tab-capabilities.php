<?php
/**
 * Tab de Capabilities - Panel de Permisos
 *
 * Matriz de roles vs capabilities estilo WordPress
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener rol seleccionado para edicion
$rol_editar = isset($_GET['role']) ? sanitize_key($_GET['role']) : null;

// Obtener todos los roles de WordPress que nos interesan
$roles_wp = [];
foreach (wp_roles()->roles as $slug => $info) {
    // Solo incluir roles de Flavor y administrator
    if (strpos($slug, 'flavor_') === 0 || $slug === 'administrator') {
        $roles_wp[$slug] = $info['name'];
    }
}

// Agregar roles personalizados
foreach ($roles_personalizados as $slug => $info) {
    if (empty($info['modulo'])) {
        $roles_wp[$slug] = $info['label'];
    }
}
?>

<div class="capabilities-tab">
    <?php if ($rol_editar): ?>
        <!-- Modo edicion de un rol especifico -->
        <div class="flavor-card">
            <h3>
                <?php
                printf(
                    esc_html__('Editar Capabilities de: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    '<strong>' . esc_html($roles_wp[$rol_editar] ?? $rol_editar) . '</strong>'
                );
                ?>
                <a href="?page=flavor-permissions&tab=capabilities" class="button button-small" style="float: right;">
                    <?php esc_html_e('Volver a Matriz', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </h3>

            <form method="post">
                <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                <input type="hidden" name="accion" value="<?php echo esc_attr__('actualizar_capabilities', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <input type="hidden" name="rol_slug" value="<?php echo esc_attr($rol_editar); ?>">

                <?php
                $rol_actual = get_role($rol_editar);
                $caps_rol = $rol_actual ? $rol_actual->capabilities : [];

                // Verificar si es rol personalizado
                if (isset($roles_personalizados[$rol_editar])) {
                    $caps_rol = [];
                    $caps_config = $roles_personalizados[$rol_editar]['capabilities'];
                    foreach ($role_manager->expandir_capabilities($caps_config) as $cap) {
                        $caps_rol[$cap] = true;
                    }
                }
                ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    <?php foreach ($capabilities_agrupadas as $grupo => $caps): ?>
                        <div class="cap-group-box" style="border: 1px solid #ddd; border-radius: 4px;">
                            <div style="background: #f5f5f5; padding: 10px; border-bottom: 1px solid #ddd;">
                                <label style="font-weight: 600;">
                                    <input type="checkbox" class="toggle-capabilities"
                                           data-grupo="<?php echo esc_attr($grupo); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $grupo))); ?>
                                    <span style="color: #666; font-weight: normal;">
                                        (<?php echo count($caps); ?>)
                                    </span>
                                </label>
                            </div>
                            <div style="padding: 10px; max-height: 250px; overflow-y: auto;">
                                <?php foreach ($caps as $cap => $descripcion): ?>
                                    <label style="display: block; padding: 4px 0;">
                                        <input type="checkbox" name="capabilities[]"
                                               value="<?php echo esc_attr($cap); ?>"
                                               data-grupo="<?php echo esc_attr($grupo); ?>"
                                               <?php checked(!empty($caps_rol[$cap])); ?>>
                                        <?php echo esc_html($descripcion); ?>
                                        <br>
                                        <code style="font-size: 10px; color: #999; margin-left: 22px;">
                                            <?php echo esc_html($cap); ?>
                                        </code>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <button type="submit" class="button button-primary button-large">
                        <?php esc_html_e('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="?page=flavor-permissions&tab=capabilities" class="button button-large">
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- Matriz de roles vs capabilities -->
        <h2><?php esc_html_e('Matriz de Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description">
            <?php esc_html_e('Vista general de capabilities asignadas a cada rol. Haz clic en "Editar" para modificar los permisos de un rol.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>

        <?php foreach ($capabilities_agrupadas as $grupo => $caps): ?>
            <div class="flavor-card" style="margin-bottom: 20px;">
                <h3 style="margin: -15px -15px 15px; padding: 12px 15px; background: #f5f5f5; border-bottom: 1px solid #ddd;">
                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $grupo))); ?>
                    <span style="font-weight: normal; color: #666;">
                        (<?php echo count($caps); ?> <?php esc_html_e('capabilities', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>)
                    </span>
                </h3>

                <div class="capabilities-matrix">
                    <table>
                        <thead>
                            <tr>
                                <th class="capability-name"><?php esc_html_e('Capability', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <?php foreach ($roles_wp as $rol_slug => $rol_nombre): ?>
                                    <th style="min-width: 80px;">
                                        <?php echo esc_html($rol_nombre); ?>
                                        <br>
                                        <a href="?page=flavor-permissions&tab=capabilities&role=<?php echo esc_attr($rol_slug); ?>" style="font-size: 11px; font-weight: normal;">
                                            <?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($caps as $cap => $descripcion): ?>
                                <tr>
                                    <td class="capability-name">
                                        <strong><?php echo esc_html($descripcion); ?></strong>
                                        <br>
                                        <code style="font-size: 11px; color: #666;">
                                            <?php echo esc_html($cap); ?>
                                        </code>
                                    </td>
                                    <?php foreach ($roles_wp as $rol_slug => $rol_nombre): ?>
                                        <?php
                                        $rol_obj = get_role($rol_slug);
                                        $tiene_cap = $rol_obj && $rol_obj->has_cap($cap);
                                        ?>
                                        <td>
                                            <?php if ($tiene_cap): ?>
                                                <span class="dashicons dashicons-yes cap-granted"
                                                      title="<?php esc_attr_e('Concedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-minus cap-denied"
                                                      title="<?php esc_attr_e('No concedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<style>
.capabilities-matrix .dashicons-yes {
    color: #46b450;
}
.capabilities-matrix .dashicons-minus {
    color: #ccc;
}
.cap-group-box {
    background: #fff;
}
</style>
