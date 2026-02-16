/**
 * Visual Builder Pro - Inspector Completo
 * Gestión de edición de componentes con soporte para items/arrays
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Componente Inspector
 */
function vbpInspector() {
    return {
        activeTab: 'content',
        editingItemIndex: null,
        styleUpdateFrame: null,
        mediaLibraryField: null,
        mediaLibraryItemIndex: null,
        spacerHeight: 60,

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
         * Obtener elemento seleccionado
         */
        get selectedElement() {
            var store = Alpine.store('vbp');
            if (store.selection.elementIds.length === 1) {
                return store.getElement(store.selection.elementIds[0]);
            }
            return null;
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
                'before-after': 'Antes/Después'
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
         * Cambiar variante del elemento
         */
        setVariant: function(variantId) {
            if (!this.selectedElement) return;
            Alpine.store('vbp').updateElement(this.selectedElement.id, { variant: variantId });
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
         * Actualizar estilo con requestAnimationFrame (preview instantáneo)
         * Ahora soporta estilos responsive por breakpoint
         */
        updateStyle: function(path, value) {
            if (!this.selectedElement) return;
            var self = this;
            var store = Alpine.store('vbp');

            // Cancelar frame anterior si existe
            if (this.styleUpdateFrame) {
                cancelAnimationFrame(this.styleUpdateFrame);
            }

            // Usar requestAnimationFrame para update instantáneo pero sincronizado con render
            this.styleUpdateFrame = requestAnimationFrame(function() {
                // Usar el método del store que maneja breakpoints
                store.updateElementStyleForBreakpoint(self.selectedElement.id, path, value);
            });
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
                'form': { tipo: 'text', label: 'Nuevo campo', placeholder: '', requerido: false }
            };
            return defaults[type] || {};
        },

        /**
         * Actualizar campo de un item
         */
        updateItem: function(index, field, value) {
            if (!this.selectedElement || !this.selectedElement.data.items) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            if (data.items[index]) {
                data.items[index][field] = value;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Mover item arriba o abajo
         */
        moveItem: function(index, direction) {
            if (!this.selectedElement || !this.selectedElement.data.items) return;

            var nuevoIndex = index + direction;
            var items = this.selectedElement.data.items;

            if (nuevoIndex < 0 || nuevoIndex >= items.length) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            var temp = data.items[index];
            data.items[index] = data.items[nuevoIndex];
            data.items[nuevoIndex] = temp;

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
            if (!this.selectedElement || !this.selectedElement.data.items) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data));
            data.items.splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            // Cerrar edición si estábamos editando el item eliminado
            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else if (this.editingItemIndex > index) {
                this.editingItemIndex--;
            }
        },

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
        // MEDIA LIBRARY
        // ============================================

        /**
         * Abrir Media Library de WordPress
         */
        openMediaLibrary: function(field) {
            var self = this;
            this.mediaLibraryField = field || 'src';
            this.mediaLibraryItemIndex = null;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar imagen',
                    button: { text: 'Usar imagen' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    self.updateElementData(self.mediaLibraryField, attachment.url);

                    // Si es imagen, también actualizar el alt
                    if (self.mediaLibraryField === 'src' && attachment.alt) {
                        self.updateElementData('alt', attachment.alt);
                    }
                });

                frame.open();
            } else {
                // Fallback: prompt para URL
                var url = prompt('Introduce la URL de la imagen:');
                if (url) {
                    this.updateElementData(field, url);
                }
            }
        },

        /**
         * Abrir Media Library para un item específico
         */
        openMediaLibraryForItem: function(itemIndex, field) {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar imagen',
                    button: { text: 'Usar imagen' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    self.updateItem(itemIndex, field, attachment.url);
                });

                frame.open();
            } else {
                var url = prompt('Introduce la URL de la imagen:');
                if (url) {
                    this.updateItem(itemIndex, field, url);
                }
            }
        },

        /**
         * Añadir imagen a galería
         */
        addGalleryImage: function() {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar imágenes',
                    button: { text: 'Añadir imágenes' },
                    multiple: true
                });

                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    var data = JSON.parse(JSON.stringify(self.selectedElement.data || {}));

                    if (!data.items) data.items = [];

                    attachments.forEach(function(attachment) {
                        data.items.push({
                            src: attachment.url,
                            alt: attachment.alt || ''
                        });
                    });

                    Alpine.store('vbp').updateElement(self.selectedElement.id, { data: data });
                });

                frame.open();
            } else {
                var url = prompt('Introduce la URL de la imagen:');
                if (url) {
                    this.addItem('gallery');
                    var lastIndex = this.selectedElement.data.items.length - 1;
                    this.updateItem(lastIndex, 'src', url);
                }
            }
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
                console.error('[VBP] Error de geocodificación:', error);
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

            var numColumns = this.selectedElement.data.columns || 2;
            var widths = this.selectedElement.data.columnWidths || [];

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

            var numColumns = this.selectedElement.data.columns || 2;
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

            this.updateElementData('columnWidths', widths);
        },

        /**
         * Resetear anchos de columnas a distribución equitativa
         */
        resetColumnWidths: function() {
            if (!this.selectedElement) return;

            var numColumns = this.selectedElement.data.columns || 2;
            var equalWidth = 100 / numColumns;
            var widths = [];

            for (var i = 0; i < numColumns; i++) {
                widths.push(equalWidth.toFixed(1) + '%');
            }

            this.updateElementData('columnWidths', widths);
        },

        /**
         * Actualizar número de columnas y ajustar anchos
         * @param {number} count - Nuevo número de columnas
         */
        updateColumnsCount: function(count) {
            if (!this.selectedElement) return;

            var oldCount = this.selectedElement.data.columns || 2;
            var oldWidths = this.selectedElement.data.columnWidths || [];

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

            // Actualizar ambos valores
            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            data.columns = count;
            data.columnWidths = newWidths;

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
        },

        // ============================================
        // HELPERS
        // ============================================

        /**
         * Inicializar spacer height desde el elemento
         */
        init: function() {
            var self = this;

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
