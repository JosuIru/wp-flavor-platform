/**
 * Visual Builder Pro - History Manager
 * Gestión avanzada del historial de cambios
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.vbpHistory = {
    /**
     * Configuración
     */
    maxSnapshots: 50,
    saveTimeout: null,
    lastState: null,

    /**
     * Inicializar el gestor de historial
     */
    init: function() {
        var self = this;

        // Escuchar eventos de cambios
        document.addEventListener('vbp:elementChanged', function(e) {
            self.onElementChanged(e.detail);
        });

        document.addEventListener('vbp:beforeSave', function(e) {
            self.createCheckpoint('Antes de guardar');
        });

        // Capturar estado inicial
        document.addEventListener('alpine:initialized', function() {
            self.captureInitialState();
        });
    },

    /**
     * Capturar estado inicial
     */
    captureInitialState: function() {
        var store = Alpine.store('vbp');
        if (store) {
            this.lastState = JSON.stringify(store.elements);
        }
    },

    /**
     * Manejador cuando un elemento cambia
     */
    onElementChanged: function(detail) {
        // Debounce para no guardar demasiados estados
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        var self = this;
        this.saveTimeout = setTimeout(function() {
            self.checkForChanges();
        }, 500);
    },

    /**
     * Verificar si hubo cambios significativos
     */
    checkForChanges: function() {
        var store = Alpine.store('vbp');
        if (!store) return;

        var currentState = JSON.stringify(store.elements);

        if (this.lastState !== currentState) {
            this.lastState = currentState;
            // El store ya maneja saveToHistory(), solo marcamos dirty
            store.isDirty = true;
        }
    },

    /**
     * Crear checkpoint con nombre
     */
    createCheckpoint: function(nombre) {
        var store = Alpine.store('vbp');
        if (!store) return;

        // Guardar con metadatos
        var checkpoint = {
            state: JSON.stringify(store.elements),
            nombre: nombre,
            timestamp: new Date().toISOString()
        };

        // Almacenar en sessionStorage para recuperación
        var checkpoints = this.getCheckpoints();
        checkpoints.push(checkpoint);

        // Mantener solo los últimos N checkpoints
        if (checkpoints.length > 10) {
            checkpoints = checkpoints.slice(-10);
        }

        sessionStorage.setItem('vbp_checkpoints', JSON.stringify(checkpoints));
    },

    /**
     * Obtener checkpoints guardados
     */
    getCheckpoints: function() {
        try {
            var stored = sessionStorage.getItem('vbp_checkpoints');
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    },

    /**
     * Restaurar desde checkpoint
     */
    restoreCheckpoint: function(index) {
        var checkpoints = this.getCheckpoints();
        var checkpoint = checkpoints[index];

        if (checkpoint && checkpoint.state) {
            var store = Alpine.store('vbp');
            if (store) {
                store.saveToHistory();
                store.elements = JSON.parse(checkpoint.state);
                store.isDirty = true;
                return true;
            }
        }
        return false;
    },

    /**
     * Limpiar historial de sesión
     */
    clearSessionHistory: function() {
        sessionStorage.removeItem('vbp_checkpoints');
    },

    /**
     * Obtener estadísticas del historial
     */
    getStats: function() {
        var store = Alpine.store('vbp');
        if (!store) return null;

        return {
            undoSteps: store.history.past.length,
            redoSteps: store.history.future.length,
            maxSize: store.maxHistorySize,
            canUndo: store.canUndo,
            canRedo: store.canRedo,
            checkpoints: this.getCheckpoints().length
        };
    },

    /**
     * Exportar historial completo para debugging
     */
    exportHistory: function() {
        var store = Alpine.store('vbp');
        if (!store) return null;

        return {
            current: store.elements,
            past: store.history.past,
            future: store.history.future,
            checkpoints: this.getCheckpoints()
        };
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.vbpHistory.init();
});
