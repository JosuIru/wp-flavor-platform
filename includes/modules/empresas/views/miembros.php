<?php
/**
 * Gestión de Miembros de Empresa - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$roles_labels = [
    'admin' => __('Administrador', 'flavor-platform'),
    'contable' => __('Contable', 'flavor-platform'),
    'empleado' => __('Empleado', 'flavor-platform'),
    'colaborador' => __('Colaborador', 'flavor-platform'),
    'observador' => __('Observador', 'flavor-platform'),
];
?>
<div class="wrap flavor-modulo-page">
    <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $empresa->id)); ?>" class="button" style="margin-bottom:16px;">
        ← <?php esc_html_e('Volver a la empresa', 'flavor-platform'); ?>
    </a>

    <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1 style="margin:0;font-size:20px;">
                    <?php printf(esc_html__('Miembros de %s', 'flavor-platform'), esc_html($empresa->nombre)); ?>
                </h1>
                <p style="margin:8px 0 0;color:#666;"><?php printf(esc_html__('%d miembros en total', 'flavor-platform'), count($miembros)); ?></p>
            </div>
            <button type="button" class="button button-primary" onclick="document.getElementById('modal-agregar').style.display='flex';">
                <span class="dashicons dashicons-plus-alt" style="vertical-align:middle;"></span>
                <?php esc_html_e('Agregar miembro', 'flavor-platform'); ?>
            </button>
        </div>
    </div>

    <!-- Tabla de miembros -->
    <table class="widefat striped" style="background:#fff;border-radius:8px;overflow:hidden;">
        <thead>
            <tr>
                <th style="padding:12px;"><?php esc_html_e('Miembro', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Rol', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Cargo', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Contacto corporativo', 'flavor-platform'); ?></th>
                <th style="padding:12px;text-align:center;"><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Fecha alta', 'flavor-platform'); ?></th>
                <th style="padding:12px;text-align:center;"><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($miembros)): ?>
                <?php foreach ($miembros as $m): ?>
                <tr>
                    <td style="padding:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php echo get_avatar($m->user_id, 40, '', '', ['extra_attr' => 'style="border-radius:50%;"']); ?>
                            <div>
                                <strong><?php echo esc_html($m->display_name); ?></strong>
                                <br><small style="color:#666;"><?php echo esc_html($m->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px;">
                        <?php
                        $roles_colores = [
                            'admin' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                            'contable' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                            'empleado' => ['bg' => '#dcfce7', 'color' => '#166534'],
                            'colaborador' => ['bg' => '#f3e8ff', 'color' => '#6b21a8'],
                            'observador' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                        ];
                        $rol_style = $roles_colores[$m->rol] ?? $roles_colores['empleado'];
                        ?>
                        <span style="display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:500;background:<?php echo esc_attr($rol_style['bg']); ?>;color:<?php echo esc_attr($rol_style['color']); ?>;">
                            <?php echo esc_html($roles_labels[$m->rol] ?? ucfirst($m->rol)); ?>
                        </span>
                    </td>
                    <td style="padding:12px;"><?php echo esc_html($m->cargo ?: '-'); ?></td>
                    <td style="padding:12px;">
                        <?php if ($m->email_corporativo): ?>
                        <small><?php echo esc_html($m->email_corporativo); ?></small><br>
                        <?php endif; ?>
                        <?php if ($m->telefono_corporativo): ?>
                        <small style="color:#666;"><?php echo esc_html($m->telefono_corporativo); ?></small>
                        <?php endif; ?>
                        <?php if (!$m->email_corporativo && !$m->telefono_corporativo): ?>-<?php endif; ?>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        <?php
                        $estados_colores = [
                            'activo' => ['bg' => '#dcfce7', 'color' => '#166534'],
                            'pendiente' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                            'suspendido' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                            'baja' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                        ];
                        $estado_style = $estados_colores[$m->estado] ?? $estados_colores['baja'];
                        ?>
                        <span style="display:inline-block;padding:4px 8px;border-radius:10px;font-size:11px;background:<?php echo esc_attr($estado_style['bg']); ?>;color:<?php echo esc_attr($estado_style['color']); ?>;">
                            <?php echo esc_html(ucfirst($m->estado)); ?>
                        </span>
                    </td>
                    <td style="padding:12px;"><?php echo $m->fecha_alta ? esc_html(date_i18n('d/m/Y', strtotime($m->fecha_alta))) : '-'; ?></td>
                    <td style="padding:12px;text-align:center;">
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('gestionar_miembro'); ?>
                            <input type="hidden" name="miembro_id" value="<?php echo esc_attr($m->id); ?>" />

                            <?php if ($m->estado === 'pendiente'): ?>
                            <button type="submit" name="accion_miembro" value="activar" class="button button-small" title="<?php esc_attr_e('Activar', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-yes" style="color:#10b981;"></span>
                            </button>
                            <?php endif; ?>

                            <?php if ($m->estado === 'activo'): ?>
                            <button type="submit" name="accion_miembro" value="suspender" class="button button-small" title="<?php esc_attr_e('Suspender', 'flavor-platform'); ?>" onclick="return confirm('<?php esc_attr_e('¿Suspender este miembro?', 'flavor-platform'); ?>');">
                                <span class="dashicons dashicons-controls-pause" style="color:#f59e0b;"></span>
                            </button>
                            <?php endif; ?>

                            <?php if ($m->estado === 'suspendido'): ?>
                            <button type="submit" name="accion_miembro" value="activar" class="button button-small" title="<?php esc_attr_e('Reactivar', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-controls-play" style="color:#10b981;"></span>
                            </button>
                            <?php endif; ?>

                            <?php if ($m->estado !== 'baja'): ?>
                            <select name="nuevo_rol" style="font-size:11px;padding:2px;">
                                <?php foreach ($roles_disponibles as $r): ?>
                                <option value="<?php echo esc_attr($r); ?>" <?php selected($m->rol, $r); ?>><?php echo esc_html(ucfirst($r)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="accion_miembro" value="cambiar_rol" class="button button-small" title="<?php esc_attr_e('Cambiar rol', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-update"></span>
                            </button>

                            <button type="submit" name="accion_miembro" value="dar_baja" class="button button-small" title="<?php esc_attr_e('Dar de baja', 'flavor-platform'); ?>" onclick="return confirm('<?php esc_attr_e('¿Dar de baja definitiva?', 'flavor-platform'); ?>');" style="margin-left:8px;">
                                <span class="dashicons dashicons-dismiss" style="color:#dc2626;"></span>
                            </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding:40px;text-align:center;color:#666;">
                        <?php esc_html_e('Esta empresa no tiene miembros.', 'flavor-platform'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal agregar miembro -->
<div id="modal-agregar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;">
    <div style="background:#fff;padding:24px;border-radius:12px;max-width:500px;width:90%;">
        <h3 style="margin:0 0 20px;"><?php esc_html_e('Agregar miembro', 'flavor-platform'); ?></h3>
        <form method="post">
            <?php wp_nonce_field('agregar_miembro'); ?>

            <p>
                <label><strong><?php esc_html_e('Usuario', 'flavor-platform'); ?></strong></label><br>
                <select name="user_id" required style="width:100%;">
                    <option value=""><?php esc_html_e('Seleccionar usuario...', 'flavor-platform'); ?></option>
                    <?php
                    $usuarios = get_users(['number' => 100, 'orderby' => 'display_name']);
                    foreach ($usuarios as $u):
                    ?>
                    <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name . ' (' . $u->user_email . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label><strong><?php esc_html_e('Rol', 'flavor-platform'); ?></strong></label><br>
                <select name="rol" style="width:100%;">
                    <?php foreach ($roles_disponibles as $r): ?>
                    <option value="<?php echo esc_attr($r); ?>"><?php echo esc_html($roles_labels[$r] ?? ucfirst($r)); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label><strong><?php esc_html_e('Cargo', 'flavor-platform'); ?></strong></label><br>
                <input type="text" name="cargo" style="width:100%;" />
            </p>

            <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="button" onclick="document.getElementById('modal-agregar').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-platform'); ?>
                </button>
                <button type="submit" name="agregar_miembro" class="button button-primary">
                    <?php esc_html_e('Agregar', 'flavor-platform'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
