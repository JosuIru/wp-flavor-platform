/**
 * VBP Output Metrics Collector
 * Recolector de metricas de paginas generadas (Core Web Vitals)
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

/**
 * Clase para recolectar metricas de output (paginas renderizadas)
 */
class OutputMetricsCollector {
    constructor(options = {}) {
        this.options = {
            debug: options.debug || false,
            observeAll: options.observeAll || true,
            ...options
        };

        this.metrics = {
            ttfb: 0,
            fcp: 0,
            lcp: 0,
            cls: 0,
            fid: 0,
            inp: 0,
            domSize: 0,
            domDepth: 0,
            cssSize: 0,
            jsSize: 0,
            imageSize: 0,
            totalRequests: 0
        };

        this.observers = [];
        this.clsEntries = [];
        this.inpEntries = [];
    }

    /**
     * Iniciar recoleccion de metricas
     */
    start() {
        this.measureTTFB();
        this.measureFCP();
        this.measureLCP();
        this.measureCLS();
        this.measureFID();
        this.measureINP();
        this.measureDOMMetrics();
        this.measureResourceSizes();

        this.log('Output metrics collection started');
    }

    /**
     * Detener recoleccion
     */
    stop() {
        this.observers.forEach(observer => observer.disconnect());
        this.observers = [];
        this.log('Output metrics collection stopped');
    }

    /**
     * Medir Time to First Byte
     */
    measureTTFB() {
        if (typeof window === 'undefined') return;

        try {
            const navigationEntries = performance.getEntriesByType('navigation');
            if (navigationEntries.length > 0) {
                const navEntry = navigationEntries[0];
                this.metrics.ttfb = navEntry.responseStart - navEntry.requestStart;
            } else if (performance.timing) {
                this.metrics.ttfb = performance.timing.responseStart - performance.timing.requestStart;
            }
        } catch (error) {
            this.log('Error measuring TTFB:', error);
        }

        this.log('TTFB:', this.metrics.ttfb, 'ms');
    }

    /**
     * Medir First Contentful Paint
     */
    measureFCP() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') return;

