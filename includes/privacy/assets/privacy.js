/**
 * JavaScript del panel de privacidad RGPD
 *
 * @package Flavor_Chat_IA
 */

(function($) {
    'use strict';

    const FlavorPrivacy = {
        init: function() {
            this.bindEvents();
            this.loadDataSummary();
        },

        bindEvents: function() {
            // Tabs
            $('.flavor-tab-btn').on('click', this.handleTabClick);

            // Export data buttons
            $('#btn-export-data, #btn-request-export').on('click', this.handleExportRequest.bind(this));

            // Delete account
            $('#btn-delete-account, #btn-show-delete-form').on('click', this.showDeleteForm);
            $('#btn-cancel-delete').on('click', this.hideDeleteForm);
            $('#confirm-delete').on('change', this.toggleDeleteButton);
            $('#btn-confirm-delete').on('click', this.handleDeleteRequest.bind(this));

            // Consent toggles
            $('.flavor-consent-item input[type="checkbox"]').on('change', this.handleConsentChange.bind(this));
            $('#btn-save-consents').on('click', this.saveConsents.bind(this));

            // Modal
            $('.flavor-modal-close, .flavor-modal-overlay, #modal-cancel').on('click', this.closeModal);
        },

        handleTabClick: function(e) {
            e.preventDefault();
            const targetTab = $(this).data('tab');

            // Update button states
            $('.flavor-tab-btn').removeClass('active');
            $(this).addClass('active');

            // Update content visibility
            $('.flavor-tab-content').removeClass('active');
            $('#tab-' + targetTab).addClass('active');

            // Load data if switching to datos tab
            if (targetTab === 'datos') {
                FlavorPrivacy.loadDataCategories();
            }
        },

        loadDataSummary: function() {
            const container = $('#data-summary');

            $.ajax({
                url: flavorPrivacy.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_privacy_get_data',
                    nonce: flavorPrivacy.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorPrivacy.renderDataSummary(container, response.data);
                    } else {
                        container.html('<p class="flavor-text-muted">Error al cargar los datos</p>');
                    }
                },
                error: function() {
                    container.html('<p class="flavor-text-muted">Error de conexión</p>');
                }
            });
        },

        renderDataSummary: function(container, data) {
            const items = [
                { key: 'publicaciones', label: 'Publicaciones', icon: 'admin-post' },
                { key: 'comentarios', label: 'Comentarios', icon: 'admin-comments' },
                { key: 'reacciones', label: 'Reacciones', icon: 'heart' },
                { key: 'seguidores', label: 'Seguidores', icon: 'groups' },
                { key: 'siguiendo', label: 'Siguiendo', icon: 'admin-users' },
                { key: 'mensajes_enviados', label: 'Mensajes enviados', icon: 'email' },
                { key: 'mensajes_recibidos', label: 'Mensajes recibidos', icon: 'email-alt' },
                { key: 'eventos_inscritos', label: 'Eventos', icon: 'calendar-alt' },
                { key: 'cursos_inscritos', label: 'Cursos', icon: 'welcome-learn-more' },
                { key: 'reservas', label: 'Reservas', icon: 'calendar' },
                { key: 'comunidades', label: 'Comunidades', icon: 'networking' },
                { key: 'marketplace_articulos', label: 'Anuncios', icon: 'cart' }
            ];

            let html = '';
            items.forEach(function(item) {
                const count = data[item.key] || 0;
                html += `
                    <div class="flavor-data-item">
                        <span class="count">${count}</span>
                        <span class="label">${item.label}</span>
                    </div>
                `;
            });

            container.html(html);
        },

        loadDataCategories: function() {
            const container = $('#data-categories');

            if (container.data('loaded')) {
                return;
            }

            $.ajax({
                url: flavorPrivacy.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_privacy_get_data',
                    nonce: flavorPrivacy.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorPrivacy.renderDataCategories(container, response.data);
                        container.data('loaded', true);
                    } else {
                        container.html('<p class="flavor-text-muted">Error al cargar los datos</p>');
                    }
                },
                error: function() {
                    container.html('<p class="flavor-text-muted">Error de conexión</p>');
                }
            });
        },

        renderDataCategories: function(container, data) {
            const categories = [
                { key: 'perfil', label: 'Perfil de usuario', icon: 'admin-users' },
                { key: 'publicaciones', label: 'Publicaciones', icon: 'admin-post', countKey: 'publicaciones' },
                { key: 'comentarios', label: 'Comentarios', icon: 'admin-comments', countKey: 'comentarios' },
                { key: 'mensajes', label: 'Mensajes', icon: 'email', countKey: 'mensajes_enviados' },
                { key: 'eventos', label: 'Inscripciones a eventos', icon: 'calendar-alt', countKey: 'eventos_inscritos' },
                { key: 'cursos', label: 'Inscripciones a cursos', icon: 'welcome-learn-more', countKey: 'cursos_inscritos' },
                { key: 'reservas', label: 'Reservas', icon: 'calendar', countKey: 'reservas' },
                { key: 'comunidades', label: 'Membresías de comunidades', icon: 'networking', countKey: 'comunidades' },
                { key: 'marketplace', label: 'Artículos en marketplace', icon: 'cart', countKey: 'marketplace_articulos' },
                { key: 'incidencias', label: 'Incidencias reportadas', icon: 'warning', countKey: 'incidencias' },
                { key: 'tramites', label: 'Trámites realizados', icon: 'clipboard', countKey: 'tramites' },
                { key: 'consentimientos', label: 'Historial de consentimientos', icon: 'yes-alt', countKey: 'consentimientos' }
            ];

            let html = '<div class="flavor-categories-grid">';

            categories.forEach(function(cat) {
                const count = cat.countKey ? (data[cat.countKey] || 0) : '';
                const countDisplay = count !== '' ? `<span class="count">${count}</span>` : '';

                html += `
                    <div class="flavor-category-item">
                        <span class="dashicons dashicons-${cat.icon}"></span>
                        <div class="flavor-category-info">
                            <strong>${cat.label}</strong>
                            ${countDisplay}
                        </div>
                    </div>
                `;
            });

            html += '</div>';

            // Add styles for the grid
            html += `
                <style>
                    .flavor-categories-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                        gap: 10px;
                    }
                    .flavor-category-item {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 12px 15px;
                        background: var(--flavor-gray-100);
                        border-radius: 6px;
                    }
                    .flavor-category-item .dashicons {
                        color: var(--flavor-primary);
                        font-size: 20px;
                        width: 20px;
                        height: 20px;
                    }
                    .flavor-category-info {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        flex: 1;
                    }
                    .flavor-category-info .count {
                        background: var(--flavor-primary);
                        color: #fff;
                        padding: 2px 8px;
                        border-radius: 10px;
                        font-size: 12px;
                    }
                </style>
            `;

            container.html(html);
        },

        handleExportRequest: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            const originalText = button.html();

            if (!confirm(flavorPrivacy.strings.confirmExport)) {
                return;
            }

            button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + flavorPrivacy.strings.processing);

            $.ajax({
                url: flavorPrivacy.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_privacy_export',
                    nonce: flavorPrivacy.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorPrivacy.showNotification('success', response.data.message);
                    } else {
                        FlavorPrivacy.showNotification('error', response.data.message || flavorPrivacy.strings.error);
                    }
                },
                error: function() {
                    FlavorPrivacy.showNotification('error', flavorPrivacy.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        showDeleteForm: function(e) {
            e.preventDefault();
            $('#delete-form').slideDown();
            $('#btn-show-delete-form').hide();
        },

        hideDeleteForm: function(e) {
            e.preventDefault();
            $('#delete-form').slideUp();
            $('#btn-show-delete-form').show();
            $('#confirm-delete').prop('checked', false);
            $('#btn-confirm-delete').prop('disabled', true);
            $('#delete-reason').val('');
        },

        toggleDeleteButton: function() {
            $('#btn-confirm-delete').prop('disabled', !this.checked);
        },

        handleDeleteRequest: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            const originalText = button.html();
            const motivo = $('#delete-reason').val();

            if (!confirm(flavorPrivacy.strings.confirmDelete)) {
                return;
            }

            button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + flavorPrivacy.strings.processing);

            $.ajax({
                url: flavorPrivacy.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_privacy_delete',
                    nonce: flavorPrivacy.nonce,
                    motivo: motivo
                },
                success: function(response) {
                    if (response.success) {
                        FlavorPrivacy.showNotification('success', response.data.message);
                        FlavorPrivacy.hideDeleteForm({ preventDefault: function() {} });
                    } else {
                        FlavorPrivacy.showNotification('error', response.data.message || flavorPrivacy.strings.error);
                    }
                },
                error: function() {
                    FlavorPrivacy.showNotification('error', flavorPrivacy.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        handleConsentChange: function(e) {
            const checkbox = $(e.currentTarget);
            const tipo = checkbox.data('tipo');
            const consentido = checkbox.is(':checked');

            // Mark as pending change
            checkbox.closest('.flavor-consent-item').addClass('pending-change');
        },

        saveConsents: function(e) {
            e.preventDefault();
            const button = $(e.currentTarget);
            const originalText = button.html();

            const consentimientos = {};
            $('.flavor-consent-item.pending-change input[type="checkbox"]').each(function() {
                const tipo = $(this).data('tipo');
                consentimientos[tipo] = $(this).is(':checked');
            });

            if (Object.keys(consentimientos).length === 0) {
                FlavorPrivacy.showNotification('info', 'No hay cambios para guardar');
                return;
            }

            button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Guardando...');

            $.ajax({
                url: flavorPrivacy.restUrl + 'consentimientos',
                type: 'POST',
                headers: {
                    'X-WP-Nonce': flavorPrivacy.restNonce
                },
                contentType: 'application/json',
                data: JSON.stringify({ consentimientos: consentimientos }),
                success: function(response) {
                    if (response.success) {
                        FlavorPrivacy.showNotification('success', 'Consentimientos actualizados');
                        $('.flavor-consent-item').removeClass('pending-change');
                        // Reload to get updated dates
                        location.reload();
                    } else {
                        FlavorPrivacy.showNotification('error', response.message || flavorPrivacy.strings.error);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    FlavorPrivacy.showNotification('error', response?.message || flavorPrivacy.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        },

        showModal: function(title, message, onConfirm) {
            $('#modal-title').text(title);
            $('#modal-message').text(message);
            $('#confirm-modal').show();

            $('#modal-confirm').off('click').on('click', function() {
                FlavorPrivacy.closeModal();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        },

        closeModal: function() {
            $('#confirm-modal').hide();
        },

        showNotification: function(type, message) {
            // Remove existing notifications
            $('.flavor-notification').remove();

            const iconMap = {
                success: 'yes-alt',
                error: 'warning',
                info: 'info'
            };

            const notification = $(`
                <div class="flavor-notification flavor-notification-${type}">
                    <span class="dashicons dashicons-${iconMap[type]}"></span>
                    <span class="message">${message}</span>
                    <button type="button" class="close">&times;</button>
                </div>
            `);

            // Add styles if not already present
            if (!$('#flavor-notification-styles').length) {
                $('head').append(`
                    <style id="flavor-notification-styles">
                        .flavor-notification {
                            position: fixed;
                            top: 100px;
                            right: 20px;
                            z-index: 100001;
                            padding: 15px 20px;
                            border-radius: 6px;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                            animation: slideIn 0.3s ease;
                            max-width: 400px;
                        }
                        .flavor-notification-success {
                            background: #d4edda;
                            border: 1px solid #c3e6cb;
                            color: #155724;
                        }
                        .flavor-notification-error {
                            background: #f8d7da;
                            border: 1px solid #f5c6cb;
                            color: #721c24;
                        }
                        .flavor-notification-info {
                            background: #cce5ff;
                            border: 1px solid #b8daff;
                            color: #004085;
                        }
                        .flavor-notification .close {
                            background: none;
                            border: none;
                            font-size: 20px;
                            cursor: pointer;
                            opacity: 0.5;
                            padding: 0;
                            margin-left: auto;
                        }
                        .flavor-notification .close:hover {
                            opacity: 1;
                        }
                        @keyframes slideIn {
                            from {
                                transform: translateX(100%);
                                opacity: 0;
                            }
                            to {
                                transform: translateX(0);
                                opacity: 1;
                            }
                        }
                    </style>
                `);
            }

            $('body').append(notification);

            // Auto close after 5 seconds
            setTimeout(function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Close on click
            notification.find('.close').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.flavor-privacy-panel').length) {
            FlavorPrivacy.init();
        }
    });

})(jQuery);
