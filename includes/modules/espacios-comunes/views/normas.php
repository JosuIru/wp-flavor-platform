<?php
/**
 * Gestión de Normas y Políticas de Espacios Comunes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-normas-management">
    <h1 class="wp-heading-inline">
        <?php _e('Normas y Políticas', 'flavor-platform'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nueva-norma">
        <?php _e('Nueva Norma', 'flavor-platform'); ?>
    </button>
    <hr class="wp-header-end">

    <div class="flavor-grid-two-columns">
        <!-- Normas generales -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Normas Generales', 'flavor-platform'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <div id="normas-generales-list">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>

        <!-- Políticas de cancelación -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Políticas de Cancelación', 'flavor-platform'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <form id="form-politicas-cancelacion">
                    <div class="flavor-form-group">
                        <label for="plazo-cancelacion"><?php _e('Plazo mínimo para cancelar (horas)', 'flavor-platform'); ?></label>
                        <input type="number" id="plazo-cancelacion" name="plazo_cancelacion" min="0" class="widefat">
                    </div>

                    <div class="flavor-form-group">
                        <label for="cancelaciones-permitidas"><?php _e('Cancelaciones permitidas por mes', 'flavor-platform'); ?></label>
                        <input type="number" id="cancelaciones-permitidas" name="cancelaciones_permitidas" min="0" class="widefat">
                    </div>

                    <div class="flavor-form-group">
                        <label>
                            <input type="checkbox" id="penalizacion-activa" name="penalizacion_activa" value="1">
                            <?php _e('Penalizar cancelaciones tardías', 'flavor-platform'); ?>
                        </label>
                    </div>

                    <div class="flavor-form-group" id="penalizacion-config" style="display: none;">
                        <label for="penalizacion-duracion"><?php _e('Duración de la penalización (días)', 'flavor-platform'); ?></label>
                        <input type="number" id="penalizacion-duracion" name="penalizacion_duracion" min="1" class="widefat">
                    </div>

                    <button type="submit" class="button button-primary"><?php _e('Guardar Políticas', 'flavor-platform'); ?></button>
                </form>
            </div>
        </div>

        <!-- Normas por espacio -->
        <div class="flavor-card" style="grid-column: span 2;">
            <div class="flavor-card-header">
                <h2><?php _e('Normas Específicas por Espacio', 'flavor-platform'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <div id="normas-espacios-list">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>

        <!-- Restricciones de uso -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Restricciones de Uso', 'flavor-platform'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <form id="form-restricciones">
                    <div class="flavor-form-group">
                        <label for="max-horas-mes"><?php _e('Máximo de horas por usuario/mes', 'flavor-platform'); ?></label>
                        <input type="number" id="max-horas-mes" name="max_horas_mes" min="0" class="widefat">
                        <p class="description"><?php _e('0 = sin límite', 'flavor-platform'); ?></p>
                    </div>

                    <div class="flavor-form-group">
                        <label for="max-reservas-simultaneas"><?php _e('Máximo de reservas simultáneas', 'flavor-platform'); ?></label>
                        <input type="number" id="max-reservas-simultaneas" name="max_reservas_simultaneas" min="1" class="widefat">
                    </div>

                    <div class="flavor-form-group">
                        <label>
                            <input type="checkbox" id="restriccion-repeticion" name="restriccion_repeticion" value="1">
                            <?php _e('Limitar reservas repetidas del mismo espacio', 'flavor-platform'); ?>
                        </label>
                    </div>

                    <button type="submit" class="button button-primary"><?php _e('Guardar Restricciones', 'flavor-platform'); ?></button>
                </form>
            </div>
        </div>

        <!-- Notificaciones y recordatorios -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Notificaciones', 'flavor-platform'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <form id="form-notificaciones">
                    <div class="flavor-form-group">
                        <label>
                            <input type="checkbox" name="notif_confirmacion" value="1" checked>
                            <?php _e('Enviar confirmación de reserva', 'flavor-platform'); ?>
                        </label>
                    </div>

                    <div class="flavor-form-group">
                        <label>
                            <input type="checkbox" name="notif_recordatorio" value="1" checked>
                            <?php _e('Enviar recordatorio antes de la reserva', 'flavor-platform'); ?>
                        </label>
                    </div>

                    <div class="flavor-form-group">
                        <label for="recordatorio-horas"><?php _e('Horas de antelación para recordatorio', 'flavor-platform'); ?></label>
                        <input type="number" id="recordatorio-horas" name="recordatorio_horas" value="24" min="1" class="widefat">
                    </div>

                    <div class="flavor-form-group">
                        <label>
                            <input type="checkbox" name="notif_cancelacion" value="1" checked>
                            <?php _e('Notificar cancelaciones', 'flavor-platform'); ?>
                        </label>
                    </div>

                    <button type="submit" class="button button-primary"><?php _e('Guardar Configuración', 'flavor-platform'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar norma -->
<div id="modal-norma" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2 id="modal-norma-title"><?php _e('Nueva Norma', 'flavor-platform'); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-norma">
                <input type="hidden" id="norma-id" name="id">

                <div class="flavor-form-group">
                    <label for="norma-titulo"><?php _e('Título', 'flavor-platform'); ?> *</label>
                    <input type="text" id="norma-titulo" name="titulo" required class="widefat">
                </div>

                <div class="flavor-form-group">
                    <label for="norma-descripcion"><?php _e('Descripción', 'flavor-platform'); ?> *</label>
                    <textarea id="norma-descripcion" name="descripcion" rows="5" required class="widefat"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="norma-tipo"><?php _e('Tipo', 'flavor-platform'); ?></label>
                    <select id="norma-tipo" name="tipo" class="widefat">
                        <option value="<?php echo esc_attr__('general', 'flavor-platform'); ?>"><?php _e('General', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('especifica', 'flavor-platform'); ?>"><?php _e('Específica de espacio', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group" id="select-espacio-norma" style="display: none;">
                    <label for="norma-espacio"><?php _e('Espacio', 'flavor-platform'); ?></label>
                    <select id="norma-espacio" name="espacio_id" class="widefat">
                        <option value=""><?php _e('Seleccionar...', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="norma-prioridad"><?php _e('Prioridad', 'flavor-platform'); ?></label>
                    <select id="norma-prioridad" name="prioridad" class="widefat">
                        <option value="<?php echo esc_attr__('baja', 'flavor-platform'); ?>"><?php _e('Baja', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('media', 'flavor-platform'); ?>" selected><?php _e('Media', 'flavor-platform'); ?></option>
                        <option value="<?php echo esc_attr__('alta', 'flavor-platform'); ?>"><?php _e('Alta', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label>
                        <input type="checkbox" id="norma-activa" name="activa" value="1" checked>
                        <?php _e('Norma activa', 'flavor-platform'); ?>
                    </label>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-norma"><?php _e('Cancelar', 'flavor-platform'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-norma"><?php _e('Guardar', 'flavor-platform'); ?></button>
        </div>
    </div>
</div>

<style>
.flavor-normas-management {
    margin: 20px;
}

.flavor-grid-two-columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

.flavor-card-header h2 {
    margin: 0;
    font-size: 16px;
}

.flavor-card-body {
    padding: 20px;
}

.flavor-norma-item {
    padding: 15px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 10px;
    position: relative;
}

.flavor-norma-item.alta {
    border-left: 4px solid #ef4444;
}

.flavor-norma-item.media {
    border-left: 4px solid #f59e0b;
}

.flavor-norma-item.baja {
    border-left: 4px solid #3b82f6;
}

.flavor-norma-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.flavor-norma-title {
    font-weight: 600;
    font-size: 14px;
}

.flavor-norma-actions {
    display: flex;
    gap: 5px;
}

.flavor-norma-content {
    font-size: 13px;
    color: #666;
    line-height: 1.5;
}

.flavor-form-group {
    margin-bottom: 15px;
}

.flavor-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.flavor-form-group .description {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
    font-style: italic;
}

@media (max-width: 782px) {
    .flavor-grid-two-columns {
        grid-template-columns: 1fr;
    }

    .flavor-card {
        grid-column: span 1 !important;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Cargar datos
    cargarNormasGenerales();
    cargarNormasEspacios();
    cargarPoliticasCancelacion();
    cargarRestricciones();
    cargarConfiguracionNotificaciones();
    cargarEspaciosSelect();

    // Nueva norma
    $('#btn-nueva-norma').on('click', function() {
        $('#form-norma')[0].reset();
        $('#norma-id').val('');
        $('#modal-norma-title').text('<?php _e('Nueva Norma', 'flavor-platform'); ?>');
        $('#modal-norma').fadeIn();
    });

    // Cambio tipo de norma
    $('#norma-tipo').on('change', function() {
        if ($(this).val() === 'especifica') {
            $('#select-espacio-norma').show();
        } else {
            $('#select-espacio-norma').hide();
        }
    });

    // Penalización activa
    $('#penalizacion-activa').on('change', function() {
        if ($(this).is(':checked')) {
            $('#penalizacion-config').show();
        } else {
            $('#penalizacion-config').hide();
        }
    });

    // Cerrar modal
    $('.flavor-modal-close, #btn-cancelar-norma').on('click', function() {
        $('#modal-norma').fadeOut();
    });

    // Guardar norma
    $('#btn-guardar-norma').on('click', function() {
        const formData = $('#form-norma').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData + '&action=espacios_comunes_guardar_norma',
            success: function(response) {
                if (response.success) {
                    $('#modal-norma').fadeOut();
                    cargarNormasGenerales();
                    cargarNormasEspacios();
                    alert('<?php _e('Norma guardada correctamente', 'flavor-platform'); ?>');
                }
            }
        });
    });

    // Guardar políticas de cancelación
    $('#form-politicas-cancelacion').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $(this).serialize() + '&action=espacios_comunes_guardar_politicas_cancelacion',
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Políticas guardadas correctamente', 'flavor-platform'); ?>');
                }
            }
        });
    });

    // Guardar restricciones
    $('#form-restricciones').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $(this).serialize() + '&action=espacios_comunes_guardar_restricciones',
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Restricciones guardadas correctamente', 'flavor-platform'); ?>');
                }
            }
        });
    });

    // Guardar notificaciones
    $('#form-notificaciones').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $(this).serialize() + '&action=espacios_comunes_guardar_notificaciones',
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Configuración guardada correctamente', 'flavor-platform'); ?>');
                }
            }
        });
    });

    function cargarNormasGenerales() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_listar_normas',
                tipo: 'general'
            },
            success: function(response) {
                if (response.success) {
                    renderizarNormas(response.data, '#normas-generales-list');
                }
            }
        });
    }

    function cargarNormasEspacios() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_listar_normas',
                tipo: 'especifica'
            },
            success: function(response) {
                if (response.success) {
                    renderizarNormas(response.data, '#normas-espacios-list');
                }
            }
        });
    }

    function renderizarNormas(normas, contenedor) {
        if (normas.length === 0) {
            $(contenedor).html('<p><?php _e('No hay normas definidas', 'flavor-platform'); ?></p>');
            return;
        }

        let html = '';
        normas.forEach(norma => {
            html += `
                <div class="flavor-norma-item ${norma.prioridad}">
                    <div class="flavor-norma-header">
                        <div class="flavor-norma-title">${norma.titulo}</div>
                        <div class="flavor-norma-actions">
                            <button class="button button-small btn-editar-norma" data-id="${norma.id}">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button class="button button-small btn-eliminar-norma" data-id="${norma.id}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="flavor-norma-content">${norma.descripcion}</div>
                    ${norma.espacio_nombre ? `<p style="margin-top: 8px; font-size: 12px; color: #999;"><strong>${norma.espacio_nombre}</strong></p>` : ''}
                </div>
            `;
        });
        $(contenedor).html(html);
    }

    function cargarEspaciosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_listar_espacios' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Seleccionar...', 'flavor-platform'); ?></option>';
                    response.data.forEach(espacio => {
                        options += `<option value="<?php echo esc_attr__('${espacio.id}', 'flavor-platform'); ?>">${espacio.nombre}</option>`;
                    });
                    $('#norma-espacio').html(options);
                }
            }
        });
    }

    function cargarPoliticasCancelacion() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_obtener_politicas_cancelacion' },
            success: function(response) {
                if (response.success) {
                    $('#plazo-cancelacion').val(response.data.plazo_cancelacion);
                    $('#cancelaciones-permitidas').val(response.data.cancelaciones_permitidas);
                    $('#penalizacion-activa').prop('checked', response.data.penalizacion_activa == 1);
                    $('#penalizacion-duracion').val(response.data.penalizacion_duracion);
                    if (response.data.penalizacion_activa == 1) {
                        $('#penalizacion-config').show();
                    }
                }
            }
        });
    }

    function cargarRestricciones() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_obtener_restricciones' },
            success: function(response) {
                if (response.success) {
                    $('#max-horas-mes').val(response.data.max_horas_mes);
                    $('#max-reservas-simultaneas').val(response.data.max_reservas_simultaneas);
                    $('#restriccion-repeticion').prop('checked', response.data.restriccion_repeticion == 1);
                }
            }
        });
    }

    function cargarConfiguracionNotificaciones() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_obtener_config_notificaciones' },
            success: function(response) {
                if (response.success) {
                    $('input[name="notif_confirmacion"]').prop('checked', response.data.notif_confirmacion == 1);
                    $('input[name="notif_recordatorio"]').prop('checked', response.data.notif_recordatorio == 1);
                    $('#recordatorio-horas').val(response.data.recordatorio_horas);
                    $('input[name="notif_cancelacion"]').prop('checked', response.data.notif_cancelacion == 1);
                }
            }
        });
    }

    // Editar norma
    $(document).on('click', '.btn-editar-norma', function() {
        const normaId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_norma',
                id: normaId
            },
            success: function(response) {
                if (response.success) {
                    $('#norma-id').val(response.data.id);
                    $('#norma-titulo').val(response.data.titulo);
                    $('#norma-descripcion').val(response.data.descripcion);
                    $('#norma-tipo').val(response.data.tipo).trigger('change');
                    $('#norma-espacio').val(response.data.espacio_id);
                    $('#norma-prioridad').val(response.data.prioridad);
                    $('#norma-activa').prop('checked', response.data.activa == 1);
                    $('#modal-norma-title').text('<?php _e('Editar Norma', 'flavor-platform'); ?>');
                    $('#modal-norma').fadeIn();
                }
            }
        });
    });

    // Eliminar norma
    $(document).on('click', '.btn-eliminar-norma', function() {
        if (!confirm('<?php _e('¿Estás seguro de eliminar esta norma?', 'flavor-platform'); ?>')) return;

        const normaId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_eliminar_norma',
                id: normaId
            },
            success: function(response) {
                if (response.success) {
                    cargarNormasGenerales();
                    cargarNormasEspacios();
                    alert('<?php _e('Norma eliminada', 'flavor-platform'); ?>');
                }
            }
        });
    });
});
</script>
