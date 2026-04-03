/**
 * Visual Builder Pro - App Module: Split Screen
 * Funcionalidad de vista dividida para previsualización responsive
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppSplitScreen = {
    // Estado
    splitScreenMode: false,
    splitScreenSyncScroll: true,
    splitScreenDevices: ['desktop', 'mobile'],
    _splitScrollHandler: null,
    _splitSyncIndicator: null,
    _isSyncingScroll: false,

    /**
     * Activa/desactiva el modo split-screen
     */
    toggleSplitScreen: function() {
        this.splitScreenMode = !this.splitScreenMode;

        if (this.splitScreenMode) {
            this.initSplitScreen();
            this.showNotification('Modo split-screen activado', 'info');
        } else {
            this.destroySplitScreen();
            this.showNotification('Modo split-screen desactivado', 'info');
        }
    },

    /**
     * Inicializa el modo split-screen
     */
    initSplitScreen: function() {
        var self = this;
        var devices = this.splitScreenDevices;

        this.$nextTick(function() {
            var canvasArea = document.querySelector('.vbp-canvas-area');
            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            var canvas = document.querySelector('.vbp-canvas');

            if (!canvasArea || !canvasWrapper || !canvas) return;

            // Guardar referencia al canvas original
            self._originalCanvasParent = canvasWrapper;
            self._originalCanvas = canvas.cloneNode(true);

            // Añadir clase split-screen
            canvasArea.classList.add('vbp-split-screen-active');

            // Crear panel izquierdo (primer dispositivo)
            var panelLeft = document.createElement('div');
            panelLeft.className = 'vbp-split-panel';
            panelLeft.setAttribute('data-device', self.getDeviceLabel(devices[0]));
            panelLeft.style.cssText = 'flex: 1; overflow: auto; background: var(--vbp-bg-secondary, #f9fafb); border-radius: 12px; position: relative;';

            // Crear canvas para panel izquierdo
            var canvasLeft = document.createElement('div');
            canvasLeft.className = 'vbp-canvas vbp-canvas--' + devices[0];
            canvasLeft.style.cssText = 'width: ' + self.getDeviceWidth(devices[0]) + 'px; max-width: 100%; margin: 16px auto; background: white; box-shadow: 0 2px 12px rgba(0,0,0,0.1); border-radius: 8px;';
            canvasLeft.innerHTML = canvas.innerHTML;
            panelLeft.appendChild(canvasLeft);

            // Crear panel derecho (segundo dispositivo)
            var panelRight = document.createElement('div');
            panelRight.className = 'vbp-split-panel';
            panelRight.setAttribute('data-device', self.getDeviceLabel(devices[1]));
            panelRight.style.cssText = 'width: 420px; flex-shrink: 0; overflow: auto; background: var(--vbp-bg-secondary, #f9fafb); border-radius: 12px; position: relative;';

            // Crear canvas para panel derecho
            var canvasRight = document.createElement('div');
            canvasRight.className = 'vbp-canvas vbp-canvas--' + devices[1];
            canvasRight.style.cssText = 'width: ' + self.getDeviceWidth(devices[1]) + 'px; max-width: 100%; margin: 16px auto; background: white; box-shadow: 0 2px 12px rgba(0,0,0,0.1); border-radius: 8px;';
            canvasRight.innerHTML = canvas.innerHTML;
            panelRight.appendChild(canvasRight);

            // Limpiar y añadir paneles
            canvasWrapper.style.display = 'none';
            canvasArea.appendChild(panelLeft);
            canvasArea.appendChild(panelRight);

            // Inicializar sincronización de scroll
            if (self.splitScreenSyncScroll) {
                self.initSplitScreenScrollSync();
            }

            // Crear indicador de sincronización
            var syncIndicator = document.createElement('div');
            syncIndicator.className = 'vbp-split-sync-indicator' + (self.splitScreenSyncScroll ? ' active' : '');
            syncIndicator.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg> <span>Scroll sincronizado</span>';
            syncIndicator.style.cursor = 'pointer';
            syncIndicator.addEventListener('click', function() {
                self.toggleSplitScreenSyncScroll();
                syncIndicator.classList.toggle('active', self.splitScreenSyncScroll);
            });
            document.body.appendChild(syncIndicator);
            self._splitSyncIndicator = syncIndicator;
        });
    },

    /**
     * Destruye el modo split-screen
     */
    destroySplitScreen: function() {
        var canvasArea = document.querySelector('.vbp-canvas-area');
        var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');

        if (canvasArea) {
            canvasArea.classList.remove('vbp-split-screen-active');

            // Remover paneles split
            var paneles = canvasArea.querySelectorAll('.vbp-split-panel');
            paneles.forEach(function(panel) {
                panel.remove();
            });

            // Mostrar canvas wrapper original
            if (canvasWrapper) {
                canvasWrapper.style.display = '';
            }
        }

        // Remover indicador de sincronización
        if (this._splitSyncIndicator) {
            this._splitSyncIndicator.remove();
            this._splitSyncIndicator = null;
        }

        // Remover listeners de scroll
        this.removeSplitScreenScrollSync();
    },

    /**
     * Cambia los dispositivos mostrados en split-screen
     */
    setSplitScreenDevices: function(device1, device2) {
        this.splitScreenDevices = [device1, device2];
    },

    /**
     * Toggle sincronización de scroll en split-screen
     */
    toggleSplitScreenSyncScroll: function() {
        this.splitScreenSyncScroll = !this.splitScreenSyncScroll;

        if (this.splitScreenSyncScroll) {
            this.initSplitScreenScrollSync();
            this.showNotification('Sincronización de scroll activada', 'info');
        } else {
            this.removeSplitScreenScrollSync();
            this.showNotification('Sincronización de scroll desactivada', 'info');
        }
    },

    /**
     * Inicializa la sincronización de scroll entre paneles
     */
    initSplitScreenScrollSync: function() {
        var self = this;
        var paneles = document.querySelectorAll('.vbp-split-panel');

        if (paneles.length < 2) return;

        this._splitScrollHandler = function(event) {
            if (self._isSyncingScroll) return;
            self._isSyncingScroll = true;

            var sourcePanel = event.target;
            var scrollPercentage = sourcePanel.scrollTop / (sourcePanel.scrollHeight - sourcePanel.clientHeight);

            paneles.forEach(function(panel) {
                if (panel !== sourcePanel) {
                    var targetScrollTop = scrollPercentage * (panel.scrollHeight - panel.clientHeight);
                    panel.scrollTop = targetScrollTop;
                }
            });

            requestAnimationFrame(function() {
                self._isSyncingScroll = false;
            });
        };

        paneles.forEach(function(panel) {
            panel.addEventListener('scroll', self._splitScrollHandler);
        });
    },

    /**
     * Remueve la sincronización de scroll
     */
    removeSplitScreenScrollSync: function() {
        var self = this;
        var paneles = document.querySelectorAll('.vbp-split-panel');

        if (this._splitScrollHandler) {
            paneles.forEach(function(panel) {
                panel.removeEventListener('scroll', self._splitScrollHandler);
            });
            this._splitScrollHandler = null;
        }
    },

    /**
     * Obtiene el ancho para un dispositivo
     */
    getDeviceWidth: function(device) {
        var widths = {
            desktop: 1200,
            laptop: 1024,
            tablet: 768,
            mobile: 375
        };
        return widths[device] || 1200;
    },

    /**
     * Obtiene la etiqueta para un dispositivo
     */
    getDeviceLabel: function(device) {
        var labels = {
            desktop: 'Escritorio (1200px)',
            laptop: 'Portátil (1024px)',
            tablet: 'Tablet (768px)',
            mobile: 'Móvil (375px)'
        };
        return labels[device] || device;
    },

    /**
     * Obtiene el icono SVG para un dispositivo
     */
    getDeviceIcon: function(device) {
        var icons = {
            desktop: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
            laptop: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v8H4V6z"/><path d="M2 18h20"/></svg>',
            tablet: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>',
            mobile: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M12 18h.01"/></svg>'
        };
        return icons[device] || icons.desktop;
    }
};
