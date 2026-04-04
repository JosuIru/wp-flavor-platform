/**
 * Visual Builder Pro - Accessibility & UX Improvements
 * Mejoras de accesibilidad, navegación por teclado, ARIA y UX
 *
 * @package Flavor_Chat_IA
 * @since 3.5.1
 */

(function() {
    'use strict';

    // ============================================
    // CONFIRM DIALOG PERSONALIZADO
    // ============================================
    window.vbpConfirm = {
        dialog: null,
        resolvePromise: null,

        init: function() {
            if (this.dialog) return;

            var dialogHtml = '<div class="vbp-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="vbp-confirm-title">' +
                '<div class="vbp-confirm-dialog-content">' +
                '<div class="vbp-confirm-dialog-icon">⚠️</div>' +
                '<h3 class="vbp-confirm-dialog-title" id="vbp-confirm-title"></h3>' +
                '<p class="vbp-confirm-dialog-message"></p>' +
                '<div class="vbp-confirm-dialog-actions">' +
                '<button class="vbp-confirm-dialog-btn vbp-confirm-dialog-btn--cancel" type="button">Cancelar</button>' +
                '<button class="vbp-confirm-dialog-btn vbp-confirm-dialog-btn--confirm" type="button">Confirmar</button>' +
                '</div></div></div>';

            var container = document.createElement('div');
            container.innerHTML = dialogHtml;
            this.dialog = container.firstChild;
            document.body.appendChild(this.dialog);

            // Event listeners
            var self = this;
            this.dialog.querySelector('.vbp-confirm-dialog-btn--cancel').addEventListener('click', function() {
                self.close(false);
            });
            this.dialog.querySelector('.vbp-confirm-dialog-btn--confirm').addEventListener('click', function() {
                self.close(true);
            });

            // Cerrar con Escape
            this.dialog.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.close(false);
                }
            });

            // Cerrar al hacer clic fuera
            this.dialog.addEventListener('click', function(e) {
                if (e.target === self.dialog) {
                    self.close(false);
                }
            });
        },

        show: function(options) {
            this.init();

            var opts = Object.assign({
                title: 'Confirmar acción',
                message: '¿Estás seguro de que deseas continuar?',
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                icon: '⚠️',
                danger: false
            }, options || {});

            this.dialog.querySelector('.vbp-confirm-dialog-icon').textContent = opts.icon;
            this.dialog.querySelector('.vbp-confirm-dialog-title').textContent = opts.title;
            this.dialog.querySelector('.vbp-confirm-dialog-message').textContent = opts.message;
            this.dialog.querySelector('.vbp-confirm-dialog-btn--confirm').textContent = opts.confirmText;
            this.dialog.querySelector('.vbp-confirm-dialog-btn--cancel').textContent = opts.cancelText;

            var confirmBtn = this.dialog.querySelector('.vbp-confirm-dialog-btn--confirm');
            if (opts.danger) {
                confirmBtn.style.background = 'var(--vbp-danger-color, #ef4444)';
            } else {
                confirmBtn.style.background = 'var(--vbp-accent-color, #6366f1)';
            }

            this.dialog.classList.add('is-open');
            this.dialog.querySelector('.vbp-confirm-dialog-btn--cancel').focus();

            var self = this;
            return new Promise(function(resolve) {
                self.resolvePromise = resolve;
            });
        },

        close: function(result) {
            this.dialog.classList.remove('is-open');
            if (this.resolvePromise) {
                this.resolvePromise(result);
                this.resolvePromise = null;
            }
        }
    };

    // ============================================
    // KEYBOARD NAVIGATION
    // ============================================
    window.vbpKeyboardNav = {
        initialized: false,

        init: function() {
            if (!this.initialized) {
                this._boundGlobalKeydown = this.handleGlobalKeydown.bind(this);
                document.addEventListener('keydown', this._boundGlobalKeydown);
                this.initialized = true;
            }
            this.initBlocksList();
            this.initLayersList();
            this.initInspectorTabs();
        },

        handleGlobalKeydown: function(e) {
            // Escape cierra dropdowns y modales
            if (e.key === 'Escape') {
                this.closeAllDropdowns();
            }
        },

        closeAllDropdowns: function() {
            document.querySelectorAll('.vbp-dropdown.is-open, .vbp-color-picker-dropdown.is-open').forEach(function(el) {
                el.classList.remove('is-open');
            });
        },

        initBlocksList: function() {
            var blocksList = document.querySelector('.vbp-blocks-list');
            if (!blocksList || blocksList.dataset.vbpKeyboardBound === 'true') return;

            blocksList.addEventListener('keydown', function(e) {
                var currentItem = document.activeElement;
                if (!currentItem.classList.contains('vbp-block-item')) return;

                var items = Array.from(blocksList.querySelectorAll('.vbp-block-item:not([hidden])'));
                var currentIndex = items.indexOf(currentItem);

                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex < items.length - 1) {
                            items[currentIndex + 1].focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            items[currentIndex - 1].focus();
                        }
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        currentItem.click();
                        break;
                }
            });

            // Hacer items focusables
            blocksList.querySelectorAll('.vbp-block-item').forEach(function(item) {
                if (!item.hasAttribute('tabindex')) {
                    item.setAttribute('tabindex', '0');
                }
            });

            blocksList.dataset.vbpKeyboardBound = 'true';
        },

        initLayersList: function() {
            var layersList = document.querySelector('.vbp-layers-list');
            if (!layersList || layersList.dataset.vbpKeyboardBound === 'true') return;

            layersList.addEventListener('keydown', function(e) {
                var currentItem = document.activeElement;
                if (!currentItem.classList.contains('vbp-layer-item')) return;

                var items = Array.from(layersList.querySelectorAll('.vbp-layer-item'));
                var currentIndex = items.indexOf(currentItem);

                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        if (currentIndex < items.length - 1) {
                            items[currentIndex + 1].focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (currentIndex > 0) {
                            items[currentIndex - 1].focus();
                        }
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        currentItem.click();
                        break;
                    case 'Delete':
                    case 'Backspace':
                        e.preventDefault();
                        var deleteBtn = currentItem.querySelector('.vbp-layer-control[data-action="delete"]');
                        if (deleteBtn) deleteBtn.click();
                        break;
                }
            });

            // Hacer items focusables
            layersList.querySelectorAll('.vbp-layer-item').forEach(function(item) {
                if (!item.hasAttribute('tabindex')) {
                    item.setAttribute('tabindex', '0');
                }
            });

            layersList.dataset.vbpKeyboardBound = 'true';
        },

        initInspectorTabs: function() {
            var tabsContainer = document.querySelector('.vbp-inspector-tabs');
            if (!tabsContainer || tabsContainer.dataset.vbpKeyboardBound === 'true') return;

            tabsContainer.addEventListener('keydown', function(e) {
                var currentTab = document.activeElement;
                if (!currentTab.classList.contains('vbp-inspector-tab')) return;

                var tabs = Array.from(tabsContainer.querySelectorAll('.vbp-inspector-tab'));
                var currentIndex = tabs.indexOf(currentTab);

                switch(e.key) {
                    case 'ArrowRight':
                        e.preventDefault();
                        var nextIndex = (currentIndex + 1) % tabs.length;
                        tabs[nextIndex].focus();
                        tabs[nextIndex].click();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        var prevIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                        tabs[prevIndex].focus();
                        tabs[prevIndex].click();
                        break;
                }
            });

            tabsContainer.dataset.vbpKeyboardBound = 'true';
        }
    };

    // ============================================
    // ARIA LABELS
    // ============================================
    window.vbpAria = {
        init: function() {
            this.addAriaToButtons();
            this.addAriaToInputs();
            this.addAriaToLayers();
            this.addAriaToCategories();
        },

        addAriaToButtons: function() {
            // Botones de control de capas
            document.querySelectorAll('.vbp-layer-control').forEach(function(btn) {
                var action = btn.dataset.action;
                var labels = {
                    'visibility': 'Alternar visibilidad',
                    'lock': 'Bloquear/Desbloquear',
                    'delete': 'Eliminar elemento',
                    'duplicate': 'Duplicar elemento',
                    'move-up': 'Mover arriba',
                    'move-down': 'Mover abajo'
                };
                if (labels[action] && !btn.hasAttribute('aria-label')) {
                    btn.setAttribute('aria-label', labels[action]);
                }
            });

            // Botones de toolbar
            document.querySelectorAll('.vbp-toolbar-btn').forEach(function(btn) {
                if (!btn.hasAttribute('aria-label') && btn.title) {
                    btn.setAttribute('aria-label', btn.title);
                }
            });
        },

        addAriaToInputs: function() {
            document.querySelectorAll('.vbp-field-input, .vbp-field-select, .vbp-field-textarea').forEach(function(input) {
                var label = input.closest('.vbp-field-group')?.querySelector('.vbp-field-label');
                if (label && !input.hasAttribute('aria-label')) {
                    var labelText = label.textContent.trim();
                    input.setAttribute('aria-label', labelText);
                }
            });
        },

        addAriaToLayers: function() {
            var layersList = document.querySelector('.vbp-layers-list');
            if (layersList) {
                layersList.setAttribute('role', 'listbox');
                layersList.setAttribute('aria-label', 'Lista de capas');

                layersList.querySelectorAll('.vbp-layer-item').forEach(function(item) {
                    item.setAttribute('role', 'option');
                    if (item.classList.contains('selected')) {
                        item.setAttribute('aria-selected', 'true');
                    }
                });
            }
        },

        addAriaToCategories: function() {
            document.querySelectorAll('.vbp-category-header').forEach(function(header) {
                header.setAttribute('role', 'button');
                header.setAttribute('aria-expanded', header.classList.contains('open') ? 'true' : 'false');

                var categoryName = header.querySelector('.vbp-category-name');
                if (categoryName) {
                    header.setAttribute('aria-label', 'Categoría: ' + categoryName.textContent.trim());
                }
            });
        }
    };

    // ============================================
    // SEARCH RESULTS COUNTER
    // ============================================
    window.vbpSearchEnhanced = {
        initialized: false,

        init: function() {
            var searchInput = document.querySelector('.vbp-search-input');
            if (!searchInput || this.initialized) return;

            var self = this;
            searchInput.addEventListener('input', function() {
                self.updateResultsCount();
            });

            // Crear contenedor de resultados si no existe
            if (!document.querySelector('.vbp-search-results')) {
                var resultsContainer = document.createElement('div');
                resultsContainer.className = 'vbp-search-results';
                resultsContainer.style.display = 'none';
                resultsContainer.innerHTML = '<span><span class="vbp-search-results-count">0</span> resultados</span>' +
                    '<button class="vbp-search-clear" type="button">Limpiar</button>';

                var searchContainer = searchInput.closest('.vbp-blocks-search');
                if (searchContainer) {
                    searchContainer.parentNode.insertBefore(resultsContainer, searchContainer.nextSibling);

                    resultsContainer.querySelector('.vbp-search-clear').addEventListener('click', function() {
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                        searchInput.focus();
                    });
                }
            }

            this.initialized = true;
        },

        updateResultsCount: function() {
            var searchInput = document.querySelector('.vbp-search-input');
            var resultsContainer = document.querySelector('.vbp-search-results');
            if (!searchInput || !resultsContainer) return;

            var query = searchInput.value.trim();
            if (query.length > 0) {
                var visibleBlocks = document.querySelectorAll('.vbp-block-item:not([style*="display: none"])');
                resultsContainer.querySelector('.vbp-search-results-count').textContent = visibleBlocks.length;
                resultsContainer.style.display = 'flex';
            } else {
                resultsContainer.style.display = 'none';
            }
        }
    };

    // ============================================
    // COLOR PICKER ENHANCEMENTS
    // ============================================
    window.vbpColorEnhanced = {
        recentColors: [],
        maxRecent: 8,
        initialized: false,

        init: function() {
            if (this.initialized) return;
            this.loadRecentColors();
            this.enhanceColorPickers();
            this.initialized = true;
        },

        loadRecentColors: function() {
            try {
                var stored = localStorage.getItem('vbp_recent_colors');
                if (stored) {
                    this.recentColors = JSON.parse(stored);
                }
            } catch (e) {
                this.recentColors = [];
            }
        },

        saveRecentColors: function() {
            try {
                localStorage.setItem('vbp_recent_colors', JSON.stringify(this.recentColors));
            } catch (e) {
                // Ignorar errores de localStorage
            }
        },

        addRecentColor: function(color) {
            if (!color || color === 'transparent') return;

            // Remover si ya existe
            var index = this.recentColors.indexOf(color);
            if (index > -1) {
                this.recentColors.splice(index, 1);
            }

            // Añadir al principio
            this.recentColors.unshift(color);

            // Mantener solo los últimos N
            if (this.recentColors.length > this.maxRecent) {
                this.recentColors = this.recentColors.slice(0, this.maxRecent);
            }

            this.saveRecentColors();
            this.updateRecentColorsUI();
        },

        enhanceColorPickers: function() {
            var self = this;

            document.querySelectorAll('.vbp-color-native').forEach(function(picker) {
                if (picker.dataset.vbpColorEnhanced === 'true') return;
                picker.addEventListener('change', function() {
                    self.addRecentColor(this.value);
                });
                picker.dataset.vbpColorEnhanced = 'true';
            });
        },

        updateRecentColorsUI: function() {
            var container = document.querySelector('.vbp-color-recent');
            if (!container) return;

            var itemsHtml = this.recentColors.map(function(color) {
                return '<button class="vbp-color-recent-item" style="background: ' + color + ';" data-color="' + color + '" aria-label="Color reciente: ' + color + '"></button>';
            }).join('');

            container.innerHTML = '<span class="vbp-color-recent-label">Recientes</span>' + itemsHtml;

            container.querySelectorAll('.vbp-color-recent-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var colorInput = container.closest('.vbp-color-picker-wrapper')?.querySelector('.vbp-color-native');
                    if (colorInput) {
                        colorInput.value = this.dataset.color;
                        colorInput.dispatchEvent(new Event('change'));
                    }
                });
            });
        }
    };

    // ============================================
    // BREAKPOINT INDICATOR
    // ============================================
    window.vbpBreakpointIndicator = {
        currentBreakpoint: 'desktop',
        indicator: null,
        initialized: false,
        resizeObserver: null,

        init: function() {
            if (this.initialized) return;
            this.createIndicator();
            this.bindEvents();
            this.initialized = true;
        },

        createIndicator: function() {
            var toolbar = document.querySelector('.vbp-toolbar-center, .vbp-statusbar');
            if (!toolbar || document.querySelector('.vbp-breakpoint-indicator')) return;

            this.indicator = document.createElement('div');
            this.indicator.className = 'vbp-breakpoint-indicator';
            this.indicator.setAttribute('data-breakpoint', 'desktop');
            this.indicator.innerHTML = '<span class="material-icons">computer</span><span>Desktop</span>';

            toolbar.appendChild(this.indicator);
        },

        bindEvents: function() {
            var self = this;

            // Escuchar cambios de breakpoint del store
            document.addEventListener('vbp:breakpoint-change', function(e) {
                self.updateIndicator(e.detail.breakpoint);
            });

            // También observar el canvas por cambios de ancho
            var canvas = document.querySelector('.vbp-canvas-container');
            if (canvas) {
                this.resizeObserver = new ResizeObserver(function(entries) {
                    var width = entries[0].contentRect.width;
                    if (width <= 480) {
                        self.updateIndicator('mobile');
                    } else if (width <= 768) {
                        self.updateIndicator('tablet');
                    } else {
                        self.updateIndicator('desktop');
                    }
                });
                this.resizeObserver.observe(canvas);
            }
        },

        updateIndicator: function(breakpoint) {
            if (!this.indicator || this.currentBreakpoint === breakpoint) return;

            this.currentBreakpoint = breakpoint;
            this.indicator.setAttribute('data-breakpoint', breakpoint);

            var icons = {
                desktop: 'computer',
                tablet: 'tablet',
                mobile: 'smartphone'
            };

            var labels = {
                desktop: 'Desktop',
                tablet: 'Tablet',
                mobile: 'Mobile'
            };

            this.indicator.innerHTML = '<span class="material-icons">' + icons[breakpoint] + '</span><span>' + labels[breakpoint] + '</span>';
        }
    };

    // ============================================
    // PRESET APPLIED FEEDBACK
    // ============================================
    window.vbpPresetFeedback = {
        showApplied: function(elementId) {
            var element = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!element) return;

            element.classList.add('vbp-preset-applied');

            setTimeout(function() {
                element.classList.remove('vbp-preset-applied');
            }, 500);
        }
    };

    // ============================================
    // ZOOM INDICATOR
    // ============================================
    window.vbpZoomIndicator = {
        indicator: null,
        hideTimeout: null,

        init: function() {
            this.createIndicator();
        },

        createIndicator: function() {
            if (document.querySelector('.vbp-zoom-indicator')) return;

            this.indicator = document.createElement('div');
            this.indicator.className = 'vbp-zoom-indicator';
            this.indicator.setAttribute('aria-live', 'polite');
            document.body.appendChild(this.indicator);
        },

        show: function(zoomLevel) {
            if (!this.indicator) return;

            this.indicator.textContent = Math.round(zoomLevel * 100) + '%';
            this.indicator.classList.add('is-visible');

            clearTimeout(this.hideTimeout);
            var self = this;
            this.hideTimeout = setTimeout(function() {
                self.indicator.classList.remove('is-visible');
            }, 1500);
        }
    };

    // ============================================
    // SPACING CONTROL VISUAL
    // ============================================
    window.vbpSpacingControl = {
        createVisual: function(container, values, onChange) {
            var html = '<div class="vbp-spacing-control">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--top" value="' + (values.top || 0) + '" data-side="top" aria-label="Espaciado superior">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--right" value="' + (values.right || 0) + '" data-side="right" aria-label="Espaciado derecho">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--bottom" value="' + (values.bottom || 0) + '" data-side="bottom" aria-label="Espaciado inferior">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--left" value="' + (values.left || 0) + '" data-side="left" aria-label="Espaciado izquierdo">' +
                '<div class="vbp-spacing-box">elem</div>' +
                '<button class="vbp-spacing-link" type="button" aria-label="Vincular todos los valores">' +
                '<span class="material-icons">link</span>Link' +
                '</button></div>';

            container.innerHTML = html;

            var linked = false;
            var linkBtn = container.querySelector('.vbp-spacing-link');
            var inputs = container.querySelectorAll('.vbp-spacing-input');

            linkBtn.addEventListener('click', function() {
                linked = !linked;
                this.classList.toggle('is-linked', linked);
            });

            inputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    var side = this.dataset.side;
                    var value = this.value;

                    if (linked) {
                        inputs.forEach(function(inp) {
                            inp.value = value;
                        });
                        onChange({
                            top: value,
                            right: value,
                            bottom: value,
                            left: value
                        });
                    } else {
                        var newValues = {};
                        inputs.forEach(function(inp) {
                            newValues[inp.dataset.side] = inp.value;
                        });
                        onChange(newValues);
                    }
                });
            });
        }
    };

    // ============================================
    // INIT ALL
    // ============================================
    function initAll() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            if (!window.__vbpAccessibilityDomReadyBound) {
                window.__vbpAccessibilityDomReadyBound = true;
                document.addEventListener('DOMContentLoaded', initAll, { once: true });
            }
            return;
        }

        // Inicializar todos los módulos
        vbpKeyboardNav.init();
        vbpAria.init();
        vbpSearchEnhanced.init();
        vbpColorEnhanced.init();
        vbpBreakpointIndicator.init();
        vbpZoomIndicator.init();

        // Re-init cuando Alpine actualiza el DOM
        if (!window.__vbpAccessibilityAlpineInitBound) {
            window.__vbpAccessibilityAlpineInitBound = true;
            document.addEventListener('alpine:initialized', function() {
                requestAnimationFrame(function() {
                    vbpAria.init();
                    vbpKeyboardNav.initBlocksList();
                    vbpKeyboardNav.initLayersList();
                });
            });
        }

        // Observar cambios en el DOM para re-aplicar ARIA
        if (!window.__vbpAccessibilityObserverBound) {
            window.__vbpAccessibilityObserverBound = true;
            var pendingAriaRefresh = false;
            var observer = new MutationObserver(function(mutations) {
                var needsAriaUpdate = mutations.some(function(m) {
                    return m.addedNodes && m.addedNodes.length > 0;
                });
                if (!needsAriaUpdate || pendingAriaRefresh) {
                    return;
                }

                pendingAriaRefresh = true;
                requestAnimationFrame(function() {
                    pendingAriaRefresh = false;
                    vbpAria.addAriaToButtons();
                    vbpAria.addAriaToLayers();
                    vbpAria.addAriaToCategories();
                });
            });

            var editorContainer = document.querySelector('.vbp-editor-body');
            if (editorContainer) {
                observer.observe(editorContainer, { childList: true, subtree: true });
            }
        }
    }

    // Iniciar
    initAll();

})();
