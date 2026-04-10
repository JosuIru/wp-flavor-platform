<?php
/**
 * Estadísticas e Impacto - Ayuda Vecinal
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-estadisticas-ayuda">
    <h1><?php _e('Estadísticas e Impacto Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-chart-line"></span></div><div class="flavor-kpi-content"><h3><?php _e('Total Ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3><div class="flavor-kpi-value" id="total-ayudas">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-clock"></span></div><div class="flavor-kpi-content"><h3><?php _e('Horas Voluntariado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3><div class="flavor-kpi-value" id="total-horas">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-groups"></span></div><div class="flavor-kpi-content"><h3><?php _e('Personas Ayudadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3><div class="flavor-kpi-value" id="personas-ayudadas">0</div></div></div>
        <div class="flavor-kpi-card"><div class="flavor-kpi-icon"><span class="dashicons dashicons-heart"></span></div><div class="flavor-kpi-content"><h3><?php _e('Valoración Media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3><div class="flavor-kpi-value" id="valoracion-media">0.0</div></div></div>
    </div>
    <div class="flavor-grid-two-columns">
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Evolución Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-evolucion" width="400" height="300"></canvas></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Ayudas por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-categorias-stats" width="400" height="300"></canvas></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Top Voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div><div class="flavor-card-body" id="top-voluntarios"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Tiempo Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-tiempos" width="400" height="300"></canvas></div></div>
    </div>
</div>
<style>
.flavor-estadisticas-ayuda{margin:20px;}.flavor-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}.flavor-kpi-card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;display:flex;align-items:center;gap:15px;}.flavor-kpi-icon{font-size:48px;color:#2271b1;}.flavor-kpi-icon .dashicons{width:48px;height:48px;font-size:48px;}.flavor-kpi-content h3{margin:0 0 5px 0;font-size:14px;color:#666;font-weight:500;}.flavor-kpi-value{font-size:32px;font-weight:700;color:#1d2327;line-height:1;}.flavor-grid-two-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}
</style>
<script>
jQuery(document).ready(function($) {
    cargarEstadisticas();
    function cargarEstadisticas() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'ayuda_vecinal_obtener_estadisticas' },
            success: function(response) {
                if (response.success) {
                    $('#total-ayudas').text(response.data.kpis.total_ayudas);
                    $('#total-horas').text(response.data.kpis.total_horas);
                    $('#personas-ayudadas').text(response.data.kpis.personas_ayudadas);
                    $('#valoracion-media').text(response.data.kpis.valoracion_media);
                    renderizarGraficos(response.data);
                }
            }
        });
    }
    function renderizarGraficos(data) {
        // Gráfico de evolución
        new Chart(document.getElementById('grafico-evolucion').getContext('2d'), {
            type: 'line',
            data: { labels: data.evolucion.labels, datasets: [{ label: '<?php _e('Ayudas completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', data: data.evolucion.values, borderColor: '#2271b1', backgroundColor: 'rgba(34, 113, 177, 0.1)', tension: 0.4, fill: true }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
        // Gráfico de categorías
        new Chart(document.getElementById('grafico-categorias-stats').getContext('2d'), {
            type: 'doughnut',
            data: { labels: data.categorias.labels, datasets: [{ data: data.categorias.values, backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
        // Top voluntarios
        let html = '';
        data.top_voluntarios.forEach((vol, idx) => {
            html += `<div style="padding:10px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;">
                <span><strong>${idx + 1}.</strong> ${vol.nombre}</span>
                <span style="color:#2271b1;font-weight:600;">${vol.ayudas} ayudas</span>
            </div>`;
        });
        $('#top-voluntarios').html(html);
        // Gráfico de tiempos
        new Chart(document.getElementById('grafico-tiempos').getContext('2d'), {
            type: 'bar',
            data: { labels: ['<?php _e('Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', '<?php _e('Asignación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', '<?php _e('Resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>'], datasets: [{ label: '<?php _e('Horas promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', data: data.tiempos, backgroundColor: '#10b981' }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>
