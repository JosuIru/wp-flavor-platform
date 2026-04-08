/**
 * Dashboard Unificado - Widget Manager
 *
 * Gestiona widgets, drag-drop, personalizacion y actualizacion
 * automatica del dashboard unificado.
 *
 * @package FlavorChatIA
 * @since 4.0.0
 */

(function ($, FlavorDashboard, window) {
	'use strict';

	/**
     * Widget Manager
     */
	FlavorDashboard.Widgets = {
		/**
         * Widgets registrados
         */
		registry: new Map(),

		/**
         * Contenedor de widgets
         */
		$container: null,

		/**
         * Timer de auto-refresh
         */
		refreshTimer: null,

		/**
         * Timestamp de ultima actualizacion
         */
		lastRefresh: null,

		/**
         * Estado de inicializacion
         */
		initialized: false,

		/**
         * Inicializa el widget manager
         */
		init: function () {
			if (this.initialized) {
				return;
			}

			this.$container = $('#fud-widgets-container');

			if (!this.$container.length) {
				FlavorDashboard.debug.warn('Contenedor de widgets no encontrado');
				return;
			}

			this.bindEvents();
			this.initSortable();
			this.initAutoRefresh();
			this.updateLastRefreshDisplay();

			this.initialized = true;
			FlavorDashboard.debug.log('Widget Manager inicializado');
			FlavorDashboard.events.emit('widgets:initialized');
		},

		/**
         * Vincula eventos
         */
		bindEvents: function () {
			var self = this;

			// Refrescar widget individual
			$(document).on('click', '.fud-widget__action[data-action="refresh"]', function (e) {
				e.preventDefault();
				var $widget = $(this).closest('.fud-widget');
				self.refreshWidget($widget.data('widget-id'));
			});

			// Colapsar/expandir widget
			$(document).on('click', '.fud-widget__action[data-action="collapse"]', function (e) {
				e.preventDefault();
				var $widget = $(this).closest('.fud-widget');
				self.toggleWidget($widget);
			});

			// Cerrar/ocultar widget
			$(document).on('click', '.fud-widget__action[data-action="close"]', function (e) {
				e.preventDefault();
				var $widget = $(this).closest('.fud-widget');
				self.hideWidget($widget);
			});

			// Refrescar todo
			$('#fud-refresh-all').on('click', function (e) {
				e.preventDefault();
				self.refreshAll();
			});

			// Abrir modal de personalizacion
			$('#fud-customize, #fud-show-customize').on('click', function (e) {
				e.preventDefault();
				FlavorDashboard.Customize.openModal();
			});

			// Filtro por categoria
			$('#fud-category-select').on('change', function () {
				var categoria = $(this).val();
				self.filterByCategory(categoria);
			});
		},

		/**
         * Inicializa jQuery UI Sortable
         */
		initSortable: function () {
			var self = this;

			if (!this.$container.length || !$.fn.sortable) {
				FlavorDashboard.debug.warn('jQuery UI Sortable no disponible');
				return;
			}

			this.$container.sortable({
				items: '.fud-widget--draggable',
				handle: '.fud-widget__drag-handle',
				placeholder: 'fud-widget-placeholder',
				tolerance: 'pointer',
				cursor: 'grabbing',
				opacity: 0.8,
				revert: 200,

				start: function (event, ui) {
					ui.placeholder.height(ui.helper.outerHeight());
					ui.placeholder.width(ui.helper.outerWidth());
					FlavorDashboard.events.emit('widgets:sortstart', { item: ui.item });
				},

				stop: function (event, ui) {
					self.saveLayout();
					FlavorDashboard.events.emit('widgets:sortstop', { item: ui.item });
				}
			});
		},

		/**
         * Inicializa auto-refresh
         */
		initAutoRefresh: function () {
			var self = this;
			var intervalo = FlavorDashboard.config.refreshInterval;

			if (intervalo > 0) {
				this.refreshTimer = setInterval(function () {
					self.refreshAll(true); // silencioso
				}, intervalo);

				FlavorDashboard.debug.log('Auto-refresh activado cada ' + (intervalo / 1000) + 's');
			}
		},

		/**
         * Detiene auto-refresh
         */
		stopAutoRefresh: function () {
			if (this.refreshTimer) {
				clearInterval(this.refreshTimer);
				this.refreshTimer = null;
			}
		},

		/**
         * Refresca un widget individual
         */
		refreshWidget: function (widgetId) {
			var self = this;
			var $widget = this.$container.find('[data-widget-id="' + widgetId + '"]');

			if (!$widget.length) {
				FlavorDashboard.debug.warn('Widget no encontrado:', widgetId);
				return;
			}

			var $body = $widget.find('.fud-widget__body');
			var $refreshBtn = $widget.find('.fud-widget__action[data-action="refresh"]');

			// Indicador de carga
			$refreshBtn.addClass('is-refreshing');
			$widget.addClass('is-loading');

			FlavorDashboard.ajax.post('fud_refresh_widget', {
				widget_id: widgetId
			})
				.done(function (response) {
					if (response.success && response.data.html) {
						$body.html(response.data.html);
						FlavorDashboard.events.emit('widget:refreshed', {
							widgetId: widgetId,
							data: response.data
						});
					} else {
						FlavorDashboard.toast.error(response.data?.message || 'Error al actualizar');
					}
				})
				.fail(function () {
					FlavorDashboard.toast.error(FlavorDashboard.config.i18n.error || 'Error de conexion');
				})
				.always(function () {
					$refreshBtn.removeClass('is-refreshing');
					$widget.removeClass('is-loading');
				});
		},

		/**
         * Refresca todos los widgets
         */
		refreshAll: function (silent) {
			var self = this;
			var $button = $('#fud-refresh-all');

			if (!silent) {
				$button.prop('disabled', true).find('.dashicons').addClass('fud-animate-spin');
				FlavorDashboard.toast.info(FlavorDashboard.config.i18n.refreshing || 'Actualizando...');
			}

			FlavorDashboard.ajax.post('fud_refresh_all')
				.done(function (response) {
					if (response.success) {
						// Actualizar cada widget
						$.each(response.data.widgets, function (widgetId, widgetData) {
							var $widget = self.$container.find('[data-widget-id="' + widgetId + '"]');
							if ($widget.length && widgetData.html) {
								$widget.find('.fud-widget__body').html(widgetData.html);
							}
						});

						self.lastRefresh = new Date();
						self.updateLastRefreshDisplay();

						if (!silent) {
							FlavorDashboard.toast.success(FlavorDashboard.config.i18n.refreshed || 'Datos actualizados');
						}

						FlavorDashboard.events.emit('widgets:refreshed', response.data);
					}
				})
				.fail(function () {
					if (!silent) {
						FlavorDashboard.toast.error(FlavorDashboard.config.i18n.error || 'Error de conexion');
					}
				})
				.always(function () {
					$button.prop('disabled', false).find('.dashicons').removeClass('fud-animate-spin');
				});
		},

		/**
         * Colapsa/expande un widget
         */
		toggleWidget: function ($widget) {
			$widget.toggleClass('fud-widget--collapsed');

			var widgetId = $widget.data('widget-id');
			var isCollapsed = $widget.hasClass('fud-widget--collapsed');

			// Guardar estado
			var colapsados = FlavorDashboard.storage.get('collapsed_widgets', []);

			if (isCollapsed) {
				if (colapsados.indexOf(widgetId) === -1) {
					colapsados.push(widgetId);
				}
			} else {
				colapsados = colapsados.filter(function (id) { return id !== widgetId; });
			}

			FlavorDashboard.storage.set('collapsed_widgets', colapsados);
			FlavorDashboard.events.emit('widget:toggled', { widgetId: widgetId, collapsed: isCollapsed });
		},

		/**
         * Oculta un widget
         */
		hideWidget: function ($widget) {
			var self = this;
			var widgetId = $widget.data('widget-id');

			if (!confirm(FlavorDashboard.config.i18n.confirmHide || '¿Ocultar este widget?')) {
				return;
			}

			$widget.fadeOut(300, function () {
				$(this).remove();
				self.saveVisibility();
				FlavorDashboard.toast.info('Widget ocultado');
				FlavorDashboard.events.emit('widget:hidden', { widgetId: widgetId });
			});
		},

		/**
         * Filtra widgets por categoria
         */
		filterByCategory: function (categoria) {
			var $widgets = this.$container.find('.fud-widget');

			if (categoria === 'all' || !categoria) {
				$widgets.show();
			} else {
				$widgets.each(function () {
					var $widget = $(this);
					if ($widget.data('category') === categoria) {
						$widget.show();
					} else {
						$widget.hide();
					}
				});
			}

			FlavorDashboard.events.emit('widgets:filtered', { category: categoria });
		},

		/**
         * Guarda el layout actual
         */
		saveLayout: function () {
			var orden = [];
			var visible = [];
			var colapsados = FlavorDashboard.storage.get('collapsed_widgets', []);

			this.$container.find('.fud-widget').each(function () {
				var widgetId = $(this).data('widget-id');
				orden.push(widgetId);
				visible.push(widgetId);
			});

			FlavorDashboard.ajax.post('fud_save_layout', {
				order: orden,
				visible: visible,
				collapsed: colapsados
			})
				.done(function (response) {
					if (response.success) {
						FlavorDashboard.toast.success(FlavorDashboard.config.i18n.layoutSaved || 'Disposicion guardada');
					}
				})
				.fail(function () {
					FlavorDashboard.toast.error('Error al guardar');
				});

			FlavorDashboard.events.emit('layout:saved', { order: orden, visible: visible });
		},

		/**
         * Guarda solo la visibilidad
         */
		saveVisibility: function () {
			var visible = [];

			this.$container.find('.fud-widget').each(function () {
				visible.push($(this).data('widget-id'));
			});

			FlavorDashboard.ajax.post('fud_save_layout', {
				visible: visible
			});
		},

		/**
         * Actualiza el display de ultima actualizacion
         */
		updateLastRefreshDisplay: function () {
			var $display = $('#fud-last-update');

			if (!$display.length) {
				return;
			}

			var timestamp = $display.data('timestamp') || this.lastRefresh;

			if (timestamp) {
				var texto = FlavorDashboard.format.timeAgo(timestamp);
				$display.find('.fud-last-update__text').text(texto);
			}
		},

		/**
         * Destruye el widget manager
         */
		destroy: function () {
			this.stopAutoRefresh();

			if (this.$container.length && $.fn.sortable) {
				this.$container.sortable('destroy');
			}

			this.initialized = false;
		}
	};

	/**
     * Modulo de personalizacion
     */
	FlavorDashboard.Customize = {
		$modal: null,
		$layoutSortable: null,

		/**
         * Abre el modal de personalizacion
         */
		openModal: function () {
			this.$modal = $('#fud-customize-modal');

			if (!this.$modal.length) {
				FlavorDashboard.debug.warn('Modal de personalizacion no encontrado');
				return;
			}

			this.$modal.show();
			this.initTabs();
			this.initLayoutSortable();
			this.bindModalEvents();

			FlavorDashboard.events.emit('customize:opened');
		},

		/**
         * Cierra el modal
         */
		closeModal: function () {
			if (this.$modal) {
				this.$modal.hide();
			}

			FlavorDashboard.events.emit('customize:closed');
		},

		/**
         * Inicializa tabs
         */
		initTabs: function () {
			var self = this;

			this.$modal.find('.fud-customize-tab').off('click').on('click', function () {
				var tabId = $(this).data('tab');

				// Activar tab
				self.$modal.find('.fud-customize-tab').removeClass('active');
				$(this).addClass('active');

				// Mostrar panel
				self.$modal.find('.fud-customize-panel').removeClass('active');
				self.$modal.find('#fud-panel-' + tabId).addClass('active');
			});
		},

		/**
         * Inicializa sortable del layout
         */
		initLayoutSortable: function () {
			this.$layoutSortable = $('#fud-layout-sortable');

			if (this.$layoutSortable.length && $.fn.sortable) {
				this.$layoutSortable.sortable({
					handle: '.fud-layout-handle',
					axis: 'y',
					containment: 'parent'
				});
			}
		},

		/**
         * Vincula eventos del modal
         */
		bindModalEvents: function () {
			var self = this;

			// Cerrar modal
			this.$modal.find('#fud-modal-close, #fud-modal-cancel, .fud-modal__overlay').off('click').on('click', function () {
				self.closeModal();
			});

			// Guardar cambios
			this.$modal.find('#fud-modal-save').off('click').on('click', function () {
				self.saveChanges();
			});

			// Reset layout
			this.$modal.find('#fud-reset-layout').off('click').on('click', function () {
				self.resetLayout();
			});

			// Dark mode toggle
			this.$modal.find('#fud-pref-darkmode').off('change').on('change', function () {
				self.toggleDarkMode($(this).is(':checked'));
			});

			// Tecla ESC para cerrar
			$(document).off('keyup.fudCustomize').on('keyup.fudCustomize', function (e) {
				if (e.key === 'Escape') {
					self.closeModal();
				}
			});
		},

		/**
         * Guarda los cambios de personalizacion
         */
		saveChanges: function () {
			var self = this;

			// Recopilar widgets visibles
			var visible = [];
			this.$modal.find('input[name="fud_widgets[]"]:checked').each(function () {
				visible.push($(this).val());
			});

			// Recopilar orden
			var orden = [];
			this.$layoutSortable.find('.fud-layout-item').each(function () {
				orden.push($(this).data('widget-id'));
			});

			FlavorDashboard.ajax.post('fud_save_layout', {
				order: orden,
				visible: visible
			})
				.done(function (response) {
					if (response.success) {
						FlavorDashboard.toast.success('Cambios guardados');
						self.closeModal();

						// Recargar para aplicar cambios
						setTimeout(function () {
							location.reload();
						}, 500);
					} else {
						FlavorDashboard.toast.error(response.data?.message || 'Error al guardar');
					}
				})
				.fail(function () {
					FlavorDashboard.toast.error('Error de conexion');
				});
		},

		/**
         * Resetea el layout a por defecto
         */
		resetLayout: function () {
			if (!confirm('¿Restablecer la disposicion por defecto?')) {
				return;
			}

			FlavorDashboard.storage.remove('collapsed_widgets');

			FlavorDashboard.ajax.post('fud_save_layout', {
				order: [],
				visible: []
			})
				.done(function () {
					FlavorDashboard.toast.success('Layout restablecido');
					location.reload();
				});
		},

		/**
         * Activa/desactiva modo oscuro
         */
		toggleDarkMode: function (enabled) {
			var $wrapper = $('.fud-wrapper');

			if (enabled) {
				$wrapper.addClass('fud-dark-mode');
			} else {
				$wrapper.removeClass('fud-dark-mode');
			}

			FlavorDashboard.storage.set('dark_mode', enabled);
			FlavorDashboard.events.emit('darkmode:toggled', { enabled: enabled });
		}
	};

	/**
     * Inicializacion al cargar documento
     */
	$(function () {
		FlavorDashboard.Widgets.init();

		// Restaurar widgets colapsados
		var colapsados = FlavorDashboard.storage.get('collapsed_widgets', []);
		colapsados.forEach(function (widgetId) {
			$('#fud-widgets-container').find('[data-widget-id="' + widgetId + '"]').addClass('fud-widget--collapsed');
		});

		// Restaurar dark mode
		if (FlavorDashboard.storage.get('dark_mode')) {
			$('.fud-wrapper').addClass('fud-dark-mode');
			$('#fud-pref-darkmode').prop('checked', true);
		}

		// Actualizar tiempo relativo periodicamente
		setInterval(function () {
			FlavorDashboard.Widgets.updateLastRefreshDisplay();
		}, 60000);
	});

})(jQuery, FlavorDashboard, window);
