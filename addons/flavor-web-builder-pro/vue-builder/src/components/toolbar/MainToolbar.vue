<template>
  <div class="flavor-pb-main-toolbar">
    <!-- Left section: Add component & templates -->
    <div class="toolbar-section toolbar-left">
      <button class="toolbar-btn primary" @click="openAddComponent">
        <span class="dashicons dashicons-plus-alt"></span>
        <span class="btn-label">Añadir</span>
      </button>

      <button class="toolbar-btn" @click="openTemplates" title="Cargar plantilla">
        <span class="dashicons dashicons-schedule"></span>
        <span class="btn-label">Plantillas</span>
      </button>
    </div>

    <!-- Center section: Responsive & zoom -->
    <div class="toolbar-section toolbar-center">
      <DeviceToggle />

      <div class="toolbar-separator"></div>

      <div class="zoom-controls">
        <button class="zoom-btn" @click="zoomOut" :disabled="cannotZoomOut" title="Reducir">
          <span class="dashicons dashicons-minus"></span>
        </button>
        <span class="zoom-value">{{ uiStore.canvasZoom }}%</span>
        <button class="zoom-btn" @click="zoomIn" :disabled="cannotZoomIn" title="Ampliar">
          <span class="dashicons dashicons-plus"></span>
        </button>
        <button class="zoom-btn" @click="resetZoom" title="Restablecer zoom">
          <span class="dashicons dashicons-image-rotate"></span>
        </button>
      </div>
    </div>

    <!-- Right section: Undo/redo, save, preview -->
    <div class="toolbar-section toolbar-right">
      <UndoRedoButtons />

      <div class="toolbar-separator"></div>

      <button class="toolbar-btn" @click="handlePreview" title="Vista previa (Ctrl+P)">
        <span class="dashicons" :class="uiStore.isPreviewMode ? 'dashicons-hidden' : 'dashicons-visibility'"></span>
        <span class="btn-label">{{ uiStore.isPreviewMode ? 'Editar' : 'Preview' }}</span>
      </button>

      <button
        class="toolbar-btn primary"
        @click="handleSave"
        :disabled="!builderStore.isDirty || builderStore.isSaving"
        title="Guardar (Ctrl+S)"
      >
        <span v-if="builderStore.isSaving" class="dashicons dashicons-update spin"></span>
        <span v-else class="dashicons dashicons-cloud-saved"></span>
        <span class="btn-label">{{ builderStore.isSaving ? 'Guardando...' : 'Guardar' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore, ZOOM_CONFIG } from '../../stores/uiStore';

import DeviceToggle from './DeviceToggle.vue';
import UndoRedoButtons from './UndoRedoButtons.vue';

const emit = defineEmits(['save', 'preview', 'undo', 'redo']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();

// Computed
const cannotZoomIn = computed(() => uiStore.canvasZoom >= ZOOM_CONFIG.MAX);
const cannotZoomOut = computed(() => uiStore.canvasZoom <= ZOOM_CONFIG.MIN);

// Methods
function openAddComponent() {
  // Toggle sidebar si está cerrado
  if (!uiStore.sidebarOpen) {
    uiStore.toggleSidebar();
  }
}

function openTemplates() {
  uiStore.openModal('templates');
}

function handlePreview() {
  emit('preview');
}

function handleSave() {
  emit('save');
}

function zoomIn() {
  uiStore.zoomIn();
}

function zoomOut() {
  uiStore.zoomOut();
}

function resetZoom() {
  uiStore.resetZoom();
}
</script>

<style scoped>
.flavor-pb-main-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: var(--pb-toolbar-height, 50px);
  padding: 0 15px;
  background: var(--pb-bg-light);
  border-bottom: 1px solid var(--pb-border);
}

.toolbar-section {
  display: flex;
  align-items: center;
  gap: 8px;
}

.toolbar-left {
  flex: 1;
}

.toolbar-center {
  flex: 0 0 auto;
}

.toolbar-right {
  flex: 1;
  justify-content: flex-end;
}

.toolbar-separator {
  width: 1px;
  height: 24px;
  background: var(--pb-border);
  margin: 0 8px;
}

/* Buttons */
.toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--pb-radius);
  color: var(--pb-text);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.toolbar-btn:hover:not(:disabled) {
  background: var(--pb-bg);
  border-color: var(--pb-border);
}

.toolbar-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toolbar-btn.primary {
  background: var(--pb-primary);
  border-color: var(--pb-primary);
  color: white;
}

.toolbar-btn.primary:hover:not(:disabled) {
  background: var(--pb-primary-hover);
  border-color: var(--pb-primary-hover);
}

.toolbar-btn .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.btn-label {
  display: inline-block;
}

/* Zoom controls */
.zoom-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.zoom-btn {
  padding: 4px;
  background: transparent;
  border: none;
  cursor: pointer;
  border-radius: 3px;
  color: var(--pb-text-muted);
  transition: all 0.2s;
}

.zoom-btn:hover:not(:disabled) {
  background: var(--pb-bg);
  color: var(--pb-text);
}

.zoom-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.zoom-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.zoom-value {
  min-width: 45px;
  text-align: center;
  font-size: 12px;
  color: var(--pb-text-muted);
}

/* Animations */
.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .btn-label {
    display: none;
  }

  .toolbar-btn {
    padding: 6px 8px;
  }

  .zoom-controls {
    display: none;
  }
}
</style>
