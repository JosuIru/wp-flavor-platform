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
            this.initCharts();
        },

        mostrarAviso: function(mensaje, tipo) {
            tipo = tipo || 'error';
            var $contenedor = $('.gc-dashboard-notice-container');
            if (!$contenedor.length) {
                $contenedor = $('<div class="gc-dashboard-notice-container"></div>').appendTo('body');
            }

            var $aviso = $('<div class="gc-dashboard-notice gc-dashboard-notice-' + tipo + '"></div>').text(mensaje);
            $contenedor.append($aviso);

            setTimeout(function() {
                $aviso.addClass('is-visible');
            }, 10);

            setTimeout(function() {
                $aviso.removeClass('is-visible');
                setTimeout(function() {
                    $aviso.remove();
                }, 200);
            }, 3000);
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
         * Inicializa gráficos del panel
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            if (!gcDashboardData || !gcDashboardData.charts) {
                return;
            }
            var canvas = document.getElementById('gc-user-activity-chart');
            if (!canvas) {
                return;
            }
            var ctx = canvas.getContext('2d');
            var labels = gcDashboardData.charts.labels || [];
            var series = gcDashboardData.charts.series || [];
            var cycleLabels = gcDashboardData.charts.cycleLabels || [];
            var cycleSeries = gcDashboardData.charts.cycleSeries || [];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pedidos',
                        data: series,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });

            var cycleCanvas = document.getElementById('gc-user-cycle-chart');
            if (cycleCanvas && cycleLabels.length > 0) {
                var cycleCtx = cycleCanvas.getContext('2d');
                new Chart(cycleCtx, {
                    type: 'bar',
                    data: {
                        labels: cycleLabels,
                        datasets: [{
                            label: 'Importe',
                            data: cycleSeries,
                            backgroundColor: '#16a34a'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
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
            var $btn = $(this);
            GCDashboard.solicitarConfirmacion(gcDashboardData.i18n.confirmar_vaciar, function() {
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
            });
        },

        /**
         * Convierte la lista a pedido
         */
        convertirAPedido: function() {
            var $btn = $(this);
            GCDashboard.solicitarConfirmacion(gcDashboardData.i18n.confirmar_convertir, function() {
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
                            GCDashboard.mostrarAviso(response.data.mensaje, 'success');
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
            var suscripcionId = $(this).data('suscripcion-id');
            var $card = $(this).closest('.gc-suscripcion-card');
            GCDashboard.solicitarConfirmacion('¿Estás seguro de cancelar esta suscripción? Esta acción no se puede deshacer.', function() {
                GCDashboard.accionSuscripcion('gc_cancelar_suscripcion', suscripcionId, $card);
            });
        },

        /**
         * Ejecuta acción sobre suscripción
         */
        accionSuscripcion: function(accion, suscripcionId, $card) {
            var nonce = gcDashboardData.suscripcion_nonce || gcDashboardData.nonce;

            GCDashboard.mostrarCargando($card);

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: accion,
                    nonce: nonce,
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

            var nonce = gcDashboardData.suscripcion_nonce || gcDashboardData.nonce;

            $.ajax({
                url: gcDashboardData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gc_crear_suscripcion',
                    nonce: nonce,
                    consumidor_id: consumidorId,
                    tipo_cesta_id: cestaId,
                    frecuencia: frecuencia
                },
                success: function(response) {
                    if (response.success) {
                        GCDashboard.mostrarAviso(response.data.mensaje, 'success');
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
            this.mostrarAviso(mensaje, 'error');
        },

        solicitarConfirmacion: function(mensaje, onConfirm) {
            $('.gc-notificacion-confirm').remove();

            var $notif = $(`
                <div class="gc-notificacion gc-notificacion-warning gc-notificacion-confirm visible">
                    <span class="gc-notificacion-texto"></span>
                    <div class="gc-notificacion-acciones">
                        <button type="button" class="gc-notificacion-btn gc-notificacion-btn-primary"></button>
                        <button type="button" class="gc-notificacion-btn gc-notificacion-btn-secondary"></button>
                    </div>
                </div>
            `);

            $notif.find('.gc-notificacion-texto').text(mensaje || '¿Confirmar acción?');
            $notif.find('.gc-notificacion-btn-primary').text((gcDashboardData.i18n && gcDashboardData.i18n.confirmar) || 'Confirmar');
            $notif.find('.gc-notificacion-btn-secondary').text((gcDashboardData.i18n && gcDashboardData.i18n.cancelar) || 'Cancelar');

            $notif.find('.gc-notificacion-btn-primary').on('click', function() {
                $notif.remove();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });

            $notif.find('.gc-notificacion-btn-secondary').on('click', function() {
                $notif.remove();
            });

            $('body').append($notif);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        GCDashboard.init();
    });

})(jQuery);

(function() {
    var css = [
        '.gc-dashboard-notice-container{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:360px}',
        '.gc-dashboard-notice{opacity:0;transform:translateY(-6px);transition:all .2s ease;padding:12px 14px;border-radius:10px;box-shadow:0 10px 24px rgba(0,0,0,.12);font-size:14px}',
        '.gc-dashboard-notice.is-visible{opacity:1;transform:translateY(0)}',
        '.gc-dashboard-notice-error{background:#fee2e2;color:#991b1b}',
        '.gc-dashboard-notice-success{background:#dcfce7;color:#166534}'
    ].join('');
    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
})();
