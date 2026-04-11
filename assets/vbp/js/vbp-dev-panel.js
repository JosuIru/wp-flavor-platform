/**
 * Visual Builder Pro - Dev Panel
 * Panel de inspección para desarrolladores
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP DevPanel]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP DevPanel]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP DevPanel]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

document.addEventListener('alpine:init', function() {
    /**
     * Store para Dev Mode
     */
    Alpine.store('vbpDevMode', {
        // Estado principal
        enabled: false,
        isPanelOpen: false,

        // Elemento seleccionado
        selectedElementId: null,
        selectedDomElement: null,

        // Vista activa
        activeTab: 'css', // css, code, assets, tokens, compare

        // Formato de código
        codeFormat: 'css', // css, scss, tailwind, styled-components, css-in-js

        // Framework para componentes
        componentFramework: 'react', // react, vue, html

        // Unidades
        units: 'px', // px, rem, em, %
        remBase: 16,

        // Estilos extraídos
        extractedStyles: null,
        flatStyles: {},

        // Tokens
        usedTokens: {},
        globalTokens: null,

        // Assets
        assets: [],

        // Medidas
        measurements: null,
        measureMode: false,
        measureStartElement: null,
        distanceMeasurement: null,

        // Comparación
        comparisonResult: null,
        codeStyles: {},

        // Código generado (caché)
        generatedCode: '',

        // Historial de copias
        copyHistory: [],

        /**
         * Activa/desactiva Dev Mode
         */
        toggle: function() {
            this.enabled = !this.enabled;

            if (this.enabled) {
                this.isPanelOpen = true;
                this.initDevMode();
                document.body.classList.add('vbp-dev-mode-active');
            } else {
                this.cleanup();
                document.body.classList.remove('vbp-dev-mode-active');
            }

            // Guardar preferencia
            localStorage.setItem('vbp_dev_mode_enabled', this.enabled ? '1' : '0');

            vbpLog.log('Dev Mode:', this.enabled ? 'ON' : 'OFF');
        },

        /**
         * Inicializa Dev Mode
         */
        initDevMode: function() {
            // Obtener tokens globales si están disponibles
            this.loadGlobalTokens();

            // Sincronizar con selección actual
            this.syncWithSelection();

            // Agregar listeners
            this.addEventListeners();
        },

        /**
         * Limpia recursos al desactivar
         */
        cleanup: function() {
            this.selectedElementId = null;
            this.selectedDomElement = null;
            this.extractedStyles = null;
            this.flatStyles = {};
            this.assets = [];
            this.measurements = null;
            this.measureMode = false;
            this.measureStartElement = null;
            this.distanceMeasurement = null;
            this.comparisonResult = null;
            this.generatedCode = '';

            // Limpiar overlays
            this.clearMeasurementOverlays();

            // Remover listeners
            this.removeEventListeners();
        },

        /**
         * Carga tokens globales del proyecto
         */
        loadGlobalTokens: function() {
            // Intentar obtener tokens de la configuración de VBP
            if (window.VBP_Config && window.VBP_Config.designTokens) {
                this.globalTokens = window.VBP_Config.designTokens;
            } else if (window.VBPTokenExtractor) {
                this.globalTokens = window.VBPTokenExtractor.getDefaultTokens();
            }
        },

        /**
         * Sincroniza con la selección actual del editor
         */
        syncWithSelection: function() {
            var vbpStore = Alpine.store('vbp');
            if (vbpStore && vbpStore.selectedElement) {
                this.inspect(vbpStore.selectedElement.id);
            }
        },

        /**
         * Agrega event listeners
         */
        addEventListeners: function() {
            var self = this;

            // Escuchar cambios de selección
            this._selectionHandler = function(event) {
                if (self.enabled && event.detail && event.detail.elementId) {
                    self.inspect(event.detail.elementId);
                }
            };
            document.addEventListener('vbp:element:selected', this._selectionHandler);

            // Atajos de teclado
            this._keyboardHandler = function(event) {
                if (!self.enabled) return;

                // Alt+Click para medir distancia
                if (event.altKey && event.type === 'click') {
                    self.handleMeasureClick(event);
                }

                // Cmd/Ctrl+C en dev mode para copiar código
                if ((event.metaKey || event.ctrlKey) && event.key === 'c' && self.isPanelOpen) {
                    // Solo si no hay texto seleccionado
                    if (!window.getSelection().toString()) {
                        event.preventDefault();
                        self.copyCurrentCode();
                    }
                }
            };
            document.addEventListener('keydown', this._keyboardHandler);
            document.addEventListener('click', this._keyboardHandler);
        },

        /**
         * Remueve event listeners
         */
        removeEventListeners: function() {
            if (this._selectionHandler) {
                document.removeEventListener('vbp:element:selected', this._selectionHandler);
            }
            if (this._keyboardHandler) {
                document.removeEventListener('keydown', this._keyboardHandler);
                document.removeEventListener('click', this._keyboardHandler);
            }
        },

        /**
         * Inspecciona un elemento por ID
         * @param {string} elementId - ID del elemento VBP
         */
        inspect: function(elementId) {
            if (!elementId) return;

            this.selectedElementId = elementId;

            // Obtener elemento VBP
            var vbpStore = Alpine.store('vbp');
            var element = vbpStore ? vbpStore.getElementDeep(elementId) : null;

            if (!element) {
                vbpLog.warn('Elemento no encontrado:', elementId);
                return;
            }

            // Obtener elemento DOM
            this.selectedDomElement = document.querySelector('[data-vbp-id="' + elementId + '"]');

            // Extraer estilos
            this.extractStyles(element);

            // Extraer tokens usados
            this.extractTokens(element);

            // Extraer assets
            this.extractAssets(element);

            // Obtener medidas
            this.measureElement();

            // Regenerar código
            this.updateGeneratedCode();

            vbpLog.log('Inspeccionando elemento:', elementId);
        },

        /**
         * Extrae estilos del elemento
         * @param {object} element - Elemento VBP
         */
        extractStyles: function(element) {
            if (window.VBPStyleExtractor) {
                // Priorizar estilos del DOM si está disponible
                if (this.selectedDomElement) {
                    this.extractedStyles = window.VBPStyleExtractor.extractFromDOM(this.selectedDomElement);
                } else {
                    this.extractedStyles = window.VBPStyleExtractor.extractFromVBP(element);
                }

                this.flatStyles = window.VBPStyleExtractor.flatten(this.extractedStyles);
            }
        },

        /**
         * Extrae tokens usados
         * @param {object} element - Elemento VBP
         */
        extractTokens: function(element) {
            if (window.VBPTokenExtractor) {
                this.usedTokens = window.VBPTokenExtractor.extractUsedTokens(element, this.globalTokens);
            }
        },

        /**
         * Extrae assets del elemento
         * @param {object} element - Elemento VBP
         */
        extractAssets: function(element) {
            if (window.VBPAssetExporter) {
                this.assets = window.VBPAssetExporter.extractAssets(element);
            }
        },

        /**
         * Mide el elemento actual
         */
        measureElement: function() {
            if (this.selectedDomElement && window.VBPMeasurement) {
                this.measurements = window.VBPMeasurement.measure(this.selectedDomElement);
            }
        },

        /**
         * Actualiza el código generado
         */
        updateGeneratedCode: function() {
            this.generatedCode = this.generateCode(this.codeFormat);
        },

        /**
         * Genera código en el formato especificado
         * @param {string} format - Formato de código
         * @returns {string} Código generado
         */
        generateCode: function(format) {
            if (!window.VBPCodeGenerator || !this.flatStyles) {
                return '/* No hay estilos para generar */';
            }

            var element = this.getSelectedElement();
            var selector = element ? '.' + this.generateClassName(element) : '.element';

            switch (format) {
                case 'css':
                    return window.VBPCodeGenerator.generateCSS(this.flatStyles, selector, this.units);

                case 'scss':
                    return window.VBPCodeGenerator.generateSCSS(this.flatStyles, selector, this.usedTokens);

                case 'tailwind':
                    var tailwindClasses = window.VBPCodeGenerator.generateTailwind(this.flatStyles);
                    return '<div class="' + tailwindClasses + '">\n  <!-- Content -->\n</div>';

                case 'styled-components':
                    var componentName = element ? this.toComponentName(element.name || element.type) : 'Element';
                    return window.VBPCodeGenerator.generateStyledComponents(this.flatStyles, componentName);

                case 'css-in-js':
                    return window.VBPCodeGenerator.generateCSSinJS(this.flatStyles);

                default:
                    return window.VBPCodeGenerator.generateCSS(this.flatStyles, selector, this.units);
            }
        },

        /**
         * Genera código de componente
         * @param {string} framework - Framework (react, vue, html)
         * @returns {string} Código del componente
         */
        generateComponent: function(framework) {
            var element = this.getSelectedElement();
            if (!element || !window.VBPComponentGenerator) {
                return '/* Selecciona un elemento */';
            }

            switch (framework) {
                case 'react':
                    return window.VBPComponentGenerator.generateReact(element, this.flatStyles);

                case 'vue':
                    return window.VBPComponentGenerator.generateVue(element, this.flatStyles);

                case 'html':
                    return window.VBPComponentGenerator.generateHTML(element, this.flatStyles);

                default:
                    return window.VBPComponentGenerator.generateReact(element, this.flatStyles);
            }
        },

        /**
         * Obtiene el elemento seleccionado del store VBP
         * @returns {object|null} Elemento VBP
         */
        getSelectedElement: function() {
            var vbpStore = Alpine.store('vbp');
            return vbpStore ? vbpStore.getElementDeep(this.selectedElementId) : null;
        },

        /**
         * Copia contenido al portapapeles
         * @param {string} content - Contenido a copiar
         * @param {string} type - Tipo de contenido (para historial)
         */
        copyToClipboard: function(content, type) {
            var self = this;
            type = type || 'code';

            navigator.clipboard.writeText(content).then(function() {
                // Agregar al historial
                self.copyHistory.unshift({
                    type: type,
                    content: content.substring(0, 100) + (content.length > 100 ? '...' : ''),
                    timestamp: Date.now()
                });

                // Limitar historial a 10 items
                if (self.copyHistory.length > 10) {
                    self.copyHistory.pop();
                }

                // Mostrar notificación
                self.showToast('Copiado al portapapeles', 'success');

                vbpLog.log('Copiado:', type);
            }).catch(function(error) {
                vbpLog.error('Error al copiar:', error);
                self.showToast('Error al copiar', 'error');
            });
        },

        /**
         * Copia el código actual
         */
        copyCurrentCode: function() {
            if (this.activeTab === 'css') {
                this.copyToClipboard(this.generatedCode, 'css-' + this.codeFormat);
            } else if (this.activeTab === 'code') {
                var componentCode = this.generateComponent(this.componentFramework);
                this.copyToClipboard(componentCode, 'component-' + this.componentFramework);
            }
        },

        /**
         * Cambia el formato de código
         * @param {string} format - Nuevo formato
         */
        setCodeFormat: function(format) {
            this.codeFormat = format;
            this.updateGeneratedCode();
            localStorage.setItem('vbp_dev_code_format', format);
        },

        /**
         * Cambia las unidades
         * @param {string} units - Nuevas unidades
         */
        setUnits: function(units) {
            this.units = units;
            this.updateGeneratedCode();
            localStorage.setItem('vbp_dev_units', units);
        },

        /**
         * Cambia el base rem
         * @param {number} base - Nuevo base rem
         */
        setRemBase: function(base) {
            this.remBase = parseInt(base) || 16;
            if (window.VBPUnitConverter) {
                window.VBPUnitConverter.remBase = this.remBase;
            }
            this.updateGeneratedCode();
            localStorage.setItem('vbp_dev_rem_base', this.remBase);
        },

        /**
         * Cambia el framework de componentes
         * @param {string} framework - Nuevo framework
         */
        setComponentFramework: function(framework) {
            this.componentFramework = framework;
            localStorage.setItem('vbp_dev_component_framework', framework);
        },

        /**
         * Activa modo de medición de distancias
         */
        toggleMeasureMode: function() {
            this.measureMode = !this.measureMode;
            this.measureStartElement = null;
            this.distanceMeasurement = null;

            if (this.measureMode) {
                document.body.classList.add('vbp-measure-mode');
                this.showToast('Modo medición: Alt+Click en dos elementos', 'info');
            } else {
                document.body.classList.remove('vbp-measure-mode');
                this.clearMeasurementOverlays();
            }
        },

        /**
         * Maneja click para medición
         * @param {Event} event - Evento click
         */
        handleMeasureClick: function(event) {
            if (!this.measureMode) return;

            var target = event.target.closest('[data-vbp-id]');
            if (!target) return;

            event.preventDefault();
            event.stopPropagation();

            if (!this.measureStartElement) {
                this.measureStartElement = target;
                target.classList.add('vbp-measure-start');
                this.showToast('Ahora Alt+Click en el segundo elemento', 'info');
            } else {
                // Medir distancia
                if (window.VBPMeasurement) {
                    this.distanceMeasurement = window.VBPMeasurement.measureDistance(this.measureStartElement, target);
                    this.showDistanceOverlay(this.measureStartElement, target);
                }

                // Limpiar
                this.measureStartElement.classList.remove('vbp-measure-start');
                this.measureStartElement = null;
            }
        },

        /**
         * Muestra overlay con distancias
         * @param {HTMLElement} elementA - Primer elemento
         * @param {HTMLElement} elementB - Segundo elemento
         */
        showDistanceOverlay: function(elementA, elementB) {
            this.clearMeasurementOverlays();

            if (!this.distanceMeasurement) return;

            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            // Crear overlay
            var overlay = document.createElement('div');
            overlay.className = 'vbp-distance-overlay';

            // Obtener posiciones relativas al canvas
            var rectA = elementA.getBoundingClientRect();
            var rectB = elementB.getBoundingClientRect();
            var canvasRect = canvas.getBoundingClientRect();

            // Línea horizontal
            var horizontalLine = document.createElement('div');
            horizontalLine.className = 'vbp-distance-line vbp-distance-horizontal';
            var hStart = Math.min(rectA.right, rectB.right) - canvasRect.left;
            var hEnd = Math.max(rectA.left, rectB.left) - canvasRect.left;
            var hY = (rectA.top + rectA.height / 2 + rectB.top + rectB.height / 2) / 2 - canvasRect.top;
            horizontalLine.style.left = hStart + 'px';
            horizontalLine.style.width = Math.abs(hEnd - hStart) + 'px';
            horizontalLine.style.top = hY + 'px';

            // Label horizontal
            var hLabel = document.createElement('span');
            hLabel.className = 'vbp-distance-label';
            hLabel.textContent = this.distanceMeasurement.shortestHorizontal + 'px';
            horizontalLine.appendChild(hLabel);

            // Línea vertical
            var verticalLine = document.createElement('div');
            verticalLine.className = 'vbp-distance-line vbp-distance-vertical';
            var vX = (rectA.left + rectA.width / 2 + rectB.left + rectB.width / 2) / 2 - canvasRect.left;
            var vStart = Math.min(rectA.bottom, rectB.bottom) - canvasRect.top;
            var vEnd = Math.max(rectA.top, rectB.top) - canvasRect.top;
            verticalLine.style.left = vX + 'px';
            verticalLine.style.top = vStart + 'px';
            verticalLine.style.height = Math.abs(vEnd - vStart) + 'px';

            // Label vertical
            var vLabel = document.createElement('span');
            vLabel.className = 'vbp-distance-label';
            vLabel.textContent = this.distanceMeasurement.shortestVertical + 'px';
            verticalLine.appendChild(vLabel);

            overlay.appendChild(horizontalLine);
            overlay.appendChild(verticalLine);
            canvas.appendChild(overlay);
        },

        /**
         * Limpia overlays de medición
         */
        clearMeasurementOverlays: function() {
            var overlays = document.querySelectorAll('.vbp-distance-overlay');
            overlays.forEach(function(overlay) {
                overlay.remove();
            });
        },

        /**
         * Exporta un asset
         * @param {object} asset - Asset a exportar
         */
        exportAsset: function(asset) {
            if (window.VBPAssetExporter) {
                window.VBPAssetExporter.downloadAsset(asset);
                this.showToast('Descargando: ' + asset.name, 'success');
            }
        },

        /**
         * Exporta todos los assets
         */
        exportAllAssets: function() {
            if (this.assets.length === 0) {
                this.showToast('No hay assets para exportar', 'warning');
                return;
            }

            if (window.VBPAssetExporter) {
                // Intentar ZIP si está disponible
                if (typeof JSZip !== 'undefined') {
                    var element = this.getSelectedElement();
                    var filename = (element ? element.name || element.type : 'assets') + '-assets.zip';
                    window.VBPAssetExporter.generateZip(this.assets, filename).then(function() {
                        vbpLog.log('Assets exportados como ZIP');
                    }).catch(function() {
                        // Fallback a descarga individual
                        window.VBPAssetExporter.downloadAllAssets(this.assets);
                    }.bind(this));
                } else {
                    window.VBPAssetExporter.downloadAllAssets(this.assets);
                }

                this.showToast('Exportando ' + this.assets.length + ' assets', 'success');
            }
        },

        /**
         * Compara estilos con código implementado
         * @param {string} codeStylesJson - JSON con estilos del código
         */
        compareWithCode: function(codeStylesJson) {
            try {
                this.codeStyles = JSON.parse(codeStylesJson);

                if (window.VBPStyleComparator) {
                    this.comparisonResult = window.VBPStyleComparator.compare(this.flatStyles, this.codeStyles);
                }
            } catch (error) {
                vbpLog.error('Error al parsear estilos:', error);
                this.showToast('JSON inválido', 'error');
            }
        },

        /**
         * Genera CSS de tokens usados
         * @returns {string} CSS con variables
         */
        generateTokensCSS: function() {
            if (window.VBPTokenExtractor) {
                return window.VBPTokenExtractor.generateTokensCSS(this.usedTokens);
            }
            return '';
        },

        /**
         * Obtiene icono de tipo de asset
         * @param {string} type - Tipo de asset
         * @returns {string} Icono
         */
        getAssetIcon: function(type) {
            var icons = {
                image: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
                svg: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
                video: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>',
                audio: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
                unknown: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
            };

            return icons[type] || icons.unknown;
        },

        /**
         * Genera nombre de clase CSS
         * @param {object} element - Elemento
         * @returns {string} Nombre de clase
         */
        generateClassName: function(element) {
            if (!element) return 'element';

            var name = element.name || element.type || 'element';
            return name
                .toLowerCase()
                .replace(/[\s_]+/g, '-')
                .replace(/[^a-z0-9-]/g, '');
        },

        /**
         * Convierte string a PascalCase para nombres de componente
         * @param {string} str - String a convertir
         * @returns {string} Nombre en PascalCase
         */
        toComponentName: function(str) {
            return str
                .split(/[\s-_]+/)
                .map(function(word) {
                    return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
                })
                .join('');
        },

        /**
         * Muestra notificación toast
         * @param {string} message - Mensaje
         * @param {string} type - Tipo (success, error, warning, info)
         */
        showToast: function(message, type) {
            type = type || 'info';

            // Usar sistema de toast de VBP si está disponible
            if (window.VBPToast) {
                window.VBPToast.show(message, type);
                return;
            }

            // Fallback simple
            var toast = document.createElement('div');
            toast.className = 'vbp-dev-toast vbp-dev-toast-' + type;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(function() {
                toast.classList.add('vbp-dev-toast-visible');
            }, 10);

            setTimeout(function() {
                toast.classList.remove('vbp-dev-toast-visible');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 2000);
        },

        /**
         * Restaura preferencias guardadas
         */
        restorePreferences: function() {
            var savedFormat = localStorage.getItem('vbp_dev_code_format');
            if (savedFormat) this.codeFormat = savedFormat;

            var savedUnits = localStorage.getItem('vbp_dev_units');
            if (savedUnits) this.units = savedUnits;

            var savedRemBase = localStorage.getItem('vbp_dev_rem_base');
            if (savedRemBase) this.remBase = parseInt(savedRemBase) || 16;

            var savedFramework = localStorage.getItem('vbp_dev_component_framework');
            if (savedFramework) this.componentFramework = savedFramework;

            // Restaurar estado de dev mode
            var savedEnabled = localStorage.getItem('vbp_dev_mode_enabled');
            if (savedEnabled === '1') {
                this.enabled = true;
                this.isPanelOpen = true;
                this.initDevMode();
                document.body.classList.add('vbp-dev-mode-active');
            }
        },

        /**
         * Verifica si hay tokens de color usados
         * @returns {boolean}
         */
        hasColorTokens: function() {
            return this.usedTokens && this.usedTokens.colors && Object.keys(this.usedTokens.colors).length > 0;
        },

        /**
         * Verifica si hay tokens de spacing usados
         * @returns {boolean}
         */
        hasSpacingTokens: function() {
            return this.usedTokens && this.usedTokens.spacing && Object.keys(this.usedTokens.spacing).length > 0;
        },

        /**
         * Verifica si hay tokens de tipografía usados
         * @returns {boolean}
         */
        hasTypographyTokens: function() {
            return this.usedTokens && this.usedTokens.typography && Object.keys(this.usedTokens.typography).length > 0;
        },

        /**
         * Cuenta total de tokens usados
         * @returns {number}
         */
        totalTokensCount: function() {
            if (!this.usedTokens) return 0;

            var count = 0;
            Object.keys(this.usedTokens).forEach(function(category) {
                count += Object.keys(this.usedTokens[category] || {}).length;
            }.bind(this));
            return count;
        }
    });

    // Restaurar preferencias al iniciar
    var devModeStore = Alpine.store('vbpDevMode');
    if (devModeStore) {
        devModeStore.restorePreferences();
    }
});

