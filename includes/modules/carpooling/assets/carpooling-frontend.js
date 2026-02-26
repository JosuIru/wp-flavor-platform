/**
 * Carpooling Frontend JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var CarpoolingFrontend = {
        config: window.carpoolingFrontend || {},
        filterTimeout: null,

        init: function() {
            this.bindEvents();
            this.initDatePickers();
        },

        bindEvents: function() {
            var self = this;

            // Filtros con debounce
            $(document).on('input', '.carpooling-filtro-grupo input', function() {
                clearTimeout(self.filterTimeout);
                self.filterTimeout = setTimeout(function() {
                    self.filtrarViajes();
                }, 300);
            });

            $(document).on('change', '.carpooling-filtro-grupo select', function() {
                self.filtrarViajes();
            });

            // Reservar plaza
            $(document).on('click', '.cp-btn-reservar', function(e) {
                e.preventDefault();
                var viajeId = $(this).data('viaje-id');
                self.reservarPlaza(viajeId, $(this));
            });

            // Cancelar reserva
            $(document).on('click', '.cp-btn-cancelar', function(e) {
                e.preventDefault();
                var reservaId = $(this).data('reserva-id');
                self.cancelarReserva(reservaId, $(this));
            });

            // Formulario publicar viaje
            $(document).on('submit', '#carpooling-form-publicar', function(e) {
                e.preventDefault();
                self.publicarViaje($(this));
            });
        },

        initDatePickers: function() {
            // Inicializar datepickers si existen
            if ($.fn.datepicker && $('.carpooling-datepicker').length) {
                $('.carpooling-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });
            }
        },

        filtrarViajes: function() {
            var self = this;
            var $grid = $('.carpooling-viajes-grid');
            var formData = {};

            // Recoger valores de filtros
            $('.carpooling-filtro-grupo input, .carpooling-filtro-grupo select').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (name && value) {
                    formData[name] = value;
                }
            });

            $grid.addClass('carpooling-loading');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_filtrar_viajes',
                    nonce: self.config.nonce,
                    filtros: formData
                },
                success: function(response) {
                    $grid.removeClass('carpooling-loading');
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);
                    }
                },
                error: function() {
                    $grid.removeClass('carpooling-loading');
                    self.mostrarNotificacion(self.config.i18n.error || 'Error al filtrar', 'error');
                }
            });
        },

        reservarPlaza: function(viajeId, $btn) {
            var self = this;

            if (!self.config.isLoggedIn) {
                window.location.href = self.config.loginUrl;
                return;
            }

            $btn.prop('disabled', true).text(self.config.i18n.procesando || 'Procesando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_reservar_plaza',
                    nonce: self.config.nonce,
                    viaje_id: viajeId
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        // Actualizar UI
                        $btn.closest('.carpooling-viaje-card').addClass('reservado');
                        $btn.text(self.config.i18n.reservado || 'Reservado').removeClass('cp-btn-primary').addClass('cp-btn-success');
                    } else {
                        $btn.prop('disabled', false).text(self.config.i18n.reservar || 'Reservar');
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n.reservar || 'Reservar');
                    self.mostrarNotificacion(self.config.i18n.error || 'Error', 'error');
                }
            });
        },

        cancelarReserva: function(reservaId, $btn) {
            var self = this;

            if (!confirm(self.config.i18n.confirmarCancelar || '¿Cancelar esta reserva?')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_cancelar_reserva',
                    nonce: self.config.nonce,
                    reserva_id: reservaId
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        $btn.closest('.carpooling-reserva-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        $btn.prop('disabled', false);
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    self.mostrarNotificacion(self.config.i18n.error || 'Error', 'error');
                }
            });
        },

        publicarViaje: function($form) {
            var self = this;
            var $btn = $form.find('button[type="submit"]');
            var formData = new FormData($form[0]);
            formData.append('action', 'carpooling_publicar_viaje');
            formData.append('nonce', self.config.nonce);

            $btn.prop('disabled', true).text(self.config.i18n.publicando || 'Publicando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.mostrarNotificacion(response.data.message, 'success');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        $btn.prop('disabled', false).text(self.config.i18n.publicar || 'Publicar viaje');
                        self.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(self.config.i18n.publicar || 'Publicar viaje');
                    self.mostrarNotificacion(self.config.i18n.error || 'Error', 'error');
                }
            });
        },

        mostrarNotificacion: function(mensaje, tipo) {
            var $notif = $('<div class="carpooling-notificacion ' + tipo + '">' + mensaje + '</div>');

            $('body').append($notif);

            setTimeout(function() {
                $notif.addClass('visible');
            }, 10);

            setTimeout(function() {
                $notif.removeClass('visible');
                setTimeout(function() {
                    $notif.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        CarpoolingFrontend.init();
    });

})(jQuery);
