/**
 * JavaScript del modulo Comunidades
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var COM = {
        config: window.flavorComunidadesConfig || {},

        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initUploadArea();
            this.initFilters();
            this.loadFeed();
        },

        bindEvents: function() {
            // Unirse a comunidad
            $(document).on('click', '.flavor-com-btn-unirse', this.handleUnirse.bind(this));

            // Salir de comunidad
            $(document).on('click', '.flavor-com-btn-salir', this.handleSalir.bind(this));

            // Crear comunidad
            $(document).on('submit', '#flavor-com-form-crear', this.handleCrear.bind(this));

            // Publicar en comunidad
            $(document).on('submit', '#flavor-com-form-publicar', this.handlePublicar.bind(this));

            // Like en actividad
            $(document).on('click', '.flavor-com-btn-like', this.handleLike.bind(this));
        },

        handleUnirse: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var comunidadId = $btn.data('comunidad-id');

            if (!confirm(this.config.strings.confirmUnirse)) {
                return;
            }

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_unirse',
                    nonce: this.config.nonce,
                    comunidad_id: comunidadId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        COM.showMessage('error', response.data.message || COM.config.strings.error);
                        COM.setLoading($btn, false);
                    }
                },
                error: function() {
                    COM.showMessage('error', COM.config.strings.error);
                    COM.setLoading($btn, false);
                }
            });
        },

        handleSalir: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var comunidadId = $btn.data('comunidad-id');

            if (!confirm(this.config.strings.confirmSalir)) {
                return;
            }

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_salir',
                    nonce: this.config.nonce,
                    comunidad_id: comunidadId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect || '/comunidades/';
                    } else {
                        COM.showMessage('error', response.data.message || COM.config.strings.error);
                        COM.setLoading($btn, false);
                    }
                },
                error: function() {
                    COM.showMessage('error', COM.config.strings.error);
                    COM.setLoading($btn, false);
                }
            });
        },

        handleCrear: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            this.setLoading($btn, true);

            var formData = new FormData($form[0]);
            formData.append('action', 'comunidades_crear');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        COM.showFormMessage($form, 'exito', response.data.message || 'Comunidad creada correctamente');
                        setTimeout(function() {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            }
                        }, 1500);
                    } else {
                        COM.showFormMessage($form, 'error', response.data.message || COM.config.strings.error);
                        COM.setLoading($btn, false);
                    }
                },
                error: function() {
                    COM.showFormMessage($form, 'error', COM.config.strings.error);
                    COM.setLoading($btn, false);
                }
            });
        },

        handlePublicar: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');
            var $textarea = $form.find('textarea[name="contenido"]');
            var contenido = $textarea.val().trim();

            if (!contenido) {
                return;
            }

            this.setLoading($btn, true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_publicar',
                    nonce: this.config.nonce,
                    comunidad_id: $form.find('input[name="comunidad_id"]').val(),
                    contenido: contenido
                },
                success: function(response) {
                    if (response.success) {
                        $textarea.val('');
                        COM.loadFeed();
                    } else {
                        COM.showMessage('error', response.data.message || COM.config.strings.error);
                    }
                },
                error: function() {
                    COM.showMessage('error', COM.config.strings.error);
                },
                complete: function() {
                    COM.setLoading($btn, false);
                }
            });
        },

        handleLike: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var actividadId = $btn.data('actividad-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_like',
                    nonce: this.config.nonce,
                    actividad_id: actividadId
                },
                success: function(response) {
                    if (response.success) {
                        var $count = $btn.find('.count');
                        var currentCount = parseInt($count.text()) || 0;
                        $count.text(response.data.liked ? currentCount + 1 : currentCount - 1);
                        $btn.toggleClass('liked', response.data.liked);
                    }
                }
            });
        },

        initTabs: function() {
            var $tabs = $('.flavor-com-tab');
            var $contents = $('.flavor-com-tab-content');

            $tabs.on('click', function() {
                var tabId = $(this).data('tab');

                $tabs.removeClass('active');
                $(this).addClass('active');

                $contents.removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });
        },

        initUploadArea: function() {
            var $uploadArea = $('#com-upload-area');
            var $input = $('#com-imagen');
            var $preview = $('.flavor-com-upload-preview');
            var $placeholder = $('.flavor-com-upload-placeholder');
            var $previewImg = $('#com-imagen-preview');

            if (!$uploadArea.length) return;

            $uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $uploadArea.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $uploadArea.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $input[0].files = files;
                    $input.trigger('change');
                }
            });

            $input.on('change', function() {
                var file = this.files[0];
                if (file) {
                    if (!file.type.match(/image\/(jpeg|png|webp)/)) {
                        alert('Solo se permiten imagenes JPG, PNG o WebP.');
                        this.value = '';
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen no puede superar los 2MB.');
                        this.value = '';
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $previewImg.attr('src', e.target.result);
                        $placeholder.hide();
                        $preview.show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('.flavor-com-btn-quitar-imagen').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $input.val('');
                $preview.hide();
                $placeholder.show();
            });
        },

        initFilters: function() {
            var $categoriaFilter = $('#flavor-com-filtro-categoria');
            var $tipoFilter = $('#flavor-com-filtro-tipo');
            var $buscarInput = $('#flavor-com-buscar');

            $categoriaFilter.on('change', function() {
                COM.filterCommunities();
            });

            $tipoFilter.on('change', function() {
                COM.filterCommunities();
            });

            var searchTimeout;
            $buscarInput.on('input', function() {
                clearTimeout(searchTimeout);
                var query = $(this).val().toLowerCase();
                searchTimeout = setTimeout(function() {
                    COM.searchCommunities(query);
                }, 300);
            });
        },

        filterCommunities: function() {
            var categoria = $('#flavor-com-filtro-categoria').val();
            var tipo = $('#flavor-com-filtro-tipo').val();
            var $cards = $('.flavor-com-card');

            $cards.each(function() {
                var $card = $(this);
                var show = true;

                if (categoria && $card.data('categoria') !== categoria) {
                    show = false;
                }

                // Tipo filtering would need data attribute on cards

                $card.toggle(show);
            });
        },

        searchCommunities: function(query) {
            var $cards = $('.flavor-com-card');

            if (!query) {
                $cards.show();
                return;
            }

            $cards.each(function() {
                var $card = $(this);
                var titulo = $card.find('.flavor-com-card-titulo').text().toLowerCase();
                var descripcion = $card.find('.flavor-com-card-descripcion').text().toLowerCase();

                var match = titulo.indexOf(query) !== -1 || descripcion.indexOf(query) !== -1;
                $card.toggle(match);
            });
        },

        loadFeed: function() {
            var $feed = $('#flavor-com-feed');
            if (!$feed.length) return;

            var $feedContainer = $feed.closest('.flavor-com-feed-contenedor');
            var comunidadId = $feedContainer.data('comunidad-id');

            if (!comunidadId) {
                // Get from URL if not in container
                var urlParams = new URLSearchParams(window.location.search);
                comunidadId = urlParams.get('comunidad');
            }

            if (!comunidadId) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_cargar_mas',
                    nonce: this.config.nonce,
                    comunidad_id: comunidadId,
                    offset: 0
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $feed.html(response.data.html);
                    } else {
                        $feed.html('<div class="flavor-com-feed-vacio"><p>No hay actividad reciente.</p></div>');
                    }
                },
                error: function() {
                    $feed.html('<div class="flavor-com-feed-vacio"><p>Error al cargar la actividad.</p></div>');
                }
            });
        },

        setLoading: function($btn, loading) {
            if (loading) {
                $btn.prop('disabled', true).addClass('loading');
                $btn.data('original-text', $btn.html());
                $btn.html('<span class="flavor-com-spinner"></span> ' + this.config.strings.cargando);
            } else {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.html($btn.data('original-text'));
            }
        },

        showMessage: function(tipo, mensaje) {
            var $mensaje = $('<div class="flavor-com-notice flavor-com-notice-' + (tipo === 'error' ? 'error' : 'info') + '">' + mensaje + '</div>');
            $('.flavor-com-contenedor, .flavor-com-detalle-contenedor').first().prepend($mensaje);

            setTimeout(function() {
                $mensaje.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        showFormMessage: function($form, tipo, mensaje) {
            var $mensajeEl = $form.find('#com-mensaje-resultado');
            $mensajeEl
                .removeClass('flavor-com-mensaje-oculto flavor-com-mensaje-exito flavor-com-mensaje-error')
                .addClass('flavor-com-mensaje-' + tipo)
                .text(mensaje)
                .show();
        }
    };

    $(document).ready(function() {
        COM.init();
    });

})(jQuery);
