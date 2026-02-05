<?php
/** Gestión de Asistentes * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-asistentes-management">
    <h1><?php _e('Gestión de Asistentes', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">
    <div class="flavor-filters-bar">
        <input type="text" id="search-asistentes" placeholder="<?php _e('Buscar asistentes...', 'flavor-chat-ia'); ?>">
        <select id="filtro-evento-asistentes"><option value=""><?php _e('Todos los eventos', 'flavor-chat-ia'); ?></option></select>
        <select id="filtro-estado-asistente"><option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option><option value="confirmado"><?php _e('Confirmado', 'flavor-chat-ia'); ?></option><option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option><option value="cancelado"><?php _e('Cancelado', 'flavor-chat-ia'); ?></option></select>
        <button id="btn-exportar-asistentes" class="button"><?php _e('Exportar', 'flavor-chat-ia'); ?></button>
    </div>
    <div class="flavor-card"><table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th><?php _e('Nombre', 'flavor-chat-ia'); ?></th><th><?php _e('Email', 'flavor-chat-ia'); ?></th><th><?php _e('Evento', 'flavor-chat-ia'); ?></th><th><?php _e('Fecha Inscripción', 'flavor-chat-ia'); ?></th><th><?php _e('Estado', 'flavor-chat-ia'); ?></th><th><?php _e('Check-in', 'flavor-chat-ia'); ?></th><th><?php _e('Acciones', 'flavor-chat-ia'); ?></th></tr></thead><tbody id="asistentes-list"></tbody></table></div>
</div>
<style>
.flavor-asistentes-management{margin:20px;}.flavor-filters-bar{background:#fff;padding:15px 20px;margin:20px 0;border:1px solid #ddd;border-radius:8px;display:flex;gap:15px;flex-wrap:wrap;}.flavor-filters-bar input,.flavor-filters-bar select{padding:6px 12px;border:1px solid #ddd;border-radius:4px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden;}.flavor-badge{padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;}.flavor-badge.confirmado{background:#d1fae5;color:#065f46;}.flavor-badge.pendiente{background:#fef3c7;color:#92400e;}.flavor-badge.cancelado{background:#fee2e2;color:#991b1b;}
</style>
<script>
jQuery(document).ready(function($) {
    cargarAsistentes();
    cargarEventosSelect();
    function cargarAsistentes() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_listar_asistentes', search: $('#search-asistentes').val(), evento_id: $('#filtro-evento-asistentes').val(), estado: $('#filtro-estado-asistente').val() },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(asistente => {
                        const checkinIcon = asistente.checkin ? '<span class="dashicons dashicons-yes" style="color:#10b981;"></span>' : '<span class="dashicons dashicons-minus" style="color:#ccc;"></span>';
                        html += `<tr><td>#${asistente.id}</td><td>${asistente.nombre}</td><td>${asistente.email}</td><td>${asistente.evento_titulo}</td><td>${asistente.fecha_inscripcion}</td><td><span class="flavor-badge ${asistente.estado}">${asistente.estado}</span></td><td>${checkinIcon}</td><td><button class="button button-small btn-checkin" data-id="${asistente.id}" ${asistente.checkin ? 'disabled' : ''}><span class="dashicons dashicons-yes"></span></button></td></tr>`;
                    });
                    $('#asistentes-list').html(html || '<tr><td colspan="8" style="text-align:center;"><?php _e('No se encontraron asistentes', 'flavor-chat-ia'); ?></td></tr>');
                }
            }
        });
    }
    function cargarEventosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_listar_eventos' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Todos los eventos', 'flavor-chat-ia'); ?></option>';
                    response.data.forEach(evento => {
                        options += `<option value="${evento.id}">${evento.titulo}</option>`;
                    });
                    $('#filtro-evento-asistentes').html(options);
                }
            }
        });
    }
    $(document).on('click', '.btn-checkin', function() {
        const asistenteId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_hacer_checkin', asistente_id: asistenteId },
            success: function(response) {
                if (response.success) {
                    cargarAsistentes();
                }
            }
        });
    });
    $('#search-asistentes, #filtro-evento-asistentes, #filtro-estado-asistente').on('change keyup', cargarAsistentes);
    $('#btn-exportar-asistentes').on('click', function() {
        window.location.href = ajaxurl + '?action=eventos_exportar_asistentes&evento_id=' + $('#filtro-evento-asistentes').val();
    });
});
</script>
