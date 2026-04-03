/**
 * Visual Builder Pro - App Module: Templates & Global Widgets
 * Gestión de plantillas y widgets globales
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppTemplates = {
    // Estado Templates
    templatesTab: 'library',
    templateSearch: '',
    templateCategory: '',
    templates: [],
    userTemplates: [],
    selectedTemplate: null,
    newTemplateName: '',
    newTemplateCategory: 'landing',
    newTemplateDescription: '',
    isSavingTemplate: false,
    showTemplatesModal: false,
    showSaveTemplateModal: false,

    // Estado Global Widgets
    globalWidgets: [],
    globalWidgetsLoaded: false,
    showGlobalWidgetsModal: false,
    showSaveGlobalWidgetModal: false,
    newGlobalWidgetName: '',
    newGlobalWidgetCategory: 'general',
    isSavingGlobalWidget: false,

    // ============ TEMPLATES ============

    loadTemplates: function() {
        var self = this;
        // Cargar templates desde VBP_Config si están disponibles
        if (typeof VBP_Config !== 'undefined' && VBP_Config.templates) {
            this.templates = VBP_Config.templates.library || [];
            this.userTemplates = VBP_Config.templates.user || [];
        } else {
            // Cargar desde REST API si no están en config
            fetch(VBP_Config.restUrl + 'templates', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type');
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Respuesta no es JSON válido');
                }
                return response.json();
            })
            .then(function(data) {
                self.templates = data.library || [];
                self.userTemplates = data.user || [];
            })
            .catch(function(error) {
                vbpLog.warn('Error cargando templates (usando librería vacía):', error.message);
                self.templates = [];
                self.userTemplates = [];
            });
        }
    },

    get filteredTemplates() {
        var self = this;
        var results = this.templates.slice();

        // Filtrar por búsqueda
        if (this.templateSearch) {
            var searchLower = this.templateSearch.toLowerCase();
            results = results.filter(function(template) {
                return template.title.toLowerCase().indexOf(searchLower) !== -1 ||
                       (template.description && template.description.toLowerCase().indexOf(searchLower) !== -1);
            });
        }

        // Filtrar por categoría
        if (this.templateCategory) {
            results = results.filter(function(template) {
                return template.category === self.templateCategory;
            });
        }

        return results;
    },

    selectTemplate: function(template) {
        if (confirm('¿Deseas aplicar este template? Se reemplazará el contenido actual.')) {
            this.applyTemplate(template);
        }
    },

    previewTemplate: function(template) {
        if (template.preview_url) {
            window.open(template.preview_url, '_blank');
        } else {
            this.showNotification('Vista previa no disponible para este template', 'info');
        }
    },

    applyTemplate: function(template) {
        var self = this;

        // Si el template tiene elementos directamente, aplicarlos
        if (template.elements) {
            Alpine.store('vbp').elements = sanitizeElements(template.elements);
            if (template.settings) {
                Alpine.store('vbp').settings = Object.assign({}, Alpine.store('vbp').settings, template.settings);
            }
            Alpine.store('vbp').isDirty = true;
            this.showTemplatesModal = false;
            this.showNotification('Template aplicado correctamente', 'success');
            return;
        }

        // Si es un template de usuario o librería, aplicar via API
        var templateId = template.id;

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/apply-template', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({ template_id: templateId })
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success && result.document) {
                Alpine.store('vbp').elements = sanitizeElements(result.document.elements || []);
                if (result.document.settings) {
                    Alpine.store('vbp').settings = Object.assign({}, Alpine.store('vbp').settings, result.document.settings);
                }
                Alpine.store('vbp').isDirty = true;
                self.showTemplatesModal = false;
                self.showNotification('Template aplicado correctamente', 'success');
            } else {
                throw new Error(result.message || 'Error al aplicar template');
            }
        })
        .catch(function(error) {
            self.showNotification('Error al aplicar template: ' + error.message, 'error');
        });
    },

    saveAsTemplate: function() {
        this.newTemplateName = this.documentTitle || '';
        this.showSaveTemplateModal = true;
    },

    confirmSaveTemplate: function() {
        var self = this;
        if (!this.newTemplateName.trim()) return;

        this.isSavingTemplate = true;

        fetch(VBP_Config.restUrl + 'templates', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({
                post_id: VBP_Config.postId,
                name: this.newTemplateName,
                category: this.newTemplateCategory,
                description: this.newTemplateDescription
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                self.showNotification('Template guardado correctamente', 'success');
                self.showSaveTemplateModal = false;
                self.newTemplateName = '';
                self.newTemplateDescription = '';
                self.loadTemplates();
            } else {
                throw new Error(result.message || 'Error al guardar template');
            }
            self.isSavingTemplate = false;
        })
        .catch(function(error) {
            self.showNotification('Error al guardar template: ' + error.message, 'error');
            self.isSavingTemplate = false;
        });
    },

    deleteTemplate: function(template) {
        var self = this;
        if (!confirm('¿Estás seguro de eliminar este template?')) return;

        fetch(VBP_Config.restUrl + 'templates/' + template.id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                self.showNotification('Template eliminado', 'success');
                self.loadTemplates();
            } else {
                throw new Error(result.message || 'Error al eliminar template');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    },

    // ============ GLOBAL WIDGETS ============

    loadGlobalWidgets: function() {
        var self = this;
        fetch(VBP_Config.restUrl + 'global-widgets', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            var contentType = response.headers.get('content-type');
            if (!response.ok || !contentType || !contentType.includes('application/json')) {
                throw new Error('Error al cargar widgets globales');
            }
            return response.json();
        })
        .then(function(data) {
            self.globalWidgets = Array.isArray(data) ? data : [];
            self.globalWidgetsLoaded = true;
        })
        .catch(function(error) {
            vbpLog.warn('Error cargando widgets globales:', error.message);
            self.globalWidgets = [];
        });
    },

    saveAsGlobalWidget: function() {
        var store = Alpine.store('vbp');
        var selectedIds = store.selection.elementIds;

        if (selectedIds.length === 0) {
            this.showNotification('Selecciona un elemento para guardarlo como widget global', 'warning');
            return;
        }

        if (selectedIds.length > 1) {
            this.showNotification('Solo puedes guardar un elemento a la vez como widget global', 'warning');
            return;
        }

        var element = store.getElementById(selectedIds[0]);
        if (!element) {
            this.showNotification('No se encontró el elemento seleccionado', 'error');
            return;
        }

        this.newGlobalWidgetName = element.name || element.type;
        this.showSaveGlobalWidgetModal = true;
    },

    confirmSaveGlobalWidget: function() {
        var self = this;
        if (!this.newGlobalWidgetName.trim()) return;

        var store = Alpine.store('vbp');
        var selectedId = store.selection.elementIds[0];
        var element = store.getElementById(selectedId);

        if (!element) {
            this.showNotification('No se encontró el elemento', 'error');
            return;
        }

        this.isSavingGlobalWidget = true;

        // Clonar el elemento para guardarlo
        var elementToSave = JSON.parse(JSON.stringify(element));
        delete elementToSave.id;

        fetch(VBP_Config.restUrl + 'global-widgets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({
                title: this.newGlobalWidgetName,
                element: elementToSave,
                category: this.newGlobalWidgetCategory
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.id) {
                self.showNotification('Widget global guardado correctamente', 'success');
                self.showSaveGlobalWidgetModal = false;
                self.newGlobalWidgetName = '';
                self.loadGlobalWidgets();
            } else {
                throw new Error(result.error || 'Error al guardar widget global');
            }
            self.isSavingGlobalWidget = false;
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
            self.isSavingGlobalWidget = false;
        });
    },

    insertGlobalWidget: function(widget) {
        var self = this;
        var store = Alpine.store('vbp');

        fetch(VBP_Config.restUrl + 'global-widgets/' + widget.id, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.element) {
                var newElement = {
                    id: 'el_' + Date.now(),
                    type: 'global_widget',
                    name: widget.title,
                    data: {
                        globalWidgetId: widget.id,
                        originalType: data.element.type
                    },
                    styles: {},
                    visible: true,
                    locked: false
                };

                store.addElement(newElement);
                self.showNotification('Widget global insertado', 'success');
                self.showGlobalWidgetsModal = false;
            } else {
                throw new Error('No se encontraron datos del widget');
            }
        })
        .catch(function(error) {
            self.showNotification('Error al insertar widget: ' + error.message, 'error');
        });
    },

    deleteGlobalWidget: function(widget) {
        var self = this;
        if (!confirm('¿Estás seguro de eliminar este widget global? Se usará en ' + (widget.usageCount || 0) + ' páginas.')) return;

        fetch(VBP_Config.restUrl + 'global-widgets/' + widget.id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.message) {
                self.showNotification('Widget global eliminado', 'success');
                self.loadGlobalWidgets();
            } else {
                throw new Error(result.error || 'Error al eliminar widget');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    }
};
