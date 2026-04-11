/**
 * VBP Editor Metrics Collector
 * Recolector de metricas de rendimiento del editor
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

/**
 * Clase principal de recoleccion de metricas del editor
 */
class EditorMetricsCollector {
    constructor(options = {}) {
        this.options = {
            sampleInterval: options.sampleInterval || 1000,
            fpsMeasureDuration: options.fpsMeasureDuration || 1000,
            historySize: options.historySize || 120,
            debug: options.debug || false,
            ...options
        };

        this.metrics = {
            loadTime: 0,
            tti: 0,
            memoryInitial: 0,
            memoryPeak: 0,
            memoryCurrent: 0,
            fpsDrag: 0,
            fpsIdle: 0,
            renderTime: 0,
            saveTime: 0
        };

        this.history = {
            fps: [],
            memory: [],
            renderTime: []
        };

        this.state = {
            isCollecting: false,
            isDragging: false,
            startTime: 0,
            interactiveTime: 0
        };

        this.observers = [];
        this.rafCallbacks = [];
    }

    /**
     * Iniciar recoleccion de metricas
     */
    start() {
        if (this.state.isCollecting) {
            this.log('Ya esta recolectando metricas');
            return;
        }

        this.state.isCollecting = true;
        this.state.startTime = performance.now();

        // Medir tiempo de carga
        this.measureLoadTime();

        // Medir TTI
        this.measureTTI();

        // Medir memoria inicial
        this.measureMemory(true);

        // Iniciar monitoreo continuo
        this.startContinuousMonitoring();

        // Escuchar eventos de drag
        this.attachDragListeners();

        this.log('Recoleccion de metricas iniciada');
    }

    /**
     * Detener recoleccion
     */
    stop() {
        this.state.isCollecting = false;

        // Limpiar observers
        this.observers.forEach(observer => observer.disconnect());
        this.observers = [];

        // Cancelar RAF callbacks
        this.rafCallbacks.forEach(id => cancelAnimationFrame(id));
        this.rafCallbacks = [];

        // Quitar listeners
        this.detachDragListeners();

        this.log('Recoleccion de metricas detenida');
    }

    /**
     * Medir tiempo de carga
     */
    measureLoadTime() {
        if (typeof window === 'undefined') return;

        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            this.metrics.loadTime = timing.domContentLoadedEventEnd - timing.navigationStart;
        } else if (window.performance && window.performance.getEntriesByType) {
            const navigationEntries = window.performance.getEntriesByType('navigation');
            if (navigationEntries.length > 0) {
                this.metrics.loadTime = navigationEntries[0].domContentLoadedEventEnd;
            }
        }

