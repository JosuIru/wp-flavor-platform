/**
 * VBP 3D Store - Integración con Alpine.js Store
 *
 * Extiende el store de VBP con capacidades 3D/WebGL,
 * permitiendo gestión reactiva de escenas, objetos y animaciones 3D.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Esperar a que Alpine.js esté disponible
     */
    function waitForAlpine(callback) {
        if (typeof Alpine !== 'undefined') {
            callback();
        } else {
            document.addEventListener('alpine:init', callback);
        }
    }

    /**
     * Generar ID único para objetos 3D
     */
    function generateObjectId() {
        return 'obj3d_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now().toString(36);
    }

    /**
     * Deep merge de objetos
     */
    function deepMerge(target, source) {
        var output = Object.assign({}, target);
        if (isObject(target) && isObject(source)) {
            Object.keys(source).forEach(function(key) {
                if (isObject(source[key])) {
                    if (!(key in target)) {
                        Object.assign(output, { [key]: source[key] });
                    } else {
                        output[key] = deepMerge(target[key], source[key]);
                    }
                } else {
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        return output;
    }

    function isObject(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    }

    /**
     * Registrar el store 3D en Alpine
     */
    waitForAlpine(function() {
        /**
         * Extender el store VBP existente con capacidades 3D
         */
        var existingStore = Alpine.store('vbp');

        if (existingStore) {
            // Extender el store existente
            Object.assign(existingStore, {
                // Estado 3D
                three: {
                    scenes: {},
                    activeSceneId: null,
                    selectedObjectId: null,
                    isLoaded: false,
                    isLoading: false,
                    loadError: null
                },

                /**
                 * Crear nueva escena 3D
                 * @param {string} elementId - ID del elemento contenedor
                 * @param {Object} config - Configuración de la escena
                 * @returns {Promise}
                 */
                createScene: function(elementId, config) {
                    var self = this;

                    if (!window.VBP3D) {
                        console.error('VBP3D no está cargado');
                        return Promise.reject(new Error('VBP3D no disponible'));
                    }

                    self.three.isLoading = true;

                    var scene = new window.VBP3D.Scene(elementId, config);

                    return scene.init().then(function(initializedScene) {
                        self.three.scenes[elementId] = {
                            instance: initializedScene,
                            config: config,
                            objects: [],
                            animations: []
                        };

                        self.three.activeSceneId = elementId;
                        self.three.isLoaded = true;
                        self.three.isLoading = false;

                        self._dispatch3DEvent('scene-created', { sceneId: elementId });

                        return initializedScene;
                    }).catch(function(error) {
                        self.three.isLoading = false;
                        self.three.loadError = error.message;
                        throw error;
                    });
                },

                /**
                 * Crear escena desde preset
                 * @param {string} elementId - ID del elemento contenedor
                 * @param {string} presetId - ID del preset
                 * @returns {Promise}
                 */
                createSceneFromPreset: function(elementId, presetId) {
                    var self = this;

                    if (!window.VBP3D) {
                        return Promise.reject(new Error('VBP3D no disponible'));
                    }

                    self.three.isLoading = true;

                    return window.VBP3D.createFromPreset(elementId, presetId).then(function(scene) {
                        var preset = window.VBP3D.PRESETS[presetId];

                        self.three.scenes[elementId] = {
                            instance: scene,
                            config: preset,
                            presetId: presetId,
                            objects: [],
                            animations: []
                        };

                        self.three.activeSceneId = elementId;
                        self.three.isLoaded = true;
                        self.three.isLoading = false;

                        self._dispatch3DEvent('scene-created', { sceneId: elementId, presetId: presetId });

                        return scene;
                    }).catch(function(error) {
                        self.three.isLoading = false;
                        self.three.loadError = error.message;
                        throw error;
                    });
                },

                /**
                 * Obtener escena activa
                 * @returns {Object|null}
                 */
                getActiveScene: function() {
                    if (!this.three.activeSceneId) return null;
                    var sceneData = this.three.scenes[this.three.activeSceneId];
                    return sceneData ? sceneData.instance : null;
                },

                /**
                 * Agregar objeto 3D a la escena activa
                 * @param {Object} objectConfig - Configuración del objeto
                 * @returns {string|null} ID del objeto creado
                 */
                addObject: function(objectConfig) {
                    var scene = this.getActiveScene();
                    if (!scene) {
                        console.warn('No hay escena activa');
                        return null;
                    }

                    var objectId = objectConfig.id || generateObjectId();
                    var object3d = scene.addObject(objectId, objectConfig);

                    if (object3d) {
                        var sceneData = this.three.scenes[this.three.activeSceneId];
                        sceneData.objects.push({
                            id: objectId,
                            config: objectConfig,
                            createdAt: Date.now()
                        });

                        this.three.selectedObjectId = objectId;
                        this.isDirty = true;

                        this._dispatch3DEvent('object-added', {
                            sceneId: this.three.activeSceneId,
                            objectId: objectId
                        });
                    }

                    return objectId;
                },

                /**
                 * Agregar primitiva 3D
                 * @param {string} primitiveType - Tipo de primitiva (box, sphere, etc)
                 * @param {Object} options - Opciones adicionales
                 * @returns {string|null}
                 */
                addPrimitive: function(primitiveType, options) {
                    var config = deepMerge({
                        type: 'primitive',
                        primitive: primitiveType,
                        position: { x: 0, y: 0, z: 0 },
                        rotation: { x: 0, y: 0, z: 0 },
                        scale: { x: 1, y: 1, z: 1 },
                        material: {
                            type: 'standard',
                            color: '#6366f1',
                            metalness: 0.3,
                            roughness: 0.7
                        }
                    }, options || {});

                    return this.addObject(config);
                },

                /**
                 * Cargar modelo 3D
                 * @param {string} modelUrl - URL del modelo
                 * @param {Object} options - Opciones adicionales
                 * @returns {string|null}
                 */
                loadModel: function(modelUrl, options) {
                    var config = deepMerge({
                        type: 'model',
                        src: modelUrl,
                        position: { x: 0, y: 0, z: 0 },
                        scale: 1,
                        autoPlay: true
                    }, options || {});

                    return this.addObject(config);
                },

                /**
                 * Crear texto 3D
                 * @param {string} text - Texto a crear
                 * @param {Object} options - Opciones adicionales
                 * @returns {string|null}
                 */
                addText3D: function(text, options) {
                    var config = deepMerge({
                        type: 'text',
                        text: text,
                        size: 1,
                        depth: 0.2,
                        material: {
                            color: '#ffffff',
                            metalness: 0.5,
                            roughness: 0.3
                        }
                    }, options || {});

                    return this.addObject(config);
                },

                /**
                 * Crear sistema de partículas
                 * @param {Object} options - Configuración de partículas
                 * @returns {string|null}
                 */
                addParticles: function(options) {
                    var config = deepMerge({
                        type: 'particles',
                        count: 1000,
                        size: 0.02,
                        color: '#ffffff',
                        spread: 10,
                        movement: 'float',
                        speed: 1
                    }, options || {});

                    return this.addObject(config);
                },

                /**
                 * Actualizar objeto seleccionado
                 * @param {Object} props - Propiedades a actualizar
                 */
                updateSelectedObject: function(props) {
                    var scene = this.getActiveScene();
                    if (!scene || !this.three.selectedObjectId) return;

                    scene.updateObject(this.three.selectedObjectId, props);

                    // Actualizar datos en el store
                    var sceneData = this.three.scenes[this.three.activeSceneId];
                    var objectData = sceneData.objects.find(function(obj) {
                        return obj.id === this.three.selectedObjectId;
                    }, this);

                    if (objectData) {
                        objectData.config = deepMerge(objectData.config, props);
                    }

                    this.isDirty = true;

                    this._dispatch3DEvent('object-updated', {
                        objectId: this.three.selectedObjectId,
                        props: props
                    });
                },

                /**
                 * Eliminar objeto seleccionado
                 */
                removeSelectedObject: function() {
                    var scene = this.getActiveScene();
                    if (!scene || !this.three.selectedObjectId) return;

                    var objectId = this.three.selectedObjectId;
                    scene.removeObject(objectId);

                    // Eliminar del store
                    var sceneData = this.three.scenes[this.three.activeSceneId];
                    sceneData.objects = sceneData.objects.filter(function(obj) {
                        return obj.id !== objectId;
                    });

                    this.three.selectedObjectId = null;
                    this.isDirty = true;

                    this._dispatch3DEvent('object-removed', { objectId: objectId });
                },

                /**
                 * Seleccionar objeto
                 * @param {string} objectId - ID del objeto
                 */
                selectObject: function(objectId) {
                    var scene = this.getActiveScene();
                    if (!scene) return;

                    scene.selectObject(objectId);
                    this.three.selectedObjectId = objectId;

                    this._dispatch3DEvent('object-selected', { objectId: objectId });
                },

                /**
                 * Deseleccionar objeto actual
                 */
                deselectObject: function() {
                    this.three.selectedObjectId = null;
                    this._dispatch3DEvent('object-deselected');
                },

                /**
                 * Agregar animación a objeto
                 * @param {string} objectId - ID del objeto
                 * @param {Object} animationConfig - Configuración de animación
                 */
                addAnimation: function(objectId, animationConfig) {
                    var scene = this.getActiveScene();
                    if (!scene) return;

                    var animation = scene.addAnimation(objectId, animationConfig);

                    if (animation) {
                        var sceneData = this.three.scenes[this.three.activeSceneId];
                        sceneData.animations.push({
                            objectId: objectId,
                            config: animationConfig,
                            createdAt: Date.now()
                        });

                        this.isDirty = true;

                        this._dispatch3DEvent('animation-added', {
                            objectId: objectId,
                            animation: animationConfig
                        });
                    }
                },

                /**
                 * Agregar animación de rotación continua
                 * @param {string} objectId - ID del objeto
                 * @param {Object} options - Opciones
                 */
                addRotationAnimation: function(objectId, options) {
                    var config = deepMerge({
                        keyframes: [
                            { time: 0, rotation: { y: 0 } },
                            { time: 1, rotation: { y: Math.PI * 2 } }
                        ],
                        duration: options.duration || 4,
                        loop: true,
                        easing: 'linear'
                    }, options || {});

                    this.addAnimation(objectId, config);
                },

                /**
                 * Agregar animación de flotación
                 * @param {string} objectId - ID del objeto
                 * @param {Object} options - Opciones
                 */
                addFloatAnimation: function(objectId, options) {
                    var amplitude = (options && options.amplitude) || 0.3;
                    var baseY = (options && options.baseY) || 0;

                    var config = {
                        keyframes: [
                            { time: 0, position: { y: baseY } },
                            { time: 0.5, position: { y: baseY + amplitude } },
                            { time: 1, position: { y: baseY } }
                        ],
                        duration: (options && options.duration) || 2,
                        loop: true,
                        easing: 'ease-in-out'
                    };

                    this.addAnimation(objectId, config);
                },

                /**
                 * Configurar cámara
                 * @param {Object} cameraConfig - Configuración de cámara
                 */
                setCamera: function(cameraConfig) {
                    var scene = this.getActiveScene();
                    if (!scene) return;

                    scene.setCamera(cameraConfig);

                    this._dispatch3DEvent('camera-updated', cameraConfig);
                },

                /**
                 * Establecer vista predefinida de cámara
                 * @param {string} view - Nombre de la vista
                 */
                setCameraView: function(view) {
                    var viewPositions = {
                        front: { position: { x: 0, y: 0, z: 5 }, lookAt: { x: 0, y: 0, z: 0 } },
                        back: { position: { x: 0, y: 0, z: -5 }, lookAt: { x: 0, y: 0, z: 0 } },
                        left: { position: { x: -5, y: 0, z: 0 }, lookAt: { x: 0, y: 0, z: 0 } },
                        right: { position: { x: 5, y: 0, z: 0 }, lookAt: { x: 0, y: 0, z: 0 } },
                        top: { position: { x: 0, y: 5, z: 0.01 }, lookAt: { x: 0, y: 0, z: 0 } },
                        bottom: { position: { x: 0, y: -5, z: 0.01 }, lookAt: { x: 0, y: 0, z: 0 } },
                        isometric: { position: { x: 3, y: 3, z: 3 }, lookAt: { x: 0, y: 0, z: 0 } }
                    };

                    var cameraConfig = viewPositions[view];
                    if (cameraConfig) {
                        this.setCamera(cameraConfig);
                    }
                },

                /**
                 * Pausar escena
                 */
                pauseScene: function() {
                    var scene = this.getActiveScene();
                    if (scene) {
                        scene.pause();
                        this._dispatch3DEvent('scene-paused');
                    }
                },

                /**
                 * Reanudar escena
                 */
                playScene: function() {
                    var scene = this.getActiveScene();
                    if (scene) {
                        scene.play();
                        this._dispatch3DEvent('scene-playing');
                    }
                },

                /**
                 * Tomar captura de pantalla
                 * @returns {string|null} Data URL de la imagen
                 */
                takeScreenshot: function() {
                    var scene = this.getActiveScene();
                    return scene ? scene.takeScreenshot() : null;
                },

                /**
                 * Exportar escena como JSON
                 * @returns {Object|null}
                 */
                exportSceneJSON: function() {
                    var scene = this.getActiveScene();
                    if (!scene) return null;

                    var sceneData = this.three.scenes[this.three.activeSceneId];

                    return {
                        version: '1.0',
                        exportedAt: new Date().toISOString(),
                        config: sceneData.config,
                        objects: sceneData.objects,
                        animations: sceneData.animations,
                        sceneState: scene.exportScene()
                    };
                },

                /**
                 * Importar escena desde JSON
                 * @param {string} elementId - ID del contenedor
                 * @param {Object} sceneJSON - Datos JSON de la escena
                 * @returns {Promise}
                 */
                importSceneJSON: function(elementId, sceneJSON) {
                    var self = this;

                    return this.createScene(elementId, sceneJSON.config).then(function() {
                        // Recrear objetos
                        sceneJSON.objects.forEach(function(objData) {
                            self.addObject(objData.config);
                        });

                        // Recrear animaciones
                        sceneJSON.animations.forEach(function(animData) {
                            self.addAnimation(animData.objectId, animData.config);
                        });

                        self._dispatch3DEvent('scene-imported', { sceneId: elementId });
                    });
                },

                /**
                 * Destruir escena
                 * @param {string} sceneId - ID de la escena (opcional, usa activa si no se especifica)
                 */
                destroyScene: function(sceneId) {
                    var targetSceneId = sceneId || this.three.activeSceneId;
                    if (!targetSceneId) return;

                    var sceneData = this.three.scenes[targetSceneId];
                    if (sceneData && sceneData.instance) {
                        sceneData.instance.destroy();
                    }

                    delete this.three.scenes[targetSceneId];

                    if (this.three.activeSceneId === targetSceneId) {
                        this.three.activeSceneId = null;
                        this.three.selectedObjectId = null;
                    }

                    this._dispatch3DEvent('scene-destroyed', { sceneId: targetSceneId });
                },

                /**
                 * Obtener lista de objetos en la escena activa
                 * @returns {Array}
                 */
                getSceneObjects: function() {
                    if (!this.three.activeSceneId) return [];
                    var sceneData = this.three.scenes[this.three.activeSceneId];
                    return sceneData ? sceneData.objects : [];
                },

                /**
                 * Obtener objeto por ID
                 * @param {string} objectId - ID del objeto
                 * @returns {Object|null}
                 */
                getObjectById: function(objectId) {
                    var objects = this.getSceneObjects();
                    return objects.find(function(obj) {
                        return obj.id === objectId;
                    }) || null;
                },

                /**
                 * Duplicar objeto
                 * @param {string} objectId - ID del objeto a duplicar
                 * @returns {string|null} ID del nuevo objeto
                 */
                duplicateObject: function(objectId) {
                    var originalObject = this.getObjectById(objectId);
                    if (!originalObject) return null;

                    var newConfig = JSON.parse(JSON.stringify(originalObject.config));

                    // Offset la posición
                    if (newConfig.position) {
                        newConfig.position.x = (newConfig.position.x || 0) + 1;
                    } else {
                        newConfig.position = { x: 1, y: 0, z: 0 };
                    }

                    return this.addObject(newConfig);
                },

                /**
                 * Centrar cámara en objeto
                 * @param {string} objectId - ID del objeto
                 */
                focusOnObject: function(objectId) {
                    var scene = this.getActiveScene();
                    if (!scene) return;

                    var object3d = scene.objects.get(objectId);
                    if (!object3d) return;

                    var position = object3d.position;
                    this.setCamera({
                        lookAt: { x: position.x, y: position.y, z: position.z },
                        position: {
                            x: position.x + 3,
                            y: position.y + 2,
                            z: position.z + 3
                        }
                    });
                },

                /**
                 * Resetear transformación de objeto
                 * @param {string} objectId - ID del objeto (opcional, usa seleccionado)
                 */
                resetTransform: function(objectId) {
                    var targetId = objectId || this.three.selectedObjectId;
                    if (!targetId) return;

                    var scene = this.getActiveScene();
                    if (!scene) return;

                    scene.updateObject(targetId, {
                        position: { x: 0, y: 0, z: 0 },
                        rotation: { x: 0, y: 0, z: 0 },
                        scale: { x: 1, y: 1, z: 1 }
                    });

                    this._dispatch3DEvent('transform-reset', { objectId: targetId });
                },

                /**
                 * Aplicar preset de material
                 * @param {string} presetName - Nombre del preset
                 */
                applyMaterialPreset: function(presetName) {
                    if (!this.three.selectedObjectId) return;

                    var presets = {
                        'gold': { color: '#ffd700', metalness: 1, roughness: 0.2 },
                        'silver': { color: '#c0c0c0', metalness: 1, roughness: 0.3 },
                        'bronze': { color: '#cd7f32', metalness: 0.8, roughness: 0.4 },
                        'plastic-red': { color: '#e63946', metalness: 0, roughness: 0.5 },
                        'plastic-blue': { color: '#457b9d', metalness: 0, roughness: 0.5 },
                        'glass': { color: '#ffffff', metalness: 0, roughness: 0, opacity: 0.3 },
                        'rubber': { color: '#2d3436', metalness: 0, roughness: 0.9 },
                        'wood': { color: '#8b4513', metalness: 0, roughness: 0.8 },
                        'marble': { color: '#f5f5f5', metalness: 0.1, roughness: 0.3 },
                        'neon': { color: '#00ff88', metalness: 0, roughness: 0.5, emissive: '#00ff88', emissiveIntensity: 0.5 }
                    };

                    var preset = presets[presetName];
                    if (preset) {
                        this.updateSelectedObject({ material: preset });
                    }
                },

                /**
                 * Dispatch evento 3D personalizado
                 * @private
                 */
                _dispatch3DEvent: function(eventName, detail) {
                    document.dispatchEvent(new CustomEvent('vbp-3d-' + eventName, {
                        detail: detail || {}
                    }));
                }
            });

            console.log('[VBP] Store 3D extendido correctamente');
        } else {
            // Crear store 3D standalone si el principal no existe
            Alpine.store('vbp3d', {
                scenes: {},
                activeSceneId: null,
                selectedObjectId: null,
                isLoaded: false,
                isLoading: false,

                // Los mismos métodos que arriba pero sin 'this.three'
                createScene: function(elementId, config) {
                    // Implementación simplificada
                    console.log('Crear escena 3D:', elementId);
                }
            });

            console.log('[VBP] Store 3D standalone creado');
        }
    });

    /**
     * Escuchar eventos de la escena 3D
     */
    document.addEventListener('vbp-3d-object-selected', function(event) {
        if (typeof Alpine !== 'undefined') {
            var store = Alpine.store('vbp');
            if (store && store.three) {
                store.three.selectedObjectId = event.detail.objectId;
            }
        }
    });

    document.addEventListener('vbp-3d-scene-ready', function(event) {
        if (typeof Alpine !== 'undefined') {
            var store = Alpine.store('vbp');
            if (store && store.three) {
                store.three.isLoaded = true;
                store.three.isLoading = false;
            }
        }
    });

    /**
     * Componente Alpine para bloques 3D en el editor
     */
    waitForAlpine(function() {
        Alpine.data('vbp3dBlock', function(initialConfig) {
            return {
                sceneId: null,
                isInitialized: false,
                showPrimitivePanel: false,
                selectedPrimitive: null,

                init: function() {
                    var self = this;
                    this.sceneId = 'scene-' + Math.random().toString(36).substr(2, 9);

                    this.$nextTick(function() {
                        self.initializeScene();
                    });
                },

                initializeScene: function() {
                    var self = this;
                    var store = Alpine.store('vbp');

                    if (!store || !store.createScene) {
                        console.warn('Store VBP no disponible para 3D');
                        return;
                    }

                    var container = this.$refs.sceneContainer;
                    if (!container) return;

                    container.id = this.sceneId;

                    var config = initialConfig || {};
                    store.createScene(this.sceneId, config).then(function() {
                        self.isInitialized = true;
                    }).catch(function(error) {
                        console.error('Error inicializando escena 3D:', error);
                    });
                },

                addPrimitive: function(primitiveType) {
                    var store = Alpine.store('vbp');
                    if (store && store.addPrimitive) {
                        store.addPrimitive(primitiveType);
                    }
                    this.showPrimitivePanel = false;
                },

                get primitives() {
                    return window.VBP3D ? Object.keys(window.VBP3D.PRIMITIVES) : [];
                },

                getPrimitiveIcon: function(type) {
                    if (window.VBP3D && window.VBP3D.PRIMITIVES[type]) {
                        return window.VBP3D.PRIMITIVES[type].icon;
                    }
                    return '◆';
                },

                getPrimitiveLabel: function(type) {
                    if (window.VBP3D && window.VBP3D.PRIMITIVES[type]) {
                        return window.VBP3D.PRIMITIVES[type].label;
                    }
                    return type;
                }
            };
        });
    });

})();
