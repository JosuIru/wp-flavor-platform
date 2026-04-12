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
    var inspector = {
        activeTab: 'content',
        editingItemIndex: null,
        styleUpdateFrame: null,
        styleDebounceTimers: {},
        mediaLibraryField: null,
        mediaLibraryItemIndex: null,
        spacerHeight: 60,
        threeDInspectorInstance: null,

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

        get selectionCount() {
            var store = Alpine.store('vbp');
            return store && store.selection && Array.isArray(store.selection.elementIds)
                ? store.selection.elementIds.length
                : 0;
        },

        get hasMultipleSelection() {
            return this.selectionCount > 1;
        },

        get selectedElementsSummary() {
            var store = Alpine.store('vbp');
            if (!store || !store.selection || !Array.isArray(store.selection.elementIds)) {
                return '';
            }

            var self = this;
            var counts = {};

            store.selection.elementIds.forEach(function(id) {
                var element = store.getElementDeep(id) || store.getElement(id);
                if (!element || !element.type) {
                    return;
                }
                counts[element.type] = (counts[element.type] || 0) + 1;
            });

            var keys = Object.keys(counts);
            if (!keys.length) {
                return '';
            }

            return keys.map(function(type) {
                var count = counts[type];
                return count + ' ' + self.getTypeName(type);
            }).join(' · ');
        },

        get isStructuralSelection() {
            return !!(this.selectedElement && ['container', 'columns', 'row', 'grid'].indexOf(this.selectedElement.type) !== -1);
        },

        get selectionContextEyebrow() {
            if (!this.selectedElement) {
                return '';
            }

            return this.isStructuralSelection ? __('structure', 'Estructura') : __('content', 'Contenido');
        },

        get selectionContextTitle() {
            if (!this.selectedElement) {
                return '';
            }

            return this.getTypeName(this.selectedElement.type);
        },

        get selectionContextDescription() {
            if (!this.selectedElement) {
                return '';
            }

            if (this.isStructuralSelection) {
                return __('editingStructure', 'Estás editando la base del layout. Ajusta distribución, columnas y espaciado; el contenido vive dentro de sus bloques hijos.');
            }

            if (this.isSectionBlock(this.selectedElement.type)) {
                return __('editingSectionBlock', 'Bloque de sección listo para ajustar contenido principal, apariencia y jerarquía visual desde este panel.');
            }

            return __('editingElement', 'Elemento individual listo para edición rápida. Cambia contenido, apariencia y comportamiento sin salir del contexto actual.');
        },

        get selectedElementPath() {
            var store = Alpine.store('vbp');
            if (!store || !this.selectedElement) {
                return [];
            }

            return store.getElementPath(this.selectedElement.id) || [];
        },

        get selectedElementPathSummary() {
            var path = this.selectedElementPath || [];
            if (path.length <= 1) {
                return __('page', 'Página');
            }

            return path.map(function(node) {
                return node.name || node.type || node.id;
            }).join(' / ');
        },

        get isColumnsLikeSelection() {
            return !!(this.selectedElement && ['columns', 'row'].indexOf(this.selectedElement.type) !== -1);
        },

        get isContainerSelection() {
            return !!(this.selectedElement && this.selectedElement.type === 'container');
        },

        get isGridSelection() {
            return !!(this.selectedElement && this.selectedElement.type === 'grid');
        },

        is3DElementSelection() {
            return !!(this.selectedElement && ['3d-scene', '3d-object', '3d-model', '3d-text', '3d-particles', '3d-light', '3d-group', '3d-animation'].indexOf(this.selectedElement.type) !== -1);
        },

        get3DSceneId() {
            var store = Alpine.store('vbp');
            var path = store && this.selectedElement ? store.getElementPath(this.selectedElement.id) : [];
            var sceneNode = Array.isArray(path) ? path.find(function(node) {
                return node.type === '3d-scene';
            }) : null;
            return sceneNode ? sceneNode.id : null;
        },

        get3DObjectOptions() {
            var store = Alpine.store('vbp');
            var sceneId = this.get3DSceneId();
            var sceneElement = sceneId && store ? (store.getElementDeep(sceneId) || store.getElement(sceneId)) : null;
            var options = [];

            function walk(children) {
                if (!Array.isArray(children)) {
                    return;
                }

                children.forEach(function(child) {
                    if (!child || !child.type) {
                        return;
                    }

                    if (['3d-object', '3d-model', '3d-text', '3d-group'].indexOf(child.type) !== -1) {
                        options.push({
                            id: child.id,
                            name: child.name || child.type,
                            type: child.type
                        });
                    }

                    if (Array.isArray(child.children) && child.children.length) {
                        walk(child.children);
                    }
                });
            }

            if (sceneElement && Array.isArray(sceneElement.children)) {
                walk(sceneElement.children);
            }

            return options;
        },

        sanitize3DAnimationTarget() {
            if (!this.selectedElement || this.selectedElement.type !== '3d-animation') {
                return;
            }

            var targetId = this.selectedElement.data && this.selectedElement.data.target;
            if (!targetId) {
                return;
            }

            var exists = this.get3DObjectOptions().some(function(option) {
                return option.id === targetId;
            });

            if (!exists) {
                this.updateElementData('target', '');
            }
        },

        add3DChildToSelectedGroup(type) {
            if (!this.selectedElement || this.selectedElement.type !== '3d-group') {
                return;
            }

            var store = Alpine.store('vbp');
            if (!store) {
                return;
            }

            var groupElement = store.getElementDeep(this.selectedElement.id) || store.getElement(this.selectedElement.id);
            if (!groupElement) {
                return;
            }

            var child = {
                id: (typeof generateElementId === 'function') ? generateElementId() : 'el_' + Math.random().toString(36).substr(2, 9),
                type: type,
                variant: store.getDefaultVariant ? store.getDefaultVariant(type) : 'default',
                name: store.getDefaultName ? store.getDefaultName(type) : type,
                visible: true,
                locked: false,
                data: store.getDefaultData ? store.getDefaultData(type) : {},
                styles: store.getDefaultStyles ? store.getDefaultStyles() : {},
                children: []
            };

            var nextChildren = Array.isArray(groupElement.children) ? JSON.parse(JSON.stringify(groupElement.children)) : [];
            nextChildren.push(child);
            store.updateElement(groupElement.id, { children: nextChildren });
            store.setSelection([child.id]);
            this.activeTab = 'content';
        },

        selectAdjacent3DGroupChild(direction) {
            if (!this.selectedElement) {
                return;
            }

            var store = Alpine.store('vbp');
            if (!store || typeof store.getElementPath !== 'function') {
                return;
            }

            var path = store.getElementPath(this.selectedElement.id) || [];
            var groupNode = null;
            var currentNode = null;

            path.forEach(function(node) {
                if (node && node.id === this.selectedElement.id) {
                    currentNode = node;
                }
                if (node && node.type === '3d-group') {
                    groupNode = node;
                }
            }, this);

            if (!groupNode || !currentNode || !Array.isArray(groupNode.children) || groupNode.children.length === 0) {
                return;
            }

            var currentIndex = groupNode.children.findIndex(function(child) {
                return child && child.id === currentNode.id;
            });

            if (currentIndex === -1) {
                currentIndex = 0;
            }

            var step = direction === 'prev' ? -1 : 1;
            var nextIndex = (currentIndex + step + groupNode.children.length) % groupNode.children.length;
            var nextChild = groupNode.children[nextIndex];

            if (nextChild && nextChild.id) {
                store.setSelection([nextChild.id]);
                this.activeTab = 'content';
            }
        },

        ungroupSelected3DGroup() {
            if (!this.selectedElement || this.selectedElement.type !== '3d-group') {
                return;
            }

            var store = Alpine.store('vbp');
            if (!store || typeof store.getElementPath !== 'function') {
                return;
            }

            var groupId = this.selectedElement.id;
            var path = store.getElementPath(groupId) || [];
            var parentNode = path.length > 1 ? path[path.length - 2] : null;

            if (!parentNode || (parentNode.type !== '3d-scene' && parentNode.type !== '3d-group')) {
                return;
            }

            var parentElement = store.getElementDeep(parentNode.id) || store.getElement(parentNode.id);
            var groupElement = store.getElementDeep(groupId) || store.getElement(groupId);
            if (!parentElement || !groupElement) {
                return;
            }

            var parentChildren = Array.isArray(parentElement.children) ? JSON.parse(JSON.stringify(parentElement.children)) : [];
            var groupIndex = parentChildren.findIndex(function(child) {
                return child && child.id === groupId;
            });

            if (groupIndex === -1) {
                return;
            }

            var liftedChildren = Array.isArray(groupElement.children) ? JSON.parse(JSON.stringify(groupElement.children)) : [];
            parentChildren.splice(groupIndex, 1);

            liftedChildren.forEach(function(child, offset) {
                parentChildren.splice(groupIndex + offset, 0, child);
            });

            store.updateElement(parentElement.id, { children: parentChildren });

            if (liftedChildren.length > 0) {
                store.setSelection([liftedChildren[0].id]);
            } else {
                store.setSelection([parentElement.id]);
            }

            this.activeTab = 'content';
        },

        sync3DInspectorHost() {
            var host = document.getElementById('vbp-3d-inspector-host');
            if (!host) {
                return;
            }

            if (!this.is3DElementSelection()) {
                if (this.threeDInspectorInstance && typeof this.threeDInspectorInstance.destroy === 'function') {
                    this.threeDInspectorInstance.destroy();
                }
                this.threeDInspectorInstance = null;
                host.innerHTML = '';
                return;
            }

            if (!window.VBP3DInspector || typeof window.VBP3DInspector !== 'function') {
                host.innerHTML = '<div class="vbp-field-hint">Inspector 3D avanzado no disponible.</div>';
                return;
            }

            if (!this.threeDInspectorInstance) {
                this.threeDInspectorInstance = new window.VBP3DInspector('vbp-3d-inspector-host');
                this.threeDInspectorInstance.init();
            }

            var store = Alpine.store('vbp');
            var path = store && this.selectedElement ? store.getElementPath(this.selectedElement.id) : [];
            var sceneNode = Array.isArray(path) ? path.find(function(node) {
                return node.type === '3d-scene';
            }) : null;

            if (sceneNode && window.VBP3D && typeof window.VBP3D.getScene === 'function') {
                var currentScene = window.VBP3D.getScene(sceneNode.id);
                if (currentScene) {
                    this.threeDInspectorInstance.sceneId = sceneNode.id;
                    this.threeDInspectorInstance.scene = currentScene;
                    if (typeof this.threeDInspectorInstance._updateObjectsTree === 'function') {
                        this.threeDInspectorInstance._updateObjectsTree();
                    }
                    if (this.selectedElement.type !== '3d-scene' && currentScene.objects && currentScene.objects.get(this.selectedElement.id)) {
                        this.threeDInspectorInstance.selectedObjectId = this.selectedElement.id;
                        this.threeDInspectorInstance._onObjectSelected({
                            detail: {
                                sceneId: sceneNode.id,
                                objectId: this.selectedElement.id,
                                object: currentScene.objects.get(this.selectedElement.id)
                            }
                        });
                    }
                }
            }
        },

        get currentStructureColumnsCount() {
            if (!this.selectedElement || !this.selectedElement.data) {
                return 0;
            }

            if (this.isGridSelection) {
                return parseInt(this.selectedElement.data.columnas) || 3;
            }

            if (this.isColumnsLikeSelection) {
                return parseInt(this.selectedElement.data.columnas) || parseInt(this.selectedElement.data.columns) || 2;
            }

            return 0;
        },

        /**
         * Verificar si el elemento tiene estilos completos
         */
        hasCompleteStyles() {
            var el = this.selectedElement;
            return el && el.styles && el.styles.spacing && el.styles.spacing.margin && el.styles.colors;
        },

        clearSelection() {
            Alpine.store('vbp').clearSelection();
        },

        deleteSelectedElements() {
            var store = Alpine.store('vbp');
            if (!store || !store.selection || !store.selection.elementIds.length) {
                return;
            }

            var ids = store.selection.elementIds.slice();
            var total = ids.length;
            var message = total === 1
                ? __('deleteElementConfirm', '¿Eliminar el elemento seleccionado?')
                : _s('deleteElementsConfirm', total);

            if (!window.confirm(message)) {
                return;
            }

            store.batchOperations(function() {
                ids.forEach(function(id) {
                    store.removeElement(id);
                });
            });

            if (typeof store.addNotification === 'function') {
                var notificationMessage = total === 1
                    ? __('elementDeleted', 'Elemento eliminado')
                    : _s('elementsDeleted', total);
                store.addNotification(notificationMessage, 'success');
            }
        },

        /**
         * Obtener nombre legible del tipo
         * Usa el sistema i18n para obtener traducciones
         */
        getTypeName: function(type) {
            // Mapeo de tipo a clave de traducción
            var keyMap = {
                'hero': 'blockHero',
                'features': 'blockFeatures',
                'testimonials': 'blockTestimonials',
                'pricing': 'blockPricing',
                'cta': 'blockCta',
                'faq': 'blockFaq',
                'contact': 'blockContact',
                'team': 'blockTeam',
                'stats': 'blockStats',
                'gallery': 'blockGallery',
                'blog': 'blockBlog',
                'video-section': 'blockVideo',
                'heading': 'blockHeading',
                'text': 'blockText',
                'image': 'blockImage',
                'button': 'blockButton',
                'divider': 'blockDivider',
                'spacer': 'blockSpacer',
                'icon': 'blockIcon',
                'html': 'blockHtml',
                'shortcode': 'blockShortcode',
                'container': 'blockContainer',
                'columns': 'blockColumns',
                'row': 'blockRow',
                'grid': 'blockGrid',
                'video-embed': 'blockVideo',
                'map': 'blockMap',
                'mapa': 'blockMap',
                'countdown': 'blockCountdown',
                'social-icons': 'blockSocialIcons',
                'newsletter': 'blockNewsletter',
                'logo-grid': 'blockLogoGrid',
                'icon-box': 'blockIconBox',
                'accordion': 'blockAccordion',
                'tabs': 'blockTabs',
                'progress-bar': 'blockProgressBar',
                'alert': 'blockAlert',
                'before-after': 'blockBeforeAfter',
                'timeline': 'blockTimeline',
                'carousel': 'blockCarousel'
            };

            // Fallbacks para nombres por defecto
            var fallbacks = {
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

            var key = keyMap[type];
            if (key && typeof window.__ === 'function') {
                return __(key, fallbacks[type] || type);
            }
            return fallbacks[type] || type;
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
                var message = typeof window._s === 'function'
                    ? _s('presetApplied', presetName)
                    : 'Preset "' + presetName + '" aplicado';
                window.vbpApp.showNotification(message, 'success');
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
                window.vbpApp.showNotification(__('stylesReset', 'Estilos reseteados'), 'info');
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
            if (typeof field === 'string' && field.indexOf('.') !== -1) {
                var parts = field.split('.');
                var target = data;
                for (var i = 0; i < parts.length - 1; i++) {
                    if (!target[parts[i]] || typeof target[parts[i]] !== 'object') {
                        target[parts[i]] = {};
                    }
                    target = target[parts[i]];
                }
                target[parts[parts.length - 1]] = value;
            } else {
                data[field] = value;
            }
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

        duplicateCurrentElement: function() {
            if (!this.selectedElement) return;

            Alpine.store('vbp').duplicateElement(this.selectedElement.id);

            if (typeof Alpine.store('vbp').addNotification === 'function') {
                Alpine.store('vbp').addNotification('Elemento duplicado', 'success');
            }
        },

        focusSelectedElement: function() {
            if (!this.selectedElement || !this.selectedElement.id) return;

            var elementNode = document.querySelector('.vbp-element[data-element-id="' + this.selectedElement.id + '"]');
            if (!elementNode) return;

            elementNode.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

            if (typeof elementNode.focus === 'function') {
                elementNode.focus({ preventScroll: true });
            }
        },

        selectPathNode: function(nodeId) {
            var store = Alpine.store('vbp');
            if (!store || !nodeId || nodeId === 'root') {
                return;
            }

            store.setSelection([nodeId]);

            var elementNode = document.querySelector('.vbp-element[data-element-id="' + nodeId + '"]');
            if (elementNode) {
                elementNode.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                if (typeof elementNode.focus === 'function') {
                    elementNode.focus({ preventScroll: true });
                }
            }
        },

        setContainerWidthPreset: function(value) {
            if (!this.isContainerSelection) return;
            this.updateElementData('max_width', value);
        },

        setContainerAlignmentQuick: function(value) {
            if (!this.isContainerSelection) return;
            this.updateElementData('align', value);
        },

        toggleContainerFullHeightQuick: function() {
            if (!this.isContainerSelection) return;
            this.updateElementData('full_height', !this.selectedElement.data.full_height);
        },

        setQuickColumnsCount: function(count) {
            if (!this.isColumnsLikeSelection && !this.isGridSelection) return;

            if (this.isGridSelection) {
                this.updateElementData('columnas', count);
                return;
            }

            this.updateColumnsCount(count);
        },

        setQuickGap: function(value) {
            if (!this.selectedElement) return;
            this.updateElementData('gap', value);
        },

        toggleMobileStackQuick: function() {
            if (!this.isColumnsLikeSelection) return;
            this.updateElementData('stackOnMobile', this.selectedElement.data.stackOnMobile === false);
        },

        applyStructureLayoutPreset: function(preset) {
            if (!this.selectedElement) return;

            if (this.isColumnsLikeSelection) {
                switch (preset) {
                    case 'equal-2':
                        this.applyColumnPreset([50, 50]);
                        return;
                    case 'equal-3':
                        this.applyColumnPreset([33, 33, 34]);
                        return;
                    case 'sidebar-left':
                        this.applyColumnPreset([33, 67]);
                        return;
                    case 'sidebar-right':
                        this.applyColumnPreset([67, 33]);
                        return;
                }
            }

            if (this.isGridSelection) {
                switch (preset) {
                    case 'grid-2':
                        this.updateElementData('columnas', 2);
                        this.updateElementData('auto_fit', '');
                        return;
                    case 'grid-3':
                        this.updateElementData('columnas', 3);
                        this.updateElementData('auto_fit', '');
                        return;
                    case 'grid-auto':
                        this.updateElementData('auto_fit', 'auto-fit');
                        if (!this.selectedElement.data.min_col_width) {
                            this.updateElementData('min_col_width', '220px');
                        }
                        return;
                }
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
            if (type === 'timeline') {
                if (this.selectedElement.data && Array.isArray(this.selectedElement.data.eventos)) return 'eventos';
                if (this.selectedElement.data && Array.isArray(this.selectedElement.data.items)) return 'items';
                return 'eventos';
            }
            if (type === 'social-icons') return 'redes';
            if (type === 'form') return 'campos';
            return 'items';
        },

        /**
         * Obtener una colección editable de forma segura.
         * Devuelve siempre un array aunque el dato llegue incompleto/importado.
         */
        getEditableCollection: function(prop) {
            if (!this.selectedElement || !this.selectedElement.data) {
                return [];
            }

            var collection = this.selectedElement.data[prop];
            if (Array.isArray(collection)) {
                return collection;
            }

            if (this.selectedElement.type === 'timeline') {
                if (prop === 'eventos' && Array.isArray(this.selectedElement.data.items)) {
                    return this.selectedElement.data.items;
                }
                if (prop === 'items' && Array.isArray(this.selectedElement.data.eventos)) {
                    return this.selectedElement.data.eventos;
                }
            }

            return Array.isArray(collection) ? collection : [];
        },

        /**
         * Obtener longitud segura de una colección editable.
         */
        getEditableCollectionLength: function(prop) {
            return this.getEditableCollection(prop).length;
        },

        /**
         * Actualizar campo de un item
         */
        updateItem: function(index, field, value) {
            var itemsProp = this.getItemsProperty();
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data[itemsProp])) {
                data[itemsProp] = [];
            }

            if (data[itemsProp][index]) {
                data[itemsProp][index][field] = value;

                if (this.selectedElement.type === 'accordion' && field === 'abierto' && value && !data.multiples_abiertos) {
                    for (var ai = 0; ai < data[itemsProp].length; ai++) {
                        if (ai !== index && data[itemsProp][ai]) {
                            data[itemsProp][ai].abierto = false;
                        }
                    }
                }

                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Mover item arriba o abajo
         */
        moveItem: function(index, direction) {
            var itemsProp = this.getItemsProperty();
            if (!this.selectedElement) return;

            var nuevoIndex = index + direction;
            var items = this.getEditableCollection(itemsProp);

            if (nuevoIndex < 0 || nuevoIndex >= items.length) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data[itemsProp])) {
                data[itemsProp] = [];
            }
            var temp = data[itemsProp][index];
            data[itemsProp][index] = data[itemsProp][nuevoIndex];
            data[itemsProp][nuevoIndex] = temp;

            if (this.selectedElement.type === 'tabs') {
                var activeTab = parseInt(data.tab_activa, 10);
                if (!isNaN(activeTab)) {
                    if (activeTab === index) {
                        data.tab_activa = nuevoIndex;
                    } else if (activeTab === nuevoIndex) {
                        data.tab_activa = index;
                    }
                }
            }

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
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data[itemsProp])) {
                data[itemsProp] = [];
            }
            data[itemsProp].splice(index, 1);

            if (this.selectedElement.type === 'tabs') {
                var activeTab = parseInt(data.tab_activa, 10);
                if (isNaN(activeTab)) {
                    data.tab_activa = 0;
                } else if (activeTab === index) {
                    data.tab_activa = Math.max(0, Math.min(index, data[itemsProp].length - 1));
                } else if (activeTab > index) {
                    data.tab_activa = activeTab - 1;
                }
            }

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

        openColumnMediaLibrary: function(columna, campo, mediaType) {
            var self = this;
            mediaType = mediaType || 'image';

            if (!this.selectedElement) return;

            if (typeof wp !== 'undefined' && wp.media) {
                var typeConfig = this.mediaTypeConfig && this.mediaTypeConfig[mediaType]
                    ? this.mediaTypeConfig[mediaType]
                    : { title: 'Seleccionar archivo', button: 'Usar archivo', libraryType: null };

                var frameConfig = {
                    title: typeConfig.title,
                    button: { text: typeConfig.button },
                    multiple: false
                };

                if (typeConfig.libraryType) {
                    frameConfig.library = { type: typeConfig.libraryType };
                }

                var frame = wp.media(frameConfig);
                frame.on('select', function() {
                    var selection = frame.state().get('selection').first();
                    if (!selection) return;

                    var attachment = selection.toJSON();
                    if (!attachment.url) return;

                    self.updateColumnData(columna, campo, attachment.url);

                    if (mediaType === 'image' && attachment.alt) {
                        self.updateColumnData(columna, 'alt', attachment.alt);
                    }
                });
                frame.open();
                return;
            }

            if (typeof this.showMediaFallbackDialog === 'function') {
                this.showMediaFallbackDialog(campo, mediaType);
                this.urlModal.callback = function(url) {
                    if (url && self.isValidUrl(url)) {
                        self.updateColumnData(columna, campo, url);
                    }
                };
                return;
            }

            var url = prompt('Introduce la URL del archivo:');
            if (url) {
                this.updateColumnData(columna, campo, url);
            }
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
        // SOCIAL ICONS
        // ============================================

        /**
         * Actualizar campo de red social
         */
        updateSocialItem: function(index, field, value) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data.redes)) {
                data.redes = [];
            }
            if (data.redes[index]) {
                data.redes[index][field] = value;
                Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });
            }
        },

        /**
         * Eliminar red social
         */
        removeSocialItem: function(index) {
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data.redes)) {
                data.redes = [];
            }
            data.redes.splice(index, 1);

            Alpine.store('vbp').updateElement(this.selectedElement.id, { data: data });

            if (this.editingItemIndex === index) {
                this.editingItemIndex = null;
            } else if (this.editingItemIndex > index) {
                this.editingItemIndex--;
            }
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
            if (typeof this.addMediaCollectionItems === 'function') {
                return this.addMediaCollectionItems({
                    collectionField: 'logos',
                    mediaType: 'image',
                    title: 'Seleccionar logos',
                    buttonText: 'Añadir logos',
                    fallbackField: 'src',
                    mapAttachment: function(attachment) {
                        return {
                            src: attachment.url,
                            alt: attachment.alt || attachment.title || 'Logo',
                            attachment_id: attachment.id || null,
                            width: attachment.width || null,
                            height: attachment.height || null
                        };
                    }
                });
            }

            if (typeof wp !== 'undefined' && wp.media) {
                var self = this;
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
            if (!this.selectedElement) return;

            var data = JSON.parse(JSON.stringify(this.selectedElement.data || {}));
            if (!Array.isArray(data.logos)) {
                data.logos = [];
            }
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

            this.$nextTick(function() {
                document.dispatchEvent(new CustomEvent('vbp:inspector:init'));
            });

            document.addEventListener('vbp:editElement', function() {
                self.activeTab = 'content';

                var inspectorPanel = document.querySelector('.vbp-sidebar-right');
                if (inspectorPanel) {
                    inspectorPanel.scrollTop = 0;
                }
            });

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
                self.sanitize3DAnimationTarget();
                self.sync3DInspectorHost();
            });

            this.$watch('$store.vbp.inspectorMode', function(mode) {
                if (mode === 'basic') {
                    self.activeTab = 'content';
                }
            });

            document.addEventListener('vbp-3d-object-selected', function(event) {
                var store = Alpine.store('vbp');
                if (!store || !event.detail || !event.detail.objectId) {
                    return;
                }

                var existingElement = store.getElementDeep(event.detail.objectId) || store.getElement(event.detail.objectId);
                if (existingElement) {
                    store.setSelection([event.detail.objectId]);
                    self.activeTab = 'content';
                }
            });

            document.addEventListener('vbp:contentChanged', function() {
                self.sanitize3DAnimationTarget();
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

    if (typeof window.extendVBPInspector === 'function') {
        inspector = window.extendVBPInspector(inspector) || inspector;
    }

    return inspector;
}

// Exportar a window para acceso global
window.vbpInspector = vbpInspector;

/**
 * Registrar componente en Alpine
 */
function registerInspectorComponent() {
    if (typeof Alpine === 'undefined') return false;
    Alpine.data('vbpInspector', vbpInspector);
    return true;
}

// Registrar inmediatamente si Alpine ya existe
if (typeof Alpine !== 'undefined') {
    registerInspectorComponent();
}

// También escuchar el evento por si Alpine se carga después
document.addEventListener('alpine:init', function() {
    registerInspectorComponent();
});
