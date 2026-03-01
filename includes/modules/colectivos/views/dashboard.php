<?php
/** Dashboard de Colectivos * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-colectivos-dashboard">
    <h1 class="wp-heading-inline"><?php _e('Colectivos - Dashboard', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rapidos -->
    <div class="colectivos-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=colectivos-listado'); ?>" class="colectivos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Listado', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=colectivos-proyectos'); ?>" class="colectivos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-portfolio" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Proyectos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=colectivos-asambleas'); ?>" class="colectivos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Asambleas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-app-composer&module=colectivos'); ?>" class="colectivos-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-groups"></span></div><div class="flavor-kpi-content"><h3><?php _e('Total Colectivos', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="total-colectivos">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-admin-users"></span></div><div class="flavor-kpi-content"><h3><?php _e('Miembros Totales', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="miembros-totales">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-portfolio"></span></div><div class="flavor-kpi-content"><h3><?php _e('Proyectos Activos', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="proyectos-activos">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-calendar-alt"></span></div><div class="flavor-kpi-content"><h3><?php _e('Asambleas Programadas', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="asambleas-programadas">0</div></div></div>
    </div>
    <div class="flavor-grid-two-columns">
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Colectivos mas Activos', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="colectivos-activos"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Proximas Asambleas', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="proximas-asambleas"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Proyectos Recientes', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="proyectos-recientes"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Distribucion por Tipo', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-tipos" width="400" height="300"></canvas></div></div>
    </div>
</div>
<style>
.flavor-colectivos-dashboard{margin:20px;}.flavor-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}.flavor-kpi-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;display:flex;align-items:center;gap:15px;}.flavor-kpi-icon{font-size:48px;color:#2271b1;}.flavor-kpi-icon .dashicons{width:48px;height:48px;font-size:48px;}.flavor-kpi-content h3{margin:0 0 5px 0;font-size:14px;color:#666;}.flavor-kpi-value{font-size:32px;font-weight:700;color:#1d2327;}.flavor-grid-two-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}
</style>
<script>
jQuery(document).ready(function($) {
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: { action: 'colectivos_get_dashboard_data' },
        success: function(response) {
            if (response.success) {
                $('#total-colectivos').text(response.data.kpis.total_colectivos || 0);
                $('#miembros-totales').text(response.data.kpis.miembros_totales || 0);
                $('#proyectos-activos').text(response.data.kpis.proyectos_activos || 0);
                $('#asambleas-programadas').text(response.data.kpis.asambleas_programadas || 0);
                if (response.data.tipos && typeof Chart !== 'undefined') {
                    new Chart(document.getElementById('grafico-tipos').getContext('2d'), {
                        type: 'doughnut',
                        data: { labels: response.data.tipos.labels, datasets: [{ data: response.data.tipos.values, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }] }
                    });
                }
            }
        }
    });
});
</script>
