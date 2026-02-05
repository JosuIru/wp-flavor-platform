import { computed } from 'vue';
import { useBuilderStore } from '../stores/builderStore';
import { useUiStore } from '../stores/uiStore';

/**
 * Composable para operaciones CRUD del layout
 */
export function useLayout() {
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  /**
   * Añadir nueva sección al layout
   */
  function addSection(position = -1) {
    const sectionId = builderStore.addSection(position);
    builderStore.selectSection(sectionId);
    return sectionId;
  }

  /**
   * Añadir bloque a una sección
   */
  function addBlock(sectionId, componentId, position = -1, values = {}, variant = null) {
    // Si no hay sección, crear una
    let targetSectionId = sectionId;
    if (!targetSectionId) {
      if (builderStore.sections.length === 0) {
        targetSectionId = addSection();
      } else {
        targetSectionId = builderStore.selectedSectionId || builderStore.sections[0].id;
      }
    }

    const blockId = builderStore.addBlock(targetSectionId, componentId, position, values, variant);
    return blockId;
  }

  /**
   * Duplicar bloque
   */
  function duplicateBlock(blockId) {
    const newBlockId = builderStore.duplicateBlock(blockId);
    if (newBlockId) {
      uiStore.showSuccess('Bloque duplicado');
    }
    return newBlockId;
  }

  /**
   * Duplicar sección
   */
  function duplicateSection(sectionId) {
    const newSectionId = builderStore.duplicateSection(sectionId);
    if (newSectionId) {
      uiStore.showSuccess('Sección duplicada');
    }
    return newSectionId;
  }

  /**
   * Eliminar bloque con confirmación
   */
  function removeBlock(blockId, skipConfirm = false) {
    const performDelete = () => {
      builderStore.removeBlock(blockId);
      uiStore.showToast('Bloque eliminado', 'info', 1500);
    };

    if (skipConfirm) {
      performDelete();
    } else {
      uiStore.confirm('¿Eliminar este bloque?', performDelete);
    }
  }

  /**
   * Eliminar sección con confirmación
   */
  function removeSection(sectionId, skipConfirm = false) {
    const section = builderStore.getSectionById(sectionId);
    if (!section) return;

    const hasBlocks = section.blocks.length > 0;

    const performDelete = () => {
      builderStore.removeSection(sectionId);
      uiStore.showToast('Sección eliminada', 'info', 1500);
    };

    if (skipConfirm) {
      performDelete();
    } else {
      const message = hasBlocks
        ? `¿Eliminar esta sección y sus ${section.blocks.length} bloque(s)?`
        : '¿Eliminar esta sección vacía?';
      uiStore.confirm(message, performDelete);
    }
  }

  /**
   * Mover bloque hacia arriba
   */
  function moveBlockUp(blockId) {
    const section = builderStore.getSectionByBlockId(blockId);
    if (!section) return;

    const currentIndex = section.blocks.findIndex(b => b.id === blockId);
    if (currentIndex <= 0) {
      // Mover a sección anterior si existe
      const sectionIndex = builderStore.sections.findIndex(s => s.id === section.id);
      if (sectionIndex > 0) {
        const prevSection = builderStore.sections[sectionIndex - 1];
        builderStore.moveBlock(blockId, prevSection.id, prevSection.blocks.length);
      }
      return;
    }

    builderStore.moveBlock(blockId, section.id, currentIndex - 1);
  }

  /**
   * Mover bloque hacia abajo
   */
  function moveBlockDown(blockId) {
    const section = builderStore.getSectionByBlockId(blockId);
    if (!section) return;

    const currentIndex = section.blocks.findIndex(b => b.id === blockId);
    if (currentIndex >= section.blocks.length - 1) {
      // Mover a sección siguiente si existe
      const sectionIndex = builderStore.sections.findIndex(s => s.id === section.id);
      if (sectionIndex < builderStore.sections.length - 1) {
        const nextSection = builderStore.sections[sectionIndex + 1];
        builderStore.moveBlock(blockId, nextSection.id, 0);
      }
      return;
    }

    builderStore.moveBlock(blockId, section.id, currentIndex + 2);
  }

  /**
   * Mover sección hacia arriba
   */
  function moveSectionUp(sectionId) {
    const currentIndex = builderStore.sections.findIndex(s => s.id === sectionId);
    if (currentIndex <= 0) return;

    builderStore.moveSection(sectionId, currentIndex - 1);
  }

  /**
   * Mover sección hacia abajo
   */
  function moveSectionDown(sectionId) {
    const currentIndex = builderStore.sections.findIndex(s => s.id === sectionId);
    if (currentIndex >= builderStore.sections.length - 1) return;

    builderStore.moveSection(sectionId, currentIndex + 1);
  }

  /**
   * Actualizar configuración de sección
   */
  function updateSectionSettings(sectionId, settings) {
    const section = builderStore.getSectionById(sectionId);
    if (section) {
      section.settings = { ...section.settings, ...settings };
      builderStore.isDirty = true;
      builderStore.pushHistory();
    }
  }

  /**
   * Limpiar layout completo
   */
  function clearLayout(skipConfirm = false) {
    const performClear = () => {
      builderStore.sections = [];
      builderStore.selectedBlockId = null;
      builderStore.selectedSectionId = null;
      builderStore.previewHtmlCache = {};
      builderStore.isDirty = true;
      builderStore.pushHistory();
      uiStore.showToast('Layout limpiado', 'info', 1500);
    };

    if (skipConfirm) {
      performClear();
    } else {
      uiStore.confirm('¿Limpiar todo el layout? Esta acción no se puede deshacer.', performClear);
    }
  }

  /**
   * Cargar plantilla
   */
  function loadTemplate(templateLayout, skipConfirm = false) {
    const performLoad = () => {
      builderStore.loadFromLegacyLayout(templateLayout);
      builderStore.isDirty = true;
      builderStore.pushHistory();
      builderStore.refreshAllPreviews();
      uiStore.showSuccess('Plantilla cargada');
    };

    if (skipConfirm) {
      performLoad();
    } else {
      uiStore.confirm(
        '¿Cargar esta plantilla? Se reemplazará el contenido actual.',
        performLoad
      );
    }
  }

  /**
   * Exportar layout como JSON
   */
  function exportLayout() {
    const json = builderStore.exportLayout();
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);

    const downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.download = `layout-${Date.now()}.json`;
    downloadLink.click();

    URL.revokeObjectURL(url);
    uiStore.showSuccess('Layout exportado');
  }

  /**
   * Importar layout desde archivo JSON
   */
  function importLayout(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = (event) => {
        try {
          const success = builderStore.importLayout(event.target.result);
          if (success) {
            uiStore.showSuccess('Layout importado');
            resolve(true);
          } else {
            uiStore.showError('Error al importar el layout');
            resolve(false);
          }
        } catch (error) {
          uiStore.showError('Archivo JSON inválido');
          reject(error);
        }
      };

      reader.onerror = () => {
        uiStore.showError('Error al leer el archivo');
        reject(new Error('Error reading file'));
      };

      reader.readAsText(file);
    });
  }

  // Computed helpers
  const sections = computed(() => builderStore.sections);
  const selectedBlock = computed(() => builderStore.selectedBlock);
  const selectedSection = computed(() =>
    builderStore.selectedSectionId
      ? builderStore.getSectionById(builderStore.selectedSectionId)
      : null
  );
  const totalBlocks = computed(() =>
    builderStore.sections.reduce((total, section) => total + section.blocks.length, 0)
  );
  const isEmpty = computed(() => totalBlocks.value === 0);

  return {
    // Operaciones de sección
    addSection,
    duplicateSection,
    removeSection,
    moveSectionUp,
    moveSectionDown,
    updateSectionSettings,

    // Operaciones de bloque
    addBlock,
    duplicateBlock,
    removeBlock,
    moveBlockUp,
    moveBlockDown,

    // Operaciones de layout
    clearLayout,
    loadTemplate,
    exportLayout,
    importLayout,

    // Computed
    sections,
    selectedBlock,
    selectedSection,
    totalBlocks,
    isEmpty,
  };
}
