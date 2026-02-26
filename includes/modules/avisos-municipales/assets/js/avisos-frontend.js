/**
 * JavaScript Frontend para Avisos Municipales
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorAvisos = {
        config: window.flavorAvisosConfig || {},

        init: function() {
            this.bindEvents();
            this.initBanner();
        },

        bindEvents: function() {
            // Confirmar lectura
            $(document).on('click', '.flavor-btn-confirmar', this.confirmarLectura.bind(this));

            // Marcar como leído al hacer click en aviso
            $(document).on('click', '.flavor-aviso-row.no-leido', this.marcarLeido.bind(this));

            // Filtros
            $(document).on('change', '#filtro-categoria, #filtro-prioridad', this.filtrar.bind(this));

            // Cerrar banner
            $(document).on('click', '.flavor-banner-close', this.cerrarBanner.bind(this));

            // Compartir
            $(document).on('click', '.flavor-btn-share', this.compartir.bind(this));

            // Guardar suscripciones
            $(document).on('submit', '#form-suscripciones', this.guardarSuscripciones.bind(this));
        },

        /**
         * Inicializa el banner de avisos urgentes
         */
        initBanner: function() {
            var $banner = $('.flavor-avisos-banner');
            if (!$banner.length) return;

            // Verificar si fue cerrado previamente
            var bannerId = $banner.data('banner-id') || 'default';
            if (localStorage.getItem('flavor_banner_closed_' + bannerId)) {
                $banner.hide();
            }
        },

        /**
         * Confirma la lectura de un aviso
         */
        confirmarLectura: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var avisoId = $btn.data('aviso-id');

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_confirmar',
                    nonce: this.config.nonce,
                    aviso_id: avisoId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.confirmado, 'success');

                        // Actualizar UI
                        var $box = $btn.closest('.flavor-confirmacion-box');
                        $box.html('<div class="flavor-confirmacion-estado confirmado">' +
                                  '<span class="dashicons dashicons-yes-alt"></span> ' +
                                  self.config.strings.confirmado + '</div>');
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Marca un aviso como leído
         */
        marcarLeido: function(e) {
            var $row = $(e.currentTarget);
            var avisoId = $row.data('aviso-id');

            // Quitar clase visualmente
            $row.removeClass('no-leido').addClass('leido');
            $row.find('.flavor-badge-nuevo').remove();

            // Enviar al servidor
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_marcar_leido',
                    nonce: this.config.nonce,
                    aviso_id: avisoId
                }
            });
        },

        /**
         * Filtra avisos
         */
        filtrar: function() {
            var categoria = $('#filtro-categoria').val();
            var prioridad = $('#filtro-prioridad').val();

            var url = new URL(window.location.href);

            if (categoria) {
                url.searchParams.set('categoria', categoria);
            } else {
                url.searchParams.delete('categoria');
            }

            if (prioridad) {
                url.searchParams.set('prioridad', prioridad);
            } else {
                url.searchParams.delete('prioridad');
            }

            url.searchParams.delete('pag'); // Reset paginación
            window.location.href = url.toString();
        },

        /**
         * Cierra el banner de avisos urgentes
         */
        cerrarBanner: function(e) {
            e.preventDefault();

            var $banner = $(e.currentTarget).closest('.flavor-avisos-banner');
            var bannerId = $banner.data('banner-id') || 'default';

            $banner.slideUp();
            localStorage.setItem('flavor_banner_closed_' + bannerId, '1');
        },

        /**
         * Comparte en redes sociales
         */
        compartir: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var red = $btn.data('share');
            var url = encodeURIComponent(window.location.href);
            var titulo = encodeURIComponent(document.title);

            var urls = {
                'twitter': 'https://twitter.com/intent/tweet?url=' + url + '&text=' + titulo,
                'facebook': 'https://www.facebook.com/sharer/sharer.php?u=' + url,
                'whatsapp': 'https://wa.me/?text=' + titulo + ' ' + url
            };

            if (urls[red]) {
                window.open(urls[red], '_blank', 'width=600,height=400');
            }
        },

        /**
         * Guarda las preferencias de suscripción
         */
        guardarSuscripciones: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            var categorias = [];
            $form.find('input[name="categorias[]"]:checked').each(function() {
                categorias.push($(this).val());
            });

            var zonas = [];
            $form.find('input[name="zonas[]"]:checked').each(function() {
                zonas.push($(this).val());
            });

            var canal = $form.find('input[name="canal"]:checked').val();

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_suscribir',
                    nonce: this.config.nonce,
                    categorias: categorias,
                    zonas: zonas,
                    canal: canal
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.suscrito, 'success');
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Muestra notificación toast
         */
        showToast: function(mensaje, tipo) {
            var $toast = $('<div class="flavor-toast ' + (tipo || '') + '">' + mensaje + '</div>');
            $('body').append($toast);

            setTimeout(function() {
                $toast.addClass('fade-out');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Inicializar
    $(document).ready(function() {
        FlavorAvisos.init();
    });

    // Estilos para toast fade-out
    var estilos = document.createElement('style');
    estilos.textContent = '.flavor-toast.fade-out { opacity: 0; transform: translateX(100px); transition: all 0.3s ease; } .flavor-btn.loading { pointer-events: none; opacity: 0.7; }';
    document.head.appendChild(estilos);

})(jQuery);
