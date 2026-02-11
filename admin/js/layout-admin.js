/**
 * Layout Admin JavaScript
 * Gestiona la interactividad del panel de layouts
 */

(function($) {
    'use strict';

    const FlavorLayoutAdmin = {
        /**
         * Inicialización
         */
        init() {
            this.bindEvents();
            this.initColorPickers();
        },

        /**
         * Vincular eventos
         */
        bindEvents() {
            // Tabs
            $(document).on('click', '.flavor-tab-btn', this.handleTabChange.bind(this));

            // Seleccionar layout
            $(document).on('click', '.flavor-select-layout', this.handleLayoutSelect.bind(this));

            // Aplicar preset
            $(document).on('click', '.flavor-apply-preset', this.handlePresetApply.bind(this));

            // Preview layout
            $(document).on('click', '.flavor-preview-layout', this.handlePreviewOpen.bind(this));

            // Modal
            $(document).on('click', '.flavor-modal__close, .flavor-modal__backdrop', this.handleModalClose.bind(this));

            // Device preview
            $(document).on('click', '.flavor-preview-device', this.handleDeviceChange.bind(this));

            // Guardar settings
            $(document).on('submit', '#flavor-layout-settings-form', this.handleSettingsSave.bind(this));

            // Guardar componentes
            $(document).on('submit', '#flavor-components-settings-form', this.handleSettingsSave.bind(this));

            // Exportar para móvil
            $(document).on('click', '#flavor-export-mobile', this.handleMobileExport.bind(this));

            // Sponsors footer
            $(document).on('change', '.flavor-footer-sponsors-select', this.handleFooterSponsorsChange.bind(this));
            $(document).on('click', '.flavor-add-sponsor', this.handleAddSponsor.bind(this));
            $(document).on('click', '.flavor-remove-sponsor', this.handleRemoveSponsor.bind(this));

            // Menu settings
            $(document).on('change', '.flavor-menu-settings-select', this.handleMenuSettingsChange.bind(this));

            // Keyboard events
            $(document).on('keydown', this.handleKeyboard.bind(this));
        },

        /**
         * Inicializar color pickers
         */
        initColorPickers() {
            if ($.fn.wpColorPicker) {
                $('.flavor-color-picker').wpColorPicker();
            }
        },

        /**
         * Cambiar tab
         */
        handleTabChange(event) {
            const $button = $(event.currentTarget);
            const tabId = $button.data('tab');

            // Actualizar botones
            $('.flavor-tab-btn').removeClass('active');
            $button.addClass('active');

            // Actualizar contenido
            $('.flavor-tab-content').removeClass('active');
            $(`.flavor-tab-content[data-tab="${tabId}"]`).addClass('active');
        },

        /**
         * Seleccionar layout
         */
        handleLayoutSelect(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const $card = $button.closest('.flavor-layout-card');
            const layoutType = $card.data('type');
            const layoutId = $card.data('id');

            // Si ya está seleccionado, no hacer nada
            if ($card.hasClass('selected')) {
                return;
            }

            // Mostrar loading
            $button.prop('disabled', true).text(flavorLayoutAdmin.strings.saving);

            // Guardar via AJAX
            $.ajax({
                url: flavorLayoutAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_save_layout',
                    nonce: flavorLayoutAdmin.nonce,
                    type: layoutType,
                    id: layoutId
                },
                success: (response) => {
                    if (response.success) {
                        // Actualizar UI
                        $(`.flavor-layout-card[data-type="${layoutType}"]`).removeClass('selected');
                        $(`.flavor-layout-card[data-type="${layoutType}"] .flavor-select-layout`)
                            .removeClass('button-primary')
                            .text(flavorLayoutAdmin.strings.select || 'Seleccionar');

                        $card.addClass('selected');
                        $button.addClass('button-primary').text(flavorLayoutAdmin.strings.selected || 'Seleccionado');

                        // Actualizar presets si corresponde
                        this.updatePresetStates(response.data.active_menu, response.data.active_footer);

                        this.showToast(flavorLayoutAdmin.strings.saved, 'success');
                    } else {
                        this.showToast(response.data.message || flavorLayoutAdmin.strings.error, 'error');
                        $button.prop('disabled', false).text(flavorLayoutAdmin.strings.select || 'Seleccionar');
                    }
                },
                error: () => {
                    this.showToast(flavorLayoutAdmin.strings.error, 'error');
                    $button.prop('disabled', false).text(flavorLayoutAdmin.strings.select || 'Seleccionar');
                }
            });
        },

        /**
         * Aplicar preset
         */
        handlePresetApply(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const menuId = $button.data('menu');
            const footerId = $button.data('footer');

            $button.prop('disabled', true).text(flavorLayoutAdmin.strings.saving);

            // Guardar menú
            $.ajax({
                url: flavorLayoutAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_save_layout',
                    nonce: flavorLayoutAdmin.nonce,
                    type: 'menu',
                    id: menuId
                },
                success: () => {
                    // Guardar footer
                    $.ajax({
                        url: flavorLayoutAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'flavor_save_layout',
                            nonce: flavorLayoutAdmin.nonce,
                            type: 'footer',
                            id: footerId
                        },
                        success: (response) => {
                            if (response.success) {
                                // Actualizar todas las UIs
                                this.updateAllSelections(menuId, footerId);
                                this.showToast(flavorLayoutAdmin.strings.saved, 'success');
                            }
                            $button.prop('disabled', false).text('Aplicar Preset');
                        },
                        error: () => {
                            this.showToast(flavorLayoutAdmin.strings.error, 'error');
                            $button.prop('disabled', false).text('Aplicar Preset');
                        }
                    });
                },
                error: () => {
                    this.showToast(flavorLayoutAdmin.strings.error, 'error');
                    $button.prop('disabled', false).text('Aplicar Preset');
                }
            });
        },

        /**
         * Actualizar todas las selecciones en la UI
         */
        updateAllSelections(menuId, footerId) {
            // Actualizar cards de menú
            $('.flavor-layout-card[data-type="menu"]').removeClass('selected');
            $('.flavor-layout-card[data-type="menu"] .flavor-select-layout')
                .removeClass('button-primary')
                .text('Seleccionar');

            $(`.flavor-layout-card[data-type="menu"][data-id="${menuId}"]`).addClass('selected');
            $(`.flavor-layout-card[data-type="menu"][data-id="${menuId}"] .flavor-select-layout`)
                .addClass('button-primary')
                .text('Seleccionado');

            // Actualizar cards de footer
            $('.flavor-layout-card[data-type="footer"]').removeClass('selected');
            $('.flavor-layout-card[data-type="footer"] .flavor-select-layout')
                .removeClass('button-primary')
                .text('Seleccionar');

            $(`.flavor-layout-card[data-type="footer"][data-id="${footerId}"]`).addClass('selected');
            $(`.flavor-layout-card[data-type="footer"][data-id="${footerId}"] .flavor-select-layout`)
                .addClass('button-primary')
                .text('Seleccionado');

            // Actualizar presets
            this.updatePresetStates(menuId, footerId);
        },

        /**
         * Actualizar estados de presets
         */
        updatePresetStates(activeMenu, activeFooter) {
            $('.flavor-preset-card').each(function() {
                const $card = $(this);
                const $btn = $card.find('.flavor-apply-preset');
                const presetMenu = $btn.data('menu');
                const presetFooter = $btn.data('footer');

                if (presetMenu === activeMenu && presetFooter === activeFooter) {
                    $card.addClass('active');
                    if (!$card.find('.flavor-preset-card__badge').length) {
                        $card.find('.flavor-preset-card__header').append(
                            '<span class="flavor-preset-card__badge">Activo</span>'
                        );
                    }
                } else {
                    $card.removeClass('active');
                    $card.find('.flavor-preset-card__badge').remove();
                }
            });
        },

        /**
         * Abrir preview
         */
        handlePreviewOpen(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const previewType = $button.data('preview');
            const previewId = $button.data('id');

            // Obtener URL de preview
            $.ajax({
                url: flavorLayoutAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_layout_preview',
                    nonce: flavorLayoutAdmin.nonce,
                    type: previewType,
                    id: previewId
                },
                success: (response) => {
                    if (response.success) {
                        this.openPreviewModal(response.data.preview_url);
                    }
                }
            });
        },

        /**
         * Abrir modal de preview
         */
        openPreviewModal(url) {
            const $modal = $('#flavor-preview-modal');
            const $iframe = $('#flavor-preview-iframe');

            $iframe.attr('src', url);
            $modal.show();
            $('body').addClass('flavor-modal-open');
        },

        /**
         * Cerrar modal
         */
        handleModalClose() {
            const $modal = $('#flavor-preview-modal');
            const $iframe = $('#flavor-preview-iframe');

            $modal.hide();
            $iframe.attr('src', 'about:blank');
            $('body').removeClass('flavor-modal-open');
        },

        /**
         * Cambiar dispositivo de preview
         */
        handleDeviceChange(event) {
            const $button = $(event.currentTarget);
            const device = $button.data('device');

            $('.flavor-preview-device').removeClass('active');
            $button.addClass('active');

            $('.flavor-preview-container').attr('data-device', device);
        },

        /**
         * Guardar settings
         */
        handleSettingsSave(event) {
            event.preventDefault();
            const $form = $(event.currentTarget);
            const $submitBtn = $form.find('button[type="submit"]');

            $submitBtn.prop('disabled', true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<span class="dashicons dashicons-update spin"></span> ' + flavorLayoutAdmin.strings.saving);

            $.ajax({
                url: flavorLayoutAdmin.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_save_layout_settings&nonce=' + flavorLayoutAdmin.nonce,
                success: (response) => {
                    if (response.success) {
                        this.showToast(flavorLayoutAdmin.strings.saved, 'success');
                    } else {
                        this.showToast(response.data.message || flavorLayoutAdmin.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(flavorLayoutAdmin.strings.error, 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Cambiar footer a configurar en sponsors
         */
        handleFooterSponsorsChange(event) {
            const footerId = $(event.currentTarget).val();
            $('.flavor-footer-sponsors').hide();
            $(`.flavor-footer-sponsors[data-footer="${footerId}"]`).show();
        },

        /**
         * Cambiar menú a configurar
         */
        handleMenuSettingsChange(event) {
            const menuId = $(event.currentTarget).val();
            $('.flavor-menu-settings').hide();
            $(`.flavor-menu-settings[data-menu="${menuId}"]`).show();
        },

        /**
         * Añadir sponsor
         */
        handleAddSponsor(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const footerId = $button.data('footer');
            const $container = $(`.flavor-footer-sponsors[data-footer="${footerId}"]`);
            const $list = $container.find('.flavor-sponsors-list');
            let index = parseInt($list.attr('data-next-index'), 10);
            if (Number.isNaN(index)) {
                index = 0;
            }

            const row = `
                <div class="flavor-sponsor-row">
                    <input type="text" name="footer_sponsors[${footerId}][${index}][name]" placeholder="${flavorLayoutAdmin.strings.sponsor_name || 'Nombre'}">
                    <input type="url" name="footer_sponsors[${footerId}][${index}][url]" placeholder="https://">
                    <input type="url" name="footer_sponsors[${footerId}][${index}][logo]" placeholder="${flavorLayoutAdmin.strings.sponsor_logo || 'URL del logo'}">
                    <button type="button" class="button flavor-remove-sponsor">${flavorLayoutAdmin.strings.remove || 'Eliminar'}</button>
                </div>
            `;

            $list.append(row);
            $list.attr('data-next-index', index + 1);
        },

        /**
         * Eliminar sponsor
         */
        handleRemoveSponsor(event) {
            event.preventDefault();
            $(event.currentTarget).closest('.flavor-sponsor-row').remove();
        },

        /**
         * Exportar para móvil
         */
        handleMobileExport(event) {
            event.preventDefault();

            if (!confirm(flavorLayoutAdmin.strings.confirm_export)) {
                return;
            }

            const $button = $(event.currentTarget);
            $button.prop('disabled', true);

            $.ajax({
                url: flavorLayoutAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_export_mobile_config',
                    nonce: flavorLayoutAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast(flavorLayoutAdmin.strings.exported + ': ' + response.data.filename, 'success');

                        // Ofrecer descarga
                        const downloadLink = document.createElement('a');
                        downloadLink.href = response.data.url;
                        downloadLink.download = response.data.filename;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else {
                        this.showToast(response.data.message || flavorLayoutAdmin.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(flavorLayoutAdmin.strings.error, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Manejar teclado
         */
        handleKeyboard(event) {
            // Cerrar modal con ESC
            if (event.key === 'Escape') {
                this.handleModalClose();
            }
        },

        /**
         * Mostrar toast notification
         */
        showToast(message, type = 'info') {
            const $container = $('#flavor-toast-container');
            const $toast = $(`
                <div class="flavor-toast flavor-toast--${type}">
                    <span class="dashicons dashicons-${type === 'success' ? 'yes-alt' : type === 'error' ? 'warning' : 'info'}"></span>
                    ${message}
                </div>
            `);

            $container.append($toast);

            // Auto-remove after 4 seconds
            setTimeout(() => {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        FlavorLayoutAdmin.init();
    });

    // Exponer para uso externo
    window.FlavorLayoutAdmin = FlavorLayoutAdmin;

})(jQuery);
