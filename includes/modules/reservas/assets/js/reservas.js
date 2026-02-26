/**
 * JavaScript Frontend para Reservas
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    /**
     * Módulo principal de Reservas
     */
    const FlavorReservas = {

        /**
         * Configuración
         */
        config: {
            ajaxUrl: flavorReservas?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: flavorReservas?.nonce || '',
            i18n: flavorReservas?.i18n || {}
        },

        /**
         * Estado actual
         */
        state: {
            recursoSeleccionado: null,
            fechaSeleccionada: null,
            horaInicio: null,
            horaFin: null,
            mes: new Date().getMonth(),
            anio: new Date().getFullYear()
        },

        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initCalendario();
            this.initFormulario();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Crear reserva
            $(document).on('submit', '.js-reserva-form', this.handleCrearReserva.bind(this));

            // Cancelar reserva
            $(document).on('click', '.js-reserva-cancelar', this.handleCancelarReserva.bind(this));

            // Seleccionar día en calendario
            $(document).on('click', '.reservas-calendario__dia:not(.deshabilitado)', this.handleSeleccionarDia.bind(this));

            // Seleccionar horario
            $(document).on('click', '.reservas-horario-slot:not(.ocupado)', this.handleSeleccionarHorario.bind(this));

            // Navegación calendario
            $(document).on('click', '.js-calendario-prev', this.handleCalendarioPrev.bind(this));
            $(document).on('click', '.js-calendario-next', this.handleCalendarioNext.bind(this));

            // Filtros
            $(document).on('change', '.js-reservas-filtro', this.handleFiltrar.bind(this));
            $(document).on('submit', '.js-reservas-filtros-form', this.handleFiltrarForm.bind(this));

            // Verificar disponibilidad
            $(document).on('change', '.js-verificar-disponibilidad', this.handleVerificarDisponibilidad.bind(this));

            // Seleccionar recurso
            $(document).on('click', '.js-seleccionar-recurso', this.handleSeleccionarRecurso.bind(this));
        },

        /**
         * Crear reserva
         */
        handleCrearReserva: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('[type="submit"]');

            if ($btn.hasClass('loading')) return;

            // Validar formulario
            if (!this.validarFormulario($form)) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);
            const textoOriginal = $btn.text();
            $btn.text(this.config.i18n.cargando || 'Procesando...');

            const datos = {
                action: 'reservas_crear',
                nonce: this.config.nonce,
                recurso_id: $form.find('[name="recurso_id"]').val(),
                fecha: $form.find('[name="fecha"]').val(),
                hora_inicio: $form.find('[name="hora_inicio"]').val(),
                hora_fin: $form.find('[name="hora_fin"]').val(),
                notas: $form.find('[name="notas"]').val() || ''
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datos,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(
                            response.data.message || this.config.i18n.reserva_creada,
                            'success'
                        );

                        // Redirigir o actualizar UI
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            // Resetear formulario
                            $form[0].reset();
                            this.resetState();

                            // Recargar calendario
                            this.cargarCalendario();

                            // Disparar evento
                            $(document).trigger('reservas:creada', [response.data]);
                        }
                    } else {
                        this.showNotification(
                            response.data?.message || this.config.i18n.error,
                            'error'
                        );
                    }
                },
                error: () => {
                    this.showNotification(this.config.i18n.error || 'Error de conexión', 'error');
                },
                complete: () => {
                    $btn.removeClass('loading').prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Cancelar reserva
         */
        handleCancelarReserva: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const reservaId = $btn.data('reserva-id');

            if (!confirm(this.config.i18n.confirmacion || '¿Estás seguro de cancelar esta reserva?')) {
                return;
            }

            if ($btn.hasClass('loading')) return;

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reservas_cancelar',
                    reserva_id: reservaId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(
                            response.data.message || this.config.i18n.reserva_cancelada,
                            'success'
                        );

                        // Actualizar UI
                        const $card = $btn.closest('.mi-reserva-card');
                        $card.addClass('cancelada')
                             .find('.mi-reserva-card__estado')
                             .removeClass('confirmada pendiente')
                             .addClass('cancelada')
                             .text('Cancelada');

                        $btn.remove();

                        $(document).trigger('reservas:cancelada', [reservaId, response.data]);
                    } else {
                        this.showNotification(
                            response.data?.message || this.config.i18n.error,
                            'error'
                        );
                    }
                },
                error: () => {
                    this.showNotification(this.config.i18n.error || 'Error de conexión', 'error');
                },
                complete: () => {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Inicializar calendario
         */
        initCalendario: function() {
            const $calendario = $('.reservas-calendario');
            if (!$calendario.length) return;

            // Inicializar con mes actual
            this.state.mes = new Date().getMonth();
            this.state.anio = new Date().getFullYear();
        },

        /**
         * Seleccionar día en calendario
         */
        handleSeleccionarDia: function(e) {
            const $dia = $(e.currentTarget);
            const fecha = $dia.data('fecha');

            if (!fecha) return;

            // Actualizar selección visual
            $('.reservas-calendario__dia').removeClass('seleccionado');
            $dia.addClass('seleccionado');

            // Actualizar estado
            this.state.fechaSeleccionada = fecha;

            // Actualizar input hidden si existe
            $('[name="fecha"]').val(fecha);

            // Cargar horarios disponibles
            this.cargarHorarios(fecha);

            $(document).trigger('reservas:dia_seleccionado', [fecha]);
        },

        /**
         * Cargar horarios disponibles para una fecha
         */
        cargarHorarios: function(fecha) {
            const $horarios = $('.reservas-horarios');
            if (!$horarios.length) return;

            const recursoId = this.state.recursoSeleccionado || $('[name="recurso_id"]').val();

            $horarios.addClass('loading');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reservas_disponibilidad',
                    recurso_id: recursoId,
                    fecha: fecha,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data.horarios) {
                        this.renderHorarios(response.data.horarios);
                    }
                },
                complete: () => {
                    $horarios.removeClass('loading');
                }
            });
        },

        /**
         * Renderizar horarios disponibles
         */
        renderHorarios: function(horarios) {
            const $grid = $('.reservas-horarios__grid');

            let html = '';
            horarios.forEach(slot => {
                const clase = slot.disponible ? '' : 'ocupado';
                const estado = slot.disponible
                    ? (this.config.i18n.disponible || 'Disponible')
                    : (this.config.i18n.no_disponible || 'Ocupado');

                html += `
                    <div class="reservas-horario-slot ${clase}"
                         data-hora-inicio="${slot.inicio}"
                         data-hora-fin="${slot.fin}">
                        <div class="reservas-horario-slot__hora">${slot.inicio} - ${slot.fin}</div>
                        <div class="reservas-horario-slot__estado">${estado}</div>
                    </div>
                `;
            });

            $grid.html(html);
        },

        /**
         * Seleccionar horario
         */
        handleSeleccionarHorario: function(e) {
            const $slot = $(e.currentTarget);

            // Actualizar selección visual
            $('.reservas-horario-slot').removeClass('seleccionado');
            $slot.addClass('seleccionado');

            // Actualizar estado
            this.state.horaInicio = $slot.data('hora-inicio');
            this.state.horaFin = $slot.data('hora-fin');

            // Actualizar inputs hidden
            $('[name="hora_inicio"]').val(this.state.horaInicio);
            $('[name="hora_fin"]').val(this.state.horaFin);

            // Actualizar resumen
            this.actualizarResumen();

            $(document).trigger('reservas:horario_seleccionado', [this.state.horaInicio, this.state.horaFin]);
        },

        /**
         * Navegación calendario - Mes anterior
         */
        handleCalendarioPrev: function(e) {
            e.preventDefault();

            this.state.mes--;
            if (this.state.mes < 0) {
                this.state.mes = 11;
                this.state.anio--;
            }

            this.cargarCalendario();
        },

        /**
         * Navegación calendario - Mes siguiente
         */
        handleCalendarioNext: function(e) {
            e.preventDefault();

            this.state.mes++;
            if (this.state.mes > 11) {
                this.state.mes = 0;
                this.state.anio++;
            }

            this.cargarCalendario();
        },

        /**
         * Cargar calendario via AJAX
         */
        cargarCalendario: function() {
            const $calendario = $('.reservas-calendario');
            const recursoId = this.state.recursoSeleccionado || $('[name="recurso_id"]').val();

            $calendario.addClass('loading');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reservas_calendario',
                    recurso_id: recursoId,
                    mes: this.state.mes + 1,
                    anio: this.state.anio,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        $calendario.html(response.data.html);
                    }
                },
                complete: () => {
                    $calendario.removeClass('loading');
                }
            });
        },

        /**
         * Verificar disponibilidad
         */
        handleVerificarDisponibilidad: function(e) {
            const $form = $(e.currentTarget).closest('form');
            const recursoId = $form.find('[name="recurso_id"]').val();
            const fecha = $form.find('[name="fecha"]').val();
            const horaInicio = $form.find('[name="hora_inicio"]').val();
            const horaFin = $form.find('[name="hora_fin"]').val();

            if (!recursoId || !fecha || !horaInicio) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reservas_disponibilidad',
                    recurso_id: recursoId,
                    fecha: fecha,
                    hora_inicio: horaInicio,
                    hora_fin: horaFin,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    const $estado = $form.find('.js-disponibilidad-estado');

                    if (response.success && response.data.disponible) {
                        $estado.html(`<span class="disponible">${this.config.i18n.disponible || 'Disponible'}</span>`);
                        $form.find('[type="submit"]').prop('disabled', false);
                    } else {
                        $estado.html(`<span class="no-disponible">${this.config.i18n.no_disponible || 'No disponible'}</span>`);
                        $form.find('[type="submit"]').prop('disabled', true);
                    }
                }
            });
        },

        /**
         * Seleccionar recurso
         */
        handleSeleccionarRecurso: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const recursoId = $btn.data('recurso-id');

            this.state.recursoSeleccionado = recursoId;

            // Actualizar input hidden
            $('[name="recurso_id"]').val(recursoId);

            // Actualizar selección visual
            $('.recurso-card').removeClass('seleccionado');
            $btn.closest('.recurso-card').addClass('seleccionado');

            // Recargar calendario con disponibilidad del recurso
            this.cargarCalendario();

            $(document).trigger('reservas:recurso_seleccionado', [recursoId]);
        },

        /**
         * Manejar filtros
         */
        handleFiltrar: function(e) {
            const $container = $(e.currentTarget).closest('.reservas-container');
            this.filtrarRecursos($container);
        },

        handleFiltrarForm: function(e) {
            e.preventDefault();
            const $container = $(e.currentTarget).closest('.reservas-container');
            this.filtrarRecursos($container);
        },

        /**
         * Filtrar recursos via AJAX
         */
        filtrarRecursos: function($container) {
            const $grid = $container.find('.reservas-grid');
            const $filtros = $container.find('.reservas-filtros');

            const datos = {
                action: 'reservas_filtrar',
                nonce: this.config.nonce,
                tipo: $filtros.find('[name="tipo"]').val() || '',
                ubicacion: $filtros.find('[name="ubicacion"]').val() || '',
                fecha: $filtros.find('[name="fecha"]').val() || '',
                capacidad: $filtros.find('[name="capacidad"]').val() || '',
                busqueda: $filtros.find('[name="busqueda"]').val() || ''
            };

            $grid.addClass('loading').css('opacity', '0.5');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datos,
                success: (response) => {
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);

                        if (response.data.total !== undefined) {
                            $container.find('.reservas-total').text(response.data.total + ' recursos encontrados');
                        }
                    }
                },
                error: () => {
                    this.showNotification(this.config.i18n.error || 'Error al filtrar', 'error');
                },
                complete: () => {
                    $grid.removeClass('loading').css('opacity', '1');
                }
            });
        },

        /**
         * Inicializar formulario
         */
        initFormulario: function() {
            const $form = $('.js-reserva-form');
            if (!$form.length) return;

            // Inicializar datepicker si existe
            if (typeof $.fn.datepicker !== 'undefined') {
                $form.find('.js-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    onSelect: (fecha) => {
                        this.state.fechaSeleccionada = fecha;
                        this.cargarHorarios(fecha);
                    }
                });
            }
        },

        /**
         * Validar formulario
         */
        validarFormulario: function($form) {
            let valido = true;
            const errores = [];

            if (!$form.find('[name="recurso_id"]').val()) {
                errores.push('Selecciona un recurso');
                valido = false;
            }

            if (!$form.find('[name="fecha"]').val()) {
                errores.push('Selecciona una fecha');
                valido = false;
            }

            if (!$form.find('[name="hora_inicio"]').val()) {
                errores.push('Selecciona un horario');
                valido = false;
            }

            if (!valido) {
                this.showNotification(errores.join('. '), 'error');
            }

            return valido;
        },

        /**
         * Actualizar resumen de reserva
         */
        actualizarResumen: function() {
            const $resumen = $('.reserva-form__resumen');
            if (!$resumen.length) return;

            // Actualizar fecha
            if (this.state.fechaSeleccionada) {
                const fechaFormateada = this.formatearFecha(this.state.fechaSeleccionada);
                $resumen.find('.js-resumen-fecha').text(fechaFormateada);
            }

            // Actualizar horario
            if (this.state.horaInicio && this.state.horaFin) {
                $resumen.find('.js-resumen-horario').text(`${this.state.horaInicio} - ${this.state.horaFin}`);
            }
        },

        /**
         * Formatear fecha
         */
        formatearFecha: function(fecha) {
            const partes = fecha.split('-');
            const fechaObj = new Date(partes[0], partes[1] - 1, partes[2]);

            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return fechaObj.toLocaleDateString('es-ES', opciones);
        },

        /**
         * Resetear estado
         */
        resetState: function() {
            this.state.fechaSeleccionada = null;
            this.state.horaInicio = null;
            this.state.horaFin = null;

            $('.reservas-calendario__dia').removeClass('seleccionado');
            $('.reservas-horario-slot').removeClass('seleccionado');
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            if (typeof FlavorNotifications !== 'undefined') {
                FlavorNotifications.show(message, type);
                return;
            }

            const $notif = $('<div>')
                .addClass(`reservas-notificacion reservas-notificacion--${type}`)
                .text(message)
                .css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    padding: '15px 25px',
                    borderRadius: '8px',
                    background: type === 'success' ? '#2ecc71' : '#e74c3c',
                    color: '#fff',
                    zIndex: 9999,
                    boxShadow: '0 4px 15px rgba(0,0,0,0.2)'
                });

            $('body').append($notif);

            setTimeout(() => {
                $notif.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorReservas.init();
    });

    // Exponer globalmente
    window.FlavorReservas = FlavorReservas;

})(jQuery);
