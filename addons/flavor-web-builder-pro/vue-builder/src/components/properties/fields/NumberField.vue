<template>
  <div class="field number-field">
    <label class="field-label">
      {{ field.label || field.key }}
      <span v-if="field.required" class="required">*</span>
    </label>
    <div class="number-input-wrapper">
      <button class="step-btn" @click="decrement" :disabled="isAtMin">
        <span class="dashicons dashicons-minus"></span>
      </button>
      <input
        type="number"
        class="field-input"
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
    <p v-if="field.description" class="field-description">
      {{ field.description }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

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

function handleInput(event) {
  let numberValue = parseFloat(event.target.value);
  if (isNaN(numberValue)) numberValue = 0;

  // Aplicar límites
  if (props.field.min !== undefined) {
    numberValue = Math.max(props.field.min, numberValue);
  }
  if (props.field.max !== undefined) {
    numberValue = Math.min(props.field.max, numberValue);
  }

  emit('update', numberValue);
}

function increment() {
  const step = props.field.step || 1;
  let newValue = (props.value || 0) + step;

  if (props.field.max !== undefined) {
    newValue = Math.min(props.field.max, newValue);
  }

  emit('update', newValue);
}

function decrement() {
  const step = props.field.step || 1;
  let newValue = (props.value || 0) - step;

  if (props.field.min !== undefined) {
    newValue = Math.max(props.field.min, newValue);
  }

  emit('update', newValue);
}
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--pb-text);
}

.required {
  color: var(--pb-error);
}

.number-input-wrapper {
  display: flex;
  align-items: center;
  gap: 4px;
}

.field-input {
  flex: 1;
  min-width: 60px;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  text-align: center;
  background: var(--pb-bg-light);
  -moz-appearance: textfield;
}

.field-input::-webkit-inner-spin-button,
.field-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.field-input:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
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

.field-description {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
