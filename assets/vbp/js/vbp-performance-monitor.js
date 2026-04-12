/**
 * Visual Builder Pro - Performance Monitor
 * Sistema de monitoreo de rendimiento en tiempo real
 *
 * @package Flavor_Platform
 * @since 2.2.0
 */

// Fallback de vbpLog si no esta definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

/**
 * Performance Monitor Store
 * Monitorea metricas de rendimiento del editor VBP
 */
document.addEventListener('alpine:init', function() {
    'use strict';

    // Constantes de umbrales de rendimiento
    var THRESHOLDS = {
        ELEMENT_COUNT_WARNING: 500,
        ELEMENT_COUNT_ERROR: 1000,
        NESTING_DEPTH_WARNING: 10,
        NESTING_DEPTH_ERROR: 15,
        RENDER_TIME_WARNING: 100,
        RENDER_TIME_ERROR: 250,
        JSON_SIZE_WARNING: 1048576, // 1MB
        JSON_SIZE_ERROR: 5242880, // 5MB
        FPS_WARNING: 30,
        FPS_CRITICAL: 15,
        MEMORY_WARNING: 100, // MB
        MEMORY_ERROR: 250 // MB
    };

    // Buffer para calcular FPS promedio
    var fpsBuffer = [];
    var FPS_BUFFER_SIZE = 60;

    // Historial de metricas para graficos
    var metricsHistory = {
        fps: [],
        renderTime: [],
        elementCount: [],
        memoryUsage: []
    };
    var HISTORY_MAX_LENGTH = 120; // 2 minutos a 1 muestra/segundo

    Alpine.store('vbpPerformance', {
        // Estado del monitor
        isEnabled: true,
        isPanelOpen: false,
        isCollapsed: false,

        // Control de inicialización y cleanup
        _initialized: false,
        _fpsAnimationFrameId: null,
        _monitoringIntervalId: null,
        _eventHandlers: {},

        // Metricas principales
        metrics: {
            renderTime: 0,
            averageRenderTime: 0,
            elementCount: 0,
            nestingDepth: 0,
            memoryUsage: 0,
            fps: 60,
            lastSaveTime: 0,
            loadTime: 0,
            jsonSize: 0,
            domNodes: 0,
            listenerCount: 0
        },

        // Historial para graficos
        history: metricsHistory,

        // Warnings activos
        warnings: [],

        // Estadisticas de sesion
        sessionStats: {
            startTime: Date.now(),
            totalSaves: 0,
            totalUndos: 0,
            totalRedos: 0,
            peakElementCount: 0,
            peakMemoryUsage: 0,
            peakRenderTime: 0
        },

        // Estado de mediciones
        measurementActive: false,
        lastMeasureStart: 0,

        // ============ INICIALIZACION ============

        /**
         * Inicializar el monitor de rendimiento
         */
        init: function() {
            if (this._initialized) return;
            this._initialized = true;

            var self = this;

            // Medir tiempo de carga inicial
            if (window.performance && window.performance.timing) {
                var timing = window.performance.timing;
                this.metrics.loadTime = timing.domContentLoadedEventEnd - timing.navigationStart;
            }

            // Iniciar monitoreo de FPS
            this.startFPSMonitor();

            // Iniciar monitoreo periodico
            this.startPeriodicMonitoring();

            // Escuchar eventos del editor
            this.attachEventListeners();

            vbpLog.log('Performance Monitor inicializado');
        },

        /**
         * Destruir y limpiar recursos
         */
        destroy: function() {
            // Cancelar FPS animation frame
            if (this._fpsAnimationFrameId) {
                cancelAnimationFrame(this._fpsAnimationFrameId);
                this._fpsAnimationFrameId = null;
            }

            // Cancelar interval de monitoreo
            if (this._monitoringIntervalId) {
                clearInterval(this._monitoringIntervalId);
                this._monitoringIntervalId = null;
            }

            // Remover event listeners
            var handlers = this._eventHandlers;
            if (handlers.elementAdded) {
                document.removeEventListener('vbp:element:added', handlers.elementAdded);
            }
            if (handlers.elementRemoved) {
                document.removeEventListener('vbp:element:removed', handlers.elementRemoved);
            }
            if (handlers.elementUpdated) {
                document.removeEventListener('vbp:element:updated', handlers.elementUpdated);
            }
            if (handlers.beforeSave) {
                document.removeEventListener('vbp:beforeSave', handlers.beforeSave);
            }
            if (handlers.afterSave) {
                document.removeEventListener('vbp:afterSave', handlers.afterSave);
            }
            if (handlers.undo) {
                document.removeEventListener('vbp:undo', handlers.undo);
            }
            if (handlers.redo) {
                document.removeEventListener('vbp:redo', handlers.redo);
            }
            this._eventHandlers = {};

            // Limpiar buffers
            fpsBuffer = [];
            metricsHistory.fps = [];
            metricsHistory.renderTime = [];
            metricsHistory.elementCount = [];
            metricsHistory.memoryUsage = [];

            this._initialized = false;
            vbpLog.log('Performance Monitor destruido');
        },

        /**
         * Adjuntar listeners de eventos del editor
         */
        attachEventListeners: function() {
            var self = this;

            // Guardar referencias para cleanup
            this._eventHandlers.elementAdded = function() {
                self.updateElementMetrics();
            };
            this._eventHandlers.elementRemoved = function() {
                self.updateElementMetrics();
            };
            this._eventHandlers.elementUpdated = function() {
                self.measureRenderTime();
            };
            this._eventHandlers.beforeSave = function() {
                self.measureSaveStart();
            };
            this._eventHandlers.afterSave = function() {
                self.measureSaveEnd();
                self.sessionStats.totalSaves++;
            };
            this._eventHandlers.undo = function() {
                self.sessionStats.totalUndos++;
            };
            this._eventHandlers.redo = function() {
                self.sessionStats.totalRedos++;
            };

            // Registrar eventos
            document.addEventListener('vbp:element:added', this._eventHandlers.elementAdded);
            document.addEventListener('vbp:element:removed', this._eventHandlers.elementRemoved);
            document.addEventListener('vbp:element:updated', this._eventHandlers.elementUpdated);
            document.addEventListener('vbp:beforeSave', this._eventHandlers.beforeSave);
            document.addEventListener('vbp:afterSave', this._eventHandlers.afterSave);
            document.addEventListener('vbp:undo', this._eventHandlers.undo);
            document.addEventListener('vbp:redo', this._eventHandlers.redo);
        },

        // ============ MONITOREO DE FPS ============

        /**
         * Iniciar monitor de FPS
         */
        startFPSMonitor: function() {
            var self = this;
            var lastFrameTime = performance.now();
            var frameCount = 0;

            function measureFPS() {
                // Si está deshabilitado, no continuar el loop
                if (!self.isEnabled) {
                    self._fpsAnimationFrameId = null;
                    return;
                }

                var currentTime = performance.now();
                frameCount++;

                // Calcular cada segundo
                if (currentTime - lastFrameTime >= 1000) {
                    var currentFPS = Math.round(frameCount * 1000 / (currentTime - lastFrameTime));

                    // Agregar al buffer
                    fpsBuffer.push(currentFPS);
                    if (fpsBuffer.length > FPS_BUFFER_SIZE) {
                        fpsBuffer.shift();
                    }

                    // Calcular promedio
                    var fpsSum = 0;
                    for (var i = 0; i < fpsBuffer.length; i++) {
                        fpsSum += fpsBuffer[i];
                    }
                    self.metrics.fps = Math.round(fpsSum / fpsBuffer.length);

                    // Agregar al historial
                    self.addToHistory('fps', currentFPS);

                    frameCount = 0;
                    lastFrameTime = currentTime;

                    // Verificar warnings
                    self.checkFPSWarnings();
                }

                self._fpsAnimationFrameId = requestAnimationFrame(measureFPS);
            }

            this._fpsAnimationFrameId = requestAnimationFrame(measureFPS);
        },

        /**
         * Reanudar monitor de FPS si fue detenido
         */
        resumeFPSMonitor: function() {
            if (this.isEnabled && !this._fpsAnimationFrameId) {
                this.startFPSMonitor();
            }
        },

        /**
         * Verificar warnings de FPS
         */
        checkFPSWarnings: function() {
            if (this.metrics.fps < THRESHOLDS.FPS_CRITICAL) {
                this.addWarning('fps-critical', 'FPS critico (' + this.metrics.fps + '). El editor puede estar lento.', 'error');
            } else if (this.metrics.fps < THRESHOLDS.FPS_WARNING) {
                this.addWarning('fps-low', 'FPS bajo (' + this.metrics.fps + '). Considera reducir elementos.', 'warning');
            } else {
                this.removeWarning('fps-critical');
                this.removeWarning('fps-low');
            }
        },

        // ============ MONITOREO PERIODICO ============

        /**
         * Iniciar monitoreo periodico (cada segundo)
         */
        startPeriodicMonitoring: function() {
            var self = this;

            // Cancelar si ya existe
            if (this._monitoringIntervalId) {
                clearInterval(this._monitoringIntervalId);
            }

            this._monitoringIntervalId = setInterval(function() {
                if (!self.isEnabled) return;

                self.updateElementMetrics();
                self.updateMemoryMetrics();
                self.updateDOMMetrics();
                self.calculateJSONSize();
                self.analyzePerformance();

            }, 1000);
        },

        /**
         * Actualizar metricas de elementos
         */
        updateElementMetrics: function() {
            var store = Alpine.store('vbp');
            if (!store) return;

            // Contar elementos total (incluyendo hijos)
            var elementCount = this.countAllElements(store.elements);
            this.metrics.elementCount = elementCount;

            // Actualizar pico
            if (elementCount > this.sessionStats.peakElementCount) {
                this.sessionStats.peakElementCount = elementCount;
            }

            // Calcular profundidad de anidamiento
            this.metrics.nestingDepth = this.calculateMaxNesting(store.elements);

            // Agregar al historial
            this.addToHistory('elementCount', elementCount);

            // Verificar warnings
            this.checkElementWarnings();
        },

        /**
         * Contar todos los elementos recursivamente
         */
        countAllElements: function(elements) {
            if (!elements || !Array.isArray(elements)) return 0;

            var count = elements.length;
            for (var i = 0; i < elements.length; i++) {
                if (elements[i].children && elements[i].children.length > 0) {
                    count += this.countAllElements(elements[i].children);
                }
            }
            return count;
        },

        /**
         * Calcular profundidad maxima de anidamiento
         */
        calculateMaxNesting: function(elements, currentDepth) {
            if (!elements || !Array.isArray(elements) || elements.length === 0) {
                return currentDepth || 0;
            }

            currentDepth = currentDepth || 1;
            var maxDepth = currentDepth;

            for (var i = 0; i < elements.length; i++) {
                if (elements[i].children && elements[i].children.length > 0) {
                    var childDepth = this.calculateMaxNesting(elements[i].children, currentDepth + 1);
                    if (childDepth > maxDepth) {
                        maxDepth = childDepth;
                    }
                }
            }

            return maxDepth;
        },

        /**
         * Verificar warnings de elementos
         */
        checkElementWarnings: function() {
            // Cantidad de elementos
            if (this.metrics.elementCount > THRESHOLDS.ELEMENT_COUNT_ERROR) {
                this.addWarning('elements-error', 'Demasiados elementos (' + this.metrics.elementCount + '). Esto puede causar problemas serios de rendimiento.', 'error');
            } else if (this.metrics.elementCount > THRESHOLDS.ELEMENT_COUNT_WARNING) {
                this.addWarning('elements-warning', 'Muchos elementos (' + this.metrics.elementCount + '). Considera dividir en paginas o secciones.', 'warning');
            } else {
                this.removeWarning('elements-error');
                this.removeWarning('elements-warning');
            }

            // Profundidad de anidamiento
            if (this.metrics.nestingDepth > THRESHOLDS.NESTING_DEPTH_ERROR) {
                this.addWarning('nesting-error', 'Anidamiento excesivo (' + this.metrics.nestingDepth + ' niveles). Simplifica la estructura.', 'error');
            } else if (this.metrics.nestingDepth > THRESHOLDS.NESTING_DEPTH_WARNING) {
                this.addWarning('nesting-warning', 'Anidamiento profundo (' + this.metrics.nestingDepth + ' niveles). Puede afectar el rendimiento.', 'warning');
            } else {
                this.removeWarning('nesting-error');
                this.removeWarning('nesting-warning');
            }
        },

        /**
         * Actualizar metricas de memoria
         */
        updateMemoryMetrics: function() {
            if (window.performance && window.performance.memory) {
                var memoryMB = Math.round(window.performance.memory.usedJSHeapSize / 1048576);
                this.metrics.memoryUsage = memoryMB;

                // Actualizar pico
                if (memoryMB > this.sessionStats.peakMemoryUsage) {
                    this.sessionStats.peakMemoryUsage = memoryMB;
                }

                // Agregar al historial
                this.addToHistory('memoryUsage', memoryMB);

                // Verificar warnings
                this.checkMemoryWarnings();
            }
        },

        /**
         * Verificar warnings de memoria
         */
        checkMemoryWarnings: function() {
            if (this.metrics.memoryUsage > THRESHOLDS.MEMORY_ERROR) {
                this.addWarning('memory-error', 'Uso de memoria alto (' + this.metrics.memoryUsage + 'MB). Guarda y recarga la pagina.', 'error');
            } else if (this.metrics.memoryUsage > THRESHOLDS.MEMORY_WARNING) {
                this.addWarning('memory-warning', 'Memoria elevada (' + this.metrics.memoryUsage + 'MB). Considera guardar pronto.', 'warning');
            } else {
                this.removeWarning('memory-error');
                this.removeWarning('memory-warning');
            }
        },

        /**
         * Actualizar metricas de DOM
         */
        updateDOMMetrics: function() {
            var canvas = document.querySelector('.vbp-canvas-content');
            if (canvas) {
                this.metrics.domNodes = canvas.getElementsByTagName('*').length;
            }

            // Contar listeners (aproximado)
            this.metrics.listenerCount = this.estimateListenerCount();
        },

        /**
         * Estimar cantidad de listeners activos
         */
        estimateListenerCount: function() {
            // Estimacion basada en nodos interactivos
            var interactiveElements = document.querySelectorAll('.vbp-canvas-content [data-vbp-element]');
            return interactiveElements.length * 3; // Promedio de 3 listeners por elemento
        },

        /**
         * Calcular tamano del JSON
         */
        calculateJSONSize: function() {
            var store = Alpine.store('vbp');
            if (!store) return;

            try {
                var jsonString = JSON.stringify({
                    elements: store.elements,
                    settings: store.settings
                });
                this.metrics.jsonSize = jsonString.length;

                // Verificar warnings
                this.checkJSONWarnings();
            } catch (error) {
                vbpLog.error('Error calculando tamano JSON:', error);
            }
        },

        /**
         * Verificar warnings de tamano JSON
         */
        checkJSONWarnings: function() {
            if (this.metrics.jsonSize > THRESHOLDS.JSON_SIZE_ERROR) {
                this.addWarning('json-error', 'Documento muy grande (' + this.formatBytes(this.metrics.jsonSize) + '). Puede fallar al guardar.', 'error');
            } else if (this.metrics.jsonSize > THRESHOLDS.JSON_SIZE_WARNING) {
                this.addWarning('json-warning', 'Documento grande (' + this.formatBytes(this.metrics.jsonSize) + '). Considera optimizar.', 'warning');
            } else {
                this.removeWarning('json-error');
                this.removeWarning('json-warning');
            }
        },

        // ============ MEDICION DE RENDIMIENTO ============

        /**
         * Medir tiempo de renderizado
         */
        measureRender: function(renderFunction) {
            var start = performance.now();
            renderFunction();
            var duration = performance.now() - start;

            this.metrics.renderTime = Math.round(duration * 100) / 100;

            // Calcular promedio
            this.addToHistory('renderTime', duration);
            var renderHistory = metricsHistory.renderTime;
            if (renderHistory.length > 0) {
                var sum = 0;
                for (var i = 0; i < renderHistory.length; i++) {
                    sum += renderHistory[i].value;
                }
                this.metrics.averageRenderTime = Math.round(sum / renderHistory.length * 100) / 100;
            }

            // Actualizar pico
            if (duration > this.sessionStats.peakRenderTime) {
                this.sessionStats.peakRenderTime = duration;
            }

            // Verificar warnings
            this.checkRenderWarnings();
        },

        /**
         * Medir tiempo de renderizado de forma asincrona
         */
        measureRenderTime: function() {
            var self = this;
            var start = performance.now();

            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    var duration = performance.now() - start;
                    self.metrics.renderTime = Math.round(duration * 100) / 100;

                    if (duration > self.sessionStats.peakRenderTime) {
                        self.sessionStats.peakRenderTime = duration;
                    }

                    self.checkRenderWarnings();
                });
            });
        },

        /**
         * Verificar warnings de renderizado
         */
        checkRenderWarnings: function() {
            if (this.metrics.renderTime > THRESHOLDS.RENDER_TIME_ERROR) {
                this.addWarning('render-error', 'Renderizado muy lento (' + this.metrics.renderTime + 'ms). Revisa la complejidad del diseno.', 'error');
            } else if (this.metrics.renderTime > THRESHOLDS.RENDER_TIME_WARNING) {
                this.addWarning('render-warning', 'Renderizado lento (' + this.metrics.renderTime + 'ms). Considera simplificar.', 'warning');
            } else {
                this.removeWarning('render-error');
                this.removeWarning('render-warning');
            }
        },

        /**
         * Marcar inicio de guardado
         */
        measureSaveStart: function() {
            this.lastMeasureStart = performance.now();
        },

        /**
         * Marcar fin de guardado
         */
        measureSaveEnd: function() {
            if (this.lastMeasureStart) {
                this.metrics.lastSaveTime = Math.round(performance.now() - this.lastMeasureStart);
                this.lastMeasureStart = 0;
            }
        },

        // ============ ANALISIS DE RENDIMIENTO ============

        /**
         * Analizar rendimiento general
         */
        analyzePerformance: function() {
            // Generar sugerencias de optimizacion basadas en metricas
            this.generateOptimizationSuggestions();
        },

        /**
         * Generar sugerencias de optimizacion
         */
        generateOptimizationSuggestions: function() {
            var suggestions = [];

            // Sugerencia por cantidad de elementos
            if (this.metrics.elementCount > THRESHOLDS.ELEMENT_COUNT_WARNING) {
                suggestions.push({
                    type: 'elements',
                    priority: this.metrics.elementCount > THRESHOLDS.ELEMENT_COUNT_ERROR ? 'high' : 'medium',
                    title: 'Reducir cantidad de elementos',
                    description: 'Considera usar componentes reutilizables o dividir el contenido en multiples paginas.'
                });
            }

            // Sugerencia por anidamiento
            if (this.metrics.nestingDepth > THRESHOLDS.NESTING_DEPTH_WARNING) {
                suggestions.push({
                    type: 'nesting',
                    priority: this.metrics.nestingDepth > THRESHOLDS.NESTING_DEPTH_ERROR ? 'high' : 'medium',
                    title: 'Simplificar estructura',
                    description: 'Reduce el nivel de anidamiento. Usa layouts mas planos cuando sea posible.'
                });
            }

            // Sugerencia por tamano JSON
            if (this.metrics.jsonSize > THRESHOLDS.JSON_SIZE_WARNING) {
                suggestions.push({
                    type: 'size',
                    priority: this.metrics.jsonSize > THRESHOLDS.JSON_SIZE_ERROR ? 'high' : 'medium',
                    title: 'Optimizar contenido',
                    description: 'Elimina elementos innecesarios o imagenes embebidas. Usa URLs externas para medios.'
                });
            }

            // Sugerencia por FPS bajo
            if (this.metrics.fps < THRESHOLDS.FPS_WARNING) {
                suggestions.push({
                    type: 'fps',
                    priority: this.metrics.fps < THRESHOLDS.FPS_CRITICAL ? 'high' : 'medium',
                    title: 'Mejorar fluidez',
                    description: 'Reduce animaciones complejas o efectos de sombra. Cierra otras pestanas del navegador.'
                });
            }

            this.optimizationSuggestions = suggestions;
        },

        /**
         * Obtener puntuacion de rendimiento (0-100)
         */
        getPerformanceScore: function() {
            var score = 100;

            // Penalizar por elementos
            if (this.metrics.elementCount > THRESHOLDS.ELEMENT_COUNT_WARNING) {
                var elementPenalty = Math.min(30, (this.metrics.elementCount - THRESHOLDS.ELEMENT_COUNT_WARNING) / 20);
                score -= elementPenalty;
            }

            // Penalizar por FPS bajo
            if (this.metrics.fps < 60) {
                var fpsPenalty = Math.min(30, (60 - this.metrics.fps));
                score -= fpsPenalty;
            }

            // Penalizar por render lento
            if (this.metrics.renderTime > THRESHOLDS.RENDER_TIME_WARNING) {
                var renderPenalty = Math.min(20, (this.metrics.renderTime - THRESHOLDS.RENDER_TIME_WARNING) / 10);
                score -= renderPenalty;
            }

            // Penalizar por JSON grande
            if (this.metrics.jsonSize > THRESHOLDS.JSON_SIZE_WARNING) {
                score -= 10;
            }

            return Math.max(0, Math.round(score));
        },

        /**
         * Obtener estado de salud general
         */
        getHealthStatus: function() {
            var score = this.getPerformanceScore();

            if (score >= 80) return 'excellent';
            if (score >= 60) return 'good';
            if (score >= 40) return 'fair';
            if (score >= 20) return 'poor';
            return 'critical';
        },

        // ============ GESTION DE WARNINGS ============

        /**
         * Agregar warning
         */
        addWarning: function(id, message, level) {
            level = level || 'warning';

            // Verificar si ya existe
            for (var i = 0; i < this.warnings.length; i++) {
                if (this.warnings[i].id === id) {
                    // Actualizar mensaje si cambio
                    if (this.warnings[i].message !== message) {
                        this.warnings[i].message = message;
                        this.warnings[i].level = level;
                        this.warnings[i].timestamp = Date.now();
                    }
                    return;
                }
            }

            // Agregar nuevo
            this.warnings.push({
                id: id,
                message: message,
                level: level,
                timestamp: Date.now()
            });
        },

        /**
         * Eliminar warning
         */
        removeWarning: function(id) {
            for (var i = this.warnings.length - 1; i >= 0; i--) {
                if (this.warnings[i].id === id) {
                    this.warnings.splice(i, 1);
                    break;
                }
            }
        },

        /**
         * Limpiar todos los warnings
         */
        clearWarnings: function() {
            this.warnings = [];
        },

        /**
         * Obtener warnings por nivel
         */
        getWarningsByLevel: function(level) {
            return this.warnings.filter(function(warning) {
                return warning.level === level;
            });
        },

        // ============ HISTORIAL ============

        /**
         * Agregar valor al historial
         */
        addToHistory: function(metric, value) {
            if (!metricsHistory[metric]) {
                metricsHistory[metric] = [];
            }

            metricsHistory[metric].push({
                value: value,
                timestamp: Date.now()
            });

            // Limitar tamano
            if (metricsHistory[metric].length > HISTORY_MAX_LENGTH) {
                metricsHistory[metric].shift();
            }
        },

        /**
         * Obtener historial de una metrica
         */
        getHistory: function(metric) {
            return metricsHistory[metric] || [];
        },

        /**
         * Obtener datos para grafico
         */
        getChartData: function(metric, points) {
            points = points || 60;
            var history = this.getHistory(metric);
            var data = [];

            var start = Math.max(0, history.length - points);
            for (var i = start; i < history.length; i++) {
                data.push(history[i].value);
            }

            // Rellenar con ceros si no hay suficientes datos
            while (data.length < points) {
                data.unshift(0);
            }

            return data;
        },

        // ============ UI ============

        /**
         * Abrir panel de rendimiento
         */
        openPanel: function() {
            this.isPanelOpen = true;
        },

        /**
         * Cerrar panel de rendimiento
         */
        closePanel: function() {
            this.isPanelOpen = false;
        },

        /**
         * Toggle panel
         */
        togglePanel: function() {
            this.isPanelOpen = !this.isPanelOpen;
        },

        /**
         * Toggle colapso
         */
        toggleCollapse: function() {
            this.isCollapsed = !this.isCollapsed;
        },

        /**
         * Habilitar/deshabilitar monitoreo
         */
        setEnabled: function(enabled) {
            var wasEnabled = this.isEnabled;
            this.isEnabled = enabled;

            // Reanudar FPS monitor si se habilita después de estar deshabilitado
            if (enabled && !wasEnabled) {
                this.resumeFPSMonitor();
            }
        },

        // ============ UTILIDADES ============

        /**
         * Formatear bytes a unidad legible
         */
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';

            var units = ['B', 'KB', 'MB', 'GB'];
            var unitIndex = 0;
            var size = bytes;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }

            return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
        },

        /**
         * Formatear duracion de sesion
         */
        getSessionDuration: function() {
            var duration = Date.now() - this.sessionStats.startTime;
            var seconds = Math.floor(duration / 1000);
            var minutes = Math.floor(seconds / 60);
            var hours = Math.floor(minutes / 60);

            if (hours > 0) {
                return hours + 'h ' + (minutes % 60) + 'm';
            }
            if (minutes > 0) {
                return minutes + 'm ' + (seconds % 60) + 's';
            }
            return seconds + 's';
        },

        /**
         * Exportar metricas para debug
         */
        exportMetrics: function() {
            return {
                metrics: JSON.parse(JSON.stringify(this.metrics)),
                warnings: JSON.parse(JSON.stringify(this.warnings)),
                sessionStats: JSON.parse(JSON.stringify(this.sessionStats)),
                history: {
                    fps: this.getChartData('fps', 60),
                    renderTime: this.getChartData('renderTime', 60),
                    elementCount: this.getChartData('elementCount', 60),
                    memoryUsage: this.getChartData('memoryUsage', 60)
                },
                score: this.getPerformanceScore(),
                health: this.getHealthStatus(),
                exportedAt: new Date().toISOString()
            };
        },

        /**
         * Resetear estadisticas de sesion
         */
        resetSessionStats: function() {
            this.sessionStats = {
                startTime: Date.now(),
                totalSaves: 0,
                totalUndos: 0,
                totalRedos: 0,
                peakElementCount: this.metrics.elementCount,
                peakMemoryUsage: this.metrics.memoryUsage,
                peakRenderTime: this.metrics.renderTime
            };

            // Limpiar historial
            metricsHistory.fps = [];
            metricsHistory.renderTime = [];
            metricsHistory.elementCount = [];
            metricsHistory.memoryUsage = [];

            this.clearWarnings();
        },

        // Sugerencias de optimizacion
        optimizationSuggestions: []
    });
});

