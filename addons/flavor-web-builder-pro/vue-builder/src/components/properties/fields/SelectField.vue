<template>
  <FieldWrapper :field="field" field-class="select-field">
    <select
      class="field-select"
      :value="value"
      @change="handleChange"
    >
      <option v-if="field.placeholder" value="" disabled>
        {{ field.placeholder }}
      </option>
      <option
        v-for="option in normalizedOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
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
    type: [String, Number],
    default: '',
  },
});

const emit = defineEmits(['update']);

const normalizedOptions = computed(() => {
  if (!props.field.options) return [];

  return props.field.options.map(option => {
    if (typeof option === 'object') {
      return {
        value: option.value,
        label: option.label || option.value,
      };
    }
    return {
      value: option,
      label: option,
    };
  });
});

function handleChange(event) {
  emit('update', event.target.value);
}
</script>

<style scoped>
.field-select {
  width: 100%;
  padding: 8px 32px 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  background: var(--pb-bg-light);
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2350575e' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
}

.field-select:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
}
</style>
