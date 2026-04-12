/**
 * VBP Scalability Test
 * Test de escalabilidad del editor con diferentes cantidades de elementos
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

/**
 * Clase de test de escalabilidad
 */
class ScalabilityTest {
    constructor(options = {}) {
        this.options = {
            elementCounts: options.elementCounts || [10, 50, 100, 500],
            warmupRuns: options.warmupRuns || 2,
            testRuns: options.testRuns || 3,
            delay: options.delay || 1000,
            debug: options.debug || false,
            ...options
        };

        this.results = {};
        this.isRunning = false;
    }

    /**
     * Ejecutar test completo de escalabilidad
     */
    async run(targetElement = null) {
        if (this.isRunning) {
            throw new Error('Test ya en ejecucion');
        }

        this.isRunning = true;
        this.results = {};

        this.log('Iniciando test de escalabilidad...');

        try {
            for (const elementCount of this.options.elementCounts) {
                this.log(`Testing con ${elementCount} elementos...`);
                this.results[`elements${elementCount}`] = await this.testWithElements(elementCount, targetElement);
                await this.delay(this.options.delay);
            }

            this.log('Test completado');
            return this.generateReport();
        } finally {
            this.isRunning = false;
        }
    }

    /**
     * Test con N elementos
     */
    async testWithElements(elementCount, targetElement) {
        const results = {
            elementCount,
            loadTime: [],
            renderTime: [],
            fps: [],
            memory: [],
            domNodes: []
        };

        // Crear contenedor de test si no se proporciona uno
        const container = targetElement || this.createTestContainer();

        try {
            // Warmup
            for (let i = 0; i < this.options.warmupRuns; i++) {
                await this.runSingleTest(container, elementCount);
                this.clearContainer(container);
                await this.delay(100);
            }

            // Tests reales
            for (let i = 0; i < this.options.testRuns; i++) {
                const testResult = await this.runSingleTest(container, elementCount);
                results.loadTime.push(testResult.loadTime);
                results.renderTime.push(testResult.renderTime);
                results.fps.push(testResult.fps);
                results.memory.push(testResult.memory);
                results.domNodes.push(testResult.domNodes);

                this.clearContainer(container);
                await this.delay(100);
            }

            // Calcular promedios
            return {
                elementCount,
                loadTime: this.average(results.loadTime),
                renderTime: this.average(results.renderTime),
                fps: this.average(results.fps),
                memory: this.average(results.memory),
                domNodes: this.average(results.domNodes),
                raw: results
            };
        } finally {
            if (!targetElement) {
                this.removeTestContainer(container);
            }
        }
    }

    /**
     * Ejecutar un test individual
     */
    async runSingleTest(container, elementCount) {
        const startTime = performance.now();

        // Crear elementos
        const fragment = document.createDocumentFragment();
        for (let i = 0; i < elementCount; i++) {
            const element = this.createTestElement(i);
            fragment.appendChild(element);
        }

        // Medir tiempo de insercion
        container.appendChild(fragment);
        const loadTime = performance.now() - startTime;

        // Forzar layout y medir render
        const renderStart = performance.now();
        container.offsetHeight; // Force reflow
        const renderTime = performance.now() - renderStart;

        // Medir FPS
        const fps = await this.measureFPS();

        // Medir memoria
        const memory = this.measureMemory();

        // Contar nodos
        const domNodes = container.querySelectorAll('*').length;

        return {
            loadTime,
            renderTime,
            fps,
            memory,
            domNodes
        };
    }

    /**
     * Crear elemento de test
     */
    createTestElement(index) {
        const element = document.createElement('div');
        element.className = 'vbp-test-element';
        element.setAttribute('data-test-index', index);
        element.innerHTML = `
            <div class="test-header">
                <h3>Elemento ${index + 1}</h3>
                <button type="button">Accion</button>
            </div>
            <div class="test-content">
                <p>Contenido de ejemplo para el elemento ${index + 1}</p>
                <ul>
                    <li>Item 1</li>
                    <li>Item 2</li>
                </ul>
            </div>
            <div class="test-footer">
                <span>Footer ${index + 1}</span>
            </div>
        `;
        return element;
    }

