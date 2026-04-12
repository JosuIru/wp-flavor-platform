/**
 * Visual Builder Pro - App Module: Workflows
 * Sistema de flujos de aprobación
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

window.VBPAppWorkflows = {
    // Estado del workflow
    showWorkflowPanel: false,
    workflowStatus: null,
    workflowHistory: [],
    workflowLoading: false,
    workflowUsers: [],
    pendingReviews: [],
    pendingReviewsCount: 0,
    showScheduleModal: false,
    scheduledDate: '',
    transitionComment: '',

    // Control de inicialización
    _initialized: false,

    // ============ HELPERS ============

    /**
     * Obtiene el ID del post actual
     */
    getPostId: function() {
        return (typeof VBP_Config !== 'undefined' && VBP_Config.postId) ? VBP_Config.postId : null;
    },

    /**
     * Muestra una notificación
     */
    showNotification: function(message, type) {
        if (typeof VBPAppToast !== 'undefined' && VBPAppToast.show) {
            VBPAppToast.show(message, type);
        } else if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            vbpLog.log('[VBP Workflows] ' + type + ': ' + message);
        }
    },

    /**
     * Sanitiza un parámetro para URL
     */
    sanitizeUrlParam: function(param) {
        if (typeof param !== 'string') {
            return '';
        }
        return encodeURIComponent(param.replace(/[^a-zA-Z0-9_-]/g, ''));
    },

    // ============ CARGA DE DATOS ============

    /**
     * Cargar estado del workflow
     */
    loadWorkflowStatus: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        this.workflowLoading = true;

        fetch(VBP_Config.restUrl + 'workflow/' + postId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            self.workflowLoading = false;
            if (data.success) {
                self.workflowStatus = data;
            }
        })
        .catch(function(error) {
            self.workflowLoading = false;
            vbpLog.error(' Error cargando workflow:', error);
        });
    },

    /**
     * Cargar historial del workflow
     */
    loadWorkflowHistory: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        fetch(VBP_Config.restUrl + 'workflow/' + postId + '/history', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.workflowHistory = data.history || [];
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando historial:', error);
        });
    },

    /**
     * Cargar usuarios para asignación
     */
    loadWorkflowUsers: function(role) {
        var self = this;
        var roleParam = this.sanitizeUrlParam(role || 'reviewers');

        fetch(VBP_Config.restUrl + 'workflow/users?role=' + roleParam, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.workflowUsers = data.users || [];
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando usuarios:', error);
        });
    },

    /**
     * Cargar posts pendientes de revisión
     */
    loadPendingReviews: function() {
        var self = this;

        fetch(VBP_Config.restUrl + 'workflow/pending', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.pendingReviews = data.posts || [];
                self.pendingReviewsCount = data.total || 0;
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando pendientes:', error);
        });
    },

    // ============ TRANSICIONES ============

    /**
     * Ejecutar transición de workflow
     */
    executeTransition: function(action) {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        // Si es programación, mostrar modal
        if (action === 'schedule') {
            this.showScheduleModal = true;
            return;
        }

        // Si requiere comentario, solicitarlo
        if (action === 'request_changes' && !this.transitionComment) {
            var commentInput = prompt('Describe los cambios necesarios:');
            if (commentInput === null) return; // Cancelado
            this.transitionComment = commentInput;
        }

        this.workflowLoading = true;

        var requestBody = {
            action: action,
            comment: this.transitionComment
        };

        if (action === 'schedule' && this.scheduledDate) {
            requestBody.scheduled_date = this.scheduledDate;
        }

        fetch(VBP_Config.restUrl + 'workflow/' + postId + '/transition', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify(requestBody)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            self.workflowLoading = false;
            self.transitionComment = '';
            self.scheduledDate = '';
            self.showScheduleModal = false;

            if (data.success) {
                self.showNotification(data.message, 'success');
                self.loadWorkflowStatus();
                self.loadWorkflowHistory();
            } else {
                self.showNotification(data.message || 'Error en la transición', 'error');
            }
        })
        .catch(function(error) {
            self.workflowLoading = false;
            vbpLog.error(' Error en transición:', error);
            self.showNotification('Error al cambiar estado', 'error');
        });
    },

    /**
     * Confirmar programación
     */
    confirmSchedule: function() {
        if (!this.scheduledDate) {
            this.showNotification('Selecciona una fecha', 'warning');
            return;
        }

        this.executeTransition('schedule');
    },

    /**
     * Cancelar programación
     */
    cancelSchedule: function() {
        this.showScheduleModal = false;
        this.scheduledDate = '';
    },

    // ============ REVISORES ============

    /**
     * Asignar revisores
     */
    assignReviewers: function(reviewerIds) {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        fetch(VBP_Config.restUrl + 'workflow/' + postId + '/reviewers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({
                reviewers: reviewerIds
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Revisores asignados', 'success');
                self.loadWorkflowStatus();
            } else {
                self.showNotification(data.message || 'Error al asignar', 'error');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error asignando revisores:', error);
        });
    },

    // ============ UI ============

    /**
     * Abrir panel de workflow
     */
    openWorkflowPanel: function() {
        this.showWorkflowPanel = true;
        this.loadWorkflowStatus();
        this.loadWorkflowHistory();
        this.loadWorkflowUsers('reviewers');
    },

    /**
     * Cerrar panel de workflow
     */
    closeWorkflowPanel: function() {
        this.showWorkflowPanel = false;
    },

    /**
     * Obtener color del estado
     */
    getWorkflowStatusColor: function(status) {
        var colors = {
            'draft': '#6b7280',
            'pending_review': '#f59e0b',
            'changes_requested': '#ef4444',
            'approved': '#22c55e',
            'publish': '#3b82f6',
            'scheduled': '#8b5cf6'
        };
        return colors[status] || '#6b7280';
    },

    /**
     * Obtener icono del estado
     */
    getWorkflowStatusIcon: function(status) {
        var icons = {
            'draft': '📝',
            'pending_review': '👀',
            'changes_requested': '✏️',
            'approved': '✅',
            'publish': '🚀',
            'scheduled': '📅'
        };
        return icons[status] || '📄';
    },

    /**
     * Obtener icono de acción
     */
    getWorkflowActionIcon: function(action) {
        var icons = {
            'submit_review': '📤',
            'approve': '✅',
            'request_changes': '✏️',
            'publish': '🚀',
            'schedule': '📅',
            'unpublish': '📥',
            'revert_draft': '↩️'
        };
        return icons[action] || '📝';
    },

    /**
     * Formatear fecha para input datetime-local
     */
    formatDateForInput: function(date) {
        if (!date) {
            var now = new Date();
            now.setHours(now.getHours() + 1);
            date = now;
        }
        return date.toISOString().slice(0, 16);
    },

    /**
     * Inicializar workflow
     */
    initWorkflow: function() {
        // Evitar inicialización duplicada
        if (this._initialized) {
            vbpLog.log('VBP Workflows: ya inicializado, ignorando');
            return;
        }
        this._initialized = true;

        // Cargar estado inicial si estamos editando
        if (this.getPostId()) {
            this.loadWorkflowStatus();
            // Cargar pendientes si el usuario puede revisar
            if (VBP_Config.userCan && VBP_Config.userCan.edit_others_posts) {
                this.loadPendingReviews();
            }
        }

        vbpLog.info('VBP Workflows: Inicializado');
    },

    /**
     * Destruye el módulo y limpia recursos
     */
    destroy: function() {
        // Resetear estado
        this.showWorkflowPanel = false;
        this.workflowStatus = null;
        this.workflowHistory = [];
        this.workflowLoading = false;
        this.workflowUsers = [];
        this.pendingReviews = [];
        this.pendingReviewsCount = 0;
        this.showScheduleModal = false;
        this.scheduledDate = '';
        this.transitionComment = '';
        this._initialized = false;

        vbpLog.info('VBP Workflows: Destruido');
    }
};
