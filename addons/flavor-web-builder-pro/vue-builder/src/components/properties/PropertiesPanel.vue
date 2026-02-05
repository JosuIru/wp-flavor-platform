<template>
  <div class="flavor-pb-properties-panel">
    <!-- Header -->
    <div class="panel-header">
      <h3 class="panel-title">Propiedades</h3>
    </div>

    <!-- Content -->
    <div class="panel-content">
      <!-- No block selected -->
      <div v-if="!selectedBlock" class="panel-empty">
        <span class="dashicons dashicons-edit"></span>
        <p>Selecciona un bloque para editar sus propiedades</p>
      </div>

      <!-- Block properties -->
      <div v-else class="block-properties">
        <!-- Block info -->
        <div class="block-info">
          <span class="block-icon dashicons" :class="componentIcon"></span>
          <div class="block-details">
            <span class="block-name">{{ componentLabel }}</span>
            <span class="block-variant" v-if="selectedBlock.variant">
              {{ variantLabel }}
            </span>
          </div>
        </div>

        <!-- Fields -->
        <div class="fields-container">
          <FieldGroup
            v-for="(fields, groupName) in fieldsByGroup"
            :key="groupName"
            :group-name="groupName"
            :fields="fields"
            :values="selectedBlock.values"
            :is-expanded="isGroupExpanded(groupName)"
            @toggle="toggleGroup(groupName)"
            @update="handleFieldUpdate"
          />
        </div>

        <!-- Actions -->
        <div class="block-actions">
          <button class="action-btn" @click="duplicateBlock">
            <span class="dashicons dashicons-admin-page"></span>
            Duplicar
          </button>
          <button class="action-btn danger" @click="removeBlock">
            <span class="dashicons dashicons-trash"></span>
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { useComponentDefs } from '../../composables/useComponentDefs';
import { useLayout } from '../../composables/useLayout';

import FieldGroup from './FieldGroup.vue';

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const { getComponentDef, getFieldsByGroup } = useComponentDefs();
const { duplicateBlock: duplicate, removeBlock: remove } = useLayout();

// Computed
const selectedBlock = computed(() => builderStore.selectedBlock);

const componentDef = computed(() => {
  if (!selectedBlock.value) return null;
  return getComponentDef(selectedBlock.value.componentId);
});

const componentLabel = computed(() =>
  componentDef.value?.label || selectedBlock.value?.componentId || ''
);

const componentIcon = computed(() =>
  componentDef.value?.icon || 'dashicons-layout'
);

const variantLabel = computed(() => {
  if (!selectedBlock.value?.variant || !componentDef.value?.variants) return '';
  const variant = componentDef.value.variants[selectedBlock.value.variant];
  return variant?.label || selectedBlock.value.variant;
});

const fieldsByGroup = computed(() => {
  if (!selectedBlock.value) return {};
  return getFieldsByGroup(selectedBlock.value.componentId);
});

// Methods
function isGroupExpanded(groupName) {
  return uiStore.isPropertyGroupExpanded(groupName);
}

function toggleGroup(groupName) {
  uiStore.togglePropertyGroup(groupName);
}

function handleFieldUpdate(fieldKey, value) {
  if (selectedBlock.value) {
    builderStore.updateBlock(selectedBlock.value.id, fieldKey, value);
  }
}

function duplicateBlock() {
  if (selectedBlock.value) {
    duplicate(selectedBlock.value.id);
  }
}

function removeBlock() {
  if (selectedBlock.value) {
    remove(selectedBlock.value.id);
  }
}
</script>

<style scoped>
.flavor-pb-properties-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Header */
.panel-header {
  padding: 15px;
  border-bottom: 1px solid var(--pb-border-light);
}

.panel-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--pb-text);
}

/* Content */
.panel-content {
  flex: 1;
  overflow-y: auto;
}

/* Empty state */
.panel-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.panel-empty .dashicons {
  font-size: 32px;
  width: 32px;
  height: 32px;
  color: var(--pb-text-muted);
  margin-bottom: 12px;
}

.panel-empty p {
  margin: 0;
  font-size: 13px;
  color: var(--pb-text-muted);
}

/* Block properties */
.block-properties {
  padding: 15px;
}

/* Block info */
.block-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--pb-bg);
  border-radius: var(--pb-radius);
  margin-bottom: 15px;
}

.block-icon {
  font-size: 24px;
  width: 24px;
  height: 24px;
  color: var(--pb-primary);
}

.block-details {
  flex: 1;
}

.block-name {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: var(--pb-text);
}

.block-variant {
  display: block;
  font-size: 11px;
  color: var(--pb-text-muted);
  margin-top: 2px;
}

/* Fields container */
.fields-container {
  margin-bottom: 15px;
}

/* Block actions */
.block-actions {
  display: flex;
  gap: 8px;
  padding-top: 15px;
  border-top: 1px solid var(--pb-border-light);
}

.action-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 12px;
  color: var(--pb-text);
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--pb-border-light);
}

.action-btn.danger:hover {
  background: var(--pb-error);
  border-color: var(--pb-error);
  color: white;
}

.action-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}
</style>
