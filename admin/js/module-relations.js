/**
 * JavaScript para Configuración de Relaciones entre Módulos
 */

(function($) {
    'use strict';

    const FlavorModuleRelations = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.updatePreview();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Guardar formulario
            $('#flavor-module-relations-form').on('submit', this.handleSave.bind(this));

            // Resetear relaciones
            $('#flavor-reset-relations').on('click', this.handleReset.bind(this));

            // Cambio de contexto
            $('#context-select').on('change', this.handleContextChange.bind(this));

            // Actualizar preview al cambiar checkboxes
            $('#flavor-module-relations-form').on('change', 'input[type="checkbox"]', this.updatePreview.bind(this));
        },

        /**
         * Guardar relaciones
         */
        handleSave: function(e) {
            e.preventDefault();

            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const formData = new FormData($form[0]);

            // Recopilar relaciones
            const relations = {};
            $form.find('input[type="checkbox"]:checked').each(function() {
                const parentId = $(this).data('parent');
                const childId = $(this).data('child');

                if (!relations[parentId]) {
                    relations[parentId] = [];
                }

                relations[parentId].push(childId);
            });

            // Preparar datos AJAX
            const ajaxData = {
                action: 'flavor_save_module_relations',
                nonce: flavorModuleRelations.nonce,
                relations: relations,
                context: $form.find('input[name="context"]').val()
            };

            // Mostrar loading
            $submitBtn.prop('disabled', true).text('Guardando...');

            // Enviar AJAX
            $.ajax({
                url: flavorModuleRelations.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        FlavorModuleRelations.showNotice(
                            flavorModuleRelations.i18n.guardado,
                            'success'
                        );
                    } else {
                        FlavorModuleRelations.showNotice(
                            response.data.message || flavorModuleRelations.i18n.error,
                            'error'
                        );
                    }
                },
                error: function() {
                    FlavorModuleRelations.showNotice(
                        flavorModuleRelations.i18n.error,
                        'error'
                    );
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text($submitBtn.data('original-text') || 'Guardar Relaciones');
                }
            });
        },

        /**
         * Resetear relaciones
         */
        handleReset: function(e) {
            e.preventDefault();

            if (!confirm(flavorModuleRelations.i18n.confirmReset)) {
                return;
            }

            const $btn = $(e.target);

            // Mostrar loading
            $btn.prop('disabled', true).text('Reseteando...');

            $.ajax({
                url: flavorModuleRelations.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_reset_module_relations',
                    nonce: flavorModuleRelations.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorModuleRelations.showNotice(
                            response.data.message,
                            'success'
                        );
                        // Recargar página después de 1 segundo
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        FlavorModuleRelations.showNotice(
                            response.data.message || 'Error al resetear',
                            'error'
                        );
                    }
                },
                error: function() {
                    FlavorModuleRelations.showNotice('Error al resetear', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Resetear a Valores por Defecto');
                }
            });
        },

        /**
         * Cambio de contexto
         */
        handleContextChange: function(e) {
            const context = $(e.target).val();
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('context', context);
            window.location.href = currentUrl.toString();
        },

        /**
         * Mostrar notificación
         */
        showNotice: function(message, type) {
            const $notice = $('.flavor-mr-notice');
            $notice
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .fadeIn();

            // Ocultar después de 5 segundos
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        },

        /**
         * Actualizar previsualización
         */
        updatePreview: function() {
            const $container = $('#flavor-nav-preview-container');
            $container.empty();

            // Recopilar datos de previsualización
            $('.flavor-mr-vertical-module').each(function() {
                const $module = $(this);
                const moduleName = $module.find('.flavor-mr-module-header h2').text();
                const moduleIcon = $module.find('.flavor-mr-module-header .dashicons').attr('class');

                // Obtener módulos horizontales seleccionados
                const selectedHorizontals = [];
                $module.find('input[type="checkbox"]:checked').each(function() {
                    const $label = $(this).closest('.flavor-mr-checkbox-label');
                    selectedHorizontals.push({
                        name: $label.find('.flavor-mr-module-label').text(),
                        icon: $label.find('.dashicons').attr('class')
                    });
                });

                // Solo mostrar si hay módulos seleccionados
                if (selectedHorizontals.length > 0) {
                    const $preview = $('<div class="flavor-nav-preview-module"></div>');
                    $preview.append('<h4>' + moduleName + '</h4>');

                    const $tabs = $('<div class="flavor-nav-preview-tabs"></div>');

                    // Tab principal del módulo
                    $tabs.append(
                        '<div class="flavor-nav-preview-tab">' +
                        '<span class="' + moduleIcon + '"></span>' +
                        '<span>' + moduleName + '</span>' +
                        '</div>'
                    );

                    // Tabs de módulos horizontales
                    selectedHorizontals.forEach(function(horizontal) {
                        $tabs.append(
                            '<div class="flavor-nav-preview-tab">' +
                            '<span class="' + horizontal.icon + '"></span>' +
                            '<span>' + horizontal.name + '</span>' +
                            '</div>'
                        );
                    });

                    $preview.append($tabs);
                    $container.append($preview);
                }
            });

            // Mensaje si no hay previsualizaciones
            if ($container.children().length === 0) {
                $container.html('<p style="color: #646970; font-style: italic;">Selecciona módulos horizontales para ver la previsualización de navegación.</p>');
            }
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        FlavorModuleRelations.init();
    });

})(jQuery);
