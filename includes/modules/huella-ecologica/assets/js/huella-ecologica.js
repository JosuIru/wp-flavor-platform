/**
 * Huella Ecológica Comunitaria - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const HuellaEcologica = {
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initCalculadora();
            this.initGraficos();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Calculadora - navegación por pasos
            $(document).on('click', '.he-paso', this.irAPaso.bind(this));
            $(document).on('click', '.he-btn-siguiente', this.siguientePaso.bind(this));
            $(document).on('click', '.he-btn-anterior', this.anteriorPaso.bind(this));
            $(document).on('click', '.he-btn-calcular', this.calcularHuella.bind(this));

            // Registrar huella
            $(document).on('submit', '.he-form-registro', this.registrarHuella.bind(this));

            // Registrar acción reductora
            $(document).on('click', '.he-accion-card', this.seleccionarAccion.bind(this));
            $(document).on('submit', '.he-form-accion', this.registrarAccion.bind(this));

            // Proyectos
            $(document).on('submit', '.he-form-proyecto', this.proponerProyecto.bind(this));
            $(document).on('click', '.he-btn-unirse', this.unirseProyecto.bind(this));

            // Filtros
            $(document).on('click', '.he-filtro-btn', this.cambiarFiltro.bind(this));
            $(document).on('change', '#he-periodo', this.cambiarPeriodo.bind(this));

            // Modal
            $(document).on('click', '.he-btn-abrir-modal', this.abrirModal.bind(this));
            $(document).on('click', '.he-modal__cerrar, .he-modal', this.cerrarModal.bind(this));
            $(document).on('click', '.he-modal__contenido', function(e) { e.stopPropagation(); });
        },

        /**
         * Inicializa la calculadora de huella
         */
        initCalculadora: function() {
            this.pasoActual = 1;
            this.totalPasos = $('.he-calculadora__seccion').length;
            this.datosCalculadora = {};

            this.actualizarPasos();
        },

        /**
         * Ir a un paso específico
         */
        irAPaso: function(e) {
            const $paso = $(e.currentTarget);
            const numeroPaso = parseInt($paso.data('paso'));

            if (numeroPaso && numeroPaso <= this.pasoActual) {
                this.pasoActual = numeroPaso;
                this.actualizarPasos();
            }
        },

        /**
         * Siguiente paso
         */
        siguientePaso: function(e) {
            e.preventDefault();

            // Guardar datos del paso actual
            this.guardarDatosPaso();

            if (this.pasoActual < this.totalPasos) {
                this.pasoActual++;
                this.actualizarPasos();
            }
        },

        /**
         * Paso anterior
         */
        anteriorPaso: function(e) {
            e.preventDefault();

            if (this.pasoActual > 1) {
                this.pasoActual--;
                this.actualizarPasos();
            }
        },

        /**
         * Actualiza la visualización de pasos
         */
        actualizarPasos: function() {
            // Actualizar indicadores
            $('.he-paso').each((index, elem) => {
                const $elem = $(elem);
                const numPaso = index + 1;

                $elem.removeClass('activo completado');
                if (numPaso < this.pasoActual) {
                    $elem.addClass('completado');
                } else if (numPaso === this.pasoActual) {
                    $elem.addClass('activo');
                }
            });

            // Mostrar sección correcta
            $('.he-calculadora__seccion').removeClass('activa');
            $(`.he-calculadora__seccion[data-paso="${this.pasoActual}"]`).addClass('activa');

            // Actualizar botones
            $('.he-btn-anterior').toggle(this.pasoActual > 1);
            $('.he-btn-siguiente').toggle(this.pasoActual < this.totalPasos);
            $('.he-btn-calcular').toggle(this.pasoActual === this.totalPasos);
        },

        /**
         * Guarda los datos del paso actual
         */
        guardarDatosPaso: function() {
            const $seccion = $(`.he-calculadora__seccion[data-paso="${this.pasoActual}"]`);

            $seccion.find('input, select').each((i, elem) => {
                const $elem = $(elem);
                const nombre = $elem.attr('name');
                if (nombre) {
                    this.datosCalculadora[nombre] = $elem.val();
                }
            });
        },

        /**
         * Calcula la huella ecológica
         */
        calcularHuella: function(e) {
            e.preventDefault();

            // Guardar último paso
            this.guardarDatosPaso();

            const $btn = $(e.currentTarget);
            $btn.prop('disabled', true).text('Calculando...');

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_calcular_huella',
                    nonce: flavorHuellaEcologica.nonce,
                    datos: this.datosCalculadora
                },
                success: (response) => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-chart-bar"></span> Calcular mi huella');

                    if (response.success) {
                        this.mostrarResultado(response.data);
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-chart-bar"></span> Calcular mi huella');
                    this.mostrarError(flavorHuellaEcologica.i18n.error);
                }
            });
        },

        /**
         * Muestra el resultado del cálculo
         */
        mostrarResultado: function(data) {
            const $resultado = $('.he-resultado');

            // Actualizar valores
            $resultado.find('.he-resultado__huella').html(
                `${data.huella_diaria}<small> kg CO2/día</small>`
            );

            // Comparativas
            $resultado.find('.he-comparativa-item').eq(0).find('.he-comparativa-item__valor').text(data.huella_mensual + ' kg');
            $resultado.find('.he-comparativa-item').eq(1).find('.he-comparativa-item__valor').text(data.huella_anual + ' kg');
            $resultado.find('.he-comparativa-item').eq(2).find('.he-comparativa-item__valor').text(data.comparativa.media_espana + ' kg');

            // Desglose
            const $desglose = $resultado.find('.he-resultado__desglose');
            $desglose.empty();

            for (const [categoria, valor] of Object.entries(data.desglose)) {
                const categoriaInfo = flavorHuellaEcologica.categorias[categoria.split('_')[0]] || {};
                $desglose.append(`
                    <div class="he-desglose-item">
                        <span class="he-desglose-item__categoria">
                            <span class="dashicons ${categoriaInfo.icono || 'dashicons-marker'}"></span>
                            ${categoria.replace('_', ' ')}
                        </span>
                        <span>${valor.toFixed(1)} kg</span>
                    </div>
                `);
            }

            // Mostrar resultado
            $resultado.show();

            // Scroll suave
            $('html, body').animate({
                scrollTop: $resultado.offset().top - 100
            }, 500);
        },

        /**
         * Registra un consumo de huella
         */
        registrarHuella: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_registrar_huella',
                    nonce: flavorHuellaEcologica.nonce,
                    fecha: $form.find('[name="fecha"]').val(),
                    categoria: $form.find('[name="categoria"]').val(),
                    valor: $form.find('[name="valor"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                        this.recargarHistorial();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorHuellaEcologica.i18n.error);
                }
            });
        },

        /**
         * Selecciona una acción reductora
         */
        seleccionarAccion: function(e) {
            const $card = $(e.currentTarget);
            const tipo = $card.data('tipo');

            $('.he-accion-card').removeClass('seleccionada');
            $card.addClass('seleccionada');

            $('#he-accion-tipo').val(tipo);
            $('.he-form-accion').show();
        },

        /**
         * Registra una acción reductora
         */
        registrarAccion: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');
            const tipo = $('#he-accion-tipo').val();

            if (!tipo) {
                this.mostrarError('Selecciona una acción');
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_registrar_accion',
                    nonce: flavorHuellaEcologica.nonce,
                    tipo: tipo,
                    fecha: $form.find('[name="fecha"]').val() || new Date().toISOString().split('T')[0],
                    cantidad: $form.find('[name="cantidad"]').val() || 1,
                    notas: $form.find('[name="notas"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                        $('.he-accion-card').removeClass('seleccionada');
                        $form.hide();
                        this.actualizarEstadisticas();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorHuellaEcologica.i18n.error);
                }
            });
        },

        /**
         * Propone un proyecto de compensación
         */
        proponerProyecto: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_proponer_proyecto',
                    nonce: flavorHuellaEcologica.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    meta_co2: $form.find('[name="meta_co2"]').val(),
                    ubicacion: $form.find('[name="ubicacion"]').val(),
                    tipo_proyecto: $form.find('[name="tipo_proyecto"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        this.cerrarModal();
                        location.reload();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorHuellaEcologica.i18n.error);
                }
            });
        },

        /**
         * Unirse a un proyecto
         */
        unirseProyecto: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const proyectoId = $btn.data('proyecto');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_unirse_proyecto',
                    nonce: flavorHuellaEcologica.nonce,
                    proyecto_id: proyectoId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Ya participas').addClass('he-btn--secondary').removeClass('he-btn--primary');
                        // Actualizar contador
                        const $participantes = $btn.closest('.he-proyecto-card').find('.he-participantes-count');
                        $participantes.text(response.data.participantes);
                    } else {
                        $btn.prop('disabled', false);
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorHuellaEcologica.i18n.error);
                }
            });
        },

        /**
         * Cambia el filtro activo
         */
        cambiarFiltro: function(e) {
            const $btn = $(e.currentTarget);
            const filtro = $btn.data('filtro');

            $('.he-filtro-btn').removeClass('activo');
            $btn.addClass('activo');

            // Filtrar elementos
            if (filtro === 'todos') {
                $('.he-registro-item, .he-proyecto-card').show();
            } else {
                $('.he-registro-item, .he-proyecto-card').hide();
                $(`.he-registro-item[data-categoria="${filtro}"], .he-proyecto-card[data-tipo="${filtro}"]`).show();
            }
        },

        /**
         * Cambia el período de estadísticas
         */
        cambiarPeriodo: function(e) {
            const periodo = $(e.currentTarget).val();

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_obtener_estadisticas',
                    nonce: flavorHuellaEcologica.nonce,
                    periodo: periodo
                },
                success: (response) => {
                    if (response.success) {
                        this.actualizarVistaEstadisticas(response.data);
                    }
                }
            });
        },

        /**
         * Actualiza la vista de estadísticas
         */
        actualizarVistaEstadisticas: function(data) {
            // Actualizar stats cards
            $('.he-stat-card[data-stat="huella"] .he-stat-card__valor').text(data.huella_total + ' kg');
            $('.he-stat-card[data-stat="reduccion"] .he-stat-card__valor').text(data.reduccion_total + ' kg');
            $('.he-stat-card[data-stat="neta"] .he-stat-card__valor').text(data.huella_neta + ' kg');

            // Actualizar gráfico de barras
            this.actualizarGraficoBarras(data.huella_por_categoria);
        },

        /**
         * Actualiza las estadísticas
         */
        actualizarEstadisticas: function() {
            const periodo = $('#he-periodo').val() || 'mes';

            $.ajax({
                url: flavorHuellaEcologica.ajaxurl,
                type: 'POST',
                data: {
                    action: 'he_obtener_estadisticas',
                    nonce: flavorHuellaEcologica.nonce,
                    periodo: periodo
                },
                success: (response) => {
                    if (response.success) {
                        this.actualizarVistaEstadisticas(response.data);
                    }
                }
            });
        },

        /**
         * Recarga el historial de registros
         */
        recargarHistorial: function() {
            // Esto podría hacer una llamada AJAX para recargar la lista
            // Por ahora, simplemente recargamos la página
            location.reload();
        },

        /**
         * Inicializa gráficos
         */
        initGraficos: function() {
            this.animarBarras();
        },

        /**
         * Anima las barras del gráfico
         */
        animarBarras: function() {
            $('.he-chart-bar__fill').each(function() {
                const $bar = $(this);
                const width = $bar.data('width') || $bar.attr('style')?.match(/width:\s*(\d+)%/)?.[1];

                if (width) {
                    $bar.css('width', '0');
                    setTimeout(() => {
                        $bar.css('width', width + '%');
                    }, 100);
                }
            });

            // Animar barra de progreso de proyectos
            $('.he-progreso-bar__fill').each(function() {
                const $bar = $(this);
                const width = $bar.data('progreso') || 0;

                $bar.css('width', '0');
                setTimeout(() => {
                    $bar.css('width', width + '%');
                }, 100);
            });
        },

        /**
         * Actualiza el gráfico de barras por categoría
         */
        actualizarGraficoBarras: function(datosCategorias) {
            const $container = $('.he-chart-bar');
            if (!$container.length || !datosCategorias) return;

            // Encontrar el valor máximo
            const maxValor = Math.max(...datosCategorias.map(c => parseFloat(c.total || 0)));

            datosCategorias.forEach(cat => {
                const $item = $container.find(`[data-categoria="${cat.categoria}"]`);
                const porcentaje = maxValor > 0 ? (parseFloat(cat.total) / maxValor * 100) : 0;

                $item.find('.he-chart-bar__value').text(parseFloat(cat.total).toFixed(1) + ' kg');
                $item.find('.he-chart-bar__fill').css('width', porcentaje + '%');
            });
        },

        /**
         * Abre un modal
         */
        abrirModal: function(e) {
            e.preventDefault();
            const modalId = $(e.currentTarget).data('modal');
            $(`#${modalId}`).addClass('activo');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Cierra el modal
         */
        cerrarModal: function(e) {
            if (e) {
                e.preventDefault();
            }
            $('.he-modal').removeClass('activo');
            $('body').css('overflow', '');
        },

        /**
         * Muestra mensaje de éxito
         */
        mostrarExito: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'success');
        },

        /**
         * Muestra mensaje de error
         */
        mostrarError: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'error');
        },

        /**
         * Muestra una notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            const $notif = $(`
                <div class="he-notificacion he-notificacion--${tipo}">
                    <span class="dashicons dashicons-${tipo === 'success' ? 'yes-alt' : 'warning'}"></span>
                    <span>${mensaje}</span>
                </div>
            `);

            $('body').append($notif);

            setTimeout(() => {
                $notif.addClass('visible');
            }, 10);

            setTimeout(() => {
                $notif.removeClass('visible');
                setTimeout(() => $notif.remove(), 300);
            }, 4000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        HuellaEcologica.init();
    });

    // Agregar estilos de notificación
    $('<style>')
        .text(`
            .he-notificacion {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: white;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(120%);
                transition: transform 0.3s ease;
                z-index: 10000;
            }
            .he-notificacion.visible {
                transform: translateX(0);
            }
            .he-notificacion--success {
                background: #27ae60;
            }
            .he-notificacion--error {
                background: #e74c3c;
            }
        `)
        .appendTo('head');

})(jQuery);
