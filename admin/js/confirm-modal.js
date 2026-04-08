/**
 * Flavor Confirm Modal
 * Reemplaza confirm() con modales accesibles
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */
(function () {
	'use strict';

	window.FlavorConfirm = {
		/**
         * Inicializa el sistema de modales
         */
		init: function () {
			this.createModal();
			this.bindTriggers();
		},

		/**
         * Crea el elemento DOM del modal
         */
		createModal: function () {
			if (document.getElementById('flavor-confirm-modal')) {return;}

			const modalElement = document.createElement('div');
			modalElement.id = 'flavor-confirm-modal';
			modalElement.className = 'flavor-modal';
			modalElement.setAttribute('role', 'dialog');
			modalElement.setAttribute('aria-modal', 'true');
			modalElement.setAttribute('aria-labelledby', 'flavor-confirm-title');
			modalElement.innerHTML = `
                <div class="flavor-modal__backdrop"></div>
                <div class="flavor-modal__container">
                    <div class="flavor-modal__header">
                        <h2 id="flavor-confirm-title" class="flavor-modal__title">Confirmar acción</h2>
                        <button type="button" class="flavor-modal__close" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="flavor-modal__body">
                        <p id="flavor-confirm-message"></p>
                    </div>
                    <div class="flavor-modal__footer">
                        <button type="button" class="button flavor-modal__cancel">Cancelar</button>
                        <button type="button" class="button button-primary flavor-modal__confirm">Confirmar</button>
                    </div>
                </div>
            `;
			document.body.appendChild(modalElement);

			// Almacenar referencias a elementos del modal
			this.modal = modalElement;
			this.backdrop = modalElement.querySelector('.flavor-modal__backdrop');
			this.container = modalElement.querySelector('.flavor-modal__container');
			this.titleElement = modalElement.querySelector('#flavor-confirm-title');
			this.messageElement = modalElement.querySelector('#flavor-confirm-message');
			this.confirmButton = modalElement.querySelector('.flavor-modal__confirm');
			this.cancelButton = modalElement.querySelector('.flavor-modal__cancel');
			this.closeButton = modalElement.querySelector('.flavor-modal__close');

			// Event listeners
			this.backdrop.addEventListener('click', () => this.close(false));
			this.closeButton.addEventListener('click', () => this.close(false));
			this.cancelButton.addEventListener('click', () => this.close(false));
			this.confirmButton.addEventListener('click', () => this.close(true));

			// Escape key
			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && this.modal.classList.contains('flavor-modal--open')) {
					this.close(false);
				}
			});

			// Trap focus dentro del modal
			this.modal.addEventListener('keydown', (event) => {
				if (event.key === 'Tab') {
					this.handleTabKey(event);
				}
			});
		},

		/**
         * Maneja la navegación con Tab dentro del modal
         * @param {KeyboardEvent} event
         */
		handleTabKey: function (event) {
			const focusableElements = this.modal.querySelectorAll(
				'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);
			const firstFocusable = focusableElements[0];
			const lastFocusable = focusableElements[focusableElements.length - 1];

			if (event.shiftKey) {
				if (document.activeElement === firstFocusable) {
					lastFocusable.focus();
					event.preventDefault();
				}
			} else {
				if (document.activeElement === lastFocusable) {
					firstFocusable.focus();
					event.preventDefault();
				}
			}
		},

		/**
         * Vincula triggers automáticos para formularios y elementos con data-confirm
         */
		bindTriggers: function () {
			// Reemplazar onsubmit con confirm()
			document.querySelectorAll('form[onsubmit*="confirm"]').forEach(formElement => {
				const originalOnsubmit = formElement.getAttribute('onsubmit');
				const confirmMessage = originalOnsubmit.match(/confirm\(['"](.+?)['"]\)/)?.[1] || '¿Estás seguro?';

				formElement.removeAttribute('onsubmit');
				formElement.addEventListener('submit', async (submitEvent) => {
					submitEvent.preventDefault();
					const userConfirmed = await this.show({
						title: 'Confirmar acción',
						message: confirmMessage,
						confirmText: 'Sí, continuar',
						cancelText: 'Cancelar',
						type: 'warning'
					});
					if (userConfirmed) {
						// Crear un input hidden para indicar que ya fue confirmado
						const confirmedInput = document.createElement('input');
						confirmedInput.type = 'hidden';
						confirmedInput.name = '_flavor_confirmed';
						confirmedInput.value = '1';
						formElement.appendChild(confirmedInput);
						formElement.submit();
					}
				});
			});

			// Botones/enlaces con data-confirm
			document.querySelectorAll('[data-confirm]').forEach(triggerElement => {
				triggerElement.addEventListener('click', async (clickEvent) => {
					clickEvent.preventDefault();
					clickEvent.stopPropagation();

					const userConfirmed = await this.show({
						title: triggerElement.dataset.confirmTitle || 'Confirmar',
						message: triggerElement.dataset.confirm,
						confirmText: triggerElement.dataset.confirmYes || 'Confirmar',
						cancelText: triggerElement.dataset.confirmNo || 'Cancelar',
						type: triggerElement.dataset.confirmType || 'warning'
					});

					if (userConfirmed) {
						if (triggerElement.href) {
							window.location.href = triggerElement.href;
						} else if (triggerElement.form) {
							triggerElement.form.submit();
						} else if (triggerElement.type === 'submit') {
							const parentForm = triggerElement.closest('form');
							if (parentForm) {
								parentForm.submit();
							}
						}
					}
				});
			});

			// Observar cambios en el DOM para nuevos elementos
			this.observeDOMChanges();
		},

		/**
         * Observa cambios en el DOM para vincular nuevos elementos con data-confirm
         */
		observeDOMChanges: function () {
			const mutationObserver = new MutationObserver((mutationsList) => {
				for (const mutation of mutationsList) {
					if (mutation.type === 'childList') {
						mutation.addedNodes.forEach(addedNode => {
							if (addedNode.nodeType === Node.ELEMENT_NODE) {
								// Buscar elementos con data-confirm en el nodo añadido
								const confirmElements = addedNode.querySelectorAll
									? addedNode.querySelectorAll('[data-confirm]')
									: [];

								confirmElements.forEach(confirmElement => {
									if (!confirmElement.dataset.flavorConfirmBound) {
										this.bindSingleElement(confirmElement);
									}
								});

								// Si el propio nodo tiene data-confirm
								if (addedNode.dataset && addedNode.dataset.confirm && !addedNode.dataset.flavorConfirmBound) {
									this.bindSingleElement(addedNode);
								}
							}
						});
					}
				}
			});

			mutationObserver.observe(document.body, {
				childList: true,
				subtree: true
			});
		},

		/**
         * Vincula un único elemento con data-confirm
         * @param {HTMLElement} targetElement
         */
		bindSingleElement: function (targetElement) {
			targetElement.dataset.flavorConfirmBound = 'true';

			targetElement.addEventListener('click', async (clickEvent) => {
				clickEvent.preventDefault();
				clickEvent.stopPropagation();

				const userConfirmed = await this.show({
					title: targetElement.dataset.confirmTitle || 'Confirmar',
					message: targetElement.dataset.confirm,
					confirmText: targetElement.dataset.confirmYes || 'Confirmar',
					cancelText: targetElement.dataset.confirmNo || 'Cancelar',
					type: targetElement.dataset.confirmType || 'warning'
				});

				if (userConfirmed) {
					if (targetElement.href) {
						window.location.href = targetElement.href;
					} else if (targetElement.form) {
						targetElement.form.submit();
					} else if (targetElement.type === 'submit') {
						const parentForm = targetElement.closest('form');
						if (parentForm) {
							parentForm.submit();
						}
					}
				}
			});
		},

		/**
         * Muestra el modal de confirmación
         * @param {Object} options - Opciones del modal
         * @param {string} options.title - Título del modal
         * @param {string} options.message - Mensaje de confirmación
         * @param {string} options.confirmText - Texto del botón confirmar
         * @param {string} options.cancelText - Texto del botón cancelar
         * @param {string} options.type - Tipo: 'warning', 'danger', 'info'
         * @returns {Promise<boolean>}
         */
		show: function (options) {
			return new Promise((resolve) => {
				// Guardar elemento activo actual para restaurar después
				this.previousActiveElement = document.activeElement;

				// Configurar contenido del modal
				this.titleElement.textContent = options.title || 'Confirmar';
				this.messageElement.textContent = options.message || '¿Estás seguro?';
				this.confirmButton.textContent = options.confirmText || 'Confirmar';
				this.cancelButton.textContent = options.cancelText || 'Cancelar';

				// Configurar tipo (warning, danger, info)
				const modalType = options.type || 'warning';
				this.container.className = `flavor-modal__container flavor-modal--${modalType}`;

				// Configurar clases del botón confirmar según el tipo
				this.confirmButton.className = 'button flavor-modal__confirm';
				if (modalType === 'danger') {
					this.confirmButton.classList.add('button-link-delete');
				} else {
					this.confirmButton.classList.add('button-primary');
				}

				// Mostrar modal
				this.modal.classList.add('flavor-modal--open');
				document.body.classList.add('flavor-modal-open');

				// Enfocar el botón apropiado
				if (modalType === 'danger') {
					this.cancelButton.focus();
				} else {
					this.confirmButton.focus();
				}

				// Guardar resolver para usar en close()
				this._resolvePromise = resolve;
			});
		},

		/**
         * Cierra el modal y resuelve la promesa
         * @param {boolean} result - Resultado de la confirmación
         */
		close: function (result) {
			this.modal.classList.remove('flavor-modal--open');
			document.body.classList.remove('flavor-modal-open');

			// Restaurar foco al elemento anterior
			if (this.previousActiveElement && this.previousActiveElement.focus) {
				this.previousActiveElement.focus();
			}

			// Resolver la promesa
			if (this._resolvePromise) {
				this._resolvePromise(result);
				this._resolvePromise = null;
			}
		},

		/**
         * Método de conveniencia para confirmar acciones peligrosas
         * @param {string} message - Mensaje de confirmación
         * @returns {Promise<boolean>}
         */
		danger: function (message) {
			return this.show({
				title: '¿Estás seguro?',
				message: message,
				confirmText: 'Sí, eliminar',
				cancelText: 'Cancelar',
				type: 'danger'
			});
		},

		/**
         * Método de conveniencia para confirmaciones de advertencia
         * @param {string} message - Mensaje de confirmación
         * @returns {Promise<boolean>}
         */
		warning: function (message) {
			return this.show({
				title: 'Confirmar acción',
				message: message,
				confirmText: 'Continuar',
				cancelText: 'Cancelar',
				type: 'warning'
			});
		},

		/**
         * Método de conveniencia para confirmaciones informativas
         * @param {string} message - Mensaje de confirmación
         * @returns {Promise<boolean>}
         */
		info: function (message) {
			return this.show({
				title: 'Información',
				message: message,
				confirmText: 'Aceptar',
				cancelText: 'Cancelar',
				type: 'info'
			});
		}
	};

	// Inicializar cuando el DOM esté listo
	document.addEventListener('DOMContentLoaded', () => FlavorConfirm.init());

	// También exponer una función global para uso programático
	window.flavorConfirm = function (message, options = {}) {
		return FlavorConfirm.show({
			title: options.title || 'Confirmar',
			message: message,
			confirmText: options.confirmText || 'Confirmar',
			cancelText: options.cancelText || 'Cancelar',
			type: options.type || 'warning'
		});
	};
})();
