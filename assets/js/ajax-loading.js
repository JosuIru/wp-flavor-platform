/**
 * Flavor AJAX Loading System
 *
 * Sistema unificado de spinners y estados de carga para operaciones AJAX
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */
(function() {
    'use strict';

    window.FlavorLoading = {
        /**
         * Mostrar/ocultar loading en botón
         *
         * @param {HTMLElement} button - El botón a modificar
         * @param {boolean} loading - true para mostrar loading, false para restaurar
         */
        buttonLoading: function(button, loading = true) {
            if (!button) return;

            if (loading) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<span class="flavor-spinner"></span> Cargando...';
                button.classList.add('flavor-btn--loading');
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || button.innerHTML;
                button.classList.remove('flavor-btn--loading');
            }
        },

        /**
         * Mostrar overlay de carga en contenedor
         *
         * @param {HTMLElement} container - El contenedor donde mostrar el overlay
         * @param {string} text - Texto opcional a mostrar
         * @returns {HTMLElement} El elemento overlay creado
         */
        showOverlay: function(container, text = 'Cargando...') {
            if (!container) return null;

            // Evitar duplicados
            this.hideOverlay(container);

            const overlay = document.createElement('div');
            overlay.className = 'flavor-loading-overlay';
            overlay.innerHTML = `
                <div class="flavor-loading-spinner">
                    <svg class="flavor-spinner-svg" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
                    </svg>
                    <span class="flavor-loading-text">${this.escapeHtml(text)}</span>
                </div>
            `;

            const computedPosition = window.getComputedStyle(container).position;
            if (computedPosition === 'static') {
                container.style.position = 'relative';
            }

            container.appendChild(overlay);
            return overlay;
        },

        /**
         * Ocultar overlay de carga
         *
         * @param {HTMLElement} container - El contenedor con el overlay
         */
        hideOverlay: function(container) {
            if (!container) return;

            const overlay = container.querySelector('.flavor-loading-overlay');
            if (overlay) {
                overlay.classList.add('flavor-loading--fade-out');
                setTimeout(() => overlay.remove(), 300);
            }
        },

        /**
         * Mostrar notificación toast
         *
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
         * @param {number} duration - Duración en ms (default: 3000)
         */
        toast: function(message, type = 'success', duration = 3000) {
            const iconMap = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            const toast = document.createElement('div');
            toast.className = `flavor-toast flavor-toast--${type}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'polite');
            toast.innerHTML = `
                <span class="flavor-toast__icon">${iconMap[type] || iconMap.info}</span>
                <span class="flavor-toast__message">${this.escapeHtml(message)}</span>
                <button class="flavor-toast__close" aria-label="Cerrar">&times;</button>
            `;

            // Cerrar al hacer clic en X
            toast.querySelector('.flavor-toast__close').addEventListener('click', () => {
                this.removeToast(toast);
            });

            document.body.appendChild(toast);

            // Trigger reflow para animación
            toast.offsetHeight;
            setTimeout(() => toast.classList.add('flavor-toast--visible'), 10);

            // Auto-cerrar
            setTimeout(() => this.removeToast(toast), duration);
        },

        /**
         * Eliminar toast con animación
         *
         * @param {HTMLElement} toast - Elemento toast a eliminar
         */
        removeToast: function(toast) {
            if (!toast || !toast.parentNode) return;

            toast.classList.remove('flavor-toast--visible');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        },

        /**
         * Wrapper para fetch con loading automático
         *
         * @param {string} url - URL de la petición
         * @param {Object} options - Opciones de fetch
         * @param {HTMLElement|null} button - Botón opcional para loading
         * @param {HTMLElement|null} container - Contenedor opcional para overlay
         * @returns {Promise<Object>} Respuesta JSON
         */
        fetch: async function(url, options = {}, button = null, container = null) {
            if (button) this.buttonLoading(button, true);
            if (container) this.showOverlay(container);

            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.toast(data.message || 'Operación exitosa', 'success');
                } else {
                    this.toast(data.message || 'Error en la operación', 'error');
                }

                return data;
            } catch (error) {
                this.toast('Error de conexión', 'error');
                console.error('FlavorLoading fetch error:', error);
                throw error;
            } finally {
                if (button) this.buttonLoading(button, false);
                if (container) this.hideOverlay(container);
            }
        },

        /**
         * Wrapper para jQuery AJAX (si jQuery está disponible)
         *
         * @param {Object} ajaxOptions - Opciones de jQuery.ajax
         * @param {HTMLElement|null} button - Botón opcional para loading
         * @param {HTMLElement|null} container - Contenedor opcional para overlay
         * @returns {Promise} Promise del AJAX
         */
        ajax: function(ajaxOptions, button = null, container = null) {
            if (typeof jQuery === 'undefined') {
                console.warn('FlavorLoading.ajax requiere jQuery');
                return Promise.reject(new Error('jQuery no disponible'));
            }

            const self = this;

            if (button) this.buttonLoading(button, true);
            if (container) this.showOverlay(container);

            return jQuery.ajax(ajaxOptions)
                .done(function(response) {
                    if (response.success) {
                        self.toast(response.data?.message || 'Operación exitosa', 'success');
                    } else {
                        self.toast(response.data?.message || 'Error en la operación', 'error');
                    }
                })
                .fail(function(jqXHR, textStatus) {
                    self.toast('Error de conexión: ' + textStatus, 'error');
                })
                .always(function() {
                    if (button) self.buttonLoading(button, false);
                    if (container) self.hideOverlay(container);
                });
        },

        /**
         * Escapar HTML para prevenir XSS
         *
         * @param {string} text - Texto a escapar
         * @returns {string} Texto escapado
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Mostrar spinner inline
         *
         * @param {HTMLElement} element - Elemento donde insertar el spinner
         * @param {string} position - 'before', 'after', 'replace'
         * @returns {HTMLElement} El spinner creado
         */
        inlineSpinner: function(element, position = 'after') {
            if (!element) return null;

            const spinner = document.createElement('span');
            spinner.className = 'flavor-spinner flavor-spinner--inline';

            switch (position) {
                case 'before':
                    element.insertBefore(spinner, element.firstChild);
                    break;
                case 'replace':
                    element.dataset.originalContent = element.innerHTML;
                    element.innerHTML = '';
                    element.appendChild(spinner);
                    break;
                case 'after':
                default:
                    element.appendChild(spinner);
            }

            return spinner;
        },

        /**
         * Eliminar spinner inline
         *
         * @param {HTMLElement} element - Elemento con el spinner
         */
        removeInlineSpinner: function(element) {
            if (!element) return;

            const spinner = element.querySelector('.flavor-spinner--inline');
            if (spinner) {
                spinner.remove();
            }

            // Restaurar contenido si fue reemplazado
            if (element.dataset.originalContent) {
                element.innerHTML = element.dataset.originalContent;
                delete element.dataset.originalContent;
            }
        },

        /**
         * Skeleton loader para contenido
         *
         * @param {HTMLElement} container - Contenedor donde mostrar skeleton
         * @param {number} lines - Número de líneas skeleton
         * @returns {HTMLElement} El skeleton creado
         */
        showSkeleton: function(container, lines = 3) {
            if (!container) return null;

            const skeleton = document.createElement('div');
            skeleton.className = 'flavor-skeleton';

            for (let i = 0; i < lines; i++) {
                const line = document.createElement('div');
                line.className = 'flavor-skeleton__line';
                line.style.width = (70 + Math.random() * 30) + '%';
                skeleton.appendChild(line);
            }

            container.appendChild(skeleton);
            return skeleton;
        },

        /**
         * Ocultar skeleton loader
         *
         * @param {HTMLElement} container - Contenedor con skeleton
         */
        hideSkeleton: function(container) {
            if (!container) return;

            const skeleton = container.querySelector('.flavor-skeleton');
            if (skeleton) {
                skeleton.remove();
            }
        }
    };

    // Exponer globalmente
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.FlavorLoading;
    }
})();
