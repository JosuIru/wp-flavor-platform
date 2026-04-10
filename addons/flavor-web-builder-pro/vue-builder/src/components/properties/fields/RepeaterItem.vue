<template>
  <div
    class="repeater-item"
    :class="{ 'is-collapsed': isCollapsed }"
  >
    <!-- Item header -->
    <div class="item-header" @click="$emit('toggle', index)">
      <span class="item-handle" draggable="true">
        <span class="dashicons dashicons-move"></span>
      </span>
      <span class="item-title">{{ itemTitle }}</span>
      <div class="item-actions">
        <button
          class="item-btn"
          @click.stop="$emit('duplicate', index)"
          title="Duplicar"
          :disabled="isAtMax"
        >
          <span class="dashicons dashicons-admin-page"></span>
        </button>
        <button
          class="item-btn danger"
          @click.stop="$emit('remove', index)"
          title="Eliminar"
        >
          <span class="dashicons dashicons-trash"></span>
        </button>
        <span
          class="expand-icon dashicons"
          :class="isCollapsed ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'"
        ></span>
      </div>
    </div>

    <!-- Item fields (lazy loaded) -->
    <div v-if="!isCollapsed" class="item-content">
      <template v-if="hasLoaded">
        <template v-for="subField in subFields" :key="subField.key">
          <FieldRenderer
            :field="subField"
            :value="item[subField.key]"
            @update="handleFieldUpdate(subField.key, $event)"
          />
        </template>
      </template>
      <div v-else class="item-loading">
        <span class="dashicons dashicons-update spin"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import FieldRenderer from '../FieldRenderer.vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  itemId: {
    type: [String, Number],
    required: true,
  },
  subFields: {
    type: Array,
    default: () => [],
  },
  isCollapsed: {
    type: Boolean,
    default: false,
  },
  isAtMax: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle', 'duplicate', 'remove', 'update-field']);

// Lazy loading de contenido
const hasLoaded = ref(false);

// Cargar contenido cuando se expande
watch(() => props.isCollapsed, (collapsed) => {
  if (!collapsed && !hasLoaded.value) {
    // Pequeño delay para no bloquear la animación
    requestAnimationFrame(() => {
      hasLoaded.value = true;
    });
  }
}, { immediate: true });

// Computed
const itemTitle = computed(() => {
  // Intentar usar el primer campo de texto como título
  const firstTextField = props.subFields.find(f => f.type === 'text');
  if (firstTextField && props.item[firstTextField.key]) {
    const title = props.item[firstTextField.key];
    return title.length > 30 ? title.substring(0, 30) + '...' : title;
  }
  return `Item ${props.index + 1}`;
});

// Methods
function handleFieldUpdate(fieldKey, value) {
  emit('update-field', props.index, fieldKey, value);
}
</script>

<style scoped>
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

.item-btn:hover:not(:disabled) {
  background: var(--pb-bg-light);
  color: var(--pb-text);
}

.item-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.item-btn.danger:hover:not(:disabled) {
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
  transition: transform 0.2s;
}

/* Item content */
.item-content {
  padding: 12px;
  background: var(--pb-bg-light);
  border-top: 1px solid var(--pb-border-light);
}

.item-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  color: var(--pb-text-muted);
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
