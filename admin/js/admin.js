/**
 * Flavor Chat IA - Admin JavaScript
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// Unsaved changes detection
		var formHasChanges = false;
		var $settingsForm = $('#flavor-chat-settings-form');
		if ($settingsForm.length) {
			$settingsForm.on('change input', 'input, select, textarea', function () {
				if (!formHasChanges) {
					formHasChanges = true;
					$('.flavor-chat-settings').addClass('has-unsaved-changes');
				}
			});
			$(window).on('beforeunload', function () {
				if (formHasChanges) {
					return 'Hay cambios sin guardar.';
				}
			});
			$settingsForm.on('submit', function () {
				formHasChanges = false;
			});
		}

		// Scroll to top on save notice
		if ($('.flavor-chat-settings .notice').length) {
			$('html, body').animate({ scrollTop: 0 }, 300);
		}

		// Color picker
		if ($.fn.wpColorPicker) {
			$('.color-picker').wpColorPicker();
		}

		// ==========================================
		// MULTI-PROVEEDOR IA
		// ==========================================

		// Resaltar el proveedor activo
		function highlightActiveProvider() {
			const activeProvider = $('#active_provider').val();

			// Quitar resaltado de todos
			$('.provider-settings').css({
				'border-width': '1px',
				'opacity': '0.7'
			});

			// Resaltar el activo
			$(`.provider-${activeProvider}`).css({
				'border-width': '3px',
				'opacity': '1'
			});
		}

		// Ejecutar al cargar y al cambiar
		if ($('#active_provider').length) {
			highlightActiveProvider();

			$('#active_provider').on('change', function () {
				highlightActiveProvider();
			});
		}

		// ==========================================
		// AUTOCONFIGURACIÓN CON IA
		// ==========================================

		// Autoconfig para cada sección
		$('[id^="autoconfig-"]').on('click', function () {
			var $button = $(this);
			var section = $button.data('section');
			var $status = $button.next('.autoconfig-status');

			if (!section) {return;}

			$button.prop('disabled', true);
			$status.html('<span style="color:#666;">' + (flavorChatAdmin?.strings?.analyzing || 'Analizando sitio...') + '</span>');

			$.ajax({
				url: flavorChatAdmin?.ajaxUrl || ajaxurl,
				method: 'POST',
				data: {
					action: 'flavor_chat_autoconfig',
					nonce: flavorChatAdmin?.nonce || '',
					section: section
				},
				success: function (response) {
					$button.prop('disabled', false);

					if (response.success) {
						$status.html('<span style="color:green;">✓ ' + (flavorChatAdmin?.strings?.success || 'Configuración generada') + '</span>');
						applyAutoconfigData(section, response.data);
					} else {
						$status.html('<span style="color:red;">✗ ' + (response.data?.error || flavorChatAdmin?.strings?.error || 'Error') + '</span>');
					}
				},
				error: function () {
					$button.prop('disabled', false);
					$status.html('<span style="color:red;">✗ Error de conexión</span>');
				}
			});
		});

		// Aplicar datos de autoconfiguración al formulario
		function applyAutoconfigData(section, data) {
			if (section === 'knowledge' && data) {
				// Business info
				if (data.business_info) {
					$('input[name="flavor_chat_ia_settings[business_info][name]"]').val(data.business_info.name || '');
					$('textarea[name="flavor_chat_ia_settings[business_info][description]"]').val(data.business_info.description || '');
					$('input[name="flavor_chat_ia_settings[business_info][address]"]').val(data.business_info.address || '');
					$('input[name="flavor_chat_ia_settings[business_info][phone]"]').val(data.business_info.phone || '');
					$('input[name="flavor_chat_ia_settings[business_info][email]"]').val(data.business_info.email || '');
					$('input[name="flavor_chat_ia_settings[business_info][schedule]"]').val(data.business_info.schedule || '');
				}

				// FAQs
				if (data.faqs && data.faqs.length > 0) {
					var $container = $('#faqs-container');
					$container.empty();
					data.faqs.forEach(function (faq, index) {
						var html = '<div class="faq-item" style="background:#fff;padding:15px;border:1px solid #ccd0d4;margin-bottom:10px;">' +
                            '<div style="display:flex;justify-content:space-between;margin-bottom:10px;"><strong>FAQ #' + (index + 1) + '</strong><button type="button" class="button remove-faq">Eliminar</button></div>' +
                            '<p><label>Pregunta:</label><input type="text" name="flavor_chat_ia_settings[faqs][' + index + '][question]" value="' + escapeHtml(faq.question || '') + '" class="large-text"></p>' +
                            '<p><label>Respuesta:</label><textarea name="flavor_chat_ia_settings[faqs][' + index + '][answer]" rows="3" class="large-text">' + escapeHtml(faq.answer || '') + '</textarea></p></div>';
						$container.append(html);
					});
				}

				// Policies
				if (data.policies) {
					$('textarea[name="flavor_chat_ia_settings[policies][shipping]"]').val(data.policies.shipping || '');
					$('textarea[name="flavor_chat_ia_settings[policies][returns]"]').val(data.policies.returns || '');
					$('textarea[name="flavor_chat_ia_settings[policies][privacy]"]').val(data.policies.privacy || '');
				}

				// Business topics
				if (data.business_topics) {
					$('input[name="flavor_chat_ia_settings[business_topics]"]').val(data.business_topics.join(', '));
				}
			}

			if (section === 'quick_actions' && data) {
				if (data.quick_actions) {
					Object.keys(data.quick_actions).forEach(function (id) {
						var action = data.quick_actions[id];
						var $enabled = $('input[name="flavor_chat_ia_settings[quick_actions][' + id + '][enabled]"]');
						var $label = $('input[name="flavor_chat_ia_settings[quick_actions][' + id + '][label]"]');
						var $prompt = $('input[name="flavor_chat_ia_settings[quick_actions][' + id + '][prompt]"]');
						var $icon = $('select[name="flavor_chat_ia_settings[quick_actions][' + id + '][icon]"]');

						if ($enabled.length) {$enabled.prop('checked', action.enabled);}
						if ($label.length) {$label.val(action.label || '');}
						if ($prompt.length) {$prompt.val(action.prompt || '');}
						if ($icon.length && action.icon) {$icon.val(action.icon);}
					});
				}

				if (data.custom_quick_actions && data.custom_quick_actions.length > 0) {
					var $container = $('#custom-actions-container');
					$container.empty();
					data.custom_quick_actions.forEach(function (action, index) {
						var html = '<div class="custom-action-item" style="background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">' +
                            '<input type="text" name="flavor_chat_ia_settings[custom_quick_actions][' + index + '][label]" value="' + escapeHtml(action.label || '') + '" placeholder="Texto del botón" class="regular-text">' +
                            '<input type="text" name="flavor_chat_ia_settings[custom_quick_actions][' + index + '][prompt]" value="' + escapeHtml(action.prompt || '') + '" placeholder="Mensaje que envía" class="large-text">' +
                            '<button type="button" class="button remove-custom-action">Eliminar</button></div>';
						$container.append(html);
					});
				}
			}

			if (section === 'escalation' && data) {
				if (data.escalation_whatsapp) {$('input[name="flavor_chat_ia_settings[escalation_whatsapp]"]').val(data.escalation_whatsapp);}
				if (data.escalation_phone) {$('input[name="flavor_chat_ia_settings[escalation_phone]"]').val(data.escalation_phone);}
				if (data.escalation_email) {$('input[name="flavor_chat_ia_settings[escalation_email]"]').val(data.escalation_email);}
				if (data.escalation_hours) {$('input[name="flavor_chat_ia_settings[escalation_hours]"]').val(data.escalation_hours);}
			}

			if (section === 'appearance' && data && data.appearance) {
				if (data.appearance.primary_color) {$('#primary_color').val(data.appearance.primary_color);}
				if (data.appearance.header_bg) {$('#header_bg').val(data.appearance.header_bg);}
				if (data.appearance.user_bubble) {$('#user_bubble').val(data.appearance.user_bubble);}
				if (data.appearance.assistant_bubble) {$('#assistant_bubble').val(data.appearance.assistant_bubble);}
				if (data.appearance.welcome_message) {$('textarea[name="flavor_chat_ia_settings[appearance][welcome_message]"]').val(data.appearance.welcome_message);}
			}
		}

		function escapeHtml(text) {
			if (!text) {return '';}
			return text.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}

		// ==========================================
		// MEDIA UPLOADER (Avatar)
		// ==========================================

		$('#upload-avatar').on('click', function (e) {
			e.preventDefault();

			var frame = wp.media({
				title: 'Seleccionar avatar',
				button: { text: 'Usar esta imagen' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#avatar_url').val(attachment.url);
				$('#avatar-preview').html('<img src="' + attachment.url + '" style="max-width:80px;max-height:80px;border-radius:50%;">');
				$('#remove-avatar').show();
			});

			frame.open();
		});

		$('#remove-avatar').on('click', function () {
			$('#avatar_url').val('');
			$('#avatar-preview').html('<div style="width:80px;height:80px;border-radius:50%;background:#1e3a5f;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🤖</div>');
			$(this).hide();
		});

		// ==========================================
		// RESOLUCIÓN DE ESCALADOS
		// ==========================================

		$('.resolve-escalation').on('click', function () {
			var $button = $(this);
			var id = $button.data('id');
			var $row = $button.closest('tr');

			if (!confirm('¿Marcar como resuelto?')) {
				return;
			}

			$.ajax({
				url: flavorChatAdmin?.ajaxUrl || ajaxurl,
				method: 'POST',
				data: {
					action: 'flavor_chat_resolve_escalation',
					nonce: flavorChatAdmin?.nonce || '',
					escalation_id: id
				},
				success: function (response) {
					if (response.success) {
						$row.fadeOut(function () {
							$(this).remove();
						});
					} else {
						alert('Error al resolver');
					}
				}
			});
		});

		// ==========================================
		// FAQs DINÁMICOS
		// ==========================================

		$(document).on('click', '.remove-faq', function () {
			$(this).closest('.faq-item').remove();
		});

		$(document).on('click', '.remove-custom-action', function () {
			$(this).closest('.custom-action-item').remove();
		});
	});

})(jQuery);
