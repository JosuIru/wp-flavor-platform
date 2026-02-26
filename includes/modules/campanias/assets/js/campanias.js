/**
 * JavaScript del modulo Campanias
 */
(function($) {
    'use strict';

    const FlavorCampanias = {
        config: window.flavorCampaniasConfig || {},

        init: function() {
            this.bindEvents();
            this.initFirmasProgress();
        },

        bindEvents: function() {
            // Formulario de firma
            $(document).on('submit', '.flavor-firma-form', this.handleFirmar.bind(this));

            // Boton participar
            $(document).on('click', '.flavor-btn-participar', this.handleParticipar.bind(this));

            // Boton abandonar
            $(document).on('click', '.flavor-btn-abandonar', this.handleAbandonar.bind(this));

            // Formulario crear campania
            $(document).on('submit', '.flavor-crear-campania-form', this.handleCrear.bind(this));

            // Confirmar asistencia a accion
            $(document).on('click', '.flavor-btn-confirmar-asistencia', this.handleConfirmarAsistencia.bind(this));

            // Filtros
            $(document).on('change', '.flavor-campanias-filtro', this.handleFiltrar.bind(this));
        },

        initFirmasProgress: function() {
            $('.flavor-firmas-fill').each(function() {
                const porcentaje = $(this).data('porcentaje') || 0;
                $(this).css('width', Math.min(porcentaje, 100) + '%');
            });
        },

        handleFirmar: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');
            const btnTextoOriginal = $btn.text();

            $btn.prop('disabled', true).text('Firmando...');

            const datos = {
                action: 'campanias_firmar',
                nonce: this.config.nonce,
                campania_id: $form.find('[name="campania_id"]').val(),
                nombre: $form.find('[name="nombre"]').val(),
                email: $form.find('[name="email"]').val(),
                localidad: $form.find('[name="localidad"]').val(),
                comentario: $form.find('[name="comentario"]').val()
            };

            $.post(this.config.ajaxUrl, datos)
                .done(function(response) {
                    if (response.success) {
                        $form.html('<div class="flavor-mensaje-exito"><span class="dashicons dashicons-yes-alt"></span> ' + (response.data.mensaje || 'Gracias por firmar!') + '</div>');

                        // Actualizar contador
                        if (response.data.total_firmas) {
                            $('.flavor-firmas-actual').text(response.data.total_firmas + ' firmas');
                            FlavorCampanias.actualizarBarraFirmas(response.data.total_firmas);
                        }
                    } else {
                        FlavorCampanias.mostrarError($form, response.data.error || 'Error al firmar');
                        $btn.prop('disabled', false).text(btnTextoOriginal);
                    }
                })
                .fail(function() {
                    FlavorCampanias.mostrarError($form, FlavorCampanias.config.strings.errorConexion);
                    $btn.prop('disabled', false).text(btnTextoOriginal);
                });
        },

        handleParticipar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);

            if (!confirm(this.config.strings.confirmParticipar)) {
                return;
            }

            $btn.prop('disabled', true);

            $.post(this.config.ajaxUrl, {
                action: 'campanias_participar',
                nonce: this.config.nonce,
                campania_id: $btn.data('campania-id')
            })
            .done(function(response) {
                if (response.success) {
                    $btn.replaceWith('<span class="flavor-estado flavor-estado-activa">Participando</span>');
                } else {
                    alert(response.data.error || 'Error al unirse');
                    $btn.prop('disabled', false);
                }
            })
            .fail(function() {
                alert(FlavorCampanias.config.strings.errorConexion);
                $btn.prop('disabled', false);
            });
        },

        handleAbandonar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);

            if (!confirm(this.config.strings.confirmAbandonar)) {
                return;
            }

            $btn.prop('disabled', true);

            $.post(this.config.ajaxUrl, {
                action: 'campanias_abandonar',
                nonce: this.config.nonce,
                campania_id: $btn.data('campania-id')
            })
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.error || 'Error al abandonar');
                    $btn.prop('disabled', false);
                }
            })
            .fail(function() {
                alert(FlavorCampanias.config.strings.errorConexion);
                $btn.prop('disabled', false);
            });
        },

        handleCrear: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Creando...');

            const formData = new FormData($form[0]);
            formData.append('action', 'campanias_crear');
            formData.append('nonce', this.config.nonce);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                if (response.success) {
                    window.location.href = '?campania_id=' + response.data.campania_id;
                } else {
                    FlavorCampanias.mostrarError($form, response.data.error || 'Error al crear');
                    $btn.prop('disabled', false).text('Crear Campania');
                }
            })
            .fail(function() {
                FlavorCampanias.mostrarError($form, FlavorCampanias.config.strings.errorConexion);
                $btn.prop('disabled', false).text('Crear Campania');
            });
        },

        handleConfirmarAsistencia: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);

            $btn.prop('disabled', true);

            $.post(this.config.ajaxUrl, {
                action: 'campanias_confirmar_asistencia',
                nonce: this.config.nonce,
                accion_id: $btn.data('accion-id')
            })
            .done(function(response) {
                if (response.success) {
                    $btn.replaceWith('<span class="flavor-estado flavor-estado-activa">Asistencia confirmada</span>');
                } else {
                    alert(response.data.error || 'Error al confirmar');
                    $btn.prop('disabled', false);
                }
            })
            .fail(function() {
                alert(FlavorCampanias.config.strings.errorConexion);
                $btn.prop('disabled', false);
            });
        },

        handleFiltrar: function(e) {
            const tipo = $('.flavor-filtro-tipo').val();
            const estado = $('.flavor-filtro-estado').val();

            // Recargar con filtros
            const params = new URLSearchParams(window.location.search);
            if (tipo) params.set('tipo', tipo);
            else params.delete('tipo');
            if (estado) params.set('estado', estado);
            else params.delete('estado');

            window.location.search = params.toString();
        },

        actualizarBarraFirmas: function(totalFirmas) {
            const objetivo = parseInt($('.flavor-firmas-objetivo').data('objetivo')) || 0;
            if (objetivo > 0) {
                const porcentaje = (totalFirmas / objetivo) * 100;
                $('.flavor-firmas-fill').css('width', Math.min(porcentaje, 100) + '%');
            }
        },

        mostrarError: function($container, mensaje) {
            const $error = $('<div class="flavor-mensaje-error">' + mensaje + '</div>');
            $container.find('.flavor-mensaje-error').remove();
            $container.prepend($error);

            setTimeout(function() {
                $error.fadeOut(function() { $(this).remove(); });
            }, 5000);
        }
    };

    $(document).ready(function() {
        FlavorCampanias.init();
    });

    // Exponer globalmente
    window.FlavorCampanias = FlavorCampanias;

})(jQuery);
