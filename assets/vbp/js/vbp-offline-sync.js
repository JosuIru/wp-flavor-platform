/**
 * Visual Builder Pro - Offline Sync Module
 *
 * Gestiona el estado offline/online del editor y sincroniza
 * cambios pendientes cuando se recupera la conexion.
 *
 * Integra con:
 * - Alpine.js store para estado reactivo
 * - IndexedDB para persistencia
 * - Service Worker para background sync
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Configuracion del modulo offline
     */
    const OFFLINE_CONFIG = {
        // Intervalo de verificacion de conexion (ms)
        connectionCheckInterval: 30000,

        // Tiempo minimo entre intentos de sync (ms)
        syncCooldown: 5000,

        // Maximo de intentos de sync por cambio
        maxSyncAttempts: 5,

        // Tiempo de espera antes de marcar como offline (ms)
        offlineDebounce: 2000,

        // Endpoints para verificar conexion
        healthCheckEndpoints: [
            '/wp-json/flavor-vbp/v1/claude/status',
            '/wp-admin/admin-ajax.php',
        ],
    };

    /**
     * Tipos de acciones que se pueden sincronizar
     */
    const SYNC_ACTIONS = {
        SAVE: 'save',
        PUBLISH: 'publish',
        AUTOSAVE: 'autosave',
        DELETE: 'delete',
        UPDATE_SETTINGS: 'update_settings',
    };

    /**
     * Prioridades de sincronizacion
     */
    const SYNC_PRIORITY = {
        [SYNC_ACTIONS.PUBLISH]: 10,
        [SYNC_ACTIONS.SAVE]: 8,
        [SYNC_ACTIONS.DELETE]: 6,
        [SYNC_ACTIONS.AUTOSAVE]: 4,
        [SYNC_ACTIONS.UPDATE_SETTINGS]: 2,
    };

    /**
     * Registra el store de Alpine.js para estado offline
     */
    function registerOfflineStore() {
        if (typeof Alpine === 'undefined') {
            console.warn('[VBP Offline] Alpine.js not available, store not registered');
            return;
        }

        Alpine.store('vbpOffline', {
            // ==========================================
            // ESTADO
            // ==========================================

            /** Estado de conexion actual */
            isOnline: navigator.onLine,

            /** Indica si estamos sincronizando */
            isSyncing: false,

            /** Numero de cambios pendientes */
            pendingCount: 0,

            /** Lista de cambios pendientes (para UI) */
            pendingChanges: [],

            /** Timestamp del ultimo sync exitoso */
            lastSyncTime: null,

            /** Error del ultimo intento de sync */
            lastSyncError: null,

            /** Service Worker registrado */
            serviceWorkerReady: false,

            /** Indica si hay conflictos sin resolver */
            hasConflicts: false,

            /** Lista de conflictos */
            conflicts: [],

            /** Estado del Service Worker */
            swStatus: 'checking',

            // ==========================================
            // INICIALIZACION
            // ==========================================

            /**
             * Inicializa el modulo offline
             */
            async init() {
                console.log('[VBP Offline] Initializing offline module...');

                // Verificar soporte
                if (!this.checkSupport()) {
                    console.warn('[VBP Offline] Offline features not fully supported');
                    return;
                }

                // Configurar listeners de conexion
                this.setupConnectionListeners();

                // Registrar Service Worker
                await this.registerServiceWorker();

                // Cargar estado inicial desde IndexedDB
                await this.loadPendingChanges();

                // Escuchar mensajes del Service Worker
                this.setupServiceWorkerMessages();

                // Si estamos online, intentar sync inicial
                if (this.isOnline) {
                    this.syncPendingChanges();
                }

                console.log('[VBP Offline] Offline module initialized');
            },

            /**
             * Verifica soporte de funcionalidades offline
             */
            checkSupport() {
                const hasIndexedDB = 'indexedDB' in window;
                const hasServiceWorker = 'serviceWorker' in navigator;
                const hasBackgroundSync = 'SyncManager' in window;

                console.log('[VBP Offline] Support check:', {
                    indexedDB: hasIndexedDB,
                    serviceWorker: hasServiceWorker,
                    backgroundSync: hasBackgroundSync,
                });

                return hasIndexedDB;
            },

            // ==========================================
            // CONNECTION MANAGEMENT
            // ==========================================

            /** Referencias a handlers para cleanup */
            _onlineHandler: null,
            _offlineHandler: null,
            _connectionCheckIntervalId: null,

            /**
             * Configura listeners de eventos de conexion
             */
            setupConnectionListeners() {
                // Guardar referencias para poder eliminar listeners después
                this._onlineHandler = () => this.handleOnline();
                this._offlineHandler = () => this.handleOffline();

                // Eventos nativos de conexion
                window.addEventListener('online', this._onlineHandler);
                window.addEventListener('offline', this._offlineHandler);

                // Verificacion periodica de conexion real (guardar ID para cleanup)
                this._connectionCheckIntervalId = setInterval(() => {
                    this.verifyConnection();
                }, OFFLINE_CONFIG.connectionCheckInterval);
            },

            /**
             * Limpia todos los listeners y timers (llamar al destruir)
             */
            destroy() {
                // Remover event listeners
                if (this._onlineHandler) {
                    window.removeEventListener('online', this._onlineHandler);
                    this._onlineHandler = null;
                }
                if (this._offlineHandler) {
                    window.removeEventListener('offline', this._offlineHandler);
                    this._offlineHandler = null;
                }

                // Limpiar interval
                if (this._connectionCheckIntervalId) {
                    clearInterval(this._connectionCheckIntervalId);
                    this._connectionCheckIntervalId = null;
                }

                console.log('[VBP Offline] Module destroyed and cleaned up');
            },

            /**
             * Maneja el evento de volver online
             */
            async handleOnline() {
                console.log('[VBP Offline] Connection restored');

                // Verificar que realmente hay conexion
                const hasRealConnection = await this.verifyConnection();

                if (hasRealConnection) {
                    this.isOnline = true;
                    this.showConnectionNotification('online');

                    // Iniciar sincronizacion
                    this.syncPendingChanges();
                }
            },

            /**
             * Maneja el evento de perder conexion
             */
            handleOffline() {
                console.log('[VBP Offline] Connection lost');
                this.isOnline = false;
                this.showConnectionNotification('offline');
            },

            /**
             * Verifica la conexion real haciendo un request
             */
            async verifyConnection() {
                if (!navigator.onLine) {
                    this.isOnline = false;
                    return false;
                }

                try {
                    // Usar el endpoint de health check
                    const healthEndpoint = window.vbpConfig?.restUrl || '/wp-json/flavor-vbp/v1/';
                    const statusUrl = `${healthEndpoint}claude/status`;

                    const response = await fetch(statusUrl, {
                        method: 'HEAD',
                        cache: 'no-store',
                        headers: {
                            'X-VBP-Connection-Check': '1',
                        },
                    });

                    this.isOnline = response.ok;
                    return response.ok;
                } catch (error) {
                    console.log('[VBP Offline] Connection check failed:', error.message);
                    this.isOnline = false;
                    return false;
                }
            },

            /**
             * Muestra notificacion de cambio de estado de conexion
             */
            showConnectionNotification(connectionStatus) {
                // Usar el sistema de toast de VBP si esta disponible
                const toastStore = Alpine.store('vbpToast');
                if (toastStore) {
                    if (connectionStatus === 'online') {
                        toastStore.show({
                            message: 'Conexion restaurada',
                            type: 'success',
                            duration: 3000,
                            icon: 'wifi',
                        });

                        if (this.pendingCount > 0) {
                            toastStore.show({
                                message: `Sincronizando ${this.pendingCount} cambio(s) pendiente(s)...`,
                                type: 'info',
                                duration: 4000,
                                icon: 'sync',
                            });
                        }
                    } else {
                        toastStore.show({
                            message: 'Sin conexion - Los cambios se guardaran localmente',
                            type: 'warning',
                            duration: 5000,
                            icon: 'wifi_off',
                        });
                    }
                }

                // Emitir evento personalizado
                window.dispatchEvent(new CustomEvent('vbp:connection-change', {
                    detail: { isOnline: connectionStatus === 'online' },
                }));
            },

            // ==========================================
            // SERVICE WORKER
            // ==========================================

            /**
             * Registra el Service Worker
             */
            async registerServiceWorker() {
                if (!('serviceWorker' in navigator)) {
                    this.swStatus = 'unsupported';
                    return;
                }

                try {
                    // Construir URL del Service Worker
                    const pluginUrl = window.vbpConfig?.assetsUrl || '/wp-content/plugins/flavor-platform/assets/vbp/';
                    const swUrl = `${pluginUrl}js/vbp-service-worker.js`;

                    // El SW solo puede controlar páginas bajo su directorio
                    // Por seguridad del navegador, no podemos usar scope '/wp-admin/'
                    // Registramos sin scope para usar el default (directorio del SW)
                    const registration = await navigator.serviceWorker.register(swUrl);

                    console.log('[VBP Offline] Service Worker registered:', registration.scope);

                    // Manejar actualizaciones
                    registration.addEventListener('updatefound', () => {
                        const installingWorker = registration.installing;
                        console.log('[VBP Offline] New Service Worker installing...');

                        installingWorker.addEventListener('statechange', () => {
                            if (installingWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    // Hay una version anterior, notificar update
                                    this.notifyServiceWorkerUpdate();
                                }
                            }
                        });
                    });

                    this.serviceWorkerReady = true;
                    this.swStatus = 'active';
                } catch (error) {
                    console.error('[VBP Offline] Service Worker registration failed:', error);
                    this.swStatus = 'error';
                }
            },

            /**
             * Configura la escucha de mensajes del Service Worker
             */
            setupServiceWorkerMessages() {
                if (!('serviceWorker' in navigator)) return;

                navigator.serviceWorker.addEventListener('message', (event) => {
                    const { type, timestamp } = event.data || {};

                    switch (type) {
                        case 'SYNC_REQUIRED':
                            console.log('[VBP Offline] Sync required by SW');
                            this.syncPendingChanges();
                            break;

                        case 'CACHE_UPDATED':
                            console.log('[VBP Offline] Cache updated');
                            break;
                    }
                });
            },

            /**
             * Notifica que hay una actualizacion del Service Worker
             */
            notifyServiceWorkerUpdate() {
                const toastStore = Alpine.store('vbpToast');
                if (toastStore) {
                    toastStore.show({
                        message: 'Nueva version disponible. Recarga para actualizar.',
                        type: 'info',
                        duration: 0, // Persistente
                        icon: 'update',
                        actions: [
                            {
                                label: 'Recargar',
                                callback: () => window.location.reload(),
                            },
                        ],
                    });
                }
            },

            // ==========================================
            // LOCAL STORAGE
            // ==========================================

            /**
             * Guarda cambios localmente
             * @param {number} postId
             * @param {Object} pageData
             * @param {Object} options
             */
            async saveLocal(postId, pageData, options = {}) {
                if (!window.VBP_DB) {
                    console.error('[VBP Offline] IndexedDB not available');
                    return false;
                }

                try {
                    // Guardar pagina en IndexedDB
                    await VBP_DB.savePage(postId, pageData, {
                        title: options.title || '',
                        status: options.status || 'draft',
                    });

                    // Agregar a cola de sincronizacion
                    const actionType = options.action || SYNC_ACTIONS.SAVE;
                    const actionPriority = SYNC_PRIORITY[actionType] || 5;

                    await VBP_DB.addPendingChange(actionType, postId, {
                        pageData,
                        title: options.title,
                        status: options.status,
                    }, actionPriority);

                    // Actualizar contador
                    await this.loadPendingChanges();

                    console.log(`[VBP Offline] Saved locally: post ${postId}`);
                    return true;
                } catch (error) {
                    console.error('[VBP Offline] Error saving locally:', error);
                    return false;
                }
            },

            /**
             * Carga cambios pendientes desde IndexedDB
             */
            async loadPendingChanges() {
                if (!window.VBP_DB) return;

                try {
                    this.pendingCount = await VBP_DB.getPendingCount();
                    this.pendingChanges = await VBP_DB.getPendingChanges();
                } catch (error) {
                    console.error('[VBP Offline] Error loading pending changes:', error);
                }
            },

            /**
             * Obtiene la version local de una pagina
             * @param {number} postId
             */
            async getLocalPage(postId) {
                if (!window.VBP_DB) return null;

                try {
                    return await VBP_DB.getPage(postId);
                } catch (error) {
                    console.error('[VBP Offline] Error getting local page:', error);
                    return null;
                }
            },

            // ==========================================
            // SINCRONIZACION
            // ==========================================

            /**
             * Sincroniza todos los cambios pendientes
             */
            async syncPendingChanges() {
                if (!this.isOnline || this.isSyncing) {
                    console.log('[VBP Offline] Sync skipped:', { online: this.isOnline, syncing: this.isSyncing });
                    return;
                }

                if (this.pendingCount === 0) {
                    console.log('[VBP Offline] No pending changes to sync');
                    return;
                }

                this.isSyncing = true;
                this.lastSyncError = null;

                console.log(`[VBP Offline] Starting sync of ${this.pendingCount} changes...`);

                try {
                    const pendingItems = await VBP_DB.getPendingChanges();
                    let syncedCount = 0;
                    let failedCount = 0;

                    for (const pendingChange of pendingItems) {
                        // Verificar si debemos reintentar
                        if (pendingChange.attempts >= OFFLINE_CONFIG.maxSyncAttempts) {
                            console.warn(`[VBP Offline] Max attempts reached for change ${pendingChange.id}`);
                            failedCount++;
                            continue;
                        }

                        try {
                            await this.syncSingleChange(pendingChange);
                            await VBP_DB.removePendingChange(pendingChange.id);
                            syncedCount++;
                        } catch (syncError) {
                            console.error(`[VBP Offline] Sync failed for change ${pendingChange.id}:`, syncError);

                            // Incrementar intentos
                            await VBP_DB.updatePendingChange(pendingChange.id, {
                                attempts: pendingChange.attempts + 1,
                                last_attempt: Date.now(),
                                error: syncError.message,
                            });

                            failedCount++;

                            // Si es error de conflicto, agregar a lista de conflictos
                            if (syncError.conflict) {
                                this.addConflict(pendingChange, syncError.serverVersion);
                            }
                        }
                    }

                    // Actualizar estado
                    await this.loadPendingChanges();
                    this.lastSyncTime = Date.now();

                    console.log(`[VBP Offline] Sync completed: ${syncedCount} synced, ${failedCount} failed`);

                    // Notificar resultado
                    if (syncedCount > 0) {
                        this.showSyncNotification(syncedCount, failedCount);
                    }

                } catch (error) {
                    console.error('[VBP Offline] Sync error:', error);
                    this.lastSyncError = error.message;
                } finally {
                    this.isSyncing = false;
                }
            },

            /**
             * Sincroniza un cambio individual
             * @param {Object} pendingChange
             */
            async syncSingleChange(pendingChange) {
                const { action, post_id: postId, data: changeData } = pendingChange;

                // Construir URL y payload segun accion
                let endpoint = '';
                let method = 'POST';
                let requestPayload = {};

                switch (action) {
                    case SYNC_ACTIONS.SAVE:
                    case SYNC_ACTIONS.AUTOSAVE:
                        endpoint = `pages/${postId}`;
                        method = 'PUT';
                        requestPayload = {
                            content: changeData.pageData,
                            title: changeData.title,
                        };
                        break;

                    case SYNC_ACTIONS.PUBLISH:
                        endpoint = `pages/${postId}/publish`;
                        method = 'POST';
                        requestPayload = {
                            content: changeData.pageData,
                        };
                        break;

                    case SYNC_ACTIONS.DELETE:
                        endpoint = `pages/${postId}`;
                        method = 'DELETE';
                        break;

                    default:
                        throw new Error(`Unknown action: ${action}`);
                }

                // Hacer request
                const baseUrl = window.vbpConfig?.restUrl || '/wp-json/flavor-vbp/v1/';
                const fullUrl = `${baseUrl}claude/${endpoint}`;

                const response = await fetch(fullUrl, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.vbpConfig?.restNonce || '',
                        'X-VBP-Offline-Sync': '1',
                    },
                    body: method !== 'DELETE' ? JSON.stringify(requestPayload) : undefined,
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));

                    // Detectar conflicto de versiones
                    if (response.status === 409) {
                        const conflictError = new Error('Version conflict');
                        conflictError.conflict = true;
                        conflictError.serverVersion = errorData.server_version;
                        throw conflictError;
                    }

                    throw new Error(errorData.message || `HTTP ${response.status}`);
                }

                // Marcar pagina como sincronizada
                await VBP_DB.markPageSynced(postId);

                return await response.json();
            },

            /**
             * Muestra notificacion de resultado de sync
             */
            showSyncNotification(syncedCount, failedCount) {
                const toastStore = Alpine.store('vbpToast');
                if (!toastStore) return;

                if (failedCount === 0) {
                    toastStore.show({
                        message: `${syncedCount} cambio(s) sincronizado(s)`,
                        type: 'success',
                        duration: 3000,
                        icon: 'cloud_done',
                    });
                } else {
                    toastStore.show({
                        message: `${syncedCount} sincronizado(s), ${failedCount} fallido(s)`,
                        type: 'warning',
                        duration: 5000,
                        icon: 'sync_problem',
                    });
                }
            },

            // ==========================================
            // CONFLICTOS
            // ==========================================

            /**
             * Agrega un conflicto a la lista
             * @param {Object} pendingChange
             * @param {Object} serverVersion
             */
            addConflict(pendingChange, serverVersion) {
                this.conflicts.push({
                    id: `conflict_${Date.now()}`,
                    pendingChange,
                    serverVersion,
                    createdAt: Date.now(),
                });
                this.hasConflicts = true;
            },

            /**
             * Resuelve un conflicto eligiendo una version
             * @param {string} conflictId
             * @param {string} resolution - 'local' o 'server'
             */
            async resolveConflict(conflictId, resolution) {
                const conflictIndex = this.conflicts.findIndex((conflictItem) => conflictItem.id === conflictId);
                if (conflictIndex === -1) return;

                const conflictData = this.conflicts[conflictIndex];

                if (resolution === 'local') {
                    // Forzar sync de version local
                    try {
                        await this.forceSyncChange(conflictData.pendingChange);
                    } catch (error) {
                        console.error('[VBP Offline] Force sync failed:', error);
                        return;
                    }
                } else {
                    // Descartar version local y cargar la del servidor
                    await VBP_DB.removePendingChange(conflictData.pendingChange.id);
                    await VBP_DB.deletePage(conflictData.pendingChange.post_id);

                    // Notificar al store principal para recargar
                    window.dispatchEvent(new CustomEvent('vbp:reload-page', {
                        detail: { postId: conflictData.pendingChange.post_id },
                    }));
                }

                // Eliminar conflicto de la lista
                this.conflicts.splice(conflictIndex, 1);
                this.hasConflicts = this.conflicts.length > 0;

                // Recargar pendientes
                await this.loadPendingChanges();
            },

            /**
             * Fuerza la sincronizacion de un cambio ignorando conflictos
             * @param {Object} pendingChange
             */
            async forceSyncChange(pendingChange) {
                const { action, post_id: postId, data: changeData } = pendingChange;

                const baseUrl = window.vbpConfig?.restUrl || '/wp-json/flavor-vbp/v1/';
                const fullUrl = `${baseUrl}claude/pages/${postId}`;

                const response = await fetch(fullUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.vbpConfig?.restNonce || '',
                        'X-VBP-Force-Sync': '1',
                    },
                    body: JSON.stringify({
                        content: changeData.pageData,
                        title: changeData.title,
                        force: true,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Force sync failed');
                }

                await VBP_DB.removePendingChange(pendingChange.id);
                await VBP_DB.markPageSynced(postId);
            },

            /**
             * Muestra el modal de resolucion de conflictos
             */
            showConflictModal() {
                // Usar el sistema de modales de VBP
                const modalsStore = Alpine.store('vbpModals');
                if (modalsStore) {
                    modalsStore.show('conflict-resolution');
                }
            },

            // ==========================================
            // UTILIDADES
            // ==========================================

            /**
             * Limpia todos los datos offline
             */
            async clearOfflineData() {
                if (window.VBP_DB) {
                    await VBP_DB.clearAll();
                }
                this.pendingCount = 0;
                this.pendingChanges = [];
                this.conflicts = [];
                this.hasConflicts = false;
            },

            /**
             * Obtiene estadisticas de almacenamiento
             */
            async getStorageStats() {
                if (window.VBP_DB) {
                    return await VBP_DB.getStorageStats();
                }
                return null;
            },

            /**
             * Registra un background sync (si es soportado)
             */
            async requestBackgroundSync() {
                if ('serviceWorker' in navigator && 'SyncManager' in window) {
                    const registration = await navigator.serviceWorker.ready;
                    try {
                        await registration.sync.register('vbp-sync-pending');
                        console.log('[VBP Offline] Background sync registered');
                    } catch (error) {
                        console.warn('[VBP Offline] Background sync not available:', error);
                    }
                }
            },
        });
    }

    // ==========================================================================
    // INICIALIZACION
    // ==========================================================================

    /**
     * Inicializa el modulo cuando Alpine esta listo
     */
    function initializeOfflineModule() {
        if (typeof Alpine !== 'undefined') {
            // Alpine ya esta cargado, registrar store
            if (document.readyState === 'loading') {
                document.addEventListener('alpine:init', registerOfflineStore);
            } else {
                registerOfflineStore();
            }
        } else {
            // Esperar a que Alpine este disponible
            document.addEventListener('alpine:init', registerOfflineStore);
        }
    }

    // Inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeOfflineModule);
    } else {
        initializeOfflineModule();
    }

    // Exponer API publica
    window.VBP_Offline = {
        ACTIONS: SYNC_ACTIONS,
        PRIORITY: SYNC_PRIORITY,
        CONFIG: OFFLINE_CONFIG,

        /**
         * Guarda cambios localmente (shortcut)
         */
        async saveLocal(postId, pageData, options) {
            const offlineStore = Alpine.store('vbpOffline');
            if (offlineStore) {
                return offlineStore.saveLocal(postId, pageData, options);
            }
            return false;
        },

        /**
         * Fuerza sincronizacion manual
         */
        async sync() {
            const offlineStore = Alpine.store('vbpOffline');
            if (offlineStore) {
                return offlineStore.syncPendingChanges();
            }
        },

        /**
         * Verifica estado de conexion
         */
        isOnline() {
            const offlineStore = Alpine.store('vbpOffline');
            return offlineStore ? offlineStore.isOnline : navigator.onLine;
        },

        /**
         * Obtiene numero de cambios pendientes
         */
        getPendingCount() {
            const offlineStore = Alpine.store('vbpOffline');
            return offlineStore ? offlineStore.pendingCount : 0;
        },
    };

})();
