<template>
  <div
    class="flavor-pb-drop-zone"
    :class="dropZoneClasses"
    @dragover.prevent.stop="handleDragOver"
    @dragleave="handleDragLeave"
    @drop.prevent.stop="handleDrop"
  >
    <div class="drop-indicator">
      <span class="drop-line"></span>
      <span v-if="showLabel" class="drop-label">Soltar aquí</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';

const props = defineProps({
  position: {
    type: String,
    default: 'before', // 'before' | 'after'
  },
  sectionIndex: {
    type: Number,
    default: null,
  },
  blockIndex: {
    type: Number,
    default: null,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['drop']);

const builderStore = useBuilderStore();

// Local state
const isHovering = ref(false);

// Computed
const dropZoneClasses = computed(() => ({
  'is-active': props.isActive || isHovering.value,
  'is-visible': builderStore.isDragging,
  [`position-${props.position}`]: true,
}));

const showLabel = computed(() => isHovering.value);

// Methods
function handleDragOver(event) {
  const dragSource = builderStore.dragSource;
  if (!dragSource) return;

  isHovering.value = true;

  // Determinar el efecto permitido
  if (dragSource.type === 'sidebar') {
    event.dataTransfer.dropEffect = 'copy';
  } else {
    event.dataTransfer.dropEffect = 'move';
  }
}

function handleDragLeave() {
  isHovering.value = false;
}

function handleDrop() {
  isHovering.value = false;
  emit('drop');
}
</script>

<style scoped>
.flavor-pb-drop-zone {
  position: relative;
  height: 0;
  transition: height 0.2s ease;
  overflow: visible;
}

.flavor-pb-drop-zone.is-visible {
  height: 20px;
}

.flavor-pb-drop-zone.is-active {
  height: 40px;
}

.drop-indicator {
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  opacity: 0;
  transition: opacity 0.2s;
}

.flavor-pb-drop-zone.is-visible .drop-indicator {
  opacity: 0.5;
}

.flavor-pb-drop-zone.is-active .drop-indicator {
  opacity: 1;
}

.drop-line {
  flex: 1;
  height: 3px;
  background: var(--pb-primary);
  border-radius: 2px;
}

.drop-label {
  padding: 4px 12px;
  background: var(--pb-primary);
  color: white;
  font-size: 11px;
  border-radius: 4px;
  white-space: nowrap;
}
</style>
