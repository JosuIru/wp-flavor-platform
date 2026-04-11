<template>
  <div class="flavor-pb-vue-app" :class="appClasses">
    <!-- Toolbar Principal -->
    <MainToolbar
      @save="handleSave"
      @preview="togglePreview"
      @undo="handleUndo"
      @redo="handleRedo"
    />

    <!-- Layout Principal: 3 columnas -->
    <div class="flavor-pb-main-layout">
      <!-- Sidebar izquierdo: Componentes -->
      <aside
        class="flavor-pb-sidebar"
        :class="{ 'is-collapsed': !uiStore.sidebarOpen }"
      >
        <ComponentsSidebar v-if="uiStore.sidebarOpen" />
        <button
          class="flavor-pb-sidebar-toggle"
          @click="uiStore.toggleSidebar"
          :title="uiStore.sidebarOpen ? 'Ocultar componentes' : 'Mostrar componentes'"
        >
          <span class="dashicons" :class="uiStore.sidebarOpen ? 'dashicons-arrow-left-alt2' : 'dashicons-arrow-right-alt2'"></span>
        </button>
      </aside>

      <!-- Canvas Central -->
      <main class="flavor-pb-canvas-area">
        <BuilderCanvas />
      </main>

      <!-- Panel derecho: Propiedades -->
      <aside
        class="flavor-pb-properties"
        :class="{ 'is-collapsed': !uiStore.propertiesPanelOpen }"
      >
        <button
          class="flavor-pb-properties-toggle"
          @click="uiStore.togglePropertiesPanel"
          :title="uiStore.propertiesPanelOpen ? 'Ocultar propiedades' : 'Mostrar propiedades'"
        >
          <span class="dashicons" :class="uiStore.propertiesPanelOpen ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-left-alt2'"></span>
        </button>
        <PropertiesPanel v-if="uiStore.propertiesPanelOpen" />
      </aside>
    </div>

    <!-- Modal de variantes -->
    <VariantModal
      v-if="uiStore.activeModal?.name === 'variant'"
      :component-id="uiStore.activeModal.data.componentId"
      @select="handleVariantSelect"
      @close="uiStore.closeModal"
    />

    <!-- Modal de plantillas -->
    <TemplatesModal
      v-if="uiStore.activeModal?.name === 'templates'"
      @close="uiStore.closeModal"
    />

    <!-- Modal de configuracion de seccion -->
    <SectionSettingsModal
      v-if="uiStore.activeModal?.name === 'sectionSettings'"
      :section-id="uiStore.activeModal.data.sectionId"
      @close="uiStore.closeModal"
    />

    <!-- Modal de confirmación -->
    <ConfirmDialog
      v-if="uiStore.pendingConfirmation"
      :message="uiStore.pendingConfirmation.message"
      @confirm="uiStore.confirmAction"
      @cancel="uiStore.cancelConfirmation"
    />

    <!-- Toasts -->
    <div class="flavor-pb-toasts">
      <Toast
        v-for="toast in uiStore.toasts"
        :key="toast.id"
        :message="toast.message"
        :type="toast.type"
        @close="uiStore.removeToast(toast.id)"
      />
    </div>

    <!-- Indicador de carga -->
    <div v-if="uiStore.isLoading" class="flavor-pb-loading-overlay">
      <div class="flavor-pb-loading-spinner">
        <span class="dashicons dashicons-update spin"></span>
        <span v-if="uiStore.loadingMessage">{{ uiStore.loadingMessage }}</span>
      </div>
    </div>

    <!-- Indicador de cambios sin guardar -->
    <div v-if="builderStore.isDirty" class="flavor-pb-dirty-indicator">
      <span class="dashicons dashicons-warning"></span>
      Cambios sin guardar
    </div>

    <!-- Ghost visual durante drag -->
    <DragGhost />
  </div>
</template>

<script setup>
import { computed, onMounted, defineAsyncComponent } from 'vue';
import { useBuilderStore } from './stores/builderStore';
import { useUiStore } from './stores/uiStore';
import { useKeyboard } from './composables/useKeyboard';
import { useAutoSave } from './composables/useAutoSave';

// Importar estilos de layout global (archivo externo)
import './styles/app-layout.css';

// Componentes principales (carga inmediata)
import MainToolbar from './components/toolbar/MainToolbar.vue';
import ComponentsSidebar from './components/sidebar/ComponentsSidebar.vue';
import BuilderCanvas from './components/canvas/BuilderCanvas.vue';
import PropertiesPanel from './components/properties/PropertiesPanel.vue';
import ConfirmDialog from './components/ui/ConfirmDialog.vue';
import Toast from './components/ui/Toast.vue';
import DragGhost from './components/canvas/DragGhost.vue';

// Modales pesados (lazy loading - solo se cargan cuando se usan)
const VariantModal = defineAsyncComponent(() =>
  import('./components/sidebar/VariantModal.vue')
);
const TemplatesModal = defineAsyncComponent(() =>
  import('./components/sidebar/TemplatesModal.vue')
);
const SectionSettingsModal = defineAsyncComponent(() =>
  import('./components/sidebar/SectionSettingsModal.vue')
);

// Stores
const builderStore = useBuilderStore();
const uiStore = useUiStore();

// Composables
const { saveLayout } = useKeyboard();
const { saveNow } = useAutoSave();

// Computed
const appClasses = computed(() => ({
  'is-dragging': builderStore.isDragging,
  'is-preview-mode': uiStore.isPreviewMode,
  [`device-${uiStore.previewDevice}`]: true,
}));

// Handlers
async function handleSave() {
  await saveNow();
}

function togglePreview() {
  uiStore.togglePreviewMode();
}

function handleUndo() {
  builderStore.undo();
}

function handleRedo() {
  builderStore.redo();
}

function handleVariantSelect(data) {
  const { componentId, variant, values } = data;

  // Si hay una sección seleccionada, añadir ahí
  const targetSectionId = builderStore.selectedSectionId ||
    (builderStore.sections[0]?.id);

  builderStore.addBlock(targetSectionId, componentId, -1, values, variant);
  uiStore.closeModal();
}

// Lifecycle
onMounted(() => {
  // Sincronizar con el campo oculto del formulario de WordPress
  syncWithWordPressForm();
});

function syncWithWordPressForm() {
  // Buscar el campo oculto del layout
  const layoutInput = document.querySelector('input[name="flavor_page_layout"]');
  if (!layoutInput) return;

  // Actualizar el campo cuando cambie el layout
  const updateField = () => {
    layoutInput.value = JSON.stringify(builderStore.layoutJson);
  };

  // Observar cambios en el store
  builderStore.$subscribe(() => {
    updateField();
  });

  // Actualización inicial
  updateField();
}
</script>

<!--
  Estilos movidos a src/styles/app-layout.css
  Se importan automáticamente en el script setup
-->
