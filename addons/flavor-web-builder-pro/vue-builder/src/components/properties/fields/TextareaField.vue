<template>
  <FieldWrapper :field="field" field-class="textarea-field">
    <textarea
      class="field-textarea"
      :value="value"
      :placeholder="field.placeholder || ''"
      :rows="field.rows || 4"
      :maxlength="field.maxLength"
      @input="handleInput"
    />
  </FieldWrapper>
</template>

<script setup>
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

function handleInput(event) {
  emit('update', event.target.value);
}
</script>
