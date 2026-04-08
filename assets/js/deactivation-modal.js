/**
 * Modal de confirmación para desactivación de Flavor Platform
 *
 * Intercepta el clic en el enlace de desactivar y muestra un diálogo
 * para preguntar si se desean conservar o eliminar los datos.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

(function ($) {
	'use strict';

	if (typeof flavorDeactivation === 'undefined') {
		return;
	}

	const config = flavorDeactivation;
	let deactivateUrl = '';

	/**
     * Inicializa el modal de desactivación
     */
	function init() {
		// Buscar el enlace de desactivar del plugin
		const pluginRow = $('tr[data-slug="flavor-chat-ia"]');
		if (!pluginRow.length) {
			return;
		}

		const deactivateLink = pluginRow.find('.deactivate a');
		if (!deactivateLink.length) {
			return;
		}

		// Guardar la URL original de desactivación
		deactivateUrl = deactivateLink.attr('href');

		// Interceptar el clic
		deactivateLink.on('click', function (e) {
			e.preventDefault();
			showModal();
		});

		// Configurar eventos del modal
		setupModalEvents();
	}

	/**
     * Muestra el modal
     */
	function showModal() {
		const overlay = $('#flavor-deactivation-overlay');
		overlay.addClass('active');

		// Focus en el primer elemento interactivo
		overlay.find('input[type="radio"]:checked').focus();

		// Cerrar con Escape
		$(document).on('keydown.flavorDeactivation', function (e) {
			if (e.key === 'Escape') {
				hideModal();
			}
		});
	}

	/**
     * Oculta el modal
     */
	function hideModal() {
		const overlay = $('#flavor-deactivation-overlay');
		overlay.removeClass('active');

		// Remover listener de Escape
		$(document).off('keydown.flavorDeactivation');
	}

	/**
     * Configura los eventos del modal
     */
	function setupModalEvents() {
		const overlay = $('#flavor-deactivation-overlay');
		const options = overlay.find('.flavor-deactivation-option');
		const warning = $('#flavor-deactivation-warning');
		const confirmBtn = $('#flavor-deactivation-confirm');

		// Click en overlay cierra el modal
		overlay.on('click', function (e) {
			if (e.target === this) {
				hideModal();
			}
		});

		// Botón cancelar
		$('#flavor-deactivation-cancel').on('click', function () {
			hideModal();
		});

		// Selección de opción
		options.on('click', function () {
			const value = $(this).data('value');

			// Actualizar selección visual
			options.removeClass('selected');
			$(this).addClass('selected');

			// Actualizar radio button
			$(this).find('input[type="radio"]').prop('checked', true);

			// Mostrar/ocultar advertencia y cambiar estilo del botón
			if (value === 'delete') {
				warning.addClass('visible');
				confirmBtn
					.removeClass('button-primary')
					.addClass('button-delete')
					.text(config.i18n.deactivate);
			} else {
				warning.removeClass('visible');
				confirmBtn
					.removeClass('button-delete')
					.addClass('button-primary')
					.text(config.i18n.deactivate);
			}
		});

		// Botón confirmar
		confirmBtn.on('click', function () {
			const deleteData = overlay.find('input[name="flavor_uninstall_data"]:checked').val() === 'delete';

			// Deshabilitar botones mientras se procesa
			confirmBtn.prop('disabled', true).text('...');
			$('#flavor-deactivation-cancel').prop('disabled', true);

			// Guardar preferencia via AJAX
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_save_uninstall_preference',
					nonce: config.nonce,
					delete_data: deleteData ? 'true' : 'false'
				},
				success: function (response) {
					// Redirigir a la URL de desactivación original
					window.location.href = deactivateUrl;
				},
				error: function () {
					// En caso de error, desactivar de todos modos
					window.location.href = deactivateUrl;
				}
			});
		});
	}

	// Inicializar cuando el DOM esté listo
	$(document).ready(init);

})(jQuery);
