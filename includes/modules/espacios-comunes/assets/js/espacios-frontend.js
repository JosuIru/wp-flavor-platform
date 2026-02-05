/**
 * Espacios Comunes Frontend JavaScript
 * Flavor Chat IA - Módulo de Reservas de Espacios
 */

(function($) {
    'use strict';

    // Variables globales
    const FlavorEspacios = {
        ajaxurl: typeof flavorEspaciosData !== 'undefined' ? flavorEspaciosData.ajaxurl : '/wp-admin/admin-ajax.php',
        nonce: typeof flavorEspaciosData !== 'undefined' ? flavorEspaciosData.nonce : '',
        strings: typeof flavorEspaciosData !== 'undefined' ? flavorEspaciosData.strings : {},
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedDate: null,
        selectedHora: null,
    };

    /**
     * Inicialización
     */
    $(document).ready(function() {
        FlavorEspacios.init();
    });

    FlavorEspacios.init = function() {
        this.bindEvents();
        this.initTabs();
        this.initCalendario();
        this.initGaleria();
    };

    /**
     * Bindear eventos
     */
    FlavorEspacios.bindEvents = function() {
        const self = this;

        // Crear reserva
        $(document).on('click', '.btn-reservar-espacio', function(e) {
            e.preventDefault();
            const espacioId = $(this).data('espacio-id');
            self.abrirModalReserva(espacioId);
        });

        // Enviar formulario de reserva
        $(document).on('submit', '.reserva-form', function(e) {
            e.preventDefault();
            self.crearReserva($(this));
        });

        // Cancelar reserva
        $(document).on('click', '.btn-cancelar-reserva', function(e) {
            e.preventDefault();
            const reservaId = $(this).data('reserva-id');
            if (confirm(self.strings.confirmar_cancelar || '¿Estás seguro de que deseas cancelar esta reserva?')) {
                self.cancelarReserva(reservaId, $(this));
            }
        });

        // Ver disponibilidad
        $(document).on('click', '.btn-ver-disponibilidad', function(e) {
            e.preventDefault();
            const espacioId = $(this).data('espacio-id');
            self.cargarDisponibilidad(espacioId);
        });

        // Seleccionar fecha en el calendario
        $(document).on('click', '.calendario-dia:not(.otro-mes)', function() {
            const fecha = $(this).data('fecha');
            if (fecha) {
                self.seleccionarFecha(fecha, $(this));
            }
        });

        // Seleccionar horario
        $(document).on('click', '.horario-slot:not(.ocupado)', function() {
            $('.horario-slot').removeClass('selected');
            $(this).addClass('selected');
            self.selectedHora = $(this).data('hora');
        });

        // Reportar incidencia
        $(document).on('click', '.btn-reportar-incidencia', function(e) {
            e.preventDefault();
            const espacioId = $(this).data('espacio-id');
            self.abrirModalIncidencia(espacioId);
        });

        // Enviar incidencia
        $(document).on('submit', '.incidencia-form', function(e) {
            e.preventDefault();
            self.enviarIncidencia($(this));
        });

        // Seleccionar tipo de incidencia
        $(document).on('click', '.incidencia-tipo', function() {
            $('.incidencia-tipo').removeClass('selected');
            $(this).addClass('selected');
        });

        // Valorar espacio
        $(document).on('click', '.valoracion-estrellas .star', function() {
            const valor = $(this).data('valor');
            const container = $(this).closest('.valoracion-estrellas');
            container.data('valoracion', valor);
            container.find('.star').each(function() {
                const starValor = $(this).data('valor');
                $(this).removeClass('dashicons-star-filled dashicons-star-empty')
                    .addClass(starValor <= valor ? 'dashicons-star-filled' : 'dashicons-star-empty');
            });
        });

        // Enviar valoración
        $(document).on('submit', '.form-valoracion', function(e) {
            e.preventDefault();
            self.enviarValoracion($(this));
        });

        // Cerrar modal
        $(document).on('click', '.espacios-modal-close, .espacios-modal-overlay', function(e) {
            if (e.target === this) {
                self.cerrarModal();
            }
        });

        // Navegación del calendario
        $(document).on('click', '.calendario-prev', function() {
            self.navegarCalendario(-1);
        });

        $(document).on('click', '.calendario-next', function() {
            self.navegarCalendario(1);
        });

        // Filtros
        $(document).on('change', '.espacios-filtro-grupo select, .espacios-filtro-grupo input', function() {
            self.aplicarFiltros();
        });

        // ESC para cerrar modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                self.cerrarModal();
            }
        });
    };

    /**
     * Tabs de navegación
     */
    FlavorEspacios.initTabs = function() {
        $(document).on('click', '.mis-reservas-tab', function() {
            const tabId = $(this).data('tab');

            $('.mis-reservas-tab').removeClass('active');
            $(this).addClass('active');

            $('.mis-reservas-panel').hide();
            $('#' + tabId).fadeIn(200);
        });
    };

    /**
     * Inicializar calendario
     */
    FlavorEspacios.initCalendario = function() {
        if ($('.calendario-wrapper').length) {
            this.renderCalendario();
        }
    };

    /**
     * Renderizar calendario
     */
    FlavorEspacios.renderCalendario = function() {
        const self = this;
        const espacioId = $('.calendario-wrapper').data('espacio-id');

        if (!espacioId) return;

        const container = $('.calendario-grid');
        container.html('<div class="espacios-loading"><div class="espacios-spinner"></div></div>');

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_disponibilidad',
                nonce: self.nonce,
                espacio_id: espacioId,
                mes: self.currentMonth + 1,
                ano: self.currentYear
            },
            success: function(response) {
                if (response.success) {
                    self.construirCalendario(response.data);
                } else {
                    self.showToast(response.data.message || 'Error al cargar calendario', 'error');
                }
            },
            error: function() {
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Construir HTML del calendario
     */
    FlavorEspacios.construirCalendario = function(data) {
        const self = this;
        const container = $('.calendario-grid');
        const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // Actualizar título del mes
        $('.calendario-mes-actual').text(meses[self.currentMonth] + ' ' + self.currentYear);

        let html = '';

        // Headers de días
        diasSemana.forEach(function(dia) {
            html += '<div class="calendario-dia-header">' + dia + '</div>';
        });

        // Calcular días del mes
        const primerDia = new Date(self.currentYear, self.currentMonth, 1);
        const ultimoDia = new Date(self.currentYear, self.currentMonth + 1, 0);
        const diasEnMes = ultimoDia.getDate();

        // Ajustar para que la semana empiece en lunes
        let diaInicio = primerDia.getDay() - 1;
        if (diaInicio < 0) diaInicio = 6;

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        // Días del mes anterior
        const mesAnterior = new Date(self.currentYear, self.currentMonth, 0);
        const diasMesAnterior = mesAnterior.getDate();

        for (let i = diaInicio - 1; i >= 0; i--) {
            const dia = diasMesAnterior - i;
            html += '<div class="calendario-dia otro-mes"><span class="calendario-dia-numero">' + dia + '</span></div>';
        }

        // Días del mes actual
        for (let dia = 1; dia <= diasEnMes; dia++) {
            const fecha = self.currentYear + '-' + String(self.currentMonth + 1).padStart(2, '0') + '-' + String(dia).padStart(2, '0');
            const fechaObj = new Date(self.currentYear, self.currentMonth, dia);

            let clases = 'calendario-dia';
            if (fechaObj.getTime() === hoy.getTime()) {
                clases += ' hoy';
            }
            if (fechaObj < hoy) {
                clases += ' pasado';
            }

            html += '<div class="' + clases + '" data-fecha="' + fecha + '">';
            html += '<span class="calendario-dia-numero">' + dia + '</span>';

            // Mostrar reservas del día
            if (data.reservas && data.reservas[fecha]) {
                html += '<div class="calendario-dia-reservas">';
                data.reservas[fecha].forEach(function(reserva) {
                    let claseReserva = 'calendario-reserva ' + reserva.estado;
                    if (reserva.propia) {
                        claseReserva += ' propia';
                    }
                    html += '<div class="' + claseReserva + '" title="' + reserva.hora_inicio + ' - ' + reserva.hora_fin + '">';
                    html += reserva.hora_inicio;
                    html += '</div>';
                });
                html += '</div>';
            }

            html += '</div>';
        }

        // Días del mes siguiente
        const diasRestantes = 42 - (diaInicio + diasEnMes);
        for (let dia = 1; dia <= diasRestantes; dia++) {
            html += '<div class="calendario-dia otro-mes"><span class="calendario-dia-numero">' + dia + '</span></div>';
        }

        container.html(html);
    };

    /**
     * Navegar calendario
     */
    FlavorEspacios.navegarCalendario = function(direccion) {
        this.currentMonth += direccion;

        if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
        } else if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
        }

        this.renderCalendario();
    };

    /**
     * Seleccionar fecha
     */
    FlavorEspacios.seleccionarFecha = function(fecha, element) {
        const self = this;

        if (element.hasClass('pasado')) {
            self.showToast('No puedes reservar fechas pasadas', 'warning');
            return;
        }

        $('.calendario-dia').removeClass('selected');
        element.addClass('selected');
        self.selectedDate = fecha;

        // Cargar horarios disponibles
        const espacioId = $('.calendario-wrapper').data('espacio-id');
        self.cargarHorariosDisponibles(espacioId, fecha);
    };

    /**
     * Cargar horarios disponibles
     */
    FlavorEspacios.cargarHorariosDisponibles = function(espacioId, fecha) {
        const self = this;
        const container = $('.horarios-disponibles');

        if (!container.length) return;

        container.html('<div class="espacios-loading"><div class="espacios-spinner"></div></div>');

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_horarios',
                nonce: self.nonce,
                espacio_id: espacioId,
                fecha: fecha
            },
            success: function(response) {
                if (response.success) {
                    self.renderHorarios(response.data.horarios);
                } else {
                    container.html('<p style="color: #6b7280;">No hay horarios disponibles para esta fecha.</p>');
                }
            },
            error: function() {
                self.showToast('Error al cargar horarios', 'error');
            }
        });
    };

    /**
     * Renderizar horarios
     */
    FlavorEspacios.renderHorarios = function(horarios) {
        const container = $('.horarios-disponibles');
        let html = '';

        horarios.forEach(function(horario) {
            const clase = horario.disponible ? '' : 'ocupado';
            html += '<button type="button" class="horario-slot ' + clase + '" data-hora="' + horario.hora + '">';
            html += horario.hora;
            html += '</button>';
        });

        container.html(html);
    };

    /**
     * Abrir modal de reserva
     */
    FlavorEspacios.abrirModalReserva = function(espacioId) {
        const self = this;

        const modalHtml = `
            <div class="espacios-modal-overlay active">
                <div class="espacios-modal">
                    <div class="espacios-modal-header">
                        <h3>Nueva Reserva</h3>
                        <button class="espacios-modal-close">&times;</button>
                    </div>
                    <div class="espacios-modal-body">
                        <form class="reserva-form" data-espacio-id="${espacioId}">
                            <div class="form-grupo">
                                <label>Fecha *</label>
                                <input type="date" name="fecha" required min="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="form-grupo-inline">
                                <div class="form-grupo">
                                    <label>Hora inicio *</label>
                                    <input type="time" name="hora_inicio" required>
                                </div>
                                <div class="form-grupo">
                                    <label>Hora fin *</label>
                                    <input type="time" name="hora_fin" required>
                                </div>
                            </div>
                            <div class="form-grupo">
                                <label>Número de personas</label>
                                <input type="number" name="num_personas" min="1" value="1">
                            </div>
                            <div class="form-grupo">
                                <label>Motivo de la reserva</label>
                                <textarea name="motivo" rows="3" placeholder="Describe brevemente para qué necesitas el espacio..."></textarea>
                            </div>
                            <div class="espacios-modal-footer" style="border: none; padding: 1rem 0 0; background: transparent;">
                                <button type="button" class="btn btn-outline espacios-modal-close">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Solicitar reserva</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    };

    /**
     * Crear reserva
     */
    FlavorEspacios.crearReserva = function(form) {
        const self = this;
        const submitBtn = form.find('button[type="submit"]');
        const espacioId = form.data('espacio-id');

        submitBtn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_crear_reserva',
                nonce: self.nonce,
                espacio_id: espacioId,
                fecha: form.find('[name="fecha"]').val(),
                hora_inicio: form.find('[name="hora_inicio"]').val(),
                hora_fin: form.find('[name="hora_fin"]').val(),
                num_personas: form.find('[name="num_personas"]').val(),
                motivo: form.find('[name="motivo"]').val()
            },
            success: function(response) {
                submitBtn.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.message || 'Reserva solicitada correctamente', 'success');
                    self.cerrarModal();

                    // Recargar página para mostrar la nueva reserva
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    self.showToast(response.data.message || 'Error al crear la reserva', 'error');
                }
            },
            error: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Cancelar reserva
     */
    FlavorEspacios.cancelarReserva = function(reservaId, button) {
        const self = this;

        button.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_cancelar_reserva',
                nonce: self.nonce,
                reserva_id: reservaId
            },
            success: function(response) {
                button.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.message || 'Reserva cancelada', 'success');
                    button.closest('.reserva-card').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    self.showToast(response.data.message || 'Error al cancelar', 'error');
                }
            },
            error: function() {
                button.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Abrir modal de incidencia
     */
    FlavorEspacios.abrirModalIncidencia = function(espacioId) {
        const self = this;

        const tipos = [
            { id: 'limpieza', label: 'Limpieza' },
            { id: 'mantenimiento', label: 'Mantenimiento' },
            { id: 'equipamiento', label: 'Equipamiento' },
            { id: 'seguridad', label: 'Seguridad' },
            { id: 'otro', label: 'Otro' }
        ];

        let tiposHtml = tipos.map(t =>
            `<button type="button" class="incidencia-tipo" data-tipo="${t.id}">${t.label}</button>`
        ).join('');

        const modalHtml = `
            <div class="espacios-modal-overlay active">
                <div class="espacios-modal">
                    <div class="espacios-modal-header">
                        <h3>Reportar Incidencia</h3>
                        <button class="espacios-modal-close">&times;</button>
                    </div>
                    <div class="espacios-modal-body">
                        <form class="incidencia-form" data-espacio-id="${espacioId}">
                            <div class="form-grupo">
                                <label>Tipo de incidencia *</label>
                                <div class="incidencia-tipos">${tiposHtml}</div>
                            </div>
                            <div class="form-grupo">
                                <label>Descripción *</label>
                                <textarea name="descripcion" rows="4" required placeholder="Describe la incidencia con el mayor detalle posible..."></textarea>
                            </div>
                            <div class="form-grupo">
                                <label>Urgencia</label>
                                <select name="urgencia">
                                    <option value="baja">Baja - Puede esperar</option>
                                    <option value="media" selected>Media - Resolver pronto</option>
                                    <option value="alta">Alta - Urgente</option>
                                </select>
                            </div>
                            <div class="espacios-modal-footer" style="border: none; padding: 1rem 0 0; background: transparent;">
                                <button type="button" class="btn btn-outline espacios-modal-close">Cancelar</button>
                                <button type="submit" class="btn btn-danger">Enviar incidencia</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    };

    /**
     * Enviar incidencia
     */
    FlavorEspacios.enviarIncidencia = function(form) {
        const self = this;
        const submitBtn = form.find('button[type="submit"]');
        const espacioId = form.data('espacio-id');
        const tipo = form.find('.incidencia-tipo.selected').data('tipo');

        if (!tipo) {
            self.showToast('Selecciona un tipo de incidencia', 'warning');
            return;
        }

        submitBtn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_reportar_incidencia',
                nonce: self.nonce,
                espacio_id: espacioId,
                tipo: tipo,
                descripcion: form.find('[name="descripcion"]').val(),
                urgencia: form.find('[name="urgencia"]').val()
            },
            success: function(response) {
                submitBtn.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.message || 'Incidencia reportada', 'success');
                    self.cerrarModal();
                } else {
                    self.showToast(response.data.message || 'Error al enviar', 'error');
                }
            },
            error: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Enviar valoración
     */
    FlavorEspacios.enviarValoracion = function(form) {
        const self = this;
        const submitBtn = form.find('button[type="submit"]');
        const espacioId = form.data('espacio-id');
        const valoracion = form.find('.valoracion-estrellas').data('valoracion');

        if (!valoracion || valoracion < 1) {
            self.showToast('Selecciona una valoración', 'warning');
            return;
        }

        submitBtn.addClass('loading').prop('disabled', true);

        $.ajax({
            url: self.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_espacios_valorar',
                nonce: self.nonce,
                espacio_id: espacioId,
                valoracion: valoracion,
                comentario: form.find('[name="comentario"]').val()
            },
            success: function(response) {
                submitBtn.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    self.showToast(response.data.message || 'Valoración enviada', 'success');
                    form.replaceWith('<p style="color: #10b981;">¡Gracias por tu valoración!</p>');
                } else {
                    self.showToast(response.data.message || 'Error al enviar', 'error');
                }
            },
            error: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    /**
     * Inicializar galería
     */
    FlavorEspacios.initGaleria = function() {
        $(document).on('click', '.espacio-detalle-miniatura', function() {
            const src = $(this).find('img').attr('src');
            $('.espacio-detalle-miniatura').removeClass('active');
            $(this).addClass('active');
            $('.espacio-detalle-imagen-principal img').attr('src', src);
        });
    };

    /**
     * Aplicar filtros
     */
    FlavorEspacios.aplicarFiltros = function() {
        const self = this;
        const tipo = $('.espacios-filtro-grupo select[name="tipo"]').val();
        const capacidad = $('.espacios-filtro-grupo select[name="capacidad"]').val();
        const buscar = $('.espacios-buscar input').val();

        // Para una implementación completa, hacer una petición AJAX
        // Por ahora, filtrar en cliente
        $('.espacio-card').each(function() {
            const card = $(this);
            let visible = true;

            // Filtrar por búsqueda
            if (buscar) {
                const titulo = card.find('.espacio-card-titulo').text().toLowerCase();
                const ubicacion = card.find('.espacio-card-ubicacion').text().toLowerCase();
                if (!titulo.includes(buscar.toLowerCase()) && !ubicacion.includes(buscar.toLowerCase())) {
                    visible = false;
                }
            }

            card.toggle(visible);
        });
    };

    /**
     * Cerrar modal
     */
    FlavorEspacios.cerrarModal = function() {
        $('.espacios-modal-overlay').removeClass('active');
        setTimeout(function() {
            $('.espacios-modal-overlay').remove();
        }, 300);
    };

    /**
     * Mostrar toast notification
     */
    FlavorEspacios.showToast = function(message, type) {
        type = type || 'info';

        // Crear contenedor si no existe
        if (!$('.espacios-toast-container').length) {
            $('body').append('<div class="espacios-toast-container"></div>');
        }

        const icons = {
            success: 'yes-alt',
            error: 'dismiss',
            warning: 'warning',
            info: 'info'
        };

        const toast = $(`
            <div class="espacios-toast ${type}">
                <span class="dashicons dashicons-${icons[type]} espacios-toast-icon"></span>
                <span class="espacios-toast-message">${message}</span>
                <button class="espacios-toast-close">&times;</button>
            </div>
        `);

        $('.espacios-toast-container').append(toast);

        // Auto-cerrar después de 5 segundos
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Cerrar al hacer clic
        toast.find('.espacios-toast-close').on('click', function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        });
    };

    // Exponer globalmente
    window.FlavorEspacios = FlavorEspacios;

})(jQuery);
