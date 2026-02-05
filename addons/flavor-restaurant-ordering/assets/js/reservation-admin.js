/**
 * JavaScript para la administración de reservas
 */

(function($) {
    'use strict';

    const ReservationAdmin = {
        refreshInterval: null,

        init() {
            this.loadReservations();
            this.bindEvents();

            // Auto-refresh cada 60 segundos
            this.refreshInterval = setInterval(() => {
                this.loadReservations(true);
            }, 60000);
        },

        bindEvents() {
            // Filtros
            $('#filter-status, #filter-date, #filter-table, #filter-upcoming').on('change', () => {
                this.loadReservations();
            });

            // Refrescar
            $('#refresh-reservations').on('click', () => {
                this.loadReservations();
            });

            // Click en reserva
            $(document).on('click', '.reservation-card', (e) => {
                const reservationId = $(e.currentTarget).data('reservation-id');
                this.showReservationDetails(reservationId);
            });

            // Cambiar estado
            $(document).on('click', '.change-reservation-status', (e) => {
                e.stopPropagation();
                const reservationId = $(e.currentTarget).data('reservation-id');
                this.changeReservationStatus(reservationId);
            });

            // Cancelar reserva
            $(document).on('click', '.cancel-reservation', (e) => {
                e.stopPropagation();
                const reservationId = $(e.currentTarget).data('reservation-id');
                this.cancelReservation(reservationId);
            });

            // Cerrar modal
            $('.flavor-modal-close').on('click', () => {
                $('.flavor-modal').fadeOut(300);
            });

            // Click fuera del modal
            $('.flavor-modal').on('click', (e) => {
                if ($(e.target).hasClass('flavor-modal')) {
                    $('.flavor-modal').fadeOut(300);
                }
            });
        },

        loadReservations(silent = false) {
            const $list = $('#reservations-list');
            const $noReservations = $('#no-reservations');

            if (!silent) {
                $list.html('<div class="loading-indicator"><span class="spinner is-active"></span> Cargando reservas...</div>');
            }

            const data = {
                action: 'get_restaurant_reservations',
                nonce: flavorReservationAdmin.nonce,
                status: $('#filter-status').val(),
                table_id: $('#filter-table').val(),
                date: $('#filter-date').val(),
                upcoming: $('#filter-upcoming').is(':checked')
            };

            $.post(flavorReservationAdmin.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.renderReservations(response.data.reservations);
                        this.updateStats(response.data.reservations);
                    }
                })
                .fail(() => {
                    $list.html('<div class="notice notice-error"><p>Error al cargar reservas</p></div>');
                });
        },

        renderReservations(reservations) {
            const $list = $('#reservations-list');
            const $noReservations = $('#no-reservations');

            if (!reservations || reservations.length === 0) {
                $list.empty();
                $noReservations.fadeIn(300);
                return;
            }

            $noReservations.hide();

            let html = '';
            reservations.forEach(reservation => {
                html += this.getReservationCardHTML(reservation);
            });

            $list.html(html);
        },

        getReservationCardHTML(reservation) {
            const datetime = new Date(reservation.reservation_datetime);
            const dateStr = datetime.toLocaleDateString('es-ES');
            const timeStr = datetime.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});

            return `
                <div class="reservation-card" data-reservation-id="${reservation.id}">
                    <div class="reservation-header">
                        <span class="reservation-code">#${reservation.reservation_code}</span>
                        <span class="reservation-status ${reservation.status}">${reservation.status_label}</span>
                    </div>

                    <div class="reservation-details">
                        <div class="detail-row">
                            <span class="dashicons dashicons-admin-users"></span>
                            <span>${reservation.customer.name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span>${dateStr} ${timeStr}</span>
                        </div>
                        <div class="detail-row">
                            <span class="dashicons dashicons-groups"></span>
                            <span>${reservation.guests_count} personas</span>
                        </div>
                        ${reservation.table ? `
                        <div class="detail-row">
                            <span class="dashicons dashicons-admin-home"></span>
                            <span>${reservation.table.table_name}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="reservation-actions">
                        ${reservation.status === 'pending' ? `
                            <button class="button button-small change-reservation-status"
                                    data-reservation-id="${reservation.id}">
                                <span class="dashicons dashicons-yes"></span> Confirmar
                            </button>
                        ` : ''}
                        ${['pending', 'confirmed'].includes(reservation.status) ? `
                            <button class="button button-small cancel-reservation"
                                    data-reservation-id="${reservation.id}">
                                <span class="dashicons dashicons-no"></span> Cancelar
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        },

        updateStats(reservations) {
            let pending = 0;
            let confirmed = 0;
            let today = 0;
            let totalGuests = 0;
            const todayStr = new Date().toISOString().split('T')[0];

            reservations.forEach(reservation => {
                if (reservation.status === 'pending') pending++;
                if (reservation.status === 'confirmed') confirmed++;
                if (reservation.reservation_date === todayStr) today++;
                totalGuests += reservation.guests_count;
            });

            $('#stat-pending').text(pending);
            $('#stat-confirmed').text(confirmed);
            $('#stat-today').text(today);
            $('#stat-guests').text(totalGuests);
        },

        showReservationDetails(reservationId) {
            const $modal = $('#reservation-details-modal');
            const $content = $('#reservation-details-content');

            $modal.fadeIn(300);
            $content.html('<div class="loading-indicator"><span class="spinner is-active"></span> Cargando detalles...</div>');

            $.post(flavorReservationAdmin.ajax_url, {
                action: 'get_reservation_details',
                nonce: flavorReservationAdmin.nonce,
                reservation_id: reservationId
            })
                .done((response) => {
                    if (response.success) {
                        this.renderReservationDetails(response.data.reservation);
                    }
                })
                .fail(() => {
                    $content.html('<p>Error al cargar detalles de la reserva</p>');
                });
        },

        renderReservationDetails(reservation) {
            const $content = $('#reservation-details-content');
            const $title = $('#modal-reservation-title');

            $title.text(`Reserva #${reservation.reservation_code}`);

            const datetime = new Date(reservation.reservation_datetime);
            const dateStr = datetime.toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const timeStr = datetime.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});

            let html = `
                <div class="reservation-detail-section">
                    <h3>Estado: <span class="reservation-status ${reservation.status}">${reservation.status_label}</span></h3>

                    <div class="reservation-actions" style="margin: 15px 0;">
                        ${reservation.status === 'pending' ? `
                            <button class="button button-primary change-reservation-status"
                                    data-reservation-id="${reservation.id}">
                                Confirmar Reserva
                            </button>
                        ` : ''}
                        ${['pending', 'confirmed'].includes(reservation.status) ? `
                            <button class="button button-secondary cancel-reservation"
                                    data-reservation-id="${reservation.id}">
                                Cancelar Reserva
                            </button>
                        ` : ''}
                    </div>
                </div>

                <div class="reservation-detail-section">
                    <h3>Información de la Reserva</h3>
                    <table class="widefat">
                        <tr>
                            <th>Código:</th>
                            <td><code>${reservation.reservation_code}</code></td>
                        </tr>
                        <tr>
                            <th>Fecha y Hora:</th>
                            <td>${dateStr} a las ${timeStr}</td>
                        </tr>
                        <tr>
                            <th>Duración:</th>
                            <td>${reservation.duration} minutos</td>
                        </tr>
                        <tr>
                            <th>Número de Personas:</th>
                            <td>${reservation.guests_count}</td>
                        </tr>
                        <tr>
                            <th>Mesa:</th>
                            <td>${reservation.table ? reservation.table.table_name : 'Por asignar'}</td>
                        </tr>
                    </table>
                </div>

                <div class="reservation-detail-section">
                    <h3>Información del Cliente</h3>
                    <table class="widefat">
                        <tr>
                            <th>Nombre:</th>
                            <td>${reservation.customer.name}</td>
                        </tr>
                        <tr>
                            <th>Teléfono:</th>
                            <td><a href="tel:${reservation.customer.phone}">${reservation.customer.phone}</a></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>${reservation.customer.email ? `<a href="mailto:${reservation.customer.email}">${reservation.customer.email}</a>` : '-'}</td>
                        </tr>
                    </table>
                </div>

                ${reservation.special_requests ? `
                <div class="reservation-detail-section">
                    <h3>Solicitudes Especiales</h3>
                    <p>${reservation.special_requests}</p>
                </div>
                ` : ''}

                ${reservation.notes ? `
                <div class="reservation-detail-section">
                    <h3>Notas Internas</h3>
                    <p>${reservation.notes}</p>
                </div>
                ` : ''}

                <div class="reservation-detail-section">
                    <h3>Fechas</h3>
                    <table class="widefat">
                        <tr>
                            <th>Creada:</th>
                            <td>${new Date(reservation.created_at).toLocaleString('es-ES')}</td>
                        </tr>
                        ${reservation.confirmed_at ? `
                        <tr>
                            <th>Confirmada:</th>
                            <td>${new Date(reservation.confirmed_at).toLocaleString('es-ES')}</td>
                        </tr>
                        ` : ''}
                        ${reservation.cancelled_at ? `
                        <tr>
                            <th>Cancelada:</th>
                            <td>${new Date(reservation.cancelled_at).toLocaleString('es-ES')}</td>
                        </tr>
                        ` : ''}
                    </table>
                </div>
            `;

            $content.html(html);
        },

        changeReservationStatus(reservationId) {
            const newStatus = 'confirmed';

            if (!confirm('¿Confirmar esta reserva?')) {
                return;
            }

            $.post(flavorReservationAdmin.ajax_url, {
                action: 'update_reservation_status',
                nonce: flavorReservationAdmin.nonce,
                reservation_id: reservationId,
                status: newStatus,
                notes: ''
            })
                .done((response) => {
                    if (response.success) {
                        alert('Reserva confirmada correctamente');
                        this.loadReservations();
                        $('.flavor-modal').fadeOut(300);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Error al confirmar la reserva');
                });
        },

        cancelReservation(reservationId) {
            const reason = prompt('Motivo de cancelación (opcional):');

            if (reason === null) {
                return; // Usuario canceló
            }

            $.post(flavorReservationAdmin.ajax_url, {
                action: 'cancel_reservation',
                nonce: flavorReservationAdmin.nonce,
                reservation_id: reservationId,
                reason: reason
            })
                .done((response) => {
                    if (response.success) {
                        alert('Reserva cancelada correctamente');
                        this.loadReservations();
                        $('.flavor-modal').fadeOut(300);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                })
                .fail(() => {
                    alert('Error al cancelar la reserva');
                });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        if ($('.flavor-restaurant-reservations').length) {
            ReservationAdmin.init();
        }
    });

    // Limpiar interval al salir
    $(window).on('beforeunload', () => {
        if (ReservationAdmin.refreshInterval) {
            clearInterval(ReservationAdmin.refreshInterval);
        }
    });

})(jQuery);
