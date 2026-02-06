/**
 * JavaScript Frontend - Módulo de Reciclaje
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // ========================================
    // VARIABLES GLOBALES
    // ========================================

    let mapaReciclaje = null;
    let marcadores = [];
    let ubicacionUsuario = null;

    // ========================================
    // INICIALIZACIÓN
    // ========================================

    $(document).ready(function() {
        inicializarMapa();
        inicializarCalendario();
        inicializarCanjeRecompensas();
        inicializarReportarContenedor();
        obtenerUbicacionUsuario();
    });

    // ========================================
    // MAPA DE PUNTOS DE RECICLAJE
    // ========================================

    function inicializarMapa() {
        const $mapContainer = $('#mapa-reciclaje');

        if ($mapContainer.length === 0 || typeof L === 'undefined') {
            return;
        }

        // Crear mapa centrado en España
        mapaReciclaje = L.map('mapa-reciclaje').setView([40.4168, -3.7038], 13);

        // Añadir capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapaReciclaje);

        // Cargar puntos de reciclaje
        cargarPuntosReciclaje();
    }

    function cargarPuntosReciclaje(filtros = {}) {
        const $container = $('.reciclaje-puntos-map');
        const tipoMaterial = $container.data('tipo') || '';

        $.ajax({
            url: reciclajeData.restUrl + '/puntos',
            method: 'GET',
            data: {
                lat: ubicacionUsuario ? ubicacionUsuario.lat : 0,
                lng: ubicacionUsuario ? ubicacionUsuario.lng : 0,
                tipo_material: tipoMaterial,
                ...filtros
            },
            success: function(response) {
                if (response.success) {
                    mostrarPuntosEnMapa(response.puntos);
                    mostrarPuntosEnLista(response.puntos);
                }
            },
            error: function() {
                mostrarNotificacion(reciclajeData.i18n.error, 'error');
            }
        });
    }

    function mostrarPuntosEnMapa(puntos) {
        // Limpiar marcadores anteriores
        marcadores.forEach(m => m.remove());
        marcadores = [];

        puntos.forEach(punto => {
            const icono = obtenerIconoPunto(punto.tipo);

            const marcador = L.marker([punto.lat, punto.lng], {
                icon: icono
            }).addTo(mapaReciclaje);

            const popupContent = `
                <div class="punto-popup">
                    <h4>${punto.nombre}</h4>
                    <p><strong>${punto.tipo}</strong></p>
                    <p>${punto.direccion}</p>
                    ${punto.horario ? `<p><strong>Horario:</strong> ${punto.horario}</p>` : ''}
                    ${punto.distancia_km ? `<p class="punto-distancia">A ${punto.distancia_km} km</p>` : ''}
                    <div class="punto-materiales">
                        ${punto.materiales.map(m => `<span class="material-badge">${m}</span>`).join('')}
                    </div>
                    <button class="btn-registrar-deposito" data-punto-id="${punto.id}">Registrar depósito</button>
                </div>
            `;

            marcador.bindPopup(popupContent);
            marcadores.push(marcador);
        });

        // Ajustar vista del mapa
        if (marcadores.length > 0) {
            const group = new L.featureGroup(marcadores);
            mapaReciclaje.fitBounds(group.getBounds().pad(0.1));
        }
    }

    function mostrarPuntosEnLista(puntos) {
        const $lista = $('.puntos-lista');

        if ($lista.length === 0) {
            return;
        }

        $lista.empty();

        puntos.forEach(punto => {
            const $item = $(`
                <div class="punto-item" data-lat="${punto.lat}" data-lng="${punto.lng}">
                    <div class="punto-nombre">${punto.nombre}</div>
                    <div class="punto-direccion">${punto.direccion}</div>
                    ${punto.distancia_km ? `<div class="punto-distancia">${punto.distancia_km} km</div>` : ''}
                    <div class="punto-materiales">
                        ${punto.materiales.map(m => `<span class="material-badge">${m}</span>`).join('')}
                    </div>
                </div>
            `);

            $item.on('click', function() {
                const lat = $(this).data('lat');
                const lng = $(this).data('lng');
                mapaReciclaje.setView([lat, lng], 16);
            });

            $lista.append($item);
        });
    }

    function obtenerIconoPunto(tipo) {
        const colores = {
            'punto_limpio': 'green',
            'contenedor_comunitario': 'blue',
            'centro_acopio': 'orange',
            'movil': 'red'
        };

        return L.icon({
            iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${colores[tipo] || 'blue'}.png`,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }

    function obtenerUbicacionUsuario() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    ubicacionUsuario = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    if (mapaReciclaje) {
                        mapaReciclaje.setView([ubicacionUsuario.lat, ubicacionUsuario.lng], 13);
                        L.marker([ubicacionUsuario.lat, ubicacionUsuario.lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        }).addTo(mapaReciclaje).bindPopup('Tu ubicación');

                        cargarPuntosReciclaje();
                    }
                },
                function(error) {
                    console.log('Error obteniendo ubicación:', error);
                }
            );
        }
    }

    // ========================================
    // REGISTRAR DEPÓSITO
    // ========================================

    $(document).on('click', '.btn-registrar-deposito', function(e) {
        e.preventDefault();
        const puntoId = $(this).data('punto-id');
        mostrarFormularioDeposito(puntoId);
    });

    function mostrarFormularioDeposito(puntoId) {
        const html = `
            <div class="reciclaje-modal" id="modal-deposito">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <h3>Registrar Depósito</h3>
                    <form id="form-deposito">
                        <input type="hidden" name="punto_id" value="${puntoId}">
                        <div class="form-group">
                            <label>Tipo de Material</label>
                            <select name="tipo_material" required>
                                <option value="">Selecciona...</option>
                                <option value="papel">Papel y cartón</option>
                                <option value="plastico">Plástico y envases</option>
                                <option value="vidrio">Vidrio</option>
                                <option value="organico">Orgánico</option>
                                <option value="electronico">Electrónico (RAEE)</option>
                                <option value="ropa">Ropa y textil</option>
                                <option value="aceite">Aceite usado</option>
                                <option value="pilas">Pilas y baterías</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cantidad (kg)</label>
                            <input type="number" name="cantidad_kg" min="0.1" step="0.1" required>
                        </div>
                        <button type="submit" class="btn-primary">Registrar</button>
                    </form>
                </div>
            </div>
        `;

        $('body').append(html);

        $('#modal-deposito').fadeIn();

        $('.modal-close').on('click', function() {
            $('#modal-deposito').fadeOut(function() {
                $(this).remove();
            });
        });
    }

    $(document).on('submit', '#form-deposito', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: reciclajeData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_registrar_deposito',
                nonce: reciclajeData.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion(
                        `¡Depósito registrado! Has ganado ${response.puntos_ganados} puntos`,
                        'success'
                    );
                    $('#modal-deposito').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    mostrarNotificacion(response.error || reciclajeData.i18n.error, 'error');
                }
            },
            error: function() {
                mostrarNotificacion(reciclajeData.i18n.error, 'error');
            }
        });
    });

    // ========================================
    // CALENDARIO DE RECOGIDAS
    // ========================================

    function inicializarCalendario() {
        const $calendario = $('.reciclaje-calendario');

        if ($calendario.length === 0) {
            return;
        }

        const zona = $calendario.data('zona') || '';
        cargarRecogidas(zona);

        $('#zona-selector').on('change', function() {
            cargarRecogidas($(this).val());
        });
    }

    function cargarRecogidas(zona = '', tipoResiduo = '') {
        $.ajax({
            url: reciclajeData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_calendario',
                nonce: reciclajeData.nonce,
                zona: zona,
                tipo_residuo: tipoResiduo
            },
            beforeSend: function() {
                $('#calendario-recogidas').html('<div class="reciclaje-loader"></div>');
            },
            success: function(response) {
                if (response.success) {
                    mostrarRecogidas(response.recogidas);
                } else {
                    $('#calendario-recogidas').html('<p>Error al cargar recogidas</p>');
                }
            }
        });
    }

    function mostrarRecogidas(recogidas) {
        const $container = $('#calendario-recogidas');
        $container.empty();

        if (recogidas.length === 0) {
            $container.html('<p>No hay recogidas programadas</p>');
            return;
        }

        recogidas.forEach(recogida => {
            const fecha = new Date(recogida.fecha);
            const dia = fecha.getDate();
            const mes = fecha.toLocaleDateString('es-ES', { month: 'short' });

            const $item = $(`
                <div class="recogida-item ${recogida.tipo}">
                    <div class="recogida-fecha">
                        <div class="recogida-dia">${dia}</div>
                        <div class="recogida-mes">${mes}</div>
                    </div>
                    <div class="recogida-info">
                        <div class="recogida-zona">${recogida.zona}</div>
                        <div class="recogida-tipos">${recogida.tipos_residuos.join(', ')}</div>
                        ${recogida.hora_inicio ? `<div class="recogida-hora">${recogida.hora_inicio} - ${recogida.hora_fin}</div>` : ''}
                    </div>
                </div>
            `);

            $container.append($item);
        });
    }

    // ========================================
    // CANJE DE RECOMPENSAS
    // ========================================

    function inicializarCanjeRecompensas() {
        $('.btn-canjear').on('click', function() {
            const recompensaId = $(this).data('recompensa-id');
            canjearRecompensa(recompensaId);
        });
    }

    function canjearRecompensa(recompensaId) {
        if (!confirm('¿Confirmas que quieres canjear esta recompensa?')) {
            return;
        }

        $.ajax({
            url: reciclajeData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_canjear_puntos',
                nonce: reciclajeData.nonce,
                recompensa_id: recompensaId
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion(
                        `¡Recompensa canjeada! Te quedan ${response.puntos_restantes} puntos`,
                        'success'
                    );
                    setTimeout(() => location.reload(), 2000);
                } else {
                    mostrarNotificacion(response.error || reciclajeData.i18n.error, 'error');
                }
            },
            error: function() {
                mostrarNotificacion(reciclajeData.i18n.error, 'error');
            }
        });
    }

    // ========================================
    // REPORTAR CONTENEDOR
    // ========================================

    function inicializarReportarContenedor() {
        $('.btn-reportar-contenedor').on('click', function() {
            const contenedorId = $(this).data('contenedor-id');
            mostrarFormularioReporte(contenedorId);
        });
    }

    function mostrarFormularioReporte(contenedorId) {
        const html = `
            <div class="reciclaje-modal" id="modal-reporte">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <h3>Reportar Problema</h3>
                    <form id="form-reporte">
                        <input type="hidden" name="contenedor_id" value="${contenedorId}">
                        <div class="form-group">
                            <label>Problema</label>
                            <select name="problema" required>
                                <option value="">Selecciona...</option>
                                <option value="lleno">Contenedor lleno</option>
                                <option value="danado">Contenedor dañado</option>
                                <option value="sucio">Zona sucia</option>
                                <option value="vandalismo">Vandalismo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Enviar Reporte</button>
                    </form>
                </div>
            </div>
        `;

        $('body').append(html);
        $('#modal-reporte').fadeIn();

        $('.modal-close').on('click', function() {
            $('#modal-reporte').fadeOut(function() {
                $(this).remove();
            });
        });
    }

    $(document).on('submit', '#form-reporte', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: reciclajeData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reciclaje_reportar_contenedor',
                nonce: reciclajeData.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion('Reporte enviado correctamente', 'success');
                    $('#modal-reporte').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    mostrarNotificacion(response.error || reciclajeData.i18n.error, 'error');
                }
            },
            error: function() {
                mostrarNotificacion(reciclajeData.i18n.error, 'error');
            }
        });
    });

    // ========================================
    // UTILIDADES
    // ========================================

    function mostrarNotificacion(mensaje, tipo = 'info') {
        const $notif = $(`
            <div class="reciclaje-alert ${tipo}">
                ${mensaje}
            </div>
        `);

        $('body').prepend($notif);

        setTimeout(() => {
            $notif.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);
