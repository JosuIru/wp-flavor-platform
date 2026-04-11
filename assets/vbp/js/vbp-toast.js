/**
 * Visual Builder Pro - Sistema de Notificaciones Toast
 * Feedback visual para acciones del usuario
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

document.addEventListener('alpine:init', function() {
    Alpine.store('vbpToast', {
        notifications: [],
        maxNotifications: 5,
        defaultDuration: 3000,

        /**
         * Mostrar notificación
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - Tipo: success, error, warning, info
         * @param {object} options - Opciones adicionales
         */
        show: function(message, type, options) {
            type = type || 'info';
            options = options || {};

            var notification = {
                id: 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                message: message,
                type: type,
                icon: options.icon || this.getDefaultIcon(type),
                duration: options.duration || this.defaultDuration,
                action: options.action || null,
                actionLabel: options.actionLabel || null,
                dismissible: options.dismissible !== false,
                timestamp: Date.now()
            };

            // Agregar al inicio
            this.notifications.unshift(notification);

            // Limitar cantidad
            if (this.notifications.length > this.maxNotifications) {
                this.notifications = this.notifications.slice(0, this.maxNotifications);
            }

            // Auto-dismiss
            if (notification.duration > 0) {
                var self = this;
                setTimeout(function() {
                    self.dismiss(notification.id);
                }, notification.duration);
            }

            return notification.id;
        },

        /**
         * Cerrar notificación
         */
        dismiss: function(id) {
            var index = this.notifications.findIndex(function(n) { return n.id === id; });
            if (index !== -1) {
                this.notifications.splice(index, 1);
            }
        },

        /**
         * Cerrar todas las notificaciones
         */
        dismissAll: function() {
            this.notifications = [];
        },

        /**
         * Obtener icono por defecto según tipo
         */
        getDefaultIcon: function(type) {
            var icons = {
                'success': '✓',
                'error': '✕',
                'warning': '⚠',
                'info': 'ℹ'
            };
            return icons[type] || icons['info'];
        },

        /**
         * Atajos para tipos comunes
         */
        success: function(message, options) {
            return this.show(message, 'success', options);
        },

        error: function(message, options) {
            return this.show(message, 'error', options);
        },

        warning: function(message, options) {
            return this.show(message, 'warning', options);
        },

        info: function(message, options) {
            return this.show(message, 'info', options);
        },

        /**
         * Notificación con acción de deshacer
         */
        withUndo: function(message, undoCallback, options) {
            options = options || {};
            options.action = undoCallback;
            options.actionLabel = options.actionLabel || (typeof window.__ === 'function' ? __('undoAction', 'Deshacer') : 'Deshacer');
            options.duration = options.duration || 5000;
            return this.show(message, 'info', options);
        }
    });
});

/**
 * Componente Alpine para el contenedor de toasts
 */
function vbpToastContainer() {
    return {
        get notifications() {
            return Alpine.store('vbpToast').notifications;
        },

        dismiss: function(id) {
            Alpine.store('vbpToast').dismiss(id);
        },

        executeAction: function(notification) {
            if (notification.action && typeof notification.action === 'function') {
                notification.action();
            }
            this.dismiss(notification.id);
        },

        getTypeClass: function(type) {
            return 'vbp-toast--' + type;
        }
    };
}

window.vbpToastContainer = vbpToastContainer;

// Escuchar eventos de notificación globales
document.addEventListener('vbp:notification', function(e) {
    var detail = e.detail || {};
    Alpine.store('vbpToast').show(detail.message, detail.type, detail);
});

// Integrar con el sistema existente de notificaciones
document.addEventListener('vbp:toast', function(e) {
    var detail = e.detail || {};
    Alpine.store('vbpToast').show(detail.message, detail.type, detail);
});

// Exponer API global para uso fuera de Alpine
window.VBPToast = {
    show: function(message, type, options) {
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpToast')) {
            return Alpine.store('vbpToast').show(message, type, options);
        }
        // Fallback
        console.log('[VBP Toast]', type || 'info', ':', message);
    },
    success: function(message, options) {
        return this.show(message, 'success', options);
    },
    error: function(message, options) {
        return this.show(message, 'error', options);
    },
    warning: function(message, options) {
        return this.show(message, 'warning', options);
    },
    info: function(message, options) {
        return this.show(message, 'info', options);
    }
};
