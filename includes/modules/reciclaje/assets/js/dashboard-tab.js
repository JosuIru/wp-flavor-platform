/**
 * JavaScript para las tabs del Dashboard de Reciclaje
 *
 * @package FlavorChatIA
 * @subpackage Reciclaje
 */

(function($) {
    'use strict';

    /**
     * Modulo de Dashboard de Reciclaje
     */
    var ReciclajeDashboard = {

        /**
         * Configuracion
         */
        config: {
            ajaxUrl: flavorReciclajeDashboard?.ajaxUrl || '',
            nonce: flavorReciclajeDashboard?.nonce || '',
            i18n: flavorReciclajeDashboard?.i18n || {}
        },

        /**
         * Selectores
         */
        selectors: {
            tabContainer: '.flavor-dashboard-tab',
            btnCanjear: '.btn-canjear',
            graficoBarra: '.grafico-barras .barra',
            evolucionBarra: '.evolucion-barra .barra',
            badgeItem: '.badge'
        },

        /**
         * Inicializacion
         */
        init: function() {
            this.bindEvents();
            this.initAnimations();
            this.initTooltips();
        },

        /**
         * Bindear eventos
         */
        bindEvents: function() {
            var self = this;

            // Evento de canje de puntos
            $(document).on('click', self.selectors.btnCanjear, function(e) {
                e.preventDefault();
                self.handleCanjePuntos($(this));
            });

            // Animacion al hacer hover en badges
            $(document).on('mouseenter', self.selectors.badgeItem, function() {
                $(this).addClass('badge-hover');
            }).on('mouseleave', self.selectors.badgeItem, function() {
                $(this).removeClass('badge-hover');
            });

            // Actualizar graficos cuando la tab se hace visible
            $(document).on('shown.bs.tab flavor:tab:shown', function(e) {
                if ($(e.target).attr('href')?.includes('reciclaje')) {
                    self.animateCharts();
                }
            });

            // Observador de interseccion para animaciones
            if ('IntersectionObserver' in window) {
                self.initIntersectionObserver();
            }
        },

        /**
         * Inicializa animaciones de entrada
         */
        initAnimations: function() {
            var self = this;

            // Animar barras de graficos al cargar
            setTimeout(function() {
                self.animateCharts();
            }, 300);

            // Animar stats cards
            $('.stat-card').each(function(index) {
                var elementoCard = $(this);
                setTimeout(function() {
                    elementoCard.addClass('stat-card-visible');
                }, index * 100);
            });
        },

        /**
         * Anima los graficos de barras
         */
        animateCharts: function() {
            // Animar barras del grafico mensual
            $(this.selectors.graficoBarra).each(function() {
                var barra = $(this);
                var alturaObjetivo = barra.css('height');
                barra.css('height', '0').animate({
                    height: alturaObjetivo
                }, 800, 'easeOutQuart');
            });

            // Animar barras de evolucion
            $(this.selectors.evolucionBarra).each(function(index) {
                var barra = $(this);
                var alturaObjetivo = barra.css('height');
                setTimeout(function() {
                    barra.css('height', '0').animate({
                        height: alturaObjetivo
                    }, 600, 'easeOutQuart');
                }, index * 50);
            });

            // Animar barra de progreso del ranking
            $('.progreso-fill').each(function() {
                var barra = $(this);
                var anchoObjetivo = barra.css('width');
                barra.css('width', '0').animate({
                    width: anchoObjetivo
                }, 1000, 'easeOutQuart');
            });

            // Animar barras de materiales
            $('.material-barra .barra-fill').each(function(index) {
                var barra = $(this);
                var anchoObjetivo = barra.css('width');
                setTimeout(function() {
                    barra.css('width', '0').animate({
                        width: anchoObjetivo
                    }, 700, 'easeOutQuart');
                }, index * 100);
            });
        },

        /**
         * Inicializa observador de interseccion
         */
        initIntersectionObserver: function() {
            var self = this;

            var observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var element = $(entry.target);

                        // Animar numeros grandes
                        if (element.hasClass('puntos-numero') ||
                            element.hasClass('impacto-numero') ||
                            element.hasClass('stat-valor')) {
                            self.animateNumber(element);
                        }

                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observar elementos numericos
            $('.puntos-numero, .impacto-numero, .stat-valor, .metrica-valor').each(function() {
                observer.observe(this);
            });
        },

        /**
         * Anima un numero de 0 al valor final
         */
        animateNumber: function(elemento) {
            var textoOriginal = elemento.text().trim();
            var valorNumerico = parseFloat(textoOriginal.replace(/[^\d.,]/g, '').replace(',', '.'));

            if (isNaN(valorNumerico)) {
                return;
            }

            var sufijo = textoOriginal.replace(/[\d.,\s]/g, '');
            var tieneDecimal = textoOriginal.indexOf(',') !== -1 || textoOriginal.indexOf('.') !== -1;
            var decimales = tieneDecimal ? 2 : 0;

            $({valor: 0}).animate({valor: valorNumerico}, {
                duration: 1000,
                easing: 'easeOutQuart',
                step: function() {
                    var valorFormateado = this.valor.toLocaleString('es-ES', {
                        minimumFractionDigits: decimales,
                        maximumFractionDigits: decimales
                    });
                    elemento.text(valorFormateado + ' ' + sufijo);
                },
                complete: function() {
                    elemento.text(textoOriginal);
                }
            });
        },

        /**
         * Inicializa tooltips
         */
        initTooltips: function() {
            // Tooltips para badges
            $(this.selectors.badgeItem).each(function() {
                var badge = $(this);
                var descripcion = badge.attr('title');

                if (descripcion) {
                    badge.removeAttr('title');

                    badge.on('mouseenter', function(e) {
                        var tooltip = $('<div class="badge-tooltip">' + descripcion + '</div>');
                        $('body').append(tooltip);

                        var posicionBadge = badge.offset();
                        var anchoBadge = badge.outerWidth();
                        var anchoTooltip = tooltip.outerWidth();

                        tooltip.css({
                            top: posicionBadge.top - tooltip.outerHeight() - 10,
                            left: posicionBadge.left + (anchoBadge / 2) - (anchoTooltip / 2)
                        }).fadeIn(200);
                    }).on('mouseleave', function() {
                        $('.badge-tooltip').remove();
                    });
                }
            });
        },

        /**
         * Maneja el canje de puntos
         */
        handleCanjePuntos: function(boton) {
            var self = this;
            var idRecompensa = boton.data('recompensa-id');
            var puntosCosto = boton.data('puntos');
            var tituloRecompensa = boton.closest('.recompensa-card').find('.recompensa-titulo').text();

            // Confirmar canje
            var mensajeConfirmacion = self.config.i18n.confirmCanje +
                '\n\n' + tituloRecompensa +
                '\nCosto: ' + puntosCosto + ' puntos';

            if (!confirm(mensajeConfirmacion)) {
                return;
            }

            // Deshabilitar boton
            boton.prop('disabled', true).text(self.config.i18n.loading);

            // Realizar peticion AJAX
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reciclaje_canjear_puntos',
                    nonce: self.config.nonce,
                    recompensa_id: idRecompensa
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message || self.config.i18n.success, 'success');

                        // Actualizar UI
                        self.actualizarPuntosDespuesCanje(puntosCosto);

                        // Recargar la seccion de recompensas
                        setTimeout(function() {
                            self.recargarTab('reciclaje-recompensas');
                        }, 1500);
                    } else {
                        self.showNotification(response.data.message || self.config.i18n.error, 'error');
                        boton.prop('disabled', false).text('Canjear');
                    }
                },
                error: function() {
                    self.showNotification(self.config.i18n.error, 'error');
                    boton.prop('disabled', false).text('Canjear');
                }
            });
        },

        /**
         * Actualiza la UI despues de un canje exitoso
         */
        actualizarPuntosDespuesCanje: function(puntosCanjeados) {
            // Actualizar el banner de puntos disponibles
            var bannerPuntos = $('.puntos-banner strong');
            if (bannerPuntos.length) {
                var puntosActuales = parseInt(bannerPuntos.text().replace(/\D/g, ''), 10);
                var nuevosPuntos = Math.max(0, puntosActuales - puntosCanjeados);
                bannerPuntos.text(nuevosPuntos.toLocaleString('es-ES'));
            }

            // Actualizar hero de puntos si existe
            var heroPuntos = $('.puntos-numero');
            if (heroPuntos.length) {
                var puntosHero = parseInt(heroPuntos.text().replace(/\D/g, ''), 10);
                var nuevosPuntosHero = Math.max(0, puntosHero - puntosCanjeados);
                heroPuntos.text(nuevosPuntosHero.toLocaleString('es-ES'));
            }
        },

        /**
         * Recarga el contenido de una tab via AJAX
         */
        recargarTab: function(tabId) {
            var self = this;
            var contenedorTab = $('[data-tab="' + tabId + '"]').closest('.tab-content');

            if (!contenedorTab.length) {
                // Fallback: recargar pagina
                location.reload();
                return;
            }

            contenedorTab.addClass('loading');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reciclaje_dashboard_load_tab',
                    nonce: self.config.nonce,
                    tab: tabId
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        contenedorTab.html(response.data.html);
                        self.initAnimations();
                        self.initTooltips();
                    }
                    contenedorTab.removeClass('loading');
                },
                error: function() {
                    contenedorTab.removeClass('loading');
                }
            });
        },

        /**
         * Muestra una notificacion
         */
        showNotification: function(mensaje, tipo) {
            tipo = tipo || 'info';

            var claseIcono = 'dashicons-info';
            if (tipo === 'success') {
                claseIcono = 'dashicons-yes-alt';
            } else if (tipo === 'error') {
                claseIcono = 'dashicons-dismiss';
            }

            var notificacion = $(
                '<div class="reciclaje-notification notification-' + tipo + '">' +
                    '<span class="dashicons ' + claseIcono + '"></span>' +
                    '<span class="notification-text">' + mensaje + '</span>' +
                '</div>'
            );

            $('body').append(notificacion);

            // Posicionar y mostrar
            setTimeout(function() {
                notificacion.addClass('notification-visible');
            }, 10);

            // Ocultar despues de 3 segundos
            setTimeout(function() {
                notificacion.removeClass('notification-visible');
                setTimeout(function() {
                    notificacion.remove();
                }, 300);
            }, 3000);
        }
    };

    /**
     * Easing personalizado si no existe
     */
    if (typeof $.easing.easeOutQuart === 'undefined') {
        $.easing.easeOutQuart = function(x, t, b, c, d) {
            return -c * ((t = t / d - 1) * t * t * t - 1) + b;
        };
    }

    /**
     * Inicializar cuando el DOM este listo
     */
    $(document).ready(function() {
        // Solo inicializar si estamos en una pagina con tabs de reciclaje
        if ($('.reciclaje-mis-aportes, .reciclaje-mis-puntos, .reciclaje-recompensas, .reciclaje-estadisticas').length) {
            ReciclajeDashboard.init();
        }
    });

    /**
     * Estilos CSS para notificaciones (inline)
     */
    $('<style>')
        .prop('type', 'text/css')
        .html(
            '.reciclaje-notification {' +
                'position: fixed;' +
                'top: 20px;' +
                'right: 20px;' +
                'padding: 15px 25px;' +
                'background: #333;' +
                'color: #fff;' +
                'border-radius: 8px;' +
                'display: flex;' +
                'align-items: center;' +
                'gap: 10px;' +
                'z-index: 99999;' +
                'transform: translateX(120%);' +
                'transition: transform 0.3s ease;' +
                'box-shadow: 0 4px 12px rgba(0,0,0,0.2);' +
            '}' +
            '.reciclaje-notification.notification-visible {' +
                'transform: translateX(0);' +
            '}' +
            '.reciclaje-notification.notification-success {' +
                'background: #2e7d32;' +
            '}' +
            '.reciclaje-notification.notification-error {' +
                'background: #c62828;' +
            '}' +
            '.reciclaje-notification .dashicons {' +
                'font-size: 20px;' +
                'width: 20px;' +
                'height: 20px;' +
            '}' +
            '.badge-tooltip {' +
                'position: absolute;' +
                'background: #333;' +
                'color: #fff;' +
                'padding: 8px 12px;' +
                'border-radius: 4px;' +
                'font-size: 12px;' +
                'white-space: nowrap;' +
                'z-index: 99999;' +
                'display: none;' +
            '}' +
            '.badge-tooltip::after {' +
                'content: "";' +
                'position: absolute;' +
                'top: 100%;' +
                'left: 50%;' +
                'transform: translateX(-50%);' +
                'border: 6px solid transparent;' +
                'border-top-color: #333;' +
            '}' +
            '.tab-content.loading {' +
                'opacity: 0.5;' +
                'pointer-events: none;' +
            '}' +
            '.stat-card {' +
                'opacity: 0;' +
                'transform: translateY(20px);' +
                'transition: opacity 0.3s ease, transform 0.3s ease;' +
            '}' +
            '.stat-card.stat-card-visible {' +
                'opacity: 1;' +
                'transform: translateY(0);' +
            '}'
        )
        .appendTo('head');

    // Exponer globalmente para debugging
    window.ReciclajeDashboard = ReciclajeDashboard;

})(jQuery);
