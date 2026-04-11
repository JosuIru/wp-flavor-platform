/**
 * Visual Builder Pro - Dev Mode Store Integration
 * Integración con el store principal de VBP
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP DevStore]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP DevStore]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP DevStore]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

/**
 * Extender el store principal de VBP con funcionalidad de Dev Mode
 */
document.addEventListener('alpine:init', function() {
    // Esperar a que el store principal esté disponible
    var checkStore = setInterval(function() {
        var vbpStore = Alpine.store('vbp');
        if (vbpStore) {
            clearInterval(checkStore);
            extendVBPStore(vbpStore);
        }
    }, 100);

    // Timeout después de 5 segundos
    setTimeout(function() {
        clearInterval(checkStore);
    }, 5000);
});

/**
 * Extiende el store VBP con métodos de Dev Mode
 * @param {object} vbpStore - Store principal de VBP
 */
function extendVBPStore(vbpStore) {
    // Agregar estado de dev mode al store principal
    vbpStore.devMode = {
        enabled: false,

        /**
         * Toggle Dev Mode
         */
        toggle: function() {
            var devModeStore = Alpine.store('vbpDevMode');
            if (devModeStore) {
                devModeStore.toggle();
            }
        },

        /**
         * Obtiene CSS del elemento seleccionado
         * @param {string} elementId - ID del elemento
         * @param {string} format - Formato de salida
         * @returns {string} Código CSS
         */
        getElementCSS: function(elementId, format) {
            format = format || 'css';
            var element = vbpStore.getElementDeep(elementId);

            if (!element || !window.VBPStyleExtractor || !window.VBPCodeGenerator) {
                return '';
            }

            var styles = window.VBPStyleExtractor.extractFromVBP(element);
            var flatStyles = window.VBPStyleExtractor.flatten(styles);
            var selector = '.' + generateClassName(element);

            switch (format) {
                case 'css':
                    return window.VBPCodeGenerator.generateCSS(flatStyles, selector);
                case 'scss':
                    return window.VBPCodeGenerator.generateSCSS(flatStyles, selector);
                case 'tailwind':
                    return window.VBPCodeGenerator.generateTailwind(flatStyles);
                case 'styled-components':
                    return window.VBPCodeGenerator.generateStyledComponents(flatStyles, toComponentName(element.name || element.type));
                case 'css-in-js':
                    return window.VBPCodeGenerator.generateCSSinJS(flatStyles);
                default:
                    return window.VBPCodeGenerator.generateCSS(flatStyles, selector);
            }
        },

        /**
         * Obtiene código de componente
         * @param {string} elementId - ID del elemento
         * @param {string} framework - Framework (react, vue, html)
         * @returns {string} Código del componente
         */
        getElementCode: function(elementId, framework) {
            framework = framework || 'react';
            var element = vbpStore.getElementDeep(elementId);

            if (!element || !window.VBPComponentGenerator || !window.VBPStyleExtractor) {
                return '';
            }

            var styles = window.VBPStyleExtractor.extractFromVBP(element);
            var flatStyles = window.VBPStyleExtractor.flatten(styles);

            switch (framework) {
                case 'react':
                    return window.VBPComponentGenerator.generateReact(element, flatStyles);
                case 'vue':
                    return window.VBPComponentGenerator.generateVue(element, flatStyles);
                case 'html':
                    return window.VBPComponentGenerator.generateHTML(element, flatStyles);
                default:
                    return window.VBPComponentGenerator.generateReact(element, flatStyles);
            }
        },

        /**
         * Exporta assets de un elemento
         * @param {string} elementId - ID del elemento
         * @param {object} options - Opciones de exportación
         */
        exportAssets: function(elementId, options) {
            options = options || {};
            var element = vbpStore.getElementDeep(elementId);

            if (!element || !window.VBPAssetExporter) {
                return;
            }

            var assets = window.VBPAssetExporter.extractAssets(element);

            if (assets.length === 0) {
                vbpLog.warn('No hay assets para exportar');
                return;
            }

            if (options.zip && typeof JSZip !== 'undefined') {
                var filename = (element.name || element.type || 'assets') + '.zip';
                window.VBPAssetExporter.generateZip(assets, filename);
            } else {
                window.VBPAssetExporter.downloadAllAssets(assets);
            }
        },

        /**
         * Obtiene tokens usados en un elemento
         * @param {string} elementId - ID del elemento
         * @returns {object} Tokens encontrados
         */
        getUsedTokens: function(elementId) {
            var element = vbpStore.getElementDeep(elementId);

            if (!element || !window.VBPTokenExtractor) {
                return {};
            }

            return window.VBPTokenExtractor.extractUsedTokens(element);
        },

        /**
         * Mide distancia entre dos elementos
         * @param {string} elementAId - ID del primer elemento
         * @param {string} elementBId - ID del segundo elemento
         * @returns {object} Medidas de distancia
         */
        measureDistance: function(elementAId, elementBId) {
            if (!window.VBPMeasurement) {
                return null;
            }

            var domElementA = document.querySelector('[data-vbp-id="' + elementAId + '"]');
            var domElementB = document.querySelector('[data-vbp-id="' + elementBId + '"]');

            if (!domElementA || !domElementB) {
                return null;
            }

            return window.VBPMeasurement.measureDistance(domElementA, domElementB);
        },

        /**
         * Copia contenido al portapapeles
         * @param {string} content - Contenido a copiar
         */
        copyToClipboard: function(content) {
            var devModeStore = Alpine.store('vbpDevMode');
            if (devModeStore) {
                devModeStore.copyToClipboard(content);
            } else {
                navigator.clipboard.writeText(content).catch(function(error) {
                    vbpLog.error('Error al copiar:', error);
                });
            }
        }
    };

    // Agregar watcher para sincronizar selección con dev mode
    document.addEventListener('vbp:selection:changed', function(event) {
        var devModeStore = Alpine.store('vbpDevMode');
        if (devModeStore && devModeStore.enabled && event.detail && event.detail.elementIds && event.detail.elementIds.length > 0) {
            devModeStore.inspect(event.detail.elementIds[0]);
        }
    });

    // Disparar evento cuando cambia selección
    var originalSelectElement = vbpStore.selectElement;
    if (originalSelectElement) {
        vbpStore.selectElement = function(elementId, multiSelect) {
            var result = originalSelectElement.call(this, elementId, multiSelect);

            // Disparar evento de selección
            document.dispatchEvent(new CustomEvent('vbp:element:selected', {
                detail: { elementId: elementId }
            }));

            return result;
        };
    }

    vbpLog.log('VBP Dev Store integration loaded');
}

