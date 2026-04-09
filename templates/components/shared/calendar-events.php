<?php
/**
 * Componente: Calendar Events
 *
 * Calendario interactivo con eventos/citas usando FullCalendar o vista simple.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $events     Array de eventos: [['id' => x, 'title' => '', 'start' => '', 'end' => '', 'color' => '', 'url' => '']]
 * @param string $view       Vista inicial: month, week, day, list
 * @param string $module     Módulo para cargar eventos via AJAX
 * @param bool   $editable   Permitir arrastrar/redimensionar eventos
 * @param bool   $selectable Permitir seleccionar rangos de fechas
 * @param string $default_date Fecha inicial (YYYY-MM-DD)
 * @param array  $business_hours Horario laboral: ['start' => '09:00', 'end' => '18:00', 'daysOfWeek' => [1,2,3,4,5]]
 * @param string $locale     Locale del calendario
 * @param string $height     Altura del calendario (auto, 600px, etc.)
 * @param string $id         ID único del calendario
 */

if (!defined('ABSPATH')) {
    exit;
}

$events = $events ?? [];
$view = $view ?? 'month';
$module = $module ?? '';
$editable = $editable ?? false;
$selectable = $selectable ?? false;
$default_date = $default_date ?? date('Y-m-d');
$business_hours = $business_hours ?? null;
$locale = $locale ?? substr(get_locale(), 0, 2);
$height = $height ?? 'auto';
$calendar_id = $id ?? 'flavor-calendar-' . wp_rand(1000, 9999);

// Mapear vistas
$view_map = [
    'month' => 'dayGridMonth',
    'week'  => 'timeGridWeek',
    'day'   => 'timeGridDay',
    'list'  => 'listWeek',
];
$initial_view = $view_map[$view] ?? 'dayGridMonth';

// Colores para categorías
$category_colors = [
    'default' => '#3B82F6',
    'evento'  => '#8B5CF6',
    'reunion' => '#10B981',
    'taller'  => '#F59E0B',
    'curso'   => '#EF4444',
    'reserva' => '#06B6D4',
    'cita'    => '#EC4899',
];
?>

