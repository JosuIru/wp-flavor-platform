/**
 * VBP Data Consistency Tests
 * Pruebas de integridad y consistencia de datos
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

/**
 * Utilidades para tests de consistencia
 */
const ConsistencyUtils = {
    /**
     * Comparacion profunda de objetos
     * @param {*} objectA - Primer objeto
     * @param {*} objectB - Segundo objeto
     * @returns {boolean} true si son iguales
     */
    deepEqual(objectA, objectB) {
        if (objectA === objectB) return true;

        if (typeof objectA !== typeof objectB) return false;

        if (objectA === null || objectB === null) return objectA === objectB;

        if (typeof objectA !== 'object') return objectA === objectB;

        if (Array.isArray(objectA) !== Array.isArray(objectB)) return false;

        if (Array.isArray(objectA)) {
            if (objectA.length !== objectB.length) return false;
            return objectA.every((item, index) => this.deepEqual(item, objectB[index]));
        }

        const keysA = Object.keys(objectA);
        const keysB = Object.keys(objectB);

        if (keysA.length !== keysB.length) return false;

        return keysA.every(key => this.deepEqual(objectA[key], objectB[key]));
    },

    /**
     * Clonar profundamente un objeto
     * @param {*} object - Objeto a clonar
     * @returns {*} Clon del objeto
     */
    deepClone(object) {
        if (object === null || typeof object !== 'object') {
            return object;
        }

        if (Array.isArray(object)) {
            return object.map(item => this.deepClone(item));
        }

        const clone = {};
        for (const key in object) {
            if (Object.prototype.hasOwnProperty.call(object, key)) {
                clone[key] = this.deepClone(object[key]);
            }
        }
        return clone;
    },

    /**
     * Generar hash simple de un objeto
     * @param {Object} object - Objeto a hashear
     * @returns {string} Hash del objeto
     */
    hashObject(object) {
        const str = JSON.stringify(object);
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString(16);
    },

    /**
     * Generar diff entre dos objetos
     * @param {Object} objectA - Objeto original
     * @param {Object} objectB - Objeto modificado
     * @returns {Array} Lista de diferencias
     */
    diff(objectA, objectB, path = '') {
        const differences = [];

        if (typeof objectA !== typeof objectB) {
            differences.push({
                path,
                type: 'type_change',
                from: typeof objectA,
                to: typeof objectB
            });
            return differences;
        }

        if (objectA === null || objectB === null) {
            if (objectA !== objectB) {
                differences.push({
                    path,
                    type: 'value_change',
                    from: objectA,
                    to: objectB
                });
            }
            return differences;
        }

        if (typeof objectA !== 'object') {
            if (objectA !== objectB) {
                differences.push({
                    path,
                    type: 'value_change',
                    from: objectA,
                    to: objectB
                });
            }
            return differences;
        }

        const keysA = new Set(Object.keys(objectA));
        const keysB = new Set(Object.keys(objectB));

        // Claves eliminadas
        for (const key of keysA) {
            if (!keysB.has(key)) {
                differences.push({
                    path: path ? `${path}.${key}` : key,
                    type: 'removed',
                    value: objectA[key]
                });
            }
        }

        // Claves agregadas
        for (const key of keysB) {
            if (!keysA.has(key)) {
                differences.push({
                    path: path ? `${path}.${key}` : key,
                    type: 'added',
                    value: objectB[key]
                });
            }
        }

        // Claves modificadas
        for (const key of keysA) {
            if (keysB.has(key)) {
                const childDiffs = this.diff(
                    objectA[key],
                    objectB[key],
                    path ? `${path}.${key}` : key
                );
                differences.push(...childDiffs);
            }
        }

        return differences;
    },

    /**
     * Esperar un tiempo
     * @param {number} ms - Milisegundos
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Generar ID unico
     * @returns {string}
     */
    generateId() {
        return `id-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }
};

/**
 * Mock del store VBP para tests de consistencia
 */
class ConsistencyMockStore {
    constructor() {
        this.reset();
    }

    reset() {
        this.elements = [];
        this.history = [];
        this.historyIndex = -1;
        this.symbols = new Map();
        this.symbolInstances = new Map();
        this.globalStyles = {};
        this.snapshots = [];
    }

    // ========== Elementos ==========

    addElement(element) {
        const newElement = {
            ...ConsistencyUtils.deepClone(element),
            id: element.id || ConsistencyUtils.generateId(),
            createdAt: Date.now()
        };
        this.elements.push(newElement);
        this.recordHistory({ type: 'add', element: newElement });
        return newElement;
    }

    updateElement(elementId, updates) {
        const elementIndex = this.elements.findIndex(el => el.id === elementId);
        if (elementIndex === -1) return null;

        const previousState = ConsistencyUtils.deepClone(this.elements[elementIndex]);
        Object.assign(this.elements[elementIndex], updates);
        this.elements[elementIndex].updatedAt = Date.now();

        this.recordHistory({
            type: 'update',
            elementId,
            previous: previousState,
            current: ConsistencyUtils.deepClone(this.elements[elementIndex])
        });

        return this.elements[elementIndex];
    }

    removeElement(elementId) {
        const elementIndex = this.elements.findIndex(el => el.id === elementId);
        if (elementIndex === -1) return null;

        const removedElement = this.elements.splice(elementIndex, 1)[0];
        this.recordHistory({ type: 'remove', element: removedElement });
        return removedElement;
    }

    getElementById(elementId) {
        return this.elements.find(el => el.id === elementId);
    }

    // ========== Historial ==========

    recordHistory(action) {
        // Truncar historial si estamos en medio
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }
        this.history.push({ ...action, timestamp: Date.now() });
        this.historyIndex = this.history.length - 1;
    }

    undo() {
        if (this.historyIndex < 0) return false;

        const action = this.history[this.historyIndex];
        this.applyReverseAction(action);
        this.historyIndex--;
        return true;
    }

    redo() {
        if (this.historyIndex >= this.history.length - 1) return false;

        this.historyIndex++;
        const action = this.history[this.historyIndex];
        this.applyAction(action);
        return true;
    }

    applyAction(action) {
        switch (action.type) {
            case 'add':
                this.elements.push(ConsistencyUtils.deepClone(action.element));
                break;
            case 'remove':
                const removeIndex = this.elements.findIndex(el => el.id === action.element.id);
                if (removeIndex !== -1) this.elements.splice(removeIndex, 1);
                break;
            case 'update':
                const updateIndex = this.elements.findIndex(el => el.id === action.elementId);
                if (updateIndex !== -1) {
                    this.elements[updateIndex] = ConsistencyUtils.deepClone(action.current);
                }
                break;
        }
    }

    applyReverseAction(action) {
        switch (action.type) {
            case 'add':
                const addIndex = this.elements.findIndex(el => el.id === action.element.id);
                if (addIndex !== -1) this.elements.splice(addIndex, 1);
                break;
            case 'remove':
                this.elements.push(ConsistencyUtils.deepClone(action.element));
                break;
            case 'update':
                const updateIndex = this.elements.findIndex(el => el.id === action.elementId);
                if (updateIndex !== -1) {
                    this.elements[updateIndex] = ConsistencyUtils.deepClone(action.previous);
                }
                break;
        }
    }

    // ========== Symbols ==========

    createSymbol(element, symbolId = null) {
        const id = symbolId || ConsistencyUtils.generateId();
        const symbol = {
            id,
            sourceElement: ConsistencyUtils.deepClone(element),
            createdAt: Date.now(),
            updatedAt: Date.now()
        };
        this.symbols.set(id, symbol);
        return symbol;
    }

    createInstance(symbolId) {
        const symbol = this.symbols.get(symbolId);
        if (!symbol) return null;

        const instanceId = ConsistencyUtils.generateId();
        const instance = {
            id: instanceId,
            symbolId,
            overrides: {},
            element: ConsistencyUtils.deepClone(symbol.sourceElement),
            createdAt: Date.now()
        };
        instance.element.id = instanceId;
        instance.element.isSymbolInstance = true;
        instance.element.symbolId = symbolId;

        if (!this.symbolInstances.has(symbolId)) {
            this.symbolInstances.set(symbolId, []);
        }
        this.symbolInstances.get(symbolId).push(instance);

        this.elements.push(instance.element);
        return instance;
    }

    updateSymbol(symbolId, updates) {
        const symbol = this.symbols.get(symbolId);
        if (!symbol) return null;

        // Actualizar source
        Object.assign(symbol.sourceElement, updates);
        symbol.updatedAt = Date.now();

        // Propagar a instancias
        const instances = this.symbolInstances.get(symbolId) || [];
        instances.forEach(instance => {
            const element = this.getElementById(instance.id);
            if (element) {
                // Aplicar updates excepto overrides
                Object.keys(updates).forEach(key => {
                    if (!instance.overrides[key]) {
                        element[key] = ConsistencyUtils.deepClone(updates[key]);
                    }
                });
            }
        });

        return symbol;
    }

    setInstanceOverride(instanceId, propertyPath, value) {
        const element = this.getElementById(instanceId);
        if (!element || !element.isSymbolInstance) return false;

        const instances = this.symbolInstances.get(element.symbolId) || [];
        const instance = instances.find(i => i.id === instanceId);
        if (!instance) return false;

        instance.overrides[propertyPath] = value;
        // Aplicar override al elemento
        this.setNestedProperty(element, propertyPath, value);
        return true;
    }

    setNestedProperty(object, path, value) {
        const parts = path.split('.');
        let current = object;
        for (let i = 0; i < parts.length - 1; i++) {
            if (!(parts[i] in current)) {
                current[parts[i]] = {};
            }
            current = current[parts[i]];
        }
        current[parts[parts.length - 1]] = value;
    }

    // ========== Snapshots ==========

    createSnapshot(name = '') {
        const snapshot = {
            id: ConsistencyUtils.generateId(),
            name: name || `Snapshot ${this.snapshots.length + 1}`,
            timestamp: Date.now(),
            elements: ConsistencyUtils.deepClone(this.elements),
            history: ConsistencyUtils.deepClone(this.history),
            historyIndex: this.historyIndex,
            symbols: new Map(
                Array.from(this.symbols.entries()).map(([k, v]) =>
                    [k, ConsistencyUtils.deepClone(v)]
                )
            ),
            hash: ConsistencyUtils.hashObject(this.elements)
        };
        this.snapshots.push(snapshot);
        return snapshot;
    }

    restoreSnapshot(snapshotId) {
        const snapshot = this.snapshots.find(s => s.id === snapshotId);
        if (!snapshot) return false;

        this.elements = ConsistencyUtils.deepClone(snapshot.elements);
        this.history = ConsistencyUtils.deepClone(snapshot.history);
        this.historyIndex = snapshot.historyIndex;
        this.symbols = new Map(
            Array.from(snapshot.symbols.entries()).map(([k, v]) =>
                [k, ConsistencyUtils.deepClone(v)]
            )
        );
        return true;
    }

    // ========== Serializacion ==========

    serialize() {
        return JSON.stringify({
            elements: this.elements,
            symbols: Array.from(this.symbols.entries()),
            symbolInstances: Array.from(this.symbolInstances.entries()),
            globalStyles: this.globalStyles
        });
    }

    deserialize(data) {
        try {
            const parsed = JSON.parse(data);
            this.elements = parsed.elements || [];
            this.symbols = new Map(parsed.symbols || []);
            this.symbolInstances = new Map(parsed.symbolInstances || []);
            this.globalStyles = parsed.globalStyles || {};
            return true;
        } catch (error) {
            return false;
        }
    }

    getState() {
        return {
            elementCount: this.elements.length,
            historyLength: this.history.length,
            historyIndex: this.historyIndex,
            symbolCount: this.symbols.size,
            snapshotCount: this.snapshots.length
        };
    }
}

/**
 * Tests de consistencia de datos
 */
const DATA_CONSISTENCY_TESTS = {
    /**
     * Test: Guardar y cargar produce el mismo resultado
     */
    'save-load-consistency': {
        name: 'Consistencia guardar/cargar',
        description: 'Verificar que guardar y cargar produce datos identicos',
        category: 'serialization',

        async run() {
            const store = new ConsistencyMockStore();

            // Crear datos complejos
            for (let i = 0; i < 20; i++) {
                store.addElement({
                    type: 'container',
                    props: {
                        x: Math.random() * 1000,
                        y: Math.random() * 1000,
                        width: 100 + Math.random() * 200,
                        height: 50 + Math.random() * 150,
                        style: {
                            backgroundColor: '#' + Math.floor(Math.random()*16777215).toString(16),
                            borderRadius: Math.random() * 20,
                            nested: {
                                deep: {
                                    property: 'value ' + i
                                }
                            }
                        }
                    },
                    children: [
                        { type: 'text', content: 'Child ' + i }
                    ]
                });
            }

            const originalElements = ConsistencyUtils.deepClone(store.elements);
            const originalHash = ConsistencyUtils.hashObject(store.elements);

            // Serializar
            const serialized = store.serialize();
            const serializedSize = serialized.length;

            // Crear nuevo store y deserializar
            const newStore = new ConsistencyMockStore();
            const deserializeSuccess = newStore.deserialize(serialized);

            // Comparar
            const loadedHash = ConsistencyUtils.hashObject(newStore.elements);
            const isEqual = ConsistencyUtils.deepEqual(originalElements, newStore.elements);

            const differences = isEqual ? [] : ConsistencyUtils.diff(originalElements, newStore.elements);

            const metrics = {
                originalElementCount: originalElements.length,
                loadedElementCount: newStore.elements.length,
                serializedSizeKB: (serializedSize / 1024).toFixed(2),
                originalHash,
                loadedHash,
                hashMatch: originalHash === loadedHash,
                differenceCount: differences.length
            };

            store.reset();
            newStore.reset();

            return {
                passed: deserializeSuccess && isEqual,
                metrics,
                differences: differences.slice(0, 5),
                message: isEqual
                    ? 'Datos consistentes despues de guardar/cargar'
                    : `${differences.length} diferencias encontradas`
            };
        }
    },

    /**
     * Test: Undo/redo mantiene consistencia
     */
    'undo-redo-consistency': {
        name: 'Consistencia undo/redo',
        description: 'Verificar que undo/redo produce estados consistentes',
        category: 'history',

        async run() {
            const store = new ConsistencyMockStore();
            const states = [];
            const operationCount = 30;

            // Registrar estado inicial
            states.push({
                index: -1,
                elements: ConsistencyUtils.deepClone(store.elements),
                hash: ConsistencyUtils.hashObject(store.elements)
            });

            // Ejecutar operaciones y guardar estados
            for (let i = 0; i < operationCount; i++) {
                const operation = Math.random();

                if (operation < 0.5 || store.elements.length === 0) {
                    store.addElement({
                        type: 'element',
                        props: { index: i }
                    });
                } else if (operation < 0.8) {
                    const randomElement = store.elements[
                        Math.floor(Math.random() * store.elements.length)
                    ];
                    store.updateElement(randomElement.id, {
                        props: { updated: true, iteration: i }
                    });
                } else {
                    const randomElement = store.elements[
                        Math.floor(Math.random() * store.elements.length)
                    ];
                    store.removeElement(randomElement.id);
                }

                states.push({
                    index: i,
                    elements: ConsistencyUtils.deepClone(store.elements),
                    hash: ConsistencyUtils.hashObject(store.elements)
                });
            }

            // Undo todo
            let undoCount = 0;
            while (store.undo()) {
                undoCount++;
            }

            const afterUndoAll = ConsistencyUtils.deepClone(store.elements);
            const undoAllMatch = ConsistencyUtils.deepEqual(afterUndoAll, states[0].elements);

            // Redo todo y verificar cada estado
            let redoMismatches = 0;
            let currentStateIndex = 0;

            while (store.redo()) {
                currentStateIndex++;
                const currentState = ConsistencyUtils.deepClone(store.elements);
                const expectedState = states[currentStateIndex].elements;

                if (!ConsistencyUtils.deepEqual(currentState, expectedState)) {
                    redoMismatches++;
                }
            }

            // Verificar estado final
            const finalState = ConsistencyUtils.deepClone(store.elements);
            const finalMatch = ConsistencyUtils.deepEqual(
                finalState,
                states[states.length - 1].elements
            );

            const metrics = {
                operationCount,
                undoCount,
                redoCount: currentStateIndex,
                undoAllMatch,
                redoMismatches,
                finalMatch,
                historyLength: store.history.length,
                statesRecorded: states.length
            };

            store.reset();

            return {
                passed: undoAllMatch && finalMatch && redoMismatches === 0,
                metrics,
                message: undoAllMatch && finalMatch && redoMismatches === 0
                    ? `${operationCount} operaciones con undo/redo consistente`
                    : `Inconsistencias: undo=${!undoAllMatch}, redo_mismatches=${redoMismatches}, final=${!finalMatch}`
            };
        }
    },

    /**
     * Test: Symbols se sincronizan correctamente
     */
    'symbol-sync': {
        name: 'Sincronizacion de Symbols',
        description: 'Verificar que los symbols se sincronizan a todas las instancias',
        category: 'symbols',

        async run() {
            const store = new ConsistencyMockStore();

            // Crear symbol
            const baseElement = {
                type: 'button',
                props: {
                    text: 'Click me',
                    color: 'blue',
                    size: 'medium'
                }
            };

            const symbol = store.createSymbol(baseElement);
            const instanceCount = 5;
            const instances = [];

            // Crear instancias
            for (let i = 0; i < instanceCount; i++) {
                const instance = store.createInstance(symbol.id);
                instances.push(instance);
            }

            // Verificar estado inicial
            const initialConsistent = instances.every(instance => {
                const element = store.getElementById(instance.id);
                return element && element.props.color === 'blue';
            });

            // Modificar symbol
            store.updateSymbol(symbol.id, {
                props: {
                    text: 'Updated',
                    color: 'red',
                    size: 'large'
                }
            });

            // Verificar propagacion
            const afterUpdateConsistent = instances.every(instance => {
                const element = store.getElementById(instance.id);
                return element && element.props.color === 'red';
            });

            // Crear override en una instancia
            const overrideInstance = instances[0];
            store.setInstanceOverride(overrideInstance.id, 'props.color', 'green');

            // Verificar que solo la instancia con override es diferente
            const overrideVerification = instances.map(instance => {
                const element = store.getElementById(instance.id);
                return {
                    id: instance.id,
                    color: element.props.color,
                    hasOverride: instance.id === overrideInstance.id
                };
            });

            const overrideApplied = store.getElementById(overrideInstance.id).props.color === 'green';
            const othersUnaffected = instances
                .filter(i => i.id !== overrideInstance.id)
                .every(instance => store.getElementById(instance.id).props.color === 'red');

            // Modificar symbol de nuevo, verificar que override se mantiene
            store.updateSymbol(symbol.id, {
                props: {
                    text: 'Final update',
                    color: 'purple'
                }
            });

            const overridePreserved = store.getElementById(overrideInstance.id).props.color === 'green';
            const othersUpdated = instances
                .filter(i => i.id !== overrideInstance.id)
                .every(instance => store.getElementById(instance.id).props.color === 'purple');

            const metrics = {
                instanceCount,
                initialConsistent,
                afterUpdateConsistent,
                overrideApplied,
                othersUnaffected,
                overridePreserved,
                othersUpdated,
                overrideVerification
            };

            store.reset();

            const passed = initialConsistent &&
                afterUpdateConsistent &&
                overrideApplied &&
                othersUnaffected &&
                overridePreserved &&
                othersUpdated;

            return {
                passed,
                metrics,
                message: passed
                    ? `${instanceCount} instancias sincronizadas correctamente`
                    : 'Fallo en sincronizacion de symbols'
            };
        }
    },

    /**
     * Test: Snapshots preservan estado completo
     */
    'snapshot-integrity': {
        name: 'Integridad de Snapshots',
        description: 'Verificar que los snapshots preservan y restauran estado completo',
        category: 'snapshots',

        async run() {
            const store = new ConsistencyMockStore();

            // Crear estado inicial
            for (let i = 0; i < 10; i++) {
                store.addElement({
                    type: 'element',
                    props: { phase: 'initial', index: i }
                });
            }

            // Crear snapshot inicial
            const snapshot1 = store.createSnapshot('Initial state');
            const snapshot1Hash = snapshot1.hash;
            const snapshot1ElementCount = snapshot1.elements.length;

            // Hacer cambios
            for (let i = 0; i < 5; i++) {
                store.addElement({
                    type: 'element',
                    props: { phase: 'second', index: i }
                });
            }

            store.updateElement(store.elements[0].id, { props: { modified: true } });
            store.removeElement(store.elements[5].id);

            // Crear snapshot de estado modificado
            const snapshot2 = store.createSnapshot('Modified state');
            const snapshot2Hash = snapshot2.hash;
            const snapshot2ElementCount = snapshot2.elements.length;

            // Verificar que son diferentes
            const snapshotsDifferent = snapshot1Hash !== snapshot2Hash;

            // Mas cambios
            store.addElement({ type: 'element', props: { phase: 'third' } });
            const currentElementCount = store.elements.length;

            // Restaurar snapshot 1
            const restore1Success = store.restoreSnapshot(snapshot1.id);
            const afterRestore1Count = store.elements.length;
            const afterRestore1Hash = ConsistencyUtils.hashObject(store.elements);
            const restore1Match = afterRestore1Hash === snapshot1Hash;

            // Restaurar snapshot 2
            const restore2Success = store.restoreSnapshot(snapshot2.id);
            const afterRestore2Count = store.elements.length;
            const afterRestore2Hash = ConsistencyUtils.hashObject(store.elements);
            const restore2Match = afterRestore2Hash === snapshot2Hash;

            const metrics = {
                snapshot1ElementCount,
                snapshot2ElementCount,
                currentElementCount,
                snapshotsDifferent,
                restore1Success,
                afterRestore1Count,
                restore1Match,
                restore2Success,
                afterRestore2Count,
                restore2Match,
                totalSnapshots: store.snapshots.length
            };

            store.reset();

            const passed = snapshotsDifferent &&
                restore1Success && restore1Match &&
                restore2Success && restore2Match;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Snapshots preservan e restauran estado correctamente'
                    : 'Error en integridad de snapshots'
            };
        }
    },

    /**
     * Test: Operaciones concurrentes no corrompen datos
     */
    'concurrent-operations': {
        name: 'Operaciones concurrentes',
        description: 'Verificar integridad con operaciones paralelas',
        category: 'concurrency',

        async run() {
            const store = new ConsistencyMockStore();
            const operationsPerWorker = 20;
            const workerCount = 5;

            // Estado inicial
            for (let i = 0; i < 10; i++) {
                store.addElement({
                    type: 'element',
                    props: { initial: true, index: i }
                });
            }

            const initialHash = ConsistencyUtils.hashObject(store.elements);
            const corruptionErrors = [];

            // Simular operaciones concurrentes
            const workerOperations = [];
            for (let workerId = 0; workerId < workerCount; workerId++) {
                const workerPromise = (async () => {
                    for (let op = 0; op < operationsPerWorker; op++) {
                        const operation = Math.random();

                        try {
                            if (operation < 0.4) {
                                store.addElement({
                                    type: 'element',
                                    props: { workerId, operation: op }
                                });
                            } else if (operation < 0.7 && store.elements.length > 0) {
                                const randomIndex = Math.floor(Math.random() * store.elements.length);
                                const element = store.elements[randomIndex];
                                if (element) {
                                    store.updateElement(element.id, {
                                        props: { ...element.props, updatedBy: workerId }
                                    });
                                }
                            } else if (store.elements.length > 10) {
                                const randomIndex = Math.floor(Math.random() * store.elements.length);
                                const element = store.elements[randomIndex];
                                if (element) {
                                    store.removeElement(element.id);
                                }
                            }
                        } catch (error) {
                            corruptionErrors.push({
                                workerId,
                                operation: op,
                                error: error.message
                            });
                        }

                        // Pequena pausa para simular async
                        await ConsistencyUtils.sleep(Math.random() * 5);
                    }
                })();
                workerOperations.push(workerPromise);
            }

            await Promise.all(workerOperations);

            // Verificar integridad de datos
            const elementIntegrity = store.elements.every(el =>
                el.id && el.type && typeof el.props === 'object'
            );

            // Verificar que no hay elementos duplicados
            const elementIds = store.elements.map(el => el.id);
            const uniqueIds = new Set(elementIds);
            const noDuplicates = elementIds.length === uniqueIds.size;

            // Verificar que el historial es consistente
            const historyIntegrity = store.history.every(action =>
                action.type && action.timestamp
            );

            // Probar undo/redo
            const undoCount = Math.min(10, store.historyIndex + 1);
            let undoSuccess = true;
            for (let i = 0; i < undoCount; i++) {
                if (!store.undo()) {
                    undoSuccess = false;
                    break;
                }
            }

            let redoSuccess = true;
            for (let i = 0; i < undoCount; i++) {
                if (!store.redo()) {
                    redoSuccess = false;
                    break;
                }
            }

            const metrics = {
                workerCount,
                operationsPerWorker,
                totalOperations: workerCount * operationsPerWorker,
                finalElementCount: store.elements.length,
                historyLength: store.history.length,
                corruptionErrors: corruptionErrors.length,
                elementIntegrity,
                noDuplicates,
                historyIntegrity,
                undoSuccess,
                redoSuccess
            };

            store.reset();

            const passed = elementIntegrity &&
                noDuplicates &&
                historyIntegrity &&
                undoSuccess &&
                redoSuccess &&
                corruptionErrors.length === 0;

            return {
                passed,
                metrics,
                errors: corruptionErrors.slice(0, 5),
                message: passed
                    ? `${workerCount * operationsPerWorker} operaciones concurrentes sin corrupcion`
                    : `Errores de integridad detectados`
            };
        }
    },

    /**
     * Test: Referencias cruzadas se mantienen validas
     */
    'reference-integrity': {
        name: 'Integridad de referencias',
        description: 'Verificar que las referencias entre elementos se mantienen validas',
        category: 'references',

        async run() {
            const store = new ConsistencyMockStore();

            // Crear estructura con referencias
            const parent = store.addElement({
                type: 'container',
                props: { role: 'parent' },
                children: []
            });

            const children = [];
            for (let i = 0; i < 5; i++) {
                const child = store.addElement({
                    type: 'element',
                    props: { role: 'child', index: i },
                    parentId: parent.id
                });
                children.push(child);
            }

            // Actualizar parent con referencias a children
            store.updateElement(parent.id, {
                childIds: children.map(c => c.id)
            });

            // Crear simbolo de un child
            const symbol = store.createSymbol(children[0]);

            // Verificar referencias iniciales
            const parentElement = store.getElementById(parent.id);
            const initialReferencesValid = parentElement.childIds.every(
                childId => store.getElementById(childId) !== undefined
            );

            // Eliminar un child
            const removedChildId = children[2].id;
            store.removeElement(removedChildId);

            // Verificar que la referencia ahora es invalida (esperado)
            const hasOrphanReference = parentElement.childIds.includes(removedChildId);
            const orphanedElementExists = store.getElementById(removedChildId) !== undefined;

            // Limpiar referencias huerfanas (simulando un proceso de limpieza)
            const cleanedChildIds = parentElement.childIds.filter(
                childId => store.getElementById(childId) !== undefined
            );
            store.updateElement(parent.id, { childIds: cleanedChildIds });

            // Verificar despues de limpieza
            const afterCleanupValid = store.getElementById(parent.id).childIds.every(
                childId => store.getElementById(childId) !== undefined
            );

            // Verificar symbol reference
            const symbolStillValid = store.symbols.has(symbol.id);

            const metrics = {
                initialChildCount: children.length,
                initialReferencesValid,
                removedChildId,
                hasOrphanReference,
                orphanedElementExists,
                afterCleanupValid,
                finalChildCount: cleanedChildIds.length,
                symbolStillValid
            };

            store.reset();

            // El test pasa si podemos detectar y limpiar referencias invalidas
            const passed = initialReferencesValid &&
                hasOrphanReference &&
                !orphanedElementExists &&
                afterCleanupValid;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Referencias gestionadas correctamente'
                    : 'Problema con integridad de referencias'
            };
        }
    }
};

/**
 * Runner de tests de consistencia
 */
class DataConsistencyTestRunner {
    constructor(options = {}) {
        this.options = {
            verbose: options.verbose || false,
            stopOnFailure: options.stopOnFailure || false
        };
    }

    async runTest(testId) {
        const test = DATA_CONSISTENCY_TESTS[testId];
        if (!test) {
            return { error: `Test not found: ${testId}` };
        }

        if (this.options.verbose) {
            console.log(`\nRunning: ${test.name}`);
        }

        const startTime = performance.now();

        try {
            const result = await test.run();
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

    async runAll() {
        console.log('VBP Data Consistency Tests');
        console.log('==========================\n');

        const results = [];

        for (const testId of Object.keys(DATA_CONSISTENCY_TESTS)) {
            const result = await this.runTest(testId);
            results.push(result);

            const status = result.passed ? '  ' : '  ';
            console.log(`${status} ${result.testName} (${Math.round(result.duration)}ms)`);

            if (this.options.verbose && result.metrics) {
                console.log('   Metrics:', JSON.stringify(result.metrics, null, 2));
            }

            if (!result.passed && this.options.stopOnFailure) {
                break;
            }
        }

        return this.generateSummary(results);
    }

    generateSummary(results) {
        const passed = results.filter(r => r.passed).length;
        const failed = results.filter(r => !r.passed).length;
        const total = results.length;

        console.log('\n==========================');
        console.log('Summary');
        console.log('==========================');
        console.log(`Passed: ${passed}/${total}`);
        console.log(`Failed: ${failed}`);

        if (failed > 0) {
            console.log('\nFailed tests:');
            results.filter(r => !r.passed).forEach(r => {
                console.log(`  - ${r.testName}: ${r.message || r.error}`);
            });
        }

        return {
            total,
            passed,
            failed,
            successRate: Math.round((passed / total) * 100),
            results
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DATA_CONSISTENCY_TESTS,
        DataConsistencyTestRunner,
        ConsistencyMockStore,
        ConsistencyUtils
    };
}

if (typeof window !== 'undefined') {
    window.VBPDataConsistency = {
        DATA_CONSISTENCY_TESTS,
        DataConsistencyTestRunner,
        ConsistencyMockStore,
        ConsistencyUtils
    };
}
