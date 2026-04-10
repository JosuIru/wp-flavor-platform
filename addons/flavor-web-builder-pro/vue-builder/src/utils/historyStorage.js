/**
 * Persistencia de historial en IndexedDB
 * Permite recuperar historial después de recargar la página
 */

const DB_NAME = 'FlavorPageBuilder';
const DB_VERSION = 1;
const STORE_NAME = 'history';

let dbInstance = null;

/**
 * Abre o crea la base de datos IndexedDB
 * @returns {Promise<IDBDatabase>}
 */
async function openDB() {
  if (dbInstance) return dbInstance;

  return new Promise((resolve, reject) => {
    if (!window.indexedDB) {
      reject(new Error('IndexedDB not supported'));
      return;
    }

    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);

    request.onsuccess = () => {
      dbInstance = request.result;
      resolve(dbInstance);
    };

    request.onupgradeneeded = (event) => {
      const db = event.target.result;

      // Crear object store para historial
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
        store.createIndex('postId', 'postId', { unique: false });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
    };
  });
}

/**
 * Genera key única para un post
 * @param {number} postId
 * @returns {string}
 */
function getHistoryKey(postId) {
  return `history_${postId}`;
}

/**
 * Guarda el historial en IndexedDB
 * @param {number} postId - ID del post
 * @param {Object} historyData - Datos del historial
 * @returns {Promise<void>}
 */
export async function saveHistory(postId, historyData) {
  try {
    const db = await openDB();
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);

    const record = {
      id: getHistoryKey(postId),
      postId,
      timestamp: Date.now(),
      checkpoints: historyData.checkpoints,
      diffs: historyData.diffs,
      currentIndex: historyData.currentIndex,
    };

    return new Promise((resolve, reject) => {
      const request = store.put(record);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.warn('Failed to save history to IndexedDB:', error);
  }
}

/**
 * Carga el historial desde IndexedDB
 * @param {number} postId - ID del post
 * @returns {Promise<Object|null>} Datos del historial o null
 */
export async function loadHistory(postId) {
  try {
    const db = await openDB();
    const transaction = db.transaction([STORE_NAME], 'readonly');
    const store = transaction.objectStore(STORE_NAME);

    return new Promise((resolve, reject) => {
      const request = store.get(getHistoryKey(postId));
      request.onsuccess = () => {
        const result = request.result;
        if (result) {
          // Verificar que no sea muy antiguo (24 horas)
          const maxAge = 24 * 60 * 60 * 1000;
          if (Date.now() - result.timestamp > maxAge) {
            // Historial expirado, eliminarlo
            deleteHistory(postId);
            resolve(null);
          } else {
            resolve({
              checkpoints: result.checkpoints,
              diffs: result.diffs,
              currentIndex: result.currentIndex,
            });
          }
        } else {
          resolve(null);
        }
      };
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.warn('Failed to load history from IndexedDB:', error);
    return null;
  }
}

/**
 * Elimina el historial de un post
 * @param {number} postId - ID del post
 * @returns {Promise<void>}
 */
export async function deleteHistory(postId) {
  try {
    const db = await openDB();
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);

    return new Promise((resolve, reject) => {
      const request = store.delete(getHistoryKey(postId));
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.warn('Failed to delete history from IndexedDB:', error);
  }
}

/**
 * Limpia historiales antiguos (más de 7 días)
 * @returns {Promise<number>} Número de registros eliminados
 */
export async function cleanupOldHistory() {
  try {
    const db = await openDB();
    const transaction = db.transaction([STORE_NAME], 'readwrite');
    const store = transaction.objectStore(STORE_NAME);
    const index = store.index('timestamp');

    const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 días
    const cutoff = Date.now() - maxAge;
    let deleted = 0;

    return new Promise((resolve, reject) => {
      const range = IDBKeyRange.upperBound(cutoff);
      const request = index.openCursor(range);

      request.onsuccess = (event) => {
        const cursor = event.target.result;
        if (cursor) {
          cursor.delete();
          deleted++;
          cursor.continue();
        } else {
          resolve(deleted);
        }
      };

      request.onerror = () => reject(request.error);
    });
  } catch (error) {
    console.warn('Failed to cleanup old history:', error);
    return 0;
  }
}

/**
 * Obtiene estadísticas de almacenamiento
 * @returns {Promise<Object>}
 */
export async function getStorageStats() {
  try {
    const db = await openDB();
    const transaction = db.transaction([STORE_NAME], 'readonly');
    const store = transaction.objectStore(STORE_NAME);

    return new Promise((resolve, reject) => {
      const countRequest = store.count();

      countRequest.onsuccess = () => {
        resolve({
          recordCount: countRequest.result,
          dbName: DB_NAME,
          storeName: STORE_NAME,
        });
      };

      countRequest.onerror = () => reject(countRequest.error);
    });
  } catch (error) {
    return { recordCount: 0, error: error.message };
  }
}

/**
 * Verifica si IndexedDB está disponible
 * @returns {boolean}
 */
export function isIndexedDBAvailable() {
  return typeof window !== 'undefined' && !!window.indexedDB;
}

/**
 * Guarda historial con debounce para evitar escrituras frecuentes
 */
let saveDebounceTimer = null;
export function saveHistoryDebounced(postId, historyData, delay = 2000) {
  if (saveDebounceTimer) {
    clearTimeout(saveDebounceTimer);
  }

  saveDebounceTimer = setTimeout(() => {
    saveHistory(postId, historyData);
    saveDebounceTimer = null;
  }, delay);
}

/**
 * Fuerza el guardado inmediato (útil antes de cerrar página)
 */
export function flushPendingSave(postId, historyData) {
  if (saveDebounceTimer) {
    clearTimeout(saveDebounceTimer);
    saveDebounceTimer = null;
  }
  return saveHistory(postId, historyData);
}
