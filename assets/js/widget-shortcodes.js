/**
 * Widget Shortcodes - JavaScript para widgets en páginas públicas
 *
 * @package FlavorChatIA
 * @since 4.2.0
 */

(function ($) {
	'use strict';

	const FlavorWidgetShortcodes = {
		/**
         * Configuración del módulo
         */
		config: {
			refreshInterval: 60000, // 1 minuto
			animationDuration: 300,
		},

		/**
         * Inicialización
         */
		init: function () {
			this.bindEvents();
			this.setupAutoRefresh();
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			// Click en acciones del widget
			$(document).on('click', '.fws-widget__action[data-action]', this.handleAction.bind(this));

			// Refresh manual
			$(document).on('click', '.fws-widget__action--refresh', this.handleRefresh.bind(this));

			// Copiar shortcode
			$(document).on('click', '.fws-selector__shortcode', this.handleCopyShortcode.bind(this));
		},

		/**
         * Configurar auto-refresh para widgets que lo requieran
         */
		setupAutoRefresh: function () {
			const $autoRefreshWidgets = $('.fws-widget[data-refresh="true"]');

			if ($autoRefreshWidgets.length === 0) {
				return;
			}

			setInterval(() => {
				$autoRefreshWidgets.each((index, widget) => {
					this.refreshWidget($(widget));
				});
			}, this.config.refreshInterval);
		},

		/**
         * Manejar acción del widget
         *
         * @param {Event} e Evento click
         */
		handleAction: function (e) {
			const $button = $(e.currentTarget);
			const action = $button.data('action');
			const $widget = $button.closest('.fws-widget');
			const widgetId = $widget.data('widget-id');

			switch (action) {
				case 'refresh':
					this.refreshWidget($widget);
					break;
				case 'expand':
					this.expandWidget($widget);
					break;
				case 'collapse':
					this.collapseWidget($widget);
					break;
				default:
					// Acción personalizada
					$(document).trigger('fws:action', [action, widgetId, $widget]);
			}
		},

		/**
         * Manejar refresh del widget
         *
         * @param {Event} e Evento click
         */
		handleRefresh: function (e) {
			e.preventDefault();
			const $widget = $(e.currentTarget).closest('.fws-widget');
			this.refreshWidget($widget);
		},

		/**
         * Refrescar contenido del widget
         *
         * @param {jQuery} $widget Elemento widget
         */
		refreshWidget: function ($widget) {
			const widgetId = $widget.data('widget-id');
			const $body = $widget.find('.fws-widget__body');
			const $refreshBtn = $widget.find('.fws-widget__action--refresh');

			// Mostrar loading
			$body.addClass('fws-loading');
			$refreshBtn.addClass('fws-spinning');

			$.ajax({
				url: flavorWidgetShortcodes.ajaxUrl,
				type: 'POST',
				data: {
					action: 'fws_refresh_widget',
					widget_id: widgetId,
					nonce: flavorWidgetShortcodes.nonce,
				},
				success: (response) => {
					if (response.success && response.data.html) {
						$body.html(response.data.html);
					} else {
						this.showError($body, flavorWidgetShortcodes.i18n.error);
					}
				},
				error: () => {
					this.showError($body, flavorWidgetShortcodes.i18n.error);
				},
				complete: () => {
					$body.removeClass('fws-loading');
					$refreshBtn.removeClass('fws-spinning');
				},
			});
		},

		/**
         * Expandir widget (fullscreen)
         *
         * @param {jQuery} $widget Elemento widget
         */
		expandWidget: function ($widget) {
			$widget.addClass('fws-widget--expanded');
			$('body').addClass('fws-widget-expanded-active');

			// Cambiar icono
			const $expandBtn = $widget.find('.fws-widget__action--expand');
			$expandBtn
				.removeClass('fws-widget__action--expand')
				.addClass('fws-widget__action--collapse')
				.data('action', 'collapse')
				.find('.dashicons')
				.removeClass('dashicons-fullscreen')
				.addClass('dashicons-fullscreen-exit');
		},

		/**
         * Colapsar widget
         *
         * @param {jQuery} $widget Elemento widget
         */
		collapseWidget: function ($widget) {
			$widget.removeClass('fws-widget--expanded');
			$('body').removeClass('fws-widget-expanded-active');

			// Cambiar icono
			const $collapseBtn = $widget.find('.fws-widget__action--collapse');
			$collapseBtn
				.removeClass('fws-widget__action--collapse')
				.addClass('fws-widget__action--expand')
				.data('action', 'expand')
				.find('.dashicons')
				.removeClass('dashicons-fullscreen-exit')
				.addClass('dashicons-fullscreen');
		},

		/**
         * Copiar shortcode al portapapeles
         *
         * @param {Event} e Evento click
         */
		handleCopyShortcode: function (e) {
			const $code = $(e.currentTarget);
			const text = $code.text();

			// Intentar usar API moderna
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(() => {
					this.showCopiedFeedback($code);
				});
			} else {
				// Fallback para navegadores antiguos
				const $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
				this.showCopiedFeedback($code);
			}
		},

		/**
         * Mostrar feedback de copiado
         *
         * @param {jQuery} $element Elemento code
         */
		showCopiedFeedback: function ($element) {
			const originalText = $element.text();
			$element.text('Copiado!').addClass('fws-copied');

			setTimeout(() => {
				$element.text(originalText).removeClass('fws-copied');
			}, 1500);
		},

		/**
         * Mostrar error en el widget
         *
         * @param {jQuery} $body Contenedor del body
         * @param {string} message Mensaje de error
         */
		showError: function ($body, message) {
			$body.html(`
                <div class="fws-widget-error">
                    <span class="dashicons dashicons-warning"></span>
                    <p>${message}</p>
                    <button type="button" class="fws-retry-button" onclick="location.reload()">
                        ${flavorWidgetShortcodes.i18n.retry}
                    </button>
                </div>
            `);
		},
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function () {
		FlavorWidgetShortcodes.init();
	});

	// Exponer para uso externo
	window.FlavorWidgetShortcodes = FlavorWidgetShortcodes;

})(jQuery);
