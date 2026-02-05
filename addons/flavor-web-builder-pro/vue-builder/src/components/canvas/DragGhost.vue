<template>
  <Teleport to="body">
    <div
      v-if="isVisible"
      class="drag-ghost"
      :style="ghostStyle"
    >
      <div class="drag-ghost-content">
        <span class="dashicons" :class="componentIcon"></span>
        <span class="drag-ghost-label">{{ componentLabel }}</span>
      </div>
      <div class="drag-ghost-badge" v-if="variantLabel">
        {{ variantLabel }}
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useComponentDefs } from '../../composables/useComponentDefs';

const builderStore = useBuilderStore();
const { getComponentDef } = useComponentDefs();

// State
const position = ref({ x: 0, y: 0 });
const isVisible = ref(false);

// Computed
const dragSource = computed(() => builderStore.dragSource);

const componentDef = computed(() => {
  if (!dragSource.value) return null;

  if (dragSource.value.type === 'sidebar') {
    return getComponentDef(dragSource.value.componentId);
  }

  if (dragSource.value.type === 'canvas') {
    const block = builderStore.getBlockById(dragSource.value.blockId);
    if (block) {
      return getComponentDef(block.componentId);
    }
  }

  return null;
});

const componentLabel = computed(() => {
  return componentDef.value?.label || 'Componente';
});

const componentIcon = computed(() => {
  return componentDef.value?.icon || 'dashicons-layout';
});

const variantLabel = computed(() => {
  if (!dragSource.value || dragSource.value.type !== 'canvas') return null;

  const block = builderStore.getBlockById(dragSource.value.blockId);
  if (!block?.variant || !componentDef.value?.variants) return null;

  const variant = componentDef.value.variants[block.variant];
  return variant?.label || block.variant;
});

const ghostStyle = computed(() => ({
  left: `${position.value.x}px`,
  top: `${position.value.y}px`,
  opacity: isVisible.value ? 1 : 0,
}));

// Methods
function handleMouseMove(event) {
  if (builderStore.isDragging) {
    position.value = {
      x: event.clientX + 15,
      y: event.clientY + 15,
    };
    isVisible.value = true;
  }
}

function handleDragEnd() {
  isVisible.value = false;
}

// Watch dragging state
watch(() => builderStore.isDragging, (isDragging) => {
  if (!isDragging) {
    isVisible.value = false;
  }
});

// Lifecycle
onMounted(() => {
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('dragend', handleDragEnd);
  document.addEventListener('drop', handleDragEnd);
});

onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('dragend', handleDragEnd);
  document.removeEventListener('drop', handleDragEnd);
});
</script>

<style scoped>
.drag-ghost {
  position: fixed;
  z-index: 100001;
  pointer-events: none;
  transform: translate(0, 0);
  transition: opacity 0.15s ease;
}

.drag-ghost-content {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: var(--pb-primary, #0073aa);
  color: white;
  border-radius: var(--pb-radius, 4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
}

.drag-ghost-content .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.drag-ghost-label {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.drag-ghost-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  padding: 2px 6px;
  background: var(--pb-info, #00a0d2);
  color: white;
  font-size: 10px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>
