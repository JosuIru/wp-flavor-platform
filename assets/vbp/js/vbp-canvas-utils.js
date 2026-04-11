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
        enabled: true,
        tolerance: 5,
        guides: [],
        currentDragId: null,
        currentDragRect: null,
        distanceIndicators: [],

        /**
         * Habilitar/deshabilitar smart guides
         * @returns {boolean} Estado actual
         */
        toggle: function() {
            this.enabled = !this.enabled;
            if (!this.enabled) {
                this.clearGuides();
            }
            return this.enabled;
        },

        startDrag: function(elementId) {
            if (!this.enabled) return;
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
            var canvasHeight = canvas.offsetHeight;

            // Canvas guides (edges and center)
            this.guides.push(
                { type: 'vertical', position: 0, source: 'canvas-left', isCenter: false },
                { type: 'vertical', position: canvasWidth / 2, source: 'canvas-center', isCenter: true },
                { type: 'vertical', position: canvasWidth, source: 'canvas-right', isCenter: false },
                { type: 'horizontal', position: 0, source: 'canvas-top', isCenter: false },
                { type: 'horizontal', position: canvasHeight / 2, source: 'canvas-middle', isCenter: true },
                { type: 'horizontal', position: canvasHeight, source: 'canvas-bottom', isCenter: false }
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

                // Store element bounds for distance calculation
                self.guides.push(
                    { type: 'vertical', position: relativeLeft, source: elId + '-left', isCenter: false, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } },
                    { type: 'vertical', position: centerX, source: elId + '-center', isCenter: true, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } },
                    { type: 'vertical', position: relativeRight, source: elId + '-right', isCenter: false, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } }
                );

                self.guides.push(
                    { type: 'horizontal', position: relativeTop, source: elId + '-top', isCenter: false, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } },
                    { type: 'horizontal', position: centerY, source: elId + '-middle', isCenter: true, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } },
                    { type: 'horizontal', position: relativeBottom, source: elId + '-bottom', isCenter: false, elementId: elId, bounds: { left: relativeLeft, right: relativeRight, top: relativeTop, bottom: relativeBottom } }
                );
            });
        },

        checkSnap: function(dragRect) {
            if (!this.enabled) return { snapX: null, snapY: null };

            var self = this;
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return { snapX: null, snapY: null };

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;

            var left = (dragRect.left - canvasRect.left) / zoom;
            var top = (dragRect.top - canvasRect.top) / zoom;
            var width = dragRect.width / zoom;
            var height = dragRect.height / zoom;
            var right = left + width;
            var bottom = top + height;
            var centerX = left + width / 2;
            var centerY = top + height / 2;

            // Store current drag rect for distance indicators
            this.currentDragRect = { left: left, top: top, right: right, bottom: bottom, centerX: centerX, centerY: centerY, width: width, height: height };

            var snapX = null;
            var snapY = null;
            var matchedGuidesV = [];
            var matchedGuidesH = [];

            // Check vertical guides (X axis)
            this.guides.filter(function(g) { return g.type === 'vertical'; }).forEach(function(guide) {
                var matchType = null;
                var offset = 0;

                if (Math.abs(left - guide.position) <= self.tolerance) {
                    matchType = 'left';
                    offset = guide.position - left;
                } else if (Math.abs(centerX - guide.position) <= self.tolerance) {
                    matchType = 'center';
                    offset = guide.position - centerX;
                } else if (Math.abs(right - guide.position) <= self.tolerance) {
                    matchType = 'right';
                    offset = guide.position - right;
                }

                if (matchType) {
                    snapX = { offset: offset, position: guide.position, matchType: matchType };
                    matchedGuidesV.push(guide);
                }
            });

            // Check horizontal guides (Y axis)
            this.guides.filter(function(g) { return g.type === 'horizontal'; }).forEach(function(guide) {
                var matchType = null;
                var offset = 0;

                if (Math.abs(top - guide.position) <= self.tolerance) {
                    matchType = 'top';
                    offset = guide.position - top;
                } else if (Math.abs(centerY - guide.position) <= self.tolerance) {
                    matchType = 'center';
                    offset = guide.position - centerY;
                } else if (Math.abs(bottom - guide.position) <= self.tolerance) {
                    matchType = 'bottom';
                    offset = guide.position - bottom;
                }

                if (matchType) {
                    snapY = { offset: offset, position: guide.position, matchType: matchType };
                    matchedGuidesH.push(guide);
                }
            });

            this.showSmartGuides(matchedGuidesV, matchedGuidesH, snapX, snapY);

            return { snapX: snapX, snapY: snapY };
        },

        showSmartGuides: function(verticalGuides, horizontalGuides, snapX, snapY) {
            this.clearGuides();

            var canvas = document.querySelector('.vbp-canvas-wrapper');
            if (!canvas) return;

            var store = Alpine.store('vbp');
            var zoom = (store.zoom || 100) / 100;
            var self = this;

            // Render vertical guides
            verticalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--vertical';

                // Color: red for center alignment, blue for edge alignment
                var isCenter = guide.isCenter || guide.source.includes('center');
                line.classList.add(isCenter ? 'vbp-smart-guide--center' : 'vbp-smart-guide--edge');

                line.style.left = (guide.position * zoom) + 'px';
                canvas.appendChild(line);
            });

            // Render horizontal guides
            horizontalGuides.forEach(function(guide) {
                var line = document.createElement('div');
                line.className = 'vbp-smart-guide vbp-smart-guide--horizontal';

                // Color: red for center/middle alignment, blue for edge alignment
                var isCenter = guide.isCenter || guide.source.includes('middle') || guide.source.includes('center');
                line.classList.add(isCenter ? 'vbp-smart-guide--center' : 'vbp-smart-guide--edge');

                line.style.top = (guide.position * zoom) + 'px';
                canvas.appendChild(line);
            });

            // Show distance indicators when snapping
            if (snapX !== null || snapY !== null) {
                this.showDistanceIndicators(verticalGuides, horizontalGuides, snapX, snapY, zoom, canvas);
            }
        },

        /**
         * Show distance indicators when snapping to guides
         */
        showDistanceIndicators: function(verticalGuides, horizontalGuides, snapX, snapY, zoom, canvas) {
            var self = this;
            var dragRect = this.currentDragRect;
            if (!dragRect) return;

            // Find element bounds for distance calculation
            verticalGuides.forEach(function(guide) {
                if (!guide.bounds || !snapX) return;

                var distance = null;
                var indicatorY = null;

                // Calculate horizontal distance from drag element to guide element
                if (guide.bounds.right < dragRect.left) {
                    // Element is to the left
                    distance = Math.round(dragRect.left - guide.bounds.right);
                    indicatorY = Math.max(guide.bounds.top, dragRect.top);
                } else if (guide.bounds.left > dragRect.right) {
                    // Element is to the right
                    distance = Math.round(guide.bounds.left - dragRect.right);
                    indicatorY = Math.max(guide.bounds.top, dragRect.top);
                }

                if (distance !== null && distance > 0) {
                    self.createDistanceIndicator(
                        guide.position * zoom,
                        indicatorY * zoom,
                        distance + 'px',
                        'horizontal',
                        canvas
                    );
                }
            });

            horizontalGuides.forEach(function(guide) {
                if (!guide.bounds || !snapY) return;

                var distance = null;
                var indicatorX = null;

                // Calculate vertical distance from drag element to guide element
                if (guide.bounds.bottom < dragRect.top) {
                    // Element is above
                    distance = Math.round(dragRect.top - guide.bounds.bottom);
                    indicatorX = Math.max(guide.bounds.left, dragRect.left);
                } else if (guide.bounds.top > dragRect.bottom) {
                    // Element is below
                    distance = Math.round(guide.bounds.top - dragRect.bottom);
                    indicatorX = Math.max(guide.bounds.left, dragRect.left);
                }

                if (distance !== null && distance > 0) {
                    self.createDistanceIndicator(
                        indicatorX * zoom,
                        guide.position * zoom,
                        distance + 'px',
                        'vertical',
                        canvas
                    );
                }
            });
        },

        /**
         * Create a distance indicator element
         */
        createDistanceIndicator: function(x, y, text, direction, container) {
            var indicator = document.createElement('div');
            indicator.className = 'vbp-smart-guide-distance vbp-smart-guide-distance--' + direction;
            indicator.textContent = text;
            indicator.style.left = x + 'px';
            indicator.style.top = y + 'px';
            container.appendChild(indicator);
            this.distanceIndicators.push(indicator);
        },

        /**
         * Clear all guide lines and distance indicators
         */
        clearGuides: function() {
            document.querySelectorAll('.vbp-smart-guide').forEach(function(g) { g.remove(); });
            document.querySelectorAll('.vbp-smart-guide-distance').forEach(function(g) { g.remove(); });
            this.distanceIndicators = [];
        },

        endDrag: function() {
            this.currentDragId = null;
            this.currentDragRect = null;
            this.guides = [];
            this.clearGuides();
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
