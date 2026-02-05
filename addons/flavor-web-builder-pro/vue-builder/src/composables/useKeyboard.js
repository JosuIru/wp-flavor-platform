import { onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para gestionar atajos de teclado
 */
export function useKeyboard() {
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  /**
   * Manejar eventos de teclado
   */
  function handleKeyDown(event) {
    // Ignorar si está en un campo de entrada
    if (isInputElement(event.target)) {
      return;
    }

    const isCtrlOrCmd = event.ctrlKey || event.metaKey;
    const key = event.key.toLowerCase();

    // Ctrl/Cmd + Z: Deshacer
    if (isCtrlOrCmd && key === 'z' && !event.shiftKey) {
      event.preventDefault();
      builderStore.undo();
      return;
    }

    // Ctrl/Cmd + Shift + Z o Ctrl/Cmd + Y: Rehacer
    if ((isCtrlOrCmd && key === 'z' && event.shiftKey) || (isCtrlOrCmd && key === 'y')) {
      event.preventDefault();
      builderStore.redo();
      return;
    }

    // Ctrl/Cmd + S: Guardar
    if (isCtrlOrCmd && key === 's') {
      event.preventDefault();
      saveLayout();
      return;
    }

    // Ctrl/Cmd + D: Duplicar bloque seleccionado
    if (isCtrlOrCmd && key === 'd') {
      event.preventDefault();
      duplicateSelectedBlock();
      return;
    }

    // Delete o Backspace: Eliminar bloque seleccionado
    if (key === 'delete' || key === 'backspace') {
      event.preventDefault();
      deleteSelectedBlock();
      return;
    }

    // Escape: Deseleccionar / cerrar modal
    if (key === 'escape') {
      event.preventDefault();
      handleEscape();
      return;
    }

    // Flechas: Navegar entre bloques
    if (key === 'arrowup' || key === 'arrowdown') {
      event.preventDefault();
      navigateBlocks(key === 'arrowup' ? -1 : 1);
      return;
    }

    // Ctrl/Cmd + Flechas: Mover bloque
    if (isCtrlOrCmd && (key === 'arrowup' || key === 'arrowdown')) {
      event.preventDefault();
      moveSelectedBlock(key === 'arrowup' ? -1 : 1);
      return;
    }

    // Ctrl/Cmd + P: Vista previa
    if (isCtrlOrCmd && key === 'p') {
      event.preventDefault();
      uiStore.togglePreviewMode();
      return;
    }

    // F2: Renombrar / editar bloque
    if (key === 'f2' && builderStore.selectedBlockId) {
      event.preventDefault();
      // Abrir panel de propiedades si está cerrado
      if (!uiStore.propertiesPanelOpen) {
        uiStore.togglePropertiesPanel();
      }
      return;
    }

    // 1-3: Cambiar dispositivo preview
    if (key === '1' && event.altKey) {
      event.preventDefault();
      uiStore.setPreviewDevice('desktop');
      return;
    }
    if (key === '2' && event.altKey) {
      event.preventDefault();
      uiStore.setPreviewDevice('tablet');
      return;
    }
    if (key === '3' && event.altKey) {
      event.preventDefault();
      uiStore.setPreviewDevice('mobile');
      return;
    }
  }

  /**
   * Verificar si el elemento es un campo de entrada
   */
  function isInputElement(element) {
    if (!element) return false;
    const tagName = element.tagName.toLowerCase();
    return (
      tagName === 'input' ||
      tagName === 'textarea' ||
      tagName === 'select' ||
      element.isContentEditable
    );
  }

  /**
   * Guardar layout
   */
  async function saveLayout() {
    uiStore.setLoading(true, 'Guardando...');
    const success = await builderStore.save();
    uiStore.setLoading(false);

    if (success) {
      uiStore.showSuccess('Guardado correctamente');
    } else {
      uiStore.showError('Error al guardar');
    }
  }

  /**
   * Duplicar bloque seleccionado
   */
  function duplicateSelectedBlock() {
    if (builderStore.selectedBlockId) {
      const newBlockId = builderStore.duplicateBlock(builderStore.selectedBlockId);
      if (newBlockId) {
        uiStore.showToast('Bloque duplicado', 'success', 1500);
      }
    }
  }

  /**
   * Eliminar bloque seleccionado
   */
  function deleteSelectedBlock() {
    if (builderStore.selectedBlockId) {
      uiStore.confirm(
        '¿Eliminar este bloque?',
        () => {
          builderStore.removeBlock(builderStore.selectedBlockId);
          uiStore.showToast('Bloque eliminado', 'info', 1500);
        }
      );
    } else if (builderStore.selectedSectionId && builderStore.sections.length > 1) {
      const section = builderStore.getSectionById(builderStore.selectedSectionId);
      if (section && section.blocks.length === 0) {
        uiStore.confirm(
          '¿Eliminar esta sección vacía?',
          () => {
            builderStore.removeSection(builderStore.selectedSectionId);
            uiStore.showToast('Sección eliminada', 'info', 1500);
          }
        );
      }
    }
  }

  /**
   * Manejar tecla Escape
   */
  function handleEscape() {
    // Primero cerrar modal si hay alguno abierto
    if (uiStore.hasActiveModal) {
      uiStore.closeModal();
      return;
    }

    // Cerrar menú contextual si está abierto
    if (uiStore.contextMenu) {
      uiStore.closeContextMenu();
      return;
    }

    // Detener edición inline
    if (uiStore.isInlineEditing) {
      uiStore.stopInlineEdit();
      return;
    }

    // Salir de modo preview
    if (uiStore.isPreviewMode) {
      uiStore.togglePreviewMode();
      return;
    }

    // Deseleccionar
    builderStore.clearSelection();
  }

  /**
   * Navegar entre bloques con flechas
   */
  function navigateBlocks(direction) {
    const allBlocks = builderStore.sections.flatMap(s => s.blocks);
    if (allBlocks.length === 0) return;

    if (!builderStore.selectedBlockId) {
      // Seleccionar primer o último bloque
      const targetBlock = direction > 0 ? allBlocks[0] : allBlocks[allBlocks.length - 1];
      builderStore.selectBlock(targetBlock.id);
      return;
    }

    const currentIndex = allBlocks.findIndex(b => b.id === builderStore.selectedBlockId);
    if (currentIndex === -1) return;

    const newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < allBlocks.length) {
      builderStore.selectBlock(allBlocks[newIndex].id);
    }
  }

  /**
   * Mover bloque seleccionado
   */
  function moveSelectedBlock(direction) {
    if (!builderStore.selectedBlockId) return;

    const section = builderStore.getSectionByBlockId(builderStore.selectedBlockId);
    if (!section) return;

    const currentIndex = section.blocks.findIndex(b => b.id === builderStore.selectedBlockId);
    if (currentIndex === -1) return;

    const newIndex = currentIndex + direction;

    // Verificar límites dentro de la sección
    if (newIndex >= 0 && newIndex < section.blocks.length) {
      // Mover dentro de la misma sección
      const [block] = section.blocks.splice(currentIndex, 1);
      section.blocks.splice(newIndex, 0, block);
      builderStore.isDirty = true;
      builderStore.pushHistory();
    } else if (newIndex < 0) {
      // Mover a la sección anterior
      const sectionIndex = builderStore.sections.findIndex(s => s.id === section.id);
      if (sectionIndex > 0) {
        const prevSection = builderStore.sections[sectionIndex - 1];
        builderStore.moveBlock(
          builderStore.selectedBlockId,
          prevSection.id,
          prevSection.blocks.length
        );
      }
    } else {
      // Mover a la sección siguiente
      const sectionIndex = builderStore.sections.findIndex(s => s.id === section.id);
      if (sectionIndex < builderStore.sections.length - 1) {
        const nextSection = builderStore.sections[sectionIndex + 1];
        builderStore.moveBlock(builderStore.selectedBlockId, nextSection.id, 0);
      }
    }
  }

  // Lifecycle
  onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown);
  });

  return {
    saveLayout,
    duplicateSelectedBlock,
    deleteSelectedBlock,
    navigateBlocks,
    moveSelectedBlock,
  };
}
