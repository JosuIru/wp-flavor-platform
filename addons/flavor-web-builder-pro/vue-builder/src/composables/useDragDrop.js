import { ref, computed } from 'vue';
import { useBuilderStore } from '../stores/builderStore';

/**
 * Composable para gestionar drag & drop
 * Soporta arrastrar componentes desde sidebar y reordenar en canvas
 */
export function useDragDrop() {
  const builderStore = useBuilderStore();

  // Estado local de drag
  const draggedElement = ref(null);
  const dropIndicator = ref(null); // { sectionId, position, isSection }
  const isDraggingOver = ref(false);

  /**
   * Iniciar drag desde la sidebar (nuevo componente)
   */
  function onSidebarDragStart(event, componentId, variant = null) {
    builderStore.isDragging = true;
    builderStore.dragSource = {
      type: 'sidebar',
      componentId,
      variant,
    };

    // Configurar datos de transferencia
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('text/plain', JSON.stringify({
      type: 'sidebar',
      componentId,
      variant,
    }));

    // Crear ghost personalizado
    const ghostElement = createDragGhost(componentId);
    if (ghostElement) {
      document.body.appendChild(ghostElement);
      event.dataTransfer.setDragImage(ghostElement, 50, 25);
      setTimeout(() => ghostElement.remove(), 0);
    }

    draggedElement.value = { type: 'sidebar', componentId, variant };
  }

  /**
   * Iniciar drag desde el canvas (reordenar bloque existente)
   */
  function onCanvasDragStart(event, blockId, sectionId) {
    builderStore.isDragging = true;
    builderStore.dragSource = {
      type: 'canvas',
      blockId,
      sectionId,
    };

    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify({
      type: 'canvas',
      blockId,
      sectionId,
    }));

    draggedElement.value = { type: 'canvas', blockId, sectionId };

    // Añadir clase visual al elemento que se arrastra
    event.target.classList.add('is-dragging');
  }

  /**
   * Iniciar drag de sección
   */
  function onSectionDragStart(event, sectionId) {
    builderStore.isDragging = true;
    builderStore.dragSource = {
      type: 'section',
      sectionId,
    };

    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify({
      type: 'section',
      sectionId,
    }));

    draggedElement.value = { type: 'section', sectionId };
    event.target.classList.add('is-dragging');
  }

  /**
   * Manejar drag over en canvas/sección
   */
  function onDragOver(event, sectionId, position) {
    event.preventDefault();

    // Determinar el efecto permitido
    const sourceType = builderStore.dragSource?.type;
    event.dataTransfer.dropEffect = sourceType === 'sidebar' ? 'copy' : 'move';

    // Actualizar indicador de drop
    builderStore.dropTarget = { sectionId, position };
    dropIndicator.value = { sectionId, position, isSection: false };
    isDraggingOver.value = true;
  }

  /**
   * Manejar drag over entre secciones
   */
  function onSectionDragOver(event, position) {
    event.preventDefault();

    const sourceType = builderStore.dragSource?.type;
    if (sourceType === 'section') {
      event.dataTransfer.dropEffect = 'move';
      dropIndicator.value = { sectionId: null, position, isSection: true };
    }
    isDraggingOver.value = true;
  }

  /**
   * Manejar drag leave
   */
  function onDragLeave(event) {
    // Solo limpiar si realmente salimos del área de drop
    if (!event.currentTarget.contains(event.relatedTarget)) {
      dropIndicator.value = null;
      isDraggingOver.value = false;
    }
  }

  /**
   * Manejar drop
   */
  function onDrop(event, sectionId, position) {
    event.preventDefault();

    const dragSource = builderStore.dragSource;
    if (!dragSource) return;

    try {
      if (dragSource.type === 'sidebar') {
        // Nuevo componente desde sidebar
        const targetSectionId = sectionId || getOrCreateDefaultSection();
        builderStore.addBlock(
          targetSectionId,
          dragSource.componentId,
          position,
          {},
          dragSource.variant
        );
      } else if (dragSource.type === 'canvas') {
        // Mover bloque existente
        if (dragSource.blockId) {
          const targetSectionId = sectionId || getOrCreateDefaultSection();
          builderStore.moveBlock(dragSource.blockId, targetSectionId, position);
        }
      } else if (dragSource.type === 'section') {
        // Mover sección
        if (dragSource.sectionId && typeof position === 'number') {
          builderStore.moveSection(dragSource.sectionId, position);
        }
      }
    } catch (error) {
      console.error('Error on drop:', error);
    }

    // Limpiar estado
    cleanup();
  }

  /**
   * Manejar drop de sección
   */
  function onSectionDrop(event, position) {
    event.preventDefault();

    const dragSource = builderStore.dragSource;
    if (dragSource?.type === 'section' && dragSource.sectionId) {
      builderStore.moveSection(dragSource.sectionId, position);
    }

    cleanup();
  }

  /**
   * Manejar fin de drag
   */
  function onDragEnd(event) {
    event.target.classList.remove('is-dragging');
    cleanup();
  }

  /**
   * Limpiar estado de drag
   */
  function cleanup() {
    builderStore.isDragging = false;
    builderStore.dragSource = null;
    builderStore.dropTarget = null;
    draggedElement.value = null;
    dropIndicator.value = null;
    isDraggingOver.value = false;
  }

  /**
   * Obtener o crear sección por defecto
   */
  function getOrCreateDefaultSection() {
    if (builderStore.sections.length === 0) {
      return builderStore.addSection();
    }
    return builderStore.sections[0].id;
  }

  /**
   * Crear elemento ghost para drag
   */
  function createDragGhost(componentId) {
    const componentDef = builderStore.componentDefs[componentId];
    if (!componentDef) return null;

    const ghostElement = document.createElement('div');
    ghostElement.className = 'flavor-pb-drag-ghost';
    ghostElement.innerHTML = `
      <span class="dashicons ${componentDef.icon || 'dashicons-layout'}"></span>
      <span class="ghost-label">${componentDef.label || componentId}</span>
    `;
    ghostElement.style.cssText = `
      position: fixed;
      top: -1000px;
      left: -1000px;
      padding: 8px 12px;
      background: #0073aa;
      color: white;
      border-radius: 4px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      z-index: 100000;
    `;

    return ghostElement;
  }

  /**
   * Calcular posición de drop basada en coordenadas del mouse
   */
  function calculateDropPosition(event, containerElement) {
    const rect = containerElement.getBoundingClientRect();
    const relativeY = event.clientY - rect.top;
    const items = containerElement.querySelectorAll('.flavor-pb-block');

    let dropPosition = 0;
    for (const item of items) {
      const itemRect = item.getBoundingClientRect();
      const itemMiddle = itemRect.top + itemRect.height / 2 - rect.top;

      if (relativeY > itemMiddle) {
        dropPosition++;
      } else {
        break;
      }
    }

    return dropPosition;
  }

  // Computed helpers
  const isDragging = computed(() => builderStore.isDragging);
  const currentDropTarget = computed(() => builderStore.dropTarget);

  return {
    // Estado
    draggedElement,
    dropIndicator,
    isDragging,
    isDraggingOver,
    currentDropTarget,

    // Handlers
    onSidebarDragStart,
    onCanvasDragStart,
    onSectionDragStart,
    onDragOver,
    onSectionDragOver,
    onDragLeave,
    onDrop,
    onSectionDrop,
    onDragEnd,

    // Utils
    cleanup,
    calculateDropPosition,
  };
}
