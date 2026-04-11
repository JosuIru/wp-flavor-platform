/**
 * Visual Builder Pro - Logic Runtime
 * Ejecuta la lógica de variables, bindings, condiciones y loops en el frontend
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

(function() {
    'use strict';

    /**
     * VBP Logic Runtime
     * Sistema ligero para ejecutar lógica en páginas publicadas
     */
    var VBPLogicRuntime = {
        /**
         * Estado de variables
         */
        _variables: {},
        _watchers: {},
        _bindings: {},
        _loops: {},
        _events: {},
        _states: {},
        _initialized: false,

        /**
         * Inicializar runtime
         */
        init: function() {
            if (this._initialized) return;

            // Cargar configuración desde el DOM
            this._loadConfig();

            // Cargar variables iniciales
            this._loadInitialVariables();

            // Procesar bindings
            this._processBindings();

            // Procesar loops
            this._processLoops();

            // Procesar condiciones
            this._processConditions();

            // Configurar eventos
            this._setupEvents();

            // Marcar como inicializado
            this._initialized = true;

            console.log('[VBP Runtime] Initialized');
        },

        /**
         * Cargar configuración desde el DOM
         */
        _loadConfig: function() {
            var configElement = document.getElementById('vbp-logic-config');
            if (!configElement) return;

            try {
                var config = JSON.parse(configElement.textContent);
                this._variables = config.variables || {};
                this._bindings = config.bindings || {};
                this._loops = config.loops || {};
                this._events = config.events || {};
                this._states = config.states || {};
            } catch (e) {
                console.warn('[VBP Runtime] Error parsing config:', e);
            }
        },

        /**
         * Cargar variables iniciales
         */
        _loadInitialVariables: function() {
            // Cargar de localStorage/sessionStorage
            try {
                var savedVars = localStorage.getItem('vbp_user_variables');
                if (savedVars) {
                    var parsed = JSON.parse(savedVars);
                    for (var id in parsed) {
                        this._variables[id] = parsed[id].value;
                    }
                }

                var sessionVars = sessionStorage.getItem('vbp_session_variables');
                if (sessionVars) {
                    var parsedSession = JSON.parse(sessionVars);
                    for (var sessionId in parsedSession) {
                        this._variables[sessionId] = parsedSession[sessionId].value;
                    }
                }
            } catch (e) {
                console.warn('[VBP Runtime] Error loading persisted variables:', e);
            }

            // Variables del contexto de WordPress
            if (typeof vbpPageData !== 'undefined') {
                this._variables['wp:post_id'] = vbpPageData.postId || 0;
                this._variables['wp:site_url'] = vbpPageData.siteUrl || '';
                this._variables['wp:user_id'] = vbpPageData.userId || 0;
                this._variables['wp:is_logged_in'] = vbpPageData.userId > 0;
            }

            // Variables del query string
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.forEach(function(value, key) {
                this._variables['query:' + key] = value;
            }.bind(this));
        },

        /**
         * Obtener valor de variable
         */
        getVariable: function(name) {
            // Soporte para acceso a propiedades anidadas
            var parts = name.split('.');
            var value = this._variables[parts[0]];

            for (var i = 1; i < parts.length && value !== undefined; i++) {
                value = value[parts[i]];
            }

            return value;
        },

        /**
         * Establecer valor de variable
         */
        setVariable: function(name, value) {
            var oldValue = this._variables[name];
            this._variables[name] = value;

            // Notificar watchers
            if (this._watchers[name]) {
                this._watchers[name].forEach(function(callback) {
                    try {
                        callback(value, oldValue);
                    } catch (e) {
                        console.error('[VBP Runtime] Watcher error:', e);
                    }
                });
            }

            // Re-evaluar bindings afectados
            this._updateBindingsForVariable(name);

            // Re-evaluar condiciones afectadas
            this._updateConditionsForVariable(name);
        },

        /**
         * Observar cambios en variable
         */
        watch: function(name, callback) {
            if (!this._watchers[name]) {
                this._watchers[name] = [];
            }
            this._watchers[name].push(callback);

            // Devolver función para cancelar
            return function() {
                var index = this._watchers[name].indexOf(callback);
                if (index > -1) {
                    this._watchers[name].splice(index, 1);
                }
            }.bind(this);
        },

        /**
         * Evaluar expresión
         */
        evaluate: function(expression, context) {
            if (typeof expression !== 'string') return expression;

            context = context || {};
            var self = this;

            // Reemplazar {{variable}} con valores
            return expression.replace(/\{\{(.+?)\}\}/g, function(match, expr) {
                expr = expr.trim();

                // Manejar transforms con pipes
                var parts = expr.split('|');
                var value = self._resolveValue(parts[0].trim(), context);

                // Aplicar transforms
                for (var i = 1; i < parts.length; i++) {
                    value = self._applyTransform(value, parts[i].trim());
                }

                return value !== undefined ? value : '';
            });
        },

        /**
         * Resolver valor de variable o propiedad
         */
        _resolveValue: function(path, context) {
            // Literales
            if (path === 'true') return true;
            if (path === 'false') return false;
            if (path === 'null') return null;
            if (!isNaN(Number(path))) return Number(path);
            if (path.match(/^['"].*['"]$/)) return path.slice(1, -1);

            // Primero buscar en contexto
            var parts = path.split('.');
            if (context[parts[0]] !== undefined) {
                var value = context[parts[0]];
                for (var i = 1; i < parts.length && value !== undefined; i++) {
                    value = value[parts[i]];
                }
                return value;
            }

            // Luego buscar en variables
            return this.getVariable(path);
        },

        /**
         * Aplicar transform a valor
         */
        _applyTransform: function(value, transformExpr) {
            var parts = transformExpr.split(':');
            var transformName = parts[0].trim();
            var args = parts.slice(1).map(function(arg) {
                return arg.trim().replace(/^['"]|['"]$/g, '');
            });

            var transforms = {
                'uppercase': function(v) { return String(v).toUpperCase(); },
                'lowercase': function(v) { return String(v).toLowerCase(); },
                'capitalize': function(v) {
                    var str = String(v);
                    return str.charAt(0).toUpperCase() + str.slice(1);
                },
                'currency': function(v, args) {
                    var num = parseFloat(v);
                    if (isNaN(num)) return v;
                    var symbol = args[0] || '€';
                    return symbol + num.toFixed(2);
                },
                'truncate': function(v, args) {
                    var str = String(v);
                    var len = parseInt(args[0]) || 50;
                    if (str.length <= len) return str;
                    return str.slice(0, len) + '...';
                },
                'default': function(v, args) {
                    return v || args[0] || '';
                },
                'number_format': function(v, args) {
                    var num = Number(v);
                    if (isNaN(num)) return v;
                    return num.toLocaleString();
                },
                'date_format': function(v, args) {
                    var date = new Date(v);
                    if (isNaN(date.getTime())) return v;
                    return date.toLocaleDateString();
                },
                'json': function(v) {
                    return JSON.stringify(v);
                }
            };

            if (transforms[transformName]) {
                return transforms[transformName](value, args);
            }

            return value;
        },

        /**
         * Procesar bindings
         */
        _processBindings: function() {
            var self = this;

            for (var elementId in this._bindings) {
                var elementBindings = this._bindings[elementId];

                for (var property in elementBindings) {
                    this._applyBinding(elementId, property, elementBindings[property]);
                }
            }
        },

        /**
         * Aplicar binding a elemento
         */
        _applyBinding: function(elementId, property, binding) {
            var element = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!element) return;

            var value;
            if (binding.type === 'variable') {
                value = this.getVariable(binding.source);
            } else if (binding.type === 'expression') {
                value = this.evaluate(binding.source);
            }

            // Aplicar transform si existe
            if (binding.transform) {
                value = this._applyTransform(value, binding.transform);
            }

            // Aplicar al DOM
            this._applyToDom(element, property, value);

            // Registrar para actualizaciones
            var variables = this._extractVariables(binding.source);
            var self = this;
            variables.forEach(function(varName) {
                if (!self._watchers[varName]) {
                    self._watchers[varName] = [];
                }
                self._watchers[varName].push(function() {
                    self._applyBinding(elementId, property, binding);
                });
            });
        },

        /**
         * Aplicar valor al DOM
         */
        _applyToDom: function(element, property, value) {
            switch (property) {
                case 'text':
                case 'data.text':
                case 'data.content':
                    element.textContent = value;
                    break;
                case 'html':
                    element.innerHTML = value;
                    break;
                case 'visible':
                    element.style.display = value ? '' : 'none';
                    break;
                case 'className':
                    element.className = value;
                    break;
                case 'data.src':
                case 'src':
                    element.src = value;
                    break;
                case 'data.url':
                case 'href':
                    element.href = value;
                    break;
                case 'styles.colors.background':
                    element.style.backgroundColor = value;
                    break;
                case 'styles.colors.text':
                    element.style.color = value;
                    break;
                default:
                    // Intentar como atributo data
                    if (property.indexOf('data.') === 0) {
                        var attrName = 'data-' + property.replace('data.', '').replace(/([A-Z])/g, '-$1').toLowerCase();
                        element.setAttribute(attrName, value);
                    }
            }
        },

        /**
         * Extraer nombres de variables de una expresión
         */
        _extractVariables: function(expression) {
            var variables = [];
            var matches = expression.match(/\{\{([^}|]+)/g);
            if (matches) {
                matches.forEach(function(match) {
                    var varName = match.replace('{{', '').trim().split('.')[0].split('[')[0];
                    if (variables.indexOf(varName) === -1) {
                        variables.push(varName);
                    }
                });
            }
            return variables;
        },

        /**
         * Actualizar bindings cuando cambia una variable
         */
        _updateBindingsForVariable: function(variableName) {
            // Ya se maneja con watchers en _applyBinding
        },

        /**
         * Procesar loops
         */
        _processLoops: function() {
            var self = this;

            for (var elementId in this._loops) {
                this._renderLoop(elementId, this._loops[elementId]);
            }
        },

        /**
         * Renderizar loop
         */
        _renderLoop: function(elementId, config) {
            var container = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!container) return;

            var template = container.querySelector('[data-vbp-loop-template]');
            if (!template) {
                // Usar el contenido actual como template
                template = container.firstElementChild;
                if (template) {
                    template.setAttribute('data-vbp-loop-template', 'true');
                    template.style.display = 'none';
                }
            }

            if (!template) return;

            // Obtener datos de la colección
            var data = this.getVariable(config.collection);
            if (!Array.isArray(data)) return;

            // Limpiar elementos anteriores (excepto template)
            var existingItems = container.querySelectorAll('[data-vbp-loop-item]');
            existingItems.forEach(function(item) {
                item.remove();
            });

            // Renderizar items
            var self = this;
            data.forEach(function(item, index) {
                var clone = template.cloneNode(true);
                clone.removeAttribute('data-vbp-loop-template');
                clone.setAttribute('data-vbp-loop-item', index);
                clone.style.display = '';

                // Crear contexto para el item
                var context = {};
                context[config.itemVariable || 'item'] = item;
                context[config.indexVariable || 'index'] = index;

                // Procesar expresiones en el contenido
                self._processElementExpressions(clone, context);

                container.appendChild(clone);
            });

            // Observar cambios en la colección
            this.watch(config.collection, function() {
                self._renderLoop(elementId, config);
            });
        },

        /**
         * Procesar expresiones en un elemento y sus hijos
         */
        _processElementExpressions: function(element, context) {
            var self = this;

            // Procesar texto
            if (element.childNodes) {
                element.childNodes.forEach(function(node) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        var text = node.textContent;
                        if (text.indexOf('{{') !== -1) {
                            node.textContent = self.evaluate(text, context);
                        }
                    }
                });
            }

            // Procesar atributos
            if (element.attributes) {
                for (var i = 0; i < element.attributes.length; i++) {
                    var attr = element.attributes[i];
                    if (attr.value.indexOf('{{') !== -1) {
                        element.setAttribute(attr.name, self.evaluate(attr.value, context));
                    }
                }
            }

            // Procesar hijos
            if (element.children) {
                for (var j = 0; j < element.children.length; j++) {
                    this._processElementExpressions(element.children[j], context);
                }
            }
        },

        /**
         * Procesar condiciones
         */
        _processConditions: function() {
            var elements = document.querySelectorAll('[data-vbp-conditions]');
            var self = this;

            elements.forEach(function(element) {
                try {
                    var conditions = JSON.parse(element.getAttribute('data-vbp-conditions'));
                    self._evaluateElementConditions(element, conditions);

                    // Observar variables relevantes
                    conditions.forEach(function(condition) {
                        if (condition.when) {
                            condition.when.forEach(function(rule) {
                                self.watch(rule.variable, function() {
                                    self._evaluateElementConditions(element, conditions);
                                });
                            });
                        }
                    });
                } catch (e) {
                    console.warn('[VBP Runtime] Error parsing conditions:', e);
                }
            });
        },

        /**
         * Evaluar condiciones de un elemento
         */
        _evaluateElementConditions: function(element, conditions) {
            var self = this;

            conditions.forEach(function(condition) {
                var result = self._evaluateCondition(condition);

                switch (condition.property) {
                    case 'visible':
                        element.style.display = result ? '' : 'none';
                        break;
                    case 'className':
                        if (result && condition.then) {
                            element.classList.add(condition.then);
                            if (condition.else) element.classList.remove(condition.else);
                        } else if (!result && condition.else) {
                            element.classList.add(condition.else);
                            if (condition.then) element.classList.remove(condition.then);
                        }
                        break;
                    case 'disabled':
                        element.disabled = result;
                        break;
                }
            });
        },

        /**
         * Evaluar una condición
         */
        _evaluateCondition: function(condition) {
            if (!condition.when || !condition.when.length) return true;

            var self = this;
            var logic = condition.logic || 'and';

            if (logic === 'or') {
                return condition.when.some(function(rule) {
                    return self._evaluateRule(rule);
                });
            }

            return condition.when.every(function(rule) {
                return self._evaluateRule(rule);
            });
        },

        /**
         * Evaluar una regla individual
         */
        _evaluateRule: function(rule) {
            var leftValue = this.getVariable(rule.variable);
            var rightValue = rule.value;

            // Evaluar valor si es expresión
            if (typeof rightValue === 'string' && rightValue.indexOf('{{') !== -1) {
                rightValue = this.evaluate(rightValue);
            }

            var operators = {
                'equals': function(l, r) { return l == r; },
                'not_equals': function(l, r) { return l != r; },
                'strict_equals': function(l, r) { return l === r; },
                'greater_than': function(l, r) { return Number(l) > Number(r); },
                'less_than': function(l, r) { return Number(l) < Number(r); },
                'greater_or_equal': function(l, r) { return Number(l) >= Number(r); },
                'less_or_equal': function(l, r) { return Number(l) <= Number(r); },
                'contains': function(l, r) {
                    if (Array.isArray(l)) return l.indexOf(r) !== -1;
                    return String(l).indexOf(String(r)) !== -1;
                },
                'is_empty': function(l) {
                    if (l === null || l === undefined) return true;
                    if (typeof l === 'string') return l.trim() === '';
                    if (Array.isArray(l)) return l.length === 0;
                    return false;
                },
                'is_not_empty': function(l) {
                    return !operators.is_empty(l);
                }
            };

            var operator = operators[rule.operator];
            return operator ? operator(leftValue, rightValue) : false;
        },

        /**
         * Configurar eventos
         */
        _setupEvents: function() {
            var self = this;

            for (var elementId in this._events) {
                var elementEvents = this._events[elementId];
                var element = document.querySelector('[data-vbp-id="' + elementId + '"]');

                if (!element) continue;

                for (var eventType in elementEvents) {
                    (function(evType, actions) {
                        element.addEventListener(evType, function(event) {
                            self._executeActions(actions, {
                                event: event,
                                elementId: elementId,
                                target: event.target
                            });
                        });
                    })(eventType, elementEvents[eventType]);
                }
            }
        },

        /**
         * Ejecutar lista de acciones
         */
        _executeActions: function(actions, context) {
            var self = this;

            actions.forEach(function(action) {
                self._executeAction(action, context);
            });
        },

        /**
         * Ejecutar acción individual
         */
        _executeAction: function(action, context) {
            switch (action.action) {
                case 'setVariable':
                    var value = this.evaluate(action.value, context);
                    this.setVariable(action.variable, value);
                    break;

                case 'toggleVariable':
                    var currentValue = this.getVariable(action.variable);
                    this.setVariable(action.variable, !currentValue);
                    break;

                case 'incrementVariable':
                    var currentNumValue = Number(this.getVariable(action.variable)) || 0;
                    var incrementAmount = Number(action.amount) || 1;
                    this.setVariable(action.variable, currentNumValue + incrementAmount);
                    break;

                case 'navigate':
                    var url = this.evaluate(action.url, context);
                    if (action.target === '_blank') {
                        window.open(url, '_blank');
                    } else {
                        window.location.href = url;
                    }
                    break;

                case 'showElement':
                    var showTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (showTarget) showTarget.style.display = '';
                    break;

                case 'hideElement':
                    var hideTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (hideTarget) hideTarget.style.display = 'none';
                    break;

                case 'toggleElement':
                    var toggleTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (toggleTarget) {
                        toggleTarget.style.display = toggleTarget.style.display === 'none' ? '' : 'none';
                    }
                    break;

                case 'addClass':
                    var addClassTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (addClassTarget && action.className) {
                        addClassTarget.classList.add(action.className);
                    }
                    break;

                case 'removeClass':
                    var removeClassTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (removeClassTarget && action.className) {
                        removeClassTarget.classList.remove(action.className);
                    }
                    break;

                case 'toggleClass':
                    var toggleClassTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (toggleClassTarget && action.className) {
                        toggleClassTarget.classList.toggle(action.className);
                    }
                    break;

                case 'scrollTo':
                    var scrollTarget = document.querySelector('[data-vbp-id="' + action.elementId + '"]');
                    if (scrollTarget) {
                        scrollTarget.scrollIntoView({
                            behavior: action.behavior || 'smooth',
                            block: 'start'
                        });
                    }
                    break;

                case 'copyToClipboard':
                    var textToCopy = this.evaluate(action.value, context);
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(textToCopy);
                    }
                    break;

                case 'fetch':
                    var self = this;
                    var fetchUrl = this.evaluate(action.url, context);
                    fetch(fetchUrl, {
                        method: action.method || 'GET',
                        headers: action.headers || {}
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (action.target) {
                            self.setVariable(action.target, data);
                        }
                    })
                    .catch(function(error) {
                        console.error('[VBP Runtime] Fetch error:', error);
                    });
                    break;

                case 'emit':
                    var payload = action.payload ? this.evaluate(action.payload, context) : {};
                    document.dispatchEvent(new CustomEvent('vbp:' + action.eventName, {
                        detail: payload
                    }));
                    break;

                case 'console':
                    var message = this.evaluate(action.message, context);
                    console[action.level || 'log']('[VBP]', message);
                    break;
            }
        },

        /**
         * Actualizar condiciones cuando cambia una variable
         */
        _updateConditionsForVariable: function(variableName) {
            // Ya se maneja con watchers en _processConditions
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPLogicRuntime.init();
        });
    } else {
        VBPLogicRuntime.init();
    }

    // Exportar a window
    window.VBPLogicRuntime = VBPLogicRuntime;

})();
