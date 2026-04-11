import { defineStore } from 'pinia';

// Constantes de zoom (exportadas para uso en componentes)
export const ZOOM_CONFIG = Object.freeze({
  MIN: 25,
  MAX: 200,
  DEFAULT: 100,
  STEP: 10,
});

// Dispositivos de preview válidos
export const PREVIEW_DEVICES = Object.freeze(['desktop', 'tablet', 'mobile']);

/**
 * Store para estado de UI
 * Gestiona modales, toasts, paneles y estado visual
 */
export const useUiStore = defineStore('ui', {
  state: () => ({
    // Paneles
    sidebarOpen: true,
    propertiesPanelOpen: true,

    // Modal activo
    activeModal: null, // { name: string, data: object }

    // Vista previa responsive
    previewDevice: 'desktop', // 'desktop' | 'tablet' | 'mobile'

    // Edición inline
    inlineEditingBlock: null, // { blockId, fieldName }

    // Notificaciones toast
    toasts: [], // [{ id, message, type, duration }]
    toastCounter: 0,

    // Estado de carga
    isLoading: false,
    loadingMessage: '',

    // Preferencias de usuario
    preferences: {
      showGridLines: false,
      snapToGrid: true,
      autoPreview: true,
      compactSidebar: false,
    },

    // Panel de propiedades - acordeón expandido
    expandedPropertyGroups: ['general'],

    // Tooltips
    activeTooltip: null,

    // Contexto de menú
    contextMenu: null, // { x, y, items, targetId, targetType }

    // Confirmación pendiente
    pendingConfirmation: null, // { message, onConfirm, onCancel }

    // Zoom del canvas
    canvasZoom: 100,

    // Modo preview en iframe
    isPreviewMode: false,
  }),

  getters: {
    /**
     * Verificar si hay un modal abierto
     */
    hasActiveModal: (state) => state.activeModal !== null,

    /**
     * Obtener ancho de viewport según dispositivo
     */
    viewportWidth: (state) => {
      switch (state.previewDevice) {
        case 'mobile': return 375;
        case 'tablet': return 768;
        default: return '100%';
      }
    },

    /**
     * Verificar si hay edición inline activa
     */
    isInlineEditing: (state) => state.inlineEditingBlock !== null,
  },

  actions: {
    /**
     * Abrir modal
     */
    openModal(name, data = {}) {
      this.activeModal = { name, data };
    },

    /**
     * Cerrar modal activo
     */
    closeModal() {
      this.activeModal = null;
    },

    /**
     * Toggle sidebar
     */
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },

    /**
     * Toggle panel de propiedades
     */
    togglePropertiesPanel() {
      this.propertiesPanelOpen = !this.propertiesPanelOpen;
    },

    /**
     * Cambiar dispositivo de preview
     */
    setPreviewDevice(device) {
      if (PREVIEW_DEVICES.includes(device)) {
        this.previewDevice = device;
      }
    },

    /**
     * Iniciar edición inline
     */
    startInlineEdit(blockId, fieldName) {
      this.inlineEditingBlock = { blockId, fieldName };
    },

    /**
     * Detener edición inline
     */
    stopInlineEdit() {
      this.inlineEditingBlock = null;
    },

    /**
     * Mostrar toast
     */
    showToast(message, type = 'info', duration = 3000) {
      const toastId = ++this.toastCounter;

      this.toasts.push({
        id: toastId,
        message,
        type, // 'success' | 'error' | 'warning' | 'info'
        duration,
      });

      // Auto-remove después de la duración
      if (duration > 0) {
        setTimeout(() => {
          this.removeToast(toastId);
        }, duration);
      }

      return toastId;
    },

    /**
     * Eliminar toast
     */
    removeToast(toastId) {
      const index = this.toasts.findIndex(t => t.id === toastId);
      if (index !== -1) {
        this.toasts.splice(index, 1);
      }
    },

    /**
     * Mostrar notificación de éxito
     */
    showSuccess(message, duration = 3000) {
      return this.showToast(message, 'success', duration);
    },

    /**
     * Mostrar notificación de error
     */
    showError(message, duration = 5000) {
      return this.showToast(message, 'error', duration);
    },

    /**
     * Mostrar notificación de advertencia
     */
    showWarning(message, duration = 4000) {
      return this.showToast(message, 'warning', duration);
    },

    /**
     * Establecer estado de carga
     */
    setLoading(isLoading, message = '') {
      this.isLoading = isLoading;
      this.loadingMessage = message;
    },

    /**
     * Toggle grupo de propiedades
     */
    togglePropertyGroup(groupName) {
      const index = this.expandedPropertyGroups.indexOf(groupName);
      if (index !== -1) {
        this.expandedPropertyGroups.splice(index, 1);
      } else {
        this.expandedPropertyGroups.push(groupName);
      }
    },

    /**
     * Verificar si grupo de propiedades está expandido
     */
    isPropertyGroupExpanded(groupName) {
      return this.expandedPropertyGroups.includes(groupName);
    },

    /**
     * Mostrar menú contextual
     */
    showContextMenu(x, y, items, targetId = null, targetType = null) {
      this.contextMenu = { x, y, items, targetId, targetType };
    },

    /**
     * Cerrar menú contextual
     */
    closeContextMenu() {
      this.contextMenu = null;
    },

    /**
     * Mostrar diálogo de confirmación
     */
    confirm(message, onConfirm, onCancel = null) {
      this.pendingConfirmation = { message, onConfirm, onCancel };
    },

    /**
     * Confirmar acción pendiente
     */
    confirmAction() {
      if (this.pendingConfirmation?.onConfirm) {
        this.pendingConfirmation.onConfirm();
      }
      this.pendingConfirmation = null;
    },

    /**
     * Cancelar acción pendiente
     */
    cancelConfirmation() {
      if (this.pendingConfirmation?.onCancel) {
        this.pendingConfirmation.onCancel();
      }
      this.pendingConfirmation = null;
    },

    /**
     * Establecer zoom del canvas
     */
    setCanvasZoom(zoom) {
      this.canvasZoom = Math.max(ZOOM_CONFIG.MIN, Math.min(ZOOM_CONFIG.MAX, zoom));
    },

    /**
     * Zoom in
     */
    zoomIn() {
      this.setCanvasZoom(this.canvasZoom + ZOOM_CONFIG.STEP);
    },

    /**
     * Zoom out
     */
    zoomOut() {
      this.setCanvasZoom(this.canvasZoom - ZOOM_CONFIG.STEP);
    },

    /**
     * Reset zoom
     */
    resetZoom() {
      this.canvasZoom = ZOOM_CONFIG.DEFAULT;
    },

    /**
     * Toggle modo preview
     */
    togglePreviewMode() {
      this.isPreviewMode = !this.isPreviewMode;
    },

    /**
     * Actualizar preferencia
     */
    updatePreference(key, value) {
      if (key in this.preferences) {
        this.preferences[key] = value;
        // Persistir en localStorage
        this.savePreferences();
      }
    },

    /**
     * Guardar preferencias en localStorage
     */
    savePreferences() {
      try {
        localStorage.setItem('flavorPageBuilderPrefs', JSON.stringify(this.preferences));
      } catch (error) {
        console.error('Error saving preferences:', error);
      }
    },

    /**
     * Cargar preferencias desde localStorage
     */
    loadPreferences() {
      try {
        const saved = localStorage.getItem('flavorPageBuilderPrefs');
        if (saved) {
          const prefs = JSON.parse(saved);
          this.preferences = { ...this.preferences, ...prefs };
        }
      } catch (error) {
        console.error('Error loading preferences:', error);
      }
    },

    /**
     * Mostrar tooltip
     */
    showTooltip(content, x, y) {
      this.activeTooltip = { content, x, y };
    },

    /**
     * Ocultar tooltip
     */
    hideTooltip() {
      this.activeTooltip = null;
    },
  },
});
