/**
 * VBP Error Recovery Tests
 * Pruebas de recuperacion de errores y resiliencia
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

/**
 * Simulador de errores de red
 */
class NetworkErrorSimulator {
    constructor() {
        this.isOnline = true;
        this.latency = 0;
        this.failureRate = 0;
        this.timeoutRate = 0;
        this.originalFetch = null;
    }

    /**
     * Activar simulacion de red
     */
    enable() {
        if (typeof global !== 'undefined' && global.fetch) {
            this.originalFetch = global.fetch;
            global.fetch = this.createMockFetch();
        }
    }

    /**
     * Desactivar simulacion
     */
    disable() {
        if (this.originalFetch) {
            global.fetch = this.originalFetch;
            this.originalFetch = null;
        }
    }

    /**
     * Simular desconexion
     */
    goOffline() {
        this.isOnline = false;
    }

    /**
     * Simular reconexion
     */
    goOnline() {
        this.isOnline = true;
    }

    /**
     * Configurar latencia
     * @param {number} ms - Latencia en milisegundos
     */
    setLatency(ms) {
        this.latency = ms;
    }

    /**
     * Configurar tasa de fallos
     * @param {number} rate - Tasa de fallo (0-1)
     */
    setFailureRate(rate) {
        this.failureRate = Math.max(0, Math.min(1, rate));
    }

    /**
     * Configurar tasa de timeouts
     * @param {number} rate - Tasa de timeout (0-1)
     */
    setTimeoutRate(rate) {
        this.timeoutRate = Math.max(0, Math.min(1, rate));
    }

    /**
     * Crear fetch mock
     */
    createMockFetch() {
        const simulator = this;

        return async function mockFetch(url, options = {}) {
            // Simular latencia
            if (simulator.latency > 0) {
                await new Promise(resolve => setTimeout(resolve, simulator.latency));
            }

            // Simular offline
            if (!simulator.isOnline) {
                throw new Error('Network request failed: offline');
            }

            // Simular timeout
            if (Math.random() < simulator.timeoutRate) {
                await new Promise(resolve => setTimeout(resolve, 30000));
                throw new Error('Network request timeout');
            }

            // Simular fallo
            if (Math.random() < simulator.failureRate) {
                throw new Error('Network request failed');
            }

            // Respuesta exitosa mock
            return {
                ok: true,
                status: 200,
                json: async () => ({ success: true, timestamp: Date.now() }),
                text: async () => 'OK'
            };
        };
    }
}

/**
 * Mock del store con capacidades de recuperacion
 */
class RecoveryMockStore {
    constructor() {
        this.elements = [];
        this.pendingChanges = [];
        this.localBackup = null;
        this.lastSaveTimestamp = null;
        this.saveQueue = [];
        this.isSaving = false;
        this.saveRetryCount = 0;
        this.maxRetries = 3;
        this.retryDelay = 1000;
        this.conflictResolutionStrategy = 'last-write-wins';
        this.versionNumber = 0;
        this.serverVersion = 0;
    }

