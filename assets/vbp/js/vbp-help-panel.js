/**
 * Visual Builder Pro - Panel de ayuda in-app.
 *
 * Registra un store Alpine global "vbpHelp" con open()/close()/isOpen()
 * para que cualquier botón del editor pueda abrir el panel de ayuda de
 * un bloque concreto por id (ej: 'dynamic-list').
 *
 * También define el componente vbpHelpPanel con la lógica de
 * copyToClipboard para los snippets de código. Usa la Clipboard API si
 * está disponible, fallback a document.execCommand('copy') para navegadores
 * antiguos. Feedback visual: el botón cambia a "✓ Copiado" durante 1500 ms.
 *
 * @since 3.6.0
 */

(function () {
    'use strict';

    function registrarStore() {
        if (typeof Alpine === 'undefined') return false;
        if (typeof Alpine.store('vbpHelp') !== 'undefined') return true;

        Alpine.store('vbpHelp', {
            panelAbierto: null,
            open: function (panelId) { this.panelAbierto = panelId; },
            close: function () { this.panelAbierto = null; },
            isOpen: function (panelId) { return this.panelAbierto === panelId; }
        });

        Alpine.data('vbpHelpPanel', function () {
            return {
                isOpen: function (panelId) {
                    return Alpine.store('vbpHelp').isOpen(panelId);
                },
                close: function () {
                    Alpine.store('vbpHelp').close();
                },
                copyToClipboard: function (boton) {
                    var targetId = boton.getAttribute('data-copy-target');
                    if (!targetId) return;
                    var target = document.getElementById(targetId);
                    if (!target) return;

                    var texto = target.textContent || target.innerText || '';

                    var feedback = function () {
                        var original = boton.textContent;
                        boton.textContent = '✓ ¡Copiado!';
                        boton.style.background = '#10b981';
                        setTimeout(function () {
                            boton.textContent = original;
                            boton.style.background = '';
                        }, 1500);
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(texto).then(feedback, function () {
                            fallbackCopy(texto) && feedback();
                        });
                    } else if (fallbackCopy(texto)) {
                        feedback();
                    }
                }
            };
        });

        return true;
    }

    /**
     * Fallback para navegadores sin Clipboard API (principalmente
     * contextos no-secure). Crea textarea temporal, selecciona, exec
     * copy y limpia. Devuelve true si funcionó.
     */
    function fallbackCopy(texto) {
        try {
            var textarea = document.createElement('textarea');
            textarea.value = texto;
            textarea.style.cssText = 'position:fixed;top:-9999px;left:-9999px;';
            document.body.appendChild(textarea);
            textarea.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(textarea);
            return ok;
        } catch (e) {
            return false;
        }
    }

    if (typeof Alpine !== 'undefined') {
        registrarStore();
    }
    document.addEventListener('alpine:init', registrarStore);
})();
