/**
 * JavaScript Frontend para Eventos
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    /**
     * Módulo principal de Eventos
     */
    const FlavorEventos = {

        /**
         * Configuración
         */
        config: {
            ajaxUrl: flavorEventos?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: flavorEventos?.nonce || '',
            i18n: flavorEventos?.i18n || {}
        },

        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initCalendario();
            this.initFiltros();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Inscripción a eventos
            $(document).on('click', '.js-eventos-inscribirse', this.handleInscribirse.bind(this));

            // Cancelar inscripción
            $(document).on('click', '.js-eventos-cancelar', this.handleCancelar.bind(this));

            // Compartir evento
            $(document).on('click', '.js-eventos-compartir', this.handleCompartir.bind(this));

            // Filtros
            $(document).on('change', '.js-eventos-filtro', this.handleFiltrar.bind(this));
            $(document).on('submit', '.js-eventos-filtros-form', this.handleFiltrarForm.bind(this));

            // Calendario navegación
            $(document).on('click', '.js-calendario-prev', this.handleCalendarioPrev.bind(this));
            $(document).on('click', '.js-calendario-next', this.handleCalendarioNext.bind(this));
            $(document).on('click', '.eventos-calendario__dia.tiene-eventos', this.handleCalendarioDiaClick.bind(this));

            // Lista de espera
            $(document).on('click', '.js-eventos-lista-espera', this.handleListaEspera.bind(this));

            // Cargar más
            $(document).on('click', '.js-eventos-cargar-mas', this.handleCargarMas.bind(this));
        },

        /**
         * Inscribirse a evento
         */
        handleInscribirse: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const eventoId = $btn.data('evento-id');

            if ($btn.hasClass('loading')) return;

            $btn.addClass('loading').prop('disabled', true);
            const textoOriginal = $btn.text();
            $btn.text(this.config.i18n.cargando || 'Cargando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_inscribirse',
                    evento_id: eventoId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || this.config.i18n.inscrito, 'success');

                        // Actualizar UI
                        $btn.removeClass('js-eventos-inscribirse')
                            .addClass('js-eventos-cancelar evento-btn--danger')
                            .text('Cancelar inscripción');

                        // Actualizar contador de plazas
                        if (response.data.plazas_restantes !== undefined) {
                            this.actualizarPlazas(eventoId, response.data.plazas_restantes);
                        }

                        // Disparar evento personalizado
                        $(document).trigger('eventos:inscrito', [eventoId, response.data]);
                    } else {
                        this.showNotification(response.data?.message || this.config.i18n.error, 'error');
                        $btn.text(textoOriginal);
                    }
                },
                error: () => {
                    this.showNotification(this.config.i18n.error || 'Error de conexión', 'error');
                    $btn.text(textoOriginal);
                },
                complete: () => {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Cancelar inscripción
         */
        handleCancelar: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const eventoId = $btn.data('evento-id');

            if (!confirm(this.config.i18n.confirmacion || '¿Estás seguro?')) {
                return;
            }

            if ($btn.hasClass('loading')) return;

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_cancelar_inscripcion',
                    evento_id: eventoId,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || this.config.i18n.cancelado, 'success');

                        // Actualizar UI
                        $btn.removeClass('js-eventos-cancelar evento-btn--danger')
                            .addClass('js-eventos-inscribirse evento-btn--primary')
                            .text('Inscribirme');

                        // Actualizar contador de plazas
                        if (response.data.plazas_restantes !== undefined) {
                            this.actualizarPlazas(eventoId, response.data.plazas_restantes);
                        }

                        // Disparar evento personalizado
                        $(document).trigger('eventos:cancelado', [eventoId, response.data]);
                    } else {
                        this.showNotification(response.data?.message || this.config.i18n.error, 'error');
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
         * Añadirse a lista de espera
         */
        handleListaEspera: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const eventoId = $btn.data('evento-id');

            if ($btn.hasClass('loading')) return;

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_inscribirse',
                    evento_id: eventoId,
                    lista_espera: true,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(this.config.i18n.lista_espera || 'Te has añadido a la lista de espera', 'success');
                        $btn.text('En lista de espera').prop('disabled', true);

                        $(document).trigger('eventos:lista_espera', [eventoId, response.data]);
                    } else {
                        this.showNotification(response.data?.message || this.config.i18n.error, 'error');
                    }
                },
                error: () => {
                    this.showNotification(this.config.i18n.error || 'Error de conexión', 'error');
                },
                complete: () => {
                    $btn.removeClass('loading');
                }
            });
        },

        /**
         * Compartir evento
         */
        handleCompartir: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const red = $btn.data('red');
            const eventoId = $btn.data('evento-id');
            const url = $btn.data('url') || window.location.href;
            const titulo = $btn.data('titulo') || document.title;

            let shareUrl = '';

            switch (red) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(titulo)}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${encodeURIComponent(titulo + ' ' + url)}`;
                    break;
                case 'copiar':
                    this.copiarAlPortapapeles(url);
                    return;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        },

        /**
         * Copiar URL al portapapeles
         */
        copiarAlPortapapeles: function(texto) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(texto).then(() => {
                    this.showNotification('Enlace copiado al portapapeles', 'success');
                });
            } else {
                // Fallback para navegadores antiguos
                const textarea = document.createElement('textarea');
                textarea.value = texto;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.showNotification('Enlace copiado al portapapeles', 'success');
            }
        },

        /**
         * Manejar filtros
         */
        handleFiltrar: function(e) {
            const $container = $(e.currentTarget).closest('.eventos-container');
            this.filtrarEventos($container);
        },

        handleFiltrarForm: function(e) {
            e.preventDefault();
            const $container = $(e.currentTarget).closest('.eventos-container');
            this.filtrarEventos($container);
        },

        /**
         * Filtrar eventos via AJAX
         */
        filtrarEventos: function($container) {
            const $grid = $container.find('.eventos-grid');
            const $filtros = $container.find('.eventos-filtros');

            const datos = {
                action: 'eventos_filtrar',
                nonce: this.config.nonce,
                categoria: $filtros.find('[name="categoria"]').val() || '',
                fecha_desde: $filtros.find('[name="fecha_desde"]').val() || '',
                fecha_hasta: $filtros.find('[name="fecha_hasta"]').val() || '',
                ubicacion: $filtros.find('[name="ubicacion"]').val() || '',
                busqueda: $filtros.find('[name="busqueda"]').val() || '',
                orden: $filtros.find('[name="orden"]').val() || 'fecha_asc'
            };

            $grid.addClass('loading').css('opacity', '0.5');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datos,
                success: (response) => {
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);

                        // Actualizar contador
                        if (response.data.total !== undefined) {
                            $container.find('.eventos-total').text(response.data.total + ' eventos encontrados');
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
         * Inicializar calendario
         */
        initCalendario: function() {
            const $calendario = $('.eventos-calendario');
            if (!$calendario.length) return;

            this.calendarioMes = new Date().getMonth();
            this.calendarioAnio = new Date().getFullYear();
        },

        /**
         * Navegar calendario - Mes anterior
         */
        handleCalendarioPrev: function(e) {
            e.preventDefault();

            this.calendarioMes--;
            if (this.calendarioMes < 0) {
                this.calendarioMes = 11;
                this.calendarioAnio--;
            }

            this.cargarCalendario();
        },

        /**
         * Navegar calendario - Mes siguiente
         */
        handleCalendarioNext: function(e) {
            e.preventDefault();

            this.calendarioMes++;
            if (this.calendarioMes > 11) {
                this.calendarioMes = 0;
                this.calendarioAnio++;
            }

            this.cargarCalendario();
        },

        /**
         * Cargar calendario via AJAX
         */
        cargarCalendario: function() {
            const $calendario = $('.eventos-calendario');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_calendario',
                    mes: this.calendarioMes + 1,
                    anio: this.calendarioAnio,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        $calendario.html(response.data.html);
                    }
                }
            });
        },

        /**
         * Click en día del calendario
         */
        handleCalendarioDiaClick: function(e) {
            const $dia = $(e.currentTarget);
            const fecha = $dia.data('fecha');

            if (fecha) {
                // Mostrar modal con eventos del día o filtrar
                this.mostrarEventosDia(fecha);
            }
        },

        /**
         * Mostrar eventos de un día específico
         */
        mostrarEventosDia: function(fecha) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_filtrar',
                    fecha_desde: fecha,
                    fecha_hasta: fecha,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Aquí podrías mostrar un modal o scroll a la sección
                        $(document).trigger('eventos:dia_seleccionado', [fecha, response.data]);
                    }
                }
            });
        },

        /**
         * Inicializar filtros
         */
        initFiltros: function() {
            // Autocompletar ubicación si existe
            const $ubicacion = $('.js-eventos-filtro-ubicacion');
            if ($ubicacion.length && typeof $.fn.autocomplete !== 'undefined') {
                // Configurar autocomplete si está disponible
            }
        },

        /**
         * Actualizar contador de plazas
         */
        actualizarPlazas: function(eventoId, plazasRestantes) {
            const $plazas = $(`.evento-card[data-evento-id="${eventoId}"] .evento-card__plazas`);

            if (plazasRestantes === 0) {
                $plazas.text('Sin plazas disponibles')
                       .removeClass('ultimas')
                       .addClass('agotadas');
            } else if (plazasRestantes <= 5) {
                $plazas.text(`¡Últimas ${plazasRestantes} plazas!`)
                       .removeClass('agotadas')
                       .addClass('ultimas');
            } else {
                $plazas.text(`${plazasRestantes} plazas disponibles`)
                       .removeClass('agotadas ultimas');
            }
        },

        /**
         * Cargar más eventos
         */
        handleCargarMas: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const $container = $btn.closest('.eventos-container');
            const $grid = $container.find('.eventos-grid');
            const pagina = parseInt($btn.data('pagina') || 1) + 1;

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eventos_filtrar',
                    pagina: pagina,
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success && response.data.html) {
                        $grid.append(response.data.html);
                        $btn.data('pagina', pagina);

                        if (!response.data.hay_mas) {
                            $btn.hide();
                        }
                    }
                },
                complete: () => {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            // Si existe sistema de notificaciones global
            if (typeof FlavorNotifications !== 'undefined') {
                FlavorNotifications.show(message, type);
                return;
            }

            // Fallback simple
            const $notif = $('<div>')
                .addClass(`eventos-notificacion eventos-notificacion--${type}`)
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
        FlavorEventos.init();
    });

    // Exponer globalmente
    window.FlavorEventos = FlavorEventos;

})(jQuery);
