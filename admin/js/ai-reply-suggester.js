/**
 * AI Reply Suggester - Sugeridor de Respuestas
 *
 * Sugiere respuestas automáticas para incidencias y tickets.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

(function ($) {
	'use strict';

	var ReplySuggester = {
		// Estado
		isLoading: false,
		suggestions: [],
		ticketId: null,

		// Elementos
		$container: null,
		$responseTextarea: null,

		/**
         * Inicializar
         */
		init: function () {
			// Buscar textarea de respuesta en página de incidencias
			this.$responseTextarea = $('#incidencia-respuesta, #ticket-reply, .ticket-reply-textarea');

			if (!this.$responseTextarea.length) {return;}

			this.ticketId = this.getTicketId();
			this.injectUI();
			this.bindEvents();
		},

		/**
         * Obtener ID del ticket
         */
		getTicketId: function () {
			// Intentar obtener de la URL
			var urlParams = new URLSearchParams(window.location.search);
			return urlParams.get('id') || urlParams.get('ticket_id') || '';
		},

		/**
         * Inyectar UI del sugeridor
         */
		injectUI: function () {
			var html = '<div class="flavor-ai-suggestions" id="ai-reply-suggestions">' +
                '<div class="flavor-ai-suggestions__header">' +
                '<h4><span class="dashicons dashicons-lightbulb"></span> Sugerencias de respuesta</h4>' +
                '<button type="button" class="button button-small" id="ai-load-suggestions">' +
                '<span class="dashicons dashicons-update"></span> Generar' +
                '</button>' +
                '</div>' +
                '<div class="flavor-ai-suggestions__list">' +
                '<p class="no-suggestions">Haz clic en "Generar" para obtener sugerencias basadas en el contexto del ticket.</p>' +
                '</div>' +
                '</div>';

			// Insertar antes del textarea
			this.$responseTextarea.before(html);
			this.$container = $('#ai-reply-suggestions');
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			var self = this;

			// Cargar sugerencias
			$('#ai-load-suggestions').on('click', function () {
				self.loadSuggestions();
			});

			// Usar sugerencia
			$(document).on('click', '.flavor-ai-suggestion', function () {
				var text = $(this).find('.flavor-ai-suggestion__text').text();
				self.useSuggestion(text);
			});

			// Copiar sugerencia
			$(document).on('click', '.suggestion-copy', function (e) {
				e.stopPropagation();
				var text = $(this).closest('.flavor-ai-suggestion').find('.flavor-ai-suggestion__text').text();
				FlavorAITools.utils.copyToClipboard(text);
			});
		},

		/**
         * Cargar sugerencias
         */
		loadSuggestions: function () {
			var self = this;

			if (!FlavorAITools.checkConfiguration()) {return;}
			if (this.isLoading) {return;}

			this.isLoading = true;
			var $btn = $('#ai-load-suggestions');
			var $list = this.$container.find('.flavor-ai-suggestions__list');

			// Estado de carga
			$btn.prop('disabled', true).find('.dashicons').addClass('spin');
			$list.html('<p class="loading"><span class="spinner is-active"></span> Analizando ticket...</p>');

			// Obtener contexto del ticket
			var ticketContext = this.getTicketContext();

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_reply_suggestions',
					nonce: FlavorAITools.config.nonces.reply,
					ticket_id: this.ticketId,
					context: ticketContext
				},
				success: function (response) {
					self.isLoading = false;
					$btn.prop('disabled', false).find('.dashicons').removeClass('spin');

					if (response.success && response.data.suggestions) {
						self.suggestions = response.data.suggestions;
						self.renderSuggestions();
					} else {
						$list.html('<p class="no-suggestions error">' +
                            (response.data?.error || 'No se pudieron generar sugerencias') +
                            '</p>');
					}
				},
				error: function () {
					self.isLoading = false;
					$btn.prop('disabled', false).find('.dashicons').removeClass('spin');
					$list.html('<p class="no-suggestions error">Error de conexión</p>');
				}
			});
		},

		/**
         * Obtener contexto del ticket
         */
		getTicketContext: function () {
			var context = {
				title: '',
				description: '',
				category: '',
				priority: '',
				history: []
			};

			// Primero intentar obtener de atributos data- del textarea
			if (this.$responseTextarea.data('titulo')) {
				context.title = this.$responseTextarea.data('titulo');
			}
			if (this.$responseTextarea.data('categoria')) {
				context.category = this.$responseTextarea.data('categoria');
			}
			if (this.$responseTextarea.data('prioridad')) {
				context.priority = this.$responseTextarea.data('prioridad');
			}

			// Fallback: obtener datos de elementos de la página
			if (!context.title) {
				context.title = $('.ticket-title, .incidencia-titulo, h1.entry-title, .postbox-header h2').first().text().trim();
			}
			if (!context.description) {
				context.description = $('.ticket-description, .incidencia-descripcion, .postbox .inside p').first().text().trim();
			}
			if (!context.category) {
				context.category = $('.ticket-category, .incidencia-categoria').first().text().trim();
			}
			if (!context.priority) {
				context.priority = $('.ticket-priority, .incidencia-prioridad').first().text().trim();
			}

			// Historial de respuestas desde timeline
			$('.flavor-timeline-item .flavor-ai-suggestion__text, .ticket-response, .incidencia-respuesta').each(function () {
				context.history.push($(this).text().trim().substring(0, 500));
			});

			// También obtener contenido del timeline existente
			if (!context.history.length) {
				$('.flavor-timeline-item > div:last-child p').each(function () {
					var text = $(this).text().trim();
					if (text.length > 10) {
						context.history.push(text.substring(0, 500));
					}
				});
			}

			return JSON.stringify(context);
		},

		/**
         * Renderizar sugerencias
         */
		renderSuggestions: function () {
			var $list = this.$container.find('.flavor-ai-suggestions__list');
			$list.empty();

			if (!this.suggestions.length) {
				$list.html('<p class="no-suggestions">No hay sugerencias disponibles.</p>');
				return;
			}

			var self = this;

			$.each(this.suggestions, function (index, suggestion) {
				var typeLabel = self.getTypeLabel(suggestion.type);

				var $suggestion = $('<div class="flavor-ai-suggestion">' +
                    '<span class="flavor-ai-suggestion__type">' +
                    '<span class="dashicons dashicons-' + (suggestion.icon || 'format-chat') + '"></span> ' +
                    typeLabel +
                    '</span>' +
                    '<div class="flavor-ai-suggestion__text">' + FlavorAITools.utils.escapeHtml(suggestion.text) + '</div>' +
                    '<div class="flavor-ai-suggestion__actions">' +
                    '<button type="button" class="button button-small suggestion-use">Usar</button>' +
                    '<button type="button" class="button button-small suggestion-copy">Copiar</button>' +
                    '</div>' +
                    '</div>');

				$list.append($suggestion);
			});
		},

		/**
         * Obtener etiqueta de tipo
         */
		getTypeLabel: function (type) {
			var labels = {
				'template': 'Plantilla',
				'ai': 'IA',
				'similar': 'Similar',
				'quick': 'Rápida'
			};
			return labels[type] || 'Sugerencia';
		},

		/**
         * Usar sugerencia
         */
		useSuggestion: function (text) {
			if (this.$responseTextarea.length) {
				this.$responseTextarea.val(text);
				this.$responseTextarea.trigger('change');
				this.$responseTextarea.focus();

				FlavorAITools.utils.toast('Sugerencia insertada', 'success');
			}
		}
	};

	// Estilos adicionales
	var styles = '<style>' +
        '.flavor-ai-suggestions { margin-bottom: 15px; }' +
        '.flavor-ai-suggestions .loading { display: flex; align-items: center; gap: 10px; padding: 20px; color: #666; }' +
        '.flavor-ai-suggestions .loading .spinner { margin: 0; }' +
        '.flavor-ai-suggestions .no-suggestions { text-align: center; padding: 20px; color: #888; font-style: italic; }' +
        '.flavor-ai-suggestions .no-suggestions.error { color: #dc2626; }' +
        '#ai-load-suggestions .dashicons.spin { animation: spin 1s linear infinite; }' +
        '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }' +
        '</style>';

	$('head').append(styles);

	// Inicializar cuando esté listo
	$(document).ready(function () {
		ReplySuggester.init();
	});

})(jQuery);
