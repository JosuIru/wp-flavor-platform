/**
 * JavaScript para la configuración del restaurante
 */

(function($) {
    'use strict';

    const strings = (typeof flavorRestaurantSettings !== 'undefined' && flavorRestaurantSettings.strings) ? flavorRestaurantSettings.strings : {};

    const RestaurantSettings = {
        init() {
            this.bindEvents();
            this.loadAvailableCPTs();
        },

        bindEvents() {
            // Agregar CPTs seleccionados con botón
            $(document).on('click', '.add-selected-cpts', (e) => {
                this.addSelectedCPTs($(e.currentTarget).closest('.menu-category'));
            });

            // Doble clic en opción individual (atajo rápido)
            $('.cpt-select').on('dblclick', 'option', (e) => {
                this.addCPTToCategory($(e.currentTarget));
            });

            // Eliminar CPT de categoría
            $(document).on('click', '.remove-cpt', (e) => {
                this.removeCPT($(e.currentTarget).closest('.selected-cpt'));
            });

            // Guardar configuración
            $('#save-restaurant-settings').on('click', () => {
                this.saveSettings();
            });

            // Agregar estado de pedido
            $('#add-order-status').on('click', () => {
                this.addOrderStatus();
            });

            // Eliminar estado de pedido
            $(document).on('click', '.remove-status', (e) => {
                this.removeOrderStatus($(e.currentTarget).closest('tr'));
            });

            // Actualizar símbolo de moneda al cambiar moneda
            $('#currency').on('change', (e) => {
                this.updateCurrencySymbol($(e.currentTarget).val());
            });
        },

        loadAvailableCPTs() {
            // Los CPTs ya están cargados en el HTML
        },

        /**
         * Agregar todos los CPTs seleccionados en el <select> de una categoría
         */
        addSelectedCPTs($category) {
            const $select = $category.find('.cpt-select');
            const $opcionesSeleccionadas = $select.find('option:selected');

            if ($opcionesSeleccionadas.length === 0) {
                return;
            }

            $opcionesSeleccionadas.each((index, opcion) => {
                this.addCPTToCategory($(opcion));
            });
        },

        addCPTToCategory($option) {
            const cptSlug = $option.val();
            const cptName = $option.text();
            const $category = $option.closest('.menu-category');
            const categorySlug = $category.data('category');
            const $selectedContainer = $category.find('.selected-cpts');

            // Eliminar mensaje "no hay CPTs"
            $selectedContainer.find('.no-cpts').remove();

            // Crear elemento seleccionado
            const nombreLimpio = cptName.replace(/\s*\(\d+\s+items\)/, '');
            const conteoMatch = cptName.match(/\((\d+ items)\)/);
            const conteoTexto = conteoMatch ? conteoMatch[1] : '';

            const $selectedCPT = $(`
                <div class="selected-cpt" data-cpt="${cptSlug}">
                    <span class="cpt-name">${nombreLimpio}</span>
                    <span class="cpt-count">${conteoTexto}</span>
                    <button type="button" class="remove-cpt" title="Eliminar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <input type="hidden" name="menu_cpts[${categorySlug}][]" value="${cptSlug}">
                </div>
            `);

            $selectedContainer.append($selectedCPT);

            // Eliminar de la lista de disponibles
            $option.remove();

            // Animación
            $selectedCPT.hide().fadeIn(300);
        },

        removeCPT($cptElement) {
            const cptSlug = $cptElement.data('cpt');
            const cptName = $cptElement.find('.cpt-name').text();
            const cptCount = $cptElement.find('.cpt-count').text();
            const $category = $cptElement.closest('.menu-category');
            const $select = $category.find('.cpt-select');

            // Agregar de vuelta a la lista de disponibles
            const optionText = cptCount ? `${cptName} (${cptCount})` : cptName;
            $select.append(`<option value="${cptSlug}">${optionText}</option>`);

            // Ordenar alfabéticamente
            const $options = $select.find('option');
            $options.sort((a, b) => {
                return $(a).text().localeCompare($(b).text());
            });
            $select.empty().append($options);

            // Eliminar elemento
            $cptElement.fadeOut(300, function() {
                $(this).remove();

                // Mostrar mensaje si no hay CPTs
                const $container = $category.find('.selected-cpts');
                if ($container.find('.selected-cpt').length === 0) {
                    $container.html('<p class="no-cpts">No hay CPTs seleccionados para esta categoría.</p>');
                }
            });
        },

        addOrderStatus() {
            const statusId = prompt('ID del estado (sin espacios, en minúsculas):');

            if (!statusId) return;

            const sanitizedId = statusId.toLowerCase().replace(/[^a-z0-9_-]/g, '_');
            const statusLabel = prompt('Etiqueta del estado:');

            if (!statusLabel) return;

            // Verificar que no exista
            if ($(`tr[data-status="${sanitizedId}"]`).length > 0) {
                this.showNotice('error', 'Ya existe un estado con ese ID');
                return;
            }

            const $row = $(`
                <tr data-status="${sanitizedId}">
                    <td><code>${sanitizedId}</code></td>
                    <td>
                        <input type="text"
                               name="order_statuses[${sanitizedId}]"
                               value="${statusLabel}"
                               class="regular-text">
                    </td>
                    <td>
                        <button type="button" class="button button-small remove-status">Eliminar</button>
                    </td>
                </tr>
            `);

            $('#order-statuses-list').append($row);
            $row.hide().fadeIn(300);
        },

        removeOrderStatus($row) {
            if (!confirm('¿Estás seguro de eliminar este estado?')) {
                return;
            }

            $row.fadeOut(300, function() {
                $(this).remove();
            });
        },

        updateCurrencySymbol(currency) {
            const symbols = {
                'EUR': '€',
                'USD': '$',
                'GBP': '£',
                'MXN': '$'
            };

            if (symbols[currency]) {
                $('#currency_symbol').val(symbols[currency]);
            }
        },

        saveSettings() {
            const $button = $('#save-restaurant-settings');
            const $overlay = $('.flavor-loading-overlay');

            // Recopilar datos del formulario
            const formData = {
                action: 'save_restaurant_settings',
                nonce: flavorRestaurantSettings.nonce,
                menu_cpts: {
                    dishes: [],
                    drinks: [],
                    desserts: []
                },
                table_prefix: $('#table_prefix').val(),
                currency: $('#currency').val(),
                currency_symbol: $('#currency_symbol').val(),
                tax_rate: $('#tax_rate').val(),
                enable_table_qr: $('input[name="enable_table_qr"]').is(':checked') ? '1' : '0',
                enable_notifications: $('input[name="enable_notifications"]').is(':checked') ? '1' : '0',
                order_statuses: {}
            };

            // Recopilar CPTs seleccionados
            $('input[name^="menu_cpts"]').each(function() {
                const name = $(this).attr('name');
                const match = name.match(/menu_cpts\[(\w+)\]\[\]/);
                if (match) {
                    const category = match[1];
                    formData.menu_cpts[category].push($(this).val());
                }
            });

            // Recopilar estados de pedido
            $('input[name^="order_statuses"]').each(function() {
                const name = $(this).attr('name');
                const match = name.match(/order_statuses\[(\w+)\]/);
                if (match) {
                    formData.order_statuses[match[1]] = $(this).val();
                }
            });

            // Mostrar loading
            $button.prop('disabled', true);
            $overlay.fadeIn(200);

            // Enviar datos
            $.post(flavorRestaurantSettings.ajax_url, formData)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message || strings.save_success || 'Configuración guardada exitosamente');
                    } else {
                        this.showNotice('error', response.data.message || strings.save_error || 'Error al guardar la configuración');
                    }
                })
                .fail(() => {
                    this.showNotice('error', strings.save_error_connection || 'Error de conexión al guardar');
                })
                .always(() => {
                    $button.prop('disabled', false);
                    $overlay.fadeOut(200);
                });
        },

        showNotice(type, message) {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible" style="display:none;">
                    <p>${message}</p>
                </div>
            `);

            $('.flavor-restaurant-settings h1').after($notice);
            $notice.slideDown(300);

            // Auto-dismiss después de 5 segundos
            setTimeout(() => {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Botón dismiss
            $notice.find('.notice-dismiss').on('click', () => {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        if ($('.flavor-restaurant-settings').length) {
            RestaurantSettings.init();
        }
    });

})(jQuery);
