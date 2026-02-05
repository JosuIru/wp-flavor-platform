/**
 * Network Frontend JavaScript
 * Red de Comunidades - Shortcodes públicos
 */
(function($) {
    'use strict';

    if (typeof flavorNetwork === 'undefined') return;

    var API_URL = flavorNetwork.apiUrl;
    var I18N = flavorNetwork.i18n;

    function apiGet(endpoint, params) {
        return $.ajax({
            url: API_URL + endpoint,
            method: 'GET',
            data: params || {},
        });
    }

    function escapeHtml(texto) {
        if (!texto) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(texto));
        return div.innerHTML;
    }

    function truncar(texto, longitud) {
        if (!texto) return '';
        return texto.length > longitud ? texto.substring(0, longitud) + '...' : texto;
    }

    // ─── Directory Widget ───
    function initDirectoryWidget($widget) {
        var paginaActual = 1;

        function cargar(pagina) {
            paginaActual = pagina || 1;
            var $grid = $widget.find('.fn-directory-results');
            $grid.html('<div class="fn-loading">' + I18N.cargando + '</div>');

            apiGet('/directory', {
                busqueda: $widget.find('.fn-search-input').val() || '',
                tipo: $widget.find('.fn-filter-tipo').val() || '',
                nivel: $widget.find('.fn-filter-nivel').val() || '',
                pagina: paginaActual,
                por_pagina: $widget.data('limite') || 20,
            })
            .done(function(data) {
                $grid.empty();

                if (!data.nodos.length) {
                    $grid.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                    return;
                }

                var htmlGrid = '<div class="fn-directory-grid">';
                data.nodos.forEach(function(nodo) {
                    var inicial = (nodo.nombre || '?').charAt(0).toUpperCase();
                    var logo = nodo.logo_url
                        ? '<img src="' + nodo.logo_url + '" class="fn-node-logo" alt="">'
                        : '<div class="fn-node-logo-placeholder">' + inicial + '</div>';

                    htmlGrid += '<div class="fn-node-card">' +
                        '<div class="fn-node-header">' + logo +
                        '<div><h4 class="fn-node-name">' + escapeHtml(nodo.nombre) + '</h4>' +
                        '<span class="fn-node-type">' + escapeHtml(nodo.tipo_entidad) + '</span></div></div>' +
                        '<p class="fn-node-desc">' + escapeHtml(nodo.descripcion_corta || '') + '</p>' +
                        '<div class="fn-node-tags">' +
                        (nodo.ciudad ? '<span class="fn-badge fn-badge-location">' + escapeHtml(nodo.ciudad) + '</span>' : '') +
                        '<span class="fn-badge fn-badge-' + nodo.nivel_consciencia + '">' + escapeHtml(nodo.nivel_consciencia) + '</span>' +
                        (nodo.verificado ? '<span class="fn-badge fn-badge-verified">Verificado</span>' : '') +
                        '</div></div>';
                });
                htmlGrid += '</div>';
                $grid.html(htmlGrid);

                // Pagination
                if (data.paginas > 1) {
                    var htmlPag = '<div class="fn-pagination">';
                    for (var i = 1; i <= data.paginas; i++) {
                        htmlPag += '<button class="fn-page-btn' + (i === paginaActual ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
                    }
                    htmlPag += '</div>';
                    $grid.append(htmlPag);
                }
            })
            .fail(function() {
                $grid.html('<p style="text-align:center;color:#ef4444;">' + I18N.error + '</p>');
            });
        }

        $widget.on('click', '.fn-search-btn', function() { cargar(1); });
        $widget.on('click', '.fn-page-btn', function() { cargar($(this).data('page')); });
        $widget.on('keypress', '.fn-search-input', function(e) {
            if (e.which === 13) cargar(1);
        });

        cargar(1);
    }

    // ─── Board Widget ───
    function initBoardWidget($widget) {
        apiGet('/board', { por_pagina: $widget.data('limite') || 15 })
            .done(function(data) {
                var $lista = $widget.find('.fn-board-results');
                $lista.empty();

                if (!data.publicaciones.length) {
                    $lista.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                    return;
                }

                data.publicaciones.forEach(function(pub) {
                    $lista.append(
                        '<div class="fn-board-item">' +
                        '<div class="fn-board-header">' +
                        '<h4 class="fn-board-title">' + escapeHtml(pub.titulo) + '</h4>' +
                        '<span class="fn-board-type">' + escapeHtml(pub.tipo) + '</span></div>' +
                        '<div class="fn-board-content">' + escapeHtml(truncar(pub.contenido, 300)) + '</div>' +
                        '<div class="fn-board-meta">' +
                        (pub.nodo_nombre ? escapeHtml(pub.nodo_nombre) + ' · ' : '') +
                        pub.fecha_publicacion +
                        '</div></div>'
                    );
                });
            });
    }

    // ─── Events Widget ───
    function initEventsWidget($widget) {
        var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

        apiGet('/events', { por_pagina: $widget.data('limite') || 10 })
            .done(function(data) {
                var $lista = $widget.find('.fn-events-results');
                $lista.empty();

                if (!data.eventos.length) {
                    $lista.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                    return;
                }

                data.eventos.forEach(function(evento) {
                    var fecha = new Date(evento.fecha_inicio);
                    $lista.append(
                        '<div class="fn-event-item">' +
                        '<div class="fn-event-date"><span class="fn-event-day">' + fecha.getDate() + '</span>' +
                        '<span class="fn-event-month">' + meses[fecha.getMonth()] + '</span></div>' +
                        '<div class="fn-event-info"><h4>' + escapeHtml(evento.titulo) + '</h4>' +
                        '<p class="fn-event-desc">' + escapeHtml(truncar(evento.descripcion || '', 200)) + '</p>' +
                        '<div class="fn-event-meta">' +
                        '<span>' + escapeHtml(evento.tipo_evento) + '</span>' +
                        (evento.ubicacion ? '<span>' + escapeHtml(evento.ubicacion) + '</span>' : '') +
                        (evento.nodo_nombre ? '<span>' + escapeHtml(evento.nodo_nombre) + '</span>' : '') +
                        '</div></div></div>'
                    );
                });
            });
    }

    // ─── Alerts Widget ───
    function initAlertsWidget($widget) {
        apiGet('/alerts')
            .done(function(data) {
                var $lista = $widget.find('.fn-alerts-results');
                $lista.empty();

                if (!data.alertas.length) {
                    $lista.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                    return;
                }

                data.alertas.forEach(function(alerta) {
                    $lista.append(
                        '<div class="fn-alert-item urgencia-' + alerta.urgencia + '">' +
                        '<span class="fn-urgencia-badge ' + alerta.urgencia + '">' + alerta.urgencia + '</span>' +
                        '<h4>' + escapeHtml(alerta.titulo) + '</h4>' +
                        '<p>' + escapeHtml(truncar(alerta.descripcion || '', 200)) + '</p>' +
                        '<div class="fn-board-meta">' +
                        (alerta.nodo_nombre ? escapeHtml(alerta.nodo_nombre) : '') +
                        (alerta.ubicacion ? ' · ' + escapeHtml(alerta.ubicacion) : '') +
                        '</div></div>'
                    );
                });
            });
    }

    // ─── Catalog Widget ───
    function initCatalogWidget($widget) {
        var slug = $widget.data('nodo');
        var endpoint = slug ? '/catalog/' + slug : '/content';
        var params = {};
        var tipo = $widget.data('tipo');
        if (tipo) params.tipo = tipo;

        apiGet(endpoint, params)
            .done(function(data) {
                var $grid = $widget.find('.fn-catalog-results');
                $grid.empty();

                var items = data.contenidos || [];
                if (!items.length) {
                    $grid.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                    return;
                }

                var html = '<div class="fn-catalog-grid">';
                items.forEach(function(item) {
                    var precio = item.precio > 0 ? item.precio + ' ' + (item.moneda || 'EUR') : 'Gratuito';
                    html += '<div class="fn-catalog-item">';
                    if (item.imagen_url) {
                        html += '<img src="' + item.imagen_url + '" class="fn-catalog-img" alt="">';
                    }
                    html += '<div class="fn-catalog-body">' +
                        '<span class="fn-catalog-type">' + escapeHtml(item.tipo_contenido) + '</span>' +
                        '<h4 class="fn-catalog-title">' + escapeHtml(item.titulo) + '</h4>' +
                        '<p class="fn-node-desc">' + escapeHtml(truncar(item.descripcion || '', 100)) + '</p>' +
                        '<span class="fn-catalog-price">' + precio + '</span>' +
                        '</div></div>';
                });
                html += '</div>';
                $grid.html(html);
            });
    }

    // ─── Collaborations Widget ───
    function initCollaborationsWidget($widget) {
        function cargar() {
            var parametros = {
                tipo: $widget.find('.fn-colab-tipo-filtro').val() || $widget.data('tipo') || '',
                por_pagina: $widget.data('limite') || 10,
            };

            var $lista = $widget.find('.fn-collaborations-results');
            $lista.html('<div class="fn-loading">' + I18N.cargando + '</div>');

            apiGet('/collaborations', parametros)
                .done(function(data) {
                    $lista.empty();

                    if (!data.colaboraciones.length) {
                        $lista.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                        return;
                    }

                    data.colaboraciones.forEach(function(colab) {
                        $lista.append(
                            '<div class="fn-colab-item">' +
                            '<span class="fn-colab-type">' + escapeHtml(colab.tipo) + '</span>' +
                            '<h4>' + escapeHtml(colab.titulo) + '</h4>' +
                            '<p>' + escapeHtml(truncar(colab.descripcion || '', 200)) + '</p>' +
                            '<div class="fn-board-meta">' +
                            (colab.nodo_nombre ? escapeHtml(colab.nodo_nombre) : '') +
                            ' · ' + escapeHtml(colab.estado || '') +
                            (colab.fecha_limite ? ' · ' + colab.fecha_limite : '') +
                            '</div></div>'
                        );
                    });
                })
                .fail(function() {
                    $lista.html('<p style="text-align:center;color:#ef4444;">' + I18N.error + '</p>');
                });
        }

        $widget.on('change', '.fn-colab-tipo-filtro', function() { cargar(); });
        cargar();
    }

    // ─── Time Offers Widget ───
    function initTimeOffersWidget($widget) {
        function cargar() {
            var parametros = {
                tipo: $widget.find('.fn-tiempo-tipo-filtro').val() || $widget.data('tipo') || '',
                por_pagina: $widget.data('limite') || 10,
            };

            var $lista = $widget.find('.fn-time-offers-results');
            $lista.html('<div class="fn-loading">' + I18N.cargando + '</div>');

            apiGet('/time-offers', parametros)
                .done(function(data) {
                    $lista.empty();

                    if (!data.ofertas.length) {
                        $lista.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                        return;
                    }

                    data.ofertas.forEach(function(oferta) {
                        $lista.append(
                            '<div class="fn-time-offer-item">' +
                            '<span class="fn-colab-type">' + escapeHtml(oferta.tipo) + '</span>' +
                            (oferta.categoria ? '<span class="fn-catalog-type">' + escapeHtml(oferta.categoria) + '</span> ' : '') +
                            '<h4>' + escapeHtml(oferta.titulo) + '</h4>' +
                            '<p>' + escapeHtml(truncar(oferta.descripcion || '', 200)) + '</p>' +
                            '<div class="fn-board-meta">' +
                            (oferta.horas_estimadas ? oferta.horas_estimadas + 'h' : '') +
                            ' · ' + escapeHtml(oferta.modalidad || 'presencial') +
                            (oferta.nodo_nombre ? ' · ' + escapeHtml(oferta.nodo_nombre) : '') +
                            '</div></div>'
                        );
                    });
                })
                .fail(function() {
                    $lista.html('<p style="text-align:center;color:#ef4444;">' + I18N.error + '</p>');
                });
        }

        $widget.on('change', '.fn-tiempo-tipo-filtro', function() { cargar(); });
        cargar();
    }

    // ─── Node Profile Widget ───
    function initNodeProfileWidget($widget) {
        var slug = $widget.data('slug');
        if (!slug) {
            $widget.find('.fn-node-profile-content').html('<p>' + I18N.sin_resultados + '</p>');
            return;
        }

        apiGet('/node/' + slug)
            .done(function(nodo) {
                var $contenido = $widget.find('.fn-node-profile-content');
                $contenido.empty();

                var inicial = (nodo.nombre || '?').charAt(0).toUpperCase();
                var logo = nodo.logo_url
                    ? '<img src="' + nodo.logo_url + '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;" alt="">'
                    : '<div style="width:80px;height:80px;border-radius:50%;background:#6366f1;color:#fff;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:bold;">' + inicial + '</div>';

                var tagsHtml = '';
                if (nodo.tags) {
                    var tags = typeof nodo.tags === 'string' ? JSON.parse(nodo.tags || '[]') : (nodo.tags || []);
                    tags.forEach(function(tag) {
                        tagsHtml += '<span class="fn-badge fn-badge-location">' + escapeHtml(tag) + '</span> ';
                    });
                }

                var html = '<div class="fn-node-profile" style="max-width:700px;">' +
                    '<div style="display:flex;gap:20px;align-items:center;margin-bottom:20px;">' +
                    logo +
                    '<div>' +
                    '<h2 style="margin:0 0 4px;">' + escapeHtml(nodo.nombre) + '</h2>' +
                    '<span class="fn-node-type" style="font-size:14px;">' + escapeHtml(nodo.tipo_entidad || '') + '</span>' +
                    '<span class="fn-badge fn-badge-' + (nodo.nivel_consciencia || 'basico') + '" style="margin-left:8px;">' + escapeHtml(nodo.nivel_consciencia || '') + '</span>' +
                    (nodo.verificado ? '<span class="fn-badge fn-badge-verified" style="margin-left:8px;">Verificado</span>' : '') +
                    '</div></div>';

                if (nodo.descripcion_corta) {
                    html += '<p style="font-size:16px;color:#4b5563;margin-bottom:16px;">' + escapeHtml(nodo.descripcion_corta) + '</p>';
                }

                if (nodo.descripcion) {
                    html += '<div style="margin-bottom:16px;">' + escapeHtml(nodo.descripcion) + '</div>';
                }

                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">';

                if (nodo.direccion || nodo.ciudad || nodo.provincia) {
                    html += '<div><strong>Ubicación:</strong><br>' +
                        escapeHtml([nodo.direccion, nodo.ciudad, nodo.provincia, nodo.pais].filter(Boolean).join(', ')) +
                        '</div>';
                }

                if (nodo.sector) {
                    html += '<div><strong>Sector:</strong><br>' + escapeHtml(nodo.sector) + '</div>';
                }

                if (nodo.email) {
                    html += '<div><strong>Email:</strong><br>' + escapeHtml(nodo.email) + '</div>';
                }

                if (nodo.web) {
                    html += '<div><strong>Web:</strong><br><a href="' + escapeHtml(nodo.web) + '" target="_blank">' + escapeHtml(nodo.web) + '</a></div>';
                }

                if (nodo.telefono) {
                    html += '<div><strong>Teléfono:</strong><br>' + escapeHtml(nodo.telefono) + '</div>';
                }

                html += '</div>';

                if (tagsHtml) {
                    html += '<div style="margin-top:12px;"><strong>Tags:</strong><br>' + tagsHtml + '</div>';
                }

                html += '</div>';

                $contenido.html(html);
            })
            .fail(function() {
                $widget.find('.fn-node-profile-content').html('<p>' + I18N.error + '</p>');
            });
    }

    // ─── Questions Widget ───
    function initQuestionsWidget($widget) {
        var paginaActual = 1;

        function cargar(pagina) {
            paginaActual = pagina || 1;
            var $resultados = $widget.find('.fn-questions-results');
            $resultados.html('<div class="fn-loading">' + I18N.cargando + '</div>');

            var categoriaInicial = $widget.data('categoria') || '';
            var parametros = {
                categoria: $widget.find('.fn-questions-categoria-filtro').val() || categoriaInicial,
                estado: $widget.find('.fn-questions-estado-filtro').val() || '',
                busqueda: $widget.find('.fn-questions-busqueda').val() || '',
                pagina: paginaActual,
                por_pagina: $widget.data('limite') || 10,
            };

            apiGet('/questions', parametros)
                .done(function(data) {
                    $resultados.empty();
                    $widget.find('.fn-question-detail').hide();

                    if (!data.preguntas.length) {
                        $resultados.html('<p style="text-align:center;color:#9ca3af;">' + I18N.sin_resultados + '</p>');
                        return;
                    }

                    data.preguntas.forEach(function(pregunta) {
                        var badgeEstado = '';
                        if (pregunta.estado === 'abierta') {
                            badgeEstado = '<span class="fn-badge fn-question-badge-abierta">Abierta</span>';
                        } else if (pregunta.estado === 'respondida') {
                            badgeEstado = '<span class="fn-badge fn-question-badge-respondida">Respondida</span>';
                        } else if (pregunta.estado === 'cerrada') {
                            badgeEstado = '<span class="fn-badge fn-question-badge-cerrada">Cerrada</span>';
                        }

                        var badgeCategoria = '<span class="fn-badge" style="background:#ede9fe;color:#5b21b6;">' + escapeHtml(pregunta.categoria) + '</span>';

                        var htmlPregunta = '<div class="fn-question-item" data-pregunta-id="' + pregunta.id + '">' +
                            '<h4 class="fn-question-title">' + escapeHtml(pregunta.titulo) + '</h4>' +
                            '<p style="margin:4px 0 10px;color:#4b5563;font-size:14px;line-height:1.5;">' + escapeHtml(truncar(pregunta.descripcion || '', 200)) + '</p>' +
                            '<div class="fn-question-meta">' +
                            badgeCategoria + ' ' + badgeEstado +
                            '</div>' +
                            '<div class="fn-question-stats">' +
                            (pregunta.nodo_nombre ? '<span>' + escapeHtml(pregunta.nodo_nombre) + '</span> · ' : '') +
                            '<span>' + pregunta.respuestas_count + ' respuestas</span> · ' +
                            '<span>' + pregunta.vistas + ' vistas</span> · ' +
                            '<span>' + pregunta.fecha_publicacion + '</span>' +
                            '</div></div>';

                        $resultados.append(htmlPregunta);
                    });

                    // Paginacion
                    if (data.paginas > 1) {
                        var htmlPag = '<div class="fn-pagination">';
                        for (var i = 1; i <= data.paginas; i++) {
                            htmlPag += '<button class="fn-page-btn' + (i === paginaActual ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
                        }
                        htmlPag += '</div>';
                        $resultados.append(htmlPag);
                    }
                })
                .fail(function() {
                    $resultados.html('<p style="text-align:center;color:#ef4444;">' + I18N.error + '</p>');
                });
        }

        function mostrarDetallePregunta(preguntaId) {
            var $detalle = $widget.find('.fn-question-detail');
            var $cabecera = $widget.find('.fn-question-detail-header');
            var $listaRespuestas = $widget.find('.fn-question-answers-list');

            $cabecera.html('<div class="fn-loading">' + I18N.cargando + '</div>');
            $listaRespuestas.empty();
            $detalle.show();

            apiGet('/questions/' + preguntaId)
                .done(function(pregunta) {
                    var badgeEstado = '';
                    if (pregunta.estado === 'abierta') {
                        badgeEstado = '<span class="fn-badge fn-question-badge-abierta">Abierta</span>';
                    } else if (pregunta.estado === 'respondida') {
                        badgeEstado = '<span class="fn-badge fn-question-badge-respondida">Respondida</span>';
                    } else if (pregunta.estado === 'cerrada') {
                        badgeEstado = '<span class="fn-badge fn-question-badge-cerrada">Cerrada</span>';
                    }

                    var htmlCabecera = '<div class="fn-question-item" style="border-left:4px solid #2271b1;">' +
                        '<button class="fn-question-back-btn" style="background:none;border:none;cursor:pointer;color:#2271b1;padding:0;margin-bottom:10px;font-size:14px;">← Volver a la lista</button>' +
                        '<h3 style="margin:0 0 8px;">' + escapeHtml(pregunta.titulo) + '</h3>' +
                        '<div style="margin-bottom:12px;color:#4b5563;line-height:1.6;">' + escapeHtml(pregunta.descripcion || '') + '</div>' +
                        '<div class="fn-question-meta">' +
                        '<span class="fn-badge" style="background:#ede9fe;color:#5b21b6;">' + escapeHtml(pregunta.categoria) + '</span> ' +
                        badgeEstado +
                        (pregunta.nodo_nombre ? ' · ' + escapeHtml(pregunta.nodo_nombre) : '') +
                        ' · ' + pregunta.vistas + ' vistas' +
                        ' · ' + pregunta.fecha_publicacion +
                        '</div></div>';

                    $cabecera.html(htmlCabecera);

                    // Respuestas
                    if (!pregunta.respuestas || !pregunta.respuestas.length) {
                        $listaRespuestas.html('<p style="text-align:center;color:#9ca3af;padding:20px;">No hay respuestas todavia.</p>');
                    } else {
                        $listaRespuestas.append('<h4 style="margin:15px 0 10px;">' + pregunta.respuestas.length + ' respuesta(s)</h4>');
                        pregunta.respuestas.forEach(function(respuesta) {
                            var claseSolucion = respuesta.es_solucion ? ' fn-answer-solution' : '';
                            var etiquetaSolucion = respuesta.es_solucion ? '<span class="fn-badge" style="background:#dcfce7;color:#166534;font-weight:600;">Solucion</span> ' : '';

                            var htmlRespuesta = '<div class="fn-answer-item' + claseSolucion + '">' +
                                etiquetaSolucion +
                                '<p style="margin:4px 0 8px;line-height:1.6;">' + escapeHtml(respuesta.contenido) + '</p>' +
                                '<div class="fn-board-meta">' +
                                (respuesta.nodo_nombre ? escapeHtml(respuesta.nodo_nombre) : '') +
                                ' · ' + respuesta.fecha_publicacion +
                                ' · +' + respuesta.votos_positivos + ' / -' + respuesta.votos_negativos +
                                '</div></div>';

                            $listaRespuestas.append(htmlRespuesta);
                        });
                    }
                })
                .fail(function() {
                    $cabecera.html('<p style="color:#ef4444;">' + I18N.error + '</p>');
                });
        }

        // Click en pregunta para ver detalle
        $widget.on('click', '.fn-question-item', function() {
            var preguntaId = $(this).data('pregunta-id');
            if (preguntaId) {
                $widget.find('.fn-questions-results').hide();
                mostrarDetallePregunta(preguntaId);
            }
        });

        // Volver a la lista
        $widget.on('click', '.fn-question-back-btn', function() {
            $widget.find('.fn-question-detail').hide();
            $widget.find('.fn-questions-results').show();
        });

        // Filtros
        $widget.on('click', '.fn-questions-buscar-btn', function() { cargar(1); });
        $widget.on('change', '.fn-questions-categoria-filtro', function() { cargar(1); });
        $widget.on('change', '.fn-questions-estado-filtro', function() { cargar(1); });
        $widget.on('keypress', '.fn-questions-busqueda', function(e) {
            if (e.which === 13) cargar(1);
        });
        $widget.on('click', '.fn-page-btn', function() { cargar($(this).data('page')); });

        cargar(1);
    }

    // ─── Initialize widgets on page ───
    $(document).ready(function() {
        $('.flavor-network-directory-widget').each(function() { initDirectoryWidget($(this)); });
        $('.flavor-network-board-widget').each(function() { initBoardWidget($(this)); });
        $('.flavor-network-events-widget').each(function() { initEventsWidget($(this)); });
        $('.flavor-network-alerts-widget').each(function() { initAlertsWidget($(this)); });
        $('.flavor-network-catalog-widget').each(function() { initCatalogWidget($(this)); });
        $('.flavor-network-collaborations-widget').each(function() { initCollaborationsWidget($(this)); });
        $('.flavor-network-time-offers-widget').each(function() { initTimeOffersWidget($(this)); });
        $('.flavor-network-node-profile-widget').each(function() { initNodeProfileWidget($(this)); });
        $('.flavor-network-questions-widget').each(function() { initQuestionsWidget($(this)); });
    });

})(jQuery);
