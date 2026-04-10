<template>
  <Modal
    title="Plantillas de Ejemplo"
    size="large"
    @close="$emit('close')"
  >
    <div class="templates-content">
      <!-- Filtros -->
      <div class="templates-filters">
        <div class="search-box">
          <span class="dashicons dashicons-search"></span>
          <input
            ref="searchInput"
            type="text"
            v-model="searchQuery"
            placeholder="Buscar plantilla..."
            class="search-input"
          />
        </div>
        <select v-model="selectedCategory" class="category-filter">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.label }}
          </option>
        </select>
      </div>

      <!-- Grid de plantillas -->
      <div class="templates-grid" v-if="filteredTemplates.length > 0">
        <div
          v-for="template in filteredTemplates"
          :key="template.id"
          class="template-card"
          :class="{ 'is-selected': selectedTemplate === template.id }"
          @click="selectTemplate(template.id)"
        >
          <div class="template-preview">
            <img
              v-if="template.thumbnail"
              :src="template.thumbnail"
              :alt="template.name"
              loading="lazy"
            />
            <span v-else class="dashicons" :class="template.icon || 'dashicons-layout'"></span>
          </div>
          <div class="template-info">
            <h4>{{ template.name || 'Sin nombre' }}</h4>
            <p v-if="template.description" class="template-description">{{ template.description }}</p>
            <span v-if="template.categoryLabel" class="template-category">{{ template.categoryLabel }}</span>
          </div>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-else class="templates-empty">
        <span class="dashicons dashicons-portfolio"></span>
        <p>No se encontraron plantillas</p>
      </div>
    </div>

    <template #footer>
      <button class="button" @click="$emit('close')">Cancelar</button>
      <button
        class="button button-primary"
        :disabled="!selectedTemplate"
        @click="loadTemplate"
      >
        Cargar Plantilla
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import Modal from '../ui/Modal.vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';

const emit = defineEmits(['close', 'load']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();

// State
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedTemplate = ref(null);
const searchInput = ref(null);

// Focus search on mount
onMounted(async () => {
  await nextTick();
  searchInput.value?.focus();
});

// Transformar plantillas de la config de WordPress a array plano
const templates = computed(() => {
  const wpConfig = window.flavorPageBuilder || {};
  const templatesConfig = wpConfig.templates;

  if (!templatesConfig || typeof templatesConfig !== 'object') return [];
  if (Array.isArray(templatesConfig)) return templatesConfig;

  const flatTemplates = [];

  for (const [categoryKey, categoryData] of Object.entries(templatesConfig)) {
    if (categoryData?.templates && typeof categoryData.templates === 'object') {
      // Estructura anidada con categorías
      const categoryLabel = categoryData.label || categoryKey;

      for (const [templateKey, templateData] of Object.entries(categoryData.templates)) {
        flatTemplates.push({
          id: templateKey,
          name: templateData.name || templateKey,
          description: templateData.description || '',
          category: categoryKey,
          categoryLabel,
          thumbnail: templateData.preview || templateData.thumbnail || '',
          icon: templateData.icon || 'dashicons-layout',
          layout: templateData.layout || [],
        });
      }
    } else if (categoryData?.name) {
      // Estructura plana
      flatTemplates.push({
        id: categoryKey,
        name: categoryData.name || categoryKey,
        description: categoryData.description || '',
        category: categoryData.category || 'general',
        thumbnail: categoryData.preview || categoryData.thumbnail || '',
        icon: categoryData.icon || 'dashicons-layout',
        layout: categoryData.layout || [],
      });
    }
  }

  return flatTemplates;
});

const categories = computed(() => {
  const categoriesMap = new Map();

  for (const template of templates.value) {
    if (template.category && !categoriesMap.has(template.category)) {
      categoriesMap.set(template.category, {
        id: template.category,
        label: template.categoryLabel || formatCategoryLabel(template.category),
      });
    }
  }

  return Array.from(categoriesMap.values());
});

const filteredTemplates = computed(() => {
  let result = templates.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(template =>
      template.name?.toLowerCase().includes(query) ||
      template.description?.toLowerCase().includes(query)
    );
  }

  if (selectedCategory.value) {
    result = result.filter(template => template.category === selectedCategory.value);
  }

  return result;
});

function formatCategoryLabel(category) {
  return category.charAt(0).toUpperCase() + category.slice(1).replace(/[-_]/g, ' ');
}

function selectTemplate(templateId) {
  selectedTemplate.value = templateId;
}

function loadTemplate() {
  if (!selectedTemplate.value) return;

  const template = templates.value.find(t => t.id === selectedTemplate.value);
  if (!template) return;

  // Confirmar si hay contenido existente
  const hasContent = builderStore.sections.some(s => s.blocks.length > 0);

  if (hasContent) {
    uiStore.confirm(
      '¿Cargar esta plantilla? Se reemplazará el contenido actual.',
      () => applyTemplate(template)
    );
  } else {
    applyTemplate(template);
  }
}

function applyTemplate(template) {
  if (!Array.isArray(template.layout)) return;

  // Transformar formato PHP a formato Vue
  const transformedLayout = template.layout.map(item => ({
    componentId: item.component_id || item.componentId,
    values: item.data || item.values || {},
    variant: item.variant || null,
  }));

  builderStore.importLayout(JSON.stringify(transformedLayout));
  uiStore.showSuccess(`Plantilla "${template.name}" cargada correctamente`);
  emit('close');
}
</script>

<style scoped>
.templates-content {
  padding: 20px;
}

/* Filtros */
.templates-filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.search-box {
  flex: 1;
  position: relative;
}

.search-box .dashicons {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--pb-text-muted);
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 34px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
}

.search-input:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 1px var(--pb-primary);
}

.category-filter {
  padding: 8px 12px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
  min-width: 180px;
}

/* Grid de plantillas */
.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  max-height: calc(60vh - 120px);
  overflow-y: auto;
}

.template-card {
  border: 2px solid var(--pb-border-light);
  border-radius: var(--pb-radius);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
}

.template-card:hover {
  border-color: var(--pb-border);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.template-card.is-selected {
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 1px var(--pb-primary);
}

.template-preview {
  height: 140px;
  background: var(--pb-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.template-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.template-preview .dashicons {
  font-size: 40px;
  width: 40px;
  height: 40px;
  color: var(--pb-text-muted);
}

.template-info {
  padding: 12px;
}

.template-info h4 {
  margin: 0 0 4px;
  font-size: 13px;
  font-weight: 600;
}

.template-description {
  margin: 0;
  font-size: 11px;
  color: var(--pb-text-light);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.template-category {
  display: inline-block;
  margin-top: 6px;
  padding: 2px 8px;
  font-size: 10px;
  background: var(--pb-bg);
  color: var(--pb-text-muted);
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* Estado vacío */
.templates-empty {
  text-align: center;
  padding: 60px 20px;
  color: var(--pb-text-muted);
}

.templates-empty .dashicons {
  font-size: 48px;
  width: 48px;
  height: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.templates-empty p {
  margin: 0;
  font-size: 14px;
}
</style>
