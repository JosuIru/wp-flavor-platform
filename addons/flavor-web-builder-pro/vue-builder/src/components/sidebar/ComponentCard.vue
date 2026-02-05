<template>
  <div
    class="component-card"
    :class="{ 'has-variants': hasVariants, 'is-dragging': isDragging }"
    draggable="true"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
    @click="handleClick"
    :title="component.description || component.label"
  >
    <div class="card-content">
      <span class="component-icon dashicons" :class="component.icon || 'dashicons-layout'"></span>
      <span class="component-name">{{ component.label }}</span>
    </div>

    <div class="card-actions">
      <button
        v-if="hasVariants"
        class="variant-btn"
        @click.stop="openVariants"
        title="Ver variantes"
      >
        <span class="dashicons dashicons-grid-view"></span>
      </button>
      <button class="add-btn" @click.stop="addComponent" title="Añadir">
        <span class="dashicons dashicons-plus-alt"></span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { useDragDrop } from '../../composables/useDragDrop';

const props = defineProps({
  component: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['add']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const { onSidebarDragStart, onDragEnd } = useDragDrop();

// Local state
const isDragging = ref(false);

// Computed
const hasVariants = computed(() =>
  props.component.variants && Object.keys(props.component.variants).length > 0
);

// Methods
function handleDragStart(event) {
  isDragging.value = true;
  onSidebarDragStart(event, props.component.id);
}

function handleDragEnd(event) {
  isDragging.value = false;
  onDragEnd(event);
}

function handleClick() {
  if (hasVariants.value) {
    openVariants();
  } else {
    addComponent();
  }
}

function openVariants() {
  uiStore.openModal('variant', { componentId: props.component.id });
}

function addComponent(variant = null) {
  emit('add', props.component.id, variant);
}
</script>

<style scoped>
.component-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  background: var(--pb-bg-light);
  border: 1px solid var(--pb-border-light);
  border-radius: var(--pb-radius);
  cursor: grab;
  transition: all 0.2s;
}

.component-card:hover {
  border-color: var(--pb-border);
  background: var(--pb-bg);
}

.component-card.is-dragging {
  opacity: 0.5;
  cursor: grabbing;
}

.card-content {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.component-icon {
  font-size: 18px;
  width: 18px;
  height: 18px;
  color: var(--pb-primary);
}

.component-name {
  font-size: 13px;
  color: var(--pb-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-actions {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s;
}

.component-card:hover .card-actions {
  opacity: 1;
}

.variant-btn,
.add-btn {
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 3px;
  color: var(--pb-text-muted);
  transition: all 0.2s;
}

.variant-btn:hover,
.add-btn:hover {
  background: var(--pb-primary);
  color: white;
}

.variant-btn .dashicons,
.add-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}
</style>
