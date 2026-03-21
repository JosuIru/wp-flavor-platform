/**
 * Visual Builder Pro - App
 * Componente principal de Alpine.js
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Sanitiza elementos asegurando que todos tengan ID válido
 * @param {Array} elements - Array de elementos
 * @returns {Array} - Array filtrado con elementos válidos
 */
function sanitizeElements(elements) {
    if (!Array.isArray(elements)) return [];
    return elements.filter(function(el) {
        // Filtrar elementos sin ID o con ID undefined/null
        if (!el || typeof el.id !== 'string' || !el.id) {
            console.warn('[VBP] Elemento inválido filtrado:', el);
            return false;
        }
        return true;
    }).map(function(el) {
        // Asegurar que cada elemento tenga las propiedades mínimas
        return Object.assign({
            id: el.id,
            type: el.type || 'unknown',
            name: el.name || el.type || 'Elemento',
            visible: el.visible !== false,
            locked: el.locked || false,
            data: el.data || {},
            styles: el.styles || {},
            children: el.children || [],
            _version: el._version || 0
        }, el);
    });
}

function vbpApp() {
    return {
        // Getter seguro para elementos del store
        get elements() {
            var store = Alpine.store('vbp');
            return store && store.elements ? store.elements : [];
        },

        documentTitle: '',
        isSaving: false,
        saveStatus: '',
        saveStatusClass: '',
        zoom: 100,
        devicePreview: 'desktop',
        splitScreenMode: false,
        splitScreenSyncScroll: true,
        splitScreenDevices: ['desktop', 'mobile'],
        showRulers: true,
        activeLeftTab: 'blocks',
        panels: { blocks: true, inspector: true, layers: true },
        showFloatingToolbar: false,
        floatingToolbarPosition: { x: 0, y: 0 },
        notifications: [],
        dropIndicator: { visible: false, y: 0 },
        draggedElement: null,
        autosaveTimer: null,
        autosaveInterval: 30000,

        // Modales
        showHelpModal: false,
        showCommandPalette: false,
        showTemplatesModal: false,
        showExportModal: false,
        showSaveTemplateModal: false,
        showRevisionsModal: false,
        commandSearch: '',
        commandIndex: 0,

        // Revisiones
        revisions: [],
        isLoadingRevisions: false,
        isRestoringRevision: false,

        // Templates
        templatesTab: 'library',
        templateSearch: '',
        templateCategory: '',
        templates: [],
        userTemplates: [],
        selectedTemplate: null,
        newTemplateName: '',
        newTemplateCategory: 'landing',
        newTemplateDescription: '',
        isSavingTemplate: false,
        importDragOver: false,
        importJsonText: '',

        // Widgets Globales
        globalWidgets: [],
        globalWidgetsLoaded: false,
        showSaveGlobalWidgetModal: false,
        newGlobalWidgetName: '',
        newGlobalWidgetCategory: 'general',
        isSavingGlobalWidget: false,

        // Unsplash
        showUnsplashModal: false,
        unsplashConfigured: true,
        unsplashQuery: '',
        unsplashOrientation: '',
        unsplashImages: [],
        unsplashPage: 1,
        unsplashTotalPages: 0,
        isSearchingUnsplash: false,
        unsplashTargetElement: null,

        // Historial de versiones
        showVersionHistoryModal: false,
        showVersionDiffModal: false,
        versions: [],
        isLoadingVersions: false,
        selectedVersionA: null,
        selectedVersionB: null,
        versionDiff: null,
        isRestoringVersion: false,
        newVersionLabel: '',

        commands: [
            { id: 'save', name: 'Guardar', shortcut: 'Ctrl+S', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/></svg>', action: 'save' },
            { id: 'undo', name: 'Deshacer', shortcut: 'Ctrl+Z', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13"/></svg>', action: 'undo' },
            { id: 'redo', name: 'Rehacer', shortcut: 'Ctrl+Shift+Z', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 019-9 9 9 0 016 2.3l3 2.7"/></svg>', action: 'redo' },
            { id: 'copy', name: 'Copiar', shortcut: 'Ctrl+C', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>', action: 'copy' },
            { id: 'paste', name: 'Pegar', shortcut: 'Ctrl+V', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>', action: 'paste' },
            { id: 'duplicate', name: 'Duplicar', shortcut: 'Ctrl+D', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>', action: 'duplicate' },
            { id: 'saveAsGlobal', name: 'Guardar como widget global', shortcut: 'Ctrl+Shift+G', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>', action: 'saveAsGlobal' },
            { id: 'delete', name: 'Eliminar', shortcut: 'Delete', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>', action: 'delete' },
            { id: 'selectAll', name: 'Seleccionar todo', shortcut: 'Ctrl+A', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 12l2 2 4-4"/></svg>', action: 'selectAll' },
            { id: 'deselect', name: 'Deseleccionar', shortcut: 'Esc', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>', action: 'deselect' },
            { id: 'zoomIn', name: 'Acercar', shortcut: 'Ctrl++', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>', action: 'zoomIn' },
            { id: 'zoomOut', name: 'Alejar', shortcut: 'Ctrl+-', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M8 11h6"/></svg>', action: 'zoomOut' },
            { id: 'zoomReset', name: 'Zoom 100%', shortcut: 'Ctrl+0', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', action: 'zoomReset' },
            { id: 'preview', name: 'Previsualizar', shortcut: 'Ctrl+P', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>', action: 'preview' },
            { id: 'help', name: 'Mostrar ayuda', shortcut: '?', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/></svg>', action: 'help' },
            { id: 'togglePanels', name: 'Toggle paneles', shortcut: 'Ctrl+\\', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>', action: 'togglePanels' },
            { id: 'addHero', name: 'Añadir Hero', shortcut: '', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>', action: 'addHero' },
            { id: 'addText', name: 'Añadir Texto', shortcut: '', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>', action: 'addText' },
            { id: 'addImage', name: 'Añadir Imagen', shortcut: '', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>', action: 'addImage' },
            { id: 'addButton', name: 'Añadir Botón', shortcut: '', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="4"/></svg>', action: 'addButton' },
            { id: 'templates', name: 'Templates', shortcut: 'Ctrl+T', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>', action: 'templates' },
            { id: 'export', name: 'Exportar diseño', shortcut: 'Ctrl+E', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17,8 12,3 7,8"/><path d="M12 3v12"/></svg>', action: 'export' },
            { id: 'unsplash', name: 'Buscar en Unsplash', shortcut: 'Ctrl+U', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7.5 6.75V0h9v6.75h-9zm9 3.75H24V24H0V10.5h7.5v6.75h9V10.5z"/></svg>', action: 'unsplash' },
            { id: 'versionHistory', name: 'Historial de versiones', shortcut: 'Ctrl+H', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>', action: 'versionHistory' }
        ],
        filteredCommands: [],

        // Panel de configuración de página
        showPageSettings: false,
        pageSettingsTab: 'general',
        pageSettings: {
            seoTitle: '',
            seoDescription: '',
            ogImage: '',
            ogTitle: '',
            ogDescription: '',
            customCss: '',
            customJs: '',
            pageClass: '',
            pageId: ''
        },

        initEditor: function(datos) {
            if (datos && datos.elements) {
                Alpine.store('vbp').elements = sanitizeElements(datos.elements);
            }
            if (datos && datos.settings) {
                Alpine.store('vbp').settings = datos.settings;
                // Cargar page settings
                if (datos.settings.pageSettings) {
                    this.pageSettings = Object.assign({}, this.pageSettings, datos.settings.pageSettings);
                }
            }
            Alpine.store('vbp').postId = VBP_Config.postId;
            this.loadDocument();
            this.loadTemplates();
            this.loadGlobalWidgets();
            this.startAutosave();
            this.initZoomWheel();
            this.initCountdownTimer();
            this.initInteractiveElements();
            var self = this;
            this.$nextTick(function() { self.drawRulers(); });
        },

        // Timer para actualizar countdowns cada segundo
        countdownTimer: null,
        initCountdownTimer: function() {
            var self = this;
            this.countdownTimer = setInterval(function() {
                self.updateCountdowns();
            }, 1000);
        },

        updateCountdowns: function() {
            var elements = Alpine.store('vbp').elements;
            var countdownElements = document.querySelectorAll('[data-countdown-id]');

            countdownElements.forEach(function(el) {
                var elementId = el.getAttribute('data-countdown-id');
                var element = elements.find(function(e) { return e.id === elementId; });

                if (element && element.data) {
                    var targetDate = element.data.fecha ?
                        new Date(element.data.fecha + 'T' + (element.data.hora || '23:59')) :
                        new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);

                    var now = new Date();
                    var diff = Math.max(0, targetDate - now);

                    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    var secs = Math.floor((diff % (1000 * 60)) / 1000);

                    var daysEl = el.querySelector('[data-unit="days"]');
                    var hoursEl = el.querySelector('[data-unit="hours"]');
                    var minsEl = el.querySelector('[data-unit="mins"]');
                    var secsEl = el.querySelector('[data-unit="secs"]');

                    if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
                    if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
                    if (minsEl) minsEl.textContent = String(mins).padStart(2, '0');
                    if (secsEl) secsEl.textContent = String(secs).padStart(2, '0');
                }
            });
        },

        // Inicializar elementos interactivos (accordion, tabs, elementos hijos)
        initInteractiveElements: function() {
            var self = this;

            // Delegación de eventos para accordion, tabs y elementos hijos de contenedores
            document.addEventListener('click', function(e) {
                // Selección de elementos hijos dentro de contenedores
                var childElement = e.target.closest('.vbp-element-child');
                if (childElement) {
                    var elementId = childElement.getAttribute('data-element-id');
                    if (elementId) {
                        e.stopPropagation();
                        var store = Alpine.store('vbp');
                        var multiSelect = e.ctrlKey || e.metaKey || e.shiftKey;

                        if (multiSelect) {
                            store.toggleSelection(elementId);
                        } else {
                            store.setSelection([elementId]);
                        }

                        // Marcar visualmente como seleccionado
                        document.querySelectorAll('.vbp-element-child.selected').forEach(function(el) {
                            el.classList.remove('selected');
                        });
                        if (store.selection.elementIds.includes(elementId)) {
                            childElement.classList.add('selected');
                        }
                        return;
                    }
                }

                // Accordion toggle
                var accordionHeader = e.target.closest('.vbp-accordion-header');
                if (accordionHeader) {
                    e.stopPropagation();
                    self.toggleAccordionItem(accordionHeader);
                }

                // Tabs toggle
                var tabButton = e.target.closest('.vbp-tab-button');
                if (tabButton) {
                    e.stopPropagation();
                    self.switchTab(tabButton);
                }
            });

            // Escuchar eventos de apertura de modales (desde keyboard shortcuts)
            document.addEventListener('vbp:openModal', function(e) {
                var modalType = e.detail && e.detail.modal;
                switch (modalType) {
                    case 'export':
                        self.showExportModal = true;
                        break;
                    case 'templates':
                        self.showTemplatesModal = true;
                        break;
                    case 'commandPalette':
                        self.openCommandPalette();
                        break;
                    case 'help':
                        self.showHelpModal = true;
                        break;
                    case 'settings':
                        self.showPageSettings = true;
                        break;
                }
            });

            // Escuchar eventos de notificación (desde keyboard shortcuts)
            document.addEventListener('vbp:notification', function(e) {
                if (e.detail && e.detail.message) {
                    self.showNotification(e.detail.message, e.detail.type || 'info');
                }
            });

            // Before/After slider interactivity
            self.initBeforeAfterSliders();
        },

        // Inicializar sliders Before/After
        initBeforeAfterSliders: function() {
            var self = this;
            var isDragging = false;
            var currentSlider = null;
            var currentContainer = null;

            document.addEventListener('mousedown', function(e) {
                var slider = e.target.closest('.vbp-ba-slider');
                if (slider) {
                    e.preventDefault();
                    isDragging = true;
                    currentSlider = slider;
                    currentContainer = slider.closest('.vbp-ba-container');
                }
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging || !currentContainer) return;

                var rect = currentContainer.getBoundingClientRect();
                var orientation = currentContainer.getAttribute('data-orientation') || 'horizontal';
                var percentage;

                if (orientation === 'horizontal') {
                    percentage = ((e.clientX - rect.left) / rect.width) * 100;
                } else {
                    percentage = ((e.clientY - rect.top) / rect.height) * 100;
                }

                percentage = Math.max(0, Math.min(100, percentage));

                var beforeElement = currentContainer.querySelector('.vbp-ba-before');
                if (beforeElement) {
                    if (orientation === 'horizontal') {
                        beforeElement.style.width = percentage + '%';
                        currentSlider.style.left = percentage + '%';
                    } else {
                        beforeElement.style.height = percentage + '%';
                        currentSlider.style.top = percentage + '%';
                    }
                }

                // Actualizar el store con la nueva posición
                var elementId = currentContainer.closest('[data-element-id]');
                if (elementId) {
                    var id = elementId.getAttribute('data-element-id');
                    var store = Alpine.store('vbp');
                    var element = store.getElement(id);
                    if (element) {
                        var data = JSON.parse(JSON.stringify(element.data || {}));
                        data.posicion = Math.round(percentage);
                        store.updateElement(id, { data: data });
                    }
                }
            });

            document.addEventListener('mouseup', function() {
                isDragging = false;
                currentSlider = null;
                currentContainer = null;
            });
        },

        toggleAccordionItem: function(header) {
            var item = header.closest('.vbp-accordion-item');
            if (!item) return;

            var content = item.querySelector('.vbp-accordion-content');
            var icon = header.querySelector('.vbp-accordion-icon');

            if (content.style.display === 'none' || !content.style.display) {
                content.style.display = 'block';
                if (icon) icon.textContent = '▼';
            } else {
                content.style.display = 'none';
                if (icon) icon.textContent = '▶';
            }
        },

        switchTab: function(tabButton) {
            var tabsContainer = tabButton.closest('.vbp-tabs');
            if (!tabsContainer) return;

            var tabIndex = parseInt(tabButton.getAttribute('data-tab-index'));
            var allButtons = tabsContainer.querySelectorAll('.vbp-tab-button');
            var allPanels = tabsContainer.querySelectorAll('.vbp-tab-panel');

            allButtons.forEach(function(btn, i) {
                btn.classList.toggle('active', i === tabIndex);
            });

            allPanels.forEach(function(panel, i) {
                panel.style.display = i === tabIndex ? 'block' : 'none';
            });
        },

        initZoomWheel: function() {
            var self = this;
            var canvasArea = document.querySelector('.vbp-canvas-area');
            if (canvasArea) {
                canvasArea.addEventListener('wheel', function(e) {
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            self.zoomIn();
                        } else {
                            self.zoomOut();
                        }
                    }
                }, { passive: false });
            }
        },

        get canvasStyles() {
            var settings = Alpine.store('vbp').settings;
            var styles = {
                backgroundColor: settings.backgroundColor || '#ffffff'
            };
            // Solo aplicar maxWidth en desktop, tablet y mobile usan las clases CSS
            if (this.devicePreview === 'desktop') {
                var pageWidth = settings.pageWidth || '1200';
                var pageWidthUnit = settings.pageWidthUnit || 'px';
                // Si el valor ya incluye unidad, usarlo directamente
                if (typeof pageWidth === 'string' && (pageWidth.includes('%') || pageWidth.includes('px'))) {
                    styles.maxWidth = pageWidth;
                } else {
                    styles.maxWidth = pageWidth + pageWidthUnit;
                }
                // Si es porcentaje, también ajustar el width
                if (pageWidthUnit === '%' || (typeof pageWidth === 'string' && pageWidth.includes('%'))) {
                    styles.width = styles.maxWidth;
                }
            }
            return styles;
        },

        updatePageWidthUnit: function() {
            // Forzar actualización del canvas al cambiar unidad
            Alpine.store('vbp').isDirty = true;
        },

        loadDocument: function() {
            var self = this;
            fetch(VBP_Config.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'vbp_cargar_documento',
                    nonce: VBP_Config.nonce,
                    post_id: VBP_Config.postId
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.documentTitle = result.data.post.title;
                    if (result.data.data) {
                        Alpine.store('vbp').elements = sanitizeElements(result.data.data.elements || []);
                        var settings = result.data.data.settings || {};
                        // Normalizar pageWidth para compatibilidad con formato antiguo (numérico)
                        if (settings.pageWidth !== undefined) {
                            var pageWidth = settings.pageWidth;
                            if (typeof pageWidth === 'number') {
                                settings.pageWidth = String(pageWidth);
                                settings.pageWidthUnit = 'px';
                            } else if (typeof pageWidth === 'string') {
                                if (pageWidth.includes('%')) {
                                    settings.pageWidthUnit = '%';
                                    settings.pageWidth = pageWidth.replace('%', '');
                                } else if (pageWidth.includes('px')) {
                                    settings.pageWidthUnit = 'px';
                                    settings.pageWidth = pageWidth.replace('px', '');
                                } else if (!settings.pageWidthUnit) {
                                    settings.pageWidthUnit = 'px';
                                }
                            }
                        }
                        if (!settings.pageWidthUnit) {
                            settings.pageWidthUnit = 'px';
                        }
                        Alpine.store('vbp').settings = settings;
                    }
                }
            })
            .catch(function(error) {
                console.error('Error cargando documento:', error);
            });
        },

        saveDocument: function() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.saveStatus = VBP_Config.strings.saving;
            this.saveStatusClass = 'saving';
            var self = this;

            fetch(VBP_Config.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'vbp_guardar_documento',
                    nonce: VBP_Config.nonce,
                    post_id: VBP_Config.postId,
                    title: this.documentTitle,
                    data: JSON.stringify({
                        elements: Alpine.store('vbp').elements,
                        settings: Alpine.store('vbp').settings
                    })
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.saveStatus = VBP_Config.strings.saved;
                    self.saveStatusClass = 'saved';
                    Alpine.store('vbp').isDirty = false;
                    self.showNotification('Documento guardado', 'success');
                } else {
                    throw new Error(result.data.message);
                }
                self.isSaving = false;
                setTimeout(function() { self.saveStatus = ''; }, 3000);
            })
            .catch(function(error) {
                self.saveStatus = VBP_Config.strings.error;
                self.saveStatusClass = 'error';
                self.showNotification('Error al guardar: ' + error.message, 'error');
                self.isSaving = false;
                setTimeout(function() { self.saveStatus = ''; }, 3000);
            });
        },

        publishDocument: function() {
            this.saveDocument();
            this.showNotification('Documento publicado', 'success');
        },

        startAutosave: function() {
            var self = this;
            this.autosaveTimer = setInterval(function() {
                if (Alpine.store('vbp').isDirty) { self.saveDocument(); }
            }, this.autosaveInterval);
        },

        markDirty: function() { Alpine.store('vbp').isDirty = true; },
        zoomIn: function() { if (this.zoom < 200) { this.zoom += 10; Alpine.store('vbp').zoom = this.zoom; this.drawRulers(); } },
        zoomOut: function() { if (this.zoom > 25) { this.zoom -= 10; Alpine.store('vbp').zoom = this.zoom; this.drawRulers(); } },
        setZoom: function(value) { this.zoom = Math.max(25, Math.min(200, value)); Alpine.store('vbp').zoom = this.zoom; this.drawRulers(); },
        fitToScreen: function() {
            var canvasArea = document.querySelector('.vbp-canvas-wrapper');
            var canvas = document.querySelector('.vbp-canvas');
            if (canvasArea && canvas) {
                var areaWidth = canvasArea.clientWidth - 40;
                var settings = Alpine.store('vbp').settings;
                var pageWidthUnit = settings.pageWidthUnit || 'px';
                var pageWidth = settings.pageWidth || '1200';
                // Si es porcentaje, usar el ancho real del área
                if (pageWidthUnit === '%' || (typeof pageWidth === 'string' && pageWidth.includes('%'))) {
                    var percent = parseInt(pageWidth) || 100;
                    var canvasWidth = (areaWidth * percent) / 100;
                } else {
                    var canvasWidth = parseInt(pageWidth) || 1200;
                }
                var fitZoom = Math.floor((areaWidth / canvasWidth) * 100);
                this.setZoom(Math.min(fitZoom, 100));
            }
        },
        setDevicePreview: function(device) {
            this.devicePreview = device;
            Alpine.store('vbp').devicePreview = device;
            // Ajustar ancho del canvas según dispositivo
            var widths = { desktop: 1200, tablet: 768, mobile: 375 };
            if (widths[device]) {
                Alpine.store('vbp').settings.previewWidth = widths[device];
            }
        },

        // ============ SPLIT SCREEN / RESPONSIVE PREVIEW ============

        /**
         * Activa/desactiva el modo split-screen
         */
        toggleSplitScreen: function() {
            this.splitScreenMode = !this.splitScreenMode;

            if (this.splitScreenMode) {
                this.initSplitScreen();
                this.showNotification('Modo split-screen activado', 'info');
            } else {
                this.destroySplitScreen();
                this.showNotification('Modo split-screen desactivado', 'info');
            }
        },

        /**
         * Inicializa el modo split-screen
         */
        initSplitScreen: function() {
            var self = this;

            // Crear contenedor split si no existe
            this.$nextTick(function() {
                var canvasArea = document.querySelector('.vbp-canvas-area');
                if (!canvasArea) return;

                // Añadir clase split-screen
                canvasArea.classList.add('vbp-split-screen-active');

                // Inicializar sincronización de scroll
                if (self.splitScreenSyncScroll) {
                    self.initSplitScreenScrollSync();
                }
            });
        },

        /**
         * Destruye el modo split-screen
         */
        destroySplitScreen: function() {
            var canvasArea = document.querySelector('.vbp-canvas-area');
            if (canvasArea) {
                canvasArea.classList.remove('vbp-split-screen-active');
            }

            // Remover listeners de scroll
            this.removeSplitScreenScrollSync();
        },

        /**
         * Cambia los dispositivos mostrados en split-screen
         */
        setSplitScreenDevices: function(device1, device2) {
            this.splitScreenDevices = [device1, device2];
        },

        /**
         * Toggle sincronización de scroll en split-screen
         */
        toggleSplitScreenSyncScroll: function() {
            this.splitScreenSyncScroll = !this.splitScreenSyncScroll;

            if (this.splitScreenSyncScroll) {
                this.initSplitScreenScrollSync();
                this.showNotification('Sincronización de scroll activada', 'info');
            } else {
                this.removeSplitScreenScrollSync();
                this.showNotification('Sincronización de scroll desactivada', 'info');
            }
        },

        /**
         * Inicializa la sincronización de scroll entre paneles
         */
        initSplitScreenScrollSync: function() {
            var self = this;
            var paneles = document.querySelectorAll('.vbp-split-panel');

            if (paneles.length < 2) return;

            this._splitScrollHandler = function(event) {
                if (self._isSyncingScroll) return;
                self._isSyncingScroll = true;

                var sourcePanel = event.target;
                var scrollPercentage = sourcePanel.scrollTop / (sourcePanel.scrollHeight - sourcePanel.clientHeight);

                paneles.forEach(function(panel) {
                    if (panel !== sourcePanel) {
                        var targetScrollTop = scrollPercentage * (panel.scrollHeight - panel.clientHeight);
                        panel.scrollTop = targetScrollTop;
                    }
                });

                requestAnimationFrame(function() {
                    self._isSyncingScroll = false;
                });
            };

            paneles.forEach(function(panel) {
                panel.addEventListener('scroll', self._splitScrollHandler);
            });
        },

        /**
         * Remueve la sincronización de scroll
         */
        removeSplitScreenScrollSync: function() {
            var self = this;
            var paneles = document.querySelectorAll('.vbp-split-panel');

            if (this._splitScrollHandler) {
                paneles.forEach(function(panel) {
                    panel.removeEventListener('scroll', self._splitScrollHandler);
                });
                this._splitScrollHandler = null;
            }
        },

        /**
         * Obtiene el ancho para un dispositivo
         */
        getDeviceWidth: function(device) {
            var widths = {
                desktop: 1200,
                laptop: 1024,
                tablet: 768,
                mobile: 375
            };
            return widths[device] || 1200;
        },

        /**
         * Obtiene la etiqueta para un dispositivo
         */
        getDeviceLabel: function(device) {
            var labels = {
                desktop: 'Escritorio (1200px)',
                laptop: 'Portátil (1024px)',
                tablet: 'Tablet (768px)',
                mobile: 'Móvil (375px)'
            };
            return labels[device] || device;
        },

        /**
         * Obtiene el icono SVG para un dispositivo
         */
        getDeviceIcon: function(device) {
            var icons = {
                desktop: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
                laptop: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v8H4V6z"/><path d="M2 18h20"/></svg>',
                tablet: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>',
                mobile: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M12 18h.01"/></svg>'
            };
            return icons[device] || icons.desktop;
        },

        // Page Settings
        openPageSettings: function() {
            this.showPageSettings = true;
        },

        savePageSettings: function() {
            var settings = Alpine.store('vbp').settings;
            settings.pageSettings = this.pageSettings;
            Alpine.store('vbp').settings = settings;
            Alpine.store('vbp').isDirty = true;
            this.showPageSettings = false;
            this.showNotification('Configuración de página guardada', 'success');
        },

        // ============ TEMPLATES ============
        loadTemplates: function() {
            var self = this;
            // Cargar templates desde VBP_Config si están disponibles
            if (typeof VBP_Config !== 'undefined' && VBP_Config.templates) {
                this.templates = VBP_Config.templates.library || [];
                this.userTemplates = VBP_Config.templates.user || [];
            } else {
                // Cargar desde REST API si no están en config
                fetch(VBP_Config.restUrl + 'templates', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': VBP_Config.restNonce
                    }
                })
                .then(function(response) {
                    // Verificar que la respuesta sea OK y contenga JSON
                    var contentType = response.headers.get('content-type');
                    if (!response.ok) {
                        throw new Error('Error HTTP: ' + response.status);
                    }
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Respuesta no es JSON válido');
                    }
                    return response.json();
                })
                .then(function(data) {
                    self.templates = data.library || [];
                    self.userTemplates = data.user || [];
                })
                .catch(function(error) {
                    console.warn('Error cargando templates (usando librería vacía):', error.message);
                    self.templates = [];
                    self.userTemplates = [];
                });
            }
        },

        get filteredTemplates() {
            var self = this;
            var results = this.templates.slice();

            // Filtrar por búsqueda
            if (this.templateSearch) {
                var searchLower = this.templateSearch.toLowerCase();
                results = results.filter(function(template) {
                    return template.title.toLowerCase().indexOf(searchLower) !== -1 ||
                           (template.description && template.description.toLowerCase().indexOf(searchLower) !== -1);
                });
            }

            // Filtrar por categoría
            if (this.templateCategory) {
                results = results.filter(function(template) {
                    return template.category === self.templateCategory;
                });
            }

            return results;
        },

        selectTemplate: function(template) {
            if (confirm('¿Deseas aplicar este template? Se reemplazará el contenido actual.')) {
                this.applyTemplate(template);
            }
        },

        previewTemplate: function(template) {
            if (template.preview_url) {
                window.open(template.preview_url, '_blank');
            } else {
                this.showNotification('Vista previa no disponible para este template', 'info');
            }
        },

        applyTemplate: function(template) {
            var self = this;

            // Si el template tiene elementos directamente, aplicarlos
            if (template.elements) {
                Alpine.store('vbp').elements = sanitizeElements(template.elements);
                if (template.settings) {
                    Alpine.store('vbp').settings = Object.assign({}, Alpine.store('vbp').settings, template.settings);
                }
                Alpine.store('vbp').isDirty = true;
                this.showTemplatesModal = false;
                this.showNotification('Template aplicado correctamente', 'success');
                return;
            }

            // Si es un template de usuario o librería, aplicar via API
            var templateId = template.id;

            fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/apply-template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ template_id: templateId })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success && result.document) {
                    Alpine.store('vbp').elements = sanitizeElements(result.document.elements || []);
                    if (result.document.settings) {
                        Alpine.store('vbp').settings = Object.assign({}, Alpine.store('vbp').settings, result.document.settings);
                    }
                    Alpine.store('vbp').isDirty = true;
                    self.showTemplatesModal = false;
                    self.showNotification('Template aplicado correctamente', 'success');
                } else {
                    throw new Error(result.message || 'Error al aplicar template');
                }
            })
            .catch(function(error) {
                self.showNotification('Error al aplicar template: ' + error.message, 'error');
            });
        },

        saveAsTemplate: function() {
            this.newTemplateName = this.documentTitle || '';
            this.showSaveTemplateModal = true;
        },

        confirmSaveTemplate: function() {
            var self = this;
            if (!this.newTemplateName.trim()) return;

            this.isSavingTemplate = true;

            fetch(VBP_Config.restUrl + 'templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    post_id: VBP_Config.postId,
                    name: this.newTemplateName,
                    category: this.newTemplateCategory,
                    description: this.newTemplateDescription
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.showNotification('Template guardado correctamente', 'success');
                    self.showSaveTemplateModal = false;
                    self.newTemplateName = '';
                    self.newTemplateDescription = '';
                    // Recargar templates
                    self.loadTemplates();
                } else {
                    throw new Error(result.message || 'Error al guardar template');
                }
                self.isSavingTemplate = false;
            })
            .catch(function(error) {
                self.showNotification('Error al guardar template: ' + error.message, 'error');
                self.isSavingTemplate = false;
            });
        },

        deleteTemplate: function(template) {
            var self = this;
            if (!confirm('¿Estás seguro de eliminar este template?')) return;

            fetch(VBP_Config.restUrl + 'templates/' + template.id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.showNotification('Template eliminado', 'success');
                    self.loadTemplates();
                } else {
                    throw new Error(result.message || 'Error al eliminar template');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        // ============ GLOBAL WIDGETS ============
        globalWidgets: [],
        showGlobalWidgetsModal: false,
        showSaveGlobalWidgetModal: false,
        newGlobalWidgetName: '',
        newGlobalWidgetCategory: 'general',
        isSavingGlobalWidget: false,

        loadGlobalWidgets: function() {
            var self = this;
            fetch(VBP_Config.restUrl + 'global-widgets', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (!response.ok || !contentType || !contentType.includes('application/json')) {
                    throw new Error('Error al cargar widgets globales');
                }
                return response.json();
            })
            .then(function(data) {
                self.globalWidgets = Array.isArray(data) ? data : [];
            })
            .catch(function(error) {
                console.warn('Error cargando widgets globales:', error.message);
                self.globalWidgets = [];
            });
        },

        saveAsGlobalWidget: function() {
            var store = Alpine.store('vbp');
            var selectedIds = store.selection.elementIds;

            if (selectedIds.length === 0) {
                this.showNotification('Selecciona un elemento para guardarlo como widget global', 'warning');
                return;
            }

            if (selectedIds.length > 1) {
                this.showNotification('Solo puedes guardar un elemento a la vez como widget global', 'warning');
                return;
            }

            var element = store.getElementById(selectedIds[0]);
            if (!element) {
                this.showNotification('No se encontró el elemento seleccionado', 'error');
                return;
            }

            this.newGlobalWidgetName = element.name || element.type;
            this.showSaveGlobalWidgetModal = true;
        },

        confirmSaveGlobalWidget: function() {
            var self = this;
            if (!this.newGlobalWidgetName.trim()) return;

            var store = Alpine.store('vbp');
            var selectedId = store.selection.elementIds[0];
            var element = store.getElementById(selectedId);

            if (!element) {
                this.showNotification('No se encontró el elemento', 'error');
                return;
            }

            this.isSavingGlobalWidget = true;

            // Clonar el elemento para guardarlo
            var elementToSave = JSON.parse(JSON.stringify(element));
            delete elementToSave.id; // Remover ID para evitar conflictos

            fetch(VBP_Config.restUrl + 'global-widgets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    title: this.newGlobalWidgetName,
                    element: elementToSave,
                    category: this.newGlobalWidgetCategory
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.id) {
                    self.showNotification('Widget global guardado correctamente', 'success');
                    self.showSaveGlobalWidgetModal = false;
                    self.newGlobalWidgetName = '';
                    self.loadGlobalWidgets();
                } else {
                    throw new Error(result.error || 'Error al guardar widget global');
                }
                self.isSavingGlobalWidget = false;
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
                self.isSavingGlobalWidget = false;
            });
        },

        insertGlobalWidget: function(widget) {
            var self = this;
            var store = Alpine.store('vbp');

            // Obtener los datos completos del widget
            fetch(VBP_Config.restUrl + 'global-widgets/' + widget.id, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.element) {
                    // Crear un nuevo elemento de tipo global_widget
                    var newElement = {
                        id: 'el_' + Date.now(),
                        type: 'global_widget',
                        name: widget.title,
                        data: {
                            globalWidgetId: widget.id,
                            originalType: data.element.type
                        },
                        styles: {},
                        visible: true,
                        locked: false
                    };

                    store.addElement(newElement);
                    self.showNotification('Widget global insertado', 'success');
                    self.showGlobalWidgetsModal = false;
                } else {
                    throw new Error('No se encontraron datos del widget');
                }
            })
            .catch(function(error) {
                self.showNotification('Error al insertar widget: ' + error.message, 'error');
            });
        },

        deleteGlobalWidget: function(widget) {
            var self = this;
            if (!confirm('¿Estás seguro de eliminar este widget global? Se usará en ' + (widget.usageCount || 0) + ' páginas.')) return;

            fetch(VBP_Config.restUrl + 'global-widgets/' + widget.id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.message) {
                    self.showNotification('Widget global eliminado', 'success');
                    self.loadGlobalWidgets();
                } else {
                    throw new Error(result.error || 'Error al eliminar widget');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        selectOgImage: function() {
            var self = this;
            if (typeof wp !== 'undefined' && wp.media) {
                var mediaUploader = wp.media({
                    title: 'Seleccionar imagen para redes sociales',
                    button: { text: 'Usar esta imagen' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    self.pageSettings.ogImage = attachment.url;
                });

                mediaUploader.open();
            }
        },

        selectElement: function(element, event) {
            if (element.locked) return;
            var multiSelect = event.ctrlKey || event.metaKey || event.shiftKey;
            if (multiSelect) { Alpine.store('vbp').toggleSelection(element.id); }
            else { Alpine.store('vbp').setSelection([element.id]); }
        },

        clearSelection: function() { Alpine.store('vbp').clearSelection(); },
        isSelected: function(elementId) { return Alpine.store('vbp').selection.elementIds.includes(elementId); },

        editElement: function(element) {
            var elementEl = document.querySelector('[data-element-id="' + element.id + '"] [contenteditable]');
            if (elementEl) { elementEl.focus(); }
        },

        deleteElement: function(element) {
            if (confirm(VBP_Config.strings.deleteConfirm)) {
                Alpine.store('vbp').removeElement(element.id);
            }
        },

        duplicateElement: function(element) {
            Alpine.store('vbp').duplicateElement(element.id);
            this.showNotification(VBP_Config.strings.duplicated, 'success');
        },

        moveElementUp: function(element) {
            var index = Alpine.store('vbp').elements.findIndex(function(el) { return el.id === element.id; });
            if (index > 0) { Alpine.store('vbp').moveElement(index, index - 1); }
        },

        moveElementDown: function(element) {
            var index = Alpine.store('vbp').elements.findIndex(function(el) { return el.id === element.id; });
            if (index < Alpine.store('vbp').elements.length - 1) { Alpine.store('vbp').moveElement(index, index + 1); }
        },

        getElementClasses: function(element) {
            return { 'selected': this.isSelected(element.id), 'hidden': element.visible === false, 'locked': element.locked };
        },

        // ============ RENDER TWO COLUMN CONTENT ============
        // Renderiza el contenido de una columna en bloques two_columns
        renderTwoColumnContent: function(colData, lado) {
            if (!colData || !colData.type) {
                return '<div style="text-align: center; color: #6b7280; padding: 20px; font-size: 12px;">📥 Columna ' + lado + '</div>';
            }

            var colType = colData.type;
            var colContent = colData.data || {};
            var ds = (typeof VBP_Config !== 'undefined' && VBP_Config.designSettings) ? VBP_Config.designSettings : {};
            var primaryColor = ds.primary_color || '#3b82f6';
            var textColor = ds.text_color || '#1f2937';
            var textMutedColor = ds.text_muted_color || '#6b7280';
            var buttonRadius = (ds.button_border_radius || 8) + 'px';

            // Contact Info
            if (colType === 'contact_info') {
                var infoHtml = '<div class="vbp-contact-info">';
                infoHtml += '<h3 contenteditable="true" style="margin: 0 0 16px; font-size: 20px; color: ' + textColor + ';">' + (colContent.titulo || 'Información') + '</h3>';
                var items = colContent.items || [];
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    infoHtml += '<div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid #eee;">';
                    infoHtml += '<span style="font-size: 20px;">' + (item.icono || '📌') + '</span>';
                    infoHtml += '<div>';
                    infoHtml += '<div style="font-weight: 600; color: ' + textColor + '; font-size: 14px;">' + (item.titulo || 'Campo') + '</div>';
                    infoHtml += '<div style="color: ' + textMutedColor + '; font-size: 13px;">' + (item.valor || '') + '</div>';
                    infoHtml += '</div></div>';
                }
                if (items.length === 0) {
                    infoHtml += '<div style="color: ' + textMutedColor + '; padding: 12px; text-align: center;">Sin información</div>';
                }
                infoHtml += '</div>';
                return infoHtml;
            }

            // Contact Form
            if (colType === 'contact_form') {
                var formHtml = '<div class="vbp-contact-form">';
                formHtml += '<h3 contenteditable="true" style="margin: 0 0 16px; font-size: 20px; color: ' + textColor + ';">' + (colContent.titulo || 'Formulario') + '</h3>';
                formHtml += '<form style="display: flex; flex-direction: column; gap: 12px;" onsubmit="return false;">';
                var campos = colContent.campos || [];
                for (var ci = 0; ci < campos.length; ci++) {
                    var campo = campos[ci];
                    var inputStyle = 'width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: 14px; box-sizing: border-box;';
                    formHtml += '<div>';
                    formHtml += '<label style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 13px; color: ' + textColor + ';">' + (campo.label || campo.nombre || 'Campo') + (campo.requerido ? ' *' : '') + '</label>';
                    if (campo.tipo === 'textarea') {
                        formHtml += '<textarea rows="3" style="' + inputStyle + ' resize: vertical;" placeholder="' + (campo.placeholder || '') + '"></textarea>';
                    } else if (campo.tipo === 'select') {
                        formHtml += '<select style="' + inputStyle + '">';
                        formHtml += '<option value="">Selecciona...</option>';
                        var opciones = campo.opciones || [];
                        for (var oi = 0; oi < opciones.length; oi++) {
                            formHtml += '<option>' + opciones[oi] + '</option>';
                        }
                        formHtml += '</select>';
                    } else {
                        formHtml += '<input type="' + (campo.tipo || 'text') + '" style="' + inputStyle + '" placeholder="' + (campo.placeholder || '') + '">';
                    }
                    formHtml += '</div>';
                }
                formHtml += '<button type="button" style="padding: 12px 24px; background: ' + primaryColor + '; color: #fff; border: none; border-radius: ' + buttonRadius + '; cursor: pointer; font-weight: 600;">' + (colContent.boton_texto || 'Enviar') + '</button>';
                formHtml += '</form></div>';
                return formHtml;
            }

            // Generic/Unknown type
            return '<div style="padding: 16px; background: #f0f0f0; border-radius: 8px; text-align: center; color: ' + textMutedColor + ';">Tipo: ' + colType + '</div>';
        },

        // ============ RENDER ELEMENT - TODOS LOS BLOQUES ============
        renderElement: function(element) {
            var type = element.type;
            var data = element.data || {};
            var styles = element.styles || {};
            var customStyle = this.buildInlineStyles(styles);

            // ============ SECCIONES ============
            // Obtener colores de Design Settings
            var ds = (typeof VBP_Config !== 'undefined' && VBP_Config.designSettings) ? VBP_Config.designSettings : {};
            var primaryColor = ds.primary_color || '#3b82f6';
            var secondaryColor = ds.secondary_color || '#8b5cf6';
            var textColor = ds.text_color || '#1f2937';
            var textMutedColor = ds.text_muted_color || '#6b7280';
            var buttonRadius = (ds.button_border_radius || 8) + 'px';
            var cardRadius = (ds.card_border_radius || 12) + 'px';
            var sectionPaddingY = (ds.section_padding_y || 80) + 'px';
            var sectionPaddingX = (ds.section_padding_x || 40) + 'px';
            var gridGap = (ds.grid_gap || 24) + 'px';
            var cardPadding = (ds.card_padding || 24) + 'px';
            var fontH1 = (ds.font_size_h1 || 48) + 'px';
            var fontH2 = (ds.font_size_h2 || 36) + 'px';
            var fontH3 = (ds.font_size_h3 || 28) + 'px';

            if (type === 'hero') {
                var heroVariant = element.variant || 'centered';

                // Colores personalizables
                var tituloColor = data.titulo_color || '#ffffff';
                var subtituloColor = data.subtitulo_color || '#e0e0e0';
                var botonFondo = data.boton_color_fondo || primaryColor;
                var botonTexto = data.boton_color_texto || '#ffffff';
                var colorFondo = data.color_fondo || '#1a1a2e';
                var overlayColor = data.overlay_color || 'rgba(0,0,0,0.3)';
                var overlayOpacity = (data.overlay_opacity || 30) / 100;
                var alineacion = data.alineacion || 'center';
                var altura = data.altura || 'auto';

                // Segundo botón
                var boton2Html = '';
                if (data.boton_2_texto) {
                    var boton2Fondo = data.boton_2_color_fondo || 'transparent';
                    var boton2Texto = data.boton_2_color_texto || '#ffffff';
                    var boton2Borde = data.boton_2_color_borde || '#ffffff';
                    boton2Html = ' <a href="' + (data.boton_2_url || '#') + '" contenteditable="true" data-field="boton_2_texto" class="flavor-button flavor-button--secondary" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + boton2Fondo + '; color: ' + boton2Texto + '; border: 2px solid ' + boton2Borde + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight); margin-left: 12px;">' + data.boton_2_texto + '</a>';
                }

                // Estilo de fondo
                var bgStyle = data.imagen_fondo
                    ? 'background-image: url(' + data.imagen_fondo + '); background-size: cover; background-position: center;'
                    : 'background: ' + colorFondo + ';';

                // Altura de la sección
                var alturaStyle = altura !== 'auto' ? 'min-height: ' + altura + ';' : '';

                // Variante: Centrado (default)
                if (heroVariant === 'centered' || heroVariant === 'default') {
                    return '<section class="vbp-hero vbp-hero--centered flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; text-align: ' + alineacion + '; ' + alturaStyle + ' display: flex; align-items: center; justify-content: center; ' + bgStyle + customStyle + '">' +
                        '<div class="vbp-hero-overlay" style="background: ' + overlayColor + '; padding: 40px; border-radius: ' + cardRadius + '; display: inline-block; max-width: 800px;">' +
                        '<h1 contenteditable="true" data-field="titulo" style="font-size: ' + fontH1 + '; margin: 0 0 16px; font-weight: 700; font-family: var(--flavor-font-headings); color: ' + tituloColor + ';">' + (data.titulo || 'Título Principal') + '</h1>' +
                        '<p contenteditable="true" data-field="subtitulo" style="font-size: 20px; margin: 0 0 32px; line-height: var(--flavor-line-height-base); color: ' + subtituloColor + ';">' + (data.subtitulo || 'Subtítulo descriptivo que explica el valor de tu propuesta') + '</p>' +
                        '<div class="vbp-hero-buttons">' +
                        '<a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight); font-size: var(--flavor-button-font-size);">' + (data.boton_texto || 'Comenzar ahora') + '</a>' +
                        boton2Html +
                        '</div></div></section>';
                }

                // Variante: Izquierda
                if (heroVariant === 'left') {
                    return '<section class="vbp-hero vbp-hero--left flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; text-align: left; ' + alturaStyle + ' display: flex; align-items: center; ' + bgStyle + customStyle + '">' +
                        '<div style="max-width: 600px; background: ' + overlayColor + '; padding: 40px; border-radius: ' + cardRadius + ';">' +
                        '<h1 contenteditable="true" data-field="titulo" style="font-size: ' + fontH1 + '; margin: 0 0 16px; font-weight: 700; font-family: var(--flavor-font-headings); color: ' + tituloColor + ';">' + (data.titulo || 'Título Principal') + '</h1>' +
                        '<p contenteditable="true" data-field="subtitulo" style="font-size: 20px; margin: 0 0 32px; line-height: var(--flavor-line-height-base); color: ' + subtituloColor + ';">' + (data.subtitulo || 'Subtítulo descriptivo') + '</p>' +
                        '<div class="vbp-hero-buttons">' +
                        '<a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight);">' + (data.boton_texto || 'Comenzar') + '</a>' +
                        boton2Html +
                        '</div></div></section>';
                }

                // Variante: Dividido (Split)
                if (heroVariant === 'split') {
                    return '<section class="vbp-hero vbp-hero--split flavor-component" style="padding: 0; display: grid; grid-template-columns: 1fr 1fr; min-height: 500px; ' + customStyle + '">' +
                        '<div style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; display: flex; flex-direction: column; justify-content: center; background: ' + colorFondo + ';">' +
                        '<h1 contenteditable="true" data-field="titulo" style="font-size: ' + fontH1 + '; margin: 0 0 16px; font-weight: 700; color: ' + tituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Título Principal') + '</h1>' +
                        '<p contenteditable="true" data-field="subtitulo" style="font-size: 18px; margin: 0 0 32px; color: ' + subtituloColor + '; line-height: var(--flavor-line-height-base);">' + (data.subtitulo || 'Subtítulo descriptivo') + '</p>' +
                        '<div class="vbp-hero-buttons"><a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight);">' + (data.boton_texto || 'Comenzar') + '</a>' + boton2Html + '</div>' +
                        '</div>' +
                        '<div style="' + (data.imagen_fondo ? 'background-image: url(' + data.imagen_fondo + '); background-size: cover; background-position: center;' : 'background: linear-gradient(135deg, ' + primaryColor + ' 0%, ' + secondaryColor + ' 100%);') + '"></div></section>';
                }

                // Variante: Minimalista
                if (heroVariant === 'minimal') {
                    return '<section class="vbp-hero vbp-hero--minimal flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; text-align: center; background: ' + colorFondo + '; ' + alturaStyle + ' display: flex; align-items: center; justify-content: center; flex-direction: column; ' + customStyle + '">' +
                        '<h1 contenteditable="true" data-field="titulo" style="font-size: ' + fontH1 + '; margin: 0 0 16px; font-weight: 700; color: ' + tituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Título Principal') + '</h1>' +
                        '<p contenteditable="true" data-field="subtitulo" style="font-size: 20px; margin: 0 0 32px; color: ' + subtituloColor + '; max-width: 600px; line-height: var(--flavor-line-height-base);">' + (data.subtitulo || 'Subtítulo descriptivo') + '</p>' +
                        '<div class="vbp-hero-buttons">' +
                        '<a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight);">' + (data.boton_texto || 'Comenzar') + '</a>' +
                        boton2Html +
                        '</div></section>';
                }

                // Fallback al centered
                return '<section class="vbp-hero flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; text-align: ' + alineacion + '; ' + alturaStyle + ' display: flex; align-items: center; justify-content: center; ' + bgStyle + customStyle + '">' +
                    '<div class="vbp-hero-overlay" style="background: ' + overlayColor + '; padding: 40px; border-radius: ' + cardRadius + '; display: inline-block; max-width: 800px;">' +
                    '<h1 contenteditable="true" data-field="titulo" style="font-size: ' + fontH1 + '; margin: 0 0 16px; font-weight: 700; font-family: var(--flavor-font-headings); color: ' + tituloColor + ';">' + (data.titulo || 'Título Principal') + '</h1>' +
                    '<p contenteditable="true" data-field="subtitulo" style="font-size: 20px; margin: 0 0 32px; color: ' + subtituloColor + ';">' + (data.subtitulo || 'Subtítulo descriptivo') + '</p>' +
                    '<div class="vbp-hero-buttons">' +
                    '<a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none;">' + (data.boton_texto || 'Comenzar') + '</a>' +
                    boton2Html +
                    '</div></div></section>';
            }

            if (type === 'features') {
                var featuresVariant = element.variant || 'grid';
                var items = data.items || [
                    { icono: '⚡', titulo: 'Rápido', descripcion: 'Implementación en minutos' },
                    { icono: '🔒', titulo: 'Seguro', descripcion: 'Protección de datos garantizada' },
                    { icono: '📱', titulo: 'Responsive', descripcion: 'Funciona en todos los dispositivos' }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secTextoColor = data.texto_color || textMutedColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardFondo = data.card_fondo_color || '#ffffff';
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var cardIcono = data.card_icono_color || primaryColor;
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var featuresHtml = '<section class="vbp-features vbp-features--' + featuresVariant + ' flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Nuestras Características') + '</h2>';

                // Variante: Grid (default)
                if (featuresVariant === 'grid' || featuresVariant === 'default') {
                    featuresHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: ' + gridGap + '; max-width: var(--flavor-container-max); margin: 0 auto;">';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        featuresHtml += '<div class="vbp-feature-card flavor-card" style="padding: ' + cardPadding + '; background: ' + cardFondo + '; border: 1px solid ' + cardBorde + '; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow); text-align: center;">' +
                            '<div style="font-size: 48px; margin-bottom: 16px; color: ' + cardIcono + ';">' + (item.icono || '✨') + '</div>' +
                            '<h3 contenteditable="true" style="font-size: 20px; margin: 0 0 12px; color: ' + cardTitulo + '; font-family: var(--flavor-font-headings);">' + (item.titulo || 'Característica') + '</h3>' +
                            '<p contenteditable="true" style="margin: 0; color: ' + cardTexto + '; line-height: var(--flavor-line-height-base);">' + (item.descripcion || 'Descripción') + '</p></div>';
                    }
                    featuresHtml += '</div>';
                }

                // Variante: Lista
                if (featuresVariant === 'list') {
                    featuresHtml += '<div style="max-width: 700px; margin: 0 auto;">';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        featuresHtml += '<div style="display: flex; align-items: flex-start; gap: 20px; padding: 24px 0; border-bottom: 1px solid ' + cardBorde + ';">' +
                            '<div style="font-size: 32px; flex-shrink: 0; color: ' + cardIcono + ';">' + (item.icono || '✨') + '</div>' +
                            '<div style="flex: 1;"><h3 contenteditable="true" style="font-size: 18px; margin: 0 0 8px; color: ' + cardTitulo + ';">' + (item.titulo || 'Característica') + '</h3>' +
                            '<p contenteditable="true" style="margin: 0; color: ' + cardTexto + ';">' + (item.descripcion || 'Descripción') + '</p></div></div>';
                    }
                    featuresHtml += '</div>';
                }

                // Variante: Iconos grandes
                if (featuresVariant === 'icons') {
                    featuresHtml += '<div style="display: flex; justify-content: center; gap: 60px; flex-wrap: wrap; max-width: 900px; margin: 0 auto;">';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        featuresHtml += '<div style="text-align: center; flex: 0 0 200px;">' +
                            '<div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + '); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: white;">' + (item.icono || '✨') + '</div>' +
                            '<h3 contenteditable="true" style="font-size: 18px; margin: 0 0 8px; color: ' + cardTitulo + ';">' + (item.titulo || 'Característica') + '</h3>' +
                            '<p contenteditable="true" style="margin: 0; font-size: 14px; color: ' + cardTexto + ';">' + (item.descripcion || 'Descripción') + '</p></div>';
                    }
                    featuresHtml += '</div>';
                }

                // Variante: Tarjetas con borde
                if (featuresVariant === 'cards') {
                    featuresHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: ' + gridGap + '; max-width: var(--flavor-container-max); margin: 0 auto;">';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        featuresHtml += '<div style="padding: ' + cardPadding + '; background: ' + cardFondo + '; border: 2px solid ' + cardBorde + '; border-radius: ' + cardRadius + '; text-align: left; transition: border-color 0.2s;">' +
                            '<div style="width: 48px; height: 48px; background: ' + acentoColor + '15; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; color: ' + cardIcono + ';">' + (item.icono || '✨') + '</div>' +
                            '<h3 contenteditable="true" style="font-size: 18px; margin: 0 0 8px; color: ' + cardTitulo + ';">' + (item.titulo || 'Característica') + '</h3>' +
                            '<p contenteditable="true" style="margin: 0; color: ' + cardTexto + '; font-size: 14px;">' + (item.descripcion || 'Descripción') + '</p></div>';
                    }
                    featuresHtml += '</div>';
                }

                featuresHtml += '</section>';
                return featuresHtml;
            }

            if (type === 'testimonials') {
                var testimonialsVariant = element.variant || 'cards';
                var testimonios = data.items || [
                    { texto: 'Excelente servicio, muy recomendado. Ha superado todas nuestras expectativas.', autor: 'María García', cargo: 'CEO, Empresa X' }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#f8f9fa';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardFondo = data.card_fondo_color || '#ffffff';
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var testiHtml = '<section class="vbp-testimonials vbp-testimonials--' + testimonialsVariant + ' flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Lo que dicen nuestros clientes') + '</h2>';

                // Variante: Tarjetas (default)
                if (testimonialsVariant === 'cards' || testimonialsVariant === 'default') {
                    testiHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: ' + gridGap + '; max-width: 1000px; margin: 0 auto;">';
                    for (var t = 0; t < testimonios.length; t++) {
                        var testi = testimonios[t];
                        testiHtml += '<div class="flavor-card" style="background: ' + cardFondo + '; border: 1px solid ' + cardBorde + '; padding: ' + cardPadding + '; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow);">' +
                            '<p contenteditable="true" style="font-size: var(--flavor-font-size-base); line-height: var(--flavor-line-height-base); color: ' + cardTexto + '; margin: 0 0 20px; font-style: italic;">"' + (testi.texto || 'Testimonio') + '"</p>' +
                            '<div style="display: flex; align-items: center; gap: 12px;">' +
                            '<div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + '); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">' + (testi.autor ? testi.autor.charAt(0) : 'U') + '</div>' +
                            '<div><div contenteditable="true" style="font-weight: 600; color: ' + cardTitulo + ';">' + (testi.autor || 'Nombre') + '</div>' +
                            '<div contenteditable="true" style="font-size: 14px; color: ' + cardTexto + ';">' + (testi.cargo || 'Cargo') + '</div></div></div></div>';
                    }
                    testiHtml += '</div>';
                }

                // Variante: Citas grandes
                if (testimonialsVariant === 'quotes') {
                    testiHtml += '<div style="max-width: 800px; margin: 0 auto;">';
                    for (var t = 0; t < testimonios.length; t++) {
                        var testi = testimonios[t];
                        testiHtml += '<div style="text-align: center; padding: 40px 0; border-bottom: 1px solid ' + cardBorde + ';">' +
                            '<div style="font-size: 64px; color: ' + acentoColor + '; line-height: 1; margin-bottom: 20px;">❝</div>' +
                            '<p contenteditable="true" style="font-size: 24px; line-height: 1.6; color: ' + cardTitulo + '; margin: 0 0 24px; font-style: italic;">' + (testi.texto || 'Testimonio') + '</p>' +
                            '<div style="display: flex; align-items: center; justify-content: center; gap: 12px;">' +
                            '<div style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + '); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px;">' + (testi.autor ? testi.autor.charAt(0) : 'U') + '</div>' +
                            '<div style="text-align: left;"><div contenteditable="true" style="font-weight: 600; color: ' + cardTitulo + '; font-size: 16px;">' + (testi.autor || 'Nombre') + '</div>' +
                            '<div contenteditable="true" style="color: ' + cardTexto + ';">' + (testi.cargo || 'Cargo') + '</div></div></div></div>';
                    }
                    testiHtml += '</div>';
                }

                // Variante: Minimalista
                if (testimonialsVariant === 'minimal') {
                    testiHtml += '<div style="max-width: 700px; margin: 0 auto;">';
                    for (var t = 0; t < testimonios.length; t++) {
                        var testi = testimonios[t];
                        testiHtml += '<div style="padding: 32px 0; border-left: 4px solid ' + acentoColor + '; padding-left: 24px; margin-bottom: 24px;">' +
                            '<p contenteditable="true" style="font-size: 18px; line-height: 1.7; color: ' + cardTitulo + '; margin: 0 0 16px;">' + (testi.texto || 'Testimonio') + '</p>' +
                            '<div><span contenteditable="true" style="font-weight: 600; color: ' + cardTitulo + ';">— ' + (testi.autor || 'Nombre') + '</span>' +
                            '<span contenteditable="true" style="color: ' + cardTexto + ';">, ' + (testi.cargo || 'Cargo') + '</span></div></div>';
                    }
                    testiHtml += '</div>';
                }

                // Variante: Carrusel (visual placeholder)
                if (testimonialsVariant === 'carousel') {
                    testiHtml += '<div style="max-width: 700px; margin: 0 auto; position: relative;">' +
                        '<div style="text-align: center; padding: 40px; background: ' + cardFondo + '; border: 1px solid ' + cardBorde + '; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow);">';
                    var firstTesti = testimonios[0] || {};
                    testiHtml += '<div style="font-size: 48px; color: ' + acentoColor + '; margin-bottom: 16px;">❝</div>' +
                        '<p contenteditable="true" style="font-size: 20px; line-height: 1.6; color: ' + cardTitulo + '; margin: 0 0 24px;">' + (firstTesti.texto || 'Testimonio') + '</p>' +
                        '<div style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + '); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 24px; margin: 0 auto 12px;">' + (firstTesti.autor ? firstTesti.autor.charAt(0) : 'U') + '</div>' +
                        '<div contenteditable="true" style="font-weight: 600; color: ' + cardTitulo + ';">' + (firstTesti.autor || 'Nombre') + '</div>' +
                        '<div contenteditable="true" style="color: ' + cardTexto + '; font-size: 14px;">' + (firstTesti.cargo || 'Cargo') + '</div>' +
                        '</div>' +
                        '<div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">';
                    for (var d = 0; d < testimonios.length; d++) {
                        testiHtml += '<div style="width: 10px; height: 10px; border-radius: 50%; background: ' + (d === 0 ? acentoColor : cardBorde) + ';"></div>';
                    }
                    testiHtml += '</div></div>';
                }

                testiHtml += '</section>';
                return testiHtml;
            }

            if (type === 'pricing') {
                var planes = data.items || [
                    { nombre: 'Básico', precio: '9', periodo: '/mes', caracteristicas: ['5 usuarios', '10GB almacenamiento', 'Soporte email'], destacado: false },
                    { nombre: 'Pro', precio: '29', periodo: '/mes', caracteristicas: ['25 usuarios', '100GB almacenamiento', 'Soporte prioritario'], destacado: true },
                    { nombre: 'Enterprise', precio: '99', periodo: '/mes', caracteristicas: ['Usuarios ilimitados', '1TB almacenamiento', 'Soporte 24/7'], destacado: false }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secSubtituloColor = data.subtitulo_color || textMutedColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardFondo = data.card_fondo_color || '#ffffff';
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;
                var botonFondo = data.boton_color_fondo || primaryColor;
                var botonTexto = data.boton_color_texto || '#ffffff';
                var destacadoFondo = data.destacado_fondo || '#eff6ff';
                var destacadoBorde = data.destacado_borde || acentoColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var html = '<section class="vbp-pricing flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 16px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Planes y Precios') + '</h2>' +
                    '<p contenteditable="true" data-field="subtitulo" style="text-align: center; color: ' + secSubtituloColor + '; margin: 0 0 48px;">' + (data.subtitulo || 'Elige el plan que mejor se adapte a tus necesidades') + '</p>' +
                    '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: ' + gridGap + '; max-width: 1000px; margin: 0 auto;">';
                for (var p = 0; p < planes.length; p++) {
                    var plan = planes[p];
                    var destacadoStyle = plan.destacado ? 'transform: scale(1.05); border: 2px solid ' + destacadoBorde + '; background: ' + destacadoFondo + ';' : 'border: 1px solid ' + cardBorde + '; background: ' + cardFondo + ';';
                    html += '<div class="flavor-card" style="padding: ' + cardPadding + '; border-radius: ' + cardRadius + '; text-align: center; ' + destacadoStyle + '">' +
                        (plan.destacado ? '<div style="background: ' + acentoColor + '; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: inline-block; margin-bottom: 16px;">MÁS POPULAR</div>' : '') +
                        '<h3 contenteditable="true" style="font-size: 24px; margin: 0 0 8px; color: ' + cardTitulo + '; font-family: var(--flavor-font-headings);">' + (plan.nombre || 'Plan') + '</h3>' +
                        '<div style="font-size: 48px; font-weight: 700; color: ' + acentoColor + '; margin: 16px 0;">' + (plan.precio || '0') + '<span style="font-size: 16px; color: ' + cardTexto + ';">' + (plan.periodo || '/mes') + '</span></div>' +
                        '<ul style="list-style: none; padding: 0; margin: 24px 0; text-align: left;">';
                    var caracteristicas = plan.caracteristicas || [];
                    for (var c = 0; c < caracteristicas.length; c++) {
                        html += '<li style="padding: 8px 0; color: ' + cardTexto + ';"><span style="color: ' + acentoColor + '; margin-right: 8px;">✓</span>' + caracteristicas[c] + '</li>';
                    }
                    html += '</ul><button class="flavor-button" style="width: 100%; padding: var(--flavor-button-py); background: ' + (plan.destacado ? botonFondo : cardBorde) + '; color: ' + (plan.destacado ? botonTexto : cardTitulo) + '; border: none; border-radius: ' + buttonRadius + '; font-size: var(--flavor-button-font-size); cursor: pointer; font-weight: var(--flavor-button-weight);">Elegir plan</button></div>';
                }
                html += '</div></section>';
                return html;
            }

            if (type === 'cta') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || '#ffffff';
                var secSubtituloColor = data.subtitulo_color || 'rgba(255,255,255,0.9)';
                var secFondoTipo = data.seccion_fondo_tipo || 'gradient';
                var secFondoColor = data.seccion_fondo_color || primaryColor;
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var botonFondo = data.boton_color_fondo || '#ffffff';
                var botonTexto = data.boton_color_texto || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ' 0%, ' + secGradienteFin + ' 100%);';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                return '<section class="vbp-cta flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + ' text-align: center; ' + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="font-size: 40px; color: ' + secTituloColor + '; margin: 0 0 16px; font-family: var(--flavor-font-headings);">' + (data.titulo || '¿Listo para empezar?') + '</h2>' +
                    '<p contenteditable="true" data-field="subtitulo" style="font-size: 18px; color: ' + secSubtituloColor + '; margin: 0 0 32px; max-width: 600px; margin-left: auto; margin-right: auto; line-height: var(--flavor-line-height-base);">' + (data.subtitulo || 'Únete a miles de usuarios que ya confían en nosotros') + '</p>' +
                    '<a href="' + (data.boton_url || '#') + '" contenteditable="true" data-field="boton_texto" class="flavor-button" style="display: inline-block; padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight); font-size: 18px;">' + (data.boton_texto || 'Empezar gratis') + '</a>' +
                    '</section>';
            }

            if (type === 'faq') {
                var preguntas = data.items || [
                    { pregunta: '¿Cómo funciona?', respuesta: 'Es muy sencillo, solo tienes que registrarte y empezar a usar la plataforma.' },
                    { pregunta: '¿Puedo cancelar en cualquier momento?', respuesta: 'Sí, puedes cancelar tu suscripción cuando quieras sin penalizaciones.' },
                    { pregunta: '¿Ofrecen soporte técnico?', respuesta: 'Sí, ofrecemos soporte técnico 24/7 para todos nuestros usuarios.' }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var html = '<section class="vbp-faq flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Preguntas Frecuentes') + '</h2>' +
                    '<div style="max-width: 800px; margin: 0 auto;">';
                for (var f = 0; f < preguntas.length; f++) {
                    var faq = preguntas[f];
                    html += '<div style="border-bottom: 1px solid ' + cardBorde + '; padding: 24px 0;">' +
                        '<h3 contenteditable="true" style="font-size: 18px; margin: 0 0 12px; color: ' + cardTitulo + '; cursor: pointer; font-family: var(--flavor-font-headings);"><span style="color: ' + acentoColor + ';">❓</span> ' + (faq.pregunta || 'Pregunta') + '</h3>' +
                        '<p contenteditable="true" style="margin: 0; color: ' + cardTexto + '; line-height: var(--flavor-line-height-base);">' + (faq.respuesta || 'Respuesta') + '</p></div>';
                }
                html += '</div></section>';
                return html;
            }

            if (type === 'contact') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secSubtituloColor = data.subtitulo_color || textMutedColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var botonFondo = data.boton_color_fondo || primaryColor;
                var botonTexto = data.boton_color_texto || '#ffffff';

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                return '<section class="vbp-contact flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<div style="max-width: 600px; margin: 0 auto;">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 16px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Contáctanos') + '</h2>' +
                    '<p contenteditable="true" data-field="subtitulo" style="text-align: center; color: ' + secSubtituloColor + '; margin: 0 0 32px;">' + (data.subtitulo || 'Estaremos encantados de ayudarte') + '</p>' +
                    '<form style="display: flex; flex-direction: column; gap: 16px;">' +
                    '<input type="text" placeholder="Nombre" style="padding: 14px 16px; border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base);">' +
                    '<input type="email" placeholder="Email" style="padding: 14px 16px; border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base);">' +
                    '<textarea placeholder="Mensaje" rows="4" style="padding: 14px 16px; border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base); resize: vertical;"></textarea>' +
                    '<button type="submit" class="flavor-button flavor-button-primary" style="padding: var(--flavor-button-py) var(--flavor-button-px); background: ' + botonFondo + '; color: ' + botonTexto + '; border: none; border-radius: ' + buttonRadius + '; font-size: var(--flavor-button-font-size); cursor: pointer; font-weight: var(--flavor-button-weight);">Enviar mensaje</button>' +
                    '</form></div></section>';
            }

            if (type === 'team') {
                var miembros = data.items || [
                    { nombre: 'Ana García', cargo: 'CEO', bio: 'Fundadora con más de 10 años de experiencia.' },
                    { nombre: 'Carlos López', cargo: 'CTO', bio: 'Experto en tecnología e innovación.' },
                    { nombre: 'María Rodríguez', cargo: 'CMO', bio: 'Especialista en marketing digital.' }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var html = '<section class="vbp-team flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Nuestro Equipo') + '</h2>' +
                    '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: ' + gridGap + '; max-width: 1000px; margin: 0 auto;">';
                for (var m = 0; m < miembros.length; m++) {
                    var miembro = miembros[m];
                    html += '<div style="text-align: center;">' +
                        '<div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + '); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">' + (miembro.nombre ? miembro.nombre.charAt(0) : '?') + '</div>' +
                        '<h3 contenteditable="true" style="font-size: 20px; margin: 0 0 4px; color: ' + cardTitulo + '; font-family: var(--flavor-font-headings);">' + (miembro.nombre || 'Nombre') + '</h3>' +
                        '<p contenteditable="true" style="color: ' + acentoColor + '; margin: 0 0 12px; font-size: 14px;">' + (miembro.cargo || 'Cargo') + '</p>' +
                        '<p contenteditable="true" style="color: ' + cardTexto + '; margin: 0; font-size: 14px; line-height: var(--flavor-line-height-base);">' + (miembro.bio || 'Biografía') + '</p></div>';
                }
                html += '</div></section>';
                return html;
            }

            if (type === 'stats') {
                var estadisticas = data.items || [
                    { numero: '10K+', label: 'Usuarios activos' },
                    { numero: '99%', label: 'Satisfacción' },
                    { numero: '24/7', label: 'Soporte' },
                    { numero: '50+', label: 'Países' }
                ];

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || '#ffffff';
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#1a1a2e';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardTitulo = data.card_titulo_color || '#ffffff';
                var cardTexto = data.card_texto_color || 'rgba(255,255,255,0.7)';
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var html = '<section class="vbp-stats flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + ' color: ' + secTituloColor + '; ' + customStyle + '">' +
                    '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: ' + gridGap + '; max-width: 800px; margin: 0 auto; text-align: center;">';
                for (var s = 0; s < estadisticas.length; s++) {
                    var stat = estadisticas[s];
                    html += '<div>' +
                        '<div contenteditable="true" style="font-size: 48px; font-weight: 700; color: ' + acentoColor + '; margin-bottom: 8px;">' + (stat.numero || '0') + '</div>' +
                        '<div contenteditable="true" style="font-size: 14px; color: ' + cardTexto + '; text-transform: uppercase; letter-spacing: 1px;">' + (stat.label || 'Estadística') + '</div></div>';
                }
                html += '</div></section>';
                return html;
            }

            if (type === 'gallery') {
                var imagenes = data.items || [];
                var imgRadius = (ds.image_border_radius || 8) + 'px';

                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var html = '<section class="vbp-gallery flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Galería') + '</h2>';
                if (imagenes.length > 0) {
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: ' + gridGap + ';">';
                    for (var g = 0; g < imagenes.length; g++) {
                        var img = imagenes[g];
                        html += '<div style="aspect-ratio: 4/3; overflow: hidden; border-radius: ' + imgRadius + ';"><img src="' + img.src + '" alt="' + (img.alt || '') + '" class="flavor-image" style="width: 100%; height: 100%; object-fit: cover;"></div>';
                    }
                    html += '</div>';
                } else {
                    html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: ' + gridGap + ';">';
                    for (var gi = 0; gi < 6; gi++) {
                        html += '<div style="aspect-ratio: 4/3; background: linear-gradient(135deg, #f0f0f0, #e0e0e0); border-radius: ' + imgRadius + '; display: flex; align-items: center; justify-content: center; color: ' + textMutedColor + ';">🖼️ Imagen ' + (gi+1) + '</div>';
                    }
                    html += '</div>';
                }
                html += '</section>';
                return html;
            }

            if (type === 'blog') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardFondo = data.card_fondo_color || '#ffffff';
                var cardBorde = data.card_borde_color || '#e5e7eb';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                return '<section class="vbp-blog flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: ' + fontH2 + '; margin: 0 0 48px; color: ' + secTituloColor + '; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Últimas Noticias') + '</h2>' +
                    '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: ' + gridGap + '; max-width: var(--flavor-container-max); margin: 0 auto;">' +
                    '<div class="flavor-card" style="background: ' + cardFondo + '; border: 1px solid ' + cardBorde + '; border-radius: ' + cardRadius + '; overflow: hidden; box-shadow: var(--flavor-card-shadow);">' +
                    '<div style="height: 200px; background: linear-gradient(135deg, ' + acentoColor + ', ' + secondaryColor + ');"></div>' +
                    '<div style="padding: ' + cardPadding + ';"><span style="color: ' + acentoColor + '; font-size: 12px;">CATEGORÍA</span>' +
                    '<h3 contenteditable="true" style="margin: 8px 0 12px; font-size: 20px; color: ' + cardTitulo + '; font-family: var(--flavor-font-headings);">Título del artículo</h3>' +
                    '<p contenteditable="true" style="color: ' + cardTexto + '; margin: 0 0 16px; line-height: var(--flavor-line-height-base);">Descripción breve del artículo que resume el contenido...</p>' +
                    '<a href="#" style="color: ' + acentoColor + '; text-decoration: none; font-weight: 500;">Leer más →</a></div></div>' +
                    '<div class="flavor-card" style="background: ' + cardFondo + '; border: 1px solid ' + cardBorde + '; border-radius: ' + cardRadius + '; overflow: hidden; box-shadow: var(--flavor-card-shadow);">' +
                    '<div style="height: 200px; background: linear-gradient(135deg, ' + secondaryColor + ', ' + acentoColor + ');"></div>' +
                    '<div style="padding: ' + cardPadding + ';"><span style="color: ' + acentoColor + '; font-size: 12px;">CATEGORÍA</span>' +
                    '<h3 contenteditable="true" style="margin: 8px 0 12px; font-size: 20px; color: ' + cardTitulo + '; font-family: var(--flavor-font-headings);">Otro artículo interesante</h3>' +
                    '<p contenteditable="true" style="color: ' + cardTexto + '; margin: 0 0 16px; line-height: var(--flavor-line-height-base);">Más contenido relevante para tus usuarios...</p>' +
                    '<a href="#" style="color: ' + acentoColor + '; text-decoration: none; font-weight: 500;">Leer más →</a></div></div>' +
                    '</div></section>';
            }

            if (type === 'video-section') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || '#ffffff';
                var secTextoColor = data.texto_color || 'rgba(255,255,255,0.7)';
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#1a1a2e';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var videoFondo = data.card_fondo_color || '#000000';

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                return '<section class="vbp-video flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + customStyle + '">' +
                    '<div style="max-width: 900px; margin: 0 auto; text-align: center;">' +
                    '<h2 contenteditable="true" data-field="titulo" style="color: ' + secTituloColor + '; font-size: ' + fontH2 + '; margin: 0 0 32px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Mira cómo funciona') + '</h2>' +
                    '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: ' + cardRadius + '; background: ' + videoFondo + ';">' +
                    '<div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: ' + secTituloColor + '; font-size: 64px; cursor: pointer;">▶️</div>' +
                    '</div>' +
                    '<p contenteditable="true" data-field="descripcion" style="color: ' + secTextoColor + '; margin: 24px 0 0; font-size: var(--flavor-font-size-base); line-height: var(--flavor-line-height-base);">' + (data.descripcion || 'Descripción del video') + '</p>' +
                    '</div></section>';
            }

            // ============ BÁSICOS ============
            if (type === 'heading') {
                var level = data.level || 'h2';
                var sizes = { h1: fontH1, h2: fontH2, h3: fontH3, h4: '24px', h5: '20px', h6: '18px' };
                return '<' + level + ' contenteditable="true" data-field="text" class="flavor-component" style="margin: 0; padding: 16px; font-size: ' + (sizes[level] || '24px') + '; color: ' + textColor + '; font-family: var(--flavor-font-headings); line-height: var(--flavor-line-height-headings); ' + customStyle + '">' + (data.text || 'Escribe tu encabezado aquí') + '</' + level + '>';
            }

            if (type === 'text') {
                return '<div contenteditable="true" data-field="text" class="flavor-component" style="padding: 16px; line-height: var(--flavor-line-height-base); color: ' + textColor + '; font-family: var(--flavor-font-body); font-size: var(--flavor-font-size-base); ' + customStyle + '">' + (data.text || '<p>Escribe tu texto aquí. Puedes usar <strong>negrita</strong>, <em>cursiva</em> y más formatos usando la barra de herramientas flotante.</p>') + '</div>';
            }

            if (type === 'image') {
                var imgRadius = (ds.image_border_radius || 8) + 'px';
                if (data.src) {
                    return '<figure style="margin: 0; padding: 16px; ' + customStyle + '">' +
                        '<img src="' + data.src + '" alt="' + (data.alt || '') + '" class="flavor-image" style="max-width: 100%; height: auto; border-radius: ' + imgRadius + ';">' +
                        (data.caption ? '<figcaption contenteditable="true" style="text-align: center; margin-top: 12px; color: ' + textMutedColor + '; font-size: 14px;">' + data.caption + '</figcaption>' : '') +
                        '</figure>';
                }
                return '<div style="padding: 60px 40px; background: linear-gradient(135deg, #f0f0f0, #e0e0e0); text-align: center; border-radius: ' + imgRadius + '; margin: 16px; ' + customStyle + '">' +
                    '<div style="font-size: 48px; margin-bottom: 16px;">🖼️</div>' +
                    '<p style="color: ' + textMutedColor + '; margin: 0;">Haz clic para seleccionar una imagen</p></div>';
            }

            if (type === 'button') {
                var buttonVariant = element.variant || 'filled';
                var btnBg, btnColor, btnBorder;

                switch (buttonVariant) {
                    case 'outline':
                        btnBg = 'transparent';
                        btnColor = primaryColor;
                        btnBorder = '2px solid ' + primaryColor;
                        break;
                    case 'ghost':
                        btnBg = 'rgba(59,130,246,0.1)';
                        btnColor = primaryColor;
                        btnBorder = 'none';
                        break;
                    case 'link':
                        btnBg = 'transparent';
                        btnColor = primaryColor;
                        btnBorder = 'none';
                        break;
                    case 'filled':
                    default:
                        btnBg = primaryColor;
                        btnColor = 'white';
                        btnBorder = 'none';
                }

                var linkStyles = buttonVariant === 'link'
                    ? 'text-decoration: underline; padding: 0;'
                    : 'padding: var(--flavor-button-py) var(--flavor-button-px);';

                return '<div style="padding: 16px; text-align: ' + (data.align || 'left') + '; ' + customStyle + '">' +
                    '<a href="' + (data.url || '#') + '" target="' + (data.target || '_self') + '" contenteditable="true" data-field="text" class="flavor-button vbp-button--' + buttonVariant + '" style="display: inline-block; ' + linkStyles + ' border-radius: ' + buttonRadius + '; text-decoration: none; font-weight: var(--flavor-button-weight); font-size: var(--flavor-button-font-size); cursor: pointer; background: ' + btnBg + '; color: ' + btnColor + '; border: ' + btnBorder + ';">' + (data.text || 'Botón') + '</a></div>';
            }

            if (type === 'divider') {
                var dividerStyle = data.style || 'solid';
                return '<div style="padding: 24px 16px; ' + customStyle + '"><hr style="margin: 0; border: none; border-top: ' + (data.width || '1px') + ' ' + dividerStyle + ' ' + (data.color || '#e0e0e0') + ';"></div>';
            }

            if (type === 'spacer') {
                return '<div style="height: ' + (data.height || '60px') + '; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(102,126,234,0.05) 10px, rgba(102,126,234,0.05) 20px); ' + customStyle + '"></div>';
            }

            if (type === 'icon') {
                return '<div style="padding: 16px; text-align: center; ' + customStyle + '">' +
                    '<span style="font-size: ' + (data.size || '48px') + ';">' + (data.icon || '⭐') + '</span></div>';
            }

            if (type === 'html') {
                return '<div class="vbp-html-block" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: #1a1a2e; color: #e0e0e0; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 14px;">' +
                    '<div style="color: #888; margin-bottom: 8px;">📝 HTML personalizado</div>' +
                    '<code contenteditable="true" data-field="code" style="display: block; white-space: pre-wrap;">' + (data.code || '<!-- Tu código HTML aquí -->') + '</code></div></div>';
            }

            if (type === 'shortcode') {
                return '<div class="vbp-shortcode-block" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: #fef3c7; border: 1px dashed #f59e0b; padding: 16px; border-radius: 8px; text-align: center;">' +
                    '<div style="color: #92400e; margin-bottom: 8px;">⚡ Shortcode</div>' +
                    '<code contenteditable="true" data-field="shortcode" style="font-family: monospace; color: #78350f;">' + (data.shortcode || '[tu_shortcode]') + '</code></div></div>';
            }

            if (type === 'audio') {
                var audioSrc = data.src || '';
                var audioTitle = data.titulo || '';
                var audioAutoplay = data.autoplay ? 'autoplay' : '';
                var audioLoop = data.loop ? 'loop' : '';
                var audioMuted = data.muted ? 'muted' : '';
                var audioControls = data.controls !== false ? 'controls' : '';
                var audioPreload = data.preload || 'metadata';

                if (audioSrc) {
                    return '<div class="vbp-audio-block" style="padding: 16px; ' + customStyle + '">' +
                        (audioTitle ? '<div style="font-weight: 500; margin-bottom: 8px; color: ' + textColor + ';">🎵 ' + audioTitle + '</div>' : '') +
                        '<audio src="' + audioSrc + '" ' + audioControls + ' ' + audioAutoplay + ' ' + audioLoop + ' ' + audioMuted + ' preload="' + audioPreload + '" style="width: 100%; border-radius: 8px;"></audio>' +
                        '</div>';
                }
                return '<div class="vbp-audio-block" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 24px; border-radius: 12px; text-align: center; color: white;">' +
                    '<div style="font-size: 32px; margin-bottom: 12px;">🎵</div>' +
                    '<div style="font-size: 14px; opacity: 0.9;">Selecciona un archivo de audio</div>' +
                    '<div style="font-size: 11px; opacity: 0.7; margin-top: 8px;">MP3, WAV, OGG</div>' +
                    '</div></div>';
            }

            if (type === 'embed') {
                var embedCode = data.code || '';
                var embedUrl = data.url || '';
                var embedWidth = data.width || '100%';
                var embedHeight = data.height || '400px';
                var embedAspect = data.aspect_ratio || '';

                // Si hay código embed directo, mostrarlo
                if (embedCode) {
                    var aspectStyle = embedAspect ? 'aspect-ratio: ' + embedAspect + '; height: auto;' : 'height: ' + embedHeight + ';';
                    return '<div class="vbp-embed-block" style="padding: 16px; ' + customStyle + '">' +
                        '<div style="width: ' + embedWidth + '; ' + aspectStyle + ' overflow: hidden; border-radius: 8px; background: #000;">' +
                        embedCode +
                        '</div></div>';
                }

                // Si hay URL, mostrar preview
                if (embedUrl) {
                    var isYouTube = embedUrl.includes('youtube.com') || embedUrl.includes('youtu.be');
                    var isVimeo = embedUrl.includes('vimeo.com');
                    var platformIcon = isYouTube ? '📺' : (isVimeo ? '🎬' : '🌐');
                    var platformName = isYouTube ? 'YouTube' : (isVimeo ? 'Vimeo' : 'Embed');

                    return '<div class="vbp-embed-block" style="padding: 16px; ' + customStyle + '">' +
                        '<div style="background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 40px; border-radius: 12px; text-align: center; color: white;">' +
                        '<div style="font-size: 48px; margin-bottom: 16px;">' + platformIcon + '</div>' +
                        '<div style="font-weight: 500; margin-bottom: 8px;">' + platformName + '</div>' +
                        '<div style="font-size: 12px; opacity: 0.7; word-break: break-all; max-width: 300px; margin: 0 auto;">' + embedUrl + '</div>' +
                        '</div></div>';
                }

                // Sin contenido, mostrar placeholder
                return '<div class="vbp-embed-block" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: #1a1a2e; padding: 40px; border-radius: 12px; text-align: center; color: white;">' +
                    '<div style="font-size: 32px; margin-bottom: 12px;">🔗</div>' +
                    '<div style="font-size: 14px; opacity: 0.9;">Pega tu código embed</div>' +
                    '<div style="font-size: 11px; opacity: 0.7; margin-top: 8px;">YouTube, Vimeo, Spotify, etc.</div>' +
                    '</div></div>';
            }

            // ============ LAYOUT ============
            if (type === 'container') {
                var containerMaxWidth = data.max_width || (ds.container_max_width ? ds.container_max_width + 'px' : '1200px');
                if (containerMaxWidth === 'full') containerMaxWidth = '100%';
                var containerBg = data.background || 'transparent';
                var containerPadding = data.padding || '20px';
                var containerAlign = data.align || 'center';
                var containerFullHeight = data.full_height;
                var containerChildren = element.children || [];
                var containerSelf = this;

                // Determinar margin según alineación
                var containerMargin = '0 auto';
                if (containerAlign === 'left') containerMargin = '0 auto 0 0';
                else if (containerAlign === 'right') containerMargin = '0 0 0 auto';

                // Altura completa
                var containerMinHeight = containerFullHeight ? '100vh' : '100px';

                var containerHtml = '<div class="vbp-container vbp-container-dropzone flavor-container" data-container-id="' + element.id + '" style="max-width: ' + containerMaxWidth + '; margin: ' + containerMargin + '; padding: ' + containerPadding + '; background: ' + containerBg + '; border: 2px dashed ' + primaryColor + '40; min-height: ' + containerMinHeight + '; border-radius: ' + cardRadius + '; ' + customStyle + '">';

                if (containerChildren.length > 0) {
                    containerHtml += '<div style="display: flex; flex-direction: column; gap: 16px;">';
                    for (var cti = 0; cti < containerChildren.length; cti++) {
                        containerHtml += '<div class="vbp-element vbp-element-child" data-element-id="' + containerChildren[cti].id + '">' + containerSelf.renderElement(containerChildren[cti]) + '</div>';
                    }
                    containerHtml += '</div>';
                } else {
                    containerHtml += '<div style="text-align: center; color: ' + textMutedColor + '; padding: 40px;">📦 Contenedor - Arrastra elementos aquí</div>';
                }
                containerHtml += '</div>';
                return containerHtml;
            }

            if (type === 'columns' || type === 'row') {
                var cols = parseInt(data.columnas) || 2;
                var colsGap = data.gap ? data.gap + 'px' : gridGap;
                var colsReverse = data.reverse ? 'direction: rtl;' : '';
                var colsAlign = data.align || 'stretch';
                var children = element.children || [];
                var self = this;

                // Determinar grid-template-columns: usar anchos personalizados o iguales
                var gridCols;
                if (data.gridTemplateColumns) {
                    gridCols = data.gridTemplateColumns;
                } else if (data.columnWidths && data.columnWidths.length === cols) {
                    gridCols = data.columnWidths.join(' ');
                } else {
                    gridCols = 'repeat(' + cols + ', 1fr)';
                }

                var html = '<div class="vbp-columns vbp-container-dropzone" data-container-id="' + element.id + '" style="display: grid; grid-template-columns: ' + gridCols + '; gap: ' + colsGap + '; align-items: ' + colsAlign + '; padding: 16px; border: 2px dashed ' + primaryColor + '40; border-radius: ' + cardRadius + '; min-height: 100px; ' + colsReverse + customStyle + '">';
                for (var ci = 0; ci < cols; ci++) {
                    // Filtrar hijos para esta columna
                    var columnChildren = children.filter(function(child) {
                        return (child._columnIndex || 0) === ci;
                    });

                    html += '<div class="vbp-column-dropzone" data-container-id="' + element.id + '" data-column-index="' + ci + '" style="min-height: 80px; background: rgba(248,249,250,0.5); border-radius: ' + buttonRadius + '; padding: 8px; border: 1px dashed #dee2e6; display: flex; flex-direction: column; gap: 8px;">';

                    if (columnChildren.length > 0) {
                        for (var cci = 0; cci < columnChildren.length; cci++) {
                            html += '<div class="vbp-element vbp-element-child" data-element-id="' + columnChildren[cci].id + '">' + self.renderElement(columnChildren[cci]) + '</div>';
                        }
                    } else {
                        html += '<div style="text-align: center; color: ' + textMutedColor + '; padding: 20px; font-size: 12px;">📥 Col ' + (ci+1) + '</div>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                return html;
            }

            // Bloque contact_section (antes two_columns) - Sección de contacto predefinida
            if (type === 'two_columns' || type === 'contact_section') {
                var twoColsGap = data.gap ? data.gap + 'px' : gridGap;
                var leftCol = data.columna_izquierda || {};
                var rightCol = data.columna_derecha || {};
                var self = this;

                var twoColsHtml = '<div class="vbp-two-columns" style="display: grid; grid-template-columns: 1fr 1fr; gap: ' + twoColsGap + '; padding: 16px; border: 2px dashed ' + primaryColor + '40; border-radius: ' + cardRadius + '; min-height: 100px; ' + customStyle + '">';

                // Columna izquierda
                twoColsHtml += '<div class="vbp-column-left" style="background: rgba(248,249,250,0.5); border-radius: ' + buttonRadius + '; padding: 16px; border: 1px dashed #dee2e6;">';
                twoColsHtml += self.renderTwoColumnContent(leftCol, 'izquierda');
                twoColsHtml += '</div>';

                // Columna derecha
                twoColsHtml += '<div class="vbp-column-right" style="background: rgba(248,249,250,0.5); border-radius: ' + buttonRadius + '; padding: 16px; border: 1px dashed #dee2e6;">';
                twoColsHtml += self.renderTwoColumnContent(rightCol, 'derecha');
                twoColsHtml += '</div>';

                twoColsHtml += '</div>';
                return twoColsHtml;
            }

            if (type === 'grid') {
                var gridColsNum = parseInt(data.columnas) || 3;
                var gridRows = data.filas ? 'repeat(' + parseInt(data.filas) + ', auto)' : 'auto';
                var gridGapValue = data.gap ? (isNaN(data.gap) ? data.gap : data.gap + 'px') : gridGap;
                var gridAutoFit = data.auto_fit || '';
                var gridMinColWidth = data.min_col_width || '200px';
                var gridChildren = element.children || [];
                var gridSelf = this;

                // Determinar grid-template-columns
                var gridColsTemplate;
                if (gridAutoFit) {
                    gridColsTemplate = 'repeat(' + gridAutoFit + ', minmax(' + gridMinColWidth + ', 1fr))';
                } else {
                    gridColsTemplate = 'repeat(' + gridColsNum + ', 1fr)';
                }

                var gridHtml = '<div class="vbp-grid vbp-container-dropzone flavor-grid" data-container-id="' + element.id + '" style="display: grid; grid-template-columns: ' + gridColsTemplate + '; grid-template-rows: ' + gridRows + '; gap: ' + gridGapValue + '; padding: 16px; border: 2px dashed ' + primaryColor + '40; min-height: 100px; border-radius: ' + cardRadius + '; ' + customStyle + '">';

                if (gridChildren.length > 0) {
                    for (var gi = 0; gi < gridChildren.length; gi++) {
                        gridHtml += '<div class="vbp-element vbp-element-child" data-element-id="' + gridChildren[gi].id + '">' + gridSelf.renderElement(gridChildren[gi]) + '</div>';
                    }
                } else {
                    gridHtml += '<div style="text-align: center; color: ' + textMutedColor + '; padding: 40px; grid-column: 1/-1;">🔲 Grid - Arrastra elementos aquí</div>';
                }
                gridHtml += '</div>';
                return gridHtml;
            }

            // ============ FORMULARIOS ============
            if (type === 'form') {
                var campos = data.campos || [];
                var camposHtml = '';

                for (var ci = 0; ci < campos.length; ci++) {
                    var campo = campos[ci];
                    var campoTipo = campo.tipo || 'text';
                    var campoLabel = campo.label || 'Campo';
                    var campoPlaceholder = campo.placeholder || '';
                    var campoRequerido = campo.requerido ? ' *' : '';
                    var inputStyle = 'width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base); box-sizing: border-box;';

                    camposHtml += '<div style="margin-bottom: 12px;">';
                    camposHtml += '<label style="display: block; margin-bottom: 6px; font-weight: 500; color: ' + textColor + ';">' + campoLabel + campoRequerido + '</label>';

                    if (campoTipo === 'textarea') {
                        camposHtml += '<textarea placeholder="' + campoPlaceholder + '" rows="4" style="' + inputStyle + ' resize: vertical;"></textarea>';
                    } else if (campoTipo === 'select') {
                        var opciones = (campo.opciones_text || '').split('\n').filter(function(o) { return o.trim(); });
                        camposHtml += '<select style="' + inputStyle + '">';
                        camposHtml += '<option value="">' + (campoPlaceholder || 'Selecciona...') + '</option>';
                        for (var oi = 0; oi < opciones.length; oi++) {
                            camposHtml += '<option value="' + opciones[oi].trim() + '">' + opciones[oi].trim() + '</option>';
                        }
                        camposHtml += '</select>';
                    } else if (campoTipo === 'checkbox') {
                        camposHtml += '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">';
                        camposHtml += '<input type="checkbox" style="width: 18px; height: 18px; accent-color: ' + primaryColor + ';">';
                        camposHtml += '<span>' + campoLabel + '</span></label>';
                    } else {
                        camposHtml += '<input type="' + campoTipo + '" placeholder="' + campoPlaceholder + '" style="' + inputStyle + '">';
                    }

                    camposHtml += '</div>';
                }

                // Si no hay campos, mostrar mensaje
                if (campos.length === 0) {
                    camposHtml = '<div style="text-align: center; padding: 20px; color: ' + textMutedColor + ';">📋 Añade campos desde el inspector</div>';
                }

                return '<div class="vbp-form flavor-card" style="padding: ' + cardPadding + '; background: #f8f9fa; border-radius: ' + cardRadius + '; ' + customStyle + '">' +
                    '<h3 contenteditable="true" data-field="titulo" style="margin: 0 0 24px; text-align: center; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Formulario') + '</h3>' +
                    '<form style="display: flex; flex-direction: column; gap: 8px; max-width: 500px; margin: 0 auto;" onsubmit="return false;">' +
                    camposHtml +
                    '<button type="submit" class="flavor-button flavor-button-primary" style="margin-top: 12px; padding: var(--flavor-button-py); background: ' + primaryColor + '; color: white; border: none; border-radius: ' + buttonRadius + '; cursor: pointer; font-size: var(--flavor-button-font-size); font-weight: var(--flavor-button-weight);">' + (data.boton_texto || 'Enviar') + '</button>' +
                    '</form></div>';
            }

            if (type === 'input') {
                return '<div class="flavor-component" style="padding: 12px 16px; ' + customStyle + '">' +
                    '<label contenteditable="true" style="display: block; margin-bottom: 6px; font-weight: 500; color: ' + textColor + ';">' + (data.label || 'Campo') + '</label>' +
                    '<input type="' + (data.inputType || 'text') + '" placeholder="' + (data.placeholder || 'Escribe aquí...') + '" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; box-sizing: border-box; font-size: var(--flavor-font-size-base);"></div>';
            }

            if (type === 'textarea') {
                return '<div class="flavor-component" style="padding: 12px 16px; ' + customStyle + '">' +
                    '<label contenteditable="true" style="display: block; margin-bottom: 6px; font-weight: 500; color: ' + textColor + ';">' + (data.label || 'Mensaje') + '</label>' +
                    '<textarea placeholder="' + (data.placeholder || 'Escribe tu mensaje...') + '" rows="4" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; resize: vertical; box-sizing: border-box; font-size: var(--flavor-font-size-base);"></textarea></div>';
            }

            if (type === 'select') {
                return '<div class="flavor-component" style="padding: 12px 16px; ' + customStyle + '">' +
                    '<label contenteditable="true" style="display: block; margin-bottom: 6px; font-weight: 500; color: ' + textColor + ';">' + (data.label || 'Selecciona') + '</label>' +
                    '<select style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base);">' +
                    '<option>Opción 1</option><option>Opción 2</option><option>Opción 3</option></select></div>';
            }

            if (type === 'checkbox') {
                return '<div class="flavor-component" style="padding: 12px 16px; ' + customStyle + '">' +
                    '<label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: ' + textColor + ';">' +
                    '<input type="checkbox" style="width: 18px; height: 18px; accent-color: ' + primaryColor + ';">' +
                    '<span contenteditable="true">' + (data.label || 'Acepto los términos y condiciones') + '</span></label></div>';
            }

            // ============ MEDIA ============
            if (type === 'video-embed') {
                return '<div class="vbp-video-embed" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="position: relative; padding-bottom: 56.25%; height: 0; background: #000; border-radius: ' + cardRadius + '; overflow: hidden;">' +
                    '<div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; color: white;">' +
                    '<div style="font-size: 48px;">🎬</div>' +
                    '<p style="margin: 16px 0 0;">Pega la URL del video en el inspector</p></div></div></div>';
            }

            if (type === 'audio') {
                return '<div class="vbp-audio" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: #f0f0f0; padding: ' + cardPadding + '; border-radius: ' + cardRadius + '; display: flex; align-items: center; gap: 16px;">' +
                    '<div style="font-size: 32px;">🎵</div>' +
                    '<div style="flex: 1;"><div style="font-weight: 600; color: ' + textColor + ';">Reproductor de Audio</div>' +
                    '<div style="color: ' + textMutedColor + '; font-size: 14px;">Selecciona un archivo de audio</div></div>' +
                    '<button class="flavor-button" style="padding: 8px 16px; background: ' + primaryColor + '; color: white; border: none; border-radius: ' + buttonRadius + ';">▶️</button></div></div>';
            }

            if (type === 'map') {
                return '<div class="vbp-map" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="height: 300px; background: linear-gradient(135deg, #e8f4ea, #d4e8d8); border-radius: ' + cardRadius + '; display: flex; align-items: center; justify-content: center; flex-direction: column;">' +
                    '<div style="font-size: 48px;">🗺️</div>' +
                    '<p style="margin: 16px 0 0; color: ' + textMutedColor + ';">Mapa interactivo</p>' +
                    '<p style="margin: 8px 0 0; color: ' + textMutedColor + '; font-size: 14px;">Configura las coordenadas en el inspector</p></div></div>';
            }

            if (type === 'embed') {
                var accentColor = ds.accent_color || '#f59e0b';
                return '<div class="vbp-embed" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="background: ' + accentColor + '20; border: 1px dashed ' + accentColor + '; padding: 32px; border-radius: ' + cardRadius + '; text-align: center;">' +
                    '<div style="font-size: 32px;">📋</div>' +
                    '<p style="margin: 16px 0 8px; font-weight: 500; color: ' + textColor + ';">Embed HTML</p>' +
                    '<p style="margin: 0; color: ' + textMutedColor + '; font-size: 14px;">Pega tu código embed en el inspector</p></div></div>';
            }

            // ============ NUEVOS BLOQUES ============

            if (type === 'countdown') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || '#ffffff';
                var secFondoTipo = data.seccion_fondo_tipo || 'gradient';
                var secFondoColor = data.seccion_fondo_color || primaryColor;
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardFondo = data.card_fondo_color || 'rgba(255,255,255,0.2)';
                var cardTitulo = data.card_titulo_color || '#ffffff';
                var cardTexto = data.card_texto_color || 'rgba(255,255,255,0.8)';

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ' 0%, ' + secGradienteFin + ' 100%);';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                // Calcular tiempo restante
                var targetDate = data.fecha ? new Date(data.fecha + 'T' + (data.hora || '23:59')) : new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
                var now = new Date();
                var diff = Math.max(0, targetDate - now);
                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var secs = Math.floor((diff % (1000 * 60)) / 1000);

                var countdownHtml = '<section class="vbp-countdown flavor-component" data-countdown-id="' + element.id + '" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + ' text-align: center; ' + customStyle + '">' +
                    '<h2 contenteditable="true" data-field="titulo" style="color: ' + secTituloColor + '; font-size: ' + fontH2 + '; margin: 0 0 32px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'La oferta termina en') + '</h2>' +
                    '<div class="vbp-countdown-timer" style="display: flex; justify-content: center; gap: 24px; flex-wrap: wrap;">';

                if (data.mostrar_dias !== false) {
                    countdownHtml += '<div class="vbp-countdown-unit" style="background: ' + cardFondo + '; padding: 24px 32px; border-radius: ' + cardRadius + '; min-width: 100px;"><div class="vbp-countdown-value" data-unit="days" style="font-size: 48px; font-weight: 700; color: ' + cardTitulo + ';">' + String(days).padStart(2, '0') + '</div><div style="color: ' + cardTexto + '; font-size: 14px; text-transform: uppercase;">Días</div></div>';
                }
                if (data.mostrar_horas !== false) {
                    countdownHtml += '<div class="vbp-countdown-unit" style="background: ' + cardFondo + '; padding: 24px 32px; border-radius: ' + cardRadius + '; min-width: 100px;"><div class="vbp-countdown-value" data-unit="hours" style="font-size: 48px; font-weight: 700; color: ' + cardTitulo + ';">' + String(hours).padStart(2, '0') + '</div><div style="color: ' + cardTexto + '; font-size: 14px; text-transform: uppercase;">Horas</div></div>';
                }
                if (data.mostrar_minutos !== false) {
                    countdownHtml += '<div class="vbp-countdown-unit" style="background: ' + cardFondo + '; padding: 24px 32px; border-radius: ' + cardRadius + '; min-width: 100px;"><div class="vbp-countdown-value" data-unit="mins" style="font-size: 48px; font-weight: 700; color: ' + cardTitulo + ';">' + String(mins).padStart(2, '0') + '</div><div style="color: ' + cardTexto + '; font-size: 14px; text-transform: uppercase;">Min</div></div>';
                }
                if (data.mostrar_segundos !== false) {
                    countdownHtml += '<div class="vbp-countdown-unit" style="background: ' + cardFondo + '; padding: 24px 32px; border-radius: ' + cardRadius + '; min-width: 100px;"><div class="vbp-countdown-value" data-unit="secs" style="font-size: 48px; font-weight: 700; color: ' + cardTitulo + ';">' + String(secs).padStart(2, '0') + '</div><div style="color: ' + cardTexto + '; font-size: 14px; text-transform: uppercase;">Seg</div></div>';
                }

                countdownHtml += '</div></section>';
                return countdownHtml;
            }

            if (type === 'social-icons') {
                var redes = data.redes || [{ red: 'facebook', icono: '📘' }, { red: 'twitter', icono: '🐦' }, { red: 'instagram', icono: '📸' }, { red: 'linkedin', icono: '💼' }];
                var html = '<div class="vbp-social-icons flavor-component" style="padding: 24px; text-align: ' + (data.alineacion || 'center') + '; ' + customStyle + '">';
                if (data.titulo) {
                    html += '<p contenteditable="true" data-field="titulo" style="margin: 0 0 16px; color: ' + textMutedColor + ';">' + data.titulo + '</p>';
                }
                html += '<div style="display: flex; gap: 12px; justify-content: ' + (data.alineacion || 'center') + '; flex-wrap: wrap;">';
                for (var si = 0; si < redes.length; si++) {
                    var red = redes[si];
                    var btnSize = data.tamano === 'large' ? '56px' : (data.tamano === 'small' ? '36px' : '44px');
                    var fontSize = data.tamano === 'large' ? '28px' : (data.tamano === 'small' ? '16px' : '22px');
                    html += '<a href="' + (red.url || '#') + '" style="display: flex; align-items: center; justify-content: center; width: ' + btnSize + '; height: ' + btnSize + '; background: ' + primaryColor + '; border-radius: ' + (data.estilo === 'square' ? '8px' : '50%') + '; text-decoration: none; font-size: ' + fontSize + '; transition: transform 0.2s;">' + (red.icono || '🔗') + '</a>';
                }
                html += '</div></div>';
                return html;
            }

            if (type === 'newsletter') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textColor;
                var secTextoColor = data.texto_color || textMutedColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#f8f9fa';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var cardBorde = data.card_borde_color || '#e0e0e0';
                var botonFondo = data.boton_color_fondo || primaryColor;
                var botonTexto = data.boton_color_texto || '#ffffff';

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                return '<section class="vbp-newsletter flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + ' text-align: center; ' + customStyle + '">' +
                    '<div style="max-width: 500px; margin: 0 auto;">' +
                    '<h2 contenteditable="true" data-field="titulo" style="color: ' + secTituloColor + '; font-size: ' + fontH2 + '; margin: 0 0 12px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Suscríbete a nuestro newsletter') + '</h2>' +
                    '<p contenteditable="true" data-field="subtitulo" style="color: ' + secTextoColor + '; margin: 0 0 24px;">' + (data.subtitulo || 'Recibe las últimas novedades directamente en tu email') + '</p>' +
                    '<form style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">' +
                    (data.mostrar_nombre ? '<input type="text" placeholder="Tu nombre" style="flex: 1; min-width: 150px; padding: 14px 16px; border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base);">' : '') +
                    '<input type="email" placeholder="' + (data.placeholder_email || 'tu@email.com') + '" style="flex: 2; min-width: 200px; padding: 14px 16px; border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; font-size: var(--flavor-font-size-base);">' +
                    '<button type="submit" class="flavor-button" style="padding: 14px 32px; background: ' + botonFondo + '; color: ' + botonTexto + '; border: none; border-radius: ' + buttonRadius + '; font-weight: var(--flavor-button-weight); cursor: pointer;">' + (data.boton_texto || 'Suscribirse') + '</button>' +
                    '</form></div></section>';
            }

            if (type === 'logo-grid') {
                // Colores personalizables de sección
                var secTituloColor = data.titulo_color || textMutedColor;
                var secFondoTipo = data.seccion_fondo_tipo || 'color';
                var secFondoColor = data.seccion_fondo_color || '#ffffff';
                var secGradienteInicio = data.seccion_fondo_gradiente_inicio || primaryColor;
                var secGradienteFin = data.seccion_fondo_gradiente_fin || secondaryColor;
                var placeholderFondo = data.card_fondo_color || '#e0e0e0';
                var placeholderTexto = data.card_texto_color || textMutedColor;

                // Fondo de sección
                var secFondoStyle = '';
                if (secFondoTipo === 'gradient') {
                    secFondoStyle = 'background: linear-gradient(135deg, ' + secGradienteInicio + ', ' + secGradienteFin + ');';
                } else if (secFondoTipo === 'image' && data.seccion_fondo_imagen) {
                    secFondoStyle = 'background-image: url(' + data.seccion_fondo_imagen + '); background-size: cover; background-position: center;';
                } else {
                    secFondoStyle = 'background: ' + secFondoColor + ';';
                }

                var logos = data.logos || [];
                var cols = data.columnas || 4;
                var html = '<section class="vbp-logo-grid flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + secFondoStyle + ' ' + customStyle + '">';
                if (data.titulo) {
                    html += '<h2 contenteditable="true" data-field="titulo" style="text-align: center; font-size: 18px; color: ' + secTituloColor + '; margin: 0 0 32px; font-weight: 500; text-transform: uppercase; letter-spacing: 2px;">' + data.titulo + '</h2>';
                }
                html += '<div style="display: grid; grid-template-columns: repeat(' + cols + ', 1fr); gap: ' + gridGap + '; align-items: center; justify-items: center;">';
                if (logos.length > 0) {
                    for (var li = 0; li < logos.length; li++) {
                        html += '<img src="' + logos[li].src + '" alt="' + (logos[li].alt || 'Logo') + '" style="max-width: 120px; max-height: 60px; object-fit: contain;' + (data.escala_grises ? ' filter: grayscale(100%); opacity: 0.6;' : '') + '">';
                    }
                } else {
                    for (var pi = 0; pi < cols; pi++) {
                        html += '<div style="width: 100px; height: 50px; background: ' + placeholderFondo + '; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: ' + placeholderTexto + '; font-size: 12px;">Logo ' + (pi + 1) + '</div>';
                    }
                }
                html += '</div></section>';
                return html;
            }

            if (type === 'icon-box') {
                var iconBoxVariant = element.variant || 'vertical';
                var iconBoxLink = data.enlace_url ? '<a href="' + data.enlace_url + '" style="color: ' + primaryColor + '; text-decoration: none; font-weight: 500;">' + (data.enlace_texto || 'Saber más') + ' →</a>' : '';

                // Variante: Vertical (default)
                if (iconBoxVariant === 'vertical' || iconBoxVariant === 'default') {
                    return '<div class="vbp-icon-box vbp-icon-box--vertical flavor-card" style="padding: ' + cardPadding + '; background: white; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow); text-align: center; ' + customStyle + '">' +
                        '<div style="font-size: 48px; margin-bottom: 16px;">' + (data.icono || '🚀') + '</div>' +
                        '<h3 contenteditable="true" data-field="titulo" style="font-size: 20px; margin: 0 0 12px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Título') + '</h3>' +
                        '<p contenteditable="true" data-field="descripcion" style="color: ' + textMutedColor + '; margin: 0 0 16px; line-height: var(--flavor-line-height-base);">' + (data.descripcion || 'Descripción') + '</p>' +
                        iconBoxLink + '</div>';
                }

                // Variante: Horizontal
                if (iconBoxVariant === 'horizontal') {
                    return '<div class="vbp-icon-box vbp-icon-box--horizontal flavor-card" style="padding: ' + cardPadding + '; background: white; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow); display: flex; align-items: flex-start; gap: 20px; ' + customStyle + '">' +
                        '<div style="font-size: 40px; flex-shrink: 0; width: 64px; height: 64px; background: ' + primaryColor + '15; border-radius: 16px; display: flex; align-items: center; justify-content: center;">' + (data.icono || '🚀') + '</div>' +
                        '<div style="flex: 1;">' +
                        '<h3 contenteditable="true" data-field="titulo" style="font-size: 18px; margin: 0 0 8px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Título') + '</h3>' +
                        '<p contenteditable="true" data-field="descripcion" style="color: ' + textMutedColor + '; margin: 0 0 12px; line-height: var(--flavor-line-height-base); font-size: 14px;">' + (data.descripcion || 'Descripción') + '</p>' +
                        iconBoxLink + '</div></div>';
                }

                // Variante: Izquierda con borde
                if (iconBoxVariant === 'left') {
                    return '<div class="vbp-icon-box vbp-icon-box--left" style="padding: ' + cardPadding + '; background: white; border-left: 4px solid ' + primaryColor + '; display: flex; align-items: flex-start; gap: 16px; ' + customStyle + '">' +
                        '<div style="font-size: 32px; flex-shrink: 0;">' + (data.icono || '🚀') + '</div>' +
                        '<div style="flex: 1;">' +
                        '<h3 contenteditable="true" data-field="titulo" style="font-size: 18px; margin: 0 0 8px; font-family: var(--flavor-font-headings);">' + (data.titulo || 'Título') + '</h3>' +
                        '<p contenteditable="true" data-field="descripcion" style="color: ' + textMutedColor + '; margin: 0; line-height: var(--flavor-line-height-base); font-size: 14px;">' + (data.descripcion || 'Descripción') + '</p>' +
                        '</div></div>';
                }

                // Fallback
                return '<div class="vbp-icon-box flavor-card" style="padding: ' + cardPadding + '; background: white; border-radius: ' + cardRadius + '; box-shadow: var(--flavor-card-shadow); text-align: center; ' + customStyle + '">' +
                    '<div style="font-size: 48px; margin-bottom: 16px;">' + (data.icono || '🚀') + '</div>' +
                    '<h3 contenteditable="true" data-field="titulo" style="font-size: 20px; margin: 0 0 12px;">' + (data.titulo || 'Título') + '</h3>' +
                    '<p contenteditable="true" data-field="descripcion" style="color: ' + textMutedColor + '; margin: 0;">' + (data.descripcion || 'Descripción') + '</p></div>';
            }

            if (type === 'accordion') {
                // Colores personalizables
                var cardFondo = data.card_fondo_color || '#f8f9fa';
                var cardBorde = data.card_borde_color || '#e0e0e0';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;
                var contenidoFondo = data.seccion_fondo_color || '#ffffff';

                var accordionVariant = element.variant || 'simple';
                var items = data.items || [{ titulo: 'Elemento 1', contenido: 'Contenido del elemento', abierto: true }];
                var accordionHtml = '<div class="vbp-accordion vbp-accordion--' + accordionVariant + ' flavor-component" style="padding: 16px; ' + customStyle + '">';

                for (var ai = 0; ai < items.length; ai++) {
                    var item = items[ai];
                    var isOpen = item.abierto;

                    // Variante: Simple (default)
                    if (accordionVariant === 'simple' || accordionVariant === 'default') {
                        accordionHtml += '<div class="vbp-accordion-item" style="border: 1px solid ' + cardBorde + '; border-radius: ' + buttonRadius + '; margin-bottom: 8px; overflow: hidden;">' +
                            '<div class="vbp-accordion-header" style="padding: 16px; background: ' + cardFondo + '; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">' +
                            '<span contenteditable="true" style="font-weight: 500; color: ' + cardTitulo + ';">' + (item.titulo || 'Título') + '</span>' +
                            '<span class="vbp-accordion-icon" style="transition: transform 0.2s; color: ' + cardTexto + ';">' + (isOpen ? '▼' : '▶') + '</span></div>' +
                            '<div class="vbp-accordion-content" style="padding: 16px; background: ' + contenidoFondo + '; display: ' + (isOpen ? 'block' : 'none') + ';"><p contenteditable="true" style="margin: 0; color: ' + cardTexto + ';">' + (item.contenido || 'Contenido') + '</p></div></div>';
                    }

                    // Variante: Bordeado
                    if (accordionVariant === 'bordered') {
                        accordionHtml += '<div class="vbp-accordion-item" style="border: 2px solid ' + (isOpen ? acentoColor : cardBorde) + '; border-radius: ' + cardRadius + '; margin-bottom: 12px; overflow: hidden; transition: border-color 0.2s;">' +
                            '<div class="vbp-accordion-header" style="padding: 18px 20px; background: ' + contenidoFondo + '; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">' +
                            '<span contenteditable="true" style="font-weight: 600; color: ' + (isOpen ? acentoColor : cardTitulo) + ';">' + (item.titulo || 'Título') + '</span>' +
                            '<span style="width: 24px; height: 24px; background: ' + (isOpen ? acentoColor : cardBorde) + '; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: ' + (isOpen ? contenidoFondo : cardTexto) + '; font-size: 12px;">' + (isOpen ? '−' : '+') + '</span></div>' +
                            '<div class="vbp-accordion-content" style="padding: 0 20px ' + (isOpen ? '20px' : '0') + '; background: ' + contenidoFondo + '; display: ' + (isOpen ? 'block' : 'none') + '; border-top: ' + (isOpen ? '1px solid ' + cardBorde : 'none') + ';"><p contenteditable="true" style="margin: 16px 0 0; color: ' + cardTexto + ';">' + (item.contenido || 'Contenido') + '</p></div></div>';
                    }

                    // Variante: Relleno
                    if (accordionVariant === 'filled') {
                        accordionHtml += '<div class="vbp-accordion-item" style="background: ' + (isOpen ? acentoColor : cardFondo) + '; border-radius: ' + buttonRadius + '; margin-bottom: 8px; overflow: hidden; transition: background 0.2s;">' +
                            '<div class="vbp-accordion-header" style="padding: 16px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">' +
                            '<span contenteditable="true" style="font-weight: 500; color: ' + (isOpen ? contenidoFondo : cardTitulo) + ';">' + (item.titulo || 'Título') + '</span>' +
                            '<span style="color: ' + (isOpen ? contenidoFondo : cardTexto) + '; font-size: 14px;">' + (isOpen ? '▲' : '▼') + '</span></div>' +
                            '<div class="vbp-accordion-content" style="padding: 0 20px ' + (isOpen ? '20px' : '0') + '; display: ' + (isOpen ? 'block' : 'none') + ';"><p contenteditable="true" style="margin: 0; color: rgba(255,255,255,0.9);">' + (item.contenido || 'Contenido') + '</p></div></div>';
                    }
                }

                accordionHtml += '</div>';
                return accordionHtml;
            }

            if (type === 'tabs') {
                // Colores personalizables
                var cardBorde = data.card_borde_color || '#e0e0e0';
                var cardFondo = data.card_fondo_color || '#f8f9fa';
                var cardTitulo = data.card_titulo_color || textColor;
                var cardTexto = data.card_texto_color || textMutedColor;
                var acentoColor = data.acento_color || primaryColor;
                var contenidoFondo = data.seccion_fondo_color || '#ffffff';
                var tabInactivoFondo = data.tab_inactivo_fondo || '#f0f0f0';

                var tabsVariant = element.variant || 'horizontal';
                var tabItems = data.items || [{ titulo: 'Tab 1', contenido: 'Contenido de la pestaña 1' }];
                var activeTab = data.tab_activa || 0;

                // Variante: Horizontal (default)
                if (tabsVariant === 'horizontal' || tabsVariant === 'default') {
                    var tabsHtml = '<div class="vbp-tabs vbp-tabs--horizontal flavor-component" style="padding: 16px; ' + customStyle + '">' +
                        '<div class="vbp-tabs-header" style="display: flex; border-bottom: 2px solid ' + cardBorde + '; margin-bottom: 16px;">';
                    for (var ti = 0; ti < tabItems.length; ti++) {
                        var isActive = ti === activeTab;
                        tabsHtml += '<button class="vbp-tab-button' + (isActive ? ' active' : '') + '" data-tab-index="' + ti + '" style="padding: 12px 24px; background: transparent; border: none; border-bottom: 2px solid ' + (isActive ? acentoColor : 'transparent') + '; margin-bottom: -2px; color: ' + (isActive ? acentoColor : cardTexto) + '; font-weight: ' + (isActive ? '600' : '400') + '; cursor: pointer;">' + (tabItems[ti].titulo || 'Tab') + '</button>';
                    }
                    tabsHtml += '</div><div class="vbp-tabs-content">';
                    for (var tc = 0; tc < tabItems.length; tc++) {
                        tabsHtml += '<div class="vbp-tab-panel" style="display: ' + (tc === activeTab ? 'block' : 'none') + '; padding: 16px;"><p contenteditable="true" style="margin: 0; color: ' + cardTitulo + ';">' + (tabItems[tc].contenido || 'Contenido') + '</p></div>';
                    }
                    tabsHtml += '</div></div>';
                    return tabsHtml;
                }

                // Variante: Vertical
                if (tabsVariant === 'vertical') {
                    var tabsHtml = '<div class="vbp-tabs vbp-tabs--vertical flavor-component" style="padding: 16px; display: flex; gap: 20px; ' + customStyle + '">' +
                        '<div class="vbp-tabs-header" style="display: flex; flex-direction: column; gap: 4px; min-width: 150px; border-right: 2px solid ' + cardBorde + '; padding-right: 20px;">';
                    for (var ti = 0; ti < tabItems.length; ti++) {
                        var isActive = ti === activeTab;
                        tabsHtml += '<button class="vbp-tab-button' + (isActive ? ' active' : '') + '" data-tab-index="' + ti + '" style="padding: 12px 16px; background: ' + (isActive ? acentoColor + '15' : 'transparent') + '; border: none; border-radius: ' + buttonRadius + '; text-align: left; color: ' + (isActive ? acentoColor : cardTexto) + '; font-weight: ' + (isActive ? '600' : '400') + '; cursor: pointer;">' + (tabItems[ti].titulo || 'Tab') + '</button>';
                    }
                    tabsHtml += '</div><div class="vbp-tabs-content" style="flex: 1;">';
                    for (var tc = 0; tc < tabItems.length; tc++) {
                        tabsHtml += '<div class="vbp-tab-panel" style="display: ' + (tc === activeTab ? 'block' : 'none') + ';"><p contenteditable="true" style="margin: 0; color: ' + cardTitulo + ';">' + (tabItems[tc].contenido || 'Contenido') + '</p></div>';
                    }
                    tabsHtml += '</div></div>';
                    return tabsHtml;
                }

                // Variante: Pills
                if (tabsVariant === 'pills') {
                    var tabsHtml = '<div class="vbp-tabs vbp-tabs--pills flavor-component" style="padding: 16px; ' + customStyle + '">' +
                        '<div class="vbp-tabs-header" style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">';
                    for (var ti = 0; ti < tabItems.length; ti++) {
                        var isActive = ti === activeTab;
                        tabsHtml += '<button class="vbp-tab-button' + (isActive ? ' active' : '') + '" data-tab-index="' + ti + '" style="padding: 10px 20px; background: ' + (isActive ? acentoColor : tabInactivoFondo) + '; border: none; border-radius: 50px; color: ' + (isActive ? contenidoFondo : cardTexto) + '; font-weight: 500; cursor: pointer; transition: all 0.2s;">' + (tabItems[ti].titulo || 'Tab') + '</button>';
                    }
                    tabsHtml += '</div><div class="vbp-tabs-content" style="background: ' + cardFondo + '; padding: 24px; border-radius: ' + cardRadius + ';">';
                    for (var tc = 0; tc < tabItems.length; tc++) {
                        tabsHtml += '<div class="vbp-tab-panel" style="display: ' + (tc === activeTab ? 'block' : 'none') + ';"><p contenteditable="true" style="margin: 0; color: ' + cardTitulo + ';">' + (tabItems[tc].contenido || 'Contenido') + '</p></div>';
                    }
                    tabsHtml += '</div></div>';
                    return tabsHtml;
                }

                // Fallback
                return '<div class="vbp-tabs flavor-component" style="padding: 16px;"><p>Pestañas</p></div>';
            }

            if (type === 'progress-bar') {
                var barItems = data.items || [{ label: 'Skill', porcentaje: 80 }];
                var html = '<div class="vbp-progress-bars flavor-component" style="padding: 24px; ' + customStyle + '">';
                for (var bi = 0; bi < barItems.length; bi++) {
                    var bar = barItems[bi];
                    html += '<div style="margin-bottom: 20px;">' +
                        '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">' +
                        '<span contenteditable="true" style="font-weight: 500;">' + (bar.label || 'Skill') + '</span>' +
                        (data.mostrar_porcentaje !== false ? '<span style="color: ' + textMutedColor + ';">' + (bar.porcentaje || 0) + '%</span>' : '') +
                        '</div>' +
                        '<div style="height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">' +
                        '<div style="height: 100%; width: ' + (bar.porcentaje || 0) + '%; background: linear-gradient(90deg, ' + primaryColor + ', ' + secondaryColor + '); border-radius: 4px;"></div>' +
                        '</div></div>';
                }
                html += '</div>';
                return html;
            }

            if (type === 'alert') {
                var alertColors = {
                    info: { bg: '#e0f2fe', border: '#0ea5e9', icon: 'ℹ️' },
                    success: { bg: '#dcfce7', border: '#22c55e', icon: '✅' },
                    warning: { bg: '#fef3c7', border: '#f59e0b', icon: '⚠️' },
                    error: { bg: '#fee2e2', border: '#ef4444', icon: '❌' }
                };
                var alertStyle = alertColors[data.tipo] || alertColors.info;
                return '<div class="vbp-alert flavor-component" style="padding: 16px; ' + customStyle + '">' +
                    '<div style="padding: 16px; background: ' + alertStyle.bg + '; border-left: 4px solid ' + alertStyle.border + '; border-radius: ' + buttonRadius + '; display: flex; gap: 12px; align-items: flex-start;">' +
                    (data.icono !== false ? '<span style="font-size: 20px;">' + alertStyle.icon + '</span>' : '') +
                    '<div style="flex: 1;">' +
                    '<div contenteditable="true" data-field="titulo" style="font-weight: 600; margin-bottom: 4px;">' + (data.titulo || 'Título') + '</div>' +
                    '<div contenteditable="true" data-field="mensaje" style="color: ' + textColor + ';">' + (data.mensaje || 'Mensaje de alerta') + '</div>' +
                    '</div>' +
                    (data.dismissible ? '<button style="background: transparent; border: none; font-size: 18px; cursor: pointer; opacity: 0.6;">✕</button>' : '') +
                    '</div></div>';
            }

            if (type === 'before-after') {
                var orientation = data.orientacion || 'horizontal';
                var sliderPosition = data.posicion || 50;
                var beforeImg = data.imagen_antes || '';
                var afterImg = data.imagen_despues || '';

                var beforeBg = beforeImg ? 'url(' + beforeImg + ') center/cover' : 'linear-gradient(135deg, ' + primaryColor + '20, ' + primaryColor + '40)';
                var afterBg = afterImg ? 'url(' + afterImg + ') center/cover' : 'linear-gradient(135deg, ' + secondaryColor + '20, ' + secondaryColor + '40)';

                return '<div class="vbp-before-after flavor-component" data-element-id="' + element.id + '" style="padding: 16px; ' + customStyle + '">' +
                    '<div class="vbp-ba-container" data-orientation="' + orientation + '" style="position: relative; aspect-ratio: 16/9; background: #f0f0f0; border-radius: ' + cardRadius + '; overflow: hidden;">' +
                    '<div class="vbp-ba-after" style="position: absolute; inset: 0; background: ' + afterBg + ';"></div>' +
                    '<div class="vbp-ba-before" style="position: absolute; ' + (orientation === 'horizontal' ? 'left: 0; top: 0; bottom: 0; width: ' + sliderPosition + '%' : 'left: 0; right: 0; top: 0; height: ' + sliderPosition + '%') + '; background: ' + beforeBg + '; overflow: hidden;">' +
                    '<div class="vbp-ba-label" style="position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px;">' + (data.label_antes || 'Antes') + '</div></div>' +
                    '<div class="vbp-ba-after-label" style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px;">' + (data.label_despues || 'Después') + '</div>' +
                    '<div class="vbp-ba-slider" style="position: absolute; ' + (orientation === 'horizontal' ? 'left: ' + sliderPosition + '%; top: 0; bottom: 0; width: 4px;' : 'top: ' + sliderPosition + '%; left: 0; right: 0; height: 4px;') + ' background: white; cursor: ' + (orientation === 'horizontal' ? 'ew-resize' : 'ns-resize') + ';">' +
                    '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; background: white; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center;">' + (orientation === 'horizontal' ? '⟷' : '⤊') + '</div>' +
                    '</div></div></div>';
            }

            if (type === 'social-icons') {
                var redes = data.redes || [
                    { red: 'facebook', icono: '📘', url: '#' },
                    { red: 'twitter', icono: '🐦', url: '#' },
                    { red: 'instagram', icono: '📷', url: '#' }
                ];
                var iconSize = data.tamano_icono || '32px';
                var iconSpacing = data.espaciado || '12px';
                var iconColor = data.color_iconos || primaryColor;

                var html = '<div class="vbp-social-icons flavor-component" style="padding: 24px; text-align: center; ' + customStyle + '">' +
                    '<div style="display: flex; justify-content: center; align-items: center; gap: ' + iconSpacing + '; flex-wrap: wrap;">';

                for (var si = 0; si < redes.length; si++) {
                    var red = redes[si];
                    html += '<a href="' + (red.url || '#') + '" style="display: flex; align-items: center; justify-content: center; width: ' + iconSize + '; height: ' + iconSize + '; background: ' + iconColor + '20; border-radius: 50%; text-decoration: none; font-size: calc(' + iconSize + ' * 0.5); transition: transform 0.2s, background 0.2s;" onmouseover="this.style.transform=\'scale(1.1)\'; this.style.background=\'' + iconColor + '30\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.background=\'' + iconColor + '20\';">' +
                        (red.icono || '🔗') + '</a>';
                }

                html += '</div></div>';
                return html;
            }

            if (type === 'newsletter') {
                var nlTitle = data.titulo || '📬 Suscríbete a nuestro newsletter';
                var nlSubtitle = data.subtitulo || 'Recibe las últimas novedades directamente en tu inbox';
                var nlPlaceholder = data.placeholder || 'Tu email';
                var nlButtonText = data.texto_boton || 'Suscribirse';
                var nlLayout = data.layout || 'horizontal';

                return '<div class="vbp-newsletter flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; background: linear-gradient(135deg, ' + primaryColor + '10, ' + secondaryColor + '15); text-align: center; ' + customStyle + '">' +
                    '<div style="max-width: 500px; margin: 0 auto;">' +
                    '<h3 contenteditable="true" style="margin: 0 0 8px; font-size: 24px; color: ' + textColor + ';">' + nlTitle + '</h3>' +
                    '<p contenteditable="true" style="margin: 0 0 24px; color: ' + textMutedColor + ';">' + nlSubtitle + '</p>' +
                    '<form style="display: flex; ' + (nlLayout === 'vertical' ? 'flex-direction: column;' : '') + ' gap: 12px; justify-content: center;">' +
                    '<input type="email" placeholder="' + nlPlaceholder + '" style="flex: 1; max-width: 300px; padding: 14px 18px; border: 1px solid #e0e0e0; border-radius: ' + buttonRadius + '; font-size: 15px; outline: none;">' +
                    '<button type="button" style="padding: 14px 28px; background: linear-gradient(135deg, ' + primaryColor + ', ' + secondaryColor + '); color: white; border: none; border-radius: ' + buttonRadius + '; font-weight: 600; cursor: pointer;">' + nlButtonText + '</button>' +
                    '</form></div></div>';
            }

            if (type === 'logo-grid') {
                var logos = data.logos || [];
                var columns = data.columnas || 4;
                var logoGap = data.gap || '24px';
                var grayscale = data.escala_grises !== false;

                var html = '<div class="vbp-logo-grid flavor-component" style="padding: ' + sectionPaddingY + ' ' + sectionPaddingX + '; ' + customStyle + '">' +
                    '<div style="display: grid; grid-template-columns: repeat(' + columns + ', 1fr); gap: ' + logoGap + '; align-items: center;">';

                if (logos.length === 0) {
                    for (var pl = 0; pl < 4; pl++) {
                        html += '<div style="background: #f5f5f5; border: 2px dashed #e0e0e0; padding: 32px; text-align: center; border-radius: ' + buttonRadius + ';">' +
                            '<span style="font-size: 32px; opacity: 0.5;">🏢</span>' +
                            '<div style="margin-top: 8px; color: ' + textMutedColor + '; font-size: 12px;">Logo</div></div>';
                    }
                } else {
                    for (var li = 0; li < logos.length; li++) {
                        var logo = logos[li];
                        html += '<div style="text-align: center; ' + (grayscale ? 'filter: grayscale(1); opacity: 0.7; transition: all 0.3s;' : '') + '" ' + (grayscale ? 'onmouseover="this.style.filter=\'grayscale(0)\'; this.style.opacity=\'1\';" onmouseout="this.style.filter=\'grayscale(1)\'; this.style.opacity=\'0.7\';"' : '') + '>' +
                            '<img src="' + logo.src + '" alt="' + (logo.alt || 'Logo') + '" style="max-width: 100%; max-height: 80px; object-fit: contain;"></div>';
                    }
                }

                html += '</div></div>';
                return html;
            }

            if (type === 'icon-box') {
                var ibIcon = data.icono || '⭐';
                var ibTitle = data.titulo || 'Título';
                var ibDesc = data.descripcion || 'Descripción del elemento';
                var ibLayout = data.layout || 'vertical';
                var ibIconSize = data.icono_tamano || '48px';
                var ibIconBg = data.icono_fondo || primaryColor + '15';

                var flexDir = ibLayout === 'horizontal' ? 'row' : 'column';
                var textAlign = ibLayout === 'horizontal' ? 'left' : 'center';

                return '<div class="vbp-icon-box flavor-component" style="padding: 32px; ' + customStyle + '">' +
                    '<div style="display: flex; flex-direction: ' + flexDir + '; align-items: ' + (ibLayout === 'horizontal' ? 'flex-start' : 'center') + '; gap: 20px; text-align: ' + textAlign + ';">' +
                    '<div style="width: ' + ibIconSize + '; height: ' + ibIconSize + '; background: ' + ibIconBg + '; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: calc(' + ibIconSize + ' * 0.5); flex-shrink: 0;">' + ibIcon + '</div>' +
                    '<div style="flex: 1;">' +
                    '<h4 contenteditable="true" style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: ' + textColor + ';">' + ibTitle + '</h4>' +
                    '<p contenteditable="true" style="margin: 0; color: ' + textMutedColor + '; line-height: 1.6;">' + ibDesc + '</p>' +
                    '</div></div></div>';
            }

            // ============ MÓDULOS (shortcode-based) ============
            if (data.shortcode || element.shortcode) {
                var shortcodeTag = data.shortcode || element.shortcode;
                var elementId = element.id || 'temp_' + Date.now();
                var moduleType = element.module || type || '';
                var moduleName = element.name || shortcodeTag;

                // Generar preview estática según tipo de módulo
                var modulePreview = this.generateModulePreview(moduleType, shortcodeTag, moduleName, {
                    primaryColor: primaryColor,
                    secondaryColor: secondaryColor,
                    textColor: textColor,
                    textMutedColor: textMutedColor,
                    cardRadius: cardRadius,
                    cardPadding: cardPadding,
                    data: data
                });

                // Marcar el elemento para carga diferida de previsualización real
                setTimeout(function() {
                    window.vbpModulePreview && window.vbpModulePreview.loadPreview(elementId, shortcodeTag);
                }, 100);

                return '<div class="vbp-module-block vbp-module-preview-container" data-element-id="' + elementId + '" data-shortcode="' + shortcodeTag + '" data-module="' + moduleType + '" style="' + customStyle + '">' +
                    '<div class="vbp-module-preview-content" style="min-height: 100px; position: relative;">' +
                    modulePreview +
                    '</div></div>';
            }

            // ============ MÓDULO CON PREVIEW HTML ============
            // Buscar si hay un preview_html disponible para este tipo de bloque
            var previewHtml = this.getBlockPreviewHtml(type, element);
            if (previewHtml) {
                var moduleName = element.name || type;
                return '<div class="vbp-module-preview" data-module-type="' + type + '" style="' + customStyle + '">' +
                    '<div class="vbp-module-preview-badge" style="position: absolute; top: 8px; right: 8px; background: ' + primaryColor + '; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; z-index: 10; text-transform: uppercase; letter-spacing: 0.5px;">Preview</div>' +
                    '<div class="vbp-module-preview-content" style="position: relative;">' +
                    previewHtml +
                    '</div></div>';
            }

            // ============ FALLBACK MEJORADO ============
            var fallbackIcon = this.getTypeIcon(type);
            return '<div style="padding: 32px; background: linear-gradient(135deg, ' + primaryColor + '08, ' + secondaryColor + '06); text-align: center; border-radius: ' + cardRadius + '; border: 2px dashed ' + primaryColor + '30; position: relative; ' + customStyle + '">' +
                '<div style="position: absolute; top: 8px; right: 8px; background: ' + primaryColor + '20; color: ' + primaryColor + '; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase;">Componente</div>' +
                '<div style="width: 64px; height: 64px; margin: 0 auto 16px; background: linear-gradient(135deg, ' + primaryColor + ', ' + secondaryColor + '); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; box-shadow: 0 4px 12px ' + primaryColor + '30;">' + fallbackIcon + '</div>' +
                '<div style="color: ' + textColor + '; font-weight: 600; font-size: 16px; margin-bottom: 6px;">' + (element.name || type) + '</div>' +
                '<div style="color: ' + textMutedColor + '; font-size: 12px;">' + type + '</div>' +
                '<div style="margin-top: 16px; display: flex; justify-content: center; gap: 8px;">' +
                '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: white; border-radius: 12px; font-size: 11px; color: ' + textMutedColor + '; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">✏️ Editable</span>' +
                '<span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: white; border-radius: 12px; font-size: 11px; color: ' + textMutedColor + '; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">⚙️ Configurable</span></div></div>';
        },

        /**
         * Renderiza el contenido de una columna en un bloque two_columns
         * Soporta tipos: contact_info, contact_form, text, image, etc.
         */
        renderTwoColumnContent: function(colData, lado) {
            var ds = (typeof VBP_Config !== 'undefined' && VBP_Config.designSettings) ? VBP_Config.designSettings : {};
            var primaryColor = ds.primary_color || '#3b82f6';
            var textColor = ds.text_color || '#1f2937';
            var textMutedColor = ds.text_muted_color || '#6b7280';
            var buttonRadius = (ds.button_border_radius || 8) + 'px';

            if (!colData || !colData.type) {
                return '<div style="text-align: center; color: #6b7280; padding: 20px; font-size: 12px;">📥 Columna ' + lado + '</div>';
            }

            var colType = colData.type;
            var colContent = colData.data || {};

            // Renderizar contact_info
            if (colType === 'contact_info') {
                var titulo = colContent.titulo || 'Información';
                var items = colContent.items || [];
                var html = '<div class="vbp-contact-info" style="padding: 16px;">';
                html += '<h3 style="font-size: 18px; font-weight: 600; margin: 0 0 16px; color: ' + textColor + ';">' + titulo + '</h3>';
                html += '<ul style="list-style: none; padding: 0; margin: 0;">';
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    html += '<li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">';
                    if (item.icono) {
                        html += '<span style="font-size: 18px; flex-shrink: 0;">' + item.icono + '</span>';
                    }
                    html += '<div>';
                    if (item.titulo) {
                        html += '<strong style="display: block; color: ' + textColor + '; font-size: 13px;">' + item.titulo + '</strong>';
                    }
                    if (item.valor) {
                        html += '<span style="color: ' + textMutedColor + '; font-size: 13px;">' + item.valor + '</span>';
                    }
                    html += '</div></li>';
                }
                html += '</ul></div>';
                return html;
            }

            // Renderizar contact_form
            if (colType === 'contact_form') {
                var titulo = colContent.titulo || 'Contacto';
                var campos = colContent.campos || [];
                var botonTexto = colContent.boton_texto || 'Enviar';
                var html = '<div class="vbp-contact-form" style="padding: 16px;">';
                html += '<h3 style="font-size: 18px; font-weight: 600; margin: 0 0 16px; color: ' + textColor + ';">' + titulo + '</h3>';
                html += '<form style="display: flex; flex-direction: column; gap: 12px;">';
                for (var i = 0; i < campos.length; i++) {
                    var campo = campos[i];
                    var label = campo.label || campo.nombre || 'Campo';
                    var tipo = campo.tipo || 'text';
                    var requerido = campo.requerido ? ' *' : '';
                    html += '<div style="display: flex; flex-direction: column; gap: 4px;">';
                    html += '<label style="font-size: 12px; font-weight: 500; color: ' + textColor + ';">' + label + requerido + '</label>';
                    if (tipo === 'textarea') {
                        html += '<textarea style="padding: 8px 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: 13px; resize: vertical; min-height: 80px;" placeholder="' + label + '"></textarea>';
                    } else if (tipo === 'select') {
                        html += '<select style="padding: 8px 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: 13px; background: white;">';
                        html += '<option>Selecciona...</option>';
                        var opciones = campo.opciones || [];
                        for (var o = 0; o < opciones.length; o++) {
                            html += '<option>' + opciones[o] + '</option>';
                        }
                        html += '</select>';
                    } else {
                        html += '<input type="' + tipo + '" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: ' + buttonRadius + '; font-size: 13px;" placeholder="' + label + '">';
                    }
                    html += '</div>';
                }
                html += '<button type="button" style="padding: 10px 20px; background: ' + primaryColor + '; color: white; border: none; border-radius: ' + buttonRadius + '; font-weight: 500; cursor: pointer; margin-top: 8px;">' + botonTexto + '</button>';
                html += '</form></div>';
                return html;
            }

            // Renderizar texto libre
            if (colType === 'text') {
                var contenido = colContent.contenido || 'Tu texto aquí...';
                return '<div class="vbp-column-text" style="padding: 16px; color: ' + textColor + '; line-height: 1.6; font-size: 14px;">' + contenido + '</div>';
            }

            // Renderizar imagen
            if (colType === 'image') {
                var imgSrc = colContent.src || '';
                var imgAlt = colContent.alt || 'Imagen';
                if (imgSrc) {
                    return '<div class="vbp-column-image" style="padding: 16px;"><img src="' + imgSrc + '" alt="' + imgAlt + '" style="max-width: 100%; height: auto; border-radius: ' + buttonRadius + ';"></div>';
                }
                return '<div style="padding: 32px; text-align: center; color: ' + textMutedColor + '; border: 2px dashed #ddd; border-radius: ' + buttonRadius + '; font-size: 12px;">🖼️ Añade una imagen</div>';
            }

            // Fallback: tipo desconocido
            if (colType && colContent) {
                return '<div style="padding: 16px; text-align: center; color: ' + textMutedColor + '; font-size: 12px;">📦 ' + colType + '</div>';
            }

            return '<div style="text-align: center; color: #6b7280; padding: 20px; font-size: 12px;">📥 Columna ' + lado + '</div>';
        },

        /**
         * Obtiene icono según tipo de bloque
         */
        getTypeIcon: function(type) {
            var icons = {
                'hero': '🚀', 'features': '✨', 'testimonials': '💬', 'pricing': '💰',
                'cta': '📢', 'faq': '❓', 'contact': '📧', 'team': '👥',
                'stats': '📊', 'gallery': '🖼️', 'blog': '📝', 'video-section': '🎬',
                'heading': '📌', 'text': '📄', 'image': '🖼️', 'button': '🔘',
                'divider': '➖', 'spacer': '↕️', 'icon': '⭐', 'html': '💻',
                'shortcode': '⚡', 'container': '📦', 'columns': '▦', 'row': '═',
                'grid': '▦', 'form': '📋', 'input': '📝', 'countdown': '⏱️',
                'newsletter': '📮', 'social-icons': '🔗', 'accordion': '📑',
                'tabs': '📑', 'progress-bar': '📊', 'alert': '⚠️', 'map': '🗺️'
            };
            return icons[type] || '📦';
        },

        /**
         * Busca el preview_html de un bloque en VBP_Config.blocks
         */
        getBlockPreviewHtml: function(blockType, element) {
            // Verificar si hay un preview_html en los datos del elemento
            if (element.preview_html) {
                return element.preview_html;
            }

            // Buscar en VBP_Config.blocks
            if (typeof VBP_Config !== 'undefined' && VBP_Config.blocks) {
                for (var i = 0; i < VBP_Config.blocks.length; i++) {
                    var categoria = VBP_Config.blocks[i];
                    if (categoria.blocks) {
                        for (var j = 0; j < categoria.blocks.length; j++) {
                            var bloque = categoria.blocks[j];
                            if (bloque.id === blockType && bloque.preview_html) {
                                return bloque.preview_html;
                            }
                        }
                    }
                }
            }

            return null;
        },

        /**
         * Genera preview visual según tipo de módulo
         */
        generateModulePreview: function(moduleType, shortcodeTag, moduleName, opts) {
            var pc = opts.primaryColor || '#3b82f6';
            var sc = opts.secondaryColor || '#8b5cf6';
            var tc = opts.textColor || '#1f2937';
            var tm = opts.textMutedColor || '#6b7280';
            var cr = opts.cardRadius || '12px';
            var cp = opts.cardPadding || '24px';
            var data = opts.data || {};

            // Configuración del módulo desde el inspector
            var numCols = parseInt(data.columnas) || 3;
            var itemsLimit = data.limite || '12';
            var tipoFiltro = data.tipo || '';
            var colorScheme = data.esquema_color || 'default';
            var cardStyle = data.estilo_tarjeta || 'elevated';
            var showTitle = data.mostrar_titulo !== false;
            var showFilters = data.mostrar_filtros === 'si' || data.mostrar_filtros === 'true' || data.mostrar_filtros === true;
            var customTitle = data.titulo_personalizado || '';

            // Aplicar esquema de color personalizado
            var schemeColors = {
                'default': pc,
                'primary': '#3b82f6',
                'success': '#22c55e',
                'warning': '#f59e0b',
                'danger': '#ef4444',
                'purple': '#8b5cf6',
                'dark': '#1f2937'
            };
            var themeColor = schemeColors[colorScheme] || pc;

            // Mapeo de iconos y colores según tipo de módulo
            var moduleConfig = {
                // Mapas
                'parkings': { icon: '🅿️', color: '#3b82f6', label: 'Mapa de Parkings', preview: 'map' },
                'huertos-urbanos': { icon: '🌱', color: '#22c55e', label: 'Mapa de Huertos', preview: 'map' },
                'compostaje': { icon: '♻️', color: '#84cc16', label: 'Mapa de Composteras', preview: 'map' },
                'biodiversidad-local': { icon: '🦋', color: '#10b981', label: 'Mapa Biodiversidad', preview: 'map' },
                'incidencias': { icon: '⚠️', color: '#ef4444', label: 'Mapa de Incidencias', preview: 'map' },
                // Economía
                'banco-tiempo': { icon: '⏱️', color: '#f59e0b', label: 'Banco de Tiempo', preview: 'dashboard' },
                'economia-don': { icon: '🎁', color: '#ec4899', label: 'Economía del Don', preview: 'cards' },
                'grupos-consumo': { icon: '🛒', color: '#22c55e', label: 'Grupos de Consumo', preview: 'grid' },
                'marketplace': { icon: '🏪', color: '#8b5cf6', label: 'Marketplace', preview: 'grid' },
                // Comunidad
                'eventos': { icon: '📅', color: '#6366f1', label: 'Eventos', preview: 'calendar' },
                'socios': { icon: '👥', color: '#0ea5e9', label: 'Socios', preview: 'list' },
                'comunidades': { icon: '🏘️', color: '#14b8a6', label: 'Comunidades', preview: 'cards' },
                'foros': { icon: '💬', color: '#8b5cf6', label: 'Foros', preview: 'list' },
                // Formación
                'cursos': { icon: '🎓', color: '#3b82f6', label: 'Cursos', preview: 'grid' },
                'talleres': { icon: '🔧', color: '#f97316', label: 'Talleres', preview: 'grid' },
                'biblioteca': { icon: '📚', color: '#a855f7', label: 'Biblioteca', preview: 'grid' },
                // Multimedia
                'multimedia': { icon: '🎬', color: '#ef4444', label: 'Galería', preview: 'gallery' },
                'podcast': { icon: '🎙️', color: '#ec4899', label: 'Podcast', preview: 'audio' },
                'radio': { icon: '📻', color: '#f59e0b', label: 'Radio', preview: 'audio' },
                // Dashboard widgets
                'dashboard-widget': { icon: '📊', color: '#6366f1', label: 'Widget', preview: 'widget' },
                'dashboard-widgets-grid': { icon: '📋', color: '#6366f1', label: 'Grid de Widgets', preview: 'widget-grid' },
                // Participación
                'encuestas': { icon: '📝', color: '#14b8a6', label: 'Encuestas', preview: 'form' },
                'participacion': { icon: '🗳️', color: '#8b5cf6', label: 'Participación', preview: 'form' },
                'presupuestos-participativos': { icon: '💰', color: '#22c55e', label: 'Presupuestos', preview: 'cards' },
                // Reservas
                'reservas': { icon: '📆', color: '#0ea5e9', label: 'Reservas', preview: 'calendar' },
                'espacios-comunes': { icon: '🏛️', color: '#6366f1', label: 'Espacios', preview: 'grid' }
            };

            var config = moduleConfig[moduleType] || { icon: '📦', color: pc, label: moduleName, preview: 'default' };
            var modColor = colorScheme !== 'default' ? themeColor : config.color;

            // Badge del módulo con configuración
            var configBadges = '';
            if (numCols && numCols !== 3) {
                configBadges += '<span style="background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 8px; font-size: 10px;">' + numCols + ' col</span>';
            }
            if (itemsLimit && itemsLimit !== '12') {
                configBadges += '<span style="background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 8px; font-size: 10px; margin-left: 4px;">' + (itemsLimit === '-1' ? '∞' : itemsLimit) + ' items</span>';
            }
            if (tipoFiltro) {
                configBadges += '<span style="background: rgba(255,255,255,0.3); padding: 2px 6px; border-radius: 8px; font-size: 10px; margin-left: 4px;">' + tipoFiltro + '</span>';
            }
            var badge = '<div style="position: absolute; top: 8px; right: 8px; background: ' + modColor + '; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; z-index: 10; display: flex; align-items: center; gap: 6px;">' +
                '<span>' + config.icon + '</span> <span>' + config.label + '</span>' + (configBadges ? configBadges : '') + '</div>';

            // Título personalizado si está configurado
            var titleSection = '';
            if (showTitle && customTitle) {
                titleSection = '<div style="margin-bottom: 16px; text-align: center;"><h3 style="margin: 0; font-size: 20px; color: ' + tc + ';">' + customTitle + '</h3></div>';
            }

            // Filtros si están activados
            var filtersSection = '';
            if (showFilters) {
                filtersSection = '<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">' +
                    '<span style="padding: 6px 12px; background: ' + modColor + '; color: white; border-radius: 16px; font-size: 12px;">Todos</span>' +
                    '<span style="padding: 6px 12px; background: #f3f4f6; color: ' + tm + '; border-radius: 16px; font-size: 12px;">Categoría 1</span>' +
                    '<span style="padding: 6px 12px; background: #f3f4f6; color: ' + tm + '; border-radius: 16px; font-size: 12px;">Categoría 2</span></div>';
            }

            // Paginación visual
            var paginationSection = '';
            if (pagination === 'numbers') {
                paginationSection = '<div style="display: flex; justify-content: center; gap: 6px; margin-top: 16px;">' +
                    '<span style="padding: 6px 10px; background: ' + modColor + '; color: white; border-radius: 6px; font-size: 12px;">1</span>' +
                    '<span style="padding: 6px 10px; background: #f3f4f6; color: ' + tm + '; border-radius: 6px; font-size: 12px;">2</span>' +
                    '<span style="padding: 6px 10px; background: #f3f4f6; color: ' + tm + '; border-radius: 6px; font-size: 12px;">3</span>' +
                    '<span style="padding: 6px 10px; color: ' + tm + '; font-size: 12px;">→</span></div>';
            } else if (pagination === 'loadmore') {
                paginationSection = '<div style="text-align: center; margin-top: 16px;">' +
                    '<button style="padding: 10px 24px; background: ' + modColor + '15; color: ' + modColor + '; border: 1px solid ' + modColor + '30; border-radius: 8px; font-size: 13px; font-weight: 500;">Cargar más</button></div>';
            } else if (pagination === 'infinite') {
                paginationSection = '<div style="text-align: center; margin-top: 16px; color: ' + tm + '; font-size: 12px;">' +
                    '<span style="display: inline-flex; align-items: center; gap: 6px;">⏳ Scroll infinito activo</span></div>';
            }

            // Generar preview según tipo
            var previewContent = '';

            switch(config.preview) {
                case 'map':
                    previewContent = '<div style="height: 250px; background: linear-gradient(135deg, #e8f4ea 0%, #d4e8d8 100%); border-radius: ' + cr + '; position: relative; overflow: hidden;">' +
                        '<div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column;">' +
                        '<div style="font-size: 48px; margin-bottom: 12px;">' + config.icon + '</div>' +
                        '<div style="font-size: 18px; font-weight: 600; color: ' + tc + ';">' + config.label + '</div>' +
                        '<div style="font-size: 13px; color: ' + tm + '; margin-top: 4px;">Mapa interactivo</div></div>' +
                        '<div style="position: absolute; bottom: 0; left: 0; right: 0; height: 40px; background: ' + modColor + '20; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12px; color: ' + tm + ';">' +
                        '<span>🔍 Filtros</span> <span>📍 Marcadores</span> <span>📋 Listado</span></div></div>';
                    break;

                case 'dashboard':
                    previewContent = '<div style="padding: ' + cp + '; background: linear-gradient(135deg, ' + modColor + '10, ' + modColor + '05); border-radius: ' + cr + '; border: 1px solid ' + modColor + '20;">' +
                        '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' +
                        '<div style="font-size: 28px; font-weight: 700; color: ' + modColor + ';">24</div><div style="font-size: 12px; color: ' + tm + ';">Activos</div></div>' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' +
                        '<div style="font-size: 28px; font-weight: 700; color: #22c55e;">156h</div><div style="font-size: 12px; color: ' + tm + ';">Total</div></div>' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' +
                        '<div style="font-size: 28px; font-weight: 700; color: #f59e0b;">87%</div><div style="font-size: 12px; color: ' + tm + ';">Completado</div></div></div>' +
                        '<div style="text-align: center; font-size: 13px; color: ' + tm + ';">' + config.icon + ' ' + config.label + '</div></div>';
                    break;

                case 'grid':
                    // Generar items según número de columnas
                    var gridItems = '';
                    var gridColsDisplay = Math.min(numCols || 3, 4);
                    for (var gi = 0; gi < gridColsDisplay; gi++) {
                        var cardBg = cardStyle === 'glass' ? 'rgba(255,255,255,0.7); backdrop-filter: blur(10px)' :
                                     cardStyle === 'filled' ? modColor + '10' :
                                     cardStyle === 'outlined' ? 'white; border: 2px solid ' + modColor + '30' : 'white';
                        var cardShadow = cardStyle === 'elevated' ? '0 4px 12px rgba(0,0,0,0.1)' :
                                         cardStyle === 'minimal' ? 'none' : '0 1px 3px rgba(0,0,0,0.08)';
                        gridItems += '<div style="background: ' + cardBg + '; height: 120px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: ' + cardShadow + ';">' + config.icon + '</div>';
                    }
                    previewContent = '<div style="padding: ' + cp + '; background: ' + modColor + '05; border-radius: ' + cr + '; border: 1px solid ' + modColor + '15;">' +
                        titleSection + filtersSection +
                        '<div style="display: grid; grid-template-columns: repeat(' + gridColsDisplay + ', 1fr); gap: 12px;">' +
                        gridItems + '</div>' +
                        paginationSection +
                        '<div style="text-align: center; margin-top: 16px; font-size: 14px; color: ' + tm + ';">' + config.icon + ' ' + config.label + '</div></div>';
                    break;

                case 'cards':
                    var cardsColsDisplay = Math.min(numCols || 2, 4);
                    var cardsItems = '';
                    for (var ci = 0; ci < cardsColsDisplay; ci++) {
                        var cardBgCards = cardStyle === 'glass' ? 'rgba(255,255,255,0.7); backdrop-filter: blur(10px)' :
                                          cardStyle === 'filled' ? modColor + '10' :
                                          cardStyle === 'outlined' ? 'white; border: 2px solid ' + modColor + '30' : 'white';
                        cardsItems += '<div style="background: ' + cardBgCards + '; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
                            '<div style="width: 40px; height: 40px; background: ' + modColor + '20; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px;">' + config.icon + '</div>' +
                            '<div style="height: 10px; background: #e5e7eb; border-radius: 4px; width: ' + (70 + ci * 5) + '%; margin-bottom: 8px;"></div>' +
                            '<div style="height: 8px; background: #f3f4f6; border-radius: 4px; width: ' + (50 + ci * 5) + '%;"></div></div>';
                    }
                    previewContent = '<div style="padding: ' + cp + '; background: ' + modColor + '05; border-radius: ' + cr + '; border: 1px solid ' + modColor + '15;">' +
                        titleSection + filtersSection +
                        '<div style="display: grid; grid-template-columns: repeat(' + cardsColsDisplay + ', 1fr); gap: 16px;">' +
                        cardsItems + '</div>' +
                        paginationSection +
                        '<div style="text-align: center; margin-top: 16px; font-size: 14px; color: ' + tm + ';">' + config.label + '</div></div>';
                    break;

                case 'calendar':
                    previewContent = '<div style="padding: ' + cp + '; background: white; border-radius: ' + cr + '; border: 1px solid ' + modColor + '20;">' +
                        '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">' +
                        '<span style="font-weight: 600; color: ' + tc + ';">Marzo 2026</span>' +
                        '<div style="display: flex; gap: 8px;"><span style="cursor: pointer;">◀</span><span style="cursor: pointer;">▶</span></div></div>' +
                        '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; font-size: 12px;">' +
                        '<div style="color: ' + tm + '; padding: 8px 0;">L</div><div style="color: ' + tm + '; padding: 8px 0;">M</div><div style="color: ' + tm + '; padding: 8px 0;">X</div><div style="color: ' + tm + '; padding: 8px 0;">J</div><div style="color: ' + tm + '; padding: 8px 0;">V</div><div style="color: ' + tm + '; padding: 8px 0;">S</div><div style="color: ' + tm + '; padding: 8px 0;">D</div>' +
                        '<div style="padding: 8px 0;">1</div><div style="padding: 8px 0;">2</div><div style="padding: 8px 0;">3</div><div style="padding: 8px 0; background: ' + modColor + '; color: white; border-radius: 50%;">4</div><div style="padding: 8px 0;">5</div><div style="padding: 8px 0;">6</div><div style="padding: 8px 0;">7</div>' +
                        '<div style="padding: 8px 0;">8</div><div style="padding: 8px 0; background: ' + modColor + '20; border-radius: 50%;">9</div><div style="padding: 8px 0;">10</div><div style="padding: 8px 0;">11</div><div style="padding: 8px 0;">12</div><div style="padding: 8px 0;">13</div><div style="padding: 8px 0;">14</div></div>' +
                        '<div style="text-align: center; margin-top: 12px; font-size: 13px; color: ' + tm + ';">' + config.icon + ' ' + config.label + '</div></div>';
                    break;

                case 'list':
                    previewContent = '<div style="padding: ' + cp + '; background: white; border-radius: ' + cr + '; border: 1px solid #e5e7eb;">' +
                        titleSection + filtersSection +
                        '<div style="display: flex; flex-direction: column; gap: 12px;">' +
                        '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 8px;">' +
                        '<div style="width: 36px; height: 36px; background: ' + modColor + '20; border-radius: 50%; display: flex; align-items: center; justify-content: center;">' + config.icon + '</div>' +
                        '<div style="flex: 1;"><div style="height: 10px; background: #e5e7eb; border-radius: 4px; width: 70%; margin-bottom: 6px;"></div><div style="height: 8px; background: #f3f4f6; border-radius: 4px; width: 50%;"></div></div></div>' +
                        '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 8px;">' +
                        '<div style="width: 36px; height: 36px; background: ' + modColor + '20; border-radius: 50%; display: flex; align-items: center; justify-content: center;">' + config.icon + '</div>' +
                        '<div style="flex: 1;"><div style="height: 10px; background: #e5e7eb; border-radius: 4px; width: 60%; margin-bottom: 6px;"></div><div style="height: 8px; background: #f3f4f6; border-radius: 4px; width: 40%;"></div></div></div>' +
                        '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 8px;">' +
                        '<div style="width: 36px; height: 36px; background: ' + modColor + '20; border-radius: 50%; display: flex; align-items: center; justify-content: center;">' + config.icon + '</div>' +
                        '<div style="flex: 1;"><div style="height: 10px; background: #e5e7eb; border-radius: 4px; width: 80%; margin-bottom: 6px;"></div><div style="height: 8px; background: #f3f4f6; border-radius: 4px; width: 55%;"></div></div></div></div>' +
                        paginationSection +
                        '<div style="text-align: center; margin-top: 12px; font-size: 13px; color: ' + tm + ';">' + config.label + '</div></div>';
                    break;

                case 'gallery':
                    previewContent = '<div style="padding: ' + cp + '; background: #1a1a2e; border-radius: ' + cr + ';">' +
                        '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, ' + modColor + ', ' + sc + '); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🖼️</div>' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, ' + sc + ', #ec4899); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📷</div>' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, #ec4899, #f59e0b); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🎬</div>' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, #f59e0b, #22c55e); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">🎨</div>' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, #22c55e, #0ea5e9); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📸</div>' +
                        '<div style="aspect-ratio: 1; background: linear-gradient(135deg, #0ea5e9, ' + modColor + '); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white;">+12</div></div>' +
                        '<div style="text-align: center; margin-top: 12px; font-size: 13px; color: rgba(255,255,255,0.7);">' + config.icon + ' ' + config.label + '</div></div>';
                    break;

                case 'audio':
                    previewContent = '<div style="padding: ' + cp + '; background: linear-gradient(135deg, ' + modColor + ', ' + sc + '); border-radius: ' + cr + ';">' +
                        '<div style="display: flex; align-items: center; gap: 16px;">' +
                        '<div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 36px;">' + config.icon + '</div>' +
                        '<div style="flex: 1; color: white;">' +
                        '<div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">' + config.label + '</div>' +
                        '<div style="height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; margin-bottom: 8px;"><div style="height: 100%; width: 35%; background: white; border-radius: 2px;"></div></div>' +
                        '<div style="display: flex; justify-content: space-between; font-size: 12px; opacity: 0.8;"><span>1:23</span><span>3:45</span></div></div>' +
                        '<button style="width: 48px; height: 48px; border-radius: 50%; background: white; border: none; font-size: 20px; cursor: pointer;">▶️</button></div></div>';
                    break;

                case 'widget':
                    previewContent = '<div style="padding: ' + cp + '; background: white; border-radius: ' + cr + '; box-shadow: 0 4px 6px rgba(0,0,0,0.07);">' +
                        '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">' +
                        '<span style="font-weight: 600; color: ' + tc + '; display: flex; align-items: center; gap: 8px;">' + config.icon + ' Widget</span>' +
                        '<span style="font-size: 20px;">⋮</span></div>' +
                        '<div style="display: flex; justify-content: center; align-items: center; height: 80px; background: ' + modColor + '10; border-radius: 8px;">' +
                        '<div style="text-align: center;"><div style="font-size: 32px; font-weight: 700; color: ' + modColor + ';">42</div><div style="font-size: 12px; color: ' + tm + ';">Contenido dinámico</div></div></div></div>';
                    break;

                case 'widget-grid':
                    previewContent = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 16px; background: #f3f4f6; border-radius: ' + cr + ';">' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
                        '<div style="font-size: 24px; margin-bottom: 8px;">📊</div><div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 70%;"></div></div>' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
                        '<div style="font-size: 24px; margin-bottom: 8px;">📈</div><div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 60%;"></div></div>' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
                        '<div style="font-size: 24px; margin-bottom: 8px;">📋</div><div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 80%;"></div></div>' +
                        '<div style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
                        '<div style="font-size: 24px; margin-bottom: 8px;">📅</div><div style="height: 8px; background: #e5e7eb; border-radius: 4px; width: 55%;"></div></div></div>';
                    break;

                case 'form':
                    previewContent = '<div style="padding: ' + cp + '; background: white; border-radius: ' + cr + '; border: 1px solid #e5e7eb;">' +
                        '<div style="text-align: center; margin-bottom: 20px;">' +
                        '<div style="font-size: 32px; margin-bottom: 8px;">' + config.icon + '</div>' +
                        '<div style="font-weight: 600; color: ' + tc + ';">' + config.label + '</div></div>' +
                        '<div style="display: flex; flex-direction: column; gap: 12px;">' +
                        '<div style="height: 40px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;"></div>' +
                        '<div style="height: 40px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;"></div>' +
                        '<div style="height: 80px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;"></div>' +
                        '<button style="padding: 12px; background: ' + modColor + '; color: white; border: none; border-radius: 8px; font-weight: 500;">Enviar</button></div></div>';
                    break;

                default:
                    previewContent = '<div style="padding: 32px; background: linear-gradient(135deg, ' + modColor + '10, ' + modColor + '05); border-radius: ' + cr + '; border: 1px solid ' + modColor + '20; text-align: center;">' +
                        '<div style="font-size: 48px; margin-bottom: 16px;">' + config.icon + '</div>' +
                        '<div style="font-size: 18px; font-weight: 600; color: ' + tc + '; margin-bottom: 8px;">' + moduleName + '</div>' +
                        '<code style="display: inline-block; padding: 6px 12px; background: ' + modColor + '15; color: ' + modColor + '; border-radius: 6px; font-size: 12px;">[' + shortcodeTag + ']</code></div>';
            }

            return '<div style="position: relative;">' + badge + previewContent + '</div>';
        },

        buildInlineStyles: function(styles) {
            if (!styles) return '';
            var css = '';

            // Spacing
            if (styles.spacing) {
                if (styles.spacing.margin) {
                    if (styles.spacing.margin.top) css += 'margin-top: ' + styles.spacing.margin.top + '; ';
                    if (styles.spacing.margin.right) css += 'margin-right: ' + styles.spacing.margin.right + '; ';
                    if (styles.spacing.margin.bottom) css += 'margin-bottom: ' + styles.spacing.margin.bottom + '; ';
                    if (styles.spacing.margin.left) css += 'margin-left: ' + styles.spacing.margin.left + '; ';
                }
                if (styles.spacing.padding) {
                    if (styles.spacing.padding.top) css += 'padding-top: ' + styles.spacing.padding.top + '; ';
                    if (styles.spacing.padding.right) css += 'padding-right: ' + styles.spacing.padding.right + '; ';
                    if (styles.spacing.padding.bottom) css += 'padding-bottom: ' + styles.spacing.padding.bottom + '; ';
                    if (styles.spacing.padding.left) css += 'padding-left: ' + styles.spacing.padding.left + '; ';
                }
            }

            // Colors
            if (styles.colors) {
                if (styles.colors.background) css += 'background-color: ' + styles.colors.background + '; ';
                if (styles.colors.text) css += 'color: ' + styles.colors.text + '; ';
            }

            // Typography
            if (styles.typography) {
                if (styles.typography.fontSize) css += 'font-size: ' + styles.typography.fontSize + '; ';
                if (styles.typography.fontWeight) css += 'font-weight: ' + styles.typography.fontWeight + '; ';
                if (styles.typography.lineHeight) css += 'line-height: ' + styles.typography.lineHeight + '; ';
                if (styles.typography.textAlign) css += 'text-align: ' + styles.typography.textAlign + '; ';
            }

            // Layout (flex/grid)
            if (styles.layout) {
                if (styles.layout.display) css += 'display: ' + styles.layout.display + '; ';
                if (styles.layout.flexDirection) css += 'flex-direction: ' + styles.layout.flexDirection + '; ';
                if (styles.layout.justifyContent) css += 'justify-content: ' + styles.layout.justifyContent + '; ';
                if (styles.layout.alignItems) css += 'align-items: ' + styles.layout.alignItems + '; ';
                if (styles.layout.gap) css += 'gap: ' + styles.layout.gap + '; ';
            }

            // Borders
            if (styles.borders) {
                if (styles.borders.radius) css += 'border-radius: ' + styles.borders.radius + '; ';
                if (styles.borders.width) css += 'border-width: ' + styles.borders.width + '; ';
                if (styles.borders.color) css += 'border-color: ' + styles.borders.color + '; ';
                if (styles.borders.style) css += 'border-style: ' + styles.borders.style + '; ';
            }

            // Shadows
            if (styles.shadows && styles.shadows.boxShadow) {
                css += 'box-shadow: ' + styles.shadows.boxShadow + '; ';
            }

            // Dimensions
            if (styles.dimensions) {
                if (styles.dimensions.width) css += 'width: ' + styles.dimensions.width + '; ';
                if (styles.dimensions.height) css += 'height: ' + styles.dimensions.height + '; ';
                if (styles.dimensions.minHeight) css += 'min-height: ' + styles.dimensions.minHeight + '; ';
                if (styles.dimensions.maxWidth) css += 'max-width: ' + styles.dimensions.maxWidth + '; ';
            }

            // Background avanzado (gradient, image)
            if (styles.background && styles.background.type) {
                if (styles.background.type === 'gradient') {
                    var dir = styles.background.gradientDirection || 'to bottom';
                    var start = styles.background.gradientStart || '#3b82f6';
                    var end = styles.background.gradientEnd || '#8b5cf6';
                    css += 'background: linear-gradient(' + dir + ', ' + start + ', ' + end + '); ';
                } else if (styles.background.type === 'image' && styles.background.image) {
                    var bgSize = styles.background.size || 'cover';
                    var bgPos = styles.background.position || 'center';
                    var bgRepeat = styles.background.repeat || 'no-repeat';
                    css += 'background-image: url(' + styles.background.image + '); ';
                    css += 'background-size: ' + bgSize + '; ';
                    css += 'background-position: ' + bgPos + '; ';
                    css += 'background-repeat: ' + bgRepeat + '; ';
                    if (styles.background.fixed) {
                        css += 'background-attachment: fixed; ';
                    }
                }
            }

            // Position
            if (styles.position) {
                if (styles.position.position) css += 'position: ' + styles.position.position + '; ';
                if (styles.position.top) css += 'top: ' + styles.position.top + '; ';
                if (styles.position.right) css += 'right: ' + styles.position.right + '; ';
                if (styles.position.bottom) css += 'bottom: ' + styles.position.bottom + '; ';
                if (styles.position.left) css += 'left: ' + styles.position.left + '; ';
                if (styles.position.zIndex) css += 'z-index: ' + styles.position.zIndex + '; ';
            }

            // Transform
            if (styles.transform) {
                var transforms = [];
                if (styles.transform.rotate) transforms.push('rotate(' + styles.transform.rotate + 'deg)');
                if (styles.transform.scale && styles.transform.scale !== '1') transforms.push('scale(' + styles.transform.scale + ')');
                if (styles.transform.translateX) transforms.push('translateX(' + styles.transform.translateX + ')');
                if (styles.transform.translateY) transforms.push('translateY(' + styles.transform.translateY + ')');
                if (styles.transform.skewX) transforms.push('skewX(' + styles.transform.skewX + 'deg)');
                if (styles.transform.skewY) transforms.push('skewY(' + styles.transform.skewY + 'deg)');
                if (transforms.length > 0) {
                    css += 'transform: ' + transforms.join(' ') + '; ';
                }
            }

            // Overflow
            if (styles.overflow) {
                css += 'overflow: ' + styles.overflow + '; ';
            }

            // Opacity
            if (styles.opacity && styles.opacity !== '' && styles.opacity !== '1') {
                css += 'opacity: ' + styles.opacity + '; ';
            }

            return css;
        },

        handleDragOver: function(event) {
            event.preventDefault();

            // Detectar si estamos sobre un dropzone de contenedor
            var dropzone = event.target.closest('.vbp-column-dropzone, .vbp-container-dropzone');

            if (dropzone) {
                // Estamos sobre un contenedor - mostrar indicador en el contenedor
                this.dropIndicator.visible = false;
                dropzone.classList.add('vbp-dropzone-active');

                // Remover clase de otros dropzones
                document.querySelectorAll('.vbp-dropzone-active').forEach(function(el) {
                    if (el !== dropzone) el.classList.remove('vbp-dropzone-active');
                });
            } else {
                // Drop en canvas principal
                document.querySelectorAll('.vbp-dropzone-active').forEach(function(el) {
                    el.classList.remove('vbp-dropzone-active');
                });

                var canvasRect = this.$refs.canvas.getBoundingClientRect();
                var y = event.clientY - canvasRect.top;
                this.dropIndicator.visible = true;
                this.dropIndicator.y = this.getDropPosition(y);
            }
        },

        handleDrop: function(event) {
            event.preventDefault();
            this.dropIndicator.visible = false;

            // Limpiar indicadores de dropzone
            document.querySelectorAll('.vbp-dropzone-active').forEach(function(el) {
                el.classList.remove('vbp-dropzone-active');
            });

            var blockData = event.dataTransfer.getData('application/json');
            if (!blockData) return;

            var block = JSON.parse(blockData);
            var store = Alpine.store('vbp');

            // Detectar si el drop fue dentro de un contenedor
            var columnDropzone = event.target.closest('.vbp-column-dropzone');
            var containerDropzone = event.target.closest('.vbp-container-dropzone');

            if (columnDropzone) {
                // Drop en una columna específica
                var containerId = columnDropzone.getAttribute('data-container-id');
                var columnIndex = parseInt(columnDropzone.getAttribute('data-column-index') || '0');

                if (containerId) {
                    store.addElementToContainer(block.type, containerId, columnIndex);
                    this.showNotification('Elemento añadido a la columna ' + (columnIndex + 1), 'success');
                    return;
                }
            } else if (containerDropzone) {
                // Drop en un contenedor (no en columna específica)
                var containerId = containerDropzone.getAttribute('data-container-id');

                if (containerId) {
                    store.addElementToContainer(block.type, containerId, 0);
                    this.showNotification('Elemento añadido al contenedor', 'success');
                    return;
                }
            }

            // Drop en canvas principal
            var index = this.getDropIndex(event.clientY);
            store.addElement(block.type, index);
        },

        getDropPosition: function(y) {
            var canvas = this.$refs.canvas;
            var position = 0;
            var canvasRect = canvas.getBoundingClientRect();
            canvas.querySelectorAll('.vbp-element').forEach(function(el) {
                var rect = el.getBoundingClientRect();
                var elY = rect.top - canvasRect.top + rect.height / 2;
                if (y > elY) { position = rect.bottom - canvasRect.top; }
            });
            return position;
        },

        getDropIndex: function(clientY) {
            var canvas = this.$refs.canvas;
            var canvasRect = canvas.getBoundingClientRect();
            var y = clientY - canvasRect.top;
            var index = Alpine.store('vbp').elements.length;
            canvas.querySelectorAll('.vbp-element').forEach(function(el, i) {
                var rect = el.getBoundingClientRect();
                var elY = rect.top - canvasRect.top + rect.height / 2;
                if (y < elY && index === Alpine.store('vbp').elements.length) { index = i; }
            });
            return index;
        },

        handleElementDragStart: function(event, element) {
            if (element.locked) { event.preventDefault(); return; }
            this.draggedElement = element;
            event.dataTransfer.effectAllowed = 'move';
        },

        handleElementDragEnd: function(event) {
            this.draggedElement = null;
            this.dropIndicator.visible = false;
        },

        drawRulers: function() {
            var rulerH = document.getElementById('vbp-ruler-h');
            var rulerV = document.getElementById('vbp-ruler-v');
            if (rulerH) this.drawRuler(rulerH, 'horizontal');
            if (rulerV) this.drawRuler(rulerV, 'vertical');
        },

        drawRuler: function(canvas, direction) {
            var ctx = canvas.getContext('2d');
            var isHorizontal = direction === 'horizontal';
            var length = isHorizontal ? canvas.width : canvas.height;
            ctx.fillStyle = '#242424';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#666';
            ctx.font = '9px sans-serif';
            ctx.textAlign = 'center';
            for (var i = 0; i < length; i += 10) {
                var tickHeight = i % 100 === 0 ? 12 : (i % 50 === 0 ? 8 : 4);
                ctx.fillStyle = '#444';
                if (isHorizontal) { ctx.fillRect(i, 20 - tickHeight, 1, tickHeight); }
                else { ctx.fillRect(20 - tickHeight, i, tickHeight, 1); }
                if (i % 100 === 0 && i > 0) {
                    ctx.fillStyle = '#666';
                    if (isHorizontal) { ctx.fillText(i.toString(), i, 8); }
                    else { ctx.save(); ctx.translate(8, i); ctx.rotate(-Math.PI / 2); ctx.fillText(i.toString(), 0, 0); ctx.restore(); }
                }
            }
        },

        showNotification: function(message, type) {
            type = type || 'info';
            var id = Date.now();
            this.notifications.push({ id: id, message: message, type: type, visible: true });
            var self = this;
            setTimeout(function() { self.dismissNotification(id); }, 4000);
        },

        dismissNotification: function(id) {
            var self = this;
            var index = this.notifications.findIndex(function(n) { return n.id === id; });
            if (index !== -1) {
                this.notifications[index].visible = false;
                setTimeout(function() { self.notifications.splice(index, 1); }, 300);
            }
        },

        handleKeydown: function(event) {
            var isCtrl = event.ctrlKey || event.metaKey;

            // Abrir paleta de comandos con Ctrl+K o Cmd+K
            if (isCtrl && event.key === 'k') {
                event.preventDefault();
                this.openCommandPalette();
                return;
            }

            // Abrir ayuda con ?
            if (event.key === '?' && !event.target.closest('[contenteditable], input, textarea')) {
                event.preventDefault();
                this.showHelpModal = true;
                return;
            }

            if (isCtrl && event.key === 's') { event.preventDefault(); this.saveDocument(); }
            if (isCtrl && event.key === 'z' && !event.shiftKey) { event.preventDefault(); Alpine.store('vbp').undo(); }
            if (isCtrl && event.key === 'z' && event.shiftKey) { event.preventDefault(); Alpine.store('vbp').redo(); }
            if (isCtrl && event.key === 't') { event.preventDefault(); this.showTemplatesModal = true; }
            if (isCtrl && event.key === 'e') { event.preventDefault(); this.showExportModal = true; }
            if (isCtrl && event.key === 'u') { event.preventDefault(); this.openUnsplash(); }
            if (isCtrl && event.shiftKey && event.key === 'S') { event.preventDefault(); this.saveAsTemplate(); }
            if (isCtrl && event.shiftKey && (event.key === 'G' || event.key === 'g')) { event.preventDefault(); this.saveAsGlobalWidget(); }
            if (event.key === 'Delete' || event.key === 'Backspace') {
                var selection = Alpine.store('vbp').selection;
                if (selection.elementIds.length > 0 && !event.target.closest('[contenteditable]')) {
                    event.preventDefault();
                    selection.elementIds.forEach(function(id) { Alpine.store('vbp').removeElement(id); });
                }
            }
            if (event.key === 'Escape') {
                if (this.showCommandPalette) { this.showCommandPalette = false; }
                else if (this.showHelpModal) { this.showHelpModal = false; }
                else { this.clearSelection(); }
            }
        },

        // ============ COMMAND PALETTE ============
        openCommandPalette: function() {
            this.showCommandPalette = true;
            this.commandSearch = '';
            this.commandIndex = 0;
            this.filteredCommands = this.commands.slice();
            var self = this;
            this.$nextTick(function() {
                if (self.$refs.commandInput) {
                    self.$refs.commandInput.focus();
                }
            });
        },

        filterCommands: function() {
            var search = this.commandSearch.toLowerCase();
            if (!search) {
                this.filteredCommands = this.commands.slice();
            } else {
                this.filteredCommands = this.commands.filter(function(cmd) {
                    return cmd.name.toLowerCase().includes(search) || cmd.id.toLowerCase().includes(search);
                });
            }
            this.commandIndex = 0;
        },

        executeCommand: function(cmd) {
            if (!cmd) return;
            this.showCommandPalette = false;
            var self = this;
            var store = Alpine.store('vbp');

            switch (cmd.action) {
                case 'save': this.saveDocument(); break;
                case 'undo': store.undo(); break;
                case 'redo': store.redo(); break;
                case 'copy': document.dispatchEvent(new CustomEvent('vbp:command', { detail: { action: 'copy' } })); break;
                case 'paste': document.dispatchEvent(new CustomEvent('vbp:command', { detail: { action: 'paste' } })); break;
                case 'duplicate':
                    store.selection.elementIds.forEach(function(id) { store.duplicateElement(id); });
                    break;
                case 'delete':
                    store.selection.elementIds.forEach(function(id) { store.removeElement(id); });
                    break;
                case 'saveAsGlobal':
                    this.saveAsGlobalWidget();
                    break;
                case 'selectAll':
                    store.setSelection(store.elements.map(function(el) { return el.id; }));
                    break;
                case 'deselect': store.clearSelection(); break;
                case 'zoomIn': this.zoomIn(); break;
                case 'zoomOut': this.zoomOut(); break;
                case 'zoomReset': this.zoom = 100; store.zoom = 100; break;
                case 'preview':
                    if (VBP_Config.previewUrl) { window.open(VBP_Config.previewUrl, '_blank'); }
                    break;
                case 'help': this.showHelpModal = true; break;
                case 'togglePanels':
                    var allVisible = this.panels.blocks && this.panels.inspector && this.panels.layers;
                    this.panels.blocks = !allVisible;
                    this.panels.inspector = !allVisible;
                    this.panels.layers = !allVisible;
                    break;
                case 'addHero': store.addElement('hero'); break;
                case 'addText': store.addElement('text'); break;
                case 'addImage': store.addElement('image'); break;
                case 'addButton': store.addElement('button'); break;
                case 'templates': this.showTemplatesModal = true; break;
                case 'export': this.showExportModal = true; break;
                case 'unsplash': this.openUnsplash(); break;
                case 'versionHistory': this.openVersionHistory(); break;
            }
        },

        // ============ TEMPLATES ============

        get filteredTemplates() {
            var self = this;
            var result = this.templates.slice();

            if (this.templateSearch) {
                var search = this.templateSearch.toLowerCase();
                result = result.filter(function(t) {
                    return t.title.toLowerCase().includes(search) ||
                           (t.description && t.description.toLowerCase().includes(search));
                });
            }

            if (this.templateCategory) {
                result = result.filter(function(t) {
                    return t.category === self.templateCategory;
                });
            }

            return result;
        },

        loadTemplates: function() {
            var self = this;
            fetch(VBP_Config.restUrl + 'templates', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Respuesta no es JSON válido');
                }
                return response.json();
            })
            .then(function(data) {
                self.templates = data.library || [];
                self.userTemplates = data.user || [];
            })
            .catch(function(error) {
                console.warn('Error cargando templates (usando librería vacía):', error.message);
                self.templates = [];
                self.userTemplates = [];
            });
        },

        selectTemplate: function(template) {
            if (confirm(VBP_Config.strings.confirmApplyTemplate || '¿Aplicar este template? Se reemplazará el contenido actual.')) {
                this.applyTemplate(template);
            }
        },

        previewTemplate: function(template) {
            if (template.preview_url) {
                window.open(template.preview_url, '_blank');
            } else {
                this.showNotification('Preview no disponible', 'warning');
            }
        },

        applyTemplate: function(template) {
            var self = this;

            fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/apply-template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ template_id: template.id })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.document) {
                    Alpine.store('vbp').elements = sanitizeElements(result.document.elements || []);
                    Alpine.store('vbp').settings = result.document.settings || {};
                    self.showNotification('Template aplicado correctamente', 'success');
                    self.showTemplatesModal = false;
                }
            })
            .catch(function(error) {
                self.showNotification('Error aplicando template: ' + error.message, 'error');
            });
        },

        deleteTemplate: function(template) {
            if (!confirm(VBP_Config.strings.confirmDeleteTemplate || '¿Eliminar este template?')) {
                return;
            }

            var self = this;
            fetch(VBP_Config.restUrl + 'templates/' + template.id, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.userTemplates = self.userTemplates.filter(function(t) { return t.id !== template.id; });
                    self.showNotification('Template eliminado', 'success');
                }
            })
            .catch(function(error) {
                self.showNotification('Error eliminando template', 'error');
            });
        },

        saveAsTemplate: function() {
            this.newTemplateName = this.documentTitle + ' - Template';
            this.newTemplateCategory = 'landing';
            this.newTemplateDescription = '';
            this.showSaveTemplateModal = true;
        },

        confirmSaveTemplate: function() {
            if (!this.newTemplateName.trim()) return;

            this.isSavingTemplate = true;
            var self = this;

            fetch(VBP_Config.restUrl + 'templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    post_id: VBP_Config.postId,
                    name: this.newTemplateName,
                    category: this.newTemplateCategory,
                    description: this.newTemplateDescription
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.showNotification('Template guardado correctamente', 'success');
                    self.showSaveTemplateModal = false;
                    self.loadTemplates(); // Recargar lista de templates
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
            })
            .catch(function(error) {
                self.showNotification('Error guardando template: ' + error.message, 'error');
            })
            .finally(function() {
                self.isSavingTemplate = false;
            });
        },

        // ============ IMPORT/EXPORT ============

        handleImportDrop: function(event) {
            this.importDragOver = false;
            var files = event.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/json') {
                this.readImportFile(files[0]);
            }
        },

        handleImportFile: function(event) {
            var files = event.target.files;
            if (files.length > 0) {
                this.readImportFile(files[0]);
            }
        },

        readImportFile: function(file) {
            var self = this;
            var reader = new FileReader();

            reader.onload = function(e) {
                try {
                    var data = JSON.parse(e.target.result);
                    self.importData(data);
                } catch (error) {
                    self.showNotification('Archivo JSON inválido', 'error');
                }
            };

            reader.readAsText(file);
        },

        importFromJson: function() {
            if (!this.importJsonText.trim()) return;

            try {
                var data = JSON.parse(this.importJsonText);
                this.importData(data);
            } catch (error) {
                this.showNotification('JSON inválido: ' + error.message, 'error');
            }
        },

        importData: function(data) {
            if (!data.elements && !data.settings) {
                this.showNotification('Formato de datos inválido', 'error');
                return;
            }

            if (!confirm(VBP_Config.strings.confirmImport || '¿Importar este diseño? Se reemplazará el contenido actual.')) {
                return;
            }

            if (data.elements) {
                Alpine.store('vbp').elements = sanitizeElements(data.elements);
            }
            if (data.settings) {
                Alpine.store('vbp').settings = data.settings;
            }

            Alpine.store('vbp').isDirty = true;
            this.showNotification('Diseño importado correctamente', 'success');
            this.showTemplatesModal = false;
            this.importJsonText = '';
        },

        getExportJson: function() {
            var data = {
                version: '2.0',
                exported: new Date().toISOString(),
                elements: Alpine.store('vbp').elements,
                settings: Alpine.store('vbp').settings
            };
            return JSON.stringify(data, null, 2);
        },

        exportAsJson: function() {
            var json = this.getExportJson();
            var blob = new Blob([json], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = this.documentTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '-vbp-export.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            this.showNotification('Archivo JSON descargado', 'success');
        },

        copyJsonToClipboard: function() {
            var self = this;
            var json = this.getExportJson();

            navigator.clipboard.writeText(json).then(function() {
                self.showNotification('JSON copiado al portapapeles', 'success');
            }).catch(function() {
                // Fallback para navegadores antiguos
                var textarea = document.createElement('textarea');
                textarea.value = json;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                self.showNotification('JSON copiado al portapapeles', 'success');
            });
        },

        exportAsHtml: function() {
            var self = this;

            fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/export-html', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.html) {
                    var blob = new Blob([result.html], { type: 'text/html' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = self.documentTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.html';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    self.showNotification('HTML exportado correctamente', 'success');
                }
            })
            .catch(function(error) {
                self.showNotification('Error exportando HTML', 'error');
            });
        },

        // ============ REVISIONES ============

        openRevisionsModal: function() {
            this.showRevisionsModal = true;
            this.loadRevisions();
        },

        loadRevisions: function() {
            var self = this;
            this.isLoadingRevisions = true;
            this.revisions = [];

            fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (Array.isArray(data)) {
                    self.revisions = data.map(function(rev, index) {
                        return {
                            id: rev.id,
                            date: rev.date,
                            author: rev.author || 'Usuario',
                            title: rev.title || '',
                            isCurrent: index === 0
                        };
                    });
                }
            })
            .catch(function(error) {
                console.error('Error cargando revisiones:', error);
                self.showNotification('Error cargando revisiones', 'error');
            })
            .finally(function() {
                self.isLoadingRevisions = false;
            });
        },

        formatRevisionDate: function(dateString) {
            if (!dateString) return '';

            var date = new Date(dateString);
            var now = new Date();
            var diffMs = now - date;
            var diffMins = Math.floor(diffMs / 60000);
            var diffHours = Math.floor(diffMs / 3600000);
            var diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) {
                return 'Ahora mismo';
            } else if (diffMins < 60) {
                return 'Hace ' + diffMins + ' minuto' + (diffMins === 1 ? '' : 's');
            } else if (diffHours < 24) {
                return 'Hace ' + diffHours + ' hora' + (diffHours === 1 ? '' : 's');
            } else if (diffDays < 7) {
                return 'Hace ' + diffDays + ' día' + (diffDays === 1 ? '' : 's');
            } else {
                return date.toLocaleDateString('es-ES', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        },

        restoreRevision: function(revision) {
            if (!confirm(VBP_Config.strings.confirmRestoreRevision || '¿Restaurar esta versión? Se perderán los cambios no guardados.')) {
                return;
            }

            var self = this;
            this.isRestoringRevision = true;

            fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions/' + revision.id + '/restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    self.showNotification('Revisión restaurada correctamente', 'success');
                    self.showRevisionsModal = false;
                    // Recargar el documento
                    self.loadDocument();
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }
            })
            .catch(function(error) {
                self.showNotification('Error restaurando revisión: ' + error.message, 'error');
            })
            .finally(function() {
                self.isRestoringRevision = false;
            });
        },

        // ===== WIDGETS GLOBALES =====

        loadGlobalWidgets: function() {
            var self = this;
            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/') + 'global-widgets', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Respuesta no es JSON válido');
                }
                return response.json();
            })
            .then(function(widgets) {
                self.globalWidgets = widgets || [];
                self.globalWidgetsLoaded = true;
            })
            .catch(function(error) {
                console.warn('Error cargando widgets globales:', error.message);
                self.globalWidgets = [];
                self.globalWidgetsLoaded = true;
            });
        },

        saveAsGlobalWidget: function() {
            var store = Alpine.store('vbp');
            if (!store.selectedElementId) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }
            var element = store.elements.find(function(el) { return el.id === store.selectedElementId; });
            if (!element) {
                this.showNotification('Elemento no encontrado', 'error');
                return;
            }
            this.newGlobalWidgetName = element.name || element.type || 'Widget';
            this.newGlobalWidgetCategory = 'general';
            this.showSaveGlobalWidgetModal = true;
        },

        confirmSaveGlobalWidget: function() {
            if (!this.newGlobalWidgetName.trim()) {
                this.showNotification('Ingresa un nombre para el widget', 'warning');
                return;
            }

            var store = Alpine.store('vbp');
            var element = store.elements.find(function(el) { return el.id === store.selectedElementId; });
            if (!element) {
                this.showNotification('Elemento no encontrado', 'error');
                return;
            }

            var self = this;
            this.isSavingGlobalWidget = true;

            // Clonar el elemento sin el ID original
            var elementClone = JSON.parse(JSON.stringify(element));
            delete elementClone.id;

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/') + 'global-widgets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    title: this.newGlobalWidgetName,
                    element: elementClone,
                    category: this.newGlobalWidgetCategory
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.id) {
                    self.showNotification('Widget global guardado correctamente', 'success');
                    self.showSaveGlobalWidgetModal = false;
                    self.loadGlobalWidgets();
                } else {
                    throw new Error(result.error || 'Error desconocido');
                }
            })
            .catch(function(error) {
                self.showNotification('Error guardando widget: ' + error.message, 'error');
            })
            .finally(function() {
                self.isSavingGlobalWidget = false;
            });
        },

        insertGlobalWidget: function(widget) {
            var self = this;

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/') + 'global-widgets/' + widget.id, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.element) {
                    var store = Alpine.store('vbp');
                    var newElement = JSON.parse(JSON.stringify(data.element));
                    newElement.id = 'el_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    newElement.data = newElement.data || {};
                    newElement.data.globalWidgetId = widget.id;
                    newElement.name = widget.title;
                    store.elements.push(newElement);
                    store.selectedElementId = newElement.id;
                    store.saveHistory();
                    self.showNotification('Widget insertado', 'success');
                } else {
                    throw new Error('Widget no encontrado');
                }
            })
            .catch(function(error) {
                self.showNotification('Error insertando widget: ' + error.message, 'error');
            });
        },

        deleteGlobalWidget: function(widget) {
            if (!confirm('¿Eliminar este widget global? Esta acción no se puede deshacer.')) {
                return;
            }

            var self = this;

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/') + 'global-widgets/' + widget.id, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.message) {
                    self.globalWidgets = self.globalWidgets.filter(function(w) { return w.id !== widget.id; });
                    self.showNotification('Widget eliminado', 'success');
                } else if (result.error) {
                    throw new Error(result.error);
                }
            })
            .catch(function(error) {
                self.showNotification('Error eliminando widget: ' + error.message, 'error');
            });
        },

        get filteredGlobalWidgets() {
            var self = this;
            var search = (this.blockSearch || '').toLowerCase();
            if (!search) return this.globalWidgets;
            return this.globalWidgets.filter(function(widget) {
                return widget.title.toLowerCase().includes(search) ||
                       widget.type.toLowerCase().includes(search);
            });
        },

        // ===== UNSPLASH =====

        openUnsplash: function(targetElement) {
            this.unsplashTargetElement = targetElement || null;
            this.showUnsplashModal = true;
            this.checkUnsplashStatus();
        },

        checkUnsplashStatus: function() {
            var self = this;
            fetch(VBP_Config.restUrl + 'unsplash/status', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.unsplashConfigured = data.configured || false;
            })
            .catch(function() {
                self.unsplashConfigured = false;
            });
        },

        searchUnsplash: function() {
            if (!this.unsplashQuery.trim()) return;

            var self = this;
            this.isSearchingUnsplash = true;
            this.unsplashPage = 1;

            var url = VBP_Config.restUrl + 'unsplash/search?' + new URLSearchParams({
                query: this.unsplashQuery,
                page: this.unsplashPage,
                per_page: 20,
                orientation: this.unsplashOrientation
            });

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    throw new Error(data.error);
                }
                self.unsplashImages = data.results || [];
                self.unsplashTotalPages = data.totalPages || 0;
            })
            .catch(function(error) {
                self.showNotification('Error buscando imágenes: ' + error.message, 'error');
                self.unsplashImages = [];
            })
            .finally(function() {
                self.isSearchingUnsplash = false;
            });
        },

        unsplashNextPage: function() {
            if (this.unsplashPage >= this.unsplashTotalPages) return;
            this.unsplashPage++;
            this.loadUnsplashPage();
        },

        unsplashPrevPage: function() {
            if (this.unsplashPage <= 1) return;
            this.unsplashPage--;
            this.loadUnsplashPage();
        },

        loadUnsplashPage: function() {
            var self = this;
            this.isSearchingUnsplash = true;

            var url = VBP_Config.restUrl + 'unsplash/search?' + new URLSearchParams({
                query: this.unsplashQuery,
                page: this.unsplashPage,
                per_page: 20,
                orientation: this.unsplashOrientation
            });

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.unsplashImages = data.results || [];
            })
            .catch(function(error) {
                self.showNotification('Error cargando página', 'error');
            })
            .finally(function() {
                self.isSearchingUnsplash = false;
            });
        },

        selectUnsplashImage: function(image) {
            var self = this;

            // Registrar la descarga (requerido por Unsplash API)
            fetch(VBP_Config.restUrl + 'unsplash/photos/' + image.id + '/download', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            }).catch(function() {
                // Ignorar errores de tracking
            });

            // Si hay un elemento objetivo, actualizar su imagen
            if (this.unsplashTargetElement) {
                var store = Alpine.store('vbp');
                var element = store.getElement(this.unsplashTargetElement);
                if (element) {
                    var data = JSON.parse(JSON.stringify(element.data || {}));
                    data.src = image.urls.regular;
                    data.alt = image.description || 'Imagen de ' + image.user.name + ' en Unsplash';
                    data.unsplashId = image.id;
                    data.unsplashAuthor = image.user.name;
                    data.unsplashAuthorUrl = image.user.link;
                    store.updateElement(this.unsplashTargetElement, { data: data });
                    this.showNotification('Imagen actualizada', 'success');
                }
            } else {
                // Crear nuevo elemento de imagen
                var store = Alpine.store('vbp');
                store.addElement('image', {
                    src: image.urls.regular,
                    alt: image.description || 'Imagen de ' + image.user.name + ' en Unsplash',
                    unsplashId: image.id,
                    unsplashAuthor: image.user.name,
                    unsplashAuthorUrl: image.user.link
                });
                this.showNotification('Imagen insertada', 'success');
            }

            this.showUnsplashModal = false;
            this.unsplashTargetElement = null;
        },

        // === HISTORIAL DE VERSIONES ===
        openVersionHistory: function() {
            this.showVersionHistoryModal = true;
            this.loadVersions();
        },

        loadVersions: function() {
            var self = this;
            this.isLoadingVersions = true;

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.versions = data.versiones;
                } else {
                    self.showNotification('Error cargando versiones', 'error');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            })
            .finally(function() {
                self.isLoadingVersions = false;
            });
        },

        createVersionSnapshot: function() {
            var self = this;
            var label = this.newVersionLabel.trim() || '';

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ label: label })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.showNotification('Versión guardada correctamente', 'success');
                    self.newVersionLabel = '';
                    self.loadVersions();
                } else {
                    throw new Error(data.message || 'Error al crear versión');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        restoreVersion: function(version) {
            var self = this;
            if (!confirm('¿Restaurar a la versión #' + version.version_number + '? Se guardará una copia del estado actual.')) {
                return;
            }

            this.isRestoringVersion = true;

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id + '/restore', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.showNotification('Versión restaurada correctamente', 'success');
                    // Recargar el contenido en el store
                    var store = Alpine.store('vbp');
                    if (store && data.content) {
                        store.elements = sanitizeElements(data.content);
                    }
                    self.showVersionHistoryModal = false;
                    self.loadVersions();
                } else {
                    throw new Error(data.message || 'Error al restaurar versión');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            })
            .finally(function() {
                self.isRestoringVersion = false;
            });
        },

        selectVersionForCompare: function(version, slot) {
            if (slot === 'A') {
                this.selectedVersionA = version;
            } else {
                this.selectedVersionB = version;
            }
        },

        compareVersions: function() {
            var self = this;
            if (!this.selectedVersionA || !this.selectedVersionB) {
                this.showNotification('Selecciona dos versiones para comparar', 'warning');
                return;
            }

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/compare?version_a=' + this.selectedVersionA.id + '&version_b=' + this.selectedVersionB.id, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.versionDiff = data;
                    self.showVersionDiffModal = true;
                } else {
                    throw new Error(data.message || 'Error al comparar versiones');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        updateVersionLabel: function(version, newLabel) {
            var self = this;

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id + '/label', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ label: newLabel })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.showNotification('Etiqueta actualizada', 'success');
                    version.label = newLabel;
                } else {
                    throw new Error(data.message || 'Error al actualizar etiqueta');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        deleteVersion: function(version) {
            var self = this;
            if (!confirm('¿Eliminar la versión #' + version.version_number + '? Esta acción no se puede deshacer.')) {
                return;
            }

            fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.showNotification('Versión eliminada', 'success');
                    self.loadVersions();
                } else {
                    throw new Error(data.message || 'Error al eliminar versión');
                }
            })
            .catch(function(error) {
                self.showNotification('Error: ' + error.message, 'error');
            });
        },

        getDiffChangeTypeClass: function(type) {
            var classes = {
                added: 'vbp-diff-added',
                removed: 'vbp-diff-removed',
                modified: 'vbp-diff-modified'
            };
            return classes[type] || '';
        },

        getDiffChangeTypeLabel: function(type) {
            var labels = {
                added: 'Añadido',
                removed: 'Eliminado',
                modified: 'Modificado'
            };
            return labels[type] || type;
        }
    };
}

