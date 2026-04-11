/**
 * Visual Builder Pro - Accessibility & UX Improvements
 * Mejoras de accesibilidad, navegación por teclado, ARIA y UX
 *
 * @package Flavor_Chat_IA
 * @since 3.5.1
 *
 * Incluye:
 * - Focus Management (trap, restore, skip links)
 * - Screen Reader Support (live regions, announcements)
 * - Keyboard Navigation (roving tabindex, arrow keys)
 * - ARIA Attributes (roles, labels, states)
 * - Accessibility Audit
 */

(function() {
    'use strict';

    // ============================================
    // SCREEN READER ANNOUNCEMENTS (Live Regions)
    // ============================================
    window.VBPAnnounce = {
        politeRegion: null,
        assertiveRegion: null,
        initialized: false,

        init: function() {
            if (this.initialized) return;

            // Crear live regions para anuncios
            this.politeRegion = this.createLiveRegion('polite');
            this.assertiveRegion = this.createLiveRegion('assertive');

            this.initialized = true;
        },

        createLiveRegion: function(priority) {
            var existingRegion = document.getElementById('vbp-live-region-' + priority);
            if (existingRegion) return existingRegion;

            var region = document.createElement('div');
            region.id = 'vbp-live-region-' + priority;
            region.className = 'vbp-sr-only';
            region.setAttribute('role', 'status');
            region.setAttribute('aria-live', priority);
            region.setAttribute('aria-atomic', 'true');

            document.body.appendChild(region);
            return region;
        },

        /**
         * Anunciar mensaje a lectores de pantalla
         * @param {string} message - Mensaje a anunciar
         * @param {string} priority - 'polite' (default) o 'assertive'
         */
        announce: function(message, priority) {
            this.init();

            priority = priority || 'polite';
            var region = priority === 'assertive' ? this.assertiveRegion : this.politeRegion;

            if (!region) return;

            // Limpiar y re-anunciar para garantizar que se lea
            region.textContent = '';

            // Pequeño delay para asegurar que el cambio se detecte
            var self = this;
            requestAnimationFrame(function() {
                region.textContent = message;

                // Limpiar después de un tiempo
                setTimeout(function() {
                    region.textContent = '';
                }, 3000);
            });
        },

        /**
         * Anunciar acciones comunes
         * Usa sistema i18n para traducciones
         */
        elementDeleted: function(elementName) {
            var element = elementName || (typeof window.__ === 'function' ? __('element', 'Elemento') : 'Elemento');
            var msg = typeof window._s === 'function' ? _s('elementDeleted', element) : element + ' eliminado';
            this.announce(msg);
        },

        elementCreated: function(elementName) {
            var element = elementName || (typeof window.__ === 'function' ? __('element', 'Elemento') : 'Elemento');
            var msg = element + ' ' + (typeof window.__ === 'function' ? __('created', 'creado') : 'creado');
            this.announce(msg);
        },

        elementSelected: function(count) {
            var msg;
            if (count === 0) {
                msg = typeof window.__ === 'function' ? __('noSelection', 'Sin seleccion') : 'Sin seleccion';
            } else if (count === 1) {
                msg = typeof window.__ === 'function' ? __('oneElementSelected', '1 elemento seleccionado') : '1 elemento seleccionado';
            } else {
                msg = typeof window._s === 'function' ? _s('selectedCount', count) : count + ' elementos seleccionados';
            }
            this.announce(msg);
        },

        saved: function() {
            var msg = typeof window.__ === 'function' ? __('saved', 'Guardado exitoso') : 'Guardado exitoso';
            this.announce(msg, 'assertive');
        },

        error: function(message) {
            var prefix = typeof window.__ === 'function' ? __('errorGeneric', 'Error') : 'Error';
            this.announce(prefix + ': ' + message, 'assertive');
        },

        undoAction: function() {
            var msg = typeof window.__ === 'function' ? __('actionUndone', 'Accion deshecha') : 'Accion deshecha';
            this.announce(msg);
        },

        redoAction: function() {
            var msg = typeof window.__ === 'function' ? __('actionRedone', 'Accion rehecha') : 'Accion rehecha';
            this.announce(msg);
        },

        copied: function(count) {
            var num = count || 1;
            var suffix = num > 1 ? 's' : '';
            var msg = num + ' elemento' + suffix + ' copiado' + suffix;
            this.announce(msg);
        },

        pasted: function(count) {
            var num = count || 1;
            var suffix = num > 1 ? 's' : '';
            var msg = num + ' elemento' + suffix + ' pegado' + suffix;
            this.announce(msg);
        },

        modeChanged: function(modeName) {
            var msg = (typeof window.__ === 'function' ? __('mode', 'Modo') : 'Modo') + ' ' + modeName + ' ' + (typeof window.__ === 'function' ? __('activated', 'activado') : 'activado');
            this.announce(msg);
        },

        panelToggled: function(panelName, isOpen) {
            var panel = (typeof window.__ === 'function' ? __('panel', 'Panel') : 'Panel');
            var state = isOpen
                ? (typeof window.__ === 'function' ? __('opened', 'abierto') : 'abierto')
                : (typeof window.__ === 'function' ? __('closed', 'cerrado') : 'cerrado');
            this.announce(panel + ' ' + panelName + ' ' + state);
        }
    };

    // Exponer globalmente
    window.VBP = window.VBP || {};
    window.VBP.announce = function(message, priority) {
        VBPAnnounce.announce(message, priority);
    };

    // ============================================
    // FOCUS MANAGEMENT
    // ============================================
    window.VBPFocusManager = {
        focusStack: [],
        trapContainers: new WeakMap(),

        /**
         * Guardar el elemento actualmente enfocado
         */
        saveFocus: function() {
            var activeElement = document.activeElement;
            if (activeElement && activeElement !== document.body) {
                this.focusStack.push(activeElement);
            }
        },

        /**
         * Restaurar el último focus guardado
         */
        restoreFocus: function() {
            var lastFocusedElement = this.focusStack.pop();
            if (lastFocusedElement && document.contains(lastFocusedElement)) {
                requestAnimationFrame(function() {
                    lastFocusedElement.focus();
                });
            }
        },

        /**
         * Obtener todos los elementos focuseables dentro de un contenedor
         */
        getFocusableElements: function(container) {
            var focusableSelectors = [
                'a[href]',
                'button:not([disabled])',
                'input:not([disabled]):not([type="hidden"])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"])',
                '[contenteditable="true"]'
            ].join(', ');

            var elements = Array.from(container.querySelectorAll(focusableSelectors));

            // Filtrar elementos no visibles
            return elements.filter(function(element) {
                return element.offsetParent !== null &&
                       getComputedStyle(element).visibility !== 'hidden';
            });
        },

        /**
         * Crear focus trap en un contenedor (modal, dialog)
         */
        trapFocus: function(container) {
            if (!container) return;

            var self = this;

            // Guardar el focus actual
            this.saveFocus();

            // Handler de keydown para el trap
            var trapHandler = function(event) {
                if (event.key !== 'Tab') return;

                var focusableElements = self.getFocusableElements(container);
                if (focusableElements.length === 0) return;

                var firstElement = focusableElements[0];
                var lastElement = focusableElements[focusableElements.length - 1];
                var activeElement = document.activeElement;

                if (event.shiftKey) {
                    // Shift + Tab: ir al ultimo si estamos en el primero
                    if (activeElement === firstElement || !container.contains(activeElement)) {
                        event.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    // Tab: ir al primero si estamos en el ultimo
                    if (activeElement === lastElement || !container.contains(activeElement)) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            };

            // Guardar referencia al handler
            this.trapContainers.set(container, trapHandler);

            // Agregar listener
            container.addEventListener('keydown', trapHandler);

            // Enfocar primer elemento focuseable
            var firstFocusable = this.getFocusableElements(container)[0];
            if (firstFocusable) {
                requestAnimationFrame(function() {
                    firstFocusable.focus();
                });
            }
        },

        /**
         * Liberar focus trap
         */
        releaseTrap: function(container) {
            if (!container) return;

            var trapHandler = this.trapContainers.get(container);
            if (trapHandler) {
                container.removeEventListener('keydown', trapHandler);
                this.trapContainers.delete(container);
            }

            // Restaurar focus
            this.restoreFocus();
        },

        /**
         * Mover focus al primer elemento de error
         */
        focusFirstError: function(container) {
            container = container || document;
            var firstError = container.querySelector('[aria-invalid="true"], .vbp-field-error, .vbp-error');
            if (firstError) {
                var input = firstError.querySelector('input, select, textarea') || firstError;
                input.focus();
            }
        },

        /**
         * Focus en el siguiente/anterior elemento en una lista
         */
        moveFocusInList: function(list, currentElement, direction) {
            var items = Array.from(list.querySelectorAll('[tabindex="0"], [tabindex]:not([tabindex="-1"])'));
            if (items.length === 0) return;

            var currentIndex = items.indexOf(currentElement);
            var nextIndex;

            if (direction === 'next') {
                nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
            } else if (direction === 'prev') {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
            } else if (direction === 'first') {
                nextIndex = 0;
            } else if (direction === 'last') {
                nextIndex = items.length - 1;
            }

            if (items[nextIndex]) {
                items[nextIndex].focus();
            }
        }
    };

    // ============================================
    // SKIP LINKS
    // ============================================
    window.VBPSkipLinks = {
        initialized: false,

        init: function() {
            if (this.initialized) return;

            var skipLinksContainer = document.querySelector('.vbp-skip-links');
            if (skipLinksContainer) {
                this.initialized = true;
                return;
            }

            var skipLinksHtml = '<nav class="vbp-skip-links" aria-label="Enlaces de acceso rapido">' +
                '<a href="#vbp-canvas" class="vbp-skip-link">Ir al lienzo</a>' +
                '<a href="#vbp-inspector" class="vbp-skip-link">Ir al inspector</a>' +
                '<a href="#vbp-blocks-panel" class="vbp-skip-link">Ir a bloques</a>' +
                '<a href="#vbp-layers-panel" class="vbp-skip-link">Ir a capas</a>' +
                '</nav>';

            var container = document.createElement('div');
            container.innerHTML = skipLinksHtml;
            var skipLinks = container.firstChild;

            // Insertar al inicio del editor
            var editor = document.querySelector('.vbp-editor, .vbp-editor-body');
            if (editor) {
                editor.insertBefore(skipLinks, editor.firstChild);
            } else {
                document.body.insertBefore(skipLinks, document.body.firstChild);
            }

            // Manejar clicks en skip links
            skipLinks.querySelectorAll('.vbp-skip-link').forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var targetId = this.getAttribute('href').substring(1);
                    var targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        targetElement.setAttribute('tabindex', '-1');
                        targetElement.focus();
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            this.initialized = true;
        }
    };

    // ============================================
    // ROVING TABINDEX
    // ============================================
    window.VBPRovingTabindex = {
        /**
         * Inicializar roving tabindex en un contenedor
         * @param {HTMLElement} container - Contenedor (toolbar, lista)
         * @param {string} itemSelector - Selector de items
         * @param {object} options - Opciones { horizontal: true, loop: true }
         */
        init: function(container, itemSelector, options) {
            if (!container || container.dataset.vbpRovingInit === 'true') return;

            options = Object.assign({
                horizontal: true,
                loop: true,
                activateOnFocus: false
            }, options || {});

            var items = Array.from(container.querySelectorAll(itemSelector));
            if (items.length === 0) return;

            // Establecer tabindex inicial
            items.forEach(function(item, index) {
                item.setAttribute('tabindex', index === 0 ? '0' : '-1');
            });

            // Handler de navegacion
            container.addEventListener('keydown', function(event) {
                var currentItem = document.activeElement;
                if (!items.includes(currentItem)) return;

                var currentIndex = items.indexOf(currentItem);
                var nextIndex = currentIndex;
                var handled = false;

                var prevKey = options.horizontal ? 'ArrowLeft' : 'ArrowUp';
                var nextKey = options.horizontal ? 'ArrowRight' : 'ArrowDown';

                switch (event.key) {
                    case prevKey:
                        handled = true;
                        if (currentIndex > 0) {
                            nextIndex = currentIndex - 1;
                        } else if (options.loop) {
                            nextIndex = items.length - 1;
                        }
                        break;

                    case nextKey:
                        handled = true;
                        if (currentIndex < items.length - 1) {
                            nextIndex = currentIndex + 1;
                        } else if (options.loop) {
                            nextIndex = 0;
                        }
                        break;

                    case 'Home':
                        handled = true;
                        nextIndex = 0;
                        break;

                    case 'End':
                        handled = true;
                        nextIndex = items.length - 1;
                        break;
                }

                if (handled) {
                    event.preventDefault();

                    // Actualizar tabindex
                    items[currentIndex].setAttribute('tabindex', '-1');
                    items[nextIndex].setAttribute('tabindex', '0');
                    items[nextIndex].focus();

                    // Activar si corresponde
                    if (options.activateOnFocus && items[nextIndex].click) {
                        items[nextIndex].click();
                    }
                }
            });

            container.dataset.vbpRovingInit = 'true';
        }
    };

    // ============================================
    // CONFIRM DIALOG PERSONALIZADO (mejorado)
    // ============================================
    window.vbpConfirm = {
        dialog: null,
        resolvePromise: null,

        init: function() {
            if (this.dialog) return;

            var dialogHtml = '<div class="vbp-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="vbp-confirm-title" aria-describedby="vbp-confirm-message">' +
                '<div class="vbp-confirm-dialog-content">' +
                '<div class="vbp-confirm-dialog-icon" aria-hidden="true"></div>' +
                '<h3 class="vbp-confirm-dialog-title" id="vbp-confirm-title"></h3>' +
                '<p class="vbp-confirm-dialog-message" id="vbp-confirm-message"></p>' +
                '<div class="vbp-confirm-dialog-actions">' +
                '<button class="vbp-confirm-dialog-btn vbp-confirm-dialog-btn--cancel" type="button">Cancelar</button>' +
                '<button class="vbp-confirm-dialog-btn vbp-confirm-dialog-btn--confirm" type="button">Confirmar</button>' +
                '</div></div></div>';

            var container = document.createElement('div');
            container.innerHTML = dialogHtml;
            this.dialog = container.firstChild;
            document.body.appendChild(this.dialog);

            var self = this;

            this.dialog.querySelector('.vbp-confirm-dialog-btn--cancel').addEventListener('click', function() {
                self.close(false);
            });

            this.dialog.querySelector('.vbp-confirm-dialog-btn--confirm').addEventListener('click', function() {
                self.close(true);
            });

            // Cerrar con Escape
            this.dialog.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    self.close(false);
                }
            });

            // Cerrar al hacer clic fuera
            this.dialog.addEventListener('click', function(event) {
                if (event.target === self.dialog) {
                    self.close(false);
                }
            });
        },

        show: function(options) {
            this.init();

            var opts = Object.assign({
                title: 'Confirmar accion',
                message: 'Estas seguro de que deseas continuar?',
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                icon: 'warning',
                danger: false
            }, options || {});

            var iconEmojis = {
                'warning': '\u26A0\uFE0F',
                'danger': '\u274C',
                'info': '\u2139\uFE0F',
                'success': '\u2705',
                'question': '\u2753'
            };

            this.dialog.querySelector('.vbp-confirm-dialog-icon').textContent = iconEmojis[opts.icon] || opts.icon;
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

            // Activar focus trap
            VBPFocusManager.trapFocus(this.dialog);

            this.dialog.classList.add('is-open');

            // Anunciar a lectores de pantalla
            VBPAnnounce.announce(opts.title + '. ' + opts.message, 'assertive');

            var self = this;
            return new Promise(function(resolve) {
                self.resolvePromise = resolve;
            });
        },

        close: function(result) {
            this.dialog.classList.remove('is-open');

            // Liberar focus trap
            VBPFocusManager.releaseTrap(this.dialog);

            if (this.resolvePromise) {
                this.resolvePromise(result);
                this.resolvePromise = null;
            }
        }
    };

    // ============================================
    // KEYBOARD NAVIGATION (mejorado)
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
            this.initToolbars();
            this.initCanvasNavigation();
        },

        handleGlobalKeydown: function(event) {
            // Escape cierra dropdowns y modales
            if (event.key === 'Escape') {
                this.closeAllDropdowns();
            }
        },

        closeAllDropdowns: function() {
            document.querySelectorAll('.vbp-dropdown.is-open, .vbp-color-picker-dropdown.is-open').forEach(function(element) {
                element.classList.remove('is-open');
            });
        },

        initBlocksList: function() {
            var blocksList = document.querySelector('.vbp-blocks-list');
            if (!blocksList) return;

            // Usar roving tabindex
            VBPRovingTabindex.init(blocksList, '.vbp-block-item', {
                horizontal: false,
                loop: true
            });

            // Agregar atributos ARIA
            blocksList.setAttribute('role', 'listbox');
            blocksList.setAttribute('aria-label', 'Bloques disponibles');

            blocksList.querySelectorAll('.vbp-block-item').forEach(function(item) {
                item.setAttribute('role', 'option');

                // Enter/Space para insertar
                if (!item.dataset.vbpKeyboardBound) {
                    item.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            this.click();
                        }
                    });
                    item.dataset.vbpKeyboardBound = 'true';
                }
            });
        },

        initLayersList: function() {
            var layersList = document.querySelector('.vbp-layers-list');
            if (!layersList || layersList.dataset.vbpKeyboardBound === 'true') return;

            layersList.setAttribute('role', 'tree');
            layersList.setAttribute('aria-label', 'Arbol de capas');

            layersList.addEventListener('keydown', function(event) {
                var currentItem = document.activeElement;
                if (!currentItem.classList.contains('vbp-layer-item')) return;

                var items = Array.from(layersList.querySelectorAll('.vbp-layer-item'));
                var currentIndex = items.indexOf(currentItem);

                switch(event.key) {
                    case 'ArrowDown':
                        event.preventDefault();
                        if (currentIndex < items.length - 1) {
                            items[currentIndex].setAttribute('tabindex', '-1');
                            items[currentIndex + 1].setAttribute('tabindex', '0');
                            items[currentIndex + 1].focus();
                        }
                        break;

                    case 'ArrowUp':
                        event.preventDefault();
                        if (currentIndex > 0) {
                            items[currentIndex].setAttribute('tabindex', '-1');
                            items[currentIndex - 1].setAttribute('tabindex', '0');
                            items[currentIndex - 1].focus();
                        }
                        break;

                    case 'ArrowRight':
                        event.preventDefault();
                        // Expandir nodo si tiene hijos
                        var expandBtn = currentItem.querySelector('[data-action="expand"], .vbp-layer-expand');
                        if (expandBtn && currentItem.getAttribute('aria-expanded') === 'false') {
                            expandBtn.click();
                            currentItem.setAttribute('aria-expanded', 'true');
                        }
                        break;

                    case 'ArrowLeft':
                        event.preventDefault();
                        // Colapsar nodo o ir al padre
                        if (currentItem.getAttribute('aria-expanded') === 'true') {
                            var collapseBtn = currentItem.querySelector('[data-action="expand"], .vbp-layer-expand');
                            if (collapseBtn) {
                                collapseBtn.click();
                                currentItem.setAttribute('aria-expanded', 'false');
                            }
                        }
                        break;

                    case 'Enter':
                    case ' ':
                        event.preventDefault();
                        currentItem.click();
                        break;

                    case 'Delete':
                    case 'Backspace':
                        event.preventDefault();
                        var deleteBtn = currentItem.querySelector('.vbp-layer-control[data-action="delete"]');
                        if (deleteBtn) deleteBtn.click();
                        break;

                    case 'Home':
                        event.preventDefault();
                        if (items.length > 0) {
                            items[currentIndex].setAttribute('tabindex', '-1');
                            items[0].setAttribute('tabindex', '0');
                            items[0].focus();
                        }
                        break;

                    case 'End':
                        event.preventDefault();
                        if (items.length > 0) {
                            items[currentIndex].setAttribute('tabindex', '-1');
                            items[items.length - 1].setAttribute('tabindex', '0');
                            items[items.length - 1].focus();
                        }
                        break;
                }
            });

            // Configurar items
            layersList.querySelectorAll('.vbp-layer-item').forEach(function(item, index) {
                item.setAttribute('role', 'treeitem');
                item.setAttribute('tabindex', index === 0 ? '0' : '-1');

                // Indicar nivel de anidamiento si aplica
                var depth = item.dataset.depth || 0;
                item.setAttribute('aria-level', parseInt(depth) + 1);

                // Indicar si tiene hijos
                var hasChildren = item.querySelector('.vbp-layer-children');
                if (hasChildren) {
                    item.setAttribute('aria-expanded', item.classList.contains('expanded') ? 'true' : 'false');
                }
            });

            layersList.dataset.vbpKeyboardBound = 'true';
        },

        initInspectorTabs: function() {
            var tabsContainer = document.querySelector('.vbp-inspector-tabs');
            if (!tabsContainer || tabsContainer.dataset.vbpKeyboardBound === 'true') return;

            tabsContainer.setAttribute('role', 'tablist');

            // Usar roving tabindex
            VBPRovingTabindex.init(tabsContainer, '.vbp-inspector-tab', {
                horizontal: true,
                loop: true,
                activateOnFocus: true
            });

            tabsContainer.querySelectorAll('.vbp-inspector-tab').forEach(function(tab) {
                tab.setAttribute('role', 'tab');

                var isActive = tab.classList.contains('active') || tab.classList.contains('is-active');
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');

                // Vincular con panel
                var panelId = tab.dataset.panel || tab.getAttribute('href')?.substring(1);
                if (panelId) {
                    tab.setAttribute('aria-controls', panelId);
                }
            });

            tabsContainer.dataset.vbpKeyboardBound = 'true';
        },

        initToolbars: function() {
            document.querySelectorAll('.vbp-toolbar, .vbp-toolbar-group').forEach(function(toolbar) {
                if (toolbar.dataset.vbpKeyboardBound === 'true') return;

                toolbar.setAttribute('role', 'toolbar');

                var toolbarLabel = toolbar.dataset.label || 'Barra de herramientas';
                toolbar.setAttribute('aria-label', toolbarLabel);

                // Roving tabindex para botones
                VBPRovingTabindex.init(toolbar, '.vbp-toolbar-btn, button', {
                    horizontal: true,
                    loop: true
                });

                toolbar.dataset.vbpKeyboardBound = 'true';
            });
        },

        initCanvasNavigation: function() {
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas || canvas.dataset.vbpKeyboardBound === 'true') return;

            canvas.setAttribute('role', 'application');
            canvas.setAttribute('aria-label', 'Lienzo de diseno. Use las flechas para mover elementos seleccionados.');

            // Hacer el canvas focuseable si no lo es
            if (!canvas.hasAttribute('tabindex')) {
                canvas.setAttribute('tabindex', '0');
            }

            canvas.dataset.vbpKeyboardBound = 'true';
        }
    };

    // ============================================
    // ARIA LABELS (mejorado)
    // ============================================
    window.vbpAria = {
        init: function() {
            this.addAriaToButtons();
            this.addAriaToInputs();
            this.addAriaToLayers();
            this.addAriaToCategories();
            this.addAriaToCanvas();
            this.addAriaToModals();
            this.addAriaToCollapsibles();
        },

        addAriaToButtons: function() {
            // Botones de control de capas
            var layerControlLabels = {
                'visibility': 'Alternar visibilidad',
                'lock': 'Bloquear/Desbloquear',
                'delete': 'Eliminar elemento',
                'duplicate': 'Duplicar elemento',
                'move-up': 'Mover arriba',
                'move-down': 'Mover abajo',
                'expand': 'Expandir/Colapsar',
                'rename': 'Renombrar'
            };

            document.querySelectorAll('.vbp-layer-control').forEach(function(btn) {
                var action = btn.dataset.action;
                if (layerControlLabels[action] && !btn.hasAttribute('aria-label')) {
                    btn.setAttribute('aria-label', layerControlLabels[action]);
                }
            });

            // Botones de toolbar
            document.querySelectorAll('.vbp-toolbar-btn').forEach(function(btn) {
                if (!btn.hasAttribute('aria-label')) {
                    if (btn.title) {
                        btn.setAttribute('aria-label', btn.title);
                    } else {
                        var icon = btn.querySelector('.material-icons, [class*="icon"]');
                        if (icon && icon.textContent) {
                            btn.setAttribute('aria-label', icon.textContent.replace(/_/g, ' '));
                        }
                    }
                }
            });

            // Botones solo con icono
            document.querySelectorAll('button:not([aria-label])').forEach(function(btn) {
                // Si el boton solo tiene un icono, necesita aria-label
                var hasOnlyIcon = btn.children.length === 1 &&
                    (btn.firstElementChild?.classList.contains('material-icons') ||
                     btn.firstElementChild?.tagName === 'svg');

                if (hasOnlyIcon && btn.title) {
                    btn.setAttribute('aria-label', btn.title);
                }
            });

            // Iconos decorativos
            document.querySelectorAll('.material-icons, [class*="icon"]').forEach(function(icon) {
                // Si el icono esta dentro de un boton con aria-label, es decorativo
                var parentButton = icon.closest('button[aria-label]');
                if (parentButton && !icon.hasAttribute('aria-hidden')) {
                    icon.setAttribute('aria-hidden', 'true');
                }
            });
        },

        addAriaToInputs: function() {
            document.querySelectorAll('.vbp-field-input, .vbp-field-select, .vbp-field-textarea').forEach(function(input) {
                var fieldGroup = input.closest('.vbp-field-group');
                var label = fieldGroup?.querySelector('.vbp-field-label');

                if (label && !input.hasAttribute('aria-label') && !input.id) {
                    var labelText = label.textContent.trim();
                    input.setAttribute('aria-label', labelText);
                }

                // Si tiene error
                var errorMsg = fieldGroup?.querySelector('.vbp-field-error');
                if (errorMsg) {
                    input.setAttribute('aria-invalid', 'true');
                    if (errorMsg.id) {
                        input.setAttribute('aria-describedby', errorMsg.id);
                    }
                }
            });
        },

        addAriaToLayers: function() {
            var layersList = document.querySelector('.vbp-layers-list');
            if (layersList) {
                layersList.setAttribute('role', 'tree');
                layersList.setAttribute('aria-label', 'Arbol de capas');

                layersList.querySelectorAll('.vbp-layer-item').forEach(function(item) {
                    item.setAttribute('role', 'treeitem');
                    if (item.classList.contains('selected')) {
                        item.setAttribute('aria-selected', 'true');
                    } else {
                        item.setAttribute('aria-selected', 'false');
                    }
                });
            }
        },

        addAriaToCategories: function() {
            document.querySelectorAll('.vbp-category-header').forEach(function(header) {
                header.setAttribute('role', 'button');

                var isExpanded = header.classList.contains('open') ||
                                 header.classList.contains('expanded') ||
                                 header.getAttribute('aria-expanded') === 'true';

                header.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

                var categoryName = header.querySelector('.vbp-category-name');
                if (categoryName && !header.hasAttribute('aria-label')) {
                    header.setAttribute('aria-label', 'Categoria: ' + categoryName.textContent.trim());
                }

                // Vincular con contenido
                var categoryContent = header.nextElementSibling;
                if (categoryContent && categoryContent.classList.contains('vbp-category-content')) {
                    if (!categoryContent.id) {
                        categoryContent.id = 'vbp-category-' + Math.random().toString(36).substr(2, 9);
                    }
                    header.setAttribute('aria-controls', categoryContent.id);
                }
            });
        },

        addAriaToCanvas: function() {
            var canvas = document.querySelector('.vbp-canvas');
            if (canvas) {
                canvas.setAttribute('role', 'application');
                canvas.setAttribute('aria-label', 'Lienzo de diseno');
                canvas.setAttribute('aria-roledescription', 'Area de construccion visual');
            }

            // Elementos del canvas
            document.querySelectorAll('.vbp-element').forEach(function(element) {
                if (!element.hasAttribute('role')) {
                    element.setAttribute('role', 'group');
                }

                var elementType = element.dataset.type || element.dataset.blockType || 'Elemento';
                element.setAttribute('aria-label', elementType);

                if (element.classList.contains('selected')) {
                    element.setAttribute('aria-selected', 'true');
                }
            });
        },

        addAriaToModals: function() {
            document.querySelectorAll('.vbp-modal, .vbp-dialog, [role="dialog"]').forEach(function(modal) {
                if (!modal.hasAttribute('aria-modal')) {
                    modal.setAttribute('aria-modal', 'true');
                }

                // Buscar titulo
                var title = modal.querySelector('.vbp-modal-title, .vbp-dialog-title, h2, h3');
                if (title) {
                    if (!title.id) {
                        title.id = 'vbp-modal-title-' + Math.random().toString(36).substr(2, 9);
                    }
                    modal.setAttribute('aria-labelledby', title.id);
                }
            });
        },

        addAriaToCollapsibles: function() {
            document.querySelectorAll('.vbp-collapsible-header, .vbp-accordion-header, [data-toggle="collapse"]').forEach(function(trigger) {
                if (!trigger.hasAttribute('aria-expanded')) {
                    var isExpanded = trigger.classList.contains('active') ||
                                     trigger.classList.contains('open') ||
                                     trigger.classList.contains('expanded');
                    trigger.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                }

                // Buscar contenido asociado
                var targetId = trigger.dataset.target || trigger.getAttribute('href')?.substring(1);
                if (targetId) {
                    trigger.setAttribute('aria-controls', targetId);
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

            // Agregar atributos de accesibilidad
            searchInput.setAttribute('role', 'searchbox');
            searchInput.setAttribute('aria-label', 'Buscar bloques');

            searchInput.addEventListener('input', function() {
                self.updateResultsCount();
            });

            // Crear contenedor de resultados si no existe
            if (!document.querySelector('.vbp-search-results')) {
                var resultsContainer = document.createElement('div');
                resultsContainer.className = 'vbp-search-results';
                resultsContainer.setAttribute('role', 'status');
                resultsContainer.setAttribute('aria-live', 'polite');
                resultsContainer.style.display = 'none';
                resultsContainer.innerHTML = '<span><span class="vbp-search-results-count">0</span> resultados</span>' +
                    '<button class="vbp-search-clear" type="button" aria-label="Limpiar busqueda">Limpiar</button>';

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
                var visibleBlocks = document.querySelectorAll('.vbp-block-item:not([style*="display: none"]):not([hidden])');
                var count = visibleBlocks.length;
                resultsContainer.querySelector('.vbp-search-results-count').textContent = count;
                resultsContainer.style.display = 'flex';

                // Anunciar a lectores de pantalla (con debounce)
                clearTimeout(this._announceTimeout);
                var self = this;
                this._announceTimeout = setTimeout(function() {
                    VBPAnnounce.announce(count + ' bloques encontrados para "' + query + '"');
                }, 500);
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
            } catch (error) {
                this.recentColors = [];
            }
        },

        saveRecentColors: function() {
            try {
                localStorage.setItem('vbp_recent_colors', JSON.stringify(this.recentColors));
            } catch (error) {
                // Ignorar errores de localStorage
            }
        },

        addRecentColor: function(color) {
            if (!color || color === 'transparent') return;

            var index = this.recentColors.indexOf(color);
            if (index > -1) {
                this.recentColors.splice(index, 1);
            }

            this.recentColors.unshift(color);

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

                // Agregar aria-label si no tiene
                if (!picker.hasAttribute('aria-label')) {
                    var fieldGroup = picker.closest('.vbp-field-group');
                    var label = fieldGroup?.querySelector('.vbp-field-label');
                    if (label) {
                        picker.setAttribute('aria-label', 'Selector de color: ' + label.textContent.trim());
                    }
                }

                picker.dataset.vbpColorEnhanced = 'true';
            });
        },

        updateRecentColorsUI: function() {
            var container = document.querySelector('.vbp-color-recent');
            if (!container) return;

            var itemsHtml = this.recentColors.map(function(color, index) {
                return '<button class="vbp-color-recent-item" style="background: ' + color + ';" data-color="' + color + '" ' +
                    'aria-label="Color reciente: ' + color + '" ' +
                    'tabindex="' + (index === 0 ? '0' : '-1') + '"></button>';
            }).join('');

            container.innerHTML = '<span class="vbp-color-recent-label" id="vbp-color-recent-label">Recientes</span>' + itemsHtml;
            container.setAttribute('role', 'listbox');
            container.setAttribute('aria-labelledby', 'vbp-color-recent-label');

            // Roving tabindex
            VBPRovingTabindex.init(container, '.vbp-color-recent-item', {
                horizontal: true,
                loop: true
            });

            container.querySelectorAll('.vbp-color-recent-item').forEach(function(item) {
                item.setAttribute('role', 'option');

                item.addEventListener('click', function() {
                    var colorInput = container.closest('.vbp-color-picker-wrapper')?.querySelector('.vbp-color-native');
                    if (colorInput) {
                        colorInput.value = this.dataset.color;
                        colorInput.dispatchEvent(new Event('change'));
                    }
                });

                item.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        this.click();
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
            this.indicator.setAttribute('role', 'status');
            this.indicator.setAttribute('aria-live', 'polite');
            this.indicator.setAttribute('aria-label', 'Vista actual: Desktop');
            this.indicator.innerHTML = '<span class="material-icons" aria-hidden="true">computer</span><span>Desktop</span>';

            toolbar.appendChild(this.indicator);
        },

        bindEvents: function() {
            var self = this;

            document.addEventListener('vbp:breakpoint-change', function(event) {
                self.updateIndicator(event.detail.breakpoint);
            });

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

            this.indicator.innerHTML = '<span class="material-icons" aria-hidden="true">' + icons[breakpoint] + '</span><span>' + labels[breakpoint] + '</span>';
            this.indicator.setAttribute('aria-label', 'Vista actual: ' + labels[breakpoint]);

            // Anunciar cambio
            VBPAnnounce.announce('Vista cambiada a ' + labels[breakpoint]);
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

            VBPAnnounce.announce('Preset aplicado');
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
            this.indicator.setAttribute('role', 'status');
            document.body.appendChild(this.indicator);
        },

        show: function(zoomLevel) {
            if (!this.indicator) return;

            var zoomPercentage = Math.round(zoomLevel * 100) + '%';
            this.indicator.textContent = zoomPercentage;
            this.indicator.classList.add('is-visible');

            // Anunciar a lectores de pantalla
            VBPAnnounce.announce('Zoom: ' + zoomPercentage);

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
            var controlId = 'vbp-spacing-' + Math.random().toString(36).substr(2, 9);

            var html = '<div class="vbp-spacing-control" role="group" aria-label="Control de espaciado">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--top" value="' + (values.top || 0) + '" data-side="top" aria-label="Espaciado superior" id="' + controlId + '-top">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--right" value="' + (values.right || 0) + '" data-side="right" aria-label="Espaciado derecho" id="' + controlId + '-right">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--bottom" value="' + (values.bottom || 0) + '" data-side="bottom" aria-label="Espaciado inferior" id="' + controlId + '-bottom">' +
                '<input type="number" class="vbp-spacing-input vbp-spacing-input--left" value="' + (values.left || 0) + '" data-side="left" aria-label="Espaciado izquierdo" id="' + controlId + '-left">' +
                '<div class="vbp-spacing-box" aria-hidden="true">elem</div>' +
                '<button class="vbp-spacing-link" type="button" aria-label="Vincular todos los valores" aria-pressed="false">' +
                '<span class="material-icons" aria-hidden="true">link</span>Link' +
                '</button></div>';

            container.innerHTML = html;

            var linked = false;
            var linkBtn = container.querySelector('.vbp-spacing-link');
            var inputs = container.querySelectorAll('.vbp-spacing-input');

            linkBtn.addEventListener('click', function() {
                linked = !linked;
                this.classList.toggle('is-linked', linked);
                this.setAttribute('aria-pressed', linked ? 'true' : 'false');
                VBPAnnounce.announce(linked ? 'Valores vinculados' : 'Valores desvinculados');
            });

            inputs.forEach(function(input) {
                input.addEventListener('input', function() {
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
    // ACCESSIBILITY AUDIT
    // ============================================
    window.VBPAccessibilityAudit = {
        /**
         * Ejecutar auditoria de accesibilidad
         * @returns {object} Resultados de la auditoria
         */
        run: function() {
            var results = {
                errors: [],
                warnings: [],
                passed: [],
                summary: {}
            };

            // Verificar imagenes sin alt
            this.checkImagesWithoutAlt(results);

            // Verificar botones sin label
            this.checkButtonsWithoutLabel(results);

            // Verificar inputs sin label
            this.checkInputsWithoutLabel(results);

            // Verificar contraste (basico)
            this.checkFocusVisible(results);

            // Verificar focus order
            this.checkFocusOrder(results);

            // Verificar landmarks
            this.checkLandmarks(results);

            // Generar resumen
            results.summary = {
                totalErrors: results.errors.length,
                totalWarnings: results.warnings.length,
                totalPassed: results.passed.length,
                score: this.calculateScore(results)
            };

            return results;
        },

        checkImagesWithoutAlt: function(results) {
            var imagesWithoutAlt = document.querySelectorAll('img:not([alt]), img[alt=""]');
            if (imagesWithoutAlt.length > 0) {
                results.errors.push({
                    rule: 'images-alt',
                    message: imagesWithoutAlt.length + ' imagen(es) sin atributo alt',
                    elements: Array.from(imagesWithoutAlt),
                    severity: 'error'
                });
            } else {
                results.passed.push({
                    rule: 'images-alt',
                    message: 'Todas las imagenes tienen atributo alt'
                });
            }
        },

        checkButtonsWithoutLabel: function(results) {
            var buttonsWithoutLabel = [];

            document.querySelectorAll('button').forEach(function(button) {
                var hasAccessibleName = button.hasAttribute('aria-label') ||
                    button.hasAttribute('aria-labelledby') ||
                    button.textContent.trim().length > 0 ||
                    button.title;

                if (!hasAccessibleName) {
                    buttonsWithoutLabel.push(button);
                }
            });

            if (buttonsWithoutLabel.length > 0) {
                results.errors.push({
                    rule: 'buttons-label',
                    message: buttonsWithoutLabel.length + ' boton(es) sin texto accesible',
                    elements: buttonsWithoutLabel,
                    severity: 'error'
                });
            } else {
                results.passed.push({
                    rule: 'buttons-label',
                    message: 'Todos los botones tienen texto accesible'
                });
            }
        },

        checkInputsWithoutLabel: function(results) {
            var inputsWithoutLabel = [];

            document.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea').forEach(function(input) {
                var hasLabel = input.hasAttribute('aria-label') ||
                    input.hasAttribute('aria-labelledby') ||
                    input.id && document.querySelector('label[for="' + input.id + '"]') ||
                    input.closest('label');

                if (!hasLabel) {
                    inputsWithoutLabel.push(input);
                }
            });

            if (inputsWithoutLabel.length > 0) {
                results.errors.push({
                    rule: 'inputs-label',
                    message: inputsWithoutLabel.length + ' campo(s) de formulario sin etiqueta',
                    elements: inputsWithoutLabel,
                    severity: 'error'
                });
            } else {
                results.passed.push({
                    rule: 'inputs-label',
                    message: 'Todos los campos tienen etiqueta'
                });
            }
        },

        checkFocusVisible: function(results) {
            // Verificar que hay estilos de focus visible
            var hasCustomFocusStyles = false;

            // Buscar en stylesheets
            try {
                for (var i = 0; i < document.styleSheets.length; i++) {
                    try {
                        var rules = document.styleSheets[i].cssRules || document.styleSheets[i].rules;
                        if (!rules) continue;

                        for (var j = 0; j < rules.length; j++) {
                            var rule = rules[j];
                            if (rule.selectorText && (rule.selectorText.includes(':focus') || rule.selectorText.includes(':focus-visible'))) {
                                hasCustomFocusStyles = true;
                                break;
                            }
                        }
                    } catch (error) {
                        // Puede fallar por CORS
                    }
                }
            } catch (error) {
                // Ignorar errores
            }

            if (hasCustomFocusStyles) {
                results.passed.push({
                    rule: 'focus-visible',
                    message: 'Se detectaron estilos de focus personalizados'
                });
            } else {
                results.warnings.push({
                    rule: 'focus-visible',
                    message: 'No se detectaron estilos de focus personalizados. Considere agregar :focus-visible',
                    severity: 'warning'
                });
            }
        },

        checkFocusOrder: function(results) {
            // Verificar que no hay tabindex positivos
            var positiveTabindex = document.querySelectorAll('[tabindex]:not([tabindex="-1"]):not([tabindex="0"])');
            var positiveCount = 0;

            positiveTabindex.forEach(function(element) {
                var tabindex = parseInt(element.getAttribute('tabindex'), 10);
                if (tabindex > 0) {
                    positiveCount++;
                }
            });

            if (positiveCount > 0) {
                results.warnings.push({
                    rule: 'focus-order',
                    message: positiveCount + ' elemento(s) con tabindex positivo. Esto puede alterar el orden de navegacion',
                    elements: Array.from(positiveTabindex),
                    severity: 'warning'
                });
            } else {
                results.passed.push({
                    rule: 'focus-order',
                    message: 'No hay tabindex positivos que alteren el orden de navegacion'
                });
            }
        },

        checkLandmarks: function(results) {
            var hasMain = document.querySelector('main, [role="main"]');
            var hasNav = document.querySelector('nav, [role="navigation"]');

            if (!hasMain) {
                results.warnings.push({
                    rule: 'landmarks',
                    message: 'No se encontro landmark main. Considere agregar <main> o role="main"',
                    severity: 'warning'
                });
            } else {
                results.passed.push({
                    rule: 'landmarks',
                    message: 'Landmark main presente'
                });
            }
        },

        calculateScore: function(results) {
            var total = results.errors.length + results.warnings.length + results.passed.length;
            if (total === 0) return 100;

            var errorWeight = results.errors.length * 2;
            var warningWeight = results.warnings.length * 1;
            var deductions = errorWeight + warningWeight;

            var score = Math.max(0, 100 - (deductions / total) * 50);
            return Math.round(score);
        },

        /**
         * Mostrar resultados en consola
         */
        logResults: function() {
            var results = this.run();

            console.group('%cVBP Accessibility Audit', 'font-size: 16px; font-weight: bold;');
            console.log('%cScore: ' + results.summary.score + '/100', 'font-size: 14px; color: ' + (results.summary.score >= 80 ? 'green' : results.summary.score >= 60 ? 'orange' : 'red'));

            if (results.errors.length > 0) {
                console.group('%cErrors (' + results.errors.length + ')', 'color: red; font-weight: bold;');
                results.errors.forEach(function(error) {
                    console.log('%c' + error.rule + ': ' + error.message, 'color: red;');
                    if (error.elements) console.log('  Elements:', error.elements);
                });
                console.groupEnd();
            }

            if (results.warnings.length > 0) {
                console.group('%cWarnings (' + results.warnings.length + ')', 'color: orange; font-weight: bold;');
                results.warnings.forEach(function(warning) {
                    console.log('%c' + warning.rule + ': ' + warning.message, 'color: orange;');
                });
                console.groupEnd();
            }

            if (results.passed.length > 0) {
                console.group('%cPassed (' + results.passed.length + ')', 'color: green;');
                results.passed.forEach(function(pass) {
                    console.log('%c' + pass.rule + ': ' + pass.message, 'color: green;');
                });
                console.groupEnd();
            }

            console.groupEnd();

            return results;
        }
    };

    // Exponer globalmente
    window.VBP = window.VBP || {};
    window.VBP.a11yAudit = function() {
        return VBPAccessibilityAudit.logResults();
    };

    // ============================================
    // KEYBOARD SHORTCUTS HELP PANEL
    // ============================================
    window.VBPKeyboardHelp = {
        panel: null,
        shortcuts: null,
        isOpen: false,

        init: function() {
            if (this.panel) return;

            var self = this;

            // Cargar shortcuts desde JSON
            this.loadShortcuts().then(function() {
                self.createPanel();
                self.bindEvents();
            });
        },

        loadShortcuts: function() {
            var self = this;
            var basePath = typeof VBP_Config !== 'undefined' ? VBP_Config.assetsUrl : '/wp-content/plugins/flavor-platform/assets/vbp/';

            return fetch(basePath + 'js/vbp-keyboard-shortcuts.json')
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    self.shortcuts = data.categories;
                })
                .catch(function(error) {
                    console.warn('[VBP] Error loading shortcuts:', error);
                    self.shortcuts = {};
                });
        },

        createPanel: function() {
            var panelHtml = '<div class="vbp-keyboard-help-panel" role="dialog" aria-modal="true" aria-labelledby="vbp-keyboard-help-title">' +
                '<div class="vbp-keyboard-help-content">' +
                '<div class="vbp-keyboard-help-header">' +
                '<h2 id="vbp-keyboard-help-title">Atajos de teclado</h2>' +
                '<button class="vbp-keyboard-help-close" aria-label="Cerrar panel de atajos">&times;</button>' +
                '</div>' +
                '<div class="vbp-keyboard-help-body">' +
                '<div class="vbp-keyboard-help-search">' +
                '<input type="search" class="vbp-keyboard-help-input" placeholder="Buscar atajos..." aria-label="Buscar atajos de teclado">' +
                '</div>' +
                '<div class="vbp-keyboard-help-categories">' +
                this.renderCategories() +
                '</div>' +
                '</div>' +
                '</div></div>';

            var container = document.createElement('div');
            container.innerHTML = panelHtml;
            this.panel = container.firstChild;
            document.body.appendChild(this.panel);

            // Busqueda
            var searchInput = this.panel.querySelector('.vbp-keyboard-help-input');
            var self = this;
            searchInput.addEventListener('input', function() {
                self.filterShortcuts(this.value);
            });

            // Cerrar
            this.panel.querySelector('.vbp-keyboard-help-close').addEventListener('click', function() {
                self.close();
            });

            this.panel.addEventListener('click', function(event) {
                if (event.target === self.panel) {
                    self.close();
                }
            });
        },

        renderCategories: function() {
            if (!this.shortcuts) return '<p>Cargando atajos...</p>';

            var html = '';
            var self = this;

            Object.keys(this.shortcuts).forEach(function(categoryId) {
                var category = self.shortcuts[categoryId];
                if (!category.shortcuts) return;

                html += '<div class="vbp-keyboard-category" data-category="' + categoryId + '">' +
                    '<h3 class="vbp-keyboard-category-title">' + (category.label || categoryId) + '</h3>' +
                    '<ul class="vbp-keyboard-list" role="list">';

                Object.keys(category.shortcuts).forEach(function(shortcut) {
                    var action = category.shortcuts[shortcut];
                    var formattedShortcut = self.formatShortcut(shortcut);
                    var formattedAction = self.formatAction(action);

                    html += '<li class="vbp-keyboard-item" data-shortcut="' + shortcut + '" data-action="' + action + '">' +
                        '<kbd class="vbp-keyboard-kbd">' + formattedShortcut + '</kbd>' +
                        '<span class="vbp-keyboard-action">' + formattedAction + '</span>' +
                        '</li>';
                });

                html += '</ul></div>';
            });

            return html || '<p>No hay atajos disponibles</p>';
        },

        formatShortcut: function(shortcut) {
            var isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform);

            return shortcut
                .replace(/ctrl/gi, isMac ? '\u2318' : 'Ctrl')
                .replace(/alt/gi, isMac ? '\u2325' : 'Alt')
                .replace(/shift/gi, '\u21E7')
                .replace(/\+/g, ' + ')
                .replace(/arrowup/gi, '\u2191')
                .replace(/arrowdown/gi, '\u2193')
                .replace(/arrowleft/gi, '\u2190')
                .replace(/arrowright/gi, '\u2192')
                .replace(/escape/gi, 'Esc')
                .replace(/delete/gi, 'Del')
                .replace(/backspace/gi, '\u232B')
                .replace(/enter/gi, '\u23CE')
                .replace(/ /g, 'Espacio');
        },

        formatAction: function(action) {
            // Convertir camelCase a texto legible
            return action
                .replace(/([A-Z])/g, ' $1')
                .replace(/^./, function(str) { return str.toUpperCase(); })
                .trim();
        },

        filterShortcuts: function(query) {
            query = query.toLowerCase().trim();
            var items = this.panel.querySelectorAll('.vbp-keyboard-item');
            var categories = this.panel.querySelectorAll('.vbp-keyboard-category');

            items.forEach(function(item) {
                var shortcut = item.dataset.shortcut.toLowerCase();
                var action = item.dataset.action.toLowerCase();
                var matches = shortcut.includes(query) || action.includes(query);
                item.style.display = matches || query === '' ? '' : 'none';
            });

            // Ocultar categorias vacias
            categories.forEach(function(category) {
                var visibleItems = category.querySelectorAll('.vbp-keyboard-item:not([style*="display: none"])');
                category.style.display = visibleItems.length > 0 || query === '' ? '' : 'none';
            });
        },

        bindEvents: function() {
            var self = this;

            // Abrir con ? o F1
            document.addEventListener('keydown', function(event) {
                if ((event.key === '?' || event.key === 'F1') && !self.isInputFocused()) {
                    event.preventDefault();
                    self.toggle();
                }

                if (event.key === 'Escape' && self.isOpen) {
                    self.close();
                }
            });
        },

        isInputFocused: function() {
            var activeElement = document.activeElement;
            var tagName = activeElement?.tagName.toLowerCase();
            return tagName === 'input' || tagName === 'textarea' || activeElement?.isContentEditable;
        },

        open: function() {
            if (!this.panel) this.init();

            VBPFocusManager.trapFocus(this.panel);
            this.panel.classList.add('is-open');
            this.isOpen = true;

            // Focus en la busqueda
            var searchInput = this.panel.querySelector('.vbp-keyboard-help-input');
            if (searchInput) {
                searchInput.focus();
            }

            VBPAnnounce.announce('Panel de atajos de teclado abierto');
        },

        close: function() {
            if (!this.panel) return;

            this.panel.classList.remove('is-open');
            this.isOpen = false;

            VBPFocusManager.releaseTrap(this.panel);
        },

        toggle: function() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }
    };

    // ============================================
    // INIT ALL
    // ============================================
    function initAll() {
        // Esperar a que el DOM este listo
        if (document.readyState === 'loading') {
            if (!window.__vbpAccessibilityDomReadyBound) {
                window.__vbpAccessibilityDomReadyBound = true;
                document.addEventListener('DOMContentLoaded', initAll, { once: true });
            }
            return;
        }

        // Inicializar todos los modulos
        VBPAnnounce.init();
        VBPSkipLinks.init();
        vbpKeyboardNav.init();
        vbpAria.init();
        vbpSearchEnhanced.init();
        vbpColorEnhanced.init();
        vbpBreakpointIndicator.init();
        vbpZoomIndicator.init();
        VBPKeyboardHelp.init();

        // Re-init cuando Alpine actualiza el DOM
        if (!window.__vbpAccessibilityAlpineInitBound) {
            window.__vbpAccessibilityAlpineInitBound = true;
            document.addEventListener('alpine:initialized', function() {
                requestAnimationFrame(function() {
                    vbpAria.init();
                    vbpKeyboardNav.initBlocksList();
                    vbpKeyboardNav.initLayersList();
                    vbpKeyboardNav.initToolbars();
                });
            });
        }

        // Observar cambios en el DOM para re-aplicar ARIA
        if (!window.__vbpAccessibilityObserverBound) {
            window.__vbpAccessibilityObserverBound = true;
            var pendingAriaRefresh = false;
            var observer = new MutationObserver(function(mutations) {
                var needsAriaUpdate = mutations.some(function(mutation) {
                    return mutation.addedNodes && mutation.addedNodes.length > 0;
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
