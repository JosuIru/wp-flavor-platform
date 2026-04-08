/**
 * Sistema de Notificaciones - Frontend
 *
 * @package FlavorChatIA
 */

(function ($) {
	'use strict';

	const FlavorNotifications = {
		pollInterval: null,
		pollDelay: 30000, // 30 segundos
		unreadCount: 0,
		isOpen: false,
		notifications: [],

		/**
         * Inicializar
         */
		init: function () {
			this.bindEvents();
			this.createWidget();
			this.loadNotifications();
			this.startPolling();
		},

		/**
         * Crear widget de notificaciones
         */
		createWidget: function () {
			if ($('#flavor-notifications-widget').length) {return;}

			const html = `
                <div id="flavor-notifications-widget" class="flavor-notif-widget">
                    <button type="button" class="flavor-notif-trigger" aria-label="Notificaciones">
                        <span class="dashicons dashicons-bell"></span>
                        <span class="flavor-notif-badge" style="display: none;">0</span>
                    </button>
                    <div class="flavor-notif-dropdown" style="display: none;">
                        <div class="flavor-notif-header">
                            <h3>Notificaciones</h3>
                            <div class="flavor-notif-actions">
                                <button type="button" class="flavor-notif-mark-all" title="Marcar todas como leídas">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </button>
                                <a href="${flavorNotifications.preferencesUrl || '#'}" class="flavor-notif-settings" title="Configuración">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </a>
                            </div>
                        </div>
                        <div class="flavor-notif-list">
                            <div class="flavor-notif-loading">
                                <span class="spinner is-active"></span>
                                Cargando...
                            </div>
                        </div>
                        <div class="flavor-notif-footer">
                            <a href="${flavorNotifications.allNotificationsUrl || '#'}">Ver todas las notificaciones</a>
                        </div>
                    </div>
                </div>
            `;

			// Insertar en el lugar adecuado
			if ($('#wpadminbar').length) {
				$('#wp-admin-bar-top-secondary').prepend(`
                    <li id="wp-admin-bar-flavor-notifications" class="menupop">
                        ${html}
                    </li>
                `);
			} else {
				$('body').append(html);
			}
		},

		/**
         * Vincular eventos
         */
		bindEvents: function () {
			const self = this;

			// Toggle dropdown
			$(document).on('click', '.flavor-notif-trigger', function (e) {
				e.stopPropagation();
				self.toggleDropdown();
			});

			// Cerrar al hacer clic fuera
			$(document).on('click', function (e) {
				if (!$(e.target).closest('#flavor-notifications-widget').length) {
					self.closeDropdown();
				}
			});

			// Marcar como leída
			$(document).on('click', '.flavor-notif-item:not(.read)', function () {
				const id = $(this).data('id');
				self.markAsRead(id);
			});

			// Descartar
			$(document).on('click', '.flavor-notif-dismiss', function (e) {
				e.stopPropagation();
				const id = $(this).closest('.flavor-notif-item').data('id');
				self.dismissNotification(id);
			});

			// Marcar todas como leídas
			$(document).on('click', '.flavor-notif-mark-all', function () {
				self.markAllAsRead();
			});

			// Cargar más
			$(document).on('click', '.flavor-notif-load-more', function () {
				self.loadMore();
			});

			// Tecla ESC para cerrar
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && self.isOpen) {
					self.closeDropdown();
				}
			});
		},

		/**
         * Toggle dropdown
         */
		toggleDropdown: function () {
			if (this.isOpen) {
				this.closeDropdown();
			} else {
				this.openDropdown();
			}
		},

		/**
         * Abrir dropdown
         */
		openDropdown: function () {
			$('.flavor-notif-dropdown').slideDown(200);
			$('.flavor-notif-trigger').addClass('active');
			this.isOpen = true;
			this.loadNotifications();
		},

		/**
         * Cerrar dropdown
         */
		closeDropdown: function () {
			$('.flavor-notif-dropdown').slideUp(200);
			$('.flavor-notif-trigger').removeClass('active');
			this.isOpen = false;
		},

		/**
         * Cargar notificaciones
         */
		loadNotifications: function (append = false) {
			const self = this;
			const offset = append ? this.notifications.length : 0;

			if (!append) {
				$('.flavor-notif-list').html(`
                    <div class="flavor-notif-loading">
                        <span class="spinner is-active"></span>
                        Cargando...
                    </div>
                `);
			}

			$.ajax({
				url: flavorNotifications.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_notifications',
					nonce: flavorNotifications.nonce,
					limit: 10,
					offset: offset,
				},
				success: function (response) {
					if (response.success) {
						if (append) {
							self.notifications = self.notifications.concat(response.data.notifications);
						} else {
							self.notifications = response.data.notifications;
						}
						self.updateUnreadCount(response.data.unread_count);
						self.renderNotifications(append);
					}
				},
				error: function () {
					$('.flavor-notif-list').html(`
                        <div class="flavor-notif-empty">
                            Error al cargar notificaciones
                        </div>
                    `);
				}
			});
		},

		/**
         * Renderizar notificaciones
         */
		renderNotifications: function (append = false) {
			const self = this;
			let html = '';

			if (this.notifications.length === 0) {
				html = `
                    <div class="flavor-notif-empty">
                        <span class="dashicons dashicons-bell"></span>
                        <p>No tienes notificaciones</p>
                    </div>
                `;
			} else {
				this.notifications.forEach(function (notif) {
					html += self.renderNotificationItem(notif);
				});

				// Botón cargar más si hay más
				if (this.notifications.length % 10 === 0) {
					html += `
                        <button type="button" class="flavor-notif-load-more">
                            Cargar más
                        </button>
                    `;
				}
			}

			if (append) {
				$('.flavor-notif-load-more').remove();
				$('.flavor-notif-list').append(html);
			} else {
				$('.flavor-notif-list').html(html);
			}
		},

		/**
         * Renderizar item de notificación
         */
		renderNotificationItem: function (notif) {
			const readClass = notif.is_read == 1 ? 'read' : '';
			const priorityClass = notif.priority !== 'normal' ? `priority-${notif.priority}` : '';
			const timeAgo = this.timeAgo(notif.created_at);

			return `
                <div class="flavor-notif-item ${readClass} ${priorityClass}" data-id="${notif.id}">
                    <div class="flavor-notif-icon" style="background-color: ${notif.color}20; color: ${notif.color};">
                        <span class="dashicons ${notif.icon}"></span>
                    </div>
                    <div class="flavor-notif-content">
                        <div class="flavor-notif-title">${this.escapeHtml(notif.title)}</div>
                        <div class="flavor-notif-message">${this.escapeHtml(notif.message)}</div>
                        <div class="flavor-notif-time">${timeAgo}</div>
                    </div>
                    <button type="button" class="flavor-notif-dismiss" title="Descartar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    ${notif.link ? `<a href="${notif.link}" class="flavor-notif-link"></a>` : ''}
                </div>
            `;
		},

		/**
         * Marcar como leída
         */
		markAsRead: function (id) {
			const self = this;
			const $item = $(`.flavor-notif-item[data-id="${id}"]`);

			$item.addClass('read');

			$.ajax({
				url: flavorNotifications.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_mark_notification_read',
					nonce: flavorNotifications.nonce,
					notification_id: id,
				},
				success: function (response) {
					if (response.success) {
						self.updateUnreadCount(response.data.unread_count);

						// Actualizar en array local
						const notif = self.notifications.find(n => n.id == id);
						if (notif) {notif.is_read = 1;}
					}
				}
			});

			// Si tiene link, navegar
			const link = $item.find('.flavor-notif-link').attr('href');
			if (link && link !== '#') {
				window.location.href = link;
			}
		},

		/**
         * Marcar todas como leídas
         */
		markAllAsRead: function () {
			const self = this;

			$.ajax({
				url: flavorNotifications.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_mark_notification_read',
					nonce: flavorNotifications.nonce,
					notification_id: 0, // 0 = todas
				},
				success: function (response) {
					if (response.success) {
						self.updateUnreadCount(0);
						$('.flavor-notif-item').addClass('read');
						self.notifications.forEach(n => n.is_read = 1);
					}
				}
			});
		},

		/**
         * Descartar notificación
         */
		dismissNotification: function (id) {
			const self = this;
			const $item = $(`.flavor-notif-item[data-id="${id}"]`);

			$item.slideUp(200, function () {
				$(this).remove();

				// Remover del array local
				self.notifications = self.notifications.filter(n => n.id != id);

				if (self.notifications.length === 0) {
					self.renderNotifications();
				}
			});

			$.ajax({
				url: flavorNotifications.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_dismiss_notification',
					nonce: flavorNotifications.nonce,
					notification_id: id,
				},
				success: function (response) {
					if (response.success) {
						self.updateUnreadCount(response.data.unread_count);
					}
				}
			});
		},

		/**
         * Actualizar contador de no leídas
         */
		updateUnreadCount: function (count) {
			this.unreadCount = count;
			const $badge = $('.flavor-notif-badge');

			if (count > 0) {
				$badge.text(count > 99 ? '99+' : count).show();
				$('.flavor-notif-trigger').addClass('has-unread');
			} else {
				$badge.hide();
				$('.flavor-notif-trigger').removeClass('has-unread');
			}

			// Actualizar título de página
			this.updatePageTitle(count);
		},

		/**
         * Actualizar título de página
         */
		updatePageTitle: function (count) {
			let title = document.title;

			// Remover contador existente
			title = title.replace(/^\(\d+\+?\)\s*/, '');

			if (count > 0) {
				title = `(${count > 99 ? '99+' : count}) ${title}`;
			}

			document.title = title;
		},

		/**
         * Cargar más notificaciones
         */
		loadMore: function () {
			const $btn = $('.flavor-notif-load-more');
			$btn.text('Cargando...').prop('disabled', true);
			this.loadNotifications(true);
		},

		/**
         * Iniciar polling
         */
		startPolling: function () {
			const self = this;

			this.pollInterval = setInterval(function () {
				self.checkNewNotifications();
			}, this.pollDelay);

			// Parar polling cuando la pestaña no está visible
			document.addEventListener('visibilitychange', function () {
				if (document.hidden) {
					clearInterval(self.pollInterval);
				} else {
					self.checkNewNotifications();
					self.startPolling();
				}
			});
		},

		/**
         * Verificar nuevas notificaciones
         */
		checkNewNotifications: function () {
			const self = this;

			$.ajax({
				url: flavorNotifications.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_notifications',
					nonce: flavorNotifications.nonce,
					limit: 1,
					unread_only: true,
				},
				success: function (response) {
					if (response.success) {
						const newCount = response.data.unread_count;

						if (newCount > self.unreadCount) {
							// Hay nuevas notificaciones
							self.showNewNotificationAlert(response.data.notifications[0]);
						}

						self.updateUnreadCount(newCount);
					}
				}
			});
		},

		/**
         * Mostrar alerta de nueva notificación
         */
		showNewNotificationAlert: function (notif) {
			// Notificación del navegador si está permitida
			if ('Notification' in window && Notification.permission === 'granted') {
				new Notification(notif.title, {
					body: notif.message,
					icon: flavorNotifications.iconUrl || '/favicon.ico',
					tag: 'flavor-notification-' + notif.id,
				});
			}

			// Toast en la UI
			this.showToast(notif);

			// Sonido (opcional)
			if (flavorNotifications.soundEnabled) {
				this.playNotificationSound();
			}
		},

		/**
         * Mostrar toast
         */
		showToast: function (notif) {
			const toast = $(`
                <div class="flavor-notif-toast" data-id="${notif.id}">
                    <div class="flavor-notif-toast-icon" style="background-color: ${notif.color}20; color: ${notif.color};">
                        <span class="dashicons ${notif.icon}"></span>
                    </div>
                    <div class="flavor-notif-toast-content">
                        <div class="flavor-notif-toast-title">${this.escapeHtml(notif.title)}</div>
                        <div class="flavor-notif-toast-message">${this.escapeHtml(notif.message)}</div>
                    </div>
                    <button type="button" class="flavor-notif-toast-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `);

			// Crear contenedor si no existe
			if (!$('#flavor-notif-toasts').length) {
				$('body').append('<div id="flavor-notif-toasts"></div>');
			}

			$('#flavor-notif-toasts').append(toast);

			// Animar entrada
			setTimeout(() => toast.addClass('show'), 10);

			// Auto-cerrar después de 5 segundos
			setTimeout(() => {
				toast.removeClass('show');
				setTimeout(() => toast.remove(), 300);
			}, 5000);

			// Cerrar manualmente
			toast.find('.flavor-notif-toast-close').on('click', function () {
				toast.removeClass('show');
				setTimeout(() => toast.remove(), 300);
			});

			// Click para ver
			toast.on('click', function (e) {
				if (!$(e.target).hasClass('flavor-notif-toast-close')) {
					if (notif.link) {
						window.location.href = notif.link;
					}
				}
			});
		},

		/**
         * Reproducir sonido de notificación
         */
		playNotificationSound: function () {
			const audio = new Audio(flavorNotifications.soundUrl || '');
			audio.volume = 0.5;
			audio.play().catch(() => {}); // Ignorar errores de autoplay
		},

		/**
         * Solicitar permiso para notificaciones del navegador
         */
		requestBrowserPermission: function () {
			if ('Notification' in window && Notification.permission === 'default') {
				Notification.requestPermission();
			}
		},

		/**
         * Helpers
         */
		timeAgo: function (dateString) {
			const date = new Date(dateString);
			const now = new Date();
			const seconds = Math.floor((now - date) / 1000);

			const intervals = [
				{ label: 'año', seconds: 31536000 },
				{ label: 'mes', seconds: 2592000 },
				{ label: 'semana', seconds: 604800 },
				{ label: 'día', seconds: 86400 },
				{ label: 'hora', seconds: 3600 },
				{ label: 'minuto', seconds: 60 },
			];

			for (const interval of intervals) {
				const count = Math.floor(seconds / interval.seconds);
				if (count >= 1) {
					const plural = count > 1 ? (interval.label === 'mes' ? 'es' : 's') : '';
					return `hace ${count} ${interval.label}${plural}`;
				}
			}

			return 'ahora mismo';
		},

		escapeHtml: function (text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
		}
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function () {
		if (typeof flavorNotifications !== 'undefined') {
			FlavorNotifications.init();

			// Solicitar permiso para notificaciones del navegador
			FlavorNotifications.requestBrowserPermission();
		}
	});

	// Exponer globalmente
	window.FlavorNotifications = FlavorNotifications;

})(jQuery);
