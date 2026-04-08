/**
 * Flavor Chat IA - Keyboard Shortcuts System
 *
 * Sistema de atajos de teclado con registro dinámico,
 * soporte para modificadores y panel de ayuda.
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */

(function (window, document) {
	'use strict';

	/**
     * Configuración por defecto
     */
	const DEFAULT_CONFIG = {
		enableInInputs: false,
		enableInTextareas: false,
		enableInContentEditable: false,
		helpModalId: 'flavor-shortcuts-help-modal',
		helpModalClass: 'flavor-shortcuts-modal',
		debug: false
	};

	/**
     * Clase principal del sistema de atajos
     */
	class FlavorKeyboardShortcuts {
		constructor(config = {}) {
			this.config = { ...DEFAULT_CONFIG, ...config };
			this.shortcuts = new Map();
			this.categories = new Map();
			this.isHelpModalOpen = false;
			this.isEnabled = true;
			this.eventListenerAttached = false;

			this.init();
		}

		/**
         * Inicializa el sistema de atajos
         */
		init() {
			this.attachEventListener();
			this.registerDefaultShortcuts();
			this.createHelpModal();

			if (this.config.debug) {
				console.log('[FlavorShortcuts] Sistema inicializado');
			}
		}

		/**
         * Adjunta el listener de eventos de teclado
         */
		attachEventListener() {
			if (this.eventListenerAttached) {
				return;
			}

			document.addEventListener('keydown', this.handleKeyDown.bind(this), true);
			this.eventListenerAttached = true;
		}

		/**
         * Maneja los eventos de teclado
         * @param {KeyboardEvent} event
         */
		handleKeyDown(event) {
			if (!this.isEnabled) {
				return;
			}

			// Verificar si estamos en un elemento de entrada
			if (this.isInputElement(event.target)) {
				// Solo permitir Escape en inputs
				if (event.key !== 'Escape') {
					return;
				}
			}

			const shortcutKey = this.buildShortcutKey(event);
			const shortcut = this.shortcuts.get(shortcutKey);

			if (shortcut && shortcut.enabled) {
				if (this.config.debug) {
					console.log('[FlavorShortcuts] Ejecutando:', shortcutKey);
				}

				event.preventDefault();
				event.stopPropagation();

				try {
					shortcut.callback(event);
				} catch (error) {
					console.error('[FlavorShortcuts] Error ejecutando atajo:', error);
				}
			}
		}

		/**
         * Verifica si el elemento es un campo de entrada
         * @param {HTMLElement} element
         * @returns {boolean}
         */
		isInputElement(element) {
			if (!element) {
				return false;
			}

			const tagName = element.tagName.toLowerCase();
			const isInput = tagName === 'input' && !this.config.enableInInputs;
			const isTextarea = tagName === 'textarea' && !this.config.enableInTextareas;
			const isContentEditable = element.isContentEditable && !this.config.enableInContentEditable;
			const isSelect = tagName === 'select';

			return isInput || isTextarea || isContentEditable || isSelect;
		}

		/**
         * Construye la clave del atajo a partir del evento
         * @param {KeyboardEvent} event
         * @returns {string}
         */
		buildShortcutKey(event) {
			const modifiers = [];

			if (event.ctrlKey || event.metaKey) {
				modifiers.push('ctrl');
			}
			if (event.altKey) {
				modifiers.push('alt');
			}
			if (event.shiftKey) {
				modifiers.push('shift');
			}

			let key = event.key.toLowerCase();

			// Normalizar teclas especiales
			const keyMap = {
				'escape': 'escape',
				'esc': 'escape',
				'enter': 'enter',
				'return': 'enter',
				'tab': 'tab',
				'backspace': 'backspace',
				'delete': 'delete',
				'arrowup': 'up',
				'arrowdown': 'down',
				'arrowleft': 'left',
				'arrowright': 'right',
				' ': 'space'
			};

			key = keyMap[key] || key;

			// Evitar duplicar modificadores en la tecla
			if (['control', 'alt', 'shift', 'meta'].includes(key)) {
				return '';
			}

			return [...modifiers, key].join('+');
		}

		/**
         * Registra un nuevo atajo de teclado
         * @param {string} keyCombo - Combinación de teclas (ej: 'ctrl+s', 'shift+?')
         * @param {Function} callback - Función a ejecutar
         * @param {Object} options - Opciones adicionales
         * @returns {FlavorKeyboardShortcuts}
         */
		register(keyCombo, callback, options = {}) {
			const defaultOptions = {
				description: '',
				category: 'general',
				enabled: true,
				global: false
			};

			const shortcutOptions = { ...defaultOptions, ...options };
			const normalizedKey = this.normalizeKeyCombo(keyCombo);

			if (this.shortcuts.has(normalizedKey)) {
				if (this.config.debug) {
					console.warn('[FlavorShortcuts] Sobrescribiendo atajo:', normalizedKey);
				}
			}

			this.shortcuts.set(normalizedKey, {
				key: normalizedKey,
				originalKey: keyCombo,
				callback: callback,
				description: shortcutOptions.description,
				category: shortcutOptions.category,
				enabled: shortcutOptions.enabled,
				global: shortcutOptions.global
			});

			// Agregar a categoría
			if (!this.categories.has(shortcutOptions.category)) {
				this.categories.set(shortcutOptions.category, []);
			}

			const categoryShortcuts = this.categories.get(shortcutOptions.category);
			const existingIndex = categoryShortcuts.findIndex(shortcut => shortcut.key === normalizedKey);

			if (existingIndex >= 0) {
				categoryShortcuts[existingIndex] = this.shortcuts.get(normalizedKey);
			} else {
				categoryShortcuts.push(this.shortcuts.get(normalizedKey));
			}

			if (this.config.debug) {
				console.log('[FlavorShortcuts] Registrado:', normalizedKey, shortcutOptions.description);
			}

			return this;
		}

		/**
         * Normaliza la combinación de teclas
         * @param {string} keyCombo
         * @returns {string}
         */
		normalizeKeyCombo(keyCombo) {
			const parts = keyCombo.toLowerCase().split('+').map(part => part.trim());
			const modifiers = [];
			let mainKey = '';

			const modifierMap = {
				'ctrl': 'ctrl',
				'control': 'ctrl',
				'cmd': 'ctrl',
				'command': 'ctrl',
				'meta': 'ctrl',
				'alt': 'alt',
				'option': 'alt',
				'shift': 'shift'
			};

			parts.forEach(part => {
				if (modifierMap[part]) {
					modifiers.push(modifierMap[part]);
				} else {
					mainKey = part;
				}
			});

			// Ordenar modificadores consistentemente
			const orderedModifiers = ['ctrl', 'alt', 'shift'].filter(modifier => modifiers.includes(modifier));

			return [...orderedModifiers, mainKey].join('+');
		}

		/**
         * Elimina un atajo registrado
         * @param {string} keyCombo
         * @returns {boolean}
         */
		unregister(keyCombo) {
			const normalizedKey = this.normalizeKeyCombo(keyCombo);
			const shortcut = this.shortcuts.get(normalizedKey);

			if (shortcut) {
				// Eliminar de la categoría
				const categoryShortcuts = this.categories.get(shortcut.category);
				if (categoryShortcuts) {
					const index = categoryShortcuts.findIndex(s => s.key === normalizedKey);
					if (index >= 0) {
						categoryShortcuts.splice(index, 1);
					}
				}

				this.shortcuts.delete(normalizedKey);
				return true;
			}

			return false;
		}

		/**
         * Habilita o deshabilita un atajo
         * @param {string} keyCombo
         * @param {boolean} enabled
         */
		setEnabled(keyCombo, enabled) {
			const normalizedKey = this.normalizeKeyCombo(keyCombo);
			const shortcut = this.shortcuts.get(normalizedKey);

			if (shortcut) {
				shortcut.enabled = enabled;
			}
		}

		/**
         * Habilita o deshabilita todo el sistema
         * @param {boolean} enabled
         */
		setSystemEnabled(enabled) {
			this.isEnabled = enabled;
		}

		/**
         * Registra los atajos predefinidos
         */
		registerDefaultShortcuts() {
			// Ctrl+S: Guardar formulario actual
			this.register('ctrl+s', () => {
				this.saveCurrentForm();
			}, {
				description: 'Guardar formulario actual',
				category: 'formularios'
			});

			// Ctrl+K: Abrir búsqueda rápida
			this.register('ctrl+k', () => {
				this.openQuickSearch();
			}, {
				description: 'Abrir búsqueda rápida',
				category: 'navegacion'
			});

			// Escape: Cerrar modales/dropdowns
			this.register('escape', () => {
				this.closeActiveOverlays();
			}, {
				description: 'Cerrar modales y dropdowns',
				category: 'navegacion'
			});

			// ?: Mostrar panel de ayuda
			this.register('shift+?', () => {
				this.toggleHelpModal();
			}, {
				description: 'Mostrar ayuda de atajos',
				category: 'ayuda'
			});

			// Ctrl+/: Alternar panel lateral (si existe)
			this.register('ctrl+/', () => {
				this.toggleSidebar();
			}, {
				description: 'Alternar panel lateral',
				category: 'navegacion'
			});

			// Ctrl+Enter: Enviar formulario
			this.register('ctrl+enter', () => {
				this.submitCurrentForm();
			}, {
				description: 'Enviar formulario',
				category: 'formularios'
			});

			// Alt+N: Nueva entrada (contexto dependiente)
			this.register('alt+n', () => {
				this.createNewEntry();
			}, {
				description: 'Crear nueva entrada',
				category: 'acciones'
			});

			// Alt+E: Editar seleccionado
			this.register('alt+e', () => {
				this.editSelected();
			}, {
				description: 'Editar elemento seleccionado',
				category: 'acciones'
			});

			// Ctrl+Shift+D: Modo debug (solo desarrollo)
			this.register('ctrl+shift+d', () => {
				this.toggleDebugMode();
			}, {
				description: 'Alternar modo debug',
				category: 'desarrollo'
			});
		}

		/**
         * Guarda el formulario actual
         */
		saveCurrentForm() {
			// Buscar botón de guardar en el formulario activo
			const saveButtons = [
				'input[type="submit"][name="submit"]',
				'button[type="submit"]',
				'#submit',
				'.button-primary[type="submit"]',
				'input[name="save"]',
				'.flavor-save-btn',
				'[data-action="save"]'
			];

			for (const selector of saveButtons) {
				const button = document.querySelector(selector);
				if (button && this.isVisible(button)) {
					button.click();
					this.showNotification('Guardando...', 'info');
					return;
				}
			}

			// Intentar disparar evento personalizado
			document.dispatchEvent(new CustomEvent('flavor:save', { bubbles: true }));
		}

		/**
         * Abre la búsqueda rápida
         */
		openQuickSearch() {
			// Buscar campo de búsqueda existente
			const searchFields = [
				'#flavor-quick-search',
				'.flavor-search-input',
				'#post-search-input',
				'.search-box input[type="search"]',
				'[data-quick-search]'
			];

			for (const selector of searchFields) {
				const field = document.querySelector(selector);
				if (field && this.isVisible(field)) {
					field.focus();
					field.select();
					return;
				}
			}

			// Disparar evento para crear búsqueda rápida
			document.dispatchEvent(new CustomEvent('flavor:quicksearch', { bubbles: true }));
		}

		/**
         * Cierra overlays activos (modales, dropdowns)
         */
		closeActiveOverlays() {
			// Cerrar modal de ayuda si está abierto
			if (this.isHelpModalOpen) {
				this.hideHelpModal();
				return;
			}

			// Buscar y cerrar modales abiertos
			const modals = document.querySelectorAll('.flavor-modal.is-open, .modal.show, [role="dialog"][aria-hidden="false"]');
			modals.forEach(modal => {
				const closeBtn = modal.querySelector('.close, .modal-close, [data-dismiss="modal"]');
				if (closeBtn) {
					closeBtn.click();
				} else {
					modal.style.display = 'none';
					modal.classList.remove('is-open', 'show');
					modal.setAttribute('aria-hidden', 'true');
				}
			});

			// Cerrar dropdowns abiertos
			const dropdowns = document.querySelectorAll('.dropdown.open, .dropdown-menu.show, [data-dropdown].is-open');
			dropdowns.forEach(dropdown => {
				dropdown.classList.remove('open', 'show', 'is-open');
			});

			// Disparar evento personalizado
			document.dispatchEvent(new CustomEvent('flavor:close-overlays', { bubbles: true }));
		}

		/**
         * Alterna el panel lateral
         */
		toggleSidebar() {
			const sidebars = [
				'#flavor-sidebar',
				'.flavor-sidebar',
				'#adminmenuwrap',
				'[data-sidebar]'
			];

			for (const selector of sidebars) {
				const sidebar = document.querySelector(selector);
				if (sidebar) {
					sidebar.classList.toggle('collapsed');
					sidebar.classList.toggle('is-collapsed');
					document.body.classList.toggle('sidebar-collapsed');

					document.dispatchEvent(new CustomEvent('flavor:sidebar-toggle', {
						detail: { collapsed: sidebar.classList.contains('collapsed') || sidebar.classList.contains('is-collapsed') }
					}));
					return;
				}
			}
		}

		/**
         * Envía el formulario actual
         */
		submitCurrentForm() {
			const activeElement = document.activeElement;
			const form = activeElement?.closest('form');

			if (form) {
				const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
				if (form.dispatchEvent(submitEvent)) {
					form.submit();
				}
			}
		}

		/**
         * Crea una nueva entrada (contexto dependiente)
         */
		createNewEntry() {
			// Buscar botón de agregar nuevo
			const newButtons = [
				'.page-title-action',
				'.add-new-h2',
				'[data-action="new"]',
				'.flavor-add-new',
				'#flavor-add-new'
			];

			for (const selector of newButtons) {
				const button = document.querySelector(selector);
				if (button && this.isVisible(button)) {
					button.click();
					return;
				}
			}

			document.dispatchEvent(new CustomEvent('flavor:new-entry', { bubbles: true }));
		}

		/**
         * Edita el elemento seleccionado
         */
		editSelected() {
			// Buscar elemento seleccionado en listas
			const selected = document.querySelector('.selected, .is-selected, [aria-selected="true"], tr.highlighted');

			if (selected) {
				const editLink = selected.querySelector('.row-actions .edit a, [data-action="edit"]');
				if (editLink) {
					editLink.click();
					return;
				}
			}

			document.dispatchEvent(new CustomEvent('flavor:edit-selected', { bubbles: true }));
		}

		/**
         * Alterna el modo debug
         */
		toggleDebugMode() {
			this.config.debug = !this.config.debug;
			this.showNotification(
				`Modo debug ${this.config.debug ? 'activado' : 'desactivado'}`,
				this.config.debug ? 'success' : 'info'
			);
		}

		/**
         * Verifica si un elemento es visible
         * @param {HTMLElement} element
         * @returns {boolean}
         */
		isVisible(element) {
			if (!element) {
				return false;
			}

			const style = window.getComputedStyle(element);
			return style.display !== 'none' &&
                   style.visibility !== 'hidden' &&
                   style.opacity !== '0' &&
                   element.offsetParent !== null;
		}

		/**
         * Muestra una notificación temporal
         * @param {string} message
         * @param {string} type
         */
		showNotification(message, type = 'info') {
			// Usar sistema de notificaciones existente si está disponible
			if (window.FlavorNotifications) {
				window.FlavorNotifications.show(message, type);
				return;
			}

			// Crear notificación simple
			const notification = document.createElement('div');
			notification.className = `flavor-shortcut-notification flavor-shortcut-notification--${type}`;
			notification.textContent = message;
			notification.setAttribute('role', 'alert');

			document.body.appendChild(notification);

			// Animar entrada
			requestAnimationFrame(() => {
				notification.classList.add('is-visible');
			});

			// Remover después de 2 segundos
			setTimeout(() => {
				notification.classList.remove('is-visible');
				setTimeout(() => notification.remove(), 300);
			}, 2000);
		}

		/**
         * Crea el modal de ayuda de atajos
         */
		createHelpModal() {
			// Verificar si ya existe
			if (document.getElementById(this.config.helpModalId)) {
				return;
			}

			const modal = document.createElement('div');
			modal.id = this.config.helpModalId;
			modal.className = this.config.helpModalClass;
			modal.setAttribute('role', 'dialog');
			modal.setAttribute('aria-modal', 'true');
			modal.setAttribute('aria-labelledby', 'flavor-shortcuts-title');
			modal.setAttribute('aria-hidden', 'true');

			modal.innerHTML = `
                <div class="flavor-shortcuts-modal__backdrop"></div>
                <div class="flavor-shortcuts-modal__content">
                    <div class="flavor-shortcuts-modal__header">
                        <h2 id="flavor-shortcuts-title">Atajos de Teclado</h2>
                        <button type="button" class="flavor-shortcuts-modal__close" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="flavor-shortcuts-modal__body">
                        <div class="flavor-shortcuts-list"></div>
                    </div>
                    <div class="flavor-shortcuts-modal__footer">
                        <p class="flavor-shortcuts-modal__hint">
                            Presiona <kbd>Shift</kbd> + <kbd>?</kbd> para mostrar/ocultar esta ayuda
                        </p>
                    </div>
                </div>
            `;

			document.body.appendChild(modal);

			// Event listeners para cerrar
			const closeBtn = modal.querySelector('.flavor-shortcuts-modal__close');
			const backdrop = modal.querySelector('.flavor-shortcuts-modal__backdrop');

			closeBtn.addEventListener('click', () => this.hideHelpModal());
			backdrop.addEventListener('click', () => this.hideHelpModal());
		}

		/**
         * Muestra el modal de ayuda
         */
		showHelpModal() {
			const modal = document.getElementById(this.config.helpModalId);
			if (!modal) {
				return;
			}

			// Actualizar contenido
			this.updateHelpModalContent();

			// Mostrar modal
			modal.setAttribute('aria-hidden', 'false');
			modal.classList.add('is-open');
			this.isHelpModalOpen = true;

			// Focus trap
			const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
			if (firstFocusable) {
				firstFocusable.focus();
			}

			document.body.classList.add('flavor-shortcuts-modal-open');
		}

		/**
         * Oculta el modal de ayuda
         */
		hideHelpModal() {
			const modal = document.getElementById(this.config.helpModalId);
			if (!modal) {
				return;
			}

			modal.setAttribute('aria-hidden', 'true');
			modal.classList.remove('is-open');
			this.isHelpModalOpen = false;

			document.body.classList.remove('flavor-shortcuts-modal-open');
		}

		/**
         * Alterna el modal de ayuda
         */
		toggleHelpModal() {
			if (this.isHelpModalOpen) {
				this.hideHelpModal();
			} else {
				this.showHelpModal();
			}
		}

		/**
         * Actualiza el contenido del modal de ayuda
         */
		updateHelpModalContent() {
			const modal = document.getElementById(this.config.helpModalId);
			if (!modal) {
				return;
			}

			const listContainer = modal.querySelector('.flavor-shortcuts-list');
			if (!listContainer) {
				return;
			}

			let html = '';

			// Nombres de categorías en español
			const categoryNames = {
				'general': 'General',
				'formularios': 'Formularios',
				'navegacion': 'Navegación',
				'acciones': 'Acciones',
				'ayuda': 'Ayuda',
				'desarrollo': 'Desarrollo'
			};

			// Agrupar por categoría
			this.categories.forEach((shortcuts, category) => {
				const enabledShortcuts = shortcuts.filter(s => s.enabled);

				if (enabledShortcuts.length === 0) {
					return;
				}

				const categoryName = categoryNames[category] || category.charAt(0).toUpperCase() + category.slice(1);

				html += `
                    <div class="flavor-shortcuts-category">
                        <h3 class="flavor-shortcuts-category__title">${this.escapeHtml(categoryName)}</h3>
                        <dl class="flavor-shortcuts-category__list">
                `;

				enabledShortcuts.forEach(shortcut => {
					const keyBadges = this.formatKeyCombo(shortcut.originalKey);
					html += `
                        <div class="flavor-shortcut-item">
                            <dt class="flavor-shortcut-item__keys">${keyBadges}</dt>
                            <dd class="flavor-shortcut-item__description">${this.escapeHtml(shortcut.description)}</dd>
                        </div>
                    `;
				});

				html += `
                        </dl>
                    </div>
                `;
			});

			listContainer.innerHTML = html;
		}

		/**
         * Formatea la combinación de teclas para mostrar
         * @param {string} keyCombo
         * @returns {string}
         */
		formatKeyCombo(keyCombo) {
			const keyNames = {
				'ctrl': 'Ctrl',
				'alt': 'Alt',
				'shift': 'Shift',
				'meta': 'Cmd',
				'cmd': 'Cmd',
				'escape': 'Esc',
				'enter': 'Enter',
				'space': 'Espacio',
				'tab': 'Tab',
				'backspace': 'Retroceso',
				'delete': 'Supr',
				'up': '↑',
				'down': '↓',
				'left': '←',
				'right': '→',
				'?': '?',
				'/': '/'
			};

			const parts = keyCombo.split('+');

			return parts.map(part => {
				const trimmedPart = part.trim().toLowerCase();
				const displayName = keyNames[trimmedPart] || part.toUpperCase();
				return `<kbd class="flavor-kbd">${this.escapeHtml(displayName)}</kbd>`;
			}).join(' + ');
		}

		/**
         * Escapa HTML para prevenir XSS
         * @param {string} text
         * @returns {string}
         */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		/**
         * Obtiene todos los atajos registrados
         * @returns {Array}
         */
		getAll() {
			return Array.from(this.shortcuts.values());
		}

		/**
         * Obtiene atajos por categoría
         * @param {string} category
         * @returns {Array}
         */
		getByCategory(category) {
			return this.categories.get(category) || [];
		}

		/**
         * Obtiene todas las categorías
         * @returns {Array}
         */
		getCategories() {
			return Array.from(this.categories.keys());
		}

		/**
         * Destruye la instancia y limpia recursos
         */
		destroy() {
			// Remover event listener
			document.removeEventListener('keydown', this.handleKeyDown.bind(this), true);
			this.eventListenerAttached = false;

			// Remover modal
			const modal = document.getElementById(this.config.helpModalId);
			if (modal) {
				modal.remove();
			}

			// Limpiar atajos
			this.shortcuts.clear();
			this.categories.clear();

			// Remover referencia global
			if (window.FlavorShortcuts === this) {
				delete window.FlavorShortcuts;
			}
		}
	}

	/**
     * Inicialización automática cuando el DOM está listo
     */
	function initializeFlavorShortcuts() {
		// Verificar si ya existe una instancia
		if (window.FlavorShortcuts) {
			return window.FlavorShortcuts;
		}

		// Obtener configuración desde data attribute o variable global
		let config = {};

		const configElement = document.querySelector('[data-flavor-shortcuts-config]');
		if (configElement) {
			try {
				config = JSON.parse(configElement.dataset.flavorShortcutsConfig);
			} catch (error) {
				console.warn('[FlavorShortcuts] Error parsing config:', error);
			}
		}

		if (window.flavorShortcutsConfig) {
			config = { ...config, ...window.flavorShortcutsConfig };
		}

		// Crear instancia global
		window.FlavorShortcuts = new FlavorKeyboardShortcuts(config);

		return window.FlavorShortcuts;
	}

	// Inicializar cuando el DOM esté listo
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeFlavorShortcuts);
	} else {
		initializeFlavorShortcuts();
	}

	// Exponer clase para uso avanzado
	window.FlavorKeyboardShortcuts = FlavorKeyboardShortcuts;

})(window, document);
