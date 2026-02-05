/**
 * Helpers para drag & drop
 */

/**
 * Obtener posición de drop basada en coordenadas del mouse
 */
export function getDropPosition(event, container) {
  const rect = container.getBoundingClientRect();
  const relativeY = event.clientY - rect.top;
  const items = container.querySelectorAll('[data-draggable="true"]');

  let dropPosition = 0;

  for (const item of items) {
    const itemRect = item.getBoundingClientRect();
    const itemMiddle = itemRect.top + itemRect.height / 2 - rect.top;

    if (relativeY > itemMiddle) {
      dropPosition++;
    } else {
      break;
    }
  }

  return dropPosition;
}

/**
 * Obtener elemento padre draggable más cercano
 */
export function getClosestDraggable(element) {
  return element.closest('[data-draggable="true"]');
}

/**
 * Obtener información del elemento bajo el cursor
 */
export function getElementUnderCursor(event, selector) {
  const elements = document.elementsFromPoint(event.clientX, event.clientY);
  return elements.find(el => el.matches(selector));
}

/**
 * Calcular si el cursor está en la mitad superior o inferior de un elemento
 */
export function isInTopHalf(event, element) {
  const rect = element.getBoundingClientRect();
  const middle = rect.top + rect.height / 2;
  return event.clientY < middle;
}

/**
 * Crear indicador visual de drop
 */
export function createDropIndicator() {
  const indicator = document.createElement('div');
  indicator.className = 'flavor-pb-drop-indicator';
  indicator.style.cssText = `
    position: absolute;
    left: 0;
    right: 0;
    height: 3px;
    background: #0073aa;
    border-radius: 2px;
    pointer-events: none;
    z-index: 1000;
    transition: transform 0.1s ease;
  `;
  return indicator;
}

/**
 * Posicionar indicador de drop
 */
export function positionDropIndicator(indicator, targetElement, position) {
  if (!indicator || !targetElement) return;

  const rect = targetElement.getBoundingClientRect();
  const parentRect = targetElement.parentElement.getBoundingClientRect();

  indicator.style.left = '0';
  indicator.style.right = '0';

  if (position === 'before') {
    indicator.style.top = `${rect.top - parentRect.top - 2}px`;
  } else {
    indicator.style.top = `${rect.bottom - parentRect.top - 1}px`;
  }
}

/**
 * Crear elemento ghost para drag
 */
export function createDragGhost(content, options = {}) {
  const {
    backgroundColor = '#0073aa',
    textColor = 'white',
    icon = null,
  } = options;

  const ghostElement = document.createElement('div');
  ghostElement.className = 'flavor-pb-drag-ghost';

  let innerHTML = '';
  if (icon) {
    innerHTML += `<span class="dashicons ${icon}"></span>`;
  }
  innerHTML += `<span class="ghost-label">${content}</span>`;

  ghostElement.innerHTML = innerHTML;
  ghostElement.style.cssText = `
    position: fixed;
    top: -1000px;
    left: -1000px;
    padding: 8px 16px;
    background: ${backgroundColor};
    color: ${textColor};
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100000;
    white-space: nowrap;
  `;

  return ghostElement;
}

/**
 * Aplicar estilos de drag activo a elemento
 */
export function applyDraggingStyles(element) {
  element.style.opacity = '0.5';
  element.style.transform = 'scale(0.98)';
  element.classList.add('is-dragging');
}

/**
 * Remover estilos de drag de elemento
 */
export function removeDraggingStyles(element) {
  element.style.opacity = '';
  element.style.transform = '';
  element.classList.remove('is-dragging');
}

/**
 * Verificar si el drag es válido (no está sobre sí mismo)
 */
export function isValidDrop(draggedId, targetId) {
  return draggedId !== targetId;
}

/**
 * Obtener datos de transferencia del evento drag
 */
export function getDragData(event) {
  try {
    const dataString = event.dataTransfer.getData('text/plain');
    return JSON.parse(dataString);
  } catch {
    return null;
  }
}

/**
 * Establecer datos de transferencia para drag
 */
export function setDragData(event, data) {
  event.dataTransfer.setData('text/plain', JSON.stringify(data));
}

/**
 * Configurar imagen de drag personalizada
 */
export function setDragImage(event, element, offsetX = 50, offsetY = 25) {
  if (event.dataTransfer.setDragImage) {
    event.dataTransfer.setDragImage(element, offsetX, offsetY);
  }
}

/**
 * Animar elemento al hacer drop
 */
export function animateDropTarget(element) {
  element.style.transition = 'background-color 0.3s ease';
  element.style.backgroundColor = 'rgba(0, 115, 170, 0.1)';

  setTimeout(() => {
    element.style.backgroundColor = '';
    setTimeout(() => {
      element.style.transition = '';
    }, 300);
  }, 300);
}

/**
 * Crear zona de drop vacía
 */
export function createEmptyDropZone(message = 'Arrastra componentes aquí') {
  const dropZone = document.createElement('div');
  dropZone.className = 'flavor-pb-empty-drop-zone';
  dropZone.innerHTML = `
    <div class="drop-zone-content">
      <span class="dashicons dashicons-plus-alt2"></span>
      <p>${message}</p>
    </div>
  `;
  return dropZone;
}
