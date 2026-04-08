/**
 * AI Content Generator - Generador de Contenido Inline
 *
 * Modal para generar contenido con IA desde cualquier textarea.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

(function ($) {
	'use strict';

	var ContentGenerator = {
		// Estado
		isGenerating: false,
		lastResult: null,
		activeTextarea: null,

		// Elementos
		$modal: null,

		/**
         * Inicializar
         */
		init: function () {
			this.$modal = $('#flavor-ai-content-modal');
			if (!this.$modal.length) {return;}

			this.bindEvents();
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			var self = this;

			// Cerrar modal
			$('#content-modal-close, #content-modal-cancel').on('click', function () {
				self.close();
			});

			// Cerrar al hacer clic en overlay
			this.$modal.find('.flavor-modal-overlay').on('click', function () {
				self.close();
			});

			// Cerrar con ESC
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && self.$modal.is(':visible')) {
					self.close();
				}
			});

			// Generar contenido
			$('#ai-content-generate').on('click', function () {
				self.generate();
			});

			// Regenerar
			$('#ai-content-regenerate').on('click', function () {
				self.generate();
			});

			// Copiar resultado
			$('#ai-content-copy').on('click', function () {
				self.copyResult();
			});

			// Insertar resultado
			$('#ai-content-insert').on('click', function () {
				self.insertResult();
			});

			// Cambio de tipo de contenido
			$('#ai-content-type').on('change', function () {
				self.updatePlaceholder();
			});
		},

		/**
         * Abrir modal
         */
		open: function ($textarea) {
			this.activeTextarea = $textarea || FlavorAITools.activeTextarea;
			this.lastResult = null;

			// Reset formulario
			this.resetForm();

			// Prellenar contexto si hay texto existente
			if (this.activeTextarea && this.activeTextarea.val()) {
				$('#ai-content-context').val(this.activeTextarea.val());
			}

			// Mostrar modal
			this.$modal.fadeIn(200);
		},

		/**
         * Cerrar modal
         */
		close: function () {
			this.$modal.fadeOut(200);
			this.resetForm();
		},

		/**
         * Resetear formulario
         */
		resetForm: function () {
			$('#ai-content-context').val('');
			$('#ai-content-result-area').hide();
			$('#ai-content-result').html('');
			$('#ai-generation-status').hide();
			$('#ai-generation-error').hide();
			this.isGenerating = false;
		},

		/**
         * Actualizar placeholder según tipo
         */
		updatePlaceholder: function () {
			var type = $('#ai-content-type').val();
			var placeholders = {
				'evento_descripcion': 'Ejemplo: Taller de cocina vegana el sábado 15, de 10 a 13h en el centro cívico...',
				'evento_titulo': 'Ejemplo: Un taller sobre alimentación saludable para familias...',
				'post_blog': 'Ejemplo: Un artículo sobre los beneficios de la agricultura ecológica...',
				'pagina_bienvenida': 'Ejemplo: Página de bienvenida para una cooperativa de consumo...',
				'email_notificacion': 'Ejemplo: Recordatorio de evento, confirmación de inscripción...',
				'descripcion_modulo': 'Ejemplo: Módulo de gestión de reservas de espacios...',
				'faq': 'Ejemplo: Preguntas sobre cómo inscribirse en cursos...',
				'slogan': 'Ejemplo: Una cooperativa de consumo ecológico local...',
				'bio': 'Ejemplo: Biografía para un artista local del barrio...',
				'general': 'Describe qué contenido necesitas generar...'
			};

			$('#ai-content-context').attr('placeholder', placeholders[type] || placeholders.general);
		},

		/**
         * Generar contenido
         */
		generate: function () {
			var self = this;
			var context = $('#ai-content-context').val().trim();

			if (!context) {
				FlavorAITools.utils.toast('Por favor, describe qué quieres generar', 'warning');
				return;
			}

			if (this.isGenerating) {return;}
			this.isGenerating = true;

			// Ocultar resultado anterior y mostrar status
			$('#ai-content-result-area').hide();
			$('#ai-generation-error').hide();
			$('#ai-generation-status').show();

			// Obtener opciones
			var data = {
				action: 'flavor_ai_generate_content',
				nonce: FlavorAITools.config.nonces.content,
				type: $('#ai-content-type').val(),
				context: context,
				options: {
					tone: $('#ai-content-tone').val(),
					length: $('#ai-content-length').val(),
					language: $('#ai-content-language').val()
				}
			};

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					self.isGenerating = false;
					$('#ai-generation-status').hide();

					if (response.success && response.data.content) {
						self.showResult(response.data.content);
					} else {
						self.showError(response.data?.error || 'Error al generar contenido');
					}
				},
				error: function () {
					self.isGenerating = false;
					$('#ai-generation-status').hide();
					self.showError('Error de conexión. Por favor, inténtalo de nuevo.');
				}
			});
		},

		/**
         * Mostrar resultado
         */
		showResult: function (content) {
			this.lastResult = content;

			// Formatear contenido para HTML
			var formattedContent = this.formatContent(content);

			$('#ai-content-result').html(formattedContent);
			$('#ai-content-result-area').slideDown(200);
		},

		/**
         * Formatear contenido
         */
		formatContent: function (content) {
			// Escapar HTML
			var html = FlavorAITools.utils.escapeHtml(content);

			// Saltos de línea
			html = html.replace(/\n\n/g, '</p><p>');
			html = html.replace(/\n/g, '<br>');

			// Wrap en párrafos
			html = '<p>' + html + '</p>';

			return html;
		},

		/**
         * Mostrar error
         */
		showError: function (message) {
			$('#ai-generation-error')
				.find('.error-message').text(message).end()
				.show();
		},

		/**
         * Copiar resultado
         */
		copyResult: function () {
			if (this.lastResult) {
				FlavorAITools.utils.copyToClipboard(this.lastResult);
			}
		},

		/**
         * Insertar resultado en textarea
         */
		insertResult: function () {
			if (this.lastResult && this.activeTextarea) {
				// Obtener contenido actual
				var currentContent = this.activeTextarea.val();
				var insertContent = this.lastResult;

				// Si hay contenido, añadir separación
				if (currentContent.trim()) {
					insertContent = currentContent + '\n\n' + insertContent;
				}

				// Insertar
				this.activeTextarea.val(insertContent);

				// Trigger change para editores (TinyMCE, etc)
				this.activeTextarea.trigger('change');

				// Si es un editor visual de WordPress
				var editorId = this.activeTextarea.attr('id');
				if (editorId && typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
					tinyMCE.get(editorId).setContent(insertContent);
				}

				FlavorAITools.utils.toast('Contenido insertado', 'success');
				this.close();
			}
		}
	};

	/**
     * Generador de asuntos de email
     */
	var SubjectGenerator = {
		isGenerating: false,

		init: function () {
			var self = this;

			// Click en botón de generar asunto
			$(document).on('click', '.flavor-ai-generate-subject-btn', function (e) {
				e.preventDefault();
				var targetId = $(this).data('target');
				var $target = $('#' + targetId);
				if ($target.length) {
					self.generateSubject($target, $(this));
				}
			});
		},

		generateSubject: function ($input, $btn) {
			var self = this;

			if (!FlavorAITools.checkConfiguration()) {return;}
			if (this.isGenerating) {return;}

			this.isGenerating = true;

			// Obtener contexto del email
			var emailContent = '';
			var emailName = $('#em-nombre').val() || '';

			// Intentar obtener contenido del editor
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('em_contenido_html')) {
				emailContent = tinyMCE.get('em_contenido_html').getContent({format: 'text'});
			}
			if (!emailContent) {
				emailContent = $('#em-contenido-html-raw').val() || '';
			}

			// Estado de carga
			var originalHtml = $btn.html();
			$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_ai_generate_content',
					nonce: FlavorAITools.config.nonces.content,
					type: 'email_asunto',
					context: emailContent.substring(0, 1000) || emailName,
					options: {
						tone: 'profesional',
						length: 'corto',
						language: 'es'
					}
				},
				success: function (response) {
					self.isGenerating = false;
					$btn.prop('disabled', false).html(originalHtml);

					if (response.success && response.data.content) {
						// Limpiar el asunto (quitar comillas, saltos de línea, etc.)
						var subject = response.data.content.replace(/["'\n\r]/g, '').trim();
						$input.val(subject);
						FlavorAITools.utils.toast('Asunto generado', 'success');
					} else {
						FlavorAITools.utils.toast(response.data?.error || 'Error al generar', 'error');
					}
				},
				error: function () {
					self.isGenerating = false;
					$btn.prop('disabled', false).html(originalHtml);
					FlavorAITools.utils.toast('Error de conexión', 'error');
				}
			});
		}
	};

	// Inicializar cuando esté listo
	$(document).ready(function () {
		ContentGenerator.init();
		SubjectGenerator.init();
	});

	// Exponer para uso externo
	window.FlavorAIContentGenerator = ContentGenerator;
	window.FlavorAISubjectGenerator = SubjectGenerator;

})(jQuery);
