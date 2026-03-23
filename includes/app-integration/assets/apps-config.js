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
            this.bindTypeChanges();
            this.bindRemoveEvents();
            this.bindAddWebTab();
            this.bindAddWebTabFromMenu();
            this.bindAddAllWebTabs();
            this.bindSyncWebTabLabels();
            this.bindDrawerTypeChanges();
            this.bindDrawerIconSelector();
            this.bindMockupDrawer();
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
                    if ($tabItem.length) {
                        $tabItem.find('.flavor-tab-icon-value').val(iconoSeleccionado);
                    }
                    var $drawerItem = moduloEditorTabs.tabIconEditando.closest('.flavor-info-section-item');
                    if ($drawerItem.length) {
                        $drawerItem.find('.flavor-drawer-icon-value').val(iconoSeleccionado);
                    }

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

        bindTypeChanges: function() {
            // Manejar cambio de content_type (nuevo sistema de renderizado nativo)
            $(document).on('change', '.flavor-tab-content-type', function() {
                var $item = $(this).closest('.flavor-tab-item');
                var contentType = $(this).val();

                // Ocultar todos los selectores de referencia
                $item.find('.flavor-tab-content-ref').hide();

                // Mostrar el selector correspondiente al tipo seleccionado
                switch (contentType) {
                    case 'native_screen':
                        $item.find('.flavor-content-native-screen').show();
                        break;
                    case 'page':
                        $item.find('.flavor-content-page').show();
                        break;
                    case 'cpt':
                        $item.find('.flavor-content-cpt').show();
                        break;
                    case 'module':
                        $item.find('.flavor-content-module').show();
                        break;
                }
            });

            // Legacy: manejar cambio de type web/native (mantener compatibilidad)
            $(document).on('change', '.flavor-tab-type', function() {
                var $item = $(this).closest('.flavor-tab-item');
                var type = $(this).val();
                var $urlWrapper = $item.find('.flavor-tab-url-wrapper');

                if (type === 'web') {
                    $urlWrapper.show();
                } else {
                    $urlWrapper.hide();
                }
            });
        },

        bindRemoveEvents: function() {
            $(document).on('click', '.flavor-tab-remove', function() {
                var $item = $(this).closest('.flavor-tab-item');
                $item.remove();
                moduloEditorTabs.actualizarOrdenTabs();
                moduloEditorTabs.actualizarMockupTabs();
            });
        },

        bindAddWebTab: function() {
            $('#flavor-add-web-tab').on('click', function() {
                moduloEditorTabs.addWebTab('', '');
            });
        },

        bindAddWebTabFromMenu: function() {
            $('#flavor-add-web-tab-from-menu').on('click', function() {
                var $select = $('#flavor-web-section-select');
                var url = $select.val();
                if (!url) return;

                var label = $select.find('option:selected').data('title') || '';
                moduloEditorTabs.addWebTab(label, url);
            });
        },

        bindAddAllWebTabs: function() {
            $('#flavor-add-all-web-tabs').on('click', function() {
                var $select = $('#flavor-web-section-select');
                $select.find('option').each(function() {
                    var url = $(this).val();
                    if (!url) return;
                    var label = $(this).data('title') || '';
                    moduloEditorTabs.addWebTab(label, url, true);
                });
            });
        },

        bindSyncWebTabLabels: function() {
            $('#flavor-sync-web-tab-labels').on('click', function() {
                var map = {};
                $('#flavor-web-section-select option').each(function() {
                    var url = $(this).val();
                    var title = $(this).data('title');
                    if (url) {
                        map[url] = title || '';
                    }
                });

                $('#flavor-tabs-sortable .flavor-tab-item').each(function() {
                    var $item = $(this);
                    var type = $item.find('.flavor-tab-type').val();
                    if (type !== 'web') return;

                    var $url = $item.find('.flavor-tab-url-input');
                    var currentUrl = $url.val();
                    if (!currentUrl || !map[currentUrl]) return;

                    $item.find('.flavor-tab-label-input').val(map[currentUrl]);
                });

                moduloEditorTabs.actualizarMockupTabs();
            });
        },

        addWebTab: function(label, url) {
            var skipIfExists = arguments.length > 2 ? arguments[2] : false;
            var $list = $('#flavor-tabs-sortable');
            var index = $list.find('.flavor-tab-item').length;
            var tabId = 'custom_' + Date.now();
            var icon = moduloEditorTabs.guessIconFromLabel(label, url);
            var safeLabel = label || '';
            var safeUrl = url || '';

            if (skipIfExists && safeUrl) {
                var exists = false;
                $list.find('.flavor-tab-item').each(function() {
                    var existingLabel = $(this).find('.flavor-tab-label-input').val();
                    if (existingLabel === safeLabel) {
                        exists = true;
                        return false;
                    }
                });
                if (exists) return;
            }

            // Intentar detectar si es una página de WordPress
            var pageSlug = moduloEditorTabs.extractPageSlugFromUrl(safeUrl);
            var contentType = pageSlug ? 'page' : 'native_screen';
            var contentRef = pageSlug || 'info';

            var html = ''
                + '<li class="flavor-tab-item" data-tab-id="' + tabId + '" data-tab-core="0">'
                +   '<span class="flavor-tab-drag-handle dashicons dashicons-menu"></span>'
                +   '<label class="flavor-toggle-switch">'
                +     '<input type="hidden" name="flavor_apps_config[tabs][' + index + '][enabled]" value="0">'
                +     '<input type="checkbox" name="flavor_apps_config[tabs][' + index + '][enabled]" value="1" class="flavor-tab-toggle" checked>'
                +     '<span class="flavor-toggle-slider"></span>'
                +   '</label>'
                +   '<button type="button" class="flavor-tab-icon-btn" data-tab-index="' + index + '">'
                +     '<span class="material-icons">' + icon + '</span>'
                +   '</button>'
                +   '<input type="text" name="flavor_apps_config[tabs][' + index + '][label]" value="' + safeLabel + '" class="flavor-tab-label-input" placeholder="Etiqueta">'
                +   '<select name="flavor_apps_config[tabs][' + index + '][content_type]" class="flavor-tab-content-type">'
                +     '<option value="native_screen"' + (contentType === 'native_screen' ? ' selected' : '') + '>Pantalla nativa</option>'
                +     '<option value="page"' + (contentType === 'page' ? ' selected' : '') + '>Página</option>'
                +     '<option value="cpt">Contenido (CPT)</option>'
                +     '<option value="module">Módulo</option>'
                +   '</select>'
                // Selector de pantalla nativa
                +   '<select name="flavor_apps_config[tabs][' + index + '][content_ref]" class="flavor-tab-content-ref flavor-content-native-screen"' + (contentType !== 'native_screen' ? ' style="display:none;"' : '') + '>'
                +     '<option value="info"' + (contentRef === 'info' ? ' selected' : '') + '>Info</option>'
                +     '<option value="chat"' + (contentRef === 'chat' ? ' selected' : '') + '>Chat</option>'
                +     '<option value="reservations"' + (contentRef === 'reservations' ? ' selected' : '') + '>Reservas</option>'
                +     '<option value="my_tickets"' + (contentRef === 'my_tickets' ? ' selected' : '') + '>Mis Tickets</option>'
                +     '<option value="profile"' + (contentRef === 'profile' ? ' selected' : '') + '>Perfil</option>'
                +     '<option value="notifications"' + (contentRef === 'notifications' ? ' selected' : '') + '>Notificaciones</option>'
                +     '<option value="settings"' + (contentRef === 'settings' ? ' selected' : '') + '>Configuración</option>'
                +   '</select>'
                // Selector de página (se llenará con las páginas disponibles)
                +   '<select name="flavor_apps_config[tabs][' + index + '][content_ref_page]" class="flavor-tab-content-ref flavor-content-page"' + (contentType !== 'page' ? ' style="display:none;"' : '') + '>'
                +     '<option value="">Seleccionar página...</option>'
                +     moduloEditorTabs.getPageOptionsHtml(contentRef)
                +   '</select>'
                // Selector de CPT
                +   '<select name="flavor_apps_config[tabs][' + index + '][content_ref_cpt]" class="flavor-tab-content-ref flavor-content-cpt" style="display:none;">'
                +     '<option value="">Seleccionar tipo...</option>'
                +   '</select>'
                // Selector de módulo
                +   '<select name="flavor_apps_config[tabs][' + index + '][content_ref_module]" class="flavor-tab-content-ref flavor-content-module" style="display:none;">'
                +     '<option value="">Seleccionar módulo...</option>'
                +   '</select>'
                +   '<button type="button" class="button-link-delete flavor-tab-remove">Eliminar</button>'
                +   '<input type="hidden" name="flavor_apps_config[tabs][' + index + '][id]" value="' + tabId + '">'
                +   '<input type="hidden" name="flavor_apps_config[tabs][' + index + '][icon]" value="' + icon + '" class="flavor-tab-icon-value">'
                +   '<input type="hidden" name="flavor_apps_config[tabs][' + index + '][order]" value="' + index + '" class="flavor-tab-order">'
                + '</li>';

            $list.append(html);
            moduloEditorTabs.actualizarOrdenTabs();
            moduloEditorTabs.actualizarMockupTabs();
        },

        /**
         * Extrae el slug de página de una URL de WordPress
         */
        extractPageSlugFromUrl: function(url) {
            if (!url) return '';
            try {
                var urlObj = new URL(url, window.location.origin);
                var pathname = urlObj.pathname.replace(/^\/|\/$/g, '');
                // Si es la raíz, no es una página específica
                if (!pathname) return '';
                // Si contiene wp-admin o wp-json, no es una página
                if (pathname.includes('wp-admin') || pathname.includes('wp-json')) return '';
                // Retornar el último segmento como slug
                var segments = pathname.split('/');
                return segments[segments.length - 1] || '';
            } catch (e) {
                return '';
            }
        },

        /**
         * Genera el HTML de opciones de páginas existentes
         */
        getPageOptionsHtml: function(selectedSlug) {
            var html = '';
            // Obtener páginas del primer select de página existente
            var $existingSelect = $('.flavor-content-page').first();
            if ($existingSelect.length) {
                $existingSelect.find('option').each(function() {
                    var val = $(this).val();
                    if (!val) return;
                    var text = $(this).text();
                    var selected = (val === selectedSlug) ? ' selected' : '';
                    html += '<option value="' + val + '"' + selected + '>' + text + '</option>';
                });
            }
            return html;
        },

        bindDrawerTypeChanges: function() {
            // Nuevo sistema: manejar cambio de content_type en drawer items
            $(document).on('change', '.flavor-drawer-content-type', function() {
                var $item = $(this).closest('.flavor-drawer-item');
                var contentType = $(this).val();

                // Ocultar todos los selectores de referencia
                $item.find('.flavor-drawer-content-ref').hide();

                // Mostrar el selector correspondiente al tipo seleccionado
                switch (contentType) {
                    case 'native_screen':
                        $item.find('.flavor-drawer-native-screen').show();
                        break;
                    case 'page':
                        $item.find('.flavor-drawer-page').show();
                        break;
                    case 'cpt':
                        $item.find('.flavor-drawer-cpt').show();
                        break;
                    case 'module':
                        $item.find('.flavor-drawer-module').show();
                        break;
                }
            });

            // Legacy: manejar cambio de type web/native (compatibilidad)
            $(document).on('change', '.flavor-drawer-type', function() {
                var $item = $(this).closest('.flavor-info-section-item');
                var type = $(this).val();
                var $target = $item.find('.flavor-drawer-target');
                if (type === 'native') {
                    $target.show();
                } else {
                    $target.hide();
                }
            });
        },

        bindDrawerIconSelector: function() {
            $(document).on('click', '.flavor-drawer-icon-btn', function() {
                moduloEditorTabs.tabIconEditando = $(this);
                var iconoActual = $(this).find('.material-icons').text();

                $('.flavor-icon-option').removeClass('selected');
                $('.flavor-icon-option[data-icon="' + iconoActual + '"]').addClass('selected');

                $('#flavor-icon-modal').addClass('active');
            });
        },

        initDrawerSortable: function() {
            if (!$.fn.sortable) return;

            $('#flavor-drawer-sections-sortable').sortable({
                handle: '.section-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                update: function() {
                    $('#flavor-drawer-sections-sortable .flavor-info-section-item').each(function(index) {
                        $(this).find('.flavor-drawer-order').val(index);
                    });
                }
            });
        },

        guessIconFromLabel: function(label, url) {
            var text = ((label || '') + ' ' + (url || '')).toLowerCase();
            if (text.includes('home') || text.includes('inicio') || text === '/') return 'home';
            if (text.includes('chat')) return 'chat_bubble';
            if (text.includes('reserv') || text.includes('book') || text.includes('cita')) return 'calendar_today';
            if (text.includes('ticket')) return 'confirmation_number';
            if (text.includes('contact') || text.includes('telefono') || text.includes('phone')) return 'phone';
            if (text.includes('ubic') || text.includes('map') || text.includes('location')) return 'location_on';
            if (text.includes('blog') || text.includes('news') || text.includes('noticias')) return 'announcement';
            if (text.includes('store') || text.includes('tienda')) return 'store';
            if (text.includes('shop') || text.includes('cart') || text.includes('compra')) return 'shopping_cart';
            if (text.includes('event') || text.includes('evento')) return 'event';
            if (text.includes('menu') || text.includes('carta')) return 'menu_book';
            if (text.includes('about') || text.includes('info')) return 'info';
            return 'public';
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
        },

        bindMockupDrawer: function() {
            var $mockupDrawer = $('#mockup-drawer');
            if (!$mockupDrawer.length) return;

            $('#mockup-app-bar .mockup-hamburger').on('click', function(e) {
                e.preventDefault();
                $mockupDrawer.toggleClass('is-open');
                $(this).toggleClass('is-open');
            });

            $mockupDrawer.on('click', '.mockup-drawer-backdrop', function() {
                $mockupDrawer.removeClass('is-open');
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
            },
            empresarial: {
                tabs: [
                    { id: 'info', label: 'Info', icon: 'info', enabled: true },
                    { id: 'chat', label: 'Chat', icon: 'chat_bubble', enabled: true },
                    { id: 'reservations', label: 'Agenda', icon: 'event', enabled: true },
                    { id: 'my_tickets', label: 'Gestion', icon: 'receipt', enabled: true },
                ],
                modules: ['empresarial', 'clientes', 'facturas', 'fichaje_empleados', 'advertising', 'eventos', 'foros', 'participacion', 'red_social'],
                primaryColor: '#1E4B5B'
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
            // Caso especial: preset "recomendado" carga config del servidor
            if (nombrePreset === 'recomendado') {
                this.cargarPresetRecomendado();
                return;
            }

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
        },

        cargarPresetRecomendado: function() {
            var self = this;
            var $btn = $('.flavor-preset-btn[data-preset="recomendado"]');
            $btn.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: flavorAppsConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_recommended_config',
                    nonce: flavorAppsConfig.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var preset = {
                            tabs: response.data.tabs || [],
                            modules: response.data.modules || [],
                            moduleDescriptions: response.data.module_descriptions || {},
                            primaryColor: response.data.primary_color || '#4CAF50'
                        };

                        // Aplicar tabs
                        if (preset.tabs.length > 0) {
                            // Primero desactivar todas las tabs
                            $('#flavor-tabs-sortable .flavor-tab-item').each(function() {
                                $(this).find('.flavor-tab-toggle').prop('checked', false);
                                $(this).addClass('disabled');
                            });

                            // Activar las tabs recomendadas
                            preset.tabs.forEach(function(tabConfig) {
                                var $tabItem = $('#flavor-tabs-sortable .flavor-tab-item[data-tab-id="' + tabConfig.id + '"]');
                                if ($tabItem.length) {
                                    $tabItem.find('.flavor-tab-toggle').prop('checked', tabConfig.enabled);
                                    $tabItem.find('.flavor-tab-label-input').val(tabConfig.label);
                                    $tabItem.find('.flavor-tab-icon-value').val(tabConfig.icon);
                                    $tabItem.find('.flavor-tab-icon-btn .material-icons').text(tabConfig.icon);
                                    if (tabConfig.enabled) {
                                        $tabItem.removeClass('disabled');
                                    }
                                }
                            });

                            moduloEditorTabs.actualizarMockupTabs();
                        }

                        // Construir mensaje con descripciones de módulos
                        var enabledTabs = preset.tabs.filter(function(t) { return t.enabled; });
                        var tabsInfo = enabledTabs.map(function(t) {
                            var desc = t.description || preset.moduleDescriptions[t.id] || '';
                            return '• ' + t.label + (desc ? ': ' + desc : '');
                        }).join('\n');

                        var extraModulesCount = preset.tabs.filter(function(t) { return !t.enabled; }).length;
                        var extraInfo = extraModulesCount > 0
                            ? '\n\n(+' + extraModulesCount + ' módulos disponibles en menú hamburguesa)'
                            : '';

                        alert('✅ Configuración recomendada aplicada\n\nTabs activos (' + enabledTabs.length + '/5):\n' + tabsInfo + extraInfo);

                        // Quitar el badge después de aplicar
                        $btn.find('.flavor-preset-badge').fadeOut();
                    } else {
                        alert('Error al cargar configuración recomendada');
                    }
                },
                error: function() {
                    alert('Error de conexión');
                },
                complete: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
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
    // MÓDULO: Editor de Drawer (Menú Hamburguesa)
    // ========================================
    var moduloEditorDrawer = {
        init: function() {
            this.bindAddDrawerItem();
            this.bindAddDrawerFromMenu();
            this.bindAddAllDrawerItems();
            this.bindRemoveDrawerItem();
            this.initSortable();
        },

        initSortable: function() {
            if (!$.fn.sortable) return;

            // Usar el ID correcto según el PHP: flavor-drawer-sections-sortable
            $('#flavor-drawer-sections-sortable').sortable({
                handle: '.section-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                update: function() {
                    moduloEditorDrawer.actualizarOrdenDrawer();
                }
            });
        },

        bindAddDrawerItem: function() {
            $('#flavor-add-drawer-item').on('click', function() {
                moduloEditorDrawer.addDrawerItem('', '', 'public');
            });
        },

        bindAddDrawerFromMenu: function() {
            $('#flavor-add-drawer-from-menu').on('click', function() {
                var $select = $('#flavor-drawer-section-select');
                var url = $select.val();
                if (!url) {
                    alert('Selecciona una sección del menú');
                    return;
                }

                var $selectedOption = $select.find('option:selected');
                var title = $selectedOption.data('title') || $selectedOption.text();
                var icon = moduloEditorDrawer.guessIconFromLabel(title, url);

                moduloEditorDrawer.addDrawerItem(title, url, icon);

                // Limpiar selección
                $select.val('');
            });
        },

        bindAddAllDrawerItems: function() {
            $('#flavor-add-all-drawer-items').on('click', function() {
                var $select = $('#flavor-drawer-section-select');
                var addedCount = 0;

                $select.find('option').each(function() {
                    var url = $(this).val();
                    if (!url) return; // Saltar la opción vacía

                    var title = $(this).data('title') || $(this).text();
                    var icon = moduloEditorDrawer.guessIconFromLabel(title, url);

                    // Solo añadir si no existe ya
                    if (!moduloEditorDrawer.drawerItemExists(title, url)) {
                        moduloEditorDrawer.addDrawerItem(title, url, icon, true);
                        addedCount++;
                    }
                });

                if (addedCount > 0) {
                    alert('Se añadieron ' + addedCount + ' secciones al menú hamburguesa');
                } else {
                    alert('Todas las secciones del menú ya están añadidas');
                }
            });
        },

        bindRemoveDrawerItem: function() {
            $(document).on('click', '.flavor-drawer-remove', function() {
                var $item = $(this).closest('.flavor-drawer-item, .flavor-info-section-item');
                $item.fadeOut(200, function() {
                    $(this).remove();
                    moduloEditorDrawer.actualizarOrdenDrawer();
                });
            });
        },

        /**
         * Verifica si ya existe un item con el mismo título o URL
         */
        drawerItemExists: function(title, url) {
            var exists = false;
            $('#flavor-drawer-sections-sortable .flavor-drawer-item, #flavor-drawer-sections-sortable .flavor-info-section-item').each(function() {
                var existingTitle = $(this).find('.flavor-drawer-title-value').val() || $(this).find('.section-label').text();
                var existingUrl = $(this).find('.flavor-drawer-url-value').val() || $(this).data('drawer-url');

                if ((title && existingTitle === title) || (url && existingUrl === url)) {
                    exists = true;
                    return false;
                }
            });
            return exists;
        },

        /**
         * Añade un nuevo item al drawer
         * Usa la misma estructura HTML que el PHP para mantener consistencia
         */
        addDrawerItem: function(title, url, icon, skipAnimation) {
            var $list = $('#flavor-drawer-sections-sortable');
            var index = $list.find('.flavor-info-section-item, .flavor-drawer-item').length;
            var safeTitle = title || 'Nueva sección';
            var safeUrl = url || '';
            var safeIcon = icon || 'public';

            // Detectar tipo de contenido
            var contentType = 'page';
            var contentRef = '';
            if (safeUrl) {
                contentRef = moduloEditorDrawer.extractPageSlugFromUrl(safeUrl);
            }

            // Usar la misma estructura que el PHP (flavor-info-section-item flavor-drawer-item)
            var html = ''
                + '<li class="flavor-info-section-item flavor-drawer-item" data-drawer-url="' + safeUrl + '">'
                +   '<span class="section-drag-handle dashicons dashicons-menu"></span>'
                +   '<label class="flavor-toggle-switch">'
                +     '<input type="hidden" name="flavor_apps_config[drawer_items][' + index + '][enabled]" value="0">'
                +     '<input type="checkbox" name="flavor_apps_config[drawer_items][' + index + '][enabled]" value="1" checked>'
                +     '<span class="flavor-toggle-slider"></span>'
                +   '</label>'
                +   '<button type="button" class="flavor-tab-icon-btn flavor-drawer-icon-btn" data-drawer-index="' + index + '">'
                +     '<span class="material-icons">' + safeIcon + '</span>'
                +   '</button>'
                +   '<span class="section-label">' + safeTitle + '</span>'
                // Selector de tipo de contenido
                +   '<select name="flavor_apps_config[drawer_items][' + index + '][content_type]" class="flavor-drawer-content-type">'
                +     '<option value="native_screen">Pantalla nativa</option>'
                +     '<option value="page"' + (contentType === 'page' ? ' selected' : '') + '>Página</option>'
                +     '<option value="cpt">Contenido (CPT)</option>'
                +     '<option value="module">Módulo</option>'
                +   '</select>'
                // Selector de pantalla nativa
                +   '<select name="flavor_apps_config[drawer_items][' + index + '][content_ref]" class="flavor-drawer-content-ref flavor-drawer-native-screen" style="display:none;">'
                +     '<option value="info">Info</option>'
                +     '<option value="chat">Chat</option>'
                +     '<option value="reservations">Reservas</option>'
                +     '<option value="my_tickets">Mis Tickets</option>'
                +     '<option value="profile">Perfil</option>'
                +     '<option value="notifications">Notificaciones</option>'
                +     '<option value="settings">Configuración</option>'
                +   '</select>'
                // Selector de página
                +   '<select name="flavor_apps_config[drawer_items][' + index + '][content_ref_page]" class="flavor-drawer-content-ref flavor-drawer-page"' + (contentType !== 'page' ? ' style="display:none;"' : '') + '>'
                +     '<option value="">Seleccionar página...</option>'
                +     moduloEditorDrawer.getPageOptionsHtml(contentRef)
                +   '</select>'
                // Selector de CPT
                +   '<select name="flavor_apps_config[drawer_items][' + index + '][content_ref_cpt]" class="flavor-drawer-content-ref flavor-drawer-cpt" style="display:none;">'
                +     '<option value="">Seleccionar tipo...</option>'
                +     moduloEditorDrawer.getCptOptionsHtml()
                +   '</select>'
                // Selector de módulo
                +   '<select name="flavor_apps_config[drawer_items][' + index + '][content_ref_module]" class="flavor-drawer-content-ref flavor-drawer-module" style="display:none;">'
                +     '<option value="">Seleccionar módulo...</option>'
                +     moduloEditorDrawer.getModuleOptionsHtml()
                +   '</select>'
                +   '<button type="button" class="button-link-delete flavor-drawer-remove" title="Eliminar">'
                +     '<span class="dashicons dashicons-trash"></span>'
                +   '</button>'
                // Hidden inputs
                +   '<input type="hidden" name="flavor_apps_config[drawer_items][' + index + '][title]" value="' + safeTitle + '" class="flavor-drawer-title-value">'
                +   '<input type="hidden" name="flavor_apps_config[drawer_items][' + index + '][url]" value="' + safeUrl + '" class="flavor-drawer-url-value">'
                +   '<input type="hidden" name="flavor_apps_config[drawer_items][' + index + '][icon]" value="' + safeIcon + '" class="flavor-drawer-icon-value">'
                +   '<input type="hidden" name="flavor_apps_config[drawer_items][' + index + '][order]" value="' + index + '" class="flavor-drawer-order">'
                + '</li>';

            if (skipAnimation) {
                $list.append(html);
            } else {
                var $newItem = $(html).hide();
                $list.append($newItem);
                $newItem.slideDown(200);
            }

            moduloEditorDrawer.actualizarOrdenDrawer();
        },

        /**
         * Extrae el slug de página de una URL
         */
        extractPageSlugFromUrl: function(url) {
            if (!url) return '';
            try {
                var urlObj = new URL(url, window.location.origin);
                var pathname = urlObj.pathname.replace(/^\/|\/$/g, '');
                if (!pathname) return '';
                if (pathname.includes('wp-admin') || pathname.includes('wp-json')) return '';
                var segments = pathname.split('/');
                return segments[segments.length - 1] || '';
            } catch (e) {
                return '';
            }
        },

        /**
         * Genera el HTML de opciones de páginas existentes
         */
        getPageOptionsHtml: function(selectedSlug) {
            var html = '';
            var $existingSelect = $('.flavor-drawer-page').first();
            if ($existingSelect.length) {
                $existingSelect.find('option').each(function() {
                    var val = $(this).val();
                    if (!val) return;
                    var text = $(this).text();
                    var selected = (val === selectedSlug) ? ' selected' : '';
                    html += '<option value="' + val + '"' + selected + '>' + text + '</option>';
                });
            }
            return html;
        },

        /**
         * Genera el HTML de opciones de CPTs disponibles
         */
        getCptOptionsHtml: function() {
            var html = '';
            var $existingSelect = $('.flavor-drawer-cpt').first();
            if ($existingSelect.length) {
                $existingSelect.find('option').each(function() {
                    var val = $(this).val();
                    if (!val) return;
                    var text = $(this).text();
                    html += '<option value="' + val + '">' + text + '</option>';
                });
            }
            return html;
        },

        /**
         * Genera el HTML de opciones de módulos disponibles
         */
        getModuleOptionsHtml: function() {
            var html = '';
            var $existingSelect = $('.flavor-drawer-module').first();
            if ($existingSelect.length) {
                $existingSelect.find('option').each(function() {
                    var val = $(this).val();
                    if (!val) return;
                    var text = $(this).text();
                    html += '<option value="' + val + '">' + text + '</option>';
                });
            }
            return html;
        },

        /**
         * Intenta adivinar el icono apropiado según el título y URL
         */
        guessIconFromLabel: function(label, url) {
            var text = ((label || '') + ' ' + (url || '')).toLowerCase();
            if (text.includes('home') || text.includes('inicio') || text === '/') return 'home';
            if (text.includes('chat')) return 'chat_bubble';
            if (text.includes('reserv') || text.includes('book') || text.includes('cita')) return 'calendar_today';
            if (text.includes('ticket')) return 'confirmation_number';
            if (text.includes('contact') || text.includes('telefono') || text.includes('phone')) return 'phone';
            if (text.includes('ubic') || text.includes('map') || text.includes('location')) return 'location_on';
            if (text.includes('blog') || text.includes('news') || text.includes('noticias')) return 'announcement';
            if (text.includes('store') || text.includes('tienda')) return 'store';
            if (text.includes('shop') || text.includes('cart') || text.includes('compra')) return 'shopping_cart';
            if (text.includes('event') || text.includes('evento')) return 'event';
            if (text.includes('menu') || text.includes('carta')) return 'menu_book';
            if (text.includes('about') || text.includes('info') || text.includes('acerca')) return 'info';
            if (text.includes('portal')) return 'dashboard';
            if (text.includes('perfil') || text.includes('profile') || text.includes('cuenta')) return 'person';
            if (text.includes('config') || text.includes('settings') || text.includes('ajuste')) return 'settings';
            if (text.includes('notif')) return 'notifications';
            if (text.includes('ayuda') || text.includes('help') || text.includes('faq')) return 'help';
            return 'public';
        },

        actualizarOrdenDrawer: function() {
            $('#flavor-drawer-sections-sortable .flavor-info-section-item, #flavor-drawer-sections-sortable .flavor-drawer-item').each(function(index) {
                $(this).find('.flavor-drawer-order').val(index);

                // Actualizar índices en los nombres de campos
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[drawer_items\]\[\d+\]/, '[drawer_items][' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }
    };

    // ========================================
    // MÓDULO: Activación de módulos del plugin
    // ========================================
    var moduloActivacionModulos = {
        init: function() {
            this.bindActivateButtons();
        },

        bindActivateButtons: function() {
            $(document).on('click', '.flavor-module-activate-btn', function() {
                var $btn = $(this);
                var moduleId = $btn.data('module-id');
                var isActive = $btn.data('active') === 1 || $btn.data('active') === '1';
                var activate = !isActive;

                if (!moduleId) return;

                $btn.prop('disabled', true);

                $.ajax({
                    url: flavorAppsConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'flavor_toggle_module_activation',
                        nonce: flavorAppsConfig.nonce,
                        module_id: moduleId,
                        activate: activate ? 1 : 0
                    },
                    success: function(response) {
                        if (response && response.success) {
                            var $card = $btn.closest('.flavor-module-card');
                            var $status = $card.find('.flavor-module-api-status');

                            $btn.data('active', activate ? 1 : 0);
                            $btn.text(activate ? 'Desactivar módulo' : 'Activar módulo');

                            if (activate) {
                                $status.removeClass('unavailable').addClass('available');
                                $status.html('<span class="dashicons dashicons-yes-alt"></span> ' + (flavorAppsConfig.strings.moduleActivated || 'Módulo activado'));
                            } else {
                                $status.removeClass('unavailable').addClass('available');
                                $status.html('<span class="dashicons dashicons-warning"></span> Disponible (no activo)');
                            }
                        } else {
                            alert((response && response.data && response.data.message) || flavorAppsConfig.strings.moduleActivateError || 'No se pudo actualizar el módulo');
                        }
                    },
                    error: function() {
                        alert(flavorAppsConfig.strings.moduleActivateError || 'No se pudo actualizar el módulo');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
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
            moduloMenuSync.init();
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
    // MÓDULO: Menús dinámicos (secciones web)
    // ========================================
    var moduloMenuSync = {
        init: function() {
            this.bindMenuSourceChange();
            this.bindManualRefresh();
            this.fetchMenuItems($('#web_sections_menu').val() || '');
        },

        bindMenuSourceChange: function() {
            $(document).on('change', '#web_sections_menu', function() {
                var menuSource = $(this).val() || '';
                moduloMenuSync.fetchMenuItems(menuSource);
            });
        },

        bindManualRefresh: function() {
            $(document).on('click', '#flavor-refresh-web-sections', function() {
                var menuSource = $('#web_sections_menu').val() || '';
                moduloMenuSync.fetchMenuItems(menuSource);
            });
        },

        fetchMenuItems: function(menuSource) {
            $.ajax({
                url: flavorAppsConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_menu_items',
                    nonce: flavorAppsConfig.nonce,
                    menu_source: menuSource
                },
                success: function(response) {
                    if (!response.success || !response.data || !response.data.items) {
                        return;
                    }

                    var $select = $('#flavor-web-section-select');
                    $select.find('option').not(':first').remove();

                    response.data.items.forEach(function(item) {
                        var title = item.title || '';
                        var url = item.url || '';
                        if (!url) return;
                        var $option = $('<option></option>')
                            .attr('value', url)
                            .attr('data-title', title)
                            .text(title);
                        $select.append($option);
                    });
                }
            });
        }
    };

    // ========================================
    // MÓDULO: Editor de Info Sections
    // ========================================
    var moduloInfoSections = {
        sectionIconEditando: null,

        init: function() {
            this.initSortable();
            this.bindAddSection();
            this.bindRemoveSection();
            this.bindIconSelector();
            this.bindLabelChanges();
        },

        initSortable: function() {
            if (!$.fn.sortable) return;

            $('#flavor-info-sections-sortable').sortable({
                handle: '.section-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                update: function() {
                    moduloInfoSections.actualizarOrdenSections();
                }
            });
        },

        bindAddSection: function() {
            $('#flavor-add-info-section').on('click', function() {
                moduloInfoSections.addCustomSection();
            });
        },

        bindRemoveSection: function() {
            $(document).on('click', '.flavor-section-remove', function() {
                if (confirm(flavorAppsConfig.strings.confirmDelete || '¿Estás seguro de eliminar esta sección?')) {
                    $(this).closest('.flavor-info-section-item').remove();
                    moduloInfoSections.actualizarOrdenSections();
                }
            });
        },

        bindIconSelector: function() {
            // Abrir modal al click en icono de sección
            $(document).on('click', '.flavor-section-icon-btn', function() {
                moduloInfoSections.sectionIconEditando = $(this);
                var iconoActual = $(this).find('.material-icons').text();

                $('.flavor-icon-option').removeClass('selected');
                $('.flavor-icon-option[data-icon="' + iconoActual + '"]').addClass('selected');

                $('#flavor-icon-modal').addClass('active');
            });

            // Seleccionar icono para sección
            $(document).on('click', '.flavor-icon-option', function() {
                var iconoSeleccionado = $(this).data('icon');

                if (moduloInfoSections.sectionIconEditando) {
                    // Actualizar botón de la sección
                    moduloInfoSections.sectionIconEditando.find('.material-icons').text(iconoSeleccionado);

                    // Actualizar hidden input
                    var $sectionItem = moduloInfoSections.sectionIconEditando.closest('.flavor-info-section-item');
                    if ($sectionItem.length) {
                        $sectionItem.find('.flavor-section-icon-value').val(iconoSeleccionado);
                    }

                    moduloInfoSections.sectionIconEditando = null;
                }

                // El modal se cierra por el handler existente en moduloEditorTabs
            });
        },

        bindLabelChanges: function() {
            $(document).on('input', '.flavor-section-label-input', function() {
                // Podríamos agregar validación o feedback aquí si es necesario
            });
        },

        addCustomSection: function() {
            var $list = $('#flavor-info-sections-sortable');
            var timestamp = Date.now();
            var sectionId = 'custom_' + timestamp;
            var sectionIndex = $list.find('.flavor-info-section-item').length;

            var html = ''
                + '<li class="flavor-info-section-item" data-section-id="' + sectionId + '" data-section-type="custom">'
                +   '<span class="section-drag-handle dashicons dashicons-menu"></span>'
                +   '<label class="flavor-toggle-switch">'
                +     '<input type="hidden" name="flavor_apps_config[info_sections][' + sectionId + '][enabled]" value="0">'
                +     '<input type="checkbox" name="flavor_apps_config[info_sections][' + sectionId + '][enabled]" value="1" checked>'
                +     '<span class="flavor-toggle-slider"></span>'
                +   '</label>'
                +   '<button type="button" class="flavor-section-icon-btn" data-section-id="' + sectionId + '">'
                +     '<span class="material-icons">article</span>'
                +   '</button>'
                +   '<input type="text" name="flavor_apps_config[info_sections][' + sectionId + '][label]" value="Nueva Sección" class="flavor-section-label-input" placeholder="Título de la sección">'
                +   '<button type="button" class="button-link-delete flavor-section-remove">Eliminar</button>'
                +   '<input type="hidden" name="flavor_apps_config[info_sections][' + sectionId + '][icon]" value="article" class="flavor-section-icon-value">'
                +   '<input type="hidden" name="flavor_apps_config[info_sections][' + sectionId + '][order]" value="' + sectionIndex + '" class="flavor-section-order">'
                +   '<input type="hidden" name="flavor_apps_config[info_sections][' + sectionId + '][type]" value="custom">'
                + '</li>';

            $list.append(html);

            // Focus en el input de label
            $list.find('.flavor-info-section-item:last .flavor-section-label-input').focus().select();

            moduloInfoSections.actualizarOrdenSections();
        },

        actualizarOrdenSections: function() {
            $('#flavor-info-sections-sortable .flavor-info-section-item').each(function(index) {
                $(this).find('.flavor-section-order').val(index);
            });
        }
    };

    // ========================================
    // MÓDULO: Copiar al Portapapeles
    // ========================================
    var moduloCopiarPortapapeles = {
        init: function() {
            this.bindCopyButtons();
        },

        bindCopyButtons: function() {
            $(document).on('click', '.flavor-copy-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var targetId = $btn.data('copy-target');
                var $target = $('#' + targetId);

                if ($target.length) {
                    moduloCopiarPortapapeles.copyToClipboard($target.val(), $btn);
                }
            });
        },

        copyToClipboard: function(text, $btn) {
            var self = this;

            // Usar Clipboard API si está disponible
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    self.showCopiedFeedback($btn);
                }).catch(function() {
                    self.fallbackCopy(text, $btn);
                });
            } else {
                self.fallbackCopy(text, $btn);
            }
        },

        fallbackCopy: function(text, $btn) {
            var self = this;
            var $temp = $('<textarea>');
            $temp.val(text).css({
                position: 'fixed',
                left: '-9999px'
            }).appendTo('body').select();

            try {
                document.execCommand('copy');
                self.showCopiedFeedback($btn);
            } catch (err) {
                console.error('Error al copiar:', err);
            }

            $temp.remove();
        },

        showCopiedFeedback: function($btn) {
            var $text = $btn.find('.flavor-copy-text');
            var originalText = $text.text();
            var $icon = $btn.find('.dashicons');

            $btn.addClass('copied');
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
            $text.text(flavorAppsConfig.strings.copied || '¡Copiado!');

            setTimeout(function() {
                $btn.removeClass('copied');
                $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
                $text.text(originalText);
            }, 2000);
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
        moduloActivacionModulos.init();
        moduloEditorDrawer.init();
        moduloCopiarPortapapeles.init();
    });

})(jQuery);
