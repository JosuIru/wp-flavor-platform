/**
 * Visual Builder Pro - Sistema de Simbolos
 *
 * Extiende el store de VBP para soportar simbolos con instancias sincronizadas.
 * Los simbolos son componentes maestros cuyas instancias se actualizan automaticamente.
 *
 * DIFERENCIA CON COMPONENTES:
 * - Componentes: Copias independientes (no sincronizadas)
 * - Simbolos: Instancias VINCULADAS al maestro (sincronizadas)
 *
 * @package Flavor_Chat_IA
 * @since 2.0.22
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no esta definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Sistema de Simbolos VBP
     */
    window.VBPSymbols = {
        // Cache local de simbolos
        symbols: [],

        // Mapa de element_id -> symbol_id para lookup rapido de instancias
        instances: {},

        // Cache de variantes por symbol_id
        variantCache: {},

        // Instancias pendientes de sincronizar con el servidor
        pendingSync: [],

        // Estado de carga
        cargando: false,
        inicializado: false,

        // Debounce timer para guardar overrides
        saveOverridesTimer: null,
        saveOverridesDelay: 500,

        /**
         * Inicializar el sistema de simbolos
         */
        init: function() {
            if (this.inicializado) {
                vbpLog.log('[VBPSymbols] Ya inicializado, omitiendo');
                return;
            }

            var self = this;
            vbpLog.log('[VBPSymbols] Inicializando sistema de simbolos');

            // Cargar simbolos desde la API
            this.cargarSimbolos();

            // Extender el store de Alpine.js
            this.extenderStore();

            // Configurar listeners de eventos
            this.configurarEventos();

            this.inicializado = true;
        },

        /**
         * Cargar simbolos desde la API REST
         */
        cargarSimbolos: function() {
            var self = this;

            if (typeof VBP_Config === 'undefined' || !VBP_Config.restUrl) {
                vbpLog.warn('[VBPSymbols] VBP_Config no disponible, reintentando...');
                setTimeout(function() { self.cargarSimbolos(); }, 500);
                return;
            }

            self.cargando = true;

            fetch(VBP_Config.restUrl + 'symbols', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.cargando = false;
                if (data.success && data.symbols) {
                    self.symbols = data.symbols;
                    vbpLog.log('[VBPSymbols] Cargados ' + self.symbols.length + ' simbolos');

                    // Actualizar store si ya existe
                    if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbp')) {
                        Alpine.store('vbp').symbols = self.symbols;
                    }

                    // Notificar carga completada
                    document.dispatchEvent(new CustomEvent('vbp:symbols:loaded', {
                        detail: { symbols: self.symbols }
                    }));
                } else if (data.data && Array.isArray(data.data)) {
                    // Formato alternativo de respuesta
                    self.symbols = data.data;
                    vbpLog.log('[VBPSymbols] Cargados ' + self.symbols.length + ' simbolos (formato alt)');
                }
            })
            .catch(function(error) {
                self.cargando = false;
                vbpLog.warn('[VBPSymbols] Error cargando simbolos:', error);
            });
        },

        /**
         * Recargar simbolos desde el servidor
         */
        recargarSimbolos: function() {
            this.cargarSimbolos();
        },

        /**
         * Extender el store de Alpine.js
         * Agrega metodos para simbolos sin sobrescribir los existentes
         */
        extenderStore: function() {
            var self = this;

            // Esperar a que Alpine.js este disponible
            if (typeof Alpine === 'undefined') {
                document.addEventListener('alpine:init', function() {
                    self.aplicarExtensionesAlStore();
                });
            } else if (Alpine.store && Alpine.store('vbp')) {
                // Alpine ya esta inicializado
                self.aplicarExtensionesAlStore();
            } else {
                // Alpine existe pero store no, esperar
                document.addEventListener('alpine:init', function() {
                    setTimeout(function() {
                        self.aplicarExtensionesAlStore();
                    }, 100);
                });
            }
        },

        /**
         * Aplicar las extensiones al store VBP
         */
        aplicarExtensionesAlStore: function() {
            var self = this;
            var intentos = 0;
            var maxIntentos = 20;

            function intentarAplicar() {
                var store = Alpine.store('vbp');

                if (!store) {
                    intentos++;
                    if (intentos < maxIntentos) {
                        setTimeout(intentarAplicar, 100);
                    } else {
                        vbpLog.error('[VBPSymbols] No se pudo acceder al store VBP despues de ' + maxIntentos + ' intentos');
                    }
                    return;
                }

                vbpLog.log('[VBPSymbols] Extendiendo store VBP');

                // Agregar propiedades para simbolos
                store.symbols = self.symbols;
                store.symbolInstances = self.instances;
                store.symbolsLoading = false;

                // =============================================
                // METODOS DE CREACION
                // =============================================

                /**
                 * Crear simbolo desde la seleccion actual
                 * @param {string} nombre - Nombre del simbolo
                 * @param {Array} exposedProps - Propiedades expuestas para overrides
                 * @returns {Promise}
                 */
                store.createSymbolFromSelection = function(nombre, exposedProps) {
                    return self.crearSimboloDesdeSeleccion(nombre, exposedProps);
                };

                /**
                 * Crear simbolo desde bloques especificos
                 * @param {string} nombre - Nombre del simbolo
                 * @param {Array} bloques - Array de bloques
                 * @param {object} opciones - Opciones adicionales
                 * @returns {Promise}
                 */
                store.createSymbol = function(nombre, bloques, opciones) {
                    return self.crearSimbolo(nombre, bloques, opciones);
                };

                // =============================================
                // METODOS DE INSTANCIAS
                // =============================================

                /**
                 * Insertar instancia de simbolo en el canvas
                 * @param {number|string} symbolId - ID del simbolo
                 * @param {string} parentId - ID del contenedor padre (opcional)
                 * @param {number} index - Posicion de insercion (opcional)
                 * @returns {Promise}
                 */
                store.insertSymbolInstance = function(symbolId, parentId, index) {
                    return self.insertarInstancia(symbolId, parentId, index);
                };

                /**
                 * Verificar si un elemento es una instancia de simbolo
                 * @param {string} elementId - ID del elemento
                 * @returns {boolean}
                 */
                store.isSymbolInstance = function(elementId) {
                    return self.esInstancia(elementId);
                };

                /**
                 * Obtener datos completos de una instancia
                 * @param {string} elementId - ID del elemento
                 * @returns {object|null}
                 */
                store.getInstanceData = function(elementId) {
                    return self.obtenerDatosInstancia(elementId);
                };

                /**
                 * Obtener el simbolo fuente de una instancia
                 * @param {string} elementId - ID del elemento
                 * @returns {object|null}
                 */
                store.getInstanceSymbol = function(elementId) {
                    var datos = self.obtenerDatosInstancia(elementId);
                    return datos ? datos.symbol : null;
                };

                // =============================================
                // METODOS DE OVERRIDES
                // =============================================

                /**
                 * Aplicar override a una propiedad de instancia
                 * @param {string} elementId - ID de la instancia
                 * @param {string} propPath - Ruta de la propiedad (ej: "data.text")
                 * @param {*} value - Nuevo valor
                 * @returns {boolean}
                 */
                store.setInstanceOverride = function(elementId, propPath, value) {
                    return self.aplicarOverride(elementId, propPath, value);
                };

                /**
                 * Resetear un override especifico
                 * @param {string} elementId - ID de la instancia
                 * @param {string} propPath - Ruta de la propiedad
                 * @returns {boolean}
                 */
                store.resetInstanceOverride = function(elementId, propPath) {
                    return self.resetearOverride(elementId, propPath);
                };

                /**
                 * Resetear todos los overrides de una instancia
                 * @param {string} elementId - ID de la instancia
                 * @returns {boolean}
                 */
                store.resetAllInstanceOverrides = function(elementId) {
                    return self.resetearTodosOverrides(elementId);
                };

                /**
                 * Verificar si una instancia tiene overrides
                 * @param {string} elementId - ID de la instancia
                 * @returns {boolean}
                 */
                store.instanceHasOverrides = function(elementId) {
                    var datos = self.obtenerDatosInstancia(elementId);
                    return datos ? datos.hasOverrides : false;
                };

                /**
                 * Obtener lista de propiedades con override
                 * @param {string} elementId - ID de la instancia
                 * @returns {Array}
                 */
                store.getInstanceOverriddenProps = function(elementId) {
                    return self.obtenerPropsConOverride(elementId);
                };

                // =============================================
                // METODOS DE SINCRONIZACION
                // =============================================

                /**
                 * Detach: convertir instancia a bloques independientes
                 * @param {string} elementId - ID de la instancia
                 * @returns {Promise}
                 */
                store.detachInstance = function(elementId) {
                    return self.detachInstancia(elementId);
                };

                /**
                 * Forzar sincronizacion de una instancia
                 * @param {string} elementId - ID de la instancia
                 * @returns {Promise}
                 */
                store.syncInstance = function(elementId) {
                    return self.sincronizarInstancia(elementId);
                };

                /**
                 * Sincronizar todas las instancias de un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {Promise}
                 */
                store.syncSymbolInstances = function(symbolId) {
                    return self.sincronizarTodasInstancias(symbolId);
                };

                /**
                 * Resolver contenido completo de instancia (simbolo + overrides)
                 * @param {string} elementId - ID de la instancia
                 * @returns {object|null}
                 */
                store.resolveInstanceContent = function(elementId) {
                    return self.resolverContenido(elementId);
                };

                // =============================================
                // METODOS DE GESTION DE SIMBOLOS
                // =============================================

                /**
                 * Actualizar un simbolo maestro
                 * @param {number|string} symbolId - ID del simbolo
                 * @param {object} cambios - Cambios a aplicar
                 * @returns {Promise}
                 */
                store.updateSymbol = function(symbolId, cambios) {
                    return self.actualizarSimbolo(symbolId, cambios);
                };

                /**
                 * Eliminar un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {Promise}
                 */
                store.deleteSymbol = function(symbolId) {
                    return self.eliminarSimbolo(symbolId);
                };

                /**
                 * Obtener simbolo por ID
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {object|null}
                 */
                store.getSymbol = function(symbolId) {
                    return self.obtenerSimbolo(symbolId);
                };

                /**
                 * Obtener todas las instancias de un simbolo en el documento actual
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {Array}
                 */
                store.getSymbolInstancesInDocument = function(symbolId) {
                    return self.obtenerInstanciasEnDocumento(symbolId);
                };

                /**
                 * Recargar simbolos desde el servidor
                 */
                store.reloadSymbols = function() {
                    self.recargarSimbolos();
                };

                /**
                 * Editar simbolo maestro (abre en editor separado)
                 * @param {number|string} symbolId - ID del simbolo
                 */
                store.editSymbolMaster = function(symbolId) {
                    return self.editarSimboloMaestro(symbolId);
                };

                // =============================================
                // METODOS DE VARIANTES
                // =============================================

                /**
                 * Obtener variantes de un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {object} Objeto con variantes {key: {name, overrides}}
                 */
                store.getSymbolVariants = function(symbolId) {
                    return self.obtenerVariantes(symbolId);
                };

                /**
                 * Cambiar variante de una instancia
                 * @param {string} elementId - ID de la instancia
                 * @param {string} variantKey - Clave de la variante
                 * @returns {Promise}
                 */
                store.setInstanceVariant = function(elementId, variantKey) {
                    return self.cambiarVariante(elementId, variantKey);
                };

                /**
                 * Obtener variante actual de una instancia
                 * @param {string} elementId - ID de la instancia
                 * @returns {string} Clave de la variante
                 */
                store.getInstanceVariant = function(elementId) {
                    return self.obtenerVarianteInstancia(elementId);
                };

                /**
                 * Crear nueva variante desde una instancia
                 * @param {string} elementId - ID de la instancia
                 * @param {string} variantName - Nombre de la variante
                 * @param {string} variantKey - Clave opcional de la variante
                 * @returns {Promise}
                 */
                store.createVariantFromInstance = function(elementId, variantName, variantKey) {
                    return self.crearVarianteDesdeInstancia(elementId, variantName, variantKey);
                };

                /**
                 * Crear variante en un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @param {string} key - Clave de la variante
                 * @param {string} name - Nombre de la variante
                 * @param {object} overrides - Overrides de la variante
                 * @returns {Promise}
                 */
                store.createSymbolVariant = function(symbolId, key, name, overrides) {
                    return self.crearVariante(symbolId, key, name, overrides);
                };

                /**
                 * Eliminar variante de un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @param {string} variantKey - Clave de la variante
                 * @returns {Promise}
                 */
                store.deleteSymbolVariant = function(symbolId, variantKey) {
                    return self.eliminarVariante(symbolId, variantKey);
                };

                /**
                 * Resolver contenido de instancia con variante aplicada
                 * @param {string} elementId - ID de la instancia
                 * @returns {object|null}
                 */
                store.resolveInstanceWithVariant = function(elementId) {
                    return self.resolverContenidoConVariante(elementId);
                };

                // =============================================
                // METODOS DE NESTED SYMBOLS (ANIDACION)
                // =============================================

                /**
                 * Verificar si se puede anidar un simbolo dentro de otro
                 * @param {number|string} parentSymbolId - ID del simbolo padre
                 * @param {number|string} childSymbolId - ID del simbolo a insertar
                 * @returns {Promise<boolean>}
                 */
                store.canNestSymbol = function(parentSymbolId, childSymbolId) {
                    return self.puedeAnidar(parentSymbolId, childSymbolId);
                };

                /**
                 * Obtener simbolos anidados de un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @param {boolean} recursive - Si buscar recursivamente (default: true)
                 * @returns {Promise<Array>}
                 */
                store.getNestedSymbols = function(symbolId, recursive) {
                    return self.obtenerSimbolosAnidados(symbolId, recursive);
                };

                /**
                 * Obtener simbolos padre que usan este simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {Promise<Array>}
                 */
                store.getParentSymbols = function(symbolId) {
                    return self.obtenerSimbolosPadre(symbolId);
                };

                /**
                 * Resolver contenido de una instancia con anidacion
                 * @param {string} elementId - ID del elemento
                 * @param {number} depth - Profundidad actual (default: 0)
                 * @returns {object|null}
                 */
                store.resolveNestedContent = function(elementId, depth) {
                    return self.resolverContenidoAnidado(elementId, depth);
                };

                /**
                 * Verificar si un contenido tiene instancias anidadas
                 * @param {Array} content - Contenido a verificar
                 * @returns {boolean}
                 */
                store.hasNestedInstances = function(content) {
                    return self.tieneInstanciasAnidadas(content);
                };

                /**
                 * Obtener profundidad de anidacion de una instancia
                 * @param {string} elementId - ID del elemento
                 * @returns {number}
                 */
                store.getInstanceNestingDepth = function(elementId) {
                    return self.obtenerProfundidadInstancia(elementId);
                };

                /**
                 * Obtener arbol de dependencias de un simbolo
                 * @param {number|string} symbolId - ID del simbolo
                 * @returns {Promise<object>}
                 */
                store.getSymbolDependencyTree = function(symbolId) {
                    return self.obtenerArbolDependencias(symbolId);
                };

                /**
                 * Profundidad maxima de anidacion permitida
                 * @returns {number}
                 */
                store.getMaxNestingDepth = function() {
                    return self.MAX_NESTING_DEPTH;
                };

                vbpLog.log('[VBPSymbols] Store extendido correctamente');

                // Notificar que el sistema esta listo
                document.dispatchEvent(new CustomEvent('vbp:symbols:ready'));
            }

            intentarAplicar();
        },

        // =============================================
        // IMPLEMENTACION DE METODOS
        // =============================================

        /**
         * Crear simbolo desde bloques seleccionados
         */
        crearSimboloDesdeSeleccion: function(nombre, exposedProps) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var seleccionados = store.selection.elementIds;

            if (!seleccionados || seleccionados.length === 0) {
                self.mostrarError('Selecciona elementos para crear un simbolo');
                return Promise.reject(new Error('No hay seleccion'));
            }

            // Obtener bloques seleccionados
            var bloques = [];
            for (var i = 0; i < seleccionados.length; i++) {
                var elemento = store.getElementById(seleccionados[i]);
                if (elemento) {
                    // Clonar para no modificar original
                    bloques.push(JSON.parse(JSON.stringify(elemento)));
                }
            }

            if (bloques.length === 0) {
                self.mostrarError('No se encontraron elementos validos');
                return Promise.reject(new Error('Sin elementos validos'));
            }

            return this.crearSimbolo(nombre, bloques, {
                exposed_properties: exposedProps || [],
                replace_with_instance: true,
                original_ids: seleccionados
            });
        },

        /**
         * Crear simbolo desde bloques
         */
        crearSimbolo: function(nombre, bloques, opciones) {
            var self = this;
            opciones = opciones || {};

            if (!nombre || nombre.trim() === '') {
                self.mostrarError('El nombre del simbolo es requerido');
                return Promise.reject(new Error('Nombre requerido'));
            }

            return fetch(VBP_Config.restUrl + 'symbols', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    name: nombre.trim(),
                    content: bloques,
                    exposed_properties: opciones.exposed_properties || [],
                    category: opciones.category || '',
                    description: opciones.description || '',
                    tags: opciones.tags || ''
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.symbol) {
                    // Agregar a cache local
                    self.symbols.push(data.symbol);

                    // Actualizar store
                    var store = Alpine.store('vbp');
                    if (store) {
                        store.symbols = self.symbols;
                    }

                    // Si se solicito reemplazar con instancia
                    if (opciones.replace_with_instance && opciones.original_ids) {
                        self.reemplazarConInstancia(data.symbol.id, opciones.original_ids);
                    }

                    self.mostrarExito('Simbolo "' + nombre + '" creado correctamente');

                    // Notificar
                    document.dispatchEvent(new CustomEvent('vbp:symbol:created', {
                        detail: { symbol: data.symbol }
                    }));

                    return data.symbol;
                }
                throw new Error(data.message || 'Error al crear simbolo');
            })
            .catch(function(error) {
                self.mostrarError('Error: ' + error.message);
                throw error;
            });
        },

        /**
         * Reemplazar bloques originales con una instancia del simbolo
         */
        reemplazarConInstancia: function(symbolId, originalIds) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store || !originalIds || originalIds.length === 0) return;

            // Obtener posicion del primer elemento para insertar ahi la instancia
            var primerElemento = store.getElementById(originalIds[0]);
            var indiceInsercion = -1;

            if (primerElemento) {
                indiceInsercion = store.getElementIndex(originalIds[0]);
            }

            // Eliminar elementos originales
            store.batchOperations(function() {
                for (var i = originalIds.length - 1; i >= 0; i--) {
                    store.removeElement(originalIds[i]);
                }
            });

            // Insertar instancia en la posicion original
            self.insertarInstancia(symbolId, null, indiceInsercion >= 0 ? indiceInsercion : undefined);
        },

        /**
         * Insertar instancia de simbolo en el canvas
         */
        insertarInstancia: function(symbolId, parentId, index) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var symbol = this.obtenerSimbolo(symbolId);

            if (!symbol) {
                self.mostrarError('Simbolo no encontrado');
                return Promise.reject(new Error('Simbolo no encontrado'));
            }

            // Crear elemento de tipo instancia
            var instanceId = (typeof generateElementId === 'function')
                ? generateElementId()
                : 'el_' + Math.random().toString(36).substr(2, 9);

            var instanceElement = {
                id: instanceId,
                type: '__symbol_instance__',
                name: symbol.name + ' (instancia)',
                symbolId: parseInt(symbolId, 10),
                symbolName: symbol.name,
                variant: symbol.default_variant || 'default',
                overrides: {},
                visible: true,
                locked: false,
                // Metadatos para el canvas
                _isSymbolInstance: true,
                _symbolVersion: symbol.version || 1
            };

            // Guardar historial antes de modificar
            store.saveToHistory('Insertar instancia de ' + symbol.name);

            // Insertar en el arbol
            if (parentId) {
                var parent = store.getElementById(parentId);
                if (parent && parent.children) {
                    var insertIndex = (index !== undefined) ? index : parent.children.length;
                    parent.children.splice(insertIndex, 0, instanceElement);
                } else {
                    store.elements.push(instanceElement);
                }
            } else {
                var insertIndexRoot = (index !== undefined) ? index : store.elements.length;
                store.elements.splice(insertIndexRoot, 0, instanceElement);
            }

            // Registrar en mapa de instancias local
            self.instances[instanceId] = symbolId;

            // Registrar instancia en el backend
            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/instances', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    document_id: store.postId,
                    element_id: instanceId,
                    variant: instanceElement.variant
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    store.markAsDirty();
                    store.setSelection([instanceId]);

                    document.dispatchEvent(new CustomEvent('vbp:instance:inserted', {
                        detail: {
                            instanceId: instanceId,
                            symbolId: symbolId,
                            symbol: symbol
                        }
                    }));

                    return instanceElement;
                }
                // Si falla el registro, igual mantener la instancia local
                vbpLog.warn('[VBPSymbols] No se pudo registrar instancia en servidor:', data.message);
                store.markAsDirty();
                return instanceElement;
            })
            .catch(function(error) {
                vbpLog.warn('[VBPSymbols] Error registrando instancia:', error);
                store.markAsDirty();
                return instanceElement;
            });
        },

        /**
         * Verificar si un elemento es una instancia de simbolo
         */
        esInstancia: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementById(elementId);
            return element && (element.type === '__symbol_instance__' || element._isSymbolInstance === true);
        },

        /**
         * Obtener datos completos de una instancia
         */
        obtenerDatosInstancia: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return null;
            }

            var symbol = this.obtenerSimbolo(element.symbolId);
            var overrides = element.overrides || {};
            var overrideKeys = this.obtenerClavesProfundas(overrides);

            return {
                element: element,
                symbol: symbol,
                overrides: overrides,
                hasOverrides: overrideKeys.length > 0,
                overriddenProps: overrideKeys,
                symbolVersion: element._symbolVersion || 1,
                isOutdated: symbol && symbol.version && element._symbolVersion < symbol.version
            };
        },

        /**
         * Obtener claves profundas de un objeto (para listar overrides)
         */
        obtenerClavesProfundas: function(obj, prefijo) {
            var claves = [];
            prefijo = prefijo || '';

            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    var rutaCompleta = prefijo ? prefijo + '.' + key : key;
                    if (obj[key] !== null && typeof obj[key] === 'object' && !Array.isArray(obj[key])) {
                        claves = claves.concat(this.obtenerClavesProfundas(obj[key], rutaCompleta));
                    } else {
                        claves.push(rutaCompleta);
                    }
                }
            }

            return claves;
        },

        /**
         * Obtener propiedades con override de una instancia
         */
        obtenerPropsConOverride: function(elementId) {
            var datos = this.obtenerDatosInstancia(elementId);
            return datos ? datos.overriddenProps : [];
        },

        /**
         * Aplicar override a una propiedad
         */
        aplicarOverride: function(elementId, propPath, value) {
            var store = Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                vbpLog.warn('[VBPSymbols] Elemento no es instancia:', elementId);
                return false;
            }

            // Guardar historial
            store.saveToHistory('Override en instancia');

            // Asegurar que existe el objeto overrides
            if (!element.overrides) {
                element.overrides = {};
            }

            // Establecer valor anidado
            this.setNestedValue(element.overrides, propPath, value);

            store.markAsDirty();

            // Sincronizar con backend (debounced)
            this.programarGuardadoOverrides(elementId, element.overrides);

            // Notificar
            document.dispatchEvent(new CustomEvent('vbp:instance:override', {
                detail: {
                    elementId: elementId,
                    propPath: propPath,
                    value: value
                }
            }));

            return true;
        },

        /**
         * Resetear un override especifico
         */
        resetearOverride: function(elementId, propPath) {
            var store = Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementById(elementId);

            if (!element || !element.overrides) return false;

            store.saveToHistory('Resetear override');

            this.deleteNestedValue(element.overrides, propPath);

            store.markAsDirty();
            this.programarGuardadoOverrides(elementId, element.overrides);

            document.dispatchEvent(new CustomEvent('vbp:instance:override:reset', {
                detail: { elementId: elementId, propPath: propPath }
            }));

            return true;
        },

        /**
         * Resetear todos los overrides de una instancia
         */
        resetearTodosOverrides: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) return false;

            store.saveToHistory('Resetear todos los overrides');

            element.overrides = {};

            store.markAsDirty();
            this.programarGuardadoOverrides(elementId, {});

            document.dispatchEvent(new CustomEvent('vbp:instance:override:reset-all', {
                detail: { elementId: elementId }
            }));

            return true;
        },

        /**
         * Detach: convertir instancia a bloques independientes
         */
        detachInstancia: function(elementId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia de simbolo'));
            }

            // Resolver contenido final con overrides aplicados
            var contenidoFinal = this.resolverContenido(elementId);

            if (!contenidoFinal) {
                return Promise.reject(new Error('No se pudo resolver contenido del simbolo'));
            }

            // Llamar a API para detach (desvincular del simbolo)
            return fetch(VBP_Config.restUrl + 'symbols/instances/' + elementId + '/detach', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    document_id: store.postId
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success || data.code === 'instance_not_found') {
                    // Reemplazar instancia con bloques reales
                    self.reemplazarConBloques(elementId, contenidoFinal);

                    // Eliminar del mapa de instancias
                    delete self.instances[elementId];

                    store.markAsDirty();

                    self.mostrarExito('Instancia desvinculada correctamente');

                    document.dispatchEvent(new CustomEvent('vbp:instance:detached', {
                        detail: { elementId: elementId }
                    }));

                    return true;
                }
                throw new Error(data.message || 'Error al desvincular');
            })
            .catch(function(error) {
                // Incluso si falla la API, hacer detach local
                vbpLog.warn('[VBPSymbols] Error en API detach, aplicando localmente:', error);
                self.reemplazarConBloques(elementId, contenidoFinal);
                delete self.instances[elementId];
                store.markAsDirty();
                return true;
            });
        },

        /**
         * Resolver contenido: aplicar variante + overrides al contenido del simbolo
         */
        resolverContenido: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return null;
            }

            var symbol = this.obtenerSimbolo(element.symbolId);
            if (!symbol || !symbol.content) return null;

            // Clonar contenido del simbolo profundamente
            var contenido;
            if (Array.isArray(symbol.content)) {
                contenido = JSON.parse(JSON.stringify(symbol.content));
            } else {
                contenido = [JSON.parse(JSON.stringify(symbol.content))];
            }

            // 1. Aplicar overrides de la variante (si no es default)
            var variantKey = element.variant || symbol.default_variant || 'default';
            var variants = symbol.variants || {};

            if (variantKey !== 'default' && variants[variantKey] && variants[variantKey].overrides) {
                var variantOverrides = variants[variantKey].overrides;
                if (Object.keys(variantOverrides).length > 0) {
                    this.aplicarOverridesRecursivo(contenido, variantOverrides);
                }
            }

            // 2. Aplicar overrides de instancia
            if (element.overrides && Object.keys(element.overrides).length > 0) {
                this.aplicarOverridesRecursivo(contenido, element.overrides);
            }

            // Regenerar IDs para evitar conflictos
            this.regenerarIdsRecursivo(contenido);

            return contenido;
        },

        /**
         * Aplicar overrides recursivamente al contenido
         */
        aplicarOverridesRecursivo: function(contenido, overrides) {
            var self = this;

            // Los overrides estan en formato path: "children.0.data.text" -> valor
            // Necesitamos aplicarlos al contenido correspondiente

            for (var path in overrides) {
                if (overrides.hasOwnProperty(path)) {
                    var valor = overrides[path];

                    // Navegar al elemento correcto y aplicar
                    try {
                        self.setNestedValueInArray(contenido, path, valor);
                    } catch (e) {
                        vbpLog.warn('[VBPSymbols] No se pudo aplicar override:', path, e);
                    }
                }
            }
        },

        /**
         * Establecer valor anidado en array/objeto
         */
        setNestedValueInArray: function(arr, path, value) {
            var parts = path.split('.');
            var current = arr;

            for (var i = 0; i < parts.length - 1; i++) {
                var key = parts[i];

                // Si es numero, acceder como indice de array
                if (!isNaN(parseInt(key, 10)) && Array.isArray(current)) {
                    current = current[parseInt(key, 10)];
                } else if (current[key] !== undefined) {
                    current = current[key];
                } else {
                    // Crear path si no existe
                    current[key] = {};
                    current = current[key];
                }

                if (!current) return;
            }

            var lastKey = parts[parts.length - 1];
            if (!isNaN(parseInt(lastKey, 10)) && Array.isArray(current)) {
                current[parseInt(lastKey, 10)] = value;
            } else {
                current[lastKey] = value;
            }
        },

        /**
         * Regenerar IDs recursivamente para evitar conflictos
         */
        regenerarIdsRecursivo: function(elementos) {
            var self = this;

            if (!Array.isArray(elementos)) {
                elementos = [elementos];
            }

            for (var i = 0; i < elementos.length; i++) {
                var el = elementos[i];
                if (el && el.id) {
                    el.id = (typeof generateElementId === 'function')
                        ? generateElementId()
                        : 'el_' + Math.random().toString(36).substr(2, 9);
                }
                if (el && el.children && el.children.length > 0) {
                    self.regenerarIdsRecursivo(el.children);
                }
            }
        },

        /**
         * Reemplazar instancia con bloques reales
         */
        reemplazarConBloques: function(elementId, bloques) {
            var store = Alpine.store('vbp');
            if (!store || !bloques) return;

            // Encontrar posicion de la instancia
            var index = store.getElementIndex(elementId);

            if (index === -1) {
                // Buscar en hijos
                vbpLog.warn('[VBPSymbols] Instancia no encontrada en nivel raiz, buscando en hijos');
                // TODO: implementar reemplazo en hijos anidados
                return;
            }

            // Eliminar instancia
            store.elements.splice(index, 1);

            // Insertar bloques en su lugar
            var bloquesArray = Array.isArray(bloques) ? bloques : [bloques];
            for (var i = 0; i < bloquesArray.length; i++) {
                store.elements.splice(index + i, 0, bloquesArray[i]);
            }

            // Seleccionar los nuevos bloques
            var nuevosIds = bloquesArray.map(function(b) { return b.id; });
            store.setSelection(nuevosIds);
        },

        /**
         * Sincronizar instancia con la version actual del simbolo
         */
        sincronizarInstancia: function(elementId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia'));
            }

            // Recargar simbolo desde servidor
            return fetch(VBP_Config.restUrl + 'symbols/' + element.symbolId, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.symbol) {
                    // Actualizar version en instancia
                    element._symbolVersion = data.symbol.version || 1;

                    // Actualizar cache local
                    var symbolIndex = self.symbols.findIndex(function(s) {
                        return s.id === element.symbolId || s.id === parseInt(element.symbolId, 10);
                    });
                    if (symbolIndex !== -1) {
                        self.symbols[symbolIndex] = data.symbol;
                    }

                    store.markAsDirty();

                    document.dispatchEvent(new CustomEvent('vbp:instance:synced', {
                        detail: { elementId: elementId, symbol: data.symbol }
                    }));

                    return data.symbol;
                }
                throw new Error(data.message || 'Error al sincronizar');
            });
        },

        /**
         * Sincronizar todas las instancias de un simbolo en el documento
         */
        sincronizarTodasInstancias: function(symbolId) {
            var self = this;
            var instancias = this.obtenerInstanciasEnDocumento(symbolId);
            var promesas = [];

            for (var i = 0; i < instancias.length; i++) {
                promesas.push(this.sincronizarInstancia(instancias[i].id));
            }

            return Promise.all(promesas);
        },

        /**
         * Obtener todas las instancias de un simbolo en el documento actual
         */
        obtenerInstanciasEnDocumento: function(symbolId) {
            var store = Alpine.store('vbp');
            if (!store) return [];

            var instancias = [];
            var simboloIdNum = parseInt(symbolId, 10);

            function buscarEnElementos(elementos) {
                for (var i = 0; i < elementos.length; i++) {
                    var el = elementos[i];
                    if (el.type === '__symbol_instance__' && parseInt(el.symbolId, 10) === simboloIdNum) {
                        instancias.push(el);
                    }
                    if (el.children && el.children.length > 0) {
                        buscarEnElementos(el.children);
                    }
                }
            }

            buscarEnElementos(store.elements);
            return instancias;
        },

        /**
         * Actualizar un simbolo maestro
         */
        actualizarSimbolo: function(symbolId, cambios) {
            var self = this;

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify(cambios)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.symbol) {
                    // Actualizar cache local
                    var index = self.symbols.findIndex(function(s) {
                        return s.id === symbolId || s.id === parseInt(symbolId, 10);
                    });
                    if (index !== -1) {
                        self.symbols[index] = data.symbol;
                    }

                    var store = Alpine.store('vbp');
                    if (store) {
                        store.symbols = self.symbols;
                    }

                    self.mostrarExito('Simbolo actualizado');

                    document.dispatchEvent(new CustomEvent('vbp:symbol:updated', {
                        detail: { symbol: data.symbol }
                    }));

                    return data.symbol;
                }
                throw new Error(data.message || 'Error al actualizar');
            });
        },

        /**
         * Eliminar un simbolo
         */
        eliminarSimbolo: function(symbolId) {
            var self = this;

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Eliminar de cache local
                    self.symbols = self.symbols.filter(function(s) {
                        return s.id !== symbolId && s.id !== parseInt(symbolId, 10);
                    });

                    var store = Alpine.store('vbp');
                    if (store) {
                        store.symbols = self.symbols;
                    }

                    self.mostrarExito('Simbolo eliminado');

                    document.dispatchEvent(new CustomEvent('vbp:symbol:deleted', {
                        detail: { symbolId: symbolId }
                    }));

                    return true;
                }
                throw new Error(data.message || 'Error al eliminar');
            });
        },

        /**
         * Obtener simbolo por ID
         */
        obtenerSimbolo: function(symbolId) {
            var idNum = parseInt(symbolId, 10);
            return this.symbols.find(function(s) {
                return s.id === symbolId || s.id === idNum || parseInt(s.id, 10) === idNum;
            }) || null;
        },

        /**
         * Editar simbolo maestro (abrir en editor)
         */
        editarSimboloMaestro: function(symbolId) {
            var symbol = this.obtenerSimbolo(symbolId);
            if (!symbol) {
                this.mostrarError('Simbolo no encontrado');
                return;
            }

            // Abrir en nueva pestana para editar el post del simbolo
            var editUrl = VBP_Config.adminUrl + 'post.php?post=' + symbol.id + '&action=edit';
            window.open(editUrl, '_blank');
        },

        // =============================================
        // METODOS DE VARIANTES
        // =============================================

        /**
         * Obtener variantes de un simbolo
         */
        obtenerVariantes: function(symbolId) {
            var symbol = this.obtenerSimbolo(symbolId);
            if (!symbol) {
                return { default: { name: 'Por defecto', overrides: {} } };
            }

            var variants = symbol.variants || {};

            // Asegurar que siempre existe default
            if (!variants.default) {
                variants.default = { name: 'Por defecto', overrides: {} };
            }

            return variants;
        },

        /**
         * Cambiar variante de una instancia
         */
        cambiarVariante: function(elementId, variantKey) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia de simbolo'));
            }

            var symbol = this.obtenerSimbolo(element.symbolId);
            if (!symbol) {
                return Promise.reject(new Error('Simbolo no encontrado'));
            }

            // Verificar que la variante existe
            var variants = this.obtenerVariantes(element.symbolId);
            if (!variants[variantKey]) {
                return Promise.reject(new Error('Variante no encontrada: ' + variantKey));
            }

            // Guardar historial
            store.saveToHistory('Cambiar variante a ' + variants[variantKey].name);

            // Actualizar localmente
            element.variant = variantKey;
            store.markAsDirty();

            // Notificar cambio (para que el renderer actualice)
            document.dispatchEvent(new CustomEvent('vbp:instance:variant-changed', {
                detail: {
                    elementId: elementId,
                    variant: variantKey,
                    variantName: variants[variantKey].name
                }
            }));

            // Sincronizar con el servidor
            return fetch(VBP_Config.restUrl + 'instances/' + this.getInstanceId(elementId) + '/variant', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    variant: variantKey
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.mostrarExito('Variante cambiada a: ' + variants[variantKey].name);
                }
                return data;
            })
            .catch(function(error) {
                vbpLog.warn('[VBPSymbols] Error guardando variante:', error);
                // El cambio local ya se aplico
                return { success: true };
            });
        },

        /**
         * Obtener variante actual de una instancia
         */
        obtenerVarianteInstancia: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return 'default';

            var element = store.getElementById(elementId);
            if (!element || !this.esInstancia(elementId)) return 'default';

            return element.variant || 'default';
        },

        /**
         * Crear nueva variante desde una instancia
         */
        crearVarianteDesdeInstancia: function(elementId, variantName, variantKey) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia de simbolo'));
            }

            if (!variantName || variantName.trim() === '') {
                return Promise.reject(new Error('Nombre de variante requerido'));
            }

            // Generar key si no se proporciona
            if (!variantKey) {
                variantKey = variantName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
            }

            return fetch(VBP_Config.restUrl + 'instances/' + this.getInstanceId(elementId) + '/create-variant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    name: variantName,
                    key: variantKey
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar cache de simbolo
                    var symbol = self.obtenerSimbolo(element.symbolId);
                    if (symbol) {
                        if (!symbol.variants) symbol.variants = {};
                        symbol.variants[data.data.variant_key] = data.data.variant;
                    }

                    // Cambiar instancia a la nueva variante y limpiar overrides
                    element.variant = data.data.variant_key;
                    element.overrides = {};
                    store.markAsDirty();

                    self.mostrarExito('Variante "' + variantName + '" creada');

                    // Notificar
                    document.dispatchEvent(new CustomEvent('vbp:symbol:variant-created', {
                        detail: {
                            symbolId: element.symbolId,
                            variantKey: data.data.variant_key,
                            variant: data.data.variant
                        }
                    }));

                    return data;
                }
                throw new Error(data.message || 'Error al crear variante');
            });
        },

        /**
         * Crear variante en un simbolo
         */
        crearVariante: function(symbolId, key, name, overrides) {
            var self = this;

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/variants', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    key: key,
                    name: name,
                    overrides: overrides || {}
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar cache local
                    var symbol = self.obtenerSimbolo(symbolId);
                    if (symbol) {
                        if (!symbol.variants) symbol.variants = {};
                        symbol.variants[data.data.variant_key] = data.data.variant;
                    }

                    self.mostrarExito('Variante "' + name + '" creada');

                    document.dispatchEvent(new CustomEvent('vbp:symbol:variant-created', {
                        detail: {
                            symbolId: symbolId,
                            variantKey: data.data.variant_key,
                            variant: data.data.variant
                        }
                    }));

                    return data;
                }
                throw new Error(data.message || 'Error al crear variante');
            });
        },

        /**
         * Eliminar variante de un simbolo
         */
        eliminarVariante: function(symbolId, variantKey) {
            var self = this;

            if (variantKey === 'default') {
                return Promise.reject(new Error('No se puede eliminar la variante por defecto'));
            }

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/variants/' + variantKey, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Actualizar cache local
                    var symbol = self.obtenerSimbolo(symbolId);
                    if (symbol && symbol.variants) {
                        delete symbol.variants[variantKey];
                    }

                    self.mostrarExito('Variante eliminada');

                    document.dispatchEvent(new CustomEvent('vbp:symbol:variant-deleted', {
                        detail: {
                            symbolId: symbolId,
                            variantKey: variantKey
                        }
                    }));

                    return data;
                }
                throw new Error(data.message || 'Error al eliminar variante');
            });
        },

        /**
         * Resolver contenido de instancia con variante aplicada
         */
        resolverContenidoConVariante: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return null;
            }

            var symbol = this.obtenerSimbolo(element.symbolId);
            if (!symbol || !symbol.content) return null;

            // 1. Clonar contenido base del simbolo
            var contenido;
            if (Array.isArray(symbol.content)) {
                contenido = JSON.parse(JSON.stringify(symbol.content));
            } else {
                contenido = [JSON.parse(JSON.stringify(symbol.content))];
            }

            // 2. Aplicar overrides de la variante (si no es default)
            var variantKey = element.variant || symbol.default_variant || 'default';
            var variants = symbol.variants || {};

            if (variantKey !== 'default' && variants[variantKey] && variants[variantKey].overrides) {
                var variantOverrides = variants[variantKey].overrides;
                if (Object.keys(variantOverrides).length > 0) {
                    this.aplicarOverridesRecursivo(contenido, variantOverrides);
                }
            }

            // 3. Aplicar overrides de instancia
            if (element.overrides && Object.keys(element.overrides).length > 0) {
                this.aplicarOverridesRecursivo(contenido, element.overrides);
            }

            return contenido;
        },

        /**
         * Obtener ID de instancia en servidor (buscar en registro de instancias)
         */
        getInstanceId: function(elementId) {
            // Por ahora retornar elementId como ID
            // En una implementacion completa, buscar en el mapa de instancias
            var store = Alpine.store('vbp');
            if (store && store.postId) {
                // TODO: implementar lookup en servidor si es necesario
            }
            return elementId;
        },

        // =============================================
        // SWAP INSTANCE - Cambiar simbolo de instancia
        // =============================================

        /**
         * Obtener sugerencias de swap para una instancia
         * @param {string} elementId - ID del elemento instancia
         * @returns {Promise} Lista de simbolos similares con puntuacion de compatibilidad
         */
        obtenerSugerenciasSwap: function(elementId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.resolve([]);
            }

            var instanceId = this.getInstanceId(elementId);

            return fetch(VBP_Config.restUrl + 'instances/' + instanceId + '/swap-suggestions', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    return data.data.suggestions || [];
                }
                return [];
            })
            .catch(function(error) {
                vbpLog.warn('[VBPSymbols] Error obteniendo sugerencias swap:', error);
                return [];
            });
        },

        /**
         * Verificar compatibilidad antes de swap
         * @param {string} elementId - ID del elemento instancia
         * @param {number} targetSymbolId - ID del simbolo destino
         * @returns {Promise} Resultado de compatibilidad
         */
        verificarCompatibilidadSwap: function(elementId, targetSymbolId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia'));
            }

            var instanceId = this.getInstanceId(elementId);

            return fetch(VBP_Config.restUrl + 'instances/' + instanceId + '/check-compatibility', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    target_symbol_id: parseInt(targetSymbolId, 10)
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    return data.data;
                }
                throw new Error(data.message || 'Error verificando compatibilidad');
            });
        },

        /**
         * Ejecutar swap de instancia a otro simbolo
         * @param {string} elementId - ID del elemento instancia
         * @param {number} nuevoSymbolId - ID del nuevo simbolo
         * @param {boolean} preservarCompatibles - Si preservar overrides compatibles
         * @returns {Promise} Resultado del swap
         */
        swapInstancia: function(elementId, nuevoSymbolId, preservarCompatibles) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                return Promise.reject(new Error('Store VBP no disponible'));
            }

            var element = store.getElementById(elementId);

            if (!element || !this.esInstancia(elementId)) {
                return Promise.reject(new Error('No es una instancia de simbolo'));
            }

            preservarCompatibles = preservarCompatibles !== false;
            var instanceId = this.getInstanceId(elementId);
            var oldSymbolId = element.symbolId;

            return fetch(VBP_Config.restUrl + 'instances/' + instanceId + '/swap', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    new_symbol_id: parseInt(nuevoSymbolId, 10),
                    preserve_compatible: preservarCompatibles,
                    document_id: store.postId
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var resultData = data.data || data;

                    // Actualizar elemento local
                    element.symbolId = parseInt(nuevoSymbolId, 10);
                    element.symbolName = resultData.new_symbol_name || '';
                    element.name = (resultData.new_symbol_name || 'Symbol') + ' (instancia)';
                    element.overrides = resultData.preserved_overrides || {};
                    element._symbolVersion = resultData.new_symbol_version || 1;

                    // Si tenia variante, resetear a default
                    if (element.variant && element.variant !== 'default') {
                        element.variant = 'default';
                    }

                    // Actualizar cache de instancias
                    self.instances[elementId] = nuevoSymbolId;

                    store.markAsDirty();

                    // Emitir evento
                    document.dispatchEvent(new CustomEvent('vbp:instance:swapped', {
                        detail: {
                            elementId: elementId,
                            oldSymbolId: oldSymbolId,
                            newSymbolId: nuevoSymbolId,
                            preservedOverrides: resultData.preserved_overrides,
                            lostOverrides: resultData.lost_overrides
                        }
                    }));

                    // Notificar si se perdieron overrides
                    var lostCount = resultData.lost_count || (resultData.lost_overrides ? resultData.lost_overrides.length : 0);
                    if (lostCount > 0) {
                        self.mostrarAviso('Se descartaron ' + lostCount + ' propiedades incompatibles');
                    }

                    self.mostrarExito('Simbolo cambiado a "' + resultData.new_symbol_name + '"');

                    return resultData;
                }
                throw new Error(data.message || 'Error al cambiar simbolo');
            });
        },

        /**
         * Obtener simbolos similares para sugerir swap
         * @param {number} symbolId - ID del simbolo actual
         * @param {number} limit - Limite de resultados
         * @returns {Promise} Lista de simbolos similares
         */
        obtenerSimbolosSimilares: function(symbolId, limit) {
            var self = this;
            limit = limit || 10;

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/similar?limit=' + limit, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    return data.data.similar || [];
                }
                return [];
            });
        },

        /**
         * Mostrar aviso (warning)
         */
        mostrarAviso: function(mensaje) {
            if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbpToast')) {
                Alpine.store('vbpToast').warning(mensaje);
            } else {
                vbpLog.warn('[VBPSymbols] ' + mensaje);
            }
        },

        // =============================================
        // HELPERS
        // =============================================

        /**
         * Establecer valor en objeto anidado usando path
         */
        setNestedValue: function(obj, path, value) {
            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }

            current[parts[parts.length - 1]] = value;
        },

        /**
         * Obtener valor de objeto anidado usando path
         */
        getNestedValue: function(obj, path) {
            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length; i++) {
                if (current === null || current === undefined) return undefined;
                current = current[parts[i]];
            }

            return current;
        },

        /**
         * Eliminar valor de objeto anidado
         */
        deleteNestedValue: function(obj, path) {
            var parts = path.split('.');
            var current = obj;

            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) return;
                current = current[parts[i]];
            }

            delete current[parts[parts.length - 1]];

            // Limpiar objetos vacios hacia arriba
            this.limpiarObjetosVacios(obj, path);
        },

        /**
         * Limpiar objetos vacios en el path
         */
        limpiarObjetosVacios: function(obj, path) {
            var parts = path.split('.');
            parts.pop(); // Quitar ultimo elemento ya eliminado

            while (parts.length > 0) {
                var parentPath = parts.join('.');
                var parent = this.getNestedValue(obj, parentPath);

                if (parent && typeof parent === 'object' && Object.keys(parent).length === 0) {
                    this.deleteNestedValue(obj, parentPath);
                } else {
                    break;
                }

                parts.pop();
            }
        },

        /**
         * Programar guardado de overrides con debounce
         */
        programarGuardadoOverrides: function(elementId, overrides) {
            var self = this;

            // Agregar a cola de pendientes
            var pendingIndex = this.pendingSync.findIndex(function(p) { return p.elementId === elementId; });
            if (pendingIndex !== -1) {
                this.pendingSync[pendingIndex].overrides = overrides;
            } else {
                this.pendingSync.push({ elementId: elementId, overrides: overrides });
            }

            // Cancelar timer anterior
            if (this.saveOverridesTimer) {
                clearTimeout(this.saveOverridesTimer);
            }

            // Programar nuevo guardado
            this.saveOverridesTimer = setTimeout(function() {
                self.guardarOverridesPendientes();
            }, this.saveOverridesDelay);
        },

        /**
         * Guardar todos los overrides pendientes
         */
        guardarOverridesPendientes: function() {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store || this.pendingSync.length === 0) return;

            var pendientes = this.pendingSync.slice();
            this.pendingSync = [];

            // Guardar cada uno
            pendientes.forEach(function(item) {
                fetch(VBP_Config.restUrl + 'symbols/instances/' + item.elementId + '/overrides', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': VBP_Config.restNonce
                    },
                    body: JSON.stringify({
                        document_id: store.postId,
                        overrides: item.overrides
                    })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data.success) {
                        vbpLog.warn('[VBPSymbols] Error guardando overrides:', data.message);
                    }
                })
                .catch(function(error) {
                    vbpLog.warn('[VBPSymbols] Error en API overrides:', error);
                });
            });
        },

        /**
         * Mostrar notificacion de exito
         */
        mostrarExito: function(mensaje) {
            if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbpToast')) {
                Alpine.store('vbpToast').success(mensaje);
            } else {
                vbpLog.log('[VBPSymbols] ' + mensaje);
            }
        },

        /**
         * Mostrar notificacion de error
         */
        mostrarError: function(mensaje) {
            if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbpToast')) {
                Alpine.store('vbpToast').error(mensaje);
            } else {
                vbpLog.error('[VBPSymbols] ' + mensaje);
            }
        },

        // =============================================
        // NESTED SYMBOLS - METODOS DE ANIDACION
        // =============================================

        /**
         * Profundidad maxima de anidacion
         */
        MAX_NESTING_DEPTH: 5,

        /**
         * Verificar si se puede insertar un simbolo dentro de otro
         * @param {number|string} parentSymbolId - ID del simbolo padre
         * @param {number|string} childSymbolId - ID del simbolo a insertar
         * @returns {Promise<boolean>}
         */
        puedeAnidar: function(parentSymbolId, childSymbolId) {
            var self = this;

            return fetch(VBP_Config.restUrl + 'symbols/' + parentSymbolId + '/validate-nesting', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ child_symbol_id: parseInt(childSymbolId, 10) })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    if (!data.can_nest) {
                        vbpLog.warn('[VBPSymbols] No se puede anidar: ' + data.message);
                    }
                    return data.can_nest;
                }
                vbpLog.error('[VBPSymbols] Error validando anidacion:', data.message);
                return false;
            })
            .catch(function(error) {
                vbpLog.error('[VBPSymbols] Error en validacion de anidacion:', error);
                return false;
            });
        },

        /**
         * Obtener simbolos anidados de un simbolo
         * @param {number|string} symbolId - ID del simbolo
         * @param {boolean} recursive - Si buscar recursivamente
         * @returns {Promise<Array>}
         */
        obtenerSimbolosAnidados: function(symbolId, recursive) {
            recursive = recursive !== false;

            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/nested?recursive=' + recursive, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    return data.nested_symbols || [];
                }
                return [];
            })
            .catch(function(error) {
                vbpLog.warn('[VBPSymbols] Error obteniendo simbolos anidados:', error);
                return [];
            });
        },

        /**
         * Obtener simbolos padre que usan este simbolo
         * @param {number|string} symbolId - ID del simbolo
         * @returns {Promise<Array>}
         */
        obtenerSimbolosPadre: function(symbolId) {
            return fetch(VBP_Config.restUrl + 'symbols/' + symbolId + '/parents', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    return data.parent_symbols || [];
                }
                return [];
            })
            .catch(function(error) {
                vbpLog.warn('[VBPSymbols] Error obteniendo simbolos padre:', error);
                return [];
            });
        },

        /**
         * Resolver contenido con anidacion
         * @param {string} elementId - ID del elemento instancia
         * @param {number} profundidad - Profundidad actual de anidacion
         * @returns {object|null} Contenido resuelto o null
         */
        resolverContenidoAnidado: function(elementId, profundidad) {
            profundidad = profundidad || 0;
            var self = this;

            if (profundidad >= self.MAX_NESTING_DEPTH) {
                vbpLog.warn('[VBPSymbols] Profundidad maxima alcanzada en elemento:', elementId);
                return null;
            }

            var store = Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementById(elementId);

            if (!element || element.type !== '__symbol_instance__') {
                return null;
            }

            var symbol = self.obtenerSimbolo(element.symbolId);
            if (!symbol || !symbol.content) return null;

            // Clonar contenido profundamente
            var contenido = JSON.parse(JSON.stringify(symbol.content));

            // Aplicar overrides del nivel actual
            if (element.overrides && Object.keys(element.overrides).length > 0) {
                self.aplicarOverridesRecursivo(contenido, element.overrides);
            }

            // Resolver instancias anidadas recursivamente
            self.resolverInstanciasAnidadas(contenido, profundidad + 1);

            return contenido;
        },

        /**
         * Resolver instancias anidadas en el contenido
         * @param {Array} contenido - Array de elementos a procesar
         * @param {number} profundidad - Profundidad actual
         */
        resolverInstanciasAnidadas: function(contenido, profundidad) {
            var self = this;

            if (!Array.isArray(contenido)) return;

            contenido.forEach(function(elemento, index) {
                if (!elemento || typeof elemento !== 'object') return;

                if (elemento.type === '__symbol_instance__') {
                    var symbol = self.obtenerSimbolo(elemento.symbolId);
                    if (symbol && symbol.content) {
                        elemento._resolved = JSON.parse(JSON.stringify(symbol.content));
                        elemento._symbolName = symbol.name;
                        elemento._symbolVersion = symbol.version;
                        elemento._depth = profundidad;
                        elemento._maxDepthReached = (profundidad >= self.MAX_NESTING_DEPTH);

                        // Aplicar overrides de esta instancia
                        if (elemento.overrides && Object.keys(elemento.overrides).length > 0) {
                            self.aplicarOverridesRecursivo(elemento._resolved, elemento.overrides);
                        }

                        // Continuar resolviendo anidados si no hemos alcanzado el limite
                        if (profundidad < self.MAX_NESTING_DEPTH) {
                            self.resolverInstanciasAnidadas(elemento._resolved, profundidad + 1);
                        }
                    }
                } else if (elemento.children && Array.isArray(elemento.children)) {
                    self.resolverInstanciasAnidadas(elemento.children, profundidad);
                }
            });
        },

        /**
         * Verificar si un contenido tiene instancias anidadas
         * @param {Array} contenido - Contenido a verificar
         * @returns {boolean}
         */
        tieneInstanciasAnidadas: function(contenido) {
            var self = this;

            if (!Array.isArray(contenido)) return false;

            for (var i = 0; i < contenido.length; i++) {
                var elemento = contenido[i];
                if (!elemento || typeof elemento !== 'object') continue;

                if (elemento.type === '__symbol_instance__') {
                    return true;
                }

                if (elemento.children && self.tieneInstanciasAnidadas(elemento.children)) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Obtener profundidad de anidacion de una instancia
         * @param {string} elementId - ID del elemento
         * @returns {number} Profundidad (0 si no es instancia anidada)
         */
        obtenerProfundidadInstancia: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return 0;

            var element = store.getElementById(elementId);
            if (!element || element.type !== '__symbol_instance__') {
                return 0;
            }

            return element._depth || 0;
        },

        /**
         * Notificar cambio en simbolo para actualizar instancias anidadas
         * @param {number|string} symbolId - ID del simbolo modificado
         */
        notificarCambioSimbolo: function(symbolId) {
            var self = this;

            // Obtener simbolos padre que usan este
            self.obtenerSimbolosPadre(symbolId)
                .then(function(parentSymbols) {
                    if (parentSymbols && parentSymbols.length > 0) {
                        parentSymbols.forEach(function(parent) {
                            // Emitir evento para refrescar instancias del padre
                            document.dispatchEvent(new CustomEvent('vbp:symbol:nested-changed', {
                                detail: {
                                    symbolId: parent.id,
                                    changedChildId: symbolId,
                                    parentName: parent.name
                                }
                            }));

                            vbpLog.log('[VBPSymbols] Notificado cambio a simbolo padre:', parent.name);
                        });
                    }
                });

            // Actualizar instancias en el documento actual
            var instancias = self.obtenerInstanciasEnDocumento(symbolId);
            instancias.forEach(function(inst) {
                document.dispatchEvent(new CustomEvent('vbp:instance:content-changed', {
                    detail: { elementId: inst.id, symbolId: symbolId }
                }));
            });
        },

        /**
         * Obtener arbol de dependencias de un simbolo
         * @param {number|string} symbolId - ID del simbolo
         * @returns {Promise<object>} Arbol de dependencias
         */
        obtenerArbolDependencias: function(symbolId) {
            var self = this;
            var symbol = self.obtenerSimbolo(symbolId);

            if (!symbol) {
                return Promise.resolve(null);
            }

            return self.obtenerSimbolosAnidados(symbolId, true)
                .then(function(nested) {
                    return self.obtenerSimbolosPadre(symbolId)
                        .then(function(parents) {
                            return {
                                symbol: {
                                    id: symbol.id,
                                    name: symbol.name,
                                    version: symbol.version
                                },
                                nested: nested,
                                parents: parents,
                                hasNested: nested.length > 0,
                                hasParents: parents.length > 0
                            };
                        });
                });
        },

        // =============================================
        // METODOS DE IMPORT/EXPORT
        // =============================================

        /**
         * Exportar un simbolo como JSON
         * @param {number|string} symbolId - ID del simbolo
         * @returns {Promise<object>} Datos del simbolo exportado
         */
        exportSymbol: function(symbolId) {
            return this.exportSymbols([symbolId]);
        },

        /**
         * Exportar multiples simbolos como JSON
         * @param {Array<number|string>} symbolIds - IDs de simbolos a exportar
         * @returns {Promise<object>} Datos exportados
         */
        exportSymbols: function(symbolIds) {
            var self = this;

            if (!VBP_Config || !VBP_Config.restUrl) {
                return Promise.reject(new Error('VBP_Config no disponible'));
            }

            var idsString = symbolIds && symbolIds.length > 0 ? symbolIds.join(',') : '';
            var exportUrl = VBP_Config.restUrl + 'symbols/export';
            if (idsString) {
                exportUrl += '?ids=' + encodeURIComponent(idsString);
            }

            return fetch(exportUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Error al exportar: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data) {
                    vbpLog.log('[VBPSymbols] Exportados ' + (data.data.symbols ? data.data.symbols.length : 0) + ' simbolos');
                    return data.data;
                }
                throw new Error(data.message || 'Error en la exportacion');
            });
        },

        /**
         * Exportar toda la libreria de simbolos
         * @returns {Promise<object>} Datos exportados con todos los simbolos
         */
        exportLibrary: function() {
            return this.exportSymbols([]);
        },

        /**
         * Descargar simbolos como archivo JSON
         * @param {Array<number|string>} symbolIds - IDs de simbolos (vacio = todos)
         * @param {string} filename - Nombre del archivo (opcional)
         */
        downloadExport: function(symbolIds, filename) {
            var self = this;
            var exportFilename = filename || 'vbp-symbols-export-' + self.getTimestamp() + '.json';

            this.exportSymbols(symbolIds)
                .then(function(exportData) {
                    var jsonString = JSON.stringify(exportData, null, 2);
                    var downloadBlob = new Blob([jsonString], { type: 'application/json' });
                    var downloadUrl = URL.createObjectURL(downloadBlob);

                    var downloadLink = document.createElement('a');
                    downloadLink.href = downloadUrl;
                    downloadLink.download = exportFilename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    URL.revokeObjectURL(downloadUrl);

                    vbpLog.log('[VBPSymbols] Archivo descargado:', exportFilename);

                    document.dispatchEvent(new CustomEvent('vbp:symbols:exported', {
                        detail: {
                            filename: exportFilename,
                            symbolCount: exportData.symbols ? exportData.symbols.length : 0
                        }
                    }));
                })
                .catch(function(error) {
                    vbpLog.error('[VBPSymbols] Error descargando exportacion:', error);
                    document.dispatchEvent(new CustomEvent('vbp:symbols:export-error', {
                        detail: { error: error.message }
                    }));
                });
        },

        /**
         * Validar datos de importacion
         * @param {object} importData - Datos a validar
         * @returns {Promise<object>} Resultado de validacion
         */
        validateImportData: function(importData) {
            var self = this;

            if (!VBP_Config || !VBP_Config.restUrl) {
                return Promise.reject(new Error('VBP_Config no disponible'));
            }

            return fetch(VBP_Config.restUrl + 'symbols/import/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({ data: importData })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    return data.data;
                }
                return {
                    valid: false,
                    errors: [data.message || 'Error de validacion']
                };
            });
        },

        /**
         * Importar simbolos desde datos JSON
         * @param {object} importData - Datos de importacion (con array symbols)
         * @param {object} options - Opciones: { mode: 'merge'|'replace' }
         * @returns {Promise<object>} Resultado de la importacion
         */
        importSymbols: function(importData, options) {
            var self = this;
            var importOptions = options || {};
            var importMode = importOptions.mode || 'merge';

            if (!VBP_Config || !VBP_Config.restUrl) {
                return Promise.reject(new Error('VBP_Config no disponible'));
            }

            // Extraer array de simbolos del objeto de importacion
            var symbolsArray = importData.symbols || importData;
            if (!Array.isArray(symbolsArray)) {
                return Promise.reject(new Error('Formato de importacion invalido'));
            }

            return fetch(VBP_Config.restUrl + 'symbols/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    symbols: symbolsArray,
                    mode: importMode
                })
            })
            .then(function(response) {
                if (!response.ok) {
                    return response.json().then(function(errorData) {
                        throw new Error(errorData.message || 'Error en la importacion');
                    });
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data) {
                    vbpLog.log('[VBPSymbols] Importacion completada:', data.data);

                    // Recargar lista de simbolos
                    self.recargarSimbolos();

                    // Emitir evento
                    document.dispatchEvent(new CustomEvent('vbp:symbols:imported', {
                        detail: data.data
                    }));

                    return data.data;
                }
                throw new Error(data.message || 'Error en la importacion');
            });
        },

        /**
         * Importar simbolos desde archivo
         * @param {File} file - Archivo JSON a importar
         * @param {object} options - Opciones de importacion
         * @returns {Promise<object>} Resultado de la importacion
         */
        importFromFile: function(file, options) {
            var self = this;

            return new Promise(function(resolve, reject) {
                if (!file) {
                    reject(new Error('No se proporciono archivo'));
                    return;
                }

                if (!file.name.endsWith('.json')) {
                    reject(new Error('El archivo debe ser JSON'));
                    return;
                }

                var fileReader = new FileReader();

                fileReader.onload = function(loadEvent) {
                    try {
                        var jsonContent = JSON.parse(loadEvent.target.result);

                        // Validar primero
                        self.validateImportData(jsonContent)
                            .then(function(validationResult) {
                                if (!validationResult.valid) {
                                    var validationErrorMessage = 'Archivo invalido: ' + (validationResult.errors || []).join(', ');
                                    reject(new Error(validationErrorMessage));
                                    return;
                                }

                                // Proceder con importacion
                                return self.importSymbols(jsonContent, options);
                            })
                            .then(function(importResult) {
                                resolve(importResult);
                            })
                            .catch(function(importError) {
                                reject(importError);
                            });
                    } catch (parseError) {
                        reject(new Error('Error al parsear JSON: ' + parseError.message));
                    }
                };

                fileReader.onerror = function() {
                    reject(new Error('Error al leer el archivo'));
                };

                fileReader.readAsText(file);
            });
        },

        /**
         * Mostrar modal de importacion
         */
        showImportModal: function() {
            var self = this;

            // Verificar si existe un modal custom
            var existingModal = document.getElementById('vbp-symbols-import-modal');
            if (existingModal) {
                existingModal.style.display = 'flex';
                return;
            }

            // Crear modal de importacion
            var modalHtml = [
                '<div id="vbp-symbols-import-modal" class="vbp-modal vbp-symbols-import-modal">',
                '  <div class="vbp-modal-content">',
                '    <div class="vbp-modal-header">',
                '      <h3>Importar Simbolos</h3>',
                '      <button type="button" class="vbp-modal-close" aria-label="Cerrar">&times;</button>',
                '    </div>',
                '    <div class="vbp-modal-body">',
                '      <div class="vbp-import-dropzone" id="vbp-import-dropzone">',
                '        <div class="vbp-dropzone-content">',
                '          <span class="vbp-dropzone-icon">📦</span>',
                '          <p>Arrastra un archivo JSON aqui</p>',
                '          <p class="vbp-dropzone-or">o</p>',
                '          <button type="button" class="vbp-btn vbp-btn-primary" id="vbp-import-select-btn">Seleccionar archivo</button>',
                '          <input type="file" id="vbp-import-file-input" accept=".json" style="display:none">',
                '        </div>',
                '      </div>',
                '      <div class="vbp-import-options">',
                '        <label class="vbp-import-option">',
                '          <input type="radio" name="vbp-import-mode" value="merge" checked>',
                '          <span>Combinar (no sobrescribir existentes)</span>',
                '        </label>',
                '        <label class="vbp-import-option">',
                '          <input type="radio" name="vbp-import-mode" value="replace">',
                '          <span>Reemplazar (sobrescribir existentes)</span>',
                '        </label>',
                '      </div>',
                '      <div class="vbp-import-preview" id="vbp-import-preview" style="display:none">',
                '        <h4>Vista previa:</h4>',
                '        <div class="vbp-import-preview-content"></div>',
                '      </div>',
                '      <div class="vbp-import-result" id="vbp-import-result" style="display:none"></div>',
                '    </div>',
                '    <div class="vbp-modal-footer">',
                '      <button type="button" class="vbp-btn vbp-btn-secondary" id="vbp-import-cancel-btn">Cancelar</button>',
                '      <button type="button" class="vbp-btn vbp-btn-primary" id="vbp-import-confirm-btn" disabled>Importar</button>',
                '    </div>',
                '  </div>',
                '</div>'
            ].join('\n');

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            var importModal = document.getElementById('vbp-symbols-import-modal');
            var fileInput = document.getElementById('vbp-import-file-input');
            var selectBtn = document.getElementById('vbp-import-select-btn');
            var confirmBtn = document.getElementById('vbp-import-confirm-btn');
            var cancelBtn = document.getElementById('vbp-import-cancel-btn');
            var closeBtn = importModal.querySelector('.vbp-modal-close');
            var dropzone = document.getElementById('vbp-import-dropzone');
            var previewContainer = document.getElementById('vbp-import-preview');
            var resultContainer = document.getElementById('vbp-import-result');

            var pendingImportData = null;

            // Cerrar modal
            function closeModal() {
                importModal.style.display = 'none';
                pendingImportData = null;
                previewContainer.style.display = 'none';
                resultContainer.style.display = 'none';
                confirmBtn.disabled = true;
            }

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            importModal.addEventListener('click', function(clickEvent) {
                if (clickEvent.target === importModal) closeModal();
            });

            // Seleccionar archivo
            selectBtn.addEventListener('click', function() {
                fileInput.click();
            });

            // Procesar archivo seleccionado
            function processSelectedFile(selectedFile) {
                if (!selectedFile || !selectedFile.name.endsWith('.json')) {
                    resultContainer.innerHTML = '<div class="vbp-import-error">El archivo debe ser JSON</div>';
                    resultContainer.style.display = 'block';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(readEvent) {
                    try {
                        var parsedData = JSON.parse(readEvent.target.result);
                        pendingImportData = parsedData;

                        // Mostrar preview
                        var symbolsCount = parsedData.symbols ? parsedData.symbols.length : 0;
                        var previewHtml = '<p>' + symbolsCount + ' simbolo(s) encontrados</p>';
                        if (parsedData.symbols && parsedData.symbols.length > 0) {
                            previewHtml += '<ul class="vbp-import-symbols-list">';
                            parsedData.symbols.slice(0, 10).forEach(function(symbolItem) {
                                previewHtml += '<li>' + (symbolItem.name || 'Sin nombre') + ' <span class="vbp-import-symbol-category">(' + (symbolItem.category || 'custom') + ')</span></li>';
                            });
                            if (parsedData.symbols.length > 10) {
                                previewHtml += '<li>... y ' + (parsedData.symbols.length - 10) + ' mas</li>';
                            }
                            previewHtml += '</ul>';
                        }

                        previewContainer.querySelector('.vbp-import-preview-content').innerHTML = previewHtml;
                        previewContainer.style.display = 'block';
                        confirmBtn.disabled = false;
                        resultContainer.style.display = 'none';

                    } catch (parseJsonError) {
                        resultContainer.innerHTML = '<div class="vbp-import-error">Error al parsear JSON: ' + parseJsonError.message + '</div>';
                        resultContainer.style.display = 'block';
                        previewContainer.style.display = 'none';
                        confirmBtn.disabled = true;
                    }
                };
                reader.readAsText(selectedFile);
            }

            fileInput.addEventListener('change', function(changeEvent) {
                var changedFile = changeEvent.target.files[0];
                processSelectedFile(changedFile);
            });

            // Drag and drop
            dropzone.addEventListener('dragover', function(dragOverEvent) {
                dragOverEvent.preventDefault();
                dropzone.classList.add('vbp-dropzone-active');
            });

            dropzone.addEventListener('dragleave', function() {
                dropzone.classList.remove('vbp-dropzone-active');
            });

            dropzone.addEventListener('drop', function(dropEvent) {
                dropEvent.preventDefault();
                dropzone.classList.remove('vbp-dropzone-active');
                var droppedFile = dropEvent.dataTransfer.files[0];
                processSelectedFile(droppedFile);
            });

            // Confirmar importacion
            confirmBtn.addEventListener('click', function() {
                if (!pendingImportData) return;

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Importando...';

                var selectedMode = document.querySelector('input[name="vbp-import-mode"]:checked').value;

                self.importSymbols(pendingImportData, { mode: selectedMode })
                    .then(function(importResultData) {
                        var successHtml = '<div class="vbp-import-success">';
                        successHtml += '<p>Importacion completada:</p>';
                        successHtml += '<ul>';
                        successHtml += '<li>' + importResultData.imported + ' simbolos creados</li>';
                        successHtml += '<li>' + importResultData.updated + ' simbolos actualizados</li>';
                        successHtml += '<li>' + importResultData.skipped + ' simbolos omitidos</li>';
                        successHtml += '</ul>';
                        if (importResultData.errors && importResultData.errors.length > 0) {
                            successHtml += '<p class="vbp-import-errors-title">Errores:</p>';
                            successHtml += '<ul class="vbp-import-errors-list">';
                            importResultData.errors.forEach(function(errorItem) {
                                successHtml += '<li>' + errorItem + '</li>';
                            });
                            successHtml += '</ul>';
                        }
                        successHtml += '</div>';

                        resultContainer.innerHTML = successHtml;
                        resultContainer.style.display = 'block';
                        previewContainer.style.display = 'none';

                        confirmBtn.textContent = 'Importar';
                        cancelBtn.textContent = 'Cerrar';
                    })
                    .catch(function(importError) {
                        resultContainer.innerHTML = '<div class="vbp-import-error">Error: ' + importError.message + '</div>';
                        resultContainer.style.display = 'block';
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Importar';
                    });
            });

            // Mostrar modal
            importModal.style.display = 'flex';
        },

        /**
         * Obtener timestamp formateado para nombres de archivo
         * @returns {string}
         */
        getTimestamp: function() {
            var now = new Date();
            return now.getFullYear() +
                   ('0' + (now.getMonth() + 1)).slice(-2) +
                   ('0' + now.getDate()).slice(-2) + '-' +
                   ('0' + now.getHours()).slice(-2) +
                   ('0' + now.getMinutes()).slice(-2);
        },

        /**
         * Configurar listeners de eventos
         */
        configurarEventos: function() {
            var self = this;

            // Escuchar cuando se guarda el documento para sincronizar instancias
            document.addEventListener('vbp:afterSave', function(e) {
                // Limpiar cola de pendientes ya que el documento se guardo
                self.pendingSync = [];
            });

            // Escuchar cuando se actualiza un simbolo para notificar instancias
            document.addEventListener('vbp:symbol:updated', function(e) {
                var detail = e.detail || {};
                if (detail.symbol) {
                    // Marcar instancias como desactualizadas si la version cambio
                    var instancias = self.obtenerInstanciasEnDocumento(detail.symbol.id);
                    instancias.forEach(function(inst) {
                        if (inst._symbolVersion && detail.symbol.version > inst._symbolVersion) {
                            // Notificar que la instancia esta desactualizada
                            document.dispatchEvent(new CustomEvent('vbp:instance:outdated', {
                                detail: { elementId: inst.id, symbol: detail.symbol }
                            }));
                        }
                    });

                    // Notificar a simbolos padre que un hijo cambio (nested symbols)
                    self.notificarCambioSimbolo(detail.symbol.id);
                }
            });

            // Escuchar solicitudes de recarga de simbolos
            document.addEventListener('vbp:symbols:reload', function() {
                self.recargarSimbolos();
            });

            // Escuchar cambios en simbolos anidados para actualizar padres
            document.addEventListener('vbp:symbol:nested-changed', function(e) {
                var detail = e.detail || {};
                vbpLog.log('[VBPSymbols] Simbolo padre necesita actualizar por cambio en hijo:', detail);

                // Sincronizar todas las instancias del simbolo padre afectado
                if (detail.symbolId) {
                    var instancias = self.obtenerInstanciasEnDocumento(detail.symbolId);
                    instancias.forEach(function(inst) {
                        self.sincronizarInstancia(inst.id);
                    });
                }
            });
        }
    };

    // =============================================
    // INICIALIZACION
    // =============================================

    // Auto-inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar un poco para que VBP_Config este disponible
            setTimeout(function() {
                window.VBPSymbols.init();
            }, 100);
        });
    } else {
        // DOM ya cargado
        setTimeout(function() {
            window.VBPSymbols.init();
        }, 100);
    }

    // Tambien escuchar alpine:init como fallback
    document.addEventListener('alpine:init', function() {
        setTimeout(function() {
            if (!window.VBPSymbols.inicializado) {
                window.VBPSymbols.init();
            }
        }, 200);
    });

})();
