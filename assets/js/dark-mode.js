/**
 * Flavor Dark Mode Toggle
 *
 * Sistema de gestion de tema oscuro para Flavor Chat IA.
 * Soporta:
 * - Deteccion automatica de preferencia del sistema
 * - Toggle manual con persistencia en localStorage
 * - Multiples botones de toggle en la pagina
 * - Transiciones suaves entre temas
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */
(function () {
	'use strict';

	/**
     * Namespace global para el Dark Mode
     */
	window.FlavorDarkMode = {
		/**
         * Nombre de la clave en localStorage
         */
		storageKey: 'flavor-theme',

		/**
         * Evento personalizado cuando cambia el tema
         */
		changeEvent: 'flavor:theme-change',

		/**
         * Inicializa el sistema de dark mode
         */
		init: function () {
			this.loadPreference();
			this.setupToggleButtons();
			this.setupTransitions();
			// NOTA: no se observa la preferencia del SO (prefers-color-scheme).
			// El default del portal es CLARO forzado; solo un toggle/preferencia
			// guardada explicita puede activar dark. Ver watchSystemPreference().
		},

		/**
         * Carga la preferencia guardada del usuario.
         *
         * Solo se aplica dark/light cuando hay una preferencia EXPLICITA en
         * localStorage. NO se deriva el tema de prefers-color-scheme: el portal
         * arranca en claro por defecto (decision de producto) y el atributo
         * data-theme lo emite el servidor (class-theme-customizer.php).
         */
		loadPreference: function () {
			var savedTheme = localStorage.getItem(this.storageKey);

			if (savedTheme) {
				// Usuario tiene preferencia guardada explicita
				document.documentElement.setAttribute('data-theme', savedTheme);
				this.updateToggleButtons(savedTheme);
			} else {
				// Sin preferencia guardada: respetar el tema emitido por el
				// servidor (claro por defecto). No leer prefers-color-scheme.
				this.updateToggleButtons(this.getCurrentTheme());
			}
		},

		/**
         * Obtiene el tema actual
         * @returns {string} 'dark' o 'light'
         */
		getCurrentTheme: function () {
			var explicitTheme = document.documentElement.getAttribute('data-theme');
			if (explicitTheme) {
				return explicitTheme;
			}
			// Sin atributo explicito el default es claro. NO se consulta
			// prefers-color-scheme (auto-dark por SO retirado a proposito).
			return 'light';
		},

		/**
         * Cambia el tema
         * @param {string} theme - 'dark', 'light' o 'auto'
         */
		setTheme: function (theme) {
			if (theme === 'auto') {
				// Eliminar preferencia manual y volver al default forzado (claro).
				// NO se deriva el tema de prefers-color-scheme: el auto-dark por
				// SO se retiro para no romper el portal claro por defecto.
				localStorage.removeItem(this.storageKey);
				document.documentElement.removeAttribute('data-theme');
				this.updateToggleButtons('light');
				this.dispatchChangeEvent('light', true);
			} else {
				document.documentElement.setAttribute('data-theme', theme);
				localStorage.setItem(this.storageKey, theme);
				this.updateToggleButtons(theme);
				this.dispatchChangeEvent(theme, false);
			}
		},

		/**
         * Alterna entre dark y light mode
         */
		toggle: function () {
			var currentTheme = this.getCurrentTheme();
			var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
			this.setTheme(newTheme);
		},

		/**
         * Configura todos los botones de toggle en la pagina
         */
		setupToggleButtons: function () {
			var self = this;

			// Buscar botones con el atributo data-dark-mode-toggle
			document.querySelectorAll('[data-dark-mode-toggle]').forEach(function (button) {
				button.addEventListener('click', function (event) {
					event.preventDefault();
					self.toggle();
				});
			});

			// Buscar botones con clase especifica
			document.querySelectorAll('.flavor-dark-mode-toggle').forEach(function (button) {
				if (!button.hasAttribute('data-dark-mode-toggle')) {
					button.addEventListener('click', function (event) {
						event.preventDefault();
						self.toggle();
					});
				}
			});

			// Actualizar estado inicial de los botones
			this.updateToggleButtons(this.getCurrentTheme());
		},

		/**
         * Actualiza el estado visual de todos los botones de toggle
         * @param {string} theme - Tema actual
         */
		updateToggleButtons: function (theme) {
			var isDark = theme === 'dark';

			document.querySelectorAll('[data-dark-mode-toggle], .flavor-dark-mode-toggle').forEach(function (button) {
				// Actualizar iconos si existen
				var sunIcon = button.querySelector('.icon-sun, .flavor-icon-sun, [data-icon="sun"]');
				var moonIcon = button.querySelector('.icon-moon, .flavor-icon-moon, [data-icon="moon"]');

				if (sunIcon) {
					sunIcon.style.display = isDark ? 'block' : 'none';
				}
				if (moonIcon) {
					moonIcon.style.display = isDark ? 'none' : 'block';
				}

				// Actualizar aria-label para accesibilidad
				var label = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
				button.setAttribute('aria-label', label);
				button.setAttribute('title', label);

				// Actualizar atributo de estado
				button.setAttribute('data-theme-active', theme);

				// Actualizar texto si existe
				var textElement = button.querySelector('.flavor-theme-text');
				if (textElement) {
					textElement.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
				}
			});
		},

		/**
         * Observa cambios en la preferencia del sistema.
         *
         * DESACTIVADO a proposito: el auto-dark por prefers-color-scheme se
         * retiro para que el portal coincida con la decision "claro por
         * defecto". Se mantiene como no-op para compatibilidad con cualquier
         * codigo externo que aun lo invoque. NO reintroducir el listener de
         * matchMedia aqui sin coordinar con el resto de gestores de tema.
         */
		watchSystemPreference: function () {
			/* no-op: auto-dark por SO retirado intencionadamente */
		},

		/**
         * Configura transiciones suaves al cambiar de tema
         */
		setupTransitions: function () {
			// Agregar clase para habilitar transiciones despues de la carga inicial
			// Esto evita flash de transicion al cargar la pagina
			window.addEventListener('load', function () {
				document.documentElement.classList.add('flavor-theme-transitions');
			});
		},

		/**
         * Emite un evento personalizado cuando cambia el tema
         * @param {string} theme - Nuevo tema
         * @param {boolean} isSystemChange - Si el cambio fue por preferencia del sistema
         */
		dispatchChangeEvent: function (theme, isSystemChange) {
			var event = new CustomEvent(this.changeEvent, {
				detail: {
					theme: theme,
					isDark: theme === 'dark',
					isSystemChange: isSystemChange
				},
				bubbles: true
			});
			document.dispatchEvent(event);
		},

		/**
         * Verifica si el modo oscuro esta activo
         * @returns {boolean}
         */
		isDark: function () {
			return this.getCurrentTheme() === 'dark';
		},

		/**
         * Verifica si hay una preferencia manual guardada
         * @returns {boolean}
         */
		hasManualPreference: function () {
			return localStorage.getItem(this.storageKey) !== null;
		},

		/**
         * Resetea a la preferencia del sistema
         */
		resetToSystem: function () {
			this.setTheme('auto');
		}
	};

	/**
     * Inicializar cuando el DOM este listo
     */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			FlavorDarkMode.init();
		});
	} else {
		// DOM ya esta listo
		FlavorDarkMode.init();
	}

	/**
     * Aplicar tema lo antes posible para evitar flash
     * (script debe estar en el head sin defer/async)
     */
	(function () {
		var savedTheme = localStorage.getItem('flavor-theme');
		if (savedTheme) {
			document.documentElement.setAttribute('data-theme', savedTheme);
		}
	})();

})();
