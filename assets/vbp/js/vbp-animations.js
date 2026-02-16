/**
 * Visual Builder Pro - Animations
 *
 * Sistema de animaciones para el frontend del VBP.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * VBP Animations Controller
     */
    var VBPAnimations = {
        /**
         * Animation configurations
         */
        config: {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px',
            parallaxSpeed: 0.3
        },

        /**
         * Available entrance animations
         */
        entranceAnimations: [
            'fade-in',
            'fade-in-up',
            'fade-in-down',
            'fade-in-left',
            'fade-in-right',
            'zoom-in',
            'zoom-out',
            'bounce-in',
            'bounce-in-up',
            'rotate-in',
            'flip-in-x',
            'flip-in-y'
        ],

        /**
         * Available hover animations
         */
        hoverAnimations: [
            'grow',
            'shrink',
            'float',
            'pulse',
            'wobble',
            'swing',
            'glow'
        ],

        /**
         * Available loop animations
         */
        loopAnimations: [
            'spin',
            'ping',
            'bounce',
            'shake',
            'heartbeat',
            'blink'
        ],

        /**
         * Easing functions
         */
        easings: {
            'ease': 'ease',
            'ease-in': 'ease-in',
            'ease-out': 'ease-out',
            'ease-in-out': 'ease-in-out',
            'linear': 'linear',
            'bounce': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
            'elastic': 'cubic-bezier(0.68, -0.6, 0.32, 1.6)'
        },

        /**
         * Initialize all animations
         */
        init: function() {
            this.initScrollReveal();
            this.initParallax();
            this.initEntranceAnimations();
        },

        /**
         * Initialize scroll reveal animations
         */
        initScrollReveal: function() {
            var self = this;
            var revealElements = document.querySelectorAll('[data-vbp-reveal]');

            if (revealElements.length === 0) return;

            // Check if IntersectionObserver is supported
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var element = entry.target;
                            var delay = element.dataset.vbpRevealDelay || 0;

                            setTimeout(function() {
                                element.classList.add('vbp-revealed');
                            }, parseFloat(delay) * 1000);

                            // Unobserve after revealing
                            observer.unobserve(element);
                        }
                    });
                }, {
                    threshold: self.config.threshold,
                    rootMargin: self.config.rootMargin
                });

                revealElements.forEach(function(el) {
                    observer.observe(el);
                });
            } else {
                // Fallback for older browsers
                revealElements.forEach(function(el) {
                    el.classList.add('vbp-revealed');
                });
            }
        },

        /**
         * Initialize entrance animations (on load)
         */
        initEntranceAnimations: function() {
            var self = this;
            var animatedElements = document.querySelectorAll('[data-vbp-entrance]');

            animatedElements.forEach(function(element) {
                var animation = element.dataset.vbpEntrance;
                var trigger = element.dataset.vbpTrigger || 'scroll';
                var duration = element.dataset.vbpDuration || '0.6s';
                var delay = element.dataset.vbpDelay || '0s';
                var easing = element.dataset.vbpEasing || 'ease-out';

                // Set CSS custom properties
                element.style.setProperty('--vbp-anim-duration', duration);
                element.style.setProperty('--vbp-anim-delay', delay);
                element.style.setProperty('--vbp-anim-easing', self.easings[easing] || easing);

                if (trigger === 'load') {
                    // Trigger on page load
                    element.classList.add('vbp-anim-' + animation);
                } else if (trigger === 'scroll') {
                    // Trigger on scroll into view
                    self.observeForAnimation(element, animation);
                }
            });
        },

        /**
         * Observe element for scroll-triggered animation
         */
        observeForAnimation: function(element, animation) {
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('vbp-anim-' + animation);
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1
                });

                observer.observe(element);
            } else {
                // Fallback
                element.classList.add('vbp-anim-' + animation);
            }
        },

        /**
         * Initialize parallax effects
         */
        initParallax: function() {
            var self = this;
            var parallaxElements = document.querySelectorAll('[data-vbp-parallax]');

            if (parallaxElements.length === 0) return;

            var updateParallax = function() {
                var scrollTop = window.pageYOffset;

                parallaxElements.forEach(function(element) {
                    var speed = parseFloat(element.dataset.vbpParallax) || self.config.parallaxSpeed;
                    var rect = element.getBoundingClientRect();
                    var elementTop = rect.top + scrollTop;
                    var elementCenter = elementTop + (rect.height / 2);
                    var windowCenter = scrollTop + (window.innerHeight / 2);
                    var distance = windowCenter - elementCenter;
                    var offset = distance * speed;

                    // Only apply if element is in viewport
                    if (rect.bottom > 0 && rect.top < window.innerHeight) {
                        element.style.transform = 'translateY(' + offset + 'px)';
                    }
                });
            };

            // Throttle scroll events
            var ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        updateParallax();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });

            // Initial update
            updateParallax();
        },

        /**
         * Apply animation to element programmatically
         */
        animate: function(element, animationType, options) {
            options = options || {};

            var duration = options.duration || '0.6s';
            var delay = options.delay || '0s';
            var easing = options.easing || 'ease-out';

            element.style.setProperty('--vbp-anim-duration', duration);
            element.style.setProperty('--vbp-anim-delay', delay);
            element.style.setProperty('--vbp-anim-easing', this.easings[easing] || easing);

            // Remove any existing animation classes
            this.entranceAnimations.forEach(function(anim) {
                element.classList.remove('vbp-anim-' + anim);
            });

            // Force reflow
            void element.offsetWidth;

            // Add new animation
            element.classList.add('vbp-anim-' + animationType);

            // Return promise for animation end
            return new Promise(function(resolve) {
                element.addEventListener('animationend', function handler() {
                    element.removeEventListener('animationend', handler);
                    resolve();
                });
            });
        },

        /**
         * Apply hover animation
         */
        applyHoverAnimation: function(element, hoverType) {
            var self = this;

            // Remove existing hover animations
            this.hoverAnimations.forEach(function(anim) {
                element.classList.remove('vbp-hover-' + anim);
            });

            // Add new hover animation
            if (hoverType && hoverType !== 'none') {
                element.classList.add('vbp-hover-' + hoverType);
            }
        },

        /**
         * Apply loop animation
         */
        applyLoopAnimation: function(element, loopType, options) {
            var self = this;
            options = options || {};

            // Remove existing loop animations
            this.loopAnimations.forEach(function(anim) {
                element.classList.remove('vbp-loop-' + anim);
            });

            // Apply duration if provided
            if (options.duration) {
                element.style.setProperty('--vbp-anim-duration', options.duration);
            }

            // Add new loop animation
            if (loopType && loopType !== 'none') {
                element.classList.add('vbp-loop-' + loopType);
            }
        },

        /**
         * Remove all animations from element
         */
        removeAnimations: function(element) {
            var self = this;

            this.entranceAnimations.forEach(function(anim) {
                element.classList.remove('vbp-anim-' + anim);
            });

            this.hoverAnimations.forEach(function(anim) {
                element.classList.remove('vbp-hover-' + anim);
            });

            this.loopAnimations.forEach(function(anim) {
                element.classList.remove('vbp-loop-' + anim);
            });

            element.classList.remove('vbp-animated', 'vbp-animated--visible', 'vbp-revealed');
            element.removeAttribute('data-vbp-entrance');
            element.removeAttribute('data-vbp-reveal');
            element.removeAttribute('data-vbp-parallax');

            // Remove custom properties
            element.style.removeProperty('--vbp-anim-duration');
            element.style.removeProperty('--vbp-anim-delay');
            element.style.removeProperty('--vbp-anim-easing');
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPAnimations.init();
        });
    } else {
        VBPAnimations.init();
    }

    // Expose globally
    window.VBPAnimations = VBPAnimations;

})();
