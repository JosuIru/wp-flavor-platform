/**
 * Portal de Usuario - JavaScript
 *
 * Búsqueda en tiempo real, notificaciones y acciones dinámicas.
 *
 * @package FlavorChatIA
 * @version 4.2.0
 */

(function ($) {
	'use strict';

	const FlavorUserPortal = {
		/**
         * Inicializa el portal
         */
		init: function () {
			this.initSearch();
			this.initNotifications();
			this.initModuleActions();
			this.initLazyLoading();
			this.initRefreshTimer();
		},

		/**
         * Inicializa la búsqueda universal
         */
		initSearch: function () {
			const $searchInput = $('#portal-search-input');
			const $searchResults = $('#portal-search-results');
			let searchTimeout;

			if ($searchInput.length === 0) {return;}

			// Búsqueda en tiempo real con debounce
			$searchInput.on('input', function () {
				const query = $(this).val().trim();

				clearTimeout(searchTimeout);

				if (query.length < 2) {
					$searchResults.hide();
					return;
				}

				searchTimeout = setTimeout(function () {
					FlavorUserPortal.performSearch(query, $searchResults);
				}, 300);
			});

			// Cerrar resultados al hacer clic fuera
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.portal-search').length) {
					$searchResults.hide();
				}
			});

			// Navegar con teclado (flechas arriba/abajo, Enter)
			$searchInput.on('keydown', function (e) {
				const $results = $searchResults.find('.search-result-item');
				const $active = $results.filter('.active');
				let $next;

				if (e.key === 'ArrowDown') {
					e.preventDefault();
					if ($active.length === 0) {
						$next = $results.first();
					} else {
						$next = $active.next('.search-result-item');
						if ($next.length === 0) {$next = $results.first();}
					}
					$results.removeClass('active');
					$next.addClass('active');
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					if ($active.length === 0) {
						$next = $results.last();
					} else {
						$next = $active.prev('.search-result-item');
						if ($next.length === 0) {$next = $results.last();}
					}
					$results.removeClass('active');
					$next.addClass('active');
				} else if (e.key === 'Enter') {
					e.preventDefault();
					const $selected = $results.filter('.active');
					if ($selected.length > 0) {
						window.location.href = $selected.data('url');
					}
				}
			});
		},

		/**
         * Ejecuta la búsqueda
         */
		performSearch: function (query, $resultsContainer) {
			$.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_search',
					nonce: flavorPortal.nonce,
					search: query,
				},
				beforeSend: function () {
					$resultsContainer.html('<div class="search-result-item"><div class="result-title">' + flavorPortal.strings.loading + '</div></div>').show();
				},
				success: function (response) {
					if (response.success && response.data.length > 0) {
						let html = '';
						response.data.forEach(function (result) {
							html += '<div class="search-result-item" data-url="' + result.url + '">';
							html += '<div class="result-module">' + result.module + '</div>';
							html += '<div class="result-title">' + result.title + '</div>';
							if (result.excerpt) {
								html += '<div class="result-excerpt">' + result.excerpt + '</div>';
							}
							html += '</div>';
						});
						$resultsContainer.html(html).show();

						// Click en resultado
						$resultsContainer.find('.search-result-item').on('click', function () {
							window.location.href = $(this).data('url');
						});
					} else {
						$resultsContainer.html('<div class="search-result-item"><div class="result-title">' + flavorPortal.strings.no_results + '</div></div>').show();
					}
				},
				error: function () {
					$resultsContainer.html('<div class="search-result-item"><div class="result-title">' + flavorPortal.strings.error + '</div></div>').show();
				}
			});
		},

		/**
         * Inicializa notificaciones en tiempo real
         */
		initNotifications: function () {
			const $notifContainer = $('#portal-notifications');
			if ($notifContainer.length === 0) {return;}

			// Cargar notificaciones cada 2 minutos
			this.loadNotifications();
			setInterval(function () {
				FlavorUserPortal.loadNotifications();
			}, 120000); // 2 minutos
		},

		/**
         * Carga las notificaciones
         */
		loadNotifications: function () {
			$.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_get_notifications',
					nonce: flavorPortal.nonce,
					limit: 5,
				},
				success: function (response) {
					if (response.success && response.data.length > 0) {
						FlavorUserPortal.renderNotifications(response.data);
					}
				}
			});
		},

		/**
         * Renderiza notificaciones
         */
		renderNotifications: function (notifications) {
			const $container = $('#portal-notifications');
			const $list = $container.find('.portal-notifications-list');

			if ($list.length === 0) {
				$container.html('<div class="portal-notifications-list"></div>');
			}

			let html = '';
			notifications.forEach(function (notif) {
				const typeClass = 'dm-alert--' + (notif.type || 'info');
				html += '<div class="dm-alert ' + typeClass + '" data-notif-id="' + (notif.id || '') + '">';
				html += '<strong>' + notif.title + '</strong><br>';
				html += '<small>' + notif.message + '</small>';
				html += '<button class="dm-alert__close">&times;</button>';
				html += '</div>';
			});

			$container.find('.portal-notifications-list').html(html);

			// Cerrar notificación
			$container.find('.dm-alert__close').on('click', function () {
				const $alert = $(this).closest('.dm-alert');
				const notifId = $alert.data('notif-id');

				$alert.fadeOut(300, function () {
					$(this).remove();
				});

				if (notifId) {
					FlavorUserPortal.markNotificationRead(notifId);
				}
			});
		},

		/**
         * Marca notificación como leída
         */
		markNotificationRead: function (notifId) {
			$.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_mark_notification_read',
					nonce: flavorPortal.nonce,
					notif_id: notifId,
				},
			});
		},

		/**
         * Inicializa acciones de módulos
         */
		initModuleActions: function () {
			// Acciones rápidas
			$('.quick-action-btn').on('click', function (e) {
				const $btn = $(this);
				$btn.addClass('loading');
			});

			// Enlaces de acción de módulo
			$('.portal-action-link').on('mouseenter', function () {
				$(this).css('transform', 'translateX(4px)');
			}).on('mouseleave', function () {
				$(this).css('transform', 'translateX(0)');
			});
		},

		/**
         * Lazy loading de widgets
         */
		initLazyLoading: function () {
			if ('IntersectionObserver' in window) {
				const observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							const $section = $(entry.target);
							$section.addClass('portal-module-visible');
							observer.unobserve(entry.target);
						}
					});
				}, {
					threshold: 0.1
				});

				$('.dm-section').each(function () {
					observer.observe(this);
				});
			}
		},

		/**
         * Timer de auto-refresh
         */
		initRefreshTimer: function () {
			// Opcional: refresh automático de stats cada 5 minutos
			// Desactivado por defecto para no consumir recursos
			if (window.flavorPortalAutoRefresh === true) {
				setInterval(function () {
					FlavorUserPortal.refreshStats();
				}, 300000); // 5 minutos
			}
		},

		/**
         * Refresca las estadísticas
         */
		refreshStats: function () {
			const $stats = $('.portal-stats');
			if ($stats.length === 0) {return;}

			$.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_get_module_data',
					nonce: flavorPortal.nonce,
					module: 'stats',
				},
				success: function (response) {
					if (response.success && response.data.html) {
						$stats.html(response.data.html);
						// Reiniciar animaciones
						FlavorDashboard.initCounters();
					}
				}
			});
		},

		/**
         * Ejecuta acción rápida inter-módulo
         */
		quickAction: function (actionType, module, data) {
			return $.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_quick_action',
					nonce: flavorPortal.nonce,
					action_type: actionType,
					module: module,
					data: data,
				}
			});
		},

		/**
         * Guarda preferencias de widgets del usuario
         */
		saveWidgetPreferences: function (preferences) {
			$.ajax({
				url: flavorPortal.ajax_url,
				type: 'POST',
				data: {
					action: 'flavor_portal_save_widget_prefs',
					nonce: flavorPortal.nonce,
					preferences: preferences,
				},
				success: function (response) {
					if (response.success) {
						console.log('Preferencias guardadas');
					}
				}
			});
		}
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function () {
		if ($('.flavor-user-portal').length > 0) {
			FlavorUserPortal.init();
		}
	});

	// Exponer globalmente para uso externo
	window.FlavorUserPortal = FlavorUserPortal;

})(jQuery);
