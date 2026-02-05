<template>
  <div class="field-group" :class="{ 'is-expanded': isExpanded }">
    <!-- Group header -->
    <button class="group-header" @click="toggle">
      <span class="group-name">{{ groupLabel }}</span>
      <span class="group-count">{{ visibleFields.length }}</span>
      <span class="expand-icon dashicons" :class="isExpanded ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
    </button>

    <!-- Group content -->
    <div class="group-content" v-show="isExpanded">
      <template v-for="field in visibleFields" :key="field.key">
        <FieldRenderer
          :field="field"
          :value="values[field.key]"
          @update="handleUpdate(field.key, $event)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useComponentDefs } from '../../composables/useComponentDefs';

import FieldRenderer from './FieldRenderer.vue';

const props = defineProps({
  groupName: {
    type: String,
    required: true,
  },
  fields: {
    type: Array,
    required: true,
  },
  values: {
    type: Object,
    required: true,
  },
  isExpanded: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['toggle', 'update']);

const { shouldShowField } = useComponentDefs();

// Computed
const groupLabel = computed(() => {
  const labels = {
    general: 'General',
    content: 'Contenido',
    style: 'Estilo',
    layout: 'Diseño',
    advanced: 'Avanzado',
    responsive: 'Responsive',
  };
  return labels[props.groupName] || props.groupName;
});

const visibleFields = computed(() =>
  props.fields.filter(field => shouldShowField(field, props.values))
);

// Methods
function toggle() {
  emit('toggle');
}

function handleUpdate(fieldKey, value) {
  emit('update', fieldKey, value);
}
</script>

<style scoped>
.field-group {
  border: 1px solid var(--pb-border-light);
  border-radius: var(--pb-radius);
  margin-bottom: 10px;
  overflow: hidden;
}

.field-group:last-child {
  margin-bottom: 0;
}

/* Header */
.group-header {
  width: 100%;
  display: flex;
  align-items: center;
  padding: 10px 12px;
  background: var(--pb-bg);
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.2s;
}

.group-header:hover {
  background: var(--pb-border-light);
}

.group-name {
  flex: 1;
  font-size: 12px;
  font-weight: 600;
  color: var(--pb-text);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.group-count {
  padding: 2px 6px;
  background: var(--pb-bg-light);
  font-size: 10px;
  color: var(--pb-text-muted);
  border-radius: 10px;
  margin-right: 8px;
}

.expand-icon {
  font-size: 14px;
  width: 14px;
  height: 14px;
  color: var(--pb-text-muted);
}

/* Content */
.group-content {
  padding: 12px;
  background: var(--pb-bg-light);
}
</style>
