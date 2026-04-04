/**
 * Visual Builder Pro - Canvas Utils
 * Utilidades globales de snap, smart guides y Alt+Drag
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.vbpCanvasUtils = {
    startBlockDrag: function(event, blockType) {
        event.dataTransfer.setData('text/vbp-block-type', blockType);
        event.dataTransfer.effectAllowed = 'copy';
        document.body.classList.add('vbp-dragging', 'vbp-dragging-new');
    },

    endBlockDrag: function() {
        document.body.classList.remove('vbp-dragging', 'vbp-dragging-new');
    },

    getRulersComponent: function() {
        var rulersEl = document.querySelector('[x-data*="vbpRulers"]');
        if (rulersEl && rulersEl.__x) {
            return rulersEl.__x.$data;
        }
        return null;
    },

    snapToGuides: function(position, type) {
        var rulers = this.getRulersComponent();
        if (!rulers) {
            return { snapped: false, position: position };
        }

        var snappedPosition = rulers.snapToGuides(position, type);
        return {
            snapped: snappedPosition !== position,
            position: snappedPosition
        };
    },

    showSnapLine: function(position, type) {
        var existingLine = document.getElementById('vbp-snap-line');
        if (existingLine) existingLine.remove();

        var line = document.createElement('div');
        line.id = 'vbp-snap-line';
        line.className = 'vbp-snap-line vbp-snap-line--' + type;

        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        var store = Alpine.store('vbp');
        var zoom = store.zoom / 100;

        if (type === 'horizontal') {
            line.style.cssText = 'position: absolute; left: 0; right: 0; height: 2px; background: #10b981; top: ' + (position * zoom) + 'px; pointer-events: none; z-index: 1001; box-shadow: 0 0 4px rgba(16,185,129,0.5);';
        } else {
            line.style.cssText = 'position: absolute; top: 0; bottom: 0; width: 2px; background: #10b981; left: ' + (position * zoom) + 'px; pointer-events: none; z-index: 1001; box-shadow: 0 0 4px rgba(16,185,129,0.5);';
        }

        var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
        if (canvasWrapper) {
            canvasWrapper.appendChild(line);
        }
    },

    hideSnapLines: function() {
        var lines = document.querySelectorAll('.vbp-snap-line');
        lines.forEach(function(line) { line.remove(); });
    },

    smartGuides: {
        tolerance: 5,
        guides: [],
        currentDragId: null,

        startDrag: function(elementId) {
            this.currentDragId = elementId;
            this.calculateGuides(elementId);
        },

        calculateGuides: function(excludeId) {
            var self = this;
            this.guides = [];

            var elements = document.querySelectorAll('.vbp-canvas .vbp-element');
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            var canvasWidth = canvas.offsetWidth;

            this.guides.push(
                { type: 'vertical', position: 0, source: 'canvas-left' },
                { type: 'vertical', position: canvasWidth / 2, source: 'canvas-center' },
                { type: 'vertical', position: canvasWidth, source: 'canvas-right' },
                { type: 'horizontal', position: 0, source: 'canvas-top' }
            );

            elements.forEach(function(el) {
                var elId = el.dataset.elementId;
                if (elId === excludeId) return;

                var rect = el.getBoundingClientRect();
                var relativeLeft = (rect.left - canvasRect.left) / zoom;
                var relativeTop = (rect.top - canvasRect.top) / zoom;
                var relativeRight = relativeLeft + rect.width / zoom;
                var relativeBottom = relativeTop + rect.height / zoom;
                var centerX = relativeLeft + (rect.width / zoom) / 2;
                var centerY = relativeTop + (rect.height / zoom) / 2;

                self.guides.push(
                    { type: 'vertical', position: relativeLeft, source: elId + '-left' },
                    { type: 'vertical', position: centerX, source: elId + '-center' },
                    { type: 'vertical', position: relativeRight, source: elId + '-right' }
                );

                self.guides.push(
                    { type: 'horizontal', position: relativeTop, source: elId + '-top' },
                    { type: 'horizontal', position: centerY, source: elId + '-middle' },
                    { type: 'horizontal', position: relativeBottom, source: elId + '-bottom' }
                );
            });
        },

        checkSnap: function(dragRect) {
            var self = this;
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return { snapX: null, snapY: null };

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            var left = (dragRect.left - canvasRect.left) / zoom;
            var top = (dragRect.top - canvasRect.top) / zoom;
            var right = left + dragRect.width / zoom;
            var bottom = top + dragRect.height / zoom;
            var centerX = left + (dragRect.width / zoom) / 2;
            var centerY = top + (dragRect.height / zoom) / 2;

            var snapX = null;
            var snapY = null;
            var matchedGuidesV = [];
            var matchedGuidesH = [];

            this.guides.filter(function(g) { return g.type === 'vertical'; }).forEach(function(guide) {
                if (Math.abs(left - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - left, position: guide.position };
                    matchedGuidesV.push(guide);
                } else if (Math.abs(centerX - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - centerX, position: guide.position };
                    matchedGuidesV.push(guide);
                } else if (Math.abs(right - guide.position) <= self.tolerance) {
                    snapX = { offset: guide.position - right, position: guide.position };
                    matchedGuidesV.push(guide);
                }
            });

            this.guides.filter(function(g) { return g.type === 'horizontal'; }).forEach(function(guide) {
                if (Math.abs(top - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - top, position: guide.position };
                    matchedGuidesH.push(guide);
                } else if (Math.abs(centerY - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - centerY, position: guide.position };
                    matchedGuidesH.push(guide);
                } else if (Math.abs(bottom - guide.position) <= self.tolerance) {
                    snapY = { offset: guide.position - bottom, position: guide.position };
                    matchedGuidesH.push(guide);
                }
            });

            this.showSmartGuides(matchedGuidesV, matchedGuidesH);

            return { snapX: snapX, snapY: snapY };
        },

        showSmartGuides: function(verticalGuides, horizontalGuides) {
            document.querySelectorAll('.vbp-smart-guide').forEach(function(g) { g.remove(); });

            var canvas = document.querySelector('.vbp-canvas-wrapper');
            if (!canvas) return;

            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            verticalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--vertical';
                line.style.cssText = 'position: absolute; top: 0; bottom: 0; width: 1px; background: #ec4899; left: ' + (guide.position * zoom) + 'px; pointer-events: none; z-index: 1002;';
                if (guide.source.includes('center')) {
                    line.style.background = '#8b5cf6';
                }
                canvas.appendChild(line);
            });

            horizontalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--horizontal';
                line.style.cssText = 'position: absolute; left: 0; right: 0; height: 1px; background: #ec4899; top: ' + (guide.position * zoom) + 'px; pointer-events: none; z-index: 1002;';
                if (guide.source.includes('middle') || guide.source.includes('center')) {
                    line.style.background = '#8b5cf6';
                }
                canvas.appendChild(line);
            });
        },

        endDrag: function() {
            this.currentDragId = null;
            this.guides = [];
            document.querySelectorAll('.vbp-smart-guide').forEach(function(g) { g.remove(); });
        }
    },

    altDragDuplicate: {
        isAltDrag: false,
        originalId: null,
        duplicatedId: null,

        start: function(elementId, event) {
            if (event.altKey) {
                this.isAltDrag = true;
                this.originalId = elementId;

                var store = Alpine.store('vbp');
                var newId = store.duplicateElement(elementId);
                this.duplicatedId = newId;
                store.setSelection([newId]);
                this.showDuplicateIndicator();

                return newId;
            }
            return null;
        },

        showDuplicateIndicator: function() {
            var indicator = document.createElement('div');
            indicator.id = 'vbp-duplicate-indicator';
            indicator.innerHTML = '⧉ Duplicando...';
            indicator.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); padding: 8px 16px; background: #8b5cf6; color: white; border-radius: 6px; font-size: 13px; font-weight: 500; z-index: 10000; box-shadow: 0 4px 12px rgba(139,92,246,0.3);';
            document.body.appendChild(indicator);

            setTimeout(function() {
                var el = document.getElementById('vbp-duplicate-indicator');
                if (el) el.remove();
            }, 1500);
        },

        end: function() {
            this.isAltDrag = false;
            this.originalId = null;
            this.duplicatedId = null;
        }
    }
};
