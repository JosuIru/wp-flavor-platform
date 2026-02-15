/**
 * Economía de Suficiencia - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const Suficiencia = {
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initAnimaciones();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Evaluación de necesidades
            $(document).on('click', '.es-escala-btn', this.seleccionarEscala.bind(this));
            $(document).on('submit', '.es-form-evaluacion', this.guardarEvaluacion.bind(this));

            // Compromisos
            $(document).on('click', '.es-compromiso-card', this.seleccionarCompromiso.bind(this));
            $(document).on('submit', '.es-form-compromiso', this.hacerCompromiso.bind(this));
            $(document).on('click', '.es-btn-registrar-practica', this.registrarPractica.bind(this));

            // Reflexiones
            $(document).on('submit', '.es-form-reflexion', this.guardarReflexion.bind(this));

            // Biblioteca
            $(document).on('click', '.es-filtro-btn', this.filtrarBiblioteca.bind(this));
            $(document).on('submit', '.es-form-recurso', this.compartirRecurso.bind(this));
            $(document).on('click', '.es-btn-solicitar', this.solicitarPrestamo.bind(this));

            // Modal
            $(document).on('click', '.es-btn-abrir-modal', this.abrirModal.bind(this));
            $(document).on('click', '.es-modal__cerrar, .es-modal', this.cerrarModal.bind(this));
            $(document).on('click', '.es-modal__contenido', function(e) { e.stopPropagation(); });
        },

        /**
         * Seleccionar valor en escala de necesidades
         */
        seleccionarEscala: function(e) {
            const $btn = $(e.currentTarget);
            const $contenedor = $btn.closest('.es-necesidad__escala');
            const categoria = $contenedor.data('categoria');
            const valor = $btn.data('valor');

            $contenedor.find('.es-escala-btn').removeClass('seleccionado');
            $btn.addClass('seleccionado');

            // Guardar en campo oculto
            $(`#es-necesidad-${categoria}`).val(valor);
        },

        /**
         * Guardar evaluación de necesidades
         */
        guardarEvaluacion: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            // Recopilar evaluaciones
            const evaluaciones = {};
            $form.find('.es-necesidad__escala').each(function() {
                const cat = $(this).data('categoria');
                const val = $(this).find('.es-escala-btn.seleccionado').data('valor');
                if (val) {
                    evaluaciones[cat] = val;
                }
            });

            if (Object.keys(evaluaciones).length === 0) {
                this.mostrarError('Selecciona al menos una evaluación');
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_evaluar_necesidades',
                    nonce: flavorSuficiencia.nonce,
                    evaluaciones: evaluaciones
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);

                        if (response.data.areas_mejorar && response.data.areas_mejorar.length > 0) {
                            const areas = response.data.areas_mejorar.join(', ');
                            this.mostrarNotificacion(
                                `Áreas a cultivar: ${areas}`,
                                'info'
                            );
                        }
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Seleccionar un tipo de compromiso
         */
        seleccionarCompromiso: function(e) {
            const $card = $(e.currentTarget);
            const tipo = $card.data('tipo');

            $('.es-compromiso-card').removeClass('seleccionado');
            $card.addClass('seleccionado');

            $('#es-compromiso-tipo').val(tipo);
            $('.es-form-compromiso-detalle').slideDown();
        },

        /**
         * Hacer un compromiso
         */
        hacerCompromiso: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');
            const tipo = $('#es-compromiso-tipo').val();

            if (!tipo) {
                this.mostrarError('Selecciona un tipo de compromiso');
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_hacer_compromiso',
                    nonce: flavorSuficiencia.nonce,
                    tipo: tipo,
                    descripcion: $form.find('[name="descripcion"]').val(),
                    duracion: $form.find('[name="duracion"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Registrar práctica de un compromiso
         */
        registrarPractica: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const compromisoId = $btn.data('compromiso');
            const notas = $btn.closest('.es-compromiso-activo').find('.es-practica-notas').val() || '';

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_registrar_practica',
                    nonce: flavorSuficiencia.nonce,
                    compromiso_id: compromisoId,
                    notas: notas
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);

                        // Actualizar contador visual
                        const $compromiso = $btn.closest('.es-compromiso-activo');
                        const $diasSpan = $compromiso.find('.es-dias-cumplidos');
                        $diasSpan.text(response.data.dias_cumplidos);

                        // Actualizar barra de progreso
                        const duracion = parseInt($compromiso.data('duracion'));
                        const progreso = (response.data.dias_cumplidos / duracion) * 100;
                        $compromiso.find('.es-progreso-bar__fill').css('width', Math.min(progreso, 100) + '%');

                        $btn.text('✓ Hoy completado').addClass('es-btn--secondary').removeClass('es-btn--primary');
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Guardar reflexión
         */
        guardarReflexion: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_guardar_reflexion',
                    nonce: flavorSuficiencia.nonce,
                    categoria: $form.find('[name="categoria"]').val(),
                    pregunta: $form.find('[name="pregunta"]').val(),
                    respuesta: $form.find('[name="respuesta"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                        this.cerrarModal();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Filtrar biblioteca de objetos
         */
        filtrarBiblioteca: function(e) {
            const $btn = $(e.currentTarget);
            const filtro = $btn.data('filtro');

            $('.es-filtro-btn').removeClass('activo');
            $btn.addClass('activo');

            if (filtro === 'todos') {
                $('.es-recurso-card').show();
            } else {
                $('.es-recurso-card').hide();
                $(`.es-recurso-card[data-categoria="${filtro}"]`).show();
            }
        },

        /**
         * Compartir recurso en biblioteca
         */
        compartirRecurso: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_compartir_recurso',
                    nonce: flavorSuficiencia.nonce,
                    nombre: $form.find('[name="nombre"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    categoria: $form.find('[name="categoria"]').val(),
                    condiciones: $form.find('[name="condiciones"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        this.cerrarModal();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Solicitar préstamo de recurso
         */
        solicitarPrestamo: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const recursoId = $btn.data('recurso');
            const mensaje = prompt('Mensaje para el propietario (opcional):');

            if (mensaje === null) return; // Cancelado

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorSuficiencia.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_solicitar_prestamo',
                    nonce: flavorSuficiencia.nonce,
                    recurso_id: recursoId,
                    mensaje: mensaje,
                    dias: 7
                },
                success: (response) => {
                    $btn.prop('disabled', false);

                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Solicitado').removeClass('es-btn--primary').addClass('es-btn--secondary');
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorSuficiencia.i18n.error);
                }
            });
        },

        /**
         * Inicializar animaciones
         */
        initAnimaciones: function() {
            // Animar barras de progreso
            $('.es-progreso-bar__fill, .es-progreso-nivel__fill').each(function() {
                const $bar = $(this);
                const width = $bar.data('progreso') || $bar.css('width');

                $bar.css('width', '0');
                setTimeout(() => {
                    $bar.css('width', typeof width === 'number' ? width + '%' : width);
                }, 100);
            });

            // Animar barras de radar
            $('.es-radar-item__fill').each(function() {
                const $fill = $(this);
                const height = $fill.data('valor') * 20; // valor 1-5 * 20 = 20-100%

                $fill.css('height', '0');
                setTimeout(() => {
                    $fill.css('height', height + '%');
                }, 100);
            });
        },

        /**
         * Abrir modal
         */
        abrirModal: function(e) {
            e.preventDefault();
            const modalId = $(e.currentTarget).data('modal');
            $(`#${modalId}`).addClass('activo');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Cerrar modal
         */
        cerrarModal: function(e) {
            if (e) e.preventDefault();
            $('.es-modal').removeClass('activo');
            $('body').css('overflow', '');
        },

        /**
         * Mostrar mensaje de éxito
         */
        mostrarExito: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'success');
        },

        /**
         * Mostrar mensaje de error
         */
        mostrarError: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'error');
        },

        /**
         * Mostrar notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            const icono = tipo === 'success' ? 'yes-alt' : (tipo === 'error' ? 'warning' : 'info');
            const color = tipo === 'success' ? '#27ae60' : (tipo === 'error' ? '#e74c3c' : '#3498db');

            const $notif = $(`
                <div class="es-notificacion" style="
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    background: ${color};
                    color: white;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    transform: translateX(120%);
                    transition: transform 0.3s ease;
                    z-index: 10000;
                ">
                    <span class="dashicons dashicons-${icono}"></span>
                    <span>${mensaje}</span>
                </div>
            `);

            $('body').append($notif);

            setTimeout(() => $notif.css('transform', 'translateX(0)'), 10);

            setTimeout(() => {
                $notif.css('transform', 'translateX(120%)');
                setTimeout(() => $notif.remove(), 300);
            }, 4000);
        }
    };

    // Inicializar cuando DOM esté listo
    $(document).ready(function() {
        Suficiencia.init();
    });

})(jQuery);
