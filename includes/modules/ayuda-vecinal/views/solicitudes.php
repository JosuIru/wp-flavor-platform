<?php
/**
 * Gestión de Solicitudes de Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-solicitudes-management">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Solicitudes', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nueva-solicitud">
        <?php _e('Nueva Solicitud', 'flavor-chat-ia'); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <div class="flavor-filter-group">
            <label><?php _e('Buscar:', 'flavor-chat-ia'); ?></label>
            <input type="text" id="search-solicitudes" class="flavor-search-input" placeholder="<?php _e('Título, solicitante...', 'flavor-chat-ia'); ?>">
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-estado" class="flavor-select">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                <option value="asignada"><?php _e('Asignada', 'flavor-chat-ia'); ?></option>
                <option value="en_proceso"><?php _e('En proceso', 'flavor-chat-ia'); ?></option>
                <option value="completada"><?php _e('Completada', 'flavor-chat-ia'); ?></option>
                <option value="cancelada"><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Categoría:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-categoria" class="flavor-select">
                <option value=""><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                <option value="compras"><?php _e('Compras', 'flavor-chat-ia'); ?></option>
                <option value="transporte"><?php _e('Transporte', 'flavor-chat-ia'); ?></option>
                <option value="companía"><?php _e('Compañía', 'flavor-chat-ia'); ?></option>
                <option value="tramites"><?php _e('Trámites', 'flavor-chat-ia'); ?></option>
                <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
                <option value="otro"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label>
                <input type="checkbox" id="filtro-urgente" value="1">
                <?php _e('Solo urgentes', 'flavor-chat-ia'); ?>
            </label>
        </div>
    </div>

    <!-- Lista de solicitudes -->
    <div class="flavor-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Solicitante', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Categoría', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Voluntario', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody id="solicitudes-list">
                <tr>
                    <td colspan="8" class="flavor-loading"><?php _e('Cargando solicitudes...', 'flavor-chat-ia'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para crear/editar solicitud -->
<div id="modal-solicitud" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2 id="modal-solicitud-title"><?php _e('Nueva Solicitud', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-solicitud">
                <input type="hidden" id="solicitud-id" name="id">

                <div class="flavor-form-group">
                    <label for="solicitud-titulo"><?php _e('Título', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="solicitud-titulo" name="titulo" required class="widefat">
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?> *</label>
                    <textarea id="solicitud-descripcion" name="descripcion" rows="4" required class="widefat"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="solicitud-categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?> *</label>
                        <select id="solicitud-categoria" name="categoria" required class="widefat">
                            <option value="compras"><?php _e('Compras', 'flavor-chat-ia'); ?></option>
                            <option value="transporte"><?php _e('Transporte', 'flavor-chat-ia'); ?></option>
                            <option value="companía"><?php _e('Compañía', 'flavor-chat-ia'); ?></option>
                            <option value="tramites"><?php _e('Trámites', 'flavor-chat-ia'); ?></option>
                            <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
                            <option value="otro"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-form-group">
                        <label for="solicitud-fecha"><?php _e('Fecha necesaria', 'flavor-chat-ia'); ?> *</label>
                        <input type="date" id="solicitud-fecha" name="fecha_necesaria" required class="widefat">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-solicitante"><?php _e('Solicitante', 'flavor-chat-ia'); ?> *</label>
                    <select id="solicitud-solicitante" name="solicitante_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label>
                        <input type="checkbox" id="solicitud-urgente" name="urgente" value="1">
                        <?php _e('Marcar como urgente', 'flavor-chat-ia'); ?>
                    </label>
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="solicitud-estado" name="estado" class="widefat">
                        <option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                        <option value="asignada"><?php _e('Asignada', 'flavor-chat-ia'); ?></option>
                        <option value="en_proceso"><?php _e('En proceso', 'flavor-chat-ia'); ?></option>
                        <option value="completada"><?php _e('Completada', 'flavor-chat-ia'); ?></option>
                        <option value="cancelada"><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group" id="select-voluntario" style="display: none;">
                    <label for="solicitud-voluntario"><?php _e('Asignar voluntario', 'flavor-chat-ia'); ?></label>
                    <select id="solicitud-voluntario" name="voluntario_id" class="widefat">
                        <option value=""><?php _e('Sin asignar', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-solicitud"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-solicitud"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    cargarSolicitudes();
    cargarUsuariosSelect();
    cargarVoluntariosSelect();

    $('#btn-nueva-solicitud').on('click', function() {
        $('#form-solicitud')[0].reset();
        $('#solicitud-id').val('');
        $('#modal-solicitud-title').text('<?php _e('Nueva Solicitud', 'flavor-chat-ia'); ?>');
        $('#modal-solicitud').fadeIn();
    });

    $('.flavor-modal-close, #btn-cancelar-solicitud').on('click', function() {
        $('#modal-solicitud').fadeOut();
    });

    $('#solicitud-estado').on('change', function() {
        if (['asignada', 'en_proceso'].includes($(this).val())) {
            $('#select-voluntario').show();
        } else {
            $('#select-voluntario').hide();
        }
    });

    $('#btn-guardar-solicitud').on('click', function() {
        const formData = $('#form-solicitud').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData + '&action=ayuda_vecinal_guardar_solicitud',
            success: function(response) {
                if (response.success) {
                    $('#modal-solicitud').fadeOut();
                    cargarSolicitudes();
                }
            }
        });
    });

    $('#search-solicitudes, #filtro-estado, #filtro-categoria, #filtro-urgente').on('change keyup', function() {
        cargarSolicitudes();
    });

    function cargarSolicitudes() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'ayuda_vecinal_listar_solicitudes',
                search: $('#search-solicitudes').val(),
                estado: $('#filtro-estado').val(),
                categoria: $('#filtro-categoria').val(),
                urgente: $('#filtro-urgente').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    renderizarSolicitudes(response.data);
                }
            }
        });
    }

    function renderizarSolicitudes(solicitudes) {
        if (solicitudes.length === 0) {
            $('#solicitudes-list').html('<tr><td colspan="8" style="text-align: center;"><?php _e('No se encontraron solicitudes', 'flavor-chat-ia'); ?></td></tr>');
            return;
        }

        let html = '';
        solicitudes.forEach(solicitud => {
            const urgenteBadge = solicitud.urgente ? '<span class="flavor-urgent-badge">¡URGENTE!</span>' : '';
            html += `
                <tr>
                    <td>#${solicitud.id}</td>
                    <td>${solicitud.titulo} ${urgenteBadge}</td>
                    <td>${solicitud.solicitante_nombre}</td>
                    <td>${solicitud.categoria}</td>
                    <td>${solicitud.fecha_creacion}</td>
                    <td><span class="flavor-estado-badge ${solicitud.estado}">${solicitud.estado}</span></td>
                    <td>${solicitud.voluntario_nombre || '-'}</td>
                    <td>
                        <button class="button button-small btn-ver-solicitud" data-id="${solicitud.id}">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="button button-small btn-editar-solicitud" data-id="${solicitud.id}">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#solicitudes-list').html(html);
    }

    function cargarUsuariosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'ayuda_vecinal_listar_usuarios' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>';
                    response.data.forEach(usuario => {
                        options += `<option value="${usuario.id}">${usuario.nombre}</option>`;
                    });
                    $('#solicitud-solicitante').html(options);
                }
            }
        });
    }

    function cargarVoluntariosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'ayuda_vecinal_listar_voluntarios' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Sin asignar', 'flavor-chat-ia'); ?></option>';
                    response.data.forEach(voluntario => {
                        options += `<option value="${voluntario.id}">${voluntario.nombre}</option>`;
                    });
                    $('#solicitud-voluntario').html(options);
                }
            }
        });
    }
});
</script>

<style>
.flavor-solicitudes-management { margin: 20px; }
.flavor-filters-bar { background: #fff; padding: 15px 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; display: flex; gap: 20px; flex-wrap: wrap; }
.flavor-filter-group { display: flex; align-items: center; gap: 10px; }
.flavor-filter-group label { font-weight: 600; margin: 0; }
.flavor-search-input { padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; min-width: 250px; }
.flavor-select { padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; }
.flavor-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
.flavor-estado-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.flavor-estado-badge.pendiente { background: #fef3c7; color: #92400e; }
.flavor-estado-badge.asignada { background: #dbeafe; color: #1e40af; }
.flavor-estado-badge.en_proceso { background: #e0e7ff; color: #4338ca; }
.flavor-estado-badge.completada { background: #d1fae5; color: #065f46; }
.flavor-estado-badge.cancelada { background: #fee2e2; color: #991b1b; }
.flavor-urgent-badge { padding: 2px 6px; background: #ef4444; color: #fff; font-size: 10px; border-radius: 4px; font-weight: 600; margin-left: 5px; }
.flavor-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
@media (max-width: 782px) { .flavor-filters-bar { flex-direction: column; } .flavor-filter-group { width: 100%; } .flavor-search-input { width: 100%; } .flavor-form-row { grid-template-columns: 1fr; } }
</style>
