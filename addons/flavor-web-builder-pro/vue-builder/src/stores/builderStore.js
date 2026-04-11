import { defineStore } from 'pinia';
import { useHistoryStore } from './historyStore';
import { usePreviewStore } from './previewStore';
import { createSnapshot } from '../utils/historyDiff';

// Valores por defecto para cada tipo de campo (inmutable)
const FIELD_DEFAULT_VALUES = Object.freeze({
  text: '',
  textarea: '',
  color: '',
  image: '',
  select: '',
  icon: '',
  number: 0,
  toggle: false,
  repeater: [],
});

/**
 * Store principal del Page Builder
 * Gestiona layout, selección y operaciones de bloques
 * Delegando historial a historyStore y previews a previewStore
 */
export const useBuilderStore = defineStore('builder', {
  state: () => ({
    // Estructura del layout: array de secciones con bloques
    sections: [],

    // Índices para acceso O(1) - se reconstruyen automáticamente
    blockIndex: new Map(),
    blockToSectionIndex: new Map(),
    sectionIndex: new Map(),

    // Selección actual
    selectedBlockId: null,
    selectedSectionId: null,

    // Definiciones de componentes desde WordPress
    componentDefs: {},
    categories: [],

    // Estado de drag & drop
    isDragging: false,
    dragSource: null,
    dropTarget: null,

    // Estado de guardado
    isDirty: false,
    isSaving: false,
    lastSavedAt: null,

    // Cache de componentes por categoría
    _componentsByCategoryCache: null,
    _componentsByCategoryCacheKey: null,

    // Batch mode para agrupar operaciones
    _batchMode: false,
    _batchOperations: 0,

    // Throttle para drag & drop
    _lastDragUpdate: 0,

    // Configuración global
    postId: 0,
    ajaxUrl: '',
    nonce: '',
    previewUrl: '',
  }),

  getters: {
    getBlockById: (state) => (blockId) => state.blockIndex.get(blockId) || null,
    getSectionById: (state) => (sectionId) => state.sectionIndex.get(sectionId) || null,

    getSectionByBlockId: (state) => (blockId) => {
      const sectionId = state.blockToSectionIndex.get(blockId);
      if (!sectionId) return null;
      return state.sectionIndex.get(sectionId) || null;
    },

    selectedBlock: (state) => {
      if (!state.selectedBlockId) return null;
      return state.blockIndex.get(state.selectedBlockId) || null;
    },

    selectedComponentDef: (state) => {
      if (!state.selectedBlockId) return null;
      const block = state.blockIndex.get(state.selectedBlockId);
      if (!block) return null;
      return state.componentDefs[block.componentId] || null;
    },

    canUndo: () => {
      const historyStore = useHistoryStore();
      return historyStore.canUndo;
    },

    canRedo: () => {
      const historyStore = useHistoryStore();
      return historyStore.canRedo;
    },

    historyStats: () => {
      const historyStore = useHistoryStore();
      return historyStore.stats;
    },

    componentsByCategory: (state) => {
      const cacheKey = Object.keys(state.componentDefs).sort().join(',');

      if (state._componentsByCategoryCacheKey === cacheKey && state._componentsByCategoryCache) {
        return state._componentsByCategoryCache;
      }

      const grouped = {};
      for (const [componentId, componentDef] of Object.entries(state.componentDefs)) {
        if (componentDef.deprecated) continue;

        const category = componentDef.category || 'general';
        if (!grouped[category]) {
          grouped[category] = [];
        }
        grouped[category].push({ id: componentId, ...componentDef });
      }

      state._componentsByCategoryCacheKey = cacheKey;
      state._componentsByCategoryCache = grouped;

      return grouped;
    },

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

    // Acceso al cache de preview para compatibilidad
    previewHtmlCache: () => {
      const previewStore = usePreviewStore();
      return previewStore.htmlCache;
    },
  },

  actions: {
    // ==================== Batch Operations ====================

    startBatch() {
      const historyStore = useHistoryStore();
      historyStore.flushPending();
      this._batchMode = true;
      this._batchOperations = 0;
    },

    endBatch() {
      if (this._batchMode && this._batchOperations > 0) {
        this.pushHistory();
      }
      this._batchMode = false;
      this._batchOperations = 0;
    },

    // ==================== Drag & Drop ====================

    updateDropTarget(target) {
      const now = Date.now();
      const throttleMs = 50;

      if (now - this._lastDragUpdate >= throttleMs) {
        this.dropTarget = target;
        this._lastDragUpdate = now;
      }
    },

    // ==================== Index Management ====================

    rebuildIndex() {
      this.blockIndex.clear();
      this.blockToSectionIndex.clear();
      this.sectionIndex.clear();

      for (const section of this.sections) {
        this.sectionIndex.set(section.id, section);
        for (const block of section.blocks) {
          this.blockIndex.set(block.id, block);
          this.blockToSectionIndex.set(block.id, section.id);
        }
      }
    },

    // ==================== Initialization ====================

    async initialize(config) {
      this.postId = config.postId || 0;
      this.ajaxUrl = config.ajaxUrl || '';
      this.nonce = config.nonce || '';
      this.previewUrl = config.previewUrl || '';
      this.componentDefs = config.components || {};
      this.categories = config.categories || [];

      // Inicializar stores especializados
      const historyStore = useHistoryStore();
      const previewStore = usePreviewStore();

      historyStore.initialize({
        postId: this.postId,
        useCompression: config.useCompression !== false,
        persistHistory: config.persistHistory !== false,
      });

      previewStore.initialize({
        ajaxUrl: this.ajaxUrl,
        nonce: this.nonce,
        postId: this.postId,
      });

      // Cargar layout existente
      if (config.layout && Array.isArray(config.layout)) {
        this.loadFromLegacyLayout(config.layout);
      }

      // Intentar restaurar historial de IndexedDB
      const persistedState = await historyStore.loadPersistedHistory();
      if (persistedState) {
        this.sections = persistedState.sections;
        this.selectedBlockId = persistedState.selectedBlockId;
        this.selectedSectionId = persistedState.selectedSectionId;
        this.rebuildIndex();
      } else {
        this.pushHistory();
      }

      // Limpiar historiales antiguos en background
      historyStore.cleanupOldHistories();

      // Guardar historial al cerrar página
      if (typeof window !== 'undefined') {
        window.addEventListener('beforeunload', () => {
          historyStore.persistHistorySync();
        });
      }
    },

    cleanup() {
      const historyStore = useHistoryStore();
      const previewStore = usePreviewStore();

      historyStore.cleanup();
      previewStore.cleanup();
    },

    // ==================== Layout Loading ====================

    loadFromLegacyLayout(legacyLayout) {
      if (!Array.isArray(legacyLayout)) {
        this.sections = [this.createSection()];
        this.rebuildIndex();
        return;
      }

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

    // ==================== Section Operations ====================

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

    generateId() {
      return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    },

    addSection(position = -1) {
      const newSection = this.createSection();

      if (position === -1 || position >= this.sections.length) {
        this.sections.push(newSection);
      } else {
        this.sections.splice(position, 0, newSection);
      }

      this.sectionIndex.set(newSection.id, newSection);

      this.isDirty = true;
      this.pushHistory();
      return newSection.id;
    },

    removeSection(sectionId) {
      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      const sectionIdx = this.sections.findIndex(s => s.id === sectionId);
      if (sectionIdx === -1) return false;

      const previewStore = usePreviewStore();

      section.blocks.forEach(block => {
        this.blockIndex.delete(block.id);
        this.blockToSectionIndex.delete(block.id);
        previewStore.cancelRequest(block.id);
        previewStore.invalidate(block.id);
      });

      this.sectionIndex.delete(sectionId);
      this.sections.splice(sectionIdx, 1);

      if (this.selectedSectionId === sectionId) {
        this.selectedSectionId = null;
        this.selectedBlockId = null;
      }

      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    moveSection(sectionId, newPosition) {
      const currentIndex = this.sections.findIndex(s => s.id === sectionId);
      if (currentIndex === -1) return false;

      const [section] = this.sections.splice(currentIndex, 1);
      this.sections.splice(newPosition, 0, section);

      this.isDirty = true;

      if (this._batchMode) {
        this._batchOperations++;
      } else {
        this.pushHistory();
      }
      return true;
    },

    duplicateSection(sectionId) {
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

      this.sectionIndex.set(newSection.id, newSection);
      newSection.blocks.forEach(block => {
        this.blockIndex.set(block.id, block);
        this.blockToSectionIndex.set(block.id, newSection.id);
      });

      this.selectedSectionId = newSection.id;
      this.isDirty = true;
      this.pushHistory();

      this.refreshAllPreviews();

      return newSection.id;
    },

    updateSectionSettings(sectionId, newSettings) {
      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      section.settings = { ...section.settings, ...newSettings };
      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    // ==================== Block Operations ====================

    addBlock(sectionId, componentId, position = -1, values = {}, variant = null) {
      const section = this.sectionIndex.get(sectionId);
      if (!section) {
        const newSectionId = this.addSection();
        return this.addBlock(newSectionId, componentId, position, values, variant);
      }

      const componentDef = this.componentDefs[componentId];
      if (!componentDef) {
        console.error(`Component ${componentId} not found`);
        return null;
      }

      const defaultValues = this.getDefaultValues(componentDef.fields);
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

      this.blockIndex.set(newBlock.id, newBlock);
      this.blockToSectionIndex.set(newBlock.id, sectionId);

      this.selectedBlockId = newBlock.id;
      this.selectedSectionId = sectionId;
      this.isDirty = true;
      this.pushHistory();

      this.refreshPreview(newBlock.id);

      return newBlock.id;
    },

    getDefaultValues(fields) {
      const values = {};
      if (!fields) return values;

      for (const [fieldKey, fieldDef] of Object.entries(fields)) {
        if (fieldDef.default !== undefined) {
          values[fieldKey] = fieldDef.default;
        } else {
          // Usar mapeo de valores por defecto, con '' como fallback
          const defaultValue = FIELD_DEFAULT_VALUES[fieldDef.type];
          // Para arrays, crear nueva instancia para evitar referencias compartidas
          values[fieldKey] = Array.isArray(defaultValue) ? [] : (defaultValue ?? '');
        }
      }
      return values;
    },

    updateBlock(blockId, field, value) {
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      block.values[field] = value;
      this.isDirty = true;

      this.pushHistoryDebounced(500);
      this.refreshPreviewDebounced(blockId);
      return true;
    },

    updateBlockValues(blockId, newValues) {
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      block.values = { ...block.values, ...newValues };
      this.isDirty = true;

      this.pushHistoryDebounced(500);
      this.refreshPreviewDebounced(blockId);
      return true;
    },

    removeBlock(blockId) {
      const sectionId = this.blockToSectionIndex.get(blockId);
      if (!sectionId) return false;

      const section = this.sectionIndex.get(sectionId);
      if (!section) return false;

      const blockIdx = section.blocks.findIndex(b => b.id === blockId);
      if (blockIdx === -1) return false;

      section.blocks.splice(blockIdx, 1);

      this.blockIndex.delete(blockId);
      this.blockToSectionIndex.delete(blockId);

      const previewStore = usePreviewStore();
      previewStore.cancelRequest(blockId);
      previewStore.invalidate(blockId);

      if (this.selectedBlockId === blockId) {
        this.selectedBlockId = null;
      }

      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    moveBlock(blockId, targetSectionId, targetPosition) {
      const block = this.blockIndex.get(blockId);
      if (!block) return false;

      const sourceSectionId = this.blockToSectionIndex.get(blockId);
      if (!sourceSectionId) return false;

      const sourceSection = this.sectionIndex.get(sourceSectionId);
      const targetSection = this.sectionIndex.get(targetSectionId);
      if (!sourceSection || !targetSection) return false;

      const blockIdx = sourceSection.blocks.findIndex(b => b.id === blockId);
      if (blockIdx === -1) return false;

      sourceSection.blocks.splice(blockIdx, 1);

      if (targetPosition === -1 || targetPosition >= targetSection.blocks.length) {
        targetSection.blocks.push(block);
      } else {
        targetSection.blocks.splice(targetPosition, 0, block);
      }

      this.blockToSectionIndex.set(blockId, targetSectionId);

      this.isDirty = true;

      if (this._batchMode) {
        this._batchOperations++;
      } else {
        this.pushHistory();
      }
      return true;
    },

    duplicateBlock(blockId) {
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

      section.blocks.splice(blockIdx + 1, 0, newBlock);

      this.blockIndex.set(newBlock.id, newBlock);
      this.blockToSectionIndex.set(newBlock.id, sectionId);

      this.selectedBlockId = newBlock.id;
      this.isDirty = true;
      this.pushHistory();

      this.refreshPreview(newBlock.id);

      return newBlock.id;
    },

    // ==================== Selection ====================

    selectBlock(blockId) {
      this.selectedBlockId = blockId;

      if (blockId) {
        const section = this.getSectionByBlockId(blockId);
        if (section) {
          this.selectedSectionId = section.id;
        }
      }
    },

    selectSection(sectionId) {
      this.selectedSectionId = sectionId;
      this.selectedBlockId = null;
    },

    clearSelection() {
      this.selectedBlockId = null;
      this.selectedSectionId = null;
    },

    // ==================== History (delegated) ====================

    pushHistory() {
      const historyStore = useHistoryStore();
      historyStore.push({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });
    },

    pushHistoryDebounced(delay = 500) {
      const historyStore = useHistoryStore();
      historyStore.pushDebounced({
        sections: JSON.parse(JSON.stringify(this.sections)),
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      }, delay);
    },

    flushPendingHistory() {
      const historyStore = useHistoryStore();
      historyStore.flushPending();
    },

    undo() {
      const historyStore = useHistoryStore();
      const newState = historyStore.undo({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      if (newState) {
        this.sections = newState.sections;
        this.selectedBlockId = newState.selectedBlockId;
        this.selectedSectionId = newState.selectedSectionId;
        this.isDirty = true;
        this.rebuildIndex();
        this.refreshAllPreviews();
        return true;
      }
      return false;
    },

    redo() {
      const historyStore = useHistoryStore();
      const newState = historyStore.redo({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      if (newState) {
        this.sections = newState.sections;
        this.selectedBlockId = newState.selectedBlockId;
        this.selectedSectionId = newState.selectedSectionId;
        this.isDirty = true;
        this.rebuildIndex();
        this.refreshAllPreviews();
        return true;
      }
      return false;
    },

    jumpToHistoryIndex(targetIndex) {
      const historyStore = useHistoryStore();
      const newState = historyStore.jumpToIndex(targetIndex);

      if (newState) {
        this.sections = newState.sections;
        this.selectedBlockId = newState.selectedBlockId;
        this.selectedSectionId = newState.selectedSectionId;
        this.isDirty = true;
        this.rebuildIndex();
        this.refreshAllPreviews();
        return true;
      }
      return false;
    },

    // ==================== Preview (delegated) ====================

    refreshPreview(blockId, forceRefresh = false) {
      const block = this.getBlockById(blockId);
      if (!block) return;

      const previewStore = usePreviewStore();
      previewStore.refresh(block, forceRefresh);
    },

    refreshPreviewDebounced(blockId) {
      const block = this.getBlockById(blockId);
      if (!block) return;

      const previewStore = usePreviewStore();
      previewStore.refreshDebounced(block);
    },

    async refreshAllPreviews() {
      const blocks = this.sections.flatMap(s => s.blocks);
      const previewStore = usePreviewStore();
      await previewStore.refreshAll(blocks);
    },

    cleanupPreviewCache(removeExpired = true) {
      const previewStore = usePreviewStore();
      const validBlockIds = Array.from(this.blockIndex.keys());
      let cleaned = previewStore.cleanupDeleted(validBlockIds);

      if (removeExpired) {
        cleaned += previewStore.cleanupExpired();
      }

      return cleaned;
    },

    invalidatePreviewCache(blockIds = null) {
      const previewStore = usePreviewStore();
      previewStore.invalidate(blockIds);
    },

    // ==================== Save ====================

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

    // ==================== Import/Export ====================

    exportLayout() {
      return JSON.stringify(this.layoutJson, null, 2);
    },

    importLayout(jsonString) {
      try {
        const layout = JSON.parse(jsonString);
        this.loadFromLegacyLayout(layout);
        this.isDirty = true;
        this.pushHistory();
        this.refreshAllPreviews();
        return true;
      } catch (error) {
        console.error('Error importing layout:', error);
        return false;
      }
    },

    // ==================== Performance Stats ====================

    getPerformanceStats() {
      const historyStore = useHistoryStore();
      const previewStore = usePreviewStore();

      const layoutMemory = JSON.stringify(this.sections).length;

      return {
        blocks: this.blockIndex.size,
        sections: this.sectionIndex.size,
        history: historyStore.stats,
        previewCache: previewStore.cacheStats,
        memoryEstimate: {
          layout: `${(layoutMemory / 1024).toFixed(1)} KB`,
        },
      };
    },
  },
});
