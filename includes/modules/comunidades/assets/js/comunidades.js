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
                        $btn.toggleClass('liked', response.data.liked);
                        $btn.attr('data-liked', response.data.liked ? '1' : '0');
                        $count.text(response.data.likes ?? 0);
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
                    action: 'comunidades_cargar_actividad',
                    nonce: this.config.nonce,
                    comunidad_id: comunidadId,
                    limite: 10,
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

    // ========================================
    // Centro de Notificaciones
    // ========================================
    var NOTIFICACIONES = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Filtrar por tipo
            $(document).on('click', '.flavor-com-notificaciones-tabs button', this.handleFiltrar.bind(this));

            // Marcar como leída
            $(document).on('click', '.flavor-com-notificacion-marcar-leida', this.handleMarcarLeida.bind(this));

            // Eliminar notificación
            $(document).on('click', '.flavor-com-notificacion-eliminar', this.handleEliminar.bind(this));

            // Marcar todas como leídas
            $(document).on('click', '#com-marcar-todas-leidas', this.handleMarcarTodasLeidas.bind(this));

            // Abrir/cerrar preferencias
            $(document).on('click', '#com-abrir-preferencias', this.handleAbrirPreferencias.bind(this));
            $(document).on('click', '#com-cerrar-preferencias', this.handleCerrarPreferencias.bind(this));

            // Guardar preferencias
            $(document).on('click', '#com-guardar-preferencias', this.handleGuardarPreferencias.bind(this));
        },

        handleFiltrar: function(e) {
            var $btn = $(e.currentTarget);
            var tipo = $btn.data('tipo');

            $('.flavor-com-notificaciones-tabs button').removeClass('active');
            $btn.addClass('active');

            var $notificaciones = $('.flavor-com-notificacion');

            if (tipo === 'todas') {
                $notificaciones.show();
            } else if (tipo === 'no-leidas') {
                $notificaciones.hide().filter('.no-leida').show();
            } else {
                $notificaciones.hide().filter('[data-tipo="' + tipo + '"]').show();
            }
        },

        handleMarcarLeida: function(e) {
            var $btn = $(e.currentTarget);
            var $notif = $btn.closest('.flavor-com-notificacion');
            var notifId = $notif.data('id');

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_marcar_notificacion_leida',
                    nonce: COM.config.nonce,
                    notificacion_id: notifId
                },
                success: function(response) {
                    if (response.success) {
                        $notif.removeClass('no-leida');
                    }
                }
            });
        },

        handleEliminar: function(e) {
            var $btn = $(e.currentTarget);
            var $notif = $btn.closest('.flavor-com-notificacion');
            var notifId = $notif.data('id');

            if (!confirm('¿Eliminar esta notificación?')) return;

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_eliminar_notificacion',
                    nonce: COM.config.nonce,
                    notificacion_id: notifId
                },
                success: function(response) {
                    if (response.success) {
                        $notif.fadeOut(300, function() { $(this).remove(); });
                    }
                }
            });
        },

        handleMarcarTodasLeidas: function(e) {
            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_marcar_todas_leidas',
                    nonce: COM.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.flavor-com-notificacion').removeClass('no-leida');
                    }
                }
            });
        },

        handleAbrirPreferencias: function(e) {
            $('#com-modal-preferencias').removeClass('flavor-com-modal-hidden');
        },

        handleCerrarPreferencias: function(e) {
            $('#com-modal-preferencias').addClass('flavor-com-modal-hidden');
        },

        handleGuardarPreferencias: function(e) {
            var preferencias = {};
            $('.flavor-com-preferencia-toggle').each(function() {
                preferencias[$(this).data('tipo')] = $(this).is(':checked');
            });

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_guardar_preferencias_notificaciones',
                    nonce: COM.config.nonce,
                    preferencias: JSON.stringify(preferencias)
                },
                success: function(response) {
                    if (response.success) {
                        NOTIFICACIONES.handleCerrarPreferencias();
                        COM.showMessage('info', 'Preferencias guardadas correctamente');
                    }
                }
            });
        }
    };

    // ========================================
    // Búsqueda Federada
    // ========================================
    var BUSQUEDA = {
        searchTimeout: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Buscar
            $(document).on('submit', '#com-form-busqueda', this.handleBuscar.bind(this));
            $(document).on('input', '#com-busqueda-input', this.handleBuscarDebounced.bind(this));

            // Filtrar por tipo
            $(document).on('click', '.flavor-com-chip', this.handleFiltrarTipo.bind(this));

            // Filtrar por origen
            $(document).on('click', '.flavor-com-filtro-origen button', this.handleFiltrarOrigen.bind(this));
        },

        handleBuscarDebounced: function(e) {
            var query = $(e.currentTarget).val();
            clearTimeout(this.searchTimeout);

            if (query.length < 2) return;

            this.searchTimeout = setTimeout(function() {
                BUSQUEDA.ejecutarBusqueda();
            }, 300);
        },

        handleBuscar: function(e) {
            e.preventDefault();
            this.ejecutarBusqueda();
        },

        handleFiltrarTipo: function(e) {
            var $chip = $(e.currentTarget);
            $chip.toggleClass('active');
            this.ejecutarBusqueda();
        },

        handleFiltrarOrigen: function(e) {
            var $btn = $(e.currentTarget);
            $('.flavor-com-filtro-origen button').removeClass('active');
            $btn.addClass('active');
            this.ejecutarBusqueda();
        },

        ejecutarBusqueda: function() {
            var query = $('#com-busqueda-input').val();
            var tipos = [];
            var origen = $('.flavor-com-filtro-origen button.active').data('origen') || 'todos';

            $('.flavor-com-chip.active').each(function() {
                tipos.push($(this).data('tipo'));
            });

            if (tipos.length === 0) {
                tipos = ['comunidades', 'publicaciones', 'eventos', 'recetas', 'biblioteca', 'multimedia'];
            }

            var $resultados = $('#com-busqueda-resultados');
            $resultados.html('<div class="flavor-com-cargando"><div class="flavor-com-spinner"></div> Buscando...</div>');

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_busqueda_federada',
                    nonce: COM.config.nonce,
                    query: query,
                    tipos: tipos,
                    origen: origen
                },
                success: function(response) {
                    if (response.success) {
                        BUSQUEDA.renderResultados(response.data.resultados);
                    } else {
                        $resultados.html('<div class="flavor-com-vacio"><p>Error al buscar</p></div>');
                    }
                },
                error: function() {
                    $resultados.html('<div class="flavor-com-vacio"><p>Error de conexión</p></div>');
                }
            });
        },

        renderResultados: function(resultados) {
            var $resultados = $('#com-busqueda-resultados');

            if (!resultados || resultados.length === 0) {
                $resultados.html('<div class="flavor-com-vacio"><span class="dashicons dashicons-search"></span><p>No se encontraron resultados</p></div>');
                return;
            }

            var html = '';
            var iconos = {
                'comunidad': '👥',
                'publicacion': '📝',
                'evento': '📅',
                'receta': '🍳',
                'biblioteca': '📚',
                'multimedia': '🎬'
            };

            for (var i = 0; i < resultados.length; i++) {
                var r = resultados[i];
                var icono = iconos[r.tipo] || '📄';
                var badgeClase = r.federado ? 'flavor-com-badge-federado' : 'flavor-com-badge-local';
                var badgeTexto = r.federado ? '🌐 ' + r.nodo : '📍 Local';

                html += '<div class="flavor-com-resultado-item">';
                html += '<div class="flavor-com-resultado-icono">' + icono + '</div>';
                html += '<div class="flavor-com-resultado-contenido">';
                html += '<h4 class="flavor-com-resultado-titulo"><a href="' + (r.url || '#') + '">' + r.titulo + '</a></h4>';
                if (r.descripcion) {
                    html += '<p class="flavor-com-resultado-descripcion">' + r.descripcion + '</p>';
                }
                html += '<div class="flavor-com-resultado-meta">';
                html += '<span class="' + badgeClase + '">' + badgeTexto + '</span>';
                if (r.fecha) {
                    html += '<span>📅 ' + r.fecha + '</span>';
                }
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }

            $resultados.html(html);
        }
    };

    // ========================================
    // Tablón de Anuncios
    // ========================================
    var TABLON = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Filtrar por categoría
            $(document).on('click', '.flavor-com-tablon-filtros .flavor-com-chip', this.handleFiltrarCategoria.bind(this));

            // Abrir modal crear anuncio
            $(document).on('click', '#com-btn-crear-anuncio', this.handleAbrirModal.bind(this));

            // Cerrar modal
            $(document).on('click', '#com-cerrar-modal-anuncio', this.handleCerrarModal.bind(this));
            $(document).on('click', '#com-modal-anuncio', function(e) {
                if ($(e.target).is('#com-modal-anuncio')) {
                    TABLON.handleCerrarModal();
                }
            });

            // Crear anuncio
            $(document).on('submit', '#com-form-anuncio', this.handleCrearAnuncio.bind(this));

            // Cargar más anuncios
            $(document).on('click', '#com-cargar-mas-anuncios', this.handleCargarMas.bind(this));
        },

        handleFiltrarCategoria: function(e) {
            var $chip = $(e.currentTarget);
            var categoria = $chip.data('categoria');

            $('.flavor-com-tablon-filtros .flavor-com-chip').removeClass('active');
            $chip.addClass('active');

            var $anuncios = $('.flavor-com-anuncio');

            if (categoria === 'todos') {
                $anuncios.show();
            } else {
                $anuncios.hide().filter('[data-categoria="' + categoria + '"]').show();
            }
        },

        handleAbrirModal: function(e) {
            $('#com-modal-anuncio').removeClass('flavor-com-modal-hidden');
            this.cargarMisComunidades();
        },

        handleCerrarModal: function() {
            $('#com-modal-anuncio').addClass('flavor-com-modal-hidden');
        },

        cargarMisComunidades: function() {
            var $select = $('#com-anuncio-comunidad');
            if ($select.children().length > 1) return; // Ya cargado

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_mis_comunidades_admin',
                    nonce: COM.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data.comunidades) {
                        var comunidades = response.data.comunidades;
                        for (var i = 0; i < comunidades.length; i++) {
                            $select.append('<option value="' + comunidades[i].id + '">' + comunidades[i].nombre + '</option>');
                        }
                    }
                }
            });
        },

        handleCrearAnuncio: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            COM.setLoading($btn, true);

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_crear_anuncio',
                    nonce: COM.config.nonce,
                    comunidad_id: $('#com-anuncio-comunidad').val(),
                    titulo: $('#com-anuncio-titulo').val(),
                    contenido: $('#com-anuncio-contenido').val(),
                    categoria: $('#com-anuncio-categoria').val(),
                    destacado: $('#com-anuncio-destacado').is(':checked') ? 1 : 0,
                    compartir_red: $('#com-anuncio-compartir').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        TABLON.handleCerrarModal();
                        location.reload();
                    } else {
                        COM.showMessage('error', response.data.message || 'Error al crear anuncio');
                    }
                },
                error: function() {
                    COM.showMessage('error', 'Error de conexión');
                },
                complete: function() {
                    COM.setLoading($btn, false);
                }
            });
        },

        handleCargarMas: function(e) {
            var $btn = $(e.currentTarget);
            var offset = parseInt($btn.data('offset')) || 0;
            var categoria = $('.flavor-com-tablon-filtros .flavor-com-chip.active').data('categoria') || 'todos';

            COM.setLoading($btn, true);

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_obtener_anuncios',
                    nonce: COM.config.nonce,
                    offset: offset,
                    limite: 10,
                    categoria: categoria === 'todos' ? '' : categoria
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('#com-lista-anuncios').append(response.data.html);
                        $btn.data('offset', offset + 10);

                        if (!response.data.has_more) {
                            $btn.hide();
                        }
                    }
                },
                complete: function() {
                    COM.setLoading($btn, false);
                }
            });
        }
    };

    // ========================================
    // Métricas de Colaboración
    // ========================================
    var METRICAS = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Cambiar período
            $(document).on('click', '.flavor-com-periodo-selector button', this.handleCambiarPeriodo.bind(this));
        },

        handleCambiarPeriodo: function(e) {
            var $btn = $(e.currentTarget);
            var periodo = $btn.data('periodo');

            $('.flavor-com-periodo-selector button').removeClass('active');
            $btn.addClass('active');

            this.cargarMetricas(periodo);
        },

        cargarMetricas: function(periodo) {
            var $contenedor = $('.flavor-com-metricas');

            $.ajax({
                url: COM.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'comunidades_obtener_metricas',
                    nonce: COM.config.nonce,
                    periodo: periodo
                },
                success: function(response) {
                    if (response.success) {
                        METRICAS.actualizarDatos(response.data);
                    }
                }
            });
        },

        actualizarDatos: function(data) {
            // Actualizar tarjetas de métricas
            if (data.resumen) {
                var r = data.resumen;
                $('.flavor-com-metrica-card[data-metrica="comunidades"] .flavor-com-metrica-valor').text(r.comunidades_activas || 0);
                $('.flavor-com-metrica-card[data-metrica="colaboraciones"] .flavor-com-metrica-valor').text(r.total_colaboraciones || 0);
                $('.flavor-com-metrica-card[data-metrica="publicaciones"] .flavor-com-metrica-valor').text(r.publicaciones || 0);
                $('.flavor-com-metrica-card[data-metrica="federado"] .flavor-com-metrica-valor').text(r.contenido_federado || 0);
            }

            // Actualizar rankings
            if (data.top_comunidades) {
                var $ranking = $('#com-ranking-comunidades');
                $ranking.empty();

                for (var i = 0; i < data.top_comunidades.length && i < 5; i++) {
                    var c = data.top_comunidades[i];
                    $ranking.append(
                        '<div class="flavor-com-ranking-item">' +
                        '<span class="flavor-com-ranking-posicion">' + (i + 1) + '</span>' +
                        '<span class="flavor-com-ranking-nombre">' + c.nombre + '</span>' +
                        '<span class="flavor-com-ranking-valor">' + c.actividad + ' actividades</span>' +
                        '</div>'
                    );
                }
            }

            // Actualizar timeline
            if (data.actividad_reciente) {
                var $timeline = $('#com-timeline-actividad');
                $timeline.empty();

                for (var j = 0; j < data.actividad_reciente.length && j < 5; j++) {
                    var a = data.actividad_reciente[j];
                    $timeline.append(
                        '<div class="flavor-com-timeline-item">' +
                        '<span class="flavor-com-timeline-fecha">' + a.fecha + '</span>' +
                        '<p class="flavor-com-timeline-texto">' + a.descripcion + '</p>' +
                        '</div>'
                    );
                }
            }
        }
    };

    $(document).ready(function() {
        COM.init();
        NOTIFICACIONES.init();
        BUSQUEDA.init();
        TABLON.init();
        METRICAS.init();
    });

})(jQuery);
