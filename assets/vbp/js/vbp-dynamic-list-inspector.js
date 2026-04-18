/**
 * Visual Builder Pro - Inspector dinámico para el bloque Lista Dinámica.
 *
 * Registra el componente Alpine `vbpDynamicListInspector` que reemplaza la
 * antigua textarea JSON por un formulario basado en el schema devuelto por
 * el endpoint REST /flavor-vbp/v1/collections.
 *
 * Flujo:
 *   1. Al seleccionar un elemento dynamic-list, initFromElement rehidrata el
 *      componente a partir de selectedElement.data (source + query_args_json).
 *   2. loadCollections se ejecuta una única vez y cachea la lista en el propio
 *      módulo para evitar refetches al cambiar entre elementos.
 *   3. handleSourceChange resetea queryArgs a los defaults del nuevo schema y
 *      escribe source + query_args_json vía updateElementData.
 *   4. updateQueryArg muta queryArgs y vuelca el objeto serializado de vuelta
 *      a query_args_json, que es el que consume el renderer PHP.
 *
 * @since 3.6.0
 */

(function () {
    'use strict';

    // Cache compartido por módulo para no repetir la llamada REST.
    var collectionsCachePromise = null;

    function fetchCollectionsOnce() {
        if (collectionsCachePromise) {
            return collectionsCachePromise;
        }

        var restUrl = (window.VBP_Config && window.VBP_Config.restUrl) ? window.VBP_Config.restUrl : '/wp-json/flavor-vbp/v1/';
        var nonce = (window.VBP_Config && window.VBP_Config.restNonce) ? window.VBP_Config.restNonce : '';

        collectionsCachePromise = fetch(restUrl + 'collections', {
            headers: nonce ? { 'X-WP-Nonce': nonce } : {},
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        }).then(function (payload) {
            var collections = (payload && Array.isArray(payload.collections)) ? payload.collections : [];
            // Construir un mapa por id para acceso rápido al schema.
            var byId = {};
            collections.forEach(function (coleccion) {
                byId[coleccion.id] = coleccion;
            });
            return { list: collections, byId: byId };
        }).catch(function (error) {
            // Invalidar el cache si falla para permitir reintentos en siguientes selecciones.
            collectionsCachePromise = null;
            throw error;
        });

        return collectionsCachePromise;
    }

    function clampIntWithinRange(value, minValue, maxValue, defaultValue) {
        var parsed = parseInt(value, 10);
        if (isNaN(parsed)) {
            return (defaultValue !== undefined) ? defaultValue : 0;
        }
        if (typeof minValue === 'number' && parsed < minValue) {
            return minValue;
        }
        if (typeof maxValue === 'number' && parsed > maxValue) {
            return maxValue;
        }
        return parsed;
    }

    function parseQueryArgsJson(jsonString) {
        if (!jsonString) {
            return {};
        }
        try {
            var parsed = JSON.parse(jsonString);
            return (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function buildDefaultsFromSchema(schemaFields) {
        var defaults = {};
        if (!schemaFields) {
            return defaults;
        }
        Object.keys(schemaFields).forEach(function (fieldName) {
            var fieldConfig = schemaFields[fieldName];
            if (fieldConfig && fieldConfig.default !== undefined && fieldConfig.default !== null) {
                defaults[fieldName] = fieldConfig.default;
            }
        });
        return defaults;
    }

    function vbpDynamicListInspector() {
        return {
            collections: [],
            collectionsById: {},
            collectionsLoaded: false,
            currentSchema: { id: '', label: '', description: '', fields: {} },
            queryArgs: {},
            loadError: '',
            elementId: null,
            // Preview state
            previewItem: null,
            previewLoading: false,
            previewError: '',
            previewTotal: 0,
            previewDebounceTimer: null,

            /**
             * Rehidrata el estado cuando el inspector abre un elemento
             * dynamic-list (o cambia al siguiente elemento seleccionado).
             */
            initFromElement: function (element) {
                var self = this;

                self.elementId = element && element.id ? element.id : null;
                self.queryArgs = parseQueryArgsJson(element && element.data ? element.data.query_args_json : '');

                if (self.collectionsLoaded) {
                    self.applySourceFromElement(element);
                    return;
                }

                fetchCollectionsOnce().then(function (result) {
                    self.collections = result.list;
                    self.collectionsById = result.byId;
                    self.collectionsLoaded = true;
                    self.loadError = '';
                    self.applySourceFromElement(element);
                }).catch(function (error) {
                    self.loadError = 'No se pudo cargar la lista de colecciones: ' + error.message;
                });
            },

            applySourceFromElement: function (element) {
                var sourceActual = element && element.data ? (element.data.source || '') : '';

                if (!sourceActual && this.collections.length > 0) {
                    // El elemento todavía no tiene fuente asignada: usamos la
                    // primera y sembramos defaults.
                    this.handleSourceChange(this.collections[0].id);
                    return;
                }

                if (sourceActual && this.collectionsById[sourceActual]) {
                    this.currentSchema = this.collectionsById[sourceActual];
                } else {
                    this.currentSchema = { id: '', label: '', description: '', fields: {} };
                }

                this.schedulePreviewFetch();
            },

            handleSourceChange: function (nuevoSource) {
                var schemaDestino = this.collectionsById[nuevoSource];
                if (!schemaDestino) {
                    return;
                }

                this.currentSchema = schemaDestino;
                this.queryArgs = buildDefaultsFromSchema(schemaDestino.fields);

                this.persistFieldsToElement({
                    source: nuevoSource,
                    query_args_json: JSON.stringify(this.queryArgs)
                });

                this.schedulePreviewFetch();
            },

            updateQueryArg: function (fieldName, valor) {
                // Alpine no detecta mutaciones dentro de objetos indexados por
                // template x-for si no reasignamos la referencia.
                var clonQueryArgs = Object.assign({}, this.queryArgs);
                if (valor === '' || valor === null || valor === undefined) {
                    delete clonQueryArgs[fieldName];
                } else {
                    clonQueryArgs[fieldName] = valor;
                }
                this.queryArgs = clonQueryArgs;

                this.persistFieldsToElement({
                    query_args_json: JSON.stringify(clonQueryArgs)
                });

                this.schedulePreviewFetch();
            },

            /**
             * Persiste campos al store de VBP. El store ya se encarga del
             * undo/redo, autosave debounced y re-render reactivo del canvas.
             */
            persistFieldsToElement: function (campos) {
                if (!this.elementId || typeof Alpine === 'undefined') {
                    return;
                }
                var vbpStore = Alpine.store('vbp');
                if (!vbpStore || typeof vbpStore.updateElement !== 'function') {
                    return;
                }

                var elementoActual = typeof vbpStore.getElement === 'function'
                    ? vbpStore.getElement(this.elementId)
                    : null;
                var dataActual = (elementoActual && elementoActual.data) ? elementoActual.data : {};
                var dataNueva = Object.assign({}, dataActual, campos);

                vbpStore.updateElement(this.elementId, { data: dataNueva });
            },

            hasSchemaFields: function () {
                return this.currentSchema
                    && this.currentSchema.fields
                    && Object.keys(this.currentSchema.fields).length > 0;
            },

            /**
             * Proxy con el mismo nombre que el método del inspector padre,
             * para que las expresiones inline `updateElementData(...)` dentro
             * de la sección dynamic-list funcionen igual que en el resto del
             * inspector. Los templates estaban escritos contra ese nombre.
             */
            updateElementData: function (campo, valor) {
                var campos = {};
                campos[campo] = valor;
                this.persistFieldsToElement(campos);
            },

            /**
             * Debounce del fetch de preview. Evita spam al endpoint cuando
             * el usuario escribe rápidamente en campos de texto.
             */
            schedulePreviewFetch: function () {
                var self = this;
                if (this.previewDebounceTimer) {
                    clearTimeout(this.previewDebounceTimer);
                }
                this.previewDebounceTimer = setTimeout(function () {
                    self.previewDebounceTimer = null;
                    self.fetchPreviewItem();
                }, 350);
            },

            /**
             * Pide limit=1 al endpoint autenticado para mostrar el primer
             * item como muestra. Reutiliza la URL REST y el nonce que el
             * editor ya expone en VBP_Config.
             */
            fetchPreviewItem: function () {
                var self = this;

                if (!this.currentSchema || !this.currentSchema.id) {
                    this.previewItem = null;
                    this.previewTotal = 0;
                    return;
                }

                var restUrl = (window.VBP_Config && window.VBP_Config.restUrl) ? window.VBP_Config.restUrl : '/wp-json/flavor-vbp/v1/';
                var nonce   = (window.VBP_Config && window.VBP_Config.restNonce) ? window.VBP_Config.restNonce : '';
                var sourceId = this.currentSchema.id;

                var argsPreview = Object.assign({}, this.queryArgs, { limit: 1, page: 1 });

                this.previewLoading = true;
                this.previewError = '';

                fetch(restUrl + 'collections/' + encodeURIComponent(sourceId) + '/query', {
                    method: 'POST',
                    headers: Object.assign(
                        { 'Content-Type': 'application/json' },
                        nonce ? { 'X-WP-Nonce': nonce } : {}
                    ),
                    credentials: 'same-origin',
                    body: JSON.stringify(argsPreview)
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                }).then(function (payload) {
                    var items = (payload && Array.isArray(payload.items)) ? payload.items : [];
                    self.previewItem = items.length > 0 ? items[0] : null;
                    self.previewTotal = (payload && payload.meta && typeof payload.meta.total === 'number') ? payload.meta.total : 0;
                    self.previewLoading = false;
                }).catch(function (error) {
                    self.previewError = 'Preview no disponible: ' + error.message;
                    self.previewLoading = false;
                    self.previewItem = null;
                });
            },

            clampInt: clampIntWithinRange
        };
    }

    window.vbpDynamicListInspector = vbpDynamicListInspector;

    function registerDynamicListInspectorComponent() {
        if (typeof Alpine === 'undefined') {
            return false;
        }
        Alpine.data('vbpDynamicListInspector', vbpDynamicListInspector);
        return true;
    }

    if (typeof Alpine !== 'undefined') {
        registerDynamicListInspectorComponent();
    }

    document.addEventListener('alpine:init', function () {
        registerDynamicListInspectorComponent();
    });
})();
