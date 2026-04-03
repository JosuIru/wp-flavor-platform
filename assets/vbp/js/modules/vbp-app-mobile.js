/**
 * Visual Builder Pro - App Module: Mobile Editor
 * Optimizaciones para edición en dispositivos móviles
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

window.VBPAppMobile = {
    // Estado del modo móvil
    isMobileDevice: false,
    isTouchDevice: false,
    mobileMode: false,
    mobileMenuOpen: false,
    mobileQuickActions: false,
    mobilePanelActive: null, // 'blocks', 'inspector', 'layers'
    touchStartX: 0,
    touchStartY: 0,
    touchStartTime: 0,
    pinchStartDistance: 0,
    lastTapTime: 0,

    // Configuración móvil
    mobileConfig: {
        enableGestures: true,
        enableHaptic: true,
        simplifiedUI: true,
        largerTouchTargets: true,
        autoHidePanels: true,
        swipeThreshold: 50,
        doubleTapDelay: 300,
        longPressDelay: 500
    },

    // ============ DETECCIÓN ============

    /**
     * Detectar dispositivo móvil
     */
    detectMobileDevice: function() {
        // Detectar por user agent
        var mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
        this.isMobileDevice = mobileRegex.test(navigator.userAgent);

        // Detectar soporte táctil
        this.isTouchDevice = ('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0);

        // Detectar por ancho de pantalla
        var isSmallScreen = window.innerWidth <= 1024;

        // Activar modo móvil si es dispositivo móvil o pantalla pequeña con touch
        this.mobileMode = this.isMobileDevice || (isSmallScreen && this.isTouchDevice);

        return this.mobileMode;
    },

    /**
     * Inicializar modo móvil
     */
    initMobile: function() {
        var self = this;

        // Detectar dispositivo
        this.detectMobileDevice();

        if (!this.mobileMode) {
            return;
        }

        vbpLog.log(' Modo móvil activado');

        // Añadir clase al body
        document.body.classList.add('vbp-mobile-mode');

        if (this.isTouchDevice) {
            document.body.classList.add('vbp-touch-device');
        }

        // Configurar gestos si está habilitado
        if (this.mobileConfig.enableGestures) {
            this.initTouchGestures();
        }

        // Configurar UI simplificada
        if (this.mobileConfig.simplifiedUI) {
            this.initSimplifiedUI();
        }

        // Escuchar cambios de orientación
        window.addEventListener('orientationchange', function() {
            self.handleOrientationChange();
        });

        // Escuchar resize
        window.addEventListener('resize', this.debounce(function() {
            self.detectMobileDevice();
            self.updateMobileLayout();
        }, 250));

        // Prevenir zoom en inputs
        this.preventInputZoom();

        // Configurar viewport
        this.configureViewport();
    },

    // ============ GESTOS TÁCTILES ============

    /**
     * Inicializar gestos táctiles
     */
    initTouchGestures: function() {
        var self = this;
        var canvas = document.querySelector('.vbp-canvas-container');

        if (!canvas) return;

        // Touch start
        canvas.addEventListener('touchstart', function(e) {
            self.handleTouchStart(e);
        }, { passive: false });

        // Touch move
        canvas.addEventListener('touchmove', function(e) {
            self.handleTouchMove(e);
        }, { passive: false });

        // Touch end
        canvas.addEventListener('touchend', function(e) {
            self.handleTouchEnd(e);
        }, { passive: false });

        // Prevenir gestos del navegador en el canvas
        canvas.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
    },

    /**
     * Manejar inicio de touch
     */
    handleTouchStart: function(e) {
        if (e.touches.length === 1) {
            // Single touch
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
            this.touchStartTime = Date.now();
        } else if (e.touches.length === 2) {
            // Pinch zoom
            this.pinchStartDistance = this.getPinchDistance(e.touches);
        }
    },

    /**
     * Manejar movimiento de touch
     */
    handleTouchMove: function(e) {
        if (e.touches.length === 2 && this.pinchStartDistance > 0) {
            // Pinch zoom
            e.preventDefault();
            var currentDistance = this.getPinchDistance(e.touches);
            var scale = currentDistance / this.pinchStartDistance;

            // Aplicar zoom
            var currentZoom = this.zoom || 100;
            var newZoom = Math.min(200, Math.max(25, currentZoom * scale));

            if (typeof this.setZoom === 'function') {
                this.setZoom(Math.round(newZoom));
            } else {
                this.zoom = Math.round(newZoom);
            }

            this.pinchStartDistance = currentDistance;
        }
    },

    /**
     * Manejar fin de touch
     */
    handleTouchEnd: function(e) {
        var touchEndX = e.changedTouches[0].clientX;
        var touchEndY = e.changedTouches[0].clientY;
        var touchDuration = Date.now() - this.touchStartTime;

        var deltaX = touchEndX - this.touchStartX;
        var deltaY = touchEndY - this.touchStartY;
        var distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

        // Detectar swipe
        if (distance > this.mobileConfig.swipeThreshold && touchDuration < 300) {
            this.handleSwipe(deltaX, deltaY);
        }

        // Detectar double tap
        var now = Date.now();
        if (now - this.lastTapTime < this.mobileConfig.doubleTapDelay && distance < 20) {
            this.handleDoubleTap(touchEndX, touchEndY);
        }
        this.lastTapTime = now;

        // Reset pinch
        this.pinchStartDistance = 0;
    },

    /**
     * Calcular distancia entre dos dedos
     */
    getPinchDistance: function(touches) {
        var dx = touches[0].clientX - touches[1].clientX;
        var dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    },

    /**
     * Manejar swipe
     */
    handleSwipe: function(deltaX, deltaY) {
        var absX = Math.abs(deltaX);
        var absY = Math.abs(deltaY);

        if (absX > absY) {
            // Swipe horizontal
            if (deltaX > 0) {
                // Swipe derecha - mostrar bloques
                this.showMobilePanel('blocks');
            } else {
                // Swipe izquierda - mostrar inspector
                this.showMobilePanel('inspector');
            }
        } else {
            // Swipe vertical
            if (deltaY > 0) {
                // Swipe abajo - mostrar acciones rápidas
                this.toggleMobileQuickActions();
            } else {
                // Swipe arriba - ocultar paneles
                this.hideMobilePanels();
            }
        }

        // Feedback háptico
        this.hapticFeedback('light');
    },

    /**
     * Manejar doble tap
     */
    handleDoubleTap: function(x, y) {
        // Resetear zoom o hacer zoom in
        if (this.zoom === 100) {
            this.zoom = 150;
        } else {
            this.zoom = 100;
        }

        this.hapticFeedback('medium');
    },

    // ============ UI MÓVIL ============

    /**
     * Inicializar UI simplificada
     */
    initSimplifiedUI: function() {
        // Ocultar elementos secundarios
        var elementsToHide = [
            '.vbp-minimap',
            '.vbp-ruler',
            '.vbp-zoom-controls',
            '.vbp-split-screen-btn'
        ];

        elementsToHide.forEach(function(selector) {
            var el = document.querySelector(selector);
            if (el) {
                el.style.display = 'none';
            }
        });

        // Hacer targets más grandes
        if (this.mobileConfig.largerTouchTargets) {
            document.documentElement.style.setProperty('--vbp-touch-target-size', '48px');
            document.documentElement.style.setProperty('--vbp-btn-min-height', '44px');
        }
    },

    /**
     * Mostrar panel móvil
     */
    showMobilePanel: function(panel) {
        this.mobilePanelActive = panel;
        document.body.classList.add('vbp-mobile-panel-open');

        var panelEl = null;
        switch (panel) {
            case 'blocks':
                panelEl = document.querySelector('.vbp-sidebar-left');
                break;
            case 'inspector':
                panelEl = document.querySelector('.vbp-sidebar-right');
                break;
            case 'layers':
                panelEl = document.querySelector('.vbp-layers-panel');
                break;
        }

        if (panelEl) {
            panelEl.classList.add('mobile-open');
        }

        this.hapticFeedback('light');
    },

    /**
     * Ocultar paneles móviles
     */
    hideMobilePanels: function() {
        this.mobilePanelActive = null;
        document.body.classList.remove('vbp-mobile-panel-open');

        var panels = document.querySelectorAll('.vbp-sidebar-left, .vbp-sidebar-right, .vbp-layers-panel');
        panels.forEach(function(panel) {
            panel.classList.remove('mobile-open');
        });
    },

    /**
     * Toggle acciones rápidas
     */
    toggleMobileQuickActions: function() {
        this.mobileQuickActions = !this.mobileQuickActions;
        this.hapticFeedback('light');
    },

    /**
     * Toggle menú móvil
     */
    toggleMobileMenu: function() {
        this.mobileMenuOpen = !this.mobileMenuOpen;
        this.hapticFeedback('light');
    },

    // ============ ACCIONES RÁPIDAS ============

    /**
     * Obtener acciones rápidas para móvil
     */
    getMobileQuickActions: function() {
        var store = Alpine.store('vbp');
        var hasSelection = store && store.selection.elementIds.length > 0;

        var actions = [
            {
                id: 'undo',
                icon: '↩️',
                label: 'Deshacer',
                action: function() { store.undo(); },
                disabled: !store || !store.canUndo
            },
            {
                id: 'redo',
                icon: '↪️',
                label: 'Rehacer',
                action: function() { store.redo(); },
                disabled: !store || !store.canRedo
            },
            {
                id: 'save',
                icon: '💾',
                label: 'Guardar',
                action: this.saveDocument.bind(this),
                primary: true
            }
        ];

        if (hasSelection) {
            actions.push(
                {
                    id: 'duplicate',
                    icon: '📋',
                    label: 'Duplicar',
                    action: this.duplicateSelected.bind(this)
                },
                {
                    id: 'delete',
                    icon: '🗑️',
                    label: 'Eliminar',
                    action: this.deleteSelected.bind(this),
                    danger: true
                }
            );
        }

        return actions;
    },

    /**
     * Ejecutar acción rápida
     */
    executeMobileAction: function(action) {
        if (action.disabled) return;

        if (typeof action.action === 'function') {
            action.action();
        }

        this.hapticFeedback('medium');
        this.mobileQuickActions = false;
    },

    // ============ HELPERS ============

    /**
     * Feedback háptico
     */
    hapticFeedback: function(intensity) {
        if (!this.mobileConfig.enableHaptic) return;

        if ('vibrate' in navigator) {
            switch (intensity) {
                case 'light':
                    navigator.vibrate(10);
                    break;
                case 'medium':
                    navigator.vibrate(25);
                    break;
                case 'heavy':
                    navigator.vibrate(50);
                    break;
            }
        }
    },

    /**
     * Manejar cambio de orientación
     */
    handleOrientationChange: function() {
        var self = this;
        // Esperar a que se complete la rotación
        setTimeout(function() {
            self.updateMobileLayout();
        }, 100);
    },

    /**
     * Actualizar layout móvil
     */
    updateMobileLayout: function() {
        var isLandscape = window.innerWidth > window.innerHeight;
        document.body.classList.toggle('vbp-landscape', isLandscape);
        document.body.classList.toggle('vbp-portrait', !isLandscape);
    },

    /**
     * Prevenir zoom en inputs
     */
    preventInputZoom: function() {
        // Añadir font-size mínimo a inputs para prevenir zoom automático en iOS
        var style = document.createElement('style');
        style.textContent = '.vbp-mobile-mode input, .vbp-mobile-mode textarea, .vbp-mobile-mode select { font-size: 16px !important; }';
        document.head.appendChild(style);
    },

    /**
     * Configurar viewport
     */
    configureViewport: function() {
        var viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');
        }
    },

    /**
     * Debounce helper
     */
    debounce: function(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    },

    /**
     * Verificar si estamos en modo móvil
     */
    isMobile: function() {
        return this.mobileMode;
    },

    /**
     * Obtener orientación actual
     */
    getOrientation: function() {
        return window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
    }
};
