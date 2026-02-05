<template>
  <div class="field variant-selector-field">
    <label class="field-label">
      {{ field.label || 'Variante' }}
    </label>
    <div class="variants-list">
      <button
        v-for="variant in variants"
        :key="variant.key"
        class="variant-btn"
        :class="{ 'is-selected': value === variant.key }"
        @click="selectVariant(variant.key)"
      >
        <span class="variant-name">{{ variant.label }}</span>
      </button>
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
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update']);

const variants = computed(() => {
  if (!props.field.options) return [];

  return props.field.options.map(option => {
    if (typeof option === 'object') {
      return {
        key: option.value || option.key,
        label: option.label || option.value,
      };
    }
    return {
      key: option,
      label: option,
    };
  });
});

function selectVariant(variantKey) {
  emit('update', variantKey);
}
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 500;
  color: var(--pb-text);
}

.variants-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.variant-btn {
  padding: 8px 14px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 12px;
  color: var(--pb-text);
  cursor: pointer;
  transition: all 0.2s;
}

.variant-btn:hover {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
}

.variant-btn.is-selected {
  background: var(--pb-primary);
  border-color: var(--pb-primary);
  color: white;
}

.field-description {
  margin: 8px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
