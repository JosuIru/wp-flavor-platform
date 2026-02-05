import { ref, watch, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para guardado automático con debounce
 */
export function useAutoSave(options = {}) {
  const {
    debounceTime = 30000, // 30 segundos por defecto
    enabled = true,
  } = options;

  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  const isAutoSaveEnabled = ref(enabled);
  const lastAutoSaveTime = ref(null);
  const autoSaveTimer = ref(null);

  /**
   * Programar autosave
   */
  function scheduleAutoSave() {
    if (!isAutoSaveEnabled.value) return;

    // Cancelar timer anterior
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value);
    }

    // Programar nuevo guardado
    autoSaveTimer.value = setTimeout(async () => {
      await performAutoSave();
    }, debounceTime);
  }

  /**
   * Ejecutar autosave
   */
  async function performAutoSave() {
    if (!builderStore.isDirty || builderStore.isSaving) return;

    const success = await builderStore.save();

    if (success) {
      lastAutoSaveTime.value = new Date();
      uiStore.showToast('Guardado automático', 'info', 2000);
    }
  }

  /**
   * Forzar guardado inmediato
   */
  async function saveNow() {
    // Cancelar timer pendiente
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value);
      autoSaveTimer.value = null;
    }

    if (!builderStore.isDirty) {
      uiStore.showToast('No hay cambios por guardar', 'info', 1500);
      return true;
    }

    uiStore.setLoading(true, 'Guardando...');
    const success = await builderStore.save();
    uiStore.setLoading(false);

    if (success) {
      lastAutoSaveTime.value = new Date();
      uiStore.showSuccess('Guardado correctamente');
    } else {
      uiStore.showError('Error al guardar');
    }

    return success;
  }

  /**
   * Activar/desactivar autosave
   */
  function toggleAutoSave(state) {
    isAutoSaveEnabled.value = state ?? !isAutoSaveEnabled.value;

    if (!isAutoSaveEnabled.value && autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value);
      autoSaveTimer.value = null;
    }
  }

  // Watch para cambios en isDirty
  watch(
    () => builderStore.isDirty,
    (isDirty) => {
      if (isDirty) {
        scheduleAutoSave();
      }
    }
  );

  // Guardar antes de cerrar la página
  function handleBeforeUnload(event) {
    if (builderStore.isDirty) {
      event.preventDefault();
      event.returnValue = 'Tienes cambios sin guardar. ¿Seguro que quieres salir?';
      return event.returnValue;
    }
  }

  // Registrar listener de beforeunload
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', handleBeforeUnload);
  }

  // Cleanup al desmontar
  onUnmounted(() => {
    if (autoSaveTimer.value) {
      clearTimeout(autoSaveTimer.value);
    }
    if (typeof window !== 'undefined') {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    }
  });

  return {
    isAutoSaveEnabled,
    lastAutoSaveTime,
    saveNow,
    toggleAutoSave,
    scheduleAutoSave,
  };
}
