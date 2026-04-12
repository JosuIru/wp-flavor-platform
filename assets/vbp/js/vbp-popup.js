/**
 * Visual Builder Pro - Popup Frontend
 *
 * Manejo de popups en el frontend.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    var VBPPopup = {
        popups: [],
        shownPopups: [],
        _initialized: false,
        _keydownHandler: null,
        _timers: [],
        _scrollHandlers: [],
        _exitHandlers: [],

        init: function() {
            if (this._initialized) return;
            this._initialized = true;

            var self = this;
            var overlays = document.querySelectorAll('.vbp-popup-overlay');

            overlays.forEach(function(overlay) {
                var popupData = self.getPopupData(overlay);
                self.popups.push(popupData);
                self.setupTrigger(popupData);
            });

            // Event listeners globales (guardar referencia)
            this._keydownHandler = function(e) {
                if (e.key === 'Escape') {
                    self.closeAllPopups();
                }
            };
            document.addEventListener('keydown', this._keydownHandler);
        },

        destroy: function() {
            // Limpiar timers
            this._timers.forEach(function(timerId) {
                clearTimeout(timerId);
            });
            this._timers = [];

            // Limpiar scroll handlers
            this._scrollHandlers.forEach(function(handler) {
                window.removeEventListener('scroll', handler);
            });
            this._scrollHandlers = [];

            // Limpiar exit handlers
            this._exitHandlers.forEach(function(handler) {
                document.removeEventListener('mouseout', handler);
            });
            this._exitHandlers = [];

            // Limpiar keydown handler
            if (this._keydownHandler) {
                document.removeEventListener('keydown', this._keydownHandler);
                this._keydownHandler = null;
            }

            // Cerrar todos los popups
            this.closeAllPopups();

            // Resetear estado
            this.popups = [];
            this.shownPopups = [];
            this._initialized = false;
        },

        getPopupData: function(overlay) {
            return {
                id: overlay.dataset.popupId,
                element: overlay,
                popup: overlay.querySelector('.vbp-popup'),
                trigger: overlay.dataset.trigger || 'time',
                triggerDelay: parseInt(overlay.dataset.triggerDelay, 10) || 3,
                triggerScroll: parseInt(overlay.dataset.triggerScroll, 10) || 50,
                triggerElement: overlay.dataset.triggerElement || '',
                frequency: overlay.dataset.frequency || 'once',
                animation: overlay.dataset.animation || 'fade',
                closeOnOverlay: overlay.dataset.closeOverlay === 'true',
                closeOnEsc: overlay.dataset.closeEsc === 'true',
                shown: false
            };
        },

        setupTrigger: function(popupData) {
            var self = this;

            // Verificar si ya se mostró (según frecuencia)
            if (this.shouldSkip(popupData)) {
                return;
            }

            switch (popupData.trigger) {
                case 'time':
                    var timerId = setTimeout(function() {
                        self.showPopup(popupData);
                    }, popupData.triggerDelay * 1000);
                    this._timers.push(timerId);
                    break;

                case 'scroll':
                    this.setupScrollTrigger(popupData);
                    break;

                case 'exit_intent':
                    this.setupExitIntentTrigger(popupData);
                    break;

                case 'click':
                    this.setupClickTrigger(popupData);
                    break;

                default:
                    // Por defecto, mostrar después de 3 segundos
                    var defaultTimerId = setTimeout(function() {
                        self.showPopup(popupData);
                    }, 3000);
                    this._timers.push(defaultTimerId);
            }
        },

        setupScrollTrigger: function(popupData) {
            var self = this;
            var triggered = false;

            var scrollHandler = function() {
                if (triggered) return;

                var scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;

                if (scrollPercent >= popupData.triggerScroll) {
                    triggered = true;
                    self.showPopup(popupData);
                    window.removeEventListener('scroll', scrollHandler);
                }
            };

            // Guardar referencia para cleanup
            this._scrollHandlers.push(scrollHandler);
            window.addEventListener('scroll', scrollHandler, { passive: true });
        },

        setupExitIntentTrigger: function(popupData) {
            var self = this;
            var triggered = false;

            var exitHandler = function(e) {
                if (triggered) return;

                // Detectar si el mouse sale por arriba de la ventana
                if (e.clientY <= 0) {
                    triggered = true;
                    self.showPopup(popupData);
                    document.removeEventListener('mouseout', exitHandler);
                }
            };

            // Guardar referencia para cleanup
            this._exitHandlers.push(exitHandler);
            document.addEventListener('mouseout', exitHandler);
        },

        setupClickTrigger: function(popupData) {
            var self = this;

            if (!popupData.triggerElement) return;

            var elements = document.querySelectorAll(popupData.triggerElement);
            elements.forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.showPopup(popupData);
                });
            });
        },

        shouldSkip: function(popupData) {
            var storageKey = 'vbp_popup_' + popupData.id;

            switch (popupData.frequency) {
                case 'once':
                    return localStorage.getItem(storageKey) === 'shown';

                case 'session':
                    return sessionStorage.getItem(storageKey) === 'shown';

                case 'always':
                default:
                    return false;
            }
        },

        markAsShown: function(popupData) {
            var storageKey = 'vbp_popup_' + popupData.id;

            switch (popupData.frequency) {
                case 'once':
                    localStorage.setItem(storageKey, 'shown');
                    break;

                case 'session':
                    sessionStorage.setItem(storageKey, 'shown');
                    break;
            }
        },

        showPopup: function(popupData) {
            if (popupData.shown) return;

            var self = this;
            var overlay = popupData.element;
            var popup = popupData.popup;

            // Mostrar overlay
            overlay.classList.add('vbp-popup-overlay--visible');
            document.body.classList.add('vbp-popup-open');

            // Animar popup
            requestAnimationFrame(function() {
                popup.classList.add('vbp-popup--visible');
            });

            popupData.shown = true;
            this.shownPopups.push(popupData);
            this.markAsShown(popupData);

            // Event listeners para cerrar
            if (popupData.closeOnOverlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        self.closePopup(popupData);
                    }
                });
            }

            // Botón cerrar
            var closeBtn = popup.querySelector('.vbp-popup-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    self.closePopup(popupData);
                });
            }

            // Focus trap
            this.trapFocus(popup);
        },

        closePopup: function(popupData) {
            var overlay = popupData.element;
            var popup = popupData.popup;

            popup.classList.remove('vbp-popup--visible');

            setTimeout(function() {
                overlay.classList.remove('vbp-popup-overlay--visible');

                // Remover de popups mostrados
                var index = this.shownPopups.indexOf(popupData);
                if (index > -1) {
                    this.shownPopups.splice(index, 1);
                }

                // Si no hay más popups, quitar clase del body
                if (this.shownPopups.length === 0) {
                    document.body.classList.remove('vbp-popup-open');
                }
            }.bind(this), 300);
        },

        closeAllPopups: function() {
            var self = this;
            this.shownPopups.slice().forEach(function(popupData) {
                if (popupData.closeOnEsc) {
                    self.closePopup(popupData);
                }
            });
        },

        trapFocus: function(popup) {
            var focusableElements = popup.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            var firstFocusable = focusableElements[0];
            var lastFocusable = focusableElements[focusableElements.length - 1];

            firstFocusable.focus();

            popup.addEventListener('keydown', function(e) {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPPopup.init();
        });
    } else {
        VBPPopup.init();
    }

    // Exponer globalmente para uso externo
    window.VBPPopup = VBPPopup;
})();
