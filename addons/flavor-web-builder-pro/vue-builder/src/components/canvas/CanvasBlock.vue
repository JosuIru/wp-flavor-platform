<template>
  <div
    class="flavor-pb-block"
    :class="blockClasses"
    :data-block-id="block.id"
    :data-component-id="block.componentId"
    draggable="true"
    @click.stop="handleClick"
    @dblclick.stop="handleDoubleClick"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Block Toolbar -->
    <BlockToolbar
      v-if="showToolbar"
      :block-id="block.id"
      :block-index="blockIndex"
      :component-label="componentLabel"
      @move-up="moveUp"
      @move-down="moveDown"
      @duplicate="duplicate"
      @remove="remove"
    />

    <!-- Block Content (Preview) -->
    <div class="block-content">
      <div
        v-if="previewHtml"
        class="block-preview"
        v-html="previewHtml"
      ></div>
      <div v-else class="block-placeholder">
        <span class="dashicons" :class="componentIcon"></span>
        <span class="component-label">{{ componentLabel }}</span>
        <span v-if="isLoadingPreview" class="loading-indicator">
          <span class="dashicons dashicons-update spin"></span>
        </span>
      </div>
    </div>

    <!-- Resize handles (solo para elementos redimensionables) -->
    <ResizeHandles
      v-if="isSelected && canResize"
      :block-id="block.id"
    />

    <!-- Badge de variante -->
    <div v-if="block.variant" class="block-variant-badge">
      {{ variantLabel }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useDragDrop } from '../../composables/useDragDrop';
import { useLayout } from '../../composables/useLayout';
import { useComponentDefs } from '../../composables/useComponentDefs';

// Importar estilos de Tailwind para previews (archivo externo)
import '../../styles/preview-tailwind.css';

import BlockToolbar from '../toolbar/BlockToolbar.vue';
import ResizeHandles from '../inline-editing/ResizeHandles.vue';

// Tipos de campo que permiten redimensionado (inmutable)
const RESIZABLE_FIELD_TYPES = Object.freeze(['image', 'gallery', 'video']);

const props = defineProps({
  block: {
    type: Object,
    required: true,
  },
  blockIndex: {
    type: Number,
    required: true,
  },
  sectionId: {
    type: String,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);

const builderStore = useBuilderStore();
const { onCanvasDragStart, onDragEnd } = useDragDrop();
const { duplicateBlock, removeBlock, moveBlockUp, moveBlockDown } = useLayout();
const { getComponentDef } = useComponentDefs();

// Local state
const isHovered = ref(false);
const isLoadingPreview = ref(false);

// Computed
const componentDef = computed(() => getComponentDef(props.block.componentId));

const componentLabel = computed(() =>
  componentDef.value?.label || props.block.componentId
);

const componentIcon = computed(() =>
  componentDef.value?.icon || 'dashicons-layout'
);

const variantLabel = computed(() => {
  if (!props.block.variant || !componentDef.value?.variants) return '';
  const variant = componentDef.value.variants[props.block.variant];
  return variant?.label || props.block.variant;
});

const previewHtml = computed(() => {
  const cached = builderStore.previewHtmlCache[props.block.id];
  // Soportar nuevo formato {html, timestamp, valuesHash} y legacy (string directo)
  if (!cached) return '';
  return typeof cached === 'string' ? cached : cached.html || '';
});

const canResize = computed(() => {
  // Determinar si el bloque puede redimensionarse basado en sus campos
  if (!componentDef.value?.fields) return false;

  return Object.values(componentDef.value.fields).some(field =>
    RESIZABLE_FIELD_TYPES.includes(field.type)
  );
});

const showToolbar = computed(() =>
  props.isSelected || isHovered.value
);

const blockClasses = computed(() => ({
  'is-selected': props.isSelected,
  'is-hovered': isHovered.value,
  'is-dragging': builderStore.isDragging && builderStore.dragSource?.blockId === props.block.id,
  'has-preview': !!previewHtml.value,
}));

// Methods
function handleClick() {
  emit('select');
}

function handleDoubleClick() {
  // Abrir edición inline del primer campo de texto
  const textFields = Object.entries(componentDef.value?.fields || {})
    .filter(([, field]) => ['text', 'textarea'].includes(field.type));

  if (textFields.length > 0) {
    const [fieldName] = textFields[0];
    // Aquí se podría iniciar edición inline
    // Por ahora solo seleccionamos y abrimos el panel de propiedades
    emit('select');
  }
}

function handleDragStart(event) {
  onCanvasDragStart(event, props.block.id, props.sectionId);
}

function handleDragEnd(event) {
  onDragEnd(event);
}

function moveUp() {
  moveBlockUp(props.block.id);
}

function moveDown() {
  moveBlockDown(props.block.id);
}

function duplicate() {
  duplicateBlock(props.block.id);
}

function remove() {
  removeBlock(props.block.id);
}

// Lifecycle - cargar preview si no existe
onMounted(() => {
  if (!previewHtml.value) {
    loadPreview();
  }
});

// NOTA: No se necesita watcher aquí - el store maneja el debounce
// de refreshPreview cuando se actualizan valores via updateBlock()

async function loadPreview() {
  isLoadingPreview.value = true;
  await builderStore.refreshPreview(props.block.id);
  isLoadingPreview.value = false;
}
</script>

<style scoped>
.flavor-pb-block {
  position: relative;
  background: var(--pb-bg-light);
  border: 1px solid var(--pb-border-light);
  border-radius: var(--pb-radius);
  cursor: grab;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.flavor-pb-block:hover {
  border-color: var(--pb-border);
}

.flavor-pb-block.is-selected {
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
}

.flavor-pb-block.is-dragging {
  opacity: 0.5;
  cursor: grabbing;
}

/* Block content */
.block-content {
  min-height: 60px;
  overflow: visible;
}

.block-preview {
  padding: 0;
  overflow: visible;
}

/* Preview HTML contenido - asegurar que se muestre correctamente */
.block-preview :deep(*) {
  max-width: 100%;
}

.block-preview :deep(> *) {
  pointer-events: none;
}

/* Asegurar que las imagenes y secciones se muestren */
.block-preview :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
}

.block-preview :deep(section),
.block-preview :deep(.flavor-section),
.block-preview :deep([class*="hero"]),
.block-preview :deep([class*="cta"]),
.block-preview :deep([class*="features"]) {
  width: 100%;
  min-height: auto;
}

/* Block placeholder */
.block-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: var(--pb-text-muted);
}

.block-placeholder .dashicons {
  font-size: 20px;
  width: 20px;
  height: 20px;
}

.block-placeholder .component-label {
  font-size: 13px;
}

.loading-indicator {
  margin-left: 8px;
}

.loading-indicator .spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Variant badge */
.block-variant-badge {
  position: absolute;
  top: -8px;
  right: 10px;
  padding: 2px 8px;
  background: var(--pb-info);
  color: white;
  font-size: 10px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>
