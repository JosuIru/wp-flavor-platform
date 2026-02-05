<?php
/** Dashboard de Eventos * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-eventos-dashboard">
    <h1 class="wp-heading-inline"><?php _e('Eventos - Dashboard', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-calendar"></span></div><div class="flavor-kpi-content"><h3><?php _e('Eventos Activos', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="eventos-activos">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-tickets-alt"></span></div><div class="flavor-kpi-content"><h3><?php _e('Entradas Vendidas', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="entradas-vendidas">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-groups"></span></div><div class="flavor-kpi-content"><h3><?php _e('Asistentes Totales', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="asistentes-totales">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-money-alt"></span></div><div class="flavor-kpi-content"><h3><?php _e('Ingresos', 'flavor-chat-ia'); ?></h3><div class="flavor-kpi-value" id="ingresos-totales">0€</div></div></div>
    </div>
    <div class="flavor-grid-two-columns">
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Próximos Eventos', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="proximos-eventos"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Eventos por Categoría', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-categorias" width="400" height="300"></canvas></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Asistencia Mensual', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-asistencia" width="400" height="300"></canvas></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Eventos Populares', 'flavor-chat-ia'); ?></h2></div><div class="flavor-card-body" id="eventos-populares"></div></div>
    </div>
</div>
<style>
.flavor-eventos-dashboard{margin:20px;}.flavor-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}.flavor-kpi-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;display:flex;align-items:center;gap:15px;}.flavor-kpi-icon{font-size:48px;color:#2271b1;}.flavor-kpi-icon .dashicons{width:48px;height:48px;font-size:48px;}.flavor-kpi-content h3{margin:0 0 5px 0;font-size:14px;color:#666;}.flavor-kpi-value{font-size:32px;font-weight:700;color:#1d2327;}.flavor-grid-two-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}
</style>
<script>
jQuery(document).ready(function($) {
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: { action: 'eventos_get_dashboard_data' },
        success: function(response) {
            if (response.success) {
                $('#eventos-activos').text(response.data.kpis.eventos_activos);
                $('#entradas-vendidas').text(response.data.kpis.entradas_vendidas);
                $('#asistentes-totales').text(response.data.kpis.asistentes_totales);
                $('#ingresos-totales').text(response.data.kpis.ingresos_totales + '€');
                new Chart(document.getElementById('grafico-categorias').getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: response.data.categorias.labels, datasets: [{ data: response.data.categorias.values, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }] }
                });
                new Chart(document.getElementById('grafico-asistencia').getContext('2d'), {
                    type: 'bar',
                    data: { labels: response.data.asistencia.labels, datasets: [{ label: 'Asistentes', data: response.data.asistencia.values, backgroundColor: '#2271b1' }] }
                });
            }
        }
    });
});
</script>
