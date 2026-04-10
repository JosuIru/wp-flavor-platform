<?php
/**
 * Gestión de Solicitudes de Ayuda Vecinal
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-solicitudes-management">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nueva-solicitud">
        <?php _e('Nueva Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <div class="flavor-filter-group">
            <label><?php _e('Buscar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" id="search-solicitudes" class="flavor-search-input" placeholder="<?php _e('Título, solicitante...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-estado" class="flavor-select">
                <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('asignada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Asignada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('en_proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Categoría:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-categoria" class="flavor-select">
                <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('companía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label>
                <input type="checkbox" id="filtro-urgente" value="1">
                <?php _e('Solo urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </label>
        </div>
    </div>

    <!-- Lista de solicitudes -->
    <div class="flavor-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody id="solicitudes-list">
                <tr>
                    <td colspan="8" class="flavor-loading"><?php _e('Cargando solicitudes...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
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
            <h2 id="modal-solicitud-title"><?php _e('Nueva Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-solicitud">
                <input type="hidden" id="solicitud-id" name="id">

                <div class="flavor-form-group">
                    <label for="solicitud-titulo"><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" id="solicitud-titulo" name="titulo" required class="widefat">
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea id="solicitud-descripcion" name="descripcion" rows="4" required class="widefat"></textarea>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="solicitud-categoria"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <select id="solicitud-categoria" name="categoria" required class="widefat">
                            <option value="<?php echo esc_attr__('compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('companía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-form-group">
                        <label for="solicitud-fecha"><?php _e('Fecha necesaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="date" id="solicitud-fecha" name="fecha_necesaria" required class="widefat">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-solicitante"><?php _e('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <select id="solicitud-solicitante" name="solicitante_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label>
                        <input type="checkbox" id="solicitud-urgente" name="urgente" value="1">
                        <?php _e('Marcar como urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </div>

                <div class="flavor-form-group">
                    <label for="solicitud-estado"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="solicitud-estado" name="estado" class="widefat">
                        <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('asignada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Asignada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('en_proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group" id="select-voluntario" style="display: none;">
                    <label for="solicitud-voluntario"><?php _e('Asignar voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="solicitud-voluntario" name="voluntario_id" class="widefat">
                        <option value=""><?php _e('Sin asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-solicitud"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-solicitud"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
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
        $('#modal-solicitud-title').text('<?php _e('Nueva Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
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
            $('#solicitudes-list').html('<tr><td colspan="8" style="text-align: center;"><?php _e('No se encontraron solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>');
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
                    let options = '<option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>';
                    response.data.forEach(usuario => {
                        options += `<option value="<?php echo esc_attr__('${usuario.id}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">${usuario.nombre}</option>`;
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
                    let options = '<option value=""><?php _e('Sin asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>';
                    response.data.forEach(voluntario => {
                        options += `<option value="<?php echo esc_attr__('${voluntario.id}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">${voluntario.nombre}</option>`;
                    });
                    $('#solicitud-voluntario').html(options);
                }
            }
        });
    }
});
</script>

<style>
/* Layout */
.flavor-solicitudes-management { margin: 20px; max-width: 1400px; }

/* Filtros */
.flavor-filters-bar { background: #fff; padding: 15px 20px; margin: 20px 0; border: 1px solid #c3c4c7; border-radius: 8px; display: flex; gap: 20px; flex-wrap: wrap; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
.flavor-filter-group { display: flex; align-items: center; gap: 10px; }
.flavor-filter-group label { font-weight: 600; margin: 0; color: #1d2327; }
.flavor-search-input { padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; min-width: 250px; font-size: 14px; }
.flavor-search-input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.flavor-select { padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }

/* Cards */
.flavor-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 1px rgba(0,0,0,.04); }

/* Badges */
.flavor-estado-badge { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.flavor-estado-badge.pendiente { background: #fef3c7; color: #92400e; }
.flavor-estado-badge.asignada { background: #dbeafe; color: #1e40af; }
.flavor-estado-badge.en_proceso { background: #e0e7ff; color: #4338ca; }
.flavor-estado-badge.completada { background: #d1fae5; color: #065f46; }
.flavor-estado-badge.cancelada { background: #fee2e2; color: #991b1b; }
.flavor-urgent-badge { padding: 2px 8px; background: #ef4444; color: #fff; font-size: 10px; border-radius: 4px; font-weight: 600; margin-left: 5px; }

/* Modal */
.flavor-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; }
.flavor-modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); }
.flavor-modal-content { position: relative; background: #fff; border-radius: 8px; width: 90%; max-width: 600px; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); display: flex; flex-direction: column; }
.flavor-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #dcdcde; background: #f6f7f7; }
.flavor-modal-header h2 { margin: 0; font-size: 18px; font-weight: 600; color: #1d2327; }
.flavor-modal-close { background: none; border: none; padding: 5px; cursor: pointer; color: #646970; transition: color 0.2s; }
.flavor-modal-close:hover { color: #d63638; }
.flavor-modal-close .dashicons { font-size: 20px; width: 20px; height: 20px; }
.flavor-modal-body { padding: 24px; overflow-y: auto; flex: 1; }
.flavor-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #dcdcde; background: #f6f7f7; }

/* Formularios */
.flavor-form-group { margin-bottom: 20px; }
.flavor-form-group > label { display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 13px; }
.flavor-form-group .widefat { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }
.flavor-form-group .widefat:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.flavor-form-group textarea.widefat { resize: vertical; min-height: 100px; }
.flavor-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* Responsive */
@media (max-width: 782px) {
    .flavor-filters-bar { flex-direction: column; }
    .flavor-filter-group { width: 100%; }
    .flavor-search-input { width: 100%; min-width: auto; }
    .flavor-form-row { grid-template-columns: 1fr; }
    .flavor-modal-content { width: 95%; max-height: 90vh; }
}
</style>
