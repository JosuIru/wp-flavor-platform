/**
 * Visual Builder Pro - Store History Helpers
 *
 * Helpers puros para push/undo/redo del historial.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPStoreHistoryHelpers = {
    pushHistory: function(history, elements, maxHistorySize, description) {
        var entry = {
            state: JSON.stringify(elements),
            description: description || 'Cambio',
            timestamp: Date.now()
        };

        history.past.push(entry);
        history.future = [];

        if (history.past.length > maxHistorySize) {
            history.past.shift();
        }
    },

    undo: function(history, elements) {
        if (!history.past.length) return null;

        var lastEntry = history.past.pop();
        var currentEntry = {
            state: JSON.stringify(elements),
            description: lastEntry.description,
            timestamp: Date.now()
        };
        history.future.unshift(currentEntry);

        var stateData = typeof lastEntry === 'string' ? lastEntry : lastEntry.state;
        return {
            elements: JSON.parse(stateData),
            description: lastEntry.description || 'Cambio'
        };
    },

    redo: function(history, elements) {
        if (!history.future.length) return null;

        var nextEntry = history.future.shift();
        var currentEntry = {
            state: JSON.stringify(elements),
            description: nextEntry.description,
            timestamp: Date.now()
        };
        history.past.push(currentEntry);

        var stateData = typeof nextEntry === 'string' ? nextEntry : nextEntry.state;
        return {
            elements: JSON.parse(stateData),
            description: nextEntry.description || 'Cambio'
        };
    }
};
