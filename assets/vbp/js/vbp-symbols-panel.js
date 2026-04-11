/**
 * Visual Builder Pro - Symbols Panel Component
 *
 * Componente Alpine.js para el panel de gestión de símbolos.
 * Integra con VBPSymbols para operaciones CRUD y sincronización.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * Componente Alpine.js para el panel de símbolos
     *
     * @returns {Object} Definición del componente Alpine
     */
    window.vbpSymbolsPanel = function() {
        return {
            // Estado del panel
            symbols: [],
            filteredSymbols: [],
            searchQuery: '',
            selectedCategory: '',
            categories: [],
            loading: true,
            selectedSymbolId: null,
            canCreateFromSelection: false,

            // Menú contextual
            contextMenu: {
                visible: false,
                symbolId: null,
                positionX: 0,
                positionY: 0
            },

            // Cache de uso de símbolos
            usageCache: {},

            /**
             * Inicializa el componente
             */
            init: function() {
                var self = this;

                // Cargar símbolos iniciales
                this.loadSymbols();

                // Cargar categorías
                this.loadCategories();

                // Escuchar eventos de VBPSymbols
                this.setupEventListeners();

                // Verificar selección inicial
                this.checkSelectionState();

                // Actualizar estado de selección periódicamente
                this.$watch('$store.vbp.selection.elementIds', function() {
                    self.checkSelectionState();
                });
            },

            /**
             * Configura los event listeners
             */
            setupEventListeners: function() {
                var self = this;

                // Evento de símbolo creado
                document.addEventListener('vbp:symbol:created', function(event) {
                    self.loadSymbols();
                    var message = typeof window._s === 'function'
                        ? _s('symbolCreated', event.detail.name)
                        : 'Símbolo creado: ' + event.detail.name;
                    self.showNotification(message, 'success');
                });

                // Evento de símbolo actualizado
                document.addEventListener('vbp:symbol:updated', function(event) {
                    self.loadSymbols();
                });

                // Evento de símbolo eliminado
                document.addEventListener('vbp:symbol:deleted', function() {
                    self.loadSymbols();
                });

                // Evento de instancia creada
                document.addEventListener('vbp:symbol:instance:created', function() {
                    self.updateUsageCache();
                });

                // Evento de instancia eliminada
                document.addEventListener('vbp:symbol:instance:deleted', function() {
                    self.updateUsageCache();
                });

                // Atajo de teclado Ctrl+Shift+K para crear símbolo
                document.addEventListener('keydown', function(event) {
                    if (event.ctrlKey && event.shiftKey && event.key === 'K') {
                        event.preventDefault();
                        self.createFromSelection();
                    }
                });
            },

            /**
             * Carga la lista de símbolos desde la API
             */
            loadSymbols: function() {
                var self = this;
                self.loading = true;

                // Verificar si VBPSymbols está disponible
                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.getAll) {
                    window.VBPSymbols.getAll()
                        .then(function(symbolsList) {
                            self.symbols = symbolsList || [];
                            self.filterSymbols();
                            self.updateUsageCache();
                            self.loading = false;
                        })
                        .catch(function(error) {
                            console.error('[VBP Symbols Panel] Error loading symbols:', error);
                            self.symbols = [];
                            self.filteredSymbols = [];
                            self.loading = false;
                            self.showNotification(__('symbolLoadError', 'Error al cargar símbolos'), 'error');
                        });
                } else {
                    // Fallback: cargar directamente desde REST API
                    this.loadSymbolsFromAPI();
                }
            },

            /**
             * Carga símbolos directamente desde la REST API
             */
            loadSymbolsFromAPI: function() {
                var self = this;

                if (typeof VBP_Config === 'undefined') {
                    self.loading = false;
                    return;
                }

                var apiUrl = VBP_Config.restUrl + 'symbols';

                fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': VBP_Config.restNonce
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success && data.symbols) {
                        self.symbols = data.symbols;
                    } else if (Array.isArray(data)) {
                        self.symbols = data;
                    } else {
                        self.symbols = [];
                    }
                    self.filterSymbols();
                    self.updateUsageCache();
                    self.loading = false;
                })
                .catch(function(error) {
                    console.error('[VBP Symbols Panel] API Error:', error);
                    self.symbols = [];
                    self.filteredSymbols = [];
                    self.loading = false;
                });
            },

            /**
             * Carga las categorías de símbolos
             */
            loadCategories: function() {
                var self = this;

                // Categorías por defecto con soporte i18n
                self.categories = [
                    { id: 'layout', name: __('categoryLayout', 'Layout') },
                    { id: 'content', name: __('categoryContent', 'Contenido') },
                    { id: 'navigation', name: __('categoryNavigation', 'Navegación') },
                    { id: 'forms', name: __('categoryForms', 'Formularios') },
                    { id: 'media', name: __('categoryMedia', 'Media') },
                    { id: 'custom', name: __('categoryCustom', 'Personalizado') }
                ];

                // Intentar cargar desde API si está disponible
                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.getCategories) {
                    window.VBPSymbols.getCategories()
                        .then(function(categoriesList) {
                            if (categoriesList && categoriesList.length > 0) {
                                self.categories = categoriesList;
                            }
                        })
                        .catch(function() {
                            // Mantener categorías por defecto
                        });
                }
            },

            /**
             * Filtra símbolos por búsqueda y categoría
             */
            filterSymbols: function() {
                var self = this;
                var searchQueryLower = this.searchQuery.toLowerCase().trim();
                var selectedCategoryFilter = this.selectedCategory;

                this.filteredSymbols = this.symbols.filter(function(symbol) {
                    // Filtro por categoría
                    if (selectedCategoryFilter && symbol.category !== selectedCategoryFilter) {
                        return false;
                    }

                    // Filtro por búsqueda
                    if (searchQueryLower) {
                        var symbolNameLower = (symbol.name || '').toLowerCase();
                        var symbolDescriptionLower = (symbol.description || '').toLowerCase();
                        var symbolTagsLower = (symbol.tags || []).join(' ').toLowerCase();

                        return symbolNameLower.indexOf(searchQueryLower) !== -1 ||
                               symbolDescriptionLower.indexOf(searchQueryLower) !== -1 ||
                               symbolTagsLower.indexOf(searchQueryLower) !== -1;
                    }

                    return true;
                });
            },

            /**
             * Selecciona una categoría
             *
             * @param {string} categoryId - ID de la categoría
             */
            selectCategory: function(categoryId) {
                this.selectedCategory = categoryId;
                this.filterSymbols();
            },

            /**
             * Limpia la búsqueda
             */
            clearSearch: function() {
                this.searchQuery = '';
                this.filterSymbols();
            },

            /**
             * Limpia todos los filtros
             */
            clearFilters: function() {
                this.searchQuery = '';
                this.selectedCategory = '';
                this.filterSymbols();
            },

            /**
             * Selecciona un símbolo
             *
             * @param {Object} symbol - Símbolo a seleccionar
             */
            selectSymbol: function(symbol) {
                this.selectedSymbolId = symbol.id;
            },

            /**
             * Inserta una instancia del símbolo en el canvas
             *
             * @param {string} symbolId - ID del símbolo
             */
            insertSymbol: function(symbolId) {
                var self = this;

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.createInstance) {
                    window.VBPSymbols.createInstance(symbolId)
                        .then(function(instance) {
                            self.showNotification('Instancia insertada', 'success');
                            self.updateUsageCache();
                        })
                        .catch(function(error) {
                            console.error('[VBP Symbols Panel] Error inserting instance:', error);
                            self.showNotification('Error al insertar instancia', 'error');
                        });
                } else {
                    // Fallback: insertar directamente en el store
                    this.insertSymbolFallback(symbolId);
                }
            },

            /**
             * Fallback para insertar símbolo sin VBPSymbols
             *
             * @param {string} symbolId - ID del símbolo
             */
            insertSymbolFallback: function(symbolId) {
                var self = this;
                var symbolData = this.symbols.find(function(symbol) {
                    return symbol.id === symbolId;
                });

                if (!symbolData || !symbolData.blocks) {
                    this.showNotification('Símbolo no encontrado', 'error');
                    return;
                }

                var vbpStore = Alpine.store('vbp');
                if (!vbpStore) {
                    this.showNotification('Store no disponible', 'error');
                    return;
                }

                // Crear instancia con referencia al símbolo
                var instanceId = this.generateInstanceId();
                var instanceElement = {
                    id: instanceId,
                    type: 'symbol-instance',
                    symbolId: symbolId,
                    name: symbolData.name + ' (Instancia)',
                    overrides: {},
                    children: JSON.parse(JSON.stringify(symbolData.blocks))
                };

                // Regenerar IDs de hijos
                this.regenerateChildIds(instanceElement.children);

                // Insertar en el store
                vbpStore.elements.push(instanceElement);
                vbpStore.markAsDirty();
                vbpStore.setSelection([instanceId]);

                this.showNotification('Instancia insertada', 'success');
                this.updateUsageCache();
            },

            /**
             * Abre el editor del símbolo maestro
             *
             * @param {string} symbolId - ID del símbolo
             */
            editSymbol: function(symbolId) {
                var self = this;
                this.closeContextMenu();

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.openEditor) {
                    window.VBPSymbols.openEditor(symbolId);
                } else {
                    // Fallback: abrir modal de edición
                    this.openSymbolEditorModal(symbolId);
                }
            },

            /**
             * Abre modal de edición de símbolo (fallback)
             *
             * @param {string} symbolId - ID del símbolo
             */
            openSymbolEditorModal: function(symbolId) {
                var symbolData = this.symbols.find(function(symbol) {
                    return symbol.id === symbolId;
                });

                if (!symbolData) {
                    this.showNotification('Símbolo no encontrado', 'error');
                    return;
                }

                // Disparar evento para abrir editor
                var eventDetail = {
                    symbolId: symbolId,
                    symbol: symbolData
                };
                document.dispatchEvent(new CustomEvent('vbp:symbol:edit-request', { detail: eventDetail }));
            },

            /**
             * Crea un símbolo desde la selección actual
             */
            createFromSelection: function() {
                var self = this;

                if (!this.canCreateFromSelection) {
                    this.showNotification('Selecciona elementos primero', 'warning');
                    return;
                }

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.createFromSelection) {
                    window.VBPSymbols.createFromSelection()
                        .then(function(symbol) {
                            self.loadSymbols();
                        })
                        .catch(function(error) {
                            console.error('[VBP Symbols Panel] Error creating symbol:', error);
                            self.showNotification('Error al crear símbolo', 'error');
                        });
                } else {
                    // Fallback: mostrar modal de creación
                    this.showCreateSymbolModal();
                }
            },

            /**
             * Muestra el modal de creación de símbolo
             */
            showCreateSymbolModal: function() {
                var vbpStore = Alpine.store('vbp');
                if (!vbpStore || !vbpStore.selection || vbpStore.selection.elementIds.length === 0) {
                    this.showNotification('Selecciona elementos primero', 'warning');
                    return;
                }

                // Obtener elementos seleccionados
                var selectedElements = [];
                vbpStore.selection.elementIds.forEach(function(elementId) {
                    var element = vbpStore.getElementDeep(elementId);
                    if (element) {
                        selectedElements.push(JSON.parse(JSON.stringify(element)));
                    }
                });

                if (selectedElements.length === 0) {
                    this.showNotification('No se encontraron elementos válidos', 'error');
                    return;
                }

                // Disparar evento para mostrar modal
                var eventDetail = {
                    elements: selectedElements
                };
                document.dispatchEvent(new CustomEvent('vbp:symbol:create-request', { detail: eventDetail }));
            },

            /**
             * Maneja el inicio de drag
             *
             * @param {DragEvent} event - Evento de drag
             * @param {string} symbolId - ID del símbolo
             */
            handleDragStart: function(event, symbolId) {
                var symbolData = this.symbols.find(function(symbol) {
                    return symbol.id === symbolId;
                });

                if (!symbolData) {
                    event.preventDefault();
                    return;
                }

                // Configurar datos de transferencia
                var transferData = {
                    type: 'vbp-symbol',
                    symbolId: symbolId,
                    symbolName: symbolData.name
                };

                event.dataTransfer.setData('application/json', JSON.stringify(transferData));
                event.dataTransfer.setData('text/plain', symbolData.name);
                event.dataTransfer.effectAllowed = 'copy';

                // Añadir clase visual
                event.target.classList.add('dragging');
            },

            /**
             * Maneja el fin de drag
             *
             * @param {DragEvent} event - Evento de drag
             */
            handleDragEnd: function(event) {
                event.target.classList.remove('dragging');
            },

            /**
             * Obtiene el conteo de instancias de un símbolo
             *
             * @param {string} symbolId - ID del símbolo
             * @returns {number} Número de instancias
             */
            getSymbolUsageCount: function(symbolId) {
                return this.usageCache[symbolId] || 0;
            },

            /**
             * Actualiza la cache de uso de símbolos
             */
            updateUsageCache: function() {
                var self = this;
                var vbpStore = Alpine.store('vbp');

                if (!vbpStore || !vbpStore.elements) {
                    return;
                }

                // Reiniciar cache
                this.usageCache = {};

                // Contar instancias
                var countInstances = function(elements) {
                    elements.forEach(function(element) {
                        if (element.type === 'symbol-instance' && element.symbolId) {
                            self.usageCache[element.symbolId] = (self.usageCache[element.symbolId] || 0) + 1;
                        }
                        if (element.children && element.children.length > 0) {
                            countInstances(element.children);
                        }
                    });
                };

                countInstances(vbpStore.elements);
            },

            /**
             * Muestra el menú contextual del símbolo
             *
             * @param {Object} symbol - Símbolo
             * @param {Event} event - Evento del click
             */
            showSymbolMenu: function(symbol, event) {
                event.preventDefault();
                event.stopPropagation();

                var panelRect = this.$el.getBoundingClientRect();
                var buttonRect = event.target.closest('button').getBoundingClientRect();

                this.contextMenu.visible = true;
                this.contextMenu.symbolId = symbol.id;
                this.contextMenu.positionX = buttonRect.left - panelRect.left;
                this.contextMenu.positionY = buttonRect.bottom - panelRect.top + 4;
            },

            /**
             * Cierra el menú contextual
             */
            closeContextMenu: function() {
                this.contextMenu.visible = false;
                this.contextMenu.symbolId = null;
            },

            /**
             * Obtiene el estilo para el menú contextual
             */
            get contextMenuStyle() {
                return 'left: ' + this.contextMenu.positionX + 'px; top: ' + this.contextMenu.positionY + 'px;';
            },

            /**
             * Duplica un símbolo
             *
             * @param {string} symbolId - ID del símbolo
             */
            duplicateSymbol: function(symbolId) {
                var self = this;
                this.closeContextMenu();

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.duplicate) {
                    window.VBPSymbols.duplicate(symbolId)
                        .then(function() {
                            self.loadSymbols();
                            self.showNotification('Símbolo duplicado', 'success');
                        })
                        .catch(function(error) {
                            self.showNotification('Error al duplicar símbolo', 'error');
                        });
                } else {
                    this.showNotification('Función no disponible', 'warning');
                }
            },

            /**
             * Renombra un símbolo
             *
             * @param {string} symbolId - ID del símbolo
             */
            renameSymbol: function(symbolId) {
                var self = this;
                var symbolData = this.symbols.find(function(symbol) {
                    return symbol.id === symbolId;
                });

                this.closeContextMenu();

                if (!symbolData) {
                    return;
                }

                var newName = prompt('Nuevo nombre para el símbolo:', symbolData.name);
                if (newName && newName.trim() && newName !== symbolData.name) {
                    if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.rename) {
                        window.VBPSymbols.rename(symbolId, newName.trim())
                            .then(function() {
                                self.loadSymbols();
                                self.showNotification('Símbolo renombrado', 'success');
                            })
                            .catch(function(error) {
                                self.showNotification('Error al renombrar símbolo', 'error');
                            });
                    } else {
                        this.showNotification('Función no disponible', 'warning');
                    }
                }
            },

            /**
             * Desvincula todas las instancias de un símbolo
             *
             * @param {string} symbolId - ID del símbolo
             */
            unlinkAllInstances: function(symbolId) {
                var self = this;
                this.closeContextMenu();

                var instanceCount = this.getSymbolUsageCount(symbolId);
                if (instanceCount === 0) {
                    this.showNotification('No hay instancias para desvincular', 'info');
                    return;
                }

                var confirmMessage = '¿Desvincular ' + instanceCount + ' instancia(s) de este símbolo? ' +
                                    'Las instancias se convertirán en elementos normales.';

                if (!confirm(confirmMessage)) {
                    return;
                }

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.unlinkAllInstances) {
                    window.VBPSymbols.unlinkAllInstances(symbolId)
                        .then(function() {
                            self.updateUsageCache();
                            self.showNotification('Instancias desvinculadas', 'success');
                        })
                        .catch(function(error) {
                            self.showNotification('Error al desvincular instancias', 'error');
                        });
                } else {
                    this.unlinkInstancesFallback(symbolId);
                }
            },

            /**
             * Fallback para desvincular instancias
             *
             * @param {string} symbolId - ID del símbolo
             */
            unlinkInstancesFallback: function(symbolId) {
                var self = this;
                var vbpStore = Alpine.store('vbp');

                if (!vbpStore) {
                    return;
                }

                var unlinkRecursive = function(elements) {
                    elements.forEach(function(element) {
                        if (element.type === 'symbol-instance' && element.symbolId === symbolId) {
                            element.type = 'container';
                            delete element.symbolId;
                            delete element.overrides;
                        }
                        if (element.children && element.children.length > 0) {
                            unlinkRecursive(element.children);
                        }
                    });
                };

                unlinkRecursive(vbpStore.elements);
                vbpStore.markAsDirty();
                this.updateUsageCache();
                this.showNotification('Instancias desvinculadas', 'success');
            },

            /**
             * Elimina un símbolo
             *
             * @param {string} symbolId - ID del símbolo
             */
            deleteSymbol: function(symbolId) {
                var self = this;
                this.closeContextMenu();

                var instanceCount = this.getSymbolUsageCount(symbolId);
                var confirmMessage = '¿Eliminar este símbolo?';

                if (instanceCount > 0) {
                    confirmMessage += ' Hay ' + instanceCount + ' instancia(s) que serán desvinculadas.';
                }

                confirmMessage += ' Esta acción no se puede deshacer.';

                if (!confirm(confirmMessage)) {
                    return;
                }

                if (typeof window.VBPSymbols !== 'undefined' && window.VBPSymbols.delete) {
                    window.VBPSymbols.delete(symbolId)
                        .then(function() {
                            self.loadSymbols();
                            self.showNotification('Símbolo eliminado', 'success');
                        })
                        .catch(function(error) {
                            self.showNotification('Error al eliminar símbolo', 'error');
                        });
                } else {
                    this.showNotification('Función no disponible', 'warning');
                }
            },

            /**
             * Verifica el estado de selección para habilitar/deshabilitar creación
             */
            checkSelectionState: function() {
                var vbpStore = Alpine.store('vbp');
                this.canCreateFromSelection = vbpStore &&
                                             vbpStore.selection &&
                                             vbpStore.selection.elementIds &&
                                             vbpStore.selection.elementIds.length > 0;
            },

            /**
             * Obtiene el nombre de una categoría
             *
             * @param {string} categoryId - ID de la categoría
             * @returns {string} Nombre de la categoría
             */
            getCategoryName: function(categoryId) {
                var category = this.categories.find(function(categoryItem) {
                    return categoryItem.id === categoryId;
                });
                return category ? category.name : categoryId || 'Sin categoría';
            },

            /**
             * Obtiene el estilo del thumbnail
             *
             * @param {Object} symbol - Símbolo
             * @returns {string} Estilo CSS
             */
            getThumbnailStyle: function(symbol) {
                if (symbol.thumbnail) {
                    return 'background-image: url(' + symbol.thumbnail + ');';
                }
                return '';
            },

            /**
             * Genera un ID único para instancia
             *
             * @returns {string} ID único
             */
            generateInstanceId: function() {
                return 'symbol_inst_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now().toString(36);
            },

            /**
             * Regenera IDs de elementos hijos
             *
             * @param {Array} children - Array de elementos hijos
             */
            regenerateChildIds: function(children) {
                var self = this;
                children.forEach(function(child) {
                    child.id = 'el_' + Math.random().toString(36).substr(2, 9);
                    if (child.children && child.children.length > 0) {
                        self.regenerateChildIds(child.children);
                    }
                });
            },

            // =============================================
            // IMPORT/EXPORT
            // =============================================

            /**
             * Exportar todos los simbolos
             */
            exportAllSymbols: function() {
                var self = this;

                if (self.symbols.length === 0) {
                    self.showNotification('No hay simbolos para exportar', 'warning');
                    return;
                }

                if (window.VBPSymbols && typeof VBPSymbols.downloadExport === 'function') {
                    VBPSymbols.downloadExport([]);
                    self.showNotification('Exportando ' + self.symbols.length + ' simbolos...', 'info');
                } else {
                    self.showNotification('Funcion de exportacion no disponible', 'error');
                }
            },

            /**
             * Exportar simbolos seleccionados
             * @param {Array<string>} symbolIds - IDs de simbolos a exportar
             */
            exportSelectedSymbols: function(symbolIds) {
                var self = this;

                if (!symbolIds || symbolIds.length === 0) {
                    self.showNotification('Selecciona simbolos para exportar', 'warning');
                    return;
                }

                if (window.VBPSymbols && typeof VBPSymbols.downloadExport === 'function') {
                    VBPSymbols.downloadExport(symbolIds);
                    self.showNotification('Exportando ' + symbolIds.length + ' simbolo(s)...', 'info');
                } else {
                    self.showNotification('Funcion de exportacion no disponible', 'error');
                }
            },

            /**
             * Exportar un simbolo especifico desde menu contextual
             * @param {string} symbolId - ID del simbolo
             */
            exportSymbol: function(symbolId) {
                var self = this;
                self.closeContextMenu();

                var symbolData = this.symbols.find(function(symbol) {
                    return symbol.id === symbolId;
                });

                var exportFilename = symbolData ? 'symbol-' + symbolData.slug + '.json' : undefined;

                if (window.VBPSymbols && typeof VBPSymbols.downloadExport === 'function') {
                    VBPSymbols.downloadExport([symbolId], exportFilename);
                    self.showNotification('Exportando simbolo...', 'info');
                } else {
                    self.showNotification('Funcion de exportacion no disponible', 'error');
                }
            },

            /**
             * Mostrar modal de importacion
             */
            showImportModal: function() {
                var self = this;

                if (window.VBPSymbols && typeof VBPSymbols.showImportModal === 'function') {
                    VBPSymbols.showImportModal();
                } else {
                    self.showNotification('Funcion de importacion no disponible', 'error');
                }
            },

            /**
             * Muestra una notificación
             *
             * @param {string} message - Mensaje de la notificación
             * @param {string} notificationType - Tipo de notificación (success, error, warning, info)
             */
            showNotification: function(message, notificationType) {
                notificationType = notificationType || 'info';

                // Intentar usar el sistema de notificaciones de VBP
                var vbpApp = document.querySelector('[x-data="vbpApp()"]');
                if (vbpApp && vbpApp.__x && vbpApp.__x.$data && vbpApp.__x.$data.showNotification) {
                    vbpApp.__x.$data.showNotification(message, notificationType);
                    return;
                }

                // Fallback: usar console
                var logMethod = notificationType === 'error' ? 'error' : (notificationType === 'warning' ? 'warn' : 'log');
                console[logMethod]('[VBP Symbols Panel]', message);
            }
        };
    };

    // Registrar componente cuando Alpine esté listo
    document.addEventListener('alpine:init', function() {
        // El componente ya está registrado como función global
        if (typeof window.vbpLog !== 'undefined') {
            window.vbpLog.log('Symbols Panel component registered');
        }
    });

})();
