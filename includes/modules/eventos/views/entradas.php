<?php
/** Gestión de Entradas/Tickets * @package FlavorPlatform */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-entradas-management">
    <h1><?php _e('Gestión de Entradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-stats-bar">
        <div class="flavor-stat-item"><span class="flavor-stat-label"><?php _e('Entradas vendidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span><span class="flavor-stat-value" id="stat-vendidas">0</span></div>
        <div class="flavor-stat-item"><span class="flavor-stat-label"><?php _e('Entradas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span><span class="flavor-stat-value" id="stat-disponibles">0</span></div>
        <div class="flavor-stat-item"><span class="flavor-stat-label"><?php _e('Ingresos totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span><span class="flavor-stat-value" id="stat-ingresos">0€</span></div>
    </div>
    <div class="flavor-grid-two-columns">
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Tipos de Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2><button class="button button-small" id="btn-nuevo-tipo"><?php _e('Nuevo Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button></div><div class="flavor-card-body" id="tipos-entrada-list"></div></div>
        <div class="flavor-card"><div class="flavor-card-header"><h2><?php _e('Ventas por Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div><div class="flavor-card-body"><canvas id="grafico-ventas" width="400" height="300"></canvas></div></div>
    </div>
</div>
<style>
.flavor-entradas-management{margin:20px;}.flavor-stats-bar{display:flex;gap:20px;background:#fff;padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;}.flavor-stat-item{flex:1;text-align:center;}.flavor-stat-label{display:block;font-size:12px;color:#666;margin-bottom:8px;text-transform:uppercase;}.flavor-stat-value{display:block;font-size:28px;font-weight:700;color:#2271b1;}.flavor-grid-two-columns{display:grid;grid-template-columns:1fr 1fr;gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}.flavor-tipo-entrada{padding:15px;border:1px solid #eee;border-radius:8px;margin-bottom:10px;}.flavor-tipo-entrada h4{margin:0 0 8px 0;}.flavor-tipo-precio{font-size:20px;font-weight:700;color:#10b981;}
</style>
<script>
jQuery(document).ready(function($) {
    cargarEstadisticasEntradas();
    cargarTiposEntrada();
    function cargarEstadisticasEntradas() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_estadisticas_entradas' },
            success: function(response) {
                if (response.success) {
                    $('#stat-vendidas').text(response.data.vendidas);
                    $('#stat-disponibles').text(response.data.disponibles);
                    $('#stat-ingresos').text(response.data.ingresos + '€');
                    new Chart(document.getElementById('grafico-ventas').getContext('2d'), {
                        type: 'bar',
                        data: { labels: response.data.ventas.labels, datasets: [{ label: '<?php _e('Entradas vendidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', data: response.data.ventas.values, backgroundColor: '#2271b1' }] }
                    });
                }
            }
        });
    }
    function cargarTiposEntrada() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_listar_tipos_entrada' },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(tipo => {
                        html += `<div class="flavor-tipo-entrada"><h4>${tipo.nombre}</h4><div class="flavor-tipo-precio">${tipo.precio}€</div><p style="font-size:12px;color:#666;margin:8px 0 0 0;">${tipo.descripcion}</p></div>`;
                    });
                    $('#tipos-entrada-list').html(html || '<p><?php _e('No hay tipos de entrada configurados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
                }
            }
        });
    }
});
</script>
