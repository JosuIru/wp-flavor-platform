/**
 * Función debounce clásica
 */
export function debounce(func, wait, immediate = false) {
  let timeout;

  return function executedFunction(...args) {
    const context = this;

    const later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };

    const callNow = immediate && !timeout;

    clearTimeout(timeout);
    timeout = setTimeout(later, wait);

    if (callNow) func.apply(context, args);
  };
}

/**
 * Función throttle
 */
export function throttle(func, limit) {
  let inThrottle;

  return function executedFunction(...args) {
    const context = this;

    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

/**
 * Debounce con promesa
 */
export function debounceAsync(func, wait) {
  let timeout;
  let pendingPromise = null;
  let resolveFunc = null;

  return function executedFunction(...args) {
    const context = this;

    return new Promise((resolve) => {
      if (pendingPromise) {
        resolveFunc = resolve;
      } else {
        resolveFunc = resolve;
      }

      clearTimeout(timeout);

      timeout = setTimeout(async () => {
        const result = await func.apply(context, args);
        resolveFunc(result);
        pendingPromise = null;
      }, wait);

      pendingPromise = true;
    });
  };
}

/**
 * Crear función debounceada con ID único
 * Útil para debounce por entidad (ej: por blockId)
 */
export function createDebouncedById(func, wait) {
  const timeouts = new Map();

  return function executedFunction(id, ...args) {
    const context = this;

    if (timeouts.has(id)) {
      clearTimeout(timeouts.get(id));
    }

    const timeout = setTimeout(() => {
      func.apply(context, [id, ...args]);
      timeouts.delete(id);
    }, wait);

    timeouts.set(id, timeout);
  };
}

/**
 * Cancelar todas las operaciones pendientes de un debounce por ID
 */
export function cancelAllDebounced(debouncedFunc) {
  // Esta función requiere que se exporte el map de timeouts
  // Es una extensión opcional
}
