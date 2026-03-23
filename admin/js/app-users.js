/**
 * App Users Panel JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function($) {
    'use strict';

    const AppUsers = {
        currentPage: 1,
        totalPages: 1,
        filters: {
            search: '',
            platform: '',
            status: ''
        },

        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadUsers();
        },

        cacheElements: function() {
            this.$container = $('.flavor-app-users');
            this.$usersList = $('#users-list');
            this.$searchInput = $('#search-users');
            this.$platformFilter = $('#filter-platform');
            this.$statusFilter = $('#filter-status');
            this.$userModal = $('#user-modal');
            this.$broadcastModal = $('#broadcast-modal');
            this.$pagination = $('.tablenav-pages');
        },

        bindEvents: function() {
            const self = this;

            // Search
            this.$searchInput.on('input', _.debounce(function() {
                self.filters.search = $(this).val();
                self.currentPage = 1;
                self.loadUsers();
            }, 300));

            // Filters
            this.$platformFilter.on('change', function() {
                self.filters.platform = $(this).val();
                self.currentPage = 1;
                self.loadUsers();
            });

            this.$statusFilter.on('change', function() {
                self.filters.status = $(this).val();
                self.currentPage = 1;
                self.loadUsers();
            });

            // View user details
            this.$usersList.on('click', '.btn-view', function() {
                const userId = $(this).closest('tr').data('user-id');
                self.showUserDetails(userId);
            });

            // Send push to single user
            this.$usersList.on('click', '.btn-push', function() {
                const userId = $(this).closest('tr').data('user-id');
                self.openPushModal(userId);
            });

            // Broadcast button
            $('#send-broadcast').on('click', function() {
                self.openPushModal();
            });

            // Export button
            $('#export-users').on('click', function() {
                self.exportUsers();
            });

            // Pagination
            this.$pagination.on('click', '.prev-page', function() {
                if (self.currentPage > 1) {
                    self.currentPage--;
                    self.loadUsers();
                }
            });

            this.$pagination.on('click', '.next-page', function() {
                if (self.currentPage < self.totalPages) {
                    self.currentPage++;
                    self.loadUsers();
                }
            });

            // Close modals
            $('.user-modal-overlay').on('click', function(e) {
                if ($(e.target).is('.user-modal-overlay')) {
                    self.closeModals();
                }
            });

            $('.user-modal-close, .btn-cancel').on('click', function() {
                self.closeModals();
            });

            // Send push notification
            $('#send-push-btn').on('click', function() {
                self.sendPushNotification();
            });

            // Revoke device
            this.$userModal.on('click', '.btn-revoke', function() {
                const deviceId = $(this).data('device-id');
                self.revokeDevice(deviceId);
            });
        },

        loadUsers: function() {
            const self = this;

            this.$usersList.html(`
                <tr class="loading-row">
                    <td colspan="7">
                        <span class="spinner is-active"></span>
                        ${flavorAppUsers.i18n.loading}
                    </td>
                </tr>
            `);

            $.ajax({
                url: flavorAppUsers.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_app_users',
                    nonce: flavorAppUsers.nonce,
                    page: this.currentPage,
                    search: this.filters.search,
                    platform: this.filters.platform,
                    status: this.filters.status
                },
                success: function(response) {
                    if (response.success) {
                        self.renderUsers(response.data.users);
                        self.updatePagination(response.data);
                    } else {
                        self.showError(response.data.message);
                    }
                },
                error: function() {
                    self.showError('Error de conexión');
                }
            });
        },

        renderUsers: function(users) {
            if (users.length === 0) {
                this.$usersList.html(`
                    <tr>
                        <td colspan="7">
                            <div class="no-users">
                                <span class="dashicons dashicons-smartphone"></span>
                                <h3>${flavorAppUsers.i18n.no_results}</h3>
                                <p>No hay usuarios de la app que coincidan con los filtros.</p>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }

            const html = users.map(function(user) {
                const platforms = user.platforms.split(',').map(function(p) {
                    return `<span class="platform-badge ${p.trim()}">${p.trim()}</span>`;
                }).join('');

                const isRecent = user.last_seen_raw &&
                    new Date(user.last_seen_raw) > new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);

                return `
                    <tr data-user-id="${user.id}">
                        <td class="column-avatar">
                            <img src="${user.avatar}" alt="${user.display_name}">
                        </td>
                        <td class="column-user">
                            <span class="user-name">${user.display_name || user.username}</span>
                            <span class="user-email">${user.email}</span>
                        </td>
                        <td class="column-devices">
                            <span class="device-count">${user.device_count}</span>
                        </td>
                        <td class="column-platform">
                            <div class="platform-badges">${platforms}</div>
                        </td>
                        <td class="column-last-seen">
                            <span class="last-seen ${isRecent ? 'recent' : ''}">${user.last_seen}</span>
                        </td>
                        <td class="column-sessions">
                            <span class="session-count">${user.session_count}</span>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <button type="button" class="btn-view" title="Ver detalles">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="btn-push" title="Enviar push">
                                    <span class="dashicons dashicons-megaphone"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            this.$usersList.html(html);
        },

        updatePagination: function(data) {
            this.totalPages = data.total_pages;

            this.$pagination.find('.displaying-num').text(
                `${data.total} elementos`
            );
            this.$pagination.find('.current-page').text(data.page);
            this.$pagination.find('.total-pages').text(data.total_pages);

            this.$pagination.find('.prev-page').prop('disabled', data.page <= 1);
            this.$pagination.find('.next-page').prop('disabled', data.page >= data.total_pages);
        },

        showUserDetails: function(userId) {
            const self = this;
            const $modal = this.$userModal;
            const $body = $modal.find('.user-modal-body');

            $body.html('<div class="loading"><span class="spinner is-active"></span> Cargando...</div>');
            $modal.addClass('is-visible');

            $.ajax({
                url: flavorAppUsers.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_user_devices',
                    nonce: flavorAppUsers.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderUserDetails(response.data);
                    } else {
                        $body.html('<p class="error">Error al cargar detalles</p>');
                    }
                }
            });
        },

        renderUserDetails: function(data) {
            const user = data.user;
            const devices = data.devices;
            const sessions = data.sessions;

            let devicesHtml = devices.map(function(device) {
                const icon = device.platform === 'ios' ? 'apple' : 'smartphone';
                const info = JSON.parse(device.device_info || '{}');

                return `
                    <div class="device-item">
                        <div class="device-info">
                            <span class="dashicons dashicons-${icon}"></span>
                            <div>
                                <div class="device-name">${info.model || device.platform}</div>
                                <div class="device-meta">v${device.app_version || '?'} • ${device.status}</div>
                            </div>
                        </div>
                        <div class="device-actions">
                            ${device.status === 'active' ?
                                `<button type="button" class="button btn-revoke" data-device-id="${device.device_id}">Revocar</button>`
                                : '<span class="revoked">Revocado</span>'}
                        </div>
                    </div>
                `;
            }).join('');

            let sessionsHtml = sessions.map(function(session) {
                const started = new Date(session.started_at).toLocaleString();
                let duration = '-';
                if (session.ended_at) {
                    const diff = new Date(session.ended_at) - new Date(session.started_at);
                    duration = Math.round(diff / 60000) + 'm';
                }

                return `
                    <div class="session-item">
                        <span class="session-time">${started}</span>
                        <span class="session-duration">${duration}</span>
                    </div>
                `;
            }).join('');

            const html = `
                <div class="user-detail-header">
                    <img src="${user.avatar}" alt="${user.display_name}">
                    <div class="user-detail-info">
                        <h4>${user.display_name || user.username}</h4>
                        <div class="email">${user.email}</div>
                        <div class="registered">Registrado: ${new Date(user.registered).toLocaleDateString()}</div>
                    </div>
                </div>

                <div class="devices-list">
                    <h5><span class="dashicons dashicons-smartphone"></span> Dispositivos (${devices.length})</h5>
                    ${devicesHtml || '<p>Sin dispositivos registrados</p>'}
                </div>

                <div class="sessions-list">
                    <h5><span class="dashicons dashicons-clock"></span> Últimas sesiones</h5>
                    ${sessionsHtml || '<p>Sin sesiones recientes</p>'}
                </div>
            `;

            this.$userModal.find('.user-modal-body').html(html);
        },

        openPushModal: function(userId) {
            const $modal = this.$broadcastModal;
            $modal.data('userId', userId || null);
            $modal.find('#broadcast-form')[0].reset();

            if (userId) {
                $modal.find('.user-modal-header h3').text('Enviar Notificación a Usuario');
                $modal.find('#push-target').closest('.form-group').hide();
            } else {
                $modal.find('.user-modal-header h3').text('Enviar Notificación Push');
                $modal.find('#push-target').closest('.form-group').show();
            }

            $modal.addClass('is-visible');
        },

        sendPushNotification: function() {
            const self = this;
            const $modal = this.$broadcastModal;
            const userId = $modal.data('userId');

            const data = {
                action: 'flavor_send_push_notification',
                nonce: flavorAppUsers.nonce,
                title: $('#push-title').val(),
                body: $('#push-body').val(),
                target: userId ? 'user_' + userId : $('#push-target').val(),
                data: $('#push-data').val()
            };

            if (!data.title || !data.body) {
                alert('Título y mensaje son requeridos');
                return;
            }

            const $btn = $('#send-push-btn');
            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: flavorAppUsers.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Enviar');

                    if (response.success) {
                        alert(response.data.message);
                        self.closeModals();
                    } else {
                        alert(response.data.message || 'Error al enviar');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Enviar');
                    alert('Error de conexión');
                }
            });
        },

        revokeDevice: function(deviceId) {
            const self = this;

            if (!confirm(flavorAppUsers.i18n.confirm_revoke)) {
                return;
            }

            $.ajax({
                url: flavorAppUsers.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_revoke_device',
                    nonce: flavorAppUsers.nonce,
                    device_id: deviceId
                },
                success: function(response) {
                    if (response.success) {
                        // Reload current user details
                        const userId = self.$userModal.find('[data-device-id="' + deviceId + '"]')
                            .closest('.user-modal-body').data('userId');
                        if (userId) {
                            self.showUserDetails(userId);
                        }
                        self.loadUsers();
                    }
                }
            });
        },

        exportUsers: function() {
            const self = this;

            $.ajax({
                url: flavorAppUsers.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_export_app_users',
                    nonce: flavorAppUsers.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.downloadCSV(response.data.data, 'app-users.csv');
                    }
                }
            });
        },

        downloadCSV: function(data, filename) {
            const csv = data.map(function(row) {
                return row.map(function(cell) {
                    if (typeof cell === 'string' && cell.includes(',')) {
                        return '"' + cell.replace(/"/g, '""') + '"';
                    }
                    return cell;
                }).join(',');
            }).join('\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        },

        closeModals: function() {
            $('.user-modal-overlay').removeClass('is-visible');
        },

        showError: function(message) {
            this.$usersList.html(`
                <tr>
                    <td colspan="7">
                        <div class="no-users">
                            <span class="dashicons dashicons-warning"></span>
                            <h3>Error</h3>
                            <p>${message}</p>
                        </div>
                    </td>
                </tr>
            `);
        }
    };

    $(document).ready(function() {
        if ($('.flavor-app-users').length) {
            AppUsers.init();
        }
    });

})(jQuery);
