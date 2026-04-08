/**
 * Chat IA Admin JavaScript
 *
 * @package CalendarioExperiencias
 * @subpackage ChatIA
 */

(function ($) {
	'use strict';

	const config = window.chatIaAdmin || {};
	const strings = config.strings || {};

	/**
     * Inicializa el admin
     */
	function init() {
		bindEvents();
		initAnalytics();
	}

	/**
     * Vincula eventos
     */
	function bindEvents() {
		// Verificar API key
		$('#verify-api-key').on('click', verifyApiKey);

		// Añadir FAQ
		$('#add-faq').on('click', addFaq);

		// Eliminar FAQ
		$(document).on('click', '.remove-faq', removeFaq);

		// Actualizar ticket de escalado
		$(document).on('click', '.update-ticket', updateTicket);

		// Refrescar analíticas
		$('#refresh-analytics').on('click', loadAnalytics);
		$('#analytics-period').on('change', loadAnalytics);

		// Acciones personalizadas
		$('#add-custom-action').on('click', addCustomAction);
		$(document).on('click', '.remove-custom-action', removeCustomAction);

		// Autoconfiguración IA
		$('#chat-ia-autoconfig').on('click', function () { runAutoconfig(false); });
		$('#chat-ia-autoconfig-refresh').on('click', function () { runAutoconfig(true); });

		// Autoconfiguración IA para sección de apps móviles (solo aplica datos móviles)
		$('#chat-ia-autoconfig-mobile').on('click', function () { runAutoconfigMobile(); });

		// Regenerar token de seguridad admin
		$('#regenerate-admin-token').on('click', function () { regenerateAdminToken(); });

		// Avatar uploader
		$('#chat-ia-upload-avatar').on('click', openAvatarUploader);
		$('#chat-ia-remove-avatar').on('click', removeAvatar);

		// Modo de visualización - mostrar/ocultar opciones
		$('#chat_ia_display_mode').on('change', function () {
			toggleDisplayModeOptions($(this).val());
		});
		// Ejecutar al cargar
		toggleDisplayModeOptions($('#chat_ia_display_mode').val());
	}

	/**
     * Muestra/oculta opciones según el modo de visualización
     */
	function toggleDisplayModeOptions(mode) {
		console.log('[Chat IA Admin] toggleDisplayModeOptions:', mode);

		var $floatingOptions = $('.chat-ia-floating-option');
		var $sidebarOptions = $('.chat-ia-sidebar-option');

		console.log('[Chat IA Admin] Floating options found:', $floatingOptions.length);
		console.log('[Chat IA Admin] Sidebar options found:', $sidebarOptions.length);

		if (mode === 'sidebar') {
			$floatingOptions.css('display', 'none');
			$sidebarOptions.css('display', 'table-row');
		} else {
			$floatingOptions.css('display', 'table-row');
			$sidebarOptions.css('display', 'none');
		}
	}

	/**
     * Abre el media uploader para seleccionar avatar
     */
	function openAvatarUploader() {
		// Si ya existe el frame, abrirlo
		if (window.chatIaAvatarFrame) {
			window.chatIaAvatarFrame.open();
			return;
		}

		// Crear el frame del media uploader
		window.chatIaAvatarFrame = wp.media({
			title: 'Seleccionar Avatar',
			button: {
				text: 'Usar como avatar'
			},
			library: {
				type: 'image'
			},
			multiple: false
		});

		// Cuando se selecciona una imagen
		window.chatIaAvatarFrame.on('select', function () {
			const attachment = window.chatIaAvatarFrame.state().get('selection').first().toJSON();
			const imageUrl = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			// Actualizar campo oculto
			$('#chat_ia_avatar_url').val(attachment.url);

			// Actualizar preview
			$('#chat-ia-avatar-preview').html(
				'<img src="' + imageUrl + '" alt="Avatar" style="max-width: 80px; max-height: 80px; border-radius: 50%;">'
			);

			// Mostrar botón eliminar
			$('#chat-ia-remove-avatar').show();
		});

		// Abrir el frame
		window.chatIaAvatarFrame.open();
	}

	/**
     * Elimina el avatar seleccionado
     */
	function removeAvatar() {
		// Limpiar campo
		$('#chat_ia_avatar_url').val('');

		// Restaurar preview por defecto
		$('#chat-ia-avatar-preview').html(
			'<div style="width: 80px; height: 80px; border-radius: 50%; background: #1e3a5f; display: flex; align-items: center; justify-content: center;">' +
            '<svg viewBox="0 0 24 24" fill="white" width="40" height="40">' +
            '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>' +
            '</svg>' +
            '</div>'
		);

		// Ocultar botón eliminar
		$('#chat-ia-remove-avatar').hide();
	}

	/**
     * Ejecuta la autoconfiguración con IA (optimizado)
     */
	function runAutoconfig(forceRefresh) {
		const $btn = $('#chat-ia-autoconfig');
		const $refreshBtn = $('#chat-ia-autoconfig-refresh');
		const $status = $('#chat-ia-autoconfig-status');

		if ($btn.prop('disabled')) {return;}

		$btn.prop('disabled', true);
		if ($refreshBtn.length) {$refreshBtn.prop('disabled', true);}

		const statusMsg = forceRefresh
			? '⏳ Regenerando análisis...'
			: '⏳ Analizando sitio...';
		$status.html('<span style="color:#856404;">' + statusMsg + '</span>');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_autoconfig',
				nonce: config.nonce,
				force_refresh: forceRefresh ? '1' : ''
			},
			timeout: 60000, // Reducido a 60s gracias a optimización
			success: function (response) {
				$btn.prop('disabled', false);
				if ($refreshBtn.length) {$refreshBtn.prop('disabled', false);}

				if (response.success && response.data) {
					$status.html('<span style="color:#155724;">✓ Completado. Rellenando campos...</span>');
					applyAutoconfigData(response.data);

					setTimeout(function () {
						$status.html('<span style="color:#155724;">✓ Configuración aplicada. Revisa y guarda los cambios.</span>');
					}, 1000);
				} else {
					$status.html('<span style="color:#721c24;">✗ ' + (response.data?.error || 'Error desconocido') + '</span>');
				}
			},
			error: function (xhr, status, error) {
				$btn.prop('disabled', false);
				if ($refreshBtn.length) {$refreshBtn.prop('disabled', false);}
				let errorMsg = 'Error de conexión';
				if (status === 'timeout') {
					errorMsg = 'Tiempo de espera agotado. Intenta de nuevo.';
				}
				$status.html('<span style="color:#721c24;">✗ ' + errorMsg + '</span>');
			}
		});
	}

	/**
     * Ejecuta la autoconfiguración solo para la sección de Apps Móviles
     */
	function runAutoconfigMobile() {
		const $btn = $('#chat-ia-autoconfig-mobile');
		const $status = $('#chat-ia-autoconfig-mobile-status');

		if ($btn.prop('disabled')) {return;}

		$btn.prop('disabled', true);
		$status.html('<span style="color:#856404;">⏳ Detectando datos del sitio...</span>');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_autoconfig',
				nonce: config.nonce,
				force_refresh: ''
			},
			timeout: 60000,
			success: function (response) {
				$btn.prop('disabled', false);

				if (response.success && response.data) {
					var data = response.data;

					// Aplicar solo datos de contacto y ubicación móvil
					if (data.mobile_contact) {
						var mc = data.mobile_contact;
						if (mc.phone) {$('input[name="chat_ia_settings[mobile_apps][contact][phone]"]').val(mc.phone);}
						if (mc.email) {$('input[name="chat_ia_settings[mobile_apps][contact][email]"]').val(mc.email);}
						if (mc.whatsapp) {$('input[name="chat_ia_settings[mobile_apps][contact][whatsapp]"]').val(mc.whatsapp);}
						if (mc.schedule) {$('textarea[name="chat_ia_settings[mobile_apps][contact][schedule]"]').val(mc.schedule);}
					} else if (data.business_info) {
						// Fallback: usar datos de business_info
						var bi = data.business_info;
						if (bi.phone) {$('input[name="chat_ia_settings[mobile_apps][contact][phone]"]').val(bi.phone);}
						if (bi.email) {$('input[name="chat_ia_settings[mobile_apps][contact][email]"]').val(bi.email);}
						if (bi.schedule) {$('textarea[name="chat_ia_settings[mobile_apps][contact][schedule]"]').val(bi.schedule);}
					}

					if (data.mobile_location) {
						var ml = data.mobile_location;
						if (ml.address) {$('textarea[name="chat_ia_settings[mobile_apps][location][address]"]').val(ml.address);}
						if (ml.lat) {$('input[name="chat_ia_settings[mobile_apps][location][lat]"]').val(ml.lat);}
						if (ml.lng) {$('input[name="chat_ia_settings[mobile_apps][location][lng]"]').val(ml.lng);}
						if (ml.map_url) {$('input[name="chat_ia_settings[mobile_apps][location][map_url]"]').val(ml.map_url);}
					} else if (data.business_info && data.business_info.address) {
						// Fallback: usar dirección de business_info
						$('textarea[name="chat_ia_settings[mobile_apps][location][address]"]').val(data.business_info.address);
					}

					// Highlight campos actualizados
					$('#chat-ia-settings-mobile_apps input, #chat-ia-settings-mobile_apps textarea').filter(function () {
						return $(this).val() !== '';
					}).css('background-color', '#fffde7');

					setTimeout(function () {
						$('#chat-ia-settings-mobile_apps input, #chat-ia-settings-mobile_apps textarea').css('background-color', '');
					}, 3000);

					$status.html('<span style="color:#155724;">✓ Datos detectados. Revisa y guarda.</span>');
				} else {
					$status.html('<span style="color:#721c24;">✗ ' + (response.data?.error || 'No se encontraron datos') + '</span>');
				}
			},
			error: function (xhr, status, error) {
				$btn.prop('disabled', false);
				let errorMsg = 'Error de conexión';
				if (status === 'timeout') {
					errorMsg = 'Tiempo de espera agotado';
				}
				$status.html('<span style="color:#721c24;">✗ ' + errorMsg + '</span>');
			}
		});
	}

	/**
     * Regenera el token de seguridad para la app Admin
     */
	function regenerateAdminToken() {
		const $btn = $('#regenerate-admin-token');
		const $msg = $('#token-regenerated-msg');

		if ($btn.prop('disabled')) {return;}

		if (!confirm('¿Regenerar el token de seguridad?\n\nLas apps Admin existentes deberán volver a escanear el código QR para conectarse.')) {
			return;
		}

		$btn.prop('disabled', true).text('Regenerando...');
		$msg.hide();

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_regenerate_admin_token',
				nonce: config.nonce
			},
			success: function (response) {
				$btn.prop('disabled', false).html('🔄 Regenerar Token Admin');

				if (response.success) {
					$msg.show();
					// Recargar la página para actualizar el QR
					setTimeout(function () {
						location.reload();
					}, 2000);
				} else {
					alert('Error: ' + (response.data?.error || 'No se pudo regenerar el token'));
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('🔄 Regenerar Token Admin');
				alert('Error de conexión al regenerar token');
			}
		});
	}

	/**
     * Aplica los datos de autoconfiguración a los campos del formulario
     */
	function applyAutoconfigData(data) {
		console.log('[Chat IA Autoconfig] Datos recibidos:', data);

		// Normalizar estructura - soporta tanto nested (business_info) como flat (address directo)
		var businessInfo = data.business_info || data;

		console.log('[Chat IA Autoconfig] Business info normalizado:', businessInfo);

		const $address = $('#business_address');
		const $phone = $('#business_phone');
		const $email = $('#business_email');
		const $schedule = $('#business_schedule');
		const $description = $('#business_description');

		console.log('[Chat IA Autoconfig] Campos encontrados:', {
			address: $address.length,
			phone: $phone.length,
			email: $email.length,
			schedule: $schedule.length,
			description: $description.length
		});

		// Aplicar valores si existen
		if (businessInfo.address) {$address.val(businessInfo.address);}
		if (businessInfo.phone) {$phone.val(businessInfo.phone);}
		if (businessInfo.email) {$email.val(businessInfo.email);}
		if (businessInfo.schedule) {$schedule.val(businessInfo.schedule);}
		if (businessInfo.description) {$description.val(businessInfo.description);}

		// Policies - usar los nombres correctos del formulario
		if (data.policies) {
			console.log('[Chat IA Autoconfig] Aplicando policies:', data.policies);
			$('textarea[name="chat_ia_settings[policies][cancellation]"]').val(data.policies.cancellation || '');
			$('textarea[name="chat_ia_settings[policies][groups]"]').val(data.policies.groups || '');
			$('textarea[name="chat_ia_settings[policies][accessibility]"]').val(data.policies.accessibility || '');
		}

		// FAQs - limpiar existentes y añadir nuevas
		if (data.faqs && data.faqs.length > 0) {
			const $container = $('#faqs-container');
			$container.empty();

			data.faqs.forEach(function (faq, index) {
				const html = `
                    <div class="faq-item" data-index="${index}">
                        <table class="form-table" style="margin:0;background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">
                            <tr>
                                <th style="width:100px;">Pregunta (ES)</th>
                                <td>
                                    <input type="text"
                                           name="chat_ia_settings[faqs][${index}][question]"
                                           value="${escapeHtml(faq.question || '')}"
                                           class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th>Respuesta (ES)</th>
                                <td>
                                    <textarea name="chat_ia_settings[faqs][${index}][answer]"
                                              rows="2"
                                              class="large-text">${escapeHtml(faq.answer || '')}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <th>Pregunta (EU)</th>
                                <td>
                                    <input type="text"
                                           name="chat_ia_settings[faqs][${index}][question_eu]"
                                           value=""
                                           class="large-text"
                                           placeholder="Traducción en euskera (opcional)">
                                </td>
                            </tr>
                            <tr>
                                <th>Respuesta (EU)</th>
                                <td>
                                    <textarea name="chat_ia_settings[faqs][${index}][answer_eu]"
                                              rows="2"
                                              class="large-text"
                                              placeholder="Traducción en euskera (opcional)"></textarea>
                                    <button type="button" class="button remove-faq" style="margin-top:8px;">Eliminar FAQ</button>
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
				$container.append(html);
			});
		}

		// Suggested welcome message
		if (data.suggested_welcome) {
			const $welcome = $('textarea[name="chat_ia_settings[welcome_message]"]');
			if ($welcome.length) {
				$welcome.val(data.suggested_welcome);
				console.log('[Chat IA Autoconfig] Welcome message aplicado');
			}
		}

		// Suggested assistant name
		if (data.suggested_name) {
			const $name = $('input[name="chat_ia_settings[assistant_name]"]');
			if ($name.length) {
				$name.val(data.suggested_name);
				console.log('[Chat IA Autoconfig] Assistant name aplicado');
			}
		}

		// === NUEVOS: Datos para Apps Móviles ===

		// Contacto móvil
		if (data.mobile_contact) {
			console.log('[Chat IA Autoconfig] Aplicando mobile_contact:', data.mobile_contact);
			var mc = data.mobile_contact;
			if (mc.phone) {$('input[name="chat_ia_settings[mobile_apps][contact][phone]"]').val(mc.phone);}
			if (mc.email) {$('input[name="chat_ia_settings[mobile_apps][contact][email]"]').val(mc.email);}
			if (mc.whatsapp) {$('input[name="chat_ia_settings[mobile_apps][contact][whatsapp]"]').val(mc.whatsapp);}
			if (mc.schedule) {$('textarea[name="chat_ia_settings[mobile_apps][contact][schedule]"]').val(mc.schedule);}
		}

		// Ubicación móvil
		if (data.mobile_location) {
			console.log('[Chat IA Autoconfig] Aplicando mobile_location:', data.mobile_location);
			var ml = data.mobile_location;
			if (ml.address) {$('textarea[name="chat_ia_settings[mobile_apps][location][address]"]').val(ml.address);}
			if (ml.lat) {$('input[name="chat_ia_settings[mobile_apps][location][lat]"]').val(ml.lat);}
			if (ml.lng) {$('input[name="chat_ia_settings[mobile_apps][location][lng]"]').val(ml.lng);}
			if (ml.map_url) {$('input[name="chat_ia_settings[mobile_apps][location][map_url]"]').val(ml.map_url);}
		}

		// Log resumen de campos rellenados
		console.log('[Chat IA Autoconfig] Resumen de campos rellenados:', {
			address: $address.val(),
			phone: $phone.val(),
			email: $email.val(),
			schedule: $schedule.val(),
			description: $description.val() ? $description.val().substring(0, 50) + '...' : '',
			faqs: data.faqs ? data.faqs.length : 0,
			policies: data.policies ? 'Sí' : 'No',
			mobile_contact: data.mobile_contact ? 'Sí' : 'No',
			mobile_location: data.mobile_location ? 'Sí' : 'No'
		});

		// Highlight changed fields
		$('input, textarea').filter(function () {
			return $(this).val() !== '';
		}).css('background-color', '#fffde7');

		setTimeout(function () {
			$('input, textarea').css('background-color', '');
		}, 3000);
	}

	/**
     * Escapa HTML para evitar XSS
     */
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, m => map[m]);
	}

	/**
     * Añade una nueva acción personalizada
     */
	function addCustomAction() {
		const $container = $('#custom-actions-container');
		const index = Date.now(); // Usar timestamp como índice único

		const html = `
            <div class="custom-action-item" data-index="${index}">
                <table class="form-table" style="margin:0;background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">
                    <tr>
                        <th style="width:100px;">Texto (ES)</th>
                        <td>
                            <input type="text"
                                   name="chat_ia_settings[custom_quick_actions][${index}][label]"
                                   value=""
                                   class="regular-text"
                                   placeholder="Ej: Ver horarios">
                        </td>
                        <th style="width:100px;">Texto (EU)</th>
                        <td>
                            <input type="text"
                                   name="chat_ia_settings[custom_quick_actions][${index}][label_eu]"
                                   value=""
                                   class="regular-text"
                                   placeholder="Ej: Ordutegiak ikusi">
                        </td>
                    </tr>
                    <tr>
                        <th>Mensaje</th>
                        <td colspan="3">
                            <input type="text"
                                   name="chat_ia_settings[custom_quick_actions][${index}][prompt]"
                                   value=""
                                   class="large-text"
                                   placeholder="Ej: ¿Cuáles son los horarios de apertura?">
                            <button type="button" class="button remove-custom-action" style="margin-left:10px;">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
        `;

		$container.append(html);
	}

	/**
     * Elimina una acción personalizada
     */
	function removeCustomAction() {
		$(this).closest('.custom-action-item').remove();
	}

	/**
     * Verifica la API key
     */
	function verifyApiKey() {
		const apiKey = $('#chat_ia_api_key').val();
		const $status = $('#api-key-status');
		const $btn = $('#verify-api-key');

		if (!apiKey) {
			$status.removeClass('success error loading')
				.addClass('error')
				.text('Introduce una API key');
			return;
		}

		$btn.prop('disabled', true);
		$status.removeClass('success error')
			.addClass('loading')
			.text(strings.verifying || 'Verificando...');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_verify_api_key',
				nonce: config.nonce,
				api_key: apiKey
			},
			success: function (response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$status.removeClass('loading error')
						.addClass('success')
						.text(strings.validKey || 'API key válida');
				} else {
					$status.removeClass('loading success')
						.addClass('error')
						.text(strings.invalidKey || 'API key no válida');
				}
			},
			error: function () {
				$btn.prop('disabled', false);
				$status.removeClass('loading success')
					.addClass('error')
					.text(strings.error || 'Error de conexión');
			}
		});
	}

	/**
     * Añade una nueva FAQ
     */
	function addFaq() {
		const $container = $('#faqs-container');
		const index = $container.find('.faq-item').length;

		const html = `
            <div class="faq-item" data-index="${index}">
                <div class="faq-header">
                    <strong>FAQ #${index + 1}</strong>
                    <button type="button" class="button remove-faq">Eliminar</button>
                </div>
                <table class="form-table">
                    <tr>
                        <th><label>Pregunta (ES)</label></th>
                        <td>
                            <input type="text"
                                   name="chat_ia_settings[faqs][${index}][question]"
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Respuesta (ES)</label></th>
                        <td>
                            <textarea name="chat_ia_settings[faqs][${index}][answer]"
                                      rows="3"
                                      class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Pregunta (EU)</label></th>
                        <td>
                            <input type="text"
                                   name="chat_ia_settings[faqs][${index}][question_eu]"
                                   class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label>Respuesta (EU)</label></th>
                        <td>
                            <textarea name="chat_ia_settings[faqs][${index}][answer_eu]"
                                      rows="3"
                                      class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
            </div>
        `;

		$container.append(html);
	}

	/**
     * Elimina una FAQ
     */
	function removeFaq() {
		if (confirm(strings.confirmDelete || '¿Estás seguro de que deseas eliminar este elemento?')) {
			$(this).closest('.faq-item').remove();
			reindexFaqs();
		}
	}

	/**
     * Reindexa las FAQs después de eliminar
     */
	function reindexFaqs() {
		$('#faqs-container .faq-item').each(function (index) {
			$(this).attr('data-index', index);
			$(this).find('.faq-header strong').text('FAQ #' + (index + 1));

			// Actualizar nombres de campos
			$(this).find('input, textarea').each(function () {
				const name = $(this).attr('name');
				if (name) {
					const newName = name.replace(/\[faqs\]\[\d+\]/, '[faqs][' + index + ']');
					$(this).attr('name', newName);
				}
			});
		});
	}

	/**
     * Actualiza un ticket de escalado
     */
	function updateTicket() {
		const $row = $(this).closest('tr');
		const ticketId = $row.data('ticket-id');
		const status = $row.find('.ticket-status').val();
		const $btn = $(this);

		$btn.prop('disabled', true).text(strings.loading || 'Cargando...');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_update_escalation',
				nonce: config.nonce,
				ticket_id: ticketId,
				status: status
			},
			success: function (response) {
				$btn.prop('disabled', false).text('Actualizar');
				if (response.success) {
					// Feedback visual
					$row.css('background-color', '#d4edda');
					setTimeout(() => $row.css('background-color', ''), 1000);
				} else {
					alert(response.data.error || strings.error);
				}
			},
			error: function () {
				$btn.prop('disabled', false).text('Actualizar');
				alert(strings.error || 'Error de conexión');
			}
		});
	}

	/**
     * Inicializa las analíticas
     */
	function initAnalytics() {
		var $container = $('#analytics-container');
		console.log('[Chat IA] initAnalytics - Container encontrado:', $container.length > 0);
		if ($container.length) {
			loadAnalytics();
		}
	}

	/**
     * Carga las analíticas
     */
	function loadAnalytics() {
		const periodo = $('#analytics-period').val() || 'week';
		const $container = $('#analytics-container');

		$container.css('opacity', '0.5');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_get_stats',
				nonce: config.nonce,
				periodo: periodo
			},
			success: function (response) {
				$container.css('opacity', '1');

				if (response.success) {
					const chat = response.data.chat || {};
					const escalation = response.data.escalation || {};
					const adminChat = response.data.admin_chat || {};

					// Actualizar valores chat frontend
					$('#stat-conversations').text(chat.total_conversaciones || 0);
					$('#stat-reservations').text(chat.con_reserva || 0);
					$('#stat-conversion').text((chat.tasa_conversion || 0) + '%');
					$('#stat-escalated').text(chat.escaladas || 0);
					$('#stat-messages').text(chat.total_mensajes || 0);
					$('#stat-avg-messages').text(chat.promedio_mensajes || 0);
					$('#stat-tokens').text(formatNumber(chat.total_tokens || 0));

					// Actualizar valores chat admin
					console.log('[Chat IA] Admin stats recibidas:', adminChat);
					var adminMessages = adminChat.total_mensajes !== undefined ? adminChat.total_mensajes : 0;
					var adminSessions = adminChat.total_sesiones !== undefined ? adminChat.total_sesiones : 0;
					$('#stat-admin-messages').text(adminMessages);
					$('#stat-admin-sessions').text(adminSessions);

					// Mostrar último uso
					if (adminChat.ultimo_uso) {
						var lastUsedDate = new Date(adminChat.ultimo_uso);
						var options = { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };
						var lastUsed = lastUsedDate.toLocaleDateString('es-ES', options);
						$('#stat-admin-last-use').text(lastUsed);
					} else {
						$('#stat-admin-last-use').text('Nunca');
					}

					// Calcular coste estimado según proveedor/modelo
					var provider = response.data.provider || {};
					var costPerMillion = calculateCostPerMillion(provider.provider, provider.model);
					var totalTokens = chat.total_tokens || 0;
					var estimatedCost = (totalTokens / 1000000 * costPerMillion).toFixed(2);
					$('#stat-cost').text('~$' + estimatedCost);
					$('#stat-cost').attr('title', 'Basado en ' + (provider.model || 'modelo desconocido'));

					// Actualizar estadísticas de apps móviles
					var mobile = response.data.mobile || {};
					console.log('[Chat IA] Mobile stats recibidas:', mobile);
					$('#stat-mobile-requests').text(mobile.periodo_requests || 0);
					$('#stat-mobile-devices').text(mobile.active_devices || 0);
					$('#stat-mobile-android').text((mobile.by_platform || {}).android || 0);
					$('#stat-mobile-ios').text((mobile.by_platform || {}).ios || 0);
					$('#stat-mobile-client').text((mobile.by_app_type || {}).client || 0);
					$('#stat-mobile-admin').text((mobile.by_app_type || {}).admin || 0);

					// Colorear según valores
					const conversionRate = chat.tasa_conversion || 0;
					$('#stat-conversion')
						.removeClass('positive negative')
						.addClass(conversionRate >= 10 ? 'positive' : conversionRate < 5 ? 'negative' : '');

					const escalationRate = chat.tasa_escalado || 0;
					$('#stat-escalated')
						.removeClass('positive negative')
						.addClass(escalationRate <= 20 ? 'positive' : escalationRate > 40 ? 'negative' : '');
				}
			},
			error: function () {
				$container.css('opacity', '1');
				console.error('Error loading analytics');
			}
		});
	}

	/**
     * Calcula el coste por millón de tokens según proveedor y modelo
     * Precios actualizados enero 2025 (promedio ponderado input 70% + output 30%)
     */
	function calculateCostPerMillion(provider, model) {
		// Precios por millón de tokens: [input, output]
		var prices = {
			// Claude (Anthropic)
			'claude-sonnet-4-20250514': [3, 15],      // Sonnet 4
			'claude-3-5-sonnet-latest': [3, 15],      // Sonnet 3.5
			'claude-3-5-sonnet-20241022': [3, 15],
			'claude-3-haiku-20240307': [0.25, 1.25],  // Haiku
			'claude-3-5-haiku-latest': [0.80, 4],     // Haiku 3.5
			'claude-opus-4-20250514': [15, 75],       // Opus 4
			'claude-3-opus-latest': [15, 75],         // Opus 3

			// OpenAI
			'gpt-4o': [2.50, 10],
			'gpt-4o-mini': [0.15, 0.60],
			'gpt-4-turbo': [10, 30],
			'gpt-3.5-turbo': [0.50, 1.50],

			// DeepSeek (muy barato)
			'deepseek-chat': [0.14, 0.28],
			'deepseek-coder': [0.14, 0.28],

			// Mistral
			'mistral-small-latest': [0.20, 0.60],
			'mistral-medium-latest': [2.70, 8.10],
			'mistral-large-latest': [2, 6],
		};

		// Buscar precio del modelo
		var modelPrices = prices[model];

		if (!modelPrices) {
			// Fallbacks por proveedor
			switch (provider) {
				case 'claude':
					modelPrices = [3, 15]; // Sonnet por defecto
					break;
				case 'openai':
					modelPrices = [0.15, 0.60]; // GPT-4o-mini por defecto
					break;
				case 'deepseek':
					modelPrices = [0.14, 0.28];
					break;
				case 'mistral':
					modelPrices = [0.20, 0.60];
					break;
				default:
					modelPrices = [3, 15]; // Claude Sonnet como fallback
			}
		}

		// Promedio ponderado: ~70% input, ~30% output (típico en chatbots)
		return modelPrices[0] * 0.7 + modelPrices[1] * 0.3;
	}

	/**
     * Formatea un número grande
     */
	function formatNumber(num) {
		if (num >= 1000000) {
			return (num / 1000000).toFixed(1) + 'M';
		}
		if (num >= 1000) {
			return (num / 1000).toFixed(1) + 'K';
		}
		return num.toString();
	}

	// Inicializar cuando el DOM esté listo
	$(document).ready(init);

})(jQuery);
