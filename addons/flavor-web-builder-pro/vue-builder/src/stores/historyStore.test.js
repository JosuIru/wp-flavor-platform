/**
 * Tests para historyStore
 * @vitest-environment node
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

// Mock de historyDiff
vi.mock('../utils/historyDiff', () => ({
  createDiff: vi.fn((oldState, newState) => ({
    sections: { old: oldState.sections, new: newState.sections },
  })),
  applyDiff: vi.fn((state, diff) => {
    state.sections = diff.sections.new;
  }),
  revertDiff: vi.fn((state, diff) => {
    state.sections = diff.sections.old;
  }),
  createSnapshot: vi.fn((state) => JSON.parse(JSON.stringify(state))),
  isDiffEmpty: vi.fn((diff) => JSON.stringify(diff.sections.old) === JSON.stringify(diff.sections.new)),
}));

// Mock de historyStorage
vi.mock('../utils/historyStorage', () => ({
  saveHistoryDebounced: vi.fn(),
  loadHistory: vi.fn().mockResolvedValue(null),
  flushPendingSave: vi.fn(),
  cleanupOldHistory: vi.fn().mockResolvedValue(undefined),
  isIndexedDBAvailable: vi.fn().mockReturnValue(true),
}));

// Mock de lzCompress
vi.mock('../utils/lzCompress', () => ({
  compressObject: vi.fn((obj) => JSON.stringify(obj)),
  decompressObject: vi.fn((str) => JSON.parse(str)),
}));

import { useHistoryStore } from './historyStore';

describe('historyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  describe('initialize', () => {
    it('debería inicializar con configuración', () => {
      const store = useHistoryStore();
      store.initialize({
        postId: 123,
        useCompression: true,
        persistHistory: true,
      });

      expect(store.postId).toBe(123);
      expect(store.useCompression).toBe(true);
      expect(store.persistToIndexedDB).toBe(true);
    });
  });

  describe('push', () => {
    it('debería crear checkpoint inicial', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      store.push({
        sections: [{ id: '1', blocks: [] }],
        selectedBlockId: null,
        selectedSectionId: null,
      });

      expect(store.checkpoints.length).toBe(1);
      expect(store.currentIndex).toBe(0);
    });

    it('debería añadir diffs sucesivos', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      // Estado inicial
      store.push({
        sections: [{ id: '1', blocks: [] }],
        selectedBlockId: null,
        selectedSectionId: null,
      });

      // Cambio
      store.push({
        sections: [{ id: '1', blocks: [{ id: 'b1' }] }],
        selectedBlockId: 'b1',
        selectedSectionId: '1',
      });

      expect(store.diffs.length).toBe(1);
      expect(store.currentIndex).toBe(1);
    });

    it('debería eliminar estados futuros al hacer push desde medio del historial', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      // 3 estados
      store.push({ sections: [], selectedBlockId: null, selectedSectionId: null });
      store.push({ sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null });
      store.push({ sections: [{ id: '1' }, { id: '2' }], selectedBlockId: null, selectedSectionId: null });

      // Volver atrás
      store.currentIndex = 1;

      // Nuevo estado desde el medio
      store.push({ sections: [{ id: 'new' }], selectedBlockId: null, selectedSectionId: null });

      // Debería haber eliminado el diff del índice 2
      expect(store.currentIndex).toBe(2);
    });
  });

  describe('undo/redo', () => {
    it('canUndo debería ser false inicialmente', () => {
      const store = useHistoryStore();
      expect(store.canUndo).toBe(false);
    });

    it('canRedo debería ser false inicialmente', () => {
      const store = useHistoryStore();
      expect(store.canRedo).toBe(false);
    });

    it('undo debería retornar estado anterior', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      const initialState = { sections: [], selectedBlockId: null, selectedSectionId: null };
      const newState = { sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null };

      store.push(initialState);
      store.push(newState);

      expect(store.canUndo).toBe(true);

      const undoneState = store.undo(newState);
      expect(undoneState).toBeTruthy();
      expect(store.currentIndex).toBe(0);
    });

    it('redo debería retornar estado siguiente', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      const initialState = { sections: [], selectedBlockId: null, selectedSectionId: null };
      const newState = { sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null };

      store.push(initialState);
      store.push(newState);
      store.undo(newState);

      expect(store.canRedo).toBe(true);

      const redoneState = store.redo(initialState);
      expect(redoneState).toBeTruthy();
      expect(store.currentIndex).toBe(1);
    });
  });

  describe('prune', () => {
    it('debería limitar el tamaño del historial', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });
      store.maxSize = 3;

      // El primer push crea el checkpoint inicial (sin diff)
      store.push({
        sections: [],
        selectedBlockId: null,
        selectedSectionId: null,
      });

      // Los siguientes crean diffs
      for (let i = 0; i < 5; i++) {
        store.push({
          sections: [{ id: `s${i}` }],
          selectedBlockId: null,
          selectedSectionId: null,
        });
      }

      // Debería haber prunado para mantener el máximo
      expect(store.diffs.length).toBeLessThanOrEqual(store.maxSize);
    });
  });

  describe('stats', () => {
    it('debería retornar estadísticas correctas', () => {
      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      store.push({ sections: [], selectedBlockId: null, selectedSectionId: null });
      store.push({ sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null });

      const stats = store.stats;
      expect(stats.totalDiffs).toBe(1);
      expect(stats.totalCheckpoints).toBeGreaterThanOrEqual(1);
      expect(stats.canUndo).toBe(true);
      expect(stats.canRedo).toBe(false);
    });
  });

  describe('debounce', () => {
    it('pushDebounced debería agrupar llamadas', async () => {
      vi.useFakeTimers();

      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      // Estado inicial
      store.push({ sections: [], selectedBlockId: null, selectedSectionId: null });

      // Múltiples pushDebounced
      store.pushDebounced({ sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null }, 100);
      store.pushDebounced({ sections: [{ id: '2' }], selectedBlockId: null, selectedSectionId: null }, 100);
      store.pushDebounced({ sections: [{ id: '3' }], selectedBlockId: null, selectedSectionId: null }, 100);

      // No debería haber añadido diffs todavía
      expect(store.diffs.length).toBe(0);

      // Avanzar tiempo
      vi.advanceTimersByTime(100);

      // Debería haber añadido solo un diff (el último)
      expect(store.diffs.length).toBe(1);

      vi.useRealTimers();
    });

    it('flushPending debería forzar el push', () => {
      vi.useFakeTimers();

      const store = useHistoryStore();
      store.initialize({ postId: 1 });

      store.push({ sections: [], selectedBlockId: null, selectedSectionId: null });
      store.pushDebounced({ sections: [{ id: '1' }], selectedBlockId: null, selectedSectionId: null }, 100);

      // Flush antes del timeout
      store.flushPending();

      expect(store.diffs.length).toBe(1);

      vi.useRealTimers();
    });
  });
});
