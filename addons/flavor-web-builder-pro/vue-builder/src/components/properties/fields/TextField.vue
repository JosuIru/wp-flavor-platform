<template>
  <FieldWrapper :field="field" field-class="text-field">
    <input
      type="text"
      class="field-input"
      :value="value"
      :placeholder="field.placeholder || ''"
      :maxlength="field.maxLength"
      @input="handleInput"
      @blur="handleBlur"
    />
  </FieldWrapper>
</template>

<script setup>
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
