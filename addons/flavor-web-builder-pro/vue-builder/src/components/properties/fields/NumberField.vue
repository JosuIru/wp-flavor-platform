<template>
  <FieldWrapper :field="field" field-class="number-field">
    <div class="number-input-wrapper">
      <button class="step-btn" @click="decrement" :disabled="isAtMin">
        <span class="dashicons dashicons-minus"></span>
      </button>
      <input
        type="number"
        class="field-input number-input"
        :value="value"
        :min="field.min"
        :max="field.max"
        :step="field.step || 1"
        @input="handleInput"
      />
      <button class="step-btn" @click="increment" :disabled="isAtMax">
        <span class="dashicons dashicons-plus"></span>
      </button>
      <span v-if="field.unit" class="field-unit">{{ field.unit }}</span>
    </div>
  </FieldWrapper>
</template>

<script setup>
import { computed } from 'vue';
import FieldWrapper from './FieldWrapper.vue';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['update']);

const isAtMin = computed(() =>
  props.field.min !== undefined && props.value <= props.field.min
);

const isAtMax = computed(() =>
  props.field.max !== undefined && props.value >= props.field.max
);

function clampValue(numberValue) {
  if (props.field.min !== undefined) {
    numberValue = Math.max(props.field.min, numberValue);
  }
  if (props.field.max !== undefined) {
    numberValue = Math.min(props.field.max, numberValue);
  }
  return numberValue;
}

function handleInput(event) {
  let numberValue = parseFloat(event.target.value);
  if (isNaN(numberValue)) numberValue = 0;
  emit('update', clampValue(numberValue));
}

function increment() {
  const step = props.field.step || 1;
  emit('update', clampValue((props.value || 0) + step));
}

function decrement() {
  const step = props.field.step || 1;
  emit('update', clampValue((props.value || 0) - step));
}
</script>

<style scoped>
.number-input-wrapper {
  display: flex;
  align-items: center;
  gap: 4px;
}

.number-input {
  flex: 1;
  min-width: 60px;
  text-align: center;
  -moz-appearance: textfield;
}

.number-input::-webkit-inner-spin-button,
.number-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.step-btn {
  padding: 8px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  transition: all 0.2s;
}

.step-btn:hover:not(:disabled) {
  background: var(--pb-border-light);
}

.step-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.step-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.field-unit {
  font-size: 12px;
  color: var(--pb-text-muted);
  margin-left: 4px;
}
</style>
