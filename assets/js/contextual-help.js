/**
 * Flavor Chat IA - Sistema de Ayuda Contextual Inline
 *
 * Proporciona help bubbles, popovers y tours guiados
 * para mejorar la experiencia del usuario en el admin.
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */

(function () {
	'use strict';

	/**
     * Diccionario de ayudas en español
     */
	const helpDictionary = {
		// Configuración de API
		api_key: 'Tu clave de API del proveedor de IA. Puedes obtenerla en el panel de tu proveedor (OpenAI, Claude, etc.).',
		api_secret: 'Clave secreta de la API. Mantenla confidencial y no la compartas.',
		api_endpoint: 'URL del endpoint de la API. Solo modifica si usas un servidor proxy o personalizado.',

		// Webhooks y URLs
		webhook_url: 'URL donde se enviarán las notificaciones automáticas cuando ocurran eventos.',
		callback_url: 'URL de retorno donde se redirigirá al usuario después de una acción.',
		redirect_url: 'URL de redirección para flujos de autenticación o pagos.',

		// Configuración de rendimiento
		cron_interval: 'Frecuencia de ejecución de tareas programadas. Valores comunes: hourly (cada hora), twicedaily (dos veces al día), daily (diario).',
		cache_ttl: 'Tiempo en segundos que se mantienen los datos en caché. Mayor valor = mejor rendimiento, menor frescura de datos.',
		rate_limit: 'Número máximo de peticiones por minuto. Protege contra abusos y sobrecarga del servidor.',
		timeout: 'Tiempo máximo de espera en segundos para conexiones externas.',
		max_retries: 'Número máximo de reintentos en caso de error de conexión.',

		// Configuración de chat
		chat_enabled: 'Activa o desactiva el widget de chat en el sitio.',
		chat_position: 'Posición del widget de chat en la pantalla (inferior derecha, inferior izquierda, etc.).',
		chat_theme: 'Tema visual del widget de chat.',
		welcome_message: 'Mensaje de bienvenida que se muestra al abrir el chat.',
		placeholder_text: 'Texto de ejemplo que aparece en el campo de entrada del chat.',

		// Configuración de IA
		model: 'Modelo de IA a utilizar. Modelos más avanzados ofrecen mejores respuestas pero mayor coste.',
		temperature: 'Controla la creatividad de las respuestas. 0 = respuestas deterministas, 1 = respuestas más creativas.',
		max_tokens: 'Límite máximo de tokens (palabras aproximadas) en cada respuesta.',
		system_prompt: 'Instrucciones base que definen el comportamiento y personalidad del asistente.',
		context_window: 'Número de mensajes anteriores a incluir como contexto en cada petición.',

		// Configuración de usuarios
		user_roles: 'Roles de usuario que pueden acceder a esta funcionalidad.',
		require_login: 'Si está activado, solo usuarios registrados pueden usar esta función.',
		guest_access: 'Permite el acceso a usuarios no registrados (invitados).',

		// Configuración de notificaciones
		email_notifications: 'Envía notificaciones por correo electrónico cuando ocurran eventos importantes.',
		push_notifications: 'Envía notificaciones push al navegador del usuario.',
		notification_frequency: 'Con qué frecuencia se envían resúmenes de notificaciones.',

		// Configuración de módulos
		module_enabled: 'Activa o desactiva este módulo.',
		module_visibility: 'Controla quién puede ver este módulo en el frontend.',
		module_priority: 'Orden de prioridad del módulo. Números menores aparecen primero.',

		// Configuración de seguridad
		ssl_verify: 'Verifica certificados SSL en conexiones externas. Desactivar solo para pruebas.',
		sanitize_input: 'Limpia y valida todos los datos de entrada del usuario.',
		nonce_check: 'Verifica tokens de seguridad en formularios para prevenir CSRF.',

		// Configuración de exportación/importación
		export_format: 'Formato de archivo para exportar datos (JSON, CSV, XML).',
		include_media: 'Incluye archivos multimedia en la exportación.',
		compression: 'Comprime el archivo de exportación para reducir su tamaño.',

		// Configuración de layouts
		layout_type: 'Tipo de diseño para mostrar el contenido.',
		columns: 'Número de columnas en la cuadrícula.',
		spacing: 'Espacio entre elementos del diseño.',
		responsive: 'Ajusta automáticamente el diseño en dispositivos móviles.',

		// Configuración de formularios
		required_field: 'Este campo es obligatorio y debe completarse.',
		field_validation: 'Tipo de validación a aplicar en este campo.',
		error_message: 'Mensaje que se muestra cuando hay un error de validación.',
		success_message: 'Mensaje que se muestra al completar exitosamente.',

		// Configuración de pagos
		currency: 'Moneda para transacciones y precios.',
		payment_gateway: 'Pasarela de pago a utilizar (PayPal, Stripe, etc.).',
		test_mode: 'Modo de prueba para transacciones sin cargos reales.',

		// Configuración de idiomas
		default_language: 'Idioma predeterminado del sitio.',
		auto_translate: 'Traduce automáticamente el contenido usando IA.',
		rtl_support: 'Soporte para idiomas de derecha a izquierda (árabe, hebreo).',

		// Métricas y analíticas
		track_usage: 'Registra estadísticas de uso para análisis.',
		anonymize_data: 'Anonimiza datos personales en las estadísticas.',
		retention_days: 'Días que se conservan los datos de analíticas.',

		// Configuración de red
		network_enabled: 'Habilita funciones multisite/red.',
		sync_settings: 'Sincroniza configuración entre sitios de la red.',
		shared_users: 'Comparte usuarios entre sitios de la red.'
	};

	/**
     * Configuración por defecto
     */
	const defaultConfig = {
		trigger: 'click', // 'click' o 'hover'
		position: 'auto', // 'auto', 'top', 'bottom', 'left', 'right'
		animation: 'fade', // 'fade', 'scale', 'slide'
		delay: 200,
		hideDelay: 100,
		maxWidth: 300,
		offset: 8,
		zIndex: 100000,
		showIcon: true,
		iconPosition: 'after', // 'before' o 'after'
		iconClass: 'flavor-help-icon',
		tourOverlay: true,
		tourSpotlight: true,
		closeOnClickOutside: true,
		closeOnEscape: true,
		onShow: null,
		onHide: null,
		onTourStart: null,
		onTourEnd: null,
		onTourStep: null
	};

	/**
     * Estado global
     */
	let activePopover = null;
	let activeTour = null;
	const tourCurrentStep = 0;
	let config = { ...defaultConfig };
	const customDictionary = {};

	/**
     * Clase principal FlavorHelp
     */
	class FlavorHelp {
		constructor(userConfig = {}) {
			this.config = { ...defaultConfig, ...userConfig };
			this.popovers = new Map();
			this.tours = new Map();
			this.initialized = false;
		}

		/**
         * Inicializa el sistema de ayuda
         */
		init() {
			if (this.initialized) {return this;}

			this.injectStyles();
			this.setupEventListeners();
			this.processElements();
			this.initialized = true;

			// Emitir evento de inicialización
			document.dispatchEvent(new CustomEvent('flavorHelp:init', { detail: this }));

			return this;
		}

		/**
         * Inyecta estilos críticos si no están cargados
         */
		injectStyles() {
			if (document.getElementById('flavor-help-inline-styles')) {return;}

			const criticalStyles = document.createElement('style');
			criticalStyles.id = 'flavor-help-inline-styles';
			criticalStyles.textContent = `
                .flavor-help-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 16px;
                    height: 16px;
                    border-radius: 50%;
                    background: var(--flavor-help-bg, #e0e0e0);
                    color: var(--flavor-help-color, #666);
                    font-size: 11px;
                    font-weight: 600;
                    cursor: help;
                    margin-left: 6px;
                    transition: all 0.2s ease;
                    user-select: none;
                }
                .flavor-help-icon:hover {
                    background: var(--flavor-help-bg-hover, #2271b1);
                    color: var(--flavor-help-color-hover, #fff);
                }
            `;
			document.head.appendChild(criticalStyles);
		}

		/**
         * Configura los event listeners globales
         */
		setupEventListeners() {
			// Click outside para cerrar
			document.addEventListener('click', (event) => {
				if (this.config.closeOnClickOutside && activePopover) {
					const popoverElement = document.querySelector('.flavor-help-popover');
					const isClickOnPopover = popoverElement && popoverElement.contains(event.target);
					const isClickOnIcon = event.target.closest('.flavor-help-icon');

					if (!isClickOnPopover && !isClickOnIcon) {
						this.hidePopover();
					}
				}
			});

			// Escape para cerrar
			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape') {
					if (this.config.closeOnEscape && activePopover) {
						this.hidePopover();
					}
					if (activeTour) {
						this.endTour();
					}
				}
			});

			// Resize para reposicionar
			window.addEventListener('resize', this.debounce(() => {
				if (activePopover) {
					this.repositionPopover();
				}
				if (activeTour) {
					this.updateTourSpotlight();
				}
			}, 100));

			// Scroll para reposicionar
			window.addEventListener('scroll', this.debounce(() => {
				if (activePopover) {
					this.repositionPopover();
				}
				if (activeTour) {
					this.updateTourSpotlight();
				}
			}, 50), true);
		}

		/**
         * Procesa elementos con atributos data-help
         */
		processElements() {
			// Elementos con data-help (texto directo)
			document.querySelectorAll('[data-help]').forEach(element => {
				this.attachHelp(element, element.dataset.help);
			});

			// Elementos con data-help-id (referencia al diccionario)
			document.querySelectorAll('[data-help-id]').forEach(element => {
				const helpKey = element.dataset.helpId;
				const helpText = this.getHelpText(helpKey);
				if (helpText) {
					this.attachHelp(element, helpText, helpKey);
				}
			});

			// Elementos con data-help-tour (pasos de tour)
			document.querySelectorAll('[data-help-tour]').forEach(element => {
				const tourId = element.dataset.helpTour;
				const stepIndex = parseInt(element.dataset.helpTourStep || '0', 10);
				const stepConfig = {
					element: element,
					title: element.dataset.helpTourTitle || '',
					content: element.dataset.helpTourContent || element.dataset.help || '',
					position: element.dataset.helpTourPosition || 'auto'
				};
				this.addTourStep(tourId, stepIndex, stepConfig);
			});
		}

		/**
         * Obtiene el texto de ayuda del diccionario
         */
		getHelpText(key) {
			return customDictionary[key] || helpDictionary[key] || null;
		}

		/**
         * Agrega texto de ayuda personalizado al diccionario
         */
		addHelpText(key, text) {
			customDictionary[key] = text;
			return this;
		}

		/**
         * Agrega múltiples textos de ayuda
         */
		addHelpTexts(texts) {
			Object.assign(customDictionary, texts);
			return this;
		}

		/**
         * Adjunta ayuda a un elemento
         */
		attachHelp(element, helpText, helpKey = null) {
			if (!element || !helpText) {return this;}

			// Evitar duplicados
			if (element.dataset.flavorHelpAttached) {return this;}
			element.dataset.flavorHelpAttached = 'true';

			// Crear icono de ayuda si está configurado
			if (this.config.showIcon) {
				const icon = this.createHelpIcon(helpText, helpKey);

				// Insertar antes o después según configuración
				if (this.config.iconPosition === 'before') {
					element.insertBefore(icon, element.firstChild);
				} else {
					// Si el elemento es un label, insertar después del texto
					if (element.tagName === 'LABEL') {
						element.appendChild(icon);
					} else {
						element.parentNode.insertBefore(icon, element.nextSibling);
					}
				}

				this.popovers.set(icon, { text: helpText, key: helpKey, target: element });
			}

			// También agregar evento al elemento original si no tiene icono
			if (!this.config.showIcon) {
				this.attachEventToElement(element, helpText, helpKey);
			}

			return this;
		}

		/**
         * Crea un icono de ayuda
         */
		createHelpIcon(helpText, helpKey) {
			const icon = document.createElement('span');
			icon.className = this.config.iconClass;
			icon.setAttribute('role', 'button');
			icon.setAttribute('tabindex', '0');
			icon.setAttribute('aria-label', 'Mostrar ayuda');
			icon.innerHTML = '?';

			if (helpKey) {
				icon.dataset.helpKey = helpKey;
			}

			this.attachEventToElement(icon, helpText, helpKey);

			return icon;
		}

		/**
         * Adjunta eventos a un elemento
         */
		attachEventToElement(element, helpText, helpKey) {
			if (this.config.trigger === 'hover') {
				let showTimeout, hideTimeout;

				element.addEventListener('mouseenter', () => {
					clearTimeout(hideTimeout);
					showTimeout = setTimeout(() => {
						this.showPopover(element, helpText, helpKey);
					}, this.config.delay);
				});

				element.addEventListener('mouseleave', () => {
					clearTimeout(showTimeout);
					hideTimeout = setTimeout(() => {
						this.hidePopover();
					}, this.config.hideDelay);
				});
			} else {
				element.addEventListener('click', (event) => {
					event.preventDefault();
					event.stopPropagation();

					if (activePopover && activePopover.trigger === element) {
						this.hidePopover();
					} else {
						this.showPopover(element, helpText, helpKey);
					}
				});
			}

			// Soporte para teclado
			element.addEventListener('keydown', (event) => {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					this.showPopover(element, helpText, helpKey);
				}
			});
		}

		/**
         * Muestra un popover de ayuda
         */
		showPopover(trigger, content, helpKey = null) {
			// Ocultar popover anterior
			this.hidePopover();

			// Crear popover
			const popover = document.createElement('div');
			popover.className = 'flavor-help-popover';
			popover.setAttribute('role', 'tooltip');
			popover.setAttribute('aria-live', 'polite');

			// Contenido
			const contentWrapper = document.createElement('div');
			contentWrapper.className = 'flavor-help-popover-content';
			contentWrapper.innerHTML = this.formatContent(content);

			// Botón de cerrar
			const closeBtn = document.createElement('button');
			closeBtn.className = 'flavor-help-popover-close';
			closeBtn.innerHTML = '&times;';
			closeBtn.setAttribute('aria-label', 'Cerrar ayuda');
			closeBtn.addEventListener('click', () => this.hidePopover());

			// Flecha
			const arrow = document.createElement('div');
			arrow.className = 'flavor-help-popover-arrow';

			popover.appendChild(closeBtn);
			popover.appendChild(contentWrapper);
			popover.appendChild(arrow);

			document.body.appendChild(popover);

			// Posicionar
			this.positionPopover(popover, trigger, arrow);

			// Animar entrada
			requestAnimationFrame(() => {
				popover.classList.add('flavor-help-popover-visible');
			});

			// Guardar referencia
			activePopover = {
				element: popover,
				trigger: trigger,
				arrow: arrow,
				helpKey: helpKey
			};

			// Callback
			if (typeof this.config.onShow === 'function') {
				this.config.onShow(popover, trigger, helpKey);
			}

			// Emitir evento
			document.dispatchEvent(new CustomEvent('flavorHelp:show', {
				detail: { popover, trigger, helpKey }
			}));

			return this;
		}

		/**
         * Oculta el popover activo
         */
		hidePopover() {
			if (!activePopover) {return this;}

			const { element, trigger, helpKey } = activePopover;

			element.classList.remove('flavor-help-popover-visible');
			element.classList.add('flavor-help-popover-hiding');

			setTimeout(() => {
				if (element.parentNode) {
					element.parentNode.removeChild(element);
				}
			}, 200);

			// Callback
			if (typeof this.config.onHide === 'function') {
				this.config.onHide(element, trigger, helpKey);
			}

			// Emitir evento
			document.dispatchEvent(new CustomEvent('flavorHelp:hide', {
				detail: { trigger, helpKey }
			}));

			activePopover = null;

			return this;
		}

		/**
         * Posiciona el popover
         */
		positionPopover(popover, trigger, arrow) {
			const triggerRect = trigger.getBoundingClientRect();
			const popoverRect = popover.getBoundingClientRect();
			const viewportWidth = window.innerWidth;
			const viewportHeight = window.innerHeight;
			const scrollX = window.scrollX;
			const scrollY = window.scrollY;
			const offset = this.config.offset;

			let position = this.config.position;

			// Auto-detectar mejor posición
			if (position === 'auto') {
				const spaceAbove = triggerRect.top;
				const spaceBelow = viewportHeight - triggerRect.bottom;
				const spaceLeft = triggerRect.left;
				const spaceRight = viewportWidth - triggerRect.right;

				if (spaceBelow >= popoverRect.height + offset) {
					position = 'bottom';
				} else if (spaceAbove >= popoverRect.height + offset) {
					position = 'top';
				} else if (spaceRight >= popoverRect.width + offset) {
					position = 'right';
				} else if (spaceLeft >= popoverRect.width + offset) {
					position = 'left';
				} else {
					position = 'bottom';
				}
			}

			let top, left;

			switch (position) {
				case 'top':
					top = triggerRect.top + scrollY - popoverRect.height - offset;
					left = triggerRect.left + scrollX + (triggerRect.width / 2) - (popoverRect.width / 2);
					popover.classList.add('flavor-help-popover-top');
					break;
				case 'bottom':
					top = triggerRect.bottom + scrollY + offset;
					left = triggerRect.left + scrollX + (triggerRect.width / 2) - (popoverRect.width / 2);
					popover.classList.add('flavor-help-popover-bottom');
					break;
				case 'left':
					top = triggerRect.top + scrollY + (triggerRect.height / 2) - (popoverRect.height / 2);
					left = triggerRect.left + scrollX - popoverRect.width - offset;
					popover.classList.add('flavor-help-popover-left');
					break;
				case 'right':
					top = triggerRect.top + scrollY + (triggerRect.height / 2) - (popoverRect.height / 2);
					left = triggerRect.right + scrollX + offset;
					popover.classList.add('flavor-help-popover-right');
					break;
			}

			// Ajustar si se sale de la pantalla
			if (left < 10) {left = 10;}
			if (left + popoverRect.width > viewportWidth - 10) {
				left = viewportWidth - popoverRect.width - 10;
			}
			if (top < 10) {top = 10;}

			popover.style.top = `${top}px`;
			popover.style.left = `${left}px`;
			popover.style.maxWidth = `${this.config.maxWidth}px`;
			popover.style.zIndex = this.config.zIndex;

			// Posicionar flecha
			if (arrow) {
				const arrowOffset = triggerRect.left + scrollX + (triggerRect.width / 2) - left;
				if (position === 'top' || position === 'bottom') {
					arrow.style.left = `${Math.max(10, Math.min(arrowOffset, popoverRect.width - 10))}px`;
				}
			}
		}

		/**
         * Reposiciona el popover activo
         */
		repositionPopover() {
			if (!activePopover) {return;}
			this.positionPopover(activePopover.element, activePopover.trigger, activePopover.arrow);
		}

		/**
         * Formatea el contenido del popover
         */
		formatContent(content) {
			// Escapar HTML básico pero permitir algunos tags
			const formatted = content
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
				.replace(/\*(.*?)\*/g, '<em>$1</em>')
				.replace(/`(.*?)`/g, '<code>$1</code>')
				.replace(/\n/g, '<br>');

			return formatted;
		}

		// ==========================================
		// SISTEMA DE TOURS GUIADOS
		// ==========================================

		/**
         * Agrega un paso al tour
         */
		addTourStep(tourId, stepIndex, stepConfig) {
			if (!this.tours.has(tourId)) {
				this.tours.set(tourId, []);
			}

			const steps = this.tours.get(tourId);
			steps[stepIndex] = stepConfig;
			this.tours.set(tourId, steps);

			return this;
		}

		/**
         * Define un tour completo
         */
		defineTour(tourId, steps) {
			this.tours.set(tourId, steps.map(step => ({
				element: typeof step.element === 'string'
					? document.querySelector(step.element)
					: step.element,
				title: step.title || '',
				content: step.content || '',
				position: step.position || 'auto',
				onEnter: step.onEnter || null,
				onLeave: step.onLeave || null
			})));

			return this;
		}

		/**
         * Inicia un tour
         */
		startTour(tourId) {
			const steps = this.tours.get(tourId);
			if (!steps || steps.length === 0) {
				console.warn(`Tour "${tourId}" no encontrado o sin pasos`);
				return this;
			}

			// Ocultar popover si hay uno activo
			this.hidePopover();

			activeTour = {
				id: tourId,
				steps: steps.filter(s => s), // Filtrar undefined
				currentStep: 0
			};

			// Crear overlay
			if (this.config.tourOverlay) {
				this.createTourOverlay();
			}

			// Callback
			if (typeof this.config.onTourStart === 'function') {
				this.config.onTourStart(tourId, steps);
			}

			// Emitir evento
			document.dispatchEvent(new CustomEvent('flavorHelp:tourStart', {
				detail: { tourId, totalSteps: steps.length }
			}));

			// Mostrar primer paso
			this.showTourStep(0);

			return this;
		}

		/**
         * Crea el overlay del tour
         */
		createTourOverlay() {
			// Overlay base
			const overlay = document.createElement('div');
			overlay.className = 'flavor-help-tour-overlay';
			overlay.id = 'flavor-help-tour-overlay';
			document.body.appendChild(overlay);

			// Spotlight
			if (this.config.tourSpotlight) {
				const spotlight = document.createElement('div');
				spotlight.className = 'flavor-help-tour-spotlight';
				spotlight.id = 'flavor-help-tour-spotlight';
				document.body.appendChild(spotlight);
			}

			requestAnimationFrame(() => {
				overlay.classList.add('visible');
			});
		}

		/**
         * Muestra un paso del tour
         */
		showTourStep(stepIndex) {
			if (!activeTour) {return this;}

			const steps = activeTour.steps;
			if (stepIndex < 0 || stepIndex >= steps.length) {return this;}

			const step = steps[stepIndex];
			const previousStep = steps[activeTour.currentStep];

			// Callback de salida del paso anterior
			if (previousStep && typeof previousStep.onLeave === 'function') {
				previousStep.onLeave(activeTour.currentStep, previousStep);
			}

			activeTour.currentStep = stepIndex;

			// Callback
			if (typeof this.config.onTourStep === 'function') {
				this.config.onTourStep(stepIndex, step, steps.length);
			}

			// Emitir evento
			document.dispatchEvent(new CustomEvent('flavorHelp:tourStep', {
				detail: {
					tourId: activeTour.id,
					stepIndex,
					step,
					totalSteps: steps.length
				}
			}));

			// Callback de entrada al nuevo paso
			if (typeof step.onEnter === 'function') {
				step.onEnter(stepIndex, step);
			}

			// Actualizar spotlight
			if (this.config.tourSpotlight && step.element) {
				this.updateTourSpotlight(step.element);
			}

			// Scroll al elemento
			if (step.element) {
				step.element.scrollIntoView({
					behavior: 'smooth',
					block: 'center'
				});
			}

			// Mostrar popover del tour
			setTimeout(() => {
				this.showTourPopover(step, stepIndex, steps.length);
			}, 300);

			return this;
		}

		/**
         * Actualiza el spotlight del tour
         */
		updateTourSpotlight(element = null) {
			const spotlight = document.getElementById('flavor-help-tour-spotlight');
			if (!spotlight) {return;}

			if (!element && activeTour) {
				element = activeTour.steps[activeTour.currentStep]?.element;
			}

			if (!element) {
				spotlight.style.display = 'none';
				return;
			}

			const rect = element.getBoundingClientRect();
			const padding = 8;

			spotlight.style.display = 'block';
			spotlight.style.top = `${rect.top + window.scrollY - padding}px`;
			spotlight.style.left = `${rect.left + window.scrollX - padding}px`;
			spotlight.style.width = `${rect.width + (padding * 2)}px`;
			spotlight.style.height = `${rect.height + (padding * 2)}px`;
		}

		/**
         * Muestra el popover del tour
         */
		showTourPopover(step, currentIndex, totalSteps) {
			// Eliminar popover anterior
			const existingPopover = document.querySelector('.flavor-help-tour-popover');
			if (existingPopover) {
				existingPopover.remove();
			}

			const popover = document.createElement('div');
			popover.className = 'flavor-help-tour-popover';

			// Header
			if (step.title) {
				const header = document.createElement('div');
				header.className = 'flavor-help-tour-header';
				header.innerHTML = `<h4>${step.title}</h4>`;
				popover.appendChild(header);
			}

			// Contenido
			const content = document.createElement('div');
			content.className = 'flavor-help-tour-content';
			content.innerHTML = this.formatContent(step.content);
			popover.appendChild(content);

			// Footer con navegación
			const footer = document.createElement('div');
			footer.className = 'flavor-help-tour-footer';

			// Indicador de progreso
			const progress = document.createElement('span');
			progress.className = 'flavor-help-tour-progress';
			progress.textContent = `${currentIndex + 1} de ${totalSteps}`;
			footer.appendChild(progress);

			// Botones
			const buttons = document.createElement('div');
			buttons.className = 'flavor-help-tour-buttons';

			if (currentIndex > 0) {
				const prevBtn = document.createElement('button');
				prevBtn.className = 'flavor-help-tour-btn flavor-help-tour-btn-secondary';
				prevBtn.textContent = 'Anterior';
				prevBtn.addEventListener('click', () => this.previousTourStep());
				buttons.appendChild(prevBtn);
			}

			const skipBtn = document.createElement('button');
			skipBtn.className = 'flavor-help-tour-btn flavor-help-tour-btn-skip';
			skipBtn.textContent = 'Saltar';
			skipBtn.addEventListener('click', () => this.endTour());
			buttons.appendChild(skipBtn);

			if (currentIndex < totalSteps - 1) {
				const nextBtn = document.createElement('button');
				nextBtn.className = 'flavor-help-tour-btn flavor-help-tour-btn-primary';
				nextBtn.textContent = 'Siguiente';
				nextBtn.addEventListener('click', () => this.nextTourStep());
				buttons.appendChild(nextBtn);
			} else {
				const finishBtn = document.createElement('button');
				finishBtn.className = 'flavor-help-tour-btn flavor-help-tour-btn-primary';
				finishBtn.textContent = 'Finalizar';
				finishBtn.addEventListener('click', () => this.endTour());
				buttons.appendChild(finishBtn);
			}

			footer.appendChild(buttons);
			popover.appendChild(footer);

			// Flecha
			const arrow = document.createElement('div');
			arrow.className = 'flavor-help-tour-arrow';
			popover.appendChild(arrow);

			document.body.appendChild(popover);

			// Posicionar
			if (step.element) {
				this.positionPopover(popover, step.element, arrow);
			} else {
				// Centrar si no hay elemento
				popover.style.position = 'fixed';
				popover.style.top = '50%';
				popover.style.left = '50%';
				popover.style.transform = 'translate(-50%, -50%)';
			}

			// Animar
			requestAnimationFrame(() => {
				popover.classList.add('visible');
			});
		}

		/**
         * Avanza al siguiente paso del tour
         */
		nextTourStep() {
			if (!activeTour) {return this;}

			const nextIndex = activeTour.currentStep + 1;
			if (nextIndex < activeTour.steps.length) {
				this.showTourStep(nextIndex);
			} else {
				this.endTour();
			}

			return this;
		}

		/**
         * Retrocede al paso anterior del tour
         */
		previousTourStep() {
			if (!activeTour) {return this;}

			const prevIndex = activeTour.currentStep - 1;
			if (prevIndex >= 0) {
				this.showTourStep(prevIndex);
			}

			return this;
		}

		/**
         * Finaliza el tour
         */
		endTour() {
			if (!activeTour) {return this;}

			const tourId = activeTour.id;

			// Eliminar overlay
			const overlay = document.getElementById('flavor-help-tour-overlay');
			if (overlay) {
				overlay.classList.remove('visible');
				setTimeout(() => overlay.remove(), 300);
			}

			// Eliminar spotlight
			const spotlight = document.getElementById('flavor-help-tour-spotlight');
			if (spotlight) {
				spotlight.remove();
			}

			// Eliminar popover
			const popover = document.querySelector('.flavor-help-tour-popover');
			if (popover) {
				popover.classList.remove('visible');
				setTimeout(() => popover.remove(), 300);
			}

			// Callback
			if (typeof this.config.onTourEnd === 'function') {
				this.config.onTourEnd(tourId);
			}

			// Emitir evento
			document.dispatchEvent(new CustomEvent('flavorHelp:tourEnd', {
				detail: { tourId }
			}));

			// Guardar progreso en localStorage
			try {
				const completedTours = JSON.parse(localStorage.getItem('flavorHelpCompletedTours') || '[]');
				if (!completedTours.includes(tourId)) {
					completedTours.push(tourId);
					localStorage.setItem('flavorHelpCompletedTours', JSON.stringify(completedTours));
				}
			} catch (e) {
				// Ignorar errores de localStorage
			}

			activeTour = null;

			return this;
		}

		/**
         * Verifica si un tour ya fue completado
         */
		isTourCompleted(tourId) {
			try {
				const completedTours = JSON.parse(localStorage.getItem('flavorHelpCompletedTours') || '[]');
				return completedTours.includes(tourId);
			} catch (e) {
				return false;
			}
		}

		/**
         * Resetea el estado de tours completados
         */
		resetCompletedTours() {
			try {
				localStorage.removeItem('flavorHelpCompletedTours');
			} catch (e) {
				// Ignorar errores
			}
			return this;
		}

		// ==========================================
		// UTILIDADES
		// ==========================================

		/**
         * Debounce helper
         */
		debounce(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		}

		/**
         * Actualiza la configuración
         */
		configure(newConfig) {
			this.config = { ...this.config, ...newConfig };
			config = this.config;
			return this;
		}

		/**
         * Obtiene la configuración actual
         */
		getConfig() {
			return { ...this.config };
		}

		/**
         * Obtiene el diccionario completo
         */
		getDictionary() {
			return { ...helpDictionary, ...customDictionary };
		}

		/**
         * Destruye la instancia y limpia
         */
		destroy() {
			this.hidePopover();
			this.endTour();

			// Eliminar iconos de ayuda
			document.querySelectorAll('.flavor-help-icon').forEach(icon => icon.remove());

			// Limpiar atributos
			document.querySelectorAll('[data-flavor-help-attached]').forEach(el => {
				delete el.dataset.flavorHelpAttached;
			});

			this.popovers.clear();
			this.tours.clear();
			this.initialized = false;

			return this;
		}

		/**
         * Reprocesa elementos (útil después de cargar contenido dinámico)
         */
		refresh() {
			this.processElements();
			return this;
		}
	}

	// ==========================================
	// API GLOBAL
	// ==========================================

	/**
     * Instancia global
     */
	const flavorHelp = new FlavorHelp();

	/**
     * API pública expuesta en window.FlavorHelp
     */
	window.FlavorHelp = {
		// Instancia principal
		instance: flavorHelp,

		// Inicializar
		init: (config) => {
			if (config) {flavorHelp.configure(config);}
			return flavorHelp.init();
		},

		// Configuración
		configure: (config) => flavorHelp.configure(config),
		getConfig: () => flavorHelp.getConfig(),

		// Diccionario
		addHelp: (key, text) => flavorHelp.addHelpText(key, text),
		addHelps: (texts) => flavorHelp.addHelpTexts(texts),
		getHelp: (key) => flavorHelp.getHelpText(key),
		getDictionary: () => flavorHelp.getDictionary(),

		// Popovers
		show: (element, content, key) => flavorHelp.showPopover(element, content, key),
		hide: () => flavorHelp.hidePopover(),
		attach: (element, text, key) => flavorHelp.attachHelp(element, text, key),

		// Tours
		defineTour: (id, steps) => flavorHelp.defineTour(id, steps),
		startTour: (id) => flavorHelp.startTour(id),
		nextStep: () => flavorHelp.nextTourStep(),
		prevStep: () => flavorHelp.previousTourStep(),
		endTour: () => flavorHelp.endTour(),
		isTourCompleted: (id) => flavorHelp.isTourCompleted(id),
		resetTours: () => flavorHelp.resetCompletedTours(),

		// Utilidades
		refresh: () => flavorHelp.refresh(),
		destroy: () => flavorHelp.destroy(),

		// Versión
		version: '1.0.0'
	};

	// ==========================================
	// AUTO-INICIALIZACIÓN
	// ==========================================

	// Inicializar cuando el DOM esté listo
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			// Solo auto-inicializar si hay elementos con data-help
			if (document.querySelector('[data-help], [data-help-id]')) {
				flavorHelp.init();
			}
		});
	} else {
		// DOM ya está listo
		if (document.querySelector('[data-help], [data-help-id]')) {
			flavorHelp.init();
		}
	}

})();