/**
 * Wrapper global para medicion de rendimiento
 */
window.VBPPerformanceMonitor = {
    /**
     * Medir tiempo de ejecucion de una funcion
     */
    measure: function(name, callback) {
        var store = Alpine.store && Alpine.store('vbpPerformance');
        var start = performance.now();
        var result = callback();
        var duration = performance.now() - start;

        if (store) {
            vbpLog.log('Performance [' + name + ']: ' + duration.toFixed(2) + 'ms');
        }

        return result;
    },

    /**
     * Medir tiempo de renderizado con callback
     */
    measureRender: function(callback) {
        var store = Alpine.store && Alpine.store('vbpPerformance');
        if (store) {
            store.measureRender(callback);
        } else {
            callback();
        }
    },

    /**
     * Obtener metricas actuales
     */
    getMetrics: function() {
        var store = Alpine.store && Alpine.store('vbpPerformance');
        return store ? store.metrics : null;
    },

    /**
     * Obtener puntuacion de rendimiento
     */
    getScore: function() {
        var store = Alpine.store && Alpine.store('vbpPerformance');
        return store ? store.getPerformanceScore() : 100;
    },

    /**
     * Verificar si hay warnings criticos
     */
    hasCriticalWarnings: function() {
        var store = Alpine.store && Alpine.store('vbpPerformance');
        if (!store) return false;
        return store.getWarningsByLevel('error').length > 0;
    }
};
