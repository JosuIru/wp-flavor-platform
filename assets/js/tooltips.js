/**
 * Flavor Tooltips System
 * Sistema de tooltips accesibles sin dependencias externas
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */
(function () {
	'use strict';

	const TOOLTIP_ID = 'flavor-tooltip';
	const TOOLTIP_OFFSET = 8;
	const TOOLTIP_MAX_WIDTH = 250;

	window.FlavorTooltips = {
		tooltipElement: null,
		currentTarget: null,
		hideTimeout: null,

		/**
         * Inicializa el sistema de tooltips
         */
		init: function () {
			this.createContainer();
			this.bindEvents();
			this.observeDOM();
		},

		/**
         * Crea el contenedor del tooltip en el DOM
         */
		createContainer: function () {
			if (document.getElementById(TOOLTIP_ID)) {
				this.tooltipElement = document.getElementById(TOOLTIP_ID);
				return;
			}

			const tooltipContainer = document.createElement('div');
			tooltipContainer.id = TOOLTIP_ID;
			tooltipContainer.className = 'flavor-tooltip';
			tooltipContainer.setAttribute('role', 'tooltip');
			tooltipContainer.setAttribute('aria-hidden', 'true');
			document.body.appendChild(tooltipContainer);

			this.tooltipElement = tooltipContainer;
		},

		/**
         * Vincula eventos a elementos con data-tooltip
         */
		bindEvents: function () {
			const tooltipTargets = document.querySelectorAll('[data-tooltip]');

			tooltipTargets.forEach(targetElement => {
				this.attachEventsToElement(targetElement);
			});

			// Cerrar tooltip con Escape
			document.addEventListener('keydown', (keyboardEvent) => {
				if (keyboardEvent.key === 'Escape') {
					this.hide();
				}
			});

			// Ocultar en scroll para evitar desincronizacion
			window.addEventListener('scroll', () => {
				if (this.currentTarget) {
					this.hide();
				}
			}, { passive: true });
		},

		/**
         * Adjunta eventos a un elemento individual
         * @param {HTMLElement} targetElement - Elemento al que adjuntar eventos
         */
		attachEventsToElement: function (targetElement) {
			targetElement.addEventListener('mouseenter', (mouseEvent) => this.show(mouseEvent.target));
			targetElement.addEventListener('mouseleave', () => this.scheduleHide());
			targetElement.addEventListener('focus', (focusEvent) => this.show(focusEvent.target));
			targetElement.addEventListener('blur', () => this.hide());

			// Accesibilidad: vincular el tooltip al elemento
			targetElement.setAttribute('aria-describedby', TOOLTIP_ID);

			// Asegurar que el elemento sea focuseable si no lo es
			if (!targetElement.hasAttribute('tabindex') &&
                !['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(targetElement.tagName)) {
				targetElement.setAttribute('tabindex', '0');
			}
		},

		/**
         * Observa cambios en el DOM para tooltips dinamicos
         */
		observeDOM: function () {
			const mutationObserver = new MutationObserver((mutationsList) => {
				mutationsList.forEach((mutation) => {
					mutation.addedNodes.forEach((addedNode) => {
						if (addedNode.nodeType === Node.ELEMENT_NODE) {
							if (addedNode.hasAttribute && addedNode.hasAttribute('data-tooltip')) {
								this.attachEventsToElement(addedNode);
							}

							const nestedTooltipElements = addedNode.querySelectorAll ?
								addedNode.querySelectorAll('[data-tooltip]') : [];
							nestedTooltipElements.forEach(nestedElement => {
								this.attachEventsToElement(nestedElement);
							});
						}
					});
				});
			});

			mutationObserver.observe(document.body, {
				childList: true,
				subtree: true
			});
		},

		/**
         * Muestra el tooltip para un elemento
         * @param {HTMLElement} targetElement - Elemento que activa el tooltip
         */
		show: function (targetElement) {
			if (this.hideTimeout) {
				clearTimeout(this.hideTimeout);
				this.hideTimeout = null;
			}

			const tooltipText = targetElement.dataset.tooltip;
			if (!tooltipText) {return;}

			const tooltipPosition = targetElement.dataset.tooltipPosition || 'top';

			this.tooltipElement.textContent = tooltipText;
			this.tooltipElement.setAttribute('aria-hidden', 'false');
			this.tooltipElement.className = `flavor-tooltip flavor-tooltip--${tooltipPosition} flavor-tooltip--visible`;

			this.currentTarget = targetElement;
			this.calculatePosition(this.tooltipElement, targetElement, tooltipPosition);
		},

		/**
         * Programa la ocultacion del tooltip con un pequeno delay
         */
		scheduleHide: function () {
			this.hideTimeout = setTimeout(() => {
				this.hide();
			}, 100);
		},

		/**
         * Oculta el tooltip
         */
		hide: function () {
			if (this.hideTimeout) {
				clearTimeout(this.hideTimeout);
				this.hideTimeout = null;
			}

			this.tooltipElement.setAttribute('aria-hidden', 'true');
			this.tooltipElement.classList.remove('flavor-tooltip--visible');
			this.currentTarget = null;
		},

		/**
         * Calcula y aplica la posicion del tooltip
         * @param {HTMLElement} tooltipElement - Elemento tooltip
         * @param {HTMLElement} targetElement - Elemento objetivo
         * @param {string} preferredPosition - Posicion preferida (top, bottom, left, right)
         */
		calculatePosition: function (tooltipElement, targetElement, preferredPosition) {
			const targetRect = targetElement.getBoundingClientRect();
			const tooltipRect = tooltipElement.getBoundingClientRect();
			const viewportWidth = window.innerWidth;
			const viewportHeight = window.innerHeight;

			let topPosition, leftPosition;
			let finalPosition = preferredPosition;

			// Calcular posicion inicial segun preferencia
			const positionCalculations = this.getPositionCalculations(targetRect, tooltipRect);

			// Verificar si la posicion preferida cabe en el viewport
			if (!this.positionFitsViewport(positionCalculations[preferredPosition], tooltipRect, viewportWidth, viewportHeight)) {
				// Intentar posiciones alternativas
				const alternativePositions = this.getAlternativePositions(preferredPosition);

				for (const alternativePosition of alternativePositions) {
					if (this.positionFitsViewport(positionCalculations[alternativePosition], tooltipRect, viewportWidth, viewportHeight)) {
						finalPosition = alternativePosition;
						break;
					}
				}
			}

			const calculatedPosition = positionCalculations[finalPosition];
			topPosition = calculatedPosition.top;
			leftPosition = calculatedPosition.left;

			// Ajustar para que no salga del viewport
			leftPosition = Math.max(TOOLTIP_OFFSET, Math.min(leftPosition, viewportWidth - tooltipRect.width - TOOLTIP_OFFSET));
			topPosition = Math.max(TOOLTIP_OFFSET, topPosition);

			// Actualizar clase si la posicion cambio
			if (finalPosition !== preferredPosition) {
				tooltipElement.className = `flavor-tooltip flavor-tooltip--${finalPosition} flavor-tooltip--visible`;
			}

			tooltipElement.style.top = `${topPosition + window.scrollY}px`;
			tooltipElement.style.left = `${leftPosition + window.scrollX}px`;
		},

		/**
         * Obtiene los calculos de posicion para todas las direcciones
         * @param {DOMRect} targetRect - Rectangulo del elemento objetivo
         * @param {DOMRect} tooltipRect - Rectangulo del tooltip
         * @returns {Object} Objeto con calculos para cada posicion
         */
		getPositionCalculations: function (targetRect, tooltipRect) {
			const horizontalCenter = targetRect.left + (targetRect.width - tooltipRect.width) / 2;
			const verticalCenter = targetRect.top + (targetRect.height - tooltipRect.height) / 2;

			return {
				top: {
					top: targetRect.top - tooltipRect.height - TOOLTIP_OFFSET,
					left: horizontalCenter
				},
				bottom: {
					top: targetRect.bottom + TOOLTIP_OFFSET,
					left: horizontalCenter
				},
				left: {
					top: verticalCenter,
					left: targetRect.left - tooltipRect.width - TOOLTIP_OFFSET
				},
				right: {
					top: verticalCenter,
					left: targetRect.right + TOOLTIP_OFFSET
				}
			};
		},

		/**
         * Verifica si una posicion cabe en el viewport
         * @param {Object} positionData - Datos de posicion {top, left}
         * @param {DOMRect} tooltipRect - Rectangulo del tooltip
         * @param {number} viewportWidth - Ancho del viewport
         * @param {number} viewportHeight - Alto del viewport
         * @returns {boolean} True si cabe en el viewport
         */
		positionFitsViewport: function (positionData, tooltipRect, viewportWidth, viewportHeight) {
			return positionData.top >= 0 &&
                   positionData.left >= 0 &&
                   positionData.top + tooltipRect.height <= viewportHeight &&
                   positionData.left + tooltipRect.width <= viewportWidth;
		},

		/**
         * Obtiene posiciones alternativas en orden de preferencia
         * @param {string} currentPosition - Posicion actual
         * @returns {Array} Array de posiciones alternativas
         */
		getAlternativePositions: function (currentPosition) {
			const alternativesMap = {
				top: ['bottom', 'right', 'left'],
				bottom: ['top', 'right', 'left'],
				left: ['right', 'top', 'bottom'],
				right: ['left', 'top', 'bottom']
			};
			return alternativesMap[currentPosition] || ['top', 'bottom', 'left', 'right'];
		},

		/**
         * Anade tooltips dinamicamente a elementos
         * @param {string} selector - Selector CSS
         * @param {string} tooltipText - Texto del tooltip
         * @param {string} position - Posicion del tooltip (top, bottom, left, right)
         */
		add: function (selector, tooltipText, position = 'top') {
			const targetElements = document.querySelectorAll(selector);

			targetElements.forEach(targetElement => {
				targetElement.dataset.tooltip = tooltipText;
				targetElement.dataset.tooltipPosition = position;
				this.attachEventsToElement(targetElement);
			});
		},

		/**
         * Elimina el tooltip de elementos
         * @param {string} selector - Selector CSS
         */
		remove: function (selector) {
			const targetElements = document.querySelectorAll(selector);

			targetElements.forEach(targetElement => {
				delete targetElement.dataset.tooltip;
				delete targetElement.dataset.tooltipPosition;
				targetElement.removeAttribute('aria-describedby');
			});
		},

		/**
         * Actualiza el texto de un tooltip existente
         * @param {string} selector - Selector CSS
         * @param {string} newText - Nuevo texto
         */
		update: function (selector, newText) {
			const targetElements = document.querySelectorAll(selector);

			targetElements.forEach(targetElement => {
				targetElement.dataset.tooltip = newText;

				// Si este elemento tiene el tooltip visible, actualizarlo
				if (this.currentTarget === targetElement) {
					this.tooltipElement.textContent = newText;
				}
			});
		},

		/**
         * Destruye el sistema de tooltips
         */
		destroy: function () {
			if (this.tooltipElement && this.tooltipElement.parentNode) {
				this.tooltipElement.parentNode.removeChild(this.tooltipElement);
			}
			this.tooltipElement = null;
			this.currentTarget = null;
		}
	};

	// Inicializar cuando el DOM este listo
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => FlavorTooltips.init());
	} else {
		FlavorTooltips.init();
	}
})();
