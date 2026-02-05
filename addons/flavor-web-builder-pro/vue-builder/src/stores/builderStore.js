import { defineStore } from 'pinia';

/**
 * Store principal del Page Builder
 * Gestiona layout, historial y estado de drag & drop
 */
export const useBuilderStore = defineStore('builder', {
  state: () => ({
    // Estructura del layout: array de secciones con bloques
    sections: [],

    // Bloque seleccionado actualmente
    selectedBlockId: null,

    // Sección seleccionada actualmente
    selectedSectionId: null,

    // Definiciones de componentes desde WordPress
    componentDefs: {},

    // Categorías de componentes
    categories: [],

    // Historial para undo/redo
    historyStack: [],
    historyIndex: -1,
    maxHistorySize: 50,

    // Cache de HTML preview por bloque
    previewHtmlCache: {},

    // Estado de drag & drop
    isDragging: false,
    dragSource: null, // { type: 'sidebar'|'canvas', componentId?, blockId?, sectionId? }
    dropTarget: null, // { sectionId, position }

    // Estado de guardado
    isDirty: false,
    isSaving: false,
    lastSavedAt: null,

    // Configuración global
    postId: 0,
    ajaxUrl: '',
    nonce: '',
    previewUrl: '',
  }),

  getters: {
    /**
     * Obtener bloque por ID
     */
    getBlockById: (state) => (blockId) => {
      for (const section of state.sections) {
        const block = section.blocks.find(b => b.id === blockId);
        if (block) return block;
      }
      return null;
    },

    /**
     * Obtener sección por ID
     */
    getSectionById: (state) => (sectionId) => {
      return state.sections.find(s => s.id === sectionId);
    },

    /**
     * Obtener la sección que contiene un bloque
     */
    getSectionByBlockId: (state) => (blockId) => {
      return state.sections.find(section =>
        section.blocks.some(block => block.id === blockId)
      );
    },

    /**
     * Bloque seleccionado actual
     */
    selectedBlock: (state) => {
      if (!state.selectedBlockId) return null;
      for (const section of state.sections) {
        const block = section.blocks.find(b => b.id === state.selectedBlockId);
        if (block) return block;
      }
      return null;
    },

    /**
     * Definición del componente del bloque seleccionado
     */
    selectedComponentDef: (state) => {
      const block = state.sections
        .flatMap(s => s.blocks)
        .find(b => b.id === state.selectedBlockId);
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
    canRedo: (state) => state.historyIndex < state.historyStack.length - 1,

    /**
     * Componentes agrupados por categoría
     */
    componentsByCategory: (state) => {
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
     * Inicializar store con datos de WordPress
     */
    initialize(config) {
      this.postId = config.postId || 0;
      this.ajaxUrl = config.ajaxUrl || '';
      this.nonce = config.nonce || '';
      this.previewUrl = config.previewUrl || '';
      this.componentDefs = config.components || {};
      this.categories = config.categories || [];

      // Cargar layout existente
      if (config.layout && Array.isArray(config.layout)) {
        this.loadFromLegacyLayout(config.layout);
      }

      // Guardar estado inicial en historial
      this.pushHistory();
    },

    /**
     * Cargar layout desde formato legacy (array plano de componentes)
     */
    loadFromLegacyLayout(legacyLayout) {
      // El formato legacy es un array plano de componentes
      // Lo convertimos a secciones con bloques
      if (!Array.isArray(legacyLayout)) {
        this.sections = [this.createSection()];
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

      this.isDirty = true;
      this.pushHistory();
      return newSection.id;
    },

    /**
     * Añadir bloque a una sección
     */
    addBlock(sectionId, componentId, position = -1, values = {}, variant = null) {
      const section = this.sections.find(s => s.id === sectionId);
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
     * Actualizar valor de campo en bloque
     */
    updateBlock(blockId, field, value) {
      for (const section of this.sections) {
        const block = section.blocks.find(b => b.id === blockId);
        if (block) {
          block.values[field] = value;
          this.isDirty = true;

          // Refrescar preview con debounce
          this.refreshPreviewDebounced(blockId);
          return true;
        }
      }
      return false;
    },

    /**
     * Actualizar múltiples valores de bloque
     */
    updateBlockValues(blockId, newValues) {
      for (const section of this.sections) {
        const block = section.blocks.find(b => b.id === blockId);
        if (block) {
          block.values = { ...block.values, ...newValues };
          this.isDirty = true;
          this.refreshPreviewDebounced(blockId);
          return true;
        }
      }
      return false;
    },

    /**
     * Eliminar bloque
     */
    removeBlock(blockId) {
      for (const section of this.sections) {
        const blockIndex = section.blocks.findIndex(b => b.id === blockId);
        if (blockIndex !== -1) {
          section.blocks.splice(blockIndex, 1);

          // Limpiar cache de preview
          delete this.previewHtmlCache[blockId];

          // Deseleccionar si era el seleccionado
          if (this.selectedBlockId === blockId) {
            this.selectedBlockId = null;
          }

          this.isDirty = true;
          this.pushHistory();
          return true;
        }
      }
      return false;
    },

    /**
     * Eliminar sección
     */
    removeSection(sectionId) {
      const sectionIndex = this.sections.findIndex(s => s.id === sectionId);
      if (sectionIndex !== -1) {
        // Limpiar cache de preview de todos los bloques
        const section = this.sections[sectionIndex];
        section.blocks.forEach(block => {
          delete this.previewHtmlCache[block.id];
        });

        this.sections.splice(sectionIndex, 1);

        // Deseleccionar si era la sección seleccionada
        if (this.selectedSectionId === sectionId) {
          this.selectedSectionId = null;
          this.selectedBlockId = null;
        }

        this.isDirty = true;
        this.pushHistory();
        return true;
      }
      return false;
    },

    /**
     * Mover bloque a nueva posición
     */
    moveBlock(blockId, targetSectionId, targetPosition) {
      // Encontrar bloque y su sección actual
      let sourceSection = null;
      let blockIndex = -1;
      let block = null;

      for (const section of this.sections) {
        const idx = section.blocks.findIndex(b => b.id === blockId);
        if (idx !== -1) {
          sourceSection = section;
          blockIndex = idx;
          block = section.blocks[idx];
          break;
        }
      }

      if (!block || !sourceSection) return false;

      const targetSection = this.sections.find(s => s.id === targetSectionId);
      if (!targetSection) return false;

      // Remover de posición actual
      sourceSection.blocks.splice(blockIndex, 1);

      // Insertar en nueva posición
      if (targetPosition === -1 || targetPosition >= targetSection.blocks.length) {
        targetSection.blocks.push(block);
      } else {
        targetSection.blocks.splice(targetPosition, 0, block);
      }

      this.isDirty = true;
      this.pushHistory();
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
      this.pushHistory();
      return true;
    },

    /**
     * Duplicar bloque
     */
    duplicateBlock(blockId) {
      for (const section of this.sections) {
        const blockIndex = section.blocks.findIndex(b => b.id === blockId);
        if (blockIndex !== -1) {
          const originalBlock = section.blocks[blockIndex];
          const newBlock = {
            id: this.generateId(),
            componentId: originalBlock.componentId,
            values: JSON.parse(JSON.stringify(originalBlock.values)),
            variant: originalBlock.variant,
          };

          // Insertar después del original
          section.blocks.splice(blockIndex + 1, 0, newBlock);

          this.selectedBlockId = newBlock.id;
          this.isDirty = true;
          this.pushHistory();

          // Refrescar preview del nuevo bloque
          this.refreshPreview(newBlock.id);

          return newBlock.id;
        }
      }
      return null;
    },

    /**
     * Actualizar settings de sección
     */
    updateSectionSettings(sectionId, newSettings) {
      const section = this.sections.find(s => s.id === sectionId);
      if (!section) return false;

      section.settings = { ...section.settings, ...newSettings };
      this.isDirty = true;
      this.pushHistory();
      return true;
    },

    /**
     * Duplicar sección
     */
    duplicateSection(sectionId) {
      const sectionIndex = this.sections.findIndex(s => s.id === sectionId);
      if (sectionIndex === -1) return null;

      const originalSection = this.sections[sectionIndex];
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

      this.sections.splice(sectionIndex + 1, 0, newSection);
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
     * Guardar estado actual en historial
     */
    pushHistory() {
      // Crear snapshot del estado actual
      const snapshot = JSON.stringify({
        sections: this.sections,
        selectedBlockId: this.selectedBlockId,
        selectedSectionId: this.selectedSectionId,
      });

      // Si estamos en medio del historial, eliminar estados futuros
      if (this.historyIndex < this.historyStack.length - 1) {
        this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
      }

      // Añadir nuevo estado
      this.historyStack.push(snapshot);

      // Limitar tamaño del historial
      if (this.historyStack.length > this.maxHistorySize) {
        this.historyStack.shift();
      } else {
        this.historyIndex++;
      }
    },

    /**
     * Deshacer último cambio
     */
    undo() {
      if (!this.canUndo) return false;

      this.historyIndex--;
      this.restoreFromHistory();
      return true;
    },

    /**
     * Rehacer cambio deshecho
     */
    redo() {
      if (!this.canRedo) return false;

      this.historyIndex++;
      this.restoreFromHistory();
      return true;
    },

    /**
     * Restaurar estado desde historial
     */
    restoreFromHistory() {
      const snapshot = JSON.parse(this.historyStack[this.historyIndex]);
      this.sections = snapshot.sections;
      this.selectedBlockId = snapshot.selectedBlockId;
      this.selectedSectionId = snapshot.selectedSectionId;
      this.isDirty = true;

      // Refrescar previews de todos los bloques
      this.refreshAllPreviews();
    },

    /**
     * Refrescar preview de un bloque via AJAX
     */
    async refreshPreview(blockId) {
      const block = this.getBlockById(blockId);
      if (!block) return;

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
        });

        const data = await response.json();
        if (data.success && data.data?.html) {
          this.previewHtmlCache[blockId] = data.data.html;
        }
      } catch (error) {
        console.error('Error refreshing preview:', error);
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
     * Refrescar todos los previews
     */
    async refreshAllPreviews() {
      const blocks = this.sections.flatMap(s => s.blocks);
      for (const block of blocks) {
        await this.refreshPreview(block.id);
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
