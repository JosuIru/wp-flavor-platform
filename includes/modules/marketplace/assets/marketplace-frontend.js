/**
 * Marketplace Frontend JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var MarketplaceFrontend = {
        config: window.marketplaceFrontend || {},
        filterTimeout: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Filtro de busqueda con debounce
            $(document).on('input', '#marketplace-buscar', function() {
                clearTimeout(self.filterTimeout);
                self.filterTimeout = setTimeout(function() {
                    self.filtrarAnuncios();
                }, 300);
            });

            // Filtros de select
            $(document).on('change', '#marketplace-filtrar-tipo, #marketplace-filtrar-categoria', function() {
                self.filtrarAnuncios();
            });

            // Favoritos
            $(document).on('click', '.marketplace-card-favorito', function(e) {
                e.preventDefault();
                self.toggleFavorito($(this));
            });

            // Formulario de publicar anuncio
            $(document).on('submit', '#marketplace-publicar-form', function(e) {
                e.preventDefault();
                self.publicarAnuncio($(this));
            });

            // Guardar borrador
            $(document).on('click', '#mp-guardar-borrador', function(e) {
                e.preventDefault();
                self.publicarAnuncio($('#marketplace-publicar-form'), true);
            });
        },

        publicarAnuncio: function($form, esBorrador) {
            var self = this;
            var $submitBtn = $form.find('button[type="submit"]');
            var formData = new FormData($form[0]);

            // Añadir flag de borrador si aplica
            if (esBorrador) {
                formData.append('estado', 'borrador');
            }

            $submitBtn.prop('disabled', true).text(self.config.i18n.cargando);

            $.ajax({
                url: self.config.restUrl + 'anuncios',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-WP-Nonce': self.config.restNonce
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).text(esBorrador ? 'Guardar borrador' : 'Publicar Anuncio');
                    if (response.success) {
                        self.mostrarNotificacion(response.mensaje || 'Anuncio publicado', 'success');
                        // Redireccionar a mis anuncios o al detalle
                        if (response.anuncio && response.anuncio.id) {
                            window.location.href = self.config.restUrl.replace('/flavor/v1/marketplace/', '/mi-portal/marketplace/detalle/?anuncio_id=') + response.anuncio.id;
                        } else {
                            window.location.href = window.location.origin + '/mi-portal/marketplace/mis-anuncios/';
                        }
                    } else {
                        self.mostrarNotificacion(response.message || self.config.i18n.error, 'error');
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).text(esBorrador ? 'Guardar borrador' : 'Publicar Anuncio');
                    var errorMsg = self.config.i18n.error;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    self.mostrarNotificacion(errorMsg, 'error');
                }
            });
        },

        filtrarAnuncios: function() {
            var self = this;
            var $grid = $('#marketplace-lista');
            var $catalogo = $('.marketplace-catalogo');
            var buscar = $('#marketplace-buscar').val();
            var tipo = $('#marketplace-filtrar-tipo').val();
            var categoria = $('#marketplace-filtrar-categoria').val();
            var comunidadId = $catalogo.data('comunidad') || 0;

            $grid.addClass('marketplace-loading');

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'marketplace_filtrar_anuncios',
                    nonce: self.config.nonce,
                    buscar: buscar,
                    tipo: tipo,
                    categoria: categoria,
                    comunidad_id: comunidadId
                },
                success: function(response) {
                    $grid.removeClass('marketplace-loading');
                    if (response.success && response.data.html) {
                        $grid.html(response.data.html);
                    } else if (response.success && response.data.anuncios) {
                        // Si devuelve JSON, renderizar
                        self.renderAnuncios(response.data.anuncios);
                    } else {
                        $grid.html('<p class="marketplace-sin-anuncios">' + self.config.i18n.sinResultados + '</p>');
                    }
                },
                error: function() {
                    $grid.removeClass('marketplace-loading');
                    console.error('Error al filtrar anuncios');
                }
            });
        },

        toggleFavorito: function($btn) {
            var self = this;
            var anuncioId = $btn.data('anuncio-id');
            var isActivo = $btn.hasClass('activo');
            var action = isActivo ? 'marketplace_quitar_favorito' : 'marketplace_agregar_favorito';

            if (!self.config.isLoggedIn) {
                window.location.href = self.config.loginUrl;
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: self.config.nonce,
                    anuncio_id: anuncioId
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $btn.toggleClass('activo');
                        var mensaje = isActivo ? self.config.i18n.quitadoFavoritos : self.config.i18n.agregadoFavoritos;
                        self.mostrarNotificacion(mensaje, 'success');
                    } else {
                        self.mostrarNotificacion(response.data.message || self.config.i18n.error, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    self.mostrarNotificacion(self.config.i18n.error, 'error');
                }
            });
        },

        mostrarNotificacion: function(mensaje, tipo) {
            // Notificacion simple - puede mejorarse con una libreria de toast
            var $notif = $('<div class="marketplace-notificacion ' + tipo + '">' + mensaje + '</div>');
            $('body').append($notif);

            setTimeout(function() {
                $notif.addClass('visible');
            }, 10);

            setTimeout(function() {
                $notif.removeClass('visible');
                setTimeout(function() {
                    $notif.remove();
                }, 300);
            }, 3000);
        },

        renderAnuncios: function(anuncios) {
            var $grid = $('#marketplace-lista');
            var html = '';

            if (!anuncios || anuncios.length === 0) {
                html = '<p class="marketplace-sin-anuncios">' + this.config.i18n.sinResultados + '</p>';
            } else {
                // Renderizado basico - el servidor deberia devolver HTML preferiblemente
                anuncios.forEach(function(anuncio) {
                    html += '<div class="marketplace-card">';
                    html += '<div class="marketplace-card-contenido">';
                    html += '<h3 class="marketplace-card-titulo">' + anuncio.titulo + '</h3>';
                    html += '</div>';
                    html += '</div>';
                });
            }

            $grid.html(html);
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        MarketplaceFrontend.init();
    });

})(jQuery);
