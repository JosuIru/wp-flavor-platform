/**
 * Visual Builder Pro - Store
 * Estado global con Alpine.js
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', () => {
    // Store para modales de selectores
    Alpine.store('vbpModals', {
        iconSelector: {
            open: false,
            callback: null,
            currentValue: '',
            field: '',
            itemIndex: null
        },
        emojiPicker: {
            open: false,
            callback: null,
            position: { x: 0, y: 0 },
            field: '',
            itemIndex: null
        },

        openEmojiPicker: function(callback, position, field, itemIndex) {
            this.emojiPicker.callback = callback;
            this.emojiPicker.position = position || { x: 0, y: 0 };
            this.emojiPicker.field = field || '';
            this.emojiPicker.itemIndex = itemIndex !== undefined ? itemIndex : null;
            this.emojiPicker.open = true;
        },

        closeEmojiPicker: function() {
            this.emojiPicker.open = false;
            this.emojiPicker.callback = null;
        },

        applyEmojiSelection: function(emoji) {
            if (this.emojiPicker.callback) {
                this.emojiPicker.callback(emoji);
            }
            this.closeEmojiPicker();
        },
        linkSearch: {
            open: false,
            callback: null
        },

        openIconSelector: function(callback, currentValue, field, itemIndex) {
            this.iconSelector.callback = callback;
            this.iconSelector.currentValue = currentValue || '';
            this.iconSelector.field = field || 'icono';
            this.iconSelector.itemIndex = itemIndex !== undefined ? itemIndex : null;
            this.iconSelector.open = true;
        },

        closeIconSelector: function() {
            this.iconSelector.open = false;
            this.iconSelector.callback = null;
        },

        applyIconSelection: function(type, value) {
            if (this.iconSelector.callback) {
                this.iconSelector.callback(type, value);
            }
            this.closeIconSelector();
        }
    });

    // Índice de elementos para búsquedas O(1)
    var elementIndex = new Map();

    // Flag para modo batch - evita reconstruir índice en cada operación
    var batchMode = false;
    var batchPendingRebuild = false;

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
                var store = Alpine.store('vbp');
                elementIndex.clear();
                store.elements.forEach(function(el, idx) {
                    elementIndex.set(el.id, { element: el, index: idx });
                });
                batchPendingRebuild = false;
            }
        }
    }

    // Debounce para autosave usando VBPPerformance si está disponible
    var debouncedSave = window.VBPPerformance
        ? window.VBPPerformance.debounce(function(store) {
            if (store.isDirty && store.postId) {
                store.autoSave();
            }
        }, 3000)
        : function() {};

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
        saveError: null,

        // UI
        zoom: 100,
        devicePreview: 'desktop',
        activeBreakpoint: 'desktop', // Breakpoint actual para edición de estilos
        showRulers: true,
        panels: { blocks: true, inspector: true, layers: true },
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

        // Historial
        history: { past: [], future: [] },
        maxHistorySize: 50,

        // Getters computados
        get canUndo() { return this.history.past.length > 0; },
        get canRedo() { return this.history.future.length > 0; },
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
                id: 'el_' + Math.random().toString(36).substr(2, 9),
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
                id: 'el_' + Math.random().toString(36).substr(2, 9),
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

            debouncedSave(this);

            return nuevoElemento;
        },

        /**
         * Obtener elemento por ID incluyendo hijos de contenedores (recursivo)
         */
        getElementDeep(id) {
            // Primero buscar en elementos principales
            var element = this.getElement(id);
            if (element) return element;

            // Función recursiva para buscar en hijos anidados
            function findInChildren(children) {
                if (!children || children.length === 0) return null;
                for (var j = 0; j < children.length; j++) {
                    if (children[j].id === id) {
                        return children[j];
                    }
                    // Buscar recursivamente en hijos anidados
                    if (children[j].children && children[j].children.length > 0) {
                        var found = findInChildren(children[j].children);
                        if (found) return found;
                    }
                }
                return null;
            }

            // Buscar en hijos de contenedores de nivel raíz
            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].children && this.elements[i].children.length > 0) {
                    var found = findInChildren(this.elements[i].children);
                    if (found) return found;
                }
            }
            return null;
        },

        /**
         * Obtiene la ruta completa de un elemento desde la raíz
         * @param {string} id - ID del elemento
         * @returns {Array} Array de objetos {id, name, type} desde la raíz hasta el elemento
         */
        getElementPath(id) {
            var path = [];
            var self = this;

            // Añadir "Página" como raíz siempre
            path.push({ id: 'root', name: 'Página', type: 'root' });

            // Función recursiva para encontrar el camino
            function findPath(elements, targetId, currentPath) {
                for (var i = 0; i < elements.length; i++) {
                    var el = elements[i];
                    var newPath = currentPath.concat([{
                        id: el.id,
                        name: el.name || el.type,
                        type: el.type
                    }]);

                    if (el.id === targetId) {
                        return newPath;
                    }

                    // Buscar en hijos recursivamente
                    if (el.children && el.children.length > 0) {
                        var foundPath = findPath(el.children, targetId, newPath);
                        if (foundPath) return foundPath;
                    }
                }
                return null;
            }

            var elementPath = findPath(this.elements, id, []);
            if (elementPath) {
                path = path.concat(elementPath);
            }

            return path;
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
                // Trigger autosave debounced
                debouncedSave(this);
                return;
            }

            // Buscar en hijos de contenedores (recursivo para múltiples niveles)
            var self = this;
            function findAndUpdateChild(children, parentElement) {
                if (!children || children.length === 0) return false;
                for (var j = 0; j < children.length; j++) {
                    if (children[j].id === id) {
                        self.saveToHistory();
                        // Actualizar el hijo
                        var newChildVersion = (children[j]._version || 0) + 1;
                        children[j] = { ...children[j], ...cambios, _version: newChildVersion };
                        return true;
                    }
                    // Buscar recursivamente en hijos anidados
                    if (children[j].children && children[j].children.length > 0) {
                        if (findAndUpdateChild(children[j].children, children[j])) {
                            // Actualizar versión del contenedor intermedio
                            var intermediateVersion = (children[j]._version || 0) + 1;
                            children[j] = { ...children[j], _version: intermediateVersion };
                            return true;
                        }
                    }
                }
                return false;
            }

            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].children && this.elements[i].children.length > 0) {
                    if (findAndUpdateChild(this.elements[i].children, this.elements[i])) {
                        // Forzar re-render del contenedor padre raíz
                        var parentVersion = (this.elements[i]._version || 0) + 1;
                        this.elements[i] = { ...this.elements[i], _version: parentVersion };
                        elementIndex.set(this.elements[i].id, { element: this.elements[i], index: i });
                        this.markAsDirty();
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
                // Trigger autosave debounced
                debouncedSave(this);
                return;
            }

            // Buscar y eliminar de hijos de contenedores (recursivo)
            var self = this;
            function findAndRemoveChild(children) {
                if (!children || children.length === 0) return false;
                for (var j = 0; j < children.length; j++) {
                    if (children[j].id === id) {
                        self.saveToHistory();
                        children.splice(j, 1);
                        return true;
                    }
                    // Buscar recursivamente en hijos anidados
                    if (children[j].children && children[j].children.length > 0) {
                        if (findAndRemoveChild(children[j].children)) {
                            // Actualizar versión del contenedor intermedio
                            var intermediateVersion = (children[j]._version || 0) + 1;
                            children[j] = { ...children[j], _version: intermediateVersion };
                            return true;
                        }
                    }
                }
                return false;
            }

            for (var i = 0; i < this.elements.length; i++) {
                if (this.elements[i].children && this.elements[i].children.length > 0) {
                    if (findAndRemoveChild(this.elements[i].children)) {
                        // Forzar re-render del contenedor padre raíz
                        var parentVersion = (this.elements[i]._version || 0) + 1;
                        this.elements[i] = { ...this.elements[i], _version: parentVersion };
                        elementIndex.set(this.elements[i].id, { element: this.elements[i], index: i });
                        this.markAsDirty();
                        this.clearSelection();
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
            duplicado.id = 'el_' + Math.random().toString(36).substr(2, 9);
            duplicado.name = original.name + ' (copia)';

            const index = this.getElementIndex(id);
            this.elements.splice(index + 1, 0, duplicado);
            // Reconstruir índice
            rebuildIndex(this.elements);
            this.markAsDirty();
            this.setSelection([duplicado.id]);
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

        // Historial
        saveToHistory() {
            const estado = JSON.stringify(this.elements);
            this.history.past.push(estado);
            this.history.future = [];

            if (this.history.past.length > this.maxHistorySize) {
                this.history.past.shift();
            }
        },

        undo() {
            if (!this.canUndo) return;
            const estadoActual = JSON.stringify(this.elements);
            this.history.future.unshift(estadoActual);
            const estadoAnterior = this.history.past.pop();
            this.elements = JSON.parse(estadoAnterior);
            // Reconstruir índice después de undo
            rebuildIndex(this.elements);
            this.markAsDirty();
        },

        redo() {
            if (!this.canRedo) return;
            const estadoActual = JSON.stringify(this.elements);
            this.history.past.push(estadoActual);
            const estadoSiguiente = this.history.future.shift();
            this.elements = JSON.parse(estadoSiguiente);
            // Reconstruir índice después de redo
            rebuildIndex(this.elements);
            this.markAsDirty();
        },

        // Autosave - llamado por debounce
        autoSave() {
            if (!this.isDirty || !this.postId) return;

            var self = this;
            var datosDocumento = {
                elements: this.elements,
                settings: this.settings
            };

            // Actualizar estado a "saving"
            self.saveStatus = 'saving';
            self.saveError = null;

            // Notificar inicio de guardado
            document.dispatchEvent(new CustomEvent('vbp:beforeSave', {
                detail: { postId: this.postId, elements: this.elements }
            }));

            fetch(VBP_Config.restUrl + 'documents/' + this.postId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify(datosDocumento)
            })
            .then(function(response) {
                if (response.ok) {
                    self.isDirty = false;
                    self.saveStatus = 'saved';
                    self.lastSaved = new Date();
                    self.saveError = null;
                    // Notificar guardado exitoso
                    document.dispatchEvent(new CustomEvent('vbp:afterSave', {
                        detail: { postId: self.postId, success: true }
                    }));
                    return response.json();
                } else {
                    // Leer el cuerpo del error para debugging
                    return response.json().then(function(errorData) {
                        console.error('[VBP] Autosave server error:', response.status, errorData);
                        throw new Error(errorData.message || 'Error del servidor');
                    });
                }
            })
            .catch(function(error) {
                console.warn('[VBP] Autosave error:', error);
                self.saveStatus = 'error';
                self.saveError = error.message || 'Error al guardar';
                // Notificar error
                document.dispatchEvent(new CustomEvent('vbp:saveError', {
                    detail: { message: error.message || 'Error al guardar' }
                }));
            });
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
                        if (diferencia < 60) return 'Guardado hace ' + diferencia + 's';
                        if (diferencia < 3600) return 'Guardado hace ' + Math.floor(diferencia / 60) + 'm';
                        return 'Guardado hace ' + Math.floor(diferencia / 3600) + 'h';
                    }
                    return 'Guardado';
                case 'error':
                    return this.saveError || 'Error al guardar';
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
            // Notificar al store de estado de guardado
            document.dispatchEvent(new CustomEvent('vbp:contentChanged'));
        },

        // Inicializar elementos desde datos cargados
        initElements(elements) {
            this.elements = elements || [];
            rebuildIndex(this.elements);
        },

        // Helpers
        getDefaultName(type) {
            const nombres = {
                // Secciones
                hero: 'Hero',
                features: 'Características',
                testimonials: 'Testimonios',
                pricing: 'Precios',
                cta: 'Llamada a Acción',
                faq: 'FAQ',
                contact: 'Contacto',
                team: 'Equipo',
                stats: 'Estadísticas',
                gallery: 'Galería',
                blog: 'Blog',
                'video-section': 'Sección Video',
                // Básicos
                heading: 'Encabezado',
                text: 'Texto',
                image: 'Imagen',
                button: 'Botón',
                divider: 'Separador',
                spacer: 'Espaciador',
                icon: 'Icono',
                html: 'HTML',
                shortcode: 'Shortcode',
                // Layout
                container: 'Contenedor',
                columns: 'Columnas',
                row: 'Fila',
                grid: 'Grid',
                // Formularios
                form: 'Formulario',
                input: 'Campo de texto',
                textarea: 'Área de texto',
                select: 'Selector',
                checkbox: 'Checkbox',
                // Media
                'video-embed': 'Video Embed',
                audio: 'Audio',
                map: 'Mapa',
                mapa: 'Mapa',
                embed: 'Embed',
                // Nuevos bloques
                countdown: 'Cuenta Regresiva',
                'social-icons': 'Iconos Sociales',
                newsletter: 'Newsletter',
                'logo-grid': 'Grid de Logos',
                'icon-box': 'Caja de Icono',
                accordion: 'Acordeón',
                tabs: 'Pestañas',
                'progress-bar': 'Barra de Progreso',
                alert: 'Alerta',
                'before-after': 'Antes/Después'
            };
            return nombres[type] || type;
        },

        getDefaultData(type) {
            const defaults = {
                // Secciones
                hero: {
                    titulo: 'Título Principal',
                    subtitulo: 'Subtítulo descriptivo que explica el valor de tu propuesta',
                    boton_texto: 'Comenzar ahora',
                    boton_url: '#',
                    imagen_fondo: ''
                },
                features: {
                    titulo: 'Nuestras Características',
                    items: [
                        { icono: '⚡', titulo: 'Rápido', descripcion: 'Implementación en minutos' },
                        { icono: '🔒', titulo: 'Seguro', descripcion: 'Protección de datos garantizada' },
                        { icono: '📱', titulo: 'Responsive', descripcion: 'Funciona en todos los dispositivos' }
                    ]
                },
                testimonials: {
                    titulo: 'Lo que dicen nuestros clientes',
                    items: [
                        { texto: 'Excelente servicio, muy recomendado. Ha superado todas nuestras expectativas.', autor: 'María García', cargo: 'CEO, Empresa X' }
                    ]
                },
                pricing: {
                    titulo: 'Planes y Precios',
                    subtitulo: 'Elige el plan que mejor se adapte a tus necesidades',
                    items: [
                        { nombre: 'Básico', precio: '9', periodo: '/mes', caracteristicas: ['5 usuarios', '10GB almacenamiento', 'Soporte email'], destacado: false },
                        { nombre: 'Pro', precio: '29', periodo: '/mes', caracteristicas: ['25 usuarios', '100GB almacenamiento', 'Soporte prioritario'], destacado: true },
                        { nombre: 'Enterprise', precio: '99', periodo: '/mes', caracteristicas: ['Usuarios ilimitados', '1TB almacenamiento', 'Soporte 24/7'], destacado: false }
                    ]
                },
                cta: {
                    titulo: '¿Listo para empezar?',
                    subtitulo: 'Únete a miles de usuarios que ya confían en nosotros',
                    boton_texto: 'Empezar gratis',
                    boton_url: '#'
                },
                faq: {
                    titulo: 'Preguntas Frecuentes',
                    items: [
                        { pregunta: '¿Cómo funciona?', respuesta: 'Es muy sencillo, solo tienes que registrarte y empezar a usar la plataforma.' },
                        { pregunta: '¿Puedo cancelar en cualquier momento?', respuesta: 'Sí, puedes cancelar tu suscripción cuando quieras sin penalizaciones.' },
                        { pregunta: '¿Ofrecen soporte técnico?', respuesta: 'Sí, ofrecemos soporte técnico 24/7 para todos nuestros usuarios.' }
                    ]
                },
                contact: {
                    titulo: 'Contáctanos',
                    subtitulo: 'Estaremos encantados de ayudarte'
                },
                team: {
                    titulo: 'Nuestro Equipo',
                    items: [
                        { nombre: 'Ana García', cargo: 'CEO', bio: 'Fundadora con más de 10 años de experiencia.' },
                        { nombre: 'Carlos López', cargo: 'CTO', bio: 'Experto en tecnología e innovación.' },
                        { nombre: 'María Rodríguez', cargo: 'CMO', bio: 'Especialista en marketing digital.' }
                    ]
                },
                stats: {
                    items: [
                        { numero: '10K+', label: 'Usuarios activos' },
                        { numero: '99%', label: 'Satisfacción' },
                        { numero: '24/7', label: 'Soporte' },
                        { numero: '50+', label: 'Países' }
                    ]
                },
                gallery: {
                    titulo: 'Galería',
                    items: []
                },
                blog: {
                    titulo: 'Últimas Noticias'
                },
                'video-section': {
                    titulo: 'Mira cómo funciona',
                    descripcion: 'Descripción del video',
                    video_url: ''
                },
                // Básicos
                heading: { text: 'Escribe tu encabezado aquí', level: 'h2' },
                text: { text: '<p>Escribe tu texto aquí. Puedes usar <strong>negrita</strong>, <em>cursiva</em> y más formatos usando la barra de herramientas flotante.</p>' },
                image: { src: '', alt: '', caption: '' },
                button: { text: 'Botón', url: '#', target: '_self', style: 'filled', align: 'left' },
                divider: { style: 'solid', width: '1px', color: '#e0e0e0' },
                spacer: { height: '60px' },
                icon: { icon: '⭐', size: '48px' },
                html: { code: '<!-- Tu código HTML aquí -->' },
                shortcode: { shortcode: 'tu_shortcode' },
                // Layout
                container: { maxWidth: '1200px' },
                columns: { columns: 2 },
                row: { columns: 2 },
                grid: {},
                // Formularios
                form: {
                    titulo: 'Formulario',
                    boton_texto: 'Enviar',
                    boton_url: '',
                    mensaje_exito: '¡Gracias! Tu mensaje ha sido enviado.',
                    campos: [
                        { tipo: 'text', label: 'Nombre', placeholder: 'Tu nombre', requerido: true },
                        { tipo: 'email', label: 'Email', placeholder: 'tu@email.com', requerido: true },
                        { tipo: 'textarea', label: 'Mensaje', placeholder: 'Escribe tu mensaje...', requerido: false }
                    ]
                },
                input: { label: 'Campo', inputType: 'text', placeholder: 'Escribe aquí...' },
                textarea: { label: 'Mensaje', placeholder: 'Escribe tu mensaje...' },
                select: { label: 'Selecciona' },
                checkbox: { label: 'Acepto los términos y condiciones' },
                // Media
                'video-embed': { url: '' },
                audio: { src: '' },
                map: { lat: '', lng: '', zoom: 14 },
                mapa: { lat: '', lng: '', zoom: 14 },
                embed: { code: '' },
                // Nuevos bloques
                countdown: {
                    titulo: 'La oferta termina en',
                    fecha: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    hora: '23:59',
                    mensaje_fin: '¡La oferta ha terminado!',
                    mostrar_dias: true,
                    mostrar_horas: true,
                    mostrar_minutos: true,
                    mostrar_segundos: true
                },
                'social-icons': {
                    titulo: 'Síguenos',
                    redes: [
                        { red: 'facebook', url: '#', icono: '📘' },
                        { red: 'twitter', url: '#', icono: '🐦' },
                        { red: 'instagram', url: '#', icono: '📸' },
                        { red: 'linkedin', url: '#', icono: '💼' }
                    ],
                    estilo: 'circle',
                    tamano: 'medium',
                    alineacion: 'center'
                },
                newsletter: {
                    titulo: 'Suscríbete a nuestro newsletter',
                    subtitulo: 'Recibe las últimas novedades directamente en tu email',
                    placeholder_email: 'tu@email.com',
                    boton_texto: 'Suscribirse',
                    mostrar_nombre: false,
                    mensaje_exito: '¡Gracias por suscribirte!'
                },
                'logo-grid': {
                    titulo: 'Confían en nosotros',
                    logos: [],
                    columnas: 4,
                    escala_grises: true,
                    hover_color: true
                },
                'icon-box': {
                    icono: '🚀',
                    titulo: 'Título',
                    descripcion: 'Descripción del servicio o característica',
                    enlace_url: '',
                    enlace_texto: 'Saber más',
                    alineacion: 'center'
                },
                accordion: {
                    titulo: 'Acordeón',
                    items: [
                        { titulo: 'Elemento 1', contenido: 'Contenido del elemento 1', abierto: true },
                        { titulo: 'Elemento 2', contenido: 'Contenido del elemento 2', abierto: false },
                        { titulo: 'Elemento 3', contenido: 'Contenido del elemento 3', abierto: false }
                    ],
                    multiples_abiertos: false
                },
                tabs: {
                    items: [
                        { titulo: 'Tab 1', contenido: 'Contenido de la pestaña 1' },
                        { titulo: 'Tab 2', contenido: 'Contenido de la pestaña 2' },
                        { titulo: 'Tab 3', contenido: 'Contenido de la pestaña 3' }
                    ],
                    tab_activa: 0,
                    estilo: 'horizontal'
                },
                'progress-bar': {
                    items: [
                        { label: 'Diseño UI/UX', porcentaje: 90 },
                        { label: 'Desarrollo Web', porcentaje: 85 },
                        { label: 'Marketing Digital', porcentaje: 75 }
                    ],
                    mostrar_porcentaje: true,
                    animado: true
                },
                alert: {
                    tipo: 'info',
                    titulo: 'Información importante',
                    mensaje: 'Este es un mensaje de alerta para el usuario.',
                    dismissible: true,
                    icono: true
                },
                'before-after': {
                    imagen_antes: '',
                    imagen_despues: '',
                    label_antes: 'Antes',
                    label_despues: 'Después',
                    posicion_inicial: 50,
                    orientacion: 'horizontal'
                },
                timeline: {
                    titulo: 'Línea de Tiempo',
                    subtitulo: '',
                    titulo_color: '#ffffff',
                    subtitulo_color: '#9CA3AF',
                    color_fondo: '#0f0f0f',
                    linea_color: '#3b82f6',
                    linea_posicion: 'center',
                    eventos: [
                        { fecha: '2020', titulo: 'Primer evento', descripcion: 'Descripción del primer evento', icono: '🚀' },
                        { fecha: '2022', titulo: 'Segundo evento', descripcion: 'Descripción del segundo evento', icono: '📈' },
                        { fecha: '2024', titulo: 'Tercer evento', descripcion: 'Descripción del tercer evento', icono: '🎯' }
                    ]
                },
                carousel: {
                    titulo: '',
                    subtitulo: '',
                    titulo_color: '#ffffff',
                    subtitulo_color: '#9CA3AF',
                    color_fondo: '#0f0f0f',
                    autoplay: true,
                    intervalo: 5,
                    mostrar_flechas: true,
                    mostrar_dots: true,
                    loop: true,
                    slides_visibles: 1,
                    items: [
                        { imagen: '', titulo: 'Slide 1', descripcion: 'Descripción del primer slide', enlace_url: '', enlace_texto: 'Ver más' },
                        { imagen: '', titulo: 'Slide 2', descripcion: 'Descripción del segundo slide', enlace_url: '', enlace_texto: 'Ver más' }
                    ]
                }
            };
            return defaults[type] || {};
        },

        getDefaultStyles() {
            return {
                spacing: {
                    margin: { top: '', right: '', bottom: '', left: '' },
                    padding: { top: '', right: '', bottom: '', left: '' }
                },
                colors: { background: '', text: '' },
                background: {
                    type: '',
                    gradientDirection: 'to bottom',
                    gradientStart: '#3b82f6',
                    gradientEnd: '#8b5cf6',
                    image: '',
                    size: 'cover',
                    position: 'center',
                    repeat: 'no-repeat',
                    fixed: false,
                    overlayOpacity: 0
                },
                typography: { fontSize: '', fontWeight: '', lineHeight: '', textAlign: '' },
                borders: { radius: '', width: '', color: '', style: '' },
                shadows: { boxShadow: '' },
                layout: { display: '', flexDirection: '', justifyContent: '', alignItems: '', gap: '' },
                dimensions: { width: '', height: '', minHeight: '', maxWidth: '' },
                position: {
                    position: '',
                    top: '',
                    right: '',
                    bottom: '',
                    left: '',
                    zIndex: ''
                },
                transform: {
                    rotate: '',
                    scale: '',
                    translateX: '',
                    translateY: '',
                    skewX: '',
                    skewY: ''
                },
                overflow: '',
                opacity: '',
                advanced: {
                    cssId: '',
                    cssClasses: '',
                    customCss: '',
                    entranceAnimation: '',
                    hoverAnimation: '',
                    loopAnimation: '',
                    parallaxEnabled: false,
                    parallaxSpeed: 0.5
                }
            };
        },

        /**
         * Asegurar que un elemento tiene la estructura completa de estilos
         */
        ensureStylesComplete(element) {
            if (!element) return element;

            var defaults = this.getDefaultStyles();

            if (!element.styles) {
                element.styles = defaults;
                return element;
            }

            // Merge profundo para cada sección
            var sections = ['spacing', 'colors', 'background', 'typography', 'borders', 'shadows', 'layout', 'dimensions', 'position', 'transform', 'advanced'];
            var self = this;

            sections.forEach(function(section) {
                if (!element.styles[section]) {
                    element.styles[section] = defaults[section];
                } else if (typeof defaults[section] === 'object') {
                    // Merge de subobjetos
                    Object.keys(defaults[section]).forEach(function(key) {
                        if (element.styles[section][key] === undefined) {
                            element.styles[section][key] = defaults[section][key];
                        }
                    });
                }
            });

            // Asegurar subobjetos de spacing
            if (element.styles.spacing) {
                if (!element.styles.spacing.margin) {
                    element.styles.spacing.margin = { top: '', right: '', bottom: '', left: '' };
                }
                if (!element.styles.spacing.padding) {
                    element.styles.spacing.padding = { top: '', right: '', bottom: '', left: '' };
                }
            }

            // Asegurar propiedades simples
            if (element.styles.overflow === undefined) {
                element.styles.overflow = '';
            }
            if (element.styles.opacity === undefined) {
                element.styles.opacity = '';
            }

            return element;
        },

        /**
         * Obtener variante por defecto según tipo
         */
        getDefaultVariant(type) {
            const defaults = {
                hero: 'centered',
                features: 'grid',
                testimonials: 'cards',
                pricing: 'columns',
                cta: 'centered',
                faq: 'simple',
                contact: 'simple',
                team: 'grid',
                button: 'filled',
                divider: 'solid',
                'icon-box': 'vertical',
                accordion: 'simple',
                tabs: 'horizontal',
                alert: 'info',
                newsletter: 'inline'
            };
            return defaults[type] || 'default';
        },

        /**
         * Obtener variantes disponibles para un tipo
         */
        getVariantsForType(type) {
            const variants = {
                hero: [
                    { id: 'centered', name: 'Centrado', icon: '⊡' },
                    { id: 'left', name: 'Izquierda', icon: '⊟' },
                    { id: 'split', name: 'Dividido', icon: '⊞' },
                    { id: 'video-bg', name: 'Video fondo', icon: '▶' },
                    { id: 'minimal', name: 'Minimalista', icon: '―' }
                ],
                features: [
                    { id: 'grid', name: 'Grid', icon: '⊞' },
                    { id: 'list', name: 'Lista', icon: '≡' },
                    { id: 'icons', name: 'Iconos', icon: '◎' },
                    { id: 'cards', name: 'Tarjetas', icon: '▢' }
                ],
                testimonials: [
                    { id: 'cards', name: 'Tarjetas', icon: '▢' },
                    { id: 'carousel', name: 'Carrusel', icon: '↔' },
                    { id: 'quotes', name: 'Citas', icon: '❝' },
                    { id: 'minimal', name: 'Mínimo', icon: '―' }
                ],
                pricing: [
                    { id: 'columns', name: 'Columnas', icon: '▥' },
                    { id: 'cards', name: 'Tarjetas', icon: '▢' },
                    { id: 'toggle', name: 'Toggle', icon: '⇄' }
                ],
                cta: [
                    { id: 'centered', name: 'Centrado', icon: '⊡' },
                    { id: 'split', name: 'Dividido', icon: '⊞' },
                    { id: 'banner', name: 'Banner', icon: '▭' }
                ],
                faq: [
                    { id: 'simple', name: 'Simple', icon: '≡' },
                    { id: 'accordion', name: 'Acordeón', icon: '▼' },
                    { id: 'tabs', name: 'Pestañas', icon: '⊟' }
                ],
                contact: [
                    { id: 'simple', name: 'Simple', icon: '▢' },
                    { id: 'split', name: 'Dividido', icon: '⊞' },
                    { id: 'minimal', name: 'Mínimo', icon: '―' }
                ],
                team: [
                    { id: 'grid', name: 'Grid', icon: '⊞' },
                    { id: 'cards', name: 'Tarjetas', icon: '▢' },
                    { id: 'list', name: 'Lista', icon: '≡' }
                ],
                button: [
                    { id: 'filled', name: 'Relleno', icon: '▮' },
                    { id: 'outline', name: 'Contorno', icon: '▯' },
                    { id: 'ghost', name: 'Ghost', icon: '◇' },
                    { id: 'link', name: 'Enlace', icon: '―' }
                ],
                divider: [
                    { id: 'solid', name: 'Sólido', icon: '―' },
                    { id: 'dashed', name: 'Guiones', icon: '- -' },
                    { id: 'dotted', name: 'Puntos', icon: '···' },
                    { id: 'gradient', name: 'Gradiente', icon: '▬' }
                ],
                'icon-box': [
                    { id: 'vertical', name: 'Vertical', icon: '⊡' },
                    { id: 'horizontal', name: 'Horizontal', icon: '⊟' },
                    { id: 'left', name: 'Izquierda', icon: '◀' }
                ],
                accordion: [
                    { id: 'simple', name: 'Simple', icon: '≡' },
                    { id: 'bordered', name: 'Bordeado', icon: '▢' },
                    { id: 'filled', name: 'Relleno', icon: '▮' }
                ],
                tabs: [
                    { id: 'horizontal', name: 'Horizontal', icon: '⊟' },
                    { id: 'vertical', name: 'Vertical', icon: '⊡' },
                    { id: 'pills', name: 'Pills', icon: '◯' }
                ],
                alert: [
                    { id: 'info', name: 'Info', icon: 'ℹ' },
                    { id: 'success', name: 'Éxito', icon: '✓' },
                    { id: 'warning', name: 'Aviso', icon: '⚠' },
                    { id: 'error', name: 'Error', icon: '✗' }
                ],
                newsletter: [
                    { id: 'inline', name: 'En línea', icon: '⊟' },
                    { id: 'stacked', name: 'Apilado', icon: '⊡' },
                    { id: 'minimal', name: 'Mínimo', icon: '―' }
                ]
            };
            return variants[type] || [];
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
            var result = JSON.parse(JSON.stringify(base));

            for (var key in overrides) {
                if (overrides.hasOwnProperty(key)) {
                    if (typeof overrides[key] === 'object' && overrides[key] !== null && !Array.isArray(overrides[key])) {
                        result[key] = this.mergeStyles(result[key] || {}, overrides[key]);
                    } else if (overrides[key] !== '' && overrides[key] !== null && overrides[key] !== undefined) {
                        result[key] = overrides[key];
                    }
                }
            }

            return result;
        },

        /**
         * Establecer valor en objeto anidado usando path tipo 'spacing.padding.top'
         */
        setNestedValue(obj, path, value) {
            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }

            current[parts[parts.length - 1]] = value;
        },

        /**
         * Obtener valor de objeto anidado
         */
        getNestedValue(obj, path) {
            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length; i++) {
                if (!current || !current.hasOwnProperty(parts[i])) {
                    return undefined;
                }
                current = current[parts[i]];
            }

            return current;
        },

        /**
         * Verificar si un elemento tiene overrides para un breakpoint
         */
        hasBreakpointOverrides(elementId, breakpoint) {
            var element = this.getElement(elementId);
            if (!element || breakpoint === 'desktop') return false;

            var responsiveStyles = element.responsiveStyles || {};
            return responsiveStyles[breakpoint] && Object.keys(responsiveStyles[breakpoint]).length > 0;
        }
    });
});
