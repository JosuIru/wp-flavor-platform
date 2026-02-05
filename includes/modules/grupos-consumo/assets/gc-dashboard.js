/**
 * JavaScript del Dashboard de Grupos de Consumo
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // Objeto principal
    var GCDashboard = {
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Lista de compra
            $(document).on('click', '.gc-cantidad-menos', this.reducirCantidad);
            $(document).on('click', '.gc-cantidad-mas', this.aumentarCantidad);
            $(document).on('change', '.gc-cantidad-input', this.actualizarCantidad);
            $(document).on('click', '.gc-item-quitar', this.quitarItem);
            $(document).on('click', '.gc-vaciar-lista', this.vaciarLista);
            $(document).on('click', '.gc-convertir-pedido', this.convertirAPedido);

            // Suscripciones
            $(document).on('click', '.gc-pausar-suscripcion', this.pausarSuscripcion);
            $(document).on('click', '.gc-reanudar-suscripcion', this.reanudarSuscripcion);
            $(document).on('click', '.gc-cancelar-suscripcion', this.cancelarSuscripcion);
            $(document).on('click', '.gc-suscribirse-cesta', this.suscribirseCesta);
        },

        /**
         * Reduce cantidad de un item
         */
        reducirCantidad: function() {
            var $input = $(this).siblings('.gc-cantidad-input');
            var cantidadActual = parseFloat($input.val()) || 1;
            var paso = parseFloat($input.attr('step')) || 0.5;
            var minimo = parseFloat($input.attr('min')) || 0.5;

            var nuevaCantidad = Math.max(minimo, cantidadActual - paso);
            $input.val(nuevaCantidad).trigger('change');
        },

        /**
         * Aumenta cantidad de un item
         */
        aumentarCantidad: function() {
            var $input = $(this).siblings('.gc-cantidad-input');
            var cantidadActual = parseFloat($input.val()) || 1;
            var paso = parseFloat($input.attr('step')) || 0.5;

            var nuevaCantidad = cantidadActual + paso;
            $input.val(nuevaCantidad).trigger('change');
        },

        /**
         * Actualiza cantidad de un item
         */
        actualizarCantidad: function() {
            var $item = $(this).closest('.gc-lista-item');
            var itemId = $item.data('item-id');
            var cantidad = parseFloat($(this).val()) || 1;

            GCDashboard.mostrarCargando($item);

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_actualizar_cantidad_lista',
                    nonce: gcDashboardData.nonce,
                    item_id: itemId,
                    cantidad: cantidad
                },
                success: function(response) {
                    GCDashboard.ocultarCargando($item);

                    if (response.success) {
                        GCDashboard.actualizarSubtotal($item, cantidad);
                        GCDashboard.recalcularTotal();
                    } else {
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    GCDashboard.ocultarCargando($item);
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Quita un item de la lista
         */
        quitarItem: function() {
            var $item = $(this).closest('.gc-lista-item');
            var itemId = $item.data('item-id');

            GCDashboard.mostrarCargando($item);

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_quitar_lista_compra',
                    nonce: gcDashboardData.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        $item.slideUp(300, function() {
                            $(this).remove();
                            GCDashboard.recalcularTotal();
                            GCDashboard.actualizarBadge(response.data.count);

                            // Si no quedan items, mostrar estado vacío
                            if ($('.gc-lista-item').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        GCDashboard.ocultarCargando($item);
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    GCDashboard.ocultarCargando($item);
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Vacía la lista completa
         */
        vaciarLista: function() {
            if (!confirm(gcDashboardData.i18n.confirmar_vaciar)) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_vaciar_lista_compra',
                    nonce: gcDashboardData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        $btn.prop('disabled', false);
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Convierte la lista a pedido
         */
        convertirAPedido: function() {
            if (!confirm(gcDashboardData.i18n.confirmar_convertir)) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_convertir_lista_pedido',
                    nonce: gcDashboardData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.mensaje);
                        location.reload();
                    } else {
                        $btn.prop('disabled', false).text('Hacer Pedido');
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Hacer Pedido');
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Pausa una suscripción
         */
        pausarSuscripcion: function() {
            var suscripcionId = $(this).data('suscripcion-id');
            var $card = $(this).closest('.gc-suscripcion-card');

            GCDashboard.accionSuscripcion('gc_pausar_suscripcion', suscripcionId, $card);
        },

        /**
         * Reanuda una suscripción
         */
        reanudarSuscripcion: function() {
            var suscripcionId = $(this).data('suscripcion-id');
            var $card = $(this).closest('.gc-suscripcion-card');

            GCDashboard.accionSuscripcion('gc_reanudar_suscripcion', suscripcionId, $card);
        },

        /**
         * Cancela una suscripción
         */
        cancelarSuscripcion: function() {
            if (!confirm('¿Estás seguro de cancelar esta suscripción? Esta acción no se puede deshacer.')) {
                return;
            }

            var suscripcionId = $(this).data('suscripcion-id');
            var $card = $(this).closest('.gc-suscripcion-card');

            GCDashboard.accionSuscripcion('gc_cancelar_suscripcion', suscripcionId, $card);
        },

        /**
         * Ejecuta acción sobre suscripción
         */
        accionSuscripcion: function(accion, suscripcionId, $card) {
            GCDashboard.mostrarCargando($card);

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: accion,
                    nonce: gcDashboardData.nonce,
                    suscripcion_id: suscripcionId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        GCDashboard.ocultarCargando($card);
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    GCDashboard.ocultarCargando($card);
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Suscribirse a una cesta
         */
        suscribirseCesta: function() {
            var cestaId = $(this).data('cesta-id');
            var consumidorId = $(this).data('consumidor-id');
            var $btn = $(this);

            // Mostrar selector de frecuencia (simplificado - en producción sería un modal)
            var frecuencia = prompt('Selecciona frecuencia:\n1. Semanal\n2. Quincenal\n3. Mensual', '1');

            if (!frecuencia) return;

            var frecuenciaMap = {
                '1': 'semanal',
                '2': 'quincenal',
                '3': 'mensual'
            };

            frecuencia = frecuenciaMap[frecuencia] || 'semanal';

            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_crear_suscripcion',
                    nonce: gcDashboardData.nonce,
                    consumidor_id: consumidorId,
                    tipo_cesta_id: cestaId,
                    frecuencia: frecuencia
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.mensaje);
                        location.reload();
                    } else {
                        $btn.prop('disabled', false).text('Suscribirse');
                        GCDashboard.mostrarError(response.data.mensaje || response.data.error);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Suscribirse');
                    GCDashboard.mostrarError(gcDashboardData.i18n.error_generico);
                }
            });
        },

        /**
         * Actualiza el subtotal de un item
         */
        actualizarSubtotal: function($item, cantidad) {
            var precioTexto = $item.find('.gc-item-precio').text();
            var precio = parseFloat(precioTexto.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
            var subtotal = (precio * cantidad).toFixed(2);

            $item.find('.gc-item-subtotal').text(subtotal + ' €');
        },

        /**
         * Recalcula el total de la lista
         */
        recalcularTotal: function() {
            var total = 0;

            $('.gc-lista-item').each(function() {
                var subtotalTexto = $(this).find('.gc-item-subtotal').text();
                var subtotal = parseFloat(subtotalTexto.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
                total += subtotal;
            });

            $('.gc-total-valor').text(total.toFixed(2) + ' €');
        },

        /**
         * Actualiza el badge del tab
         */
        actualizarBadge: function(count) {
            var $badge = $('.flavor-dashboard-tab[data-tab="gc-lista-compra"] .gc-badge');
            if ($badge.length) {
                if (count > 0) {
                    $badge.text(count).show();
                } else {
                    $badge.hide();
                }
            }
        },

        /**
         * Muestra indicador de carga
         */
        mostrarCargando: function($elemento) {
            $elemento.addClass('gc-cargando');
        },

        /**
         * Oculta indicador de carga
         */
        ocultarCargando: function($elemento) {
            $elemento.removeClass('gc-cargando');
        },

        /**
         * Muestra mensaje de error
         */
        mostrarError: function(mensaje) {
            alert(mensaje);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        GCDashboard.init();
    });

})(jQuery);
