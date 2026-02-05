/**
 * JavaScript para Módulo de Grupos de Consumo
 */

(function($) {
    'use strict';

    const GruposConsumo = {

        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Botón unirse a pedido
            $(document).on('click', '.btn-unirse-pedido', this.abrirModalUnirse.bind(this));

            // Botón marcar como pagado
            $(document).on('click', '.btn-marcar-pagado', this.marcarPagado.bind(this));
        },

        /**
         * Abre modal para unirse a pedido
         */
        abrirModalUnirse: function(e) {
            e.preventDefault();
            const pedidoId = $(e.currentTarget).data('pedido-id');

            const modalHtml = `
                <div class="modal-unirse" id="modal-unirse-${pedidoId}">
                    <div class="modal-content">
                        <h3>Unirse al Pedido</h3>
                        <form class="form-unirse" data-pedido-id="${pedidoId}">
                            <div class="form-group">
                                <label for="cantidad-${pedidoId}">Cantidad</label>
                                <input type="number"
                                       id="cantidad-${pedidoId}"
                                       name="cantidad"
                                       min="0.1"
                                       step="0.1"
                                       required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Confirmar</button>
                                <button type="button" class="btn-secondary btn-cerrar-modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);

            // Evento cerrar modal
            $(document).on('click', '.btn-cerrar-modal, .modal-unirse', function(e) {
                if (e.target === this) {
                    $(this).closest('.modal-unirse').remove();
                }
            });

            // Evento enviar formulario
            $(document).on('submit', '.form-unirse', this.enviarUnirse.bind(this));
        },

        /**
         * Envía formulario para unirse
         */
        enviarUnirse: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const pedidoId = $form.data('pedido-id');
            const cantidad = $form.find('input[name="cantidad"]').val();
            const $btn = $form.find('button[type="submit"]');

            const textoOriginal = $btn.text();
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: gruposConsumoData.ajax_url,
                type: 'POST',
                data: {
                    action: 'grupos_consumo_unirse',
                    nonce: gruposConsumoData.nonce,
                    pedido_id: pedidoId,
                    cantidad: cantidad
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarMensaje('exito', response.data.message || '¡Te has unido al pedido!');
                        $('.modal-unirse').remove();

                        // Recargar página después de 2 segundos
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        this.mostrarMensaje('error', response.data.message || 'Error al unirse al pedido');
                        $btn.prop('disabled', false).text(textoOriginal);
                    }
                },
                error: () => {
                    this.mostrarMensaje('error', 'Error de conexión. Intenta de nuevo.');
                    $btn.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Marca pedido como pagado
         */
        marcarPagado: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const pedidoId = $btn.data('pedido-id');

            if (!confirm('¿Confirmas que has realizado el pago?')) {
                return;
            }

            const textoOriginal = $btn.text();
            $btn.prop('disabled', true).text('Marcando...');

            $.ajax({
                url: gruposConsumoData.ajax_url,
                type: 'POST',
                data: {
                    action: 'grupos_consumo_marcar_pagado',
                    nonce: gruposConsumoData.nonce,
                    pedido_id: pedidoId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarMensaje('exito', response.data.message || 'Marcado como pagado');
                        $btn.replaceWith('<span class="estado-badge estado-pagado">✅ Pagado</span>');
                    } else {
                        this.mostrarMensaje('error', response.data.message || 'Error al marcar como pagado');
                        $btn.prop('disabled', false).text(textoOriginal);
                    }
                },
                error: () => {
                    this.mostrarMensaje('error', 'Error de conexión.');
                    $btn.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Muestra mensaje
         */
        mostrarMensaje: function(tipo, mensaje) {
            const clase = tipo === 'exito' ? 'mensaje-exito' : 'mensaje-error';
            const $mensaje = $(`<div class="${clase}">${mensaje}</div>`);

            $('.grupos-consumo-pedidos, .grupos-consumo-mis-pedidos').prepend($mensaje);

            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                $mensaje.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        GruposConsumo.init();
    });

})(jQuery);