        try {
            // Intentar obtener de entradas existentes
            const paintEntries = performance.getEntriesByType('paint');
            const fcpEntry = paintEntries.find(entry => entry.name === 'first-contentful-paint');
            if (fcpEntry) {
                this.metrics.fcp = fcpEntry.startTime;
                this.log('FCP (buffered):', this.metrics.fcp, 'ms');
                return;
            }

            // Observar si aun no ha ocurrido
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.name === 'first-contentful-paint') {
                        this.metrics.fcp = entry.startTime;
                        this.log('FCP:', this.metrics.fcp, 'ms');
                    }
                }
            });

            observer.observe({ entryTypes: ['paint'], buffered: true });
            this.observers.push(observer);
        } catch (error) {
            this.log('Error measuring FCP:', error);
        }
    }

    /**
     * Medir Largest Contentful Paint
     */
    measureLCP() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') return;

        try {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                // LCP es la ultima entrada
                const lastEntry = entries[entries.length - 1];
                if (lastEntry) {
                    this.metrics.lcp = lastEntry.startTime;
                    this.log('LCP:', this.metrics.lcp, 'ms', lastEntry.element?.tagName);
                }
            });

            observer.observe({ entryTypes: ['largest-contentful-paint'], buffered: true });
            this.observers.push(observer);
        } catch (error) {
            this.log('Error measuring LCP:', error);
        }
    }

    /**
     * Medir Cumulative Layout Shift
     */
    measureCLS() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') return;

        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    // Solo contar si no hubo input reciente
                    if (!entry.hadRecentInput) {
                        this.clsEntries.push(entry);
                    }
                }
                this.calculateCLS();
            });

            observer.observe({ entryTypes: ['layout-shift'], buffered: true });
            this.observers.push(observer);
        } catch (error) {
            this.log('Error measuring CLS:', error);
        }
    }

    /**
     * Calcular CLS con ventana de sesion
     */
    calculateCLS() {
        // Usar metodo de ventana de sesion (session window)
        let sessionValue = 0;
        let sessionEntries = [];
        let previousSessionEnd = 0;

        for (const entry of this.clsEntries) {
            // Si ha pasado mas de 1 segundo o la sesion dura mas de 5 segundos
            if (entry.startTime - previousSessionEnd > 1000 || entry.startTime - sessionEntries[0]?.startTime > 5000) {
                // Nueva sesion
                sessionValue = entry.value;
                sessionEntries = [entry];
            } else {
                sessionValue += entry.value;
                sessionEntries.push(entry);
            }

            if (sessionValue > this.metrics.cls) {
                this.metrics.cls = sessionValue;
            }

            previousSessionEnd = entry.startTime + entry.duration;
        }

        this.log('CLS:', this.metrics.cls.toFixed(4));
    }

    /**
     * Medir First Input Delay
     */
    measureFID() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') return;

        try {
            const observer = new PerformanceObserver((list) => {
                const firstEntry = list.getEntries()[0];
                if (firstEntry) {
                    this.metrics.fid = firstEntry.processingStart - firstEntry.startTime;
                    this.log('FID:', this.metrics.fid, 'ms');
                }
            });

            observer.observe({ entryTypes: ['first-input'], buffered: true });
            this.observers.push(observer);
        } catch (error) {
            this.log('Error measuring FID:', error);
        }
    }

    /**
     * Medir Interaction to Next Paint (INP)
     */
    measureINP() {
        if (typeof window === 'undefined' || typeof PerformanceObserver === 'undefined') return;

        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    // Solo interacciones de usuario
                    if (entry.interactionId) {
                        this.inpEntries.push(entry.duration);
                    }
                }
                this.calculateINP();
            });

            observer.observe({ entryTypes: ['event'], buffered: true, durationThreshold: 16 });
            this.observers.push(observer);
        } catch (error) {
            this.log('INP API not supported');
        }
    }

    /**
     * Calcular INP (percentil 98)
     */
    calculateINP() {
        if (this.inpEntries.length === 0) return;

        // Ordenar y obtener percentil 98
        const sorted = [...this.inpEntries].sort((a, b) => a - b);
        const percentile98Index = Math.ceil(sorted.length * 0.98) - 1;
        this.metrics.inp = sorted[percentile98Index] || 0;

        this.log('INP:', this.metrics.inp, 'ms');
    }

    /**
     * Medir metricas del DOM
     */
    measureDOMMetrics() {
        if (typeof document === 'undefined') return;

        // Tamano del DOM
        this.metrics.domSize = document.querySelectorAll('*').length;

        // Profundidad del DOM
        this.metrics.domDepth = this.calculateDOMDepth(document.documentElement);

        this.log('DOM Size:', this.metrics.domSize, 'nodes');
        this.log('DOM Depth:', this.metrics.domDepth, 'levels');
    }

    /**
     * Calcular profundidad maxima del DOM
     */
    calculateDOMDepth(element, depth = 0) {
        let maxDepth = depth;

        for (const child of element.children) {
            const childDepth = this.calculateDOMDepth(child, depth + 1);
            if (childDepth > maxDepth) {
                maxDepth = childDepth;
            }
        }

        return maxDepth;
    }

    /**
     * Medir tamano de recursos
     */
    measureResourceSizes() {
        if (typeof window === 'undefined') return;

        try {
            const resources = performance.getEntriesByType('resource');

            let cssSize = 0;
            let jsSize = 0;
            let imageSize = 0;

            for (const resource of resources) {
                const transferSize = resource.transferSize || 0;

                if (resource.initiatorType === 'link' && resource.name.includes('.css')) {
                    cssSize += transferSize;
                } else if (resource.initiatorType === 'script') {
                    jsSize += transferSize;
                } else if (resource.initiatorType === 'img' || resource.name.match(/\.(jpg|jpeg|png|gif|webp|svg|avif)$/i)) {
                    imageSize += transferSize;
                }
            }

            // Convertir a KB
            this.metrics.cssSize = cssSize / 1024;
            this.metrics.jsSize = jsSize / 1024;
            this.metrics.imageSize = imageSize / 1024;
            this.metrics.totalRequests = resources.length;

            this.log('CSS Size:', this.metrics.cssSize.toFixed(2), 'KB');
            this.log('JS Size:', this.metrics.jsSize.toFixed(2), 'KB');
            this.log('Image Size:', this.metrics.imageSize.toFixed(2), 'KB');
            this.log('Total Requests:', this.metrics.totalRequests);
        } catch (error) {
            this.log('Error measuring resource sizes:', error);
        }
    }

    /**
     * Obtener Core Web Vitals
     */
    getCoreWebVitals() {
        return {
            lcp: this.metrics.lcp,
            fid: this.metrics.fid,
            cls: this.metrics.cls,
            inp: this.metrics.inp
        };
    }

    /**
     * Obtener todas las metricas
     */
    getMetrics() {
        return { ...this.metrics };
    }

    /**
     * Obtener resumen de metricas
     */
    getSummary() {
        const webVitals = this.getCoreWebVitals();

        return {
            webVitals,
            performance: {
                ttfb: this.metrics.ttfb,
                fcp: this.metrics.fcp
            },
            size: {
                dom: this.metrics.domSize,
                css: this.metrics.cssSize,
                js: this.metrics.jsSize,
                images: this.metrics.imageSize,
                requests: this.metrics.totalRequests
            },
            scores: this.calculateScores()
        };
    }

    /**
     * Calcular puntuaciones
     */
    calculateScores() {
        const scores = {};

        // LCP Score
        if (this.metrics.lcp <= 2500) {
            scores.lcp = 'good';
        } else if (this.metrics.lcp <= 4000) {
            scores.lcp = 'needs-improvement';
        } else {
            scores.lcp = 'poor';
        }

        // FID Score
        if (this.metrics.fid <= 100) {
            scores.fid = 'good';
        } else if (this.metrics.fid <= 300) {
            scores.fid = 'needs-improvement';
        } else {
            scores.fid = 'poor';
        }

        // CLS Score
        if (this.metrics.cls <= 0.1) {
            scores.cls = 'good';
        } else if (this.metrics.cls <= 0.25) {
            scores.cls = 'needs-improvement';
        } else {
            scores.cls = 'poor';
        }

        // INP Score
        if (this.metrics.inp <= 200) {
            scores.inp = 'good';
        } else if (this.metrics.inp <= 500) {
            scores.inp = 'needs-improvement';
        } else {
            scores.inp = 'poor';
        }

        return scores;
    }

    /**
     * Exportar metricas para reporte
     */
    exportMetrics() {
        return {
            metrics: this.getMetrics(),
            coreWebVitals: this.getCoreWebVitals(),
            scores: this.calculateScores(),
            summary: this.getSummary(),
            exportedAt: new Date().toISOString()
        };
    }

    /**
     * Log condicional
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[OutputMetrics]', ...args);
        }
    }
}

/**
 * Utilidad para obtener Web Vitals de una pagina
 */
async function getWebVitals(timeoutMs = 10000) {
    const collector = new OutputMetricsCollector({ debug: false });
    collector.start();

    // Esperar a que se cargue la pagina y se estabilicen las metricas
    await new Promise(resolve => {
        if (document.readyState === 'complete') {
            setTimeout(resolve, 3000);
        } else {
            window.addEventListener('load', () => setTimeout(resolve, 3000));
        }
    });

    // Esperar un poco mas para CLS y LCP
    await new Promise(resolve => setTimeout(resolve, Math.min(timeoutMs - 3000, 5000)));

    collector.stop();
    return collector.exportMetrics();
}

// Exportar para Node.js y browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        OutputMetricsCollector,
        getWebVitals
    };
}

if (typeof window !== 'undefined') {
    window.VBPOutputMetrics = {
        OutputMetricsCollector,
        getWebVitals
    };
}
