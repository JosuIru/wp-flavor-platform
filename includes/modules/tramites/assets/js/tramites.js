/**
 * Flavor Chat IA - Modulo Tramites
 * JavaScript del sistema de tramites online
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // Configuracion global
    const CONFIG = window.flavorTramitesConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        restUrl: '/wp-json/flavor-tramites/v1/',
        nonce: '',
        maxFileSize: 10485760,
        allowedTypes: ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        maxFiles: 20,
        i18n: {}
    };

    // Utilidades
    const Utils = {
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const kilobytes = 1024;
            const tamanios = ['Bytes', 'KB', 'MB', 'GB'];
            const indice = Math.floor(Math.log(bytes) / Math.log(kilobytes));
            return parseFloat((bytes / Math.pow(kilobytes, indice)).toFixed(decimals)) + ' ' + tamanios[indice];
        },

        formatDate: function(dateString) {
            const fecha = new Date(dateString);
            return fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getFileExtension: function(filename) {
            return filename.split('.').pop().toLowerCase();
        },

        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        isValidDNI: function(dni) {
            const dniRegex = /^[0-9]{8}[A-Za-z]$/;
            const nieRegex = /^[XYZxyz][0-9]{7}[A-Za-z]$/;
            return dniRegex.test(dni) || nieRegex.test(dni);
        },

        isValidPhone: function(phone) {
            const regex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s./0-9]{6,}$/;
            return regex.test(phone);
        },

        showLoading: function(container) {
            const loadingHtml = '<div class="flavor-loading">' + (CONFIG.i18n.loading || 'Cargando...') + '</div>';
            $(container).html(loadingHtml);
        },

        showError: function(container, message) {
            const errorHtml = '<div class="flavor-error">' + message + '</div>';
            $(container).html(errorHtml);
        },

        showSuccess: function(container, message) {
            const successHtml = '<div class="flavor-success">' + message + '</div>';
            $(container).html(successHtml);
        },

        showNotice: function(container, message, type = 'info') {
            const $container = $(container);
            if (!$container.length) {
                return;
            }

            $container.find('.flavor-inline-notice').remove();

            const cssClass = 'flavor-inline-notice flavor-inline-notice--' + type;
            const noticeHtml =
                '<div class="' + cssClass + '" role="status" aria-live="polite">' +
                    '<span class="flavor-inline-notice__message">' + message + '</span>' +
                    '<button type="button" class="flavor-inline-notice__close" aria-label="' + (CONFIG.i18n.close || 'Cerrar') + '">&times;</button>' +
                '</div>';

            const $notice = $(noticeHtml);
            $container.prepend($notice);

            $notice.on('click', '.flavor-inline-notice__close', function() {
                $notice.remove();
            });
        },

        scrollToElement: function(element, offset = 100) {
            const elementPosition = $(element).offset().top - offset;
            $('html, body').animate({ scrollTop: elementPosition }, 400);
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Modulo Catalogo
    const CatalogoTramites = {
        init: function() {
            this.container = $('.flavor-tramites-catalogo');
            if (!this.container.length) return;

            this.grid = this.container.find('.flavor-tramites-grid');
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Buscador
            $('#flavor-buscar-tramite').on('input', Utils.debounce(function() {
                self.filterTramites();
            }, 300));

            // Filtro por categoria
            this.container.on('click', '.flavor-categoria-btn', function() {
                $('.flavor-categoria-btn').removeClass('active');
                $(this).addClass('active');
                self.filterTramites();
            });
        },

        filterTramites: function() {
            const busqueda = $('#flavor-buscar-tramite').val().toLowerCase();
            const categoria = $('.flavor-categoria-btn.active').data('categoria') || '';

            this.grid.find('.flavor-tramite-card').each(function() {
                const card = $(this);
                const cardCategoria = card.data('categoria') || '';
                const titulo = card.find('.flavor-tramite-titulo').text().toLowerCase();
                const descripcion = card.find('.flavor-tramite-descripcion').text().toLowerCase();

                let mostrar = true;

                if (categoria && cardCategoria !== categoria) {
                    mostrar = false;
                }

                if (busqueda && titulo.indexOf(busqueda) === -1 && descripcion.indexOf(busqueda) === -1) {
                    mostrar = false;
                }

                card.toggle(mostrar);
            });

            // Mostrar mensaje si no hay resultados
            const visibles = this.grid.find('.flavor-tramite-card:visible').length;
            this.grid.find('.flavor-tramites-vacio').remove();

            if (visibles === 0) {
                this.grid.append('<p class="flavor-tramites-vacio">No se encontraron tramites que coincidan con tu busqueda.</p>');
            }
        }
    };

    // Modulo Formulario
    const FormularioTramite = {
        init: function() {
            this.container = $('.flavor-tramites-formulario');
            if (!this.container.length) return;

            this.form = $('#flavor-form-tramite');
            this.tipoId = this.container.data('tipo-id');
            this.archivosSubidos = {};

            this.bindEvents();
            this.initConditionalFields();
        },

        bindEvents: function() {
            const self = this;

            // Envio del formulario
            this.form.on('submit', function(e) {
                e.preventDefault();
                self.submitForm(false);
            });

            // Guardar borrador
            $('#flavor-guardar-borrador').on('click', function() {
                self.submitForm(true);
            });

            // Inputs de archivo
            this.form.on('change', '.flavor-file-input', function() {
                self.handleFileSelect(this);
            });

            // Validacion en tiempo real
            this.form.on('blur', 'input[required], select[required], textarea[required]', function() {
                self.validateField(this);
            });

            // Campos condicionales
            this.form.on('change', 'input, select', function() {
                self.checkConditionalFields();
            });
        },

        initConditionalFields: function() {
            this.checkConditionalFields();
        },

        checkConditionalFields: function() {
            const self = this;

            this.form.find('[data-condicion]').each(function() {
                const field = $(this);
                const condicion = field.data('condicion');

                if (condicion) {
                    const cumple = self.evaluateCondition(condicion);
                    field.toggle(cumple);

                    if (!cumple) {
                        field.find('input, select, textarea').prop('required', false);
                    }
                }
            });
        },

        evaluateCondition: function(condicion) {
            if (!condicion || !condicion.campo) return true;

            const valorCampo = this.form.find('[name="datos_formulario[' + condicion.campo + ']"]').val();
            const operador = condicion.operador || '==';
            const valorComparar = condicion.valor;

            switch (operador) {
                case '==':
                    return valorCampo == valorComparar;
                case '!=':
                    return valorCampo != valorComparar;
                case '>':
                    return parseFloat(valorCampo) > parseFloat(valorComparar);
                case '<':
                    return parseFloat(valorCampo) < parseFloat(valorComparar);
                case 'contains':
                    return valorCampo && valorCampo.indexOf(valorComparar) !== -1;
                case 'not_empty':
                    return valorCampo && valorCampo.length > 0;
                default:
                    return true;
            }
        },

        handleFileSelect: function(input) {
            const file = input.files[0];
            const documentItem = $(input).closest('.flavor-documento-item');
            const fileNameSpan = documentItem.find('.flavor-file-name');
            const tipoDocumento = documentItem.data('tipo');
            const noticeTarget = this.container.find('.flavor-form-header, .flavor-tramites-formulario').first();

            if (!file) {
                fileNameSpan.text('');
                delete this.archivosSubidos[tipoDocumento];
                return;
            }

            // Validar extension
            const extension = Utils.getFileExtension(file.name);
            if (CONFIG.allowedTypes.indexOf(extension) === -1) {
                Utils.showNotice(noticeTarget, CONFIG.i18n.invalidType || 'Tipo de archivo no permitido', 'error');
                input.value = '';
                fileNameSpan.text('');
                return;
            }

            // Validar tamanio
            if (file.size > CONFIG.maxFileSize) {
                Utils.showNotice(noticeTarget, CONFIG.i18n.fileTooBig || 'El archivo es demasiado grande', 'error');
                input.value = '';
                fileNameSpan.text('');
                return;
            }

            fileNameSpan.text(file.name + ' (' + Utils.formatBytes(file.size) + ')');
            this.archivosSubidos[tipoDocumento] = file;
        },

        validateField: function(field) {
            const $field = $(field);
            const value = $field.val();
            const type = $field.attr('type');
            let isValid = true;
            let errorMessage = '';

            // Requerido
            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = CONFIG.i18n.required || 'Este campo es obligatorio';
            }

            // Email
            if (isValid && type === 'email' && value && !Utils.isValidEmail(value)) {
                isValid = false;
                errorMessage = CONFIG.i18n.invalidEmail || 'Email no valido';
            }

            // Telefono
            if (isValid && type === 'tel' && value && !Utils.isValidPhone(value)) {
                isValid = false;
                errorMessage = CONFIG.i18n.invalidPhone || 'Telefono no valido';
            }

            // Pattern
            if (isValid && $field.attr('pattern') && value) {
                const pattern = new RegExp($field.attr('pattern'));
                if (!pattern.test(value)) {
                    isValid = false;
                    errorMessage = $field.data('error') || 'Formato no valido';
                }
            }

            // Mostrar/ocultar error
            $field.toggleClass('error', !isValid);

            let errorSpan = $field.siblings('.flavor-field-error');
            if (!isValid) {
                if (!errorSpan.length) {
                    errorSpan = $('<span class="flavor-field-error flavor-error"></span>');
                    $field.after(errorSpan);
                }
                errorSpan.text(errorMessage);
            } else {
                errorSpan.remove();
            }

            return isValid;
        },

        validateForm: function() {
            const self = this;
            let isValid = true;

            this.form.find('input[required]:visible, select[required]:visible, textarea[required]:visible').each(function() {
                if (!self.validateField(this)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        getFormData: function() {
            const formData = new FormData(this.form[0]);

            // Agregar datos del formulario dinamico
            const datosFormulario = {};
            this.form.find('[name^="datos_formulario"]').each(function() {
                const name = $(this).attr('name').match(/\[([^\]]+)\]/);
                if (name && name[1]) {
                    const value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                    datosFormulario[name[1]] = value;
                }
            });

            formData.set('datos_formulario', JSON.stringify(datosFormulario));

            return formData;
        },

        submitForm: function(esBorrador) {
            const self = this;

            // Validar si no es borrador
            if (!esBorrador && !this.validateForm()) {
                Utils.scrollToElement(this.form.find('.error').first());
                return;
            }

            // Confirmar envio
            if (!esBorrador && !confirm(CONFIG.i18n.confirmSubmit || 'Deseas enviar el tramite?')) {
                return;
            }

            const formData = this.getFormData();
            formData.append('action', 'flavor_tramites_action');
            formData.append('accion_tramites', 'crear_expediente');
            formData.append('nonce', CONFIG.nonce);
            formData.append('borrador', esBorrador ? 'true' : 'false');

            // Deshabilitar botones
            this.form.find('button').prop('disabled', true);

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.handleSuccess(response.data, esBorrador);
                    } else {
                        self.handleError(response.data);
                    }
                },
                error: function() {
                    self.handleError({ mensaje: CONFIG.i18n.error || 'Ha ocurrido un error' });
                },
                complete: function() {
                    self.form.find('button').prop('disabled', false);
                }
            });
        },

        handleSuccess: function(data, esBorrador) {
            if (esBorrador) {
                Utils.showNotice(
                    this.container.find('.flavor-form-header, .flavor-tramites-formulario').first(),
                    data.mensaje || 'Borrador guardado correctamente',
                    'success'
                );
                return;
            }

            // Mostrar resultado
            this.form.hide();
            const resultado = this.container.find('.flavor-tramite-resultado');
            resultado.find('.flavor-resultado-numero').text('Numero: ' + data.expediente.numero_expediente);
            resultado.show();

            Utils.scrollToElement(resultado);
        },

        handleError: function(data) {
            if (data.errors) {
                // Mostrar errores de validacion
                for (const campo in data.errors) {
                    const $field = this.form.find('[name="datos_formulario[' + campo + ']"]');
                    $field.addClass('error');
                    $field.after('<span class="flavor-field-error flavor-error">' + data.errors[campo] + '</span>');
                }
            } else {
                Utils.showNotice(
                    this.container.find('.flavor-form-header, .flavor-tramites-formulario').first(),
                    data.mensaje || CONFIG.i18n.error || 'Ha ocurrido un error',
                    'error'
                );
            }
        }
    };

    // Modulo Mis Expedientes
    const MisExpedientes = {
        init: function() {
            this.container = $('.flavor-mis-expedientes');
            if (!this.container.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Filtro por estado
            $('#flavor-filtro-estado').on('change', function() {
                self.filterExpedientes($(this).val());
            });
        },

        filterExpedientes: function(estado) {
            this.container.find('.flavor-expediente-item').each(function() {
                const item = $(this);
                const itemEstado = item.data('estado');

                if (!estado || itemEstado === estado) {
                    item.show();
                } else {
                    item.hide();
                }
            });
        }
    };

    // Modulo Consulta Estado
    const ConsultaEstado = {
        init: function() {
            this.container = $('.flavor-estado-expediente');
            if (!this.container.length) return;

            this.detalleContainer = this.container.find('.flavor-expediente-detalle');
            this.noEncontrado = this.container.find('.flavor-expediente-no-encontrado');

            this.bindEvents();
            this.checkInitialQuery();
        },

        bindEvents: function() {
            const self = this;

            // Formulario de consulta
            $('#flavor-form-consulta').on('submit', function(e) {
                e.preventDefault();
                const numero = $('#flavor-numero-expediente').val().trim();
                if (numero) {
                    self.consultarEstado(numero);
                }
            });
        },

        checkInitialQuery: function() {
            const numero = $('#flavor-numero-expediente').val();
            if (numero) {
                this.consultarEstado(numero);
            }
        },

        consultarEstado: function(numero) {
            const self = this;

            this.detalleContainer.hide();
            this.noEncontrado.hide();

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_tramites_action',
                    accion_tramites: 'consultar_estado',
                    numero_expediente: numero,
                    nonce: CONFIG.nonce
                },
                beforeSend: function() {
                    Utils.showLoading(self.detalleContainer);
                    self.detalleContainer.show();
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarDetalle(response.data);
                    } else {
                        self.detalleContainer.hide();
                        self.noEncontrado.show();
                    }
                },
                error: function() {
                    self.detalleContainer.hide();
                    self.noEncontrado.show();
                }
            });
        },

        mostrarDetalle: function(data) {
            const expediente = data.expediente;
            const historial = data.historial || [];

            // Numero y estado
            this.detalleContainer.find('.flavor-detalle-numero').text(expediente.numero_expediente);
            this.detalleContainer.find('.flavor-detalle-estado').html(
                '<span class="flavor-estado-badge" style="background-color: ' + expediente.estado_color + '">' +
                expediente.estado_nombre +
                '</span>'
            );

            // Info
            this.detalleContainer.find('.flavor-detalle-tipo').text(expediente.tipo_nombre || '-');
            this.detalleContainer.find('.flavor-detalle-fecha').text(
                expediente.fecha_creacion ? Utils.formatDate(expediente.fecha_creacion) : '-'
            );
            this.detalleContainer.find('.flavor-detalle-limite').text(
                expediente.fecha_limite ? Utils.formatDate(expediente.fecha_limite) : '-'
            );

            // Timeline
            this.renderTimeline(historial);

            this.detalleContainer.html(this.detalleContainer.html()).show();
        },

        renderTimeline: function(historial) {
            const timeline = this.detalleContainer.find('.flavor-timeline');
            timeline.empty();

            if (historial.length === 0) {
                timeline.html('<p>No hay eventos registrados.</p>');
                return;
            }

            historial.forEach(function(evento) {
                const tipoClase = 'evento-' + evento.tipo_evento;
                const itemHtml = `
                    <div class="flavor-timeline-item ${tipoClase}">
                        <div class="flavor-timeline-fecha">${Utils.formatDate(evento.fecha_evento)}</div>
                        <div class="flavor-timeline-descripcion">${evento.descripcion}</div>
                    </div>
                `;
                timeline.append(itemHtml);
            });
        }
    };

    // Modulo Subida de Documentos
    const SubidaDocumentos = {
        init: function() {
            this.initDragDrop();
        },

        initDragDrop: function() {
            const dropZones = $('.flavor-documento-upload');

            dropZones.each(function() {
                const zone = $(this);
                const input = zone.find('.flavor-file-input');

                zone.on('dragover dragenter', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.addClass('dragover');
                });

                zone.on('dragleave dragend drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.removeClass('dragover');
                });

                zone.on('drop', function(e) {
                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        input[0].files = files;
                        input.trigger('change');
                    }
                });
            });
        }
    };

    // API Cliente REST
    const APIClient = {
        baseUrl: CONFIG.restUrl,

        request: function(endpoint, method, data) {
            return $.ajax({
                url: this.baseUrl + endpoint,
                type: method || 'GET',
                data: data,
                headers: {
                    'X-WP-Nonce': CONFIG.nonce
                },
                contentType: 'application/json'
            });
        },

        getTiposTramite: function(params) {
            return this.request('tipos', 'GET', params);
        },

        getTipoTramite: function(id) {
            return this.request('tipos/' + id, 'GET');
        },

        getExpedientes: function(params) {
            return this.request('expedientes', 'GET', params);
        },

        getExpediente: function(id) {
            return this.request('expedientes/' + id, 'GET');
        },

        createExpediente: function(data) {
            return this.request('expedientes', 'POST', JSON.stringify(data));
        },

        updateExpediente: function(id, data) {
            return this.request('expedientes/' + id, 'PUT', JSON.stringify(data));
        },

        consultarExpediente: function(numero) {
            return this.request('expedientes/consulta/' + numero, 'GET');
        },

        getHistorial: function(id) {
            return this.request('expedientes/' + id + '/historial', 'GET');
        },

        getEstados: function() {
            return this.request('estados', 'GET');
        }
    };

    // Notificaciones
    const Notificaciones = {
        show: function(message, type) {
            type = type || 'info';

            const notification = $('<div class="flavor-notification flavor-notification-' + type + '">' +
                '<span class="flavor-notification-message">' + message + '</span>' +
                '<button class="flavor-notification-close">&times;</button>' +
                '</div>');

            $('body').append(notification);

            setTimeout(function() {
                notification.addClass('show');
            }, 10);

            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 5000);

            notification.find('.flavor-notification-close').on('click', function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            });
        },

        success: function(message) {
            this.show(message, 'success');
        },

        error: function(message) {
            this.show(message, 'error');
        },

        warning: function(message) {
            this.show(message, 'warning');
        },

        info: function(message) {
            this.show(message, 'info');
        }
    };

    // Inicializacion
    $(document).ready(function() {
        CatalogoTramites.init();
        FormularioTramite.init();
        MisExpedientes.init();
        ConsultaEstado.init();
        SubidaDocumentos.init();

        // Exponer API para uso externo
        window.FlavorTramites = {
            API: APIClient,
            Utils: Utils,
            Notificaciones: Notificaciones
        };
    });

    // Estilos adicionales para notificaciones (inyectados dinamicamente)
    const notificationStyles = `
        <style>
        .flavor-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            padding: 16px 40px 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 99999;
            font-size: 14px;
        }
        .flavor-notification.show {
            transform: translateX(0);
        }
        .flavor-notification-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .flavor-notification-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .flavor-notification-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .flavor-notification-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .flavor-notification-close {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.5;
            line-height: 1;
        }
        .flavor-notification-close:hover {
            opacity: 1;
        }
        .flavor-documento-upload.dragover {
            background: rgba(0, 115, 170, 0.1);
            border-color: var(--flavor-tramites-primary);
        }
        .flavor-documento-upload.dragover .flavor-file-label {
            border-style: solid;
            background: rgba(0, 115, 170, 0.1);
        }
        </style>
    `;
    $('head').append(notificationStyles);

})(jQuery);
