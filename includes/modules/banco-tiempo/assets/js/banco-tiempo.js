/**
 * Banco de Tiempo - JavaScript
 * Sistema de intercambio de servicios por horas
 */

(function($) {
    'use strict';

    // Namespace global
    window.BancoTiempo = window.BancoTiempo || {};

    const bancoTiempoConfig = typeof bancoTiempoData !== 'undefined'
        ? bancoTiempoData
        : (typeof flavorBancoTiempo !== 'undefined' ? flavorBancoTiempo : {});

    /**
     * Configuracion global
     */
    BancoTiempo.config = {
        ajaxUrl: bancoTiempoConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: bancoTiempoConfig.nonce || '',
        strings: bancoTiempoConfig.strings || bancoTiempoConfig.i18n || {}
    };

    BancoTiempo.t = function(clave, fallback) {
        return BancoTiempo.config.strings[clave] || fallback || '';
    };

    /**
     * Utilidades generales
     */
    BancoTiempo.Utils = {
        /**
         * Muestra notificacion toast
         */
        showToast: function(mensaje, tipo) {
            tipo = tipo || 'info';
            var toastContainer = $('#bt-toast-container');

            if (!toastContainer.length) {
                toastContainer = $('<div id="bt-toast-container" style="position:fixed;top:20px;right:20px;z-index:100000;"></div>');
                $('body').append(toastContainer);
            }

            var iconos = {
                success: 'dashicons-yes-alt',
                error: 'dashicons-dismiss',
                warning: 'dashicons-warning',
                info: 'dashicons-info'
            };

            var toast = $('<div class="bt-alert bt-alert-' + tipo + '" style="margin-bottom:10px;min-width:280px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">' +
                '<span class="dashicons ' + iconos[tipo] + ' bt-alert-icon"></span>' +
                '<div class="bt-alert-content">' + mensaje + '</div>' +
                '</div>');

            toastContainer.append(toast);

            setTimeout(function() {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        },

        /**
         * Formatea horas para mostrar
         */
        formatHoras: function(horas) {
            horas = parseFloat(horas);
            if (horas === 1) {
                return '1 hora';
            }
            return horas.toFixed(1).replace('.0', '') + ' horas';
        },

        /**
         * Formatea fecha relativa
         */
        formatFechaRelativa: function(fecha) {
            var ahora = new Date();
            var fechaObj = new Date(fecha);
            var diferencia = ahora - fechaObj;
            var segundos = Math.floor(diferencia / 1000);
            var minutos = Math.floor(segundos / 60);
            var horasTranscurridas = Math.floor(minutos / 60);
            var dias = Math.floor(horasTranscurridas / 24);

            if (dias > 7) {
                return fechaObj.toLocaleDateString('es-ES');
            } else if (dias > 0) {
                return 'hace ' + dias + (dias === 1 ? ' dia' : ' dias');
            } else if (horasTranscurridas > 0) {
                return 'hace ' + horasTranscurridas + (horasTranscurridas === 1 ? ' hora' : ' horas');
            } else if (minutos > 0) {
                return 'hace ' + minutos + (minutos === 1 ? ' minuto' : ' minutos');
            }
            return 'ahora mismo';
        },

        /**
         * Hace peticion AJAX
         */
        ajax: function(accion, datos, callback) {
            datos = datos || {};
            datos.action = 'banco_tiempo_' + accion;
            datos.nonce = BancoTiempo.config.nonce;

            return $.ajax({
                url: BancoTiempo.config.ajaxUrl,
                type: 'POST',
                data: datos,
                success: function(response) {
                    if (callback) {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    BancoTiempo.Utils.showToast(BancoTiempo.t('error_conexion', 'Error de conexión') + ': ' + error, 'error');
                }
            });
        }
    };

    /**
     * Modulo de Servicios
     */
    BancoTiempo.Servicios = {
        /**
         * Inicializa el modulo
         */
        init: function() {
            this.bindEvents();
            this.initFiltros();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            var self = this;

            // Busqueda con debounce
            var debounceTimer;
            $(document).on('input', '.bt-busqueda-input', function() {
                clearTimeout(debounceTimer);
                var valor = $(this).val();
                debounceTimer = setTimeout(function() {
                    self.buscar(valor);
                }, 300);
            });

            // Filtro por categoria
            $(document).on('change', '.bt-filtro-categoria', function() {
                self.filtrarPorCategoria($(this).val());
            });

            // Filtro por tipo
            $(document).on('change', '.bt-filtro-tipo', function() {
                self.filtrarPorTipo($(this).val());
            });

            // Click en categoria card
            $(document).on('click', '.bt-categoria-card', function(e) {
                e.preventDefault();
                var categoria = $(this).data('categoria');
                $('.bt-categoria-card').removeClass('active');
                $(this).addClass('active');
                self.filtrarPorCategoria(categoria);
            });

            // Solicitar servicio
            $(document).on('click', '.bt-btn-solicitar', function() {
                var servicioId = $(this).data('servicio-id');
                self.abrirModalSolicitar(servicioId);
            });

            // Ver detalle servicio
            $(document).on('click', '.bt-servicio-card', function(e) {
                if (!$(e.target).closest('.bt-btn').length) {
                    var servicioId = $(this).data('servicio-id');
                    self.verDetalle(servicioId);
                }
            });
        },

        /**
         * Inicializa filtros
         */
        initFiltros: function() {
            this.filtrosActuales = {
                busqueda: '',
                categoria: '',
                tipo: ''
            };
        },

        /**
         * Busca servicios
         */
        buscar: function(termino) {
            this.filtrosActuales.busqueda = termino;
            this.cargarServicios();
        },

        /**
         * Filtra por categoria
         */
        filtrarPorCategoria: function(categoria) {
            this.filtrosActuales.categoria = categoria;
            this.cargarServicios();
        },

        /**
         * Filtra por tipo
         */
        filtrarPorTipo: function(tipo) {
            this.filtrosActuales.tipo = tipo;
            this.cargarServicios();
        },

        /**
         * Carga servicios con filtros
         */
        cargarServicios: function() {
            var self = this;
            var contenedor = $('.bt-servicios-grid');

            contenedor.html('<div class="bt-loading"><div class="bt-spinner"></div><span>' + BancoTiempo.t('cargando', 'Cargando...') + '</span></div>');

            BancoTiempo.Utils.ajax('buscar_servicios', this.filtrosActuales, function(response) {
                if (response.success) {
                    self.renderServicios(response.data.servicios);
                } else {
                    contenedor.html('<div class="bt-empty"><div class="bt-empty-icon"><span class="dashicons dashicons-search"></span></div><p class="bt-empty-title">' + BancoTiempo.t('sin_resultados', 'No se encontraron servicios') + '</p></div>');
                }
            });
        },

        /**
         * Renderiza lista de servicios
         */
        renderServicios: function(servicios) {
            var contenedor = $('.bt-servicios-grid');

            if (!servicios || servicios.length === 0) {
                contenedor.html('<div class="bt-empty"><div class="bt-empty-icon"><span class="dashicons dashicons-clock"></span></div><p class="bt-empty-title">' + BancoTiempo.t('sin_servicios', 'No hay servicios disponibles') + '</p><p class="bt-empty-text">' + BancoTiempo.t('ofrecer_servicio', 'Ofrecer servicio') + '</p><button class="bt-btn bt-btn-primary" onclick="BancoTiempo.Formularios.abrirNuevoServicio()">' + BancoTiempo.t('ofrecer_servicio', 'Ofrecer servicio') + '</button></div>');
                return;
            }

            var html = '';
            servicios.forEach(function(servicio) {
                html += BancoTiempo.Servicios.renderServicioCard(servicio);
            });

            contenedor.html(html);
        },

        /**
         * Renderiza tarjeta de servicio
         */
        renderServicioCard: function(servicio) {
            var tipoClase = servicio.tipo === 'oferta' ? 'oferta' : 'demanda';
            var tipoTexto = servicio.tipo === 'oferta' ? 'Ofrezco' : 'Necesito';

            return '<div class="bt-servicio-card" data-servicio-id="' + servicio.id + '">' +
                '<div class="bt-servicio-card-header">' +
                '<span class="bt-servicio-categoria"><span class="dashicons dashicons-category"></span>' + servicio.categoria_nombre + '</span>' +
                '<span class="bt-servicio-tipo ' + tipoClase + '">' + tipoTexto + '</span>' +
                '</div>' +
                '<h3 class="bt-servicio-titulo">' + servicio.titulo + '</h3>' +
                '<p class="bt-servicio-descripcion">' + servicio.descripcion + '</p>' +
                '<div class="bt-servicio-meta">' +
                '<span class="bt-servicio-meta-item"><span class="dashicons dashicons-clock"></span><span class="bt-servicio-horas">' + BancoTiempo.Utils.formatHoras(servicio.horas_estimadas) + '</span></span>' +
                (servicio.ubicacion ? '<span class="bt-servicio-meta-item"><span class="dashicons dashicons-location"></span>' + servicio.ubicacion + '</span>' : '') +
                '</div>' +
                '<div class="bt-servicio-usuario">' +
                '<img src="' + servicio.usuario_avatar + '" alt="" class="bt-servicio-usuario-avatar">' +
                '<div class="bt-servicio-usuario-info">' +
                '<div class="bt-servicio-usuario-nombre">' + servicio.usuario_nombre + '</div>' +
                '<div class="bt-servicio-usuario-rating">' + BancoTiempo.Servicios.renderEstrellas(servicio.usuario_valoracion) + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="bt-servicio-acciones">' +
                '<button class="bt-btn bt-btn-primary bt-btn-solicitar" data-servicio-id="' + servicio.id + '">' + BancoTiempo.t('solicitar_servicio', 'Solicitar servicio') + '</button>' +
                '<button class="bt-btn bt-btn-secondary">' + BancoTiempo.t('ver_mas', 'Ver más') + '</button>' +
                '</div>' +
                '</div>';
        },

        /**
         * Renderiza estrellas de valoracion
         */
        renderEstrellas: function(valoracion) {
            valoracion = parseFloat(valoracion) || 0;
            var html = '';
            for (var i = 1; i <= 5; i++) {
                if (i <= valoracion) {
                    html += '<span class="dashicons dashicons-star-filled"></span>';
                } else if (i - 0.5 <= valoracion) {
                    html += '<span class="dashicons dashicons-star-half"></span>';
                } else {
                    html += '<span class="dashicons dashicons-star-empty"></span>';
                }
            }
            return html + ' <span>(' + valoracion.toFixed(1) + ')</span>';
        },

        /**
         * Abre modal para solicitar servicio
         */
        abrirModalSolicitar: function(servicioId) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('solicitar_servicio', 'Solicitar servicio'),
                contenido: '<form id="bt-form-solicitar">' +
                    '<input type="hidden" name="servicio_id" value="' + servicioId + '">' +
                    '<div class="bt-form-group">' +
                    '<label class="bt-form-label">' + BancoTiempo.t('mensaje_proveedor', 'Mensaje para el proveedor') + '</label>' +
                    '<textarea name="mensaje" class="bt-form-textarea" placeholder="' + BancoTiempo.t('explica_necesidad', 'Explica brevemente por qué necesitas este servicio...') + '" required></textarea>' +
                    '</div>' +
                    '<div class="bt-form-group">' +
                    '<label class="bt-form-label">' + BancoTiempo.t('fecha_preferida', 'Fecha preferida') + '</label>' +
                    '<input type="datetime-local" name="fecha_preferida" class="bt-form-input">' +
                    '</div>' +
                    '</form>',
                botones: [
                    { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('enviar_solicitud', 'Enviar solicitud'), clase: 'bt-btn-primary', callback: function() {
                        BancoTiempo.Servicios.enviarSolicitud();
                    }}
                ]
            });
        },

        /**
         * Envia solicitud de servicio
         */
        enviarSolicitud: function() {
            var form = $('#bt-form-solicitar');
            var datos = {
                servicio_id: form.find('[name="servicio_id"]').val(),
                mensaje: form.find('[name="mensaje"]').val(),
                fecha_preferida: form.find('[name="fecha_preferida"]').val()
            };

            if (!datos.mensaje) {
                BancoTiempo.Utils.showToast(BancoTiempo.t('escribe_mensaje', 'Por favor escribe un mensaje'), 'warning');
                return;
            }

            BancoTiempo.Utils.ajax('solicitar_servicio', datos, function(response) {
                if (response.success) {
                    BancoTiempo.Modal.cerrar();
                    BancoTiempo.Utils.showToast(BancoTiempo.t('solicitud_enviada', 'Solicitud enviada correctamente'), 'success');
                } else {
                    BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_enviar_solicitud', 'Error al enviar solicitud'), 'error');
                }
            });
        },

        /**
         * Ver detalle de servicio
         */
        verDetalle: function(servicioId) {
            BancoTiempo.Utils.ajax('obtener_servicio', { servicio_id: servicioId }, function(response) {
                if (response.success) {
                    var servicio = response.data;
                    BancoTiempo.Modal.abrir({
                        titulo: servicio.titulo,
                        contenido: '<div class="bt-servicio-detalle">' +
                            '<p class="bt-servicio-descripcion">' + servicio.descripcion + '</p>' +
                            '<div class="bt-servicio-meta">' +
                            '<p><strong>' + BancoTiempo.t('categoria', 'Categoría') + ':</strong> ' + servicio.categoria_nombre + '</p>' +
                            '<p><strong>' + BancoTiempo.t('horas_estimadas', 'Horas estimadas') + ':</strong> ' + BancoTiempo.Utils.formatHoras(servicio.horas_estimadas) + '</p>' +
                            '<p><strong>' + BancoTiempo.t('publicado', 'Publicado') + ':</strong> ' + BancoTiempo.Utils.formatFechaRelativa(servicio.fecha_publicacion) + '</p>' +
                            '</div>' +
                            '</div>',
                        botones: [
                            { texto: BancoTiempo.t('cerrar', 'Cerrar'), clase: 'bt-btn-secondary', cerrar: true },
                            { texto: BancoTiempo.t('solicitar_servicio', 'Solicitar servicio'), clase: 'bt-btn-primary', callback: function() {
                                BancoTiempo.Modal.cerrar();
                                BancoTiempo.Servicios.abrirModalSolicitar(servicioId);
                            }}
                        ]
                    });
                }
            });
        }
    };

    /**
     * Modulo de Formularios
     */
    BancoTiempo.Formularios = {
        /**
         * Inicializa el modulo
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bindea eventos de formularios
         */
        bindEvents: function() {
            var self = this;

            // Submit formulario nuevo servicio
            $(document).on('submit', '#bt-form-nuevo-servicio', function(e) {
                e.preventDefault();
                self.guardarServicio($(this));
            });

            // Submit formulario editar servicio
            $(document).on('submit', '#bt-form-editar-servicio', function(e) {
                e.preventDefault();
                self.actualizarServicio($(this));
            });

            // Boton nuevo servicio
            $(document).on('click', '.bt-btn-nuevo-servicio', function() {
                self.abrirNuevoServicio();
            });

            $(document).on('click', '.bt-btn-editar', function() {
                self.abrirEditarServicio($(this).data('servicio-id'));
            });

            $(document).on('click', '.bt-btn-pausar', function() {
                self.cambiarEstadoServicio($(this).data('servicio-id'), 'pausar_servicio', BancoTiempo.t('confirmar_pausar_servicio', '¿Seguro que quieres pausar este servicio?'), BancoTiempo.t('servicio_pausado', 'Servicio pausado'));
            });

            $(document).on('click', '.bt-btn-activar', function() {
                self.cambiarEstadoServicio($(this).data('servicio-id'), 'activar_servicio', BancoTiempo.t('confirmar_activar_servicio', '¿Quieres volver a activar este servicio?'), BancoTiempo.t('servicio_activado', 'Servicio activado'));
            });

            $(document).on('click', '.bt-btn-eliminar', function() {
                self.eliminarServicio($(this).data('servicio-id'));
            });
        },

        /**
         * Abre formulario de nuevo servicio
         */
        abrirNuevoServicio: function() {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('ofrecer_nuevo_servicio', 'Ofrecer nuevo servicio'),
                contenido: this.getFormularioServicio(),
                botones: [
                    { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('publicar_servicio', 'Publicar servicio'), clase: 'bt-btn-success', callback: function() {
                        $('#bt-form-nuevo-servicio').submit();
                    }}
                ]
            });
        },

        /**
         * Obtiene HTML del formulario de servicio
         */
        getFormularioServicio: function(servicio, formId) {
            servicio = servicio || {};
            formId = formId || 'bt-form-nuevo-servicio';
            var categorias = typeof bancoTiempoData !== 'undefined' ? bancoTiempoData.categorias : {};

            var opcionesCategorias = '<option value="">' + BancoTiempo.t('selecciona_categoria', 'Selecciona categoría') + '</option>';
            for (var key in categorias) {
                var selected = servicio.categoria === key ? ' selected' : '';
                opcionesCategorias += '<option value="' + key + '"' + selected + '>' + categorias[key] + '</option>';
            }

            return '<form id="' + formId + '">' +
                (servicio.id ? '<input type="hidden" name="servicio_id" value="' + servicio.id + '">' : '') +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Tipo de servicio <span class="required">*</span></label>' +
                '<select name="tipo" class="bt-form-select" required>' +
                '<option value="oferta"' + (servicio.tipo === 'oferta' ? ' selected' : '') + '>Ofrezco este servicio</option>' +
                '<option value="demanda"' + (servicio.tipo === 'demanda' ? ' selected' : '') + '>Necesito este servicio</option>' +
                '</select>' +
                '</div>' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Titulo <span class="required">*</span></label>' +
                '<input type="text" name="titulo" class="bt-form-input" value="' + (servicio.titulo || '') + '" placeholder="Ej: Clases de ingles, Reparacion de ordenadores..." required>' +
                '</div>' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Descripcion <span class="required">*</span></label>' +
                '<textarea name="descripcion" class="bt-form-textarea" placeholder="Describe detalladamente el servicio..." required>' + (servicio.descripcion || '') + '</textarea>' +
                '</div>' +
                '<div class="bt-form-row">' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Categoria <span class="required">*</span></label>' +
                '<select name="categoria" class="bt-form-select" required>' + opcionesCategorias + '</select>' +
                '</div>' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Horas estimadas <span class="required">*</span></label>' +
                '<input type="number" name="horas_estimadas" class="bt-form-input" value="' + (servicio.horas_estimadas || '1') + '" min="0.5" max="8" step="0.5" required>' +
                '<span class="bt-form-hint">Entre 0.5 y 8 horas</span>' +
                '</div>' +
                '</div>' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Ubicacion</label>' +
                '<input type="text" name="ubicacion" class="bt-form-input" value="' + (servicio.ubicacion || '') + '" placeholder="Ej: Centro de Madrid, Online...">' +
                '</div>' +
                '<div class="bt-form-group">' +
                '<label class="bt-form-label">Disponibilidad</label>' +
                '<textarea name="disponibilidad" class="bt-form-textarea" rows="2" placeholder="Ej: Tardes de lunes a viernes, Fines de semana...">' + (servicio.disponibilidad || '') + '</textarea>' +
                '</div>' +
                '</form>';
        },

        /**
         * Abre formulario de edición
         */
        abrirEditarServicio: function(servicioId) {
            var self = this;

            BancoTiempo.Utils.ajax('obtener_servicio', { servicio_id: servicioId }, function(response) {
                if (response.success && response.data) {
                    BancoTiempo.Modal.abrir({
                        titulo: BancoTiempo.t('editar_servicio', 'Editar servicio'),
                        contenido: self.getFormularioServicio(response.data, 'bt-form-editar-servicio'),
                        botones: [
                            { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                            { texto: BancoTiempo.t('guardar_cambios', 'Guardar cambios'), clase: 'bt-btn-success', callback: function() {
                                $('#bt-form-editar-servicio').submit();
                            }}
                        ]
                    });
                } else {
                    BancoTiempo.Utils.showToast((response.data && response.data.error) || BancoTiempo.t('error_cargar_servicio', 'Error al cargar el servicio'), 'error');
                }
            });
        },

        /**
         * Guarda nuevo servicio
         */
        guardarServicio: function(form) {
            var datos = {
                tipo: form.find('[name="tipo"]').val(),
                titulo: form.find('[name="titulo"]').val(),
                descripcion: form.find('[name="descripcion"]').val(),
                categoria: form.find('[name="categoria"]').val(),
                horas_estimadas: form.find('[name="horas_estimadas"]').val(),
                ubicacion: form.find('[name="ubicacion"]').val(),
                disponibilidad: form.find('[name="disponibilidad"]').val()
            };

            BancoTiempo.Utils.ajax('crear_servicio', datos, function(response) {
                if (response.success) {
                    BancoTiempo.Modal.cerrar();
                    BancoTiempo.Utils.showToast(BancoTiempo.t('servicio_publicado', 'Servicio publicado correctamente'), 'success');
                    BancoTiempo.Servicios.cargarServicios();
                } else {
                    BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_publicar_servicio', 'Error al publicar servicio'), 'error');
                }
            });
        },

        /**
         * Actualiza servicio existente
         */
        actualizarServicio: function(form) {
            var datos = {
                servicio_id: form.find('[name="servicio_id"]').val(),
                titulo: form.find('[name="titulo"]').val(),
                descripcion: form.find('[name="descripcion"]').val(),
                categoria: form.find('[name="categoria"]').val(),
                horas_estimadas: form.find('[name="horas_estimadas"]').val(),
                ubicacion: form.find('[name="ubicacion"]').val(),
                disponibilidad: form.find('[name="disponibilidad"]').val()
            };

            BancoTiempo.Utils.ajax('actualizar_servicio', datos, function(response) {
                if (response.success) {
                    BancoTiempo.Modal.cerrar();
                    BancoTiempo.Utils.showToast(BancoTiempo.t('servicio_actualizado', 'Servicio actualizado'), 'success');
                    BancoTiempo.MisServicios.cargar();
                } else {
                    BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_actualizar_servicio', 'Error al actualizar'), 'error');
                }
            });
        },

        /**
         * Cambia estado del servicio propio
         */
        cambiarEstadoServicio: function(servicioId, accion, confirmacion, mensajeOk) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('actualizar_servicio', 'Actualizar servicio'),
                contenido: '<p>' + confirmacion + '</p>',
                botones: [
                    { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('confirmar', 'Confirmar'), clase: 'bt-btn-primary', callback: function() {
                        BancoTiempo.Utils.ajax(accion, { servicio_id: servicioId }, function(response) {
                            if (response.success) {
                                BancoTiempo.Modal.cerrar();
                                BancoTiempo.Utils.showToast(mensajeOk, 'success');
                                BancoTiempo.MisServicios.cargar();
                            } else {
                                BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_actualizar_servicio', 'Error al actualizar'), 'error');
                            }
                        });
                    }}
                ]
            });
        },

        /**
         * Elimina servicio propio
         */
        eliminarServicio: function(servicioId) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('eliminar_servicio', 'Eliminar servicio'),
                contenido: '<p>' + BancoTiempo.t('confirmar_eliminar_servicio', '¿Seguro que quieres eliminar este servicio? Esta acción no se puede deshacer.') + '</p>',
                botones: [
                    { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('eliminar', 'Eliminar'), clase: 'bt-btn-danger', callback: function() {
                        BancoTiempo.Utils.ajax('eliminar_servicio', { servicio_id: servicioId }, function(response) {
                            if (response.success) {
                                BancoTiempo.Modal.cerrar();
                                BancoTiempo.Utils.showToast(BancoTiempo.t('servicio_eliminado', 'Servicio eliminado'), 'success');
                                BancoTiempo.MisServicios.cargar();
                            } else {
                                BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_eliminar_servicio', 'Error al eliminar'), 'error');
                            }
                        });
                    }}
                ]
            });
        }
    };

    /**
     * Modulo de Intercambios
     */
    BancoTiempo.Intercambios = {
        /**
         * Inicializa el modulo
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            var self = this;

            // Aceptar intercambio
            $(document).on('click', '.bt-btn-aceptar-intercambio', function() {
                var intercambioId = $(this).data('intercambio-id');
                self.aceptar(intercambioId);
            });

            // Rechazar intercambio
            $(document).on('click', '.bt-btn-rechazar-intercambio', function() {
                var intercambioId = $(this).data('intercambio-id');
                self.rechazar(intercambioId);
            });

            // Completar intercambio
            $(document).on('click', '.bt-btn-completar-intercambio', function() {
                var intercambioId = $(this).data('intercambio-id');
                self.abrirModalCompletar(intercambioId);
            });

            // Cancelar intercambio
            $(document).on('click', '.bt-btn-cancelar-intercambio', function() {
                var intercambioId = $(this).data('intercambio-id');
                self.cancelar(intercambioId);
            });

            // Tabs de historial
            $(document).on('click', '.bt-tab', function() {
                var tab = $(this).data('tab');
                $('.bt-tab').removeClass('active');
                $(this).addClass('active');
                $('.bt-tab-content').removeClass('active');
                $('#bt-tab-' + tab).addClass('active');
            });
        },

        /**
         * Carga historial de intercambios
         */
        cargarHistorial: function(filtro) {
            filtro = filtro || 'todos';
            var contenedor = $('.bt-historial-lista');

            contenedor.html('<div class="bt-loading"><div class="bt-spinner"></div></div>');

            BancoTiempo.Utils.ajax('obtener_historial', { filtro: filtro }, function(response) {
                if (response.success && response.data.intercambios.length > 0) {
                    var html = '';
                    response.data.intercambios.forEach(function(intercambio) {
                        html += BancoTiempo.Intercambios.renderIntercambioItem(intercambio);
                    });
                    contenedor.html(html);
                } else {
                    contenedor.html('<div class="bt-empty"><div class="bt-empty-icon"><span class="dashicons dashicons-randomize"></span></div><p class="bt-empty-title">' + BancoTiempo.t('sin_intercambios', 'No hay intercambios') + '</p></div>');
                }
            });
        },

        /**
         * Renderiza item de intercambio
         */
        renderIntercambioItem: function(intercambio) {
            var esEntrante = intercambio.direccion === 'entrante';
            var iconoClase = esEntrante ? 'entrante' : 'saliente';
            var icono = esEntrante ? 'dashicons-download' : 'dashicons-upload';
            var horasClase = esEntrante ? 'positivo' : 'negativo';
            var horasSigno = esEntrante ? '+' : '-';

            return '<div class="bt-intercambio-item">' +
                '<div class="bt-intercambio-icono ' + iconoClase + '"><span class="dashicons ' + icono + '"></span></div>' +
                '<div class="bt-intercambio-info">' +
                '<div class="bt-intercambio-titulo">' + intercambio.servicio_titulo + '</div>' +
                '<div class="bt-intercambio-detalle">' +
                (esEntrante ? BancoTiempo.t('de', 'De: ') : BancoTiempo.t('para', 'Para: ')) + intercambio.otro_usuario + ' - ' +
                BancoTiempo.Utils.formatFechaRelativa(intercambio.fecha) +
                '</div>' +
                '</div>' +
                '<div class="bt-intercambio-horas">' +
                '<div class="bt-intercambio-horas-valor ' + horasClase + '">' + horasSigno + BancoTiempo.Utils.formatHoras(intercambio.horas) + '</div>' +
                '<span class="bt-intercambio-estado ' + intercambio.estado + '">' + intercambio.estado_texto + '</span>' +
                '</div>' +
                '</div>';
        },

        /**
         * Acepta intercambio
         */
        aceptar: function(intercambioId) {
            BancoTiempo.Utils.ajax('aceptar_intercambio', { intercambio_id: intercambioId }, function(response) {
                if (response.success) {
                    BancoTiempo.Utils.showToast(BancoTiempo.t('intercambio_aceptado', 'Intercambio aceptado'), 'success');
                    BancoTiempo.Intercambios.cargarHistorial();
                } else {
                    BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_aceptar', 'Error al aceptar'), 'error');
                }
            });
        },

        /**
         * Rechaza intercambio
         */
        rechazar: function(intercambioId) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('rechazar_intercambio', 'Rechazar intercambio'),
                contenido: '<p>' + BancoTiempo.t('confirmar_rechazar_intercambio', '¿Estas seguro de rechazar este intercambio?') + '</p>',
                botones: [
                    { texto: BancoTiempo.t('mantener', 'Mantener'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('rechazar', 'Rechazar'), clase: 'bt-btn-danger', callback: function() {
                        BancoTiempo.Utils.ajax('rechazar_intercambio', { intercambio_id: intercambioId }, function(response) {
                            if (response.success) {
                                BancoTiempo.Modal.cerrar();
                                BancoTiempo.Utils.showToast(BancoTiempo.t('intercambio_rechazado', 'Intercambio rechazado'), 'info');
                                BancoTiempo.Intercambios.cargarHistorial();
                            } else {
                                BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_generico', 'Error'), 'error');
                            }
                        });
                    }}
                ]
            });
        },

        /**
         * Abre modal para completar intercambio
         */
        abrirModalCompletar: function(intercambioId) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('completar_intercambio', 'Completar Intercambio'),
                contenido: '<form id="bt-form-completar">' +
                    '<input type="hidden" name="intercambio_id" value="' + intercambioId + '">' +
                    '<div class="bt-form-group">' +
                    '<label class="bt-form-label">' + BancoTiempo.t('horas_reales_servicio', 'Horas reales del servicio') + '</label>' +
                    '<input type="number" name="horas_reales" class="bt-form-input" min="0.5" max="8" step="0.5" required>' +
                    '</div>' +
                    '<div class="bt-form-group">' +
                    '<label class="bt-form-label">' + BancoTiempo.t('valoracion', 'Valoración') + '</label>' +
                    '<div class="bt-valoracion" id="bt-valoracion-completar"></div>' +
                    '<input type="hidden" name="valoracion" value="5">' +
                    '</div>' +
                    '<div class="bt-form-group">' +
                    '<label class="bt-form-label">' + BancoTiempo.t('comentario', 'Comentario') + '</label>' +
                    '<textarea name="comentario" class="bt-form-textarea" placeholder="' + BancoTiempo.t('comparte_experiencia', 'Comparte tu experiencia...') + '"></textarea>' +
                    '</div>' +
                    '</form>',
                botones: [
                    { texto: BancoTiempo.t('cancelar', 'Cancelar'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('confirmar', 'Confirmar'), clase: 'bt-btn-success', callback: function() {
                        BancoTiempo.Intercambios.completar();
                    }}
                ],
                onOpen: function() {
                    BancoTiempo.Valoracion.init('#bt-valoracion-completar', 5, function(valor) {
                        $('#bt-form-completar [name="valoracion"]').val(valor);
                    });
                }
            });
        },

        /**
         * Completa intercambio
         */
        completar: function() {
            var form = $('#bt-form-completar');
            var datos = {
                intercambio_id: form.find('[name="intercambio_id"]').val(),
                horas_reales: form.find('[name="horas_reales"]').val(),
                valoracion: form.find('[name="valoracion"]').val(),
                comentario: form.find('[name="comentario"]').val()
            };

            BancoTiempo.Utils.ajax('completar_intercambio', datos, function(response) {
                if (response.success) {
                    BancoTiempo.Modal.cerrar();
                    BancoTiempo.Utils.showToast(BancoTiempo.t('intercambio_completado', 'Intercambio completado. Gracias por participar!'), 'success');
                    BancoTiempo.Intercambios.cargarHistorial();
                    BancoTiempo.Saldo.actualizar();
                } else {
                    BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_generico', 'Error'), 'error');
                }
            });
        },

        /**
         * Cancela intercambio
         */
        cancelar: function(intercambioId) {
            BancoTiempo.Modal.abrir({
                titulo: BancoTiempo.t('cancelar_intercambio', 'Cancelar intercambio'),
                contenido: '<p>' + BancoTiempo.t('confirmar_cancelar_intercambio', '¿Estas seguro de cancelar este intercambio?') + '</p>',
                botones: [
                    { texto: BancoTiempo.t('volver', 'Volver'), clase: 'bt-btn-secondary', cerrar: true },
                    { texto: BancoTiempo.t('cancelar_intercambio', 'Cancelar intercambio'), clase: 'bt-btn-danger', callback: function() {
                        BancoTiempo.Utils.ajax('cancelar_intercambio', { intercambio_id: intercambioId }, function(response) {
                            if (response.success) {
                                BancoTiempo.Modal.cerrar();
                                BancoTiempo.Utils.showToast(BancoTiempo.t('intercambio_cancelado', 'Intercambio cancelado'), 'info');
                                BancoTiempo.Intercambios.cargarHistorial();
                            } else {
                                BancoTiempo.Utils.showToast(response.data.error || BancoTiempo.t('error_generico', 'Error'), 'error');
                            }
                        });
                    }}
                ]
            });
        }
    };

    /**
     * Modulo de Saldo
     */
    BancoTiempo.Saldo = {
        /**
         * Inicializa el modulo
         */
        init: function() {
            this.actualizar();
        },

        /**
         * Actualiza saldo mostrado
         */
        actualizar: function() {
            BancoTiempo.Utils.ajax('obtener_saldo', {}, function(response) {
                if (response.success) {
                    var saldo = response.data;
                    $('.bt-saldo-actual').text(BancoTiempo.Utils.formatHoras(saldo.saldo_actual));
                    $('.bt-horas-ganadas').text(BancoTiempo.Utils.formatHoras(saldo.horas_ganadas));
                    $('.bt-horas-gastadas').text(BancoTiempo.Utils.formatHoras(saldo.horas_gastadas));
                    $('.bt-intercambios-pendientes').text(saldo.intercambios_pendientes);
                }
            });
        }
    };

    /**
     * Modulo de Valoraciones
     */
    BancoTiempo.Valoracion = {
        /**
         * Inicializa widget de valoracion
         */
        init: function(selector, valorInicial, onChange) {
            var contenedor = $(selector);
            valorInicial = valorInicial || 0;

            var html = '';
            for (var i = 1; i <= 5; i++) {
                var activeClass = i <= valorInicial ? ' active' : '';
                html += '<span class="bt-valoracion-star' + activeClass + '" data-valor="' + i + '">&#9733;</span>';
            }
            contenedor.html(html);

            contenedor.find('.bt-valoracion-star').on('click', function() {
                var valor = $(this).data('valor');
                contenedor.find('.bt-valoracion-star').each(function() {
                    $(this).toggleClass('active', $(this).data('valor') <= valor);
                });
                if (onChange) {
                    onChange(valor);
                }
            });

            contenedor.find('.bt-valoracion-star').on('mouseenter', function() {
                var valor = $(this).data('valor');
                contenedor.find('.bt-valoracion-star').each(function() {
                    $(this).toggleClass('active', $(this).data('valor') <= valor);
                });
            });

            contenedor.on('mouseleave', function() {
                var valorActual = contenedor.closest('form').find('[name="valoracion"]').val() || valorInicial;
                contenedor.find('.bt-valoracion-star').each(function() {
                    $(this).toggleClass('active', $(this).data('valor') <= valorActual);
                });
            });
        }
    };

    /**
     * Modulo de Modal
     */
    BancoTiempo.Modal = {
        /**
         * Abre modal
         */
        abrir: function(opciones) {
            var overlay = $('#bt-modal-overlay');

            if (!overlay.length) {
                overlay = $('<div id="bt-modal-overlay" class="bt-modal-overlay"><div class="bt-modal"><div class="bt-modal-header"><h3 class="bt-modal-title"></h3><button class="bt-modal-close">&times;</button></div><div class="bt-modal-body"></div><div class="bt-modal-footer"></div></div></div>');
                $('body').append(overlay);

                overlay.on('click', function(e) {
                    if ($(e.target).is('.bt-modal-overlay') || $(e.target).is('.bt-modal-close')) {
                        BancoTiempo.Modal.cerrar();
                    }
                });
            }

            overlay.find('.bt-modal-title').text(opciones.titulo || '');
            overlay.find('.bt-modal-body').html(opciones.contenido || '');

            var footer = overlay.find('.bt-modal-footer').empty();
            if (opciones.botones && opciones.botones.length) {
                opciones.botones.forEach(function(boton) {
                    var btn = $('<button class="bt-btn ' + (boton.clase || '') + '">' + boton.texto + '</button>');
                    if (boton.cerrar) {
                        btn.on('click', function() {
                            BancoTiempo.Modal.cerrar();
                        });
                    }
                    if (boton.callback) {
                        btn.on('click', boton.callback);
                    }
                    footer.append(btn);
                });
            }

            overlay.addClass('active');
            $('body').css('overflow', 'hidden');

            if (opciones.onOpen) {
                setTimeout(opciones.onOpen, 100);
            }
        },

        /**
         * Cierra modal
         */
        cerrar: function() {
            $('#bt-modal-overlay').removeClass('active');
            $('body').css('overflow', '');
        }
    };

    /**
     * Modulo Mis Servicios
     */
    BancoTiempo.MisServicios = {
        /**
         * Carga mis servicios
         */
        cargar: function() {
            var contenedor = $('.bt-mis-servicios-lista');
            if (!contenedor.length) return;

            contenedor.html('<div class="bt-loading"><div class="bt-spinner"></div></div>');

            BancoTiempo.Utils.ajax('obtener_mis_servicios', {}, function(response) {
                if (response.success && response.data.servicios.length > 0) {
                    var html = '';
                    response.data.servicios.forEach(function(servicio) {
                        html += BancoTiempo.MisServicios.renderItem(servicio);
                    });
                    contenedor.html(html);
                } else {
                    contenedor.html('<div class="bt-empty"><div class="bt-empty-icon"><span class="dashicons dashicons-admin-post"></span></div><p class="bt-empty-title">' + BancoTiempo.t('sin_servicios_publicados', 'No tienes servicios publicados') + '</p><button class="bt-btn bt-btn-primary bt-btn-nuevo-servicio">' + BancoTiempo.t('publicar_servicio', 'Publicar servicio') + '</button></div>');
                }
            });
        },

        /**
         * Renderiza item de mis servicios
         */
        renderItem: function(servicio) {
            var estadoClase = servicio.estado;
            return '<div class="bt-servicio-card" data-servicio-id="' + servicio.id + '">' +
                '<div class="bt-servicio-card-header">' +
                '<span class="bt-servicio-categoria">' + servicio.categoria_nombre + '</span>' +
                '<span class="bt-intercambio-estado ' + estadoClase + '">' + servicio.estado_texto + '</span>' +
                '</div>' +
                '<h3 class="bt-servicio-titulo">' + servicio.titulo + '</h3>' +
                '<p class="bt-servicio-descripcion">' + servicio.descripcion + '</p>' +
                '<div class="bt-servicio-meta">' +
                '<span class="bt-servicio-meta-item"><span class="dashicons dashicons-clock"></span>' + BancoTiempo.Utils.formatHoras(servicio.horas_estimadas) + '</span>' +
                '<span class="bt-servicio-meta-item"><span class="dashicons dashicons-visibility"></span>' + servicio.solicitudes_count + ' ' + BancoTiempo.t('solicitudes', 'solicitudes') + '</span>' +
                '</div>' +
                '<div class="bt-servicio-acciones">' +
                '<button class="bt-btn bt-btn-secondary bt-btn-sm bt-btn-editar" data-servicio-id="' + servicio.id + '">' + BancoTiempo.t('editar', 'Editar') + '</button>' +
                (servicio.estado === 'activo' ? '<button class="bt-btn bt-btn-secondary bt-btn-sm bt-btn-pausar" data-servicio-id="' + servicio.id + '">' + BancoTiempo.t('pausar', 'Pausar') + '</button>' : '<button class="bt-btn bt-btn-primary bt-btn-sm bt-btn-activar" data-servicio-id="' + servicio.id + '">' + BancoTiempo.t('activar', 'Activar') + '</button>') +
                '<button class="bt-btn bt-btn-danger bt-btn-sm bt-btn-eliminar" data-servicio-id="' + servicio.id + '">' + BancoTiempo.t('eliminar', 'Eliminar') + '</button>' +
                '</div>' +
                '</div>';
        }
    };

    /**
     * Inicializacion
     */
    $(document).ready(function() {
        // Inicializar modulos segun contexto
        if ($('.banco-tiempo-container').length) {
            BancoTiempo.Servicios.init();
            BancoTiempo.Formularios.init();
            BancoTiempo.Intercambios.init();
        }

        if ($('.bt-saldo-cards').length) {
            BancoTiempo.Saldo.init();
        }

        if ($('.bt-mis-servicios-lista').length) {
            BancoTiempo.MisServicios.cargar();
        }

        if ($('.bt-historial-lista').length) {
            BancoTiempo.Intercambios.cargarHistorial();
        }
    });

})(jQuery);
