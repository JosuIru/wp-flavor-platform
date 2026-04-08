/**
 * Dashboard Sortable - Sistema Drag & Drop con SortableJS
 *
 * Reemplaza jQuery UI Sortable con SortableJS para mejor rendimiento
 * y soporte de drag & drop entre categorias.
 *
 * @package FlavorChatIA
 * @since 4.1.0
 * @requires SortableJS (https://sortablejs.github.io/Sortable/)
 */

(function () {
	'use strict';

	/**
     * FlavorSortable - Gestor de Drag & Drop
     */
	const FlavorSortable = {
		/**
         * Configuracion
         */
		config: {
			// Selectores
			gridSelector: '.fl-widgets-grid, .fud-widgets-grid, #fud-widgets-container',
			groupContentSelector: '.fl-widget-group__content',
			widgetSelector: '.fl-widget, .fud-widget',
			handleSelector: '.fl-widget__drag-handle, .fud-widget__drag-handle',

			// Clases CSS
			ghostClass: 'fl-widget--ghost',
			chosenClass: 'fl-widget--chosen',
			dragClass: 'fl-widget--dragging',
			fallbackClass: 'fl-widget--fallback',

			// Animacion
			animation: 200,
			easing: 'cubic-bezier(0.25, 1, 0.5, 1)',

			// Opciones
			swapThreshold: 0.65,
			invertSwap: false,
			direction: 'horizontal',
			ghostGap: true,

			// API
			restUrl: '/wp-json/flavor/v1/dashboard/widgets/order',
			ajaxUrl: '/wp-admin/admin-ajax.php',
			nonce: '',
		},

		/**
         * Instancias de Sortable
         */
		instances: [],

		/**
         * Estado
         */
		state: {
			isDragging: false,
			originalOrder: [],
			lastSavedOrder: [],
			saveTimeout: null,
		},

		/**
         * Inicializa el sistema de drag & drop
         */
		init: function () {
			// Verificar que SortableJS esta disponible
			if (typeof Sortable === 'undefined') {
				console.warn('FlavorSortable: SortableJS no esta cargado. Usando fallback jQuery UI.');
				this.initJQueryUIFallback();
				return;
			}

			// Obtener configuracion de WordPress
			this.loadWordPressConfig();

			// Inicializar grids
			this.initWidgetGrids();
			this.initCategoryGroups();

			// Eventos globales
			this.bindGlobalEvents();

			// Anunciar a screen readers
			this.createLiveRegion();

			console.log('FlavorSortable: Inicializado correctamente');
		},

		/**
         * Carga configuracion de WordPress
         */
		loadWordPressConfig: function () {
			// Intentar obtener de diferentes variables globales
			const wpConfig = window.fudConfig || window.flavorDashboard || window.flDashboard || {};

			if (wpConfig.restUrl) {
				this.config.restUrl = wpConfig.restUrl + 'widgets/order';
			}
			if (wpConfig.ajaxUrl) {
				this.config.ajaxUrl = wpConfig.ajaxUrl;
			}
			if (wpConfig.nonce) {
				this.config.nonce = wpConfig.nonce;
			}
		},

		/**
         * Inicializa grids de widgets
         */
		initWidgetGrids: function () {
			const grids = document.querySelectorAll(this.config.gridSelector);

			grids.forEach(function (grid) {
				// Evitar inicializar dos veces
				if (grid.dataset.sortableInit) {
					return;
				}

				const sortableInstance = Sortable.create(grid, {
					group: 'fl-widgets', // Permite mover entre grids
					animation: this.config.animation,
					easing: this.config.easing,
					handle: this.config.handleSelector,
					draggable: this.config.widgetSelector,
					ghostClass: this.config.ghostClass,
					chosenClass: this.config.chosenClass,
					dragClass: this.config.dragClass,
					fallbackClass: this.config.fallbackClass,
					swapThreshold: this.config.swapThreshold,
					invertSwap: this.config.invertSwap,

					// Callbacks
					onStart: this.onDragStart.bind(this),
					onEnd: this.onDragEnd.bind(this),
					onMove: this.onDragMove.bind(this),
					onChange: this.onChange.bind(this),

					// Accesibilidad
					setData: function (dataTransfer, dragEl) {
						// Datos para accesibilidad
						dataTransfer.setData('text/plain', dragEl.dataset.widgetId || '');
					},
				});

				grid.dataset.sortableInit = 'true';
				this.instances.push(sortableInstance);

			}.bind(this));
		},

		/**
         * Inicializa grupos de categorias
         */
		initCategoryGroups: function () {
			const groupContents = document.querySelectorAll(this.config.groupContentSelector);

			groupContents.forEach(function (groupContent) {
				if (groupContent.dataset.sortableInit) {
					return;
				}

				const sortableInstance = Sortable.create(groupContent, {
					group: 'fl-widgets', // Mismo grupo para mover entre categorias
					animation: this.config.animation,
					easing: this.config.easing,
					handle: this.config.handleSelector,
					draggable: this.config.widgetSelector,
					ghostClass: this.config.ghostClass,
					chosenClass: this.config.chosenClass,
					dragClass: this.config.dragClass,
					swapThreshold: this.config.swapThreshold,

					onStart: this.onDragStart.bind(this),
					onEnd: this.onDragEnd.bind(this),
					onAdd: this.onWidgetAddedToGroup.bind(this),
					onRemove: this.onWidgetRemovedFromGroup.bind(this),
				});

				groupContent.dataset.sortableInit = 'true';
				this.instances.push(sortableInstance);

			}.bind(this));
		},

		/**
         * Callback: Inicio de arrastre
         */
		onDragStart: function (evt) {
			this.state.isDragging = true;
			this.state.originalOrder = this.getCurrentOrder();

			// Actualizar ARIA
			const draggedEl = evt.item;
			draggedEl.setAttribute('aria-grabbed', 'true');

			// Anunciar a screen readers
			const widgetTitle = this.getWidgetTitle(draggedEl);
			this.announce('Arrastrando ' + widgetTitle + '. Use las flechas para mover y Enter para soltar.');

			// Clase al body
			document.body.classList.add('fl-is-dragging');

			// Trigger evento personalizado
			this.triggerEvent('fl:dragstart', { widget: draggedEl });
		},

		/**
         * Callback: Fin de arrastre
         */
		onDragEnd: function (evt) {
			this.state.isDragging = false;

			const draggedEl = evt.item;
			draggedEl.setAttribute('aria-grabbed', 'false');

			document.body.classList.remove('fl-is-dragging');

			// Anunciar posicion final
			const widgetTitle = this.getWidgetTitle(draggedEl);
			const newPosition = evt.newIndex + 1;
			this.announce(widgetTitle + ' movido a posicion ' + newPosition);

			// Guardar nuevo orden (con debounce)
			this.debouncedSaveOrder();

			// Trigger evento personalizado
			this.triggerEvent('fl:dragend', {
				widget: draggedEl,
				oldIndex: evt.oldIndex,
				newIndex: evt.newIndex,
			});
		},

		/**
         * Callback: Durante el movimiento
         */
		onDragMove: function (evt, originalEvent) {
			// Permitir soltar en cualquier lugar valido
			return true;
		},

		/**
         * Callback: Cambio de orden
         */
		onChange: function (evt) {
			// Actualizar visualmente
			this.triggerEvent('fl:orderchange', { widget: evt.item });
		},

		/**
         * Callback: Widget agregado a un grupo
         */
		onWidgetAddedToGroup: function (evt) {
			const widget = evt.item;
			const newGroup = evt.to.closest('.fl-widget-group');

			if (newGroup) {
				const newCategory = newGroup.dataset.category;
				widget.dataset.category = newCategory;

				// Actualizar clase de categoria
				this.updateWidgetCategoryClass(widget, newCategory);

				this.announce(this.getWidgetTitle(widget) + ' movido a categoria ' + this.getCategoryLabel(newCategory));
			}
		},

		/**
         * Callback: Widget removido de un grupo
         */
		onWidgetRemovedFromGroup: function (evt) {
			// No necesita accion especial
		},

		/**
         * Obtiene el orden actual de widgets
         */
		getCurrentOrder: function () {
			const order = [];
			const widgets = document.querySelectorAll(this.config.widgetSelector);

			widgets.forEach(function (widget) {
				const widgetId = widget.dataset.widgetId;
				if (widgetId) {
					order.push(widgetId);
				}
			});

			return order;
		},

		/**
         * Guarda el orden con debounce
         */
		debouncedSaveOrder: function () {
			if (this.state.saveTimeout) {
				clearTimeout(this.state.saveTimeout);
			}

			this.state.saveTimeout = setTimeout(function () {
				this.saveOrder();
			}.bind(this), 500);
		},

		/**
         * Guarda el orden en el servidor
         */
		saveOrder: function () {
			const currentOrder = this.getCurrentOrder();

			// Evitar guardar si no cambio
			if (JSON.stringify(currentOrder) === JSON.stringify(this.state.lastSavedOrder)) {
				return;
			}

			// Usar REST API si esta disponible
			if (this.config.nonce) {
				this.saveOrderREST(currentOrder);
			} else {
				this.saveOrderAJAX(currentOrder);
			}
		},

		/**
         * Guarda orden via REST API
         */
		saveOrderREST: function (order) {
			fetch(this.config.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.config.nonce,
				},
				body: JSON.stringify({ order: order }),
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					if (data.success) {
						this.state.lastSavedOrder = order;
						this.showSaveSuccess();
					} else {
						this.showSaveError(data.message);
					}
				}.bind(this))
				.catch(function (error) {
					console.error('FlavorSortable: Error guardando orden', error);
					this.showSaveError();
				}.bind(this));
		},

		/**
         * Guarda orden via AJAX (fallback)
         */
		saveOrderAJAX: function (order) {
			const formData = new FormData();
			formData.append('action', 'fud_save_layout');
			formData.append('order', JSON.stringify(order));

			if (this.config.nonce) {
				formData.append('nonce', this.config.nonce);
			}

			fetch(this.config.ajaxUrl, {
				method: 'POST',
				body: formData,
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					if (data.success) {
						this.state.lastSavedOrder = order;
						this.showSaveSuccess();
					}
				}.bind(this))
				.catch(function (error) {
					console.error('FlavorSortable: Error guardando orden via AJAX', error);
				});
		},

		/**
         * Muestra mensaje de exito al guardar
         */
		showSaveSuccess: function () {
			this.announce('Disposicion guardada correctamente');

			// Toast visual opcional
			this.showToast('Disposicion guardada', 'success');
		},

		/**
         * Muestra mensaje de error al guardar
         */
		showSaveError: function (message) {
			const errorMessage = message || 'Error al guardar la disposicion';
			this.announce(errorMessage);
			this.showToast(errorMessage, 'error');
		},

		/**
         * Muestra un toast
         */
		showToast: function (message, type) {
			type = type || 'info';

			// Remover toast anterior
			const existingToast = document.querySelector('.fl-sortable-toast');
			if (existingToast) {
				existingToast.remove();
			}

			const toast = document.createElement('div');
			toast.className = 'fl-sortable-toast fl-sortable-toast--' + type;
			toast.textContent = message;
			toast.setAttribute('role', 'status');
			toast.setAttribute('aria-live', 'polite');

			document.body.appendChild(toast);

			// Remover despues de 3 segundos
			setTimeout(function () {
				toast.classList.add('fl-sortable-toast--out');
				setTimeout(function () {
					toast.remove();
				}, 300);
			}, 3000);
		},

		/**
         * Eventos globales
         */
		bindGlobalEvents: function () {
			// Soporte de teclado para drag & drop
			document.addEventListener('keydown', this.handleKeyboardDrag.bind(this));

			// Actualizar al redimensionar
			window.addEventListener('resize', this.debounce(function () {
				this.refreshInstances();
			}.bind(this), 250));
		},

		/**
         * Manejo de teclado para arrastrar
         */
		handleKeyboardDrag: function (evt) {
			// Solo si un widget tiene foco
			const focusedWidget = document.activeElement.closest(this.config.widgetSelector);
			if (!focusedWidget) {
				return;
			}

			const key = evt.key;

			// Mover con flechas cuando esta en modo drag
			if (focusedWidget.getAttribute('aria-grabbed') === 'true') {
				let moved = false;

				switch (key) {
					case 'ArrowLeft':
					case 'ArrowUp':
						moved = this.moveWidgetByKeyboard(focusedWidget, -1);
						break;
					case 'ArrowRight':
					case 'ArrowDown':
						moved = this.moveWidgetByKeyboard(focusedWidget, 1);
						break;
					case 'Escape':
						this.cancelKeyboardDrag(focusedWidget);
						break;
					case 'Enter':
					case ' ':
						this.finishKeyboardDrag(focusedWidget);
						break;
				}

				if (moved) {
					evt.preventDefault();
				}
			} else {
				// Iniciar drag con Enter o Espacio
				if (key === 'Enter' || key === ' ') {
					const handle = focusedWidget.querySelector(this.config.handleSelector);
					if (document.activeElement === handle) {
						evt.preventDefault();
						this.startKeyboardDrag(focusedWidget);
					}
				}
			}
		},

		/**
         * Inicia drag por teclado
         */
		startKeyboardDrag: function (widget) {
			widget.setAttribute('aria-grabbed', 'true');
			widget.classList.add(this.config.chosenClass);
			this.state.originalOrder = this.getCurrentOrder();

			const widgetTitle = this.getWidgetTitle(widget);
			this.announce('Arrastrando ' + widgetTitle + '. Use flechas para mover, Enter para soltar, Escape para cancelar.');
		},

		/**
         * Mueve widget por teclado
         */
		moveWidgetByKeyboard: function (widget, direction) {
			const parent = widget.parentElement;
			const siblings = Array.from(parent.querySelectorAll(this.config.widgetSelector));
			const currentIndex = siblings.indexOf(widget);
			const newIndex = currentIndex + direction;

			if (newIndex < 0 || newIndex >= siblings.length) {
				return false;
			}

			// Mover en el DOM
			if (direction > 0) {
				parent.insertBefore(widget, siblings[newIndex].nextSibling);
			} else {
				parent.insertBefore(widget, siblings[newIndex]);
			}

			// Anunciar
			this.announce('Posicion ' + (newIndex + 1) + ' de ' + siblings.length);

			// Mantener foco
			widget.focus();

			return true;
		},

		/**
         * Finaliza drag por teclado
         */
		finishKeyboardDrag: function (widget) {
			widget.setAttribute('aria-grabbed', 'false');
			widget.classList.remove(this.config.chosenClass);

			const widgetTitle = this.getWidgetTitle(widget);
			this.announce(widgetTitle + ' soltado. Nueva posicion guardada.');

			this.debouncedSaveOrder();
		},

		/**
         * Cancela drag por teclado
         */
		cancelKeyboardDrag: function (widget) {
			widget.setAttribute('aria-grabbed', 'false');
			widget.classList.remove(this.config.chosenClass);

			// Restaurar orden original
			// (Simplificado - en produccion se restauraria el orden DOM)

			this.announce('Arrastre cancelado.');
		},

		/**
         * Crea region live para anuncios
         */
		createLiveRegion: function () {
			if (document.getElementById('fl-sortable-live')) {
				return;
			}

			const liveRegion = document.createElement('div');
			liveRegion.id = 'fl-sortable-live';
			liveRegion.className = 'fl-sr-only fl-live-region';
			liveRegion.setAttribute('role', 'status');
			liveRegion.setAttribute('aria-live', 'polite');
			liveRegion.setAttribute('aria-atomic', 'true');

			document.body.appendChild(liveRegion);
		},

		/**
         * Anuncia mensaje a screen readers
         */
		announce: function (message) {
			const liveRegion = document.getElementById('fl-sortable-live');
			if (liveRegion) {
				liveRegion.textContent = '';
				// Pequeño delay para que el screen reader detecte el cambio
				setTimeout(function () {
					liveRegion.textContent = message;
				}, 100);
			}
		},

		/**
         * Obtiene titulo del widget
         */
		getWidgetTitle: function (widget) {
			const titleEl = widget.querySelector('.fl-widget__title, .fud-widget__title, h3');
			return titleEl ? titleEl.textContent.trim() : 'Widget';
		},

		/**
         * Obtiene label de categoria
         */
		getCategoryLabel: function (categoryId) {
			const group = document.querySelector('.fl-widget-group[data-category="' + categoryId + '"]');
			if (group) {
				const titleEl = group.querySelector('.fl-widget-group__title');
				return titleEl ? titleEl.textContent.trim() : categoryId;
			}
			return categoryId;
		},

		/**
         * Actualiza clase de categoria del widget
         */
		updateWidgetCategoryClass: function (widget, newCategory) {
			// Remover clases de categoria anteriores
			const categoryClasses = Array.from(widget.classList).filter(function (cls) {
				return cls.startsWith('fl-widget--') || cls.startsWith('fud-widget--');
			});

			categoryClasses.forEach(function (cls) {
				if (cls.includes('--') && !cls.includes('--standard') && !cls.includes('--featured') && !cls.includes('--compact')) {
					widget.classList.remove(cls);
				}
			});

			// Agregar nueva clase
			widget.classList.add('fl-widget--' + newCategory);
			widget.classList.add('fud-widget--' + newCategory);
		},

		/**
         * Refresca instancias de Sortable
         */
		refreshInstances: function () {
			this.instances.forEach(function (instance) {
				if (instance && typeof instance.option === 'function') {
					instance.option('disabled', false);
				}
			});
		},

		/**
         * Destruye todas las instancias
         */
		destroy: function () {
			this.instances.forEach(function (instance) {
				if (instance && typeof instance.destroy === 'function') {
					instance.destroy();
				}
			});
			this.instances = [];

			// Limpiar marcadores
			const initElements = document.querySelectorAll('[data-sortable-init]');
			initElements.forEach(function (el) {
				delete el.dataset.sortableInit;
			});
		},

		/**
         * Trigger evento personalizado
         */
		triggerEvent: function (eventName, detail) {
			const event = new CustomEvent(eventName, {
				bubbles: true,
				detail: detail || {},
			});
			document.dispatchEvent(event);
		},

		/**
         * Utilidad debounce
         */
		debounce: function (func, wait) {
			var timeout;
			return function () {
				var context = this;
				var args = arguments;
				clearTimeout(timeout);
				timeout = setTimeout(function () {
					func.apply(context, args);
				}, wait);
			};
		},

		/**
         * Fallback a jQuery UI si SortableJS no esta disponible
         */
		initJQueryUIFallback: function () {
			if (typeof jQuery === 'undefined' || typeof jQuery.ui === 'undefined' || typeof jQuery.ui.sortable === 'undefined') {
				console.error('FlavorSortable: Ni SortableJS ni jQuery UI estan disponibles.');
				return;
			}

			var self = this;
			var $ = jQuery;

			$(this.config.gridSelector).sortable({
				handle: this.config.handleSelector,
				placeholder: 'fl-widget--placeholder',
				tolerance: 'pointer',
				revert: 200,
				start: function (event, ui) {
					ui.item.addClass(self.config.dragClass);
				},
				stop: function (event, ui) {
					ui.item.removeClass(self.config.dragClass);
					self.debouncedSaveOrder();
				},
			});

			console.log('FlavorSortable: Usando jQuery UI Sortable como fallback');
		},
	};

	// Inicializar cuando el DOM este listo
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			FlavorSortable.init();
		});
	} else {
		FlavorSortable.init();
	}

	// Exponer globalmente
	window.FlavorSortable = FlavorSortable;

})();
