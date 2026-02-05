<template>
  <div
    class="flavor-pb-section"
    :class="sectionClasses"
    :style="sectionStyles"
    :data-section-id="section.id"
    @click.stop="handleClick"
    @dragover.prevent="handleDragOver"
    @dragleave="handleDragLeave"
    @drop.prevent="handleDrop"
  >
    <!-- Toolbar de sección -->
    <div class="section-toolbar" v-show="isSelected || isHovered">
      <div class="section-toolbar-label">
        <span class="dashicons dashicons-screenoptions"></span>
        Sección {{ sectionIndex + 1 }}
      </div>
      <div class="section-toolbar-actions">
        <button
          class="toolbar-btn"
          @click.stop="moveUp"
          :disabled="sectionIndex === 0"
          title="Mover arriba"
        >
          <span class="dashicons dashicons-arrow-up-alt2"></span>
        </button>
        <button
          class="toolbar-btn"
          @click.stop="moveDown"
          :disabled="isLastSection"
          title="Mover abajo"
        >
          <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
        <button class="toolbar-btn" @click.stop="duplicate" title="Duplicar">
          <span class="dashicons dashicons-admin-page"></span>
        </button>
        <button class="toolbar-btn" @click.stop="openSettings" title="Configuración">
          <span class="dashicons dashicons-admin-generic"></span>
        </button>
        <button class="toolbar-btn danger" @click.stop="remove" title="Eliminar">
          <span class="dashicons dashicons-trash"></span>
        </button>
      </div>
      <!-- Handle de drag -->
      <div
        class="section-drag-handle"
        draggable="true"
        @dragstart="handleSectionDragStart"
        @dragend="handleDragEnd"
        title="Arrastrar para reordenar"
      >
        <span class="dashicons dashicons-move"></span>
      </div>
    </div>

    <!-- Contenido de la sección -->
    <div class="section-content">
      <!-- Bloques -->
      <template v-if="section.blocks.length > 0">
        <div
          v-for="(block, blockIndex) in section.blocks"
          :key="block.id"
          class="flavor-pb-block-wrapper"
        >
          <!-- Drop zone antes del bloque -->
          <DropZone
            v-if="showBlockDropZones"
            position="before"
            :block-index="blockIndex"
            :is-active="isDropTargetBefore(blockIndex)"
            @drop="handleBlockDrop(blockIndex)"
          />

          <!-- Bloque -->
          <CanvasBlock
            :block="block"
            :block-index="blockIndex"
            :section-id="section.id"
            :is-selected="block.id === builderStore.selectedBlockId"
            @select="selectBlock(block.id)"
          />

          <!-- Drop zone después del último bloque -->
          <DropZone
            v-if="blockIndex === section.blocks.length - 1 && showBlockDropZones"
            position="after"
            :block-index="blockIndex + 1"
            :is-active="isDropTargetAfter(blockIndex)"
            @drop="handleBlockDrop(blockIndex + 1)"
          />
        </div>
      </template>

      <!-- Estado vacío de sección -->
      <div
        v-else
        class="section-empty"
        :class="{ 'is-drop-target': isDroppingHere }"
      >
        <span class="dashicons dashicons-plus-alt"></span>
        <span>Arrastra componentes aquí</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { useDragDrop } from '../../composables/useDragDrop';
import { useLayout } from '../../composables/useLayout';

import CanvasBlock from './CanvasBlock.vue';
import DropZone from './DropZone.vue';

