<template>
  <div class="preview-frame-container" :class="containerClasses">
    <div class="preview-frame-header">
      <div class="device-indicator">
        <span class="dashicons" :class="deviceIcon"></span>
        <span class="device-label">{{ deviceLabel }}</span>
      </div>
      <div class="preview-actions">
        <button
          class="preview-btn"
          @click="refreshPreview"
          title="Refrescar preview"
        >
          <span class="dashicons dashicons-update" :class="{ spin: isRefreshing }"></span>
        </button>
        <button
          class="preview-btn"
          @click="openInNewTab"
          title="Abrir en nueva pestaña"
        >
          <span class="dashicons dashicons-external"></span>
        </button>
      </div>
    </div>

    <div class="preview-frame-wrapper" :style="frameWrapperStyle">
      <iframe
        ref="iframeRef"
        :src="previewUrl"
        class="preview-iframe"
        :style="iframeStyle"
        @load="onIframeLoad"
        sandbox="allow-scripts allow-same-origin allow-forms"
      ></iframe>

      <!-- Overlay para interacciones -->
      <PreviewOverlay
        v-if="showOverlay"
        :iframe-ref="iframeRef"
        @block-click="handleBlockClick"
        @block-hover="handleBlockHover"
      />

      <!-- Indicador de carga -->
      <div v-if="isLoading" class="preview-loading">
        <span class="dashicons dashicons-update spin"></span>
        <span>Cargando preview...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';
import { usePreview } from '../../composables/usePreview';
import PreviewOverlay from './PreviewOverlay.vue';

const builderStore = useBuilderStore();
const uiStore = useUiStore();
const {
  setIframeRef,
  previewUrl,
  isIframeReady,
  sendLayoutToPreview,
  setPreviewDevice,
} = usePreview();

// Refs
const iframeRef = ref(null);
const isLoading = ref(true);
const isRefreshing = ref(false);

// Device dimensions
const deviceDimensions = {
  desktop: { width: '100%', height: '100%' },
  tablet: { width: '768px', height: '1024px' },
  mobile: { width: '375px', height: '667px' },
};

// Computed
const containerClasses = computed(() => ({
  'is-preview-mode': uiStore.isPreviewMode,
  [`device-${uiStore.previewDevice}`]: true,
}));

const deviceIcon = computed(() => {
  const icons = {
    desktop: 'dashicons-desktop',
    tablet: 'dashicons-tablet',
    mobile: 'dashicons-smartphone',
  };
  return icons[uiStore.previewDevice] || icons.desktop;
});

const deviceLabel = computed(() => {
  const labels = {
    desktop: 'Escritorio',
    tablet: 'Tablet',
    mobile: 'Móvil',
  };
  return labels[uiStore.previewDevice] || labels.desktop;
});

const showOverlay = computed(() => {
  return !uiStore.isPreviewMode && isIframeReady.value;
});

const frameWrapperStyle = computed(() => {
  if (uiStore.previewDevice === 'desktop') {
    return {};
  }
  const dims = deviceDimensions[uiStore.previewDevice];
  return {
    maxWidth: dims.width,
    margin: '0 auto',
  };
});

const iframeStyle = computed(() => {
  const dims = deviceDimensions[uiStore.previewDevice];
  if (uiStore.previewDevice === 'desktop') {
    return { width: '100%', height: '100%' };
  }
  return {
    width: dims.width,
    height: dims.height,
    maxHeight: '100%',
  };
});

// Methods
function onIframeLoad() {
  isLoading.value = false;
  setIframeRef(iframeRef.value);

  // Inyectar script de comunicación en el iframe
  injectPreviewScript();
}