    /**
     * Crear contenedor de test
     */
    createTestContainer() {
        const container = document.createElement('div');
        container.id = 'vbp-scalability-test-container';
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: white;
            z-index: 99999;
            display: none;
        `;
        document.body.appendChild(container);
        return container;
    }

    /**
     * Limpiar contenedor
     */
    clearContainer(container) {
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
    }

    /**
     * Remover contenedor de test
     */
    removeTestContainer(container) {
        if (container && container.parentNode) {
            container.parentNode.removeChild(container);
        }
    }

    /**
     * Medir FPS
     */
    measureFPS(duration = 500) {
        return new Promise((resolve) => {
            let frameCount = 0;
            const startTime = performance.now();

            const countFrame = () => {
                frameCount++;
                if (performance.now() - startTime < duration) {
                    requestAnimationFrame(countFrame);
                } else {
                    resolve(Math.round((frameCount * 1000) / (performance.now() - startTime)));
                }
            };

            requestAnimationFrame(countFrame);
        });
    }

    /**
     * Medir memoria
     */
    measureMemory() {
        if (window.performance && window.performance.memory) {
            return window.performance.memory.usedJSHeapSize / (1024 * 1024);
        }
        return 0;
    }

    /**
     * Calcular promedio
     */
    average(values) {
        if (!values || values.length === 0) return 0;
        return values.reduce((sum, val) => sum + val, 0) / values.length;
    }

    /**
     * Delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Generar reporte de resultados
     */
    generateReport() {
        const report = {
            timestamp: new Date().toISOString(),
            config: {
                elementCounts: this.options.elementCounts,
                testRuns: this.options.testRuns
            },
            results: this.results,
            analysis: this.analyzeResults()
        };

        return report;
    }

    /**
     * Analizar resultados
     */
    analyzeResults() {
        const analysis = {
            scalabilityFactor: {},
            bottlenecks: [],
            recommendations: []
        };

        const elementCountsSorted = this.options.elementCounts.slice().sort((a, b) => a - b);

        // Calcular factor de escalabilidad (comparando con el baseline)
        if (elementCountsSorted.length >= 2) {
            const baselineCount = elementCountsSorted[0];
            const baselineResult = this.results[`elements${baselineCount}`];

            for (let i = 1; i < elementCountsSorted.length; i++) {
                const currentCount = elementCountsSorted[i];
                const currentResult = this.results[`elements${currentCount}`];
                const factor = currentCount / baselineCount;

                analysis.scalabilityFactor[`${baselineCount}_to_${currentCount}`] = {
                    elementFactor: factor,
                    loadTimeFactor: currentResult.loadTime / baselineResult.loadTime,
                    renderTimeFactor: currentResult.renderTime / baselineResult.renderTime,
                    memoryFactor: currentResult.memory / baselineResult.memory
                };
            }
        }

        // Detectar cuellos de botella
        for (const [key, result] of Object.entries(this.results)) {
            if (result.fps < 30) {
                analysis.bottlenecks.push({
                    type: 'fps',
                    elements: result.elementCount,
                    value: result.fps,
                    severity: result.fps < 15 ? 'critical' : 'warning'
                });
            }

            if (result.renderTime > 100) {
                analysis.bottlenecks.push({
                    type: 'render',
                    elements: result.elementCount,
                    value: result.renderTime,
                    severity: result.renderTime > 250 ? 'critical' : 'warning'
                });
            }

            if (result.memory > 200) {
                analysis.bottlenecks.push({
                    type: 'memory',
                    elements: result.elementCount,
                    value: result.memory,
                    severity: result.memory > 400 ? 'critical' : 'warning'
                });
            }
        }

        // Generar recomendaciones
        if (analysis.bottlenecks.some(b => b.type === 'fps')) {
            analysis.recommendations.push({
                priority: 'high',
                title: 'Optimizar FPS',
                description: 'Implementar virtualizacion para listas largas, reducir re-renders'
            });
        }

        if (analysis.bottlenecks.some(b => b.type === 'render')) {
            analysis.recommendations.push({
                priority: 'high',
                title: 'Optimizar tiempo de render',
                description: 'Usar requestAnimationFrame, batch DOM updates, evitar forced reflow'
            });
        }

        if (analysis.bottlenecks.some(b => b.type === 'memory')) {
            analysis.recommendations.push({
                priority: 'medium',
                title: 'Reducir uso de memoria',
                description: 'Implementar pooling de objetos, limpiar referencias no usadas'
            });
        }

        // Analizar escalabilidad
        for (const [range, factors] of Object.entries(analysis.scalabilityFactor)) {
            // Si el tiempo crece mas que linealmente es una senal de problema
            if (factors.loadTimeFactor > factors.elementFactor * 1.5) {
                analysis.recommendations.push({
                    priority: 'medium',
                    title: `Escalabilidad sub-lineal en rango ${range}`,
                    description: `El tiempo de carga crece ${(factors.loadTimeFactor / factors.elementFactor).toFixed(2)}x mas rapido que el numero de elementos`
                });
            }
        }

        return analysis;
    }

    /**
     * Generar markdown del reporte
     */
    toMarkdown() {
        const report = this.generateReport();
        let markdown = `# Test de Escalabilidad VBP

> Ejecutado: ${report.timestamp}
> Configuracion: ${report.config.testRuns} ejecuciones por test

## Resultados

| Elementos | Tiempo Carga | Tiempo Render | FPS | Memoria | Nodos DOM |
|-----------|--------------|---------------|-----|---------|-----------|
`;

        for (const count of this.options.elementCounts) {
            const result = report.results[`elements${count}`];
            if (result) {
                markdown += `| ${count} | ${result.loadTime.toFixed(2)}ms | ${result.renderTime.toFixed(2)}ms | ${result.fps.toFixed(0)} | ${result.memory.toFixed(1)}MB | ${result.domNodes} |\n`;
            }
        }

        // Factores de escalabilidad
        if (Object.keys(report.analysis.scalabilityFactor).length > 0) {
            markdown += `\n## Factores de Escalabilidad\n\n`;
            markdown += `| Rango | Factor Elementos | Factor Tiempo | Factor Memoria |\n`;
            markdown += `|-------|------------------|---------------|----------------|\n`;

            for (const [range, factors] of Object.entries(report.analysis.scalabilityFactor)) {
                markdown += `| ${range} | ${factors.elementFactor.toFixed(1)}x | ${factors.loadTimeFactor.toFixed(2)}x | ${factors.memoryFactor.toFixed(2)}x |\n`;
            }
        }

