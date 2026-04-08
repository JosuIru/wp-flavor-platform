/**
 * Unified Portal - JavaScript para interactividad
 *
 * @package FlavorChatIA
 * @since 4.3.0
 */

(function ($) {
	'use strict';

	console.log('[Flavor Unified Portal] Script cargado');

	// Namespace
	window.FlavorUnifiedPortal = window.FlavorUnifiedPortal || {};

	/**
     * Inicialización
     */
	FlavorUnifiedPortal.init = function () {
		this.bindEvents();
		this.initTooltips();
		this.initScrollIndicators();
	};

	/**
     * Vincular eventos
     */
	FlavorUnifiedPortal.bindEvents = function () {
		var self = this;

		// Acciones del header
		$(document).on('click', '.fup-header__actions [data-action]', function (e) {
			e.preventDefault();
			var action = $(this).data('action');
			self.handleHeaderAction(action);
		});

		// Expandir/contraer satélites en cards base
		$(document).on('click', '.fup-base-card__satellites-toggle', function (e) {
			e.preventDefault();
			var card = $(this).closest('.fup-base-card');
			card.toggleClass('fup-base-card--expanded');
		});

		// Refrescar datos
		$(document).on('click', '[data-action="refresh"]', function (e) {
			e.preventDefault();
			self.refreshData();
		});

		// Toggle del selector de layout
		$(document).on('click', '.fup-layout-selector__toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var selector = $(this).closest('.fup-layout-selector');
			selector.toggleClass('is-open');
			console.log('Toggle layout selector:', selector.hasClass('is-open') ? 'abierto' : 'cerrado');
		});

		// Seleccionar layout
		$(document).on('click', '.fup-layout-selector__option', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var layout = $(this).data('layout');
			console.log('Seleccionando layout:', layout);
			self.changeLayout(layout);
		});

		// Restablecer a configuración global
		$(document).on('click', '.fup-layout-selector__reset', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self.resetLayout();
		});

		// Cerrar selector al hacer clic fuera
		$(document).on('click', function (e) {
			if (!$(e.target).closest('.fup-layout-selector').length) {
				$('.fup-layout-selector').removeClass('is-open');
			}
		});
	};

	/**
     * Cambiar layout del portal
     */
	FlavorUnifiedPortal.changeLayout = function (layout) {
		var self = this;
		var selector = $('.fup-layout-selector');

		// Cerrar dropdown
		selector.removeClass('is-open');

		// Mostrar loading
		$('.flavor-unified-portal').addClass('fup--loading');

		// Guardar preferencia via AJAX
		$.ajax({
			url: flavorUnifiedPortal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_portal_save_layout',
				nonce: flavorUnifiedPortal.nonce,
				layout: layout
			},
			success: function (response) {
				if (response.success) {
					self.showToast(response.data.message || flavorUnifiedPortal.i18n.layoutSaved || 'Vista guardada');
					// Recargar la página para aplicar el nuevo layout
					setTimeout(function () {
						window.location.reload();
					}, 500);
				} else {
					self.showToast(response.data.message || 'Error al guardar');
					$('.flavor-unified-portal').removeClass('fup--loading');
				}
			},
			error: function () {
				self.showToast('Error de conexión');
				$('.flavor-unified-portal').removeClass('fup--loading');
			}
		});
	};

	/**
     * Restablecer a configuración global
     */
	FlavorUnifiedPortal.resetLayout = function () {
		var self = this;
		var selector = $('.fup-layout-selector');

		// Cerrar dropdown
		selector.removeClass('is-open');

		// Mostrar loading
		$('.flavor-unified-portal').addClass('fup--loading');

		// Restablecer preferencia via AJAX
		$.ajax({
			url: flavorUnifiedPortal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_portal_reset_layout',
				nonce: flavorUnifiedPortal.nonce
			},
			success: function (response) {
				if (response.success) {
					self.showToast(response.data.message || flavorUnifiedPortal.i18n.layoutReset || 'Usando configuración global');
					// Recargar la página para aplicar el layout global
					setTimeout(function () {
						window.location.reload();
					}, 500);
				} else {
					self.showToast(response.data.message || 'Error al restablecer');
					$('.flavor-unified-portal').removeClass('fup--loading');
				}
			},
			error: function () {
				self.showToast('Error de conexión');
				$('.flavor-unified-portal').removeClass('fup--loading');
			}
		});
	};

	/**
     * Manejar acciones del header
     */
	FlavorUnifiedPortal.handleHeaderAction = function (action) {
		switch (action) {
			case 'notifications':
				// Abrir panel de notificaciones
				if (typeof FlavorNotifications !== 'undefined') {
					FlavorNotifications.toggle();
				} else {
					this.showToast(flavorUnifiedPortal.i18n.notificationsUnavailable || 'Las notificaciones no están disponibles');
				}
				break;

			case 'settings':
				// Ir a configuración
				var settingsUrl = flavorUnifiedPortal.settingsUrl || '/mi-portal/configuracion/';
				window.location.href = settingsUrl;
				break;

			case 'refresh':
				this.refreshData();
				break;

			default:
				console.log('Acción no reconocida:', action);
		}
	};

	/**
     * Mostrar toast de feedback
     */
	FlavorUnifiedPortal.showToast = function (message) {
		var toast = $('.fup-toast');

		if (!toast.length) {
			toast = $('<div class="fup-toast"></div>').appendTo('body');
		}

		toast.text(message).addClass('fup-toast--visible');

		setTimeout(function () {
			toast.removeClass('fup-toast--visible');
		}, 3000);
	};

	/**
     * Refrescar datos via AJAX
     */
	FlavorUnifiedPortal.refreshData = function () {
		var self = this;
		var container = $('.flavor-unified-portal');

		container.addClass('fup--loading');

		$.ajax({
			url: flavorUnifiedPortal.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_portal_refresh',
				nonce: flavorUnifiedPortal.nonce
			},
			success: function (response) {
				if (response.success) {
					self.updateUI(response.data);
				} else {
					console.error('Error refreshing portal:', response.data.message);
				}
			},
			error: function () {
				console.error('Network error refreshing portal');
			},
			complete: function () {
				container.removeClass('fup--loading');
			}
		});
	};

	/**
     * Actualizar UI con nuevos datos
     */
	FlavorUnifiedPortal.updateUI = function (data) {
		// Actualizar estadísticas de red si existen
		if (data.network) {
			this.updateNetworkStats(data.network);
		}

		// Trigger evento para que otros scripts puedan reaccionar
		$(document).trigger('flavorPortalUpdated', [data]);
	};

	/**
     * Actualizar estadísticas de red
     */
	FlavorUnifiedPortal.updateNetworkStats = function (network) {
		var statsEl = $('.fup-header__network-stats');
		if (statsEl.length && network.nodes_count !== undefined) {
			statsEl.text(
				network.nodes_count + ' ' + flavorUnifiedPortal.i18n.nodes + ' · ' +
                network.communities_count + ' ' + flavorUnifiedPortal.i18n.communities
			);
		}
	};

	/**
     * Inicializar tooltips
     */
	FlavorUnifiedPortal.initTooltips = function () {
		// Usar title nativo o integrar con librería de tooltips si existe
		$('[title]').each(function () {
			var el = $(this);
			if (!el.data('tooltip-init')) {
				el.data('tooltip-init', true);
			}
		});
	};

	/**
     * Inicializar indicadores de scroll para elementos con overflow
     */
	FlavorUnifiedPortal.initScrollIndicators = function () {
		var self = this;

		// Elementos con scroll horizontal
		var scrollableSelectors = [
			'.fup-scroll-content',
			'.fup-transversal-bar',
			'.fup-satellites-list'
		];

		scrollableSelectors.forEach(function (selector) {
			$(selector).each(function () {
				self.setupScrollIndicator($(this));
			});
		});
	};

	/**
     * Configurar indicadores de scroll para un elemento
     */
	FlavorUnifiedPortal.setupScrollIndicator = function (scrollElement) {
		var self = this;
		var container = scrollElement.parent();

		// Añadir clase al contenedor si no la tiene
		if (!container.hasClass('fup-scroll-container')) {
			scrollElement.wrap('<div class="fup-scroll-container"></div>');
			container = scrollElement.parent();
		}

		function actualizarIndicadores() {
			var scrollLeft = scrollElement.scrollLeft();
			var scrollWidth = scrollElement[0].scrollWidth;
			var clientWidth = scrollElement[0].clientWidth;
			var umbral = 10;

			var puedeScrollIzquierda = scrollLeft > umbral;
			var puedeScrollDerecha = (scrollLeft + clientWidth) < (scrollWidth - umbral);

			container.toggleClass('has-scroll-left', puedeScrollIzquierda);
			container.toggleClass('has-scroll-right', puedeScrollDerecha);
		}

		// Detectar scroll
		scrollElement.on('scroll', actualizarIndicadores);

		// Actualizar al redimensionar
		$(window).on('resize', actualizarIndicadores);

		// Ejecutar una vez al cargar
		setTimeout(actualizarIndicadores, 100);
	};

	// Auto-inicializar cuando el DOM esté listo
	$(document).ready(function () {
		console.log('[Flavor Unified Portal] DOM ready, buscando .flavor-unified-portal:', $('.flavor-unified-portal').length);
		if ($('.flavor-unified-portal').length) {
			console.log('[Flavor Unified Portal] Inicializando...');
			FlavorUnifiedPortal.init();
			console.log('[Flavor Unified Portal] Inicializado correctamente');
		}
	});

})(jQuery);
