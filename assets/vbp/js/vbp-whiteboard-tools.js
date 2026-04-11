/**
 * Visual Builder Pro - Whiteboard Tools
 *
 * Herramientas de dibujo y manipulacion para el whiteboard:
 * - Dibujo a mano alzada con suavizado
 * - Conectores inteligentes
 * - Manipulacion de formas
 * - Sistema de snap y guias
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.6.0
 */

(function() {
    'use strict';

    // Verificar dependencias
    if (typeof Alpine === 'undefined') {
        console.warn('[VBP Whiteboard Tools] Alpine.js no esta disponible');
        return;
    }

    /**
     * Algoritmo de suavizado de lineas (simplificacion Douglas-Peucker)
     */
    class LineSmoothing {
        /**
         * Simplificar puntos usando Douglas-Peucker
         */
        static simplify(points, tolerance = 1) {
            if (points.length <= 2) return points;

            const firstPoint = points[0];
            const lastPoint = points[points.length - 1];

            let maxDistance = 0;
            let maxIndex = 0;

            for (let i = 1; i < points.length - 1; i++) {
                const distance = this.perpendicularDistance(points[i], firstPoint, lastPoint);
                if (distance > maxDistance) {
                    maxDistance = distance;
                    maxIndex = i;
                }
            }

            if (maxDistance > tolerance) {
                const leftPart = this.simplify(points.slice(0, maxIndex + 1), tolerance);
                const rightPart = this.simplify(points.slice(maxIndex), tolerance);
                return [...leftPart.slice(0, -1), ...rightPart];
            } else {
                return [firstPoint, lastPoint];
            }
        }

        /**
         * Distancia perpendicular de un punto a una linea
         */
        static perpendicularDistance(point, lineStart, lineEnd) {
            const dx = lineEnd.x - lineStart.x;
            const dy = lineEnd.y - lineStart.y;

            if (dx === 0 && dy === 0) {
                return Math.sqrt(
                    Math.pow(point.x - lineStart.x, 2) +
                    Math.pow(point.y - lineStart.y, 2)
                );
            }

            const t = ((point.x - lineStart.x) * dx + (point.y - lineStart.y) * dy) / (dx * dx + dy * dy);
            const nearestX = lineStart.x + t * dx;
            const nearestY = lineStart.y + t * dy;

            return Math.sqrt(
                Math.pow(point.x - nearestX, 2) +
                Math.pow(point.y - nearestY, 2)
            );
        }

        /**
         * Suavizar curva usando Bezier
         */
        static smoothCurve(points, tension = 0.5) {
            if (points.length < 3) return points;

            const result = [];
            result.push(points[0]);

            for (let i = 1; i < points.length - 1; i++) {
                const previousPoint = points[i - 1];
                const currentPoint = points[i];
                const nextPoint = points[i + 1];

                // Punto de control 1
                const cp1x = currentPoint.x - (nextPoint.x - previousPoint.x) * tension;
                const cp1y = currentPoint.y - (nextPoint.y - previousPoint.y) * tension;

                // Punto de control 2
                const cp2x = currentPoint.x + (nextPoint.x - previousPoint.x) * tension;
                const cp2y = currentPoint.y + (nextPoint.y - previousPoint.y) * tension;

                result.push({
                    x: currentPoint.x,
                    y: currentPoint.y,
                    cp1: { x: cp1x, y: cp1y },
                    cp2: { x: cp2x, y: cp2y }
                });
            }

            result.push(points[points.length - 1]);

            return result;
        }

        /**
         * Generar path SVG desde puntos suavizados
         */
        static pointsToSVGPath(points, smooth = true) {
            if (points.length === 0) return '';
            if (points.length === 1) return `M ${points[0].x} ${points[0].y}`;

            let pathCommands = `M ${points[0].x} ${points[0].y}`;

            if (smooth && points.length > 2) {
                const smoothed = this.smoothCurve(points, 0.3);

                for (let i = 1; i < smoothed.length; i++) {
                    const point = smoothed[i];
                    const prevPoint = smoothed[i - 1];

                    if (point.cp1 && prevPoint.cp2) {
                        pathCommands += ` C ${prevPoint.cp2.x} ${prevPoint.cp2.y}, ${point.cp1.x} ${point.cp1.y}, ${point.x} ${point.y}`;
                    } else {
                        pathCommands += ` L ${point.x} ${point.y}`;
                    }
                }
            } else {
                for (let i = 1; i < points.length; i++) {
                    pathCommands += ` L ${points[i].x} ${points[i].y}`;
                }
            }

            return pathCommands;
        }
    }

    /**
     * Generador de formas SVG
     */
    class ShapeGenerator {
        /**
         * Generar path para rectangulo
         */
        static rectangle(x, y, width, height, cornerRadius = 0) {
            if (cornerRadius === 0) {
                return `M ${x} ${y} H ${x + width} V ${y + height} H ${x} Z`;
            }

            const r = Math.min(cornerRadius, width / 2, height / 2);
            return `M ${x + r} ${y}
                    H ${x + width - r}
                    Q ${x + width} ${y} ${x + width} ${y + r}
                    V ${y + height - r}
                    Q ${x + width} ${y + height} ${x + width - r} ${y + height}
                    H ${x + r}
                    Q ${x} ${y + height} ${x} ${y + height - r}
                    V ${y + r}
                    Q ${x} ${y} ${x + r} ${y}
                    Z`;
        }

        /**
         * Generar path para elipse/circulo
         */
        static ellipse(cx, cy, rx, ry) {
            return `M ${cx - rx} ${cy}
                    A ${rx} ${ry} 0 1 0 ${cx + rx} ${cy}
                    A ${rx} ${ry} 0 1 0 ${cx - rx} ${cy}
                    Z`;
        }

        /**
         * Generar path para rombo
         */
        static diamond(x, y, width, height) {
            const centerX = x + width / 2;
            const centerY = y + height / 2;

            return `M ${centerX} ${y}
                    L ${x + width} ${centerY}
                    L ${centerX} ${y + height}
                    L ${x} ${centerY}
                    Z`;
        }

        /**
         * Generar path para triangulo
         */
        static triangle(x, y, width, height) {
            const centerX = x + width / 2;

            return `M ${centerX} ${y}
                    L ${x + width} ${y + height}
                    L ${x} ${y + height}
                    Z`;
        }

        /**
         * Generar path para estrella
         */
        static star(cx, cy, outerRadius, innerRadius, points = 5) {
            const pathParts = [];
            const angleStep = Math.PI / points;

            for (let i = 0; i < points * 2; i++) {
                const radius = i % 2 === 0 ? outerRadius : innerRadius;
                const angle = i * angleStep - Math.PI / 2;

                const pointX = cx + radius * Math.cos(angle);
                const pointY = cy + radius * Math.sin(angle);

                pathParts.push(`${i === 0 ? 'M' : 'L'} ${pointX} ${pointY}`);
            }

            return pathParts.join(' ') + ' Z';
        }

        /**
         * Generar path para hexagono
         */
        static hexagon(cx, cy, radius) {
            const pathParts = [];

            for (let i = 0; i < 6; i++) {
                const angle = (Math.PI / 3) * i - Math.PI / 6;
                const pointX = cx + radius * Math.cos(angle);
                const pointY = cy + radius * Math.sin(angle);

                pathParts.push(`${i === 0 ? 'M' : 'L'} ${pointX} ${pointY}`);
            }

            return pathParts.join(' ') + ' Z';
        }

        /**
         * Generar path para flecha
         */
        static arrow(x, y, width, height) {
            const bodyWidth = width * 0.6;
            const bodyHeight = height * 0.4;
            const headHeight = height * 0.3;
            const centerY = y + height / 2;

            return `M ${x} ${centerY - bodyHeight / 2}
                    H ${x + bodyWidth}
                    V ${y + headHeight}
                    L ${x + width} ${centerY}
                    L ${x + bodyWidth} ${y + height - headHeight}
                    V ${centerY + bodyHeight / 2}
                    H ${x}
                    Z`;
        }

        /**
         * Generar path para nube
         */
        static cloud(x, y, width, height) {
            const centerX = x + width / 2;
            const centerY = y + height / 2;

            // Simplificacion: usar circulos superpuestos
            const cloudCircles = [
                { cx: centerX - width * 0.25, cy: centerY, r: height * 0.35 },
                { cx: centerX, cy: centerY - height * 0.15, r: height * 0.4 },
                { cx: centerX + width * 0.25, cy: centerY, r: height * 0.35 },
                { cx: centerX, cy: centerY + height * 0.1, r: height * 0.3 }
            ];

            // Generar path unificado
            let path = '';
            cloudCircles.forEach((c, i) => {
                path += `M ${c.cx - c.r} ${c.cy} A ${c.r} ${c.r} 0 1 0 ${c.cx + c.r} ${c.cy} A ${c.r} ${c.r} 0 1 0 ${c.cx - c.r} ${c.cy} `;
            });

            return path;
        }

        /**
         * Obtener path para cualquier forma
         */
        static getPath(shapeType, x, y, width, height, options = {}) {
            const cx = x + width / 2;
            const cy = y + height / 2;
            const rx = width / 2;
            const ry = height / 2;

            switch (shapeType) {
                case 'rectangle':
                case 'square':
                    return this.rectangle(x, y, width, height, options.cornerRadius || 0);

                case 'ellipse':
                case 'circle':
                    return this.ellipse(cx, cy, rx, ry);

                case 'diamond':
                    return this.diamond(x, y, width, height);

                case 'triangle':
                    return this.triangle(x, y, width, height);

                case 'star':
                    return this.star(cx, cy, Math.min(rx, ry), Math.min(rx, ry) * 0.4, options.points || 5);

                case 'hexagon':
                    return this.hexagon(cx, cy, Math.min(rx, ry));

                case 'arrow':
                    return this.arrow(x, y, width, height);

                case 'cloud':
                    return this.cloud(x, y, width, height);

                default:
                    return this.rectangle(x, y, width, height);
            }
        }
    }

    /**
     * Generador de conectores
     */
    class ConnectorGenerator {
        /**
         * Calcular puntos de anclaje de un elemento
         */
        static getAnchorPoints(element) {
            if (!element || !element.position || !element.size) {
                return {};
            }

            const x = element.position.x;
            const y = element.position.y;
            const w = element.size.width;
            const h = element.size.height;

            return {
                top: { x: x + w / 2, y: y },
                right: { x: x + w, y: y + h / 2 },
                bottom: { x: x + w / 2, y: y + h },
                left: { x: x, y: y + h / 2 },
                center: { x: x + w / 2, y: y + h / 2 }
            };
        }

        /**
         * Encontrar mejor ancla de conexion
         */
        static findBestAnchor(fromPoint, targetElement) {
            const anchors = this.getAnchorPoints(targetElement);
            let bestAnchor = 'center';
            let bestDistance = Infinity;

            Object.entries(anchors).forEach(([name, point]) => {
                const distance = Math.sqrt(
                    Math.pow(fromPoint.x - point.x, 2) +
                    Math.pow(fromPoint.y - point.y, 2)
                );

                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestAnchor = name;
                }
            });

            return bestAnchor;
        }

        /**
         * Generar path de conector recto
         */
        static straightPath(from, to) {
            return `M ${from.x} ${from.y} L ${to.x} ${to.y}`;
        }

        /**
         * Generar path de conector curvo
         */
        static curvedPath(from, to) {
            const dx = to.x - from.x;
            const dy = to.y - from.y;

            // Punto de control en el medio con offset
            const midX = from.x + dx / 2;
            const midY = from.y + dy / 2;

            // Offset perpendicular
            const offsetMagnitude = Math.min(Math.abs(dx), Math.abs(dy)) * 0.3;
            const angle = Math.atan2(dy, dx) + Math.PI / 2;

            const ctrlX = midX + Math.cos(angle) * offsetMagnitude;
            const ctrlY = midY + Math.sin(angle) * offsetMagnitude;

            return `M ${from.x} ${from.y} Q ${ctrlX} ${ctrlY} ${to.x} ${to.y}`;
        }

        /**
         * Generar path de conector con codo
         */
        static elbowPath(from, to, fromAnchor = 'right', toAnchor = 'left') {
            const dx = to.x - from.x;
            const dy = to.y - from.y;

            // Determinar direccion basada en anclas
            const midOffset = 20;
            let pathParts = [`M ${from.x} ${from.y}`];

            if (fromAnchor === 'right' || fromAnchor === 'left') {
                // Salir horizontal
                const midX = from.x + dx / 2;
                pathParts.push(`H ${midX}`);
                pathParts.push(`V ${to.y}`);
                pathParts.push(`H ${to.x}`);
            } else {
                // Salir vertical
                const midY = from.y + dy / 2;
                pathParts.push(`V ${midY}`);
                pathParts.push(`H ${to.x}`);
                pathParts.push(`V ${to.y}`);
            }

            return pathParts.join(' ');
        }

        /**
         * Generar path de conector escalonado
         */
        static stepPath(from, to) {
            const stepSize = 40;
            const dx = to.x - from.x;

            return `M ${from.x} ${from.y}
                    H ${from.x + stepSize}
                    V ${to.y}
                    H ${to.x}`;
        }

        /**
         * Generar marker de flecha SVG
         */
        static arrowMarker(id, fill = '#000000') {
            return `<marker id="${id}" viewBox="0 0 10 10" refX="9" refY="5"
                    markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                <path d="M 0 0 L 10 5 L 0 10 z" fill="${fill}"/>
            </marker>`;
        }

        /**
         * Obtener path para estilo de conector
         */
        static getPath(style, from, to, fromAnchor, toAnchor) {
            switch (style) {
                case 'curved':
                    return this.curvedPath(from, to);
                case 'elbow':
                    return this.elbowPath(from, to, fromAnchor, toAnchor);
                case 'step':
                    return this.stepPath(from, to);
                case 'straight':
                default:
                    return this.straightPath(from, to);
            }
        }
    }

    /**
     * Sistema de snap y guias
     */
    class SnapGuides {
        constructor(gridSize = 20, snapThreshold = 10) {
            this.gridSize = gridSize;
            this.snapThreshold = snapThreshold;
            this.guides = {
                horizontal: [],
                vertical: []
            };
        }

        /**
         * Snap a grid
         */
        snapToGrid(value) {
            return Math.round(value / this.gridSize) * this.gridSize;
        }

        /**
         * Snap posicion a grid
         */
        snapPosition(position) {
            return {
                x: this.snapToGrid(position.x),
                y: this.snapToGrid(position.y)
            };
        }

        /**
         * Encontrar guias de alineacion con otros elementos
         */
        findAlignmentGuides(element, allElements) {
            const guides = {
                horizontal: [],
                vertical: []
            };

            if (!element || !element.position || !element.size) {
                return guides;
            }

            const elementBounds = {
                left: element.position.x,
                right: element.position.x + element.size.width,
                top: element.position.y,
                bottom: element.position.y + element.size.height,
                centerX: element.position.x + element.size.width / 2,
                centerY: element.position.y + element.size.height / 2
            };

            allElements.forEach(other => {
                if (other.id === element.id) return;
                if (!other.position || !other.size) return;

                const otherBounds = {
                    left: other.position.x,
                    right: other.position.x + other.size.width,
                    top: other.position.y,
                    bottom: other.position.y + other.size.height,
                    centerX: other.position.x + other.size.width / 2,
                    centerY: other.position.y + other.size.height / 2
                };

                // Guias verticales
                const verticalChecks = [
                    { source: 'left', target: 'left' },
                    { source: 'left', target: 'right' },
                    { source: 'right', target: 'left' },
                    { source: 'right', target: 'right' },
                    { source: 'centerX', target: 'centerX' }
                ];

                verticalChecks.forEach(check => {
                    const sourceValue = elementBounds[check.source];
                    const targetValue = otherBounds[check.target];
                    const diff = Math.abs(sourceValue - targetValue);

                    if (diff <= this.snapThreshold) {
                        guides.vertical.push({
                            position: targetValue,
                            sourceEdge: check.source,
                            targetEdge: check.target,
                            targetElement: other.id
                        });
                    }
                });

                // Guias horizontales
                const horizontalChecks = [
                    { source: 'top', target: 'top' },
                    { source: 'top', target: 'bottom' },
                    { source: 'bottom', target: 'top' },
                    { source: 'bottom', target: 'bottom' },
                    { source: 'centerY', target: 'centerY' }
                ];

                horizontalChecks.forEach(check => {
                    const sourceValue = elementBounds[check.source];
                    const targetValue = otherBounds[check.target];
                    const diff = Math.abs(sourceValue - targetValue);

                    if (diff <= this.snapThreshold) {
                        guides.horizontal.push({
                            position: targetValue,
                            sourceEdge: check.source,
                            targetEdge: check.target,
                            targetElement: other.id
                        });
                    }
                });
            });

            return guides;
        }

        /**
         * Aplicar snap a posicion basado en guias
         */
        applySnap(position, element, allElements) {
            const guides = this.findAlignmentGuides({
                ...element,
                position: position
            }, allElements);

            let snappedPosition = { ...position };

            // Aplicar snap vertical
            if (guides.vertical.length > 0) {
                const guide = guides.vertical[0];
                if (guide.sourceEdge === 'left') {
                    snappedPosition.x = guide.position;
                } else if (guide.sourceEdge === 'right') {
                    snappedPosition.x = guide.position - element.size.width;
                } else if (guide.sourceEdge === 'centerX') {
                    snappedPosition.x = guide.position - element.size.width / 2;
                }
            }

            // Aplicar snap horizontal
            if (guides.horizontal.length > 0) {
                const guide = guides.horizontal[0];
                if (guide.sourceEdge === 'top') {
                    snappedPosition.y = guide.position;
                } else if (guide.sourceEdge === 'bottom') {
                    snappedPosition.y = guide.position - element.size.height;
                } else if (guide.sourceEdge === 'centerY') {
                    snappedPosition.y = guide.position - element.size.height / 2;
                }
            }

            return {
                position: snappedPosition,
                guides: guides
            };
        }
    }

    /**
     * Herramienta de seleccion rectangular
     */
    class SelectionTool {
        constructor() {
            this.isSelecting = false;
            this.startPoint = null;
            this.currentPoint = null;
        }

        start(point) {
            this.isSelecting = true;
            this.startPoint = { ...point };
            this.currentPoint = { ...point };
        }

        move(point) {
            if (!this.isSelecting) return;
            this.currentPoint = { ...point };
        }

        end() {
            this.isSelecting = false;
            const rect = this.getRect();
            this.startPoint = null;
            this.currentPoint = null;
            return rect;
        }

        getRect() {
            if (!this.startPoint || !this.currentPoint) return null;

            return {
                x: Math.min(this.startPoint.x, this.currentPoint.x),
                y: Math.min(this.startPoint.y, this.currentPoint.y),
                width: Math.abs(this.currentPoint.x - this.startPoint.x),
                height: Math.abs(this.currentPoint.y - this.startPoint.y)
            };
        }
    }

    /**
     * Herramienta de resize
     */
    class ResizeTool {
        constructor() {
            this.isResizing = false;
            this.handle = null; // 'nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'
            this.startBounds = null;
            this.startPoint = null;
            this.maintainAspectRatio = false;
        }

        start(handle, elementBounds, point, maintainAspectRatio = false) {
            this.isResizing = true;
            this.handle = handle;
            this.startBounds = { ...elementBounds };
            this.startPoint = { ...point };
            this.maintainAspectRatio = maintainAspectRatio;
        }

        move(point) {
            if (!this.isResizing) return null;

            const dx = point.x - this.startPoint.x;
            const dy = point.y - this.startPoint.y;

            let newBounds = { ...this.startBounds };

            switch (this.handle) {
                case 'nw':
                    newBounds.x = this.startBounds.x + dx;
                    newBounds.y = this.startBounds.y + dy;
                    newBounds.width = this.startBounds.width - dx;
                    newBounds.height = this.startBounds.height - dy;
                    break;

                case 'n':
                    newBounds.y = this.startBounds.y + dy;
                    newBounds.height = this.startBounds.height - dy;
                    break;

                case 'ne':
                    newBounds.y = this.startBounds.y + dy;
                    newBounds.width = this.startBounds.width + dx;
                    newBounds.height = this.startBounds.height - dy;
                    break;

                case 'e':
                    newBounds.width = this.startBounds.width + dx;
                    break;

                case 'se':
                    newBounds.width = this.startBounds.width + dx;
                    newBounds.height = this.startBounds.height + dy;
                    break;

                case 's':
                    newBounds.height = this.startBounds.height + dy;
                    break;

                case 'sw':
                    newBounds.x = this.startBounds.x + dx;
                    newBounds.width = this.startBounds.width - dx;
                    newBounds.height = this.startBounds.height + dy;
                    break;

                case 'w':
                    newBounds.x = this.startBounds.x + dx;
                    newBounds.width = this.startBounds.width - dx;
                    break;
            }

            // Mantener proporcion si es necesario
            if (this.maintainAspectRatio) {
                const aspectRatio = this.startBounds.width / this.startBounds.height;

                if (['nw', 'ne', 'sw', 'se'].includes(this.handle)) {
                    const newRatio = newBounds.width / newBounds.height;

                    if (newRatio > aspectRatio) {
                        newBounds.width = newBounds.height * aspectRatio;
                    } else {
                        newBounds.height = newBounds.width / aspectRatio;
                    }
                }
            }

            // Asegurar tamano minimo
            const minSize = 20;
            newBounds.width = Math.max(minSize, newBounds.width);
            newBounds.height = Math.max(minSize, newBounds.height);

            return newBounds;
        }

        end() {
            this.isResizing = false;
            this.handle = null;
            this.startBounds = null;
            this.startPoint = null;
        }

        /**
         * Obtener cursor para handle
         */
        static getCursor(handle) {
            const cursors = {
                'nw': 'nwse-resize',
                'n': 'ns-resize',
                'ne': 'nesw-resize',
                'e': 'ew-resize',
                'se': 'nwse-resize',
                's': 'ns-resize',
                'sw': 'nesw-resize',
                'w': 'ew-resize'
            };
            return cursors[handle] || 'default';
        }
    }

    /**
     * Hit testing para elementos
     */
    class HitTesting {
        /**
         * Verificar si punto esta dentro de un elemento
         */
        static isPointInElement(point, element) {
            if (!element.position || !element.size) {
                return false;
            }

            const { x, y } = element.position;
            const { width, height } = element.size;

            // Rectangulo simple para la mayoria de elementos
            if (['sticky', 'shape', 'text', 'stamp', 'frame'].includes(element.type)) {
                return (
                    point.x >= x &&
                    point.x <= x + width &&
                    point.y >= y &&
                    point.y <= y + height
                );
            }

            // Dibujos: verificar cercania a la linea
            if (element.type === 'draw' && element.points) {
                return this.isPointNearPath(point, element.points, 10);
            }

            // Conectores
            if (element.type === 'connector') {
                return this.isPointNearLine(
                    point,
                    element.fromPoint || element.from,
                    element.toPoint || element.to,
                    10
                );
            }

            return false;
        }

        /**
         * Verificar si punto esta cerca de un path
         */
        static isPointNearPath(point, pathPoints, threshold) {
            for (let i = 0; i < pathPoints.length - 1; i++) {
                if (this.isPointNearLine(point, pathPoints[i], pathPoints[i + 1], threshold)) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Verificar si punto esta cerca de una linea
         */
        static isPointNearLine(point, lineStart, lineEnd, threshold) {
            const lineLength = Math.sqrt(
                Math.pow(lineEnd.x - lineStart.x, 2) +
                Math.pow(lineEnd.y - lineStart.y, 2)
            );

            if (lineLength === 0) return false;

            const t = Math.max(0, Math.min(1,
                ((point.x - lineStart.x) * (lineEnd.x - lineStart.x) +
                 (point.y - lineStart.y) * (lineEnd.y - lineStart.y)) / (lineLength * lineLength)
            ));

            const nearestX = lineStart.x + t * (lineEnd.x - lineStart.x);
            const nearestY = lineStart.y + t * (lineEnd.y - lineStart.y);

            const distance = Math.sqrt(
                Math.pow(point.x - nearestX, 2) +
                Math.pow(point.y - nearestY, 2)
            );

            return distance <= threshold;
        }

        /**
         * Encontrar elemento bajo el cursor
         */
        static findElementAtPoint(point, elements) {
            // Buscar de arriba hacia abajo (ultimos elementos primero)
            for (let i = elements.length - 1; i >= 0; i--) {
                if (this.isPointInElement(point, elements[i])) {
                    return elements[i];
                }
            }
            return null;
        }

        /**
         * Verificar si punto esta en handle de resize
         */
        static getResizeHandle(point, element, handleSize = 8) {
            if (!element.position || !element.size) return null;

            const { x, y } = element.position;
            const { width, height } = element.size;

            const handles = {
                'nw': { x: x, y: y },
                'n': { x: x + width / 2, y: y },
                'ne': { x: x + width, y: y },
                'e': { x: x + width, y: y + height / 2 },
                'se': { x: x + width, y: y + height },
                's': { x: x + width / 2, y: y + height },
                'sw': { x: x, y: y + height },
                'w': { x: x, y: y + height / 2 }
            };

            for (const [name, handlePos] of Object.entries(handles)) {
                const distance = Math.sqrt(
                    Math.pow(point.x - handlePos.x, 2) +
                    Math.pow(point.y - handlePos.y, 2)
                );

                if (distance <= handleSize) {
                    return name;
                }
            }

            return null;
        }
    }

    // Exponer clases globalmente
    window.VBPLineSmoothing = LineSmoothing;
    window.VBPShapeGenerator = ShapeGenerator;
    window.VBPConnectorGenerator = ConnectorGenerator;
    window.VBPSnapGuides = SnapGuides;
    window.VBPSelectionTool = SelectionTool;
    window.VBPResizeTool = ResizeTool;
    window.VBPHitTesting = HitTesting;

})();
