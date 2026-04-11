/**
 * Preview Iframe Bridge Script
 *
 * Este script se inyecta en el iframe de preview para establecer
 * comunicación bidireccional con el builder principal.
 *
 * Características:
 * - Notifica cuando el iframe está listo
 * - Escucha comandos del builder (UPDATE_BLOCK, HIGHLIGHT, SCROLL, etc.)
 * - Detecta interacciones del usuario (clicks, hover) en bloques
 */
export function getPreviewBridgeScript() {
  return `
(function() {
  'use strict';

  var SOURCE_PREVIEW = 'flavor-preview-frame';
  var SOURCE_BUILDER = 'flavor-page-builder';

  // Notificar que el iframe está listo
  window.parent.postMessage({
    type: 'PREVIEW_READY',
    source: SOURCE_PREVIEW
  }, '*');

  // Escuchar mensajes del builder
  window.addEventListener('message', function(event) {
    if (!event.data || event.data.source !== SOURCE_BUILDER) return;

    var type = event.data.type;
    var payload = event.data.payload;

    switch (type) {
      case 'UPDATE_LAYOUT':
        // Recargar la página con el nuevo layout
        // En una implementación más avanzada, actualizar solo las secciones afectadas
        break;

      case 'UPDATE_BLOCK':
        updateBlock(payload.blockId, payload.html);
        break;

      case 'HIGHLIGHT_BLOCK':
        highlightBlock(payload.blockId, true);
        break;

      case 'UNHIGHLIGHT_BLOCK':
        highlightBlock(payload.blockId, false);
        break;

      case 'SCROLL_TO_BLOCK':
        scrollToBlock(payload.blockId);
        break;

      case 'SET_DEVICE':
        // El builder maneja esto, el iframe solo recibe la notificación
        break;
    }
  });

  // Helpers
  function getBlockElement(blockId) {
    return document.querySelector('[data-block-id="' + blockId + '"]');
  }

  function updateBlock(blockId, html) {
    var blockEl = getBlockElement(blockId);
    if (blockEl) {
      blockEl.innerHTML = html;
    }
  }

  function highlightBlock(blockId, isHighlighted) {
    var blockEl = getBlockElement(blockId);
    if (blockEl) {
      blockEl.style.outline = isHighlighted ? '2px solid #0073aa' : '';
      blockEl.style.outlineOffset = isHighlighted ? '2px' : '';
    }
  }

  function scrollToBlock(blockId) {
    var blockEl = getBlockElement(blockId);
    if (blockEl) {
      blockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function sendToParent(type, payload) {
    window.parent.postMessage({
      type: type,
      source: SOURCE_PREVIEW,
      payload: payload
    }, '*');
  }

  // Detectar clics en bloques
  document.addEventListener('click', function(e) {
    var blockEl = e.target.closest('[data-block-id]');
    if (blockEl) {
      e.preventDefault();
      e.stopPropagation();
      sendToParent('BLOCK_CLICKED', {
        blockId: blockEl.getAttribute('data-block-id')
      });
    }
  });

  // Detectar hover en bloques
  document.addEventListener('mouseover', function(e) {
    var blockEl = e.target.closest('[data-block-id]');
    if (blockEl) {
      sendToParent('BLOCK_HOVER', {
        blockId: blockEl.getAttribute('data-block-id'),
        isHovering: true
      });
    }
  });

  document.addEventListener('mouseout', function(e) {
    var blockEl = e.target.closest('[data-block-id]');
    if (blockEl) {
      sendToParent('BLOCK_HOVER', {
        blockId: blockEl.getAttribute('data-block-id'),
        isHovering: false
      });
    }
  });
})();
`;
}
