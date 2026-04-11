/**
 * VBP Advanced Animations - Sistema de animaciones avanzadas
 *
 * Implementa animaciones profesionales con soporte para:
 * - Stagger (secuencias escalonadas)
 * - Motion Path (animacion siguiendo trazados SVG)
 * - Morphing de formas SVG
 * - Fisica Spring/Bounce
 * - Gestos (drag, pinch, swipe)
 * - Timeline avanzado con tracks multiples
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Configuracion de easings con fisica
     */
    var PHYSICS_EASINGS = {
        'linear': function(progressValue) { return progressValue; },
        'ease-in': function(progressValue) { return progressValue * progressValue; },
        'ease-out': function(progressValue) { return 1 - Math.pow(1 - progressValue, 2); },
        'ease-in-out': function(progressValue) {
            return progressValue < 0.5
                ? 2 * progressValue * progressValue
                : 1 - Math.pow(-2 * progressValue + 2, 2) / 2;
        },
        'ease-in-cubic': function(progressValue) { return progressValue * progressValue * progressValue; },
        'ease-out-cubic': function(progressValue) { return 1 - Math.pow(1 - progressValue, 3); },
        'ease-in-out-cubic': function(progressValue) {
            return progressValue < 0.5
                ? 4 * progressValue * progressValue * progressValue
                : 1 - Math.pow(-2 * progressValue + 2, 3) / 2;
        },
        'ease-in-quart': function(progressValue) { return progressValue * progressValue * progressValue * progressValue; },
        'ease-out-quart': function(progressValue) { return 1 - Math.pow(1 - progressValue, 4); },
        'ease-in-out-quart': function(progressValue) {
            return progressValue < 0.5
                ? 8 * progressValue * progressValue * progressValue * progressValue
                : 1 - Math.pow(-2 * progressValue + 2, 4) / 2;
        },
        'ease-in-expo': function(progressValue) {
            return progressValue === 0 ? 0 : Math.pow(2, 10 * progressValue - 10);
        },
        'ease-out-expo': function(progressValue) {
            return progressValue === 1 ? 1 : 1 - Math.pow(2, -10 * progressValue);
        },
        'ease-in-back': function(progressValue) {
            var overshootAmount = 1.70158;
            return (overshootAmount + 1) * progressValue * progressValue * progressValue - overshootAmount * progressValue * progressValue;
        },
        'ease-out-back': function(progressValue) {
            var overshootAmount = 1.70158;
            return 1 + (overshootAmount + 1) * Math.pow(progressValue - 1, 3) + overshootAmount * Math.pow(progressValue - 1, 2);
        },
        'ease-in-elastic': function(progressValue) {
            var elasticConstant = (2 * Math.PI) / 3;
            return progressValue === 0 ? 0 : progressValue === 1 ? 1
                : -Math.pow(2, 10 * progressValue - 10) * Math.sin((progressValue * 10 - 10.75) * elasticConstant);
        },
        'ease-out-elastic': function(progressValue) {
            var elasticConstant = (2 * Math.PI) / 3;
            return progressValue === 0 ? 0 : progressValue === 1 ? 1
                : Math.pow(2, -10 * progressValue) * Math.sin((progressValue * 10 - 0.75) * elasticConstant) + 1;
        },
        'ease-out-bounce': function(progressValue) {
            var bounceCoefficient = 7.5625;
            var bounceThreshold = 2.75;
            if (progressValue < 1 / bounceThreshold) {
                return bounceCoefficient * progressValue * progressValue;
            } else if (progressValue < 2 / bounceThreshold) {
                return bounceCoefficient * (progressValue -= 1.5 / bounceThreshold) * progressValue + 0.75;
            } else if (progressValue < 2.5 / bounceThreshold) {
                return bounceCoefficient * (progressValue -= 2.25 / bounceThreshold) * progressValue + 0.9375;
            } else {
                return bounceCoefficient * (progressValue -= 2.625 / bounceThreshold) * progressValue + 0.984375;
            }
        }
    };

    /**
     * Presets de animacion avanzados
     */
    var ADVANCED_PRESETS = {
        'text-reveal': {
            name: 'Text Reveal',
            description: 'Revela texto letra por letra',
            type: 'stagger',
            target: 'chars',
            animation: {
                from: { opacity: 0, translateY: 20 },
                to: { opacity: 1, translateY: 0 }
            },
            stagger: 0.03,
            duration: 0.4,
            easing: 'ease-out'
        },
        'text-reveal-wave': {
            name: 'Text Reveal Wave',
            description: 'Revela texto con efecto onda',
            type: 'stagger',
            target: 'chars',
            animation: {
                from: { opacity: 0, translateY: 30, rotateX: -90 },
                to: { opacity: 1, translateY: 0, rotateX: 0 }
            },
            stagger: 0.02,
            from: 'center',
            duration: 0.5,
            easing: 'ease-out-back'
        },
        'image-ken-burns': {
            name: 'Ken Burns',
            description: 'Zoom lento cinematografico',
            type: 'keyframes',
            keyframes: [
                { offset: 0, scale: 1, translateX: 0, translateY: 0 },
                { offset: 100, scale: 1.15, translateX: -5, translateY: -5 }
            ],
            duration: 15,
            easing: 'linear',
            iterations: 'infinite',
            direction: 'alternate'
        },
        'card-flip-3d': {
            name: 'Card Flip 3D',
            description: 'Voltear tarjeta en 3D',
            type: 'keyframes',
            keyframes: [
                { offset: 0, rotateY: 0, scale: 1 },
                { offset: 50, rotateY: 90, scale: 1.1 },
                { offset: 100, rotateY: 180, scale: 1 }
            ],
            duration: 0.8,
            easing: 'ease-in-out',
            perspective: 1000
        },
        'liquid-morph': {
            name: 'Liquid Morph',
            description: 'Efecto liquido organico',
            type: 'keyframes',
            keyframes: [
                { offset: 0, borderRadius: '60% 40% 30% 70%/60% 30% 70% 40%' },
                { offset: 25, borderRadius: '30% 60% 70% 40%/50% 60% 30% 60%' },
                { offset: 50, borderRadius: '70% 30% 50% 50%/30% 30% 70% 70%' },
                { offset: 75, borderRadius: '40% 60% 30% 70%/60% 40% 50% 40%' },
                { offset: 100, borderRadius: '60% 40% 30% 70%/60% 30% 70% 40%' }
            ],
            duration: 8,
            easing: 'ease-in-out',
            iterations: 'infinite'
        },
        'glitch': {
            name: 'Glitch',
            description: 'Efecto de distorsion digital',
            type: 'keyframes',
            keyframes: [
                { offset: 0, filter: 'none', transform: 'translate(0)' },
                { offset: 10, filter: 'hue-rotate(90deg)', transform: 'translate(-2px, 2px)' },
                { offset: 20, filter: 'none', transform: 'translate(0)' },
                { offset: 30, filter: 'hue-rotate(-90deg)', transform: 'translate(2px, -2px)' },
                { offset: 40, filter: 'none', transform: 'translate(0)' },
                { offset: 50, filter: 'invert(100%)', transform: 'translate(-1px, 1px) skew(1deg)' },
                { offset: 60, filter: 'none', transform: 'translate(0)' },
                { offset: 70, filter: 'saturate(200%)', transform: 'translate(1px, -1px) skew(-1deg)' },
                { offset: 100, filter: 'none', transform: 'translate(0)' }
            ],
            duration: 0.5,
            easing: 'steps(1)',
            iterations: 1
        },
        'typewriter': {
            name: 'Typewriter',
            description: 'Efecto maquina de escribir',
            type: 'typewriter',
            speed: 50,
            cursor: true,
            cursorChar: '|',
            cursorBlink: true
        },
        'wave': {
            name: 'Wave',
            description: 'Onda a traves de elementos',
            type: 'stagger',
            target: 'children',
            animation: {
                from: { translateY: 0 },
                to: { translateY: -10 }
            },
            stagger: 0.05,
            from: 'start',
            duration: 0.3,
            easing: 'ease-in-out',
            yoyo: true,
            iterations: 'infinite'
        },
        'magnetic': {
            name: 'Magnetic',
            description: 'Efecto magnetico al cursor',
            type: 'magnetic',
            strength: 0.3,
            distance: 100,
            duration: 0.3,
            easing: 'ease-out'
        },
        'shake': {
            name: 'Shake',
            description: 'Sacudida horizontal',
            type: 'keyframes',
            keyframes: [
                { offset: 0, translateX: 0 },
                { offset: 10, translateX: -10 },
                { offset: 20, translateX: 10 },
                { offset: 30, translateX: -10 },
                { offset: 40, translateX: 10 },
                { offset: 50, translateX: -10 },
                { offset: 60, translateX: 10 },
                { offset: 70, translateX: -10 },
                { offset: 80, translateX: 10 },
                { offset: 90, translateX: -5 },
                { offset: 100, translateX: 0 }
            ],
            duration: 0.6,
            easing: 'ease-in-out'
        },
        'bounce-in': {
            name: 'Bounce In',
            description: 'Entrada con rebote',
            type: 'spring',
            from: { scale: 0, opacity: 0 },
            to: { scale: 1, opacity: 1 },
            stiffness: 200,
            damping: 15,
            mass: 1
        },
        'elastic-scale': {
            name: 'Elastic Scale',
            description: 'Escala con elasticidad',
            type: 'spring',
            from: { scale: 0 },
            to: { scale: 1 },
            stiffness: 100,
            damping: 10,
            mass: 1
        },
        'float': {
            name: 'Float',
            description: 'Flotacion suave',
            type: 'keyframes',
            keyframes: [
                { offset: 0, translateY: 0 },
                { offset: 50, translateY: -10 },
                { offset: 100, translateY: 0 }
            ],
            duration: 3,
            easing: 'ease-in-out',
            iterations: 'infinite'
        },
        'pulse-glow': {
            name: 'Pulse Glow',
            description: 'Pulso con brillo',
            type: 'keyframes',
            keyframes: [
                { offset: 0, boxShadow: '0 0 0 0 rgba(99, 102, 241, 0.7)' },
                { offset: 50, boxShadow: '0 0 0 20px rgba(99, 102, 241, 0)' },
                { offset: 100, boxShadow: '0 0 0 0 rgba(99, 102, 241, 0)' }
            ],
            duration: 2,
            easing: 'ease-in-out',
            iterations: 'infinite'
        },
        'rotate-3d': {
            name: 'Rotate 3D',
            description: 'Rotacion en tres dimensiones',
            type: 'keyframes',
            keyframes: [
                { offset: 0, rotateX: 0, rotateY: 0, rotateZ: 0 },
                { offset: 25, rotateX: 90, rotateY: 0, rotateZ: 0 },
                { offset: 50, rotateX: 90, rotateY: 90, rotateZ: 0 },
                { offset: 75, rotateX: 90, rotateY: 90, rotateZ: 90 },
                { offset: 100, rotateX: 0, rotateY: 0, rotateZ: 0 }
            ],
            duration: 4,
            easing: 'linear',
            iterations: 'infinite',
            perspective: 800
        }
    };

    /**
     * Genera un ID unico para animaciones avanzadas
     */
    function generateAdvancedAnimationId() {
        return 'adv_anim_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Simulador de fisica Spring
     */
    function SpringSimulator(springConfig) {
        this.stiffness = springConfig.stiffness || 100;
        this.damping = springConfig.damping || 10;
        this.mass = springConfig.mass || 1;
        this.velocity = 0;
        this.currentValue = 0;
        this.targetValue = 1;
    }

    SpringSimulator.prototype.step = function(deltaTime) {
        var springForce = -this.stiffness * (this.currentValue - this.targetValue);
        var dampingForce = -this.damping * this.velocity;
        var acceleration = (springForce + dampingForce) / this.mass;

        this.velocity += acceleration * deltaTime;
        this.currentValue += this.velocity * deltaTime;

        return this.currentValue;
    };

    SpringSimulator.prototype.isAtRest = function() {
        return Math.abs(this.velocity) < 0.01 && Math.abs(this.currentValue - this.targetValue) < 0.001;
    };

    SpringSimulator.prototype.reset = function() {
        this.currentValue = 0;
        this.velocity = 0;
    };

    /**
     * Parsea un path SVG y extrae puntos
     * @param {string} pathData - Atributo d del path SVG
     * @returns {Array} Array de puntos {x, y}
     */
    function parseSVGPath(pathData) {
        var pathPoints = [];
        var pathCommands = pathData.match(/[a-zA-Z][^a-zA-Z]*/g) || [];
        var currentX = 0;
        var currentY = 0;

        for (var cmdIndex = 0; cmdIndex < pathCommands.length; cmdIndex++) {
            var pathCmd = pathCommands[cmdIndex];
            var cmdType = pathCmd.charAt(0);
            var cmdParams = pathCmd.slice(1).trim().split(/[\s,]+/).map(parseFloat);

            switch (cmdType) {
                case 'M':
                case 'm':
                    currentX = cmdType === 'M' ? cmdParams[0] : currentX + cmdParams[0];
                    currentY = cmdType === 'M' ? cmdParams[1] : currentY + cmdParams[1];
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'L':
                case 'l':
                    currentX = cmdType === 'L' ? cmdParams[0] : currentX + cmdParams[0];
                    currentY = cmdType === 'L' ? cmdParams[1] : currentY + cmdParams[1];
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'H':
                case 'h':
                    currentX = cmdType === 'H' ? cmdParams[0] : currentX + cmdParams[0];
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'V':
                case 'v':
                    currentY = cmdType === 'V' ? cmdParams[0] : currentY + cmdParams[0];
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'C':
                case 'c':
                    // Bezier cubica - solo tomamos el punto final para simplificar
                    if (cmdType === 'C') {
                        currentX = cmdParams[4];
                        currentY = cmdParams[5];
                    } else {
                        currentX += cmdParams[4];
                        currentY += cmdParams[5];
                    }
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'Q':
                case 'q':
                    // Bezier cuadratica
                    if (cmdType === 'Q') {
                        currentX = cmdParams[2];
                        currentY = cmdParams[3];
                    } else {
                        currentX += cmdParams[2];
                        currentY += cmdParams[3];
                    }
                    pathPoints.push({ x: currentX, y: currentY });
                    break;
                case 'Z':
                case 'z':
                    // Cerrar path
                    if (pathPoints.length > 0) {
                        pathPoints.push({ x: pathPoints[0].x, y: pathPoints[0].y });
                    }
                    break;
            }
        }

        return pathPoints;
    }

    /**
     * Interpola posicion en un path dado un progreso
     * @param {Array} pathPoints - Array de puntos del path
     * @param {number} progressAmount - Progreso (0-1)
     * @returns {object} {x, y, angle}
     */
    function interpolatePathPosition(pathPoints, progressAmount) {
        if (pathPoints.length < 2) {
            return pathPoints[0] || { x: 0, y: 0, angle: 0 };
        }

        var totalSegments = pathPoints.length - 1;
        var segmentIndex = Math.floor(progressAmount * totalSegments);
        segmentIndex = Math.min(segmentIndex, totalSegments - 1);

        var segmentProgress = (progressAmount * totalSegments) - segmentIndex;

        var startPoint = pathPoints[segmentIndex];
        var endPoint = pathPoints[segmentIndex + 1];

        var interpolatedX = startPoint.x + (endPoint.x - startPoint.x) * segmentProgress;
        var interpolatedY = startPoint.y + (endPoint.y - startPoint.y) * segmentProgress;

        // Calcular angulo para autoRotate
        var deltaX = endPoint.x - startPoint.x;
        var deltaY = endPoint.y - startPoint.y;
        var angleRadians = Math.atan2(deltaY, deltaX) * (180 / Math.PI);

        return { x: interpolatedX, y: interpolatedY, angle: angleRadians };
    }

    /**
     * VBP Advanced Animations Manager
     */
    var VBPAdvancedAnimations = {
        /**
         * Animaciones activas
         */
        activeAnimations: new Map(),

        /**
         * Timelines activos
         */
        timelines: new Map(),

        /**
         * Springs activos
         */
        activeSprings: new Map(),

        /**
         * Elementos con magnetic effect
         */
        magneticElements: new Map(),

        /**
         * RAF ID global
         */
        rafId: null,

        /**
         * Easings disponibles
         */
        easings: PHYSICS_EASINGS,

        /**
         * Presets disponibles
         */
        presets: ADVANCED_PRESETS,

        /**
         * Inicializa el sistema
         */
        init: function() {
            var advAnimManager = this;

            // Registrar comando en la paleta
            if (window.VBPCommandPalette && typeof window.VBPCommandPalette.registerCommand === 'function') {
                window.VBPCommandPalette.registerCommand({
                    id: 'advanced-animations',
                    label: 'Animaciones Avanzadas',
                    category: 'animation',
                    icon: '🎭',
                    action: function() {
                        advAnimManager.openConfigPanel();
                    }
                });
            }

            // Iniciar loop de actualizacion
            this.startUpdateLoop();

            // Listener para efectos magneticos
            document.addEventListener('mousemove', function(mouseEvent) {
                advAnimManager.handleMouseMove(mouseEvent);
            });

            console.log('[VBP Advanced Animations] Initialized');
        },

        /**
         * Inicia el loop de actualizacion para springs y animaciones
         */
        startUpdateLoop: function() {
            var advAnimManager = this;
            var lastFrameTime = performance.now();

            function updateAnimationLoop(currentTime) {
                var deltaTime = (currentTime - lastFrameTime) / 1000;
                lastFrameTime = currentTime;

                // Actualizar springs
                advAnimManager.activeSprings.forEach(function(springData, springId) {
                    if (!springData.spring.isAtRest()) {
                        var springProgress = springData.spring.step(deltaTime);
                        advAnimManager.applySpringProgress(springData, springProgress);
                    }
                });

                advAnimManager.rafId = requestAnimationFrame(updateAnimationLoop);
            }

            this.rafId = requestAnimationFrame(updateAnimationLoop);
        },

        /**
         * Aplica el progreso de un spring a un elemento
         */
        applySpringProgress: function(springData, springProgress) {
            var targetElement = document.querySelector('[data-vbp-id="' + springData.elementId + '"]');
            if (!targetElement) return;

            var interpolatedProps = this.interpolateProperties(
                springData.from,
                springData.to,
                springProgress
            );

            this.applyProperties(targetElement, interpolatedProps);
        },

        /**
         * Crea una animacion stagger
         * @param {string} elementId - ID del elemento padre
         * @param {object} staggerConfig - Configuracion del stagger
         * @returns {string} ID de la animacion
         */
        createStagger: function(elementId, staggerConfig) {
            var staggerAnimId = generateAdvancedAnimationId();

            var containerElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!containerElement) return null;

            var targetChildren;
            if (staggerConfig.target === 'chars') {
                targetChildren = this.splitIntoChars(containerElement);
            } else if (staggerConfig.target === 'words') {
                targetChildren = this.splitIntoWords(containerElement);
            } else {
                targetChildren = Array.from(containerElement.children);
            }

            if (targetChildren.length === 0) return null;

            var staggerDelay = staggerConfig.stagger || 0.1;
            var animDuration = staggerConfig.duration || 0.5;
            var animEasing = staggerConfig.easing || 'ease-out';
            var staggerFrom = staggerConfig.from || 'start';
            var animDirection = staggerConfig.direction || 'normal';

            // Calcular orden de elementos
            var childIndices = this.calculateStaggerOrder(targetChildren.length, staggerFrom);

            // Aplicar estados iniciales
            var advAnimManager = this;
            targetChildren.forEach(function(childElement, childIndex) {
                if (staggerConfig.animation && staggerConfig.animation.from) {
                    advAnimManager.applyProperties(childElement, staggerConfig.animation.from);
                }
            });

            // Animar cada elemento con delay
            targetChildren.forEach(function(childElement, childIndex) {
                var orderIndex = childIndices.indexOf(childIndex);
                var childDelay = orderIndex * staggerDelay;

                setTimeout(function() {
                    childElement.style.transition = 'all ' + animDuration + 's ' + advAnimManager.getEasingCSS(animEasing);

                    if (staggerConfig.animation && staggerConfig.animation.to) {
                        advAnimManager.applyProperties(childElement, staggerConfig.animation.to);
                    }
                }, childDelay * 1000);
            });

            // Guardar referencia
            this.activeAnimations.set(staggerAnimId, {
                id: staggerAnimId,
                type: 'stagger',
                elementId: elementId,
                config: staggerConfig,
                children: targetChildren
            });

            return staggerAnimId;
        },

        /**
         * Calcula el orden de elementos para stagger
         */
        calculateStaggerOrder: function(elementCount, staggerFrom) {
            var orderIndices = [];
            var indexCounter;

            switch (staggerFrom) {
                case 'end':
                    for (indexCounter = elementCount - 1; indexCounter >= 0; indexCounter--) {
                        orderIndices.push(indexCounter);
                    }
                    break;
                case 'center':
                    var centerIndex = Math.floor(elementCount / 2);
                    orderIndices.push(centerIndex);
                    for (var offset = 1; offset <= centerIndex; offset++) {
                        if (centerIndex + offset < elementCount) orderIndices.push(centerIndex + offset);
                        if (centerIndex - offset >= 0) orderIndices.push(centerIndex - offset);
                    }
                    break;
                case 'random':
                    for (indexCounter = 0; indexCounter < elementCount; indexCounter++) {
                        orderIndices.push(indexCounter);
                    }
                    // Fisher-Yates shuffle
                    for (var shuffleIndex = orderIndices.length - 1; shuffleIndex > 0; shuffleIndex--) {
                        var randomIndex = Math.floor(Math.random() * (shuffleIndex + 1));
                        var tempValue = orderIndices[shuffleIndex];
                        orderIndices[shuffleIndex] = orderIndices[randomIndex];
                        orderIndices[randomIndex] = tempValue;
                    }
                    break;
                case 'edges':
                    for (var edgeIndex = 0; edgeIndex < Math.ceil(elementCount / 2); edgeIndex++) {
                        orderIndices.push(edgeIndex);
                        if (elementCount - 1 - edgeIndex !== edgeIndex) {
                            orderIndices.push(elementCount - 1 - edgeIndex);
                        }
                    }
                    break;
                default: // 'start'
                    for (indexCounter = 0; indexCounter < elementCount; indexCounter++) {
                        orderIndices.push(indexCounter);
                    }
            }

            return orderIndices;
        },

        /**
         * Divide el texto en caracteres individuales
         */
        splitIntoChars: function(containerElement) {
            var textContent = containerElement.textContent;
            containerElement.textContent = '';

            var charElements = [];
            for (var charIndex = 0; charIndex < textContent.length; charIndex++) {
                var charSpan = document.createElement('span');
                charSpan.className = 'vbp-anim-char';
                charSpan.style.display = 'inline-block';
                charSpan.textContent = textContent[charIndex] === ' ' ? '\u00A0' : textContent[charIndex];
                containerElement.appendChild(charSpan);
                charElements.push(charSpan);
            }

            return charElements;
        },

        /**
         * Divide el texto en palabras
         */
        splitIntoWords: function(containerElement) {
            var textContent = containerElement.textContent;
            var textWords = textContent.split(' ');
            containerElement.textContent = '';

            var wordElements = [];
            for (var wordIndex = 0; wordIndex < textWords.length; wordIndex++) {
                var wordSpan = document.createElement('span');
                wordSpan.className = 'vbp-anim-word';
                wordSpan.style.display = 'inline-block';
                wordSpan.textContent = textWords[wordIndex];
                containerElement.appendChild(wordSpan);

                if (wordIndex < textWords.length - 1) {
                    var spaceSpan = document.createElement('span');
                    spaceSpan.textContent = ' ';
                    containerElement.appendChild(spaceSpan);
                }

                wordElements.push(wordSpan);
            }

            return wordElements;
        },

        /**
         * Crea una animacion de motion path
         * @param {string} elementId - ID del elemento
         * @param {string} pathData - Datos del path SVG
         * @param {object} motionConfig - Configuracion
         * @returns {string} ID de la animacion
         */
        createMotionPath: function(elementId, pathData, motionConfig) {
            var motionAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var pathPoints = parseSVGPath(pathData);
            if (pathPoints.length < 2) return null;

            var animDuration = (motionConfig.duration || 2) * 1000;
            var autoRotate = motionConfig.autoRotate !== false;
            var alignToPath = motionConfig.align !== false;
            var animEasing = motionConfig.easing || 'ease-in-out';

            var animStartTime = null;
            var advAnimManager = this;

            function animateMotionPath(currentTime) {
                if (!animStartTime) animStartTime = currentTime;
                var elapsedTime = currentTime - animStartTime;
                var rawProgress = elapsedTime / animDuration;

                if (rawProgress >= 1) {
                    if (motionConfig.iterations === 'infinite' || (motionConfig.iterations && motionConfig.iterations > 1)) {
                        animStartTime = currentTime;
                        rawProgress = 0;
                    } else {
                        rawProgress = 1;
                    }
                }

                var easedProgress = advAnimManager.easings[animEasing]
                    ? advAnimManager.easings[animEasing](rawProgress)
                    : rawProgress;

                var pathPosition = interpolatePathPosition(pathPoints, easedProgress);

                var transformParts = [];
                transformParts.push('translate(' + pathPosition.x + 'px, ' + pathPosition.y + 'px)');

                if (autoRotate && alignToPath) {
                    transformParts.push('rotate(' + pathPosition.angle + 'deg)');
                }

                targetElement.style.transform = transformParts.join(' ');

                if (rawProgress < 1 || motionConfig.iterations === 'infinite') {
                    requestAnimationFrame(animateMotionPath);
                }
            }

            requestAnimationFrame(animateMotionPath);

            this.activeAnimations.set(motionAnimId, {
                id: motionAnimId,
                type: 'motion-path',
                elementId: elementId,
                config: motionConfig,
                pathPoints: pathPoints
            });

            return motionAnimId;
        },

        /**
         * Crea una animacion spring
         * @param {string} elementId - ID del elemento
         * @param {object} springConfig - Configuracion del spring
         * @returns {string} ID de la animacion
         */
        createSpring: function(elementId, springConfig) {
            var springAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var springSimulator = new SpringSimulator({
                stiffness: springConfig.stiffness || 100,
                damping: springConfig.damping || 10,
                mass: springConfig.mass || 1
            });

            var springAnimData = {
                id: springAnimId,
                elementId: elementId,
                spring: springSimulator,
                from: springConfig.from || {},
                to: springConfig.to || {},
                config: springConfig
            };

            // Aplicar estado inicial
            this.applyProperties(targetElement, springConfig.from || {});

            this.activeSprings.set(springAnimId, springAnimData);
            this.activeAnimations.set(springAnimId, {
                id: springAnimId,
                type: 'spring',
                elementId: elementId,
                config: springConfig
            });

            return springAnimId;
        },

        /**
         * Crea efecto bounce
         * @param {string} elementId - ID del elemento
         * @param {object} bounceConfig - Configuracion del bounce
         * @returns {string} ID de la animacion
         */
        createBounce: function(elementId, bounceConfig) {
            var bounceAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var bounceCount = bounceConfig.bounces || 3;
            var bounciness = bounceConfig.bounciness || 0.6;
            var initialVelocity = bounceConfig.velocity || 1;

            var keyframes = [];
            var currentOffset = 0;
            var currentAmplitude = initialVelocity;

            for (var bounceIndex = 0; bounceIndex <= bounceCount; bounceIndex++) {
                var bounceProgress = bounceIndex / bounceCount;
                var bounceOffset = Math.round(bounceProgress * 100);

                keyframes.push({
                    offset: bounceOffset,
                    translateY: 0
                });

                if (bounceIndex < bounceCount) {
                    var peakOffset = bounceOffset + Math.round((1 / bounceCount) * 50);
                    keyframes.push({
                        offset: peakOffset,
                        translateY: -currentAmplitude * 30
                    });
                    currentAmplitude *= bounciness;
                }
            }

            return this.playKeyframeAnimation(elementId, {
                keyframes: keyframes,
                duration: bounceConfig.duration || 1,
                easing: 'ease-out'
            });
        },

        /**
         * Configura efecto magnetico
         * @param {string} elementId - ID del elemento
         * @param {object} magneticConfig - Configuracion
         */
        createMagnetic: function(elementId, magneticConfig) {
            var magneticAnimId = generateAdvancedAnimationId();

            this.magneticElements.set(elementId, {
                id: magneticAnimId,
                strength: magneticConfig.strength || 0.3,
                distance: magneticConfig.distance || 100,
                duration: magneticConfig.duration || 0.3,
                easing: magneticConfig.easing || 'ease-out'
            });

            return magneticAnimId;
        },

        /**
         * Maneja movimiento del mouse para efectos magneticos
         */
        handleMouseMove: function(mouseEvent) {
            var advAnimManager = this;

            this.magneticElements.forEach(function(magneticConfig, elementId) {
                var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
                if (!targetElement) return;

                var elementRect = targetElement.getBoundingClientRect();
                var elementCenterX = elementRect.left + elementRect.width / 2;
                var elementCenterY = elementRect.top + elementRect.height / 2;

                var mouseX = mouseEvent.clientX;
                var mouseY = mouseEvent.clientY;

                var distanceFromMouse = Math.sqrt(
                    Math.pow(mouseX - elementCenterX, 2) +
                    Math.pow(mouseY - elementCenterY, 2)
                );

                if (distanceFromMouse < magneticConfig.distance) {
                    var pullStrength = (1 - distanceFromMouse / magneticConfig.distance) * magneticConfig.strength;
                    var pullX = (mouseX - elementCenterX) * pullStrength;
                    var pullY = (mouseY - elementCenterY) * pullStrength;

                    targetElement.style.transition = 'transform ' + magneticConfig.duration + 's ' + magneticConfig.easing;
                    targetElement.style.transform = 'translate(' + pullX + 'px, ' + pullY + 'px)';
                } else {
                    targetElement.style.transition = 'transform ' + magneticConfig.duration + 's ' + magneticConfig.easing;
                    targetElement.style.transform = 'translate(0, 0)';
                }
            });
        },

        /**
         * Crea animacion de gestos drag
         * @param {string} elementId - ID del elemento
         * @param {object} dragConfig - Configuracion
         * @returns {string} ID de la animacion
         */
        createDrag: function(elementId, dragConfig) {
            var dragAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var dragAxis = dragConfig.axis || 'both';
            var dragBounds = dragConfig.bounds || null;
            var onDragEnd = dragConfig.onDragEnd || null;

            var isDragging = false;
            var startX = 0;
            var startY = 0;
            var currentX = 0;
            var currentY = 0;

            targetElement.style.cursor = 'grab';
            targetElement.style.userSelect = 'none';

            function handleDragStart(startEvent) {
                isDragging = true;
                targetElement.style.cursor = 'grabbing';

                var clientX = startEvent.touches ? startEvent.touches[0].clientX : startEvent.clientX;
                var clientY = startEvent.touches ? startEvent.touches[0].clientY : startEvent.clientY;

                startX = clientX - currentX;
                startY = clientY - currentY;

                startEvent.preventDefault();
            }

            function handleDragMove(moveEvent) {
                if (!isDragging) return;

                var clientX = moveEvent.touches ? moveEvent.touches[0].clientX : moveEvent.clientX;
                var clientY = moveEvent.touches ? moveEvent.touches[0].clientY : moveEvent.clientY;

                var newX = clientX - startX;
                var newY = clientY - startY;

                // Aplicar restricciones de eje
                if (dragAxis === 'x') newY = currentY;
                if (dragAxis === 'y') newX = currentX;

                // Aplicar bounds
                if (dragBounds) {
                    if (dragBounds.left !== undefined) newX = Math.max(dragBounds.left, newX);
                    if (dragBounds.right !== undefined) newX = Math.min(dragBounds.right, newX);
                    if (dragBounds.top !== undefined) newY = Math.max(dragBounds.top, newY);
                    if (dragBounds.bottom !== undefined) newY = Math.min(dragBounds.bottom, newY);
                }

                currentX = newX;
                currentY = newY;

                targetElement.style.transform = 'translate(' + currentX + 'px, ' + currentY + 'px)';
            }

            function handleDragEnd() {
                if (!isDragging) return;
                isDragging = false;
                targetElement.style.cursor = 'grab';

                if (onDragEnd === 'snap-to-grid') {
                    var gridSize = dragConfig.gridSize || 50;
                    currentX = Math.round(currentX / gridSize) * gridSize;
                    currentY = Math.round(currentY / gridSize) * gridSize;
                    targetElement.style.transition = 'transform 0.2s ease-out';
                    targetElement.style.transform = 'translate(' + currentX + 'px, ' + currentY + 'px)';
                    setTimeout(function() {
                        targetElement.style.transition = '';
                    }, 200);
                } else if (onDragEnd === 'snap-to-origin') {
                    currentX = 0;
                    currentY = 0;
                    targetElement.style.transition = 'transform 0.3s ease-out';
                    targetElement.style.transform = 'translate(0, 0)';
                    setTimeout(function() {
                        targetElement.style.transition = '';
                    }, 300);
                }
            }

            targetElement.addEventListener('mousedown', handleDragStart);
            targetElement.addEventListener('touchstart', handleDragStart, { passive: false });
            document.addEventListener('mousemove', handleDragMove);
            document.addEventListener('touchmove', handleDragMove, { passive: false });
            document.addEventListener('mouseup', handleDragEnd);
            document.addEventListener('touchend', handleDragEnd);

            this.activeAnimations.set(dragAnimId, {
                id: dragAnimId,
                type: 'drag',
                elementId: elementId,
                config: dragConfig
            });

            return dragAnimId;
        },

        /**
         * Reproduce una animacion basada en keyframes
         */
        playKeyframeAnimation: function(elementId, keyframeConfig) {
            var keyframeAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var keyframes = keyframeConfig.keyframes || [];
            var animDuration = (keyframeConfig.duration || 1) * 1000;
            var animEasing = keyframeConfig.easing || 'ease-out';
            var iterationCount = keyframeConfig.iterations || 1;
            var animDirection = keyframeConfig.direction || 'normal';

            // Construir keyframes para Web Animations API
            var webAnimKeyframes = keyframes.map(function(keyframe) {
                var formattedKeyframe = { offset: keyframe.offset / 100 };

                for (var propKey in keyframe) {
                    if (propKey !== 'offset') {
                        formattedKeyframe[propKey] = keyframe[propKey];
                    }
                }

                return formattedKeyframe;
            });

            // Aplicar perspectiva si se necesita
            if (keyframeConfig.perspective) {
                targetElement.style.perspective = keyframeConfig.perspective + 'px';
            }

            // Usar Web Animations API si esta disponible
            if (targetElement.animate) {
                var animationEffect = targetElement.animate(webAnimKeyframes, {
                    duration: animDuration,
                    easing: this.getEasingCSS(animEasing),
                    iterations: iterationCount === 'infinite' ? Infinity : iterationCount,
                    direction: animDirection,
                    fill: 'forwards'
                });

                this.activeAnimations.set(keyframeAnimId, {
                    id: keyframeAnimId,
                    type: 'keyframes',
                    elementId: elementId,
                    animation: animationEffect,
                    config: keyframeConfig
                });
            }

            return keyframeAnimId;
        },

        /**
         * Crea un timeline avanzado
         * @param {string} timelineId - ID del timeline
         * @param {object} timelineConfig - Configuracion
         * @returns {object} Instancia del timeline
         */
        createTimeline: function(timelineId, timelineConfig) {
            var newTimeline = {
                id: timelineId || generateAdvancedAnimationId(),
                tracks: [],
                markers: {},
                duration: 0,
                currentTime: 0,
                isPlaying: false,
                isPaused: false,
                loop: timelineConfig.loop || false,
                loopStart: timelineConfig.loopStart || 0,
                loopEnd: timelineConfig.loopEnd || null,
                onUpdate: timelineConfig.onUpdate || null,
                onComplete: timelineConfig.onComplete || null
            };

            this.timelines.set(newTimeline.id, newTimeline);

            return newTimeline;
        },

        /**
         * Agrega un track al timeline
         */
        addTimelineTrack: function(timelineId, trackConfig) {
            var targetTimeline = this.timelines.get(timelineId);
            if (!targetTimeline) return null;

            var newTrack = {
                id: generateAdvancedAnimationId(),
                elementId: trackConfig.elementId,
                property: trackConfig.property,
                keyframes: trackConfig.keyframes || [],
                easing: trackConfig.easing || 'ease-out'
            };

            targetTimeline.tracks.push(newTrack);

            // Recalcular duracion
            var trackEndTime = 0;
            newTrack.keyframes.forEach(function(keyframe) {
                if (keyframe.time > trackEndTime) {
                    trackEndTime = keyframe.time;
                }
            });

            if (trackEndTime > targetTimeline.duration) {
                targetTimeline.duration = trackEndTime;
            }

            return newTrack;
        },

        /**
         * Agrega un marker al timeline
         */
        addTimelineMarker: function(timelineId, markerName, markerTime) {
            var targetTimeline = this.timelines.get(timelineId);
            if (!targetTimeline) return;

            targetTimeline.markers[markerName] = markerTime;
        },

        /**
         * Reproduce un timeline desde una posicion
         */
        playTimeline: function(timelineId, startTime) {
            var targetTimeline = this.timelines.get(timelineId);
            if (!targetTimeline) return;

            targetTimeline.currentTime = startTime || 0;
            targetTimeline.isPlaying = true;
            targetTimeline.isPaused = false;

            this.updateTimeline(targetTimeline);
        },

        /**
         * Pausa un timeline
         */
        pauseTimeline: function(timelineId) {
            var targetTimeline = this.timelines.get(timelineId);
            if (!targetTimeline) return;

            targetTimeline.isPaused = true;
        },

        /**
         * Busca una posicion en el timeline
         */
        seekTimeline: function(timelineId, seekTime) {
            var targetTimeline = this.timelines.get(timelineId);
            if (!targetTimeline) return;

            // Soportar markers
            if (typeof seekTime === 'string' && targetTimeline.markers[seekTime] !== undefined) {
                seekTime = targetTimeline.markers[seekTime];
            }

            targetTimeline.currentTime = seekTime;
            this.renderTimelineFrame(targetTimeline);
        },

        /**
         * Actualiza el timeline
         */
        updateTimeline: function(targetTimeline) {
            if (!targetTimeline.isPlaying || targetTimeline.isPaused) return;

            var advAnimManager = this;
            var lastUpdateTime = performance.now();

            function timelineUpdateLoop(currentTime) {
                if (!targetTimeline.isPlaying || targetTimeline.isPaused) return;

                var deltaTime = (currentTime - lastUpdateTime) / 1000;
                lastUpdateTime = currentTime;

                targetTimeline.currentTime += deltaTime;

                // Verificar loop
                var loopEndTime = targetTimeline.loopEnd || targetTimeline.duration;
                if (targetTimeline.currentTime >= loopEndTime) {
                    if (targetTimeline.loop) {
                        targetTimeline.currentTime = targetTimeline.loopStart;
                    } else {
                        targetTimeline.currentTime = loopEndTime;
                        targetTimeline.isPlaying = false;

                        if (targetTimeline.onComplete) {
                            targetTimeline.onComplete();
                        }
                        return;
                    }
                }

                advAnimManager.renderTimelineFrame(targetTimeline);

                if (targetTimeline.onUpdate) {
                    targetTimeline.onUpdate(targetTimeline.currentTime);
                }

                requestAnimationFrame(timelineUpdateLoop);
            }

            requestAnimationFrame(timelineUpdateLoop);
        },

        /**
         * Renderiza un frame del timeline
         */
        renderTimelineFrame: function(targetTimeline) {
            var currentTimeValue = targetTimeline.currentTime;
            var advAnimManager = this;

            targetTimeline.tracks.forEach(function(track) {
                var targetElement = document.querySelector('[data-vbp-id="' + track.elementId + '"]');
                if (!targetElement) return;

                // Encontrar keyframes anterior y siguiente
                var prevKeyframe = null;
                var nextKeyframe = null;

                for (var kfIndex = 0; kfIndex < track.keyframes.length; kfIndex++) {
                    var keyframe = track.keyframes[kfIndex];
                    if (keyframe.time <= currentTimeValue) {
                        if (!prevKeyframe || keyframe.time > prevKeyframe.time) {
                            prevKeyframe = keyframe;
                        }
                    }
                    if (keyframe.time >= currentTimeValue) {
                        if (!nextKeyframe || keyframe.time < nextKeyframe.time) {
                            nextKeyframe = keyframe;
                        }
                    }
                }

                if (!prevKeyframe && !nextKeyframe) return;
                if (!prevKeyframe) prevKeyframe = nextKeyframe;
                if (!nextKeyframe) nextKeyframe = prevKeyframe;

                // Interpolar valor
                var interpolatedValue;
                if (prevKeyframe === nextKeyframe) {
                    interpolatedValue = prevKeyframe.value;
                } else {
                    var frameProgress = (currentTimeValue - prevKeyframe.time) / (nextKeyframe.time - prevKeyframe.time);
                    var easingFn = advAnimManager.easings[track.easing] || advAnimManager.easings['linear'];
                    var easedProgress = easingFn(frameProgress);

                    if (typeof prevKeyframe.value === 'number') {
                        interpolatedValue = prevKeyframe.value + (nextKeyframe.value - prevKeyframe.value) * easedProgress;
                    } else {
                        interpolatedValue = easedProgress < 0.5 ? prevKeyframe.value : nextKeyframe.value;
                    }
                }

                // Aplicar valor
                var propStyles = {};
                propStyles[track.property] = interpolatedValue;
                advAnimManager.applyProperties(targetElement, propStyles);
            });
        },

        /**
         * Previsualiza el timeline en un tiempo especifico
         */
        previewTimeline: function(timelineId, previewTime) {
            this.seekTimeline(timelineId, previewTime);
        },

        /**
         * Aplica un preset a un elemento
         * @param {string} elementId - ID del elemento
         * @param {string} presetId - ID del preset
         * @returns {string|null} ID de la animacion
         */
        applyPreset: function(elementId, presetId) {
            var presetConfig = this.presets[presetId];
            if (!presetConfig) {
                console.warn('[VBP Advanced Animations] Preset not found:', presetId);
                return null;
            }

            switch (presetConfig.type) {
                case 'stagger':
                    return this.createStagger(elementId, presetConfig);
                case 'spring':
                    return this.createSpring(elementId, presetConfig);
                case 'keyframes':
                    return this.playKeyframeAnimation(elementId, presetConfig);
                case 'magnetic':
                    return this.createMagnetic(elementId, presetConfig);
                case 'typewriter':
                    return this.createTypewriter(elementId, presetConfig);
                default:
                    return this.playKeyframeAnimation(elementId, presetConfig);
            }
        },

        /**
         * Crea efecto typewriter
         */
        createTypewriter: function(elementId, typewriterConfig) {
            var typewriterAnimId = generateAdvancedAnimationId();

            var targetElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (!targetElement) return null;

            var originalText = targetElement.textContent;
            var charSpeed = typewriterConfig.speed || 50;
            var showCursor = typewriterConfig.cursor !== false;
            var cursorChar = typewriterConfig.cursorChar || '|';
            var cursorBlink = typewriterConfig.cursorBlink !== false;

            targetElement.textContent = '';

            var cursorSpan = null;
            if (showCursor) {
                cursorSpan = document.createElement('span');
                cursorSpan.className = 'vbp-typewriter-cursor';
                cursorSpan.textContent = cursorChar;
                if (cursorBlink) {
                    cursorSpan.style.animation = 'vbp-cursor-blink 0.7s infinite';
                }
                targetElement.appendChild(cursorSpan);
            }

            var charIndex = 0;
            var textSpan = document.createElement('span');
            targetElement.insertBefore(textSpan, cursorSpan);

            function typeNextChar() {
                if (charIndex < originalText.length) {
                    textSpan.textContent += originalText[charIndex];
                    charIndex++;
                    setTimeout(typeNextChar, charSpeed);
                }
            }

            typeNextChar();

            this.activeAnimations.set(typewriterAnimId, {
                id: typewriterAnimId,
                type: 'typewriter',
                elementId: elementId,
                config: typewriterConfig
            });

            return typewriterAnimId;
        },

        /**
         * Interpola propiedades entre dos estados
         */
        interpolateProperties: function(fromProps, toProps, progressValue) {
            var interpolatedResult = {};

            for (var propKey in toProps) {
                if (toProps.hasOwnProperty(propKey)) {
                    var fromValue = fromProps[propKey];
                    var toValue = toProps[propKey];

                    if (fromValue === undefined) fromValue = toValue;

                    if (typeof fromValue === 'number' && typeof toValue === 'number') {
                        interpolatedResult[propKey] = fromValue + (toValue - fromValue) * progressValue;
                    } else {
                        interpolatedResult[propKey] = progressValue < 0.5 ? fromValue : toValue;
                    }
                }
            }

            return interpolatedResult;
        },

        /**
         * Aplica propiedades CSS a un elemento
         */
        applyProperties: function(targetElement, cssProperties) {
            var transformProps = {};
            var otherProps = {};
            var transformPropertyKeys = ['translateX', 'translateY', 'translateZ', 'rotate', 'rotateX', 'rotateY', 'rotateZ', 'scale', 'scaleX', 'scaleY', 'skewX', 'skewY'];

            for (var propKey in cssProperties) {
                if (cssProperties.hasOwnProperty(propKey)) {
                    if (transformPropertyKeys.indexOf(propKey) !== -1) {
                        transformProps[propKey] = cssProperties[propKey];
                    } else {
                        otherProps[propKey] = cssProperties[propKey];
                    }
                }
            }

            // Construir transform
            var transformParts = [];
            if (transformProps.translateX !== undefined) {
                transformParts.push('translateX(' + transformProps.translateX + 'px)');
            }
            if (transformProps.translateY !== undefined) {
                transformParts.push('translateY(' + transformProps.translateY + 'px)');
            }
            if (transformProps.translateZ !== undefined) {
                transformParts.push('translateZ(' + transformProps.translateZ + 'px)');
            }
            if (transformProps.rotate !== undefined) {
                transformParts.push('rotate(' + transformProps.rotate + 'deg)');
            }
            if (transformProps.rotateX !== undefined) {
                transformParts.push('rotateX(' + transformProps.rotateX + 'deg)');
            }
            if (transformProps.rotateY !== undefined) {
                transformParts.push('rotateY(' + transformProps.rotateY + 'deg)');
            }
            if (transformProps.rotateZ !== undefined) {
                transformParts.push('rotateZ(' + transformProps.rotateZ + 'deg)');
            }
            if (transformProps.scale !== undefined) {
                transformParts.push('scale(' + transformProps.scale + ')');
            }
            if (transformProps.scaleX !== undefined) {
                transformParts.push('scaleX(' + transformProps.scaleX + ')');
            }
            if (transformProps.scaleY !== undefined) {
                transformParts.push('scaleY(' + transformProps.scaleY + ')');
            }
            if (transformProps.skewX !== undefined) {
                transformParts.push('skewX(' + transformProps.skewX + 'deg)');
            }
            if (transformProps.skewY !== undefined) {
                transformParts.push('skewY(' + transformProps.skewY + 'deg)');
            }

            if (transformParts.length > 0) {
                targetElement.style.transform = transformParts.join(' ');
            }

            // Aplicar otras propiedades
            for (var styleKey in otherProps) {
                if (otherProps.hasOwnProperty(styleKey)) {
                    var kebabKey = styleKey.replace(/([A-Z])/g, '-$1').toLowerCase();
                    targetElement.style[kebabKey] = otherProps[styleKey];
                }
            }
        },

        /**
         * Obtiene CSS de easing
         */
        getEasingCSS: function(easingName) {
            var easingCSSMap = {
                'linear': 'linear',
                'ease-in': 'ease-in',
                'ease-out': 'ease-out',
                'ease-in-out': 'ease-in-out',
                'ease-in-cubic': 'cubic-bezier(0.55, 0.055, 0.675, 0.19)',
                'ease-out-cubic': 'cubic-bezier(0.215, 0.61, 0.355, 1)',
                'ease-in-out-cubic': 'cubic-bezier(0.645, 0.045, 0.355, 1)',
                'ease-in-quart': 'cubic-bezier(0.895, 0.03, 0.685, 0.22)',
                'ease-out-quart': 'cubic-bezier(0.165, 0.84, 0.44, 1)',
                'ease-in-out-quart': 'cubic-bezier(0.77, 0, 0.175, 1)',
                'ease-in-expo': 'cubic-bezier(0.95, 0.05, 0.795, 0.035)',
                'ease-out-expo': 'cubic-bezier(0.19, 1, 0.22, 1)',
                'ease-in-back': 'cubic-bezier(0.6, -0.28, 0.735, 0.045)',
                'ease-out-back': 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                'ease-in-elastic': 'cubic-bezier(0.6, 0.04, 0.98, 0.335)',
                'ease-out-elastic': 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                'ease-out-bounce': 'cubic-bezier(0.34, 1.56, 0.64, 1)'
            };

            return easingCSSMap[easingName] || easingName;
        },

        /**
         * Detiene una animacion
         */
        stopAnimation: function(animationId) {
            var animData = this.activeAnimations.get(animationId);
            if (!animData) return;

            if (animData.animation && animData.animation.cancel) {
                animData.animation.cancel();
            }

            this.activeAnimations.delete(animationId);
            this.activeSprings.delete(animationId);
        },

        /**
         * Abre panel de configuracion
         */
        openConfigPanel: function() {
            document.dispatchEvent(new CustomEvent('vbp:open-advanced-animations-panel'));
        },

        /**
         * Exporta animacion como CSS @keyframes
         */
        exportAsCSS: function(animationId) {
            var animData = this.activeAnimations.get(animationId);
            if (!animData || animData.type !== 'keyframes') return '';

            var keyframes = animData.config.keyframes || [];
            var animName = 'vbp-anim-' + animationId;

            var cssContent = '@keyframes ' + animName + ' {\n';

            keyframes.forEach(function(keyframe) {
                cssContent += '  ' + keyframe.offset + '% {\n';

                for (var propKey in keyframe) {
                    if (propKey !== 'offset') {
                        var kebabKey = propKey.replace(/([A-Z])/g, '-$1').toLowerCase();
                        cssContent += '    ' + kebabKey + ': ' + keyframe[propKey] + ';\n';
                    }
                }

                cssContent += '  }\n';
            });

            cssContent += '}\n';

            return cssContent;
        },

        /**
         * Exporta animacion como JSON (formato Lottie-like)
         */
        exportAsJSON: function(animationId) {
            var animData = this.activeAnimations.get(animationId);
            if (!animData) return null;

            return {
                version: '1.0',
                type: 'vbp-animation',
                id: animationId,
                animationType: animData.type,
                config: animData.config,
                exportedAt: new Date().toISOString()
            };
        },

        /**
         * Destruye el sistema
         */
        destroy: function() {
            if (this.rafId) {
                cancelAnimationFrame(this.rafId);
                this.rafId = null;
            }

            this.activeAnimations.clear();
            this.timelines.clear();
            this.activeSprings.clear();
            this.magneticElements.clear();
        }
    };

    // Integrar con Alpine store
    document.addEventListener('alpine:init', function() {
        if (window.Alpine && Alpine.store) {
            var vbpStore = Alpine.store('vbp');
            if (vbpStore) {
                if (!vbpStore.animations) {
                    vbpStore.animations = {};
                }

                vbpStore.animations.advanced = {
                    easings: PHYSICS_EASINGS,
                    presets: ADVANCED_PRESETS,

                    createStagger: function(elementId, staggerConfig) {
                        return VBPAdvancedAnimations.createStagger(elementId, staggerConfig);
                    },

                    createMotionPath: function(elementId, pathData, motionConfig) {
                        return VBPAdvancedAnimations.createMotionPath(elementId, pathData, motionConfig);
                    },

                    createSpring: function(elementId, springConfig) {
                        return VBPAdvancedAnimations.createSpring(elementId, springConfig);
                    },

                    createBounce: function(elementId, bounceConfig) {
                        return VBPAdvancedAnimations.createBounce(elementId, bounceConfig);
                    },

                    createMagnetic: function(elementId, magneticConfig) {
                        return VBPAdvancedAnimations.createMagnetic(elementId, magneticConfig);
                    },

                    createDrag: function(elementId, dragConfig) {
                        return VBPAdvancedAnimations.createDrag(elementId, dragConfig);
                    },

                    createTimeline: function(timelineId, timelineConfig) {
                        return VBPAdvancedAnimations.createTimeline(timelineId, timelineConfig);
                    },

                    playTimeline: function(timelineId, startTime) {
                        return VBPAdvancedAnimations.playTimeline(timelineId, startTime);
                    },

                    previewTimeline: function(timelineId, previewTime) {
                        return VBPAdvancedAnimations.previewTimeline(timelineId, previewTime);
                    },

                    applyPreset: function(elementId, presetId) {
                        return VBPAdvancedAnimations.applyPreset(elementId, presetId);
                    },

                    exportAsCSS: function(animationId) {
                        return VBPAdvancedAnimations.exportAsCSS(animationId);
                    },

                    exportAsJSON: function(animationId) {
                        return VBPAdvancedAnimations.exportAsJSON(animationId);
                    }
                };
            }
        }

        VBPAdvancedAnimations.init();
    });

    // Inicializar si Alpine ya cargo
    if (document.readyState !== 'loading') {
        setTimeout(function() {
            if (!VBPAdvancedAnimations.rafId) {
                VBPAdvancedAnimations.init();
            }
        }, 100);
    }

    // Exponer globalmente
    window.VBPAdvancedAnimations = VBPAdvancedAnimations;

})();
