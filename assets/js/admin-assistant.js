/**
 * Chat IA Admin Assistant - JavaScript
 */

(function ($) {
	'use strict';

	const config = window.chatIAAdminAssistant || {
		ajaxUrl: '/wp-admin/admin-ajax.php',
		nonce: '',
		strings: {}
	};

	let isProcessing = false;

	/**
     * Inicialización
     */
	function init() {
		bindEvents();
		autoResizeTextarea();
	}

	/**
     * Vincula eventos
     */
	function bindEvents() {
		// Enviar mensaje
		$('#assistant-form').on('submit', handleSubmit);

		// Auto-resize textarea
		$('#assistant-input').on('input', autoResizeTextarea);

		// Enter para enviar (shift+enter para nueva línea)
		$('#assistant-input').on('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				$('#assistant-form').trigger('submit');
			}
		});

		// Limpiar chat
		$('#clear-chat').on('click', clearChat);

		// Acciones rápidas
		$('.quick-action').on('click', function () {
			const prompt = $(this).data('prompt');
			if (prompt && !isProcessing) {
				$('#assistant-input').val(prompt);
				$('#assistant-form').trigger('submit');
			}
		});
	}

	/**
     * Auto-resize del textarea
     */
	function autoResizeTextarea() {
		const textarea = document.getElementById('assistant-input');
		if (textarea) {
			textarea.style.height = 'auto';
			textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
		}
	}

	/**
     * Maneja el envío del formulario
     */
	function handleSubmit(e) {
		e.preventDefault();

		const $input = $('#assistant-input');
		const message = $input.val().trim();

		if (!message || isProcessing) {
			return;
		}

		// Limpiar input
		$input.val('');
		autoResizeTextarea();

		// Añadir mensaje del usuario
		addMessage('user', message);

		// Mostrar indicador de escritura
		showTyping();

		// Enviar al servidor
		sendMessage(message);
	}

	/**
     * Envía mensaje al servidor
     */
	function sendMessage(message) {
		isProcessing = true;
		updateInputState();

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_admin_assistant_message',
				nonce: config.nonce,
				message: message
			},
			success: function (response) {
				hideTyping();
				isProcessing = false;
				updateInputState();

				if (response.success) {
					addMessage('bot', response.data.response, response.data.actions || []);
				} else {
					addMessage('bot', '**Error:** ' + (response.data.error || config.strings.error));
				}
			},
			error: function () {
				hideTyping();
				isProcessing = false;
				updateInputState();
				addMessage('bot', '**Error de conexión.** Por favor, inténtalo de nuevo.');
			}
		});
	}

	/**
     * Añade un mensaje al chat
     */
	function addMessage(role, content, actions) {
		const $messages = $('#assistant-messages');
		const isBot = role === 'bot';

		const avatarIcon = isBot ? 'admin-generic' : 'admin-users';
		const roleClass = isBot ? 'assistant-message-bot' : 'assistant-message-user';

		// Parsear markdown básico
		const parsedContent = parseMarkdown(content);

		// Renderizar acciones si existen
		const actionsHtml = isBot && actions && actions.length > 0 ? renderActions(actions) : '';

		const html = `
            <div class="assistant-message ${roleClass}">
                <div class="message-avatar">
                    <span class="dashicons dashicons-${avatarIcon}"></span>
                </div>
                <div class="message-content">
                    ${parsedContent}
                    ${actionsHtml}
                </div>
            </div>
        `;

		$messages.append(html);
		scrollToBottom();
	}

	/**
     * Renderiza botones de accion
     */
	function renderActions(actions) {
		if (!actions || !actions.length) {return '';}

		const buttons = actions.map(action => {
			const icon = action.icon ? `<span class="dashicons dashicons-${action.icon}"></span>` : '';
			const dataAttrs = [];

			if (action.url) {dataAttrs.push(`data-url="${action.url}"`);}
			if (action.shortcut) {dataAttrs.push(`data-shortcut="${action.shortcut}"`);}
			if (action.params) {dataAttrs.push(`data-params='${JSON.stringify(action.params)}'`);}
			if (action.action) {dataAttrs.push(`data-action="${action.action}"`);}

			return `<button class="message-action-btn" ${dataAttrs.join(' ')}>${icon}${action.label}</button>`;
		}).join('');

		return `<div class="message-actions">${buttons}</div>`;
	}

	/**
     * Maneja clics en botones de accion
     */
	$(document).on('click', '.message-action-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);

		// Accion URL - abrir en nueva pestana
		if ($btn.data('url')) {
			window.open($btn.data('url'), '_blank');
			return;
		}

		// Accion de copiar codigo
		if ($btn.data('action') === 'copy_code') {
			const $content = $btn.closest('.message-content');
			const $code = $content.find('code, pre');
			if ($code.length) {
				const text = $code.first().text();
				navigator.clipboard.writeText(text).then(() => {
					const originalText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes"></span> Copiado');
					setTimeout(() => $btn.html(originalText), 2000);
				});
			}
			return;
		}

		// Accion shortcut - usar AdminShortcuts si esta disponible
		if ($btn.data('shortcut') && window.AdminShortcuts) {
			const params = $btn.data('params') || {};
			window.AdminShortcuts.executeShortcut($btn.data('shortcut'), params);
		}
	});

	/**
     * Parsea markdown básico
     */
	function parseMarkdown(text) {
		if (!text) {return '';}

		// Escapar HTML
		text = escapeHtml(text);

		// Bloques de código
		text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

		// Código inline
		text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

		// Negrita
		text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

		// Cursiva
		text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');

		// Listas desordenadas
		text = text.replace(/^[\-\*] (.+)$/gm, '<li>$1</li>');
		text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

		// Listas ordenadas
		text = text.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

		// Tablas markdown
		text = parseMarkdownTable(text);

		// Headers
		text = text.replace(/^### (.+)$/gm, '<h4>$1</h4>');
		text = text.replace(/^## (.+)$/gm, '<h3>$1</h3>');
		text = text.replace(/^# (.+)$/gm, '<h2>$1</h2>');

		// Párrafos (doble salto de línea)
		text = text.replace(/\n\n/g, '</p><p>');
		text = '<p>' + text + '</p>';

		// Limpiar párrafos vacíos
		text = text.replace(/<p><\/p>/g, '');
		text = text.replace(/<p>(<[hulo])/g, '$1');
		text = text.replace(/(<\/[hulo][^>]*>)<\/p>/g, '$1');

		return text;
	}

	/**
     * Parsea tablas markdown
     */
	function parseMarkdownTable(text) {
		const tableRegex = /\|(.+)\|\n\|[-:\s|]+\|\n((?:\|.+\|\n?)+)/g;

		return text.replace(tableRegex, function (match, headerRow, bodyRows) {
			const headers = headerRow.split('|').filter(h => h.trim());
			const rows = bodyRows.trim().split('\n');

			let tableHtml = '<table><thead><tr>';
			headers.forEach(h => {
				tableHtml += '<th>' + h.trim() + '</th>';
			});
			tableHtml += '</tr></thead><tbody>';

			rows.forEach(row => {
				const cells = row.split('|').filter(c => c.trim() !== '');
				if (cells.length > 0) {
					tableHtml += '<tr>';
					cells.forEach(c => {
						tableHtml += '<td>' + c.trim() + '</td>';
					});
					tableHtml += '</tr>';
				}
			});

			tableHtml += '</tbody></table>';
			return tableHtml;
		});
	}

	/**
     * Escapa HTML
     */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
     * Muestra indicador de escritura
     */
	function showTyping() {
		const $messages = $('#assistant-messages');
		const html = `
            <div class="assistant-message assistant-message-bot" id="typing-message">
                <div class="message-avatar">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
		$messages.append(html);
		scrollToBottom();
	}

	/**
     * Oculta indicador de escritura
     */
	function hideTyping() {
		$('#typing-message').remove();
	}

	/**
     * Scroll al final
     */
	function scrollToBottom() {
		const $messages = $('#assistant-messages');
		$messages.scrollTop($messages[0].scrollHeight);
	}

	/**
     * Actualiza estado del input
     */
	function updateInputState() {
		const $wrapper = $('.admin-assistant-input');
		const $button = $('#assistant-send');

		if (isProcessing) {
			$wrapper.addClass('loading');
			$button.prop('disabled', true);
		} else {
			$wrapper.removeClass('loading');
			$button.prop('disabled', false);
		}
	}

	/**
     * Limpia el chat
     */
	function clearChat() {
		if (!confirm('¿Limpiar la conversación?')) {
			return;
		}

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_admin_assistant_clear',
				nonce: config.nonce
			},
			success: function (response) {
				if (response.success) {
					// Limpiar mensajes excepto el de bienvenida
					const $messages = $('#assistant-messages');
					const $welcome = $messages.find('.assistant-message').first().clone();
					$messages.empty().append($welcome);
				}
			}
		});
	}

	// Inicializar cuando el DOM esté listo
	$(document).ready(init);

})(jQuery);
