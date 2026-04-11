/**
 * VBP Stress Tests
 * Pruebas de carga, limites y recuperacion
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

// Utilidades de medicion
const StressTestUtils = {
    /**
     * Mide el tiempo de renderizado de un conjunto de elementos
     * @param {Function} renderFunction - Funcion de renderizado a medir
     * @returns {number} Tiempo en milisegundos
     */
    measureRenderTime(renderFunction) {
        const startTime = performance.now();
        renderFunction();
        return performance.now() - startTime;
    },

    /**
     * Mide FPS durante un periodo de tiempo
     * @param {number} durationMs - Duracion de la medicion
     * @returns {Promise<number>} FPS promedio
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
                    resolve(frameCount / elapsedSeconds);
                }
            }

            requestAnimationFrame(countFrame);
        });
    },

    /**
     * Mide el uso de memoria
     * @returns {Object} Informacion de memoria
     */
    measureMemory() {
        if (performance.memory) {
            return {
                usedJSHeapSize: performance.memory.usedJSHeapSize,
                totalJSHeapSize: performance.memory.totalJSHeapSize,
                jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
                usedMB: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024)
            };
        }
        return { usedMB: 0, available: false };
    },

    /**
     * Crea un elemento aleatorio para pruebas
     * @returns {Object} Elemento VBP
     */
    createRandomElement() {
        const types = ['text', 'image', 'button', 'container', 'heading', 'paragraph'];
        const elementType = types[Math.floor(Math.random() * types.length)];

        return {
            id: `stress-element-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            type: elementType,
            props: {
                x: Math.random() * 1000,
                y: Math.random() * 2000,
                width: 50 + Math.random() * 200,
                height: 30 + Math.random() * 100,
                content: `Element ${elementType}`,
                style: {
                    backgroundColor: `#${Math.floor(Math.random()*16777215).toString(16)}`,
                    borderRadius: Math.random() * 20
                }
            },
            children: []
        };
    },

    /**
     * Espera un tiempo determinado
     * @param {number} ms - Milisegundos a esperar
     * @returns {Promise<void>}
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Genera un ID unico
     * @returns {string}
     */
    generateId() {
        return `id-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }
};

/**
 * Mock del store VBP para pruebas de estres
 */
class MockVBPStore {
    constructor() {
        this.elements = [];
        this.history = [];
        this.historyIndex = -1;
        this.maxHistorySize = 100;
        this.symbols = new Map();
        this.selectedElements = new Set();
        this.isDirty = false;
        this.saveCount = 0;
        this.saveErrors = [];
    }

    addElement(element) {
        this.elements.push(element);
        this.pushHistory({ action: 'add', element });
        this.isDirty = true;
        return element;
    }

    removeElement(elementId) {
        const index = this.elements.findIndex(el => el.id === elementId);
        if (index !== -1) {
            const removedElement = this.elements.splice(index, 1)[0];
            this.pushHistory({ action: 'remove', element: removedElement });
            this.isDirty = true;
            return removedElement;
        }
        return null;
    }

    updateElement(elementId, updates) {
        const element = this.elements.find(el => el.id === elementId);
        if (element) {
            const previousState = JSON.parse(JSON.stringify(element));
            Object.assign(element, updates);
            this.pushHistory({ action: 'update', elementId, previous: previousState, current: updates });
            this.isDirty = true;
            return element;
        }
        return null;
    }

    pushHistory(action) {
        // Eliminar historial futuro si estamos en medio
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }

        this.history.push(action);

        // Limitar tamano del historial
        if (this.history.length > this.maxHistorySize) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }
    }

    undo() {
        if (this.historyIndex >= 0) {
            const action = this.history[this.historyIndex];
            this.applyReverseAction(action);
            this.historyIndex--;
            this.isDirty = true;
            return true;
        }
        return false;
    }

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            const action = this.history[this.historyIndex];
            this.applyAction(action);
            this.isDirty = true;
            return true;
        }
        return false;
    }

    applyAction(action) {
        switch (action.action) {
            case 'add':
                this.elements.push(action.element);
                break;
            case 'remove':
                const removeIndex = this.elements.findIndex(el => el.id === action.element.id);
                if (removeIndex !== -1) this.elements.splice(removeIndex, 1);
                break;
            case 'update':
                const updateElement = this.elements.find(el => el.id === action.elementId);
                if (updateElement) Object.assign(updateElement, action.current);
                break;
        }
    }

    applyReverseAction(action) {
        switch (action.action) {
            case 'add':
                const addIndex = this.elements.findIndex(el => el.id === action.element.id);
                if (addIndex !== -1) this.elements.splice(addIndex, 1);
                break;
            case 'remove':
                this.elements.push(action.element);
                break;
            case 'update':
                const updateElement = this.elements.find(el => el.id === action.elementId);
                if (updateElement) Object.assign(updateElement, action.previous);
                break;
        }
    }

    async save(simulateError = false) {
        if (simulateError) {
            this.saveErrors.push({ time: Date.now(), error: 'Simulated network error' });
            throw new Error('Simulated network error');
        }

        await StressTestUtils.sleep(50); // Simular latencia
        this.saveCount++;
        this.isDirty = false;
        return { success: true, timestamp: Date.now() };
    }

    getState() {
        return {
            elements: this.elements,
            historyLength: this.history.length,
            historyIndex: this.historyIndex,
            isDirty: this.isDirty
        };
    }

    clear() {
        this.elements = [];
        this.history = [];
        this.historyIndex = -1;
        this.symbols.clear();
        this.selectedElements.clear();
        this.isDirty = false;
    }
}

/**
 * Suite de pruebas de estres para VBP
 */
const STRESS_TESTS = {
    /**
     * Test 1: Muchos elementos en canvas
     */
    'massive-elements': {
        name: 'Canvas con 1000 elementos',
        description: 'Verificar rendimiento con muchos elementos simultaneos',
        category: 'performance',
        timeout: 60000,

        async run(options = {}) {
            const elementCount = options.elementCount || 1000;
            const store = new MockVBPStore();
            const startTime = performance.now();

            // Agregar elementos
            for (let i = 0; i < elementCount; i++) {
                const element = StressTestUtils.createRandomElement();
                store.addElement(element);
            }

            const addTime = performance.now() - startTime;

            // Medir FPS durante operaciones
            const fps = await StressTestUtils.measureFPS(2000);

            // Medir memoria
            const memory = StressTestUtils.measureMemory();

            // Probar interactividad (seleccionar elementos aleatorios)
            const selectStartTime = performance.now();
            for (let i = 0; i < 100; i++) {
                const randomIndex = Math.floor(Math.random() * store.elements.length);
                store.selectedElements.add(store.elements[randomIndex].id);
            }
            const selectTime = performance.now() - selectStartTime;

            // Probar actualizaciones
            const updateStartTime = performance.now();
            for (let i = 0; i < 100; i++) {
                const randomIndex = Math.floor(Math.random() * store.elements.length);
                store.updateElement(store.elements[randomIndex].id, {
                    props: { x: Math.random() * 1000 }
                });
            }
            const updateTime = performance.now() - updateStartTime;

            const metrics = {
                elementCount: store.elements.length,
                addTimeMs: Math.round(addTime),
                addTimePerElement: (addTime / elementCount).toFixed(3),
                fps: Math.round(fps),
                memoryMB: memory.usedMB,
                selectTime100: Math.round(selectTime),
                updateTime100: Math.round(updateTime),
                historySize: store.history.length
            };

            // Criterios de exito
            const passed = fps > 30 && selectTime < 100 && updateTime < 200;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `Rendimiento aceptable: ${fps} FPS con ${elementCount} elementos`
                    : `Rendimiento degradado: ${fps} FPS (minimo 30)`
            };
        }
    },

    /**
     * Test 2: Operaciones rapidas consecutivas
     */
    'rapid-operations': {
        name: 'Operaciones rapidas consecutivas',
        description: '100 operaciones undo/redo en 10 segundos',
        category: 'stability',
        timeout: 30000,

        async run(options = {}) {
            const operationCount = options.operationCount || 100;
            const store = new MockVBPStore();
            const startTime = performance.now();
            let operationsCompleted = 0;
            let undoRedoMatches = 0;

            // Preparar elementos
            for (let i = 0; i < 50; i++) {
                store.addElement(StressTestUtils.createRandomElement());
            }

            const initialState = JSON.stringify(store.getState().elements);

            // Ejecutar operaciones rapidas
            for (let i = 0; i < operationCount; i++) {
                const element = StressTestUtils.createRandomElement();
                store.addElement(element);
                operationsCompleted++;

                // Undo
                store.undo();
                operationsCompleted++;

                // Redo
                store.redo();
                operationsCompleted++;

                // Delete
                store.removeElement(element.id);
                operationsCompleted++;
            }

            const elapsed = performance.now() - startTime;
            const operationsPerSecond = (operationsCompleted / (elapsed / 1000)).toFixed(2);

            // Verificar consistencia del estado
            // Undo todo
            let undoCount = 0;
            while (store.undo()) {
                undoCount++;
            }

            // Redo todo
            let redoCount = 0;
            while (store.redo()) {
                redoCount++;
            }

            const stateConsistent = undoCount === redoCount;

            const metrics = {
                operationsCompleted,
                elapsedMs: Math.round(elapsed),
                operationsPerSecond,
                undoCount,
                redoCount,
                stateConsistent,
                finalElementCount: store.elements.length
            };

            const passed = operationsPerSecond > 50 && stateConsistent;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `${operationsPerSecond} ops/s, estado consistente`
                    : `Rendimiento o consistencia fallidos`
            };
        }
    },

    /**
     * Test 3: Simulacion de usuarios simultaneos
     */
    'concurrent-users': {
        name: '10 usuarios simultaneos',
        description: 'Simular edicion concurrente de multiples usuarios',
        category: 'concurrency',
        timeout: 30000,

        async run(options = {}) {
            const userCount = options.userCount || 10;
            const operationsPerUser = options.operationsPerUser || 20;
            const store = new MockVBPStore();

            // Elementos base
            for (let i = 0; i < 20; i++) {
                store.addElement(StressTestUtils.createRandomElement());
            }

            const conflicts = [];
            const userOperations = [];

            // Simular usuarios
            const simulateUser = async (userId) => {
                const localOperations = [];
                const startTime = performance.now();

                for (let i = 0; i < operationsPerUser; i++) {
                    const operation = Math.random();

                    try {
                        if (operation < 0.33) {
                            // Agregar
                            const element = StressTestUtils.createRandomElement();
                            element.id = `user-${userId}-${element.id}`;
                            store.addElement(element);
                            localOperations.push({ type: 'add', elementId: element.id });
                        } else if (operation < 0.66 && store.elements.length > 0) {
                            // Actualizar
                            const randomIndex = Math.floor(Math.random() * store.elements.length);
                            const elementId = store.elements[randomIndex].id;
                            store.updateElement(elementId, {
                                props: {
                                    x: Math.random() * 1000,
                                    updatedBy: userId
                                }
                            });
                            localOperations.push({ type: 'update', elementId });
                        } else if (store.elements.length > 10) {
                            // Eliminar (solo si hay suficientes elementos)
                            const randomIndex = Math.floor(Math.random() * store.elements.length);
                            const elementId = store.elements[randomIndex].id;
                            store.removeElement(elementId);
                            localOperations.push({ type: 'remove', elementId });
                        }
                    } catch (error) {
                        conflicts.push({
                            userId,
                            operation: i,
                            error: error.message
                        });
                    }

                    // Pequena pausa para simular latencia
                    await StressTestUtils.sleep(Math.random() * 10);
                }

                return {
                    userId,
                    operations: localOperations.length,
                    duration: performance.now() - startTime
                };
            };

            // Ejecutar todos los usuarios en paralelo
            const startTime = performance.now();
            const userPromises = [];
            for (let i = 0; i < userCount; i++) {
                userPromises.push(simulateUser(i));
            }

            const results = await Promise.all(userPromises);
            const totalDuration = performance.now() - startTime;

            // Verificar integridad de datos
            const dataIntegrity = store.elements.every(el =>
                el.id && el.type && el.props
            );

            // Verificar historial
            const historyIntact = store.history.length > 0;

            const metrics = {
                userCount,
                totalOperations: results.reduce((sum, r) => sum + r.operations, 0),
                totalDurationMs: Math.round(totalDuration),
                conflictCount: conflicts.length,
                finalElementCount: store.elements.length,
                dataIntegrity,
                historyIntact,
                avgOperationsPerUser: (results.reduce((sum, r) => sum + r.operations, 0) / userCount).toFixed(1)
            };

            const passed = dataIntegrity && historyIntact && conflicts.length < userCount;

            store.clear();

            return {
                passed,
                metrics,
                conflicts: conflicts.slice(0, 5), // Solo primeros 5 conflictos
                message: passed
                    ? `${userCount} usuarios, ${conflicts.length} conflictos`
                    : `Demasiados conflictos o datos corruptos`
            };
        }
    },

    /**
     * Test 4: Guardado bajo estres
     */
    'save-stress': {
        name: 'Guardado durante edicion intensa',
        description: 'Guardar mientras se edita rapidamente',
        category: 'reliability',
        timeout: 20000,

        async run(options = {}) {
            const editDurationMs = options.editDurationMs || 5000;
            const saveIntervalMs = options.saveIntervalMs || 500;
            const store = new MockVBPStore();

            let editCount = 0;
            let saveAttempts = 0;
            let saveSuccesses = 0;
            let saveFailures = 0;
            let editActive = true;

            // Funcion de edicion continua
            const continuousEditing = async () => {
                const startTime = Date.now();
                while (Date.now() - startTime < editDurationMs) {
                    const element = StressTestUtils.createRandomElement();
                    store.addElement(element);
                    editCount++;

                    if (Math.random() < 0.3 && store.elements.length > 1) {
                        const randomIndex = Math.floor(Math.random() * store.elements.length);
                        store.removeElement(store.elements[randomIndex].id);
                    }

                    await StressTestUtils.sleep(10);
                }
                editActive = false;
            };

            // Funcion de guardado periodico
            const periodicSaving = async () => {
                while (editActive || store.isDirty) {
                    saveAttempts++;
                    try {
                        // Simular fallo ocasional de red
                        const shouldFail = Math.random() < 0.1;
                        await store.save(shouldFail);
                        saveSuccesses++;
                    } catch (error) {
                        saveFailures++;
                    }
                    await StressTestUtils.sleep(saveIntervalMs);
                }
            };

            // Ejecutar ambos en paralelo
            const startTime = performance.now();
            await Promise.all([
                continuousEditing(),
                periodicSaving()
            ]);
            const totalDuration = performance.now() - startTime;

            // Verificar que los datos finales son consistentes
            const dataConsistent = store.elements.every(el =>
                el.id && el.type && typeof el.props === 'object'
            );

            const metrics = {
                editCount,
                saveAttempts,
                saveSuccesses,
                saveFailures,
                saveSuccessRate: ((saveSuccesses / saveAttempts) * 100).toFixed(1) + '%',
                totalDurationMs: Math.round(totalDuration),
                finalElementCount: store.elements.length,
                dataConsistent
            };

            const passed = saveSuccesses > 0 && dataConsistent && (saveSuccesses / saveAttempts) > 0.8;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `${saveSuccesses}/${saveAttempts} guardados exitosos`
                    : `Tasa de guardado muy baja o datos inconsistentes`
            };
        }
    },

    /**
     * Test 5: Recuperacion despues de crash
     */
    'crash-recovery': {
        name: 'Recuperacion despues de crash',
        description: 'Verificar que se recuperan datos no guardados',
        category: 'reliability',
        timeout: 10000,

        async run(options = {}) {
            const store = new MockVBPStore();
            const localStorageKey = 'vbp_crash_recovery_test';

            // Simular localStorage
            const mockLocalStorage = {
                data: {},
                setItem(key, value) { this.data[key] = value; },
                getItem(key) { return this.data[key] || null; },
                removeItem(key) { delete this.data[key]; }
            };

            // Hacer cambios
            const changesBeforeCrash = [];
            for (let i = 0; i < 10; i++) {
                const element = StressTestUtils.createRandomElement();
                store.addElement(element);
                changesBeforeCrash.push(element);
            }

            // Simular guardado local (antes de crash)
            const backupData = {
                timestamp: Date.now(),
                elements: JSON.parse(JSON.stringify(store.elements)),
                historyIndex: store.historyIndex
            };
            mockLocalStorage.setItem(localStorageKey, JSON.stringify(backupData));

            // Simular crash (limpiar estado)
            const elementCountBeforeCrash = store.elements.length;
            store.clear();

            // Verificar que el estado se limpio
            const stateCleared = store.elements.length === 0;

            // Reabrir y recuperar
            let recoveredData = null;
            let recoveryPromptShown = false;

            const storedBackup = mockLocalStorage.getItem(localStorageKey);
            if (storedBackup) {
                recoveryPromptShown = true;
                recoveredData = JSON.parse(storedBackup);

                // Restaurar elementos
                recoveredData.elements.forEach(el => {
                    store.elements.push(el);
                });
            }

            const recoveredCount = store.elements.length;
            const dataRecovered = recoveredCount === elementCountBeforeCrash;

            // Verificar integridad de datos recuperados
            const dataIntegrity = store.elements.every((el, index) =>
                el.id === changesBeforeCrash[index].id
            );

            // Limpiar
            mockLocalStorage.removeItem(localStorageKey);

            const metrics = {
                elementsBeforeCrash: elementCountBeforeCrash,
                stateCleared,
                recoveryPromptShown,
                recoveredElements: recoveredCount,
                dataRecovered,
                dataIntegrity,
                recoveryTimestamp: recoveredData ? recoveredData.timestamp : null
            };

            const passed = recoveryPromptShown && dataRecovered && dataIntegrity;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `${recoveredCount}/${elementCountBeforeCrash} elementos recuperados`
                    : `Fallo en recuperacion de datos`
            };
        }
    },

    /**
     * Test 6: Deep nesting stress
     */
    'deep-nesting': {
        name: 'Anidamiento profundo',
        description: 'Verificar rendimiento con elementos muy anidados',
        category: 'performance',
        timeout: 30000,

        async run(options = {}) {
            const nestingDepth = options.nestingDepth || 50;
            const store = new MockVBPStore();

            // Crear estructura profundamente anidada
            const createNestedElement = (depth, maxDepth) => {
                const element = {
                    id: StressTestUtils.generateId(),
                    type: 'container',
                    props: { depth },
                    children: []
                };

                if (depth < maxDepth) {
                    element.children.push(createNestedElement(depth + 1, maxDepth));
                }

                return element;
            };

            const startTime = performance.now();
            const rootElement = createNestedElement(0, nestingDepth);
            store.addElement(rootElement);
            const createTime = performance.now() - startTime;

            // Medir tiempo de acceso a elemento mas profundo
            const findDeepest = (element, depth = 0) => {
                if (element.children && element.children.length > 0) {
                    return findDeepest(element.children[0], depth + 1);
                }
                return { element, depth };
            };

            const accessStartTime = performance.now();
            const deepest = findDeepest(rootElement);
            const accessTime = performance.now() - accessStartTime;

            // Medir tiempo de serializacion
            const serializeStartTime = performance.now();
            const serialized = JSON.stringify(rootElement);
            const serializeTime = performance.now() - serializeStartTime;

            // Medir tiempo de deserializacion
            const deserializeStartTime = performance.now();
            JSON.parse(serialized);
            const deserializeTime = performance.now() - deserializeStartTime;

            const metrics = {
                nestingDepth: deepest.depth,
                createTimeMs: createTime.toFixed(2),
                accessTimeMs: accessTime.toFixed(2),
                serializeTimeMs: serializeTime.toFixed(2),
                deserializeTimeMs: deserializeTime.toFixed(2),
                serializedSizeKB: (serialized.length / 1024).toFixed(2)
            };

            const passed = createTime < 1000 && accessTime < 10 && serializeTime < 100;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `Profundidad ${nestingDepth} manejada correctamente`
                    : `Rendimiento degradado con anidamiento profundo`
            };
        }
    },

    /**
     * Test 7: Memory leak detection
     */
    'memory-leak': {
        name: 'Deteccion de fugas de memoria',
        description: 'Detectar posibles fugas de memoria durante operaciones',
        category: 'stability',
        timeout: 30000,

        async run(options = {}) {
            const iterations = options.iterations || 10;
            const elementsPerIteration = options.elementsPerIteration || 100;
            const store = new MockVBPStore();

            const memoryReadings = [];
            const initialMemory = StressTestUtils.measureMemory();

            for (let i = 0; i < iterations; i++) {
                // Agregar muchos elementos
                for (let j = 0; j < elementsPerIteration; j++) {
                    store.addElement(StressTestUtils.createRandomElement());
                }

                // Limpiar todos
                store.clear();

                // Forzar GC si esta disponible
                if (global.gc) {
                    global.gc();
                }

                await StressTestUtils.sleep(100);

                const currentMemory = StressTestUtils.measureMemory();
                memoryReadings.push(currentMemory.usedMB);
            }

            const finalMemory = StressTestUtils.measureMemory();

            // Calcular tendencia de memoria
            const memoryGrowth = finalMemory.usedMB - initialMemory.usedMB;
            const avgMemory = memoryReadings.reduce((a, b) => a + b, 0) / memoryReadings.length;
            const maxMemory = Math.max(...memoryReadings);
            const minMemory = Math.min(...memoryReadings);

            const metrics = {
                iterations,
                elementsPerIteration,
                initialMemoryMB: initialMemory.usedMB,
                finalMemoryMB: finalMemory.usedMB,
                memoryGrowthMB: memoryGrowth,
                avgMemoryMB: avgMemory.toFixed(2),
                maxMemoryMB: maxMemory,
                minMemoryMB: minMemory,
                memoryReadings
            };

            // Un crecimiento de mas de 10MB podria indicar fuga
            const passed = memoryGrowth < 10 || !initialMemory.available;

            return {
                passed,
                metrics,
                message: passed
                    ? `Sin fugas detectadas (crecimiento: ${memoryGrowth}MB)`
                    : `Posible fuga de memoria (crecimiento: ${memoryGrowth}MB)`
            };
        }
    },

    /**
     * Test 8: Large data payload
     */
    'large-payload': {
        name: 'Payload de datos grande',
        description: 'Manejar paginas con datos muy grandes',
        category: 'performance',
        timeout: 30000,

        async run(options = {}) {
            const store = new MockVBPStore();
            const targetSizeMB = options.targetSizeMB || 5;

            // Crear elementos con contenido grande
            const createLargeElement = () => {
                const largeContent = 'x'.repeat(10000); // 10KB de texto
                return {
                    id: StressTestUtils.generateId(),
                    type: 'rich-text',
                    props: {
                        content: largeContent,
                        metadata: {
                            created: Date.now(),
                            data: Array(100).fill({ key: 'value', nested: { deep: true } })
                        }
                    },
                    children: []
                };
            };

            const startTime = performance.now();
            let totalSize = 0;
            let elementCount = 0;

            while (totalSize < targetSizeMB * 1024 * 1024) {
                const element = createLargeElement();
                store.addElement(element);
                totalSize = JSON.stringify(store.elements).length;
                elementCount++;

                if (elementCount > 1000) break; // Limite de seguridad
            }

            const createTime = performance.now() - startTime;

            // Medir guardado
            const saveStartTime = performance.now();
            const serialized = JSON.stringify(store.elements);
            const saveTime = performance.now() - saveStartTime;

            // Medir carga
            const loadStartTime = performance.now();
            JSON.parse(serialized);
            const loadTime = performance.now() - loadStartTime;

            const metrics = {
                elementCount,
                totalSizeMB: (totalSize / 1024 / 1024).toFixed(2),
                createTimeMs: Math.round(createTime),
                saveTimeMs: Math.round(saveTime),
                loadTimeMs: Math.round(loadTime),
                throughputMBps: ((totalSize / 1024 / 1024) / (saveTime / 1000)).toFixed(2)
            };

            const passed = saveTime < 5000 && loadTime < 5000;

            store.clear();

            return {
                passed,
                metrics,
                message: passed
                    ? `${metrics.totalSizeMB}MB manejados en ${Math.round(saveTime + loadTime)}ms`
                    : `Rendimiento inaceptable con datos grandes`
            };
        }
    }
};

/**
 * Runner de stress tests
 */
class StressTestRunner {
    constructor(options = {}) {
        this.options = {
            verbose: options.verbose || false,
            stopOnFailure: options.stopOnFailure || false,
            timeout: options.timeout || 60000
        };
        this.results = {};
    }

    async runTest(testId) {
        const test = STRESS_TESTS[testId];
        if (!test) {
            throw new Error(`Test not found: ${testId}`);
        }

        if (this.options.verbose) {
            console.log(`\nRunning: ${test.name}`);
            console.log(`Description: ${test.description}`);
        }

        const startTime = performance.now();

        try {
            const result = await Promise.race([
                test.run(),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Test timeout')), test.timeout || this.options.timeout)
                )
            ]);

            result.duration = performance.now() - startTime;
            result.testId = testId;
            result.testName = test.name;
            result.category = test.category;

            return result;
        } catch (error) {
            return {
                passed: false,
                testId,
                testName: test.name,
                category: test.category,
                duration: performance.now() - startTime,
                error: error.message,
                metrics: {}
            };
        }
    }

    async runAll(testIds = null) {
        const testsToRun = testIds || Object.keys(STRESS_TESTS);
        const results = [];

        console.log('VBP Stress Test Suite');
        console.log('=====================\n');

        for (const testId of testsToRun) {
            const result = await this.runTest(testId);
            results.push(result);

            const status = result.passed ? 'PASS' : 'FAIL';
            const statusIcon = result.passed ? '  ' : '  ';
            console.log(`${statusIcon} [${status}] ${result.testName} (${Math.round(result.duration)}ms)`);

            if (this.options.verbose && result.metrics) {
                console.log('   Metrics:', JSON.stringify(result.metrics, null, 2));
            }

            if (!result.passed && this.options.stopOnFailure) {
                console.log('\nStopping: test failure');
                break;
            }
        }

        return this.generateSummary(results);
    }

    async runCategory(category) {
        const testsInCategory = Object.keys(STRESS_TESTS).filter(
            id => STRESS_TESTS[id].category === category
        );
        return this.runAll(testsInCategory);
    }

    generateSummary(results) {
        const passed = results.filter(r => r.passed).length;
        const failed = results.filter(r => !r.passed).length;
        const totalDuration = results.reduce((sum, r) => sum + (r.duration || 0), 0);

        console.log('\n=====================');
        console.log('Summary');
        console.log('=====================');
        console.log(`Total: ${results.length} tests`);
        console.log(`Passed: ${passed}`);
        console.log(`Failed: ${failed}`);
        console.log(`Duration: ${Math.round(totalDuration)}ms`);
        console.log(`Success rate: ${((passed / results.length) * 100).toFixed(1)}%`);

        if (failed > 0) {
            console.log('\nFailed tests:');
            results.filter(r => !r.passed).forEach(r => {
                console.log(`  - ${r.testName}: ${r.message || r.error || 'Unknown error'}`);
            });
        }

        return {
            total: results.length,
            passed,
            failed,
            duration: totalDuration,
            successRate: (passed / results.length) * 100,
            results
        };
    }
}

// Exportar para uso en diferentes entornos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        STRESS_TESTS,
        StressTestRunner,
        StressTestUtils,
        MockVBPStore
    };
}

if (typeof window !== 'undefined') {
    window.VBPStressTests = {
        STRESS_TESTS,
        StressTestRunner,
        StressTestUtils,
        MockVBPStore
    };
}
