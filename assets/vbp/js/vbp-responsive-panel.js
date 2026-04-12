/**
 * VBP Responsive Panel - Panel UI para gestión de variantes responsive
 *
 * Proporciona una interfaz para ver y editar overrides por breakpoint
 * en el panel inspector.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Componente Alpine para el panel de responsive variants
     */
    window.vbpResponsivePanelComponent = function() {
        return {
            /**
             * Estado del panel
             */
            isExpanded: true,
            showCompareMode: false,
            compareBreakpointA: 'desktop',
            compareBreakpointB: 'tablet',

            /**
             * Inicialización del componente
             */
            init: function() {
                var self = this;

                // Escuchar cambios de breakpoint
                document.addEventListener('vbp:responsive:breakpointChanged', function(event) {
                    self.$nextTick(function() {
                        self.$el && self.$el.dispatchEvent(new CustomEvent('refresh'));
                    });
                });

                // Escuchar cambios de selección
                document.addEventListener('vbp:selection:changed', function() {
                    self.$nextTick(function() {
                        self.$el && self.$el.dispatchEvent(new CustomEvent('refresh'));
                    });
                });
            },

            /**
             * Obtiene el breakpoint actual
             */
            get currentBreakpoint() {
                if (window.VBPResponsiveVariants) {
                    return window.VBPResponsiveVariants.getCurrentBreakpoint();
                }
                return 'desktop';
            },

            /**
             * Obtiene la configuración de breakpoints
             */
            get breakpoints() {
                if (window.VBPResponsiveVariants) {
                    return window.VBPResponsiveVariants.getBreakpoints();
                }
                return {
                    desktop: { label: 'Desktop', canvasWidth: 1200 },
                    laptop: { label: 'Laptop', canvasWidth: 1024 },
                    tablet: { label: 'Tablet', canvasWidth: 768 },
                    mobile: { label: 'Mobile', canvasWidth: 375 }
                };
            },

            /**
             * Obtiene el elemento seleccionado
             */
            get selectedElement() {
                var store = Alpine.store('vbp');
                if (store && store.selection.elementIds.length === 1) {
                    return store.getElementDeep(store.selection.elementIds[0]);
                }
                return null;
            },

            /**
             * Verifica si hay un elemento seleccionado
             */
            get hasSelection() {
                return this.selectedElement !== null;
            },

            /**
             * Verifica si es el breakpoint base (desktop)
             */
            get isBaseBreakpoint() {
                return this.currentBreakpoint === 'desktop';
            },

            /**
             * Obtiene el ancho actual del canvas
             */
            get canvasWidth() {
                if (window.VBPResponsiveVariants) {
                    return window.VBPResponsiveVariants.getCanvasWidth();
                }
                return 1200;
            },

            /**
             * Verifica si el elemento tiene overrides para el breakpoint actual
             */
            get hasOverridesForCurrentBreakpoint() {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return false;
                }
                return window.VBPResponsiveVariants.hasOverrides(
                    this.selectedElement.id,
                    this.currentBreakpoint
                );
            },

            /**
             * Obtiene la lista de propiedades con override
             */
            get overriddenProperties() {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return [];
                }
                return window.VBPResponsiveVariants.getOverriddenProps(
                    this.selectedElement.id,
                    this.currentBreakpoint
                );
            },

            /**
             * Obtiene breakpoints que tienen overrides
             */
            get breakpointsWithOverrides() {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return [];
                }

                var breakpointsWithChanges = [];
                var elementId = this.selectedElement.id;

                for (var breakpointId in this.breakpoints) {
                    if (breakpointId !== 'desktop' &&
                        window.VBPResponsiveVariants.hasOverrides(elementId, breakpointId)) {
                        breakpointsWithChanges.push(breakpointId);
                    }
                }

                return breakpointsWithChanges;
            },

            /**
             * Cambia al breakpoint especificado
             *
             * @param {string} breakpoint ID del breakpoint
             */
            setBreakpoint: function(breakpoint) {
                if (window.VBPResponsiveVariants) {
                    window.VBPResponsiveVariants.setBreakpoint(breakpoint);
                }
            },

            /**
             * Obtiene el icono SVG para un breakpoint
             *
             * @param {string} breakpoint ID del breakpoint
             * @returns {string} HTML del icono
             */
            getBreakpointIcon: function(breakpoint) {
                if (window.VBPResponsiveVariants) {
                    return window.VBPResponsiveVariants.getBreakpointIcon(breakpoint);
                }
                return '';
            },

            /**
             * Verifica si un breakpoint tiene overrides
             *
             * @param {string} breakpoint ID del breakpoint
             * @returns {boolean}
             */
            breakpointHasOverrides: function(breakpoint) {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return false;
                }
                return window.VBPResponsiveVariants.hasOverrides(
                    this.selectedElement.id,
                    breakpoint
                );
            },

            /**
             * Limpia todos los overrides del breakpoint actual
             */
            clearCurrentOverrides: function() {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return;
                }

                if (confirm('¿Eliminar todos los cambios de ' + this.breakpoints[this.currentBreakpoint].label + '?')) {
                    window.VBPResponsiveVariants.clearAllOverrides(
                        this.selectedElement.id,
                        this.currentBreakpoint
                    );

                    // Notificar
                    this.showToast('Overrides eliminados');
                }
            },

            /**
             * Elimina el override de una propiedad específica
             *
             * @param {string} propertyPath Path de la propiedad
             */
            clearPropertyOverride: function(propertyPath) {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return;
                }

                window.VBPResponsiveVariants.clearOverride(
                    this.selectedElement.id,
                    this.currentBreakpoint,
                    propertyPath
                );
            },

            /**
             * Copia el layout de desktop al breakpoint actual
             */
            copyFromDesktop: function() {
                if (!this.selectedElement || !window.VBPResponsiveVariants || this.isBaseBreakpoint) {
                    return;
                }

                window.VBPResponsiveVariants.copyLayout(
                    this.selectedElement.id,
                    'desktop',
                    this.currentBreakpoint
                );

                this.showToast('Layout copiado desde Desktop');
            },

            /**
             * Copia el layout actual a todos los breakpoints
             */
            copyToAllBreakpoints: function() {
                if (!this.selectedElement || !window.VBPResponsiveVariants) {
                    return;
                }

                var self = this;
                for (var breakpointId in this.breakpoints) {
                    if (breakpointId !== this.currentBreakpoint) {
                        window.VBPResponsiveVariants.copyLayout(
                            self.selectedElement.id,
                            self.currentBreakpoint,
                            breakpointId
                        );
                    }
                }

                this.showToast('Layout copiado a todos los breakpoints');
            },

            /**
             * Obtiene las diferencias entre dos breakpoints
             */
            get comparisonDifferences() {
                if (!this.selectedElement || !window.VBPResponsiveVariants || !this.showCompareMode) {
                    return { changed: [], addedInB: [], removedInB: [] };
                }

                return window.VBPResponsiveVariants.getDifferences(
                    this.selectedElement.id,
                    this.compareBreakpointA,
                    this.compareBreakpointB
                );
            },

            /**
             * Activa/desactiva el modo comparación
             */
            toggleCompareMode: function() {
                this.showCompareMode = !this.showCompareMode;
            },

            /**
             * Formatea el nombre de una propiedad para mostrar
             *
             * @param {string} propertyPath Path de la propiedad
             * @returns {string} Nombre formateado
             */
            formatPropertyName: function(propertyPath) {
                // Convertir camelCase y paths a formato legible
                var formattedName = propertyPath
                    .replace(/\./g, ' > ')
                    .replace(/([A-Z])/g, ' $1')
                    .toLowerCase()
                    .replace(/^./, function(str) { return str.toUpperCase(); });

                return formattedName;
            },

            /**
             * Muestra un toast de notificación
             *
             * @param {string} message Mensaje a mostrar
             */
            showToast: function(message) {
                if (window.VBPToast && typeof window.VBPToast.show === 'function') {
                    window.VBPToast.show(message, 'success');
                } else if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbpToast')) {
                    Alpine.store('vbpToast').show(message, 'success');
                } else {
                    console.log('[VBP Responsive]', message);
                }
            },

            /**
             * Genera el HTML del panel completo
             */
            renderPanel: function() {
                // El HTML se genera mediante Alpine templates en la vista
                return '';
            }
        };
    };

    /**
     * Registrar componente con Alpine cuando esté listo
     */
    document.addEventListener('alpine:init', function() {
        Alpine.data('vbpResponsivePanel', window.vbpResponsivePanelComponent);
    });

    /**
     * HTML Template del panel responsive para usar en el inspector
     * Se puede incluir en la vista del inspector
     */
    window.VBPResponsivePanelTemplate = '\
<div class="vbp-responsive-panel" x-data="vbpResponsivePanel()">\
    <!-- Header -->\
    <div class="vbp-responsive-panel__header">\
        <div class="vbp-responsive-panel__title">\
            <svg class="vbp-responsive-panel__title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">\
                <rect x="2" y="3" width="20" height="14" rx="2"/>\
                <rect x="7" y="9" width="10" height="8" rx="1"/>\
            </svg>\
            <span>Responsive</span>\
        </div>\
        <div class="vbp-responsive-panel__breakpoints">\
            <template x-for="(config, bp) in breakpoints" :key="bp">\
                <button\
                    class="vbp-responsive-panel__bp-btn"\
                    :class="{\
                        \'is-active\': currentBreakpoint === bp,\
                        \'has-overrides\': breakpointHasOverrides(bp)\
                    }"\
                    @click="setBreakpoint(bp)"\
                    :title="config.label + \' (\' + config.canvasWidth + \'px)\'"\
                    x-html="getBreakpointIcon(bp)">\
                </button>\
            </template>\
        </div>\
    </div>\
    \
    <!-- Status -->\
    <template x-if="hasSelection">\
        <div class="vbp-responsive-panel__status">\
            <div class="vbp-responsive-panel__status-icon" x-html="getBreakpointIcon(currentBreakpoint)"></div>\
            <div class="vbp-responsive-panel__status-info">\
                <div class="vbp-responsive-panel__status-label" x-text="breakpoints[currentBreakpoint].label"></div>\
                <div class="vbp-responsive-panel__status-value" x-text="canvasWidth + \'px\'"></div>\
            </div>\
        </div>\
    </template>\
    \
    <!-- Base indicator -->\
    <template x-if="isBaseBreakpoint && hasSelection">\
        <div class="vbp-responsive-panel__base-indicator">\
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">\
                <circle cx="12" cy="12" r="10"/>\
                <path d="M12 8v8M8 12h8"/>\
            </svg>\
            <span>Estilos base - Los cambios se heredan</span>\
        </div>\
    </template>\
    \
    <!-- Overrides list -->\
    <template x-if="!isBaseBreakpoint && hasOverridesForCurrentBreakpoint">\
        <div class="vbp-responsive-panel__overrides">\
            <div class="vbp-responsive-panel__overrides-title">\
                Propiedades modificadas en <strong x-text="breakpoints[currentBreakpoint].label"></strong>\
            </div>\
            <div class="vbp-responsive-panel__override-list">\
                <template x-for="prop in overriddenProperties" :key="prop">\
                    <div class="vbp-responsive-panel__override-item">\
                        <span class="vbp-responsive-panel__override-prop" x-text="formatPropertyName(prop)"></span>\
                        <button\
                            class="vbp-responsive-panel__override-clear"\
                            @click="clearPropertyOverride(prop)"\
                            title="Eliminar override">\
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">\
                                <path d="M18 6L6 18M6 6l12 12"/>\
                            </svg>\
                        </button>\
                    </div>\
                </template>\
            </div>\
        </div>\
    </template>\
    \
    <!-- Actions -->\
    <template x-if="hasSelection">\
        <div class="vbp-responsive-panel__actions">\
            <template x-if="!isBaseBreakpoint">\
                <button class="vbp-responsive-panel__action-btn" @click="copyFromDesktop()">\
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">\
                        <rect x="9" y="9" width="13" height="13" rx="2"/>\
                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>\
                    </svg>\
                    <span>Copiar de Desktop</span>\
                </button>\
            </template>\
            <template x-if="hasOverridesForCurrentBreakpoint">\
                <button class="vbp-responsive-panel__action-btn vbp-responsive-panel__action-btn--danger" @click="clearCurrentOverrides()">\
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">\
                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/>\
                    </svg>\
                    <span>Limpiar overrides</span>\
                </button>\
            </template>\
        </div>\
    </template>\
    \
    <!-- No selection message -->\
    <template x-if="!hasSelection">\
        <div class="vbp-responsive-panel__empty">\
            <p>Selecciona un elemento para ver sus variantes responsive.</p>\
        </div>\
    </template>\
</div>';

})();
