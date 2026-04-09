<?php
/**
 * Detalle de Empresa - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_labels = [
    'sl' => 'Sociedad Limitada',
    'sa' => 'Sociedad Anónima',
    'autonomo' => 'Autónomo',
    'cooperativa' => 'Cooperativa',
    'asociacion' => 'Asociación',
    'comunidad_bienes' => 'Comunidad de Bienes',
    'sociedad_civil' => 'Sociedad Civil',
    'otro' => 'Otro',
];

if (isset($_GET['created'])) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Empresa creada correctamente.', 'flavor-platform') . '</p></div>';
}
?>
<div class="wrap flavor-modulo-page">
    <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado')); ?>" class="button" style="margin-bottom:16px;">
        ← <?php esc_html_e('Volver al listado', 'flavor-platform'); ?>
    </a>

    <!-- Cabecera -->
    <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
        <div style="display:flex;align-items:start;gap:20px;">
            <?php if ($empresa->logo_url): ?>
            <img src="<?php echo esc_url($empresa->logo_url); ?>" alt="" style="width:80px;height:80px;border-radius:12px;object-fit:cover;" />
            <?php else: ?>
            <div style="width:80px;height:80px;border-radius:12px;background:#e0e7ff;display:flex;align-items:center;justify-content:center;">
                <span class="dashicons dashicons-building" style="font-size:36px;width:36px;height:36px;color:#3730a3;"></span>
            </div>
            <?php endif; ?>

            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                    <h1 style="margin:0;font-size:24px;"><?php echo esc_html($empresa->nombre); ?></h1>
                    <?php
                    $estados_colores = [
                        'activa' => ['bg' => '#dcfce7', 'color' => '#166534'],
                        'pendiente' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                        'suspendida' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                        'baja' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                    ];
                    $estado_style = $estados_colores[$empresa->estado] ?? $estados_colores['baja'];
                    ?>
                    <span style="padding:4px 12px;border-radius:12px;font-size:12px;font-weight:500;background:<?php echo esc_attr($estado_style['bg']); ?>;color:<?php echo esc_attr($estado_style['color']); ?>;">
                        <?php echo esc_html(ucfirst($empresa->estado)); ?>
                    </span>
                </div>

                <?php if ($empresa->razon_social && $empresa->razon_social !== $empresa->nombre): ?>
                <p style="margin:0 0 8px;color:#666;"><?php echo esc_html($empresa->razon_social); ?></p>
                <?php endif; ?>

                <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:#666;">
                    <?php if ($empresa->cif_nif): ?>
                    <span><strong><?php esc_html_e('CIF/NIF:', 'flavor-platform'); ?></strong> <?php echo esc_html($empresa->cif_nif); ?></span>
                    <?php endif; ?>
                    <span>
                        <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;background:#e0e7ff;color:#3730a3;">
                            <?php echo esc_html($tipos_labels[$empresa->tipo] ?? strtoupper($empresa->tipo)); ?>
                        </span>
                    </span>
                    <?php if ($empresa->sector): ?>
                    <span><strong><?php esc_html_e('Sector:', 'flavor-platform'); ?></strong> <?php echo esc_html(ucfirst($empresa->sector)); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=editar&id=' . $empresa->id)); ?>" class="button">
                    <span class="dashicons dashicons-edit" style="vertical-align:middle;"></span>
                    <?php esc_html_e('Editar', 'flavor-platform'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=miembros&id=' . $empresa->id)); ?>" class="button button-primary">
                    <span class="dashicons dashicons-groups" style="vertical-align:middle;"></span>
                    <?php esc_html_e('Miembros', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:20px;">
        <div style="background:#fff;padding:16px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size:24px;font-weight:700;color:#3b82f6;"><?php echo esc_html($stats['miembros_activos']); ?></div>
            <div style="font-size:12px;color:#666;"><?php esc_html_e('Miembros activos', 'flavor-platform'); ?></div>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size:24px;font-weight:700;color:#10b981;"><?php echo esc_html(number_format($stats['ingresos_mes'], 2, ',', '.')); ?> €</div>
            <div style="font-size:12px;color:#666;"><?php esc_html_e('Ingresos mes', 'flavor-platform'); ?></div>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size:24px;font-weight:700;color:#ef4444;"><?php echo esc_html(number_format($stats['gastos_mes'], 2, ',', '.')); ?> €</div>
            <div style="font-size:12px;color:#666;"><?php esc_html_e('Gastos mes', 'flavor-platform'); ?></div>
        </div>
        <div style="background:#fff;padding:16px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size:24px;font-weight:700;color:#8b5cf6;"><?php echo esc_html($stats['documentos']); ?></div>
            <div style="font-size:12px;color:#666;"><?php esc_html_e('Documentos', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
        <!-- Columna izquierda -->
        <div>
            <!-- Datos de contacto -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <?php esc_html_e('Información de contacto', 'flavor-platform'); ?>
                </h3>
                <table class="widefat" style="border:none;">
                    <tbody>
                        <?php if ($empresa->email): ?>
                        <tr>
                            <td style="padding:10px;width:120px;color:#666;"><span class="dashicons dashicons-email"></span> <?php esc_html_e('Email', 'flavor-platform'); ?></td>
                            <td style="padding:10px;"><a href="mailto:<?php echo esc_attr($empresa->email); ?>"><?php echo esc_html($empresa->email); ?></a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($empresa->telefono): ?>
                        <tr>
                            <td style="padding:10px;color:#666;"><span class="dashicons dashicons-phone"></span> <?php esc_html_e('Teléfono', 'flavor-platform'); ?></td>
                            <td style="padding:10px;"><a href="tel:<?php echo esc_attr($empresa->telefono); ?>"><?php echo esc_html($empresa->telefono); ?></a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($empresa->web): ?>
                        <tr>
                            <td style="padding:10px;color:#666;"><span class="dashicons dashicons-admin-site"></span> <?php esc_html_e('Web', 'flavor-platform'); ?></td>
                            <td style="padding:10px;"><a href="<?php echo esc_url($empresa->web); ?>" target="_blank"><?php echo esc_html($empresa->web); ?></a></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($empresa->direccion): ?>
                        <tr>
                            <td style="padding:10px;color:#666;"><span class="dashicons dashicons-location"></span> <?php esc_html_e('Dirección', 'flavor-platform'); ?></td>
                            <td style="padding:10px;">
                                <?php echo esc_html($empresa->direccion); ?><br>
                                <?php echo esc_html($empresa->codigo_postal . ' ' . $empresa->ciudad); ?>
                                <?php if ($empresa->provincia): ?>, <?php echo esc_html($empresa->provincia); ?><?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Descripción -->
            <?php if ($empresa->descripcion): ?>
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <?php esc_html_e('Descripción', 'flavor-platform'); ?>
                </h3>
                <p style="margin:0;color:#444;line-height:1.6;"><?php echo nl2br(esc_html($empresa->descripcion)); ?></p>
            </div>
            <?php endif; ?>

            <!-- Miembros -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                    <?php esc_html_e('Miembros', 'flavor-platform'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=miembros&id=' . $empresa->id)); ?>" style="font-size:13px;font-weight:400;">
                        <?php esc_html_e('Gestionar →', 'flavor-platform'); ?>
                    </a>
                </h3>
                <?php if (!empty($miembros)): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                    <?php foreach (array_slice($miembros, 0, 6) as $m): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:#f8fafc;border-radius:8px;">
                        <?php echo get_avatar($m->user_id, 36, '', '', ['extra_attr' => 'style="border-radius:50%;"']); ?>
                        <div>
                            <div style="font-weight:500;font-size:13px;"><?php echo esc_html($m->display_name); ?></div>
                            <div style="font-size:11px;color:#666;">
                                <?php echo esc_html(ucfirst($m->rol)); ?>
                                <?php if ($m->cargo): ?> · <?php echo esc_html($m->cargo); ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($miembros) > 6): ?>
                <p style="margin:12px 0 0;text-align:center;color:#666;font-size:13px;">
                    <?php printf(esc_html__('Y %d miembros más...', 'flavor-platform'), count($miembros) - 6); ?>
                </p>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:#666;text-align:center;padding:20px;"><?php esc_html_e('Sin miembros aún.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Columna derecha -->
        <div>
            <!-- Actividad reciente -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <?php esc_html_e('Actividad reciente', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($actividad)): ?>
                <ul style="list-style:none;margin:0;padding:0;max-height:400px;overflow-y:auto;">
                    <?php foreach ($actividad as $act): ?>
                    <li style="padding:10px 0;border-bottom:1px solid #f3f4f6;">
                        <div style="font-size:13px;"><?php echo esc_html($act->descripcion); ?></div>
                        <div style="font-size:11px;color:#666;margin-top:4px;">
                            <?php echo esc_html($act->display_name ?? __('Sistema', 'flavor-platform')); ?>
                            · <?php echo esc_html(human_time_diff(strtotime($act->created_at), current_time('timestamp'))); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p style="color:#666;text-align:center;padding:20px;"><?php esc_html_e('Sin actividad reciente.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
