/**
 * Compresión para diffs de historial
 *
 * Usa la API de Compression Stream nativa si está disponible,
 * con fallback a codificación simple para navegadores antiguos.
 */

/**
 * Verifica si la API de compresión nativa está disponible
 */
export function isCompressionSupported() {
  return typeof CompressionStream !== 'undefined' && typeof DecompressionStream !== 'undefined';
}

/**
 * Comprime un string usando gzip nativo (async)
 * @param {string} input - String a comprimir
 * @returns {Promise<string>} String comprimido en base64
 */
export async function compressAsync(input) {
  if (!input) return '';

  if (!isCompressionSupported()) {
    // Fallback: codificación simple
    return encodeSimple(input);
  }

  try {
    const encoder = new TextEncoder();
    const data = encoder.encode(input);

    const compressedStream = new Blob([data])
      .stream()
      .pipeThrough(new CompressionStream('gzip'));

    const compressedData = await new Response(compressedStream).arrayBuffer();
    return arrayBufferToBase64(compressedData);
  } catch {
    return encodeSimple(input);
  }
}

/**
 * Descomprime un string comprimido con compressAsync
 * @param {string} compressed - String comprimido
 * @returns {Promise<string>} String original
 */
export async function decompressAsync(compressed) {
  if (!compressed) return '';

  if (!isCompressionSupported()) {
    return decodeSimple(compressed);
  }

  try {
    const data = base64ToArrayBuffer(compressed);

    const decompressedStream = new Blob([data])
      .stream()
      .pipeThrough(new DecompressionStream('gzip'));

    const decompressedData = await new Response(decompressedStream).arrayBuffer();
    const decoder = new TextDecoder();
    return decoder.decode(decompressedData);
  } catch {
    // Fallback: intentar decodificar como simple
    return decodeSimple(compressed);
  }
}

/**
 * Codificación simple (fallback síncrono)
 * Solo convierte a base64 sin compresión real
 */
function encodeSimple(input) {
  if (!input) return '';

  try {
    // Prefijo para identificar como no comprimido
    return 'S:' + btoa(unescape(encodeURIComponent(input)));
  } catch {
    return 'R:' + input; // Raw fallback
  }
}

/**
 * Decodificación simple (fallback síncrono)
 */
function decodeSimple(input) {
  if (!input) return '';

  if (input.startsWith('R:')) {
    return input.slice(2);
  }

  if (input.startsWith('S:')) {
    try {
      return decodeURIComponent(escape(atob(input.slice(2))));
    } catch {
      return input;
    }
  }

  // Intentar decodificar como base64 antiguo
  try {
    return decodeURIComponent(escape(atob(input)));
  } catch {
    return input;
  }
}

/**
 * Convierte ArrayBuffer a base64
 */
function arrayBufferToBase64(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.length; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return 'G:' + btoa(binary); // G: prefix for gzip
}

/**
 * Convierte base64 a ArrayBuffer
 */
function base64ToArrayBuffer(base64) {
  // Remover prefijo si existe
  const data = base64.startsWith('G:') ? base64.slice(2) : base64;
  const binary = atob(data);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
}

// === API Síncrona (usa fallback simple) ===

/**
 * Comprime un string de forma síncrona (sin compresión real)
 * @param {string} input - String a comprimir
 * @returns {string} String codificado
 */
export function compress(input) {
  return encodeSimple(input);
}

/**
 * Descomprime un string de forma síncrona
 * @param {string} compressed - String comprimido
 * @returns {string} String original
 */
export function decompress(compressed) {
  return decodeSimple(compressed);
}

/**
 * Comprime un objeto JSON de forma síncrona
 * @param {Object} obj - Objeto a comprimir
 * @returns {string} String comprimido
 */
export function compressObject(obj) {
  return compress(JSON.stringify(obj));
}

/**
 * Descomprime un objeto JSON de forma síncrona
 * @param {string} compressed - String comprimido
 * @returns {Object} Objeto original
 */
export function decompressObject(compressed) {
  const json = decompress(compressed);
  return JSON.parse(json);
}

/**
 * Comprime un objeto JSON de forma asíncrona (con compresión real si disponible)
 * @param {Object} obj - Objeto a comprimir
 * @returns {Promise<string>} String comprimido
 */
export async function compressObjectAsync(obj) {
  return compressAsync(JSON.stringify(obj));
}

/**
 * Descomprime un objeto JSON de forma asíncrona
 * @param {string} compressed - String comprimido
 * @returns {Promise<Object>} Objeto original
 */
export async function decompressObjectAsync(compressed) {
  const json = await decompressAsync(compressed);
  return JSON.parse(json);
}

/**
 * Estima el ratio de compresión
 * @param {string} original - String original
 * @param {string} compressed - String comprimido
 * @returns {number} Ratio (0-1, menor es mejor)
 */
export function compressionRatio(original, compressed) {
  if (!original || !compressed) return 1;
  return compressed.length / original.length;
}
