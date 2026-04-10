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
import { ref, computed, watch, onMounted } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useDragDrop } from '../../composables/useDragDrop';
import { useLayout } from '../../composables/useLayout';
import { useComponentDefs } from '../../composables/useComponentDefs';

import BlockToolbar from '../toolbar/BlockToolbar.vue';
import ResizeHandles from '../inline-editing/ResizeHandles.vue';

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

  const resizableFields = ['image', 'gallery', 'video'];
  return Object.values(componentDef.value.fields).some(field =>
    resizableFields.includes(field.type)
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

<!-- Estilos globales para el preview de componentes (no scoped) -->
<style>
/* Resetear estilos de WordPress admin dentro del preview */
.flavor-pb-block .block-preview {
  all: initial;
  display: block;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  font-size: 16px;
  line-height: 1.5;
  color: #1d2327;
  box-sizing: border-box;
}

.flavor-pb-block .block-preview *,
.flavor-pb-block .block-preview *::before,
.flavor-pb-block .block-preview *::after {
  box-sizing: border-box;
}

/* Asegurar que las clases de Tailwind funcionen */
.flavor-pb-block .block-preview .flex { display: flex !important; }
.flavor-pb-block .block-preview .grid { display: grid !important; }
.flavor-pb-block .block-preview .hidden { display: none !important; }
.flavor-pb-block .block-preview .block { display: block !important; }
.flavor-pb-block .block-preview .inline-flex { display: inline-flex !important; }
.flavor-pb-block .block-preview .inline-block { display: inline-block !important; }

/* Flex utilities */
.flavor-pb-block .block-preview .flex-col { flex-direction: column !important; }
.flavor-pb-block .block-preview .flex-row { flex-direction: row !important; }
.flavor-pb-block .block-preview .flex-wrap { flex-wrap: wrap !important; }
.flavor-pb-block .block-preview .items-center { align-items: center !important; }
.flavor-pb-block .block-preview .items-start { align-items: flex-start !important; }
.flavor-pb-block .block-preview .items-end { align-items: flex-end !important; }
.flavor-pb-block .block-preview .justify-center { justify-content: center !important; }
.flavor-pb-block .block-preview .justify-between { justify-content: space-between !important; }
.flavor-pb-block .block-preview .justify-start { justify-content: flex-start !important; }
.flavor-pb-block .block-preview .justify-end { justify-content: flex-end !important; }

/* Grid utilities */
.flavor-pb-block .block-preview .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
.flavor-pb-block .block-preview .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
.flavor-pb-block .block-preview .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
.flavor-pb-block .block-preview .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }

/* Gap utilities */
.flavor-pb-block .block-preview .gap-2 { gap: 0.5rem !important; }
.flavor-pb-block .block-preview .gap-4 { gap: 1rem !important; }
.flavor-pb-block .block-preview .gap-6 { gap: 1.5rem !important; }
.flavor-pb-block .block-preview .gap-8 { gap: 2rem !important; }

/* Spacing utilities */
.flavor-pb-block .block-preview .p-4 { padding: 1rem !important; }
.flavor-pb-block .block-preview .p-6 { padding: 1.5rem !important; }
.flavor-pb-block .block-preview .p-8 { padding: 2rem !important; }
.flavor-pb-block .block-preview .px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
.flavor-pb-block .block-preview .px-6 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
.flavor-pb-block .block-preview .py-4 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
.flavor-pb-block .block-preview .py-6 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
.flavor-pb-block .block-preview .py-8 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
.flavor-pb-block .block-preview .py-12 { padding-top: 3rem !important; padding-bottom: 3rem !important; }
.flavor-pb-block .block-preview .py-16 { padding-top: 4rem !important; padding-bottom: 4rem !important; }

/* Margin utilities */
.flavor-pb-block .block-preview .m-0 { margin: 0 !important; }
.flavor-pb-block .block-preview .mb-2 { margin-bottom: 0.5rem !important; }
.flavor-pb-block .block-preview .mb-4 { margin-bottom: 1rem !important; }
.flavor-pb-block .block-preview .mb-6 { margin-bottom: 1.5rem !important; }
.flavor-pb-block .block-preview .mb-8 { margin-bottom: 2rem !important; }
.flavor-pb-block .block-preview .mt-4 { margin-top: 1rem !important; }
.flavor-pb-block .block-preview .mt-8 { margin-top: 2rem !important; }

