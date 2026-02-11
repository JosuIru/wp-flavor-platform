<?php
/**
 * Tab de Roles - Panel de Permisos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="roles-tab">
    <div class="row">
        <div class="col-left" style="float: left; width: 65%; padding-right: 20px;">
            <h2><?php esc_html_e('Roles del Sistema', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-card">
                <h3><?php esc_html_e('Roles Predefinidos', 'flavor-chat-ia'); ?></h3>
                <p class="description">
                    <?php esc_html_e('Estos roles vienen incluidos con Flavor Platform y no pueden ser eliminados.', 'flavor-chat-ia'); ?>
                </p>

                <?php foreach ($roles_definidos as $rol_slug => $rol_config): ?>
                    <div class="role-card">
                        <div class="role-info">
                            <h4>
                                <?php echo esc_html($rol_config['label']); ?>
                                <code style="font-size: 11px; color: #666;"><?php echo esc_html($rol_slug); ?></code>
                            </h4>
                            <div class="role-meta">
                                <?php
                                $num_caps = is_array($rol_config['capabilities'])
                                    ? count($rol_config['capabilities'])
                                    : __('Todas', 'flavor-chat-ia');
                                printf(
                                    esc_html__('Capabilities: %s', 'flavor-chat-ia'),
                                    '<strong>' . esc_html($num_caps) . '</strong>'
                                );
                                ?>
                            </div>
                        </div>
                        <div class="role-actions">
                            <a href="?page=flavor-permissions&tab=capabilities&role=<?php echo esc_attr($rol_slug); ?>" class="button button-small">
                                <?php esc_html_e('Ver Permisos', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($roles_personalizados)): ?>
                <div class="flavor-card">
                    <h3><?php esc_html_e('Roles Personalizados', 'flavor-chat-ia'); ?></h3>

                    <?php foreach ($roles_personalizados as $rol_slug => $rol_config): ?>
                        <div class="role-card">
                            <div class="role-info">
                                <h4>
                                    <?php echo esc_html($rol_config['label']); ?>
                                    <code style="font-size: 11px; color: #666;"><?php echo esc_html($rol_slug); ?></code>
                                    <?php if (!empty($rol_config['modulo'])): ?>
                                        <span class="module-role-badge">
                                            <?php echo esc_html($rol_config['modulo']); ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <div class="role-meta">
                                    <?php if (!empty($rol_config['description'])): ?>
                                        <p style="margin: 5px 0;"><?php echo esc_html($rol_config['description']); ?></p>
                                    <?php endif; ?>
                                    <?php
                                    $caps = $rol_config['capabilities'];
                                    $num_caps = is_array($caps) ? count($caps) : $caps;
                                    printf(
                                        esc_html__('Capabilities: %s | Creado: %s', 'flavor-chat-ia'),
                                        '<strong>' . esc_html($num_caps) . '</strong>',
                                        esc_html($rol_config['created'] ?? '-')
                                    );
                                    ?>
                                </div>
                            </div>
                            <div class="role-actions">
                                <a href="?page=flavor-permissions&tab=capabilities&role=<?php echo esc_attr($rol_slug); ?>" class="button button-small">
                                    <?php esc_html_e('Editar Permisos', 'flavor-chat-ia'); ?>
                                </a>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                                    <input type="hidden" name="accion" value="<?php echo esc_attr__('eliminar_rol', 'flavor-chat-ia'); ?>">
                                    <input type="hidden" name="rol_slug" value="<?php echo esc_attr($rol_slug); ?>">
                                    <button type="submit" class="button button-small delete-role-btn"
                                            style="color: #a00;">
                                        <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-right" style="float: right; width: 32%;">
            <div class="flavor-card">
                <h3><?php esc_html_e('Crear Nuevo Rol', 'flavor-chat-ia'); ?></h3>

                <form method="post" class="create-role-form">
                    <?php wp_nonce_field('flavor_manage_permissions', 'flavor_permissions_nonce'); ?>
                    <input type="hidden" name="accion" value="<?php echo esc_attr__('crear_rol', 'flavor-chat-ia'); ?>">

                    <div class="form-field">
                        <label for="rol_slug"><?php esc_html_e('Identificador (slug)', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="rol_slug" name="rol_slug" required
                               pattern="[a-z0-9_]+" title="<?php esc_attr_e('Solo letras minusculas, numeros y guiones bajos', 'flavor-chat-ia'); ?>">
                        <p class="description">
                            <?php esc_html_e('Ejemplo: gestor_local', 'flavor-chat-ia'); ?>
                        </p>
                    </div>

                    <div class="form-field">
                        <label for="rol_label"><?php esc_html_e('Nombre visible', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="rol_label" name="rol_label" required>
                    </div>

                    <div class="form-field">
                        <label for="rol_description"><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></label>
                        <textarea id="rol_description" name="rol_description" rows="2"></textarea>
                    </div>

                    <div class="form-field">
                        <label for="rol_modulo"><?php esc_html_e('Modulo (opcional)', 'flavor-chat-ia'); ?></label>
                        <select id="rol_modulo" name="rol_modulo">
                            <option value=""><?php esc_html_e('-- Rol global --', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($modulos as $slug => $modulo): ?>
                                <option value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($modulo['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Dejar vacio para un rol global de WordPress.', 'flavor-chat-ia'); ?>
                        </p>
                    </div>

                    <div class="form-field">
                        <label><?php esc_html_e('Permisos', 'flavor-chat-ia'); ?></label>
                        <div class="capabilities-checklist">
                            <?php foreach ($capabilities_agrupadas as $grupo => $caps): ?>
                                <div class="cap-group">
                                    <div class="cap-group-title">
                                        <label>
                                            <input type="checkbox" class="toggle-capabilities"
                                                   data-grupo="<?php echo esc_attr($grupo); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $grupo))); ?>
                                        </label>
                                    </div>
                                    <?php foreach ($caps as $cap => $descripcion): ?>
                                        <label>
                                            <input type="checkbox" name="capabilities[]"
                                                   value="<?php echo esc_attr($cap); ?>"
                                                   data-grupo="<?php echo esc_attr($grupo); ?>">
                                            <?php echo esc_html($descripcion); ?>
                                            <code style="font-size: 10px; color: #999;"><?php echo esc_html($cap); ?></code>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Crear Rol', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div style="clear: both;"></div>
    </div>

    <div class="flavor-card" style="margin-top: 20px;">
        <h3><?php esc_html_e('Roles por Modulo', 'flavor-chat-ia'); ?></h3>
        <p class="description">
            <?php esc_html_e('Cada modulo tiene roles especificos que se pueden asignar a usuarios independientemente de su rol de WordPress.', 'flavor-chat-ia'); ?>
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">
            <?php foreach ($modulos as $modulo_slug => $modulo_info): ?>
                <div class="module-section" style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; padding: 8px 12px;">
                        <?php echo esc_html($modulo_info['label']); ?>
                    </h3>
                    <div class="module-capabilities" style="padding: 10px;">
                        <?php if (!empty($modulo_info['roles'])): ?>
                            <?php foreach ($modulo_info['roles'] as $rol_slug => $rol_info): ?>
                                <div style="padding: 5px 0; border-bottom: 1px solid #eee;">
                                    <strong><?php echo esc_html($rol_info['label']); ?></strong>
                                    <code style="font-size: 10px;"><?php echo esc_html($rol_slug); ?></code>
                                    <?php if (!empty($rol_info['description'])): ?>
                                        <p style="margin: 3px 0 0; font-size: 12px; color: #666;">
                                            <?php echo esc_html($rol_info['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic;">
                                <?php esc_html_e('Sin roles especificos', 'flavor-chat-ia'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
