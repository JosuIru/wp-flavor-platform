/**
 * Visual Builder Pro - Global Styles Panel
 *
 * Panel de gestión de estilos globales reutilizables.
 * Permite crear, editar, eliminar y aplicar estilos globales.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    // Esperar a que Alpine esté listo
    document.addEventListener('alpine:init', function() {

        /**
         * Componente Alpine para el panel de Global Styles
         */
        Alpine.data('vbpGlobalStylesPanel', function() {
            return {
                // Estado del panel
                isOpen: false,
                activeCategory: 'all',
                searchQuery: '',
                isLoading: true,

                // Datos
                styles: [],
                categories: [],
                groupedStyles: {},

                // Estado de edición
                isEditing: false,
                editingStyle: null,
                isCreating: false,

                // Formulario de creación/edición
                formData: {
                    name: '',
                    category: 'typography',
                    description: '',
                    styles: {}
                },

                /**
                 * Inicialización del componente
                 */
                init: function() {
                    var self = this;

                    // Cargar datos iniciales
                    this.loadStyles();
                    this.loadCategories();

                    // Escuchar eventos de actualización
                    document.addEventListener('vbp:global-style-updated', function() {
                        self.loadStyles();
                    });

                    document.addEventListener('vbp:global-style-applied', function() {
                        self.loadStyles();
                    });
                },

                /**
                 * Cargar todos los estilos
                 */
                loadStyles: function() {
                    var self = this;
                    this.isLoading = true;

                    if (window.VBPGlobalStyles) {
                        VBPGlobalStyles.getGrouped().then(function(grouped) {
                            self.groupedStyles = grouped;
                            self.isLoading = false;
                        }).catch(function(error) {
                            console.error('Error cargando estilos:', error);
                            self.isLoading = false;
                        });

                        VBPGlobalStyles.getAll().then(function(styles) {
                            self.styles = styles;
                        });
                    } else {
                        this.isLoading = false;
                    }
                },

                /**
                 * Cargar categorías
                 */
                loadCategories: function() {
                    var self = this;

                    if (window.VBPGlobalStyles) {
                        VBPGlobalStyles.getCategories().then(function(categories) {
                            self.categories = categories;
                        });
                    }
                },

                /**
                 * Obtener estilos filtrados
                 */
                get filteredStyles() {
                    var self = this;
                    var query = this.searchQuery.toLowerCase();

                    if (!query && this.activeCategory === 'all') {
                        return this.styles;
                    }

                    return this.styles.filter(function(style) {
                        var matchesCategory = self.activeCategory === 'all' || style.category === self.activeCategory;
                        var matchesSearch = !query ||
                            style.name.toLowerCase().includes(query) ||
                            (style.description && style.description.toLowerCase().includes(query));

                        return matchesCategory && matchesSearch;
                    });
                },

                /**
                 * Verificar si una categoría tiene estilos
                 */
                categoryHasStyles: function(categoryId) {
                    return this.styles.some(function(style) {
                        return style.category === categoryId;
                    });
                },

                /**
                 * Contar estilos en una categoría
                 */
                countStylesInCategory: function(categoryId) {
                    return this.styles.filter(function(style) {
                        return style.category === categoryId;
                    }).length;
                },

                /**
                 * Abrir panel de creación
                 */
                openCreatePanel: function() {
                    this.isCreating = true;
                    this.isEditing = false;
                    this.editingStyle = null;
                    this.formData = {
                        name: '',
                        category: this.activeCategory !== 'all' ? this.activeCategory : 'typography',
                        description: '',
                        styles: {}
                    };
                },

                /**
                 * Abrir panel de edición
                 */
                openEditPanel: function(style) {
                    this.isEditing = true;
                    this.isCreating = false;
                    this.editingStyle = style;
                    this.formData = {
                        name: style.name,
                        category: style.category,
                        description: style.description || '',
                        styles: JSON.parse(JSON.stringify(style.styles || {}))
                    };
                },

                /**
                 * Cerrar panel de edición/creación
                 */
                closeEditPanel: function() {
                    this.isEditing = false;
                    this.isCreating = false;
                    this.editingStyle = null;
                },

                /**
                 * Guardar estilo (crear o actualizar)
                 */
                saveStyle: function() {
                    var self = this;

                    if (!this.formData.name.trim()) {
                        this.showToast('El nombre es requerido', 'error');
                        return;
                    }

                    var styleData = {
                        name: this.formData.name,
                        category: this.formData.category,
                        description: this.formData.description,
                        styles: this.formData.styles
                    };

                    var promise;
                    if (this.isEditing && this.editingStyle) {
                        promise = VBPGlobalStyles.update(this.editingStyle.id, styleData);
                    } else {
                        promise = VBPGlobalStyles.create(styleData);
                    }

                    promise.then(function() {
                        self.showToast(self.getStrings().styleSaved, 'success');
                        self.loadStyles();
                        self.closeEditPanel();
                    }).catch(function(error) {
                        self.showToast(error.message || 'Error al guardar', 'error');
                    });
                },

                /**
                 * Eliminar estilo
                 */
                deleteStyle: function(style) {
                    var self = this;

                    if (style.isDefault) {
                        this.showToast('No se pueden eliminar estilos predefinidos', 'error');
                        return;
                    }

                    if (!confirm(this.getStrings().confirmDelete)) {
                        return;
                    }

                    VBPGlobalStyles.delete(style.id).then(function() {
                        self.showToast(self.getStrings().styleDeleted, 'success');
                        self.loadStyles();
                        if (self.editingStyle && self.editingStyle.id === style.id) {
                            self.closeEditPanel();
                        }
                    }).catch(function(error) {
                        self.showToast(error.message || 'Error al eliminar', 'error');
                    });
                },

                /**
                 * Aplicar estilo al elemento seleccionado
                 */
                applyToSelected: function(style) {
                    var store = Alpine.store('vbp');
                    if (!store || !store.selection || !store.selection.elementIds.length) {
                        this.showToast('No hay elemento seleccionado', 'error');
                        return;
                    }

                    var elementId = store.selection.elementIds[0];
                    VBPGlobalStyles.applyToElement(elementId, style.id);
                    this.showToast(this.getStrings().styleApplied, 'success');
                },

                /**
                 * Crear estilo desde elemento seleccionado
                 */
                createFromSelected: function() {
                    var store = Alpine.store('vbp');
                    if (!store || !store.selection || !store.selection.elementIds.length) {
                        this.showToast('No hay elemento seleccionado', 'error');
                        return;
                    }

                    var self = this;
                    var elementId = store.selection.elementIds[0];
                    var element = store.getElementDeep(elementId);

                    if (!element) {
                        return;
                    }

                    // Extraer estilos del elemento
                    var extractedStyles = VBPGlobalStyles.extractStylesFromElement(element);

                    // Abrir panel de creación con estilos pre-llenados
                    this.isCreating = true;
                    this.isEditing = false;
                    this.editingStyle = null;
                    this.formData = {
                        name: 'Estilo de ' + (element.name || element.type),
                        category: VBPGlobalStyles.suggestCategory(element.type),
                        description: 'Creado desde ' + element.type,
                        styles: extractedStyles
                    };
                },

                /**
                 * Obtener preview de un estilo
                 */
                getStylePreview: function(style) {
                    var styles = style.styles || {};
                    var previewStyles = [];

                    if (styles.fontSize) {
                        previewStyles.push('font-size: ' + styles.fontSize);
                    }
                    if (styles.fontWeight) {
                        previewStyles.push('font-weight: ' + styles.fontWeight);
                    }
                    if (styles.color) {
                        previewStyles.push('color: ' + styles.color);
                    }
                    if (styles.backgroundColor) {
                        previewStyles.push('background-color: ' + styles.backgroundColor);
                    }
                    if (styles.borderRadius) {
                        previewStyles.push('border-radius: ' + styles.borderRadius);
                    }
                    if (styles.padding) {
                        previewStyles.push('padding: ' + styles.padding);
                    }

                    return previewStyles.join('; ');
                },

                /**
                 * Obtener texto de preview según categoría
                 */
                getPreviewText: function(style) {
                    switch (style.category) {
                        case 'typography':
                            return 'Aa';
                        case 'buttons':
                            return 'Btn';
                        case 'containers':
                            return '[ ]';
                        default:
                            return style.name.charAt(0).toUpperCase();
                    }
                },

                /**
                 * Contar uso de un estilo
                 */
                getUsageCount: function(style) {
                    return VBPGlobalStyles.countUsage(style.id);
                },

                /**
                 * Obtener strings de configuración
                 */
                getStrings: function() {
                    if (window.VBP_Config && window.VBP_Config.globalStyles && window.VBP_Config.globalStyles.strings) {
                        return window.VBP_Config.globalStyles.strings;
                    }
                    return {
                        panelTitle: 'Estilos Globales',
                        createNew: 'Crear estilo',
                        styleSaved: 'Estilo guardado',
                        styleDeleted: 'Estilo eliminado',
                        styleApplied: 'Estilo aplicado',
                        confirmDelete: '¿Eliminar este estilo?'
                    };
                },

                /**
                 * Mostrar toast de notificación
                 */
                showToast: function(message, type) {
                    if (window.VBPToast && typeof VBPToast.show === 'function') {
                        VBPToast.show(message, type);
                    } else {
                        console.log('[GlobalStyles]', type + ':', message);
                    }
                },

                /**
                 * Toggle panel de categoría
                 */
                toggleCategory: function(categoryId) {
                    // Toggle collapsed state en UI
                    var categoryElement = document.querySelector('[data-category="' + categoryId + '"]');
                    if (categoryElement) {
                        categoryElement.classList.toggle('collapsed');
                    }
                },

                /**
                 * Actualizar propiedad de estilo en formulario
                 */
                updateStyleProperty: function(property, value) {
                    this.formData.styles[property] = value;
                },

                /**
                 * Obtener valor de propiedad del formulario
                 */
                getStyleProperty: function(property, defaultValue) {
                    return this.formData.styles[property] || defaultValue || '';
                }
            };
        });

        /**
         * Componente Alpine para el selector de Global Style en el inspector
         */
        Alpine.data('vbpGlobalStyleSelector', function() {
            return {
                isOpen: false,
                styles: [],
                categories: [],
                searchQuery: '',
                isLoading: false,

                /**
                 * Inicialización
                 */
                init: function() {
                    var self = this;

                    // Cargar estilos cuando se abre
                    this.$watch('isOpen', function(value) {
                        if (value && self.styles.length === 0) {
                            self.loadStyles();
                        }
                    });
                },

                /**
                 * Cargar estilos
                 */
                loadStyles: function() {
                    var self = this;
                    this.isLoading = true;

                    if (window.VBPGlobalStyles) {
                        VBPGlobalStyles.getAll().then(function(styles) {
                            self.styles = styles;
                            self.isLoading = false;
                        });

                        VBPGlobalStyles.getCategories().then(function(categories) {
                            self.categories = categories;
                        });
                    }
                },

                /**
                 * Toggle dropdown
                 */
                toggle: function() {
                    this.isOpen = !this.isOpen;
                },

                /**
                 * Cerrar dropdown
                 */
                close: function() {
                    this.isOpen = false;
                    this.searchQuery = '';
                },

                /**
                 * Obtener elemento seleccionado actual
                 */
                get selectedElement() {
                    var store = Alpine.store('vbp');
                    if (store && store.selection && store.selection.elementIds.length === 1) {
                        return store.getElementDeep(store.selection.elementIds[0]);
                    }
                    return null;
                },

                /**
                 * Obtener estilo global aplicado al elemento actual
                 */
                get currentGlobalStyle() {
                    var element = this.selectedElement;
                    if (!element || !element.globalStyleId) {
                        return null;
                    }

                    var styleId = element.globalStyleId;
                    return this.styles.find(function(style) {
                        return style.id === styleId;
                    }) || null;
                },

                /**
                 * Verificar si el elemento tiene un estilo global
                 */
                get hasGlobalStyle() {
                    return this.selectedElement && !!this.selectedElement.globalStyleId;
                },

                /**
                 * Verificar si el elemento tiene overrides locales
                 */
                get hasOverrides() {
                    var element = this.selectedElement;
                    return element && element.globalStyleId &&
                           element.localStyleOverrides &&
                           Object.keys(element.localStyleOverrides).length > 0;
                },

                /**
                 * Estilos filtrados por búsqueda
                 */
                get filteredStyles() {
                    var query = this.searchQuery.toLowerCase();
                    if (!query) {
                        return this.styles;
                    }

                    return this.styles.filter(function(style) {
                        return style.name.toLowerCase().includes(query) ||
                               (style.description && style.description.toLowerCase().includes(query));
                    });
                },

                /**
                 * Aplicar estilo al elemento
                 */
                applyStyle: function(style) {
                    var element = this.selectedElement;
                    if (!element) {
                        return;
                    }

                    VBPGlobalStyles.applyToElement(element.id, style.id);
                    this.close();
                },

                /**
                 * Quitar estilo global del elemento
                 */
                detachStyle: function(keepStyles) {
                    var element = this.selectedElement;
                    if (!element) {
                        return;
                    }

                    VBPGlobalStyles.detachFromElement(element.id, keepStyles);
                    this.close();
                },

                /**
                 * Resetear overrides locales
                 */
                resetOverrides: function() {
                    var element = this.selectedElement;
                    if (!element) {
                        return;
                    }

                    VBPGlobalStyles.resetLocalOverrides(element.id);
                },

                /**
                 * Obtener nombre de categoría
                 */
                getCategoryName: function(categoryId) {
                    var category = this.categories.find(function(cat) {
                        return cat.id === categoryId;
                    });
                    return category ? category.name : categoryId;
                }
            };
        });

    });

    // Logging
    if (window.vbpLog) {
        vbpLog.log('Global Styles Panel: Componentes Alpine registrados');
    }

})();
