/**
 * Flavor Chat IA - Sistema de Undo/Redo
 *
 * Gestiona el historial de estados para permitir deshacer y rehacer acciones.
 *
 * @package Flavor_Chat_IA
 * @since 1.0.0
 */

(function(window, document) {
    'use strict';

    /**
     * Clase UndoManager
     * Gestiona el historial de estados con soporte para undo/redo
     */
    class UndoManager {
        /**
         * Constructor
         * @param {Object} options - Opciones de configuración
         * @param {number} options.maxStates - Límite máximo de estados (default: 50)
         * @param {string} options.storageKey - Clave para localStorage
         * @param {boolean} options.persistSession - Persistir en localStorage
         */
        constructor(options = {}) {
            this.maxStates = options.maxStates || 50;
            this.storageKey = options.storageKey || 'flavor_undo_history';
            this.persistSession = options.persistSession !== false;

            this.historyStack = [];
            this.currentIndex = -1;
            this.isPerformingUndoRedo = false;

            this.initFromStorage();
            this.bindKeyboardShortcuts();
            this.createUndoRedoButtons();
        }

        /**
         * Inicializa el historial desde localStorage
         */
        initFromStorage() {
            if (!this.persistSession) {
                return;
            }

            try {
                const storedData = localStorage.getItem(this.storageKey);
                if (storedData) {
                    const parsedData = JSON.parse(storedData);
                    this.historyStack = parsedData.historyStack || [];
                    this.currentIndex = parsedData.currentIndex || -1;

                    // Validar que el índice esté dentro del rango
                    if (this.currentIndex >= this.historyStack.length) {
                        this.currentIndex = this.historyStack.length - 1;
                    }
                }
            } catch (error) {
                console.warn('Flavor UndoManager: Error al cargar historial desde localStorage', error);
                this.historyStack = [];
                this.currentIndex = -1;
            }
        }

        /**
         * Guarda el historial en localStorage
         */
        saveToStorage() {
            if (!this.persistSession) {
                return;
            }

            try {
                const dataToStore = {
                    historyStack: this.historyStack,
                    currentIndex: this.currentIndex,
                    timestamp: Date.now()
                };
                localStorage.setItem(this.storageKey, JSON.stringify(dataToStore));
            } catch (error) {
                console.warn('Flavor UndoManager: Error al guardar en localStorage', error);
            }
        }

        /**
         * Añade un nuevo estado al historial
         * @param {Object} state - Estado a guardar
         * @param {string} state.type - Tipo de acción
         * @param {*} state.data - Datos del estado
         * @param {string} state.description - Descripción de la acción
         * @returns {boolean} - True si se añadió correctamente
         */
        pushState(state) {
            if (this.isPerformingUndoRedo) {
                return false;
            }

            if (!state || typeof state !== 'object') {
                console.warn('Flavor UndoManager: Estado inválido');
                return false;
            }

            // Si estamos en medio del historial, eliminar estados posteriores
            if (this.currentIndex < this.historyStack.length - 1) {
                this.historyStack = this.historyStack.slice(0, this.currentIndex + 1);
            }

            // Crear entrada de historial con metadatos
            const historyEntry = {
                id: this.generateStateId(),
                timestamp: Date.now(),
                type: state.type || 'unknown',
                description: state.description || '',
                data: this.cloneState(state.data)
            };

            this.historyStack.push(historyEntry);
            this.currentIndex = this.historyStack.length - 1;

            // Aplicar límite de estados
            if (this.historyStack.length > this.maxStates) {
                const excesoDeEstados = this.historyStack.length - this.maxStates;
                this.historyStack = this.historyStack.slice(excesoDeEstados);
                this.currentIndex -= excesoDeEstados;
            }

            this.saveToStorage();
            this.updateButtonStates();
            this.dispatchEvent('flavor:state-pushed', historyEntry);

            return true;
        }

        /**
         * Deshace la última acción
         * @returns {Object|null} - Estado anterior o null si no hay más estados
         */
        undo() {
            if (!this.canUndo()) {
                this.showToast('No hay acciones para deshacer', 'info');
                return null;
            }

            this.isPerformingUndoRedo = true;

            const estadoActual = this.historyStack[this.currentIndex];
            this.currentIndex--;
            const estadoAnterior = this.currentIndex >= 0 ? this.historyStack[this.currentIndex] : null;

            this.saveToStorage();
            this.updateButtonStates();

            const eventData = {
                previousState: estadoActual,
                currentState: estadoAnterior,
                currentIndex: this.currentIndex,
                totalStates: this.historyStack.length
            };

            this.dispatchEvent('flavor:undo', eventData);

            const descripcionAccion = estadoActual.description || 'Acción';
            this.showToast(`Deshecho: ${descripcionAccion}`, 'success');

            this.isPerformingUndoRedo = false;

            return estadoAnterior;
        }

        /**
         * Rehace la última acción deshecha
         * @returns {Object|null} - Estado siguiente o null si no hay más estados
         */
        redo() {
            if (!this.canRedo()) {
                this.showToast('No hay acciones para rehacer', 'info');
                return null;
            }

            this.isPerformingUndoRedo = true;

            this.currentIndex++;
            const estadoActual = this.historyStack[this.currentIndex];

            this.saveToStorage();
            this.updateButtonStates();

            const eventData = {
                currentState: estadoActual,
                currentIndex: this.currentIndex,
                totalStates: this.historyStack.length
            };

            this.dispatchEvent('flavor:redo', eventData);

            const descripcionAccion = estadoActual.description || 'Acción';
            this.showToast(`Rehecho: ${descripcionAccion}`, 'success');

            this.isPerformingUndoRedo = false;

            return estadoActual;
        }

        /**
         * Verifica si se puede deshacer
         * @returns {boolean}
         */
        canUndo() {
            return this.currentIndex > 0;
        }

        /**
         * Verifica si se puede rehacer
         * @returns {boolean}
         */
        canRedo() {
            return this.currentIndex < this.historyStack.length - 1;
        }

        /**
         * Obtiene el estado actual
         * @returns {Object|null}
         */
        getCurrentState() {
            if (this.currentIndex >= 0 && this.currentIndex < this.historyStack.length) {
                return this.cloneState(this.historyStack[this.currentIndex]);
            }
            return null;
        }

        /**
         * Obtiene información del historial
         * @returns {Object}
         */
        getHistoryInfo() {
            return {
                totalStates: this.historyStack.length,
                currentIndex: this.currentIndex,
                canUndo: this.canUndo(),
                canRedo: this.canRedo(),
                undoCount: this.currentIndex,
                redoCount: this.historyStack.length - this.currentIndex - 1
            };
        }

        /**
         * Limpia todo el historial
         */
        clearHistory() {
            this.historyStack = [];
            this.currentIndex = -1;
            this.saveToStorage();
            this.updateButtonStates();
            this.dispatchEvent('flavor:history-cleared', {});
            this.showToast('Historial limpiado', 'info');
        }

        /**
         * Genera un ID único para el estado
         * @returns {string}
         */
        generateStateId() {
            return 'state_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        /**
         * Clona un estado de forma profunda
         * @param {*} state - Estado a clonar
         * @returns {*}
         */
        cloneState(state) {
            if (state === null || state === undefined) {
                return state;
            }

            try {
                return JSON.parse(JSON.stringify(state));
            } catch (error) {
                console.warn('Flavor UndoManager: Error al clonar estado', error);
                return state;
            }
        }

        /**
         * Configura los atajos de teclado
         */
        bindKeyboardShortcuts() {
            document.addEventListener('keydown', (event) => {
                // Ignorar si está en un input o textarea (excepto si es un editor)
                const targetTagName = event.target.tagName.toLowerCase();
                const esEditorFlavor = event.target.closest('.flavor-editor, .flavor-builder');

                if ((targetTagName === 'input' || targetTagName === 'textarea') && !esEditorFlavor) {
                    return;
                }

                const esCtrlOCmd = event.ctrlKey || event.metaKey;

                // Ctrl+Z o Cmd+Z para Undo
                if (esCtrlOCmd && event.key === 'z' && !event.shiftKey) {
                    event.preventDefault();
                    this.undo();
                    return;
                }

                // Ctrl+Y o Ctrl+Shift+Z o Cmd+Shift+Z para Redo
                if (esCtrlOCmd && (event.key === 'y' || (event.key === 'z' && event.shiftKey))) {
                    event.preventDefault();
                    this.redo();
                    return;
                }
            });
        }

        /**
         * Crea los botones de undo/redo en la toolbar
         */
        createUndoRedoButtons() {
            // Buscar toolbars existentes
            const toolbarSelectors = [
                '.flavor-toolbar',
                '.flavor-editor-toolbar',
                '.flavor-builder-toolbar',
                '#flavor-undo-redo-container'
            ];

            let toolbarContainer = null;
            for (const selector of toolbarSelectors) {
                toolbarContainer = document.querySelector(selector);
                if (toolbarContainer) break;
            }

            // Si no existe toolbar, crear contenedor flotante
            if (!toolbarContainer) {
                toolbarContainer = document.createElement('div');
                toolbarContainer.id = 'flavor-undo-redo-floating';
                toolbarContainer.className = 'flavor-undo-redo-floating';
                document.body.appendChild(toolbarContainer);
            }

            // Verificar si ya existen los botones
            if (toolbarContainer.querySelector('.flavor-undo-btn')) {
                return;
            }

            // Crear wrapper para los botones
            const buttonWrapper = document.createElement('div');
            buttonWrapper.className = 'flavor-undo-redo-buttons';

            // Botón Undo
            const undoButton = document.createElement('button');
            undoButton.type = 'button';
            undoButton.className = 'flavor-undo-btn flavor-undo-redo-btn';
            undoButton.title = 'Deshacer (Ctrl+Z)';
            undoButton.disabled = !this.canUndo();
            undoButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7v6h6"></path>
                    <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path>
                </svg>
                <span class="flavor-btn-text">Deshacer</span>
            `;
            undoButton.addEventListener('click', () => this.undo());

            // Botón Redo
            const redoButton = document.createElement('button');
            redoButton.type = 'button';
            redoButton.className = 'flavor-redo-btn flavor-undo-redo-btn';
            redoButton.title = 'Rehacer (Ctrl+Y)';
            redoButton.disabled = !this.canRedo();
            redoButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 7v6h-6"></path>
                    <path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"></path>
                </svg>
                <span class="flavor-btn-text">Rehacer</span>
            `;
            redoButton.addEventListener('click', () => this.redo());

            buttonWrapper.appendChild(undoButton);
            buttonWrapper.appendChild(redoButton);
            toolbarContainer.appendChild(buttonWrapper);

            // Guardar referencias
            this.undoButton = undoButton;
            this.redoButton = redoButton;
        }

        /**
         * Actualiza el estado de los botones
         */
        updateButtonStates() {
            if (this.undoButton) {
                this.undoButton.disabled = !this.canUndo();
                this.undoButton.classList.toggle('flavor-btn-disabled', !this.canUndo());
            }

            if (this.redoButton) {
                this.redoButton.disabled = !this.canRedo();
                this.redoButton.classList.toggle('flavor-btn-disabled', !this.canRedo());
            }
        }

        /**
         * Muestra un toast de notificación
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - Tipo: 'success', 'error', 'info', 'warning'
         */
        showToast(message, type = 'info') {
            // Buscar o crear contenedor de toasts
            let toastContainer = document.getElementById('flavor-toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'flavor-toast-container';
                toastContainer.className = 'flavor-toast-container';
                document.body.appendChild(toastContainer);
            }

            // Crear toast
            const toast = document.createElement('div');
            toast.className = `flavor-toast flavor-toast-${type}`;

            const iconosToast = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                error: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
                info: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
            };

            toast.innerHTML = `
                <span class="flavor-toast-icon">${iconosToast[type] || iconosToast.info}</span>
                <span class="flavor-toast-message">${message}</span>
                <button type="button" class="flavor-toast-close" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            `;

            // Botón cerrar
            const closeButton = toast.querySelector('.flavor-toast-close');
            closeButton.addEventListener('click', () => this.removeToast(toast));

            // Añadir al contenedor
            toastContainer.appendChild(toast);

            // Animar entrada
            requestAnimationFrame(() => {
                toast.classList.add('flavor-toast-visible');
            });

            // Auto-cerrar después de 3 segundos
            setTimeout(() => {
                this.removeToast(toast);
            }, 3000);
        }

        /**
         * Elimina un toast
         * @param {HTMLElement} toast - Elemento toast a eliminar
         */
        removeToast(toast) {
            if (!toast || !toast.parentNode) return;

            toast.classList.remove('flavor-toast-visible');
            toast.classList.add('flavor-toast-hiding');

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        /**
         * Dispara un evento personalizado
         * @param {string} eventName - Nombre del evento
         * @param {Object} detail - Datos del evento
         */
        dispatchEvent(eventName, detail) {
            const event = new CustomEvent(eventName, {
                detail: detail,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        }

        /**
         * Suscribe un callback a un evento
         * @param {string} eventName - Nombre del evento
         * @param {Function} callback - Función callback
         */
        on(eventName, callback) {
            document.addEventListener(eventName, callback);
        }

        /**
         * Desuscribe un callback de un evento
         * @param {string} eventName - Nombre del evento
         * @param {Function} callback - Función callback
         */
        off(eventName, callback) {
            document.removeEventListener(eventName, callback);
        }

        /**
         * Destruye la instancia y limpia recursos
         */
        destroy() {
            // Eliminar botones
            const floatingContainer = document.getElementById('flavor-undo-redo-floating');
            if (floatingContainer) {
                floatingContainer.remove();
            }

            // Eliminar toast container
            const toastContainer = document.getElementById('flavor-toast-container');
            if (toastContainer) {
                toastContainer.remove();
            }

            // Limpiar storage si se desea
            if (this.persistSession) {
                localStorage.removeItem(this.storageKey);
            }
        }
    }

    // Crear instancia global
    const flavorUndoManager = new UndoManager();

    // Exponer API global
    window.FlavorUndo = {
        // Instancia del manager
        manager: flavorUndoManager,

        // Métodos principales
        pushState: (state) => flavorUndoManager.pushState(state),
        undo: () => flavorUndoManager.undo(),
        redo: () => flavorUndoManager.redo(),
        canUndo: () => flavorUndoManager.canUndo(),
        canRedo: () => flavorUndoManager.canRedo(),

        // Métodos adicionales
        getCurrentState: () => flavorUndoManager.getCurrentState(),
        getHistoryInfo: () => flavorUndoManager.getHistoryInfo(),
        clearHistory: () => flavorUndoManager.clearHistory(),

        // Eventos
        on: (eventName, callback) => flavorUndoManager.on(eventName, callback),
        off: (eventName, callback) => flavorUndoManager.off(eventName, callback),

        // Crear nueva instancia con opciones personalizadas
        createInstance: (options) => new UndoManager(options),

        // Destruir instancia
        destroy: () => flavorUndoManager.destroy()
    };

    // Disparar evento de inicialización
    document.dispatchEvent(new CustomEvent('flavor:undo-ready', {
        detail: { manager: flavorUndoManager }
    }));

})(window, document);
