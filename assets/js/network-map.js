/**
 * Network Map JavaScript
 * Red de Comunidades - Mapa público con Leaflet/OpenStreetMap
 */
(function($) {
    'use strict';

    if (typeof flavorNetwork === 'undefined' || typeof L === 'undefined') return;

    var API_URL = flavorNetwork.apiUrl;

    function initNetworkMap($widget) {
        var $contenedorMapa = $widget.find('.fn-map-render');
        if (!$contenedorMapa.length) return;

        var zoomInicial = parseInt($widget.data('zoom') || 6);
        var mapaInstancia = L.map($contenedorMapa[0]).setView([40.4168, -3.7038], zoomInicial);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(mapaInstancia);

        var capaMarcadores = L.layerGroup().addTo(mapaInstancia);

        function cargarMarcadores() {
            var parametros = {
                tipo: $widget.find('.fn-map-filter-tipo').val() || '',
                nivel: $widget.find('.fn-map-filter-nivel').val() || '',
            };

            $.get(API_URL + '/map', parametros, function(data) {
                capaMarcadores.clearLayers();

                if (!data.nodos || !data.nodos.length) return;

                var coordenadasLimites = [];

                data.nodos.forEach(function(nodo) {
                    if (!nodo.lat || !nodo.lng) return;

                    coordenadasLimites.push([nodo.lat, nodo.lng]);

                    var coloresPorNivel = {
                        basico: '#9ca3af',
                        transicion: '#f59e0b',
                        consciente: '#10b981',
                        referente: '#3b82f6',
                    };

                    var colorMarcador = coloresPorNivel[nodo.nivel_consciencia] || '#9ca3af';

                    var iconoCirculo = L.divIcon({
                        className: 'fn-map-marker',
                        html: '<div style="width:14px;height:14px;background:' + colorMarcador +
                              ';border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.3);"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    });

                    var marcador = L.marker([nodo.lat, nodo.lng], { icon: iconoCirculo });

                    var nombreEscapado = $('<span>').text(nodo.nombre).html();
                    var tipoEscapado = $('<span>').text(nodo.tipo_entidad).html();
                    var ciudadEscapada = nodo.ciudad ? $('<span>').text(nodo.ciudad).html() : '';

                    var contenidoPopup = '<div style="min-width:180px;">' +
                        '<strong style="font-size:14px;">' + nombreEscapado + '</strong><br>' +
                        '<em style="color:#6b7280;">' + tipoEscapado + '</em><br>';

                    if (nodo.descripcion_corta) {
                        var descEscapada = $('<span>').text(nodo.descripcion_corta.substring(0, 100)).html();
                        contenidoPopup += '<p style="margin:5px 0;font-size:13px;">' + descEscapada + '</p>';
                    }

                    if (ciudadEscapada) {
                        contenidoPopup += '<span style="font-size:12px;color:#9ca3af;">' + ciudadEscapada + '</span><br>';
                    }

                    contenidoPopup += '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;background:' + colorMarcador + ';color:#fff;">' +
                        nodo.nivel_consciencia + '</span>';

                    if (nodo.verificado) {
                        contenidoPopup += ' <span style="color:#10b981;font-weight:600;">✓</span>';
                    }

                    contenidoPopup += '</div>';

                    marcador.bindPopup(contenidoPopup);
                    capaMarcadores.addLayer(marcador);
                });

                if (coordenadasLimites.length) {
                    mapaInstancia.fitBounds(coordenadasLimites, { padding: [40, 40] });
                }
            });
        }

        $widget.on('click', '.fn-map-filter-btn', function() {
            cargarMarcadores();
        });

        $widget.on('click', '.fn-map-locate-btn', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    mapaInstancia.setView([pos.coords.latitude, pos.coords.longitude], 12);
                });
            }
        });

        // Nearby search
        $widget.on('click', '.fn-map-nearby-btn', function() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(function(pos) {
                var radio = parseInt($widget.find('.fn-map-radio').val() || 50);

                $.get(API_URL + '/nearby', {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    radio: radio,
                    limit: 30,
                }, function(data) {
                    capaMarcadores.clearLayers();

                    // User location marker
                    L.marker([pos.coords.latitude, pos.coords.longitude], {
                        icon: L.divIcon({
                            className: 'fn-map-marker-me',
                            html: '<div style="width:18px;height:18px;background:#ef4444;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>',
                            iconSize: [18, 18],
                            iconAnchor: [9, 9],
                        })
                    }).addTo(capaMarcadores).bindPopup('Tu ubicación');

                    // Draw radius circle
                    L.circle([pos.coords.latitude, pos.coords.longitude], {
                        radius: radio * 1000,
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.05,
                        weight: 1,
                    }).addTo(capaMarcadores);

                    data.nodos.forEach(function(nodo) {
                        if (!nodo.latitud && !nodo.lat) return;
                        var lat = nodo.latitud || nodo.lat;
                        var lng = nodo.longitud || nodo.lng;

                        L.marker([lat, lng]).addTo(capaMarcadores)
                            .bindPopup(
                                '<strong>' + $('<span>').text(nodo.nombre).html() + '</strong><br>' +
                                (nodo.distancia_km ? nodo.distancia_km + ' km' : '')
                            );
                    });

                    mapaInstancia.setView([pos.coords.latitude, pos.coords.longitude], 10);
                });
            });
        });

        // Initial load
        cargarMarcadores();

        // Fix map display issues
        setTimeout(function() {
            mapaInstancia.invalidateSize();
        }, 500);
    }

    $(document).ready(function() {
        $('.flavor-network-map-widget').each(function() {
            initNetworkMap($(this));
        });
    });

})(jQuery);
