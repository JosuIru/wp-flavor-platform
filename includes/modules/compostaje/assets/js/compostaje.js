/**
 * JavaScript del Modulo de Compostaje Comunitario
 * @package FlavorChatIA
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // Configuracion global
    const CONFIG = window.flavorCompostaje || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        restUrl: '/wp-json/flavor-compostaje/v1/',
        nonce: '',
        restNonce: '',
        usuario_id: 0,
        strings: {
            cargando: 'Cargando...',
            error: 'Ha ocurrido un error',
            exito: 'Operacion realizada con exito',
            confirmar_turno: '¿Confirmas que quieres apuntarte a este turno?',
            confirmar_cancelar: '¿Seguro que quieres cancelar tu turno?'
        }
    };

    // Estado de la aplicacion
    const ESTADO = {
        puntosCompostaje: [],
        turnosDisponibles: [],
        paginaActualAportaciones: 1,
        mapaInstancia: null,
        marcadoresMapa: []
    };

    /**
     * Inicializacion al cargar el DOM
     */
    $(document).ready(function() {
        inicializarModuloCompostaje();
    });

    /**
     * Inicializa todos los componentes del modulo
     */
    function inicializarModuloCompostaje() {
        // Mapa de composteras
        if ($('#mapa-composteras').length) {
            inicializarMapaComposteras();
        }

        // Formulario de aportacion
        if ($('#form-aportacion-compost').length) {
            inicializarFormularioAportacion();
        }

        // Lista de aportaciones del usuario
        if ($('#lista-aportaciones').length) {
            cargarMisAportaciones(1);
        }

        // Ranking
        if ($('.flavor-compostaje-ranking').length) {
            inicializarRanking();
        }

        // Turnos
        if ($('.flavor-compostaje-turnos').length) {
            inicializarTurnos();
        }

        // Estadisticas con contadores animados
        if ($('.contador').length) {
            animarContadores();
        }
    }

    // =========================================
    // MAPA DE COMPOSTERAS
    // =========================================

    /**
     * Inicializa el mapa de puntos de compostaje
     */
    function inicializarMapaComposteras() {
        cargarPuntosCompostaje().then(function(puntos) {
            ESTADO.puntosCompostaje = puntos;
            renderizarListaPuntos(puntos);

            // Inicializar mapa si hay libreria disponible
            if (typeof L !== 'undefined') {
                inicializarMapaLeaflet(puntos);
            }
        });

        // Eventos de filtros
        $('#filtro-tipo-punto').on('change', filtrarPuntos);
        $('#btn-mi-ubicacion').on('click', obtenerMiUbicacion);
    }

    /**
     * Carga los puntos de compostaje desde la API
     */
    function cargarPuntosCompostaje(parametros) {
        parametros = parametros || {};

        return new Promise(function(resolve, reject) {
            $.ajax({
                url: CONFIG.restUrl + 'puntos',
                method: 'GET',
                data: parametros,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', CONFIG.restNonce);
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        resolve(respuesta.puntos);
                    } else {
                        reject(respuesta.error || CONFIG.strings.error);
                    }
                },
                error: function(xhr, estado, error) {
                    reject(error || CONFIG.strings.error);
                }
            });
        });
    }

    /**
     * Renderiza la lista de puntos de compostaje
     */
    function renderizarListaPuntos(puntos) {
        const $lista = $('#lista-puntos-compostaje');
        $lista.empty();

        if (!puntos.length) {
            $lista.html('<div class="punto-vacio">No se encontraron puntos de compostaje</div>');
            return;
        }

        puntos.forEach(function(punto) {
            const nivelClase = punto.nivel_llenado_pct > 80 ? 'nivel-alto' : '';
            const faseTexto = obtenerTextoFase(punto.fase_actual);

            const tarjetaHtml = `
                <div class="punto-compostaje-card fase-${punto.fase_actual}" data-id="${punto.id}">
                    <h4 class="punto-nombre">${escapeHtml(punto.nombre)}</h4>
                    <p class="punto-direccion">${escapeHtml(punto.direccion)}</p>
                    <div class="punto-info-grid">
                        <div class="punto-info-item">
                            <span>Tipo:</span>
                            <span>${capitalizar(punto.tipo)}</span>
                        </div>
                        <div class="punto-info-item">
                            <span>Capacidad:</span>
                            <span>${punto.capacidad_litros}L</span>
                        </div>
                    </div>
                    <div class="punto-nivel-barra">
                        <div class="punto-nivel-llenado ${nivelClase}" style="width: ${punto.nivel_llenado_pct}%"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="punto-fase-badge fase-${punto.fase_actual}">${faseTexto}</span>
                        ${punto.distancia_km ? `<span class="punto-distancia">${punto.distancia_km} km</span>` : ''}
                    </div>
                    ${punto.acepta_aportaciones ? `
                        <button class="flavor-btn flavor-btn-primary flavor-btn-block btn-seleccionar-punto" data-punto="${punto.id}" style="margin-top: 12px;">
                            Aportar aqui
                        </button>
                    ` : ''}
                </div>
            `;

            $lista.append(tarjetaHtml);
        });

        // Evento para seleccionar punto
        $('.btn-seleccionar-punto').on('click', function() {
            const puntoId = $(this).data('punto');
            seleccionarPuntoParaAportacion(puntoId);
        });
    }

    /**
     * Inicializa el mapa con Leaflet
     */
    function inicializarMapaLeaflet(puntos) {
        // Centro predeterminado (Espana)
        const centroInicial = [40.4168, -3.7038];
        const zoomInicial = 6;

        ESTADO.mapaInstancia = L.map('mapa-composteras').setView(centroInicial, zoomInicial);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(ESTADO.mapaInstancia);

        agregarMarcadoresMapa(puntos);

        // Ajustar vista si hay puntos
        if (puntos.length > 0) {
            const grupo = L.featureGroup(ESTADO.marcadoresMapa);
            ESTADO.mapaInstancia.fitBounds(grupo.getBounds().pad(0.1));
        }
    }

    /**
     * Agrega marcadores al mapa
     */
    function agregarMarcadoresMapa(puntos) {
        // Limpiar marcadores anteriores
        ESTADO.marcadoresMapa.forEach(function(marcador) {
            ESTADO.mapaInstancia.removeLayer(marcador);
        });
        ESTADO.marcadoresMapa = [];

        const iconoCompost = L.divIcon({
            className: 'marcador-compostaje',
            html: '<div class="marcador-icono">🌱</div>',
            iconSize: [36, 36],
            iconAnchor: [18, 36]
        });

        puntos.forEach(function(punto) {
            const marcador = L.marker([punto.latitud, punto.longitud], { icon: iconoCompost })
                .addTo(ESTADO.mapaInstancia)
                .bindPopup(`
                    <div class="popup-punto">
                        <strong>${escapeHtml(punto.nombre)}</strong>
                        <p>${escapeHtml(punto.direccion)}</p>
                        <p>Nivel: ${punto.nivel_llenado_pct}%</p>
                        ${punto.acepta_aportaciones ?
                            `<button class="flavor-btn flavor-btn-primary btn-popup-aportar" data-punto="${punto.id}">Aportar</button>` :
                            '<span class="punto-cerrado">No acepta aportaciones</span>'
                        }
                    </div>
                `);

            ESTADO.marcadoresMapa.push(marcador);
        });

        // Evento para botones en popups
        ESTADO.mapaInstancia.on('popupopen', function() {
            $('.btn-popup-aportar').on('click', function() {
                const puntoId = $(this).data('punto');
                seleccionarPuntoParaAportacion(puntoId);
            });
        });
    }

    /**
     * Filtra puntos segun criterios
     */
    function filtrarPuntos() {
        const tipo = $('#filtro-tipo-punto').val();

        let puntosFiltrados = ESTADO.puntosCompostaje;

        if (tipo) {
            puntosFiltrados = puntosFiltrados.filter(function(punto) {
                return punto.tipo === tipo;
            });
        }

        renderizarListaPuntos(puntosFiltrados);

        if (ESTADO.mapaInstancia) {
            agregarMarcadoresMapa(puntosFiltrados);
        }
    }

    /**
     * Obtiene la ubicacion del usuario
     */
    function obtenerMiUbicacion() {
        if (!navigator.geolocation) {
            mostrarMensaje('Tu navegador no soporta geolocalizacion', 'error');
            return;
        }

        const $boton = $('#btn-mi-ubicacion');
        $boton.prop('disabled', true).text('Obteniendo...');

        navigator.geolocation.getCurrentPosition(
            function(posicion) {
                const lat = posicion.coords.latitude;
                const lng = posicion.coords.longitude;

                cargarPuntosCompostaje({ lat: lat, lng: lng, radio: 20 })
                    .then(function(puntos) {
                        ESTADO.puntosCompostaje = puntos;
                        renderizarListaPuntos(puntos);

                        if (ESTADO.mapaInstancia) {
                            ESTADO.mapaInstancia.setView([lat, lng], 12);
                            agregarMarcadoresMapa(puntos);

                            // Marcador de ubicacion del usuario
                            L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: 'marcador-usuario',
                                    html: '<div class="marcador-icono usuario">📍</div>',
                                    iconSize: [30, 30]
                                })
                            }).addTo(ESTADO.mapaInstancia)
                              .bindPopup('Tu ubicacion');
                        }

                        $boton.prop('disabled', false).html('<span class="dashicons dashicons-location"></span> Mi ubicacion');
                    });
            },
            function(error) {
                mostrarMensaje('No se pudo obtener tu ubicacion', 'error');
                $boton.prop('disabled', false).html('<span class="dashicons dashicons-location"></span> Mi ubicacion');
            }
        );
    }

    // =========================================
    // FORMULARIO DE APORTACION
    // =========================================

    /**
     * Inicializa el formulario de aportacion
     */
    function inicializarFormularioAportacion() {
        cargarSelectPuntos();

        // Calcular preview al cambiar valores
        $('#tipo-material, #cantidad-kg').on('change input', actualizarPreviewAportacion);

        // Envio del formulario
        $('#form-aportacion-compost').on('submit', function(evento) {
            evento.preventDefault();
            enviarAportacion();
        });
    }

    /**
     * Carga el select de puntos de compostaje
     */
    function cargarSelectPuntos() {
        cargarPuntosCompostaje().then(function(puntos) {
            const $select = $('#punto-compostaje');
            $select.find('option:not(:first)').remove();

            puntos.filter(function(punto) {
                return punto.acepta_aportaciones;
            }).forEach(function(punto) {
                $select.append(`<option value="${punto.id}">${escapeHtml(punto.nombre)} - ${escapeHtml(punto.direccion)}</option>`);
            });
        });
    }

    /**
     * Selecciona un punto para aportacion
     */
    function seleccionarPuntoParaAportacion(puntoId) {
        $('#punto-compostaje').val(puntoId);

        // Scroll al formulario si existe
        const $formulario = $('#form-aportacion-compost');
        if ($formulario.length) {
            $('html, body').animate({
                scrollTop: $formulario.offset().top - 100
            }, 500);
        }
    }

    /**
     * Actualiza el preview de puntos estimados
     */
    function actualizarPreviewAportacion() {
        const tipoMaterial = $('#tipo-material').val();
        const cantidadKg = parseFloat($('#cantidad-kg').val()) || 0;
        const $preview = $('#preview-puntos');

        if (!tipoMaterial || cantidadKg <= 0) {
            $preview.hide();
            return;
        }

        const puntosPorKg = $('#tipo-material option:selected').data('puntos') || 5;
        const puntosEstimados = Math.round(puntosPorKg * cantidadKg);
        const co2Estimado = (cantidadKg * 0.5).toFixed(2);

        $('#puntos-estimados').text(puntosEstimados + ' pts');
        $('#co2-estimado').text(co2Estimado + ' kg');
        $preview.show();
    }

    /**
     * Envia la aportacion al servidor
     */
    function enviarAportacion() {
        const $formulario = $('#form-aportacion-compost');
        const $boton = $formulario.find('button[type="submit"]');
        const $resultado = $('#resultado-aportacion');

        const datos = {
            action: 'compostaje_registrar_aportacion',
            nonce: CONFIG.nonce,
            punto_id: $('#punto-compostaje').val(),
            tipo_material: $('#tipo-material').val(),
            cantidad_kg: $('#cantidad-kg').val(),
            notas: $('#notas-aportacion').val()
        };

        // Validacion
        if (!datos.punto_id || !datos.tipo_material || !datos.cantidad_kg) {
            mostrarMensaje('Por favor, completa todos los campos requeridos', 'error');
            return;
        }

        $boton.prop('disabled', true).text('Registrando...');

        $.ajax({
            url: CONFIG.ajaxUrl,
            method: 'POST',
            data: datos,
            success: function(respuesta) {
                if (respuesta.success) {
                    $resultado.removeClass('error').addClass('exito').html(`
                        <h4>¡Aportacion registrada!</h4>
                        <p>Has ganado <strong>${respuesta.puntos_obtenidos} puntos</strong></p>
                        ${respuesta.bonus_nivel > 0 ? `<p>Bonus de nivel: +${respuesta.bonus_nivel} pts</p>` : ''}
                        <p>CO2 evitado: ${respuesta.co2_evitado} kg</p>
                    `).show();

                    // Limpiar formulario
                    $formulario[0].reset();
                    $('#preview-puntos').hide();

                    // Disparar evento personalizado
                    $(document).trigger('compostaje:aportacion_registrada', [respuesta]);
                } else {
                    $resultado.removeClass('exito').addClass('error')
                        .text(respuesta.error || CONFIG.strings.error).show();
                }
            },
            error: function() {
                $resultado.removeClass('exito').addClass('error')
                    .text(CONFIG.strings.error).show();
            },
            complete: function() {
                $boton.prop('disabled', false).text('Registrar Aportacion');
            }
        });
    }

    // =========================================
    // MIS APORTACIONES
    // =========================================

    /**
     * Carga las aportaciones del usuario
     */
    function cargarMisAportaciones(pagina) {
        const $lista = $('#lista-aportaciones');
        const $paginacion = $('#paginacion-aportaciones');

        $lista.html('<div class="flavor-cargando">' + CONFIG.strings.cargando + '</div>');

        $.ajax({
            url: CONFIG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'compostaje_mis_aportaciones',
                nonce: CONFIG.nonce,
                pagina: pagina
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    ESTADO.paginaActualAportaciones = respuesta.pagina_actual;
                    renderizarListaAportaciones(respuesta.aportaciones);
                    renderizarPaginacion($paginacion, respuesta.pagina_actual, respuesta.paginas);
                } else {
                    $lista.html('<div class="flavor-aviso flavor-aviso-info">No tienes aportaciones registradas</div>');
                }
            },
            error: function() {
                $lista.html('<div class="flavor-aviso flavor-aviso-error">' + CONFIG.strings.error + '</div>');
            }
        });
    }

    /**
     * Renderiza la lista de aportaciones
     */
    function renderizarListaAportaciones(aportaciones) {
        const $lista = $('#lista-aportaciones');
        $lista.empty();

        if (!aportaciones.length) {
            $lista.html('<div class="aportacion-vacia">No tienes aportaciones aun</div>');
            return;
        }

        aportaciones.forEach(function(aportacion) {
            const categoriaClase = aportacion.categoria_material === 'marron' ? 'cat-marron' :
                                   aportacion.categoria_material === 'especial' ? 'cat-especial' : '';
            const fecha = new Date(aportacion.fecha_aportacion);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            const itemHtml = `
                <div class="aportacion-item">
                    <div class="aportacion-icono ${categoriaClase}">
                        ${obtenerIconoMaterial(aportacion.tipo_material)}
                    </div>
                    <div class="aportacion-info">
                        <span class="aportacion-material">${escapeHtml(aportacion.nombre_material || aportacion.tipo_material)}</span>
                        <span class="aportacion-punto">${escapeHtml(aportacion.nombre_punto)}</span>
                    </div>
                    <div class="aportacion-stats">
                        <span class="aportacion-kg">${parseFloat(aportacion.cantidad_kg).toFixed(1)} kg</span>
                        <span class="aportacion-puntos">+${aportacion.puntos_obtenidos} pts</span>
                        <span class="aportacion-fecha">${fechaFormateada}</span>
                    </div>
                </div>
            `;

            $lista.append(itemHtml);
        });
    }

    /**
     * Renderiza la paginacion
     */
    function renderizarPaginacion($contenedor, paginaActual, totalPaginas) {
        $contenedor.empty();

        if (totalPaginas <= 1) return;

        for (let paginaIterada = 1; paginaIterada <= totalPaginas; paginaIterada++) {
            const claseActiva = paginaIterada === paginaActual ? 'activa' : '';
            $contenedor.append(`<button class="pagina-btn ${claseActiva}" data-pagina="${paginaIterada}">${paginaIterada}</button>`);
        }

        $contenedor.find('.pagina-btn').on('click', function() {
            const nuevaPagina = $(this).data('pagina');
            cargarMisAportaciones(nuevaPagina);
        });
    }

    // =========================================
    // RANKING
    // =========================================

    /**
     * Inicializa el componente de ranking
     */
    function inicializarRanking() {
        $('.ranking-filtro').on('click', function() {
            const $boton = $(this);
            const periodo = $boton.data('periodo');

            $('.ranking-filtro').removeClass('activo');
            $boton.addClass('activo');

            cargarRanking(periodo);
        });
    }

    /**
     * Carga el ranking desde la API
     */
    function cargarRanking(periodo) {
        const $lista = $('#lista-ranking');
        $lista.html('<div class="flavor-cargando">' + CONFIG.strings.cargando + '</div>');

        $.ajax({
            url: CONFIG.restUrl + 'ranking',
            method: 'GET',
            data: { periodo: periodo, limite: 10 },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', CONFIG.restNonce);
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    renderizarRanking(respuesta.ranking);
                }
            },
            error: function() {
                $lista.html('<div class="ranking-vacio">' + CONFIG.strings.error + '</div>');
            }
        });
    }

    /**
     * Renderiza el ranking
     */
    function renderizarRanking(ranking) {
        const $lista = $('#lista-ranking');
        $lista.empty();

        if (!ranking.length) {
            $lista.html('<div class="ranking-vacio">No hay datos de ranking</div>');
            return;
        }

        ranking.forEach(function(entrada) {
            const itemHtml = `
                <div class="ranking-item ranking-posicion-${entrada.posicion}">
                    <span class="ranking-pos">${entrada.posicion <= 3 ? obtenerMedalla(entrada.posicion) : entrada.posicion}</span>
                    <img src="${escapeHtml(entrada.avatar)}" alt="" class="ranking-avatar">
                    <div class="ranking-info">
                        <span class="ranking-nombre">${escapeHtml(entrada.nombre)}</span>
                        <span class="ranking-nivel">${escapeHtml(entrada.nivel_nombre)}</span>
                    </div>
                    <div class="ranking-stats">
                        <span class="ranking-kg">${parseFloat(entrada.total_kg).toFixed(1)} kg</span>
                        <span class="ranking-puntos">${entrada.total_puntos} pts</span>
                    </div>
                </div>
            `;

            $lista.append(itemHtml);
        });
    }

    // =========================================
    // TURNOS
    // =========================================

    /**
     * Inicializa el componente de turnos
     */
    function inicializarTurnos() {
        cargarTurnos();

        // Cargar puntos para el filtro
        cargarPuntosCompostaje().then(function(puntos) {
            const $select = $('#filtro-punto-turno');
            puntos.forEach(function(punto) {
                $select.append(`<option value="${punto.id}">${escapeHtml(punto.nombre)}</option>`);
            });
        });

        // Eventos de filtros
        $('#filtro-punto-turno, #filtro-tipo-turno').on('change', function() {
            cargarTurnos();
        });
    }

    /**
     * Carga los turnos disponibles
     */
    function cargarTurnos() {
        const $lista = $('#lista-turnos');
        const puntoId = $('#filtro-punto-turno').val();
        const tipoTurno = $('#filtro-tipo-turno').val();

        const parametros = {};
        if (puntoId) parametros.punto_id = puntoId;

        $lista.html('<div class="flavor-cargando">' + CONFIG.strings.cargando + '</div>');

        $.ajax({
            url: CONFIG.restUrl + 'turnos',
            method: 'GET',
            data: parametros,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', CONFIG.restNonce);
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    let turnos = respuesta.turnos;

                    if (tipoTurno) {
                        turnos = turnos.filter(function(turno) {
                            return turno.tipo_tarea === tipoTurno;
                        });
                    }

                    ESTADO.turnosDisponibles = turnos;
                    renderizarTurnos(turnos);
                }
            },
            error: function() {
                $lista.html('<div class="turno-vacio">' + CONFIG.strings.error + '</div>');
            }
        });
    }

    /**
     * Renderiza la lista de turnos
     */
    function renderizarTurnos(turnos) {
        const $lista = $('#lista-turnos');
        $lista.empty();

        if (!turnos.length) {
            $lista.html('<div class="turno-vacio">No hay turnos disponibles</div>');
            return;
        }

        turnos.forEach(function(turno) {
            const fecha = new Date(turno.fecha_turno);
            const diaNumero = fecha.getDate();
            const mesTexto = fecha.toLocaleDateString('es-ES', { month: 'short' });
            const plazasLibres = turno.plazas_disponibles - turno.plazas_ocupadas;
            const estaCompleto = plazasLibres <= 0;

            const turnoHtml = `
                <div class="turno-card" data-turno="${turno.id}">
                    <div class="turno-fecha-box">
                        <span class="turno-dia">${diaNumero}</span>
                        <span class="turno-mes">${mesTexto}</span>
                    </div>
                    <div class="turno-info">
                        <div class="turno-tipo">${capitalizar(turno.tipo_tarea)}</div>
                        <div class="turno-punto">${escapeHtml(turno.nombre_punto || '')}</div>
                        <div class="turno-hora">${turno.hora_inicio} - ${turno.hora_fin}</div>
                    </div>
                    <div class="turno-plazas ${estaCompleto ? 'completo' : ''}">
                        <span class="turno-plazas-numero">${plazasLibres}</span>
                        <span class="turno-plazas-label">plazas libres</span>
                    </div>
                    <div class="turno-acciones">
                        <span class="turno-puntos-badge">+${turno.puntos_recompensa} pts</span>
                        ${!estaCompleto && CONFIG.usuario_id ? `
                            <button class="flavor-btn flavor-btn-primary btn-inscribir-turno" data-turno="${turno.id}">
                                Apuntarse
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;

            $lista.append(turnoHtml);
        });

        // Eventos de inscripcion
        $('.btn-inscribir-turno').on('click', function() {
            const turnoId = $(this).data('turno');
            inscribirseEnTurno(turnoId);
        });
    }

    /**
     * Inscribe al usuario en un turno
     */
    function inscribirseEnTurno(turnoId) {
        if (!confirm(CONFIG.strings.confirmar_turno)) {
            return;
        }

        const $boton = $(`.btn-inscribir-turno[data-turno="${turnoId}"]`);
        $boton.prop('disabled', true).text('Inscribiendo...');

        $.ajax({
            url: CONFIG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'compostaje_apuntarse_turno',
                nonce: CONFIG.nonce,
                turno_id: turnoId
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    mostrarMensaje('Te has inscrito correctamente al turno', 'exito');
                    cargarTurnos();
                } else {
                    mostrarMensaje(respuesta.error || CONFIG.strings.error, 'error');
                    $boton.prop('disabled', false).text('Apuntarse');
                }
            },
            error: function() {
                mostrarMensaje(CONFIG.strings.error, 'error');
                $boton.prop('disabled', false).text('Apuntarse');
            }
        });
    }

    // =========================================
    // UTILIDADES
    // =========================================

    /**
     * Anima los contadores de estadisticas
     */
    function animarContadores() {
        $('.contador').each(function() {
            const $elemento = $(this);
            const valorFinal = parseFloat($elemento.data('valor')) || 0;
            const duracion = 2000;
            const inicio = 0;
            const incremento = valorFinal / (duracion / 16);
            let valorActual = inicio;

            const animacion = setInterval(function() {
                valorActual += incremento;
                if (valorActual >= valorFinal) {
                    valorActual = valorFinal;
                    clearInterval(animacion);
                }
                $elemento.text(formatearNumero(valorActual));
            }, 16);
        });
    }

    /**
     * Muestra un mensaje al usuario
     */
    function mostrarMensaje(mensaje, tipo) {
        tipo = tipo || 'info';

        const $mensaje = $(`
            <div class="flavor-mensaje flavor-mensaje-${tipo}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                border-radius: 8px;
                background: ${tipo === 'exito' ? '#4CAF50' : tipo === 'error' ? '#F44336' : '#2196F3'};
                color: white;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                max-width: 400px;
            ">
                ${escapeHtml(mensaje)}
            </div>
        `);

        $('body').append($mensaje);

        setTimeout(function() {
            $mensaje.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(texto) {
        if (!texto) return '';
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }

    /**
     * Capitaliza la primera letra
     */
    function capitalizar(texto) {
        if (!texto) return '';
        return texto.charAt(0).toUpperCase() + texto.slice(1).replace(/_/g, ' ');
    }

    /**
     * Formatea numeros grandes
     */
    function formatearNumero(numero) {
        if (numero >= 1000) {
            return (numero / 1000).toFixed(1) + 'k';
        }
        return Math.round(numero).toLocaleString('es-ES');
    }

    /**
     * Obtiene el texto de la fase
     */
    function obtenerTextoFase(fase) {
        const fases = {
            'recepcion': 'Recibiendo',
            'activo': 'Activo',
            'maduracion': 'Madurando',
            'listo': 'Listo',
            'mantenimiento': 'Mantenimiento'
        };
        return fases[fase] || fase;
    }

    /**
     * Obtiene el icono del material
     */
    function obtenerIconoMaterial(tipo) {
        const iconos = {
            'frutas_verduras': '🥕',
            'posos_cafe': '☕',
            'cesped_fresco': '🌿',
            'restos_cocina': '🍳',
            'plantas_verdes': '🌱',
            'hojas_secas': '🍂',
            'papel_carton': '📦',
            'ramas_poda': '🌳',
            'serrin': '🪵',
            'paja': '🌾',
            'cascaras_huevo': '🥚',
            'bolsas_te': '🍵'
        };
        return iconos[tipo] || '♻️';
    }

    /**
     * Obtiene la medalla segun posicion
     */
    function obtenerMedalla(posicion) {
        const medallas = {
            1: '🥇',
            2: '🥈',
            3: '🥉'
        };
        return medallas[posicion] || posicion;
    }

    // Exponer funciones para uso externo si es necesario
    window.FlavorCompostaje = {
        cargarPuntos: cargarPuntosCompostaje,
        cargarTurnos: cargarTurnos,
        cargarRanking: cargarRanking,
        mostrarMensaje: mostrarMensaje
    };

})(jQuery);
