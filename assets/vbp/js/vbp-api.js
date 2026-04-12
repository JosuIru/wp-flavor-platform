/**
 * Visual Builder Pro - API
 * Cliente REST y AJAX para comunicación con WordPress
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

window.vbpApi = {
    /**
     * Estado de la API
     */
    isSaving: false,
    lastSaveTime: null,
    autoSaveTimer: null,
    autoSaveDelay: 3000,
    _initialized: false,
    _eventHandlers: {},

    /**
     * Inicializar la API
     */
    init: function() {
        if (this._initialized) return;
        this._initialized = true;

        var self = this;
        this._eventHandlers.alpineInitialized = function() {
            self.startAutoSave();
        };
        document.addEventListener('alpine:initialized', this._eventHandlers.alpineInitialized);
    },

    /**
     * Destruir y limpiar recursos
     */
    destroy: function() {
        this.stopAutoSave();

        if (this._eventHandlers.alpineInitialized) {
            document.removeEventListener('alpine:initialized', this._eventHandlers.alpineInitialized);
        }

        this._eventHandlers = {};
        this._initialized = false;
    },

    /**
     * Request AJAX genérico
     */
    async request(action, data) {
        try {
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

            if (!response.ok) {
                throw new Error('HTTP error: ' + response.status);
            }

            return response.json();
        } catch (error) {
            vbpLog.error('Error en request AJAX:', action, error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Request REST API
     */
    async restRequest(endpoint, method, data) {
        try {
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

            if (!response.ok) {
                throw new Error('HTTP error: ' + response.status);
            }

            return response.json();
        } catch (error) {
            vbpLog.error('Error en request REST:', endpoint, error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Guardar documento
     */
    async saveDocument(postId, elements, settings) {
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbp') : null;
        var titleInput = document.querySelector('.vbp-title-input');
        var title = titleInput ? titleInput.value : undefined;

        if (store && typeof store.saveDocument === 'function') {
            if (postId) {
                store.postId = postId;
            }
            if (elements) {
                store.elements = elements;
            }
            if (settings) {
                store.settings = settings;
            }

            return store.saveDocument({
                title: title
            });
        }

        return { success: false, message: 'Store VBP no disponible' };
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
            vbpLog.error('Error al cargar documento', error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Iniciar autosave
     */
    startAutoSave() {
        // Legacy no-op: el autosave canónico se dispara desde Alpine.store('vbp').markAsDirty()
    },

    /**
     * Programar autosave con debounce
     */
    scheduleAutoSave() {
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbp') : null;
        if (store && typeof store.autoSave === 'function') {
            return store.autoSave();
        }
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
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbp') : null;
        if (store && typeof store.saveDocument === 'function') {
            if (postId) {
                store.postId = postId;
            }
            if (elements) {
                store.elements = elements;
            }
            if (settings) {
                store.settings = settings;
            }
            return store.saveDocument({ autosave: true });
        }

        return { success: false, message: 'Store VBP no disponible' };
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
        try {
            var formData = new FormData();
            formData.append('action', 'vbp_upload_media');
            formData.append('nonce', VBP_Config.nonce);
            formData.append('file', file);

            var response = await fetch(VBP_Config.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('HTTP error: ' + response.status);
            }

            return response.json();
        } catch (error) {
            vbpLog.error('Error subiendo media:', error);
            return { success: false, message: error.message };
        }
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

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.vbpApi.init();
});
