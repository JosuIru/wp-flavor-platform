<template>
  <div class="category-accordion" :class="{ 'is-expanded': isExpanded }">
    <!-- Header -->
    <button class="accordion-header" @click="toggle">
      <span class="category-icon dashicons" :class="category.icon"></span>
      <span class="category-label">{{ category.label }}</span>
      <span class="component-count">{{ components.length }}</span>
      <span class="expand-icon dashicons" :class="isExpanded ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
    </button>

    <!-- Content -->
    <div class="accordion-content" v-show="isExpanded">
      <div class="components-list">
        <ComponentCard
          v-for="component in components"
          :key="component.id"
          :component="component"
          @add="handleAdd"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import ComponentCard from './ComponentCard.vue';

const props = defineProps({
  category: {
    type: Object,
    required: true,
  },
  components: {
    type: Array,
    required: true,
  },
  isExpanded: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle', 'add-component']);

function toggle() {
  emit('toggle');
}

function handleAdd(componentId, variant) {
  emit('add-component', componentId, variant);
}
</script>

<style scoped>
.category-accordion {
  border-bottom: 1px solid var(--pb-border-light);
}

.category-accordion:last-child {
  border-bottom: none;
}

/* Header */
.accordion-header {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 15px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.2s;
}

.accordion-header:hover {
  background: var(--pb-bg);
}

.category-icon {
  font-size: 16px;
  width: 16px;
  height: 16px;
  color: var(--pb-text-muted);
}

.category-label {
  flex: 1;
  font-size: 13px;
  font-weight: 500;
  color: var(--pb-text);
}

.component-count {
  padding: 2px 8px;
  background: var(--pb-bg);
  color: var(--pb-text-muted);
  font-size: 11px;
  border-radius: 10px;
}

.expand-icon {
  font-size: 14px;
  width: 14px;
  height: 14px;
  color: var(--pb-text-muted);
  transition: transform 0.2s;
}

/* Content */
.accordion-content {
  padding: 5px 15px 15px;
}

.components-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}
</style>
