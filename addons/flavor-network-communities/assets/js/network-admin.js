/**
 * Network Admin JavaScript
 * Red de Comunidades - Panel de administración
 * CRUD completo para todos los módulos
 */
(function($) {
    'use strict';

    var API_URL = flavorNetworkAdmin.apiUrl;
    var NONCE = flavorNetworkAdmin.nonce;
    var I18N = flavorNetworkAdmin.i18n;

    // ─── Helper: API request ───
    function apiRequest(endpoint, method, data) {
        var opciones = {
            url: API_URL + endpoint,
            method: method || 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', NONCE);
            },
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            opciones.contentType = 'application/json';
            opciones.data = JSON.stringify(data);
        } else if (data) {
            opciones.data = data;
        }

        return $.ajax(opciones);
    }

    // ─── Helper: Show notification ───
    function showNotice(mensaje, tipo) {
        tipo = tipo || 'success';
        var $aviso = $('<div class="notice notice-' + tipo + ' is-dismissible" style="margin:10px 0;"><p>' + mensaje + '</p></div>');
        $('.flavor-network-content').prepend($aviso);
        setTimeout(function() { $aviso.fadeOut(function() { $aviso.remove(); }); }, 4000);
    }

    // ─── Helper: Load nodes list into a select ───
    function cargarNodosSelect($select, excluirLocal) {
        apiRequest('/nodes-list')
            .done(function(respuesta) {
                var primerOption = $select.find('option:first').clone();
                $select.empty().append(primerOption);
                respuesta.nodos.forEach(function(nodo) {
                    $select.append('<option value="' + nodo.id + '">' + escapeHtml(nodo.nombre) + ' (' + escapeHtml(nodo.tipo_entidad) + (nodo.ciudad ? ', ' + escapeHtml(nodo.ciudad) : '') + ')</option>');
                });
            });
    }

    // ─── Helper: Confirm delete ───
    function confirmarEliminacion(callback) {
        if (confirm(I18N.confirmar_eliminar)) {
            callback();
        }
    }

    // ─── Mi Nodo: Guardar ───
    $(document).on('submit', '#flavor-nodo-form', function(e) {
        e.preventDefault();
        var $boton = $('#btn-guardar-nodo');
        $boton.prop('disabled', true).text(I18N.cargando);

        var tagsValor = $('#nodo-tags').val();
        var tagsArray = tagsValor ? tagsValor.split(',').map(function(t) { return t.trim(); }).filter(Boolean) : [];

        var datosNodo = {
            nombre: $('#nodo-nombre').val(),
            slug: $('#nodo-slug').val(),
            descripcion_corta: $('#nodo-descripcion-corta').val(),
            descripcion: $('#nodo-descripcion').val(),
            tipo_entidad: $('#nodo-tipo').val(),
            sector: $('#nodo-sector').val(),
            nivel_consciencia: $('#nodo-nivel').val(),
            logo_url: $('#nodo-logo').val(),
            direccion: $('#nodo-direccion').val(),
            ciudad: $('#nodo-ciudad').val(),
            provincia: $('#nodo-provincia').val(),
            pais: $('#nodo-pais').val(),
            latitud: $('#nodo-latitud').val() || null,
            longitud: $('#nodo-longitud').val() || null,
            email: $('#nodo-email').val(),
            telefono: $('#nodo-telefono').val(),
            web: $('#nodo-web').val(),
            tags: tagsArray,
        };

        apiRequest('/local-node', 'POST', datosNodo)
            .done(function() {
                showNotice(I18N.guardado);
                $('#nodo-save-status').text('✓').css('color', 'green');
            })
            .fail(function(xhr) {
                showNotice(xhr.responseJSON?.message || I18N.error, 'error');
            })
            .always(function() {
                $boton.prop('disabled', false).text('Guardar configuración del nodo');
            });
    });

    // ─── Mi Nodo: Media upload ───
    $(document).on('click', '.flavor-upload-media', function(e) {
        e.preventDefault();
        var $boton = $(this);
        var $target = $($boton.data('target'));

        var frame = wp.media({
            title: 'Seleccionar imagen',
            button: { text: 'Usar imagen' },
            multiple: false,
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $target.val(attachment.url);
        });

        frame.open();
    });

    // ─── Mi Nodo: Geolocalización ───
    $(document).on('click', '#btn-geolocate', function() {
        if (!navigator.geolocation) {
            showNotice('Geolocalización no soportada', 'error');
            return;
        }

        navigator.geolocation.getCurrentPosition(function(posicion) {
            $('#nodo-latitud').val(posicion.coords.latitude.toFixed(8));
            $('#nodo-longitud').val(posicion.coords.longitude.toFixed(8));
            initMapPreview(posicion.coords.latitude, posicion.coords.longitude);
        }, function() {
            showNotice('No se pudo obtener la ubicación', 'error');
        });
    });

    // ─── Map preview for Mi Nodo ───
    var mapaPreview = null;
    var marcadorPreview = null;

    function initMapPreview(lat, lng) {
        if (typeof L === 'undefined') return;

        if (mapaPreview) {
            mapaPreview.setView([lat, lng], 14);
            marcadorPreview.setLatLng([lat, lng]);
            return;
        }

        mapaPreview = L.map('nodo-map-preview').setView([lat, lng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 18,
        }).addTo(mapaPreview);

        marcadorPreview = L.marker([lat, lng], { draggable: true }).addTo(mapaPreview);
        marcadorPreview.on('dragend', function(e) {
            var pos = e.target.getLatLng();
            $('#nodo-latitud').val(pos.lat.toFixed(8));
            $('#nodo-longitud').val(pos.lng.toFixed(8));
        });
    }

    $(document).ready(function() {
        var lat = parseFloat($('#nodo-latitud').val());
        var lng = parseFloat($('#nodo-longitud').val());
        if (lat && lng && $('#nodo-map-preview').length) {
            setTimeout(function() { initMapPreview(lat, lng); }, 500);
        }
    });

    // ─── Directorio ───
    function cargarDirectorio(pagina) {
        pagina = pagina || 1;
        var parametros = {
            busqueda: $('#dir-busqueda').val() || '',
            tipo: $('#dir-tipo').val() || '',
            nivel: $('#dir-nivel').val() || '',
            pagina: pagina,
            por_pagina: 20,
        };

        apiRequest('/directory', 'GET', parametros)
            .done(function(respuesta) {
                renderizarDirectorio(respuesta.nodos, respuesta.total, respuesta.pagina, respuesta.paginas);
            })
            .fail(function() {
                $('#directorio-resultados').html('<p>' + I18N.error + '</p>');
            });
    }

    function renderizarDirectorio(nodos, total, pagina, paginas) {
        var $contenedor = $('#directorio-resultados');
        $contenedor.empty();

        if (!nodos.length) {
            $contenedor.html('<p>' + I18N.sin_resultados + '</p>');
            return;
        }

        nodos.forEach(function(nodo) {
            var inicialNombre = (nodo.nombre || '?').charAt(0).toUpperCase();
            var imagenLogo = nodo.logo_url
                ? '<img src="' + nodo.logo_url + '" class="node-logo" alt="">'
                : '<div class="node-logo-placeholder">' + inicialNombre + '</div>';

            var htmlTarjeta = '<div class="flavor-node-card" data-id="' + nodo.id + '" data-slug="' + nodo.slug + '">' +
                '<div class="node-header">' + imagenLogo +
                '<div class="node-info"><h4>' + escapeHtml(nodo.nombre) + '</h4>' +
                '<span class="node-type">' + escapeHtml(nodo.tipo_entidad) + '</span></div></div>' +
                '<p class="node-description">' + escapeHtml(nodo.descripcion_corta || '') + '</p>' +
                '<div class="node-meta">' +
                (nodo.ciudad ? '<span>📍 ' + escapeHtml(nodo.ciudad) + '</span>' : '') +
                '<span class="node-badge ' + nodo.nivel_consciencia + '">' + escapeHtml(nodo.nivel_consciencia) + '</span>' +
                (nodo.verificado ? '<span class="node-badge verificado">✓ Verificado</span>' : '') +
                '</div>' +
                '<div class="node-actions" style="margin-top:8px;">' +
                '<button class="button button-small btn-conectar-nodo" data-id="' + nodo.id + '">Conectar</button> ' +
                '<button class="button button-small btn-favorito-nodo" data-id="' + nodo.id + '">★ Favorito</button> ' +
                '<button class="button button-small btn-eliminar-nodo" data-id="' + nodo.id + '" style="color:#d63638;">Eliminar</button>' +
                '</div></div>';

            $contenedor.append(htmlTarjeta);
        });

        var $paginacion = $('#directorio-paginacion');
        $paginacion.empty();
        if (paginas > 1) {
            for (var i = 1; i <= paginas; i++) {
                var claseActiva = i === pagina ? ' button-primary' : '';
                $paginacion.append('<button class="button' + claseActiva + ' btn-pagina-dir" data-pagina="' + i + '">' + i + '</button> ');
            }
        }
    }

    $(document).on('click', '#btn-buscar-dir', function() { cargarDirectorio(1); });
    $(document).on('click', '.btn-pagina-dir', function() { cargarDirectorio($(this).data('pagina')); });

    // Conectar nodo desde directorio
    $(document).on('click', '.btn-conectar-nodo', function() {
        var nodoId = $(this).data('id');
        apiRequest('/connect', 'POST', { nodo_destino_id: nodoId })
            .done(function() { showNotice(I18N.conexion_enviada); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    // Favorito desde directorio
    $(document).on('click', '.btn-favorito-nodo', function() {
        var nodoId = $(this).data('id');
        apiRequest('/favorites', 'POST', { nodo_favorito_id: nodoId })
            .done(function(resp) { showNotice(resp.message); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    // Eliminar nodo desde directorio
    $(document).on('click', '.btn-eliminar-nodo', function() {
        var nodoId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/nodes/' + nodoId, 'DELETE')
                .done(function() { showNotice('Nodo eliminado'); cargarDirectorio(1); })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        });
    });

    // Añadir nodo manualmente
    $(document).on('click', '#btn-add-node', function() {
        var nombre = prompt('Nombre del nodo:');
        if (!nombre) return;
        var slug = nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

        apiRequest('/nodes', 'POST', { nombre: nombre, slug: slug, site_url: '' })
            .done(function() { showNotice('Nodo añadido'); cargarDirectorio(1); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    if ($('#directorio-resultados').length) {
        cargarDirectorio(1);
    }

    // ─── Mapa Admin ───
    var mapaAdmin = null;

    function initAdminMap() {
        if (typeof L === 'undefined' || !$('#network-admin-map').length) return;

        mapaAdmin = L.map('network-admin-map').setView([40.4168, -3.7038], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 18,
        }).addTo(mapaAdmin);

        cargarNodosMapa();
    }

    function cargarNodosMapa() {
        var parametros = {
            tipo: $('#map-tipo').val() || '',
            nivel: $('#map-nivel').val() || '',
        };

        apiRequest('/map', 'GET', parametros)
            .done(function(respuesta) {
                if (!mapaAdmin) return;

                mapaAdmin.eachLayer(function(capa) {
                    if (capa instanceof L.Marker) {
                        mapaAdmin.removeLayer(capa);
                    }
                });

                respuesta.nodos.forEach(function(nodo) {
                    if (!nodo.lat || !nodo.lng) return;

                    var marcador = L.marker([nodo.lat, nodo.lng]).addTo(mapaAdmin);
                    marcador.bindPopup(
                        '<strong>' + escapeHtml(nodo.nombre) + '</strong><br>' +
                        '<em>' + escapeHtml(nodo.tipo_entidad) + '</em><br>' +
                        (nodo.ciudad ? '📍 ' + escapeHtml(nodo.ciudad) : '') +
                        '<br><span class="node-badge ' + nodo.nivel_consciencia + '">' + nodo.nivel_consciencia + '</span>'
                    );
                });

                if (respuesta.nodos.length > 0) {
                    var coordenadas = respuesta.nodos
                        .filter(function(n) { return n.lat && n.lng; })
                        .map(function(n) { return [n.lat, n.lng]; });
                    if (coordenadas.length) {
                        mapaAdmin.fitBounds(coordenadas, { padding: [30, 30] });
                    }
                }
            });
    }

    $(document).on('click', '#btn-filtrar-mapa', function() { cargarNodosMapa(); });
    $(document).on('click', '#btn-mi-ubicacion', function() {
        if (navigator.geolocation && mapaAdmin) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                mapaAdmin.setView([pos.coords.latitude, pos.coords.longitude], 12);
            });
        }
    });

    if ($('#network-admin-map').length) {
        setTimeout(initAdminMap, 300);
    }

    // ─── Conexiones ───
    function cargarConexiones() {
        apiRequest('/connections')
            .done(function(respuesta) {
                var $lista = $('#conexiones-lista');
                $lista.empty();

                if (!respuesta.conexiones.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.conexiones.forEach(function(conexion) {
                    var htmlItem = '<div class="connection-item">' +
                        '<div class="connection-info">' +
                        '<strong>' + escapeHtml(conexion.origen_nombre) + '</strong>' +
                        ' ↔ <strong>' + escapeHtml(conexion.destino_nombre) + '</strong>' +
                        ' <span class="connection-status ' + conexion.estado + '">' + conexion.estado + '</span>' +
                        ' <span class="node-badge ' + conexion.nivel + '">' + conexion.nivel + '</span>' +
                        '</div>' +
                        '<div class="connection-actions">';

                    if (conexion.estado === 'pendiente') {
                        htmlItem += '<button class="button button-small btn-aprobar-conexion" data-id="' + conexion.id + '">Aprobar</button> ';
                        htmlItem += '<button class="button button-small btn-rechazar-conexion" data-id="' + conexion.id + '">Rechazar</button> ';
                    }
                    if (conexion.estado === 'aprobada') {
                        htmlItem += '<select class="btn-nivel-conexion" data-id="' + conexion.id + '" style="vertical-align:middle;">' +
                            '<option value="visible"' + (conexion.nivel === 'visible' ? ' selected' : '') + '>Visible</option>' +
                            '<option value="conectado"' + (conexion.nivel === 'conectado' ? ' selected' : '') + '>Conectado</option>' +
                            '<option value="federado"' + (conexion.nivel === 'federado' ? ' selected' : '') + '>Federado</option>' +
                            '</select> ';
                    }
                    htmlItem += '<button class="button button-small btn-eliminar-conexion" data-id="' + conexion.id + '" style="color:#d63638;">Eliminar</button>';
                    htmlItem += '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '.btn-aprobar-conexion', function() {
        apiRequest('/connections/' + $(this).data('id'), 'PUT', { estado: 'aprobada' })
            .done(function() { showNotice('Conexión aprobada'); cargarConexiones(); });
    });

    $(document).on('click', '.btn-rechazar-conexion', function() {
        apiRequest('/connections/' + $(this).data('id'), 'PUT', { estado: 'rechazada' })
            .done(function() { showNotice('Conexión rechazada'); cargarConexiones(); });
    });

    $(document).on('change', '.btn-nivel-conexion', function() {
        apiRequest('/connections/' + $(this).data('id'), 'PUT', { nivel: $(this).val() })
            .done(function() { showNotice('Nivel actualizado'); });
    });

    $(document).on('click', '.btn-eliminar-conexion', function() {
        var conexionId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/connections/' + conexionId, 'DELETE')
                .done(function() { showNotice('Conexión eliminada'); cargarConexiones(); });
        });
    });

    if ($('#conexiones-lista').length) {
        cargarConexiones();
    }

    // ─── Contenido (con editar/eliminar) ───
    function cargarContenido() {
        var parametros = {
            tipo: $('#contenido-tipo-filtro').val() || '',
            busqueda: $('#contenido-busqueda').val() || '',
        };

        apiRequest('/content', 'GET', parametros)
            .done(function(respuesta) {
                var $lista = $('#contenido-lista');
                $lista.empty();

                if (!respuesta.contenidos.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.contenidos.forEach(function(contenido) {
                    var precioTexto = contenido.precio > 0 ? contenido.precio + ' ' + (contenido.moneda || 'EUR') : 'Gratuito';
                    var htmlItem = '<div class="flavor-content-card" data-id="' + contenido.id + '">' +
                        '<span class="content-type-badge">' + escapeHtml(contenido.tipo_contenido) + '</span>' +
                        '<h4>' + escapeHtml(contenido.titulo) + '</h4>' +
                        '<p class="content-desc">' + escapeHtml(truncar(contenido.descripcion || '', 150)) + '</p>' +
                        '<div class="content-meta">' +
                        '<span>' + precioTexto + '</span>' +
                        (contenido.nodo_nombre ? ' · ' + escapeHtml(contenido.nodo_nombre) : '') +
                        (contenido.ubicacion ? ' · 📍 ' + escapeHtml(contenido.ubicacion) : '') +
                        '</div>' +
                        '<div class="content-actions" style="margin-top:8px;">' +
                        '<button class="button button-small btn-editar-contenido" data-id="' + contenido.id + '" data-titulo="' + escapeHtml(contenido.titulo) + '" data-descripcion="' + escapeHtml(contenido.descripcion || '') + '" data-precio="' + (contenido.precio || 0) + '" data-ubicacion="' + escapeHtml(contenido.ubicacion || '') + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-contenido" data-id="' + contenido.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-buscar-contenido', function() { cargarContenido(); });
    $(document).on('click', '#btn-nuevo-contenido', function() {
        $('#form-nuevo-contenido').slideDown();
        $('#nuevo-contenido-titulo').val('');
        $('#nuevo-contenido-desc').val('');
        $('#nuevo-contenido-precio').val('');
        $('#nuevo-contenido-ubicacion').val('');
        $('#form-nuevo-contenido').data('edit-id', '');
        $('#form-nuevo-contenido h3').text('Publicar contenido');
    });
    $(document).on('click', '#btn-cancelar-contenido', function() { $('#form-nuevo-contenido').slideUp(); });

    $(document).on('click', '.btn-editar-contenido', function() {
        var $btn = $(this);
        $('#nuevo-contenido-titulo').val($btn.data('titulo'));
        $('#nuevo-contenido-desc').val($btn.data('descripcion'));
        $('#nuevo-contenido-precio').val($btn.data('precio'));
        $('#nuevo-contenido-ubicacion').val($btn.data('ubicacion'));
        $('#form-nuevo-contenido').data('edit-id', $btn.data('id'));
        $('#form-nuevo-contenido h3').text('Editar contenido');
        $('#form-nuevo-contenido').slideDown();
    });

    $(document).on('click', '#btn-publicar-contenido', function() {
        var editId = $('#form-nuevo-contenido').data('edit-id');
        var datos = {
            tipo_contenido: $('#nuevo-contenido-tipo').val(),
            titulo: $('#nuevo-contenido-titulo').val(),
            descripcion: $('#nuevo-contenido-desc').val(),
            precio: $('#nuevo-contenido-precio').val() || 0,
            ubicacion: $('#nuevo-contenido-ubicacion').val(),
        };

        if (editId) {
            apiRequest('/content/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Contenido actualizado');
                    $('#form-nuevo-contenido').slideUp();
                    cargarContenido();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/content', 'POST', datos)
                .done(function() {
                    showNotice('Contenido publicado');
                    $('#form-nuevo-contenido').slideUp();
                    cargarContenido();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-eliminar-contenido', function() {
        var contenidoId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/content/' + contenidoId, 'DELETE')
                .done(function() { showNotice('Contenido eliminado'); cargarContenido(); });
        });
    });

    if ($('#contenido-lista').length) {
        cargarContenido();
    }

    // ─── Eventos (con editar/eliminar) ───
    function cargarEventos() {
        apiRequest('/events')
            .done(function(respuesta) {
                var $lista = $('#eventos-lista');
                $lista.empty();

                if (!respuesta.eventos.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
                respuesta.eventos.forEach(function(evento) {
                    var fecha = new Date(evento.fecha_inicio);
                    var dia = fecha.getDate();
                    var mes = meses[fecha.getMonth()];

                    var htmlItem = '<div class="flavor-event-item" data-id="' + evento.id + '">' +
                        '<div class="event-date-box"><span class="event-day">' + dia + '</span><span class="event-month">' + mes + '</span></div>' +
                        '<div class="event-info"><h4>' + escapeHtml(evento.titulo) + '</h4>' +
                        '<div class="event-meta">' +
                        '<span>' + escapeHtml(evento.tipo_evento) + '</span>' +
                        (evento.ubicacion ? '<span>📍 ' + escapeHtml(evento.ubicacion) + '</span>' : '') +
                        (evento.nodo_nombre ? '<span>Por: ' + escapeHtml(evento.nodo_nombre) + '</span>' : '') +
                        '</div>' +
                        '<div class="event-actions" style="margin-top:6px;">' +
                        '<button class="button button-small btn-editar-evento" data-id="' + evento.id + '" data-titulo="' + escapeHtml(evento.titulo) + '" data-descripcion="' + escapeHtml(evento.descripcion || '') + '" data-tipo="' + escapeHtml(evento.tipo_evento) + '" data-ubicacion="' + escapeHtml(evento.ubicacion || '') + '" data-inicio="' + (evento.fecha_inicio || '').replace(' ', 'T').substring(0,16) + '" data-fin="' + (evento.fecha_fin || '').replace(' ', 'T').substring(0,16) + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-evento" data-id="' + evento.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nuevo-evento', function() {
        $('#form-nuevo-evento').slideDown();
        $('#evento-titulo').val('');
        $('#evento-desc').val('');
        $('#evento-inicio').val('');
        $('#evento-fin').val('');
        $('#evento-ubicacion').val('');
        $('#form-nuevo-evento').data('edit-id', '');
        $('#form-nuevo-evento h3').text('Nuevo evento');
    });
    $(document).on('click', '#btn-cancelar-evento', function() { $('#form-nuevo-evento').slideUp(); });

    $(document).on('click', '.btn-editar-evento', function() {
        var $btn = $(this);
        $('#evento-titulo').val($btn.data('titulo'));
        $('#evento-desc').val($btn.data('descripcion'));
        $('#evento-tipo').val($btn.data('tipo'));
        $('#evento-ubicacion').val($btn.data('ubicacion'));
        $('#evento-inicio').val($btn.data('inicio'));
        $('#evento-fin').val($btn.data('fin'));
        $('#form-nuevo-evento').data('edit-id', $btn.data('id'));
        $('#form-nuevo-evento h3').text('Editar evento');
        $('#form-nuevo-evento').slideDown();
    });

    $(document).on('click', '#btn-crear-evento', function() {
        var editId = $('#form-nuevo-evento').data('edit-id');
        var datos = {
            titulo: $('#evento-titulo').val(),
            descripcion: $('#evento-desc').val(),
            fecha_inicio: $('#evento-inicio').val(),
            fecha_fin: $('#evento-fin').val(),
            tipo_evento: $('#evento-tipo').val(),
            ubicacion: $('#evento-ubicacion').val(),
        };

        if (editId) {
            apiRequest('/events/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Evento actualizado');
                    $('#form-nuevo-evento').slideUp();
                    cargarEventos();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/events', 'POST', datos)
                .done(function() {
                    showNotice('Evento creado');
                    $('#form-nuevo-evento').slideUp();
                    cargarEventos();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-eliminar-evento', function() {
        var eventoId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/events/' + eventoId, 'DELETE')
                .done(function() { showNotice('Evento eliminado'); cargarEventos(); });
        });
    });

    if ($('#eventos-lista').length) {
        cargarEventos();
    }

    // ─── Colaboraciones (con editar/eliminar) ───
    function cargarColaboraciones() {
        var parametros = { tipo: $('#colab-tipo-filtro').val() || '' };

        apiRequest('/collaborations', 'GET', parametros)
            .done(function(respuesta) {
                var $lista = $('#colaboraciones-lista');
                $lista.empty();

                if (!respuesta.colaboraciones.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.colaboraciones.forEach(function(colab) {
                    var htmlItem = '<div class="flavor-colab-item" data-id="' + colab.id + '">' +
                        '<span class="colab-type-badge">' + escapeHtml(colab.tipo) + '</span>' +
                        '<h4>' + escapeHtml(colab.titulo) + '</h4>' +
                        '<p>' + escapeHtml(truncar(colab.descripcion || '', 200)) + '</p>' +
                        '<div class="colab-meta">' +
                        (colab.nodo_nombre ? 'Creado por: ' + escapeHtml(colab.nodo_nombre) : '') +
                        ' · Estado: ' + colab.estado +
                        (colab.fecha_limite ? ' · Fecha límite: ' + colab.fecha_limite : '') +
                        '</div>' +
                        '<div class="colab-actions" style="margin-top:8px;">' +
                        '<button class="button button-small btn-unirse-colab" data-id="' + colab.id + '">Unirse</button> ' +
                        '<button class="button button-small btn-editar-colab" data-id="' + colab.id + '" data-titulo="' + escapeHtml(colab.titulo) + '" data-descripcion="' + escapeHtml(colab.descripcion || '') + '" data-objetivo="' + escapeHtml(colab.objetivo || '') + '" data-tipo="' + escapeHtml(colab.tipo) + '">Editar</button> ' +
                        '<button class="button button-small btn-cerrar-colab" data-id="' + colab.id + '" style="color:#d63638;">Cerrar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nueva-colab', function() {
        $('#form-nueva-colab').slideDown();
        $('#colab-titulo').val('');
        $('#colab-desc').val('');
        $('#colab-objetivo').val('');
        $('#colab-fecha-limite').val('');
        $('#form-nueva-colab').data('edit-id', '');
        $('#form-nueva-colab h3').text('Crear colaboración');
    });
    $(document).on('click', '#btn-cancelar-colab', function() { $('#form-nueva-colab').slideUp(); });

    $(document).on('click', '.btn-editar-colab', function() {
        var $btn = $(this);
        $('#colab-titulo').val($btn.data('titulo'));
        $('#colab-desc').val($btn.data('descripcion'));
        $('#colab-objetivo').val($btn.data('objetivo'));
        $('#colab-tipo').val($btn.data('tipo'));
        $('#form-nueva-colab').data('edit-id', $btn.data('id'));
        $('#form-nueva-colab h3').text('Editar colaboración');
        $('#form-nueva-colab').slideDown();
    });

    $(document).on('click', '#btn-crear-colab', function() {
        var editId = $('#form-nueva-colab').data('edit-id');
        var datos = {
            tipo: $('#colab-tipo').val(),
            titulo: $('#colab-titulo').val(),
            descripcion: $('#colab-desc').val(),
            objetivo: $('#colab-objetivo').val(),
            fecha_limite: $('#colab-fecha-limite').val(),
        };

        if (editId) {
            apiRequest('/collaborations/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Colaboración actualizada');
                    $('#form-nueva-colab').slideUp();
                    cargarColaboraciones();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/collaborations', 'POST', datos)
                .done(function() {
                    showNotice('Colaboración creada');
                    $('#form-nueva-colab').slideUp();
                    cargarColaboraciones();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-unirse-colab', function() {
        apiRequest('/collaborations/' + $(this).data('id') + '/join', 'POST', {})
            .done(function() { showNotice('Solicitud enviada'); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    $(document).on('click', '.btn-cerrar-colab', function() {
        var colabId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/collaborations/' + colabId, 'DELETE')
                .done(function() { showNotice('Colaboración cerrada'); cargarColaboraciones(); });
        });
    });

    if ($('#colaboraciones-lista').length) {
        cargarColaboraciones();
    }

    // ─── Banco de Tiempo ───
    function cargarBancoTiempo() {
        var parametros = { tipo: $('#tiempo-tipo-filtro').val() || '' };

        apiRequest('/time-offers', 'GET', parametros)
            .done(function(respuesta) {
                var $lista = $('#tiempo-lista');
                $lista.empty();

                if (!respuesta.ofertas.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.ofertas.forEach(function(oferta) {
                    var htmlItem = '<div class="flavor-content-card" data-id="' + oferta.id + '">' +
                        '<span class="content-type-badge">' + escapeHtml(oferta.tipo) + '</span>' +
                        (oferta.categoria ? '<span class="content-type-badge" style="background:#e0f2fe;color:#0369a1;">' + escapeHtml(oferta.categoria) + '</span> ' : '') +
                        '<h4>' + escapeHtml(oferta.titulo) + '</h4>' +
                        '<p class="content-desc">' + escapeHtml(truncar(oferta.descripcion || '', 200)) + '</p>' +
                        '<div class="content-meta">' +
                        (oferta.horas_estimadas ? oferta.horas_estimadas + 'h' : '') +
                        ' · ' + escapeHtml(oferta.modalidad || 'presencial') +
                        (oferta.nodo_nombre ? ' · ' + escapeHtml(oferta.nodo_nombre) : '') +
                        '</div>' +
                        '<div class="content-actions" style="margin-top:8px;">' +
                        '<button class="button button-small btn-editar-tiempo" data-id="' + oferta.id + '" data-titulo="' + escapeHtml(oferta.titulo) + '" data-descripcion="' + escapeHtml(oferta.descripcion || '') + '" data-tipo="' + escapeHtml(oferta.tipo) + '" data-categoria="' + escapeHtml(oferta.categoria || '') + '" data-horas="' + (oferta.horas_estimadas || '') + '" data-modalidad="' + escapeHtml(oferta.modalidad || 'presencial') + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-tiempo" data-id="' + oferta.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nueva-oferta-tiempo', function() {
        $('#form-nueva-oferta-tiempo').slideDown();
        $('#tiempo-titulo').val('');
        $('#tiempo-desc').val('');
        $('#tiempo-horas').val('');
        $('#tiempo-edit-id').val('');
        $('#form-tiempo-titulo').text('Crear oferta de tiempo');
    });
    $(document).on('click', '#btn-cancelar-tiempo', function() { $('#form-nueva-oferta-tiempo').slideUp(); });

    $(document).on('click', '.btn-editar-tiempo', function() {
        var $btn = $(this);
        $('#tiempo-titulo').val($btn.data('titulo'));
        $('#tiempo-desc').val($btn.data('descripcion'));
        $('#tiempo-tipo').val($btn.data('tipo'));
        $('#tiempo-categoria').val($btn.data('categoria'));
        $('#tiempo-horas').val($btn.data('horas'));
        $('#tiempo-modalidad').val($btn.data('modalidad'));
        $('#tiempo-edit-id').val($btn.data('id'));
        $('#form-tiempo-titulo').text('Editar oferta de tiempo');
        $('#form-nueva-oferta-tiempo').slideDown();
    });

    $(document).on('click', '#btn-guardar-tiempo', function() {
        var editId = $('#tiempo-edit-id').val();
        var datos = {
            tipo: $('#tiempo-tipo').val(),
            titulo: $('#tiempo-titulo').val(),
            descripcion: $('#tiempo-desc').val(),
            categoria: $('#tiempo-categoria').val(),
            horas_estimadas: $('#tiempo-horas').val() || 0,
            modalidad: $('#tiempo-modalidad').val(),
        };

        if (editId) {
            apiRequest('/time-offers/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Oferta actualizada');
                    $('#form-nueva-oferta-tiempo').slideUp();
                    cargarBancoTiempo();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/time-offers', 'POST', datos)
                .done(function() {
                    showNotice('Oferta publicada');
                    $('#form-nueva-oferta-tiempo').slideUp();
                    cargarBancoTiempo();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-eliminar-tiempo', function() {
        var ofertaId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/time-offers/' + ofertaId, 'DELETE')
                .done(function() { showNotice('Oferta eliminada'); cargarBancoTiempo(); });
        });
    });

    $(document).on('change', '#tiempo-tipo-filtro', function() { cargarBancoTiempo(); });

    if ($('#tiempo-lista').length) {
        cargarBancoTiempo();
    }

    // ─── Tablón (con editar/eliminar) ───
    function cargarTablon() {
        apiRequest('/board')
            .done(function(respuesta) {
                var $lista = $('#tablon-lista');
                $lista.empty();

                if (!respuesta.publicaciones.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.publicaciones.forEach(function(pub) {
                    var htmlItem = '<div class="flavor-board-item" data-id="' + pub.id + '">' +
                        '<div class="board-header">' +
                        '<h4>' + escapeHtml(pub.titulo) + '</h4>' +
                        '<span class="board-type-badge">' + escapeHtml(pub.tipo) + '</span>' +
                        '</div>' +
                        '<p>' + escapeHtml(truncar(pub.contenido || '', 300)) + '</p>' +
                        '<div class="content-meta">' +
                        (pub.nodo_nombre ? escapeHtml(pub.nodo_nombre) + ' · ' : '') +
                        pub.fecha_publicacion +
                        '</div>' +
                        '<div class="board-actions" style="margin-top:8px;">' +
                        '<button class="button button-small btn-editar-pub" data-id="' + pub.id + '" data-titulo="' + escapeHtml(pub.titulo) + '" data-contenido="' + escapeHtml(pub.contenido || '') + '" data-tipo="' + escapeHtml(pub.tipo) + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-pub" data-id="' + pub.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nueva-publicacion', function() {
        $('#form-nueva-pub').slideDown();
        $('#pub-titulo').val('');
        $('#pub-contenido').val('');
        $('#form-nueva-pub').data('edit-id', '');
        $('#form-nueva-pub h3').text('Publicar en el tablón');
    });
    $(document).on('click', '#btn-cancelar-pub', function() { $('#form-nueva-pub').slideUp(); });

    $(document).on('click', '.btn-editar-pub', function() {
        var $btn = $(this);
        $('#pub-titulo').val($btn.data('titulo'));
        $('#pub-contenido').val($btn.data('contenido'));
        $('#pub-tipo').val($btn.data('tipo'));
        $('#form-nueva-pub').data('edit-id', $btn.data('id'));
        $('#form-nueva-pub h3').text('Editar publicación');
        $('#form-nueva-pub').slideDown();
    });

    $(document).on('click', '#btn-enviar-pub', function() {
        var editId = $('#form-nueva-pub').data('edit-id');
        var datos = {
            titulo: $('#pub-titulo').val(),
            contenido: $('#pub-contenido').val(),
            tipo: $('#pub-tipo').val(),
        };

        if (editId) {
            apiRequest('/board/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Publicación actualizada');
                    $('#form-nueva-pub').slideUp();
                    cargarTablon();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/board', 'POST', datos)
                .done(function() {
                    showNotice('Publicación creada');
                    $('#form-nueva-pub').slideUp();
                    cargarTablon();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-eliminar-pub', function() {
        var pubId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/board/' + pubId, 'DELETE')
                .done(function() { showNotice('Publicación eliminada'); cargarTablon(); });
        });
    });

    if ($('#tablon-lista').length) {
        cargarTablon();
    }

    // ─── Mensajes (con responder, eliminar, node selector) ───
    var tipoMensajesActual = 'recibidos';

    function cargarMensajes(tipo) {
        tipo = tipo || tipoMensajesActual;

        apiRequest('/messages', 'GET', { tipo: tipo })
            .done(function(respuesta) {
                var $lista = $('#mensajes-lista');
                $lista.empty();

                if (!respuesta.mensajes.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.mensajes.forEach(function(msg) {
                    var claseNoLeido = (!msg.leido && tipo === 'recibidos') ? ' unread' : '';
                    var htmlItem = '<div class="flavor-message-item' + claseNoLeido + '" data-id="' + msg.id + '">' +
                        '<div class="msg-header">' +
                        '<span class="msg-from">' + escapeHtml(msg.remitente_nombre || 'Nodo #' + msg.de_nodo_id) + '</span>' +
                        (msg.destinatario_nombre && tipo === 'enviados' ? ' → ' + escapeHtml(msg.destinatario_nombre) : '') +
                        '<span class="msg-date">' + msg.fecha_envio + '</span>' +
                        '</div>' +
                        (msg.asunto ? '<div class="msg-subject">' + escapeHtml(msg.asunto) + '</div>' : '') +
                        '<div class="msg-content">' + escapeHtml(truncar(msg.contenido || '', 300)) + '</div>' +
                        '<div class="msg-actions" style="margin-top:8px;">';

                    if (!msg.leido && tipo === 'recibidos') {
                        htmlItem += '<button class="button button-small btn-marcar-leido" data-id="' + msg.id + '">Marcar leído</button> ';
                    }
                    if (tipo === 'recibidos') {
                        htmlItem += '<button class="button button-small btn-responder-msg" data-id="' + msg.id + '" data-from="' + escapeHtml(msg.remitente_nombre || '') + '">Responder</button> ';
                    }
                    htmlItem += '<button class="button button-small btn-eliminar-msg" data-id="' + msg.id + '" style="color:#d63638;">Eliminar</button>';
                    htmlItem += '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '.btn-msg-tab', function() {
        $('.btn-msg-tab').removeClass('active');
        $(this).addClass('active');
        tipoMensajesActual = $(this).data('tipo');
        cargarMensajes(tipoMensajesActual);
    });

    $(document).on('click', '.btn-marcar-leido', function() {
        apiRequest('/messages/' + $(this).data('id') + '/read', 'POST')
            .done(function() { cargarMensajes(); });
    });

    $(document).on('click', '#btn-nuevo-mensaje', function() {
        $('#form-nuevo-msg').slideToggle();
        $('#form-nuevo-msg').data('reply-id', '');
        cargarNodosSelect($('#msg-destinatario'));
    });
    $(document).on('click', '#btn-cancelar-msg', function() { $('#form-nuevo-msg').slideUp(); });

    $(document).on('click', '.btn-responder-msg', function() {
        var msgId = $(this).data('id');
        var fromName = $(this).data('from');
        $('#form-nuevo-msg').data('reply-id', msgId);
        $('#msg-asunto').val('Re: ');
        $('#msg-contenido').val('');
        $('#form-nuevo-msg h3').text('Responder a ' + fromName);
        $('#form-nuevo-msg').slideDown();
    });

    $(document).on('click', '#btn-enviar-msg', function() {
        var replyId = $('#form-nuevo-msg').data('reply-id');

        if (replyId) {
            apiRequest('/messages/' + replyId + '/reply', 'POST', {
                contenido: $('#msg-contenido').val(),
            })
            .done(function() {
                showNotice(I18N.mensaje_enviado);
                $('#form-nuevo-msg').slideUp();
                cargarMensajes();
            })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/messages', 'POST', {
                a_nodo_id: $('#msg-destinatario').val(),
                asunto: $('#msg-asunto').val(),
                contenido: $('#msg-contenido').val(),
            })
            .done(function() {
                showNotice(I18N.mensaje_enviado);
                $('#form-nuevo-msg').slideUp();
                cargarMensajes();
            })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-eliminar-msg', function() {
        var msgId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/messages/' + msgId, 'DELETE')
                .done(function() { showNotice('Mensaje eliminado'); cargarMensajes(); });
        });
    });

    if ($('#mensajes-lista').length) {
        cargarMensajes();
    }

    // ─── Alertas (con editar/eliminar/resolver) ───
    function cargarAlertas() {
        apiRequest('/alerts')
            .done(function(respuesta) {
                var $lista = $('#alertas-lista');
                $lista.empty();

                if (!respuesta.alertas.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.alertas.forEach(function(alerta) {
                    var htmlItem = '<div class="flavor-alert-item urgencia-' + alerta.urgencia + '" data-id="' + alerta.id + '">' +
                        '<span class="urgencia-badge ' + alerta.urgencia + '">' + alerta.urgencia + '</span> ' +
                        '<h4>' + escapeHtml(alerta.titulo) + '</h4>' +
                        '<p>' + escapeHtml(truncar(alerta.descripcion || '', 200)) + '</p>' +
                        '<div class="content-meta">' +
                        (alerta.nodo_nombre ? escapeHtml(alerta.nodo_nombre) : '') +
                        (alerta.ubicacion ? ' · 📍 ' + escapeHtml(alerta.ubicacion) : '') +
                        ' · ' + alerta.fecha_publicacion +
                        '</div>' +
                        '<div class="alert-actions" style="margin-top:8px;">' +
                        '<button class="button button-small btn-resolver-alerta" data-id="' + alerta.id + '">Marcar resuelta</button> ' +
                        '<button class="button button-small btn-editar-alerta" data-id="' + alerta.id + '" data-titulo="' + escapeHtml(alerta.titulo) + '" data-descripcion="' + escapeHtml(alerta.descripcion || '') + '" data-urgencia="' + escapeHtml(alerta.urgencia) + '" data-ubicacion="' + escapeHtml(alerta.ubicacion || '') + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-alerta" data-id="' + alerta.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nueva-alerta', function() {
        $('#form-nueva-alerta').slideDown();
        $('#alerta-titulo').val('');
        $('#alerta-desc').val('');
        $('#alerta-ubicacion').val('');
        $('#form-nueva-alerta').data('edit-id', '');
        $('#form-nueva-alerta h3').text('Nueva alerta solidaria');
    });
    $(document).on('click', '#btn-cancelar-alerta', function() { $('#form-nueva-alerta').slideUp(); });

    $(document).on('click', '.btn-editar-alerta', function() {
        var $btn = $(this);
        $('#alerta-titulo').val($btn.data('titulo'));
        $('#alerta-desc').val($btn.data('descripcion'));
        $('#alerta-urgencia').val($btn.data('urgencia'));
        $('#alerta-ubicacion').val($btn.data('ubicacion'));
        $('#form-nueva-alerta').data('edit-id', $btn.data('id'));
        $('#form-nueva-alerta h3').text('Editar alerta');
        $('#form-nueva-alerta').slideDown();
    });

    $(document).on('click', '#btn-crear-alerta', function() {
        var editId = $('#form-nueva-alerta').data('edit-id');
        var datos = {
            titulo: $('#alerta-titulo').val(),
            descripcion: $('#alerta-desc').val(),
            urgencia: $('#alerta-urgencia').val(),
            ubicacion: $('#alerta-ubicacion').val(),
        };

        if (editId) {
            apiRequest('/alerts/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Alerta actualizada');
                    $('#form-nueva-alerta').slideUp();
                    cargarAlertas();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/alerts', 'POST', datos)
                .done(function() {
                    showNotice('Alerta publicada');
                    $('#form-nueva-alerta').slideUp();
                    cargarAlertas();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-resolver-alerta', function() {
        apiRequest('/alerts/' + $(this).data('id'), 'PUT', { estado: 'resuelta' })
            .done(function() { showNotice('Alerta marcada como resuelta'); cargarAlertas(); });
    });

    $(document).on('click', '.btn-eliminar-alerta', function() {
        var alertaId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/alerts/' + alertaId, 'DELETE')
                .done(function() { showNotice('Alerta eliminada'); cargarAlertas(); });
        });
    });

    if ($('#alertas-lista').length) {
        cargarAlertas();
    }

    // ─── Favoritos ───
    function cargarFavoritos() {
        apiRequest('/favorites')
            .done(function(respuesta) {
                var $lista = $('#favoritos-lista');
                $lista.empty();

                if (!respuesta.favoritos.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.favoritos.forEach(function(fav) {
                    var inicialNombre = (fav.nombre || '?').charAt(0).toUpperCase();
                    var imagenLogo = fav.logo_url
                        ? '<img src="' + fav.logo_url + '" class="node-logo" alt="" style="width:40px;height:40px;border-radius:50%;margin-right:10px;">'
                        : '<div class="node-logo-placeholder" style="width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#6366f1;color:#fff;margin-right:10px;">' + inicialNombre + '</div>';

                    var htmlItem = '<div class="flavor-content-card" style="display:flex;align-items:center;gap:10px;">' +
                        imagenLogo +
                        '<div style="flex:1;">' +
                        '<h4 style="margin:0;">' + escapeHtml(fav.nombre) + '</h4>' +
                        '<span class="node-type">' + escapeHtml(fav.tipo_entidad || '') + '</span>' +
                        (fav.ciudad ? ' · 📍 ' + escapeHtml(fav.ciudad) : '') +
                        '<p style="margin:4px 0 0;color:#6b7280;font-size:13px;">' + escapeHtml(fav.descripcion_corta || '') + '</p>' +
                        '</div>' +
                        '<button class="button button-small btn-quitar-favorito" data-id="' + fav.nodo_favorito_id + '" style="color:#d63638;">Quitar</button>' +
                        '</div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '.btn-quitar-favorito', function() {
        var nodoId = $(this).data('id');
        apiRequest('/favorites', 'POST', { nodo_favorito_id: nodoId })
            .done(function() { showNotice('Eliminado de favoritos'); cargarFavoritos(); });
    });

    if ($('#favoritos-lista').length) {
        cargarFavoritos();
    }

    // ─── Recomendaciones ───
    function cargarRecomendaciones() {
        apiRequest('/recommendations')
            .done(function(respuesta) {
                var $lista = $('#recomendaciones-lista');
                $lista.empty();

                if (!respuesta.recomendaciones.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.recomendaciones.forEach(function(rec) {
                    var htmlItem = '<div class="flavor-content-card">' +
                        '<div class="content-meta" style="margin-bottom:6px;">' +
                        '<strong>' + escapeHtml(rec.de_nombre) + '</strong>' +
                        ' recomienda <strong>' + escapeHtml(rec.recomendado_nombre) + '</strong>' +
                        ' a <strong>' + escapeHtml(rec.a_nombre) + '</strong>' +
                        '</div>' +
                        (rec.motivo ? '<p style="margin:0;">' + escapeHtml(rec.motivo) + '</p>' : '') +
                        '<div class="content-meta" style="margin-top:6px;">' +
                        '<span>' + rec.fecha + '</span>' +
                        ' · Estado: ' + rec.estado +
                        (rec.recomendado_tipo ? ' · ' + escapeHtml(rec.recomendado_tipo) : '') +
                        (rec.recomendado_ciudad ? ' · 📍 ' + escapeHtml(rec.recomendado_ciudad) : '') +
                        '</div>' +
                        '<button class="button button-small btn-eliminar-rec" data-id="' + rec.id + '" style="margin-top:8px;color:#d63638;">Eliminar</button>' +
                        '</div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nueva-recomendacion', function() {
        $('#form-nueva-recomendacion').slideToggle();
        cargarNodosSelect($('#rec-nodo-recomendado'));
        cargarNodosSelect($('#rec-destinatario'));
    });
    $(document).on('click', '#btn-cancelar-recomendacion', function() { $('#form-nueva-recomendacion').slideUp(); });

    $(document).on('click', '#btn-enviar-recomendacion', function() {
        apiRequest('/recommendations', 'POST', {
            nodo_recomendado_id: $('#rec-nodo-recomendado').val(),
            a_nodo_id: $('#rec-destinatario').val(),
            motivo: $('#rec-motivo').val(),
        })
        .done(function() {
            showNotice('Recomendación enviada');
            $('#form-nueva-recomendacion').slideUp();
            cargarRecomendaciones();
        })
        .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    $(document).on('click', '.btn-eliminar-rec', function() {
        var recId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/recommendations/' + recId, 'DELETE')
                .done(function() { showNotice('Recomendación eliminada'); cargarRecomendaciones(); });
        });
    });

    if ($('#recomendaciones-lista').length) {
        cargarRecomendaciones();
    }

    // ─── Módulos ───
    $(document).on('change', '.flavor-module-card input[type="checkbox"]', function() {
        $(this).closest('.flavor-module-card').toggleClass('active', this.checked);
    });

    $(document).on('submit', '#modulos-form', function(e) {
        e.preventDefault();
        var modulosSeleccionados = [];
        $('#modulos-form input[name="modulos[]"]:checked').each(function() {
            modulosSeleccionados.push($(this).val());
        });

        apiRequest('/local-node')
            .done(function(respuesta) {
                if (!respuesta.configurado) {
                    showNotice('Configura tu nodo primero', 'error');
                    return;
                }

                var datosNodo = respuesta.nodo;
                datosNodo.modulos_activos = modulosSeleccionados;

                apiRequest('/local-node', 'POST', datosNodo)
                    .done(function() {
                        showNotice(I18N.guardado);
                        $('#modulos-save-status').text('✓').css('color', 'green');
                    })
                    .fail(function() { showNotice(I18N.error, 'error'); });
            });
    });

    // ─── Sellos de Calidad ───
    function cargarSellos() {
        apiRequest('/seals')
            .done(function(respuesta) {
                var $lista = $('#sellos-lista');
                $lista.empty();

                if (!respuesta.sellos.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.sellos.forEach(function(sello) {
                    var nivelClases = { basico: '#9ca3af', transicion: '#f59e0b', consciente: '#10b981', referente: '#3b82f6' };
                    var nivelColor = nivelClases[sello.nivel] || '#9ca3af';

                    var htmlItem = '<div class="flavor-content-card" data-id="' + sello.id + '">' +
                        '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
                        '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + nivelColor + ';"></span>' +
                        '<strong>' + escapeHtml(sello.nodo_nombre || 'Nodo #' + sello.nodo_id) + '</strong>' +
                        '<span class="node-badge ' + sello.nivel + '">' + escapeHtml(sello.nivel) + '</span>' +
                        '<span style="color:#6b7280;font-size:13px;">' + sello.puntuacion + '/100</span>' +
                        (sello.estado !== 'activo' ? '<span style="color:#d63638;font-weight:600;">' + sello.estado + '</span>' : '') +
                        '</div>' +
                        (sello.criterios_cumplidos ? '<p style="margin:4px 0;color:#4b5563;font-size:13px;">' + escapeHtml(truncar(sello.criterios_cumplidos, 200)) + '</p>' : '') +
                        '<div class="content-meta">' +
                        'Otorgado: ' + sello.fecha_obtencion +
                        (sello.fecha_expiracion ? ' · Expira: ' + sello.fecha_expiracion : '') +
                        '</div>' +
                        '<div style="margin-top:8px;">' +
                        '<button class="button button-small btn-editar-sello" data-id="' + sello.id + '" data-nivel="' + escapeHtml(sello.nivel) + '" data-puntuacion="' + sello.puntuacion + '" data-criterios="' + escapeHtml(sello.criterios_cumplidos || '') + '" data-expiracion="' + escapeHtml(sello.fecha_expiracion || '') + '">Editar</button> ' +
                        '<button class="button button-small btn-revocar-sello" data-id="' + sello.id + '" style="color:#d63638;">Revocar</button>' +
                        '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-nuevo-sello', function() {
        $('#form-nuevo-sello').slideDown();
        $('#sello-edit-id').val('');
        $('#sello-puntuacion').val(0);
        $('#sello-criterios').val('');
        $('#sello-expiracion').val('');
        $('#form-sello-titulo').text('Otorgar sello de calidad');
        cargarNodosSelect($('#sello-nodo'));
    });
    $(document).on('click', '#btn-cancelar-sello', function() { $('#form-nuevo-sello').slideUp(); });

    $(document).on('click', '.btn-editar-sello', function() {
        var $btn = $(this);
        $('#sello-edit-id').val($btn.data('id'));
        $('#sello-nivel').val($btn.data('nivel'));
        $('#sello-puntuacion').val($btn.data('puntuacion'));
        $('#sello-criterios').val($btn.data('criterios'));
        $('#sello-expiracion').val($btn.data('expiracion'));
        $('#form-sello-titulo').text('Editar sello');
        $('#form-nuevo-sello').slideDown();
    });

    $(document).on('click', '#btn-guardar-sello', function() {
        var editId = $('#sello-edit-id').val();
        var datos = {
            nodo_id: $('#sello-nodo').val(),
            nivel: $('#sello-nivel').val(),
            puntuacion: $('#sello-puntuacion').val(),
            criterios_cumplidos: $('#sello-criterios').val(),
            fecha_expiracion: $('#sello-expiracion').val(),
        };

        if (editId) {
            apiRequest('/seals/' + editId, 'PUT', datos)
                .done(function() { showNotice('Sello actualizado'); $('#form-nuevo-sello').slideUp(); cargarSellos(); })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/seals', 'POST', datos)
                .done(function() { showNotice('Sello otorgado'); $('#form-nuevo-sello').slideUp(); cargarSellos(); })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-revocar-sello', function() {
        var selloId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/seals/' + selloId, 'DELETE')
                .done(function() { showNotice('Sello revocado'); cargarSellos(); });
        });
    });

    if ($('#sellos-lista').length) {
        cargarSellos();
    }

    // ─── QR Code ───
    $(document).on('click', '#btn-generar-qr', function() {
        var slug = $(this).data('slug');
        var size = $('#qr-size').val();
        $('#qr-loading').text('Generando...');
        apiRequest('/node/' + slug + '/qr', 'GET', { size: size })
            .done(function(data) {
                $('#qr-img').attr('src', data.qr_url).show();
                $('#qr-loading').hide();
            })
            .fail(function() { showNotice('Error al generar QR', 'error'); });
    });

    $(document).on('click', '#btn-generar-qr-vcard', function() {
        var slug = $(this).data('slug');
        var size = $('#qr-size').val();
        $('#qr-loading').text('Generando vCard...');
        apiRequest('/node/' + slug + '/qr', 'GET', { size: size })
            .done(function(data) {
                $('#qr-img').attr('src', data.qr_vcard_url).show();
                $('#qr-loading').hide();
            })
            .fail(function() { showNotice('Error al generar QR', 'error'); });
    });

    // ─── Newsletter ───
    function cargarNewsletters() {
        apiRequest('/newsletters')
            .done(function(respuesta) {
                var $lista = $('#newsletter-lista');
                $lista.empty();

                if (!respuesta.newsletters.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.newsletters.forEach(function(nl) {
                    var estadoBadge = nl.estado === 'enviada'
                        ? '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:11px;">Enviada</span>'
                        : '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:11px;">Borrador</span>';

                    var htmlItem = '<div class="flavor-content-card" data-id="' + nl.id + '">' +
                        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">' +
                        '<h4 style="margin:0;">' + escapeHtml(nl.asunto) + '</h4>' +
                        estadoBadge +
                        '</div>' +
                        '<div class="content-meta">' +
                        escapeHtml(nl.tipo) + ' · ' + nl.fecha_creacion +
                        (nl.destinatarios_count > 0 ? ' · Enviada a ' + nl.destinatarios_count + ' suscriptores' : '') +
                        '</div>' +
                        '<div style="margin-top:8px;">';

                    if (nl.estado === 'borrador') {
                        htmlItem += '<button class="button button-primary button-small btn-enviar-newsletter" data-id="' + nl.id + '">Enviar</button> ';
                        htmlItem += '<button class="button button-small btn-editar-newsletter" data-id="' + nl.id + '" data-asunto="' + escapeHtml(nl.asunto) + '" data-contenido="' + escapeHtml(nl.contenido || '') + '" data-tipo="' + escapeHtml(nl.tipo) + '">Editar</button> ';
                    }
                    htmlItem += '<button class="button button-small btn-eliminar-newsletter" data-id="' + nl.id + '" style="color:#d63638;">Eliminar</button>';
                    htmlItem += '</div></div>';
                    $lista.append(htmlItem);
                });
            });
    }

    function cargarSuscriptores() {
        apiRequest('/newsletter-subscribers')
            .done(function(respuesta) {
                var lista = $('#suscriptores-lista');
                lista.empty();

                if (!respuesta.suscriptores.length) {
                    lista.html('<p style="color:#9ca3af;font-size:13px;">Sin suscriptores</p>');
                    return;
                }

                lista.append('<p style="margin:0 0 10px;font-weight:600;">' + respuesta.suscriptores.length + ' suscriptores</p>');
                respuesta.suscriptores.forEach(function(sub) {
                    lista.append(
                        '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;">' +
                        '<div><strong style="font-size:13px;">' + escapeHtml(sub.nombre || sub.email) + '</strong>' +
                        (sub.nombre ? '<br><span style="font-size:12px;color:#9ca3af;">' + escapeHtml(sub.email) + '</span>' : '') +
                        '</div>' +
                        '<button class="button button-small btn-eliminar-sub" data-id="' + sub.id + '" style="color:#d63638;">×</button>' +
                        '</div>'
                    );
                });
            });
    }

    $(document).on('click', '#btn-nueva-newsletter', function() {
        $('#form-nueva-newsletter').slideDown();
        $('#newsletter-edit-id').val('');
        $('#newsletter-asunto').val('');
        $('#newsletter-contenido').val('');
        $('#form-newsletter-titulo').text('Crear newsletter');
    });
    $(document).on('click', '#btn-cancelar-newsletter', function() { $('#form-nueva-newsletter').slideUp(); });

    $(document).on('click', '.btn-editar-newsletter', function() {
        var btn = $(this);
        $('#newsletter-edit-id').val(btn.data('id'));
        $('#newsletter-asunto').val(btn.data('asunto'));
        $('#newsletter-contenido').val(btn.data('contenido'));
        $('#newsletter-tipo').val(btn.data('tipo'));
        $('#form-newsletter-titulo').text('Editar newsletter');
        $('#form-nueva-newsletter').slideDown();
    });

    $(document).on('click', '#btn-auto-contenido', function() {
        $(this).prop('disabled', true).text('Generando...');
        apiRequest('/newsletter-auto-content', 'GET', { dias: 7 })
            .done(function(respuesta) {
                $('#newsletter-contenido').val(respuesta.contenido);
            })
            .always(function() {
                $('#btn-auto-contenido').prop('disabled', false).text('Generar contenido automático (últimos 7 días)');
            });
    });

    $(document).on('click', '#btn-guardar-newsletter', function() {
        var editId = $('#newsletter-edit-id').val();
        var datos = {
            asunto: $('#newsletter-asunto').val(),
            contenido: $('#newsletter-contenido').val(),
            tipo: $('#newsletter-tipo').val(),
        };

        if (editId) {
            apiRequest('/newsletters/' + editId, 'PUT', datos)
                .done(function() { showNotice('Newsletter actualizada'); $('#form-nueva-newsletter').slideUp(); cargarNewsletters(); })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/newsletters', 'POST', datos)
                .done(function() { showNotice('Newsletter creada'); $('#form-nueva-newsletter').slideUp(); cargarNewsletters(); })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    $(document).on('click', '.btn-enviar-newsletter', function() {
        var nlId = $(this).data('id');
        if (!confirm('¿Enviar esta newsletter a todos los suscriptores?')) return;
        $(this).prop('disabled', true).text('Enviando...');
        apiRequest('/newsletters/' + nlId + '/send', 'POST', {})
            .done(function(resp) { showNotice(resp.message); cargarNewsletters(); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    $(document).on('click', '.btn-eliminar-newsletter', function() {
        var nlId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/newsletters/' + nlId, 'DELETE')
                .done(function() { showNotice('Newsletter eliminada'); cargarNewsletters(); });
        });
    });

    $(document).on('click', '#btn-add-sub', function() {
        apiRequest('/newsletter-subscribers', 'POST', {
            nombre: $('#sub-nombre').val(),
            email: $('#sub-email').val(),
        })
        .done(function() {
            showNotice('Suscriptor añadido');
            $('#sub-nombre').val('');
            $('#sub-email').val('');
            cargarSuscriptores();
        })
        .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    $(document).on('click', '.btn-eliminar-sub', function() {
        var subId = $(this).data('id');
        apiRequest('/newsletter-subscribers/' + subId, 'DELETE')
            .done(function() { cargarSuscriptores(); });
    });

    if ($('#newsletter-lista').length) {
        cargarNewsletters();
        cargarSuscriptores();
    }

    // ─── Preguntas a la Red ───
    var preguntaSeleccionadaId = null;

    function cargarPreguntas() {
        var parametros = {
            categoria: $('#preguntas-categoria-filtro').val() || '',
            estado: $('#preguntas-estado-filtro').val() || '',
            busqueda: $('#preguntas-busqueda').val() || '',
        };

        apiRequest('/questions', 'GET', parametros)
            .done(function(respuesta) {
                var $lista = $('#preguntas-list');
                $lista.empty();

                if (!respuesta.preguntas.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.preguntas.forEach(function(pregunta) {
                    var badgeEstado = '';
                    if (pregunta.estado === 'abierta') {
                        badgeEstado = '<span class="node-badge" style="background:#dcfce7;color:#166534;">Abierta</span>';
                    } else if (pregunta.estado === 'respondida') {
                        badgeEstado = '<span class="node-badge" style="background:#dbeafe;color:#1e40af;">Respondida</span>';
                    } else if (pregunta.estado === 'cerrada') {
                        badgeEstado = '<span class="node-badge" style="background:#f3f4f6;color:#6b7280;">Cerrada</span>';
                    }

                    var badgeCategoria = '<span class="node-badge" style="background:#ede9fe;color:#5b21b6;">' + escapeHtml(pregunta.categoria) + '</span>';

                    var htmlItem = '<div class="flavor-content-card" data-id="' + pregunta.id + '" style="cursor:pointer;">' +
                        '<div style="display:flex;justify-content:space-between;align-items:flex-start;">' +
                        '<div style="flex:1;">' +
                        '<h4 style="margin:0 0 6px;">' + escapeHtml(pregunta.titulo) + '</h4>' +
                        '<p class="content-desc" style="margin:0 0 8px;">' + escapeHtml(truncar(pregunta.descripcion || '', 200)) + '</p>' +
                        '<div class="content-meta">' +
                        badgeCategoria + ' ' + badgeEstado +
                        (pregunta.nodo_nombre ? ' · ' + escapeHtml(pregunta.nodo_nombre) : '') +
                        ' · ' + pregunta.respuestas_count + ' respuestas' +
                        ' · ' + pregunta.vistas + ' vistas' +
                        ' · ' + pregunta.fecha_publicacion +
                        '</div>' +
                        '</div>' +
                        '<div class="content-actions" style="margin-left:10px;white-space:nowrap;">' +
                        '<button class="button button-small btn-editar-pregunta" data-id="' + pregunta.id + '" data-titulo="' + escapeHtml(pregunta.titulo) + '" data-descripcion="' + escapeHtml(pregunta.descripcion || '') + '" data-categoria="' + escapeHtml(pregunta.categoria) + '" data-tags="' + escapeHtml(pregunta.tags || '') + '">Editar</button> ' +
                        '<button class="button button-small btn-eliminar-pregunta" data-id="' + pregunta.id + '" style="color:#d63638;">Eliminar</button>' +
                        '</div></div></div>';
                    $lista.append(htmlItem);
                });
            })
            .fail(function() {
                $('#preguntas-list').html('<p>' + I18N.error + '</p>');
            });
    }

    function cargarRespuestas(preguntaId) {
        preguntaSeleccionadaId = preguntaId;

        apiRequest('/questions/' + preguntaId)
            .done(function(pregunta) {
                // Renderizar cabecera de la pregunta
                var badgeEstado = '';
                if (pregunta.estado === 'abierta') {
                    badgeEstado = '<span class="node-badge" style="background:#dcfce7;color:#166534;">Abierta</span>';
                } else if (pregunta.estado === 'respondida') {
                    badgeEstado = '<span class="node-badge" style="background:#dbeafe;color:#1e40af;">Respondida</span>';
                } else if (pregunta.estado === 'cerrada') {
                    badgeEstado = '<span class="node-badge" style="background:#f3f4f6;color:#6b7280;">Cerrada</span>';
                }

                var htmlCabecera = '<h3 style="margin:0 0 8px;">' + escapeHtml(pregunta.titulo) + '</h3>' +
                    '<div style="margin-bottom:12px;">' + escapeHtml(pregunta.descripcion || '') + '</div>' +
                    '<div class="content-meta">' +
                    '<span class="node-badge" style="background:#ede9fe;color:#5b21b6;">' + escapeHtml(pregunta.categoria) + '</span> ' +
                    badgeEstado +
                    (pregunta.nodo_nombre ? ' · ' + escapeHtml(pregunta.nodo_nombre) : '') +
                    ' · ' + pregunta.vistas + ' vistas' +
                    ' · ' + pregunta.fecha_publicacion +
                    '</div>';

                $('#pregunta-detalle-header').html(htmlCabecera);

                // Renderizar respuestas
                var $listaRespuestas = $('#respuestas-lista');
                $listaRespuestas.empty();

                if (!pregunta.respuestas || !pregunta.respuestas.length) {
                    $listaRespuestas.html('<p style="color:#9ca3af;">Aun no hay respuestas. Se el primero en responder.</p>');
                } else {
                    $listaRespuestas.append('<h4>' + pregunta.respuestas.length + ' respuesta(s)</h4>');
                    pregunta.respuestas.forEach(function(respuesta) {
                        var claseSolucion = respuesta.es_solucion ? ' style="border-left:4px solid #10b981;background:#f0fdf4;"' : '';
                        var etiquetaSolucion = respuesta.es_solucion ? '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">Solucion</span> ' : '';

                        var htmlRespuesta = '<div class="flavor-content-card"' + claseSolucion + ' data-respuesta-id="' + respuesta.id + '">' +
                            '<div style="display:flex;justify-content:space-between;align-items:flex-start;">' +
                            '<div style="flex:1;">' +
                            etiquetaSolucion +
                            '<p style="margin:4px 0 8px;">' + escapeHtml(respuesta.contenido) + '</p>' +
                            '<div class="content-meta">' +
                            (respuesta.nodo_nombre ? escapeHtml(respuesta.nodo_nombre) : 'Nodo #' + respuesta.nodo_id) +
                            ' · ' + respuesta.fecha_publicacion +
                            ' · +' + respuesta.votos_positivos + ' / -' + respuesta.votos_negativos +
                            '</div>' +
                            '</div>' +
                            '<div style="margin-left:10px;white-space:nowrap;">' +
                            '<button class="button button-small btn-voto-positivo" data-id="' + respuesta.id + '" title="Voto positivo">+1</button> ' +
                            '<button class="button button-small btn-voto-negativo" data-id="' + respuesta.id + '" title="Voto negativo">-1</button> ' +
                            '<button class="button button-small btn-marcar-solucion" data-id="' + respuesta.id + '" title="Marcar como solucion">' + (respuesta.es_solucion ? 'Desmarcar' : 'Solucion') + '</button>' +
                            '</div></div></div>';
                        $listaRespuestas.append(htmlRespuesta);
                    });
                }

                $('#pregunta-detalle').slideDown();
            })
            .fail(function() {
                showNotice(I18N.error, 'error');
            });
    }

    // Click en pregunta para ver detalle/respuestas
    $(document).on('click', '.flavor-content-card[data-id]', function(e) {
        // Evitar si el click fue en un boton
        if ($(e.target).closest('button').length) return;
        // Solo en la tab de preguntas
        if (!$('#preguntas-list').length) return;
        var preguntaId = $(this).data('id');
        if (preguntaId && $(this).closest('#preguntas-list').length) {
            cargarRespuestas(preguntaId);
        }
    });

    // Nueva pregunta - abrir formulario
    $(document).on('click', '#btn-nueva-pregunta', function() {
        $('#form-nueva-pregunta').slideDown();
        $('#pregunta-titulo').val('');
        $('#pregunta-descripcion').val('');
        $('#pregunta-categoria').val('general');
        $('#pregunta-tags').val('');
        $('#form-nueva-pregunta').data('edit-id', '');
        $('#form-nueva-pregunta h3').text('Nueva pregunta');
    });
    $(document).on('click', '#btn-cancelar-pregunta', function() { $('#form-nueva-pregunta').slideUp(); });

    // Editar pregunta
    $(document).on('click', '.btn-editar-pregunta', function(e) {
        e.stopPropagation();
        var $boton = $(this);
        $('#pregunta-titulo').val($boton.data('titulo'));
        $('#pregunta-descripcion').val($boton.data('descripcion'));
        $('#pregunta-categoria').val($boton.data('categoria'));
        $('#pregunta-tags').val($boton.data('tags'));
        $('#form-nueva-pregunta').data('edit-id', $boton.data('id'));
        $('#form-nueva-pregunta h3').text('Editar pregunta');
        $('#form-nueva-pregunta').slideDown();
    });

    // Publicar/actualizar pregunta
    $(document).on('click', '#btn-publicar-pregunta', function() {
        var editId = $('#form-nueva-pregunta').data('edit-id');
        var tagsValor = $('#pregunta-tags').val();
        var tagsArray = tagsValor ? tagsValor.split(',').map(function(t) { return t.trim(); }).filter(Boolean) : [];

        var datos = {
            titulo: $('#pregunta-titulo').val(),
            descripcion: $('#pregunta-descripcion').val(),
            categoria: $('#pregunta-categoria').val(),
            tags: tagsArray,
        };

        if (editId) {
            apiRequest('/questions/' + editId, 'PUT', datos)
                .done(function() {
                    showNotice('Pregunta actualizada');
                    $('#form-nueva-pregunta').slideUp();
                    cargarPreguntas();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        } else {
            apiRequest('/questions', 'POST', datos)
                .done(function() {
                    showNotice('Pregunta publicada');
                    $('#form-nueva-pregunta').slideUp();
                    cargarPreguntas();
                })
                .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
        }
    });

    // Eliminar pregunta
    $(document).on('click', '.btn-eliminar-pregunta', function(e) {
        e.stopPropagation();
        var preguntaId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/questions/' + preguntaId, 'DELETE')
                .done(function() {
                    showNotice('Pregunta eliminada');
                    cargarPreguntas();
                    $('#pregunta-detalle').slideUp();
                });
        });
    });

    // Publicar respuesta
    $(document).on('click', '#btn-publicar-respuesta', function() {
        var contenido = $('#respuesta-contenido').val();
        if (!contenido || !preguntaSeleccionadaId) return;

        apiRequest('/questions/' + preguntaSeleccionadaId + '/answers', 'POST', { contenido: contenido })
            .done(function() {
                showNotice('Respuesta publicada');
                $('#respuesta-contenido').val('');
                cargarRespuestas(preguntaSeleccionadaId);
                cargarPreguntas();
            })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    // Cerrar detalle
    $(document).on('click', '#btn-cerrar-detalle', function() {
        $('#pregunta-detalle').slideUp();
        preguntaSeleccionadaId = null;
    });

    // Votar respuesta positivo
    $(document).on('click', '.btn-voto-positivo', function() {
        var respuestaId = $(this).data('id');
        apiRequest('/answers/' + respuestaId + '/vote', 'POST', { voto: 'positivo' })
            .done(function() {
                showNotice('Voto registrado');
                if (preguntaSeleccionadaId) cargarRespuestas(preguntaSeleccionadaId);
            });
    });

    // Votar respuesta negativo
    $(document).on('click', '.btn-voto-negativo', function() {
        var respuestaId = $(this).data('id');
        apiRequest('/answers/' + respuestaId + '/vote', 'POST', { voto: 'negativo' })
            .done(function() {
                showNotice('Voto registrado');
                if (preguntaSeleccionadaId) cargarRespuestas(preguntaSeleccionadaId);
            });
    });

    // Marcar como solucion
    $(document).on('click', '.btn-marcar-solucion', function() {
        var respuestaId = $(this).data('id');
        apiRequest('/answers/' + respuestaId + '/solution', 'POST', {})
            .done(function(resp) {
                showNotice(resp.message);
                if (preguntaSeleccionadaId) cargarRespuestas(preguntaSeleccionadaId);
                cargarPreguntas();
            });
    });

    // Filtros de preguntas
    $(document).on('click', '#btn-buscar-preguntas', function() { cargarPreguntas(); });
    $(document).on('change', '#preguntas-categoria-filtro', function() { cargarPreguntas(); });
    $(document).on('change', '#preguntas-estado-filtro', function() { cargarPreguntas(); });
    $(document).on('keypress', '#preguntas-busqueda', function(e) {
        if (e.which === 13) cargarPreguntas();
    });

    // Auto-init preguntas
    if ($('#preguntas-list').length) {
        cargarPreguntas();
    }


    // ─── Matching Necesidades/Excedentes ───
    function cargarMatches() {
        var estado = $('#match-filtro-estado').val() || '';
        apiRequest('/matches', 'GET', { estado: estado })
            .done(function(respuesta) {
                var $lista = $('#matches-lista');
                $lista.empty();
                $('#match-count').text(respuesta.matches.length + ' matches');

                if (!respuesta.matches.length) {
                    $lista.html('<p>' + I18N.sin_resultados + '</p>');
                    return;
                }

                respuesta.matches.forEach(function(match) {
                    var estadoClases = {
                        sugerido: 'background:#dbeafe;color:#1e40af',
                        aceptado: 'background:#dcfce7;color:#166534',
                        contactado: 'background:#fef3c7;color:#92400e',
                        en_proceso: 'background:#e0e7ff;color:#3730a3',
                        rechazado: 'background:#fee2e2;color:#991b1b',
                        descartado: 'background:#f3f4f6;color:#6b7280',
                    };
                    var estiloEstado = estadoClases[match.estado] || estadoClases.sugerido;

                    var barraScore = '<div style="background:#e5e7eb;height:6px;border-radius:3px;width:100px;display:inline-block;vertical-align:middle;">' +
                        '<div style="background:#10b981;height:100%;border-radius:3px;width:' + match.puntuacion + '%;"></div></div>' +
                        ' <span style="font-size:12px;color:#6b7280;">' + match.puntuacion + '%</span>';

                    var htmlItem = '<div class="flavor-content-card" data-id="' + match.id + '" style="border-left:4px solid ' + (match.puntuacion >= 60 ? '#10b981' : match.puntuacion >= 30 ? '#f59e0b' : '#9ca3af') + ';">' +
                        '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">' +
                        '<div>' +
                        '<span style="' + estiloEstado + ';padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">' + match.estado + '</span> ' +
                        barraScore +
                        '</div></div>' +
                        '<div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;">' +
                        '<div style="background:#fef2f2;padding:10px;border-radius:6px;">' +
                        '<span style="font-size:11px;color:#991b1b;font-weight:600;">NECESIDAD</span>' +
                        '<h4 style="margin:4px 0 2px;font-size:14px;">' + escapeHtml(match.necesidad_titulo) + '</h4>' +
                        '<span style="font-size:12px;color:#6b7280;">' + escapeHtml(match.nodo_necesidad_nombre) + '</span>' +
                        '</div>' +
                        '<div style="font-size:24px;color:#10b981;">↔</div>' +
                        '<div style="background:#f0fdf4;padding:10px;border-radius:6px;">' +
                        '<span style="font-size:11px;color:#166534;font-weight:600;">EXCEDENTE</span>' +
                        '<h4 style="margin:4px 0 2px;font-size:14px;">' + escapeHtml(match.excedente_titulo) + '</h4>' +
                        '<span style="font-size:12px;color:#6b7280;">' + escapeHtml(match.nodo_excedente_nombre) + '</span>' +
                        '</div></div>' +
                        '<div style="margin-top:10px;display:flex;gap:6px;">';

                    if (match.estado === 'sugerido') {
                        htmlItem += '<button class="button button-primary button-small btn-aceptar-match" data-id="' + match.id + '">Aceptar</button>';
                        htmlItem += '<button class="button button-small btn-contactar-match" data-id="' + match.id + '">Contactar</button>';
                        htmlItem += '<button class="button button-small btn-rechazar-match" data-id="' + match.id + '">Rechazar</button>';
                        htmlItem += '<button class="button button-small btn-descartar-match" data-id="' + match.id + '" style="color:#d63638;">Descartar</button>';
                    } else if (match.estado === 'aceptado') {
                        htmlItem += '<button class="button button-small btn-contactar-match" data-id="' + match.id + '">Enviar mensaje</button>';
                        htmlItem += '<button class="button button-small btn-proceso-match" data-id="' + match.id + '">Marcar en proceso</button>';
                    } else if (match.estado === 'contactado' || match.estado === 'en_proceso') {
                        htmlItem += '<button class="button button-small btn-descartar-match" data-id="' + match.id + '" style="color:#d63638;">Cerrar</button>';
                    }

                    htmlItem += '</div>';

                    if (match.respuesta) {
                        htmlItem += '<div style="margin-top:8px;padding:8px;background:#f9fafb;border-radius:4px;font-size:13px;"><strong>Respuesta:</strong> ' + escapeHtml(match.respuesta) + '</div>';
                    }

                    htmlItem += '</div>';
                    $lista.append(htmlItem);
                });
            });
    }

    $(document).on('click', '#btn-buscar-matches', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Buscando...');
        apiRequest('/matches', 'POST', {})
            .done(function(resp) {
                showNotice(resp.message);
                cargarMatches();
            })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); })
            .always(function() { $btn.prop('disabled', false).text('Buscar nuevos matches'); });
    });

    $(document).on('change', '#match-filtro-estado', function() { cargarMatches(); });

    $(document).on('click', '.btn-aceptar-match', function() {
        apiRequest('/matches/' + $(this).data('id'), 'PUT', { estado: 'aceptado' })
            .done(function() { showNotice('Match aceptado'); cargarMatches(); });
    });

    $(document).on('click', '.btn-rechazar-match', function() {
        apiRequest('/matches/' + $(this).data('id'), 'PUT', { estado: 'rechazado' })
            .done(function() { showNotice('Match rechazado'); cargarMatches(); });
    });

    $(document).on('click', '.btn-proceso-match', function() {
        apiRequest('/matches/' + $(this).data('id'), 'PUT', { estado: 'en_proceso' })
            .done(function() { showNotice('Match en proceso'); cargarMatches(); });
    });

    $(document).on('click', '.btn-descartar-match', function() {
        var matchId = $(this).data('id');
        confirmarEliminacion(function() {
            apiRequest('/matches/' + matchId, 'DELETE')
                .done(function() { showNotice('Match descartado'); cargarMatches(); });
        });
    });

    $(document).on('click', '.btn-contactar-match', function() {
        var matchId = $(this).data('id');
        var mensaje = prompt('Mensaje para el otro nodo:');
        if (mensaje === null) return;
        apiRequest('/matches/' + matchId + '/contact', 'POST', { mensaje: mensaje })
            .done(function() { showNotice('Mensaje enviado'); cargarMatches(); })
            .fail(function(xhr) { showNotice(xhr.responseJSON?.message || I18N.error, 'error'); });
    });

    if ($('#matches-lista').length) {
        cargarMatches();
    }


    // ─── Utilities ───
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

})(jQuery);
