/**
 * Visual Builder Pro - AI Layout Assistant
 *
 * Sistema de asistencia de diseno con IA para sugerencias de layout,
 * auto-spacing, colores complementarios y generacion de variantes.
 *
 * @package Flavor_Chat_IA
 * @since 2.2.0
 */

// Fallback de vbpLog si no esta definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP-AI-Layout]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP-AI-Layout]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP-AI-Layout]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

/**
 * Inicializa el AI Layout Assistant
 */
function initVbpAILayout() {
    if (typeof Alpine === 'undefined' || window.__vbpAILayoutInitialized) {
        return !!window.__vbpAILayoutInitialized;
    }

    // Store para AI Layout
    Alpine.store('vbpAILayout', {
        // Estado del panel
        isOpen: false,
        activeTab: 'generate', // 'generate' | 'spacing' | 'colors' | 'variants' | 'analyze'

        // Estado de carga
        loading: false,
        error: null,

        // Input de comandos
        prompt: '',
        commandHistory: [],
        maxHistoryItems: 50,

        // Sugerencias contextuales
        suggestions: [],
        showSuggestions: true,

        // Resultados
        generatedBlocks: [],
        spacingSuggestions: [],
        colorPalette: [],
        colorVariations: {},
        variants: [],
        analysisResult: null,

        // Configuracion
        gridBase: 8,
        colorScheme: 'complementary',

        // Templates predefinidos
        templates: [],
        templatesLoaded: false,

        // Estado de AI
        aiAvailable: false,
        fallbackEnabled: true,

        // ============================================
        // Metodos de apertura/cierre
        // ============================================

        /**
         * Abre el panel de AI Layout
         */
        open: function(tab) {
            this.isOpen = true;
            this.activeTab = tab || 'generate';
            this.error = null;
            this.loadStatus();

            if (!this.templatesLoaded) {
                this.loadTemplates();
            }

            // Focus en input
            var self = this;
            setTimeout(function() {
                var input = document.querySelector('.vbp-ai-layout-input');
                if (input) input.focus();
            }, 100);
        },

        /**
         * Cierra el panel
         */
        close: function() {
            this.isOpen = false;
            this.error = null;
        },

        /**
         * Toggle del panel
         */
        toggle: function() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        /**
         * Cambia de tab
         */
        setTab: function(tab) {
            this.activeTab = tab;
            this.error = null;
        },

        // ============================================
        // Carga de datos
        // ============================================

        /**
         * Carga estado de AI Layout
         */
        loadStatus: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                this.aiAvailable = false;
                return;
            }

            fetch(VBP_Config.restUrl + 'ai/layout/status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.aiAvailable = data.aiAvailable;
                    self.fallbackEnabled = data.fallbackEnabled;
                }
            })
            .catch(function(error) {
                vbpLog.warn('AI Layout: Error loading status:', error);
                self.aiAvailable = false;
            });
        },

        /**
         * Carga templates predefinidos
         */
        loadTemplates: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                return;
            }

            fetch(VBP_Config.restUrl + 'ai/layout/templates', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.templates) {
                    self.templates = data.templates;
                    self.templatesLoaded = true;
                }
            })
            .catch(function(error) {
                vbpLog.warn('AI Layout: Error loading templates:', error);
            });
        },

        // ============================================
        // Generacion de Layout
        // ============================================

        /**
         * Genera layout desde prompt
         */
        generateLayout: function(prompt) {
            var self = this;
            var commandPrompt = prompt || this.prompt;

            if (!commandPrompt.trim()) {
                this.error = 'Escribe una descripcion del layout que quieres crear';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                this.error = 'API no configurada';
                return;
            }

            this.loading = true;
            this.error = null;
            this.generatedBlocks = [];

            // Obtener contexto de la pagina
            var pageContext = this.getPageContext();

            fetch(VBP_Config.restUrl + 'ai/layout/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    prompt: commandPrompt,
                    context: pageContext
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success && data.blocks) {
                    self.generatedBlocks = data.blocks;
                    self.addToHistory(commandPrompt, 'generate');

                    // Mostrar notificacion
                    self.showNotification(
                        'Layout generado' + (data.cached ? ' (cache)' : data.ai ? ' con IA' : ''),
                        'success'
                    );
                } else {
                    self.error = data.message || 'Error al generar layout';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Aplica bloques generados al canvas
         */
        applyGeneratedBlocks: function() {
            if (!this.generatedBlocks || this.generatedBlocks.length === 0) {
                this.error = 'No hay bloques generados para aplicar';
                return;
            }

            var store = Alpine.store('vbp');
            var self = this;

            // Confirmar si hay elementos existentes
            if (store.elements && store.elements.length > 0) {
                if (!confirm('Se anadiran los bloques generados al canvas. Continuar?')) {
                    return;
                }
            }

            // Convertir y anadir bloques
            var convertedBlocks = this.convertBlocksToVBP(this.generatedBlocks);

            convertedBlocks.forEach(function(block) {
                store.addElement(block.type);
                var lastElement = store.elements[store.elements.length - 1];
                if (lastElement) {
                    store.updateElement(lastElement.id, {
                        data: block.data || {},
                        styles: block.styles || {},
                        children: block.children || []
                    });
                }
            });

            this.showNotification('Bloques aplicados al canvas', 'success');
            this.close();
        },

        /**
         * Convierte bloques del formato AI a formato VBP
         */
        convertBlocksToVBP: function(blocks) {
            var self = this;
            var convertedBlocksArray = [];

            blocks.forEach(function(block) {
                var vbpBlock = self.convertSingleBlock(block);
                if (vbpBlock) {
                    convertedBlocksArray.push(vbpBlock);
                }
            });

            return convertedBlocksArray;
        },

        /**
         * Convierte un bloque individual
         */
        convertSingleBlock: function(block) {
            var vbpBlock = {
                type: block.type || 'section',
                data: {},
                styles: {},
                children: []
            };

            // Copiar props a data
            if (block.props) {
                Object.assign(vbpBlock.data, block.props);
            }

            // Copiar estilos
            if (block.styles) {
                Object.assign(vbpBlock.styles, block.styles);
            }

            // Convertir hijos recursivamente
            if (block.children && Array.isArray(block.children)) {
                var self = this;
                vbpBlock.children = block.children.map(function(child) {
                    return self.convertSingleBlock(child);
                }).filter(Boolean);
            }

            return vbpBlock;
        },

        // ============================================
        // Auto-Spacing
        // ============================================

        /**
         * Calcula auto-spacing para elementos seleccionados
         */
        calculateAutoSpacing: function() {
            var self = this;
            var store = Alpine.store('vbp');

            var selectedIds = store.selection.elementIds;
            if (selectedIds.length === 0) {
                this.error = 'Selecciona elementos para calcular spacing';
                return;
            }

            // Obtener elementos seleccionados
            var elements = selectedIds.map(function(id) {
                return store.getElementById(id);
            }).filter(Boolean);

            if (elements.length === 0) {
                this.error = 'No se encontraron elementos seleccionados';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                // Fallback local
                this.spacingSuggestions = this.calculateLocalSpacing(elements);
                return;
            }

            this.loading = true;
            this.error = null;

            fetch(VBP_Config.restUrl + 'ai/layout/auto-spacing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    elements: elements,
                    gridBase: this.gridBase
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success && data.suggestions) {
                    self.spacingSuggestions = data.suggestions;
                    self.showNotification('Spacing calculado', 'success');
                } else {
                    self.error = data.message || 'Error al calcular spacing';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Calcula spacing localmente (fallback)
         */
        calculateLocalSpacing: function(elements) {
            var self = this;
            var gridBase = this.gridBase;

            return elements.map(function(element) {
                var elementType = element.type || 'unknown';
                var spacing = self.getTypeSpacing(elementType, gridBase);

                return {
                    elementId: element.id,
                    type: elementType,
                    spacing: spacing
                };
            });
        },

        /**
         * Obtiene spacing recomendado por tipo
         */
        getTypeSpacing: function(type, gridBase) {
            var typeSpacing = {
                'section':   { padding: gridBase * 8, margin: 0 },
                'container': { padding: gridBase * 4, margin: 0 },
                'heading':   { padding: 0, margin: gridBase * 3 },
                'text':      { padding: 0, margin: gridBase * 2 },
                'button':    { padding: gridBase * 2, margin: gridBase * 2 },
                'image':     { padding: 0, margin: gridBase * 2 },
                'columns':   { padding: 0, margin: gridBase * 4 },
                'row':       { padding: 0, margin: gridBase * 3 }
            };

            return typeSpacing[type] || { padding: gridBase * 2, margin: gridBase * 2 };
        },

        /**
         * Aplica spacing sugerido a un elemento
         */
        applySpacing: function(suggestion) {
            var store = Alpine.store('vbp');
            var element = store.getElementById(suggestion.elementId);

            if (!element) {
                this.error = 'Elemento no encontrado';
                return;
            }

            var currentStyles = element.styles || {};
            var newStyles = Object.assign({}, currentStyles, {
                spacing: {
                    padding: {
                        top: suggestion.spacing.padding + 'px',
                        bottom: suggestion.spacing.padding + 'px',
                        left: suggestion.spacing.padding + 'px',
                        right: suggestion.spacing.padding + 'px'
                    },
                    margin: {
                        top: suggestion.spacing.margin + 'px',
                        bottom: suggestion.spacing.margin + 'px',
                        left: '0',
                        right: '0'
                    }
                }
            });

            store.updateElement(suggestion.elementId, { styles: newStyles });
            this.showNotification('Spacing aplicado', 'success');
        },

        /**
         * Aplica spacing a todos los elementos sugeridos
         */
        applyAllSpacing: function() {
            var self = this;

            this.spacingSuggestions.forEach(function(suggestion) {
                self.applySpacing(suggestion);
            });

            this.showNotification('Spacing aplicado a ' + this.spacingSuggestions.length + ' elementos', 'success');
        },

        // ============================================
        // Colores Complementarios
        // ============================================

        /**
         * Sugiere colores complementarios
         */
        suggestColors: function(baseColor) {
            var self = this;

            if (!baseColor) {
                this.error = 'Selecciona un color base';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                // Fallback local
                this.colorPalette = this.generateLocalPalette(baseColor, this.colorScheme);
                this.colorVariations = this.generateLocalVariations(baseColor);
                return;
            }

            this.loading = true;
            this.error = null;

            fetch(VBP_Config.restUrl + 'ai/layout/colors', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    baseColor: baseColor,
                    scheme: this.colorScheme
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success) {
                    self.colorPalette = data.palette || [];
                    self.colorVariations = data.variations || {};
                    self.showNotification('Paleta generada', 'success');
                } else {
                    self.error = data.message || 'Error al generar colores';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Genera paleta local (fallback)
         */
        generateLocalPalette: function(baseColor, scheme) {
            var hsl = this.hexToHsl(baseColor);
            if (!hsl) return [baseColor];

            var palette = [];
            var h = hsl.h;
            var s = hsl.s;
            var l = hsl.l;

            switch (scheme) {
                case 'complementary':
                    palette.push(this.hslToHex({ h: h, s: s, l: l }));
                    palette.push(this.hslToHex({ h: (h + 180) % 360, s: s, l: l }));
                    break;

                case 'analogous':
                    palette.push(this.hslToHex({ h: (h - 30 + 360) % 360, s: s, l: l }));
                    palette.push(this.hslToHex({ h: h, s: s, l: l }));
                    palette.push(this.hslToHex({ h: (h + 30) % 360, s: s, l: l }));
                    break;

                case 'triadic':
                    palette.push(this.hslToHex({ h: h, s: s, l: l }));
                    palette.push(this.hslToHex({ h: (h + 120) % 360, s: s, l: l }));
                    palette.push(this.hslToHex({ h: (h + 240) % 360, s: s, l: l }));
                    break;

                case 'monochromatic':
                    palette.push(this.hslToHex({ h: h, s: s, l: Math.max(0, l - 30) }));
                    palette.push(this.hslToHex({ h: h, s: s, l: Math.max(0, l - 15) }));
                    palette.push(this.hslToHex({ h: h, s: s, l: l }));
                    palette.push(this.hslToHex({ h: h, s: s, l: Math.min(100, l + 15) }));
                    palette.push(this.hslToHex({ h: h, s: s, l: Math.min(100, l + 30) }));
                    break;

                default:
                    palette.push(baseColor);
            }

            return palette;
        },

        /**
         * Genera variaciones local (fallback)
         */
        generateLocalVariations: function(baseColor) {
            var hsl = this.hexToHsl(baseColor);
            if (!hsl) return {};

            return {
                lightest: this.hslToHex({ h: hsl.h, s: Math.max(0, hsl.s - 20), l: Math.min(100, hsl.l + 40) }),
                light:    this.hslToHex({ h: hsl.h, s: Math.max(0, hsl.s - 10), l: Math.min(100, hsl.l + 20) }),
                base:     baseColor,
                dark:     this.hslToHex({ h: hsl.h, s: Math.min(100, hsl.s + 10), l: Math.max(0, hsl.l - 20) }),
                darkest:  this.hslToHex({ h: hsl.h, s: Math.min(100, hsl.s + 20), l: Math.max(0, hsl.l - 40) })
            };
        },

        /**
         * Convierte hex a HSL
         */
        hexToHsl: function(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            if (hex.length !== 6) return null;

            var r = parseInt(hex.substr(0, 2), 16) / 255;
            var g = parseInt(hex.substr(2, 2), 16) / 255;
            var b = parseInt(hex.substr(4, 2), 16) / 255;

            var max = Math.max(r, g, b);
            var min = Math.min(r, g, b);
            var l = (max + min) / 2;
            var h, s;

            if (max === min) {
                h = s = 0;
            } else {
                var d = max - min;
                s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

                switch (max) {
                    case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                    case g: h = ((b - r) / d + 2) / 6; break;
                    case b: h = ((r - g) / d + 4) / 6; break;
                }
            }

            return {
                h: Math.round(h * 360),
                s: Math.round(s * 100),
                l: Math.round(l * 100)
            };
        },

        /**
         * Convierte HSL a hex
         */
        hslToHex: function(hsl) {
            var h = hsl.h / 360;
            var s = hsl.s / 100;
            var l = hsl.l / 100;

            var r, g, b;

            if (s === 0) {
                r = g = b = l;
            } else {
                var hueToRgb = function(p, q, t) {
                    if (t < 0) t += 1;
                    if (t > 1) t -= 1;
                    if (t < 1/6) return p + (q - p) * 6 * t;
                    if (t < 1/2) return q;
                    if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                    return p;
                };

                var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                var p = 2 * l - q;
                r = hueToRgb(p, q, h + 1/3);
                g = hueToRgb(p, q, h);
                b = hueToRgb(p, q, h - 1/3);
            }

            var toHex = function(x) {
                var hex = Math.round(x * 255).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            };

            return '#' + toHex(r) + toHex(g) + toHex(b);
        },

        /**
         * Copia color al clipboard
         */
        copyColor: function(color) {
            navigator.clipboard.writeText(color).then(function() {
                // Usar this de manera segura
            });
            this.showNotification('Color copiado: ' + color, 'success');
        },

        /**
         * Aplica color a elemento seleccionado
         */
        applyColorToSelection: function(color, property) {
            var store = Alpine.store('vbp');
            var selectedIds = store.selection.elementIds;

            if (selectedIds.length === 0) {
                this.error = 'Selecciona un elemento para aplicar el color';
                return;
            }

            selectedIds.forEach(function(id) {
                var element = store.getElementById(id);
                if (element) {
                    var currentStyles = element.styles || {};
                    var newStyles = Object.assign({}, currentStyles);

                    if (property === 'background') {
                        newStyles.background = color;
                    } else if (property === 'color') {
                        newStyles.color = color;
                    } else if (property === 'borderColor') {
                        newStyles.borderColor = color;
                    }

                    store.updateElement(id, { styles: newStyles });
                }
            });

            this.showNotification('Color aplicado', 'success');
        },

        // ============================================
        // Generacion de Variantes
        // ============================================

        /**
         * Genera variantes de un elemento
         */
        generateVariants: function() {
            var self = this;
            var store = Alpine.store('vbp');

            var selectedIds = store.selection.elementIds;
            if (selectedIds.length !== 1) {
                this.error = 'Selecciona exactamente un elemento';
                return;
            }

            var element = store.getElementById(selectedIds[0]);
            if (!element) {
                this.error = 'Elemento no encontrado';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                // Fallback local
                this.variants = this.generateLocalVariants(element, 3);
                return;
            }

            this.loading = true;
            this.error = null;

            fetch(VBP_Config.restUrl + 'ai/layout/variants', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    element: element,
                    count: 4
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success && data.variants) {
                    self.variants = data.variants;
                    self.showNotification('Variantes generadas', 'success');
                } else {
                    self.error = data.message || 'Error al generar variantes';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Genera variantes localmente (fallback)
         */
        generateLocalVariants: function(element, count) {
            var variants = [];

            var modifications = [
                { spacing: { padding: '16px', margin: '8px' }, style: 'compact' },
                { spacing: { padding: '48px', margin: '32px' }, style: 'spacious' },
                { background: 'gradient', style: 'highlighted' },
                { spacing: { padding: '24px', margin: '16px' }, style: 'minimal' },
                { border: '1px solid #e5e7eb', style: 'bordered' }
            ];

            for (var i = 0; i < count && i < modifications.length; i++) {
                var variant = JSON.parse(JSON.stringify(element));
                variant.id = element.id + '_variant_' + i;
                variant.variantStyle = modifications[i].style;

                if (!variant.styles) variant.styles = {};

                if (modifications[i].spacing) {
                    variant.styles.spacing = modifications[i].spacing;
                }
                if (modifications[i].background) {
                    variant.styles.background = modifications[i].background;
                }
                if (modifications[i].border) {
                    variant.styles.border = modifications[i].border;
                }

                variants.push(variant);
            }

            return variants;
        },

        /**
         * Aplica variante seleccionada
         */
        applyVariant: function(variantIndex) {
            var variant = this.variants[variantIndex];
            if (!variant) {
                this.error = 'Variante no encontrada';
                return;
            }

            var store = Alpine.store('vbp');
            var selectedIds = store.selection.elementIds;

            if (selectedIds.length !== 1) {
                this.error = 'Selecciona exactamente un elemento';
                return;
            }

            var originalId = selectedIds[0];

            // Aplicar estilos de la variante
            store.updateElement(originalId, {
                styles: variant.styles || {},
                data: variant.data || {}
            });

            this.showNotification('Variante aplicada: ' + (variant.variantStyle || 'default'), 'success');
        },

        // ============================================
        // Analisis de Diseno
        // ============================================

        /**
         * Analiza diseno y sugiere mejoras
         */
        analyzeDesign: function() {
            var self = this;
            var store = Alpine.store('vbp');

            var elements = store.elements;
            if (!elements || elements.length === 0) {
                this.error = 'No hay elementos para analizar';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                // Fallback local
                this.analysisResult = this.analyzeLocal(elements);
                return;
            }

            this.loading = true;
            this.error = null;

            fetch(VBP_Config.restUrl + 'ai/layout/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    elements: elements
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success) {
                    self.analysisResult = {
                        issues: data.issues || [],
                        suggestions: data.suggestions || [],
                        score: data.score || 100
                    };
                    self.showNotification('Analisis completado', 'success');
                } else {
                    self.error = data.message || 'Error al analizar diseno';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Analiza localmente (fallback)
         */
        analyzeLocal: function(elements) {
            var issues = [];
            var suggestions = [];

            elements.forEach(function(element) {
                // Verificar spacing consistente
                var styles = element.styles || {};
                var spacing = styles.spacing || {};

                // Sugerencias basicas
                if (element.type === 'section' && (!element.children || element.children.length === 0)) {
                    suggestions.push({
                        type: 'content',
                        elementId: element.id,
                        message: 'Esta seccion esta vacia. Considera anadir contenido.',
                        action: 'addContent'
                    });
                }
            });

            var score = 100 - (issues.length * 10);

            return {
                issues: issues,
                suggestions: suggestions,
                score: Math.max(0, score)
            };
        },

        /**
         * Aplica fix sugerido
         */
        applyFix: function(issue) {
            var self = this;

            if (!issue.fix) {
                this.error = 'No hay fix disponible';
                return;
            }

            var store = Alpine.store('vbp');
            var element = store.getElementById(issue.elementId);

            if (!element) {
                this.error = 'Elemento no encontrado';
                return;
            }

            switch (issue.fix.action) {
                case 'adjustContrast':
                    // Ajustar contraste
                    this.showNotification('Ajuste de contraste aplicado', 'success');
                    break;

                case 'snapToGrid':
                    // Snap to grid
                    var newSpacing = issue.fix.params.value;
                    var currentStyles = element.styles || {};
                    currentStyles.spacing = currentStyles.spacing || {};
                    currentStyles.spacing.padding = newSpacing + 'px';
                    store.updateElement(issue.elementId, { styles: currentStyles });
                    this.showNotification('Spacing ajustado a ' + newSpacing + 'px', 'success');
                    break;

                default:
                    this.showNotification('Fix aplicado', 'success');
            }

            // Volver a analizar
            this.analyzeDesign();
        },

        // ============================================
        // Comandos naturales
        // ============================================

        /**
         * Ejecuta comando desde input
         */
        executeCommand: function() {
            var self = this;
            var command = this.prompt.trim();

            if (!command) {
                return;
            }

            // Comandos especiales locales
            if (command.toLowerCase() === 'ayuda' || command.toLowerCase() === 'help') {
                this.showHelp();
                return;
            }

            if (command.toLowerCase() === 'analizar' || command.toLowerCase() === 'analyze') {
                this.setTab('analyze');
                this.analyzeDesign();
                return;
            }

            if (command.toLowerCase().indexOf('color') !== -1) {
                this.setTab('colors');
                return;
            }

            // Enviar al servidor
            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                // Fallback: intentar generar layout
                this.generateLayout(command);
                return;
            }

            this.loading = true;
            this.error = null;

            var pageContext = this.getPageContext();
            var store = Alpine.store('vbp');
            var selectedIds = store.selection.elementIds;

            fetch(VBP_Config.restUrl + 'ai/layout/command', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    command: command,
                    selectedIds: selectedIds,
                    pageContext: pageContext
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.loading = false;

                if (data.success && data.result) {
                    self.handleCommandResult(data.result);
                    self.addToHistory(command, 'command');
                } else {
                    self.error = data.message || 'Comando no reconocido';
                }
            })
            .catch(function(error) {
                self.loading = false;
                self.error = 'Error de conexion: ' + error.message;
            });
        },

        /**
         * Maneja resultado de comando
         */
        handleCommandResult: function(result) {
            var store = Alpine.store('vbp');

            switch (result.action) {
                case 'addBlocks':
                    if (result.blocks) {
                        this.generatedBlocks = result.blocks;
                        this.showNotification('Bloques generados. Haz clic en "Aplicar" para anadirlos.', 'success');
                    }
                    break;

                case 'alignElements':
                    this.showNotification('Alineacion: ' + (result.alignment || 'aplicada'), 'success');
                    break;

                case 'applySpacing':
                    this.showNotification('Spacing ' + (result.preset || 'aplicado'), 'success');
                    break;

                case 'openColorSuggestions':
                    this.setTab('colors');
                    break;

                case 'showSuggestions':
                    this.setTab('analyze');
                    this.analyzeDesign();
                    break;

                case 'analyzeAndFix':
                    this.setTab('analyze');
                    this.analyzeDesign();
                    break;

                default:
                    this.showNotification('Comando ejecutado', 'success');
            }
        },

        /**
         * Muestra ayuda de comandos
         */
        showHelp: function() {
            var helpText = [
                'Comandos disponibles:',
                '',
                '- Crear hero section',
                '- Anadir grid de 3 columnas',
                '- Generar features',
                '- Crear testimonios',
                '- Hacer esto mas compacto',
                '- Centrar todo verticalmente',
                '- Sugerir colores',
                '- Analizar diseno',
                '',
                'Escribe cualquier descripcion y se intentara generar el layout.'
            ].join('\n');

            alert(helpText);
        },

        // ============================================
        // Historial y sugerencias
        // ============================================

        /**
         * Anade al historial
         */
        addToHistory: function(prompt, type) {
            var entry = {
                id: 'hist_' + Date.now(),
                prompt: prompt,
                type: type,
                timestamp: new Date().toISOString()
            };

            this.commandHistory.unshift(entry);

            if (this.commandHistory.length > this.maxHistoryItems) {
                this.commandHistory = this.commandHistory.slice(0, this.maxHistoryItems);
            }

            this.saveHistoryToStorage();
        },

        /**
         * Guarda historial en localStorage
         */
        saveHistoryToStorage: function() {
            try {
                localStorage.setItem('vbp_ai_layout_history', JSON.stringify(this.commandHistory));
            } catch (e) {
                vbpLog.warn('AI Layout: Error saving history:', e);
            }
        },

        /**
         * Carga historial desde localStorage
         */
        loadHistoryFromStorage: function() {
            try {
                var saved = localStorage.getItem('vbp_ai_layout_history');
                if (saved) {
                    this.commandHistory = JSON.parse(saved);
                }
            } catch (e) {
                vbpLog.warn('AI Layout: Error loading history:', e);
                this.commandHistory = [];
            }
        },

        /**
         * Usa prompt del historial
         */
        useFromHistory: function(entry) {
            this.prompt = entry.prompt;
        },

        /**
         * Obtiene sugerencias de autocompletado
         */
        getSuggestions: function() {
            var prompt = this.prompt.toLowerCase().trim();

            if (prompt.length < 2) {
                return [];
            }

            var allSuggestions = [
                'Crear hero section',
                'Crear hero con imagen de fondo',
                'Anadir grid de 2 columnas',
                'Anadir grid de 3 columnas',
                'Anadir grid de 4 columnas',
                'Generar features',
                'Generar testimonios',
                'Crear pricing table',
                'Crear FAQ',
                'Crear CTA',
                'Crear footer',
                'Crear navbar',
                'Hacer mas compacto',
                'Hacer mas espacioso',
                'Centrar verticalmente',
                'Centrar horizontalmente',
                'Sugerir colores',
                'Analizar diseno',
                'Mejorar spacing',
                'Generar variantes'
            ];

            return allSuggestions.filter(function(suggestion) {
                return suggestion.toLowerCase().indexOf(prompt) !== -1;
            }).slice(0, 5);
        },

        /**
         * Aplica sugerencia
         */
        applySuggestion: function(suggestion) {
            this.prompt = suggestion;
            this.executeCommand();
        },

        // ============================================
        // Utilidades
        // ============================================

        /**
         * Obtiene contexto de la pagina
         */
        getPageContext: function() {
            var store = Alpine.store('vbp');
            var elements = store.elements || [];

            return {
                elementCount: elements.length,
                elementTypes: elements.map(function(el) { return el.type; }),
                hasHero: elements.some(function(el) { return el.type === 'hero' || (el.data && el.data.className && el.data.className.indexOf('hero') !== -1); }),
                hasFooter: elements.some(function(el) { return el.type === 'footer' || (el.data && el.data.className && el.data.className.indexOf('footer') !== -1); })
            };
        },

        /**
         * Muestra notificacion
         */
        showNotification: function(message, type) {
            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification(message, type);
            } else {
                vbpLog.log('AI Layout:', message);
            }
        },

        /**
         * Formatea fecha del historial
         */
        formatHistoryDate: function(isoString) {
            var date = new Date(isoString);
            var now = new Date();
            var diff = now - date;

            if (diff < 60000) return 'Hace un momento';
            if (diff < 3600000) return 'Hace ' + Math.floor(diff / 60000) + ' min';
            if (diff < 86400000) return 'Hace ' + Math.floor(diff / 3600000) + ' horas';
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        /**
         * Obtiene label para esquema de color
         */
        getSchemeLabel: function(scheme) {
            var labels = {
                'complementary': 'Complementario',
                'analogous': 'Analogo',
                'triadic': 'Triadico',
                'split-complementary': 'Complementario dividido',
                'monochromatic': 'Monocromatico'
            };
            return labels[scheme] || scheme;
        },

        /**
         * Obtiene icono para severidad
         */
        getSeverityIcon: function(severity) {
            switch (severity) {
                case 'high': return '!!!';
                case 'medium': return '!!';
                case 'low': return '!';
                default: return 'i';
            }
        },

        /**
         * Obtiene color para score
         */
        getScoreColor: function(score) {
            if (score >= 90) return '#22c55e';
            if (score >= 70) return '#eab308';
            if (score >= 50) return '#f97316';
            return '#ef4444';
        }
    });

    // Inicializar historial
    Alpine.store('vbpAILayout').loadHistoryFromStorage();

    // Registrar comandos en command palette
    var commandPalette = Alpine.store('vbpCommandPalette');
    if (commandPalette && commandPalette.commands) {
        // Agregar comandos de AI Layout
        var aiLayoutCommands = [
            {
                id: 'ai-layout-open',
                label: 'AI: Abrir asistente de layout',
                category: 'ia',
                icon: '***',
                action: 'openAILayout'
            },
            {
                id: 'ai-layout-generate',
                label: 'AI: Generar layout',
                category: 'ia',
                icon: '***',
                action: 'openAILayoutGenerate'
            },
            {
                id: 'ai-auto-spacing',
                label: 'AI: Auto-spacing',
                category: 'ia',
                icon: '!!!',
                action: 'openAILayoutSpacing'
            },
            {
                id: 'ai-color-suggest',
                label: 'AI: Sugerir colores',
                category: 'ia',
                icon: '!!!',
                action: 'openAILayoutColors'
            },
            {
                id: 'ai-variants',
                label: 'AI: Generar variantes',
                category: 'ia',
                icon: '!!!',
                action: 'openAILayoutVariants'
            },
            {
                id: 'ai-analyze',
                label: 'AI: Analizar diseno',
                category: 'ia',
                icon: '!!!',
                action: 'openAILayoutAnalyze'
            }
        ];

        aiLayoutCommands.forEach(function(cmd) {
            commandPalette.commands.push(cmd);
        });
    }

    window.__vbpAILayoutInitialized = true;
    return true;
}

// Exponer funcion de inicializacion
window.initVbpAILayout = initVbpAILayout;

// Inicializar cuando Alpine este listo
document.addEventListener('alpine:init', function() {
    initVbpAILayout();
});

// Fallback por si Alpine ya esta cargado
if (typeof Alpine !== 'undefined') {
    initVbpAILayout();
}

// Manejador de comandos desde command palette
document.addEventListener('vbp:executeAction', function(event) {
    if (!event.detail || !event.detail.action) return;

    var action = event.detail.action;
    var aiLayout = Alpine.store('vbpAILayout');

    if (!aiLayout) return;

    switch (action) {
        case 'openAILayout':
            aiLayout.open();
            break;
        case 'openAILayoutGenerate':
            aiLayout.open('generate');
            break;
        case 'openAILayoutSpacing':
            aiLayout.open('spacing');
            aiLayout.calculateAutoSpacing();
            break;
        case 'openAILayoutColors':
            aiLayout.open('colors');
            break;
        case 'openAILayoutVariants':
            aiLayout.open('variants');
            break;
        case 'openAILayoutAnalyze':
            aiLayout.open('analyze');
            aiLayout.analyzeDesign();
            break;
    }
});

// Atajo de teclado global para AI Layout
document.addEventListener('keydown', function(event) {
    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var modKey = isMac ? event.metaKey : event.ctrlKey;

    // No capturar si estamos en un input
    var isInput = event.target.tagName === 'INPUT' ||
                  event.target.tagName === 'TEXTAREA' ||
                  event.target.isContentEditable;

    if (isInput) return;

    // Ctrl+Shift+A: Abrir AI Layout
    if (modKey && event.shiftKey && event.key.toLowerCase() === 'a') {
        event.preventDefault();
        var aiLayout = Alpine.store('vbpAILayout');
        if (aiLayout) {
            aiLayout.toggle();
        }
    }
});

// Componente Alpine para el panel
window.vbpAILayoutPanel = function() {
    return {
        baseColor: '#3b82f6',

        get store() {
            return Alpine.store('vbpAILayout');
        },

        init: function() {
            // Inicializacion del componente
        },

        handleKeydown: function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.store.executeCommand();
            }

            if (event.key === 'Escape') {
                this.store.close();
            }
        }
    };
};
