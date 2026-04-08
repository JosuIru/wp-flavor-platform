/**
 * Module Chatbot - Widget Flotante
 *
 * Chatbot contextual que proporciona ayuda específica
 * según el módulo actual.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

(function ($) {
	'use strict';

	var ModuleChatbot = {
		// Estado
		isOpen: false,
		isTyping: false,
		conversationHistory: [],
		currentModule: '',

		// Elementos
		$widget: null,
		$toggle: null,
		$panel: null,
		$messages: null,
		$input: null,
		$sendBtn: null,

		/**
         * Inicializar
         */
		init: function () {
			this.$widget = $('#flavor-ai-chatbot');
			if (!this.$widget.length) {return;}

			this.$toggle = $('#chatbot-toggle');
			this.$panel = $('#chatbot-panel');
			this.$messages = $('#chatbot-messages');
			this.$input = $('#chatbot-input');
			this.$sendBtn = $('#chatbot-send');

			this.currentModule = this.$widget.data('module') || 'general';

			this.bindEvents();
			this.loadQuickActions();
			this.loadFAQs();
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			var self = this;

			// Toggle del chat
			this.$toggle.on('click', function () {
				self.toggle();
			});

			// Minimizar
			$('#chatbot-minimize').on('click', function () {
				self.close();
			});

			// Limpiar chat
			$('#chatbot-clear').on('click', function () {
				self.clearChat();
			});

			// Enviar mensaje
			this.$sendBtn.on('click', function () {
				self.sendMessage();
			});

			// Enter para enviar
			this.$input.on('keypress', function (e) {
				if (e.which === 13 && !e.shiftKey) {
					e.preventDefault();
					self.sendMessage();
				}
			});

			// Auto-resize textarea
			this.$input.on('input', function () {
				this.style.height = 'auto';
				this.style.height = Math.min(this.scrollHeight, 80) + 'px';
			});

			// Quick actions
			$(document).on('click', '.quick-action-chip', function () {
				var actionType = $(this).data('action');
				self.executeQuickAction(actionType);
			});

			// FAQs
			$(document).on('click', '.faq-chip', function () {
				var question = $(this).text();
				self.$input.val(question);
				self.sendMessage();
			});

			// Cerrar al hacer clic fuera
			$(document).on('click', function (e) {
				if (self.isOpen && !$(e.target).closest('.flavor-ai-chatbot').length) {
					self.close();
				}
			});
		},

		/**
         * Toggle del panel
         */
		toggle: function () {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		},

		/**
         * Abrir panel
         */
		open: function () {
			this.isOpen = true;
			this.$panel.slideDown(200);
			this.$toggle.find('.toggle-icon-open').hide();
			this.$toggle.find('.toggle-icon-close').show();
			this.$input.focus();
		},

		/**
         * Cerrar panel
         */
		close: function () {
			this.isOpen = false;
			this.$panel.slideUp(200);
			this.$toggle.find('.toggle-icon-open').show();
			this.$toggle.find('.toggle-icon-close').hide();
		},

		/**
         * Limpiar chat
         */
		clearChat: function () {
			this.conversationHistory = [];
			this.$messages.html(this.getWelcomeMessage());

			// Mostrar quick actions y FAQs de nuevo
			$('#chatbot-quick-actions').show();
			$('#chatbot-faqs').show();
		},

		/**
         * Obtener mensaje de bienvenida
         */
		getWelcomeMessage: function () {
			var moduleName = FlavorAITools.config.moduleContext?.name || 'Flavor Platform';

			return '<div class="message assistant">' +
                '<div class="message-avatar"><span class="dashicons dashicons-format-chat"></span></div>' +
                '<div class="message-content">' +
                '<p>¡Hola! Soy tu asistente para <strong>' + moduleName + '</strong>. ¿En qué puedo ayudarte?</p>' +
                '</div>' +
                '</div>';
		},

		/**
         * Enviar mensaje
         */
		sendMessage: function () {
			var message = this.$input.val().trim();
			if (!message || this.isTyping) {return;}

			// Verificar configuración
			if (!FlavorAITools.checkConfiguration()) {return;}

			// Añadir mensaje del usuario
			this.addMessage(message, 'user');

			// Limpiar input
			this.$input.val('').css('height', 'auto');

			// Ocultar quick actions y FAQs
			$('#chatbot-quick-actions').hide();
			$('#chatbot-faqs').hide();

			// Mostrar typing
			this.showTyping();

			// Guardar en historial
			this.conversationHistory.push({
				role: 'user',
				content: message
			});

			// Enviar al backend
			this.fetchResponse(message);
		},

		/**
         * Añadir mensaje al chat
         */
		addMessage: function (content, type) {
			var avatarIcon = type === 'user' ? 'dashicons-admin-users' : 'dashicons-format-chat';

			var $message = $('<div class="message ' + type + '">' +
                '<div class="message-avatar"><span class="dashicons ' + avatarIcon + '"></span></div>' +
                '<div class="message-content"><p>' + this.formatMessage(content) + '</p></div>' +
                '</div>');

			this.$messages.append($message);
			this.scrollToBottom();
		},

		/**
         * Formatear mensaje (markdown básico)
         */
		formatMessage: function (text) {
			// Escapar HTML
			text = FlavorAITools.utils.escapeHtml(text);

			// Negrita
			text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

			// Enlaces
			text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

			// Saltos de línea
			text = text.replace(/\n/g, '<br>');

			return text;
		},

		/**
         * Mostrar indicador de typing
         */
		showTyping: function () {
			this.isTyping = true;
			$('#chatbot-typing').show();
			this.scrollToBottom();
		},

		/**
         * Ocultar indicador de typing
         */
		hideTyping: function () {
			this.isTyping = false;
			$('#chatbot-typing').hide();
		},

		/**
         * Scroll al final del chat
         */
		scrollToBottom: function () {
			var $messages = this.$messages;
			$messages.scrollTop($messages[0].scrollHeight);
		},

		/**
         * Obtener respuesta del backend
         */
		fetchResponse: function (message) {
			var self = this;

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_module_chat',
					nonce: FlavorAITools.config.nonces.chat,
					message: message,
					module: this.currentModule,
					history: JSON.stringify(this.conversationHistory.slice(-10))
				},
				success: function (response) {
					self.hideTyping();

					if (response.success && response.data.response) {
						self.addMessage(response.data.response, 'assistant');

						// Guardar en historial
						self.conversationHistory.push({
							role: 'assistant',
							content: response.data.response
						});
					} else {
						self.addMessage(
							response.data?.error || 'Lo siento, ha ocurrido un error.',
							'assistant'
						);
					}
				},
				error: function () {
					self.hideTyping();
					self.addMessage('Error de conexión. Por favor, inténtalo de nuevo.', 'assistant');
				}
			});
		},

		/**
         * Cargar acciones rápidas según módulo
         */
		loadQuickActions: function () {
			var self = this;

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_module_help',
					nonce: FlavorAITools.config.nonces.chat,
					module: this.currentModule,
					type: 'quick_actions'
				},
				success: function (response) {
					if (response.success && response.data.quick_actions) {
						self.renderQuickActions(response.data.quick_actions);
					}
				}
			});
		},

		/**
         * Renderizar acciones rápidas
         */
		renderQuickActions: function (actions) {
			var $container = $('#chatbot-quick-actions .quick-actions-list');
			$container.empty();

			$.each(actions, function (key, action) {
				var $chip = $('<button type="button" class="quick-action-chip" data-action="' + key + '">')
					.html('<span class="dashicons dashicons-' + (action.icon || 'admin-generic') + '"></span> ' + action.label);

				$container.append($chip);
			});

			if (Object.keys(actions).length > 0) {
				$('#chatbot-quick-actions').show();
			}
		},

		/**
         * Ejecutar acción rápida
         */
		executeQuickAction: function (actionType) {
			var self = this;

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_module_quick_action',
					nonce: FlavorAITools.config.nonces.chat,
					module: this.currentModule,
					action_type: actionType
				},
				success: function (response) {
					if (response.success) {
						// Puede redirigir o mostrar modal según la acción
						if (response.data.redirect) {
							window.location.href = response.data.redirect;
						} else if (response.data.message) {
							self.addMessage(response.data.message, 'assistant');
						}
					}
				}
			});
		},

		/**
         * Cargar FAQs según módulo
         */
		loadFAQs: function () {
			var self = this;

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_module_help',
					nonce: FlavorAITools.config.nonces.chat,
					module: this.currentModule,
					type: 'faqs'
				},
				success: function (response) {
					if (response.success && response.data.faqs) {
						self.renderFAQs(response.data.faqs);
					}
				}
			});
		},

		/**
         * Renderizar FAQs
         */
		renderFAQs: function (faqs) {
			var $container = $('#chatbot-faqs .faqs-list');
			$container.empty();

			$.each(faqs, function (index, question) {
				var $chip = $('<button type="button" class="faq-chip">').text(question);
				$container.append($chip);
			});

			if (faqs.length > 0) {
				$('#chatbot-faqs').show();
			}
		}
	};

	// Inicializar cuando esté listo
	$(document).ready(function () {
		ModuleChatbot.init();
	});

})(jQuery);