// Helper functions
function generateClassName(element) {
    if (!element) return 'element';
    var name = element.name || element.type || 'element';
    return name
        .toLowerCase()
        .replace(/[\s_]+/g, '-')
        .replace(/[^a-z0-9-]/g, '');
}

function toComponentName(str) {
    return str
        .split(/[\s-_]+/)
        .map(function(word) {
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        })
        .join('');
}

/**
 * Syntax Highlighter para código
 */
const VBPSyntaxHighlighter = {
    /**
     * Aplica highlighting a código CSS
     * @param {string} code - Código CSS
     * @returns {string} HTML con highlighting
     */
    highlightCSS: function(code) {
        // Escapar HTML
        code = this.escapeHtml(code);

        // Comentarios
        code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

        // Selectores
        code = code.replace(/^([^{\n]+)({)/gm, '<span class="selector">$1</span>$2');

        // Propiedades y valores
        code = code.replace(/([a-z-]+)(\s*:\s*)([^;]+)(;)/gi,
            '<span class="property">$1</span>$2<span class="value">$3</span>$4');

        return code;
    },

    /**
     * Aplica highlighting a código JavaScript/JSX
     * @param {string} code - Código JS
     * @returns {string} HTML con highlighting
     */
    highlightJS: function(code) {
        // Escapar HTML
        code = this.escapeHtml(code);

        // Comentarios
        code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
        code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

        // Strings
        code = code.replace(/("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|`(?:[^`\\]|\\.)*`)/g, '<span class="string">$1</span>');

        // Keywords
        var keywords = ['import', 'export', 'from', 'const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'class', 'extends', 'new', 'this', 'default', 'async', 'await'];
        keywords.forEach(function(keyword) {
            var regex = new RegExp('\\b(' + keyword + ')\\b', 'g');
            code = code.replace(regex, '<span class="keyword">$1</span>');
        });

        // Funciones
        code = code.replace(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(/g, '<span class="function">$1</span>(');

        // Números
        code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

        return code;
    },

    /**
     * Aplica highlighting a código HTML/JSX
     * @param {string} code - Código HTML
     * @returns {string} HTML con highlighting
     */
    highlightHTML: function(code) {
        // Escapar HTML primero
        code = this.escapeHtml(code);

        // Tags
        code = code.replace(/(&lt;\/?)([\w-]+)/g, '$1<span class="tag">$2</span>');

        // Atributos
        code = code.replace(/\s([\w-]+)(=)/g, ' <span class="property">$1</span>$2');

        // Strings (valores de atributos)
        code = code.replace(/("(?:[^"\\]|\\.)*")/g, '<span class="string">$1</span>');

        // Comentarios HTML
        code = code.replace(/(&lt;!--[\s\S]*?--&gt;)/g, '<span class="comment">$1</span>');

        return code;
    },

    /**
     * Detecta tipo de código y aplica highlighting
     * @param {string} code - Código
     * @param {string} language - Lenguaje (css, js, html, json)
     * @returns {string} HTML con highlighting
     */
    highlight: function(code, language) {
        switch (language) {
            case 'css':
            case 'scss':
                return this.highlightCSS(code);
            case 'javascript':
            case 'js':
            case 'jsx':
            case 'styled-components':
            case 'css-in-js':
                return this.highlightJS(code);
            case 'html':
            case 'vue':
                return this.highlightHTML(code);
            case 'json':
                return this.highlightJS(code);
            default:
                return this.escapeHtml(code);
        }
    },

    /**
     * Escapa caracteres HTML
     * @param {string} text - Texto a escapar
     * @returns {string} Texto escapado
     */
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Exportar globalmente
window.VBPSyntaxHighlighter = VBPSyntaxHighlighter;

/**
 * Keyboard shortcuts manager para Dev Mode
 */
const VBPDevKeyboardShortcuts = {
    shortcuts: {
        'Cmd+Shift+D': {
            description: 'Toggle Dev Mode',
            action: function() {
                var devModeStore = Alpine.store('vbpDevMode');
                if (devModeStore) {
                    devModeStore.toggle();
                }
            }
        },
        'Cmd+C': {
            description: 'Copy code (in Dev Mode)',
            action: function() {
                var devModeStore = Alpine.store('vbpDevMode');
                if (devModeStore && devModeStore.enabled && devModeStore.isPanelOpen) {
                    devModeStore.copyCurrentCode();
                    return true; // Prevenir default
                }
                return false;
            }
        },
        'Alt+Click': {
            description: 'Measure distance between elements',
            action: null // Manejado en evento click
        },
        'Escape': {
            description: 'Exit measure mode',
            action: function() {
                var devModeStore = Alpine.store('vbpDevMode');
                if (devModeStore && devModeStore.measureMode) {
                    devModeStore.toggleMeasureMode();
                    return true;
                }
                return false;
            }
        }
    },

    /**
     * Inicializa shortcuts
     */
    init: function() {
        var self = this;

        document.addEventListener('keydown', function(event) {
            // Cmd/Ctrl+Shift+D
            if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key.toLowerCase() === 'd') {
                event.preventDefault();
                self.shortcuts['Cmd+Shift+D'].action();
                return;
            }

            // Escape
            if (event.key === 'Escape') {
                if (self.shortcuts['Escape'].action()) {
                    event.preventDefault();
                }
            }
        });
    },

    /**
     * Obtiene lista de shortcuts para mostrar en UI
     * @returns {array} Lista de shortcuts
     */
    getShortcutsList: function() {
        var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        var modKey = isMac ? 'Cmd' : 'Ctrl';

        return [
            { keys: [modKey, 'Shift', 'D'], description: 'Toggle Dev Mode' },
            { keys: [modKey, 'C'], description: 'Copiar código' },
            { keys: ['Alt', 'Click'], description: 'Medir distancia' },
            { keys: ['Esc'], description: 'Salir modo medición' }
        ];
    }
};

// Inicializar shortcuts
VBPDevKeyboardShortcuts.init();

// Exportar globalmente
window.VBPDevKeyboardShortcuts = VBPDevKeyboardShortcuts;

vbpLog.log('VBP Dev Store utilities loaded');
