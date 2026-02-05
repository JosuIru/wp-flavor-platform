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
import { computed, onMounted, onUnmounted } from 'vue';
import { useBuilderStore } from './stores/builderStore';
import { useUiStore } from './stores/uiStore';
import { useKeyboard } from './composables/useKeyboard';
import { useAutoSave } from './composables/useAutoSave';

// Componentes
import MainToolbar from './components/toolbar/MainToolbar.vue';
import ComponentsSidebar from './components/sidebar/ComponentsSidebar.vue';
import BuilderCanvas from './components/canvas/BuilderCanvas.vue';
import PropertiesPanel from './components/properties/PropertiesPanel.vue';
import VariantModal from './components/sidebar/VariantModal.vue';
import TemplatesModal from './components/sidebar/TemplatesModal.vue';
import SectionSettingsModal from './components/sidebar/SectionSettingsModal.vue';
import ConfirmDialog from './components/ui/ConfirmDialog.vue';
import Toast from './components/ui/Toast.vue';
import DragGhost from './components/canvas/DragGhost.vue';

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

<style>
/* =============================================
   WORDPRESS ADMIN LAYOUT OVERRIDE - Critical!
   ============================================= */

/* Override WordPress 2-column layout - use highest specificity */
body.wp-admin.post-type-flavor_landing #poststuff #post-body.columns-2 {
  margin-right: 0 !important;
}

body.wp-admin.post-type-flavor_landing #poststuff #post-body #postbox-container-1 {
  display: none !important;
  width: 0 !important;
}

body.wp-admin.post-type-flavor_landing #poststuff #post-body #postbox-container-2 {
  float: none !important;
  width: 100% !important;
  max-width: 100% !important;
  margin-right: 0 !important;
}

/* Force metabox full width */
body.wp-admin.post-type-flavor_landing #postbox-container-2 #normal-sortables,
body.wp-admin.post-type-flavor_landing #postbox-container-2 #flavor_page_builder {
  width: 100% !important;
}

/* Reset WordPress metabox container */
#flavor_page_builder,
#flavor_page_builder .inside,
#flavor-vue-page-builder,
.flavor-pb-vue-wrapper {
  width: 100% !important;
  max-width: none !important;
  padding: 0 !important;
  margin: 0 !important;
  float: none !important;
  clear: both !important;
  display: block !important;
}

/* =============================================
   Variables CSS
   ============================================= */
.flavor-pb-vue-app {
  --pb-primary: #0073aa;
  --pb-primary-hover: #005a87;
  --pb-success: #46b450;
  --pb-warning: #ffb900;
  --pb-error: #dc3232;
  --pb-info: #00a0d2;

  --pb-bg: #f0f0f1;
  --pb-bg-light: #ffffff;
  --pb-border: #c3c4c7;
  --pb-border-light: #e2e4e7;

  --pb-text: #1d2327;
  --pb-text-light: #50575e;
  --pb-text-muted: #787c82;

  --pb-sidebar-width: 280px;
  --pb-properties-width: 320px;
  --pb-toolbar-height: 50px;

  --pb-radius: 4px;
  --pb-radius-lg: 8px;

  --pb-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  --pb-shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* =============================================
   Layout Principal - Critical Flex Setup
   ============================================= */

/* Box-sizing reset for all elements */
.flavor-pb-vue-app,
.flavor-pb-vue-app *,
.flavor-pb-vue-app *::before,
.flavor-pb-vue-app *::after {
  box-sizing: border-box !important;
}

/* Main app container - vertical flex */
.flavor-pb-vue-app {
  display: flex !important;
  flex-direction: column !important;
  width: 100% !important;
  max-width: 100% !important;
  height: calc(100vh - 100px);
  min-height: 600px;
  background: var(--pb-bg);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  font-size: 13px;
  color: var(--pb-text);
  position: relative !important;
  overflow: hidden !important;
}

/* Toolbar - first child, full width */
.flavor-pb-vue-app > .flavor-pb-main-toolbar {
  flex: 0 0 auto !important;
  width: 100% !important;
  order: 1 !important;
}

/* Main layout - second child, takes remaining space */
.flavor-pb-vue-app > .flavor-pb-main-layout {
  flex: 1 1 auto !important;
  width: 100% !important;
  order: 2 !important;
}

/* Toasts container - fixed position */
.flavor-pb-vue-app > .flavor-pb-toasts {
  order: 3 !important;
}

/* Main horizontal layout */
.flavor-pb-main-layout {
  display: flex !important;
  flex-direction: row !important;
  flex: 1 1 auto !important;
  width: 100% !important;
  min-width: 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}

/* Sidebar - fixed width, no shrink */
.flavor-pb-sidebar {
  flex: 0 0 var(--pb-sidebar-width) !important;
  width: var(--pb-sidebar-width) !important;
  min-width: var(--pb-sidebar-width) !important;
  max-width: var(--pb-sidebar-width) !important;
  background: var(--pb-bg-light);
  border-right: 1px solid var(--pb-border);
  display: flex !important;
  flex-direction: column !important;
  position: relative !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
}

.flavor-pb-sidebar.is-collapsed {
  flex: 0 0 40px !important;
  width: 40px !important;
  min-width: 40px !important;
  max-width: 40px !important;
}

.flavor-pb-sidebar-toggle {
  position: absolute;
  top: 10px;
  right: -12px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--pb-bg-light);
  border: 1px solid var(--pb-border);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  transition: background 0.2s;
}

