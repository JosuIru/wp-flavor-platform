/**
 * Setup Wizard JavaScript
 * Maneja la navegación, validación y AJAX del wizard de configuración
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Controlador principal del Wizard
     */
    const FlavorSetupWizard = {
        // Estado actual
        pasoActual: 0,
        totalPasos: 0,
        datosFormulario: {},
        guardandoPaso: false,
        importandoDemo: false,

        // Elementos DOM
        elementos: {},

        /**
         * Inicializar wizard
         */
        init: function() {
            this.cachearElementos();
            this.inicializarEstado();
            this.vincularEventos();
            this.inicializarColorPickers();
            this.actualizarContadorModulos();
            this.actualizarListaDemo();
            this.actualizarResumen();
            this.manejarModalContinuar();
        },

        /**
         * Cachear elementos DOM
         */
        cachearElementos: function() {
            this.elementos = {
                wizard: $('#flavor-wizard'),
                paneles: $('.flavor-wizard__panel'),
                pasos: $('.flavor-wizard__step'),
                barraProgreso: $('.flavor-wizard__progress-fill'),
                btnAnterior: $('#wizard-prev-btn'),
                btnSiguiente: $('#wizard-next-btn'),
                btnCompletar: $('#wizard-complete-btn'),
                btnSaltar: $('#wizard-skip-btn'),
                btnImportarDemo: $('#import-demo-btn'),
                indicadorPaso: $('#current-step-num'),
                loader: $('#wizard-loader'),
                loaderTexto: $('#loader-text')
            };
        },

        /**
         * Inicializar estado desde datos del servidor
         */
        inicializarEstado: function() {
            this.totalPasos = flavorWizard.pasos.length;
            this.pasoActual = flavorWizard.pasos.indexOf(flavorWizard.paso_actual);

            if (this.pasoActual === -1) {
                this.pasoActual = 0;
            }

            // Cargar datos guardados
            this.datosFormulario = Object.assign({}, flavorWizard.datos_guardados);
        },

        /**
         * Vincular eventos
         */
        vincularEventos: function() {
            const self = this;

            // Navegación principal
            this.elementos.btnSiguiente.on('click', function() {
                self.siguientePaso();
            });

            this.elementos.btnAnterior.on('click', function() {
                self.pasoAnterior();
            });

            this.elementos.btnCompletar.on('click', function() {
                self.completarWizard();
            });

            this.elementos.btnSaltar.on('click', function() {
                self.saltarWizard();
            });

            // Perfil (Paso 1)
            $('input[name="perfil"]').on('change', function() {
                self.seleccionarPerfil($(this).val());
            });

            // Info básica (Paso 2)
            $('#nombre_sitio').on('input', function() {
                self.actualizarPreviewNombre($(this).val());
            });

            $('#upload-logo-btn').on('click', function() {
                self.abrirMediaUploader();
            });

            $('#remove-logo-btn').on('click', function() {
                self.eliminarLogo();
            });

            // Módulos (Paso 3)
            $('input[name="modulos_activos[]"]').on('change', function() {
                const tarjeta = $(this).closest('.flavor-wizard__module-card');
                tarjeta.toggleClass('flavor-wizard__module-card--active', $(this).is(':checked'));
                self.actualizarContadorModulos();
            });

            $('.flavor-wizard__filter-btn').on('click', function() {
                self.filtrarModulos($(this).data('category'));
                $('.flavor-wizard__filter-btn').removeClass('flavor-wizard__filter-btn--active');
                $(this).addClass('flavor-wizard__filter-btn--active');
            });

            // Temas (Paso 4)
            $('input[name="tema_visual"]').on('change', function() {
                self.seleccionarTema($(this).val());
            });

            // Demo data (Paso 5)
            this.elementos.btnImportarDemo.on('click', function() {
                self.importarDemoData();
            });

            // Editar desde resumen
            $('.flavor-wizard__summary-edit').on('click', function() {
                const destino = $(this).data('goto');
                self.irAPaso(destino);
            });

            // Validación en tiempo real
            $('.flavor-wizard__input[required]').on('blur', function() {
                self.validarCampo($(this));
            });
        },

        /**
         * Inicializar color pickers
         */
        inicializarColorPickers: function() {
            const self = this;

            if ($.fn.wpColorPicker) {
                $('#color_primario').wpColorPicker({
                    change: function(event, ui) {
                        self.actualizarPreviewColor('primario', ui.color.toString());
                    }
                });

                $('#color_secundario').wpColorPicker({
                    change: function(event, ui) {
                        self.actualizarPreviewColor('secundario', ui.color.toString());
                    }
                });
            }
        },

        /**
         * Ir al siguiente paso
         */
        siguientePaso: function() {
            if (this.guardandoPaso) return;

            // Validar paso actual
            if (!this.validarPasoActual()) {
                return;
            }

            // Guardar paso actual
            this.guardarPasoActual().then(() => {
                if (this.pasoActual < this.totalPasos - 1) {
                    this.pasoActual++;
                    this.mostrarPaso(this.pasoActual);
                }
            });
        },

        /**
         * Ir al paso anterior
         */
        pasoAnterior: function() {
            if (this.pasoActual > 0) {
                this.pasoActual--;
                this.mostrarPaso(this.pasoActual);
            }
        },

        /**
         * Ir a un paso específico
         */
        irAPaso: function(clavePaso) {
            const indice = flavorWizard.pasos.indexOf(clavePaso);
            if (indice !== -1) {
                this.pasoActual = indice;
                this.mostrarPaso(this.pasoActual);
            }
        },

        /**
         * Mostrar un paso específico
         */
        mostrarPaso: function(indice) {
            const clavePaso = flavorWizard.pasos[indice];

            // Ocultar todos los paneles
            this.elementos.paneles.removeClass('flavor-wizard__panel--active');

            // Mostrar panel actual
            $(`.flavor-wizard__panel[data-step="${clavePaso}"]`).addClass('flavor-wizard__panel--active');

            // Actualizar indicadores de pasos
            this.elementos.pasos.each(function() {
                const pasoIndice = parseInt($(this).data('index'));
                $(this).removeClass('flavor-wizard__step--active flavor-wizard__step--completed');

                if (pasoIndice < indice) {
                    $(this).addClass('flavor-wizard__step--completed');
                } else if (pasoIndice === indice) {
                    $(this).addClass('flavor-wizard__step--active');
                }
            });

            // Actualizar barra de progreso
            const porcentaje = (indice / (this.totalPasos - 1)) * 100;
            this.elementos.barraProgreso.css('width', porcentaje + '%');

            // Actualizar indicador de paso
            this.elementos.indicadorPaso.text(indice + 1);

            // Mostrar/ocultar botón anterior
            this.elementos.btnAnterior.css('visibility', indice === 0 ? 'hidden' : 'visible');

            // Cambiar botón siguiente/completar
            if (indice === this.totalPasos - 1) {
                this.elementos.btnSiguiente.hide();
                this.elementos.btnCompletar.show();
            } else {
                this.elementos.btnSiguiente.show();
                this.elementos.btnCompletar.hide();
            }

            // Acciones específicas por paso
            if (clavePaso === 'resumen') {
                this.actualizarResumen();
            }

            // Actualizar URL sin recargar
            const url = new URL(window.location);
            url.searchParams.set('step', clavePaso);
            window.history.pushState({}, '', url);

            // Scroll al inicio
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * Validar el paso actual
         */
        validarPasoActual: function() {
            const clavePaso = flavorWizard.pasos[this.pasoActual];
            let esValido = true;

            switch (clavePaso) {
                case 'bienvenida':
                    const perfilSeleccionado = $('input[name="perfil"]:checked').val();
                    if (!perfilSeleccionado) {
                        this.mostrarError('Selecciona un tipo de organización');
                        esValido = false;
                    }
                    break;

                case 'info_basica':
                    const nombreSitio = $('#nombre_sitio').val().trim();
                    if (!nombreSitio) {
                        this.validarCampo($('#nombre_sitio'));
                        esValido = false;
                    }
                    break;

                case 'notificaciones':
                    const emailRemitente = $('#email_remitente').val().trim();
                    if (emailRemitente && !this.validarEmail(emailRemitente)) {
                        this.mostrarError(flavorWizard.strings.email_invalido);
                        esValido = false;
                    }
                    break;
            }

            return esValido;
        },

        /**
         * Validar un campo específico
         */
        validarCampo: function($campo) {
            const valor = $campo.val().trim();
            const esRequerido = $campo.prop('required');
            const $error = $campo.siblings('.flavor-wizard__input-error');

            if (esRequerido && !valor) {
                $campo.addClass('flavor-wizard__input--error');
                $error.text(flavorWizard.strings.campo_requerido);
                return false;
            }

            $campo.removeClass('flavor-wizard__input--error');
            $error.text('');
            return true;
        },

        /**
         * Validar email
         */
        validarEmail: function(email) {
            const patronEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return patronEmail.test(email);
        },

        /**
         * Guardar el paso actual via AJAX
         */
        guardarPasoActual: function() {
            const self = this;
            const clavePaso = flavorWizard.pasos[this.pasoActual];

            this.guardandoPaso = true;
            this.mostrarLoader(flavorWizard.strings.guardando);

            // Recopilar datos del paso
            const datos = this.recopilarDatosPaso(clavePaso);

            return $.ajax({
                url: flavorWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_wizard_save_step',
                    nonce: flavorWizard.nonce,
                    step: clavePaso,
                    data: datos
                }
            }).done(function(respuesta) {
                if (respuesta.success) {
                    // Actualizar datos locales
                    Object.assign(self.datosFormulario, datos);
                }
            }).fail(function() {
                self.mostrarError(flavorWizard.strings.error);
            }).always(function() {
                self.guardandoPaso = false;
                self.ocultarLoader();
            });
        },

        /**
         * Recopilar datos del paso actual
         */
        recopilarDatosPaso: function(clavePaso) {
            const datos = {};

            switch (clavePaso) {
                case 'bienvenida':
                    datos.perfil = $('input[name="perfil"]:checked').val();
                    break;

                case 'info_basica':
                    datos.nombre_sitio = $('#nombre_sitio').val();
                    datos.logo_url = $('#logo_url').val();
                    datos.color_primario = $('#color_primario').val();
                    datos.color_secundario = $('#color_secundario').val();
                    break;

                case 'modulos':
                    datos.modulos_activos = [];
                    $('input[name="modulos_activos[]"]:checked').each(function() {
                        datos.modulos_activos.push($(this).val());
                    });
                    break;

                case 'diseno':
                    datos.tema_visual = $('input[name="tema_visual"]:checked').val();
                    break;

                case 'demo_data':
                    datos.importar_demo = $('#importar_demo').val();
                    break;

                case 'notificaciones':
                    datos.notificaciones_email = $('#notificaciones_email').is(':checked') ? 'true' : 'false';
                    datos.notificaciones_push = $('#notificaciones_push').is(':checked') ? 'true' : 'false';
                    datos.email_remitente = $('#email_remitente').val();
                    break;
            }

            return datos;
        },

        /**
         * Seleccionar perfil
         */
        seleccionarPerfil: function(perfilId) {
            // Actualizar UI
            $('.flavor-wizard__profile-card').removeClass('flavor-wizard__profile-card--selected');
            $(`input[name="perfil"][value="${perfilId}"]`)
                .closest('.flavor-wizard__profile-card')
                .addClass('flavor-wizard__profile-card--selected');

            // Guardar en estado
            this.datosFormulario.perfil = perfilId;

            // Actualizar lista de demo data
            this.actualizarListaDemo();
        },

        /**
         * Seleccionar tema visual
         */
        seleccionarTema: function(temaId) {
            // Actualizar UI
            $('.flavor-wizard__theme-card').removeClass('flavor-wizard__theme-card--selected');
            $(`input[name="tema_visual"][value="${temaId}"]`)
                .closest('.flavor-wizard__theme-card')
                .addClass('flavor-wizard__theme-card--selected');

            // Actualizar preview de landing
            this.actualizarPreviewLanding(temaId);

            // Guardar en estado
            this.datosFormulario.tema_visual = temaId;
        },

        /**
         * Actualizar preview de landing según tema
         */
        actualizarPreviewLanding: function(temaId) {
            const tema = flavorWizard.temas[temaId];
            if (!tema) return;

            const $preview = $('#landing-preview');
            $preview.css({
                '--wizard-primary': tema.color_primario,
                '--wizard-secondary': tema.color_secundario,
                'background-color': tema.color_fondo
            });

            // Actualizar elementos del preview
            $preview.find('.flavor-wizard__landing-logo').css('background', tema.color_primario);
            $preview.find('.flavor-wizard__landing-cta').css('background', tema.color_primario);
        },

        /**
         * Abrir media uploader para logo
         */
        abrirMediaUploader: function() {
            const self = this;

            // Si ya existe, abrirlo
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }

            // Crear nuevo frame
            this.mediaFrame = wp.media({
                title: flavorWizard.strings.seleccionar_logo,
                button: {
                    text: flavorWizard.strings.usar_imagen
                },
                multiple: false
            });

            this.mediaFrame.on('select', function() {
                const adjunto = self.mediaFrame.state().get('selection').first().toJSON();
                self.establecerLogo(adjunto.url);
            });

            this.mediaFrame.open();
        },

        /**
         * Establecer logo
         */
        establecerLogo: function(url) {
            $('#logo_url').val(url);
            $('#logo-preview').html(`<img src="${url}" alt="Logo">`);
            $('#remove-logo-btn').show();
            this.actualizarPreviewLogo(url);
        },

        /**
         * Eliminar logo
         */
        eliminarLogo: function() {
            $('#logo_url').val('');
            $('#logo-preview').html(`
                <span class="dashicons dashicons-format-image"></span>
                <span class="flavor-wizard__logo-placeholder-text">Sin logo</span>
            `);
            $('#remove-logo-btn').hide();
            this.actualizarPreviewLogo(null);
        },

        /**
         * Actualizar preview del logo
         */
        actualizarPreviewLogo: function(url) {
            const $previewLogo = $('#preview-logo');
            if (url) {
                $previewLogo.html(`<img src="${url}" alt="Logo preview">`);
            } else {
                $previewLogo.html('<span class="dashicons dashicons-admin-site"></span>');
            }
        },

        /**
         * Actualizar preview del nombre
         */
        actualizarPreviewNombre: function(nombre) {
            $('#preview-name').text(nombre || 'Mi Sitio');
        },

        /**
         * Actualizar preview de colores
         */
        actualizarPreviewColor: function(tipo, color) {
            if (tipo === 'primario') {
                $('#preview-header').css('--wizard-primary', color);
                $('#preview-btn-primary').css('background', color);
                $('#preview-logo').css('background', color);
            } else {
                $('#preview-btn-secondary').css({
                    'border-color': color,
                    'color': color
                });
            }
        },

        /**
         * Filtrar módulos por categoría
         */
        filtrarModulos: function(categoria) {
            const $tarjetas = $('.flavor-wizard__module-card');

            if (categoria === 'all') {
                $tarjetas.removeClass('flavor-wizard__module-card--hidden');
            } else {
                $tarjetas.each(function() {
                    const categoriaModulo = $(this).data('category');
                    $(this).toggleClass('flavor-wizard__module-card--hidden', categoriaModulo !== categoria);
                });
            }
        },

        /**
         * Actualizar contador de módulos
         */
        actualizarContadorModulos: function() {
            const cantidad = $('input[name="modulos_activos[]"]:checked').length;
            $('#modules-count').text(cantidad);
        },

        /**
         * Actualizar lista de contenido demo según perfil
         */
        actualizarListaDemo: function() {
            const perfil = this.datosFormulario.perfil || $('input[name="perfil"]:checked').val();
            const $lista = $('#demo-content-list');

            let items = [];

            switch (perfil) {
                case 'grupo_consumo':
                    items = [
                        '3 productores locales con información completa',
                        '10 productos de ejemplo con precios',
                        '1 ciclo de pedidos configurado',
                        '1 grupo de recogida con horarios'
                    ];
                    break;

                case 'comunidad':
                    items = [
                        '5 eventos programados',
                        '3 talleres con plazas disponibles',
                        '10 socios ficticios con datos completos'
                    ];
                    break;

                case 'banco_tiempo':
                    items = [
                        '5 servicios de ejemplo en diferentes categorías',
                        '5 miembros con habilidades diversas',
                        'Saldo inicial de horas configurado'
                    ];
                    break;

                case 'coworking':
                    items = [
                        '4 espacios de trabajo configurados',
                        'Salas de reuniones con capacidad y precios',
                        'Zona de coworking abierta'
                    ];
                    break;

                case 'barrio':
                    items = [
                        '5 eventos comunitarios',
                        '3 talleres vecinales',
                        '10 vecinos registrados',
                        '3 incidencias de ejemplo'
                    ];
                    break;

                default:
                    items = [
                        'Página "Sobre Nosotros"',
                        'Página de "Contacto"',
                        'Contenido básico de inicio'
                    ];
            }

            $lista.html(items.map(item => `<li>${item}</li>`).join(''));
        },

        /**
         * Importar datos demo
         */
        importarDemoData: function() {
            if (this.importandoDemo) return;

            const self = this;
            const perfil = this.datosFormulario.perfil || $('input[name="perfil"]:checked').val();

            this.importandoDemo = true;

            // Mostrar progreso
            $('#import-progress').show();
            $('#import-result').hide();
            this.elementos.btnImportarDemo.prop('disabled', true);

            // Animar barra de progreso
            let progreso = 0;
            const intervalo = setInterval(function() {
                progreso += Math.random() * 15;
                if (progreso > 90) progreso = 90;
                $('#import-progress-fill').css('width', progreso + '%');
            }, 300);

            $.ajax({
                url: flavorWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_wizard_import_demo',
                    nonce: flavorWizard.nonce,
                    perfil: perfil
                }
            }).done(function(respuesta) {
                clearInterval(intervalo);
                $('#import-progress-fill').css('width', '100%');

                if (respuesta.success) {
                    setTimeout(function() {
                        $('#import-progress').hide();
                        $('#import-result').show();
                        $('#import-result-text').text(respuesta.data.message);
                        $('#importar_demo').val('true');
                    }, 500);
                } else {
                    self.mostrarError(respuesta.data?.message || flavorWizard.strings.error_importar);
                }
            }).fail(function() {
                clearInterval(intervalo);
                self.mostrarError(flavorWizard.strings.error_importar);
            }).always(function() {
                self.importandoDemo = false;
                self.elementos.btnImportarDemo.prop('disabled', false);
            });
        },

        /**
         * Actualizar resumen
         */
        actualizarResumen: function() {
            // Perfil
            const perfilId = this.datosFormulario.perfil || $('input[name="perfil"]:checked').val();
            const $perfilCard = $(`input[name="perfil"][value="${perfilId}"]`).closest('.flavor-wizard__profile-card');
            const nombrePerfil = $perfilCard.find('.flavor-wizard__profile-name').text() || '-';
            $('#summary-perfil').text(nombrePerfil);

            // Info básica
            const nombreSitio = $('#nombre_sitio').val() || this.datosFormulario.nombre_sitio || '-';
            const tienelogo = $('#logo_url').val() ? ' (con logo)' : '';
            $('#summary-info').text(nombreSitio + tienelogo);

            // Módulos
            const modulosActivos = $('input[name="modulos_activos[]"]:checked').length;
            $('#summary-modulos').text(modulosActivos + ' módulos seleccionados');

            // Tema
            const temaId = $('input[name="tema_visual"]:checked').val() || this.datosFormulario.tema_visual;
            const tema = flavorWizard.temas[temaId];
            $('#summary-tema').text(tema ? tema.nombre : '-');

            // Demo data
            const demoImportado = $('#importar_demo').val() === 'true';
            $('#summary-demo').text(demoImportado ? 'Datos importados' : 'Sin datos de ejemplo');

            // Notificaciones
            const tiposNotif = [];
            if ($('#notificaciones_email').is(':checked')) tiposNotif.push('Email');
            if ($('#notificaciones_push').is(':checked')) tiposNotif.push('Push');
            $('#summary-notificaciones').text(tiposNotif.length ? tiposNotif.join(', ') : 'Ninguna');
        },

        /**
         * Completar wizard
         */
        completarWizard: function() {
            const self = this;

            this.mostrarLoader('Finalizando configuración...');

            $.ajax({
                url: flavorWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_wizard_complete',
                    nonce: flavorWizard.nonce
                }
            }).done(function(respuesta) {
                if (respuesta.success) {
                    window.location.href = respuesta.data.redirect_url;
                } else {
                    self.ocultarLoader();
                    self.mostrarError(respuesta.data?.message || 'Error al completar');
                }
            }).fail(function() {
                self.ocultarLoader();
                self.mostrarError('Error de conexión');
            });
        },

        /**
         * Saltar wizard
         */
        saltarWizard: function() {
            if (!confirm(flavorWizard.strings.confirmar_saltar)) {
                return;
            }

            const self = this;
            this.mostrarLoader('Saltando configuración...');

            $.ajax({
                url: flavorWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_wizard_skip',
                    nonce: flavorWizard.nonce
                }
            }).done(function(respuesta) {
                if (respuesta.success) {
                    window.location.href = respuesta.data.redirect_url;
                } else {
                    self.ocultarLoader();
                }
            }).fail(function() {
                self.ocultarLoader();
            });
        },

        /**
         * Manejar modal de continuar progreso
         */
        manejarModalContinuar: function() {
            const $modal = $('#continue-modal');
            if (!$modal.length) return;

            const self = this;

            $('#continue-btn').on('click', function() {
                $modal.remove();
                // Ir al siguiente paso del guardado
                const pasoGuardado = flavorWizard.datos_guardados.paso_completado;
                const indicePasoGuardado = flavorWizard.pasos.indexOf(pasoGuardado);
                if (indicePasoGuardado !== -1 && indicePasoGuardado < self.totalPasos - 1) {
                    self.pasoActual = indicePasoGuardado + 1;
                    self.mostrarPaso(self.pasoActual);
                }
            });

            $('#start-fresh-btn').on('click', function() {
                $modal.remove();
                // Limpiar datos y empezar de nuevo
                self.datosFormulario = {};
            });
        },

        /**
         * Mostrar loader
         */
        mostrarLoader: function(texto) {
            this.elementos.loaderTexto.text(texto || flavorWizard.strings.guardando);
            this.elementos.loader.show();
        },

        /**
         * Ocultar loader
         */
        ocultarLoader: function() {
            this.elementos.loader.hide();
        },

        /**
         * Mostrar mensaje de error
         */
        mostrarError: function(mensaje) {
            // Crear toast de error si no existe
            let $toast = $('#wizard-toast');
            if (!$toast.length) {
                $toast = $(`
                    <div id="wizard-toast" style="
                        position: fixed;
                        bottom: 100px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: #ef4444;
                        color: white;
                        padding: 12px 24px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 1002;
                        font-size: 14px;
                        display: none;
                    "></div>
                `);
                $('body').append($toast);
            }

            $toast.text(mensaje).fadeIn(200);
            setTimeout(function() {
                $toast.fadeOut(200);
            }, 4000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Solo inicializar si estamos en el wizard
        if ($('#flavor-wizard').length) {
            FlavorSetupWizard.init();
        }
    });

})(jQuery);
