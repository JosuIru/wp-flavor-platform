<template>
  <FieldWrapper :field="field" field-class="image-field">
    <!-- Preview -->
    <div class="image-preview" v-if="value">
      <img :src="value" :alt="field.label" @error="handleImageError" />
      <div class="image-actions">
        <button class="action-btn" @click="openMediaLibrary" title="Cambiar imagen">
          <span class="dashicons dashicons-edit"></span>
        </button>
        <button class="action-btn danger" @click="removeImage" title="Eliminar">
          <span class="dashicons dashicons-trash"></span>
        </button>
      </div>
    </div>

    <!-- Upload button -->
    <div class="image-upload" v-else>
      <button class="upload-btn" @click="openMediaLibrary">
        <span class="dashicons dashicons-cloud-upload"></span>
        <span>Seleccionar imagen</span>
      </button>
    </div>

    <!-- URL manual -->
    <div class="url-input" v-if="field.allowUrl !== false">
      <input
        type="text"
        class="url-text"
        :value="value"
        placeholder="O introduce una URL..."
        @input="handleUrlInput"
      />
    </div>
  </FieldWrapper>
</template>

<script setup>
import { ref } from 'vue';
import FieldWrapper from './FieldWrapper.vue';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update']);

const imageError = ref(false);

function openMediaLibrary() {
  // Usar el Media Library de WordPress si está disponible
  if (typeof wp !== 'undefined' && wp.media) {
    const mediaFrame = wp.media({
      title: props.field.label || 'Seleccionar imagen',
      button: {
        text: 'Usar esta imagen',
      },
      multiple: false,
      library: {
        type: 'image',
      },
    });

    mediaFrame.on('select', function() {
      const attachment = mediaFrame.state().get('selection').first().toJSON();
      emit('update', attachment.url);
    });

    mediaFrame.open();
  } else {
    // Fallback: mostrar input de URL
    console.warn('WordPress Media Library not available');
  }
}

function removeImage() {
  emit('update', '');
}

function handleUrlInput(event) {
  emit('update', event.target.value.trim());
}

function handleImageError() {
  imageError.value = true;
}
</script>

<style scoped>
/* Preview */
.image-preview {
  position: relative;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  overflow: hidden;
  margin-bottom: 8px;
}

.image-preview img {
  display: block;
  width: 100%;
  height: auto;
  max-height: 200px;
  object-fit: contain;
  background: var(--pb-bg);
}

.image-actions {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s;
}

.image-preview:hover .image-actions {
  opacity: 1;
}

.action-btn {
  padding: 6px;
  background: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--pb-bg);
}

.action-btn.danger:hover {
  background: var(--pb-error);
  color: white;
}

.action-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

/* Upload */
.image-upload {
  margin-bottom: 8px;
}

.upload-btn {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 24px;
  background: var(--pb-bg);
  border: 2px dashed var(--pb-border);
  border-radius: var(--pb-radius);
  color: var(--pb-text-muted);
  cursor: pointer;
  transition: all 0.2s;
}

.upload-btn:hover {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
}

.upload-btn .dashicons {
  font-size: 24px;
  width: 24px;
  height: 24px;
}

/* URL input */
.url-input {
  margin-top: 8px;
}

.url-text {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 12px;
  background: var(--pb-bg-light);
}

.url-text:focus {
  outline: none;
  border-color: var(--pb-primary);
}
</style>
