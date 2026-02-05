<?php
/**
 * Gestión de Voluntarios - Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap flavor-voluntarios-management">
    <h1 class="wp-heading-inline"><?php _e('Gestión de Voluntarios', 'flavor-chat-ia'); ?></h1>
    <button type="button" class="page-title-action" id="btn-nuevo-voluntario"><?php _e('Nuevo Voluntario', 'flavor-chat-ia'); ?></button>
    <hr class="wp-header-end">

    <div class="flavor-filters-bar">
        <div class="flavor-filter-group">
            <label><?php _e('Buscar:', 'flavor-chat-ia'); ?></label>
            <input type="text" id="search-voluntarios" class="flavor-search-input" placeholder="<?php _e('Nombre, habilidades...', 'flavor-chat-ia'); ?>">
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Disponibilidad:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-disponibilidad" class="flavor-select">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="disponible"><?php _e('Disponible', 'flavor-chat-ia'); ?></option>
                <option value="ocupado"><?php _e('Ocupado', 'flavor-chat-ia'); ?></option>
                <option value="inactivo"><?php _e('Inactivo', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Categoría:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-categoria-vol" class="flavor-select">
                <option value=""><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                <option value="compras"><?php _e('Compras', 'flavor-chat-ia'); ?></option>
                <option value="transporte"><?php _e('Transporte', 'flavor-chat-ia'); ?></option>
                <option value="companía"><?php _e('Compañía', 'flavor-chat-ia'); ?></option>
                <option value="tramites"><?php _e('Trámites', 'flavor-chat-ia'); ?></option>
                <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
    </div>

    <div class="flavor-voluntarios-grid" id="voluntarios-list">
        <div class="flavor-loading"><?php _e('Cargando voluntarios...', 'flavor-chat-ia'); ?></div>
    </div>
</div>

<!-- Modal para crear/editar voluntario -->
<div id="modal-voluntario" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2 id="modal-voluntario-title"><?php _e('Nuevo Voluntario', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close"><span class="dashicons dashicons-no"></span></button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-voluntario">
                <input type="hidden" id="voluntario-id" name="id">
                <div class="flavor-form-group">
                    <label for="voluntario-usuario"><?php _e('Usuario', 'flavor-chat-ia'); ?> *</label>
                    <select id="voluntario-usuario" name="usuario_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="flavor-form-group">
                    <label><?php _e('Categorías de ayuda', 'flavor-chat-ia'); ?> *</label>
                    <div class="flavor-checkboxes-grid">
                        <label><input type="checkbox" name="categorias[]" value="compras"> <?php _e('Compras', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="transporte"> <?php _e('Transporte', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="companía"> <?php _e('Compañía', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="tramites"> <?php _e('Trámites', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="tecnologia"> <?php _e('Tecnología', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="otro"> <?php _e('Otro', 'flavor-chat-ia'); ?></label>
                    </div>
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-habilidades"><?php _e('Habilidades específicas', 'flavor-chat-ia'); ?></label>
                    <textarea id="voluntario-habilidades" name="habilidades" rows="3" class="widefat"></textarea>
                </div>
                <div class="flavor-form-group">
                    <label><?php _e('Disponibilidad horaria', 'flavor-chat-ia'); ?></label>
                    <div class="flavor-checkboxes-inline">
                        <label><input type="checkbox" name="dias_disponibles[]" value="1"> <?php _e('L', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="2"> <?php _e('M', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="3"> <?php _e('X', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="4"> <?php _e('J', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="5"> <?php _e('V', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="6"> <?php _e('S', 'flavor-chat-ia'); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="0"> <?php _e('D', 'flavor-chat-ia'); ?></label>
                    </div>
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-max-ayudas"><?php _e('Máximo ayudas simultáneas', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="voluntario-max-ayudas" name="max_ayudas_simultaneas" value="3" min="1" class="widefat">
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="voluntario-estado" name="estado" class="widefat">
                        <option value="disponible"><?php _e('Disponible', 'flavor-chat-ia'); ?></option>
                        <option value="ocupado"><?php _e('Ocupado', 'flavor-chat-ia'); ?></option>
                        <option value="inactivo"><?php _e('Inactivo', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-voluntario"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-voluntario"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<style>
.flavor-voluntarios-management { margin: 20px; }
.flavor-filters-bar { background: #fff; padding: 15px 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; display: flex; gap: 20px; flex-wrap: wrap; }
.flavor-filter-group { display: flex; align-items: center; gap: 10px; }
.flavor-filter-group label { font-weight: 600; margin: 0; }
.flavor-search-input { padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; min-width: 250px; }
.flavor-select { padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; }
.flavor-voluntarios-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.flavor-voluntario-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
.flavor-voluntario-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.flavor-voluntario-avatar { width: 60px; height: 60px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 600; color: #6b7280; }
.flavor-voluntario-info h3 { margin: 0 0 5px 0; font-size: 16px; }
.flavor-voluntario-meta { font-size: 12px; color: #666; }
.flavor-voluntario-badges { display: flex; flex-wrap: wrap; gap: 5px; margin: 10px 0; }
.flavor-badge { padding: 4px 8px; background: #f3f4f6; border-radius: 4px; font-size: 11px; }
.flavor-voluntario-stats { display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; margin-top: 10px; }
.flavor-stat-item { text-align: center; }
.flavor-stat-value { display: block; font-size: 20px; font-weight: 700; color: #2271b1; }
.flavor-stat-label { display: block; font-size: 11px; color: #666; }
.flavor-voluntario-estado { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.flavor-voluntario-estado.disponible { background: #d1fae5; color: #065f46; }
.flavor-voluntario-estado.ocupado { background: #fef3c7; color: #92400e; }
.flavor-voluntario-estado.inactivo { background: #fee2e2; color: #991b1b; }
.flavor-voluntario-actions { display: flex; gap: 8px; margin-top: 10px; }
.flavor-checkboxes-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.flavor-checkboxes-inline { display: flex; gap: 15px; flex-wrap: wrap; }
@media (max-width: 782px) { .flavor-voluntarios-grid { grid-template-columns: 1fr; } .flavor-filters-bar { flex-direction: column; } .flavor-filter-group { width: 100%; } .flavor-search-input { width: 100%; } }
</style>

<script>
jQuery(document).ready(function($) {
    cargarVoluntarios();
    cargarUsuariosSelect();

    $('#btn-nuevo-voluntario').on('click', function() {
        $('#form-voluntario')[0].reset();
        $('#voluntario-id').val('');
        $('#modal-voluntario-title').text('<?php _e('Nuevo Voluntario', 'flavor-chat-ia'); ?>');
        $('#modal-voluntario').fadeIn();
    });

    $('.flavor-modal-close, #btn-cancelar-voluntario').on('click', function() {
        $('#modal-voluntario').fadeOut();
    });

    $('#btn-guardar-voluntario').on('click', function() {
        const formData = $('#form-voluntario').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData + '&action=ayuda_vecinal_guardar_voluntario',
            success: function(response) {
                if (response.success) {
                    $('#modal-voluntario').fadeOut();
                    cargarVoluntarios();
                }
            }
        });
    });

    $('#search-voluntarios, #filtro-disponibilidad, #filtro-categoria-vol').on('change keyup', function() {
        cargarVoluntarios();
    });

    function cargarVoluntarios() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'ayuda_vecinal_listar_voluntarios',
                search: $('#search-voluntarios').val(),
                disponibilidad: $('#filtro-disponibilidad').val(),
                categoria: $('#filtro-categoria-vol').val()
            },
            success: function(response) {
                if (response.success) {
                    renderizarVoluntarios(response.data);
                }
            }
        });
    }

    function renderizarVoluntarios(voluntarios) {
        if (voluntarios.length === 0) {
            $('#voluntarios-list').html('<div class="flavor-empty-state"><p><?php _e('No se encontraron voluntarios', 'flavor-chat-ia'); ?></p></div>');
            return;
        }

        let html = '';
        voluntarios.forEach(voluntario => {
            const categorias = JSON.parse(voluntario.categorias || '[]');
            const badgesHTML = categorias.map(cat => `<span class="flavor-badge">${cat}</span>`).join('');

            html += `
                <div class="flavor-voluntario-card">
                    <div class="flavor-voluntario-header">
                        <div class="flavor-voluntario-avatar">${voluntario.iniciales}</div>
                        <div class="flavor-voluntario-info">
                            <h3>${voluntario.nombre}</h3>
                            <div class="flavor-voluntario-meta">
                                <span class="flavor-voluntario-estado ${voluntario.estado}">${voluntario.estado}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flavor-voluntario-badges">${badgesHTML}</div>
                    ${voluntario.habilidades ? `<p style="font-size: 13px; color: #666; margin-top: 10px;">${voluntario.habilidades}</p>` : ''}
                    <div class="flavor-voluntario-stats">
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-value">${voluntario.ayudas_completadas || 0}</span>
                            <span class="flavor-stat-label"><?php _e('Completadas', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-value">${voluntario.ayudas_activas || 0}</span>
                            <span class="flavor-stat-label"><?php _e('Activas', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-value">${voluntario.valoracion || '-'}</span>
                            <span class="flavor-stat-label"><?php _e('Valoración', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                    <div class="flavor-voluntario-actions">
                        <button class="button btn-editar-voluntario" data-id="${voluntario.id}">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Editar', 'flavor-chat-ia'); ?>
                        </button>
                        <button class="button btn-ver-perfil" data-id="${voluntario.id}">
                            <span class="dashicons dashicons-visibility"></span> <?php _e('Ver Perfil', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            `;
        });
        $('#voluntarios-list').html(html);
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
                    $('#voluntario-usuario').html(options);
                }
            }
        });
    }

    $(document).on('click', '.btn-editar-voluntario', function() {
        const voluntarioId = $(this).data('id');
        // Implementar carga de datos del voluntario
        alert('Editar voluntario #' + voluntarioId);
    });
});
</script>