.flavor-pb-sidebar-toggle:hover {
  background: var(--pb-bg);
}

/* Canvas area - flexible, takes remaining space */
.flavor-pb-canvas-area {
  flex: 1 1 auto !important;
  min-width: 200px !important;
  overflow: auto !important;
  padding: 20px;
  background: var(--pb-bg);
}

/* Panel de propiedades - fixed width, no shrink */
.flavor-pb-properties {
  flex: 0 0 var(--pb-properties-width) !important;
  width: var(--pb-properties-width) !important;
  min-width: var(--pb-properties-width) !important;
  max-width: var(--pb-properties-width) !important;
  background: var(--pb-bg-light);
  border-left: 1px solid var(--pb-border);
  display: flex !important;
  flex-direction: column !important;
  position: relative !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
}

.flavor-pb-properties.is-collapsed {
  flex: 0 0 40px !important;
  width: 40px !important;
  min-width: 40px !important;
  max-width: 40px !important;
}

.flavor-pb-properties-toggle {
  position: absolute;
  top: 10px;
  left: -12px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--pb-bg-light);
  border: 1px solid var(--pb-border);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  transition: background 0.2s;
}

.flavor-pb-properties-toggle:hover {
  background: var(--pb-bg);
}

/* Toasts */
.flavor-pb-toasts {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 100000;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Loading overlay */
.flavor-pb-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100001;
}

.flavor-pb-loading-spinner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 30px;
  background: var(--pb-bg-light);
  border-radius: var(--pb-radius-lg);
  box-shadow: var(--pb-shadow-lg);
}

.flavor-pb-loading-spinner .spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Indicador de cambios sin guardar */
.flavor-pb-dirty-indicator {
  position: fixed;
  bottom: 20px;
  left: 20px;
  padding: 8px 16px;
  background: var(--pb-warning);
  color: #000;
  border-radius: var(--pb-radius);
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  z-index: 1000;
}

/* Estados de drag */
.flavor-pb-vue-app.is-dragging {
  cursor: grabbing;
}

.flavor-pb-vue-app.is-dragging * {
  cursor: grabbing !important;
}

/* Responsive */
@media (max-width: 1200px) {
  .flavor-pb-vue-app {
    --pb-sidebar-width: 240px;
    --pb-properties-width: 280px;
  }
}

@media (max-width: 992px) {
  .flavor-pb-sidebar,
  .flavor-pb-properties {
    position: absolute;
    top: var(--pb-toolbar-height);
    bottom: 0;
    z-index: 100;
  }

  .flavor-pb-sidebar {
    left: 0;
  }

  .flavor-pb-properties {
    right: 0;
  }

  .flavor-pb-sidebar.is-collapsed,
  .flavor-pb-properties.is-collapsed {
    width: 0;
    border: none;
  }
}
</style>