/* Text utilities */
.flavor-pb-block .block-preview .text-center { text-align: center !important; }
.flavor-pb-block .block-preview .text-left { text-align: left !important; }
.flavor-pb-block .block-preview .text-right { text-align: right !important; }
.flavor-pb-block .block-preview .text-sm { font-size: 0.875rem !important; line-height: 1.25rem !important; }
.flavor-pb-block .block-preview .text-base { font-size: 1rem !important; line-height: 1.5rem !important; }
.flavor-pb-block .block-preview .text-lg { font-size: 1.125rem !important; line-height: 1.75rem !important; }
.flavor-pb-block .block-preview .text-xl { font-size: 1.25rem !important; line-height: 1.75rem !important; }
.flavor-pb-block .block-preview .text-2xl { font-size: 1.5rem !important; line-height: 2rem !important; }
.flavor-pb-block .block-preview .text-3xl { font-size: 1.875rem !important; line-height: 2.25rem !important; }
.flavor-pb-block .block-preview .text-4xl { font-size: 2.25rem !important; line-height: 2.5rem !important; }
.flavor-pb-block .block-preview .font-medium { font-weight: 500 !important; }
.flavor-pb-block .block-preview .font-semibold { font-weight: 600 !important; }
.flavor-pb-block .block-preview .font-bold { font-weight: 700 !important; }

/* Color utilities */
.flavor-pb-block .block-preview .text-white { color: #fff !important; }
.flavor-pb-block .block-preview .text-gray-500 { color: #6b7280 !important; }
.flavor-pb-block .block-preview .text-gray-600 { color: #4b5563 !important; }
.flavor-pb-block .block-preview .text-gray-700 { color: #374151 !important; }
.flavor-pb-block .block-preview .text-gray-900 { color: #111827 !important; }
.flavor-pb-block .block-preview .bg-white { background-color: #fff !important; }
.flavor-pb-block .block-preview .bg-gray-50 { background-color: #f9fafb !important; }
.flavor-pb-block .block-preview .bg-gray-100 { background-color: #f3f4f6 !important; }
.flavor-pb-block .block-preview .bg-gray-900 { background-color: #111827 !important; }
.flavor-pb-block .block-preview .bg-blue-500 { background-color: #3b82f6 !important; }
.flavor-pb-block .block-preview .bg-blue-600 { background-color: #2563eb !important; }
.flavor-pb-block .block-preview .bg-green-500 { background-color: #22c55e !important; }

/* Width/height utilities */
.flavor-pb-block .block-preview .w-full { width: 100% !important; }
.flavor-pb-block .block-preview .h-full { height: 100% !important; }
.flavor-pb-block .block-preview .max-w-4xl { max-width: 56rem !important; }
.flavor-pb-block .block-preview .max-w-6xl { max-width: 72rem !important; }
.flavor-pb-block .block-preview .max-w-7xl { max-width: 80rem !important; }
.flavor-pb-block .block-preview .mx-auto { margin-left: auto !important; margin-right: auto !important; }

/* Border radius */
.flavor-pb-block .block-preview .rounded { border-radius: 0.25rem !important; }
.flavor-pb-block .block-preview .rounded-lg { border-radius: 0.5rem !important; }
.flavor-pb-block .block-preview .rounded-xl { border-radius: 0.75rem !important; }
.flavor-pb-block .block-preview .rounded-2xl { border-radius: 1rem !important; }
.flavor-pb-block .block-preview .rounded-full { border-radius: 9999px !important; }

/* Shadow */
.flavor-pb-block .block-preview .shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important; }
.flavor-pb-block .block-preview .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important; }
.flavor-pb-block .block-preview .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; }

/* Position */
.flavor-pb-block .block-preview .relative { position: relative !important; }
.flavor-pb-block .block-preview .absolute { position: absolute !important; }
.flavor-pb-block .block-preview .inset-0 { top: 0 !important; right: 0 !important; bottom: 0 !important; left: 0 !important; }

/* Overflow */
.flavor-pb-block .block-preview .overflow-hidden { overflow: hidden !important; }

/* Objeto fit para imágenes */
.flavor-pb-block .block-preview .object-cover { object-fit: cover !important; }
.flavor-pb-block .block-preview .object-contain { object-fit: contain !important; }

/* Button reset */
.flavor-pb-block .block-preview button,
.flavor-pb-block .block-preview a.btn,
.flavor-pb-block .block-preview [class*="btn-"] {
  cursor: pointer;
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Input reset */
.flavor-pb-block .block-preview input,
.flavor-pb-block .block-preview select,
.flavor-pb-block .block-preview textarea {
  font-family: inherit;
  font-size: inherit;
}

/* Secciones y contenedores */
.flavor-pb-block .block-preview section,
.flavor-pb-block .block-preview .flavor-section {
  width: 100%;
  display: block;
}

/* Imágenes */
.flavor-pb-block .block-preview img {
  max-width: 100%;
  height: auto;
  display: block;
}

/* Heading reset */
.flavor-pb-block .block-preview h1,
.flavor-pb-block .block-preview h2,
.flavor-pb-block .block-preview h3,
.flavor-pb-block .block-preview h4,
.flavor-pb-block .block-preview h5,
.flavor-pb-block .block-preview h6 {
  margin: 0;
  font-weight: 700;
  line-height: 1.2;
}

/* Párrafos */
.flavor-pb-block .block-preview p {
  margin: 0;
}

/* Listas */
.flavor-pb-block .block-preview ul,
.flavor-pb-block .block-preview ol {
  margin: 0;
  padding: 0;
  list-style: none;
}
</style>
