<template>
  <div class="flavor-pb-components-sidebar">
    <!-- Header con búsqueda -->
    <div class="sidebar-header">
      <h3 class="sidebar-title">Componentes</h3>
      <div class="search-box">
        <span class="dashicons dashicons-search"></span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar componentes..."
          class="search-input"
        />
        <button
          v-if="searchQuery"
          class="clear-search"
          @click="searchQuery = ''"
        >
          <span class="dashicons dashicons-no-alt"></span>
        </button>
      </div>
    </div>

    <!-- Lista de componentes -->
    <div class="sidebar-content">
      <!-- Resultados de búsqueda -->
      <template v-if="searchQuery">
        <div class="search-results">
          <p class="results-count" v-if="filteredComponents.length">
            {{ filteredComponents.length }} componente(s) encontrado(s)
          </p>
          <p class="no-results" v-else>
            No se encontraron componentes
          </p>
          <div class="components-grid">
            <ComponentCard
              v-for="component in filteredComponents"
              :key="component.id"
              :component="component"
              @add="handleAddComponent"
            />
          </div>
        </div>
      </template>

      <!-- Acordeón de categorías -->
      <template v-else>
        <CategoryAccordion
          v-for="category in categories"
          :key="category.id"
          :category="category"
          :components="getComponentsForCategory(category.id)"
          :is-expanded="expandedCategories.includes(category.id)"
          @toggle="toggleCategory(category.id)"
          @add-component="handleAddComponent"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { useComponentDefs } from '../../composables/useComponentDefs';

import CategoryAccordion from './CategoryAccordion.vue';
import ComponentCard from './ComponentCard.vue';

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const { searchComponents, componentsByCategory } = useComponentDefs();

// Local state
const searchQuery = ref('');
const expandedCategories = ref(['structure', 'content', 'media']);

// Computed
const categories = computed(() => {
  // Definir categorías con labels traducidos
  const categoryDefs = [
    { id: 'structure', label: 'Estructura', icon: 'dashicons-layout' },
    { id: 'content', label: 'Contenido', icon: 'dashicons-text' },
    { id: 'media', label: 'Medios', icon: 'dashicons-format-image' },
    { id: 'forms', label: 'Formularios', icon: 'dashicons-feedback' },
    { id: 'widgets', label: 'Widgets', icon: 'dashicons-screenoptions' },
    { id: 'advanced', label: 'Avanzado', icon: 'dashicons-admin-tools' },
    { id: 'general', label: 'General', icon: 'dashicons-admin-generic' },
  ];

  // Solo mostrar categorías que tienen componentes
  return categoryDefs.filter(category =>
    componentsByCategory.value[category.id]?.length > 0
  );
});

const filteredComponents = computed(() => {
  if (!searchQuery.value) return [];
  return searchComponents(searchQuery.value);
});

// Methods
function getComponentsForCategory(categoryId) {
  return componentsByCategory.value[categoryId] || [];
}

function toggleCategory(categoryId) {
  const index = expandedCategories.value.indexOf(categoryId);
  if (index !== -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(categoryId);
  }
}

function handleAddComponent(componentId, variant = null) {
  const componentDef = builderStore.componentDefs[componentId];

  // Si tiene variantes y no se especificó una, abrir modal de variantes
  if (componentDef?.variants && Object.keys(componentDef.variants).length > 0 && !variant) {
    uiStore.openModal('variant', { componentId });
    return;
  }

  // Obtener sección destino
  let targetSectionId = builderStore.selectedSectionId;

  // Si no hay sección seleccionada, usar la primera o crear una
  if (!targetSectionId) {
    if (builderStore.sections.length === 0) {
      targetSectionId = builderStore.addSection();
    } else {
      targetSectionId = builderStore.sections[0].id;
    }
  }

  // Añadir el bloque
  builderStore.addBlock(targetSectionId, componentId, -1, {}, variant);
}
</script>

<style scoped>
.flavor-pb-components-sidebar {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Header */
.sidebar-header {
  padding: 15px;
  border-bottom: 1px solid var(--pb-border-light);
}

.sidebar-title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: var(--pb-text);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box .dashicons-search {
  position: absolute;
  left: 10px;
  color: var(--pb-text-muted);
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.search-input {
  width: 100%;
  padding: 8px 32px 8px 34px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  background: var(--pb-bg-light);
}

.search-input:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 1px var(--pb-primary);
}

.clear-search {
  position: absolute;
  right: 6px;
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--pb-text-muted);
}

.clear-search:hover {
  color: var(--pb-text);
}

/* Content */
.sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 10px 0;
}

/* Search results */
.search-results {
  padding: 0 15px;
}

.results-count {
  margin: 0 0 12px;
  font-size: 12px;
  color: var(--pb-text-muted);
}

.no-results {
  text-align: center;
  padding: 30px;
  color: var(--pb-text-muted);
}

.components-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}
</style>
