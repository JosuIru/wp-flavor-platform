/**
 * Grupos de Consumo - Trueques JavaScript
 *
 * Sistema de intercambio entre socios del grupo
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var GCTrueques = {
        config: window.gcTruequesConfig || {},
        filterTimeout: null,
        confirmacionActual: null,

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initImageUpload();
        },

        bindEvents: function() {
            var self = this;

            // Filtros con debounce
            $(document).on('input', '.trueques-filtro-grupo input', function() {
                clearTimeout(self.filterTimeout);
                self.filterTimeout = setTimeout(function() {
                    self.filtrarTrueques();
                }, 300);
            });

            $(document).on('change', '.trueques-filtro-grupo select', function() {
                self.filtrarTrueques();
            });

            // Click en tarjeta de trueque
            $(document).on('click', '.trueque-card', function(e) {
                if ($(e.target).closest('.tr-btn').length) return;
                var truequeId = $(this).data('trueque-id');
                self.verDetalle(truequeId);
            });

            // Nuevo trueque
            $(document).on('click', '.tr-btn-nuevo-trueque', function(e) {
                e.preventDefault();
                self.abrirFormulario();
            });

            // Selector de tipo
            $(document).on('click', '.trueque-tipo-opcion', function() {
                $('.trueque-tipo-opcion').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input').prop('checked', true);
            });

            // Enviar trueque
            $(document).on('submit', '#trueque-form', function(e) {
                e.preventDefault();
                self.guardarTrueque($(this));
            });

            // Enviar propuesta
            $(document).on('submit', '#propuesta-form', function(e) {
                e.preventDefault();
                self.enviarPropuesta($(this));
            });

            // Aceptar propuesta
            $(document).on('click', '.tr-btn-aceptar-propuesta', function(e) {
                e.preventDefault();
                var propuestaId = $(this).data('propuesta-id');
                self.responderPropuesta(propuestaId, 'aceptar', $(this));
            });

            // Rechazar propuesta
            $(document).on('click', '.tr-btn-rechazar-propuesta', function(e) {
                e.preventDefault();
                var propuestaId = $(this).data('propuesta-id');
                self.responderPropuesta(propuestaId, 'rechazar', $(this));
            });

            // Eliminar trueque
            $(document).on('click', '.tr-btn-eliminar-trueque', function(e) {
                e.preventDefault();
                var truequeId = $(this).data('trueque-id');
                self.solicitarConfirmacion(self.config.i18n?.confirmarEliminar || '¿Eliminar este trueque?', function() {
                    self.eliminarTrueque(truequeId, $(e.currentTarget));
                });
            });

            // Marcar como intercambiado
            $(document).on('click', '.tr-btn-completar', function(e) {
                e.preventDefault();
                var truequeId = $(this).data('trueque-id');
                self.completarTrueque(truequeId, $(this));
            });

            // Cerrar modal
            $(document).on('click', '.trueque-modal-overlay, .trueque-modal-close', function() {
                self.cerrarModal();
            });

            // Prevenir cierre al click en contenido del modal
            $(document).on('click', '.trueque-modal-content', function(e) {
                e.stopPropagation();
            });

            // ESC para cerrar modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.cerrarModal();
                }
            });
        },

        solicitarConfirmacion: function(mensaje, onConfirm) {
            if (this.confirmacionActual) {
                this.confirmacionActual.remove();
            }

            this.confirmacionActual = $(`
                <div class="trueque-inline-confirm">
                    <span class="trueque-inline-confirm-texto">${mensaje}</span>
                    <div class="trueque-inline-confirm-actions">
                        <button type="button" class="tr-btn tr-btn-primary trueque-confirmar">Confirmar</button>
                        <button type="button" class="tr-btn tr-btn-secondary trueque-cancelar">Cancelar</button>
                    </div>
                </div>
            `);

            $('.trueques-contenedor, .trueques-grid, body').first().prepend(this.confirmacionActual);

            this.confirmacionActual.on('click', '.trueque-confirmar', () => {
                this.confirmacionActual.remove();
                this.confirmacionActual = null;
                onConfirm();
            });

            this.confirmacionActual.on('click', '.trueque-cancelar', () => {
                this.confirmacionActual.remove();
                this.confirmacionActual = null;
            });
        },

        initTabs: function() {
            var self = this;

            $(document).on('click', '.trueques-tab', function() {
                var tab = $(this).data('tab');

                $('.trueques-tab').removeClass('active');
                $(this).addClass('active');

                self.cargarTrueques(tab);
            });
        },

        initImageUpload: function() {
            var self = this;

            // Click en zona de upload
            $(document).on('click', '.trueque-imagen-upload', function() {
                var $input = $(this).find('input[type="file"]');
                if (!$input.length) {
                    $input = $('<input type="file" accept="image/*" style="display:none">');
                    $(this).append($input);
                }
                $input.trigger('click');
            });

            // Cambio de archivo
            $(document).on('change', '.trueque-imagen-upload input[type="file"]', function() {
                var files = this.files;
                if (files && files[0]) {
                    self.previsualizarImagen(files[0], $(this).closest('.trueque-imagen-upload'));
                }
            });

            // Eliminar imagen
            $(document).on('click', '.trueque-imagen-preview .btn-remove', function(e) {
                e.stopPropagation();
                var $container = $(this).closest('.trueque-form-group');
                $container.find('.trueque-imagen-preview').remove();
                $container.find('.trueque-imagen-upload').show();
                $container.find('input[type="file"]').val('');
            });

            // Drag and drop
            $(document).on('dragover', '.trueque-imagen-upload', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $(document).on('dragleave', '.trueque-imagen-upload', function() {
                $(this).removeClass('dragover');
            });

            $(document).on('drop', '.trueque-imagen-upload', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                var files = e.originalEvent.dataTransfer.files;
                if (files && files[0]) {
                    self.previsualizarImagen(files[0], $(this));
                }
            });
        },

        previsualizarImagen: function(file, $uploadArea) {
            var self = this;

            if (!file.type.match('image.*')) {
                self.mostrarNotificacion(self.config.i18n?.soloImagenes || 'Solo se permiten imágenes', 'error');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                var $preview = $('<div class="trueque-imagen-preview">' +
                    '<img src="' + e.target.result + '" alt="Preview">' +
                    '<button type="button" class="btn-remove"><span class="dashicons dashicons-no-alt"></span></button>' +
                    '</div>');

                $uploadArea.hide().after($preview);
            };
            reader.readAsDataURL(file);
        },

        filtrarTrueques: function() {
            var self = this;
            var $grid = $('.trueques-grid');
            var formData = {};

            // Recoger valores de filtros
            $('.trueques-filtro-grupo input, .trueques-filtro-grupo select').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (name && value) {
                    formData[name] = value;
                }
            });

            // Tab activo
            formData.tab = $('.trueques-tab.active').data('tab') || 'todos';

            $grid.addClass('trueques-loading');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_filtrar',
                    nonce: self.config.nonce,
                    filtros: formData
                },
                success: function(response) {
                    $grid.removeClass('trueques-loading');
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);
                        self.actualizarContadores(response.data.contadores);
                    }
                },
                error: function() {
                    $grid.removeClass('trueques-loading');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error al filtrar', 'error');
                }
            });
        },

        cargarTrueques: function(tab) {
            var self = this;
            var $grid = $('.trueques-grid');

            $grid.addClass('trueques-loading');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_listar',
                    nonce: self.config.nonce,
                    tab: tab
                },
                success: function(response) {
                    $grid.removeClass('trueques-loading');
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);
                    }
                },
                error: function() {
                    $grid.removeClass('trueques-loading');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        actualizarContadores: function(contadores) {
            if (!contadores) return;

            for (var tab in contadores) {
                if (contadores.hasOwnProperty(tab)) {
                    var $badge = $('.trueques-tab[data-tab="' + tab + '"] .trueques-tab-badge');
                    if ($badge.length) {
                        $badge.text(contadores[tab]);
                    }
                }
            }
        },

        verDetalle: function(truequeId) {
            var self = this;

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_detalle',
                    nonce: self.config.nonce,
                    trueque_id: truequeId
                },
                beforeSend: function() {
                    self.mostrarLoading();
                },
                success: function(response) {
                    self.ocultarLoading();
                    if (response.success && response.data.html) {
                        self.abrirModal(response.data.html);
                    } else {
                        self.mostrarNotificacion(response.data.message || 'Error', 'error');
                    }
                },
                error: function() {
                    self.ocultarLoading();
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        abrirFormulario: function(truequeId) {
            var self = this;

            var url = self.config.ajaxUrl;
            var data = {
                action: 'gc_trueques_formulario',
                nonce: self.config.nonce
            };

            if (truequeId) {
                data.trueque_id = truequeId;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    self.mostrarLoading();
                },
                success: function(response) {
                    self.ocultarLoading();
                    if (response.success && response.data.html) {
                        self.abrirModal(response.data.html);
                    } else {
                        self.mostrarNotificacion(response.data.message || 'Error', 'error');
                    }
                },
                error: function() {
                    self.ocultarLoading();
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        guardarTrueque: function($form) {
            var self = this;
            var formData = new FormData($form[0]);
            formData.append('action', 'gc_trueques_guardar');
            formData.append('nonce', self.config.nonce);

            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text(self.config.i18n?.guardando || 'Guardando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.prop('disabled', false).text(self.config.i18n?.publicar || 'Publicar trueque');
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        self.cerrarModal();
                        self.cargarTrueques($('.trueques-tab.active').data('tab') || 'todos');
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n?.publicar || 'Publicar trueque');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        enviarPropuesta: function($form) {
            var self = this;
            var formData = new FormData($form[0]);
            formData.append('action', 'gc_trueques_propuesta');
            formData.append('nonce', self.config.nonce);

            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text(self.config.i18n?.enviando || 'Enviando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.prop('disabled', false).text(self.config.i18n?.enviarPropuesta || 'Enviar propuesta');
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        // Actualizar lista de propuestas si está visible
                        if (response.data.propuestasHtml) {
                            $('.trueque-propuestas').html(response.data.propuestasHtml);
                        }
                        $form[0].reset();
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n?.enviarPropuesta || 'Enviar propuesta');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        responderPropuesta: function(propuestaId, accion, $btn) {
            var self = this;

            $btn.prop('disabled', true);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_responder_propuesta',
                    nonce: self.config.nonce,
                    propuesta_id: propuestaId,
                    respuesta: accion
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        // Actualizar UI de la propuesta
                        var $propuesta = $btn.closest('.propuesta-item');
                        $propuesta.find('.propuesta-estado')
                            .removeClass('pendiente')
                            .addClass(accion === 'aceptar' ? 'aceptada' : 'rechazada')
                            .text(accion === 'aceptar' ? 'Aceptada' : 'Rechazada');
                        $propuesta.find('.propuesta-acciones').remove();
                    } else {
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        eliminarTrueque: function(truequeId, $btn) {
            var self = this;

            $btn.prop('disabled', true);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_eliminar',
                    nonce: self.config.nonce,
                    trueque_id: truequeId
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        // Remover tarjeta o cerrar modal
                        var $card = $('.trueque-card[data-trueque-id="' + truequeId + '"]');
                        if ($card.length) {
                            $card.fadeOut(300, function() { $(this).remove(); });
                        }
                        self.cerrarModal();
                    } else {
                        $btn.prop('disabled', false);
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        completarTrueque: function(truequeId, $btn) {
            var self = this;

            $btn.prop('disabled', true).text(self.config.i18n?.procesando || 'Procesando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gc_trueques_completar',
                    nonce: self.config.nonce,
                    trueque_id: truequeId
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        self.cerrarModal();
                        self.cargarTrueques($('.trueques-tab.active').data('tab') || 'todos');
                    } else {
                        $btn.prop('disabled', false).text(self.config.i18n?.completar || 'Marcar como intercambiado');
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n?.completar || 'Marcar como intercambiado');
                    self.mostrarNotificacion(self.config.i18n?.error || 'Error', 'error');
                }
            });
        },

        abrirModal: function(contenido) {
            var modalHtml = '<div class="trueque-modal-overlay">' +
                '<div class="trueque-modal">' +
                '<button class="trueque-modal-close"><span class="dashicons dashicons-no-alt"></span></button>' +
                '<div class="trueque-modal-content">' + contenido + '</div>' +
                '</div>' +
                '</div>';

            $('body').append(modalHtml);

            setTimeout(function() {
                $('.trueque-modal-overlay').addClass('visible');
            }, 10);

            $('body').addClass('trueque-modal-abierto');
        },

        cerrarModal: function() {
            var $modal = $('.trueque-modal-overlay');
            $modal.removeClass('visible');

            setTimeout(function() {
                $modal.remove();
            }, 300);

            $('body').removeClass('trueque-modal-abierto');
        },

        mostrarLoading: function() {
            if (!$('.trueque-loading-global').length) {
                $('body').append('<div class="trueque-loading-global"><div class="trueques-loading"></div></div>');
            }
            $('.trueque-loading-global').fadeIn(200);
        },

        ocultarLoading: function() {
            $('.trueque-loading-global').fadeOut(200);
        },

        mostrarNotificacion: function(mensaje, tipo) {
            var $notif = $('<div class="trueque-notificacion ' + tipo + '">' + mensaje + '</div>');

            $('body').append($notif);

            setTimeout(function() {
                $notif.addClass('visible');
            }, 10);

            setTimeout(function() {
                $notif.removeClass('visible');
                setTimeout(function() {
                    $notif.remove();
                }, 300);
            }, 4000);
        }
    };

    $(document).ready(function() {
        GCTrueques.init();
    });

})(jQuery);
