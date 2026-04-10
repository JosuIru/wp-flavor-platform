<template>
  <div class="field" :class="fieldClass">
    <label v-if="showLabel" class="field-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
      <span v-if="tooltip" class="field-tooltip" :title="tooltip">
        <span class="dashicons dashicons-info"></span>
      </span>
    </label>

    <slot />

    <p v-if="description" class="field-description">
      {{ description }}
    </p>

    <p v-if="error" class="field-error">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /**
   * Definición del campo desde componentDef
   */
  field: {
    type: Object,
    required: true,
  },
  /**
   * Clase CSS adicional para el wrapper
   */
  fieldClass: {
    type: String,
    default: '',
  },
  /**
   * Mostrar label (puede ocultarse para inline editing)
   */
  showLabel: {
    type: Boolean,
    default: true,
  },
  /**
   * Error de validación
   */
  error: {
    type: String,
    default: '',
  },
});

const label = computed(() => props.field.label || props.field.key);
const required = computed(() => props.field.required);
const description = computed(() => props.field.description);
const tooltip = computed(() => props.field.tooltip);
</script>

<style scoped>
.field {
  margin-bottom: 16px;
}

.field:last-child {
  margin-bottom: 0;
}

.field-label {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--pb-text);
}

.required {
  color: var(--pb-error);
}

.field-tooltip {
  cursor: help;
  color: var(--pb-text-muted);
}

.field-tooltip .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.field-description {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
  line-height: 1.4;
}

.field-error {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-error);
  line-height: 1.4;
}

/* Input común styles - heredados por slots */
:deep(.field-input),
:deep(.field-textarea),
:deep(.field-select) {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  background: var(--pb-bg-light);
  transition: border-color 0.2s, box-shadow 0.2s;
}

:deep(.field-input:focus),
:deep(.field-textarea:focus),
:deep(.field-select:focus) {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
}

:deep(.field-input.has-error),
:deep(.field-textarea.has-error),
:deep(.field-select.has-error) {
  border-color: var(--pb-error);
}

:deep(.field-textarea) {
  font-family: inherit;
  resize: vertical;
  min-height: 80px;
}
</style>
