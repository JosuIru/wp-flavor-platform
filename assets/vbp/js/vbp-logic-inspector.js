/**
 * Visual Builder Pro - Inspector de Lógica
 * Sección del inspector para configurar lógica de elementos
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP Logic Inspector]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP Logic Inspector]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP Logic Inspector]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Componente Inspector de Lógica para elementos
     */
    function vbpLogicInspector() {
        return {
            // Estado expandido de secciones
            expandedSections: {
                bindings: true,
                conditions: false,
                loop: false,
                events: false,
                states: false
            },

            // Modales
            showBindingEditor: false,
            showConditionEditor: false,
            showEventEditor: false,
            showStateEditor: false,

            // Formularios de edición
            currentBinding: {
                property: '',
                type: 'variable',
                source: '',
                transform: '',
                twoWay: false
            },

            currentCondition: {
                index: -1,
                property: 'visible',
                logic: 'and',
                rules: []
            },

            currentEvent: {
                type: 'click',
                actions: []
            },

            currentState: {
                name: '',
                styles: {}
            },

            // Autocompletado
            showSuggestions: false,
            suggestions: [],
            suggestionTarget: null,

            // ============================================
            // GETTERS
            // ============================================

            get element() {
                var store = Alpine.store('vbp');
                return store ? store.selectedElement : null;
            },

            get elementId() {
                return this.element ? this.element.id : null;
            },

            get bindings() {
                if (!this.elementId || !window.VBPBindings) return {};
                return window.VBPBindings.getBindings(this.elementId);
            },

            get bindingsList() {
                var bindings = this.bindings;
                var result = [];
                for (var prop in bindings) {
                    result.push({
                        property: prop,
                        binding: bindings[prop]
                    });
                }
                return result;
            },

            get hasBindings() {
                return Object.keys(this.bindings).length > 0;
            },

            get conditions() {
                if (!this.element || !this.element.logic) return [];
                return this.element.logic.conditions || [];
            },

            get hasConditions() {
                return this.conditions.length > 0;
            },

            get loop() {
                if (!this.elementId || !window.VBPLoops) return null;
                return window.VBPLoops.getConfig(this.elementId);
            },

            get hasLoop() {
                return !!this.loop;
            },

            get events() {
                if (!this.elementId || !window.VBPActions) return {};
                return window.VBPActions.getEvents(this.elementId);
            },

            get eventsList() {
                var events = this.events;
                var result = [];
                for (var eventType in events) {
                    result.push({
                        type: eventType,
                        actions: events[eventType]
                    });
                }
                return result;
            },

            get hasEvents() {
                return Object.keys(this.events).length > 0;
            },

            get states() {
                if (!this.elementId || !window.VBPComponentStates) return [];
                return window.VBPComponentStates.listStates(this.elementId);
            },

            get currentStateName() {
                if (!this.elementId || !window.VBPComponentStates) return 'default';
                return window.VBPComponentStates.getCurrentState(this.elementId);
            },

            get hasStates() {
                return this.states.length > 0;
            },

            get variables() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return [];
                return store.logic.listVariables();
            },

            get collections() {
                var store = Alpine.store('vbp');
                if (!store || !store.logic) return [];
                return store.logic.listCollections();
            },

            // ============================================
            // MÉTODOS DE SECCIONES
            // ============================================

            toggleSection: function(section) {
                this.expandedSections[section] = !this.expandedSections[section];
            },

            // ============================================
            // BINDINGS
            // ============================================

            getBindableProperties: function() {
                var elementType = this.element ? this.element.type : null;

                var commonProperties = [
                    { value: 'visible', label: 'Visible', icon: '👁' },
                    { value: 'className', label: 'Clases CSS', icon: '◻' },
                    { value: 'styles.colors.background', label: 'Color fondo', icon: '🎨' },
                    { value: 'styles.colors.text', label: 'Color texto', icon: '🖌' }
                ];

                var typeSpecificProperties = {
                    'text': [
                        { value: 'data.text', label: 'Texto', icon: 'T' },
                        { value: 'data.content', label: 'Contenido HTML', icon: '<>' }
                    ],
                    'heading': [
                        { value: 'data.text', label: 'Texto', icon: 'T' }
                    ],
                    'image': [
                        { value: 'data.src', label: 'URL imagen', icon: '▣' },
                        { value: 'data.alt', label: 'Texto alternativo', icon: 'Alt' }
                    ],
                    'button': [
                        { value: 'data.text', label: 'Texto', icon: 'T' },
                        { value: 'data.url', label: 'URL enlace', icon: '🔗' }
                    ],
                    'link': [
                        { value: 'data.text', label: 'Texto', icon: 'T' },
                        { value: 'data.url', label: 'URL', icon: '🔗' }
                    ]
                };

                var specificProps = typeSpecificProperties[elementType] || [];
                return specificProps.concat(commonProperties);
            },

            openBindingEditor: function(property) {
                var existingBinding = this.bindings[property];

                this.currentBinding = {
                    property: property || '',
                    type: existingBinding ? existingBinding.type : 'variable',
                    source: existingBinding ? existingBinding.source : '',
                    transform: existingBinding ? (existingBinding.transform || '') : '',
                    twoWay: existingBinding ? !!existingBinding.twoWay : false
                };

                this.showBindingEditor = true;
            },

            closeBindingEditor: function() {
                this.showBindingEditor = false;
            },

            saveBinding: function() {
                if (!this.elementId || !this.currentBinding.property || !this.currentBinding.source) {
                    return;
                }

                if (window.VBPBindings) {
                    window.VBPBindings.bind(this.elementId, this.currentBinding.property, {
                        type: this.currentBinding.type,
                        variableId: this.currentBinding.type === 'variable' ? this.currentBinding.source : null,
                        expression: this.currentBinding.type === 'expression' ? this.currentBinding.source : null,
                        transform: this.currentBinding.transform || null,
                        twoWay: this.currentBinding.twoWay
                    });
                }

                this.closeBindingEditor();
                this.saveLogicToElement();
            },

            removeBinding: function(property) {
                if (!this.elementId || !window.VBPBindings) return;
                window.VBPBindings.unbind(this.elementId, property);
                this.saveLogicToElement();
            },

            getBindingDisplayText: function(binding) {
                var text = binding.source;
                if (binding.transform) {
                    text += ' | ' + binding.transform;
                }
                return text;
            },

            // ============================================
            // CONDICIONES
            // ============================================

            openConditionEditor: function(index) {
                var existing = index >= 0 ? this.conditions[index] : null;

                this.currentCondition = {
                    index: index,
                    property: existing ? existing.property : 'visible',
                    logic: existing ? (existing.logic || 'and') : 'and',
                    rules: existing ? JSON.parse(JSON.stringify(existing.when || [])) : [
                        { variable: '', operator: 'equals', value: '' }
                    ],
                    thenValue: existing ? existing.then : '',
                    elseValue: existing ? existing.else : ''
                };

                this.showConditionEditor = true;
            },

            closeConditionEditor: function() {
                this.showConditionEditor = false;
            },

            addConditionRule: function() {
                this.currentCondition.rules.push({
                    variable: '',
                    operator: 'equals',
                    value: ''
                });
            },

            removeConditionRule: function(index) {
                this.currentCondition.rules.splice(index, 1);
            },

            saveCondition: function() {
                var store = Alpine.store('vbp');
                if (!store || !this.elementId) return;

                var logic = this.element.logic || {};
                var conditions = logic.conditions ? JSON.parse(JSON.stringify(logic.conditions)) : [];

                var conditionData = {
                    property: this.currentCondition.property,
                    logic: this.currentCondition.logic,
                    when: this.currentCondition.rules
                };

                if (this.currentCondition.thenValue) {
                    conditionData.then = this.currentCondition.thenValue;
                }
                if (this.currentCondition.elseValue) {
                    conditionData.else = this.currentCondition.elseValue;
                }

                if (this.currentCondition.index >= 0) {
                    conditions[this.currentCondition.index] = conditionData;
                } else {
                    conditions.push(conditionData);
                }

                logic.conditions = conditions;
                store.updateElement(this.elementId, { logic: logic });

                this.closeConditionEditor();
            },

            removeCondition: function(index) {
                var store = Alpine.store('vbp');
                if (!store || !this.elementId) return;

                var logic = this.element.logic || {};
                var conditions = logic.conditions ? JSON.parse(JSON.stringify(logic.conditions)) : [];
                conditions.splice(index, 1);
                logic.conditions = conditions;

                store.updateElement(this.elementId, { logic: logic });
            },

            getOperators: function() {
                var operators = window.VBP_COMPARISON_OPERATORS || {};
                var result = [];
                for (var key in operators) {
                    result.push({
                        value: key,
                        label: operators[key].label
                    });
                }
                return result;
            },

            getConditionSummary: function(condition) {
                if (!condition.when || condition.when.length === 0) return 'Sin reglas';

                var ruleTexts = condition.when.map(function(rule) {
                    return rule.variable + ' ' + rule.operator + ' ' + rule.value;
                });

                var logic = condition.logic === 'or' ? ' O ' : ' Y ';
                return condition.property + ': ' + ruleTexts.join(logic);
            },

            // ============================================
            // LOOP
            // ============================================

            configureLoop: function() {
                var collectionName = prompt('Nombre de la colección o variable array:');
                if (!collectionName) return;

                var itemVar = prompt('Nombre de variable para cada item (default: item):') || 'item';
                var indexVar = prompt('Nombre de variable para el índice (default: index):') || 'index';
                var keyProp = prompt('Propiedad para key única (default: id):') || 'id';

                if (window.VBPLoops) {
                    window.VBPLoops.configure(this.elementId, {
                        collection: collectionName,
                        itemVariable: itemVar,
                        indexVariable: indexVar,
                        keyProperty: keyProp
                    });
                }

                this.saveLogicToElement();
            },

            removeLoop: function() {
                if (window.VBPLoops) {
                    window.VBPLoops.remove(this.elementId);
                }
                this.saveLogicToElement();
            },

            getLoopPreview: function() {
                if (!this.loop) return '';
                return 'for (' + this.loop.itemVariable + ', ' + this.loop.indexVariable + ') in ' + this.loop.collection;
            },

            // ============================================
            // EVENTOS / ACCIONES
            // ============================================

            getAvailableEvents: function() {
                return [
                    { value: 'click', label: 'Click', icon: '👆' },
                    { value: 'dblclick', label: 'Doble click', icon: '👆👆' },
                    { value: 'mouseenter', label: 'Mouse entra', icon: '➡' },
                    { value: 'mouseleave', label: 'Mouse sale', icon: '⬅' },
                    { value: 'focus', label: 'Focus', icon: '🎯' },
                    { value: 'blur', label: 'Blur', icon: '💫' },
                    { value: 'change', label: 'Cambio de valor', icon: '🔄' },
                    { value: 'input', label: 'Input', icon: '⌨' },
                    { value: 'submit', label: 'Submit', icon: '📤' },
                    { value: 'scroll', label: 'Scroll', icon: '📜' },
                    { value: 'load', label: 'Cargado', icon: '✓' }
                ];
            },

            getAvailableActions: function() {
                var actions = window.VBP_ACTION_TYPES || {};
                var result = [];
                for (var key in actions) {
                    result.push({
                        value: key,
                        label: actions[key].label,
                        icon: actions[key].icon,
                        params: actions[key].params
                    });
                }
                return result;
            },

            openEventEditor: function(eventType) {
                var existingActions = this.events[eventType] || [];

                this.currentEvent = {
                    type: eventType || 'click',
                    actions: existingActions.length > 0
                        ? JSON.parse(JSON.stringify(existingActions))
                        : [{ action: 'setVariable', variable: '', value: '' }]
                };

                this.showEventEditor = true;
            },

            closeEventEditor: function() {
                this.showEventEditor = false;
            },

            addEventAction: function() {
                this.currentEvent.actions.push({
                    action: 'setVariable',
                    variable: '',
                    value: ''
                });
            },

            removeEventAction: function(index) {
                this.currentEvent.actions.splice(index, 1);
            },

            moveActionUp: function(index) {
                if (index <= 0) return;
                var actions = this.currentEvent.actions;
                var temp = actions[index];
                actions[index] = actions[index - 1];
                actions[index - 1] = temp;
            },

            moveActionDown: function(index) {
                if (index >= this.currentEvent.actions.length - 1) return;
                var actions = this.currentEvent.actions;
                var temp = actions[index];
                actions[index] = actions[index + 1];
                actions[index + 1] = temp;
            },

            getActionParams: function(actionType) {
                var actionDef = (window.VBP_ACTION_TYPES || {})[actionType];
                return actionDef ? actionDef.params : [];
            },

            saveEvent: function() {
                if (!this.elementId || !window.VBPActions) return;

                // Obtener eventos existentes y actualizar/añadir el actual
                var allEvents = JSON.parse(JSON.stringify(this.events));
                allEvents[this.currentEvent.type] = this.currentEvent.actions;

                window.VBPActions.configure(this.elementId, allEvents);

                this.closeEventEditor();
                this.saveLogicToElement();
            },

            removeEvent: function(eventType) {
                if (!this.elementId || !window.VBPActions) return;

                var allEvents = JSON.parse(JSON.stringify(this.events));
                delete allEvents[eventType];

                window.VBPActions.configure(this.elementId, allEvents);
                this.saveLogicToElement();
            },

            getEventSummary: function(event) {
                if (!event.actions || event.actions.length === 0) return 'Sin acciones';
                return event.actions.length + ' acción' + (event.actions.length > 1 ? 'es' : '');
            },

            // ============================================
            // ESTADOS DE COMPONENTE
            // ============================================

            openStateEditor: function(stateName) {
                var stateDefinition = null;
                if (stateName && window.VBPComponentStates) {
                    stateDefinition = window.VBPComponentStates.getStateDefinition(this.elementId, stateName);
                }

                this.currentState = {
                    name: stateName || '',
                    isNew: !stateName,
                    styles: stateDefinition ? JSON.parse(JSON.stringify(stateDefinition)) : {
                        opacity: '',
                        transform: '',
                        backgroundColor: '',
                        color: '',
                        borderColor: '',
                        boxShadow: ''
                    }
                };

                this.showStateEditor = true;
            },

            closeStateEditor: function() {
                this.showStateEditor = false;
            },

            saveState: function() {
                if (!this.elementId || !this.currentState.name || !window.VBPComponentStates) return;

                // Obtener estados existentes
                var allStates = {};
                this.states.forEach(function(stateName) {
                    allStates[stateName] = window.VBPComponentStates.getStateDefinition(this.elementId, stateName);
                }, this);

                // Añadir/actualizar estado actual
                allStates[this.currentState.name] = this.currentState.styles;

                // Reconfigurar estados
                window.VBPComponentStates.configure(this.elementId, allStates);

                this.closeStateEditor();
                this.saveLogicToElement();
            },

            removeState: function(stateName) {
                if (stateName === 'default') {
                    alert('No se puede eliminar el estado por defecto');
                    return;
                }

                if (!this.elementId || !window.VBPComponentStates) return;

                var allStates = {};
                this.states.forEach(function(existingStateName) {
                    if (existingStateName !== stateName) {
                        allStates[existingStateName] = window.VBPComponentStates.getStateDefinition(this.elementId, existingStateName);
                    }
                }, this);

                window.VBPComponentStates.configure(this.elementId, allStates);
                this.saveLogicToElement();
            },

            setActiveState: function(stateName) {
                if (!this.elementId || !window.VBPComponentStates) return;
                window.VBPComponentStates.setState(this.elementId, stateName);
            },

            getStateStyleProperties: function() {
                return [
                    { key: 'opacity', label: 'Opacidad', type: 'range', min: 0, max: 1, step: 0.1 },
                    { key: 'transform', label: 'Transform', type: 'text', placeholder: 'scale(1.05)' },
                    { key: 'backgroundColor', label: 'Fondo', type: 'color' },
                    { key: 'color', label: 'Color texto', type: 'color' },
                    { key: 'borderColor', label: 'Color borde', type: 'color' },
                    { key: 'boxShadow', label: 'Sombra', type: 'text', placeholder: '0 4px 6px rgba(0,0,0,0.1)' }
                ];
            },

            // ============================================
            // AUTOCOMPLETADO
            // ============================================

            showVariableSuggestions: function(event, targetField) {
                var input = event.target;
                var value = input.value;
                var cursorPos = input.selectionStart;

                // Buscar si estamos dentro de {{ }}
                var beforeCursor = value.substring(0, cursorPos);
                var openBracket = beforeCursor.lastIndexOf('{{');
                var closeBracket = beforeCursor.lastIndexOf('}}');

                if (openBracket > closeBracket) {
                    // Estamos dentro de una expresión
                    var searchTerm = beforeCursor.substring(openBracket + 2).toLowerCase();
                    this.suggestions = this.getSuggestions(searchTerm);
                    this.suggestionTarget = targetField;
                    this.showSuggestions = this.suggestions.length > 0;
                } else {
                    this.hideSuggestions();
                }
            },

            getSuggestions: function(searchTerm) {
                var suggestions = [];

                // Variables
                this.variables.forEach(function(variableItem) {
                    if (variableItem.name.toLowerCase().indexOf(searchTerm) !== -1) {
                        suggestions.push({
                            type: 'variable',
                            text: variableItem.name,
                            label: variableItem.name,
                            description: variableItem.type,
                            icon: (window.VBP_VARIABLE_TYPES[variableItem.type] || {}).icon || '?'
                        });
                    }
                });

                // Colecciones
                this.collections.forEach(function(collection) {
                    if (collection.name.toLowerCase().indexOf(searchTerm) !== -1) {
                        suggestions.push({
                            type: 'collection',
                            text: collection.name,
                            label: collection.name,
                            description: 'Colección (' + collection.data.length + ')',
                            icon: '[]'
                        });
                    }
                });

                // Transforms (después de |)
                if (searchTerm.indexOf('|') !== -1) {
                    var transformSearch = searchTerm.split('|').pop().trim();
                    var transforms = window.VBP_TRANSFORMS || {};
                    for (var transformKey in transforms) {
                        if (transformKey.indexOf(transformSearch) !== -1) {
                            suggestions.push({
                                type: 'transform',
                                text: transformKey,
                                label: transformKey,
                                description: transforms[transformKey].label,
                                icon: 'f'
                            });
                        }
                    }
                }

                return suggestions.slice(0, 8);
            },

            applySuggestion: function(suggestion, inputRef) {
                if (!inputRef) return;

                var value = inputRef.value;
                var cursorPos = inputRef.selectionStart;
                var beforeCursor = value.substring(0, cursorPos);

                // Encontrar inicio de la expresión actual
                var openBracket = beforeCursor.lastIndexOf('{{');
                var pipePos = beforeCursor.lastIndexOf('|');

                var insertPos;
                var insertText;

                if (suggestion.type === 'transform' && pipePos > openBracket) {
                    // Insertar transform después del pipe
                    insertPos = pipePos + 1;
                    insertText = ' ' + suggestion.text;
                } else {
                    // Insertar variable/colección después de {{
                    insertPos = openBracket + 2;
                    insertText = suggestion.text;
                }

                // Encontrar fin del término actual
                var afterInsert = value.substring(insertPos);
                var endOfTerm = afterInsert.search(/[\s|}\]]/);
                if (endOfTerm === -1) endOfTerm = afterInsert.length;

                // Reconstruir valor
                var newValue = value.substring(0, insertPos) + insertText + value.substring(insertPos + endOfTerm);
                inputRef.value = newValue;

                // Posicionar cursor
                var newCursorPos = insertPos + insertText.length;
                inputRef.setSelectionRange(newCursorPos, newCursorPos);
                inputRef.focus();

                this.hideSuggestions();
            },

            hideSuggestions: function() {
                this.showSuggestions = false;
                this.suggestions = [];
                this.suggestionTarget = null;
            },

            // ============================================
            // UTILIDADES
            // ============================================

            saveLogicToElement: function() {
                // Guardar configuración de lógica en el elemento del store
                var store = Alpine.store('vbp');
                if (!store || !this.elementId) return;

                var logic = {
                    bindings: window.VBPBindings ? window.VBPBindings.getBindings(this.elementId) : {},
                    conditions: this.conditions,
                    loop: window.VBPLoops ? window.VBPLoops.getConfig(this.elementId) : null,
                    events: window.VBPActions ? window.VBPActions.getEvents(this.elementId) : {},
                    states: {}
                };

                // Guardar estados
                if (window.VBPComponentStates) {
                    this.states.forEach(function(stateName) {
                        logic.states[stateName] = window.VBPComponentStates.getStateDefinition(this.elementId, stateName);
                    }, this);
                }

                store.updateElement(this.elementId, { logic: logic });
            },

            loadLogicFromElement: function() {
                // Cargar configuración de lógica desde el elemento
                if (!this.element || !this.element.logic) return;

                var logic = this.element.logic;

                // Restaurar bindings
                if (logic.bindings && window.VBPBindings) {
                    for (var prop in logic.bindings) {
                        window.VBPBindings.bind(this.elementId, prop, logic.bindings[prop]);
                    }
                }

                // Restaurar loop
                if (logic.loop && window.VBPLoops) {
                    window.VBPLoops.configure(this.elementId, logic.loop);
                }

                // Restaurar eventos
                if (logic.events && window.VBPActions) {
                    window.VBPActions.configure(this.elementId, logic.events);
                }

                // Restaurar estados
                if (logic.states && window.VBPComponentStates) {
                    window.VBPComponentStates.configure(this.elementId, logic.states);
                }
            },

            copyExpression: function(text) {
                var expression = '{{' + text + '}}';
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(expression);
                }
            },

            formatJSON: function(value) {
                if (typeof value === 'object') {
                    return JSON.stringify(value, null, 2);
                }
                return String(value);
            }
        };
    }

    // Registrar componente
    window.vbpLogicInspector = vbpLogicInspector;

    vbpLog.log('VBP Logic Inspector loaded');

})();
