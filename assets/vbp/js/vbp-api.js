/**
 * Visual Builder Pro - API
 * Cliente REST y AJAX para comunicación con WordPress
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.vbpApi = {
    /**
     * Estado de la API
     */
    isSaving: false,
    lastSaveTime: null,
    autoSaveTimer: null,
    autoSaveDelay: 3000,

    /**
     * Request AJAX genérico
     */
    async request(action, data) {
        var params = new URLSearchParams({
            action: action,
            nonce: VBP_Config.nonce
        });

        // Agregar datos adicionales
        if (data) {
            Object.keys(data).forEach(function(key) {
                if (typeof data[key] === 'object') {
                    params.append(key, JSON.stringify(data[key]));
                } else {
                    params.append(key, data[key]);
                }
            });
        }

        var response = await fetch(VBP_Config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params
        });

        return response.json();
    },

    /**
     * Request REST API
     */
    async restRequest(endpoint, method, data) {
        var options = {
            method: method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        var response = await fetch(VBP_Config.restUrl + endpoint, options);
        return response.json();
    },

    /**
     * Guardar documento
     */
    async saveDocument(postId, elements, settings) {
        if (this.isSaving) {
            console.log('VBP: Ya hay un guardado en progreso');
            return { success: false, message: 'Guardado en progreso' };
        }

        this.isSaving = true;
        var self = this;

        try {
            // Dispatch evento antes de guardar
            document.dispatchEvent(new CustomEvent('vbp:beforeSave', {
                detail: { postId: postId, elements: elements }
            }));

            // Preparar datos completos
            var documentData = {
                elements: elements,
                settings: settings
            };

            var result = await this.request('vbp_guardar_documento', {
                post_id: postId,
                data: documentData
            });

            if (result.success) {
                self.lastSaveTime = new Date();
                Alpine.store('vbp').isDirty = false;

                // Dispatch evento después de guardar
                document.dispatchEvent(new CustomEvent('vbp:afterSave', {
                    detail: { postId: postId, success: true }
                }));

                console.log('VBP: Documento guardado correctamente');
            } else {
                console.error('VBP: Error al guardar', result.data);
                document.dispatchEvent(new CustomEvent('vbp:saveError', {
                    detail: { error: result.data }
                }));
            }

            return result;
        } catch (error) {
            console.error('VBP: Error de red al guardar', error);
            document.dispatchEvent(new CustomEvent('vbp:saveError', {
                detail: { error: error.message }
            }));
            return { success: false, message: error.message };
        } finally {
            self.isSaving = false;
        }
    },

    /**
     * Cargar documento
     */
    async loadDocument(postId) {
        try {
            var result = await this.request('vbp_cargar_documento', {
                post_id: postId
            });

            if (result.success && result.data) {
                // Dispatch evento después de cargar
                document.dispatchEvent(new CustomEvent('vbp:documentLoaded', {
                    detail: { postId: postId, data: result.data }
                }));
            }

            return result;
        } catch (error) {
            console.error('VBP: Error al cargar documento', error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Iniciar autosave
     */
    startAutoSave() {
        var self = this;

        // Observar cambios en el store
        if (typeof Alpine !== 'undefined') {
            Alpine.effect(function() {
                var store = Alpine.store('vbp');
                if (store && store.isDirty) {
                    self.scheduleAutoSave();
                }
            });
        }
    },

    /**
     * Programar autosave con debounce
     */
    scheduleAutoSave() {
        var self = this;

        if (this.autoSaveTimer) {
            clearTimeout(this.autoSaveTimer);
        }

        this.autoSaveTimer = setTimeout(function() {
            var store = Alpine.store('vbp');
            if (store && store.isDirty && store.postId) {
                self.saveDocument(store.postId, store.elements, store.settings);
            }
        }, this.autoSaveDelay);
    },

    /**
     * Detener autosave
     */
    stopAutoSave() {
        if (this.autoSaveTimer) {
            clearTimeout(this.autoSaveTimer);
            this.autoSaveTimer = null;
        }
    },

    /**
     * Guardar como borrador (autosave)
     */
    async saveDraft(postId, elements, settings) {
        var documentData = {
            elements: elements,
            settings: settings
        };
        return this.request('vbp_autosave', {
            post_id: postId,
            data: documentData
        });
    },

    /**
     * Publicar documento
     */
    async publishDocument(postId) {
        return this.request('vbp_publicar_documento', {
            post_id: postId
        });
    },

    /**
     * Obtener revisiones (via REST API)
     */
    async getRevisions(postId) {
        return this.restRequest('documents/' + postId + '/revisions', 'GET');
    },

    /**
     * Restaurar revisión (via REST API)
     */
    async restoreRevision(postId, revisionId) {
        return this.restRequest('documents/' + postId + '/revisions/' + revisionId + '/restore', 'POST');
    },

    /**
     * Previsualizar elemento
     */
    async previewElement(element) {
        return this.request('vbp_render_elemento', {
            element: element
        });
    },

    /**
     * Obtener librería de bloques
     */
    async getBlockLibrary() {
        return this.request('vbp_obtener_bloques', {});
    },

    /**
     * Subir media
     */
    async uploadMedia(file) {
        var formData = new FormData();
        formData.append('action', 'vbp_upload_media');
        formData.append('nonce', VBP_Config.nonce);
        formData.append('file', file);

        var response = await fetch(VBP_Config.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        return response.json();
    },

    /**
     * Exportar template
     */
    async exportTemplate(elements, name) {
        return this.request('vbp_exportar_template', {
            elements: elements,
            name: name
        });
    },

    /**
     * Importar template
     */
    async importTemplate(templateId) {
        return this.request('vbp_importar_template', {
            template_id: templateId
        });
    },

    /**
     * Obtener templates guardados
     */
    async getTemplates() {
        return this.request('vbp_obtener_templates', {});
    }
};

// Iniciar autosave cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que Alpine esté inicializado
    document.addEventListener('alpine:initialized', function() {
        window.vbpApi.startAutoSave();
    });
});
