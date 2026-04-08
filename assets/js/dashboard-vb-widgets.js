/**
 * Dashboard Visual Builder Widgets - Frontend JavaScript
 *
 * Maneja la interactividad de los widgets de dashboard.
 *
 * @package FlavorChatIA
 * @since 4.0.0
 */

(function ($) {
	'use strict';

	/**
     * Namespace principal para widgets de dashboard
     */
	window.FVBDashboardWidgets = window.FVBDashboardWidgets || {};

	/**
     * Configuracion del modulo
     */
	const config = window.fvbDashboardWidgets || {
		ajaxUrl: '/wp-admin/admin-ajax.php',
		nonce: '',
		userId: 0,
		i18n: {
			cargando: 'Cargando...',
			error: 'Error al cargar',
			actualizado: 'Actualizado'
		}
	};

	/**
     * Cache de datos de widgets
     */
	const widgetCache = new Map();

	/**
     * Inicializar widgets de dashboard
     */
	FVBDashboardWidgets.init = function () {
		this.initWidgets();
		this.bindEvents();
		this.loadAjaxWidgets();
	};

	/**
     * Inicializar cada widget
     */
	FVBDashboardWidgets.initWidgets = function () {
		$('.fvb-widget').each(function () {
			const widgetElement = $(this);
			const widgetId = widgetElement.data('widget-id');

			if (widgetId) {
				FVBDashboardWidgets.initWidget(widgetElement, widgetId);
			}
		});
	};

	/**
     * Inicializar un widget individual
     */
	FVBDashboardWidgets.initWidget = function (widgetElement, widgetId) {
		// Marcar como inicializado
		widgetElement.data('initialized', true);

		// Inicializar componentes especiales segun tipo de widget
		if (widgetElement.find('[data-chart]').length) {
			this.initCharts(widgetElement);
		}

		if (widgetElement.find('[data-map]').length) {
			this.initMaps(widgetElement);
		}

		if (widgetElement.find('[data-calendar]').length) {
			this.initCalendars(widgetElement);
		}
	};

	/**
     * Vincular eventos
     */
	FVBDashboardWidgets.bindEvents = function () {
		// Boton de actualizar widget
		$(document).on('click', '.fvb-widget__refresh', function (evento) {
			evento.preventDefault();
			const widgetElement = $(this).closest('.fvb-widget');
			const widgetId = widgetElement.data('widget-id');
			FVBDashboardWidgets.refreshWidget(widgetId, widgetElement);
		});

		// Cargar contenido via AJAX cuando el widget entra en viewport
		if ('IntersectionObserver' in window) {
			const observer = new IntersectionObserver((entries) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						const widgetElement = $(entry.target);
						const widgetId = widgetElement.data('widget-id');

						if (widgetElement.find('[data-ajax-load="true"]').length && !widgetElement.data('loaded')) {
							FVBDashboardWidgets.loadWidgetContent(widgetId, widgetElement);
							observer.unobserve(entry.target);
						}
					}
				});
			}, { threshold: 0.1 });

			$('.fvb-widget[data-widget-id]').each(function () {
				observer.observe(this);
			});
		} else {
			// Fallback para navegadores antiguos
			this.loadAjaxWidgets();
		}

		// Eventos de notificaciones en tiempo real (si WebSocket disponible)
		if (window.FlavorWebSocket) {
			window.FlavorWebSocket.on('widget_update', function (data) {
				if (data.widget_id) {
					const widgetElement = $('.fvb-widget[data-widget-id="' + data.widget_id + '"]');
					if (widgetElement.length) {
						FVBDashboardWidgets.updateWidgetContent(widgetElement, data.html);
					}
				}
			});
		}
	};

	/**
     * Cargar widgets que requieren AJAX
     */
	FVBDashboardWidgets.loadAjaxWidgets = function () {
		$('.fvb-widget [data-ajax-load="true"]').each(function () {
			const contenidoElement = $(this);
			const widgetElement = contenidoElement.closest('.fvb-widget');
			const widgetId = widgetElement.data('widget-id');

			if (!widgetElement.data('loaded')) {
				FVBDashboardWidgets.loadWidgetContent(widgetId, widgetElement);
			}
		});
	};

	/**
     * Cargar contenido de un widget via AJAX
     */
	FVBDashboardWidgets.loadWidgetContent = function (widgetId, widgetElement) {
		// Verificar cache
		if (widgetCache.has(widgetId)) {
			const cached = widgetCache.get(widgetId);
			if (Date.now() - cached.timestamp < 60000) { // Cache de 1 minuto
				this.updateWidgetContent(widgetElement, cached.html);
				return;
			}
		}

		// Mostrar estado de carga
		widgetElement.addClass('is-loading');

		$.ajax({
			url: config.ajaxUrl,
			type: 'POST',
			data: {
				action: 'fvb_dashboard_widget_data',
				widget_id: widgetId,
				nonce: config.nonce
			},
			success: function (response) {
				widgetElement.removeClass('is-loading');
				widgetElement.data('loaded', true);

				if (response.success && response.data) {
					const html = response.data.html || '';
					FVBDashboardWidgets.updateWidgetContent(widgetElement, html);

					// Guardar en cache
					widgetCache.set(widgetId, {
						html: html,
						timestamp: Date.now()
					});

					// Trigger evento personalizado
					widgetElement.trigger('fvb:widget:loaded', [widgetId, response.data]);
				} else {
					FVBDashboardWidgets.showWidgetError(widgetElement);
				}
			},
			error: function () {
				widgetElement.removeClass('is-loading');
				FVBDashboardWidgets.showWidgetError(widgetElement);
			}
		});
	};

	/**
     * Actualizar contenido de widget
     */
	FVBDashboardWidgets.updateWidgetContent = function (widgetElement, html) {
		const contenidoElement = widgetElement.find('.fvb-widget__content');
		contenidoElement.html(html);

		// Re-inicializar componentes especiales
		this.initWidget(widgetElement, widgetElement.data('widget-id'));
	};

	/**
     * Mostrar error en widget
     */
	FVBDashboardWidgets.showWidgetError = function (widgetElement) {
		const contenidoElement = widgetElement.find('.fvb-widget__content');
		contenidoElement.html('<p class="fvb-widget__error">' + config.i18n.error + '</p>');
	};

	/**
     * Refrescar widget
     */
	FVBDashboardWidgets.refreshWidget = function (widgetId, widgetElement) {
		const botonRefresh = widgetElement.find('.fvb-widget__refresh');
		botonRefresh.addClass('is-loading');

		// Invalidar cache
		widgetCache.delete(widgetId);
		widgetElement.data('loaded', false);

		// Recargar
		this.loadWidgetContent(widgetId, widgetElement);

		// Quitar clase de loading del boton despues de un tiempo
		setTimeout(function () {
			botonRefresh.removeClass('is-loading');
		}, 1000);
	};

	/**
     * Inicializar graficos dentro de widgets
     */
	FVBDashboardWidgets.initCharts = function (widgetElement) {
		const chartElements = widgetElement.find('[data-chart]');

		chartElements.each(function () {
			const chartElement = $(this);
			const chartType = chartElement.data('chart');
			const chartData = chartElement.data('chart-data');

			if (typeof Chart !== 'undefined' && chartData) {
				const ctx = chartElement[0].getContext('2d');
				new Chart(ctx, {
					type: chartType,
					data: chartData,
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								display: false
							}
						}
					}
				});
			}
		});
	};

	/**
     * Inicializar mapas dentro de widgets
     */
	FVBDashboardWidgets.initMaps = function (widgetElement) {
		const mapElements = widgetElement.find('[data-map]');

		mapElements.each(function () {
			const mapElement = $(this);
			const mapConfig = mapElement.data('map-config') || {};

			if (typeof L !== 'undefined') {
				const lat = mapConfig.lat || 40.4168;
				const lng = mapConfig.lng || -3.7038;
				const zoom = mapConfig.zoom || 13;

				const map = L.map(mapElement[0]).setView([lat, lng], zoom);

				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; OpenStreetMap'
				}).addTo(map);

				// Agregar marcadores si existen
				if (mapConfig.markers && Array.isArray(mapConfig.markers)) {
					mapConfig.markers.forEach(function (marker) {
						L.marker([marker.lat, marker.lng])
							.addTo(map)
							.bindPopup(marker.popup || '');
					});
				}

				// Guardar referencia al mapa
				mapElement.data('leaflet-map', map);
			}
		});
	};

	/**
     * Inicializar calendarios dentro de widgets
     */
	FVBDashboardWidgets.initCalendars = function (widgetElement) {
		const calendarElements = widgetElement.find('[data-calendar]');

		calendarElements.each(function () {
			const calendarElement = $(this);
			const calendarConfig = calendarElement.data('calendar-config') || {};

			if (typeof FullCalendar !== 'undefined') {
				const calendar = new FullCalendar.Calendar(calendarElement[0], {
					initialView: calendarConfig.view || 'dayGridMonth',
					locale: calendarConfig.locale || 'es',
					headerToolbar: {
						left: 'prev,next today',
						center: 'title',
						right: 'dayGridMonth,timeGridWeek,listWeek'
					},
					events: calendarConfig.events || [],
					height: 'auto'
				});

				calendar.render();
				calendarElement.data('fullcalendar', calendar);
			}
		});
	};

	/**
     * Utilidad: Formatear numero
     */
	FVBDashboardWidgets.formatNumber = function (num) {
		if (num >= 1000000) {
			return (num / 1000000).toFixed(1) + 'M';
		}
		if (num >= 1000) {
			return (num / 1000).toFixed(1) + 'K';
		}
		return num.toString();
	};

	/**
     * Utilidad: Formatear fecha relativa
     */
	FVBDashboardWidgets.formatRelativeDate = function (date) {
		const now = new Date();
		const diff = now - new Date(date);
		const seconds = Math.floor(diff / 1000);
		const minutes = Math.floor(seconds / 60);
		const hours = Math.floor(minutes / 60);
		const days = Math.floor(hours / 24);

		if (days > 0) {return days + 'd';}
		if (hours > 0) {return hours + 'h';}
		if (minutes > 0) {return minutes + 'm';}
		return 'ahora';
	};

	/**
     * Inicializar cuando el DOM este listo
     */
	$(document).ready(function () {
		FVBDashboardWidgets.init();
	});

})(jQuery);
