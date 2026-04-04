/**
 * Visual Builder Pro - Canvas Resize
 * Sistema de redimensionado para elementos del canvas
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.vbpResize = {
    initialized: false,
    isResizing: false,
    currentElement: null,
    currentHandle: null,
    startX: 0,
    startY: 0,
    startWidth: 0,
    startHeight: 0,
    startLeft: 0,
    startTop: 0,
    aspectRatio: null,

    init: function() {
        if (this.initialized) {
            return;
        }

        var self = this;

        document.addEventListener('mousedown', function(e) {
            var handle = e.target.closest('.vbp-handle');
            if (handle) {
                e.preventDefault();
                e.stopPropagation();
                self.startResize(handle, e);
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (self.isResizing) {
                e.preventDefault();
                self.doResize(e);
            }
        });

        document.addEventListener('mouseup', function() {
            if (self.isResizing) {
                self.endResize();
            }
        });

        this.initialized = true;
    },

    startResize: function(handle, event) {
        var elementWrapper = handle.closest('.vbp-element');
        if (!elementWrapper) return;

        this.isResizing = true;
        this.currentElement = elementWrapper;
        this.currentHandle = this.getHandleType(handle);
        this.startX = event.clientX;
        this.startY = event.clientY;

        var rect = elementWrapper.getBoundingClientRect();

        this.startWidth = rect.width;
        this.startHeight = rect.height;
        this.startLeft = rect.left;
        this.startTop = rect.top;
        this.aspectRatio = event.shiftKey ? (this.startWidth / this.startHeight) : null;

        document.body.classList.add('vbp-resizing');
        elementWrapper.classList.add('vbp-element-resizing');

        this.showSizeIndicator(this.startWidth, this.startHeight);
    },

    getHandleType: function(handle) {
        var classes = handle.className;
        if (classes.includes('handle-nw')) return 'nw';
        if (classes.includes('handle-n')) return 'n';
        if (classes.includes('handle-ne')) return 'ne';
        if (classes.includes('handle-w')) return 'w';
        if (classes.includes('handle-e')) return 'e';
        if (classes.includes('handle-sw')) return 'sw';
        if (classes.includes('handle-s')) return 's';
        if (classes.includes('handle-se')) return 'se';
        return 'se';
    },

    doResize: function(event) {
        if (!this.currentElement) return;

        var deltaX = event.clientX - this.startX;
        var deltaY = event.clientY - this.startY;

        if (window.vbpKeyboard && window.vbpKeyboard.snapToGridEnabled) {
            var gridSize = window.vbpKeyboard.gridSize || 8;
            deltaX = Math.round(deltaX / gridSize) * gridSize;
            deltaY = Math.round(deltaY / gridSize) * gridSize;
        }

        var newWidth = this.startWidth;
        var newHeight = this.startHeight;

        switch (this.currentHandle) {
            case 'e':
                newWidth = this.startWidth + deltaX;
                break;
            case 'w':
                newWidth = this.startWidth - deltaX;
                break;
            case 's':
                newHeight = this.startHeight + deltaY;
                break;
            case 'n':
                newHeight = this.startHeight - deltaY;
                break;
            case 'se':
                newWidth = this.startWidth + deltaX;
                newHeight = this.startHeight + deltaY;
                break;
            case 'sw':
                newWidth = this.startWidth - deltaX;
                newHeight = this.startHeight + deltaY;
                break;
            case 'ne':
                newWidth = this.startWidth + deltaX;
                newHeight = this.startHeight - deltaY;
                break;
            case 'nw':
                newWidth = this.startWidth - deltaX;
                newHeight = this.startHeight - deltaY;
                break;
        }

        if (event.shiftKey && this.aspectRatio) {
            if (this.currentHandle.includes('e') || this.currentHandle.includes('w')) {
                newHeight = newWidth / this.aspectRatio;
            } else {
                newWidth = newHeight * this.aspectRatio;
            }
        }

        newWidth = Math.max(50, newWidth);
        newHeight = Math.max(30, newHeight);

        var content = this.currentElement.querySelector('.vbp-element-content');
        if (content) {
            content.style.width = newWidth + 'px';
            content.style.minHeight = newHeight + 'px';
        }

        this.showSizeIndicator(Math.round(newWidth), Math.round(newHeight));
    },

    endResize: function() {
        if (!this.currentElement) return;

        var elementId = this.currentElement.dataset.elementId;
        var content = this.currentElement.querySelector('.vbp-element-content');

        if (elementId && content) {
            var newWidth = content.style.width;
            var newHeight = content.style.minHeight;
            var store = Alpine.store('vbp');

            if (store) {
                var element = store.getElement(elementId);
                if (element) {
                    store.saveToHistory();

                    var currentStyles = element.styles ? JSON.parse(JSON.stringify(element.styles)) : store.getDefaultStyles();
                    if (!currentStyles.dimensions) {
                        currentStyles.dimensions = { width: '', height: '', minHeight: '', maxWidth: '' };
                    }

                    currentStyles.dimensions.width = newWidth;
                    currentStyles.dimensions.height = newHeight;

                    store.updateElement(elementId, { styles: currentStyles });
                    store.isDirty = true;
                }
            }
        }

        document.body.classList.remove('vbp-resizing');
        this.currentElement.classList.remove('vbp-element-resizing');
        this.hideSizeIndicator();

        this.isResizing = false;
        this.currentElement = null;
        this.currentHandle = null;
    },

    showSizeIndicator: function(width, height) {
        var indicator = document.getElementById('vbp-size-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'vbp-size-indicator';
            indicator.style.cssText = 'position: fixed; bottom: 60px; right: 20px; padding: 8px 14px; background: rgba(30, 30, 46, 0.95); color: #cdd6f4; border-radius: 6px; font-size: 13px; font-family: monospace; z-index: 10001; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            document.body.appendChild(indicator);
        }
        indicator.innerHTML = '<span style="color: #89b4fa;">' + width + '</span> × <span style="color: #a6e3a1;">' + height + '</span> px';
    },

    hideSizeIndicator: function() {
        var indicator = document.getElementById('vbp-size-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.vbpResize.init();
});

document.addEventListener('alpine:initialized', function() {
    if (!window.vbpResize.isResizing) {
        window.vbpResize.init();
    }
});
