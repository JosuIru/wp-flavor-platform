import { ref, onUnmounted } from 'vue';

/**
 * Composable para debounce de updates de campos
 * Evita re-renders excesivos durante typing y proporciona feedback visual
 */
export function useFieldDebounce(defaultDelay = 300) {
  const pendingUpdates = ref(new Map());
  const timers = new Map();

  /**
   * Emitir update con debounce
   * @param {string} fieldKey - Clave del campo
   * @param {any} value - Nuevo valor
   * @param {Function} emitFn - Función para emitir el update
   * @param {number} delay - Delay en ms (opcional)
   */
  function debouncedUpdate(fieldKey, value, emitFn, delay = defaultDelay) {
    // Marcar como pendiente para feedback visual
    pendingUpdates.value.set(fieldKey, value);

    // Cancelar timer anterior
    if (timers.has(fieldKey)) {
      clearTimeout(timers.get(fieldKey));
    }

    // Crear nuevo timer
    const timer = setTimeout(() => {
      emitFn(fieldKey, value);
      pendingUpdates.value.delete(fieldKey);
      timers.delete(fieldKey);
    }, delay);

    timers.set(fieldKey, timer);
  }

  /**
   * Verificar si hay un update pendiente para un campo
   */
  function isPending(fieldKey) {
    return pendingUpdates.value.has(fieldKey);
  }

  /**
   * Forzar flush de todos los updates pendientes
   */
  function flushAll(emitFn) {
    for (const [fieldKey, value] of pendingUpdates.value.entries()) {
      if (timers.has(fieldKey)) {
        clearTimeout(timers.get(fieldKey));
        timers.delete(fieldKey);
      }
      emitFn(fieldKey, value);
    }
    pendingUpdates.value.clear();
  }

  /**
   * Cancelar todos los updates pendientes sin emitirlos
   */
  function cancelAll() {
    for (const timer of timers.values()) {
      clearTimeout(timer);
    }
    timers.clear();
    pendingUpdates.value.clear();
  }

  // Cleanup al desmontar
  onUnmounted(() => {
    cancelAll();
  });

  return {
    pendingUpdates,
    debouncedUpdate,
    isPending,
    flushAll,
    cancelAll,
  };
}

/**
 * Composable simplificado para un solo campo
 */
export function useSingleFieldDebounce(emitFn, delay = 300) {
  let timer = null;
  let pendingValue = null;

  function update(value) {
    pendingValue = value;

    if (timer) {
      clearTimeout(timer);
    }

    timer = setTimeout(() => {
      emitFn(pendingValue);
      pendingValue = null;
      timer = null;
    }, delay);
  }

  function flush() {
    if (timer && pendingValue !== null) {
      clearTimeout(timer);
      emitFn(pendingValue);
      pendingValue = null;
      timer = null;
    }
  }

  function cancel() {
    if (timer) {
      clearTimeout(timer);
      pendingValue = null;
      timer = null;
    }
  }

  onUnmounted(() => {
    cancel();
  });

  return {
    update,
    flush,
    cancel,
  };
}
