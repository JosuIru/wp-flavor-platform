<template>
  <div class="inline-image-editor" @click.stop>
    <!-- Image preview with resize handles -->
    <div class="image-container" ref="containerRef">
      <img
        :src="value"
        :alt="fieldName"
        :style="imageStyles"
        @load="handleImageLoad"
      />

      <!-- Resize handles -->
      <ResizeHandles
        v-if="isSelected"
        :block-id="blockId"
        :field-name="fieldName"
        :maintain-aspect-ratio="true"
      />
    </div>

    <!-- Quick actions -->
    <div class="image-actions" v-if="isSelected">
      <button class="action-btn" @click="openMediaLibrary" title="Cambiar imagen">
        <span class="dashicons dashicons-edit"></span>
      </button>
      <button class="action-btn" @click="resetSize" title="Restablecer tamaño">
        <span class="dashicons dashicons-image-rotate"></span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import ResizeHandles from './ResizeHandles.vue';

const props = defineProps({
  blockId: {
    type: String,
    required: true,
  },
  fieldName: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    default: '',
  },
  width: {
    type: Number,
    default: null,
  },
  height: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['update']);

const builderStore = useBuilderStore();
const containerRef = ref(null);
const naturalWidth = ref(0);
const naturalHeight = ref(0);

const isSelected = computed(() =>
  builderStore.selectedBlockId === props.blockId
);

const imageStyles = computed(() => {
  const styles = {};

  if (props.width) {
    styles.width = `${props.width}px`;
  }
  if (props.height) {
    styles.height = `${props.height}px`;
  }

  return styles;
});

function handleImageLoad(event) {
  naturalWidth.value = event.target.naturalWidth;
  naturalHeight.value = event.target.naturalHeight;
}

function openMediaLibrary() {
  if (typeof wp !== 'undefined' && wp.media) {
    const mediaFrame = wp.media({
      title: 'Seleccionar imagen',
      button: { text: 'Usar esta imagen' },
      multiple: false,
      library: { type: 'image' },
    });

    mediaFrame.on('select', function() {
      const attachment = mediaFrame.state().get('selection').first().toJSON();
      emit('update', { url: attachment.url });
    });

    mediaFrame.open();
  }
}

function resetSize() {
  emit('update', {
    width: naturalWidth.value,
    height: naturalHeight.value,
  });
}
</script>

<style scoped>
.inline-image-editor {
  position: relative;
  display: inline-block;
}

.image-container {
  position: relative;
  display: inline-block;
}

.image-container img {
  display: block;
  max-width: 100%;
  height: auto;
}

.image-actions {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
}

.action-btn {
  padding: 6px;
  background: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--pb-bg);
  transform: scale(1.1);
}

.action-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}
</style>