<div class="flavor-calendar-events">

    <!-- Header con navegación rápida -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4 p-4 bg-white rounded-xl shadow-sm">
        <div class="flex items-center gap-2">
            <button type="button" class="fc-custom-prev p-2 rounded-lg hover:bg-gray-100 transition-colors" title="<?php esc_attr_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button type="button" class="fc-custom-today px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="fc-custom-next p-2 rounded-lg hover:bg-gray-100 transition-colors" title="<?php esc_attr_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <h2 class="fc-custom-title text-lg font-semibold text-gray-900 ml-2"></h2>
        </div>

        <div class="flex items-center gap-2">
            <!-- Selector de vista -->
            <div class="inline-flex rounded-lg border border-gray-200 p-0.5 bg-gray-50">
                <button type="button" class="fc-view-btn px-3 py-1.5 text-sm font-medium rounded-md transition-all" data-view="dayGridMonth">
                    <?php esc_html_e('Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="fc-view-btn px-3 py-1.5 text-sm font-medium rounded-md transition-all" data-view="timeGridWeek">
                    <?php esc_html_e('Semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="fc-view-btn px-3 py-1.5 text-sm font-medium rounded-md transition-all" data-view="listWeek">
                    <?php esc_html_e('Lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- Botón añadir (si es editable) -->
            <?php if ($editable || $selectable): ?>
                <button type="button" class="fc-add-event inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php esc_html_e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenedor del calendario -->
    <div id="<?php echo esc_attr($calendar_id); ?>"
         class="bg-white rounded-xl shadow-lg p-4"
         data-events='<?php echo esc_attr(json_encode($events)); ?>'
         data-module="<?php echo esc_attr($module); ?>"
         data-view="<?php echo esc_attr($initial_view); ?>"
         data-date="<?php echo esc_attr($default_date); ?>"
         data-editable="<?php echo $editable ? 'true' : 'false'; ?>"
         data-selectable="<?php echo $selectable ? 'true' : 'false'; ?>"
         data-locale="<?php echo esc_attr($locale); ?>"
         data-height="<?php echo esc_attr($height); ?>"
         <?php if ($business_hours): ?>
         data-business-hours='<?php echo esc_attr(json_encode($business_hours)); ?>'
         <?php endif; ?>>
    </div>

    <!-- Mini leyenda -->
    <div class="flex flex-wrap items-center gap-4 mt-4 px-4 py-3 bg-gray-50 rounded-lg text-sm">
        <span class="text-gray-500"><?php esc_html_e('Categorías:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <?php foreach (array_slice($category_colors, 0, 5) as $cat => $color): ?>
            <?php if ($cat !== 'default'): ?>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full" style="background-color: <?php echo esc_attr($color); ?>;"></span>
                    <span class="text-gray-700"><?php echo esc_html(ucfirst($cat)); ?></span>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para crear/editar evento -->
<div id="<?php echo esc_attr($calendar_id); ?>-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 fc-modal-backdrop"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <button type="button" class="fc-modal-close absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <h3 class="text-lg font-semibold text-gray-900 mb-4 fc-modal-title">
                <?php esc_html_e('Nuevo evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>

            <form class="fc-event-form space-y-4">
                <input type="hidden" name="event_id" value="">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *
                    </label>
                    <input type="text" name="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *
                        </label>
                        <input type="datetime-local" name="start" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="datetime-local" name="end"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($category_colors as $cat => $color): ?>
                            <option value="<?php echo esc_attr($cat); ?>" data-color="<?php echo esc_attr($color); ?>">
                                <?php echo esc_html(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="all_day" id="<?php echo esc_attr($calendar_id); ?>-allday"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="<?php echo esc_attr($calendar_id); ?>-allday" class="text-sm text-gray-700">
                        <?php esc_html_e('Todo el día', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" class="fc-modal-close px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <?php esc_html_e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos personalizados para FullCalendar */
.fc .fc-toolbar-title { font-size: 1.1rem !important; }
.fc .fc-button { padding: 0.4rem 0.8rem !important; font-size: 0.875rem !important; }
.fc-event { cursor: pointer !important; border-radius: 4px !important; }
.fc-daygrid-event { padding: 2px 4px !important; }
.fc-timegrid-event { border-radius: 4px !important; }
.fc-list-event { cursor: pointer !important; }
.fc-day-today { background-color: #EFF6FF !important; }
.fc-highlight { background-color: #DBEAFE !important; }
.fc-non-business { background-color: #F9FAFB !important; }

/* Ocultar toolbar original de FullCalendar (usamos el custom) */
.fc .fc-toolbar { display: none !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('<?php echo esc_js($calendar_id); ?>');
    if (!calendarEl || typeof FullCalendar === 'undefined') {
        console.warn('FullCalendar no disponible');
        return;
    }

    const config = {
        events: JSON.parse(calendarEl.dataset.events || '[]'),
        module: calendarEl.dataset.module,
        initialView: calendarEl.dataset.view,
        initialDate: calendarEl.dataset.date,
        editable: calendarEl.dataset.editable === 'true',
        selectable: calendarEl.dataset.selectable === 'true',
        locale: calendarEl.dataset.locale,
        height: calendarEl.dataset.height,
        businessHours: calendarEl.dataset.businessHours ? JSON.parse(calendarEl.dataset.businessHours) : false
    };

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: config.initialView,
        initialDate: config.initialDate,
        locale: config.locale,
        height: config.height === 'auto' ? 'auto' : parseInt(config.height),
        editable: config.editable,
        selectable: config.selectable,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        businessHours: config.businessHours,
        nowIndicator: true,
        navLinks: true,
        headerToolbar: false, // Usamos toolbar custom

        events: config.module ? {
            url: flavorAjax.url,
            method: 'POST',
            extraParams: function() {
                return {
                    action: 'flavor_get_calendar_events',
                    module: config.module,
                    _wpnonce: flavorAjax.nonce
                };
            },
            failure: function() {
                console.error('Error cargando eventos');
            }
        } : config.events,

        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            } else if (config.editable) {
                openEventModal(info.event);
            }
        },

        select: function(info) {
            if (config.selectable) {
                openEventModal(null, info.start, info.end, info.allDay);
            }
        },

        eventDrop: function(info) {
            updateEvent(info.event);
        },

        eventResize: function(info) {
            updateEvent(info.event);
        },

        datesSet: function(info) {
            // Actualizar título custom
            document.querySelector('.fc-custom-title').textContent = info.view.title;

            // Actualizar botones de vista activa
            document.querySelectorAll('.fc-view-btn').forEach(btn => {
                btn.classList.toggle('bg-white', btn.dataset.view === info.view.type);
                btn.classList.toggle('shadow-sm', btn.dataset.view === info.view.type);
                btn.classList.toggle('text-gray-900', btn.dataset.view === info.view.type);
                btn.classList.toggle('text-gray-500', btn.dataset.view !== info.view.type);
            });
        }
    });

    calendar.render();

    // Navegación custom
    document.querySelector('.fc-custom-prev').addEventListener('click', () => calendar.prev());
    document.querySelector('.fc-custom-next').addEventListener('click', () => calendar.next());
    document.querySelector('.fc-custom-today').addEventListener('click', () => calendar.today());

    // Cambio de vista
    document.querySelectorAll('.fc-view-btn').forEach(btn => {
        btn.addEventListener('click', () => calendar.changeView(btn.dataset.view));
    });

    // Modal
    const modal = document.getElementById('<?php echo esc_js($calendar_id); ?>-modal');
    const form = modal.querySelector('.fc-event-form');

    function openEventModal(event = null, start = null, end = null, allDay = false) {
        const title = modal.querySelector('.fc-modal-title');

        if (event) {
            title.textContent = '<?php esc_html_e('Editar evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
            form.querySelector('[name="event_id"]').value = event.id;
            form.querySelector('[name="title"]').value = event.title;
            form.querySelector('[name="start"]').value = formatDateTimeLocal(event.start);
            form.querySelector('[name="end"]').value = event.end ? formatDateTimeLocal(event.end) : '';
            form.querySelector('[name="description"]').value = event.extendedProps?.description || '';
            form.querySelector('[name="all_day"]').checked = event.allDay;
        } else {
            title.textContent = '<?php esc_html_e('Nuevo evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>';
            form.reset();
            form.querySelector('[name="event_id"]').value = '';
            if (start) form.querySelector('[name="start"]').value = formatDateTimeLocal(start);
            if (end) form.querySelector('[name="end"]').value = formatDateTimeLocal(end);
            form.querySelector('[name="all_day"]').checked = allDay;
        }

        modal.classList.remove('hidden');
    }

    function formatDateTimeLocal(date) {
        const d = new Date(date);
        return d.toISOString().slice(0, 16);
    }

    // Cerrar modal
    modal.querySelectorAll('.fc-modal-close, .fc-modal-backdrop').forEach(el => {
        el.addEventListener('click', () => modal.classList.add('hidden'));
    });

    // Botón añadir
    const addBtn = document.querySelector('.fc-add-event');
    if (addBtn) {
        addBtn.addEventListener('click', () => openEventModal());
    }

    // Submit formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const eventData = {
            id: formData.get('event_id'),
            title: formData.get('title'),
            start: formData.get('start'),
            end: formData.get('end') || null,
            allDay: formData.get('all_day') === 'on',
            category: formData.get('category'),
            description: formData.get('description')
        };

        // Enviar via AJAX
        fetch(flavorAjax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'flavor_save_calendar_event',
                module: config.module,
                event: JSON.stringify(eventData),
                _wpnonce: flavorAjax.nonce
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                calendar.refetchEvents();
                modal.classList.add('hidden');
            } else {
                alert(data.data?.message || 'Error al guardar');
            }
        });
    });

    function updateEvent(event) {
        fetch(flavorAjax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'flavor_update_calendar_event',
                module: config.module,
                event_id: event.id,
                start: event.start.toISOString(),
                end: event.end ? event.end.toISOString() : '',
                _wpnonce: flavorAjax.nonce
            })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                calendar.refetchEvents(); // Revertir cambios
            }
        });
    }

    // Exponer calendario para uso externo
    window['flavorCalendar_<?php echo esc_js($calendar_id); ?>'] = calendar;
});
</script>
