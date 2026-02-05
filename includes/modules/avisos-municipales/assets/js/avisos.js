/**
 * Avisos Municipales - JavaScript principal
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const AvisosMunicipales = {
        config: {
            ajaxUrl: typeof flavorAvisosConfig !== 'undefined' ? flavorAvisosConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorAvisosConfig !== 'undefined' ? flavorAvisosConfig.nonce : '',
            pushVapidKey: typeof flavorAvisosConfig !== 'undefined' ? flavorAvisosConfig.vapidKey : '',
            i18n: typeof flavorAvisosConfig !== 'undefined' ? flavorAvisosConfig.i18n : {}
        },

        selectors: {
            container: '.avisos-municipales-container',
            lista: '.avisos-lista',
            filtroCategoria: '#avisos-filtro-categoria',
            filtroPrioridad: '#avisos-filtro-prioridad',
            filtroZona: '#avisos-filtro-zona',
            btnFiltrar: '.avisos-btn-filtrar',
            btnCargarMas: '.avisos-btn-cargar-mas',
            formSuscripcion: '.avisos-suscripcion-form',
            btnConfirmarLectura: '.avisos-btn-confirmar',
            pushPrompt: '.avisos-push-prompt',
            avisoCard: '.aviso-card'
        },

        state: {
            pagina: 1,
            cargando: false,
            hayMas: true,
            filtros: {
                categoria: '',
                prioridad: '',
                zona: ''
            }
        },

        init: function() {
            this.bindEvents();
            this.initPushNotifications();
            this.marcarAvisosVistos();
            this.initInfiniteScroll();
        },

        bindEvents: function() {
            const self = this;

            $(document).on('change', this.selectors.filtroCategoria + ',' +
                this.selectors.filtroPrioridad + ',' +
                this.selectors.filtroZona, function() {
                self.actualizarFiltros();
            });

            $(document).on('click', this.selectors.btnFiltrar, function(e) {
                e.preventDefault();
                self.filtrarAvisos();
            });

            $(document).on('click', this.selectors.btnCargarMas, function(e) {
                e.preventDefault();
                self.cargarMasAvisos();
            });

            $(document).on('submit', this.selectors.formSuscripcion, function(e) {
                e.preventDefault();
                self.procesarSuscripcion($(this));
            });

            $(document).on('click', this.selectors.btnConfirmarLectura, function(e) {
                e.preventDefault();
                const avisoId = $(this).data('aviso-id');
                self.confirmarLectura(avisoId, $(this));
            });

            $(document).on('click', this.selectors.avisoCard, function(e) {
                if (!$(e.target).is('a, button')) {
                    const avisoId = $(this).data('aviso-id');
                    if (avisoId) {
                        self.marcarLeido(avisoId);
                    }
                }
            });

            $(document).on('click', '.avisos-push-aceptar', function() {
                self.solicitarPermisosPush();
            });

            $(document).on('click', '.avisos-push-rechazar, .avisos-push-prompt-close', function() {
                self.cerrarPromptPush();
            });

            $(document).on('click', '.avisos-zona-item', function() {
                $(this).toggleClass('active');
                $(this).find('input[type="checkbox"]').prop('checked', $(this).hasClass('active'));
            });
        },

        actualizarFiltros: function() {
            this.state.filtros.categoria = $(this.selectors.filtroCategoria).val() || '';
            this.state.filtros.prioridad = $(this.selectors.filtroPrioridad).val() || '';
            this.state.filtros.zona = $(this.selectors.filtroZona).val() || '';
        },

        filtrarAvisos: function() {
            this.state.pagina = 1;
            this.state.hayMas = true;
            this.cargarAvisos(true);
        },

        cargarAvisos: function(reemplazar = false) {
            const self = this;

            if (this.state.cargando) return;

            this.state.cargando = true;
            this.mostrarLoading();

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_listar',
                    nonce: this.config.nonce,
                    pagina: this.state.pagina,
                    categoria: this.state.filtros.categoria,
                    prioridad: this.state.filtros.prioridad,
                    zona: this.state.filtros.zona
                },
                success: function(response) {
                    if (response.success) {
                        self.renderizarAvisos(response.data.avisos, reemplazar);
                        self.state.hayMas = response.data.hay_mas;
                        self.actualizarBotonCargarMas();
                    } else {
                        self.mostrarError(response.data.message || 'Error al cargar avisos');
                    }
                },
                error: function() {
                    self.mostrarError('Error de conexion');
                },
                complete: function() {
                    self.state.cargando = false;
                    self.ocultarLoading();
                }
            });
        },

        cargarMasAvisos: function() {
            if (!this.state.hayMas || this.state.cargando) return;
            this.state.pagina++;
            this.cargarAvisos(false);
        },

        renderizarAvisos: function(avisos, reemplazar) {
            const contenedor = $(this.selectors.lista);

            if (reemplazar) {
                contenedor.empty();
            }

            if (avisos.length === 0 && reemplazar) {
                contenedor.html(this.getEmptyTemplate());
                return;
            }

            avisos.forEach(aviso => {
                contenedor.append(this.getAvisoTemplate(aviso));
            });
        },

        getAvisoTemplate: function(aviso) {
            const claseNoLeido = aviso.leido ? '' : 'no-leido';
            const badgeNuevo = aviso.leido ? '' : '<span class="aviso-badge aviso-badge-nuevo">Nuevo</span>';

            return `
                <article class="aviso-card prioridad-${aviso.prioridad} ${claseNoLeido}" data-aviso-id="${aviso.id}">
                    <div class="aviso-card-header">
                        <div>
                            <h3 class="aviso-titulo">
                                <a href="?aviso=${aviso.id}">${this.escapeHtml(aviso.titulo)}</a>
                            </h3>
                        </div>
                        <div class="aviso-badges">
                            ${badgeNuevo}
                            <span class="aviso-badge aviso-badge-${aviso.prioridad}">${this.capitalizarPrioridad(aviso.prioridad)}</span>
                            <span class="aviso-badge aviso-badge-categoria">${this.escapeHtml(aviso.categoria_nombre)}</span>
                        </div>
                    </div>
                    <div class="aviso-card-body">
                        <p class="aviso-extracto">${this.escapeHtml(aviso.extracto)}</p>
                    </div>
                    <div class="aviso-card-footer">
                        <div class="aviso-meta">
                            <span class="aviso-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                ${aviso.fecha}
                            </span>
                            ${aviso.zona ? `<span class="aviso-meta-item">${this.escapeHtml(aviso.zona)}</span>` : ''}
                        </div>
                        <a href="?aviso=${aviso.id}" class="avisos-btn avisos-btn-secondary">Ver mas</a>
                    </div>
                </article>
            `;
        },

        getEmptyTemplate: function() {
            return `
                <div class="avisos-empty">
                    <div class="avisos-empty-icon">📢</div>
                    <p>No hay avisos que coincidan con los filtros seleccionados</p>
                </div>
            `;
        },

        marcarLeido: function(avisoId) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_marcar_leido',
                    nonce: this.config.nonce,
                    aviso_id: avisoId
                },
                success: function(response) {
                    if (response.success) {
                        $(`.aviso-card[data-aviso-id="${avisoId}"]`)
                            .removeClass('no-leido')
                            .find('.aviso-badge-nuevo').fadeOut();
                    }
                }
            });
        },

        confirmarLectura: function(avisoId, boton) {
            const self = this;
            const textoOriginal = boton.text();

            boton.prop('disabled', true).text('Confirmando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_confirmar_lectura',
                    nonce: this.config.nonce,
                    aviso_id: avisoId
                },
                success: function(response) {
                    if (response.success) {
                        boton.closest('.aviso-confirmacion').html(`
                            <div class="aviso-confirmado">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Lectura confirmada
                            </div>
                        `);
                    } else {
                        self.mostrarError(response.data.message);
                        boton.prop('disabled', false).text(textoOriginal);
                    }
                },
                error: function() {
                    self.mostrarError('Error al confirmar lectura');
                    boton.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        procesarSuscripcion: function(formulario) {
            const self = this;
            const botonSubmit = formulario.find('button[type="submit"]');
            const textoOriginal = botonSubmit.text();

            const categoriasSeleccionadas = [];
            formulario.find('input[name="categorias[]"]:checked').each(function() {
                categoriasSeleccionadas.push($(this).val());
            });

            const zonasSeleccionadas = [];
            formulario.find('input[name="zonas[]"]:checked').each(function() {
                zonasSeleccionadas.push($(this).val());
            });

            botonSubmit.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_suscribir',
                    nonce: this.config.nonce,
                    email: formulario.find('input[name="email"]').val(),
                    nombre: formulario.find('input[name="nombre"]').val(),
                    categorias: categoriasSeleccionadas,
                    zonas: zonasSeleccionadas,
                    notificaciones_push: formulario.find('input[name="push"]').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        self.mostrarExito('Te has suscrito correctamente. Recibiras los avisos en tu email.');
                        formulario[0].reset();

                        if (response.data.solicitar_push) {
                            self.mostrarPromptPush();
                        }
                    } else {
                        self.mostrarError(response.data.message);
                    }
                },
                error: function() {
                    self.mostrarError('Error al procesar la suscripcion');
                },
                complete: function() {
                    botonSubmit.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        initPushNotifications: function() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }

            if (Notification.permission === 'default' && this.debePromptearPush()) {
                setTimeout(() => this.mostrarPromptPush(), 5000);
            }
        },

        debePromptearPush: function() {
            const ultimoPrompt = localStorage.getItem('avisos_push_prompt');
            if (!ultimoPrompt) return true;

            const diasDesdeUltimo = (Date.now() - parseInt(ultimoPrompt)) / (1000 * 60 * 60 * 24);
            return diasDesdeUltimo > 7;
        },

        mostrarPromptPush: function() {
            if ($('.avisos-push-prompt').length) return;

            const promptHtml = `
                <div class="avisos-push-prompt">
                    <button class="avisos-push-prompt-close" aria-label="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div class="avisos-push-prompt-header">
                        <div class="avisos-push-prompt-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="24" height="24">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div class="avisos-push-prompt-titulo">Avisos importantes</div>
                    </div>
                    <p class="avisos-push-prompt-texto">
                        Activa las notificaciones para recibir avisos urgentes del ayuntamiento directamente en tu dispositivo.
                    </p>
                    <div class="avisos-push-prompt-actions">
                        <button class="avisos-btn avisos-btn-primary avisos-push-aceptar">Activar</button>
                        <button class="avisos-btn avisos-btn-secondary avisos-push-rechazar">Ahora no</button>
                    </div>
                </div>
            `;

            $('body').append(promptHtml);
        },

        cerrarPromptPush: function() {
            $('.avisos-push-prompt').fadeOut(300, function() {
                $(this).remove();
            });
            localStorage.setItem('avisos_push_prompt', Date.now().toString());
        },

        solicitarPermisosPush: function() {
            const self = this;

            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    self.registrarServiceWorker();
                }
                self.cerrarPromptPush();
            });
        },

        registrarServiceWorker: function() {
            const self = this;

            navigator.serviceWorker.register('/sw-avisos.js')
                .then(registration => {
                    return registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: self.urlBase64ToUint8Array(self.config.pushVapidKey)
                    });
                })
                .then(subscription => {
                    return self.enviarSuscripcionAlServidor(subscription);
                })
                .catch(error => {
                    console.error('Error registrando push:', error);
                });
        },

        enviarSuscripcionAlServidor: function(subscription) {
            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_avisos_registrar_push',
                    nonce: this.config.nonce,
                    subscription: JSON.stringify(subscription)
                }
            });
        },

        urlBase64ToUint8Array: function(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },

        marcarAvisosVistos: function() {
            const avisosVisibles = [];

            $(this.selectors.avisoCard).each(function() {
                const avisoId = $(this).data('aviso-id');
                if (avisoId) {
                    avisosVisibles.push(avisoId);
                }
            });

            if (avisosVisibles.length > 0) {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flavor_avisos_registrar_visualizacion',
                        nonce: this.config.nonce,
                        avisos: avisosVisibles
                    }
                });
            }
        },

        initInfiniteScroll: function() {
            const self = this;
            let scrollTimeout;

            $(window).on('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    if (self.state.cargando || !self.state.hayMas) return;

                    const scrollPos = $(window).scrollTop() + $(window).height();
                    const docHeight = $(document).height();

                    if (scrollPos >= docHeight - 300) {
                        self.cargarMasAvisos();
                    }
                }, 100);
            });
        },

        actualizarBotonCargarMas: function() {
            const boton = $(this.selectors.btnCargarMas);
            if (this.state.hayMas) {
                boton.show();
            } else {
                boton.hide();
            }
        },

        mostrarLoading: function() {
            const contenedor = $(this.selectors.container);
            if (!contenedor.find('.avisos-loading').length) {
                contenedor.append(`
                    <div class="avisos-loading">
                        <div class="avisos-spinner"></div>
                        <span>Cargando avisos...</span>
                    </div>
                `);
            }
        },

        ocultarLoading: function() {
            $('.avisos-loading').remove();
        },

        mostrarError: function(mensaje) {
            this.mostrarMensaje(mensaje, 'error');
        },

        mostrarExito: function(mensaje) {
            this.mostrarMensaje(mensaje, 'success');
        },

        mostrarMensaje: function(mensaje, tipo) {
            const claseAlerta = tipo === 'error' ? 'avisos-error' : 'avisos-success';
            const alerta = $(`<div class="${claseAlerta}">${this.escapeHtml(mensaje)}</div>`);

            $(this.selectors.container).prepend(alerta);

            setTimeout(function() {
                alerta.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        escapeHtml: function(texto) {
            if (!texto) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return texto.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        capitalizarPrioridad: function(prioridad) {
            const mapa = {
                'urgente': 'Urgente',
                'alta': 'Alta',
                'media': 'Media',
                'baja': 'Baja'
            };
            return mapa[prioridad] || prioridad;
        }
    };

    $(document).ready(function() {
        AvisosMunicipales.init();
    });

    window.AvisosMunicipales = AvisosMunicipales;

})(jQuery);
