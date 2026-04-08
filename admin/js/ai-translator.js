/**
 * AI Translator - Traductor Inline
 *
 * Traduce contenido entre idiomas directamente en los campos de texto.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

(function ($) {
	'use strict';

	var AITranslator = {
		// Estado
		isTranslating: false,
		activeTextarea: null,

		// Idiomas disponibles
		languages: [
			{ code: 'es', name: 'Español', flag: '🇪🇸' },
			{ code: 'eu', name: 'Euskera', flag: '🏴' },
			{ code: 'ca', name: 'Catalán', flag: '🏴' },
			{ code: 'gl', name: 'Gallego', flag: '🏴' },
			{ code: 'en', name: 'English', flag: '🇬🇧' },
			{ code: 'fr', name: 'Français', flag: '🇫🇷' },
			{ code: 'de', name: 'Deutsch', flag: '🇩🇪' },
			{ code: 'pt', name: 'Português', flag: '🇵🇹' }
		],

		/**
         * Inicializar
         */
		init: function () {
			this.bindEvents();
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			var self = this;

			// Click en opción de idioma
			$(document).on('click', '.flavor-ai-translate-option', function () {
				var targetLang = $(this).data('lang');
				var $dropdown = $(this).closest('.flavor-ai-translate-dropdown');
				var $textarea = $dropdown.closest('.flavor-ai-textarea-wrapper').find('textarea');

				self.translate($textarea, targetLang);
				$dropdown.removeClass('active');
			});

			// Cerrar dropdown al hacer clic fuera
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.flavor-ai-translate-btn').length) {
					$('.flavor-ai-translate-dropdown').removeClass('active');
				}
			});
		},

		/**
         * Traducir texto
         */
		translate: function ($textarea, targetLang) {
			var self = this;
			var text = $textarea.val().trim();

			if (!text) {
				FlavorAITools.utils.toast('No hay texto para traducir', 'warning');
				return;
			}

			if (!FlavorAITools.checkConfiguration()) {return;}
			if (this.isTranslating) {return;}

			this.isTranslating = true;
			this.activeTextarea = $textarea;

			// Mostrar estado de traducción
			var $wrapper = $textarea.closest('.flavor-ai-textarea-wrapper');
			var $btn = $wrapper.find('.flavor-ai-translate-btn');

			$btn.prop('disabled', true).addClass('translating');
			$textarea.prop('disabled', true).css('opacity', '0.7');

			// Mostrar overlay de traducción
			this.showTranslatingOverlay($wrapper);

			$.ajax({
				url: FlavorAITools.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_content_translate',
					nonce: FlavorAITools.config.nonces.translate,
					text: text,
					target_language: targetLang
				},
				success: function (response) {
					self.isTranslating = false;
					self.hideTranslatingOverlay($wrapper);

					$btn.prop('disabled', false).removeClass('translating');
					$textarea.prop('disabled', false).css('opacity', '1');

					if (response.success && response.data.translated) {
						// Guardar original por si quiere deshacer
						$textarea.data('original-text', text);

						// Insertar traducción
						$textarea.val(response.data.translated);
						$textarea.trigger('change');

						// Mostrar opción de deshacer
						self.showUndoOption($wrapper);

						var langName = self.getLanguageName(targetLang);
						FlavorAITools.utils.toast('Traducido a ' + langName, 'success');
					} else {
						FlavorAITools.utils.toast(
							response.data?.error || 'Error al traducir',
							'error'
						);
					}
				},
				error: function () {
					self.isTranslating = false;
					self.hideTranslatingOverlay($wrapper);

					$btn.prop('disabled', false).removeClass('translating');
					$textarea.prop('disabled', false).css('opacity', '1');

					FlavorAITools.utils.toast('Error de conexión', 'error');
				}
			});
		},

		/**
         * Obtener nombre del idioma
         */
		getLanguageName: function (code) {
			var lang = this.languages.find(function (l) {
				return l.code === code;
			});
			return lang ? lang.name : code;
		},

		/**
         * Mostrar overlay de traducción
         */
		showTranslatingOverlay: function ($wrapper) {
			var $overlay = $('<div class="flavor-ai-translate-overlay">' +
                '<div class="translate-spinner"></div>' +
                '<span>Traduciendo...</span>' +
                '</div>');

			$wrapper.css('position', 'relative').append($overlay);
		},

		/**
         * Ocultar overlay
         */
		hideTranslatingOverlay: function ($wrapper) {
			$wrapper.find('.flavor-ai-translate-overlay').remove();
		},

		/**
         * Mostrar opción de deshacer
         */
		showUndoOption: function ($wrapper) {
			var self = this;

			// Remover undo anterior si existe
			$wrapper.find('.flavor-ai-translate-undo').remove();

			var $undo = $('<button type="button" class="flavor-ai-translate-undo">' +
                '<span class="dashicons dashicons-undo"></span> Deshacer traducción' +
                '</button>');

			$wrapper.append($undo);

			$undo.on('click', function () {
				var $textarea = $wrapper.find('textarea');
				var originalText = $textarea.data('original-text');

				if (originalText) {
					$textarea.val(originalText);
					$textarea.trigger('change');
					FlavorAITools.utils.toast('Texto restaurado', 'info');
				}

				$undo.remove();
			});

			// Auto-ocultar después de 10 segundos
			setTimeout(function () {
				$undo.fadeOut(function () {
					$(this).remove();
				});
			}, 10000);
		}
	};

	// Estilos
	var styles = '<style>' +
        '.flavor-ai-translate-btn.translating .dashicons {' +
        '    animation: spin 1s linear infinite;' +
        '}' +
        '.flavor-ai-translate-overlay {' +
        '    position: absolute;' +
        '    top: 0;' +
        '    left: 0;' +
        '    right: 0;' +
        '    bottom: 0;' +
        '    background: rgba(255, 255, 255, 0.9);' +
        '    display: flex;' +
        '    align-items: center;' +
        '    justify-content: center;' +
        '    gap: 10px;' +
        '    border-radius: 6px;' +
        '    font-size: 13px;' +
        '    color: #667eea;' +
        '    z-index: 10;' +
        '}' +
        '.translate-spinner {' +
        '    width: 20px;' +
        '    height: 20px;' +
        '    border: 2px solid rgba(102, 126, 234, 0.3);' +
        '    border-top-color: #667eea;' +
        '    border-radius: 50%;' +
        '    animation: spin 0.8s linear infinite;' +
        '}' +
        '.flavor-ai-translate-undo {' +
        '    position: absolute;' +
        '    bottom: -30px;' +
        '    right: 0;' +
        '    background: #f0f0f0;' +
        '    border: 1px solid #ddd;' +
        '    border-radius: 4px;' +
        '    padding: 4px 10px;' +
        '    font-size: 11px;' +
        '    cursor: pointer;' +
        '    display: flex;' +
        '    align-items: center;' +
        '    gap: 5px;' +
        '    transition: all 0.2s;' +
        '}' +
        '.flavor-ai-translate-undo:hover {' +
        '    background: #e0e0e0;' +
        '}' +
        '.flavor-ai-translate-undo .dashicons {' +
        '    font-size: 14px;' +
        '    width: 14px;' +
        '    height: 14px;' +
        '}' +
        '@keyframes spin {' +
        '    from { transform: rotate(0deg); }' +
        '    to { transform: rotate(360deg); }' +
        '}' +
        '</style>';

	$('head').append(styles);

	// Inicializar cuando esté listo
	$(document).ready(function () {
		AITranslator.init();
	});

	// Exponer para uso externo
	window.FlavorAITranslator = AITranslator;

})(jQuery);
