<template>
  <FieldWrapper :field="field" field-class="color-field">
    <div class="color-input-wrapper">
      <div
        class="color-preview"
        :style="{ backgroundColor: value || 'transparent' }"
        @click="togglePicker"
      >
        <span v-if="!value" class="no-color">
          <span class="dashicons dashicons-no-alt"></span>
        </span>
      </div>
      <input
        type="text"
        class="color-text"
        :value="value"
        placeholder="#000000"
        @input="handleTextInput"
      />
      <button v-if="value" class="clear-btn" @click="clearColor" title="Limpiar">
        <span class="dashicons dashicons-no-alt"></span>
      </button>
    </div>

    <!-- Color picker nativo (oculto) -->
    <input
      ref="colorPicker"
      type="color"
      class="native-picker"
      :value="value || '#000000'"
      @input="handlePickerInput"
    />
  </FieldWrapper>
</template>

<script setup>
import { ref } from 'vue';
import FieldWrapper from './FieldWrapper.vue';

defineProps({
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

const colorPicker = ref(null);

function togglePicker() {
  colorPicker.value?.click();
}

function handleTextInput(event) {
  let colorValue = event.target.value.trim();

  // Añadir # si falta
  if (colorValue && !colorValue.startsWith('#') && /^[0-9A-Fa-f]+$/.test(colorValue)) {
    colorValue = '#' + colorValue;
  }

  emit('update', colorValue);
}

function handlePickerInput(event) {
  emit('update', event.target.value);
}

function clearColor() {
  emit('update', '');
}
</script>

<style scoped>
.color-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-preview {
  width: 36px;
  height: 36px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  background-image: linear-gradient(45deg, #ccc 25%, transparent 25%),
    linear-gradient(-45deg, #ccc 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #ccc 75%),
    linear-gradient(-45deg, transparent 75%, #ccc 75%);
  background-size: 8px 8px;
  background-position: 0 0, 0 4px, 4px -4px, -4px 0px;
}

.color-preview::after {
  content: '';
  position: absolute;
  inset: 0;
  background: inherit;
}

.no-color {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  z-index: 1;
}

.no-color .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
  color: var(--pb-text-muted);
}

.color-text {
  flex: 1;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  font-family: monospace;
  background: var(--pb-bg-light);
}

.color-text:focus {
  outline: none;
  border-color: var(--pb-primary);
}

.clear-btn {
  padding: 6px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--pb-text-muted);
  border-radius: 3px;
}

.clear-btn:hover {
  background: var(--pb-bg);
  color: var(--pb-error);
}

.native-picker {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  pointer-events: none;
}
</style>