        this.log('Load time:', this.metrics.loadTime, 'ms');
    }

    /**
     * Medir Time to Interactive
     */
    measureTTI() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') {
            return Promise.resolve(0);
        }

        return new Promise((resolve) => {
            let lastLongTaskEnd = 0;
            let firstInputTime = 0;

            // Observar Long Tasks
            try {
                const longTaskObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        lastLongTaskEnd = entry.startTime + entry.duration;
                    }
                });
                longTaskObserver.observe({ entryTypes: ['longtask'] });
                this.observers.push(longTaskObserver);
            } catch (error) {
                this.log('Long Tasks API no soportada');
            }

            // Observar First Input
            try {
                const firstInputObserver = new PerformanceObserver((list) => {
                    const firstEntry = list.getEntries()[0];
                    if (firstEntry) {
                        firstInputTime = firstEntry.startTime;
                        this.metrics.tti = Math.max(lastLongTaskEnd, firstInputTime);
                        this.log('TTI (first input):', this.metrics.tti, 'ms');
                        resolve(this.metrics.tti);
                    }
                });
                firstInputObserver.observe({ entryTypes: ['first-input'], buffered: true });
                this.observers.push(firstInputObserver);
            } catch (error) {
                this.log('First Input API no soportada');
            }

            // Fallback: usar load event + 5s sin long tasks
            setTimeout(() => {
                if (this.metrics.tti === 0) {
                    this.metrics.tti = lastLongTaskEnd || this.metrics.loadTime;
                    this.log('TTI (fallback):', this.metrics.tti, 'ms');
                    resolve(this.metrics.tti);
                }
            }, 10000);
        });
    }

    /**
     * Medir uso de memoria
     */
    measureMemory(isInitial = false) {
        if (typeof window === 'undefined') return null;

        if (window.performance && window.performance.memory) {
            const memoryMB = window.performance.memory.usedJSHeapSize / (1024 * 1024);

            if (isInitial) {
                this.metrics.memoryInitial = memoryMB;
                this.metrics.memoryPeak = memoryMB;
            } else {
                this.metrics.memoryCurrent = memoryMB;
                if (memoryMB > this.metrics.memoryPeak) {
                    this.metrics.memoryPeak = memoryMB;
                }
            }

            this.addToHistory('memory', memoryMB);
            return memoryMB;
        }

        return null;
    }

    /**
     * Medir FPS durante un periodo
     */
    measureFPS(duration = 1000) {
        return new Promise((resolve) => {
            if (typeof window === 'undefined' || typeof requestAnimationFrame === 'undefined') {
                resolve(60);
                return;
            }

            let frameCount = 0;
            const startTime = performance.now();

            const countFrame = () => {
                frameCount++;
                const elapsedTime = performance.now() - startTime;

                if (elapsedTime < duration) {
                    const rafId = requestAnimationFrame(countFrame);
                    this.rafCallbacks.push(rafId);
                } else {
                    const fps = Math.round((frameCount * 1000) / elapsedTime);
                    this.addToHistory('fps', fps);
                    resolve(fps);
                }
            };

            const rafId = requestAnimationFrame(countFrame);
            this.rafCallbacks.push(rafId);
        });
    }

    /**
     * Medir tiempo de render de una operacion
     */
    measureRender(renderFunction) {
        const startTime = performance.now();

        // Forzar layout/paint
        if (typeof document !== 'undefined') {
            document.body.offsetHeight; // Force reflow
        }

        const result = renderFunction();

        // Usar doble RAF para capturar paint
        return new Promise((resolve) => {
            const measureEnd = () => {
                const duration = performance.now() - startTime;
                this.metrics.renderTime = duration;
                this.addToHistory('renderTime', duration);
                resolve({ result, duration });
            };

            if (typeof requestAnimationFrame !== 'undefined') {
                requestAnimationFrame(() => {
                    requestAnimationFrame(measureEnd);
                });
            } else {
                measureEnd();
            }
        });
    }

    /**
     * Medir tiempo de guardado
     */
    async measureSave(saveFunction) {
        const startTime = performance.now();

        try {
            const result = await saveFunction();
            this.metrics.saveTime = performance.now() - startTime;
            this.log('Save time:', this.metrics.saveTime, 'ms');
            return result;
        } catch (error) {
            this.metrics.saveTime = performance.now() - startTime;
            throw error;
        }
    }

    /**
     * Iniciar monitoreo continuo
     */
    startContinuousMonitoring() {
        const monitor = async () => {
            if (!this.state.isCollecting) return;

            // Medir FPS
            const fps = await this.measureFPS(this.options.fpsMeasureDuration);

            if (this.state.isDragging) {
                this.metrics.fpsDrag = fps;
            } else {
                this.metrics.fpsIdle = fps;
            }

            // Medir memoria
            this.measureMemory();

            // Programar siguiente medicion
            setTimeout(monitor, this.options.sampleInterval);
        };

        monitor();
    }

    /**
     * Adjuntar listeners de drag
     */
    attachDragListeners() {
        if (typeof document === 'undefined') return;

        this._onDragStart = () => {
            this.state.isDragging = true;
            this.log('Drag started');
        };

        this._onDragEnd = () => {
            this.state.isDragging = false;
            this.log('Drag ended');
        };

        // Eventos nativos
        document.addEventListener('dragstart', this._onDragStart);
        document.addEventListener('dragend', this._onDragEnd);

        // Eventos de mouse (para drag custom)
        document.addEventListener('mousedown', (event) => {
            if (event.target.closest('[draggable]') ||
                event.target.closest('.vbp-draggable') ||
                event.target.closest('[data-vbp-element]')) {
                this._onDragStart();
            }
        });

        document.addEventListener('mouseup', this._onDragEnd);

        // Eventos personalizados de VBP
        document.addEventListener('vbp:drag:start', this._onDragStart);
        document.addEventListener('vbp:drag:end', this._onDragEnd);
    }

    /**
     * Quitar listeners de drag
     */
    detachDragListeners() {
        if (typeof document === 'undefined') return;

        document.removeEventListener('dragstart', this._onDragStart);
        document.removeEventListener('dragend', this._onDragEnd);
        document.removeEventListener('vbp:drag:start', this._onDragStart);
        document.removeEventListener('vbp:drag:end', this._onDragEnd);
    }

    /**
     * Agregar valor al historial
     */
    addToHistory(metric, value) {
        if (!this.history[metric]) {
            this.history[metric] = [];
        }

        this.history[metric].push({
            value,
            timestamp: Date.now()
        });

        // Limitar tamano
        while (this.history[metric].length > this.options.historySize) {
            this.history[metric].shift();
        }
    }

    /**
     * Obtener metricas actuales
     */
    getMetrics() {
        return { ...this.metrics };
    }

    /**
     * Obtener historial
     */
    getHistory(metric) {
        return this.history[metric] || [];
    }

    /**
     * Obtener promedio de historial
     */
    getHistoryAverage(metric) {
        const historyData = this.history[metric];
        if (!historyData || historyData.length === 0) return 0;

        const sum = historyData.reduce((accumulator, item) => accumulator + item.value, 0);
        return sum / historyData.length;
    }

    /**
     * Exportar todas las metricas
     */
    exportMetrics() {
        return {
            metrics: this.getMetrics(),
            history: {
                fps: this.history.fps.map(item => item.value),
                memory: this.history.memory.map(item => item.value),
                renderTime: this.history.renderTime.map(item => item.value)
            },
            averages: {
                fps: this.getHistoryAverage('fps'),
                memory: this.getHistoryAverage('memory'),
                renderTime: this.getHistoryAverage('renderTime')
            },
            state: {
                isCollecting: this.state.isCollecting,
                sessionDuration: performance.now() - this.state.startTime
            },
            exportedAt: new Date().toISOString()
        };
    }

    /**
     * Reiniciar metricas
     */
    reset() {
        this.metrics = {
            loadTime: 0,
            tti: 0,
            memoryInitial: 0,
            memoryPeak: 0,
            memoryCurrent: 0,
            fpsDrag: 0,
            fpsIdle: 0,
            renderTime: 0,
            saveTime: 0
        };

        this.history = {
            fps: [],
            memory: [],
            renderTime: []
        };

        this.state.startTime = performance.now();
    }

    /**
     * Log condicional
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[EditorMetrics]', ...args);
        }
    }
}

/**
 * Utilidad para medir tiempo de ejecucion
 */
function measureExecution(label, callback) {
    const startTime = performance.now();
    const result = callback();
    const duration = performance.now() - startTime;
    console.log(`[Performance] ${label}: ${duration.toFixed(2)}ms`);
    return { result, duration };
}

/**
 * Utilidad para medir ejecucion asincrona
 */
async function measureAsyncExecution(label, asyncFunction) {
    const startTime = performance.now();
    const result = await asyncFunction();
    const duration = performance.now() - startTime;
    console.log(`[Performance] ${label}: ${duration.toFixed(2)}ms`);
    return { result, duration };
}

// Exportar para Node.js y browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EditorMetricsCollector,
        measureExecution,
        measureAsyncExecution
    };
}

if (typeof window !== 'undefined') {
    window.VBPEditorMetrics = {
        EditorMetricsCollector,
        measureExecution,
        measureAsyncExecution
    };
}
