import { computed } from 'vue';
import { useBuilderStore } from '../stores/builderStore';

/**
 * Composable para trabajar con definiciones de componentes
 */
export function useComponentDefs() {
  const builderStore = useBuilderStore();

  /**
   * Obtener definición de componente por ID
   */
  function getComponentDef(componentId) {
    return builderStore.componentDefs[componentId] || null;
  }

  /**
   * Obtener todos los componentes
   */
  function getAllComponents() {
    return Object.entries(builderStore.componentDefs).map(([id, def]) => ({
      id,
      ...def,
    }));
  }

  /**
   * Obtener componentes por categoría
   */
  function getComponentsByCategory(category) {
    return getAllComponents().filter(
      component => (component.category || 'general') === category && !component.deprecated
    );
  }

  /**
   * Obtener campos de un componente
   */
  function getComponentFields(componentId) {
    const componentDef = getComponentDef(componentId);
    if (!componentDef || !componentDef.fields) return [];

    return Object.entries(componentDef.fields).map(([key, field]) => ({
      key,
      ...field,
    }));
  }

  /**
   * Obtener variantes de un componente
   */
  function getComponentVariants(componentId) {
    const componentDef = getComponentDef(componentId);
    if (!componentDef || !componentDef.variants) return [];

    return Object.entries(componentDef.variants).map(([key, variant]) => ({
      key,
      ...variant,
    }));
  }

  /**
   * Obtener presets de un componente
   */
  function getComponentPresets(componentId) {
    const componentDef = getComponentDef(componentId);
    if (!componentDef || !componentDef.presets) return [];

    return Object.entries(componentDef.presets).map(([key, preset]) => ({
      key,
      ...preset,
    }));
  }

  /**
   * Verificar si un componente tiene variantes
   */
  function hasVariants(componentId) {
    const componentDef = getComponentDef(componentId);
    return componentDef && componentDef.variants && Object.keys(componentDef.variants).length > 0;
  }

  /**
   * Verificar si un componente tiene presets
   */
  function hasPresets(componentId) {
    const componentDef = getComponentDef(componentId);
    return componentDef && componentDef.presets && Object.keys(componentDef.presets).length > 0;
  }

  /**
   * Obtener valores por defecto de un componente
   */
  function getDefaultValues(componentId, variantKey = null) {
    const componentDef = getComponentDef(componentId);
    if (!componentDef) return {};

    const defaultValues = {};

    // Valores por defecto de campos
    if (componentDef.fields) {
      for (const [key, field] of Object.entries(componentDef.fields)) {
        if (field.default !== undefined) {
          defaultValues[key] = field.default;
        } else {
          // Valores por defecto según tipo
          switch (field.type) {
            case 'text':
            case 'textarea':
            case 'color':
            case 'image':
            case 'select':
            case 'icon':
              defaultValues[key] = '';
              break;
            case 'number':
              defaultValues[key] = field.min || 0;
              break;
            case 'toggle':
              defaultValues[key] = false;
              break;
            case 'repeater':
              defaultValues[key] = [];
              break;
            default:
              defaultValues[key] = '';
          }
        }
      }
    }

    // Aplicar valores de variante si se especifica
    if (variantKey && componentDef.variants?.[variantKey]) {
      const variantValues = componentDef.variants[variantKey].values || {};
      Object.assign(defaultValues, variantValues);
    }

    return defaultValues;
  }

  /**
   * Obtener valores de un preset
   */
  function getPresetValues(componentId, presetKey) {
    const componentDef = getComponentDef(componentId);
    if (!componentDef?.presets?.[presetKey]) return {};

    return componentDef.presets[presetKey].values || {};
  }

  /**
   * Verificar si un campo debe mostrarse según condiciones show_when
   */
  function shouldShowField(fieldDef, currentValues) {
    if (!fieldDef.show_when) return true;

    const { field, value, operator = '==' } = fieldDef.show_when;
    const currentValue = currentValues[field];

    switch (operator) {
      case '==':
      case '===':
        return currentValue === value;
      case '!=':
      case '!==':
        return currentValue !== value;
      case '>':
        return currentValue > value;
      case '<':
        return currentValue < value;
      case '>=':
        return currentValue >= value;
      case '<=':
        return currentValue <= value;
      case 'in':
        return Array.isArray(value) && value.includes(currentValue);
      case 'not_in':
        return Array.isArray(value) && !value.includes(currentValue);
      case 'truthy':
        return !!currentValue;
      case 'falsy':
        return !currentValue;
      default:
        return true;
    }
  }

  /**
   * Obtener campos visibles según valores actuales
   */
  function getVisibleFields(componentId, currentValues) {
    const fields = getComponentFields(componentId);
    return fields.filter(field => shouldShowField(field, currentValues));
  }

  /**
   * Agrupar campos por grupo
   */
  function getFieldsByGroup(componentId) {
    const fields = getComponentFields(componentId);
    const groups = {};

    for (const field of fields) {
      const groupName = field.group || 'general';
      if (!groups[groupName]) {
        groups[groupName] = [];
      }
      groups[groupName].push(field);
    }

    return groups;
  }

  /**
   * Cache de búsqueda para evitar recálculos
   */
  const searchCache = new Map();
  const SEARCH_CACHE_MAX_SIZE = 20;

  /**
   * Buscar componentes por término (con memoización)
   */
  function searchComponents(term) {
    if (!term) return getAllComponents().filter(c => !c.deprecated);

    const lowerTerm = term.toLowerCase().trim();
    if (lowerTerm.length < 2) return []; // Mínimo 2 caracteres

    // Verificar cache
    if (searchCache.has(lowerTerm)) {
      return searchCache.get(lowerTerm);
    }

    // Realizar búsqueda
    const results = getAllComponents().filter(component => {
      if (component.deprecated) return false;

      // Búsqueda optimizada: priorizar coincidencias exactas
      const idMatch = component.id.toLowerCase().includes(lowerTerm);
      const labelMatch = component.label?.toLowerCase().includes(lowerTerm);
      const descMatch = component.description?.toLowerCase().includes(lowerTerm);
      const catMatch = component.category?.toLowerCase().includes(lowerTerm);

      return idMatch || labelMatch || descMatch || catMatch;
    });

    // Ordenar resultados: coincidencias en label/id primero
    results.sort((componentA, componentB) => {
      const aLabelMatch = componentA.label?.toLowerCase().startsWith(lowerTerm) ? -2 :
                          componentA.label?.toLowerCase().includes(lowerTerm) ? -1 : 0;
      const bLabelMatch = componentB.label?.toLowerCase().startsWith(lowerTerm) ? -2 :
                          componentB.label?.toLowerCase().includes(lowerTerm) ? -1 : 0;
      return aLabelMatch - bLabelMatch;
    });

    // Guardar en cache (con límite de tamaño)
    if (searchCache.size >= SEARCH_CACHE_MAX_SIZE) {
      // Eliminar la entrada más antigua
      const firstKey = searchCache.keys().next().value;
      searchCache.delete(firstKey);
    }
    searchCache.set(lowerTerm, results);

    return results;
  }

  /**
   * Limpiar cache de búsqueda (útil cuando cambian los componentes)
   */
  function clearSearchCache() {
    searchCache.clear();
  }

  // Computed
  const categories = computed(() => builderStore.categories);
  const componentsByCategory = computed(() => builderStore.componentsByCategory);
  const totalComponents = computed(() => Object.keys(builderStore.componentDefs).length);

  return {
    // Métodos
    getComponentDef,
    getAllComponents,
    getComponentsByCategory,
    getComponentFields,
    getComponentVariants,
    getComponentPresets,
    hasVariants,
    hasPresets,
    getDefaultValues,
    getPresetValues,
    shouldShowField,
    getVisibleFields,
    getFieldsByGroup,
    searchComponents,
    clearSearchCache,

    // Computed
    categories,
    componentsByCategory,
    totalComponents,
  };
}
