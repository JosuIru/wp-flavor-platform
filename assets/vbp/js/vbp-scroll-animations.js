/**
 * VBP Scroll Animations - Sistema de animaciones basadas en scroll
 *
 * Implementa animaciones activadas por scroll con soporte para:
 * - Scroll Into View (aparecer al entrar en viewport)
 * - Scroll Progress (animacion basada en % de scroll)
 * - Parallax (velocidad diferencial)
 * - Sticky animations (mientras el elemento esta sticky)
 * - Scroll Snap animations
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Configuracion de triggers de scroll disponibles
     */
    var SCROLL_TRIGGERS = {
        'scroll-into-view': {
            label: 'Al entrar en viewport',
            icon: '👁',
            description: 'Se ejecuta cuando el elemento entra en el viewport'
        },
        'scroll-progress': {
            label: 'Progreso de scroll',
            icon: '📊',
            description: 'Animacion vinculada al porcentaje de scroll'
        },
        'parallax': {
            label: 'Parallax',
            icon: '🎭',
            description: 'Movimiento a velocidad diferencial'
        },
        'sticky': {
            label: 'Sticky',
            icon: '📌',
            description: 'Se activa mientras el elemento esta sticky'
        },
        'scroll-snap': {
            label: 'Scroll Snap',
            icon: '🎯',
            description: 'Se activa al hacer snap a la seccion'
        }
    };

    /**
     * Presets de animaciones de scroll
     */
    var SCROLL_ANIMATION_PRESETS = {
        'fade-in-up': {
            name: 'Fade In Up',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, translateY: 40 },
                to: { opacity: 1, translateY: 0 }
            },
            duration: 0.6,
            easing: 'ease-out'
        },
        'fade-in-down': {
            name: 'Fade In Down',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, translateY: -40 },
                to: { opacity: 1, translateY: 0 }
            },
            duration: 0.6,
            easing: 'ease-out'
        },
        'fade-in-left': {
            name: 'Fade In Left',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, translateX: -40 },
                to: { opacity: 1, translateX: 0 }
            },
            duration: 0.6,
            easing: 'ease-out'
        },
        'fade-in-right': {
            name: 'Fade In Right',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, translateX: 40 },
                to: { opacity: 1, translateX: 0 }
            },
            duration: 0.6,
            easing: 'ease-out'
        },
        'zoom-in': {
            name: 'Zoom In',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, scale: 0.8 },
                to: { opacity: 1, scale: 1 }
            },
            duration: 0.5,
            easing: 'ease-out'
        },
        'zoom-out': {
            name: 'Zoom Out',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, scale: 1.2 },
                to: { opacity: 1, scale: 1 }
            },
            duration: 0.5,
            easing: 'ease-out'
        },
        'flip-in': {
            name: 'Flip In',
            trigger: 'scroll-into-view',
            animation: {
                from: { opacity: 0, rotateX: -90 },
                to: { opacity: 1, rotateX: 0 }
            },
            duration: 0.7,
            easing: 'ease-out'
        },
        'slide-reveal': {
            name: 'Slide Reveal',
            trigger: 'scroll-into-view',
            animation: {
                from: { clipPath: 'inset(0 100% 0 0)' },
                to: { clipPath: 'inset(0 0% 0 0)' }
            },
            duration: 0.8,
            easing: 'ease-out'
        },
        'parallax-slow': {
            name: 'Parallax Lento',
            trigger: 'parallax',
            speed: 0.3,
            direction: 'vertical'
        },
        'parallax-medium': {
            name: 'Parallax Medio',
            trigger: 'parallax',
            speed: 0.5,
            direction: 'vertical'
        },
        'parallax-fast': {
            name: 'Parallax Rapido',
            trigger: 'parallax',
            speed: 0.8,
            direction: 'vertical'
        },
        'progress-scale': {
            name: 'Escalar con Scroll',
            trigger: 'scroll-progress',
            animation: {
                from: { scale: 0.5 },
                to: { scale: 1 }
            },
            scrub: true
        },
        'progress-rotate': {
            name: 'Rotar con Scroll',
            trigger: 'scroll-progress',
            animation: {
                from: { rotate: 0 },
                to: { rotate: 360 }
            },
            scrub: true
        },
        'progress-blur': {
            name: 'Desenfocar con Scroll',
            trigger: 'scroll-progress',
            animation: {
                from: { filter: 'blur(10px)' },
                to: { filter: 'blur(0px)' }
            },
            scrub: true
        }
    };

    /**
     * Genera un ID unico para scroll animations
     */
    function generateScrollAnimationId() {
        return 'scroll_anim_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Parsea un valor de offset (puede ser porcentaje o pixeles)
     * @param {string|number} offsetValue - Valor del offset
     * @param {number} containerHeight - Altura del contenedor
     * @returns {number} Valor en pixeles
     */
    function parseOffset(offsetValue, containerHeight) {
        if (typeof offsetValue === 'number') {
            return offsetValue;
        }

        var stringValue = String(offsetValue);
        if (stringValue.indexOf('%') !== -1) {
            var percentValue = parseFloat(stringValue.replace('%', ''));
            return (percentValue / 100) * containerHeight;
        }

        return parseFloat(stringValue) || 0;
    }

    /**
     * Interpola entre dos valores segun el progreso
     * @param {number} startValue - Valor inicial
     * @param {number} endValue - Valor final
     * @param {number} progressAmount - Progreso (0-1)
     * @returns {number} Valor interpolado
     */
    function interpolateValue(startValue, endValue, progressAmount) {
        return startValue + (endValue - startValue) * progressAmount;
    }

    /**
     * Parsea un valor CSS numerico
     * @param {string|number} cssValue - Valor CSS
     * @returns {object} {value: number, unit: string}
     */
    function parseCSSValue(cssValue) {
        if (typeof cssValue === 'number') {
            return { value: cssValue, unit: '' };
        }

        var stringCssValue = String(cssValue);
        var match = stringCssValue.match(/^(-?[\d.]+)(px|%|em|rem|vh|vw|deg)?$/);
        if (match) {
            return { value: parseFloat(match[1]), unit: match[2] || '' };
        }

        return { value: 0, unit: '' };
    }

    /**
     * Construye string de transform desde objeto de propiedades
     * @param {object} transformProperties - Propiedades de transform
     * @returns {string} String CSS de transform
     */
    function buildTransformFromProperties(transformProperties) {
        var transformParts = [];

        if (transformProperties.translateX !== undefined) {
            var translateXValue = parseCSSValue(transformProperties.translateX);
            transformParts.push('translateX(' + translateXValue.value + (translateXValue.unit || 'px') + ')');
        }
        if (transformProperties.translateY !== undefined) {
            var translateYValue = parseCSSValue(transformProperties.translateY);
            transformParts.push('translateY(' + translateYValue.value + (translateYValue.unit || 'px') + ')');
        }
        if (transformProperties.translateZ !== undefined) {
            var translateZValue = parseCSSValue(transformProperties.translateZ);
            transformParts.push('translateZ(' + translateZValue.value + (translateZValue.unit || 'px') + ')');
        }
        if (transformProperties.rotate !== undefined) {
            var rotateValue = parseCSSValue(transformProperties.rotate);
            transformParts.push('rotate(' + rotateValue.value + (rotateValue.unit || 'deg') + ')');
        }
        if (transformProperties.rotateX !== undefined) {
            var rotateXValue = parseCSSValue(transformProperties.rotateX);
            transformParts.push('rotateX(' + rotateXValue.value + (rotateXValue.unit || 'deg') + ')');
        }
        if (transformProperties.rotateY !== undefined) {
            var rotateYValue = parseCSSValue(transformProperties.rotateY);
            transformParts.push('rotateY(' + rotateYValue.value + (rotateYValue.unit || 'deg') + ')');
        }
        if (transformProperties.rotateZ !== undefined) {
            var rotateZValue = parseCSSValue(transformProperties.rotateZ);
            transformParts.push('rotateZ(' + rotateZValue.value + (rotateZValue.unit || 'deg') + ')');
        }
        if (transformProperties.scale !== undefined) {
            transformParts.push('scale(' + transformProperties.scale + ')');
        }
        if (transformProperties.scaleX !== undefined) {
            transformParts.push('scaleX(' + transformProperties.scaleX + ')');
        }
        if (transformProperties.scaleY !== undefined) {
            transformParts.push('scaleY(' + transformProperties.scaleY + ')');
        }
        if (transformProperties.skewX !== undefined) {
            var skewXValue = parseCSSValue(transformProperties.skewX);
            transformParts.push('skewX(' + skewXValue.value + (skewXValue.unit || 'deg') + ')');
        }
        if (transformProperties.skewY !== undefined) {
            var skewYValue = parseCSSValue(transformProperties.skewY);
            transformParts.push('skewY(' + skewYValue.value + (skewYValue.unit || 'deg') + ')');
        }

        return transformParts.length > 0 ? transformParts.join(' ') : 'none';
    }

    /**
     * Interpola propiedades entre dos estados
     * @param {object} fromProperties - Estado inicial
     * @param {object} toProperties - Estado final
     * @param {number} progressAmount - Progreso (0-1)
     * @returns {object} Propiedades interpoladas
     */
    function interpolateProperties(fromProperties, toProperties, progressAmount) {
        var interpolatedResult = {};
        var allPropertyKeys = Object.keys(Object.assign({}, fromProperties, toProperties));

        for (var keyIndex = 0; keyIndex < allPropertyKeys.length; keyIndex++) {
            var propertyKey = allPropertyKeys[keyIndex];
            var fromValue = fromProperties[propertyKey];
            var toValue = toProperties[propertyKey];

            if (fromValue === undefined) fromValue = toValue;
            if (toValue === undefined) toValue = fromValue;

            if (typeof fromValue === 'number' && typeof toValue === 'number') {
                interpolatedResult[propertyKey] = interpolateValue(fromValue, toValue, progressAmount);
            } else if (typeof fromValue === 'string' && typeof toValue === 'string') {
                // Intentar parsear valores numericos con unidad
                var parsedFrom = parseCSSValue(fromValue);
                var parsedTo = parseCSSValue(toValue);

                if (parsedFrom.unit === parsedTo.unit) {
                    var interpolatedCssValue = interpolateValue(parsedFrom.value, parsedTo.value, progressAmount);
                    interpolatedResult[propertyKey] = interpolatedCssValue + parsedFrom.unit;
                } else {
                    // Si no se puede interpolar, usar el mas cercano al progreso actual
                    interpolatedResult[propertyKey] = progressAmount < 0.5 ? fromValue : toValue;
                }
            } else {
                interpolatedResult[propertyKey] = progressAmount < 0.5 ? fromValue : toValue;
            }
        }

        return interpolatedResult;
    }

    /**
     * Aplica propiedades CSS a un elemento
     * @param {HTMLElement} targetElement - Elemento DOM
     * @param {object} cssProperties - Propiedades a aplicar
     */
    function applyPropertiesToElement(targetElement, cssProperties) {
        var transformProps = {};
        var otherStyleProperties = {};

        var transformPropertyKeys = ['translateX', 'translateY', 'translateZ', 'rotate', 'rotateX', 'rotateY', 'rotateZ', 'scale', 'scaleX', 'scaleY', 'skewX', 'skewY'];

        for (var propertyKey in cssProperties) {
            if (cssProperties.hasOwnProperty(propertyKey)) {
                if (transformPropertyKeys.indexOf(propertyKey) !== -1) {
                    transformProps[propertyKey] = cssProperties[propertyKey];
                } else {
                    otherStyleProperties[propertyKey] = cssProperties[propertyKey];
                }
            }
        }

        // Aplicar transforms
        var transformString = buildTransformFromProperties(transformProps);
        if (transformString !== 'none') {
            targetElement.style.transform = transformString;
        }

        // Aplicar otras propiedades
        for (var styleProperty in otherStyleProperties) {
            if (otherStyleProperties.hasOwnProperty(styleProperty)) {
                var kebabCaseProperty = styleProperty.replace(/([A-Z])/g, '-$1').toLowerCase();
                targetElement.style[kebabCaseProperty] = otherStyleProperties[styleProperty];
            }
        }
    }

    /**
     * VBP Scroll Animations Manager
     */
    var VBPScrollAnimations = {
        /**
         * Almacen de animaciones activas
         */
        activeAnimations: new Map(),

        /**
         * Observer de interseccion para scroll-into-view
         */
        intersectionObserver: null,

        /**
         * Listener de scroll activo
         */
        scrollListenerActive: false,

        /**
         * Cache de posiciones de elementos
         */
        elementPositionCache: new Map(),

        /**
         * RAF ID para animaciones de scroll
         */
        scrollAnimationFrameId: null,

        /**
         * Triggers disponibles
         */
        triggers: SCROLL_TRIGGERS,

        /**
         * Presets disponibles
         */
        presets: SCROLL_ANIMATION_PRESETS,

        /**
         * Inicializa el sistema de scroll animations
         */
        init: function() {
            var scrollAnimManager = this;

            // Crear IntersectionObserver para scroll-into-view
            this.createIntersectionObserver();

            // Registrar comando en la paleta si existe
            if (window.VBPCommandPalette && typeof window.VBPCommandPalette.registerCommand === 'function') {
                window.VBPCommandPalette.registerCommand({
                    id: 'scroll-animations',
                    label: 'Configurar Scroll Animations',
                    category: 'animation',
                    icon: '📜',
                    action: function() {
                        scrollAnimManager.openConfigPanel();
                    }
                });
            }

            // Escuchar eventos de navegacion
            document.addEventListener('vbp:page-loaded', function() {
                scrollAnimManager.refreshAllAnimations();
            });

            console.log('[VBP Scroll Animations] Initialized');
        },

        /**
         * Crea el IntersectionObserver para detectar elementos en viewport
         */
        createIntersectionObserver: function() {
            var scrollAnimManager = this;

            if (!('IntersectionObserver' in window)) {
                console.warn('[VBP Scroll Animations] IntersectionObserver not supported');
                return;
            }

            this.intersectionObserver = new IntersectionObserver(
                function(observerEntries) {
                    for (var entryIndex = 0; entryIndex < observerEntries.length; entryIndex++) {
                        var observerEntry = observerEntries[entryIndex];
                        scrollAnimManager.handleIntersection(observerEntry);
                    }
                },
                {
                    root: null,
                    rootMargin: '0px',
                    threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]
                }
            );
        },

        /**
         * Maneja interseccion de un elemento
         * @param {IntersectionObserverEntry} intersectionEntry - Entrada del observer
         */
        handleIntersection: function(intersectionEntry) {
            var targetElement = intersectionEntry.target;
            var elementId = targetElement.getAttribute('data-vbp-scroll-id');

            if (!elementId) return;

            var animationConfig = this.activeAnimations.get(elementId);
            if (!animationConfig) return;

            var visibilityProgress = intersectionEntry.intersectionRatio;
            var elementIsVisible = intersectionEntry.isIntersecting;

            if (animationConfig.trigger === 'scroll-into-view') {
                this.handleScrollIntoView(targetElement, animationConfig, elementIsVisible, visibilityProgress);
            } else if (animationConfig.trigger === 'scroll-progress') {
                this.handleScrollProgress(targetElement, animationConfig, visibilityProgress);
            }
        },

        /**
         * Maneja animacion scroll-into-view
         */
        handleScrollIntoView: function(targetElement, animationConfig, elementIsVisible, visibilityProgress) {
            var triggerThreshold = animationConfig.offset ? parseFloat(animationConfig.offset) / 100 : 0.2;

            if (elementIsVisible && visibilityProgress >= triggerThreshold) {
                if (!targetElement.hasAttribute('data-vbp-animated')) {
                    this.playAnimation(targetElement, animationConfig);
                    targetElement.setAttribute('data-vbp-animated', 'true');

                    // Si once es true, dejar de observar
                    if (animationConfig.once !== false && this.intersectionObserver) {
                        this.intersectionObserver.unobserve(targetElement);
                    }
                }
            } else if (!elementIsVisible && animationConfig.once === false) {
                // Resetear para re-animar cuando vuelva a entrar
                targetElement.removeAttribute('data-vbp-animated');
                this.resetAnimation(targetElement, animationConfig);
            }
        },

        /**
         * Maneja animacion scroll-progress
         */
        handleScrollProgress: function(targetElement, animationConfig, scrollProgress) {
            if (animationConfig.scrub) {
                var interpolatedProps = interpolateProperties(
                    animationConfig.animation.from,
                    animationConfig.animation.to,
                    scrollProgress
                );
                applyPropertiesToElement(targetElement, interpolatedProps);
            }
        },

        /**
         * Reproduce una animacion
         */
        playAnimation: function(targetElement, animationConfig) {
            var animDuration = (animationConfig.duration || 0.5) * 1000;
            var animEasing = animationConfig.easing || 'ease-out';
            var animDelay = (animationConfig.delay || 0) * 1000;

            // Aplicar estado inicial
            if (animationConfig.animation && animationConfig.animation.from) {
                applyPropertiesToElement(targetElement, animationConfig.animation.from);
            }

            // Forzar reflow
            void targetElement.offsetWidth;

            // Configurar transicion
            targetElement.style.transition = 'all ' + (animDuration / 1000) + 's ' + animEasing + ' ' + (animDelay / 1000) + 's';

            // Aplicar estado final
            requestAnimationFrame(function() {
                if (animationConfig.animation && animationConfig.animation.to) {
                    applyPropertiesToElement(targetElement, animationConfig.animation.to);
                }
            });

            // Disparar evento
            this.dispatchAnimationEvent('vbp:scroll-animation:play', {
                element: targetElement,
                config: animationConfig
            });
        },

        /**
         * Resetea una animacion a su estado inicial
         */
        resetAnimation: function(targetElement, animationConfig) {
            targetElement.style.transition = 'none';

            if (animationConfig.animation && animationConfig.animation.from) {
                applyPropertiesToElement(targetElement, animationConfig.animation.from);
            }

            this.dispatchAnimationEvent('vbp:scroll-animation:reset', {
                element: targetElement,
                config: animationConfig
            });
        },

        /**
         * Crea una animacion de scroll para un elemento
         * @param {string} elementId - ID del elemento VBP
         * @param {object} scrollConfig - Configuracion de la animacion
         * @returns {string} ID de la animacion creada
         */
        createScrollAnimation: function(elementId, scrollConfig) {
            var scrollAnimationId = generateScrollAnimationId();

            var animationConfigObject = {
                id: scrollAnimationId,
                elementId: elementId,
                trigger: scrollConfig.trigger || 'scroll-into-view',
                offset: scrollConfig.offset || '20%',
                duration: scrollConfig.duration || 0.5,
                easing: scrollConfig.easing || 'ease-out',
                once: scrollConfig.once !== undefined ? scrollConfig.once : true,
                scrub: scrollConfig.scrub || false,
                animation: scrollConfig.animation || null,
                speed: scrollConfig.speed || 0.5,
                direction: scrollConfig.direction || 'vertical',
                enabled: true
            };

            this.activeAnimations.set(scrollAnimationId, animationConfigObject);

            // Buscar y observar el elemento en el DOM
            var domElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
            if (domElement) {
                domElement.setAttribute('data-vbp-scroll-id', scrollAnimationId);

                if (animationConfigObject.trigger === 'scroll-into-view' || animationConfigObject.trigger === 'scroll-progress') {
                    if (this.intersectionObserver) {
                        this.intersectionObserver.observe(domElement);
                    }
                } else if (animationConfigObject.trigger === 'parallax') {
                    this.setupParallax(domElement, animationConfigObject);
                } else if (animationConfigObject.trigger === 'sticky') {
                    this.setupStickyAnimation(domElement, animationConfigObject);
                }
            }

            // Guardar en el elemento del store
            this.saveToElement(elementId, animationConfigObject);

            return scrollAnimationId;
        },

        /**
         * Configura animacion parallax
         */
        setupParallax: function(targetElement, parallaxConfig) {
            var scrollAnimManager = this;

            if (!this.scrollListenerActive) {
                this.startScrollListener();
            }

            // Guardar configuracion en el elemento
            targetElement.setAttribute('data-vbp-parallax', JSON.stringify({
                speed: parallaxConfig.speed,
                direction: parallaxConfig.direction
            }));

            // Aplicar estilos iniciales
            targetElement.style.willChange = 'transform';
        },

        /**
         * Configura animacion sticky
         */
        setupStickyAnimation: function(targetElement, stickyConfig) {
            var scrollAnimManager = this;

            // Usar IntersectionObserver con rootMargin para detectar sticky
            var stickyObserver = new IntersectionObserver(
                function(stickyEntries) {
                    for (var stickyEntryIndex = 0; stickyEntryIndex < stickyEntries.length; stickyEntryIndex++) {
                        var stickyEntry = stickyEntries[stickyEntryIndex];
                        var isElementSticky = stickyEntry.intersectionRatio < 1 && stickyEntry.boundingClientRect.top <= 0;

                        if (isElementSticky) {
                            stickyEntry.target.classList.add('vbp-is-sticky');
                            scrollAnimManager.playAnimation(stickyEntry.target, stickyConfig);
                        } else {
                            stickyEntry.target.classList.remove('vbp-is-sticky');
                            scrollAnimManager.resetAnimation(stickyEntry.target, stickyConfig);
                        }
                    }
                },
                {
                    threshold: [1],
                    rootMargin: '-1px 0px 0px 0px'
                }
            );

            stickyObserver.observe(targetElement);
        },

        /**
         * Inicia el listener de scroll para parallax y otras animaciones
         */
        startScrollListener: function() {
            if (this.scrollListenerActive) return;

            var scrollAnimManager = this;
            this.scrollListenerActive = true;

            var lastScrollY = window.pageYOffset;
            var ticking = false;

            function handleScrollUpdate() {
                var currentScrollY = window.pageYOffset;
                scrollAnimManager.updateParallaxElements(currentScrollY);
                lastScrollY = currentScrollY;
                ticking = false;
            }

            function requestScrollUpdate() {
                if (!ticking) {
                    scrollAnimManager.scrollAnimationFrameId = requestAnimationFrame(handleScrollUpdate);
                    ticking = true;
                }
            }

            window.addEventListener('scroll', requestScrollUpdate, { passive: true });
        },

        /**
         * Actualiza elementos parallax
         */
        updateParallaxElements: function(currentScrollY) {
            var parallaxElements = document.querySelectorAll('[data-vbp-parallax]');

            for (var parallaxIndex = 0; parallaxIndex < parallaxElements.length; parallaxIndex++) {
                var parallaxElement = parallaxElements[parallaxIndex];
                var parallaxData = JSON.parse(parallaxElement.getAttribute('data-vbp-parallax') || '{}');

                var parallaxSpeed = parallaxData.speed || 0.5;
                var parallaxDirection = parallaxData.direction || 'vertical';

                var elementRect = parallaxElement.getBoundingClientRect();
                var elementCenter = elementRect.top + elementRect.height / 2;
                var viewportCenter = window.innerHeight / 2;

                var distanceFromCenter = elementCenter - viewportCenter;
                var parallaxOffset = distanceFromCenter * (1 - parallaxSpeed);

                if (parallaxDirection === 'vertical') {
                    parallaxElement.style.transform = 'translateY(' + parallaxOffset + 'px)';
                } else if (parallaxDirection === 'horizontal') {
                    parallaxElement.style.transform = 'translateX(' + parallaxOffset + 'px)';
                }
            }
        },

        /**
         * Elimina una animacion de scroll
         * @param {string} scrollAnimationId - ID de la animacion
         */
        removeScrollAnimation: function(scrollAnimationId) {
            var animConfig = this.activeAnimations.get(scrollAnimationId);
            if (!animConfig) return;

            var targetElement = document.querySelector('[data-vbp-scroll-id="' + scrollAnimationId + '"]');
            if (targetElement) {
                if (this.intersectionObserver) {
                    this.intersectionObserver.unobserve(targetElement);
                }
                targetElement.removeAttribute('data-vbp-scroll-id');
                targetElement.removeAttribute('data-vbp-animated');
                targetElement.removeAttribute('data-vbp-parallax');
                targetElement.style.transition = '';
                targetElement.style.transform = '';
            }

            this.activeAnimations.delete(scrollAnimationId);
        },

        /**
         * Aplica un preset a un elemento
         * @param {string} elementId - ID del elemento VBP
         * @param {string} presetId - ID del preset
         * @returns {string|null} ID de la animacion creada
         */
        applyPreset: function(elementId, presetId) {
            var presetConfig = this.presets[presetId];
            if (!presetConfig) {
                console.warn('[VBP Scroll Animations] Preset not found:', presetId);
                return null;
            }

            return this.createScrollAnimation(elementId, presetConfig);
        },

        /**
         * Guarda la configuracion de scroll animation en el elemento del store
         */
        saveToElement: function(elementId, scrollAnimConfig) {
            var vbpStore = window.Alpine && Alpine.store && Alpine.store('vbp');
            if (!vbpStore) return;

            var targetElement = vbpStore.getElementDeep ? vbpStore.getElementDeep(elementId) : vbpStore.getElement(elementId);
            if (!targetElement) return;

            if (!targetElement.data) {
                targetElement.data = {};
            }

            if (!targetElement.data.scrollAnimations) {
                targetElement.data.scrollAnimations = [];
            }

            // Verificar si ya existe una animacion con este ID
            var existingIndex = -1;
            for (var scrollAnimIndex = 0; scrollAnimIndex < targetElement.data.scrollAnimations.length; scrollAnimIndex++) {
                if (targetElement.data.scrollAnimations[scrollAnimIndex].id === scrollAnimConfig.id) {
                    existingIndex = scrollAnimIndex;
                    break;
                }
            }

            if (existingIndex !== -1) {
                targetElement.data.scrollAnimations[existingIndex] = scrollAnimConfig;
            } else {
                targetElement.data.scrollAnimations.push(scrollAnimConfig);
            }

            // Marcar como modificado
            if (typeof vbpStore.markAsDirty === 'function') {
                vbpStore.markAsDirty();
            }
        },

        /**
         * Carga animaciones de scroll desde el store para un elemento
         */
        loadFromElement: function(elementId) {
            var vbpStore = window.Alpine && Alpine.store && Alpine.store('vbp');
            if (!vbpStore) return;

            var targetElement = vbpStore.getElementDeep ? vbpStore.getElementDeep(elementId) : vbpStore.getElement(elementId);
            if (!targetElement || !targetElement.data || !targetElement.data.scrollAnimations) return;

            var scrollAnimManager = this;
            var scrollAnimsList = targetElement.data.scrollAnimations;

            for (var scrollAnimIndex = 0; scrollAnimIndex < scrollAnimsList.length; scrollAnimIndex++) {
                var savedScrollAnim = scrollAnimsList[scrollAnimIndex];
                if (savedScrollAnim.enabled !== false) {
                    scrollAnimManager.activeAnimations.set(savedScrollAnim.id, savedScrollAnim);

                    var domElement = document.querySelector('[data-vbp-id="' + elementId + '"]');
                    if (domElement) {
                        domElement.setAttribute('data-vbp-scroll-id', savedScrollAnim.id);

                        if (savedScrollAnim.trigger === 'scroll-into-view' || savedScrollAnim.trigger === 'scroll-progress') {
                            if (scrollAnimManager.intersectionObserver) {
                                scrollAnimManager.intersectionObserver.observe(domElement);
                            }
                        } else if (savedScrollAnim.trigger === 'parallax') {
                            scrollAnimManager.setupParallax(domElement, savedScrollAnim);
                        }
                    }
                }
            }
        },

        /**
         * Refresca todas las animaciones
         */
        refreshAllAnimations: function() {
            var scrollAnimManager = this;

            // Limpiar animaciones existentes
            this.activeAnimations.clear();

            // Recargar desde el store
            var vbpStore = window.Alpine && Alpine.store && Alpine.store('vbp');
            if (!vbpStore || !vbpStore.elements) return;

            function processVbpElement(vbpElement) {
                if (vbpElement.data && vbpElement.data.scrollAnimations) {
                    scrollAnimManager.loadFromElement(vbpElement.id);
                }

                if (vbpElement.children && vbpElement.children.length > 0) {
                    for (var childIndex = 0; childIndex < vbpElement.children.length; childIndex++) {
                        processVbpElement(vbpElement.children[childIndex]);
                    }
                }
            }

            for (var elementIndex = 0; elementIndex < vbpStore.elements.length; elementIndex++) {
                processVbpElement(vbpStore.elements[elementIndex]);
            }
        },

        /**
         * Abre el panel de configuracion
         */
        openConfigPanel: function() {
            document.dispatchEvent(new CustomEvent('vbp:open-scroll-animations-panel'));
        },

        /**
         * Dispara un evento de animacion
         */
        dispatchAnimationEvent: function(eventName, eventDetail) {
            document.dispatchEvent(new CustomEvent(eventName, {
                detail: eventDetail || {}
            }));
        },

        /**
         * Obtiene la configuracion de una animacion
         * @param {string} scrollAnimationId - ID de la animacion
         * @returns {object|null} Configuracion de la animacion
         */
        getAnimation: function(scrollAnimationId) {
            return this.activeAnimations.get(scrollAnimationId) || null;
        },

        /**
         * Actualiza la configuracion de una animacion
         * @param {string} scrollAnimationId - ID de la animacion
         * @param {object} configChanges - Cambios a aplicar
         */
        updateAnimation: function(scrollAnimationId, configChanges) {
            var existingConfig = this.activeAnimations.get(scrollAnimationId);
            if (!existingConfig) return;

            var updatedConfig = Object.assign({}, existingConfig, configChanges);
            this.activeAnimations.set(scrollAnimationId, updatedConfig);

            // Actualizar en el store
            if (existingConfig.elementId) {
                this.saveToElement(existingConfig.elementId, updatedConfig);
            }
        },

        /**
         * Habilita/deshabilita una animacion
         * @param {string} scrollAnimationId - ID de la animacion
         * @param {boolean} isEnabled - Estado de habilitacion
         */
        toggleAnimation: function(scrollAnimationId, isEnabled) {
            this.updateAnimation(scrollAnimationId, { enabled: isEnabled });
        },

        /**
         * Obtiene estadisticas de animaciones
         * @returns {object} Estadisticas
         */
        getStats: function() {
            var triggerStats = {};

            this.activeAnimations.forEach(function(animConfig) {
                var triggerType = animConfig.trigger || 'unknown';
                triggerStats[triggerType] = (triggerStats[triggerType] || 0) + 1;
            });

            return {
                total: this.activeAnimations.size,
                byTrigger: triggerStats
            };
        },

        /**
         * Destruye el sistema de scroll animations
         */
        destroy: function() {
            // Desconectar observer
            if (this.intersectionObserver) {
                this.intersectionObserver.disconnect();
                this.intersectionObserver = null;
            }

            // Cancelar RAF
            if (this.scrollAnimationFrameId) {
                cancelAnimationFrame(this.scrollAnimationFrameId);
                this.scrollAnimationFrameId = null;
            }

            // Limpiar animaciones
            this.activeAnimations.clear();
            this.elementPositionCache.clear();
            this.scrollListenerActive = false;
        }
    };

    // Inicializar cuando Alpine este listo
    document.addEventListener('alpine:init', function() {
        // Registrar en el store de VBP si existe
        if (window.Alpine && Alpine.store) {
            var vbpStore = Alpine.store('vbp');
            if (vbpStore) {
                // Extender el store con sistema de animaciones
                if (!vbpStore.animations) {
                    vbpStore.animations = {};
                }

                vbpStore.animations.scroll = {
                    triggers: SCROLL_TRIGGERS,
                    presets: SCROLL_ANIMATION_PRESETS,

                    createScrollAnimation: function(elementId, scrollConfig) {
                        return VBPScrollAnimations.createScrollAnimation(elementId, scrollConfig);
                    },

                    removeScrollAnimation: function(scrollAnimationId) {
                        return VBPScrollAnimations.removeScrollAnimation(scrollAnimationId);
                    },

                    applyPreset: function(elementId, presetId) {
                        return VBPScrollAnimations.applyPreset(elementId, presetId);
                    },

                    getAnimation: function(scrollAnimationId) {
                        return VBPScrollAnimations.getAnimation(scrollAnimationId);
                    },

                    updateAnimation: function(scrollAnimationId, configChanges) {
                        return VBPScrollAnimations.updateAnimation(scrollAnimationId, configChanges);
                    },

                    getStats: function() {
                        return VBPScrollAnimations.getStats();
                    }
                };
            }
        }

        VBPScrollAnimations.init();
    });

    // Inicializar si Alpine ya cargo
    if (document.readyState !== 'loading') {
        setTimeout(function() {
            if (!VBPScrollAnimations.intersectionObserver) {
                VBPScrollAnimations.init();
            }
        }, 100);
    }

    // Exponer globalmente
    window.VBPScrollAnimations = VBPScrollAnimations;

})();
