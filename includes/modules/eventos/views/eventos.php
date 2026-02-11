<?php
/** Gestión de Eventos * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-eventos-management">
    <h1 class="wp-heading-inline"><?php _e('Gestión de Eventos', 'flavor-chat-ia'); ?></h1>
    <button type="button" class="page-title-action" id="btn-nuevo-evento"><?php _e('Nuevo Evento', 'flavor-chat-ia'); ?></button>
    <hr class="wp-header-end">
    <div class="flavor-filters-bar">
        <input type="text" id="search-eventos" placeholder="<?php _e('Buscar eventos...', 'flavor-chat-ia'); ?>">
        <select id="filtro-categoria"><option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option></select>
        <select id="filtro-estado"><option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('publicado', 'flavor-chat-ia'); ?>"><?php _e('Publicado', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('borrador', 'flavor-chat-ia'); ?>"><?php _e('Borrador', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('cancelado', 'flavor-chat-ia'); ?>"><?php _e('Cancelado', 'flavor-chat-ia'); ?></option></select>
    </div>
    <div class="flavor-card"><table class="wp-list-table widefat fixed striped"><thead><tr><th><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th><th><?php _e('Título', 'flavor-chat-ia'); ?></th><th><?php _e('Fecha', 'flavor-chat-ia'); ?></th><th><?php _e('Ubicación', 'flavor-chat-ia'); ?></th><th><?php _e('Asistentes', 'flavor-chat-ia'); ?></th><th><?php _e('Estado', 'flavor-chat-ia'); ?></th><th><?php _e('Acciones', 'flavor-chat-ia'); ?></th></tr></thead><tbody id="eventos-list"></tbody></table></div>
</div>
<div id="modal-evento" class="flavor-modal" style="display:none;"><div class="flavor-modal-overlay"></div><div class="flavor-modal-content"><div class="flavor-modal-header"><h2 id="modal-evento-title"><?php _e('Nuevo Evento', 'flavor-chat-ia'); ?></h2><button type="button" class="flavor-modal-close"><span class="dashicons dashicons-no"></span></button></div><div class="flavor-modal-body"><form id="form-evento"><input type="hidden" id="evento-id" name="id"><div class="flavor-form-group"><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label><input type="text" id="evento-titulo" name="titulo" required class="widefat"></div><div class="flavor-form-group"><label><?php _e('Descripción', 'flavor-chat-ia'); ?></label><textarea id="evento-descripcion" name="descripcion" rows="4" class="widefat"></textarea></div><div class="flavor-form-row"><div class="flavor-form-group"><label><?php _e('Fecha', 'flavor-chat-ia'); ?> *</label><input type="date" id="evento-fecha" name="fecha" required class="widefat"></div><div class="flavor-form-group"><label><?php _e('Hora', 'flavor-chat-ia'); ?> *</label><input type="time" id="evento-hora" name="hora" required class="widefat"></div></div><div class="flavor-form-group"><label><?php _e('Ubicación', 'flavor-chat-ia'); ?></label><input type="text" id="evento-ubicacion" name="ubicacion" class="widefat"></div><div class="flavor-form-row"><div class="flavor-form-group"><label><?php _e('Capacidad', 'flavor-chat-ia'); ?></label><input type="number" id="evento-capacidad" name="capacidad" min="1" class="widefat"></div><div class="flavor-form-group"><label><?php _e('Categoría', 'flavor-chat-ia'); ?></label><select id="evento-categoria" name="categoria" class="widefat"><option value="<?php echo esc_attr__('cultura', 'flavor-chat-ia'); ?>"><?php _e('Cultura', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('deporte', 'flavor-chat-ia'); ?>"><?php _e('Deporte', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('formacion', 'flavor-chat-ia'); ?>"><?php _e('Formación', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('social', 'flavor-chat-ia'); ?>"><?php _e('Social', 'flavor-chat-ia'); ?></option><option value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>"><?php _e('Otro', 'flavor-chat-ia'); ?></option></select></div></div></form></div><div class="flavor-modal-footer"><button type="button" class="button" id="btn-cancelar-evento"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button><button type="button" class="button button-primary" id="btn-guardar-evento"><?php _e('Guardar', 'flavor-chat-ia'); ?></button></div></div></div>
<style>
.flavor-eventos-management{margin:20px;}.flavor-filters-bar{background:#fff;padding:15px 20px;margin:20px 0;border:1px solid #ddd;border-radius:8px;display:flex;gap:15px;}.flavor-filters-bar input,.flavor-filters-bar select{padding:6px 12px;border:1px solid #ddd;border-radius:4px;}.flavor-card{background:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden;}.flavor-form-row{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
</style>
<script>
jQuery(document).ready(function($) {
    cargarEventos();
    $('#btn-nuevo-evento').on('click', function() {
        $('#form-evento')[0].reset();
        $('#evento-id').val('');
        $('#modal-evento').fadeIn();
    });
    $('.flavor-modal-close, #btn-cancelar-evento').on('click', function() { $('#modal-evento').fadeOut(); });
    $('#btn-guardar-evento').on('click', function() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $('#form-evento').serialize() + '&action=eventos_guardar_evento',
            success: function(response) {
                if (response.success) {
                    $('#modal-evento').fadeOut();
                    cargarEventos();
                }
            }
        });
    });
    function cargarEventos() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'eventos_listar_eventos', search: $('#search-eventos').val(), categoria: $('#filtro-categoria').val(), estado: $('#filtro-estado').val() },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(evento => {
                        html += `<tr><td>#${evento.id}</td><td>${evento.titulo}</td><td>${evento.fecha}</td><td>${evento.ubicacion || '-'}</td><td>${evento.asistentes_confirmados}/${evento.capacidad || '∞'}</td><td><span class="flavor-badge ${evento.estado}">${evento.estado}</span></td><td><button class="button button-small btn-editar-evento" data-id="${evento.id}"><span class="dashicons dashicons-edit"></span></button></td></tr>`;
                    });
                    $('#eventos-list').html(html || '<tr><td colspan="7" style="text-align:center;"><?php _e('No se encontraron eventos', 'flavor-chat-ia'); ?></td></tr>');
                }
            }
        });
    }
    $('#search-eventos, #filtro-categoria, #filtro-estado').on('change keyup', cargarEventos);
});
</script>
