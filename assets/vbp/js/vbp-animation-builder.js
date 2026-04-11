/**
 * VBP Animation Builder - Constructor de animaciones CSS
 *
 * Panel de timeline visual para definir animaciones personalizadas con keyframes,
 * curvas de easing y triggers configurables.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Configuracion de easings predefinidos
     */
    var EASING_PRESETS = {
        'linear': { css: 'linear', label: 'Linear', icon: '—' },
        'ease': { css: 'ease', label: 'Ease', icon: '~' },
        'ease-in': { css: 'ease-in', label: 'Ease In', icon: '⌐' },
        'ease-out': { css: 'ease-out', label: 'Ease Out', icon: '⌙' },
        'ease-in-out': { css: 'ease-in-out', label: 'Ease In Out', icon: '∿' },
        'bounce': { css: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)', label: 'Bounce', icon: '⤴' },
        'elastic': { css: 'cubic-bezier(0.68, -0.6, 0.32, 1.6)', label: 'Elastic', icon: '⤸' },
        'sharp': { css: 'cubic-bezier(0.4, 0, 0.6, 1)', label: 'Sharp', icon: '⚡' },
        'smooth': { css: 'cubic-bezier(0.25, 0.1, 0.25, 1)', label: 'Smooth', icon: '◠' },
        'overshoot': { css: 'cubic-bezier(0.34, 1.56, 0.64, 1)', label: 'Overshoot', icon: '↗' }
    };

    /**
     * Propiedades animables disponibles
     */
    var ANIMATABLE_PROPERTIES = [
        { key: 'opacity', label: 'Opacidad', type: 'number', min: 0, max: 1, step: 0.1, unit: '', category: 'appearance' },
        { key: 'translateX', label: 'Mover X', type: 'number', min: -500, max: 500, step: 1, unit: 'px', category: 'transform' },
        { key: 'translateY', label: 'Mover Y', type: 'number', min: -500, max: 500, step: 1, unit: 'px', category: 'transform' },
        { key: 'translateZ', label: 'Mover Z', type: 'number', min: -500, max: 500, step: 1, unit: 'px', category: 'transform' },
        { key: 'rotate', label: 'Rotar', type: 'number', min: -360, max: 360, step: 1, unit: 'deg', category: 'transform' },
        { key: 'rotateX', label: 'Rotar X', type: 'number', min: -360, max: 360, step: 1, unit: 'deg', category: 'transform' },
        { key: 'rotateY', label: 'Rotar Y', type: 'number', min: -360, max: 360, step: 1, unit: 'deg', category: 'transform' },
        { key: 'scale', label: 'Escala', type: 'number', min: 0, max: 3, step: 0.1, unit: '', category: 'transform' },
        { key: 'scaleX', label: 'Escala X', type: 'number', min: 0, max: 3, step: 0.1, unit: '', category: 'transform' },
        { key: 'scaleY', label: 'Escala Y', type: 'number', min: 0, max: 3, step: 0.1, unit: '', category: 'transform' },
        { key: 'skewX', label: 'Sesgar X', type: 'number', min: -45, max: 45, step: 1, unit: 'deg', category: 'transform' },
        { key: 'skewY', label: 'Sesgar Y', type: 'number', min: -45, max: 45, step: 1, unit: 'deg', category: 'transform' },
        { key: 'backgroundColor', label: 'Fondo', type: 'color', category: 'colors' },
        { key: 'color', label: 'Color texto', type: 'color', category: 'colors' },
        { key: 'borderColor', label: 'Color borde', type: 'color', category: 'colors' },
        { key: 'boxShadow', label: 'Sombra', type: 'shadow', category: 'effects' },
        { key: 'filter', label: 'Filtro', type: 'filter', category: 'effects' }
    ];

    /**
     * Presets de animaciones predefinidas
     */
    var ANIMATION_PRESETS = {
        'fadeIn': {
            name: 'Fade In',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0 } },
                { offset: 100, properties: { opacity: 1 } }
            ]
        },
        'fadeOut': {
            name: 'Fade Out',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 1 } },
                { offset: 100, properties: { opacity: 0 } }
            ]
        },
        'fadeInUp': {
            name: 'Fade In Up',
            duration: '0.6s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, translateY: 30 } },
                { offset: 100, properties: { opacity: 1, translateY: 0 } }
            ]
        },
        'fadeInDown': {
            name: 'Fade In Down',
            duration: '0.6s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, translateY: -30 } },
                { offset: 100, properties: { opacity: 1, translateY: 0 } }
            ]
        },
        'fadeInLeft': {
            name: 'Fade In Left',
            duration: '0.6s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, translateX: -30 } },
                { offset: 100, properties: { opacity: 1, translateX: 0 } }
            ]
        },
        'fadeInRight': {
            name: 'Fade In Right',
            duration: '0.6s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, translateX: 30 } },
                { offset: 100, properties: { opacity: 1, translateX: 0 } }
            ]
        },
        'slideInUp': {
            name: 'Slide In Up',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { translateY: 100 } },
                { offset: 100, properties: { translateY: 0 } }
            ]
        },
        'slideInDown': {
            name: 'Slide In Down',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { translateY: -100 } },
                { offset: 100, properties: { translateY: 0 } }
            ]
        },
        'slideInLeft': {
            name: 'Slide In Left',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { translateX: -100 } },
                { offset: 100, properties: { translateX: 0 } }
            ]
        },
        'slideInRight': {
            name: 'Slide In Right',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { translateX: 100 } },
                { offset: 100, properties: { translateX: 0 } }
            ]
        },
        'zoomIn': {
            name: 'Zoom In',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, scale: 0.5 } },
                { offset: 100, properties: { opacity: 1, scale: 1 } }
            ]
        },
        'zoomOut': {
            name: 'Zoom Out',
            duration: '0.5s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { opacity: 0, scale: 1.5 } },
                { offset: 100, properties: { opacity: 1, scale: 1 } }
            ]
        },
        'bounce': {
            name: 'Bounce',
            duration: '0.8s',
            easing: 'ease-out',
            keyframes: [
                { offset: 0, properties: { translateY: -30 } },
                { offset: 50, properties: { translateY: 0 } },
                { offset: 70, properties: { translateY: -15 } },
                { offset: 85, properties: { translateY: 0 } },
                { offset: 92, properties: { translateY: -5 } },
                { offset: 100, properties: { translateY: 0 } }
            ]
        },
        'pulse': {
            name: 'Pulse',
            duration: '1s',
            easing: 'ease-in-out',
            iterations: 'infinite',
            keyframes: [
                { offset: 0, properties: { scale: 1 } },
                { offset: 50, properties: { scale: 1.05 } },
                { offset: 100, properties: { scale: 1 } }
            ]
        },
        'shake': {
            name: 'Shake',
            duration: '0.6s',
            easing: 'ease-in-out',
            keyframes: [
                { offset: 0, properties: { translateX: 0 } },
                { offset: 10, properties: { translateX: -10 } },
                { offset: 20, properties: { translateX: 10 } },
                { offset: 30, properties: { translateX: -10 } },
                { offset: 40, properties: { translateX: 10 } },
                { offset: 50, properties: { translateX: -10 } },
                { offset: 60, properties: { translateX: 10 } },
                { offset: 70, properties: { translateX: -10 } },
                { offset: 80, properties: { translateX: 10 } },
                { offset: 90, properties: { translateX: -5 } },
                { offset: 100, properties: { translateX: 0 } }
            ]
        },
        'flip': {
            name: 'Flip',
            duration: '0.6s',
            easing: 'ease-in-out',
            keyframes: [
                { offset: 0, properties: { rotateY: 0 } },
                { offset: 100, properties: { rotateY: 180 } }
            ]
        },
        'rotate360': {
            name: 'Rotate 360',
            duration: '1s',
            easing: 'linear',
            keyframes: [
                { offset: 0, properties: { rotate: 0 } },
                { offset: 100, properties: { rotate: 360 } }
            ]
        }
    };

    /**
     * Triggers disponibles para las animaciones
     */
    var ANIMATION_TRIGGERS = {
        'load': { label: 'Al cargar', icon: '⚡', description: 'Se ejecuta al cargar la pagina' },
        'hover': { label: 'Al pasar el cursor', icon: '👆', description: 'Se ejecuta al hacer hover' },
        'click': { label: 'Al hacer clic', icon: '🖱️', description: 'Se ejecuta al hacer clic' },
        'scroll': { label: 'Al hacer scroll', icon: '📜', description: 'Se ejecuta al entrar en viewport' }
    };

    /**
     * Genera un ID unico para animaciones
     */
    function generateAnimationId() {
        return 'anim_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Genera un ID unico para keyframes
     */
    function generateKeyframeId() {
        return 'kf_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Valida una duracion CSS
     */
    function parseDuration(durationValue) {
        if (typeof durationValue === 'number') {
            return durationValue;
        }
        var match = String(durationValue).match(/^([\d.]+)(ms|s)?$/);
        if (!match) return 500;
        var value = parseFloat(match[1]);
        var unit = match[2] || 's';
        return unit === 'ms' ? value : value * 1000;
    }

    /**
     * Formatea milisegundos a string CSS
     */
    function formatDuration(milliseconds) {
        if (milliseconds >= 1000) {
            return (milliseconds / 1000) + 's';
        }
        return milliseconds + 'ms';
    }

    /**
     * Convierte propiedades de keyframe a string CSS de transform
     */
    function buildTransformString(properties) {
        var transforms = [];

        if (properties.translateX !== undefined) {
            transforms.push('translateX(' + properties.translateX + 'px)');
        }
        if (properties.translateY !== undefined) {
            transforms.push('translateY(' + properties.translateY + 'px)');
        }
        if (properties.translateZ !== undefined) {
            transforms.push('translateZ(' + properties.translateZ + 'px)');
        }
        if (properties.rotate !== undefined) {
            transforms.push('rotate(' + properties.rotate + 'deg)');
        }
        if (properties.rotateX !== undefined) {
            transforms.push('rotateX(' + properties.rotateX + 'deg)');
        }
        if (properties.rotateY !== undefined) {
            transforms.push('rotateY(' + properties.rotateY + 'deg)');
        }
        if (properties.scale !== undefined) {
            transforms.push('scale(' + properties.scale + ')');
        }
        if (properties.scaleX !== undefined) {
            transforms.push('scaleX(' + properties.scaleX + ')');
        }
        if (properties.scaleY !== undefined) {
            transforms.push('scaleY(' + properties.scaleY + ')');
        }
        if (properties.skewX !== undefined) {
            transforms.push('skewX(' + properties.skewX + 'deg)');
        }
        if (properties.skewY !== undefined) {
            transforms.push('skewY(' + properties.skewY + 'deg)');
        }

        return transforms.length > 0 ? transforms.join(' ') : 'none';
    }

    /**
     * VBP Animation Builder - Componente Alpine.js
     */
    window.VBPAnimationBuilder = {
        /**
         * Estado del builder
         */
        animations: [],
        currentAnimationId: null,
        currentKeyframeIndex: null,
        isPlaying: false,
        isPanelOpen: false,
        previewElement: null,
        previewAnimationFrame: null,
        bezierEditorOpen: false,
        customBezierValues: [0.25, 0.1, 0.25, 1],

        /**
         * Easings predefinidos
         */
        easings: EASING_PRESETS,

        /**
         * Propiedades animables
         */
        animatableProps: ANIMATABLE_PROPERTIES,

        /**
         * Presets de animaciones
         */
        presets: ANIMATION_PRESETS,

        /**
         * Triggers disponibles
         */
        triggers: ANIMATION_TRIGGERS,

        /**
         * Inicializa el Animation Builder
         */
        init: function() {
            var self = this;

            // Registrar comando en la paleta
            if (window.VBPCommandPalette && typeof window.VBPCommandPalette.registerCommand === 'function') {
                window.VBPCommandPalette.registerCommand({
                    id: 'animation-builder',
                    label: 'Abrir Animation Builder',
                    category: 'tools',
                    icon: '🎬',
                    action: function() {
                        self.openPanel();
                    }
                });
            }

            // Escuchar evento de apertura
            document.addEventListener('vbp:open-animation-builder', function(event) {
                self.openPanel();
                if (event.detail && event.detail.elementId) {
                    self.loadAnimationsForElement(event.detail.elementId);
                }
            });

            // Cargar animaciones del elemento seleccionado
            this.watchSelectedElement();

            // Registrar atajos de teclado
            this.registerKeyboardShortcuts();

            console.log('[VBP Animation Builder] Initialized');
        },

        /**
         * Observa cambios en el elemento seleccionado
         */
        watchSelectedElement: function() {
            var self = this;
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                Alpine.effect(function() {
                    var selectedId = Alpine.store('vbp').selectedId;
                    if (selectedId && self.isPanelOpen) {
                        self.loadAnimationsForElement(selectedId);
                    }
                });
            }
        },

        /**
         * Abre el panel de animaciones
         */
        openPanel: function() {
            this.isPanelOpen = true;
            var selectedId = this.getSelectedElementId();
            if (selectedId) {
                this.loadAnimationsForElement(selectedId);
            }
        },

        /**
         * Cierra el panel de animaciones
         */
        closePanel: function() {
            this.isPanelOpen = false;
            this.stopPreview();
            this.currentAnimationId = null;
            this.currentKeyframeIndex = null;
        },

        /**
         * Obtiene el ID del elemento seleccionado
         */
        getSelectedElementId: function() {
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                return Alpine.store('vbp').selectedId;
            }
            return null;
        },

        /**
         * Obtiene el elemento seleccionado
         */
        getSelectedElement: function() {
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                return Alpine.store('vbp').getSelectedElement();
            }
            return null;
        },

        /**
         * Carga las animaciones de un elemento
         */
        loadAnimationsForElement: function(elementId) {
            var element = this.findElementById(elementId);
            if (element && element.data && element.data.animations) {
                this.animations = JSON.parse(JSON.stringify(element.data.animations));
            } else {
                this.animations = [];
            }
            this.currentAnimationId = null;
            this.currentKeyframeIndex = null;
        },

        /**
         * Busca un elemento por ID
         */
        findElementById: function(elementId) {
            if (window.Alpine && Alpine.store && Alpine.store('vbp')) {
                return Alpine.store('vbp').findElement(elementId);
            }
            return null;
        },

        /**
         * Guarda las animaciones en el elemento
         */
        saveAnimationsToElement: function() {
            var elementId = this.getSelectedElementId();
            if (!elementId) return false;

            var store = window.Alpine && Alpine.store && Alpine.store('vbp');
            if (!store) return false;

            store.updateElementData(elementId, 'animations', JSON.parse(JSON.stringify(this.animations)));
            return true;
        },

        /**
         * Crea una nueva animacion
         */
        createAnimation: function(name, presetId) {
            var newAnimation;

            if (presetId && this.presets[presetId]) {
                var preset = this.presets[presetId];
                newAnimation = {
                    id: generateAnimationId(),
                    name: name || preset.name,
                    duration: preset.duration,
                    delay: '0s',
                    easing: preset.easing,
                    iterations: preset.iterations || 1,
                    fillMode: 'forwards',
                    trigger: 'load',
                    keyframes: preset.keyframes.map(function(keyframeData) {
                        return {
                            id: generateKeyframeId(),
                            offset: keyframeData.offset,
                            properties: JSON.parse(JSON.stringify(keyframeData.properties))
                        };
                    })
                };
            } else {
                newAnimation = {
                    id: generateAnimationId(),
                    name: name || 'Nueva Animacion',
                    duration: '0.5s',
                    delay: '0s',
                    easing: 'ease-out',
                    iterations: 1,
                    fillMode: 'forwards',
                    trigger: 'load',
                    keyframes: [
                        { id: generateKeyframeId(), offset: 0, properties: { opacity: 1 } },
                        { id: generateKeyframeId(), offset: 100, properties: { opacity: 1 } }
                    ]
                };
            }

            this.animations.push(newAnimation);
            this.currentAnimationId = newAnimation.id;
            this.currentKeyframeIndex = null;
            this.saveAnimationsToElement();

            return newAnimation;
        },

        /**
         * Elimina una animacion
         */
        deleteAnimation: function(animationId) {
            var index = this.animations.findIndex(function(animationItem) {
                return animationItem.id === animationId;
            });

            if (index !== -1) {
                this.animations.splice(index, 1);
                if (this.currentAnimationId === animationId) {
                    this.currentAnimationId = null;
                    this.currentKeyframeIndex = null;
                }
                this.saveAnimationsToElement();
            }
        },

        /**
         * Duplica una animacion
         */
        duplicateAnimation: function(animationId) {
            var original = this.animations.find(function(animationItem) {
                return animationItem.id === animationId;
            });

            if (original) {
                var duplicate = JSON.parse(JSON.stringify(original));
                duplicate.id = generateAnimationId();
                duplicate.name = original.name + ' (copia)';
                duplicate.keyframes.forEach(function(keyframeItem) {
                    keyframeItem.id = generateKeyframeId();
                });

                this.animations.push(duplicate);
                this.currentAnimationId = duplicate.id;
                this.saveAnimationsToElement();

                return duplicate;
            }
            return null;
        },

        /**
         * Obtiene la animacion actual
         */
        getCurrentAnimation: function() {
            var self = this;
            return this.animations.find(function(animationItem) {
                return animationItem.id === self.currentAnimationId;
            });
        },

        /**
         * Selecciona una animacion
         */
        selectAnimation: function(animationId) {
            this.currentAnimationId = animationId;
            this.currentKeyframeIndex = null;
            this.stopPreview();
        },

        /**
         * Actualiza una propiedad de la animacion actual
         */
        updateAnimationProperty: function(property, value) {
            var animation = this.getCurrentAnimation();
            if (animation) {
                animation[property] = value;
                this.saveAnimationsToElement();
            }
        },

        /**
         * Agrega un keyframe a la animacion actual
         */
        addKeyframe: function(offset) {
            var animation = this.getCurrentAnimation();
            if (!animation) return null;

            // Calcular offset por defecto si no se proporciona
            if (offset === undefined) {
                var maxOffset = 0;
                animation.keyframes.forEach(function(keyframeItem) {
                    if (keyframeItem.offset > maxOffset) {
                        maxOffset = keyframeItem.offset;
                    }
                });
                offset = Math.min(maxOffset + 25, 100);
            }

            // Interpolar propiedades entre keyframes adyacentes
            var interpolatedProperties = this.interpolatePropertiesAtOffset(animation, offset);

            var newKeyframe = {
                id: generateKeyframeId(),
                offset: offset,
                properties: interpolatedProperties
            };

            animation.keyframes.push(newKeyframe);

            // Ordenar keyframes por offset
            animation.keyframes.sort(function(keyframeA, keyframeB) {
                return keyframeA.offset - keyframeB.offset;
            });

            this.currentKeyframeIndex = animation.keyframes.findIndex(function(keyframeItem) {
                return keyframeItem.id === newKeyframe.id;
            });

            this.saveAnimationsToElement();

            return newKeyframe;
        },

        /**
         * Interpola propiedades en un offset dado
         */
        interpolatePropertiesAtOffset: function(animation, offset) {
            if (!animation.keyframes || animation.keyframes.length === 0) {
                return { opacity: 1 };
            }

            // Encontrar keyframes antes y despues
            var prevKeyframe = null;
            var nextKeyframe = null;

            animation.keyframes.forEach(function(keyframeItem) {
                if (keyframeItem.offset <= offset) {
                    if (!prevKeyframe || keyframeItem.offset > prevKeyframe.offset) {
                        prevKeyframe = keyframeItem;
                    }
                }
                if (keyframeItem.offset >= offset) {
                    if (!nextKeyframe || keyframeItem.offset < nextKeyframe.offset) {
                        nextKeyframe = keyframeItem;
                    }
                }
            });

            if (!prevKeyframe && !nextKeyframe) {
                return { opacity: 1 };
            }

            if (!prevKeyframe) {
                return JSON.parse(JSON.stringify(nextKeyframe.properties));
            }

            if (!nextKeyframe || prevKeyframe === nextKeyframe) {
                return JSON.parse(JSON.stringify(prevKeyframe.properties));
            }

            // Interpolar
            var progress = (offset - prevKeyframe.offset) / (nextKeyframe.offset - prevKeyframe.offset);
            var result = {};

            Object.keys(prevKeyframe.properties).forEach(function(propKey) {
                var prevValue = prevKeyframe.properties[propKey];
                var nextValue = nextKeyframe.properties[propKey];

                if (nextValue === undefined) {
                    result[propKey] = prevValue;
                    return;
                }

                if (typeof prevValue === 'number' && typeof nextValue === 'number') {
                    result[propKey] = prevValue + (nextValue - prevValue) * progress;
                } else {
                    result[propKey] = progress < 0.5 ? prevValue : nextValue;
                }
            });

            return result;
        },

        /**
         * Elimina un keyframe de la animacion actual
         */
        removeKeyframe: function(keyframeIndex) {
            var animation = this.getCurrentAnimation();
            if (!animation || animation.keyframes.length <= 2) return false;

            animation.keyframes.splice(keyframeIndex, 1);

            if (this.currentKeyframeIndex === keyframeIndex) {
                this.currentKeyframeIndex = null;
            } else if (this.currentKeyframeIndex > keyframeIndex) {
                this.currentKeyframeIndex--;
            }

            this.saveAnimationsToElement();
            return true;
        },

        /**
         * Selecciona un keyframe
         */
        selectKeyframe: function(keyframeIndex) {
            this.currentKeyframeIndex = keyframeIndex;
        },

        /**
         * Obtiene el keyframe actual
         */
        getCurrentKeyframe: function() {
            var animation = this.getCurrentAnimation();
            if (animation && this.currentKeyframeIndex !== null) {
                return animation.keyframes[this.currentKeyframeIndex];
            }
            return null;
        },

        /**
         * Actualiza el offset de un keyframe
         */
        updateKeyframeOffset: function(keyframeIndex, newOffset) {
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            newOffset = Math.max(0, Math.min(100, newOffset));
            animation.keyframes[keyframeIndex].offset = newOffset;

            // Reordenar keyframes
            animation.keyframes.sort(function(keyframeA, keyframeB) {
                return keyframeA.offset - keyframeB.offset;
            });

            this.saveAnimationsToElement();
        },

        /**
         * Actualiza una propiedad de un keyframe
         */
        updateKeyframeProperty: function(keyframeIndex, property, value) {
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            animation.keyframes[keyframeIndex].properties[property] = value;
            this.saveAnimationsToElement();
        },

        /**
         * Elimina una propiedad de un keyframe
         */
        removeKeyframeProperty: function(keyframeIndex, property) {
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            delete animation.keyframes[keyframeIndex].properties[property];
            this.saveAnimationsToElement();
        },

        /**
         * Agrega una propiedad a un keyframe
         */
        addKeyframeProperty: function(keyframeIndex, property) {
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            var propConfig = this.animatableProps.find(function(propItem) {
                return propItem.key === property;
            });

            var defaultValue;
            if (propConfig) {
                if (propConfig.type === 'number') {
                    defaultValue = propConfig.key === 'opacity' ? 1 : 0;
                } else if (propConfig.type === 'color') {
                    defaultValue = '#000000';
                } else {
                    defaultValue = '';
                }
            } else {
                defaultValue = 0;
            }

            animation.keyframes[keyframeIndex].properties[property] = defaultValue;
            this.saveAnimationsToElement();
        },

        /**
         * Genera el CSS @keyframes para una animacion
         */
        generateCSS: function(animation) {
            if (!animation) {
                animation = this.getCurrentAnimation();
            }
            if (!animation) return '';

            var cssLines = ['@keyframes ' + this.sanitizeAnimationName(animation.name) + ' {'];

            animation.keyframes.forEach(function(keyframe) {
                var properties = keyframe.properties;
                var transformString = buildTransformString(properties);

                cssLines.push('  ' + keyframe.offset + '% {');

                // Propiedades no-transform
                if (properties.opacity !== undefined) {
                    cssLines.push('    opacity: ' + properties.opacity + ';');
                }
                if (properties.backgroundColor) {
                    cssLines.push('    background-color: ' + properties.backgroundColor + ';');
                }
                if (properties.color) {
                    cssLines.push('    color: ' + properties.color + ';');
                }
                if (properties.borderColor) {
                    cssLines.push('    border-color: ' + properties.borderColor + ';');
                }
                if (properties.boxShadow) {
                    cssLines.push('    box-shadow: ' + properties.boxShadow + ';');
                }
                if (properties.filter) {
                    cssLines.push('    filter: ' + properties.filter + ';');
                }

                // Transform
                if (transformString !== 'none') {
                    cssLines.push('    transform: ' + transformString + ';');
                }

                cssLines.push('  }');
            });

            cssLines.push('}');

            return cssLines.join('\n');
        },

        /**
         * Genera CSS de aplicacion de animacion
         */
        generateAnimationCSS: function(animation, selector) {
            if (!animation) return '';

            var easingValue = this.easings[animation.easing] ? this.easings[animation.easing].css : animation.easing;
            var iterationsValue = animation.iterations === 'infinite' ? 'infinite' : animation.iterations;

            var animationProperty = [
                this.sanitizeAnimationName(animation.name),
                animation.duration,
                easingValue,
                animation.delay,
                iterationsValue,
                animation.fillMode
            ].join(' ');

            return selector + ' {\n  animation: ' + animationProperty + ';\n}';
        },

        /**
         * Sanitiza el nombre de la animacion para CSS
         */
        sanitizeAnimationName: function(animationName) {
            return animationName
                .toLowerCase()
                .replace(/[^a-z0-9-_]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        },

        /**
         * Previsualiza la animacion en el elemento seleccionado
         */
        playPreview: function() {
            var self = this;
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            this.stopPreview();

            var elementId = this.getSelectedElementId();
            if (!elementId) return;

            // Buscar el elemento en el canvas
            var canvasElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!canvasElement) return;

            this.previewElement = canvasElement;
            this.isPlaying = true;

            // Generar CSS de keyframes
            var keyframesCSS = this.generateCSS(animation);

            // Crear style tag temporal
            var styleTag = document.createElement('style');
            styleTag.id = 'vbp-animation-preview-' + animation.id;
            styleTag.textContent = keyframesCSS;
            document.head.appendChild(styleTag);

            // Aplicar animacion
            var easingValue = this.easings[animation.easing] ? this.easings[animation.easing].css : animation.easing;
            var iterationsValue = animation.iterations === 'infinite' ? 'infinite' : animation.iterations;

            canvasElement.style.animation = [
                this.sanitizeAnimationName(animation.name),
                animation.duration,
                easingValue,
                animation.delay,
                iterationsValue,
                animation.fillMode
            ].join(' ');

            // Escuchar fin de animacion
            var handleAnimationEnd = function() {
                canvasElement.removeEventListener('animationend', handleAnimationEnd);
                if (!animation.iterations || animation.iterations !== 'infinite') {
                    self.isPlaying = false;
                }
            };

            canvasElement.addEventListener('animationend', handleAnimationEnd);
        },

        /**
         * Detiene la previsualizacion
         */
        stopPreview: function() {
            this.isPlaying = false;

            if (this.previewElement) {
                this.previewElement.style.animation = '';
                this.previewElement = null;
            }

            // Limpiar style tags de preview
            var styleTagsList = document.querySelectorAll('[id^="vbp-animation-preview-"]');
            styleTagsList.forEach(function(styleTagElement) {
                styleTagElement.remove();
            });

            if (this.previewAnimationFrame) {
                cancelAnimationFrame(this.previewAnimationFrame);
                this.previewAnimationFrame = null;
            }
        },

        /**
         * Toggle play/pause
         */
        togglePreview: function() {
            if (this.isPlaying) {
                this.stopPreview();
            } else {
                this.playPreview();
            }
        },

        /**
         * Exporta el CSS completo de todas las animaciones
         */
        exportAllCSS: function() {
            var self = this;
            var cssContent = '/* VBP Animation Builder - Generated CSS */\n\n';

            this.animations.forEach(function(animation) {
                cssContent += '/* Animation: ' + animation.name + ' */\n';
                cssContent += self.generateCSS(animation) + '\n\n';
            });

            return cssContent;
        },

        /**
         * Copia CSS al portapapeles
         */
        copyCSS: function(animation) {
            if (!animation) {
                animation = this.getCurrentAnimation();
            }
            if (!animation) return;

            var cssContent = this.generateCSS(animation);

            if (navigator.clipboard) {
                navigator.clipboard.writeText(cssContent).then(function() {
                    if (window.VBPToast) {
                        window.VBPToast.show('CSS copiado al portapapeles', 'success');
                    }
                });
            } else {
                // Fallback
                var textArea = document.createElement('textarea');
                textArea.value = cssContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                if (window.VBPToast) {
                    window.VBPToast.show('CSS copiado al portapapeles', 'success');
                }
            }
        },

        /**
         * Aplica un preset a la animacion actual
         */
        applyPreset: function(presetId) {
            var preset = this.presets[presetId];
            if (!preset) return;

            var animation = this.getCurrentAnimation();
            if (!animation) {
                this.createAnimation(preset.name, presetId);
                return;
            }

            animation.name = preset.name;
            animation.duration = preset.duration;
            animation.easing = preset.easing;
            if (preset.iterations) {
                animation.iterations = preset.iterations;
            }
            animation.keyframes = preset.keyframes.map(function(keyframeData) {
                return {
                    id: generateKeyframeId(),
                    offset: keyframeData.offset,
                    properties: JSON.parse(JSON.stringify(keyframeData.properties))
                };
            });

            this.saveAnimationsToElement();
        },

        /**
         * Abre el editor de curva bezier
         */
        openBezierEditor: function() {
            this.bezierEditorOpen = true;
        },

        /**
         * Cierra el editor de curva bezier
         */
        closeBezierEditor: function() {
            this.bezierEditorOpen = false;
        },

        /**
         * Aplica la curva bezier personalizada
         */
        applyCustomBezier: function() {
            var bezierValue = 'cubic-bezier(' + this.customBezierValues.join(', ') + ')';
            this.updateAnimationProperty('easing', bezierValue);
            this.closeBezierEditor();
        },

        /**
         * Obtiene el color del timeline para un offset
         */
        getTimelineColor: function(offset) {
            var hue = (offset / 100) * 240; // 0 = red, 100 = blue
            return 'hsl(' + hue + ', 70%, 50%)';
        },

        /**
         * Obtiene propiedades por categoria
         */
        getPropertiesByCategory: function(category) {
            return this.animatableProps.filter(function(propItem) {
                return propItem.category === category;
            });
        },

        /**
         * Verifica si una propiedad esta en uso en el keyframe actual
         */
        isPropertyUsed: function(property) {
            var keyframe = this.getCurrentKeyframe();
            return keyframe && keyframe.properties && keyframe.properties[property] !== undefined;
        },

        /**
         * Obtiene las propiedades disponibles para agregar
         */
        getAvailableProperties: function() {
            var self = this;
            var keyframe = this.getCurrentKeyframe();
            if (!keyframe) return this.animatableProps;

            return this.animatableProps.filter(function(propItem) {
                return !self.isPropertyUsed(propItem.key);
            });
        },

        /**
         * Valida el CSS generado
         * @param {string} cssString - CSS a validar
         * @return {object} - { valid: boolean, errors: array }
         */
        validateCSS: function(cssString) {
            var errors = [];
            var valid = true;

            if (!cssString || typeof cssString !== 'string') {
                return { valid: false, errors: ['CSS vacio o invalido'] };
            }

            // Verificar sintaxis de @keyframes
            var keyframesRegex = /@keyframes\s+[\w-]+\s*\{[\s\S]*?\}/g;
            var keyframesMatch = cssString.match(keyframesRegex);

            if (!keyframesMatch || keyframesMatch.length === 0) {
                errors.push('No se encontraron @keyframes validos');
                valid = false;
            }

            // Verificar porcentajes validos
            var percentageRegex = /(\d+(?:\.\d+)?)\s*%\s*\{/g;
            var percentMatch;
            while ((percentMatch = percentageRegex.exec(cssString)) !== null) {
                var percentValue = parseFloat(percentMatch[1]);
                if (percentValue < 0 || percentValue > 100) {
                    errors.push('Porcentaje invalido: ' + percentValue + '%');
                    valid = false;
                }
            }

            // Verificar propiedades CSS basicas
            var dangerousProps = ['expression', 'behavior', 'javascript'];
            dangerousProps.forEach(function(dangerousProp) {
                if (cssString.toLowerCase().indexOf(dangerousProp) !== -1) {
                    errors.push('Propiedad potencialmente peligrosa: ' + dangerousProp);
                    valid = false;
                }
            });

            return { valid: valid, errors: errors };
        },

        /**
         * Genera CSS validado y sanitizado
         * @param {object} animation - Animacion
         * @return {string|null} - CSS generado o null si hay errores
         */
        generateValidatedCSS: function(animation) {
            var cssContent = this.generateCSS(animation);
            var validation = this.validateCSS(cssContent);

            if (!validation.valid) {
                console.warn('[VBP Animation Builder] CSS validation errors:', validation.errors);
                if (window.VBPToast) {
                    window.VBPToast.show('Error en CSS generado: ' + validation.errors[0], 'error');
                }
                return null;
            }

            return cssContent;
        },

        /**
         * Preview en canvas con indicador visual
         */
        playPreviewWithIndicator: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return;

            var elementId = this.getSelectedElementId();
            if (!elementId) return;

            var canvasElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!canvasElement) return;

            // Agregar atributo de preview
            canvasElement.setAttribute('data-vbp-animation-preview', 'true');

            this.playPreview();

            // Actualizar indicador cuando empiece a reproducir
            if (this.isPlaying) {
                canvasElement.setAttribute('data-vbp-animation-playing', 'true');
            }
        },

        /**
         * Detiene preview y limpia indicadores
         */
        stopPreviewWithIndicator: function() {
            this.stopPreview();

            // Limpiar indicadores
            var previewElements = document.querySelectorAll('[data-vbp-animation-preview]');
            previewElements.forEach(function(el) {
                el.removeAttribute('data-vbp-animation-preview');
                el.removeAttribute('data-vbp-animation-playing');
            });
        },

        /**
         * Obtiene preview de la animacion actual como data URL del canvas
         * @return {string|null} - Data URL de la imagen o null
         */
        capturePreviewFrame: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return null;

            // Crear canvas para captura
            var previewCanvas = document.createElement('canvas');
            previewCanvas.width = 200;
            previewCanvas.height = 150;
            var ctx = previewCanvas.getContext('2d');

            // Dibujar fondo
            ctx.fillStyle = '#2a2a3e';
            ctx.fillRect(0, 0, 200, 150);

            // Dibujar elemento de preview
            var gradient = ctx.createLinearGradient(70, 45, 130, 105);
            gradient.addColorStop(0, '#6366f1');
            gradient.addColorStop(1, '#f59e0b');
            ctx.fillStyle = gradient;
            ctx.roundRect(70, 45, 60, 60, 8);
            ctx.fill();

            // Dibujar texto
            ctx.fillStyle = '#888';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(animation.name, 100, 130);

            return previewCanvas.toDataURL('image/png');
        },

        /**
         * Exporta animacion como archivo JSON
         * @param {object} animation - Animacion a exportar
         */
        exportAnimationJSON: function(animation) {
            if (!animation) {
                animation = this.getCurrentAnimation();
            }
            if (!animation) return;

            var exportData = {
                version: '1.0',
                type: 'vbp-animation',
                animation: JSON.parse(JSON.stringify(animation)),
                exportedAt: new Date().toISOString()
            };

            var blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);

            var downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.download = this.sanitizeAnimationName(animation.name) + '.vbp-anim.json';
            downloadLink.click();

            URL.revokeObjectURL(url);

            if (window.VBPToast) {
                window.VBPToast.show('Animacion exportada', 'success');
            }
        },

        /**
         * Importa animacion desde archivo JSON
         * @param {File} file - Archivo JSON
         * @return {Promise}
         */
        importAnimationJSON: function(file) {
            var self = this;

            return new Promise(function(resolve, reject) {
                var reader = new FileReader();

                reader.onload = function(event) {
                    try {
                        var data = JSON.parse(event.target.result);

                        if (!data.type || data.type !== 'vbp-animation') {
                            reject(new Error('Archivo no es una animacion VBP valida'));
                            return;
                        }

                        if (!data.animation || !data.animation.keyframes) {
                            reject(new Error('Formato de animacion invalido'));
                            return;
                        }

                        // Regenerar IDs para evitar conflictos
                        var importedAnimation = data.animation;
                        importedAnimation.id = 'anim_' + Math.random().toString(36).substr(2, 9);
                        importedAnimation.name = importedAnimation.name + ' (importada)';
                        importedAnimation.keyframes.forEach(function(keyframe) {
                            keyframe.id = 'kf_' + Math.random().toString(36).substr(2, 9);
                        });

                        self.animations.push(importedAnimation);
                        self.currentAnimationId = importedAnimation.id;
                        self.saveAnimationsToElement();

                        if (window.VBPToast) {
                            window.VBPToast.show('Animacion importada: ' + importedAnimation.name, 'success');
                        }

                        resolve(importedAnimation);
                    } catch (parseError) {
                        reject(parseError);
                    }
                };

                reader.onerror = function() {
                    reject(new Error('Error leyendo archivo'));
                };

                reader.readAsText(file);
            });
        },

        /**
         * Registra comandos de teclado
         */
        registerKeyboardShortcuts: function() {
            var self = this;

            // Registrar en el sistema de teclado modular si existe
            if (window.VBPKeyboardModular && typeof window.VBPKeyboardModular.registerShortcut === 'function') {
                // Espacio: Play/Pause preview
                window.VBPKeyboardModular.registerShortcut({
                    key: ' ',
                    context: 'animation-builder',
                    handler: function() {
                        if (self.isPanelOpen) {
                            self.togglePreview();
                            return true;
                        }
                        return false;
                    },
                    description: 'Play/Pause animacion'
                });

                // Delete: Eliminar keyframe seleccionado
                window.VBPKeyboardModular.registerShortcut({
                    key: 'Delete',
                    context: 'animation-builder',
                    handler: function() {
                        if (self.isPanelOpen && self.currentKeyframeIndex !== null) {
                            self.removeKeyframe(self.currentKeyframeIndex);
                            return true;
                        }
                        return false;
                    },
                    description: 'Eliminar keyframe'
                });

                // N: Nuevo keyframe
                window.VBPKeyboardModular.registerShortcut({
                    key: 'n',
                    context: 'animation-builder',
                    handler: function() {
                        if (self.isPanelOpen && self.currentAnimationId) {
                            self.addKeyframe();
                            return true;
                        }
                        return false;
                    },
                    description: 'Nuevo keyframe'
                });

                // D: Duplicar animacion
                window.VBPKeyboardModular.registerShortcut({
                    key: 'd',
                    ctrl: true,
                    context: 'animation-builder',
                    handler: function() {
                        if (self.isPanelOpen && self.currentAnimationId) {
                            self.duplicateAnimation(self.currentAnimationId);
                            return true;
                        }
                        return false;
                    },
                    description: 'Duplicar animacion'
                });

                // C: Copiar CSS
                window.VBPKeyboardModular.registerShortcut({
                    key: 'c',
                    ctrl: true,
                    shift: true,
                    context: 'animation-builder',
                    handler: function() {
                        if (self.isPanelOpen && self.currentAnimationId) {
                            self.copyCSS();
                            return true;
                        }
                        return false;
                    },
                    description: 'Copiar CSS al portapapeles'
                });
            }
        },

        /**
         * Obtiene estadisticas de las animaciones
         * @return {object} - Estadisticas
         */
        getAnimationStats: function() {
            var totalKeyframes = 0;
            var totalDuration = 0;
            var triggersUsed = {};

            this.animations.forEach(function(animation) {
                totalKeyframes += animation.keyframes ? animation.keyframes.length : 0;
                totalDuration += parseDuration(animation.duration);

                var trigger = animation.trigger || 'load';
                triggersUsed[trigger] = (triggersUsed[trigger] || 0) + 1;
            });

            return {
                totalAnimations: this.animations.length,
                totalKeyframes: totalKeyframes,
                averageDuration: this.animations.length > 0 ? totalDuration / this.animations.length : 0,
                triggersUsed: triggersUsed
            };
        },

        /**
         * Limpia todas las animaciones
         */
        clearAllAnimations: function() {
            if (this.animations.length === 0) return;

            if (!confirm('Eliminar todas las animaciones?')) return;

            this.animations = [];
            this.currentAnimationId = null;
            this.currentKeyframeIndex = null;
            this.stopPreview();
            this.saveAnimationsToElement();

            if (window.VBPToast) {
                window.VBPToast.show('Todas las animaciones eliminadas', 'info');
            }
        },

        // ============================================
        // Integracion con Scroll Animations
        // ============================================

        /**
         * Crea una animacion de scroll para el elemento actual
         * @param {object} scrollConfig - Configuracion de scroll
         * @returns {string|null} ID de la animacion
         */
        createScrollAnimation: function(scrollConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPScrollAnimations) {
                return window.VBPScrollAnimations.createScrollAnimation(elementId, scrollConfig);
            }
            return null;
        },

        /**
         * Aplica preset de scroll animation al elemento actual
         * @param {string} presetId - ID del preset
         * @returns {string|null} ID de la animacion
         */
        applyScrollPreset: function(presetId) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPScrollAnimations) {
                return window.VBPScrollAnimations.applyPreset(elementId, presetId);
            }
            return null;
        },

        /**
         * Obtiene los presets de scroll disponibles
         * @returns {object} Presets de scroll
         */
        getScrollPresets: function() {
            if (window.VBPScrollAnimations) {
                return window.VBPScrollAnimations.presets;
            }
            return {};
        },

        /**
         * Obtiene los triggers de scroll disponibles
         * @returns {object} Triggers de scroll
         */
        getScrollTriggers: function() {
            if (window.VBPScrollAnimations) {
                return window.VBPScrollAnimations.triggers;
            }
            return {};
        },

        // ============================================
        // Integracion con Advanced Animations
        // ============================================

        /**
         * Crea una animacion stagger para el elemento actual
         * @param {object} staggerConfig - Configuracion del stagger
         * @returns {string|null} ID de la animacion
         */
        createStagger: function(staggerConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createStagger(elementId, staggerConfig);
            }
            return null;
        },

        /**
         * Crea una animacion de motion path
         * @param {string} pathData - Datos SVG del path
         * @param {object} motionConfig - Configuracion
         * @returns {string|null} ID de la animacion
         */
        createMotionPath: function(pathData, motionConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createMotionPath(elementId, pathData, motionConfig);
            }
            return null;
        },

        /**
         * Crea una animacion spring con fisica
         * @param {object} springConfig - Configuracion del spring
         * @returns {string|null} ID de la animacion
         */
        createSpring: function(springConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createSpring(elementId, springConfig);
            }
            return null;
        },

        /**
         * Crea efecto magnetico
         * @param {object} magneticConfig - Configuracion
         * @returns {string|null} ID de la animacion
         */
        createMagnetic: function(magneticConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createMagnetic(elementId, magneticConfig);
            }
            return null;
        },

        /**
         * Crea animacion de drag
         * @param {object} dragConfig - Configuracion del drag
         * @returns {string|null} ID de la animacion
         */
        createDrag: function(dragConfig) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createDrag(elementId, dragConfig);
            }
            return null;
        },

        /**
         * Aplica preset avanzado al elemento actual
         * @param {string} presetId - ID del preset
         * @returns {string|null} ID de la animacion
         */
        applyAdvancedPreset: function(presetId) {
            var elementId = this.getSelectedElementId();
            if (!elementId) return null;

            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.applyPreset(elementId, presetId);
            }
            return null;
        },

        /**
         * Obtiene los presets avanzados disponibles
         * @returns {object} Presets avanzados
         */
        getAdvancedPresets: function() {
            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.presets;
            }
            return {};
        },

        // ============================================
        // Exportacion Mejorada
        // ============================================

        /**
         * Exporta la animacion actual como CSS @keyframes
         * @returns {string} CSS generado
         */
        exportAsCSS: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return '';

            return this.generateCSS(animation);
        },

        /**
         * Exporta la animacion actual como GSAP
         * @returns {string} Codigo GSAP
         */
        exportAsGSAP: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return '';

            var elementId = this.getSelectedElementId();
            var gsapCode = '// GSAP Animation\n';
            gsapCode += 'gsap.to("[data-vbp-id=\\"' + elementId + '\\"]", {\n';

            // Duracion y easing
            gsapCode += '  duration: ' + parseDuration(animation.duration) / 1000 + ',\n';
            gsapCode += '  ease: "' + this.convertEasingToGSAP(animation.easing) + '",\n';

            // Propiedades del ultimo keyframe
            if (animation.keyframes && animation.keyframes.length > 0) {
                var lastKeyframe = animation.keyframes[animation.keyframes.length - 1];
                for (var propKey in lastKeyframe.properties) {
                    if (lastKeyframe.properties.hasOwnProperty(propKey)) {
                        var propValue = lastKeyframe.properties[propKey];
                        if (typeof propValue === 'number') {
                            gsapCode += '  ' + propKey + ': ' + propValue + ',\n';
                        } else {
                            gsapCode += '  ' + propKey + ': "' + propValue + '",\n';
                        }
                    }
                }
            }

            if (animation.iterations === 'infinite') {
                gsapCode += '  repeat: -1,\n';
            } else if (animation.iterations > 1) {
                gsapCode += '  repeat: ' + (animation.iterations - 1) + ',\n';
            }

            gsapCode += '});';

            return gsapCode;
        },

        /**
         * Convierte easing a formato GSAP
         * @param {string} easingValue - Valor de easing
         * @returns {string} Easing GSAP
         */
        convertEasingToGSAP: function(easingValue) {
            var easingMap = {
                'linear': 'none',
                'ease': 'power1.inOut',
                'ease-in': 'power1.in',
                'ease-out': 'power1.out',
                'ease-in-out': 'power1.inOut',
                'bounce': 'bounce.out',
                'elastic': 'elastic.out(1, 0.3)',
                'sharp': 'power2.inOut',
                'smooth': 'power1.out',
                'overshoot': 'back.out(1.7)'
            };
            return easingMap[easingValue] || 'power1.out';
        },

        /**
         * Exporta la animacion actual como JSON (formato Lottie-like)
         * @returns {object|null} Objeto JSON
         */
        exportAsJSON: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return null;

            return {
                version: '1.0',
                type: 'vbp-animation',
                name: animation.name,
                duration: parseDuration(animation.duration),
                easing: animation.easing,
                iterations: animation.iterations,
                fillMode: animation.fillMode,
                trigger: animation.trigger,
                keyframes: animation.keyframes.map(function(keyframe) {
                    return {
                        offset: keyframe.offset / 100,
                        properties: JSON.parse(JSON.stringify(keyframe.properties))
                    };
                }),
                exportedAt: new Date().toISOString()
            };
        },

        /**
         * Exporta la animacion como codigo JS vanilla
         * @returns {string} Codigo JavaScript
         */
        exportAsVanillaJS: function() {
            var animation = this.getCurrentAnimation();
            if (!animation) return '';

            var elementId = this.getSelectedElementId();
            var jsCode = '// Vanilla JavaScript Animation\n';
            jsCode += 'const element = document.querySelector(\'[data-vbp-id="' + elementId + '"]\');\n\n';
            jsCode += 'const keyframes = [\n';

            animation.keyframes.forEach(function(keyframe, keyframeIndex) {
                jsCode += '  { ';
                var propEntries = [];
                for (var propKey in keyframe.properties) {
                    if (keyframe.properties.hasOwnProperty(propKey)) {
                        var propValue = keyframe.properties[propKey];
                        if (typeof propValue === 'number') {
                            propEntries.push(propKey + ': ' + propValue);
                        } else {
                            propEntries.push(propKey + ': "' + propValue + '"');
                        }
                    }
                }
                jsCode += propEntries.join(', ');
                jsCode += ', offset: ' + (keyframe.offset / 100) + ' }';
                if (keyframeIndex < animation.keyframes.length - 1) {
                    jsCode += ',';
                }
                jsCode += '\n';
            });

            jsCode += '];\n\n';
            jsCode += 'const options = {\n';
            jsCode += '  duration: ' + parseDuration(animation.duration) + ',\n';
            jsCode += '  easing: "' + (this.easings[animation.easing] ? this.easings[animation.easing].css : animation.easing) + '",\n';
            jsCode += '  iterations: ' + (animation.iterations === 'infinite' ? 'Infinity' : animation.iterations) + ',\n';
            jsCode += '  fill: "' + animation.fillMode + '"\n';
            jsCode += '};\n\n';
            jsCode += 'element.animate(keyframes, options);';

            return jsCode;
        },

        /**
         * Descarga la exportacion como archivo
         * @param {string} formatType - Formato: 'css', 'gsap', 'json', 'js'
         */
        downloadExport: function(formatType) {
            var contentToExport;
            var mimeType;
            var fileExtension;

            switch (formatType) {
                case 'css':
                    contentToExport = this.exportAsCSS();
                    mimeType = 'text/css';
                    fileExtension = 'css';
                    break;
                case 'gsap':
                    contentToExport = this.exportAsGSAP();
                    mimeType = 'text/javascript';
                    fileExtension = 'js';
                    break;
                case 'json':
                    contentToExport = JSON.stringify(this.exportAsJSON(), null, 2);
                    mimeType = 'application/json';
                    fileExtension = 'json';
                    break;
                case 'js':
                    contentToExport = this.exportAsVanillaJS();
                    mimeType = 'text/javascript';
                    fileExtension = 'js';
                    break;
                default:
                    return;
            }

            if (!contentToExport) return;

            var animation = this.getCurrentAnimation();
            var fileName = animation
                ? this.sanitizeAnimationName(animation.name) + '.' + fileExtension
                : 'animation.' + fileExtension;

            var blobToDownload = new Blob([contentToExport], { type: mimeType });
            var downloadUrl = URL.createObjectURL(blobToDownload);

            var downloadLink = document.createElement('a');
            downloadLink.href = downloadUrl;
            downloadLink.download = fileName;
            downloadLink.click();

            URL.revokeObjectURL(downloadUrl);

            if (window.VBPToast) {
                window.VBPToast.show('Exportado como ' + fileExtension.toUpperCase(), 'success');
            }
        },

        // ============================================
        // Timeline Avanzado
        // ============================================

        /**
         * Crea un timeline avanzado
         * @param {object} timelineConfig - Configuracion
         * @returns {object|null} Timeline creado
         */
        createAdvancedTimeline: function(timelineConfig) {
            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.createTimeline(null, timelineConfig || {});
            }
            return null;
        },

        /**
         * Agrega track al timeline
         * @param {string} timelineId - ID del timeline
         * @param {object} trackConfig - Configuracion del track
         * @returns {object|null} Track creado
         */
        addTrackToTimeline: function(timelineId, trackConfig) {
            if (window.VBPAdvancedAnimations) {
                return window.VBPAdvancedAnimations.addTimelineTrack(timelineId, trackConfig);
            }
            return null;
        },

        /**
         * Previsualiza el timeline en un tiempo especifico
         * @param {string} timelineId - ID del timeline
         * @param {number} previewTime - Tiempo en segundos
         */
        previewTimeline: function(timelineId, previewTime) {
            if (window.VBPAdvancedAnimations) {
                window.VBPAdvancedAnimations.previewTimeline(timelineId, previewTime);
            }
        },

        /**
         * Reproduce el timeline
         * @param {string} timelineId - ID del timeline
         * @param {number} startTime - Tiempo inicial
         */
        playTimeline: function(timelineId, startTime) {
            if (window.VBPAdvancedAnimations) {
                window.VBPAdvancedAnimations.playTimeline(timelineId, startTime);
            }
        },

        /**
         * Pausa el timeline
         * @param {string} timelineId - ID del timeline
         */
        pauseTimeline: function(timelineId) {
            if (window.VBPAdvancedAnimations) {
                window.VBPAdvancedAnimations.pauseTimeline(timelineId);
            }
        },

        // ============================================
        // Copiar/Pegar Keyframes
        // ============================================

        /**
         * Keyframe en el clipboard
         */
        clipboardKeyframe: null,

        /**
         * Copia el keyframe actual al clipboard
         */
        copyKeyframe: function() {
            var keyframe = this.getCurrentKeyframe();
            if (!keyframe) return;

            this.clipboardKeyframe = JSON.parse(JSON.stringify(keyframe));
            this.clipboardKeyframe.id = null; // Resetear ID para paste

            if (window.VBPToast) {
                window.VBPToast.show('Keyframe copiado', 'success');
            }
        },

        /**
         * Pega el keyframe del clipboard
         * @param {number} targetOffset - Offset donde pegar (opcional)
         */
        pasteKeyframe: function(targetOffset) {
            if (!this.clipboardKeyframe) return;

            var animation = this.getCurrentAnimation();
            if (!animation) return;

            var newKeyframe = JSON.parse(JSON.stringify(this.clipboardKeyframe));
            newKeyframe.id = 'kf_' + Math.random().toString(36).substr(2, 9);

            if (targetOffset !== undefined) {
                newKeyframe.offset = targetOffset;
            } else {
                // Usar offset del keyframe copiado + 10%, o el siguiente slot disponible
                newKeyframe.offset = Math.min(100, this.clipboardKeyframe.offset + 10);
            }

            animation.keyframes.push(newKeyframe);
            animation.keyframes.sort(function(keyframeA, keyframeB) {
                return keyframeA.offset - keyframeB.offset;
            });

            this.saveAnimationsToElement();

            if (window.VBPToast) {
                window.VBPToast.show('Keyframe pegado en ' + newKeyframe.offset + '%', 'success');
            }
        },

        /**
         * Obtiene todas las categorias de presets
         * @returns {object} Presets organizados por categoria
         */
        getAllPresetCategories: function() {
            var basicPresets = this.presets;
            var scrollPresets = this.getScrollPresets();
            var advancedPresets = this.getAdvancedPresets();

            return {
                basic: {
                    label: 'Basicas',
                    presets: basicPresets
                },
                scroll: {
                    label: 'Scroll',
                    presets: scrollPresets
                },
                advanced: {
                    label: 'Avanzadas',
                    presets: advancedPresets
                }
            };
        }
    };

    /**
     * Componente Alpine.js para el panel de Animation Builder
     */
    document.addEventListener('alpine:init', function() {
        if (window.Alpine && Alpine.data) {
            Alpine.data('vbpAnimationBuilder', function() {
                return Object.assign({}, window.VBPAnimationBuilder, {
                    init: function() {
                        window.VBPAnimationBuilder.init.call(this);
                    }
                });
            });
        }
    });

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPAnimationBuilder.init();
        });
    } else {
        VBPAnimationBuilder.init();
    }

    // Exponer globalmente
    window.VBPAnimationBuilder = VBPAnimationBuilder;

})();
