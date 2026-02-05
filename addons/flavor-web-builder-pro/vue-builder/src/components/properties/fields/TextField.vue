<template>
  <div class="field text-field">
    <label class="field-label">
      {{ field.label || field.key }}
      <span v-if="field.required" class="required">*</span>
    </label>
    <input
      type="text"
      class="field-input"
      :value="value"
      :placeholder="field.placeholder || ''"
      :maxlength="field.maxLength"
      @input="handleInput"
      @blur="handleBlur"
    />
    <p v-if="field.description" class="field-description">
      {{ field.description }}
    </p>
  </div>
</template>

<script setup>
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

function handleInput(event) {
  emit('update', event.target.value);
}

function handleBlur(event) {
  // Trim en blur si está configurado
  if (props.field.trim !== false) {
    const trimmed = event.target.value.trim();
    if (trimmed !== event.target.value) {
      emit('update', trimmed);
    }
  }
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

.field-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  background: var(--pb-bg-light);
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field-input:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
}

.field-description {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
