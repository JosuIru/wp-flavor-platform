/**
 * JavaScript Frontend - Módulo de Incidencias Urbanas
 * Flavor Chat IA
 */

(function($) {
    'use strict';

    // Namespace global del módulo
    window.FlavorIncidencias = window.FlavorIncidencias || {};

    /**
     * Configuración del módulo
     */
    const CONFIG = {
        ajaxUrl: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
        nonce: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.nonce : '',
        mapboxToken: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.mapboxToken : '',
        defaultLat: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.defaultLat : 40.4168,
        defaultLng: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.defaultLng : -3.7038,
        defaultZoom: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.defaultZoom : 14,
        maxFotos: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.maxFotos : 5,
        maxFileSize: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.maxFileSize : 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
        i18n: typeof flavorIncidenciasConfig !== 'undefined' ? flavorIncidenciasConfig.i18n : {
            errorGenerico: 'Ha ocurrido un error. Por favor, intenta de nuevo.',
            confirmDelete: 'Esta accion no se puede deshacer.',
            cargando: 'Cargando...',
            enviando: 'Enviando...',
            ubicacionObtenida: 'Ubicacion obtenida correctamente',
            errorUbicacion: 'No se pudo obtener la ubicacion',
            archivoGrande: 'El archivo es demasiado grande',
            tipoNoPermitido: 'Tipo de archivo no permitido',
            maxFotosAlcanzado: 'Numero maximo de fotos alcanzado'
        }
    };

    /**
     * Estado de la aplicación
     */
    const STATE = {
        mapaInstancia: null,
        marcadores: [],
        fotosSubidas: [],
        ubicacionActual: null,
        filtrosActivos: {
            categoria: '',
            estado: '',
            prioridad: '',
            busqueda: ''
        },
        paginaActual: 1,
        cargando: false
    };

    /**
     * Inicialización del módulo
     */
    FlavorIncidencias.init = function() {
        this.initFormularioReporte();
        this.initUploadFotos();
        this.initMapa();
        this.initFiltros();
        this.initVotos();
        this.initComentarios();
        this.initModales();
        this.initCategoriasSelector();
        this.initAccionesAdmin();
        this.initInfiniteScroll();
    };

    /**
     * Inicializar formulario de reporte
     */
    FlavorIncidencias.initFormularioReporte = function() {
        const formularioReporte = $('#incidencias-form-reportar');

        if (!formularioReporte.length) return;

        formularioReporte.on('submit', function(evento) {
            evento.preventDefault();
            FlavorIncidencias.enviarReporte($(this));
        });

        // Validación en tiempo real
        formularioReporte.find('input, textarea, select').on('blur', function() {
            FlavorIncidencias.validarCampo($(this));
        });

        // Obtener ubicación automáticamente
        if (navigator.geolocation) {
            $('#btn-obtener-ubicacion').on('click', function() {
                FlavorIncidencias.obtenerUbicacionActual();
            });
        } else {
            $('#btn-obtener-ubicacion').hide();
        }
    };

    /**
     * Enviar reporte de incidencia
     */
    FlavorIncidencias.enviarReporte = function(formulario) {
        if (STATE.cargando) return;

        const datosFormulario = new FormData(formulario[0]);
        datosFormulario.append('action', 'incidencias_reportar');
        datosFormulario.append('nonce', CONFIG.nonce);

        // Agregar fotos
        STATE.fotosSubidas.forEach((foto, indice) => {
            datosFormulario.append('fotos[' + indice + ']', foto.file);
        });

        // Agregar coordenadas si están disponibles
        if (STATE.ubicacionActual) {
            datosFormulario.append('latitud', STATE.ubicacionActual.lat);
            datosFormulario.append('longitud', STATE.ubicacionActual.lng);
        }

        const botonSubmit = formulario.find('button[type="submit"]');
        const textoOriginal = botonSubmit.text();

        STATE.cargando = true;
        botonSubmit.prop('disabled', true).text(CONFIG.i18n.enviando);

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: datosFormulario,
            processData: false,
            contentType: false,
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorIncidencias.mostrarAlerta('success', 'Incidencia Reportada', respuesta.data.mensaje);
                    formulario[0].reset();
                    STATE.fotosSubidas = [];
                    FlavorIncidencias.actualizarPreviews();

                    // Redireccionar al detalle
                    if (respuesta.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = respuesta.data.redirect_url;
                        }, 2000);
                    }
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error || CONFIG.i18n.errorGenerico);
                }
            },
            error: function() {
                FlavorIncidencias.mostrarAlerta('error', 'Error', CONFIG.i18n.errorGenerico);
            },
            complete: function() {
                STATE.cargando = false;
                botonSubmit.prop('disabled', false).text(textoOriginal);
            }
        });
    };

    /**
     * Validar campo individual
     */
    FlavorIncidencias.validarCampo = function(campo) {
        const grupoFormulario = campo.closest('.incidencias-form-group');
        const valor = campo.val().trim();
        const esRequerido = campo.prop('required');

        grupoFormulario.removeClass('has-error has-success');

        if (esRequerido && !valor) {
            grupoFormulario.addClass('has-error');
            return false;
        }

        grupoFormulario.addClass('has-success');
        return true;
    };

    /**
     * Inicializar upload de fotos
     */
    FlavorIncidencias.initUploadFotos = function() {
        const areaUpload = $('.incidencias-upload-area');
        const inputArchivo = $('#incidencias-fotos-input');

        if (!areaUpload.length) return;

        // Click para abrir selector
        areaUpload.on('click', function() {
            inputArchivo.trigger('click');
        });

        // Cambio de archivos
        inputArchivo.on('change', function() {
            FlavorIncidencias.procesarArchivos(this.files);
        });

        // Drag and drop
        areaUpload.on('dragover dragenter', function(evento) {
            evento.preventDefault();
            evento.stopPropagation();
            $(this).addClass('dragover');
        });

        areaUpload.on('dragleave dragend drop', function(evento) {
            evento.preventDefault();
            evento.stopPropagation();
            $(this).removeClass('dragover');
        });

        areaUpload.on('drop', function(evento) {
            const archivos = evento.originalEvent.dataTransfer.files;
            FlavorIncidencias.procesarArchivos(archivos);
        });

        // Eliminar foto
        $(document).on('click', '.incidencias-preview-item .remove-btn', function() {
            const indice = $(this).closest('.incidencias-preview-item').data('index');
            FlavorIncidencias.eliminarFoto(indice);
        });
    };

    /**
     * Procesar archivos seleccionados
     */
    FlavorIncidencias.procesarArchivos = function(archivos) {
        for (let i = 0; i < archivos.length; i++) {
            const archivo = archivos[i];

            // Verificar cantidad máxima
            if (STATE.fotosSubidas.length >= CONFIG.maxFotos) {
                FlavorIncidencias.mostrarAlerta('warning', 'Aviso', CONFIG.i18n.maxFotosAlcanzado);
                break;
            }

            // Verificar tipo
            if (!CONFIG.allowedTypes.includes(archivo.type)) {
                FlavorIncidencias.mostrarAlerta('warning', 'Aviso', CONFIG.i18n.tipoNoPermitido + ': ' + archivo.name);
                continue;
            }

            // Verificar tamaño
            if (archivo.size > CONFIG.maxFileSize) {
                FlavorIncidencias.mostrarAlerta('warning', 'Aviso', CONFIG.i18n.archivoGrande + ': ' + archivo.name);
                continue;
            }

            // Crear preview
            const lector = new FileReader();
            lector.onload = function(evento) {
                STATE.fotosSubidas.push({
                    file: archivo,
                    preview: evento.target.result,
                    name: archivo.name
                });
                FlavorIncidencias.actualizarPreviews();
            };
            lector.readAsDataURL(archivo);
        }
    };

    /**
     * Actualizar previews de fotos
     */
    FlavorIncidencias.actualizarPreviews = function() {
        const contenedor = $('.incidencias-preview-container');
        contenedor.empty();

        STATE.fotosSubidas.forEach((foto, indice) => {
            const elementoPreview = $('<div class="incidencias-preview-item" data-index="' + indice + '">' +
                '<img src="' + foto.preview + '" alt="' + foto.name + '">' +
                '<button type="button" class="remove-btn" aria-label="Eliminar">&times;</button>' +
                '</div>');
            contenedor.append(elementoPreview);
        });

        // Actualizar contador
        const contador = $('.incidencias-fotos-count');
        if (contador.length) {
            contador.text(STATE.fotosSubidas.length + '/' + CONFIG.maxFotos);
        }
    };

    /**
     * Eliminar foto del listado
     */
    FlavorIncidencias.eliminarFoto = function(indice) {
        STATE.fotosSubidas.splice(indice, 1);
        FlavorIncidencias.actualizarPreviews();
    };

    /**
     * Inicializar mapa
     */
    FlavorIncidencias.initMapa = function() {
        const contenedorMapa = $('#incidencias-mapa');

        if (!contenedorMapa.length) return;

        // Verificar si hay librería de mapas disponible
        if (typeof L !== 'undefined') {
            FlavorIncidencias.initMapaLeaflet(contenedorMapa);
        } else if (typeof mapboxgl !== 'undefined' && CONFIG.mapboxToken) {
            FlavorIncidencias.initMapaMapbox(contenedorMapa);
        } else {
            // Fallback: mostrar mapa estático o mensaje
            contenedorMapa.html('<div class="incidencias-mapa-fallback">' +
                '<p>Mapa no disponible. Por favor, introduce la direccion manualmente.</p>' +
                '</div>');
        }
    };

    /**
     * Inicializar mapa con Leaflet
     */
    FlavorIncidencias.initMapaLeaflet = function(contenedor) {
        const mapa = L.map(contenedor[0]).setView([CONFIG.defaultLat, CONFIG.defaultLng], CONFIG.defaultZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapa);

        STATE.mapaInstancia = mapa;

        // Modo reportar: permitir seleccionar ubicación
        if (contenedor.hasClass('incidencias-mapa-reportar')) {
            let marcadorSeleccion = null;

            mapa.on('click', function(evento) {
                const coordenadas = evento.latlng;

                if (marcadorSeleccion) {
                    marcadorSeleccion.setLatLng(coordenadas);
                } else {
                    marcadorSeleccion = L.marker(coordenadas, {
                        draggable: true
                    }).addTo(mapa);

                    marcadorSeleccion.on('dragend', function() {
                        const posicion = marcadorSeleccion.getLatLng();
                        FlavorIncidencias.actualizarUbicacionSeleccionada(posicion.lat, posicion.lng);
                    });
                }

                FlavorIncidencias.actualizarUbicacionSeleccionada(coordenadas.lat, coordenadas.lng);
            });
        }

        // Modo visualización: cargar incidencias
        if (contenedor.hasClass('incidencias-mapa-visualizar')) {
            FlavorIncidencias.cargarIncidenciasMapa();
        }
    };

    /**
     * Inicializar mapa con Mapbox
     */
    FlavorIncidencias.initMapaMapbox = function(contenedor) {
        mapboxgl.accessToken = CONFIG.mapboxToken;

        const mapa = new mapboxgl.Map({
            container: contenedor[0],
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [CONFIG.defaultLng, CONFIG.defaultLat],
            zoom: CONFIG.defaultZoom
        });

        mapa.addControl(new mapboxgl.NavigationControl());

        STATE.mapaInstancia = mapa;

        // Modo reportar
        if (contenedor.hasClass('incidencias-mapa-reportar')) {
            let marcadorSeleccion = null;

            mapa.on('click', function(evento) {
                const coordenadas = evento.lngLat;

                if (marcadorSeleccion) {
                    marcadorSeleccion.setLngLat(coordenadas);
                } else {
                    marcadorSeleccion = new mapboxgl.Marker({ draggable: true })
                        .setLngLat(coordenadas)
                        .addTo(mapa);

                    marcadorSeleccion.on('dragend', function() {
                        const lngLat = marcadorSeleccion.getLngLat();
                        FlavorIncidencias.actualizarUbicacionSeleccionada(lngLat.lat, lngLat.lng);
                    });
                }

                FlavorIncidencias.actualizarUbicacionSeleccionada(coordenadas.lat, coordenadas.lng);
            });
        }

        // Modo visualización
        if (contenedor.hasClass('incidencias-mapa-visualizar')) {
            mapa.on('load', function() {
                FlavorIncidencias.cargarIncidenciasMapa();
            });
        }
    };

    /**
     * Actualizar ubicación seleccionada
     */
    FlavorIncidencias.actualizarUbicacionSeleccionada = function(latitud, longitud) {
        STATE.ubicacionActual = { lat: latitud, lng: longitud };

        $('#incidencias-latitud').val(latitud);
        $('#incidencias-longitud').val(longitud);

        // Geocodificación inversa para obtener dirección
        FlavorIncidencias.obtenerDireccion(latitud, longitud);
    };

    /**
     * Obtener dirección desde coordenadas
     */
    FlavorIncidencias.obtenerDireccion = function(latitud, longitud) {
        const urlNominatim = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + latitud + '&lon=' + longitud;

        $.ajax({
            url: urlNominatim,
            type: 'GET',
            success: function(respuesta) {
                if (respuesta && respuesta.display_name) {
                    const direccion = respuesta.display_name;
                    $('#incidencias-direccion').val(direccion);
                    $('.incidencias-ubicacion-actual .direccion').text(direccion);
                }
            }
        });
    };

    /**
     * Obtener ubicación actual del dispositivo
     */
    FlavorIncidencias.obtenerUbicacionActual = function() {
        const botonUbicacion = $('#btn-obtener-ubicacion');
        const textoOriginal = botonUbicacion.html();

        botonUbicacion.prop('disabled', true).html('<span class="incidencias-spinner-sm"></span>');

        navigator.geolocation.getCurrentPosition(
            function(posicion) {
                const latitud = posicion.coords.latitude;
                const longitud = posicion.coords.longitude;

                FlavorIncidencias.actualizarUbicacionSeleccionada(latitud, longitud);

                // Centrar mapa
                if (STATE.mapaInstancia) {
                    if (typeof L !== 'undefined') {
                        STATE.mapaInstancia.setView([latitud, longitud], 16);
                        L.marker([latitud, longitud]).addTo(STATE.mapaInstancia);
                    } else if (typeof mapboxgl !== 'undefined') {
                        STATE.mapaInstancia.flyTo({
                            center: [longitud, latitud],
                            zoom: 16
                        });
                    }
                }

                FlavorIncidencias.mostrarAlerta('success', 'Ubicacion', CONFIG.i18n.ubicacionObtenida);
                botonUbicacion.prop('disabled', false).html(textoOriginal);
            },
            function(error) {
                FlavorIncidencias.mostrarAlerta('error', 'Error', CONFIG.i18n.errorUbicacion);
                botonUbicacion.prop('disabled', false).html(textoOriginal);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    };

    /**
     * Cargar incidencias en el mapa
     */
    FlavorIncidencias.cargarIncidenciasMapa = function() {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_obtener_mapa',
                nonce: CONFIG.nonce,
                categoria: STATE.filtrosActivos.categoria,
                estado: STATE.filtrosActivos.estado
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.data.incidencias) {
                    FlavorIncidencias.mostrarMarcadores(respuesta.data.incidencias);
                }
            }
        });
    };

    /**
     * Mostrar marcadores en el mapa
     */
    FlavorIncidencias.mostrarMarcadores = function(incidencias) {
        // Limpiar marcadores anteriores
        STATE.marcadores.forEach(function(marcador) {
            if (typeof L !== 'undefined') {
                STATE.mapaInstancia.removeLayer(marcador);
            } else {
                marcador.remove();
            }
        });
        STATE.marcadores = [];

        incidencias.forEach(function(incidencia) {
            if (!incidencia.latitud || !incidencia.longitud) return;

            const contenidoPopup = FlavorIncidencias.crearContenidoPopup(incidencia);
            let marcador;

            if (typeof L !== 'undefined') {
                // Leaflet
                const iconoPersonalizado = L.divIcon({
                    className: 'incidencias-marker ' + incidencia.estado,
                    html: '<span class="dashicons dashicons-warning"></span>',
                    iconSize: [40, 40]
                });

                marcador = L.marker([incidencia.latitud, incidencia.longitud], {
                    icon: iconoPersonalizado
                }).addTo(STATE.mapaInstancia);

                marcador.bindPopup(contenidoPopup);
            } else if (typeof mapboxgl !== 'undefined') {
                // Mapbox
                const elementoMarcador = document.createElement('div');
                elementoMarcador.className = 'incidencias-marker ' + incidencia.estado;
                elementoMarcador.innerHTML = '<span class="dashicons dashicons-warning"></span>';

                const popup = new mapboxgl.Popup({ offset: 25 })
                    .setHTML(contenidoPopup);

                marcador = new mapboxgl.Marker(elementoMarcador)
                    .setLngLat([incidencia.longitud, incidencia.latitud])
                    .setPopup(popup)
                    .addTo(STATE.mapaInstancia);
            }

            STATE.marcadores.push(marcador);
        });

        // Ajustar vista a los marcadores
        if (STATE.marcadores.length > 0 && typeof L !== 'undefined') {
            const grupo = L.featureGroup(STATE.marcadores);
            STATE.mapaInstancia.fitBounds(grupo.getBounds().pad(0.1));
        }
    };

    /**
     * Crear contenido del popup
     */
    FlavorIncidencias.crearContenidoPopup = function(incidencia) {
        return '<div class="incidencias-popup">' +
            '<div class="incidencias-popup-header">' +
                '<span class="incidencias-badge incidencias-badge-' + incidencia.estado + '">' + incidencia.estado_texto + '</span>' +
            '</div>' +
            '<h4 class="incidencias-popup-titulo">' + incidencia.titulo + '</h4>' +
            '<p class="incidencias-popup-categoria">' + incidencia.categoria_texto + '</p>' +
            '<p class="incidencias-popup-direccion">' + (incidencia.direccion || '') + '</p>' +
            '<div class="incidencias-popup-footer">' +
                '<span class="fecha">' + incidencia.fecha + '</span>' +
                '<a href="' + incidencia.url_detalle + '" class="incidencias-btn incidencias-btn-sm incidencias-btn-primary">Ver detalle</a>' +
            '</div>' +
        '</div>';
    };

    /**
     * Inicializar filtros
     */
    FlavorIncidencias.initFiltros = function() {
        const contenedorFiltros = $('.incidencias-filtros');

        if (!contenedorFiltros.length) return;

        // Cambio en selects
        contenedorFiltros.find('select').on('change', function() {
            const nombreFiltro = $(this).attr('name');
            const valor = $(this).val();
            STATE.filtrosActivos[nombreFiltro] = valor;
            FlavorIncidencias.aplicarFiltros();
        });

        // Búsqueda
        let timeoutBusqueda;
        contenedorFiltros.find('input[name="busqueda"]').on('input', function() {
            clearTimeout(timeoutBusqueda);
            const valor = $(this).val();
            timeoutBusqueda = setTimeout(function() {
                STATE.filtrosActivos.busqueda = valor;
                FlavorIncidencias.aplicarFiltros();
            }, 300);
        });

        // Botón limpiar filtros
        contenedorFiltros.find('.btn-limpiar-filtros').on('click', function() {
            STATE.filtrosActivos = {
                categoria: '',
                estado: '',
                prioridad: '',
                busqueda: ''
            };
            contenedorFiltros.find('select').val('');
            contenedorFiltros.find('input[name="busqueda"]').val('');
            FlavorIncidencias.aplicarFiltros();
        });
    };

    /**
     * Aplicar filtros
     */
    FlavorIncidencias.aplicarFiltros = function() {
        STATE.paginaActual = 1;
        FlavorIncidencias.cargarIncidencias(true);

        // Actualizar mapa si existe
        if (STATE.mapaInstancia) {
            FlavorIncidencias.cargarIncidenciasMapa();
        }
    };

    /**
     * Cargar incidencias con filtros
     */
    FlavorIncidencias.cargarIncidencias = function(reemplazar) {
        if (STATE.cargando) return;

        const contenedorGrid = $('.incidencias-grid');
        if (!contenedorGrid.length) return;

        STATE.cargando = true;

        if (reemplazar) {
            contenedorGrid.html('<div class="incidencias-loading"><div class="incidencias-spinner"></div><p>' + CONFIG.i18n.cargando + '</p></div>');
        }

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_listar',
                nonce: CONFIG.nonce,
                categoria: STATE.filtrosActivos.categoria,
                estado: STATE.filtrosActivos.estado,
                prioridad: STATE.filtrosActivos.prioridad,
                busqueda: STATE.filtrosActivos.busqueda,
                pagina: STATE.paginaActual
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    if (reemplazar) {
                        contenedorGrid.html('');
                    }

                    if (respuesta.data.incidencias.length === 0 && reemplazar) {
                        contenedorGrid.html(FlavorIncidencias.crearEstadoVacio());
                    } else {
                        respuesta.data.incidencias.forEach(function(incidencia) {
                            contenedorGrid.append(FlavorIncidencias.crearTarjetaIncidencia(incidencia));
                        });
                    }

                    // Actualizar paginación
                    STATE.paginaActual = respuesta.data.pagina_actual;
                    $('.incidencias-total-count').text(respuesta.data.total);
                }
            },
            complete: function() {
                STATE.cargando = false;
            }
        });
    };

    /**
     * Crear tarjeta de incidencia
     */
    FlavorIncidencias.crearTarjetaIncidencia = function(incidencia) {
        const imagen = incidencia.imagen_principal
            ? '<img src="' + incidencia.imagen_principal + '" alt="' + incidencia.titulo + '">'
            : '<div class="no-image"><span class="dashicons dashicons-format-image"></span></div>';

        return '<article class="incidencias-card">' +
            '<div class="incidencias-card-image">' +
                imagen +
                '<span class="incidencias-card-estado incidencias-badge incidencias-badge-' + incidencia.estado + '">' + incidencia.estado_texto + '</span>' +
            '</div>' +
            '<div class="incidencias-card-body">' +
                '<span class="incidencias-card-categoria"><span class="dashicons dashicons-category"></span>' + incidencia.categoria_texto + '</span>' +
                '<h3 class="incidencias-card-titulo"><a href="' + incidencia.url_detalle + '">' + incidencia.titulo + '</a></h3>' +
                '<p class="incidencias-card-descripcion">' + incidencia.descripcion_corta + '</p>' +
                '<p class="incidencias-card-ubicacion"><span class="dashicons dashicons-location"></span>' + (incidencia.direccion || 'Sin ubicacion') + '</p>' +
                '<div class="incidencias-card-meta">' +
                    '<span class="incidencias-card-fecha">' + incidencia.fecha + '</span>' +
                    '<span class="incidencias-card-votos"><span class="dashicons dashicons-thumbs-up"></span>' + incidencia.votos + '</span>' +
                '</div>' +
            '</div>' +
        '</article>';
    };

    /**
     * Crear estado vacío
     */
    FlavorIncidencias.crearEstadoVacio = function() {
        return '<div class="incidencias-empty">' +
            '<div class="incidencias-empty-icon"><span class="dashicons dashicons-search"></span></div>' +
            '<h3 class="incidencias-empty-titulo">No se encontraron incidencias</h3>' +
            '<p class="incidencias-empty-descripcion">Prueba a cambiar los filtros o reporta una nueva incidencia.</p>' +
            '<a href="#reportar" class="incidencias-btn incidencias-btn-primary">Reportar Incidencia</a>' +
        '</div>';
    };

    /**
     * Inicializar votos
     */
    FlavorIncidencias.initVotos = function() {
        $(document).on('click', '.incidencias-btn-votar', function(evento) {
            evento.preventDefault();

            const boton = $(this);
            const idIncidencia = boton.data('id');

            if (boton.hasClass('voted')) {
                FlavorIncidencias.quitarVoto(idIncidencia, boton);
            } else {
                FlavorIncidencias.agregarVoto(idIncidencia, boton);
            }
        });
    };

    /**
     * Agregar voto a incidencia
     */
    FlavorIncidencias.agregarVoto = function(idIncidencia, boton) {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_votar',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    boton.addClass('voted');
                    const contador = boton.find('.count');
                    contador.text(parseInt(contador.text()) + 1);
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error);
                }
            }
        });
    };

    /**
     * Quitar voto de incidencia
     */
    FlavorIncidencias.quitarVoto = function(idIncidencia, boton) {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_quitar_voto',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    boton.removeClass('voted');
                    const contador = boton.find('.count');
                    contador.text(Math.max(0, parseInt(contador.text()) - 1));
                }
            }
        });
    };

    /**
     * Inicializar comentarios
     */
    FlavorIncidencias.initComentarios = function() {
        const formularioComentario = $('#incidencias-comentario-form');

        if (!formularioComentario.length) return;

        formularioComentario.on('submit', function(evento) {
            evento.preventDefault();
            FlavorIncidencias.enviarComentario($(this));
        });
    };

    /**
     * Enviar comentario
     */
    FlavorIncidencias.enviarComentario = function(formulario) {
        const idIncidencia = formulario.data('incidencia-id');
        const contenido = formulario.find('textarea').val().trim();

        if (!contenido) return;

        const botonEnviar = formulario.find('button[type="submit"]');
        botonEnviar.prop('disabled', true);

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_comentar',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia,
                contenido: contenido
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    formulario.find('textarea').val('');
                    FlavorIncidencias.agregarComentarioDOM(respuesta.data.comentario);
                    FlavorIncidencias.mostrarAlerta('success', 'Comentario enviado', 'Tu comentario ha sido publicado.');
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error);
                }
            },
            complete: function() {
                botonEnviar.prop('disabled', false);
            }
        });
    };

    /**
     * Agregar comentario al DOM
     */
    FlavorIncidencias.agregarComentarioDOM = function(comentario) {
        const listaComentarios = $('.incidencias-comentarios-lista');
        const inicialNombre = comentario.autor.charAt(0).toUpperCase();

        const elementoComentario = $('<div class="incidencias-comentario-item">' +
            '<div class="incidencias-comentario-avatar">' + inicialNombre + '</div>' +
            '<div class="incidencias-comentario-content">' +
                '<div class="incidencias-comentario-header">' +
                    '<span class="incidencias-comentario-autor">' + comentario.autor + '</span>' +
                    '<span class="incidencias-comentario-fecha">' + comentario.fecha + '</span>' +
                '</div>' +
                '<p class="incidencias-comentario-texto">' + comentario.contenido + '</p>' +
            '</div>' +
        '</div>');

        listaComentarios.prepend(elementoComentario);
    };

    /**
     * Inicializar selector de categorías
     */
    FlavorIncidencias.initCategoriasSelector = function() {
        $('.incidencias-categoria-item').on('click', function() {
            const radio = $(this).find('input[type="radio"]');
            radio.prop('checked', true);

            $('.incidencias-categoria-item').removeClass('selected');
            $(this).addClass('selected');
        });
    };

    /**
     * Inicializar modales
     */
    FlavorIncidencias.initModales = function() {
        // Abrir modal
        $('[data-modal]').on('click', function(evento) {
            evento.preventDefault();
            const idModal = $(this).data('modal');
            FlavorIncidencias.abrirModal(idModal);
        });

        // Cerrar modal
        $(document).on('click', '.incidencias-modal-close, .incidencias-modal-overlay', function(evento) {
            if (evento.target === this) {
                FlavorIncidencias.cerrarModal();
            }
        });

        // Cerrar con ESC
        $(document).on('keydown', function(evento) {
            if (evento.key === 'Escape') {
                FlavorIncidencias.cerrarModal();
            }
        });
    };

    /**
     * Abrir modal
     */
    FlavorIncidencias.abrirModal = function(idModal) {
        const modal = $('#' + idModal);
        if (modal.length) {
            modal.addClass('active');
            $('body').addClass('modal-open');
        }
    };

    /**
     * Cerrar modal
     */
    FlavorIncidencias.cerrarModal = function() {
        $('.incidencias-modal-overlay.active').removeClass('active');
        $('body').removeClass('modal-open');
    };

    /**
     * Inicializar acciones de administrador
     */
    FlavorIncidencias.initAccionesAdmin = function() {
        // Cambiar estado
        $(document).on('change', '.incidencias-admin-estado', function() {
            const idIncidencia = $(this).data('id');
            const nuevoEstado = $(this).val();
            FlavorIncidencias.cambiarEstado(idIncidencia, nuevoEstado);
        });

        // Cambiar prioridad
        $(document).on('change', '.incidencias-admin-prioridad', function() {
            const idIncidencia = $(this).data('id');
            const nuevaPrioridad = $(this).val();
            FlavorIncidencias.cambiarPrioridad(idIncidencia, nuevaPrioridad);
        });

        // Asignar responsable
        $(document).on('change', '.incidencias-admin-asignar', function() {
            const idIncidencia = $(this).data('id');
            const idUsuario = $(this).val();
            FlavorIncidencias.asignarResponsable(idIncidencia, idUsuario);
        });
    };

    /**
     * Cambiar estado de incidencia
     */
    FlavorIncidencias.cambiarEstado = function(idIncidencia, nuevoEstado) {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_cambiar_estado',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia,
                estado: nuevoEstado
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorIncidencias.mostrarAlerta('success', 'Estado actualizado', 'El estado de la incidencia ha sido actualizado.');
                    // Actualizar UI
                    $('.incidencias-badge-estado-' + idIncidencia)
                        .removeClass()
                        .addClass('incidencias-badge incidencias-badge-' + nuevoEstado)
                        .text(respuesta.data.estado_texto);
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error);
                }
            }
        });
    };

    /**
     * Cambiar prioridad de incidencia
     */
    FlavorIncidencias.cambiarPrioridad = function(idIncidencia, nuevaPrioridad) {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_cambiar_prioridad',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia,
                prioridad: nuevaPrioridad
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorIncidencias.mostrarAlerta('success', 'Prioridad actualizada', 'La prioridad ha sido actualizada.');
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error);
                }
            }
        });
    };

    /**
     * Asignar responsable
     */
    FlavorIncidencias.asignarResponsable = function(idIncidencia, idUsuario) {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'incidencias_asignar',
                nonce: CONFIG.nonce,
                incidencia_id: idIncidencia,
                usuario_id: idUsuario
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorIncidencias.mostrarAlerta('success', 'Responsable asignado', 'El responsable ha sido asignado correctamente.');
                } else {
                    FlavorIncidencias.mostrarAlerta('error', 'Error', respuesta.data.error);
                }
            }
        });
    };

    /**
     * Inicializar infinite scroll
     */
    FlavorIncidencias.initInfiniteScroll = function() {
        const contenedorGrid = $('.incidencias-grid');
        if (!contenedorGrid.length || !contenedorGrid.data('infinite-scroll')) return;

        $(window).on('scroll', function() {
            if (STATE.cargando) return;

            const alturaDocumento = $(document).height();
            const posicionScroll = $(window).scrollTop() + $(window).height();

            if (posicionScroll >= alturaDocumento - 200) {
                STATE.paginaActual++;
                FlavorIncidencias.cargarIncidencias(false);
            }
        });
    };

    /**
     * Mostrar alerta
     */
    FlavorIncidencias.mostrarAlerta = function(tipo, titulo, mensaje) {
        const iconos = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-dismiss',
            warning: 'dashicons-warning',
            info: 'dashicons-info'
        };

        const alerta = $('<div class="incidencias-alert incidencias-alert-' + tipo + '">' +
            '<span class="dashicons ' + iconos[tipo] + '"></span>' +
            '<div class="incidencias-alert-content">' +
                '<strong class="incidencias-alert-titulo">' + titulo + '</strong>' +
                '<p>' + mensaje + '</p>' +
            '</div>' +
            '<button class="incidencias-alert-close">&times;</button>' +
        '</div>');

        // Añadir al contenedor de alertas o al body
        let contenedorAlertas = $('.incidencias-alertas-container');
        if (!contenedorAlertas.length) {
            contenedorAlertas = $('<div class="incidencias-alertas-container" style="position:fixed;top:20px;right:20px;z-index:10000;max-width:400px;"></div>');
            $('body').append(contenedorAlertas);
        }

        contenedorAlertas.append(alerta);

        // Auto cerrar después de 5 segundos
        setTimeout(function() {
            alerta.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Cerrar al hacer clic
        alerta.find('.incidencias-alert-close').on('click', function() {
            alerta.fadeOut(300, function() {
                $(this).remove();
            });
        });
    };

    /**
     * Inicialización cuando el DOM está listo
     */
    $(document).ready(function() {
        FlavorIncidencias.init();
    });

})(jQuery);
