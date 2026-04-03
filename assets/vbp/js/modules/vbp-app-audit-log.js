/**
 * Visual Builder Pro - App Module: Audit Log
 * Sistema de registro de auditoría
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

window.VBPAppAuditLog = {
    // Estado del audit log
    showAuditPanel: false,
    auditLogs: [],
    auditStats: null,
    auditLoading: false,
    auditFilter: {
        actionType: '',
        dateFrom: '',
        dateTo: '',
        page: 1,
        perPage: 20
    },
    auditTotalPages: 1,

    // ============ CARGA DE DATOS ============

    /**
     * Cargar logs de auditoría
     */
    loadAuditLogs: function() {
        var self = this;
        var postId = this.getPostId();

        if (!postId) return;

        this.auditLoading = true;

        var params = new URLSearchParams({
            post_id: postId,
            page: this.auditFilter.page,
            per_page: this.auditFilter.perPage
        });

        if (this.auditFilter.actionType) {
            params.append('action_type', this.auditFilter.actionType);
        }
        if (this.auditFilter.dateFrom) {
            params.append('date_from', this.auditFilter.dateFrom);
        }
        if (this.auditFilter.dateTo) {
            params.append('date_to', this.auditFilter.dateTo);
        }

        fetch(VBP_Config.restUrl + 'audit-log?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            self.auditLoading = false;
            if (data.success) {
                self.auditLogs = data.logs || [];
                self.auditTotalPages = data.pages || 1;
            }
        })
        .catch(function(error) {
            self.auditLoading = false;
            vbpLog.error(' Error cargando audit logs:', error);
        });
    },

    /**
     * Cargar estadísticas de auditoría
     */
    loadAuditStats: function() {
        var self = this;

        fetch(VBP_Config.restUrl + 'audit-log/stats', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.auditStats = data;
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cargando stats:', error);
        });
    },

    // ============ NAVEGACIÓN ============

    /**
     * Ir a página de logs
     */
    goToAuditPage: function(page) {
        this.auditFilter.page = page;
        this.loadAuditLogs();
    },

    /**
     * Página anterior
     */
    auditPrevPage: function() {
        if (this.auditFilter.page > 1) {
            this.goToAuditPage(this.auditFilter.page - 1);
        }
    },

    /**
     * Página siguiente
     */
    auditNextPage: function() {
        if (this.auditFilter.page < this.auditTotalPages) {
            this.goToAuditPage(this.auditFilter.page + 1);
        }
    },

    /**
     * Aplicar filtros
     */
    applyAuditFilters: function() {
        this.auditFilter.page = 1;
        this.loadAuditLogs();
    },

    /**
     * Limpiar filtros
     */
    clearAuditFilters: function() {
        this.auditFilter = {
            actionType: '',
            dateFrom: '',
            dateTo: '',
            page: 1,
            perPage: 20
        };
        this.loadAuditLogs();
    },

    // ============ UI ============

    /**
     * Abrir panel de auditoría
     */
    openAuditPanel: function() {
        this.showAuditPanel = true;
        this.loadAuditLogs();
        this.loadAuditStats();
    },

    /**
     * Cerrar panel de auditoría
     */
    closeAuditPanel: function() {
        this.showAuditPanel = false;
    },

    /**
     * Obtener icono según tipo de acción
     */
    getAuditActionIcon: function(actionType) {
        var icons = {
            'page_created': '📄',
            'page_updated': '✏️',
            'page_published': '🚀',
            'page_unpublished': '📥',
            'page_deleted': '🗑️',
            'page_trashed': '🗑️',
            'page_restored': '♻️',
            'revision_created': '📋',
            'revision_restored': '⏪',
            'element_added': '➕',
            'element_updated': '🔄',
            'element_deleted': '➖',
            'element_moved': '↕️',
            'element_duplicated': '📑',
            'style_changed': '🎨',
            'settings_changed': '⚙️',
            'template_applied': '📐',
            'template_saved': '💾',
            'export_created': '📤',
            'import_completed': '📥',
            'collaboration_joined': '👋',
            'collaboration_left': '👋',
            'comment_added': '💬',
            'comment_resolved': '✅',
            'ab_test_created': '🧪',
            'ab_test_ended': '📊',
            'popup_created': '🪟',
            'popup_activated': '▶️'
        };
        return icons[actionType] || '📝';
    },

    /**
     * Obtener color según tipo de acción
     */
    getAuditActionColor: function(actionType) {
        var colors = {
            'page_created': '#22c55e',
            'page_updated': '#3b82f6',
            'page_published': '#8b5cf6',
            'page_unpublished': '#f59e0b',
            'page_deleted': '#ef4444',
            'page_trashed': '#ef4444',
            'page_restored': '#22c55e',
            'revision_restored': '#3b82f6',
            'element_added': '#22c55e',
            'element_deleted': '#ef4444',
            'collaboration_joined': '#22c55e',
            'collaboration_left': '#f59e0b',
            'comment_added': '#3b82f6',
            'comment_resolved': '#22c55e'
        };
        return colors[actionType] || '#6b7280';
    },

    /**
     * Exportar logs como CSV
     */
    exportAuditLogs: function() {
        var self = this;

        fetch(VBP_Config.restUrl + 'audit-log/export', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                // Convertir a CSV
                var csvContent = data.data.map(function(row) {
                    return row.map(function(cell) {
                        // Escapar comillas dobles y envolver en comillas
                        var escaped = String(cell || '').replace(/"/g, '""');
                        return '"' + escaped + '"';
                    }).join(',');
                }).join('\n');

                // Descargar
                var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', data.filename || 'audit-log.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                self.showNotification('Audit log exportado', 'success');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error exportando audit log:', error);
            self.showNotification('Error al exportar', 'error');
        });
    },

    // ============ TIPOS DE ACCIÓN ============

    /**
     * Obtener lista de tipos de acción para filtro
     */
    getAuditActionTypes: function() {
        return [
            { value: 'page_created', label: 'Página creada' },
            { value: 'page_updated', label: 'Página actualizada' },
            { value: 'page_published', label: 'Página publicada' },
            { value: 'page_unpublished', label: 'Página despublicada' },
            { value: 'page_deleted', label: 'Página eliminada' },
            { value: 'page_trashed', label: 'Movida a papelera' },
            { value: 'page_restored', label: 'Página restaurada' },
            { value: 'revision_restored', label: 'Revisión restaurada' },
            { value: 'element_added', label: 'Elemento añadido' },
            { value: 'element_updated', label: 'Elemento modificado' },
            { value: 'element_deleted', label: 'Elemento eliminado' },
            { value: 'template_applied', label: 'Plantilla aplicada' },
            { value: 'comment_added', label: 'Comentario añadido' },
            { value: 'comment_resolved', label: 'Comentario resuelto' }
        ];
    }
};
