<?php
/** Gestión de Eventos * @package FlavorChatIA */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap flavor-eventos-management">
    <h1 class="wp-heading-inline"><?php _e('Gestión de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <button type="button" class="page-title-action" id="btn-nuevo-evento"><?php _e('Nuevo Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    <hr class="wp-header-end">
    <div class="flavor-filters-bar">
        <input type="text" id="search-eventos" placeholder="<?php _e('Buscar eventos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <select id="filtro-categoria"><option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option></select>
        <select id="filtro-estado"><option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option></select>
    </div>
    <div class="flavor-card"><table class="wp-list-table widefat fixed striped"><thead><tr><th><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Asistentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th></tr></thead><tbody id="eventos-list"></tbody></table></div>
</div>
<div id="modal-evento" class="flavor-modal" style="display:none;"><div class="flavor-modal-overlay"></div><div class="flavor-modal-content"><div class="flavor-modal-header"><h2 id="modal-evento-title"><?php _e('Nuevo Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2><button type="button" class="flavor-modal-close"><span class="dashicons dashicons-no"></span></button></div><div class="flavor-modal-body"><form id="form-evento"><input type="hidden" id="evento-id" name="id"><div class="flavor-form-group"><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label><input type="text" id="evento-titulo" name="titulo" required class="widefat"></div><div class="flavor-form-group"><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label><div class="flavor-ai-textarea-wrapper" data-ai-enabled="true"><textarea id="evento-descripcion" name="descripcion" rows="4" class="widefat flavor-ai-content-target" data-content-type="evento" data-context="<?php echo esc_attr__('descripción de evento comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea></div></div><div class="flavor-form-row"><div class="flavor-form-group"><label><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label><input type="date" id="evento-fecha" name="fecha" required class="widefat"></div><div class="flavor-form-group"><label><?php _e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label><input type="time" id="evento-hora" name="hora" required class="widefat"></div></div><div class="flavor-form-group"><label><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label><input type="text" id="evento-ubicacion" name="ubicacion" class="widefat"></div><div class="flavor-form-row"><div class="flavor-form-group"><label><?php _e('Capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label><input type="number" id="evento-capacidad" name="capacidad" min="1" class="widefat"></div><div class="flavor-form-group"><label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label><select id="evento-categoria" name="categoria" class="widefat"><option value="<?php echo esc_attr__('cultura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('deporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Deporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('formacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option><option value="<?php echo esc_attr__('otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option></select></div></div></form></div><div class="flavor-modal-footer"><button type="button" class="button" id="btn-cancelar-evento"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button><button type="button" class="button button-primary" id="btn-guardar-evento"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button></div></div></div>
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
                    $('#eventos-list').html(html || '<tr><td colspan="7" style="text-align:center;"><?php _e('No se encontraron eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>');
                }
            }
        });
    }
    $('#search-eventos, #filtro-categoria, #filtro-estado').on('change keyup', cargarEventos);
});
</script>
