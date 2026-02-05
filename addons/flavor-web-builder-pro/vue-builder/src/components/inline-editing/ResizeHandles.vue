<template>
  <div class="resize-handles" v-if="showHandles">
    <div
      v-for="handle in handles"
      :key="handle.position"
      class="resize-handle"
      :class="`handle-${handle.position}`"
      @mousedown.stop.prevent="startResize($event, handle.position)"
    ></div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useResize } from '../../composables/useResize';
import { useBuilderStore } from '../../stores/builderStore';

const props = defineProps({
  blockId: {
    type: String,
    required: true,
  },
  fieldName: {
    type: String,
    default: 'image',
  },
  corners: {
    type: Boolean,
    default: true,
  },
  edges: {
    type: Boolean,
    default: true,
  },
  maintainAspectRatio: {
    type: Boolean,
    default: true,
  },
});

const builderStore = useBuilderStore();
const { startResize: initResize } = useResize();

const showHandles = computed(() =>
  builderStore.selectedBlockId === props.blockId
);

const handles = computed(() => {
  const handlesArray = [];

  if (props.edges) {
    handlesArray.push(
      { position: 'n' },
      { position: 's' },
      { position: 'e' },
      { position: 'w' }
    );
  }

  if (props.corners) {
    handlesArray.push(
      { position: 'nw' },
      { position: 'ne' },
      { position: 'sw' },
      { position: 'se' }
    );
  }

  return handlesArray;
});

function startResize(event, handle) {
  const blockElement = event.target.closest('.flavor-pb-block');
  if (!blockElement) return;

  const contentElement = blockElement.querySelector('.block-preview') || blockElement;

  initResize(event, {
    blockId: props.blockId,
    fieldName: props.fieldName,
    handle,
    element: contentElement,
    maintainAspectRatio: props.maintainAspectRatio,
    minWidth: 50,
    minHeight: 50,
  });
}
</script>

<style scoped>
.resize-handles {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.resize-handle {
  position: absolute;
  width: 10px;
  height: 10px;
  background: var(--pb-primary);
  border: 2px solid white;
  border-radius: 2px;
  pointer-events: all;
  z-index: 20;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Corners */
.handle-nw {
  top: -5px;
  left: -5px;
  cursor: nwse-resize;
}

.handle-ne {
  top: -5px;
  right: -5px;
  cursor: nesw-resize;
}

.handle-sw {
  bottom: -5px;
  left: -5px;
  cursor: nesw-resize;
}

.handle-se {
  bottom: -5px;
  right: -5px;
  cursor: nwse-resize;
}

/* Edges */
.handle-n {
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
  cursor: ns-resize;
}

.handle-s {
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
  cursor: ns-resize;
}

.handle-e {
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  cursor: ew-resize;
}

.handle-w {
  left: -5px;
  top: 50%;
  transform: translateY(-50%);
  cursor: ew-resize;
}
</style>
