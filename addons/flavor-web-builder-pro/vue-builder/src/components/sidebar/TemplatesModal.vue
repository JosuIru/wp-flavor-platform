<template>
  <div class="flavor-pb-modal-overlay" @click.self="$emit('close')">
    <div class="flavor-pb-modal templates-modal">
      <div class="modal-header">
        <h3>Plantillas de Ejemplo</h3>
        <button class="modal-close" @click="$emit('close')" title="Cerrar">
          <span class="dashicons dashicons-no-alt"></span>
        </button>
      </div>

      <div class="modal-body">
        <!-- Filtros -->
        <div class="templates-filters">
          <div class="search-box">
            <span class="dashicons dashicons-search"></span>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar plantilla..."
              class="search-input"
            />
          </div>
          <select v-model="selectedCategory" class="category-filter">
            <option value="">Todas las categorias</option>
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
              <img v-if="template.thumbnail" :src="template.thumbnail" :alt="template.name" />
              <span v-else class="dashicons" :class="template.icon || 'dashicons-layout'"></span>
            </div>
            <div class="template-info">
              <h4>{{ template.name || 'Sin nombre' }}</h4>
              <p v-if="template.description" class="template-description">{{ template.description }}</p>
              <span v-if="template.categoryLabel" class="template-category">{{ template.categoryLabel }}</span>
            </div>
          </div>
        </div>

        <!-- Estado vacio -->
        <div v-else class="templates-empty">
          <span class="dashicons dashicons-portfolio"></span>
          <p>No se encontraron plantillas</p>
        </div>
      </div>

      <div class="modal-footer">
        <button class="button" @click="$emit('close')">Cancelar</button>
        <button
          class="button button-primary"
          :disabled="!selectedTemplate"
          @click="loadTemplate"
        >
          Cargar Plantilla
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';

