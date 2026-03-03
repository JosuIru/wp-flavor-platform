/**
 * Reciclaje Module JavaScript
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // Namespace global
    window.FlavorReciclaje = window.FlavorReciclaje || {};

    const reciclajeConfig = typeof flavorReciclajeData !== 'undefined'
        ? flavorReciclajeData
        : (typeof flavorReciclaje !== 'undefined' ? flavorReciclaje : {});

    /**
     * Configuracion global
     */
    FlavorReciclaje.config = {
        ajaxUrl: reciclajeConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: reciclajeConfig.nonce || '',
        defaultLat: 40.4168,
        defaultLng: -3.7038,
        defaultZoom: 13,
        mapTileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        mapAttribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    };

    /**
     * Estado de la aplicacion
     */
    FlavorReciclaje.state = {
        mapa: null,
        marcadores: [],
        marcadorGrupo: null,
        filtroActivo: 'todos',
        puntosData: [],
        usuarioUbicacion: null,
        calendarioMes: new Date().getMonth(),
        calendarioAnio: new Date().getFullYear()
    };

    /**
     * Iconos para marcadores del mapa
     */
    FlavorReciclaje.iconos = {
        punto_limpio: {
            icon: 'recycle',
            color: '#2e7d32'
        },
        contenedor_comunitario: {
            icon: 'trash',
            color: '#4caf50'
        },
        centro_acopio: {
            icon: 'warehouse',
            color: '#1565c0'
        },
        movil: {
            icon: 'truck',
            color: '#ff9800'
        }
    };

    /**
     * Colores por tipo de material
     */
    FlavorReciclaje.coloresMaterial = {
        papel: '#2196f3',
        plastico: '#ffeb3b',
        vidrio: '#4caf50',
        organico: '#795548',
        electronico: '#9c27b0',
        ropa: '#e91e63',
        aceite: '#ff5722',
        pilas: '#f44336'
    };

    /**
     * Inicializacion del modulo Mapa
     */
    FlavorReciclaje.initMapa = function() {
        var contenedorMapa = document.getElementById('reciclaje-mapa');
        if (!contenedorMapa) return;

        // Verificar si Leaflet esta disponible
        if (typeof L === 'undefined') {
            console.error('Leaflet no esta cargado');
            return;
        }

        // Crear mapa
        FlavorReciclaje.state.mapa = L.map('reciclaje-mapa').setView(
            [FlavorReciclaje.config.defaultLat, FlavorReciclaje.config.defaultLng],
            FlavorReciclaje.config.defaultZoom
        );

        // Agregar capa de tiles
        L.tileLayer(FlavorReciclaje.config.mapTileUrl, {
            attribution: FlavorReciclaje.config.mapAttribution,
            maxZoom: 19
        }).addTo(FlavorReciclaje.state.mapa);

        // Crear grupo de marcadores
        FlavorReciclaje.state.marcadorGrupo = L.featureGroup().addTo(FlavorReciclaje.state.mapa);

        // Obtener ubicacion del usuario
        FlavorReciclaje.obtenerUbicacionUsuario();

        // Cargar puntos de reciclaje
        FlavorReciclaje.cargarPuntosReciclaje();

        // Inicializar controles
        FlavorReciclaje.initControlesMapa();
    };

    /**
     * Obtener ubicacion del usuario
     */
    FlavorReciclaje.obtenerUbicacionUsuario = function() {
        if (!navigator.geolocation) {
            console.log('Geolocalizacion no soportada');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(posicion) {
                var latitudUsuario = posicion.coords.latitude;
                var longitudUsuario = posicion.coords.longitude;

                FlavorReciclaje.state.usuarioUbicacion = {
                    lat: latitudUsuario,
                    lng: longitudUsuario
                };

                // Centrar mapa en ubicacion del usuario
                if (FlavorReciclaje.state.mapa) {
                    FlavorReciclaje.state.mapa.setView([latitudUsuario, longitudUsuario], 14);

                    // Agregar marcador de ubicacion del usuario
                    var marcadorUsuario = L.marker([latitudUsuario, longitudUsuario], {
                        icon: L.divIcon({
                            className: 'reciclaje-marcador-usuario',
                            html: '<div class="pulso"></div>',
                            iconSize: [20, 20]
                        })
                    }).addTo(FlavorReciclaje.state.mapa);

                    marcadorUsuario.bindPopup('<strong>Tu ubicacion</strong>');
                }

                // Recargar puntos con distancia
                FlavorReciclaje.cargarPuntosReciclaje();
            },
            function(error) {
                console.log('Error obteniendo ubicacion:', error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    };

    /**
     * Cargar puntos de reciclaje via AJAX
     */
    FlavorReciclaje.cargarPuntosReciclaje = function(filtroMaterial) {
        var parametrosAjax = {
            action: 'reciclaje_obtener_puntos',
            nonce: FlavorReciclaje.config.nonce,
            tipo_material: filtroMaterial || ''
        };

        if (FlavorReciclaje.state.usuarioUbicacion) {
            parametrosAjax.lat = FlavorReciclaje.state.usuarioUbicacion.lat;
            parametrosAjax.lng = FlavorReciclaje.state.usuarioUbicacion.lng;
        }

        $.ajax({
            url: FlavorReciclaje.config.ajaxUrl,
            type: 'POST',
            data: parametrosAjax,
            success: function(respuesta) {
                if (respuesta.success && respuesta.data.puntos) {
                    FlavorReciclaje.state.puntosData = respuesta.data.puntos;
                    FlavorReciclaje.mostrarMarcadores(respuesta.data.puntos);
                    FlavorReciclaje.actualizarListaPuntos(respuesta.data.puntos);
                }
            },
            error: function(xhr, estado, error) {
                console.error('Error cargando puntos:', error);
            }
        });
    };

    /**
     * Mostrar marcadores en el mapa
     */
    FlavorReciclaje.mostrarMarcadores = function(puntos) {
        // Limpiar marcadores existentes
        FlavorReciclaje.state.marcadorGrupo.clearLayers();
        FlavorReciclaje.state.marcadores = [];

        puntos.forEach(function(punto) {
            var configuracionIcono = FlavorReciclaje.iconos[punto.tipo] || FlavorReciclaje.iconos.contenedor_comunitario;

            var iconoPersonalizado = L.divIcon({
                className: 'reciclaje-marcador',
                html: '<div class="reciclaje-marcador-pin" style="background-color: ' + configuracionIcono.color + '">' +
                      '<span class="dashicons dashicons-location"></span></div>',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            });

            var marcador = L.marker([punto.lat, punto.lng], {
                icon: iconoPersonalizado
            });

            // Crear contenido del popup
            var materialesHtml = '';
            if (punto.materiales && punto.materiales.length > 0) {
                materialesHtml = '<div class="reciclaje-popup-materiales">';
                punto.materiales.forEach(function(material) {
                    materialesHtml += '<span class="reciclaje-popup-material">' + material + '</span>';
                });
                materialesHtml += '</div>';
            }

            var distanciaHtml = '';
            if (punto.distancia_km !== null) {
                distanciaHtml = '<p><strong>' + punto.distancia_km + ' km</strong> de tu ubicacion</p>';
            }

            var contenidoPopup =
                '<div class="reciclaje-popup">' +
                    '<div class="reciclaje-popup-title">' + punto.nombre + '</div>' +
                    '<span class="reciclaje-popup-tipo">' + FlavorReciclaje.formatearTipo(punto.tipo) + '</span>' +
                    '<p class="reciclaje-popup-direccion">' + punto.direccion + '</p>' +
                    materialesHtml +
                    (punto.horario ? '<p class="reciclaje-popup-horario"><strong>Horario:</strong> ' + punto.horario + '</p>' : '') +
                    distanciaHtml +
                    '<button class="reciclaje-popup-btn" onclick="FlavorReciclaje.comoLlegar(' + punto.lat + ', ' + punto.lng + ')">' +
                        'Como llegar' +
                    '</button>' +
                '</div>';

            marcador.bindPopup(contenidoPopup);

            // Guardar referencia al punto
            marcador.puntoData = punto;

            FlavorReciclaje.state.marcadorGrupo.addLayer(marcador);
            FlavorReciclaje.state.marcadores.push(marcador);
        });

        // Ajustar vista a todos los marcadores si hay alguno
        if (FlavorReciclaje.state.marcadores.length > 0) {
            var limites = FlavorReciclaje.state.marcadorGrupo.getBounds();
            if (limites.isValid()) {
                FlavorReciclaje.state.mapa.fitBounds(limites, { padding: [50, 50] });
            }
        }
    };

    /**
     * Actualizar lista de puntos en el sidebar
     */
    FlavorReciclaje.actualizarListaPuntos = function(puntos) {
        var contenedorLista = $('.reciclaje-puntos-lista');
        if (contenedorLista.length === 0) return;

        contenedorLista.empty();

        if (puntos.length === 0) {
            contenedorLista.html('<p class="reciclaje-sin-resultados">No se encontraron puntos de reciclaje.</p>');
            return;
        }

        puntos.forEach(function(punto) {
            var distanciaTexto = punto.distancia_km !== null ? punto.distancia_km + ' km' : '';

            var itemHtml =
                '<div class="reciclaje-punto-item" data-punto-id="' + punto.id + '">' +
                    '<div class="reciclaje-punto-icono">♻️</div>' +
                    '<div class="reciclaje-punto-info">' +
                        '<div class="reciclaje-punto-nombre">' + punto.nombre + '</div>' +
                        '<div class="reciclaje-punto-direccion">' + punto.direccion + '</div>' +
                        (distanciaTexto ? '<div class="reciclaje-punto-distancia">' + distanciaTexto + '</div>' : '') +
                    '</div>' +
                '</div>';

            contenedorLista.append(itemHtml);
        });
    };

    /**
     * Inicializar controles del mapa
     */
    FlavorReciclaje.initControlesMapa = function() {
        // Filtros por tipo de material
        $(document).on('click', '.reciclaje-filtro-btn', function() {
            var filtro = $(this).data('filtro');

            $('.reciclaje-filtro-btn').removeClass('active');
            $(this).addClass('active');

            FlavorReciclaje.state.filtroActivo = filtro;

            if (filtro === 'todos') {
                FlavorReciclaje.cargarPuntosReciclaje();
            } else {
                FlavorReciclaje.cargarPuntosReciclaje(filtro);
            }
        });

        // Busqueda por direccion
        $(document).on('submit', '.reciclaje-buscar-ubicacion', function(evento) {
            evento.preventDefault();
            var direccionBusqueda = $(this).find('input').val();
            if (direccionBusqueda) {
                FlavorReciclaje.buscarDireccion(direccionBusqueda);
            }
        });

        // Click en item de lista
        $(document).on('click', '.reciclaje-punto-item', function() {
            var idPunto = $(this).data('punto-id');
            FlavorReciclaje.centrarEnPunto(idPunto);
        });

        // Boton mi ubicacion
        $(document).on('click', '.reciclaje-btn-mi-ubicacion', function() {
            FlavorReciclaje.obtenerUbicacionUsuario();
        });
    };

    /**
     * Buscar direccion usando Nominatim
     */
    FlavorReciclaje.buscarDireccion = function(direccion) {
        var urlNominatim = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(direccion);

        $.ajax({
            url: urlNominatim,
            type: 'GET',
            success: function(resultados) {
                if (resultados && resultados.length > 0) {
                    var ubicacionEncontrada = resultados[0];
                    var latitudEncontrada = parseFloat(ubicacionEncontrada.lat);
                    var longitudEncontrada = parseFloat(ubicacionEncontrada.lon);

                    FlavorReciclaje.state.mapa.setView([latitudEncontrada, longitudEncontrada], 15);

                    // Actualizar ubicacion para calcular distancias
                    FlavorReciclaje.state.usuarioUbicacion = {
                        lat: latitudEncontrada,
                        lng: longitudEncontrada
                    };

                    FlavorReciclaje.cargarPuntosReciclaje();
                } else {
                    alert('No se encontro la direccion');
                }
            },
            error: function() {
                alert('Error buscando la direccion');
            }
        });
    };

    /**
     * Centrar mapa en un punto especifico
     */
    FlavorReciclaje.centrarEnPunto = function(idPunto) {
        var marcadorEncontrado = FlavorReciclaje.state.marcadores.find(function(marcador) {
            return marcador.puntoData && marcador.puntoData.id == idPunto;
        });

        if (marcadorEncontrado) {
            FlavorReciclaje.state.mapa.setView(marcadorEncontrado.getLatLng(), 16);
            marcadorEncontrado.openPopup();
        }
    };

    /**
     * Abrir direcciones en Google Maps
     */
    FlavorReciclaje.comoLlegar = function(latitud, longitud) {
        var urlGoogleMaps = 'https://www.google.com/maps/dir/?api=1&destination=' + latitud + ',' + longitud;
        window.open(urlGoogleMaps, '_blank');
    };

    /**
     * Formatear tipo de punto
     */
    FlavorReciclaje.formatearTipo = function(tipo) {
        var tiposFormateados = {
            'punto_limpio': 'Punto Limpio',
            'contenedor_comunitario': 'Contenedor Comunitario',
            'centro_acopio': 'Centro de Acopio',
            'movil': 'Punto Movil'
        };
        return tiposFormateados[tipo] || tipo;
    };

    /**
     * Modulo Calendario
     */
    FlavorReciclaje.initCalendario = function() {
        var contenedorCalendario = $('.reciclaje-calendario');
        if (contenedorCalendario.length === 0) return;

        FlavorReciclaje.renderizarCalendario();
        FlavorReciclaje.cargarRecogidas();
        FlavorReciclaje.initControlesCalendario();
    };

    /**
     * Renderizar calendario
     */
    FlavorReciclaje.renderizarCalendario = function() {
        var mesActual = FlavorReciclaje.state.calendarioMes;
        var anioActual = FlavorReciclaje.state.calendarioAnio;

        var nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        // Actualizar titulo del mes
        $('.reciclaje-calendario-mes').text(nombresMeses[mesActual] + ' ' + anioActual);

        // Obtener primer dia del mes y total de dias
        var primerDiaMes = new Date(anioActual, mesActual, 1);
        var ultimoDiaMes = new Date(anioActual, mesActual + 1, 0);
        var totalDiasMes = ultimoDiaMes.getDate();
        var primerDiaSemana = primerDiaMes.getDay();

        // Ajustar para que la semana empiece en lunes
        var inicioSemana = primerDiaSemana === 0 ? 6 : primerDiaSemana - 1;

        // Dias del mes anterior
        var mesAnterior = mesActual === 0 ? 11 : mesActual - 1;
        var anioMesAnterior = mesActual === 0 ? anioActual - 1 : anioActual;
        var diasMesAnterior = new Date(anioMesAnterior, mesAnterior + 1, 0).getDate();

        var contenedorDias = $('.reciclaje-calendario-dias');
        // Mantener solo los nombres de los dias
        contenedorDias.find('.reciclaje-calendario-dia').remove();

        var hoy = new Date();
        var diasCalendario = '';

        // Dias del mes anterior
        for (var diasAnteriores = inicioSemana - 1; diasAnteriores >= 0; diasAnteriores--) {
            var numeroDiaAnterior = diasMesAnterior - diasAnteriores;
            diasCalendario += '<div class="reciclaje-calendario-dia otro-mes">' +
                '<span class="reciclaje-calendario-dia-numero">' + numeroDiaAnterior + '</span>' +
            '</div>';
        }

        // Dias del mes actual
        for (var numeroDia = 1; numeroDia <= totalDiasMes; numeroDia++) {
            var esHoy = (numeroDia === hoy.getDate() && mesActual === hoy.getMonth() && anioActual === hoy.getFullYear());
            var claseHoy = esHoy ? ' hoy' : '';

            diasCalendario += '<div class="reciclaje-calendario-dia' + claseHoy + '" data-fecha="' + anioActual + '-' + (mesActual + 1) + '-' + numeroDia + '">' +
                '<span class="reciclaje-calendario-dia-numero">' + numeroDia + '</span>' +
                '<div class="reciclaje-calendario-eventos"></div>' +
            '</div>';
        }

        // Dias del mes siguiente
        var totalCeldas = inicioSemana + totalDiasMes;
        var celdasRestantes = totalCeldas % 7 === 0 ? 0 : 7 - (totalCeldas % 7);
        for (var diasSiguientes = 1; diasSiguientes <= celdasRestantes; diasSiguientes++) {
            diasCalendario += '<div class="reciclaje-calendario-dia otro-mes">' +
                '<span class="reciclaje-calendario-dia-numero">' + diasSiguientes + '</span>' +
            '</div>';
        }

        contenedorDias.append(diasCalendario);
    };

    /**
     * Cargar recogidas del mes
     */
    FlavorReciclaje.cargarRecogidas = function() {
        $.ajax({
            url: FlavorReciclaje.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reciclaje_obtener_recogidas',
                nonce: FlavorReciclaje.config.nonce,
                mes: FlavorReciclaje.state.calendarioMes + 1,
                anio: FlavorReciclaje.state.calendarioAnio
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.data.recogidas) {
                    FlavorReciclaje.mostrarRecogidas(respuesta.data.recogidas);
                    FlavorReciclaje.actualizarProximasRecogidas(respuesta.data.recogidas);
                }
            }
        });
    };

    /**
     * Mostrar recogidas en el calendario
     */
    FlavorReciclaje.mostrarRecogidas = function(recogidas) {
        recogidas.forEach(function(recogida) {
            var fechaRecogida = new Date(recogida.fecha);
            var selector = '[data-fecha="' + fechaRecogida.getFullYear() + '-' + (fechaRecogida.getMonth() + 1) + '-' + fechaRecogida.getDate() + '"]';
            var contenedorDia = $(selector).find('.reciclaje-calendario-eventos');

            if (contenedorDia.length) {
                var tipoMaterial = recogida.tipos_residuos[0] || 'general';
                contenedorDia.append(
                    '<div class="reciclaje-calendario-evento ' + tipoMaterial + '" title="' + recogida.zona + '">' +
                        recogida.zona +
                    '</div>'
                );
            }
        });
    };

    /**
     * Actualizar lista de proximas recogidas
     */
    FlavorReciclaje.actualizarProximasRecogidas = function(recogidas) {
        var contenedorProximas = $('.reciclaje-proximas-recogidas');
        if (contenedorProximas.length === 0) return;

        contenedorProximas.empty();

        var hoy = new Date();
        var proximasRecogidas = recogidas.filter(function(recogida) {
            return new Date(recogida.fecha) >= hoy;
        }).slice(0, 5);

        var nombresMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        proximasRecogidas.forEach(function(recogida) {
            var fechaRecogida = new Date(recogida.fecha);

            contenedorProximas.append(
                '<li class="reciclaje-recogida-item">' +
                    '<div class="reciclaje-recogida-fecha">' +
                        '<span class="reciclaje-recogida-dia">' + fechaRecogida.getDate() + '</span>' +
                        '<span class="reciclaje-recogida-mes">' + nombresMeses[fechaRecogida.getMonth()] + '</span>' +
                    '</div>' +
                    '<div class="reciclaje-recogida-info">' +
                        '<h4>' + recogida.tipos_residuos.join(', ') + '</h4>' +
                        '<p>' + recogida.zona + '</p>' +
                        (recogida.hora_inicio ? '<span class="reciclaje-recogida-hora">🕐 ' + recogida.hora_inicio + '</span>' : '') +
                    '</div>' +
                '</li>'
            );
        });
    };

    /**
     * Controles del calendario
     */
    FlavorReciclaje.initControlesCalendario = function() {
        $(document).on('click', '.reciclaje-calendario-nav button', function() {
            var direccion = $(this).data('dir');

            if (direccion === 'prev') {
                FlavorReciclaje.state.calendarioMes--;
                if (FlavorReciclaje.state.calendarioMes < 0) {
                    FlavorReciclaje.state.calendarioMes = 11;
                    FlavorReciclaje.state.calendarioAnio--;
                }
            } else {
                FlavorReciclaje.state.calendarioMes++;
                if (FlavorReciclaje.state.calendarioMes > 11) {
                    FlavorReciclaje.state.calendarioMes = 0;
                    FlavorReciclaje.state.calendarioAnio++;
                }
            }

            FlavorReciclaje.renderizarCalendario();
            FlavorReciclaje.cargarRecogidas();
        });
    };

    /**
     * Modulo Estadisticas
     */
    FlavorReciclaje.initEstadisticas = function() {
        var contenedorStats = $('.reciclaje-mis-estadisticas');
        if (contenedorStats.length === 0) return;

        FlavorReciclaje.cargarEstadisticasUsuario();
    };

    /**
     * Cargar estadisticas del usuario
     */
    FlavorReciclaje.cargarEstadisticasUsuario = function() {
        $.ajax({
            url: FlavorReciclaje.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reciclaje_obtener_estadisticas_usuario',
                nonce: FlavorReciclaje.config.nonce
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorReciclaje.mostrarEstadisticas(respuesta.data);
                }
            }
        });
    };

    /**
     * Mostrar estadisticas
     */
    FlavorReciclaje.mostrarEstadisticas = function(estadisticas) {
        // Actualizar valores de tarjetas
        $('.reciclaje-stat-total-kg').text(estadisticas.total_kg + ' kg');
        $('.reciclaje-stat-puntos').text(estadisticas.puntos_totales);
        $('.reciclaje-stat-depositos').text(estadisticas.total_depositos);
        $('.reciclaje-stat-co2').text(estadisticas.co2_ahorrado + ' kg');

        // Barras de progreso por material
        if (estadisticas.por_material) {
            var contenedorProgreso = $('.reciclaje-progreso-container');
            contenedorProgreso.empty();

            var totalMaterial = 0;
            for (var material in estadisticas.por_material) {
                totalMaterial += estadisticas.por_material[material];
            }

            for (var tipoMaterial in estadisticas.por_material) {
                var cantidad = estadisticas.por_material[tipoMaterial];
                var porcentaje = totalMaterial > 0 ? Math.round((cantidad / totalMaterial) * 100) : 0;

                contenedorProgreso.append(
                    '<div class="reciclaje-progreso-item">' +
                        '<div class="reciclaje-progreso-header">' +
                            '<span class="reciclaje-progreso-label">' +
                                '<span class="color-dot" style="background: ' + (FlavorReciclaje.coloresMaterial[tipoMaterial] || '#999') + '"></span>' +
                                tipoMaterial.charAt(0).toUpperCase() + tipoMaterial.slice(1) +
                            '</span>' +
                            '<span class="reciclaje-progreso-valor">' + cantidad + ' kg (' + porcentaje + '%)</span>' +
                        '</div>' +
                        '<div class="reciclaje-progreso-bar">' +
                            '<div class="reciclaje-progreso-fill ' + tipoMaterial + '" style="width: ' + porcentaje + '%"></div>' +
                        '</div>' +
                    '</div>'
                );
            }
        }

        // Historial
        if (estadisticas.historial) {
            FlavorReciclaje.mostrarHistorial(estadisticas.historial);
        }
    };

    /**
     * Mostrar historial de reciclaje
     */
    FlavorReciclaje.mostrarHistorial = function(historial) {
        var contenedorHistorial = $('.reciclaje-historial');
        if (contenedorHistorial.length === 0) return;

        contenedorHistorial.empty();

        historial.forEach(function(registro) {
            var colorMaterial = FlavorReciclaje.coloresMaterial[registro.tipo_material] || '#999';
            var fechaFormateada = new Date(registro.fecha).toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            contenedorHistorial.append(
                '<div class="reciclaje-historial-item">' +
                    '<div class="reciclaje-historial-icono" style="background: ' + colorMaterial + '">♻️</div>' +
                    '<div class="reciclaje-historial-info">' +
                        '<div class="reciclaje-historial-tipo">' + registro.tipo_material + ' - ' + registro.cantidad_kg + ' kg</div>' +
                        '<div class="reciclaje-historial-fecha">' + fechaFormateada + '</div>' +
                    '</div>' +
                    '<div class="reciclaje-historial-puntos">+' + registro.puntos + ' pts</div>' +
                '</div>'
            );
        });
    };

    /**
     * Modulo Retos
     */
    FlavorReciclaje.initRetos = function() {
        var contenedorRetos = $('.reciclaje-retos-container');
        if (contenedorRetos.length === 0) return;

        FlavorReciclaje.cargarRetos();
        FlavorReciclaje.initControlesRetos();
    };

    /**
     * Cargar retos disponibles
     */
    FlavorReciclaje.cargarRetos = function() {
        $.ajax({
            url: FlavorReciclaje.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reciclaje_obtener_retos',
                nonce: FlavorReciclaje.config.nonce
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.data.retos) {
                    FlavorReciclaje.mostrarRetos(respuesta.data.retos);
                }
            }
        });
    };

    /**
     * Mostrar retos
     */
    FlavorReciclaje.mostrarRetos = function(retos) {
        var contenedorRetos = $('.reciclaje-retos-grid');
        contenedorRetos.empty();

        retos.forEach(function(reto) {
            var porcentajeProgreso = reto.meta > 0 ? Math.min(100, Math.round((reto.progreso / reto.meta) * 100)) : 0;
            var estaCompletado = porcentajeProgreso >= 100;

            contenedorRetos.append(
                '<div class="reciclaje-reto-card' + (estaCompletado ? ' completado' : '') + '">' +
                    (estaCompletado ? '<span class="reciclaje-reto-badge">Completado</span>' : '') +
                    '<div class="reciclaje-reto-header">' +
                        '<div class="reciclaje-reto-icono">' + (reto.icono || '🎯') + '</div>' +
                        '<h3 class="reciclaje-reto-titulo">' + reto.nombre + '</h3>' +
                    '</div>' +
                    '<div class="reciclaje-reto-body">' +
                        '<p class="reciclaje-reto-descripcion">' + reto.descripcion + '</p>' +
                        '<div class="reciclaje-reto-progreso">' +
                            '<div class="reciclaje-reto-progreso-texto">' +
                                '<span>' + reto.progreso + ' / ' + reto.meta + ' ' + reto.unidad + '</span>' +
                                '<span>' + porcentajeProgreso + '%</span>' +
                            '</div>' +
                            '<div class="reciclaje-reto-progreso-bar">' +
                                '<div class="reciclaje-reto-progreso-fill" style="width: ' + porcentajeProgreso + '%"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="reciclaje-reto-recompensa">' +
                            '🏆 Recompensa: <span class="puntos">' + reto.puntos_recompensa + ' puntos</span>' +
                        '</div>' +
                        (estaCompletado ?
                            '<button class="reciclaje-reto-btn" data-reto-id="' + reto.id + '" data-action="reclamar">Reclamar recompensa</button>' :
                            '<button class="reciclaje-reto-btn" disabled>En progreso</button>'
                        ) +
                    '</div>' +
                '</div>'
            );
        });
    };

    /**
     * Controles de retos
     */
    FlavorReciclaje.initControlesRetos = function() {
        $(document).on('click', '.reciclaje-reto-btn[data-action="reclamar"]', function() {
            var boton = $(this);
            var idReto = boton.data('reto-id');

            boton.prop('disabled', true).text('Reclamando...');

            $.ajax({
                url: FlavorReciclaje.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reciclaje_reclamar_reto',
                    nonce: FlavorReciclaje.config.nonce,
                    reto_id: idReto
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        boton.text('Recompensa reclamada').addClass('reclamado');
                        FlavorReciclaje.mostrarNotificacion('success', 'Has ganado ' + respuesta.data.puntos + ' puntos');
                    } else {
                        boton.prop('disabled', false).text('Reclamar recompensa');
                        FlavorReciclaje.mostrarNotificacion('error', respuesta.data.message || 'Error al reclamar');
                    }
                },
                error: function() {
                    boton.prop('disabled', false).text('Reclamar recompensa');
                    FlavorReciclaje.mostrarNotificacion('error', 'Error de conexion');
                }
            });
        });
    };

    /**
     * Registrar deposito de reciclaje
     */
    FlavorReciclaje.registrarDeposito = function(datosFormulario) {
        $.ajax({
            url: FlavorReciclaje.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reciclaje_registrar_deposito',
                nonce: FlavorReciclaje.config.nonce,
                punto_id: datosFormulario.punto_id,
                tipo_material: datosFormulario.tipo_material,
                cantidad_kg: datosFormulario.cantidad_kg
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    FlavorReciclaje.mostrarNotificacion('success',
                        'Deposito registrado. Has ganado ' + respuesta.data.puntos + ' puntos');

                    // Limpiar formulario
                    $('.reciclaje-form-registrar')[0].reset();

                    // Actualizar estadisticas si estan visibles
                    if ($('.reciclaje-mis-estadisticas').length) {
                        FlavorReciclaje.cargarEstadisticasUsuario();
                    }
                } else {
                    FlavorReciclaje.mostrarNotificacion('error', respuesta.data.message || 'Error al registrar');
                }
            },
            error: function() {
                FlavorReciclaje.mostrarNotificacion('error', 'Error de conexion');
            }
        });
    };

    /**
     * Mostrar notificacion
     */
    FlavorReciclaje.mostrarNotificacion = function(tipo, mensaje) {
        var notificacion = $(
            '<div class="reciclaje-alert reciclaje-alert-' + tipo + '">' +
                '<span>' + mensaje + '</span>' +
            '</div>'
        );

        $('body').append(notificacion);

        notificacion.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: 9999,
            maxWidth: '400px'
        });

        setTimeout(function() {
            notificacion.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    };

    /**
     * Guia de reciclaje - Acordeon
     */
    FlavorReciclaje.initGuiaAcordeon = function() {
        $(document).on('click', '.reciclaje-acordeon-header', function() {
            var header = $(this);
            var contenido = header.next('.reciclaje-acordeon-contenido');

            // Cerrar otros
            $('.reciclaje-acordeon-header').not(header).removeClass('active');
            $('.reciclaje-acordeon-contenido').not(contenido).removeClass('active');

            // Toggle actual
            header.toggleClass('active');
            contenido.toggleClass('active');
        });
    };

    /**
     * Inicializar formularios
     */
    FlavorReciclaje.initFormularios = function() {
        // Formulario de registro de deposito
        $(document).on('submit', '.reciclaje-form-registrar', function(evento) {
            evento.preventDefault();

            var formulario = $(this);
            var datosFormulario = {
                punto_id: formulario.find('[name="punto_id"]').val(),
                tipo_material: formulario.find('[name="tipo_material"]').val(),
                cantidad_kg: formulario.find('[name="cantidad_kg"]').val()
            };

            if (!datosFormulario.punto_id || !datosFormulario.tipo_material || !datosFormulario.cantidad_kg) {
                FlavorReciclaje.mostrarNotificacion('warning', 'Por favor completa todos los campos');
                return;
            }

            FlavorReciclaje.registrarDeposito(datosFormulario);
        });
    };

    /**
     * Inicializacion general
     */
    FlavorReciclaje.init = function() {
        FlavorReciclaje.initMapa();
        FlavorReciclaje.initCalendario();
        FlavorReciclaje.initEstadisticas();
        FlavorReciclaje.initRetos();
        FlavorReciclaje.initGuiaAcordeon();
        FlavorReciclaje.initFormularios();
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        FlavorReciclaje.init();
    });

})(jQuery);
