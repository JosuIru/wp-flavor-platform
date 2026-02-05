import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import { useBuilderStore } from './stores/builderStore';
import { useUiStore } from './stores/uiStore';

/**
 * Inicializa el Vue Page Builder
 * Se expone globalmente para que WordPress pueda iniciarlo
 */
function initFlavorPageBuilder(containerId = 'flavor-vue-page-builder', config = {}) {
  const container = document.getElementById(containerId);

  if (!container) {
    console.error(`Container #${containerId} not found`);
    return null;
  }

  // Crear app Vue
  const app = createApp(App);

  // Crear e instalar Pinia
  const pinia = createPinia();
  app.use(pinia);

  // Montar app
  app.mount(container);

  // Inicializar stores con configuración de WordPress
  const builderStore = useBuilderStore();
  const uiStore = useUiStore();

  // Obtener configuración desde window.flavorPageBuilder (localizado por WordPress)
  const wordpressConfig = window.flavorPageBuilder || config;

  builderStore.initialize({
    postId: wordpressConfig.postId || 0,
    ajaxUrl: wordpressConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
    nonce: wordpressConfig.nonce || '',
    previewUrl: wordpressConfig.previewUrl || '',
    components: wordpressConfig.components || {},
    categories: wordpressConfig.categories || [],
    layout: wordpressConfig.layout || [],
  });

  // Cargar preferencias de usuario
  uiStore.loadPreferences();

  // Exponer API pública
  const publicApi = {
    app,
    stores: {
      builder: builderStore,
      ui: uiStore,
    },
    // Métodos públicos
    save: () => builderStore.save(),
    undo: () => builderStore.undo(),
    redo: () => builderStore.redo(),
    addBlock: (sectionId, componentId, position, values, variant) =>
      builderStore.addBlock(sectionId, componentId, position, values, variant),
    removeBlock: (blockId) => builderStore.removeBlock(blockId),
    selectBlock: (blockId) => builderStore.selectBlock(blockId),
    getLayout: () => builderStore.layoutJson,
    exportLayout: () => builderStore.exportLayout(),
    importLayout: (json) => builderStore.importLayout(json),
    destroy: () => {
      app.unmount();
    },
  };

  // Exponer globalmente para debug/extensión
  window.flavorVuePageBuilder = publicApi;

  return publicApi;
}

// Exportar para uso como módulo
export { initFlavorPageBuilder };

// Exportar stores para uso externo
export { useBuilderStore } from './stores/builderStore';
export { useUiStore } from './stores/uiStore';

// Auto-inicializar si el contenedor existe y hay configuración
if (typeof window !== 'undefined') {
  // Exponer función de inicialización globalmente
  window.initFlavorPageBuilder = initFlavorPageBuilder;

  // Auto-inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      // Solo auto-inicializar si existe el contenedor y la configuración
      if (
        document.getElementById('flavor-vue-page-builder') &&
        window.flavorPageBuilder
      ) {
        initFlavorPageBuilder();
      }
    });
  } else {
    // DOM ya está listo
    if (
      document.getElementById('flavor-vue-page-builder') &&
      window.flavorPageBuilder
    ) {
      initFlavorPageBuilder();
    }
  }
}
