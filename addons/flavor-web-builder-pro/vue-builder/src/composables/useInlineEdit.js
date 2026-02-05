import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para edición inline de texto e imágenes
 */
export function useInlineEdit() {
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  const isEditing = ref(false);
  const editingBlockId = ref(null);
  const editingFieldName = ref(null);
  const originalValue = ref(null);
  const currentValue = ref(null);

  /**
   * Iniciar edición inline
   */
  function startEdit(blockId, fieldName) {
    const block = builderStore.getBlockById(blockId);
    if (!block) return;

    editingBlockId.value = blockId;
    editingFieldName.value = fieldName;
    originalValue.value = block.values[fieldName];
    currentValue.value = block.values[fieldName];
    isEditing.value = true;

    uiStore.startInlineEdit(blockId, fieldName);

    // Notificar al iframe que inicie edición
    notifyIframeStartEdit(blockId, fieldName);
  }

  /**
   * Actualizar valor durante edición
   */
  function updateValue(value) {
    if (!isEditing.value) return;

    currentValue.value = value;
    builderStore.updateBlock(editingBlockId.value, editingFieldName.value, value);
  }

  /**
   * Confirmar edición
   */
  function confirmEdit() {
    if (!isEditing.value) return;

    // Guardar en historial si el valor cambió
    if (currentValue.value !== originalValue.value) {
      builderStore.pushHistory();
    }

    cleanup();
    notifyIframeStopEdit();
  }

  /**
   * Cancelar edición y restaurar valor original
   */
  function cancelEdit() {
    if (!isEditing.value) return;

    // Restaurar valor original
    if (editingBlockId.value && editingFieldName.value) {
      builderStore.updateBlock(
        editingBlockId.value,
        editingFieldName.value,
        originalValue.value
      );
    }

    cleanup();
    notifyIframeStopEdit();
  }

  /**
   * Limpiar estado de edición
   */
  function cleanup() {
    isEditing.value = false;
    editingBlockId.value = null;
    editingFieldName.value = null;
    originalValue.value = null;
    currentValue.value = null;
    uiStore.stopInlineEdit();
  }

  /**
   * Notificar al iframe que inicie edición
   */
  function notifyIframeStartEdit(blockId, fieldName) {
    window.postMessage({
      type: 'INLINE_EDIT_START',
      payload: { blockId, fieldName },
      source: 'flavor-page-builder',
    }, '*');
  }

  /**
   * Notificar al iframe que termine edición
   */
  function notifyIframeStopEdit() {
    window.postMessage({
      type: 'INLINE_EDIT_STOP',
      payload: {},
      source: 'flavor-page-builder',
    }, '*');
  }

  /**
   * Manejar mensajes del iframe
   */
  function handleIframeMessage(event) {
    if (!event.data || event.data.source !== 'flavor-preview-frame') {
      return;
    }

    const { type, payload } = event.data;

    switch (type) {
      case 'INLINE_TEXT_CHANGE':
        if (isEditing.value && payload.blockId === editingBlockId.value) {
          updateValue(payload.value);
        }
        break;

      case 'INLINE_TEXT_CONFIRM':
        if (isEditing.value && payload.blockId === editingBlockId.value) {
          confirmEdit();
        }
        break;

      case 'INLINE_TEXT_CANCEL':
        if (isEditing.value && payload.blockId === editingBlockId.value) {
          cancelEdit();
        }
        break;

      case 'INLINE_IMAGE_CHANGE':
        if (payload.blockId && payload.fieldName) {
          builderStore.updateBlock(payload.blockId, payload.fieldName, payload.url);
          builderStore.pushHistory();
        }
        break;
    }
  }

  /**
   * Verificar si un campo es editable inline
   */
  function isFieldInlineEditable(componentDef, fieldName) {
    if (!componentDef || !componentDef.fields) return false;

    const field = componentDef.fields[fieldName];
    if (!field) return false;

    // Campos de texto y textarea son editables inline
    const inlineEditableTypes = ['text', 'textarea', 'image'];
    return inlineEditableTypes.includes(field.type) && field.inlineEditable !== false;
  }

  /**
   * Obtener campos editables inline de un componente
   */
  function getInlineEditableFields(componentId) {
    const componentDef = builderStore.componentDefs[componentId];
    if (!componentDef || !componentDef.fields) return [];

    return Object.entries(componentDef.fields)
      .filter(([fieldName, field]) => {
        const editableTypes = ['text', 'textarea', 'image'];
        return editableTypes.includes(field.type) && field.inlineEditable !== false;
      })
      .map(([fieldName, field]) => ({
        name: fieldName,
        type: field.type,
        label: field.label || fieldName,
      }));
  }

  // Computed
  const isCurrentlyEditing = computed(() => isEditing.value);
  const editingInfo = computed(() => ({
    blockId: editingBlockId.value,
    fieldName: editingFieldName.value,
    originalValue: originalValue.value,
    currentValue: currentValue.value,
  }));

  // Watch para sincronizar con uiStore
  watch(
    () => uiStore.inlineEditingBlock,
    (newValue) => {
      if (!newValue && isEditing.value) {
        // El UI cerró la edición inline
        confirmEdit();
      }
    }
  );

  // Lifecycle
  onMounted(() => {
    window.addEventListener('message', handleIframeMessage);
  });

  onUnmounted(() => {
    window.removeEventListener('message', handleIframeMessage);

    // Limpiar si hay edición en progreso
    if (isEditing.value) {
      cancelEdit();
    }
  });

  return {
    // Estado
    isEditing: isCurrentlyEditing,
    editingInfo,

    // Métodos
    startEdit,
    updateValue,
    confirmEdit,
    cancelEdit,
    isFieldInlineEditable,
    getInlineEditableFields,
  };
}
