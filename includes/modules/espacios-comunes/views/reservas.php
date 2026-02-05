<?php
/**
 * Gestión de Reservas de Espacios Comunes
 * Lista y gestión de reservas con calendario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-reservas-management">
    <h1 class="wp-heading-inline">
        <?php _e('Gestión de Reservas', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-nueva-reserva">
        <?php _e('Nueva Reserva', 'flavor-chat-ia'); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Selector de vista -->
    <div class="flavor-view-switcher">
        <button class="flavor-view-btn active" data-view="calendario">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Calendario', 'flavor-chat-ia'); ?>
        </button>
        <button class="flavor-view-btn" data-view="lista">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('Lista', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- Vista de calendario -->
    <div id="vista-calendario" class="flavor-view-content active">
        <div class="flavor-calendar-controls">
            <div class="flavor-calendar-nav">
                <button id="btn-mes-anterior" class="button">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <h2 id="calendar-month-year"></h2>
                <button id="btn-mes-siguiente" class="button">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
            <div class="flavor-calendar-filters">
                <select id="filtro-espacio-calendario" class="flavor-select">
                    <option value=""><?php _e('Todos los espacios', 'flavor-chat-ia'); ?></option>
                </select>
                <button id="btn-hoy" class="button"><?php _e('Hoy', 'flavor-chat-ia'); ?></button>
            </div>
        </div>
        <div id="calendario-reservas"></div>
    </div>

    <!-- Vista de lista -->
    <div id="vista-lista" class="flavor-view-content" style="display: none;">
        <!-- Filtros -->
        <div class="flavor-filters-bar">
            <div class="flavor-filter-group">
                <label><?php _e('Buscar:', 'flavor-chat-ia'); ?></label>
                <input type="text" id="search-reservas" class="flavor-search-input" placeholder="<?php _e('Usuario, espacio...', 'flavor-chat-ia'); ?>">
            </div>
            <div class="flavor-filter-group">
                <label><?php _e('Espacio:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-espacio" class="flavor-select">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div class="flavor-filter-group">
                <label><?php _e('Estado:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-estado-reserva" class="flavor-select">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                    <option value="confirmada"><?php _e('Confirmada', 'flavor-chat-ia'); ?></option>
                    <option value="completada"><?php _e('Completada', 'flavor-chat-ia'); ?></option>
                    <option value="cancelada"><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div class="flavor-filter-group">
                <label><?php _e('Período:', 'flavor-chat-ia'); ?></label>
                <select id="filtro-periodo" class="flavor-select">
                    <option value="proximas"><?php _e('Próximas', 'flavor-chat-ia'); ?></option>
                    <option value="hoy"><?php _e('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="semana"><?php _e('Esta semana', 'flavor-chat-ia'); ?></option>
                    <option value="mes"><?php _e('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="todas"><?php _e('Todas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>

        <!-- Tabla de reservas -->
        <div class="flavor-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Espacio', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Horario', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Duración', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody id="reservas-list">
                    <tr>
                        <td colspan="8" class="flavor-loading"><?php _e('Cargando reservas...', 'flavor-chat-ia'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar reserva -->
<div id="modal-reserva" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2 id="modal-reserva-title"><?php _e('Nueva Reserva', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body">
            <form id="form-reserva">
                <input type="hidden" id="reserva-id" name="id">

                <div class="flavor-form-group">
                    <label for="reserva-espacio"><?php _e('Espacio', 'flavor-chat-ia'); ?> *</label>
                    <select id="reserva-espacio" name="espacio_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar espacio...', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="reserva-usuario"><?php _e('Usuario', 'flavor-chat-ia'); ?> *</label>
                    <select id="reserva-usuario" name="usuario_id" required class="widefat">
                        <option value=""><?php _e('Seleccionar usuario...', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="reserva-fecha"><?php _e('Fecha', 'flavor-chat-ia'); ?> *</label>
                        <input type="date" id="reserva-fecha" name="fecha" required class="widefat">
                    </div>
                </div>

                <div class="flavor-form-row">
                    <div class="flavor-form-group">
                        <label for="reserva-hora-inicio"><?php _e('Hora inicio', 'flavor-chat-ia'); ?> *</label>
                        <input type="time" id="reserva-hora-inicio" name="hora_inicio" required class="widefat">
                    </div>

                    <div class="flavor-form-group">
                        <label for="reserva-hora-fin"><?php _e('Hora fin', 'flavor-chat-ia'); ?> *</label>
                        <input type="time" id="reserva-hora-fin" name="hora_fin" required class="widefat">
                    </div>
                </div>

                <div class="flavor-form-group">
                    <label for="reserva-proposito"><?php _e('Propósito', 'flavor-chat-ia'); ?></label>
                    <textarea id="reserva-proposito" name="proposito" rows="3" class="widefat" placeholder="<?php _e('Describe el propósito de la reserva...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="reserva-asistentes"><?php _e('Número de asistentes', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="reserva-asistentes" name="num_asistentes" min="1" class="widefat">
                </div>

                <div class="flavor-form-group">
                    <label for="reserva-estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="reserva-estado" name="estado" class="widefat">
                        <option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                        <option value="confirmada"><?php _e('Confirmada', 'flavor-chat-ia'); ?></option>
                        <option value="completada"><?php _e('Completada', 'flavor-chat-ia'); ?></option>
                        <option value="cancelada"><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="reserva-notas"><?php _e('Notas internas', 'flavor-chat-ia'); ?></label>
                    <textarea id="reserva-notas" name="notas_admin" rows="3" class="widefat"></textarea>
                </div>
            </form>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancelar-reserva"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary" id="btn-guardar-reserva"><?php _e('Guardar Reserva', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<!-- Modal de detalles de reserva -->
<div id="modal-detalle-reserva" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2><?php _e('Detalles de la Reserva', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="flavor-modal-body" id="detalle-reserva-content">
            <!-- Se llena dinámicamente -->
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cerrar-detalle"><?php _e('Cerrar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button" id="btn-editar-desde-detalle"><?php _e('Editar', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>

<style>
.flavor-reservas-management {
    margin: 20px;
}

.flavor-view-switcher {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.flavor-view-btn {
    padding: 10px 20px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.flavor-view-btn.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.flavor-view-content {
    display: none;
}

.flavor-view-content.active {
    display: block;
}

.flavor-calendar-controls {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-calendar-nav {
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-calendar-nav h2 {
    margin: 0;
    min-width: 200px;
    text-align: center;
}

.flavor-calendar-filters {
    display: flex;
    gap: 10px;
    align-items: center;
}

#calendario-reservas {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.flavor-calendar {
    width: 100%;
}

.flavor-calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 10px;
}

.flavor-calendar-day-name {
    text-align: center;
    font-weight: 600;
    padding: 10px;
    background: #f3f4f6;
    border-radius: 4px;
}

.flavor-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.flavor-calendar-cell {
    min-height: 100px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 8px;
    background: #fff;
    position: relative;
}

.flavor-calendar-cell.other-month {
    background: #f9fafb;
    color: #9ca3af;
}

.flavor-calendar-cell.today {
    border-color: #2271b1;
    background: #f0f6fc;
}

.flavor-calendar-date {
    font-weight: 600;
    margin-bottom: 5px;
}

.flavor-calendar-reserva {
    font-size: 11px;
    padding: 3px 6px;
    margin-bottom: 3px;
    border-radius: 3px;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.flavor-calendar-reserva.pendiente {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
}

.flavor-calendar-reserva.confirmada {
    background: #d1fae5;
    border-left: 3px solid #10b981;
}

.flavor-calendar-reserva.completada {
    background: #dbeafe;
    border-left: 3px solid #3b82f6;
}

.flavor-calendar-reserva.cancelada {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
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

.flavor-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.flavor-estado-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.flavor-estado-badge.pendiente {
    background: #fef3c7;
    color: #92400e;
}

.flavor-estado-badge.confirmada {
    background: #d1fae5;
    color: #065f46;
}

.flavor-estado-badge.completada {
    background: #dbeafe;
    color: #1e40af;
}

.flavor-estado-badge.cancelada {
    background: #fee2e2;
    color: #991b1b;
}

.flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.flavor-detalle-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.flavor-detalle-item {
    margin-bottom: 15px;
}

.flavor-detalle-label {
    font-weight: 600;
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.flavor-detalle-value {
    font-size: 14px;
    color: #1d2327;
}

@media (max-width: 782px) {
    .flavor-calendar-controls {
        flex-direction: column;
        gap: 15px;
    }

    .flavor-calendar-filters {
        width: 100%;
        flex-direction: column;
    }

    .flavor-calendar-cell {
        min-height: 60px;
        font-size: 12px;
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

    .flavor-form-row,
    .flavor-detalle-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentDate = new Date();
    let currentReservaId = null;

    // Inicializar
    cargarEspaciosSelect();
    cargarUsuariosSelect();
    renderizarCalendario();
    cargarReservasLista();

    // Cambio de vista
    $('.flavor-view-btn').on('click', function() {
        const vista = $(this).data('view');
        $('.flavor-view-btn').removeClass('active');
        $(this).addClass('active');
        $('.flavor-view-content').removeClass('active');
        $(`#vista-${vista}`).addClass('active');
    });

    // Nueva reserva
    $('#btn-nueva-reserva').on('click', function() {
        $('#form-reserva')[0].reset();
        $('#reserva-id').val('');
        $('#modal-reserva-title').text('<?php _e('Nueva Reserva', 'flavor-chat-ia'); ?>');
        $('#modal-reserva').fadeIn();
    });

    // Navegación calendario
    $('#btn-mes-anterior').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderizarCalendario();
    });

    $('#btn-mes-siguiente').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderizarCalendario();
    });

    $('#btn-hoy').on('click', function() {
        currentDate = new Date();
        renderizarCalendario();
    });

    // Cerrar modales
    $('.flavor-modal-close, #btn-cancelar-reserva, #btn-cerrar-detalle').on('click', function() {
        $(this).closest('.flavor-modal').fadeOut();
    });

    // Guardar reserva
    $('#btn-guardar-reserva').on('click', function() {
        const formData = $('#form-reserva').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData + '&action=espacios_comunes_guardar_reserva',
            success: function(response) {
                if (response.success) {
                    $('#modal-reserva').fadeOut();
                    renderizarCalendario();
                    cargarReservasLista();
                    alert('<?php _e('Reserva guardada correctamente', 'flavor-chat-ia'); ?>');
                }
            }
        });
    });

    // Filtros
    $('#search-reservas, #filtro-espacio, #filtro-estado-reserva, #filtro-periodo').on('change keyup', function() {
        cargarReservasLista();
    });

    $('#filtro-espacio-calendario').on('change', function() {
        renderizarCalendario();
    });

    function cargarEspaciosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_listar_espacios' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>';
                    response.data.forEach(espacio => {
                        options += `<option value="${espacio.id}">${espacio.nombre}</option>`;
                    });
                    $('#reserva-espacio, #filtro-espacio, #filtro-espacio-calendario').html(options);
                }
            }
        });
    }

    function cargarUsuariosSelect() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_listar_usuarios' },
            success: function(response) {
                if (response.success) {
                    let options = '<option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>';
                    response.data.forEach(usuario => {
                        options += `<option value="${usuario.id}">${usuario.nombre}</option>`;
                    });
                    $('#reserva-usuario').html(options);
                }
            }
        });
    }

    function renderizarCalendario() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        $('#calendar-month-year').text(new Date(year, month).toLocaleDateString('es-ES', {
            month: 'long',
            year: 'numeric'
        }));

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_calendario',
                year: year,
                month: month + 1,
                espacio_id: $('#filtro-espacio-calendario').val()
            },
            success: function(response) {
                if (response.success) {
                    generarCalendarioHTML(year, month, response.data);
                }
            }
        });
    }

    function generarCalendarioHTML(year, month, reservas) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const prevLastDay = new Date(year, month, 0);

        let html = '<div class="flavor-calendar">';
        html += '<div class="flavor-calendar-header">';
        ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'].forEach(day => {
            html += `<div class="flavor-calendar-day-name">${day}</div>`;
        });
        html += '</div><div class="flavor-calendar-grid">';

        // Días del mes anterior
        for (let i = firstDay.getDay() - 1; i >= 0; i--) {
            const day = prevLastDay.getDate() - i;
            html += `<div class="flavor-calendar-cell other-month"><div class="flavor-calendar-date">${day}</div></div>`;
        }

        // Días del mes actual
        const today = new Date();
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;

            html += `<div class="flavor-calendar-cell ${isToday ? 'today' : ''}" data-date="${dateStr}">`;
            html += `<div class="flavor-calendar-date">${day}</div>`;

            if (reservas[dateStr]) {
                reservas[dateStr].forEach(reserva => {
                    html += `<div class="flavor-calendar-reserva ${reserva.estado}" data-id="${reserva.id}" title="${reserva.espacio} - ${reserva.hora_inicio}">
                        ${reserva.hora_inicio} ${reserva.espacio_corto}
                    </div>`;
                });
            }
            html += '</div>';
        }

        html += '</div></div>';
        $('#calendario-reservas').html(html);
    }

    function cargarReservasLista() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_listar_reservas',
                search: $('#search-reservas').val(),
                espacio_id: $('#filtro-espacio').val(),
                estado: $('#filtro-estado-reserva').val(),
                periodo: $('#filtro-periodo').val()
            },
            success: function(response) {
                if (response.success) {
                    renderizarReservasLista(response.data);
                }
            }
        });
    }

    function renderizarReservasLista(reservas) {
        if (reservas.length === 0) {
            $('#reservas-list').html('<tr><td colspan="8" style="text-align: center;"><?php _e('No se encontraron reservas', 'flavor-chat-ia'); ?></td></tr>');
            return;
        }

        let html = '';
        reservas.forEach(reserva => {
            html += `
                <tr>
                    <td>#${reserva.id}</td>
                    <td>${reserva.espacio_nombre}</td>
                    <td>${reserva.usuario_nombre}</td>
                    <td>${reserva.fecha_formato}</td>
                    <td>${reserva.hora_inicio} - ${reserva.hora_fin}</td>
                    <td>${reserva.duracion}</td>
                    <td><span class="flavor-estado-badge ${reserva.estado}">${reserva.estado}</span></td>
                    <td>
                        <button class="button button-small btn-ver-reserva" data-id="${reserva.id}">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="button button-small btn-editar-reserva" data-id="${reserva.id}">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button class="button button-small btn-eliminar-reserva" data-id="${reserva.id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#reservas-list').html(html);
    }

    // Ver detalle de reserva
    $(document).on('click', '.btn-ver-reserva, .flavor-calendar-reserva', function() {
        const reservaId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_reserva',
                id: reservaId
            },
            success: function(response) {
                if (response.success) {
                    mostrarDetalleReserva(response.data);
                }
            }
        });
    });

    function mostrarDetalleReserva(reserva) {
        currentReservaId = reserva.id;
        const html = `
            <div class="flavor-detalle-grid">
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Espacio', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.espacio_nombre}</div>
                </div>
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Usuario', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.usuario_nombre}</div>
                </div>
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Fecha', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.fecha_formato}</div>
                </div>
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Horario', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.hora_inicio} - ${reserva.hora_fin}</div>
                </div>
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Estado', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value"><span class="flavor-estado-badge ${reserva.estado}">${reserva.estado}</span></div>
                </div>
                <div class="flavor-detalle-item">
                    <div class="flavor-detalle-label"><?php _e('Asistentes', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.num_asistentes || '-'}</div>
                </div>
                <div class="flavor-detalle-item" style="grid-column: span 2;">
                    <div class="flavor-detalle-label"><?php _e('Propósito', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.proposito || '-'}</div>
                </div>
                ${reserva.notas_admin ? `
                <div class="flavor-detalle-item" style="grid-column: span 2;">
                    <div class="flavor-detalle-label"><?php _e('Notas internas', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-detalle-value">${reserva.notas_admin}</div>
                </div>
                ` : ''}
            </div>
        `;
        $('#detalle-reserva-content').html(html);
        $('#modal-detalle-reserva').fadeIn();
    }

    // Editar desde detalle
    $('#btn-editar-desde-detalle').on('click', function() {
        $('#modal-detalle-reserva').fadeOut();
        // Cargar datos en formulario de edición
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_reserva',
                id: currentReservaId
            },
            success: function(response) {
                if (response.success) {
                    cargarDatosFormularioReserva(response.data);
                    $('#modal-reserva-title').text('<?php _e('Editar Reserva', 'flavor-chat-ia'); ?>');
                    $('#modal-reserva').fadeIn();
                }
            }
        });
    });

    function cargarDatosFormularioReserva(reserva) {
        $('#reserva-id').val(reserva.id);
        $('#reserva-espacio').val(reserva.espacio_id);
        $('#reserva-usuario').val(reserva.usuario_id);
        $('#reserva-fecha').val(reserva.fecha);
        $('#reserva-hora-inicio').val(reserva.hora_inicio);
        $('#reserva-hora-fin').val(reserva.hora_fin);
        $('#reserva-proposito').val(reserva.proposito);
        $('#reserva-asistentes').val(reserva.num_asistentes);
        $('#reserva-estado').val(reserva.estado);
        $('#reserva-notas').val(reserva.notas_admin);
    }

    // Eliminar reserva
    $(document).on('click', '.btn-eliminar-reserva', function() {
        if (!confirm('<?php _e('¿Estás seguro de eliminar esta reserva?', 'flavor-chat-ia'); ?>')) return;

        const reservaId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_eliminar_reserva',
                id: reservaId
            },
            success: function(response) {
                if (response.success) {
                    renderizarCalendario();
                    cargarReservasLista();
                    alert('<?php _e('Reserva eliminada', 'flavor-chat-ia'); ?>');
                }
            }
        });
    });
});
</script>