const props = defineProps({
  section: {
    type: Object,
    required: true,
  },
  sectionIndex: {
    type: Number,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const {
  dropIndicator,
  onSectionDragStart,
  onDragOver,
  onDragLeave,
  onDrop,
  onDragEnd,
} = useDragDrop();
const {
  duplicateSection,
  removeSection,
  moveSectionUp,
  moveSectionDown,
} = useLayout();

// Local state
const isHovered = ref(false);
const isDroppingHere = ref(false);

// Computed
const sectionClasses = computed(() => ({
  'is-selected': props.isSelected,
  'is-hovered': isHovered.value,
  'is-empty': props.section.blocks.length === 0,
  'is-drop-target': isDroppingHere.value,
}));

const sectionStyles = computed(() => {
  const settings = props.section.settings || {};
  const styles = {};

  if (settings.backgroundColor) {
    styles.backgroundColor = settings.backgroundColor;
  }
  if (settings.paddingTop) {
    styles.paddingTop = settings.paddingTop;
  }
  if (settings.paddingBottom) {
    styles.paddingBottom = settings.paddingBottom;
  }

  return styles;
});

const isLastSection = computed(() =>
  props.sectionIndex === builderStore.sections.length - 1
);

const showBlockDropZones = computed(() =>
  builderStore.isDragging &&
  (builderStore.dragSource?.type === 'sidebar' || builderStore.dragSource?.type === 'canvas')
);

// Methods
function handleClick() {
  emit('select');
}

function selectBlock(blockId) {
  builderStore.selectBlock(blockId);
}

function handleDragOver(event) {
  const dragSource = builderStore.dragSource;
  if (!dragSource) return;

  if (dragSource.type === 'sidebar' || dragSource.type === 'canvas') {
    isDroppingHere.value = true;
    onDragOver(event, props.section.id, props.section.blocks.length);
  }
}

function handleDragLeave(event) {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDroppingHere.value = false;
    onDragLeave(event);
  }
}

function handleDrop(event) {
  isDroppingHere.value = false;
  onDrop(event, props.section.id, props.section.blocks.length);
}

function handleSectionDragStart(event) {
  onSectionDragStart(event, props.section.id);
}

function handleDragEnd(event) {
  onDragEnd(event);
}

function handleBlockDrop(position) {
  const dragSource = builderStore.dragSource;
  if (!dragSource) return;

  if (dragSource.type === 'sidebar') {
    builderStore.addBlock(
      props.section.id,
      dragSource.componentId,
      position,
      {},
      dragSource.variant
    );
  } else if (dragSource.type === 'canvas') {
    builderStore.moveBlock(dragSource.blockId, props.section.id, position);
  }
}

function isDropTargetBefore(blockIndex) {
  return (
    dropIndicator.value?.sectionId === props.section.id &&
    dropIndicator.value?.position === blockIndex
  );
}

function isDropTargetAfter(blockIndex) {
  return (
    dropIndicator.value?.sectionId === props.section.id &&
    dropIndicator.value?.position === blockIndex + 1
  );
}

function moveUp() {
  moveSectionUp(props.section.id);
}

function moveDown() {
  moveSectionDown(props.section.id);
}

function duplicate() {
  duplicateSection(props.section.id);
}

function openSettings() {
  uiStore.openModal('sectionSettings', { sectionId: props.section.id });
}

function remove() {
  removeSection(props.section.id);
}
</script>

<style scoped>
.flavor-pb-section {
  position: relative;
  background: var(--pb-bg-light);
  border: 1px solid var(--pb-border-light);
  border-radius: var(--pb-radius-lg);
  min-height: 100px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-pb-section:hover {
  border-color: var(--pb-border);
}

.flavor-pb-section.is-selected {
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
}

.flavor-pb-section.is-drop-target {
  border-color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
}

/* Section toolbar */
.section-toolbar {
  position: absolute;
  top: -1px;
  left: -1px;
  right: -1px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 8px;
  background: var(--pb-primary);
  color: white;
  border-radius: var(--pb-radius-lg) var(--pb-radius-lg) 0 0;
  font-size: 11px;
  z-index: 10;
}

.section-toolbar-label {
  display: flex;
  align-items: center;
  gap: 4px;
}

.section-toolbar-label .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.section-toolbar-actions {
  display: flex;
  gap: 2px;
}

.toolbar-btn {
  padding: 4px;
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  border-radius: 3px;
  transition: background 0.2s;
}

.toolbar-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.toolbar-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toolbar-btn.danger:hover {
  background: var(--pb-error);
}

.toolbar-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.section-drag-handle {
  cursor: grab;
  padding: 4px 8px;
  margin-left: 8px;
  border-left: 1px solid rgba(255, 255, 255, 0.3);
}

.section-drag-handle:active {
  cursor: grabbing;
}

/* Section content */
.section-content {
  padding: 15px;
}

/* Empty section */
.section-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 80px;
  border: 2px dashed var(--pb-border);
  border-radius: var(--pb-radius);
  color: var(--pb-text-muted);
  transition: all 0.2s;
}

.section-empty.is-drop-target {
  border-color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.1);
  color: var(--pb-primary);
}

.section-empty .dashicons {
  font-size: 24px;
  width: 24px;
  height: 24px;
  margin-bottom: 8px;
}

/* Block wrapper */
.flavor-pb-block-wrapper {
  margin-bottom: 10px;
}

.flavor-pb-block-wrapper:last-child {
  margin-bottom: 0;
}
</style>
