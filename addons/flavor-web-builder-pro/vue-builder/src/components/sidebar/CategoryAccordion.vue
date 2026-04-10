<template>
  <div class="category-accordion" :class="{ 'is-expanded': isExpanded }">
    <!-- Header -->
    <button class="accordion-header" @click="toggle">
      <span class="category-icon dashicons" :class="category.icon"></span>
      <span class="category-label">{{ category.label }}</span>
      <span class="component-count">{{ components.length }}</span>
      <span class="expand-icon dashicons" :class="isExpanded ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
    </button>

    <!-- Content con lazy loading y virtualización opcional -->
    <div class="accordion-content" v-show="isExpanded">
      <!-- Virtualizar solo si hay muchos componentes (>15) -->
      <VirtualList
        v-if="shouldVirtualize && hasLoaded"
        :items="components"
        :item-height="56"
        :height="virtualListHeight"
        :buffer="2"
        item-key="id"
        class="components-list virtualized"
      >
        <template #default="{ item, index }">
          <ComponentCard
            :component="item"
            @add="handleAdd"
          />
        </template>
      </VirtualList>

      <!-- Lista normal para categorías pequeñas -->
      <div v-else-if="hasLoaded" class="components-list">
        <ComponentCard
          v-for="component in components"
          :key="component.id"
          :component="component"
          @add="handleAdd"
        />
      </div>

      <!-- Skeleton loader mientras carga -->
      <div v-else class="components-skeleton">
        <div v-for="i in Math.min(3, components.length)" :key="i" class="skeleton-card" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ComponentCard from './ComponentCard.vue';
import VirtualList from '../common/VirtualList.vue';

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

// Lazy loading: no renderizar componentes hasta que la categoría se expanda
const hasLoaded = ref(false);

// Virtualizar categorías con más de 15 componentes
const VIRTUALIZATION_THRESHOLD = 15;
const shouldVirtualize = computed(() => props.components.length > VIRTUALIZATION_THRESHOLD);

// Altura dinámica para lista virtual (máximo 5 items visibles)
const virtualListHeight = computed(() => {
  const visibleItems = Math.min(5, props.components.length);
  return `${visibleItems * 56}px`;
});

// Lazy load cuando se expande por primera vez
watch(() => props.isExpanded, (expanded) => {
  if (expanded && !hasLoaded.value) {
    // Pequeño delay para evitar jank durante la animación
    requestAnimationFrame(() => {
      hasLoaded.value = true;
    });
  }
}, { immediate: true });

// Si inicia expandido, cargar inmediatamente
onMounted(() => {
  if (props.isExpanded) {
    hasLoaded.value = true;
  }
});

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

.components-list.virtualized {
  /* Reset grid para VirtualList */
  display: block;
}

/* Skeleton loader */
.components-skeleton {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
}

.skeleton-card {
  height: 50px;
  background: linear-gradient(
    90deg,
    var(--pb-bg) 25%,
    var(--pb-bg-light) 50%,
    var(--pb-bg) 75%
  );
  background-size: 200% 100%;
  border-radius: var(--pb-radius);
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
