import { computed, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para gestionar historial de undo/redo
 */
export function useHistory() {
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  /**
   * Deshacer último cambio
   */
  function undo() {
    if (builderStore.canUndo) {
      builderStore.undo();
      uiStore.showToast('Deshacer', 'info', 1500);
    }
  }

  /**
   * Rehacer cambio deshecho
   */
  function redo() {
    if (builderStore.canRedo) {
      builderStore.redo();
      uiStore.showToast('Rehacer', 'info', 1500);
    }
  }

  /**
   * Guardar estado actual en historial
   */
  function pushState() {
    builderStore.pushHistory();
  }

  /**
   * Manejar atajos de teclado para undo/redo
   */
  function handleKeyboardShortcuts(event) {
    // Ignorar si está en un campo de entrada
    if (isInputElement(event.target)) {
      return;
    }

    const isCtrlOrCmd = event.ctrlKey || event.metaKey;

    if (isCtrlOrCmd && event.key === 'z') {
      event.preventDefault();
      if (event.shiftKey) {
        redo();
      } else {
        undo();
      }
    } else if (isCtrlOrCmd && event.key === 'y') {
      event.preventDefault();
      redo();
    }
  }

  /**
   * Verificar si el elemento es un campo de entrada
   */
  function isInputElement(element) {
    const tagName = element.tagName.toLowerCase();
    return (
      tagName === 'input' ||
      tagName === 'textarea' ||
      tagName === 'select' ||
      element.isContentEditable
    );
  }

  // Computed
  const canUndo = computed(() => builderStore.canUndo);
  const canRedo = computed(() => builderStore.canRedo);
  const historyLength = computed(() => builderStore.historyStack.length);
  const currentHistoryIndex = computed(() => builderStore.historyIndex);

  // Lifecycle - registrar atajos de teclado
  onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcuts);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
  });

  return {
    undo,
    redo,
    pushState,
    canUndo,
    canRedo,
    historyLength,
    currentHistoryIndex,
  };
}
