/**
 * Sistema de historial con diffs para el Page Builder
 *
 * En lugar de guardar snapshots completos, guarda:
 * - Un snapshot base cada N cambios (checkpoint)
 * - Diffs incrementales entre checkpoints
 *
 * Esto reduce significativamente el uso de memoria para layouts grandes.
 */

/**
 * Calcula el diff entre dos estados
 * @param {Object} oldState - Estado anterior
 * @param {Object} newState - Estado nuevo
 * @returns {Object} Diff con operaciones para ir de old a new
 */
export function createDiff(oldState, newState) {
  const diff = {
    sections: diffSections(oldState.sections, newState.sections),
    selectedBlockId: newState.selectedBlockId !== oldState.selectedBlockId
      ? { old: oldState.selectedBlockId, new: newState.selectedBlockId }
      : null,
    selectedSectionId: newState.selectedSectionId !== oldState.selectedSectionId
      ? { old: oldState.selectedSectionId, new: newState.selectedSectionId }
      : null,
  };

  return diff;
}

/**
 * Calcula el diff entre dos arrays de secciones
 */
function diffSections(oldSections, newSections) {
  const operations = [];
  const oldMap = new Map(oldSections.map(s => [s.id, s]));
  const newMap = new Map(newSections.map(s => [s.id, s]));

  // Detectar secciones eliminadas
  for (const [id, section] of oldMap) {
    if (!newMap.has(id)) {
      operations.push({
        type: 'removeSection',
        sectionId: id,
        section: deepClone(section),
        index: oldSections.findIndex(s => s.id === id),
      });
    }
  }

  // Detectar secciones añadidas
  for (const [id, section] of newMap) {
    if (!oldMap.has(id)) {
      operations.push({
        type: 'addSection',
        sectionId: id,
        section: deepClone(section),
        index: newSections.findIndex(s => s.id === id),
      });
    }
  }

  // Detectar cambios en secciones existentes
  for (const [id, newSection] of newMap) {
    const oldSection = oldMap.get(id);
    if (oldSection) {
      // Verificar cambios en settings
      if (JSON.stringify(oldSection.settings) !== JSON.stringify(newSection.settings)) {
        operations.push({
          type: 'updateSectionSettings',
          sectionId: id,
          oldSettings: deepClone(oldSection.settings),
          newSettings: deepClone(newSection.settings),
        });
      }

      // Verificar cambios en bloques
      const blockOps = diffBlocks(oldSection.blocks, newSection.blocks, id);
      operations.push(...blockOps);
    }
  }

  // Detectar reordenamiento real de secciones (solo entre sobrevivientes)
  const survivingSectionIds = oldSections
    .filter(s => newMap.has(s.id))
    .map(s => s.id);
  const newOrderSurvivors = newSections
    .filter(s => oldMap.has(s.id))
    .map(s => s.id);

  // Solo generar moveSection si el orden relativo de sobrevivientes cambió
  if (JSON.stringify(survivingSectionIds) !== JSON.stringify(newOrderSurvivors)) {
    // Generar operaciones de movimiento basadas en el nuevo orden
    for (let i = 0; i < newOrderSurvivors.length; i++) {
      const sectionId = newOrderSurvivors[i];
      const oldRelativeIndex = survivingSectionIds.indexOf(sectionId);
      if (oldRelativeIndex !== i) {
        operations.push({
          type: 'moveSection',
          sectionId,
          oldIndex: oldSections.findIndex(s => s.id === sectionId),
          newIndex: newSections.findIndex(s => s.id === sectionId),
        });
      }
    }
  }

  return operations;
}

/**
 * Calcula el diff entre dos arrays de bloques
 */
