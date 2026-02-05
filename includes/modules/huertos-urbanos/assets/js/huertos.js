/**
 * Huertos Urbanos - JavaScript del Módulo
 * Sistema completo de huertos comunitarios
 *
 * @package FlavorChatIA
 * @subpackage HuertosUrbanos
 */

(function($) {
    'use strict';

    /**
     * Objeto principal del módulo Huertos Urbanos
     */
    const HuertosUrbanos = {

        /**
         * Configuración por defecto
         */
        configuracion: {
            ajaxUrl: typeof flavorHuertosConfig !== 'undefined' ? flavorHuertosConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorHuertosConfig !== 'undefined' ? flavorHuertosConfig.nonce : '',
            mapaZoomInicial: 12,
            mapaLatitudInicial: 40.4168,
            mapaLongitudInicial: -3.7038,
            animacionDuracion: 300
        },

        /**
         * Estado de la aplicación
         */
        estado: {
            mapaInstancia: null,
            marcadoresHuertos: [],
            huertosData: [],
            cultivosData: [],
            tareasData: [],
            intercambiosData: [],
            mesActualCalendario: new Date().getMonth(),
            anioActualCalendario: new Date().getFullYear(),
            parcelaUsuario: null,
            modalActivo: null
        },

        /**
         * Inicialización del módulo
         */
        init: function() {
            this.bindEventos();
            this.inicializarComponentes();
            this.cargarDatosIniciales();
        },

        /**
         * Vincula eventos del DOM
         */
        bindEventos: function() {
            const self = this;

            // Delegación de eventos para acciones principales
            $(document).on('click', '[data-huertos-accion]', function(evento) {
                evento.preventDefault();
                const accion = $(this).data('huertos-accion');
                const parametros = $(this).data('huertos-params') || {};
                self.ejecutarAccion(accion, parametros, $(this));
            });

            // Formularios
            $(document).on('submit', '.huertos-formulario', function(evento) {
                evento.preventDefault();
                self.procesarFormulario($(this));
            });

            // Modal eventos
            $(document).on('click', '.huertos-modal-cerrar, .huertos-modal-overlay', function(evento) {
                if (evento.target === this) {
                    self.cerrarModal();
                }
            });

            // Navegación calendario
            $(document).on('click', '.calendario-cultivos-navegacion button', function() {
                const direccion = $(this).data('direccion');
                self.navegarCalendario(direccion);
            });

            // Filtros mapa
            $(document).on('change', '.huertos-mapa-filtro select', function() {
                self.filtrarMarcadores($(this).val());
            });

            // Búsqueda en tiempo real
            $(document).on('input', '.huertos-busqueda input', function() {
                self.buscarEnTiempoReal($(this).val());
            });

            // Tecla Escape para cerrar modal
            $(document).on('keydown', function(evento) {
                if (evento.key === 'Escape' && self.estado.modalActivo) {
                    self.cerrarModal();
                }
            });
        },

        /**
         * Inicializa componentes específicos de la página
         */
        inicializarComponentes: function() {
            // Inicializar mapa si existe el contenedor
            if ($('.huertos-mapa').length) {
                this.inicializarMapa();
            }

            // Inicializar calendario si existe
            if ($('.calendario-cultivos-contenedor').length) {
                this.renderizarCalendarioCultivos();
            }

            // Inicializar tabs si existen
            if ($('.huertos-tabs').length) {
                this.inicializarTabs();
            }
        },

        /**
         * Carga datos iniciales necesarios
         */
        cargarDatosIniciales: function() {
            // Cargar huertos para el mapa
            if ($('.huertos-mapa').length) {
                this.cargarHuertos();
            }

            // Cargar parcela del usuario si está autenticado
            if ($('.mi-parcela-contenedor').length) {
                this.cargarMiParcela();
            }

            // Cargar tareas próximas
            if ($('.tareas-lista').length) {
                this.cargarTareas();
            }

            // Cargar intercambios
            if ($('.intercambios-contenedor').length) {
                this.cargarIntercambios();
            }
        },

        /**
         * Ejecuta una acción específica
         */
        ejecutarAccion: function(accion, parametros, elemento) {
            const acciones = {
                'solicitar-parcela': () => this.abrirModalSolicitudParcela(parametros),
                'registrar-cultivo': () => this.abrirModalRegistrarCultivo(parametros),
                'apuntarse-tarea': () => this.apuntarseTarea(parametros, elemento),
                'publicar-intercambio': () => this.abrirModalPublicarIntercambio(),
                'contactar-intercambio': () => this.contactarIntercambio(parametros),
                'ver-detalle-huerto': () => this.verDetalleHuerto(parametros),
                'ver-detalle-cultivo': () => this.verDetalleCultivo(parametros),
                'completar-tarea': () => this.completarTarea(parametros, elemento),
                'cancelar-intercambio': () => this.cancelarIntercambio(parametros, elemento)
            };

            if (acciones[accion]) {
                acciones[accion]();
            } else {
                console.warn('Acción no reconocida:', accion);
            }
        },

        /**
         * Inicializa el mapa de huertos
         */
        inicializarMapa: function() {
            const contenedorMapa = document.querySelector('.huertos-mapa');
            if (!contenedorMapa || typeof L === 'undefined') {
                console.warn('Leaflet no está disponible o no hay contenedor de mapa');
                return;
            }

            // Crear instancia del mapa
            this.estado.mapaInstancia = L.map(contenedorMapa).setView(
                [this.configuracion.mapaLatitudInicial, this.configuracion.mapaLongitudInicial],
                this.configuracion.mapaZoomInicial
            );

            // Añadir capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.estado.mapaInstancia);

            // Intentar obtener ubicación del usuario
            this.obtenerUbicacionUsuario();
        },

        /**
         * Obtiene la ubicación del usuario
         */
        obtenerUbicacionUsuario: function() {
            const self = this;

            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(posicion) {
                        const latitudUsuario = posicion.coords.latitude;
                        const longitudUsuario = posicion.coords.longitude;

                        if (self.estado.mapaInstancia) {
                            self.estado.mapaInstancia.setView([latitudUsuario, longitudUsuario], 13);

                            // Marcador de ubicación del usuario
                            L.marker([latitudUsuario, longitudUsuario], {
                                icon: L.divIcon({
                                    className: 'huertos-marcador-usuario',
                                    html: '<div class="marcador-usuario-icono"></div>',
                                    iconSize: [20, 20]
                                })
                            }).addTo(self.estado.mapaInstancia)
                            .bindPopup('Tu ubicación');
                        }

                        // Recargar huertos ordenados por distancia
                        self.cargarHuertos(latitudUsuario, longitudUsuario);
                    },
                    function(error) {
                        console.log('No se pudo obtener la ubicación:', error.message);
                    }
                );
            }
        },

        /**
         * Carga los huertos desde el servidor
         */
        cargarHuertos: function(latitud, longitud) {
            const self = this;

            this.mostrarCargando('.huertos-mapa');

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_listar',
                    nonce: this.configuracion.nonce,
                    lat: latitud || '',
                    lng: longitud || ''
                },
                success: function(respuesta) {
                    self.ocultarCargando('.huertos-mapa');

                    if (respuesta.success) {
                        self.estado.huertosData = respuesta.data.huertos;
                        self.renderizarMarcadoresHuertos();
                        self.renderizarListaHuertos();
                    } else {
                        self.mostrarNotificacion('Error al cargar huertos', 'error');
                    }
                },
                error: function() {
                    self.ocultarCargando('.huertos-mapa');
                    self.mostrarNotificacion('Error de conexión', 'error');
                }
            });
        },

        /**
         * Renderiza los marcadores de huertos en el mapa
         */
        renderizarMarcadoresHuertos: function() {
            const self = this;

            // Limpiar marcadores existentes
            this.estado.marcadoresHuertos.forEach(function(marcador) {
                self.estado.mapaInstancia.removeLayer(marcador);
            });
            this.estado.marcadoresHuertos = [];

            // Crear nuevos marcadores
            this.estado.huertosData.forEach(function(huerto) {
                if (huerto.latitud && huerto.longitud) {
                    const estaDisponible = huerto.parcelas_disponibles > 0;
                    const claseEstado = estaDisponible ? 'disponible' : 'completo';

                    const icono = L.divIcon({
                        className: 'huertos-marcador ' + claseEstado,
                        html: '<span>' + huerto.parcelas_disponibles + '</span>',
                        iconSize: [40, 40]
                    });

                    const marcador = L.marker([huerto.latitud, huerto.longitud], { icon: icono })
                        .addTo(self.estado.mapaInstancia)
                        .bindPopup(self.generarPopupHuerto(huerto));

                    marcador.huertoId = huerto.id;
                    self.estado.marcadoresHuertos.push(marcador);
                }
            });

            // Ajustar vista para mostrar todos los marcadores
            if (this.estado.marcadoresHuertos.length > 0) {
                const grupo = L.featureGroup(this.estado.marcadoresHuertos);
                this.estado.mapaInstancia.fitBounds(grupo.getBounds().pad(0.1));
            }
        },

        /**
         * Genera el contenido del popup de un huerto
         */
        generarPopupHuerto: function(huerto) {
            const disponibilidadTexto = huerto.parcelas_disponibles > 0
                ? huerto.parcelas_disponibles + ' parcelas disponibles'
                : 'Sin parcelas disponibles';

            return `
                <div class="huertos-popup-info">
                    <h4>${this.escaparHtml(huerto.nombre)}</h4>
                    <p class="direccion">${this.escaparHtml(huerto.direccion)}</p>
                    <div class="estadisticas">
                        <div class="estadistica">
                            <span class="estadistica-valor">${huerto.superficie_m2}</span>
                            <span class="estadistica-etiqueta">m2</span>
                        </div>
                        <div class="estadistica">
                            <span class="estadistica-valor">${huerto.parcelas_totales}</span>
                            <span class="estadistica-etiqueta">parcelas</span>
                        </div>
                    </div>
                    <p><strong>${disponibilidadTexto}</strong></p>
                    <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                            data-huertos-accion="ver-detalle-huerto"
                            data-huertos-params='{"id": ${huerto.id}}'>
                        Ver detalles
                    </button>
                </div>
            `;
        },

        /**
         * Renderiza la lista de huertos
         */
        renderizarListaHuertos: function() {
            const contenedor = $('.huertos-grid');
            if (!contenedor.length) return;

            let htmlTarjetas = '';

            this.estado.huertosData.forEach(function(huerto) {
                const imagenUrl = huerto.foto || '';
                const badgeClase = huerto.parcelas_disponibles > 0 ? 'disponible' : 'ocupada';
                const badgeTexto = huerto.parcelas_disponibles > 0
                    ? huerto.parcelas_disponibles + ' disponibles'
                    : 'Completo';

                htmlTarjetas += `
                    <div class="huertos-tarjeta huertos-animacion-aparecer">
                        ${imagenUrl ? `<img src="${imagenUrl}" alt="${huerto.nombre}" class="huertos-tarjeta-imagen">`
                                    : '<div class="huertos-tarjeta-imagen"></div>'}
                        <div class="huertos-tarjeta-contenido">
                            <h3 class="huertos-tarjeta-nombre">${HuertosUrbanos.escaparHtml(huerto.nombre)}</h3>
                            <p class="huertos-tarjeta-direccion">
                                <span class="dashicons dashicons-location"></span>
                                ${HuertosUrbanos.escaparHtml(huerto.direccion)}
                            </p>
                            <div class="huertos-tarjeta-estadisticas">
                                <div class="huertos-tarjeta-estadistica">
                                    <span class="huertos-tarjeta-estadistica-valor">${huerto.superficie_m2}</span>
                                    <span class="huertos-tarjeta-estadistica-etiqueta">m2</span>
                                </div>
                                <div class="huertos-tarjeta-estadistica">
                                    <span class="huertos-tarjeta-estadistica-valor">${huerto.parcelas_totales}</span>
                                    <span class="huertos-tarjeta-estadistica-etiqueta">parcelas</span>
                                </div>
                            </div>
                            <span class="huertos-badge huertos-badge-${badgeClase}">${badgeTexto}</span>
                            <div class="huertos-tarjeta-acciones">
                                <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                                        data-huertos-accion="ver-detalle-huerto"
                                        data-huertos-params='{"id": ${huerto.id}}'>
                                    Ver detalles
                                </button>
                                ${huerto.parcelas_disponibles > 0 ? `
                                <button class="huertos-boton huertos-boton-secundario huertos-boton-pequeno"
                                        data-huertos-accion="solicitar-parcela"
                                        data-huertos-params='{"huerto_id": ${huerto.id}}'>
                                    Solicitar
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            contenedor.html(htmlTarjetas);
        },

        /**
         * Filtra los marcadores del mapa
         */
        filtrarMarcadores: function(filtro) {
            const self = this;

            this.estado.marcadoresHuertos.forEach(function(marcador) {
                const huerto = self.estado.huertosData.find(h => h.id === marcador.huertoId);

                if (!huerto) return;

                let mostrar = true;

                switch (filtro) {
                    case 'disponibles':
                        mostrar = huerto.parcelas_disponibles > 0;
                        break;
                    case 'completos':
                        mostrar = huerto.parcelas_disponibles === 0;
                        break;
                }

                if (mostrar) {
                    marcador.addTo(self.estado.mapaInstancia);
                } else {
                    self.estado.mapaInstancia.removeLayer(marcador);
                }
            });
        },

        /**
         * Carga los datos de la parcela del usuario
         */
        cargarMiParcela: function() {
            const self = this;

            this.mostrarCargando('.mi-parcela-contenedor');

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_mi_parcela',
                    nonce: this.configuracion.nonce
                },
                success: function(respuesta) {
                    self.ocultarCargando('.mi-parcela-contenedor');

                    if (respuesta.success && respuesta.data.parcela) {
                        self.estado.parcelaUsuario = respuesta.data.parcela;
                        self.renderizarMiParcela();
                    } else {
                        self.renderizarSinParcela();
                    }
                },
                error: function() {
                    self.ocultarCargando('.mi-parcela-contenedor');
                    self.mostrarNotificacion('Error al cargar tu parcela', 'error');
                }
            });
        },

        /**
         * Renderiza la información de la parcela del usuario
         */
        renderizarMiParcela: function() {
            const parcela = this.estado.parcelaUsuario;
            const contenedor = $('.mi-parcela-contenedor');

            const htmlParcela = `
                <div class="mi-parcela-info">
                    <div class="mi-parcela-numero">Parcela ${this.escaparHtml(parcela.numero)}</div>
                    <div class="mi-parcela-huerto">${this.escaparHtml(parcela.huerto_nombre)}</div>
                    <div class="mi-parcela-detalles">
                        <div class="mi-parcela-detalle">
                            <span class="mi-parcela-detalle-etiqueta">Superficie</span>
                            <span class="mi-parcela-detalle-valor">${parcela.superficie_m2} m2</span>
                        </div>
                        <div class="mi-parcela-detalle">
                            <span class="mi-parcela-detalle-etiqueta">Orientación</span>
                            <span class="mi-parcela-detalle-valor">${parcela.orientacion || 'N/A'}</span>
                        </div>
                        <div class="mi-parcela-detalle">
                            <span class="mi-parcela-detalle-etiqueta">Desde</span>
                            <span class="mi-parcela-detalle-valor">${this.formatearFecha(parcela.fecha_asignacion)}</span>
                        </div>
                        <div class="mi-parcela-detalle">
                            <span class="mi-parcela-detalle-etiqueta">Cultivos activos</span>
                            <span class="mi-parcela-detalle-valor">${parcela.cultivos_activos || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="mi-parcela-cultivos">
                    <div class="mi-parcela-cultivos-titulo">
                        <span>Mis Cultivos</span>
                        <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                                data-huertos-accion="registrar-cultivo"
                                data-huertos-params='{"parcela_id": ${parcela.id}}'>
                            + Nuevo cultivo
                        </button>
                    </div>
                    <div class="cultivos-lista" id="lista-cultivos-parcela">
                        <div class="huertos-cargando">
                            <div class="huertos-cargando-spinner"></div>
                            Cargando cultivos...
                        </div>
                    </div>
                </div>
            `;

            contenedor.html(htmlParcela);
            this.cargarCultivosParcela(parcela.id);
        },

        /**
         * Renderiza mensaje cuando el usuario no tiene parcela
         */
        renderizarSinParcela: function() {
            const contenedor = $('.mi-parcela-contenedor');

            contenedor.html(`
                <div class="huertos-alerta huertos-alerta-info">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong>Aún no tienes una parcela asignada</strong>
                        <p>Explora los huertos disponibles y solicita una parcela para empezar a cultivar.</p>
                        <button class="huertos-boton huertos-boton-primario"
                                onclick="document.querySelector('.huertos-mapa-contenedor').scrollIntoView({behavior: 'smooth'})">
                            Ver huertos disponibles
                        </button>
                    </div>
                </div>
            `);
        },

        /**
         * Carga los cultivos de una parcela
         */
        cargarCultivosParcela: function(parcelaId) {
            const self = this;

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_cultivos_parcela',
                    nonce: this.configuracion.nonce,
                    parcela_id: parcelaId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.estado.cultivosData = respuesta.data.cultivos;
                        self.renderizarListaCultivos('#lista-cultivos-parcela');
                    }
                }
            });
        },

        /**
         * Renderiza la lista de cultivos
         */
        renderizarListaCultivos: function(selector) {
            const contenedor = $(selector);
            const cultivos = this.estado.cultivosData;

            if (!cultivos || cultivos.length === 0) {
                contenedor.html(`
                    <div class="huertos-alerta huertos-alerta-info">
                        No hay cultivos registrados. ¡Empieza a plantar!
                    </div>
                `);
                return;
            }

            const iconosCultivos = {
                'tomate': '🍅',
                'lechuga': '🥬',
                'zanahoria': '🥕',
                'pepino': '🥒',
                'pimiento': '🌶️',
                'calabacin': '🥒',
                'berenjena': '🍆',
                'patata': '🥔',
                'cebolla': '🧅',
                'ajo': '🧄',
                'fresa': '🍓',
                'default': '🌱'
            };

            let htmlCultivos = '';

            cultivos.forEach(function(cultivo) {
                const nombreLower = cultivo.nombre.toLowerCase();
                const icono = iconosCultivos[nombreLower] || iconosCultivos['default'];

                htmlCultivos += `
                    <div class="cultivo-item">
                        <div class="cultivo-icono">${icono}</div>
                        <div class="cultivo-info">
                            <div class="cultivo-nombre">${HuertosUrbanos.escaparHtml(cultivo.nombre)}</div>
                            ${cultivo.variedad ? `<div class="cultivo-variedad">${HuertosUrbanos.escaparHtml(cultivo.variedad)}</div>` : ''}
                            <div class="cultivo-fecha">Sembrado: ${HuertosUrbanos.formatearFecha(cultivo.fecha_siembra)}</div>
                        </div>
                        <span class="cultivo-estado ${cultivo.estado}">${cultivo.estado}</span>
                    </div>
                `;
            });

            contenedor.html(htmlCultivos);
        },

        /**
         * Renderiza el calendario de cultivos
         */
        renderizarCalendarioCultivos: function() {
            const contenedor = $('.calendario-cultivos-contenedor');
            const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const mesesCompletos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

            // Datos de calendario de cultivos (zona templada)
            const cultivosCalendario = [
                { nombre: 'Tomate', icono: '🍅', siembra: [2, 3, 4], cosecha: [6, 7, 8, 9] },
                { nombre: 'Lechuga', icono: '🥬', siembra: [1, 2, 3, 8, 9], cosecha: [3, 4, 5, 10, 11] },
                { nombre: 'Zanahoria', icono: '🥕', siembra: [2, 3, 4, 7, 8], cosecha: [5, 6, 7, 10, 11] },
                { nombre: 'Pimiento', icono: '🌶️', siembra: [2, 3], cosecha: [7, 8, 9] },
                { nombre: 'Calabacín', icono: '🥒', siembra: [3, 4, 5], cosecha: [6, 7, 8, 9] },
                { nombre: 'Berenjena', icono: '🍆', siembra: [2, 3], cosecha: [7, 8, 9] },
                { nombre: 'Cebolla', icono: '🧅', siembra: [0, 1, 8, 9], cosecha: [5, 6, 7] },
                { nombre: 'Ajo', icono: '🧄', siembra: [9, 10, 11], cosecha: [5, 6] },
                { nombre: 'Fresa', icono: '🍓', siembra: [8, 9], cosecha: [4, 5, 6] },
                { nombre: 'Espinaca', icono: '🥬', siembra: [1, 2, 8, 9, 10], cosecha: [3, 4, 5, 10, 11] },
                { nombre: 'Judía verde', icono: '🫛', siembra: [3, 4, 5], cosecha: [6, 7, 8] },
                { nombre: 'Pepino', icono: '🥒', siembra: [3, 4, 5], cosecha: [6, 7, 8, 9] }
            ];

            let htmlCalendario = `
                <div class="calendario-cultivos-cabecera">
                    <h3 class="huertos-titulo-seccion">
                        <span class="icono">📅</span>
                        Calendario de Cultivos ${this.estado.anioActualCalendario}
                    </h3>
                    <div class="calendario-cultivos-navegacion">
                        <button data-direccion="anterior" title="Año anterior">←</button>
                        <span class="calendario-cultivos-mes-actual">${this.estado.anioActualCalendario}</span>
                        <button data-direccion="siguiente" title="Año siguiente">→</button>
                    </div>
                </div>
                <div class="calendario-cultivos-grid">
                    <div class="calendario-cultivos-cabecera-celda">Cultivo</div>
            `;

            // Cabeceras de meses
            meses.forEach(function(mes) {
                htmlCalendario += `<div class="calendario-cultivos-cabecera-celda">${mes}</div>`;
            });

            // Filas de cultivos
            cultivosCalendario.forEach(function(cultivo) {
                htmlCalendario += `
                    <div class="calendario-cultivos-fila">
                        <div class="calendario-cultivos-nombre">
                            <span>${cultivo.icono}</span>
                            ${cultivo.nombre}
                        </div>
                `;

                for (let mes = 0; mes < 12; mes++) {
                    const esSiembra = cultivo.siembra.includes(mes);
                    const esCosecha = cultivo.cosecha.includes(mes);

                    let contenidoCelda = '';

                    if (esSiembra && esCosecha) {
                        contenidoCelda = `<div class="calendario-cultivos-barra siembra-cosecha">S/C</div>`;
                    } else if (esSiembra) {
                        contenidoCelda = `<div class="calendario-cultivos-barra siembra">Siembra</div>`;
                    } else if (esCosecha) {
                        contenidoCelda = `<div class="calendario-cultivos-barra cosecha">Cosecha</div>`;
                    }

                    htmlCalendario += `<div class="calendario-cultivos-mes">${contenidoCelda}</div>`;
                }

                htmlCalendario += `</div>`;
            });

            htmlCalendario += `
                </div>
                <div class="calendario-cultivos-leyenda">
                    <div class="calendario-cultivos-leyenda-item">
                        <div class="calendario-cultivos-leyenda-color siembra"></div>
                        <span>Época de siembra</span>
                    </div>
                    <div class="calendario-cultivos-leyenda-item">
                        <div class="calendario-cultivos-leyenda-color cosecha"></div>
                        <span>Época de cosecha</span>
                    </div>
                </div>
            `;

            contenedor.html(htmlCalendario);
        },

        /**
         * Navega entre años en el calendario
         */
        navegarCalendario: function(direccion) {
            if (direccion === 'anterior') {
                this.estado.anioActualCalendario--;
            } else {
                this.estado.anioActualCalendario++;
            }
            this.renderizarCalendarioCultivos();
        },

        /**
         * Carga las tareas del huerto
         */
        cargarTareas: function() {
            const self = this;

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_tareas',
                    nonce: this.configuracion.nonce
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.estado.tareasData = respuesta.data.tareas;
                        self.renderizarTareas();
                    }
                }
            });
        },

        /**
         * Renderiza la lista de tareas
         */
        renderizarTareas: function() {
            const contenedor = $('.tareas-lista');
            const tareas = this.estado.tareasData;

            if (!tareas || tareas.length === 0) {
                contenedor.html(`
                    <div class="huertos-alerta huertos-alerta-info">
                        No hay tareas programadas próximamente.
                    </div>
                `);
                return;
            }

            let htmlTareas = '';

            tareas.forEach(function(tarea) {
                const fecha = new Date(tarea.fecha);
                const dia = fecha.getDate();
                const mesNombre = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                                   'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'][fecha.getMonth()];

                htmlTareas += `
                    <div class="tarea-item">
                        <div class="tarea-fecha">
                            <div class="tarea-fecha-dia">${dia}</div>
                            <div class="tarea-fecha-mes">${mesNombre}</div>
                        </div>
                        <div class="tarea-contenido">
                            <div class="tarea-titulo">${HuertosUrbanos.escaparHtml(tarea.titulo)}</div>
                            <div class="tarea-descripcion">${HuertosUrbanos.escaparHtml(tarea.descripcion)}</div>
                            <div class="tarea-meta">
                                <span><span class="dashicons dashicons-clock"></span> ${tarea.hora}</span>
                                <span><span class="dashicons dashicons-groups"></span> ${tarea.participantes}/${tarea.max_participantes}</span>
                            </div>
                        </div>
                        <span class="tarea-tipo ${tarea.tipo}">${tarea.tipo}</span>
                        ${tarea.puede_apuntarse ? `
                        <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                                data-huertos-accion="apuntarse-tarea"
                                data-huertos-params='{"tarea_id": ${tarea.id}}'>
                            Apuntarse
                        </button>
                        ` : ''}
                    </div>
                `;
            });

            contenedor.html(htmlTareas);
        },

        /**
         * Carga los intercambios disponibles
         */
        cargarIntercambios: function() {
            const self = this;

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_intercambios',
                    nonce: this.configuracion.nonce
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.estado.intercambiosData = respuesta.data.intercambios;
                        self.renderizarIntercambios();
                    }
                }
            });
        },

        /**
         * Renderiza los intercambios
         */
        renderizarIntercambios: function() {
            const contenedor = $('.intercambios-contenedor');
            const intercambios = this.estado.intercambiosData;

            if (!intercambios || intercambios.length === 0) {
                contenedor.html(`
                    <div class="huertos-alerta huertos-alerta-info" style="grid-column: 1 / -1;">
                        No hay intercambios publicados actualmente.
                        <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                                data-huertos-accion="publicar-intercambio" style="margin-left: 15px;">
                            Publicar el primero
                        </button>
                    </div>
                `);
                return;
            }

            let htmlIntercambios = '';

            intercambios.forEach(function(intercambio) {
                const inicialUsuario = intercambio.usuario_nombre ? intercambio.usuario_nombre.charAt(0).toUpperCase() : '?';

                htmlIntercambios += `
                    <div class="intercambio-tarjeta">
                        ${intercambio.foto ? `<img src="${intercambio.foto}" alt="${intercambio.titulo}" class="intercambio-imagen">`
                                           : '<div class="intercambio-imagen"></div>'}
                        <div class="intercambio-contenido">
                            <span class="intercambio-tipo ${intercambio.tipo}">${intercambio.tipo}</span>
                            <h4 class="intercambio-titulo">${HuertosUrbanos.escaparHtml(intercambio.titulo)}</h4>
                            <p class="intercambio-descripcion">${HuertosUrbanos.escaparHtml(intercambio.descripcion)}</p>
                            <div class="intercambio-usuario">
                                <div class="intercambio-usuario-avatar">${inicialUsuario}</div>
                                <div class="intercambio-usuario-info">
                                    <div class="intercambio-usuario-nombre">${HuertosUrbanos.escaparHtml(intercambio.usuario_nombre)}</div>
                                    <div class="intercambio-usuario-huerto">${HuertosUrbanos.escaparHtml(intercambio.huerto_nombre)}</div>
                                </div>
                                <button class="huertos-boton huertos-boton-secundario huertos-boton-pequeno"
                                        data-huertos-accion="contactar-intercambio"
                                        data-huertos-params='{"id": ${intercambio.id}}'>
                                    Contactar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            contenedor.html(htmlIntercambios);
        },

        /**
         * Abre el modal para solicitar parcela
         */
        abrirModalSolicitudParcela: function(parametros) {
            const htmlModal = `
                <div class="huertos-modal-cabecera">
                    <h3 class="huertos-modal-titulo">Solicitar Parcela</h3>
                    <button class="huertos-modal-cerrar">&times;</button>
                </div>
                <div class="huertos-modal-contenido">
                    <form class="huertos-formulario" data-accion="solicitar-parcela">
                        <input type="hidden" name="huerto_id" value="${parametros.huerto_id}">

                        <div class="huertos-campo">
                            <label>¿Por qué quieres una parcela?</label>
                            <textarea name="motivacion" required placeholder="Cuéntanos tu interés en cultivar..."></textarea>
                        </div>

                        <div class="huertos-campo">
                            <label>¿Tienes experiencia previa en huertos?</label>
                            <select name="experiencia" required>
                                <option value="">Selecciona una opción</option>
                                <option value="ninguna">Ninguna, soy principiante</option>
                                <option value="basica">Básica, he cultivado en macetas</option>
                                <option value="media">Media, he tenido huerto antes</option>
                                <option value="avanzada">Avanzada, cultivo regularmente</option>
                            </select>
                        </div>

                        <div class="huertos-campo">
                            <label>
                                <input type="checkbox" name="compromiso" required>
                                Me comprometo a cumplir con las normas del huerto y mis turnos de riego
                            </label>
                        </div>

                        <div class="huertos-campo">
                            <label>
                                <input type="checkbox" name="horas_minimas" required>
                                Puedo dedicar al menos 4 horas mensuales al cuidado del huerto
                            </label>
                        </div>
                    </form>
                </div>
                <div class="huertos-modal-pie">
                    <button type="button" class="huertos-boton huertos-boton-secundario huertos-modal-cerrar">Cancelar</button>
                    <button type="submit" form="form-solicitar-parcela" class="huertos-boton huertos-boton-primario">Enviar solicitud</button>
                </div>
            `;

            this.abrirModal(htmlModal);

            // Asignar ID al formulario
            $('.huertos-modal .huertos-formulario').attr('id', 'form-solicitar-parcela');
        },

        /**
         * Abre el modal para registrar cultivo
         */
        abrirModalRegistrarCultivo: function(parametros) {
            const hoy = new Date().toISOString().split('T')[0];

            const htmlModal = `
                <div class="huertos-modal-cabecera">
                    <h3 class="huertos-modal-titulo">Registrar Nuevo Cultivo</h3>
                    <button class="huertos-modal-cerrar">&times;</button>
                </div>
                <div class="huertos-modal-contenido">
                    <form class="huertos-formulario" id="form-registrar-cultivo" data-accion="registrar-cultivo">
                        <input type="hidden" name="parcela_id" value="${parametros.parcela_id}">

                        <div class="huertos-campo">
                            <label>Nombre del cultivo *</label>
                            <input type="text" name="nombre" required placeholder="Ej: Tomate, Lechuga...">
                        </div>

                        <div class="huertos-campo">
                            <label>Variedad (opcional)</label>
                            <input type="text" name="variedad" placeholder="Ej: Cherry, Romana...">
                        </div>

                        <div class="huertos-campo">
                            <label>Fecha de siembra *</label>
                            <input type="date" name="fecha_siembra" required value="${hoy}">
                        </div>

                        <div class="huertos-campo">
                            <label>Fecha estimada de cosecha</label>
                            <input type="date" name="fecha_cosecha_estimada">
                            <span class="huertos-campo-ayuda">Consulta el calendario de cultivos para orientarte</span>
                        </div>

                        <div class="huertos-campo">
                            <label>Notas (opcional)</label>
                            <textarea name="notas" placeholder="Observaciones, origen de las semillas..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="huertos-modal-pie">
                    <button type="button" class="huertos-boton huertos-boton-secundario huertos-modal-cerrar">Cancelar</button>
                    <button type="submit" form="form-registrar-cultivo" class="huertos-boton huertos-boton-primario">Registrar cultivo</button>
                </div>
            `;

            this.abrirModal(htmlModal);
        },

        /**
         * Abre el modal para publicar intercambio
         */
        abrirModalPublicarIntercambio: function() {
            const htmlModal = `
                <div class="huertos-modal-cabecera">
                    <h3 class="huertos-modal-titulo">Publicar Intercambio</h3>
                    <button class="huertos-modal-cerrar">&times;</button>
                </div>
                <div class="huertos-modal-contenido">
                    <form class="huertos-formulario" id="form-publicar-intercambio" data-accion="publicar-intercambio">
                        <div class="huertos-campo">
                            <label>Tipo de intercambio *</label>
                            <select name="tipo" required>
                                <option value="">Selecciona el tipo</option>
                                <option value="semillas">Semillas</option>
                                <option value="cosecha">Cosecha</option>
                                <option value="plantulas">Plántulas</option>
                            </select>
                        </div>

                        <div class="huertos-campo">
                            <label>Título *</label>
                            <input type="text" name="titulo" required placeholder="Ej: Semillas de tomate cherry">
                        </div>

                        <div class="huertos-campo">
                            <label>Descripción *</label>
                            <textarea name="descripcion" required placeholder="Describe lo que ofreces y lo que te gustaría a cambio..."></textarea>
                        </div>

                        <div class="huertos-campo">
                            <label>Cantidad disponible</label>
                            <input type="text" name="cantidad" placeholder="Ej: 50 semillas, 2kg, 5 plántulas...">
                        </div>
                    </form>
                </div>
                <div class="huertos-modal-pie">
                    <button type="button" class="huertos-boton huertos-boton-secundario huertos-modal-cerrar">Cancelar</button>
                    <button type="submit" form="form-publicar-intercambio" class="huertos-boton huertos-boton-primario">Publicar</button>
                </div>
            `;

            this.abrirModal(htmlModal);
        },

        /**
         * Abre un modal con contenido específico
         */
        abrirModal: function(contenido) {
            // Crear overlay si no existe
            let overlay = $('.huertos-modal-overlay');

            if (!overlay.length) {
                overlay = $('<div class="huertos-modal-overlay"><div class="huertos-modal"></div></div>');
                $('body').append(overlay);
            }

            overlay.find('.huertos-modal').html(contenido);

            setTimeout(function() {
                overlay.addClass('activo');
            }, 10);

            this.estado.modalActivo = true;
            $('body').css('overflow', 'hidden');
        },

        /**
         * Cierra el modal activo
         */
        cerrarModal: function() {
            const overlay = $('.huertos-modal-overlay');
            overlay.removeClass('activo');

            setTimeout(function() {
                overlay.find('.huertos-modal').html('');
            }, 300);

            this.estado.modalActivo = false;
            $('body').css('overflow', '');
        },

        /**
         * Procesa el envío de un formulario
         */
        procesarFormulario: function(formulario) {
            const self = this;
            const accion = formulario.data('accion');
            const datos = {};

            // Serializar datos del formulario
            formulario.serializeArray().forEach(function(campo) {
                datos[campo.name] = campo.value;
            });

            // Obtener checkboxes
            formulario.find('input[type="checkbox"]').each(function() {
                datos[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
            });

            const botonSubmit = formulario.find('[type="submit"]');
            botonSubmit.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_' + accion.replace(/-/g, '_'),
                    nonce: this.configuracion.nonce,
                    ...datos
                },
                success: function(respuesta) {
                    botonSubmit.prop('disabled', false);

                    if (respuesta.success) {
                        self.mostrarNotificacion(respuesta.data.message || 'Operación completada', 'exito');
                        self.cerrarModal();
                        self.cargarDatosIniciales(); // Recargar datos
                    } else {
                        self.mostrarNotificacion(respuesta.data.message || 'Error al procesar', 'error');
                    }
                },
                error: function() {
                    botonSubmit.prop('disabled', false);
                    self.mostrarNotificacion('Error de conexión', 'error');
                }
            });
        },

        /**
         * Apuntarse a una tarea
         */
        apuntarseTarea: function(parametros, elemento) {
            const self = this;

            elemento.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_apuntarse_tarea',
                    nonce: this.configuracion.nonce,
                    tarea_id: parametros.tarea_id
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarNotificacion('Te has apuntado a la tarea', 'exito');
                        elemento.text('Apuntado').addClass('huertos-boton-secundario').removeClass('huertos-boton-primario');
                    } else {
                        self.mostrarNotificacion(respuesta.data.message || 'No se pudo apuntar', 'error');
                        elemento.prop('disabled', false).text('Apuntarse');
                    }
                },
                error: function() {
                    elemento.prop('disabled', false).text('Apuntarse');
                    self.mostrarNotificacion('Error de conexión', 'error');
                }
            });
        },

        /**
         * Ver detalle de un huerto
         */
        verDetalleHuerto: function(parametros) {
            const self = this;

            $.ajax({
                url: this.configuracion.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_huertos_detalle',
                    nonce: this.configuracion.nonce,
                    huerto_id: parametros.id
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        self.mostrarDetalleHuerto(respuesta.data.huerto);
                    }
                }
            });
        },

        /**
         * Muestra el detalle de un huerto en modal
         */
        mostrarDetalleHuerto: function(huerto) {
            const htmlModal = `
                <div class="huertos-modal-cabecera">
                    <h3 class="huertos-modal-titulo">${this.escaparHtml(huerto.nombre)}</h3>
                    <button class="huertos-modal-cerrar">&times;</button>
                </div>
                <div class="huertos-modal-contenido">
                    ${huerto.foto ? `<img src="${huerto.foto}" alt="${huerto.nombre}" style="width:100%; border-radius:8px; margin-bottom:20px;">` : ''}

                    <p><strong>Dirección:</strong> ${this.escaparHtml(huerto.direccion)}</p>
                    <p><strong>Superficie:</strong> ${huerto.superficie_m2} m2</p>
                    <p><strong>Parcelas totales:</strong> ${huerto.num_parcelas}</p>
                    <p><strong>Parcelas disponibles:</strong> ${huerto.parcelas_disponibles}</p>

                    ${huerto.descripcion ? `<p><strong>Descripción:</strong> ${this.escaparHtml(huerto.descripcion)}</p>` : ''}
                    ${huerto.horario_acceso ? `<p><strong>Horario de acceso:</strong> ${this.escaparHtml(huerto.horario_acceso)}</p>` : ''}
                    ${huerto.normas ? `<div><strong>Normas:</strong><p>${this.escaparHtml(huerto.normas)}</p></div>` : ''}
                </div>
                <div class="huertos-modal-pie">
                    <button type="button" class="huertos-boton huertos-boton-secundario huertos-modal-cerrar">Cerrar</button>
                    ${huerto.parcelas_disponibles > 0 ? `
                    <button class="huertos-boton huertos-boton-primario"
                            data-huertos-accion="solicitar-parcela"
                            data-huertos-params='{"huerto_id": ${huerto.id}}'>
                        Solicitar parcela
                    </button>
                    ` : ''}
                </div>
            `;

            this.abrirModal(htmlModal);
        },

        /**
         * Muestra una notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            const claseAlerta = 'huertos-alerta-' + (tipo || 'info');
            const icono = {
                'exito': 'yes',
                'error': 'warning',
                'advertencia': 'info',
                'info': 'info'
            }[tipo] || 'info';

            const notificacion = $(`
                <div class="huertos-alerta ${claseAlerta}" style="position:fixed; top:20px; right:20px; z-index:10001; max-width:400px;">
                    <span class="dashicons dashicons-${icono}"></span>
                    ${this.escaparHtml(mensaje)}
                </div>
            `);

            $('body').append(notificacion);

            setTimeout(function() {
                notificacion.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        },

        /**
         * Muestra indicador de carga
         */
        mostrarCargando: function(selector) {
            $(selector).addClass('huertos-cargando-activo').append(`
                <div class="huertos-cargando-overlay">
                    <div class="huertos-cargando">
                        <div class="huertos-cargando-spinner"></div>
                        Cargando...
                    </div>
                </div>
            `);
        },

        /**
         * Oculta indicador de carga
         */
        ocultarCargando: function(selector) {
            $(selector).removeClass('huertos-cargando-activo')
                      .find('.huertos-cargando-overlay').remove();
        },

        /**
         * Formatea una fecha
         */
        formatearFecha: function(fechaString) {
            if (!fechaString) return 'N/A';

            const fecha = new Date(fechaString);
            const opciones = { day: 'numeric', month: 'short', year: 'numeric' };

            return fecha.toLocaleDateString('es-ES', opciones);
        },

        /**
         * Escapa HTML para prevenir XSS
         */
        escaparHtml: function(texto) {
            if (!texto) return '';

            const elemento = document.createElement('div');
            elemento.textContent = texto;
            return elemento.innerHTML;
        },

        /**
         * Inicializa el sistema de tabs
         */
        inicializarTabs: function() {
            const self = this;

            $(document).on('click', '.huertos-tabs .huertos-tab', function() {
                const tabId = $(this).data('tab');
                const contenedor = $(this).closest('.huertos-tabs-contenedor');

                contenedor.find('.huertos-tab').removeClass('activo');
                $(this).addClass('activo');

                contenedor.find('.huertos-tab-contenido').removeClass('activo');
                contenedor.find(`[data-tab-contenido="${tabId}"]`).addClass('activo');
            });
        },

        /**
         * Búsqueda en tiempo real
         */
        buscarEnTiempoReal: function(termino) {
            const self = this;
            const terminoLower = termino.toLowerCase();

            // Filtrar huertos
            $('.huertos-tarjeta').each(function() {
                const nombre = $(this).find('.huertos-tarjeta-nombre').text().toLowerCase();
                const direccion = $(this).find('.huertos-tarjeta-direccion').text().toLowerCase();

                if (nombre.includes(terminoLower) || direccion.includes(terminoLower)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    };

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        HuertosUrbanos.init();
    });

    // Exponer para uso externo si es necesario
    window.FlavorHuertosUrbanos = HuertosUrbanos;

})(jQuery);
