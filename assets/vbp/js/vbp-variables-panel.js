/**
 * Visual Builder Pro - Panel de Variables
 * Interfaz para gestionar variables, bindings y lógica
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP Variables Panel]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP Variables Panel]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP Variables Panel]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Componente Panel de Variables
     */
    function vbpVariablesPanel() {
        return {
            // Estado del panel
            isOpen: false,
            activeTab: 'variables', // variables, collections, bindings, actions, debug
            searchQuery: '',
            filterScope: 'all',

            // Modal de creación/edición
            showCreateModal: false,
            editingVariable: null,
            variableForm: {
                name: '',
                type: 'string',
                value: '',
                scope: 'page',
                description: ''
            },

            // Modal de colección
            showCollectionModal: false,
            editingCollection: null,
            collectionForm: {
                name: '',
                schema: {},
                data: [],
                source: 'local'
            },

            // Modal de binding
            showBindingModal: false,
            bindingForm: {
                elementId: '',
                property: '',
                type: 'variable',
                source: '',
                transform: ''
            },

            // Modal de acción
            showActionModal: false,
            editingAction: null,
            actionForm: {
                elementId: '',
                event: 'click',
                actions: []
            },

            // Debug
            debugMode: false,
            debugLog: [],
            maxDebugEntries: 100,

            // ============================================
            // GETTERS
            // ============================================

            get variables() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return [];
                return store.logic.listVariables();
            },

            get filteredVariables() {
                var self = this;
                var variables = this.variables;

                // Filtrar por scope
                if (this.filterScope !== 'all') {
                    variables = variables.filter(function(variableItem) {
                        return variableItem.scope === self.filterScope;
                    });
                }

                // Filtrar por búsqueda
                if (this.searchQuery) {
                    var query = this.searchQuery.toLowerCase();
                    variables = variables.filter(function(variableItem) {
                        return variableItem.name.toLowerCase().indexOf(query) !== -1 ||
                               (variableItem.description && variableItem.description.toLowerCase().indexOf(query) !== -1);
                    });
                }

                return variables;
            },

            get collections() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return [];
                return store.logic.listCollections();
            },

            get variableTypes() {
                return window.VBP_VARIABLE_TYPES || {};
            },

            get variableScopes() {
                return window.VBP_VARIABLE_SCOPES || {};
            },

            get transforms() {
                return window.VBP_TRANSFORMS || {};
            },

            get actionTypes() {
                return window.VBP_ACTION_TYPES || {};
            },

            get operators() {
                return window.VBP_COMPARISON_OPERATORS || {};
            },

            get selectedElement() {
                var store = Alpine.store('vbp');
                return store ? store.selectedElement : null;
            },

            get elementBindings() {
                if (!this.selectedElement) return {};
                return window.VBPBindings ? window.VBPBindings.getBindings(this.selectedElement.id) : {};
            },

            get elementEvents() {
                if (!this.selectedElement) return {};
                return window.VBPActions ? window.VBPActions.getEvents(this.selectedElement.id) : {};
            },

            get hasElementLoop() {
                if (!this.selectedElement) return false;
                return window.VBPLoops ? window.VBPLoops.hasLoop(this.selectedElement.id) : false;
            },

            get elementLoop() {
                if (!this.selectedElement) return null;
                return window.VBPLoops ? window.VBPLoops.getConfig(this.selectedElement.id) : null;
            },

            // ============================================
            // MÉTODOS DE INICIALIZACIÓN
            // ============================================

            init: function() {
                var self = this;

                // Escuchar cambios de variables para debug
                if (window.VBPVariables) {
                    window.VBPVariables.watchAll(function(variableId, newValue, oldValue) {
                        if (self.debugMode) {
                            self.addDebugEntry({
                                type: 'variable_change',
                                variableId: variableId,
                                oldValue: oldValue,
                                newValue: newValue,
                                timestamp: new Date()
                            });
                        }
                    });
                }

                vbpLog.log('Panel de variables inicializado');
            },

            toggle: function() {
                this.isOpen = !this.isOpen;
            },

            open: function() {
                this.isOpen = true;
            },

            close: function() {
                this.isOpen = false;
            },

            setTab: function(tab) {
                this.activeTab = tab;
            },

            // ============================================
            // GESTIÓN DE VARIABLES
            // ============================================

            openCreateVariableModal: function() {
                this.editingVariable = null;
                this.variableForm = {
                    name: '',
                    type: 'string',
                    value: '',
                    scope: 'page',
                    description: ''
                };
                this.showCreateModal = true;
            },

            openEditVariableModal: function(variableItem) {
                this.editingVariable = variableItem;
                this.variableForm = {
                    name: variableItem.name,
                    type: variableItem.type,
                    value: this.formatValueForEdit(variableItem.value, variableItem.type),
                    scope: variableItem.scope,
                    description: variableItem.description || ''
                };
                this.showCreateModal = true;
            },

            closeVariableModal: function() {
                this.showCreateModal = false;
                this.editingVariable = null;
            },

            saveVariable: function() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return;

                var parsedValue = this.parseValueFromEdit(this.variableForm.value, this.variableForm.type);

                if (this.editingVariable) {
                    // Actualizar existente
                    store.logic.setVariable(this.editingVariable.id, parsedValue);
                    // Actualizar descripción si cambió
                    var varObj = store.logic.variables.getVariableObject(this.editingVariable.id);
                    if (varObj) {
                        varObj.description = this.variableForm.description;
                    }
                } else {
                    // Crear nueva
                    store.logic.createVariable({
                        name: this.variableForm.name,
                        type: this.variableForm.type,
                        value: parsedValue,
                        scope: this.variableForm.scope,
                        description: this.variableForm.description
                    });
                }

                this.closeVariableModal();
            },

            deleteVariable: function(variableItem) {
                if (!confirm('¿Eliminar la variable "' + variableItem.name + '"?')) return;

                var store = Alpine.store('vbp');
                if (store && store.logic) {
                    store.logic.deleteVariable(variableItem.id);
                }
            },

            formatValueForEdit: function(value, type) {
                switch (type) {
                    case 'array':
                    case 'object':
                        return JSON.stringify(value, null, 2);
                    case 'date':
                        if (value instanceof Date) {
                            return value.toISOString().split('T')[0];
                        }
                        return value || '';
                    default:
                        return value;
                }
            },

            parseValueFromEdit: function(value, type) {
                switch (type) {
                    case 'number':
                        return Number(value) || 0;
                    case 'boolean':
                        return value === true || value === 'true';
                    case 'array':
                    case 'object':
                        try {
                            return JSON.parse(value);
                        } catch (e) {
                            return type === 'array' ? [] : {};
                        }
                    case 'date':
                        return value ? new Date(value) : null;
                    default:
                        return value;
                }
            },

            formatValueDisplay: function(value, type) {
                if (value === null || value === undefined) return '(vacío)';

                switch (type) {
                    case 'boolean':
                        return value ? 'Verdadero' : 'Falso';
                    case 'array':
                        return '[' + value.length + ' elementos]';
                    case 'object':
                        return '{' + Object.keys(value).length + ' propiedades}';
                    case 'date':
                        if (value instanceof Date) {
                            return value.toLocaleDateString();
                        }
                        return value;
                    case 'color':
                        return value;
                    default:
                        var str = String(value);
                        return str.length > 30 ? str.substr(0, 30) + '...' : str;
                }
            },

            getTypeIcon: function(type) {
                var typeInfo = this.variableTypes[type];
                return typeInfo ? typeInfo.icon : '?';
            },

            getTypeLabel: function(type) {
                var typeInfo = this.variableTypes[type];
                return typeInfo ? typeInfo.label : type;
            },

            getScopeLabel: function(scope) {
                var scopeInfo = this.variableScopes[scope];
                return scopeInfo ? scopeInfo.label : scope;
            },

            copyVariableName: function(variableItem) {
                var expression = '{{' + variableItem.name + '}}';
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(expression);
                }
            },

            // ============================================
            // GESTIÓN DE COLECCIONES
            // ============================================

            openCreateCollectionModal: function() {
                this.editingCollection = null;
                this.collectionForm = {
                    name: '',
                    schema: {},
                    data: [],
                    source: 'local'
                };
                this.showCollectionModal = true;
            },

            openEditCollectionModal: function(collection) {
                this.editingCollection = collection;
                this.collectionForm = {
                    name: collection.name,
                    schema: JSON.parse(JSON.stringify(collection.schema)),
                    data: JSON.parse(JSON.stringify(collection.data)),
                    source: collection.source
                };
                this.showCollectionModal = true;
            },

            closeCollectionModal: function() {
                this.showCollectionModal = false;
                this.editingCollection = null;
            },

            saveCollection: function() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return;

                if (this.editingCollection) {
                    // Actualizar existente
                    store.logic.collections.updateCollectionData(
                        this.editingCollection.id,
                        this.collectionForm.data
                    );
                } else {
                    // Crear nueva
                    store.logic.createCollection({
                        name: this.collectionForm.name,
                        schema: this.collectionForm.schema,
                        data: this.collectionForm.data,
                        source: this.collectionForm.source
                    });
                }

                this.closeCollectionModal();
            },

            addSchemaField: function() {
                var fieldName = prompt('Nombre del campo:');
                if (!fieldName) return;

                var fieldType = prompt('Tipo (string, number, boolean, image):') || 'string';
                this.collectionForm.schema[fieldName] = fieldType;
            },

            removeSchemaField: function(fieldName) {
                delete this.collectionForm.schema[fieldName];
            },

            addCollectionItem: function() {
                var newItem = {};
                for (var field in this.collectionForm.schema) {
                    var defaultValue;
                    switch (this.collectionForm.schema[field]) {
                        case 'number': defaultValue = 0; break;
                        case 'boolean': defaultValue = false; break;
                        default: defaultValue = '';
                    }
                    newItem[field] = defaultValue;
                }
                this.collectionForm.data.push(newItem);
            },

            removeCollectionItem: function(index) {
                this.collectionForm.data.splice(index, 1);
            },

            // ============================================
            // GESTIÓN DE BINDINGS
            // ============================================

            openBindingModal: function(property) {
                if (!this.selectedElement) return;

                var existingBinding = this.elementBindings[property];

                this.bindingForm = {
                    elementId: this.selectedElement.id,
                    property: property || '',
                    type: existingBinding ? existingBinding.type : 'variable',
                    source: existingBinding ? existingBinding.source : '',
                    transform: existingBinding ? existingBinding.transform : ''
                };
                this.showBindingModal = true;
            },

            closeBindingModal: function() {
                this.showBindingModal = false;
            },

            saveBinding: function() {
                if (!window.VBPBindings) return;

                window.VBPBindings.bind(this.bindingForm.elementId, this.bindingForm.property, {
                    type: this.bindingForm.type,
                    variableId: this.bindingForm.type === 'variable' ? this.bindingForm.source : null,
                    expression: this.bindingForm.type === 'expression' ? this.bindingForm.source : null,
                    transform: this.bindingForm.transform || null
                });

                this.closeBindingModal();
            },

            removeBinding: function(property) {
                if (!this.selectedElement || !window.VBPBindings) return;
                window.VBPBindings.unbind(this.selectedElement.id, property);
            },

            getBindableProperties: function() {
                // Lista de propiedades que se pueden vincular
                return [
                    { value: 'visible', label: 'Visible' },
                    { value: 'data.text', label: 'Texto' },
                    { value: 'data.content', label: 'Contenido' },
                    { value: 'data.url', label: 'URL' },
                    { value: 'data.src', label: 'Fuente imagen' },
                    { value: 'styles.colors.background', label: 'Color fondo' },
                    { value: 'styles.colors.text', label: 'Color texto' },
                    { value: 'className', label: 'Clases CSS' }
                ];
            },

            // ============================================
            // GESTIÓN DE CONDICIONES
            // ============================================

            getElementConditions: function() {
                if (!this.selectedElement || !this.selectedElement.conditions) return [];
                return this.selectedElement.conditions;
            },

            addCondition: function() {
                if (!this.selectedElement) return;

                var store = Alpine.store('vbp');
                if (!store) return;

                var conditions = this.selectedElement.conditions || [];
                conditions.push({
                    property: 'visible',
                    when: [
                        { variable: '', operator: 'equals', value: '' }
                    ]
                });

                store.updateElement(this.selectedElement.id, { conditions: conditions });
            },

            updateCondition: function(index, updates) {
                if (!this.selectedElement) return;

                var store = Alpine.store('vbp');
                if (!store) return;

                var conditions = JSON.parse(JSON.stringify(this.selectedElement.conditions || []));
                conditions[index] = Object.assign({}, conditions[index], updates);

                store.updateElement(this.selectedElement.id, { conditions: conditions });
            },

            removeCondition: function(index) {
                if (!this.selectedElement) return;

                var store = Alpine.store('vbp');
                if (!store) return;

                var conditions = JSON.parse(JSON.stringify(this.selectedElement.conditions || []));
                conditions.splice(index, 1);

                store.updateElement(this.selectedElement.id, { conditions: conditions });
            },

            // ============================================
            // GESTIÓN DE LOOPS
            // ============================================

            configureLoop: function() {
                if (!this.selectedElement || !window.VBPLoops) return;

                var collectionName = prompt('Nombre de la colección:');
                if (!collectionName) return;

                var itemVar = prompt('Nombre de variable para item (default: item):') || 'item';
                var indexVar = prompt('Nombre de variable para índice (default: index):') || 'index';

                window.VBPLoops.configure(this.selectedElement.id, {
                    collection: collectionName,
                    itemVariable: itemVar,
                    indexVariable: indexVar
                });
            },

            removeLoop: function() {
                if (!this.selectedElement || !window.VBPLoops) return;
                window.VBPLoops.remove(this.selectedElement.id);
            },

            // ============================================
            // GESTIÓN DE EVENTOS/ACCIONES
            // ============================================

            openActionModal: function(event) {
                if (!this.selectedElement) return;

                var existingActions = this.elementEvents[event] || [];

                this.actionForm = {
                    elementId: this.selectedElement.id,
                    event: event || 'click',
                    actions: JSON.parse(JSON.stringify(existingActions))
                };
                this.showActionModal = true;
            },

            closeActionModal: function() {
                this.showActionModal = false;
            },

            addAction: function() {
                this.actionForm.actions.push({
                    action: 'setVariable',
                    variable: '',
                    value: ''
                });
            },

            updateAction: function(index, updates) {
                this.actionForm.actions[index] = Object.assign({}, this.actionForm.actions[index], updates);
            },

            removeAction: function(index) {
                this.actionForm.actions.splice(index, 1);
            },

            saveActions: function() {
                if (!window.VBPActions) return;

                var events = {};
                events[this.actionForm.event] = this.actionForm.actions;

                window.VBPActions.configure(this.actionForm.elementId, events);

                this.closeActionModal();
            },

            getAvailableEvents: function() {
                return [
                    { value: 'click', label: 'Click' },
                    { value: 'dblclick', label: 'Doble click' },
                    { value: 'mouseenter', label: 'Mouse entra' },
                    { value: 'mouseleave', label: 'Mouse sale' },
                    { value: 'focus', label: 'Focus' },
                    { value: 'blur', label: 'Blur' },
                    { value: 'change', label: 'Cambio' },
                    { value: 'input', label: 'Input' },
                    { value: 'submit', label: 'Submit' }
                ];
            },

            getActionParams: function(actionType) {
                var actionDef = this.actionTypes[actionType];
                return actionDef ? actionDef.params : [];
            },

            // ============================================
            // EXPRESIONES Y AUTOCOMPLETADO
            // ============================================

            getExpressionSuggestions: function(input) {
                var suggestions = [];

                // Variables
                this.variables.forEach(function(variableItem) {
                    suggestions.push({
                        type: 'variable',
                        text: '{{' + variableItem.name + '}}',
                        label: variableItem.name,
                        description: variableItem.description || 'Variable ' + variableItem.type
                    });
                });

                // Colecciones
                this.collections.forEach(function(collection) {
                    suggestions.push({
                        type: 'collection',
                        text: '{{' + collection.name + '}}',
                        label: collection.name,
                        description: 'Colección con ' + collection.data.length + ' items'
                    });
                });

                // Transforms
                for (var transformName in this.transforms) {
                    suggestions.push({
                        type: 'transform',
                        text: ' | ' + transformName,
                        label: transformName,
                        description: this.transforms[transformName].label
                    });
                }

                // Filtrar por input
                if (input) {
                    var query = input.toLowerCase();
                    suggestions = suggestions.filter(function(s) {
                        return s.label.toLowerCase().indexOf(query) !== -1;
                    });
                }

                return suggestions.slice(0, 10);
            },

            insertSuggestion: function(suggestion, inputRef) {
                if (!inputRef) return;

                var cursorPos = inputRef.selectionStart;
                var currentValue = inputRef.value;

                var newValue = currentValue.substring(0, cursorPos) +
                               suggestion.text +
                               currentValue.substring(inputRef.selectionEnd);

                inputRef.value = newValue;
                inputRef.focus();

                var newCursorPos = cursorPos + suggestion.text.length;
                inputRef.setSelectionRange(newCursorPos, newCursorPos);
            },

            // ============================================
            // MODO DEBUG
            // ============================================

            toggleDebugMode: function() {
                this.debugMode = !this.debugMode;
                if (this.debugMode) {
                    this.debugLog = [];
                }
            },

            addDebugEntry: function(entry) {
                this.debugLog.unshift(entry);
                if (this.debugLog.length > this.maxDebugEntries) {
                    this.debugLog.pop();
                }
            },

            clearDebugLog: function() {
                this.debugLog = [];
            },

            formatDebugEntry: function(entry) {
                var time = entry.timestamp.toLocaleTimeString();
                switch (entry.type) {
                    case 'variable_change':
                        return '[' + time + '] ' + entry.variableId + ': ' +
                               JSON.stringify(entry.oldValue) + ' -> ' +
                               JSON.stringify(entry.newValue);
                    case 'action_executed':
                        return '[' + time + '] Acción: ' + entry.actionType;
                    case 'binding_applied':
                        return '[' + time + '] Binding: ' + entry.elementId + '.' + entry.property;
                    default:
                        return '[' + time + '] ' + JSON.stringify(entry);
                }
            },

            testExpression: function() {
                var expr = prompt('Expresión a evaluar:');
                if (!expr) return;

                try {
                    var result = window.VBPExpressions.evaluate(expr);
                    alert('Resultado: ' + JSON.stringify(result));
                } catch (e) {
                    alert('Error: ' + e.message);
                }
            },

            testCondition: function() {
                var variableName = prompt('Nombre de variable:');
                var operator = prompt('Operador (equals, greater_than, etc):');
                var value = prompt('Valor a comparar:');

                if (!variableName || !operator) return;

                var result = window.VBPConditions.evaluate([
                    { variable: variableName, operator: operator, value: value }
                ]);

                alert('Resultado: ' + (result ? 'VERDADERO' : 'FALSO'));
            },

            // ============================================
            // EXPORTAR / IMPORTAR
            // ============================================

            exportVariables: function() {
                if (!window.VBPVariables) return;

                var state = window.VBPVariables.exportState();
                var json = JSON.stringify(state, null, 2);

                var blob = new Blob([json], { type: 'application/json' });
                var url = URL.createObjectURL(blob);

                var downloadLink = document.createElement('a');
                downloadLink.href = url;
                downloadLink.download = 'vbp-variables-' + Date.now() + '.json';
                downloadLink.click();

                URL.revokeObjectURL(url);
            },

            importVariables: function() {
                var self = this;
                var fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.accept = '.json';

                fileInput.onchange = function(e) {
                    var file = e.target.files[0];
                    if (!file) return;

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        try {
                            var state = JSON.parse(e.target.result);
                            if (window.VBPVariables) {
                                window.VBPVariables.importState(state);
                                alert('Variables importadas correctamente');
                            }
                        } catch (err) {
                            alert('Error al importar: ' + err.message);
                        }
                    };
                    reader.readAsText(file);
                };

                fileInput.click();
            },

            // ============================================
            // ESTADOS DE COMPONENTE
            // ============================================

            getElementStates: function() {
                if (!this.selectedElement || !window.VBPComponentStates) return [];
                return window.VBPComponentStates.listStates(this.selectedElement.id);
            },

            getCurrentElementState: function() {
                if (!this.selectedElement || !window.VBPComponentStates) return 'default';
                return window.VBPComponentStates.getCurrentState(this.selectedElement.id);
            },

            setElementState: function(stateName) {
                if (!this.selectedElement || !window.VBPComponentStates) return;
                window.VBPComponentStates.setState(this.selectedElement.id, stateName);
            },

            addElementState: function() {
                if (!this.selectedElement || !window.VBPComponentStates) return;

                var stateName = prompt('Nombre del estado:');
                if (!stateName) return;

                var states = {};
                var existingStates = this.getElementStates();

                existingStates.forEach(function(state) {
                    states[state] = window.VBPComponentStates.getStateDefinition(this.selectedElement.id, state);
                });

                states[stateName] = {
                    // Estilos por defecto para el nuevo estado
                    opacity: 1,
                    transform: 'none'
                };

                window.VBPComponentStates.configure(this.selectedElement.id, states);
            }
        };
    }

    // Registrar componente
    window.vbpVariablesPanel = vbpVariablesPanel;

    vbpLog.log('VBP Variables Panel loaded');

})();
