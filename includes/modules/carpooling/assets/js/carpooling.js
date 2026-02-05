/**
 * Carpooling Module JavaScript
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // ========================================
    // Configuracion global
    // ========================================
    const Carpooling = {
        ajaxUrl: carpoolingData.ajaxUrl,
        nonce: carpoolingData.nonce,
        restUrl: carpoolingData.restUrl,
        restNonce: carpoolingData.restNonce,
        strings: carpoolingData.strings,
        settings: carpoolingData.settings,
        debounceTimer: null,
        lugaresCache: {},
    };

    // ========================================
    // Inicializacion
    // ========================================
    $(document).ready(function() {
        Carpooling.init();
    });

    Carpooling.init = function() {
        this.initAutocompletado();
        this.initBuscador();
        this.initPublicarViaje();
        this.initMisViajes();
        this.initMisReservas();
        this.initModales();
        this.initValoraciones();
        this.initVehiculos();
    };

    // ========================================
    // Autocompletado de lugares
    // ========================================
    Carpooling.initAutocompletado = function() {
        const inputsAutocompletado = document.querySelectorAll('.carpooling-autocomplete__input');

        inputsAutocompletado.forEach(function(input) {
            const contenedorAutocomplete = input.closest('.carpooling-autocomplete');
            const listaResultados = contenedorAutocomplete.querySelector('.carpooling-autocomplete__lista');
            const inputLat = contenedorAutocomplete.querySelector('input[name$="_lat"]');
            const inputLng = contenedorAutocomplete.querySelector('input[name$="_lng"]');
            const inputPlaceId = contenedorAutocomplete.querySelector('input[name$="_place_id"]');

            let indiceSeleccionado = -1;

            input.addEventListener('input', function() {
                const terminoBusqueda = this.value.trim();

                if (terminoBusqueda.length < 3) {
                    listaResultados.classList.remove('activo');
                    listaResultados.innerHTML = '';
                    return;
                }

                clearTimeout(Carpooling.debounceTimer);
                Carpooling.debounceTimer = setTimeout(function() {
                    Carpooling.buscarLugares(terminoBusqueda, listaResultados, inputLat, inputLng, inputPlaceId, input);
                }, 300);
            });

            input.addEventListener('keydown', function(evento) {
                const items = listaResultados.querySelectorAll('.carpooling-autocomplete__item');

                if (!listaResultados.classList.contains('activo') || items.length === 0) {
                    return;
                }

                switch (evento.key) {
                    case 'ArrowDown':
                        evento.preventDefault();
                        indiceSeleccionado = Math.min(indiceSeleccionado + 1, items.length - 1);
                        Carpooling.actualizarSeleccion(items, indiceSeleccionado);
                        break;
                    case 'ArrowUp':
                        evento.preventDefault();
                        indiceSeleccionado = Math.max(indiceSeleccionado - 1, 0);
                        Carpooling.actualizarSeleccion(items, indiceSeleccionado);
                        break;
                    case 'Enter':
                        evento.preventDefault();
                        if (indiceSeleccionado >= 0 && items[indiceSeleccionado]) {
                            items[indiceSeleccionado].click();
                        }
                        break;
                    case 'Escape':
                        listaResultados.classList.remove('activo');
                        indiceSeleccionado = -1;
                        break;
                }
            });

            input.addEventListener('blur', function() {
                setTimeout(function() {
                    listaResultados.classList.remove('activo');
                    indiceSeleccionado = -1;
                }, 200);
            });
        });
    };

    Carpooling.buscarLugares = function(termino, listaResultados, inputLat, inputLng, inputPlaceId, inputPrincipal) {
        // Verificar cache
        if (this.lugaresCache[termino]) {
            this.mostrarResultadosLugares(this.lugaresCache[termino], listaResultados, inputLat, inputLng, inputPlaceId, inputPrincipal);
            return;
        }

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_autocompletar_lugar',
                nonce: this.nonce,
                termino: termino,
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.lugares) {
                    Carpooling.lugaresCache[termino] = respuesta.lugares;
                    Carpooling.mostrarResultadosLugares(respuesta.lugares, listaResultados, inputLat, inputLng, inputPlaceId, inputPrincipal);
                }
            },
        });
    };

    Carpooling.mostrarResultadosLugares = function(lugares, listaResultados, inputLat, inputLng, inputPlaceId, inputPrincipal) {
        listaResultados.innerHTML = '';

        if (lugares.length === 0) {
            listaResultados.classList.remove('activo');
            return;
        }

        lugares.forEach(function(lugar) {
            const itemElemento = document.createElement('div');
            itemElemento.className = 'carpooling-autocomplete__item';
            itemElemento.innerHTML = `
                <div class="carpooling-autocomplete__item-nombre">${Carpooling.escapeHtml(lugar.nombre)}</div>
                <div class="carpooling-autocomplete__item-tipo">${Carpooling.escapeHtml(lugar.tipo || '')}</div>
            `;

            itemElemento.addEventListener('click', function() {
                inputPrincipal.value = lugar.nombre;
                if (inputLat) inputLat.value = lugar.lat;
                if (inputLng) inputLng.value = lugar.lng;
                if (inputPlaceId) inputPlaceId.value = lugar.place_id || '';
                listaResultados.classList.remove('activo');

                // Disparar evento de cambio
                inputPrincipal.dispatchEvent(new Event('change', { bubbles: true }));
            });

            listaResultados.appendChild(itemElemento);
        });

        listaResultados.classList.add('activo');
    };

    Carpooling.actualizarSeleccion = function(items, indice) {
        items.forEach(function(item, itemIndex) {
            item.classList.toggle('seleccionado', itemIndex === indice);
        });

        if (items[indice]) {
            items[indice].scrollIntoView({ block: 'nearest' });
        }
    };

    // ========================================
    // Buscador de viajes
    // ========================================
    Carpooling.initBuscador = function() {
        const formularioBusqueda = document.getElementById('carpooling-form-buscar');
        if (!formularioBusqueda) return;

        formularioBusqueda.addEventListener('submit', function(evento) {
            evento.preventDefault();
            Carpooling.buscarViajes();
        });

        // Busqueda al cambiar fecha
        const inputFecha = formularioBusqueda.querySelector('input[name="fecha"]');
        if (inputFecha) {
            inputFecha.addEventListener('change', function() {
                Carpooling.buscarViajes();
            });
        }
    };

    Carpooling.buscarViajes = function() {
        const formularioBusqueda = document.getElementById('carpooling-form-buscar');
        const contenedorResultados = document.getElementById('carpooling-resultados');
        const botonBuscar = formularioBusqueda.querySelector('button[type="submit"]');

        const origenLat = formularioBusqueda.querySelector('input[name="origen_lat"]').value;
        const origenLng = formularioBusqueda.querySelector('input[name="origen_lng"]').value;
        const destinoLat = formularioBusqueda.querySelector('input[name="destino_lat"]').value;
        const destinoLng = formularioBusqueda.querySelector('input[name="destino_lng"]').value;

        if (!origenLat || !origenLng) {
            Carpooling.mostrarAlerta('warning', Carpooling.strings.selecciona_origen);
            return;
        }

        if (!destinoLat || !destinoLng) {
            Carpooling.mostrarAlerta('warning', Carpooling.strings.selecciona_destino);
            return;
        }

        // Mostrar loading
        botonBuscar.disabled = true;
        botonBuscar.innerHTML = '<span class="carpooling-btn__spinner"></span> ' + Carpooling.strings.cargando;
        contenedorResultados.innerHTML = Carpooling.generarSkeletonViajes(3);

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_buscar_viajes',
                nonce: this.nonce,
                origen_lat: origenLat,
                origen_lng: origenLng,
                destino_lat: destinoLat,
                destino_lng: destinoLng,
                fecha: formularioBusqueda.querySelector('input[name="fecha"]').value,
                plazas: formularioBusqueda.querySelector('select[name="plazas"]')?.value || 1,
            },
            success: function(respuesta) {
                botonBuscar.disabled = false;
                botonBuscar.innerHTML = 'Buscar';

                if (respuesta.success) {
                    Carpooling.mostrarResultadosViajes(respuesta.viajes, contenedorResultados);
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error || Carpooling.strings.error_generico);
                }
            },
            error: function() {
                botonBuscar.disabled = false;
                botonBuscar.innerHTML = 'Buscar';
                Carpooling.mostrarAlerta('error', Carpooling.strings.error_generico);
            },
        });
    };

    Carpooling.mostrarResultadosViajes = function(viajes, contenedor) {
        if (viajes.length === 0) {
            contenedor.innerHTML = `
                <div class="carpooling-empty">
                    <div class="carpooling-empty__icono">🚗</div>
                    <h3 class="carpooling-empty__titulo">${Carpooling.strings.sin_resultados}</h3>
                    <p class="carpooling-empty__texto">Prueba con otras fechas o amplia el radio de busqueda</p>
                </div>
            `;
            return;
        }

        let htmlResultados = '<div class="carpooling-viajes-grid">';

        viajes.forEach(function(viaje) {
            htmlResultados += Carpooling.generarTarjetaViaje(viaje);
        });

        htmlResultados += '</div>';
        contenedor.innerHTML = htmlResultados;

        // Agregar eventos a botones de reserva
        contenedor.querySelectorAll('.carpooling-btn-reservar').forEach(function(boton) {
            boton.addEventListener('click', function() {
                const viajeId = this.dataset.viajeId;
                Carpooling.abrirModalReserva(viajeId);
            });
        });

        // Agregar eventos para ver detalles
        contenedor.querySelectorAll('.carpooling-btn-detalles').forEach(function(boton) {
            boton.addEventListener('click', function() {
                const viajeId = this.dataset.viajeId;
                Carpooling.verDetalleViaje(viajeId);
            });
        });
    };

    Carpooling.generarTarjetaViaje = function(viaje) {
        const estrellas = Carpooling.generarEstrellas(viaje.conductor.valoracion);

        return `
            <div class="carpooling-viaje-card carpooling-fade-in" data-viaje-id="${viaje.id}">
                <div class="carpooling-viaje-card__header">
                    <img src="${Carpooling.escapeHtml(viaje.conductor.avatar)}" alt="" class="carpooling-viaje-card__avatar">
                    <div class="carpooling-viaje-card__conductor">
                        <div class="carpooling-viaje-card__conductor-nombre">${Carpooling.escapeHtml(viaje.conductor.nombre)}</div>
                        <div class="carpooling-viaje-card__conductor-rating">
                            ${estrellas}
                            <span>(${viaje.conductor.total_viajes} viajes)</span>
                        </div>
                    </div>
                    <div class="carpooling-viaje-card__precio">
                        <div class="carpooling-viaje-card__precio-valor">${viaje.precio_por_plaza.toFixed(2)}€</div>
                        <div class="carpooling-viaje-card__precio-label">por plaza</div>
                    </div>
                </div>
                <div class="carpooling-viaje-card__body">
                    <div class="carpooling-viaje-card__ruta">
                        <div class="carpooling-viaje-card__ruta-linea">
                            <div class="carpooling-viaje-card__ruta-punto"></div>
                            <div class="carpooling-viaje-card__ruta-conector"></div>
                            <div class="carpooling-viaje-card__ruta-punto carpooling-viaje-card__ruta-punto--destino"></div>
                        </div>
                        <div class="carpooling-viaje-card__ruta-info">
                            <div class="carpooling-viaje-card__lugar">
                                <div class="carpooling-viaje-card__lugar-nombre">${Carpooling.escapeHtml(viaje.origen)}</div>
                                <div class="carpooling-viaje-card__lugar-hora">${Carpooling.escapeHtml(viaje.hora_formateada)}</div>
                            </div>
                            <div class="carpooling-viaje-card__lugar">
                                <div class="carpooling-viaje-card__lugar-nombre">${Carpooling.escapeHtml(viaje.destino)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="carpooling-viaje-card__detalles">
                        <span class="carpooling-viaje-card__tag carpooling-viaje-card__tag--fecha">${Carpooling.escapeHtml(viaje.fecha_formateada)}</span>
                        <span class="carpooling-viaje-card__tag carpooling-viaje-card__tag--plazas">${viaje.plazas_disponibles} plazas</span>
                        ${viaje.permite_mascotas ? '<span class="carpooling-viaje-card__tag">🐾 Mascotas</span>' : ''}
                        ${viaje.permite_equipaje_grande ? '<span class="carpooling-viaje-card__tag">🧳 Equipaje</span>' : ''}
                    </div>
                </div>
                <div class="carpooling-viaje-card__footer">
                    <button type="button" class="carpooling-btn carpooling-btn--outline carpooling-btn--sm carpooling-btn-detalles" data-viaje-id="${viaje.id}">
                        Ver detalles
                    </button>
                    <button type="button" class="carpooling-btn carpooling-btn--primary carpooling-btn--sm carpooling-btn-reservar" data-viaje-id="${viaje.id}">
                        Reservar
                    </button>
                </div>
            </div>
        `;
    };

    // ========================================
    // Publicar viaje
    // ========================================
    Carpooling.initPublicarViaje = function() {
        const formularioPublicar = document.getElementById('carpooling-form-publicar');
        if (!formularioPublicar) return;

        // Calcular precio automatico
        const camposCoords = formularioPublicar.querySelectorAll('input[name$="_lat"], input[name$="_lng"]');
        camposCoords.forEach(function(campo) {
            campo.addEventListener('change', function() {
                Carpooling.calcularPrecioSugerido(formularioPublicar);
            });
        });

        // Selector de dias para ruta recurrente
        const checkboxRecurrente = formularioPublicar.querySelector('input[name="es_recurrente"]');
        if (checkboxRecurrente) {
            checkboxRecurrente.addEventListener('change', function() {
                const seccionRecurrente = document.getElementById('carpooling-seccion-recurrente');
                if (seccionRecurrente) {
                    seccionRecurrente.style.display = this.checked ? 'block' : 'none';
                }
            });
        }

        formularioPublicar.addEventListener('submit', function(evento) {
            evento.preventDefault();
            Carpooling.publicarViaje(this);
        });
    };

    Carpooling.calcularPrecioSugerido = function(formulario) {
        const origenLat = parseFloat(formulario.querySelector('input[name="origen_lat"]').value);
        const origenLng = parseFloat(formulario.querySelector('input[name="origen_lng"]').value);
        const destinoLat = parseFloat(formulario.querySelector('input[name="destino_lat"]').value);
        const destinoLng = parseFloat(formulario.querySelector('input[name="destino_lng"]').value);

        if (!origenLat || !origenLng || !destinoLat || !destinoLng) {
            return;
        }

        const distanciaKm = Carpooling.calcularDistanciaHaversine(origenLat, origenLng, destinoLat, destinoLng);
        const precioSugerido = (distanciaKm * Carpooling.settings.precio_por_km).toFixed(2);

        const inputPrecio = formulario.querySelector('input[name="precio"]');
        const contenedorSugerencia = document.getElementById('carpooling-precio-sugerido');

        if (contenedorSugerencia) {
            contenedorSugerencia.innerHTML = `Precio sugerido: <strong>${precioSugerido}€</strong> (${distanciaKm.toFixed(1)} km)`;
            contenedorSugerencia.style.display = 'block';
        }

        if (inputPrecio && !inputPrecio.value) {
            inputPrecio.value = precioSugerido;
        }
    };

    Carpooling.calcularDistanciaHaversine = function(lat1, lng1, lat2, lng2) {
        const radioTierraKm = 6371;
        const lat1Rad = lat1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;
        const deltaLat = (lat2 - lat1) * Math.PI / 180;
        const deltaLng = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
                  Math.cos(lat1Rad) * Math.cos(lat2Rad) *
                  Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return radioTierraKm * c;
    };

    Carpooling.publicarViaje = function(formulario) {
        const botonSubmit = formulario.querySelector('button[type="submit"]');
        const formData = new FormData(formulario);

        botonSubmit.disabled = true;
        botonSubmit.innerHTML = '<span class="carpooling-btn__spinner"></span> Publicando...';

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_publicar_viaje',
                nonce: this.nonce,
                origen: formData.get('origen'),
                origen_lat: formData.get('origen_lat'),
                origen_lng: formData.get('origen_lng'),
                origen_place_id: formData.get('origen_place_id'),
                destino: formData.get('destino'),
                destino_lat: formData.get('destino_lat'),
                destino_lng: formData.get('destino_lng'),
                destino_place_id: formData.get('destino_place_id'),
                fecha_hora: formData.get('fecha') + ' ' + formData.get('hora'),
                plazas: formData.get('plazas'),
                precio: formData.get('precio'),
                vehiculo_id: formData.get('vehiculo_id'),
                permite_fumar: formData.get('permite_fumar') ? 1 : 0,
                permite_mascotas: formData.get('permite_mascotas') ? 1 : 0,
                permite_equipaje_grande: formData.get('permite_equipaje_grande') ? 1 : 0,
                solo_mujeres: formData.get('solo_mujeres') ? 1 : 0,
                notas: formData.get('notas'),
            },
            success: function(respuesta) {
                botonSubmit.disabled = false;
                botonSubmit.innerHTML = 'Publicar viaje';

                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', Carpooling.strings.viaje_publicado);
                    formulario.reset();

                    // Redirigir a mis viajes
                    setTimeout(function() {
                        const urlMisViajes = formulario.dataset.redirectUrl;
                        if (urlMisViajes) {
                            window.location.href = urlMisViajes;
                        }
                    }, 1500);
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error || Carpooling.strings.error_generico);
                }
            },
            error: function() {
                botonSubmit.disabled = false;
                botonSubmit.innerHTML = 'Publicar viaje';
                Carpooling.mostrarAlerta('error', Carpooling.strings.error_generico);
            },
        });
    };

    // ========================================
    // Mis viajes (conductor)
    // ========================================
    Carpooling.initMisViajes = function() {
        const contenedorMisViajes = document.getElementById('carpooling-mis-viajes');
        if (!contenedorMisViajes) return;

        // Cargar viajes iniciales
        Carpooling.cargarMisViajes();

        // Tabs de filtro
        const tabs = contenedorMisViajes.querySelectorAll('.carpooling-tab');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(tabItem) { tabItem.classList.remove('activo'); });
                this.classList.add('activo');
                Carpooling.cargarMisViajes(this.dataset.estado);
            });
        });
    };

    Carpooling.cargarMisViajes = function(estado) {
        const contenedorLista = document.getElementById('carpooling-lista-mis-viajes');
        contenedorLista.innerHTML = Carpooling.generarSkeletonLista(3);

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_mis_viajes',
                nonce: this.nonce,
                estado: estado || '',
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarListaMisViajes(respuesta.viajes, contenedorLista);
                } else {
                    contenedorLista.innerHTML = '<div class="carpooling-alerta carpooling-alerta--error">' + (respuesta.error || Carpooling.strings.error_generico) + '</div>';
                }
            },
        });
    };

    Carpooling.mostrarListaMisViajes = function(viajes, contenedor) {
        if (viajes.length === 0) {
            contenedor.innerHTML = `
                <div class="carpooling-empty">
                    <div class="carpooling-empty__icono">🚗</div>
                    <h3 class="carpooling-empty__titulo">No tienes viajes</h3>
                    <p class="carpooling-empty__texto">Publica tu primer viaje y empieza a compartir gastos</p>
                    <a href="#publicar" class="carpooling-btn carpooling-btn--primary">Publicar viaje</a>
                </div>
            `;
            return;
        }

        let htmlLista = '<ul class="carpooling-lista-viajes">';

        viajes.forEach(function(viaje) {
            const fechaViaje = new Date(viaje.fecha_hora);
            const diaViaje = fechaViaje.getDate();
            const mesViaje = fechaViaje.toLocaleDateString('es-ES', { month: 'short' });

            htmlLista += `
                <li class="carpooling-lista-viajes__item">
                    <div class="carpooling-lista-viajes__fecha">
                        <div class="carpooling-lista-viajes__dia">${diaViaje}</div>
                        <div class="carpooling-lista-viajes__mes">${mesViaje}</div>
                    </div>
                    <div class="carpooling-lista-viajes__info">
                        <div class="carpooling-lista-viajes__ruta">${Carpooling.escapeHtml(viaje.origen)} → ${Carpooling.escapeHtml(viaje.destino)}</div>
                        <div class="carpooling-lista-viajes__hora">${Carpooling.escapeHtml(viaje.hora_formateada)}</div>
                        <div class="carpooling-lista-viajes__detalles">
                            <span>${viaje.plazas_disponibles}/${viaje.plazas_totales} plazas</span>
                            <span>${viaje.precio_por_plaza.toFixed(2)}€/plaza</span>
                            <span class="carpooling-estado carpooling-estado--${viaje.estado}">${viaje.estado}</span>
                        </div>
                        ${viaje.reservas && viaje.reservas.length > 0 ? Carpooling.generarListaReservasViaje(viaje.reservas, viaje.id) : ''}
                    </div>
                    <div class="carpooling-lista-viajes__acciones">
                        ${viaje.estado === 'publicado' || viaje.estado === 'completo' ? `
                            <button type="button" class="carpooling-btn carpooling-btn--outline carpooling-btn--sm carpooling-btn-cancelar-viaje" data-viaje-id="${viaje.id}">
                                Cancelar
                            </button>
                        ` : ''}
                    </div>
                </li>
            `;
        });

        htmlLista += '</ul>';
        contenedor.innerHTML = htmlLista;

        // Eventos para botones
        contenedor.querySelectorAll('.carpooling-btn-cancelar-viaje').forEach(function(boton) {
            boton.addEventListener('click', function() {
                if (confirm('¿Seguro que quieres cancelar este viaje?')) {
                    Carpooling.cancelarViaje(this.dataset.viajeId);
                }
            });
        });

        contenedor.querySelectorAll('.carpooling-btn-confirmar-reserva').forEach(function(boton) {
            boton.addEventListener('click', function() {
                Carpooling.gestionarReserva(this.dataset.reservaId, 'confirmar');
            });
        });

        contenedor.querySelectorAll('.carpooling-btn-rechazar-reserva').forEach(function(boton) {
            boton.addEventListener('click', function() {
                const motivo = prompt('Motivo del rechazo (opcional):');
                Carpooling.gestionarReserva(this.dataset.reservaId, 'rechazar', motivo);
            });
        });
    };

    Carpooling.generarListaReservasViaje = function(reservas, viajeId) {
        let html = '<div class="carpooling-reservas-viaje" style="margin-top: 12px;">';
        html += '<strong style="font-size: 13px;">Reservas:</strong>';

        reservas.forEach(function(reserva) {
            html += `
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px; padding: 8px; background: #f9fafb; border-radius: 6px;">
                    <span>${Carpooling.escapeHtml(reserva.nombre_pasajero)} - ${reserva.plazas_reservadas} plaza(s)</span>
                    <span class="carpooling-estado carpooling-estado--${reserva.estado}">${reserva.estado}</span>
                    ${reserva.estado === 'solicitada' ? `
                        <button type="button" class="carpooling-btn carpooling-btn--secondary carpooling-btn--sm carpooling-btn-confirmar-reserva" data-reserva-id="${reserva.id}">Confirmar</button>
                        <button type="button" class="carpooling-btn carpooling-btn--outline carpooling-btn--sm carpooling-btn-rechazar-reserva" data-reserva-id="${reserva.id}">Rechazar</button>
                    ` : ''}
                </div>
            `;
        });

        html += '</div>';
        return html;
    };

    Carpooling.gestionarReserva = function(reservaId, accion, motivo) {
        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_confirmar_reserva',
                nonce: this.nonce,
                reserva_id: reservaId,
                accion: accion,
                motivo: motivo || '',
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', respuesta.message);
                    Carpooling.cargarMisViajes();
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    Carpooling.cancelarViaje = function(viajeId) {
        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_cancelar_viaje',
                nonce: this.nonce,
                viaje_id: viajeId,
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', respuesta.message);
                    Carpooling.cargarMisViajes();
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    // ========================================
    // Mis reservas (pasajero)
    // ========================================
    Carpooling.initMisReservas = function() {
        const contenedorMisReservas = document.getElementById('carpooling-mis-reservas');
        if (!contenedorMisReservas) return;

        Carpooling.cargarMisReservas();

        const tabs = contenedorMisReservas.querySelectorAll('.carpooling-tab');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(tabItem) { tabItem.classList.remove('activo'); });
                this.classList.add('activo');
                Carpooling.cargarMisReservas(this.dataset.estado);
            });
        });
    };

    Carpooling.cargarMisReservas = function(estado) {
        const contenedorLista = document.getElementById('carpooling-lista-mis-reservas');
        contenedorLista.innerHTML = Carpooling.generarSkeletonLista(3);

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_mis_reservas',
                nonce: this.nonce,
                estado: estado || '',
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarListaMisReservas(respuesta.reservas, contenedorLista);
                }
            },
        });
    };

    Carpooling.mostrarListaMisReservas = function(reservas, contenedor) {
        if (reservas.length === 0) {
            contenedor.innerHTML = `
                <div class="carpooling-empty">
                    <div class="carpooling-empty__icono">🎫</div>
                    <h3 class="carpooling-empty__titulo">No tienes reservas</h3>
                    <p class="carpooling-empty__texto">Busca viajes disponibles y reserva tu plaza</p>
                </div>
            `;
            return;
        }

        let htmlLista = '<ul class="carpooling-lista-viajes">';

        reservas.forEach(function(reserva) {
            const fechaViaje = new Date(reserva.fecha_hora);
            const diaViaje = fechaViaje.getDate();
            const mesViaje = fechaViaje.toLocaleDateString('es-ES', { month: 'short' });

            htmlLista += `
                <li class="carpooling-lista-viajes__item">
                    <div class="carpooling-lista-viajes__fecha">
                        <div class="carpooling-lista-viajes__dia">${diaViaje}</div>
                        <div class="carpooling-lista-viajes__mes">${mesViaje}</div>
                    </div>
                    <div class="carpooling-lista-viajes__info">
                        <div class="carpooling-lista-viajes__ruta">${Carpooling.escapeHtml(reserva.origen)} → ${Carpooling.escapeHtml(reserva.destino)}</div>
                        <div class="carpooling-lista-viajes__hora">Conductor: ${Carpooling.escapeHtml(reserva.nombre_conductor)}</div>
                        <div class="carpooling-lista-viajes__detalles">
                            <span>${reserva.plazas_reservadas} plaza(s)</span>
                            <span>${parseFloat(reserva.coste_total).toFixed(2)}€ total</span>
                            <span class="carpooling-estado carpooling-estado--${reserva.estado_reserva}">${reserva.estado_reserva}</span>
                        </div>
                    </div>
                    <div class="carpooling-lista-viajes__acciones">
                        ${reserva.estado_reserva === 'solicitada' || reserva.estado_reserva === 'confirmada' ? `
                            <button type="button" class="carpooling-btn carpooling-btn--outline carpooling-btn--sm carpooling-btn-cancelar-reserva" data-reserva-id="${reserva.reserva_id}">
                                Cancelar
                            </button>
                        ` : ''}
                        ${reserva.estado_reserva === 'completada' && !reserva.valoracion_realizada ? `
                            <button type="button" class="carpooling-btn carpooling-btn--primary carpooling-btn--sm carpooling-btn-valorar" data-reserva-id="${reserva.reserva_id}">
                                Valorar
                            </button>
                        ` : ''}
                    </div>
                </li>
            `;
        });

        htmlLista += '</ul>';
        contenedor.innerHTML = htmlLista;

        // Eventos
        contenedor.querySelectorAll('.carpooling-btn-cancelar-reserva').forEach(function(boton) {
            boton.addEventListener('click', function() {
                if (confirm(Carpooling.strings.cancelar_reserva)) {
                    Carpooling.cancelarReserva(this.dataset.reservaId);
                }
            });
        });

        contenedor.querySelectorAll('.carpooling-btn-valorar').forEach(function(boton) {
            boton.addEventListener('click', function() {
                Carpooling.abrirModalValoracion(this.dataset.reservaId);
            });
        });
    };

    Carpooling.cancelarReserva = function(reservaId) {
        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_cancelar_reserva',
                nonce: this.nonce,
                reserva_id: reservaId,
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', respuesta.message);
                    Carpooling.cargarMisReservas();
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    // ========================================
    // Modales
    // ========================================
    Carpooling.initModales = function() {
        // Cerrar modal con click fuera
        document.querySelectorAll('.carpooling-modal').forEach(function(modal) {
            modal.addEventListener('click', function(evento) {
                if (evento.target === this) {
                    Carpooling.cerrarModal(this.id);
                }
            });
        });

        // Cerrar con boton
        document.querySelectorAll('.carpooling-modal__cerrar').forEach(function(boton) {
            boton.addEventListener('click', function() {
                const modalId = this.closest('.carpooling-modal').id;
                Carpooling.cerrarModal(modalId);
            });
        });

        // Cerrar con Escape
        document.addEventListener('keydown', function(evento) {
            if (evento.key === 'Escape') {
                document.querySelectorAll('.carpooling-modal.activo').forEach(function(modal) {
                    Carpooling.cerrarModal(modal.id);
                });
            }
        });
    };

    Carpooling.abrirModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('activo');
            document.body.style.overflow = 'hidden';
        }
    };

    Carpooling.cerrarModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('activo');
            document.body.style.overflow = '';
        }
    };

    Carpooling.abrirModalReserva = function(viajeId) {
        const modal = document.getElementById('carpooling-modal-reserva');
        if (!modal) {
            // Crear modal si no existe
            Carpooling.crearModalReserva(viajeId);
            return;
        }

        modal.querySelector('input[name="viaje_id"]').value = viajeId;
        Carpooling.abrirModal('carpooling-modal-reserva');
    };

    Carpooling.crearModalReserva = function(viajeId) {
        const htmlModal = `
            <div id="carpooling-modal-reserva" class="carpooling-modal">
                <div class="carpooling-modal__contenido">
                    <div class="carpooling-modal__header">
                        <h3 class="carpooling-modal__titulo">Reservar plaza</h3>
                        <button type="button" class="carpooling-modal__cerrar">&times;</button>
                    </div>
                    <div class="carpooling-modal__body">
                        <form id="carpooling-form-reserva">
                            <input type="hidden" name="viaje_id" value="${viajeId}">
                            <div class="carpooling-campo" style="margin-bottom: 16px;">
                                <label class="carpooling-campo__label">Plazas a reservar</label>
                                <select name="plazas" class="carpooling-campo__select">
                                    <option value="1">1 plaza</option>
                                    <option value="2">2 plazas</option>
                                    <option value="3">3 plazas</option>
                                    <option value="4">4 plazas</option>
                                </select>
                            </div>
                            <div class="carpooling-campo" style="margin-bottom: 16px;">
                                <label class="carpooling-campo__label">Mensaje para el conductor (opcional)</label>
                                <textarea name="mensaje" class="carpooling-campo__textarea" placeholder="Ej: Llevare una maleta pequeña..."></textarea>
                            </div>
                            <div class="carpooling-campo">
                                <label class="carpooling-campo__label">Telefono de contacto</label>
                                <input type="tel" name="telefono" class="carpooling-campo__input" placeholder="600 000 000">
                            </div>
                        </form>
                    </div>
                    <div class="carpooling-modal__footer">
                        <button type="button" class="carpooling-btn carpooling-btn--outline" onclick="Carpooling.cerrarModal('carpooling-modal-reserva')">Cancelar</button>
                        <button type="button" class="carpooling-btn carpooling-btn--primary" onclick="Carpooling.enviarReserva()">Confirmar reserva</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', htmlModal);
        Carpooling.initModales();
        Carpooling.abrirModal('carpooling-modal-reserva');
    };

    Carpooling.enviarReserva = function() {
        const formulario = document.getElementById('carpooling-form-reserva');
        const formData = new FormData(formulario);

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_reservar_plaza',
                nonce: this.nonce,
                viaje_id: formData.get('viaje_id'),
                plazas: formData.get('plazas'),
                mensaje: formData.get('mensaje'),
                telefono: formData.get('telefono'),
            },
            success: function(respuesta) {
                Carpooling.cerrarModal('carpooling-modal-reserva');

                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', Carpooling.strings.reserva_exitosa);
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    // ========================================
    // Valoraciones
    // ========================================
    Carpooling.initValoraciones = function() {
        // Rating input interactivo
        document.querySelectorAll('.carpooling-rating-input').forEach(function(contenedor) {
            const inputs = contenedor.querySelectorAll('input');
            inputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    // Feedback visual ya manejado por CSS
                });
            });
        });
    };

    Carpooling.abrirModalValoracion = function(reservaId) {
        const htmlModal = `
            <div id="carpooling-modal-valoracion" class="carpooling-modal activo">
                <div class="carpooling-modal__contenido">
                    <div class="carpooling-modal__header">
                        <h3 class="carpooling-modal__titulo">Valorar viaje</h3>
                        <button type="button" class="carpooling-modal__cerrar">&times;</button>
                    </div>
                    <div class="carpooling-modal__body">
                        <form id="carpooling-form-valoracion">
                            <input type="hidden" name="reserva_id" value="${reservaId}">
                            <div class="carpooling-campo" style="margin-bottom: 20px;">
                                <label class="carpooling-campo__label">Puntuacion general</label>
                                <div class="carpooling-rating-input">
                                    <input type="radio" name="puntuacion" value="5" id="star5"><label for="star5">★</label>
                                    <input type="radio" name="puntuacion" value="4" id="star4"><label for="star4">★</label>
                                    <input type="radio" name="puntuacion" value="3" id="star3"><label for="star3">★</label>
                                    <input type="radio" name="puntuacion" value="2" id="star2"><label for="star2">★</label>
                                    <input type="radio" name="puntuacion" value="1" id="star1"><label for="star1">★</label>
                                </div>
                            </div>
                            <div class="carpooling-campo">
                                <label class="carpooling-campo__label">Comentario (opcional)</label>
                                <textarea name="comentario" class="carpooling-campo__textarea" placeholder="Comparte tu experiencia..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="carpooling-modal__footer">
                        <button type="button" class="carpooling-btn carpooling-btn--outline" onclick="Carpooling.cerrarModal('carpooling-modal-valoracion')">Cancelar</button>
                        <button type="button" class="carpooling-btn carpooling-btn--primary" onclick="Carpooling.enviarValoracion()">Enviar valoracion</button>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal anterior si existe
        const modalExistente = document.getElementById('carpooling-modal-valoracion');
        if (modalExistente) modalExistente.remove();

        document.body.insertAdjacentHTML('beforeend', htmlModal);
        document.body.style.overflow = 'hidden';
        Carpooling.initModales();
    };

    Carpooling.enviarValoracion = function() {
        const formulario = document.getElementById('carpooling-form-valoracion');
        const formData = new FormData(formulario);

        if (!formData.get('puntuacion')) {
            Carpooling.mostrarAlerta('warning', 'Selecciona una puntuacion');
            return;
        }

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_valorar_viaje',
                nonce: this.nonce,
                reserva_id: formData.get('reserva_id'),
                puntuacion: formData.get('puntuacion'),
                comentario: formData.get('comentario'),
            },
            success: function(respuesta) {
                Carpooling.cerrarModal('carpooling-modal-valoracion');

                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', 'Valoracion enviada correctamente');
                    Carpooling.cargarMisReservas();
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    // ========================================
    // Vehiculos
    // ========================================
    Carpooling.initVehiculos = function() {
        const formularioVehiculo = document.getElementById('carpooling-form-vehiculo');
        if (!formularioVehiculo) return;

        formularioVehiculo.addEventListener('submit', function(evento) {
            evento.preventDefault();
            Carpooling.guardarVehiculo(this);
        });

        // Cargar vehiculos existentes
        Carpooling.cargarVehiculos();
    };

    Carpooling.cargarVehiculos = function() {
        const contenedor = document.getElementById('carpooling-vehiculos-lista');
        if (!contenedor) return;

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_obtener_vehiculos',
                nonce: this.nonce,
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.vehiculos) {
                    Carpooling.mostrarVehiculos(respuesta.vehiculos, contenedor);
                }
            },
        });
    };

    Carpooling.mostrarVehiculos = function(vehiculos, contenedor) {
        if (vehiculos.length === 0) {
            contenedor.innerHTML = '<p class="carpooling-empty__texto">No tienes vehiculos registrados</p>';
            return;
        }

        let html = '<div class="carpooling-vehiculos">';

        vehiculos.forEach(function(vehiculo) {
            html += `
                <div class="carpooling-vehiculo-opcion ${vehiculo.es_predeterminado ? 'seleccionado' : ''}" data-vehiculo-id="${vehiculo.id}">
                    <div class="carpooling-vehiculo-opcion__nombre">${Carpooling.escapeHtml(vehiculo.marca)} ${Carpooling.escapeHtml(vehiculo.modelo)}</div>
                    <div class="carpooling-vehiculo-opcion__detalle">${Carpooling.escapeHtml(vehiculo.color || '')} ${vehiculo.anio || ''}</div>
                    ${vehiculo.es_predeterminado ? '<span style="font-size: 11px; color: #3b82f6;">Predeterminado</span>' : ''}
                </div>
            `;
        });

        html += '</div>';
        contenedor.innerHTML = html;

        // Seleccion de vehiculo
        contenedor.querySelectorAll('.carpooling-vehiculo-opcion').forEach(function(opcion) {
            opcion.addEventListener('click', function() {
                contenedor.querySelectorAll('.carpooling-vehiculo-opcion').forEach(function(item) {
                    item.classList.remove('seleccionado');
                });
                this.classList.add('seleccionado');

                // Actualizar input oculto si existe
                const inputVehiculo = document.querySelector('input[name="vehiculo_id"]');
                if (inputVehiculo) {
                    inputVehiculo.value = this.dataset.vehiculoId;
                }
            });
        });
    };

    Carpooling.guardarVehiculo = function(formulario) {
        const formData = new FormData(formulario);

        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_guardar_vehiculo',
                nonce: this.nonce,
                vehiculo_id: formData.get('vehiculo_id') || 0,
                marca: formData.get('marca'),
                modelo: formData.get('modelo'),
                anio: formData.get('anio'),
                color: formData.get('color'),
                matricula: formData.get('matricula'),
                plazas: formData.get('plazas'),
                predeterminado: formData.get('predeterminado') ? 1 : 0,
            },
            success: function(respuesta) {
                if (respuesta.success) {
                    Carpooling.mostrarAlerta('success', respuesta.message);
                    formulario.reset();
                    Carpooling.cargarVehiculos();
                } else {
                    Carpooling.mostrarAlerta('error', respuesta.error);
                }
            },
        });
    };

    // ========================================
    // Utilidades
    // ========================================
    Carpooling.mostrarAlerta = function(tipo, mensaje) {
        const alertaId = 'carpooling-alerta-' + Date.now();
        const html = `
            <div id="${alertaId}" class="carpooling-alerta carpooling-alerta--${tipo} carpooling-slide-up" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;">
                <div class="carpooling-alerta__contenido">${Carpooling.escapeHtml(mensaje)}</div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);

        setTimeout(function() {
            const alerta = document.getElementById(alertaId);
            if (alerta) {
                alerta.style.opacity = '0';
                setTimeout(function() { alerta.remove(); }, 300);
            }
        }, 4000);
    };

    Carpooling.escapeHtml = function(texto) {
        if (!texto) return '';
        const divTemp = document.createElement('div');
        divTemp.textContent = texto;
        return divTemp.innerHTML;
    };

    Carpooling.generarEstrellas = function(puntuacion) {
        let html = '';
        const puntuacionRedondeada = Math.round(puntuacion || 0);

        for (let i = 1; i <= 5; i++) {
            if (i <= puntuacionRedondeada) {
                html += '<span style="color: #f59e0b;">★</span>';
            } else {
                html += '<span style="color: #d1d5db;">★</span>';
            }
        }

        return html + ` <span style="font-size: 12px;">${(puntuacion || 0).toFixed(1)}</span>`;
    };

    Carpooling.generarSkeletonViajes = function(cantidad) {
        let html = '<div class="carpooling-viajes-grid">';
        for (let i = 0; i < cantidad; i++) {
            html += '<div class="carpooling-skeleton carpooling-skeleton--card"></div>';
        }
        html += '</div>';
        return html;
    };

    Carpooling.generarSkeletonLista = function(cantidad) {
        let html = '';
        for (let i = 0; i < cantidad; i++) {
            html += `
                <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                    <div class="carpooling-skeleton carpooling-skeleton--text" style="width: 60%;"></div>
                    <div class="carpooling-skeleton carpooling-skeleton--text" style="width: 40%;"></div>
                </div>
            `;
        }
        return html;
    };

    Carpooling.verDetalleViaje = function(viajeId) {
        $.ajax({
            url: this.ajaxUrl,
            type: 'POST',
            data: {
                action: 'carpooling_detalle_viaje',
                nonce: this.nonce,
                viaje_id: viajeId,
            },
            success: function(respuesta) {
                if (respuesta.success && respuesta.viaje) {
                    Carpooling.mostrarModalDetalleViaje(respuesta.viaje);
                }
            },
        });
    };

    Carpooling.mostrarModalDetalleViaje = function(viaje) {
        const htmlModal = `
            <div id="carpooling-modal-detalle" class="carpooling-modal activo">
                <div class="carpooling-modal__contenido" style="max-width: 600px;">
                    <div class="carpooling-modal__header">
                        <h3 class="carpooling-modal__titulo">Detalles del viaje</h3>
                        <button type="button" class="carpooling-modal__cerrar">&times;</button>
                    </div>
                    <div class="carpooling-modal__body">
                        <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px;">
                            <img src="${viaje.conductor.avatar}" style="width: 60px; height: 60px; border-radius: 50%;">
                            <div>
                                <div style="font-weight: 600; font-size: 18px;">${Carpooling.escapeHtml(viaje.conductor.nombre)}</div>
                                <div>${Carpooling.generarEstrellas(viaje.conductor.valoracion)} (${viaje.conductor.total_viajes} viajes)</div>
                            </div>
                            <div style="margin-left: auto; text-align: right;">
                                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">${viaje.precio_por_plaza.toFixed(2)}€</div>
                                <div style="font-size: 12px; color: #6b7280;">por plaza</div>
                            </div>
                        </div>
                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                            <div style="margin-bottom: 12px;">
                                <strong>Origen:</strong> ${Carpooling.escapeHtml(viaje.origen)}
                            </div>
                            <div style="margin-bottom: 12px;">
                                <strong>Destino:</strong> ${Carpooling.escapeHtml(viaje.destino)}
                            </div>
                            <div style="margin-bottom: 12px;">
                                <strong>Fecha:</strong> ${Carpooling.escapeHtml(viaje.fecha_formateada)}
                            </div>
                            <div>
                                <strong>Hora salida:</strong> ${Carpooling.escapeHtml(viaje.hora_formateada)}
                            </div>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                            <span class="carpooling-viaje-card__tag carpooling-viaje-card__tag--plazas">${viaje.plazas_disponibles} plazas disponibles</span>
                            ${viaje.permite_mascotas ? '<span class="carpooling-viaje-card__tag">🐾 Mascotas OK</span>' : ''}
                            ${viaje.permite_equipaje_grande ? '<span class="carpooling-viaje-card__tag">🧳 Equipaje grande OK</span>' : ''}
                            ${viaje.solo_mujeres ? '<span class="carpooling-viaje-card__tag">👩 Solo mujeres</span>' : ''}
                        </div>
                        ${viaje.vehiculo ? `
                            <div style="margin-bottom: 16px;">
                                <strong>Vehiculo:</strong> ${Carpooling.escapeHtml(viaje.vehiculo.marca)} ${Carpooling.escapeHtml(viaje.vehiculo.modelo)} ${Carpooling.escapeHtml(viaje.vehiculo.color || '')}
                                ${viaje.vehiculo.verificado ? '<span style="color: #22c55e;">✓ Verificado</span>' : ''}
                            </div>
                        ` : ''}
                        ${viaje.notas ? `
                            <div>
                                <strong>Notas del conductor:</strong><br>
                                <p style="margin-top: 4px; color: #4b5563;">${Carpooling.escapeHtml(viaje.notas)}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="carpooling-modal__footer">
                        <button type="button" class="carpooling-btn carpooling-btn--outline" onclick="Carpooling.cerrarModal('carpooling-modal-detalle')">Cerrar</button>
                        <button type="button" class="carpooling-btn carpooling-btn--primary" onclick="Carpooling.cerrarModal('carpooling-modal-detalle'); Carpooling.abrirModalReserva(${viaje.id});">Reservar plaza</button>
                    </div>
                </div>
            </div>
        `;

        const modalExistente = document.getElementById('carpooling-modal-detalle');
        if (modalExistente) modalExistente.remove();

        document.body.insertAdjacentHTML('beforeend', htmlModal);
        document.body.style.overflow = 'hidden';
        Carpooling.initModales();
    };

    // Exponer globalmente
    window.Carpooling = Carpooling;

})(jQuery);
