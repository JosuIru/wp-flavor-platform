/**
 * Biblioteca - Frontend JavaScript
 */

(function($) {
    'use strict';

    const Biblioteca = {
        init: function() {
            this.bindEvents();
            this.initCatalogo();
        },

        bindEvents: function() {
            // Búsqueda
            $(document).on('submit', '.biblioteca-buscador', this.handleBusqueda.bind(this));
            $(document).on('change', '.biblioteca-filtro-grupo select', this.handleFiltro.bind(this));

            // Acciones de libro
            $(document).on('click', '.btn-solicitar-prestamo', this.handleSolicitarPrestamo.bind(this));
            $(document).on('click', '.btn-reservar', this.handleReservar.bind(this));
            $(document).on('click', '.btn-devolver', this.handleDevolver.bind(this));
            $(document).on('click', '.btn-renovar', this.handleRenovar.bind(this));
            $(document).on('click', '.btn-cancelar-reserva', this.handleCancelarReserva.bind(this));

            // Gestión propietario
            $(document).on('click', '.btn-aprobar-prestamo', this.handleAprobar.bind(this));
            $(document).on('click', '.btn-rechazar-prestamo', this.handleRechazar.bind(this));
            $(document).on('click', '.btn-eliminar-libro', this.handleEliminarLibro.bind(this));
            $(document).on('click', '.btn-editar-libro', this.handleEditarLibro.bind(this));

            // Valoración
            $(document).on('click', '.libro-valoracion-estrellas .star', this.handleValoracion.bind(this));
            $(document).on('submit', '.form-resena', this.handleEnviarResena.bind(this));

            // Agregar libro
            $(document).on('click', '.btn-buscar-isbn', this.handleBuscarISBN.bind(this));
            $(document).on('submit', '.agregar-libro-form', this.handleAgregarLibro.bind(this));
            $(document).on('click', '.portada-preview', this.handleSeleccionarPortada.bind(this));

            // Tabs mis préstamos
            $(document).on('click', '.mis-prestamos-tab', this.handleTab.bind(this));

            // Modal
            $(document).on('click', '.biblioteca-modal-close', this.cerrarModal.bind(this));
            $(document).on('click', '.biblioteca-modal', function(e) {
                if (e.target === this) {
                    $(this).removeClass('open');
                }
            });

            // Click en tarjeta de libro
            $(document).on('click', '.libro-card', this.handleClickLibro.bind(this));
        },

        initCatalogo: function() {
            if ($('.biblioteca-grid').length && typeof bibliotecaData !== 'undefined') {
                this.cargarLibros();
            }
        },

        cargarLibros: function(params = {}) {
            const self = this;
            const $grid = $('.biblioteca-grid');

            $grid.html('<div class="biblioteca-loading"><div class="biblioteca-spinner"></div><span>Cargando libros...</span></div>');

            $.ajax({
                url: bibliotecaData.ajax_url.replace('admin-ajax.php', 'wp-json/flavor/v1/biblioteca/libros'),
                type: 'GET',
                data: params,
                success: function(response) {
                    if (response.success && response.libros.length > 0) {
                        self.renderLibros(response.libros);
                    } else {
                        $grid.html('<div class="biblioteca-empty"><span class="dashicons dashicons-book"></span><h3>No hay libros</h3><p>No se encontraron libros con esos criterios.</p></div>');
                    }
                },
                error: function() {
                    $grid.html('<div class="biblioteca-empty"><span class="dashicons dashicons-warning"></span><h3>Error</h3><p>No se pudieron cargar los libros.</p></div>');
                }
            });
        },

        renderLibros: function(libros) {
            const $grid = $('.biblioteca-grid');
            let html = '';

            libros.forEach(function(libro) {
                const badgeClass = libro.disponibilidad === 'disponible' ? 'disponible' : (libro.disponibilidad === 'prestado' ? 'prestado' : 'reservado');
                const badgeText = libro.disponibilidad === 'disponible' ? 'Disponible' : (libro.disponibilidad === 'prestado' ? 'Prestado' : 'Reservado');

                html += `
                    <div class="libro-card" data-id="${libro.id}">
                        <div class="libro-card-portada">
                            <img src="${libro.portada || ''}" alt="${libro.titulo}" onerror="this.style.display='none'">
                            <span class="libro-card-badge ${badgeClass}">${badgeText}</span>
                        </div>
                        <div class="libro-card-info">
                            <h4 class="libro-card-titulo">${libro.titulo}</h4>
                            <p class="libro-card-autor">${libro.autor}</p>
                            <div class="libro-card-meta">
                                ${libro.genero ? `<span class="libro-card-genero">${libro.genero}</span>` : ''}
                                ${libro.valoracion > 0 ? `
                                    <span class="libro-card-valoracion">
                                        <span class="dashicons dashicons-star-filled"></span>
                                        ${libro.valoracion.toFixed(1)}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            $grid.html(html);
        },

        handleBusqueda: function(e) {
            e.preventDefault();
            const busqueda = $(e.currentTarget).find('input').val();
            this.cargarLibros({ busqueda: busqueda });
        },

        handleFiltro: function(e) {
            const genero = $('#filtro-genero').val();
            const disponibilidad = $('#filtro-disponibilidad').val();

            this.cargarLibros({
                genero: genero,
                disponibilidad: disponibilidad
            });
        },

        handleClickLibro: function(e) {
            const libroId = $(e.currentTarget).data('id');
            if (libroId) {
                window.location.href = window.location.pathname + '?libro_id=' + libroId;
            }
        },

        handleSolicitarPrestamo: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const libroId = $btn.data('libro-id');

            if (!bibliotecaData.usuario_logueado) {
                this.showToast('error', 'Debes iniciar sesión para solicitar préstamos');
                return;
            }

            $btn.prop('disabled', true).text('Solicitando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_solicitar_prestamo',
                    nonce: bibliotecaData.nonce,
                    libro_id: libroId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.text('Solicitud enviada').addClass('btn-outline').removeClass('btn-primary');
                    } else {
                        this.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Solicitar préstamo');
                    }
                },
                error: () => {
                    this.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Solicitar préstamo');
                }
            });
        },

        handleReservar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const libroId = $btn.data('libro-id');

            if (!bibliotecaData.usuario_logueado) {
                this.showToast('error', 'Debes iniciar sesión');
                return;
            }

            $btn.prop('disabled', true).text('Reservando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_reservar_libro',
                    nonce: bibliotecaData.nonce,
                    libro_id: libroId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.text('Reservado').addClass('btn-outline').removeClass('btn-warning');
                    } else {
                        this.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Reservar');
                    }
                },
                error: () => {
                    this.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Reservar');
                }
            });
        },

        handleDevolver: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const prestamoId = $btn.data('prestamo-id');

            if (!confirm('¿Confirmas que has devuelto este libro?')) return;

            $btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_devolver_libro',
                    nonce: bibliotecaData.nonce,
                    prestamo_id: prestamoId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.closest('.prestamo-card').fadeOut();
                    } else {
                        this.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Marcar devuelto');
                    }
                },
                error: () => {
                    this.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Marcar devuelto');
                }
            });
        },

        handleRenovar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const prestamoId = $btn.data('prestamo-id');

            $btn.prop('disabled', true).text('Renovando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_renovar_prestamo',
                    nonce: bibliotecaData.nonce,
                    prestamo_id: prestamoId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje + ' Nueva fecha: ' + response.nueva_fecha_devolucion);
                        location.reload();
                    } else {
                        this.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Renovar');
                    }
                },
                error: () => {
                    this.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Renovar');
                }
            });
        },

        handleCancelarReserva: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const reservaId = $btn.data('reserva-id');

            if (!confirm('¿Cancelar esta reserva?')) return;

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_cancelar_reserva',
                    nonce: bibliotecaData.nonce,
                    reserva_id: reservaId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.closest('.prestamo-card').fadeOut();
                    } else {
                        this.showToast('error', response.error);
                    }
                }
            });
        },

        handleAprobar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const prestamoId = $btn.data('prestamo-id');

            // Modal para punto de entrega
            const html = `
                <div class="biblioteca-modal open" id="modal-aprobar">
                    <div class="biblioteca-modal-content">
                        <div class="biblioteca-modal-header">
                            <h3>Aprobar préstamo</h3>
                            <button class="biblioteca-modal-close">&times;</button>
                        </div>
                        <div class="biblioteca-modal-body">
                            <div class="form-grupo">
                                <label>Punto de entrega (opcional)</label>
                                <input type="text" id="punto-entrega" placeholder="Ej: Portería, cafetería...">
                            </div>
                        </div>
                        <div class="biblioteca-modal-footer">
                            <button class="btn btn-outline biblioteca-modal-close">Cancelar</button>
                            <button class="btn btn-success" id="confirmar-aprobar" data-prestamo-id="${prestamoId}">Aprobar</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(html);

            $('#confirmar-aprobar').on('click', () => {
                const puntoEntrega = $('#punto-entrega').val();
                this.aprobarPrestamo(prestamoId, puntoEntrega, $btn);
            });
        },

        aprobarPrestamo: function(prestamoId, puntoEntrega, $btn) {
            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_aprobar_prestamo',
                    nonce: bibliotecaData.nonce,
                    prestamo_id: prestamoId,
                    punto_entrega: puntoEntrega
                },
                success: (response) => {
                    this.cerrarModal();
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.closest('.solicitud-card').fadeOut();
                    } else {
                        this.showToast('error', response.error);
                    }
                }
            });
        },

        handleRechazar: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const prestamoId = $btn.data('prestamo-id');

            const motivo = prompt('Motivo del rechazo (opcional):');
            if (motivo === null) return;

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_rechazar_prestamo',
                    nonce: bibliotecaData.nonce,
                    prestamo_id: prestamoId,
                    motivo: motivo
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.closest('.solicitud-card').fadeOut();
                    } else {
                        this.showToast('error', response.error);
                    }
                }
            });
        },

        handleEliminarLibro: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const libroId = $btn.data('libro-id');

            if (!confirm('¿Eliminar este libro de la biblioteca?')) return;

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_eliminar_libro',
                    nonce: bibliotecaData.nonce,
                    libro_id: libroId
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        $btn.closest('.mi-libro-card').fadeOut();
                    } else {
                        this.showToast('error', response.error);
                    }
                }
            });
        },

        handleEditarLibro: function(e) {
            e.preventDefault();
            const libroId = $(e.currentTarget).data('libro-id');
            window.location.href = window.location.pathname + '?editar=' + libroId;
        },

        handleValoracion: function(e) {
            const $star = $(e.currentTarget);
            const valor = $star.data('valor');
            const $container = $star.closest('.libro-valoracion-estrellas');

            $container.find('.star').removeClass('active');
            $container.find('.star').each(function() {
                if ($(this).data('valor') <= valor) {
                    $(this).addClass('active');
                }
            });

            $container.data('valoracion', valor);
        },

        handleEnviarResena: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const libroId = $form.data('libro-id');
            const valoracion = $form.find('.libro-valoracion-estrellas').data('valoracion');
            const resena = $form.find('textarea').val();

            if (!valoracion) {
                this.showToast('warning', 'Selecciona una valoración');
                return;
            }

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_valorar_libro',
                    nonce: bibliotecaData.nonce,
                    libro_id: libroId,
                    valoracion: valoracion,
                    resena: resena
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        location.reload();
                    } else {
                        this.showToast('error', response.error);
                    }
                }
            });
        },

        handleBuscarISBN: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const isbn = $('#isbn').val().trim();

            if (!isbn) {
                this.showToast('warning', 'Introduce un ISBN');
                return;
            }

            $btn.prop('disabled', true).text('Buscando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: {
                    action: 'biblioteca_buscar_isbn',
                    nonce: bibliotecaData.nonce,
                    isbn: isbn
                },
                success: (response) => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span>');

                    if (response.success) {
                        const libro = response.libro;
                        $('#titulo').val(libro.titulo);
                        $('#autor').val(libro.autor);
                        $('#editorial').val(libro.editorial);
                        $('#ano').val(libro.ano);
                        $('#paginas').val(libro.paginas);

                        if (libro.portada) {
                            $('.portada-preview').html(`<img src="${libro.portada}" alt="Portada">`);
                            $('#portada_url').val(libro.portada);
                        }

                        this.showToast('success', 'Datos del libro encontrados');
                    } else {
                        this.showToast('warning', response.error || 'Libro no encontrado');
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span>');
                    this.showToast('error', 'Error al buscar ISBN');
                }
            });
        },

        handleAgregarLibro: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            const datos = {
                action: 'biblioteca_agregar_libro',
                nonce: bibliotecaData.nonce,
                titulo: $('#titulo').val(),
                autor: $('#autor').val(),
                isbn: $('#isbn').val(),
                editorial: $('#editorial').val(),
                ano: $('#ano').val(),
                idioma: $('#idioma').val(),
                genero: $('#genero').val(),
                paginas: $('#paginas').val(),
                descripcion: $('#descripcion').val(),
                portada_url: $('#portada_url').val(),
                estado_fisico: $('#estado_fisico').val(),
                tipo: $('#tipo').val(),
                ubicacion: $('#ubicacion').val()
            };

            $btn.prop('disabled', true).text('Guardando...');

            $.ajax({
                url: bibliotecaData.ajax_url,
                type: 'POST',
                data: datos,
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.mensaje);
                        setTimeout(() => {
                            window.location.href = window.location.pathname.replace('agregar', 'mis-libros');
                        }, 1500);
                    } else {
                        this.showToast('error', response.error);
                        $btn.prop('disabled', false).text('Agregar libro');
                    }
                },
                error: () => {
                    this.showToast('error', 'Error de conexión');
                    $btn.prop('disabled', false).text('Agregar libro');
                }
            });
        },

        handleSeleccionarPortada: function(e) {
            e.preventDefault();

            if (typeof wp !== 'undefined' && wp.media) {
                const frame = wp.media({
                    title: 'Seleccionar portada',
                    button: { text: 'Usar esta imagen' },
                    multiple: false
                });

                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('.portada-preview').html(`<img src="${attachment.url}" alt="Portada">`);
                    $('#portada_url').val(attachment.url);
                });

                frame.open();
            } else {
                const url = prompt('URL de la imagen de portada:');
                if (url) {
                    $('.portada-preview').html(`<img src="${url}" alt="Portada">`);
                    $('#portada_url').val(url);
                }
            }
        },

        handleTab: function(e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const target = $tab.data('tab');

            $('.mis-prestamos-tab').removeClass('active');
            $tab.addClass('active');

            $('.mis-prestamos-panel').hide();
            $('#' + target).show();
        },

        cerrarModal: function() {
            $('.biblioteca-modal').removeClass('open');
            setTimeout(() => {
                $('.biblioteca-modal').remove();
            }, 300);
        },

        showToast: function(type, message) {
            const $toast = $(`<div class="biblioteca-toast ${type}">${message}</div>`);
            $('body').append($toast);

            setTimeout(() => {
                $toast.addClass('show');
            }, 100);

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        Biblioteca.init();
    });

})(jQuery);