        // Cuellos de botella
        if (report.analysis.bottlenecks.length > 0) {
            markdown += `\n## Cuellos de Botella Detectados\n\n`;
            for (const bottleneck of report.analysis.bottlenecks) {
                const icon = bottleneck.severity === 'critical' ? '\u{1F534}' : '\u{1F7E1}';
                markdown += `- ${icon} **${bottleneck.type}** con ${bottleneck.elements} elementos: ${bottleneck.value.toFixed(2)}\n`;
            }
        }

        // Recomendaciones
        if (report.analysis.recommendations.length > 0) {
            markdown += `\n## Recomendaciones\n\n`;
            for (const rec of report.analysis.recommendations) {
                const icon = rec.priority === 'high' ? '\u{1F534}' : '\u{1F7E1}';
                markdown += `### ${icon} ${rec.title}\n\n${rec.description}\n\n`;
            }
        }

        return markdown;
    }

    /**
     * Log condicional
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[ScalabilityTest]', ...args);
        }
    }
}

/**
 * Ejecutar test rapido de escalabilidad
 */
async function quickScalabilityTest() {
    const test = new ScalabilityTest({
        elementCounts: [10, 50, 100],
        testRuns: 2,
        debug: true
    });

    const report = await test.run();
    console.log(test.toMarkdown());
    return report;
}

// Exportar para Node.js y browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ScalabilityTest,
        quickScalabilityTest
    };
}

if (typeof window !== 'undefined') {
    window.VBPScalabilityTest = {
        ScalabilityTest,
        quickScalabilityTest
    };
}
