/**
 * JavaScript Frontend para Recetas
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorRecetas = {
        config: window.flavorRecetasConfig || {},
        pasoNumero: 1,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Formulario crear receta
            $(document).on('submit', '#form-crear-receta', this.guardarReceta.bind(this));

            // Agregar/quitar ingredientes
            $(document).on('click', '#agregar-ingrediente', this.agregarIngrediente.bind(this));
            $(document).on('click', '.flavor-btn-quitar-ingrediente', this.quitarIngrediente.bind(this));

            // Agregar/quitar pasos
            $(document).on('click', '#agregar-paso', this.agregarPaso.bind(this));
            $(document).on('click', '.flavor-btn-quitar-paso', this.quitarPaso.bind(this));

            // Favoritos
            $(document).on('click', '.flavor-btn-favorito', this.toggleFavorito.bind(this));

            // Valoración
            $(document).on('click', '.flavor-valoracion .star', this.valorar.bind(this));
            $(document).on('mouseenter', '.flavor-valoracion .star', this.hoverStar.bind(this));
            $(document).on('mouseleave', '.flavor-valoracion', this.resetStars.bind(this));

            // Búsqueda
            $(document).on('submit', '#form-buscar-recetas', this.buscar.bind(this));

            // Preview imagen
            $(document).on('change', '#imagen', this.previewImagen.bind(this));
        },

        /**
         * Guardar receta
         */
        guardarReceta: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');
            var formData = new FormData($form[0]);

            formData.append('action', 'flavor_recetas_guardar');

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');

                        if (response.data.url) {
                            setTimeout(function() {
                                window.location.href = response.data.url;
                            }, 1500);
                        }
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
         * Agregar ingrediente
         */
        agregarIngrediente: function(e) {
            e.preventDefault();

            var html = '<div class="flavor-ingrediente-row">' +
                '<input type="text" name="ingredientes[]" placeholder="' + 'Ej: 2 tazas de harina' + '" class="flavor-input">' +
                '<button type="button" class="flavor-btn-icon flavor-btn-quitar-ingrediente">' +
                '<span class="dashicons dashicons-minus"></span></button></div>';

            $('#ingredientes-container').append(html);
        },

        /**
         * Quitar ingrediente
         */
        quitarIngrediente: function(e) {
            e.preventDefault();
            var $container = $('#ingredientes-container');

            if ($container.find('.flavor-ingrediente-row').length > 1) {
                $(e.currentTarget).closest('.flavor-ingrediente-row').remove();
            }
        },

        /**
         * Agregar paso
         */
        agregarPaso: function(e) {
            e.preventDefault();

            this.pasoNumero = $('#pasos-container .flavor-paso-row').length + 1;

            var html = '<div class="flavor-paso-row">' +
                '<span class="flavor-paso-numero">' + this.pasoNumero + '</span>' +
                '<textarea name="pasos[]" placeholder="' + 'Describe el paso...' + '" class="flavor-textarea" rows="2"></textarea>' +
                '<button type="button" class="flavor-btn-icon flavor-btn-quitar-paso">' +
                '<span class="dashicons dashicons-minus"></span></button></div>';

            $('#pasos-container').append(html);
        },

        /**
         * Quitar paso
         */
        quitarPaso: function(e) {
            e.preventDefault();
            var $container = $('#pasos-container');

            if ($container.find('.flavor-paso-row').length > 1) {
                $(e.currentTarget).closest('.flavor-paso-row').remove();
                this.renumerarPasos();
            }
        },

        /**
         * Renumerar pasos
         */
        renumerarPasos: function() {
            $('#pasos-container .flavor-paso-row').each(function(index) {
                $(this).find('.flavor-paso-numero').text(index + 1);
            });
        },

        /**
         * Toggle favorito
         */
        toggleFavorito: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var recetaId = $btn.data('receta-id');

            $btn.addClass('loading');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_recetas_favorito',
                    nonce: this.config.nonce,
                    receta_id: recetaId
                },
                success: function(response) {
                    if (response.success) {
                        $btn.toggleClass('activo', response.data.es_favorito);
                        self.showToast(response.data.message, 'success');
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading');
                }
            });
        },

        /**
         * Valorar receta
         */
        valorar: function(e) {
            e.preventDefault();

            var self = this;
            var $star = $(e.currentTarget);
            var $container = $star.closest('.flavor-valoracion');
            var recetaId = $container.data('receta-id');
            var valoracion = $star.data('value');

            // Marcar estrellas
            $container.find('.star').each(function(index) {
                $(this).toggleClass('active', index < valoracion);
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_recetas_valorar',
                    nonce: this.config.nonce,
                    receta_id: recetaId,
                    valoracion: valoracion
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');

                        // Actualizar promedio si se muestra
                        if (response.data.promedio) {
                            $('.flavor-valoracion-promedio[data-receta-id="' + recetaId + '"]')
                                .text(response.data.promedio);
                        }
                    }
                }
            });
        },

        /**
         * Hover en estrella
         */
        hoverStar: function(e) {
            var $star = $(e.currentTarget);
            var value = $star.data('value');

            $star.closest('.flavor-valoracion').find('.star').each(function(index) {
                $(this).toggleClass('hover', index < value);
            });
        },

        /**
         * Reset estrellas
         */
        resetStars: function(e) {
            $(e.currentTarget).find('.star').removeClass('hover');
        },

        /**
         * Buscar recetas
         */
        buscar: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $resultados = $('#resultados-recetas');
            var $btn = $form.find('button[type="submit"]');

            $btn.addClass('loading').prop('disabled', true);
            $resultados.html('<p class="flavor-loading">' + this.config.strings.buscando + '</p>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_recetas_buscar',
                    nonce: this.config.nonce,
                    busqueda: $form.find('[name="busqueda"]').val(),
                    categoria: $form.find('[name="categoria"]').val(),
                    dificultad: $form.find('[name="dificultad"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.renderResultados(response.data.recetas);
                    } else {
                        $resultados.html('<p class="flavor-notice">' + self.config.strings.error + '</p>');
                    }
                },
                error: function() {
                    $resultados.html('<p class="flavor-notice">' + self.config.strings.error + '</p>');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Renderizar resultados de búsqueda
         */
        renderResultados: function(recetas) {
            var $resultados = $('#resultados-recetas');

            if (!recetas || recetas.length === 0) {
                $resultados.html('<p class="flavor-notice">' + this.config.strings.sin_resultados + '</p>');
                return;
            }

            var html = '<div class="flavor-recetas-grid">';

            recetas.forEach(function(receta) {
                html += '<article class="flavor-receta-card" data-id="' + receta.id + '">';
                html += '<div class="flavor-receta-imagen">';

                if (receta.imagen) {
                    html += '<img src="' + receta.imagen + '" alt="' + receta.titulo + '">';
                } else {
                    html += '<div class="flavor-receta-placeholder"><span class="dashicons dashicons-carrot"></span></div>';
                }

                html += '</div>';
                html += '<div class="flavor-receta-content">';
                html += '<h4><a href="' + receta.url + '">' + receta.titulo + '</a></h4>';
                html += '<div class="flavor-receta-meta">';

                if (receta.tiempo) {
                    html += '<span><span class="dashicons dashicons-clock"></span> ' + receta.tiempo + ' min</span>';
                }

                html += '</div></div></article>';
            });

            html += '</div>';
            $resultados.html(html);
        },

        /**
         * Preview de imagen
         */
        previewImagen: function(e) {
            var file = e.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function(event) {
                var $preview = $('#preview-imagen');
                if (!$preview.length) {
                    $preview = $('<div id="preview-imagen" style="margin-top: 0.5rem;"></div>');
                    $(e.target).after($preview);
                }
                $preview.html('<img src="' + event.target.result + '" style="max-width: 200px; border-radius: 8px;">');
            };
            reader.readAsDataURL(file);
        },

        /**
         * Mostrar toast
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
        FlavorRecetas.init();
    });

    // Estilos dinámicos
    var estilos = document.createElement('style');
    estilos.textContent = `
        .flavor-toast.fade-out { opacity: 0; transform: translateX(100px); transition: all 0.3s ease; }
        .flavor-btn.loading, button.loading { pointer-events: none; opacity: 0.7; }
        .flavor-valoracion .star.hover { color: #fbbf24 !important; }
        .flavor-loading { text-align: center; padding: 2rem; color: #6b7280; }
    `;
    document.head.appendChild(estilos);

})(jQuery);
