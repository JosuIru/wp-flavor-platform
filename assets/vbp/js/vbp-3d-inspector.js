/**
 * VBP 3D Inspector - Panel de inspección para objetos 3D
 *
 * Proporciona controles visuales para transformaciones, materiales,
 * iluminación y configuración de cámara en escenas 3D.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Configuración de controles de transformación
     */
    var TRANSFORM_CONTROLS = {
        position: {
            label: 'Posición',
            icon: '⊕',
            axes: ['x', 'y', 'z'],
            step: 0.1,
            min: -100,
            max: 100
        },
        rotation: {
            label: 'Rotación',
            icon: '↻',
            axes: ['x', 'y', 'z'],
            step: 1,
            min: -360,
            max: 360,
            unit: '°'
        },
        scale: {
            label: 'Escala',
            icon: '⤢',
            axes: ['x', 'y', 'z'],
            step: 0.1,
            min: 0.01,
            max: 10,
            uniform: true
        }
    };

    /**
     * Propiedades de materiales editables
     */
    var MATERIAL_PROPERTIES = {
        color: { type: 'color', label: 'Color' },
        opacity: { type: 'range', label: 'Opacidad', min: 0, max: 1, step: 0.01 },
        metalness: { type: 'range', label: 'Metalicidad', min: 0, max: 1, step: 0.01 },
        roughness: { type: 'range', label: 'Rugosidad', min: 0, max: 1, step: 0.01 },
        emissive: { type: 'color', label: 'Emisivo' },
        emissiveIntensity: { type: 'range', label: 'Intensidad emisiva', min: 0, max: 2, step: 0.1 },
        wireframe: { type: 'checkbox', label: 'Wireframe' },
        flatShading: { type: 'checkbox', label: 'Sombreado plano' },
        transparent: { type: 'checkbox', label: 'Transparente' },
        side: { type: 'select', label: 'Caras visibles', options: ['front', 'back', 'double'] }
    };

    /**
     * Propiedades de iluminación
     */
    var LIGHTING_PROPERTIES = {
        ambient: {
            color: { type: 'color', label: 'Color' },
            intensity: { type: 'range', label: 'Intensidad', min: 0, max: 2, step: 0.1 }
        },
        directional: {
            color: { type: 'color', label: 'Color' },
            intensity: { type: 'range', label: 'Intensidad', min: 0, max: 2, step: 0.1 },
            position: { type: 'vector3', label: 'Posición' },
            castShadow: { type: 'checkbox', label: 'Proyectar sombras' }
        },
        point: {
            color: { type: 'color', label: 'Color' },
            intensity: { type: 'range', label: 'Intensidad', min: 0, max: 2, step: 0.1 },
            distance: { type: 'range', label: 'Distancia', min: 0, max: 100, step: 1 },
            decay: { type: 'range', label: 'Decaimiento', min: 0, max: 2, step: 0.1 }
        },
        spot: {
            color: { type: 'color', label: 'Color' },
            intensity: { type: 'range', label: 'Intensidad', min: 0, max: 2, step: 0.1 },
            angle: { type: 'range', label: 'Ángulo', min: 0, max: 90, step: 1, unit: '°' },
            penumbra: { type: 'range', label: 'Penumbra', min: 0, max: 1, step: 0.1 }
        }
    };

    /**
     * Propiedades de cámara
     */
    var CAMERA_PROPERTIES = {
        perspective: {
            fov: { type: 'range', label: 'FOV', min: 10, max: 120, step: 1, unit: '°' },
            near: { type: 'number', label: 'Near', min: 0.01, max: 10, step: 0.01 },
            far: { type: 'number', label: 'Far', min: 10, max: 10000, step: 10 }
        },
        orthographic: {
            zoom: { type: 'range', label: 'Zoom', min: 0.1, max: 10, step: 0.1 }
        },
        position: { type: 'vector3', label: 'Posición' },
        lookAt: { type: 'vector3', label: 'Mirar a' }
    };

    /**
     * Estado del inspector
     */
    var inspectorState = {
        activeScene: null,
        selectedObject: null,
        activeTab: 'transform',
        transformMode: 'translate',
        uniformScale: true,
        showGizmos: true
    };

    /**
     * Generar ID único
     */
    function generateId() {
        return 'vbp3d_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Crear elemento HTML
     */
    function createElement(tag, className, content) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (content) {
            if (typeof content === 'string') {
                element.innerHTML = content;
            } else {
                element.appendChild(content);
            }
        }
        return element;
    }

    /**
     * Clase principal del Inspector 3D
     */
    function VBP3DInspector(containerId) {
        this.containerId = containerId;
        this.container = null;
        this.panels = {};
        this.inputs = {};
        this.scene = null;
        this.selectedObjectId = null;

        this._boundOnObjectSelected = this._onObjectSelected.bind(this);
        this._boundOnSceneReady = this._onSceneReady.bind(this);
    }

    /**
     * Inicializar inspector
     */
    VBP3DInspector.prototype.init = function() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) {
            console.error('Contenedor del inspector no encontrado:', this.containerId);
            return;
        }

        this._createUI();
        this._bindEvents();

        document.addEventListener('vbp-3d-object-selected', this._boundOnObjectSelected);
        document.addEventListener('vbp-3d-scene-ready', this._boundOnSceneReady);

        return this;
    };

    /**
     * Crear interfaz de usuario
     * @private
     */
    VBP3DInspector.prototype._createUI = function() {
        this.container.innerHTML = '';
        this.container.className = 'vbp-3d-inspector';

        // Header
        var inspectorHeader = createElement('div', 'vbp-3d-inspector__header');
        inspectorHeader.innerHTML = '<h3 class="vbp-3d-inspector__title">Inspector 3D</h3>';
        this.container.appendChild(inspectorHeader);

        // Tabs
        var tabsContainer = createElement('div', 'vbp-3d-inspector__tabs');
        var tabs = [
            { id: 'transform', label: 'Transform', icon: '⊕' },
            { id: 'material', label: 'Material', icon: '◐' },
            { id: 'lighting', label: 'Luz', icon: '☀' },
            { id: 'camera', label: 'Cámara', icon: '📷' },
            { id: 'scene', label: 'Escena', icon: '🎬' }
        ];

        var self = this;
        tabs.forEach(function(tab) {
            var tabButton = createElement('button', 'vbp-3d-inspector__tab');
            tabButton.dataset.tab = tab.id;
            tabButton.innerHTML = '<span class="icon">' + tab.icon + '</span><span class="label">' + tab.label + '</span>';
            tabButton.addEventListener('click', function() {
                self._switchTab(tab.id);
            });
            if (tab.id === 'transform') {
                tabButton.classList.add('active');
            }
            tabsContainer.appendChild(tabButton);
        });

        this.container.appendChild(tabsContainer);

        // Panels container
        var panelsContainer = createElement('div', 'vbp-3d-inspector__panels');

        // Transform panel
        this.panels.transform = this._createTransformPanel();
        panelsContainer.appendChild(this.panels.transform);

        // Material panel
        this.panels.material = this._createMaterialPanel();
        this.panels.material.style.display = 'none';
        panelsContainer.appendChild(this.panels.material);

        // Lighting panel
        this.panels.lighting = this._createLightingPanel();
        this.panels.lighting.style.display = 'none';
        panelsContainer.appendChild(this.panels.lighting);

        // Camera panel
        this.panels.camera = this._createCameraPanel();
        this.panels.camera.style.display = 'none';
        panelsContainer.appendChild(this.panels.camera);

        // Scene panel
        this.panels.scene = this._createScenePanel();
        this.panels.scene.style.display = 'none';
        panelsContainer.appendChild(this.panels.scene);

        this.container.appendChild(panelsContainer);

        // Footer con acciones rápidas
        var inspectorFooter = createElement('div', 'vbp-3d-inspector__footer');
        inspectorFooter.innerHTML = [
            '<div class="vbp-3d-inspector__quick-actions">',
            '  <button class="vbp-btn vbp-btn--icon" data-action="reset-transform" title="Resetear transformación">↺</button>',
            '  <button class="vbp-btn vbp-btn--icon" data-action="duplicate" title="Duplicar objeto">⧉</button>',
            '  <button class="vbp-btn vbp-btn--icon" data-action="delete" title="Eliminar objeto">🗑</button>',
            '  <button class="vbp-btn vbp-btn--icon" data-action="screenshot" title="Captura de pantalla">📸</button>',
            '</div>'
        ].join('');
        this.container.appendChild(inspectorFooter);
    };

    /**
     * Crear panel de transformación
     * @private
     */
    VBP3DInspector.prototype._createTransformPanel = function() {
        var panel = createElement('div', 'vbp-3d-inspector__panel vbp-3d-inspector__panel--transform');

        // Modo de transformación
        var transformModeContainer = createElement('div', 'vbp-3d-inspector__section');
        transformModeContainer.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Modo</span>',
            '</div>',
            '<div class="vbp-3d-inspector__mode-buttons">',
            '  <button class="vbp-btn active" data-mode="translate">Mover</button>',
            '  <button class="vbp-btn" data-mode="rotate">Rotar</button>',
            '  <button class="vbp-btn" data-mode="scale">Escalar</button>',
            '</div>'
        ].join('');
        panel.appendChild(transformModeContainer);

        var self = this;

        // Controles para cada tipo de transformación
        Object.keys(TRANSFORM_CONTROLS).forEach(function(transformType) {
            var controlConfig = TRANSFORM_CONTROLS[transformType];
            var transformSection = createElement('div', 'vbp-3d-inspector__section');

            var sectionHeader = createElement('div', 'vbp-3d-inspector__section-header');
            sectionHeader.innerHTML = '<span class="icon">' + controlConfig.icon + '</span><span>' + controlConfig.label + '</span>';

            if (controlConfig.uniform) {
                var uniformToggle = createElement('label', 'vbp-3d-inspector__uniform-toggle');
                uniformToggle.innerHTML = '<input type="checkbox" checked><span>Uniforme</span>';
                uniformToggle.querySelector('input').addEventListener('change', function(event) {
                    inspectorState.uniformScale = event.target.checked;
                });
                sectionHeader.appendChild(uniformToggle);
            }

            transformSection.appendChild(sectionHeader);

            var axesContainer = createElement('div', 'vbp-3d-inspector__axes');

            controlConfig.axes.forEach(function(axis) {
                var axisRow = createElement('div', 'vbp-3d-inspector__axis-row');

                var axisLabel = createElement('label', 'vbp-3d-inspector__axis-label');
                axisLabel.textContent = axis.toUpperCase();
                axisLabel.classList.add('axis-' + axis);

                var axisInput = createElement('input', 'vbp-3d-inspector__axis-input');
                axisInput.type = 'number';
                axisInput.step = controlConfig.step;
                axisInput.min = controlConfig.min;
                axisInput.max = controlConfig.max;
                axisInput.value = transformType === 'scale' ? 1 : 0;
                axisInput.dataset.transform = transformType;
                axisInput.dataset.axis = axis;

                axisInput.addEventListener('change', function(event) {
                    self._onTransformChange(transformType, axis, parseFloat(event.target.value));
                });

                self.inputs[transformType + '_' + axis] = axisInput;

                axisRow.appendChild(axisLabel);
                axisRow.appendChild(axisInput);

                if (controlConfig.unit) {
                    var unitLabel = createElement('span', 'vbp-3d-inspector__unit');
                    unitLabel.textContent = controlConfig.unit;
                    axisRow.appendChild(unitLabel);
                }

                axesContainer.appendChild(axisRow);
            });

            transformSection.appendChild(axesContainer);
            panel.appendChild(transformSection);
        });

        return panel;
    };

    /**
     * Crear panel de material
     * @private
     */
    VBP3DInspector.prototype._createMaterialPanel = function() {
        var panel = createElement('div', 'vbp-3d-inspector__panel vbp-3d-inspector__panel--material');
        var self = this;

        // Selector de tipo de material
        var materialTypeSection = createElement('div', 'vbp-3d-inspector__section');
        materialTypeSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Tipo de Material</span>',
            '</div>',
            '<select class="vbp-3d-inspector__select" id="material-type">',
            '  <option value="standard">Estándar (PBR)</option>',
            '  <option value="basic">Básico</option>',
            '  <option value="phong">Phong</option>',
            '  <option value="lambert">Lambert</option>',
            '  <option value="physical">Físico</option>',
            '  <option value="toon">Cartoon</option>',
            '</select>'
        ].join('');
        panel.appendChild(materialTypeSection);

        // Propiedades de material
        var materialPropsSection = createElement('div', 'vbp-3d-inspector__section');
        materialPropsSection.innerHTML = '<div class="vbp-3d-inspector__section-header"><span>Propiedades</span></div>';

        Object.keys(MATERIAL_PROPERTIES).forEach(function(propName) {
            var propConfig = MATERIAL_PROPERTIES[propName];
            var propRow = self._createPropertyInput(propName, propConfig, 'material');
            materialPropsSection.appendChild(propRow);
        });

        panel.appendChild(materialPropsSection);

        // Texturas
        var texturesSection = createElement('div', 'vbp-3d-inspector__section');
        texturesSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Texturas</span>',
            '</div>',
            '<div class="vbp-3d-inspector__texture-slots">',
            '  <div class="vbp-3d-inspector__texture-slot">',
            '    <label>Difusa</label>',
            '    <button class="vbp-btn vbp-btn--sm" data-texture="map">Seleccionar</button>',
            '  </div>',
            '  <div class="vbp-3d-inspector__texture-slot">',
            '    <label>Normal</label>',
            '    <button class="vbp-btn vbp-btn--sm" data-texture="normalMap">Seleccionar</button>',
            '  </div>',
            '  <div class="vbp-3d-inspector__texture-slot">',
            '    <label>Roughness</label>',
            '    <button class="vbp-btn vbp-btn--sm" data-texture="roughnessMap">Seleccionar</button>',
            '  </div>',
            '  <div class="vbp-3d-inspector__texture-slot">',
            '    <label>Metalness</label>',
            '    <button class="vbp-btn vbp-btn--sm" data-texture="metalnessMap">Seleccionar</button>',
            '  </div>',
            '</div>'
        ].join('');
        panel.appendChild(texturesSection);

        return panel;
    };

    /**
     * Crear panel de iluminación
     * @private
     */
    VBP3DInspector.prototype._createLightingPanel = function() {
        var panel = createElement('div', 'vbp-3d-inspector__panel vbp-3d-inspector__panel--lighting');
        var self = this;

        // Agregar luz
        var addLightSection = createElement('div', 'vbp-3d-inspector__section');
        addLightSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Agregar Luz</span>',
            '</div>',
            '<div class="vbp-3d-inspector__light-buttons">',
            '  <button class="vbp-btn vbp-btn--sm" data-light="ambient">Ambiental</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-light="directional">Direccional</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-light="point">Puntual</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-light="spot">Spot</button>',
            '</div>'
        ].join('');
        panel.appendChild(addLightSection);

        // Lista de luces
        var lightsListSection = createElement('div', 'vbp-3d-inspector__section');
        lightsListSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Luces en Escena</span>',
            '</div>',
            '<div class="vbp-3d-inspector__lights-list" id="lights-list">',
            '  <p class="vbp-3d-inspector__empty">No hay luces configuradas</p>',
            '</div>'
        ].join('');
        panel.appendChild(lightsListSection);

        // Editor de luz seleccionada
        var lightEditorSection = createElement('div', 'vbp-3d-inspector__section vbp-3d-inspector__light-editor');
        lightEditorSection.id = 'light-editor';
        lightEditorSection.style.display = 'none';
        panel.appendChild(lightEditorSection);

        return panel;
    };

    /**
     * Crear panel de cámara
     * @private
     */
    VBP3DInspector.prototype._createCameraPanel = function() {
        var panel = createElement('div', 'vbp-3d-inspector__panel vbp-3d-inspector__panel--camera');
        var self = this;

        // Tipo de cámara
        var cameraTypeSection = createElement('div', 'vbp-3d-inspector__section');
        cameraTypeSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Tipo de Cámara</span>',
            '</div>',
            '<select class="vbp-3d-inspector__select" id="camera-type">',
            '  <option value="perspective">Perspectiva</option>',
            '  <option value="orthographic">Ortográfica</option>',
            '</select>'
        ].join('');
        panel.appendChild(cameraTypeSection);

        // Propiedades de perspectiva
        var perspectiveSection = createElement('div', 'vbp-3d-inspector__section');
        perspectiveSection.id = 'camera-perspective-props';
        perspectiveSection.innerHTML = '<div class="vbp-3d-inspector__section-header"><span>Perspectiva</span></div>';

        Object.keys(CAMERA_PROPERTIES.perspective).forEach(function(propName) {
            var propConfig = CAMERA_PROPERTIES.perspective[propName];
            var propRow = self._createPropertyInput(propName, propConfig, 'camera');
            perspectiveSection.appendChild(propRow);
        });

        panel.appendChild(perspectiveSection);

        // Posición de cámara
        var cameraPositionSection = createElement('div', 'vbp-3d-inspector__section');
        cameraPositionSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Posición</span>',
            '</div>',
            '<div class="vbp-3d-inspector__axes">',
            '  <div class="vbp-3d-inspector__axis-row">',
            '    <label class="vbp-3d-inspector__axis-label axis-x">X</label>',
            '    <input type="number" class="vbp-3d-inspector__axis-input" id="camera-pos-x" step="0.1" value="0">',
            '  </div>',
            '  <div class="vbp-3d-inspector__axis-row">',
            '    <label class="vbp-3d-inspector__axis-label axis-y">Y</label>',
            '    <input type="number" class="vbp-3d-inspector__axis-input" id="camera-pos-y" step="0.1" value="0">',
            '  </div>',
            '  <div class="vbp-3d-inspector__axis-row">',
            '    <label class="vbp-3d-inspector__axis-label axis-z">Z</label>',
            '    <input type="number" class="vbp-3d-inspector__axis-input" id="camera-pos-z" step="0.1" value="5">',
            '  </div>',
            '</div>'
        ].join('');
        panel.appendChild(cameraPositionSection);

        // Controles
        var controlsSection = createElement('div', 'vbp-3d-inspector__section');
        controlsSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Controles</span>',
            '</div>',
            '<div class="vbp-3d-inspector__control-options">',
            '  <label><input type="checkbox" id="camera-auto-rotate"> Auto-rotar</label>',
            '  <label><input type="checkbox" id="camera-enable-zoom" checked> Permitir zoom</label>',
            '  <label><input type="checkbox" id="camera-enable-pan" checked> Permitir pan</label>',
            '</div>'
        ].join('');
        panel.appendChild(controlsSection);

        // Presets de cámara
        var presetsSection = createElement('div', 'vbp-3d-inspector__section');
        presetsSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Vistas Predefinidas</span>',
            '</div>',
            '<div class="vbp-3d-inspector__camera-presets">',
            '  <button class="vbp-btn vbp-btn--sm" data-view="front">Frontal</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-view="back">Trasera</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-view="left">Izquierda</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-view="right">Derecha</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-view="top">Superior</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-view="iso">Isométrica</button>',
            '</div>'
        ].join('');
        panel.appendChild(presetsSection);

        return panel;
    };

    /**
     * Crear panel de escena
     * @private
     */
    VBP3DInspector.prototype._createScenePanel = function() {
        var panel = createElement('div', 'vbp-3d-inspector__panel vbp-3d-inspector__panel--scene');

        // Fondo
        var backgroundSection = createElement('div', 'vbp-3d-inspector__section');
        backgroundSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Fondo</span>',
            '</div>',
            '<div class="vbp-3d-inspector__background-options">',
            '  <select class="vbp-3d-inspector__select" id="scene-bg-type">',
            '    <option value="solid">Color sólido</option>',
            '    <option value="gradient">Gradiente</option>',
            '    <option value="transparent">Transparente</option>',
            '  </select>',
            '  <div class="vbp-3d-inspector__bg-colors">',
            '    <label>Color 1 <input type="color" id="scene-bg-color1" value="#000000"></label>',
            '    <label>Color 2 <input type="color" id="scene-bg-color2" value="#333333"></label>',
            '  </div>',
            '</div>'
        ].join('');
        panel.appendChild(backgroundSection);

        // Presets de escena
        var presetsSection = createElement('div', 'vbp-3d-inspector__section');
        presetsSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Presets de Escena</span>',
            '</div>',
            '<div class="vbp-3d-inspector__scene-presets">',
            '  <button class="vbp-btn vbp-btn--sm" data-preset="product-showcase">Showcase</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-preset="floating-cards">Tarjetas</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-preset="particle-background">Partículas</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-preset="hero-3d">Hero 3D</button>',
            '  <button class="vbp-btn vbp-btn--sm" data-preset="minimal">Minimalista</button>',
            '</div>'
        ].join('');
        panel.appendChild(presetsSection);

        // Opciones de renderizado
        var renderSection = createElement('div', 'vbp-3d-inspector__section');
        renderSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Renderizado</span>',
            '</div>',
            '<div class="vbp-3d-inspector__render-options">',
            '  <label><input type="checkbox" id="scene-shadows"> Sombras</label>',
            '  <label><input type="checkbox" id="scene-antialiasing" checked> Antialiasing</label>',
            '  <label>Pixel Ratio <input type="number" id="scene-pixel-ratio" value="1" min="0.5" max="2" step="0.5"></label>',
            '</div>'
        ].join('');
        panel.appendChild(renderSection);

        // Objetos en escena
        var objectsSection = createElement('div', 'vbp-3d-inspector__section');
        objectsSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Objetos en Escena</span>',
            '  <button class="vbp-btn vbp-btn--icon vbp-btn--sm" id="add-object-btn">+</button>',
            '</div>',
            '<div class="vbp-3d-inspector__objects-tree" id="objects-tree">',
            '  <p class="vbp-3d-inspector__empty">Sin objetos</p>',
            '</div>'
        ].join('');
        panel.appendChild(objectsSection);

        // Exportar
        var exportSection = createElement('div', 'vbp-3d-inspector__section');
        exportSection.innerHTML = [
            '<div class="vbp-3d-inspector__section-header">',
            '  <span>Exportar</span>',
            '</div>',
            '<div class="vbp-3d-inspector__export-buttons">',
            '  <button class="vbp-btn" data-export="json">JSON</button>',
            '  <button class="vbp-btn" data-export="glb">GLB</button>',
            '  <button class="vbp-btn" data-export="png">PNG</button>',
            '</div>'
        ].join('');
        panel.appendChild(exportSection);

        return panel;
    };

    /**
     * Crear input de propiedad
     * @private
     */
    VBP3DInspector.prototype._createPropertyInput = function(propName, propConfig, category) {
        var self = this;
        var propRow = createElement('div', 'vbp-3d-inspector__prop-row');
        var propLabel = createElement('label', 'vbp-3d-inspector__prop-label');
        propLabel.textContent = propConfig.label;

        var input;

        switch (propConfig.type) {
            case 'color':
                input = createElement('input', 'vbp-3d-inspector__color-input');
                input.type = 'color';
                input.value = '#ffffff';
                break;

            case 'range':
                var rangeContainer = createElement('div', 'vbp-3d-inspector__range-container');
                input = createElement('input', 'vbp-3d-inspector__range-input');
                input.type = 'range';
                input.min = propConfig.min;
                input.max = propConfig.max;
                input.step = propConfig.step;
                input.value = (propConfig.min + propConfig.max) / 2;

                var valueDisplay = createElement('span', 'vbp-3d-inspector__range-value');
                valueDisplay.textContent = input.value;

                input.addEventListener('input', function() {
                    valueDisplay.textContent = parseFloat(input.value).toFixed(2);
                });

                rangeContainer.appendChild(input);
                rangeContainer.appendChild(valueDisplay);
                propRow.appendChild(propLabel);
                propRow.appendChild(rangeContainer);

                this.inputs[category + '_' + propName] = input;
                return propRow;

            case 'checkbox':
                input = createElement('input', 'vbp-3d-inspector__checkbox-input');
                input.type = 'checkbox';
                break;

            case 'select':
                input = createElement('select', 'vbp-3d-inspector__select');
                propConfig.options.forEach(function(opt) {
                    var option = createElement('option');
                    option.value = opt;
                    option.textContent = opt.charAt(0).toUpperCase() + opt.slice(1);
                    input.appendChild(option);
                });
                break;

            case 'number':
                input = createElement('input', 'vbp-3d-inspector__number-input');
                input.type = 'number';
                input.min = propConfig.min;
                input.max = propConfig.max;
                input.step = propConfig.step;
                input.value = propConfig.min;
                break;

            default:
                input = createElement('input', 'vbp-3d-inspector__text-input');
                input.type = 'text';
        }

        input.dataset.category = category;
        input.dataset.property = propName;

        input.addEventListener('change', function(event) {
            self._onPropertyChange(category, propName, event.target.type === 'checkbox'
                ? event.target.checked
                : event.target.value);
        });

        this.inputs[category + '_' + propName] = input;

        propRow.appendChild(propLabel);
        propRow.appendChild(input);

        return propRow;
    };

    /**
     * Cambiar pestaña activa
     * @private
     */
    VBP3DInspector.prototype._switchTab = function(tabId) {
        // Actualizar botones de pestañas
        var tabButtons = this.container.querySelectorAll('.vbp-3d-inspector__tab');
        tabButtons.forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === tabId);
        });

        // Mostrar panel correspondiente
        Object.keys(this.panels).forEach(function(panelId) {
            this.panels[panelId].style.display = panelId === tabId ? 'block' : 'none';
        }, this);

        inspectorState.activeTab = tabId;
    };

    /**
     * Vincular eventos
     * @private
     */
    VBP3DInspector.prototype._bindEvents = function() {
        var self = this;

        // Acciones rápidas del footer
        this.container.querySelectorAll('[data-action]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                self._executeAction(btn.dataset.action);
            });
        });

        // Presets de cámara
        this.container.querySelectorAll('[data-view]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                self._setCameraView(btn.dataset.view);
            });
        });

        // Presets de escena
        this.container.querySelectorAll('[data-preset]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                self._applyScenePreset(btn.dataset.preset);
            });
        });

        // Exportar
        this.container.querySelectorAll('[data-export]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                self._exportScene(btn.dataset.export);
            });
        });

        // Agregar luz
        this.container.querySelectorAll('[data-light]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                self._addLight(btn.dataset.light);
            });
        });
    };

    /**
     * Manejador de escena lista
     * @private
     */
    VBP3DInspector.prototype._onSceneReady = function(event) {
        this.scene = event.detail.scene;
        this._updateObjectsTree();
    };

    /**
     * Manejador de objeto seleccionado
     * @private
     */
    VBP3DInspector.prototype._onObjectSelected = function(event) {
        this.selectedObjectId = event.detail.objectId;
        var object3d = event.detail.object;

        // Actualizar inputs de transformación
        if (object3d) {
            this._updateTransformInputs(object3d);
            this._updateMaterialInputs(object3d);
        }
    };

    /**
     * Actualizar inputs de transformación
     * @private
     */
    VBP3DInspector.prototype._updateTransformInputs = function(object3d) {
        var THREE = window.THREE;

        // Posición
        if (this.inputs.position_x) {
            this.inputs.position_x.value = object3d.position.x.toFixed(2);
        }
        if (this.inputs.position_y) {
            this.inputs.position_y.value = object3d.position.y.toFixed(2);
        }
        if (this.inputs.position_z) {
            this.inputs.position_z.value = object3d.position.z.toFixed(2);
        }

        // Rotación (convertir de radianes a grados)
        if (this.inputs.rotation_x && THREE) {
            this.inputs.rotation_x.value = THREE.MathUtils.radToDeg(object3d.rotation.x).toFixed(0);
        }
        if (this.inputs.rotation_y && THREE) {
            this.inputs.rotation_y.value = THREE.MathUtils.radToDeg(object3d.rotation.y).toFixed(0);
        }
        if (this.inputs.rotation_z && THREE) {
            this.inputs.rotation_z.value = THREE.MathUtils.radToDeg(object3d.rotation.z).toFixed(0);
        }

        // Escala
        if (this.inputs.scale_x) {
            this.inputs.scale_x.value = object3d.scale.x.toFixed(2);
        }
        if (this.inputs.scale_y) {
            this.inputs.scale_y.value = object3d.scale.y.toFixed(2);
        }
        if (this.inputs.scale_z) {
            this.inputs.scale_z.value = object3d.scale.z.toFixed(2);
        }
    };

    /**
     * Actualizar inputs de material
     * @private
     */
    VBP3DInspector.prototype._updateMaterialInputs = function(object3d) {
        if (!object3d.material) return;

        var material = object3d.material;

        if (this.inputs.material_color && material.color) {
            this.inputs.material_color.value = '#' + material.color.getHexString();
        }

        if (this.inputs.material_opacity) {
            this.inputs.material_opacity.value = material.opacity;
        }

        if (this.inputs.material_metalness && material.metalness !== undefined) {
            this.inputs.material_metalness.value = material.metalness;
        }

        if (this.inputs.material_roughness && material.roughness !== undefined) {
            this.inputs.material_roughness.value = material.roughness;
        }

        if (this.inputs.material_wireframe) {
            this.inputs.material_wireframe.checked = material.wireframe || false;
        }
    };

    /**
     * Manejador de cambio de transformación
     * @private
     */
    VBP3DInspector.prototype._onTransformChange = function(transformType, axis, value) {
        if (!this.scene || !this.selectedObjectId) return;

        var updateProps = {};

        if (transformType === 'scale' && inspectorState.uniformScale) {
            updateProps.scale = { x: value, y: value, z: value };
        } else {
            updateProps[transformType] = {};
            updateProps[transformType][axis] = value;
        }

        this.scene.updateObject(this.selectedObjectId, updateProps);
    };

    /**
     * Manejador de cambio de propiedad
     * @private
     */
    VBP3DInspector.prototype._onPropertyChange = function(category, propName, value) {
        if (!this.scene || !this.selectedObjectId) return;

        if (category === 'material') {
            var materialProps = {};
            materialProps[propName] = value;
            this.scene.updateObject(this.selectedObjectId, { material: materialProps });
        } else if (category === 'camera') {
            var cameraConfig = {};
            cameraConfig[propName] = parseFloat(value);
            this.scene.setCamera(cameraConfig);
        }
    };

    /**
     * Ejecutar acción rápida
     * @private
     */
    VBP3DInspector.prototype._executeAction = function(actionType) {
        if (!this.scene) return;

        switch (actionType) {
            case 'reset-transform':
                if (this.selectedObjectId) {
                    this.scene.updateObject(this.selectedObjectId, {
                        position: { x: 0, y: 0, z: 0 },
                        rotation: { x: 0, y: 0, z: 0 },
                        scale: { x: 1, y: 1, z: 1 }
                    });
                    var obj = this.scene.objects.get(this.selectedObjectId);
                    if (obj) this._updateTransformInputs(obj);
                }
                break;

            case 'duplicate':
                if (this.selectedObjectId) {
                    var originalObject = this.scene.objects.get(this.selectedObjectId);
                    if (originalObject && originalObject.userData.config) {
                        var newId = generateId();
                        var newConfig = JSON.parse(JSON.stringify(originalObject.userData.config));
                        newConfig.position = {
                            x: (originalObject.position.x || 0) + 1,
                            y: originalObject.position.y || 0,
                            z: originalObject.position.z || 0
                        };
                        this.scene.addObject(newId, newConfig);
                    }
                }
                break;

            case 'delete':
                if (this.selectedObjectId) {
                    this.scene.removeObject(this.selectedObjectId);
                    this.selectedObjectId = null;
                    this._updateObjectsTree();
                }
                break;

            case 'screenshot':
                var dataUrl = this.scene.takeScreenshot();
                var link = document.createElement('a');
                link.download = 'vbp-3d-scene.png';
                link.href = dataUrl;
                link.click();
                break;
        }
    };

    /**
     * Establecer vista de cámara predefinida
     * @private
     */
    VBP3DInspector.prototype._setCameraView = function(view) {
        if (!this.scene) return;

        var viewConfigs = {
            front: { position: { x: 0, y: 0, z: 5 }, lookAt: { x: 0, y: 0, z: 0 } },
            back: { position: { x: 0, y: 0, z: -5 }, lookAt: { x: 0, y: 0, z: 0 } },
            left: { position: { x: -5, y: 0, z: 0 }, lookAt: { x: 0, y: 0, z: 0 } },
            right: { position: { x: 5, y: 0, z: 0 }, lookAt: { x: 0, y: 0, z: 0 } },
            top: { position: { x: 0, y: 5, z: 0 }, lookAt: { x: 0, y: 0, z: 0 } },
            iso: { position: { x: 3, y: 3, z: 3 }, lookAt: { x: 0, y: 0, z: 0 } }
        };

        var viewConfig = viewConfigs[view];
        if (viewConfig) {
            this.scene.setCamera(viewConfig);
        }
    };

    /**
     * Aplicar preset de escena
     * @private
     */
    VBP3DInspector.prototype._applyScenePreset = function(presetId) {
        if (!this.scene || !window.VBP3D || !window.VBP3D.PRESETS) return;

        var preset = window.VBP3D.PRESETS[presetId];
        if (!preset) return;

        // Aplicar configuración del preset
        if (preset.background) {
            // Actualizar fondo (esto requeriría recrear la escena o tener un método específico)
            console.log('Aplicando preset:', presetId);
        }

        if (preset.camera) {
            this.scene.setCamera(preset.camera);
        }

        // Mostrar notificación
        document.dispatchEvent(new CustomEvent('vbp-toast', {
            detail: {
                message: 'Preset "' + preset.name + '" aplicado',
                type: 'success'
            }
        }));
    };

    /**
     * Agregar luz a la escena
     * @private
     */
    VBP3DInspector.prototype._addLight = function(lightType) {
        console.log('Agregar luz:', lightType);
        // Implementación pendiente de integración con escena
    };

    /**
     * Exportar escena
     * @private
     */
    VBP3DInspector.prototype._exportScene = function(format) {
        if (!this.scene) return;

        switch (format) {
            case 'json':
                var sceneData = this.scene.exportScene();
                var jsonStr = JSON.stringify(sceneData, null, 2);
                this._downloadFile(jsonStr, 'scene.json', 'application/json');
                break;

            case 'png':
                var dataUrl = this.scene.takeScreenshot();
                var link = document.createElement('a');
                link.download = 'scene.png';
                link.href = dataUrl;
                link.click();
                break;

            case 'glb':
                console.log('Exportación GLB pendiente de implementar');
                document.dispatchEvent(new CustomEvent('vbp-toast', {
                    detail: {
                        message: 'Exportación GLB próximamente',
                        type: 'info'
                    }
                }));
                break;
        }
    };

    /**
     * Descargar archivo
     * @private
     */
    VBP3DInspector.prototype._downloadFile = function(content, filename, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.download = filename;
        link.href = url;
        link.click();
        URL.revokeObjectURL(url);
    };

    /**
     * Actualizar árbol de objetos
     * @private
     */
    VBP3DInspector.prototype._updateObjectsTree = function() {
        var treeContainer = document.getElementById('objects-tree');
        if (!treeContainer || !this.scene) return;

        treeContainer.innerHTML = '';

        if (this.scene.objects.size === 0) {
            treeContainer.innerHTML = '<p class="vbp-3d-inspector__empty">Sin objetos</p>';
            return;
        }

        var self = this;
        this.scene.objects.forEach(function(object3d, objectId) {
            var objectItem = createElement('div', 'vbp-3d-inspector__object-item');
            objectItem.dataset.objectId = objectId;

            var objectConfig = object3d.userData.config || {};
            var objectType = objectConfig.type || objectConfig.primitive || 'object';

            objectItem.innerHTML = [
                '<span class="icon">' + (PRIMITIVES_3D[objectType] ? PRIMITIVES_3D[objectType].icon : '◆') + '</span>',
                '<span class="name">' + objectId + '</span>',
                '<span class="type">' + objectType + '</span>'
            ].join('');

            objectItem.addEventListener('click', function() {
                self.scene.selectObject(objectId);
                treeContainer.querySelectorAll('.vbp-3d-inspector__object-item').forEach(function(item) {
                    item.classList.remove('selected');
                });
                objectItem.classList.add('selected');
            });

            treeContainer.appendChild(objectItem);
        });
    };

    /**
     * Destruir inspector
     */
    VBP3DInspector.prototype.destroy = function() {
        document.removeEventListener('vbp-3d-object-selected', this._boundOnObjectSelected);
        document.removeEventListener('vbp-3d-scene-ready', this._boundOnSceneReady);

        if (this.container) {
            this.container.innerHTML = '';
        }
    };

    /**
     * Configuración para acceder primitivas
     */
    var PRIMITIVES_3D = window.VBP3D ? window.VBP3D.PRIMITIVES : {
        'box': { icon: '□' },
        'sphere': { icon: '○' },
        'cylinder': { icon: '⬭' },
        'plane': { icon: '▭' },
        'torus': { icon: '◯' },
        'cone': { icon: '△' }
    };

    /**
     * API pública
     */
    window.VBP3DInspector = VBP3DInspector;

})();
