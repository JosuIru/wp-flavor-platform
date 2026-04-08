/**
 * Asistente IA para composición de plantillas
 *
 * @package Flavor_Chat_IA
 */

(function ($) {
	'use strict';

	const FlavorAITemplateAssistant = {
		conversationHistory: [],
		currentTemplate: null,
		isProcessing: false,

		/**
         * Inicializar
         */
		init: function () {
			this.bindEvents();
			this.initPanel();
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			const self = this;

			// Enviar mensaje
			$(document).on('click', '#flavor-ai-send', function () {
				self.sendMessage();
			});

			// Enter para enviar
			$(document).on('keydown', '#flavor-ai-input', function (e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					self.sendMessage();
				}
			});

			// Aplicar plantilla
			$(document).on('click', '#flavor-ai-apply', function () {
				self.applyTemplate();
			});

			// Refinar plantilla
			$(document).on('click', '#flavor-ai-refine', function () {
				self.startRefineMode();
			});

			// Toggle panel
			$(document).on('click', '.flavor-ai-assistant-toggle', function () {
				self.togglePanel();
			});

			// Botón abrir asistente (en toolbar del page builder)
			$(document).on('click', '#flavor-pb-ai-assistant-btn', function () {
				self.showPanel();
			});
		},

		/**
         * Inicializar panel
         */
		initPanel: function () {
			// Añadir botón en la toolbar del page builder si existe
			const toolbar = $('.flavor-pb-toolbar-actions');
			if (toolbar.length && !$('#flavor-pb-ai-assistant-btn').length) {
				toolbar.prepend(`
                    <button type="button" id="flavor-pb-ai-assistant-btn" class="button button-secondary">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        Asistente IA
                    </button>
                `);
			}
		},

		/**
         * Enviar mensaje
         */
		sendMessage: function () {
			if (this.isProcessing) {return;}

			const input = $('#flavor-ai-input');
			const message = input.val().trim();

			if (!message) {return;}

			this.isProcessing = true;
			this.addMessage(message, 'user');
			input.val('');

			this.showThinking();

			const isFirstMessage = this.conversationHistory.length === 0;
			const endpoint = isFirstMessage ? 'flavor_ai_template_suggest' : 'flavor_ai_template_chat';

			const requestData = {
				action: endpoint,
				nonce: flavorAITemplateAssistant.nonce
			};

			if (isFirstMessage) {
				requestData.description = message;
			} else {
				requestData.message = message;
				requestData.conversation_history = JSON.stringify(this.conversationHistory);
				if (this.currentTemplate) {
					requestData.current_template = JSON.stringify(this.currentTemplate);
				}
			}

			$.ajax({
				url: flavorAITemplateAssistant.ajaxUrl,
				type: 'POST',
				data: requestData,
				success: (response) => {
					this.hideThinking();

					if (response.success) {
						this.handleResponse(response.data);
					} else {
						this.addMessage(response.data?.message || flavorAITemplateAssistant.strings.error, 'error');
					}

					this.isProcessing = false;
				},
				error: () => {
					this.hideThinking();
					this.addMessage(flavorAITemplateAssistant.strings.error, 'error');
					this.isProcessing = false;
				}
			});
		},

		/**
         * Manejar respuesta de la IA
         */
		handleResponse: function (data) {
			// Actualizar historial
			if (data.conversation_history) {
				this.conversationHistory = data.conversation_history;
			} else {
				this.conversationHistory.push(
					{ role: 'user', content: $('#flavor-ai-input').val() },
					{ role: 'assistant', content: data.message }
				);
			}

			// Mostrar mensaje (sin el JSON)
			const cleanMessage = this.cleanMessageForDisplay(data.message);
			this.addMessage(cleanMessage, 'assistant');

			// Si hay plantilla, mostrar preview
			if (data.template) {
				this.currentTemplate = data.template;
				this.showTemplatePreview(data.template);
			}
		},

		/**
         * Limpiar mensaje para mostrar (quitar bloques JSON)
         */
		cleanMessageForDisplay: function (message) {
			// Quitar bloques de código JSON
			return message.replace(/```json[\s\S]*?```/g, '')
				.replace(/\{[\s\S]*"layout"[\s\S]*\}/g, '[Plantilla generada - ver preview abajo]')
				.trim();
		},

		/**
         * Añadir mensaje al chat
         */
		addMessage: function (content, type) {
			const messagesContainer = $('#flavor-ai-chat-messages');
			const messageClass = `flavor-ai-message-${type}`;

			// Convertir markdown básico a HTML
			const htmlContent = this.markdownToHtml(content);

			const messageHtml = `
                <div class="flavor-ai-message ${messageClass}">
                    <div class="flavor-ai-message-content">${htmlContent}</div>
                </div>
            `;

			messagesContainer.append(messageHtml);
			messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
		},

		/**
         * Conversión básica de markdown a HTML
         */
		markdownToHtml: function (text) {
			return text
				.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
				.replace(/\*(.*?)\*/g, '<em>$1</em>')
				.replace(/`(.*?)`/g, '<code>$1</code>')
				.replace(/\n/g, '<br>')
				.replace(/- (.*?)(?=<br>|$)/g, '<li>$1</li>')
				.replace(/(<li>.*<\/li>)+/g, '<ul>$&</ul>');
		},

		/**
         * Mostrar indicador de pensando
         */
		showThinking: function () {
			const messagesContainer = $('#flavor-ai-chat-messages');
			messagesContainer.append(`
                <div class="flavor-ai-message flavor-ai-message-thinking" id="flavor-ai-thinking">
                    <div class="flavor-ai-message-content">
                        <span class="flavor-ai-thinking-dots">
                            <span></span><span></span><span></span>
                        </span>
                        ${flavorAITemplateAssistant.strings.thinking}
                    </div>
                </div>
            `);
			messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
		},

		/**
         * Ocultar indicador de pensando
         */
		hideThinking: function () {
			$('#flavor-ai-thinking').remove();
		},

		/**
         * Mostrar preview de plantilla
         */
		showTemplatePreview: function (template) {
			const previewContainer = $('#flavor-ai-preview');
			const componentsContainer = $('#flavor-ai-preview-components');

			componentsContainer.empty();

			if (template.template_name) {
				componentsContainer.append(`
                    <div class="flavor-ai-preview-header">
                        <strong>${template.template_name}</strong>
                        ${template.template_description ? `<p>${template.template_description}</p>` : ''}
                    </div>
                `);
			}

			const componentsList = $('<div class="flavor-ai-preview-list"></div>');

			template.layout.forEach((component, index) => {
				const componentHtml = `
                    <div class="flavor-ai-preview-component" data-index="${index}">
                        <span class="flavor-ai-component-order">${index + 1}</span>
                        <span class="flavor-ai-component-id">${component.component_id}</span>
                        ${component.data?.titulo ? `<span class="flavor-ai-component-title">${component.data.titulo}</span>` : ''}
                    </div>
                `;
				componentsList.append(componentHtml);
			});

			componentsContainer.append(componentsList);
			previewContainer.slideDown();
		},

		/**
         * Aplicar plantilla al page builder
         */
		applyTemplate: function () {
			if (!this.currentTemplate || !this.currentTemplate.layout) {
				alert('No hay plantilla para aplicar');
				return;
			}

			// Verificar si existe el page builder con el nuevo método
			if (typeof window.FlavorPageBuilder !== 'undefined' && typeof window.FlavorPageBuilder.loadLayoutFromData === 'function') {
				const success = window.FlavorPageBuilder.loadLayoutFromData(this.currentTemplate.layout);
				if (success) {
					this.addMessage('Plantilla aplicada correctamente. Puedes ver y editar los componentes en el canvas.', 'assistant');
					// Ocultar el preview
					$('#flavor-ai-preview').slideUp();
				}
			} else if (typeof window.FlavorPageBuilder !== 'undefined') {
				// Fallback al método antiguo si existe
				window.FlavorPageBuilder.layout = JSON.parse(JSON.stringify(this.currentTemplate.layout));
				window.FlavorPageBuilder.saveLayout();
				window.FlavorPageBuilder.refreshCanvas();
				this.addMessage('Plantilla aplicada correctamente. Puedes ver y editar los componentes en el canvas.', 'assistant');
				$('#flavor-ai-preview').slideUp();
			} else {
				// Fallback: guardar en campo oculto
				const layoutField = $('input[name="flavor_page_layout"]');
				if (layoutField.length) {
					layoutField.val(JSON.stringify(this.currentTemplate.layout));
					this.addMessage('Plantilla guardada. Guarda la página para aplicar los cambios.', 'assistant');
					// Refrescar el canvas si es posible
					location.reload();
				} else {
					// Copiar al portapapeles
					const jsonString = JSON.stringify(this.currentTemplate, null, 2);
					navigator.clipboard.writeText(jsonString).then(() => {
						this.addMessage('Plantilla copiada al portapapeles. Puedes pegarla manualmente en el editor.', 'assistant');
					}).catch(() => {
						this.addMessage('No se pudo copiar automáticamente. Por favor, revisa la consola del navegador.', 'error');
						console.log('Plantilla generada:', this.currentTemplate);
					});
				}
			}
		},

		/**
         * Iniciar modo de refinamiento
         */
		startRefineMode: function () {
			const input = $('#flavor-ai-input');
			input.attr('placeholder', 'Describe los cambios que quieres hacer...');
			input.focus();

			this.addMessage('¿Qué cambios te gustaría hacer en la plantilla? Por ejemplo: "Añade una sección de testimonios" o "Cambia el hero por uno con video".', 'assistant');
		},

		/**
         * Toggle panel
         */
		togglePanel: function () {
			const panel = $('#flavor-ai-template-assistant');
			const body = panel.find('.flavor-ai-assistant-body');
			const toggle = panel.find('.flavor-ai-assistant-toggle');
			const icon = toggle.find('.dashicons');

			body.slideToggle(200);

			if (icon.hasClass('dashicons-arrow-up-alt2')) {
				icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
				toggle.attr('aria-expanded', 'false');
			} else {
				icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
				toggle.attr('aria-expanded', 'true');
			}
		},

		/**
         * Mostrar panel
         */
		showPanel: function () {
			const panel = $('#flavor-ai-template-assistant');

			if (!panel.length) {
				// Crear panel si no existe
				this.createPanel();
			}

			panel.addClass('active');
			const body = panel.find('.flavor-ai-assistant-body');
			if (body.is(':hidden')) {
				body.slideDown(200);
			}
		},

		/**
         * Crear panel dinámicamente
         */
		createPanel: function () {
			const panelHtml = `
                <div id="flavor-ai-template-assistant" class="flavor-ai-assistant-panel">
                    <div class="flavor-ai-assistant-header">
                        <h3>
                            <span class="dashicons dashicons-superhero-alt"></span>
                            Asistente IA de Plantillas
                        </h3>
                        <button type="button" class="flavor-ai-assistant-toggle" aria-expanded="true">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                    </div>

                    <div class="flavor-ai-assistant-body">
                        <div class="flavor-ai-assistant-chat">
                            <div class="flavor-ai-chat-messages" id="flavor-ai-chat-messages">
                                <div class="flavor-ai-message flavor-ai-message-assistant">
                                    <div class="flavor-ai-message-content">
                                        ¡Hola! Soy tu asistente para crear plantillas. Describe tu negocio, comunidad u organización y te ayudaré a componer la plantilla perfecta.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flavor-ai-assistant-input">
                            <textarea
                                id="flavor-ai-input"
                                placeholder="Ej: Somos una cooperativa de productos ecológicos..."
                                rows="3"
                            ></textarea>
                            <div class="flavor-ai-assistant-actions">
                                <button type="button" id="flavor-ai-send" class="button button-primary">
                                    <span class="dashicons dashicons-arrow-right-alt"></span>
                                    Generar Plantilla
                                </button>
                            </div>
                        </div>

                        <div class="flavor-ai-assistant-preview" id="flavor-ai-preview" style="display: none;">
                            <h4>Plantilla Generada</h4>
                            <div class="flavor-ai-preview-components" id="flavor-ai-preview-components"></div>
                            <div class="flavor-ai-preview-actions">
                                <button type="button" id="flavor-ai-apply" class="button button-primary">
                                    <span class="dashicons dashicons-yes"></span>
                                    Aplicar Plantilla
                                </button>
                                <button type="button" id="flavor-ai-refine" class="button">
                                    <span class="dashicons dashicons-edit"></span>
                                    Refinar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

			// Insertar después del sidebar del page builder o al final del wrap
			const sidebar = $('.flavor-pb-sidebar');
			if (sidebar.length) {
				sidebar.after(panelHtml);
			} else {
				$('.wrap').append(panelHtml);
			}
		}
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function () {
		FlavorAITemplateAssistant.init();
	});

	// Exponer globalmente
	window.FlavorAITemplateAssistant = FlavorAITemplateAssistant;

})(jQuery);