    /**
     * Agregar elemento
     */
    addElement(element) {
        const newElement = {
            ...element,
            id: element.id || `el-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            localVersion: ++this.versionNumber
        };
        this.elements.push(newElement);
        this.pendingChanges.push({ type: 'add', element: newElement, timestamp: Date.now() });
        this.saveLocalBackup();
        return newElement;
    }

    /**
     * Actualizar elemento
     */
    updateElement(elementId, updates) {
        const element = this.elements.find(el => el.id === elementId);
        if (!element) return null;

        const previousState = { ...element };
        Object.assign(element, updates);
        element.localVersion = ++this.versionNumber;

        this.pendingChanges.push({
            type: 'update',
            elementId,
            previous: previousState,
            current: { ...element },
            timestamp: Date.now()
        });
        this.saveLocalBackup();
        return element;
    }

    /**
     * Eliminar elemento
     */
    removeElement(elementId) {
        const index = this.elements.findIndex(el => el.id === elementId);
        if (index === -1) return null;

        const removed = this.elements.splice(index, 1)[0];
        this.pendingChanges.push({ type: 'remove', element: removed, timestamp: Date.now() });
        this.saveLocalBackup();
        return removed;
    }

    /**
     * Guardar backup local
     */
    saveLocalBackup() {
        this.localBackup = {
            elements: JSON.parse(JSON.stringify(this.elements)),
            pendingChanges: [...this.pendingChanges],
            timestamp: Date.now(),
            versionNumber: this.versionNumber
        };
    }

    /**
     * Restaurar desde backup local
     */
    restoreFromLocalBackup() {
        if (!this.localBackup) return false;

        this.elements = JSON.parse(JSON.stringify(this.localBackup.elements));
        this.pendingChanges = [...this.localBackup.pendingChanges];
        this.versionNumber = this.localBackup.versionNumber;
        return true;
    }

    /**
     * Guardar al servidor (con manejo de errores)
     */
    async save(networkSimulator = null) {
        if (this.isSaving) {
            // Encolar guardado
            return new Promise((resolve, reject) => {
                this.saveQueue.push({ resolve, reject });
            });
        }

        this.isSaving = true;
        this.saveRetryCount = 0;

        try {
            const result = await this.attemptSave(networkSimulator);
            this.processSaveQueue(null, result);
            return result;
        } catch (error) {
            this.processSaveQueue(error);
            throw error;
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Intentar guardar con reintentos
     */
    async attemptSave(networkSimulator) {
        while (this.saveRetryCount < this.maxRetries) {
            try {
                // Simular llamada al servidor
                if (networkSimulator && !networkSimulator.isOnline) {
                    throw new Error('Network offline');
                }

                // Simular latencia
                await new Promise(resolve => setTimeout(resolve, 50));

                // Verificar conflictos de version
                if (this.serverVersion > this.lastSaveTimestamp && this.lastSaveTimestamp !== null) {
                    return await this.handleVersionConflict();
                }

                // Guardar exitoso
                const timestamp = Date.now();
                this.lastSaveTimestamp = timestamp;
                this.serverVersion = timestamp;
                this.pendingChanges = [];

                return {
                    success: true,
                    timestamp,
                    elementsCount: this.elements.length
                };
            } catch (error) {
                this.saveRetryCount++;

                if (this.saveRetryCount >= this.maxRetries) {
                    throw new Error(`Save failed after ${this.maxRetries} retries: ${error.message}`);
                }

                // Esperar antes de reintentar
                await new Promise(resolve =>
                    setTimeout(resolve, this.retryDelay * this.saveRetryCount)
                );
            }
        }
    }

    /**
     * Manejar conflicto de version
     */
    async handleVersionConflict() {
        switch (this.conflictResolutionStrategy) {
            case 'last-write-wins':
                // Sobrescribir con cambios locales
                this.serverVersion = Date.now();
                return {
                    success: true,
                    conflict: true,
                    resolution: 'last-write-wins',
                    timestamp: this.serverVersion
                };

            case 'server-wins':
                // Descartar cambios locales (simulado)
                this.pendingChanges = [];
                return {
                    success: true,
                    conflict: true,
                    resolution: 'server-wins',
                    discardedChanges: this.pendingChanges.length
                };

            case 'manual':
            default:
                // Requerir resolucion manual
                return {
                    success: false,
                    conflict: true,
                    resolution: 'manual-required',
                    localVersion: this.versionNumber,
                    serverVersion: this.serverVersion
                };
        }
    }

    /**
     * Procesar cola de guardados pendientes
     */
    processSaveQueue(error, result = null) {
        while (this.saveQueue.length > 0) {
            const { resolve, reject } = this.saveQueue.shift();
            if (error) {
                reject(error);
            } else {
                resolve(result);
            }
        }
    }

    /**
     * Verificar si hay cambios pendientes
     */
    hasPendingChanges() {
        return this.pendingChanges.length > 0;
    }

    /**
     * Limpiar estado
     */
    reset() {
        this.elements = [];
        this.pendingChanges = [];
        this.localBackup = null;
        this.lastSaveTimestamp = null;
        this.saveQueue = [];
        this.isSaving = false;
        this.saveRetryCount = 0;
        this.versionNumber = 0;
        this.serverVersion = 0;
    }
}

/**
 * Tests de recuperacion de errores
 */
const ERROR_RECOVERY_TESTS = {
    /**
     * Test: Recuperacion de guardado fallido
     */
    'save-failure-recovery': {
        name: 'Recuperacion de guardado fallido',
        description: 'Verificar recuperacion despues de error al guardar',
        category: 'network',

        async run() {
            const store = new RecoveryMockStore();
            const networkSimulator = new NetworkErrorSimulator();

            // Crear datos
            for (let i = 0; i < 10; i++) {
                store.addElement({
                    type: 'element',
                    content: `Element ${i}`
                });
            }

            // Primer guardado exitoso
            const firstSave = await store.save(networkSimulator);
            const firstSaveSuccess = firstSave.success;

            // Hacer cambios adicionales
            store.addElement({ type: 'element', content: 'New element' });
            store.updateElement(store.elements[0].id, { content: 'Updated' });

            const pendingBeforeFailure = store.pendingChanges.length;
            const backupBeforeFailure = store.localBackup !== null;

            // Simular error de red
            networkSimulator.goOffline();

            let saveFailedCorrectly = false;
            try {
                await store.save(networkSimulator);
            } catch (error) {
                saveFailedCorrectly = error.message.includes('offline') ||
                    error.message.includes('retries');
            }

            // Verificar que los datos locales se preservan
            const dataPreserved = store.elements.length === 11;
            const pendingPreserved = store.hasPendingChanges();
            const backupExists = store.localBackup !== null;

            // Restaurar red y reintentar
            networkSimulator.goOnline();
            store.saveRetryCount = 0;

            const retrySave = await store.save(networkSimulator);
            const retrySuccess = retrySave.success;
            const pendingCleared = !store.hasPendingChanges();

            const metrics = {
                firstSaveSuccess,
                pendingBeforeFailure,
                backupBeforeFailure,
                saveFailedCorrectly,
                dataPreserved,
                pendingPreserved,
                backupExists,
                retrySuccess,
                pendingCleared,
                finalElementCount: store.elements.length
            };

            store.reset();
            networkSimulator.disable();

            const passed = firstSaveSuccess &&
                saveFailedCorrectly &&
                dataPreserved &&
                pendingPreserved &&
                retrySuccess &&
                pendingCleared;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Recuperacion de guardado exitosa'
                    : 'Fallo en recuperacion de guardado'
            };
        }
    },

    /**
     * Test: Recuperacion de conflicto de versiones
     */
    'version-conflict-recovery': {
        name: 'Recuperacion de conflicto de versiones',
        description: 'Manejar cuando otro usuario guardo antes',
        category: 'conflict',

        async run() {
            const store = new RecoveryMockStore();

            // Usuario A hace cambios
            store.addElement({ type: 'element', content: 'User A element' });
            const firstSave = await store.save();

            // Simular que usuario B guardo (incrementar version del servidor)
            store.serverVersion = Date.now() + 1000;

            // Usuario A hace mas cambios
            store.addElement({ type: 'element', content: 'Another User A element' });

            // Probar diferentes estrategias de resolucion

            // Estrategia 1: last-write-wins
            store.conflictResolutionStrategy = 'last-write-wins';
            const lastWriteResult = await store.save();
            const lastWriteResolved = lastWriteResult.conflict && lastWriteResult.success;

            // Reset para siguiente prueba
            store.serverVersion = Date.now() + 2000;
            store.pendingChanges = [{ type: 'test' }];

            // Estrategia 2: server-wins
            store.conflictResolutionStrategy = 'server-wins';
            const serverWinsResult = await store.save();
            const serverWinsResolved = serverWinsResult.conflict && serverWinsResult.success;

            // Reset para siguiente prueba
            store.serverVersion = Date.now() + 3000;
            store.pendingChanges = [{ type: 'test' }];

            // Estrategia 3: manual
            store.conflictResolutionStrategy = 'manual';
            const manualResult = await store.save();
            const manualRequested = manualResult.conflict && !manualResult.success &&
                manualResult.resolution === 'manual-required';

            const metrics = {
                initialSaveSuccess: firstSave.success,
                lastWriteStrategy: {
                    resolved: lastWriteResolved,
                    resolution: lastWriteResult.resolution
                },
                serverWinsStrategy: {
                    resolved: serverWinsResolved,
                    resolution: serverWinsResult.resolution
                },
                manualStrategy: {
                    requested: manualRequested,
                    localVersion: manualResult.localVersion,
                    serverVersion: manualResult.serverVersion
                }
            };

            store.reset();

            const passed = lastWriteResolved && serverWinsResolved && manualRequested;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Todas las estrategias de conflicto funcionan'
                    : 'Error en manejo de conflictos'
            };
        }
    },

    /**
     * Test: Recuperacion de sesion expirada
     */
    'session-expired-recovery': {
        name: 'Recuperacion de sesion expirada',
        description: 'Manejar expiracion de sesion durante edicion',
        category: 'auth',

        async run() {
            const store = new RecoveryMockStore();
            let sessionActive = true;
            let refreshAttempts = 0;
            let loginRedirects = 0;

            // Mock de gestion de sesion
            const sessionManager = {
                isSessionValid: () => sessionActive,
                refreshSession: async () => {
                    refreshAttempts++;
                    await new Promise(resolve => setTimeout(resolve, 50));
                    // Simular refresh exitoso 50% del tiempo
                    if (Math.random() > 0.5) {
                        sessionActive = true;
                        return true;
                    }
                    return false;
                },
                redirectToLogin: () => {
                    loginRedirects++;
                    return true;
                }
            };

            // Crear datos
            for (let i = 0; i < 5; i++) {
                store.addElement({ type: 'element', index: i });
            }

            // Guardar con sesion activa
            const initialSave = await store.save();

            // Expirar sesion
            sessionActive = false;

            // Hacer cambios
            store.addElement({ type: 'element', content: 'After expiration' });
            const pendingChangesCount = store.pendingChanges.length;
            const backupCreated = store.localBackup !== null;

            // Intentar guardar (deberia detectar sesion expirada)
            let sessionError = false;
            let sessionRefreshed = false;
            let dataPreservedDuringRecovery = false;

            try {
                // Simular verificacion de sesion antes de guardar
                if (!sessionManager.isSessionValid()) {
                    // Intentar refrescar
                    sessionRefreshed = await sessionManager.refreshSession();

                    if (!sessionRefreshed) {
                        // Backup antes de redirigir
                        store.saveLocalBackup();
                        sessionManager.redirectToLogin();
                        throw new Error('Session expired, redirecting to login');
                    }
                }

                await store.save();
            } catch (error) {
                sessionError = error.message.includes('Session expired');
                dataPreservedDuringRecovery = store.hasPendingChanges() &&
                    store.localBackup !== null;
            }

            // Simular relogin exitoso
            sessionActive = true;
            store.restoreFromLocalBackup();

            const afterRelogin = await store.save();
            const recoveryComplete = afterRelogin.success && !store.hasPendingChanges();

            const metrics = {
                initialSaveSuccess: initialSave.success,
                pendingChangesCount,
                backupCreated,
                sessionError: sessionError || sessionRefreshed,
                refreshAttempts,
                loginRedirects,
                sessionRefreshed,
                dataPreservedDuringRecovery: dataPreservedDuringRecovery || sessionRefreshed,
                recoveryComplete
            };

            store.reset();

            // El test pasa si:
            // - Se detecto la expiracion de sesion
            // - Los datos se preservaron
            // - La recuperacion fue exitosa
            const passed = (sessionError || sessionRefreshed) &&
                (dataPreservedDuringRecovery || sessionRefreshed) &&
                recoveryComplete;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Recuperacion de sesion exitosa'
                    : 'Fallo en manejo de sesion expirada'
            };
        }
    },

    /**
     * Test: Recuperacion de datos corruptos
     */
    'corrupt-data-recovery': {
        name: 'Recuperacion de datos corruptos',
        description: 'Detectar y recuperar de datos corruptos',
        category: 'data',

        async run() {
            const store = new RecoveryMockStore();

            // Crear datos validos
            for (let i = 0; i < 10; i++) {
                store.addElement({
                    type: 'element',
                    content: `Valid element ${i}`,
                    props: { valid: true }
                });
            }

            // Crear snapshot valido
            const validSnapshot = {
                elements: JSON.parse(JSON.stringify(store.elements)),
                timestamp: Date.now()
            };

            // Simular corrupcion de datos
            const corruptedData = [
                { id: null, type: 'corrupted' }, // ID nulo
                { id: 'valid-id' }, // Sin tipo
                { id: 'another-id', type: 'element', props: 'invalid' }, // Props no es objeto
                'not an object', // No es un objeto
                { id: 'circular', type: 'element', self: null } // (podria ser circular)
            ];

            // Funcion de validacion
            const validateElement = (element) => {
                if (!element || typeof element !== 'object') return false;
                if (!element.id || typeof element.id !== 'string') return false;
                if (!element.type || typeof element.type !== 'string') return false;
                if (element.props && typeof element.props !== 'object') return false;
                return true;
            };

            // Funcion de limpieza
            const cleanCorruptedData = (elements) => {
                return elements.filter(validateElement);
            };

            // Verificar deteccion de corrupcion
            const corruptionDetected = corruptedData.map(el => ({
                element: el,
                isValid: validateElement(el)
            }));

            const allCorruptDetected = corruptionDetected.every(c => !c.isValid);

            // Simular carga de datos mixtos (validos + corruptos)
            const mixedData = [...store.elements, ...corruptedData];
            const cleanedData = cleanCorruptedData(mixedData);

            const corruptedRemoved = cleanedData.length === store.elements.length;
            const validPreserved = cleanedData.every(el =>
                store.elements.some(orig => orig.id === el.id)
            );

            // Probar recuperacion desde snapshot
            store.elements = corruptedData; // Simular estado corrupto
            const wasCorrupt = store.elements.some(el => !validateElement(el));

            // Restaurar desde snapshot
            store.elements = JSON.parse(JSON.stringify(validSnapshot.elements));
            const restoredCorrectly = store.elements.every(validateElement);

            const metrics = {
                originalElementCount: validSnapshot.elements.length,
                corruptedDataCount: corruptedData.length,
                allCorruptDetected,
                mixedDataCount: mixedData.length,
                cleanedDataCount: cleanedData.length,
                corruptedRemoved,
                validPreserved,
                wasCorrupt,
                restoredCorrectly
            };

            store.reset();

            const passed = allCorruptDetected &&
                corruptedRemoved &&
                validPreserved &&
                restoredCorrectly;

            return {
                passed,
                metrics,
                corruptionDetails: corruptionDetected,
                message: passed
                    ? 'Deteccion y recuperacion de corrupcion exitosa'
                    : 'Fallo en manejo de datos corruptos'
            };
        }
    },

    /**
     * Test: Recuperacion de crash del navegador
     */
    'browser-crash-recovery': {
        name: 'Recuperacion de crash del navegador',
        description: 'Recuperar trabajo despues de cierre inesperado',
        category: 'crash',

        async run() {
            // Simular localStorage
            const mockStorage = {
                data: {},
                setItem(key, value) { this.data[key] = value; },
                getItem(key) { return this.data[key] || null; },
                removeItem(key) { delete this.data[key]; },
                clear() { this.data = {}; }
            };

            const RECOVERY_KEY = 'vbp_crash_recovery';
            const AUTOSAVE_INTERVAL = 5000; // 5 segundos

            const store = new RecoveryMockStore();
            let autosaveCount = 0;

            // Funcion de autosave
            const performAutosave = () => {
                const recoveryData = {
                    elements: store.elements,
                    pendingChanges: store.pendingChanges,
                    timestamp: Date.now(),
                    version: store.versionNumber
                };
                mockStorage.setItem(RECOVERY_KEY, JSON.stringify(recoveryData));
                autosaveCount++;
                return recoveryData;
            };

            // Crear datos y simular trabajo
            for (let i = 0; i < 10; i++) {
                store.addElement({
                    type: 'element',
                    content: `Work in progress ${i}`
                });
            }

            // Realizar autosaves
            const autosave1 = performAutosave();
            const autosave1Stored = mockStorage.getItem(RECOVERY_KEY) !== null;

            // Mas trabajo
            store.addElement({ type: 'element', content: 'After first autosave' });
            store.updateElement(store.elements[0].id, { content: 'Modified' });

            const autosave2 = performAutosave();
            const workBeforeCrash = store.elements.length;

            // Simular crash (limpiar estado pero mantener storage)
            const crashTimestamp = Date.now();
            store.reset();
            const stateCleared = store.elements.length === 0;

            // Simular reinicio del navegador
            await new Promise(resolve => setTimeout(resolve, 100));

            // Detectar datos de recuperacion
            const recoveryDataStr = mockStorage.getItem(RECOVERY_KEY);
            const hasRecoveryData = recoveryDataStr !== null;

            let recoveryPromptShown = false;
            let userAcceptedRecovery = false;
            let recoveredElements = 0;

            if (hasRecoveryData) {
                const recoveryData = JSON.parse(recoveryDataStr);
                const ageMinutes = (Date.now() - recoveryData.timestamp) / 60000;

                // Mostrar prompt si datos tienen menos de 24 horas
                if (ageMinutes < 24 * 60) {
                    recoveryPromptShown = true;

                    // Simular usuario aceptando recuperacion
                    userAcceptedRecovery = true;

                    if (userAcceptedRecovery) {
                        store.elements = recoveryData.elements;
                        store.pendingChanges = recoveryData.pendingChanges;
                        store.versionNumber = recoveryData.version;
                        recoveredElements = store.elements.length;
                    }
                }
            }

            const recoveryComplete = recoveredElements === workBeforeCrash;

            // Limpiar datos de recuperacion despues de recuperacion exitosa
            if (recoveryComplete) {
                mockStorage.removeItem(RECOVERY_KEY);
            }

            const dataCleanedAfterRecovery = mockStorage.getItem(RECOVERY_KEY) === null;

            const metrics = {
                autosaveCount,
                autosave1Stored,
                workBeforeCrash,
                crashTimestamp,
                stateCleared,
                hasRecoveryData,
                recoveryPromptShown,
                userAcceptedRecovery,
                recoveredElements,
                recoveryComplete,
                dataCleanedAfterRecovery
            };

            mockStorage.clear();
            store.reset();

            const passed = autosave1Stored &&
                stateCleared &&
                hasRecoveryData &&
                recoveryPromptShown &&
                recoveryComplete &&
                dataCleanedAfterRecovery;

            return {
                passed,
                metrics,
                message: passed
                    ? `${recoveredElements} elementos recuperados despues de crash`
                    : 'Fallo en recuperacion de crash'
            };
        }
    },

    /**
     * Test: Recuperacion de error de validacion
     */
    'validation-error-recovery': {
        name: 'Recuperacion de error de validacion',
        description: 'Manejar errores de validacion del servidor',
        category: 'validation',

        async run() {
            const store = new RecoveryMockStore();

            // Reglas de validacion
            const validationRules = {
                maxElements: 100,
                maxContentLength: 10000,
                requiredFields: ['type', 'id'],
                forbiddenTypes: ['malicious', 'script']
            };

            const validateElement = (element) => {
                const errors = [];

                if (!element.id) errors.push('Missing id');
                if (!element.type) errors.push('Missing type');
                if (validationRules.forbiddenTypes.includes(element.type)) {
                    errors.push(`Forbidden type: ${element.type}`);
                }
                if (element.content && element.content.length > validationRules.maxContentLength) {
                    errors.push('Content too long');
                }

                return { valid: errors.length === 0, errors };
            };

            const validateStore = () => {
                const errors = [];

                if (store.elements.length > validationRules.maxElements) {
                    errors.push(`Too many elements: ${store.elements.length}`);
                }

                store.elements.forEach((el, index) => {
                    const result = validateElement(el);
                    if (!result.valid) {
                        errors.push({ elementIndex: index, errors: result.errors });
                    }
                });

                return { valid: errors.length === 0, errors };
            };

            // Crear datos validos
            for (let i = 0; i < 5; i++) {
                store.addElement({
                    type: 'element',
                    content: `Valid content ${i}`
                });
            }

            const initialValidation = validateStore();
            const initiallyValid = initialValidation.valid;

            // Agregar elemento invalido (tipo prohibido)
            const invalidElement = store.addElement({
                type: 'malicious',
                content: 'Bad content'
            });

            const afterInvalidAddition = validateStore();
            const detectedInvalid = !afterInvalidAddition.valid;
            const errorMessage = afterInvalidAddition.errors.length > 0;

            // Intentar guardar (deberia fallar validacion)
            let saveBlocked = false;
            try {
                if (!validateStore().valid) {
                    throw new Error('Validation failed');
                }
                await store.save();
            } catch (error) {
                saveBlocked = error.message.includes('Validation');
            }

            // Corregir el problema
            store.removeElement(invalidElement.id);

            const afterCorrection = validateStore();
            const correctionSuccessful = afterCorrection.valid;

            // Ahora el guardado deberia funcionar
            const saveAfterCorrection = await store.save();
            const saveSuccessful = saveAfterCorrection.success;

            // Probar limite de elementos
            const originalCount = store.elements.length;
            for (let i = 0; i < validationRules.maxElements; i++) {
                store.addElement({ type: 'element', content: `Bulk ${i}` });
            }

            const limitExceeded = validateStore();
            const limitDetected = !limitExceeded.valid;

            const metrics = {
                initiallyValid,
                detectedInvalid,
                errorMessage,
                saveBlocked,
                correctionSuccessful,
                saveSuccessful,
                elementsAfterBulk: store.elements.length,
                limitDetected,
                validationErrors: afterInvalidAddition.errors.slice(0, 3)
            };

            store.reset();

            const passed = initiallyValid &&
                detectedInvalid &&
                saveBlocked &&
                correctionSuccessful &&
                saveSuccessful &&
                limitDetected;

            return {
                passed,
                metrics,
                message: passed
                    ? 'Manejo de errores de validacion correcto'
                    : 'Fallo en manejo de validacion'
            };
        }
    },

    /**
     * Test: Recuperacion con reintentos exponenciales
     */
    'exponential-backoff-retry': {
        name: 'Reintentos con backoff exponencial',
        description: 'Verificar estrategia de reintentos con backoff',
        category: 'network',

        async run() {
            const store = new RecoveryMockStore();
            const retryAttempts = [];
            let successOnAttempt = 4; // Exito en el 4to intento

            // Mock de save con backoff
            const saveWithBackoff = async (maxRetries = 5, baseDelay = 100) => {
                let attempt = 0;
                let lastError = null;

                while (attempt < maxRetries) {
                    attempt++;
                    const attemptStart = Date.now();

                    try {
                        // Simular fallo hasta cierto intento
                        if (attempt < successOnAttempt) {
                            throw new Error(`Network error on attempt ${attempt}`);
                        }

                        // Exito
                        return {
                            success: true,
                            attempt,
                            timestamp: Date.now()
                        };
                    } catch (error) {
                        lastError = error;
                        const delay = baseDelay * Math.pow(2, attempt - 1); // Backoff exponencial

                        retryAttempts.push({
                            attempt,
                            error: error.message,
                            delayMs: delay,
                            timestamp: attemptStart
                        });

                        if (attempt < maxRetries) {
                            await new Promise(resolve => setTimeout(resolve, delay));
                        }
                    }
                }

                throw new Error(`Failed after ${maxRetries} attempts: ${lastError.message}`);
            };

            // Crear datos
            for (let i = 0; i < 5; i++) {
                store.addElement({ type: 'element', index: i });
            }

            const startTime = Date.now();
            const result = await saveWithBackoff(5, 50);
            const totalTime = Date.now() - startTime;

            // Verificar que los delays siguen patron exponencial
            const delaysFollowPattern = retryAttempts.every((attempt, index) => {
                const expectedDelay = 50 * Math.pow(2, index);
                return attempt.delayMs === expectedDelay;
            });

            // Verificar que hubo el numero correcto de reintentos
            const correctRetryCount = retryAttempts.length === successOnAttempt - 1;

            const metrics = {
                totalAttempts: result.attempt,
                retriesBeforeSuccess: retryAttempts.length,
                totalTimeMs: totalTime,
                delaysFollowPattern,
                correctRetryCount,
                retryAttempts: retryAttempts.map(a => ({
                    attempt: a.attempt,
                    delayMs: a.delayMs
                })),
                success: result.success
            };

            store.reset();

            const passed = result.success &&
                delaysFollowPattern &&
                correctRetryCount;

            return {
                passed,
                metrics,
                message: passed
                    ? `Exito en intento ${result.attempt} con backoff exponencial`
                    : 'Patron de reintentos incorrecto'
            };
        }
    }
};

/**
 * Runner de tests de recuperacion
 */
class ErrorRecoveryTestRunner {
    constructor(options = {}) {
        this.options = {
            verbose: options.verbose || false,
            stopOnFailure: options.stopOnFailure || false
        };
    }

    async runTest(testId) {
        const test = ERROR_RECOVERY_TESTS[testId];
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
        console.log('VBP Error Recovery Tests');
        console.log('========================\n');

        const results = [];

        for (const testId of Object.keys(ERROR_RECOVERY_TESTS)) {
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
        const total = results.length;

        console.log('\n========================');
        console.log('Summary');
        console.log('========================');
        console.log(`Passed: ${passed}/${total}`);

        return {
            total,
            passed,
            failed: total - passed,
            successRate: Math.round((passed / total) * 100),
            results
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ERROR_RECOVERY_TESTS,
        ErrorRecoveryTestRunner,
        RecoveryMockStore,
        NetworkErrorSimulator
    };
}

if (typeof window !== 'undefined') {
    window.VBPErrorRecovery = {
        ERROR_RECOVERY_TESTS,
        ErrorRecoveryTestRunner,
        RecoveryMockStore,
        NetworkErrorSimulator
    };
}
