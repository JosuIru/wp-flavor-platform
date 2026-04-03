/**
 * Visual Builder Pro - Keyboard Loader
 * Sistema de carga diferida para atajos de teclado
 *
 * Carga el módulo completo de teclado solo cuando el usuario
 * presiona una tecla por primera vez, reduciendo el tiempo
 * de carga inicial.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.18
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    var keyboardLoaded = false;
    var keyboardLoading = false;
    var pendingEvents = [];

    /**
     * Atajos básicos que funcionan sin cargar el módulo completo
     * Estos son los más usados y se manejan de forma ligera
     */
    var basicShortcuts = {
        'ctrl+s': function() {
            var store = Alpine.store('vbp');
            if (store && typeof store.autoSave === 'function') {
                store.autoSave();
            }
        },
        'ctrl+z': function() {
            var store = Alpine.store('vbp');
            if (store && store.canUndo) {
                store.undo();
            }
        },
        'ctrl+shift+z': function() {
            var store = Alpine.store('vbp');
            if (store && store.canRedo) {
                store.redo();
            }
        },
        'ctrl+y': function() {
            var store = Alpine.store('vbp');
            if (store && store.canRedo) {
                store.redo();
            }
        },
        'escape': function() {
            var store = Alpine.store('vbp');
            if (store) {
                store.clearSelection();
            }
        },
        'delete': function() {
            var store = Alpine.store('vbp');
            if (store && store.selection.elementIds.length > 0) {
                store.selection.elementIds.forEach(function(id) {
                    store.removeElement(id);
                });
            }
        },
        'backspace': function() {
            var store = Alpine.store('vbp');
            if (store && store.selection.elementIds.length > 0) {
                store.selection.elementIds.forEach(function(id) {
                    store.removeElement(id);
                });
            }
        }
    };

    /**
     * Obtener la tecla normalizada del evento
     */
    function getKeyCombo(event) {
        var parts = [];
        if (event.ctrlKey || event.metaKey) parts.push('ctrl');
        if (event.shiftKey) parts.push('shift');
        if (event.altKey) parts.push('alt');
        parts.push(event.key.toLowerCase());
        return parts.join('+');
    }

    /**
     * Verificar si el evento debe ser ignorado
     */
    function shouldIgnoreEvent(event) {
        var target = event.target;
        var tagName = target.tagName.toLowerCase();

        // Ignorar si estamos en un campo de texto
        if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
            return true;
        }

        // Ignorar si estamos en un contenteditable
        if (target.isContentEditable) {
            return true;
        }

        return false;
    }

    /**
     * Cargar el módulo completo de teclado
     */
    function loadFullKeyboardModule(callback) {
        if (keyboardLoaded) {
            if (callback) callback();
            return;
        }

        if (keyboardLoading) {
            // Si ya está cargando, encolar el callback
            if (callback) pendingEvents.push(callback);
            return;
        }

        keyboardLoading = true;

        // Crear script element - cargar versión modular optimizada
        var script = document.createElement('script');
        var baseUrl = typeof VBP_Config !== 'undefined' ? VBP_Config.pluginUrl : '';
        script.src = baseUrl + 'assets/vbp/js/vbp-keyboard-modular.js?ver=' + Date.now();
        script.async = true;

        script.onload = function() {
            keyboardLoaded = true;
            keyboardLoading = false;
            vbpLog.log('Módulo de teclado cargado');

            // Ejecutar callbacks pendientes
            if (callback) callback();
            pendingEvents.forEach(function(cb) { cb(); });
            pendingEvents = [];
        };

        script.onerror = function() {
            keyboardLoading = false;
            vbpLog.warn('Error cargando módulo de teclado');
        };

        document.head.appendChild(script);
    }

    /**
     * Manejador de eventos de teclado
     */
    function handleKeydown(event) {
        // Ignorar si estamos en campos de texto
        if (shouldIgnoreEvent(event)) {
            return;
        }

        var keyCombo = getKeyCombo(event);

        // Intentar manejar con atajos básicos primero
        if (basicShortcuts[keyCombo]) {
            event.preventDefault();
            basicShortcuts[keyCombo]();
            return;
        }

        // Para atajos más complejos, cargar el módulo completo
        // Solo si es una combinación de teclas válida (no solo una letra)
        if (event.ctrlKey || event.metaKey || event.altKey ||
            ['f1','f2','f3','f4','f5','f6','f7','f8','f9','f10','f11','f12'].indexOf(event.key.toLowerCase()) !== -1) {

            if (!keyboardLoaded) {
                event.preventDefault();
                // Mostrar indicador de carga
                showLoadingIndicator();

                loadFullKeyboardModule(function() {
                    hideLoadingIndicator();
                    // Re-disparar el evento para que lo maneje el módulo completo
                    var newEvent = new KeyboardEvent('keydown', {
                        key: event.key,
                        code: event.code,
                        ctrlKey: event.ctrlKey,
                        shiftKey: event.shiftKey,
                        altKey: event.altKey,
                        metaKey: event.metaKey,
                        bubbles: true
                    });
                    document.dispatchEvent(newEvent);
                });
            }
        }
    }

    /**
     * Mostrar indicador de carga
     */
    function showLoadingIndicator() {
        var indicator = document.getElementById('vbp-keyboard-loading');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'vbp-keyboard-loading';
            indicator.innerHTML = '⌨️ Cargando atajos...';
            indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: rgba(30, 30, 46, 0.9); color: #cdd6f4; border-radius: 6px; font-size: 13px; z-index: 10000; pointer-events: none;';
            document.body.appendChild(indicator);
        }
        indicator.style.display = 'block';
    }

    /**
     * Ocultar indicador de carga
     */
    function hideLoadingIndicator() {
        var indicator = document.getElementById('vbp-keyboard-loading');
        if (indicator) {
            indicator.style.opacity = '0';
            setTimeout(function() {
                indicator.style.display = 'none';
                indicator.style.opacity = '1';
            }, 300);
        }
    }

    /**
     * Pre-cargar módulo cuando el usuario hace hover sobre el canvas
     * para que esté listo cuando lo necesite
     */
    function setupPreloading() {
        var canvas = document.querySelector('.vbp-canvas-wrapper');
        if (canvas) {
            var preloadTimeout;
            canvas.addEventListener('mouseenter', function() {
                preloadTimeout = setTimeout(function() {
                    if (!keyboardLoaded && !keyboardLoading) {
                        loadFullKeyboardModule();
                    }
                }, 2000); // Precargar después de 2 segundos de hover
            }, { passive: true });

            canvas.addEventListener('mouseleave', function() {
                clearTimeout(preloadTimeout);
            }, { passive: true });
        }
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    function init() {
        // Registrar manejador de teclado
        document.addEventListener('keydown', handleKeydown);

        // Configurar precarga
        if (document.readyState === 'complete') {
            setupPreloading();
        } else {
            window.addEventListener('load', setupPreloading);
        }
    }

    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exponer API para carga manual
    window.VBPKeyboardLoader = {
        load: loadFullKeyboardModule,
        isLoaded: function() { return keyboardLoaded; }
    };

})();
