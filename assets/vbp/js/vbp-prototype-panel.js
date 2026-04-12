/**
 * VBP Prototype Panel - Panel de edicion de interacciones
 *
 * Panel lateral para gestionar las interacciones de un elemento,
 * incluyendo triggers, acciones, transiciones y configuracion.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * VBP Prototype Panel - Objeto principal
     */
    window.VBPPrototypePanel = {
        /**
         * Estado del panel
         */
        isOpen: false,
        selectedInteractionId: null,
        isCreatingInteraction: false,
        dragConnectionSource: null,

        /**
         * Referencia al modo prototipo
         */
        get prototypeMode() {
            return window.VBPPrototypeMode;
        },

        /**
         * Elemento seleccionado actual
         */
        get selectedElementId() {
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                var selection = Alpine.store('vbp').selection;
                if (selection && selection.elementIds && selection.elementIds.length === 1) {
                    return selection.elementIds[0];
                }
            }
            return null;
        },

        /**
         * Interacciones del elemento seleccionado
         */
        get interactions() {
            if (!this.selectedElementId || !this.prototypeMode) return [];
            return this.prototypeMode.getInteractions(this.selectedElementId);
        },

        /**
         * Tipos de trigger disponibles
         */
        get triggerTypes() {
            return this.prototypeMode ? this.prototypeMode.triggerTypes : {};
        },

        /**
         * Tipos de accion disponibles
         */
        get actionTypes() {
            return this.prototypeMode ? this.prototypeMode.actionTypes : {};
        },

        /**
         * Tipos de transicion disponibles
         */
        get transitionTypes() {
            return this.prototypeMode ? this.prototypeMode.transitionTypes : {};
        },

        /**
         * Inicializa el panel
         */
        init: function() {
            this.bindEvents();
            this.createPanelStructure();
            console.log('[VBP Prototype Panel] Initialized');
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            var self = this;

            // Escuchar cambios en el modo prototipo
            document.addEventListener('vbp:prototypeModeChanged', function(event) {
                if (event.detail.enabled) {
                    self.show();
                } else {
                    self.hide();
                }
            });

            // Escuchar cambios en la seleccion
            document.addEventListener('alpine:init', function() {
                if (window.Alpine && Alpine.effect) {
                    Alpine.effect(function() {
                        var store = Alpine.store('vbp');
                        if (store && store.selection) {
                            self.onSelectionChanged();
                        }
                    });
                }
            });

            // Escuchar solicitud de apertura del panel
            document.addEventListener('vbp:open-prototype-panel', function() {
                self.show();
            });
        },

        /**
         * Crea la estructura HTML del panel
         */
        createPanelStructure: function() {
            // El panel se agrega dinámicamente cuando se abre el modo prototipo
        },

        /**
         * Muestra el panel
         */
        show: function() {
            this.isOpen = true;
            this.renderPanel();
        },

        /**
         * Oculta el panel
         */
        hide: function() {
            this.isOpen = false;
            var panelElement = document.getElementById('vbp-prototype-panel');
            if (panelElement) {
                panelElement.classList.remove('vbp-prototype-panel--visible');
            }
        },

        /**
         * Callback cuando cambia la seleccion
         */
        onSelectionChanged: function() {
            if (this.isOpen) {
                this.renderPanel();
            }
        },

        /**
         * Renderiza el panel completo
         */
        renderPanel: function() {
            var existingPanel = document.getElementById('vbp-prototype-panel');

            if (!existingPanel) {
                existingPanel = this.createPanelElement();
            }

            this.updatePanelContent(existingPanel);
            existingPanel.classList.add('vbp-prototype-panel--visible');
        },

        /**
         * Crea el elemento del panel
         */
        createPanelElement: function() {
            var panelElement = document.createElement('div');
            panelElement.id = 'vbp-prototype-panel';
            panelElement.className = 'vbp-prototype-panel';

            // Insertar en el inspector sidebar
            var inspectorElement = document.querySelector('.vbp-inspector');
            if (inspectorElement) {
                inspectorElement.appendChild(panelElement);
            } else {
                document.body.appendChild(panelElement);
            }

            return panelElement;
        },

        /**
         * Actualiza el contenido del panel
         */
        updatePanelContent: function(panelElement) {
            var self = this;
            var interactions = this.interactions;
            var hasElement = !!this.selectedElementId;

            var contentHtml = [
                '<div class="vbp-prototype-panel__header">',
                '  <div class="vbp-prototype-panel__title">',
                '    <span class="vbp-prototype-panel__icon">🔗</span>',
                '    <span>Interacciones</span>',
                '  </div>',
                '  <div class="vbp-prototype-panel__actions">',
                '    <button class="vbp-prototype-panel__preview-btn" title="Preview">',
                '      <span>▶️</span>',
                '    </button>',
                '  </div>',
                '</div>',
                '<div class="vbp-prototype-panel__content">'
            ];

            if (!hasElement) {
                contentHtml.push(
                    '  <div class="vbp-prototype-panel__empty">',
                    '    <div class="vbp-prototype-panel__empty-icon">🎯</div>',
                    '    <div class="vbp-prototype-panel__empty-text">',
                    '      Selecciona un elemento para agregar interacciones',
                    '    </div>',
                    '  </div>'
                );
            } else {
                contentHtml.push(
                    '  <div class="vbp-prototype-panel__element-info">',
                    '    <span class="vbp-prototype-panel__element-id">' + this.selectedElementId + '</span>',
                    '  </div>',
                    '  <div class="vbp-prototype-panel__interactions-list">'
                );

                if (interactions.length === 0) {
                    contentHtml.push(
                        '    <div class="vbp-prototype-panel__no-interactions">',
                        '      Sin interacciones',
                        '    </div>'
                    );
                } else {
                    interactions.forEach(function(interaction, interactionIndex) {
                        contentHtml.push(self.renderInteractionItem(interaction, interactionIndex));
                    });
                }

                contentHtml.push(
                    '  </div>',
                    '  <button class="vbp-prototype-panel__add-btn">',
                    '    <span>+</span> Agregar interaccion',
                    '  </button>'
                );
            }

            contentHtml.push('</div>');

            panelElement.innerHTML = contentHtml.join('\n');

            // Vincular eventos
            this.bindPanelEvents(panelElement);
        },

        /**
         * Renderiza un item de interaccion
         */
        renderInteractionItem: function(interaction, itemIndex) {
            var triggerInfo = this.triggerTypes[interaction.trigger] || { label: interaction.trigger, icon: '❓' };
            var actionInfo = this.actionTypes[interaction.action] || { label: interaction.action, icon: '❓' };
            var transitionInfo = this.transitionTypes[interaction.animation] || { label: interaction.animation, icon: '➡️' };

            var targetLabel = interaction.target || '';
            if (interaction.action === 'open_url') {
                targetLabel = interaction.url || 'URL';
            } else if (interaction.action === 'set_variable') {
                targetLabel = interaction.variable + ' = ' + interaction.variableValue;
            }

            return [
                '<div class="vbp-interaction-item" data-interaction-id="' + interaction.id + '" data-index="' + itemIndex + '">',
                '  <div class="vbp-interaction-item__header">',
                '    <div class="vbp-interaction-item__trigger">',
                '      <span class="vbp-interaction-item__icon">' + triggerInfo.icon + '</span>',
                '      <span>' + triggerInfo.label + '</span>',
                '    </div>',
                '    <div class="vbp-interaction-item__actions">',
                '      <button class="vbp-interaction-item__edit" title="Editar">✏️</button>',
                '      <button class="vbp-interaction-item__delete" title="Eliminar">🗑️</button>',
                '    </div>',
                '  </div>',
                '  <div class="vbp-interaction-item__body">',
                '    <div class="vbp-interaction-item__action">',
                '      <span class="vbp-interaction-item__icon">' + actionInfo.icon + '</span>',
                '      <span>' + actionInfo.label + '</span>',
                '    </div>',
                '    <div class="vbp-interaction-item__arrow">→</div>',
                '    <div class="vbp-interaction-item__target">',
                '      <span>' + targetLabel + '</span>',
                '    </div>',
                '  </div>',
                '  <div class="vbp-interaction-item__footer">',
                '    <span class="vbp-interaction-item__transition">',
                '      ' + transitionInfo.icon + ' ' + transitionInfo.label,
                '    </span>',
                '    <span class="vbp-interaction-item__duration">' + interaction.duration + 'ms</span>',
                '  </div>',
                '</div>'
            ].join('\n');
        },

        /**
         * Vincula eventos del panel
         */
        bindPanelEvents: function(panelElement) {
            var self = this;

            // Boton agregar
            var addButton = panelElement.querySelector('.vbp-prototype-panel__add-btn');
            if (addButton) {
                addButton.addEventListener('click', function() {
                    self.openInteractionEditor();
                });
            }

            // Boton preview
            var previewButton = panelElement.querySelector('.vbp-prototype-panel__preview-btn');
            if (previewButton) {
                previewButton.addEventListener('click', function() {
                    if (self.prototypeMode) {
                        self.prototypeMode.startPreview();
                    }
                });
            }

            // Items de interaccion
            var interactionItems = panelElement.querySelectorAll('.vbp-interaction-item');
            interactionItems.forEach(function(itemElement) {
                var interactionId = itemElement.getAttribute('data-interaction-id');

                // Editar
                var editButton = itemElement.querySelector('.vbp-interaction-item__edit');
                if (editButton) {
                    editButton.addEventListener('click', function(clickEvent) {
                        clickEvent.stopPropagation();
                        self.openInteractionEditor(interactionId);
                    });
                }

                // Eliminar
                var deleteButton = itemElement.querySelector('.vbp-interaction-item__delete');
                if (deleteButton) {
                    deleteButton.addEventListener('click', function(clickEvent) {
                        clickEvent.stopPropagation();
                        self.deleteInteraction(interactionId);
                    });
                }

                // Click en item para expandir
                itemElement.addEventListener('click', function() {
                    self.toggleInteractionExpanded(interactionId);
                });
            });
        },

        /**
         * Abre el editor de interaccion
         */
        openInteractionEditor: function(interactionId) {
            this.selectedInteractionId = interactionId || null;
            this.isCreatingInteraction = !interactionId;
            this.showEditorModal();
        },

        /**
         * Muestra el modal del editor de interaccion
         */
        showEditorModal: function() {
            var self = this;
            var existingModal = document.getElementById('vbp-interaction-editor-modal');

            if (existingModal) {
                existingModal.remove();
            }

            var interaction = null;
            if (this.selectedInteractionId) {
                interaction = this.interactions.find(function(interactionItem) {
                    return interactionItem.id === self.selectedInteractionId;
                });
            }

            var modalElement = document.createElement('div');
            modalElement.id = 'vbp-interaction-editor-modal';
            modalElement.className = 'vbp-modal vbp-interaction-editor';

            modalElement.innerHTML = this.generateEditorHTML(interaction);

            document.body.appendChild(modalElement);

            // Mostrar con animacion
            requestAnimationFrame(function() {
                modalElement.classList.add('vbp-modal--visible');
            });

            // Vincular eventos del editor
            this.bindEditorEvents(modalElement, interaction);
        },

        /**
         * Genera el HTML del editor
         */
        generateEditorHTML: function(interaction) {
            var self = this;
            var isNew = !interaction;
            var currentTrigger = interaction ? interaction.trigger : 'click';
            var currentAction = interaction ? interaction.action : 'navigate';
            var currentAnimation = interaction ? interaction.animation : 'dissolve';
            var currentDuration = interaction ? interaction.duration : 300;
            var currentDelay = interaction ? interaction.delay : 0;
            var currentTarget = interaction ? interaction.target : '';
            var currentUrl = interaction ? interaction.url : '';
            var currentVariable = interaction ? interaction.variable : '';
            var currentVariableValue = interaction ? interaction.variableValue : '';

            var triggerOptionsHtml = Object.keys(this.triggerTypes).map(function(triggerId) {
                var triggerData = self.triggerTypes[triggerId];
                var selected = triggerId === currentTrigger ? 'selected' : '';
                return '<option value="' + triggerId + '" ' + selected + '>' + triggerData.icon + ' ' + triggerData.label + '</option>';
            }).join('');

            var actionOptionsHtml = Object.keys(this.actionTypes).map(function(actionId) {
                var actionData = self.actionTypes[actionId];
                var selected = actionId === currentAction ? 'selected' : '';
                return '<option value="' + actionId + '" ' + selected + '>' + actionData.icon + ' ' + actionData.label + '</option>';
            }).join('');

            var animationOptionsHtml = Object.keys(this.transitionTypes).map(function(animationId) {
                var animationData = self.transitionTypes[animationId];
                var selected = animationId === currentAnimation ? 'selected' : '';
                return '<option value="' + animationId + '" ' + selected + '>' + animationData.icon + ' ' + animationData.label + '</option>';
            }).join('');

            // Obtener elementos disponibles como targets
            var targetOptionsHtml = this.getTargetOptions(currentTarget);

            return [
                '<div class="vbp-modal__backdrop"></div>',
                '<div class="vbp-modal__content">',
                '  <div class="vbp-modal__header">',
                '    <h3>' + (isNew ? 'Nueva Interaccion' : 'Editar Interaccion') + '</h3>',
                '    <button class="vbp-modal__close">✖️</button>',
                '  </div>',
                '  <div class="vbp-modal__body">',
                '    <div class="vbp-interaction-editor__section">',
                '      <h4>Trigger (Cuando)</h4>',
                '      <div class="vbp-interaction-editor__field">',
                '        <label>Tipo de trigger</label>',
                '        <select id="interaction-trigger" class="vbp-select">',
                triggerOptionsHtml,
                '        </select>',
                '      </div>',
                '      <div class="vbp-interaction-editor__field vbp-interaction-editor__delay-field">',
                '        <label>Delay (ms)</label>',
                '        <input type="number" id="interaction-delay" class="vbp-input" value="' + currentDelay + '" min="0" step="50">',
                '      </div>',
                '    </div>',
                '',
                '    <div class="vbp-interaction-editor__section">',
                '      <h4>Accion (Que)</h4>',
                '      <div class="vbp-interaction-editor__field">',
                '        <label>Tipo de accion</label>',
                '        <select id="interaction-action" class="vbp-select">',
                actionOptionsHtml,
                '        </select>',
                '      </div>',
                '',
                '      <div class="vbp-interaction-editor__field vbp-interaction-editor__target-field" data-for-action="navigate,overlay">',
                '        <label>Destino</label>',
                '        <select id="interaction-target" class="vbp-select">',
                '          <option value="">Selecciona un elemento...</option>',
                targetOptionsHtml,
                '        </select>',
                '        <button class="vbp-interaction-editor__pick-btn" title="Seleccionar en canvas">🎯</button>',
                '      </div>',
                '',
                '      <div class="vbp-interaction-editor__field vbp-interaction-editor__url-field" data-for-action="open_url" style="display:none;">',
                '        <label>URL</label>',
                '        <input type="url" id="interaction-url" class="vbp-input" placeholder="https://..." value="' + currentUrl + '">',
                '      </div>',
                '',
                '      <div class="vbp-interaction-editor__field vbp-interaction-editor__variable-field" data-for-action="set_variable" style="display:none;">',
                '        <label>Variable</label>',
                '        <input type="text" id="interaction-variable" class="vbp-input" placeholder="nombreVariable" value="' + currentVariable + '">',
                '      </div>',
                '      <div class="vbp-interaction-editor__field vbp-interaction-editor__variable-value-field" data-for-action="set_variable" style="display:none;">',
                '        <label>Valor</label>',
                '        <input type="text" id="interaction-variable-value" class="vbp-input" placeholder="valor" value="' + currentVariableValue + '">',
                '      </div>',
                '    </div>',
                '',
                '    <div class="vbp-interaction-editor__section">',
                '      <h4>Transicion (Como)</h4>',
                '      <div class="vbp-interaction-editor__field">',
                '        <label>Animacion</label>',
                '        <select id="interaction-animation" class="vbp-select">',
                animationOptionsHtml,
                '        </select>',
                '      </div>',
                '      <div class="vbp-interaction-editor__field">',
                '        <label>Duracion (ms)</label>',
                '        <input type="number" id="interaction-duration" class="vbp-input" value="' + currentDuration + '" min="0" step="50">',
                '      </div>',
                '    </div>',
                '  </div>',
                '  <div class="vbp-modal__footer">',
                '    <button class="vbp-btn vbp-btn--secondary vbp-modal__cancel">Cancelar</button>',
                '    <button class="vbp-btn vbp-btn--primary vbp-modal__save">',
                (isNew ? 'Crear' : 'Guardar'),
                '    </button>',
                '  </div>',
                '</div>'
            ].join('\n');
        },

        /**
         * Obtiene las opciones de target disponibles
         */
        getTargetOptions: function(currentTarget) {
            var optionsHtml = [];

            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                var elements = Alpine.store('vbp').elements || [];

                elements.forEach(function(element) {
                    var selected = element.id === currentTarget ? 'selected' : '';
                    var displayName = element.name || element.type || element.id;
                    optionsHtml.push('<option value="' + element.id + '" ' + selected + '>' + displayName + '</option>');

                    // Incluir hijos si es un contenedor
                    if (element.children && element.children.length > 0) {
                        element.children.forEach(function(childElement) {
                            var childSelected = childElement.id === currentTarget ? 'selected' : '';
                            var childDisplayName = childElement.name || childElement.type || childElement.id;
                            optionsHtml.push('<option value="' + childElement.id + '" ' + childSelected + '>  ↳ ' + childDisplayName + '</option>');
                        });
                    }
                });
            }

            return optionsHtml.join('');
        },

        /**
         * Vincula eventos del editor
         */
        bindEditorEvents: function(modalElement, interaction) {
            var self = this;

            // Cerrar
            var closeButton = modalElement.querySelector('.vbp-modal__close');
            var cancelButton = modalElement.querySelector('.vbp-modal__cancel');
            var backdropElement = modalElement.querySelector('.vbp-modal__backdrop');

            var closeModal = function() {
                modalElement.classList.remove('vbp-modal--visible');
                setTimeout(function() {
                    modalElement.remove();
                }, 200);
            };

            closeButton.addEventListener('click', closeModal);
            cancelButton.addEventListener('click', closeModal);
            backdropElement.addEventListener('click', closeModal);

            // Guardar
            var saveButton = modalElement.querySelector('.vbp-modal__save');
            saveButton.addEventListener('click', function() {
                self.saveInteraction(modalElement, interaction);
                closeModal();
            });

            // Cambio de accion para mostrar/ocultar campos
            var actionSelect = modalElement.querySelector('#interaction-action');
            actionSelect.addEventListener('change', function() {
                self.updateActionFields(modalElement, actionSelect.value);
            });

            // Inicializar campos visibles
            this.updateActionFields(modalElement, actionSelect.value);

            // Boton de seleccionar en canvas
            var pickButton = modalElement.querySelector('.vbp-interaction-editor__pick-btn');
            if (pickButton) {
                pickButton.addEventListener('click', function() {
                    self.startTargetPicking(modalElement);
                });
            }
        },

        /**
         * Actualiza la visibilidad de campos segun la accion
         */
        updateActionFields: function(modalElement, actionType) {
            var allConditionalFields = modalElement.querySelectorAll('[data-for-action]');

            allConditionalFields.forEach(function(fieldElement) {
                var forActions = fieldElement.getAttribute('data-for-action').split(',');
                if (forActions.indexOf(actionType) !== -1) {
                    fieldElement.style.display = '';
                } else {
                    fieldElement.style.display = 'none';
                }
            });
        },

        /**
         * Inicia el modo de seleccion de target en el canvas
         */
        startTargetPicking: function(modalElement) {
            var self = this;

            // Ocultar temporalmente el modal
            modalElement.classList.add('vbp-modal--picking');

            // Agregar modo picking al canvas
            var canvasElement = document.querySelector('.vbp-canvas');
            if (canvasElement) {
                canvasElement.classList.add('vbp-picking-target');
            }

            // Handler para seleccionar elemento
            var pickHandler = function(clickEvent) {
                var targetElement = clickEvent.target.closest('[data-vbp-id]');
                if (targetElement) {
                    var targetId = targetElement.getAttribute('data-vbp-id');
                    var targetSelect = modalElement.querySelector('#interaction-target');
                    if (targetSelect) {
                        targetSelect.value = targetId;
                    }
                }

                // Limpiar modo picking
                if (canvasElement) {
                    canvasElement.classList.remove('vbp-picking-target');
                }
                canvasElement.removeEventListener('click', pickHandler);
                modalElement.classList.remove('vbp-modal--picking');
            };

            canvasElement.addEventListener('click', pickHandler);

            // Cancelar picking con Escape
            var escapeHandler = function(keyEvent) {
                if (keyEvent.key === 'Escape') {
                    if (canvasElement) {
                        canvasElement.classList.remove('vbp-picking-target');
                    }
                    canvasElement.removeEventListener('click', pickHandler);
                    document.removeEventListener('keydown', escapeHandler);
                    modalElement.classList.remove('vbp-modal--picking');
                }
            };

            document.addEventListener('keydown', escapeHandler);
        },

        /**
         * Guarda la interaccion desde el editor
         */
        saveInteraction: function(modalElement, existingInteraction) {
            var triggerEl = modalElement.querySelector('#interaction-trigger');
            var actionEl = modalElement.querySelector('#interaction-action');
            var targetEl = modalElement.querySelector('#interaction-target');
            var animationEl = modalElement.querySelector('#interaction-animation');
            var durationEl = modalElement.querySelector('#interaction-duration');
            var delayEl = modalElement.querySelector('#interaction-delay');
            var urlEl = modalElement.querySelector('#interaction-url');
            var variableEl = modalElement.querySelector('#interaction-variable');
            var variableValueEl = modalElement.querySelector('#interaction-variable-value');

            var triggerValue = triggerEl ? triggerEl.value : '';
            var actionValue = actionEl ? actionEl.value : '';
            var targetValue = targetEl ? targetEl.value : '';
            var animationValue = animationEl ? animationEl.value : '';
            var durationValue = parseInt(durationEl ? durationEl.value : '300') || 300;
            var delayValue = parseInt(delayEl ? delayEl.value : '0') || 0;
            var urlValue = urlEl ? urlEl.value : '';
            var variableValue = variableEl ? variableEl.value : '';
            var variableValueValue = variableValueEl ? variableValueEl.value : '';

            var interactionData = {
                trigger: triggerValue,
                action: actionValue,
                target: targetValue,
                animation: animationValue,
                duration: durationValue,
                delay: delayValue,
                url: urlValue,
                variable: variableValue,
                variableValue: variableValueValue
            };

            if (existingInteraction) {
                // Actualizar existente
                this.prototypeMode.updateInteraction(this.selectedElementId, existingInteraction.id, interactionData);
            } else {
                // Crear nueva
                this.prototypeMode.addInteraction(this.selectedElementId, interactionData);
            }

            // Actualizar panel
            this.renderPanel();

            if (window.VBPToast) {
                window.VBPToast.show(existingInteraction ? 'Interaccion actualizada' : 'Interaccion creada', 'success');
            }
        },

        /**
         * Elimina una interaccion
         */
        deleteInteraction: function(interactionId) {
            if (!confirm('Eliminar esta interaccion?')) return;

            this.prototypeMode.removeInteraction(this.selectedElementId, interactionId);
            this.renderPanel();

            if (window.VBPToast) {
                window.VBPToast.show('Interaccion eliminada', 'info');
            }
        },

        /**
         * Toggle expandir/colapsar item de interaccion
         */
        toggleInteractionExpanded: function(interactionId) {
            var itemElement = document.querySelector('[data-interaction-id="' + interactionId + '"]');
            if (itemElement) {
                itemElement.classList.toggle('vbp-interaction-item--expanded');
            }
        },

        // ============================================
        // Drag & Drop para crear conexiones
        // ============================================

        /**
         * Inicia el drag para crear una conexion
         */
        startConnectionDrag: function(sourceElementId) {
            this.dragConnectionSource = sourceElementId;

            // Agregar clase al body
            document.body.classList.add('vbp-dragging-connection');

            // Crear linea visual temporal
            this.createTempConnectionLine();
        },

        /**
         * Crea la linea temporal de conexion
         */
        createTempConnectionLine: function() {
            var lineElement = document.createElement('div');
            lineElement.id = 'vbp-temp-connection-line';
            lineElement.className = 'vbp-temp-connection-line';
            document.body.appendChild(lineElement);

            var self = this;

            // Actualizar posicion de la linea con el mouse
            var mouseMoveHandler = function(moveEvent) {
                self.updateTempConnectionLine(moveEvent);
            };

            var mouseUpHandler = function(upEvent) {
                self.endConnectionDrag(upEvent);
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };

            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);
        },

        /**
         * Actualiza la linea temporal de conexion
         */
        updateTempConnectionLine: function(mouseEvent) {
            var lineElement = document.getElementById('vbp-temp-connection-line');
            if (!lineElement || !this.dragConnectionSource) return;

            var sourceElement = document.querySelector('[data-vbp-id="' + this.dragConnectionSource + '"]');
            if (!sourceElement) return;

            var sourceRect = sourceElement.getBoundingClientRect();
            var startX = sourceRect.right;
            var startY = sourceRect.top + sourceRect.height / 2;
            var endX = mouseEvent.clientX;
            var endY = mouseEvent.clientY;

            // Calcular angulo y longitud
            var deltaX = endX - startX;
            var deltaY = endY - startY;
            var lineLength = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
            var angleRadians = Math.atan2(deltaY, deltaX);

            lineElement.style.width = lineLength + 'px';
            lineElement.style.left = startX + 'px';
            lineElement.style.top = startY + 'px';
            lineElement.style.transform = 'rotate(' + angleRadians + 'rad)';
        },

        /**
         * Finaliza el drag de conexion
         */
        endConnectionDrag: function(mouseEvent) {
            document.body.classList.remove('vbp-dragging-connection');

            var lineElement = document.getElementById('vbp-temp-connection-line');
            if (lineElement) {
                lineElement.remove();
            }

            if (!this.dragConnectionSource) return;

            // Buscar elemento destino
            var targetElement = mouseEvent.target.closest('[data-vbp-id]');
            if (targetElement) {
                var targetId = targetElement.getAttribute('data-vbp-id');

                // No permitir conexion a si mismo
                if (targetId !== this.dragConnectionSource) {
                    // Crear interaccion de navegacion
                    this.prototypeMode.addInteraction(this.dragConnectionSource, {
                        trigger: 'click',
                        action: 'navigate',
                        target: targetId,
                        animation: 'dissolve',
                        duration: 300
                    });

                    this.renderPanel();

                    if (window.VBPToast) {
                        window.VBPToast.show('Conexion creada', 'success');
                    }
                }
            }

            this.dragConnectionSource = null;
        }
    };

    // ============================================
    // Componente Alpine.js
    // ============================================

    document.addEventListener('alpine:init', function() {
        if (window.Alpine && Alpine.data) {
            Alpine.data('vbpPrototypePanel', function() {
                return Object.assign({}, window.VBPPrototypePanel, {
                    init: function() {
                        window.VBPPrototypePanel.init.call(window.VBPPrototypePanel);
                    }
                });
            });
        }
    });

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPPrototypePanel.init();
        });
    } else {
        window.VBPPrototypePanel.init();
    }

})();
