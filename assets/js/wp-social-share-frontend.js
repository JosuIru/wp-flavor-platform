/**
 * Flavor WP Social Share - Frontend Scripts
 *
 * Maneja la funcionalidad de compartir posts en la red social
 * desde el frontend con modal de opciones.
 */
(function($) {
    'use strict';

    var $modal = null;
    var $form = null;

    $(document).ready(function() {
        $modal = $('#flavor-modal-compartir');
        $form = $('#flavor-form-compartir');

        initBotonesCompartir();
        initModal();
        initFormulario();
    });

    /**
     * Inicializa los botones de compartir
     */
    function initBotonesCompartir() {
        $(document).on('click', '.flavor-btn-compartir-social', function(e) {
            e.preventDefault();
            abrirModal();
        });
    }

    /**
     * Inicializa eventos del modal
     */
    function initModal() {
        // Cerrar con X
        $modal.on('click', '.flavor-modal-close', function() {
            cerrarModal();
        });

        // Cerrar con overlay
        $modal.on('click', '.flavor-modal-overlay', function() {
            cerrarModal();
        });

        // Cerrar con botón cancelar
        $modal.on('click', '.flavor-btn-cancelar', function() {
            cerrarModal();
        });

        // Cerrar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                cerrarModal();
            }
        });

        // Radio cards - selección visual
        $modal.on('change', '.flavor-radio-card input[type="radio"]', function() {
            $('.flavor-radio-card').removeClass('selected');
            $(this).closest('.flavor-radio-card').addClass('selected');
        });
    }

    /**
     * Inicializa el formulario
     */
    function initFormulario() {
        $form.on('submit', function(e) {
            e.preventDefault();
            enviarCompartir();
        });
    }

    /**
     * Abre el modal
     */
    function abrirModal() {
        $modal.fadeIn(200);
        $('body').addClass('flavor-modal-abierto');
        $modal.find('textarea').focus();
    }

    /**
     * Cierra el modal
     */
    function cerrarModal() {
        $modal.fadeOut(200);
        $('body').removeClass('flavor-modal-abierto');
        resetearFormulario();
    }

    /**
     * Resetea el formulario
     */
    function resetearFormulario() {
        $form[0].reset();
        $('.flavor-radio-card').removeClass('selected');
        $('.flavor-radio-card:first-child').addClass('selected');
    }

    /**
     * Envía el formulario de compartir
     */
    function enviarCompartir() {
        var $btnSubmit = $form.find('.flavor-btn-compartir-submit');
        var postId = $form.data('post-id');

        // Obtener valores
        var mensaje = $form.find('[name="mensaje"]').val();
        var visibilidad = $form.find('[name="visibilidad"]:checked').val();
        var federar = $form.find('[name="federar"]').is(':checked') ? '1' : '0';

        // Recoger integraciones de módulos seleccionadas
        var integraciones = {};
        $form.find('.flavor-integracion-check:checked').each(function() {
            var modulo = $(this).data('modulo');
            integraciones[modulo] = '1';
        });

        // Deshabilitar botón
        $btnSubmit.prop('disabled', true)
            .html('<span class="dashicons dashicons-update spin"></span> ' +
                  flavorSocialShareFront.i18n.compartiendo);

        // Enviar AJAX
        $.ajax({
            url: flavorSocialShareFront.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_compartir_post_social',
                nonce: flavorSocialShareFront.nonce,
                post_id: postId,
                visibilidad: visibilidad,
                mensaje: mensaje,
                federar: federar,
                integraciones: integraciones
            },
            success: function(response) {
                if (response.success) {
                    // Cerrar modal
                    cerrarModal();

                    // Reemplazar botón original
                    $('.flavor-btn-compartir-social[data-post-id="' + postId + '"]')
                        .replaceWith(
                            '<span class="flavor-ya-compartido">' +
                            '<span class="dashicons dashicons-yes"></span> ' +
                            flavorSocialShareFront.i18n.compartido +
                            '</span>'
                        );

                    // Actualizar wrapper
                    $('.flavor-social-share-wrapper').addClass('flavor-ya-compartido-wrapper');
                    $('.flavor-share-intro').text(flavorSocialShareFront.i18n.compartido);

                    // Toast de éxito
                    mostrarToast(response.data.message, 'success');

                } else {
                    $btnSubmit.prop('disabled', false)
                        .html('<span class="dashicons dashicons-share"></span> ' +
                              flavorSocialShareFront.i18n.btnCompartir);
                    mostrarToast(response.data.message || flavorSocialShareFront.i18n.error, 'error');
                }
            },
            error: function() {
                $btnSubmit.prop('disabled', false)
                    .html('<span class="dashicons dashicons-share"></span> ' +
                          flavorSocialShareFront.i18n.btnCompartir);
                mostrarToast(flavorSocialShareFront.i18n.error, 'error');
            }
        });
    }

    /**
     * Muestra un toast de notificación
     */
    function mostrarToast(mensaje, tipo) {
        var bgColor = tipo === 'success' ? '#10b981' : '#ef4444';
        var icon = tipo === 'success' ? '✓' : '✕';

        var $toast = $('<div class="flavor-toast">' +
                       '<span class="flavor-toast-icon">' + icon + '</span>' +
                       '<span class="flavor-toast-msg">' + mensaje + '</span>' +
                       '</div>');

        $toast.css({
            position: 'fixed',
            bottom: '30px',
            left: '50%',
            transform: 'translateX(-50%) translateY(20px)',
            background: bgColor,
            color: '#fff',
            padding: '14px 24px',
            borderRadius: '12px',
            boxShadow: '0 8px 24px rgba(0,0,0,0.2)',
            zIndex: 999999,
            fontSize: '15px',
            fontWeight: '500',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            opacity: 0,
            transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
        });

        $('body').append($toast);

        // Animar entrada
        setTimeout(function() {
            $toast.css({
                opacity: 1,
                transform: 'translateX(-50%) translateY(0)'
            });
        }, 10);

        // Animar salida
        setTimeout(function() {
            $toast.css({
                opacity: 0,
                transform: 'translateX(-50%) translateY(20px)'
            });
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3500);
    }

})(jQuery);
