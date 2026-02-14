/**
 * JavaScript del módulo Socios - Frontend
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorSocios = {
        config: window.flavorSociosConfig || {},
        cuotaSeleccionada: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Abrir modal de pago
            $(document).on('click', '.flavor-soc-btn-pagar', this.abrirModalPago.bind(this));

            // Cambio de método de pago
            $(document).on('change', 'input[name="metodo_pago"]', this.cambiarMetodoPago.bind(this));

            // Confirmar pago
            $(document).on('click', '#btn-confirmar-pago', this.confirmarPago.bind(this));

            // Cerrar modales
            $(document).on('click', '.flavor-soc-modal-close, .flavor-soc-modal-cancelar', this.cerrarModal.bind(this));
            $(document).on('click', '.flavor-soc-modal', function(e) {
                if ($(e.target).hasClass('flavor-soc-modal')) {
                    FlavorSocios.cerrarModal();
                }
            });

            // Copiar al portapapeles
            $(document).on('click', '.flavor-soc-btn-copiar', this.copiarAlPortapapeles.bind(this));

            // ESC para cerrar
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    FlavorSocios.cerrarModal();
                }
            });
        },

        abrirModalPago: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const cuotaId = $btn.data('cuota');
            const importe = $btn.data('importe');
            const $cuota = $btn.closest('.flavor-soc-cuota');
            const periodo = $cuota.find('.periodo').text();

            this.cuotaSeleccionada = {
                id: cuotaId,
                importe: importe,
                periodo: periodo
            };

            // Actualizar modal
            $('#modal-pagar-cuota .flavor-soc-pago-periodo').text(periodo);
            $('#modal-pagar-cuota .flavor-soc-pago-importe').text(parseFloat(importe).toFixed(2).replace('.', ',') + ' €');
            $('#modal-pagar-cuota .referencia-pago').text('CUOTA-' + cuotaId);

            // Resetear formulario
            $('input[name="metodo_pago"][value="manual"]').prop('checked', true);
            this.mostrarPanelMetodo('manual');
            $('#referencia-pago-manual').val('');

            $('#modal-pagar-cuota').show();
        },

        cambiarMetodoPago: function(e) {
            const metodo = $(e.currentTarget).val();
            this.mostrarPanelMetodo(metodo);
        },

        mostrarPanelMetodo: function(metodo) {
            $('.flavor-soc-panel-pago').hide();
            $('#panel-pago-' + metodo).show();

            // Actualizar texto del botón
            if (metodo === 'manual') {
                $('#btn-confirmar-pago').html('<span class="dashicons dashicons-yes"></span> Confirmar pago');
            } else {
                $('#btn-confirmar-pago').html('<span class="dashicons dashicons-external"></span> Ir a pagar');
            }
        },

        confirmarPago: function(e) {
            e.preventDefault();

            if (!this.cuotaSeleccionada) {
                this.toast('No hay cuota seleccionada', 'error');
                return;
            }

            const metodo = $('input[name="metodo_pago"]:checked').val();
            const $btn = $('#btn-confirmar-pago');

            $btn.prop('disabled', true).html('<span class="flavor-soc-spinner"></span> Procesando...');

            if (metodo === 'manual') {
                // Pago manual: iniciar y confirmar con referencia
                this.procesarPagoManual($btn);
            } else {
                // Pago con gateway externo: redirigir
                this.iniciarPagoExterno(metodo, $btn);
            }
        },

        procesarPagoManual: function($btn) {
            const referencia = $('#referencia-pago-manual').val().trim();

            // Primero iniciar el pago
            this.ajax('socios_iniciar_pago', {
                cuota_id: this.cuotaSeleccionada.id,
                gateway_id: 'manual'
            })
            .done(function(res) {
                if (res.success) {
                    // Ahora confirmar si hay referencia
                    if (referencia) {
                        FlavorSocios.confirmarTransaccion(res.transaccion_id, referencia, $btn);
                    } else {
                        // Solo registrar intención
                        FlavorSocios.toast('Pago registrado. Envía la transferencia con la referencia indicada.', 'info');
                        FlavorSocios.cerrarModal();
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirmar pago');
                    }
                } else {
                    FlavorSocios.toast(res.error || 'Error al procesar', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirmar pago');
                }
            })
            .fail(function() {
                FlavorSocios.toast('Error de conexión', 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirmar pago');
            });
        },

        confirmarTransaccion: function(transaccionId, referencia, $btn) {
            this.ajax('socios_confirmar_pago', {
                transaccion_id: transaccionId,
                referencia: referencia
            })
            .done(function(res) {
                if (res.success) {
                    FlavorSocios.toast(res.mensaje || 'Pago confirmado', 'success');
                    FlavorSocios.cerrarModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    FlavorSocios.toast(res.error || 'Error al confirmar', 'error');
                }
            })
            .fail(function() {
                FlavorSocios.toast('Error de conexión', 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirmar pago');
            });
        },

        iniciarPagoExterno: function(metodo, $btn) {
            this.ajax('socios_iniciar_pago', {
                cuota_id: this.cuotaSeleccionada.id,
                gateway_id: metodo
            })
            .done(function(res) {
                if (res.success) {
                    if (res.tipo === 'redirect' && res.checkout_url) {
                        // Redirigir a pasarela
                        window.location.href = res.checkout_url;
                    } else {
                        FlavorSocios.toast('Procesando pago...', 'info');
                    }
                } else {
                    FlavorSocios.toast(res.error || 'Error al iniciar pago', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-external"></span> Ir a pagar');
                }
            })
            .fail(function() {
                FlavorSocios.toast('Error de conexión', 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-external"></span> Ir a pagar');
            });
        },

        cerrarModal: function() {
            $('.flavor-soc-modal').hide();
            this.cuotaSeleccionada = null;
        },

        copiarAlPortapapeles: function(e) {
            e.preventDefault();
            const $copiable = $(e.currentTarget).closest('.copiable');
            const texto = $copiable.data('copiar');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(texto).then(function() {
                    FlavorSocios.toast('Copiado al portapapeles', 'success');
                });
            } else {
                // Fallback
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(texto).select();
                document.execCommand('copy');
                $temp.remove();
                FlavorSocios.toast('Copiado al portapapeles', 'success');
            }
        },

        // Utilidades
        ajax: function(action, data) {
            data = data || {};
            data.action = action;
            data.nonce = this.config.nonce;

            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                dataType: 'json'
            });
        },

        toast: function(mensaje, tipo) {
            tipo = tipo || 'info';

            const $toast = $(`
                <div class="flavor-soc-toast flavor-soc-toast-${tipo}">
                    ${this.escapeHtml(mensaje)}
                </div>
            `);

            $('body').append($toast);

            setTimeout(() => $toast.addClass('show'), 10);

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        FlavorSocios.init();
    });

})(jQuery);
