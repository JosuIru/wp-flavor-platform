/**
 * JavaScript del Modulo de Transparencia
 * Portal de transparencia y rendicion de cuentas
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    /**
     * Objeto principal del modulo Transparencia
     */
    const TransparenciaModule = {
        /**
         * Configuracion
         */
        config: {
            ajaxUrl: typeof flavorTransparencia !== 'undefined' ? flavorTransparencia.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorTransparencia !== 'undefined' ? flavorTransparencia.nonce : '',
            chartColors: [
                '#1e3a5f', '#2d5a87', '#3498db', '#27ae60',
                '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c'
            ],
            animationDuration: 300
        },

        /**
         * Instancias de graficos Chart.js
         */
        charts: {},

        /**
         * Estado actual
         */
        state: {
            paginaActual: 1,
            categoriaActual: '',
            terminoBusqueda: '',
            cargando: false
        },

        /**
         * Inicializacion
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initTooltips();
        },

        /**
         * Bindear eventos
         */
        bindEvents: function() {
            const self = this;

            // Navegacion por categorias
            $(document).on('click', '.transparencia-nav-item', function(evento) {
                evento.preventDefault();
                const categoriaSeleccionada = $(this).data('categoria');
                self.filtrarPorCategoria(categoriaSeleccionada);
            });

            // Formulario de busqueda
            $(document).on('submit', '.transparencia-buscador-form', function(evento) {
                evento.preventDefault();
                self.buscarDocumentos($(this));
            });

            // Busqueda en tiempo real
            let temporizadorBusqueda;
            $(document).on('input', '.transparencia-buscar-input', function() {
                clearTimeout(temporizadorBusqueda);
                const inputBusqueda = $(this);
                temporizadorBusqueda = setTimeout(function() {
                    if (inputBusqueda.val().length >= 3 || inputBusqueda.val().length === 0) {
                        self.buscarDocumentos(inputBusqueda.closest('form'));
                    }
                }, 500);
            });

            // Paginacion
            $(document).on('click', '.transparencia-paginacion-btn:not(:disabled)', function(evento) {
                evento.preventDefault();
                const numeroPagina = $(this).data('pagina');
                self.irAPagina(numeroPagina);
            });

            // Descarga de documentos
            $(document).on('click', '.transparencia-btn-descargar', function(evento) {
                evento.preventDefault();
                const documentoId = $(this).data('documento-id');
                self.descargarDocumento(documentoId);
            });

            // Formulario de solicitud de informacion
            $(document).on('submit', '.transparencia-solicitud-form', function(evento) {
                evento.preventDefault();
                self.enviarSolicitud($(this));
            });

            // Ver detalle de documento
            $(document).on('click', '.transparencia-btn-ver', function(evento) {
                evento.preventDefault();
                const documentoId = $(this).data('documento-id');
                self.verDocumento(documentoId);
            });

            // Cerrar modal
            $(document).on('click', '.transparencia-modal-cerrar, .transparencia-modal-overlay', function() {
                self.cerrarModal();
            });

            // Cambio de periodo en presupuesto
            $(document).on('change', '.transparencia-presupuesto-selector', function() {
                const periodoSeleccionado = $(this).val();
                self.cargarPresupuesto(periodoSeleccionado);
            });

            // Filtro de gastos
            $(document).on('change', '.transparencia-gastos-filtro', function() {
                const filtroCategoria = $(this).val();
                self.filtrarGastos(filtroCategoria);
            });

            // Ordenar tabla
            $(document).on('click', '.transparencia-tabla th[data-ordenar]', function() {
                const columnaOrdenar = $(this).data('ordenar');
                const direccionOrden = $(this).hasClass('asc') ? 'desc' : 'asc';
                self.ordenarTabla(columnaOrdenar, direccionOrden);
            });
        },

        /**
         * Filtrar documentos por categoria
         */
        filtrarPorCategoria: function(categoria) {
            const self = this;

            $('.transparencia-nav-item').removeClass('active');
            $(`.transparencia-nav-item[data-categoria="${categoria}"]`).addClass('active');

            this.state.categoriaActual = categoria;
            this.state.paginaActual = 1;

            this.cargarDocumentos();
        },

        /**
         * Buscar documentos
         */
        buscarDocumentos: function(formulario) {
            const self = this;
            const datosFormulario = formulario.serializeArray();
            const parametrosBusqueda = {};

            datosFormulario.forEach(function(campo) {
                parametrosBusqueda[campo.name] = campo.value;
            });

            this.state.terminoBusqueda = parametrosBusqueda.termino || '';
            this.state.categoriaActual = parametrosBusqueda.categoria || '';
            this.state.paginaActual = 1;

            this.cargarDocumentos();
        },

        /**
         * Cargar documentos via AJAX
         */
        cargarDocumentos: function() {
            const self = this;

            if (this.state.cargando) return;
            this.state.cargando = true;

            const contenedorDocumentos = $('.transparencia-documentos-grid');
            contenedorDocumentos.html(this.getLoaderHTML());

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'transparencia_buscar_documentos',
                    nonce: this.config.nonce,
                    termino: this.state.terminoBusqueda,
                    categoria: this.state.categoriaActual,
                    pagina: this.state.paginaActual
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.renderizarDocumentos(respuesta.data.documentos);
                        self.renderizarPaginacion(respuesta.data.paginacion);
                    } else {
                        self.mostrarMensaje('error', respuesta.data.mensaje || 'Error al cargar documentos');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion. Intente nuevamente.');
                },
                complete: function() {
                    self.state.cargando = false;
                }
            });
        },

        /**
         * Renderizar documentos
         */
        renderizarDocumentos: function(documentos) {
            const contenedor = $('.transparencia-documentos-grid');

            if (!documentos || documentos.length === 0) {
                contenedor.html(this.getEmptyHTML());
                return;
            }

            let htmlDocumentos = '';
            documentos.forEach(function(documento) {
                htmlDocumentos += `
                    <div class="transparencia-documento-card" data-id="${documento.id}">
                        <div class="transparencia-documento-header">
                            <span class="transparencia-documento-categoria">${documento.categoria}</span>
                            <h3 class="transparencia-documento-titulo">${self.escapeHtml(documento.titulo)}</h3>
                        </div>
                        <div class="transparencia-documento-body">
                            <p class="transparencia-documento-descripcion">${self.escapeHtml(documento.descripcion || '')}</p>
                            <div class="transparencia-documento-meta">
                                ${documento.periodo ? `<span><span class="dashicons dashicons-calendar"></span> ${documento.periodo}</span>` : ''}
                                ${documento.entidad ? `<span><span class="dashicons dashicons-building"></span> ${documento.entidad}</span>` : ''}
                                ${documento.fecha_publicacion ? `<span><span class="dashicons dashicons-clock"></span> ${documento.fecha_publicacion}</span>` : ''}
                            </div>
                        </div>
                        <div class="transparencia-documento-footer">
                            ${documento.importe ? `<span class="transparencia-documento-importe">${self.formatearMoneda(documento.importe)}</span>` : '<span></span>'}
                            <div class="transparencia-documento-acciones">
                                <button class="transparencia-btn transparencia-btn-secondary transparencia-btn-ver" data-documento-id="${documento.id}">
                                    <span class="dashicons dashicons-visibility"></span> Ver
                                </button>
                                ${documento.tiene_archivo ? `
                                    <button class="transparencia-btn transparencia-btn-primary transparencia-btn-descargar" data-documento-id="${documento.id}">
                                        <span class="dashicons dashicons-download"></span>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            contenedor.html(htmlDocumentos);
        },

        /**
         * Renderizar paginacion
         */
        renderizarPaginacion: function(datosPaginacion) {
            const contenedor = $('.transparencia-paginacion');

            if (!datosPaginacion || datosPaginacion.total_paginas <= 1) {
                contenedor.empty();
                return;
            }

            let htmlPaginacion = '';
            const paginaActual = datosPaginacion.pagina_actual;
            const totalPaginas = datosPaginacion.total_paginas;

            // Boton anterior
            htmlPaginacion += `
                <button class="transparencia-paginacion-btn" data-pagina="${paginaActual - 1}" ${paginaActual === 1 ? 'disabled' : ''}>
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            `;

            // Numeros de pagina
            const rango = this.calcularRangoPaginacion(paginaActual, totalPaginas);
            for (let numeroPagina = rango.inicio; numeroPagina <= rango.fin; numeroPagina++) {
                htmlPaginacion += `
                    <button class="transparencia-paginacion-btn ${numeroPagina === paginaActual ? 'active' : ''}" data-pagina="${numeroPagina}">
                        ${numeroPagina}
                    </button>
                `;
            }

            // Boton siguiente
            htmlPaginacion += `
                <button class="transparencia-paginacion-btn" data-pagina="${paginaActual + 1}" ${paginaActual === totalPaginas ? 'disabled' : ''}>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            `;

            contenedor.html(htmlPaginacion);
        },

        /**
         * Calcular rango de paginacion
         */
        calcularRangoPaginacion: function(paginaActual, totalPaginas) {
            const maxBotones = 5;
            let paginaInicio = Math.max(1, paginaActual - Math.floor(maxBotones / 2));
            let paginaFin = Math.min(totalPaginas, paginaInicio + maxBotones - 1);

            if (paginaFin - paginaInicio < maxBotones - 1) {
                paginaInicio = Math.max(1, paginaFin - maxBotones + 1);
            }

            return { inicio: paginaInicio, fin: paginaFin };
        },

        /**
         * Ir a pagina
         */
        irAPagina: function(numeroPagina) {
            this.state.paginaActual = numeroPagina;
            this.cargarDocumentos();

            $('html, body').animate({
                scrollTop: $('.transparencia-documentos-grid').offset().top - 100
            }, this.config.animationDuration);
        },

        /**
         * Ver documento
         */
        verDocumento: function(documentoId) {
            const self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'transparencia_ver_documento',
                    nonce: this.config.nonce,
                    documento_id: documentoId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarModal(respuesta.data);
                    } else {
                        self.mostrarMensaje('error', respuesta.data.mensaje || 'Error al cargar documento');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion');
                }
            });
        },

        /**
         * Descargar documento
         */
        descargarDocumento: function(documentoId) {
            const urlDescarga = `${this.config.ajaxUrl}?action=transparencia_descargar_documento&nonce=${this.config.nonce}&documento_id=${documentoId}`;
            window.location.href = urlDescarga;
        },

        /**
         * Enviar solicitud de informacion
         */
        enviarSolicitud: function(formulario) {
            const self = this;
            const botonEnviar = formulario.find('button[type="submit"]');
            const textoOriginal = botonEnviar.html();

            botonEnviar.prop('disabled', true).html('<span class="transparencia-loader-spinner"></span> Enviando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'transparencia_enviar_solicitud',
                    nonce: this.config.nonce,
                    titulo: formulario.find('[name="titulo"]').val(),
                    descripcion: formulario.find('[name="descripcion"]').val(),
                    categoria: formulario.find('[name="categoria"]').val(),
                    email: formulario.find('[name="email"]').val()
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarMensaje('success', respuesta.data.mensaje);
                        formulario[0].reset();
                    } else {
                        self.mostrarMensaje('error', respuesta.data.mensaje || 'Error al enviar solicitud');
                    }
                },
                error: function() {
                    self.mostrarMensaje('error', 'Error de conexion. Intente nuevamente.');
                },
                complete: function() {
                    botonEnviar.prop('disabled', false).html(textoOriginal);
                }
            });
        },

        /**
         * Inicializar graficos Chart.js
         */
        initCharts: function() {
            const self = this;

            // Grafico de presupuesto
            const canvasPresupuesto = document.getElementById('transparencia-chart-presupuesto');
            if (canvasPresupuesto && typeof Chart !== 'undefined') {
                this.charts.presupuesto = new Chart(canvasPresupuesto.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: this.config.chartColors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const valorFormateado = self.formatearMoneda(context.parsed);
                                        return `${context.label}: ${valorFormateado}`;
                                    }
                                }
                            }
                        }
                    }
                });

                this.cargarDatosPresupuesto();
            }

            // Grafico de gastos por categoria
            const canvasGastos = document.getElementById('transparencia-chart-gastos');
            if (canvasGastos && typeof Chart !== 'undefined') {
                this.charts.gastos = new Chart(canvasGastos.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Gastos',
                            data: [],
                            backgroundColor: this.config.chartColors[0],
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return self.formatearMoneda(context.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(valor) {
                                        return self.formatearMoneda(valor, true);
                                    }
                                }
                            }
                        }
                    }
                });

                this.cargarDatosGastos();
            }

            // Grafico de evolucion temporal
            const canvasEvolucion = document.getElementById('transparencia-chart-evolucion');
            if (canvasEvolucion && typeof Chart !== 'undefined') {
                this.charts.evolucion = new Chart(canvasEvolucion.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [
                            {
                                label: 'Ingresos',
                                data: [],
                                borderColor: this.config.chartColors[3],
                                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Gastos',
                                data: [],
                                borderColor: this.config.chartColors[5],
                                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${self.formatearMoneda(context.parsed.y)}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(valor) {
                                        return self.formatearMoneda(valor, true);
                                    }
                                }
                            }
                        }
                    }
                });

                this.cargarDatosEvolucion();
            }
        },

        /**
         * Cargar datos de presupuesto para grafico
         */
        cargarDatosPresupuesto: function() {
            const self = this;
            const datosEmbebidos = $('#transparencia-datos-presupuesto').data('valores');

            if (datosEmbebidos && this.charts.presupuesto) {
                this.charts.presupuesto.data.labels = datosEmbebidos.etiquetas;
                this.charts.presupuesto.data.datasets[0].data = datosEmbebidos.valores;
                this.charts.presupuesto.update();
            }
        },

        /**
         * Cargar datos de gastos para grafico
         */
        cargarDatosGastos: function() {
            const self = this;
            const datosEmbebidos = $('#transparencia-datos-gastos').data('valores');

            if (datosEmbebidos && this.charts.gastos) {
                this.charts.gastos.data.labels = datosEmbebidos.categorias;
                this.charts.gastos.data.datasets[0].data = datosEmbebidos.importes;
                this.charts.gastos.update();
            }
        },

        /**
         * Cargar datos de evolucion para grafico
         */
        cargarDatosEvolucion: function() {
            const self = this;
            const datosEmbebidos = $('#transparencia-datos-evolucion').data('valores');

            if (datosEmbebidos && this.charts.evolucion) {
                this.charts.evolucion.data.labels = datosEmbebidos.periodos;
                this.charts.evolucion.data.datasets[0].data = datosEmbebidos.ingresos;
                this.charts.evolucion.data.datasets[1].data = datosEmbebidos.gastos;
                this.charts.evolucion.update();
            }
        },

        /**
         * Cargar presupuesto por periodo
         */
        cargarPresupuesto: function(periodo) {
            const self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'transparencia_obtener_presupuesto',
                    nonce: this.config.nonce,
                    periodo: periodo
                },
                success: function(respuesta) {
                    if (respuesta.success && self.charts.presupuesto) {
                        self.charts.presupuesto.data.labels = respuesta.data.etiquetas;
                        self.charts.presupuesto.data.datasets[0].data = respuesta.data.valores;
                        self.charts.presupuesto.update();

                        self.actualizarResumenPresupuesto(respuesta.data.resumen);
                    }
                }
            });
        },

        /**
         * Actualizar resumen de presupuesto
         */
        actualizarResumenPresupuesto: function(datosResumen) {
            if (!datosResumen) return;

            $('.transparencia-presupuesto-item-valor.total').text(this.formatearMoneda(datosResumen.total));
            $('.transparencia-presupuesto-item-valor.ingresos').text(this.formatearMoneda(datosResumen.ingresos));
            $('.transparencia-presupuesto-item-valor.gastos').text(this.formatearMoneda(datosResumen.gastos));
            $('.transparencia-presupuesto-item-valor.saldo').text(this.formatearMoneda(datosResumen.saldo));
        },

        /**
         * Filtrar gastos por categoria
         */
        filtrarGastos: function(categoria) {
            const self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'transparencia_filtrar_gastos',
                    nonce: this.config.nonce,
                    categoria: categoria
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.renderizarListaGastos(respuesta.data.gastos);
                    }
                }
            });
        },

        /**
         * Renderizar lista de gastos
         */
        renderizarListaGastos: function(listaGastos) {
            const contenedor = $('.transparencia-gastos-lista');

            if (!listaGastos || listaGastos.length === 0) {
                contenedor.html('<div class="transparencia-vacio">No hay gastos para mostrar</div>');
                return;
            }

            let htmlGastos = '';
            listaGastos.forEach(function(gasto) {
                htmlGastos += `
                    <div class="transparencia-gasto-item">
                        <div class="transparencia-gasto-info">
                            <div class="transparencia-gasto-concepto">${self.escapeHtml(gasto.concepto)}</div>
                            <div class="transparencia-gasto-fecha">${gasto.fecha}</div>
                        </div>
                        <div class="transparencia-gasto-importe">${self.formatearMoneda(gasto.importe)}</div>
                    </div>
                `;
            });

            contenedor.html(htmlGastos);
        },

        /**
         * Ordenar tabla
         */
        ordenarTabla: function(columna, direccion) {
            const tabla = $('.transparencia-tabla');
            const tbody = tabla.find('tbody');
            const filas = tbody.find('tr').toArray();

            tabla.find('th').removeClass('asc desc');
            tabla.find(`th[data-ordenar="${columna}"]`).addClass(direccion);

            filas.sort(function(filaA, filaB) {
                const valorA = $(filaA).find(`td[data-columna="${columna}"]`).text();
                const valorB = $(filaB).find(`td[data-columna="${columna}"]`).text();

                let comparacion;
                if (columna === 'importe') {
                    comparacion = parseFloat(valorA.replace(/[^\d.-]/g, '')) - parseFloat(valorB.replace(/[^\d.-]/g, ''));
                } else {
                    comparacion = valorA.localeCompare(valorB, 'es');
                }

                return direccion === 'asc' ? comparacion : -comparacion;
            });

            tbody.empty().append(filas);
        },

        /**
         * Mostrar modal
         */
        mostrarModal: function(datosDocumento) {
            const self = this;
            const htmlModal = `
                <div class="transparencia-modal">
                    <div class="transparencia-modal-overlay"></div>
                    <div class="transparencia-modal-contenido">
                        <button class="transparencia-modal-cerrar">&times;</button>
                        <div class="transparencia-modal-header">
                            <span class="transparencia-documento-categoria">${datosDocumento.categoria}</span>
                            <h2>${self.escapeHtml(datosDocumento.titulo)}</h2>
                        </div>
                        <div class="transparencia-modal-body">
                            ${datosDocumento.descripcion ? `<p>${self.escapeHtml(datosDocumento.descripcion)}</p>` : ''}
                            ${datosDocumento.contenido ? `<div class="transparencia-modal-contenido-texto">${datosDocumento.contenido}</div>` : ''}
                            <div class="transparencia-modal-meta">
                                ${datosDocumento.importe ? `<p><strong>Importe:</strong> ${self.formatearMoneda(datosDocumento.importe)}</p>` : ''}
                                ${datosDocumento.periodo ? `<p><strong>Periodo:</strong> ${datosDocumento.periodo}</p>` : ''}
                                ${datosDocumento.entidad ? `<p><strong>Entidad:</strong> ${datosDocumento.entidad}</p>` : ''}
                                ${datosDocumento.fecha_publicacion ? `<p><strong>Fecha publicacion:</strong> ${datosDocumento.fecha_publicacion}</p>` : ''}
                            </div>
                        </div>
                        <div class="transparencia-modal-footer">
                            ${datosDocumento.tiene_archivo ? `
                                <button class="transparencia-btn transparencia-btn-primary transparencia-btn-descargar" data-documento-id="${datosDocumento.id}">
                                    <span class="dashicons dashicons-download"></span> Descargar documento
                                </button>
                            ` : ''}
                            <button class="transparencia-btn transparencia-btn-secondary transparencia-modal-cerrar">Cerrar</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(htmlModal);
            setTimeout(function() {
                $('.transparencia-modal').addClass('active');
            }, 10);
        },

        /**
         * Cerrar modal
         */
        cerrarModal: function() {
            const modal = $('.transparencia-modal');
            modal.removeClass('active');
            setTimeout(function() {
                modal.remove();
            }, this.config.animationDuration);
        },

        /**
         * Inicializar tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const elemento = $(this);
                elemento.attr('title', elemento.data('tooltip'));
            });
        },

        /**
         * Mostrar mensaje
         */
        mostrarMensaje: function(tipo, texto) {
            const contenedorMensajes = $('.transparencia-mensajes');
            const clasesTipo = {
                'success': 'transparencia-mensaje-success',
                'error': 'transparencia-mensaje-error',
                'info': 'transparencia-mensaje-info'
            };

            const htmlMensaje = `
                <div class="transparencia-mensaje ${clasesTipo[tipo] || clasesTipo.info}">
                    <span class="dashicons dashicons-${tipo === 'success' ? 'yes-alt' : (tipo === 'error' ? 'warning' : 'info')}"></span>
                    <span>${this.escapeHtml(texto)}</span>
                </div>
            `;

            if (contenedorMensajes.length) {
                contenedorMensajes.html(htmlMensaje);
            } else {
                $('.transparencia-portal').prepend(`<div class="transparencia-mensajes">${htmlMensaje}</div>`);
            }

            setTimeout(function() {
                $('.transparencia-mensaje').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Obtener HTML del loader
         */
        getLoaderHTML: function() {
            return `
                <div class="transparencia-loader">
                    <div class="transparencia-loader-spinner"></div>
                </div>
            `;
        },

        /**
         * Obtener HTML de vacio
         */
        getEmptyHTML: function() {
            return `
                <div class="transparencia-vacio">
                    <div class="transparencia-vacio-icono">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <p>No se encontraron documentos</p>
                </div>
            `;
        },

        /**
         * Formatear moneda
         */
        formatearMoneda: function(valor, corto) {
            if (valor === null || valor === undefined) return '';

            const numero = parseFloat(valor);
            if (isNaN(numero)) return valor;

            if (corto && numero >= 1000000) {
                return (numero / 1000000).toFixed(1) + 'M';
            }
            if (corto && numero >= 1000) {
                return (numero / 1000).toFixed(1) + 'K';
            }

            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR'
            }).format(numero);
        },

        /**
         * Escapar HTML
         */
        escapeHtml: function(texto) {
            if (!texto) return '';
            const elementoDiv = document.createElement('div');
            elementoDiv.textContent = texto;
            return elementoDiv.innerHTML;
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        TransparenciaModule.init();
    });

    // Exponer globalmente para uso externo
    window.TransparenciaModule = TransparenciaModule;

})(jQuery);
