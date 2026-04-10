<?php
/**
 * Gestión de Voluntarios - Ayuda Vecinal
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap flavor-voluntarios-management">
    <h1 class="wp-heading-inline"><?php _e('Gestión de Voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <button type="button" class="page-title-action" id="btn-nuevo-voluntario"><?php _e('Nuevo Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    <hr class="wp-header-end">

    <div class="flavor-filters-bar">
        <div class="flavor-filter-group">
            <label><?php _e('Buscar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" id="search-voluntarios" class="flavor-search-input" placeholder="<?php _e('Nombre, habilidades...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Disponibilidad:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-disponibilidad" class="flavor-select">
                <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Categoría:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-categoria-vol" class="flavor-select">
                <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('companía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="<?php echo esc_attr__('tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
    </div>

    <div class="flavor-voluntarios-grid" id="voluntarios-list">
        <div class="flavor-loading"><?php _e('Cargando voluntarios...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>

<!-- Modal para crear/editar voluntario -->
<div id="modal-voluntario" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2 id="modal-voluntario-title"><?php _e('Nuevo Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <button type="button" class="flavor-modal-close"><span class="dashicons dashicons-no"></span></button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-voluntario">
                <input type="hidden" id="voluntario-id" name="id">
                <div class="flavor-form-group">
                    <label for="voluntario-usuario"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <select id="voluntario-usuario" name="usuario_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="flavor-form-group">
                    <label><?php _e('Categorías de ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <div class="flavor-checkboxes-grid">
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Compras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('companía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Compañía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="categorias[]" value="<?php echo esc_attr__('otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"> <?php _e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </div>
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-habilidades"><?php _e('Habilidades específicas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="voluntario-habilidades" name="habilidades" rows="3" class="widefat"></textarea>
                </div>
                <div class="flavor-form-group">
                    <label><?php _e('Disponibilidad horaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="flavor-checkboxes-inline">
                        <label><input type="checkbox" name="dias_disponibles[]" value="1"> <?php _e('L', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="2"> <?php _e('M', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="3"> <?php _e('X', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="4"> <?php _e('J', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="5"> <?php _e('V', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="6"> <?php _e('S', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <label><input type="checkbox" name="dias_disponibles[]" value="0"> <?php _e('D', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </div>
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-max-ayudas"><?php _e('Máximo ayudas simultáneas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="voluntario-max-ayudas" name="max_ayudas_simultaneas" value="3" min="1" class="widefat">
                </div>
                <div class="flavor-form-group">
                    <label for="voluntario-estado"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="voluntario-estado" name="estado" class="widefat">
                        <option value="<?php echo esc_attr__('disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php _e('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-voluntario"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-voluntario"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </div>
</div>

<style>
/* Layout Principal */
.flavor-voluntarios-management { margin: 20px; max-width: 1400px; }

