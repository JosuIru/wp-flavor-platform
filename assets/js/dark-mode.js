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
(function() {
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
        init: function() {
            this.loadPreference();
            this.setupToggleButtons();
            this.watchSystemPreference();
            this.setupTransitions();
        },

        /**
         * Carga la preferencia guardada del usuario
         */
        loadPreference: function() {
            var savedTheme = localStorage.getItem(this.storageKey);

            if (savedTheme) {
                // Usuario tiene preferencia guardada
                document.documentElement.setAttribute('data-theme', savedTheme);
                this.updateToggleButtons(savedTheme);
            } else {
                // Usar preferencia del sistema
                var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var currentTheme = systemPrefersDark ? 'dark' : 'light';
                this.updateToggleButtons(currentTheme);
            }
        },

        /**
         * Obtiene el tema actual
         * @returns {string} 'dark' o 'light'
         */
        getCurrentTheme: function() {
            var explicitTheme = document.documentElement.getAttribute('data-theme');
            if (explicitTheme) {
                return explicitTheme;
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        },

        /**
         * Cambia el tema
         * @param {string} theme - 'dark', 'light' o 'auto'
         */
        setTheme: function(theme) {
            if (theme === 'auto') {
                // Eliminar preferencia manual y usar sistema
                localStorage.removeItem(this.storageKey);
                document.documentElement.removeAttribute('data-theme');
                var systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                this.updateToggleButtons(systemTheme);
                this.dispatchChangeEvent(systemTheme, true);
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
        toggle: function() {
            var currentTheme = this.getCurrentTheme();
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            this.setTheme(newTheme);
        },

        /**
         * Configura todos los botones de toggle en la pagina
         */
        setupToggleButtons: function() {
            var self = this;

            // Buscar botones con el atributo data-dark-mode-toggle
            document.querySelectorAll('[data-dark-mode-toggle]').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    self.toggle();
                });
            });

            // Buscar botones con clase especifica
            document.querySelectorAll('.flavor-dark-mode-toggle').forEach(function(button) {
                if (!button.hasAttribute('data-dark-mode-toggle')) {
                    button.addEventListener('click', function(event) {
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
        updateToggleButtons: function(theme) {
            var isDark = theme === 'dark';

            document.querySelectorAll('[data-dark-mode-toggle], .flavor-dark-mode-toggle').forEach(function(button) {
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
         * Observa cambios en la preferencia del sistema
         */
        watchSystemPreference: function() {
            var self = this;
            var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            var handleChange = function(event) {
                // Solo aplicar si no hay preferencia manual guardada
                if (!localStorage.getItem(self.storageKey)) {
                    var newTheme = event.matches ? 'dark' : 'light';
                    self.updateToggleButtons(newTheme);
                    self.dispatchChangeEvent(newTheme, true);
                }
            };

            // Soporte para navegadores modernos y legacy
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
            } else if (mediaQuery.addListener) {
                mediaQuery.addListener(handleChange);
            }
        },

        /**
         * Configura transiciones suaves al cambiar de tema
         */
        setupTransitions: function() {
            // Agregar clase para habilitar transiciones despues de la carga inicial
            // Esto evita flash de transicion al cargar la pagina
            window.addEventListener('load', function() {
                document.documentElement.classList.add('flavor-theme-transitions');
            });
        },

        /**
         * Emite un evento personalizado cuando cambia el tema
         * @param {string} theme - Nuevo tema
         * @param {boolean} isSystemChange - Si el cambio fue por preferencia del sistema
         */
        dispatchChangeEvent: function(theme, isSystemChange) {
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
        isDark: function() {
            return this.getCurrentTheme() === 'dark';
        },

        /**
         * Verifica si hay una preferencia manual guardada
         * @returns {boolean}
         */
        hasManualPreference: function() {
            return localStorage.getItem(this.storageKey) !== null;
        },

        /**
         * Resetea a la preferencia del sistema
         */
        resetToSystem: function() {
            this.setTheme('auto');
        }
    };

    /**
     * Inicializar cuando el DOM este listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
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
    (function() {
        var savedTheme = localStorage.getItem('flavor-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();

})();
