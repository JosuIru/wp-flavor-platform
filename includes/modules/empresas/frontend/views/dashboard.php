<?php
/**
 * Dashboard Frontend - Empresa
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
<div class="flavor-empresas-dashboard">
    <!-- Selector de empresa (si tiene varias) -->
    <?php if (count($empresas_usuario) > 1): ?>
    <div class="flavor-empresa-selector" style="margin-bottom:20px;">
        <select onchange="location.href=this.value" class="flavor-select">
            <?php foreach ($empresas_usuario as $eu): ?>
            <option value="<?php echo esc_url(add_query_arg('empresa', $eu->empresa_id)); ?>" <?php selected($eu->empresa_id, $empresa->id); ?>>
                <?php echo esc_html($eu->empresa_nombre); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Cabecera empresa -->
    <div class="flavor-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <?php if ($empresa->logo_url): ?>
            <img src="<?php echo esc_url($empresa->logo_url); ?>" alt="" style="width:64px;height:64px;border-radius:12px;object-fit:cover;" />
            <?php else: ?>
            <div style="width:64px;height:64px;border-radius:12px;background:var(--flavor-primary-light, #e0e7ff);display:flex;align-items:center;justify-content:center;">
                <span class="dashicons dashicons-building" style="font-size:32px;width:32px;height:32px;color:var(--flavor-primary, #3730a3);"></span>
            </div>
            <?php endif; ?>

            <div style="flex:1;">
                <h2 style="margin:0 0 4px;font-size:20px;"><?php echo esc_html($empresa->nombre); ?></h2>
                <p style="margin:0;color:#666;font-size:14px;">
                    <?php echo esc_html($roles_labels[$miembro->rol] ?? ucfirst($miembro->rol)); ?>
                    <?php if ($miembro->cargo): ?>
                        · <?php echo esc_html($miembro->cargo); ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($es_admin): ?>
            <a href="<?php echo esc_url(add_query_arg('vista', 'perfil')); ?>" class="flavor-btn flavor-btn-secondary">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Editar', 'flavor-platform'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="flavor-grid flavor-grid-4" style="margin-bottom:20px;">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background:#dbeafe;color:#2563eb;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo esc_html($stats['miembros_activos']); ?></div>
                <div class="flavor-stat-label"><?php esc_html_e('Miembros', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo esc_html($stats['documentos']); ?></div>
                <div class="flavor-stat-label"><?php esc_html_e('Documentos', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="flavor-card" style="margin-bottom:20px;">
        <h3 style="margin:0 0 16px;font-size:16px;"><?php esc_html_e('Accesos rápidos', 'flavor-platform'); ?></h3>
        <div class="flavor-quick-actions">
            <a href="<?php echo esc_url(add_query_arg('vista', 'miembros')); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Ver miembros', 'flavor-platform'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('vista', 'documentos')); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Documentos', 'flavor-platform'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('vista', 'perfil')); ?>" class="flavor-quick-action">
                <span class="dashicons dashicons-id-alt"></span>
                <?php esc_html_e('Perfil empresa', 'flavor-platform'); ?>
            </a>
            <?php if ($es_admin || $miembro->rol === 'contable'): ?>
            <a href="#contabilidad" class="flavor-quick-action">
                <span class="dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Contabilidad', 'flavor-platform'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="flavor-grid flavor-grid-2">
        <!-- Miembros recientes -->
        <div class="flavor-card">
            <h3 style="margin:0 0 16px;font-size:16px;display:flex;justify-content:space-between;align-items:center;">
                <?php esc_html_e('Equipo', 'flavor-platform'); ?>
                <a href="<?php echo esc_url(add_query_arg('vista', 'miembros')); ?>" style="font-size:13px;font-weight:400;">
                    <?php esc_html_e('Ver todos →', 'flavor-platform'); ?>
                </a>
            </h3>

            <?php if (!empty($miembros_recientes)): ?>
            <div class="flavor-team-list">
                <?php foreach ($miembros_recientes as $m): ?>
                <div class="flavor-team-member">
                    <?php echo get_avatar($m->user_id, 40, '', '', ['extra_attr' => 'style="border-radius:50%;"']); ?>
                    <div class="flavor-team-info">
                        <strong><?php echo esc_html($m->display_name); ?></strong>
                        <span><?php echo esc_html($roles_labels[$m->rol] ?? ucfirst($m->rol)); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:#666;text-align:center;padding:20px;"><?php esc_html_e('Sin miembros aún.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Actividad reciente -->
        <div class="flavor-card">
            <h3 style="margin:0 0 16px;font-size:16px;display:flex;justify-content:space-between;align-items:center;">
                <?php esc_html_e('Actividad reciente', 'flavor-platform'); ?>
                <a href="<?php echo esc_url(add_query_arg('vista', 'actividad')); ?>" style="font-size:13px;font-weight:400;">
                    <?php esc_html_e('Ver toda →', 'flavor-platform'); ?>
                </a>
            </h3>

            <?php if (!empty($actividad)): ?>
            <ul class="flavor-activity-list">
                <?php foreach (array_slice($actividad, 0, 5) as $act): ?>
                <li>
                    <div class="flavor-activity-content"><?php echo esc_html($act->descripcion); ?></div>
                    <div class="flavor-activity-meta">
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

<style>
.flavor-empresas-dashboard .flavor-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.flavor-empresas-dashboard .flavor-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-empresas-dashboard .flavor-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.flavor-empresas-dashboard .flavor-stat-value {
    font-size: 24px;
    font-weight: 700;
}
.flavor-empresas-dashboard .flavor-stat-label {
    font-size: 13px;
    color: #666;
}
.flavor-empresas-dashboard .flavor-quick-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.flavor-empresas-dashboard .flavor-quick-action {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #f8fafc;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    transition: background 0.2s;
}
.flavor-empresas-dashboard .flavor-quick-action:hover {
    background: #e5e7eb;
}
.flavor-empresas-dashboard .flavor-team-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.flavor-empresas-dashboard .flavor-team-member {
    display: flex;
    align-items: center;
    gap: 12px;
}
.flavor-empresas-dashboard .flavor-team-info {
    display: flex;
    flex-direction: column;
}
.flavor-empresas-dashboard .flavor-team-info span {
    font-size: 12px;
    color: #666;
}
.flavor-empresas-dashboard .flavor-activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.flavor-empresas-dashboard .flavor-activity-list li {
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}
.flavor-empresas-dashboard .flavor-activity-list li:last-child {
    border-bottom: none;
}
.flavor-empresas-dashboard .flavor-activity-content {
    font-size: 13px;
}
.flavor-empresas-dashboard .flavor-activity-meta {
    font-size: 11px;
    color: #666;
    margin-top: 4px;
}
</style>
