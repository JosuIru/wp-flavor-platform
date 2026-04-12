/**
 * VBP Limits Tests
 * Pruebas para encontrar limites del sistema
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

/**
 * Utilidades para tests de limites
 */
const LimitsTestUtils = {
    /**
     * Medir memoria disponible
     */
    getMemoryUsage() {
        if (typeof performance !== 'undefined' && performance.memory) {
            return {
                usedJSHeapSize: performance.memory.usedJSHeapSize,
                totalJSHeapSize: performance.memory.totalJSHeapSize,
                jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
                usedMB: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
                limitMB: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
            };
        }
        return { usedMB: 0, limitMB: 0, available: false };
    },

    /**
     * Medir FPS
     */
    async measureFPS(durationMs = 1000) {
        return new Promise((resolve) => {
            let frameCount = 0;
            const startTime = performance.now();

            function countFrame() {
                frameCount++;
                if (performance.now() - startTime < durationMs) {
                    requestAnimationFrame(countFrame);
                } else {
                    const elapsedSeconds = (performance.now() - startTime) / 1000;
                    resolve(Math.round(frameCount / elapsedSeconds));
                }
            }

            if (typeof requestAnimationFrame !== 'undefined') {
                requestAnimationFrame(countFrame);
            } else {
                // Fallback para Node.js
                resolve(60);
            }
        });
    },

    /**
     * Generar ID unico
     */
    generateId() {
        return `id-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    },

    /**
     * Esperar
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Crear elemento aleatorio
     */
    createRandomElement(size = 'small') {
        const sizes = {
            small: 100,
            medium: 1000,
            large: 10000
        };

        const contentSize = sizes[size] || sizes.small;

        return {
            id: this.generateId(),
            type: 'element',
            props: {
                x: Math.random() * 1000,
                y: Math.random() * 2000,
                width: 50 + Math.random() * 200,
                height: 30 + Math.random() * 100,
                content: 'x'.repeat(contentSize)
            },
            children: []
        };
    }
};

/**
 * Mock Store para tests de limites
 */
class LimitsMockStore {
    constructor() {
        this.elements = [];
        this.history = [];
        this.historyIndex = -1;
        this.symbols = new Map();
        this.maxHistorySize = 1000;
    }

    addElement(element) {
        this.elements.push(element);
        this.recordHistory({ type: 'add', element });
        return element;
    }

    recordHistory(action) {
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }
        this.history.push(action);
        if (this.history.length > this.maxHistorySize) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }
    }

    clear() {
        this.elements = [];
        this.history = [];
        this.historyIndex = -1;
        this.symbols.clear();
    }

    getDataSize() {
        return JSON.stringify(this.elements).length;
    }
}

/**
 * Tests de limites del sistema
 */
const LIMITS_TESTS = {
    /**
     * Encontrar maximo numero de elementos
     */
    'max-elements': {
        name: 'Maximo numero de elementos',
        description: 'Encontrar el limite de elementos antes de degradacion',
        category: 'elements',

        async run(options = {}) {
            const targetFPS = options.targetFPS || 30;
            const maxIterations = options.maxIterations || 5000;
            const batchSize = options.batchSize || 50;

            const store = new LimitsMockStore();
            const measurements = [];
            let currentFPS = 60;
            let elementCount = 0;
            let limitReached = false;

            while (!limitReached && elementCount < maxIterations) {
                // Agregar batch de elementos
                for (let i = 0; i < batchSize; i++) {
                    store.addElement(LimitsTestUtils.createRandomElement('small'));
                    elementCount++;
                }

                // Medir FPS
                currentFPS = await LimitsTestUtils.measureFPS(500);

                measurements.push({
                    elementCount,
                    fps: currentFPS,
                    memoryMB: LimitsTestUtils.getMemoryUsage().usedMB
                });

                // Verificar si hemos alcanzado el limite
                if (currentFPS < targetFPS) {
                    limitReached = true;
                }

                // Verificar memoria
                const memory = LimitsTestUtils.getMemoryUsage();
                if (memory.usedMB > memory.limitMB * 0.9) {
                    limitReached = true;
                }
            }

            // Encontrar el punto optimo (ultimo punto con FPS > target)
            const optimalPoint = measurements
                .filter(m => m.fps >= targetFPS)
                .pop() || measurements[0];

            const metrics = {
                maxElementsTested: elementCount,
                finalFPS: currentFPS,
                targetFPS,
                limitReached,
                optimalElementCount: optimalPoint ? optimalPoint.elementCount : 0,
                optimalFPS: optimalPoint ? optimalPoint.fps : 0,
                finalMemoryMB: LimitsTestUtils.getMemoryUsage().usedMB,
                measurementCount: measurements.length,
                degradationCurve: measurements.slice(-10).map(m => ({
                    elements: m.elementCount,
                    fps: m.fps
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.elementCount > 100,
                metrics,
                recommendation: optimalPoint
                    ? `Limite recomendado: ${optimalPoint.elementCount} elementos para mantener ${targetFPS}+ FPS`
                    : 'No se pudo determinar limite'
            };
        }
    },

    /**
     * Encontrar maximo nivel de anidamiento
     */
    'max-nesting': {
        name: 'Maximo nivel de anidamiento',
        description: 'Encontrar el limite de anidamiento de elementos',
        category: 'structure',

        async run(options = {}) {
            const maxDepthToTest = options.maxDepth || 200;
            const store = new LimitsMockStore();

            const measurements = [];
            let currentDepth = 0;
            let limitReached = false;
            let lastError = null;

            // Funcion para crear estructura anidada
            const createNestedStructure = (depth) => {
                const root = {
                    id: LimitsTestUtils.generateId(),
                    type: 'container',
                    props: { depth: 0 },
                    children: []
                };

                let current = root;
                for (let i = 1; i <= depth; i++) {
                    const child = {
                        id: LimitsTestUtils.generateId(),
                        type: 'container',
                        props: { depth: i },
                        children: []
                    };
                    current.children.push(child);
                    current = child;
                }

                return root;
            };

            // Probar diferentes niveles de anidamiento
            for (let depth = 10; depth <= maxDepthToTest && !limitReached; depth += 10) {
                try {
                    const startTime = performance.now();

                    // Crear estructura
                    const nested = createNestedStructure(depth);

                    // Medir tiempo de serializacion
                    const serializeStart = performance.now();
                    const serialized = JSON.stringify(nested);
                    const serializeTime = performance.now() - serializeStart;

                    // Medir tiempo de deserializacion
                    const deserializeStart = performance.now();
                    JSON.parse(serialized);
                    const deserializeTime = performance.now() - deserializeStart;

                    // Medir acceso a elemento mas profundo
                    const accessStart = performance.now();
                    let current = nested;
                    let accessDepth = 0;
                    while (current.children && current.children.length > 0) {
                        current = current.children[0];
                        accessDepth++;
                    }
                    const accessTime = performance.now() - accessStart;

                    const totalTime = performance.now() - startTime;

                    measurements.push({
                        depth,
                        accessDepth,
                        serializeTimeMs: serializeTime,
                        deserializeTimeMs: deserializeTime,
                        accessTimeMs: accessTime,
                        totalTimeMs: totalTime,
                        sizeKB: serialized.length / 1024
                    });

                    currentDepth = depth;

                    // Verificar si el rendimiento se degrada demasiado
                    if (serializeTime > 1000 || accessTime > 100) {
                        limitReached = true;
                    }
                } catch (error) {
                    lastError = error.message;
                    limitReached = true;
                }
            }

            // Encontrar punto optimo
            const optimalPoint = measurements
                .filter(m => m.serializeTimeMs < 100 && m.accessTimeMs < 10)
                .pop() || measurements[0];

            const metrics = {
                maxDepthTested: currentDepth,
                limitReached,
                lastError,
                optimalDepth: optimalPoint ? optimalPoint.depth : 0,
                measurementCount: measurements.length,
                performanceCurve: measurements.map(m => ({
                    depth: m.depth,
                    serializeMs: Math.round(m.serializeTimeMs),
                    accessMs: Math.round(m.accessTimeMs * 100) / 100
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.depth >= 20,
                metrics,
                recommendation: `Limite recomendado: ${optimalPoint ? optimalPoint.depth : 10} niveles de anidamiento`
            };
        }
    },

    /**
     * Encontrar maximo numero de symbols
     */
    'max-symbols': {
        name: 'Maximo numero de Symbols',
        description: 'Encontrar el limite de symbols y sus instancias',
        category: 'symbols',

        async run(options = {}) {
            const maxSymbols = options.maxSymbols || 100;
            const instancesPerSymbol = options.instancesPerSymbol || 20;

            const store = new LimitsMockStore();
            const measurements = [];

            // Crear symbols y medir rendimiento
            for (let symbolCount = 5; symbolCount <= maxSymbols; symbolCount += 5) {
                const startTime = performance.now();
                store.clear();

                // Crear symbols
                for (let i = 0; i < symbolCount; i++) {
                    const symbolId = `symbol-${i}`;
                    store.symbols.set(symbolId, {
                        id: symbolId,
                        sourceElement: LimitsTestUtils.createRandomElement('medium'),
                        instances: []
                    });

                    // Crear instancias
                    for (let j = 0; j < instancesPerSymbol; j++) {
                        const instance = {
                            id: LimitsTestUtils.generateId(),
                            symbolId,
                            overrides: {}
                        };
                        store.symbols.get(symbolId).instances.push(instance);
                        store.elements.push({
                            ...store.symbols.get(symbolId).sourceElement,
                            id: instance.id,
                            isSymbolInstance: true,
                            symbolId
                        });
                    }
                }

                const createTime = performance.now() - startTime;

                // Medir tiempo de sincronizacion de symbol
                const syncStart = performance.now();
                const randomSymbol = store.symbols.get(`symbol-${Math.floor(Math.random() * symbolCount)}`);
                if (randomSymbol) {
                    // Simular actualizacion de symbol
                    randomSymbol.sourceElement.props.updated = true;
                    // Propagar a instancias
                    randomSymbol.instances.forEach(instance => {
                        const element = store.elements.find(el => el.id === instance.id);
                        if (element) {
                            element.props.updated = true;
                        }
                    });
                }
                const syncTime = performance.now() - syncStart;

                measurements.push({
                    symbolCount,
                    totalInstances: symbolCount * instancesPerSymbol,
                    totalElements: store.elements.length,
                    createTimeMs: createTime,
                    syncTimeMs: syncTime,
                    memoryMB: LimitsTestUtils.getMemoryUsage().usedMB
                });
            }

            // Encontrar punto optimo
            const optimalPoint = measurements
                .filter(m => m.syncTimeMs < 100 && m.createTimeMs < 5000)
                .pop() || measurements[0];

            const metrics = {
                maxSymbolsTested: maxSymbols,
                instancesPerSymbol,
                optimalSymbolCount: optimalPoint ? optimalPoint.symbolCount : 0,
                optimalTotalInstances: optimalPoint ? optimalPoint.totalInstances : 0,
                performanceCurve: measurements.map(m => ({
                    symbols: m.symbolCount,
                    instances: m.totalInstances,
                    syncMs: Math.round(m.syncTimeMs)
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.symbolCount >= 20,
                metrics,
                recommendation: `Limite recomendado: ${optimalPoint ? optimalPoint.symbolCount : 10} symbols con ${instancesPerSymbol} instancias cada uno`
            };
        }
    },

    /**
     * Encontrar maximo tamano de historial
     */
    'max-history': {
        name: 'Maximo tamano de historial',
        description: 'Encontrar el limite de operaciones en historial',
        category: 'history',

        async run(options = {}) {
            const maxHistory = options.maxHistory || 1000;
            const batchSize = options.batchSize || 50;

            const store = new LimitsMockStore();
            store.maxHistorySize = Infinity; // Sin limite artificial

            const measurements = [];
            let currentHistorySize = 0;

            // Agregar operaciones al historial
            while (currentHistorySize < maxHistory) {
                const startTime = performance.now();

                // Agregar batch de operaciones
                for (let i = 0; i < batchSize; i++) {
                    const element = LimitsTestUtils.createRandomElement('small');
                    store.addElement(element);
                    currentHistorySize++;
                }

                const addTime = performance.now() - startTime;

                // Medir tiempo de undo multiple
                const undoStart = performance.now();
                const undoCount = Math.min(10, store.historyIndex);
                for (let i = 0; i < undoCount; i++) {
                    store.historyIndex--;
                }
                const undoTime = performance.now() - undoStart;

                // Restaurar
                for (let i = 0; i < undoCount; i++) {
                    store.historyIndex++;
                }

                // Medir memoria del historial
                const historySize = JSON.stringify(store.history).length;

                measurements.push({
                    historySize: currentHistorySize,
                    addTimeMs: addTime,
                    undoTimeMs: undoTime,
                    historySizeKB: historySize / 1024,
                    memoryMB: LimitsTestUtils.getMemoryUsage().usedMB
                });
            }

            // Encontrar punto optimo
            const optimalPoint = measurements
                .filter(m => m.undoTimeMs < 10 && m.historySizeKB < 10240) // < 10MB
                .pop() || measurements[0];

            const metrics = {
                maxHistoryTested: maxHistory,
                optimalHistorySize: optimalPoint ? optimalPoint.historySize : 0,
                finalHistorySizeKB: measurements[measurements.length - 1].historySizeKB,
                performanceCurve: measurements.filter((_, i) => i % 5 === 0).map(m => ({
                    history: m.historySize,
                    undoMs: Math.round(m.undoTimeMs * 100) / 100,
                    sizeKB: Math.round(m.historySizeKB)
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.historySize >= 100,
                metrics,
                recommendation: `Limite recomendado: ${optimalPoint ? optimalPoint.historySize : 50} operaciones en historial`
            };
        }
    },

    /**
     * Encontrar maximo tamano de pagina
     */
    'max-page-size': {
        name: 'Maximo tamano de pagina',
        description: 'Encontrar el limite de tamano de datos de pagina',
        category: 'data',

        async run(options = {}) {
            const targetSizeMB = options.targetSizeMB || 20;
            const store = new LimitsMockStore();
            const measurements = [];

            let currentSizeMB = 0;
            let elementCount = 0;

            while (currentSizeMB < targetSizeMB) {
                // Agregar elementos grandes
                for (let i = 0; i < 10; i++) {
                    store.addElement(LimitsTestUtils.createRandomElement('large'));
                    elementCount++;
                }

                currentSizeMB = store.getDataSize() / 1024 / 1024;

                // Medir rendimiento
                const serializeStart = performance.now();
                const serialized = JSON.stringify(store.elements);
                const serializeTime = performance.now() - serializeStart;

                const deserializeStart = performance.now();
                JSON.parse(serialized);
                const deserializeTime = performance.now() - deserializeStart;

                measurements.push({
                    sizeMB: currentSizeMB,
                    elementCount,
                    serializeTimeMs: serializeTime,
                    deserializeTimeMs: deserializeTime,
                    memoryMB: LimitsTestUtils.getMemoryUsage().usedMB
                });

                // Verificar si el rendimiento se degrada demasiado
                if (serializeTime > 5000 || deserializeTime > 5000) {
                    break;
                }
            }

            // Encontrar punto optimo (guardado en menos de 1 segundo)
            const optimalPoint = measurements
                .filter(m => m.serializeTimeMs < 1000)
                .pop() || measurements[0];

            const metrics = {
                maxSizeTestedMB: Math.round(currentSizeMB * 100) / 100,
                optimalSizeMB: optimalPoint ? Math.round(optimalPoint.sizeMB * 100) / 100 : 0,
                optimalElementCount: optimalPoint ? optimalPoint.elementCount : 0,
                performanceCurve: measurements.map(m => ({
                    sizeMB: Math.round(m.sizeMB * 100) / 100,
                    elements: m.elementCount,
                    serializeMs: Math.round(m.serializeTimeMs)
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.sizeMB >= 1,
                metrics,
                recommendation: `Limite recomendado: ${optimalPoint ? Math.round(optimalPoint.sizeMB * 100) / 100 : 0.5}MB de tamano de pagina`
            };
        }
    },

    /**
     * Encontrar limite de operaciones por segundo
     */
    'max-operations-per-second': {
        name: 'Maximo operaciones por segundo',
        description: 'Encontrar el limite de operaciones simultaneas',
        category: 'throughput',

        async run(options = {}) {
            const testDurationMs = options.testDurationMs || 5000;
            const store = new LimitsMockStore();

            // Preparar elementos
            for (let i = 0; i < 100; i++) {
                store.addElement(LimitsTestUtils.createRandomElement('small'));
            }

            const measurements = [];
            const startTime = performance.now();
            let operationCount = 0;
            let lastMeasurement = startTime;

            // Ejecutar operaciones lo mas rapido posible
            while (performance.now() - startTime < testDurationMs) {
                const operationType = Math.random();

                if (operationType < 0.33) {
                    // Add
                    store.addElement(LimitsTestUtils.createRandomElement('small'));
                } else if (operationType < 0.66 && store.elements.length > 0) {
                    // Update
                    const randomIndex = Math.floor(Math.random() * store.elements.length);
                    store.elements[randomIndex].props.updated = Date.now();
                } else if (store.elements.length > 50) {
                    // Remove
                    store.elements.pop();
                }

                operationCount++;

                // Medir cada 1000 operaciones
                if (operationCount % 1000 === 0) {
                    const elapsed = performance.now() - lastMeasurement;
                    measurements.push({
                        operationCount,
                        elapsedMs: performance.now() - startTime,
                        opsPerSecond: Math.round(1000 / elapsed * 1000),
                        elementCount: store.elements.length,
                        memoryMB: LimitsTestUtils.getMemoryUsage().usedMB
                    });
                    lastMeasurement = performance.now();
                }
            }

            const totalElapsed = performance.now() - startTime;
            const avgOpsPerSecond = Math.round(operationCount / (totalElapsed / 1000));

            // Calcular percentiles
            const opsSorted = measurements.map(m => m.opsPerSecond).sort((a, b) => a - b);
            const p50 = opsSorted[Math.floor(opsSorted.length * 0.5)] || 0;
            const p95 = opsSorted[Math.floor(opsSorted.length * 0.95)] || 0;
            const p99 = opsSorted[Math.floor(opsSorted.length * 0.99)] || 0;

            const metrics = {
                testDurationMs: Math.round(totalElapsed),
                totalOperations: operationCount,
                avgOpsPerSecond,
                p50OpsPerSecond: p50,
                p95OpsPerSecond: p95,
                p99OpsPerSecond: p99,
                minOpsPerSecond: Math.min(...measurements.map(m => m.opsPerSecond)),
                maxOpsPerSecond: Math.max(...measurements.map(m => m.opsPerSecond)),
                finalElementCount: store.elements.length
            };

            store.clear();

            return {
                passed: avgOpsPerSecond > 10000,
                metrics,
                recommendation: `Throughput sostenible: ${avgOpsPerSecond} ops/s (P95: ${p95} ops/s)`
            };
        }
    },

    /**
     * Encontrar limite de tamano de seleccion
     */
    'max-selection-size': {
        name: 'Maximo tamano de seleccion',
        description: 'Encontrar el limite de elementos seleccionados simultaneamente',
        category: 'selection',

        async run(options = {}) {
            const maxSelection = options.maxSelection || 500;
            const store = new LimitsMockStore();

            // Crear elementos
            for (let i = 0; i < maxSelection + 100; i++) {
                store.addElement(LimitsTestUtils.createRandomElement('small'));
            }

            const measurements = [];
            let selectedIds = new Set();

            // Incrementar seleccion gradualmente
            for (let selectionSize = 10; selectionSize <= maxSelection; selectionSize += 10) {
                // Agregar a seleccion
                while (selectedIds.size < selectionSize && selectedIds.size < store.elements.length) {
                    const randomIndex = Math.floor(Math.random() * store.elements.length);
                    selectedIds.add(store.elements[randomIndex].id);
                }

                // Medir operaciones sobre seleccion
                const moveStart = performance.now();
                // Simular mover seleccion
                selectedIds.forEach(id => {
                    const element = store.elements.find(el => el.id === id);
                    if (element) {
                        element.props.x += 10;
                        element.props.y += 10;
                    }
                });
                const moveTime = performance.now() - moveStart;

                // Medir copiar seleccion
                const copyStart = performance.now();
                const copied = [];
                selectedIds.forEach(id => {
                    const element = store.elements.find(el => el.id === id);
                    if (element) {
                        copied.push(JSON.parse(JSON.stringify(element)));
                    }
                });
                const copyTime = performance.now() - copyStart;

                measurements.push({
                    selectionSize: selectedIds.size,
                    moveTimeMs: moveTime,
                    copyTimeMs: copyTime
                });
            }

            // Encontrar punto optimo
            const optimalPoint = measurements
                .filter(m => m.moveTimeMs < 100 && m.copyTimeMs < 200)
                .pop() || measurements[0];

            const metrics = {
                maxSelectionTested: maxSelection,
                optimalSelectionSize: optimalPoint ? optimalPoint.selectionSize : 0,
                performanceCurve: measurements.filter((_, i) => i % 5 === 0).map(m => ({
                    selection: m.selectionSize,
                    moveMs: Math.round(m.moveTimeMs * 100) / 100,
                    copyMs: Math.round(m.copyTimeMs * 100) / 100
                }))
            };

            store.clear();

            return {
                passed: optimalPoint && optimalPoint.selectionSize >= 50,
                metrics,
                recommendation: `Limite recomendado: ${optimalPoint ? optimalPoint.selectionSize : 25} elementos seleccionados`
            };
        }
    }
};

/**
 * Runner de tests de limites
 */
class LimitsTestRunner {
    constructor(options = {}) {
        this.options = {
            verbose: options.verbose || false,
            quickMode: options.quickMode || false
        };
    }

    async runTest(testId) {
        const test = LIMITS_TESTS[testId];
        if (!test) {
            return { error: `Test not found: ${testId}` };
        }

        if (this.options.verbose) {
            console.log(`\nRunning: ${test.name}`);
            console.log(`Description: ${test.description}`);
        }

        const startTime = performance.now();

        // Reducir parametros en modo rapido
        const testOptions = this.options.quickMode ? {
            maxIterations: 500,
            maxDepth: 50,
            maxSymbols: 30,
            maxHistory: 200,
            targetSizeMB: 5,
            testDurationMs: 2000,
            maxSelection: 100
        } : {};

        try {
            const result = await test.run(testOptions);
            result.testId = testId;
            result.testName = test.name;
            result.category = test.category;
            result.duration = performance.now() - startTime;
            return result;
        } catch (error) {
            return {
                testId,
                testName: test.name,
                category: test.category,
                passed: false,
                error: error.message,
                duration: performance.now() - startTime
            };
        }
    }

    async findAllLimits() {
        console.log('VBP System Limits Discovery');
        console.log('===========================\n');

        const limits = {};
        const results = [];

        for (const testId of Object.keys(LIMITS_TESTS)) {
            const result = await this.runTest(testId);
            results.push(result);

            const status = result.passed ? '  ' : '  ';
            console.log(`${status} ${result.testName} (${Math.round(result.duration)}ms)`);

            if (result.recommendation) {
                console.log(`   -> ${result.recommendation}`);
            }

            // Extraer limite encontrado
            if (result.metrics) {
                limits[testId] = {
                    category: result.category,
                    recommendation: result.recommendation,
                    metrics: result.metrics
                };
            }
        }

        return this.generateReport(results, limits);
    }

    generateReport(results, limits) {
        const passed = results.filter(r => r.passed).length;
        const total = results.length;

        console.log('\n===========================');
        console.log('System Limits Summary');
        console.log('===========================\n');

        console.log('Recommended Limits:');
        Object.entries(limits).forEach(([testId, limit]) => {
            if (limit.recommendation) {
                console.log(`  ${limit.recommendation}`);
            }
        });

        console.log(`\nTests: ${passed}/${total} passed`);

        return {
            total,
            passed,
            failed: total - passed,
            limits,
            results
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LIMITS_TESTS,
        LimitsTestRunner,
        LimitsMockStore,
        LimitsTestUtils
    };
}

if (typeof window !== 'undefined') {
    window.VBPLimitsTests = {
        LIMITS_TESTS,
        LimitsTestRunner,
        LimitsMockStore,
        LimitsTestUtils
    };
}
