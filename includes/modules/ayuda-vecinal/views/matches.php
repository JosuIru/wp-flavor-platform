<?php
/**
 * Matching de Solicitudes y Voluntarios - Ayuda Vecinal
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-matches-management">
    <h1><?php _e('Matching Solicitudes-Voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-grid-two-columns">
        <div class="flavor-card">
            <div class="flavor-card-header"><h2><?php _e('Solicitudes Sin Asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div>
            <div class="flavor-card-body" id="solicitudes-sin-asignar"></div>
        </div>
        <div class="flavor-card">
            <div class="flavor-card-header"><h2><?php _e('Sugerencias de Matching', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></div>
            <div class="flavor-card-body" id="sugerencias-matching"></div>
        </div>
    </div>
</div>
<style>
.flavor-matches-management{margin:20px;}.flavor-grid-two-columns{display:grid;grid-template-columns:1fr 1fr;gap:20px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;}.flavor-card-header{padding:15px 20px;border-bottom:1px solid #ddd;}.flavor-card-header h2{margin:0;font-size:16px;}.flavor-card-body{padding:20px;}.flavor-solicitud-item{padding:12px;border:1px solid #eee;border-radius:8px;margin-bottom:10px;}.flavor-match-suggestion{padding:15px;background:#f0f6fc;border-left:4px solid #2271b1;border-radius:4px;margin-bottom:10px;}.flavor-match-score{display:inline-block;padding:4px 10px;background:#10b981;color:#fff;border-radius:12px;font-size:12px;font-weight:600;margin-left:10px;}.flavor-match-actions{display:flex;gap:8px;margin-top:10px;}
</style>
<script>
jQuery(document).ready(function($) {
    cargarSolicitudesSinAsignar();
    cargarSugerencias();
    function cargarSolicitudesSinAsignar() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'ayuda_vecinal_solicitudes_sin_asignar' },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(sol => {
                        html += `<div class="flavor-solicitud-item">
                            <strong>${sol.titulo}</strong>
                            <p style="font-size:12px;color:#666;margin:5px 0 0 0;">${sol.categoria} · ${sol.fecha}</p>
                            <button class="button button-small btn-buscar-match" data-id="${sol.id}" style="margin-top:8px;">
                                <?php _e('Buscar voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>`;
                    });
                    $('#solicitudes-sin-asignar').html(html || '<p><?php _e('No hay solicitudes sin asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
                }
            }
        });
    }
    function cargarSugerencias() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'ayuda_vecinal_sugerencias_matching' },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(match => {
                        html += `<div class="flavor-match-suggestion">
                            <strong>${match.solicitud_titulo}</strong>
                            <span class="flavor-match-score">${match.score}% Match</span>
                            <p style="font-size:13px;margin:8px 0;"><strong><?php _e('Voluntario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> ${match.voluntario_nombre}</p>
                            <p style="font-size:12px;color:#666;">${match.razon}</p>
                            <div class="flavor-match-actions">
                                <button class="button button-primary button-small btn-asignar-match" data-solicitud="${match.solicitud_id}" data-voluntario="${match.voluntario_id}">
                                    <?php _e('Asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <button class="button button-small"><?php _e('Descartar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            </div>
                        </div>`;
                    });
                    $('#sugerencias-matching').html(html || '<p><?php _e('No hay sugerencias disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>');
                }
            }
        });
    }
    $(document).on('click', '.btn-asignar-match', function() {
        const solicitudId = $(this).data('solicitud');
        const voluntarioId = $(this).data('voluntario');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'ayuda_vecinal_asignar_voluntario',
                solicitud_id: solicitudId,
                voluntario_id: voluntarioId
            },
            success: function(response) {
                if (response.success) {
                    cargarSolicitudesSinAsignar();
                    cargarSugerencias();
                    alert('<?php _e('Voluntario asignado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                }
            }
        });
    });
});
</script>
