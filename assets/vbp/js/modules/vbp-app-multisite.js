/**
 * Visual Builder Pro - App Module: Multi-site
 * Gestión de templates y recursos compartidos en red multisite
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

window.VBPAppMultisite = {
    // Estado multisite
    isMultisite: false,
    isNetworkAdmin: false,
    currentSiteId: 0,
    currentSiteName: '',
    networkName: '',
    totalSites: 0,
    multisiteFeatures: {},

    // Modales
    showMultisitePanel: false,
    showShareTemplateModal: false,
    showImportTemplateModal: false,

    // Datos
    sharedTemplates: [],
    sharedWidgets: [],
    networkSites: [],
    networkTokens: {},
    isLoadingMultisite: false,

    // Template seleccionado para compartir
    shareTemplateData: {
        templateId: '',
        name: '',
        description: '',
        category: 'general'
    },

    // ============ INICIALIZACIÓN ============

    /**
     * Inicializar módulo multisite
     */
    initMultisite: function() {
        var self = this;

        // Cargar estado multisite
        this.loadMultisiteStatus().then(function() {
            if (self.isMultisite) {
                vbpLog.log(' Red multisite detectada:', self.networkName);

                // Cargar recursos compartidos si está habilitado
                if (self.multisiteFeatures.shared_templates) {
                    self.loadSharedTemplates();
                }
                if (self.multisiteFeatures.shared_widgets) {
                    self.loadSharedWidgets();
                }
            }
        });
    },

    /**
     * Cargar estado de multisite
     */
    loadMultisiteStatus: function() {
        var self = this;

        return fetch(VBP_Config.restUrl + 'multisite/status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Error al cargar estado multisite');
            }
            return response.json();
        })
        .then(function(data) {
            self.isMultisite = data.is_multisite;
            self.isNetworkAdmin = data.is_network_admin;
            self.currentSiteId = data.current_site_id;
            self.currentSiteName = data.current_site_name;
            self.networkName = data.network_name;
            self.totalSites = data.total_sites;
            self.multisiteFeatures = data.features_enabled || {};
        })
        .catch(function(error) {
            vbpLog.warn(' Error:', error.message);
            self.isMultisite = false;
        });
    },

    // ============ TEMPLATES COMPARTIDOS ============

    /**
     * Cargar templates compartidos
     */
    loadSharedTemplates: function() {
        var self = this;
        self.isLoadingMultisite = true;

        return fetch(VBP_Config.restUrl + 'multisite/templates', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            self.sharedTemplates = Array.isArray(data) ? data : [];
            self.isLoadingMultisite = false;
        })
        .catch(function(error) {
            vbpLog.warn(' Error cargando templates:', error);
            self.sharedTemplates = [];
            self.isLoadingMultisite = false;
        });
    },

    /**
     * Abrir modal para compartir template
     */
    openShareTemplateModal: function(template) {
        this.shareTemplateData = {
            templateId: template.id,
            name: template.name || '',
            description: template.description || '',
            category: template.category || 'general'
        };
        this.showShareTemplateModal = true;
    },

    /**
     * Compartir template con la red
     */
    shareTemplateToNetwork: function() {
        var self = this;

        if (!this.shareTemplateData.templateId) {
            this.showNotification('Selecciona un template para compartir', 'error');
            return;
        }

        var payload = {
            template_id: this.shareTemplateData.templateId,
            source_site_id: this.currentSiteId,
            name: this.shareTemplateData.name,
            description: this.shareTemplateData.description,
            category: this.shareTemplateData.category
        };

        fetch(VBP_Config.restUrl + 'multisite/templates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Template compartido con la red', 'success');
                self.showShareTemplateModal = false;
                self.loadSharedTemplates();
            } else {
                throw new Error(data.message || 'Error al compartir');
            }
        })
        .catch(function(error) {
            self.showNotification(error.message, 'error');
        });
    },

    /**
     * Importar template compartido al sitio actual
     */
    importSharedTemplate: function(templateId) {
        var self = this;

        fetch(VBP_Config.restUrl + 'multisite/templates/' + templateId + '/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Template importado correctamente', 'success');
                // Recargar templates locales
                if (typeof self.loadTemplates === 'function') {
                    self.loadTemplates();
                }
            } else {
                throw new Error(data.message || 'Error al importar');
            }
        })
        .catch(function(error) {
            self.showNotification(error.message, 'error');
        });
    },

    // ============ WIDGETS COMPARTIDOS ============

    /**
     * Cargar widgets compartidos
     */
    loadSharedWidgets: function() {
        var self = this;

        return fetch(VBP_Config.restUrl + 'multisite/widgets', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            self.sharedWidgets = Array.isArray(data) ? data : [];
        })
        .catch(function(error) {
            vbpLog.warn(' Error cargando widgets:', error);
            self.sharedWidgets = [];
        });
    },

    /**
     * Compartir widget global con la red
     */
    shareWidgetToNetwork: function(widgetId) {
        var self = this;

        var payload = {
            widget_id: widgetId,
            source_site_id: this.currentSiteId
        };

        fetch(VBP_Config.restUrl + 'multisite/widgets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify(payload)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Widget compartido con la red', 'success');
                self.loadSharedWidgets();
            } else {
                throw new Error(data.message || 'Error al compartir');
            }
        })
        .catch(function(error) {
            self.showNotification(error.message, 'error');
        });
    },

    // ============ DESIGN TOKENS DE RED ============

    /**
     * Cargar design tokens de red
     */
    loadNetworkTokens: function() {
        var self = this;

        return fetch(VBP_Config.restUrl + 'multisite/tokens', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            self.networkTokens = data || {};
        })
        .catch(function(error) {
            vbpLog.warn(' Error cargando tokens:', error);
            self.networkTokens = {};
        });
    },

    /**
     * Aplicar tokens de red al documento actual
     */
    applyNetworkTokens: function() {
        var self = this;

        if (!this.networkTokens || !this.networkTokens.colors) {
            this.showNotification('No hay tokens de red disponibles', 'warning');
            return;
        }

        var store = Alpine.store('vbp');
        if (!store) return;

        // Aplicar tokens al store de design tokens si existe
        if (store.designTokens) {
            Object.assign(store.designTokens.colors || {}, this.networkTokens.colors);

            if (this.networkTokens.typography) {
                Object.assign(store.designTokens.typography || {}, this.networkTokens.typography);
            }
        }

        this.showNotification('Tokens de red aplicados', 'success');
    },

    // ============ UI HELPERS ============

    /**
     * Abrir panel multisite
     */
    openMultisitePanel: function() {
        if (!this.isMultisite) {
            this.showNotification('Este sitio no es parte de una red multisite', 'info');
            return;
        }
        this.showMultisitePanel = true;
        this.loadSharedTemplates();
    },

    /**
     * Cerrar panel multisite
     */
    closeMultisitePanel: function() {
        this.showMultisitePanel = false;
    },

    /**
     * Obtener icono de estado multisite
     */
    getMultisiteIcon: function() {
        if (this.isMultisite) {
            return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>';
        }
        return '';
    },

    /**
     * Obtener templates compartidos filtrados por categoría
     */
    getSharedTemplatesByCategory: function(category) {
        if (!category || category === 'all') {
            return this.sharedTemplates;
        }
        return this.sharedTemplates.filter(function(template) {
            return template.category === category;
        });
    },

    /**
     * Obtener categorías únicas de templates compartidos
     */
    getSharedTemplateCategories: function() {
        var categories = {};
        this.sharedTemplates.forEach(function(template) {
            if (template.category) {
                categories[template.category] = true;
            }
        });
        return Object.keys(categories);
    },

    /**
     * Verificar si el usuario puede compartir (es admin de red)
     */
    canShare: function() {
        return this.isNetworkAdmin;
    },

    /**
     * Formatear fecha de compartido
     */
    formatSharedDate: function(dateString) {
        if (!dateString) return '';
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
};
