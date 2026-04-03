/**
 * Visual Builder Pro - Keyboard Module: Editors
 * Acciones de editores de propiedades avanzadas
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardEditors = {
    /**
     * Abrir editor de variables CSS
     */
    openCSSVariables: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'cssVariables' }
        }));
    },

    /**
     * Abrir comparador de versiones
     */
    openVersionCompare: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'versionCompare' }
        }));
    },

    /**
     * Abrir editor de sombras
     */
    openShadowEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'shadowEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de gradientes
     */
    openGradientEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'gradientEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de animaciones
     */
    openAnimationEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'animationEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de tipografía
     */
    openTypographyEditor: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'typographyEditor' }
        }));
    },

    /**
     * Abrir editor de bordes
     */
    openBorderEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'borderEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de espaciado
     */
    openSpacingEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'spacingEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de estados hover
     */
    openHoverStatesEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'hoverStatesEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir editor de animaciones de scroll
     */
    openScrollAnimationEditor: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona un elemento primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'scrollAnimationEditor', elementId: store.selection.elementIds[0] }
        }));
    },

    /**
     * Abrir biblioteca de templates
     */
    openTemplatesLibrary: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'templatesLibrary' }
        }));
    },

    /**
     * Guardar selección como componente
     */
    saveAsComponent: function() {
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 0) {
            this.showNotification('Selecciona elementos primero', 'warning');
            return;
        }
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: {
                modal: 'saveComponent',
                elementIds: store.selection.elementIds
            }
        }));
    },

    /**
     * Abrir biblioteca de componentes
     */
    openComponentsLibrary: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'componentsLibrary' }
        }));
    },

    /**
     * Abrir editor de design tokens
     */
    openDesignTokens: function() {
        document.dispatchEvent(new CustomEvent('vbp:openModal', {
            detail: { modal: 'designTokens' }
        }));
    },

    /**
     * Mostrar notificación
     */
    showNotification: function(message, type) {
        type = type || 'info';
        if (window.vbpKeyboard && window.vbpKeyboard.showNotification) {
            window.vbpKeyboard.showNotification(message, type);
        } else {
            vbpLog.log('', message);
        }
    }
};
