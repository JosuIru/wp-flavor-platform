/**
 * Visual Builder Pro - Inspector Completo
 * Gestión de edición de componentes con soporte para items/arrays
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

/**
 * Componente Inspector
 */
function vbpInspector() {
    return {
        activeTab: 'content',
        editingItemIndex: null,
        styleUpdateFrame: null,
        styleDebounceTimers: {},
        mediaLibraryField: null,
        mediaLibraryItemIndex: null,
        spacerHeight: 60,

        // Modal de URL fallback
        urlModal: {
            isOpen: false,
            title: '',
            url: '',
            error: '',
            callback: null,
            mediaType: 'image'
        },

        /**
         * Normaliza un color para input type="color"
         * El input type="color" requiere formato #rrggbb válido
         * @param {string} color - Color a normalizar
         * @param {string} fallback - Color por defecto si inválido
         * @returns {string} Color hex válido
         */
        normalizeColorForInput: function(color, fallback) {
            fallback = fallback || '#000000';
            if (!color || typeof color !== 'string') {
                return fallback;
            }
            // Limpiar espacios
            color = color.trim();
            // Si está vacío, devolver fallback
            if (!color) {
                return fallback;
            }
            // Asegurar que empieza con #
            if (!color.startsWith('#')) {
                color = '#' + color;
            }
            // Validar formato hex válido (3 o 6 dígitos)
            var hexRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
            if (hexRegex.test(color)) {
                // Expandir formato corto #rgb a #rrggbb
                if (color.length === 4) {
                    var r = color[1], g = color[2], b = color[3];
                    return '#' + r + r + g + g + b + b;
                }
                return color;
            }
            // Color inválido, devolver fallback
            return fallback;
        },

        /**
         * Obtener elemento seleccionado con estilos completos
         */
        get selectedElement() {
            var store = Alpine.store('vbp');
            if (store.selection.elementIds.length === 1) {
                var element = store.getElementDeep(store.selection.elementIds[0]);
                // Usar el método del store para asegurar estilos completos
                return store.ensureStylesComplete(element);
            }
            return null;
        },

        /**
         * Verificar si el elemento tiene estilos completos
         */
        hasCompleteStyles() {
            var el = this.selectedElement;
            return el && el.styles && el.styles.spacing && el.styles.spacing.margin && el.styles.colors;
        },

        /**
         * Obtener nombre legible del tipo
         */
        getTypeName: function(type) {
            var nombres = {
                'hero': 'Hero',
                'features': 'Características',
                'testimonials': 'Testimonios',
                'pricing': 'Precios',
                'cta': 'CTA',
                'faq': 'FAQ',
                'contact': 'Contacto',
                'team': 'Equipo',
                'stats': 'Estadísticas',
                'gallery': 'Galería',
                'blog': 'Blog',
                'video-section': 'Video',
                'heading': 'Encabezado',
                'text': 'Texto',
                'image': 'Imagen',
                'button': 'Botón',
                'divider': 'Separador',
                'spacer': 'Espaciador',
                'icon': 'Icono',
                'html': 'HTML',
                'shortcode': 'Shortcode',
                'container': 'Contenedor',
                'columns': 'Columnas',
                'row': 'Fila',
                'grid': 'Grid',
                'video-embed': 'Video',
                'map': 'Mapa',
                'mapa': 'Mapa',
                // Nuevos bloques
                'countdown': 'Cuenta Regresiva',
                'social-icons': 'Iconos Sociales',
                'newsletter': 'Newsletter',
                'logo-grid': 'Grid de Logos',
                'icon-box': 'Caja de Icono',
                'accordion': 'Acordeón',
                'tabs': 'Pestañas',
                'progress-bar': 'Barra de Progreso',
                'alert': 'Alerta',
                'before-after': 'Antes/Después',
                'timeline': 'Línea de Tiempo',
                'carousel': 'Carrusel'
            };
            return nombres[type] || type;
        },

        /**
         * Actualizar propiedad del elemento
         */
        updateElement: function(field, value) {
            if (!this.selectedElement) return;
            var cambios = {};
            cambios[field] = value;
            Alpine.store('vbp').updateElement(this.selectedElement.id, cambios);
        },

        /**
         * Obtener variantes disponibles para el elemento actual
         */
        getVariants: function() {
            if (!this.selectedElement) return [];
            return Alpine.store('vbp').getVariantsForType(this.selectedElement.type);
        },

        /**
         * Verificar si el elemento tiene variantes
         */
        hasVariants: function() {
            return this.getVariants().length > 0;
        },

        /**
         * Verificar si el tipo de elemento es un bloque de sección
         * @param {string} type - Tipo de elemento
         * @returns {boolean}
         */
        isSectionBlock: function(type) {
            var tiposSecciones = [
                'features', 'testimonials', 'pricing', 'cta', 'faq',
                'contact', 'team', 'gallery', 'stats', 'blog',
                'video-section', 'countdown', 'newsletter', 'logo-grid',
                'accordion', 'tabs'
            ];
            return tiposSecciones.indexOf(type) !== -1;
        },

        /**
         * Cambiar variante del elemento
         */
        setVariant: function(variantId) {
            if (!this.selectedElement) return;
            Alpine.store('vbp').updateElement(this.selectedElement.id, { variant: variantId });
        },

        /**
         * Aplicar preset de estilos rápido
         * @param {string} presetName - Nombre del preset
         */
        applyStylePreset: function(presetName) {
            if (!this.selectedElement) return;

            var presets = {
                modern: {
                    colors: { background: '#ffffff', text: '#1f2937' },
                    border: { radius: '12', width: '0', color: 'transparent', style: 'none' },
                    shadow: { enabled: true, x: '0', y: '4', blur: '20', spread: '0', color: 'rgba(0,0,0,0.1)' },
                    spacing: { padding: { top: '24', right: '24', bottom: '24', left: '24' } }
                },
                minimal: {
                    colors: { background: 'transparent', text: '#374151' },
                    border: { radius: '0', width: '0', color: 'transparent', style: 'none' },
                    shadow: { enabled: false },
                    spacing: { padding: { top: '16', right: '16', bottom: '16', left: '16' } }
                },
                bold: {
                    colors: { background: '#1f2937', text: '#ffffff' },
                    border: { radius: '8', width: '0', color: 'transparent', style: 'none' },
                    shadow: { enabled: true, x: '0', y: '8', blur: '32', spread: '0', color: 'rgba(0,0,0,0.3)' },
                    spacing: { padding: { top: '32', right: '32', bottom: '32', left: '32' } }
                },
                outlined: {
                    colors: { background: 'transparent', text: '#374151' },
                    border: { radius: '8', width: '2', color: '#d1d5db', style: 'solid' },
                    shadow: { enabled: false },
                    spacing: { padding: { top: '20', right: '20', bottom: '20', left: '20' } }
                },
                gradient: {
                    colors: { text: '#ffffff' },
                    background: { type: 'gradient', gradientDirection: 'to right', gradientStart: '#6366f1', gradientEnd: '#8b5cf6' },
                    border: { radius: '16', width: '0', color: 'transparent', style: 'none' },
                    shadow: { enabled: true, x: '0', y: '10', blur: '40', spread: '0', color: 'rgba(99,102,241,0.3)' },
                    spacing: { padding: { top: '28', right: '28', bottom: '28', left: '28' } }
                },
                glassmorphism: {
                    colors: { background: 'rgba(255,255,255,0.15)', text: '#1f2937' },
                    border: { radius: '16', width: '1', color: 'rgba(255,255,255,0.3)', style: 'solid' },
                    shadow: { enabled: true, x: '0', y: '8', blur: '32', spread: '0', color: 'rgba(31,38,135,0.15)' },
                    spacing: { padding: { top: '24', right: '24', bottom: '24', left: '24' } },
                    backdrop: { blur: '10px' }
                }
            };

            var preset = presets[presetName];
            if (!preset) return;

            var store = Alpine.store('vbp');
            var currentStyles = JSON.parse(JSON.stringify(this.selectedElement.styles || {}));

            // Merge preset con estilos actuales
            var newStyles = this.deepMerge(currentStyles, preset);
            store.updateElement(this.selectedElement.id, { styles: newStyles });

            // Mostrar notificación
            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('Preset "' + presetName + '" aplicado', 'success');
            }
        },

        /**
         * Resetear estilos a valores por defecto
         */
        resetStyles: function() {
            if (!this.selectedElement) return;

            var defaultStyles = Alpine.store('vbp').getDefaultStyles();
            Alpine.store('vbp').updateElement(this.selectedElement.id, { styles: defaultStyles });

            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('Estilos reseteados', 'info');
            }
        },

        /**
         * Deep merge de objetos
         */
        deepMerge: function(target, source) {
            var result = JSON.parse(JSON.stringify(target));
            for (var key in source) {
                if (source.hasOwnProperty(key)) {
                    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                        result[key] = this.deepMerge(result[key] || {}, source[key]);
                    } else {
                        result[key] = source[key];
                    }
                }
            }
            return result;
        },

        // ============================================
        // MINI COLOR PICKER
        // ============================================

        /**
         * Estado del mini color picker
         */
        colorPickerOpen: false,
        colorPickerTarget: null,
        colorPickerPosition: { top: 0, left: 0 },
        colorPickerCurrentColor: '#000000',

        /**
         * Presets de colores para el picker
         */
        colorPresets: [
            // Grises
            '#000000', '#1f2937', '#4b5563', '#9ca3af', '#e5e7eb', '#ffffff',
            // Primarios
            '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6',
            // Tonos suaves
            '#fecaca', '#fed7aa', '#fef08a', '#bbf7d0', '#bfdbfe', '#ddd6fe',
            // Marca
            '#6366f1', '#4f46e5', '#4338ca', '#3730a3', '#312e81', '#1e1b4b'
        ],

        /**
         * Abrir mini color picker
         */
        openColorPicker: function(event, targetPath, currentValue) {
            var self = this;
            var trigger = event.currentTarget || event.target;
            var rect = trigger.getBoundingClientRect();

            this.colorPickerTarget = targetPath;
            this.colorPickerCurrentColor = this.normalizeColorForInput(currentValue, '#000000');
            this.colorPickerPosition = {
                top: rect.bottom + 8,
                left: rect.left
            };

            // Asegurar que no se sale de la pantalla
            var pickerWidth = 200;
            if (this.colorPickerPosition.left + pickerWidth > window.innerWidth) {
                this.colorPickerPosition.left = window.innerWidth - pickerWidth - 16;
            }

            this.colorPickerOpen = true;

            // Cerrar al hacer clic fuera
            setTimeout(function() {
                document.addEventListener('click', self.handleColorPickerOutsideClick.bind(self), { once: true });
            }, 10);
        },

        /**
         * Cerrar color picker al clic fuera
         */
        handleColorPickerOutsideClick: function(event) {
            var picker = document.querySelector('.vbp-mini-color-picker');
            if (picker && !picker.contains(event.target)) {
                this.closeColorPicker();
            } else if (this.colorPickerOpen) {
                // Re-añadir listener si sigue abierto
                var self = this;
                setTimeout(function() {
                    document.addEventListener('click', self.handleColorPickerOutsideClick.bind(self), { once: true });
                }, 10);
            }
        },

        /**
         * Cerrar color picker
         */
        closeColorPicker: function() {
            this.colorPickerOpen = false;
            this.colorPickerTarget = null;
        },

        /**
         * Seleccionar color del picker
         */
        selectColor: function(color) {
            if (!this.colorPickerTarget) return;

            this.colorPickerCurrentColor = color;
            this.updateStyle(this.colorPickerTarget, color);
            this.closeColorPicker();
        },

        /**
         * Actualizar color desde input
         */
        updateColorFromInput: function(event) {
            var color = event.target.value;
            if (!this.colorPickerTarget) return;

            this.colorPickerCurrentColor = color;
            this.updateStyle(this.colorPickerTarget, color);
        },

        /**
         * Copiar color al portapapeles
         */
        copyColorToClipboard: function(color) {
            navigator.clipboard.writeText(color).then(function() {
                if (window.vbpApp && window.vbpApp.showNotification) {
                    window.vbpApp.showNotification('Color copiado: ' + color, 'success');
                }
            });
        },

        /**
         * Obtener color de contraste para el texto
         */
        getContrastColor: function(hexColor) {
            if (!hexColor) return '#000000';
            var hex = hexColor.replace('#', '');
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            var r = parseInt(hex.substr(0, 2), 16);
            var g = parseInt(hex.substr(2, 2), 16);
            var b = parseInt(hex.substr(4, 2), 16);
            var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.5 ? '#000000' : '#ffffff';
        },

        /**
         * Actualizar data del elemento
         */
        updateElementData: function(field, value) {
            if (!this.selectedElement) return;
            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            data[field] = value;
            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Actualizar estilo con debounce real + preview instantáneo
         * Combina preview inmediato (CSS variable) con guardado debounced al store
         * @param {string} path - Ruta del estilo (ej: 'spacing.margin.top')
         * @param {*} value - Valor a establecer
         * @param {boolean} immediate - Si true, guarda inmediatamente sin debounce
         */
        updateStyle: function(path, value, immediate) {
            if (!this.selectedElement) return;
            var self = this;
            var store = Alpine.store('vbp');
            var elementId = this.selectedElement.id;

            // Preview instantáneo usando CSS custom properties (no afecta el store)
            this.applyStylePreview(elementId, path, value);

            // Cancelar debounce anterior si existe
            if (this.styleDebounceTimers && this.styleDebounceTimers[path]) {
                clearTimeout(this.styleDebounceTimers[path]);
            }

            // Inicializar objeto de timers si no existe
            if (!this.styleDebounceTimers) {
                this.styleDebounceTimers = {};
            }

            // Si es inmediato, guardar directamente
            if (immediate) {
                store.updateElementStyleForBreakpoint(elementId, path, value);
                return;
            }

            // Debounce de 150ms antes de guardar al store
            this.styleDebounceTimers[path] = setTimeout(function() {
                // Verificar que el elemento sigue seleccionado
                if (self.selectedElement && self.selectedElement.id === elementId) {
                    store.updateElementStyleForBreakpoint(elementId, path, value);
                }
                delete self.styleDebounceTimers[path];
            }, 150);
        },

        /**
         * Aplicar preview visual instantáneo sin guardar al store
         * Usa CSS custom properties para feedback inmediato
         */
        applyStylePreview: function(elementId, path, value) {
            // Buscar el elemento en el canvas iframe
            var canvas = document.getElementById('vbp-preview-frame');
            if (!canvas || !canvas.contentDocument) return;

            var element = canvas.contentDocument.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!element) return;

            // Mapear path a propiedad CSS
            var cssProperty = this.pathToCssProperty(path);
            if (cssProperty) {
                element.style.setProperty(cssProperty, value);
            }
        },

        /**
         * Mapear path de estilo VBP a propiedad CSS
         */
        pathToCssProperty: function(path) {
            var mappings = {
                'spacing.margin.top': 'margin-top',
                'spacing.margin.right': 'margin-right',
                'spacing.margin.bottom': 'margin-bottom',
                'spacing.margin.left': 'margin-left',
                'spacing.padding.top': 'padding-top',
                'spacing.padding.right': 'padding-right',
                'spacing.padding.bottom': 'padding-bottom',
                'spacing.padding.left': 'padding-left',
                'layout.width': 'width',
                'layout.maxWidth': 'max-width',
                'layout.minHeight': 'min-height',
                'layout.height': 'height',
                'typography.fontSize': 'font-size',
                'typography.lineHeight': 'line-height',
                'typography.letterSpacing': 'letter-spacing',
                'typography.color': 'color',
                'background.color': 'background-color',
                'border.radius': 'border-radius',
                'border.width': 'border-width',
                'border.color': 'border-color',
                'effects.opacity': 'opacity'
            };
            return mappings[path] || null;
        },

        /**
         * Forzar guardado inmediato de todos los estilos pendientes
         * Útil antes de cambiar de elemento o guardar el documento
         */
        flushPendingStyles: function() {
            if (!this.styleDebounceTimers) return;

            var self = this;
            Object.keys(this.styleDebounceTimers).forEach(function(path) {
                clearTimeout(self.styleDebounceTimers[path]);
            });
            this.styleDebounceTimers = {};
        },

        /**
         * Obtener el valor actual de un estilo considerando el breakpoint activo
         */
        getStyleValue: function(path, fallback) {
            if (!this.selectedElement) return fallback || '';
            var store = Alpine.store('vbp');
            var styles = store.getElementStyles(this.selectedElement.id);
            var value = store.getNestedValue(styles, path);
            return value !== undefined ? value : (fallback || '');
        },

        /**
         * Verificar si hay override para el breakpoint actual
         */
        hasBreakpointOverride: function(path) {
            if (!this.selectedElement) return false;
            var store = Alpine.store('vbp');
            if (store.activeBreakpoint === 'desktop') return false;

            var responsiveStyles = this.selectedElement.responsiveStyles || {};
            var breakpointStyles = responsiveStyles[store.activeBreakpoint] || {};
            return store.getNestedValue(breakpointStyles, path) !== undefined;
        },

        /**
         * Limpiar override de breakpoint para un path específico
         */
        clearBreakpointOverride: function(path) {
            if (!this.selectedElement) return;
            var store = Alpine.store('vbp');
            if (store.activeBreakpoint === 'desktop') return;

            var responsiveStyles = JSON.parse(JSON.stringify(this.selectedElement.responsiveStyles || {}));
            var breakpointStyles = responsiveStyles[store.activeBreakpoint] || {};

            // Eliminar el valor en el path
            var parts = path.split('.');
            var obj = breakpointStyles;
            for (var i = 0; i < parts.length - 1; i++) {
                if (!obj[parts[i]]) return;
                obj = obj[parts[i]];
            }
            delete obj[parts[parts.length - 1]];

            responsiveStyles[store.activeBreakpoint] = breakpointStyles;
            store.updateElement(this.selectedElement.id, { responsiveStyles: responsiveStyles });
        },

        /**
         * Toggle Auto-layout (Flexbox) en el elemento seleccionado
         */
        toggleAutoLayout: function() {
            if (!this.selectedElement) return;
            var currentDisplay = this.getStyleValue('layout.display', 'block');
            var isFlexActive = currentDisplay === 'flex';

            if (isFlexActive) {
                // Desactivar flex - volver a block
                this.updateStyle('layout.display', 'block');
                this.updateStyle('layout.flexDirection', '');
                this.updateStyle('layout.justifyContent', '');
                this.updateStyle('layout.alignItems', '');
                this.updateStyle('layout.flexWrap', '');
                this.updateStyle('layout.gap', '');
            } else {
                // Activar flex con valores por defecto
                this.updateStyle('layout.display', 'flex');
                this.updateStyle('layout.flexDirection', 'row');
                this.updateStyle('layout.justifyContent', 'flex-start');
                this.updateStyle('layout.alignItems', 'stretch');
                this.updateStyle('layout.gap', '12px');
            }
        },

        /**
         * Verificar si Auto-layout está activo
         */
        isAutoLayoutActive: function() {
            return this.getStyleValue('layout.display', 'block') === 'flex';
        },

        /**
         * Establecer dirección del flex
         */
        setFlexDirection: function(direction) {
            if (!this.selectedElement) return;
            this.updateStyle('layout.flexDirection', direction);
        },

        /**
         * Establecer alineación (justify-content y align-items)
         */
        setAlignment: function(justify, items) {
            if (!this.selectedElement) return;
            if (justify) this.updateStyle('layout.justifyContent', justify);
            if (items) this.updateStyle('layout.alignItems', items);
        },

        /**
         * Establecer distribución de espacio
         */
        setDistribution: function(value) {
            if (!this.selectedElement) return;
            this.updateStyle('layout.justifyContent', value);
        },

        /**
         * Establecer gap entre elementos
         */
        setGap: function(value) {
            if (!this.selectedElement) return;
            var gapValue = value + (isNaN(value) ? '' : 'px');
            this.updateStyle('layout.gap', gapValue);
        },

        /**
         * Obtener clase activa para botón de dirección
         */
        getDirectionClass: function(direction) {
            var current = this.getStyleValue('layout.flexDirection', 'row');
            return current === direction ? 'active' : '';
        },

        /**
         * Obtener clase activa para celda de alineación
         */
        getAlignmentClass: function(justify, items) {
            var currentJustify = this.getStyleValue('layout.justifyContent', 'flex-start');
            var currentItems = this.getStyleValue('layout.alignItems', 'stretch');
            return (currentJustify === justify && currentItems === items) ? 'active' : '';
        },

        /**
         * Obtener clase activa para botón de distribución
         */
        getDistributionClass: function(value) {
            var current = this.getStyleValue('layout.justifyContent', 'flex-start');
            return current === value ? 'active' : '';
        },

        // ============ PRESETS DE ESPACIADO ============

        /**
         * Aplicar preset de padding a los 4 lados
         */
        applyPaddingPreset: function(value) {
            if (!this.selectedElement) return;
            this.updateStyle('spacing.padding.top', value);
            this.updateStyle('spacing.padding.right', value);
            this.updateStyle('spacing.padding.bottom', value);
            this.updateStyle('spacing.padding.left', value);
        },

        /**
         * Aplicar preset de margin a los 4 lados
         */
        applyMarginPreset: function(value) {
            if (!this.selectedElement) return;
            this.updateStyle('spacing.margin.top', value);
            this.updateStyle('spacing.margin.right', value);
            this.updateStyle('spacing.margin.bottom', value);
            this.updateStyle('spacing.margin.left', value);
        },

        /**
         * Verificar si el preset de padding está activo
         */
        isPaddingPresetActive: function(value) {
            if (!this.selectedElement) return false;
            var padding = this.selectedElement.styles && this.selectedElement.styles.spacing && this.selectedElement.styles.spacing.padding;
            if (!padding) return value === '0px' || value === '0';
            var normalizedValue = value.replace('px', '');
            var top = (padding.top || '0').toString().replace('px', '');
            var right = (padding.right || '0').toString().replace('px', '');
            var bottom = (padding.bottom || '0').toString().replace('px', '');
            var left = (padding.left || '0').toString().replace('px', '');
            return top === normalizedValue && right === normalizedValue && bottom === normalizedValue && left === normalizedValue;
        },

        /**
         * Verificar si el preset de margin está activo
         */
        isMarginPresetActive: function(value) {
            if (!this.selectedElement) return false;
            var margin = this.selectedElement.styles && this.selectedElement.styles.spacing && this.selectedElement.styles.spacing.margin;
            if (!margin) return value === '0px' || value === '0';
            var normalizedValue = value.replace('px', '');
            var top = (margin.top || '0').toString().replace('px', '');
            var right = (margin.right || '0').toString().replace('px', '');
            var bottom = (margin.bottom || '0').toString().replace('px', '');
            var left = (margin.left || '0').toString().replace('px', '');
            return top === normalizedValue && right === normalizedValue && bottom === normalizedValue && left === normalizedValue;
        },

        /**
         * Cambiar breakpoint activo
         */
        setBreakpoint: function(breakpoint) {
            Alpine.store('vbp').setActiveBreakpoint(breakpoint);
        },

        /**
         * Obtener breakpoint activo
         */
        get activeBreakpoint() {
            return Alpine.store('vbp').activeBreakpoint;
        },

        /**
         * Verificar si el elemento actual tiene overrides para un breakpoint específico
         */
        hasBreakpointOverridesForElement: function(breakpoint) {
            if (!this.selectedElement || breakpoint === 'desktop') return false;
            return Alpine.store('vbp').hasBreakpointOverrides(this.selectedElement.id, breakpoint);
        },

        /**
         * Toggle visibilidad del elemento
         */
        toggleVisibility: function() {
            if (!this.selectedElement) return;
            this.updateElement('visible', !this.selectedElement.visible);
        },

        /**
         * Toggle bloqueo del elemento
         */
        toggleLock: function() {
            if (!this.selectedElement) return;
            this.updateElement('locked', !this.selectedElement.locked);
        },

        /**
         * Eliminar elemento actual
         */
        deleteCurrentElement: function() {
            if (!this.selectedElement) return;
            if (confirm(VBP_Config.strings.deleteConfirm || '¿Eliminar este elemento?')) {
                Alpine.store('vbp').removeElement(this.selectedElement.id);
            }
        },

        // ============================================
        // GESTIÓN DE ITEMS (arrays)
        // ============================================

        /**
         * Toggle edición de item
         */
        toggleItemEdit: function(index) {
            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else {
                this.editingItemIndex = index;
            }
        },

        /**
         * Añadir nuevo item según tipo
         */
        addItem: function(type) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            var nuevoItem = this.getDefaultItem(type);

            // Social icons usa 'redes' en lugar de 'items'
            if (type === 'social-icons') {
                if (!data.redes) data.redes = [];
                data.redes.push(nuevoItem);
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
                this.editingItemIndex = data.redes.length - 1;
            } else if (type === 'form') {
                // Formularios usan 'campos' en lugar de 'items'
                if (!data.campos) data.campos = [];
                data.campos.push(nuevoItem);
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
                this.editingItemIndex = data.campos.length - 1;
            } else if (type === 'timeline') {
                // Timeline usa 'eventos' en lugar de 'items'
                if (!data.eventos) data.eventos = [];
                data.eventos.push(nuevoItem);
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
                this.editingItemIndex = data.eventos.length - 1;
            } else {
                if (!data.items) data.items = [];
                data.items.push(nuevoItem);
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
                this.editingItemIndex = data.items.length - 1;
            }
        },

        /**
         * Obtener item por defecto según tipo
         */
        getDefaultItem: function(type) {
            var defaults = {
                'features': { icono: '✨', titulo: 'Nueva característica', descripcion: 'Descripción de la característica' },
                'testimonials': { texto: 'Testimonio del cliente...', autor: 'Nombre', cargo: 'Cargo', foto: '' },
                'pricing': { nombre: 'Plan', precio: '0', periodo: '/mes', caracteristicas: [], destacado: false },
                'faq': { pregunta: 'Nueva pregunta', respuesta: 'Respuesta a la pregunta' },
                'team': { nombre: 'Nombre', cargo: 'Cargo', bio: 'Biografía', foto: '' },
                'stats': { numero: '0', label: 'Etiqueta' },
                'gallery': { src: '', alt: '' },
                // Nuevos tipos
                'social-icons': { red: 'nueva_red', icono: '🔗', url: '#' },
                'accordion': { titulo: 'Nuevo elemento', contenido: 'Contenido del elemento', abierto: false },
                'tabs': { titulo: 'Nueva pestaña', contenido: 'Contenido de la pestaña' },
                'progress-bar': { label: 'Skill', porcentaje: 50 },
                'form': { tipo: 'text', label: 'Nuevo campo', placeholder: '', requerido: false },
                'timeline': { fecha: '2024', titulo: 'Nuevo evento', descripcion: 'Descripción del evento', icono: '' },
                'carousel': { imagen: '', titulo: 'Nuevo slide', descripcion: '', enlace_url: '', enlace_texto: 'Ver más' }
            };
            return defaults[type] || {};
        },

        /**
         * Obtener la propiedad de items según el tipo de elemento
         */
        getItemsProperty: function() {
            if (!this.selectedElement) return 'items';
            var type = this.selectedElement.type;
            if (type === 'timeline') return 'eventos';
            if (type === 'social-icons') return 'redes';
            if (type === 'form') return 'campos';
            return 'items';
        },

        /**
         * Actualizar campo de un item
         */
        updateItem: function(index, field, value) {
            var itemsProp = this.getItemsProperty();
            if (!this.selectedElement || !this.selectedElement.data[itemsProp]) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            if (data[itemsProp][index]) {
                data[itemsProp][index][field] = value;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Mover item arriba o abajo
         */
        moveItem: function(index, direction) {
            var itemsProp = this.getItemsProperty();
            if (!this.selectedElement || !this.selectedElement.data[itemsProp]) return;

            var nuevoIndex = index + direction;
            var items = this.selectedElement.data[itemsProp];

            if (nuevoIndex < 0 || nuevoIndex >= items.length) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            var temp = data[itemsProp][index];
            data[itemsProp][index] = data[itemsProp][nuevoIndex];
            data[itemsProp][nuevoIndex] = temp;

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            // Actualizar índice de edición si estamos editando el item movido
            if (this.editingItemIndex === index) {
                this.editingItemIndex = nuevoIndex;
            } else if (this.editingItemIndex === nuevoIndex) {
                this.editingItemIndex = index;
            }
        },

        /**
         * Eliminar item
         */
        removeItem: function(index) {
            var itemsProp = this.getItemsProperty();
            if (!this.selectedElement || !this.selectedElement.data[itemsProp]) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            data[itemsProp].splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            // Cerrar edición si estábamos editando el item eliminado
            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else if (this.editingItemIndex > index) {
                this.editingItemIndex--;
            }
        },

        // ============ MÉTODOS PARA TWO_COLUMNS ============

        /**
         * Inicializa el contenido de una columna cuando se cambia el tipo
         */
        initColumnContent: function(columna, tipo) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            // Inicializar estructura de columna si no existe
            if (!data[columna]) {
                data[columna] = { type: tipo, data: {} };
            }

            // Cambiar tipo y resetear datos
            data[columna].type = tipo;

            // Inicializar datos por defecto según tipo
            if (tipo === 'contact_info') {
                data[columna].data = {
                    titulo: 'Información',
                    items: [
                        { icono: '📧', titulo: 'Email', valor: 'contacto@ejemplo.com' },
                        { icono: '📱', titulo: 'Teléfono', valor: '+34 123 456 789' }
                    ]
                };
            } else if (tipo === 'contact_form') {
                data[columna].data = {
                    titulo: 'Contacto',
                    boton_texto: 'Enviar',
                    campos: [
                        { nombre: 'nombre', label: 'Nombre', tipo: 'text', requerido: true },
                        { nombre: 'email', label: 'Email', tipo: 'email', requerido: true },
                        { nombre: 'mensaje', label: 'Mensaje', tipo: 'textarea', requerido: true }
                    ]
                };
            } else if (tipo === 'text') {
                data[columna].data = { contenido: 'Tu texto aquí...' };
            } else if (tipo === 'image') {
                data[columna].data = { src: '', alt: '' };
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Actualiza un campo de datos de una columna
         */
        updateColumnData: function(columna, campo, valor) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            if (!data[columna]) data[columna] = { type: 'text', data: {} };
            if (!data[columna].data) data[columna].data = {};

            data[columna].data[campo] = valor;

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Añade un item a una columna (contact_info o contact_form)
         */
        addColumnItem: function(columna, tipo) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            if (!data[columna]) data[columna] = { type: tipo, data: {} };
            if (!data[columna].data) data[columna].data = {};

            if (tipo === 'contact_info') {
                if (!data[columna].data.items) data[columna].data.items = [];
                data[columna].data.items.push({ icono: '📌', titulo: 'Nuevo', valor: '' });
            } else if (tipo === 'contact_form') {
                if (!data[columna].data.campos) data[columna].data.campos = [];
                data[columna].data.campos.push({ nombre: 'campo_' + Date.now(), label: 'Nuevo campo', tipo: 'text', requerido: false });
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Actualiza un campo de un item en una columna
         */
        updateColumnItem: function(columna, index, campo, valor) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            if (!data[columna] || !data[columna].data) return;

            var colData = data[columna].data;
            var colType = data[columna].type;

            if (colType === 'contact_info' && colData.items && colData.items[index]) {
                colData.items[index][campo] = valor;
            } else if (colType === 'contact_form' && colData.campos && colData.campos[index]) {
                colData.campos[index][campo] = valor;
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Actualiza las opciones de un campo select (texto a array)
         */
        updateColumnItemOptions: function(columna, index, textValue) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            if (!data[columna] || !data[columna].data || !data[columna].data.campos) return;

            if (data[columna].data.campos[index]) {
                // Guardar texto original para el input
                data[columna].data.campos[index].opciones_text = textValue;
                // Convertir a array para el renderizado
                data[columna].data.campos[index].opciones = textValue.split(',').map(function(o) { return o.trim(); }).filter(function(o) { return o; });
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        /**
         * Elimina un item de una columna
         */
        removeColumnItem: function(columna, index) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));

            if (!data[columna] || !data[columna].data) return;

            var colData = data[columna].data;
            var colType = data[columna].type;

            if (colType === 'contact_info' && colData.items) {
                colData.items.splice(index, 1);
            } else if (colType === 'contact_form' && colData.campos) {
                colData.campos.splice(index, 1);
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        // ============ FIN MÉTODOS TWO_COLUMNS ============

        /**
         * Actualizar características de pricing (texto a array)
         */
        updatePricingFeatures: function(index, textValue) {
            if (!this.selectedElement || !this.selectedElement.data.items) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            if (data.items[index]) {
                // Convertir texto a array
                var lines = textValue.split('\n').filter(function(line) {
                    return line.trim() !== '';
                });
                data.items[index].caracteristicas = lines;
                data.items[index].caracteristicas_text = textValue;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        // ============================================
        // MEDIA LIBRARY (Expandida para todos los tipos)
        // ============================================

        /**
         * Configuración de tipos de media soportados
         */
        mediaTypeConfig: {
            image: {
                title: 'Seleccionar imagen',
                button: 'Usar imagen',
                libraryType: 'image',
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
            },
            video: {
                title: 'Seleccionar video',
                button: 'Usar video',
                libraryType: 'video',
                extensions: ['mp4', 'webm', 'ogg', 'mov']
            },
            audio: {
                title: 'Seleccionar audio',
                button: 'Usar audio',
                libraryType: 'audio',
                extensions: ['mp3', 'wav', 'ogg', 'm4a', 'flac']
            },
            file: {
                title: 'Seleccionar archivo',
                button: 'Usar archivo',
                libraryType: null, // Todos los tipos
                extensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar']
            },
            pdf: {
                title: 'Seleccionar PDF',
                button: 'Usar PDF',
                libraryType: 'application/pdf',
                extensions: ['pdf']
            },
            any: {
                title: 'Seleccionar archivo',
                button: 'Usar archivo',
                libraryType: null,
                extensions: []
            }
        },

        /**
         * Extraer metadata completa de un attachment
         * @param {object} attachment - Objeto attachment de WordPress
         * @param {string} mediaType - Tipo de media
         * @returns {object} - Metadata normalizada
         */
        extractAttachmentMetadata: function(attachment, mediaType) {
            if (!attachment) return null;

            var metadata = {
                id: attachment.id || null,
                url: attachment.url || '',
                alt: attachment.alt || '',
                title: attachment.title || '',
                caption: attachment.caption || '',
                mime: attachment.mime || attachment.type || '',
                filename: attachment.filename || '',
                filesize: attachment.filesizeHumanReadable || '',
                width: attachment.width || null,
                height: attachment.height || null
            };

            // Añadir tamaños responsive para imágenes
            if (mediaType === 'image' && attachment.sizes) {
                metadata.sizes = {};
                var sizeNames = ['thumbnail', 'medium', 'medium_large', 'large', 'full'];
                sizeNames.forEach(function(size) {
                    if (attachment.sizes[size]) {
                        metadata.sizes[size] = {
                            url: attachment.sizes[size].url,
                            width: attachment.sizes[size].width,
                            height: attachment.sizes[size].height
                        };
                    }
                });
            }

            // Para video, añadir poster si existe
            if (mediaType === 'video' && attachment.image && attachment.image.src) {
                metadata.poster = attachment.image.src;
            }

            // Para audio, añadir duración si existe
            if (mediaType === 'audio' && attachment.meta) {
                metadata.duration = attachment.meta.length_formatted || null;
                metadata.artist = attachment.meta.artist || '';
                metadata.album = attachment.meta.album || '';
            }

            return metadata;
        },

        /**
         * Abrir Media Library de WordPress (expandida)
         * @param {string} field - Campo a actualizar
         * @param {string} mediaType - Tipo de medio: 'image', 'video', 'audio', 'file', 'pdf', 'any'
         * @param {object} options - Opciones adicionales { saveMetadata: boolean, metadataField: string }
         */
        openMediaLibrary: function(field, mediaType, options) {
            var self = this;
            this.mediaLibraryField = field || 'src';
            this.mediaLibraryItemIndex = null;
            mediaType = mediaType || 'image';
            options = options || {};

            if (typeof wp !== 'undefined' && wp.media) {
                var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.any;

                var mediaFrameConfig = {
                    title: typeConfig.title,
                    button: { text: typeConfig.button },
                    multiple: false
                };

                // Filtrar por tipo de archivo si está definido
                if (typeConfig.libraryType) {
                    mediaFrameConfig.library = { type: typeConfig.libraryType };
                }

                var frame = wp.media(mediaFrameConfig);

                frame.on('select', function() {
                    var selection = frame.state().get('selection').first();
                    if (!selection) {
                        vbpLog.warn('No se seleccionó ningún archivo');
                        return;
                    }

                    var attachment = selection.toJSON();

                    // Validar que el attachment tiene URL
                    if (!attachment.url) {
                        vbpLog.error('El attachment no tiene URL válida');
                        if (window.vbpApp && window.vbpApp.showNotification) {
                            window.vbpApp.showNotification('Error: archivo sin URL válida', 'error');
                        }
                        return;
                    }

                    // Guardar URL en el campo principal
                    self.updateElementData(self.mediaLibraryField, attachment.url);

                    // Extraer y guardar metadata completa
                    var metadata = self.extractAttachmentMetadata(attachment, mediaType);

                    // Guardar metadata adicional según el tipo
                    if (mediaType === 'image') {
                        if (attachment.alt) {
                            self.updateElementData('alt', attachment.alt);
                        }
                        if (attachment.id) {
                            self.updateElementData('attachment_id', attachment.id);
                        }
                        if (metadata.sizes) {
                            self.updateElementData('sizes', metadata.sizes);
                        }
                        if (metadata.width && metadata.height) {
                            self.updateElementData('width', metadata.width);
                            self.updateElementData('height', metadata.height);
                        }
                    } else if (mediaType === 'video') {
                        if (metadata.poster) {
                            self.updateElementData('video_poster', metadata.poster);
                        }
                        if (attachment.id) {
                            self.updateElementData('attachment_id', attachment.id);
                        }
                    } else if (mediaType === 'audio') {
                        if (attachment.id) {
                            self.updateElementData('attachment_id', attachment.id);
                        }
                        if (metadata.duration) {
                            self.updateElementData('duration', metadata.duration);
                        }
                        if (metadata.title) {
                            self.updateElementData('audio_title', metadata.title);
                        }
                    } else if (mediaType === 'file' || mediaType === 'pdf') {
                        if (attachment.id) {
                            self.updateElementData('attachment_id', attachment.id);
                        }
                        if (metadata.filename) {
                            self.updateElementData('filename', metadata.filename);
                        }
                        if (metadata.filesize) {
                            self.updateElementData('filesize', metadata.filesize);
                        }
                        if (metadata.mime) {
                            self.updateElementData('mime_type', metadata.mime);
                        }
                    }

                    // Guardar toda la metadata si se solicita
                    if (options.saveMetadata && options.metadataField) {
                        self.updateElementData(options.metadataField, metadata);
                    }

                    // Notificar éxito
                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('Archivo seleccionado correctamente', 'success');
                    }
                });

                frame.open();
            } else {
                // Fallback mejorado: mostrar modal si está disponible, sino prompt
                this.showMediaFallbackDialog(field, mediaType);
            }
        },

        /**
         * Mostrar diálogo fallback cuando wp.media no está disponible
         * Usa un modal interno mejorado en lugar de prompt()
         */
        showMediaFallbackDialog: function(field, mediaType) {
            var self = this;
            var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.any;

            // Usar modal interno del inspector
            this.urlModal.isOpen = true;
            this.urlModal.title = typeConfig.title;
            this.urlModal.url = '';
            this.urlModal.error = '';
            this.urlModal.mediaType = mediaType;
            this.urlModal.callback = function(url) {
                if (url && self.isValidUrl(url)) {
                    self.updateElementData(field, url);
                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification('URL aplicada correctamente', 'success');
                    }
                }
            };
        },

        /**
         * Confirmar URL del modal
         */
        confirmUrlModal: function() {
            var url = this.urlModal.url.trim();

            // Validar URL
            if (!url) {
                this.urlModal.error = 'Por favor, introduce una URL';
                return;
            }

            if (!this.isValidUrl(url)) {
                this.urlModal.error = 'URL no válida. Debe comenzar con http://, https:// o /';
                return;
            }

            // Ejecutar callback
            if (this.urlModal.callback) {
                this.urlModal.callback(url);
            }

            // Cerrar modal
            this.closeUrlModal();
        },

        /**
         * Cerrar modal de URL
         */
        closeUrlModal: function() {
            this.urlModal.isOpen = false;
            this.urlModal.url = '';
            this.urlModal.error = '';
            this.urlModal.callback = null;
        },

        /**
         * Obtener placeholder para el modal según el tipo
         */
        getUrlPlaceholder: function() {
            var typeConfig = this.mediaTypeConfig[this.urlModal.mediaType] || this.mediaTypeConfig.any;
            var extension = typeConfig.extensions[0] || 'jpg';
            return 'https://ejemplo.com/archivo.' + extension;
        },

        /**
         * Validar si una URL es válida
         * @param {string} url - URL a validar
         * @returns {boolean}
         */
        isValidUrl: function(url) {
            if (!url || typeof url !== 'string') return false;
            // Permitir URLs relativas y absolutas
            if (url.startsWith('/')) return true;
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Abrir Media Library para un item específico (expandida)
         * @param {number} itemIndex - Índice del item
         * @param {string} field - Campo a actualizar
         * @param {string} mediaType - Tipo de medio (default: 'image')
         */
        openMediaLibraryForItem: function(itemIndex, field, mediaType) {
            var self = this;
            mediaType = mediaType || 'image';

            // Validar índice
            if (typeof itemIndex !== 'number' || itemIndex < 0) {
                vbpLog.error('Índice de item inválido:', itemIndex);
                return;
            }

            if (typeof wp !== 'undefined' && wp.media) {
                var typeConfig = this.mediaTypeConfig[mediaType] || this.mediaTypeConfig.image;

                var frame = wp.media({
                    title: typeConfig.title,
                    button: { text: typeConfig.button },
                    multiple: false,
                    library: typeConfig.libraryType ? { type: typeConfig.libraryType } : undefined
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection').first();
                    if (!selection) return;

                    var attachment = selection.toJSON();
                    if (!attachment.url) return;

                    self.updateItem(itemIndex, field, attachment.url);

                    // Guardar metadata adicional para imágenes
                    if (mediaType === 'image' && attachment.alt) {
                        self.updateItem(itemIndex, 'alt', attachment.alt);
                    }
                });

                frame.open();
            } else {
                var url = prompt('Introduce la URL:');
                if (url && this.isValidUrl(url)) {
                    this.updateItem(itemIndex, field, url);
                }
            }
        },

        /**
         * Añadir imagen a galería (con metadata mejorada)
         */
        addGalleryImage: function() {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar imágenes',
                    button: { text: 'Añadir imágenes' },
                    multiple: true,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    if (!attachments || attachments.length === 0) return;

                    var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));
                    if (!data.items) data.items = [];

                    attachments.forEach(function(attachment) {
                        if (!attachment.url) return;

                        var item = {
                            src: attachment.url,
                            alt: attachment.alt || '',
                            attachment_id: attachment.id || null,
                            width: attachment.width || null,
                            height: attachment.height || null
                        };

                        // Añadir tamaños responsive si existen
                        if (attachment.sizes && attachment.sizes.medium) {
                            item.thumbnail = attachment.sizes.medium.url;
                        }

                        data.items.push(item);
                    });

                    Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });

                    if (window.vbpApp && window.vbpApp.showNotification) {
                        window.vbpApp.showNotification(attachments.length + ' imagen(es) añadida(s)', 'success');
                    }
                });

                frame.open();
            } else {
                var url = prompt('Introduce la URL de la imagen:');
                if (url && this.isValidUrl(url)) {
                    this.addItem('gallery');
                    var items = this.selectedElement.data.items || [];
                    var lastIndex = items.length - 1;
                    if (lastIndex >= 0) {
                        this.updateItem(lastIndex, 'src', url);
                    }
                }
            }
        },

        /**
         * Abrir Media Library para seleccionar archivo (PDF, documento, etc.)
         * Útil para campos de enlace que apuntan a archivos
         * @param {string} field - Campo a actualizar
         */
        openFileLibrary: function(field) {
            this.openMediaLibrary(field, 'file');
        },

        /**
         * Abrir Media Library para seleccionar audio
         * @param {string} field - Campo a actualizar
         */
        openAudioLibrary: function(field) {
            this.openMediaLibrary(field, 'audio');
        },

        // ============================================
        // SOCIAL ICONS
        // ============================================

        /**
         * Actualizar campo de red social
         */
        updateSocialItem: function(index, field, value) {
            if (!this.selectedElement || !this.selectedElement.data.redes) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            if (data.redes[index]) {
                data.redes[index][field] = value;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Eliminar red social
         */
        removeSocialItem: function(index) {
            if (!this.selectedElement || !this.selectedElement.data.redes) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            data.redes.splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else if (this.editingItemIndex > index) {
                this.editingItemIndex--;
            }
        },

        // ============================================
        // ICON & EMOJI SELECTORS
        // ============================================

        /**
         * Abrir selector de iconos para un item
         */
        openIconSelectorForItem: function(itemIndex, field) {
            var self = this;
            field = field || 'icono';

            Alpine.store('vbpModals').openIconSelector(
                function(type, value) {
                    // Formato del valor según el tipo
                    var iconValue = type === 'material' ? value : value;
                    self.updateItem(itemIndex, field, iconValue);
                },
                this.selectedElement.data.items[itemIndex][field] || '',
                field,
                itemIndex
            );
        },

        /**
         * Abrir selector de iconos para campo directo
         */
        openIconSelector: function(field) {
            var self = this;
            field = field || 'icono';

            Alpine.store('vbpModals').openIconSelector(
                function(type, value) {
                    self.updateElementData(field, value);
                },
                this.selectedElement.data[field] || '',
                field,
                null
            );
        },

        /**
         * Abrir selector de emojis para un item
         */
        openEmojiPickerForItem: function(event, itemIndex, field) {
            var self = this;
            field = field || 'emoji';
            var rect = event.target.getBoundingClientRect();

            Alpine.store('vbpModals').openEmojiPicker(
                function(emoji) {
                    self.updateItem(itemIndex, field, emoji);
                },
                { x: rect.left, y: rect.bottom + 5 },
                field,
                itemIndex
            );
        },

        /**
         * Abrir selector de emojis para campo directo
         */
        openEmojiPicker: function(event, field) {
            var self = this;
            field = field || 'emoji';
            var rect = event.target.getBoundingClientRect();

            Alpine.store('vbpModals').openEmojiPicker(
                function(emoji) {
                    self.updateElementData(field, emoji);
                },
                { x: rect.left, y: rect.bottom + 5 },
                field,
                null
            );
        },

        // ============================================
        // FORM FIELDS
        // ============================================

        /**
         * Actualizar campo de formulario
         */
        updateCampo: function(index, field, value) {
            if (!this.selectedElement || !this.selectedElement.data.campos) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            if (data.campos[index]) {
                data.campos[index][field] = value;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Mover campo de formulario arriba o abajo
         */
        moveCampo: function(index, direction) {
            if (!this.selectedElement || !this.selectedElement.data.campos) return;

            var nuevoIndex = index + direction;
            var campos = this.selectedElement.data.campos;

            if (nuevoIndex < 0 || nuevoIndex >= campos.length) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            var temp = data.campos[index];
            data.campos[index] = data.campos[nuevoIndex];
            data.campos[nuevoIndex] = temp;

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            if (this.editingItemIndex === index) {
                this.editingItemIndex = nuevoIndex;
            } else if (this.editingItemIndex === nuevoIndex) {
                this.editingItemIndex = index;
            }
        },

        /**
         * Eliminar campo de formulario
         */
        removeCampo: function(index) {
            if (!this.selectedElement || !this.selectedElement.data.campos) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            data.campos.splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else if (this.editingItemIndex > index) {
                this.editingItemIndex--;
            }
        },

        // ============================================
        // LOGO GRID
        // ============================================

        /**
         * Añadir logo a grid
         */
        addLogoImage: function() {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar logos',
                    button: { text: 'Añadir logos' },
                    multiple: true
                });

                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));

                    if (!data.logos) data.logos = [];

                    attachments.forEach(function(attachment) {
                        data.logos.push({
                            src: attachment.url,
                            alt: attachment.alt || 'Logo'
                        });
                    });

                    Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });
                });

                frame.open();
            } else {
                var url = prompt('Introduce la URL del logo:');
                if (url) {
                    var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
                    if (!data.logos) data.logos = [];
                    data.logos.push({ src: url, alt: 'Logo' });
                    Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
                }
            }
        },

        /**
         * Eliminar logo del grid
         */
        removeLogoItem: function(index) {
            if (!this.selectedElement || !this.selectedElement.data.logos) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            data.logos.splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        // ============================================
        // COLOR PALETTE
        // ============================================

        /**
         * Obtiene los colores de la paleta del sitio
         */
        getSitePalette: function() {
            if (typeof VBP_Config !== 'undefined' && VBP_Config.designSettings) {
                return [
                    { color: VBP_Config.designSettings.primary_color || '#3b82f6', label: 'Pri' },
                    { color: VBP_Config.designSettings.secondary_color || '#8b5cf6', label: 'Sec' },
                    { color: VBP_Config.designSettings.accent_color || '#f59e0b', label: 'Acc' },
                    { color: VBP_Config.designSettings.text_color || '#1f2937', label: 'Txt' },
                    { color: VBP_Config.designSettings.text_muted_color || '#6b7280', label: 'Mut' },
                    { color: VBP_Config.designSettings.background_color || '#ffffff', label: 'Bg' },
                ];
            }
            return [
                { color: '#3b82f6', label: 'Pri' },
                { color: '#8b5cf6', label: 'Sec' },
                { color: '#f59e0b', label: 'Acc' },
                { color: '#1f2937', label: 'Txt' },
                { color: '#ffffff', label: 'Bg' },
            ];
        },

        /**
         * Aplicar color desde la paleta
         */
        applyColorFromPalette: function(stylePath, color) {
            this.updateStyle(stylePath, color);
        },

        /**
         * Verifica si un color es el activo
         */
        isColorActive: function(stylePath, paletteColor) {
            if (!this.selectedElement || !this.selectedElement.styles) return false;
            var parts = stylePath.split('.');
            var value = this.selectedElement.styles;
            for (var i = 0; i < parts.length; i++) {
                if (!value || !value[parts[i]]) return false;
                value = value[parts[i]];
            }
            return value && value.toLowerCase() === paletteColor.toLowerCase();
        },

        // ============================================
        // MODULE WIDGETS
        // ============================================

        /**
         * Verifica si el elemento seleccionado es un widget de módulo
         */
        isModuleWidget: function() {
            if (!this.selectedElement) return false;
            var blockInfo = this.getBlockInfo(this.selectedElement.type);
            return blockInfo && blockInfo.module;
        },

        /**
         * Obtiene los campos configurables del módulo actual
         */
        getModuleFields: function() {
            var blockInfo = this.getBlockInfo(this.selectedElement.type);
            return blockInfo && blockInfo.fields ? blockInfo.fields : {};
        },

        /**
         * Obtiene el nombre del módulo actual
         */
        getModuleName: function() {
            var blockInfo = this.getBlockInfo(this.selectedElement.type);
            return blockInfo && blockInfo.name ? blockInfo.name : this.selectedElement.type;
        },

        /**
         * Obtiene el icono del módulo actual
         */
        getModuleIcon: function() {
            var blockInfo = this.getBlockInfo(this.selectedElement.type);
            return blockInfo && blockInfo.icon ? blockInfo.icon : '';
        },

        /**
         * Obtiene información de un bloque por su ID
         */
        getBlockInfo: function(type) {
            if (typeof VBP_Config === 'undefined' || !VBP_Config.blocks) return null;
            var categorias = VBP_Config.blocks;
            for (var i = 0; i < categorias.length; i++) {
                var bloques = categorias[i].blocks || [];
                for (var j = 0; j < bloques.length; j++) {
                    if (bloques[j].id === type) {
                        return bloques[j];
                    }
                }
            }
            return null;
        },

        /**
         * Refresca la preview del módulo en el canvas
         */
        refreshModulePreview: function() {
            if (!this.selectedElement) return;

            var self = this;
            var store = Alpine.store('vbp');

            // Notificar al canvas que debe refrescar el elemento
            store.refreshElement(this.selectedElement.id);

            // Mostrar notificación
            if (typeof Alpine.store('vbpToast') !== 'undefined') {
                Alpine.store('vbpToast').show('Preview actualizada', 'success', 1500);
            }
        },

        // ============================================
        // SELECTOR DE EMOJIS
        // ============================================

        /**
         * Abrir selector de emojis para un campo
         */
        openEmojiPicker: function(event, field) {
            var self = this;
            if (!this.selectedElement) return;

            var rect = event.target.getBoundingClientRect();
            var position = {
                x: rect.left,
                y: rect.bottom + 5
            };

            Alpine.store('vbpModals').openEmojiPicker(
                function(emoji) {
                    self.updateElementData(field, emoji);
                },
                position,
                field,
                null
            );
        },

        /**
         * Abrir selector de emojis para un item específico
         */
        openEmojiPickerForItem: function(event, itemIndex, field) {
            var self = this;
            if (!this.selectedElement) return;

            var rect = event.target.getBoundingClientRect();
            var position = {
                x: rect.left,
                y: rect.bottom + 5
            };

            Alpine.store('vbpModals').openEmojiPicker(
                function(emoji) {
                    self.updateItem(itemIndex, field, emoji);
                },
                position,
                field,
                itemIndex
            );
        },

        // ============================================
        // SELECTOR DE ICONOS
        // ============================================

        /**
         * Abrir selector de iconos para un campo
         */
        openIconSelector: function(field) {
            var self = this;
            if (!this.selectedElement) return;

            var currentValue = this.selectedElement.data[field] || '';

            Alpine.store('vbpModals').openIconSelector(
                function(type, value) {
                    // Manejar todos los tipos: material, svg, emoji
                    if (value) {
                        self.updateElementData(field, value);
                    }
                },
                currentValue,
                field,
                null
            );
        },

        /**
         * Abrir selector de iconos para un item específico
         */
        openIconSelectorForItem: function(itemIndex, field) {
            var self = this;
            if (!this.selectedElement) return;

            var items = this.selectedElement.data.items || [];
            var currentValue = items[itemIndex] ? items[itemIndex][field] || '' : '';

            Alpine.store('vbpModals').openIconSelector(
                function(type, value) {
                    self.updateItem(itemIndex, field, value);
                },
                currentValue,
                field,
                itemIndex
            );
        },

        /**
         * Abrir selector de iconos para red social
         */
        openIconSelectorForSocial: function(index) {
            var self = this;
            if (!this.selectedElement || !this.selectedElement.data.redes) return;

            var currentValue = this.selectedElement.data.redes[index] ? this.selectedElement.data.redes[index].icono || '' : '';

            Alpine.store('vbpModals').openIconSelector(
                function(type, value) {
                    self.updateSocialItem(index, 'icono', value);
                },
                currentValue,
                'icono',
                index
            );
        },

        // ============================================
        // GEOCODIFICACIÓN
        // ============================================

        /**
         * Geocodificar una dirección usando Nominatim (OpenStreetMap)
         * @param {string} address - Dirección a geocodificar
         * @param {boolean} isGeocoding - Estado de carga (pasado por referencia Alpine)
         * @param {object} refs - Referencias Alpine
         */
        geocodeAddress: function(address) {
            var self = this;
            if (!address || !this.selectedElement) return;

            // Usar la API de Nominatim (OpenStreetMap) - gratuita y sin API key
            var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1';

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'FlavorChatIA-VBP/1.0'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data && data.length > 0) {
                    var result = data[0];
                    self.updateElementData('lat', result.lat);
                    self.updateElementData('lng', result.lon);

                    // Mostrar toast de éxito si está disponible
                    if (typeof VBPToast !== 'undefined') {
                        VBPToast.success('Coordenadas encontradas: ' + result.display_name.substring(0, 50) + '...');
                    }
                } else {
                    // No se encontraron resultados
                    if (typeof VBPToast !== 'undefined') {
                        VBPToast.warning('No se encontraron coordenadas para esa dirección');
                    }
                }
            })
            .catch(function(error) {
                vbpLog.error('Error de geocodificación:', error);
                if (typeof VBPToast !== 'undefined') {
                    VBPToast.error('Error al buscar coordenadas');
                }
            });
        },

        // ============================================
        // COLUMNS/GRID WIDTH MANAGEMENT
        // ============================================

        /**
         * Obtener anchos de columnas actuales
         * @returns {Array} Array de anchos en porcentaje
         */
        getColumnWidths: function() {
            if (!this.selectedElement || !this.selectedElement.data) return [];

            var data = this.selectedElement.data;
            var numColumns = parseInt(data.columnas) || parseInt(data.columns) || 2;
            var widths = data.columnWidths || [];

            // Si no hay anchos definidos, crear array con distribución equitativa
            if (widths.length === 0 || widths.length !== numColumns) {
                var equalWidth = 100 / numColumns;
                widths = [];
                for (var i = 0; i < numColumns; i++) {
                    widths.push(equalWidth.toFixed(1) + '%');
                }
            }

            return widths;
        },

        /**
         * Actualizar ancho de una columna específica
         * @param {number} index - Índice de la columna
         * @param {string|number} value - Nuevo ancho en porcentaje
         */
        updateColumnWidth: function(index, value) {
            if (!this.selectedElement) return;

            var data = this.selectedElement.data || {};
            var numColumns = parseInt(data.columnas) || parseInt(data.columns) || 2;
            var widths = this.getColumnWidths().slice(); // Copia

            // Convertir valor a porcentaje
            var newWidth = parseFloat(value);
            if (isNaN(newWidth)) return;

            // Limitar entre 10% y 80%
            newWidth = Math.max(10, Math.min(80, newWidth));

            // Calcular la diferencia
            var oldWidth = parseFloat(widths[index]) || (100 / numColumns);
            var diff = newWidth - oldWidth;

            // Actualizar el ancho de esta columna
            widths[index] = newWidth.toFixed(1) + '%';

            // Distribuir la diferencia entre las demás columnas
            if (numColumns > 1 && diff !== 0) {
                var diffPerColumn = diff / (numColumns - 1);

                for (var i = 0; i < numColumns; i++) {
                    if (i !== index) {
                        var currentWidth = parseFloat(widths[i]) || (100 / numColumns);
                        var adjustedWidth = currentWidth - diffPerColumn;
                        // Asegurar mínimo de 10%
                        adjustedWidth = Math.max(10, adjustedWidth);
                        widths[i] = adjustedWidth.toFixed(1) + '%';
                    }
                }

                // Normalizar para que sumen 100%
                var total = widths.reduce(function(sum, w) {
                    return sum + parseFloat(w);
                }, 0);

                if (Math.abs(total - 100) > 0.5) {
                    var factor = 100 / total;
                    widths = widths.map(function(w) {
                        return (parseFloat(w) * factor).toFixed(1) + '%';
                    });
                }
            }

            // Actualizar datos con columnWidths y gridTemplateColumns
            var newData = JSON.parse(JSON.stringify(data));
            newData.columnWidths = widths;
            newData.gridTemplateColumns = widths.join(' ');

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: newData });
        },

        /**
         * Resetear anchos de columnas a distribución equitativa
         */
        resetColumnWidths: function() {
            if (!this.selectedElement) return;

            var data = this.selectedElement.data || {};
            var numColumns = parseInt(data.columnas) || parseInt(data.columns) || 2;
            var equalWidth = 100 / numColumns;
            var widths = [];

            for (var i = 0; i < numColumns; i++) {
                widths.push(equalWidth.toFixed(1) + '%');
            }

            var newData = JSON.parse(JSON.stringify(data));
            newData.columnWidths = widths;
            newData.gridTemplateColumns = widths.join(' ');

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: newData });

            if (typeof Alpine.store('vbp').addNotification === 'function') {
                Alpine.store('vbp').addNotification('Columnas igualadas', 'info');
            }
        },

        /**
         * Actualizar número de columnas y ajustar anchos
         * @param {number} count - Nuevo número de columnas
         */
        updateColumnsCount: function(count) {
            if (!this.selectedElement) return;

            var currentData = this.selectedElement.data || {};
            var oldCount = parseInt(currentData.columnas) || parseInt(currentData.columns) || 2;
            var oldWidths = currentData.columnWidths || [];
            var children = JSON.parse(JSON.stringify(this.selectedElement.children || []));

            // Crear nuevos anchos
            var equalWidth = 100 / count;
            var newWidths = [];

            for (var i = 0; i < count; i++) {
                if (i < oldWidths.length && oldCount === count) {
                    // Mantener anchos existentes si el número no cambia
                    newWidths.push(oldWidths[i]);
                } else {
                    // Distribución equitativa para columnas nuevas
                    newWidths.push(equalWidth.toFixed(1) + '%');
                }
            }

            // Ajustar índices de hijos huérfanos
            if (children.length > 0) {
                children.forEach(function(child) {
                    if (typeof child._columnIndex === 'number' && child._columnIndex >= count) {
                        child._columnIndex = count - 1;
                    }
                });
            }

            // Actualizar ambos valores
            var data = JSON.parse(JSON.stringify(currentData));
            data.columnas = count;
            data.columns = count; // Alias
            data.columnWidths = newWidths;
            data.gridTemplateColumns = newWidths.join(' ');

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data, children: children });
        },

        /**
         * Aplicar un preset de columnas
         * @param {Array} widths - Array de porcentajes [50, 50] o [33, 67] etc.
         */
        applyColumnPreset: function(widths) {
            if (!this.selectedElement) return;

            var count = widths.length;
            var newWidths = widths.map(function(w) {
                return w + '%';
            });

            // Actualizar datos del elemento
            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            data.columnas = count;
            data.columns = count; // Alias para compatibilidad
            data.columnWidths = newWidths;

            // Actualizar gridTemplateColumns para el layout
            data.gridTemplateColumns = widths.map(function(w) {
                return w + '%';
            }).join(' ');

            // Ajustar hijos para las nuevas columnas
            var children = this.selectedElement.children || [];
            // Si hay más columnas que antes, los hijos mantienen su columna
            // Si hay menos, mover los huérfanos a la última columna válida
            if (children.length > 0) {
                children.forEach(function(child) {
                    if (typeof child._columnIndex === 'number' && child._columnIndex >= count) {
                        child._columnIndex = count - 1;
                    }
                });
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data, children: children });

            // Notificación visual
            if (typeof Alpine.store('vbp').addNotification === 'function') {
                Alpine.store('vbp').addNotification('Layout aplicado: ' + widths.join('% / ') + '%', 'success');
            }
        },

        /**
         * Invertir orden de las columnas
         */
        reverseColumns: function() {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            var children = JSON.parse(JSON.stringify(this.selectedElement.children || []));

            if (data.columnWidths && data.columnWidths.length > 1) {
                data.columnWidths = data.columnWidths.reverse();
                data.gridTemplateColumns = data.columnWidths.join(' ');
            }

            // Invertir índice de columnas de los hijos
            var cols = parseInt(data.columnas) || parseInt(data.columns) || 2;
            if (children.length > 0) {
                children.forEach(function(child) {
                    if (typeof child._columnIndex === 'number') {
                        child._columnIndex = (cols - 1) - child._columnIndex;
                    }
                });
            }

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data, children: children });

            if (typeof Alpine.store('vbp').addNotification === 'function') {
                Alpine.store('vbp').addNotification('Columnas invertidas', 'info');
            }
        },

        // ============================================
        // HELPERS
        // ============================================

        /**
         * Inicializar spacer height desde el elemento
         */
        init: function() {
            var self = this;

            // Cargar estado de secciones colapsadas desde localStorage
            this.loadCollapsedSections();

            // Observar cambios en el elemento seleccionado
            this.$watch('selectedElement', function(el) {
                if (el && el.type === 'spacer' && el.data && el.data.height) {
                    var height = parseInt(el.data.height);
                    if (!isNaN(height)) {
                        self.spacerHeight = height;
                    }
                }
                // Cerrar edición de items al cambiar de elemento
                self.editingItemIndex = null;
            });
        },

        // ============================================
        // SECCIONES COLAPSABLES
        // ============================================

        /**
         * Estado de secciones colapsadas
         */
        collapsedSections: {},

        /**
         * Cargar estado de secciones desde localStorage
         */
        loadCollapsedSections: function() {
            try {
                var savedState = localStorage.getItem('vbp_inspector_collapsed');
                if (savedState) {
                    this.collapsedSections = JSON.parse(savedState);
                }
            } catch (e) {
                this.collapsedSections = {};
            }
        },

        /**
         * Guardar estado de secciones en localStorage
         */
        saveCollapsedSections: function() {
            try {
                localStorage.setItem('vbp_inspector_collapsed', JSON.stringify(this.collapsedSections));
            } catch (e) {
                // localStorage no disponible o lleno
            }
        },

        /**
         * Toggle estado de una sección
         * @param {string} sectionId - ID de la sección
         */
        toggleSection: function(sectionId) {
            if (this.collapsedSections[sectionId]) {
                delete this.collapsedSections[sectionId];
            } else {
                this.collapsedSections[sectionId] = true;
            }
            this.saveCollapsedSections();
        },

        /**
         * Verificar si una sección está colapsada
         * @param {string} sectionId - ID de la sección
         * @returns {boolean}
         */
        isSectionCollapsed: function(sectionId) {
            return !!this.collapsedSections[sectionId];
        },

        /**
         * Expandir todas las secciones
         */
        expandAllSections: function() {
            this.collapsedSections = {};
            this.saveCollapsedSections();
        },

        /**
         * Colapsar todas las secciones
         */
        collapseAllSections: function() {
            var sections = ['content', 'typography', 'spacing', 'colors', 'background', 'border', 'shadow', 'advanced'];
            var self = this;
            sections.forEach(function(section) {
                self.collapsedSections[section] = true;
            });
            this.saveCollapsedSections();
        },

        // ============================================
        // DEBOUNCE PARA INPUTS DE TEXTO
        // ============================================

        /**
         * Timer para debounce
         */
        debounceTimers: {},

        /**
         * Actualizar campo con debounce para mejor rendimiento
         * @param {string} field - Campo a actualizar
         * @param {*} value - Nuevo valor
         * @param {number} delay - Delay en ms (default: 300)
         */
        updateElementDataDebounced: function(field, value, delay) {
            var self = this;
            delay = delay || 300;

            // Cancelar timer anterior
            if (this.debounceTimers[field]) {
                clearTimeout(this.debounceTimers[field]);
            }

            // Crear nuevo timer
            this.debounceTimers[field] = setTimeout(function() {
                self.updateElementData(field, value);
                delete self.debounceTimers[field];
            }, delay);
        },

        /**
         * Actualizar estilo con debounce
         * @param {string} path - Path del estilo
         * @param {*} value - Nuevo valor
         * @param {number} delay - Delay en ms (default: 150)
         */
        updateStyleDebounced: function(path, value, delay) {
            var self = this;
            delay = delay || 150;

            // Cancelar timer anterior
            if (this.debounceTimers['style_' + path]) {
                clearTimeout(this.debounceTimers['style_' + path]);
            }

            // Crear nuevo timer
            this.debounceTimers['style_' + path] = setTimeout(function() {
                self.updateStyle(path, value);
                delete self.debounceTimers['style_' + path];
            }, delay);
        }
    };
}