/**
 * Componente Dev Panel
 */
function vbpDevPanel() {
    return {
        // Estado del panel
        isCollapsed: false,
        panelWidth: 380,
        isResizing: false,
        compareCodeInput: '',

        /**
         * Inicialización
         */
        init: function() {
            var self = this;

            // Restaurar ancho guardado
            var savedWidth = localStorage.getItem('vbp_dev_panel_width');
            if (savedWidth) {
                this.panelWidth = parseInt(savedWidth) || 380;
            }

            // Atajos de teclado globales
            document.addEventListener('keydown', function(event) {
                // Cmd/Ctrl+Shift+D para toggle dev mode
                if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key === 'd') {
                    event.preventDefault();
                    var devModeStore = Alpine.store('vbpDevMode');
                    if (devModeStore) {
                        devModeStore.toggle();
                    }
                }
            });
        },

        /**
         * Obtiene store de dev mode
         * @returns {object} Store
         */
        get store() {
            return Alpine.store('vbpDevMode');
        },

        /**
         * Colapsa/expande panel
         */
        toggleCollapse: function() {
            this.isCollapsed = !this.isCollapsed;
        },

        /**
         * Inicia redimensionado del panel
         * @param {Event} event - Evento mousedown
         */
        startResize: function(event) {
            this.isResizing = true;
            document.body.classList.add('vbp-resizing');

            var self = this;
            var startX = event.clientX;
            var startWidth = this.panelWidth;

            var onMouseMove = function(moveEvent) {
                var delta = startX - moveEvent.clientX;
                self.panelWidth = Math.max(300, Math.min(600, startWidth + delta));
            };

            var onMouseUp = function() {
                self.isResizing = false;
                document.body.classList.remove('vbp-resizing');
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                // Guardar ancho
                localStorage.setItem('vbp_dev_panel_width', self.panelWidth);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        },

        /**
         * Obtiene estilos de categoría formateados
         * @param {string} category - Categoría
         * @returns {array} Lista de propiedades
         */
        getCategoryStyles: function(category) {
            var store = this.store;
            if (!store || !store.extractedStyles || !store.extractedStyles[category]) {
                return [];
            }

            var styles = store.extractedStyles[category];
            var result = [];

            Object.keys(styles).forEach(function(prop) {
                var value = styles[prop];
                if (value && value !== '' && value !== 'none' && value !== 'normal' && value !== 'auto') {
                    result.push({
                        property: prop,
                        value: value
                    });
                }
            });

            return result;
        },

        /**
         * Obtiene nombre legible de categoría
         * @param {string} category - Categoría
         * @returns {string} Nombre
         */
        getCategoryName: function(category) {
            var names = {
                layout: 'Layout',
                typography: 'Typography',
                background: 'Background',
                border: 'Border',
                effects: 'Effects',
                position: 'Position'
            };
            return names[category] || category;
        },

        /**
         * Obtiene icono de categoría
         * @param {string} category - Categoría
         * @returns {string} SVG del icono
         */
        getCategoryIcon: function(category) {
            var icons = {
                layout: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
                typography: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
                background: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
                border: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" stroke-dasharray="4 2"/></svg>',
                effects: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10"/></svg>',
                position: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><line x1="12" y1="1" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="1" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="23" y2="12"/></svg>'
            };
            return icons[category] || '';
        },

        /**
         * Formatea nombre de propiedad CSS
         * @param {string} prop - Propiedad en camelCase
         * @returns {string} Propiedad formateada
         */
        formatPropertyName: function(prop) {
            return prop.replace(/([A-Z])/g, '-$1').toLowerCase();
        },

        /**
         * Copia propiedad individual
         * @param {string} property - Nombre de la propiedad
         * @param {string} value - Valor
         */
        copyProperty: function(property, value) {
            var cssProperty = this.formatPropertyName(property);
            var cssLine = cssProperty + ': ' + value + ';';
            this.store.copyToClipboard(cssLine, 'property');
        },

        /**
         * Aplica comparación de estilos
         */
        applyComparison: function() {
            if (this.compareCodeInput) {
                this.store.compareWithCode(this.compareCodeInput);
            }
        },

        /**
         * Obtiene clase de resultado de comparación
         * @param {object} result - Resultado de comparación
         * @returns {string} Clase CSS
         */
        getComparisonClass: function(result) {
            if (!result) return '';

            if (result.matchPercentage >= 90) return 'vbp-compare-good';
            if (result.matchPercentage >= 70) return 'vbp-compare-warning';
            return 'vbp-compare-bad';
        }
    };
}

// Registrar componente globalmente
window.vbpDevPanel = vbpDevPanel;

vbpLog.log('VBP Dev Panel loaded');
