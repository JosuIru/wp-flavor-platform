/**
 * Visual Builder Pro - App Module: Design Tokens
 * Integración con sistema de variables --flavor-* existente
 *
 * @package Flavor_Chat_IA
 * @since 2.1.0
 */

window.VBPAppDesignTokens = {
    // Estado
    showTokensPanel: false,
    activeTokenCategory: 'colors',
    tokenSearchQuery: '',

    // Temas disponibles (sincronizados desde Flavor_Theme_Manager)
    availableThemes: [],
    activeThemeId: 'default',

    // Definición de tokens usando prefijo --flavor-* del sistema existente
    tokenDefinitions: {
        colors: {
            label: 'Colores',
            icon: '🎨',
            tokens: [
                { key: '--flavor-primary', label: 'Primario', type: 'color' },
                { key: '--flavor-primary-hover', label: 'Primario Hover', type: 'color' },
                { key: '--flavor-primary-light', label: 'Primario Claro', type: 'color' },
                { key: '--flavor-primary-dark', label: 'Primario Oscuro', type: 'color' },
                { key: '--flavor-secondary', label: 'Secundario', type: 'color' },
                { key: '--flavor-secondary-hover', label: 'Secundario Hover', type: 'color' },
                { key: '--flavor-success', label: 'Éxito', type: 'color' },
                { key: '--flavor-warning', label: 'Advertencia', type: 'color' },
                { key: '--flavor-error', label: 'Error', type: 'color' },
                { key: '--flavor-info', label: 'Info', type: 'color' }
            ]
        },
        backgrounds: {
            label: 'Fondos',
            icon: '📦',
            tokens: [
                { key: '--flavor-bg', label: 'Fondo Base', type: 'color' },
                { key: '--flavor-bg-secondary', label: 'Fondo Secundario', type: 'color' },
                { key: '--flavor-bg-tertiary', label: 'Fondo Terciario', type: 'color' }
            ]
        },
        text: {
            label: 'Texto',
            icon: '📝',
            tokens: [
                { key: '--flavor-text', label: 'Texto Base', type: 'color' },
                { key: '--flavor-text-secondary', label: 'Texto Secundario', type: 'color' },
                { key: '--flavor-text-muted', label: 'Texto Muted', type: 'color' }
            ]
        },
        borders: {
            label: 'Bordes',
            icon: '🔲',
            tokens: [
                { key: '--flavor-border', label: 'Borde Default', type: 'color' },
                { key: '--flavor-border-light', label: 'Borde Claro', type: 'color' },
                { key: '--flavor-radius-sm', label: 'Radio Pequeño', type: 'size' },
                { key: '--flavor-radius', label: 'Radio Default', type: 'size' },
                { key: '--flavor-radius-lg', label: 'Radio Grande', type: 'size' },
                { key: '--flavor-radius-full', label: 'Radio Completo', type: 'size' }
            ]
        },
        typography: {
            label: 'Tipografía',
            icon: '🔤',
            tokens: [
                { key: '--flavor-font-family', label: 'Familia Fuente', type: 'font' },
                { key: '--flavor-font-size-sm', label: 'Tamaño Pequeño', type: 'size' },
                { key: '--flavor-font-size', label: 'Tamaño Base', type: 'size' },
                { key: '--flavor-font-size-lg', label: 'Tamaño Grande', type: 'size' },
                { key: '--flavor-font-size-xl', label: 'Tamaño XL', type: 'size' }
            ]
        },
        spacing: {
            label: 'Espaciado',
            icon: '📐',
            tokens: [
                { key: '--flavor-spacing-xs', label: 'Espacio XS', type: 'size' },
                { key: '--flavor-spacing-sm', label: 'Espacio Pequeño', type: 'size' },
                { key: '--flavor-spacing', label: 'Espacio Normal', type: 'size' },
                { key: '--flavor-spacing-lg', label: 'Espacio Grande', type: 'size' },
                { key: '--flavor-spacing-xl', label: 'Espacio XL', type: 'size' }
            ]
        },
        shadows: {
            label: 'Sombras',
            icon: '💫',
            tokens: [
                { key: '--flavor-shadow-sm', label: 'Sombra Pequeña', type: 'shadow' },
                { key: '--flavor-shadow', label: 'Sombra Normal', type: 'shadow' },
                { key: '--flavor-shadow-lg', label: 'Sombra Grande', type: 'shadow' }
            ]
        }
    },

    // Valores personalizados del documento actual
    documentTokenOverrides: {},

    // ============ INICIALIZACIÓN ============

    /**
     * Inicializar sistema de tokens
     */
    initDesignTokens: function() {
        var self = this;

        // Cargar temas disponibles desde config
        if (typeof VBP_Config !== 'undefined' && VBP_Config.themes) {
            this.availableThemes = VBP_Config.themes;
        }

        // Cargar tema activo
        if (typeof VBP_Config !== 'undefined' && VBP_Config.activeTheme) {
            this.activeThemeId = VBP_Config.activeTheme;
        }

        // Cargar overrides del documento
        this.loadDocumentOverrides();

        // Aplicar tokens al canvas
        this.applyTokensToCanvas();

        vbpLog.log(' Design Tokens inicializados - Tema:', this.activeThemeId);
    },

    /**
     * Cargar overrides de tokens desde el documento
     */
    loadDocumentOverrides: function() {
        var store = Alpine.store('vbp');
        if (store && store.settings && store.settings.tokenOverrides) {
            this.documentTokenOverrides = JSON.parse(JSON.stringify(store.settings.tokenOverrides));
        } else {
            this.documentTokenOverrides = {};
        }
    },

    // ============ APLICACIÓN DE TOKENS ============

    /**
     * Aplicar tokens al canvas
     */
    applyTokensToCanvas: function() {
        var canvas = document.querySelector('.vbp-canvas-content');
        if (!canvas) return;

        var self = this;

        // Aplicar overrides del documento
        Object.keys(this.documentTokenOverrides).forEach(function(tokenKey) {
            canvas.style.setProperty(tokenKey, self.documentTokenOverrides[tokenKey]);
        });
    },

    /**
     * Actualizar un token específico
     */
    updateToken: function(tokenKey, value) {
        this.documentTokenOverrides[tokenKey] = value;

        // Aplicar inmediatamente al canvas
        var canvas = document.querySelector('.vbp-canvas-content');
        if (canvas) {
            canvas.style.setProperty(tokenKey, value);
        }

        this.saveTokenOverrides();
    },

    /**
     * Obtener valor actual de un token
     */
    getTokenValue: function(tokenKey) {
        // Primero verificar overrides del documento
        if (this.documentTokenOverrides[tokenKey]) {
            return this.documentTokenOverrides[tokenKey];
        }

        // Obtener del CSS computed (tema global)
        var canvas = document.querySelector('.vbp-canvas-content');
        if (canvas) {
            var computedValue = getComputedStyle(canvas).getPropertyValue(tokenKey).trim();
            if (computedValue) {
                return computedValue;
            }
        }

        // Fallback desde :root
        return getComputedStyle(document.documentElement).getPropertyValue(tokenKey).trim();
    },

    /**
     * Resetear token al valor del tema
     */
    resetToken: function(tokenKey) {
        if (this.documentTokenOverrides[tokenKey]) {
            delete this.documentTokenOverrides[tokenKey];
        }

        var canvas = document.querySelector('.vbp-canvas-content');
        if (canvas) {
            canvas.style.removeProperty(tokenKey);
        }

        this.saveTokenOverrides();
    },

    /**
     * Resetear todos los overrides
     */
    resetAllTokens: function() {
        this.documentTokenOverrides = {};

        var canvas = document.querySelector('.vbp-canvas-content');
        if (canvas) {
            canvas.removeAttribute('style');
        }

        this.saveTokenOverrides();
        this.showNotification('Tokens reseteados al tema global', 'success');
    },

    // ============ PERSISTENCIA ============

    /**
     * Guardar overrides en el documento
     */
    saveTokenOverrides: function() {
        var store = Alpine.store('vbp');
        if (!store) return;

        if (!store.settings) {
            store.settings = {};
        }
        store.settings.tokenOverrides = JSON.parse(JSON.stringify(this.documentTokenOverrides));
        store.isDirty = true;
    },

    // ============ UI HELPERS ============

    /**
     * Obtener tokens filtrados por búsqueda
     */
    getFilteredTokens: function() {
        var categoryTokens = this.tokenDefinitions[this.activeTokenCategory];
        if (!categoryTokens) return [];

        if (!this.tokenSearchQuery) {
            return categoryTokens.tokens;
        }

        var query = this.tokenSearchQuery.toLowerCase();
        return categoryTokens.tokens.filter(function(token) {
            return token.label.toLowerCase().includes(query) ||
                   token.key.toLowerCase().includes(query);
        });
    },

    /**
     * Obtener categorías de tokens
     */
    getTokenCategories: function() {
        var self = this;
        return Object.keys(this.tokenDefinitions).map(function(key) {
            return {
                id: key,
                label: self.tokenDefinitions[key].label,
                icon: self.tokenDefinitions[key].icon
            };
        });
    },

    /**
     * Verificar si un token tiene override
     */
    isTokenOverridden: function(tokenKey) {
        return this.documentTokenOverrides.hasOwnProperty(tokenKey);
    },

    /**
     * Obtener lista de temas para el selector
     */
    getThemesList: function() {
        return this.availableThemes.map(function(theme) {
            return {
                id: theme.id,
                name: theme.name,
                description: theme.description || '',
                category: theme.category || 'general',
                colors: [
                    theme.variables['--flavor-primary'] || '#3b82f6',
                    theme.variables['--flavor-secondary'] || '#6b7280',
                    theme.variables['--flavor-bg'] || '#ffffff'
                ]
            };
        });
    },

    /**
     * Cambiar tema activo (cambia el tema global de la página)
     */
    changeTheme: function(themeId) {
        var self = this;

        // Llamar al backend para cambiar el tema
        fetch(VBP_Config.restUrl.replace('/documents', '/theme/change'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({ theme_id: themeId })
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                self.activeThemeId = themeId;
                self.showNotification('Tema cambiado a "' + themeId + '"', 'success');
                // Recargar para aplicar el nuevo tema
                window.location.reload();
            } else {
                self.showNotification('Error cambiando tema', 'error');
            }
        })
        .catch(function(error) {
            vbpLog.error(' Error cambiando tema:', error);
            self.showNotification('Error cambiando tema', 'error');
        });
    },

    /**
     * Abrir panel de tokens
     */
    openTokensPanel: function() {
        this.showTokensPanel = true;
        this.loadDocumentOverrides();
    },

    /**
     * Cerrar panel de tokens
     */
    closeTokensPanel: function() {
        this.showTokensPanel = false;
    },

    // ============ EXPORT/IMPORT ============

    /**
     * Exportar tokens como JSON
     */
    exportTokens: function() {
        var exportData = {
            themeId: this.activeThemeId,
            overrides: this.documentTokenOverrides,
            exportedAt: new Date().toISOString()
        };

        var tokensJson = JSON.stringify(exportData, null, 2);
        var blob = new Blob([tokensJson], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'flavor-design-tokens.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        this.showNotification('Tokens exportados', 'success');
    },

    /**
     * Importar tokens desde JSON
     */
    importTokens: function(jsonString) {
        try {
            var data = JSON.parse(jsonString);
            if (data.overrides) {
                this.documentTokenOverrides = data.overrides;
                this.applyTokensToCanvas();
                this.saveTokenOverrides();
                this.showNotification('Tokens importados correctamente', 'success');
                return true;
            }
            throw new Error('Formato de tokens inválido');
        } catch (error) {
            this.showNotification('Error importando tokens: ' + error.message, 'error');
            return false;
        }
    },

    // ============ UTILIDADES ============

    /**
     * Obtener CSS con todos los tokens actuales
     */
    getTokensAsCSS: function() {
        var css = '/* Flavor Design Tokens - Document Overrides */\n';
        css += ':root {\n';

        var self = this;
        Object.keys(this.documentTokenOverrides).forEach(function(key) {
            css += '  ' + key + ': ' + self.documentTokenOverrides[key] + ';\n';
        });

        css += '}\n';
        return css;
    },

    /**
     * Copiar CSS de tokens al portapapeles
     */
    copyTokensCSS: function() {
        var css = this.getTokensAsCSS();
        var self = this;

        navigator.clipboard.writeText(css).then(function() {
            self.showNotification('CSS de tokens copiado', 'success');
        }).catch(function() {
            var textarea = document.createElement('textarea');
            textarea.value = css;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            self.showNotification('CSS de tokens copiado', 'success');
        });
    },

    /**
     * Insertar variable de token en el inspector activo
     */
    insertTokenVariable: function(tokenKey) {
        // Copiar la referencia de la variable al portapapeles
        var varReference = 'var(' + tokenKey + ')';
        var self = this;

        navigator.clipboard.writeText(varReference).then(function() {
            self.showNotification('Variable copiada: ' + varReference, 'success');
        }).catch(function() {
            self.showNotification('Variable: ' + varReference, 'info');
        });
    },

    /**
     * Obtener paleta de colores rápida del tema actual
     */
    getQuickColorPalette: function() {
        var self = this;
        return [
            { key: '--flavor-primary', label: 'Primary', value: this.getTokenValue('--flavor-primary') },
            { key: '--flavor-secondary', label: 'Secondary', value: this.getTokenValue('--flavor-secondary') },
            { key: '--flavor-success', label: 'Success', value: this.getTokenValue('--flavor-success') },
            { key: '--flavor-warning', label: 'Warning', value: this.getTokenValue('--flavor-warning') },
            { key: '--flavor-error', label: 'Error', value: this.getTokenValue('--flavor-error') },
            { key: '--flavor-info', label: 'Info', value: this.getTokenValue('--flavor-info') }
        ];
    },

    /**
     * Editar valor de un token
     */
    editTokenValue: function(token) {
        var self = this;
        var currentValue = this.getTokenValue(token.key);
        var newValue = null;

        // Para colores, usar input type color
        if (token.type === 'color') {
            // Crear un input de color temporal
            var colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.value = this.normalizeColor(currentValue);
            colorInput.style.position = 'fixed';
            colorInput.style.opacity = '0';
            colorInput.style.pointerEvents = 'none';
            document.body.appendChild(colorInput);

            colorInput.addEventListener('input', function(event) {
                self.updateToken(token.key, event.target.value);
            });

            colorInput.addEventListener('change', function(event) {
                self.updateToken(token.key, event.target.value);
                document.body.removeChild(colorInput);
            });

            colorInput.click();
        } else {
            // Para otros tipos, usar prompt
            newValue = prompt('Nuevo valor para ' + token.label + ':', currentValue);
            if (newValue !== null && newValue !== currentValue) {
                this.updateToken(token.key, newValue);
                this.showNotification('Token actualizado: ' + token.label, 'success');
            }
        }
    },

    /**
     * Normalizar color a formato hex
     */
    normalizeColor: function(colorValue) {
        if (!colorValue) return '#000000';

        // Si ya es hex, devolverlo
        if (colorValue.startsWith('#')) {
            // Asegurar 6 dígitos
            if (colorValue.length === 4) {
                return '#' + colorValue[1] + colorValue[1] + colorValue[2] + colorValue[2] + colorValue[3] + colorValue[3];
            }
            return colorValue;
        }

        // Si es rgb o rgba, convertir a hex
        var rgbMatch = colorValue.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (rgbMatch) {
            var r = parseInt(rgbMatch[1]).toString(16).padStart(2, '0');
            var g = parseInt(rgbMatch[2]).toString(16).padStart(2, '0');
            var b = parseInt(rgbMatch[3]).toString(16).padStart(2, '0');
            return '#' + r + g + b;
        }

        // Fallback
        return '#000000';
    }
};
