<template>
  <div class="field-wrapper">
    <component
      :is="fieldComponent"
      :field="field"
      :value="value"
      @update="handleUpdate"
    />
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';

// Importar campos de forma lazy para mejor performance
const TextField = defineAsyncComponent(() => import('./fields/TextField.vue'));
const TextareaField = defineAsyncComponent(() => import('./fields/TextareaField.vue'));
const NumberField = defineAsyncComponent(() => import('./fields/NumberField.vue'));
const ToggleField = defineAsyncComponent(() => import('./fields/ToggleField.vue'));
const ColorField = defineAsyncComponent(() => import('./fields/ColorField.vue'));
const ImageField = defineAsyncComponent(() => import('./fields/ImageField.vue'));
const SelectField = defineAsyncComponent(() => import('./fields/SelectField.vue'));
const IconField = defineAsyncComponent(() => import('./fields/IconField.vue'));
const RepeaterField = defineAsyncComponent(() => import('./fields/RepeaterField.vue'));
const VariantSelector = defineAsyncComponent(() => import('./fields/VariantSelector.vue'));

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: [String, Number, Boolean, Array, Object],
    default: null,
  },
});

const emit = defineEmits(['update']);

// Mapeo de tipos de campo a componentes
const fieldComponents = {
  text: TextField,
  textarea: TextareaField,
  number: NumberField,
  toggle: ToggleField,
  color: ColorField,
  image: ImageField,
  select: SelectField,
  icon: IconField,
  repeater: RepeaterField,
  variant: VariantSelector,
};

const fieldComponent = computed(() => {
  return fieldComponents[props.field.type] || TextField;
});

function handleUpdate(value) {
  emit('update', value);
}
</script>

<style scoped>
.field-wrapper {
  margin-bottom: 12px;
}

.field-wrapper:last-child {
  margin-bottom: 0;
}
</style>
