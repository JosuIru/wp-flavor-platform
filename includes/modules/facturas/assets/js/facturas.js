/**
 * Facturas Module JavaScript
 * Sistema de facturacion para servicios comunitarios
 */

(function($) {
    'use strict';

    const FlavorFacturas = {
        config: {
            ajaxUrl: typeof flavorFacturasConfig !== 'undefined' ? flavorFacturasConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorFacturasConfig !== 'undefined' ? flavorFacturasConfig.nonce : '',
            currency: 'EUR',
            locale: 'es-ES'
        },

        init: function() {
            this.bindEvents();
            this.initFiltros();
            this.initModales();
        },

        bindEvents: function() {
            const self = this;

            // Filtros de facturas
            $(document).on('change', '.facturas-filtro-select', function() {
                self.filtrarFacturas();
            });

            $(document).on('click', '.facturas-filtro-btn', function(e) {
                e.preventDefault();
                self.filtrarFacturas();
            });

            // Acciones de factura
            $(document).on('click', '.btn-descargar-pdf', function(e) {
                e.preventDefault();
                const facturaId = $(this).data('factura-id');
                self.descargarPDF(facturaId);
            });

            $(document).on('click', '.btn-enviar-email', function(e) {
                e.preventDefault();
                const facturaId = $(this).data('factura-id');
                self.abrirModalEmail(facturaId);
            });

            $(document).on('click', '.btn-registrar-pago', function(e) {
                e.preventDefault();
                const facturaId = $(this).data('factura-id');
                const pendiente = $(this).data('pendiente');
                self.abrirModalPago(facturaId, pendiente);
            });

            $(document).on('click', '.btn-cancelar-factura', function(e) {
                e.preventDefault();
                const facturaId = $(this).data('factura-id');
                self.confirmarCancelacion(facturaId);
            });

            // Formulario de pago
            $(document).on('submit', '#form-registrar-pago', function(e) {
                e.preventDefault();
                self.registrarPago($(this));
            });

            // Formulario de email
            $(document).on('submit', '#form-enviar-email', function(e) {
                e.preventDefault();
                self.enviarEmail($(this));
            });

            // Cerrar modales
            $(document).on('click', '.facturas-modal-close, .facturas-modal-overlay', function(e) {
                if (e.target === this) {
                    self.cerrarModales();
                }
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.cerrarModales();
                }
            });

            // Paginacion
            $(document).on('click', '.facturas-paginacion-btn:not(.active)', function(e) {
                e.preventDefault();
                const pagina = $(this).data('pagina');
                self.cargarPagina(pagina);
            });

            // Lineas de factura dinamicas
            $(document).on('click', '.btn-agregar-linea', function(e) {
                e.preventDefault();
                self.agregarLineaFactura();
            });

            $(document).on('click', '.btn-eliminar-linea', function(e) {
                e.preventDefault();
                self.eliminarLineaFactura($(this).closest('.factura-linea-item'));
            });

            $(document).on('input', '.linea-cantidad, .linea-precio, .linea-descuento, .linea-iva', function() {
                self.calcularTotales();
            });
        },

        initFiltros: function() {
            // Inicializar datepickers si existen
            if ($.fn.datepicker) {
                $('.facturas-fecha-input').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        initModales: function() {
            // Crear contenedor de modales si no existe
            if (!$('#facturas-modales-container').length) {
                $('body').append('<div id="facturas-modales-container"></div>');
            }
        },

        filtrarFacturas: function() {
            const self = this;
            const contenedor = $('.facturas-lista-container');
            const filtros = {
                estado: $('#filtro-estado').val(),
                desde: $('#filtro-desde').val(),
                hasta: $('#filtro-hasta').val(),
                busqueda: $('#filtro-busqueda').val()
            };

            self.mostrarLoading(contenedor);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_listar',
                    nonce: self.config.nonce,
                    filtros: filtros
                },
                success: function(response) {
                    if (response.success) {
                        self.renderizarListaFacturas(response.data.facturas, contenedor);
                    } else {
                        self.mostrarMensaje('error', response.data.message || 'Error al cargar facturas');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                },
                complete: function() {
                    self.ocultarLoading(contenedor);
                }
            });
        },

        descargarPDF: function(facturaId) {
            const self = this;
            const boton = $(`.btn-descargar-pdf[data-factura-id="${facturaId}"]`);
            const textoOriginal = boton.html();

            boton.prop('disabled', true).html('<span class="facturas-spinner-sm"></span> Generando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_generar_pdf',
                    nonce: self.config.nonce,
                    factura_id: facturaId
                },
                success: function(response) {
                    if (response.success && response.data.pdf_url) {
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        self.mostrarMensaje('error', response.data.message || 'Error al generar PDF');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                },
                complete: function() {
                    boton.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        abrirModalEmail: function(facturaId) {
            const self = this;
            const modalHtml = `
                <div class="facturas-modal-overlay active" id="modal-email">
                    <div class="facturas-modal">
                        <div class="facturas-modal-header">
                            <h3 class="facturas-modal-title">Enviar Factura por Email</h3>
                            <button type="button" class="facturas-modal-close">&times;</button>
                        </div>
                        <form id="form-enviar-email">
                            <input type="hidden" name="factura_id" value="${facturaId}">
                            <div class="facturas-modal-body">
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label required">Email destinatario</label>
                                    <input type="email" name="email" class="facturas-form-input" required>
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label">Asunto personalizado</label>
                                    <input type="text" name="asunto" class="facturas-form-input" placeholder="Dejar vacio para usar asunto predeterminado">
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label">Mensaje adicional</label>
                                    <textarea name="mensaje" class="facturas-form-input" rows="4" placeholder="Mensaje opcional para incluir en el email"></textarea>
                                </div>
                            </div>
                            <div class="facturas-modal-footer">
                                <button type="button" class="facturas-btn facturas-btn-secondary facturas-modal-close">Cancelar</button>
                                <button type="submit" class="facturas-btn facturas-btn-primary">Enviar Email</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $('#facturas-modales-container').html(modalHtml);
        },

        abrirModalPago: function(facturaId, importePendiente) {
            const self = this;
            const importeFormateado = self.formatearMoneda(importePendiente);
            const modalHtml = `
                <div class="facturas-modal-overlay active" id="modal-pago">
                    <div class="facturas-modal">
                        <div class="facturas-modal-header">
                            <h3 class="facturas-modal-title">Registrar Pago</h3>
                            <button type="button" class="facturas-modal-close">&times;</button>
                        </div>
                        <form id="form-registrar-pago">
                            <input type="hidden" name="factura_id" value="${facturaId}">
                            <div class="facturas-modal-body">
                                <div class="facturas-mensaje facturas-mensaje-info">
                                    Importe pendiente: <strong>${importeFormateado}</strong>
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label required">Importe del pago</label>
                                    <input type="number" name="importe" class="facturas-form-input" step="0.01" min="0.01" max="${importePendiente}" value="${importePendiente}" required>
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label required">Fecha de pago</label>
                                    <input type="date" name="fecha_pago" class="facturas-form-input" value="${self.fechaHoy()}" required>
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label required">Metodo de pago</label>
                                    <select name="metodo_pago" class="facturas-form-input" required>
                                        <option value="transferencia">Transferencia bancaria</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="bizum">Bizum</option>
                                        <option value="domiciliacion">Domiciliacion</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="facturas-form-grupo">
                                    <label class="facturas-form-label">Referencia / Notas</label>
                                    <input type="text" name="referencia" class="facturas-form-input" placeholder="Numero de operacion, referencia bancaria...">
                                </div>
                            </div>
                            <div class="facturas-modal-footer">
                                <button type="button" class="facturas-btn facturas-btn-secondary facturas-modal-close">Cancelar</button>
                                <button type="submit" class="facturas-btn facturas-btn-success">Registrar Pago</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $('#facturas-modales-container').html(modalHtml);
        },

        registrarPago: function(form) {
            const self = this;
            const submitBtn = form.find('button[type="submit"]');
            const textoOriginal = submitBtn.html();

            submitBtn.prop('disabled', true).html('<span class="facturas-spinner-sm"></span> Procesando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_registrar_pago',
                    nonce: self.config.nonce,
                    factura_id: form.find('[name="factura_id"]').val(),
                    importe: form.find('[name="importe"]').val(),
                    fecha_pago: form.find('[name="fecha_pago"]').val(),
                    metodo_pago: form.find('[name="metodo_pago"]').val(),
                    referencia: form.find('[name="referencia"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarMensaje('success', 'Pago registrado correctamente');
                        self.cerrarModales();
                        self.actualizarVistaFactura(form.find('[name="factura_id"]').val());
                    } else {
                        self.mostrarMensaje('error', response.data.message || 'Error al registrar pago');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        enviarEmail: function(form) {
            const self = this;
            const submitBtn = form.find('button[type="submit"]');
            const textoOriginal = submitBtn.html();

            submitBtn.prop('disabled', true).html('<span class="facturas-spinner-sm"></span> Enviando...');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_enviar_email',
                    nonce: self.config.nonce,
                    factura_id: form.find('[name="factura_id"]').val(),
                    email: form.find('[name="email"]').val(),
                    asunto: form.find('[name="asunto"]').val(),
                    mensaje: form.find('[name="mensaje"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarMensaje('success', 'Email enviado correctamente');
                        self.cerrarModales();
                    } else {
                        self.mostrarMensaje('error', response.data.message || 'Error al enviar email');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        confirmarCancelacion: function(facturaId) {
            const self = this;

            if (confirm('Esta seguro de que desea cancelar esta factura? Esta accion no se puede deshacer.')) {
                self.cancelarFactura(facturaId);
            }
        },

        cancelarFactura: function(facturaId) {
            const self = this;

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_cancelar',
                    nonce: self.config.nonce,
                    factura_id: facturaId
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarMensaje('success', 'Factura cancelada correctamente');
                        location.reload();
                    } else {
                        self.mostrarMensaje('error', response.data.message || 'Error al cancelar factura');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                }
            });
        },

        agregarLineaFactura: function() {
            const self = this;
            const contenedor = $('.factura-lineas-container');
            const indice = contenedor.find('.factura-linea-item').length;

            const lineaHtml = `
                <div class="factura-linea-item" data-indice="${indice}">
                    <div class="linea-grid">
                        <div class="linea-concepto">
                            <input type="text" name="lineas[${indice}][concepto]" class="facturas-form-input" placeholder="Concepto" required>
                        </div>
                        <div class="linea-cantidad">
                            <input type="number" name="lineas[${indice}][cantidad]" class="facturas-form-input linea-cantidad" value="1" min="0.01" step="0.01" required>
                        </div>
                        <div class="linea-precio">
                            <input type="number" name="lineas[${indice}][precio]" class="facturas-form-input linea-precio" placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="linea-descuento">
                            <input type="number" name="lineas[${indice}][descuento]" class="facturas-form-input linea-descuento" value="0" min="0" max="100" step="0.01">
                        </div>
                        <div class="linea-iva">
                            <select name="lineas[${indice}][iva]" class="facturas-form-input linea-iva">
                                <option value="21">21%</option>
                                <option value="10">10%</option>
                                <option value="4">4%</option>
                                <option value="0">0%</option>
                            </select>
                        </div>
                        <div class="linea-total">
                            <span class="linea-total-valor">0.00</span>
                        </div>
                        <div class="linea-acciones">
                            <button type="button" class="facturas-btn facturas-btn-danger facturas-btn-sm btn-eliminar-linea">X</button>
                        </div>
                    </div>
                </div>
            `;

            contenedor.append(lineaHtml);
            self.calcularTotales();
        },

        eliminarLineaFactura: function(linea) {
            const self = this;
            const contenedor = $('.factura-lineas-container');

            if (contenedor.find('.factura-linea-item').length > 1) {
                linea.remove();
                self.reindexarLineas();
                self.calcularTotales();
            } else {
                self.mostrarMensaje('warning', 'Debe haber al menos una linea en la factura');
            }
        },

        reindexarLineas: function() {
            $('.factura-linea-item').each(function(indice) {
                $(this).attr('data-indice', indice);
                $(this).find('input, select').each(function() {
                    const nombre = $(this).attr('name');
                    if (nombre) {
                        $(this).attr('name', nombre.replace(/\[\d+\]/, `[${indice}]`));
                    }
                });
            });
        },

        calcularTotales: function() {
            const self = this;
            let baseImponible = 0;
            let totalIva = 0;

            $('.factura-linea-item').each(function() {
                const cantidad = parseFloat($(this).find('.linea-cantidad').val()) || 0;
                const precio = parseFloat($(this).find('.linea-precio').val()) || 0;
                const descuento = parseFloat($(this).find('.linea-descuento').val()) || 0;
                const ivaPorcentaje = parseFloat($(this).find('.linea-iva').val()) || 0;

                const subtotal = cantidad * precio;
                const descuentoImporte = subtotal * (descuento / 100);
                const baseLinea = subtotal - descuentoImporte;
                const ivaLinea = baseLinea * (ivaPorcentaje / 100);
                const totalLinea = baseLinea + ivaLinea;

                $(this).find('.linea-total-valor').text(self.formatearNumero(totalLinea));

                baseImponible += baseLinea;
                totalIva += ivaLinea;
            });

            const retencionPorcentaje = parseFloat($('#retencion-porcentaje').val()) || 0;
            const retencion = baseImponible * (retencionPorcentaje / 100);
            const total = baseImponible + totalIva - retencion;

            $('#resumen-base').text(self.formatearMoneda(baseImponible));
            $('#resumen-iva').text(self.formatearMoneda(totalIva));
            $('#resumen-retencion').text(self.formatearMoneda(retencion));
            $('#resumen-total').text(self.formatearMoneda(total));

            $('[name="base_imponible"]').val(baseImponible.toFixed(2));
            $('[name="total_iva"]').val(totalIva.toFixed(2));
            $('[name="total_retencion"]').val(retencion.toFixed(2));
            $('[name="total"]').val(total.toFixed(2));
        },

        cargarPagina: function(pagina) {
            const self = this;
            const contenedor = $('.facturas-lista-container');

            self.mostrarLoading(contenedor);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_facturas_listar',
                    nonce: self.config.nonce,
                    pagina: pagina,
                    filtros: self.obtenerFiltrosActuales()
                },
                success: function(response) {
                    if (response.success) {
                        self.renderizarListaFacturas(response.data.facturas, contenedor);
                        self.actualizarPaginacion(response.data.paginacion);
                    }
                },
                complete: function() {
                    self.ocultarLoading(contenedor);
                }
            });
        },

        actualizarVistaFactura: function(facturaId) {
            location.reload();
        },

        obtenerFiltrosActuales: function() {
            return {
                estado: $('#filtro-estado').val(),
                desde: $('#filtro-desde').val(),
                hasta: $('#filtro-hasta').val(),
                busqueda: $('#filtro-busqueda').val()
            };
        },

        renderizarListaFacturas: function(facturas, contenedor) {
            const self = this;

            if (!facturas || facturas.length === 0) {
                contenedor.html(`
                    <div class="facturas-empty">
                        <div class="facturas-empty-icon">📋</div>
                        <p class="facturas-empty-texto">No se encontraron facturas</p>
                    </div>
                `);
                return;
            }

            let html = '<div class="facturas-table-wrapper"><table class="facturas-table"><thead><tr>';
            html += '<th>Numero</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th>';
            html += '</tr></thead><tbody>';

            facturas.forEach(function(factura) {
                html += `<tr>
                    <td class="numero-factura">${factura.numero}</td>
                    <td>${factura.cliente}</td>
                    <td>${self.formatearFecha(factura.fecha)}</td>
                    <td class="importe">${self.formatearMoneda(factura.total)}</td>
                    <td><span class="facturas-estado facturas-estado-${factura.estado}">${factura.estado}</span></td>
                    <td>
                        <a href="?factura_id=${factura.id}" class="facturas-btn facturas-btn-sm facturas-btn-secondary">Ver</a>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            contenedor.html(html);
        },

        actualizarPaginacion: function(paginacion) {
            if (!paginacion) return;

            const self = this;
            let html = '<div class="facturas-paginacion">';

            for (let i = 1; i <= paginacion.total_paginas; i++) {
                const activeClass = i === paginacion.pagina_actual ? 'active' : '';
                html += `<button class="facturas-paginacion-btn ${activeClass}" data-pagina="${i}">${i}</button>`;
            }

            html += '</div>';
            $('.facturas-paginacion').replaceWith(html);
        },

        cerrarModales: function() {
            $('.facturas-modal-overlay').removeClass('active');
            setTimeout(function() {
                $('#facturas-modales-container').empty();
            }, 300);
        },

        mostrarLoading: function(contenedor) {
            contenedor.addClass('facturas-loading-active');
            contenedor.prepend('<div class="facturas-loading"><div class="facturas-spinner"></div></div>');
        },

        ocultarLoading: function(contenedor) {
            contenedor.removeClass('facturas-loading-active');
            contenedor.find('.facturas-loading').remove();
        },

        mostrarMensaje: function(tipo, texto) {
            const mensaje = $(`<div class="facturas-mensaje facturas-mensaje-${tipo}">${texto}</div>`);
            $('.facturas-container').prepend(mensaje);

            setTimeout(function() {
                mensaje.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        formatearMoneda: function(valor) {
            return new Intl.NumberFormat(this.config.locale, {
                style: 'currency',
                currency: this.config.currency
            }).format(valor);
        },

        formatearNumero: function(valor) {
            return new Intl.NumberFormat(this.config.locale, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(valor);
        },

        formatearFecha: function(fecha) {
            if (!fecha) return '';
            const date = new Date(fecha);
            return new Intl.DateTimeFormat(this.config.locale).format(date);
        },

        fechaHoy: function() {
            const hoy = new Date();
            return hoy.toISOString().split('T')[0];
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        FlavorFacturas.init();
    });

    // Exponer globalmente para uso externo
    window.FlavorFacturas = FlavorFacturas;

})(jQuery);
