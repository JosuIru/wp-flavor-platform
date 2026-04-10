<template>
  <FieldWrapper :field="field" field-class="repeater-field">
    <!-- Items con virtualización para listas largas -->
    <div class="repeater-items" ref="itemsContainer">
      <template v-if="shouldVirtualize">
        <VirtualList
          :items="itemsWithIndex"
          :item-height="collapsedItemHeight"
          :visible-count="10"
          class="repeater-virtual-list"
        >
          <template #item="{ item }">
            <RepeaterItem
              :item="item.data"
              :index="item.index"
              :item-id="item.data._id || item.index"
              :sub-fields="subFields"
              :is-collapsed="collapsedItems.has(item.index)"
              :is-at-max="isAtMax"
              @toggle="toggleItem"
              @duplicate="duplicateItem"
              @remove="removeItem"
              @update-field="updateItemField"
            />
          </template>
        </VirtualList>
      </template>

      <template v-else>
        <RepeaterItem
          v-for="(item, index) in items"
          :key="item._id || index"
          :item="item"
          :index="index"
          :item-id="item._id || index"
          :sub-fields="subFields"
          :is-collapsed="collapsedItems.has(index)"
          :is-at-max="isAtMax"
          @toggle="toggleItem"
          @duplicate="duplicateItem"
          @remove="removeItem"
          @update-field="updateItemField"
        />
      </template>
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

    <!-- Stats for long lists -->
    <p v-if="items.length > 5" class="repeater-stats">
      {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
      <template v-if="field.max"> / {{ field.max }} máx.</template>
    </p>
  </FieldWrapper>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import FieldWrapper from './FieldWrapper.vue';
import RepeaterItem from './RepeaterItem.vue';

// Lazy load VirtualList solo si se necesita
const VirtualList = defineAsyncComponent(() =>
  import('../../common/VirtualList.vue')
);

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

// Umbral para usar virtualización
const VIRTUALIZATION_THRESHOLD = 15;
const collapsedItemHeight = 40; // Altura de item colapsado

// State
const collapsedItems = ref(new Set());

// Computed
const items = computed(() => props.value || []);

// Items con índice para virtualización
const itemsWithIndex = computed(() =>
  items.value.map((data, index) => ({ data, index }))
);

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

const shouldVirtualize = computed(() =>
  items.value.length > VIRTUALIZATION_THRESHOLD
);

// Methods
function addItem() {
  if (isAtMax.value) return;

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
  const newCollapsed = new Set();
  for (const i of collapsedItems.value) {
    if (i < index) {
      newCollapsed.add(i);
    } else if (i > index) {
      newCollapsed.add(i - 1);
    }
  }
  collapsedItems.value = newCollapsed;
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
  const newCollapsed = new Set(collapsedItems.value);
  if (newCollapsed.has(index)) {
    newCollapsed.delete(index);
  } else {
    newCollapsed.add(index);
  }
  collapsedItems.value = newCollapsed;
}

function generateId() {
  return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}
</script>

<style scoped>
/* Items container */
.repeater-items {
  margin-bottom: 10px;
}

.repeater-virtual-list {
  max-height: 400px;
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

/* Stats */
.repeater-stats {
  margin: 8px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
  text-align: center;
}
</style>
