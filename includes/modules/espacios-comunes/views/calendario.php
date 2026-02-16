<?php
/**
 * Vista de Calendario Maestro de Espacios Comunes
 * Visualización completa de todas las reservas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-calendario-maestro">
    <h1 class="wp-heading-inline">
        <?php _e('Calendario Maestro', 'flavor-chat-ia'); ?>
    </h1>
    <button type="button" class="page-title-action" id="btn-exportar-calendario">
        <?php _e('Exportar', 'flavor-chat-ia'); ?>
    </button>
    <button type="button" class="page-title-action" id="btn-imprimir-calendario">
        <?php _e('Imprimir', 'flavor-chat-ia'); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Controles del calendario -->
    <div class="flavor-calendar-toolbar">
        <div class="flavor-calendar-nav-group">
            <button id="btn-prev-period" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
            <h2 id="period-title"><?php echo esc_html__('Enero 2026', 'flavor-chat-ia'); ?></h2>
            <button id="btn-next-period" class="button">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
            <button id="btn-today" class="button"><?php _e('Hoy', 'flavor-chat-ia'); ?></button>
        </div>

        <div class="flavor-view-mode-group">
            <button class="flavor-view-mode active" data-mode="month"><?php _e('Mes', 'flavor-chat-ia'); ?></button>
            <button class="flavor-view-mode" data-mode="week"><?php _e('Semana', 'flavor-chat-ia'); ?></button>
            <button class="flavor-view-mode" data-mode="day"><?php _e('Día', 'flavor-chat-ia'); ?></button>
            <button class="flavor-view-mode" data-mode="agenda"><?php _e('Agenda', 'flavor-chat-ia'); ?></button>
        </div>

        <div class="flavor-calendar-filters-group">
            <select id="filter-espacios-multi" multiple class="flavor-select-multi">
                <!-- Se llena dinámicamente -->
            </select>
            <button id="btn-toggle-filters" class="button">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filtros', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>

    <!-- Panel de filtros avanzados -->
    <div id="advanced-filters-panel" class="flavor-filters-panel" style="display: none;">
        <div class="flavor-filters-content">
            <div class="flavor-filter-section">
                <h3><?php _e('Espacios', 'flavor-chat-ia'); ?></h3>
                <div id="espacios-checkboxes">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>

            <div class="flavor-filter-section">
                <h3><?php _e('Estados', 'flavor-chat-ia'); ?></h3>
                <label><input type="checkbox" name="estado-filter[]" value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>" checked> <?php _e('Pendiente', 'flavor-chat-ia'); ?></label>
                <label><input type="checkbox" name="estado-filter[]" value="<?php echo esc_attr__('confirmada', 'flavor-chat-ia'); ?>" checked> <?php _e('Confirmada', 'flavor-chat-ia'); ?></label>
                <label><input type="checkbox" name="estado-filter[]" value="<?php echo esc_attr__('completada', 'flavor-chat-ia'); ?>"> <?php _e('Completada', 'flavor-chat-ia'); ?></label>
                <label><input type="checkbox" name="estado-filter[]" value="<?php echo esc_attr__('cancelada', 'flavor-chat-ia'); ?>"> <?php _e('Cancelada', 'flavor-chat-ia'); ?></label>
            </div>

            <div class="flavor-filter-section">
                <h3><?php _e('Horario', 'flavor-chat-ia'); ?></h3>
                <label>
                    <?php _e('Desde:', 'flavor-chat-ia'); ?>
                    <input type="time" id="filter-hora-desde" value="00:00">
                </label>
                <label>
                    <?php _e('Hasta:', 'flavor-chat-ia'); ?>
                    <input type="time" id="filter-hora-hasta" value="23:59">
                </label>
            </div>

            <div class="flavor-filter-actions">
                <button id="btn-aplicar-filtros" class="button button-primary"><?php _e('Aplicar', 'flavor-chat-ia'); ?></button>
                <button id="btn-limpiar-filtros" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></button>
            </div>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="flavor-legend">
        <span class="flavor-legend-item">
            <span class="flavor-legend-color pendiente"></span>
            <?php _e('Pendiente', 'flavor-chat-ia'); ?>
        </span>
        <span class="flavor-legend-item">
            <span class="flavor-legend-color confirmada"></span>
            <?php _e('Confirmada', 'flavor-chat-ia'); ?>
        </span>
        <span class="flavor-legend-item">
            <span class="flavor-legend-color completada"></span>
            <?php _e('Completada', 'flavor-chat-ia'); ?>
        </span>
        <span class="flavor-legend-item">
            <span class="flavor-legend-color cancelada"></span>
            <?php _e('Cancelada', 'flavor-chat-ia'); ?>
        </span>
    </div>

    <!-- Contenedor del calendario -->
    <div id="calendar-container" class="flavor-calendar-container"></div>

    <!-- Estadísticas rápidas -->
    <div class="flavor-quick-stats">
        <div class="flavor-stat-card">
            <span class="flavor-stat-label"><?php _e('Reservas hoy', 'flavor-chat-ia'); ?></span>
            <span class="flavor-stat-value" id="stat-hoy">0</span>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-label"><?php _e('Esta semana', 'flavor-chat-ia'); ?></span>
            <span class="flavor-stat-value" id="stat-semana">0</span>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-label"><?php _e('Este mes', 'flavor-chat-ia'); ?></span>
            <span class="flavor-stat-value" id="stat-mes">0</span>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-label"><?php _e('Tasa ocupación', 'flavor-chat-ia'); ?></span>
            <span class="flavor-stat-value" id="stat-ocupacion">0%</span>
        </div>
    </div>
</div>

<style>
.flavor-calendario-maestro {
    margin: 20px;
}

.flavor-calendar-toolbar {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.flavor-calendar-nav-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-calendar-nav-group h2 {
    margin: 0;
    min-width: 200px;
    text-align: center;
    font-size: 18px;
}

.flavor-view-mode-group {
    display: flex;
    gap: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-view-mode {
    padding: 8px 16px;
    border: none;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    border-right: 1px solid #ddd;
}

.flavor-view-mode:last-child {
    border-right: none;
}

.flavor-view-mode.active {
    background: #2271b1;
    color: #fff;
}

.flavor-calendar-filters-group {
    display: flex;
    gap: 10px;
}

.flavor-filters-panel {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.flavor-filters-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.flavor-filter-section h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
}

.flavor-filter-section label {
    display: block;
    margin-bottom: 8px;
}

.flavor-filter-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.flavor-legend {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.flavor-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.flavor-legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

.flavor-legend-color.pendiente {
    background: #fef3c7;
    border: 2px solid #f59e0b;
}

.flavor-legend-color.confirmada {
    background: #d1fae5;
    border: 2px solid #10b981;
}

.flavor-legend-color.completada {
    background: #dbeafe;
    border: 2px solid #3b82f6;
}

.flavor-legend-color.cancelada {
    background: #fee2e2;
    border: 2px solid #ef4444;
}

.flavor-calendar-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    min-height: 600px;
    margin-bottom: 20px;
}

.flavor-quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.flavor-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.flavor-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
    text-transform: uppercase;
}

.flavor-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #2271b1;
}

/* Estilos para FullCalendar */
.fc {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.fc-event {
    cursor: pointer;
    border-radius: 4px;
    padding: 2px 4px;
    margin: 1px 0;
}

.fc-event.pendiente {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.fc-event.confirmada {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.fc-event.completada {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1e40af;
}

.fc-event.cancelada {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

@media (max-width: 782px) {
    .flavor-calendar-toolbar {
        flex-direction: column;
    }

    .flavor-calendar-nav-group,
    .flavor-view-mode-group,
    .flavor-calendar-filters-group {
        width: 100%;
    }

    .flavor-view-mode-group {
        justify-content: stretch;
    }

    .flavor-view-mode {
        flex: 1;
    }
}

@media print {
    .flavor-calendar-toolbar,
    .flavor-filters-panel,
    .flavor-quick-stats,
    .page-title-action {
        display: none !important;
    }

    .flavor-calendar-container {
        border: none;
        box-shadow: none;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentView = 'month';
    let currentDate = new Date();
    let espaciosSeleccionados = [];

    // Inicializar
    cargarEspacios();
    cargarEstadisticas();
    inicializarCalendario();

    // Toggle filtros
    $('#btn-toggle-filters').on('click', function() {
        $('#advanced-filters-panel').slideToggle();
    });

    // Cambio de vista
    $('.flavor-view-mode').on('click', function() {
        currentView = $(this).data('mode');
        $('.flavor-view-mode').removeClass('active');
        $(this).addClass('active');
        renderizarCalendario();
    });

    // Navegación
    $('#btn-prev-period, #btn-next-period').on('click', function() {
        const direction = $(this).attr('id') === 'btn-prev-period' ? -1 : 1;
        if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + direction);
        } else if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + (7 * direction));
        } else if (currentView === 'day') {
            currentDate.setDate(currentDate.getDate() + direction);
        }
        renderizarCalendario();
        actualizarTitulo();
    });

    $('#btn-today').on('click', function() {
        currentDate = new Date();
        renderizarCalendario();
        actualizarTitulo();
    });

    // Aplicar filtros
    $('#btn-aplicar-filtros').on('click', function() {
        renderizarCalendario();
        $('#advanced-filters-panel').slideUp();
    });

    // Limpiar filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('input[name="estado-filter[]"]').prop('checked', true);
        $('#filter-hora-desde').val('00:00');
        $('#filter-hora-hasta').val('23:59');
        $('#espacios-checkboxes input').prop('checked', true);
        renderizarCalendario();
    });

    // Exportar a ICS
    $('#btn-exportar-calendario').on('click', function() {
        var params = new URLSearchParams({
            action: 'espacios_comunes_exportar_calendario',
            mes: mesActual,
            anio: anioActual,
            nonce: '<?php echo wp_create_nonce('espacios_calendario_nonce'); ?>'
        });
        window.location.href = ajaxurl + '?' + params.toString();
    });

    // Imprimir
    $('#btn-imprimir-calendario').on('click', function() {
        window.print();
    });

    function cargarEspacios() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_listar_espacios' },
            success: function(response) {
                if (response.success) {
                    let checkboxes = '';
                    response.data.forEach(espacio => {
                        checkboxes += `<label><input type="checkbox" name="espacio-filter[]" value="<?php echo esc_attr__('${espacio.id}', 'flavor-chat-ia'); ?>" checked> ${espacio.nombre}</label>`;
                    });
                    $('#espacios-checkboxes').html(checkboxes);
                }
            }
        });
    }

    function cargarEstadisticas() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'espacios_comunes_obtener_estadisticas_calendario' },
            success: function(response) {
                if (response.success) {
                    $('#stat-hoy').text(response.data.hoy);
                    $('#stat-semana').text(response.data.semana);
                    $('#stat-mes').text(response.data.mes);
                    $('#stat-ocupacion').text(response.data.ocupacion + '%');
                }
            }
        });
    }

    function inicializarCalendario() {
        // Aquí se integraría FullCalendar o similar
        renderizarCalendario();
    }

    function renderizarCalendario() {
        const espaciosFilter = [];
        $('input[name="espacio-filter[]"]:checked').each(function() {
            espaciosFilter.push($(this).val());
        });

        const estadosFilter = [];
        $('input[name="estado-filter[]"]:checked').each(function() {
            estadosFilter.push($(this).val());
        });

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_obtener_eventos_calendario',
                fecha: currentDate.toISOString().split('T')[0],
                vista: currentView,
                espacios: espaciosFilter.join(','),
                estados: estadosFilter.join(','),
                hora_desde: $('#filter-hora-desde').val(),
                hora_hasta: $('#filter-hora-hasta').val()
            },
            success: function(response) {
                if (response.success) {
                    generarVistaCalendario(response.data);
                }
            }
        });
    }

    function generarVistaCalendario(eventos) {
        // Implementación básica - idealmente usar FullCalendar
        let html = '<div class="flavor-simple-calendar">';

        if (currentView === 'agenda') {
            html += generarVistaAgenda(eventos);
        } else {
            html += generarVistaCalendarioBasico(eventos);
        }

        html += '</div>';
        $('#calendar-container').html(html);
    }

    function generarVistaAgenda(eventos) {
        if (eventos.length === 0) {
            return '<p style="text-align: center; padding: 40px;"><?php _e('No hay reservas en este período', 'flavor-chat-ia'); ?></p>';
        }

        let html = '<div class="flavor-agenda-view">';
        let currentDate = '';

        eventos.forEach(evento => {
            if (evento.fecha !== currentDate) {
                if (currentDate !== '') html += '</div>';
                html += `<div class="flavor-agenda-date">
                    <h3>${new Date(evento.fecha).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h3>
                </div>
                <div class="flavor-agenda-events">`;
                currentDate = evento.fecha;
            }

            html += `
                <div class="flavor-agenda-event ${evento.estado}">
                    <div class="flavor-agenda-time">${evento.hora_inicio} - ${evento.hora_fin}</div>
                    <div class="flavor-agenda-details">
                        <strong>${evento.espacio_nombre}</strong>
                        <p>${evento.usuario_nombre}</p>
                        ${evento.proposito ? `<p class="flavor-agenda-purpose">${evento.proposito}</p>` : ''}
                    </div>
                    <div class="flavor-agenda-status">
                        <span class="flavor-estado-badge ${evento.estado}">${evento.estado}</span>
                    </div>
                </div>
            `;
        });

        html += '</div></div>';
        return html;
    }

    function generarVistaCalendarioBasico(eventos) {
        return '<p style="text-align: center; padding: 40px; color: #999;"><?php _e('Vista de calendario - Integrar librería de calendario completa', 'flavor-chat-ia'); ?></p>';
    }

    function actualizarTitulo() {
        let titulo = '';
        if (currentView === 'month') {
            titulo = currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
        } else if (currentView === 'week') {
            titulo = '<?php _e('Semana', 'flavor-chat-ia'); ?> ' + getWeekNumber(currentDate) + ' - ' + currentDate.getFullYear();
        } else if (currentView === 'day') {
            titulo = currentDate.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        } else if (currentView === 'agenda') {
            titulo = '<?php _e('Agenda', 'flavor-chat-ia'); ?>';
        }
        $('#period-title').text(titulo);
    }

    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
        return Math.ceil((((d - yearStart) / 86400000) + 1)/7);
    }

    actualizarTitulo();
});
</script>
