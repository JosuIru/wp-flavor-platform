/**
 * Visual Builder Pro - AI Assistant
 * Integración de IA para generación de contenido
 *
 * @package Flavor_Chat_IA
 * @since 2.1.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

function initVbpAI() {
    if (typeof Alpine === 'undefined' || window.__vbpAIInitialized) {
        return !!window.__vbpAIInitialized;
    }

    var existingStore = null;
    try {
        existingStore = Alpine.store('vbpAI');
    } catch (error) {
        existingStore = null;
    }

    var aiStoreDefinition = {
        isOpen: false,
        isLoading: false,
        currentField: null,
        currentElement: null,
        currentType: null,
        currentContent: '',
        generatedContent: '',
        error: null,

        // Modo de operación: 'element' | 'page'
        mode: 'element',

        // Opciones de contexto
        industry: '',
        tone: 'profesional',
        companyName: '',
        description: '',
        targetAudience: '',

        // Opciones disponibles (se cargan desde el servidor)
        industries: [],
        tones: [],
        actions: [],

        // Opciones para generación de páginas
        pageTypes: [],
        sectionTypes: [],
        selectedPageType: '',
        selectedSections: [],

        // Página generada
        generatedPage: null,

        // Historial de generaciones
        history: [],
        maxHistoryItems: 20,
        showHistory: false,

        /**
         * Añade una entrada al historial
         */
        addToHistory: function(type, prompt, content, context) {
            var entry = {
                id: 'hist_' + Date.now(),
                timestamp: new Date().toISOString(),
                type: type,
                prompt: prompt,
                content: content,
                context: context || {}
            };

            this.history.unshift(entry);

            // Limitar tamaño del historial
            if (this.history.length > this.maxHistoryItems) {
                this.history = this.history.slice(0, this.maxHistoryItems);
            }

            // Guardar en localStorage
            this.saveHistoryToStorage();
        },

        /**
         * Guarda el historial en localStorage
         */
        saveHistoryToStorage: function() {
            try {
                localStorage.setItem('vbp_ai_history', JSON.stringify(this.history));
            } catch (e) {
                vbpLog.warn('AI: Error saving history:', e);
            }
        },

        /**
         * Carga el historial desde localStorage
         */
        loadHistoryFromStorage: function() {
            try {
                var saved = localStorage.getItem('vbp_ai_history');
                if (saved) {
                    this.history = JSON.parse(saved);
                }
            } catch (e) {
                vbpLog.warn('AI: Error loading history:', e);
                this.history = [];
            }
        },

        /**
         * Aplica una entrada del historial
         */
        applyFromHistory: function(entryId) {
            var entry = this.history.find(function(h) { return h.id === entryId; });
            if (entry) {
                this.generatedContent = entry.content;
                this.showHistory = false;
            }
        },

        /**
         * Elimina una entrada del historial
         */
        removeFromHistory: function(entryId) {
            this.history = this.history.filter(function(h) { return h.id !== entryId; });
            this.saveHistoryToStorage();
        },

        /**
         * Limpia todo el historial
         */
        clearHistory: function() {
            if (confirm('¿Eliminar todo el historial de generaciones?')) {
                this.history = [];
                this.saveHistoryToStorage();
            }
        },

        /**
         * Formatea la fecha del historial
         */
        formatHistoryDate: function(isoString) {
            var date = new Date(isoString);
            var now = new Date();
            var diff = now - date;

            if (diff < 60000) return 'Hace un momento';
            if (diff < 3600000) return 'Hace ' + Math.floor(diff / 60000) + ' min';
            if (diff < 86400000) return 'Hace ' + Math.floor(diff / 3600000) + ' horas';
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        /**
         * Abre el panel de AI en modo elemento
         */
        open: function(field, element, type, content) {
            this.mode = 'element';
            this.currentField = field;
            this.currentElement = element;
            this.currentType = type;
            this.currentContent = content || '';
            this.generatedContent = '';
            this.generatedPage = null;
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
            this.mode = 'element';
            this.currentField = null;
            this.currentElement = null;
            this.currentType = null;
            this.currentContent = '';
            this.generatedContent = '';
            this.generatedPage = null;
            this.selectedPageType = '';
            this.selectedSections = [];
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

            // Cargar opciones básicas
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
                vbpLog.warn('AI: Error loading options:', error);
            });

            // Cargar tipos de página
            this.loadPageTypes();
        },

        /**
         * Carga los tipos de página disponibles
         */
        loadPageTypes: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                return;
            }

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/ai/page-types'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.page_types) {
                    self.pageTypes = Object.entries(data.page_types).map(function(entry) {
                        return {
                            id: entry[0],
                            name: entry[1].name,
                            description: entry[1].description,
                            defaultSections: entry[1].default_sections || []
                        };
                    });
                }
                if (data.section_types) {
                    self.sectionTypes = Object.entries(data.section_types).map(function(entry) {
                        return {
                            id: entry[0],
                            name: entry[1].name,
                            description: entry[1].description
                        };
                    });
                }
            })
            .catch(function(error) {
                vbpLog.warn('AI: Error loading page types:', error);
            });
        },

        /**
         * Abre el panel en modo generación de página completa
         */
        openPageMode: function() {
            this.mode = 'page';
            this.currentField = null;
            this.currentElement = null;
            this.currentType = null;
            this.currentContent = '';
            this.generatedContent = '';
            this.generatedPage = null;
            this.error = null;
            this.isOpen = true;

            // Cargar opciones si no están cargadas
            if (this.pageTypes.length === 0) {
                this.loadPageTypes();
            }
            if (this.industries.length === 0) {
                this.loadOptions();
            }
        },

        /**
         * Selecciona un tipo de página y carga sus secciones predeterminadas
         */
        selectPageType: function(pageTypeId) {
            this.selectedPageType = pageTypeId;
            var pageType = this.pageTypes.find(function(pt) { return pt.id === pageTypeId; });
            if (pageType && pageType.defaultSections) {
                this.selectedSections = pageType.defaultSections.slice();
            } else {
                this.selectedSections = [];
            }
        },

        /**
         * Alterna una sección en la lista de seleccionadas
         */
        toggleSection: function(sectionId) {
            var index = this.selectedSections.indexOf(sectionId);
            if (index === -1) {
                this.selectedSections.push(sectionId);
            } else {
                this.selectedSections.splice(index, 1);
            }
        },

        /**
         * Comprueba si una sección está seleccionada
         */
        isSectionSelected: function(sectionId) {
            return this.selectedSections.indexOf(sectionId) !== -1;
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
                    var contentString = typeof data.content === 'string' ? data.content : JSON.stringify(data.content);
                    self.generatedContent = contentString;

                    // Guardar en historial
                    self.addToHistory(
                        self.currentType || 'generate',
                        'Generar ' + (self.currentType || 'contenido'),
                        contentString,
                        { industry: self.industry, tone: self.tone }
                    );
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
         * Genera una página completa con IA
         */
        generatePage: function() {
            var self = this;

            if (!this.selectedPageType) {
                this.error = 'Selecciona un tipo de página';
                return;
            }

            if (this.selectedSections.length === 0) {
                this.error = 'Selecciona al menos una sección';
                return;
            }

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                this.error = 'API no configurada';
                return;
            }

            this.isLoading = true;
            this.error = null;
            this.generatedPage = null;

            var requestContext = {
                industry: this.industry || 'general',
                tone: this.tone || 'profesional',
                company_name: this.companyName || 'Mi Empresa',
                description: this.description || '',
                target_audience: this.targetAudience || ''
            };

            fetch(VBP_Config.restUrl.replace('flavor-vbp/v1/', 'flavor-vbp/v1/ai/generate-page'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    page_type: this.selectedPageType,
                    sections: this.selectedSections,
                    context: requestContext
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.isLoading = false;
                if (data.success && data.content) {
                    self.generatedPage = data.content;
                    self.generatedContent = JSON.stringify(data.content, null, 2);

                    // Guardar en historial
                    self.addToHistory(
                        'page',
                        'Página: ' + self.selectedPageType,
                        self.generatedContent,
                        {
                            pageType: self.selectedPageType,
                            sections: self.selectedSections,
                            industry: self.industry
                        }
                    );
                } else {
                    self.error = data.message || 'Error al generar la página';
                }
            })
            .catch(function(error) {
                self.isLoading = false;
                self.error = 'Error de conexión: ' + error.message;
            });
        },

        /**
         * Aplica la página generada al canvas
         */
        applyPage: function() {
            if (!this.generatedPage || !this.generatedPage.blocks) {
                this.error = 'No hay página generada para aplicar';
                return;
            }

            var store = Alpine.store('vbp');
            var self = this;

            // Limpiar canvas actual
            if (store.elements && store.elements.length > 0) {
                if (!confirm('¿Deseas reemplazar el contenido actual del canvas?')) {
                    return;
                }
                // Limpiar elementos actuales
                store.elements = [];
            }

            // Convertir bloques generados a elementos VBP
            var convertedBlocks = this.convertBlocksToVBP(this.generatedPage.blocks);

            // Añadir elementos al canvas
            convertedBlocks.forEach(function(block) {
                store.addElement(block);
            });

            // Mostrar notificación de éxito
            if (window.vbpApp && window.vbpApp.showNotification) {
                window.vbpApp.showNotification('Página generada y aplicada', 'success');
            }

            this.close();
        },

        /**
         * Convierte bloques del formato IA a formato VBP
         */
        convertBlocksToVBP: function(blocks) {
            var self = this;
            var convertedBlocksArray = [];
            var yPosition = 0;

            blocks.forEach(function(block, index) {
                var vbpBlock = self.convertSingleBlock(block, yPosition);
                if (vbpBlock) {
                    convertedBlocksArray.push(vbpBlock);
                    yPosition += 400; // Espaciado entre secciones
                }
            });

            return convertedBlocksArray;
        },

        /**
         * Convierte un bloque individual al formato VBP
         */
        convertSingleBlock: function(block, yPosition) {
            var blockId = (typeof generateElementId === 'function') ? generateElementId('ai') : 'ai_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            var vbpBlock = {
                id: blockId,
                type: block.type || 'section',
                x: 0,
                y: yPosition,
                width: '100%',
                data: {},
                styles: {},
                children: []
            };

            // Copiar propiedades
            if (block.props) {
                Object.assign(vbpBlock.data, block.props);
                if (block.props.className) {
                    vbpBlock.className = block.props.className;
                }
                if (block.props.id) {
                    vbpBlock.sectionId = block.props.id;
                }
            }

            // Convertir hijos recursivamente
            if (block.children && Array.isArray(block.children)) {
                var self = this;
                vbpBlock.children = block.children.map(function(child, childIndex) {
                    return self.convertSingleBlock(child, 0);
                }).filter(function(c) { return c !== null; });
            }

            return vbpBlock;
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

                    // Guardar en historial
                    self.addToHistory(
                        'improve_' + action,
                        'Mejorar: ' + action,
                        data.content,
                        { action: action, industry: self.industry, tone: self.tone }
                    );
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
    };

    if (existingStore) {
        Object.keys(aiStoreDefinition).forEach(function(key) {
            existingStore[key] = aiStoreDefinition[key];
        });
    } else {
        Alpine.store('vbpAI', aiStoreDefinition);
    }

    window.vbpAIPanel = function() {
        return {
            get store() {
                return Alpine.store('vbpAI');
            },

            init: function() {
                // Cargar historial desde localStorage
                Alpine.store('vbpAI').loadHistoryFromStorage();

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
    };

    if (typeof Alpine.data === 'function') {
        Alpine.data('vbpAIPanel', window.vbpAIPanel);
    }

    window.__vbpAIInitialized = true;
    return true;
}

window.initVbpAI = initVbpAI;

document.addEventListener('alpine:init', function() {
    initVbpAI();
});

if (typeof Alpine !== 'undefined') {
    initVbpAI();
}

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
