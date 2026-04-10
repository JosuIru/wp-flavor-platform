/**
 * Web Worker para cálculo de diffs fuera del hilo principal
 * Evita bloqueos de UI en layouts grandes
 */

/**
 * Deep clone de un objeto
 */
function deepClone(obj) {
  if (obj === null || typeof obj !== 'object') return obj;
  return JSON.parse(JSON.stringify(obj));
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

  // Detectar cambios en bloques existentes
  for (const [id, newBlock] of newMap) {
    const oldBlock = oldMap.get(id);
    if (oldBlock) {
      if (JSON.stringify(oldBlock.values) !== JSON.stringify(newBlock.values)) {
        operations.push({
          type: 'updateBlockValues',
          sectionId,
          blockId: id,
          oldValues: deepClone(oldBlock.values),
          newValues: deepClone(newBlock.values),
        });
      }

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

  // Detectar reordenamiento real
  const survivingBlockIds = oldBlocks.filter(b => newMap.has(b.id)).map(b => b.id);
  const newOrderSurvivors = newBlocks.filter(b => oldMap.has(b.id)).map(b => b.id);

  if (JSON.stringify(survivingBlockIds) !== JSON.stringify(newOrderSurvivors)) {
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
      if (JSON.stringify(oldSection.settings) !== JSON.stringify(newSection.settings)) {
        operations.push({
          type: 'updateSectionSettings',
          sectionId: id,
          oldSettings: deepClone(oldSection.settings),
          newSettings: deepClone(newSection.settings),
        });
      }

      const blockOps = diffBlocks(oldSection.blocks, newSection.blocks, id);
      operations.push(...blockOps);
    }
  }

  // Detectar reordenamiento real de secciones
  const survivingSectionIds = oldSections.filter(s => newMap.has(s.id)).map(s => s.id);
  const newOrderSurvivors = newSections.filter(s => oldMap.has(s.id)).map(s => s.id);

  if (JSON.stringify(survivingSectionIds) !== JSON.stringify(newOrderSurvivors)) {
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
 * Calcula el diff entre dos estados
 */
function createDiff(oldState, newState) {
  return {
    sections: diffSections(oldState.sections, newState.sections),
    selectedBlockId: newState.selectedBlockId !== oldState.selectedBlockId
      ? { old: oldState.selectedBlockId, new: newState.selectedBlockId }
      : null,
    selectedSectionId: newState.selectedSectionId !== oldState.selectedSectionId
      ? { old: oldState.selectedSectionId, new: newState.selectedSectionId }
      : null,
  };
}

/**
 * Compresión LZ simple
 */
function compress(input) {
  if (!input) return '';

  const dict = new Map();
  let dictSize = 256;
  let phrase = input[0];
  const result = [];

  for (let i = 1; i < input.length; i++) {
    const char = input[i];
    const combined = phrase + char;

    if (dict.has(combined)) {
      phrase = combined;
    } else {
      result.push(phrase.length > 1 ? dict.get(phrase) : phrase.charCodeAt(0));
      if (dictSize < 65536) {
        dict.set(combined, dictSize++);
      }
      phrase = char;
    }
  }

  result.push(phrase.length > 1 ? dict.get(phrase) : phrase.charCodeAt(0));
  return btoa(String.fromCharCode(...result.map(code => code & 0xFF)));
}

// Handler de mensajes del worker
self.onmessage = function(event) {
  const { type, id, payload } = event.data;

  try {
    let result;

    switch (type) {
      case 'createDiff':
        result = createDiff(payload.oldState, payload.newState);
        break;

      case 'createDiffCompressed':
        const diff = createDiff(payload.oldState, payload.newState);
        result = {
          diff,
          compressed: compress(JSON.stringify(diff)),
        };
        break;

      case 'compress':
        result = compress(JSON.stringify(payload.data));
        break;

      default:
        throw new Error(`Unknown message type: ${type}`);
    }

    self.postMessage({ id, success: true, result });
  } catch (error) {
    self.postMessage({ id, success: false, error: error.message });
  }
};