/**
 * Componente Alpine para el selector de iconos
 */
function vbpIconSelector() {
    return {
        activeTab: 'material',
        searchQuery: '',
        selectedIcon: '',
        selectedType: '',
        customSvgUrl: '',
        filteredIcons: [],

        init: function() {
            var self = this;
            // Observar cuando se abre el modal
            this.$watch('$store.vbpModals.iconSelector.open', function(isOpen) {
                if (isOpen) {
                    self.resetState();
                    // Establecer valor actual si existe
                    var currentValue = Alpine.store('vbpModals').iconSelector.currentValue;
                    if (currentValue) {
                        // Verificar si es un Material Icon o un emoji/URL
                        if (currentValue.startsWith('http') || currentValue.startsWith('/')) {
                            self.customSvgUrl = currentValue;
                            self.selectedType = 'svg';
                            self.activeTab = 'svg';
                        } else if (currentValue.length > 4 || /^[a-z_]+$/.test(currentValue)) {
                            // Probablemente Material Icon
                            self.selectedIcon = currentValue;
                            self.selectedType = 'material';
                        } else {
                            // Emoji
                            self.selectedIcon = currentValue;
                            self.selectedType = 'emoji';
                        }
                    }
                }
            });
        },

        resetState: function() {
            this.searchQuery = '';
            this.selectedIcon = '';
            this.selectedType = '';
            this.customSvgUrl = '';
            this.activeTab = 'material';
        },

        closeModal: function() {
            Alpine.store('vbpModals').closeIconSelector();
            this.resetState();
        },

        selectIcon: function(type, icon) {
            this.selectedIcon = icon;
            this.selectedType = type;
            this.customSvgUrl = '';
        },

        isIconVisible: function(iconName) {
            if (!this.searchQuery) return true;
            var query = this.searchQuery.toLowerCase();
            return iconName.toLowerCase().indexOf(query) !== -1;
        },

        filterIcons: function() {
            // La filtración se hace en tiempo real con isIconVisible
        },

        openMediaLibrarySvg: function() {
            var self = this;
            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar SVG',
                    button: { text: 'Usar SVG' },
                    multiple: false,
                    library: { type: 'image/svg+xml' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    self.customSvgUrl = attachment.url;
                    self.selectedIcon = '';
                    self.selectedType = 'svg';
                });

                frame.open();
            }
        },

        clearCustomSvg: function() {
            this.customSvgUrl = '';
            this.selectedType = '';
        },

        confirmSelection: function() {
            if (this.selectedType === 'svg' && this.customSvgUrl) {
                Alpine.store('vbpModals').applyIconSelection('svg', this.customSvgUrl);
            } else if (this.selectedIcon) {
                Alpine.store('vbpModals').applyIconSelection(this.selectedType, this.selectedIcon);
            }
            this.closeModal();
        }
    };
}
