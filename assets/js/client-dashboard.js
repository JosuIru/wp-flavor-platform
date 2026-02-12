/**
 * Flavor Client Dashboard - JavaScript
 *
 * Gestion de la interfaz del dashboard de cliente.
 * Incluye carga dinamica de widgets, actualizacion de estadisticas,
 * atajos de teclado, notificaciones toast y preferencias.
 *
 * @package FlavorChatIA
 */
(function($) {
    'use strict';

    /**
     * Modulo principal del Dashboard de Cliente
     */
    var FlavorClientDashboard = {
        /**
         * Configuracion del dashboard
         */
        configuracion: {},

        /**
         * Intervalo de actualizacion automatica
         */
        intervaloActualizacion: null,

        /**
         * Estado de peticion en curso
         */
        peticionEnCurso: false,

        /**
         * Preferencias del usuario (cargadas de localStorage)
         */
        preferenciasLocales: {},

        /**
         * Elementos DOM cacheados
         */
        elementos: {},

        /**
         * Inicializa el dashboard
         */
        init: function() {
            if (typeof flavorClientDashboard === 'undefined') {
                return;
            }

            this.configuracion = flavorClientDashboard;
            this.cargarPreferenciasLocales();
            this.cachearElementos();
            this.vincularEventos();
            this.inicializarTema();
            this.inicializarAtajosTeclado();
            this.iniciarActualizacionAutomatica();
            this.inicializarTooltips();

            // Emitir evento de inicializacion
            $(document).trigger('flavorClientDashboard:ready', [this]);
        },

        /**
         * Carga preferencias desde localStorage
         */
        cargarPreferenciasLocales: function() {
            try {
                var preferenciasGuardadas = localStorage.getItem('flavorClientDashboardPrefs');
                if (preferenciasGuardadas) {
                    this.preferenciasLocales = JSON.parse(preferenciasGuardadas);
                }
            } catch (errorParsePrefs) {
                console.warn('Error al cargar preferencias:', errorParsePrefs);
                this.preferenciasLocales = {};
            }
        },

        /**
         * Guarda preferencias en localStorage
         */
        guardarPreferenciasLocales: function() {
            try {
                localStorage.setItem('flavorClientDashboardPrefs', JSON.stringify(this.preferenciasLocales));
            } catch (errorGuardarPrefs) {
                console.warn('Error al guardar preferencias:', errorGuardarPrefs);
            }
        },

        /**
         * Cachea elementos DOM frecuentemente usados
         */
        cachearElementos: function() {
            this.elementos = {
                dashboard: $('.flavor-client-dashboard'),
                botonRefresh: $('#flavor-dashboard-refresh'),
                botonTema: $('#flavor-dashboard-theme-toggle'),
                botonNotificaciones: $('#flavor-dashboard-notifications-toggle'),
                panelNotificaciones: $('#flavor-notifications-panel'),
                contenedorToasts: $('#flavor-dashboard-toasts'),
                indicadorCarga: $('#flavor-dashboard-loading'),
                timelineActividad: $('#flavor-activity-timeline'),
                estadisticas: $('.flavor-client-dashboard__stat-card'),
                widgets: $('.flavor-client-dashboard__widget')
            };
        },

        /**
         * Vincula todos los eventos
         */
        vincularEventos: function() {
            var self = this;

            // Boton de refrescar
            this.elementos.botonRefresh.on('click', function(evento) {
                evento.preventDefault();
                self.actualizarDashboard();
            });

            // Toggle de tema
            this.elementos.botonTema.on('click', function(evento) {
                evento.preventDefault();
                self.alternarTema();
            });

            // Toggle de notificaciones
            this.elementos.botonNotificaciones.on('click', function(evento) {
                evento.preventDefault();
                self.alternarPanelNotificaciones();
            });

            // Cerrar panel de notificaciones al hacer clic fuera
            $(document).on('click', function(evento) {
                if (!$(evento.target).closest('.flavor-client-dashboard__notifications-panel, .flavor-client-dashboard__btn-icon--notifications').length) {
                    self.cerrarPanelNotificaciones();
                }
            });

            // Descartar notificacion
            $(document).on('click', '.flavor-client-dashboard__notification-dismiss', function(evento) {
                evento.preventDefault();
                var idNotificacion = $(this).data('notification-id');
                self.descartarNotificacion(idNotificacion, $(this).closest('.flavor-client-dashboard__notification-item'));
            });

            // Marcar todas las notificaciones como leidas
            $('#flavor-mark-all-read').on('click', function(evento) {
                evento.preventDefault();
                self.marcarTodasNotificacionesLeidas();
            });

            // Toggle de widget colapsado
            $(document).on('click', '.flavor-client-dashboard__widget-toggle', function(evento) {
                evento.preventDefault();
                var idWidget = $(this).data('widget-id');
                self.alternarWidget(idWidget);
            });

            // Refrescar widget individual
            $(document).on('click', '.flavor-client-dashboard__widget-refresh', function(evento) {
                evento.preventDefault();
                var idWidget = $(this).data('widget-id');
                self.refrescarWidget(idWidget, $(this));
            });

            // Eventos de visibilidad de pagina
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.pausarActualizacionAutomatica();
                } else {
                    self.reanudarActualizacionAutomatica();
                }
            });

            // Limpiar al descargar
            $(window).on('beforeunload', function() {
                self.limpiarRecursos();
            });
        },

        /**
         * Inicializa el tema segun preferencias
         */
        inicializarTema: function() {
            var temaGuardado = this.preferenciasLocales.tema || this.configuracion.preferences.tema || 'auto';

            if (temaGuardado === 'dark') {
                this.elementos.dashboard.addClass('flavor-dark').attr('data-theme', 'dark');
            } else if (temaGuardado === 'light') {
                this.elementos.dashboard.addClass('flavor-light').attr('data-theme', 'light');
            }
            // Si es 'auto', se usa CSS media queries
        },

        /**
         * Alterna entre tema claro y oscuro
         */
        alternarTema: function() {
            var dashboard = this.elementos.dashboard;
            var temaActual = dashboard.attr('data-theme') || 'auto';
            var nuevoTema;

            if (temaActual === 'dark') {
                nuevoTema = 'light';
                dashboard.removeClass('flavor-dark').addClass('flavor-light');
            } else if (temaActual === 'light') {
                nuevoTema = 'auto';
                dashboard.removeClass('flavor-light flavor-dark');
            } else {
                nuevoTema = 'dark';
                dashboard.removeClass('flavor-light').addClass('flavor-dark');
            }

            dashboard.attr('data-theme', nuevoTema);
            this.preferenciasLocales.tema = nuevoTema;
            this.guardarPreferenciasLocales();
            this.guardarPreferenciasServidor({ tema: nuevoTema });

            this.mostrarToast(
                nuevoTema === 'auto'
                    ? 'Tema automatico activado'
                    : (nuevoTema === 'dark' ? 'Tema oscuro activado' : 'Tema claro activado'),
                'info'
            );
        },

        /**
         * Inicializa atajos de teclado
         */
        inicializarAtajosTeclado: function() {
            var self = this;

            $(document).on('keydown', function(evento) {
                // No procesar si estamos en un input
                if ($(evento.target).is('input, textarea, select, [contenteditable="true"]')) {
                    return;
                }

                // Ctrl+R o Cmd+R: Refrescar dashboard
                if ((evento.ctrlKey || evento.metaKey) && evento.key === 'r') {
                    evento.preventDefault();
                    self.actualizarDashboard();
                    return;
                }

                // Ctrl+K o Cmd+K: Buscar (futuro)
                if ((evento.ctrlKey || evento.metaKey) && evento.key === 'k') {
                    evento.preventDefault();
                    self.abrirBuscador();
                    return;
                }

                // Escape: Cerrar paneles
                if (evento.key === 'Escape') {
                    self.cerrarPanelNotificaciones();
                    return;
                }

                // N: Toggle notificaciones
                if (evento.key === 'n' && !evento.ctrlKey && !evento.metaKey) {
                    self.alternarPanelNotificaciones();
                    return;
                }

                // T: Toggle tema
                if (evento.key === 't' && !evento.ctrlKey && !evento.metaKey) {
                    self.alternarTema();
                    return;
                }
            });
        },

        /**
         * Inicializa tooltips basicos
         */
        inicializarTooltips: function() {
            // Integrar con FlavorTooltips si esta disponible
            if (typeof FlavorTooltips !== 'undefined') {
                FlavorTooltips.init('.flavor-client-dashboard [title]');
            }
        },

        /**
         * Inicia actualizacion automatica
         */
        iniciarActualizacionAutomatica: function() {
            var self = this;
            var intervaloMs = this.configuracion.refreshInterval || 120000;

            if (!this.configuracion.userId) {
                return;
            }

            this.intervaloActualizacion = setInterval(function() {
                self.actualizarEstadisticas();
            }, intervaloMs);
        },

        /**
         * Pausa la actualizacion automatica
         */
        pausarActualizacionAutomatica: function() {
            if (this.intervaloActualizacion) {
                clearInterval(this.intervaloActualizacion);
                this.intervaloActualizacion = null;
            }
        },

        /**
         * Reanuda la actualizacion automatica
         */
        reanudarActualizacionAutomatica: function() {
            if (!this.intervaloActualizacion) {
                this.iniciarActualizacionAutomatica();
                this.actualizarEstadisticas(); // Actualizar inmediatamente
            }
        },

        /**
         * Limpia recursos al cerrar
         */
        limpiarRecursos: function() {
            this.pausarActualizacionAutomatica();
        },

        // =====================================================================
        // Actualizacion de datos
        // =====================================================================

        /**
         * Actualiza todo el dashboard
         */
        actualizarDashboard: function() {
            var self = this;

            if (this.peticionEnCurso) {
                return;
            }

            this.mostrarCarga();
            this.peticionEnCurso = true;

            // Actualizar estadisticas, widgets y actividad en paralelo
            var promesaEstadisticas = this.actualizarEstadisticas();
            var promesaActividad = this.actualizarActividad();

            $.when(promesaEstadisticas, promesaActividad)
                .always(function() {
                    self.peticionEnCurso = false;
                    self.ocultarCarga();
                    self.mostrarToast(self.configuracion.i18n.actualizado, 'success');
                });
        },

        /**
         * Actualiza las estadisticas
         */
        actualizarEstadisticas: function() {
            var self = this;

            return $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_client_dashboard_stats',
                    nonce: this.configuracion.nonce
                },
                success: function(respuesta) {
                    if (respuesta.success && respuesta.data.estadisticas) {
                        self.renderizarEstadisticas(respuesta.data.estadisticas);
                    }
                },
                error: function() {
                    console.warn('Error al actualizar estadisticas');
                }
            });
        },

        /**
         * Renderiza las estadisticas actualizadas
         */
        renderizarEstadisticas: function(estadisticas) {
            var self = this;

            $.each(estadisticas, function(identificador, datos) {
                var tarjeta = self.elementos.dashboard.find('[data-stat-id="' + identificador + '"]');
                if (tarjeta.length) {
                    var elementoValor = tarjeta.find('.flavor-client-dashboard__stat-value');
                    var valorActual = parseInt(elementoValor.attr('data-value'), 10) || 0;
                    var valorNuevo = datos.valor;

                    // Animar cambio de valor
                    if (valorActual !== valorNuevo) {
                        self.animarCambioValor(elementoValor, valorActual, valorNuevo);
                    }

                    // Actualizar texto secundario
                    var elementoMeta = tarjeta.find('.flavor-client-dashboard__stat-meta');
                    if (datos.texto && elementoMeta.length) {
                        elementoMeta.text(datos.texto);
                    }
                }
            });
        },

        /**
         * Anima el cambio de un valor numerico
         */
        animarCambioValor: function(elementoValor, valorInicial, valorFinal) {
            var duracion = 600;
            var pasos = 30;
            var incremento = (valorFinal - valorInicial) / pasos;
            var valorActual = valorInicial;
            var paso = 0;

            var intervaloAnimacion = setInterval(function() {
                paso++;
                valorActual += incremento;

                if (paso >= pasos) {
                    valorActual = valorFinal;
                    clearInterval(intervaloAnimacion);
                }

                elementoValor
                    .attr('data-value', Math.round(valorActual))
                    .text(Math.round(valorActual).toLocaleString());
            }, duracion / pasos);
        },

        /**
         * Actualiza el timeline de actividad
         */
        actualizarActividad: function() {
            var self = this;

            return $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_client_dashboard_activity',
                    nonce: this.configuracion.nonce,
                    limit: 10
                },
                success: function(respuesta) {
                    if (respuesta.success && respuesta.data.actividad) {
                        self.renderizarActividad(respuesta.data.actividad);
                    }
                },
                error: function() {
                    console.warn('Error al actualizar actividad');
                }
            });
        },

        /**
         * Renderiza el timeline de actividad
         */
        renderizarActividad: function(actividad) {
            var contenedor = this.elementos.timelineActividad;

            if (!actividad || actividad.length === 0) {
                contenedor.html(
                    '<div class="flavor-client-dashboard__empty-state">' +
                    '<p class="flavor-client-dashboard__empty-text">' + this.configuracion.i18n.sin_actividad + '</p>' +
                    '</div>'
                );
                return;
            }

            var htmlActividad = '<ul class="flavor-client-dashboard__timeline">';

            $.each(actividad, function(indice, item) {
                var tipoActividad = item.tipo || 'default';
                var fechaFormateada = item.fecha_relativa || '';

                htmlActividad += '<li class="flavor-client-dashboard__timeline-item flavor-client-dashboard__timeline-item--' + tipoActividad + '">';
                htmlActividad += '<span class="flavor-client-dashboard__timeline-icon"></span>';
                htmlActividad += '<div class="flavor-client-dashboard__timeline-content">';
                htmlActividad += '<span class="flavor-client-dashboard__timeline-text">' + item.texto + '</span>';
                if (fechaFormateada) {
                    htmlActividad += '<time class="flavor-client-dashboard__timeline-time">' + fechaFormateada + '</time>';
                }
                htmlActividad += '</div>';
                if (item.url) {
                    htmlActividad += '<a href="' + item.url + '" class="flavor-client-dashboard__timeline-link" aria-label="Ver detalle">';
                    htmlActividad += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
                    htmlActividad += '</a>';
                }
                htmlActividad += '</li>';
            });

            htmlActividad += '</ul>';
            contenedor.html(htmlActividad);
        },

        // =====================================================================
        // Widgets
        // =====================================================================

        /**
         * Alterna el estado colapsado de un widget
         */
        alternarWidget: function(idWidget) {
            var widget = this.elementos.dashboard.find('[data-widget-id="' + idWidget + '"]');
            var estaColapsado = widget.hasClass('flavor-client-dashboard__widget--collapsed');
            var botonToggle = widget.find('.flavor-client-dashboard__widget-toggle');

            if (estaColapsado) {
                widget.removeClass('flavor-client-dashboard__widget--collapsed');
                widget.attr('data-collapsed', 'false');
                botonToggle.attr('aria-expanded', 'true');

                // Quitar de la lista de colapsados
                var colapsados = this.preferenciasLocales.widgets_colapsados || [];
                var indice = colapsados.indexOf(idWidget);
                if (indice > -1) {
                    colapsados.splice(indice, 1);
                }
                this.preferenciasLocales.widgets_colapsados = colapsados;
            } else {
                widget.addClass('flavor-client-dashboard__widget--collapsed');
                widget.attr('data-collapsed', 'true');
                botonToggle.attr('aria-expanded', 'false');

                // Agregar a la lista de colapsados
                var colapsadosActuales = this.preferenciasLocales.widgets_colapsados || [];
                if (colapsadosActuales.indexOf(idWidget) === -1) {
                    colapsadosActuales.push(idWidget);
                }
                this.preferenciasLocales.widgets_colapsados = colapsadosActuales;
            }

            this.guardarPreferenciasLocales();
            this.guardarPreferenciasServidor({ widgets_colapsados: this.preferenciasLocales.widgets_colapsados });
        },

        /**
         * Refresca el contenido de un widget
         */
        refrescarWidget: function(idWidget, botonRefresh) {
            var self = this;
            var widget = this.elementos.dashboard.find('[data-widget-id="' + idWidget + '"]');
            var contenido = widget.find('.flavor-client-dashboard__widget-content');

            botonRefresh.addClass('is-loading');

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_client_dashboard_widgets',
                    nonce: this.configuracion.nonce,
                    widget_id: idWidget
                },
                success: function(respuesta) {
                    if (respuesta.success && respuesta.data.html) {
                        contenido.html(respuesta.data.html);
                    }
                },
                error: function() {
                    self.mostrarToast(self.configuracion.i18n.error_conexion, 'error');
                },
                complete: function() {
                    botonRefresh.removeClass('is-loading');
                }
            });
        },

        // =====================================================================
        // Notificaciones
        // =====================================================================

        /**
         * Alterna el panel de notificaciones
         */
        alternarPanelNotificaciones: function() {
            var panel = this.elementos.panelNotificaciones;
            var boton = this.elementos.botonNotificaciones;
            var estaAbierto = panel.attr('aria-hidden') === 'false';

            if (estaAbierto) {
                this.cerrarPanelNotificaciones();
            } else {
                panel.attr('aria-hidden', 'false');
                boton.attr('aria-expanded', 'true');
            }
        },

        /**
         * Cierra el panel de notificaciones
         */
        cerrarPanelNotificaciones: function() {
            this.elementos.panelNotificaciones.attr('aria-hidden', 'true');
            this.elementos.botonNotificaciones.attr('aria-expanded', 'false');
        },

        /**
         * Descarta una notificacion
         */
        descartarNotificacion: function(idNotificacion, elementoNotificacion) {
            var self = this;

            elementoNotificacion.fadeOut(200, function() {
                $(this).remove();
                self.actualizarContadorNotificaciones();
            });

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_client_dashboard_dismiss_notification',
                    nonce: this.configuracion.nonce,
                    notification_id: idNotificacion
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarToast(self.configuracion.i18n.notificacion_descartada, 'success');
                    }
                }
            });
        },

        /**
         * Marca todas las notificaciones como leidas
         */
        marcarTodasNotificacionesLeidas: function() {
            var self = this;

            this.elementos.panelNotificaciones.find('.flavor-client-dashboard__notification-item').fadeOut(200, function() {
                $(this).remove();
            });

            setTimeout(function() {
                self.actualizarContadorNotificaciones();
                self.cerrarPanelNotificaciones();
            }, 250);

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mark_all_notifications_read',
                    nonce: this.configuracion.nonce
                }
            });
        },

        /**
         * Actualiza el contador de notificaciones
         */
        actualizarContadorNotificaciones: function() {
            var cantidadNotificaciones = this.elementos.panelNotificaciones.find('.flavor-client-dashboard__notification-item').length;
            var badge = this.elementos.botonNotificaciones.find('.flavor-client-dashboard__notification-badge');

            if (cantidadNotificaciones > 0) {
                badge.text(cantidadNotificaciones);
            } else {
                badge.remove();
            }
        },

        // =====================================================================
        // Utilidades
        // =====================================================================

        /**
         * Muestra el indicador de carga
         */
        mostrarCarga: function() {
            this.elementos.indicadorCarga.attr('aria-hidden', 'false');

            // Integrar con FlavorLoading si esta disponible
            if (typeof FlavorLoading !== 'undefined') {
                FlavorLoading.show(this.elementos.dashboard);
            }
        },

        /**
         * Oculta el indicador de carga
         */
        ocultarCarga: function() {
            this.elementos.indicadorCarga.attr('aria-hidden', 'true');

            if (typeof FlavorLoading !== 'undefined') {
                FlavorLoading.hide(this.elementos.dashboard);
            }
        },

        /**
         * Muestra un toast de notificacion
         */
        mostrarToast: function(mensaje, tipo) {
            var self = this;
            tipo = tipo || 'info';

            var idToast = 'toast-' + Date.now();
            var htmlToast = '<div class="flavor-client-dashboard__toast flavor-client-dashboard__toast--' + tipo + '" id="' + idToast + '">' +
                            '<span>' + mensaje + '</span>' +
                            '</div>';

            this.elementos.contenedorToasts.append(htmlToast);

            // Auto-ocultar despues de 4 segundos
            setTimeout(function() {
                var toast = $('#' + idToast);
                toast.addClass('is-hiding');

                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 4000);
        },

        /**
         * Guarda preferencias en el servidor
         */
        guardarPreferenciasServidor: function(preferencias) {
            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: $.extend({
                    action: 'flavor_client_dashboard_save_preferences',
                    nonce: this.configuracion.nonce
                }, preferencias)
            });
        },

        /**
         * Abre el buscador (futuro)
         */
        abrirBuscador: function() {
            // Implementacion futura de buscador global
            this.mostrarToast('Buscador (proximamente)', 'info');
        },

        /**
         * Formatea fecha relativa
         */
        formatearFechaRelativa: function(fecha) {
            var timestamp = typeof fecha === 'number' ? fecha : Date.parse(fecha);
            var ahora = Date.now();
            var diferencia = Math.floor((ahora - timestamp) / 1000);

            if (diferencia < 60) {
                return this.configuracion.i18n.hace_momentos;
            } else if (diferencia < 3600) {
                var minutos = Math.floor(diferencia / 60);
                return this.configuracion.i18n.hace_minutos.replace('%d', minutos);
            } else if (diferencia < 86400) {
                var horas = Math.floor(diferencia / 3600);
                return this.configuracion.i18n.hace_horas.replace('%d', horas);
            } else {
                var dias = Math.floor(diferencia / 86400);
                return this.configuracion.i18n.hace_dias.replace('%d', dias);
            }
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        FlavorClientDashboard.init();
    });

    // Exponer globalmente para extension
    window.FlavorClientDashboard = FlavorClientDashboard;

})(jQuery);
