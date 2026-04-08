/**
 * Visual Builder Pro - Store
 * Estado global con Alpine.js
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

document.addEventListener('alpine:init', () => {
    // Índice de elementos para búsquedas O(1)
    const elementIndex = new Map();

    // Flag para modo batch - evita reconstruir índice en cada operación
    let batchMode = false;
    let batchPendingRebuild = false;

    // Función para reconstruir el índice
    function rebuildIndex(elements) {
        // Si estamos en modo batch, marcar que necesita rebuild pero no hacerlo ahora
        if (batchMode) {
            batchPendingRebuild = true;
            return;
        }
        elementIndex.clear();
        elements.forEach(function(el, idx) {
            elementIndex.set(el.id, { element: el, index: idx });
        });
    }

    /**
     * Ejecuta múltiples operaciones en modo batch
     * El índice solo se reconstruye una vez al final
     * @param {Function} callback - Función con las operaciones a ejecutar
     */
    function executeBatch(callback) {
        batchMode = true;
        batchPendingRebuild = false;
        try {
            callback();
        } finally {
            batchMode = false;
            if (batchPendingRebuild) {
                const store = Alpine.store('vbp');
                elementIndex.clear();
                store.elements.forEach(function(el, idx) {
                    elementIndex.set(el.id, { element: el, index: idx });
                });
                batchPendingRebuild = false;
            }
        }
    }

    // Debounce para autosave usando VBPPerformance si está disponible
    const debouncedSave = window.VBPPerformance
        ? window.VBPPerformance.debounce(function(store) {
            if (store.isDirty && store.postId) {
                store.saveDocument({ autosave: true });
            }
        }, 3000)
        : function() {};

    function dispatchElementEvent(name, detail) {
        document.dispatchEvent(new CustomEvent(name, {
            detail: detail || {}
        }));
    }

    Alpine.store('vbp', {
        // Documento
        postId: 0,
        isDirty: false,
        elements: [],
        settings: {
            pageWidth: '1200',
            pageWidthUnit: 'px',
            backgroundColor: '#ffffff',
            customCss: ''
        },

        // Estado de guardado automático
        saveStatus: 'saved', // 'saved' | 'saving' | 'error' | 'dirty'
        lastSaved: null,
        lastSaveWasAutosave: false,
        saveError: null,
        savePromise: null,

        // UI
        zoom: 100,
        devicePreview: 'desktop',
        activeBreakpoint: 'desktop', // Breakpoint actual para edición de estilos
        activeStyleState: 'normal', // Estado de estilos: 'normal', 'hover', 'active', 'focus'
        showRulers: true,
        panels: { blocks: true, inspector: true, layers: true },
        inspectorMode: localStorage.getItem('vbp_inspector_mode') || 'basic', // 'basic' o 'advanced'
        showFloatingToolbar: false,
        floatingToolbarPosition: { x: 0, y: 0 },

        // Breakpoints configuration
        breakpoints: {
            desktop: { min: 1025, label: 'Desktop', icon: '🖥️' },
            tablet: { min: 769, max: 1024, label: 'Tablet', icon: '📱' },
            mobile: { min: 0, max: 768, label: 'Mobile', icon: '📲' }
        },

        // Selección
        selection: { elementIds: [], multiSelect: false },

        // Historial con descripciones
        history: { past: [], future: [] },
        maxHistorySize: 50,
        lastUndoDescription: '',
        lastRedoDescription: '',

        // Getters computados
        get canUndo() { return this.history.past.length > 0; },
        get canRedo() { return this.history.future.length > 0; },
        get undoDescription() {
            if (this.history.past.length > 0) {
                var lastEntry = this.history.past[this.history.past.length - 1];
                return lastEntry.description || 'Cambio';
            }
            return '';
        },
        get redoDescription() {
            if (this.history.future.length > 0) {
                return this.history.future[0].description || 'Cambio';
            }
            return '';
        },
        get selectedElement() {
            if (this.selection.elementIds.length === 1) {
                // Usar getElementDeep para encontrar elementos hijos de contenedores
                var element = this.getElementDeep(this.selection.elementIds[0]);
                // Asegurar estructura completa de estilos
                return this.ensureStylesComplete(element);
            }
            return null;
        },

        /**
         * Ejecuta múltiples operaciones en modo batch
         * El índice solo se reconstruye una vez al final, mejorando rendimiento
         * @param {Function} callback - Función con las operaciones a ejecutar
         * @example
         * store.batchOperations(function() {
         *     store.removeElement(id1);
         *     store.removeElement(id2);
         *     store.addElement('text');
         * }); // rebuildIndex solo se llama una vez
         */
        batchOperations(callback) {
            executeBatch(callback);
        },

        // Métodos de elementos
        addElement(type, index = -1) {
            this.saveToHistory();

            // Buscar datos del bloque original en VBP_Config
            var bloqueOriginal = this.getBlockFromConfig(type);

            const nuevoElemento = {
                id: (typeof generateElementId === 'function' ? generateElementId() : 'el_' + Math.random().toString(36).substr(2, 9)),
                type: type,
                variant: this.getDefaultVariant(type),
                name: bloqueOriginal ? bloqueOriginal.name : this.getDefaultName(type),
                visible: true,
                locked: false,
                data: this.getDefaultData(type),
                styles: this.getDefaultStyles(),
                children: []
            };

            // Si es un bloque de módulo, copiar datos adicionales
            if (bloqueOriginal) {
                if (bloqueOriginal.shortcode) {
                    nuevoElemento.shortcode = bloqueOriginal.shortcode;
                }
                if (bloqueOriginal.preview_html) {
                    nuevoElemento.preview_html = bloqueOriginal.preview_html;
                }
                if (bloqueOriginal.module) {
                    nuevoElemento.module = bloqueOriginal.module;
                }
            }

            if (index === -1) {
                this.elements.push(nuevoElemento);
            } else {
                this.elements.splice(index, 0, nuevoElemento);
            }

            // Actualizar índice
            rebuildIndex(this.elements);

            this.markAsDirty();
            this.setSelection([nuevoElemento.id]);
            dispatchElementEvent('vbp:element:added', {
                element: nuevoElemento,
                parentId: null,
                index: index
            });

            // Trigger autosave debounced
            debouncedSave(this);

            return nuevoElemento;
        },

        /**
         * Obtiene datos de un bloque desde VBP_Config.blocks
         */
        getBlockFromConfig(type) {
            if (typeof VBP_Config === 'undefined' || !VBP_Config.blocks) {
                return null;
            }

            for (var i = 0; i < VBP_Config.blocks.length; i++) {
                var categoria = VBP_Config.blocks[i];
                if (categoria.blocks) {
                    for (var j = 0; j < categoria.blocks.length; j++) {
                        var bloque = categoria.blocks[j];
                        if (bloque.id === type) {
                            return bloque;
                        }
                    }
                }
            }

            return null;
        },

        /**
         * Añadir elemento como hijo de un contenedor
         * @param {string} type - Tipo de elemento a crear
         * @param {string} containerId - ID del contenedor padre
         * @param {number} columnIndex - Índice de columna (para columns/row)
         */
        addElementToContainer(type, containerId, columnIndex = 0) {
            var container = this.getElement(containerId);
            if (!container) return null;

            // Verificar que sea un tipo de contenedor válido
            var containerTypes = ['container', 'columns', 'row', 'grid'];
            if (containerTypes.indexOf(container.type) === -1) return null;

            this.saveToHistory();

            // Buscar datos del bloque original en VBP_Config
            var bloqueOriginal = this.getBlockFromConfig(type);

            var nuevoElemento = {
                id: (typeof generateElementId === 'function' ? generateElementId() : 'el_' + Math.random().toString(36).substr(2, 9)),
                type: type,
                variant: this.getDefaultVariant(type),
                name: bloqueOriginal ? bloqueOriginal.name : this.getDefaultName(type),
                visible: true,
                locked: false,
                data: this.getDefaultData(type),
                styles: this.getDefaultStyles(),
                children: [],
                _columnIndex: columnIndex
            };

            // Si es un bloque de módulo, copiar datos adicionales
            if (bloqueOriginal) {
                if (bloqueOriginal.shortcode) {
                    nuevoElemento.shortcode = bloqueOriginal.shortcode;
                }
                if (bloqueOriginal.preview_html) {
                    nuevoElemento.preview_html = bloqueOriginal.preview_html;
                }
                if (bloqueOriginal.module) {
                    nuevoElemento.module = bloqueOriginal.module;
                }
            }

            // Asegurar que children existe
            if (!container.children) {
                container.children = [];
            }

            container.children.push(nuevoElemento);

            // Forzar actualización del contenedor
            var containerIndex = this.getElementIndex(containerId);
            if (containerIndex !== -1) {
                var newVersion = (this.elements[containerIndex]._version || 0) + 1;
                this.elements[containerIndex] = { ...container, _version: newVersion };
                elementIndex.set(containerId, { element: this.elements[containerIndex], index: containerIndex });
            }

            this.markAsDirty();
            this.setSelection([nuevoElemento.id]);
            dispatchElementEvent('vbp:element:added', {
                element: nuevoElemento,
                parentId: containerId,
                columnIndex: columnIndex
            });

            debouncedSave(this);

            return nuevoElemento;
        },

        /**
         * Obtener elemento por ID incluyendo hijos de contenedores (recursivo)
         */
        getElementDeep(id) {
            return window.VBPStoreTreeHelpers && typeof window.VBPStoreTreeHelpers.getElementDeep === 'function'
                ? window.VBPStoreTreeHelpers.getElementDeep(this.elements, this.getElement.bind(this), id)
                : null;
        },

        /**
         * Obtiene la ruta completa de un elemento desde la raíz
         * @param {string} id - ID del elemento
         * @returns {Array} Array de objetos {id, name, type} desde la raíz hasta el elemento
         */
        getElementPath(id) {
            return window.VBPStoreTreeHelpers && typeof window.VBPStoreTreeHelpers.getElementPath === 'function'
                ? window.VBPStoreTreeHelpers.getElementPath(this.elements, id)
                : [{ id: 'root', name: 'Página', type: 'root' }];
        },

        /**
         * Mover elemento a un contenedor
         */
        moveElementToContainer(elementId, containerId, columnIndex = 0) {
            var element = this.getElement(elementId);
            var container = this.getElement(containerId);
            if (!element || !container) return false;

            // Verificar que sea un contenedor válido
            var containerTypes = ['container', 'columns', 'row', 'grid'];
            if (containerTypes.indexOf(container.type) === -1) return false;

            this.saveToHistory();

            // Clonar el elemento
            var clonedElement = JSON.parse(JSON.stringify(element));
            clonedElement._columnIndex = columnIndex;

            // Añadir al contenedor
            if (!container.children) container.children = [];
            container.children.push(clonedElement);

            // Eliminar del nivel principal
            var elementIndex = this.getElementIndex(elementId);
            if (elementIndex !== -1) {
                this.elements.splice(elementIndex, 1);
                rebuildIndex(this.elements);
            }

            // Forzar actualización del contenedor
            var containerIdx = this.getElementIndex(containerId);
            if (containerIdx !== -1) {
                var newVersion = (this.elements[containerIdx]._version || 0) + 1;
                this.elements[containerIdx] = { ...container, _version: newVersion };
            }

            this.markAsDirty();
            debouncedSave(this);

            return true;
        },

        // Búsqueda optimizada O(1) usando índice
        getElement(id) {
            var cached = elementIndex.get(id);
            if (cached) {
                return cached.element;
            }
            // Fallback a búsqueda lineal si el índice no está actualizado
            return this.elements.find(el => el.id === id);
        },

        getElementById(id) {
            return this.getElementDeep(id) || this.getElement(id);
        },

        // Obtener índice de elemento O(1)
        getElementIndex(id) {
            var cached = elementIndex.get(id);
            if (cached) {
                return cached.index;
            }
            return this.elements.findIndex(el => el.id === id);
        },

        updateElement(id, cambios) {
            // Primero intentar en nivel principal
            const index = this.getElementIndex(id);
            if (index !== -1) {
                this.saveToHistory();
                // Incrementar versión para forzar re-render de Alpine.js
                var newVersion = (this.elements[index]._version || 0) + 1;
                this.elements[index] = { ...this.elements[index], ...cambios, _version: newVersion };
                // Actualizar índice con el elemento modificado
                elementIndex.set(id, { element: this.elements[index], index: index });
                this.markAsDirty();
                dispatchElementEvent('vbp:element:updated', {
                    id: id,
                    element: this.elements[index],
                    changes: cambios,
                    location: 'root'
                });
                // Trigger autosave debounced
                debouncedSave(this);
                return;
            }

            // Buscar en hijos de contenedores (recursivo para múltiples niveles)
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].children && this.elements[i].children.length > 0) {
                    if (window.VBPStoreMutationHelpers && typeof window.VBPStoreMutationHelpers.mutateChildren === 'function' &&
                        window.VBPStoreMutationHelpers.mutateChildren(this.elements[i].children, id, (children, childIndex) => {
                            this.saveToHistory();
                            var newChildVersion = (children[childIndex]._version || 0) + 1;
                            children[childIndex] = { ...children[childIndex], ...cambios, _version: newChildVersion };
                        })) {
                        // Forzar re-render del contenedor padre raíz
                        var parentVersion = (this.elements[i]._version || 0) + 1;
                        this.elements[i] = { ...this.elements[i], _version: parentVersion };
                        elementIndex.set(this.elements[i].id, { element: this.elements[i], index: i });
                        this.markAsDirty();
                        dispatchElementEvent('vbp:element:updated', {
                            id: id,
                            element: this.getElementDeep(id),
                            changes: cambios,
                            parentId: this.elements[i].id,
                            location: 'child'
                        });
                        debouncedSave(this);
                        return;
                    }
                }
            }
        },

        removeElement(id) {
            // Primero intentar en nivel principal
            const index = this.getElementIndex(id);
            if (index !== -1) {
                this.saveToHistory();
                this.elements.splice(index, 1);
                // Reconstruir índice
                rebuildIndex(this.elements);
                this.markAsDirty();
                this.clearSelection();
                dispatchElementEvent('vbp:element:removed', {
                    id: id,
                    location: 'root'
                });
                // Trigger autosave debounced
                debouncedSave(this);
                return;
            }

            // Buscar y eliminar de hijos de contenedores (recursivo)
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].children && this.elements[i].children.length > 0) {
                    if (window.VBPStoreMutationHelpers && typeof window.VBPStoreMutationHelpers.mutateChildren === 'function' &&
                        window.VBPStoreMutationHelpers.mutateChildren(this.elements[i].children, id, (children, childIndex) => {
                            this.saveToHistory();
                            children.splice(childIndex, 1);
                        })) {
                        // Forzar re-render del contenedor padre raíz
                        var parentVersion = (this.elements[i]._version || 0) + 1;
                        this.elements[i] = { ...this.elements[i], _version: parentVersion };
                        elementIndex.set(this.elements[i].id, { element: this.elements[i], index: i });
                        this.markAsDirty();
                        this.clearSelection();
                        dispatchElementEvent('vbp:element:removed', {
                            id: id,
                            parentId: this.elements[i].id,
                            location: 'child'
                        });
                        debouncedSave(this);
                        return;
                    }
                }
            }
        },

        /**
         * Fuerza la re-renderización de un elemento en el canvas
         * Útil para actualizar previews de módulos
         */
        refreshElement(id) {
            const index = this.getElementIndex(id);
            if (index !== -1) {
                // Incrementar versión para forzar re-render de Alpine.js
                var newVersion = (this.elements[index]._version || 0) + 1;
                this.elements[index] = { ...this.elements[index], _version: newVersion };
                // Actualizar índice con el elemento modificado
                elementIndex.set(id, { element: this.elements[index], index: index });
                dispatchElementEvent('vbp:element:updated', {
                    id: id,
                    element: this.elements[index],
                    changes: { _version: newVersion, forcePreviewRefresh: true },
                    location: 'root'
                });
            }
        },

        duplicateElement(id) {
            const original = this.getElement(id);
            if (!original) return null;

            this.saveToHistory();
            // Usar deepClone si está disponible para mejor rendimiento
            var duplicado = window.VBPPerformance
                ? window.VBPPerformance.deepClone(original)
                : JSON.parse(JSON.stringify(original));
            duplicado.id = (typeof generateElementId === 'function') ? generateElementId() : 'el_' + Math.random().toString(36).substr(2, 9);
            duplicado.name = original.name + ' (copia)';

            const index = this.getElementIndex(id);
            this.elements.splice(index + 1, 0, duplicado);
            // Reconstruir índice
            rebuildIndex(this.elements);
            this.markAsDirty();
            this.setSelection([duplicado.id]);
            dispatchElementEvent('vbp:element:added', {
                element: duplicado,
                sourceId: id,
                duplicated: true
            });
            // Trigger autosave debounced
            debouncedSave(this);
            return duplicado;
        },

        moveElement(fromIndex, toIndex) {
            if (fromIndex === toIndex) return;
            this.saveToHistory();
            const [elemento] = this.elements.splice(fromIndex, 1);
            this.elements.splice(toIndex, 0, elemento);
            // Reconstruir índice
            rebuildIndex(this.elements);
            this.markAsDirty();
            dispatchElementEvent('vbp:element:moved', {
                id: elemento && elemento.id,
                element: elemento,
                fromIndex: fromIndex,
                toIndex: toIndex
            });
            // Trigger autosave debounced
            debouncedSave(this);
        },

        // Selección
        setSelection(ids) {
            this.selection.elementIds = ids;
        },

        toggleSelection(id) {
            const index = this.selection.elementIds.indexOf(id);
            if (index === -1) {
                this.selection.elementIds.push(id);
            } else {
                this.selection.elementIds.splice(index, 1);
            }
        },

        clearSelection() {
            this.selection.elementIds = [];
        },

        // Inspector Mode
        toggleInspectorMode() {
            this.inspectorMode = this.inspectorMode === 'basic' ? 'advanced' : 'basic';
            localStorage.setItem('vbp_inspector_mode', this.inspectorMode);
        },

        setInspectorMode(mode) {
            if (mode === 'basic' || mode === 'advanced') {
                this.inspectorMode = mode;
                localStorage.setItem('vbp_inspector_mode', mode);
            }
        },

        // Style States (hover, active, focus)
        /**
         * Cambiar el estado de estilos activo para edición
         * @param {string} state - 'normal', 'hover', 'active', 'focus'
         */
        setStyleState(state) {
            var validStates = ['normal', 'hover', 'active', 'focus'];
            if (validStates.indexOf(state) !== -1) {
                this.activeStyleState = state;
            }
        },

        /**
         * Obtener estilos de un estado específico para un elemento
         * @param {string} elementId - ID del elemento
         * @param {string} state - 'normal', 'hover', 'active', 'focus'
         */
        getStateStyles(elementId, state) {
            var element = this.getElementDeep(elementId);
            if (!element || !element.styles) return null;

            if (state === 'normal') {
                return element.styles;
            }

            if (element.styles.states && element.styles.states[state]) {
                return element.styles.states[state];
            }

            return null;
        },

        /**
         * Actualizar un estilo de estado específico
         * @param {string} elementId - ID del elemento
         * @param {string} state - 'hover', 'active', 'focus'
         * @param {string} property - Propiedad a actualizar (background, color, etc.)
         * @param {string} value - Nuevo valor
         */
        updateStateStyle(elementId, state, property, value) {
            var element = this.getElementDeep(elementId);
            if (!element || !element.styles) return;

            // Asegurar estructura de states
            if (!element.styles.states) {
                element.styles.states = {
                    hover: { enabled: false },
                    active: { enabled: false },
                    focus: { enabled: false }
                };
            }

            if (!element.styles.states[state]) {
                element.styles.states[state] = { enabled: false };
            }

            // Actualizar propiedad
            element.styles.states[state][property] = value;

            // Auto-habilitar si se establece algún valor
            if (value && value !== '') {
                element.styles.states[state].enabled = true;
            }

            this.markAsDirty();
        },

        /**
         * Verificar si un elemento tiene estilos de estado definidos
         * @param {string} elementId - ID del elemento
         * @param {string} state - 'hover', 'active', 'focus'
         */
        hasStateStyles(elementId, state) {
            var element = this.getElementDeep(elementId);
            if (!element || !element.styles || !element.styles.states) return false;

            var stateStyles = element.styles.states[state];
            if (!stateStyles || !stateStyles.enabled) return false;

            // Verificar si hay algún valor definido
            var props = ['background', 'color', 'borderColor', 'boxShadow', 'transform', 'opacity'];
            for (var i = 0; i < props.length; i++) {
                if (stateStyles[props[i]] && stateStyles[props[i]] !== '') {
                    return true;
                }
            }

            return false;
        },

        /**
         * Actualizar configuración de transición
         * @param {string} elementId - ID del elemento
         * @param {object} transitionConfig - Configuración de transición
         */
        updateTransition(elementId, transitionConfig) {
            var element = this.getElementDeep(elementId);
            if (!element || !element.styles) return;

            if (!element.styles.transition) {
                element.styles.transition = {
                    enabled: false,
                    property: 'all',
                    duration: '0.3s',
                    timing: 'ease',
                    delay: ''
                };
            }

            Object.assign(element.styles.transition, transitionConfig);
            this.markAsDirty();
        },

        // Historial con descripciones
        saveToHistory(description) {
            this.pushHistory(description || 'Cambio');
        },

        pushHistory(description) {
            if (window.VBPStoreHistoryHelpers && typeof window.VBPStoreHistoryHelpers.pushHistory === 'function') {
                window.VBPStoreHistoryHelpers.pushHistory(this.history, this.elements, this.maxHistorySize, description || 'Cambio');
            }
        },

        undo() {
            if (!this.canUndo) return null;
            var result = window.VBPStoreHistoryHelpers && typeof window.VBPStoreHistoryHelpers.undo === 'function'
                ? window.VBPStoreHistoryHelpers.undo(this.history, this.elements)
                : null;
            if (!result) return null;

            this.elements = result.elements;
            this.lastUndoDescription = result.description;

            rebuildIndex(this.elements);
            this.markAsDirty();
            return this.lastUndoDescription;
        },

        redo() {
            if (!this.canRedo) return null;
            var result = window.VBPStoreHistoryHelpers && typeof window.VBPStoreHistoryHelpers.redo === 'function'
                ? window.VBPStoreHistoryHelpers.redo(this.history, this.elements)
                : null;
            if (!result) return null;

            this.elements = result.elements;
            this.lastRedoDescription = result.description;

            rebuildIndex(this.elements);
            this.markAsDirty();
            return this.lastRedoDescription;
        },

        saveDocument(options) {
            options = options || {};

            if (!this.postId) {
                return Promise.resolve({ success: false, message: 'Documento sin postId' });
            }

            if (!this.isDirty && !options.force) {
                return Promise.resolve({
                    success: true,
                    message: 'Sin cambios',
                    skipped: true
                });
            }

            if (this.savePromise) {
                return this.savePromise;
            }

            var self = this;
            var datosDocumento = {
                elements: this.elements,
                settings: this.settings
            };

            if (options.title !== undefined) {
                datosDocumento.title = options.title;
            }

            self.saveStatus = 'saving';
            self.saveError = null;
            self.lastSaveWasAutosave = !!options.autosave;

            document.dispatchEvent(new CustomEvent('vbp:beforeSave', {
                detail: {
                    postId: this.postId,
                    elements: this.elements,
                    autosave: !!options.autosave
                }
            }));

            this.savePromise = fetch(VBP_Config.restUrl + 'documents/' + this.postId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify(datosDocumento)
            })
            .then(function(response) {
                return response.json().catch(function() {
                    return {};
                }).then(function(payload) {
                    if (!response.ok || payload.success === false) {
                        var message = payload.message || (payload.data && payload.data.message) || 'Error del servidor';
                        throw new Error(message);
                    }
                    return payload;
                });
            })
            .then(function(result) {
                self.isDirty = false;
                self.saveStatus = 'saved';
                self.lastSaved = new Date();
                self.lastSaveWasAutosave = !!options.autosave;
                self.saveError = null;

                document.dispatchEvent(new CustomEvent('vbp:afterSave', {
                    detail: {
                        postId: self.postId,
                        success: true,
                        autosave: !!options.autosave,
                        result: result
                    }
                }));

                return result;
            })
            .catch(function(error) {
                console.warn('[VBP] Save error:', error);
                self.saveStatus = 'error';
                self.lastSaveWasAutosave = !!options.autosave;
                self.saveError = error.message || 'Error al guardar';

                document.dispatchEvent(new CustomEvent('vbp:saveError', {
                    detail: {
                        message: self.saveError,
                        autosave: !!options.autosave
                    }
                }));

                return {
                    success: false,
                    message: self.saveError
                };
            })
            .finally(function() {
                self.savePromise = null;
            });

            return this.savePromise;
        },

        // Compatibilidad: autosave ahora usa el mismo flujo canónico
        autoSave() {
            return this.saveDocument({ autosave: true });
        },

        /**
         * Obtiene el texto del estado de guardado para mostrar
         */
        getSaveStatusText() {
            switch (this.saveStatus) {
                case 'saving':
                    return 'Guardando...';
                case 'saved':
                    if (this.lastSaved) {
                        var ahora = new Date();
                        var diferencia = Math.floor((ahora - this.lastSaved) / 1000);
                        var prefijo = this.lastSaveWasAutosave ? 'Autosave hace ' : 'Guardado hace ';
                        if (diferencia < 60) return prefijo + diferencia + 's';
                        if (diferencia < 3600) return prefijo + Math.floor(diferencia / 60) + 'm';
                        return prefijo + Math.floor(diferencia / 3600) + 'h';
                    }
                    return this.lastSaveWasAutosave ? 'Autosave listo' : 'Guardado';
                case 'error':
                    return this.lastSaveWasAutosave
                        ? (this.saveError || 'Error en autosave')
                        : (this.saveError || 'Error al guardar');
                case 'dirty':
                    return 'Sin guardar';
                default:
                    return '';
            }
        },

        /**
         * Marca el documento como modificado (dirty)
         */
        markAsDirty() {
            this.isDirty = true;
            this.saveStatus = 'dirty';
            this.saveError = null;
            // Notificar al store de estado de guardado
            document.dispatchEvent(new CustomEvent('vbp:contentChanged'));
            debouncedSave(this);
        },

        // Inicializar elementos desde datos cargados
        initElements(elements) {
            this.elements = elements || [];
            rebuildIndex(this.elements);
        },

        // Helpers
        getDefaultName(type) {
            return window.VBPStoreCatalog && typeof window.VBPStoreCatalog.getDefaultName === 'function'
                ? window.VBPStoreCatalog.getDefaultName(type)
                : type;
        },

        getDefaultData(type) {
            return window.VBPStoreCatalog && typeof window.VBPStoreCatalog.getDefaultData === 'function'
                ? window.VBPStoreCatalog.getDefaultData(type)
                : {};
        },

        getDefaultStyles() {
            return window.VBPStoreCatalog && typeof window.VBPStoreCatalog.getDefaultStyles === 'function'
                ? window.VBPStoreCatalog.getDefaultStyles()
                : {};
        },

        /**
         * Asegurar que un elemento tiene la estructura completa de estilos
         */
        ensureStylesComplete(element) {
            return window.VBPStoreStyleHelpers && typeof window.VBPStoreStyleHelpers.ensureStylesComplete === 'function'
                ? window.VBPStoreStyleHelpers.ensureStylesComplete(element, this.getDefaultStyles())
                : element;
        },

        /**
         * Obtener variante por defecto según tipo
         */
        getDefaultVariant(type) {
            return window.VBPStoreCatalog && typeof window.VBPStoreCatalog.getDefaultVariant === 'function'
                ? window.VBPStoreCatalog.getDefaultVariant(type)
                : 'default';
        },

        /**
         * Obtener variantes disponibles para un tipo
         */
        getVariantsForType(type) {
            return window.VBPStoreCatalog && typeof window.VBPStoreCatalog.getVariantsForType === 'function'
                ? window.VBPStoreCatalog.getVariantsForType(type)
                : [];
        },

        /**
         * Establecer breakpoint activo para edición
         */
        setActiveBreakpoint(breakpoint) {
            if (this.breakpoints[breakpoint]) {
                this.activeBreakpoint = breakpoint;
                // Sincronizar con devicePreview
                this.devicePreview = breakpoint;
            }
        },

        /**
         * Obtener estilos de un elemento para el breakpoint activo
         * Combina estilos base (desktop) con overrides del breakpoint
         */
        getElementStyles(elementId) {
            var element = this.getElement(elementId);
            if (!element) return {};

            var baseStyles = element.styles || {};

            // Si estamos en desktop, devolver estilos base
            if (this.activeBreakpoint === 'desktop') {
                return baseStyles;
            }

            // Para tablet/mobile, combinar con overrides
            var responsiveStyles = element.responsiveStyles || {};
            var breakpointOverrides = responsiveStyles[this.activeBreakpoint] || {};

            return this.mergeStyles(baseStyles, breakpointOverrides);
        },

        /**
         * Actualizar estilo para el breakpoint activo
         */
        updateElementStyleForBreakpoint(elementId, stylePath, value) {
            var element = this.getElement(elementId);
            if (!element) return;

            this.saveToHistory();

            // Si es desktop, actualizar estilos base directamente
            if (this.activeBreakpoint === 'desktop') {
                var styles = JSON.parse(JSON.stringify(element.styles || {}));
                this.setNestedValue(styles, stylePath, value);
                this.updateElement(elementId, { styles: styles });
            } else {
                // Para tablet/mobile, actualizar responsiveStyles
                var responsiveStyles = JSON.parse(JSON.stringify(element.responsiveStyles || {}));
                if (!responsiveStyles[this.activeBreakpoint]) {
                    responsiveStyles[this.activeBreakpoint] = {};
                }
                this.setNestedValue(responsiveStyles[this.activeBreakpoint], stylePath, value);
                this.updateElement(elementId, { responsiveStyles: responsiveStyles });
            }

            this.markAsDirty();
            debouncedSave(this);
        },

        /**
         * Limpiar overrides de un breakpoint específico
         */
        clearBreakpointOverrides(elementId, breakpoint) {
            var element = this.getElement(elementId);
            if (!element || breakpoint === 'desktop') return;

            var responsiveStyles = JSON.parse(JSON.stringify(element.responsiveStyles || {}));
            delete responsiveStyles[breakpoint];
            this.updateElement(elementId, { responsiveStyles: responsiveStyles });
        },

        /**
         * Combinar estilos base con overrides
         */
        mergeStyles(base, overrides) {
            return window.VBPStoreStyleHelpers && typeof window.VBPStoreStyleHelpers.mergeStyles === 'function'
                ? window.VBPStoreStyleHelpers.mergeStyles(base, overrides)
                : base;
        },

        /**
         * Establecer valor en objeto anidado usando path tipo 'spacing.padding.top'
         */
        setNestedValue(obj, path, value) {
            if (window.VBPStoreStyleHelpers && typeof window.VBPStoreStyleHelpers.setNestedValue === 'function') {
                window.VBPStoreStyleHelpers.setNestedValue(obj, path, value);
            }
        },

        /**
         * Obtener valor de objeto anidado
         */
        getNestedValue(obj, path) {
            return window.VBPStoreStyleHelpers && typeof window.VBPStoreStyleHelpers.getNestedValue === 'function'
                ? window.VBPStoreStyleHelpers.getNestedValue(obj, path)
                : undefined;
        },

        /**
         * Verificar si un elemento tiene overrides para un breakpoint
         */
        hasBreakpointOverrides(elementId, breakpoint) {
            var element = this.getElement(elementId);
            if (!element || breakpoint === 'desktop') return false;

            var responsiveStyles = element.responsiveStyles || {};
            return responsiveStyles[breakpoint] && Object.keys(responsiveStyles[breakpoint]).length > 0;
        },

        // ============================================
        // Inspector Mode (Basic/Advanced)
        // ============================================

    });
});
