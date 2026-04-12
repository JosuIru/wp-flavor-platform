/**
 * VBP 3D Scene - Soporte WebGL/Three.js para Visual Builder Pro
 *
 * Permite crear escenas 3D interactivas con primitivas, modelos,
 * textos 3D y animaciones integradas con el Animation Builder.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    var THREE_VERSION = '0.160.0';
    var THREE_CDN_BASE = 'https://cdn.jsdelivr.net/npm/three@' + THREE_VERSION;

    function get3DAssetCandidates() {
        var assetsUrl = window.VBP_Config && window.VBP_Config.assetsUrl
            ? window.VBP_Config.assetsUrl
            : '';
        var localBase = assetsUrl ? assetsUrl.replace(/\/?$/, '/') + 'vendor/three/' : '';
        var localFont = localBase ? localBase + 'examples/fonts/helvetiker_regular.typeface.json' : null;

        return {
            modules: [
                localBase ? {
                    core: localBase + 'build/three.module.min.js',
                    orbitControls: localBase + 'examples/controls/OrbitControls.js',
                    gltfLoader: localBase + 'examples/loaders/GLTFLoader.js',
                    fontLoader: localBase + 'examples/loaders/FontLoader.js',
                    textGeometry: localBase + 'examples/geometries/TextGeometry.js',
                    importMap: {
                        imports: {
                            'three': localBase + 'build/three.module.min.js',
                            'three/addons/': localBase + 'examples/'
                        }
                    }
                } : null,
                {
                    core: THREE_CDN_BASE + '/build/three.module.min.js',
                    orbitControls: THREE_CDN_BASE + '/examples/jsm/controls/OrbitControls.js',
                    gltfLoader: THREE_CDN_BASE + '/examples/jsm/loaders/GLTFLoader.js',
                    fontLoader: THREE_CDN_BASE + '/examples/jsm/loaders/FontLoader.js',
                    textGeometry: THREE_CDN_BASE + '/examples/jsm/geometries/TextGeometry.js',
                    importMap: null
                }
            ].filter(Boolean),
            fonts: [localFont, THREE_CDN_BASE + '/examples/fonts/helvetiker_regular.typeface.json'].filter(Boolean)
        };
    }

    function ensureImportMap(importMapConfig) {
        if (!importMapConfig || !importMapConfig.imports) {
            return;
        }

        var existing = document.querySelector('script[data-vbp-3d-importmap="1"]');
        if (existing) {
            return;
        }

        var script = document.createElement('script');
        script.type = 'importmap';
        script.setAttribute('data-vbp-3d-importmap', '1');
        script.textContent = JSON.stringify(importMapConfig);
        document.head.appendChild(script);
    }

    /**
     * Definiciones de primitivas 3D
     */
    var PRIMITIVES_3D = {
        'box': {
            geometry: 'BoxGeometry',
            params: [1, 1, 1],
            label: 'Cubo',
            icon: '□'
        },
        'sphere': {
            geometry: 'SphereGeometry',
            params: [0.5, 32, 32],
            label: 'Esfera',
            icon: '○'
        },
        'cylinder': {
            geometry: 'CylinderGeometry',
            params: [0.5, 0.5, 1, 32],
            label: 'Cilindro',
            icon: '⬭'
        },
        'plane': {
            geometry: 'PlaneGeometry',
            params: [1, 1],
            label: 'Plano',
            icon: '▭'
        },
        'torus': {
            geometry: 'TorusGeometry',
            params: [0.5, 0.2, 16, 100],
            label: 'Toro',
            icon: '◯'
        },
        'cone': {
            geometry: 'ConeGeometry',
            params: [0.5, 1, 32],
            label: 'Cono',
            icon: '△'
        },
        'dodecahedron': {
            geometry: 'DodecahedronGeometry',
            params: [0.5],
            label: 'Dodecaedro',
            icon: '⬡'
        },
        'icosahedron': {
            geometry: 'IcosahedronGeometry',
            params: [0.5],
            label: 'Icosaedro',
            icon: '⬢'
        },
        'octahedron': {
            geometry: 'OctahedronGeometry',
            params: [0.5],
            label: 'Octaedro',
            icon: '◇'
        },
        'tetrahedron': {
            geometry: 'TetrahedronGeometry',
            params: [0.5],
            label: 'Tetraedro',
            icon: '▲'
        },
        'ring': {
            geometry: 'RingGeometry',
            params: [0.3, 0.5, 32],
            label: 'Anillo',
            icon: '◎'
        },
        'capsule': {
            geometry: 'CapsuleGeometry',
            params: [0.25, 0.5, 4, 16],
            label: 'Cápsula',
            icon: '⬬'
        }
    };

    /**
     * Tipos de materiales disponibles
     */
    var MATERIAL_TYPES = {
        'basic': {
            type: 'MeshBasicMaterial',
            label: 'Básico',
            description: 'Sin iluminación, color plano'
        },
        'standard': {
            type: 'MeshStandardMaterial',
            label: 'Estándar',
            description: 'PBR con metalness y roughness'
        },
        'phong': {
            type: 'MeshPhongMaterial',
            label: 'Phong',
            description: 'Reflexión especular brillante'
        },
        'lambert': {
            type: 'MeshLambertMaterial',
            label: 'Lambert',
            description: 'Difuso mate sin brillo'
        },
        'physical': {
            type: 'MeshPhysicalMaterial',
            label: 'Físico',
            description: 'PBR avanzado con clearcoat'
        },
        'toon': {
            type: 'MeshToonMaterial',
            label: 'Cartoon',
            description: 'Estilo cel-shading'
        },
        'normal': {
            type: 'MeshNormalMaterial',
            label: 'Normal',
            description: 'Visualiza normales como colores'
        },
        'wireframe': {
            type: 'MeshBasicMaterial',
            label: 'Wireframe',
            description: 'Solo aristas visibles',
            wireframe: true
        }
    };

    /**
     * Presets de escenas predefinidas
     */
    var SCENE_PRESETS = {
        'product-showcase': {
            name: 'Showcase de Producto',
            description: 'Escenario tipo estudio fotográfico',
            camera: { position: { x: 0, y: 1, z: 3 }, fov: 50 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.4 },
                directional: [
                    { color: '#ffffff', intensity: 0.8, position: { x: 2, y: 2, z: 2 } },
                    { color: '#87ceeb', intensity: 0.3, position: { x: -2, y: 1, z: -1 } }
                ]
            },
            background: { type: 'gradient', from: '#f5f5f5', to: '#e0e0e0' },
            floor: { enabled: true, color: '#ffffff', reflectivity: 0.3 },
            controls: 'orbit'
        },
        'floating-cards': {
            name: 'Tarjetas Flotantes',
            description: 'Tarjetas 3D flotando en el espacio',
            camera: { position: { x: 0, y: 0, z: 5 }, fov: 60 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.6 },
                point: [
                    { color: '#ff6b6b', intensity: 0.5, position: { x: -3, y: 2, z: 2 } },
                    { color: '#4ecdc4', intensity: 0.5, position: { x: 3, y: -2, z: 2 } }
                ]
            },
            background: { type: 'solid', color: '#1a1a2e' },
            controls: 'orbit'
        },
        'particle-background': {
            name: 'Fondo de Partículas',
            description: 'Partículas flotantes animadas',
            camera: { position: { x: 0, y: 0, z: 5 }, fov: 75 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 1 }
            },
            background: { type: 'solid', color: '#0f0f23' },
            particles: {
                count: 1000,
                size: 0.02,
                color: '#ffffff',
                movement: 'float',
                speed: 0.5
            },
            controls: 'none'
        },
        'hero-3d': {
            name: 'Hero 3D',
            description: 'Escena para sección hero con modelo central',
            camera: { position: { x: 2, y: 1, z: 4 }, fov: 45 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.3 },
                directional: [
                    { color: '#ffd700', intensity: 1, position: { x: 5, y: 5, z: 5 } }
                ],
                hemisphere: { skyColor: '#87ceeb', groundColor: '#444444', intensity: 0.4 }
            },
            background: { type: 'gradient', from: '#667eea', to: '#764ba2' },
            controls: 'orbit',
            autoRotate: true
        },
        'gallery-3d': {
            name: 'Galería 3D',
            description: 'Galería de imágenes en espacio 3D',
            camera: { position: { x: 0, y: 0, z: 8 }, fov: 60 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.8 }
            },
            background: { type: 'solid', color: '#111111' },
            controls: 'fly'
        },
        'minimal': {
            name: 'Minimalista',
            description: 'Escena simple y limpia',
            camera: { position: { x: 0, y: 0, z: 5 }, fov: 50 },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.5 },
                directional: [
                    { color: '#ffffff', intensity: 0.5, position: { x: 1, y: 1, z: 1 } }
                ]
            },
            background: { type: 'solid', color: '#ffffff' },
            controls: 'orbit'
        }
    };

    /**
     * Efectos de post-procesado disponibles
     */
    var POST_EFFECTS = {
        'bloom': {
            name: 'Bloom',
            description: 'Resplandor brillante',
            defaults: { threshold: 0.5, strength: 1, radius: 0.5 }
        },
        'outline': {
            name: 'Contorno',
            description: 'Borde alrededor de objetos',
            defaults: { color: '#ffffff', thickness: 2 }
        },
        'vignette': {
            name: 'Viñeta',
            description: 'Oscurecimiento en bordes',
            defaults: { offset: 0.5, darkness: 1 }
        },
        'film': {
            name: 'Película',
            description: 'Efecto de grano de película',
            defaults: { noiseIntensity: 0.35, scanlineIntensity: 0.5 }
        },
        'glitch': {
            name: 'Glitch',
            description: 'Efecto de distorsión digital',
            defaults: { intensity: 0.5 }
        }
    };

    /**
     * Estado de carga de Three.js
     */
    var threeJsState = {
        loaded: false,
        loading: false,
        modules: {},
        loadPromise: null
    };

    /**
     * Registro de escenas activas
     */
    var activeScenes = new Map();

    /**
     * Cargar Three.js de forma lazy
     * @returns {Promise} Promesa que resuelve cuando Three.js está listo
     */
    function loadThreeJS() {
        if (window.THREE && window.VBP3DModules) {
            threeJsState.loaded = true;
            threeJsState.modules = window.VBP3DModules;
            return Promise.resolve(window.THREE);
        }

        if (threeJsState.loaded) {
            return Promise.resolve(window.THREE);
        }

        if (threeJsState.loadPromise) {
            return threeJsState.loadPromise;
        }

        threeJsState.loading = true;

        threeJsState.loadPromise = new Promise(function(resolve, reject) {
            var candidates = get3DAssetCandidates().modules;
            var script = document.createElement('script');
            script.type = 'module';
            script.textContent = [
                '(async function() {',
                '  var candidates = ' + JSON.stringify(candidates) + ';',
                '  var lastError = null;',
                '  for (var i = 0; i < candidates.length; i++) {',
                '    var candidate = candidates[i];',
                '    try {',
                '      if (candidate.importMap) {',
                '        var existing = document.querySelector(\'script[data-vbp-3d-importmap="1"]\');',
                '        if (!existing) {',
                '          var importMapScript = document.createElement("script");',
                '          importMapScript.type = "importmap";',
                '          importMapScript.setAttribute("data-vbp-3d-importmap", "1");',
                '          importMapScript.textContent = JSON.stringify(candidate.importMap);',
                '          document.head.appendChild(importMapScript);',
                '        }',
                '      }',
                '      var THREE = await import(candidate.core);',
                '      var OrbitControlsModule = await import(candidate.orbitControls);',
                '      var GLTFLoaderModule = await import(candidate.gltfLoader);',
                '      var FontLoaderModule = await import(candidate.fontLoader);',
                '      var TextGeometryModule = await import(candidate.textGeometry);',
                '      window.THREE = THREE;',
                '      window.VBP3DModules = {',
                '        OrbitControls: OrbitControlsModule.OrbitControls,',
                '        GLTFLoader: GLTFLoaderModule.GLTFLoader,',
                '        FontLoader: FontLoaderModule.FontLoader,',
                '        TextGeometry: TextGeometryModule.TextGeometry',
                '      };',
                '      window.dispatchEvent(new CustomEvent("vbp-threejs-loaded"));',
                '      return;',
                '    } catch (error) {',
                '      lastError = error;',
                '    }',
                '  }',
                '  window.dispatchEvent(new CustomEvent("vbp-threejs-error", { detail: { error: lastError ? String(lastError) : "unknown" } }));',
                '})();'
            ].join('\n');

            var loadHandler = function() {
                cleanup();
                threeJsState.loaded = true;
                threeJsState.loading = false;
                threeJsState.modules = window.VBP3DModules || {};
                resolve(window.THREE);
            };
            var errorHandler = function(event) {
                cleanup();
                threeJsState.loading = false;
                threeJsState.loadPromise = null;
                reject(new Error('Error cargando dependencias 3D: ' + ((event.detail && event.detail.error) || 'desconocido')));
            };
            var cleanup = function() {
                window.removeEventListener('vbp-threejs-loaded', loadHandler);
                window.removeEventListener('vbp-threejs-error', errorHandler);
            };

            window.addEventListener('vbp-threejs-loaded', loadHandler);
            window.addEventListener('vbp-threejs-error', errorHandler);
            document.head.appendChild(script);
        });

        return threeJsState.loadPromise;
    }

    /**
     * Clase principal para escenas 3D
     */
    function VBP3DScene(containerId, config) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.config = Object.assign({
            width: '100%',
            height: '400px',
            camera: {
                type: 'perspective',
                fov: 75,
                near: 0.1,
                far: 1000,
                position: { x: 0, y: 0, z: 5 }
            },
            lighting: {
                ambient: { color: '#ffffff', intensity: 0.5 },
                directional: { color: '#ffffff', intensity: 1, position: { x: 1, y: 1, z: 1 } }
            },
            background: '#000000',
            controls: 'orbit',
            autoRotate: false,
            autoRotateSpeed: 2,
            shadows: false,
            antialiasing: true
        }, config);

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.objects = new Map();
        this.animations = [];
        this.animationMixer = null;
        this.clock = null;
        this.animationFrameId = null;
        this.isPlaying = true;
        this.selectedObject = null;
        this.raycaster = null;
        this.mouse = null;
        this.composer = null;
        this.effects = [];

        this._boundOnResize = this._onResize.bind(this);
        this._boundOnClick = this._onClick.bind(this);
        this._boundOnMouseMove = this._onMouseMove.bind(this);
    }

    /**
     * Inicializar la escena
     * @returns {Promise}
     */
    VBP3DScene.prototype.init = function() {
        var self = this;

        // Mostrar indicador de carga mientras se carga Three.js
        this._showLoadingIndicator();

        return loadThreeJS().then(function(THREE) {
            if (!self.container) {
                self._hideLoadingIndicator();
                throw new Error('Contenedor no encontrado: ' + self.containerId);
            }

            // Ocultar indicador de carga
            self._hideLoadingIndicator();

            self._setupRenderer(THREE);
            self._setupScene(THREE);
            self._setupCamera(THREE);
            self._setupLighting(THREE);
            self._setupControls();
            self._setupInteraction(THREE);

            self.clock = new THREE.Clock();

            window.addEventListener('resize', self._boundOnResize);
            self.container.addEventListener('click', self._boundOnClick);
            self.container.addEventListener('mousemove', self._boundOnMouseMove);

            self._animate();

            activeScenes.set(self.containerId, self);

            document.dispatchEvent(new CustomEvent('vbp-3d-scene-ready', {
                detail: { sceneId: self.containerId, scene: self }
            }));

            return self;
        }).catch(function(error) {
            self._hideLoadingIndicator();
            self._showErrorIndicator(error.message);
            throw error;
        });
    };

    /**
     * Mostrar indicador de carga en el contenedor
     * @private
     */
    VBP3DScene.prototype._showLoadingIndicator = function() {
        if (!this.container) return;

        var loadingEl = document.createElement('div');
        loadingEl.className = 'vbp-3d-loading';
        loadingEl.innerHTML = [
            '<div class="vbp-3d-loading__spinner">',
            '  <svg viewBox="0 0 50 50" width="40" height="40">',
            '    <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="90,150" stroke-dashoffset="0">',
            '      <animateTransform attributeName="transform" type="rotate" dur="1s" values="0 25 25;360 25 25" repeatCount="indefinite"/>',
            '    </circle>',
            '  </svg>',
            '</div>',
            '<div class="vbp-3d-loading__text">Cargando escena 3D...</div>'
        ].join('');

        loadingEl.style.cssText = [
            'position: absolute',
            'top: 0',
            'left: 0',
            'right: 0',
            'bottom: 0',
            'display: flex',
            'flex-direction: column',
            'align-items: center',
            'justify-content: center',
            'background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
            'color: #a0aec0',
            'font-family: -apple-system, BlinkMacSystemFont, sans-serif',
            'font-size: 14px',
            'gap: 12px',
            'z-index: 10'
        ].join('; ');

        // Asegurar posición relativa en contenedor
        if (getComputedStyle(this.container).position === 'static') {
            this.container.style.position = 'relative';
        }

        this.container.appendChild(loadingEl);
        this._loadingElement = loadingEl;
    };

    /**
     * Ocultar indicador de carga
     * @private
     */
    VBP3DScene.prototype._hideLoadingIndicator = function() {
        if (this._loadingElement && this._loadingElement.parentNode) {
            this._loadingElement.remove();
            this._loadingElement = null;
        }
    };

    /**
     * Mostrar indicador de error
     * @private
     */
    VBP3DScene.prototype._showErrorIndicator = function(message) {
        if (!this.container) return;

        var errorEl = document.createElement('div');
        errorEl.className = 'vbp-3d-error';
        errorEl.innerHTML = [
            '<div class="vbp-3d-error__icon">⚠️</div>',
            '<div class="vbp-3d-error__text">Error cargando escena 3D</div>',
            '<div class="vbp-3d-error__details">' + this._escapeHtml(message) + '</div>'
        ].join('');

        errorEl.style.cssText = [
            'position: absolute',
            'top: 0',
            'left: 0',
            'right: 0',
            'bottom: 0',
            'display: flex',
            'flex-direction: column',
            'align-items: center',
            'justify-content: center',
            'background: #1a1a2e',
            'color: #fc8181',
            'font-family: -apple-system, BlinkMacSystemFont, sans-serif',
            'text-align: center',
            'padding: 20px',
            'gap: 8px',
            'z-index: 10'
        ].join('; ');

        this.container.appendChild(errorEl);
    };

    /**
     * Escapar HTML para prevenir XSS
     * @private
     */
    VBP3DScene.prototype._escapeHtml = function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Configurar el renderer
     * @private
     */
    VBP3DScene.prototype._setupRenderer = function(THREE) {
        this.renderer = new THREE.WebGLRenderer({
            antialias: this.config.antialiasing,
            alpha: true
        });

        var containerWidth = this.container.clientWidth || 800;
        var containerHeight = this.container.clientHeight || 400;

        this.renderer.setSize(containerWidth, containerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        if (this.config.shadows) {
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        }

        // Habilitar WebXR si está configurado
        if (this.config.enableAR || this.config.enableVR) {
            this.renderer.xr.enabled = true;
        }

        this.renderer.domElement.style.display = 'block';
        this.container.appendChild(this.renderer.domElement);

        // Añadir botón AR si está habilitado
        if (this.config.enableAR) {
            this._setupARButton();
        }
    };

    /**
     * Verificar soporte WebXR AR
     * @returns {Promise<boolean>}
     */
    VBP3DScene.prototype.checkARSupport = function() {
        if (!navigator.xr) {
            return Promise.resolve(false);
        }
        return navigator.xr.isSessionSupported('immersive-ar')
            .catch(function() { return false; });
    };

    /**
     * Configurar botón AR
     * @private
     */
    VBP3DScene.prototype._setupARButton = function() {
        var self = this;

        this.checkARSupport().then(function(supported) {
            if (!supported) {
                console.log('VBP 3D: WebXR AR no soportado en este dispositivo');
                return;
            }

            // Crear botón AR
            var arButton = document.createElement('button');
            arButton.className = 'vbp-3d-ar-button';
            arButton.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M5 9h2v6H5zm12 0h2v6h-2zm-6-3h2v12h-2zm-4 5h2v2H7zm8 0h2v2h-2z"/></svg> Ver en AR';
            arButton.title = 'Ver objeto en Realidad Aumentada';
            arButton.style.cssText = 'position:absolute;bottom:16px;left:50%;transform:translateX(-50%);' +
                'padding:12px 24px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);' +
                'color:#fff;border:none;border-radius:25px;cursor:pointer;font-size:14px;font-weight:600;' +
                'display:flex;align-items:center;gap:8px;box-shadow:0 4px 15px rgba(102,126,234,0.4);' +
                'transition:all 0.3s ease;z-index:100;';

            arButton.onmouseenter = function() {
                this.style.transform = 'translateX(-50%) scale(1.05)';
                this.style.boxShadow = '0 6px 20px rgba(102,126,234,0.5)';
            };
            arButton.onmouseleave = function() {
                this.style.transform = 'translateX(-50%) scale(1)';
                this.style.boxShadow = '0 4px 15px rgba(102,126,234,0.4)';
            };

            arButton.onclick = function() {
                self.startAR();
            };

            self.container.style.position = 'relative';
            self.container.appendChild(arButton);
            self.arButton = arButton;
        });
    };

    /**
     * Iniciar sesión AR
     * @returns {Promise}
     */
    VBP3DScene.prototype.startAR = function() {
        var self = this;

        if (!navigator.xr) {
            alert('Tu navegador no soporta WebXR. Prueba con Chrome en Android.');
            return Promise.reject(new Error('WebXR no soportado'));
        }

        return navigator.xr.requestSession('immersive-ar', {
            requiredFeatures: ['hit-test', 'dom-overlay'],
            domOverlay: { root: document.body }
        }).then(function(session) {
            self.xrSession = session;
            self.renderer.xr.setSession(session);

            // Cambiar texto del botón
            if (self.arButton) {
                self.arButton.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg> Salir de AR';
                self.arButton.onclick = function() {
                    self.stopAR();
                };
            }

            session.addEventListener('end', function() {
                self.xrSession = null;
                if (self.arButton) {
                    self.arButton.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M5 9h2v6H5zm12 0h2v6h-2zm-6-3h2v12h-2zm-4 5h2v2H7zm8 0h2v2h-2z"/></svg> Ver en AR';
                    self.arButton.onclick = function() {
                        self.startAR();
                    };
                }
            });

            console.log('VBP 3D: Sesión AR iniciada');
            return session;
        }).catch(function(err) {
            console.error('VBP 3D: Error al iniciar AR:', err);
            alert('No se pudo iniciar la experiencia AR. Asegúrate de dar permisos de cámara.');
            throw err;
        });
    };

    /**
     * Detener sesión AR
     */
    VBP3DScene.prototype.stopAR = function() {
        if (this.xrSession) {
            this.xrSession.end();
        }
    };

    /**
     * Configurar la escena
     * @private
     */
    VBP3DScene.prototype._setupScene = function(THREE) {
        this.scene = new THREE.Scene();
        this._applySceneBackground(this.config.background);
    };

    VBP3DScene.prototype._disposeSceneBackground = function() {
        if (this.scene && this.scene.background && this.scene.background.isTexture && this.scene.background.dispose) {
            this.scene.background.dispose();
        }
    };

    VBP3DScene.prototype._applySceneBackground = function(background) {
        var THREE = window.THREE;
        if (!this.scene || !THREE) {
            return;
        }

        this._disposeSceneBackground();

        if (!background || background === 'transparent' || background.type === 'transparent') {
            this.scene.background = null;
            return;
        }

        if (typeof background === 'string') {
            this.scene.background = new THREE.Color(background);
            return;
        }

        if (background.type === 'gradient') {
            var canvas = document.createElement('canvas');
            canvas.width = 2;
            canvas.height = 256;
            var ctx = canvas.getContext('2d');
            var gradient = ctx.createLinearGradient(0, 0, 0, 256);
            gradient.addColorStop(0, background.from || '#000000');
            gradient.addColorStop(1, background.to || '#333333');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, 2, 256);
            this.scene.background = new THREE.CanvasTexture(canvas);
            return;
        }

        if (background.type === 'solid') {
            this.scene.background = new THREE.Color(background.color || '#000000');
        }
    };

    /**
     * Configurar la cámara
     * @private
     */
    VBP3DScene.prototype._setupCamera = function(THREE) {
        var cameraConfig = this.config.camera;
        var aspect = this.container.clientWidth / this.container.clientHeight;

        if (cameraConfig.type === 'orthographic') {
            var frustumSize = cameraConfig.frustumSize || 10;
            this.camera = new THREE.OrthographicCamera(
                frustumSize * aspect / -2,
                frustumSize * aspect / 2,
                frustumSize / 2,
                frustumSize / -2,
                cameraConfig.near || 0.1,
                cameraConfig.far || 1000
            );
        } else {
            this.camera = new THREE.PerspectiveCamera(
                cameraConfig.fov || 75,
                aspect,
                cameraConfig.near || 0.1,
                cameraConfig.far || 1000
            );
        }

        var cameraPosition = cameraConfig.position || { x: 0, y: 0, z: 5 };
        this.camera.position.set(
            cameraPosition.x,
            cameraPosition.y,
            cameraPosition.z
        );

        if (cameraConfig.lookAt) {
            this.camera.lookAt(
                cameraConfig.lookAt.x || 0,
                cameraConfig.lookAt.y || 0,
                cameraConfig.lookAt.z || 0
            );
        }
    };

    /**
     * Configurar iluminación
     * @private
     */
    VBP3DScene.prototype._setupLighting = function(THREE) {
        var lightingConfig = this.config.lighting;

        // Luz ambiental
        if (lightingConfig.ambient) {
            var ambientLight = new THREE.AmbientLight(
                lightingConfig.ambient.color || '#ffffff',
                lightingConfig.ambient.intensity || 0.5
            );
            this.scene.add(ambientLight);
        }

        // Luz direccional
        if (lightingConfig.directional) {
            var directionalLights = Array.isArray(lightingConfig.directional)
                ? lightingConfig.directional
                : [lightingConfig.directional];

            directionalLights.forEach(function(dirConfig) {
                var directionalLight = new THREE.DirectionalLight(
                    dirConfig.color || '#ffffff',
                    dirConfig.intensity || 1
                );
                var position = dirConfig.position || { x: 1, y: 1, z: 1 };
                directionalLight.position.set(position.x, position.y, position.z);

                if (this.config.shadows) {
                    directionalLight.castShadow = true;
                    directionalLight.shadow.mapSize.width = 2048;
                    directionalLight.shadow.mapSize.height = 2048;
                }

                this.scene.add(directionalLight);
            }, this);
        }

        // Luz puntual
        if (lightingConfig.point) {
            var pointLights = Array.isArray(lightingConfig.point)
                ? lightingConfig.point
                : [lightingConfig.point];

            pointLights.forEach(function(pointConfig) {
                var pointLight = new THREE.PointLight(
                    pointConfig.color || '#ffffff',
                    pointConfig.intensity || 1,
                    pointConfig.distance || 100
                );
                var position = pointConfig.position || { x: 0, y: 2, z: 0 };
                pointLight.position.set(position.x, position.y, position.z);
                this.scene.add(pointLight);
            }, this);
        }

        // Luz hemisférica
        if (lightingConfig.hemisphere) {
            var hemisphereLight = new THREE.HemisphereLight(
                lightingConfig.hemisphere.skyColor || '#87ceeb',
                lightingConfig.hemisphere.groundColor || '#444444',
                lightingConfig.hemisphere.intensity || 0.5
            );
            this.scene.add(hemisphereLight);
        }

        // Luz spot
        if (lightingConfig.spot) {
            var spotLights = Array.isArray(lightingConfig.spot)
                ? lightingConfig.spot
                : [lightingConfig.spot];

            spotLights.forEach(function(spotConfig) {
                var spotLight = new THREE.SpotLight(
                    spotConfig.color || '#ffffff',
                    spotConfig.intensity || 1
                );
                var position = spotConfig.position || { x: 0, y: 5, z: 0 };
                spotLight.position.set(position.x, position.y, position.z);
                spotLight.angle = spotConfig.angle || Math.PI / 6;
                spotLight.penumbra = spotConfig.penumbra || 0.1;

                if (spotConfig.target) {
                    spotLight.target.position.set(
                        spotConfig.target.x || 0,
                        spotConfig.target.y || 0,
                        spotConfig.target.z || 0
                    );
                }

                this.scene.add(spotLight);
            }, this);
        }
    };

    /**
     * Configurar controles de cámara
     * @private
     */
    VBP3DScene.prototype._setupControls = function() {
        var controlsType = this.config.controls;

        if (this.controls && this.controls.dispose) {
            this.controls.dispose();
            this.controls = null;
        }

        if (controlsType === 'none') {
            return;
        }

        var OrbitControls = window.VBP3DModules && window.VBP3DModules.OrbitControls;

        if (controlsType === 'orbit' && OrbitControls) {
            this.controls = new OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.autoRotate = this.config.autoRotate || false;
            this.controls.autoRotateSpeed = this.config.autoRotateSpeed || 2;
            this.controls.enablePan = this.config.enablePan !== false;
            this.controls.enableZoom = this.config.enableZoom !== false;
            this.controls.minDistance = this.config.camera.minDistance || 1;
            this.controls.maxDistance = this.config.camera.maxDistance || 100;
        }
    };

    /**
     * Configurar interacción (raycast)
     * @private
     */
    VBP3DScene.prototype._setupInteraction = function(THREE) {
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
    };

    /**
     * Agregar objeto 3D a la escena
     * @param {string} objectId - ID único del objeto
     * @param {Object} config - Configuración del objeto
     * @returns {Object} Objeto Three.js creado
     */
    VBP3DScene.prototype.addObject = function(objectId, config) {
        var THREE = window.THREE;
        if (!THREE) {
            console.error('Three.js no está cargado');
            return null;
        }

        return this._createObjectInstance(THREE, objectId, config, null);
    };

    VBP3DScene.prototype._createObjectInstance = function(THREE, objectId, config, parent) {
        config = config || {};

        var objectType = config.type || 'primitive';
        var object3d = null;

        switch (objectType) {
            case 'primitive':
                object3d = this._createPrimitive(THREE, config);
                break;
            case 'model':
                this._loadModel(objectId, config, parent);
                return null; // Se agrega asíncronamente
            case 'text':
                this._createText3D(objectId, config, parent);
                return null; // Se agrega asíncronamente
            case 'group':
                object3d = this._createGroup(THREE, config);
                break;
            case 'particles':
                object3d = this._createParticles(THREE, config);
                break;
            case 'light':
                object3d = this._createLight(THREE, config);
                break;
            default:
                console.warn('Tipo de objeto desconocido:', objectType);
                return null;
        }

        if (object3d) {
            object3d.userData.vbpId = objectId;
            object3d.userData.config = config;

            this._applyTransform(object3d, config);
            this._applyInteractivity(object3d, config);

            if (parent && typeof parent.add === 'function') {
                parent.add(object3d);
            } else {
                this.scene.add(object3d);
            }
            this.objects.set(objectId, object3d);

            document.dispatchEvent(new CustomEvent('vbp-3d-object-added', {
                detail: { sceneId: this.containerId, objectId: objectId, object: object3d }
            }));
        }

        return object3d;
    };

    /**
     * Crear primitiva 3D
     * @private
     */
    VBP3DScene.prototype._createPrimitive = function(THREE, config) {
        var primitiveType = config.primitive || 'box';
        var primitiveConfig = PRIMITIVES_3D[primitiveType];

        if (!primitiveConfig) {
            console.warn('Primitiva desconocida:', primitiveType);
            return null;
        }

        var geometryParams = config.geometryParams || primitiveConfig.params;
        var GeometryCtor = THREE[primitiveConfig.geometry];

        if (typeof GeometryCtor !== 'function') {
            console.warn('Constructor de geometría no disponible:', primitiveConfig.geometry);
            return null;
        }

        var geometry = new GeometryCtor(...geometryParams);

        var material = this._createMaterial(THREE, config.material || {});
        var mesh = new THREE.Mesh(geometry, material);

        if (this.config.shadows && config.castShadow !== false) {
            mesh.castShadow = true;
            mesh.receiveShadow = true;
        }

        return mesh;
    };

    /**
     * Crear luz 3D
     * @private
     */
    VBP3DScene.prototype._createLight = function(THREE, config) {
        var lightType = config.lightType || 'directional';
        var color = config.color || '#ffffff';
        var intensity = config.intensity !== undefined ? config.intensity : 1;
        var light = null;

        switch (lightType) {
            case 'ambient':
                light = new THREE.AmbientLight(color, intensity);
                break;
            case 'point':
                light = new THREE.PointLight(color, intensity, config.distance || 0);
                break;
            case 'spot':
                light = new THREE.SpotLight(
                    color,
                    intensity,
                    config.distance || 0,
                    THREE.MathUtils.degToRad(config.angle || 60),
                    config.penumbra || 0.1
                );
                break;
            case 'hemisphere':
                light = new THREE.HemisphereLight(
                    config.skyColor || '#87ceeb',
                    config.groundColor || '#444444',
                    intensity
                );
                break;
            case 'directional':
            default:
                light = new THREE.DirectionalLight(color, intensity);
                break;
        }

        if (light && config.castShadow === true && light.shadow) {
            light.castShadow = true;
        }

        return light;
    };

    /**
     * Crear material
     * @private
     */
    VBP3DScene.prototype._createMaterial = function(THREE, materialConfig) {
        var materialType = materialConfig.type || 'standard';
        var materialDef = MATERIAL_TYPES[materialType] || MATERIAL_TYPES.standard;

        var materialOptions = {
            color: materialConfig.color || '#ff0000',
            transparent: materialConfig.opacity !== undefined && materialConfig.opacity < 1,
            opacity: materialConfig.opacity !== undefined ? materialConfig.opacity : 1,
            wireframe: materialDef.wireframe || materialConfig.wireframe || false,
            side: THREE.DoubleSide
        };

        // Propiedades específicas de materiales PBR
        if (materialType === 'standard' || materialType === 'physical') {
            materialOptions.metalness = materialConfig.metalness !== undefined ? materialConfig.metalness : 0.5;
            materialOptions.roughness = materialConfig.roughness !== undefined ? materialConfig.roughness : 0.5;
        }

        if (materialType === 'physical') {
            materialOptions.clearcoat = materialConfig.clearcoat || 0;
            materialOptions.clearcoatRoughness = materialConfig.clearcoatRoughness || 0;
        }

        if (materialType === 'phong') {
            materialOptions.shininess = materialConfig.shininess || 30;
        }

        // Texturas
        if (materialConfig.map) {
            var textureLoader = new THREE.TextureLoader();
            materialOptions.map = textureLoader.load(materialConfig.map);
        }

        if (materialConfig.normalMap) {
            var textureLoaderNormal = new THREE.TextureLoader();
            materialOptions.normalMap = textureLoaderNormal.load(materialConfig.normalMap);
        }

        if (materialConfig.emissive) {
            materialOptions.emissive = new THREE.Color(materialConfig.emissive);
            materialOptions.emissiveIntensity = materialConfig.emissiveIntensity || 1;
        }

        return new THREE[materialDef.type](materialOptions);
    };

    /**
     * Cargar modelo 3D externo
     * @private
     */
    VBP3DScene.prototype._loadModel = function(objectId, config, parent) {
        var self = this;
        var THREE = window.THREE;
        var modelSrc = config.src;

        if (!modelSrc) {
            console.error('No se especificó src para el modelo');
            return;
        }

        var extension = modelSrc.split('.').pop().toLowerCase();
        var GLTFLoader = window.VBP3DModules && window.VBP3DModules.GLTFLoader;

        if (!GLTFLoader) {
            console.error('GLTFLoader no disponible');
            return;
        }

        if (extension === 'glb' || extension === 'gltf') {
            var loader = new GLTFLoader();

            loader.load(
                modelSrc,
                function(gltf) {
                    var model = gltf.scene;
                    model.userData.vbpId = objectId;
                    model.userData.config = config;

                    if (config.scale) {
                        var scaleValue = typeof config.scale === 'number'
                            ? config.scale
                            : 1;
                        model.scale.setScalar(scaleValue);
                    }

                    self._applyTransform(model, config);

                    // Animaciones del modelo
                    if (gltf.animations && gltf.animations.length > 0) {
                        self.animationMixer = new THREE.AnimationMixer(model);
                        gltf.animations.forEach(function(clip) {
                            var action = self.animationMixer.clipAction(clip);
                            if (config.autoPlay !== false) {
                                action.play();
                            }
                        });
                    }

                    if (parent && typeof parent.add === 'function') {
                        parent.add(model);
                    } else {
                        self.scene.add(model);
                    }
                    self.objects.set(objectId, model);

                    document.dispatchEvent(new CustomEvent('vbp-3d-model-loaded', {
                        detail: { sceneId: self.containerId, objectId: objectId, model: model }
                    }));
                },
                function(progress) {
                    var percent = (progress.loaded / progress.total * 100).toFixed(0);
                    document.dispatchEvent(new CustomEvent('vbp-3d-model-progress', {
                        detail: { sceneId: self.containerId, objectId: objectId, progress: percent }
                    }));
                },
                function(error) {
                    console.error('Error cargando modelo:', error);
                    document.dispatchEvent(new CustomEvent('vbp-3d-model-error', {
                        detail: { sceneId: self.containerId, objectId: objectId, error: error }
                    }));
                }
            );
        } else {
            console.warn('Formato de modelo no soportado:', extension);
        }
    };

    /**
     * Crear texto 3D
     * @private
     */
    VBP3DScene.prototype._createText3D = function(objectId, config, parent) {
        var self = this;
        var THREE = window.THREE;
        var FontLoader = window.VBP3DModules && window.VBP3DModules.FontLoader;
        var TextGeometry = window.VBP3DModules && window.VBP3DModules.TextGeometry;

        if (!FontLoader || !TextGeometry) {
            console.error('FontLoader o TextGeometry no disponibles');
            return;
        }

        var loader = new FontLoader();
        var fontUrls = config.fontUrl ? [config.fontUrl] : get3DAssetCandidates().fonts;

        var tryLoadFont = function(index) {
            if (!fontUrls[index]) {
                console.error('No se pudo cargar ninguna fuente 3D');
                return;
            }

            loader.load(fontUrls[index], function(font) {
            var geometry = new TextGeometry(config.text || 'Hello 3D', {
                font: font,
                size: config.size || 1,
                height: config.depth || 0.2,
                curveSegments: config.curveSegments || 12,
                bevelEnabled: config.bevel !== false,
                bevelThickness: config.bevelThickness || 0.03,
                bevelSize: config.bevelSize || 0.02,
                bevelSegments: config.bevelSegments || 5
            });

            geometry.center();

            var material = self._createMaterial(THREE, config.material || { color: '#ffffff' });
            var textMesh = new THREE.Mesh(geometry, material);

            textMesh.userData.vbpId = objectId;
            textMesh.userData.config = config;

            self._applyTransform(textMesh, config);

            if (parent && typeof parent.add === 'function') {
                parent.add(textMesh);
            } else {
                self.scene.add(textMesh);
            }
            self.objects.set(objectId, textMesh);

            document.dispatchEvent(new CustomEvent('vbp-3d-text-created', {
                detail: { sceneId: self.containerId, objectId: objectId, mesh: textMesh }
            }));
            }, undefined, function() {
                tryLoadFont(index + 1);
            });
        };

        tryLoadFont(0);
    };

    /**
     * Crear grupo de objetos
     * @private
     */
    VBP3DScene.prototype._createGroup = function(THREE, config) {
        var group = new THREE.Group();

        if (config.children && Array.isArray(config.children)) {
            config.children.forEach(function(childConfig, index) {
                var childId = childConfig && childConfig.id
                    ? childConfig.id
                    : (config.id + '_child_' + index);
                this._createObjectInstance(THREE, childId, Object.assign({}, childConfig, { id: childId }), group);
            }, this);
        }

        return group;
    };

    /**
     * Crear sistema de partículas
     * @private
     */
    VBP3DScene.prototype._createParticles = function(THREE, config) {
        var count = config.count || 1000;
        var size = config.size || 0.02;
        var color = config.color || '#ffffff';
        var spread = config.spread || 10;

        var geometry = new THREE.BufferGeometry();
        var positions = new Float32Array(count * 3);
        var velocities = new Float32Array(count * 3);

        for (var particleIndex = 0; particleIndex < count; particleIndex++) {
            var baseIndex = particleIndex * 3;
            positions[baseIndex] = (Math.random() - 0.5) * spread;
            positions[baseIndex + 1] = (Math.random() - 0.5) * spread;
            positions[baseIndex + 2] = (Math.random() - 0.5) * spread;

            velocities[baseIndex] = (Math.random() - 0.5) * 0.01;
            velocities[baseIndex + 1] = (Math.random() - 0.5) * 0.01;
            velocities[baseIndex + 2] = (Math.random() - 0.5) * 0.01;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        var material = new THREE.PointsMaterial({
            color: new THREE.Color(color),
            size: size,
            transparent: true,
            opacity: config.opacity || 0.8,
            blending: THREE.AdditiveBlending,
            depthWrite: false
        });

        var particles = new THREE.Points(geometry, material);
        particles.userData.velocities = velocities;
        particles.userData.movement = config.movement || 'float';
        particles.userData.speed = config.speed || 1;

        return particles;
    };

    /**
     * Aplicar transformaciones
     * @private
     */
    VBP3DScene.prototype._applyTransform = function(object3d, config) {
        if (config.position) {
            object3d.position.set(
                config.position.x || 0,
                config.position.y || 0,
                config.position.z || 0
            );
        }

        if (config.rotation) {
            object3d.rotation.set(
                THREE.MathUtils.degToRad(config.rotation.x || 0),
                THREE.MathUtils.degToRad(config.rotation.y || 0),
                THREE.MathUtils.degToRad(config.rotation.z || 0)
            );
        }

        if (config.scale) {
            if (typeof config.scale === 'number') {
                object3d.scale.setScalar(config.scale);
            } else {
                object3d.scale.set(
                    config.scale.x || 1,
                    config.scale.y || 1,
                    config.scale.z || 1
                );
            }
        }
    };

    /**
     * Aplicar interactividad
     * @private
     */
    VBP3DScene.prototype._applyInteractivity = function(object3d, config) {
        if (config.onClick) {
            object3d.userData.onClick = config.onClick;
        }

        if (config.onHover) {
            object3d.userData.onHover = config.onHover;
        }

        if (config.draggable) {
            object3d.userData.draggable = true;
        }
    };

    /**
     * Actualizar objeto existente
     * @param {string} objectId - ID del objeto
     * @param {Object} props - Propiedades a actualizar
     */
    VBP3DScene.prototype.updateObject = function(objectId, props) {
        var object3d = this.objects.get(objectId);
        if (!object3d) {
            console.warn('Objeto no encontrado:', objectId);
            return;
        }

        if (props.position) {
            this._applyTransform(object3d, { position: props.position });
        }

        if (props.rotation) {
            this._applyTransform(object3d, { rotation: props.rotation });
        }

        if (props.scale) {
            this._applyTransform(object3d, { scale: props.scale });
        }

        if (props.material && object3d.material) {
            if (props.material.color) {
                object3d.material.color.set(props.material.color);
            }
            if (props.material.opacity !== undefined) {
                object3d.material.opacity = props.material.opacity;
                object3d.material.transparent = props.material.opacity < 1;
            }
            if (props.material.metalness !== undefined) {
                object3d.material.metalness = props.material.metalness;
            }
            if (props.material.roughness !== undefined) {
                object3d.material.roughness = props.material.roughness;
            }
            if (props.material.wireframe !== undefined) {
                object3d.material.wireframe = props.material.wireframe;
            }
            object3d.material.needsUpdate = true;
        }

        if (props.visible !== undefined) {
            object3d.visible = props.visible;
        }

        if (object3d.isLight) {
            if (props.color && object3d.color) {
                object3d.color.set(props.color);
            }
            if (props.intensity !== undefined) {
                object3d.intensity = props.intensity;
            }
            if (props.distance !== undefined && 'distance' in object3d) {
                object3d.distance = props.distance;
            }
            if (props.angle !== undefined && object3d.isSpotLight) {
                object3d.angle = window.THREE.MathUtils.degToRad(props.angle);
            }
            if (props.penumbra !== undefined && object3d.isSpotLight) {
                object3d.penumbra = props.penumbra;
            }
            if (props.castShadow !== undefined && 'castShadow' in object3d) {
                object3d.castShadow = !!props.castShadow;
            }
            if (props.skyColor && object3d.isHemisphereLight) {
                object3d.color.set(props.skyColor);
            }
            if (props.groundColor && object3d.isHemisphereLight && object3d.groundColor) {
                object3d.groundColor.set(props.groundColor);
            }
            if (props.position) {
                this._applyTransform(object3d, { position: props.position });
            }
        }

        object3d.userData.config = Object.assign({}, object3d.userData.config || {}, props || {});
    };

    /**
     * Eliminar objeto de la escena
     * @param {string} objectId - ID del objeto
     */
    VBP3DScene.prototype.removeObject = function(objectId) {
        var object3d = this.objects.get(objectId);
        if (!object3d) {
            return;
        }

        this.scene.remove(object3d);

        // Limpiar geometría y material
        if (object3d.geometry) {
            object3d.geometry.dispose();
        }
        if (object3d.material) {
            if (Array.isArray(object3d.material)) {
                object3d.material.forEach(function(mat) { mat.dispose(); });
            } else {
                object3d.material.dispose();
            }
        }

        this.objects.delete(objectId);

        document.dispatchEvent(new CustomEvent('vbp-3d-object-removed', {
            detail: { sceneId: this.containerId, objectId: objectId }
        }));
    };

    /**
     * Agregar animación a un objeto
     * @param {string} objectId - ID del objeto
     * @param {Object} animationConfig - Configuración de animación
     */
    VBP3DScene.prototype.addAnimation = function(objectId, animationConfig) {
        var object3d = this.objects.get(objectId);
        if (!object3d) {
            console.warn('Objeto no encontrado para animación:', objectId);
            return;
        }

        var animation = {
            objectId: objectId,
            object: object3d,
            keyframes: animationConfig.keyframes || [],
            duration: animationConfig.duration || 1,
            delay: animationConfig.delay || 0,
            loop: animationConfig.loop !== false,
            easing: animationConfig.easing || 'linear',
            currentTime: 0,
            playing: true
        };

        this.animations.push(animation);

        return animation;
    };

    /**
     * Configurar cámara
     * @param {Object} cameraConfig - Configuración de cámara
     */
    VBP3DScene.prototype.setCamera = function(cameraConfig) {
        if (cameraConfig.position) {
            this.camera.position.set(
                cameraConfig.position.x,
                cameraConfig.position.y,
                cameraConfig.position.z
            );
        }

        if (cameraConfig.lookAt) {
            this.camera.lookAt(
                cameraConfig.lookAt.x || 0,
                cameraConfig.lookAt.y || 0,
                cameraConfig.lookAt.z || 0
            );
        }

        if (cameraConfig.fov && this.camera.isPerspectiveCamera) {
            this.camera.fov = cameraConfig.fov;
            this.camera.updateProjectionMatrix();
        }
    };

    /**
     * Seleccionar objeto
     * @param {string} objectId - ID del objeto
     */
    VBP3DScene.prototype.selectObject = function(objectId) {
        if (this.selectedObject) {
            this._unhighlightObject(this.selectedObject);
        }

        var object3d = this.objects.get(objectId);
        if (object3d) {
            this.selectedObject = object3d;
            this._highlightObject(object3d);

            document.dispatchEvent(new CustomEvent('vbp-3d-object-selected', {
                detail: { sceneId: this.containerId, objectId: objectId, object: object3d }
            }));
        }
    };

    /**
     * Resaltar objeto
     * @private
     */
    VBP3DScene.prototype._highlightObject = function(object3d) {
        if (object3d.material) {
            object3d.userData.originalEmissive = object3d.material.emissive
                ? object3d.material.emissive.getHex()
                : 0;
            if (object3d.material.emissive) {
                object3d.material.emissive.setHex(0x333333);
            }
        }
    };

    /**
     * Quitar resaltado de objeto
     * @private
     */
    VBP3DScene.prototype._unhighlightObject = function(object3d) {
        if (object3d.material && object3d.material.emissive) {
            object3d.material.emissive.setHex(object3d.userData.originalEmissive || 0);
        }
    };

    /**
     * Bucle de animación
     * @private
     */
    VBP3DScene.prototype._animate = function() {
        var self = this;

        if (!this.isPlaying) {
            return;
        }

        this.animationFrameId = requestAnimationFrame(function() {
            self._animate();
        });

        var delta = this.clock.getDelta();

        // Actualizar controles
        if (this.controls && this.controls.update) {
            this.controls.update();
        }

        // Actualizar mixer de animaciones de modelos
        if (this.animationMixer) {
            this.animationMixer.update(delta);
        }

        // Actualizar animaciones personalizadas
        this._updateAnimations(delta);

        // Actualizar partículas
        this._updateParticles(delta);

        // Renderizar
        if (this.composer) {
            this.composer.render();
        } else {
            this.renderer.render(this.scene, this.camera);
        }
    };

    /**
     * Actualizar animaciones personalizadas
     * @private
     */
    VBP3DScene.prototype._updateAnimations = function(delta) {
        this.animations.forEach(function(animation) {
            if (!animation.playing) return;

            animation.currentTime += delta;

            if (animation.currentTime < animation.delay) {
                return;
            }

            var activeTime = animation.currentTime - animation.delay;

            if (activeTime >= animation.duration) {
                if (animation.loop) {
                    animation.currentTime = animation.delay + (activeTime % animation.duration);
                    activeTime = animation.currentTime - animation.delay;
                } else {
                    animation.currentTime = animation.delay + animation.duration;
                    activeTime = animation.duration;
                    animation.playing = false;
                }
            }

            var progress = animation.duration > 0 ? (activeTime / animation.duration) : 1;

            // Interpolar entre keyframes
            var keyframes = animation.keyframes;
            if (keyframes.length < 2) return;

            var currentKeyframeIndex = 0;
            for (var keyframeIndex = 0; keyframeIndex < keyframes.length - 1; keyframeIndex++) {
                if (progress >= keyframes[keyframeIndex].time &&
                    progress <= keyframes[keyframeIndex + 1].time) {
                    currentKeyframeIndex = keyframeIndex;
                    break;
                }
            }

            var fromKeyframe = keyframes[currentKeyframeIndex];
            var toKeyframe = keyframes[currentKeyframeIndex + 1];
            var keyframeProgress = (progress - fromKeyframe.time) / (toKeyframe.time - fromKeyframe.time);

            // Aplicar propiedades interpoladas
            if (fromKeyframe.rotation && toKeyframe.rotation) {
                ['x', 'y', 'z'].forEach(function(axis) {
                    if (fromKeyframe.rotation[axis] !== undefined || toKeyframe.rotation[axis] !== undefined) {
                        animation.object.rotation[axis] = this._lerp(
                            fromKeyframe.rotation[axis] || 0,
                            toKeyframe.rotation[axis] || 0,
                            keyframeProgress
                        );
                    }
                }, this);
            }

            if (fromKeyframe.position && toKeyframe.position) {
                animation.object.position.x = this._lerp(
                    fromKeyframe.position.x || 0,
                    toKeyframe.position.x || 0,
                    keyframeProgress
                );
                animation.object.position.y = this._lerp(
                    fromKeyframe.position.y || 0,
                    toKeyframe.position.y || 0,
                    keyframeProgress
                );
                animation.object.position.z = this._lerp(
                    fromKeyframe.position.z || 0,
                    toKeyframe.position.z || 0,
                    keyframeProgress
                );
            }

            if (fromKeyframe.scale !== undefined && toKeyframe.scale !== undefined) {
                if (typeof fromKeyframe.scale === 'object' || typeof toKeyframe.scale === 'object') {
                    ['x', 'y', 'z'].forEach(function(axis) {
                        var fromScale = typeof fromKeyframe.scale === 'object' ? (fromKeyframe.scale[axis] || 1) : fromKeyframe.scale;
                        var toScale = typeof toKeyframe.scale === 'object' ? (toKeyframe.scale[axis] || 1) : toKeyframe.scale;
                        animation.object.scale[axis] = this._lerp(fromScale, toScale, keyframeProgress);
                    }, this);
                } else {
                    var scaleLerped = this._lerp(fromKeyframe.scale, toKeyframe.scale, keyframeProgress);
                    animation.object.scale.setScalar(scaleLerped);
                }
            }

            if (fromKeyframe.opacity !== undefined && toKeyframe.opacity !== undefined && animation.object.material) {
                animation.object.material.opacity = this._lerp(fromKeyframe.opacity, toKeyframe.opacity, keyframeProgress);
                animation.object.material.transparent = animation.object.material.opacity < 1;
            }

            if (fromKeyframe.color && toKeyframe.color && animation.object.material && animation.object.material.color) {
                var THREE = window.THREE;
                var fromColor = new THREE.Color(fromKeyframe.color);
                var toColor = new THREE.Color(toKeyframe.color);
                animation.object.material.color.copy(fromColor.lerp(toColor, keyframeProgress));
            }
        }, this);
    };

    VBP3DScene.prototype._updateShadowState = function() {
        if (!this.renderer) {
            return;
        }

        this.renderer.shadowMap.enabled = !!this.config.shadows;
        this.objects.forEach(function(object3d) {
            if (object3d.isMesh) {
                object3d.castShadow = !!this.config.shadows && object3d.userData.config && object3d.userData.config.castShadow !== false;
                object3d.receiveShadow = !!this.config.shadows;
            }
            if (object3d.isLight && 'castShadow' in object3d) {
                object3d.castShadow = !!this.config.shadows && !!(object3d.userData.config && object3d.userData.config.castShadow);
            }
        }, this);
    };

    VBP3DScene.prototype.updateSceneConfig = function(patch) {
        this.config = Object.assign({}, this.config || {}, patch || {});

        if (patch && patch.background !== undefined) {
            this._applySceneBackground(this.config.background);
        }

        if (patch && patch.camera) {
            this.setCamera(this.config.camera || {});
        }

        if (patch && (patch.controls !== undefined || patch.autoRotate !== undefined || patch.autoRotateSpeed !== undefined || patch.enableZoom !== undefined || patch.enablePan !== undefined || patch.camera)) {
            this._setupControls();
        }

        if (patch && patch.shadows !== undefined) {
            this._updateShadowState();
        }

        if (patch && patch.pixelRatio !== undefined && this.renderer) {
            this.renderer.setPixelRatio(Math.min(this.config.pixelRatio || window.devicePixelRatio || 1, 2));
        }

        if (patch && patch.antialiasing !== undefined) {
            this._updateShadowState();
        }
    };

    /**
     * Actualizar partículas
     * @private
     */
    VBP3DScene.prototype._updateParticles = function(delta) {
        this.objects.forEach(function(object3d) {
            if (object3d.isPoints && object3d.userData.velocities) {
                var positions = object3d.geometry.attributes.position.array;
                var velocities = object3d.userData.velocities;
                var speed = object3d.userData.speed || 1;

                for (var particleIndex = 0; particleIndex < positions.length; particleIndex += 3) {
                    positions[particleIndex] += velocities[particleIndex] * speed;
                    positions[particleIndex + 1] += velocities[particleIndex + 1] * speed;
                    positions[particleIndex + 2] += velocities[particleIndex + 2] * speed;

                    // Rebote en límites
                    if (Math.abs(positions[particleIndex]) > 5) {
                        velocities[particleIndex] *= -1;
                    }
                    if (Math.abs(positions[particleIndex + 1]) > 5) {
                        velocities[particleIndex + 1] *= -1;
                    }
                    if (Math.abs(positions[particleIndex + 2]) > 5) {
                        velocities[particleIndex + 2] *= -1;
                    }
                }

                object3d.geometry.attributes.position.needsUpdate = true;
            }
        });
    };

    /**
     * Interpolación lineal
     * @private
     */
    VBP3DScene.prototype._lerp = function(startValue, endValue, progress) {
        return startValue + (endValue - startValue) * progress;
    };

    /**
     * Manejador de resize
     * @private
     */
    VBP3DScene.prototype._onResize = function() {
        var width = this.container.clientWidth;
        var height = this.container.clientHeight;

        if (this.camera.isPerspectiveCamera) {
            this.camera.aspect = width / height;
        } else {
            var frustumSize = this.config.camera.frustumSize || 10;
            this.camera.left = frustumSize * (width / height) / -2;
            this.camera.right = frustumSize * (width / height) / 2;
        }

        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    };

    /**
     * Manejador de click
     * @private
     */
    VBP3DScene.prototype._onClick = function(event) {
        var rect = this.container.getBoundingClientRect();
        this.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);

        var intersectableObjects = [];
        this.objects.forEach(function(obj) {
            if (obj.isMesh) {
                intersectableObjects.push(obj);
            }
        });

        var intersects = this.raycaster.intersectObjects(intersectableObjects, true);

        if (intersects.length > 0) {
            var clickedObject = intersects[0].object;
            var vbpId = clickedObject.userData.vbpId || clickedObject.parent.userData.vbpId;

            if (vbpId) {
                this.selectObject(vbpId);

                if (clickedObject.userData.onClick) {
                    this._executeInteraction(clickedObject, clickedObject.userData.onClick);
                }
            }
        }
    };

    /**
     * Manejador de movimiento del mouse
     * @private
     */
    VBP3DScene.prototype._onMouseMove = function(event) {
        var rect = this.container.getBoundingClientRect();
        this.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);

        var intersectableObjects = [];
        this.objects.forEach(function(obj) {
            if (obj.isMesh) {
                intersectableObjects.push(obj);
            }
        });

        var intersects = this.raycaster.intersectObjects(intersectableObjects, true);

        // Cambiar cursor
        this.container.style.cursor = intersects.length > 0 ? 'pointer' : 'default';
    };

    /**
     * Ejecutar interacción
     * @private
     */
    VBP3DScene.prototype._executeInteraction = function(object3d, interactionType) {
        switch (interactionType) {
            case 'rotate':
                this.addAnimation(object3d.userData.vbpId, {
                    keyframes: [
                        { time: 0, rotation: { y: object3d.rotation.y } },
                        { time: 1, rotation: { y: object3d.rotation.y + Math.PI * 2 } }
                    ],
                    duration: 1,
                    loop: false
                });
                break;
            case 'highlight':
                this._highlightObject(object3d);
                break;
            case 'scale':
                var originalScale = object3d.scale.x;
                this.addAnimation(object3d.userData.vbpId, {
                    keyframes: [
                        { time: 0, scale: originalScale },
                        { time: 0.5, scale: originalScale * 1.2 },
                        { time: 1, scale: originalScale }
                    ],
                    duration: 0.5,
                    loop: false
                });
                break;
        }
    };

    /**
     * Pausar escena
     */
    VBP3DScene.prototype.pause = function() {
        this.isPlaying = false;
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
    };

    /**
     * Reanudar escena
     */
    VBP3DScene.prototype.play = function() {
        if (!this.isPlaying) {
            this.isPlaying = true;
            this._animate();
        }
    };

    /**
     * Exportar escena como JSON
     * @returns {Object} Datos de la escena
     */
    VBP3DScene.prototype.exportScene = function() {
        var sceneData = {
            config: this.config,
            objects: []
        };

        this.objects.forEach(function(object3d, objectId) {
            sceneData.objects.push({
                id: objectId,
                config: object3d.userData.config,
                position: {
                    x: object3d.position.x,
                    y: object3d.position.y,
                    z: object3d.position.z
                },
                rotation: {
                    x: THREE.MathUtils.radToDeg(object3d.rotation.x),
                    y: THREE.MathUtils.radToDeg(object3d.rotation.y),
                    z: THREE.MathUtils.radToDeg(object3d.rotation.z)
                },
                scale: {
                    x: object3d.scale.x,
                    y: object3d.scale.y,
                    z: object3d.scale.z
                }
            });
        });

        return sceneData;
    };

    /**
     * Tomar screenshot de la escena
     * @returns {string} Data URL de la imagen
     */
    VBP3DScene.prototype.takeScreenshot = function() {
        this.renderer.render(this.scene, this.camera);
        return this.renderer.domElement.toDataURL('image/png');
    };

    /**
     * Destruir escena y liberar recursos
     */
    VBP3DScene.prototype.destroy = function() {
        this.pause();

        window.removeEventListener('resize', this._boundOnResize);
        this.container.removeEventListener('click', this._boundOnClick);
        this.container.removeEventListener('mousemove', this._boundOnMouseMove);

        // Limpiar objetos
        this.objects.forEach(function(object3d) {
            this.scene.remove(object3d);
            if (object3d.geometry) object3d.geometry.dispose();
            if (object3d.material) {
                if (Array.isArray(object3d.material)) {
                    object3d.material.forEach(function(mat) { mat.dispose(); });
                } else {
                    object3d.material.dispose();
                }
            }
        }, this);

        this.objects.clear();

        // Limpiar renderer
        this.renderer.dispose();
        if (this.container.contains(this.renderer.domElement)) {
            this.container.removeChild(this.renderer.domElement);
        }

        // Limpiar controles
        if (this.controls && this.controls.dispose) {
            this.controls.dispose();
        }

        activeScenes.delete(this.containerId);

        document.dispatchEvent(new CustomEvent('vbp-3d-scene-destroyed', {
            detail: { sceneId: this.containerId }
        }));
    };

    /**
     * API pública
     */
    window.VBP3D = {
        Scene: VBP3DScene,
        PRIMITIVES: PRIMITIVES_3D,
        MATERIALS: MATERIAL_TYPES,
        PRESETS: SCENE_PRESETS,
        EFFECTS: POST_EFFECTS,

        /**
         * Crear escena con preset
         * @param {string} containerId - ID del contenedor
         * @param {string} presetId - ID del preset
         * @returns {Promise<VBP3DScene>}
         */
        createFromPreset: function(containerId, presetId) {
            var preset = SCENE_PRESETS[presetId];
            if (!preset) {
                return Promise.reject(new Error('Preset no encontrado: ' + presetId));
            }

            var sceneInstance = new VBP3DScene(containerId, preset);
            return sceneInstance.init();
        },

        /**
         * Obtener escena activa
         * @param {string} containerId - ID del contenedor
         * @returns {VBP3DScene|null}
         */
        getScene: function(containerId) {
            return activeScenes.get(containerId) || null;
        },

        /**
         * Listar escenas activas
         * @returns {Array<string>}
         */
        listScenes: function() {
            return Array.from(activeScenes.keys());
        },

        /**
         * Cargar Three.js
         * @returns {Promise}
         */
        loadThreeJS: loadThreeJS,

        /**
         * Verificar si Three.js está cargado
         * @returns {boolean}
         */
        isLoaded: function() {
            return threeJsState.loaded;
        }
    };

    // Exponer también como módulo si es necesario
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = window.VBP3D;
    }

})();
