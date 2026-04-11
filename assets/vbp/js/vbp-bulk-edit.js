/**
 * VBP Bulk Edit - Edicion de propiedades en multiples elementos
 *
 * Permite editar propiedades comunes cuando hay varios elementos seleccionados.
 * Muestra valores mixtos cuando difieren y aplica cambios a todos.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.2.5
 */
(function() {
    'use strict';

    // Valor especial para indicar valores mixtos
    var MIXED_VALUE_SENTINEL = '__VBP_MIXED__';

    /**
     * Modulo principal de Bulk Edit
     */
    window.VBPBulkEdit = {
        MIXED_VALUE: MIXED_VALUE_SENTINEL,

        /** @type {boolean} Indica si el modo bulk esta activo */
        isActive: false,

        /** @type {string[]} IDs de elementos seleccionados */
        selectedIds: [],

        /** @type {Object} Cache de valores comunes calculados */
        commonValuesCache: null,

        /**
         * Lista de propiedades de estilo soportadas para bulk edit
         * Formato: path completo desde el objeto styles
         */
        supportedStylePaths: [
            'colors.background',
            'colors.text',
            'typography.fontSize',
            'typography.fontWeight',
            'typography.fontFamily',
            'typography.textAlign',
            'typography.lineHeight',
            'typography.letterSpacing',
            'spacing.padding.top',
            'spacing.padding.right',
            'spacing.padding.bottom',
            'spacing.padding.left',
            'spacing.margin.top',
            'spacing.margin.right',
            'spacing.margin.bottom',
            'spacing.margin.left',
            'borders.radius',
            'borders.width',
            'borders.color',
            'borders.style',
            'shadows.boxShadow',
            'layout.display',
            'layout.flexDirection',
            'layout.justifyContent',
            'layout.alignItems',
            'layout.gap',
            'advanced.cssClasses'
        ],

        /**
         * Inicializar el modulo
         */
        init: function() {
            this.bindEvents();
            this.extendInspector();

            if (window.vbpLog) {
                window.vbpLog.log('VBPBulkEdit: Modulo inicializado');
            }
        },

        /**
         * Vincular eventos del DOM y del store
         */
        bindEvents: function() {
            var self = this;

            // Escuchar cambios de seleccion desde el store
            document.addEventListener('alpine:init', function() {
                // Esperar a que Alpine este listo
                requestAnimationFrame(function() {
                    self.watchStoreSelection();
                });
            });

            // Si Alpine ya esta inicializado
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                this.watchStoreSelection();
            }

            // Interceptar cambios en inputs del inspector cuando esta en bulk mode
            document.addEventListener('input', function(event) {
                self.handleInspectorInput(event);
            }, true);

            document.addEventListener('change', function(event) {
                self.handleInspectorChange(event);
            }, true);
        },

        /**
         * Observar cambios en la seleccion del store
         */
        watchStoreSelection: function() {
            var self = this;

            // Verificar periodicamente si hay cambios en la seleccion
            var checkSelectionInterval = setInterval(function() {
                var store = Alpine.store('vbp');
                if (!store) return;

                var currentIds = store.selection && store.selection.elementIds
                    ? store.selection.elementIds.slice()
                    : [];

                // Verificar si cambio la seleccion
                var hasChanged = currentIds.length !== self.selectedIds.length ||
                    currentIds.some(function(id, index) {
                        return id !== self.selectedIds[index];
                    });

                if (hasChanged) {
                    self.selectedIds = currentIds;
                    self.commonValuesCache = null;

                    if (currentIds.length > 1) {
                        self.enableBulkMode(currentIds);
                    } else {
                        self.disableBulkMode();
                    }

                    // Emitir evento de cambio de seleccion
                    document.dispatchEvent(new CustomEvent('vbp:selection-changed', {
                        detail: { elementIds: currentIds }
                    }));
                }
            }, 100);

            // Limpiar intervalo cuando se destruya la pagina
            window.addEventListener('beforeunload', function() {
                clearInterval(checkSelectionInterval);
            });
        },

        /**
         * Extender el inspector para soportar bulk edit
         */
        extendInspector: function() {
            var self = this;

            // Hook cuando cambia la seleccion
            document.addEventListener('vbp:selection-changed', function(event) {
                var elementIds = event.detail.elementIds || [];

                if (elementIds.length > 1) {
                    self.enableBulkMode(elementIds);
                } else {
                    self.disableBulkMode();
                }
            });
        },

        /**
         * Habilitar modo bulk edit
         * @param {string[]} elementIds - IDs de elementos seleccionados
         */
        enableBulkMode: function(elementIds) {
            this.isActive = true;
            this.selectedIds = elementIds;
            this.commonValuesCache = null;

            var inspector = document.querySelector('.vbp-inspector');
            if (!inspector) return;

            // Agregar clase de bulk mode
            inspector.classList.add('vbp-bulk-mode');

            // Mostrar indicador
            this.showBulkIndicator(elementIds.length);

            // Calcular valores comunes
            var commonValues = this.getCommonValues(elementIds);

            // Actualizar campos del inspector existentes
            this.updateInspectorFields(commonValues);

            // Inyectar panel de bulk edit con delay para que Alpine actualice el DOM
            var self = this;
            setTimeout(function() {
                self.injectBulkEditPanel();
            }, 100);

            if (window.vbpLog) {
                window.vbpLog.log('VBPBulkEdit: Modo bulk activado para', elementIds.length, 'elementos');
            }
        },

        /**
         * Deshabilitar modo bulk edit
         */
        disableBulkMode: function() {
            if (!this.isActive) return;

            this.isActive = false;
            this.selectedIds = [];
            this.commonValuesCache = null;

            var inspector = document.querySelector('.vbp-inspector');
            if (inspector) {
                inspector.classList.remove('vbp-bulk-mode');
            }

            this.hideBulkIndicator();
            this.clearMixedValueStyles();
            this.removeBulkEditPanel();

            if (window.vbpLog) {
                window.vbpLog.log('VBPBulkEdit: Modo bulk desactivado');
            }
        },

        /**
         * Mostrar indicador visual de bulk edit
         * @param {number} count - Numero de elementos seleccionados
         */
        showBulkIndicator: function(count) {
            // Remover indicador existente
            var existing = document.querySelector('.vbp-bulk-indicator');
            if (existing) {
                existing.remove();
            }

            // Crear nuevo indicador
            var indicator = document.createElement('div');
            indicator.className = 'vbp-bulk-indicator';
            indicator.innerHTML =
                '<span class="vbp-bulk-indicator__icon">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                        '<rect x="3" y="3" width="7" height="7"/>' +
                        '<rect x="14" y="3" width="7" height="7"/>' +
                        '<rect x="3" y="14" width="7" height="7"/>' +
                        '<rect x="14" y="14" width="7" height="7"/>' +
                    '</svg>' +
                '</span>' +
                '<span class="vbp-bulk-indicator__text">Editando ' + count + ' elementos</span>' +
                '<button type="button" class="vbp-bulk-indicator__clear" title="Limpiar seleccion">' +
                    '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                        '<line x1="18" y1="6" x2="6" y2="18"/>' +
                        '<line x1="6" y1="6" x2="18" y2="18"/>' +
                    '</svg>' +
                '</button>';

            // Agregar handler para limpiar seleccion
            var clearButton = indicator.querySelector('.vbp-bulk-indicator__clear');
            if (clearButton) {
                clearButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var store = Alpine.store('vbp');
                    if (store && typeof store.clearSelection === 'function') {
                        store.clearSelection();
                    }
                });
            }

            // Insertar despues del header del inspector
            var inspectorHeader = document.querySelector('.vbp-inspector-header');
            if (inspectorHeader) {
                inspectorHeader.insertAdjacentElement('afterend', indicator);
            } else {
                // Fallback: insertar al inicio del inspector
                var inspector = document.querySelector('.vbp-inspector');
                if (inspector) {
                    inspector.insertAdjacentElement('afterbegin', indicator);
                }
            }
        },

        /**
         * Ocultar indicador de bulk edit
         */
        hideBulkIndicator: function() {
            var indicator = document.querySelector('.vbp-bulk-indicator');
            if (indicator) {
                indicator.remove();
            }
        },

        /**
         * Limpiar estilos de valores mixtos de los campos
         */
        clearMixedValueStyles: function() {
            var mixedFields = document.querySelectorAll('.vbp-mixed-value');
            mixedFields.forEach(function(field) {
                field.classList.remove('vbp-mixed-value');
                if (field.tagName === 'INPUT') {
                    field.placeholder = field.dataset.originalPlaceholder || '';
                }
            });

            // Remover opciones mixed de selects
            var mixedOptions = document.querySelectorAll('option[value="__mixed__"]');
            mixedOptions.forEach(function(option) {
                option.remove();
            });
        },

        /**
         * Obtener valores comunes entre elementos seleccionados
         * @param {string[]} elementIds - IDs de elementos
         * @returns {Object} Mapa de path -> valor (o MIXED_VALUE)
         */
        getCommonValues: function(elementIds) {
            // Usar cache si esta disponible
            if (this.commonValuesCache) {
                return this.commonValuesCache;
            }

            var store = Alpine.store('vbp');
            if (!store) return {};

            var self = this;

            // Obtener elementos
            var elements = elementIds.map(function(id) {
                return store.getElementDeep ? store.getElementDeep(id) : store.getElement(id);
            }).filter(Boolean);

            if (elements.length === 0) return {};

            var commonValues = {};

            // Comparar cada propiedad soportada
            this.supportedStylePaths.forEach(function(propPath) {
                var values = elements.map(function(element) {
                    return self.getNestedValue(element, 'styles.' + propPath);
                });

                // Verificar si todos los valores son iguales
                var firstValue = values[0];
                var allEqual = values.every(function(value) {
                    // Comparar de forma segura (considerando undefined y null)
                    if (value === undefined || value === null || value === '') {
                        return firstValue === undefined || firstValue === null || firstValue === '';
                    }
                    return value === firstValue;
                });

                commonValues[propPath] = allEqual ? firstValue : self.MIXED_VALUE;
            });

            // Guardar en cache
            this.commonValuesCache = commonValues;

            return commonValues;
        },

        /**
         * Obtener valor anidado de un objeto usando path con puntos
         * @param {Object} obj - Objeto fuente
         * @param {string} path - Path con puntos (ej: 'styles.colors.background')
         * @returns {*} Valor encontrado o undefined
         */
        getNestedValue: function(obj, path) {
            if (!obj || !path) return undefined;

            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length; i++) {
                if (current === undefined || current === null) {
                    return undefined;
                }
                current = current[parts[i]];
            }

            return current;
        },

        /**
         * Establecer valor anidado en un objeto usando path con puntos
         * @param {Object} obj - Objeto destino
         * @param {string} path - Path con puntos
         * @param {*} value - Valor a establecer
         */
        setNestedValue: function(obj, path, value) {
            if (!obj || !path) return;

            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]] || typeof current[parts[i]] !== 'object') {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }

            current[parts[parts.length - 1]] = value;
        },

        /**
         * Actualizar campos del inspector con valores comunes
         * @param {Object} commonValues - Mapa de path -> valor
         */
        updateInspectorFields: function(commonValues) {
            var self = this;

            Object.keys(commonValues).forEach(function(propPath) {
                var value = commonValues[propPath];

                // Buscar el campo correspondiente en el inspector
                // Los campos pueden tener data-prop-path o name con formato transformado
                var fieldName = propPath.replace(/\./g, '-');
                var selectors = [
                    '[data-prop-path="' + propPath + '"]',
                    '[data-prop-path="styles.' + propPath + '"]',
                    '[name="' + fieldName + '"]',
                    '[data-style-path="' + propPath + '"]'
                ];

                var field = null;
                for (var i = 0; i < selectors.length && !field; i++) {
                    field = document.querySelector('.vbp-inspector ' + selectors[i]);
                }

                if (!field) return;

                if (value === self.MIXED_VALUE) {
                    // Valor mixto - mostrar indicador
                    self.markFieldAsMixed(field);
                } else {
                    // Valor comun - mostrar el valor
                    self.setFieldValue(field, value);
                    field.classList.remove('vbp-mixed-value');
                }
            });
        },

        /**
         * Marcar un campo como valor mixto
         * @param {HTMLElement} field - Campo del formulario
         */
        markFieldAsMixed: function(field) {
            field.classList.add('vbp-mixed-value');

            if (field.tagName === 'INPUT') {
                // Guardar placeholder original
                if (!field.dataset.originalPlaceholder) {
                    field.dataset.originalPlaceholder = field.placeholder || '';
                }
                field.placeholder = 'Mixed';
                field.value = '';
            } else if (field.tagName === 'SELECT') {
                // Agregar opcion Mixed si no existe
                var mixedOption = field.querySelector('option[value="__mixed__"]');
                if (!mixedOption) {
                    mixedOption = document.createElement('option');
                    mixedOption.value = '__mixed__';
                    mixedOption.textContent = '-- Mixed --';
                    mixedOption.disabled = true;
                    mixedOption.selected = true;
                    field.insertBefore(mixedOption, field.firstChild);
                } else {
                    mixedOption.selected = true;
                }
            }
        },

        /**
         * Establecer valor en un campo
         * @param {HTMLElement} field - Campo del formulario
         * @param {*} value - Valor a establecer
         */
        setFieldValue: function(field, value) {
            var normalizedValue = value === undefined || value === null ? '' : value;

            if (field.tagName === 'INPUT') {
                if (field.type === 'checkbox') {
                    field.checked = !!normalizedValue;
                } else {
                    field.value = normalizedValue;
                }
            } else if (field.tagName === 'SELECT') {
                // Remover opcion mixed si existe
                var mixedOption = field.querySelector('option[value="__mixed__"]');
                if (mixedOption) {
                    mixedOption.remove();
                }
                field.value = normalizedValue;
            } else if (field.tagName === 'TEXTAREA') {
                field.value = normalizedValue;
            }
        },

        /**
         * Manejar input en campos del inspector durante bulk mode
         * @param {Event} event - Evento de input
         */
        handleInspectorInput: function(event) {
            if (!this.isActive) return;

            var field = event.target;
            if (!field.closest('.vbp-inspector.vbp-bulk-mode')) return;

            // Obtener el path de la propiedad
            var propPath = this.getFieldPropPath(field);
            if (!propPath) return;

            // Aplicar cambio con debounce
            this.debouncedApplyChange(propPath, field.value);
        },

        /**
         * Manejar change en campos del inspector durante bulk mode
         * @param {Event} event - Evento de change
         */
        handleInspectorChange: function(event) {
            if (!this.isActive) return;

            var field = event.target;
            if (!field.closest('.vbp-inspector.vbp-bulk-mode')) return;

            // Ignorar si se selecciono la opcion mixed
            if (field.value === '__mixed__') return;

            var propPath = this.getFieldPropPath(field);
            if (!propPath) return;

            // Aplicar cambio inmediatamente
            this.applyBulkChange(propPath, field.value);
        },

        /**
         * Obtener el path de propiedad de un campo
         * @param {HTMLElement} field - Campo del formulario
         * @returns {string|null} Path de la propiedad o null
         */
        getFieldPropPath: function(field) {
            // Intentar obtener de data attributes
            if (field.dataset.propPath) {
                return field.dataset.propPath.replace(/^styles\./, '');
            }
            if (field.dataset.stylePath) {
                return field.dataset.stylePath;
            }

            // Convertir name a path
            if (field.name) {
                return field.name.replace(/-/g, '.');
            }

            return null;
        },

        /** @type {Object} Timers para debounce */
        debounceTimers: {},

        /**
         * Aplicar cambio con debounce
         * @param {string} propPath - Path de la propiedad
         * @param {*} value - Nuevo valor
         */
        debouncedApplyChange: function(propPath, value) {
            var self = this;

            // Cancelar timer anterior
            if (this.debounceTimers[propPath]) {
                clearTimeout(this.debounceTimers[propPath]);
            }

            // Crear nuevo timer
            this.debounceTimers[propPath] = setTimeout(function() {
                self.applyBulkChange(propPath, value);
                delete self.debounceTimers[propPath];
            }, 150);
        },

        /**
         * Aplicar cambio a todos los elementos seleccionados
         * @param {string} propPath - Path de la propiedad (sin prefijo 'styles.')
         * @param {*} value - Nuevo valor
         */
        applyBulkChange: function(propPath, value) {
            var store = Alpine.store('vbp');
            if (!store || this.selectedIds.length < 2) return;

            var self = this;
            var fullPath = 'styles.' + propPath;

            // Guardar estado en historial
            store.saveToHistory('Bulk edit: ' + propPath);

            // Aplicar a cada elemento
            this.selectedIds.forEach(function(elementId) {
                var element = store.getElementDeep ? store.getElementDeep(elementId) : store.getElement(elementId);
                if (!element) return;

                // Clonar estilos actuales
                var newStyles = JSON.parse(JSON.stringify(element.styles || {}));

                // Establecer el nuevo valor
                self.setNestedValue(newStyles, propPath, value);

                // Actualizar elemento
                store.updateElement(elementId, { styles: newStyles });
            });

            // Invalidar cache
            this.commonValuesCache = null;

            // Marcar como dirty
            if (typeof store.markAsDirty === 'function') {
                store.markAsDirty();
            }

            // Notificar cambio
            document.dispatchEvent(new CustomEvent('vbp:bulk-edit:applied', {
                detail: {
                    propPath: propPath,
                    value: value,
                    elementIds: this.selectedIds.slice()
                }
            }));

            // Mostrar notificacion
            if (typeof store.addNotification === 'function') {
                store.addNotification('Cambio aplicado a ' + this.selectedIds.length + ' elementos', 'success');
            } else if (window.VBPToast) {
                window.VBPToast.success('Cambio aplicado a ' + this.selectedIds.length + ' elementos');
            }

            if (window.vbpLog) {
                window.vbpLog.log('VBPBulkEdit: Aplicado', propPath, '=', value, 'a', this.selectedIds.length, 'elementos');
            }
        },

        /**
         * Verificar si el modo bulk esta activo
         * @returns {boolean}
         */
        isBulkModeActive: function() {
            return this.isActive && this.selectedIds.length > 1;
        },

        /**
         * Obtener numero de elementos seleccionados
         * @returns {number}
         */
        getSelectedCount: function() {
            return this.selectedIds.length;
        },

        /**
         * Inyectar panel de edicion masiva en el inspector
         * Se llama cuando hay seleccion multiple
         */
        injectBulkEditPanel: function() {
            var self = this;
            var inspectorMulti = document.querySelector('.vbp-inspector-empty--multi');
            if (!inspectorMulti) return;

            // Verificar si ya esta inyectado
            if (document.querySelector('.vbp-bulk-edit-panel')) return;

            var commonValues = this.getCommonValues(this.selectedIds);

            // Crear panel de edicion masiva
            var bulkPanel = document.createElement('div');
            bulkPanel.className = 'vbp-bulk-edit-panel';
            bulkPanel.innerHTML = this.generateBulkEditPanelHTML(commonValues);

            // Insertar despues del mensaje original
            inspectorMulti.insertAdjacentElement('afterend', bulkPanel);

            // Bind eventos del panel
            this.bindBulkPanelEvents(bulkPanel);
        },

        /**
         * Generar HTML del panel de edicion masiva
         * @param {Object} commonValues - Valores comunes
         * @returns {string} HTML del panel
         */
        generateBulkEditPanelHTML: function(commonValues) {
            var self = this;
            var sections = [
                {
                    title: 'Colores',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>',
                    fields: [
                        { path: 'colors.background', label: 'Fondo', type: 'color' },
                        { path: 'colors.text', label: 'Texto', type: 'color' }
                    ]
                },
                {
                    title: 'Tipografia',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
                    fields: [
                        { path: 'typography.fontSize', label: 'Tamano', type: 'text', placeholder: 'ej: 16px' },
                        { path: 'typography.fontWeight', label: 'Peso', type: 'select', options: [
                            { value: '', label: '-- Sin cambio --' },
                            { value: '300', label: 'Light (300)' },
                            { value: '400', label: 'Normal (400)' },
                            { value: '500', label: 'Medium (500)' },
                            { value: '600', label: 'Semibold (600)' },
                            { value: '700', label: 'Bold (700)' }
                        ]},
                        { path: 'typography.textAlign', label: 'Alineacion', type: 'select', options: [
                            { value: '', label: '-- Sin cambio --' },
                            { value: 'left', label: 'Izquierda' },
                            { value: 'center', label: 'Centro' },
                            { value: 'right', label: 'Derecha' }
                        ]}
                    ]
                },
                {
                    title: 'Espaciado',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M15 3v18M3 9h18M3 15h18"/></svg>',
                    fields: [
                        { path: 'spacing.padding.top', label: 'Padding Top', type: 'text', placeholder: 'ej: 20px' },
                        { path: 'spacing.padding.bottom', label: 'Padding Bottom', type: 'text', placeholder: 'ej: 20px' },
                        { path: 'spacing.margin.top', label: 'Margin Top', type: 'text', placeholder: 'ej: 10px' },
                        { path: 'spacing.margin.bottom', label: 'Margin Bottom', type: 'text', placeholder: 'ej: 10px' }
                    ]
                },
                {
                    title: 'Bordes',
                    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                    fields: [
                        { path: 'borders.radius', label: 'Border Radius', type: 'text', placeholder: 'ej: 8px' },
                        { path: 'borders.width', label: 'Border Width', type: 'text', placeholder: 'ej: 1px' },
                        { path: 'borders.color', label: 'Border Color', type: 'color' }
                    ]
                }
            ];

            var html = '<div class="vbp-bulk-edit-panel__header">';
            html += '<span class="vbp-bulk-edit-panel__title">Edicion masiva de estilos</span>';
            html += '<span class="vbp-bulk-edit-panel__hint">Los cambios se aplican a todos los elementos seleccionados</span>';
            html += '</div>';

            sections.forEach(function(section) {
                html += '<div class="vbp-bulk-edit-section">';
                html += '<div class="vbp-bulk-edit-section__header">';
                html += '<span class="vbp-bulk-edit-section__icon">' + section.icon + '</span>';
                html += '<span class="vbp-bulk-edit-section__title">' + section.title + '</span>';
                html += '</div>';
                html += '<div class="vbp-bulk-edit-section__content">';

                section.fields.forEach(function(field) {
                    var currentValue = commonValues[field.path];
                    var isMixed = currentValue === self.MIXED_VALUE;
                    var displayValue = isMixed ? '' : (currentValue || '');

                    html += '<div class="vbp-bulk-field">';
                    html += '<label class="vbp-bulk-field__label">' + field.label + '</label>';

                    if (field.type === 'color') {
                        html += '<div class="vbp-bulk-field__color-wrapper' + (isMixed ? ' vbp-mixed-value' : '') + '">';
                        html += '<input type="color" data-bulk-path="' + field.path + '" value="' + (displayValue || '#ffffff') + '" class="vbp-bulk-field__color">';
                        html += '<input type="text" data-bulk-path="' + field.path + '" value="' + displayValue + '" placeholder="' + (isMixed ? 'Mixed' : '#000000') + '" class="vbp-bulk-field__input vbp-bulk-field__input--color">';
                        html += '</div>';
                    } else if (field.type === 'select') {
                        html += '<select data-bulk-path="' + field.path + '" class="vbp-bulk-field__select' + (isMixed ? ' vbp-mixed-value' : '') + '">';
                        if (isMixed) {
                            html += '<option value="__mixed__" selected disabled>-- Mixed --</option>';
                        }
                        field.options.forEach(function(opt) {
                            var selected = !isMixed && displayValue === opt.value ? ' selected' : '';
                            html += '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
                        });
                        html += '</select>';
                    } else {
                        html += '<input type="text" data-bulk-path="' + field.path + '" value="' + displayValue + '" placeholder="' + (isMixed ? 'Mixed' : (field.placeholder || '')) + '" class="vbp-bulk-field__input' + (isMixed ? ' vbp-mixed-value' : '') + '">';
                    }

                    html += '</div>';
                });

                html += '</div>';
                html += '</div>';
            });

            return html;
        },

        /**
         * Bind eventos del panel de bulk edit
         * @param {HTMLElement} panel - Panel DOM
         */
        bindBulkPanelEvents: function(panel) {
            var self = this;

            // Inputs de texto
            panel.querySelectorAll('input[data-bulk-path]').forEach(function(input) {
                input.addEventListener('input', function(event) {
                    var path = event.target.dataset.bulkPath;
                    var value = event.target.value;

                    // Si es un input de color, sincronizar con el color picker
                    if (input.classList.contains('vbp-bulk-field__input--color')) {
                        var colorPicker = input.parentElement.querySelector('input[type="color"]');
                        if (colorPicker && value.match(/^#[0-9A-Fa-f]{6}$/)) {
                            colorPicker.value = value;
                        }
                    }

                    self.debouncedApplyChange(path, value);
                });

                input.addEventListener('change', function(event) {
                    var path = event.target.dataset.bulkPath;
                    self.applyBulkChange(path, event.target.value);
                });
            });

            // Color pickers
            panel.querySelectorAll('input[type="color"][data-bulk-path]').forEach(function(colorPicker) {
                colorPicker.addEventListener('input', function(event) {
                    var path = event.target.dataset.bulkPath;
                    var value = event.target.value;

                    // Sincronizar con el input de texto
                    var textInput = colorPicker.parentElement.querySelector('.vbp-bulk-field__input--color');
                    if (textInput) {
                        textInput.value = value;
                    }

                    self.debouncedApplyChange(path, value);
                });
            });

            // Selects
            panel.querySelectorAll('select[data-bulk-path]').forEach(function(select) {
                select.addEventListener('change', function(event) {
                    if (event.target.value === '__mixed__') return;
                    var path = event.target.dataset.bulkPath;
                    self.applyBulkChange(path, event.target.value);
                });
            });
        },

        /**
         * Remover panel de bulk edit
         */
        removeBulkEditPanel: function() {
            var panel = document.querySelector('.vbp-bulk-edit-panel');
            if (panel) {
                panel.remove();
            }
        }
    };

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPBulkEdit.init();
        });
    } else {
        window.VBPBulkEdit.init();
    }
})();
