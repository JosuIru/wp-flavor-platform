/**
 * Flavor WP Module Integrations - Admin Scripts
 *
 * Maneja la integración de posts con módulos desde el editor.
 */
(function ($) {
	'use strict';

	$(document).ready(function () {
		initCheckboxToggle();
		initIntegrarAhora();
		initEliminarIntegracion();
		initHistorialIntegraciones();
	});

	/**
     * Toggle de configuración al marcar/desmarcar checkbox
     */
	function initCheckboxToggle() {
		$(document).on('change', '.flavor-integracion-checkbox', function () {
			var $item = $(this).closest('.flavor-integracion-item');
			var $config = $item.find('.flavor-integracion-config');

			if ($(this).is(':checked')) {
				$config.slideDown(200);
			} else {
				$config.slideUp(200);
			}
		});
	}

	/**
     * Botón "Integrar ahora"
     */
	function initIntegrarAhora() {
		$(document).on('click', '#flavor-integrar-ahora', function () {
			var $btn = $(this);
			var postId = $btn.data('post-id');
			var integraciones = [];

			// Recoger integraciones seleccionadas
			$('.flavor-integracion-checkbox:checked').each(function () {
				var $item = $(this).closest('.flavor-integracion-item');
				var modulo = $item.data('modulo');
				var elementoId = $item.find('.flavor-select-elemento').val();

				integraciones.push({
					modulo: modulo,
					elemento_id: elementoId
				});
			});

			if (integraciones.length === 0) {
				alert(flavorModuleIntegrations.i18n.seleccionar || 'Selecciona al menos una integración');
				return;
			}

			// Deshabilitar botón
			$btn.prop('disabled', true)
				.html('<span class="dashicons dashicons-update spin"></span> ' +
                      flavorModuleIntegrations.i18n.integrando);

			// Procesar integraciones secuencialmente
			var promesas = integraciones.map(function (integracion) {
				return $.ajax({
					url: flavorModuleIntegrations.ajaxUrl,
					type: 'POST',
					data: {
						action: 'flavor_integrar_post_modulo',
						nonce: flavorModuleIntegrations.nonce,
						post_id: postId,
						modulo: integracion.modulo,
						elemento_id: integracion.elemento_id
					}
				});
			});

			$.when.apply($, promesas)
				.done(function () {
					$btn.html('<span class="dashicons dashicons-yes"></span> ' +
                              flavorModuleIntegrations.i18n.integrado)
						.addClass('button-primary');

					// Recargar historial
					cargarHistorialIntegraciones(postId);

					setTimeout(function () {
						$btn.prop('disabled', false)
							.removeClass('button-primary')
							.html('<span class="dashicons dashicons-update"></span> Integrar ahora');
					}, 2000);
				})
				.fail(function () {
					$btn.prop('disabled', false)
						.html('<span class="dashicons dashicons-warning"></span> ' +
                              flavorModuleIntegrations.i18n.error);

					setTimeout(function () {
						$btn.html('<span class="dashicons dashicons-update"></span> Integrar ahora');
					}, 2000);
				});
		});
	}

	/**
     * Eliminar integración
     */
	function initEliminarIntegracion() {
		$(document).on('click', '.flavor-eliminar-integracion', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var postId = $btn.data('post-id');
			var modulo = $btn.data('modulo');
			var elementoId = $btn.data('elemento-id');

			if (!confirm(flavorModuleIntegrations.i18n.confirmarEliminar || '¿Eliminar esta integración?')) {
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: flavorModuleIntegrations.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_eliminar_integracion',
					nonce: flavorModuleIntegrations.nonce,
					post_id: postId,
					modulo: modulo,
					elemento_id: elementoId
				},
				success: function (response) {
					if (response.success) {
						$btn.closest('.flavor-historial-item').fadeOut(200, function () {
							$(this).remove();
							actualizarContadorHistorial();
						});
					} else {
						alert(response.data.message || 'Error');
						$btn.prop('disabled', false);
					}
				},
				error: function () {
					alert('Error de conexión');
					$btn.prop('disabled', false);
				}
			});
		});
	}

	/**
     * Cargar historial de integraciones
     * Solo carga si el contenedor está vacío (no pre-renderizado)
     */
	function initHistorialIntegraciones() {
		var $container = $('#flavor-historial-integraciones');
		if ($container.length === 0) {
			return;
		}

		// Si ya tiene contenido pre-renderizado, no recargar
		if ($container.children().length > 0) {
			return;
		}

		var postId = $container.data('post-id');
		if (postId) {
			cargarHistorialIntegraciones(postId);
		}
	}

	/**
     * Carga el historial de integraciones de un post
     */
	function cargarHistorialIntegraciones(postId) {
		var $container = $('#flavor-historial-integraciones');
		if ($container.length === 0) {
			return;
		}

		$.ajax({
			url: flavorModuleIntegrations.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_obtener_historial_integraciones',
				nonce: flavorModuleIntegrations.nonce,
				post_id: postId
			},
			success: function (response) {
				if (response.success && response.data.html) {
					$container.html(response.data.html);
				}
			}
		});
	}

	/**
     * Actualiza contador del historial
     */
	function actualizarContadorHistorial() {
		var count = $('.flavor-historial-item').length;
		var $titulo = $('.flavor-historial-titulo .flavor-count');
		if ($titulo.length) {
			$titulo.text(count);
		}
	}

})(jQuery);
