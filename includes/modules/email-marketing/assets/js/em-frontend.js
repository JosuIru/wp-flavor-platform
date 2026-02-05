/**
 * Email Marketing - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Formulario de suscripción
    $(document).on('submit', '.em-suscripcion-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('.em-btn-suscribir');
        const $btnTexto = $btn.find('.em-btn-texto');
        const $btnLoading = $btn.find('.em-btn-loading');
        const $mensaje = $form.find('.em-form-mensaje');

        const email = $form.find('input[name="email"]').val();
        const nombre = $form.find('input[name="nombre"]').val() || '';
        const lista = $form.data('lista') || 'newsletter-principal';

        // UI loading
        $btn.prop('disabled', true);
        $btnTexto.hide();
        $btnLoading.show();
        $mensaje.hide();

        $.ajax({
            url: flavorEMFront.ajax_url,
            type: 'POST',
            data: {
                action: 'em_suscribirse',
                nonce: flavorEMFront.nonce,
                email: email,
                nombre: nombre,
                lista: lista
            },
            success: function(response) {
                $mensaje.removeClass('em-success em-error');

                if (response.success) {
                    $mensaje.addClass('em-success').html(response.mensaje).show();
                    $form.find('input').val('');
                } else {
                    $mensaje.addClass('em-error').html(response.error || 'Error al suscribirse').show();
                }
            },
            error: function() {
                $mensaje.removeClass('em-success').addClass('em-error')
                    .html('Error de conexión. Inténtalo de nuevo.').show();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btnTexto.show();
                $btnLoading.hide();
            }
        });
    });

    // Formulario de preferencias
    $(document).on('submit', '#em-form-preferencias', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $mensaje = $form.find('.em-form-mensaje');
        const token = $form.data('token');

        const listas = [];
        $form.find('input[name="listas[]"]:checked').each(function() {
            listas.push($(this).val());
        });

        $btn.prop('disabled', true).text('Guardando...');
        $mensaje.hide();

        $.ajax({
            url: flavorEMFront.ajax_url.replace('admin-ajax.php', 'wp-json/flavor/v1/em/preferencias'),
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                token: token,
                listas: listas
            }),
            success: function(response) {
                $mensaje.removeClass('em-success em-error');

                if (response.success) {
                    $mensaje.addClass('em-success').html(response.mensaje || 'Preferencias actualizadas').show();
                } else {
                    $mensaje.addClass('em-error').html(response.error || 'Error').show();
                }
            },
            error: function() {
                $mensaje.addClass('em-error').html('Error de conexión').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Guardar preferencias');
            }
        });
    });

    // Formulario de baja
    $(document).on('submit', '#em-form-baja', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $mensaje = $form.find('.em-form-mensaje');
        const token = $form.data('token');

        let motivo = $form.find('select[name="motivo"]').val();
        if (motivo === 'otro') {
            motivo = $form.find('textarea[name="motivo_otro"]').val() || 'otro';
        }

        if (!confirm('¿Estás seguro de que quieres darte de baja?')) {
            return;
        }

        $btn.prop('disabled', true).text('Procesando...');
        $mensaje.hide();

        $.ajax({
            url: flavorEMFront.ajax_url,
            type: 'POST',
            data: {
                action: 'em_darse_baja',
                token: token,
                motivo: motivo
            },
            success: function(response) {
                $mensaje.removeClass('em-success em-error');

                if (response.success) {
                    $form.html('<div class="em-success" style="padding: 2rem; text-align: center;">' +
                        '<p style="font-size: 1.25rem; margin: 0;">Te has dado de baja correctamente.</p>' +
                        '<p style="color: #666; margin-top: 0.5rem;">Lamentamos verte partir.</p>' +
                        '</div>');
                } else {
                    $mensaje.addClass('em-error').html(response.error || 'Error').show();
                    $btn.prop('disabled', false).text('Confirmar baja');
                }
            },
            error: function() {
                $mensaje.addClass('em-error').html('Error de conexión').show();
                $btn.prop('disabled', false).text('Confirmar baja');
            }
        });
    });

    // Mostrar/ocultar campo "otro motivo"
    $(document).on('change', '#em-form-baja select[name="motivo"]', function() {
        const $campoOtro = $(this).closest('form').find('.em-campo-otro');

        if ($(this).val() === 'otro') {
            $campoOtro.slideDown();
        } else {
            $campoOtro.slideUp();
        }
    });

})(jQuery);
