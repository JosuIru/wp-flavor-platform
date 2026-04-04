/**
 * Visual Builder Pro - Utilidades del Inspector
 *
 * Funcionalidades adicionales: copiar/pegar estilos, secciones colapsables
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Store para el portapapeles de estilos
 */
document.addEventListener('alpine:init', function() {
    Alpine.store('vbpClipboard', {
        copiedStyles: null,
        copiedData: null,

        /**
         * Copiar estilos del elemento seleccionado
         */
        copyStyles: function() {
            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            this.copiedStyles = JSON.parse(JSON.stringify(element.styles || {}));
            this.showNotification('Estilos copiados', 'success');
        },

        /**
         * Pegar estilos al elemento seleccionado
         */
        pasteStyles: function() {
            if (!this.copiedStyles) {
                this.showNotification('No hay estilos copiados', 'warning');
                return;
            }

            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            // Aplicar estilos copiados
            element.styles = Object.assign({}, element.styles || {}, this.copiedStyles);
            store.markDirty();
            this.showNotification('Estilos pegados', 'success');
        },

        /**
         * Copiar todo el elemento (estilos + data)
         */
        copyElement: function() {
            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            this.copiedData = JSON.parse(JSON.stringify({
                type: element.type,
                data: element.data || {},
                styles: element.styles || {}
            }));
            this.showNotification('Elemento copiado', 'success');
        },

        /**
         * Pegar configuración al elemento seleccionado
         */
        pasteElement: function() {
            if (!this.copiedData) {
                this.showNotification('No hay elemento copiado', 'warning');
                return;
            }

            var store = Alpine.store('vbp');
            if (!store.selection.elementIds.length) {
                this.showNotification('Selecciona un elemento primero', 'warning');
                return;
            }

            var element = store.getElement(store.selection.elementIds[0]);
            if (!element) return;

            // Solo pegar si son del mismo tipo
            if (element.type !== this.copiedData.type) {
                this.showNotification('Los elementos deben ser del mismo tipo', 'warning');
                return;
            }

            element.data = Object.assign({}, element.data || {}, this.copiedData.data);
            element.styles = Object.assign({}, element.styles || {}, this.copiedData.styles);
            store.markDirty();
            this.showNotification('Configuración pegada', 'success');
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            type = type || 'info';
            // Crear notificación temporal
            var notification = document.createElement('div');
            notification.className = 'vbp-notification vbp-notification-' + type;
            notification.textContent = message;
            notification.style.cssText = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 10px 20px; background: ' + (type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6') + '; color: white; border-radius: 6px; font-size: 13px; font-weight: 500; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: vbp-slide-up 0.3s ease;';

            document.body.appendChild(notification);

            setTimeout(function() {
                notification.style.animation = 'vbp-slide-down 0.3s ease forwards';
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 2000);
        }
    });

    /**
     * Store para secciones colapsables
     */
    Alpine.store('vbpSections', {
        collapsed: {},

        init: function() {
            // Cargar estado guardado
            var saved = localStorage.getItem('vbp_collapsed_sections');
            if (saved) {
                try {
                    this.collapsed = JSON.parse(saved);
                } catch (e) {
                    this.collapsed = {};
                }
            }
        },

        isCollapsed: function(sectionId) {
            return !!this.collapsed[sectionId];
        },

        toggle: function(sectionId) {
            this.collapsed[sectionId] = !this.collapsed[sectionId];
            this.save();
        },

        expand: function(sectionId) {
            this.collapsed[sectionId] = false;
            this.save();
        },

        collapse: function(sectionId) {
            this.collapsed[sectionId] = true;
            this.save();
        },

        expandAll: function() {
            this.collapsed = {};
            this.save();
        },

        collapseAll: function() {
            // Colapsar todas las secciones conocidas
            var sections = document.querySelectorAll('[data-section-id]');
            var self = this;
            sections.forEach(function(section) {
                var id = section.getAttribute('data-section-id');
                self.collapsed[id] = true;
            });
            this.save();
        },

        save: function() {
            localStorage.setItem('vbp_collapsed_sections', JSON.stringify(this.collapsed));
        }
    });
});

/**
 * Componente Alpine para sección colapsable
 */
function vbpCollapsibleSection() {
    return {
        sectionId: '',

        init: function() {
            // Generar ID único si no existe
            if (!this.sectionId) {
                this.sectionId = 'section_' + Math.random().toString(36).substr(2, 9);
            }
        },

        get isCollapsed() {
            return Alpine.store('vbpSections').isCollapsed(this.sectionId);
        },

        toggle: function() {
            Alpine.store('vbpSections').toggle(this.sectionId);
        }
    };
}

/**
 * Atajos de teclado globales para el inspector
 */
document.addEventListener('keydown', function(event) {
    // Solo funciona si no estamos en un input
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.isContentEditable) {
        return;
    }

    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var modKey = isMac ? event.metaKey : event.ctrlKey;

    // Ctrl/Cmd + C: Copiar estilos
    if (modKey && event.shiftKey && event.key.toLowerCase() === 'c') {
        event.preventDefault();
        Alpine.store('vbpClipboard').copyStyles();
    }

    // Ctrl/Cmd + V: Pegar estilos
    if (modKey && event.shiftKey && event.key.toLowerCase() === 'v') {
        event.preventDefault();
        Alpine.store('vbpClipboard').pasteStyles();
    }

    // D: Duplicar elemento
    if (event.key.toLowerCase() === 'd' && modKey) {
        event.preventDefault();
        var store = Alpine.store('vbp');
        if (store.selection.elementIds.length === 1) {
            store.duplicateElement(store.selection.elementIds[0]);
        }
    }
});

// Agregar animaciones CSS
(function() {
    var style = document.createElement('style');
    style.textContent = '\
        @keyframes vbp-slide-up {\
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }\
            to { opacity: 1; transform: translateX(-50%) translateY(0); }\
        }\
        @keyframes vbp-slide-down {\
            from { opacity: 1; transform: translateX(-50%) translateY(0); }\
            to { opacity: 0; transform: translateX(-50%) translateY(20px); }\
        }\
    ';
    document.head.appendChild(style);
})();

// Registrar componentes globalmente (para compatibilidad)
window.vbpCollapsibleSection = vbpCollapsibleSection;

// Registrar con Alpine.data() para mejor timing
document.addEventListener('alpine:init', function() {
    Alpine.data('vbpCollapsibleSection', vbpCollapsibleSection);
});
