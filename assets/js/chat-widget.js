/**
 * Chat IA Widget JavaScript
 *
 * @package CalendarioExperiencias
 * @subpackage ChatIA
 */

(function ($) {
	'use strict';

	// Configuración global
	const config = window.flavorChatConfig || window.chatIaConfig || {};
	const strings = config.strings || {};

	// Estado del widget
	const state = {
		sessionId: null,
		language: config.language || 'es',
		context: config.context || detectPageContext(),
		isOpen: false,
		isLoading: false,
		draft: null,
		messagesCount: 0
	};

	/**
     * Detecta el contexto de la página actual
     * Retorna 'landing' si estamos en la landing de Flavor Platform
     */
	function detectPageContext() {
		// Detectar por clase en body (la landing usa 'flavor-landing')
		if (document.body && (
			document.body.classList.contains('flavor-landing') ||
            document.body.classList.contains('flavor-landing-page')
		)) {
			return 'landing';
		}
		// Detectar por URL
		if (window.location.pathname.includes('/flavor-landing') ||
            window.location.pathname.includes('/plataforma')) {
			return 'landing';
		}
		// Detectar por elemento de la landing
		if (document.querySelector('.fl-hero') ||
            document.querySelector('.fl-landing-container') ||
            document.querySelector('#fl-hero')) {
			return 'landing';
		}
		return '';
	}

	// Elementos DOM
	let $widget, $trigger, $messages, $input, $form, $typing, $draft, $escalation;

	/**
     * Inicializa el widget
     */
	function init() {
		console.log('[Chat IA] Inicializando widget...');

		// Detectar contexto ahora que el DOM está listo
		if (!state.context) {
			state.context = detectPageContext();
			console.log('[Chat IA] Contexto detectado:', state.context);
		}

		cacheElements();
		console.log('[Chat IA] Elementos cacheados:', {
			widget: $widget.length,
			trigger: $trigger.length,
			input: $input.length,
			form: $form.length
		});
		bindEvents();
		initSession();
		autoResize();
		console.log('[Chat IA] Widget inicializado');
	}

	/**
     * Cachea elementos DOM
     */
	function cacheElements() {
		$widget = $('#chat-ia-widget');
		$trigger = $('#chat-ia-trigger');
		$messages = $('#chat-ia-messages');
		$input = $('#chat-ia-input');
		$form = $('#chat-ia-form');
		$typing = $('#chat-ia-typing');
		$draft = $('#chat-ia-draft');
		$escalation = $('#chat-ia-escalation');
	}

	/**
     * Vincula eventos
     */
	function bindEvents() {
		// Toggle widget flotante
		$trigger.on('click', toggleWidget);
		$('#chat-ia-minimize').on('click', toggleWidget);

		// Maximizar/Restaurar
		$('#chat-ia-maximize').on('click', toggleMaximize);

		// Click en overlay cierra maximizado
		$(document).on('click', '.chat-ia-overlay', function () {
			if ($widget.hasClass('chat-ia-maximized')) {
				toggleMaximize();
			}
		});

		// ESC cierra maximizado
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && $widget.hasClass('chat-ia-maximized')) {
				toggleMaximize();
			}
		});

		// Enviar mensaje
		$form.on('submit', handleSubmit);

		// Input
		$input.on('input', handleInput);
		$input.on('keydown', handleKeydown);

		// Selector de idioma
		$('.chat-ia-lang-btn').on('click', handleLanguageChange);

		// Nueva conversación
		$('#chat-ia-new').on('click', handleNewConversation);

		// Borrador
		$('#chat-ia-add-to-cart').on('click', handleAddToCart);
		$('#chat-ia-draft-close').on('click', hideDraft);

		// Escalado - cerrar panel
		$('#chat-ia-escalation-close').on('click', hideEscalation);

		// Acciones rápidas
		$(document).on('click', '.chat-ia-quick-action', handleQuickAction);

		// Toggle panel de información
		$('#chat-ia-info').on('click', toggleInfoPanel);
	}

	/**
     * Toggle del panel de información (noticias y enlaces)
     */
	function toggleInfoPanel() {
		const $infoPanel = $('#chat-ia-featured-content');
		const $infoBtn = $('#chat-ia-info');

		if ($infoPanel.length) {
			$infoPanel.slideToggle(250, function () {
				const isVisible = $infoPanel.is(':visible');
				$infoBtn.toggleClass('active', isVisible);

				// Scroll al panel si se muestra
				if (isVisible) {
					$messages.animate({
						scrollTop: $infoPanel.offset().top - $messages.offset().top + $messages.scrollTop()
					}, 300);
				}
			});
		}
	}

	/**
     * Crea el overlay si no existe
     */
	function ensureOverlay() {
		if ($('.chat-ia-overlay').length === 0) {
			$('body').append('<div class="chat-ia-overlay"></div>');
		}
	}

	/**
     * Maximiza o restaura el widget
     */
	function toggleMaximize() {
		ensureOverlay();
		const $overlay = $('.chat-ia-overlay');
		const isMaximized = $widget.hasClass('chat-ia-maximized');

		if (isMaximized) {
			// Restaurar
			$widget.removeClass('chat-ia-maximized');
			$overlay.removeClass('active');
			$('body').removeClass('chat-ia-maximized-mode');
		} else {
			// Maximizar
			$widget.addClass('chat-ia-maximized');
			$overlay.addClass('active');
			$('body').addClass('chat-ia-maximized-mode');
			// Focus en input
			setTimeout(() => $input.focus(), 300);
		}

		// Scroll al final de mensajes
		scrollToBottom();
	}

	/**
     * Maneja click en acción rápida
     */
	function handleQuickAction(e) {
		e.preventDefault();
		const $btn = $(this);
		const prompt = $btn.data('prompt');

		if (prompt) {
			// Ocultar las acciones rápidas después del primer uso
			$('#chat-ia-quick-actions').fadeOut(200);

			// Enviar el mensaje
			$input.val(prompt);
			handleSubmit(e);
		}
	}

	/**
     * Inicializa la sesión
     */
	function initSession() {
		// Intentar recuperar sesión de sessionStorage
		const savedSession = sessionStorage.getItem('chat_ia_session');
		if (savedSession) {
			try {
				const data = JSON.parse(savedSession);
				state.sessionId = data.sessionId;
				state.language = data.language || config.language;
				updateLanguageButtons();
				return;
			} catch (e) {
				console.error('Error parsing saved session:', e);
			}
		}

		// Crear nueva sesión
		createSession();
	}

	/**
     * Crea una nueva sesión
     */
	function createSession() {
		console.log('[Chat IA] Creando sesión...');
		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_init_session',
				nonce: config.nonce,
				language: state.language
			},
			success: function (response) {
				console.log('[Chat IA] Sesión creada:', response);
				if (response.success) {
					state.sessionId = response.data.session_id;
					saveSession();

					// Si ya existe el contenedor de bienvenida del template PHP,
					// NO añadir mensaje duplicado - el template ya lo tiene
					const $welcome = $('#chat-ia-welcome');
					if ($welcome.length === 0 && response.data.welcome_message) {
						// Solo añadir si no existe el welcome del template
						addMessage('assistant', response.data.welcome_message);
					}
				} else {
					console.error('[Chat IA] Error creando sesión:', response);
				}
			},
			error: function (xhr, status, error) {
				console.error('[Chat IA] Error AJAX creando sesión:', status, error);
				showError(strings.connectionError || 'Error de conexión');
			}
		});
	}

	/**
     * Guarda la sesión en sessionStorage
     */
	function saveSession() {
		sessionStorage.setItem('chat_ia_session', JSON.stringify({
			sessionId: state.sessionId,
			language: state.language
		}));
	}

	/**
     * Toggle del widget flotante
     */
	function toggleWidget() {
		state.isOpen = !state.isOpen;
		console.log('[Chat IA] Toggle widget, isOpen:', state.isOpen);

		const isSidebar = $widget.data('display-mode') === 'sidebar';
		const isFloating = $widget.hasClass('chat-ia-floating');

		if (state.isOpen) {
			if (isSidebar) {
				// Modo sidebar: mostrar y animar entrada
				$widget.removeClass('chat-ia-sidebar-closing');
				$widget.css('display', 'flex');
				$widget.addClass('chat-ia-sidebar-open');
			} else if (isFloating) {
				// Modo floating: usar clase para mostrar contenedor
				$widget.addClass('chat-ia-open');
			} else {
				$widget.show();
			}
			$trigger.addClass('active');
			// Pequeño delay para asegurar que el widget está visible antes de focus
			setTimeout(function () {
				$input.focus();
				console.log('[Chat IA] Input focused:', document.activeElement === $input[0]);
			}, isSidebar ? 400 : 100);
		} else {
			// Si está maximizado, restaurar primero
			if ($widget.hasClass('chat-ia-maximized')) {
				$widget.removeClass('chat-ia-maximized');
				$('.chat-ia-overlay').removeClass('active');
				$('body').removeClass('chat-ia-maximized-mode');
			}
			if (isSidebar) {
				// Modo sidebar: animación de salida
				$widget.removeClass('chat-ia-sidebar-open');
				$widget.addClass('chat-ia-sidebar-closing');
				setTimeout(function () {
					$widget.removeClass('chat-ia-sidebar-closing');
					$widget.css('display', 'none');
				}, 250);
			} else if (isFloating) {
				// Modo floating: usar clase para ocultar contenedor
				$widget.removeClass('chat-ia-open');
			} else {
				$widget.hide();
			}
			$trigger.removeClass('active');
		}
	}

	/**
     * Maneja el envío del formulario
     */
	function handleSubmit(e) {
		e.preventDefault();

		const message = $input.val().trim();
		if (!message || state.isLoading) {return;}

		// Añadir mensaje del usuario
		addMessage('user', message);

		// Limpiar input
		$input.val('');
		updateSendButton();
		autoResize();

		// Enviar al servidor
		sendMessage(message);
	}

	/**
     * Envía el mensaje al servidor con streaming SSE
     */
	function sendMessage(message) {
		state.isLoading = true;
		showTyping();

		// Antispam: honeypot field (debe estar vacío)
		const honeypotValue = $('#chat-ia-honeypot').val() || '';

		// Intentar streaming SSE primero
		if (config.streamingEnabled && typeof ReadableStream !== 'undefined') {
			sendMessageStream(message, honeypotValue);
		} else {
			sendMessageAjax(message, honeypotValue);
		}
	}

	/**
     * Envía mensaje con streaming SSE
     */
	function sendMessageStream(message, honeypotValue) {
		// Construir form data para POST
		const formData = new URLSearchParams({
			action: 'flavor_chat_send_stream',
			nonce: config.nonce,
			session_id: state.sessionId || '',
			message: message,
			language: state.language,
			context: state.context || '',
			website_url: honeypotValue
		});

		// Crear mensaje del asistente vacío para ir llenando
		const $messageEl = $(`
            <div class="chat-ia-message chat-ia-message-assistant">
                <div class="chat-ia-message-content"><span class="typing-cursor">|</span></div>
            </div>
        `);

		let responseText = '';
		let streamStarted = false;

		// Usar fetch con ReadableStream para POST + streaming
		fetch(config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString()
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}

			const reader = response.body.getReader();
			const decoder = new TextDecoder();
			let buffer = '';

			function processStream() {
				return reader.read().then(function ({ done, value }) {
					if (done) {
						finalizeStream();
						return;
					}

					buffer += decoder.decode(value, { stream: true });

					// Procesar líneas completas SSE
					const lines = buffer.split('\n');
					buffer = lines.pop(); // Guardar línea incompleta

					for (let i = 0; i < lines.length; i++) {
						const trimmed = lines[i].trim();

						if (trimmed.startsWith('event: ')) {
							// Guardar tipo de evento para la siguiente línea data
							state._sseEventType = trimmed.substring(7);
						} else if (trimmed.startsWith('data: ')) {
							const eventType = state._sseEventType || 'message';
							const dataStr = trimmed.substring(6);

							try {
								const data = JSON.parse(dataStr);
								handleSSEEvent(eventType, data, $messageEl);
							} catch (e) {
								// Ignorar líneas que no son JSON válido
							}

							state._sseEventType = null;
						}
					}

					return processStream();
				});
			}

			return processStream();

		}).catch(function (error) {
			console.error('[Chat IA] Stream error:', error);
			hideTyping();
			state.isLoading = false;

			// Fallback a AJAX normal
			if (!streamStarted) {
				sendMessageAjax(message, honeypotValue);
			} else {
				showError(strings.connectionError || 'Error de conexión');
			}
		});

		function handleSSEEvent(eventType, data, $el) {
			switch (eventType) {
				case 'session':
					if (data.session_id) {
						state.sessionId = data.session_id;
						saveSession();
					}
					break;

				case 'token':
					if (!streamStarted) {
						streamStarted = true;
						hideTyping();

						// Quitar welcome si existe
						const $welcome = $('#chat-ia-welcome');
						if ($welcome.length) {$welcome.remove();}

						$messages.append($el);
					}

					responseText += (data.token || '');
					$el.find('.chat-ia-message-content').html(
						formatMessage(responseText) + '<span class="typing-cursor">|</span>'
					);
					scrollToBottom();
					break;

				case 'error':
					hideTyping();
					state.isLoading = false;
					showError(data.error || strings.error);
					break;

				case 'done':
					finalizeStream(data);
					break;
			}
		}

		function finalizeStream(data) {
			hideTyping();
			state.isLoading = false;

			if (responseText) {
				// Quitar cursor y mostrar contenido final formateado
				$messageEl.find('.chat-ia-message-content').html(formatMessage(responseText));
				state.messagesCount++;
				scrollToBottom();

				// Leer respuesta en voz alta si TTS está activado
				if (window.flavorChatTTS && window.flavorChatTTS.isEnabled()) {
					window.flavorChatTTS.speak(responseText);
				}

				// Generar sugerencias contextuales client-side
				const smartSuggestions = generateSmartSuggestions(responseText);
				if (smartSuggestions.length > 0) {
					showSuggestions(smartSuggestions);
				}

				// Manejar cart_updated
				if (data && data.cart_updated) {
					refreshWooCommerceCart();
				}
			}
		}
	}

	/**
     * Envía mensaje via AJAX tradicional (fallback)
     */
	function sendMessageAjax(message, honeypotValue) {
		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			timeout: 120000, // 2 minutos - Claude puede tardar con tool_use
			data: {
				action: 'chat_ia_send_message',
				nonce: config.nonce,
				session_id: state.sessionId,
				message: message,
				language: state.language,
				context: state.context, // Contexto: landing, general, etc.
				website_url: honeypotValue // Campo honeypot
			},
			success: function (response) {
				console.log('[Chat IA] Response:', response);
				hideTyping();
				state.isLoading = false;

				if (response.success) {
					// Actualizar session ID si es nuevo
					if (response.data.session_id) {
						state.sessionId = response.data.session_id;
						saveSession();
					}

					// Añadir respuesta
					addMessage('assistant', response.data.response);

					// Leer respuesta en voz alta si TTS está activado
					if (window.flavorChatTTS && window.flavorChatTTS.isEnabled()) {
						window.flavorChatTTS.speak(response.data.response);
					}

					// OPTIMIZACIÓN 4: Mostrar sugerencias inteligentes
					if (response.data.suggestions && response.data.suggestions.length > 0) {
						showSuggestions(response.data.suggestions);
					} else {
						// Generar sugerencias contextuales client-side
						const smartSuggestions = generateSmartSuggestions(response.data.response);
						if (smartSuggestions.length > 0) {
							showSuggestions(smartSuggestions);
						}
					}

					// Mostrar borrador si existe
					if (response.data.draft) {
						showDraft(response.data.draft);
					}

					// Refrescar carrito si se actualizó (flag explícito del backend)
					if (response.data.cart_updated) {
						console.log('[Chat IA] Carrito actualizado - refrescando');
						refreshWooCommerceCart();
					} else if (!response.data.draft && response.data.response) {
						// Fallback: Si no hay draft y la respuesta menciona carrito
						if (response.data.response.includes('carrito') ||
                             response.data.response.includes('añadid') ||
                             response.data.response.includes('cart')) {
							refreshWooCommerceCart();
						}
					}

					// Mostrar opciones de escalado si existe
					if (response.data.escalation) {
						showEscalation(response.data.escalation);
					}

					// Actualizar mensajes restantes
					if (response.data.remaining_messages !== undefined) {
						updateRemainingMessages(response.data.remaining_messages);
					}
				} else {
					showError(response.data.error || strings.error);

					// Si hay escalado automático
					if (response.data.escalation) {
						showEscalation(response.data.escalation);
					}
				}
			},
			error: function (xhr, status, error) {
				hideTyping();
				state.isLoading = false;
				console.error('[Chat IA] AJAX Error:', status, error, xhr.responseText);
				if (status === 'timeout') {
					showError('La respuesta está tardando demasiado. Por favor, inténtalo de nuevo.');
				} else {
					showError(strings.connectionError || 'Error de conexión: ' + error);
				}
			}
		});
	}

	/**
     * Añade un mensaje al chat
     * OPTIMIZACIÓN 6: Efecto de escritura progresiva para respuestas del asistente
     */
	function addMessage(role, content, animate = true) {
		const $welcome = $('#chat-ia-welcome');
		if ($welcome.length) {
			$welcome.remove();
		}

		const messageClass = role === 'user' ? 'chat-ia-message-user' : 'chat-ia-message-assistant';

		// Para mensajes del usuario, mostrar inmediatamente
		if (role === 'user' || !animate) {
			const formattedContent = formatMessage(content);
			const $message = $(`
                <div class="chat-ia-message ${messageClass}">
                    <div class="chat-ia-message-content">${formattedContent}</div>
                </div>
            `);
			$messages.append($message);
			scrollToBottom();
		} else {
			// OPTIMIZACIÓN 6: Para respuestas del asistente, efecto de escritura
			const $message = $(`
                <div class="chat-ia-message ${messageClass}">
                    <div class="chat-ia-message-content"></div>
                </div>
            `);
			$messages.append($message);
			scrollToBottom();

			typeWriterEffect($message.find('.chat-ia-message-content'), content);
		}

		state.messagesCount++;
	}

	/**
     * OPTIMIZACIÓN 6: Efecto de escritura progresiva
     */
	function typeWriterEffect($element, text, speed = 20) {
		const formattedContent = formatMessage(text);

		// Para textos muy cortos (menos de 30 caracteres), mostrar de golpe
		if (text.length < 30) {
			$element.html(formattedContent);
			scrollToBottom();
			return;
		}

		// Mostrar por palabras con efecto de escritura
		const words = text.split(' ');
		let currentIndex = 0;
		let displayedText = '';

		function addNextWord() {
			if (currentIndex < words.length) {
				displayedText += (currentIndex > 0 ? ' ' : '') + words[currentIndex];
				$element.html(formatMessage(displayedText) + '<span class="typing-cursor">|</span>');
				scrollToBottom();
				currentIndex++;

				// Velocidad variable para parecer más natural (20-50ms por palabra)
				const delay = speed + Math.random() * 30;
				setTimeout(addNextWord, delay);
			} else {
				// Finalizar: quitar cursor y mostrar contenido formateado final
				$element.html(formattedContent);
				scrollToBottom();
			}
		}

		addNextWord();
	}

	/**
     * Formatea el contenido del mensaje
     * Soporta HTML seguro y Markdown básico
     * FIX: Procesa Markdown ANTES de escapar HTML para evitar problemas con comillas
     */
	function formatMessage(content) {
		if (!content) {return '';}

		let formatted = content;

		// Placeholder para proteger contenido procesado
		const protectedContent = [];

		function protect(html) {
			const index = protectedContent.length;
			protectedContent.push(html);
			return `%%PROTECTED_${index}%%`;
		}

		// 1. Primero procesar Markdown a HTML seguro ANTES de escapar

		// Convertir [texto](url) a enlaces (formato markdown) - usar DOM para escapar correctamente
		formatted = formatted.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function (match, text, url) {
			const $link = $('<a class="chat-ia-link" target="_blank"></a>');
			$link.attr('href', url);
			$link.text(text);
			return protect($link.prop('outerHTML'));
		});

		// Convertir **texto** a negrita
		formatted = formatted.replace(/\*\*([^*]+)\*\*/g, function (match, text) {
			return protect('<strong>' + escapeHtml(text) + '</strong>');
		});

		// Convertir *texto* a cursiva (no precedido ni seguido de *)
		formatted = formatted.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, function (match, text) {
			return protect('<em>' + escapeHtml(text) + '</em>');
		});

		// Convertir `código` a inline code
		formatted = formatted.replace(/`([^`]+)`/g, function (match, text) {
			return protect('<code>' + escapeHtml(text) + '</code>');
		});

		// 2. Proteger etiquetas HTML permitidas existentes
		formatted = formatted.replace(/<(a|strong|b|em|i|code|pre|br|p|ul|ol|li|span|div)([^>]*)>/gi, function (match, tag, attrs) {
			// Añadir target="_blank" a los enlaces si no lo tienen
			if (tag.toLowerCase() === 'a' && !attrs.includes('target=')) {
				attrs = ' class="chat-ia-link" target="_blank"' + attrs;
			}
			return protect('<' + tag + attrs + '>');
		});

		// Proteger etiquetas de cierre
		formatted = formatted.replace(/<\/(a|strong|b|em|i|code|pre|br|p|ul|ol|li|span|div)>/gi, function (match, tag) {
			return protect('</' + tag + '>');
		});

		// 3. Escapar HTML restante (potencialmente peligroso)
		formatted = escapeHtml(formatted);

		// 4. Restaurar contenido protegido
		protectedContent.forEach((html, index) => {
			formatted = formatted.replace(`%%PROTECTED_${index}%%`, html);
		});

		// 5. Convertir URLs planas a enlaces (que no estén ya en href)
		formatted = formatted.replace(
			/(?<!href=["'])(https?:\/\/[^\s<"']+)/gi,
			function (match, url) {
				const $link = $('<a class="chat-ia-link" target="_blank"></a>');
				$link.attr('href', url);
				$link.text(url);
				return $link.prop('outerHTML');
			}
		);

		// 6. Convertir saltos de línea
		formatted = formatted.replace(/\n/g, '<br>');

		// 7. Limpiar dobles <br>
		formatted = formatted.replace(/(<br\s*\/?>\s*){3,}/gi, '<br><br>');

		return formatted;
	}

	/**
     * Escapa caracteres HTML peligrosos
     */
	function escapeHtml(text) {
		if (!text) {return '';}
		return text
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/**
     * Muestra el indicador de escritura
     */
	function showTyping() {
		if ($typing && $typing.length) {
			$typing.css('display', 'flex');
			scrollToBottom();
		}
	}

	/**
     * Oculta el indicador de escritura
     */
	function hideTyping() {
		if ($typing && $typing.length) {
			$typing.css('display', 'none');
		}
	}

	/**
     * Muestra el borrador de reserva
     * FIX: Usa DOM para evitar problemas con escape de caracteres
     */
	function showDraft(draft) {
		state.draft = draft;

		const $content = $('#chat-ia-draft-content');
		$content.empty();

		// Nombre experiencia
		const $expName = $('<p></p>').append($('<strong></strong>').text(draft.experiencia_nombre || ''));
		$content.append($expName);

		// Fecha y hora
		let fechaText = draft.fecha_formateada || '';
		if (draft.hora) {
			fechaText += ' - ' + draft.hora;
		}
		$content.append($('<p></p>').text(fechaText));

		// Tickets
		if (draft.tickets && draft.tickets.length) {
			const $ticketsP = $('<p></p>');
			draft.tickets.forEach((ticket, index) => {
				const subtotal = typeof ticket.subtotal === 'number' ? ticket.subtotal.toFixed(2) : ticket.subtotal;
				const ticketText = ticket.cantidad + 'x ' + ticket.nombre + ' (' + subtotal + '€)';
				$ticketsP.append(document.createTextNode(ticketText));
				if (index < draft.tickets.length - 1) {
					$ticketsP.append($('<br>'));
				}
			});
			$content.append($ticketsP);
		}

		// Total
		const $total = $('<p></p>').append($('<strong></strong>').text('Total: ' + (draft.total_formateado || '')));
		$content.append($total);

		$draft.show();
	}

	/**
     * Oculta el borrador
     */
	function hideDraft() {
		$draft.hide();
		state.draft = null;
	}

	/**
     * Muestra las opciones de escalado
     * FIX: Usa DOM para evitar problemas con escape de caracteres
     */
	function showEscalation(escalation) {
		const $container = $('#chat-ia-escalation-options');
		$container.empty();

		if (escalation.opciones && escalation.opciones.length) {
			escalation.opciones.forEach(option => {
				const icon = getEscalationIcon(option.tipo);
				const $link = $('<a class="chat-ia-escalation-option" target="_blank"></a>');
				$link.attr('href', option.url || '#');

				const $optionText = $('<div class="option-text"></div>');
				const $label = $('<span class="option-label"></span>').text(option.label || '');
				const $desc = $('<span class="option-desc"></span>').text(option.descripcion || '');
				$optionText.append($label).append($desc);

				$link.html(icon); // El icon es SVG seguro generado internamente
				$link.append($optionText);
				$container.append($link);
			});
		}

		if (escalation.horario_atencion) {
			const $hours = $('<p class="escalation-hours" style="margin-top: 10px; font-size: 12px; color: #666;"></p>');
			$hours.text('Horario de atención: ' + escalation.horario_atencion);
			$container.append($hours);
		}

		$escalation.slideDown(200);
	}

	/**
     * Oculta las opciones de escalado
     */
	function hideEscalation() {
		$escalation.slideUp(200);
	}

	/**
     * Obtiene el icono para una opción de escalado
     */
	function getEscalationIcon(tipo) {
		const icons = {
			whatsapp: '<svg viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
			telefono: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>',
			email: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>'
		};
		return icons[tipo] || '';
	}

	/**
     * Maneja la adición al carrito
     */
	function handleAddToCart() {
		if (!state.draft || state.isLoading) {return;}

		state.isLoading = true;
		const $btn = $('#chat-ia-add-to-cart');
		$btn.prop('disabled', true).text('Añadiendo...');

		$.ajax({
			url: config.ajaxUrl,
			method: 'POST',
			data: {
				action: 'chat_ia_add_to_cart',
				nonce: config.nonce,
				session_id: state.sessionId
			},
			success: function (response) {
				state.isLoading = false;

				if (response.success) {
					hideDraft();
					addMessage('assistant', response.data.message || '¡Reserva añadida al carrito!');

					// Refrescar el mini-carrito de WooCommerce
					refreshWooCommerceCart();

					// Mostrar botón para ir al carrito (usando DOM para evitar problemas de escape)
					const cartUrl = response.data.cart_url || '/carrito';
					const $cartLink = $('<a class="chat-ia-btn"></a>');
					$cartLink.attr('href', cartUrl);
					$cartLink.text(strings.viewCart || 'Ver carrito');
					addMessage('assistant', $cartLink.prop('outerHTML'));
				} else {
					showError(response.data.error || 'Error al añadir al carrito');
					$btn.prop('disabled', false).text(strings.addToCart || 'Añadir al carrito');
				}
			},
			error: function () {
				state.isLoading = false;
				showError(strings.connectionError || 'Error de conexión');
				$btn.prop('disabled', false).text(strings.addToCart || 'Añadir al carrito');
			}
		});
	}

	/**
     * Refresca el mini-carrito de WooCommerce
     */
	function refreshWooCommerceCart() {
		// Método 1: Trigger de fragmentos de WooCommerce
		if (typeof wc_cart_fragments_params !== 'undefined') {
			$(document.body).trigger('wc_fragment_refresh');
		}

		// Método 2: Trigger added_to_cart para actualizar mini-cart
		$(document.body).trigger('added_to_cart');

		// Método 3: Si hay un contador de carrito, actualizarlo vía AJAX
		if (typeof woocommerce_params !== 'undefined' || typeof wc_add_to_cart_params !== 'undefined') {
			$.ajax({
				url: woocommerce_params?.ajax_url || wc_add_to_cart_params?.ajax_url || config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'woocommerce_get_refreshed_fragments'
				},
				success: function (data) {
					if (data && data.fragments) {
						$.each(data.fragments, function (key, value) {
							$(key).replaceWith(value);
						});
						$(document.body).trigger('wc_fragments_refreshed');
					}
				}
			});
		}

		console.log('[Chat IA] Carrito de WooCommerce actualizado');
	}

	/**
     * Maneja el cambio de idioma
     */
	function handleLanguageChange() {
		const lang = $(this).data('lang');
		if (lang === state.language) {return;}

		state.language = lang;
		updateLanguageButtons();
		saveSession();

		// Notificar al usuario
		addMessage('assistant', lang === 'eu' ?
			'Ados, orain euskaraz hitz egingo dut.' :
			'De acuerdo, ahora hablaré en español.');
	}

	/**
     * Actualiza los botones de idioma
     */
	function updateLanguageButtons() {
		$('.chat-ia-lang-btn').removeClass('active');
		$(`.chat-ia-lang-btn[data-lang="${state.language}"]`).addClass('active');
	}

	/**
     * Maneja nueva conversación
     */
	function handleNewConversation() {
		if (!confirm(strings.newConversation ? '¿Iniciar nueva conversación?' : '¿Iniciar nueva conversación?')) {
			return;
		}

		// Limpiar estado
		state.sessionId = null;
		state.draft = null;
		state.messagesCount = 0;

		// Limpiar UI
		$messages.html('');
		hideDraft();
		$escalation.hide();
		$('#chat-ia-remaining').text('');

		// Limpiar storage
		sessionStorage.removeItem('chat_ia_session');

		// Crear nueva sesión
		createSession();
	}

	/**
     * Maneja input del textarea
     */
	function handleInput() {
		updateSendButton();
		autoResize();
	}

	/**
     * Maneja keydown
     */
	function handleKeydown(e) {
		// Enter sin Shift envía el mensaje
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			$form.submit();
		}
	}

	/**
     * Actualiza el estado del botón de enviar
     */
	function updateSendButton() {
		const hasContent = $input.val().trim().length > 0;
		$('#chat-ia-send').prop('disabled', !hasContent || state.isLoading);
	}

	/**
     * Auto-resize del textarea
     */
	function autoResize() {
		if (!$input || !$input.length || !$input[0]) {
			return;
		}
		// Establecer altura mínima de 20px para que siempre sea usable
		$input.css('height', 'auto');
		const scrollHeight = $input[0].scrollHeight;
		const minHeight = 20;
		const maxHeight = 120;
		const newHeight = Math.max(minHeight, Math.min(scrollHeight, maxHeight));
		$input.css('height', newHeight + 'px');
	}

	/**
     * Scroll al final de los mensajes
     */
	function scrollToBottom() {
		$messages.scrollTop($messages[0].scrollHeight);
	}

	/**
     * Muestra un error
     */
	function showError(message) {
		addMessage('assistant', `<span style="color: #dc2626;">${message}</span>`);
	}

	/**
     * Actualiza los mensajes restantes
     */
	function updateRemainingMessages(remaining) {
		const $remaining = $('#chat-ia-remaining');
		if (remaining <= 10) {
			$remaining.text(`${remaining} ${strings.messagesRemaining || 'mensajes restantes'}`);
			$remaining.css('color', remaining <= 5 ? '#dc2626' : '#f59e0b');
		} else {
			$remaining.text('');
		}
	}

	/**
     * OPTIMIZACIÓN 4: Muestra sugerencias inteligentes como botones
     */
	function showSuggestions(suggestions) {
		// Eliminar sugerencias anteriores
		$('.chat-ia-suggestions').remove();

		if (!suggestions || suggestions.length === 0) {return;}

		const $container = $('<div class="chat-ia-suggestions"></div>');

		suggestions.forEach(function (suggestion) {
			const $btn = $('<button class="chat-ia-suggestion-btn"></button>');
			$btn.attr('data-action', suggestion.action || '');
			$btn.attr('data-prompt', suggestion.prompt || '');
			if (suggestion.data) {
				$btn.attr('data-info', JSON.stringify(suggestion.data));
			}
			$btn.text(suggestion.text || '');
			$container.append($btn);
		});

		$messages.append($container);
		scrollToBottom();
	}

	/**
     * FASE 5: Genera sugerencias inteligentes basadas en la respuesta
     * Privacy-aware: Solo sugiere acciones publicas
     */
	function generateSmartSuggestions(response) {
		if (!response) {return [];}

		const suggestions = [];
		const responseLower = response.toLowerCase();

		// Si menciona disponibilidad
		if (responseLower.includes('disponib') || responseLower.includes('plazas')) {
			suggestions.push({
				text: strings.bookNow || 'Reservar ahora',
				action: 'ask',
				prompt: 'Quiero hacer una reserva'
			});
		}

		// Si menciona precios
		if (responseLower.includes('precio') || responseLower.includes('euros') || responseLower.includes('€')) {
			suggestions.push({
				text: strings.viewDates || 'Ver fechas',
				action: 'ask',
				prompt: '¿Qué fechas hay disponibles?'
			});
		}

		// Si menciona reserva o carrito
		if (responseLower.includes('reserv') || responseLower.includes('carrito')) {
			suggestions.push({
				text: strings.paymentMethods || 'Formas de pago',
				action: 'ask',
				prompt: '¿Cuáles son las formas de pago?'
			});
		}

		// Si menciona horarios o ubicacion
		if (responseLower.includes('horario') || responseLower.includes('hora')) {
			suggestions.push({
				text: strings.location || 'Cómo llegar',
				action: 'ask',
				prompt: '¿Dónde están ubicados y cómo puedo llegar?'
			});
		}

		// Si la respuesta es muy larga o parece compleja
		if (response.length > 500 || responseLower.includes('lo siento') || responseLower.includes('disculp')) {
			suggestions.push({
				text: strings.contact || 'Contactar',
				action: 'ask',
				prompt: 'Quiero hablar con una persona'
			});
		}

		// Si menciona experiencia o visita
		if (responseLower.includes('experiencia') || responseLower.includes('visita')) {
			if (!suggestions.some(s => s.prompt.includes('fecha'))) {
				suggestions.push({
					text: strings.checkAvailability || 'Ver disponibilidad',
					action: 'ask',
					prompt: '¿Qué fechas hay disponibles para visitar?'
				});
			}
		}

		// Limitar a 3 sugerencias
		return suggestions.slice(0, 3);
	}

	/**
     * OPTIMIZACIÓN 4: Maneja click en sugerencia
     */
	function handleSuggestionClick(e) {
		e.preventDefault();
		const $btn = $(this);
		const action = $btn.data('action');
		const prompt = $btn.data('prompt');
		const info = $btn.data('info');

		// Eliminar todas las sugerencias
		$('.chat-ia-suggestions').fadeOut(200, function () {
			$(this).remove();
		});

		switch (action) {
			case 'ask':
				if (prompt) {
					$input.val(prompt);
					handleSubmit(e);
				}
				break;

			case 'select_date':
				if (info && info.fecha) {
					const mensaje = `Quiero reservar para el ${$btn.text()} (${info.estado})`;
					$input.val(mensaje);
					handleSubmit(e);
				}
				break;

			case 'confirm_draft':
				$input.val('Sí, confirmo la reserva');
				handleSubmit(e);
				break;

			case 'change_date':
				$input.val('Quiero cambiar la fecha');
				handleSubmit(e);
				break;

			default:
				if (prompt) {
					$input.val(prompt);
					handleSubmit(e);
				}
		}
	}

	// Registrar evento para sugerencias (delegación)
	$(document).on('click', '.chat-ia-suggestion-btn', handleSuggestionClick);

	// =========================================
	// FUNCIONALIDAD DE VOZ (Web Speech API)
	// =========================================

	let recognition = null;
	let isListening = false;
	let ttsEnabled = false;
	let currentUtterance = null;

	/**
     * Inicializa el reconocimiento de voz (Speech-to-Text)
     */
	function initSpeechRecognition() {
		const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

		if (!SpeechRecognition) {
			console.log('[Chat IA] Speech Recognition no disponible en este navegador');
			$('#chat-ia-mic').hide();
			return false;
		}

		recognition = new SpeechRecognition();
		recognition.continuous = false;
		recognition.interimResults = true;
		recognition.lang = state.language === 'es' ? 'es-ES' :
			state.language === 'eu' ? 'eu-ES' :
				state.language === 'en' ? 'en-US' :
					state.language === 'fr' ? 'fr-FR' : 'es-ES';

		recognition.onstart = function () {
			isListening = true;
			$('#chat-ia-mic').addClass('listening');
			$('.chat-ia-mic-icon').hide();
			$('.chat-ia-mic-stop').show();
			$input.attr('placeholder', strings.listening || 'Escuchando...');
			console.log('[Chat IA] Escuchando...');
		};

		recognition.onresult = function (event) {
			let finalTranscript = '';
			let interimTranscript = '';

			for (let i = event.resultIndex; i < event.results.length; i++) {
				const transcript = event.results[i][0].transcript;
				if (event.results[i].isFinal) {
					finalTranscript += transcript;
				} else {
					interimTranscript += transcript;
				}
			}

			// Mostrar transcripción en tiempo real
			if (interimTranscript) {
				$input.val(interimTranscript);
			}
			if (finalTranscript) {
				$input.val(finalTranscript);
			}
		};

		recognition.onend = function () {
			isListening = false;
			$('#chat-ia-mic').removeClass('listening');
			$('.chat-ia-mic-icon').show();
			$('.chat-ia-mic-stop').hide();
			$input.attr('placeholder', config.placeholder || 'Escribe tu mensaje...');
			console.log('[Chat IA] Fin de escucha');

			// Si hay texto, enviarlo automáticamente
			const text = $input.val().trim();
			if (text) {
				$form.trigger('submit');
			}
		};

		recognition.onerror = function (event) {
			console.log('[Chat IA] Error de reconocimiento:', event.error);
			isListening = false;
			$('#chat-ia-mic').removeClass('listening');
			$('.chat-ia-mic-icon').show();
			$('.chat-ia-mic-stop').hide();
			$input.attr('placeholder', config.placeholder || 'Escribe tu mensaje...');

			if (event.error === 'not-allowed') {
				alert(strings.micPermissionDenied || 'Permiso de micrófono denegado. Actívalo en la configuración del navegador.');
			}
		};

		console.log('[Chat IA] Speech Recognition inicializado');
		return true;
	}

	/**
     * Toggle del micrófono
     */
	function toggleMicrophone() {
		if (!recognition) {
			if (!initSpeechRecognition()) {
				alert(strings.speechNotSupported || 'Tu navegador no soporta reconocimiento de voz. Prueba con Chrome o Edge.');
				return;
			}
		}

		if (isListening) {
			recognition.stop();
		} else {
			// Detener TTS si está hablando
			if (window.speechSynthesis && window.speechSynthesis.speaking) {
				window.speechSynthesis.cancel();
			}
			try {
				recognition.start();
			} catch (e) {
				console.log('[Chat IA] Error al iniciar reconocimiento:', e);
			}
		}
	}

	/**
     * Lee un texto en voz alta (Text-to-Speech)
     */
	function speakText(text) {
		if (!ttsEnabled || !window.speechSynthesis) {return;}

		// Cancelar cualquier lectura anterior
		window.speechSynthesis.cancel();

		// Limpiar el texto de markdown y HTML
		const cleanText = text
			.replace(/\*\*(.*?)\*\*/g, '$1')  // Bold
			.replace(/\*(.*?)\*/g, '$1')       // Italic
			.replace(/```[\s\S]*?```/g, '')    // Code blocks
			.replace(/`(.*?)`/g, '$1')         // Inline code
			.replace(/\[(.*?)\]\(.*?\)/g, '$1') // Links
			.replace(/<[^>]*>/g, '')           // HTML tags
			.replace(/#{1,6}\s/g, '')          // Headers
			.replace(/\n+/g, '. ')             // Newlines
			.trim();

		if (!cleanText) {return;}

		currentUtterance = new SpeechSynthesisUtterance(cleanText);
		currentUtterance.lang = state.language === 'es' ? 'es-ES' :
			state.language === 'eu' ? 'eu-ES' :
				state.language === 'en' ? 'en-US' :
					state.language === 'fr' ? 'fr-FR' : 'es-ES';
		currentUtterance.rate = 1.0;
		currentUtterance.pitch = 1.0;

		// Intentar usar una voz natural si está disponible
		const voices = window.speechSynthesis.getVoices();
		const preferredVoice = voices.find(v =>
			v.lang.startsWith(currentUtterance.lang.split('-')[0]) &&
            (v.name.includes('Google') || v.name.includes('Natural') || v.name.includes('Neural'))
		) || voices.find(v => v.lang.startsWith(currentUtterance.lang.split('-')[0]));

		if (preferredVoice) {
			currentUtterance.voice = preferredVoice;
		}

		currentUtterance.onend = function () {
			currentUtterance = null;
		};

		window.speechSynthesis.speak(currentUtterance);
		console.log('[Chat IA] TTS: Leyendo respuesta');
	}

	/**
     * Toggle de Text-to-Speech
     */
	function toggleTTS() {
		if (!window.speechSynthesis) {
			alert(strings.ttsNotSupported || 'Tu navegador no soporta síntesis de voz.');
			return;
		}

		ttsEnabled = !ttsEnabled;
		const $btn = $('#chat-ia-tts-toggle');

		if (ttsEnabled) {
			$btn.addClass('active');
			$('.chat-ia-tts-on').show();
			$('.chat-ia-tts-off').hide();
			console.log('[Chat IA] TTS activado');
		} else {
			$btn.removeClass('active');
			$('.chat-ia-tts-on').hide();
			$('.chat-ia-tts-off').show();
			// Detener si está hablando
			window.speechSynthesis.cancel();
			console.log('[Chat IA] TTS desactivado');
		}
	}

	/**
     * Extiende bindEvents para incluir eventos de voz
     */
	function bindVoiceEvents() {
		$('#chat-ia-mic').on('click', toggleMicrophone);
		$('#chat-ia-tts-toggle').on('click', toggleTTS);

		// Pre-cargar voces (necesario en algunos navegadores)
		if (window.speechSynthesis) {
			window.speechSynthesis.getVoices();
			window.speechSynthesis.onvoiceschanged = function () {
				window.speechSynthesis.getVoices();
			};
		}
	}

	/**
     * Hook para leer respuestas del asistente
     * Se llama cuando se recibe una respuesta completa
     */
	window.flavorChatTTS = {
		speak: speakText,
		isEnabled: function () { return ttsEnabled; }
	};

	// Inicializar eventos de voz cuando el DOM esté listo
	$(document).ready(function () {
		setTimeout(bindVoiceEvents, 100);
	});

	// Inicializar cuando el DOM esté listo
	$(document).ready(init);

})(jQuery);
