/**
 * Flavor Form Validation
 * Sistema de validacion de formularios para Flavor Chat IA
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */
(function() {
    'use strict';

    /**
     * Sistema global de validacion de formularios
     * @namespace FlavorValidation
     */
    window.FlavorValidation = {
        /**
         * Configuracion por defecto
         */
        config: {
            errorClass: 'flavor-input--error',
            successClass: 'flavor-input--success',
            errorMessageClass: 'flavor-field-error',
            validateAttribute: 'data-validate',
            formAttribute: 'data-flavor-validate',
            debounceDelay: 300
        },

        /**
         * Mensajes de error personalizables
         */
        messages: {
            required: 'Este campo es requerido',
            email: 'Por favor, introduce un email valido',
            min: 'Debe tener al menos {param} caracteres',
            max: 'No puede exceder {param} caracteres',
            numeric: 'Solo se permiten numeros',
            url: 'Por favor, introduce una URL valida',
            phone: 'Por favor, introduce un telefono valido',
            date: 'Por favor, introduce una fecha valida',
            match: 'Los campos no coinciden',
            pattern: 'El formato no es valido',
            minValue: 'El valor minimo es {param}',
            maxValue: 'El valor maximo es {param}',
            integer: 'Debe ser un numero entero',
            alphanumeric: 'Solo se permiten letras y numeros',
            noSpaces: 'No se permiten espacios',
            fileSize: 'El archivo excede el tamano maximo de {param}MB',
            fileType: 'Tipo de archivo no permitido'
        },

        /**
         * Inicializa el sistema de validacion
         * @param {Object} customConfig - Configuracion personalizada opcional
         */
        init: function(customConfig) {
            if (customConfig) {
                this.config = Object.assign({}, this.config, customConfig);
            }
            this.setupForms();
            this.setupRealTimeValidation();
            this.setupCustomValidators();
        },

        /**
         * Configura los formularios para validacion en submit
         */
        setupForms: function() {
            const validationForms = document.querySelectorAll('form[' + this.config.formAttribute + ']');
            validationForms.forEach(form => {
                form.addEventListener('submit', this.handleSubmit.bind(this));
                form.setAttribute('novalidate', 'true');
            });
        },

        /**
         * Configura la validacion en tiempo real
         */
        setupRealTimeValidation: function() {
            const validateFields = document.querySelectorAll('[' + this.config.validateAttribute + ']');
            validateFields.forEach(input => {
                // Validar al perder el foco
                input.addEventListener('blur', () => this.validateField(input));

                // Limpiar error al escribir (con debounce)
                let debounceTimer;
                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        if (input.classList.contains(this.config.errorClass)) {
                            this.validateField(input);
                        } else {
                            this.clearError(input);
                        }
                    }, this.config.debounceDelay);
                });

                // Manejar cambios en selects y checkboxes
                if (input.type === 'checkbox' || input.type === 'radio' || input.tagName === 'SELECT') {
                    input.addEventListener('change', () => this.validateField(input));
                }
            });
        },

        /**
         * Registra validadores personalizados
         */
        setupCustomValidators: function() {
            this.customValidators = {};
        },

        /**
         * Registra un validador personalizado
         * @param {string} validatorName - Nombre del validador
         * @param {Function} validatorFunction - Funcion de validacion
         * @param {string} validatorMessage - Mensaje de error
         */
        registerValidator: function(validatorName, validatorFunction, validatorMessage) {
            this.customValidators[validatorName] = {
                validate: validatorFunction,
                message: validatorMessage
            };
        },

        /**
         * Valida un campo individual
         * @param {HTMLElement} field - Campo a validar
         * @returns {boolean} - true si es valido
         */
        validateField: function(field) {
            const validationRules = field.getAttribute(this.config.validateAttribute);
            if (!validationRules) return true;

            const rules = validationRules.split('|');
            const fieldValue = this.getFieldValue(field);

            for (const rule of rules) {
                const validationError = this.checkRule(rule, fieldValue, field);
                if (validationError) {
                    this.showError(field, validationError);
                    return false;
                }
            }

            this.showSuccess(field);
            return true;
        },

        /**
         * Obtiene el valor de un campo
         * @param {HTMLElement} field - Campo
         * @returns {string|boolean|Array} - Valor del campo
         */
        getFieldValue: function(field) {
            if (field.type === 'checkbox') {
                return field.checked;
            }
            if (field.type === 'radio') {
                const radioGroup = document.querySelectorAll('input[name="' + field.name + '"]');
                for (const radio of radioGroup) {
                    if (radio.checked) return radio.value;
                }
                return '';
            }
            if (field.type === 'file') {
                return field.files;
            }
            if (field.tagName === 'SELECT' && field.multiple) {
                return Array.from(field.selectedOptions).map(opt => opt.value);
            }
            return field.value.trim();
        },

        /**
         * Verifica una regla de validacion
         * @param {string} rule - Regla a verificar
         * @param {*} value - Valor del campo
         * @param {HTMLElement} field - Campo
         * @returns {string|null} - Mensaje de error o null si es valido
         */
        checkRule: function(rule, value, field) {
            const [ruleName, ruleParam] = rule.split(':');

            // Validadores integrados
            switch(ruleName) {
                case 'required':
                    if (field.type === 'checkbox' && !value) {
                        return this.messages.required;
                    }
                    if (field.type === 'file' && (!value || value.length === 0)) {
                        return this.messages.required;
                    }
                    if (Array.isArray(value) && value.length === 0) {
                        return this.messages.required;
                    }
                    if (typeof value === 'string' && !value) {
                        return this.messages.required;
                    }
                    break;

                case 'email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        return this.messages.email;
                    }
                    break;

                case 'min':
                    if (value && value.length < parseInt(ruleParam, 10)) {
                        return this.messages.min.replace('{param}', ruleParam);
                    }
                    break;

                case 'max':
                    if (value && value.length > parseInt(ruleParam, 10)) {
                        return this.messages.max.replace('{param}', ruleParam);
                    }
                    break;

                case 'numeric':
                    if (value && !/^\d+$/.test(value)) {
                        return this.messages.numeric;
                    }
                    break;

                case 'integer':
                    if (value && !/^-?\d+$/.test(value)) {
                        return this.messages.integer;
                    }
                    break;

                case 'url':
                    if (value && !/^https?:\/\/[^\s]+$/.test(value)) {
                        return this.messages.url;
                    }
                    break;

                case 'phone':
                    if (value && !/^[\d\s\-\+\(\)]{9,20}$/.test(value)) {
                        return this.messages.phone;
                    }
                    break;

                case 'date':
                    if (value && isNaN(Date.parse(value))) {
                        return this.messages.date;
                    }
                    break;

                case 'match':
                    const matchField = document.querySelector('[name="' + ruleParam + '"]');
                    if (matchField && value !== matchField.value) {
                        return this.messages.match;
                    }
                    break;

                case 'pattern':
                    try {
                        const patternRegex = new RegExp(ruleParam);
                        if (value && !patternRegex.test(value)) {
                            return this.messages.pattern;
                        }
                    } catch (patternError) {
                        console.error('FlavorValidation: Patron invalido', ruleParam);
                    }
                    break;

                case 'minValue':
                    if (value && parseFloat(value) < parseFloat(ruleParam)) {
                        return this.messages.minValue.replace('{param}', ruleParam);
                    }
                    break;

                case 'maxValue':
                    if (value && parseFloat(value) > parseFloat(ruleParam)) {
                        return this.messages.maxValue.replace('{param}', ruleParam);
                    }
                    break;

                case 'alphanumeric':
                    if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
                        return this.messages.alphanumeric;
                    }
                    break;

                case 'noSpaces':
                    if (value && /\s/.test(value)) {
                        return this.messages.noSpaces;
                    }
                    break;

                case 'fileSize':
                    if (value && value.length > 0) {
                        const maxSizeBytes = parseFloat(ruleParam) * 1024 * 1024;
                        for (const file of value) {
                            if (file.size > maxSizeBytes) {
                                return this.messages.fileSize.replace('{param}', ruleParam);
                            }
                        }
                    }
                    break;

                case 'fileType':
                    if (value && value.length > 0) {
                        const allowedTypes = ruleParam.split(',');
                        for (const file of value) {
                            const fileExtension = file.name.split('.').pop().toLowerCase();
                            if (!allowedTypes.includes(fileExtension)) {
                                return this.messages.fileType;
                            }
                        }
                    }
                    break;

                default:
                    // Verificar validadores personalizados
                    if (this.customValidators[ruleName]) {
                        const customValidator = this.customValidators[ruleName];
                        if (!customValidator.validate(value, ruleParam, field)) {
                            return customValidator.message.replace('{param}', ruleParam);
                        }
                    }
                    break;
            }

            return null;
        },

        /**
         * Muestra un error en el campo
         * @param {HTMLElement} field - Campo
         * @param {string} errorMessage - Mensaje de error
         */
        showError: function(field, errorMessage) {
            this.clearError(field);
            field.classList.add(this.config.errorClass);
            field.classList.remove(this.config.successClass);
            field.setAttribute('aria-invalid', 'true');

            const errorElement = document.createElement('span');
            errorElement.className = this.config.errorMessageClass;
            errorElement.setAttribute('role', 'alert');
            errorElement.setAttribute('aria-live', 'polite');
            errorElement.textContent = errorMessage;

            // Generar ID unico para el error
            const errorId = 'error-' + (field.id || field.name || Math.random().toString(36).substr(2, 9));
            errorElement.id = errorId;
            field.setAttribute('aria-describedby', errorId);

            // Insertar despues del campo o su contenedor
            const fieldContainer = field.closest('.flavor-field') || field.parentNode;
            fieldContainer.appendChild(errorElement);

            // Disparar evento personalizado
            field.dispatchEvent(new CustomEvent('flavor:validation:error', {
                detail: { message: errorMessage, field: field },
                bubbles: true
            }));
        },

        /**
         * Muestra indicador de exito en el campo
         * @param {HTMLElement} field - Campo
         */
        showSuccess: function(field) {
            this.clearError(field);
            field.classList.add(this.config.successClass);
            field.classList.remove(this.config.errorClass);
            field.setAttribute('aria-invalid', 'false');

            // Disparar evento personalizado
            field.dispatchEvent(new CustomEvent('flavor:validation:success', {
                detail: { field: field },
                bubbles: true
            }));
        },

        /**
         * Limpia errores de un campo
         * @param {HTMLElement} field - Campo
         */
        clearError: function(field) {
            field.classList.remove(this.config.errorClass, this.config.successClass);
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');

            const fieldContainer = field.closest('.flavor-field') || field.parentNode;
            const existingError = fieldContainer.querySelector('.' + this.config.errorMessageClass);
            if (existingError) {
                existingError.remove();
            }
        },

        /**
         * Maneja el envio del formulario
         * @param {Event} submitEvent - Evento submit
         */
        handleSubmit: function(submitEvent) {
            const form = submitEvent.target;
            let isFormValid = true;
            const validationErrors = [];

            const formFields = form.querySelectorAll('[' + this.config.validateAttribute + ']');
            formFields.forEach(field => {
                if (!this.validateField(field)) {
                    isFormValid = false;
                    validationErrors.push({
                        field: field,
                        name: field.name || field.id
                    });
                }
            });

            if (!isFormValid) {
                submitEvent.preventDefault();

                // Enfocar primer campo con error
                const firstErrorField = form.querySelector('.' + this.config.errorClass);
                if (firstErrorField) {
                    firstErrorField.focus();
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Disparar evento de formulario invalido
                form.dispatchEvent(new CustomEvent('flavor:validation:invalid', {
                    detail: { errors: validationErrors },
                    bubbles: true
                }));
            } else {
                // Disparar evento de formulario valido
                form.dispatchEvent(new CustomEvent('flavor:validation:valid', {
                    detail: { form: form },
                    bubbles: true
                }));
            }
        },

        /**
         * Valida todo el formulario programaticamente
         * @param {HTMLElement|string} form - Formulario o selector
         * @returns {boolean} - true si es valido
         */
        validateForm: function(form) {
            if (typeof form === 'string') {
                form = document.querySelector(form);
            }
            if (!form) return false;

            let isFormValid = true;
            const formFields = form.querySelectorAll('[' + this.config.validateAttribute + ']');
            formFields.forEach(field => {
                if (!this.validateField(field)) {
                    isFormValid = false;
                }
            });
            return isFormValid;
        },

        /**
         * Resetea la validacion de un formulario
         * @param {HTMLElement|string} form - Formulario o selector
         */
        resetValidation: function(form) {
            if (typeof form === 'string') {
                form = document.querySelector(form);
            }
            if (!form) return;

            const formFields = form.querySelectorAll('[' + this.config.validateAttribute + ']');
            formFields.forEach(field => {
                this.clearError(field);
            });
        },

        /**
         * Actualiza los mensajes de error
         * @param {Object} customMessages - Mensajes personalizados
         */
        setMessages: function(customMessages) {
            this.messages = Object.assign({}, this.messages, customMessages);
        },

        /**
         * Obtiene el estado de validacion de un campo
         * @param {HTMLElement} field - Campo
         * @returns {Object} - Estado del campo
         */
        getFieldState: function(field) {
            return {
                isValid: !field.classList.contains(this.config.errorClass),
                hasSuccess: field.classList.contains(this.config.successClass),
                hasError: field.classList.contains(this.config.errorClass)
            };
        }
    };

    // Inicializar automaticamente cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            FlavorValidation.init();
        });
    } else {
        FlavorValidation.init();
    }

})();
