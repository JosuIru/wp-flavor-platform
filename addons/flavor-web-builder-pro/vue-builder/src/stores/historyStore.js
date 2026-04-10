import { defineStore } from 'pinia';
import {
  createDiff,
  applyDiff,
  revertDiff,
  createSnapshot,
  isDiffEmpty,
} from '../utils/historyDiff';
import {
  saveHistoryDebounced,
  loadHistory,
  flushPendingSave,
  cleanupOldHistory,
  isIndexedDBAvailable,
} from '../utils/historyStorage';
import { compressObject, decompressObject } from '../utils/lzCompress';

/**
 * Store para gestión del historial (undo/redo)
 * Separado del builderStore para mejor organización y tree-shaking
 */
export const useHistoryStore = defineStore('history', {
  state: () => ({
    // Historial con diffs para undo/redo (optimizado en memoria)
    checkpoints: [],           // Snapshots completos periódicos
    diffs: [],                 // Diffs entre estados
    currentIndex: -1,          // Índice actual en el historial
    lastSnapshotState: null,   // Último estado del que se calculó diff

    // Configuración
    maxSize: 50,               // Máximo de entradas en historial
    checkpointInterval: 10,    // Crear checkpoint cada N cambios

    // Compresión y persistencia
    useCompression: true,
    persistToIndexedDB: true,
    isLoaded: false,

    // Timers y estado de debounce
    _debounceTimer: null,
    _pendingState: null,

    // Post ID para persistencia
    postId: 0,
  }),

  getters: {
    canUndo: (state) => state.currentIndex > 0,
    canRedo: (state) => state.diffs.length > 0 && state.currentIndex < state.diffs.length,

    stats: (state) => ({
      totalDiffs: state.diffs.length,
      totalCheckpoints: state.checkpoints.length,
      currentIndex: state.currentIndex,
      canUndo: state.currentIndex > 0,
      canRedo: state.currentIndex < state.diffs.length,
    }),
  },

  actions: {
    /**
     * Inicializar el store de historial
     */
    initialize(config) {
      this.postId = config.postId || 0;
      this.useCompression = config.useCompression !== false;
      this.persistToIndexedDB = config.persistHistory !== false && isIndexedDBAvailable();
    },

    /**
     * Cargar historial persistido de IndexedDB
     */
    async loadPersistedHistory() {
      if (!this.persistToIndexedDB || !this.postId) return null;

      try {
        const savedHistory = await loadHistory(this.postId);
        if (savedHistory && savedHistory.checkpoints?.length > 0) {
          this.checkpoints = savedHistory.checkpoints;
          this.diffs = this.useCompression
            ? savedHistory.diffs.map(d => typeof d === 'string' ? decompressObject(d) : d)
            : savedHistory.diffs;
          this.currentIndex = savedHistory.currentIndex || 0;
          this.isLoaded = true;

          // Retornar el estado actual reconstruido
          return this.reconstructStateAtIndex(this.currentIndex);
        }
      } catch (error) {
        console.warn('Failed to load persisted history:', error);
      }

      return null;
    },

    /**
     * Persistir historial actual en IndexedDB (async)
     */
    persistHistory() {
      if (!this.persistToIndexedDB || !this.postId) return;

      const historyData = {
        checkpoints: this.checkpoints,
        diffs: this.useCompression
          ? this.diffs.map(d => compressObject(d))
          : this.diffs,
        currentIndex: this.currentIndex,
      };

      saveHistoryDebounced(this.postId, historyData);
    },

    /**
     * Persistir historial de forma síncrona (para beforeunload)
     */
    persistHistorySync() {
      if (!this.persistToIndexedDB || !this.postId) return;

      const historyData = {
        checkpoints: this.checkpoints,
        diffs: this.useCompression
          ? this.diffs.map(d => compressObject(d))
          : this.diffs,
        currentIndex: this.currentIndex,
      };

      flushPendingSave(this.postId, historyData);
    },

    /**
     * Guardar estado actual en historial usando diffs
     */
    push(currentState) {
      // Si es el primer estado, crear checkpoint inicial
      if (this.lastSnapshotState === null) {
        this.lastSnapshotState = createSnapshot(currentState);
        this.checkpoints.push({
          index: 0,
          snapshot: createSnapshot(currentState),
        });
        this.currentIndex = 0;
        return;
      }

      // Calcular diff respecto al estado anterior
      const diff = createDiff(this.lastSnapshotState, currentState);

      // Si no hay cambios, no añadir al historial
      if (isDiffEmpty(diff)) {
        return;
      }

      // Si estamos en medio del historial, eliminar estados futuros
      if (this.currentIndex < this.diffs.length) {
        this.diffs = this.diffs.slice(0, this.currentIndex);
        this.checkpoints = this.checkpoints.filter(
          cp => cp.index <= this.currentIndex
        );
      }

      // Añadir diff
      this.diffs.push(diff);
      this.currentIndex = this.diffs.length;

      // Crear checkpoint periódico para optimizar reconstrucción
      if (this.currentIndex % this.checkpointInterval === 0) {
        this.checkpoints.push({
          index: this.currentIndex,
          snapshot: createSnapshot(currentState),
        });
      }

      // Actualizar último estado conocido
      this.lastSnapshotState = createSnapshot(currentState);

      // Limitar tamaño del historial
      this.prune();

      // Persistir en IndexedDB (debounced)
      this.persistHistory();
    },

    /**
     * Versión debounced de push para cambios frecuentes
     */
    pushDebounced(currentState, delay = 500) {
      if (this._debounceTimer) {
        clearTimeout(this._debounceTimer);
      }

      this._pendingState = JSON.parse(JSON.stringify(currentState));

      this._debounceTimer = setTimeout(() => {
        this._debounceTimer = null;
        if (this._pendingState) {
          this.push(this._pendingState);
          this._pendingState = null;
        }
      }, delay);
    },

    /**
     * Forzar flush del historial pendiente
     */
    flushPending() {
      if (this._debounceTimer) {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = null;
      }
      if (this._pendingState) {
        this.push(this._pendingState);
        this._pendingState = null;
      }
    },

    /**
     * Limitar tamaño del historial eliminando entradas antiguas
     */
    prune() {
      while (this.diffs.length > this.maxSize) {
        this.diffs.shift();
        this.currentIndex--;

        // Ajustar índices de checkpoints
        this.checkpoints = this.checkpoints
          .map(cp => ({ ...cp, index: cp.index - 1 }))
          .filter(cp => cp.index >= 0);

        // Asegurar que siempre hay un checkpoint en índice 0
        if (this.checkpoints.length === 0 || this.checkpoints[0].index > 0) {
          const stateAtZero = this.reconstructStateAtIndex(0);
          this.checkpoints.unshift({
            index: 0,
            snapshot: stateAtZero,
          });
        }
      }
    },

    /**
     * Reconstruir estado en un índice específico del historial
     */
    reconstructStateAtIndex(targetIndex) {
      // Si no hay checkpoints, retornar estado vacío
      if (this.checkpoints.length === 0) {
        return {
          sections: [],
          selectedBlockId: null,
          selectedSectionId: null,
        };
      }

      // Encontrar el checkpoint más cercano anterior al índice
      let nearestCheckpoint = this.checkpoints[0];
      for (const cp of this.checkpoints) {
        if (cp.index <= targetIndex && cp.index > nearestCheckpoint.index) {
          nearestCheckpoint = cp;
        }
      }

      // Clonar el estado del checkpoint
      const state = {
        sections: JSON.parse(JSON.stringify(nearestCheckpoint.snapshot.sections)),
        selectedBlockId: nearestCheckpoint.snapshot.selectedBlockId,
        selectedSectionId: nearestCheckpoint.snapshot.selectedSectionId,
      };

      // Aplicar diffs desde el checkpoint hasta el índice objetivo
      for (let i = nearestCheckpoint.index; i < targetIndex; i++) {
        if (this.diffs[i]) {
          applyDiff(state, this.diffs[i]);
        }
      }

      return state;
    },

    /**
     * Deshacer último cambio
     * Retorna el estado anterior o null si no se puede
     */
    undo(currentState) {
      if (!this.canUndo) return null;

      const diffToRevert = this.diffs[this.currentIndex - 1];
      if (diffToRevert) {
        const state = {
          sections: currentState.sections,
          selectedBlockId: currentState.selectedBlockId,
          selectedSectionId: currentState.selectedSectionId,
        };

        revertDiff(state, diffToRevert);
        this.currentIndex--;

        this.lastSnapshotState = createSnapshot(state);
        return state;
      }

      return null;
    },

    /**
     * Rehacer cambio deshecho
     * Retorna el nuevo estado o null si no se puede
     */
    redo(currentState) {
      if (!this.canRedo) return null;

      const diffToApply = this.diffs[this.currentIndex];
      if (diffToApply) {
        const state = {
          sections: currentState.sections,
          selectedBlockId: currentState.selectedBlockId,
          selectedSectionId: currentState.selectedSectionId,
        };

        applyDiff(state, diffToApply);
        this.currentIndex++;

        this.lastSnapshotState = createSnapshot(state);
        return state;
      }

      return null;
    },

    /**
     * Saltar a un punto específico del historial
     */
    jumpToIndex(targetIndex) {
      if (targetIndex < 0 || targetIndex > this.diffs.length) {
        return null;
      }

      const state = this.reconstructStateAtIndex(targetIndex);
      this.currentIndex = targetIndex;
      this.lastSnapshotState = createSnapshot(state);

      return state;
    },

    /**
     * Iniciar cleanup de historial antiguo en background
     */
    cleanupOldHistories() {
      if (this.persistToIndexedDB) {
        cleanupOldHistory().catch(() => {});
      }
    },

    /**
     * Limpiar recursos del store
     */
    cleanup() {
      if (this._debounceTimer) {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = null;
      }
      this.persistHistorySync();
    },
  },
});
