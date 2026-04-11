/**
 * Visual Builder Pro - IndexedDB Module
 *
 * Proporciona almacenamiento local persistente para:
 * - Paginas en edicion (datos del builder)
 * - Assets cacheados (imagenes, fuentes)
 * - Cambios pendientes de sincronizacion
 * - Configuraciones del editor
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Configuracion de la base de datos
     */
    const DATABASE_CONFIG = {
        name: 'vbp-offline',
        version: 1,
    };

    /**
     * Definicion de stores (tablas)
     */
    const STORES = {
        // Paginas guardadas localmente
        pages: {
            keyPath: 'id',
            indexes: [
                { name: 'post_id', keyPath: 'post_id', options: { unique: false } },
                { name: 'updated_at', keyPath: 'updated_at', options: { unique: false } },
                { name: 'synced', keyPath: 'synced', options: { unique: false } },
            ],
        },

        // Assets cacheados (blobs de imagenes)
        assets: {
            keyPath: 'id',
            indexes: [
                { name: 'url', keyPath: 'url', options: { unique: true } },
                { name: 'type', keyPath: 'type', options: { unique: false } },
                { name: 'cached_at', keyPath: 'cached_at', options: { unique: false } },
            ],
        },

        // Cola de cambios pendientes
        pending: {
            keyPath: 'id',
            autoIncrement: true,
            indexes: [
                { name: 'action', keyPath: 'action', options: { unique: false } },
                { name: 'post_id', keyPath: 'post_id', options: { unique: false } },
                { name: 'created_at', keyPath: 'created_at', options: { unique: false } },
                { name: 'priority', keyPath: 'priority', options: { unique: false } },
            ],
        },

        // Configuraciones del editor
        settings: {
            keyPath: 'key',
        },

        // Historial de versiones local
        versions: {
            keyPath: 'id',
            indexes: [
                { name: 'post_id', keyPath: 'post_id', options: { unique: false } },
                { name: 'created_at', keyPath: 'created_at', options: { unique: false } },
            ],
        },
    };

    /**
     * Clase principal de IndexedDB para VBP
     */
    class VBPIndexedDB {
        constructor() {
            this.databaseInstance = null;
            this.isSupported = this.checkSupport();
            this.initPromise = null;
        }

        /**
         * Verifica si IndexedDB esta soportado
         */
        checkSupport() {
            return 'indexedDB' in window;
        }

        /**
         * Inicializa la conexion a la base de datos
         * @returns {Promise<IDBDatabase>}
         */
        async open() {
            if (!this.isSupported) {
                throw new Error('IndexedDB is not supported in this browser');
            }

            // Si ya hay una inicializacion en progreso, esperar
            if (this.initPromise) {
                return this.initPromise;
            }

            // Si ya esta abierta, devolver la instancia
            if (this.databaseInstance) {
                return this.databaseInstance;
            }

            this.initPromise = new Promise((resolve, reject) => {
                const request = indexedDB.open(DATABASE_CONFIG.name, DATABASE_CONFIG.version);

                request.onerror = (event) => {
                    console.error('[VBP IDB] Error opening database:', event.target.error);
                    this.initPromise = null;
                    reject(event.target.error);
                };

                request.onsuccess = (event) => {
                    this.databaseInstance = event.target.result;
                    console.log('[VBP IDB] Database opened successfully');

                    // Manejar cierre inesperado
                    this.databaseInstance.onclose = () => {
                        console.warn('[VBP IDB] Database connection closed unexpectedly');
                        this.databaseInstance = null;
                        this.initPromise = null;
                    };

                    this.initPromise = null;
                    resolve(this.databaseInstance);
                };

                request.onupgradeneeded = (event) => {
                    console.log('[VBP IDB] Upgrading database schema...');
                    const databaseReference = event.target.result;
                    this.createStores(databaseReference);
                };
            });

            return this.initPromise;
        }

        /**
         * Crea los object stores durante la migracion
         * @param {IDBDatabase} databaseReference
         */
        createStores(databaseReference) {
            for (const [storeName, storeConfig] of Object.entries(STORES)) {
                // Eliminar store existente si hay upgrade
                if (databaseReference.objectStoreNames.contains(storeName)) {
                    databaseReference.deleteObjectStore(storeName);
                }

                // Crear store
                const storeOptions = {
                    keyPath: storeConfig.keyPath,
                };
                if (storeConfig.autoIncrement) {
                    storeOptions.autoIncrement = true;
                }

                const objectStore = databaseReference.createObjectStore(storeName, storeOptions);

                // Crear indices
                if (storeConfig.indexes) {
                    for (const indexConfig of storeConfig.indexes) {
                        objectStore.createIndex(
                            indexConfig.name,
                            indexConfig.keyPath,
                            indexConfig.options
                        );
                    }
                }

                console.log(`[VBP IDB] Created store: ${storeName}`);
            }
        }

        /**
         * Obtiene una transaccion para un store
         * @param {string} storeName
         * @param {string} mode - 'readonly' o 'readwrite'
         * @returns {Promise<IDBObjectStore>}
         */
        async getStore(storeName, mode = 'readonly') {
            const databaseReference = await this.open();
            const transaction = databaseReference.transaction([storeName], mode);
            return transaction.objectStore(storeName);
        }

        /**
         * Guarda un registro en un store
         * @param {string} storeName
         * @param {Object} data
         * @returns {Promise<any>} - Key del registro
         */
        async save(storeName, data) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readwrite');
                    const request = objectStore.put(data);

                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Obtiene un registro por su key
         * @param {string} storeName
         * @param {any} key
         * @returns {Promise<Object|undefined>}
         */
        async get(storeName, key) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readonly');
                    const request = objectStore.get(key);

                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Obtiene todos los registros de un store
         * @param {string} storeName
         * @returns {Promise<Array>}
         */
        async getAll(storeName) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readonly');
                    const request = objectStore.getAll();

                    request.onsuccess = () => resolve(request.result || []);
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Busca registros por indice
         * @param {string} storeName
         * @param {string} indexName
         * @param {any} value
         * @returns {Promise<Array>}
         */
        async getByIndex(storeName, indexName, value) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readonly');
                    const index = objectStore.index(indexName);
                    const request = index.getAll(value);

                    request.onsuccess = () => resolve(request.result || []);
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Elimina un registro por su key
         * @param {string} storeName
         * @param {any} key
         * @returns {Promise<void>}
         */
        async delete(storeName, key) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readwrite');
                    const request = objectStore.delete(key);

                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Limpia todos los registros de un store
         * @param {string} storeName
         * @returns {Promise<void>}
         */
        async clear(storeName) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readwrite');
                    const request = objectStore.clear();

                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Cuenta registros en un store
         * @param {string} storeName
         * @returns {Promise<number>}
         */
        async count(storeName) {
            return new Promise(async (resolve, reject) => {
                try {
                    const objectStore = await this.getStore(storeName, 'readonly');
                    const request = objectStore.count();

                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error);
                } catch (error) {
                    reject(error);
                }
            });
        }

        /**
         * Cierra la conexion a la base de datos
         */
        close() {
            if (this.databaseInstance) {
                this.databaseInstance.close();
                this.databaseInstance = null;
            }
        }

        /**
         * Elimina completamente la base de datos
         * @returns {Promise<void>}
         */
        async deleteDatabase() {
            this.close();

            return new Promise((resolve, reject) => {
                const request = indexedDB.deleteDatabase(DATABASE_CONFIG.name);

                request.onsuccess = () => {
                    console.log('[VBP IDB] Database deleted');
                    resolve();
                };
                request.onerror = () => reject(request.error);
                request.onblocked = () => {
                    console.warn('[VBP IDB] Database deletion blocked - close all connections');
                    reject(new Error('Database deletion blocked'));
                };
            });
        }
    }

    // ==========================================================================
    // API DE ALTO NIVEL PARA VBP
    // ==========================================================================

    /**
     * Instancia singleton
     */
    const databaseInstance = new VBPIndexedDB();

    /**
     * API publica de VBP IndexedDB
     */
    window.VBP_DB = {
        /**
         * Referencia a la instancia de la clase
         */
        instance: databaseInstance,

        /**
         * Verifica si IndexedDB esta soportado
         */
        isSupported: databaseInstance.isSupported,

        /**
         * Inicializa la base de datos
         */
        async init() {
            if (!this.isSupported) {
                console.warn('[VBP IDB] IndexedDB not supported');
                return false;
            }

            try {
                await databaseInstance.open();
                return true;
            } catch (error) {
                console.error('[VBP IDB] Initialization failed:', error);
                return false;
            }
        },

        // ======================================================================
        // PAGES - Almacenamiento de paginas en edicion
        // ======================================================================

        /**
         * Guarda una pagina localmente
         * @param {number} postId
         * @param {Object} pageData - Datos del builder
         * @param {Object} metadata - Metadatos adicionales
         */
        async savePage(postId, pageData, metadata = {}) {
            const record = {
                id: `page_${postId}`,
                post_id: postId,
                data: pageData,
                title: metadata.title || '',
                status: metadata.status || 'draft',
                synced: false,
                updated_at: Date.now(),
                created_at: metadata.created_at || Date.now(),
            };

            return databaseInstance.save('pages', record);
        },

        /**
         * Obtiene una pagina guardada localmente
         * @param {number} postId
         */
        async getPage(postId) {
            return databaseInstance.get('pages', `page_${postId}`);
        },

        /**
         * Obtiene todas las paginas no sincronizadas
         */
        async getUnsyncedPages() {
            return databaseInstance.getByIndex('pages', 'synced', false);
        },

        /**
         * Marca una pagina como sincronizada
         * @param {number} postId
         */
        async markPageSynced(postId) {
            const existingPage = await this.getPage(postId);
            if (existingPage) {
                existingPage.synced = true;
                existingPage.synced_at = Date.now();
                return databaseInstance.save('pages', existingPage);
            }
        },

        /**
         * Elimina una pagina local
         * @param {number} postId
         */
        async deletePage(postId) {
            return databaseInstance.delete('pages', `page_${postId}`);
        },

        // ======================================================================
        // PENDING - Cola de cambios pendientes
        // ======================================================================

        /**
         * Agrega un cambio a la cola de pendientes
         * @param {string} action - Tipo de accion (save, publish, delete, etc.)
         * @param {number} postId
         * @param {Object} data - Datos del cambio
         * @param {number} priority - Prioridad (mayor = mas urgente)
         */
        async addPendingChange(action, postId, data, priority = 1) {
            const record = {
                action,
                post_id: postId,
                data,
                priority,
                created_at: Date.now(),
                attempts: 0,
                last_attempt: null,
                error: null,
            };

            return databaseInstance.save('pending', record);
        },

        /**
         * Obtiene todos los cambios pendientes ordenados por prioridad
         */
        async getPendingChanges() {
            const allPending = await databaseInstance.getAll('pending');

            // Ordenar por prioridad (mayor primero) y luego por fecha
            return allPending.sort((changeA, changeB) => {
                if (changeB.priority !== changeA.priority) {
                    return changeB.priority - changeA.priority;
                }
                return changeA.created_at - changeB.created_at;
            });
        },

        /**
         * Obtiene el numero de cambios pendientes
         */
        async getPendingCount() {
            return databaseInstance.count('pending');
        },

        /**
         * Marca un cambio como procesado (elimina de la cola)
         * @param {number} pendingId
         */
        async removePendingChange(pendingId) {
            return databaseInstance.delete('pending', pendingId);
        },

        /**
         * Actualiza un cambio pendiente (ej: incrementar intentos)
         * @param {number} pendingId
         * @param {Object} updates
         */
        async updatePendingChange(pendingId, updates) {
            const existingChange = await databaseInstance.get('pending', pendingId);
            if (existingChange) {
                Object.assign(existingChange, updates);
                return databaseInstance.save('pending', existingChange);
            }
        },

        /**
         * Limpia todos los cambios pendientes
         */
        async clearPendingChanges() {
            return databaseInstance.clear('pending');
        },

        // ======================================================================
        // ASSETS - Cache de imagenes y recursos
        // ======================================================================

        /**
         * Cachea un asset (imagen, etc.) como blob
         * @param {string} url
         * @param {Blob} blob
         * @param {string} type - Tipo de asset (image, font, etc.)
         */
        async cacheAsset(url, blob, type = 'image') {
            const record = {
                id: this.generateAssetId(url),
                url,
                blob,
                type,
                size: blob.size,
                cached_at: Date.now(),
            };

            return databaseInstance.save('assets', record);
        },

        /**
         * Obtiene un asset cacheado
         * @param {string} url
         */
        async getAsset(url) {
            const assetId = this.generateAssetId(url);
            return databaseInstance.get('assets', assetId);
        },

        /**
         * Elimina un asset cacheado
         * @param {string} url
         */
        async deleteAsset(url) {
            const assetId = this.generateAssetId(url);
            return databaseInstance.delete('assets', assetId);
        },

        /**
         * Genera un ID unico para un asset basado en su URL
         * @param {string} url
         */
        generateAssetId(url) {
            // Simple hash de la URL
            let hashValue = 0;
            for (let charIndex = 0; charIndex < url.length; charIndex++) {
                const charCode = url.charCodeAt(charIndex);
                hashValue = ((hashValue << 5) - hashValue) + charCode;
                hashValue = hashValue & hashValue;
            }
            return `asset_${Math.abs(hashValue)}`;
        },

        // ======================================================================
        // SETTINGS - Configuraciones del editor
        // ======================================================================

        /**
         * Guarda una configuracion
         * @param {string} key
         * @param {any} value
         */
        async saveSetting(key, value) {
            return databaseInstance.save('settings', {
                key,
                value,
                updated_at: Date.now(),
            });
        },

        /**
         * Obtiene una configuracion
         * @param {string} key
         * @param {any} defaultValue
         */
        async getSetting(key, defaultValue = null) {
            const settingRecord = await databaseInstance.get('settings', key);
            return settingRecord ? settingRecord.value : defaultValue;
        },

        /**
         * Elimina una configuracion
         * @param {string} key
         */
        async deleteSetting(key) {
            return databaseInstance.delete('settings', key);
        },

        // ======================================================================
        // VERSIONS - Historial local de versiones
        // ======================================================================

        /**
         * Guarda una version local
         * @param {number} postId
         * @param {Object} pageData
         * @param {string} label
         */
        async saveVersion(postId, pageData, label = '') {
            const versionId = `v_${postId}_${Date.now()}`;
            const record = {
                id: versionId,
                post_id: postId,
                data: pageData,
                label,
                created_at: Date.now(),
            };

            await databaseInstance.save('versions', record);

            // Limpiar versiones antiguas (mantener max 10 por pagina)
            await this.cleanOldVersions(postId, 10);

            return versionId;
        },

        /**
         * Obtiene las versiones locales de una pagina
         * @param {number} postId
         */
        async getVersions(postId) {
            const allVersions = await databaseInstance.getByIndex('versions', 'post_id', postId);
            return allVersions.sort((versionA, versionB) => versionB.created_at - versionA.created_at);
        },

        /**
         * Limpia versiones antiguas
         * @param {number} postId
         * @param {number} keepCount
         */
        async cleanOldVersions(postId, keepCount = 10) {
            const existingVersions = await this.getVersions(postId);
            if (existingVersions.length > keepCount) {
                const versionsToDelete = existingVersions.slice(keepCount);
                for (const versionRecord of versionsToDelete) {
                    await databaseInstance.delete('versions', versionRecord.id);
                }
            }
        },

        // ======================================================================
        // UTILIDADES
        // ======================================================================

        /**
         * Obtiene estadisticas de almacenamiento
         */
        async getStorageStats() {
            const stats = {
                pages: await databaseInstance.count('pages'),
                pending: await databaseInstance.count('pending'),
                assets: await databaseInstance.count('assets'),
                versions: await databaseInstance.count('versions'),
            };

            // Calcular tamano aproximado si es posible
            if (navigator.storage && navigator.storage.estimate) {
                const storageEstimate = await navigator.storage.estimate();
                stats.quota = storageEstimate.quota;
                stats.usage = storageEstimate.usage;
                stats.usagePercentage = Math.round((storageEstimate.usage / storageEstimate.quota) * 100);
            }

            return stats;
        },

        /**
         * Limpia todos los datos locales
         */
        async clearAll() {
            await databaseInstance.clear('pages');
            await databaseInstance.clear('pending');
            await databaseInstance.clear('assets');
            await databaseInstance.clear('versions');
            await databaseInstance.clear('settings');
            console.log('[VBP IDB] All data cleared');
        },

        /**
         * Exporta todos los datos para backup
         */
        async exportData() {
            return {
                pages: await databaseInstance.getAll('pages'),
                pending: await databaseInstance.getAll('pending'),
                settings: await databaseInstance.getAll('settings'),
                versions: await databaseInstance.getAll('versions'),
                exported_at: Date.now(),
            };
        },

        /**
         * Importa datos desde un backup
         * @param {Object} backupData
         */
        async importData(backupData) {
            if (backupData.pages) {
                for (const pageRecord of backupData.pages) {
                    await databaseInstance.save('pages', pageRecord);
                }
            }
            if (backupData.settings) {
                for (const settingRecord of backupData.settings) {
                    await databaseInstance.save('settings', settingRecord);
                }
            }
            console.log('[VBP IDB] Data imported');
        },
    };

    // Inicializar automaticamente si estamos en el editor
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            VBP_DB.init();
        });
    } else {
        VBP_DB.init();
    }

})();
