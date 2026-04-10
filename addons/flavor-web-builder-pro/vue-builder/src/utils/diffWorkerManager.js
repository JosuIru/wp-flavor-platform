/**
 * Manager para el Web Worker de diffs
 * Proporciona API async/await para comunicación con el worker
 */

let worker = null;
let messageId = 0;
const pendingPromises = new Map();

/**
 * Inicializa el worker si no está activo
 */
function initWorker() {
  if (worker) return;

  try {
    // Crear worker desde el archivo
    const workerUrl = new URL('../workers/diffWorker.js', import.meta.url);
    worker = new Worker(workerUrl, { type: 'module' });

    worker.onmessage = (event) => {
      const { id, success, result, error } = event.data;
      const pending = pendingPromises.get(id);

      if (pending) {
        pendingPromises.delete(id);
        if (success) {
          pending.resolve(result);
        } else {
          pending.reject(new Error(error));
        }
      }
    };

    worker.onerror = (error) => {
      console.error('Diff worker error:', error);
      // Rechazar todas las promesas pendientes
      for (const [id, pending] of pendingPromises) {
        pending.reject(new Error('Worker error'));
        pendingPromises.delete(id);
      }
    };
  } catch (error) {
    console.warn('Web Worker not available, using main thread:', error);
    worker = null;
  }
}

/**
 * Envía mensaje al worker y espera respuesta
 */
function postMessage(type, payload) {
  return new Promise((resolve, reject) => {
    if (!worker) {
      // Fallback: ejecutar en hilo principal
      reject(new Error('Worker not available'));
      return;
    }

    const id = ++messageId;
    pendingPromises.set(id, { resolve, reject });

    worker.postMessage({ type, id, payload });

    // Timeout de 5 segundos
    setTimeout(() => {
      if (pendingPromises.has(id)) {
        pendingPromises.delete(id);
        reject(new Error('Worker timeout'));
      }
    }, 5000);
  });
}

/**
 * Calcula diff en el worker (async)
 * @param {Object} oldState - Estado anterior
 * @param {Object} newState - Estado nuevo
 * @returns {Promise<Object>} Diff calculado
 */
export async function createDiffAsync(oldState, newState) {
  initWorker();

  try {
    return await postMessage('createDiff', { oldState, newState });
  } catch {
    // Fallback a cálculo síncrono si el worker falla
    const { createDiff } = await import('./historyDiff.js');
    return createDiff(oldState, newState);
  }
}

/**
 * Calcula diff y lo comprime en el worker
 * @param {Object} oldState - Estado anterior
 * @param {Object} newState - Estado nuevo
 * @returns {Promise<{diff: Object, compressed: string}>}
 */
export async function createDiffCompressedAsync(oldState, newState) {
  initWorker();

  try {
    return await postMessage('createDiffCompressed', { oldState, newState });
  } catch {
    // Fallback
    const { createDiff } = await import('./historyDiff.js');
    const { compressObject } = await import('./lzCompress.js');
    const diff = createDiff(oldState, newState);
    return { diff, compressed: compressObject(diff) };
  }
}

/**
 * Comprime datos en el worker
 * @param {Object} data - Datos a comprimir
 * @returns {Promise<string>} Datos comprimidos
 */
export async function compressAsync(data) {
  initWorker();

  try {
    return await postMessage('compress', { data });
  } catch {
    const { compressObject } = await import('./lzCompress.js');
    return compressObject(data);
  }
}

/**
 * Termina el worker
 */
export function terminateWorker() {
  if (worker) {
    worker.terminate();
    worker = null;
    pendingPromises.clear();
  }
}

/**
 * Verifica si el worker está disponible
 */
export function isWorkerAvailable() {
  return typeof Worker !== 'undefined';
}
