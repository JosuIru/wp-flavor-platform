<template>
  <div
    class="flavor-pb-canvas"
    :class="canvasClasses"
    :style="canvasStyles"
    @dragover.prevent="handleCanvasDragOver"
    @dragleave="handleCanvasDragLeave"
    @drop.prevent="handleCanvasDrop"
  >
    <!-- Estado vacío -->
    <div v-if="isEmpty" class="flavor-pb-empty-state">
      <div class="empty-state-content">
        <span class="dashicons dashicons-layout"></span>
        <h3>Comienza a construir tu página</h3>
        <p>Arrastra componentes desde el panel izquierdo o haz clic en el botón de abajo</p>
        <button class="button button-primary" @click="addFirstSection">
          <span class="dashicons dashicons-plus-alt"></span>
          Añadir primera sección
        </button>
      </div>
    </div>

    <!-- Secciones -->
    <template v-else>
      <div
        v-for="(section, sectionIndex) in sections"
        :key="section.id"
        class="flavor-pb-section-wrapper"
      >
        <!-- Drop zone antes de la sección -->
        <DropZone
          v-if="showDropZones"
          position="before"
          :section-index="sectionIndex"
          :is-active="isDropTargetBefore(sectionIndex)"
          @drop="handleSectionDrop(sectionIndex)"
        />

        <!-- Sección -->
        <CanvasSection
          :section="section"
          :section-index="sectionIndex"
          :is-selected="section.id === builderStore.selectedSectionId"
          @select="selectSection(section.id)"
        />

        <!-- Drop zone después de la última sección -->
        <DropZone
          v-if="sectionIndex === sections.length - 1 && showDropZones"
          position="after"
          :section-index="sectionIndex + 1"
          :is-active="isDropTargetAfter(sectionIndex)"
          @drop="handleSectionDrop(sectionIndex + 1)"
        />
      </div>
    </template>

    <!-- Botón para añadir sección -->
    <div class="flavor-pb-add-section" v-if="!isEmpty">
      <button class="add-section-btn" @click="addSection" title="Añadir sección">
        <span class="dashicons dashicons-plus-alt2"></span>
        <span>Añadir sección</span>
      </button>
    </div>

    <!-- Overlay de preview -->
    <PreviewOverlay v-if="uiStore.isPreviewMode" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { useDragDrop } from '../../composables/useDragDrop';
import { useLayout } from '../../composables/useLayout';

import CanvasSection from './CanvasSection.vue';
import DropZone from './DropZone.vue';
import PreviewOverlay from '../preview/PreviewOverlay.vue';

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const { dropIndicator, isDraggingOver, onDragLeave } = useDragDrop();
const { addSection: addNewSection } = useLayout();

// Computed
const sections = computed(() => builderStore.sections);
const isEmpty = computed(() => sections.value.length === 0);

const canvasClasses = computed(() => ({
  'is-empty': isEmpty.value,
  'is-dragging-over': isDraggingOver.value,
  'is-preview-mode': uiStore.isPreviewMode,
}));

const canvasStyles = computed(() => {
  const zoom = uiStore.canvasZoom;
  if (zoom === 100) return {};

  return {
    transform: `scale(${zoom / 100})`,
    transformOrigin: 'top center',
  };
});

const showDropZones = computed(() => builderStore.isDragging);

// Methods
function addFirstSection() {
  addNewSection();
}

function addSection() {
  addNewSection();
}

function selectSection(sectionId) {
  builderStore.selectSection(sectionId);
}

function handleCanvasDragOver(event) {
  // Solo manejar si es drag de sección
  if (builderStore.dragSource?.type === 'section') {
    event.dataTransfer.dropEffect = 'move';
  }
}

function handleCanvasDragLeave(event) {
  onDragLeave(event);
}

function handleCanvasDrop(event) {
  // Manejar drop de nueva sección o componente en canvas vacío
  const dragSource = builderStore.dragSource;

  if (!dragSource) return;

  if (dragSource.type === 'sidebar' && isEmpty.value) {
    // Crear sección y añadir bloque
    const sectionId = addNewSection();
    builderStore.addBlock(sectionId, dragSource.componentId, 0, {}, dragSource.variant);
  }
}

function handleSectionDrop(position) {
  const dragSource = builderStore.dragSource;
  if (dragSource?.type === 'section') {
    builderStore.moveSection(dragSource.sectionId, position);
  }
}

function isDropTargetBefore(sectionIndex) {
  return (
    dropIndicator.value?.isSection &&
    dropIndicator.value?.position === sectionIndex
  );
}

function isDropTargetAfter(sectionIndex) {
  return (
    dropIndicator.value?.isSection &&
    dropIndicator.value?.position === sectionIndex + 1
  );
}
</script>

<style scoped>
.flavor-pb-canvas {
  min-height: 100%;
  background: var(--pb-bg);
  padding: 20px;
  transition: background-color 0.2s;
}

.flavor-pb-canvas.is-dragging-over {
  background: rgba(0, 115, 170, 0.05);
}

/* Empty state */
.flavor-pb-empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  border: 2px dashed var(--pb-border);
  border-radius: var(--pb-radius-lg);
  background: var(--pb-bg-light);
}

.empty-state-content {
  text-align: center;
  padding: 40px;
}

.empty-state-content .dashicons {
  font-size: 48px;
  width: 48px;
  height: 48px;
  color: var(--pb-text-muted);
  margin-bottom: 16px;
}

.empty-state-content h3 {
  margin: 0 0 8px;
  font-size: 18px;
  color: var(--pb-text);
}

.empty-state-content p {
  margin: 0 0 20px;
  color: var(--pb-text-light);
}

.empty-state-content .button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Section wrapper */
.flavor-pb-section-wrapper {
  margin-bottom: 20px;
}

.flavor-pb-section-wrapper:last-child {
  margin-bottom: 0;
}

/* Add section button */
.flavor-pb-add-section {
  display: flex;
  justify-content: center;
  padding: 20px;
}

.add-section-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: transparent;
  border: 2px dashed var(--pb-border);
  border-radius: var(--pb-radius);
  color: var(--pb-text-light);
  cursor: pointer;
  transition: all 0.2s;
}

.add-section-btn:hover {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
}

.add-section-btn .dashicons {
  font-size: 18px;
  width: 18px;
  height: 18px;
}

/* Preview mode */
.flavor-pb-canvas.is-preview-mode {
  pointer-events: none;
}

.flavor-pb-canvas.is-preview-mode .flavor-pb-add-section {
  display: none;
}
</style>
