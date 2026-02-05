/**
 * Participacion Module JavaScript
 * Sistema de Participacion Ciudadana
 */

(function($) {
    'use strict';

    // Namespace global
    window.FlavorParticipacion = window.FlavorParticipacion || {};

    /**
     * Configuracion del modulo
     */
    const CONFIG = {
        ajaxUrl: typeof flavorParticipacionConfig !== 'undefined' ? flavorParticipacionConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
        nonce: typeof flavorParticipacionConfig !== 'undefined' ? flavorParticipacionConfig.nonce : '',
        strings: typeof flavorParticipacionConfig !== 'undefined' ? flavorParticipacionConfig.strings : {}
    };

    /**
     * Utilidades
     */
    const Utils = {
        showLoading: function(containerSelector) {
            const contenedor = $(containerSelector);
            contenedor.addClass('participacion-loading-state');
            contenedor.append('<div class="participacion-loading"><div class="participacion-spinner"></div></div>');
        },

        hideLoading: function(containerSelector) {
            const contenedor = $(containerSelector);
            contenedor.removeClass('participacion-loading-state');
            contenedor.find('.participacion-loading').remove();
        },

        showAlert: function(mensaje, tipo, containerSelector) {
            const tipoClase = tipo || 'info';
            const iconos = {
                info: '&#8505;',
                exito: '&#10003;',
                advertencia: '&#9888;',
                error: '&#10007;'
            };

            const alertaHtml = `
                <div class="alerta-participacion ${tipoClase}" role="alert">
                    <span class="alerta-icono">${iconos[tipoClase]}</span>
                    <span class="alerta-mensaje">${mensaje}</span>
                    <button type="button" class="alerta-cerrar" aria-label="Cerrar">&times;</button>
                </div>
            `;

            if (containerSelector) {
                $(containerSelector).prepend(alertaHtml);
            } else {
                $('.participacion-container').prepend(alertaHtml);
            }

            setTimeout(function() {
                $('.alerta-participacion').first().fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        formatNumber: function(numero) {
            return new Intl.NumberFormat('es-ES').format(numero);
        },

        formatDate: function(fecha) {
            return new Date(fecha).toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Modulo de Propuestas
     */
    const Propuestas = {
        init: function() {
            this.bindEvents();
            this.initFiltros();
        },

        bindEvents: function() {
            $(document).on('submit', '.form-crear-propuesta', this.handleCrearPropuesta.bind(this));
            $(document).on('click', '.btn-apoyar', this.handleApoyar.bind(this));
            $(document).on('click', '.btn-cargar-mas-propuestas', this.handleCargarMas.bind(this));
            $(document).on('change', '.filtro-propuestas select', this.handleFiltroChange.bind(this));
            $(document).on('input', '.filtro-busqueda-propuestas input', Utils.debounce(this.handleBusqueda.bind(this), 300));
        },

        initFiltros: function() {
            const filtroUrlParams = new URLSearchParams(window.location.search);
            const categoriaUrl = filtroUrlParams.get('categoria');
            const estadoUrl = filtroUrlParams.get('estado');

            if (categoriaUrl) {
                $('select[name="filtro_categoria"]').val(categoriaUrl);
            }
            if (estadoUrl) {
                $('select[name="filtro_estado"]').val(estadoUrl);
            }
        },

        handleCrearPropuesta: function(evento) {
            evento.preventDefault();

            const formulario = $(evento.currentTarget);
            const botonEnviar = formulario.find('button[type="submit"]');
            const textoOriginal = botonEnviar.text();

            // Validacion basica
            const titulo = formulario.find('input[name="titulo"]').val().trim();
            const descripcion = formulario.find('textarea[name="descripcion"]').val().trim();

            if (!titulo || titulo.length < 10) {
                Utils.showAlert('El titulo debe tener al menos 10 caracteres.', 'error', '.form-crear-propuesta');
                return;
            }

            if (!descripcion || descripcion.length < 50) {
                Utils.showAlert('La descripcion debe tener al menos 50 caracteres.', 'error', '.form-crear-propuesta');
                return;
            }

            botonEnviar.prop('disabled', true).text('Enviando...');

            const datosFormulario = {
                action: 'participacion_crear_propuesta',
                nonce: CONFIG.nonce,
                titulo: titulo,
                descripcion: descripcion,
                categoria: formulario.find('select[name="categoria"]').val(),
                presupuesto_estimado: formulario.find('input[name="presupuesto_estimado"]').val(),
                ambito: formulario.find('select[name="ambito"]').val()
            };

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: datosFormulario,
                success: function(respuesta) {
                    if (respuesta.success) {
                        Utils.showAlert(respuesta.data.mensaje, 'exito');
                        formulario[0].reset();

                        if (respuesta.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = respuesta.data.redirect_url;
                            }, 1500);
                        }
                    } else {
                        Utils.showAlert(respuesta.data.error || 'Error al crear la propuesta.', 'error');
                    }
                },
                error: function() {
                    Utils.showAlert('Error de conexion. Intentalo de nuevo.', 'error');
                },
                complete: function() {
                    botonEnviar.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        handleApoyar: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const propuestaId = boton.data('propuesta-id');
            const contenedorVotos = boton.closest('.propuesta-card, .propuesta-detalle').find('.votos-favor-valor');

            if (boton.hasClass('apoyado') || boton.prop('disabled')) {
                return;
            }

            boton.prop('disabled', true);

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_apoyar_propuesta',
                    nonce: CONFIG.nonce,
                    propuesta_id: propuestaId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        boton.addClass('apoyado')
                             .html('<span class="icono-check">&#10003;</span> Apoyada')
                             .prop('disabled', true);

                        if (contenedorVotos.length) {
                            contenedorVotos.text(respuesta.data.votos_favor);
                        }

                        Utils.showAlert(respuesta.data.mensaje, 'exito');
                    } else {
                        Utils.showAlert(respuesta.data.error, 'advertencia');
                        boton.prop('disabled', false);
                    }
                },
                error: function() {
                    Utils.showAlert('Error al registrar el apoyo.', 'error');
                    boton.prop('disabled', false);
                }
            });
        },

        handleCargarMas: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const contenedorGrid = $('.propuestas-grid');
            const paginaActual = parseInt(boton.data('pagina')) || 1;
            const siguientePagina = paginaActual + 1;

            boton.prop('disabled', true).text('Cargando...');

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_cargar_propuestas',
                    nonce: CONFIG.nonce,
                    pagina: siguientePagina,
                    categoria: $('select[name="filtro_categoria"]').val(),
                    estado: $('select[name="filtro_estado"]').val()
                },
                success: function(respuesta) {
                    if (respuesta.success && respuesta.data.html) {
                        contenedorGrid.append(respuesta.data.html);
                        boton.data('pagina', siguientePagina);

                        if (!respuesta.data.tiene_mas) {
                            boton.hide();
                        }
                    }
                },
                complete: function() {
                    boton.prop('disabled', false).text('Cargar mas propuestas');
                }
            });
        },

        handleFiltroChange: function() {
            this.aplicarFiltros();
        },

        handleBusqueda: function(evento) {
            const terminoBusqueda = $(evento.target).val();
            this.aplicarFiltros(terminoBusqueda);
        },

        aplicarFiltros: function(busqueda) {
            const contenedorGrid = $('.propuestas-grid');
            const categoria = $('select[name="filtro_categoria"]').val();
            const estado = $('select[name="filtro_estado"]').val();
            const orden = $('select[name="filtro_orden"]').val();

            Utils.showLoading('.propuestas-grid');

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_filtrar_propuestas',
                    nonce: CONFIG.nonce,
                    categoria: categoria,
                    estado: estado,
                    orden: orden,
                    busqueda: busqueda || ''
                },
                success: function(respuesta) {
                    Utils.hideLoading('.propuestas-grid');

                    if (respuesta.success) {
                        contenedorGrid.html(respuesta.data.html);

                        // Actualizar URL sin recargar
                        const urlNueva = new URL(window.location);
                        if (categoria) urlNueva.searchParams.set('categoria', categoria);
                        if (estado) urlNueva.searchParams.set('estado', estado);
                        window.history.pushState({}, '', urlNueva);
                    }
                },
                error: function() {
                    Utils.hideLoading('.propuestas-grid');
                    Utils.showAlert('Error al filtrar propuestas.', 'error');
                }
            });
        }
    };

    /**
     * Modulo de Votaciones
     */
    const Votaciones = {
        seleccionActual: null,

        init: function() {
            this.bindEvents();
            this.initContadores();
        },

        bindEvents: function() {
            $(document).on('click', '.votacion-opcion', this.handleSeleccionOpcion.bind(this));
            $(document).on('click', '.btn-confirmar-voto', this.handleConfirmarVoto.bind(this));
            $(document).on('click', '.btn-ver-resultados', this.handleVerResultados.bind(this));
        },

        initContadores: function() {
            $('.votacion-contador').each(function() {
                const contenedorContador = $(this);
                const fechaFin = new Date(contenedorContador.data('fecha-fin')).getTime();

                const intervaloContador = setInterval(function() {
                    const ahora = new Date().getTime();
                    const distancia = fechaFin - ahora;

                    if (distancia < 0) {
                        clearInterval(intervaloContador);
                        contenedorContador.html('<span class="votacion-finalizada">Votacion finalizada</span>');
                        return;
                    }

                    const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
                    const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));

                    contenedorContador.html(`
                        <span class="contador-item">${dias}d</span>
                        <span class="contador-item">${horas}h</span>
                        <span class="contador-item">${minutos}m</span>
                    `);
                }, 1000);
            });
        },

        handleSeleccionOpcion: function(evento) {
            const opcion = $(evento.currentTarget);
            const votacionId = opcion.closest('.votacion-card').data('votacion-id');
            const opcionValor = opcion.data('opcion');
            const permiteMultiples = opcion.closest('.votacion-opciones').data('permite-multiples');

            if (!permiteMultiples) {
                opcion.siblings('.votacion-opcion').removeClass('seleccionada');
            }

            opcion.toggleClass('seleccionada');

            // Habilitar boton de confirmar
            const haySeleccion = opcion.closest('.votacion-opciones').find('.votacion-opcion.seleccionada').length > 0;
            opcion.closest('.votacion-card').find('.btn-confirmar-voto').prop('disabled', !haySeleccion);
        },

        handleConfirmarVoto: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const tarjetaVotacion = boton.closest('.votacion-card');
            const votacionId = tarjetaVotacion.data('votacion-id');
            const opcionesSeleccionadas = tarjetaVotacion.find('.votacion-opcion.seleccionada');

            if (opcionesSeleccionadas.length === 0) {
                Utils.showAlert('Selecciona una opcion para votar.', 'advertencia');
                return;
            }

            const votos = [];
            opcionesSeleccionadas.each(function() {
                votos.push($(this).data('opcion'));
            });

            boton.prop('disabled', true).text('Registrando voto...');

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_votar',
                    nonce: CONFIG.nonce,
                    votacion_id: votacionId,
                    opciones: votos
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        Utils.showAlert(respuesta.data.mensaje, 'exito');

                        // Mostrar resultados
                        Votaciones.mostrarResultados(tarjetaVotacion, respuesta.data.resultados);

                        // Deshabilitar votacion
                        tarjetaVotacion.find('.votacion-opcion').addClass('votada').off('click');
                        boton.hide();
                    } else {
                        Utils.showAlert(respuesta.data.error, 'error');
                        boton.prop('disabled', false).text('Confirmar voto');
                    }
                },
                error: function() {
                    Utils.showAlert('Error al registrar el voto.', 'error');
                    boton.prop('disabled', false).text('Confirmar voto');
                }
            });
        },

        handleVerResultados: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const votacionId = boton.data('votacion-id');

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_resultados_votacion',
                    nonce: CONFIG.nonce,
                    votacion_id: votacionId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        Votaciones.mostrarModalResultados(respuesta.data);
                    }
                }
            });
        },

        mostrarResultados: function(tarjetaVotacion, resultados) {
            const contenedorOpciones = tarjetaVotacion.find('.votacion-opciones');

            contenedorOpciones.find('.votacion-opcion').each(function() {
                const opcion = $(this);
                const opcionId = opcion.data('opcion');
                const resultado = resultados[opcionId] || { porcentaje: 0, votos: 0 };

                opcion.find('.votacion-opcion-barra').show();
                opcion.find('.votacion-opcion-fill').css('width', resultado.porcentaje + '%');
                opcion.find('.votacion-opcion-porcentaje').text(resultado.porcentaje + '%');
                opcion.append(`<span class="votacion-opcion-votos">${resultado.votos} votos</span>`);
            });
        },

        mostrarModalResultados: function(datos) {
            const modalHtml = `
                <div class="modal-participacion" id="modal-resultados">
                    <div class="modal-contenido">
                        <div class="modal-header">
                            <h3>Resultados: ${datos.titulo}</h3>
                            <button class="modal-cerrar">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="resultados-container">
                                ${datos.opciones.map((opcion, indice) => `
                                    <div class="resultado-opcion">
                                        <div class="resultado-opcion-header">
                                            <span class="resultado-opcion-nombre">${opcion.texto}</span>
                                            <span class="resultado-opcion-votos">${opcion.votos} votos</span>
                                        </div>
                                        <div class="resultado-barra">
                                            <div class="resultado-fill ${indice === 0 ? 'ganador' : 'otros'}"
                                                 style="width: ${opcion.porcentaje}%">
                                                <span class="resultado-porcentaje">${opcion.porcentaje}%</span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="resultados-total">
                                <strong>Total de votos:</strong> ${datos.total_votos}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('#modal-resultados').fadeIn(200);

            $('#modal-resultados .modal-cerrar, #modal-resultados').on('click', function(evento) {
                if (evento.target === this) {
                    $('#modal-resultados').fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });
        }
    };

    /**
     * Modulo de Comentarios
     */
    const Comentarios = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('submit', '.form-comentario', this.handleEnviarComentario.bind(this));
            $(document).on('click', '.comentario-responder', this.handleMostrarRespuesta.bind(this));
            $(document).on('click', '.comentario-like', this.handleLikeComentario.bind(this));
            $(document).on('click', '.btn-cargar-comentarios', this.handleCargarMas.bind(this));
        },

        handleEnviarComentario: function(evento) {
            evento.preventDefault();

            const formulario = $(evento.currentTarget);
            const propuestaId = formulario.data('propuesta-id');
            const comentarioPadreId = formulario.data('comentario-padre') || 0;
            const contenidoTextarea = formulario.find('textarea');
            const contenido = contenidoTextarea.val().trim();
            const botonEnviar = formulario.find('button[type="submit"]');

            if (!contenido || contenido.length < 5) {
                Utils.showAlert('El comentario debe tener al menos 5 caracteres.', 'advertencia');
                return;
            }

            botonEnviar.prop('disabled', true);

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_comentar',
                    nonce: CONFIG.nonce,
                    propuesta_id: propuestaId,
                    comentario_padre_id: comentarioPadreId,
                    contenido: contenido
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        contenidoTextarea.val('');

                        // Insertar nuevo comentario
                        const listaComentarios = $('.comentarios-lista');

                        if (comentarioPadreId) {
                            $(`.comentario-item[data-comentario-id="${comentarioPadreId}"]`)
                                .find('.comentario-respuestas')
                                .append(respuesta.data.html);
                        } else {
                            listaComentarios.prepend(respuesta.data.html);
                        }

                        // Actualizar contador
                        const contadorActual = parseInt($('.comentarios-count').text()) || 0;
                        $('.comentarios-count').text(contadorActual + 1);

                        Utils.showAlert('Comentario publicado.', 'exito');
                    } else {
                        Utils.showAlert(respuesta.data.error, 'error');
                    }
                },
                error: function() {
                    Utils.showAlert('Error al publicar el comentario.', 'error');
                },
                complete: function() {
                    botonEnviar.prop('disabled', false);
                }
            });
        },

        handleMostrarRespuesta: function(evento) {
            evento.preventDefault();

            const botonResponder = $(evento.currentTarget);
            const comentarioItem = botonResponder.closest('.comentario-item');
            const comentarioId = comentarioItem.data('comentario-id');
            const propuestaId = comentarioItem.closest('.comentarios-section').data('propuesta-id');

            // Ocultar otros formularios de respuesta
            $('.form-respuesta-comentario').remove();

            const formularioRespuesta = `
                <form class="form-comentario form-respuesta-comentario"
                      data-propuesta-id="${propuestaId}"
                      data-comentario-padre="${comentarioId}">
                    <textarea placeholder="Escribe tu respuesta..." rows="2"></textarea>
                    <div class="form-respuesta-acciones">
                        <button type="button" class="btn-cancelar-respuesta btn-participacion btn-participacion-secondary btn-participacion-sm">Cancelar</button>
                        <button type="submit" class="btn-participacion btn-participacion-primary btn-participacion-sm">Responder</button>
                    </div>
                </form>
            `;

            comentarioItem.find('.comentario-contenido').append(formularioRespuesta);

            // Cancelar respuesta
            comentarioItem.find('.btn-cancelar-respuesta').on('click', function() {
                $(this).closest('.form-respuesta-comentario').remove();
            });
        },

        handleLikeComentario: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const comentarioId = boton.closest('.comentario-item').data('comentario-id');
            const contadorLikes = boton.find('.likes-count');

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_like_comentario',
                    nonce: CONFIG.nonce,
                    comentario_id: comentarioId
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        boton.toggleClass('liked');
                        contadorLikes.text(respuesta.data.total_likes);
                    }
                }
            });
        },

        handleCargarMas: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const propuestaId = boton.data('propuesta-id');
            const offset = parseInt(boton.data('offset')) || 0;

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_cargar_comentarios',
                    nonce: CONFIG.nonce,
                    propuesta_id: propuestaId,
                    offset: offset
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        $('.comentarios-lista').append(respuesta.data.html);
                        boton.data('offset', offset + 10);

                        if (!respuesta.data.tiene_mas) {
                            boton.hide();
                        }
                    }
                }
            });
        }
    };

    /**
     * Modulo de Fases
     */
    const Fases = {
        init: function() {
            this.animarProgreso();
            this.actualizarFaseActiva();
        },

        animarProgreso: function() {
            $('.progreso-fill').each(function() {
                const barra = $(this);
                const porcentajeObjetivo = barra.data('porcentaje') || 0;

                barra.css('width', '0%');

                setTimeout(function() {
                    barra.css('width', porcentajeObjetivo + '%');
                }, 100);
            });
        },

        actualizarFaseActiva: function() {
            const fases = $('.fase-item');
            const ahora = new Date();

            fases.each(function() {
                const fase = $(this);
                const fechaInicio = new Date(fase.data('fecha-inicio'));
                const fechaFin = new Date(fase.data('fecha-fin'));

                if (ahora >= fechaInicio && ahora <= fechaFin) {
                    fase.addClass('activa');
                } else if (ahora > fechaFin) {
                    fase.addClass('completada');
                }
            });
        }
    };

    /**
     * Modulo de Presupuesto Participativo
     */
    const PresupuestoParticipativo = {
        init: function() {
            this.bindEvents();
            this.actualizarTotales();
        },

        bindEvents: function() {
            $(document).on('click', '.btn-asignar-presupuesto', this.handleAsignarPresupuesto.bind(this));
            $(document).on('input', '.input-cantidad-presupuesto', Utils.debounce(this.handleCambioCantidad.bind(this), 300));
        },

        handleAsignarPresupuesto: function(evento) {
            evento.preventDefault();

            const boton = $(evento.currentTarget);
            const propuestaId = boton.data('propuesta-id');
            const cantidad = boton.closest('.propuesta-presupuesto').find('.input-cantidad-presupuesto').val();

            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'participacion_asignar_presupuesto',
                    nonce: CONFIG.nonce,
                    propuesta_id: propuestaId,
                    cantidad: cantidad
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        Utils.showAlert('Presupuesto asignado correctamente.', 'exito');
                        PresupuestoParticipativo.actualizarTotales();
                    } else {
                        Utils.showAlert(respuesta.data.error, 'error');
                    }
                }
            });
        },

        handleCambioCantidad: function() {
            this.actualizarTotales();
        },

        actualizarTotales: function() {
            let totalAsignado = 0;
            const presupuestoTotal = parseFloat($('.presupuesto-info').data('presupuesto-total')) || 0;

            $('.input-cantidad-presupuesto').each(function() {
                totalAsignado += parseFloat($(this).val()) || 0;
            });

            const disponible = presupuestoTotal - totalAsignado;
            const porcentajeUsado = (totalAsignado / presupuestoTotal) * 100;

            $('.presupuesto-asignado').text(Utils.formatNumber(totalAsignado) + ' EUR');
            $('.presupuesto-disponible').text(Utils.formatNumber(disponible) + ' EUR');
            $('.presupuesto-progreso-fill').css('width', Math.min(porcentajeUsado, 100) + '%');

            // Deshabilitar si se excede
            if (totalAsignado > presupuestoTotal) {
                $('.btn-asignar-presupuesto').prop('disabled', true);
                Utils.showAlert('Has excedido el presupuesto disponible.', 'advertencia');
            } else {
                $('.btn-asignar-presupuesto').prop('disabled', false);
            }
        }
    };

    /**
     * Inicializacion
     */
    $(document).ready(function() {
        // Inicializar modulos
        Propuestas.init();
        Votaciones.init();
        Comentarios.init();
        Fases.init();
        PresupuestoParticipativo.init();

        // Cerrar alertas
        $(document).on('click', '.alerta-cerrar', function() {
            $(this).closest('.alerta-participacion').fadeOut(200, function() {
                $(this).remove();
            });
        });

        // Exponer API publica
        window.FlavorParticipacion = {
            Utils: Utils,
            Propuestas: Propuestas,
            Votaciones: Votaciones,
            Comentarios: Comentarios,
            Fases: Fases,
            PresupuestoParticipativo: PresupuestoParticipativo
        };
    });

})(jQuery);
