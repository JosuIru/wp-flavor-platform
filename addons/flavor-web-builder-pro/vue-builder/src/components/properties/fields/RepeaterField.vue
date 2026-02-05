<template>
  <div class="field repeater-field">
    <label class="field-label">
      {{ field.label || field.key }}
    </label>

    <!-- Items -->
    <div class="repeater-items">
      <div
        v-for="(item, index) in items"
        :key="item._id || index"
        class="repeater-item"
        :class="{ 'is-collapsed': collapsedItems.includes(index) }"
      >
        <!-- Item header -->
        <div class="item-header" @click="toggleItem(index)">
          <span class="item-handle" draggable="true">
            <span class="dashicons dashicons-move"></span>
          </span>
          <span class="item-title">{{ getItemTitle(item, index) }}</span>
          <div class="item-actions">
            <button class="item-btn" @click.stop="duplicateItem(index)" title="Duplicar">
              <span class="dashicons dashicons-admin-page"></span>
            </button>
            <button class="item-btn danger" @click.stop="removeItem(index)" title="Eliminar">
              <span class="dashicons dashicons-trash"></span>
            </button>
            <span class="expand-icon dashicons" :class="collapsedItems.includes(index) ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'"></span>
          </div>
        </div>

        <!-- Item fields -->
        <div class="item-content" v-show="!collapsedItems.includes(index)">
          <template v-for="subField in subFields" :key="subField.key">
            <FieldRenderer
              :field="subField"
              :value="item[subField.key]"
              @update="updateItemField(index, subField.key, $event)"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- Add button -->
    <button
      class="add-item-btn"
      @click="addItem"
      :disabled="isAtMax"
    >
      <span class="dashicons dashicons-plus-alt"></span>
      {{ field.addLabel || 'Añadir item' }}
    </button>

    <p v-if="field.description" class="field-description">
      {{ field.description }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import FieldRenderer from '../FieldRenderer.vue';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update']);

const collapsedItems = ref([]);

// Computed
const items = computed(() => props.value || []);

const subFields = computed(() => {
  if (!props.field.fields) return [];
  return Object.entries(props.field.fields).map(([key, field]) => ({
    key,
    ...field,
  }));
});

const isAtMax = computed(() =>
  props.field.max && items.value.length >= props.field.max
);

// Methods
function addItem() {
  if (isAtMax.value) return;

  // Crear nuevo item con valores por defecto
  const newItem = { _id: generateId() };

  subFields.value.forEach(subField => {
    newItem[subField.key] = subField.default ?? '';
  });

  emit('update', [...items.value, newItem]);
}

function removeItem(index) {
  const newItems = [...items.value];
  newItems.splice(index, 1);
  emit('update', newItems);

  // Actualizar collapsed items
  collapsedItems.value = collapsedItems.value
    .filter(i => i !== index)
    .map(i => i > index ? i - 1 : i);
}

function duplicateItem(index) {
  if (isAtMax.value) return;

  const itemToDuplicate = items.value[index];
  const newItem = {
    ...JSON.parse(JSON.stringify(itemToDuplicate)),
    _id: generateId(),
  };

  const newItems = [...items.value];
  newItems.splice(index + 1, 0, newItem);
  emit('update', newItems);
}

function updateItemField(itemIndex, fieldKey, value) {
  const newItems = [...items.value];
  newItems[itemIndex] = {
    ...newItems[itemIndex],
    [fieldKey]: value,
  };
  emit('update', newItems);
}

function toggleItem(index) {
  const idx = collapsedItems.value.indexOf(index);
  if (idx !== -1) {
    collapsedItems.value.splice(idx, 1);
  } else {
    collapsedItems.value.push(index);
  }
}

function getItemTitle(item, index) {
  // Intentar usar el primer campo de texto como título
  const firstTextField = subFields.value.find(f => f.type === 'text');
  if (firstTextField && item[firstTextField.key]) {
    const title = item[firstTextField.key];
    return title.length > 30 ? title.substring(0, 30) + '...' : title;
  }
  return `Item ${index + 1}`;
}

function generateId() {
  return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
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

/* Items */
.repeater-items {
  margin-bottom: 10px;
}

.repeater-item {
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  margin-bottom: 8px;
  overflow: hidden;
}

.repeater-item:last-child {
  margin-bottom: 0;
}

/* Item header */
.item-header {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  background: var(--pb-bg);
  cursor: pointer;
  transition: background 0.2s;
}

.item-header:hover {
  background: var(--pb-border-light);
}

.item-handle {
  cursor: grab;
  padding: 2px 6px 2px 0;
  color: var(--pb-text-muted);
}

.item-title {
  flex: 1;
  font-size: 12px;
  font-weight: 500;
  color: var(--pb-text);
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.item-btn {
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 3px;
  color: var(--pb-text-muted);
  opacity: 0;
  transition: all 0.2s;
}

.item-header:hover .item-btn {
  opacity: 1;
}

.item-btn:hover {
  background: var(--pb-bg-light);
  color: var(--pb-text);
}

.item-btn.danger:hover {
  color: var(--pb-error);
}

.item-btn .dashicons {
  font-size: 12px;
  width: 12px;
  height: 12px;
}

.expand-icon {
  font-size: 14px;
  width: 14px;
  height: 14px;
  color: var(--pb-text-muted);
}

/* Item content */
.item-content {
  padding: 12px;
  background: var(--pb-bg-light);
  border-top: 1px solid var(--pb-border-light);
}

/* Add button */
.add-item-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px;
  background: transparent;
  border: 1px dashed var(--pb-border);
  border-radius: var(--pb-radius);
  color: var(--pb-text-light);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.add-item-btn:hover:not(:disabled) {
  border-color: var(--pb-primary);
  color: var(--pb-primary);
  background: rgba(0, 115, 170, 0.05);
}

.add-item-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.add-item-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.field-description {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
