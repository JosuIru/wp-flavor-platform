/**
 * JavaScript del módulo Fichaje de Empleados
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // Variables globales
    var config = window.fichajeEmpleados || {};
    var currentAction = null;
    var isProcessing = false;

    /**
     * Inicialización
     */
    function init() {
        // Actualizar reloj cada segundo
        updateClock();
        setInterval(updateClock, 1000);

        // Event listeners para botones de fichaje
        $(document).on('click', '.fichaje-btn[data-action]', handleFichajeClick);

        // Modal
        $(document).on('click', '#fichaje-modal-cancelar', closeModal);
        $(document).on('click', '#fichaje-modal-confirmar', confirmFichaje);
        $(document).on('click', '.fichaje-modal', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Formulario de solicitud de cambio
        $(document).on('submit', '#fichaje-solicitar-cambio-form', handleSolicitarCambio);

        // Filtros de historial
        $(document).on('change', '#fichaje-filtro-periodo', handleFiltroPeriodo);

        // Selectores de mes/año en resumen
        $(document).on('change', '#fichaje-mes, #fichaje-anio', handleCambioResumen);

        // Cerrar modal con Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    /**
     * Actualiza el reloj en tiempo real
     */
    function updateClock() {
        var now = new Date();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');

        $('.reloj-hora').text(hours + ':' + minutes);
    }

    /**
     * Maneja click en botones de fichaje
     */
    function handleFichajeClick(e) {
        e.preventDefault();

        if (isProcessing) {
            return;
        }

        var action = $(this).data('action');
        currentAction = action;

        // Mostrar modal con opciones
        showModal(action);
    }

    /**
     * Muestra el modal de confirmación
     */
    function showModal(action) {
        var $modal = $('#fichaje-modal-notas');
        var $titulo = $modal.find('.fichaje-modal-titulo');
        var $pausaTipos = $('#fichaje-pausa-tipos');

        // Configurar título según acción
        var titulos = {
            'entrada': config.strings.confirmEntrada || '¿Confirmas fichar entrada?',
            'salida': config.strings.confirmSalida || '¿Confirmas fichar salida?',
            'pausa': config.strings.confirmPausa || '¿Confirmas iniciar pausa?',
            'reanudar': config.strings.confirmReanudar || '¿Confirmas reanudar la jornada?'
        };

        $titulo.text(titulos[action] || 'Confirmar fichaje');

        // Mostrar selector de tipo de pausa solo para pausas
        if (action === 'pausa') {
            $pausaTipos.show();
        } else {
            $pausaTipos.hide();
        }

        // Limpiar notas
        $('#fichaje-notas').val('');

        // Mostrar modal
        $modal.fadeIn(200);
    }

    /**
     * Cierra el modal
     */
    function closeModal() {
        $('#fichaje-modal-notas').fadeOut(200);
        currentAction = null;
    }

    /**
     * Confirma y ejecuta el fichaje
     */
    function confirmFichaje() {
        if (isProcessing || !currentAction) {
            return;
        }

        var notas = $('#fichaje-notas').val();
        var tipoPausa = $('#fichaje-tipo-pausa').val();

        // Mapear acción a endpoint AJAX
        var endpoints = {
            'entrada': 'fichaje_entrada',
            'salida': 'fichaje_salida',
            'pausa': 'fichaje_pausa_iniciar',
            'reanudar': 'fichaje_pausa_finalizar'
        };

        var endpoint = endpoints[currentAction];

        if (!endpoint) {
            showMessage(config.strings.error || 'Error', 'error');
            return;
        }

        // Preparar datos
        var data = {
            action: endpoint,
            nonce: config.nonce,
            notas: notas
        };

        if (currentAction === 'pausa') {
            data.tipo_pausa = tipoPausa;
        }

        // Enviar petición
        isProcessing = true;
        $('#fichaje-modal-confirmar').text(config.strings.procesando || 'Procesando...').prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                closeModal();

                if (response.success) {
                    showMessage(response.data.mensaje || config.strings.exito, 'exito');
                    // Recargar página para actualizar estado
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data.message || config.strings.error, 'error');
                }
            },
            error: function() {
                closeModal();
                showMessage(config.strings.error || 'Error al procesar', 'error');
            },
            complete: function() {
                isProcessing = false;
                $('#fichaje-modal-confirmar').text('Confirmar').prop('disabled', false);
            }
        });
    }

    /**
     * Muestra un mensaje al usuario
     */
    function showMessage(text, type) {
        var $mensaje = $('#fichaje-mensaje');

        $mensaje
            .removeClass('mensaje-exito mensaje-error')
            .addClass('mensaje-' + type)
            .text(text)
            .fadeIn(200);

        // Ocultar después de 5 segundos
        setTimeout(function() {
            $mensaje.fadeOut(200);
        }, 5000);
    }

    /**
     * Maneja el formulario de solicitud de cambio
     */
    function handleSolicitarCambio(e) {
        e.preventDefault();

        if (isProcessing) {
            return;
        }

        var $form = $(this);
        var $btn = $form.find('.fichaje-btn-submit');
        var $message = $form.find('.fichaje-form-message');

        var data = {
            action: 'fichaje_solicitar_cambio',
            nonce: config.nonce,
            fecha: $form.find('#fichaje-fecha').val(),
            tipo: $form.find('#fichaje-tipo').val(),
            hora: $form.find('#fichaje-hora').val(),
            motivo: $form.find('#fichaje-motivo').val()
        };

        isProcessing = true;
        $btn.text(config.strings.procesando || 'Enviando...').prop('disabled', true);
        $message.removeClass('mensaje-exito mensaje-error').hide();

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $message.addClass('mensaje-exito').text(response.data.mensaje).show();
                    $form[0].reset();
                } else {
                    $message.addClass('mensaje-error').text(response.data.message).show();
                }
            },
            error: function() {
                $message.addClass('mensaje-error').text(config.strings.error).show();
            },
            complete: function() {
                isProcessing = false;
                $btn.text('Enviar Solicitud').prop('disabled', false);
            }
        });
    }

    /**
     * Maneja cambio de filtro de periodo en historial
     */
    function handleFiltroPeriodo() {
        var periodo = $(this).val();
        var url = new URL(window.location.href);
        url.searchParams.set('periodo', periodo);
        window.location.href = url.toString();
    }

    /**
     * Maneja cambio de mes/año en resumen
     */
    function handleCambioResumen() {
        var mes = $('#fichaje-mes').val();
        var anio = $('#fichaje-anio').val();
        var url = new URL(window.location.href);
        url.searchParams.set('mes', mes);
        url.searchParams.set('anio', anio);
        window.location.href = url.toString();
    }

    /**
     * Actualiza el estado visual de los botones
     */
    function updateButtonState(estado) {
        var $wrapper = $('.fichaje-boton-wrapper');
        $wrapper.attr('data-estado', estado);

        // Aquí se podría añadir lógica para cambiar botones dinámicamente
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(init);

})(jQuery);
