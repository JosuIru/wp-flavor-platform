import { defineStore } from 'pinia';
import {
  createDiff,
  applyDiff,
  revertDiff,
  createSnapshot,
  restoreSnapshot,
  isDiffEmpty,
} from '../utils/historyDiff';
import {
  saveHistoryDebounced,
  loadHistory,
  flushPendingSave,
  cleanupOldHistory,
  isIndexedDBAvailable,
} from '../utils/historyStorage';
import { compress, decompress, compressObject, decompressObject } from '../utils/lzCompress';

/**
 * Store principal del Page Builder
 * Gestiona layout, historial y estado de drag & drop
 */
export const useBuilderStore = defineStore('builder', {
  state: () => ({
    // Estructura del layout: array de secciones con bloques
    sections: [],

    // Índices para acceso O(1) - se reconstruyen automáticamente
    // Map<blockId, block> - acceso directo a bloques
    blockIndex: new Map(),
    // Map<blockId, sectionId> - saber en qué sección está cada bloque
    blockToSectionIndex: new Map(),
    // Map<sectionId, section> - acceso directo a secciones
    sectionIndex: new Map(),

    // Bloque seleccionado actualmente
    selectedBlockId: null,

    // Sección seleccionada actualmente
    selectedSectionId: null,

    // Definiciones de componentes desde WordPress
    componentDefs: {},

    // Categorías de componentes
    categories: [],

    // Historial con diffs para undo/redo (optimizado en memoria)
    // Guarda checkpoints completos cada N cambios + diffs incrementales
    historyCheckpoints: [],      // Snapshots completos periódicos
    historyDiffs: [],            // Diffs entre estados
    historyIndex: -1,            // Índice actual en el historial
    lastSnapshotState: null,     // Último estado del que se calculó diff
    maxHistorySize: 50,          // Máximo de entradas en historial
    checkpointInterval: 10,      // Crear checkpoint cada N cambios

    // Cache de HTML preview por bloque
    previewHtmlCache: {},

    // AbortControllers para cancelar peticiones de preview obsoletas
    previewAbortControllers: {},

    // Estado de drag & drop
    isDragging: false,
    dragSource: null, // { type: 'sidebar'|'canvas', componentId?, blockId?, sectionId? }
    dropTarget: null, // { sectionId, position }

    // Estado de guardado
    isDirty: false,
    isSaving: false,
    lastSavedAt: null,

    // Timers y cache para optimizaciones
    _historyDebounceTimer: null,
    _historyPendingState: null,
    _componentsByCategoryCache: null,
    _componentsByCategoryCacheKey: null,

    // Batch mode para agrupar operaciones
    _batchMode: false,
    _batchOperations: 0,

    // Throttle para drag & drop
    _lastDragUpdate: 0,

    // Compresión de historial
    _useCompression: true,
    _compressedDiffs: [], // Diffs comprimidos para ahorro de memoria

    // Persistencia en IndexedDB
    _persistHistory: true,
    _historyLoaded: false,

    // Configuración global
    postId: 0,
    ajaxUrl: '',
    nonce: '',
    previewUrl: '',
  }),

  getters: {
    /**
     * Obtener bloque por ID - O(1) usando índice Map
     */
    getBlockById: (state) => (blockId) => {
      return state.blockIndex.get(blockId) || null;
    },

    /**
     * Obtener sección por ID - O(1) usando índice Map
     */
    getSectionById: (state) => (sectionId) => {
      return state.sectionIndex.get(sectionId) || null;
    },

    /**
     * Obtener la sección que contiene un bloque - O(1) usando índice Map
     */
    getSectionByBlockId: (state) => (blockId) => {
      const sectionId = state.blockToSectionIndex.get(blockId);
      if (!sectionId) return null;
      return state.sectionIndex.get(sectionId) || null;
    },

    /**
     * Bloque seleccionado actual - O(1) usando índice Map
     */
    selectedBlock: (state) => {
      if (!state.selectedBlockId) return null;
      return state.blockIndex.get(state.selectedBlockId) || null;
    },

    /**
     * Definición del componente del bloque seleccionado - O(1)
     */
    selectedComponentDef: (state) => {
      if (!state.selectedBlockId) return null;
      const block = state.blockIndex.get(state.selectedBlockId);
      if (!block) return null;
      return state.componentDefs[block.componentId] || null;
    },

    /**
     * Verificar si se puede hacer undo
     */
    canUndo: (state) => state.historyIndex > 0,

    /**
     * Verificar si se puede hacer redo
     */
    canRedo: (state) => state.historyIndex < state.historyDiffs.length,

    /**
     * Obtener estadísticas del historial (útil para debugging)
     */
    historyStats: (state) => ({
      totalDiffs: state.historyDiffs.length,
      totalCheckpoints: state.historyCheckpoints.length,
      currentIndex: state.historyIndex,
      canUndo: state.historyIndex > 0,
      canRedo: state.historyIndex < state.historyDiffs.length,
    }),

    /**
     * Componentes agrupados por categoría (memoizado)
     * Se recalcula solo cuando cambia componentDefs
     */
    componentsByCategory: (state) => {
      // Crear key de cache basada en los IDs de componentes
      const cacheKey = Object.keys(state.componentDefs).sort().join(',');

      // Retornar cache si está válida
      if (state._componentsByCategoryCacheKey === cacheKey && state._componentsByCategoryCache) {
        return state._componentsByCategoryCache;
      }

      // Recalcular
      const grouped = {};
      for (const [componentId, componentDef] of Object.entries(state.componentDefs)) {
        // Saltar componentes deprecados
        if (componentDef.deprecated) continue;

        const category = componentDef.category || 'general';
        if (!grouped[category]) {
          grouped[category] = [];
        }
        grouped[category].push({
          id: componentId,
          ...componentDef,
        });
      }

      // Guardar en cache (mutación directa del state para cache interno)
      state._componentsByCategoryCacheKey = cacheKey;
      state._componentsByCategoryCache = grouped;

      return grouped;
    },

    /**
     * Layout serializado para guardar
     */
    layoutJson: (state) => {
      return state.sections.map(section => ({
        id: section.id,
        settings: section.settings || {},
        blocks: section.blocks.map(block => ({
          id: block.id,
          componentId: block.componentId,
          values: block.values,
          variant: block.variant,
        })),
      }));
    },
  },

  actions: {
    /**
     * Iniciar modo batch para agrupar múltiples operaciones
     * Solo guarda una entrada de historial al finalizar
     */
    startBatch() {
      this.flushPendingHistory();
      this._batchMode = true;
      this._batchOperations = 0;
    },

    /**
     * Finalizar modo batch y guardar historial si hubo cambios
     */
    endBatch() {
      if (this._batchMode && this._batchOperations > 0) {
        this.pushHistory();
      }
      this._batchMode = false;
      this._batchOperations = 0;
    },

    /**
     * Actualizar drop target con throttle (limita actualizaciones durante drag)
     */
    updateDropTarget(target) {
      const now = Date.now();
      const throttleMs = 50; // Máximo 20 updates por segundo

      if (now - this._lastDragUpdate >= throttleMs) {
        this.dropTarget = target;
        this._lastDragUpdate = now;
      }
    },

    /**
     * Reconstruir índices Map para acceso O(1)
     * Llamar después de cualquier operación que modifique la estructura
     */
    rebuildIndex() {
      // Limpiar índices
      this.blockIndex.clear();
      this.blockToSectionIndex.clear();
      this.sectionIndex.clear();

      // Reconstruir desde sections
      for (const section of this.sections) {
        this.sectionIndex.set(section.id, section);
        for (const block of section.blocks) {
          this.blockIndex.set(block.id, block);
          this.blockToSectionIndex.set(block.id, section.id);
        }
      }
    },

    /**
     * Inicializar store con datos de WordPress
     */
    async initialize(config) {
      this.postId = config.postId || 0;
      this.ajaxUrl = config.ajaxUrl || '';
      this.nonce = config.nonce || '';
      this.previewUrl = config.previewUrl || '';
      this.componentDefs = config.components || {};
      this.categories = config.categories || [];

      // Configuración de optimizaciones
      this._useCompression = config.useCompression !== false;
      this._persistHistory = config.persistHistory !== false && isIndexedDBAvailable();

      // Cargar layout existente
      if (config.layout && Array.isArray(config.layout)) {
        this.loadFromLegacyLayout(config.layout);
      }

      // Intentar restaurar historial de IndexedDB
      if (this._persistHistory && this.postId) {
        await this.loadPersistedHistory();
      }

      // Si no se cargó historial, crear estado inicial
      if (!this._historyLoaded) {
        this.pushHistory();
      }

      // Limpiar historiales antiguos en background
      if (this._persistHistory) {
        cleanupOldHistory().catch(() => {});
      }

      // Guardar historial al cerrar página
      if (typeof window !== 'undefined') {
        window.addEventListener('beforeunload', () => {
          this.persistHistorySync();
        });
      }
    },

    /**
     * Cargar historial persistido de IndexedDB
     */
    async loadPersistedHistory() {
      if (!this._persistHistory || !this.postId) return false;

      try {
        const savedHistory = await loadHistory(this.postId);
        if (savedHistory && savedHistory.checkpoints?.length > 0) {
          // Descomprimir diffs si están comprimidos
          this.historyCheckpoints = savedHistory.checkpoints;
          this.historyDiffs = this._useCompression
            ? savedHistory.diffs.map(d => typeof d === 'string' ? decompressObject(d) : d)
            : savedHistory.diffs;
          this.historyIndex = savedHistory.currentIndex || 0;

          // Reconstruir estado actual desde historial
          const currentState = this.reconstructStateAtIndex(this.historyIndex);
          this.sections = currentState.sections;
          this.selectedBlockId = currentState.selectedBlockId;
          this.selectedSectionId = currentState.selectedSectionId;

          this.lastSnapshotState = createSnapshot({
            sections: this.sections,
            selectedBlockId: this.selectedBlockId,
            selectedSectionId: this.selectedSectionId,
          });

          this.rebuildIndex();
          this._historyLoaded = true;
          return true;
        }
      } catch (error) {
        console.warn('Failed to load persisted history:', error);
      }

      return false;
    },

    /**
     * Persistir historial actual en IndexedDB (async)
     */
    persistHistory() {
      if (!this._persistHistory || !this.postId) return;

      const historyData = {
        checkpoints: this.historyCheckpoints,
        diffs: this._useCompression
          ? this.historyDiffs.map(d => compressObject(d))
          : this.historyDiffs,
        currentIndex: this.historyIndex,
      };

      saveHistoryDebounced(this.postId, historyData);
    },

    /**
     * Persistir historial de forma síncrona (para beforeunload)
     */
    persistHistorySync() {
      if (!this._persistHistory || !this.postId) return;

      const historyData = {
        checkpoints: this.historyCheckpoints,
        diffs: this._useCompression
          ? this.historyDiffs.map(d => compressObject(d))
          : this.historyDiffs,
        currentIndex: this.historyIndex,
      };

      flushPendingSave(this.postId, historyData);
    },

    /**
     * Cargar layout desde formato legacy (array plano de componentes)
     */
    loadFromLegacyLayout(legacyLayout) {
      // El formato legacy es un array plano de componentes
      // Lo convertimos a secciones con bloques
      if (!Array.isArray(legacyLayout)) {
        this.sections = [this.createSection()];
        this.rebuildIndex();
        return;
      }

      // Si ya está en formato nuevo (con secciones)
      if (legacyLayout.length > 0 && legacyLayout[0].blocks) {
        this.sections = legacyLayout.map(section => ({
          id: section.id || this.generateId(),
          settings: section.settings || {},
          blocks: section.blocks.map(block => ({
            id: block.id || this.generateId(),
            componentId: block.componentId || block.component_id,
            values: block.values || {},
            variant: block.variant || null,
          })),
        }));
        this.rebuildIndex();
        return;
      }

      // Formato legacy: array plano de componentes
      const defaultSection = this.createSection();
      defaultSection.blocks = legacyLayout.map((item, index) => ({
        id: item.id || `block-${index}-${Date.now()}`,
        componentId: item.component_id || item.componentId,
        values: item.values || {},
        variant: item.variant || null,
      }));

      this.sections = [defaultSection];
      this.rebuildIndex();
    },

    /**
     * Crear nueva sección vacía
     */
    createSection() {
      return {
        id: this.generateId(),
        settings: {
          fullWidth: false,
          backgroundColor: '',
          paddingTop: '',
          paddingBottom: '',
        },
        blocks: [],
      };
    },

    /**
     * Generar ID único
     */
    generateId() {
      return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    },

    /**
     * Añadir nueva sección
     */
    addSection(position = -1) {
      const newSection = this.createSection();

      if (position === -1 || position >= this.sections.length) {
        this.sections.push(newSection);
      } else {
        this.sections.splice(position, 0, newSection);
      }

      // Actualizar índice de sección
      this.sectionIndex.set(newSection.id, newSection);

      this.isDirty = true;
      this.pushHistory();
      return newSection.id;
    },

    /**
     * Añadir bloque a una sección - O(1) lookup usando índice
     */
    addBlock(sectionId, componentId, position = -1, values = {}, variant = null) {
      const section = this.sectionIndex.get(sectionId);
      if (!section) {
        // Si no hay sección, crear una
        const newSectionId = this.addSection();
        return this.addBlock(newSectionId, componentId, position, values, variant);
      }

      const componentDef = this.componentDefs[componentId];
      if (!componentDef) {
        console.error(`Component ${componentId} not found`);
        return null;
      }

      // Obtener valores por defecto de los campos
      const defaultValues = this.getDefaultValues(componentDef.fields);

      // Si hay variant, obtener sus valores predefinidos
      let variantValues = {};
      if (variant && componentDef.variants?.[variant]) {
        variantValues = componentDef.variants[variant].values || {};
      }

      const newBlock = {
        id: this.generateId(),
        componentId,
        values: { ...defaultValues, ...variantValues, ...values },
        variant,
      };

      if (position === -1 || position >= section.blocks.length) {
        section.blocks.push(newBlock);
      } else {
        section.blocks.splice(position, 0, newBlock);
      }

      // Actualizar índices
      this.blockIndex.set(newBlock.id, newBlock);
      this.blockToSectionIndex.set(newBlock.id, sectionId);

      this.selectedBlockId = newBlock.id;
      this.selectedSectionId = sectionId;
      this.isDirty = true;
      this.pushHistory();

      // Refrescar preview del nuevo bloque
      this.refreshPreview(newBlock.id);

      return newBlock.id;
    },

    /**
     * Obtener valores por defecto de campos
     */
    getDefaultValues(fields) {
      const values = {};
      if (!fields) return values;

      for (const [fieldKey, fieldDef] of Object.entries(fields)) {
        if (fieldDef.default !== undefined) {
          values[fieldKey] = fieldDef.default;
        } else {
          // Valores por defecto según tipo
          switch (fieldDef.type) {
            case 'text':
            case 'textarea':
            case 'color':
            case 'image':
            case 'select':
            case 'icon':
              values[fieldKey] = '';
              break;
            case 'number':
              values[fieldKey] = 0;
              break;
            case 'toggle':
              values[fieldKey] = false;
              break;
            case 'repeater':
              values[fieldKey] = [];
              break;
            default:
              values[fieldKey] = '';
          }
        }
      }
      return values;
    },

    /**
     * Actualizar valor de campo en bloque - O(1) usando índice
     * Usa historial debounced para agrupar cambios rápidos (ej: typing)
     */
    updateBlock(blockId, field, value) {
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      block.values[field] = value;
      this.isDirty = true;

      // Historial debounced para cambios frecuentes
      this.pushHistoryDebounced(500);

      // Refrescar preview con debounce
      this.refreshPreviewDebounced(blockId);
      return true;
    },

    /**
     * Actualizar múltiples valores de bloque - O(1) usando índice
     * Usa historial debounced para agrupar cambios rápidos
     */
    updateBlockValues(blockId, newValues) {
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      block.values = { ...block.values, ...newValues };
      this.isDirty = true;

      // Historial debounced para cambios frecuentes
      this.pushHistoryDebounced(500);

      this.refreshPreviewDebounced(blockId);
      return true;
    },

    /**
     * Eliminar bloque
     */
    removeBlock(blockId) {
      // Usar índice para encontrar la sección - O(1)
      const sectionId = this.blockToSectionIndex.get(blockId);
      if (!sectionId) return false;

      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      const blockIdx = section.blocks.findIndex(b => b.id === blockId);
      if (blockIdx === -1) return false;

      section.blocks.splice(blockIdx, 1);

      // Limpiar índices
      this.blockIndex.delete(blockId);
      this.blockToSectionIndex.delete(blockId);

      // Cancelar petición de preview pendiente si existe
      if (this.previewAbortControllers[blockId]) {
        this.previewAbortControllers[blockId].abort();
        delete this.previewAbortControllers[blockId];
      }

      // Limpiar cache de preview
      delete this.previewHtmlCache[blockId];

      // Deseleccionar si era el seleccionado
      if (this.selectedBlockId === blockId) {
        this.selectedBlockId = null;
      }

      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    /**
     * Eliminar sección
     */
    removeSection(sectionId) {
      // Usar índice para encontrar la sección - O(1)
      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      const sectionIdx = this.sections.findIndex(s => s.id === sectionId);
      if (sectionIdx === -1) return false;

      // Limpiar índices y cache de todos los bloques
      section.blocks.forEach(block => {
        this.blockIndex.delete(block.id);
        this.blockToSectionIndex.delete(block.id);

        // Cancelar petición pendiente
        if (this.previewAbortControllers[block.id]) {
          this.previewAbortControllers[block.id].abort();
          delete this.previewAbortControllers[block.id];
        }
        delete this.previewHtmlCache[block.id];
      });

      // Limpiar índice de sección
      this.sectionIndex.delete(sectionId);

      this.sections.splice(sectionIdx, 1);

      // Deseleccionar si era la sección seleccionada
      if (this.selectedSectionId === sectionId) {
        this.selectedSectionId = null;
        this.selectedBlockId = null;
      }

      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    /**
     * Mover bloque a nueva posición - O(1) lookup usando índices
     */
    moveBlock(blockId, targetSectionId, targetPosition) {
      // Usar índices para encontrar bloque y secciones - O(1)
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      const sourceSectionId = this.blockToSectionIndex.get(blockId);
      if (!sourceSectionId) return false;

      const sourceSection = this.sectionIndex.get(sourceSectionId);
      const targetSection = this.sectionIndex.get(targetSectionId);
      if (!sourceSection || !targetSection) return false;

      // Encontrar índice del bloque en la sección origen
      const blockIdx = sourceSection.blocks.findIndex(b => b.id === blockId);
      if (blockIdx === -1) return false;

      // Remover de posición actual
      sourceSection.blocks.splice(blockIdx, 1);

      // Insertar en nueva posición
      if (targetPosition === -1 || targetPosition >= targetSection.blocks.length) {
        targetSection.blocks.push(block);
      } else {
        targetSection.blocks.splice(targetPosition, 0, block);
      }

      // Actualizar índice de sección del bloque
      this.blockToSectionIndex.set(blockId, targetSectionId);

      this.isDirty = true;

      // En batch mode, solo contar la operación
      if (this._batchMode) {
        this._batchOperations++;
      } else {
        this.pushHistory();
      }
      return true;
    },

    /**
     * Mover sección a nueva posición
     */
    moveSection(sectionId, newPosition) {
      const currentIndex = this.sections.findIndex(s => s.id === sectionId);
      if (currentIndex === -1) return false;

      const [section] = this.sections.splice(currentIndex, 1);
      this.sections.splice(newPosition, 0, section);

      this.isDirty = true;

      // En batch mode, solo contar la operación
      if (this._batchMode) {
        this._batchOperations++;
      } else {
        this.pushHistory();
      }
      return true;
    },

    /**
     * Duplicar bloque - O(1) lookup usando índices
     */
    duplicateBlock(blockId) {
      // Usar índices para encontrar bloque y sección - O(1)
      const originalBlock = this.blockIndex.get(blockId);
      if (!originalBlock) return null;

      const sectionId = this.blockToSectionIndex.get(blockId);
      if (!sectionId) return null;

      const section = this.sectionIndex.get(sectionId);
      if (!section) return null;

      const blockIdx = section.blocks.findIndex(b => b.id === blockId);
      if (blockIdx === -1) return null;

      const newBlock = {
        id: this.generateId(),
        componentId: originalBlock.componentId,
        values: JSON.parse(JSON.stringify(originalBlock.values)),
        variant: originalBlock.variant,
      };

      // Insertar después del original
      section.blocks.splice(blockIdx + 1, 0, newBlock);

      // Actualizar índices
      this.blockIndex.set(newBlock.id, newBlock);
      this.blockToSectionIndex.set(newBlock.id, sectionId);

      this.selectedBlockId = newBlock.id;
      this.isDirty = true;
      this.pushHistory();

      // Refrescar preview del nuevo bloque
      this.refreshPreview(newBlock.id);

      return newBlock.id;
    },

    /**
     * Actualizar settings de sección - O(1) usando índice
     */
    updateSectionSettings(sectionId, newSettings) {
      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      section.settings = { ...section.settings, ...newSettings };
      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    /**
     * Duplicar sección - O(1) lookup usando índices
     */
    duplicateSection(sectionId) {
      // Usar índice para encontrar sección - O(1)
      const originalSection = this.sectionIndex.get(sectionId);
      if (!originalSection) return null;

      const sectionIdx = this.sections.findIndex(s => s.id === sectionId);
      if (sectionIdx === -1) return null;

      const newSection = {
        id: this.generateId(),
        settings: JSON.parse(JSON.stringify(originalSection.settings)),
        blocks: originalSection.blocks.map(block => ({
          id: this.generateId(),
          componentId: block.componentId,
          values: JSON.parse(JSON.stringify(block.values)),
          variant: block.variant,
        })),
      };

      this.sections.splice(sectionIdx + 1, 0, newSection);

      // Actualizar índices
      this.sectionIndex.set(newSection.id, newSection);
      newSection.blocks.forEach(block => {
        this.blockIndex.set(block.id, block);
        this.blockToSectionIndex.set(block.id, newSection.id);
      });

      this.selectedSectionId = newSection.id;
      this.isDirty = true;
      this.pushHistory();

      // Refrescar preview de todos los bloques nuevos
      newSection.blocks.forEach(block => {
        this.refreshPreview(block.id);
      });

      return newSection.id;
    },

    /**
     * Seleccionar bloque
     */
    selectBlock(blockId) {
      this.selectedBlockId = blockId;

      // También seleccionar la sección que lo contiene
      if (blockId) {
        const section = this.getSectionByBlockId(blockId);
        if (section) {
          this.selectedSectionId = section.id;
        }
      }
    },

    /**
     * Seleccionar sección
     */
    selectSection(sectionId) {
      this.selectedSectionId = sectionId;
      this.selectedBlockId = null;
    },

    /**
     * Deseleccionar todo
     */
    clearSelection() {
      this.selectedBlockId = null;
      this.selectedSectionId = null;
    },

    /**
     * Guardar estado actual en historial usando diffs
     * Solo guarda las diferencias respecto al estado anterior
     */
    pushHistory() {
      const currentState = {
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      };

      // Si es el primer estado, crear checkpoint inicial
      if (this.lastSnapshotState === null) {
        this.lastSnapshotState = createSnapshot(currentState);
        this.historyCheckpoints.push({
          index: 0,
          snapshot: createSnapshot(currentState),
        });
        this.historyIndex = 0;
        return;
      }

      // Calcular diff respecto al estado anterior
      const diff = createDiff(this.lastSnapshotState, currentState);

      // Si no hay cambios, no añadir al historial
      if (isDiffEmpty(diff)) {
        return;
      }

      // Si estamos en medio del historial, eliminar estados futuros
      if (this.historyIndex < this.historyDiffs.length) {
        this.historyDiffs = this.historyDiffs.slice(0, this.historyIndex);
        // Eliminar checkpoints futuros
        this.historyCheckpoints = this.historyCheckpoints.filter(
          cp => cp.index <= this.historyIndex
        );
      }

      // Añadir diff
      this.historyDiffs.push(diff);
      this.historyIndex = this.historyDiffs.length;

      // Crear checkpoint periódico para optimizar reconstrucción
      if (this.historyIndex % this.checkpointInterval === 0) {
        this.historyCheckpoints.push({
          index: this.historyIndex,
          snapshot: createSnapshot(currentState),
        });
      }

      // Actualizar último estado conocido
      this.lastSnapshotState = createSnapshot(currentState);

      // Limitar tamaño del historial
      this.pruneHistory();

      // Persistir en IndexedDB (debounced)
      this.persistHistory();
    },

    /**
     * Versión debounced de pushHistory para cambios frecuentes (typing, dragging)
     * Agrupa cambios rápidos en una sola entrada de historial
     */
    pushHistoryDebounced(delay = 500) {
      // Cancelar timer anterior si existe
      if (this._historyDebounceTimer) {
        clearTimeout(this._historyDebounceTimer);
      }

      // Guardar estado actual como pendiente
      this._historyPendingState = {
        sections: JSON.parse(JSON.stringify(this.sections)),
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      };

      // Crear nuevo timer
      this._historyDebounceTimer = setTimeout(() => {
        this._historyDebounceTimer = null;
        // Forzar push del estado pendiente
        if (this._historyPendingState) {
          this.pushHistory();
          this._historyPendingState = null;
        }
      }, delay);
    },

    /**
     * Forzar flush del historial pendiente (útil antes de operaciones importantes)
     */
    flushPendingHistory() {
      if (this._historyDebounceTimer) {
        clearTimeout(this._historyDebounceTimer);
        this._historyDebounceTimer = null;
      }
      if (this._historyPendingState) {
        this.pushHistory();
        this._historyPendingState = null;
      }
    },

    /**
     * Limitar tamaño del historial eliminando entradas antiguas
     */
    pruneHistory() {
      while (this.historyDiffs.length > this.maxHistorySize) {
        this.historyDiffs.shift();
        this.historyIndex--;

        // Ajustar índices de checkpoints
        this.historyCheckpoints = this.historyCheckpoints
          .map(cp => ({ ...cp, index: cp.index - 1 }))
          .filter(cp => cp.index >= 0);

        // Asegurar que siempre hay un checkpoint en índice 0
        if (this.historyCheckpoints.length === 0 || this.historyCheckpoints[0].index > 0) {
          // Reconstruir estado en índice 0 y crear checkpoint
          const stateAtZero = this.reconstructStateAtIndex(0);
          this.historyCheckpoints.unshift({
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
      // Encontrar el checkpoint más cercano anterior al índice
      let nearestCheckpoint = this.historyCheckpoints[0];
      for (const cp of this.historyCheckpoints) {
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
        if (this.historyDiffs[i]) {
          applyDiff(state, this.historyDiffs[i]);
        }
      }

      return state;
    },

    /**
     * Deshacer último cambio aplicando diff inverso
     */
    undo() {
      if (!this.canUndo) return false;

      // Obtener el diff actual y revertirlo
      const diffToRevert = this.historyDiffs[this.historyIndex - 1];
      if (diffToRevert) {
        const currentState = {
          sections: this.sections,
          selectedBlockId: this.selectedBlockId,
          selectedSectionId: this.selectedSectionId,
        };

        revertDiff(currentState, diffToRevert);

        this.sections = currentState.sections;
        this.selectedBlockId = currentState.selectedBlockId;
        this.selectedSectionId = currentState.selectedSectionId;
      }

      this.historyIndex--;
      this.lastSnapshotState = createSnapshot({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      this.isDirty = true;
      this.rebuildIndex();
      this.refreshAllPreviews();

      return true;
    },

    /**
     * Rehacer cambio deshecho aplicando diff
     */
    redo() {
      if (!this.canRedo) return false;

      // Obtener el diff a aplicar
      const diffToApply = this.historyDiffs[this.historyIndex];
      if (diffToApply) {
        const currentState = {
          sections: this.sections,
          selectedBlockId: this.selectedBlockId,
          selectedSectionId: this.selectedSectionId,
        };

        applyDiff(currentState, diffToApply);

        this.sections = currentState.sections;
        this.selectedBlockId = currentState.selectedBlockId;
        this.selectedSectionId = currentState.selectedSectionId;
      }

      this.historyIndex++;
      this.lastSnapshotState = createSnapshot({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      this.isDirty = true;
      this.rebuildIndex();
      this.refreshAllPreviews();

      return true;
    },

    /**
     * Saltar a un punto específico del historial
     */
    jumpToHistoryIndex(targetIndex) {
      if (targetIndex < 0 || targetIndex > this.historyDiffs.length) {
        return false;
      }

      // Reconstruir estado en el índice objetivo
      const state = this.reconstructStateAtIndex(targetIndex);

      this.sections = state.sections;
      this.selectedBlockId = state.selectedBlockId;
      this.selectedSectionId = state.selectedSectionId;
      this.historyIndex = targetIndex;

      this.lastSnapshotState = createSnapshot({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      this.isDirty = true;
      this.rebuildIndex();
      this.refreshAllPreviews();

      return true;
    },

    /**
     * Refrescar preview de un bloque via AJAX
     * Usa AbortController para cancelar peticiones obsoletas del mismo bloque
     */
    async refreshPreview(blockId) {
      const block = this.getBlockById(blockId);
      if (!block) return;

      // Cancelar petición anterior para este bloque si existe
      if (this.previewAbortControllers[blockId]) {
        this.previewAbortControllers[blockId].abort();
      }

      // Crear nuevo AbortController para esta petición
      const abortController = new AbortController();
      this.previewAbortControllers[blockId] = abortController;

      try {
        const formData = new FormData();
        formData.append('action', 'flavor_preview_component');
        formData.append('nonce', this.nonce);
        formData.append('component_id', block.componentId);
        formData.append('post_id', this.postId);
        // El PHP espera 'component_data' con los valores
        formData.append('component_data', JSON.stringify(block.values || {}));
        // Settings adicionales (variant, etc.)
        formData.append('component_settings', JSON.stringify({
          variant: block.variant || '',
        }));

        const response = await fetch(this.ajaxUrl, {
          method: 'POST',
          body: formData,
          signal: abortController.signal,
        });

        const data = await response.json();
        if (data.success && data.data?.html) {
          this.previewHtmlCache[blockId] = data.data.html;
        }
      } catch (error) {
        // Ignorar errores de abort (son intencionales)
        if (error.name === 'AbortError') {
          return;
        }
        console.error('Error refreshing preview:', error);
      } finally {
        // Limpiar el controller si es el actual
        if (this.previewAbortControllers[blockId] === abortController) {
          delete this.previewAbortControllers[blockId];
        }
      }
    },

    /**
     * Refrescar preview con debounce
     */
    refreshPreviewDebounced: (() => {
      const timeouts = {};
      return function(blockId) {
        if (timeouts[blockId]) {
          clearTimeout(timeouts[blockId]);
        }
        timeouts[blockId] = setTimeout(() => {
          this.refreshPreview(blockId);
          delete timeouts[blockId];
        }, 300);
      };
    })(),

    /**
     * Refrescar todos los previews (paralelizado con límite de concurrencia)
     */
    async refreshAllPreviews() {
      const blocks = this.sections.flatMap(s => s.blocks);
      const concurrencyLimit = 3; // Máximo 3 peticiones simultáneas

      for (let i = 0; i < blocks.length; i += concurrencyLimit) {
        const batch = blocks.slice(i, i + concurrencyLimit);
        await Promise.all(batch.map(block => this.refreshPreview(block.id)));
      }
    },

    /**
     * Guardar layout via AJAX
     */
    async save() {
      if (this.isSaving) return false;

      this.isSaving = true;

      try {
        const formData = new FormData();
        formData.append('action', 'flavor_save_preview');
        formData.append('nonce', this.nonce);
        formData.append('post_id', this.postId);
        formData.append('layout', JSON.stringify(this.layoutJson));

        const response = await fetch(this.ajaxUrl, {
          method: 'POST',
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          this.isDirty = false;
          this.lastSavedAt = new Date();
          return true;
        } else {
          throw new Error(data.data?.message || 'Error al guardar');
        }
      } catch (error) {
        console.error('Error saving layout:', error);
        return false;
      } finally {
        this.isSaving = false;
      }
    },

    /**
     * Limpiar cache de previews de bloques que ya no existen
     * Llamar periódicamente para liberar memoria
     */
    cleanupPreviewCache() {
      const validBlockIds = new Set(this.blockIndex.keys());
      let cleaned = 0;

      // Limpiar cache de HTML
      for (const blockId of Object.keys(this.previewHtmlCache)) {
        if (!validBlockIds.has(blockId)) {
          delete this.previewHtmlCache[blockId];
          cleaned++;
        }
      }

      // Cancelar y limpiar abort controllers huérfanos
      for (const blockId of Object.keys(this.previewAbortControllers)) {
        if (!validBlockIds.has(blockId)) {
          this.previewAbortControllers[blockId].abort();
          delete this.previewAbortControllers[blockId];
          cleaned++;
        }
      }

      return cleaned;
    },

    /**
     * Obtener estadísticas de memoria y rendimiento del store
     */
    getPerformanceStats() {
      const historyMemory = JSON.stringify(this.historyDiffs).length +
                           JSON.stringify(this.historyCheckpoints).length;
      const previewCacheMemory = JSON.stringify(this.previewHtmlCache).length;
      const layoutMemory = JSON.stringify(this.sections).length;

      // Calcular ahorro de compresión (si está activa)
      let compressionSavings = null;
      if (this._useCompression && this.historyDiffs.length > 0) {
        const uncompressedSize = JSON.stringify(this.historyDiffs).length;
        const compressedSize = this.historyDiffs.map(d => compressObject(d)).join('').length;
        compressionSavings = {
          uncompressed: `${(uncompressedSize / 1024).toFixed(1)} KB`,
          compressed: `${(compressedSize / 1024).toFixed(1)} KB`,
          ratio: `${((1 - compressedSize / uncompressedSize) * 100).toFixed(0)}%`,
        };
      }

      return {
        blocks: this.blockIndex.size,
        sections: this.sectionIndex.size,
        historyEntries: this.historyDiffs.length,
        historyCheckpoints: this.historyCheckpoints.length,
        previewsCached: Object.keys(this.previewHtmlCache).length,
        pendingRequests: Object.keys(this.previewAbortControllers).length,
        features: {
          compression: this._useCompression,
          persistence: this._persistHistory,
          indexedDB: isIndexedDBAvailable(),
        },
        compressionSavings,
        memoryEstimate: {
          history: `${(historyMemory / 1024).toFixed(1)} KB`,
          previews: `${(previewCacheMemory / 1024).toFixed(1)} KB`,
          layout: `${(layoutMemory / 1024).toFixed(1)} KB`,
          total: `${((historyMemory + previewCacheMemory + layoutMemory) / 1024).toFixed(1)} KB`,
        },
      };
    },

    /**
     * Exportar layout como JSON
     */
    exportLayout() {
      return JSON.stringify(this.layoutJson, null, 2);
    },

    /**
     * Importar layout desde JSON
     */
    importLayout(jsonString) {
      try {
        const layout = JSON.parse(jsonString);
        this.loadFromLegacyLayout(layout);
        // loadFromLegacyLayout ya llama a rebuildIndex()
        this.isDirty = true;
        this.pushHistory();
        this.refreshAllPreviews();
        return true;
      } catch (error) {
        console.error('Error importing layout:', error);
        return false;
      }
    },
  },
});
