<?php
/**
 * Listado de Empresas - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_labels = [
    'sl' => 'S.L.',
    'sa' => 'S.A.',
    'autonomo' => 'Autónomo',
    'cooperativa' => 'Coop.',
    'asociacion' => 'Asoc.',
    'comunidad_bienes' => 'C.B.',
    'sociedad_civil' => 'S.C.',
    'otro' => 'Otro',
];
?>
<div class="wrap flavor-modulo-page">
    <?php $this->render_page_header(__('Empresas', 'flavor-platform'), [
        ['label' => __('Nueva empresa', 'flavor-platform'), 'url' => admin_url('admin.php?page=empresas-listado&action=crear'), 'class' => 'button-primary'],
    ]); ?>

    <!-- Filtros -->
    <div style="background:#fff;padding:16px 20px;border-radius:8px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="page" value="empresas-listado" />

            <div>
                <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Estado', 'flavor-platform'); ?></label>
                <select name="estado">
                    <option value="activa" <?php selected($filtros['estado'], 'activa'); ?>><?php esc_html_e('Activas', 'flavor-platform'); ?></option>
                    <option value="pendiente" <?php selected($filtros['estado'], 'pendiente'); ?>><?php esc_html_e('Pendientes', 'flavor-platform'); ?></option>
                    <option value="suspendida" <?php selected($filtros['estado'], 'suspendida'); ?>><?php esc_html_e('Suspendidas', 'flavor-platform'); ?></option>
                    <option value="" <?php selected($filtros['estado'], ''); ?>><?php esc_html_e('Todas', 'flavor-platform'); ?></option>
                </select>
            </div>

            <div>
                <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Tipo', 'flavor-platform'); ?></label>
                <select name="tipo">
                    <option value=""><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                    <?php foreach ($tipos_labels as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($filtros['tipo'], $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Buscar', 'flavor-platform'); ?></label>
                <input type="text" name="s" value="<?php echo esc_attr($filtros['busqueda']); ?>" placeholder="<?php esc_attr_e('Nombre, razón social o CIF...', 'flavor-platform'); ?>" style="min-width:200px;" />
            </div>

            <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>
        </form>
    </div>

    <!-- Resumen -->
    <div style="margin-bottom:16px;color:#666;">
        <?php printf(esc_html__('Mostrando %d empresas', 'flavor-platform'), count($empresas)); ?>
    </div>

    <!-- Tabla -->
    <table class="widefat striped" style="background:#fff;border-radius:8px;overflow:hidden;">
        <thead>
            <tr>
                <th style="padding:12px;"><?php esc_html_e('Empresa', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('CIF/NIF', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Sector', 'flavor-platform'); ?></th>
                <th style="padding:12px;"><?php esc_html_e('Contacto', 'flavor-platform'); ?></th>
                <th style="padding:12px;text-align:center;"><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                <th style="padding:12px;text-align:center;"><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($empresas)): ?>
                <?php foreach ($empresas as $emp): ?>
                <tr>
                    <td style="padding:12px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($emp->logo_url): ?>
                            <img src="<?php echo esc_url($emp->logo_url); ?>" alt="" style="width:40px;height:40px;border-radius:8px;object-fit:cover;" />
                            <?php else: ?>
                            <div style="width:40px;height:40px;border-radius:8px;background:#e0e7ff;display:flex;align-items:center;justify-content:center;">
                                <span class="dashicons dashicons-building" style="color:#3730a3;"></span>
                            </div>
                            <?php endif; ?>
                            <div>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $emp->id)); ?>" style="font-weight:600;color:#1e40af;">
                                    <?php echo esc_html($emp->nombre); ?>
                                </a>
                                <?php if ($emp->razon_social && $emp->razon_social !== $emp->nombre): ?>
                                <br><small style="color:#666;"><?php echo esc_html($emp->razon_social); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px;font-family:monospace;"><?php echo esc_html($emp->cif_nif ?: '-'); ?></td>
                    <td style="padding:12px;">
                        <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;background:#e0e7ff;color:#3730a3;">
                            <?php echo esc_html($tipos_labels[$emp->tipo] ?? strtoupper($emp->tipo)); ?>
                        </span>
                    </td>
                    <td style="padding:12px;"><?php echo esc_html(ucfirst($emp->sector ?: '-')); ?></td>
                    <td style="padding:12px;">
                        <?php if ($emp->email): ?>
                        <a href="mailto:<?php echo esc_attr($emp->email); ?>" style="font-size:12px;"><?php echo esc_html($emp->email); ?></a><br>
                        <?php endif; ?>
                        <?php if ($emp->telefono): ?>
                        <span style="font-size:12px;color:#666;"><?php echo esc_html($emp->telefono); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        <?php
                        $estados_colores = [
                            'activa' => ['bg' => '#dcfce7', 'color' => '#166534'],
                            'pendiente' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                            'suspendida' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                            'baja' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                        ];
                        $estado_style = $estados_colores[$emp->estado] ?? $estados_colores['baja'];
                        ?>
                        <span style="display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:500;background:<?php echo esc_attr($estado_style['bg']); ?>;color:<?php echo esc_attr($estado_style['color']); ?>;">
                            <?php echo esc_html(ucfirst($emp->estado)); ?>
                        </span>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $emp->id)); ?>" title="<?php esc_attr_e('Ver', 'flavor-platform'); ?>" style="margin-right:4px;">
                            <span class="dashicons dashicons-visibility" style="color:#666;"></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=editar&id=' . $emp->id)); ?>" title="<?php esc_attr_e('Editar', 'flavor-platform'); ?>" style="margin-right:4px;">
                            <span class="dashicons dashicons-edit" style="color:#2563eb;"></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=miembros&id=' . $emp->id)); ?>" title="<?php esc_attr_e('Miembros', 'flavor-platform'); ?>">
                            <span class="dashicons dashicons-groups" style="color:#10b981;"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding:40px;text-align:center;color:#666;">
                        <?php esc_html_e('No se encontraron empresas.', 'flavor-platform'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
