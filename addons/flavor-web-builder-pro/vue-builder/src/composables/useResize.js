import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';

/**
 * Composable para redimensionar elementos (imágenes, secciones)
 */
export function useResize() {
  const builderStore = useBuilderStore();

  const isResizing = ref(false);
  const resizeData = ref(null);

  /**
   * Iniciar redimensionado
   */
  function startResize(event, options) {
    const {
      blockId,
      fieldName,
      handle, // 'n', 's', 'e', 'w', 'ne', 'nw', 'se', 'sw'
      element,
      minWidth = 50,
      minHeight = 50,
      maxWidth = 2000,
      maxHeight = 2000,
      aspectRatio = null, // Si se especifica, mantiene proporción
    } = options;

    event.preventDefault();
    event.stopPropagation();

    const rect = element.getBoundingClientRect();

    resizeData.value = {
      blockId,
      fieldName,
      handle,
      element,
      startX: event.clientX,
      startY: event.clientY,
      startWidth: rect.width,
      startHeight: rect.height,
      minWidth,
      minHeight,
      maxWidth,
      maxHeight,
      aspectRatio: aspectRatio || (rect.width / rect.height),
      maintainAspectRatio: aspectRatio !== null,
    };

    isResizing.value = true;

    // Añadir listeners globales
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);

    // Añadir clase al body para cursor
    document.body.classList.add('is-resizing');
    document.body.style.cursor = getCursorForHandle(handle);
  }

  /**
   * Manejar movimiento del mouse durante resize
   */
  function handleMouseMove(event) {
    if (!isResizing.value || !resizeData.value) return;

    const data = resizeData.value;
    const deltaX = event.clientX - data.startX;
    const deltaY = event.clientY - data.startY;

    let newWidth = data.startWidth;
    let newHeight = data.startHeight;

    // Calcular nuevas dimensiones según el handle
    switch (data.handle) {
      case 'e':
        newWidth = data.startWidth + deltaX;
        break;
      case 'w':
        newWidth = data.startWidth - deltaX;
        break;
      case 's':
        newHeight = data.startHeight + deltaY;
        break;
      case 'n':
        newHeight = data.startHeight - deltaY;
        break;
      case 'se':
        newWidth = data.startWidth + deltaX;
        newHeight = data.startHeight + deltaY;
        break;
      case 'sw':
        newWidth = data.startWidth - deltaX;
        newHeight = data.startHeight + deltaY;
        break;
      case 'ne':
        newWidth = data.startWidth + deltaX;
        newHeight = data.startHeight - deltaY;
        break;
      case 'nw':
        newWidth = data.startWidth - deltaX;
        newHeight = data.startHeight - deltaY;
        break;
    }

    // Aplicar límites
    newWidth = Math.max(data.minWidth, Math.min(data.maxWidth, newWidth));
    newHeight = Math.max(data.minHeight, Math.min(data.maxHeight, newHeight));

    // Mantener proporción si es necesario
    if (data.maintainAspectRatio && ['se', 'sw', 'ne', 'nw'].includes(data.handle)) {
      // Usar el cambio mayor para determinar las dimensiones
      const widthRatio = newWidth / data.startWidth;
      const heightRatio = newHeight / data.startHeight;

      if (Math.abs(widthRatio - 1) > Math.abs(heightRatio - 1)) {
        newHeight = newWidth / data.aspectRatio;
      } else {
        newWidth = newHeight * data.aspectRatio;
      }

      // Re-aplicar límites
      newWidth = Math.max(data.minWidth, Math.min(data.maxWidth, newWidth));
      newHeight = Math.max(data.minHeight, Math.min(data.maxHeight, newHeight));
    }

    // Aplicar dimensiones al elemento visual
    data.element.style.width = `${Math.round(newWidth)}px`;
    data.element.style.height = `${Math.round(newHeight)}px`;

    // Actualizar datos para el guardado final
    resizeData.value.currentWidth = newWidth;
    resizeData.value.currentHeight = newHeight;
  }

  /**
   * Manejar fin del resize
   */
  function handleMouseUp(event) {
    if (!isResizing.value || !resizeData.value) return;

    const data = resizeData.value;

    // Guardar nuevas dimensiones en el bloque
    if (data.blockId && data.fieldName) {
      const newValue = {
        width: Math.round(data.currentWidth || data.startWidth),
        height: Math.round(data.currentHeight || data.startHeight),
      };

      // Si el campo es un string simple (como width), guardar solo el valor
      const block = builderStore.getBlockById(data.blockId);
      if (block) {
        const currentFieldValue = block.values[data.fieldName];

        if (typeof currentFieldValue === 'object') {
          builderStore.updateBlock(data.blockId, data.fieldName, {
            ...currentFieldValue,
            ...newValue,
          });
        } else {
          // Para campos simples, guardar solo ancho o alto según corresponda
          if (data.handle.includes('e') || data.handle.includes('w')) {
            builderStore.updateBlock(data.blockId, `${data.fieldName}_width`, newValue.width);
          }
          if (data.handle.includes('n') || data.handle.includes('s')) {
            builderStore.updateBlock(data.blockId, `${data.fieldName}_height`, newValue.height);
          }
        }

        // Guardar en historial
        builderStore.pushHistory();
      }
    }

    // Limpiar
    cleanup();
  }

  /**
   * Obtener cursor según handle
   */
  function getCursorForHandle(handle) {
    const cursors = {
      n: 'ns-resize',
      s: 'ns-resize',
      e: 'ew-resize',
      w: 'ew-resize',
      ne: 'nesw-resize',
      sw: 'nesw-resize',
      nw: 'nwse-resize',
      se: 'nwse-resize',
    };
    return cursors[handle] || 'default';
  }

  /**
   * Limpiar estado de resize
   */
  function cleanup() {
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
    document.body.classList.remove('is-resizing');
    document.body.style.cursor = '';

    isResizing.value = false;
    resizeData.value = null;
  }

  /**
   * Crear handles de resize para un elemento
   */
  function createResizeHandles(options = {}) {
    const { corners = true, edges = true } = options;

    const handles = [];

    if (edges) {
      handles.push(
        { position: 'n', style: 'top: -4px; left: 50%; transform: translateX(-50%); width: 30%; height: 8px; cursor: ns-resize;' },
        { position: 's', style: 'bottom: -4px; left: 50%; transform: translateX(-50%); width: 30%; height: 8px; cursor: ns-resize;' },
        { position: 'e', style: 'right: -4px; top: 50%; transform: translateY(-50%); width: 8px; height: 30%; cursor: ew-resize;' },
        { position: 'w', style: 'left: -4px; top: 50%; transform: translateY(-50%); width: 8px; height: 30%; cursor: ew-resize;' }
      );
    }

    if (corners) {
      handles.push(
        { position: 'nw', style: 'top: -4px; left: -4px; width: 8px; height: 8px; cursor: nwse-resize;' },
        { position: 'ne', style: 'top: -4px; right: -4px; width: 8px; height: 8px; cursor: nesw-resize;' },
        { position: 'sw', style: 'bottom: -4px; left: -4px; width: 8px; height: 8px; cursor: nesw-resize;' },
        { position: 'se', style: 'bottom: -4px; right: -4px; width: 8px; height: 8px; cursor: nwse-resize;' }
      );
    }

    return handles;
  }

  // Cleanup al desmontar
  onUnmounted(() => {
    if (isResizing.value) {
      cleanup();
    }
  });

  return {
    isResizing,
    resizeData,
    startResize,
    createResizeHandles,
    cleanup,
  };
}
