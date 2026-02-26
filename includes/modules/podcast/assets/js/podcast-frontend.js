/**
 * JavaScript frontend para Podcast
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorPodcast = {
        config: window.flavorPodcastConfig || {},
        audio: null,
        episodioActual: null,
        playlist: [],
        indiceActual: 0,

        init: function() {
            this.audio = document.getElementById('flavor-audio-player');
            this.bindEvents();
            this.initPlayer();
        },

        bindEvents: function() {
            // Reproducir episodio
            $(document).on('click', '.flavor-btn-play, .flavor-btn-play-mini', this.reproducir.bind(this));

            // Suscribirse
            $(document).on('click', '.flavor-btn-suscribir', this.suscribir.bind(this));

            // Like
            $(document).on('click', '.flavor-btn-like', this.darLike.bind(this));

            // Buscar
            $(document).on('input', '#buscar-podcast', this.buscar.bind(this));

            // Filtro categoría
            $(document).on('change', '#filtro-categoria-podcast', this.filtrar.bind(this));

            // Controles del player
            $(document).on('click', '#player-play', this.togglePlay.bind(this));
            $(document).on('click', '#player-prev', this.anterior.bind(this));
            $(document).on('click', '#player-next', this.siguiente.bind(this));
            $(document).on('click', '#player-cerrar', this.cerrarPlayer.bind(this));
            $(document).on('input', '#player-barra', this.cambiarPosicion.bind(this));
            $(document).on('input', '#player-volumen', this.cambiarVolumen.bind(this));

            // Crear serie
            $(document).on('click', '#btn-crear-serie', this.abrirFormSerie.bind(this));

            // Subir episodio
            $(document).on('click', '.flavor-btn-subir-episodio', this.abrirFormEpisodio.bind(this));
        },

        initPlayer: function() {
            if (!this.audio) return;

            this.audio.addEventListener('timeupdate', this.actualizarProgreso.bind(this));
            this.audio.addEventListener('ended', this.siguienteAutomatico.bind(this));
            this.audio.addEventListener('loadedmetadata', this.audioListo.bind(this));
            this.audio.addEventListener('play', this.onPlay.bind(this));
            this.audio.addEventListener('pause', this.onPause.bind(this));

            // Cargar playlist si existe
            this.construirPlaylist();
        },

        construirPlaylist: function() {
            var self = this;
            self.playlist = [];

            $('.flavor-episodio-row, .flavor-episodio-item').each(function(index) {
                var $el = $(this);
                var $btn = $el.find('.flavor-btn-play, .flavor-btn-play-mini');
                if ($btn.length) {
                    self.playlist.push({
                        id: $el.data('id'),
                        audio: $btn.data('audio'),
                        titulo: $el.find('h3, h4').text(),
                        index: index
                    });
                }
            });
        },

        reproducir: function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(e.currentTarget);
            var audioUrl = $btn.data('audio');
            var $item = $btn.closest('.flavor-episodio-row, .flavor-episodio-item');
            var episodioId = $item.data('id');
            var titulo = $item.find('h3, h4').first().text();

            if (!audioUrl || !this.audio) return;

            // Buscar en playlist
            var indice = this.playlist.findIndex(function(ep) {
                return ep.id === episodioId;
            });
            if (indice >= 0) {
                this.indiceActual = indice;
            }

            this.cargarYReproducir(audioUrl, titulo, episodioId);
        },

        cargarYReproducir: function(audioUrl, titulo, episodioId) {
            this.audio.src = audioUrl;
            this.episodioActual = episodioId;

            $('#player-titulo').text(titulo || this.config.strings.reproduciendo);
            $('#flavor-player-flotante').show();

            this.audio.play();

            // Registrar reproducción
            this.registrarReproduccion(episodioId, 0, false);
        },

        togglePlay: function() {
            if (!this.audio) return;

            if (this.audio.paused) {
                this.audio.play();
            } else {
                this.audio.pause();
            }
        },

        onPlay: function() {
            $('#player-play .dashicons').removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
        },

        onPause: function() {
            $('#player-play .dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
        },

        anterior: function() {
            if (this.indiceActual > 0) {
                this.indiceActual--;
                var ep = this.playlist[this.indiceActual];
                if (ep) {
                    this.cargarYReproducir(ep.audio, ep.titulo, ep.id);
                }
            }
        },

        siguiente: function() {
            if (this.indiceActual < this.playlist.length - 1) {
                this.indiceActual++;
                var ep = this.playlist[this.indiceActual];
                if (ep) {
                    this.cargarYReproducir(ep.audio, ep.titulo, ep.id);
                }
            }
        },

        siguienteAutomatico: function() {
            // Registrar reproducción completa
            if (this.episodioActual) {
                this.registrarReproduccion(this.episodioActual, Math.floor(this.audio.duration), true);
            }

            // Auto siguiente si está habilitado
            this.siguiente();
        },

        actualizarProgreso: function() {
            if (!this.audio) return;

            var current = this.audio.currentTime;
            var duration = this.audio.duration || 0;
            var porcentaje = duration > 0 ? (current / duration) * 100 : 0;

            $('#player-barra').val(porcentaje);
            $('#player-tiempo-actual').text(this.formatearTiempo(current));
        },

        audioListo: function() {
            $('#player-duracion').text(this.formatearTiempo(this.audio.duration));
        },

        cambiarPosicion: function(e) {
            if (!this.audio || !this.audio.duration) return;

            var porcentaje = parseFloat($(e.target).val());
            this.audio.currentTime = (porcentaje / 100) * this.audio.duration;
        },

        cambiarVolumen: function(e) {
            if (!this.audio) return;

            var volumen = parseFloat($(e.target).val()) / 100;
            this.audio.volume = volumen;
        },

        cerrarPlayer: function() {
            if (this.audio) {
                this.audio.pause();
                this.audio.currentTime = 0;
            }
            $('#flavor-player-flotante').hide();
        },

        formatearTiempo: function(segundos) {
            segundos = Math.floor(segundos);
            var minutos = Math.floor(segundos / 60);
            var segs = segundos % 60;
            return minutos + ':' + (segs < 10 ? '0' : '') + segs;
        },

        registrarReproduccion: function(episodioId, duracion, completado) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_podcast_registrar_reproduccion',
                    episodio_id: episodioId,
                    duracion: duracion,
                    completado: completado ? 1 : 0
                }
            });
        },

        suscribir: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var serieId = $btn.data('serie-id');
            var btnTexto = $btn.html();

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_podcast_suscribir',
                    nonce: this.config.nonce,
                    serie_id: serieId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorPodcast.showNotice(response.data.message, 'success');

                        if (response.data.suscrito) {
                            $btn.removeClass('flavor-btn-primary').addClass('flavor-btn-outline flavor-suscrito')
                                .html('<span class="dashicons dashicons-yes"></span> Suscrito');
                        } else {
                            $btn.removeClass('flavor-btn-outline flavor-suscrito').addClass('flavor-btn-primary')
                                .html('<span class="dashicons dashicons-heart"></span> Suscribirse');
                        }
                    } else {
                        FlavorPodcast.showNotice(response.data.message || FlavorPodcast.config.strings.error, 'error');
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    FlavorPodcast.showNotice(FlavorPodcast.config.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        darLike: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var episodioId = $btn.data('episodio-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_podcast_like',
                    nonce: this.config.nonce,
                    episodio_id: episodioId
                },
                success: function(response) {
                    if (response.success) {
                        $btn.toggleClass('liked', response.data.liked);
                        if (response.data.liked) {
                            $btn.find('.dashicons').removeClass('dashicons-heart').addClass('dashicons-heart-filled');
                        } else {
                            $btn.find('.dashicons').removeClass('dashicons-heart-filled').addClass('dashicons-heart');
                        }
                    }
                }
            });
        },

        buscar: function(e) {
            var termino = $(e.target).val();

            if (termino.length < 2) {
                $('.flavor-serie-card').show();
                return;
            }

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_podcast_buscar',
                    termino: termino
                },
                success: function(response) {
                    if (response.success && response.data.series) {
                        var ids = response.data.series.map(function(s) { return s.id; });
                        $('.flavor-serie-card').each(function() {
                            var $card = $(this);
                            // Simplificado - mostrar si coincide término en título
                            var titulo = $card.find('h3').text().toLowerCase();
                            if (titulo.indexOf(termino.toLowerCase()) >= 0) {
                                $card.show();
                            } else {
                                $card.hide();
                            }
                        });
                    }
                }
            });
        },

        filtrar: function(e) {
            var categoria = $(e.target).val();

            if (!categoria) {
                $('.flavor-serie-card').show();
            } else {
                $('.flavor-serie-card').hide();
                $('.flavor-serie-card[data-categoria="' + categoria + '"]').show();
            }
        },

        abrirFormSerie: function() {
            // TODO: Implementar modal de crear serie
            alert('Función de crear serie - pendiente de implementar modal');
        },

        abrirFormEpisodio: function(e) {
            var serieId = $(e.currentTarget).data('serie-id');
            // TODO: Implementar modal de subir episodio
            alert('Función de subir episodio a serie ' + serieId + ' - pendiente de implementar modal');
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="flavor-notice flavor-notice-' + type + '">' + message + '</div>');
            $('body').append($notice);

            setTimeout(function() {
                $notice.addClass('show');
            }, 10);

            setTimeout(function() {
                $notice.removeClass('show');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        FlavorPodcast.init();
    });

    // CSS para notificaciones
    var estilos = document.createElement('style');
    estilos.textContent = `
        .flavor-notice {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            z-index: 10001;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
        }
        .flavor-notice.show {
            opacity: 1;
            transform: translateX(0);
        }
        .flavor-notice-success {
            background: #22c55e;
        }
        .flavor-notice-error {
            background: #ef4444;
        }
        .flavor-btn-like.liked .dashicons-heart-filled {
            color: #ef4444;
        }
    `;
    document.head.appendChild(estilos);

})(jQuery);
