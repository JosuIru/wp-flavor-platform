/**
 * Helpers para trabajar con componentes y sus campos
 */

// Iconos por tipo de campo (inmutable)
const FIELD_TYPE_ICONS = Object.freeze({
  text: 'dashicons-editor-textcolor',
  textarea: 'dashicons-text',
  number: 'dashicons-calculator',
  toggle: 'dashicons-yes-alt',
  color: 'dashicons-art',
  image: 'dashicons-format-image',
  select: 'dashicons-list-view',
  repeater: 'dashicons-grid-view',
  icon: 'dashicons-star-filled',
});

// Icono por defecto para campos desconocidos
const DEFAULT_FIELD_ICON = 'dashicons-admin-generic';

/**
 * Sanitizar valor según tipo de campo
 */
export function sanitizeFieldValue(value, fieldType) {
  switch (fieldType) {
    case 'text':
    case 'textarea':
      return String(value || '');

    case 'number':
      const num = parseFloat(value);
      return isNaN(num) ? 0 : num;

    case 'toggle':
      return Boolean(value);

    case 'color':
      if (!value) return '';
      // Validar formato de color
      if (/^#[0-9A-Fa-f]{3,8}$/.test(value)) return value;
      if (/^rgb/.test(value)) return value;
      return '';

    case 'image':
      return String(value || '');

    case 'select':
      return value || '';

    case 'repeater':
      return Array.isArray(value) ? value : [];

    case 'icon':
      return String(value || '');

    default:
      return value;
  }
}

/**
 * Validar valor según configuración de campo
 */
export function validateFieldValue(value, fieldConfig) {
  const errors = [];

  // Requerido
  if (fieldConfig.required && !value && value !== 0 && value !== false) {
    errors.push(`${fieldConfig.label || 'Este campo'} es requerido`);
  }

  // Validaciones por tipo
  switch (fieldConfig.type) {
    case 'number':
      if (fieldConfig.min !== undefined && value < fieldConfig.min) {
        errors.push(`El valor mínimo es ${fieldConfig.min}`);
      }
      if (fieldConfig.max !== undefined && value > fieldConfig.max) {
        errors.push(`El valor máximo es ${fieldConfig.max}`);
      }
      break;

    case 'text':
    case 'textarea':
      if (fieldConfig.minLength && value.length < fieldConfig.minLength) {
        errors.push(`Mínimo ${fieldConfig.minLength} caracteres`);
      }
      if (fieldConfig.maxLength && value.length > fieldConfig.maxLength) {
        errors.push(`Máximo ${fieldConfig.maxLength} caracteres`);
      }
      if (fieldConfig.pattern) {
        const regex = new RegExp(fieldConfig.pattern);
        if (!regex.test(value)) {
          errors.push(fieldConfig.patternMessage || 'Formato inválido');
        }
      }
      break;

    case 'select':
      if (fieldConfig.options && !fieldConfig.options.some(opt =>
        (typeof opt === 'object' ? opt.value : opt) === value
      )) {
        errors.push('Opción no válida');
      }
      break;
  }

  return {
    isValid: errors.length === 0,
    errors,
  };
}

/**
 * Obtener icono por defecto para tipo de campo
 */
export function getFieldIcon(fieldType) {
  return FIELD_TYPE_ICONS[fieldType] || DEFAULT_FIELD_ICON;
}

/**
 * Formatear valor para mostrar en UI
 */
export function formatFieldValue(value, fieldType) {
  switch (fieldType) {
    case 'toggle':
      return value ? 'Sí' : 'No';

    case 'color':
      return value || '(Sin color)';

    case 'image':
      if (!value) return '(Sin imagen)';
      // Mostrar solo el nombre del archivo
      const parts = value.split('/');
      return parts[parts.length - 1];

    case 'repeater':
      if (!Array.isArray(value)) return '0 items';
      return `${value.length} item${value.length !== 1 ? 's' : ''}`;

    case 'number':
      return String(value);

    default:
      if (!value) return '(Vacío)';
      if (String(value).length > 50) {
        return String(value).substring(0, 47) + '...';
      }
      return String(value);
  }
}

/**
 * Clonar valores de bloque de forma profunda
 */
export function cloneBlockValues(values) {
  return JSON.parse(JSON.stringify(values));
}

/**
 * Comparar dos objetos de valores
 */
export function areValuesEqual(valuesA, valuesB) {
  return JSON.stringify(valuesA) === JSON.stringify(valuesB);
}

/**
 * Obtener diferencias entre dos objetos de valores
 */
export function getValuesDiff(oldValues, newValues) {
  const diff = {};

  // Campos modificados o añadidos
  for (const key of Object.keys(newValues)) {
    if (JSON.stringify(oldValues[key]) !== JSON.stringify(newValues[key])) {
      diff[key] = {
        old: oldValues[key],
        new: newValues[key],
      };
    }
  }

  // Campos eliminados
  for (const key of Object.keys(oldValues)) {
    if (!(key in newValues)) {
      diff[key] = {
        old: oldValues[key],
        new: undefined,
      };
    }
  }

  return diff;
}

/**
 * Generar nombre legible desde ID de componente
 */
export function componentIdToLabel(componentId) {
  return componentId
    .replace(/_/g, ' ')
    .replace(/-/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

/**
 * Generar ID de bloque único
 */
export function generateBlockId() {
  return `block-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Generar ID de sección único
 */
export function generateSectionId() {
  return `section-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}
