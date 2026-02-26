/**
 * JavaScript frontend para Trámites Municipales
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorTramites = {
        config: window.flavorTramitesConfig || {},
        pasoActual: 1,

        init: function() {
            this.bindEvents();
            this.initFileInputs();
        },

        bindEvents: function() {
            // Navegación de pasos
            $(document).on('click', '.flavor-btn-siguiente', this.siguientePaso.bind(this));
            $(document).on('click', '.flavor-btn-anterior', this.anteriorPaso.bind(this));

            // Formulario de trámite
            $(document).on('submit', '#flavor-form-tramite', this.enviarSolicitud.bind(this));

            // Subir documento adicional
            $(document).on('submit', '#flavor-form-subir-doc', this.subirDocumento.bind(this));

            // Enviar mensaje
            $(document).on('submit', '#flavor-form-mensaje', this.enviarMensaje.bind(this));

            // Cancelar trámite
            $(document).on('click', '.flavor-cancelar-tramite', this.cancelarTramite.bind(this));

            // Solicitar cita
            $(document).on('submit', '#flavor-form-cita', this.solicitarCita.bind(this));

            // Cambio de fecha para obtener horarios
            $(document).on('change', '#fecha_cita', this.obtenerHorarios.bind(this));

            // Filtro de documentos
            $(document).on('change', '#filtro-categoria-docs', this.filtrarDocumentos.bind(this));

            // Búsqueda en tiempo real
            var searchTimeout;
            $(document).on('input', '.flavor-buscar-tramites input[name="q"]', function() {
                clearTimeout(searchTimeout);
                var $input = $(this);
                searchTimeout = setTimeout(function() {
                    FlavorTramites.buscarTramites($input.val());
                }, 300);
            });
        },

        initFileInputs: function() {
            $(document).on('change', '.flavor-documento-upload-zona input[type="file"]', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).closest('.flavor-documento-upload-zona')
                       .find('.flavor-archivo-seleccionado')
                       .text(fileName);
            });
        },

        siguientePaso: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var siguiente = $btn.data('siguiente');
            var $pasoActual = $('.flavor-form-paso[data-paso="' + this.pasoActual + '"]');

            // Validar campos del paso actual
            var valido = true;
            $pasoActual.find('input[required], select[required], textarea[required]').each(function() {
                if (!this.checkValidity()) {
                    $(this).addClass('error');
                    valido = false;
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!valido) {
                this.showNotice(this.config.strings.error || 'Completa todos los campos obligatorios', 'error');
                return;
            }

            // Si vamos al paso de confirmación, actualizar resumen
            if (siguiente === 3) {
                this.actualizarResumen();
            }

            // Cambiar paso
            this.pasoActual = siguiente;
            this.actualizarPasos();
        },

        anteriorPaso: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var anterior = $btn.data('anterior');

            this.pasoActual = anterior;
            this.actualizarPasos();
        },

        actualizarPasos: function() {
            // Ocultar todos los pasos
            $('.flavor-form-paso').hide();

            // Mostrar paso actual
            $('.flavor-form-paso[data-paso="' + this.pasoActual + '"]').show();

            // Actualizar indicadores
            $('.flavor-paso-indicador').removeClass('activo completado');
            $('.flavor-paso-indicador').each(function() {
                var paso = $(this).data('paso');
                if (paso < FlavorTramites.pasoActual) {
                    $(this).addClass('completado');
                } else if (paso === FlavorTramites.pasoActual) {
                    $(this).addClass('activo');
                }
            });
        },

        actualizarResumen: function() {
            // Resumen de datos
            var datosHtml = '';
            datosHtml += '<p><strong>Nombre:</strong> ' + $('#nombre_completo').val() + '</p>';
            datosHtml += '<p><strong>DNI:</strong> ' + $('#dni').val() + '</p>';
            datosHtml += '<p><strong>Email:</strong> ' + $('#email').val() + '</p>';
            datosHtml += '<p><strong>Teléfono:</strong> ' + $('#telefono').val() + '</p>';
            datosHtml += '<p><strong>Dirección:</strong> ' + $('#direccion').val() + '</p>';
            $('#resumen-datos').html(datosHtml);

            // Resumen de documentos
            var docsHtml = '<ul>';
            $('.flavor-documento-upload-zona input[type="file"]').each(function() {
                var fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    docsHtml += '<li>' + fileName + '</li>';
                }
            });
            docsHtml += '</ul>';

            if (docsHtml === '<ul></ul>') {
                docsHtml = '<p>No se han adjuntado documentos</p>';
            }
            $('#resumen-documentos').html(docsHtml);
        },

        enviarSolicitud: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            // Validar checkboxes
            if (!$form.find('input[name="acepto_condiciones"]').is(':checked') ||
                !$form.find('input[name="declaro_veracidad"]').is(':checked')) {
                this.showNotice('Debes aceptar las condiciones', 'error');
                return;
            }

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_tramites_iniciar');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        FlavorTramites.showNotice(response.data.message, 'success');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        FlavorTramites.showNotice(response.data.message || FlavorTramites.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorTramites.showNotice(FlavorTramites.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        subirDocumento: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_tramites_subir_documento');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        FlavorTramites.showNotice(FlavorTramites.config.strings.documentoSubido, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorTramites.showNotice(response.data.message || FlavorTramites.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorTramites.showNotice(FlavorTramites.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        enviarMensaje: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var $textarea = $form.find('textarea[name="mensaje"]');
            var btnText = $btn.html();

            if (!$textarea.val().trim()) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_tramites_enviar_mensaje',
                success: function(response) {
                    if (response.success) {
                        FlavorTramites.showNotice(response.data.message, 'success');
                        $textarea.val('');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorTramites.showNotice(response.data.message || FlavorTramites.config.strings.error, 'error');
                    }
                    $btn.prop('disabled', false).html(btnText);
                },
                error: function() {
                    FlavorTramites.showNotice(FlavorTramites.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        cancelarTramite: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarCancelar)) {
                return;
            }

            var $btn = $(e.currentTarget);
            var solicitudId = $btn.data('solicitud-id');

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_tramites_cancelar',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorTramites.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorTramites.showNotice(response.data.message || FlavorTramites.config.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    FlavorTramites.showNotice(FlavorTramites.config.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        solicitarCita: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_tramites_solicitar_cita',
                success: function(response) {
                    if (response.success) {
                        FlavorTramites.showNotice(FlavorTramites.config.strings.citaReservada, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorTramites.showNotice(response.data.message || FlavorTramites.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorTramites.showNotice(FlavorTramites.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        obtenerHorarios: function(e) {
            var fecha = $(e.target).val();
            var tramiteId = $('input[name="tramite_id"]').val();
            var $select = $('#hora_cita');

            if (!fecha) {
                $select.html('<option value="">' + this.config.strings.seleccionaHora + '</option>');
                return;
            }

            $select.html('<option value="">Cargando...</option>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_tramites_obtener_horarios',
                    fecha: fecha,
                    tramite_id: tramiteId
                },
                success: function(response) {
                    if (response.success && response.data.horarios) {
                        var html = '<option value="">' + FlavorTramites.config.strings.seleccionaHora + '</option>';
                        response.data.horarios.forEach(function(hora) {
                            html += '<option value="' + hora + '">' + hora + '</option>';
                        });
                        $select.html(html);
                    } else {
                        $select.html('<option value="">No hay horarios disponibles</option>');
                    }
                },
                error: function() {
                    $select.html('<option value="">Error al cargar horarios</option>');
                }
            });
        },

        filtrarDocumentos: function(e) {
            var categoria = $(e.target).val();

            if (!categoria) {
                $('.flavor-documento-card').show();
            } else {
                $('.flavor-documento-card').hide();
                $('.flavor-documento-card[data-categoria="' + categoria + '"]').show();
            }
        },

        buscarTramites: function(termino) {
            if (termino.length < 2) {
                return;
            }

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_tramites_buscar',
                    termino: termino
                },
                success: function(response) {
                    if (response.success && response.data.tramites) {
                        var html = '';
                        response.data.tramites.forEach(function(tramite) {
                            html += '<a href="?tramite_id=' + tramite.id + '" class="flavor-resultado-item">';
                            html += '<span class="dashicons ' + (tramite.icono || 'dashicons-clipboard') + '"></span>';
                            html += '<div><strong>' + tramite.nombre + '</strong>';
                            html += '<span>' + (tramite.descripcion || '').substring(0, 100) + '</span></div></a>';
                        });

                        if (!html) {
                            html = '<div class="flavor-no-resultados"><p>No se encontraron trámites</p></div>';
                        }

                        $('#resultados-tramites').html(html);
                    }
                }
            });
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="flavor-notice flavor-notice-' + type + '">' + message + '</div>');
            $('body').append($notice);

            setTimeout(function() {
                $notice.addClass('show');
            }, 10);

            setTimeout(function() {
                $notice.removeClass('show');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        FlavorTramites.init();
    });

    // CSS para notificaciones y estados
    var style = document.createElement('style');
    style.textContent = `
        .flavor-notice {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
        }
        .flavor-notice.show {
            opacity: 1;
            transform: translateX(0);
        }
        .flavor-notice-success {
            background: #22c55e;
        }
        .flavor-notice-error {
            background: #ef4444;
        }
        .flavor-form-grupo input.error,
        .flavor-form-group input.error {
            border-color: #ef4444;
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

})(jQuery);