const emit = defineEmits(['close', 'load']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();

// State
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedTemplate = ref(null);

// Obtener plantillas de la configuracion de WordPress
// La estructura de PHP es: { categoria: { label, templates: { key: {...} } } }
// La transformamos a un array plano: [{ id, name, description, category, ... }]
const templates = computed(() => {
  const wpConfig = window.flavorPageBuilder || {};
  const tpls = wpConfig.templates;

  if (!tpls || typeof tpls !== 'object') return [];

  // Si ya es un array plano, usarlo directamente
  if (Array.isArray(tpls)) return tpls;

  // Transformar estructura anidada a array plano
  const flatTemplates = [];

  for (const [categoryKey, categoryData] of Object.entries(tpls)) {
    // Verificar si tiene la estructura { label, templates }
    if (categoryData && categoryData.templates && typeof categoryData.templates === 'object') {
      // Estructura anidada con categorias
      const categoryLabel = categoryData.label || categoryKey;

      for (const [templateKey, templateData] of Object.entries(categoryData.templates)) {
        flatTemplates.push({
          id: templateKey,
          name: templateData.name || templateKey,
          description: templateData.description || '',
          category: categoryKey,
          categoryLabel: categoryLabel,
          thumbnail: templateData.preview || templateData.thumbnail || '',
          icon: templateData.icon || 'dashicons-layout',
          layout: templateData.layout || [],
        });
      }
    } else if (categoryData && categoryData.name) {
      // Estructura plana (la plantilla esta directamente)
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
  const catsMap = new Map();
  const tplList = templates.value;

  if (Array.isArray(tplList)) {
    tplList.forEach(t => {
      if (t && t.category && !catsMap.has(t.category)) {
        catsMap.set(t.category, {
          id: t.category,
          label: t.categoryLabel || t.category.charAt(0).toUpperCase() + t.category.slice(1).replace(/[-_]/g, ' ')
        });
      }
    });
  }

  return Array.from(catsMap.values());
});

const filteredTemplates = computed(() => {
  let result = templates.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(t =>
      t.name?.toLowerCase().includes(query) ||
      t.description?.toLowerCase().includes(query)
    );
  }

  if (selectedCategory.value) {
    result = result.filter(t => t.category === selectedCategory.value);
  }

  return result;
});

function selectTemplate(templateId) {
  selectedTemplate.value = templateId;
}

function loadTemplate() {
  if (!selectedTemplate.value) return;

  const template = templates.value.find(t => t.id === selectedTemplate.value);
  if (!template) return;

  // Confirmar si hay contenido existente
  if (builderStore.sections.length > 0 && builderStore.sections.some(s => s.blocks.length > 0)) {
    uiStore.confirm(
      '¿Cargar esta plantilla? Se reemplazara el contenido actual.',
      () => {
        applyTemplate(template);
      }
    );
  } else {
    applyTemplate(template);
  }
}

function applyTemplate(template) {
  if (template.layout && Array.isArray(template.layout)) {
    // Transformar formato PHP (component_id, data) a formato Vue (componentId, values)
    const transformedLayout = template.layout.map(item => ({
      componentId: item.component_id || item.componentId,
      values: item.data || item.values || {},
      variant: item.variant || null,
    }));

    builderStore.importLayout(JSON.stringify(transformedLayout));
    uiStore.showSuccess(`Plantilla "${template.name}" cargada correctamente`);
  }
  emit('close');
}
</script>

<style scoped>
.flavor-pb-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100000;
}

.flavor-pb-modal {
  background: var(--pb-bg-light, #fff);
  border-radius: var(--pb-radius-lg, 8px);
  box-shadow: var(--pb-shadow-lg, 0 4px 12px rgba(0,0,0,0.15));
  max-width: 90vw;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.templates-modal {
  width: 900px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--pb-border, #c3c4c7);
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: var(--pb-text-muted, #787c82);
  border-radius: 4px;
}

.modal-close:hover {
  background: var(--pb-bg, #f0f0f1);
  color: var(--pb-text, #1d2327);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
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
  color: var(--pb-text-muted, #787c82);
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 34px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  font-size: 13px;
}

.search-input:focus {
  outline: none;
  border-color: var(--pb-primary, #0073aa);
  box-shadow: 0 0 0 1px var(--pb-primary, #0073aa);
}

.category-filter {
  padding: 8px 12px;
  border: 1px solid var(--pb-border, #c3c4c7);
  border-radius: var(--pb-radius, 4px);
  font-size: 13px;
  min-width: 180px;
}

/* Grid de plantillas */
.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
}

.template-card {
  border: 2px solid var(--pb-border-light, #e2e4e7);
  border-radius: var(--pb-radius, 4px);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
}

.template-card:hover {
  border-color: var(--pb-border, #c3c4c7);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.template-card.is-selected {
  border-color: var(--pb-primary, #0073aa);
  box-shadow: 0 0 0 1px var(--pb-primary, #0073aa);
}

.template-preview {
  height: 150px;
  background: var(--pb-bg, #f0f0f1);
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
  font-size: 48px;
  width: 48px;
  height: 48px;
  color: var(--pb-text-muted, #787c82);
}

.template-info {
  padding: 12px;
}

.template-info h4 {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 600;
}

.template-info p {
  margin: 0;
  font-size: 12px;
  color: var(--pb-text-light, #50575e);
}

.template-description {
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
  background: var(--pb-bg, #f0f0f1);
  color: var(--pb-text-muted, #787c82);
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Estado vacio */
.templates-empty {
  text-align: center;
  padding: 60px 20px;
  color: var(--pb-text-muted, #787c82);
}

.templates-empty .dashicons {
  font-size: 48px;
  width: 48px;
  height: 48px;
  margin-bottom: 16px;
}

.templates-empty p {
  margin: 0;
  font-size: 14px;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--pb-border, #c3c4c7);
  background: var(--pb-bg, #f0f0f1);
}

.modal-footer .button {
  padding: 8px 16px;
}
</style>
