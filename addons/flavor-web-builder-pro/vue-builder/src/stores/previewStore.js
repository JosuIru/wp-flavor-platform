import { defineStore } from 'pinia';

// Configuración del cache y peticiones (exportable para tests)
export const PREVIEW_CONFIG = Object.freeze({
  CACHE_MAX_AGE_MS: 5 * 60 * 1000,      // 5 minutos
  CLEANUP_INTERVAL_MS: 2 * 60 * 1000,   // 2 minutos
  REFRESH_DEBOUNCE_MS: 300,             // 300ms
  CONCURRENCY_LIMIT: 3,                 // Peticiones simultáneas máximas
});

/**
 * Store para gestión de previews de bloques
 * Maneja cache, peticiones AJAX y optimizaciones
 */
export const usePreviewStore = defineStore('preview', {
  state: () => ({
    // Cache de HTML preview por bloque con timestamps
    // Formato: { [blockId]: { html: string, timestamp: number, valuesHash: string } }
    htmlCache: {},

    // Tiempo máximo de validez del cache
    cacheMaxAge: PREVIEW_CONFIG.CACHE_MAX_AGE_MS,

    // AbortControllers para cancelar peticiones de preview obsoletas
    abortControllers: {},

    // Debounce timeouts por bloque
    _debounceTimeouts: {},

    // Intervalo de limpieza de cache
    _cleanupInterval: null,

    // Configuración de API
    ajaxUrl: '',
    nonce: '',
    postId: 0,
  }),

  getters: {
    /**
     * Obtener estadísticas del cache
     */
    cacheStats: (state) => {
      const now = Date.now();
      let validCount = 0;
      let expiredCount = 0;
      let totalSize = 0;

      for (const cached of Object.values(state.htmlCache)) {
        if (cached.timestamp) {
          const isExpired = now - cached.timestamp > state.cacheMaxAge;
          if (isExpired) {
            expiredCount++;
          } else {
            validCount++;
          }
          totalSize += (cached.html?.length || 0);
        }
      }

      return {
        total: Object.keys(state.htmlCache).length,
        valid: validCount,
        expired: expiredCount,
        size: totalSize,
        pendingRequests: Object.keys(state.abortControllers).length,
      };
    },
  },

  actions: {
    /**
     * Inicializar el store con configuración de API
     */
    initialize(config) {
      this.ajaxUrl = config.ajaxUrl || '';
      this.nonce = config.nonce || '';
      this.postId = config.postId || 0;

      // Limpiar cache de previews expirados periódicamente
      if (typeof window !== 'undefined') {
        this._cleanupInterval = setInterval(() => {
          this.cleanupExpired();
        }, PREVIEW_CONFIG.CLEANUP_INTERVAL_MS);
      }
    },

    /**
     * Generar hash simple de valores para detectar cambios
     */
    hashValues(values) {
      return JSON.stringify(values || {});
    },

    /**
     * Verificar si el cache de un bloque es válido
     */
    isCacheValid(blockId, currentValues) {
      const cached = this.htmlCache[blockId];
      if (!cached || !cached.timestamp) return false;

      const now = Date.now();
      const isExpired = now - cached.timestamp > this.cacheMaxAge;
      if (isExpired) return false;

      // Verificar si los valores han cambiado
      const currentHash = this.hashValues(currentValues);
      return cached.valuesHash === currentHash;
    },

    /**
     * Obtener preview cacheado de un bloque
     */
    getCached(blockId, currentValues) {
      if (this.isCacheValid(blockId, currentValues)) {
        return this.htmlCache[blockId].html;
      }
      return null;
    },

    /**
     * Obtener HTML del cache (incluyendo expirado)
     */
    getHtml(blockId) {
      const cached = this.htmlCache[blockId];
      if (!cached) return '';
      return typeof cached === 'string' ? cached : cached.html || '';
    },

    /**
     * Refrescar preview de un bloque via AJAX
     */
    async refresh(block, forceRefresh = false) {
      if (!block) return;

      const blockId = block.id;

      // Verificar cache antes de hacer petición (a menos que se fuerce)
      if (!forceRefresh && this.isCacheValid(blockId, block.values)) {
        return;
      }

      // Cancelar petición anterior para este bloque si existe
      if (this.abortControllers[blockId]) {
        this.abortControllers[blockId].abort();
      }

      // Crear nuevo AbortController para esta petición
      const abortController = new AbortController();
      this.abortControllers[blockId] = abortController;

      try {
        const formData = new FormData();
        formData.append('action', 'flavor_preview_component');
        formData.append('nonce', this.nonce);
        formData.append('component_id', block.componentId);
        formData.append('post_id', this.postId);
        formData.append('component_data', JSON.stringify(block.values || {}));
        formData.append('component_settings', JSON.stringify({
          variant: block.variant || '',
        }));

        const response = await fetch(this.ajaxUrl, {
          method: 'POST',
          body: formData,
          signal: abortController.signal,
        });

        const data = await response.json();
        if (data.success && data.data?.html) {
          this.htmlCache[blockId] = {
            html: data.data.html,
            timestamp: Date.now(),
            valuesHash: this.hashValues(block.values),
          };
        }
      } catch (error) {
        if (error.name === 'AbortError') {
          return;
        }
        console.error('Error refreshing preview:', error);
      } finally {
        if (this.abortControllers[blockId] === abortController) {
          delete this.abortControllers[blockId];
        }
      }
    },

    /**
     * Refrescar preview con debounce
     */
    refreshDebounced(block, delay = PREVIEW_CONFIG.REFRESH_DEBOUNCE_MS) {
      if (!block) return;

      const blockId = block.id;

      if (this._debounceTimeouts[blockId]) {
        clearTimeout(this._debounceTimeouts[blockId]);
      }

      this._debounceTimeouts[blockId] = setTimeout(() => {
        this.refresh(block);
        delete this._debounceTimeouts[blockId];
      }, delay);
    },

    /**
     * Refrescar todos los previews (paralelizado con límite de concurrencia)
     */
    async refreshAll(blocks) {
      for (let i = 0; i < blocks.length; i += PREVIEW_CONFIG.CONCURRENCY_LIMIT) {
        const batch = blocks.slice(i, i + PREVIEW_CONFIG.CONCURRENCY_LIMIT);
        await Promise.all(batch.map(block => this.refresh(block)));
      }
    },

    /**
     * Limpiar cache de bloques eliminados
     */
    cleanupDeleted(validBlockIds) {
      const validSet = new Set(validBlockIds);
      let cleaned = 0;

      for (const blockId of Object.keys(this.htmlCache)) {
        if (!validSet.has(blockId)) {
          delete this.htmlCache[blockId];
          cleaned++;
        }
      }

      // Cancelar y limpiar abort controllers huérfanos
      for (const blockId of Object.keys(this.abortControllers)) {
        if (!validSet.has(blockId)) {
          this.abortControllers[blockId].abort();
          delete this.abortControllers[blockId];
          cleaned++;
        }
      }

      return cleaned;
    },

    /**
     * Limpiar cache expirado
     */
    cleanupExpired() {
      const now = Date.now();
      let cleaned = 0;

      for (const blockId of Object.keys(this.htmlCache)) {
        const cached = this.htmlCache[blockId];
        if (cached.timestamp && now - cached.timestamp > this.cacheMaxAge) {
          delete this.htmlCache[blockId];
          cleaned++;
        }
      }

      return cleaned;
    },

    /**
     * Invalidar cache para bloques específicos
     */
    invalidate(blockIds = null) {
      if (blockIds === null) {
        this.htmlCache = {};
        return;
      }

      const idsToInvalidate = Array.isArray(blockIds) ? blockIds : [blockIds];
      for (const blockId of idsToInvalidate) {
        delete this.htmlCache[blockId];
      }
    },

    /**
     * Cancelar petición de preview
     */
    cancelRequest(blockId) {
      if (this.abortControllers[blockId]) {
        this.abortControllers[blockId].abort();
        delete this.abortControllers[blockId];
      }
    },

    /**
     * Limpiar recursos del store
     */
    cleanup() {
      // Cancelar todas las peticiones pendientes
      for (const blockId of Object.keys(this.abortControllers)) {
        this.abortControllers[blockId].abort();
      }
      this.abortControllers = {};

      // Cancelar todos los timeouts de debounce
      for (const blockId of Object.keys(this._debounceTimeouts)) {
        clearTimeout(this._debounceTimeouts[blockId]);
      }
      this._debounceTimeouts = {};

      // Limpiar intervalo de limpieza de cache
      if (this._cleanupInterval) {
        clearInterval(this._cleanupInterval);
        this._cleanupInterval = null;
      }
    },
  },
});