function diffBlocks(oldBlocks, newBlocks, sectionId) {
  const operations = [];
  const oldMap = new Map(oldBlocks.map(b => [b.id, b]));
  const newMap = new Map(newBlocks.map(b => [b.id, b]));

  // Detectar bloques eliminados
  for (const [id, block] of oldMap) {
    if (!newMap.has(id)) {
      operations.push({
        type: 'removeBlock',
        sectionId,
        blockId: id,
        block: deepClone(block),
        index: oldBlocks.findIndex(b => b.id === id),
      });
    }
  }

  // Detectar bloques añadidos
  for (const [id, block] of newMap) {
    if (!oldMap.has(id)) {
      operations.push({
        type: 'addBlock',
        sectionId,
        blockId: id,
        block: deepClone(block),
        index: newBlocks.findIndex(b => b.id === id),
      });
    }
  }

  // Detectar cambios en bloques existentes (valores y variantes)
  for (const [id, newBlock] of newMap) {
    const oldBlock = oldMap.get(id);
    if (oldBlock) {
      // Verificar cambios en valores
      if (JSON.stringify(oldBlock.values) !== JSON.stringify(newBlock.values)) {
        operations.push({
          type: 'updateBlockValues',
          sectionId,
          blockId: id,
          oldValues: deepClone(oldBlock.values),
          newValues: deepClone(newBlock.values),
        });
      }

      // Verificar cambio de variant
      if (oldBlock.variant !== newBlock.variant) {
        operations.push({
          type: 'updateBlockVariant',
          sectionId,
          blockId: id,
          oldVariant: oldBlock.variant,
          newVariant: newBlock.variant,
        });
      }
    }
  }

  // Detectar reordenamiento real de bloques (solo entre sobrevivientes)
  const survivingBlockIds = oldBlocks
    .filter(b => newMap.has(b.id))
    .map(b => b.id);
  const newOrderSurvivors = newBlocks
    .filter(b => oldMap.has(b.id))
    .map(b => b.id);

  // Solo generar moveBlock si el orden relativo de sobrevivientes cambió
  if (JSON.stringify(survivingBlockIds) !== JSON.stringify(newOrderSurvivors)) {
    // Generar operaciones de movimiento basadas en el nuevo orden
    for (let i = 0; i < newOrderSurvivors.length; i++) {
      const blockId = newOrderSurvivors[i];
      const oldRelativeIndex = survivingBlockIds.indexOf(blockId);
      if (oldRelativeIndex !== i) {
        operations.push({
          type: 'moveBlock',
          sectionId,
          blockId,
          oldIndex: oldBlocks.findIndex(b => b.id === blockId),
          newIndex: newBlocks.findIndex(b => b.id === blockId),
        });
      }
    }
  }

  return operations;
}

/**
 * Aplica un diff hacia adelante (para redo)
 * @param {Object} state - Estado actual (se modifica in-place)
 * @param {Object} diff - Diff a aplicar
 */
export function applyDiff(state, diff) {
  // Aplicar cambios de selección
  if (diff.selectedBlockId) {
    state.selectedBlockId = diff.selectedBlockId.new;
  }
  if (diff.selectedSectionId) {
    state.selectedSectionId = diff.selectedSectionId.new;
  }

  // Aplicar operaciones de secciones/bloques
  for (const op of diff.sections) {
    applyOperation(state, op, false);
  }
}

/**
 * Revierte un diff (para undo)
 * @param {Object} state - Estado actual (se modifica in-place)
 * @param {Object} diff - Diff a revertir
 */
export function revertDiff(state, diff) {
  // Revertir cambios de selección
  if (diff.selectedBlockId) {
    state.selectedBlockId = diff.selectedBlockId.old;
  }
  if (diff.selectedSectionId) {
    state.selectedSectionId = diff.selectedSectionId.old;
  }

  // Revertir operaciones en orden inverso
  const reversedOps = [...diff.sections].reverse();
  for (const op of reversedOps) {
    applyOperation(state, op, true);
  }
}

/**
 * Aplica una operación individual
 * @param {Object} state - Estado
 * @param {Object} op - Operación
 * @param {boolean} reverse - Si es true, aplica la operación inversa
 */