/* Barra de Filtros */
.flavor-filters-bar { background: #fff; padding: 15px 20px; margin: 20px 0; border: 1px solid #c3c4c7; border-radius: 8px; display: flex; gap: 20px; flex-wrap: wrap; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
.flavor-filter-group { display: flex; align-items: center; gap: 10px; }
.flavor-filter-group label { font-weight: 600; margin: 0; color: #1d2327; }
.flavor-search-input { padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; min-width: 250px; font-size: 14px; }
.flavor-search-input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.flavor-select { padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }

/* Grid de Voluntarios */
.flavor-voluntarios-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
.flavor-voluntario-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); transition: box-shadow 0.2s, transform 0.2s; }
.flavor-voluntario-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,.1); transform: translateY(-2px); }
.flavor-voluntario-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.flavor-voluntario-avatar { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 600; color: #fff; text-transform: uppercase; }
.flavor-voluntario-info h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: 600; color: #1d2327; }
.flavor-voluntario-meta { font-size: 12px; color: #646970; }
.flavor-voluntario-badges { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0; }
.flavor-badge { padding: 4px 10px; background: #f0f0f1; border-radius: 12px; font-size: 11px; color: #50575e; font-weight: 500; }
.flavor-voluntario-stats { display: flex; justify-content: space-around; padding: 15px 0; border-top: 1px solid #f0f0f1; margin-top: 15px; }
.flavor-stat-item { text-align: center; }
.flavor-stat-value { display: block; font-size: 22px; font-weight: 700; color: #2271b1; }
.flavor-stat-label { display: block; font-size: 11px; color: #646970; margin-top: 2px; }
.flavor-voluntario-estado { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.flavor-voluntario-estado.disponible { background: #d1fae5; color: #065f46; }
.flavor-voluntario-estado.ocupado { background: #fef3c7; color: #92400e; }
.flavor-voluntario-estado.inactivo { background: #fee2e2; color: #991b1b; }
.flavor-voluntario-actions { display: flex; gap: 8px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1; }
.flavor-voluntario-actions .button { flex: 1; justify-content: center; display: inline-flex; align-items: center; gap: 5px; }

/* Modal */
.flavor-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; }
.flavor-modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); }
.flavor-modal-content { position: relative; background: #fff; border-radius: 8px; width: 90%; max-width: 550px; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); display: flex; flex-direction: column; }
.flavor-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #dcdcde; background: #f6f7f7; }
.flavor-modal-header h2 { margin: 0; font-size: 18px; font-weight: 600; color: #1d2327; }
.flavor-modal-close { background: none; border: none; padding: 5px; cursor: pointer; color: #646970; transition: color 0.2s; }
.flavor-modal-close:hover { color: #d63638; }
.flavor-modal-close .dashicons { font-size: 20px; width: 20px; height: 20px; }
.flavor-modal-body { padding: 24px; overflow-y: auto; flex: 1; }
.flavor-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #dcdcde; background: #f6f7f7; }

/* Formularios */
.flavor-form-group { margin-bottom: 20px; }
.flavor-form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 13px; }
.flavor-form-group .widefat { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }
.flavor-form-group .widefat:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.flavor-checkboxes-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.flavor-checkboxes-grid label { display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer; padding: 6px 10px; background: #f6f7f7; border-radius: 4px; transition: background 0.2s; }
.flavor-checkboxes-grid label:hover { background: #f0f0f1; }
.flavor-checkboxes-inline { display: flex; gap: 12px; flex-wrap: wrap; }
.flavor-checkboxes-inline label { display: flex; align-items: center; gap: 4px; font-weight: normal; cursor: pointer; padding: 6px 12px; background: #f6f7f7; border-radius: 4px; }

/* Estados vacíos y carga */
.flavor-loading { text-align: center; padding: 60px 20px; color: #646970; font-size: 14px; }
.flavor-empty-state { text-align: center; padding: 60px 20px; background: #fff; border: 1px dashed #c3c4c7; border-radius: 8px; }
.flavor-empty-state p { color: #646970; font-size: 15px; margin: 0; }

/* Responsive */
@media (max-width: 782px) {
    .flavor-voluntarios-grid { grid-template-columns: 1fr; }
    .flavor-filters-bar { flex-direction: column; }
    .flavor-filter-group { width: 100%; }
    .flavor-search-input { width: 100%; min-width: auto; }
    .flavor-modal-content { width: 95%; max-height: 90vh; }
    .flavor-checkboxes-grid { grid-template-columns: 1fr; }
}
</style>

<script>
jQuery(document).ready(function($) {
    cargarVoluntarios();
    cargarUsuariosSelect();

    $('#btn-nuevo-voluntario').on('click', function() {
        $('#form-voluntario')[0].reset();
        $('#voluntario-id').val('');
        $('#modal-voluntario-title').text('<?php _e('Nuevo Voluntario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
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
            $('#voluntarios-list').html('<div class="flavor-empty-state"><p><?php _e('No se encontraron voluntarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p></div>');
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
                            <span class="flavor-stat-label"><?php _e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-value">${voluntario.ayudas_activas || 0}</span>
                            <span class="flavor-stat-label"><?php _e('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-stat-item">
                            <span class="flavor-stat-value">${voluntario.valoracion || '-'}</span>
                            <span class="flavor-stat-label"><?php _e('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                    <div class="flavor-voluntario-actions">
                        <button class="button btn-editar-voluntario" data-id="${voluntario.id}">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button class="button btn-ver-perfil" data-id="${voluntario.id}">
                            <span class="dashicons dashicons-visibility"></span> <?php _e('Ver Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                    let options = '<option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>';
                    response.data.forEach(usuario => {
                        options += `<option value="<?php echo esc_attr__('${usuario.id}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">${usuario.nombre}</option>`;
                    });
                    $('#voluntario-usuario').html(options);
                }
            }
        });
    }

    $(document).on('click', '.btn-editar-voluntario', function() {
        const voluntarioId = $(this).data('id');
        window.location.href = '<?php echo admin_url('admin.php?page=flavor-ayuda-vecinal&tab=voluntarios&editar='); ?>' + voluntarioId;
    });
});
</script>