function injectPreviewScript() {
  if (!iframeRef.value?.contentWindow) return;

  try {
    const script = iframeRef.value.contentDocument.createElement('script');
    script.textContent = `
      (function() {
        // Notificar que el iframe está listo
        window.parent.postMessage({
          type: 'PREVIEW_READY',
          source: 'flavor-preview-frame'
        }, '*');

        // Escuchar mensajes del builder
        window.addEventListener('message', function(event) {
          if (!event.data || event.data.source !== 'flavor-page-builder') return;

          var type = event.data.type;
          var payload = event.data.payload;

          switch (type) {
            case 'UPDATE_LAYOUT':
              // Recargar la página con el nuevo layout
              // En una implementación más avanzada, actualizar solo las secciones afectadas
              break;

            case 'UPDATE_BLOCK':
              // Actualizar un bloque específico
              var blockId = payload.blockId;
              var html = payload.html;
              var blockEl = document.querySelector('[data-block-id="' + blockId + '"]');
              if (blockEl) {
                blockEl.innerHTML = html;
              }
              break;

            case 'HIGHLIGHT_BLOCK':
              // Resaltar bloque
              var el = document.querySelector('[data-block-id="' + payload.blockId + '"]');
              if (el) {
                el.style.outline = '2px solid #0073aa';
                el.style.outlineOffset = '2px';
              }
              break;

            case 'UNHIGHLIGHT_BLOCK':
              // Quitar resaltado
              var el = document.querySelector('[data-block-id="' + payload.blockId + '"]');
              if (el) {
                el.style.outline = '';
                el.style.outlineOffset = '';
              }
              break;

            case 'SCROLL_TO_BLOCK':
              // Scroll al bloque
              var el = document.querySelector('[data-block-id="' + payload.blockId + '"]');
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }
              break;

            case 'SET_DEVICE':
              // El builder maneja esto, el iframe solo recibe la notificación
              break;
          }
        });

        // Detectar clics en bloques
        document.addEventListener('click', function(e) {
          var blockEl = e.target.closest('[data-block-id]');
          if (blockEl) {
            e.preventDefault();
            e.stopPropagation();
            window.parent.postMessage({
              type: 'BLOCK_CLICKED',
              source: 'flavor-preview-frame',
              payload: { blockId: blockEl.getAttribute('data-block-id') }
            }, '*');
          }
        });

        // Detectar hover en bloques
        document.addEventListener('mouseover', function(e) {
          var blockEl = e.target.closest('[data-block-id]');
          if (blockEl) {
            window.parent.postMessage({
              type: 'BLOCK_HOVER',
              source: 'flavor-preview-frame',
              payload: {
                blockId: blockEl.getAttribute('data-block-id'),
                isHovering: true
              }
            }, '*');
          }
        });

        document.addEventListener('mouseout', function(e) {
          var blockEl = e.target.closest('[data-block-id]');
          if (blockEl) {
            window.parent.postMessage({
              type: 'BLOCK_HOVER',
              source: 'flavor-preview-frame',
              payload: {
                blockId: blockEl.getAttribute('data-block-id'),
                isHovering: false
              }
            }, '*');
          }
        });
      })();
    `;
    iframeRef.value.contentDocument.body.appendChild(script);
  } catch (e) {
    console.warn('No se pudo inyectar script en iframe:', e);
  }
}

function refreshPreview() {
  isRefreshing.value = true;
  isLoading.value = true;

  if (iframeRef.value) {
    iframeRef.value.contentWindow.location.reload();
  }

  setTimeout(() => {
    isRefreshing.value = false;
  }, 1000);
}

function openInNewTab() {
  window.open(previewUrl.value, '_blank');
}

function handleBlockClick(blockId) {
  builderStore.selectBlock(blockId);
}

function handleBlockHover({ blockId, isHovering }) {
  // Podría usarse para resaltar el bloque en el panel de secciones
}

// Watch device changes
watch(() => uiStore.previewDevice, (device) => {
  setPreviewDevice(device);
});

// Lifecycle
onMounted(() => {
  if (iframeRef.value) {
    setIframeRef(iframeRef.value);
  }
});
</script>

<style scoped>
.preview-frame-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--pb-bg);
}

.preview-frame-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: var(--pb-bg-light);
  border-bottom: 1px solid var(--pb-border);
}

.device-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--pb-text-muted);
  font-size: 12px;
}

.device-indicator .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.preview-actions {
  display: flex;
  gap: 4px;
}

.preview-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  background: transparent;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  color: var(--pb-text-muted);
  transition: all 0.2s;
}

.preview-btn:hover {
  background: var(--pb-bg);
  color: var(--pb-text);
  border-color: var(--pb-primary);
}

.preview-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.preview-frame-wrapper {
  flex: 1;
  position: relative;
  overflow: auto;
  background: #e5e5e5;
}

.preview-iframe {
  display: block;
  border: none;
  background: white;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.device-desktop .preview-iframe {
  width: 100%;
  height: 100%;
  box-shadow: none;
}

.device-tablet .preview-frame-wrapper,
.device-mobile .preview-frame-wrapper {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 20px;
}

.preview-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.9);
  color: var(--pb-text-muted);
  font-size: 13px;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Preview mode - ocultar header */
.is-preview-mode .preview-frame-header {
  display: none;
}

.is-preview-mode .preview-frame-wrapper {
  background: white;
}

.is-preview-mode .preview-iframe {
  box-shadow: none;
}
</style>