/**
 * VBP Module Preview - Carga previsualizaciones en tiempo real de módulos
 */
window.vbpModulePreview = {
    cache: {},
    loading: {},
    retryCount: {},
    maxRetries: 2,

    /**
     * Carga la previsualización de un módulo
     */
    loadPreview: function(elementId, shortcode) {
        var self = this;

        // Evitar cargas duplicadas
        if (this.loading[elementId]) {
            return;
        }

        // Usar caché si existe
        if (this.cache[shortcode]) {
            this.applyPreview(elementId, this.cache[shortcode]);
            return;
        }

        this.loading[elementId] = true;

        // Verificar que VBP_Config existe
        if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
            console.warn('VBP_Config no disponible para cargar previsualización');
            this.showError(elementId, shortcode, 'Config no disponible');
            return;
        }

        fetch(VBP_Config.restUrl + 'preview-shortcode', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({
                shortcode: shortcode,
                attributes: {}
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.html) {
                // Guardar en caché
                self.cache[shortcode] = data.html;
                self.applyPreview(elementId, data.html);
            } else {
                throw new Error('Respuesta inválida');
            }
        })
        .catch(function(error) {
            console.warn('Error cargando previsualización:', error);

            // Reintentar si no se ha alcanzado el máximo
            self.retryCount[elementId] = (self.retryCount[elementId] || 0) + 1;
            if (self.retryCount[elementId] < self.maxRetries) {
                setTimeout(function() {
                    self.loading[elementId] = false;
                    self.loadPreview(elementId, shortcode);
                }, 1000);
            } else {
                self.showError(elementId, shortcode, error.message);
            }
        })
        .finally(function() {
            self.loading[elementId] = false;
        });
    },

    /**
     * Aplica la previsualización al elemento
     */
    applyPreview: function(elementId, html) {
        var container = document.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
        if (!container) {
            // Intentar encontrar en el iframe del canvas
            var iframe = document.querySelector('.vbp-canvas-iframe');
            if (iframe && iframe.contentDocument) {
                container = iframe.contentDocument.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
            }
        }

        if (container) {
            var contentDiv = container.querySelector('.vbp-module-preview-content');
            if (contentDiv) {
                contentDiv.innerHTML = html;
                contentDiv.classList.add('vbp-preview-loaded');
            }
        }
    },

    /**
     * Muestra un error de previsualización
     */
    showError: function(elementId, shortcode, message) {
        var container = document.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
        if (!container) {
            var iframe = document.querySelector('.vbp-canvas-iframe');
            if (iframe && iframe.contentDocument) {
                container = iframe.contentDocument.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
            }
        }

        if (container) {
            var contentDiv = container.querySelector('.vbp-module-preview-content');
            if (contentDiv) {
                contentDiv.innerHTML = '<div class="vbp-module-preview-error" style="padding: 24px; text-align: center; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">' +
                    '<div style="font-size: 24px; margin-bottom: 8px;">⚠️</div>' +
                    '<div style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">' + shortcode + '</div>' +
                    '<div style="font-size: 13px; color: #b91c1c;">No se pudo cargar la previsualización</div>' +
                    '<button onclick="window.vbpModulePreview.retry(\'' + elementId + '\', \'' + shortcode + '\')" ' +
                    'style="margin-top: 12px; padding: 6px 16px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">' +
                    'Reintentar</button>' +
                    '</div>';
            }
        }
    },

    /**
     * Reintenta cargar una previsualización
     */
    retry: function(elementId, shortcode) {
        this.retryCount[elementId] = 0;
        delete this.cache[shortcode];

        // Mostrar loader de nuevo
        var container = document.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
        if (container) {
            var contentDiv = container.querySelector('.vbp-module-preview-content');
            if (contentDiv) {
                contentDiv.innerHTML = '<div class="vbp-module-preview-loading" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px; background: #f5f5f5; border-radius: 8px;">' +
                    '<div class="vbp-preview-spinner" style="width: 32px; height: 32px; border: 3px solid #e5e5e5; border-top-color: #6366f1; border-radius: 50%; animation: vbp-spin 0.8s linear infinite;"></div>' +
                    '<div style="margin-top: 12px; color: #6b7280;">Reintentando...</div>' +
                    '</div>';
            }
        }

        this.loadPreview(elementId, shortcode);
    },

    /**
     * Limpia la caché
     */
    clearCache: function() {
        this.cache = {};
    },

    /**
     * Recarga todas las previsualizaciones visibles
     */
    reloadAll: function() {
        var self = this;
        this.clearCache();

        var containers = document.querySelectorAll('.vbp-module-preview-container');
        containers.forEach(function(container) {
            var elementId = container.dataset.elementId;
            var shortcode = container.dataset.shortcode;
            if (elementId && shortcode) {
                self.loadPreview(elementId, shortcode);
            }
        });
    }
};
