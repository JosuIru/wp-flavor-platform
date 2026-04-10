/**
 * Tests para el sistema de historial con diffs
 *
 * @vitest-environment node
 */

import { describe, it, expect, beforeEach } from 'vitest';
import {
  createDiff,
  applyDiff,
  revertDiff,
  createSnapshot,
  restoreSnapshot,
  isDiffEmpty,
  estimateSize,
} from './historyDiff.js';

// Helpers para crear estados de prueba
function createTestState(sections = [], selectedBlockId = null, selectedSectionId = null) {
  return { sections, selectedBlockId, selectedSectionId };
}

function createTestSection(id, blocks = [], settings = {}) {
  return {
    id,
    settings: { fullWidth: false, backgroundColor: '', ...settings },
    blocks,
  };
}

function createTestBlock(id, componentId = 'test-component', values = {}, variant = null) {
  return { id, componentId, values, variant };
}

describe('historyDiff', () => {
  describe('createDiff', () => {
    it('debería detectar cuando no hay cambios', () => {
      const state = createTestState([
        createTestSection('s1', [createTestBlock('b1')]),
      ]);

      const diff = createDiff(state, state);

      expect(isDiffEmpty(diff)).toBe(true);
    });

    it('debería detectar cambio de selección de bloque', () => {
      const oldState = createTestState([], 'block-1', null);
      const newState = createTestState([], 'block-2', null);

      const diff = createDiff(oldState, newState);

      expect(diff.selectedBlockId).toEqual({ old: 'block-1', new: 'block-2' });
    });

    it('debería detectar cambio de selección de sección', () => {
      const oldState = createTestState([], null, 'section-1');
      const newState = createTestState([], null, 'section-2');

      const diff = createDiff(oldState, newState);

      expect(diff.selectedSectionId).toEqual({ old: 'section-1', new: 'section-2' });
    });

    it('debería detectar sección añadida', () => {
      const oldState = createTestState([]);
      const newSection = createTestSection('s1', [createTestBlock('b1')]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const addOp = diff.sections.find(op => op.type === 'addSection');
      expect(addOp).toBeDefined();
      expect(addOp.sectionId).toBe('s1');
      expect(addOp.index).toBe(0);
    });

    it('debería detectar sección eliminada', () => {
      const section = createTestSection('s1', [createTestBlock('b1')]);
      const oldState = createTestState([section]);
      const newState = createTestState([]);

      const diff = createDiff(oldState, newState);

      const removeOp = diff.sections.find(op => op.type === 'removeSection');
      expect(removeOp).toBeDefined();
      expect(removeOp.sectionId).toBe('s1');
    });

    it('debería detectar cambio en settings de sección', () => {
      const oldSection = createTestSection('s1', [], { backgroundColor: 'red' });
      const newSection = createTestSection('s1', [], { backgroundColor: 'blue' });
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const updateOp = diff.sections.find(op => op.type === 'updateSectionSettings');
      expect(updateOp).toBeDefined();
      expect(updateOp.oldSettings.backgroundColor).toBe('red');
      expect(updateOp.newSettings.backgroundColor).toBe('blue');
    });

    it('debería detectar bloque añadido', () => {
      const oldSection = createTestSection('s1', []);
      const newSection = createTestSection('s1', [createTestBlock('b1')]);
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const addOp = diff.sections.find(op => op.type === 'addBlock');
      expect(addOp).toBeDefined();
      expect(addOp.blockId).toBe('b1');
      expect(addOp.sectionId).toBe('s1');
    });

    it('debería detectar bloque eliminado', () => {
      const oldSection = createTestSection('s1', [createTestBlock('b1')]);
      const newSection = createTestSection('s1', []);
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const removeOp = diff.sections.find(op => op.type === 'removeBlock');
      expect(removeOp).toBeDefined();
      expect(removeOp.blockId).toBe('b1');
    });

    it('debería detectar cambio en valores de bloque', () => {
      const oldBlock = createTestBlock('b1', 'hero', { title: 'Old Title' });
      const newBlock = createTestBlock('b1', 'hero', { title: 'New Title' });
      const oldSection = createTestSection('s1', [oldBlock]);
      const newSection = createTestSection('s1', [newBlock]);
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const updateOp = diff.sections.find(op => op.type === 'updateBlockValues');
      expect(updateOp).toBeDefined();
      expect(updateOp.oldValues.title).toBe('Old Title');
      expect(updateOp.newValues.title).toBe('New Title');
    });

    it('debería detectar cambio de variante de bloque', () => {
      const oldBlock = createTestBlock('b1', 'hero', {}, 'light');
      const newBlock = createTestBlock('b1', 'hero', {}, 'dark');
      const oldSection = createTestSection('s1', [oldBlock]);
      const newSection = createTestSection('s1', [newBlock]);
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const updateOp = diff.sections.find(op => op.type === 'updateBlockVariant');
      expect(updateOp).toBeDefined();
      expect(updateOp.oldVariant).toBe('light');
      expect(updateOp.newVariant).toBe('dark');
    });

    it('debería detectar movimiento de bloque dentro de sección', () => {
      const block1 = createTestBlock('b1');
      const block2 = createTestBlock('b2');
      const oldSection = createTestSection('s1', [block1, block2]);
      const newSection = createTestSection('s1', [block2, block1]); // Orden invertido
      const oldState = createTestState([oldSection]);
      const newState = createTestState([newSection]);

      const diff = createDiff(oldState, newState);

      const moveOps = diff.sections.filter(op => op.type === 'moveBlock');
      expect(moveOps.length).toBeGreaterThan(0);
    });
  });

  describe('applyDiff', () => {
    it('debería aplicar cambio de selección', () => {
      const state = createTestState([], 'old-block', 'old-section');
      const diff = {
        sections: [],
        selectedBlockId: { old: 'old-block', new: 'new-block' },
        selectedSectionId: { old: 'old-section', new: 'new-section' },
      };

      applyDiff(state, diff);

      expect(state.selectedBlockId).toBe('new-block');
      expect(state.selectedSectionId).toBe('new-section');
    });

    it('debería aplicar añadir sección', () => {
      const state = createTestState([]);
      const newSection = createTestSection('s1', [createTestBlock('b1')]);
      const diff = {
        sections: [{
          type: 'addSection',
          sectionId: 's1',
          section: newSection,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      applyDiff(state, diff);

      expect(state.sections.length).toBe(1);
      expect(state.sections[0].id).toBe('s1');
    });

    it('debería aplicar eliminar sección', () => {
      const section = createTestSection('s1');
      const state = createTestState([section]);
      const diff = {
        sections: [{
          type: 'removeSection',
          sectionId: 's1',
          section: section,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      applyDiff(state, diff);

      expect(state.sections.length).toBe(0);
    });

    it('debería aplicar añadir bloque', () => {
      const section = createTestSection('s1', []);
      const state = createTestState([section]);
      const newBlock = createTestBlock('b1', 'hero', { title: 'Test' });
      const diff = {
        sections: [{
          type: 'addBlock',
          sectionId: 's1',
          blockId: 'b1',
          block: newBlock,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      applyDiff(state, diff);

      expect(state.sections[0].blocks.length).toBe(1);
      expect(state.sections[0].blocks[0].id).toBe('b1');
    });

    it('debería aplicar actualizar valores de bloque', () => {
      const block = createTestBlock('b1', 'hero', { title: 'Old' });
      const section = createTestSection('s1', [block]);
      const state = createTestState([section]);
      const diff = {
        sections: [{
          type: 'updateBlockValues',
          sectionId: 's1',
          blockId: 'b1',
          oldValues: { title: 'Old' },
          newValues: { title: 'New' },
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      applyDiff(state, diff);

      expect(state.sections[0].blocks[0].values.title).toBe('New');
    });
  });

  describe('revertDiff', () => {
    it('debería revertir cambio de selección', () => {
      const state = createTestState([], 'new-block', 'new-section');
      const diff = {
        sections: [],
        selectedBlockId: { old: 'old-block', new: 'new-block' },
        selectedSectionId: { old: 'old-section', new: 'new-section' },
      };

      revertDiff(state, diff);

      expect(state.selectedBlockId).toBe('old-block');
      expect(state.selectedSectionId).toBe('old-section');
    });

    it('debería revertir añadir sección (eliminarla)', () => {
      const section = createTestSection('s1');
      const state = createTestState([section]);
      const diff = {
        sections: [{
          type: 'addSection',
          sectionId: 's1',
          section: section,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      revertDiff(state, diff);

      expect(state.sections.length).toBe(0);
    });

    it('debería revertir eliminar sección (restaurarla)', () => {
      const section = createTestSection('s1', [createTestBlock('b1')]);
      const state = createTestState([]);
      const diff = {
        sections: [{
          type: 'removeSection',
          sectionId: 's1',
          section: section,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      revertDiff(state, diff);

      expect(state.sections.length).toBe(1);
      expect(state.sections[0].id).toBe('s1');
    });

    it('debería revertir añadir bloque (eliminarlo)', () => {
      const block = createTestBlock('b1');
      const section = createTestSection('s1', [block]);
      const state = createTestState([section]);
      const diff = {
        sections: [{
          type: 'addBlock',
          sectionId: 's1',
          blockId: 'b1',
          block: block,
          index: 0,
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      revertDiff(state, diff);

      expect(state.sections[0].blocks.length).toBe(0);
    });

    it('debería revertir actualizar valores de bloque', () => {
      const block = createTestBlock('b1', 'hero', { title: 'New' });
      const section = createTestSection('s1', [block]);
      const state = createTestState([section]);
      const diff = {
        sections: [{
          type: 'updateBlockValues',
          sectionId: 's1',
          blockId: 'b1',
          oldValues: { title: 'Old' },
          newValues: { title: 'New' },
        }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      revertDiff(state, diff);

      expect(state.sections[0].blocks[0].values.title).toBe('Old');
    });
  });

  describe('createSnapshot / restoreSnapshot', () => {
    it('debería crear snapshot inmutable', () => {
      const block = createTestBlock('b1', 'hero', { title: 'Test' });
      const section = createTestSection('s1', [block]);
      const state = createTestState([section], 'b1', 's1');

      const snapshot = createSnapshot(state);

      // Modificar estado original
      state.sections[0].blocks[0].values.title = 'Modified';
      state.selectedBlockId = 'other';

      // Snapshot no debería verse afectado
      expect(snapshot.sections[0].blocks[0].values.title).toBe('Test');
      expect(snapshot.selectedBlockId).toBe('b1');
    });

    it('debería restaurar snapshot correctamente', () => {
      const state = createTestState([], null, null);
      const snapshot = {
        sections: [createTestSection('s1', [createTestBlock('b1')])],
        selectedBlockId: 'b1',
        selectedSectionId: 's1',
      };

      restoreSnapshot(state, snapshot);

      expect(state.sections.length).toBe(1);
      expect(state.sections[0].id).toBe('s1');
      expect(state.selectedBlockId).toBe('b1');
      expect(state.selectedSectionId).toBe('s1');
    });
  });

  describe('isDiffEmpty', () => {
    it('debería retornar true para diff vacío', () => {
      const diff = {
        sections: [],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      expect(isDiffEmpty(diff)).toBe(true);
    });

    it('debería retornar false si hay cambio de selección', () => {
      const diff = {
        sections: [],
        selectedBlockId: { old: 'a', new: 'b' },
        selectedSectionId: null,
      };

      expect(isDiffEmpty(diff)).toBe(false);
    });

    it('debería retornar false si hay operaciones en sections', () => {
      const diff = {
        sections: [{ type: 'addSection', sectionId: 's1' }],
        selectedBlockId: null,
        selectedSectionId: null,
      };

      expect(isDiffEmpty(diff)).toBe(false);
    });
  });

  describe('estimateSize', () => {
    it('debería estimar tamaño de objeto', () => {
      const obj = { test: 'value', nested: { a: 1, b: 2 } };
      const size = estimateSize(obj);

      expect(size).toBeGreaterThan(0);
      expect(typeof size).toBe('number');
    });
  });

  describe('Integración: ciclo completo undo/redo', () => {
    it('debería poder hacer undo y redo múltiples cambios', () => {
      // Estado inicial
      const initialState = createTestState([
        createTestSection('s1', [createTestBlock('b1', 'hero', { title: 'Initial' })]),
      ], 'b1', 's1');

      // Cambio 1: actualizar título
      const state1 = createTestState([
        createTestSection('s1', [createTestBlock('b1', 'hero', { title: 'Change 1' })]),
      ], 'b1', 's1');

      // Cambio 2: añadir bloque
      const state2 = createTestState([
        createTestSection('s1', [
          createTestBlock('b1', 'hero', { title: 'Change 1' }),
          createTestBlock('b2', 'text', { content: 'New block' }),
        ]),
      ], 'b2', 's1');

      // Crear diffs
      const diff1 = createDiff(initialState, state1);
      const diff2 = createDiff(state1, state2);

      // Aplicar ambos diffs al estado inicial
      const currentState = JSON.parse(JSON.stringify(initialState));
      applyDiff(currentState, diff1);
      applyDiff(currentState, diff2);

      // Verificar estado final
      expect(currentState.sections[0].blocks.length).toBe(2);
      expect(currentState.sections[0].blocks[0].values.title).toBe('Change 1');
      expect(currentState.sections[0].blocks[1].id).toBe('b2');

      // Undo: revertir diff2
      revertDiff(currentState, diff2);
      expect(currentState.sections[0].blocks.length).toBe(1);
      expect(currentState.selectedBlockId).toBe('b1');

      // Undo: revertir diff1
      revertDiff(currentState, diff1);
      expect(currentState.sections[0].blocks[0].values.title).toBe('Initial');

      // Redo: aplicar diff1
      applyDiff(currentState, diff1);
      expect(currentState.sections[0].blocks[0].values.title).toBe('Change 1');

      // Redo: aplicar diff2
      applyDiff(currentState, diff2);
      expect(currentState.sections[0].blocks.length).toBe(2);
    });

    it('debería manejar añadir y eliminar bloque secuencialmente', () => {
      // Estado inicial con un bloque
      const state0 = createTestState([
        createTestSection('s1', [createTestBlock('b1')]),
      ]);

      // Añadir segundo bloque
      const state1 = createTestState([
        createTestSection('s1', [
          createTestBlock('b1'),
          createTestBlock('b2', 'text', { content: 'New' }),
        ]),
      ]);

      // Eliminar primer bloque
      const state2 = createTestState([
        createTestSection('s1', [
          createTestBlock('b2', 'text', { content: 'New' }),
        ]),
      ]);

      // Diffs
      const diff1 = createDiff(state0, state1);
      const diff2 = createDiff(state1, state2);

      // Aplicar ambos diffs
      const testState = JSON.parse(JSON.stringify(state0));
      applyDiff(testState, diff1);
      expect(testState.sections[0].blocks.length).toBe(2);

      applyDiff(testState, diff2);
      expect(testState.sections[0].blocks.length).toBe(1);
      expect(testState.sections[0].blocks[0].id).toBe('b2');

      // Revertir ambos
      revertDiff(testState, diff2);
      expect(testState.sections[0].blocks.length).toBe(2);

      revertDiff(testState, diff1);
      expect(testState.sections[0].blocks.length).toBe(1);
      expect(testState.sections[0].blocks[0].id).toBe('b1');
    });

    it('debería manejar añadir y eliminar sección', () => {
      // Estado inicial con una sección
      const state0 = createTestState([
        createTestSection('s1', [createTestBlock('b1')]),
      ]);

      // Añadir segunda sección con bloque
      const state1 = createTestState([
        createTestSection('s1', [createTestBlock('b1')]),
        createTestSection('s2', [createTestBlock('b2')]),
      ]);

      // Eliminar primera sección
      const state2 = createTestState([
        createTestSection('s2', [createTestBlock('b2')]),
      ]);

      const diff1 = createDiff(state0, state1);
      const diff2 = createDiff(state1, state2);

      // Aplicar
      const testState = JSON.parse(JSON.stringify(state0));
      applyDiff(testState, diff1);
      expect(testState.sections.length).toBe(2);

      applyDiff(testState, diff2);
      expect(testState.sections.length).toBe(1);
      expect(testState.sections[0].id).toBe('s2');

      // Revertir
      revertDiff(testState, diff2);
      expect(testState.sections.length).toBe(2);

      revertDiff(testState, diff1);
      expect(testState.sections.length).toBe(1);
      expect(testState.sections[0].id).toBe('s1');
    });
  });

  describe('Casos edge', () => {
    it('debería manejar estado vacío', () => {
      const emptyState = createTestState([]);
      const diff = createDiff(emptyState, emptyState);

      expect(isDiffEmpty(diff)).toBe(true);
    });

    it('debería manejar secciones sin bloques', () => {
      const oldState = createTestState([createTestSection('s1', [])]);
      const newState = createTestState([createTestSection('s1', [])]);

      const diff = createDiff(oldState, newState);

      expect(isDiffEmpty(diff)).toBe(true);
    });

    it('debería preservar datos de bloque al mover', () => {
      const complexBlock = createTestBlock('b1', 'hero', {
        title: 'Test',
        subtitle: 'Subtitle',
        nested: { a: 1, b: [1, 2, 3] },
      }, 'dark');

      const oldState = createTestState([
        createTestSection('s1', [complexBlock]),
        createTestSection('s2', []),
      ]);

      // "Mover" bloque a otra sección (simulado con remove + add)
      const newState = createTestState([
        createTestSection('s1', []),
        createTestSection('s2', [complexBlock]),
      ]);

      const diff = createDiff(oldState, newState);
      const testState = JSON.parse(JSON.stringify(oldState));
      applyDiff(testState, diff);

      // Verificar que el bloque se movió con todos sus datos
      expect(testState.sections[1].blocks[0].values.nested.b).toEqual([1, 2, 3]);
      expect(testState.sections[1].blocks[0].variant).toBe('dark');
    });

    it('debería manejar valores null y undefined', () => {
      const block1 = createTestBlock('b1', 'test', { value: null });
      const block2 = createTestBlock('b1', 'test', { value: 'defined' });

      const oldState = createTestState([createTestSection('s1', [block1])]);
      const newState = createTestState([createTestSection('s1', [block2])]);

      const diff = createDiff(oldState, newState);

      expect(diff.sections.length).toBeGreaterThan(0);

      const testState = JSON.parse(JSON.stringify(oldState));
      applyDiff(testState, diff);

      expect(testState.sections[0].blocks[0].values.value).toBe('defined');
    });
  });
});
