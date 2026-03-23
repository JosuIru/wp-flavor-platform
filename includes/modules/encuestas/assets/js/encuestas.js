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
            this.contextSearchTimer = null;
            this.contextSearchXhr = null;
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

            // Constructor de preguntas (creación)
            $(document).on('click', '#agregar-pregunta', this.handleAddQuestion.bind(this));
            $(document).on('click', '.flavor-encuesta-crear__pregunta-remove', this.handleRemoveQuestion.bind(this));
            $(document).on('change', '.flavor-encuesta-crear__pregunta-tipo', this.handleQuestionTypeChange.bind(this));
            $(document).on('click', '.flavor-encuesta-crear__pregunta-add-opcion', this.handleAddQuestionOption.bind(this));
            $(document).on('click', '.flavor-encuesta-crear__pregunta-remove-opcion', this.handleRemoveQuestionOption.bind(this));

            // Votación rápida en versión mini
            $(document).on('click', '.flavor-encuesta-mini__opcion', this.handleQuickVote.bind(this));

            // Interacción con estrellas
            $(document).on('click', '.flavor-encuesta__star', this.handleStarClick.bind(this));
            $(document).on('mouseover', '.flavor-encuesta__star', this.handleStarHover.bind(this));
            $(document).on('mouseout', '.flavor-encuesta__stars', this.handleStarsMouseout.bind(this));

            // Vinculación de contexto
            $(document).on('change', '#encuesta-contexto-tipo', this.handleContextTypeChange.bind(this));
            $(document).on('input', '#encuesta-contexto-buscar', this.handleContextSearchInput.bind(this));
            $(document).on('click', '.flavor-encuesta-crear__context-result', this.handleContextResultClick.bind(this));
            $(document).on('click', '#encuesta-contexto-seleccion-clear', this.handleContextSelectionClear.bind(this));
            $(document).on('input', '#encuesta-contexto-id', this.handleManualContextIdInput.bind(this));

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

            this.initializeQuestionBuilder();
            this.handleContextTypeChange();
        }

        /**
         * Ajusta visibilidad/validación del contexto destino.
         */
        handleContextTypeChange() {
            const $tipo = $('#encuesta-contexto-tipo');
            const $idWrap = $('#encuesta-contexto-id-wrap');
            const $searchWrap = $('#encuesta-contexto-search-wrap');
            const $input = $('#encuesta-contexto-id');
            const $search = $('#encuesta-contexto-buscar');
            const $results = $('#encuesta-contexto-resultados');
            const $selection = $('#encuesta-contexto-seleccion');
            if (!$tipo.length || !$idWrap.length || !$input.length) return;

            const tipo = $tipo.val() || 'general';
            const necesitaId = tipo !== 'general';

            $idWrap.toggle(necesitaId);
            $searchWrap.toggle(necesitaId);
            $input.prop('required', necesitaId);
            if (!necesitaId) {
                $input.val('0');
                $search.val('');
                $results.hide().empty();
                $selection.hide();
            } else if ($input.val() && $input.val() !== '0') {
                this.showContextSelection(`ID #${$input.val()}`);
            }
        }

        handleContextSearchInput(evento) {
            const $input = $(evento.currentTarget);
            const query = ($input.val() || '').trim();
            const $contextId = $('#encuesta-contexto-id');

            if (query === '') {
                $('#encuesta-contexto-resultados').hide().empty();
                return;
            }

            if (query.length < 2) {
                return;
            }

            // Si empieza una nueva búsqueda manual, invalidamos selección previa.
            if ($contextId.val() && $('#encuesta-contexto-seleccion').is(':visible')) {
                $contextId.val('0');
                $('#encuesta-contexto-seleccion').hide();
            }

            if (this.contextSearchTimer) {
                clearTimeout(this.contextSearchTimer);
            }

            this.contextSearchTimer = setTimeout(() => {
                this.searchContextTargets(query);
            }, 260);
        }

        searchContextTargets(query) {
            const contextoTipo = ($('#encuesta-contexto-tipo').val() || 'general');
            const $results = $('#encuesta-contexto-resultados');
            if (contextoTipo === 'general') {
                $results.hide().empty();
                return;
            }

            if (this.contextSearchXhr && this.contextSearchXhr.readyState !== 4) {
                this.contextSearchXhr.abort();
            }

            $results
                .show()
                .html(`<div class="flavor-encuesta-crear__context-status">${config.strings.buscando || 'Buscando...'}</div>`);

            this.contextSearchXhr = $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'encuestas_buscar_contexto',
                    nonce: config.nonce,
                    contexto_tipo: contextoTipo,
                    q: query,
                    limit: 8
                },
                success: (response) => {
                    const items = response && response.success && response.data && Array.isArray(response.data.items)
                        ? response.data.items
                        : [];
                    this.renderContextResults(items);
                },
                error: () => {
                    $results
                        .show()
                        .html(`<div class="flavor-encuesta-crear__context-status">${config.strings.error || 'Error'}</div>`);
                }
            });
        }

        renderContextResults(items) {
            const $results = $('#encuesta-contexto-resultados');
            if (!items.length) {
                $results
                    .show()
                    .html(`<div class="flavor-encuesta-crear__context-status">${config.strings.sinResultados || 'Sin resultados'}</div>`);
                return;
            }

            let html = '';
            items.forEach((item) => {
                const typeLabel = this.escapeHtml(item.type_label || '');
                const statusLabel = this.escapeHtml(item.status_label || '');
                const badges = [typeLabel, statusLabel].filter(Boolean).map((txt) =>
                    `<span class="flavor-encuesta-crear__context-badge">${txt}</span>`
                ).join('');

                html += `
                    <button type="button"
                            class="flavor-encuesta-crear__context-result"
                            data-id="${parseInt(item.id, 10) || 0}">
                        <strong>${this.escapeHtml(item.label || '')}</strong>
                        ${badges ? `<div class="flavor-encuesta-crear__context-badges">${badges}</div>` : ''}
                        <small>${this.escapeHtml(item.subtitle || '')}</small>
                    </button>
                `;
            });
            $results.show().html(html);
        }

        handleContextResultClick(evento) {
            evento.preventDefault();

            const $btn = $(evento.currentTarget);
            const id = parseInt($btn.data('id'), 10) || 0;
            const label = ($btn.find('strong').first().text() || '').trim();
            if (!id) return;

            $('#encuesta-contexto-id').val(id);
            $('#encuesta-contexto-buscar').val(label);
            $('#encuesta-contexto-resultados').hide().empty();
            this.showContextSelection(`${label} (ID ${id})`);
        }

        handleContextSelectionClear(evento) {
            evento.preventDefault();
            $('#encuesta-contexto-id').val('0');
            $('#encuesta-contexto-buscar').val('').focus();
            $('#encuesta-contexto-resultados').hide().empty();
            $('#encuesta-contexto-seleccion').hide();
        }

        handleManualContextIdInput() {
            const value = parseInt($('#encuesta-contexto-id').val(), 10) || 0;
            if (value > 0) {
                this.showContextSelection(`ID #${value}`);
            } else {
                $('#encuesta-contexto-seleccion').hide();
            }
        }

        showContextSelection(texto) {
            $('#encuesta-contexto-seleccion-label').text(texto);
            $('#encuesta-contexto-seleccion').show();
        }

        /**
         * Inicializa constructor de preguntas
         */
        initializeQuestionBuilder() {
            const $container = $('#encuesta-preguntas');
            if (!$container.length) return;
            this.refreshQuestionUi();
        }

        refreshQuestionUi() {
            const $questions = $('#encuesta-preguntas .flavor-encuesta-crear__pregunta');
            $questions.each((idx, el) => {
                const $q = $(el);
                $q.attr('data-index', idx);
                $q.find('.flavor-encuesta-crear__pregunta-head strong').text(`Pregunta ${idx + 1}`);
                $q.find('[name^="campo_opciones_"]').attr('name', `campo_opciones_${idx}[]`);

                const $removeBtn = $q.find('.flavor-encuesta-crear__pregunta-remove');
                $removeBtn.toggle($questions.length > 1);

                this.updateQuestionTypeUi($q);
            });
        }

        updateQuestionTypeUi($question) {
            const tipo = $question.find('.flavor-encuesta-crear__pregunta-tipo').val();
            const showOptions = ['seleccion_unica', 'seleccion_multiple'].includes(tipo);
            const showRange = tipo === 'rango';

            const $optionsBox = $question.find('.flavor-encuesta-crear__pregunta-opciones');
            const $optionsInputs = $optionsBox.find('input[name^="campo_opciones_"]');
            $optionsBox.toggle(showOptions);
            $optionsInputs.prop('required', showOptions);
            $optionsInputs.prop('disabled', !showOptions);

            const $rangeBox = $question.find('.flavor-encuesta-crear__pregunta-range');
            const $rangeInputs = $rangeBox.find('input');
            $rangeBox.toggle(showRange);
            $rangeInputs.prop('disabled', !showRange);
        }

        createQuestionHtml(index) {
            return `
                <div class="flavor-encuesta-crear__pregunta" data-index="${index}">
                    <div class="flavor-encuesta-crear__pregunta-head">
                        <strong>Pregunta ${index + 1}</strong>
                        <button type="button" class="flavor-encuesta-crear__pregunta-remove" aria-label="Eliminar pregunta">×</button>
                    </div>

                    <input type="text" name="campo_etiqueta[]" class="flavor-encuesta-crear__input" placeholder="Escribe la pregunta" required>

                    <select name="campo_tipo[]" class="flavor-encuesta-crear__input flavor-encuesta-crear__pregunta-tipo">
                        <option value="seleccion_unica">Selección única</option>
                        <option value="seleccion_multiple">Selección múltiple</option>
                        <option value="texto">Texto corto</option>
                        <option value="textarea">Texto largo</option>
                        <option value="email">Email</option>
                        <option value="telefono">Teléfono</option>
                        <option value="url">URL</option>
                        <option value="numero">Número</option>
                        <option value="rango">Rango (slider)</option>
                        <option value="escala">Escala (1-10)</option>
                        <option value="nps">NPS (0-10)</option>
                        <option value="estrellas">Estrellas (1-5)</option>
                        <option value="si_no">Sí/No</option>
                        <option value="fecha">Fecha</option>
                        <option value="fecha_hora">Fecha y hora</option>
                    </select>

                    <div class="flavor-encuesta-crear__pregunta-opciones">
                        <div class="flavor-encuesta-crear__opciones">
                            <div class="flavor-encuesta-crear__opcion">
                                <input type="text" class="flavor-encuesta-crear__input" name="campo_opciones_${index}[]" placeholder="Opción 1" required>
                                <button type="button" class="flavor-encuesta-crear__pregunta-remove-opcion" aria-label="Eliminar">×</button>
                            </div>
                            <div class="flavor-encuesta-crear__opcion">
                                <input type="text" class="flavor-encuesta-crear__input" name="campo_opciones_${index}[]" placeholder="Opción 2" required>
                                <button type="button" class="flavor-encuesta-crear__pregunta-remove-opcion" aria-label="Eliminar">×</button>
                            </div>
                        </div>
                        <button type="button" class="flavor-encuesta-crear__add-opcion flavor-encuesta-crear__pregunta-add-opcion">+ Añadir opción</button>
                    </div>

                    <div class="flavor-encuesta-crear__pregunta-range" style="display:none;">
                        <div class="flavor-encuesta-crear__range-config">
                            <input type="number" name="campo_config_min[]" class="flavor-encuesta-crear__input" placeholder="Mínimo" value="1" disabled>
                            <input type="number" name="campo_config_max[]" class="flavor-encuesta-crear__input" placeholder="Máximo" value="10" disabled>
                            <input type="number" step="0.1" name="campo_config_step[]" class="flavor-encuesta-crear__input" placeholder="Paso" value="1" disabled>
                        </div>
                    </div>
                </div>
            `;
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

            // Recopilar preguntas/campos
            const campos = [];
            let preguntasValidas = true;

            $form.find('.flavor-encuesta-crear__pregunta').each((index, el) => {
                const $q = $(el);
                const etiqueta = ($q.find('[name="campo_etiqueta[]"]').val() || '').trim();
                const tipo = $q.find('[name="campo_tipo[]"]').val() || 'seleccion_unica';

                if (!etiqueta) {
                    preguntasValidas = false;
                    return;
                }

                const campo = {
                    tipo: tipo,
                    etiqueta: etiqueta,
                    es_requerido: true,
                    orden: index
                };

                if (['seleccion_unica', 'seleccion_multiple'].includes(tipo)) {
                    const opciones = [];
                    $q.find('[name^="campo_opciones_"]').each(function() {
                        const valor = ($(this).val() || '').trim();
                        if (valor) opciones.push(valor);
                    });

                    if (opciones.length < 2) {
                        preguntasValidas = false;
                        return;
                    }
                    campo.opciones = opciones;
                }

                if (tipo === 'rango') {
                    const min = parseFloat($q.find('[name="campo_config_min[]"]').val() || '1');
                    const max = parseFloat($q.find('[name="campo_config_max[]"]').val() || '10');
                    const step = parseFloat($q.find('[name="campo_config_step[]"]').val() || '1');
                    campo.configuracion = {
                        min: isNaN(min) ? 1 : min,
                        max: isNaN(max) ? 10 : max,
                        step: isNaN(step) ? 1 : step,
                        default: isNaN(min) ? 1 : min
                    };
                }

                campos.push(campo);
            });

            if (!preguntasValidas || campos.length === 0) {
                alert('Revisa las preguntas: título obligatorio y al menos 2 opciones en preguntas de selección');
                return;
            }

            if (contextoTipo !== 'general' && (!contextoId || parseInt(contextoId, 10) <= 0)) {
                alert(config.strings.contextoObligatorio || 'Debes seleccionar un destino para el contexto elegido');
                return;
            }

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
         * Añade una pregunta al constructor
         */
        handleAddQuestion(evento) {
            evento.preventDefault();
            const $container = $('#encuesta-preguntas');
            const index = $container.find('.flavor-encuesta-crear__pregunta').length;
            $container.append(this.createQuestionHtml(index));
            this.refreshQuestionUi();
            $container.find('.flavor-encuesta-crear__pregunta:last [name="campo_etiqueta[]"]').focus();
        }

        /**
         * Elimina una pregunta del constructor
         */
        handleRemoveQuestion(evento) {
            evento.preventDefault();
            const $container = $('#encuesta-preguntas');
            const $questions = $container.find('.flavor-encuesta-crear__pregunta');
            if ($questions.length <= 1) {
                alert('Debe existir al menos una pregunta');
                return;
            }

            $(evento.currentTarget).closest('.flavor-encuesta-crear__pregunta').remove();
            this.refreshQuestionUi();
        }

        /**
         * Cambio de tipo en una pregunta
         */
        handleQuestionTypeChange(evento) {
            const $question = $(evento.currentTarget).closest('.flavor-encuesta-crear__pregunta');
            this.updateQuestionTypeUi($question);
        }

        /**
         * Añade opción dentro de una pregunta
         */
        handleAddQuestionOption(evento) {
            evento.preventDefault();
            const $question = $(evento.currentTarget).closest('.flavor-encuesta-crear__pregunta');
            const index = parseInt($question.attr('data-index'), 10) || 0;
            const $options = $question.find('.flavor-encuesta-crear__opciones');
            const count = $options.find('.flavor-encuesta-crear__opcion').length;

            if (count >= 10) {
                alert('Máximo 10 opciones por pregunta');
                return;
            }

            $options.append(`
                <div class="flavor-encuesta-crear__opcion">
                    <input type="text" class="flavor-encuesta-crear__input" name="campo_opciones_${index}[]" placeholder="Opción ${count + 1}" required>
                    <button type="button" class="flavor-encuesta-crear__pregunta-remove-opcion" aria-label="Eliminar">×</button>
                </div>
            `);
            $options.find('.flavor-encuesta-crear__opcion:last input').focus();
        }

        /**
         * Elimina opción dentro de una pregunta
         */
        handleRemoveQuestionOption(evento) {
            evento.preventDefault();
            const $question = $(evento.currentTarget).closest('.flavor-encuesta-crear__pregunta');
            const $options = $question.find('.flavor-encuesta-crear__opciones');
            const count = $options.find('.flavor-encuesta-crear__opcion').length;

            if (count <= 2) {
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
