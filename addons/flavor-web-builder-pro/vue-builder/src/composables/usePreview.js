import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para comunicación con iframe de preview
 */
export function usePreview() {
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  const iframeRef = ref(null);
  const isIframeReady = ref(false);
  const pendingMessages = ref([]);

  /**
   * Enviar mensaje al iframe
   */
  function sendMessage(type, payload = {}) {
    const message = { type, payload, source: 'flavor-page-builder' };

    if (!iframeRef.value || !isIframeReady.value) {
      // Encolar mensaje para enviarlo cuando el iframe esté listo
      pendingMessages.value.push(message);
      return;
    }

    try {
      iframeRef.value.contentWindow.postMessage(message, '*');
    } catch (error) {
      console.error('Error sending message to iframe:', error);
    }
  }

  /**
   * Manejar mensajes del iframe
   */
  function handleMessage(event) {
    // Verificar origen (seguridad básica)
    if (!event.data || event.data.source !== 'flavor-preview-frame') {
      return;
    }

    const { type, payload } = event.data;

    switch (type) {
      case 'PREVIEW_READY':
        handlePreviewReady();
        break;

      case 'BLOCK_CLICKED':
        handleBlockClicked(payload);
        break;

      case 'INLINE_EDIT_START':
        handleInlineEditStart(payload);
        break;

      case 'INLINE_EDIT_CHANGE':
        handleInlineEditChange(payload);
        break;

      case 'INLINE_EDIT_END':
        handleInlineEditEnd(payload);
        break;

      case 'BLOCK_HOVER':
        handleBlockHover(payload);
        break;

      case 'SCROLL_TO_BLOCK':
        handleScrollToBlock(payload);
        break;

      case 'REQUEST_BLOCK_UPDATE':
        handleBlockUpdateRequest(payload);
        break;

      default:
        console.log('Unknown message type:', type);
    }
  }

  /**
   * Manejar cuando el iframe está listo
   */
  function handlePreviewReady() {
    isIframeReady.value = true;

    // Enviar mensajes pendientes
    while (pendingMessages.value.length > 0) {
      const message = pendingMessages.value.shift();
      sendMessage(message.type, message.payload);
    }

    // Enviar layout actual al iframe
    sendLayoutToPreview();
  }

  /**
   * Manejar click en bloque del preview
   */
  function handleBlockClicked(payload) {
    const { blockId } = payload;
    if (blockId) {
      builderStore.selectBlock(blockId);
    }
  }

  /**
   * Manejar inicio de edición inline
   */
  function handleInlineEditStart(payload) {
    const { blockId, fieldName, currentValue } = payload;
    uiStore.startInlineEdit(blockId, fieldName);
  }

  /**
   * Manejar cambio durante edición inline
   */
  function handleInlineEditChange(payload) {
    const { blockId, fieldName, value } = payload;
    builderStore.updateBlock(blockId, fieldName, value);
  }

  /**
   * Manejar fin de edición inline
   */
  function handleInlineEditEnd(payload) {
    const { blockId, fieldName, finalValue } = payload;

    // Guardar valor final
    builderStore.updateBlock(blockId, fieldName, finalValue);

    // Guardar en historial
    builderStore.pushHistory();

    // Limpiar estado de edición
    uiStore.stopInlineEdit();
  }

  /**
   * Manejar hover en bloque
   */
  function handleBlockHover(payload) {
    const { blockId, isHovering } = payload;
    // Podría usarse para resaltar el bloque en el panel lateral
  }

  /**
   * Manejar scroll a bloque
   */
  function handleScrollToBlock(payload) {
    const { blockId } = payload;
    scrollToBlock(blockId);
  }

  /**
   * Manejar solicitud de actualización de bloque
   */
  function handleBlockUpdateRequest(payload) {
    const { blockId } = payload;
    builderStore.refreshPreview(blockId);
  }

  /**
   * Enviar layout al preview
   */
  function sendLayoutToPreview() {
    sendMessage('UPDATE_LAYOUT', {
      sections: builderStore.sections,
      previewCache: builderStore.previewHtmlCache,
    });
  }

  /**
   * Actualizar preview de un bloque específico
   */
  function updateBlockPreview(blockId, html) {
    sendMessage('UPDATE_BLOCK', { blockId, html });
  }

  /**
   * Resaltar bloque en preview
   */
  function highlightBlock(blockId) {
    sendMessage('HIGHLIGHT_BLOCK', { blockId });
  }

  /**
   * Quitar resaltado de bloque
   */
  function unhighlightBlock(blockId) {
    sendMessage('UNHIGHLIGHT_BLOCK', { blockId });
  }

  /**
   * Scroll al bloque en el iframe
   */
  function scrollToBlock(blockId) {
    sendMessage('SCROLL_TO_BLOCK', { blockId });
  }

  /**
   * Habilitar edición inline de un campo
   */
  function enableInlineEdit(blockId, fieldName) {
    sendMessage('ENABLE_INLINE_EDIT', { blockId, fieldName });
  }

  /**
   * Deshabilitar edición inline
   */
  function disableInlineEdit() {
    sendMessage('DISABLE_INLINE_EDIT', {});
  }

  /**
   * Establecer dispositivo de preview
   */
  function setPreviewDevice(device) {
    sendMessage('SET_DEVICE', { device });
  }

  /**
   * Refrescar todo el preview
   */
  function refreshPreview() {
    if (iframeRef.value) {
      iframeRef.value.contentWindow.location.reload();
      isIframeReady.value = false;
    }
  }

  /**
   * Establecer referencia del iframe
   */
  function setIframeRef(iframe) {
    iframeRef.value = iframe;
  }

  // Computed
  const previewUrl = computed(() => {
    const baseUrl = builderStore.previewUrl || '';
    return baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'flavor_preview=1';
  });

  // Lifecycle
  onMounted(() => {
    window.addEventListener('message', handleMessage);
  });

  onUnmounted(() => {
    window.removeEventListener('message', handleMessage);
  });

  return {
    // Refs
    iframeRef,
    isIframeReady,
    previewUrl,

    // Métodos
    setIframeRef,
    sendMessage,
    sendLayoutToPreview,
    updateBlockPreview,
    highlightBlock,
    unhighlightBlock,
    scrollToBlock,
    enableInlineEdit,
    disableInlineEdit,
    setPreviewDevice,
    refreshPreview,
  };
}
