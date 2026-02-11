<?php
/**
 * Gestión de Espacios Comunes
 * CRUD de espacios disponibles
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-espacios-management">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Espacios', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nuevo-espacio">
        <?php _e('Nuevo Espacio', 'flavor-chat-ia'); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <div class="flavor-filter-group">
            <label><?php _e('Buscar:', 'flavor-chat-ia'); ?></label>
            <input type="text" id="search-espacios" class="flavor-search-input" placeholder="<?php _e('Nombre del espacio...', 'flavor-chat-ia'); ?>">
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Tipo:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-tipo" class="flavor-select">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('salon', 'flavor-chat-ia'); ?>"><?php _e('Salón', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('oficina', 'flavor-chat-ia'); ?>"><?php _e('Oficina', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('terraza', 'flavor-chat-ia'); ?>"><?php _e('Terraza', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('deportivo', 'flavor-chat-ia'); ?>"><?php _e('Deportivo', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <div class="flavor-filter-group">
            <label><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-estado" class="flavor-select">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>"><?php _e('Activo', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('mantenimiento', 'flavor-chat-ia'); ?>"><?php _e('Mantenimiento', 'flavor-chat-ia'); ?></option>
                <option value="<?php echo esc_attr__('inactivo', 'flavor-chat-ia'); ?>"><?php _e('Inactivo', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
    </div>

    <!-- Lista de espacios -->
    <div class="flavor-espacios-grid" id="espacios-list">
        <div class="flavor-loading"><?php _e('Cargando espacios...', 'flavor-chat-ia'); ?></div>
    </div>
</div>

<!-- Modal para crear/editar espacio -->
<div id="modal-espacio" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content flavor-modal-large">
        <div class="flavor-modal-header">
            <h2 id="modal-espacio-title"><?php _e('Nuevo Espacio', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-espacio">
                <input type="hidden" id="espacio-id" name="id">

                <div class="flavor-form-grid">
                    <!-- Columna izquierda -->
                    <div class="flavor-form-column">
                        <div class="flavor-form-section">
                            <h3><?php _e('Información Básica', 'flavor-chat-ia'); ?></h3>

                            <div class="flavor-form-group">
                                <label for="espacio-nombre"><?php _e('Nombre del espacio', 'flavor-chat-ia'); ?> *</label>
                                <input type="text" id="espacio-nombre" name="nombre" required class="widefat">
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?> *</label>
                                <select id="espacio-tipo" name="tipo" required class="widefat">
                                    <option value="<?php echo esc_attr__('salon', 'flavor-chat-ia'); ?>"><?php _e('Salón', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('oficina', 'flavor-chat-ia'); ?>"><?php _e('Oficina', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('terraza', 'flavor-chat-ia'); ?>"><?php _e('Terraza', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('deportivo', 'flavor-chat-ia'); ?>"><?php _e('Deportivo', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('cocina', 'flavor-chat-ia'); ?>"><?php _e('Cocina', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('sala_reuniones', 'flavor-chat-ia'); ?>"><?php _e('Sala de reuniones', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('otro', 'flavor-chat-ia'); ?>"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
                                </select>
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                                <textarea id="espacio-descripcion" name="descripcion" rows="4" class="widefat"></textarea>
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-ubicacion"><?php _e('Ubicación', 'flavor-chat-ia'); ?></label>
                                <input type="text" id="espacio-ubicacion" name="ubicacion" class="widefat" placeholder="<?php _e('Ej: Planta 2, Edificio A', 'flavor-chat-ia'); ?>">
                            </div>
                        </div>

                        <div class="flavor-form-section">
                            <h3><?php _e('Capacidad y Equipamiento', 'flavor-chat-ia'); ?></h3>

                            <div class="flavor-form-row">
                                <div class="flavor-form-group">
                                    <label for="espacio-capacidad"><?php _e('Capacidad (personas)', 'flavor-chat-ia'); ?></label>
                                    <input type="number" id="espacio-capacidad" name="capacidad" min="1" class="widefat">
                                </div>

                                <div class="flavor-form-group">
                                    <label for="espacio-superficie"><?php _e('Superficie (m²)', 'flavor-chat-ia'); ?></label>
                                    <input type="number" id="espacio-superficie" name="superficie" min="0" step="0.1" class="widefat">
                                </div>
                            </div>

                            <div class="flavor-form-group">
                                <label><?php _e('Equipamiento disponible', 'flavor-chat-ia'); ?></label>
                                <div class="flavor-checkboxes-grid">
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('wifi', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('WiFi', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('proyector', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Proyector', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('television', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Televisión', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('audio', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Sistema de audio', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('pizarra', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Pizarra', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('cocina', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Cocina', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('aire', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Aire acondicionado', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('calefaccion', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Calefacción', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="equipamiento[]" value="<?php echo esc_attr__('accesible', 'flavor-chat-ia'); ?>"> <?php echo esc_html__('Accesible PMR', 'flavor-chat-ia'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div class="flavor-form-column">
                        <div class="flavor-form-section">
                            <h3><?php _e('Disponibilidad y Reservas', 'flavor-chat-ia'); ?></h3>

                            <div class="flavor-form-group">
                                <label for="espacio-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                                <select id="espacio-estado" name="estado" class="widefat">
                                    <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>"><?php _e('Activo', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('mantenimiento', 'flavor-chat-ia'); ?>"><?php _e('En mantenimiento', 'flavor-chat-ia'); ?></option>
                                    <option value="<?php echo esc_attr__('inactivo', 'flavor-chat-ia'); ?>"><?php _e('Inactivo', 'flavor-chat-ia'); ?></option>
                                </select>
                            </div>

                            <div class="flavor-form-group">
                                <label>
                                    <input type="checkbox" id="espacio-requiere-aprobacion" name="requiere_aprobacion" value="1">
                                    <?php _e('Requiere aprobación del administrador', 'flavor-chat-ia'); ?>
                                </label>
                            </div>

                            <div class="flavor-form-row">
                                <div class="flavor-form-group">
                                    <label for="espacio-duracion-min"><?php _e('Duración mínima (min)', 'flavor-chat-ia'); ?></label>
                                    <input type="number" id="espacio-duracion-min" name="duracion_minima" value="30" min="15" step="15" class="widefat">
                                </div>

                                <div class="flavor-form-group">
                                    <label for="espacio-duracion-max"><?php _e('Duración máxima (h)', 'flavor-chat-ia'); ?></label>
                                    <input type="number" id="espacio-duracion-max" name="duracion_maxima" value="4" min="1" step="0.5" class="widefat">
                                </div>
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-antelacion"><?php _e('Antelación mínima (días)', 'flavor-chat-ia'); ?></label>
                                <input type="number" id="espacio-antelacion" name="antelacion_minima" value="0" min="0" class="widefat">
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-anticipacion"><?php _e('Anticipación máxima (días)', 'flavor-chat-ia'); ?></label>
                                <input type="number" id="espacio-anticipacion" name="anticipacion_maxima" value="30" min="1" class="widefat">
                            </div>
                        </div>

                        <div class="flavor-form-section">
                            <h3><?php _e('Horarios', 'flavor-chat-ia'); ?></h3>

                            <div class="flavor-form-group">
                                <label for="espacio-horario-inicio"><?php _e('Hora de apertura', 'flavor-chat-ia'); ?></label>
                                <input type="time" id="espacio-horario-inicio" name="horario_inicio" value="08:00" class="widefat">
                            </div>

                            <div class="flavor-form-group">
                                <label for="espacio-horario-fin"><?php _e('Hora de cierre', 'flavor-chat-ia'); ?></label>
                                <input type="time" id="espacio-horario-fin" name="horario_fin" value="22:00" class="widefat">
                            </div>

                            <div class="flavor-form-group">
                                <label><?php _e('Días disponibles', 'flavor-chat-ia'); ?></label>
                                <div class="flavor-checkboxes-inline">
                                    <label><input type="checkbox" name="dias_disponibles[]" value="1" checked> <?php _e('L', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="2" checked> <?php _e('M', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="3" checked> <?php _e('X', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="4" checked> <?php _e('J', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="5" checked> <?php _e('V', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="6"> <?php _e('S', 'flavor-chat-ia'); ?></label>
                                    <label><input type="checkbox" name="dias_disponibles[]" value="0"> <?php _e('D', 'flavor-chat-ia'); ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="flavor-form-section">
                            <h3><?php _e('Imágenes', 'flavor-chat-ia'); ?></h3>

                            <div class="flavor-form-group">
                                <label><?php _e('Foto principal', 'flavor-chat-ia'); ?></label>
                                <div class="flavor-image-upload">
                                    <div class="flavor-image-preview" id="imagen-preview">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                    <input type="hidden" id="espacio-imagen" name="imagen_url">
                                    <button type="button" class="button" id="btn-subir-imagen">
                                        <?php _e('Seleccionar imagen', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-espacio"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-espacio"><?php _e('Guardar Espacio', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<style>
.flavor-espacios-management {
    margin: 20px;
}

.flavor-filters-bar {
    background: #fff;
    padding: 15px 20px;
    margin: 20px 0;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.flavor-filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-filter-group label {
    font-weight: 600;
    margin: 0;
}

.flavor-search-input {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 250px;
}

.flavor-select {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.flavor-espacios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.flavor-espacio-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s;
}

.flavor-espacio-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.flavor-espacio-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 48px;
}

.flavor-espacio-body {
    padding: 15px;
}

.flavor-espacio-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.flavor-espacio-header h3 {
    margin: 0;
    font-size: 16px;
}

.flavor-espacio-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-espacio-status.activo {
    background: #d1fae5;
    color: #065f46;
}

.flavor-espacio-status.mantenimiento {
    background: #fef3c7;
    color: #92400e;
}

.flavor-espacio-status.inactivo {
    background: #fee2e2;
    color: #991b1b;
}

.flavor-espacio-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 10px 0;
    font-size: 13px;
    color: #666;
}

.flavor-espacio-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-espacio-equipamiento {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.flavor-badge {
    padding: 3px 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 11px;
    color: #374151;
}

.flavor-espacio-footer {
    padding: 10px 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.flavor-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.flavor-form-section {
    margin-bottom: 25px;
}

.flavor-form-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
    font-size: 15px;
}

.flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.flavor-checkboxes-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.flavor-checkboxes-inline {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.flavor-checkboxes-inline label {
    margin: 0;
}

.flavor-image-upload {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-image-preview {
    width: 100%;
    height: 150px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
    overflow: hidden;
}

.flavor-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-image-preview .dashicons {
    font-size: 48px;
    color: #ccc;
}

@media (max-width: 1200px) {
    .flavor-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 782px) {
    .flavor-espacios-grid {
        grid-template-columns: 1fr;
    }

    .flavor-filters-bar {
        flex-direction: column;
    }

    .flavor-filter-group {
        width: 100%;
    }

    .flavor-search-input {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let mediaUploader;

    // Cargar espacios
    cargarEspacios();

    // Nuevo espacio
    $('#btn-nuevo-espacio').on('click', function() {
        $('#modal-espacio-title').text('<?php _e('Nuevo Espacio', 'flavor-chat-ia'); ?>');
        $('#form-espacio')[0].reset();
        $('#espacio-id').val('');
        $('#modal-espacio').fadeIn();
    });

    // Cerrar modal
    $('.flavor-modal-close, #btn-cancelar-espacio').on('click', function() {
        $('#modal-espacio').fadeOut();
    });

    // Guardar espacio
    $('#btn-guardar-espacio').on('click', function() {
        const formData = new FormData($('#form-espacio')[0]);
        formData.append('action', 'espacios_comunes_guardar_espacio');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#modal-espacio').fadeOut();
                    cargarEspacios();
                    mostrarNotificacion('<?php _e('Espacio guardado correctamente', 'flavor-chat-ia'); ?>', 'success');
                } else {
                    mostrarNotificacion(response.data.message, 'error');
                }
            }
        });
    });

    // Subir imagen
    $('#btn-subir-imagen').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: '<?php _e('Seleccionar imagen', 'flavor-chat-ia'); ?>',
            button: {
                text: '<?php _e('Usar esta imagen', 'flavor-chat-ia'); ?>'
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#espacio-imagen').val(attachment.url);
            $('#imagen-preview').html('<img src="' + attachment.url + '" alt="">');
        });
        mediaUploader.open();
    });

    // Filtros
    $('#search-espacios, #filtro-tipo, #filtro-estado').on('change keyup', function() {
        cargarEspacios();
    });

    function cargarEspacios() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_listar_espacios',
                search: $('#search-espacios').val(),
                tipo: $('#filtro-tipo').val(),
                estado: $('#filtro-estado').val()
            },
            success: function(response) {
                if (response.success) {
                    renderizarEspacios(response.data);
                }
            }
        });
    }

    function renderizarEspacios(espacios) {
        if (espacios.length === 0) {
            $('#espacios-list').html('<div class="flavor-empty-state"><p><?php _e('No se encontraron espacios', 'flavor-chat-ia'); ?></p></div>');
            return;
        }

        let html = '';
        espacios.forEach(espacio => {
            const imagen = espacio.imagen_url ?
                `<img src="${espacio.imagen_url}" alt="<?php echo esc_attr__('${espacio.nombre}', 'flavor-chat-ia'); ?>" class="flavor-espacio-image">` :
                '<div class="flavor-espacio-image"><span class="dashicons dashicons-admin-home"></span></div>';

            const equipamiento = espacio.equipamiento ? JSON.parse(espacio.equipamiento).map(eq =>
                `<span class="flavor-badge">${eq}</span>`
            ).join('') : '';

            html += `
                <div class="flavor-espacio-card" data-id="${espacio.id}">
                    ${imagen}
                    <div class="flavor-espacio-body">
                        <div class="flavor-espacio-header">
                            <h3>${espacio.nombre}</h3>
                            <span class="flavor-espacio-status ${espacio.estado}">${espacio.estado}</span>
                        </div>
                        <p>${espacio.descripcion || ''}</p>
                        <div class="flavor-espacio-meta">
                            ${espacio.capacidad ? `<span class="flavor-espacio-meta-item"><span class="dashicons dashicons-groups"></span> ${espacio.capacidad} personas</span>` : ''}
                            ${espacio.superficie ? `<span class="flavor-espacio-meta-item"><span class="dashicons dashicons-admin-home"></span> ${espacio.superficie} m²</span>` : ''}
                        </div>
                        <div class="flavor-espacio-equipamiento">${equipamiento}</div>
                    </div>
                    <div class="flavor-espacio-footer">
                        <button class="button btn-editar-espacio" data-id="${espacio.id}">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Editar', 'flavor-chat-ia'); ?>
                        </button>
                        <button class="button btn-eliminar-espacio" data-id="${espacio.id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
        });
        $('#espacios-list').html(html);
    }

    // Editar espacio
    $(document).on('click', '.btn-editar-espacio', function() {
        const espacioId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_espacio',
                id: espacioId
            },
            success: function(response) {
                if (response.success) {
                    cargarDatosFormulario(response.data);
                    $('#modal-espacio-title').text('<?php _e('Editar Espacio', 'flavor-chat-ia'); ?>');
                    $('#modal-espacio').fadeIn();
                }
            }
        });
    });

    // Eliminar espacio
    $(document).on('click', '.btn-eliminar-espacio', function() {
        if (!confirm('<?php _e('¿Estás seguro de eliminar este espacio?', 'flavor-chat-ia'); ?>')) return;

        const espacioId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_eliminar_espacio',
                id: espacioId
            },
            success: function(response) {
                if (response.success) {
                    cargarEspacios();
                    mostrarNotificacion('<?php _e('Espacio eliminado', 'flavor-chat-ia'); ?>', 'success');
                }
            }
        });
    });

    function cargarDatosFormulario(espacio) {
        $('#espacio-id').val(espacio.id);
        $('#espacio-nombre').val(espacio.nombre);
        $('#espacio-tipo').val(espacio.tipo);
        $('#espacio-descripcion').val(espacio.descripcion);
        $('#espacio-ubicacion').val(espacio.ubicacion);
        $('#espacio-capacidad').val(espacio.capacidad);
        $('#espacio-superficie').val(espacio.superficie);
        $('#espacio-estado').val(espacio.estado);
        $('#espacio-requiere-aprobacion').prop('checked', espacio.requiere_aprobacion == 1);
        $('#espacio-duracion-min').val(espacio.duracion_minima);
        $('#espacio-duracion-max').val(espacio.duracion_maxima);
        $('#espacio-antelacion').val(espacio.antelacion_minima);
        $('#espacio-anticipacion').val(espacio.anticipacion_maxima);
        $('#espacio-horario-inicio').val(espacio.horario_inicio);
        $('#espacio-horario-fin').val(espacio.horario_fin);

        if (espacio.imagen_url) {
            $('#espacio-imagen').val(espacio.imagen_url);
            $('#imagen-preview').html('<img src="' + espacio.imagen_url + '" alt="">');
        }

        if (espacio.equipamiento) {
            const equipamiento = JSON.parse(espacio.equipamiento);
            equipamiento.forEach(eq => {
                $(`input[name="equipamiento[]"][value="<?php echo esc_attr__('${eq}', 'flavor-chat-ia'); ?>"]`).prop('checked', true);
            });
        }

        if (espacio.dias_disponibles) {
            const dias = JSON.parse(espacio.dias_disponibles);
            dias.forEach(dia => {
                $(`input[name="dias_disponibles[]"][value="<?php echo esc_attr__('${dia}', 'flavor-chat-ia'); ?>"]`).prop('checked', true);
            });
        }
    }

    function mostrarNotificacion(mensaje, tipo) {
        // Implementar notificación
        alert(mensaje);
    }
});
</script>
