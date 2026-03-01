<?php
/** Dashboard de Comunidades * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-comunidades-dashboard">
    <h1 class="wp-heading-inline"><?php _e('Comunidades - Dashboard', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rapidos -->
    <div class="comunidades-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=comunidades-listado'); ?>" class="comunidades-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-multisite" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Listado', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=comunidades-actividad'); ?>" class="comunidades-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-rss" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Feed Actividad', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=comunidades-metricas'); ?>" class="comunidades-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Metricas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=comunidades-config'); ?>" class="comunidades-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-admin-multisite"></span></div><div class="flavor-kpi-content"><h3><?php _e('Total Comunidades', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="total-comunidades">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-groups"></span></div><div class="flavor-kpi-content"><h3><?php _e('Miembros Activos', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="miembros-activos">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-format-chat"></span></div><div class="flavor-kpi-content"><h3><?php _e('Publicaciones Hoy', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="publicaciones-hoy">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-calendar-alt"></span></div><div class="flavor-kpi-content"><h3><?php _e('Eventos Programados', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="eventos-programados">0</div></div></div>
    </div>
    <div class="flavor-grid-two-columns">
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Comunidades mas Activas', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="comunidades-activas"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Actividad Reciente', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="actividad-reciente"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Crecimiento de Miembros', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-crecimiento" width="400" height="300"></canvas></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Distribucion por Categoria', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-categorias" width="400" height="300"></canvas></div></div>
    </div>
</div>
<style>
.flavor-comunidades-dashboard{margin:20px;}.flavor-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}.flavor-kpi-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;display:flex;align-items:center;gap:15px;}.flavor-kpi-icon{font-size:48px;color:#2271b1;}.flavor-kpi-icon .dashicons{width:48px;height:48px;font-size:48px;}.flavor-kpi-content h3{margin:0 0 5px 0;font-size:14px;color:#666;}.flavor-kpi-value{font-size:32px;font-weight:700;color:#1d2327;}.flavor-grid-two-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}
</style>
<script>
jQuery(document).ready(function($) {
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: { action: 'comunidades_get_dashboard_data' },
        success: function(response) {
            if (response.success) {
                $('#total-comunidades').text(response.data.kpis.total_comunidades || 0);
                $('#miembros-activos').text(response.data.kpis.miembros_activos || 0);
                $('#publicaciones-hoy').text(response.data.kpis.publicaciones_hoy || 0);
                $('#eventos-programados').text(response.data.kpis.eventos_programados || 0);
                if (response.data.crecimiento && typeof Chart !== 'undefined') {
                    new Chart(document.getElementById('grafico-crecimiento').getContext('2d'), {
                        type: 'line',
                        data: { labels: response.data.crecimiento.labels, datasets: [{ label: 'Miembros', data: response.data.crecimiento.values, borderColor: '#2271b1', fill: false }] }
                    });
                }
                if (response.data.categorias && typeof Chart !== 'undefined') {
                    new Chart(document.getElementById('grafico-categorias').getContext('2d'), {
                        type: 'doughnut',
                        data: { labels: response.data.categorias.labels, datasets: [{ data: response.data.categorias.values, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }] }
                    });
                }
            }
        }
    });
});
</script>
