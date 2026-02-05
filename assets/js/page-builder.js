/**
 * Flavor Page Builder - JavaScript
 */

(function($) {
    'use strict';

    const FlavorPageBuilder = {
        layout: [],
        currentEditingIndex: null,
        components: {},

        init: function() {
            this.components = flavorPageBuilder.components || {};
            this.loadLayout();
            this.bindEvents();
            this.initSortable();
        },

        loadLayout: function() {
            const layoutData = $('#flavor_page_layout').val();
            if (layoutData) {
                try {
                    this.layout = JSON.parse(layoutData);
                } catch (e) {
                    this.layout = [];
                }
            }
        },

        saveLayout: function() {
            $('#flavor_page_layout').val(JSON.stringify(this.layout));
        },

        bindEvents: function() {
            const self = this;

            // Añadir componente
            $('#flavor-pb-add-component').on('click', function() {
                self.openComponentLibrary();
            });

            // Cargar plantilla
            $('#flavor-pb-load-template').on('click', function() {
                self.openTemplateLibrary();
            });

            // Cerrar sidebar
            $('.flavor-pb-close-sidebar').on('click', function() {
                $('#flavor-pb-sidebar').fadeOut();
            });

            // Toggle acordeón de componentes
            $(document).on('click', '.flavor-pb-accordion-header', function() {
                const $header = $(this);
                const $content = $header.next('.flavor-pb-accordion-content');
                const $icon = $header.find('.flavor-pb-accordion-icon');

                // Si ya está activo, cerrarlo
                if ($header.hasClass('active')) {
                    $header.removeClass('active');
                    $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                    $content.slideUp(300);
                } else {
                    // Cerrar todos los demás
                    $('.flavor-pb-accordion-header').removeClass('active');
                    $('.flavor-pb-accordion-icon').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                    $('.flavor-pb-accordion-content').slideUp(300);

                    // Abrir el clickeado
                    $header.addClass('active');
                    $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
                    $content.slideDown(300);
                }
            });

            // Seleccionar componente de la librería
            $(document).on('click', '.flavor-pb-component-card', function() {
                const componentId = $(this).data('component-id');
                self.addComponent(componentId);
                $('#flavor-pb-sidebar').fadeOut();
            });

            // Editar componente
            $(document).on('click', '.flavor-pb-edit-component', function() {
                const index = $(this).closest('.flavor-pb-component-item').data('index');
                self.editComponent(index);
            });

            // Duplicar componente
            $(document).on('click', '.flavor-pb-duplicate-component', function() {
                const index = $(this).closest('.flavor-pb-component-item').data('index');
                self.duplicateComponent(index);
            });

            // Preview componente (toggle vista frontend)
            $(document).on('click', '.flavor-pb-preview-component', function() {
                const $item = $(this).closest('.flavor-pb-component-item');
                const index = $item.data('index');
                const $btn = $(this);
                const $preview = $item.find('.flavor-pb-component-preview');
                const $livePreview = $item.find('.flavor-pb-live-preview');

                // Si ya hay un live preview visible, ocultarlo y mostrar placeholder
                if ($livePreview.length && $livePreview.is(':visible')) {
                    $livePreview.slideUp(300);
                    $preview.slideDown(300);
                    $btn.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $btn.attr('title', flavorPageBuilder.i18n.preview || 'Preview');
                    return;
                }

                // Si ya existe el contenedor pero está oculto, mostrarlo
                if ($livePreview.length) {
                    $preview.slideUp(300);
                    $livePreview.slideDown(300);
                    $btn.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $btn.attr('title', flavorPageBuilder.i18n.hidePreview || 'Ocultar preview');
                    return;
                }

                // Primera vez: hacer AJAX para obtener el HTML renderizado
                $btn.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-update spin');

                const componentLayout = self.layout[index];
                if (!componentLayout) return;

                $.ajax({
                    url: flavorPageBuilder.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flavor_preview_component',
                        nonce: flavorPageBuilder.nonce,
                        component_id: componentLayout.component_id,
                        component_data: JSON.stringify(componentLayout.data || {}),
                        post_id: flavorPageBuilder.postId || 0
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            $preview.slideUp(300);
                            var $container = $('<div class="flavor-pb-live-preview"></div>');
                            $container.html(response.data.html);
                            $item.append($container);
                            $container.hide().slideDown(300);
                            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-hidden');
                            $btn.attr('title', flavorPageBuilder.i18n.hidePreview || 'Ocultar preview');
                        } else {
                            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-visibility');
                            var $errMsg = $('<div class="flavor-pb-live-preview" style="padding:15px;color:#856404;background:#fff3cd;border-top:2px solid #ffc107;"><span class="dashicons dashicons-warning" style="margin-right:5px;"></span>No se pudo generar el preview</div>');
                            $item.append($errMsg);
                            setTimeout(function(){ $errMsg.slideUp(300, function(){ $(this).remove(); }); }, 3000);
                        }
                    },
                    error: function() {
                        $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-visibility');
                        var $errMsg = $('<div class="flavor-pb-live-preview" style="padding:15px;color:#721c24;background:#f8d7da;border-top:2px solid #dc3545;"><span class="dashicons dashicons-warning" style="margin-right:5px;"></span>Error de conexión</div>');
                        $item.append($errMsg);
                        setTimeout(function(){ $errMsg.slideUp(300, function(){ $(this).remove(); }); }, 3000);
                    }
                });
            });

            // Eliminar componente
            $(document).on('click', '.flavor-pb-delete-component', function() {
                if (confirm(flavorPageBuilder.i18n.confirmDelete)) {
                    const index = $(this).closest('.flavor-pb-component-item').data('index');
                    self.deleteComponent(index);
                }
            });

            // Mover arriba
            $(document).on('click', '.flavor-pb-move-up', function() {
                const index = $(this).closest('.flavor-pb-component-item').data('index');
                self.moveComponent(index, 'up');
            });

            // Mover abajo
            $(document).on('click', '.flavor-pb-move-down', function() {
                const index = $(this).closest('.flavor-pb-component-item').data('index');
                self.moveComponent(index, 'down');
            });

            // Repeater: toggle item expand/collapse
            $(document).on('click', '.flavor-pb-repeater-toggle', function(e) {
                e.preventDefault();
                const $item = $(this).closest('.flavor-pb-repeater-item');
                const $body = $item.find('> .flavor-pb-repeater-item-body');
                const $icon = $(this).find('.dashicons');

                $body.slideToggle(200);
                $icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
            });

            // Repeater: add item
            $(document).on('click', '.flavor-pb-add-repeater-item', function(e) {
                e.preventDefault();
                const fieldName = $(this).data('field-name');
                self.addRepeaterItem(fieldName);
            });

            // Repeater: remove item
            $(document).on('click', '.flavor-pb-repeater-remove', function(e) {
                e.preventDefault();
                if (!confirm('¿Eliminar este item?')) return;
                const fieldName = $(this).data('field-name');
                const $item = $(this).closest('.flavor-pb-repeater-item');
                $item.slideUp(200, function() {
                    $(this).remove();
                    // Re-index remaining items
                    const $repeater = $('.flavor-pb-repeater[data-field-name="' + fieldName + '"]');
                    $repeater.find('.flavor-pb-repeater-item').each(function(idx) {
                        $(this).attr('data-index', idx);
                    });
                });
            });

            // Repeater: click on header to toggle (but not on action buttons)
            $(document).on('click', '.flavor-pb-repeater-item-header', function(e) {
                if ($(e.target).closest('.flavor-pb-repeater-item-actions').length) return;
                $(this).find('.flavor-pb-repeater-toggle').trigger('click');
            });

            // Data source: toggle between manual/dynamic
            $(document).on('change', '.flavor-pb-data-source-select', function() {
                const source = $(this).val();
                const $container = $(this).closest('.flavor-pb-data-source-container');
                const itemsField = $container.data('items-field');
                const $filters = $container.find('.flavor-pb-data-source-filters');

                if (source === 'manual') {
                    $filters.slideUp(200);
                } else {
                    $filters.slideDown(200);
                }

                // Show/hide associated repeater
                const $repeaterField = $('.flavor-pb-repeater[data-field-name="' + itemsField + '"]').closest('.flavor-pb-field');
                if (source === 'manual') {
                    $repeaterField.slideDown(200);
                } else {
                    $repeaterField.slideUp(200);
                }
            });

            // Guardar edición de componente
            $('#flavor-pb-save-component').on('click', function() {
                self.saveComponentEdit();
            });

            // Cancelar edición
            $('#flavor-pb-cancel-edit, .flavor-pb-close-modal').on('click', function() {
                $('#flavor-pb-edit-modal').fadeOut();
                $('#flavor-pb-templates-modal').fadeOut();
            });

            // Usar plantilla
            $(document).on('click', '.flavor-pb-use-template', function() {
                const templateId = $(this).closest('.flavor-pb-template-card').data('template-id');
                self.loadTemplate(templateId);
            });

            // Vista previa
            $('#flavor-pb-preview').on('click', function() {
                self.openPreview();
            });
        },

        initSortable: function() {
            const self = this;

            $('#flavor-pb-components-list').sortable({
                handle: '.flavor-pb-component-header',
                placeholder: 'flavor-pb-sortable-placeholder',
                update: function(event, ui) {
                    self.updateLayoutOrder();
                }
            });
        },

        updateLayoutOrder: function() {
            const self = this;
            const nuevaDisposicion = [];

            $('#flavor-pb-components-list .flavor-pb-component-item').each(function(index) {
                const oldIndex = $(this).data('index');
                nuevaDisposicion.push(self.layout[oldIndex]);
                $(this).data('index', index);
            });

            this.layout = nuevaDisposicion;
            this.saveLayout();
        },

        openComponentLibrary: function() {
            $('#flavor-pb-sidebar').fadeIn();
        },

        addComponent: function(componentId) {
            const component = this.components[componentId];
            if (!component) return;

            const componentData = {
                component_id: componentId,
                data: this.getDefaultFieldValues(component.fields),
                settings: {
                    align: '',
                    spacing: {
                        margin: {top: 0, bottom: 0},
                        padding: {top: 0, bottom: 0}
                    },
                    background: {color: '', image: ''}
                }
            };

            this.layout.push(componentData);
            this.saveLayout();
            this.renderComponent(this.layout.length - 1, componentData);
            this.removeEmptyState();
        },

        getDefaultFieldValues: function(fields) {
            const valoresPorDefecto = {};
            for (const fieldName in fields) {
                const fieldDefault = fields[fieldName].default;
                if (fieldDefault !== undefined) {
                    // Deep clone arrays/objects to avoid shared references
                    if (Array.isArray(fieldDefault)) {
                        valoresPorDefecto[fieldName] = JSON.parse(JSON.stringify(fieldDefault));
                    } else if (typeof fieldDefault === 'object' && fieldDefault !== null) {
                        valoresPorDefecto[fieldName] = JSON.parse(JSON.stringify(fieldDefault));
                    } else {
                        valoresPorDefecto[fieldName] = fieldDefault;
                    }
                } else {
                    valoresPorDefecto[fieldName] = '';
                }
            }
            return valoresPorDefecto;
        },

        renderComponent: function(index, componentData) {
            const component = this.components[componentData.component_id];
            if (!component) return;

            const html = `
                <div class="flavor-pb-component-item" data-index="${index}">
                    <div class="flavor-pb-component-header">
                        <div class="flavor-pb-component-info">
                            <span class="dashicons ${component.icon}"></span>
                            <span class="flavor-pb-component-label">${component.label}</span>
                        </div>
                        <div class="flavor-pb-component-actions">
                            <button type="button" class="flavor-pb-edit-component" title="${flavorPageBuilder.i18n.edit || 'Editar'}">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="flavor-pb-duplicate-component" title="${flavorPageBuilder.i18n.duplicate || 'Duplicar'}">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button type="button" class="flavor-pb-move-up" title="${flavorPageBuilder.i18n.moveUp || 'Subir'}">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                            <button type="button" class="flavor-pb-move-down" title="${flavorPageBuilder.i18n.moveDown || 'Bajar'}">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <button type="button" class="flavor-pb-delete-component" title="${flavorPageBuilder.i18n.delete || 'Eliminar'}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="flavor-pb-component-preview">
                        <div class="flavor-pb-component-preview-placeholder">
                            <span class="dashicons ${component.icon}"></span>
                        </div>
                    </div>
                </div>
            `;

            $('#flavor-pb-components-list').append(html);
        },

        removeEmptyState: function() {
            $('.flavor-pb-empty-state').remove();
        },

        editComponent: function(index) {
            this.currentEditingIndex = index;
            const componentData = this.layout[index];
            const component = this.components[componentData.component_id];

            if (!component) return;

            // Construir formulario de edición
            let formHtml = '';

            for (const fieldName in component.fields) {
                const field = component.fields[fieldName];
                let value = componentData.data[fieldName];
                if (value === undefined || value === null) {
                    value = field.default !== undefined ? field.default : '';
                }

                formHtml += this.renderField(fieldName, field, value);
            }

            $('#flavor-pb-edit-modal-body').html(formHtml);
            $('#flavor-pb-edit-modal').fadeIn();

            // Inicializar color picker
            $('.flavor-pb-color-picker').wpColorPicker();

            // Inicializar media uploader
            this.initMediaUploader();

            // Resolver previews de imágenes con attachment IDs
            this.resolveImagePreviews();

            // Manejar visibilidad inicial de data_source/repeater
            $('#flavor-pb-edit-modal-body .flavor-pb-data-source-select').each(function() {
                const source = $(this).val();
                const $container = $(this).closest('.flavor-pb-data-source-container');
                const itemsField = $container.data('items-field');
                const $filters = $container.find('.flavor-pb-data-source-filters');

                if (source === 'manual') {
                    $filters.hide();
                } else {
                    $filters.show();
                    // Hide the associated repeater
                    var $repeaterField = $('.flavor-pb-repeater[data-field-name="' + itemsField + '"]').closest('.flavor-pb-field');
                    $repeaterField.hide();
                }
            });
        },

        renderField: function(name, field, value) {
            let html = '<div class="flavor-pb-field">';
            html += '<label class="flavor-pb-field-label">' + (field.label || name) + '</label>';

            switch (field.type) {
                case 'text':
                    html += '<input type="text" name="' + name + '" value="' + this.escapeHtml(value) + '" class="widefat">';
                    break;

                case 'textarea':
                    html += '<textarea name="' + name + '" rows="4" class="widefat">' + this.escapeHtml(value) + '</textarea>';
                    break;

                case 'number':
                    html += '<input type="number" name="' + name + '" value="' + value + '" class="widefat">';
                    break;

                case 'url':
                    html += '<input type="url" name="' + name + '" value="' + this.escapeHtml(value) + '" class="widefat">';
                    break;

                case 'toggle':
                case 'checkbox':
                    html += '<label class="flavor-pb-toggle">';
                    html += '<input type="checkbox" name="' + name + '" value="1" ' + (value ? 'checked' : '') + '>';
                    html += '<span class="flavor-pb-toggle-slider"></span>';
                    html += '</label>';
                    break;

                case 'select':
                    html += '<select name="' + name + '" class="widefat">';
                    for (const option of field.options) {
                        html += '<option value="' + option + '" ' + (value == option ? 'selected' : '') + '>' + option + '</option>';
                    }
                    html += '</select>';
                    break;

                case 'color':
                    html += '<input type="text" name="' + name + '" value="' + value + '" class="flavor-pb-color-picker">';
                    break;

                case 'image':
                    var hasImage = !!value;
                    var isNumericId = hasImage && !isNaN(value) && String(parseInt(value)) === String(value);
                    html += '<div class="flavor-pb-image-field">';
                    html += '<input type="hidden" name="' + name + '" value="' + value + '" class="flavor-pb-image-id">';
                    html += '<div class="flavor-pb-image-preview">';
                    if (hasImage && !isNumericId) {
                        html += '<img src="' + value + '" alt="">';
                    } else if (hasImage && isNumericId) {
                        html += '<span class="dashicons dashicons-format-image flavor-pb-image-loading" data-attachment-id="' + value + '"></span>';
                    } else {
                        html += '<span class="dashicons dashicons-format-image"></span>';
                    }
                    html += '</div>';
                    html += '<button type="button" class="button flavor-pb-upload-image">Seleccionar Imagen</button>';
                    html += '<button type="button" class="button flavor-pb-remove-image" ' + (!hasImage ? 'style="display:none"' : '') + '>Eliminar</button>';
                    html += '</div>';
                    break;

                case 'repeater':
                    var repeaterItems = Array.isArray(value) ? value : [];
                    html += '<div class="flavor-pb-repeater" data-field-name="' + name + '" data-max-items="' + (field.max_items || 10) + '">';
                    html += '<div class="flavor-pb-repeater-items">';
                    for (var repeaterIdx = 0; repeaterIdx < repeaterItems.length; repeaterIdx++) {
                        html += this.renderRepeaterItem(name, field.fields, repeaterItems[repeaterIdx], repeaterIdx);
                    }
                    html += '</div>';
                    html += '<button type="button" class="button flavor-pb-add-repeater-item" data-field-name="' + name + '">';
                    html += '<span class="dashicons dashicons-plus-alt2"></span> Añadir item';
                    html += '</button>';
                    html += '</div>';
                    break;

                case 'data_source':
                    var sourceConfig = (typeof value === 'object' && value !== null) ? value : { source: value || field.default || 'manual' };
                    var currentSource = sourceConfig.source || 'manual';
                    var currentLimite = sourceConfig.limite || 6;
                    var currentOrden = sourceConfig.orden || 'date_desc';
                    var postTypeLabels = {
                        'post': 'Posts del blog',
                        'page': 'Páginas',
                        'product': 'Productos (WooCommerce)',
                        'flavor_evento': 'Eventos'
                    };

                    html += '<div class="flavor-pb-data-source-container" data-field-name="' + name + '" data-items-field="' + (field.items_field || 'items') + '">';
                    html += '<select name="' + name + '" class="widefat flavor-pb-data-source-select">';
                    html += '<option value="manual" ' + (currentSource === 'manual' ? 'selected' : '') + '>Items manuales</option>';
                    if (field.post_types && field.post_types.length > 0) {
                        for (var dsIdx = 0; dsIdx < field.post_types.length; dsIdx++) {
                            var postType = field.post_types[dsIdx];
                            var postTypeLabel = postTypeLabels[postType] || postType;
                            html += '<option value="' + postType + '" ' + (currentSource === postType ? 'selected' : '') + '>' + postTypeLabel + '</option>';
                        }
                    }
                    html += '</select>';

                    html += '<div class="flavor-pb-data-source-filters" style="' + (currentSource === 'manual' ? 'display:none;' : '') + 'margin-top:12px;">';
                    html += '<div class="flavor-pb-field">';
                    html += '<label class="flavor-pb-field-label">Límite de resultados</label>';
                    html += '<input type="number" name="ds_limite" value="' + currentLimite + '" class="widefat" min="1" max="20">';
                    html += '</div>';
                    html += '<div class="flavor-pb-field">';
                    html += '<label class="flavor-pb-field-label">Orden</label>';
                    html += '<select name="ds_orden" class="widefat">';
                    html += '<option value="date_desc" ' + (currentOrden === 'date_desc' ? 'selected' : '') + '>Más recientes</option>';
                    html += '<option value="date_asc" ' + (currentOrden === 'date_asc' ? 'selected' : '') + '>Más antiguos</option>';
                    html += '<option value="title_asc" ' + (currentOrden === 'title_asc' ? 'selected' : '') + '>Título A-Z</option>';
                    html += '<option value="title_desc" ' + (currentOrden === 'title_desc' ? 'selected' : '') + '>Título Z-A</option>';
                    html += '</select>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    break;
            }

            html += '</div>';
            return html;
        },

        /**
         * Renderiza un item individual dentro de un repeater
         */
        renderRepeaterItem: function(fieldName, subFields, itemData, index) {
            var firstFieldKey = Object.keys(subFields)[0];
            var itemTitle = (itemData && itemData[firstFieldKey])
                ? this.escapeHtml(String(itemData[firstFieldKey]))
                : 'Item ' + (index + 1);

            var html = '<div class="flavor-pb-repeater-item" data-index="' + index + '">';
            html += '<div class="flavor-pb-repeater-item-header">';
            html += '<span class="dashicons dashicons-menu flavor-pb-repeater-drag"></span>';
            html += '<span class="flavor-pb-repeater-item-title">' + itemTitle + '</span>';
            html += '<div class="flavor-pb-repeater-item-actions">';
            html += '<button type="button" class="flavor-pb-repeater-toggle" title="Expandir/Colapsar">';
            html += '<span class="dashicons dashicons-arrow-down-alt2"></span>';
            html += '</button>';
            html += '<button type="button" class="flavor-pb-repeater-remove" data-field-name="' + fieldName + '" title="Eliminar">';
            html += '<span class="dashicons dashicons-trash"></span>';
            html += '</button>';
            html += '</div>';
            html += '</div>';

            html += '<div class="flavor-pb-repeater-item-body" style="display:none;">';
            for (var subFieldName in subFields) {
                if (!subFields.hasOwnProperty(subFieldName)) continue;
                var subField = subFields[subFieldName];
                var subValue = (itemData && itemData[subFieldName] !== undefined)
                    ? itemData[subFieldName]
                    : (subField.default !== undefined ? subField.default : '');
                html += this.renderField(subFieldName, subField, subValue);
            }
            html += '</div>';
            html += '</div>';

            return html;
        },

        /**
         * Añade un nuevo item vacío a un repeater
         */
        addRepeaterItem: function(fieldName) {
            var componentData = this.layout[this.currentEditingIndex];
            var component = this.components[componentData.component_id];
            var fieldDef = component.fields[fieldName];

            if (!fieldDef || fieldDef.type !== 'repeater') return;

            var $repeater = $('.flavor-pb-repeater[data-field-name="' + fieldName + '"]');
            var maxItems = parseInt($repeater.data('max-items')) || 10;
            var currentCount = $repeater.find('.flavor-pb-repeater-item').length;

            if (currentCount >= maxItems) {
                alert('Máximo ' + maxItems + ' items permitidos');
                return;
            }

            var nuevoItemData = {};
            for (var subName in fieldDef.fields) {
                if (!fieldDef.fields.hasOwnProperty(subName)) continue;
                nuevoItemData[subName] = fieldDef.fields[subName].default !== undefined
                    ? fieldDef.fields[subName].default
                    : '';
            }

            var itemHtml = this.renderRepeaterItem(fieldName, fieldDef.fields, nuevoItemData, currentCount);
            $repeater.find('.flavor-pb-repeater-items').append(itemHtml);

            // Auto-expand the new item
            var $nuevoItem = $repeater.find('.flavor-pb-repeater-item').last();
            $nuevoItem.find('> .flavor-pb-repeater-item-body').slideDown(200);
            $nuevoItem.find('.flavor-pb-repeater-toggle .dashicons')
                .removeClass('dashicons-arrow-down-alt2')
                .addClass('dashicons-arrow-up-alt2');
        },

        initMediaUploader: function() {
            // Use event delegation so dynamically added fields (repeater items) also work
            var $modalBody = $('#flavor-pb-edit-modal-body');

            $modalBody.off('click.flavorMedia', '.flavor-pb-upload-image');
            $modalBody.on('click.flavorMedia', '.flavor-pb-upload-image', function(e) {
                e.preventDefault();

                var button = $(this);
                var field = button.closest('.flavor-pb-image-field');

                var frame = wp.media({
                    title: 'Seleccionar Imagen',
                    button: {text: 'Usar esta imagen'},
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    field.find('.flavor-pb-image-id').val(attachment.id);
                    field.find('.flavor-pb-image-preview').html('<img src="' + attachment.url + '" alt="">');
                    field.find('.flavor-pb-remove-image').show();
                });

                frame.open();
            });

            $modalBody.off('click.flavorMedia', '.flavor-pb-remove-image');
            $modalBody.on('click.flavorMedia', '.flavor-pb-remove-image', function(e) {
                e.preventDefault();
                var field = $(this).closest('.flavor-pb-image-field');
                field.find('.flavor-pb-image-id').val('');
                field.find('.flavor-pb-image-preview').html('<span class="dashicons dashicons-format-image"></span>');
                $(this).hide();
            });
        },

        /**
         * Resuelve previews de imágenes que tienen attachment IDs numéricos
         */
        resolveImagePreviews: function() {
            $('#flavor-pb-edit-modal-body .flavor-pb-image-loading').each(function() {
                var $placeholder = $(this);
                var attachmentId = parseInt($placeholder.data('attachment-id'));

                if (!attachmentId || !wp.media || !wp.media.attachment) return;

                var attachment = wp.media.attachment(attachmentId);
                attachment.fetch().then(function() {
                    var thumbnailUrl = attachment.get('url');
                    if (attachment.get('sizes') && attachment.get('sizes').medium) {
                        thumbnailUrl = attachment.get('sizes').medium.url;
                    } else if (attachment.get('sizes') && attachment.get('sizes').thumbnail) {
                        thumbnailUrl = attachment.get('sizes').thumbnail.url;
                    }
                    if (thumbnailUrl) {
                        $placeholder.replaceWith('<img src="' + thumbnailUrl + '" alt="">');
                    }
                });
            });
        },

        saveComponentEdit: function() {
            var componentData = this.layout[this.currentEditingIndex];
            var componentDef = this.components[componentData.component_id];
            var formData = {};
            var $modalBody = $('#flavor-pb-edit-modal-body');

            for (var fieldName in componentDef.fields) {
                if (!componentDef.fields.hasOwnProperty(fieldName)) continue;
                var fieldDef = componentDef.fields[fieldName];

                if (fieldDef.type === 'repeater') {
                    var repeaterItems = [];
                    var $repeater = $modalBody.find('.flavor-pb-repeater[data-field-name="' + fieldName + '"]');

                    $repeater.find('.flavor-pb-repeater-item').each(function() {
                        var itemData = {};
                        var $body = $(this).find('> .flavor-pb-repeater-item-body');

                        for (var subFieldName in fieldDef.fields) {
                            if (!fieldDef.fields.hasOwnProperty(subFieldName)) continue;
                            var $subInput = $body.find('[name="' + subFieldName + '"]');

                            if ($subInput.length) {
                                if ($subInput.attr('type') === 'checkbox') {
                                    itemData[subFieldName] = $subInput.is(':checked');
                                } else {
                                    itemData[subFieldName] = $subInput.val() || '';
                                }
                            }
                        }
                        repeaterItems.push(itemData);
                    });

                    formData[fieldName] = repeaterItems;

                } else if (fieldDef.type === 'data_source') {
                    var $container = $modalBody.find('.flavor-pb-data-source-container[data-field-name="' + fieldName + '"]');
                    formData[fieldName] = {
                        source: $container.find('.flavor-pb-data-source-select').val() || 'manual',
                        limite: parseInt($container.find('[name="ds_limite"]').val()) || 6,
                        orden: $container.find('[name="ds_orden"]').val() || 'date_desc'
                    };

                } else {
                    var $input = $modalBody.find('[name="' + fieldName + '"]');
                    if ($input.length) {
                        if ($input.attr('type') === 'checkbox') {
                            formData[fieldName] = $input.is(':checked');
                        } else {
                            formData[fieldName] = $input.val();
                        }
                    }
                }
            }

            this.layout[this.currentEditingIndex].data = formData;
            this.saveLayout();
            $('#flavor-pb-edit-modal').fadeOut();
        },

        duplicateComponent: function(index) {
            var componentData = JSON.parse(JSON.stringify(this.layout[index]));
            this.layout.splice(index + 1, 0, componentData);
            this.saveLayout();
            this.refreshCanvas();
        },

        deleteComponent: function(index) {
            this.layout.splice(index, 1);
            this.saveLayout();
            this.refreshCanvas();
        },

        moveComponent: function(index, direction) {
            if (direction === 'up' && index > 0) {
                var temp = this.layout[index];
                this.layout[index] = this.layout[index - 1];
                this.layout[index - 1] = temp;
            } else if (direction === 'down' && index < this.layout.length - 1) {
                var temp2 = this.layout[index];
                this.layout[index] = this.layout[index + 1];
                this.layout[index + 1] = temp2;
            }
            this.saveLayout();
            this.refreshCanvas();
        },

        refreshCanvas: function() {
            $('#flavor-pb-components-list').empty();
            for (var i = 0; i < this.layout.length; i++) {
                this.renderComponent(i, this.layout[i]);
            }
        },

        escapeHtml: function(text) {
            if (typeof text !== 'string') return String(text != null ? text : '');
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        openTemplateLibrary: function() {
            $('#flavor-pb-templates-modal').fadeIn();
        },

        loadTemplate: function(templateId) {
            // Buscar template en la estructura de sectores
            var template = null;
            var templates = flavorPageBuilder.templates || {};

            for (var sector in templates) {
                if (templates[sector].templates && templates[sector].templates[templateId]) {
                    template = templates[sector].templates[templateId];
                    break;
                }
            }

            if (!template || !template.layout) {
                alert('Template no encontrado');
                return;
            }

            // Confirmar antes de reemplazar
            if (this.layout.length > 0) {
                if (!confirm(flavorPageBuilder.i18n.confirmLoadTemplate)) {
                    return;
                }
            }

            // Cargar el layout del template
            this.layout = JSON.parse(JSON.stringify(template.layout));
            this.saveLayout();
            this.refreshCanvas();
            $('#flavor-pb-templates-modal').fadeOut();

            // Mostrar mensaje de éxito
            alert('Plantilla cargada correctamente. ¡Ahora puedes personalizarla!');
        },

        /**
         * Cargar layout directamente (usado por el asistente IA)
         */
        loadLayoutFromData: function(layoutData) {
            if (!layoutData || !Array.isArray(layoutData)) {
                console.error('Layout data inválido');
                return false;
            }

            // Confirmar antes de reemplazar si hay contenido
            if (this.layout.length > 0) {
                if (!confirm(flavorPageBuilder.i18n.confirmLoadTemplate || '¿Deseas reemplazar el contenido actual?')) {
                    return false;
                }
            }

            // Cargar el layout
            this.layout = JSON.parse(JSON.stringify(layoutData));
            this.saveLayout();
            this.refreshCanvas();

            return true;
        },

        /**
         * Abrir vista previa en nueva ventana
         */
        openPreview: function() {
            var self = this;

            if (this.layout.length === 0) {
                alert('No hay componentes para previsualizar. Añade algunos primero.');
                return;
            }

            // Mostrar indicador de carga
            var $btn = $('#flavor-pb-preview');
            var originalText = $btn.html();
            $btn.html('<span class="dashicons dashicons-update spin"></span> Generando vista previa...').prop('disabled', true);

            // Guardar layout actual
            var postId = $('#post_ID').val() || 0;
            var layoutJson = JSON.stringify(this.layout);

            // Enviar a servidor para generar preview
            $.ajax({
                url: flavorPageBuilder.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_save_preview',
                    nonce: flavorPageBuilder.nonce,
                    layout: layoutJson,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success && response.data.preview_url) {
                        // Abrir en nueva ventana
                        var width = 1200;
                        var height = 800;
                        var left = (screen.width / 2) - (width / 2);
                        var top = (screen.height / 2) - (height / 2);

                        window.open(
                            response.data.preview_url,
                            'flavor_preview',
                            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no'
                        );
                    } else {
                        alert('Error al generar vista previa: ' + (response.data.message || 'Error desconocido'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    alert('Error de conexión al generar vista previa');
                },
                complete: function() {
                    // Restaurar botón
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        FlavorPageBuilder.init();
    });

    // Exponer globalmente para integración con otros módulos (ej: AI Assistant)
    window.FlavorPageBuilder = FlavorPageBuilder;

})(jQuery);
