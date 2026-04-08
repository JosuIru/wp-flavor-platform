/**
 * Flavor WP Social Share - Admin Scripts
 *
 * Maneja la funcionalidad de compartir posts en la red social
 * desde el editor de WordPress.
 */
(function ($) {
	'use strict';

	$(document).ready(function () {
		initCompartirAhora();
	});

	/**
     * Inicializa el botón "Compartir ahora"
     */
	function initCompartirAhora() {
		$('#flavor-compartir-ahora').on('click', function (e) {
			e.preventDefault();

			var $btn = $(this);
			var postId = $btn.data('post-id');

			if (!postId) {
				return;
			}

			// Obtener valores del metabox
			var visibilidad = $('#flavor_social_visibilidad').val() || 'publico';
			var mensaje = $('#flavor_social_mensaje').val() || '';
			var federar = $('input[name="flavor_social_federar"]').is(':checked') ? '1' : '0';

			// Deshabilitar botón
			$btn.prop('disabled', true)
				.html('<span class="dashicons dashicons-update spin"></span> ' +
                      flavorSocialShare.i18n.compartiendo);

			// Enviar AJAX
			$.ajax({
				url: flavorSocialShare.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_compartir_post_social',
					nonce: flavorSocialShare.nonce,
					post_id: postId,
					visibilidad: visibilidad,
					mensaje: mensaje,
					federar: federar
				},
				success: function (response) {
					if (response.success) {
						$btn.html('<span class="dashicons dashicons-yes"></span> ' +
                                  flavorSocialShare.i18n.compartido)
							.removeClass('button-primary')
							.addClass('button-secondary');

						// Mostrar notificación
						mostrarNotificacion(response.data.message, 'success');

						// Deshabilitar campos
						$('#flavor_social_visibilidad, #flavor_social_mensaje').prop('disabled', true);
						$('input[name="flavor_compartir_social"], input[name="flavor_social_federar"]')
							.prop('disabled', true);

						// Añadir notice de compartido
						var noticeHtml = '<div class="flavor-social-compartido-notice" ' +
                            'style="background:#d4edda;padding:8px;border-radius:4px;margin-bottom:10px;">' +
                            '<span class="dashicons dashicons-yes-alt" style="color:#28a745;"></span> ' +
                            'Compartido en red social (ID: ' + response.data.publicacion_id + ')' +
                            '</div>';
						$('.flavor-social-share-metabox').prepend(noticeHtml);

					} else {
						$btn.prop('disabled', false)
							.html('<span class="dashicons dashicons-share" style="margin-top:4px;"></span> ' +
                                  'Compartir ahora');
						mostrarNotificacion(response.data.message || flavorSocialShare.i18n.error, 'error');
					}
				},
				error: function () {
					$btn.prop('disabled', false)
						.html('<span class="dashicons dashicons-share" style="margin-top:4px;"></span> ' +
                              'Compartir ahora');
					mostrarNotificacion(flavorSocialShare.i18n.error, 'error');
				}
			});
		});
	}

	/**
     * Muestra una notificación temporal
     */
	function mostrarNotificacion(mensaje, tipo) {
		var clase = tipo === 'success' ? 'notice-success' : 'notice-error';
		var $notice = $('<div class="notice ' + clase + ' is-dismissible" style="position:fixed;top:50px;right:20px;z-index:99999;padding:10px 15px;max-width:300px;">' +
                        '<p>' + mensaje + '</p>' +
                        '</div>');

		$('body').append($notice);

		setTimeout(function () {
			$notice.fadeOut(300, function () {
				$(this).remove();
			});
		}, 3000);
	}

	// CSS para animación de spin
	$('<style>')
		.text('.dashicons.spin { animation: flavor-spin 1s linear infinite; }' +
              '@keyframes flavor-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
		.appendTo('head');

})(jQuery);
