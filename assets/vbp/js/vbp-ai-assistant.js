/**
 * Visual Builder Pro - AI Assistant
 * Integración de IA para generación de contenido
 *
 * @package Flavor_Chat_IA
 * @since 2.1.0
 */

document.addEventListener('alpine:init', function() {
    // Store para el panel de AI
    Alpine.store('vbpAI', {
        isOpen: false,
        isLoading: false,
        currentField: null,
        currentElement: null,
        currentType: null,
        currentContent: '',
        generatedContent: '',
        error: null,

        // Opciones de contexto
        industry: '',
        tone: 'professional',

        // Opciones disponibles (se cargan desde el servidor)
        industries: [],
        tones: [],
        actions: [],

        /**
         * Abre el panel de AI
         */
        open: function(field, element, type, content) {
            this.currentField = field;
            this.currentElement = element;
            this.currentType = type;
            this.currentContent = content || '';
            this.generatedContent = '';
            this.error = null;
            this.isOpen = true;

            // Cargar opciones si no están cargadas
            if (this.industries.length === 0) {
                this.loadOptions();
            }
        },

        /**
         * Cierra el panel de AI
         */
        close: function() {
            this.isOpen = false;
            this.currentField = null;
            this.currentElement = null;
            this.currentType = null;
            this.currentContent = '';
            this.generatedContent = '';
            this.error = null;
        },

        /**
         * Carga las opciones disponibles desde el servidor
         */
        loadOptions: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                return;
            }

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/ai/options'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.industries) self.industries = Object.entries(data.industries).map(function(entry) { return { id: entry[0], name: entry[1] }; });
                if (data.tones) self.tones = Object.entries(data.tones).map(function(entry) { return { id: entry[0], name: entry[1] }; });
                if (data.actions) self.actions = Object.entries(data.actions).map(function(entry) { return { id: entry[0], name: entry[1] }; });
            })
            .catch(function(error) {
                console.warn('[VBP AI] Error loading options:', error);
            });
        },

        /**
         * Genera contenido nuevo
         */
        generate: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                this.error = 'API no configurada';
                return;
            }

            this.isLoading = true;
            this.error = null;

            var context = {
                industry: this.industry,
                tone: this.tone,
                currentContent: this.currentContent
            };

            // Añadir contexto del elemento si está disponible
            if (this.currentElement && this.currentElement.data) {
                context.elementData = this.currentElement.data;
            }

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/ai/generate'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    type: this.currentType,
                    context: context
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.isLoading = false;
                if (data.success && data.content) {
                    self.generatedContent = typeof data.content === 'string' ? data.content : JSON.stringify(data.content);
                } else {
                    self.error = data.message || 'Error al generar contenido';
                }
            })
            .catch(function(error) {
                self.isLoading = false;
                self.error = 'Error de conexión: ' + error.message;
            });
        },

        /**
         * Mejora el contenido existente
         */
        improve: function(action) {
            var self = this;

            if (!this.currentContent && !this.generatedContent) {
                this.error = 'No hay contenido para mejorar';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                this.error = 'API no configurada';
                return;
            }

            this.isLoading = true;
            this.error = null;

            var contentToImprove = this.generatedContent || this.currentContent;

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/ai/improve'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    content: contentToImprove,
                    action: action,
                    context: {
                        industry: this.industry,
                        tone: this.tone
                    }
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.isLoading = false;
                if (data.success && data.content) {
                    self.generatedContent = data.content;
                } else {
                    self.error = data.message || 'Error al mejorar contenido';
                }
            })
            .catch(function(error) {
                self.isLoading = false;
                self.error = 'Error de conexión: ' + error.message;
            });
        },

        /**
         * Aplica el contenido generado al elemento
         */
        apply: function() {
            if (!this.generatedContent || !this.currentElement) {
                return;
            }

            var store = Alpine.store('vbp');
            var elementId = this.currentElement.id;
            var field = this.currentField;
            var content = this.generatedContent;

            // Determinar el campo correcto basado en el tipo
            var dataField = field;
            if (field === 'heading_text') dataField = 'text';
            if (field === 'hero_title') dataField = 'titulo';
            if (field === 'hero_subtitle') dataField = 'subtitulo';

            // Actualizar el elemento
            var elemento = store.getElement(elementId);
            if (elemento) {
                var datosActualizados = Object.assign({}, elemento.data);
                datosActualizados[dataField] = content;
                store.updateElement(elementId, { data: datosActualizados });

                // Mostrar notificación de éxito
                if (window.vbpApp && window.vbpApp.showNotification) {
                    window.vbpApp.showNotification('Contenido aplicado', 'success');
                }
            }

            this.close();
        }
    });

    // Componente para el panel de AI
    Alpine.data('vbpAIPanel', function() {
        return {
            get store() {
                return Alpine.store('vbpAI');
            },

            init: function() {
                // Escuchar el evento de AI assist
                var self = this;
                document.addEventListener('vbp-ai-assist', function(e) {
                    if (e.detail) {
                        Alpine.store('vbpAI').open(
                            e.detail.field,
                            e.detail.element,
                            e.detail.type,
                            e.detail.content
                        );
                    }
                });
            }
        };
    });
});

// Listener global para el evento vbp-ai-assist
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('vbp-ai-assist', function(e) {
        if (typeof Alpine !== 'undefined' && Alpine.store('vbpAI')) {
            if (e.detail) {
                Alpine.store('vbpAI').open(
                    e.detail.field,
                    e.detail.element,
                    e.detail.type,
                    e.detail.content
                );
            }
        }
    });
});
