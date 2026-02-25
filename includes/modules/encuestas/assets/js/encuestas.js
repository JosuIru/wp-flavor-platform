/**
 * JavaScript del módulo de Encuestas
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    // Configuración global
    const config = window.flavorEncuestas || {};

    /**
     * Clase principal de Encuestas
     */
    class FlavorEncuestas {
        constructor() {
            this.bindEvents();
            this.initStars();
        }

        /**
         * Vincula eventos
         */
        bindEvents() {
            // Formulario de respuesta
            $(document).on('submit', '.flavor-encuesta__form', this.handleSubmitResponse.bind(this));

            // Formulario de creación
            $(document).on('submit', '#flavor-encuesta-crear-form', this.handleCreateSurvey.bind(this));

            // Agregar opción en creación
            $(document).on('click', '#agregar-opcion', this.handleAddOption.bind(this));

            // Eliminar opción en creación
            $(document).on('click', '.flavor-encuesta-crear__remove-opcion', this.handleRemoveOption.bind(this));

            // Votación rápida en versión mini
            $(document).on('click', '.flavor-encuesta-mini__opcion', this.handleQuickVote.bind(this));

            // Interacción con estrellas
            $(document).on('click', '.flavor-encuesta__star', this.handleStarClick.bind(this));
            $(document).on('mouseover', '.flavor-encuesta__star', this.handleStarHover.bind(this));
            $(document).on('mouseout', '.flavor-encuesta__stars', this.handleStarsMouseout.bind(this));
        }

        /**
         * Inicializa el componente de estrellas
         */
        initStars() {
            $('.flavor-encuesta__stars').each(function() {
                const $container = $(this);
                const $checkedInput = $container.find('input:checked');

                if ($checkedInput.length) {
                    const valor = parseInt($checkedInput.val());
                    FlavorEncuestas.updateStarsDisplay($container, valor);
                }
            });
        }

        /**
         * Actualiza visualización de estrellas
         */
        static updateStarsDisplay($container, valorSeleccionado) {
            $container.find('.flavor-encuesta__star-icon').each(function() {
                const valor = parseInt($(this).data('value'));
                $(this).text(valor <= valorSeleccionado ? '★' : '☆');
            });
        }

        /**
         * Maneja clic en estrella
         */
        handleStarClick(evento) {
            const $star = $(evento.currentTarget);
            const $container = $star.closest('.flavor-encuesta__stars');
            const valor = parseInt($star.find('.flavor-encuesta__star-icon').data('value'));

            $star.find('input').prop('checked', true);
            FlavorEncuestas.updateStarsDisplay($container, valor);
        }

        /**
         * Maneja hover en estrella
         */
        handleStarHover(evento) {
            const $star = $(evento.currentTarget);
            const $container = $star.closest('.flavor-encuesta__stars');
            const valor = parseInt($star.find('.flavor-encuesta__star-icon').data('value'));

            FlavorEncuestas.updateStarsDisplay($container, valor);
        }

        /**
         * Maneja mouseout del contenedor de estrellas
         */
        handleStarsMouseout(evento) {
            const $container = $(evento.currentTarget);
            const $checkedInput = $container.find('input:checked');

            if ($checkedInput.length) {
                const valor = parseInt($checkedInput.val());
                FlavorEncuestas.updateStarsDisplay($container, valor);
            } else {
                FlavorEncuestas.updateStarsDisplay($container, 0);
            }
        }

        /**
         * Envía respuesta de encuesta
         */
        handleSubmitResponse(evento) {
            evento.preventDefault();

            const $form = $(evento.currentTarget);
            const $encuesta = $form.closest('.flavor-encuesta');
            const encuestaId = $encuesta.data('encuesta-id');
            const $submitBtn = $form.find('.flavor-encuesta__submit');

            // Validar campos requeridos
            if (!this.validateForm($form)) {
                return;
            }

            // Recopilar respuestas
            const respuestas = this.collectResponses($form);

            // Estado de carga
            $submitBtn.prop('disabled', true).text(config.strings.enviando);
            $encuesta.addClass('flavor-encuesta--loading');

            // Enviar
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'encuestas_responder',
                    nonce: config.nonce,
                    encuesta_id: encuestaId,
                    respuestas: respuestas
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess($encuesta, response.data);
                    } else {
                        this.showError($encuesta, response.data.message || config.strings.error);
                    }
                },
                error: () => {
                    this.showError($encuesta, config.strings.error);
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(config.strings.enviar || 'Enviar respuesta');
                    $encuesta.removeClass('flavor-encuesta--loading');
                }
            });
        }

        /**
         * Valida formulario
         */
        validateForm($form) {
            let esValido = true;

            // Limpiar errores previos
            $form.find('.flavor-encuesta__campo-error').remove();

            // Validar campos requeridos
            $form.find('[required]').each(function() {
                const $campo = $(this);
                const $contenedor = $campo.closest('.flavor-encuesta__campo');
                let campoValido = true;

                if ($campo.is(':radio')) {
                    const nombre = $campo.attr('name');
                    if (!$form.find(`input[name="${nombre}"]:checked`).length) {
                        campoValido = false;
                    }
                } else if (!$campo.val().trim()) {
                    campoValido = false;
                }

                if (!campoValido) {
                    esValido = false;
                    $contenedor.append(
                        `<p class="flavor-encuesta__campo-error" style="color: #ef4444; font-size: 0.8125rem; margin-top: 0.25rem;">
                            ${config.strings.campoRequerido}
                        </p>`
                    );
                }
            });

            return esValido;
        }

        /**
         * Recopila respuestas del formulario
         */
        collectResponses($form) {
            const respuestas = {};

            $form.find('.flavor-encuesta__campo').each(function() {
                const $campo = $(this);
                const campoId = $campo.data('campo-id');
                const $inputs = $campo.find('input, textarea, select');

                if ($inputs.first().is(':radio')) {
                    const $checked = $inputs.filter(':checked');
                    if ($checked.length) {
                        respuestas[campoId] = $checked.val();
                    }
                } else if ($inputs.first().is(':checkbox')) {
                    const valores = [];
                    $inputs.filter(':checked').each(function() {
                        valores.push($(this).val());
                    });
                    if (valores.length) {
                        respuestas[campoId] = valores;
                    }
                } else {
                    const valor = $inputs.val();
                    if (valor) {
                        respuestas[campoId] = valor;
                    }
                }
            });

            return respuestas;
        }

        /**
         * Muestra mensaje de éxito y resultados
         */
        showSuccess($encuesta, data) {
            const $form = $encuesta.find('.flavor-encuesta__form');

            // Mostrar mensaje
            $form.before(`
                <div class="flavor-encuesta__notice flavor-encuesta__notice--success">
                    ${data.message || config.strings.graciasRespuesta}
                </div>
            `);

            // Ocultar formulario
            $form.slideUp();

            // Mostrar resultados si están disponibles
            if (data.resultados) {
                this.renderResults($encuesta, data.resultados);
            }
        }

        /**
         * Muestra error
         */
        showError($encuesta, mensaje) {
            const $notice = $encuesta.find('.flavor-encuesta__notice--error');

            if ($notice.length) {
                $notice.text(mensaje);
            } else {
                $encuesta.find('.flavor-encuesta__form').before(`
                    <div class="flavor-encuesta__notice flavor-encuesta__notice--warning">
                        ${mensaje}
                    </div>
                `);
            }
        }

        /**
         * Renderiza resultados
         */
        renderResults($encuesta, resultados) {
            if (!resultados || !resultados.campos) return;

            let html = '<div class="flavor-encuesta-resultados__campos">';

            resultados.campos.forEach(campo => {
                html += this.renderFieldResults(campo, resultados.total_participantes);
            });

            html += '</div>';

            $encuesta.find('.flavor-encuesta__form').after(`
                <div class="flavor-encuesta-resultados" style="margin-top: 1rem;">
                    ${html}
                </div>
            `);

            // Animar barras
            setTimeout(() => {
                $encuesta.find('.flavor-encuesta-resultados__bar-fill').each(function() {
                    const porcentaje = $(this).data('porcentaje');
                    $(this).css('width', porcentaje + '%');
                });
            }, 100);
        }

        /**
         * Renderiza resultados de un campo
         */
        renderFieldResults(campo, totalParticipantes) {
            let html = `
                <div class="flavor-encuesta-resultados__campo">
                    <h4 class="flavor-encuesta-resultados__pregunta">${this.escapeHtml(campo.etiqueta)}</h4>
            `;

            if (campo.conteos && campo.opciones) {
                html += '<div class="flavor-encuesta-resultados__bars">';

                campo.opciones.forEach((opcion, indice) => {
                    const conteo = campo.conteos[indice] || 0;
                    const porcentaje = totalParticipantes > 0
                        ? Math.round((conteo / totalParticipantes) * 100)
                        : 0;

                    html += `
                        <div class="flavor-encuesta-resultados__bar">
                            <div class="flavor-encuesta-resultados__bar-header">
                                <span class="flavor-encuesta-resultados__bar-label">${this.escapeHtml(opcion)}</span>
                                <span class="flavor-encuesta-resultados__bar-stats">${conteo} (${porcentaje}%)</span>
                            </div>
                            <div class="flavor-encuesta-resultados__bar-track">
                                <div class="flavor-encuesta-resultados__bar-fill" style="width: 0" data-porcentaje="${porcentaje}"></div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
            }

            html += '</div>';
            return html;
        }

        /**
         * Crea nueva encuesta
         */
        handleCreateSurvey(evento) {
            evento.preventDefault();

            const $form = $(evento.currentTarget);
            const $submitBtn = $form.find('.flavor-encuesta-crear__submit');

            // Recopilar datos
            const titulo = $form.find('[name="titulo"]').val();
            const descripcion = $form.find('[name="descripcion"]').val();
            const contextoTipo = $form.find('[name="contexto_tipo"]').val();
            const contextoId = $form.find('[name="contexto_id"]').val();
            const permiteMultiples = $form.find('[name="permite_multiples"]').is(':checked');
            const esAnonima = $form.find('[name="es_anonima"]').is(':checked');
            const fechaCierre = $form.find('[name="fecha_cierre"]').val();

            // Recopilar opciones
            const opciones = [];
            $form.find('[name="opciones[]"]').each(function() {
                const valor = $(this).val().trim();
                if (valor) {
                    opciones.push(valor);
                }
            });

            if (opciones.length < 2) {
                alert('Debes añadir al menos 2 opciones');
                return;
            }

            // Preparar campo
            const campos = [{
                tipo: permiteMultiples ? 'seleccion_multiple' : 'seleccion_unica',
                etiqueta: titulo,
                opciones: opciones,
                es_requerido: true,
                orden: 0
            }];

            // Estado de carga
            $submitBtn.prop('disabled', true).text(config.strings.enviando);

            // Enviar
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'encuestas_crear',
                    nonce: config.nonce,
                    titulo: titulo,
                    descripcion: descripcion,
                    contexto_tipo: contextoTipo,
                    contexto_id: contextoId,
                    permite_multiples: permiteMultiples ? 1 : 0,
                    es_anonima: esAnonima ? 1 : 0,
                    fecha_cierre: fechaCierre,
                    campos: campos
                },
                success: (response) => {
                    if (response.success) {
                        // Redirigir o mostrar mensaje
                        $form.before(`
                            <div class="flavor-encuesta__notice flavor-encuesta__notice--success">
                                ${response.data.message}
                            </div>
                        `);
                        $form.slideUp();

                        // Disparar evento
                        $(document).trigger('flavorEncuestaCreada', [response.data.id]);
                    } else {
                        alert(response.data.message || config.strings.error);
                    }
                },
                error: () => {
                    alert(config.strings.error);
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text('Crear encuesta');
                }
            });
        }

        /**
         * Agrega opción en formulario de creación
         */
        handleAddOption(evento) {
            evento.preventDefault();

            const $container = $('#encuesta-opciones');
            const numOpciones = $container.find('.flavor-encuesta-crear__opcion').length;

            if (numOpciones >= 10) {
                alert('Máximo 10 opciones permitidas');
                return;
            }

            $container.append(`
                <div class="flavor-encuesta-crear__opcion">
                    <input type="text"
                           name="opciones[]"
                           class="flavor-encuesta-crear__input"
                           placeholder="Opción ${numOpciones + 1}">
                    <button type="button" class="flavor-encuesta-crear__remove-opcion" aria-label="Eliminar">×</button>
                </div>
            `);

            // Enfocar nuevo campo
            $container.find('.flavor-encuesta-crear__opcion:last input').focus();
        }

        /**
         * Elimina opción en formulario de creación
         */
        handleRemoveOption(evento) {
            evento.preventDefault();

            const $container = $('#encuesta-opciones');
            const numOpciones = $container.find('.flavor-encuesta-crear__opcion').length;

            if (numOpciones <= 2) {
                alert('Debes tener al menos 2 opciones');
                return;
            }

            $(evento.currentTarget).closest('.flavor-encuesta-crear__opcion').remove();
        }

        /**
         * Votación rápida en versión mini
         */
        handleQuickVote(evento) {
            evento.preventDefault();

            const $boton = $(evento.currentTarget);
            const $mini = $boton.closest('.flavor-encuesta-mini');
            const encuestaId = $mini.data('encuesta-id');
            const campoId = $boton.data('campo-id');
            const opcion = $boton.data('opcion');

            // Deshabilitar botones
            $mini.find('.flavor-encuesta-mini__opcion').prop('disabled', true);

            // Enviar voto
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'encuestas_responder',
                    nonce: config.nonce,
                    encuesta_id: encuestaId,
                    respuestas: {
                        [campoId]: opcion
                    }
                },
                success: (response) => {
                    if (response.success) {
                        // Reemplazar con resultados
                        if (response.data.resultados) {
                            this.renderMiniResults($mini, response.data.resultados);
                        }
                    } else {
                        alert(response.data.message || config.strings.error);
                        $mini.find('.flavor-encuesta-mini__opcion').prop('disabled', false);
                    }
                },
                error: () => {
                    alert(config.strings.error);
                    $mini.find('.flavor-encuesta-mini__opcion').prop('disabled', false);
                }
            });
        }

        /**
         * Renderiza resultados en versión mini
         */
        renderMiniResults($mini, resultados) {
            if (!resultados || !resultados.campos || !resultados.campos[0]) return;

            const campo = resultados.campos[0];
            const total = resultados.total_participantes;

            let html = '<div class="flavor-encuesta-mini__bars">';

            if (campo.opciones && campo.conteos) {
                campo.opciones.forEach((opcion, indice) => {
                    const conteo = campo.conteos[indice] || 0;
                    const porcentaje = total > 0 ? Math.round((conteo / total) * 100) : 0;

                    html += `
                        <div class="flavor-encuesta-mini__bar-item">
                            <div class="flavor-encuesta-mini__bar-label">
                                <span>${this.escapeHtml(opcion)}</span>
                                <span>${porcentaje}%</span>
                            </div>
                            <div class="flavor-encuesta-mini__bar-bg">
                                <div class="flavor-encuesta-mini__bar-fill" style="width: ${porcentaje}%"></div>
                            </div>
                        </div>
                    `;
                });
            }

            html += '</div>';

            $mini.find('.flavor-encuesta-mini__opciones').replaceWith(html);
            $mini.find('.flavor-encuesta-mini__meta').text(`${total} votos`);
        }

        /**
         * Escapa HTML
         */
        escapeHtml(texto) {
            const div = document.createElement('div');
            div.textContent = texto;
            return div.innerHTML;
        }
    }

    /**
     * API pública para integración con otros módulos
     */
    window.FlavorEncuestasAPI = {
        /**
         * Crea una encuesta programáticamente
         */
        crear: function(datos) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: config.restUrl,
                    type: 'POST',
                    headers: {
                        'X-WP-Nonce': config.restNonce
                    },
                    contentType: 'application/json',
                    data: JSON.stringify(datos),
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.message);
                        }
                    },
                    error: (xhr) => {
                        reject(xhr.responseJSON?.message || 'Error');
                    }
                });
            });
        },

        /**
         * Obtiene una encuesta
         */
        obtener: function(encuestaId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${config.restUrl}/${encuestaId}`,
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': config.restNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.message);
                        }
                    },
                    error: (xhr) => {
                        reject(xhr.responseJSON?.message || 'Error');
                    }
                });
            });
        },

        /**
         * Envía respuesta
         */
        responder: function(encuestaId, respuestas) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${config.restUrl}/${encuestaId}/responder`,
                    type: 'POST',
                    headers: {
                        'X-WP-Nonce': config.restNonce
                    },
                    contentType: 'application/json',
                    data: JSON.stringify({ respuestas }),
                    success: (response) => {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response.message);
                        }
                    },
                    error: (xhr) => {
                        reject(xhr.responseJSON?.message || 'Error');
                    }
                });
            });
        },

        /**
         * Obtiene resultados
         */
        resultados: function(encuestaId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${config.restUrl}/${encuestaId}/resultados`,
                    type: 'GET',
                    headers: {
                        'X-WP-Nonce': config.restNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.message);
                        }
                    },
                    error: (xhr) => {
                        reject(xhr.responseJSON?.message || 'Error');
                    }
                });
            });
        },

        /**
         * Lista encuestas por contexto
         */
        listarPorContexto: function(contextoTipo, contextoId, opciones = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${config.restUrl}/contexto/${contextoTipo}/${contextoId}`,
                    type: 'GET',
                    data: opciones,
                    headers: {
                        'X-WP-Nonce': config.restNonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response.message);
                        }
                    },
                    error: (xhr) => {
                        reject(xhr.responseJSON?.message || 'Error');
                    }
                });
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        new FlavorEncuestas();
    });

})(jQuery);
