<?php
/**
 * Dashboard de Empresas - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header-content">
            <h1 class="dm-title">
                <span class="dashicons dashicons-building"></span>
                <?php esc_html_e('Dashboard de Empresas', 'flavor-platform'); ?>
            </h1>
            <p class="dm-subtitle"><?php esc_html_e('Gestiona las empresas de tu comunidad', 'flavor-platform'); ?></p>
        </div>
        <div class="dm-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=crear')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Nueva empresa', 'flavor-platform'); ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="dm-kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;padding:20px;border-radius:12px;">
            <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Empresas activas', 'flavor-platform'); ?></div>
            <div style="font-size:32px;font-weight:700;"><?php echo esc_html($total_activas); ?></div>
        </div>

        <div style="background:linear-gradient(135deg,<?php echo $total_pendientes > 0 ? '#f59e0b,#d97706' : '#6b7280,#4b5563'; ?>);color:#fff;padding:20px;border-radius:12px;">
            <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Solicitudes pendientes', 'flavor-platform'); ?></div>
            <div style="font-size:32px;font-weight:700;"><?php echo esc_html($total_pendientes); ?></div>
            <?php if ($total_pendientes > 0): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-solicitudes')); ?>" style="color:#fff;font-size:12px;opacity:0.9;">
                <?php esc_html_e('Revisar →', 'flavor-platform'); ?>
            </a>
            <?php endif; ?>
        </div>

        <div style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:20px;border-radius:12px;">
            <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Miembros totales', 'flavor-platform'); ?></div>
            <div style="font-size:32px;font-weight:700;"><?php echo esc_html($total_miembros); ?></div>
        </div>

        <div style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:20px;border-radius:12px;">
            <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Suspendidas', 'flavor-platform'); ?></div>
            <div style="font-size:32px;font-weight:700;"><?php echo esc_html($total_suspendidas); ?></div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
        <!-- Columna izquierda -->
        <div>
            <!-- Últimas empresas -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <span class="dashicons dashicons-building" style="color:#3b82f6;"></span>
                    <?php esc_html_e('Últimas empresas', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($ultimas_empresas)): ?>
                <table class="widefat" style="border:none;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Empresa', 'flavor-platform'); ?></th>
                            <th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                            <th><?php esc_html_e('Sector', 'flavor-platform'); ?></th>
                            <th style="text-align:center;"><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_empresas as $emp): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $emp->id)); ?>" style="font-weight:500;">
                                    <?php echo esc_html($emp->nombre); ?>
                                </a>
                                <?php if ($emp->razon_social && $emp->razon_social !== $emp->nombre): ?>
                                <br><small style="color:#666;"><?php echo esc_html($emp->razon_social); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;background:#e0e7ff;color:#3730a3;">
                                    <?php echo esc_html(strtoupper($emp->tipo)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(ucfirst($emp->sector ?: '-')); ?></td>
                            <td style="text-align:center;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=ver&id=' . $emp->id)); ?>" title="<?php esc_attr_e('Ver', 'flavor-platform'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=miembros&id=' . $emp->id)); ?>" title="<?php esc_attr_e('Miembros', 'flavor-platform'); ?>">
                                    <span class="dashicons dashicons-groups"></span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado')); ?>" style="display:block;text-align:center;margin-top:12px;color:#2563eb;">
                    <?php esc_html_e('Ver todas las empresas →', 'flavor-platform'); ?>
                </a>
                <?php else: ?>
                <p style="color:#666;text-align:center;padding:20px;">
                    <?php esc_html_e('No hay empresas registradas aún.', 'flavor-platform'); ?>
                    <br>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-listado&action=crear')); ?>" class="button" style="margin-top:10px;">
                        <?php esc_html_e('Crear primera empresa', 'flavor-platform'); ?>
                    </a>
                </p>
                <?php endif; ?>
            </div>

            <!-- Solicitudes pendientes -->
            <?php if (!empty($solicitudes_recientes)): ?>
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;color:#f59e0b;">
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Solicitudes pendientes', 'flavor-platform'); ?>
                </h3>
                <table class="widefat" style="border:none;">
                    <tbody>
                        <?php foreach ($solicitudes_recientes as $sol): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($sol->nombre); ?></strong>
                                <?php if ($sol->cif_nif): ?>
                                <br><small style="color:#666;"><?php echo esc_html($sol->cif_nif); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(strtoupper($sol->tipo)); ?></td>
                            <td style="text-align:right;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=empresas-solicitudes')); ?>" class="button button-small">
                                    <?php esc_html_e('Revisar', 'flavor-platform'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Columna derecha -->
        <div>
            <!-- Por tipo -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <?php esc_html_e('Por tipo', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($por_tipo)): ?>
                <ul style="list-style:none;margin:0;padding:0;">
                    <?php
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
                    foreach ($por_tipo as $t):
                    ?>
                    <li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                        <span><?php echo esc_html($tipos_labels[$t->tipo] ?? ucfirst($t->tipo)); ?></span>
                        <span style="font-weight:600;color:#3b82f6;"><?php echo esc_html($t->total); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p style="color:#666;"><?php esc_html_e('Sin datos', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Por sector -->
            <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                    <?php esc_html_e('Por sector', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($por_sector)): ?>
                <ul style="list-style:none;margin:0;padding:0;">
                    <?php foreach ($por_sector as $s): ?>
                    <li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                        <span><?php echo esc_html(ucfirst($s->sector)); ?></span>
                        <span style="font-weight:600;color:#10b981;"><?php echo esc_html($s->total); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p style="color:#666;"><?php esc_html_e('Sin datos', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
