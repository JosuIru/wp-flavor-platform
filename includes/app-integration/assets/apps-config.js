/**
 * JavaScript para el panel de configuración de apps
 * Módulos: Preview tiempo real, Editor tabs, Info sections, Presets, Selector módulos
 */

(function($) {
    'use strict';

    // ========================================
    // MÓDULO: Preview Tiempo Real
    // ========================================
    var moduloPreviewTiempoReal = {
        init: function() {
            this.bindColorChanges();
            this.bindLogoChanges();
            this.bindNameChanges();
        },

        bindColorChanges: function() {
            // Escuchar cambios en los wpColorPicker
            $(document).on('click', '.wp-color-result, .iris-palette', function() {
                setTimeout(function() {
                    moduloPreviewTiempoReal.actualizarColores();
                }, 100);
            });

            // Color picker change event
            $('.color-picker').wpColorPicker({
                change: function() {
                    setTimeout(function() {
                        moduloPreviewTiempoReal.actualizarColores();
                    }, 50);
                },
                clear: function() {
                    moduloPreviewTiempoReal.actualizarColores();
                }
            });
        },

        bindLogoChanges: function() {
            // Observar cambios en el campo de logo
            var observadorLogo = new MutationObserver(function() {
                moduloPreviewTiempoReal.actualizarLogo();
            });

            var logoPreview = document.querySelector('.logo-preview');
            if (logoPreview) {
                observadorLogo.observe(logoPreview, { childList: true, subtree: true });
            }
        },

        bindNameChanges: function() {
            $('input[name="flavor_apps_config[app_name]"]').on('input', function() {
                var nuevoNombre = $(this).val() || 'Mi App';
                $('#mockup-nombre-app').text(nuevoNombre);
            });
        },

        actualizarColores: function() {
            var colorPrimario = $('input[name="flavor_apps_config[primary_color]"]').val();
            if (colorPrimario) {
                $('#mockup-app-bar').css('background-color', colorPrimario);

                // Actualizar color del tab activo
                $('#mockup-navegacion-inferior .mockup-tab-item.active .material-icons').css('color', colorPrimario);
            }
        },

        actualizarLogo: function() {
            var $logoImg = $('.logo-preview img');
            var $mockupLogo = $('#mockup-logo-app');

            if ($logoImg.length && $logoImg.attr('src')) {
                $mockupLogo.attr('src', $logoImg.attr('src')).show();
            } else {
                $mockupLogo.hide();
            }
        }
    };

    // ========================================
    // MÓDULO: Editor de Tabs (Sortable)
    // ========================================
    var moduloEditorTabs = {
        tabIconEditando: null,

        init: function() {
            this.initSortable();
            this.bindToggleEvents();
            this.bindIconSelector();
            this.bindLabelChanges();
        },

        initSortable: function() {
            if (!$.fn.sortable) return;

            $('#flavor-tabs-sortable').sortable({
                handle: '.flavor-tab-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                update: function() {
                    moduloEditorTabs.actualizarOrdenTabs();
                    moduloEditorTabs.actualizarMockupTabs();
                }
            });
        },

        bindToggleEvents: function() {
            $(document).on('change', '.flavor-tab-toggle', function() {
                var $item = $(this).closest('.flavor-tab-item');
                var estaHabilitado = $(this).is(':checked');

                if (estaHabilitado) {
                    $item.removeClass('disabled');
                } else {
                    $item.addClass('disabled');
                }

                // Validar máximo 5 tabs activos
                var tabsActivos = $('.flavor-tab-toggle:checked').length;
                if (tabsActivos > 5) {
                    $(this).prop('checked', false);
                    $item.addClass('disabled');
                    alert(flavorAppsConfig.strings.maxTabs || 'Máximo 5 tabs activos permitidos');
                    return;
                }

                moduloEditorTabs.actualizarMockupTabs();
            });
        },

        bindIconSelector: function() {
            // Abrir modal al click en icono
            $(document).on('click', '.flavor-tab-icon-btn', function() {
                moduloEditorTabs.tabIconEditando = $(this);
                var iconoActual = $(this).find('.material-icons').text();

                // Marcar icono actual como seleccionado
                $('.flavor-icon-option').removeClass('selected');
                $('.flavor-icon-option[data-icon="' + iconoActual + '"]').addClass('selected');

                $('#flavor-icon-modal').addClass('active');
            });

            // Cerrar modal
            $(document).on('click', '.flavor-icon-modal-close', function() {
                $('#flavor-icon-modal').removeClass('active');
                moduloEditorTabs.tabIconEditando = null;
            });

            // Click en overlay para cerrar
            $(document).on('click', '.flavor-icon-modal-overlay', function(e) {
                if ($(e.target).hasClass('flavor-icon-modal-overlay')) {
                    $(this).removeClass('active');
                    moduloEditorTabs.tabIconEditando = null;
                }
            });

            // Seleccionar icono
            $(document).on('click', '.flavor-icon-option', function() {
                var iconoSeleccionado = $(this).data('icon');

                if (moduloEditorTabs.tabIconEditando) {
                    // Actualizar botón del tab
                    moduloEditorTabs.tabIconEditando.find('.material-icons').text(iconoSeleccionado);

                    // Actualizar hidden input
                    var $tabItem = moduloEditorTabs.tabIconEditando.closest('.flavor-tab-item');
                    $tabItem.find('.flavor-tab-icon-value').val(iconoSeleccionado);

                    moduloEditorTabs.actualizarMockupTabs();
                }

                $('#flavor-icon-modal').removeClass('active');
                moduloEditorTabs.tabIconEditando = null;
            });

            // Búsqueda de iconos
            $(document).on('input', '#flavor-icon-search-input', function() {
                var consulta = $(this).val().toLowerCase().trim();
                $('.flavor-icon-option').each(function() {
                    var nombreIcono = $(this).data('icon') || '';
                    $(this).toggle(nombreIcono.indexOf(consulta) !== -1);
                });
            });
        },

        bindLabelChanges: function() {
            $(document).on('input', '.flavor-tab-label-input', function() {
                moduloEditorTabs.actualizarMockupTabs();
            });
        },

        actualizarOrdenTabs: function() {
            $('#flavor-tabs-sortable .flavor-tab-item').each(function(index) {
                $(this).find('.flavor-tab-order').val(index);
            });
        },

        actualizarMockupTabs: function() {
            var $navegacionInferior = $('#mockup-navegacion-inferior');
            $navegacionInferior.empty();

            var colorPrimario = $('input[name="flavor_apps_config[primary_color]"]').val() || '#4CAF50';
            var contadorMostrados = 0;

            $('#flavor-tabs-sortable .flavor-tab-item').each(function() {
                var estaHabilitado = $(this).find('.flavor-tab-toggle').is(':checked');
                if (!estaHabilitado || contadorMostrados >= 5) return;

                var icono = $(this).find('.flavor-tab-icon-value').val() || 'circle';
                var label = $(this).find('.flavor-tab-label-input').val() || '';
                var esActivo = contadorMostrados === 0;

                var colorIcono = esActivo ? colorPrimario : '#999';

                var htmlTab = '<div class="mockup-tab-item ' + (esActivo ? 'active' : '') + '">'
                    + '<span class="material-icons" style="color:' + colorIcono + ';">' + icono + '</span>'
                    + '<span class="mockup-tab-label">' + label + '</span>'
                    + '</div>';

                $navegacionInferior.append(htmlTab);
                contadorMostrados++;
            });
        }
    };

    // ========================================
    // MÓDULO: Info Sections (Sortable)
    // ========================================
    var moduloInfoSections = {
        init: function() {
            this.initSortable();
        },

        initSortable: function() {
            if (!$.fn.sortable) return;

            $('#flavor-info-sections-sortable').sortable({
                handle: '.section-drag-handle',
                update: function() {
                    moduloInfoSections.actualizarOrden();
                }
            });
        },

        actualizarOrden: function() {
            $('#flavor-info-sections-sortable .flavor-info-section-item').each(function(index) {
                $(this).find('.flavor-section-order').val(index);
            });
        }
    };

    // ========================================
    // MÓDULO: Presets
    // ========================================
    var moduloPresets = {
        presets: {
            restaurante: {
                tabs: [
                    { id: 'info', label: 'Info', icon: 'info', enabled: true },
                    { id: 'reservations', label: 'Reservar', icon: 'restaurant', enabled: true },
                    { id: 'chat', label: 'Chat', icon: 'chat_bubble', enabled: true },
                    { id: 'my_tickets', label: 'Tickets', icon: 'confirmation_number', enabled: true },
                ],
                modules: ['eventos', 'woocommerce'],
                primaryColor: '#FF5722'
            },
            peluqueria: {
                tabs: [
                    { id: 'info', label: 'Info', icon: 'info', enabled: true },
                    { id: 'reservations', label: 'Cita', icon: 'calendar_today', enabled: true },
                    { id: 'chat', label: 'Chat', icon: 'chat_bubble', enabled: true },
                    { id: 'my_tickets', label: 'Mis Citas', icon: 'confirmation_number', enabled: true },
                ],
                modules: [],
                primaryColor: '#E91E63'
            },
            comunidad: {
                tabs: [
                    { id: 'info', label: 'Info', icon: 'info', enabled: true },
                    { id: 'chat', label: 'Chat', icon: 'forum', enabled: true },
                    { id: 'reservations', label: 'Eventos', icon: 'event', enabled: true },
                    { id: 'my_tickets', label: 'Tickets', icon: 'confirmation_number', enabled: true },
                ],
                modules: ['grupos_consumo', 'banco_tiempo', 'eventos', 'marketplace'],
                primaryColor: '#4CAF50'
            },
            tienda: {
                tabs: [
                    { id: 'info', label: 'Tienda', icon: 'store', enabled: true },
                    { id: 'chat', label: 'Chat', icon: 'chat_bubble', enabled: true },
                    { id: 'reservations', label: 'Pedidos', icon: 'shopping_cart', enabled: true },
                    { id: 'my_tickets', label: 'Mis Pedidos', icon: 'receipt', enabled: true },
                ],
                modules: ['woocommerce', 'marketplace'],
                primaryColor: '#9C27B0'
            }
        },

        init: function() {
            this.bindPresetButtons();
        },

        bindPresetButtons: function() {
            $(document).on('click', '.flavor-preset-btn', function() {
                var nombrePreset = $(this).data('preset');
                moduloPresets.aplicarPreset(nombrePreset);

                // Visual feedback
                $('.flavor-preset-btn').removeClass('active');
                $(this).addClass('active');
            });
        },

        aplicarPreset: function(nombrePreset) {
            var preset = this.presets[nombrePreset];
            if (!preset) return;

            // Actualizar tabs
            if (preset.tabs) {
                $('#flavor-tabs-sortable .flavor-tab-item').each(function(index) {
                    var tabId = $(this).data('tab-id');
                    var configTab = null;

                    for (var i = 0; i < preset.tabs.length; i++) {
                        if (preset.tabs[i].id === tabId) {
                            configTab = preset.tabs[i];
                            break;
                        }
                    }

                    if (configTab) {
                        $(this).find('.flavor-tab-toggle').prop('checked', configTab.enabled).trigger('change');
                        $(this).find('.flavor-tab-label-input').val(configTab.label);
                        $(this).find('.flavor-tab-icon-value').val(configTab.icon);
                        $(this).find('.flavor-tab-icon-btn .material-icons').text(configTab.icon);
                    } else {
                        $(this).find('.flavor-tab-toggle').prop('checked', false).trigger('change');
                    }
                });

                moduloEditorTabs.actualizarMockupTabs();
            }

            // Actualizar módulos
            if (preset.modules) {
                $('.flavor-module-toggle').each(function() {
                    var moduleId = $(this).attr('name').match(/\[([^\]]+)\]\[enabled\]/);
                    if (moduleId) {
                        var idModulo = moduleId[1];
                        var debeEstarActivo = preset.modules.indexOf(idModulo) !== -1;
                        $(this).prop('checked', debeEstarActivo);

                        var $card = $(this).closest('.flavor-module-card');
                        $card.toggleClass('module-disabled', !debeEstarActivo);
                    }
                });
            }

            // Actualizar color primario en mockup
            if (preset.primaryColor) {
                $('#mockup-app-bar').css('background-color', preset.primaryColor);
            }

            alert(flavorAppsConfig.strings.presetApplied || 'Preset aplicado correctamente');
        }
    };

    // ========================================
    // MÓDULO: Selector de Módulos
    // ========================================
    var moduloSelectorModulos = {
        init: function() {
            this.bindToggleEvents();
        },

        bindToggleEvents: function() {
            $(document).on('change', '.flavor-module-toggle', function() {
                var $card = $(this).closest('.flavor-module-card');
                var estaHabilitado = $(this).is(':checked');
                $card.toggleClass('module-disabled', !estaHabilitado);
            });
        }
    };

    // ========================================
    // FUNCIONALIDAD EXISTENTE (logo, tokens, directorio)
    // ========================================
    var moduloFuncionalidadBase = {
        mediaUploader: null,

        init: function() {
            this.initLogoUpload();
            this.initTokenManagement();
            this.initDirectoryRegistration();
            this.initTokenClipboard();
        },

        initLogoUpload: function() {
            var self = this;

            $('#upload_logo_button').on('click', function(e) {
                e.preventDefault();

                if (self.mediaUploader) {
                    self.mediaUploader.open();
                    return;
                }

                self.mediaUploader = wp.media({
                    title: 'Seleccionar Logo',
                    button: { text: 'Usar este logo' },
                    multiple: false,
                    library: { type: 'image' }
                });

                self.mediaUploader.on('select', function() {
                    var attachment = self.mediaUploader.state().get('selection').first().toJSON();
                    $('#app_logo_id').val(attachment.id);
                    $('.logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                    $('#remove_logo_button').show();

                    // Actualizar mockup
                    $('#mockup-logo-app').attr('src', attachment.url).show();
                });

                self.mediaUploader.open();
            });

            $('#remove_logo_button').on('click', function(e) {
                e.preventDefault();
                $('#app_logo_id').val('');
                $('.logo-preview').html('<p>No hay logo seleccionado</p>');
                $(this).hide();

                // Actualizar mockup
                $('#mockup-logo-app').hide();
            });
        },

        initTokenManagement: function() {
            // Generar token
            $('#generate_token_button').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var tokenName = $('#new_token_name').val().trim();

                if (!tokenName) {
                    alert('Por favor, ingresa un nombre para el token');
                    return;
                }

                $button.prop('disabled', true).text('Generando...');

                $.ajax({
                    url: flavorAppsConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'generate_app_token',
                        nonce: flavorAppsConfig.nonce,
                        name: tokenName
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#new_token_value').text(response.data.token);
                            $('#new_token_display').slideDown();
                            $('#new_token_name').val('');

                            setTimeout(function() {
                                location.reload();
                            }, 5000);
                        } else {
                            alert(response.data.message || flavorAppsConfig.strings.error);
                        }
                    },
                    error: function() {
                        alert(flavorAppsConfig.strings.error);
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Generar Token');
                    }
                });
            });

            // Revocar token
            $('.revoke-token').on('click', function(e) {
                e.preventDefault();

                if (!confirm(flavorAppsConfig.strings.confirmRevoke)) return;

                var $button = $(this);
                var tokenId = $button.data('token-id');
                var $row = $button.closest('tr');

                $button.prop('disabled', true).text('Revocando...');

                $.ajax({
                    url: flavorAppsConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'revoke_app_token',
                        nonce: flavorAppsConfig.nonce,
                        token_id: tokenId
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                if ($('.revoke-token').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(response.data.message || flavorAppsConfig.strings.error);
                            $button.prop('disabled', false).text('Revocar');
                        }
                    },
                    error: function() {
                        alert(flavorAppsConfig.strings.error);
                        $button.prop('disabled', false).text('Revocar');
                    }
                });
            });
        },

        initTokenClipboard: function() {
            $('#new_token_value').on('click', function() {
                var token = $(this).text();

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(token);
                } else {
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(token).select();
                    document.execCommand('copy');
                    $temp.remove();
                }

                var originalBg = $(this).css('background-color');
                $(this).css('background-color', '#d4edda');
                setTimeout(function() {
                    $('#new_token_value').css('background-color', originalBg);
                }, 500);

                alert('Token copiado al portapapeles');
            });
        },

        initDirectoryRegistration: function() {
            $('#register_in_directory').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                $button.prop('disabled', true).text('Registrando...');

                $.ajax({
                    url: flavorAppsConfig.restUrl + 'app-discovery/v1/businesses/register',
                    type: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', flavorAppsConfig.restNonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('¡Negocio registrado exitosamente en el directorio!');
                            location.reload();
                        } else {
                            alert(response.message || 'Error al registrar');
                            $button.prop('disabled', false).text('Registrar Ahora');
                        }
                    },
                    error: function() {
                        alert('Error al conectar con el servidor');
                        $button.prop('disabled', false).text('Registrar Ahora');
                    }
                });
            });
        }
    };

    // ========================================
    // INICIALIZACIÓN
    // ========================================
    $(document).ready(function() {
        moduloFuncionalidadBase.init();
        moduloPreviewTiempoReal.init();
        moduloEditorTabs.init();
        moduloInfoSections.init();
        moduloPresets.init();
        moduloSelectorModulos.init();
    });

})(jQuery);