function applyOperation(state, op, reverse) {
  const sectionMap = new Map(state.sections.map(s => [s.id, s]));

  switch (op.type) {
    case 'addSection':
      if (reverse) {
        // Revertir: eliminar la sección
        state.sections = state.sections.filter(s => s.id !== op.sectionId);
      } else {
        // Aplicar: añadir la sección
        state.sections.splice(op.index, 0, deepClone(op.section));
      }
      break;

    case 'removeSection':
      if (reverse) {
        // Revertir: restaurar la sección
        state.sections.splice(op.index, 0, deepClone(op.section));
      } else {
        // Aplicar: eliminar la sección
        state.sections = state.sections.filter(s => s.id !== op.sectionId);
      }
      break;

    case 'moveSection':
      if (reverse) {
        // Revertir: mover de newIndex a oldIndex
        const [section] = state.sections.splice(op.newIndex, 1);
        state.sections.splice(op.oldIndex, 0, section);
      } else {
        // Aplicar: mover de oldIndex a newIndex
        const [section] = state.sections.splice(op.oldIndex, 1);
        state.sections.splice(op.newIndex, 0, section);
      }
      break;

    case 'updateSectionSettings': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        section.settings = deepClone(reverse ? op.oldSettings : op.newSettings);
      }
      break;
    }

    case 'addBlock': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        if (reverse) {
          // Revertir: eliminar el bloque
          section.blocks = section.blocks.filter(b => b.id !== op.blockId);
        } else {
          // Aplicar: añadir el bloque
          section.blocks.splice(op.index, 0, deepClone(op.block));
        }
      }
      break;
    }

    case 'removeBlock': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        if (reverse) {
          // Revertir: restaurar el bloque
          section.blocks.splice(op.index, 0, deepClone(op.block));
        } else {
          // Aplicar: eliminar el bloque
          section.blocks = section.blocks.filter(b => b.id !== op.blockId);
        }
      }
      break;
    }

    case 'moveBlock': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        if (reverse) {
          const [block] = section.blocks.splice(op.newIndex, 1);
          section.blocks.splice(op.oldIndex, 0, block);
        } else {
          const [block] = section.blocks.splice(op.oldIndex, 1);
          section.blocks.splice(op.newIndex, 0, block);
        }
      }
      break;
    }

    case 'updateBlockValues': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        const block = section.blocks.find(b => b.id === op.blockId);
        if (block) {
          block.values = deepClone(reverse ? op.oldValues : op.newValues);
        }
      }
      break;
    }

    case 'updateBlockVariant': {
      const section = sectionMap.get(op.sectionId);
      if (section) {
        const block = section.blocks.find(b => b.id === op.blockId);
        if (block) {
          block.variant = reverse ? op.oldVariant : op.newVariant;
        }
      }
      break;
    }
  }
}

/**
 * Crea un snapshot del estado para checkpoints
 */
export function createSnapshot(state) {
  return {
    sections: deepClone(state.sections),
    selectedBlockId: state.selectedBlockId,
    selectedSectionId: state.selectedSectionId,
  };
}

/**
 * Restaura un estado desde un snapshot
 */
export function restoreSnapshot(state, snapshot) {
  state.sections = deepClone(snapshot.sections);
  state.selectedBlockId = snapshot.selectedBlockId;
  state.selectedSectionId = snapshot.selectedSectionId;
}

/**
 * Verifica si un diff está vacío (no hay cambios)
 */
export function isDiffEmpty(diff) {
  return (
    diff.sections.length === 0 &&
    diff.selectedBlockId === null &&
    diff.selectedSectionId === null
  );
}

/**
 * Estima el tamaño en bytes de un objeto
 */
export function estimateSize(obj) {
  return JSON.stringify(obj).length * 2; // UTF-16
}

/**
 * Deep clone de un objeto
 */
function deepClone(obj) {
  if (obj === null || typeof obj !== 'object') return obj;
  return JSON.parse(JSON.stringify(obj));
}
