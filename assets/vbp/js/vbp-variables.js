/**
 * Visual Builder Pro - Sistema de Variables y Lógica
 * Gestión de variables, bindings, condiciones y loops
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP Variables]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP Variables]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP Variables]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    // ============================================
    // CONSTANTES Y TIPOS
    // ============================================

    /**
     * Tipos de variables soportados
     */
    var VARIABLE_TYPES = {
        'string': {
            defaultValue: '',
            inputType: 'text',
            icon: 'T',
            label: 'Texto',
            validate: function(value) { return typeof value === 'string'; }
        },
        'number': {
            defaultValue: 0,
            inputType: 'number',
            icon: '#',
            label: 'Número',
            validate: function(value) { return typeof value === 'number' && !isNaN(value); }
        },
        'boolean': {
            defaultValue: false,
            inputType: 'toggle',
            icon: '⊞',
            label: 'Booleano',
            validate: function(value) { return typeof value === 'boolean'; }
        },
        'color': {
            defaultValue: '#000000',
            inputType: 'color',
            icon: '◉',
            label: 'Color',
            validate: function(value) { return typeof value === 'string' && /^#[0-9A-Fa-f]{6}$/.test(value); }
        },
        'image': {
            defaultValue: '',
            inputType: 'image',
            icon: '▣',
            label: 'Imagen',
            validate: function(value) { return typeof value === 'string'; }
        },
        'array': {
            defaultValue: [],
            inputType: 'list',
            icon: '[]',
            label: 'Lista',
            validate: function(value) { return Array.isArray(value); }
        },
        'object': {
            defaultValue: {},
            inputType: 'json',
            icon: '{}',
            label: 'Objeto',
            validate: function(value) { return typeof value === 'object' && value !== null && !Array.isArray(value); }
        },
        'date': {
            defaultValue: null,
            inputType: 'date',
            icon: '📅',
            label: 'Fecha',
            validate: function(value) { return value === null || value instanceof Date || !isNaN(Date.parse(value)); }
        }
    };

    /**
     * Scopes de variables
     */
    var VARIABLE_SCOPES = {
        'page': {
            label: 'Página',
            description: 'Solo disponible en esta página',
            persist: false
        },
        'global': {
            label: 'Global',
            description: 'Disponible en todo el sitio',
            persist: true
        },
        'session': {
            label: 'Sesión',
            description: 'Persiste durante la sesión del navegador',
            persist: true,
            storage: 'sessionStorage'
        },
        'user': {
            label: 'Usuario',
            description: 'Persiste para el usuario actual',
            persist: true,
            storage: 'localStorage'
        }
    };

    /**
     * Operadores de comparación
     */
    var COMPARISON_OPERATORS = {
        'equals': {
            label: 'Es igual a',
            symbol: '==',
            evaluate: function(leftValue, rightValue) { return leftValue == rightValue; }
        },
        'not_equals': {
            label: 'No es igual a',
            symbol: '!=',
            evaluate: function(leftValue, rightValue) { return leftValue != rightValue; }
        },
        'strict_equals': {
            label: 'Es exactamente igual a',
            symbol: '===',
            evaluate: function(leftValue, rightValue) { return leftValue === rightValue; }
        },
        'greater_than': {
            label: 'Mayor que',
            symbol: '>',
            evaluate: function(leftValue, rightValue) { return Number(leftValue) > Number(rightValue); }
        },
        'less_than': {
            label: 'Menor que',
            symbol: '<',
            evaluate: function(leftValue, rightValue) { return Number(leftValue) < Number(rightValue); }
        },
        'greater_or_equal': {
            label: 'Mayor o igual que',
            symbol: '>=',
            evaluate: function(leftValue, rightValue) { return Number(leftValue) >= Number(rightValue); }
        },
        'less_or_equal': {
            label: 'Menor o igual que',
            symbol: '<=',
            evaluate: function(leftValue, rightValue) { return Number(leftValue) <= Number(rightValue); }
        },
        'contains': {
            label: 'Contiene',
            symbol: 'contains',
            evaluate: function(leftValue, rightValue) {
                if (Array.isArray(leftValue)) return leftValue.indexOf(rightValue) !== -1;
                return String(leftValue).indexOf(String(rightValue)) !== -1;
            }
        },
        'not_contains': {
            label: 'No contiene',
            symbol: '!contains',
            evaluate: function(leftValue, rightValue) {
                if (Array.isArray(leftValue)) return leftValue.indexOf(rightValue) === -1;
                return String(leftValue).indexOf(String(rightValue)) === -1;
            }
        },
        'starts_with': {
            label: 'Empieza con',
            symbol: 'startsWith',
            evaluate: function(leftValue, rightValue) { return String(leftValue).indexOf(String(rightValue)) === 0; }
        },
        'ends_with': {
            label: 'Termina con',
            symbol: 'endsWith',
            evaluate: function(leftValue, rightValue) {
                var str = String(leftValue);
                var suffix = String(rightValue);
                return str.indexOf(suffix, str.length - suffix.length) !== -1;
            }
        },
        'is_empty': {
            label: 'Está vacío',
            symbol: 'isEmpty',
            evaluate: function(leftValue) {
                if (leftValue === null || leftValue === undefined) return true;
                if (typeof leftValue === 'string') return leftValue.trim() === '';
                if (Array.isArray(leftValue)) return leftValue.length === 0;
                if (typeof leftValue === 'object') return Object.keys(leftValue).length === 0;
                return false;
            }
        },
        'is_not_empty': {
            label: 'No está vacío',
            symbol: '!isEmpty',
            evaluate: function(leftValue) {
                return !COMPARISON_OPERATORS.is_empty.evaluate(leftValue);
            }
        },
        'matches_regex': {
            label: 'Coincide con patrón',
            symbol: 'matches',
            evaluate: function(leftValue, rightValue) {
                try {
                    var regex = new RegExp(rightValue);
                    return regex.test(String(leftValue));
                } catch (e) {
                    return false;
                }
            }
        },
        'in_array': {
            label: 'Está en lista',
            symbol: 'in',
            evaluate: function(leftValue, rightValue) {
                if (!Array.isArray(rightValue)) return false;
                return rightValue.indexOf(leftValue) !== -1;
            }
        },
        'not_in_array': {
            label: 'No está en lista',
            symbol: '!in',
            evaluate: function(leftValue, rightValue) {
                if (!Array.isArray(rightValue)) return true;
                return rightValue.indexOf(leftValue) === -1;
            }
        }
    };

    /**
     * Acciones disponibles para eventos
     */
    var ACTION_TYPES = {
        'setVariable': {
            label: 'Establecer variable',
            icon: '=',
            params: ['variable', 'value'],
            execute: function(params, context) {
                VBPVariables.setVariable(params.variable, VBPExpressions.evaluate(params.value, context));
            }
        },
        'toggleVariable': {
            label: 'Alternar variable',
            icon: '⇄',
            params: ['variable'],
            execute: function(params) {
                var currentValue = VBPVariables.getVariable(params.variable);
                VBPVariables.setVariable(params.variable, !currentValue);
            }
        },
        'incrementVariable': {
            label: 'Incrementar variable',
            icon: '+1',
            params: ['variable', 'amount'],
            execute: function(params) {
                var currentValue = Number(VBPVariables.getVariable(params.variable)) || 0;
                var amountToAdd = Number(params.amount) || 1;
                VBPVariables.setVariable(params.variable, currentValue + amountToAdd);
            }
        },
        'decrementVariable': {
            label: 'Decrementar variable',
            icon: '-1',
            params: ['variable', 'amount'],
            execute: function(params) {
                var currentValue = Number(VBPVariables.getVariable(params.variable)) || 0;
                var amountToSubtract = Number(params.amount) || 1;
                VBPVariables.setVariable(params.variable, currentValue - amountToSubtract);
            }
        },
        'navigate': {
            label: 'Navegar a URL',
            icon: '→',
            params: ['url', 'target'],
            execute: function(params, context) {
                var evaluatedUrl = VBPExpressions.evaluate(params.url, context);
                var target = params.target || '_self';
                if (target === '_blank') {
                    window.open(evaluatedUrl, '_blank');
                } else {
                    window.location.href = evaluatedUrl;
                }
            }
        },
        'fetch': {
            label: 'Obtener datos de API',
            icon: '↓',
            params: ['url', 'method', 'target', 'headers', 'body'],
            execute: function(params, context) {
                var fetchUrl = VBPExpressions.evaluate(params.url, context);
                var method = params.method || 'GET';
                var targetVariable = params.target;

                var fetchOptions = {
                    method: method,
                    headers: params.headers || {}
                };

                if (method !== 'GET' && params.body) {
                    fetchOptions.body = JSON.stringify(VBPExpressions.evaluate(params.body, context));
                    fetchOptions.headers['Content-Type'] = 'application/json';
                }

                return fetch(fetchUrl, fetchOptions)
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (targetVariable) {
                            VBPVariables.setVariable(targetVariable, data);
                        }
                        return data;
                    })
                    .catch(function(error) {
                        vbpLog.error('Fetch error:', error);
                        if (targetVariable) {
                            VBPVariables.setVariable(targetVariable + '_error', error.message);
                        }
                    });
            }
        },
        'showElement': {
            label: 'Mostrar elemento',
            icon: '👁',
            params: ['elementId'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    targetElement.style.display = '';
                    targetElement.classList.remove('vbp-hidden');
                }
            }
        },
        'hideElement': {
            label: 'Ocultar elemento',
            icon: '🚫',
            params: ['elementId'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    targetElement.style.display = 'none';
                    targetElement.classList.add('vbp-hidden');
                }
            }
        },
        'toggleElement': {
            label: 'Alternar visibilidad',
            icon: '⇄👁',
            params: ['elementId'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    var isHidden = targetElement.style.display === 'none' || targetElement.classList.contains('vbp-hidden');
                    if (isHidden) {
                        targetElement.style.display = '';
                        targetElement.classList.remove('vbp-hidden');
                    } else {
                        targetElement.style.display = 'none';
                        targetElement.classList.add('vbp-hidden');
                    }
                }
            }
        },
        'addClass': {
            label: 'Añadir clase',
            icon: '+◻',
            params: ['elementId', 'className'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement && params.className) {
                    targetElement.classList.add(params.className);
                }
            }
        },
        'removeClass': {
            label: 'Quitar clase',
            icon: '-◻',
            params: ['elementId', 'className'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement && params.className) {
                    targetElement.classList.remove(params.className);
                }
            }
        },
        'toggleClass': {
            label: 'Alternar clase',
            icon: '⇄◻',
            params: ['elementId', 'className'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement && params.className) {
                    targetElement.classList.toggle(params.className);
                }
            }
        },
        'scrollTo': {
            label: 'Desplazar a elemento',
            icon: '↕',
            params: ['elementId', 'behavior'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: params.behavior || 'smooth',
                        block: 'start'
                    });
                }
            }
        },
        'copyToClipboard': {
            label: 'Copiar al portapapeles',
            icon: '📋',
            params: ['value'],
            execute: function(params, context) {
                var textToCopy = VBPExpressions.evaluate(params.value, context);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(textToCopy);
                } else {
                    // Fallback para navegadores antiguos
                    var tempTextarea = document.createElement('textarea');
                    tempTextarea.value = textToCopy;
                    document.body.appendChild(tempTextarea);
                    tempTextarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempTextarea);
                }
            }
        },
        'triggerAnimation': {
            label: 'Activar animación',
            icon: '✨',
            params: ['elementId', 'animationName'],
            execute: function(params) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    targetElement.classList.remove(params.animationName);
                    void targetElement.offsetWidth; // Trigger reflow
                    targetElement.classList.add(params.animationName);
                }
            }
        },
        'setStyle': {
            label: 'Establecer estilo',
            icon: '🎨',
            params: ['elementId', 'property', 'value'],
            execute: function(params, context) {
                var targetElement = document.querySelector('[data-vbp-id="' + params.elementId + '"]');
                if (targetElement) {
                    var styleValue = VBPExpressions.evaluate(params.value, context);
                    targetElement.style[params.property] = styleValue;
                }
            }
        },
        'emit': {
            label: 'Emitir evento',
            icon: '📣',
            params: ['eventName', 'payload'],
            execute: function(params, context) {
                var eventPayload = params.payload ? VBPExpressions.evaluate(params.payload, context) : {};
                document.dispatchEvent(new CustomEvent('vbp:' + params.eventName, {
                    detail: eventPayload
                }));
            }
        },
        'delay': {
            label: 'Esperar',
            icon: '⏱',
            params: ['duration'],
            execute: function(params) {
                var durationMs = Number(params.duration) || 1000;
                return new Promise(function(resolve) {
                    setTimeout(resolve, durationMs);
                });
            }
        },
        'console': {
            label: 'Registrar en consola',
            icon: '💬',
            params: ['message', 'level'],
            execute: function(params, context) {
                var logMessage = VBPExpressions.evaluate(params.message, context);
                var logLevel = params.level || 'log';
                console[logLevel]('[VBP]', logMessage);
            }
        },
        'pushToArray': {
            label: 'Añadir a lista',
            icon: '[+]',
            params: ['variable', 'value'],
            execute: function(params, context) {
                var currentArray = VBPVariables.getVariable(params.variable);
                if (!Array.isArray(currentArray)) {
                    currentArray = [];
                }
                var newArray = currentArray.slice();
                newArray.push(VBPExpressions.evaluate(params.value, context));
                VBPVariables.setVariable(params.variable, newArray);
            }
        },
        'removeFromArray': {
            label: 'Quitar de lista',
            icon: '[-]',
            params: ['variable', 'index'],
            execute: function(params) {
                var currentArray = VBPVariables.getVariable(params.variable);
                if (Array.isArray(currentArray)) {
                    var indexToRemove = Number(params.index);
                    var newArray = currentArray.slice();
                    newArray.splice(indexToRemove, 1);
                    VBPVariables.setVariable(params.variable, newArray);
                }
            }
        }
    };

    /**
     * Transformaciones de valores
     */
    var TRANSFORMS = {
        'uppercase': {
            label: 'Mayúsculas',
            transform: function(value) { return String(value).toUpperCase(); }
        },
        'lowercase': {
            label: 'Minúsculas',
            transform: function(value) { return String(value).toLowerCase(); }
        },
        'capitalize': {
            label: 'Capitalizar',
            transform: function(value) {
                var str = String(value);
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        },
        'titlecase': {
            label: 'Título',
            transform: function(value) {
                return String(value).replace(/\w\S*/g, function(txt) {
                    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                });
            }
        },
        'currency': {
            label: 'Moneda',
            transform: function(value, args) {
                var numValue = parseFloat(value);
                if (isNaN(numValue)) return value;
                var symbol = args && args[0] || '€';
                return symbol + numValue.toFixed(2);
            }
        },
        'percent': {
            label: 'Porcentaje',
            transform: function(value, args) {
                var numValue = parseFloat(value);
                if (isNaN(numValue)) return value;
                var decimals = args && args[0] ? parseInt(args[0]) : 0;
                return (numValue * 100).toFixed(decimals) + '%';
            }
        },
        'date_format': {
            label: 'Formato fecha',
            transform: function(value, args) {
                var date = new Date(value);
                if (isNaN(date.getTime())) return value;
                var format = args && args[0] || 'DD/MM/YYYY';
                var day = String(date.getDate()).padStart(2, '0');
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var year = date.getFullYear();
                var hours = String(date.getHours()).padStart(2, '0');
                var minutes = String(date.getMinutes()).padStart(2, '0');
                return format
                    .replace('DD', day)
                    .replace('MM', month)
                    .replace('YYYY', year)
                    .replace('HH', hours)
                    .replace('mm', minutes);
            }
        },
        'time_ago': {
            label: 'Hace tiempo',
            transform: function(value) {
                var date = new Date(value);
                if (isNaN(date.getTime())) return value;
                var seconds = Math.floor((new Date() - date) / 1000);
                var interval = Math.floor(seconds / 31536000);
                if (interval >= 1) return 'hace ' + interval + (interval === 1 ? ' año' : ' años');
                interval = Math.floor(seconds / 2592000);
                if (interval >= 1) return 'hace ' + interval + (interval === 1 ? ' mes' : ' meses');
                interval = Math.floor(seconds / 86400);
                if (interval >= 1) return 'hace ' + interval + (interval === 1 ? ' día' : ' días');
                interval = Math.floor(seconds / 3600);
                if (interval >= 1) return 'hace ' + interval + (interval === 1 ? ' hora' : ' horas');
                interval = Math.floor(seconds / 60);
                if (interval >= 1) return 'hace ' + interval + (interval === 1 ? ' minuto' : ' minutos');
                return 'hace unos segundos';
            }
        },
        'truncate': {
            label: 'Truncar',
            transform: function(value, args) {
                var str = String(value);
                var length = args && args[0] ? parseInt(args[0]) : 50;
                var suffix = args && args[1] || '...';
                if (str.length <= length) return str;
                return str.slice(0, length) + suffix;
            }
        },
        'default': {
            label: 'Valor por defecto',
            transform: function(value, args) {
                var fallback = args && args[0] || '';
                return value || fallback;
            }
        },
        'json_stringify': {
            label: 'JSON',
            transform: function(value) { return JSON.stringify(value); }
        },
        'json_parse': {
            label: 'Parsear JSON',
            transform: function(value) {
                try {
                    return JSON.parse(value);
                } catch (e) {
                    return value;
                }
            }
        },
        'array_length': {
            label: 'Longitud lista',
            transform: function(value) {
                if (Array.isArray(value)) return value.length;
                if (typeof value === 'string') return value.length;
                return 0;
            }
        },
        'array_join': {
            label: 'Unir lista',
            transform: function(value, args) {
                if (!Array.isArray(value)) return value;
                var separator = args && args[0] || ', ';
                return value.join(separator);
            }
        },
        'array_first': {
            label: 'Primer elemento',
            transform: function(value) {
                if (Array.isArray(value) && value.length > 0) return value[0];
                return null;
            }
        },
        'array_last': {
            label: 'Último elemento',
            transform: function(value) {
                if (Array.isArray(value) && value.length > 0) return value[value.length - 1];
                return null;
            }
        },
        'math_round': {
            label: 'Redondear',
            transform: function(value) { return Math.round(Number(value)); }
        },
        'math_floor': {
            label: 'Redondear abajo',
            transform: function(value) { return Math.floor(Number(value)); }
        },
        'math_ceil': {
            label: 'Redondear arriba',
            transform: function(value) { return Math.ceil(Number(value)); }
        },
        'math_abs': {
            label: 'Valor absoluto',
            transform: function(value) { return Math.abs(Number(value)); }
        },
        'add': {
            label: 'Sumar',
            transform: function(value, args) {
                var amountToAdd = args && args[0] ? Number(args[0]) : 0;
                return Number(value) + amountToAdd;
            }
        },
        'subtract': {
            label: 'Restar',
            transform: function(value, args) {
                var amountToSubtract = args && args[0] ? Number(args[0]) : 0;
                return Number(value) - amountToSubtract;
            }
        },
        'multiply': {
            label: 'Multiplicar',
            transform: function(value, args) {
                var multiplier = args && args[0] ? Number(args[0]) : 1;
                return Number(value) * multiplier;
            }
        },
        'divide': {
            label: 'Dividir',
            transform: function(value, args) {
                var divisor = args && args[0] ? Number(args[0]) : 1;
                if (divisor === 0) return 0;
                return Number(value) / divisor;
            }
        },
        'modulo': {
            label: 'Módulo',
            transform: function(value, args) {
                var divisor = args && args[0] ? Number(args[0]) : 1;
                return Number(value) % divisor;
            }
        },
        'number_format': {
            label: 'Formato número',
            transform: function(value, args) {
                var numValue = Number(value);
                if (isNaN(numValue)) return value;
                var decimals = args && args[0] !== undefined ? parseInt(args[0]) : 2;
                var decimalSeparator = args && args[1] || ',';
                var thousandsSeparator = args && args[2] || '.';
                var parts = numValue.toFixed(decimals).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
                return parts.join(decimalSeparator);
            }
        },
        'strip_html': {
            label: 'Quitar HTML',
            transform: function(value) {
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = value;
                return tempDiv.textContent || tempDiv.innerText || '';
            }
        },
        'nl2br': {
            label: 'Saltos a BR',
            transform: function(value) {
                return String(value).replace(/\n/g, '<br>');
            }
        },
        'slugify': {
            label: 'Slug',
            transform: function(value) {
                return String(value)
                    .toLowerCase()
                    .replace(/[áàäâ]/g, 'a')
                    .replace(/[éèëê]/g, 'e')
                    .replace(/[íìïî]/g, 'i')
                    .replace(/[óòöô]/g, 'o')
                    .replace(/[úùüû]/g, 'u')
                    .replace(/ñ/g, 'n')
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        },
        'encode_uri': {
            label: 'Codificar URI',
            transform: function(value) { return encodeURIComponent(value); }
        },
        'decode_uri': {
            label: 'Decodificar URI',
            transform: function(value) { return decodeURIComponent(value); }
        },
        'trim': {
            label: 'Recortar espacios',
            transform: function(value) { return String(value).trim(); }
        },
        'reverse': {
            label: 'Invertir',
            transform: function(value) {
                if (Array.isArray(value)) return value.slice().reverse();
                return String(value).split('').reverse().join('');
            }
        },
        'keys': {
            label: 'Claves de objeto',
            transform: function(value) {
                if (typeof value === 'object' && value !== null) {
                    return Object.keys(value);
                }
                return [];
            }
        },
        'values': {
            label: 'Valores de objeto',
            transform: function(value) {
                if (typeof value === 'object' && value !== null) {
                    return Object.values(value);
                }
                return [];
            }
        }
    };

    // ============================================
    // SISTEMA DE VARIABLES
    // ============================================

    var VBPVariables = {
        /**
         * Estado interno de variables
         */
        _variables: {},
        _collections: {},
        _watchers: {},
        _globalWatchers: [],

        /**
         * Inicializar sistema de variables
         */
        init: function() {
            this._variables = {};
            this._collections = {};
            this._watchers = {};
            this._globalWatchers = [];

            // Cargar variables de WordPress si están disponibles
            this._loadWordPressVariables();

            // Cargar variables persistidas
            this._loadPersistedVariables();

            vbpLog.log('Sistema de variables inicializado');
        },

        /**
         * Crear una nueva variable
         */
        createVariable: function(config) {
            var variableId = config.id || 'var_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            var variableType = VARIABLE_TYPES[config.type];

            if (!variableType) {
                vbpLog.error('Tipo de variable no válido:', config.type);
                return null;
            }

            var newVariable = {
                id: variableId,
                name: config.name || variableId,
                type: config.type,
                value: config.value !== undefined ? config.value : variableType.defaultValue,
                scope: config.scope || 'page',
                description: config.description || '',
                readOnly: config.readOnly || false,
                createdAt: new Date().toISOString()
            };

            this._variables[variableId] = newVariable;

            // Persistir si es necesario
            if (VARIABLE_SCOPES[newVariable.scope].persist) {
                this._persistVariable(newVariable);
            }

            this._notifyChange(variableId, null, newVariable.value);

            return newVariable;
        },

        /**
         * Obtener valor de una variable
         */
        getVariable: function(nameOrId) {
            // Buscar por ID primero
            if (this._variables[nameOrId]) {
                return this._variables[nameOrId].value;
            }

            // Buscar por nombre
            for (var id in this._variables) {
                if (this._variables[id].name === nameOrId) {
                    return this._variables[id].value;
                }
            }

            // Variables especiales de WordPress
            if (nameOrId.indexOf('wp:') === 0) {
                return this._getWordPressVariable(nameOrId);
            }

            return undefined;
        },

        /**
         * Obtener objeto completo de variable
         */
        getVariableObject: function(nameOrId) {
            if (this._variables[nameOrId]) {
                return this._variables[nameOrId];
            }

            for (var id in this._variables) {
                if (this._variables[id].name === nameOrId) {
                    return this._variables[id];
                }
            }

            return null;
        },

        /**
         * Establecer valor de variable
         */
        setVariable: function(nameOrId, value) {
            var varObj = this.getVariableObject(nameOrId);

            if (!varObj) {
                // Crear variable automáticamente si no existe
                var inferredType = this._inferType(value);
                varObj = this.createVariable({
                    name: nameOrId,
                    type: inferredType,
                    value: value
                });
                return;
            }

            if (varObj.readOnly) {
                vbpLog.warn('Intentando modificar variable de solo lectura:', nameOrId);
                return;
            }

            var oldValue = varObj.value;
            varObj.value = value;

            // Persistir si es necesario
            if (VARIABLE_SCOPES[varObj.scope].persist) {
                this._persistVariable(varObj);
            }

            this._notifyChange(varObj.id, oldValue, value);
        },

        /**
         * Eliminar una variable
         */
        deleteVariable: function(nameOrId) {
            var varObj = this.getVariableObject(nameOrId);
            if (!varObj) return false;

            delete this._variables[varObj.id];
            delete this._watchers[varObj.id];

            // Eliminar de storage si estaba persistida
            if (VARIABLE_SCOPES[varObj.scope].persist) {
                this._unpersistVariable(varObj);
            }

            return true;
        },

        /**
         * Listar todas las variables
         */
        listVariables: function(scope) {
            var result = [];
            for (var id in this._variables) {
                if (!scope || this._variables[id].scope === scope) {
                    result.push(this._variables[id]);
                }
            }
            return result;
        },

        /**
         * Observar cambios en una variable
         */
        watch: function(nameOrId, callback) {
            var varObj = this.getVariableObject(nameOrId);
            var variableId = varObj ? varObj.id : nameOrId;

            if (!this._watchers[variableId]) {
                this._watchers[variableId] = [];
            }

            this._watchers[variableId].push(callback);

            // Devolver función para cancelar la observación
            return function() {
                var index = this._watchers[variableId].indexOf(callback);
                if (index > -1) {
                    this._watchers[variableId].splice(index, 1);
                }
            }.bind(this);
        },

        /**
         * Observar cualquier cambio de variable
         */
        watchAll: function(callback) {
            this._globalWatchers.push(callback);

            return function() {
                var index = this._globalWatchers.indexOf(callback);
                if (index > -1) {
                    this._globalWatchers.splice(index, 1);
                }
            }.bind(this);
        },

        /**
         * Crear una colección de datos
         */
        createCollection: function(config) {
            var collectionId = config.id || 'col_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            var newCollection = {
                id: collectionId,
                name: config.name || collectionId,
                schema: config.schema || {},
                data: config.data || [],
                source: config.source || 'local',
                sourceConfig: config.sourceConfig || {},
                createdAt: new Date().toISOString()
            };

            this._collections[collectionId] = newCollection;

            return newCollection;
        },

        /**
         * Obtener una colección
         */
        getCollection: function(nameOrId) {
            if (this._collections[nameOrId]) {
                return this._collections[nameOrId];
            }

            for (var id in this._collections) {
                if (this._collections[id].name === nameOrId) {
                    return this._collections[id];
                }
            }

            return null;
        },

        /**
         * Actualizar datos de una colección
         */
        updateCollectionData: function(nameOrId, data) {
            var collection = this.getCollection(nameOrId);
            if (!collection) return false;

            collection.data = data;
            this._notifyChange('collection:' + collection.id, null, data);
            return true;
        },

        /**
         * Añadir item a colección
         */
        addToCollection: function(nameOrId, item) {
            var collection = this.getCollection(nameOrId);
            if (!collection) return false;

            collection.data.push(item);
            this._notifyChange('collection:' + collection.id, null, collection.data);
            return true;
        },

        /**
         * Eliminar item de colección
         */
        removeFromCollection: function(nameOrId, index) {
            var collection = this.getCollection(nameOrId);
            if (!collection || index < 0 || index >= collection.data.length) return false;

            collection.data.splice(index, 1);
            this._notifyChange('collection:' + collection.id, null, collection.data);
            return true;
        },

        /**
         * Listar todas las colecciones
         */
        listCollections: function() {
            var result = [];
            for (var id in this._collections) {
                result.push(this._collections[id]);
            }
            return result;
        },

        /**
         * Exportar estado completo
         */
        exportState: function() {
            return {
                variables: JSON.parse(JSON.stringify(this._variables)),
                collections: JSON.parse(JSON.stringify(this._collections))
            };
        },

        /**
         * Importar estado
         */
        importState: function(state) {
            if (state.variables) {
                this._variables = state.variables;
            }
            if (state.collections) {
                this._collections = state.collections;
            }
        },

        // Métodos privados

        _inferType: function(value) {
            if (typeof value === 'boolean') return 'boolean';
            if (typeof value === 'number') return 'number';
            if (Array.isArray(value)) return 'array';
            if (value instanceof Date) return 'date';
            if (typeof value === 'object' && value !== null) return 'object';
            if (typeof value === 'string' && /^#[0-9A-Fa-f]{6}$/.test(value)) return 'color';
            return 'string';
        },

        _notifyChange: function(variableId, oldValue, newValue) {
            // Notificar watchers específicos
            if (this._watchers[variableId]) {
                this._watchers[variableId].forEach(function(callback) {
                    try {
                        callback(newValue, oldValue, variableId);
                    } catch (e) {
                        vbpLog.error('Error en watcher:', e);
                    }
                });
            }

            // Notificar watchers globales
            this._globalWatchers.forEach(function(callback) {
                try {
                    callback(variableId, newValue, oldValue);
                } catch (e) {
                    vbpLog.error('Error en watcher global:', e);
                }
            });

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:variable:changed', {
                detail: {
                    variableId: variableId,
                    oldValue: oldValue,
                    newValue: newValue
                }
            }));
        },

        _loadWordPressVariables: function() {
            if (typeof VBP_Config === 'undefined') return;

            // Variables de WordPress expuestas
            var wpVariables = {
                'wp:site_url': VBP_Config.siteUrl || '',
                'wp:rest_url': VBP_Config.restUrl || '',
                'wp:post_id': VBP_Config.postId || 0,
                'wp:user_id': VBP_Config.userId || 0,
                'wp:is_logged_in': VBP_Config.userId > 0,
                'wp:nonce': VBP_Config.restNonce || ''
            };

            for (var key in wpVariables) {
                this.createVariable({
                    id: key,
                    name: key,
                    type: this._inferType(wpVariables[key]),
                    value: wpVariables[key],
                    scope: 'global',
                    readOnly: true,
                    description: 'Variable de WordPress'
                });
            }
        },

        _getWordPressVariable: function(key) {
            var variable = this.getVariableObject(key);
            return variable ? variable.value : undefined;
        },

        _loadPersistedVariables: function() {
            // Cargar de localStorage
            try {
                var savedVars = localStorage.getItem('vbp_user_variables');
                if (savedVars) {
                    var parsed = JSON.parse(savedVars);
                    for (var id in parsed) {
                        if (!this._variables[id]) {
                            this._variables[id] = parsed[id];
                        }
                    }
                }
            } catch (e) {
                vbpLog.warn('Error cargando variables persistidas:', e);
            }

            // Cargar de sessionStorage
            try {
                var sessionVars = sessionStorage.getItem('vbp_session_variables');
                if (sessionVars) {
                    var parsedSession = JSON.parse(sessionVars);
                    for (var sessionId in parsedSession) {
                        if (!this._variables[sessionId]) {
                            this._variables[sessionId] = parsedSession[sessionId];
                        }
                    }
                }
            } catch (e) {
                vbpLog.warn('Error cargando variables de sesión:', e);
            }
        },

        _persistVariable: function(varObj) {
            var scopeConfig = VARIABLE_SCOPES[varObj.scope];
            if (!scopeConfig || !scopeConfig.persist) return;

            var storage = scopeConfig.storage === 'sessionStorage' ? sessionStorage : localStorage;
            var storageKey = scopeConfig.storage === 'sessionStorage' ? 'vbp_session_variables' : 'vbp_user_variables';

            try {
                var saved = storage.getItem(storageKey);
                var data = saved ? JSON.parse(saved) : {};
                data[varObj.id] = varObj;
                storage.setItem(storageKey, JSON.stringify(data));
            } catch (e) {
                vbpLog.warn('Error persistiendo variable:', e);
            }
        },

        _unpersistVariable: function(varObj) {
            var scopeConfig = VARIABLE_SCOPES[varObj.scope];
            if (!scopeConfig || !scopeConfig.persist) return;

            var storage = scopeConfig.storage === 'sessionStorage' ? sessionStorage : localStorage;
            var storageKey = scopeConfig.storage === 'sessionStorage' ? 'vbp_session_variables' : 'vbp_user_variables';

            try {
                var saved = storage.getItem(storageKey);
                if (saved) {
                    var data = JSON.parse(saved);
                    delete data[varObj.id];
                    storage.setItem(storageKey, JSON.stringify(data));
                }
            } catch (e) {
                vbpLog.warn('Error eliminando variable persistida:', e);
            }
        }
    };

    // ============================================
    // SISTEMA DE EXPRESIONES
    // ============================================

    var VBPExpressions = {
        /**
         * Cache de expresiones compiladas
         */
        _cache: {},

        /**
         * Evaluar una expresión
         * Soporta: {{variable}}, {{variable.property}}, {{variable | transform}}
         */
        evaluate: function(expression, context) {
            if (typeof expression !== 'string') {
                return expression;
            }

            context = context || {};

            // Si no tiene marcadores de expresión, devolver tal cual
            if (expression.indexOf('{{') === -1) {
                return expression;
            }

            var self = this;

            // Reemplazar expresiones
            return expression.replace(/\{\{(.+?)\}\}/g, function(match, expr) {
                return self._evaluateSingle(expr.trim(), context);
            });
        },

        /**
         * Evaluar una expresión única (sin marcadores)
         */
        _evaluateSingle: function(expr, context) {
            // Manejar ternario: condition ? valueTrue : valueFalse
            if (expr.indexOf('?') !== -1 && expr.indexOf(':') !== -1) {
                return this._evaluateTernary(expr, context);
            }

            // Manejar matemáticas: math: expression
            if (expr.indexOf('math:') === 0) {
                return this._evaluateMath(expr.substr(5).trim(), context);
            }

            // Manejar transforms con pipes: value | transform:arg
            if (expr.indexOf('|') !== -1) {
                return this._evaluateWithTransforms(expr, context);
            }

            // Manejar acceso a propiedades y arrays
            return this._resolveValue(expr, context);
        },

        /**
         * Evaluar expresión ternaria
         */
        _evaluateTernary: function(expr, context) {
            var parts = expr.split('?');
            if (parts.length !== 2) return expr;

            var condition = parts[0].trim();
            var values = parts[1].split(':');
            if (values.length !== 2) return expr;

            var conditionResult = this._resolveValue(condition, context);
            if (conditionResult) {
                return this._evaluateSingle(values[0].trim(), context);
            }
            return this._evaluateSingle(values[1].trim(), context);
        },

        /**
         * Evaluar expresión matemática
         */
        _evaluateMath: function(expr, context) {
            // Reemplazar variables en la expresión matemática
            var self = this;
            var resolvedExpr = expr.replace(/[a-zA-Z_][a-zA-Z0-9_.]*/g, function(match) {
                var value = self._resolveValue(match, context);
                return typeof value === 'number' ? value : 0;
            });

            // Evaluar de forma segura (solo operaciones matemáticas básicas)
            try {
                // Permitir solo números, operadores y paréntesis
                if (!/^[\d\s+\-*/%().]+$/.test(resolvedExpr)) {
                    return 0;
                }
                return Function('"use strict"; return (' + resolvedExpr + ')')();
            } catch (e) {
                vbpLog.warn('Error evaluando expresión matemática:', expr, e);
                return 0;
            }
        },

        /**
         * Evaluar con transforms (pipes)
         */
        _evaluateWithTransforms: function(expr, context) {
            var parts = expr.split('|');
            var value = this._resolveValue(parts[0].trim(), context);

            for (var i = 1; i < parts.length; i++) {
                var transformPart = parts[i].trim();
                var transformNameMatch = transformPart.match(/^([a-zA-Z_]+)/);
                if (!transformNameMatch) continue;

                var transformName = transformNameMatch[1];
                var transform = TRANSFORMS[transformName];
                if (!transform) continue;

                // Extraer argumentos si existen
                var argsString = transformPart.substr(transformName.length).trim();
                var args = [];
                if (argsString.indexOf(':') === 0) {
                    args = argsString.substr(1).split(',').map(function(arg) {
                        return arg.trim().replace(/^['"]|['"]$/g, '');
                    });
                }

                value = transform.transform(value, args);
            }

            return value;
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

            // Acceso a array: variable[index]
            var arrayMatch = path.match(/^(.+)\[(\d+)\]$/);
            if (arrayMatch) {
                var arrayValue = this._resolveValue(arrayMatch[1], context);
                if (Array.isArray(arrayValue)) {
                    return arrayValue[parseInt(arrayMatch[2])];
                }
                return undefined;
            }

            // Acceso a propiedad anidada: variable.property.subproperty
            var pathParts = path.split('.');
            var value;

            // Primero buscar en contexto
            if (context[pathParts[0]] !== undefined) {
                value = context[pathParts[0]];
            } else {
                // Buscar en variables
                value = VBPVariables.getVariable(pathParts[0]);
            }

            // Si es solo el nombre, devolver el valor
            if (pathParts.length === 1) {
                return value;
            }

            // Navegar propiedades anidadas
            for (var i = 1; i < pathParts.length && value !== undefined && value !== null; i++) {
                value = value[pathParts[i]];
            }

            return value;
        },

        /**
         * Compilar expresión para reutilización
         */
        compile: function(expression) {
            if (this._cache[expression]) {
                return this._cache[expression];
            }

            var self = this;
            var compiled = function(context) {
                return self.evaluate(expression, context);
            };

            this._cache[expression] = compiled;
            return compiled;
        },

        /**
         * Verificar si una cadena contiene expresiones
         */
        hasExpressions: function(str) {
            return typeof str === 'string' && str.indexOf('{{') !== -1;
        },

        /**
         * Extraer nombres de variables de una expresión
         */
        extractVariables: function(expression) {
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
        }
    };

    // ============================================
    // SISTEMA DE CONDICIONES
    // ============================================

    var VBPConditions = {
        /**
         * Evaluar un conjunto de condiciones
         */
        evaluate: function(conditions, context) {
            if (!conditions || !Array.isArray(conditions) || conditions.length === 0) {
                return true;
            }

            context = context || {};
            var self = this;

            // Por defecto, todas las condiciones deben cumplirse (AND)
            return conditions.every(function(condition) {
                return self._evaluateCondition(condition, context);
            });
        },

        /**
         * Evaluar condición individual
         */
        _evaluateCondition: function(condition, context) {
            var leftValue = VBPExpressions.evaluate('{{' + condition.variable + '}}', context);
            var operator = COMPARISON_OPERATORS[condition.operator];

            if (!operator) {
                vbpLog.warn('Operador desconocido:', condition.operator);
                return false;
            }

            var rightValue = condition.value;

            // Si el valor es una expresión, evaluarla
            if (typeof rightValue === 'string' && VBPExpressions.hasExpressions(rightValue)) {
                rightValue = VBPExpressions.evaluate(rightValue, context);
            }

            return operator.evaluate(leftValue, rightValue);
        },

        /**
         * Evaluar condiciones con lógica OR
         */
        evaluateOr: function(conditions, context) {
            if (!conditions || !Array.isArray(conditions) || conditions.length === 0) {
                return true;
            }

            context = context || {};
            var self = this;

            return conditions.some(function(condition) {
                return self._evaluateCondition(condition, context);
            });
        },

        /**
         * Evaluar grupos de condiciones (AND/OR anidados)
         */
        evaluateGroups: function(groups, context) {
            if (!groups || !Array.isArray(groups) || groups.length === 0) {
                return true;
            }

            context = context || {};
            var self = this;

            return groups.every(function(group) {
                if (group.logic === 'or') {
                    return self.evaluateOr(group.conditions, context);
                }
                return self.evaluate(group.conditions, context);
            });
        }
    };

    // ============================================
    // SISTEMA DE BINDINGS
    // ============================================

    var VBPBindings = {
        /**
         * Bindings registrados por elemento
         */
        _bindings: {},
        _unsubscribers: {},

        /**
         * Vincular propiedad de elemento a variable o expresión
         */
        bind: function(elementId, property, binding) {
            if (!this._bindings[elementId]) {
                this._bindings[elementId] = {};
            }

            this._bindings[elementId][property] = {
                type: binding.type || 'variable',
                source: binding.variableId || binding.expression,
                transform: binding.transform || null,
                twoWay: binding.twoWay || false
            };

            // Configurar watcher
            this._setupWatcher(elementId, property);

            // Aplicar valor inicial
            this._applyBinding(elementId, property);

            vbpLog.log('Binding creado:', elementId, property, binding);
        },

        /**
         * Desvincular propiedad
         */
        unbind: function(elementId, property) {
            if (!this._bindings[elementId]) return;

            delete this._bindings[elementId][property];

            // Cancelar watcher
            if (this._unsubscribers[elementId] && this._unsubscribers[elementId][property]) {
                this._unsubscribers[elementId][property]();
                delete this._unsubscribers[elementId][property];
            }

            // Limpiar si no hay más bindings
            if (Object.keys(this._bindings[elementId]).length === 0) {
                delete this._bindings[elementId];
            }
        },

        /**
         * Obtener bindings de un elemento
         */
        getBindings: function(elementId) {
            return this._bindings[elementId] || {};
        },

        /**
         * Verificar si un elemento tiene bindings
         */
        hasBindings: function(elementId) {
            return this._bindings[elementId] && Object.keys(this._bindings[elementId]).length > 0;
        },

        /**
         * Aplicar todos los bindings de un elemento
         */
        applyAll: function(elementId) {
            var bindings = this._bindings[elementId];
            if (!bindings) return;

            for (var property in bindings) {
                this._applyBinding(elementId, property);
            }
        },

        /**
         * Refrescar todos los bindings
         */
        refreshAll: function() {
            for (var elementId in this._bindings) {
                this.applyAll(elementId);
            }
        },

        // Métodos privados

        _setupWatcher: function(elementId, property) {
            var binding = this._bindings[elementId][property];
            var self = this;

            if (!this._unsubscribers[elementId]) {
                this._unsubscribers[elementId] = {};
            }

            // Cancelar watcher anterior si existe
            if (this._unsubscribers[elementId][property]) {
                this._unsubscribers[elementId][property]();
            }

            if (binding.type === 'variable') {
                // Observar variable
                this._unsubscribers[elementId][property] = VBPVariables.watch(binding.source, function() {
                    self._applyBinding(elementId, property);
                });
            } else if (binding.type === 'expression') {
                // Observar todas las variables en la expresión
                var variables = VBPExpressions.extractVariables(binding.source);
                var unsubscribers = [];

                variables.forEach(function(varName) {
                    unsubscribers.push(VBPVariables.watch(varName, function() {
                        self._applyBinding(elementId, property);
                    }));
                });

                this._unsubscribers[elementId][property] = function() {
                    unsubscribers.forEach(function(unsub) { unsub(); });
                };
            }
        },

        _applyBinding: function(elementId, property) {
            var binding = this._bindings[elementId][property];
            if (!binding) return;

            var value;

            if (binding.type === 'variable') {
                value = VBPVariables.getVariable(binding.source);
            } else if (binding.type === 'expression') {
                value = VBPExpressions.evaluate(binding.source);
            }

            // Aplicar transform si existe
            if (binding.transform && TRANSFORMS[binding.transform]) {
                value = TRANSFORMS[binding.transform].transform(value);
            }

            // Notificar al store de VBP
            var store = Alpine.store('vbp');
            if (store) {
                var element = store.getElementDeep(elementId);
                if (element) {
                    // Actualizar propiedad en data o styles según corresponda
                    if (property.indexOf('styles.') === 0) {
                        var stylePath = property.replace('styles.', '');
                        var styles = JSON.parse(JSON.stringify(element.styles || {}));
                        this._setNestedValue(styles, stylePath, value);
                        store.updateElement(elementId, { styles: styles });
                    } else if (property.indexOf('data.') === 0) {
                        var dataPath = property.replace('data.', '');
                        var data = JSON.parse(JSON.stringify(element.data || {}));
                        this._setNestedValue(data, dataPath, value);
                        store.updateElement(elementId, { data: data });
                    } else {
                        var update = {};
                        update[property] = value;
                        store.updateElement(elementId, update);
                    }
                }
            }

            // También actualizar DOM directamente para propiedades visuales
            var domElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (domElement) {
                this._applyToDom(domElement, property, value);
            }
        },

        _applyToDom: function(element, property, value) {
            switch (property) {
                case 'text':
                case 'data.text':
                case 'data.content':
                    element.textContent = value;
                    break;
                case 'visible':
                    element.style.display = value ? '' : 'none';
                    break;
                case 'className':
                    element.className = value;
                    break;
                case 'styles.colors.background':
                    element.style.backgroundColor = value;
                    break;
                case 'styles.colors.text':
                    element.style.color = value;
                    break;
                // Más casos según sea necesario
            }
        },

        _setNestedValue: function(obj, path, value) {
            var parts = path.split('.');
            var current = obj;
            for (var i = 0; i < parts.length - 1; i++) {
                if (current[parts[i]] === undefined) {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }
            current[parts[parts.length - 1]] = value;
        }
    };

    // ============================================
    // SISTEMA DE LOOPS
    // ============================================

    var VBPLoops = {
        /**
         * Loops configurados por elemento
         */
        _loops: {},

        /**
         * Configurar loop para un elemento
         */
        configure: function(elementId, config) {
            this._loops[elementId] = {
                collection: config.collection,
                itemVariable: config.itemVariable || 'item',
                indexVariable: config.indexVariable || 'index',
                keyProperty: config.keyProperty || 'id',
                template: config.template || null
            };

            vbpLog.log('Loop configurado:', elementId, config);
        },

        /**
         * Obtener configuración de loop
         */
        getConfig: function(elementId) {
            return this._loops[elementId] || null;
        },

        /**
         * Verificar si elemento tiene loop
         */
        hasLoop: function(elementId) {
            return !!this._loops[elementId];
        },

        /**
         * Eliminar configuración de loop
         */
        remove: function(elementId) {
            delete this._loops[elementId];
        },

        /**
         * Renderizar elementos de loop
         */
        render: function(elementId) {
            var config = this._loops[elementId];
            if (!config) return [];

            var collection = VBPVariables.getCollection(config.collection);
            if (!collection) {
                // Intentar como variable normal
                var variableValue = VBPVariables.getVariable(config.collection);
                if (Array.isArray(variableValue)) {
                    collection = { data: variableValue };
                } else {
                    return [];
                }
            }

            var items = collection.data || [];
            var result = [];

            for (var i = 0; i < items.length; i++) {
                var context = {};
                context[config.itemVariable] = items[i];
                context[config.indexVariable] = i;

                result.push({
                    index: i,
                    key: items[i][config.keyProperty] || i,
                    item: items[i],
                    context: context
                });
            }

            return result;
        },

        /**
         * Obtener contexto para un índice específico
         */
        getItemContext: function(elementId, index) {
            var config = this._loops[elementId];
            if (!config) return {};

            var items = this.render(elementId);
            if (index < 0 || index >= items.length) return {};

            return items[index].context;
        }
    };

    // ============================================
    // SISTEMA DE ACCIONES
    // ============================================

    var VBPActions = {
        /**
         * Eventos configurados por elemento
         */
        _events: {},

        /**
         * Configurar eventos para un elemento
         */
        configure: function(elementId, events) {
            this._events[elementId] = events;
            this._bindEvents(elementId);
        },

        /**
         * Obtener eventos de un elemento
         */
        getEvents: function(elementId) {
            return this._events[elementId] || {};
        },

        /**
         * Verificar si elemento tiene eventos
         */
        hasEvents: function(elementId) {
            return this._events[elementId] && Object.keys(this._events[elementId]).length > 0;
        },

        /**
         * Eliminar eventos de un elemento
         */
        remove: function(elementId) {
            this._unbindEvents(elementId);
            delete this._events[elementId];
        },

        /**
         * Ejecutar una lista de acciones
         */
        executeActions: function(actions, context) {
            if (!actions || !Array.isArray(actions)) return;

            context = context || {};
            var self = this;

            // Ejecutar secuencialmente
            var executeNext = function(index) {
                if (index >= actions.length) return Promise.resolve();

                var actionConfig = actions[index];
                var actionDef = ACTION_TYPES[actionConfig.action];

                if (!actionDef) {
                    vbpLog.warn('Acción desconocida:', actionConfig.action);
                    return executeNext(index + 1);
                }

                var result = actionDef.execute(actionConfig, context);

                if (result && typeof result.then === 'function') {
                    return result.then(function() {
                        return executeNext(index + 1);
                    });
                }

                return executeNext(index + 1);
            };

            return executeNext(0);
        },

        // Métodos privados

        _bindEvents: function(elementId) {
            var self = this;
            var element = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!element) return;

            var events = this._events[elementId];

            for (var eventName in events) {
                (function(name, actions) {
                    element.addEventListener(name, function(event) {
                        var context = {
                            event: event,
                            target: event.target,
                            elementId: elementId
                        };
                        self.executeActions(actions, context);
                    });
                })(eventName, events[eventName]);
            }
        },

        _unbindEvents: function(elementId) {
            // Los event listeners se limpian cuando el elemento se remueve del DOM
            // Para una limpieza más precisa, necesitaríamos guardar referencias a los handlers
        }
    };

    // ============================================
    // SISTEMA DE ESTADOS DE COMPONENTE
    // ============================================

    var VBPComponentStates = {
        /**
         * Estados por elemento
         */
        _states: {},

        /**
         * Configurar estados para un elemento
         */
        configure: function(elementId, states) {
            this._states[elementId] = {
                definitions: states,
                current: 'default'
            };
        },

        /**
         * Obtener estado actual
         */
        getCurrentState: function(elementId) {
            var config = this._states[elementId];
            return config ? config.current : 'default';
        },

        /**
         * Obtener definición de estado
         */
        getStateDefinition: function(elementId, stateName) {
            var config = this._states[elementId];
            if (!config || !config.definitions) return null;
            return config.definitions[stateName] || null;
        },

        /**
         * Cambiar a un estado
         */
        setState: function(elementId, stateName) {
            var config = this._states[elementId];
            if (!config) return;

            if (!config.definitions[stateName]) {
                vbpLog.warn('Estado no definido:', stateName);
                return;
            }

            var previousState = config.current;
            config.current = stateName;

            this._applyState(elementId, stateName);

            document.dispatchEvent(new CustomEvent('vbp:state:changed', {
                detail: {
                    elementId: elementId,
                    previousState: previousState,
                    currentState: stateName
                }
            }));
        },

        /**
         * Verificar si elemento tiene estados
         */
        hasStates: function(elementId) {
            return !!this._states[elementId];
        },

        /**
         * Listar estados disponibles
         */
        listStates: function(elementId) {
            var config = this._states[elementId];
            if (!config || !config.definitions) return [];
            return Object.keys(config.definitions);
        },

        // Métodos privados

        _applyState: function(elementId, stateName) {
            var definition = this.getStateDefinition(elementId, stateName);
            if (!definition) return;

            var element = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!element) return;

            // Aplicar estilos del estado
            for (var prop in definition) {
                element.style[prop] = definition[prop];
            }
        }
    };

    // ============================================
    // INTEGRACIÓN CON ALPINE.JS STORE
    // ============================================

    document.addEventListener('alpine:init', function() {
        // Inicializar sistema de variables
        VBPVariables.init();

        // Extender el store de VBP con el sistema de lógica
        var existingStore = Alpine.store('vbp');
        if (existingStore) {
            // Añadir métodos de lógica al store existente
            existingStore.logic = {
                // Variables
                variables: VBPVariables,
                getVariable: VBPVariables.getVariable.bind(VBPVariables),
                setVariable: VBPVariables.setVariable.bind(VBPVariables),
                createVariable: VBPVariables.createVariable.bind(VBPVariables),
                deleteVariable: VBPVariables.deleteVariable.bind(VBPVariables),
                listVariables: VBPVariables.listVariables.bind(VBPVariables),
                watchVariable: VBPVariables.watch.bind(VBPVariables),

                // Colecciones
                collections: VBPVariables,
                createCollection: VBPVariables.createCollection.bind(VBPVariables),
                getCollection: VBPVariables.getCollection.bind(VBPVariables),
                listCollections: VBPVariables.listCollections.bind(VBPVariables),

                // Expresiones
                expressions: VBPExpressions,
                evaluateExpression: VBPExpressions.evaluate.bind(VBPExpressions),

                // Condiciones
                conditions: VBPConditions,
                evaluateConditions: VBPConditions.evaluate.bind(VBPConditions),

                // Bindings
                bindings: VBPBindings,
                bindProperty: VBPBindings.bind.bind(VBPBindings),
                unbindProperty: VBPBindings.unbind.bind(VBPBindings),
                getBindings: VBPBindings.getBindings.bind(VBPBindings),

                // Loops
                loops: VBPLoops,
                configureLoop: VBPLoops.configure.bind(VBPLoops),
                renderLoop: VBPLoops.render.bind(VBPLoops),

                // Acciones
                actions: VBPActions,
                configureEvents: VBPActions.configure.bind(VBPActions),
                executeActions: VBPActions.executeActions.bind(VBPActions),

                // Estados de componente
                componentStates: VBPComponentStates,
                configureStates: VBPComponentStates.configure.bind(VBPComponentStates),
                setComponentState: VBPComponentStates.setState.bind(VBPComponentStates),
                getComponentState: VBPComponentStates.getCurrentState.bind(VBPComponentStates),

                // Tipos y constantes
                VARIABLE_TYPES: VARIABLE_TYPES,
                VARIABLE_SCOPES: VARIABLE_SCOPES,
                COMPARISON_OPERATORS: COMPARISON_OPERATORS,
                ACTION_TYPES: ACTION_TYPES,
                TRANSFORMS: TRANSFORMS
            };

            vbpLog.log('Sistema de lógica integrado con Alpine store');
        }
    });

    // ============================================
    // EXPORTAR A WINDOW
    // ============================================

    window.VBPVariables = VBPVariables;
    window.VBPExpressions = VBPExpressions;
    window.VBPConditions = VBPConditions;
    window.VBPBindings = VBPBindings;
    window.VBPLoops = VBPLoops;
    window.VBPActions = VBPActions;
    window.VBPComponentStates = VBPComponentStates;

    // Constantes
    window.VBP_VARIABLE_TYPES = VARIABLE_TYPES;
    window.VBP_VARIABLE_SCOPES = VARIABLE_SCOPES;
    window.VBP_COMPARISON_OPERATORS = COMPARISON_OPERATORS;
    window.VBP_ACTION_TYPES = ACTION_TYPES;
    window.VBP_TRANSFORMS = TRANSFORMS;

    vbpLog.log('VBP Variables System loaded');

})();
